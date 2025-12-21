"""
SMOTE Oversampling for Imbalanced Datasets

This module provides SMOTE (Synthetic Minority Oversampling Technique) 
for handling class imbalance in the training data.
"""

import numpy as np
import pandas as pd
import logging
from typing import Dict, Tuple, Optional
from pathlib import Path

logger = logging.getLogger(__name__)

# Try to import imbalanced-learn
try:
    from imblearn.over_sampling import SMOTE, ADASYN, BorderlineSMOTE
    from imblearn.combine import SMOTETomek, SMOTEENN
    IMBLEARN_AVAILABLE = True
except ImportError:
    IMBLEARN_AVAILABLE = False
    logger.warning("imbalanced-learn not available. Install with: pip install imbalanced-learn")


class SMOTEOversampler:
    """Apply SMOTE oversampling to balance classes"""
    
    def __init__(self, strategy: str = 'auto', random_state: int = 42):
        """
        Initialize SMOTE oversampler
        
        Args:
            strategy: Oversampling strategy
                - 'auto': Use SMOTE with balanced sampling
                - 'smote': Standard SMOTE
                - 'adasyn': Adaptive Synthetic Sampling
                - 'borderline': Borderline SMOTE
                - 'smote_tomek': SMOTE + Tomek links
                - 'smote_enn': SMOTE + Edited Nearest Neighbours
            random_state: Random seed
        """
        if not IMBLEARN_AVAILABLE:
            raise ImportError("imbalanced-learn is required. Install with: pip install imbalanced-learn")
        
        self.strategy = strategy
        self.random_state = random_state
        self.oversampler = None
        self._create_oversampler()
    
    def _create_oversampler(self):
        """Create the oversampler based on strategy"""
        if self.strategy == 'smote':
            self.oversampler = SMOTE(random_state=self.random_state)
        elif self.strategy == 'adasyn':
            self.oversampler = ADASYN(random_state=self.random_state)
        elif self.strategy == 'borderline':
            self.oversampler = BorderlineSMOTE(random_state=self.random_state)
        elif self.strategy == 'smote_tomek':
            self.oversampler = SMOTETomek(random_state=self.random_state)
        elif self.strategy == 'smote_enn':
            self.oversampler = SMOTEENN(random_state=self.random_state)
        else:  # 'auto'
            # Use SMOTE with balanced sampling
            self.oversampler = SMOTE(
                sampling_strategy='auto',
                random_state=self.random_state,
                k_neighbors=5
            )
    
    def fit_resample(self, X: pd.DataFrame, y: np.ndarray) -> Tuple[pd.DataFrame, np.ndarray]:
        """
        Apply SMOTE oversampling
        
        Args:
            X: Feature matrix
            y: Target labels
        
        Returns:
            Resampled (X_resampled, y_resampled)
        """
        logger.info(f"Applying {self.strategy} oversampling...")
        
        # Get class distribution before
        unique, counts = np.unique(y, return_counts=True)
        before_dist = dict(zip(unique, counts))
        logger.info(f"Class distribution before SMOTE: {before_dist}")
        
        # Apply SMOTE
        X_resampled, y_resampled = self.oversampler.fit_resample(X, y)
        
        # Convert back to DataFrame if X was DataFrame
        if isinstance(X, pd.DataFrame):
            X_resampled = pd.DataFrame(
                X_resampled,
                columns=X.columns,
                index=pd.RangeIndex(len(X_resampled))
            )
        
        # Get class distribution after
        unique, counts = np.unique(y_resampled, return_counts=True)
        after_dist = dict(zip(unique, counts))
        logger.info(f"Class distribution after SMOTE: {after_dist}")
        
        logger.info(f"Oversampled from {len(X)} to {len(X_resampled)} samples")
        
        return X_resampled, y_resampled
    
    def get_class_distribution(self, y: np.ndarray) -> Dict:
        """Get class distribution"""
        unique, counts = np.unique(y, return_counts=True)
        return dict(zip(unique, counts))


def apply_smote_to_datasets(
    X_train: pd.DataFrame,
    y_train: np.ndarray,
    strategy: str = 'auto',
    random_state: int = 42
) -> Tuple[pd.DataFrame, np.ndarray]:
    """
    Convenience function to apply SMOTE to training data
    
    Args:
        X_train: Training features
        y_train: Training labels
        strategy: SMOTE strategy
        random_state: Random seed
    
    Returns:
        Resampled (X_train_resampled, y_train_resampled)
    """
    if not IMBLEARN_AVAILABLE:
        logger.warning("imbalanced-learn not available. Skipping SMOTE.")
        return X_train, y_train
    
    oversampler = SMOTEOversampler(strategy=strategy, random_state=random_state)
    return oversampler.fit_resample(X_train, y_train)

