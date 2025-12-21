# Shadow Mode Implementation Guide

## Overview

Shadow mode allows you to validate AI accuracy without risk by:
- **AI predicts** the best action type
- **Rule-based system executes** (uses original task_type)
- **Logs comparison** between AI prediction and actual result

## How It Works

### Flow

```
1. Task arrives with task_type (e.g., 'comment')
2. OpportunitySelector runs in shadow mode:
   - AI predicts best action (e.g., 'profile')
   - Rule-based system selects opportunity for 'comment'
   - Both are logged
3. Automation executes with rule-based action ('comment')
4. Result is logged (success/failed)
5. Comparison: AI prediction vs actual result
```

### Key Points

- ✅ **No risk**: Rule-based system always executes
- ✅ **AI validation**: Compare AI predictions with actual outcomes
- ✅ **Accurate metrics**: Track if AI would have been better
- ✅ **No automation changes**: Playwright classes unchanged

## Configuration

### Enable Shadow Mode

Set environment variable or pass to OpportunitySelector:

```python
import os

# Via environment variable
os.environ['SHADOW_MODE'] = 'true'

# Or in code
from opportunity_selector import OpportunitySelector
from api_client import LaravelAPIClient

api_client = LaravelAPIClient(api_url, api_token)
selector = OpportunitySelector(api_client, shadow_mode=True)
```

### Environment Variables

```bash
# Enable shadow mode
export SHADOW_MODE=true

# Shadow mode log format (json or csv)
export SHADOW_MODE_LOG_FORMAT=json

# Shadow mode log directory
export SHADOW_MODE_LOG_DIR=logs
```

## Logging Structure

### Log File

- **JSON format**: `logs/shadow_mode_logs.jsonl` (JSON Lines)
- **CSV format**: `logs/shadow_mode_logs.csv`

### Log Fields

| Field | Description |
|-------|-------------|
| `timestamp` | When prediction was made |
| `task_id` | Task identifier |
| `campaign_id` | Campaign identifier |
| `backlink_id` | Backlink identifier |
| `domain` | Domain name |
| `pa` | Page Authority |
| `da` | Domain Authority |
| `site_type` | Site type |
| `rule_based_action` | Action that was executed |
| `ai_predicted_action` | Action AI predicted |
| `ai_confidence` | AI confidence score (0-1) |
| `ai_probabilities` | All AI probabilities (JSON) |
| `task_result` | success, failed, or error |
| `execution_time` | Execution time in seconds |
| `retry_count` | Number of retries |
| `ai_correct` | True if AI matched rule-based action |
| `ai_would_have_succeeded` | True if AI action would have succeeded (if different) |
| `notes` | Additional notes |

### Example Log Entry

```json
{
  "timestamp": "2024-01-15T10:30:00.123Z",
  "task_id": 123,
  "campaign_id": 1,
  "backlink_id": 456,
  "domain": "example.com",
  "pa": 45,
  "da": 60,
  "site_type": "comment",
  "rule_based_action": "comment",
  "ai_predicted_action": "profile",
  "ai_confidence": 0.67,
  "ai_probabilities": "{\"comment\":0.15,\"profile\":0.67,\"forum\":0.12,\"guest\":0.06}",
  "task_result": "success",
  "execution_time": 12.45,
  "retry_count": 0,
  "ai_correct": false,
  "ai_would_have_succeeded": null,
  "notes": "AI predicted profile but comment was executed"
}
```

## Integration Points

### 1. OpportunitySelector

**File:** `python/opportunity_selector.py`

```python
# Initialize with shadow mode
selector = OpportunitySelector(api_client, shadow_mode=True)

# Select opportunity (AI predicts, rules execute)
opportunity = selector.select_opportunity(
    campaign_id=1,
    task_type='comment'
)

# Opportunity includes:
# - opportunity['ai_recommended_action_type']: AI prediction
# - opportunity['ai_probability']: AI confidence
# - opportunity['shadow_mode']: True flag
```

### 2. Automation Classes

**File:** `python/automation/base.py`

```python
# Store opportunity for shadow mode logging
self.last_opportunity = opportunity
```

### 3. Worker

**File:** `python/worker.py`

