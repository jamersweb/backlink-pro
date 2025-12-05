"""
Auto Backlink Pro - Python Automation Worker
Main entry point for the Python worker that processes automation tasks
"""

import os
import time
import logging
import sys
import argparse
from dotenv import load_dotenv
from api_client import LaravelAPIClient
from automation.comment import CommentAutomation
from automation.profile import ProfileAutomation
from automation.forum import ForumAutomation
from automation.guest import GuestPostAutomation
from automation.email_confirmation import EmailConfirmationAutomation

# Load environment variables
load_dotenv()

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
LARAVEL_API_URL = os.getenv('LARAVEL_API_URL', 'http://app:8000')
LARAVEL_API_TOKEN = os.getenv('LARAVEL_API_TOKEN', '')
DEFAULT_POLL_INTERVAL = int(os.getenv('POLL_INTERVAL', '10'))  # seconds
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
        campaign = api_client.get_campaign(task['campaign_id'])
        campaign_country = campaign.get('company_country') or campaign.get('country_name')

        proxies = api_client.get_proxies(country=campaign_country, prefer_country=True)
        proxy = proxies[0] if proxies else None

        # Execute automation
        with automation_class(api_client, proxy=proxy, headless=True) as automation:
            result = automation.execute(task)

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
            api_client.update_task_status(
                task_id,
                'failed',
                error_message=result.get('error', 'Unknown error')
            )

            logger.error(f"Task {task_id} failed: {result.get('error')}")

    except Exception as e:
        logger.error(f"Error processing task {task_id}: {e}", exc_info=True)
        try:
            api_client.update_task_status(
                task_id,
                'failed',
                error_message=str(e)
            )
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
    logger.info("Starting Auto Backlink Pro Python Worker")
    logger.info(f"Laravel API URL: {LARAVEL_API_URL}")
    logger.info(f"Worker ID: {WORKER_ID}")
    logger.info(f"Mode: {'single-pass' if run_once else 'continuous'} | Limit: {limit}")

    if not LARAVEL_API_TOKEN:
        logger.error("LARAVEL_API_TOKEN not set! Worker cannot authenticate.")
        return

    api_client = LaravelAPIClient(LARAVEL_API_URL, LARAVEL_API_TOKEN)
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
        except Exception as e:
            logger.error(f"Error in worker loop: {e}", exc_info=True)
            if run_once:
                break
            time.sleep(poll_interval)


if __name__ == "__main__":
    args = parse_args()
    run_worker(run_once=args.once, limit=args.limit, poll_interval=args.poll_interval)

