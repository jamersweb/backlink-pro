# Fixing imbalanced-learn Import Issue

## Problem

The package `imbalanced-learn` is installed, but the script can't import it. This is usually because:

1. **Wrong Python interpreter** - Script is using system Python instead of virtual environment Python
2. **Virtual environment not activated** - The `.venv` environment needs to be active

## Solution

### Step 1: Check Your Environment

Run the diagnostic script:

```powershell
cd python
python check_imbalanced_learn.py
```

This will show:
- Which Python is being used
- If imbalanced-learn is available
- Where it's installed

### Step 2: Make Sure Virtual Environment is Active

In PowerShell:

```powershell
# Navigate to project root
cd D:\XAMPP\htdocs\backlink-pro

# Activate virtual environment
.venv\Scripts\Activate.ps1

# Verify you're in venv (should show (.venv) in prompt)
# Then verify imbalanced-learn
python -c "import imblearn; print('Version:', imblearn.__version__)"
```

### Step 3: Reinstall if Needed

If it's still not working:

```powershell
# Make sure venv is active
.venv\Scripts\Activate.ps1

# Reinstall
pip uninstall imbalanced-learn -y
pip install imbalanced-learn

# Verify
python -c "import imblearn; print('OK')"
```

### Step 4: Run Training Script

Make sure you're in the virtual environment when running:

```powershell
# Activate venv first
.venv\Scripts\Activate.ps1

# Then run training
cd python
python ml/retrain_model.py --use-smote
```

## Common Issues

### Issue 1: "No module named 'imblearn'"

**Cause:** Using system Python instead of venv Python

**Fix:**
```powershell
# Check which Python
python --version
where python

# Should point to: D:\XAMPP\htdocs\backlink-pro\.venv\Scripts\python.exe
# If not, activate venv:
.venv\Scripts\Activate.ps1
```

### Issue 2: Package installed but not found

**Cause:** Package installed in different environment

**Fix:**
```powershell
# Activate venv
.venv\Scripts\Activate.ps1

# Install in venv
pip install imbalanced-learn

# Verify
python -c "import imblearn; print('OK')"
```

### Issue 3: Import works in terminal but not in script

**Cause:** Script using different Python path

**Fix:** Make sure script is run with venv Python:
```powershell
# Activate venv
.venv\Scripts\Activate.ps1

# Run script (should use venv Python)
python ml/retrain_model.py --use-smote
```

## Verification

After fixing, you should see:

```
INFO - imbalanced-learn available (version: 0.14.0)
INFO - Applying SMOTE oversampling...
INFO - After SMOTE: XXX training samples
```

Instead of:

```
WARNING - imbalanced-learn not available. Skipping SMOTE.
```

## Quick Test

```powershell
# Activate venv
.venv\Scripts\Activate.ps1

# Test import
python -c "import imblearn; from imblearn.over_sampling import SMOTE; print('✅ SMOTE works!')"
```

If this works, then retraining with `--use-smote` should work too!


