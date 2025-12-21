# Retrain Workflow

## Overview

Automated retraining workflow that collects new data, retrains models, and deploys with canary rollout and rollback capabilities.

## Workflow Steps

### 1. Data Collection

**Append new run outcomes to dataset store**

- Collect outcomes from automation runs
- Append to dataset store (CSV or database)
- Track: task_id, domain, action_attempted, result, failure_reason, execution_time, etc.

**Data Collection Points:**

```python
# In worker.py after task completion
feedback_collector.append_outcome(
    task_id=task_id,
    domain=domain,
    action_attempted=task_type,
    result=result.get('success'),
    failure_reason=result.get('failure_reason'),
    execution_time=execution_time,
    pa=opportunity.get('pa'),
    da=opportunity.get('da'),
    # ... other features
)
```

**Dataset Store:**

- CSV: `ml/datasets/outcomes.csv` (append mode)
- Database: `ml/datasets/outcomes.db` (SQLite)
- Format: Same schema as training data

### 2. Weekly Retrain Job

**Schedule:** Weekly (e.g., Sunday 2 AM)

**Process:**

1. **Collect New Data**
   - Load outcomes from dataset store
   - Filter by date (last 7 days or since last retrain)
   - Merge with existing training data

2. **Prepare Dataset**
   - Run feature extraction on new URLs (if needed)
   - Merge with existing enriched dataset
   - Run dataset preparation (normalize, encode, split)

3. **Train Model**
   - Train new model with updated dataset
   - Evaluate on test set
   - Compare with current production model

4. **Register Model**
   - Calculate dataset hash
   - Register in model registry
   - Set status to "staging"

5. **Validate Model**
   - Run validation tests
   - Check metrics vs. production model
   - Verify schema compatibility

6. **Deploy (if validated)**
   - Deploy to staging
   - Run smoke tests
   - Deploy to canary (if enabled)
   - Monitor metrics

### 3. Canary Rollout

**Configuration:**

```json
{
  "canary_enabled": true,
  "canary_percentage": 0.1,
  "canary_duration_hours": 24,
  "canary_thresholds": {
    "min_success_rate": 0.80,
    "max_error_rate": 0.05,
    "max_response_time_ms": 100
  }
}
```

**Process:**

1. **Start Canary**
   - Deploy model to canary status
   - Route 10% of traffic to new model
   - Monitor metrics

2. **Monitor Metrics**
   - Success rate
   - Error rate
   - Response time
   - Action distribution

3. **Promote or Rollback**
   - **Promote**: If metrics meet thresholds after duration
   - **Rollback**: If metrics drop below thresholds

### 4. Rollback Strategy

**Automatic Rollback Triggers:**

1. **Success Rate Drop**
   - If success rate < threshold (e.g., 0.80)
   - Compare to previous model's success rate

2. **Error Rate Increase**
   - If error rate > threshold (e.g., 0.05)
   - Monitor for spikes

3. **Response Time Increase**
   - If response time > threshold (e.g., 100ms)
   - Performance degradation

4. **Manual Rollback**
   - Admin-initiated rollback
   - Emergency rollback

**Rollback Process:**

1. **Detect Issue**
   - Monitoring alerts
   - Manual trigger

2. **Verify Rollback Available**
   - Check if previous version exists
   - Verify previous version is stable

3. **Switch Traffic**
   - Update model registry status
   - Switch AI Decision Engine to previous model
   - Verify switch successful

4. **Notify Team**
   - Send alert/notification
   - Log rollback event

5. **Investigate**
   - Analyze root cause
   - Fix issues
   - Plan next retrain

## Workflow Diagram

```
┌─────────────────┐
│  Data Collection│
│  (Continuous)   │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Weekly Retrain  │
│   Job Trigger   │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Collect New Data│
│  + Merge Dataset│
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Prepare Dataset │
│  + Feature Extr.│
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Train Model    │
│  + Evaluate     │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Register Model  │
│  (Staging)      │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Validate Model  │
│  vs Production  │
└────────┬────────┘
         │
    ┌────┴────┐
    │         │
    ▼         ▼
┌────────┐ ┌────────┐
│  Pass  │ │  Fail  │
└───┬────┘ └───┬────┘
    │          │
    │          └──► Discard Model
    │
    ▼
┌─────────────────┐
│ Deploy Canary   │
│  (10% traffic)  │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Monitor Metrics │
│  (24 hours)     │
└────────┬────────┘
         │
    ┌────┴────┐
    │         │
    ▼         ▼
┌────────┐ ┌────────┐
│  Good  │ │  Bad   │
└───┬────┘ └───┬────┘
    │          │
    │          └──► Rollback
    │
    ▼
┌─────────────────┐
│ Deploy Production│
│  (100% traffic) │
└─────────────────┘
```

