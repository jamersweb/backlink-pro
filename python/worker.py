"""
Auto Backlink Pro - Python Automation Worker
Main entry point for the Python worker that processes automation tasks
"""

import os
import time
import logging
import sys
from dotenv import load_dotenv
from api_client import LaravelAPIClient
from automation.comment import CommentAutomation
from automation.profile import ProfileAutomation
from automation.forum import ForumAutomation
from automation.guest import GuestPostAutomation

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

# Configuration
LARAVEL_API_URL = os.getenv('LARAVEL_API_URL', 'http://app:8000')
LARAVEL_API_TOKEN = os.getenv('LARAVEL_API_TOKEN', '')
POLL_INTERVAL = int(os.getenv('POLL_INTERVAL', '10'))  # seconds
WORKER_ID = os.getenv('WORKER_ID', f'worker-{os.getpid()}')


def get_automation_class(task_type: str):
    """Get automation class for task type"""
    automation_classes = {
        'comment': CommentAutomation,
        'profile': ProfileAutomation,
        'forum': ForumAutomation,
        'guest': GuestPostAutomation,
        'guestposting': GuestPostAutomation,  # Alias
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
        
        # Get proxy if needed
        proxies = api_client.get_proxies()
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


def main():
    """
    Main worker loop that polls for tasks and processes them
    """
    logger.info("Starting Auto Backlink Pro Python Worker")
    logger.info(f"Laravel API URL: {LARAVEL_API_URL}")
    logger.info(f"Worker ID: {WORKER_ID}")
    
    if not LARAVEL_API_TOKEN:
        logger.error("LARAVEL_API_TOKEN not set! Worker cannot authenticate.")
        return
    
    # Initialize API client
    api_client = LaravelAPIClient(LARAVEL_API_URL, LARAVEL_API_TOKEN)
    
    # Create screenshots directory
    os.makedirs('screenshots', exist_ok=True)
    
    while True:
        try:
            # Get pending tasks
            tasks = api_client.get_pending_tasks(limit=5)
            
            if tasks:
                logger.info(f"Found {len(tasks)} pending tasks")
                for task in tasks:
                    process_task(api_client, task)
            else:
                logger.debug("No pending tasks found")
            
            time.sleep(POLL_INTERVAL)
            
        except KeyboardInterrupt:
            logger.info("Worker stopped by user")
            break
        except Exception as e:
            logger.error(f"Error in worker loop: {e}", exc_info=True)
            time.sleep(POLL_INTERVAL)


if __name__ == "__main__":
    main()

