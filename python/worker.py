"""
Auto Backlink Pro - Python Automation Worker
Main entry point for the Python worker that processes automation tasks
"""

import os
import time
import logging
import sys
import argparse
import requests
from dotenv import load_dotenv
from api_client import LaravelAPIClient
from automation.comment import CommentAutomation
from automation.profile import ProfileAutomation
from automation.forum import ForumAutomation
from automation.guest import GuestPostAutomation
from automation.email_confirmation import EmailConfirmationAutomation
from automation_logger import get_logger
from shadow_mode_logger import get_shadow_logger
from core.telemetry import init_run, log_step, save_snapshot, finalize_run
from core.failure_mapper import FailureMapper
from core.failure_enums import FailureReason
from core.state_detector import StateDetector
from core.popup_controller import PopupController
from core.budget_guard import BudgetGuard, BudgetConfig, BudgetExceededException, BudgetExceededReason
from core.domain_memory import get_domain_memory
from runtime.agent import RuntimeAgent
from runtime.healer import RuntimeHealer

# Load environment variables from .env file (but don't override existing env vars)
# This allows Process environment variables to take precedence
load_dotenv(override=False)

# Configure logging
# Determine log file location - ensure directory exists and is writable
log_handlers = [logging.StreamHandler(sys.stdout)]

try:
    # Try to use the script's directory first
    log_dir = os.path.dirname(os.path.abspath(__file__))
    log_file = os.path.join(log_dir, 'worker.log')
    # Ensure directory exists
    os.makedirs(log_dir, exist_ok=True)
    # Try to create/open the log file to verify we can write
    try:
        # Test write access
        with open(log_file, 'a'):
            pass
        log_handlers.append(logging.FileHandler(log_file))
    except (OSError, IOError, PermissionError):
        # Can't write to script directory, try /tmp
        log_file = '/tmp/worker.log'
        log_handlers.append(logging.FileHandler(log_file))
except Exception:
    # Final fallback to /tmp
    log_file = '/tmp/worker.log'
    log_handlers.append(logging.FileHandler(log_file))

logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=log_handlers
)
logger = logging.getLogger(__name__)

# Configuration (defaults can be overridden via CLI flags)
# Read environment variables after load_dotenv() to ensure Process env vars take precedence
LARAVEL_API_URL = os.getenv('LARAVEL_API_URL', 'http://nginx')
# Try LARAVEL_API_TOKEN first, then APP_API_TOKEN as fallback
LARAVEL_API_TOKEN = os.getenv('LARAVEL_API_TOKEN') or os.getenv('APP_API_TOKEN') or ''
# Default poll interval: 60 seconds (to avoid hitting rate limits)
# Rate limit is 300 requests/hour = ~5 requests/minute
# With 10s interval, we make ~360 requests/hour (slightly over, but with retry logic it's fine)
# With 12s interval, we make ~300 requests/hour (exactly at limit)
DEFAULT_POLL_INTERVAL = int(os.getenv('POLL_INTERVAL', '12'))  # seconds
WORKER_ID = os.getenv('WORKER_ID', f'worker-{os.getpid()}')
# Shadow mode: AI predicts but rule-based system executes
SHADOW_MODE = os.getenv('SHADOW_MODE', 'false').lower() in ('true', '1', 'yes')


def get_automation_class(task_type: str):
    """Get automation class for task type"""
    automation_classes = {
        'comment': CommentAutomation,
        'profile': ProfileAutomation,
        'forum': ForumAutomation,
        'guest': GuestPostAutomation,
        'guestposting': GuestPostAutomation,  # Alias
        'email_confirmation_click': EmailConfirmationAutomation,
    }
    return automation_classes.get(task_type)


