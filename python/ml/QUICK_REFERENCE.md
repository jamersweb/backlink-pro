# Continuous Learning - Quick Reference

## Commands

### Collect Feedback

```bash
# Collect last 7 days
python ml/feedback_collector.py --since-days 7

# Collect with API
python ml/feedback_collector.py --since-days 7 --use-api
```

### Retrain Model

```bash
# Full workflow (auto-deploy if better)
python ml/retrain_model.py --since-days 7 --use-api

# Specific model type
python ml/retrain_model.py --model-type xgboost

# No auto-deploy
python ml/retrain_model.py --no-auto-deploy
```

### Version Management

```bash
# List versions
python ml/model_versioning.py list

# Create version
python ml/model_versioning.py create --model ml/models/export_model.pkl

# Deploy version
python ml/model_versioning.py deploy --version v1.1.0

# Rollback
python ml/model_versioning.py rollback

# Version info
python ml/model_versioning.py info --version v1.1.0
```

## Weekly Schedule

### Cron (Linux/Mac)

```bash
0 2 * * 0 cd /path/to/python && python ml/retrain_model.py --since-days 7 --use-api
```

### Windows Task Scheduler

- Trigger: Weekly, Sunday, 2:00 AM
- Program: `python`
- Args: `ml/retrain_model.py --since-days 7 --use-api`

## File Locations

- **Dataset**: `ml/datasets/training_backlinks_enriched.csv`
- **Production Model**: `ml/export_model.pkl`
- **Versions**: `ml/models/versions/`
- **Logs**: `logs/automation_logs.jsonl`, `logs/shadow_mode_logs.jsonl`

## Workflow Summary

```
1. Collect Feedback (7 days)
   ↓
2. Prepare Dataset
   ↓
3. Train Model
   ↓
4. Evaluate Model
   ↓
5. Compare with Current
   ↓
6. Version & Deploy (if better)
```

## Rollback

```bash
# Quick rollback
python ml/model_versioning.py rollback

# Specific version
python ml/model_versioning.py deploy --version v1.0.0
```

## Troubleshooting

- **No feedback**: Check log files exist
- **Training fails**: Check dataset size
- **Deploy fails**: Check permissions
- **Rollback**: Use `model_versioning.py rollback`