## Implementation

### Weekly Retrain Job

**File:** `mlops/retrain_job.py`

**Schedule:** Cron job or scheduled task

```bash
# Cron job (Sunday 2 AM)
0 2 * * 0 cd /path/to/backlink-pro/python && python mlops/retrain_job.py
```

**Process:**

1. Load new outcomes from dataset store
2. Merge with existing training data
3. Run feature extraction (if needed)
4. Prepare dataset
5. Train model
6. Evaluate model
7. Register model
8. Deploy to staging/canary
9. Monitor and promote/rollback

### Data Collection Integration

**File:** `ml/feedback_collector.py` (already exists)

**Update to append to dataset store:**

```python
def append_outcome(self, task_id, domain, action_attempted, result, ...):
    """Append outcome to dataset store"""
    outcome = {
        'task_id': task_id,
        'domain': domain,
        'action_attempted': action_attempted,
        'result': result,
        'failure_reason': failure_reason,
        'timestamp': datetime.utcnow().isoformat(),
        # ... other features
    }
    
    # Append to CSV
    self._append_to_csv(outcome)
    
    # Or append to database
    self._append_to_db(outcome)
```

### Monitoring Integration

**Track Model Performance:**

```python
# In worker.py or monitoring service
def track_model_performance(model_version, outcome):
    """Track model performance metrics"""
    registry.update_model_metrics(
        version=model_version,
        metrics={
            'success_rate': calculate_success_rate(),
            'error_rate': calculate_error_rate(),
            'avg_response_time': calculate_avg_response_time(),
        }
    )
    
    # Check if rollback needed
    if registry.should_rollback(model_version):
        registry.rollback_to_previous(model_version)
```

## Configuration

### Retrain Configuration

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

## Best Practices

1. **Incremental Learning**: Append new data, don't retrain from scratch
2. **Validation**: Always validate new model vs. production
3. **Canary First**: Use canary deployment before full rollout
4. **Monitor Closely**: Track metrics during canary period
5. **Rollback Ready**: Always keep previous version available
6. **Document Changes**: Log all retrain events and decisions
7. **Test Staging**: Test in staging before canary
8. **Gradual Rollout**: Increase canary percentage gradually

## Alerts and Notifications

### Retrain Events

- Retrain started
- Retrain completed
- Model deployed to staging
- Model deployed to canary
- Model promoted to production
- Model rolled back

### Monitoring Alerts

- Success rate drop
- Error rate increase
- Response time increase
- Model performance degradation

## Rollback Scenarios

### Scenario 1: Success Rate Drop

**Trigger:** Success rate drops from 0.85 to 0.70

**Action:**
1. Detect via monitoring
2. Verify rollback available
3. Rollback to previous version (v1.2.2)
4. Notify team
5. Investigate root cause

### Scenario 2: Error Spike

**Trigger:** Error rate spikes to 0.15

**Action:**
1. Detect via monitoring
2. Immediate rollback
3. Investigate errors
4. Fix issues
5. Retrain with fixes

### Scenario 3: Canary Failure

**Trigger:** Canary metrics below thresholds

**Action:**
1. Stop canary deployment
2. Keep production model
3. Investigate issues
4. Fix and retrain

## Metrics Tracking

### Model Performance Metrics

- Success rate per action type
- Error rate per action type
- Response time
- Action distribution
- Feature importance changes

### Comparison Metrics

- New model vs. production model
- Success rate delta
- Error rate delta
- Response time delta

## Integration Points

### With Worker

- Collect outcomes after task completion
- Append to dataset store
- Track model version used

### With AI Decision Engine

- Load model from registry
- Track which model version is active
- Support model switching

### With Monitoring

- Track model performance
- Alert on degradation
- Trigger rollback

