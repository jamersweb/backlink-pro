# Fix: imbalanced-learn Not Found

## Problem

The script shows:
```
ERROR - IMBALANCED-LEARN NOT FOUND
Python executable: C:\Python312\python.exe
```

This means it's using **system Python** instead of **virtual environment Python**.

## Solution

### Step 1: Activate Virtual Environment

```powershell
# Navigate to project root
cd D:\XAMPP\htdocs\backlink-pro

# Activate virtual environment
.venv\Scripts\Activate.ps1

# Verify activation (should show (.venv) in prompt)
```

### Step 2: Verify You're Using Venv Python

```powershell
# Check which Python is being used
where python

# Should show:
# D:\XAMPP\htdocs\backlink-pro\.venv\Scripts\python.exe
# NOT: C:\Python312\python.exe
```

### Step 3: Install in Virtual Environment

```powershell
# Make sure venv is active (prompt shows (.venv))
pip install imbalanced-learn

# Verify
python -c "import imblearn; print('Version:', imblearn.__version__)"
```

### Step 4: Run Training Script

```powershell
# Make sure venv is active
cd python

# Run training
python ml/retrain_model.py --use-smote
```

## Why This Happens

When you run `python ml/retrain_model.py`, it uses whatever Python is first in your PATH. If the virtual environment isn't properly activated, it uses system Python.

## Quick Test

Run this to verify everything works:

```powershell
# Activate venv
.venv\Scripts\Activate.ps1

# Test import
python -c "import imblearn; from imblearn.over_sampling import SMOTE; print('OK - SMOTE works!')"
```

If this works, then `--use-smote` will work too!

## Expected Output After Fix

When SMOTE works, you'll see:
```
INFO - imbalanced-learn available (version: 0.14.0)
INFO - Applying SMOTE oversampling...
INFO - After SMOTE: XXX training samples
```

Instead of:
```
ERROR - IMBALANCED-LEARN NOT FOUND
WARNING - SMOTE failed: ...
```

