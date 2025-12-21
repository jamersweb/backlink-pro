# AI Decision Engine Integration - Code Snippets

## Integration Overview

The AI Decision Engine is integrated into `OpportunitySelector` with three-tier fallback:
1. **AI Decision Engine** (preferred)
2. **DecisionService** (fallback)
3. **Rules-based** (final fallback)

## Key Integration Points

### 1. OpportunitySelector Initialization

**File:** `python/opportunity_selector.py`

```python
def __init__(self, api_client: LaravelAPIClient, use_ai: bool = True):
    self.api_client = api_client
    self.ai_engine = None
    self.decision_service = None
    self.use_ai = False
    
    # Try AI Decision Engine first
    if use_ai and AI_ENGINE_AVAILABLE:
        try:
            self.ai_engine = get_engine()
            self.use_ai = True
            logger.info("AI Decision Engine enabled")
        except Exception as e:
            logger.warning(f"Failed to initialize AI Decision Engine: {e}")
            # Fallback to DecisionService
            if DECISION_SERVICE_AVAILABLE:
                try:
                    self.decision_service = DecisionService(api_client)
                    self.use_ai = True
                except Exception:
                    pass
    
    # Final fallback: rules-based (always available)
    if not self.use_ai:
        logger.info("Using rules-based selection")
```

### 2. AI-Powered Selection

**File:** `python/opportunity_selector.py`

```python
def _select_with_ai_engine(self, campaign_id: int, task_type: str, 
                           site_type: Optional[str]) -> Optional[Dict]:
    """Select opportunity using AI Decision Engine"""
    
    # Get opportunities
    opportunities = self.api_client.get_opportunities_for_campaign(
        campaign_id=campaign_id,
        count=10,  # Get multiple for AI to rank
        task_type=None,  # Don't filter - let AI decide
        site_type=site_type
    )
    
    # Score each with AI
    scored_opportunities = []
    for opp in opportunities:
        site_features = {
            'pa': opp.get('pa', 0),
            'da': opp.get('da', 0),
            'site_type': opp.get('site_type', 'comment'),
        }
        
        # AI prediction
        probabilities = self.ai_engine.predict(site_features)
        best_action, best_prob = self.ai_engine.get_best_action(site_features)
        
        scored_opportunities.append({
            'opportunity': opp,
            'ai_recommended_action_type': best_action,
            'ai_probability': best_prob,
            'ai_probabilities': probabilities,
        })
    
    # Sort by probability and return best
    scored_opportunities.sort(key=lambda x: x['ai_probability'], reverse=True)
    
    if scored_opportunities:
        best = scored_opportunities[0]
        opp = best['opportunity']
        opp['ai_recommended_action_type'] = best['ai_recommended_action_type']
        opp['ai_probability'] = best['ai_probability']
        opp['ai_probabilities'] = best['ai_probabilities']
        return opp
    
    return None
```

### 3. Fallback Logic

**File:** `python/opportunity_selector.py`

```python
def select_opportunity(self, campaign_id: int, task_type: str = 'comment',
                      site_type: Optional[str] = None,
                      use_ai_recommendation: bool = True) -> Optional[Dict]:
    """Select opportunity with AI recommendation"""
    
    # Tier 1: AI Decision Engine
    if self.use_ai and use_ai_recommendation and self.ai_engine:
        try:
            return self._select_with_ai_engine(campaign_id, task_type, site_type)
        except Exception as e:
            logger.warning(f"AI Engine error: {e}, falling back")
    
    # Tier 2: DecisionService
    if self.use_ai and use_ai_recommendation and self.decision_service:
        try:
            return self._select_with_decision_service(campaign_id, task_type, site_type)
        except Exception as e:
            logger.warning(f"DecisionService error: {e}, falling back")
    
    # Tier 3: Rules-based (always works)
    return self._select_with_rules(campaign_id, task_type, site_type)
```

### 4. Rules-Based Fallback

**File:** `python/opportunity_selector.py`

```python
def _select_with_rules(self, campaign_id: int, task_type: str,
                      site_type: Optional[str]) -> Optional[Dict]:
    """Rules-based selection (fallback)"""
    
    opportunities = self.api_client.get_opportunities_for_campaign(
        campaign_id=campaign_id,
        count=1,
        task_type=task_type,  # Use specified task_type
        site_type=site_type
    )
    
    if not opportunities:
        return None
    
    opp = opportunities[0]
    
    # Add default AI fields for consistency
    opp['ai_recommended_action_type'] = task_type or 'comment'
    opp['ai_probability'] = 0.5
    opp['ai_probabilities'] = {
        'comment': 0.25 if task_type != 'comment' else 0.5,
        'profile': 0.25 if task_type != 'profile' else 0.5,
        'forum': 0.25 if task_type != 'forum' else 0.5,
        'guest': 0.25 if task_type != 'guest' else 0.5,
    }
    
    return opp
```

