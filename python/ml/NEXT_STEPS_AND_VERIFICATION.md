# Next Steps & Website Verification Guide

## Current Model Status

**Model Version:** v1.1.0  
**Status:** ✅ Deployed  
**Accuracy:** 16.67% (needs improvement)

### Current Performance Issues:
- Model only predicts "comment" class well (45% recall)
- Other classes (profile, forum, guest) have 0% recall
- Accuracy is worse than baseline (59.52%)

**This means:** The model needs more training data, especially for minority classes.

---

## Step 1: Quick Verification (Recommended)

Run the test script:

```bash
cd python
python test_model_deployment.py
```

This will check:
- ✅ Model file exists
- ✅ AI Decision Engine can load it
- ✅ Predictions work
- ✅ Version info is correct

---

## Step 1 (Alternative): Manual Verification

### Check Model Files

```bash
# Check if deployed model exists (this is what the system uses)
ls -lh python/ml/export_model.pkl

# Check versioned model
ls python/ml/models/versions/v1.1.0/model.pkl
```

**Important:** The system uses `ml/export_model.pkl` - this is automatically created when a model is deployed.

### Test Model Loading (Python)

```python
import pickle
from pathlib import Path

# Load the deployed model
model_path = Path("python/ml/models/versions/v1.1.0/model.pkl")

with open(model_path, 'rb') as f:
    model_data = pickle.load(f)
    
print(f"Model Type: {model_data['model_type']}")
print(f"Features: {len(model_data['feature_names'])}")
print(f"Action Classes: {model_data['action_classes']}")
```

---

## Step 2: Verify Model is Being Used

### Check AI Decision Engine

The model is used by the `ai_decision_engine.py` which is called by the automation system.

**Location:** `python/ai_decision_engine.py`

**Test the Engine:**

```python
from python.ai_decision_engine import get_engine

# Get the engine (loads the latest model)
engine = get_engine()

# Test prediction
site_features = {
    'pa': 45,
    'da': 60,
    'status': 'live',
    'site_type': 'comment'
}

probabilities = engine.predict(site_features)
print(probabilities)
# Should output: {'comment': 0.XX, 'profile': 0.XX, 'forum': 0.XX, 'guest': 0.XX}
```

---

## Step 3: Check on Website/Admin Panel

### Option A: Check Laravel Logs

The model predictions are logged when opportunities are processed:

```bash
# Check Laravel logs
tail -f storage/logs/laravel.log | grep -i "ml\|prediction\|action"

# Or check Python automation logs
tail -f python/logs/automation_logs.jsonl | grep -i "prediction"
```

### Option B: Check Database

The model recommendations are stored when opportunities are created:

```sql
-- Check recent opportunities with action types
SELECT id, campaign_id, action_type, created_at 
FROM opportunities 
ORDER BY created_at DESC 
LIMIT 20;

-- Check if action_type distribution matches model predictions
SELECT action_type, COUNT(*) as count
FROM opportunities
WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)
GROUP BY action_type;
```

### Option C: Admin Panel (if available)

If you have an admin panel for ML:
1. Navigate to: `/admin/ml/training` or `/admin/ml/models`
2. Check model status
3. View recent predictions

---

## Step 4: Monitor Model Performance

### View Monitoring Dashboard

```python
from python.ml.monitoring_dashboard import ModelMonitor

monitor = ModelMonitor()

# View latest metrics
latest = monitor.get_latest_metrics()
print(latest)

# Generate report
print(monitor.generate_report())

# View trends
monitor.plot_metrics_trend()
```

**Files:**
- `python/ml/monitoring/metrics_history.json` - Historical metrics
- `python/ml/monitoring/metrics_trend.png` - Visualization
- `python/ml/monitoring/monitoring_report.txt` - Text report

---

## Step 5: Test Model in Production

### Test via API (if available)

```bash
# Test ML recommendation endpoint
curl -X GET "http://your-domain/api/ml/action-recommendation/1?backlink_id=123" \
  -H "X-API-Token: your-api-token"
```

### Test via Python Script

Create a test script:

