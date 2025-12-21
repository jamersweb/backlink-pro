"""
Feedback Collector for Continuous Learning

Collects new automation results and appends to training dataset
"""

import os
import sys
import logging
from pathlib import Path

# Add user site-packages to path (for packages installed with --user)
# This is critical for finding pandas and other ML packages
user_site_added = False

# Method 1: Try using site module
try:
    import site
    user_site = site.getusersitepackages()
    if user_site and os.path.isdir(user_site) and user_site not in sys.path:
        sys.path.insert(0, user_site)
        user_site_added = True
except Exception:
    pass

# Method 2: Try common Windows user site-packages locations
if not user_site_added:
    # Get home directory from multiple possible sources
    home = (os.environ.get('USERPROFILE') or 
            os.environ.get('HOME') or 
            os.path.expanduser('~'))
    
    if home:
        # Try Python version-specific paths first
        python_version = f"{sys.version_info.major}{sys.version_info.minor}"
        common_paths = [
            os.path.join(home, 'AppData', 'Roaming', 'Python', f'Python{python_version}', 'site-packages'),
            os.path.join(home, 'AppData', 'Roaming', 'Python', 'Python312', 'site-packages'),
            os.path.join(home, 'AppData', 'Roaming', 'Python', 'Python311', 'site-packages'),
            os.path.join(home, 'AppData', 'Roaming', 'Python', 'Python310', 'site-packages'),
            os.path.join(home, 'AppData', 'Roaming', 'Python', 'Python39', 'site-packages'),
        ]
        for path in common_paths:
            if os.path.isdir(path) and path not in sys.path:
                sys.path.insert(0, path)
                user_site_added = True
                break

# Method 3: Try to find pandas in any existing sys.path location
if not user_site_added:
    for path in sys.path:
        pandas_path = os.path.join(path, 'pandas')
        if os.path.isdir(pandas_path):
            if path not in sys.path:
                sys.path.insert(0, path)
            break

# Method 4: Windows-specific hardcoded fallback (if on Windows)
if not user_site_added and sys.platform == 'win32':
    # Try common Windows paths directly
    win_paths = [
        r'C:\Users\Hp\AppData\Roaming\Python\Python312\site-packages',
        r'C:\Users\Hp\AppData\Roaming\Python\Python311\site-packages',
        r'C:\Users\Hp\AppData\Roaming\Python\Python310\site-packages',
    ]
    for path in win_paths:
        if os.path.isdir(path) and path not in sys.path:
            sys.path.insert(0, path)
            break

# Add parent directory to path
sys.path.insert(0, str(Path(__file__).parent.parent))

# Now import pandas and other packages
import pandas as pd
import json
import traceback
from datetime import datetime, timedelta
from typing import Dict, List, Optional
from urllib.parse import urlparse

logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)


def format_error_with_location(e: Exception, context: str = "") -> str:
    """Format error with file name, line number, and context for easy debugging"""
    tb = traceback.extract_tb(e.__traceback__)
    if tb:
        # Get the last frame where the error occurred
        frame = tb[-1]
        filename = frame.filename
        lineno = frame.lineno
        funcname = frame.name
        line_text = frame.line or ""
        
        error_msg = f"\n{'='*70}\n"
        error_msg += f"ERROR in {context}\n" if context else "ERROR\n"
        error_msg += f"{'='*70}\n"
        error_msg += f"File: {filename}\n"
        error_msg += f"Line: {lineno}\n"
        error_msg += f"Function: {funcname}\n"
        error_msg += f"Code: {line_text.strip()}\n"
        error_msg += f"Error Type: {type(e).__name__}\n"
        error_msg += f"Error Message: {str(e)}\n"
        error_msg += f"{'='*70}\n"
        return error_msg
    else:
        return f"ERROR: {type(e).__name__}: {str(e)}"


def to_int(v, default=0):
    """Safe integer conversion helper - prevents type errors in arithmetic operations"""
    try:
        return int(v)
    except (TypeError, ValueError):
        return default


