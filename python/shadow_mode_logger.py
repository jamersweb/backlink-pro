"""
Shadow Mode Logger for AI Decision Engine Validation

Logs AI predictions vs actual results to validate AI accuracy without risk
"""

import json
import csv
import os
import logging
from datetime import datetime
from typing import Dict, Optional, List
from pathlib import Path

logger = logging.getLogger(__name__)


class ShadowModeLogger:
    """
    Logger for shadow mode validation
    Tracks AI predictions vs actual execution results
    """
    
    def __init__(self, output_dir: str = "logs", format: str = "json"):
        """
        Initialize shadow mode logger
        
        Args:
            output_dir: Directory to write log files
            format: Output format - 'json' or 'csv'
        """
        self.output_dir = Path(output_dir)
        self.output_dir.mkdir(parents=True, exist_ok=True)
        self.format = format.lower()
        
        # Log file paths
        if self.format == "csv":
            self.log_file = self.output_dir / "shadow_mode_logs.csv"
            self._ensure_csv_header()
        else:
            self.log_file = self.output_dir / "shadow_mode_logs.jsonl"  # JSON Lines format
    
    def _ensure_csv_header(self):
        """Ensure CSV file has header row"""
        if not self.log_file.exists():
            with open(self.log_file, 'w', newline='', encoding='utf-8') as f:
                writer = csv.writer(f)
                writer.writerow([
                    'timestamp',
                    'task_id',
                    'campaign_id',
                    'backlink_id',
                    'domain',
                    'pa',
                    'da',
                    'site_type',
                    'rule_based_action',  # Action that was actually executed
                    'ai_predicted_action',  # Action AI predicted
                    'ai_confidence',  # AI confidence in prediction
                    'ai_probabilities',  # All AI probabilities (JSON)
                    'task_result',  # success, failed, error
                    'execution_time',
                    'retry_count',
                    'ai_correct',  # True if AI prediction matches rule-based action
                    'ai_would_have_succeeded',  # True if AI action would have succeeded (if different)
                    'notes',
                ])
    
    def log_prediction(self, task_id: int, campaign_id: int, backlink: Dict,
                      rule_based_action: str, ai_prediction: Dict) -> str:
        """
        Log AI prediction before execution
        
        Args:
            task_id: Task ID
            campaign_id: Campaign ID
            backlink: Backlink/opportunity dictionary
            rule_based_action: Action that will be executed (rule-based)
            ai_prediction: AI prediction dictionary with:
                - 'action': Predicted action type
                - 'probability': Confidence score
                - 'probabilities': All probabilities
        
        Returns:
            Prediction ID for later matching with result
        """
        prediction_id = f"{task_id}_{datetime.utcnow().timestamp()}"
        
        log_entry = {
            'timestamp': datetime.utcnow().isoformat() + 'Z',
            'prediction_id': prediction_id,
            'task_id': task_id,
            'campaign_id': campaign_id,
            'backlink_id': backlink.get('id'),
            'domain': self._extract_domain(backlink.get('url')),
            'pa': backlink.get('pa', 0),
            'da': backlink.get('da', 0),
            'site_type': backlink.get('site_type', 'unknown'),
            'rule_based_action': rule_based_action,
            'ai_predicted_action': ai_prediction.get('action', 'unknown'),
            'ai_confidence': ai_prediction.get('probability', 0.0),
            'ai_probabilities': json.dumps(ai_prediction.get('probabilities', {})),
            'task_result': None,  # Will be filled when result is logged
            'execution_time': None,
            'retry_count': None,
            'ai_correct': None,
            'ai_would_have_succeeded': None,
            'notes': None,
        }
        
        # Write prediction
        if self.format == "csv":
            self._write_csv_entry(log_entry, is_prediction=True)
        else:
            self._write_json_entry(log_entry)
        
        logger.debug(f"Logged shadow mode prediction: task_id={task_id}, AI={ai_prediction.get('action')}, Rule={rule_based_action}")
        
        return prediction_id
    
    def log_result(self, task_id: int, rule_based_action: str, 
                  task_result: str, execution_time: Optional[float] = None,
                  retry_count: Optional[int] = None, 
                  ai_prediction: Optional[Dict] = None) -> bool:
        """
        Log task result and compare with AI prediction
        
        Args:
            task_id: Task ID
            rule_based_action: Action that was executed
            task_result: Result (success, failed, error)
            execution_time: Execution time in seconds
            retry_count: Number of retries
            ai_prediction: AI prediction dict (if available for matching)
        
        Returns:
            True if logged successfully
        """
        # Find matching prediction entry
        prediction_entry = self._find_prediction_entry(task_id)
        
        if not prediction_entry:
            logger.warning(f"No prediction entry found for task_id={task_id}, creating new entry")
            prediction_entry = {
                'task_id': task_id,
                'rule_based_action': rule_based_action,
                'ai_predicted_action': ai_prediction.get('action') if ai_prediction else 'unknown',
                'ai_confidence': ai_prediction.get('probability', 0.0) if ai_prediction else 0.0,
                'ai_probabilities': json.dumps(ai_prediction.get('probabilities', {})) if ai_prediction else '{}',
            }
        
        # Update with result
        prediction_entry['task_result'] = task_result
        prediction_entry['execution_time'] = round(execution_time, 2) if execution_time is not None else None
        prediction_entry['retry_count'] = retry_count or 0
        
        # Compare AI prediction vs rule-based action
        ai_predicted = prediction_entry.get('ai_predicted_action', 'unknown')
        rule_action = rule_based_action
        
        prediction_entry['ai_correct'] = (ai_predicted == rule_action)
        
        # Determine if AI would have succeeded (if different action)
        if ai_predicted != rule_action:
            # We can't know for sure, but we can note it
            prediction_entry['ai_would_have_succeeded'] = None  # Unknown
            prediction_entry['notes'] = f"AI predicted {ai_predicted} but {rule_action} was executed"
        else:
            # Same action, so result applies to both
            prediction_entry['ai_would_have_succeeded'] = (task_result == 'success')
            prediction_entry['notes'] = None
        
        # Write updated entry
        if self.format == "csv":
            # For CSV, we need to update the existing row or append
            # For simplicity, we'll append a new row with complete data
            self._write_csv_entry(prediction_entry, is_prediction=False)
        else:
            # For JSONL, append updated entry
            self._write_json_entry(prediction_entry)
        
        logger.debug(
            f"Logged shadow mode result: task_id={task_id}, "
            f"AI={ai_predicted}, Rule={rule_action}, Result={task_result}, "
            f"Match={prediction_entry['ai_correct']}"
        )
        
        return True
    
    def _find_prediction_entry(self, task_id: int) -> Optional[Dict]:
        """Find prediction entry for a task_id"""
        if not self.log_file.exists():
            return None
        
        try:
            if self.format == "csv":
                # Read CSV and find matching task_id
                with open(self.log_file, 'r', encoding='utf-8') as f:
                    reader = csv.DictReader(f)
                    for row in reader:
                        if int(row.get('task_id', 0)) == task_id:
                            return dict(row)
            else:
                # Read JSONL and find matching task_id
                with open(self.log_file, 'r', encoding='utf-8') as f:
                    for line in f:
                        if line.strip():
                            entry = json.loads(line)
                            if entry.get('task_id') == task_id:
                                return entry
        except Exception as e:
            logger.warning(f"Error finding prediction entry: {e}")
        
        return None
    
    def _extract_domain(self, url: Optional[str]) -> str:
        """Extract domain from URL"""
        if not url:
            return 'unknown'
        
        try:
            from urllib.parse import urlparse
            parsed = urlparse(str(url))
            domain = parsed.netloc or parsed.path.split('/')[0]
            if ':' in domain:
                domain = domain.split(':')[0]
            return domain or 'unknown'
        except:
            return 'unknown'
    
    def _write_csv_entry(self, entry: Dict, is_prediction: bool = True):
        """Write entry to CSV file"""
        try:
            with open(self.log_file, 'a', newline='', encoding='utf-8') as f:
                writer = csv.writer(f)
                writer.writerow([
                    entry.get('timestamp', ''),
                    entry.get('task_id', ''),
                    entry.get('campaign_id', ''),
                    entry.get('backlink_id', ''),
                    entry.get('domain', ''),
                    entry.get('pa', ''),
                    entry.get('da', ''),
                    entry.get('site_type', ''),
                    entry.get('rule_based_action', ''),
                    entry.get('ai_predicted_action', ''),
                    entry.get('ai_confidence', ''),
                    entry.get('ai_probabilities', ''),
                    entry.get('task_result', ''),
                    entry.get('execution_time', ''),
                    entry.get('retry_count', ''),
                    entry.get('ai_correct', ''),
                    entry.get('ai_would_have_succeeded', ''),
                    entry.get('notes', ''),
                ])
        except Exception as e:
            logger.error(f"Failed to write CSV log entry: {e}")
    
    def _write_json_entry(self, entry: Dict):
        """Write entry to JSON Lines file"""
        try:
            with open(self.log_file, 'a', encoding='utf-8') as f:
                f.write(json.dumps(entry, ensure_ascii=False) + '\n')
        except Exception as e:
            logger.error(f"Failed to write JSON log entry: {e}")
    
    def get_accuracy_stats(self) -> Dict:
        """
        Calculate accuracy statistics from shadow mode logs
        
        Returns:
            Dictionary with accuracy metrics
        """
        if not self.log_file.exists():
            return {}
        
        total = 0
        ai_correct = 0
        ai_different = 0
        ai_different_succeeded = 0
        ai_different_failed = 0
        
        try:
            if self.format == "csv":
                with open(self.log_file, 'r', encoding='utf-8') as f:
                    reader = csv.DictReader(f)
                    for row in reader:
                        if row.get('task_result'):  # Only count completed tasks
                            total += 1
                            if row.get('ai_correct', '').lower() == 'true':
                                ai_correct += 1
                            elif row.get('ai_predicted_action') != row.get('rule_based_action'):
                                ai_different += 1
                                if row.get('task_result') == 'success':
                                    ai_different_failed += 1  # Rule-based succeeded, AI was different
                                else:
                                    ai_different_succeeded += 1  # Rule-based failed, AI was different
            else:
                with open(self.log_file, 'r', encoding='utf-8') as f:
                    for line in f:
                        if line.strip():
                            entry = json.loads(line)
                            if entry.get('task_result'):
                                total += 1
                                if entry.get('ai_correct'):
                                    ai_correct += 1
                                elif entry.get('ai_predicted_action') != entry.get('rule_based_action'):
                                    ai_different += 1
                                    if entry.get('task_result') == 'success':
                                        ai_different_failed += 1
                                    else:
                                        ai_different_succeeded += 1
        except Exception as e:
            logger.error(f"Error calculating accuracy stats: {e}")
            return {}
        
        if total == 0:
            return {}
        
        return {
            'total_tasks': total,
            'ai_correct_count': ai_correct,
            'ai_correct_rate': ai_correct / total if total > 0 else 0,
            'ai_different_count': ai_different,
            'ai_different_rate': ai_different / total if total > 0 else 0,
            'ai_different_when_rule_failed': ai_different_succeeded,
            'ai_different_when_rule_succeeded': ai_different_failed,
        }


# Global logger instance
_global_shadow_logger: Optional[ShadowModeLogger] = None


def get_shadow_logger() -> ShadowModeLogger:
    """Get global shadow mode logger instance"""
    global _global_shadow_logger
    if _global_shadow_logger is None:
        log_format = os.getenv('SHADOW_MODE_LOG_FORMAT', 'json').lower()
        log_dir = os.getenv('SHADOW_MODE_LOG_DIR', 'logs')
        _global_shadow_logger = ShadowModeLogger(output_dir=log_dir, format=log_format)
    return _global_shadow_logger

