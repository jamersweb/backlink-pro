"""
Quick Test Script to Verify Model Deployment

Run this to check if your model is working correctly.
"""

import sys
from pathlib import Path

# Add parent directory to path
sys.path.insert(0, str(Path(__file__).parent))

def test_model_loading():
    """Test if model can be loaded"""
    print("=" * 70)
    print("TEST 1: Model File Exists")
    print("=" * 70)
    
    model_path = Path("ml/export_model.pkl")
    if model_path.exists():
        print(f"✅ Model file found: {model_path}")
        print(f"   Size: {model_path.stat().st_size / 1024:.2f} KB")
    else:
        print(f"❌ Model file NOT found: {model_path}")
        print("   Checking alternative locations...")
        
        alt_paths = [
            Path("ml/models/export_model.pkl"),
            Path("ml/models/versions/v1.1.0/model.pkl"),
        ]
        
        for alt_path in alt_paths:
            if alt_path.exists():
                print(f"   Found at: {alt_path}")
                break
        else:
            print("   ❌ Model not found in any location!")
            return False
    
    return True


def test_ai_decision_engine():
    """Test if AI Decision Engine can load and use the model"""
    print("\n" + "=" * 70)
    print("TEST 2: AI Decision Engine Loading")
    print("=" * 70)
    
    try:
        from ai_decision_engine import get_engine
        
        print("Loading AI Decision Engine...")
        engine = get_engine()
        
        print(f"✅ Engine loaded successfully!")
        print(f"   Model Type: {engine.model_type}")
        print(f"   Features: {len(engine.feature_names)}")
        print(f"   Action Classes: {engine.action_classes}")
        
        return engine
    except Exception as e:
        print(f"❌ Failed to load engine: {e}")
        return None


def test_predictions(engine):
    """Test making predictions"""
    print("\n" + "=" * 70)
    print("TEST 3: Making Predictions")
    print("=" * 70)
    
    if engine is None:
        print("❌ Cannot test predictions - engine not loaded")
        return False
    
    # Test cases
    test_cases = [
        {
            'name': 'Comment Site (High PA/DA)',
            'features': {'pa': 45, 'da': 60, 'status': 'live', 'site_type': 'comment'}
        },
        {
            'name': 'Profile Site (Medium PA/DA)',
            'features': {'pa': 30, 'da': 50, 'status': 'live', 'site_type': 'profile'}
        },
        {
            'name': 'Guest Site (High PA/DA)',
            'features': {'pa': 60, 'da': 70, 'status': 'live', 'site_type': 'guest'}
        },
    ]
    
    all_passed = True
    for test_case in test_cases:
        try:
            print(f"\nTest: {test_case['name']}")
            print(f"  Features: {test_case['features']}")
            
            predictions = engine.predict(test_case['features'])
            
            print(f"  Predictions:")
            for action, prob in sorted(predictions.items(), key=lambda x: x[1], reverse=True):
                print(f"    {action}: {prob:.2%}")
            
            recommended = max(predictions, key=predictions.get)
            print(f"  ✅ Recommended: {recommended} ({predictions[recommended]:.2%})")
            
        except Exception as e:
            print(f"  ❌ Prediction failed: {e}")
            all_passed = False
    
    return all_passed


def check_model_version():
    """Check model version info"""
    print("\n" + "=" * 70)
    print("TEST 4: Model Version Info")
    print("=" * 70)
    
    try:
        from ml.model_versioning import ModelVersionManager
        
        manager = ModelVersionManager()
        versions = manager.list_versions()
        
        if versions:
            print("Model Versions:")
            for v in versions[-3:]:  # Show last 3 versions
                print(f"  Version: {v['version']}")
                print(f"    Created: {v.get('created_at', 'Unknown')}")
                print(f"    Deployed: {v.get('deployed_at', 'Not deployed')}")
                print(f"    Path: {v['model_path']}")
                print()
        else:
            print("No versions found in versioning system")
        
        return True
    except Exception as e:
        print(f"❌ Could not check versions: {e}")
        return False


def main():
    """Run all tests"""
    print("\n" + "=" * 70)
    print("MODEL DEPLOYMENT VERIFICATION")
    print("=" * 70)
    print()
    
    results = {
        'model_exists': test_model_loading(),
        'engine_loaded': False,
        'predictions_work': False,
        'version_info': False
    }
    
    engine = test_ai_decision_engine()
    results['engine_loaded'] = engine is not None
    
    if engine:
        results['predictions_work'] = test_predictions(engine)
    
    results['version_info'] = check_model_version()
    
    # Summary
    print("\n" + "=" * 70)
    print("SUMMARY")
    print("=" * 70)
    
    all_passed = all(results.values())
    
    for test_name, passed in results.items():
        status = "✅ PASS" if passed else "❌ FAIL"
        print(f"{test_name}: {status}")
    
    print()
    if all_passed:
        print("✅ All tests passed! Model is ready for production.")
    else:
        print("⚠️  Some tests failed. Check the errors above.")
    
    return all_passed


if __name__ == "__main__":
    success = main()
    sys.exit(0 if success else 1)