class FeedbackCollector:
    """
    Collects new automation results and appends to training dataset
    """
    
    def __init__(self, 
                 log_dir: str = "logs",
                 dataset_dir: str = "ml/datasets",
                 automation_log_file: str = "automation_logs.jsonl",
                 shadow_log_file: str = "shadow_mode_logs.jsonl"):
        """
        Initialize feedback collector
        
        Args:
            log_dir: Directory containing log files
            dataset_dir: Directory for training datasets
            automation_log_file: Automation log file name
            shadow_log_file: Shadow mode log file name
        """
        self.log_dir = Path(log_dir)
        self.dataset_dir = Path(dataset_dir)
        self.dataset_dir.mkdir(parents=True, exist_ok=True)
        
        self.automation_log_file = self.log_dir / automation_log_file
        self.shadow_log_file = self.log_dir / shadow_log_file
        
        # Track processed task IDs to avoid duplicates
        self.processed_tasks_file = self.dataset_dir / 'processed_tasks.json'
        self.processed_task_ids = self._load_processed_tasks()
    
    def _load_processed_tasks(self) -> set:
        """Load set of already processed task IDs"""
        if self.processed_tasks_file.exists():
            try:
                with open(self.processed_tasks_file, 'r') as f:
                    data = json.load(f)
                    task_ids = data.get('task_ids', [])
                    # Convert all task IDs to strings for consistency
                    return set(str(tid) for tid in task_ids if tid is not None)
            except Exception as e:
                logger.warning(f"Error loading processed tasks: {e}")
        return set()
    
    def _save_processed_tasks(self):
        """Save processed task IDs"""
        try:
            # Ensure all task IDs are strings before saving
            task_ids = [str(tid) for tid in self.processed_task_ids if tid is not None]
            with open(self.processed_tasks_file, 'w') as f:
                json.dump({
                    'task_ids': task_ids,
                    'last_updated': datetime.utcnow().isoformat() + 'Z',
                }, f)
        except Exception as e:
            logger.error(f"Error saving processed tasks: {e}")
    
    def collect_from_automation_logs(self, since_days: int = 7) -> List[Dict]:
        """
        Collect new results from automation logs
        
        Args:
            since_days: Collect logs from last N days
        
        Returns:
            List of training records
        """
        # Ensure since_days is an integer using safe helper
        since_days = to_int(since_days, 7)
        
        logger.info(f"Collecting from automation logs (last {since_days} days)")
        
        if not self.automation_log_file.exists():
            logger.warning(f"Automation log file not found: {self.automation_log_file}")
            return []
        
        cutoff_date = datetime.utcnow() - timedelta(days=since_days)
        new_records = []
        
        try:
            with open(self.automation_log_file, 'r', encoding='utf-8') as f:
                line_num = 0
                for line in f:
                    line_num += 1
                    if not line.strip():
                        continue
                    
                    try:
                        entry = json.loads(line)
                        task_id = entry.get('task_id')
                        
                        # Convert task_id to string for consistency
                        if task_id is not None:
                            task_id = str(task_id)
                        
                        # Skip if already processed
                        if task_id and task_id in self.processed_task_ids:
                            continue
                        
                        # Check date - use safe conversion
                        timestamp_str = entry.get('timestamp', '')
                        if timestamp_str:
                            try:
                                # Try ISO format first
                                timestamp = datetime.fromisoformat(timestamp_str.replace('Z', '+00:00'))
                                if timestamp < cutoff_date:
                                    continue
                            except Exception as date_err:
                                # Try Unix timestamp if ISO format fails
                                try:
                                    ts_int = to_int(timestamp_str, 0)
                                    if ts_int > 0:
                                        timestamp = datetime.fromtimestamp(ts_int)
                                        if timestamp < cutoff_date:
                                            continue
                                except Exception as ts_err:
                                    logger.debug(f"Line {line_num}: Could not parse timestamp '{timestamp_str}': {ts_err}")
                                    pass
                        
                        # Convert to training record
                        record = self._convert_to_training_record(entry)
                        if record:
                            new_records.append(record)
                            if task_id:
                                self.processed_task_ids.add(task_id)
                    
                    except json.JSONDecodeError as e:
                        logger.warning(f"Line {line_num}: JSON decode error: {e}")
                        continue
                    except Exception as e:
                        error_detail = format_error_with_location(e, f"processing automation log line {line_num}")
                        logger.error(error_detail)
                        continue
        except Exception as e:
            error_detail = format_error_with_location(e, "reading automation log file")
            logger.error(error_detail)
            raise
        
        logger.info(f"Collected {len(new_records)} new records from automation logs")
        return new_records
    
    def collect_from_shadow_logs(self, since_days: int = 7) -> List[Dict]:
        """
        Collect new results from shadow mode logs
        
        Args:
            since_days: Collect logs from last N days
        
        Returns:
            List of training records
        """
        # Ensure since_days is an integer using safe helper
        since_days = to_int(since_days, 7)
        
        logger.info(f"Collecting from shadow mode logs (last {since_days} days)")
        
        if not self.shadow_log_file.exists():
            logger.warning(f"Shadow log file not found: {self.shadow_log_file}")
            return []
        
        cutoff_date = datetime.utcnow() - timedelta(days=since_days)
        new_records = []
        
        try:
            with open(self.shadow_log_file, 'r', encoding='utf-8') as f:
                line_num = 0
                for line in f:
                    line_num += 1
                    if not line.strip():
                        continue
                    
                    try:
                        entry = json.loads(line)
                        task_id = entry.get('task_id')
                        
                        # Convert task_id to string for consistency
                        if task_id is not None:
                            task_id = str(task_id)
                        
                        # Skip if already processed
                        if task_id and task_id in self.processed_task_ids:
                            continue
                        
                        # Only process completed tasks
                        if not entry.get('task_result'):
                            continue
                        
                        # Check date - use safe conversion
                        timestamp_str = entry.get('timestamp', '')
                        if timestamp_str:
                            try:
                                # Try ISO format first
                                timestamp = datetime.fromisoformat(timestamp_str.replace('Z', '+00:00'))
                                if timestamp < cutoff_date:
                                    continue
                            except Exception as date_err:
                                # Try Unix timestamp if ISO format fails
                                try:
                                    ts_int = to_int(timestamp_str, 0)
                                    if ts_int > 0:
                                        timestamp = datetime.fromtimestamp(ts_int)
                                        if timestamp < cutoff_date:
                                            continue
                                except Exception as ts_err:
                                    logger.debug(f"Line {line_num}: Could not parse timestamp '{timestamp_str}': {ts_err}")
                                    pass
                        
                        # Convert to training record
                        record = self._convert_shadow_to_training_record(entry)
                        if record:
                            new_records.append(record)
                            if task_id:
                                self.processed_task_ids.add(task_id)
                    
                    except json.JSONDecodeError as e:
                        logger.warning(f"Line {line_num}: JSON decode error: {e}")
                        continue
                    except Exception as e:
                        error_detail = format_error_with_location(e, f"processing shadow log line {line_num}")
                        logger.error(error_detail)
                        continue
        except Exception as e:
            error_detail = format_error_with_location(e, "reading shadow log file")
            logger.error(error_detail)
            raise
        
        logger.info(f"Collected {len(new_records)} new records from shadow logs")
        return new_records
    
    def collect_from_api(self, api_client, since_days: int = 7) -> List[Dict]:
        """
        Collect new results from Laravel API
        
        Args:
            api_client: LaravelAPIClient instance
            since_days: Collect results from last N days
        
        Returns:
            List of training records
        """
        # Ensure since_days is an integer using safe helper
        since_days = to_int(since_days, 7)
        
        logger.info(f"Collecting from API (last {since_days} days)")
        
        # Get historical data from API
        cutoff_date = (datetime.utcnow() - timedelta(days=since_days)).isoformat() + 'Z'
        historical_data = api_client.get_historical_backlink_data(limit=10000, min_date=cutoff_date)
        
        new_records = []
        record_num = 0
        for record in historical_data:
            record_num += 1
            try:
                task_id = record.get('task', {}).get('id')
                
                # Convert task_id to string for consistency
                if task_id is not None:
                    task_id = str(task_id)
                
                # Skip if already processed
                if task_id and task_id in self.processed_task_ids:
                    continue
                
                # Convert to training record format
                training_record = self._convert_api_record_to_training(record)
                if training_record:
                    new_records.append(training_record)
                    if task_id:
                        self.processed_task_ids.add(task_id)
            except Exception as e:
                error_detail = format_error_with_location(e, f"processing API record {record_num}")
                logger.error(error_detail)
                logger.error(f"Problematic record data: {json.dumps(record, default=str)[:500]}")
                continue
        
        logger.info(f"Collected {len(new_records)} new records from API")
        return new_records
    
    def _convert_to_training_record(self, log_entry: Dict) -> Optional[Dict]:
        """
        Convert automation log entry to training record format
        
        Args:
            log_entry: Automation log entry
        
        Returns:
            Training record dict or None
        """
        action_attempted = log_entry.get('action_attempted')
        result = log_entry.get('result')
        
        if not action_attempted or not result:
            return None
        
        # Determine target (action type that was attempted)
        # For training, we want to predict which action type to use
        # So target is the action_attempted
        target = action_attempted
        
        # Extract features
        domain = log_entry.get('domain', 'unknown')
        
        # Try to get PA/DA from opportunity if available
        # For now, we'll need to enrich from backlink store
        pa = None
        da = None
        site_type = None
        
        return {
            'pa': pa,
            'da': da,
            'site_type': site_type,
            'domain': domain,
            'action_type': target,  # Target variable
            'success': 1 if result == 'success' else 0,
            'execution_time': log_entry.get('execution_time'),
            'retry_count': log_entry.get('retry_count', 0),
            'failure_reason': log_entry.get('failure_reason'),
            'captcha_type': log_entry.get('captcha_type'),
            'timestamp': log_entry.get('timestamp'),
            'task_id': log_entry.get('task_id'),
        }
    
    def _convert_shadow_to_training_record(self, shadow_entry: Dict) -> Optional[Dict]:
        """
        Convert shadow mode log entry to training record
        
        Args:
            shadow_entry: Shadow mode log entry
        
        Returns:
            Training record dict or None
        """
        # Use AI prediction as target (what AI thought was best)
        ai_predicted = shadow_entry.get('ai_predicted_action')
        rule_based = shadow_entry.get('rule_based_action')
        task_result = shadow_entry.get('task_result')
        
        if not ai_predicted or not task_result:
            return None
        
        # Parse AI probabilities
        ai_probs_str = shadow_entry.get('ai_probabilities', '{}')
        try:
            ai_probs = json.loads(ai_probs_str) if isinstance(ai_probs_str, str) else ai_probs_str
        except:
            ai_probs = {}
        
        return {
            'pa': shadow_entry.get('pa', 0),
            'da': shadow_entry.get('da', 0),
            'site_type': shadow_entry.get('site_type', 'unknown'),
            'domain': shadow_entry.get('domain', 'unknown'),
            'action_type': ai_predicted,  # AI prediction as target
            'success': 1 if task_result == 'success' else 0,
            'execution_time': shadow_entry.get('execution_time'),
            'retry_count': shadow_entry.get('retry_count', 0),
            'ai_confidence': shadow_entry.get('ai_confidence', 0.5),
            'rule_based_action': rule_based,
            'ai_correct': shadow_entry.get('ai_correct', False),
            'timestamp': shadow_entry.get('timestamp'),
            'task_id': shadow_entry.get('task_id'),
        }
    
    def _convert_api_record_to_training(self, api_record: Dict) -> Optional[Dict]:
        """
        Convert API historical record to training record
        
        Args:
            api_record: API historical data record
        
        Returns:
            Training record dict or None
        """
        task = api_record.get('task', {})
        backlink = api_record.get('backlink', {})
        
        action_type = task.get('type')
        success = 1 if api_record.get('success', False) else 0
        
        if not action_type:
            return None
        
        # Convert PA/DA to integers using safe helper
        pa = to_int(backlink.get('pa', 0), 0)
        da = to_int(backlink.get('da', 0), 0)
        
        # Convert task_id to string for consistency
        task_id = task.get('id')
        if task_id is not None:
            task_id = str(task_id)
        
        return {
            'pa': pa,
            'da': da,
            'site_type': backlink.get('site_type', 'unknown'),
            'domain': self._extract_domain(backlink.get('url', '')),
            'action_type': action_type,
            'success': success,
            'timestamp': api_record.get('created_at'),
            'task_id': task_id,
        }
    
    def _extract_domain(self, url: Optional[str]) -> str:
        """Extract domain from URL"""
        if not url:
            return 'unknown'
        try:
            parsed = urlparse(str(url))
            domain = parsed.netloc or parsed.path.split('/')[0]
            if ':' in domain:
                domain = domain.split(':')[0]
            return domain or 'unknown'
        except:
            return 'unknown'
    
    def append_to_dataset(self, new_records: List[Dict], output_file: str = "training_backlinks_enriched.csv"):
        """
        Append new records to training dataset
        
        Args:
            new_records: List of new training records
            output_file: Output CSV file name
        """
        if not new_records:
            logger.info("No new records to append")
            return
        
        output_path = self.dataset_dir / output_file
        
        # Load existing dataset if it exists
        existing_df = None
        if output_path.exists():
            try:
                existing_df = pd.read_csv(output_path)
                logger.info(f"Loaded existing dataset: {len(existing_df)} records")
            except Exception as e:
                logger.warning(f"Error loading existing dataset: {e}")
        
        # Create DataFrame from new records
        new_df = pd.DataFrame(new_records)
        
        # Ensure numeric columns are properly typed
        numeric_cols = ['pa', 'da', 'success']
        for col in numeric_cols:
            if col in new_df.columns:
                try:
                    new_df[col] = pd.to_numeric(new_df[col], errors='coerce').fillna(0).astype(int)
                except Exception as e:
                    logger.warning(f"Error converting {col} to numeric: {e}")
        
        # Merge with existing
        if existing_df is not None:
            # Ensure existing dataframe also has proper types
            for col in numeric_cols:
                if col in existing_df.columns:
                    try:
                        existing_df[col] = pd.to_numeric(existing_df[col], errors='coerce').fillna(0).astype(int)
                    except Exception as e:
                        logger.warning(f"Error converting existing {col} to numeric: {e}")
            
            # Combine dataframes
            combined_df = pd.concat([existing_df, new_df], ignore_index=True)
            
            # Remove duplicates based on task_id
            if 'task_id' in combined_df.columns:
                # Ensure task_id is string for consistent comparison
                combined_df['task_id'] = combined_df['task_id'].astype(str)
                combined_df = combined_df.drop_duplicates(subset=['task_id'], keep='last')
                logger.info(f"Removed duplicates, final count: {len(combined_df)}")
        else:
            combined_df = new_df
        
        # Save updated dataset
        combined_df.to_csv(output_path, index=False)
        logger.info(f"Saved {len(combined_df)} records to {output_path}")
        
        # Save processed task IDs
        self._save_processed_tasks()
        
        return output_path
    
    def collect_and_append(self, api_client=None, since_days: int = 7) -> Path:
        """
        Collect new results and append to dataset
        
        Args:
            api_client: Optional LaravelAPIClient for API collection
            since_days: Days to look back
        
        Returns:
            Path to updated dataset file
        """
        # Ensure since_days is an integer using safe helper
        since_days = to_int(since_days, 7)
        
        logger.info(f"Collecting feedback (last {since_days} days)")
        
        all_records = []
        
        # Collect from automation logs
        try:
            logger.info("Collecting from automation logs...")
            automation_records = self.collect_from_automation_logs(since_days)
            all_records.extend(automation_records)
            logger.info(f"Collected {len(automation_records)} records from automation logs")
        except Exception as e:
            error_detail = format_error_with_location(e, "collect_from_automation_logs")
            logger.error(error_detail)
            logger.error(f"Full traceback:\n{traceback.format_exc()}")
            raise
        
        # Collect from shadow logs
        try:
            logger.info("Collecting from shadow logs...")
            shadow_records = self.collect_from_shadow_logs(since_days)
            all_records.extend(shadow_records)
            logger.info(f"Collected {len(shadow_records)} records from shadow logs")
        except Exception as e:
            error_detail = format_error_with_location(e, "collect_from_shadow_logs")
            logger.error(error_detail)
            logger.error(f"Full traceback:\n{traceback.format_exc()}")
            raise
        
        # Collect from API if available
        if api_client:
            try:
                logger.info("Collecting from API...")
                api_records = self.collect_from_api(api_client, since_days)
                # Validate and clean API records
                cleaned_records = []
                for i, record in enumerate(api_records):
                    try:
                        # Create a clean copy of the record
                        clean_record = {}
                        for key, value in record.items():
                            # Handle nested dictionaries
                            if isinstance(value, dict):
                                # Flatten nested dicts (e.g., task, backlink)
                                for nested_key, nested_value in value.items():
                                    clean_key = f"{key}_{nested_key}" if key in ['task', 'backlink', 'campaign'] else nested_key
                                    clean_record[clean_key] = nested_value
                            else:
                                clean_record[key] = value
                        
                        # Ensure all numeric fields are properly typed using safe helper
                        if 'pa' in clean_record:
                            clean_record['pa'] = to_int(clean_record['pa'], 0)
                        if 'backlink_pa' in clean_record:
                            clean_record['pa'] = to_int(clean_record['backlink_pa'], 0)
                        
                        if 'da' in clean_record:
                            clean_record['da'] = to_int(clean_record['da'], 0)
                        if 'backlink_da' in clean_record:
                            clean_record['da'] = to_int(clean_record['backlink_da'], 0)
                        
                        if 'success' in clean_record:
                            if isinstance(clean_record['success'], bool):
                                clean_record['success'] = 1 if clean_record['success'] else 0
                            else:
                                clean_record['success'] = to_int(clean_record['success'], 0)
                        
                        # Get action_type from task_type or type
                        if 'action_type' not in clean_record:
                            if 'task_type' in clean_record:
                                clean_record['action_type'] = clean_record['task_type']
                            elif 'type' in clean_record:
                                clean_record['action_type'] = clean_record['type']
                            elif 'task_type' in clean_record:
                                clean_record['action_type'] = clean_record['task_type']
                        
                        cleaned_records.append(clean_record)
                    except Exception as e:
                        logger.warning(f"Error cleaning API record {i}: {e}, skipping record")
                        continue
                all_records.extend(cleaned_records)
                logger.info(f"Collected {len(cleaned_records)} records from API")
            except Exception as e:
                error_detail = format_error_with_location(e, "collect_from_api")
                logger.error(error_detail)
                logger.error(f"Full traceback:\n{traceback.format_exc()}")
                # Don't raise - continue with data from other sources
                logger.warning("Continuing without API data")
        
        # Remove duplicates by task_id
        seen_task_ids = set()
        unique_records = []
        record_idx = 0
        for record in all_records:
            record_idx += 1
            try:
                task_id = record.get('task_id')
                if task_id is not None:
                    task_id = str(task_id)
                if task_id and task_id not in seen_task_ids:
                    unique_records.append(record)
                    seen_task_ids.add(task_id)
            except Exception as e:
                error_detail = format_error_with_location(e, f"processing record {record_idx} for deduplication")
                logger.warning(error_detail)
                logger.warning(f"Problematic record: {json.dumps(record, default=str)[:200]}")
                continue
        
        logger.info(f"Total unique new records: {len(unique_records)}")
        
        # Append to dataset
        if unique_records:
            try:
                output_path = self.append_to_dataset(unique_records)
                return output_path
            except Exception as e:
                error_detail = format_error_with_location(e, "append_to_dataset")
                logger.error(error_detail)
                logger.error(f"Full traceback:\n{traceback.format_exc()}")
                raise
        else:
            logger.info("No new records to append")
            return self.dataset_dir / "training_backlinks_enriched.csv"


def main():
    """Main function"""
    import argparse
    
    parser = argparse.ArgumentParser(description='Collect feedback and append to dataset')
    parser.add_argument('--since-days', type=int, default=7, help='Days to look back')
    parser.add_argument('--log-dir', default='logs', help='Log directory')
    parser.add_argument('--dataset-dir', default='ml/datasets', help='Dataset directory')
    parser.add_argument('--use-api', action='store_true', help='Also collect from API')
    
    args = parser.parse_args()
    
    collector = FeedbackCollector(
        log_dir=args.log_dir,
        dataset_dir=args.dataset_dir
    )
    
    api_client = None
    if args.use_api:
        from api_client import LaravelAPIClient
        import os
        from dotenv import load_dotenv
        load_dotenv()
        
        api_url = os.getenv('LARAVEL_API_URL', 'http://nginx')
        api_token = os.getenv('LARAVEL_API_TOKEN') or os.getenv('APP_API_TOKEN') or ''
        api_client = LaravelAPIClient(api_url, api_token)
    
    output_path = collector.collect_and_append(api_client, args.since_days)
    logger.info(f"Feedback collection complete. Dataset: {output_path}")


if __name__ == "__main__":
    main()

