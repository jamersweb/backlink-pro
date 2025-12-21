# AI Decision Engine Integration Guide

## Overview

The AI Decision Engine is integrated into the `OpportunitySelector` flow to intelligently select the best action type for each backlink opportunity.

## Integration Flow

```
OpportunitySelector → AI Decision Engine → Action Selected → Existing Automation Class
```

### Step-by-Step Flow

1. **Worker receives task** with `task_type` (comment, profile, forum, guest)
2. **OpportunitySelector** gets opportunities from API
3. **AI Decision Engine** evaluates each opportunity and predicts best action type
4. **Action type selected** (AI recommendation or fallback to task_type)
5. **Existing Automation Class** executes (no changes to automation logic)

## Integration Code

### OpportunitySelector Integration

The `OpportunitySelector` class now includes:

```python
from opportunity_selector import OpportunitySelector
from api_client import LaravelAPIClient

# Initialize with AI enabled
api_client = LaravelAPIClient(api_url, api_token)
selector = OpportunitySelector(api_client, use_ai=True)

# Select opportunity - AI will recommend action type
opportunity = selector.select_opportunity(
    campaign_id=1,
    task_type='comment',  # Can be overridden by AI
    use_ai_recommendation=True
)

# Opportunity now includes AI recommendations:
# - opportunity['ai_recommended_action_type']: Best action (e.g., 'profile')
# - opportunity['ai_probability']: Confidence score (e.g., 0.67)
# - opportunity['ai_probabilities']: All probabilities
#   {
#       'comment': 0.15,
#       'profile': 0.67,
#       'forum': 0.12,
#       'guest': 0.06
#   }
```

### Worker Integration

In `worker.py`, the AI-recommended action type can be used:

```python
def process_task(api_client: LaravelAPIClient, task: dict):
    task_id = task['id']
    original_task_type = task['type']  # Original task type
    
    # Get opportunity with AI recommendation
    from opportunity_selector import OpportunitySelector
    selector = OpportunitySelector(api_client, use_ai=True)
    
    opportunity = selector.select_opportunity(
        campaign_id=task['campaign_id'],
        task_type=original_task_type,
        use_ai_recommendation=True
    )
    
    if opportunity:
        # Use AI-recommended action type if available
        ai_recommended = opportunity.get('ai_recommended_action_type')
        if ai_recommended and ai_recommended != original_task_type:
            logger.info(
                f"AI recommends {ai_recommended} instead of {original_task_type} "
                f"(probability: {opportunity.get('ai_probability', 0):.2%})"
            )
            # Optionally override task_type
            # task_type = ai_recommended
    
    # Continue with existing automation flow
    automation_class = get_automation_class(task_type)
    # ... rest of existing code
```

## Fallback Logic

The integration includes three-tier fallback:

### Tier 1: AI Decision Engine (Preferred)

```python
if AI_ENGINE_AVAILABLE:
    try:
        # Use AI Decision Engine
        probabilities = ai_engine.predict(site_features)
        best_action = max(probabilities.items(), key=lambda x: x[1])[0]
        return opportunity_with_ai_recommendation
    except Exception:
        # Fall to Tier 2
```

### Tier 2: DecisionService (Fallback)

```python
elif DECISION_SERVICE_AVAILABLE:
    try:
        # Use DecisionService (older ML predictor)
        return decision_service.select_best_opportunity(...)
    except Exception:
        # Fall to Tier 3
```

### Tier 3: Rules-Based (Final Fallback)

```python
else:
    # Rules-based selection
    # - Use task_type as specified
    # - Select opportunity matching site_type
    # - Default probabilities
    return opportunity_with_default_action
```

## Fallback Rules

When AI is unavailable, the system uses these rules:

1. **Action Type**: Use the `task_type` from the task
2. **Site Type Matching**: Prefer opportunities matching the site type
3. **PA/DA Ranking**: Select opportunities with higher PA+DA
4. **Default Probabilities**: Assign equal probabilities (0.25 each)

## Usage Examples

### Example 1: Basic Usage

```python
from opportunity_selector import OpportunitySelector
from api_client import LaravelAPIClient

api_client = LaravelAPIClient(api_url, api_token)
selector = OpportunitySelector(api_client, use_ai=True)

opportunity = selector.select_opportunity(
    campaign_id=1,
    task_type='comment'
)

if opportunity:
    recommended_action = opportunity['ai_recommended_action_type']
    probability = opportunity['ai_probability']
    print(f"AI recommends: {recommended_action} ({probability:.2%})")
```

