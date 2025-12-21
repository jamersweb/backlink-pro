"""
AI Decision Layer for BacklinkPro
Predicts success probability for different backlink action types based on historical data
"""

import logging
import pickle
import os
from typing import Dict, List, Optional, Tuple
from collections import defaultdict
import numpy as np
from datetime import datetime, timedelta

logger = logging.getLogger(__name__)

# Try to import sklearn, but make it optional for basic mode
try:
    from sklearn.ensemble import RandomForestClassifier, GradientBoostingClassifier
    from sklearn.model_selection import train_test_split
    from sklearn.preprocessing import StandardScaler
    from sklearn.metrics import accuracy_score, classification_report
    SKLEARN_AVAILABLE = True
except ImportError:
    SKLEARN_AVAILABLE = False
    logger.warning("scikit-learn not available. Using basic statistical model.")


class BacklinkPredictor:
    """
    ML-based predictor that learns from historical backlink data
    and predicts which action type has highest success probability
    """
    
    ACTION_TYPES = ['comment', 'profile', 'forum', 'guest']
    
    def __init__(self, model_dir: Optional[str] = None):
        """
        Initialize predictor
        
        Args:
            model_dir: Directory to save/load trained models
        """
        self.model_dir = model_dir or os.path.join(os.path.dirname(__file__), 'ml_models')
        os.makedirs(self.model_dir, exist_ok=True)
        
        self.models = {}
        self.scalers = {}
        self.stats = {}  # Fallback statistics if ML not available
        self.is_trained = False
        
    def _extract_features(self, record: Dict) -> Dict:
        """
        Extract features from a historical record
        
        Args:
            record: Historical data record with backlink and task info
            
        Returns:
            Feature dictionary
        """
        backlink = record.get('backlink', {})
        task = record.get('task', {})
        opportunity = record.get('opportunity', {})
        campaign = record.get('campaign', {})
        
        # Basic features
        features = {
            'pa': backlink.get('pa', 0),
            'da': backlink.get('da', 0),
            'pa_da_sum': backlink.get('pa', 0) + backlink.get('da', 0),
            'pa_da_ratio': backlink.get('pa', 0) / max(backlink.get('da', 1), 1),
        }
        
        # Site type encoding (one-hot like)
        site_type = backlink.get('site_type', 'other')
        features['is_comment'] = 1 if site_type == 'comment' else 0
        features['is_profile'] = 1 if site_type == 'profile' else 0
        features['is_forum'] = 1 if site_type == 'forum' else 0
        features['is_guest'] = 1 if site_type == 'guestposting' else 0
        
        # Task type encoding
        task_type = task.get('type', 'comment')
        features['task_comment'] = 1 if task_type == 'comment' else 0
        features['task_profile'] = 1 if task_type == 'profile' else 0
        features['task_forum'] = 1 if task_type == 'forum' else 0
        features['task_guest'] = 1 if task_type in ['guest', 'guestposting'] else 0
        
        # Historical success rate for this backlink
        features['backlink_success_rate'] = record.get('backlink_success_rate', 0.5)
        features['backlink_total_attempts'] = record.get('backlink_total_attempts', 0)
        
        # Historical success rate for this action type
        features['action_type_success_rate'] = record.get('action_type_success_rate', 0.5)
        features['action_type_total_attempts'] = record.get('action_type_total_attempts', 0)
        
        # Time-based features
        created_at = record.get('created_at')
        if created_at:
            try:
                if isinstance(created_at, str):
                    dt = datetime.fromisoformat(created_at.replace('Z', '+00:00'))
                else:
                    dt = created_at
                features['hour_of_day'] = dt.hour
                features['day_of_week'] = dt.weekday()
            except:
                features['hour_of_day'] = 12
                features['day_of_week'] = 0
        
        # Campaign features
        features['campaign_daily_limit'] = campaign.get('daily_limit', 0)
        features['campaign_total_limit'] = campaign.get('total_limit', 0)
        
        return features
    
    def train(self, historical_data: List[Dict]) -> Dict:
        """
        Train models on historical data
        
        Args:
            historical_data: List of historical records with success/failure outcomes
            
        Returns:
            Training results dictionary
        """
        if not historical_data:
            logger.warning("No historical data provided for training")
            return {'success': False, 'error': 'No data'}
        
        logger.info(f"Training on {len(historical_data)} historical records")
        
        # Prepare data
        X = []
        y = []
        
        # Calculate aggregate statistics for each backlink and action type
        backlink_stats = defaultdict(lambda: {'success': 0, 'total': 0})
        action_type_stats = defaultdict(lambda: {'success': 0, 'total': 0})
        
        for record in historical_data:
            backlink_id = record.get('backlink', {}).get('id')
            task_type = record.get('task', {}).get('type', 'comment')
            is_success = record.get('success', False)
            
            if backlink_id:
                backlink_stats[backlink_id]['total'] += 1
                if is_success:
                    backlink_stats[backlink_id]['success'] += 1
            
            action_type_stats[task_type]['total'] += 1
            if is_success:
                action_type_stats[task_type]['success'] += 1
        
        # Add statistics to records
        for record in historical_data:
            backlink_id = record.get('backlink', {}).get('id')
            task_type = record.get('task', {}).get('type', 'comment')
            
            if backlink_id and backlink_stats[backlink_id]['total'] > 0:
                record['backlink_success_rate'] = (
                    backlink_stats[backlink_id]['success'] / 
                    backlink_stats[backlink_id]['total']
                )
                record['backlink_total_attempts'] = backlink_stats[backlink_id]['total']
            else:
                record['backlink_success_rate'] = 0.5
                record['backlink_total_attempts'] = 0
            
            if action_type_stats[task_type]['total'] > 0:
                record['action_type_success_rate'] = (
                    action_type_stats[task_type]['success'] / 
                    action_type_stats[task_type]['total']
                )
                record['action_type_total_attempts'] = action_type_stats[task_type]['total']
            else:
                record['action_type_success_rate'] = 0.5
                record['action_type_total_attempts'] = 0
        
        # Extract features and labels
        for record in historical_data:
            features = self._extract_features(record)
            X.append(list(features.values()))
            y.append(1 if record.get('success', False) else 0)
        
        if not X:
            logger.error("No features extracted from historical data")
            return {'success': False, 'error': 'No features'}
        
        X = np.array(X)
        y = np.array(y)
        
        # Store feature names for later use
        self.feature_names = list(self._extract_features(historical_data[0]).keys())
        
        # Train models for each action type
        results = {}
        
        if SKLEARN_AVAILABLE and len(X) >= 20:  # Need minimum data for ML
            # Split data by action type and train separate models
            for action_type in self.ACTION_TYPES:
                # Filter data for this action type
                action_indices = []
                for i, record in enumerate(historical_data):
                    task_type = record.get('task', {}).get('type', 'comment')
                    if task_type == action_type or (action_type == 'guest' and task_type == 'guestposting'):
                        action_indices.append(i)
                
                if len(action_indices) < 10:  # Need minimum samples
                    logger.warning(f"Insufficient data for {action_type} ({len(action_indices)} samples)")
                    continue
                
                X_action = X[action_indices]
                y_action = y[action_indices]
                
                if len(np.unique(y_action)) < 2:
                    logger.warning(f"Only one class in {action_type} data, skipping ML model")
                    continue
                
                # Split train/test
                X_train, X_test, y_train, y_test = train_test_split(
                    X_action, y_action, test_size=0.2, random_state=42
                )
                
                # Scale features
                scaler = StandardScaler()
                X_train_scaled = scaler.fit_transform(X_train)
                X_test_scaled = scaler.transform(X_test)
                
                # Train model (use GradientBoosting for better performance on small datasets)
                model = GradientBoostingClassifier(
                    n_estimators=50,
                    max_depth=5,
                    learning_rate=0.1,
                    random_state=42
                )
                model.fit(X_train_scaled, y_train)
                
                # Evaluate
                y_pred = model.predict(X_test_scaled)
                accuracy = accuracy_score(y_test, y_pred)
                
                self.models[action_type] = model
                self.scalers[action_type] = scaler
                
                results[action_type] = {
                    'accuracy': float(accuracy),
                    'samples': len(X_action),
                    'train_samples': len(X_train),
                    'test_samples': len(X_test),
                }
                
                logger.info(f"Trained {action_type} model: {accuracy:.2%} accuracy on {len(X_test)} test samples")
            
            # Save models
            self._save_models()
        
        # Always calculate and store basic statistics (fallback)
        for action_type in self.ACTION_TYPES:
            stats = action_type_stats.get(action_type, {'success': 0, 'total': 0})
            if stats['total'] > 0:
                self.stats[action_type] = {
                    'success_rate': stats['success'] / stats['total'],
                    'total_attempts': stats['total'],
                    'success_count': stats['success'],
                }
            else:
                self.stats[action_type] = {
                    'success_rate': 0.5,  # Default 50% if no data
                    'total_attempts': 0,
                    'success_count': 0,
                }
        
        self.is_trained = True
        
        return {
            'success': True,
            'total_samples': len(historical_data),
            'models': results,
            'statistics': self.stats,
        }
    
    def predict_success_probability(self, backlink: Dict, action_type: str, 
                                   campaign: Optional[Dict] = None) -> float:
        """
        Predict success probability for a specific backlink and action type
        
        Args:
            backlink: Backlink dictionary with pa, da, site_type, etc.
            action_type: Action type ('comment', 'profile', 'forum', 'guest')
            campaign: Optional campaign dictionary
            
        Returns:
            Success probability (0.0 to 1.0)
        """
        if not self.is_trained:
            logger.warning("Predictor not trained, using default probability")
            return 0.5
        
        # Normalize action type
        if action_type == 'guestposting':
            action_type = 'guest'
        
        if action_type not in self.ACTION_TYPES:
            logger.warning(f"Unknown action type: {action_type}")
            return 0.5
        
        # Create a record for feature extraction
        record = {
            'backlink': backlink,
            'task': {'type': action_type},
            'campaign': campaign or {},
            'backlink_success_rate': self.stats.get(action_type, {}).get('success_rate', 0.5),
            'backlink_total_attempts': 0,  # Would need backlink-specific history
            'action_type_success_rate': self.stats.get(action_type, {}).get('success_rate', 0.5),
            'action_type_total_attempts': self.stats.get(action_type, {}).get('total_attempts', 0),
            'created_at': datetime.now(),
        }
        
        features = self._extract_features(record)
        feature_vector = np.array([list(features.values())])
        
        # Use ML model if available
        if SKLEARN_AVAILABLE and action_type in self.models:
            try:
                scaler = self.scalers[action_type]
                model = self.models[action_type]
                feature_vector_scaled = scaler.transform(feature_vector)
                probability = model.predict_proba(feature_vector_scaled)[0][1]
                return float(probability)
            except Exception as e:
                logger.warning(f"ML prediction failed for {action_type}: {e}, using statistics")
        
        # Fallback to statistics
        base_rate = self.stats.get(action_type, {}).get('success_rate', 0.5)
        
        # Adjust based on PA/DA
        pa = backlink.get('pa', 0)
        da = backlink.get('da', 0)
        pa_da_sum = pa + da
        
        # Higher PA/DA generally means better success rate
        # Normalize to 0-100 range and adjust probability
        if pa_da_sum > 0:
            # Scale adjustment: sites with PA+DA > 80 get +10%, < 20 get -10%
            adjustment = (pa_da_sum - 50) / 300  # Max adjustment ~±10%
            probability = base_rate + adjustment
        else:
            probability = base_rate
        
        # Ensure probability is in valid range
        return max(0.0, min(1.0, probability))
    
    def recommend_action_type(self, backlink: Dict, 
                             campaign: Optional[Dict] = None,
                             available_types: Optional[List[str]] = None) -> Tuple[str, float]:
        """
        Recommend the best action type for a backlink
        
        Args:
            backlink: Backlink dictionary
            campaign: Optional campaign dictionary
            available_types: List of action types to consider (default: all)
            
        Returns:
            Tuple of (recommended_action_type, probability)
        """
        if available_types is None:
            available_types = self.ACTION_TYPES
        
        # Normalize action types
        available_types = [
            'guest' if at in ['guest', 'guestposting'] else at 
            for at in available_types
        ]
        
        best_action = None
        best_probability = 0.0
        
        for action_type in available_types:
            if action_type not in self.ACTION_TYPES:
                continue
            
            # Check if backlink site_type matches (optional constraint)
            site_type = backlink.get('site_type', '')
            if site_type:
                type_map = {
                    'comment': 'comment',
                    'profile': 'profile',
                    'forum': 'forum',
                    'guestposting': 'guest',
                }
                if type_map.get(site_type) != action_type:
                    # Still consider it, but with slight penalty
                    continue
            
            probability = self.predict_success_probability(backlink, action_type, campaign)
            
            if probability > best_probability:
                best_probability = probability
                best_action = action_type
        
        # If no action found, default to comment
        if best_action is None:
            best_action = 'comment'
            best_probability = self.predict_success_probability(backlink, 'comment', campaign)
        
        return (best_action, best_probability)
    
    def _save_models(self):
        """Save trained models to disk"""
        try:
            model_path = os.path.join(self.model_dir, 'models.pkl')
            scaler_path = os.path.join(self.model_dir, 'scalers.pkl')
            stats_path = os.path.join(self.model_dir, 'stats.pkl')
            
            with open(model_path, 'wb') as f:
                pickle.dump(self.models, f)
            with open(scaler_path, 'wb') as f:
                pickle.dump(self.scalers, f)
            with open(stats_path, 'wb') as f:
                pickle.dump(self.stats, f)
            
            logger.info(f"Models saved to {self.model_dir}")
        except Exception as e:
            logger.error(f"Failed to save models: {e}")
    
    def _load_models(self):
        """Load trained models from disk"""
        try:
            model_path = os.path.join(self.model_dir, 'models.pkl')
            scaler_path = os.path.join(self.model_dir, 'scalers.pkl')
            stats_path = os.path.join(self.model_dir, 'stats.pkl')
            
            if os.path.exists(model_path):
                with open(model_path, 'rb') as f:
                    self.models = pickle.load(f)
            if os.path.exists(scaler_path):
                with open(scaler_path, 'rb') as f:
                    self.scalers = pickle.load(f)
            if os.path.exists(stats_path):
                with open(stats_path, 'rb') as f:
                    self.stats = pickle.load(f)
                    self.is_trained = True
            
            logger.info(f"Models loaded from {self.model_dir}")
        except Exception as e:
            logger.warning(f"Failed to load models: {e}")
    
    def load_or_train(self, api_client, force_retrain: bool = False):
        """
        Load existing models or train new ones from API
        
        Args:
            api_client: LaravelAPIClient instance
            force_retrain: Force retraining even if models exist
        """
        if not force_retrain:
            self._load_models()
            if self.is_trained:
                logger.info("Using existing trained models")
                return
        
        # Fetch historical data from API
        logger.info("Fetching historical data from API...")
        historical_data = api_client.get_historical_backlink_data()
        
        if not historical_data:
            logger.warning("No historical data available, using default statistics")
            # Initialize with default stats
            for action_type in self.ACTION_TYPES:
                self.stats[action_type] = {
                    'success_rate': 0.5,
                    'total_attempts': 0,
                    'success_count': 0,
                }
            self.is_trained = True
            return
        
        # Train on historical data
        self.train(historical_data)

