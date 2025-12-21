# ML Model Training Guide

## Overview

This guide explains how to train and evaluate the multiclass action prediction model.

## Problem Type

**Multiclass Classification**: Predicts the best backlink action type
- Classes: `comment`, `profile`, `forum`, `guest`
- Input: Prepared dataset features (PA, DA, site_type, etc.)
- Output: Predicted action class

## Prerequisites

1. **Prepared Dataset**: Run `prepare_dataset.py` first to create train/val/test splits
2. **Target Column**: Dataset must have `action_type` or `action_attempted` as target (not binary success/failure)

## Installation

Install required packages:

```bash
pip install xgboost lightgbm scikit-learn pandas numpy matplotlib seaborn
```

Or use requirements.txt:

```bash
pip install -r requirements.txt
```

## Training

### Basic Training

```bash
cd python/ml
python train_action_model.py
```

### With Options

```bash
python train_action_model.py \
    --dataset-dir ml/datasets \
    --model-dir ml/models \
    --model-type xgboost \
    --output export_model.pkl
```

### Model Selection

The script uses preference order:
1. **XGBoost** (preferred) - Best performance, handles imbalanced data well
2. **LightGBM** (fallback) - Fast training, good performance
3. **RandomForest** (baseline) - Always available, interpretable

You can specify model type with `--model-type` flag.

## Evaluation

### Run Evaluation

```bash
python evaluate_model.py \
    --model ml/export_model.pkl \
    --dataset-dir ml/datasets
```

### Output Files

- `evaluation_report.txt` - Detailed text report
- `evaluation_metrics.json` - Metrics in JSON format
- `confusion_matrix.png` - Visual confusion matrix

## Metrics Reported

### 1. Precision Per Class

Precision for each action type:
- `comment`: Precision score
- `profile`: Precision score
- `forum`: Precision score
- `guest`: Precision score

### 2. Confusion Matrix

Shows prediction vs actual for each class:
```
              | comment | profile | forum | guest
--------------|---------|---------|-------|-------
comment       |    XX   |    XX   |  XX   |  XX
profile       |    XX   |    XX   |  XX   |  XX
forum         |    XX   |    XX   |  XX   |  XX
guest         |    XX   |    XX   |  XX   |  XX
```

### 3. Failure Rate Reduction

Compares model vs baseline (most common class):
- Baseline accuracy
- Model accuracy
- Accuracy improvement
- Error rate reduction (%)

## Dataset Requirements

### Required Columns

The prepared dataset should include:

**Features:**
- `pa` or `Page Authority` - Page Authority score
- `da` or `Domain Authority` - Domain Authority score
- `site_type` - Site type (comment, profile, forum, guest)
- Other encoded features from `prepare_dataset.py`

**Target:**
- `action_type` or `action_attempted` - The action type that was attempted (not success/failure)

### Example Dataset Structure

```csv
pa,da,site_type_comment,site_type_profile,site_type_forum,site_type_guest,...,action_type
45,60,1,0,0,0,...,comment
30,50,0,1,0,0,...,profile
...
```

## Model Usage

### Load and Use Model

```python
import pickle
import pandas as pd

# Load model
with open('ml/export_model.pkl', 'rb') as f:
    model_data = pickle.load(f)

model = model_data['model']
label_encoder = model_data['label_encoder']
feature_names = model_data['feature_names']

# Prepare features (same as training)
features = pd.DataFrame([{
    'pa': 45,
    'da': 60,
    'site_type_comment': 1,
    'site_type_profile': 0,
    # ... other features
}])

# Ensure feature order matches training
features = features[feature_names]

# Predict
prediction_encoded = model.predict(features)[0]
prediction = label_encoder.inverse_transform([prediction_encoded])[0]

print(f"Predicted action: {prediction}")
```

## Troubleshooting

### Error: "Targets are binary but action types needed"

**Solution**: The dataset has success/failure (0/1) instead of action types. You need to:
1. Re-prepare dataset with `action_type` as target column
2. Or ensure `df_cleaned.csv` is available with action types

### Error: "No ML libraries available"

**Solution**: Install required packages:
```bash
pip install xgboost lightgbm scikit-learn
```

### Error: "Feature names don't match"

**Solution**: Ensure feature order matches training. The model saves feature names - use them to order your input features.

## Model Files

- `ml/export_model.pkl` - Saved model (can be renamed)
- `ml/models/export_model.pkl` - Backup location
- Contains: model, label_encoder, feature_names, action_classes, training_stats

## Next Steps

1. **Hyperparameter Tuning**: Adjust model parameters in `train_action_model.py`
2. **Feature Engineering**: Add more features to improve performance
3. **Ensemble Models**: Combine multiple models for better accuracy
4. **Production Integration**: Use saved model in `decision_service.py`

