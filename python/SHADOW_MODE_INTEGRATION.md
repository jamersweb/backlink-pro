# Shadow Mode Integration - Code Snippets

## Overview

Shadow mode validates AI accuracy by:
1. **AI predicts** best action type
2. **Rule-based system executes** (uses original task_type)
3. **Logs comparison** for validation

## Integration Points

### 1. OpportunitySelector - Shadow Mode Selection

**File:** `python/opportunity_selector.py`

```python
def _select_with_shadow_mode(self, campaign_id: int, task_type: str,
                             site_type: Optional[str]) -> Optional[Dict]:
    """Select opportunity in shadow mode: AI predicts, rules execute"""
    
    # Get opportunity using rules (what will actually be executed)
    opportunity = self._select_with_rules(campaign_id, task_type, site_type)
    
    if not opportunity:
        return None
    
    # Get AI prediction for logging (but don't use it)
    site_features = {
        'pa': opportunity.get('pa', 0),
        'da': opportunity.get('da', 0),
        'site_type': opportunity.get('site_type', 'comment'),
    }
    
    # Get AI prediction
    probabilities = self.ai_engine.predict(site_features)
    best_action, best_prob = self.ai_engine.get_best_action(site_features)
    
    # Store AI prediction in opportunity for logging
    opportunity['ai_recommended_action_type'] = best_action
    opportunity['ai_probability'] = best_prob
    opportunity['ai_probabilities'] = probabilities
    opportunity['shadow_mode'] = True  # Flag for worker
    
    logger.info(
        f"Shadow mode: AI predicts {best_action} ({best_prob:.2%}), "
        f"but executing {task_type} (rule-based)"
    )
    
    return opportunity
```

### 2. Worker - Log Prediction and Result

**File:** `python/worker.py`

```python
# Before execution - log AI prediction
with automation_class(api_client, proxy=proxy, headless=True) as automation:
    result = automation.execute(task)
    
    # Capture opportunity for shadow mode logging
    if hasattr(automation, 'last_opportunity'):
        opportunity = automation.last_opportunity
        if opportunity and opportunity.get('shadow_mode'):
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

# After execution - log result
if result.get('success'):
    shadow_logger.log_result(
        task_id=task_id,
        rule_based_action=task_type,
        task_result='success',
        execution_time=execution_time,
        retry_count=retry_count,
        ai_prediction=ai_prediction
    )
else:
    shadow_logger.log_result(
        task_id=task_id,
        rule_based_action=task_type,
        task_result='failed',
        execution_time=execution_time,
        retry_count=retry_count,
        ai_prediction=ai_prediction
    )
```

### 3. Automation Classes - Store Opportunity

**File:** `python/automation/base.py`

```python
def __init__(self, api_client, proxy: Optional[Dict] = None, headless: bool = True):
    # ... existing code ...
    
    # Initialize with shadow mode if enabled
    shadow_mode = os.getenv('SHADOW_MODE', 'false').lower() in ('true', '1', 'yes')
    self.opportunity_selector = OpportunitySelector(
        api_client, 
        shadow_mode=shadow_mode
    ) if OpportunitySelector else None
    self.last_opportunity = None  # Store for shadow mode logging
```

**File:** `python/automation/comment.py` (and others)

```python
if self.opportunity_selector and campaign_id:
    opportunity = self.opportunity_selector.select_opportunity(
        campaign_id=campaign_id,
        task_type='comment'
    )
    if opportunity:
        target_url = opportunity.get('url')
        # Store opportunity for shadow mode logging
        self.last_opportunity = opportunity
```

## Fallback Logic

### Shadow Mode Flow

```python
# In OpportunitySelector.select_opportunity()
if self.shadow_mode and self.ai_engine:
    # Shadow mode: AI predicts, rules execute
    return self._select_with_shadow_mode(campaign_id, task_type, site_type)
elif self.use_ai and self.ai_engine:
    # Normal AI mode: Use AI recommendation
    return self._select_with_ai_engine(campaign_id, task_type, site_type)
else:
    # Rules-based fallback
    return self._select_with_rules(campaign_id, task_type, site_type)
```

### Error Handling

```python
try:
    # Get AI prediction
    probabilities = self.ai_engine.predict(site_features)
except Exception as e:
    logger.warning(f"Shadow mode AI prediction failed: {e}")
    # Still return opportunity with default values
    opportunity['ai_recommended_action_type'] = task_type
    opportunity['ai_probability'] = 0.5
```

## Logging Structure

### Prediction Log (Before Execution)

```python
shadow_logger.log_prediction(
    task_id=123,
    campaign_id=1,
    backlink={
        'id': 456,
        'url': 'https://example.com',
        'pa': 45,
        'da': 60,
        'site_type': 'comment',
    },
    rule_based_action='comment',  # What will be executed
    ai_prediction={
        'action': 'profile',  # What AI predicts
        'probability': 0.67,
        'probabilities': {
            'comment': 0.15,
            'profile': 0.67,
            'forum': 0.12,
            'guest': 0.06
        }
    }
)
```

### Result Log (After Execution)

```python
shadow_logger.log_result(
    task_id=123,
    rule_based_action='comment',  # What was executed
    task_result='success',  # success, failed, or error
    execution_time=12.45,
    retry_count=0,
    ai_prediction={
        'action': 'profile',
        'probability': 0.67,
        'probabilities': {...}
    }
)
```

## Complete Example

```python
# Enable shadow mode
import os
os.environ['SHADOW_MODE'] = 'true'

# Worker processes task
task = {
    'id': 123,
    'type': 'comment',  # Rule-based will use this
    'campaign_id': 1,
}

# OpportunitySelector runs in shadow mode
selector = OpportunitySelector(api_client, shadow_mode=True)
opportunity = selector.select_opportunity(
    campaign_id=1,
    task_type='comment'
)

# AI predicted 'profile' but 'comment' will be executed
# opportunity['ai_recommended_action_type'] = 'profile'
# opportunity['shadow_mode'] = True

# Automation executes with 'comment' (rule-based)
# Result: success or failed

# Logs show:
# - AI predicted: profile (67% confidence)
# - Rule-based executed: comment
# - Result: success
# - AI correct: False (different actions)
# - AI would have succeeded: Unknown (different action)
```

## Analysis Example

```python
from shadow_mode_logger import ShadowModeLogger

logger = ShadowModeLogger()
stats = logger.get_accuracy_stats()

print(f"Total tasks: {stats['total_tasks']}")
print(f"AI correct: {stats['ai_correct_count']} ({stats['ai_correct_rate']:.2%})")
print(f"AI different: {stats['ai_different_count']} ({stats['ai_different_rate']:.2%})")

# Analyze when AI was different
if stats['ai_different_when_rule_failed'] > 0:
    print(f"\nAI predicted different action when rule-based failed: "
          f"{stats['ai_different_when_rule_failed']} times")
    print("This suggests AI might have been better!")
```

## Configuration

### Enable Shadow Mode

```bash
# Environment variable
export SHADOW_MODE=true

# Or in code
import os
os.environ['SHADOW_MODE'] = 'true'
```

### Log Format

```bash
export SHADOW_MODE_LOG_FORMAT=json  # or csv
export SHADOW_MODE_LOG_DIR=logs
```

## Key Points

- ✅ **No automation changes**: Playwright classes unchanged
- ✅ **AI predicts**: Gets prediction for logging
- ✅ **Rules execute**: Original task_type always used
- ✅ **Comprehensive logging**: Prediction + result logged
- ✅ **Safe validation**: No risk to production

