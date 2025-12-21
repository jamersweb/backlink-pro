# Retraining Workflow

## Overview

Weekly automated retraining workflow that:
1. Collects new feedback
2. Appends to dataset
3. Retrains model
4. Evaluates and compares
5. Versions and deploys (if better)

## Workflow Steps

### Step 1: Collect Feedback

**Script:** `ml/feedback_collector.py`

**Sources:**
- Automation logs (`logs/automation_logs.jsonl`)
- Shadow mode logs (`logs/shadow_mode_logs.jsonl`)
- Laravel API (`/api/ml/historical-data`)

**Output:** Updated `ml/datasets/training_backlinks_enriched.csv`

**Command:**
```bash
python ml/feedback_collector.py --since-days 7 --use-api
```

### Step 2: Prepare Dataset

**Script:** `ml/prepare_dataset.py`

**Actions:**
- Load enriched dataset
- Clean data (remove duplicates, handle missing values)
- Encode features (label/one-hot encoding)
- Split dataset (70/15/15 train/val/test)

**Output:** Prepared datasets in `ml/datasets/`

### Step 3: Train Model

**Script:** `ml/train_action_model.py`

**Model Types:**
- XGBoost (preferred)
- LightGBM (fallback)
- RandomForest (baseline)

**Output:** Trained model `ml/models/export_model_TIMESTAMP.pkl`

### Step 4: Evaluate Model

**Script:** `ml/evaluate_model.py`

**Metrics:**
- Accuracy
- Precision per class
- F1 score (macro)
- Confusion matrix
- Failure rate reduction

**Output:** Evaluation report and plots

### Step 5: Compare with Current

**Logic:** Compare new model accuracy with current production model

**Criteria:**
- New model is better if accuracy improvement ≥ 1%
- Otherwise, keep current model

### Step 6: Version and Deploy

**Script:** `ml/model_versioning.py`

**Actions:**
- Create new version (e.g., v1.1.0)
- Store metadata (accuracy, training stats)
- Deploy to production (if better)
- Or keep as version only (if not better)

## Complete Workflow

### Automated (Weekly)

```bash
# Run complete workflow
python ml/retrain_model.py --since-days 7 --use-api
```

### Manual Steps

```bash
# Step 1: Collect feedback
python ml/feedback_collector.py --since-days 7 --use-api

# Step 2: Prepare dataset
python ml/prepare_dataset.py

# Step 3: Train model
python ml/train_action_model.py --model-type xgboost

# Step 4: Evaluate
python ml/evaluate_model.py --model ml/models/export_model_TIMESTAMP.pkl

# Step 5 & 6: Version and deploy
python ml/model_versioning.py create --model ml/models/export_model_TIMESTAMP.pkl
python ml/model_versioning.py deploy --version v1.1.0
```

## Scheduling

### Cron Job (Linux/Mac)

```bash
# Edit crontab
crontab -e

# Add weekly retraining (Sunday 2 AM)
0 2 * * 0 cd /path/to/backlink-pro/python && python ml/retrain_model.py --since-days 7 --use-api >> logs/retraining.log 2>&1
```

### Windows Task Scheduler

1. Open Task Scheduler
2. Create Basic Task
3. Name: "Weekly Model Retraining"
4. Trigger: Weekly, Sunday, 2:00 AM
5. Action: Start a program
6. Program: `python`
7. Arguments: `ml/retrain_model.py --since-days 7 --use-api`
8. Start in: `D:\XAMPP\htdocs\backlink-pro\python`

### Docker Cron

```dockerfile
# In Dockerfile
RUN echo "0 2 * * 0 cd /app/python && python ml/retrain_model.py --since-days 7 --use-api" >> /etc/crontabs/root
```

## Workflow Output

### Success Example

```
======================================================================
RETRAINING WORKFLOW STARTED
======================================================================
Step 1: Collecting Feedback
Collected 150 new records from automation logs
Collected 80 new records from shadow logs
Collected 200 new records from API
Total unique new records: 430

Step 2: Preparing Dataset
Dataset preparation complete

Step 3: Training New Model
Model training complete: ml/models/export_model_20240115_020000.pkl

Step 4: Evaluating New Model
Accuracy: 0.8750
Macro F1: 0.8600

Step 5: Comparing with Current Model
Current model accuracy: 0.8600
New model accuracy: 0.8750
Improvement: +0.0150
New model is better, will deploy

Step 6: Versioning and Deployment
Created model version: v1.1.0
Deployed version v1.1.0 to production

======================================================================
RETRAINING WORKFLOW COMPLETE
======================================================================
Success: True
Steps completed: 6
```

### Failure Example

```
Step 5: Comparing with Current Model
Current model accuracy: 0.8600
New model accuracy: 0.8550
Improvement: -0.0050
New model is not significantly better, keeping current

Step 6: Versioning and Deployment
Created model version: v1.1.0 (not deployed)
```

## Rollback

### Automatic Rollback

If new model performs worse:
- Not deployed automatically
- Version created but not activated
- Current model remains in production

### Manual Rollback

```bash
# List versions
python ml/model_versioning.py list

# Rollback to previous version
python ml/model_versioning.py rollback

# Rollback to specific version
python ml/model_versioning.py deploy --version v1.0.0
```

## Monitoring

### Logs

- Workflow logs: `logs/retraining.log`
- Model versions: `ml/models/versions/versions.json`
- Evaluation reports: `ml/models/evaluation_*.txt`

### Metrics to Track

- **Dataset Growth**: New records per week
- **Model Accuracy**: Over time
- **Deployment Frequency**: Weekly
- **Rollback Rate**: Should be low
- **Training Time**: Performance monitoring

## Troubleshooting

### Issue: No new feedback collected

**Check:**
- Log files exist and are readable
- `since_days` parameter is correct
- API connection is working

**Solution:**
```bash
# Check log files
ls -lh logs/automation_logs.jsonl
ls -lh logs/shadow_mode_logs.jsonl

# Test feedback collection
python ml/feedback_collector.py --since-days 30 --use-api
```

### Issue: Model training fails

**Check:**
- Dataset file exists
- Dataset has enough records
- Required libraries installed

**Solution:**
```bash
# Check dataset
wc -l ml/datasets/training_backlinks_enriched.csv

# Test dataset preparation
python ml/prepare_dataset.py
```

### Issue: Deployment fails

**Check:**
- Model file exists
- Target directory is writable
- Current model can be backed up

**Solution:**
```bash
# Check permissions
ls -l ml/export_model.pkl

# Test versioning
python ml/model_versioning.py list
```

## Best Practices

1. **Run weekly**: Consistent schedule
2. **Monitor results**: Check accuracy trends
3. **Keep versions**: At least 3 versions
4. **Test rollback**: Know how to rollback
5. **Document changes**: Note what changed each week

## Advanced Options

### Custom Model Type

```bash
python ml/retrain_model.py --model-type lightgbm
```

### Longer Lookback

```bash
python ml/retrain_model.py --since-days 14
```

### No Auto-Deploy

```bash
python ml/retrain_model.py --no-auto-deploy
```

### API Collection Only

```bash
python ml/retrain_model.py --use-api
```

