# Model Registry

## Overview

The Model Registry tracks model versions, datasets, and schemas for versioning, reproducibility, and rollback capabilities.

## Registry Structure

### Model Version

Each model version contains:

```json
{
  "version": "v1.2.3",
  "model_id": "action_predictor_20240115_143022",
  "created_at": "2024-01-15T14:30:22Z",
  "model_path": "ml/models/v1.2.3/export_model.pkl",
  "model_type": "xgboost",
  "dataset_hash": "a1b2c3d4e5f6...",
  "dataset_version": "dataset_v1.0",
  "schema_version": "schema_v1.0",
  "feature_names": ["pa", "da", "site_type_comment", ...],
  "num_features": 42,
  "action_classes": ["comment", "profile", "forum", "guest"],
  "training_metrics": {
    "train_accuracy": 0.85,
    "val_accuracy": 0.82,
    "test_accuracy": 0.81,
    "precision_per_class": {
      "comment": 0.88,
      "profile": 0.79,
      "forum": 0.83,
      "guest": 0.75
    }
  },
  "deployment_status": "production",
  "canary_percentage": 0.0,
  "rollback_available": true,
  "previous_version": "v1.2.2"
}
```

## Versioning Scheme

### Semantic Versioning

Format: `v{MAJOR}.{MINOR}.{PATCH}`

- **MAJOR**: Breaking changes (schema changes, feature set changes)
- **MINOR**: New features, improvements (new features added)
- **PATCH**: Bug fixes, minor improvements (same features, better performance)

### Version Examples

- `v1.0.0` - Initial model
- `v1.1.0` - Added new features (e.g., platform_guess)
- `v1.1.1` - Bug fix, same features
- `v2.0.0` - Breaking change (e.g., removed features, schema change)

## Dataset Hash

### Purpose

Track which dataset was used to train each model for reproducibility.

### Calculation

```python
import hashlib
import json

def calculate_dataset_hash(dataset_path: str) -> str:
    """Calculate SHA256 hash of dataset"""
    with open(dataset_path, 'rb') as f:
        content = f.read()
    return hashlib.sha256(content).hexdigest()
```

### Usage

- Store dataset hash with model version
- Verify dataset hasn't changed before retraining
- Track dataset lineage

## Schema Version

### Purpose

Track feature schema changes to ensure compatibility.

### Schema Definition

```json
{
  "schema_version": "schema_v1.0",
  "features": {
    "pa": {"type": "float", "range": [0, 100], "required": true},
    "da": {"type": "float", "range": [0, 100], "required": true},
    "site_type_comment": {"type": "int", "values": [0, 1], "required": true},
    "comment_supported": {"type": "int", "values": [0, 1], "required": false},
    ...
  },
  "target": {
    "type": "categorical",
    "classes": ["comment", "profile", "forum", "guest"],
    "required": true
  }
}
```

### Schema Versioning

- `schema_v1.0` - Initial schema
- `schema_v1.1` - Added optional features (backward compatible)
- `schema_v2.0` - Breaking changes (removed features, changed types)

### Compatibility Rules

- **Backward Compatible**: New optional features added
- **Breaking Change**: Features removed, types changed, required features added

## Registry Storage

### File Structure

```
mlops/
├── registry/
│   ├── models/
│   │   ├── v1.0.0/
│   │   │   ├── model.json
│   │   │   └── export_model.pkl
│   │   ├── v1.1.0/
│   │   │   ├── model.json
│   │   │   └── export_model.pkl
│   │   └── current -> v1.1.0/
│   ├── datasets/
│   │   ├── dataset_v1.0.json
│   │   └── dataset_v1.1.json
│   └── schemas/
│       ├── schema_v1.0.json
│       └── schema_v1.1.json
```

### Model Metadata File

`mlops/registry/models/{version}/model.json`:

