# Structured Logging Schema for Automation Outcomes

## Overview

The structured logging system captures automation task outcomes in a standardized format for analysis and monitoring. Logs are written in either CSV or JSON format (configurable).

## Log Schema

### Fields

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `timestamp` | ISO 8601 string | Yes | UTC timestamp when the log entry was created |
| `task_id` | Integer | Yes | Unique identifier of the automation task |
| `domain` | String | Yes | Domain name extracted from the target URL |
| `action_attempted` | String | Yes | Type of action attempted: `comment`, `profile`, `forum`, `guest` |
| `result` | String | Yes | Outcome: `success`, `failed`, `error` |
| `failure_reason` | Enum | Conditional | Failure reason enum (only present when result is `failed` or `error`) |
| `captcha_type` | String | Optional | Type of captcha encountered: `recaptcha_v2`, `recaptcha_v3`, `hcaptcha`, `image_captcha` |
| `execution_time` | Float | Optional | Execution time in seconds (rounded to 2 decimal places) |
| `retry_count` | Integer | Yes | Number of retries attempted (0 for first attempt) |

### Failure Reason Enums

The `failure_reason` field uses the following enum values:

- `captcha_failed` - Captcha solving failed
- `comment_form_not_found` - Comment form could not be found on the page
- `registration_failed` - Account registration failed
- `email_verification_failed` - Email verification step failed
- `blocked` - Access blocked, banned, or forbidden (403)
- `timeout` - Operation timed out
- `unknown` - Unknown or unclassified failure

## Output Formats

### JSON Format (Default)

JSON Lines format (`.jsonl`) - one JSON object per line:

```json
{"timestamp":"2024-01-15T10:30:00.123Z","task_id":123,"domain":"example.com","action_attempted":"comment","result":"success","failure_reason":null,"captcha_type":null,"execution_time":12.45,"retry_count":0}
{"timestamp":"2024-01-15T10:31:00.456Z","task_id":124,"domain":"test.com","action_attempted":"profile","result":"failed","failure_reason":"registration_failed","captcha_type":"recaptcha_v2","execution_time":8.23,"retry_count":1}
```

### CSV Format

Comma-separated values with header row:

```csv
timestamp,task_id,domain,action_attempted,result,failure_reason,captcha_type,execution_time,retry_count
2024-01-15T10:30:00.123Z,123,example.com,comment,success,,,12.45,0
2024-01-15T10:31:00.456Z,124,test.com,profile,failed,registration_failed,recaptcha_v2,8.23,1
```

## Configuration

### Environment Variables

- `AUTOMATION_LOG_FORMAT` - Output format: `json` (default) or `csv`
- `AUTOMATION_LOG_DIR` - Directory for log files (default: `logs`)

### Example

```bash
export AUTOMATION_LOG_FORMAT=csv
export AUTOMATION_LOG_DIR=/var/log/automation
```

## Log File Locations

- **JSON format**: `{AUTOMATION_LOG_DIR}/automation_logs.jsonl`
- **CSV format**: `{AUTOMATION_LOG_DIR}/automation_logs.csv`

Default location: `python/logs/` (relative to worker script)

## Failure Reason Classification

The system automatically classifies failure reasons from error messages:

### Captcha Failures

Detected when error message contains:
- `captcha`, `recaptcha`, `hcaptcha`
- `captcha failed`, `captcha solving failed`

### Comment Form Not Found

Detected when:
- Error message contains: `comment form not found`, `no comment form`, `form not found`
- Action type is `comment` and error mentions form not found

### Registration Failures

Detected when error message contains:
- `registration failed`, `failed to register`, `signup failed`
- `account creation failed`, `unable to register`

### Email Verification Failures

Detected when error message contains:
- `email verification failed`, `verification failed`
- `email not verified`, `verification link`, `email confirmation failed`

### Blocked

Detected when error message contains:
- `blocked`, `banned`, `access denied`, `forbidden`, `403`
- `account suspended`, `ip blocked`, `rate limit`

### Timeout

Detected when error message contains:
- `timeout`, `timed out`, `time out`
- `navigation timeout`, `waiting for selector`, `exceeded`

### Unknown

Default classification when no specific pattern matches.

## Domain Extraction

Domain is extracted from URLs using `urllib.parse.urlparse`:
- Removes protocol (`http://`, `https://`)
- Removes path and query parameters
- Removes port numbers
- Returns `unknown` if extraction fails

Example:
- `https://example.com/page?param=value` → `example.com`
- `http://subdomain.example.com:8080/path` → `subdomain.example.com`

## Code Integration Points

