"""
Hyperparameter Tuning with Optuna

This module provides automated hyperparameter tuning using Optuna
for XGBoost, LightGBM, and RandomForest models.
"""

import numpy as np
import pandas as pd
import logging
from typing import Dict, Optional, Tuple
from pathlib import Path

logger = logging.getLogger(__name__)

# Try to import Optuna
try:
    import optuna
    from optuna.samplers import TPESampler
    OPTUNA_AVAILABLE = True
except ImportError:
    OPTUNA_AVAILABLE = False
    logger.warning("Optuna not available. Install with: pip install optuna")

# Try to import ML libraries
try:
    import xgboost as xgb
    XGBOOST_AVAILABLE = True
except ImportError:
    XGBOOST_AVAILABLE = False

try:
    import lightgbm as lgb
    LIGHTGBM_AVAILABLE = True
except ImportError:
    LIGHTGBM_AVAILABLE = False

try:
    from sklearn.ensemble import RandomForestClassifier
    from sklearn.metrics import accuracy_score, f1_score
    RANDOMFOREST_AVAILABLE = True
except ImportError:
    RANDOMFOREST_AVAILABLE = False


class HyperparameterTuner:
    """Hyperparameter tuning with Optuna"""
    
    def __init__(
        self,
        model_type: str = 'xgboost',
        n_trials: int = 50,
        timeout: Optional[int] = None,
        random_state: int = 42
    ):
        """
        Initialize hyperparameter tuner
        
        Args:
            model_type: Model type ('xgboost', 'lightgbm', 'randomforest')
            n_trials: Number of optimization trials
            timeout: Maximum time in seconds (None for no limit)
            random_state: Random seed
        """
        if not OPTUNA_AVAILABLE:
            raise ImportError("Optuna is required. Install with: pip install optuna")
        
        self.model_type = model_type.lower()
        self.n_trials = n_trials
        self.timeout = timeout
        self.random_state = random_state
        self.study = None
        self.best_params = None
        self.best_score = None
    
    def _create_objective_function(
        self,
        X_train: pd.DataFrame,
        y_train: np.ndarray,
        X_val: pd.DataFrame,
        y_val: np.ndarray,
        num_classes: int
    ):
        """Create objective function for Optuna"""
        
        # Ensure targets are integer arrays
        y_train_enc = np.array(y_train, dtype=int).ravel()
        y_val_enc = np.array(y_val, dtype=int).ravel()
        
        # Check if classes are consecutive, remap if needed
        unique_classes = np.unique(y_train_enc)
        if not np.array_equal(unique_classes, np.arange(len(unique_classes))):
            # Remap to consecutive
            class_mapping = {old: new for new, old in enumerate(sorted(unique_classes))}
            y_train_enc = np.array([class_mapping[c] for c in y_train_enc], dtype=int)
            y_val_enc = np.array([class_mapping.get(c, 0) for c in y_val_enc], dtype=int)
            num_classes = len(unique_classes)
        
        if self.model_type == 'xgboost':
            def objective(trial):
                params = {
                    'objective': 'multi:softprob',
                    'num_class': num_classes,
                    'n_estimators': trial.suggest_int('n_estimators', 100, 300),
                    'max_depth': trial.suggest_int('max_depth', 4, 10),
                    'learning_rate': trial.suggest_float('learning_rate', 0.01, 0.3, log=True),
                    'subsample': trial.suggest_float('subsample', 0.6, 1.0),
                    'colsample_bytree': trial.suggest_float('colsample_bytree', 0.6, 1.0),
                    'min_child_weight': trial.suggest_int('min_child_weight', 1, 10),
                    'gamma': trial.suggest_float('gamma', 0.01, 1.0, log=True),
                    'reg_alpha': trial.suggest_float('reg_alpha', 0.01, 10.0, log=True),
                    'reg_lambda': trial.suggest_float('reg_lambda', 0.01, 10.0, log=True),
                    'random_state': self.random_state,
                    'eval_metric': 'mlogloss',
                    'use_label_encoder': False,
                    'tree_method': 'hist',
                }
                
                model = xgb.XGBClassifier(**params)
                
                # Train with early stopping (try different APIs)
                try:
                    # Try new API first
                    model.fit(
                        X_train, y_train_enc,
                        eval_set=[(X_val, y_val_enc)],
                        callbacks=[xgb.callback.EarlyStopping(rounds=10, save_best=True)],
                        verbose=False
                    )
                except (TypeError, AttributeError):
                    try:
                        # Try old API
                        model.fit(
                            X_train, y_train_enc,
                            eval_set=[(X_val, y_val_enc)],
                            early_stopping_rounds=10,
                            verbose=False
                        )
                    except TypeError:
                        # No early stopping
                        model.fit(X_train, y_train_enc, verbose=False)
                
                # Evaluate
                y_pred = model.predict(X_val)
                score = f1_score(y_val_enc, y_pred, average='macro', zero_division=0)
                
                return score
        
        elif self.model_type == 'lightgbm':
            def objective(trial):
                params = {
                    'objective': 'multiclass',
                    'num_class': num_classes,
                    'n_estimators': trial.suggest_int('n_estimators', 100, 300),
                    'max_depth': trial.suggest_int('max_depth', 4, 10),
                    'learning_rate': trial.suggest_float('learning_rate', 0.01, 0.3, log=True),
                    'subsample': trial.suggest_float('subsample', 0.6, 1.0),
                    'colsample_bytree': trial.suggest_float('colsample_bytree', 0.6, 1.0),
                    'min_child_samples': trial.suggest_int('min_child_samples', 10, 50),
                    'reg_alpha': trial.suggest_float('reg_alpha', 0.01, 10.0, log=True),
                    'reg_lambda': trial.suggest_float('reg_lambda', 0.01, 10.0, log=True),
                    'random_state': self.random_state,
                    'verbose': -1,
                    'class_weight': 'balanced',
                }
                
                model = lgb.LGBMClassifier(**params)
                
                model.fit(
                    X_train, y_train_enc,
                    eval_set=[(X_val, y_val_enc)],
                    callbacks=[lgb.early_stopping(stopping_rounds=10), lgb.log_evaluation(0)],
                    verbose=False
                )
                
                y_pred = model.predict(X_val)
                score = f1_score(y_val_enc, y_pred, average='macro', zero_division=0)
                
                return score
        
        else:  # randomforest
            def objective(trial):
                params = {
                    'n_estimators': trial.suggest_int('n_estimators', 100, 300),
                    'max_depth': trial.suggest_int('max_depth', 5, 20),
                    'min_samples_split': trial.suggest_int('min_samples_split', 2, 20),
                    'min_samples_leaf': trial.suggest_int('min_samples_leaf', 1, 10),
                    'max_features': trial.suggest_categorical('max_features', ['sqrt', 'log2', None]),
                    'bootstrap': trial.suggest_categorical('bootstrap', [True, False]),
                    'random_state': self.random_state,
                    'n_jobs': -1,
                    'class_weight': 'balanced',
                }
                
                model = RandomForestClassifier(**params)
                model.fit(X_train, y_train_enc)
                
                y_pred = model.predict(X_val)
                score = f1_score(y_val_enc, y_pred, average='macro', zero_division=0)
                
                return score
        
        return objective
    
    def tune(
        self,
        X_train: pd.DataFrame,
        y_train: np.ndarray,
        X_val: pd.DataFrame,
        y_val: np.ndarray,
        num_classes: int,
        study_name: Optional[str] = None
    ) -> Dict:
        """
        Run hyperparameter tuning
        
        Args:
            X_train: Training features
            y_train: Training labels
            X_val: Validation features
            y_val: Validation labels
            num_classes: Number of classes
            study_name: Optional study name for Optuna
        
        Returns:
            Dictionary with best parameters and score
        """
        if not OPTUNA_AVAILABLE:
            raise ImportError("Optuna is required. Install with: pip install optuna")
        
        logger.info(f"Starting hyperparameter tuning for {self.model_type}...")
        logger.info(f"Number of trials: {self.n_trials}")
        
        # Create study
        study = optuna.create_study(
            direction='maximize',
            sampler=TPESampler(seed=self.random_state),
            study_name=study_name or f"{self.model_type}_tuning"
        )
        
        # Create objective function
        objective = self._create_objective_function(
            X_train, y_train, X_val, y_val, num_classes
        )
        
        # Run optimization
        try:
            study.optimize(
                objective,
                n_trials=self.n_trials,
                timeout=self.timeout,
                show_progress_bar=True
            )
        except KeyboardInterrupt:
            logger.warning("Tuning interrupted by user")
        
        # Store results
        self.study = study
        self.best_params = study.best_params
        self.best_score = study.best_value
        
        logger.info(f"Best score: {self.best_score:.4f}")
        logger.info(f"Best parameters: {self.best_params}")
        
        return {
            'best_params': self.best_params,
            'best_score': self.best_score,
            'n_trials': len(study.trials),
            'study': study
        }
    
    def get_best_model(self, num_classes: int):
        """Get model with best parameters"""
        if self.best_params is None:
            raise ValueError("No tuning performed. Call tune() first.")
        
        if self.model_type == 'xgboost':
            params = {
                'objective': 'multi:softprob',
                'num_class': num_classes,
                'random_state': self.random_state,
                'eval_metric': 'mlogloss',
                'use_label_encoder': False,
                'tree_method': 'hist',
                **self.best_params
            }
            return xgb.XGBClassifier(**params)
        
        elif self.model_type == 'lightgbm':
            params = {
                'objective': 'multiclass',
                'num_class': num_classes,
                'random_state': self.random_state,
                'verbose': -1,
                'class_weight': 'balanced',
                **self.best_params
            }
            return lgb.LGBMClassifier(**params)
        
        else:  # randomforest
            params = {
                'random_state': self.random_state,
                'n_jobs': -1,
                'class_weight': 'balanced',
                **self.best_params
            }
            return RandomForestClassifier(**params)

