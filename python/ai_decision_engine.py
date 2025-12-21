"""
AI Decision Engine for BacklinkPro
Fast runtime inference for action type prediction

Responsibilities:
- Load trained model
- Accept site feature dict
- Return ranked probabilities per action
"""

import pickle
import os
import sys
import logging
from pathlib import Path
from typing import Dict, Optional, List
import pandas as pd
import numpy as np
from datetime import datetime

logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)

# Action classes
ACTION_CLASSES = ['comment', 'profile', 'forum', 'guest']


class AIDecisionEngine:
    """
    Fast inference engine for backlink action prediction
    No browser interaction, no automation logic - pure ML inference
    """
    
    def __init__(self, model_path: Optional[str] = None):
        """
        Initialize decision engine
        
        Args:
            model_path: Path to trained model file (default: ml/export_model.pkl)
        """
        if model_path is None:
            # Try multiple possible locations
            possible_paths = [
                Path(__file__).parent / 'ml' / 'export_model.pkl',
                Path(__file__).parent / 'ml' / 'models' / 'export_model.pkl',
                Path(__file__).parent.parent / 'ml' / 'export_model.pkl',
            ]
            
            for path in possible_paths:
                if path.exists():
                    model_path = str(path)
                    break
            
            if model_path is None:
                raise FileNotFoundError(
                    f"Model file not found. Tried: {[str(p) for p in possible_paths]}"
                )
        
        self.model_path = Path(model_path)
        self.model = None
        self.model_type = None
        self.label_encoder = None
        self.feature_names = None
        self.action_classes = None
        self.scaler = None  # If model was trained with scaler
        
        # Load model
        self._load_model()
    
    def _load_model(self):
        """Load trained model and metadata"""
        logger.info(f"Loading model from {self.model_path}")
        
        if not self.model_path.exists():
            raise FileNotFoundError(f"Model file not found: {self.model_path}")
        
        with open(self.model_path, 'rb') as f:
            model_data = pickle.load(f)
        
        self.model = model_data['model']
        self.model_type = model_data.get('model_type', 'unknown')
        self.label_encoder = model_data.get('label_encoder')
        self.feature_names = model_data.get('feature_names', [])
        self.action_classes = model_data.get('action_classes', ACTION_CLASSES)
        self.scaler = model_data.get('scaler')  # Optional scaler from training
        
        logger.info(f"Loaded {self.model_type} model")
        logger.info(f"Features: {len(self.feature_names)}")
        logger.info(f"Classes: {self.action_classes}")
    
    def _extract_features(self, site_features: Dict) -> pd.DataFrame:
        """
        Extract and transform features from site feature dict
        
        Args:
            site_features: Dictionary with backlink/site information
            
        Returns:
            DataFrame with features in model format
        """
        features = {}
        
        # Basic features - PA/DA
        pa = site_features.get('pa', site_features.get('page_authority', 0))
        da = site_features.get('da', site_features.get('domain_authority', 0))
        
        features['pa'] = float(pa) if pa is not None else 0.0
        features['da'] = float(da) if da is not None else 0.0
        features['pa_da_sum'] = features['pa'] + features['da']
        features['pa_da_ratio'] = features['pa'] / max(features['da'], 1.0)
        
        # URL features (from feature_extractor)
        features['url_path_depth'] = float(site_features.get('url_path_depth', 0))
        features['https_enabled'] = 1.0 if site_features.get('https_enabled', False) else 0.0
        
        # Platform guess (from feature_extractor)
        platform = str(site_features.get('platform_guess', 'unknown')).lower()
        if self.feature_names:
            for feat_name in self.feature_names:
                if feat_name.startswith('platform_guess_'):
                    platform_type = feat_name.replace('platform_guess_', '')
                    features[feat_name] = 1.0 if platform == platform_type else 0.0
        
        # Site type encoding (one-hot)
        site_type = str(site_features.get('site_type', 'other')).lower().strip()
        if site_type == 'guestposting':
            site_type = 'guest'
        
        features['is_comment'] = 1.0 if site_type == 'comment' else 0.0
        features['is_profile'] = 1.0 if site_type == 'profile' else 0.0
        features['is_forum'] = 1.0 if site_type == 'forum' else 0.0
        features['is_guest'] = 1.0 if site_type == 'guest' else 0.0
        
        # If feature names include one-hot encoded site_type columns, use those
        if self.feature_names:
            for feat_name in self.feature_names:
                if feat_name.startswith('site_type_'):
                    action = feat_name.replace('site_type_', '')
                    features[feat_name] = 1.0 if site_type == action else 0.0
        
        # Feature detection (from feature_extractor)
        features['comment_supported'] = 1.0 if site_features.get('comment_supported', False) else 0.0
        features['profile_supported'] = 1.0 if site_features.get('profile_supported', False) else 0.0
        features['forum_supported'] = 1.0 if site_features.get('forum_supported', False) else 0.0
        features['guest_supported'] = 1.0 if site_features.get('guest_supported', False) else 0.0
        features['requires_login'] = 1.0 if site_features.get('requires_login', False) else 0.0
        features['registration_detected'] = 1.0 if site_features.get('registration_detected', False) else 0.0
        
        # Historical success rates (if available)
        features['backlink_success_rate'] = float(site_features.get('backlink_success_rate', 0.5))
        features['backlink_total_attempts'] = float(site_features.get('backlink_total_attempts', 0))
        features['action_type_success_rate'] = float(site_features.get('action_type_success_rate', 0.5))
        features['action_type_total_attempts'] = float(site_features.get('action_type_total_attempts', 0))
        
        # Time-based features (optional)
        current_time = site_features.get('timestamp', datetime.now())
        if isinstance(current_time, str):
            try:
                current_time = datetime.fromisoformat(current_time.replace('Z', '+00:00'))
            except:
                current_time = datetime.now()
        
        features['hour_of_day'] = float(current_time.hour)
        features['day_of_week'] = float(current_time.weekday())
        
        # Campaign features (optional)
        features['campaign_daily_limit'] = float(site_features.get('campaign_daily_limit', 0))
        features['campaign_total_limit'] = float(site_features.get('campaign_total_limit', 0))
        
        # Add any other features from site_features that match feature_names
        if self.feature_names:
            for feat_name in self.feature_names:
                if feat_name not in features:
                    # Try to get from site_features (case-insensitive)
                    feat_value = None
                    for key, value in site_features.items():
                        if key.lower() == feat_name.lower():
                            feat_value = value
                            break
                    
                    if feat_value is not None:
                        try:
                            features[feat_name] = float(feat_value)
                        except (ValueError, TypeError):
                            features[feat_name] = 0.0
                    else:
                        # Default to 0 for missing features
                        features[feat_name] = 0.0
        
        # Create DataFrame with correct feature order
        if self.feature_names:
            # Ensure all required features are present
            feature_dict = {}
            for feat_name in self.feature_names:
                feature_dict[feat_name] = features.get(feat_name, 0.0)
            df = pd.DataFrame([feature_dict])
        else:
            # Fallback: use all features we extracted
            df = pd.DataFrame([features])
        
        return df
    
    def predict(self, site_features: Dict) -> Dict[str, float]:
        """
        Predict action type probabilities for a site
        
        Args:
            site_features: Dictionary with site/backlink features:
                - pa: Page Authority (0-100)
                - da: Domain Authority (0-100)
                - site_type: Site type (comment, profile, forum, guest)
                - Optional: backlink_success_rate, campaign_daily_limit, etc.
        
        Returns:
            Dictionary with probabilities for each action type:
            {
                "comment": 0.15,
                "profile": 0.67,
                "forum": 0.12,
                "guest": 0.06
            }
        """
        # Extract features
        feature_df = self._extract_features(site_features)
        
        # Scale features if scaler was used during training
        if self.scaler is not None:
            feature_df = pd.DataFrame(
                self.scaler.transform(feature_df),
                columns=feature_df.columns
            )
        
        # Get probability predictions
        try:
            probabilities = self.model.predict_proba(feature_df)[0]
        except AttributeError:
            # Some models might not have predict_proba
            # Fallback to decision function or predict
            logger.warning("Model doesn't support predict_proba, using predict")
            prediction = self.model.predict(feature_df)[0]
            # Create dummy probabilities (1.0 for predicted class, 0.0 for others)
            probabilities = np.zeros(len(self.action_classes))
            probabilities[prediction] = 1.0
        
        # Map probabilities to action class names
        result = {}
        for i, action_class in enumerate(self.action_classes):
            if i < len(probabilities):
                result[action_class] = float(probabilities[i])
            else:
                result[action_class] = 0.0
        
        # Normalize probabilities (ensure they sum to 1.0)
        total = sum(result.values())
        if total > 0:
            result = {k: v / total for k, v in result.items()}
        else:
            # If all zeros, assign equal probability
            result = {k: 1.0 / len(result) for k in result.keys()}
        
        return result
    
    def predict_ranked(self, site_features: Dict) -> List[tuple]:
        """
        Predict and return ranked action types by probability
        
        Args:
            site_features: Dictionary with site/backlink features
        
        Returns:
            List of tuples (action_type, probability) sorted by probability (descending)
        """
        probabilities = self.predict(site_features)
        ranked = sorted(probabilities.items(), key=lambda x: x[1], reverse=True)
        return ranked
    
    def get_best_action(self, site_features: Dict) -> tuple:
        """
        Get the best action type and its probability
        
        Args:
            site_features: Dictionary with site/backlink features
        
        Returns:
            Tuple of (action_type, probability)
        """
        ranked = self.predict_ranked(site_features)
        return ranked[0] if ranked else ('comment', 0.25)


