# Model Lifecycle Design

## Overview

Continuous learning system with feedback loop, weekly retraining, versioning, and rollback support.

## Architecture

```
┌─────────────────┐
│  Production     │
│  Model (v1.0.0) │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  AI Decision    │
│  Engine         │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Automation     │
│  Execution      │
└────────┬────────┘
         │
         ▼
┌─────────────────┐      ┌─────────────────┐
│  Feedback       │─────▶│  Dataset        │
│  Collector      │      │  (Append)       │
└─────────────────┘      └────────┬─────────┘
                                  │
                                  ▼
                         ┌─────────────────┐
                         │  Weekly         │
                         │  Retraining     │
                         └────────┬────────┘
                                  │
                                  ▼
                         ┌─────────────────┐
                         │  Model          │
                         │  Versioning     │
                         └────────┬────────┘
                                  │
                                  ▼
                         ┌─────────────────┐
                         │  Evaluation     │
                         │  & Comparison   │
                         └────────┬────────┘
                                  │
                                  ▼
                         ┌─────────────────┐
                         │  Deploy/Rollback│
                         └─────────────────┘
```

## Components

### 1. Feedback Collector

**File:** `ml/feedback_collector.py`

**Responsibilities:**
- Collects new automation results from logs
- Collects shadow mode predictions
- Collects from API (historical data)
- Appends to training dataset
- Tracks processed task IDs to avoid duplicates

**Data Sources:**
- `logs/automation_logs.jsonl` - Automation outcomes
- `logs/shadow_mode_logs.jsonl` - Shadow mode comparisons
- Laravel API `/api/ml/historical-data` - Historical data

**Output:**
- Updated `ml/datasets/training_backlinks_enriched.csv`

### 2. Weekly Retraining Job

**File:** `ml/retrain_model.py`

**Workflow:**
1. **Collect Feedback** (last 7 days)
2. **Prepare Dataset** (clean, encode, split)
3. **Train Model** (XGBoost/LightGBM/RandomForest)
4. **Evaluate Model** (metrics, confusion matrix)
5. **Compare with Current** (accuracy improvement)
6. **Version and Deploy** (if better)

**Schedule:**
- Weekly (e.g., Sunday 2 AM)
- Can be run manually or via cron

### 3. Model Versioning

**File:** `ml/model_versioning.py`

**Features:**
- Semantic versioning (v1.0.0, v1.1.0, etc.)
- Version metadata (training stats, accuracy, etc.)
- Version history tracking
- Rollback support

**Version Structure:**
```
ml/models/versions/
├── v1.0.0/
│   └── model.pkl
├── v1.1.0/
│   └── model.pkl
├── v1.2.0/
│   └── model.pkl
└── versions.json
```

### 4. Rollback Support

**Capabilities:**
- Rollback to previous version
- Automatic backup before deployment
- Version comparison
- Safe rollback (no data loss)

## Workflow Details

### Weekly Retraining Workflow

```python
workflow = RetrainingWorkflow()

# Step 1: Collect new feedback
dataset_path = workflow.collect_feedback(api_client, since_days=7)

# Step 2: Prepare dataset
prepared_dir = workflow.prepare_dataset(dataset_path)

# Step 3: Train new model
model_path = workflow.train_model(model_type='xgboost')

# Step 4: Evaluate
metrics = workflow.evaluate_model(model_path)

# Step 5: Compare with current
is_better = workflow.compare_with_current(metrics)

# Step 6: Version and deploy
if is_better:
    version = workflow.version_and_deploy(model_path, metrics, deploy=True)
else:
    version = workflow.version_and_deploy(model_path, metrics, deploy=False)
```

### Feedback Collection

```python
collector = FeedbackCollector()

# Collect from multiple sources
records = []
records.extend(collector.collect_from_automation_logs(since_days=7))
records.extend(collector.collect_from_shadow_logs(since_days=7))
records.extend(collector.collect_from_api(api_client, since_days=7))

# Append to dataset
collector.append_to_dataset(records)
```

### Model Versioning

```python
manager = ModelVersionManager()

# Create new version
version = manager.create_version(
    model_path=Path('ml/models/export_model_20240115.pkl'),
    metadata={
        'training_stats': {'accuracy': 0.85},
        'evaluation_metrics': {...},
    }
)

# Deploy version
manager.deploy_version('v1.1.0', 'ml/export_model.pkl')

# Rollback
manager.rollback('ml/export_model.pkl')
```

## Scheduling

### Cron Job (Linux/Mac)

```bash
# Weekly retraining: Sunday 2 AM
0 2 * * 0 cd /path/to/backlink-pro/python && python ml/retrain_model.py --since-days 7 --use-api
```

### Windows Task Scheduler

```powershell
# Create scheduled task for weekly retraining
```

### Docker Cron

```dockerfile
# Add to Dockerfile
RUN echo "0 2 * * 0 cd /app/python && python ml/retrain_model.py" >> /etc/crontabs/root
```

## Model Versioning Strategy

### Version Numbering

- **Major** (v1.0.0): Breaking changes, major architecture changes
- **Minor** (v1.1.0): New features, improved accuracy, new data
- **Patch** (v1.1.1): Bug fixes, minor improvements