def process_task(api_client: LaravelAPIClient, task: dict):
    """Process a single task"""
    task_id = task['id']
    task_type = task['type']
    retry_count = task.get('retry_count', 0)
    
    # Initialize structured loggers
    automation_logger = get_logger()
    shadow_logger = get_shadow_logger()
    
    # Track execution time
    start_time = time.time()
    execution_time = None
    result_url = None
    error_message = None
    result_status = 'unknown'
    
    # Track AI prediction for shadow mode
    ai_prediction = None
    opportunity = None

    logger.info(f"Processing task {task_id} of type {task_type}")

    # Initialize telemetry run
    try:
        init_run(task_id, meta={
            'task_type': task_type,
            'campaign_id': task.get('campaign_id'),
            'retry_count': retry_count,
        })
        log_step(task_id, 'task_started', {'task_type': task_type})
    except Exception as telem_error:
        logger.warning(f"Failed to initialize telemetry: {telem_error}")
    
    # Initialize budget guard
    try:
        budget_config = BudgetConfig(
            max_runtime_seconds=int(os.getenv('MAX_TASK_RUNTIME_SECONDS', '300')),
            max_retries_per_step=int(os.getenv('MAX_RETRIES_PER_STEP', '3')),
            max_popup_dismiss_attempts=int(os.getenv('MAX_POPUP_DISMISS_ATTEMPTS', '5')),
            max_locator_candidates=int(os.getenv('MAX_LOCATOR_CANDIDATES', '10'))
        )
        BudgetGuard.init_task(task_id, budget_config)
        log_step(task_id, 'budget_guard_initialized')
    except Exception as budget_error:
        logger.warning(f"Failed to initialize budget guard: {budget_error}")
    
    # Get domain memory
    domain_memory = get_domain_memory()
    
    # Extract domain from opportunity URL if available
    domain = None
    try:
        opportunity_url = task.get('payload', {}).get('opportunity_url') or task.get('opportunity_url')
        if opportunity_url:
            from urllib.parse import urlparse
            parsed = urlparse(opportunity_url)
            domain = parsed.netloc or parsed.path.split('/')[0] if parsed.path else None
    except:
        pass
    
    # Check if domain should be skipped
    if domain:
        should_skip, skip_reason = domain_memory.should_skip(domain)
        if should_skip:
            logger.warning(f"Domain {domain} should be skipped: {skip_reason}")
            finalize_run(task_id, {
                'success': False,
                'failure_reason': FailureReason.BLOCKED.value,
                'error': f"Domain skipped: {skip_reason}",
            })
            api_client.update_task_status(task_id, 'failed')
            return

    # Clean up any lingering asyncio event loops before processing
    # This prevents event loops from persisting between tasks
    try:
        import asyncio

        # Aggressive cleanup: Clear any event loops
        try:
            # Check if there's a running loop
            loop = asyncio.get_running_loop()
            logger.error(f"CRITICAL: Running event loop detected before task {task_id}!")
            logger.error("This should not happen. A library is creating event loops.")
            logger.error(f"Loop: {loop}")
            # Can't stop a running loop safely, but we can try to identify it
            raise RuntimeError(
                f"Cannot process task {task_id}: Running asyncio event loop detected. "
                "This prevents Playwright Sync API from working."
            )
        except RuntimeError as e:
            if "no running event loop" not in str(e).lower() and "cannot process task" not in str(e).lower():
                raise
            # No running loop, good

        # Clear any non-running loops
        try:
            asyncio.set_event_loop(None)
        except Exception:
            pass

        # Don't set a restrictive policy - Playwright needs to create its own loop
        # We only ensure no running loops exist before Playwright starts

    except RuntimeError as e:
        if "cannot process task" in str(e).lower():
            raise
    except Exception:
        pass

    try:
        # Check runtime budget
        try:
            BudgetGuard.check_runtime(task_id)
        except BudgetExceededException as e:
            logger.error(f"Budget exceeded: {e.reason.value}")
            finalize_run(task_id, {
                'success': False,
                'failure_reason': FailureReason.TIMEOUT.value,
                'error': f"Budget exceeded: {e.reason.value}",
            })
            api_client.update_task_status(task_id, 'failed')
            return
        
        # Lock task
        log_step(task_id, 'locking_task')
        try:
            BudgetGuard.check_step_retry(task_id, 'lock_task')
        except BudgetExceededException as e:
            logger.error(f"Budget exceeded: {e.reason.value}")
            finalize_run(task_id, {
                'success': False,
                'failure_reason': FailureReason.TIMEOUT.value,
                'error': f"Budget exceeded: {e.reason.value}",
            })
            return
        
        lock_result = api_client.lock_task(task_id, WORKER_ID)
        if 'error' in lock_result:
            logger.warning(f"Failed to lock task {task_id}: {lock_result.get('error')}")
            finalize_run(task_id, {
                'success': False,
                'failure_reason': FailureReason.UNKNOWN.value,
                'error': 'Failed to lock task',
            })
            return

        # Update task status to running
        log_step(task_id, 'task_locked')
        api_client.update_task_status(task_id, 'running')
        log_step(task_id, 'status_set_to_running')

        # Get automation class
        log_step(task_id, 'getting_automation_class', {'task_type': task_type})
        automation_class = get_automation_class(task_type)
        if not automation_class:
            raise ValueError(f"Unknown task type: {task_type}")
        log_step(task_id, 'automation_class_obtained', {'class_name': automation_class.__name__})
        
        # Check for shadow mode AI prediction (from opportunity selector)
        # This will be set if OpportunitySelector ran in shadow mode
        # We'll log it after we get the opportunity from automation

        # Get proxy if needed - prefer country match from campaign
        log_step(task_id, 'getting_proxy')
        try:
            campaign = api_client.get_campaign(task['campaign_id'])
            if not campaign:
                raise ValueError(f"Campaign {task['campaign_id']} not found")

            campaign_country = campaign.get('company_country') or campaign.get('country_name')
            proxies = api_client.get_proxies(country=campaign_country, prefer_country=True)
            proxy = proxies[0] if proxies else None

            if proxy:
                logger.debug(f"Using proxy: {proxy.get('host')}:{proxy.get('port')}")
                log_step(task_id, 'proxy_obtained', {'proxy_host': proxy.get('host')})
            else:
                logger.debug("No proxy available, running without proxy")
                log_step(task_id, 'no_proxy_available')
        except Exception as e:
            logger.warning(f"Error getting campaign/proxy info: {e}, continuing without proxy")
            proxy = None
            log_step(task_id, 'proxy_error', {'error': str(e)})

        # Execute automation with proper error handling
        log_step(task_id, 'starting_automation_execution')
        try:
            with automation_class(api_client, proxy=proxy, headless=True) as automation:
                log_step(task_id, 'automation_context_entered')
                
                # Try to save initial snapshot if page is available
                try:
                    if hasattr(automation, 'page') and automation.page:
                        save_snapshot(task_id, automation.page, 'initial')
                except Exception as snap_error:
                    logger.debug(f"Could not save initial snapshot: {snap_error}")
                
                # Detect page state and clear popups before automation
                try:
                    if hasattr(automation, 'page') and automation.page:
                        log_step(task_id, 'page_state_detection_start')
                        page_state = StateDetector.analyze(automation.page)
                        log_step(task_id, 'page_state_detected', page_state.to_dict())
                        
                        # Clear popups if needed
                        popup_result = PopupController.clear_if_needed(
                            automation.page, 
                            task_id, 
                            state=page_state
                        )
                        log_step(task_id, 'popup_clear_complete', popup_result)
                except Exception as state_error:
                    logger.warning(f"Error in page state detection/popup clearing: {state_error}")
                    log_step(task_id, 'page_state_detection_error', {'error': str(state_error)})
                
                # Use RuntimeAgent to run the flow
                agent = RuntimeAgent(
                    task_id=task_id,
                    page=automation.page,
                    domain=domain or 'unknown',
                    goal=task_type
                )
                
                agent_result = agent.execute()
                log_step(task_id, 'agent_execution_completed', {
                    'success': agent_result.get('success', False),
                    'subgoal': agent_result.get('subgoal')
                })
                
                # If agent succeeded in preparing the flow, delegate to automation module
                if agent_result.get('success'):
                    # Agent has prepared the page (cleared popups, found forms, etc.)
                    # Now delegate to automation module for actual form filling
                    result = automation.execute(task)
                    log_step(task_id, 'automation_execution_completed', {'success': result.get('success', False)})
                    
                    # Record success/failure in domain memory
                    if domain:
                        if result.get('success'):
                            domain_memory.increment_stat(domain, 'successes', 1)
                        else:
                            failure_reason = result.get('failure_reason', FailureReason.UNKNOWN.value)
                            domain_memory.record_failure(domain, failure_reason)
                            
                            # Try healing if possible
                            healer = RuntimeHealer(task_id, automation.page, domain)
                            heal_result = healer.heal(
                                failure_reason,
                                context=result.get('context', {})
                            )
                            
                            if heal_result.get('success'):
                                log_step(task_id, 'healer_success', {
                                    'recovery_action': heal_result.get('recovery_action')
                                })
                                # Retry with healed context
                                result = automation.execute(task)
                                log_step(task_id, 'automation_retry_completed', {'success': result.get('success', False)})
                else:
                    # Agent failed, use agent result
                    result = {
                        'success': False,
                        'failure_reason': agent_result.get('failure_reason', FailureReason.UNKNOWN.value),
                        'error': agent_result.get('error', 'Agent execution failed'),
                        'subgoal': agent_result.get('subgoal')
                    }
                    
                    # Record failure in domain memory
                    if domain:
                        domain_memory.record_failure(domain, result['failure_reason'])
                    
                    log_step(task_id, 'agent_execution_failed', {
                        'failure_reason': result['failure_reason'],
                        'error': result['error']
                    })
                
                # Save final snapshot if page is available
                try:
                    if hasattr(automation, 'page') and automation.page:
                        save_snapshot(task_id, automation.page, 'final')
                except Exception as snap_error:
                    logger.debug(f"Could not save final snapshot: {snap_error}")
                
                # Capture opportunity for shadow mode logging
                if hasattr(automation, 'last_opportunity'):
                    opportunity = automation.last_opportunity
                    if opportunity and opportunity.get('shadow_mode'):
                        try:
                            ai_prediction = {
                                'action': opportunity.get('ai_recommended_action_type', task_type),
                                'probability': opportunity.get('ai_probability', 0.5),
                                'probabilities': opportunity.get('ai_probabilities', {}),
                            }
                            shadow_logger.log_prediction(
                                task_id=task_id,
                                campaign_id=task['campaign_id'],
                                backlink=opportunity,
                                rule_based_action=task_type,
                                ai_prediction=ai_prediction
                            )
                        except Exception as e:
                            logger.warning(f"Failed to log shadow mode prediction: {e}")
        except Exception as automation_error:
            # If automation setup or execution fails, handle it properly
            error_msg = f"Automation execution failed: {str(automation_error)}"
            logger.error(error_msg, exc_info=True)
            
            # Map failure reason
            failure_reason = FailureMapper.map(automation_error)
            log_step(task_id, 'automation_error', {
                'error': error_msg,
                'failure_reason': failure_reason.value,
            })
            
            # Calculate execution time
            execution_time = time.time() - start_time
            error_message = error_msg
            result_status = 'failed'

            # Finalize telemetry run
            try:
                finalize_run(task_id, {
                    'success': False,
                    'failure_reason': failure_reason.value,
                    'error': error_msg,
                    'execution_time': execution_time,
                    'retry_count': retry_count,
                })
            except Exception as telem_error:
                logger.warning(f"Failed to finalize telemetry: {telem_error}")

            # Unlock task and mark as failed
            try:
                api_client.unlock_task(task_id)
                api_client.update_task_status(
                    task_id,
                    'failed',
                    error_message=error_msg
                )
            except Exception as update_error:
                logger.error(f"Failed to update task {task_id} after automation error: {update_error}")

            # Log structured outcome
            try:
                automation_logger.log_outcome(
                    task_id=task_id,
                    action_attempted=task_type,
                    result=result_status,
                    error_message=error_message,
                    execution_time=execution_time,
                    retry_count=retry_count,
                    result_data={'error': error_msg, 'failure_reason': failure_reason.value}
                )
            except Exception as log_error:
                logger.warning(f"Failed to log automation outcome: {log_error}")

            # Re-raise to be caught by outer exception handler
            raise

        # Calculate execution time
        execution_time = time.time() - start_time
        
        # Get AI prediction for shadow mode (if available)
        # This was logged before execution, now we log the result
        ai_prediction = None
        if hasattr(automation, 'last_opportunity'):
            opp = automation.last_opportunity
            if opp and opp.get('shadow_mode'):
                ai_prediction = {
                    'action': opp.get('ai_recommended_action_type', task_type),
                    'probability': opp.get('ai_probability', 0.5),
                    'probabilities': opp.get('ai_probabilities', {}),
                }
        
        if result.get('success'):
            log_step(task_id, 'result_success')
            
            # Create backlink opportunity (campaign-specific)
            # backlink_id is required - it references the backlink from the store
            backlink_id = result.get('backlink_id')
            if not backlink_id:
                logger.error(f"Task {task_id} succeeded but no backlink_id in result. Cannot create opportunity.")
                api_client.update_task_status(
                    task_id,
                    'failed',
                    error_message='Automation succeeded but backlink_id missing from result'
                )
                
                # Log structured outcome
                try:
                    automation_logger.log_outcome(
                        task_id=task_id,
                        action_attempted=task_type,
                        result='failed',
                        error_message='backlink_id missing from result',
                        execution_time=execution_time,
                        retry_count=retry_count,
                        url=result.get('url')
                    )
                except Exception as log_error:
                    logger.warning(f"Failed to log automation outcome: {log_error}")
                
                return
            
            result_url = result.get('url')
            opportunity = api_client.create_backlink(
                campaign_id=task['campaign_id'],
                url=result_url,
                task_type=task_type,
                status='submitted',
                site_account_id=result.get('site_account_id'),
                backlink_id=backlink_id,  # Reference to backlink store
            )

            # Mark task as success
            api_client.update_task_status(
                task_id,
                'success',
                result={
                    'opportunity_id': opportunity.get('opportunity', {}).get('id') if isinstance(opportunity.get('opportunity'), dict) else opportunity.get('id'),
                    'backlink_id': backlink_id,
                    'url': result_url,
                }
            )

            logger.info(f"Task {task_id} completed successfully")
            
            # Finalize telemetry run (success)
            try:
                finalize_run(task_id, {
                    'success': True,
                    'execution_time': execution_time,
                    'retry_count': retry_count,
                    'url': result_url,
                    'backlink_id': backlink_id,
                })
            except Exception as telem_error:
                logger.warning(f"Failed to finalize telemetry: {telem_error}")
            
            # Log structured outcome (success)
            try:
                automation_logger.log_outcome(
                    task_id=task_id,
                    action_attempted=task_type,
                    result='success',
                    execution_time=execution_time,
                    retry_count=retry_count,
                    url=result_url,
                    result_data=result
                )
            except Exception as log_error:
                logger.warning(f"Failed to log automation outcome: {log_error}")
            
            # Log shadow mode result
            try:
                shadow_logger.log_result(
                    task_id=task_id,
                    rule_based_action=task_type,
                    task_result='success',
                    execution_time=execution_time,
                    retry_count=retry_count,
                    ai_prediction=ai_prediction
                )
            except Exception as log_error:
                logger.warning(f"Failed to log shadow mode result: {log_error}")
        else:
            # Mark task as failed
            error_msg = result.get('error', 'Unknown error')
            backlink_id = result.get('backlink_id')  # Get backlink_id from result if available
            result_url = result.get('url')
            error_message = error_msg
            result_status = 'failed'
            
            # Map failure reason from result or error message
            failure_reason = FailureReason.UNKNOWN
            if result.get('failure_reason'):
                try:
                    failure_reason = FailureReason.from_string(result.get('failure_reason'))
                except:
                    pass
            else:
                failure_reason = FailureMapper.map(error_msg)
            
            log_step(task_id, 'result_failed', {
                'error': error_msg,
                'failure_reason': failure_reason.value,
            })
            
            logger.error(f"Task {task_id} failed: {error_msg}")

            try:
                # Unlock task first
                api_client.unlock_task(task_id)

                # Then mark as failed (this will handle retry logic)
                # Include backlink_id in result so we can track failures per backlink
                result_data = {}
                if backlink_id:
                    result_data['backlink_id'] = backlink_id
                
                api_client.update_task_status(
                    task_id,
                    'failed',
                    error_message=error_msg,
                    result=result_data if result_data else None
                )
            except Exception as update_error:
                logger.error(f"Failed to update task {task_id} status after failure: {update_error}")
                # Try to unlock at least
                try:
                    api_client.unlock_task(task_id)
                except:
                    pass
            
            # Finalize telemetry run (failure)
            try:
                finalize_run(task_id, {
                    'success': False,
                    'failure_reason': failure_reason.value,
                    'error': error_msg,
                    'execution_time': execution_time,
                    'retry_count': retry_count,
                    'url': result_url,
                    'backlink_id': backlink_id,
                })
            except Exception as telem_error:
                logger.warning(f"Failed to finalize telemetry: {telem_error}")
            
            # Log structured outcome (failure)
            try:
                # Extract captcha_type from result if present
                captcha_type = result.get('captcha_type')
                
                automation_logger.log_outcome(
                    task_id=task_id,
                    action_attempted=task_type,
                    result=result_status,
                    failure_reason=failure_reason.value,
                    captcha_type=captcha_type,
                    error_message=error_message,
                    execution_time=execution_time,
                    retry_count=retry_count,
                    url=result_url,
                    result_data=result
                )
            except Exception as log_error:
                logger.warning(f"Failed to log automation outcome: {log_error}")
            
            # Log shadow mode result
            try:
                shadow_logger.log_result(
                    task_id=task_id,
                    rule_based_action=task_type,
                    task_result='failed',
                    execution_time=execution_time,
                    retry_count=retry_count,
                    ai_prediction=ai_prediction
                )
            except Exception as log_error:
                logger.warning(f"Failed to log shadow mode result: {log_error}")

    except Exception as e:
        error_msg = str(e)
        # Include traceback for better debugging (truncate if too long)
        import traceback
        tb = traceback.format_exc()
        full_error = f"{error_msg}\n\nTraceback:\n{tb}"

        # Truncate if too long (API will truncate to 1000 chars, but keep it reasonable)
        if len(full_error) > 2000:
            full_error = full_error[:1997] + "..."
        
        # Calculate execution time if not already calculated
        if execution_time is None:
            execution_time = time.time() - start_time
        
        # Map failure reason
        failure_reason = FailureMapper.map(e)
        log_step(task_id, 'unhandled_exception', {
            'error': error_msg,
            'failure_reason': failure_reason.value,
        })

        logger.error(f"Error processing task {task_id}: {error_msg}", exc_info=True)
        try:
            # Unlock task first
            api_client.unlock_task(task_id)

            # Then mark as failed (this will handle retry logic)
            api_client.update_task_status(
                task_id,
                'failed',
                error_message=full_error
            )
        except Exception as unlock_error:
            logger.error(f"Failed to unlock/update task {task_id} after error: {unlock_error}")
            # Try to unlock at least
            try:
                api_client.unlock_task(task_id)
            except:
                pass
        
        # Finalize telemetry run (exception)
        try:
            finalize_run(task_id, {
                'success': False,
                'failure_reason': failure_reason.value,
                'error': error_msg,
                'execution_time': execution_time,
                'retry_count': retry_count,
                'exception': True,
            })
        except Exception as telem_error:
            logger.warning(f"Failed to finalize telemetry: {telem_error}")
        
        # Log structured outcome (exception)
        try:
            automation_logger.log_outcome(
                task_id=task_id,
                action_attempted=task_type,
                result='error',
                error_message=error_msg,
                execution_time=execution_time,
                retry_count=retry_count,
                url=result_url
            )
        except Exception as log_error:
            logger.warning(f"Failed to log automation outcome: {log_error}")
        
        # Log shadow mode result
        try:
            shadow_logger.log_result(
                task_id=task_id,
                rule_based_action=task_type,
                task_result='error',
                execution_time=execution_time,
                retry_count=retry_count,
                ai_prediction=ai_prediction
            )
        except Exception as log_error:
            logger.warning(f"Failed to log shadow mode result: {log_error}")