```python
# test_model.py
from python.ai_decision_engine import get_engine

engine = get_engine()

# Test cases
test_cases = [
    {'pa': 45, 'da': 60, 'status': 'live', 'site_type': 'comment'},
    {'pa': 30, 'da': 50, 'status': 'live', 'site_type': 'profile'},
    {'pa': 60, 'da': 70, 'status': 'live', 'site_type': 'guest'},
]

for i, features in enumerate(test_cases, 1):
    print(f"\nTest Case {i}:")
    print(f"Features: {features}")
    predictions = engine.predict(features)
    print(f"Predictions: {predictions}")
    recommended = max(predictions, key=predictions.get)
    print(f"Recommended: {recommended} ({predictions[recommended]:.2%})")
```

Run:
```bash
python test_model.py
```

---

## Step 6: Improve Model Performance

### Current Issues:
1. **Low accuracy** (16.67% vs 59.52% baseline)
2. **Class imbalance** - model only predicts "comment"
3. **Need more data** - especially for profile, forum, guest

### Solutions:

#### A. Retrain with SMOTE (Recommended)

```bash
python ml/retrain_model.py --use-smote
```

This will:
- Balance classes using SMOTE oversampling
- Should improve recall for minority classes

#### B. Retrain with Optuna Tuning

```bash
python ml/retrain_model.py --use-optuna --optuna-trials 100
```

This will:
- Find optimal hyperparameters
- Should improve overall accuracy

#### C. Retrain with Both

```bash
python ml/retrain_model.py --use-smote --use-optuna --optuna-trials 50
```

#### D. Collect More Data

Focus on collecting more examples of:
- **Profile** actions (currently 0% recall)
- **Forum** actions (currently 0% recall)  
- **Guest** actions (currently 0% recall)

---

## Step 7: Verify Improvements

After retraining:

1. **Check new evaluation report:**
   ```bash
   cat python/ml/evaluation_report.txt
   ```

2. **Compare versions:**
   ```python
   from python.ml.monitoring_dashboard import ModelMonitor
   
   monitor = ModelMonitor()
   comparison = monitor.compare_versions('v1.1.0', 'v1.2.0')
   print(comparison)
   ```

3. **Check if new model is better:**
   - Accuracy should increase
   - All classes should have >0% recall
   - F1-score should improve

---

## Step 8: Monitor in Production

### Check Automation Logs

```bash
# Watch for predictions in real-time
tail -f python/logs/automation_logs.jsonl | jq '.action_type, .prediction'
```

### Check Success Rates

After a few days, check if the model is improving success rates:

```sql
-- Compare success rates by action type
SELECT 
    action_type,
    COUNT(*) as total,
    SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as successful,
    AVG(CASE WHEN status = 'success' THEN 1.0 ELSE 0.0 END) as success_rate
FROM backlinks
WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY action_type;
```

---

## Quick Verification Checklist

- [ ] Model file exists: `python/ml/models/versions/v1.1.0/model.pkl`
- [ ] Model loads without errors
- [ ] AI Decision Engine can make predictions
- [ ] Monitoring dashboard shows metrics
- [ ] Opportunities are being created with action types
- [ ] Automation logs show predictions
- [ ] Model is being used in production

---

## Troubleshooting

### Model Not Loading?

```python
# Check model path
from pathlib import Path
model_path = Path("python/ml/models/versions/v1.1.0/model.pkl")
print(f"Exists: {model_path.exists()}")
print(f"Size: {model_path.stat().st_size if model_path.exists() else 0} bytes")
```

### No Predictions in Logs?

1. Check if automation is running
2. Check if AI Decision Engine is being called
3. Check for errors in logs

### Model Performance Not Improving?

1. Collect more training data
2. Use SMOTE to balance classes
3. Use Optuna to tune hyperparameters
4. Check feature engineering is working

---

## Next Actions (Priority Order)

1. **Immediate:** Verify model is loaded and working
2. **Short-term:** Retrain with SMOTE to fix class imbalance
3. **Medium-term:** Collect more data for minority classes
4. **Long-term:** Set up automated retraining weekly

---

## Support

- **Evaluation Report:** `python/ml/evaluation_report.txt`
- **Monitoring Dashboard:** `python/ml/monitoring/`
- **Model Files:** `python/ml/models/versions/`
- **Documentation:** `python/ml/IMPROVEMENTS_USAGE_GUIDE.md`