### Version Metadata

```json
{
  "version": "v1.1.0",
  "created_at": "2024-01-15T02:00:00Z",
  "deployed_at": "2024-01-15T02:30:00Z",
  "training_stats": {
    "train_accuracy": 0.92,
    "val_accuracy": 0.88,
    "n_features": 25,
    "n_train": 5000
  },
  "evaluation_metrics": {
    "accuracy": 0.87,
    "f1_macro": 0.85,
    "failure_rate_reduction": 0.15
  },
  "dataset_size": 5000,
  "created_by": "retraining_job"
}
```

## Rollback Strategy

### Automatic Rollback

- If new model accuracy < current - 0.05 (5% drop)
- If evaluation fails
- If deployment fails

### Manual Rollback

```bash
# List versions
python ml/model_versioning.py list

# Rollback to previous
python ml/model_versioning.py rollback

# Rollback to specific version
python ml/model_versioning.py deploy --version v1.0.0
```

### Rollback Safety

1. **Backup current model** before deployment
2. **Keep previous version** available
3. **Test rollback** before deploying
4. **Monitor** after rollback

## Deployment Strategy

### Deployment Criteria

New model is deployed if:
- Accuracy improvement ≥ 1% (0.01)
- F1 score improvement ≥ 1%
- Failure rate reduction ≥ 5%

### Deployment Process

1. **Evaluate new model**
2. **Compare with current**
3. **Create version** (if better)
4. **Backup current model**
5. **Deploy new model**
6. **Verify deployment**
7. **Monitor performance**

### Canary Deployment (Future)

- Deploy to 10% of traffic
- Monitor metrics
- Gradually increase
- Rollback if issues

## Data Flow

### Feedback Collection

```
Automation Logs → Feedback Collector → Training Dataset
Shadow Logs     → Feedback Collector → Training Dataset
API Historical  → Feedback Collector → Training Dataset
```

### Retraining

```
Training Dataset → Prepare Dataset → Train Model → Evaluate → Version → Deploy
```

### Model Serving

```
Production Model (v1.0.0) → AI Decision Engine → Predictions
```

## Monitoring

### Metrics to Track

- **Model Accuracy**: Over time
- **Deployment Frequency**: Weekly
- **Rollback Rate**: Should be low
- **Dataset Growth**: New records per week
- **Model Performance**: Accuracy per version

### Alerts

- Model accuracy drops > 5%
- Retraining job fails
- Dataset size decreases
- Deployment failures

## Best Practices

### 1. Data Quality

- Validate new data before appending
- Remove duplicates
- Handle missing values
- Check for data drift

### 2. Model Validation

- Always evaluate before deployment
- Compare with current model
- Test on holdout set
- Monitor in production

### 3. Versioning

- Always version before deployment
- Keep at least 3 versions
- Document changes
- Track performance

### 4. Rollback

- Test rollback procedure
- Keep backups
- Monitor after rollback
- Document rollback reasons

## Example Usage

### Manual Retraining

```bash
# Collect feedback and retrain
python ml/retrain_model.py --since-days 7 --use-api

# Retrain with specific model type
python ml/retrain_model.py --model-type xgboost --since-days 14

# Retrain without auto-deploy
python ml/retrain_model.py --no-auto-deploy
```

### Version Management

```bash
# List versions
python ml/model_versioning.py list

# Deploy specific version
python ml/model_versioning.py deploy --version v1.1.0

# Rollback
python ml/model_versioning.py rollback

# Get version info
python ml/model_versioning.py info --version v1.1.0
```

### Feedback Collection Only

```bash
# Collect feedback without retraining
python ml/feedback_collector.py --since-days 7 --use-api
```

## File Structure

```
ml/
├── datasets/
│   ├── training_backlinks_enriched.csv  # Main dataset (grows over time)
│   ├── X_train.csv                      # Prepared features
│   ├── X_val.csv
│   ├── X_test.csv
│   ├── y_train.csv
│   ├── y_val.csv
│   ├── y_test.csv
│   ├── encoders.pkl
│   ├── metadata.json
│   └── processed_tasks.json             # Track processed task IDs
├── models/
│   ├── export_model.pkl                 # Current production model
│   ├── export_model_backup_*.pkl       # Automatic backups
│   └── versions/
│       ├── v1.0.0/
│       │   └── model.pkl
│       ├── v1.1.0/
│       │   └── model.pkl
│       └── versions.json
├── feedback_collector.py
├── retrain_model.py
├── model_versioning.py
└── ...
```

## Continuous Learning Cycle

```
Week 1: Collect feedback → Retrain → Deploy v1.1.0
Week 2: Collect feedback → Retrain → Deploy v1.2.0
Week 3: Collect feedback → Retrain → Deploy v1.3.0
...
```

Each week:
1. New automation results collected
2. Dataset grows
3. Model retrained on larger dataset
4. New version created
5. Deployed if better

## Notes

- **No Deep Learning**: Uses XGBoost/LightGBM/RandomForest only
- **Incremental Learning**: Appends new data, doesn't replace
- **Safe Deployment**: Always compares before deploying
- **Easy Rollback**: One command to rollback
- **Version History**: Complete audit trail