### Example 2: Get Action Type Explicitly

```python
# Get both opportunity and recommended action
result = selector.select_opportunity_with_action(
    campaign_id=1,
    task_type='comment'
)

if result:
    opportunity, recommended_action = result
    print(f"Opportunity: {opportunity['id']}")
    print(f"Recommended action: {recommended_action}")
```

### Example 3: Override Task Type Based on AI

```python
# In worker.py or automation class
opportunity = selector.select_opportunity(campaign_id, task_type='comment')

if opportunity:
    ai_action = opportunity.get('ai_recommended_action_type')
    ai_prob = opportunity.get('ai_probability', 0)
    
    # Override if AI confidence is high (>60%)
    if ai_prob > 0.6 and ai_action != task_type:
        logger.info(f"Overriding task_type {task_type} with AI recommendation {ai_action}")
        task_type = ai_action
```

## Error Handling

The integration handles errors gracefully:

```python
try:
    opportunity = selector.select_opportunity(campaign_id, task_type='comment')
except Exception as e:
    logger.error(f"Opportunity selection failed: {e}")
    # Fallback to basic selection
    opportunity = selector._select_with_rules(campaign_id, task_type, None)
```

## Configuration

### Enable/Disable AI

```python
# Enable AI (default)
selector = OpportunitySelector(api_client, use_ai=True)

# Disable AI (rules-based only)
selector = OpportunitySelector(api_client, use_ai=False)
```

### Environment Variables

No additional environment variables needed. The AI engine automatically:
- Loads model from `ml/export_model.pkl`
- Falls back to rules if model not found
- Logs warnings if AI unavailable

## Integration Points

### 1. OpportunitySelector (`opportunity_selector.py`)

**Modified Methods:**
- `__init__()`: Initializes AI Decision Engine
- `select_opportunity()`: Uses AI to recommend action type
- `_select_with_ai_engine()`: AI-based selection
- `_select_with_rules()`: Rules-based fallback

**New Methods:**
- `select_opportunity_with_action()`: Returns (opportunity, action_type) tuple

### 2. Automation Classes (No Changes)

The automation classes (`comment.py`, `profile.py`, etc.) remain unchanged:
- They still call `select_opportunity()`
- They receive opportunities with AI recommendations
- They can optionally use `ai_recommended_action_type`

### 3. Worker (`worker.py`)

**Optional Enhancement:**
- Can use `ai_recommended_action_type` to override `task_type`
- Can log AI recommendations for analysis
- Can use AI probabilities for decision making

## Testing

### Test AI Integration

```python
from opportunity_selector import OpportunitySelector
from api_client import LaravelAPIClient

api_client = LaravelAPIClient(api_url, api_token)
selector = OpportunitySelector(api_client, use_ai=True)

# Test selection
opportunity = selector.select_opportunity(campaign_id=1, task_type='comment')

assert 'ai_recommended_action_type' in opportunity
assert 'ai_probability' in opportunity
assert 'ai_probabilities' in opportunity

print(f"AI recommended: {opportunity['ai_recommended_action_type']}")
print(f"Confidence: {opportunity['ai_probability']:.2%}")
```

### Test Fallback

```python
# Disable AI to test fallback
selector = OpportunitySelector(api_client, use_ai=False)
opportunity = selector.select_opportunity(campaign_id=1, task_type='comment')

# Should still work with rules-based selection
assert opportunity is not None
assert opportunity['ai_recommended_action_type'] == 'comment'  # Default
```

## Monitoring

### Log Messages

The integration logs:
- `"AI Decision Engine enabled"` - AI successfully initialized
- `"AI selected opportunity X: action (probability)"` - AI recommendation
- `"Rules-based selection: opportunity X, action: Y"` - Fallback used
- `"AI Decision Engine error: ..."` - AI failed, using fallback

### Metrics to Track

- AI recommendation rate (vs rules-based)
- Action type override rate (AI vs original task_type)
- AI confidence scores distribution
- Fallback usage frequency

## Notes

- **No Automation Changes**: Playwright automation classes are unchanged
- **Backward Compatible**: Works with or without AI
- **Fast Fallback**: Rules-based selection is instant if AI fails
- **Optional Override**: Worker can choose to use AI recommendation or not

