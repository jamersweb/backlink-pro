"""
Diagnostic script to check imbalanced-learn installation
"""

import sys
from pathlib import Path

print("=" * 70)
print("IMBALANCED-LEARN DIAGNOSTIC")
print("=" * 70)
print(f"Python executable: {sys.executable}")
print(f"Python version: {sys.version}")
print(f"Virtual env: {sys.prefix}")
print()

# Check if in virtual environment
venv_path = Path(__file__).parent.parent / ".venv"
if venv_path.exists():
    print(f"[OK] Virtual environment found: {venv_path}")
else:
    print(f"[WARNING] Virtual environment not found at: {venv_path}")

print()

# Try to import imbalanced-learn
try:
    import imblearn
    print(f"[OK] imbalanced-learn imported successfully!")
    print(f"   Version: {imblearn.__version__}")
    print(f"   Location: {imblearn.__file__}")
    
    # Try to import SMOTE
    try:
        from imblearn.over_sampling import SMOTE
        print(f"[OK] SMOTE imported successfully!")
    except ImportError as e:
        print(f"[ERROR] SMOTE import failed: {e}")
        
except ImportError as e:
    print(f"[ERROR] imbalanced-learn import failed: {e}")
    print()
    print("SOLUTION:")
    print("1. Make sure you're in the virtual environment:")
    print("   cd D:\\XAMPP\\htdocs\\backlink-pro")
    print("   .venv\\Scripts\\Activate.ps1")
    print()
    print("2. Verify you're using venv Python:")
    print("   where python")
    print("   (Should show: .venv\\Scripts\\python.exe)")
    print()
    print("3. Install imbalanced-learn:")
    print("   pip install imbalanced-learn")
    print()
    print("4. Verify installation:")
    print("   python -c \"import imblearn; print(imblearn.__version__)\"")

print()
print("=" * 70)

