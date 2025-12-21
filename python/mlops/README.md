# MLOps System

## Overview

MLOps system for model versioning, retraining workflows, canary deployment, and rollback capabilities.

## Components

### 1. Model Registry (`model_registry.md`)

- **Versioning**: Semantic versioning (v{MAJOR}.{MINOR}.{PATCH})
- **Dataset Hash**: SHA256 hash of training dataset
- **Schema Version**: Feature schema versioning
- **Deployment Status**: staging, canary, production, deprecated, archived
- **Rollback**: Automatic and manual rollback capabilities

### 2. Retrain Workflow (`retrain_workflow.md`)

- **Data Collection**: Append new run outcomes to dataset store
- **Weekly Retrain**: Automated weekly retraining job
- **Canary Rollout**: Gradual rollout with monitoring
- **Rollback**: Automatic rollback on degradation

### 3. Retrain Job (`retrain_job.py`)

Skeleton implementation of weekly retrain job.

**Usage:**
```bash
# Run retrain job
python mlops/retrain_job.py

# With config
python mlops/retrain_job.py --config mlops/config.json

# Dry run
python mlops/retrain_job.py --dry-run
```

**Schedule:**
```bash
# Cron job (Sunday 2 AM)
0 2 * * 0 cd /path/to/backlink-pro/python && python mlops/retrain_job.py
```

## Workflow

### Weekly Retrain Process

1. **Collect New Data** - Append outcomes from automation runs
2. **Merge Datasets** - Merge new data with existing training data
3. **Extract Features** - Run feature extraction on new URLs
4. **Prepare Dataset** - Normalize, encode, split
5. **Train Model** - Train new model
6. **Evaluate Model** - Evaluate on test set
7. **Register Model** - Register in model registry
8. **Validate Model** - Compare with production model
9. **Deploy** - Deploy to staging/canary/production

### Canary Rollout

1. **Deploy to Canary** - 10% traffic
2. **Monitor Metrics** - Success rate, error rate, response time
3. **Promote or Rollback** - Based on metrics

### Rollback

1. **Detect Issue** - Monitoring alerts
2. **Verify Rollback** - Check previous version available
3. **Switch Traffic** - Update model registry
4. **Notify Team** - Send alerts
5. **Investigate** - Root cause analysis

## Configuration

### Retrain Config

```json
{
  "retrain": {
    "schedule": "weekly",
    "day": "sunday",
    "time": "02:00",
    "min_new_samples": 100,
    "merge_strategy": "append",
    "validation_threshold": 0.80
  },
  "canary": {
    "enabled": true,
    "percentage": 0.1,
    "duration_hours": 24,
    "thresholds": {
      "min_success_rate": 0.80,
      "max_error_rate": 0.05,
      "max_response_time_ms": 100
    }
  },
  "rollback": {
    "auto_rollback": true,
    "thresholds": {
      "min_success_rate": 0.75,
      "max_error_rate": 0.10,
      "max_response_time_ms": 200
    }
  }
}
```

## Integration

### Data Collection

**In `worker.py`:**
```python
from ml.feedback_collector import get_feedback_collector

feedback_collector = get_feedback_collector()
feedback_collector.append_outcome(
    task_id=task_id,
    domain=domain,
    action_attempted=task_type,
    result=result.get('success'),
    failure_reason=result.get('failure_reason'),
    execution_time=execution_time,
    # ... other features
)
```

### Model Registry

**Register Model:**
```python
from mlops.model_registry import ModelRegistry

registry = ModelRegistry()
version = registry.register_model(
    model_path="ml/models/export_model.pkl",
    dataset_path="ml/datasets/training_backlinks_enriched.csv",
    schema_version="schema_v1.0",
    training_metrics=metrics
)
```

**Get Model:**
```python
model_info = registry.get_model("production")
engine = AIDecisionEngine(model_path=model_info["model_path"])
```

### Monitoring

**Track Performance:**
```python
registry.update_model_metrics(
    version="v1.2.3",
    metrics={
        "success_rate": 0.85,
        "error_rate": 0.02,
        "avg_response_time": 0.05
    }
)
```

**Rollback:**
```python
if registry.should_rollback("v1.2.3"):
    registry.rollback_to_previous("v1.2.3")
```

## Best Practices

1. **Always version models** - Never overwrite production
2. **Track dataset hash** - Ensure reproducibility
3. **Schema versioning** - Track feature changes
4. **Test before production** - Use staging/canary
5. **Monitor metrics** - Track success rates, errors
6. **Keep rollback ready** - Maintain previous versions
7. **Document changes** - Log all updates
8. **Incremental learning** - Append new data
9. **Gradual rollout** - Use canary deployment
10. **Automatic rollback** - Detect and rollback on issues

## Next Steps

1. **Implement Model Registry** - Full registry implementation
2. **Implement Monitoring** - Real-time metrics tracking
3. **Implement Rollback** - Automatic rollback logic
4. **Implement Canary** - Canary deployment system
5. **Implement Alerts** - Alerting system
6. **Implement Dashboard** - MLOps dashboard

