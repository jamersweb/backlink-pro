# Model Improvements Usage Guide

This guide explains how to use all the new improvements: feature engineering, SMOTE, Optuna tuning, and monitoring dashboard.

## Installation

First, install the required packages:

```bash
pip install imbalanced-learn optuna matplotlib seaborn
```

## 1. Feature Engineering

Feature engineering is now **automatically applied** during dataset preparation. It adds:

### PA/DA Derived Features:
- `pa_da_sum` - Sum of PA and DA
- `pa_da_diff` - Absolute difference
- `pa_da_product` - Product
- `pa_da_ratio` - Ratio (PA/DA)
- `pa_squared`, `da_squared` - Squared values
- `pa_da_mean`, `pa_da_max`, `pa_da_min` - Statistical aggregations

### Domain Features:
- `domain_length` - Length of domain name
- `domain_has_subdomain` - Boolean for subdomain
- `domain_num_dots` - Number of dots
- `domain_has_hyphen` - Boolean for hyphen
- `tld`, `tld_length` - Top-level domain info

### URL Features:
- `url_length` - URL length
- `url_has_query` - Has query parameters
- `url_has_fragment` - Has fragment
- `url_path_depth` - Path depth
- `url_is_https` - HTTPS enabled

### Status Features:
- `status_is_live`, `status_is_active`, etc. - Binary status indicators

**Usage:** No changes needed - automatically applied when you run:
```bash
python python/ml/retrain_model.py
```

## 2. SMOTE Oversampling

SMOTE helps handle class imbalance by creating synthetic samples for minority classes.

### Enable SMOTE:

```python
from python.ml.retrain_model import RetrainingWorkflow

workflow = RetrainingWorkflow()
workflow.run_full_workflow(
    use_smote=True,  # Enable SMOTE
    smote_strategy='auto'  # Options: 'auto', 'smote', 'adasyn', 'borderline'
)
```

### SMOTE Strategies:
- `'auto'` - Standard SMOTE with balanced sampling (recommended)
- `'smote'` - Standard SMOTE
- `'adasyn'` - Adaptive Synthetic Sampling
- `'borderline'` - Borderline SMOTE
- `'smote_tomek'` - SMOTE + Tomek links (cleaning)
- `'smote_enn'` - SMOTE + Edited Nearest Neighbours

### Manual Usage:

```python
from python.ml.smote_oversampling import apply_smote_to_datasets

X_train_resampled, y_train_resampled = apply_smote_to_datasets(
    X_train,
    y_train,
    strategy='auto'
)
```

## 3. Optuna Hyperparameter Tuning

Optuna automatically finds the best hyperparameters for your model.

### Enable Optuna:

```python
workflow.run_full_workflow(
    use_optuna=True,  # Enable Optuna
    optuna_trials=50  # Number of trials (more = better but slower)
)
```

### Manual Usage:

```python
from python.ml.hyperparameter_tuning import HyperparameterTuner

tuner = HyperparameterTuner(
    model_type='xgboost',  # or 'lightgbm', 'randomforest'
    n_trials=50,
    timeout=3600  # Optional: max time in seconds
)

results = tuner.tune(
    X_train, y_train,
    X_val, y_val,
    num_classes=4
)

# Get best model
best_model = tuner.get_best_model(num_classes=4)
```

### Optuna Parameters Tuned:

**XGBoost:**
- n_estimators: 100-300
- max_depth: 4-10
- learning_rate: 0.01-0.3
- subsample, colsample_bytree: 0.6-1.0
- min_child_weight, gamma, reg_alpha, reg_lambda

**LightGBM:**
- Similar to XGBoost
- min_child_samples: 10-50

**RandomForest:**
- n_estimators: 100-300
- max_depth: 5-20
- min_samples_split, min_samples_leaf
- max_features: 'sqrt', 'log2', None

## 4. Monitoring Dashboard

The monitoring dashboard tracks model performance over time.

