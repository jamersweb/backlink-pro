# AI Decision Engine

Fast runtime inference engine for backlink action prediction.

## Overview

The AI Decision Engine provides fast, pure ML inference for predicting the best backlink action type. It:
- ✅ Loads trained model
- ✅ Accepts site feature dictionary
- ✅ Returns ranked probabilities per action
- ✅ No browser interaction
- ✅ No automation logic
- ✅ Fast inference only

## Quick Start

### Basic Usage

```python
from ai_decision_engine import AIDecisionEngine

# Initialize engine
engine = AIDecisionEngine()

# Site features
site_features = {
    'pa': 45,
    'da': 60,
    'site_type': 'comment',
}

# Get predictions
probabilities = engine.predict(site_features)
# Returns: {'comment': 0.15, 'profile': 0.67, 'forum': 0.12, 'guest': 0.06}
```

### Convenience Function

```python
from ai_decision_engine import predict_action

probabilities = predict_action({
    'pa': 45,
    'da': 60,
    'site_type': 'comment',
})
```

## Input Features

### Required Features

- `pa` or `page_authority`: Page Authority score (0-100)
- `da` or `domain_authority`: Domain Authority score (0-100)
- `site_type`: Site type (`comment`, `profile`, `forum`, `guest`)

### Optional Features

- `backlink_success_rate`: Historical success rate for this backlink (0-1)
- `backlink_total_attempts`: Total attempts on this backlink
- `action_type_success_rate`: Historical success rate for action type (0-1)
- `action_type_total_attempts`: Total attempts for action type
- `campaign_daily_limit`: Campaign daily limit
- `campaign_total_limit`: Campaign total limit
- `timestamp`: Current timestamp (for time-based features)

## Output Format

Returns a dictionary with probabilities for each action type:

```python
{
    "comment": 0.15,
    "profile": 0.67,
    "forum": 0.12,
    "guest": 0.06
}
```

Probabilities are normalized (sum to 1.0) and sorted by value.

## API Methods

### `predict(site_features: Dict) -> Dict[str, float]`

Get probabilities for all action types.

```python
probabilities = engine.predict(site_features)
```

### `predict_ranked(site_features: Dict) -> List[tuple]`

Get ranked list of (action_type, probability) tuples.

```python
ranked = engine.predict_ranked(site_features)
# Returns: [('profile', 0.67), ('comment', 0.15), ('forum', 0.12), ('guest', 0.06)]
```

### `get_best_action(site_features: Dict) -> tuple`

Get the best action type and its probability.

```python
best_action, best_prob = engine.get_best_action(site_features)
# Returns: ('profile', 0.67)
```

## Examples

### Example 1: Basic Inference

```python
from ai_decision_engine import AIDecisionEngine

engine = AIDecisionEngine()

site_features = {
    'pa': 45,
    'da': 60,
    'site_type': 'comment',
}

probabilities = engine.predict(site_features)
print(probabilities)
# {'comment': 0.15, 'profile': 0.67, 'forum': 0.12, 'guest': 0.06}
```

### Example 2: With Historical Data

```python
site_features = {
    'pa': 55,
    'da': 70,
    'site_type': 'profile',
    'backlink_success_rate': 0.85,
    'backlink_total_attempts': 20,
}

probabilities = engine.predict(site_features)
best_action, best_prob = engine.get_best_action(site_features)
print(f"Best: {best_action} ({best_prob:.2%})")
```

### Example 3: Multiple Sites

```python
sites = [
    {'pa': 30, 'da': 40, 'site_type': 'comment'},
    {'pa': 50, 'da': 60, 'site_type': 'profile'},
    {'pa': 70, 'da': 80, 'site_type': 'forum'},
]

for site in sites:
    best_action, best_prob = engine.get_best_action(site)
    print(f"PA={site['pa']}, DA={site['da']}: {best_action} ({best_prob:.2%})")
```

## Integration

### With Decision Service

```python
from ai_decision_engine import AIDecisionEngine
from decision_service import DecisionService

# Use AI engine in decision service
engine = AIDecisionEngine()

def decide_action(backlink_dict):
    probabilities = engine.predict(backlink_dict)
    best_action = max(probabilities.items(), key=lambda x: x[1])[0]
    return best_action, probabilities[best_action]
```

### With Opportunity Selector

```python
from ai_decision_engine import get_engine

engine = get_engine()  # Singleton instance

def select_best_opportunity(opportunities):
    scored = []
    for opp in opportunities:
        probs = engine.predict(opp)
        best_action, best_prob = engine.get_best_action(opp)
        scored.append({
            'opportunity': opp,
            'action': best_action,
            'probability': best_prob,
        })
    
    # Sort by probability
    scored.sort(key=lambda x: x['probability'], reverse=True)
    return scored[0]
```

## Model Requirements

The engine expects a trained model at:
- `ml/export_model.pkl` (primary location)
- `ml/models/export_model.pkl` (fallback)

Train the model first:
```bash
python ml/train_action_model.py
```

## Performance

- **Inference time**: <10ms per prediction
- **Memory**: ~50-100 MB (model size)
- **Thread-safe**: Yes (read-only operations)

## Error Handling

```python
try:
    engine = AIDecisionEngine()
    probabilities = engine.predict(site_features)
except FileNotFoundError:
    print("Model not found. Train model first.")
except Exception as e:
    print(f"Error: {e}")
```

## Testing

Run example inference:
```bash
python example_inference.py
```

Or test directly:
```bash
python ai_decision_engine.py
```

## Notes

- Model is loaded once at initialization (lazy loading)
- Features are automatically transformed to match training format
- Missing features default to 0.0
- Probabilities are always normalized (sum to 1.0)
- No external dependencies beyond model file

