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

# Load environment variables from .env file (but don't override existing env vars)
# This allows Process environment variables to take precedence
load_dotenv(override=False)

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.StreamHandler(sys.stdout),
        logging.FileHandler('worker.log'),
    ]
)
logger = logging.getLogger(__name__)

# Configuration (defaults can be overridden via CLI flags)
# Read environment variables after load_dotenv() to ensure Process env vars take precedence
LARAVEL_API_URL = os.getenv('LARAVEL_API_URL', 'http://nginx')
LARAVEL_API_TOKEN = os.getenv('LARAVEL_API_TOKEN', '')
# Default poll interval: 60 seconds (to avoid hitting rate limits)
# Rate limit is 300 requests/hour = ~5 requests/minute
# With 10s interval, we make ~360 requests/hour (slightly over, but with retry logic it's fine)
# With 12s interval, we make ~300 requests/hour (exactly at limit)
DEFAULT_POLL_INTERVAL = int(os.getenv('POLL_INTERVAL', '12'))  # seconds
WORKER_ID = os.getenv('WORKER_ID', f'worker-{os.getpid()}')


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

    logger.info(f"Processing task {task_id} of type {task_type}")

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
        # Lock task
        lock_result = api_client.lock_task(task_id, WORKER_ID)
        if 'error' in lock_result:
            logger.warning(f"Failed to lock task {task_id}: {lock_result.get('error')}")
            return

        # Update task status to running
        api_client.update_task_status(task_id, 'running')

        # Get automation class
        automation_class = get_automation_class(task_type)
        if not automation_class:
            raise ValueError(f"Unknown task type: {task_type}")

        # Get proxy if needed - prefer country match from campaign
        try:
            campaign = api_client.get_campaign(task['campaign_id'])
            if not campaign:
                raise ValueError(f"Campaign {task['campaign_id']} not found")

            campaign_country = campaign.get('company_country') or campaign.get('country_name')
            proxies = api_client.get_proxies(country=campaign_country, prefer_country=True)
            proxy = proxies[0] if proxies else None

            if proxy:
                logger.debug(f"Using proxy: {proxy.get('host')}:{proxy.get('port')}")
            else:
                logger.debug("No proxy available, running without proxy")
        except Exception as e:
            logger.warning(f"Error getting campaign/proxy info: {e}, continuing without proxy")
            proxy = None

        # Execute automation with proper error handling
        try:
            with automation_class(api_client, proxy=proxy, headless=True) as automation:
                result = automation.execute(task)
        except Exception as automation_error:
            # If automation setup or execution fails, handle it properly
            error_msg = f"Automation execution failed: {str(automation_error)}"
            logger.error(error_msg, exc_info=True)

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

            # Re-raise to be caught by outer exception handler
            raise

        if result.get('success'):
            # Create backlink
            backlink = api_client.create_backlink(
                campaign_id=task['campaign_id'],
                url=result['url'],
                task_type=task_type,
                status='submitted',
                site_account_id=result.get('site_account_id'),
                backlink_opportunity_id=result.get('backlink_opportunity_id'),
            )

            # Mark task as success
            api_client.update_task_status(
                task_id,
                'success',
                result={
                    'backlink_id': backlink.get('id'),
                    'url': result['url'],
                }
            )

            logger.info(f"Task {task_id} completed successfully")
        else:
            # Mark task as failed
            error_msg = result.get('error', 'Unknown error')
            logger.error(f"Task {task_id} failed: {error_msg}")

            try:
                # Unlock task first
                api_client.unlock_task(task_id)

                # Then mark as failed (this will handle retry logic)
                api_client.update_task_status(
                    task_id,
                    'failed',
                    error_message=error_msg
                )
            except Exception as update_error:
                logger.error(f"Failed to update task {task_id} status after failure: {update_error}")
                # Try to unlock at least
                try:
                    api_client.unlock_task(task_id)
                except:
                    pass

    except Exception as e:
        error_msg = str(e)
        # Include traceback for better debugging (truncate if too long)
        import traceback
        tb = traceback.format_exc()
        full_error = f"{error_msg}\n\nTraceback:\n{tb}"

        # Truncate if too long (API will truncate to 1000 chars, but keep it reasonable)
        if len(full_error) > 2000:
            full_error = full_error[:1997] + "..."

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
    api_token = os.getenv('LARAVEL_API_TOKEN', LARAVEL_API_TOKEN)

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
            tasks = api_client.get_pending_tasks(limit=limit)

            if tasks:
                logger.info(f"Found {len(tasks)} pending tasks")
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

                logger.warning(
                    f"Rate limit exceeded. Waiting {retry_after} seconds before next poll. "
                    f"Consider increasing poll_interval (current: {poll_interval}s)"
                )

                if run_once:
                    logger.error("Rate limit exceeded in single-pass mode. Cannot retry.")
                    break

                # Wait for the retry_after period, then continue
                time.sleep(retry_after)
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