### Automatic Logging:

Metrics are automatically logged when you run the workflow. No setup needed!

### View Dashboard:

```python
from python.ml.monitoring_dashboard import ModelMonitor

monitor = ModelMonitor()

# View latest metrics
latest = monitor.get_latest_metrics()
print(latest)

# Generate report
report = monitor.generate_report()
print(report)

# Plot trends
monitor.plot_metrics_trend(['accuracy', 'f1_macro'])

# Compare versions
comparison = monitor.compare_versions('v1.0.0', 'v1.1.0')
print(comparison)
```

### Dashboard Files:

- `ml/monitoring/metrics_history.json` - Historical metrics
- `ml/monitoring/metrics_trend.png` - Visualization
- `ml/monitoring/monitoring_report.txt` - Text report

### Manual Logging:

```python
monitor = ModelMonitor()

metrics = {
    'accuracy': 0.75,
    'f1_macro': 0.68,
    'precision_macro': 0.72,
    'recall_macro': 0.65
}

monitor.log_metrics(metrics, model_version='v1.2.0')
```

## Complete Example

```python
from python.ml.retrain_model import RetrainingWorkflow

# Create workflow
workflow = RetrainingWorkflow()

# Run with all improvements
results = workflow.run_full_workflow(
    since_days=7,
    model_type='xgboost',
    use_smote=True,           # Enable SMOTE
    use_optuna=True,          # Enable Optuna tuning
    optuna_trials=50,         # 50 trials for tuning
    auto_deploy=True
)

print(f"Workflow completed: {results['steps_completed']}")
print(f"Model accuracy: {results['evaluation_metrics']['metrics']['accuracy']:.4f}")
```

## Command Line Usage

```bash
# Basic training (with feature engineering)
python python/ml/retrain_model.py

# With SMOTE
python python/ml/retrain_model.py --use-smote

# With Optuna tuning
python python/ml/retrain_model.py --use-optuna --optuna-trials 50

# With both
python python/ml/retrain_model.py --use-smote --use-optuna --optuna-trials 100
```

## Expected Improvements

### With Feature Engineering:
- **More features**: 6 → 20+ features
- **Better patterns**: Derived features capture non-linear relationships
- **Accuracy**: Expected +5-10% improvement

### With SMOTE:
- **Balanced classes**: All classes have similar representation
- **Better recall**: Minority classes (forum, comment) get better predictions
- **Accuracy**: Expected +10-15% improvement

### With Optuna:
- **Optimized hyperparameters**: Best parameters for your data
- **Better generalization**: Reduced overfitting
- **Accuracy**: Expected +5-10% improvement

### Combined:
- **Total expected improvement**: 20-35% accuracy increase
- **From 4.76% → Expected 25-40%** (or higher with more data)

## Troubleshooting

### SMOTE Issues:
- **Error**: "imbalanced-learn not available"
  - **Fix**: `pip install imbalanced-learn`

### Optuna Issues:
- **Error**: "Optuna not available"
  - **Fix**: `pip install optuna`
- **Slow**: Reduce `optuna_trials` or set `timeout`

### Monitoring Issues:
- **No plots**: Install matplotlib/seaborn
  - **Fix**: `pip install matplotlib seaborn`

## Best Practices

1. **Start Simple**: Use feature engineering first, then add SMOTE, then Optuna
2. **Monitor Progress**: Check monitoring dashboard after each training run
3. **Compare Versions**: Use `compare_versions()` to see improvements
4. **Collect More Data**: More data = better results, especially for minority classes
5. **Iterate**: Try different SMOTE strategies and Optuna trial counts

## Next Steps

1. **Retrain with improvements**: Run workflow with all features enabled
2. **Review metrics**: Check monitoring dashboard
3. **Compare results**: See improvement over baseline
4. **Collect more data**: Focus on minority classes
5. **Fine-tune**: Adjust SMOTE strategy and Optuna trials based on results


