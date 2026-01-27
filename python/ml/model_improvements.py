"""
Model Performance Improvements

This module provides improvements to address:
1. Class imbalance (using class weights and sampling)
2. Hyperparameter optimization
3. Feature engineering
4. Model evaluation enhancements
"""

import numpy as np
import pandas as pd
from sklearn.utils.class_weight import compute_class_weight
from typing import Dict, Optional
import logging

logger = logging.getLogger(__name__)

# Action classes
ACTION_CLASSES = ['comment', 'profile', 'forum', 'guest']


def compute_class_weights(y: np.ndarray, classes: Optional[np.ndarray] = None) -> Dict[int, float]:
    """
    Compute class weights for imbalanced datasets
    
    Args:
        y: Target labels
        classes: Optional array of unique classes (if None, computed from y)
    
    Returns:
        Dictionary mapping class index to weight
    """
    if classes is None:
        classes = np.unique(y)
    
    # Compute balanced class weights
    class_weights = compute_class_weight('balanced', classes=classes, y=y)
    
    # Create dictionary mapping
    weight_dict = {int(cls): float(weight) for cls, weight in zip(classes, class_weights)}
    
    logger.info(f"Computed class weights: {weight_dict}")
    return weight_dict


def get_improved_xgboost_params(num_classes: int, class_weights: Optional[Dict] = None) -> Dict:
    """
    Get improved XGBoost hyperparameters
    
    Args:
        num_classes: Number of classes
        class_weights: Optional class weights dictionary
    
    Returns:
        Dictionary of XGBoost parameters
    """
    params = {
        'objective': 'multi:softprob',
        'num_class': num_classes,
        'n_estimators': 200,  # Increased from 100
        'max_depth': 8,  # Increased from 6 for more complex patterns
        'learning_rate': 0.05,  # Reduced from 0.1 for better convergence
        'subsample': 0.85,  # Slightly increased from 0.8
        'colsample_bytree': 0.85,  # Slightly increased from 0.8
        'min_child_weight': 3,  # Added to prevent overfitting
        'gamma': 0.1,  # Added for regularization
        'reg_alpha': 0.1,  # L1 regularization
        'reg_lambda': 1.0,  # L2 regularization
        'random_state': 42,
        'eval_metric': 'mlogloss',
        'use_label_encoder': False,
        'tree_method': 'hist',  # Faster training
    }
    
    # Add class weights if provided
    if class_weights:
        # XGBoost uses sample_weight parameter, but we can also use scale_pos_weight
        # For multiclass, we'll use sample_weight during fit
        pass
    
    return params


def get_improved_lightgbm_params(num_classes: int, class_weights: Optional[Dict] = None) -> Dict:
    """
    Get improved LightGBM hyperparameters
    
    Args:
        num_classes: Number of classes
        class_weights: Optional class weights dictionary
    
    Returns:
        Dictionary of LightGBM parameters
    """
    params = {
        'objective': 'multiclass',
        'num_class': num_classes,
        'n_estimators': 200,  # Increased from 100
        'max_depth': 8,  # Increased from 6
        'learning_rate': 0.05,  # Reduced from 0.1
        'subsample': 0.85,  # Increased from 0.8
        'colsample_bytree': 0.85,  # Increased from 0.8
        'min_child_samples': 20,  # Added for regularization
        'reg_alpha': 0.1,  # L1 regularization
        'reg_lambda': 1.0,  # L2 regularization
        'random_state': 42,
        'verbose': -1,
        'class_weight': 'balanced' if class_weights else None,
    }
    
    return params


def get_improved_randomforest_params(class_weights: Optional[Dict] = None) -> Dict:
    """
    Get improved RandomForest hyperparameters
    
    Args:
        class_weights: Optional class weights dictionary
    
    Returns:
        Dictionary of RandomForest parameters
    """
    params = {
        'n_estimators': 200,  # Increased from 100
        'max_depth': 15,  # Increased from 10
        'min_samples_split': 10,  # Increased from 5 for regularization
        'min_samples_leaf': 4,  # Increased from 2
        'max_features': 'sqrt',  # Added for better generalization
        'bootstrap': True,
        'random_state': 42,
        'n_jobs': -1,
        'class_weight': 'balanced' if class_weights else None,
    }
    
    return params


def suggest_feature_engineering(df: pd.DataFrame) -> list:
    """
    Suggest additional features to engineer
    
    Args:
        df: DataFrame with existing features
    
    Returns:
        List of suggested feature names
    """
    suggestions = []
    
    # Check if PA and DA exist
    if 'pa' in df.columns and 'da' in df.columns:
        suggestions.extend([
            'pa_da_sum',  # Sum of PA and DA
            'pa_da_ratio',  # Ratio of PA to DA
            'pa_da_diff',  # Difference between PA and DA
            'pa_da_product',  # Product of PA and DA
            'pa_squared',  # PA squared (for non-linear relationships)
            'da_squared',  # DA squared
        ])
    
    # Check if domain exists
    if 'domain' in df.columns:
        suggestions.append('domain_length')  # Length of domain name
        suggestions.append('domain_has_subdomain')  # Boolean for subdomain presence
    
    # Check if URL exists
    if 'url' in df.columns:
        suggestions.extend([
            'url_length',
            'url_path_depth',
            'url_has_query',
            'url_has_fragment',
        ])
    
    # Time-based features (if timestamp exists)
    if 'created_at' in df.columns or 'timestamp' in df.columns:
        time_col = 'created_at' if 'created_at' in df.columns else 'timestamp'
        suggestions.extend([
            f'{time_col}_hour',
            f'{time_col}_day_of_week',
            f'{time_col}_month',
        ])
    
    logger.info(f"Suggested features: {suggestions}")
    return suggestions


def analyze_feature_importance(model, feature_names: list, top_n: int = 20) -> pd.DataFrame:
    """
    Analyze and return feature importance
    
    Args:
        model: Trained model (XGBoost, LightGBM, or RandomForest)
        feature_names: List of feature names
        top_n: Number of top features to return
    
    Returns:
        DataFrame with feature importance
    """
    try:
        if hasattr(model, 'feature_importances_'):
            importances = model.feature_importances_
        elif hasattr(model, 'get_booster'):
            # XGBoost
            importances = model.get_booster().get_score(importance_type='weight')
            # Convert to array matching feature_names
            importances = np.array([importances.get(f'f{i}', 0) for i in range(len(feature_names))])
        else:
            logger.warning("Model does not support feature importance")
            return pd.DataFrame()
        
        # Create DataFrame
        importance_df = pd.DataFrame({
            'feature': feature_names,
            'importance': importances
        }).sort_values('importance', ascending=False).head(top_n)
        
        logger.info(f"Top {top_n} features by importance:")
        for _, row in importance_df.iterrows():
            logger.info(f"  {row['feature']}: {row['importance']:.4f}")
        
        return importance_df
    except Exception as e:
        logger.warning(f"Could not extract feature importance: {e}")
        return pd.DataFrame()


