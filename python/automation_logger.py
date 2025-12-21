"""
Structured Logging Service for Automation Outcomes
Logs automation task results in structured format (CSV or JSON)
"""

import json
import csv
import os
import logging
from datetime import datetime
from typing import Dict, Optional, List
from enum import Enum
from urllib.parse import urlparse

logger = logging.getLogger(__name__)


class FailureReason(Enum):
    """Enumeration of failure reasons"""
    CAPTCHA_FAILED = "captcha_failed"
    COMMENT_FORM_NOT_FOUND = "comment_form_not_found"
    REGISTRATION_FAILED = "registration_failed"
    EMAIL_VERIFICATION_FAILED = "email_verification_failed"
    BLOCKED = "blocked"
    TIMEOUT = "timeout"
    UNKNOWN = "unknown"


class AutomationLogger:
    """
    Structured logger for automation outcomes
    Supports both CSV and JSON output formats
    """
    
    def __init__(self, output_dir: str = "logs", format: str = "json"):
        """
        Initialize logger
        
        Args:
            output_dir: Directory to write log files
            format: Output format - 'json' or 'csv'
        """
        self.output_dir = output_dir
        self.format = format.lower()
        
        # Ensure output directory exists
        os.makedirs(output_dir, exist_ok=True)
        
        # Log file paths
        if self.format == "csv":
            self.log_file = os.path.join(output_dir, "automation_logs.csv")
            self._ensure_csv_header()
        else:
            self.log_file = os.path.join(output_dir, "automation_logs.jsonl")  # JSON Lines format
    
    def _ensure_csv_header(self):
        """Ensure CSV file has header row"""
        if not os.path.exists(self.log_file):
            with open(self.log_file, 'w', newline='', encoding='utf-8') as f:
                writer = csv.writer(f)
                writer.writerow([
                    'timestamp',
                    'task_id',
                    'domain',
                    'action_attempted',
                    'result',
                    'failure_reason',
                    'captcha_type',
                    'execution_time',
                    'retry_count',
                ])
    
    def _extract_domain(self, url: Optional[str]) -> str:
        """
        Extract domain from URL
        
        Args:
            url: URL string or None
            
        Returns:
            Domain string or 'unknown'
        """
        if not url:
            return 'unknown'
        
        try:
            parsed = urlparse(url)
            domain = parsed.netloc or parsed.path.split('/')[0]
            # Remove port if present
            if ':' in domain:
                domain = domain.split(':')[0]
            return domain or 'unknown'
        except Exception:
            return 'unknown'
    
    def _classify_failure_reason(self, error_message: Optional[str], 
                                 action_type: Optional[str] = None) -> str:
        """
        Classify failure reason from error message
        
        Args:
            error_message: Error message string
            action_type: Type of action attempted (comment, profile, forum, guest)
            
        Returns:
            Failure reason enum value
        """
        if not error_message:
            return FailureReason.UNKNOWN.value
        
        error_lower = error_message.lower()
        
        # Check for captcha failures
        if any(phrase in error_lower for phrase in [
            'captcha', 'recaptcha', 'hcaptcha', 'captcha failed', 
            'captcha solving failed', 'failed to solve captcha'
        ]):
            return FailureReason.CAPTCHA_FAILED.value
        
        # Check for comment form not found
        if any(phrase in error_lower for phrase in [
            'comment form not found', 'no comment form', 'comment form',
            'textarea not found', 'form not found'
        ]) or (action_type == 'comment' and 'form' in error_lower and 'not found' in error_lower):
            return FailureReason.COMMENT_FORM_NOT_FOUND.value
        
        # Check for registration failures
        if any(phrase in error_lower for phrase in [
            'registration failed', 'failed to register', 'registration error',
            'signup failed', 'account creation failed', 'unable to register'
        ]):
            return FailureReason.REGISTRATION_FAILED.value
        
        # Check for email verification failures
        if any(phrase in error_lower for phrase in [
            'email verification failed', 'verification failed', 'email not verified',
            'verification link', 'email confirmation failed', 'verify email'
        ]):
            return FailureReason.EMAIL_VERIFICATION_FAILED.value
        
        # Check for blocked/banned
        if any(phrase in error_lower for phrase in [
            'blocked', 'banned', 'access denied', 'forbidden', '403',
            'account suspended', 'ip blocked', 'rate limit'
        ]):
            return FailureReason.BLOCKED.value
        
        # Check for timeouts
        if any(phrase in error_lower for phrase in [
            'timeout', 'timed out', 'time out', 'navigation timeout',
            'waiting for selector', 'waiting for', 'exceeded'
        ]):
            return FailureReason.TIMEOUT.value
        
        # Default to unknown
        return FailureReason.UNKNOWN.value
    
    def _extract_captcha_type(self, error_message: Optional[str], 
                              result_data: Optional[Dict] = None) -> Optional[str]:
        """
        Extract captcha type from error message or result data
        
        Args:
            error_message: Error message string
            result_data: Result dictionary that might contain captcha info
            
        Returns:
            Captcha type string or None
        """
        if not error_message:
            error_message = ""
        
        error_lower = error_message.lower()
        
        # Check error message for captcha type
        if 'recaptcha' in error_lower:
            if 'v3' in error_lower or 'v3' in error_lower:
                return 'recaptcha_v3'
            return 'recaptcha_v2'
        
        if 'hcaptcha' in error_lower or 'h-captcha' in error_lower:
            return 'hcaptcha'
        
        if 'image captcha' in error_lower or 'image' in error_lower:
            return 'image_captcha'
        
        # Check result data
        if result_data:
            captcha_type = result_data.get('captcha_type') or result_data.get('captcha')
            if captcha_type:
                return str(captcha_type)
        
        return None
    
    def log_outcome(self, 
                   task_id: int,
                   domain: Optional[str] = None,
                   action_attempted: Optional[str] = None,
                   result: str = "unknown",
                   failure_reason: Optional[str] = None,
                   captcha_type: Optional[str] = None,
                   execution_time: Optional[float] = None,
                   retry_count: Optional[int] = None,
                   url: Optional[str] = None,
                   error_message: Optional[str] = None,
                   result_data: Optional[Dict] = None):
        """
        Log automation outcome
        
        Args:
            task_id: Task ID
            domain: Domain name (will be extracted from url if not provided)
            action_attempted: Action type (comment, profile, forum, guest)
            result: Result status (success, failed, error)
            failure_reason: Failure reason enum (will be classified if not provided)
            captcha_type: Type of captcha encountered
            execution_time: Execution time in seconds
            retry_count: Number of retries
            url: URL used (for domain extraction)
            error_message: Error message (for failure reason classification)
            result_data: Additional result data
        """
        # Extract domain if not provided
        if not domain and url:
            domain = self._extract_domain(url)
        
        # Classify failure reason if not provided
        if not failure_reason and error_message:
            failure_reason = self._classify_failure_reason(error_message, action_attempted)
        elif not failure_reason:
            failure_reason = FailureReason.UNKNOWN.value
        
        # Extract captcha type if not provided
        if not captcha_type and (error_message or result_data):
            captcha_type = self._extract_captcha_type(error_message, result_data)
        
        # Build log entry
        log_entry = {
            'timestamp': datetime.utcnow().isoformat() + 'Z',
            'task_id': task_id,
            'domain': domain or 'unknown',
            'action_attempted': action_attempted or 'unknown',
            'result': result.lower() if result else 'unknown',
            'failure_reason': failure_reason if result.lower() in ['failed', 'error'] else None,
            'captcha_type': captcha_type,
            'execution_time': round(execution_time, 2) if execution_time is not None else None,
            'retry_count': retry_count or 0,
        }
        
        # Write log entry
        if self.format == "csv":
            self._write_csv_entry(log_entry)
        else:
            self._write_json_entry(log_entry)
        
        logger.debug(f"Logged automation outcome: task_id={task_id}, result={result}, domain={domain}")
    
    def _write_csv_entry(self, entry: Dict):
        """Write entry to CSV file"""
        try:
            with open(self.log_file, 'a', newline='', encoding='utf-8') as f:
                writer = csv.writer(f)
                writer.writerow([
                    entry.get('timestamp', ''),
                    entry.get('task_id', ''),
                    entry.get('domain', ''),
                    entry.get('action_attempted', ''),
                    entry.get('result', ''),
                    entry.get('failure_reason', ''),
                    entry.get('captcha_type', ''),
                    entry.get('execution_time', ''),
                    entry.get('retry_count', ''),
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
    
    def get_recent_logs(self, limit: int = 100) -> List[Dict]:
        """
        Get recent log entries
        
        Args:
            limit: Maximum number of entries to return
            
        Returns:
            List of log entries
        """
        if not os.path.exists(self.log_file):
            return []
        
        entries = []
        try:
            if self.format == "csv":
                with open(self.log_file, 'r', encoding='utf-8') as f:
                    reader = csv.DictReader(f)
                    entries = list(reader)
            else:
                with open(self.log_file, 'r', encoding='utf-8') as f:
                    for line in f:
                        if line.strip():
                            entries.append(json.loads(line))
            
            # Return most recent entries
            return entries[-limit:]
        except Exception as e:
            logger.error(f"Failed to read log entries: {e}")
            return []


# Global logger instance (can be overridden)
_global_logger: Optional[AutomationLogger] = None


def get_logger() -> AutomationLogger:
    """Get global logger instance"""
    global _global_logger
    if _global_logger is None:
        # Default to JSON format in logs directory
        log_format = os.getenv('AUTOMATION_LOG_FORMAT', 'json').lower()
        log_dir = os.getenv('AUTOMATION_LOG_DIR', 'logs')
        _global_logger = AutomationLogger(output_dir=log_dir, format=log_format)
    return _global_logger


def set_logger(logger_instance: AutomationLogger):
    """Set global logger instance"""
    global _global_logger
    _global_logger = logger_instance

