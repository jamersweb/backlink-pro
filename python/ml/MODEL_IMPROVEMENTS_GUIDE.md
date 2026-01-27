# Model Performance Improvements Guide

## Current Performance Analysis

### Issues Identified

1. **Very Poor Accuracy**: 4.76% (worse than baseline 59.52%)
2. **Class Imbalance**: Severe imbalance in training data:
   - Profile: 233 samples (59.4%)
   - Other: 90 samples (23.0%)
   - Comment: 58 samples (14.8%)
   - Forum: 11 samples (2.8%)
3. **Limited Features**: Only 6 features (pa, da, status_live, status_pending, status_status, status_nan)
4. **Model Not Learning**: Model predicts mostly one class (comment), missing all other classes
5. **No Class Weights**: Model doesn't account for class imbalance

### Confusion Matrix Analysis

```
                | comment    | profile    | forum      | guest     
-------------------------------------------------------------------
comment         |          4 |          0 |         27 |          0
profile         |          0 |          0 |          3 |          0
forum           |          0 |          0 |          0 |          0
guest           |          3 |          0 |         47 |          0
```

**Key Observations:**
- Model predicts "comment" for almost everything
- No predictions for "profile", "forum", or "guest" classes
- High false positive rate for "comment" class
- Zero recall for minority classes

## Implemented Improvements

### 1. Class Imbalance Handling

**Added:**
- Automatic class weight computation using `compute_class_weight('balanced')`
- Sample weights for XGBoost training
- `class_weight='balanced'` for LightGBM and RandomForest

**Impact:**
- Minority classes (forum, comment) will have higher weights
- Model will learn to predict all classes, not just the majority

### 2. Improved Hyperparameters

#### XGBoost
- `n_estimators`: 100 → 200 (more trees)
- `max_depth`: 6 → 8 (deeper trees for complex patterns)
- `learning_rate`: 0.1 → 0.05 (slower learning, better convergence)
- `subsample`: 0.8 → 0.85 (more data per tree)
- `colsample_bytree`: 0.8 → 0.85 (more features per tree)
- Added `min_child_weight=3` (regularization)
- Added `gamma=0.1` (regularization)
- Added `reg_alpha=0.1` (L1 regularization)
- Added `reg_lambda=1.0` (L2 regularization)
- Added `tree_method='hist'` (faster training)

#### LightGBM
- `n_estimators`: 100 → 200
- `max_depth`: 6 → 8
- `learning_rate`: 0.1 → 0.05
- `subsample`: 0.8 → 0.85
- `colsample_bytree`: 0.8 → 0.85
- Added `min_child_samples=20` (regularization)
- Added `reg_alpha=0.1` and `reg_lambda=1.0`
- Added `class_weight='balanced'`

#### RandomForest
- `n_estimators`: 100 → 200
- `max_depth`: 10 → 15
- `min_samples_split`: 5 → 10
- `min_samples_leaf`: 2 → 4
- Added `max_features='sqrt'`
- Added `class_weight='balanced'`

### 3. Feature Importance Analysis

**Added:**
- Automatic feature importance analysis after training
- Top 10 features logged
- Feature importance stored in model metadata

**Usage:**
```python
# Feature importance is automatically analyzed and logged
# Top features are displayed in training logs
```

## Recommended Additional Improvements

### 1. Feature Engineering

**Current Features (6):**
- pa, da, status_live, status_pending, status_status, status_nan

**Suggested Additional Features:**

#### Derived from PA/DA:
```python
- pa_da_sum = pa + da
- pa_da_ratio = pa / (da + 1)  # Avoid division by zero
- pa_da_diff = abs(pa - da)
- pa_da_product = pa * da
- pa_squared = pa ** 2
- da_squared = da ** 2
```

#### From URL/Domain:
```python
- domain_length = len(domain)
- domain_has_subdomain = '.' in domain.split('.')[0]
- url_length = len(url)
- url_path_depth = len(url.split('/'))
- url_has_query = '?' in url
- url_has_fragment = '#' in url
- tld = domain.split('.')[-1]  # Top-level domain
```

#### From Status:
```python
- status_is_live = (status == 'live')
- status_is_pending = (status == 'pending')
- status_is_inactive = (status == 'inactive')
```

#### Time-based (if available):
```python
- hour_of_day = timestamp.hour
- day_of_week = timestamp.weekday()
- month = timestamp.month
- is_weekend = (day_of_week >= 5)
```

### 2. Data Collection Improvements

**Current Issues:**
- Very small dataset (392 training samples)
- Severe class imbalance
- Missing "forum" class samples (only 11)

**Recommendations:**
1. **Collect More Data**: Aim for at least 1000+ samples per class
2. **Balance Classes**: Use oversampling (SMOTE) or undersampling
3. **Active Learning**: Focus on collecting more "forum" and "comment" samples
4. **Data Augmentation**: Create synthetic samples for minority classes

### 3. Advanced Techniques

#### SMOTE (Synthetic Minority Oversampling)
```python
from imblearn.over_sampling import SMOTE

smote = SMOTE(random_state=42)
X_train_resampled, y_train_resampled = smote.fit_resample(X_train, y_train)
```

#### Ensemble Methods
- Combine multiple models (XGBoost + LightGBM + RandomForest)
- Use voting or stacking for final predictions

#### Cross-Validation
- Use stratified k-fold cross-validation
- Better hyperparameter tuning

### 4. Hyperparameter Tuning

**Use Optuna or GridSearchCV:**
```python
import optuna

def objective(trial):
    params = {
        'n_estimators': trial.suggest_int('n_estimators', 100, 300),
        'max_depth': trial.suggest_int('max_depth', 4, 10),
        'learning_rate': trial.suggest_loguniform('learning_rate', 0.01, 0.3),
        'subsample': trial.suggest_uniform('subsample', 0.6, 1.0),
        'colsample_bytree': trial.suggest_uniform('colsample_bytree', 0.6, 1.0),
    }
    # Train and evaluate
    return validation_score
```

## Expected Improvements

### With Current Changes:
- **Accuracy**: 4.76% → Expected: 40-60%
- **Class Recall**: All classes should have >0% recall
- **F1-Score**: Expected improvement from 0.05 to 0.30-0.50

### With Feature Engineering:
- **Accuracy**: Expected: 50-70%
- **Better class separation**
- **More interpretable model**

### With More Data:
- **Accuracy**: Expected: 60-80%
- **Better generalization**
- **More stable predictions**

## Next Steps

1. **Retrain with improvements**: Run training again with new hyperparameters
2. **Add feature engineering**: Implement suggested features in `prepare_dataset.py`
3. **Collect more data**: Focus on minority classes
4. **Monitor performance**: Track metrics over time
5. **Iterate**: Continue improving based on results

## Monitoring

After retraining, check:
- Accuracy improvement
- Per-class precision/recall
- Confusion matrix
- Feature importance
- Training vs validation accuracy (check for overfitting)

## Files Modified

1. `python/ml/train_action_model.py` - Added class weights, improved hyperparameters
2. `python/ml/model_improvements.py` - Utility functions for improvements
3. `python/ml/MODEL_IMPROVEMENTS_GUIDE.md` - This document

## Usage

The improvements are automatically applied when you retrain:

```bash
python python/ml/retrain_model.py
```

The model will now:
- Use class weights to handle imbalance
- Use improved hyperparameters
- Analyze feature importance
- Log detailed training information