```json
{
  "version": "v1.2.3",
  "model_id": "action_predictor_20240115_143022",
  "created_at": "2024-01-15T14:30:22Z",
  "model_path": "ml/models/v1.2.3/export_model.pkl",
  "model_type": "xgboost",
  "dataset_hash": "a1b2c3d4e5f6...",
  "dataset_version": "dataset_v1.0",
  "schema_version": "schema_v1.0",
  "feature_names": [...],
  "num_features": 42,
  "action_classes": ["comment", "profile", "forum", "guest"],
  "training_metrics": {...},
  "deployment_status": "production",
  "canary_percentage": 0.0,
  "rollback_available": true,
  "previous_version": "v1.2.2"
}
```

## Registry Operations

### Register Model

```python
from mlops.model_registry import ModelRegistry

registry = ModelRegistry()
version = registry.register_model(
    model_path="ml/models/export_model.pkl",
    dataset_path="ml/datasets/training_backlinks_enriched.csv",
    schema_version="schema_v1.0",
    training_metrics={...}
)
```

### Get Model Version

```python
# Get current production model
model_info = registry.get_model("production")

# Get specific version
model_info = registry.get_model("v1.2.3")

# Get latest version
model_info = registry.get_latest_model()
```

### List Models

```python
# List all models
models = registry.list_models()

# List models by status
production_models = registry.list_models(status="production")
canary_models = registry.list_models(status="canary")
```

### Rollback

```python
# Rollback to previous version
registry.rollback_to_version("v1.2.2")

# Rollback to last known good version
registry.rollback_to_last_good()
```

## Deployment Status

### Status Values

- **staging** - Model in staging/testing
- **canary** - Model in canary deployment (partial traffic)
- **production** - Model in full production
- **deprecated** - Model no longer in use
- **archived** - Model archived for historical reference

### Status Transitions

```
staging → canary → production
         ↓
      deprecated
         ↓
      archived
```

## Canary Deployment

### Configuration

```json
{
  "canary_percentage": 0.1,
  "canary_started_at": "2024-01-15T14:30:22Z",
  "canary_metrics": {
    "requests": 1000,
    "success_rate": 0.85,
    "avg_response_time": 0.05
  },
  "canary_threshold": {
    "min_success_rate": 0.80,
    "max_response_time": 0.10
  }
}
```

### Canary Rules

- Start with 10% traffic
- Monitor success rate and response time
- Promote to 100% if metrics meet thresholds
- Rollback if metrics drop below thresholds

## Rollback Strategy

### Automatic Rollback Triggers

1. **Success Rate Drop**: If success rate drops below threshold
2. **Error Rate Increase**: If error rate exceeds threshold
3. **Response Time Increase**: If response time exceeds threshold
4. **Manual Rollback**: Admin-initiated rollback

### Rollback Process

1. Detect degradation (monitoring alerts)
2. Verify rollback is available
3. Switch traffic to previous version
4. Update registry status
5. Notify team
6. Investigate root cause

## Best Practices

1. **Always version models** - Never overwrite production models
2. **Track dataset hash** - Ensure reproducibility
3. **Schema versioning** - Track feature changes
4. **Test before production** - Use staging/canary
5. **Monitor metrics** - Track success rates, errors
6. **Keep rollback ready** - Maintain previous versions
7. **Document changes** - Log all model updates

## Integration

### With Training Pipeline

```python
# After training
model_version = registry.register_model(
    model_path="ml/models/export_model.pkl",
    dataset_path="ml/datasets/training_backlinks_enriched.csv",
    schema_version="schema_v1.0",
    training_metrics=metrics
)

# Deploy to staging
registry.deploy_model(model_version, status="staging")
```

### With Runtime

```python
# Load model from registry
model_info = registry.get_model("production")
engine = AIDecisionEngine(model_path=model_info["model_path"])
```

### With Monitoring

```python
# Track model performance
registry.update_model_metrics(
    version="v1.2.3",
    metrics={
        "success_rate": 0.85,
        "error_rate": 0.02,
        "avg_response_time": 0.05
    }
)

# Check if rollback needed
if registry.should_rollback("v1.2.3"):
    registry.rollback_to_previous("v1.2.3")
```