def parse_args() -> argparse.Namespace:
    """Parse CLI flags for worker."""
    parser = argparse.ArgumentParser(description="Automation worker")
    parser.add_argument("--once", action="store_true", help="Process pending tasks once and exit")
    parser.add_argument("--limit", type=int, default=5, help="Max tasks to pull per poll")
    parser.add_argument(
        "--poll-interval",
        type=int,
        default=DEFAULT_POLL_INTERVAL,
        help="Seconds to wait between polls when running continuously",
    )
    return parser.parse_args()


def run_worker(run_once: bool, limit: int, poll_interval: int):
    """
    Worker loop that polls for tasks and processes them.
    Can be run in continuous mode or a single pass (run_once).
    """
    # Re-read environment variables to ensure we have the latest values
    # (in case they were set by Process after module import)
    api_url = os.getenv('LARAVEL_API_URL', LARAVEL_API_URL)
    # Try LARAVEL_API_TOKEN first, then APP_API_TOKEN as fallback
    api_token = os.getenv('LARAVEL_API_TOKEN') or os.getenv('APP_API_TOKEN') or LARAVEL_API_TOKEN

    # Ensure we're not using the old app:8000 URL
    if 'app:8000' in api_url or 'localhost:8000' in api_url:
        api_url = 'http://nginx'
        logger.warning(f"Detected invalid API URL, using default: {api_url}")

    logger.info("Starting Auto Backlink Pro Python Worker")
    logger.info(f"Laravel API URL: {api_url}")
    logger.info(f"Worker ID: {WORKER_ID}")
    logger.info(f"Mode: {'single-pass' if run_once else 'continuous'} | Limit: {limit}")
    logger.info(f"Poll interval: {poll_interval}s (Rate limit: 300 requests/hour, ~{int(3600/poll_interval)} requests/hour at this interval)")

    if not api_token:
        logger.error("LARAVEL_API_TOKEN not set! Worker cannot authenticate.")
        logger.error("Please set APP_API_TOKEN or LARAVEL_API_TOKEN environment variable.")
        return

    # Log token info (first 10 chars for security)
    token_preview = api_token[:10] + "..." if len(api_token) > 10 else api_token
    logger.info(f"API Token: {token_preview} (length: {len(api_token)})")

    # Use the corrected API URL and token
    api_client = LaravelAPIClient(api_url, api_token)
    os.makedirs('screenshots', exist_ok=True)

    while True:
        try:
            # Prioritize comment tasks first (they're easier and more likely to succeed)
            # Try to get comment tasks first
            comment_tasks = api_client.get_pending_tasks(limit=limit, task_type='comment')
            
            if comment_tasks:
                logger.info(f"Found {len(comment_tasks)} pending COMMENT tasks (prioritizing)")
                for task in comment_tasks:
                    process_task(api_client, task)
            else:
                # If no comment tasks, get any pending tasks
                tasks = api_client.get_pending_tasks(limit=limit)
                if tasks:
                    logger.info(f"Found {len(tasks)} pending tasks (no comments available)")
                    for task in tasks:
                        process_task(api_client, task)
                else:
                    logger.debug("No pending tasks found")

            if run_once:
                break

            time.sleep(poll_interval)

        except KeyboardInterrupt:
            logger.info("Worker stopped by user")
            break
        except requests.exceptions.HTTPError as e:
            # Handle rate limiting specifically
            if e.response and e.response.status_code == 429:
                error_msg = str(e)
                retry_after = 60  # Default wait time

                # Try to extract retry_after from error message
                if "Retry after" in error_msg:
                    try:
                        import re
                        match = re.search(r'Retry after (\d+) seconds', error_msg)
                        if match:
                            retry_after = int(match.group(1))
                    except:
                        pass

                # Also try to get retry_after from response JSON
                try:
                    if e.response:
                        error_data = e.response.json()
                        retry_after = error_data.get('retry_after', retry_after)
                except:
                    pass

                logger.warning(
                    f"Rate limit exceeded. Waiting {retry_after} seconds before next poll. "
                    f"Consider increasing poll_interval (current: {poll_interval}s)"
                )

                if run_once:
                    logger.error("Rate limit exceeded in single-pass mode. Cannot retry.")
                    break

                # Wait for the retry_after period, then continue
                # Add a small buffer to ensure rate limit window has reset
                wait_time = retry_after + 10  # Add 10 seconds buffer
                logger.info(f"Waiting {wait_time} seconds for rate limit to reset...")
                time.sleep(wait_time)
                continue  # Continue loop without sleeping again
            else:
                # Other HTTP errors
                logger.error(f"HTTP error in worker loop: {e}", exc_info=True)
                if run_once:
                    break
                time.sleep(poll_interval)
        except Exception as e:
            logger.error(f"Error in worker loop: {e}", exc_info=True)
            if run_once:
                break
            time.sleep(poll_interval)


if __name__ == "__main__":
    args = parse_args()
    run_worker(run_once=args.once, limit=args.limit, poll_interval=args.poll_interval)