```python
# Log shadow mode prediction before execution
shadow_logger.log_prediction(
    task_id=task_id,
    campaign_id=campaign_id,
    backlink=opportunity,
    rule_based_action=task_type,
    ai_prediction=ai_prediction
)

# Log shadow mode result after execution
shadow_logger.log_result(
    task_id=task_id,
    rule_based_action=task_type,
    task_result='success',  # or 'failed', 'error'
    execution_time=execution_time,
    retry_count=retry_count,
    ai_prediction=ai_prediction
)
```

## Analysis

### Calculate Accuracy

```python
from shadow_mode_logger import ShadowModeLogger

logger = ShadowModeLogger()
stats = logger.get_accuracy_stats()

print(f"Total tasks: {stats['total_tasks']}")
print(f"AI correct: {stats['ai_correct_count']} ({stats['ai_correct_rate']:.2%})")
print(f"AI different: {stats['ai_different_count']} ({stats['ai_different_rate']:.2%})")
```

### Analyze Logs

```python
import json
import pandas as pd

# Read JSONL logs
entries = []
with open('logs/shadow_mode_logs.jsonl', 'r') as f:
    for line in f:
        if line.strip():
            entries.append(json.loads(line))

df = pd.DataFrame(entries)

# Filter completed tasks
completed = df[df['task_result'].notna()]

# AI accuracy
ai_correct = completed['ai_correct'].sum()
total = len(completed)
accuracy = ai_correct / total

print(f"AI Accuracy: {accuracy:.2%}")

# Cases where AI was different
different = completed[completed['ai_correct'] == False]
print(f"AI different in {len(different)} cases")

# When rule-based failed but AI predicted different action
rule_failed = completed[completed['task_result'] == 'failed']
ai_different_when_failed = rule_failed[rule_failed['ai_correct'] == False]
print(f"AI predicted different action when rule-based failed: {len(ai_different_when_failed)}")
```

## Metrics to Track

### 1. AI Accuracy

- **AI Correct Rate**: Percentage of times AI matched rule-based action
- **AI Different Rate**: Percentage of times AI predicted different action

### 2. Performance Comparison

- **When rule-based succeeded**: How often did AI agree?
- **When rule-based failed**: Did AI predict a different (better) action?

### 3. Confidence Analysis

- **High confidence predictions**: How accurate when AI confidence > 0.7?
- **Low confidence predictions**: How accurate when AI confidence < 0.5?

## Example Analysis Script

```python
from shadow_mode_logger import ShadowModeLogger
import json

logger = ShadowModeLogger()

# Get accuracy stats
stats = logger.get_accuracy_stats()
print("Shadow Mode Accuracy Statistics")
print("=" * 50)
print(f"Total tasks: {stats['total_tasks']}")
print(f"AI correct: {stats['ai_correct_count']} ({stats['ai_correct_rate']:.2%})")
print(f"AI different: {stats['ai_different_count']} ({stats['ai_different_rate']:.2%})")
print(f"AI different when rule failed: {stats['ai_different_when_rule_failed']}")
print(f"AI different when rule succeeded: {stats['ai_different_when_rule_succeeded']}")

# Detailed analysis
if stats['total_tasks'] > 0:
    # Calculate potential improvement
    if stats['ai_different_when_rule_failed'] > 0:
        potential_improvement = stats['ai_different_when_rule_failed'] / stats['total_tasks']
        print(f"\nPotential improvement: {potential_improvement:.2%}")
        print("(Cases where rule-based failed but AI predicted different action)")
```

## Enabling Shadow Mode

### Option 1: Environment Variable

```bash
export SHADOW_MODE=true
python worker.py
```

### Option 2: Code Configuration

```python
# In worker.py or automation initialization
import os
os.environ['SHADOW_MODE'] = 'true'
```

### Option 3: Direct Initialization

```python
# Modify OpportunitySelector initialization
selector = OpportunitySelector(api_client, shadow_mode=True)
```

## Validation Workflow

1. **Enable shadow mode** for a period (e.g., 1 week)
2. **Collect logs** with AI predictions vs actual results
3. **Analyze accuracy** using provided scripts
4. **Compare performance**:
   - When did AI agree with rules?
   - When did AI predict differently?
   - Would AI have succeeded when rules failed?
5. **Make decision**: Enable AI or continue shadow mode

## Notes

- **No performance impact**: Shadow mode adds minimal overhead
- **Safe**: Rule-based system always executes
- **Comprehensive logging**: All predictions and results logged
- **Easy analysis**: Structured logs for easy analysis
- **Production ready**: Can run in production without risk