## Usage in Automation Classes

### No Changes Required

Automation classes work as before:

**File:** `python/automation/comment.py`

```python
def execute(self, task: Dict) -> Dict:
    # ... existing code ...
    
    if self.opportunity_selector and campaign_id:
        opportunity = self.opportunity_selector.select_opportunity(
            campaign_id=campaign_id,
            task_type='comment'  # Can be overridden by AI
        )
        
        if opportunity:
            target_url = opportunity.get('url')
            # AI recommendation available in:
            # - opportunity['ai_recommended_action_type']
            # - opportunity['ai_probability']
            # - opportunity['ai_probabilities']
    
    # ... rest of existing code unchanged ...
```

## Optional Worker Enhancement

### Use AI Recommendation to Override Task Type

**File:** `python/worker.py` (optional enhancement)

```python
def process_task(api_client: LaravelAPIClient, task: dict):
    task_id = task['id']
    original_task_type = task['type']
    
    # Get opportunity with AI recommendation
    from opportunity_selector import OpportunitySelector
    selector = OpportunitySelector(api_client, use_ai=True)
    
    opportunity = selector.select_opportunity(
        campaign_id=task['campaign_id'],
        task_type=original_task_type,
        use_ai_recommendation=True
    )
    
    # Optionally override task_type based on AI
    if opportunity:
        ai_action = opportunity.get('ai_recommended_action_type')
        ai_prob = opportunity.get('ai_probability', 0)
        
        # Override if AI confidence > 60%
        if ai_prob > 0.6 and ai_action != original_task_type:
            logger.info(
                f"AI override: {original_task_type} → {ai_action} "
                f"(confidence: {ai_prob:.2%})"
            )
            task_type = ai_action
        else:
            task_type = original_task_type
    else:
        task_type = original_task_type
    
    # Continue with existing automation flow
    automation_class = get_automation_class(task_type)
    # ... rest unchanged ...
```

## Complete Integration Example

```python
from opportunity_selector import OpportunitySelector
from api_client import LaravelAPIClient

# Initialize
api_client = LaravelAPIClient(api_url, api_token)
selector = OpportunitySelector(api_client, use_ai=True)

# Select opportunity - AI will recommend action type
opportunity = selector.select_opportunity(
    campaign_id=1,
    task_type='comment',  # Default, can be overridden
    use_ai_recommendation=True
)

if opportunity:
    # Get AI recommendations
    recommended_action = opportunity['ai_recommended_action_type']
    confidence = opportunity['ai_probability']
    all_probs = opportunity['ai_probabilities']
    
    print(f"Opportunity: {opportunity['id']}")
    print(f"URL: {opportunity['url']}")
    print(f"AI Recommendation: {recommended_action} ({confidence:.2%})")
    print(f"All Probabilities: {all_probs}")
    
    # Use recommended action or original task_type
    action_to_use = recommended_action if confidence > 0.6 else 'comment'
    
    # Pass to automation class
    # (automation classes unchanged, they receive opportunity with AI data)
```

## Fallback Behavior

### Scenario 1: AI Engine Available

```
OpportunitySelector.select_opportunity()
  → _select_with_ai_engine()
    → AI Decision Engine.predict()
    → Returns opportunity with AI recommendations
```

### Scenario 2: AI Engine Fails

```
OpportunitySelector.select_opportunity()
  → _select_with_ai_engine() [FAILS]
  → _select_with_decision_service() [if available]
    → OR
  → _select_with_rules() [always works]
    → Returns opportunity with default action type
```

### Scenario 3: AI Disabled

```
OpportunitySelector(use_ai=False)
  → select_opportunity()
    → _select_with_rules()
    → Returns opportunity with task_type as action
```

## Error Handling

All methods include try-except blocks:

```python
try:
    # AI selection
    return self._select_with_ai_engine(...)
except Exception as e:
    logger.warning(f"AI error: {e}, using fallback")
    # Automatically falls back to next tier
```

## Testing Integration

```python
# Test AI integration
selector = OpportunitySelector(api_client, use_ai=True)
opportunity = selector.select_opportunity(campaign_id=1, task_type='comment')

assert 'ai_recommended_action_type' in opportunity
assert 'ai_probability' in opportunity
assert opportunity['ai_recommended_action_type'] in ['comment', 'profile', 'forum', 'guest']

# Test fallback
selector_no_ai = OpportunitySelector(api_client, use_ai=False)
opportunity = selector_no_ai.select_opportunity(campaign_id=1, task_type='comment')

assert opportunity['ai_recommended_action_type'] == 'comment'  # Default
```

## Summary

- ✅ **No automation changes**: Playwright classes unchanged
- ✅ **AI selects action**: Returns recommended action type
- ✅ **Three-tier fallback**: AI → DecisionService → Rules
- ✅ **Backward compatible**: Works with or without AI
- ✅ **Fast fallback**: Rules-based is instant