# Global engine instance (lazy loading)
_global_engine: Optional[AIDecisionEngine] = None


def get_engine(model_path: Optional[str] = None) -> AIDecisionEngine:
    """
    Get global decision engine instance (singleton)
    
    Args:
        model_path: Optional path to model file
    
    Returns:
        AIDecisionEngine instance
    """
    global _global_engine
    if _global_engine is None:
        _global_engine = AIDecisionEngine(model_path)
    return _global_engine


def predict_action(site_features: Dict, model_path: Optional[str] = None) -> Dict[str, float]:
    """
    Convenience function for quick predictions
    
    Args:
        site_features: Dictionary with site/backlink features
        model_path: Optional path to model file
    
    Returns:
        Dictionary with probabilities for each action type
    """
    engine = get_engine(model_path)
    return engine.predict(site_features)


if __name__ == "__main__":
    # Example usage
    print("=" * 70)
    print("AI Decision Engine - Example Inference")
    print("=" * 70)
    
    # Initialize engine
    try:
        engine = AIDecisionEngine()
    except FileNotFoundError as e:
        print(f"Error: {e}")
        print("\nPlease train a model first using:")
        print("  python ml/train_action_model.py")
        sys.exit(1)
    
    # Example 1: Basic site features
    print("\nExample 1: Basic site features")
    print("-" * 70)
    site_features_1 = {
        'pa': 45,
        'da': 60,
        'site_type': 'comment',
    }
    
    probabilities_1 = engine.predict(site_features_1)
    print(f"Input: {site_features_1}")
    print(f"Probabilities:")
    for action, prob in sorted(probabilities_1.items(), key=lambda x: x[1], reverse=True):
        print(f"  {action:10s}: {prob:.4f} ({prob*100:.2f}%)")
    
    # Example 2: With historical data
    print("\nExample 2: With historical success rates")
    print("-" * 70)
    site_features_2 = {
        'pa': 55,
        'da': 70,
        'site_type': 'profile',
        'backlink_success_rate': 0.85,
        'backlink_total_attempts': 20,
        'action_type_success_rate': 0.75,
    }
    
    probabilities_2 = engine.predict(site_features_2)
    print(f"Input: {site_features_2}")
    print(f"Probabilities:")
    for action, prob in sorted(probabilities_2.items(), key=lambda x: x[1], reverse=True):
        print(f"  {action:10s}: {prob:.4f} ({prob*100:.2f}%)")
    
    # Example 3: Get best action
    print("\nExample 3: Get best action")
    print("-" * 70)
    best_action, best_prob = engine.get_best_action(site_features_2)
    print(f"Best action: {best_action} (probability: {best_prob:.4f})")
    
    # Example 4: Ranked predictions
    print("\nExample 4: Ranked predictions")
    print("-" * 70)
    ranked = engine.predict_ranked(site_features_2)
    print("Ranked actions:")
    for i, (action, prob) in enumerate(ranked, 1):
        print(f"  {i}. {action:10s}: {prob:.4f} ({prob*100:.2f}%)")
    
    print("\n" + "=" * 70)
    print("Example complete!")
    print("=" * 70)

