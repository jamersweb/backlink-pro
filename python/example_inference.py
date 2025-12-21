"""
Example Inference Call for AI Decision Engine

Demonstrates how to use the AI decision engine for runtime inference
"""

from ai_decision_engine import AIDecisionEngine, predict_action, get_engine

def example_basic_inference():
    """Basic inference example"""
    print("=" * 70)
    print("Example 1: Basic Inference")
    print("=" * 70)
    
    # Initialize engine
    engine = AIDecisionEngine()
    
    # Site features
    site_features = {
        'pa': 45,
        'da': 60,
        'site_type': 'comment',
    }
    
    # Get predictions
    probabilities = engine.predict(site_features)
    
    print(f"\nInput features:")
    for key, value in site_features.items():
        print(f"  {key}: {value}")
    
    print(f"\nPredicted probabilities:")
    for action, prob in sorted(probabilities.items(), key=lambda x: x[1], reverse=True):
        print(f"  {action:10s}: {prob:.4f} ({prob*100:.2f}%)")
    
    return probabilities


def example_with_historical_data():
    """Example with historical success rates"""
    print("\n" + "=" * 70)
    print("Example 2: With Historical Data")
    print("=" * 70)
    
    engine = get_engine()  # Use singleton
    
    site_features = {
        'pa': 55,
        'da': 70,
        'site_type': 'profile',
        'backlink_success_rate': 0.85,
        'backlink_total_attempts': 20,
        'action_type_success_rate': 0.75,
        'action_type_total_attempts': 15,
    }
    
    probabilities = engine.predict(site_features)
    
    print(f"\nInput features:")
    for key, value in site_features.items():
        print(f"  {key}: {value}")
    
    print(f"\nPredicted probabilities:")
    for action, prob in sorted(probabilities.items(), key=lambda x: x[1], reverse=True):
        print(f"  {action:10s}: {prob:.4f} ({prob*100:.2f}%)")
    
    # Get best action
    best_action, best_prob = engine.get_best_action(site_features)
    print(f"\nBest action: {best_action} (probability: {best_prob:.4f})")
    
    return probabilities


def example_convenience_function():
    """Example using convenience function"""
    print("\n" + "=" * 70)
    print("Example 3: Convenience Function")
    print("=" * 70)
    
    site_features = {
        'pa': 40,
        'da': 50,
        'site_type': 'forum',
    }
    
    # Use convenience function
    probabilities = predict_action(site_features)
    
    print(f"\nInput features: {site_features}")
    print(f"\nPredicted probabilities:")
    for action, prob in sorted(probabilities.items(), key=lambda x: x[1], reverse=True):
        print(f"  {action:10s}: {prob:.4f} ({prob*100:.2f}%)")
    
    return probabilities


def example_ranked_predictions():
    """Example with ranked predictions"""
    print("\n" + "=" * 70)
    print("Example 4: Ranked Predictions")
    print("=" * 70)
    
    engine = get_engine()
    
    site_features = {
        'pa': 65,
        'da': 80,
        'site_type': 'guest',
        'campaign_daily_limit': 10,
    }
    
    ranked = engine.predict_ranked(site_features)
    
    print(f"\nInput features: {site_features}")
    print(f"\nRanked predictions:")
    for i, (action, prob) in enumerate(ranked, 1):
        print(f"  {i}. {action:10s}: {prob:.4f} ({prob*100:.2f}%)")
    
    return ranked


def example_multiple_sites():
    """Example: Predict for multiple sites"""
    print("\n" + "=" * 70)
    print("Example 5: Multiple Sites")
    print("=" * 70)
    
    engine = get_engine()
    
    sites = [
        {'pa': 30, 'da': 40, 'site_type': 'comment'},
        {'pa': 50, 'da': 60, 'site_type': 'profile'},
        {'pa': 70, 'da': 80, 'site_type': 'forum'},
        {'pa': 45, 'da': 55, 'site_type': 'guest'},
    ]
    
    print("\nPredictions for multiple sites:")
    for i, site in enumerate(sites, 1):
        probabilities = engine.predict(site)
        best_action, best_prob = engine.get_best_action(site)
        print(f"\nSite {i}: PA={site['pa']}, DA={site['da']}, Type={site['site_type']}")
        print(f"  Best action: {best_action} ({best_prob:.4f})")
        print(f"  All probabilities: {probabilities}")


if __name__ == "__main__":
    try:
        # Run all examples
        example_basic_inference()
        example_with_historical_data()
        example_convenience_function()
        example_ranked_predictions()
        example_multiple_sites()
        
        print("\n" + "=" * 70)
        print("All examples completed successfully!")
        print("=" * 70)
        
    except FileNotFoundError as e:
        print(f"\nError: {e}")
        print("\nPlease train a model first:")
        print("  python ml/train_action_model.py")
    except Exception as e:
        print(f"\nError: {e}")
        import traceback
        traceback.print_exc()