### Worker Level (`worker.py`)

Logging occurs in `process_task()` function:

1. **Task Start**: Records start time
2. **Task Success**: Logs success with execution time
3. **Task Failure**: Logs failure with error message and failure reason
4. **Task Exception**: Logs error with exception details

### Automation Module Level

Automation modules can include additional metadata in result dictionaries:

```python
return {
    'success': False,
    'error': 'Comment form not found',
    'failure_reason': 'comment_form_not_found',  # Optional: explicit failure reason
    'captcha_type': 'recaptcha_v2',  # Optional: captcha type if encountered
    'backlink_id': 123,
}
```

The worker extracts these fields and includes them in the log entry.

## Example Log Entries

### Success Case

```json
{
  "timestamp": "2024-01-15T10:30:00.123Z",
  "task_id": 123,
  "domain": "example.com",
  "action_attempted": "comment",
  "result": "success",
  "failure_reason": null,
  "captcha_type": null,
  "execution_time": 12.45,
  "retry_count": 0
}
```

### Failure Case - Captcha

```json
{
  "timestamp": "2024-01-15T10:31:00.456Z",
  "task_id": 124,
  "domain": "test.com",
  "action_attempted": "comment",
  "result": "failed",
  "failure_reason": "captcha_failed",
  "captcha_type": "recaptcha_v2",
  "execution_time": 8.23,
  "retry_count": 0
}
```

### Failure Case - Form Not Found

```json
{
  "timestamp": "2024-01-15T10:32:00.789Z",
  "task_id": 125,
  "domain": "blog.com",
  "action_attempted": "comment",
  "result": "failed",
  "failure_reason": "comment_form_not_found",
  "captcha_type": null,
  "execution_time": 5.12,
  "retry_count": 1
}
```

### Error Case - Timeout

```json
{
  "timestamp": "2024-01-15T10:33:00.012Z",
  "task_id": 126,
  "domain": "forum.com",
  "action_attempted": "forum",
  "result": "error",
  "failure_reason": "timeout",
  "captcha_type": null,
  "execution_time": 30.00,
  "retry_count": 2
}
```

## Database Schema (If Storing in DB)

If you want to store logs in a database, use this schema:

```sql
CREATE TABLE automation_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    timestamp DATETIME NOT NULL,
    task_id INT NOT NULL,
    domain VARCHAR(255) NOT NULL,
    action_attempted VARCHAR(50) NOT NULL,
    result ENUM('success', 'failed', 'error') NOT NULL,
    failure_reason ENUM('captcha_failed', 'comment_form_not_found', 'registration_failed', 
                       'email_verification_failed', 'blocked', 'timeout', 'unknown') NULL,
    captcha_type VARCHAR(50) NULL,
    execution_time DECIMAL(10,2) NULL,
    retry_count INT NOT NULL DEFAULT 0,
    INDEX idx_timestamp (timestamp),
    INDEX idx_task_id (task_id),
    INDEX idx_domain (domain),
    INDEX idx_result (result),
    INDEX idx_failure_reason (failure_reason)
);
```

## Usage Examples

### Reading Logs (Python)

```python
from automation_logger import AutomationLogger

logger = AutomationLogger(format='json')
recent_logs = logger.get_recent_logs(limit=100)

for entry in recent_logs:
    print(f"Task {entry['task_id']}: {entry['result']} on {entry['domain']}")
```

### Analyzing Logs (Python)

```python
import json
from collections import Counter

# Read JSON Lines file
failure_reasons = []
with open('logs/automation_logs.jsonl', 'r') as f:
    for line in f:
        entry = json.loads(line)
        if entry['result'] == 'failed' and entry['failure_reason']:
            failure_reasons.append(entry['failure_reason'])

# Count failure reasons
counter = Counter(failure_reasons)
print("Failure Reasons:")
for reason, count in counter.most_common():
    print(f"  {reason}: {count}")
```

### Importing CSV to Database

```sql
LOAD DATA INFILE '/path/to/automation_logs.csv'
INTO TABLE automation_logs
FIELDS TERMINATED BY ','
ENCLOSED BY '"'
LINES TERMINATED BY '\n'
IGNORE 1 ROWS
(timestamp, task_id, domain, action_attempted, result, 
 failure_reason, captcha_type, execution_time, retry_count);
```

## Notes

- Logs are append-only (no updates or deletes)
- Timestamps are in UTC
- Execution time is measured from task start to completion
- Retry count is from the task record in the database
- Domain extraction handles edge cases gracefully (returns `unknown` if extraction fails)
- Failure reason classification is best-effort (may default to `unknown` for novel errors)

