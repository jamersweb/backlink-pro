# Install ML Dependencies for BacklinkPro
# Run this script to install all required packages

Write-Host "Installing ML dependencies..." -ForegroundColor Green

# Activate virtual environment if exists
if (Test-Path ".venv\Scripts\Activate.ps1") {
    Write-Host "Activating virtual environment..." -ForegroundColor Yellow
    .\.venv\Scripts\Activate.ps1
}

# Install packages
Write-Host "`nInstalling imbalanced-learn (for SMOTE)..." -ForegroundColor Cyan
pip install imbalanced-learn

Write-Host "`nInstalling optuna (for hyperparameter tuning)..." -ForegroundColor Cyan
pip install optuna

Write-Host "`nInstalling matplotlib and seaborn (for visualizations)..." -ForegroundColor Cyan
pip install matplotlib seaborn

Write-Host "`nVerifying installations..." -ForegroundColor Cyan
python -c "import imblearn; print('✅ imbalanced-learn:', imblearn.__version__)"
python -c "import optuna; print('✅ optuna:', optuna.__version__)"
python -c "import matplotlib; print('✅ matplotlib:', matplotlib.__version__)"
python -c "import seaborn; print('✅ seaborn:', seaborn.__version__)"

Write-Host "`n✅ All dependencies installed!" -ForegroundColor Green

