# Fix: imbalanced-learn Version Compatibility Issue

## Problem

```
ImportError: cannot import name '_is_pandas_df' from 'sklearn.utils.validation'
Did you mean: 'is_pandas_df'?
```

## Root Cause

- **imbalanced-learn 0.14.0** expects old scikit-learn API (`_is_pandas_df`)
- **scikit-learn 1.8.0** uses new API (`is_pandas_df`)
- **Version mismatch** causes import failure

## Solution

### Option 1: Upgrade imbalanced-learn (Recommended)

```powershell
# Make sure venv is active
.venv\Scripts\Activate.ps1

# Upgrade imbalanced-learn to latest version
pip install --upgrade imbalanced-learn

# Verify it works
python -c "import imblearn; print('Version:', imblearn.__version__)"
```

### Option 2: Downgrade scikit-learn (Not Recommended)

```powershell
# Only if upgrade doesn't work
pip install "scikit-learn<1.8.0"
```

## After Fixing

Test the import:

```powershell
python -c "import imblearn; from imblearn.over_sampling import SMOTE; print('OK - SMOTE works!')"
```

Then retrain:

```powershell
python ml/retrain_model.py --use-smote
```

## Expected Result

After fixing, you should see:
```
INFO - imbalanced-learn available (version: 0.X.X)
INFO - Applying SMOTE oversampling...
INFO - After SMOTE: XXX training samples
```

Instead of:
```
ERROR - IMBALANCED-LEARN NOT FOUND
```


