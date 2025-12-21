# Structured Logging - Code Snippets

This document shows where structured logging hooks are integrated in the codebase.

## 1. Worker Level Logging (`python/worker.py`)

### Import and Initialization

```python
from automation_logger import get_logger

def process_task(api_client: LaravelAPIClient, task: dict):
    task_id = task['id']
    task_type = task['type']
    retry_count = task.get('retry_count', 0)
    
    # Initialize structured logger
    automation_logger = get_logger()
    
    # Track execution time
    start_time = time.time()
    execution_time = None
    result_url = None
    error_message = None
    result_status = 'unknown'
```

### Success Logging

```python
# After successful task completion
if result.get('success'):
    # ... create opportunity ...
    
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
```

### Failure Logging

```python
# After task failure
else:
    error_msg = result.get('error', 'Unknown error')
    result_url = result.get('url')
    error_message = error_msg
    result_status = 'failed'
    
    # ... update task status ...
    
    # Log structured outcome (failure)
    try:
        # Extract captcha_type and failure_reason from result if present
        captcha_type = result.get('captcha_type')
        failure_reason = result.get('failure_reason')
        
        automation_logger.log_outcome(
            task_id=task_id,
            action_attempted=task_type,
            result=result_status,
            failure_reason=failure_reason,
            captcha_type=captcha_type,
            error_message=error_message,
            execution_time=execution_time,
            retry_count=retry_count,
            url=result_url,
            result_data=result
        )
    except Exception as log_error:
        logger.warning(f"Failed to log automation outcome: {log_error}")
```

### Exception Logging

```python
# After exception during task processing
except Exception as e:
    error_msg = str(e)
    # ... handle exception ...
    
    # Calculate execution time if not already calculated
    if execution_time is None:
        execution_time = time.time() - start_time
    
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
```

## 2. Automation Module Level (`python/automation/comment.py`)

### Comment Form Not Found

```python
# Find comment form
comment_form = self._find_comment_form()
if not comment_form:
    return {
        'success': False,
        'error': 'Comment form not found',
        'backlink_id': opportunity.get('id') if opportunity else None,
        'failure_reason': 'comment_form_not_found',  # Explicit failure reason
    }
```

### Captcha Detection

```python
# Check for captcha before filling form
captcha_type = None
if self.captcha_solver and self.page:
    try:
        # Check if captcha is present
        page_html = self.page.content()
        if 'recaptcha' in page_html.lower() or 'hcaptcha' in page_html.lower():
            # Try to solve captcha
            captcha_solved = self.solve_captcha_if_present()
            if not captcha_solved:
                # Extract captcha type
                if 'recaptcha' in page_html.lower():
                    captcha_type = 'recaptcha_v2' if 'v3' not in page_html.lower() else 'recaptcha_v3'
                elif 'hcaptcha' in page_html.lower():
                    captcha_type = 'hcaptcha'
                else:
                    captcha_type = 'image_captcha'
    except Exception as e:
        logger.debug(f"Error checking for captcha: {e}")
```

### Error Handling with Captcha Type

```python
except Exception as e:
    error_msg = str(e)
    logger.error(f"Comment automation failed: {error_msg}", exc_info=True)
    
    # Extract captcha type from error if present
    captcha_type = None
    if 'captcha' in error_msg.lower():
        if 'recaptcha' in error_msg.lower():
            captcha_type = 'recaptcha_v2' if 'v3' not in error_msg.lower() else 'recaptcha_v3'
        elif 'hcaptcha' in error_msg.lower():
            captcha_type = 'hcaptcha'
        else:
            captcha_type = 'image_captcha'
    
    return {
        'success': False,
        'error': error_msg,
        'backlink_id': opportunity.get('id') if opportunity else None,
        'captcha_type': captcha_type,  # Include in result
    }
```

## 3. Automation Module Level (`python/automation/profile.py`)

### Registration Failure Detection

```python
except Exception as e:
    error_msg = str(e)
    logger.error(f"Profile automation failed: {error_msg}", exc_info=True)
    
    # Extract failure reason from error
    failure_reason = None
    if 'registration' in error_msg.lower() or 'signup' in error_msg.lower():
        failure_reason = 'registration_failed'
    elif 'captcha' in error_msg.lower():
        failure_reason = 'captcha_failed'
    elif 'timeout' in error_msg.lower():
        failure_reason = 'timeout'
    elif 'blocked' in error_msg.lower() or 'banned' in error_msg.lower():
        failure_reason = 'blocked'
    
    # Extract captcha type
    captcha_type = None
    if 'captcha' in error_msg.lower():
        if 'recaptcha' in error_msg.lower():
            captcha_type = 'recaptcha_v2' if 'v3' not in error_msg.lower() else 'recaptcha_v3'
        elif 'hcaptcha' in error_msg.lower():
            captcha_type = 'hcaptcha'
        else:
            captcha_type = 'image_captcha'
    
    return {
        'success': False,
        'error': error_msg,
        'backlink_id': opportunity.get('id') if opportunity else None,
        'failure_reason': failure_reason,  # Include in result
        'captcha_type': captcha_type,  # Include in result
    }
```

## 4. Logging Service (`python/automation_logger.py`)

### Failure Reason Classification

```python
def _classify_failure_reason(self, error_message: Optional[str], 
                             action_type: Optional[str] = None) -> str:
    """Classify failure reason from error message"""
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
    
    # ... other classifications ...
    
    return FailureReason.UNKNOWN.value
```

### Domain Extraction

```python
def _extract_domain(self, url: Optional[str]) -> str:
    """Extract domain from URL"""
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
```

### Log Entry Creation

```python
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
    """Log automation outcome"""
    
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
```

## 5. Configuration

### Environment Variables

```bash
# Set log format (json or csv)
export AUTOMATION_LOG_FORMAT=json

# Set log directory
export AUTOMATION_LOG_DIR=logs
```

### Default Initialization

```python
# In automation_logger.py
def get_logger() -> AutomationLogger:
    """Get global logger instance"""
    global _global_logger
    if _global_logger is None:
        # Default to JSON format in logs directory
        log_format = os.getenv('AUTOMATION_LOG_FORMAT', 'json').lower()
        log_dir = os.getenv('AUTOMATION_LOG_DIR', 'logs')
        _global_logger = AutomationLogger(output_dir=log_dir, format=log_format)
    return _global_logger
```

## Summary

Logging hooks are integrated at:

1. **Worker level** (`worker.py`):
   - Tracks execution time
   - Logs success/failure/error outcomes
   - Extracts metadata from result dictionaries

2. **Automation module level** (`automation/*.py`):
   - Adds `failure_reason` and `captcha_type` to result dictionaries
   - Detects captcha types from page content
   - Classifies failure reasons from error messages

3. **Logging service** (`automation_logger.py`):
   - Classifies failure reasons automatically
   - Extracts domains from URLs
   - Writes structured logs in CSV or JSON format

All logging is non-intrusive - it doesn't change automation behavior, only captures outcomes.

