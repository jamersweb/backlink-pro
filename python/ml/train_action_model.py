"""
Train ML Model for Backlink Action Prediction

Multiclass classification: Predicts best action type (comment, profile, forum, guest)
"""

import pandas as pd
import numpy as np
import pickle
import os
import sys
import logging
from pathlib import Path
import json
from typing import Dict, Optional

# Add parent directory to path
sys.path.insert(0, str(Path(__file__).parent.parent))

logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)

# Try to import ML libraries in preference order
XGBOOST_AVAILABLE = False
LIGHTGBM_AVAILABLE = False
RANDOMFOREST_AVAILABLE = False

try:
    import xgboost as xgb
    XGBOOST_AVAILABLE = True
    logger.info("XGBoost available")
except ImportError:
    logger.warning("XGBoost not available")

# Fix matplotlib home directory issue (must be before lightgbm import)
import tempfile
os.environ.setdefault("MPLCONFIGDIR", os.path.join(tempfile.gettempdir(), "mplconfig"))

try:
    import lightgbm as lgb
    LIGHTGBM_AVAILABLE = True
    logger.info("LightGBM available")
except ImportError:
    logger.warning("LightGBM not available")

try:
    from sklearn.ensemble import RandomForestClassifier
    RANDOMFOREST_AVAILABLE = True
    logger.info("RandomForest available")
except ImportError:
    logger.warning("RandomForest not available")

# Action classes
ACTION_CLASSES = ['comment', 'profile', 'forum', 'guest']
ACTION_CLASS_MAP = {i: action for i, action in enumerate(ACTION_CLASSES)}
REVERSE_ACTION_MAP = {action: i for i, action in enumerate(ACTION_CLASSES)}


class ActionModelTrainer:
    """Train multiclass classifier for backlink action prediction"""
    
    def __init__(self, dataset_dir: str = "ml/datasets", model_dir: str = "ml/models"):
        """
        Initialize trainer
        
        Args:
            dataset_dir: Directory containing prepared datasets
            model_dir: Directory to save trained model
        """
        self.dataset_dir = Path(dataset_dir)
        self.model_dir = Path(model_dir)
        self.model_dir.mkdir(parents=True, exist_ok=True)
        
        self.model = None
        self.model_type = None
        self.label_encoder = None
        self.feature_names = None
        self.training_stats = {}
    
    def load_datasets(self) -> Dict:
        """Load prepared datasets"""
        logger.info(f"Loading datasets from {self.dataset_dir}")
        
        # Load features
        X_train = pd.read_csv(self.dataset_dir / 'X_train.csv')
        X_val = pd.read_csv(self.dataset_dir / 'X_val.csv')
        X_test = pd.read_csv(self.dataset_dir / 'X_test.csv')
        
        # Load targets
        y_train = pd.read_csv(self.dataset_dir / 'y_train.csv')['target'].values
        y_val = pd.read_csv(self.dataset_dir / 'y_val.csv')['target'].values
        y_test = pd.read_csv(self.dataset_dir / 'y_test.csv')['target'].values
        
        # Load metadata
        with open(self.dataset_dir / 'metadata.json', 'r') as f:
            metadata = json.load(f)
        
        self.feature_names = X_train.columns.tolist()
        
        logger.info(f"Train: {len(X_train)} samples, {len(self.feature_names)} features")
        logger.info(f"Val: {len(X_val)} samples")
        logger.info(f"Test: {len(X_test)} samples")
        
        # Check if we need to convert binary target to action classes
        # If target is binary (0/1), we need to map it to action types
        # For now, assume target is already action type or we need to derive it
        
        # If target is binary, we'll need to use a different approach
        # For multiclass, we need the actual action type that was attempted
        # This assumes the dataset has been prepared with action_type as target
        
        return {
            'X_train': X_train,
            'X_val': X_val,
            'X_test': X_test,
            'y_train': y_train,
            'y_val': y_val,
            'y_test': y_test,
            'metadata': metadata,
        }
    
    def prepare_targets(self, y_train, y_val, y_test, datasets: Dict):
        """
        Prepare targets for multiclass classification
        
        The dataset should have action_type as the target column.
        If targets are binary (success/failure), we need to load action types
        from the original cleaned data.
        """
        # Check if targets are already action types (strings)
        # Handle pandas Series
        if isinstance(y_train, pd.Series):
            y_train_first = y_train.iloc[0] if len(y_train) > 0 else None
        else:
            y_train_first = y_train[0] if len(y_train) > 0 else None
        
        if len(y_train) > 0 and isinstance(y_train_first, str):
            # Encode action types to integers
            from sklearn.preprocessing import LabelEncoder
            self.label_encoder = LabelEncoder()
            self.label_encoder.fit(ACTION_CLASSES)
            
            # Helper function to normalize action types
            def normalize_action_type(action_str):
                """Normalize action type to one of ACTION_CLASSES"""
                if pd.isna(action_str):
                    return 'comment'  # Default
                action_str = str(action_str).lower().strip()
                # Map variations
                if action_str in ACTION_CLASSES:
                    return action_str
                elif action_str in ['guestposting', 'guestpost', 'guest_post']:
                    return 'guest'
                elif action_str in ['other', 'unknown', '']:
                    return 'comment'  # Map 'other' to 'comment' as default
                else:
                    return 'comment'  # Default fallback
            
            # Normalize all values first
            if isinstance(y_train, pd.Series):
                y_train_normalized = [normalize_action_type(x) for x in y_train]
                y_val_normalized = [normalize_action_type(x) for x in y_val]
                y_test_normalized = [normalize_action_type(x) for x in y_test]
            else:
                y_train_normalized = [normalize_action_type(str(x)) for x in y_train]
                y_val_normalized = [normalize_action_type(str(x)) for x in y_val]
                y_test_normalized = [normalize_action_type(str(x)) for x in y_test]
            
            y_train_encoded = self.label_encoder.transform(y_train_normalized).astype(int)
            y_val_encoded = self.label_encoder.transform(y_val_normalized).astype(int)
            y_test_encoded = self.label_encoder.transform(y_test_normalized).astype(int)
            
            logger.info("Targets are action types (strings), encoding to integers")
        elif len(y_train) > 0 and isinstance(y_train_first, (int, np.integer)):
            # Check if values are in valid range [0, 3]
            # Convert to numpy arrays for max/min calculations
            y_train_arr = np.array(y_train)
            y_val_arr = np.array(y_val)
            y_test_arr = np.array(y_test)
            max_val = max(np.max(y_train_arr), np.max(y_val_arr), np.max(y_test_arr))
            min_val = min(np.min(y_train_arr), np.min(y_val_arr), np.min(y_test_arr))
            
            if min_val >= 0 and max_val <= 3:
                # Already encoded as action type indices
                logger.info("Targets are already action type indices (0-3)")
                # Convert to numpy arrays if they're pandas Series
                y_train_encoded = np.array(y_train, dtype=int).ravel()
                y_val_encoded = np.array(y_val, dtype=int).ravel()
                y_test_encoded = np.array(y_test, dtype=int).ravel()
                
                # Create label encoder for inverse mapping
                from sklearn.preprocessing import LabelEncoder
                self.label_encoder = LabelEncoder()
                self.label_encoder.fit(ACTION_CLASSES)
            else:
                # Binary targets - need to load action types
                logger.warning("Targets appear to be binary. Attempting to load action types...")
                raise ValueError(
                    "Targets are binary (0/1) but action types needed for multiclass. "
                    "Please prepare dataset with 'action_type' or 'action_attempted' as target column."
                )
        else:
            # Try to load action types from cleaned data
            cleaned_file = self.dataset_dir / 'df_cleaned.csv'
            if not cleaned_file.exists():
                cleaned_file = self.dataset_dir.parent / 'df_cleaned.csv'
            
            if cleaned_file.exists():
                logger.info("Loading action types from cleaned dataset...")
                df_cleaned = pd.read_csv(cleaned_file)
                
                # Find action_type column
                action_col = None
                for col in df_cleaned.columns:
                    col_lower = col.lower()
                    if ('action' in col_lower and 'type' in col_lower) or col_lower == 'action_attempted':
                        action_col = col
                        break
                
                if action_col:
                    # Extract action types (assuming same order as train/val/test split)
                    # This is approximate - ideally indices should be preserved
                    logger.warning("Using action types from cleaned data - ensure row order matches!")
                    action_types = df_cleaned[action_col].values
                    
                    # Encode
                    from sklearn.preprocessing import LabelEncoder
                    self.label_encoder = LabelEncoder()
                    self.label_encoder.fit(ACTION_CLASSES)
                    
                    # Map action types
                    action_types_normalized = []
                    for at in action_types:
                        at_str = str(at).lower().strip()
                        if at_str in ACTION_CLASSES:
                            action_types_normalized.append(at_str)
                        elif at_str in ['guestposting', 'guestpost', 'guest_post']:
                            action_types_normalized.append('guest')
                        elif at_str in ['other', 'unknown', '']:
                            action_types_normalized.append('comment')  # Map 'other' to 'comment'
                        else:
                            action_types_normalized.append('comment')  # Default
                    
                    action_encoded = self.label_encoder.transform(action_types_normalized)
                    
                    # Split to match train/val/test (approximate)
                    n_total = len(action_encoded)
                    n_train = len(y_train)
                    n_val = len(y_val)
                    
                    y_train_encoded = action_encoded[:n_train].astype(int)
                    y_val_encoded = action_encoded[n_train:n_train+n_val].astype(int)
                    y_test_encoded = action_encoded[n_train+n_val:].astype(int)
                    
                    logger.info("Loaded and encoded action types from cleaned data")
                else:
                    raise ValueError(f"Action type column not found in {cleaned_file}")
            else:
                raise ValueError(
                    "Cannot determine action types. "
                    "Please prepare dataset with 'action_type' or 'action_attempted' as target column, "
                    "or ensure df_cleaned.csv is available."
                )
        
        # Ensure all returns are integer numpy arrays
        return (
            np.array(y_train_encoded, dtype=int).ravel(),
            np.array(y_val_encoded, dtype=int).ravel(),
            np.array(y_test_encoded, dtype=int).ravel()
        )
    
    def create_model(self, model_type: Optional[str] = None):
        """
        Create model based on availability and preference
        
        Args:
            model_type: Preferred model type ('xgboost', 'lightgbm', 'randomforest')
                       If None, uses preference order
        """
        if model_type is None:
            # Use preference order
            if XGBOOST_AVAILABLE:
                model_type = 'xgboost'
            elif LIGHTGBM_AVAILABLE:
                model_type = 'lightgbm'
            elif RANDOMFOREST_AVAILABLE:
                model_type = 'randomforest'
            else:
                raise ValueError("No ML libraries available!")
        
        model_type = model_type.lower()
        
        if model_type == 'xgboost' and XGBOOST_AVAILABLE:
            logger.info("Creating XGBoost model")
            self.model = xgb.XGBClassifier(
                objective='multi:softprob',
                num_class=4,
                n_estimators=100,
                max_depth=6,
                learning_rate=0.1,
                subsample=0.8,
                colsample_bytree=0.8,
                random_state=42,
                eval_metric='mlogloss',
                use_label_encoder=False,
            )
            self.model_type = 'xgboost'
            
        elif model_type == 'lightgbm' and LIGHTGBM_AVAILABLE:
            logger.info("Creating LightGBM model")
            self.model = lgb.LGBMClassifier(
                objective='multiclass',
                num_class=4,
                n_estimators=100,
                max_depth=6,
                learning_rate=0.1,
                subsample=0.8,
                colsample_bytree=0.8,
                random_state=42,
                verbose=-1,
            )
            self.model_type = 'lightgbm'
            
        elif model_type == 'randomforest' and RANDOMFOREST_AVAILABLE:
            logger.info("Creating RandomForest model with improved hyperparameters")
            self.model = RandomForestClassifier(
                n_estimators=200,  # Increased from 100
                max_depth=15,  # Increased from 10
                min_samples_split=10,  # Increased from 5
                min_samples_leaf=4,  # Increased from 2
                max_features='sqrt',  # Added for better generalization
                bootstrap=True,
                random_state=42,
                n_jobs=-1,
                class_weight='balanced',  # Handle class imbalance
            )
            self.model_type = 'randomforest'
            
        else:
            raise ValueError(f"Model type '{model_type}' not available")
    
    def train(
        self,
        datasets: Dict,
        model_type: Optional[str] = None,
        use_smote: bool = False,
        smote_strategy: str = 'auto',
        use_optuna: bool = False,
        optuna_trials: int = 50
    ):
        """
        Train the model
        
        Args:
            datasets: Dictionary with X_train, X_val, X_test, y_train, y_val, y_test
            model_type: Preferred model type
            use_smote: Whether to use SMOTE oversampling
            smote_strategy: SMOTE strategy ('auto', 'smote', 'adasyn', etc.)
            use_optuna: Whether to use Optuna for hyperparameter tuning
            optuna_trials: Number of Optuna trials (if use_optuna=True)
        """
        logger.info("Starting model training...")
        
        # Apply SMOTE if requested
        if use_smote:
            try:
                import sys  # Import sys for error messages
                
                # First verify imbalanced-learn is available
                try:
                    import imblearn
                    logger.info(f"imbalanced-learn available (version: {getattr(imblearn, '__version__', 'unknown')})")
                except ImportError:
                    logger.error("=" * 70)
                    logger.error("IMBALANCED-LEARN NOT FOUND")
                    logger.error("=" * 70)
                    logger.error(f"Python executable: {sys.executable}")
                    logger.error("Make sure you're in the virtual environment and install with:")
                    logger.error("  pip install imbalanced-learn")
                    logger.error("=" * 70)
                    raise ImportError("imbalanced-learn package not found")
                
                # Try relative import first, then absolute
                try:
                    from .smote_oversampling import apply_smote_to_datasets
                except (ImportError, ValueError, ModuleNotFoundError):
                    # Fallback to absolute import
                    import sys
                    from pathlib import Path
                    ml_path = Path(__file__).parent
                    if str(ml_path) not in sys.path:
                        sys.path.insert(0, str(ml_path))
                    from smote_oversampling import apply_smote_to_datasets
                
                logger.info("Applying SMOTE oversampling...")
                datasets['X_train'], datasets['y_train'] = apply_smote_to_datasets(
                    datasets['X_train'],
                    datasets['y_train'],
                    strategy=smote_strategy
                )
                logger.info(f"After SMOTE: {len(datasets['X_train'])} training samples")
            except ImportError as e:
                logger.warning(f"SMOTE import failed: {e}")
                logger.warning("Continuing without SMOTE. Model will train with imbalanced classes.")
            except Exception as e:
                logger.warning(f"SMOTE failed: {e}. Continuing without SMOTE.")
                import traceback
                logger.debug(traceback.format_exc())
        
        # Create model
        self.create_model(model_type)
        
        # Use Optuna for hyperparameter tuning if requested
        if use_optuna:
            try:
                from .hyperparameter_tuning import HyperparameterTuner
                logger.info("Using Optuna for hyperparameter tuning...")
                
                # Prepare targets first - need to encode them for Optuna
                y_train_raw = datasets['y_train']
                y_val_raw = datasets['y_val']
                
                # Encode targets if they're strings
                try:
                    y_train_enc, y_val_enc, y_test_enc = self.prepare_targets(
                        y_train_raw,
                        y_val_raw,
                        datasets['y_test'],
                        datasets
                    )
                except ValueError:
                    # If prepare_targets fails, try to encode manually
                    from sklearn.preprocessing import LabelEncoder
                    le = LabelEncoder()
                    if isinstance(y_train_raw[0] if len(y_train_raw) > 0 else None, str):
                        # Normalize and encode
                        def normalize_action_type(action_str):
                            if pd.isna(action_str):
                                return 'comment'
                            action_str = str(action_str).lower().strip()
                            if action_str in ACTION_CLASSES:
                                return action_str
                            elif action_str in ['guestposting', 'guestpost', 'guest_post']:
                                return 'guest'
                            elif action_str in ['other', 'unknown', '']:
                                return 'comment'
                            else:
                                return 'comment'
                        
                        y_train_normalized = [normalize_action_type(x) for x in y_train_raw]
                        y_val_normalized = [normalize_action_type(x) for x in y_val_raw]
                        
                        le.fit(ACTION_CLASSES)
                        y_train_enc = le.transform(y_train_normalized)
                        y_val_enc = le.transform(y_val_normalized)
                    else:
                        y_train_enc = np.array(y_train_raw, dtype=int)
                        y_val_enc = np.array(y_val_raw, dtype=int)
                
                # Get number of classes
                unique_classes = np.unique(y_train_enc)
                num_classes = len(unique_classes)
                
                # Remap to consecutive if needed
                if not np.array_equal(unique_classes, np.arange(num_classes)):
                    class_mapping = {old: new for new, old in enumerate(sorted(unique_classes))}
                    y_train_enc = np.array([class_mapping[c] for c in y_train_enc], dtype=int)
                    y_val_enc = np.array([class_mapping.get(c, 0) for c in y_val_enc], dtype=int)
                
                # Create tuner
                tuner = HyperparameterTuner(
                    model_type=model_type or self.model_type or 'xgboost',
                    n_trials=optuna_trials,
                    random_state=42
                )
                
                # Run tuning with encoded targets
                tuning_results = tuner.tune(
                    datasets['X_train'],
                    y_train_enc,
                    datasets['X_val'],
                    y_val_enc,
                    num_classes
                )
                
                # Get best model
                self.model = tuner.get_best_model(num_classes)
                self.model_type = model_type or self.model_type or 'xgboost'
                
                logger.info(f"Optuna tuning complete. Best score: {tuning_results['best_score']:.4f}")
                logger.info(f"Best parameters: {tuning_results['best_params']}")
                
            except ImportError:
                logger.warning("Optuna not available. Using default hyperparameters.")
            except Exception as e:
                logger.warning(f"Optuna tuning failed: {e}. Using default hyperparameters.")
                import traceback
                logger.debug(traceback.format_exc())
        
        # Prepare targets
        # For now, assume targets need to be converted from binary to action types
        # In practice, the dataset should have action_type as target
        try:
            y_train_enc, y_val_enc, y_test_enc = self.prepare_targets(
                datasets['y_train'],
                datasets['y_val'],
                datasets['y_test'],
                datasets
            )
        except ValueError as e:
            logger.warning(f"Target preparation failed: {e}")
            logger.info("Attempting to handle targets directly...")
            y_train_raw = datasets['y_train']
            y_val_raw = datasets['y_val']
            y_test_raw = datasets['y_test']
            
            # Helper function to normalize action types
            def normalize_action_type(action_str):
                """Normalize action type to one of ACTION_CLASSES"""
                if pd.isna(action_str):
                    return 'comment'  # Default
                action_str = str(action_str).lower().strip()
                # Map variations
                if action_str in ACTION_CLASSES:
                    return action_str
                elif action_str in ['guestposting', 'guestpost', 'guest_post']:
                    return 'guest'
                elif action_str in ['other', 'unknown', '']:
                    return 'comment'  # Map 'other' to 'comment' as default
                else:
                    return 'comment'  # Default fallback
            
            # Convert to integer numpy arrays if needed
            if isinstance(y_train_raw, pd.Series):
                # If it's a pandas Series, check if it's numeric or needs encoding
                if pd.api.types.is_numeric_dtype(y_train_raw):
                    # Check if values are in valid range [0, 3]
                    max_val = y_train_raw.max()
                    min_val = y_train_raw.min()
                    if min_val >= 0 and max_val <= 3:
                        y_train_enc = y_train_raw.values.astype(int)
                        y_val_enc = y_val_raw.values.astype(int)
                        y_test_enc = y_test_raw.values.astype(int)
                    else:
                        # Out of range, treat as binary and map to default
                        logger.warning("Numeric targets out of range [0,3], mapping to default")
                        y_train_enc = np.zeros(len(y_train_raw), dtype=int)
                        y_val_enc = np.zeros(len(y_val_raw), dtype=int)
                        y_test_enc = np.zeros(len(y_test_raw), dtype=int)
                else:
                    # It's strings, need to normalize and encode
                    from sklearn.preprocessing import LabelEncoder
                    self.label_encoder = LabelEncoder()
                    self.label_encoder.fit(ACTION_CLASSES)
                    
                    # Normalize all values first
                    y_train_normalized = [normalize_action_type(x) for x in y_train_raw]
                    y_val_normalized = [normalize_action_type(x) for x in y_val_raw]
                    y_test_normalized = [normalize_action_type(x) for x in y_test_raw]
                    
                    # Encode using the label encoder
                    y_train_enc = self.label_encoder.transform(y_train_normalized).astype(int)
                    y_val_enc = self.label_encoder.transform(y_val_normalized).astype(int)
                    y_test_enc = self.label_encoder.transform(y_test_normalized).astype(int)
            else:
                # Already numpy array or list - check if numeric or string
                y_train_arr = np.array(y_train_raw)
                if y_train_arr.dtype.kind in ['i', 'u', 'f']:  # Integer, unsigned int, or float
                    # Check if values are in valid range [0, 3]
                    max_val = np.max(y_train_arr)
                    min_val = np.min(y_train_arr)
                    if min_val >= 0 and max_val <= 3:
                        y_train_enc = y_train_arr.astype(int)
                        y_val_enc = np.array(y_val_raw, dtype=int)
                        y_test_enc = np.array(y_test_raw, dtype=int)
                    else:
                        logger.warning("Numeric targets out of range [0,3], mapping to default")
                        y_train_enc = np.zeros(len(y_train_arr), dtype=int)
                        y_val_enc = np.zeros(len(y_val_raw), dtype=int)
                        y_test_enc = np.zeros(len(y_test_raw), dtype=int)
                else:
                    # It's strings, normalize and encode
                    from sklearn.preprocessing import LabelEncoder
                    self.label_encoder = LabelEncoder()
                    self.label_encoder.fit(ACTION_CLASSES)
                    
                    # Normalize all values first
                    y_train_normalized = [normalize_action_type(str(x)) for x in y_train_raw]
                    y_val_normalized = [normalize_action_type(str(x)) for x in y_val_raw]
                    y_test_normalized = [normalize_action_type(str(x)) for x in y_test_raw]
                    
                    # Encode using the label encoder
                    y_train_enc = self.label_encoder.transform(y_train_normalized).astype(int)
                    y_val_enc = self.label_encoder.transform(y_val_normalized).astype(int)
                    y_test_enc = self.label_encoder.transform(y_test_normalized).astype(int)
            
            # Create label encoder for mapping if not already created
            if not hasattr(self, 'label_encoder') or self.label_encoder is None:
                from sklearn.preprocessing import LabelEncoder
                self.label_encoder = LabelEncoder()
                self.label_encoder.fit(ACTION_CLASSES)
        
        # Ensure targets are integer numpy arrays
        y_train_enc = np.array(y_train_enc, dtype=int).ravel()
        y_val_enc = np.array(y_val_enc, dtype=int).ravel()
        y_test_enc = np.array(y_test_enc, dtype=int).ravel()
        
        # Train model
        X_train = datasets['X_train']
        X_val = datasets['X_val']
        
        logger.info(f"Training {self.model_type} model...")
        logger.info(f"Training on {len(X_train)} samples")
        
        # Check actual number of classes in the data
        unique_classes = np.unique(y_train_enc)
        num_classes_actual = len(unique_classes)
        logger.info(f"Unique classes in data: {unique_classes}")
        logger.info(f"Class distribution: {np.bincount(y_train_enc)}")
        
        # Remap classes to be consecutive (0, 1, 2, ...) if they're not
        # This is required for XGBoost which expects consecutive class labels
        if not np.array_equal(unique_classes, np.arange(num_classes_actual)):
            logger.info(f"Remapping classes from {unique_classes} to consecutive range [0, {num_classes_actual-1}]")
            class_mapping = {old_class: new_class for new_class, old_class in enumerate(sorted(unique_classes))}
            inverse_mapping = {new: old for old, new in class_mapping.items()}
            
            y_train_enc = np.array([class_mapping[c] for c in y_train_enc], dtype=int)
            y_val_enc = np.array([class_mapping.get(c, 0) for c in y_val_enc], dtype=int)
            y_test_enc = np.array([class_mapping.get(c, 0) for c in y_test_enc], dtype=int)
            
            # Store mapping for inverse transform during prediction
            if not hasattr(self, '_class_remapping'):
                self._class_remapping = class_mapping
                self._inverse_class_remapping = inverse_mapping
                logger.info(f"Class remapping: {class_mapping}")
                logger.info(f"Inverse mapping (for predictions): {inverse_mapping}")
            
            # Update label encoder classes if it exists
            if hasattr(self, 'label_encoder') and self.label_encoder is not None:
                # Store which original action classes these remapped classes represent
                if hasattr(self.label_encoder, 'classes_'):
                    original_classes = self.label_encoder.classes_
                    remapped_action_classes = [original_classes[inverse_mapping[i]] for i in range(num_classes_actual)]
                    logger.info(f"Remapped action classes: {remapped_action_classes}")
                    # Create a new label encoder with only the present classes
                    from sklearn.preprocessing import LabelEncoder
                    self.label_encoder_remapped = LabelEncoder()
                    self.label_encoder_remapped.fit(remapped_action_classes)
        
        # Compute class weights for imbalanced datasets
        from sklearn.utils.class_weight import compute_class_weight
        try:
            class_weights = compute_class_weight('balanced', classes=unique_classes, y=y_train_enc)
            sample_weights = np.array([class_weights[int(c)] for c in y_train_enc])
            logger.info(f"Computed class weights: {dict(zip(unique_classes, class_weights))}")
        except Exception as e:
            logger.warning(f"Could not compute class weights: {e}")
            sample_weights = None
        
        # Update model if num_class doesn't match actual classes
        if self.model_type == 'xgboost':
            # Recreate model with improved hyperparameters and correct num_class
            logger.info(f"Updating XGBoost model to use {num_classes_actual} classes with improved hyperparameters")
            self.model = xgb.XGBClassifier(
                objective='multi:softprob',
                num_class=num_classes_actual,
                n_estimators=200,  # Increased from 100
                max_depth=8,  # Increased from 6
                learning_rate=0.05,  # Reduced from 0.1 for better convergence
                subsample=0.85,  # Slightly increased
                colsample_bytree=0.85,  # Slightly increased
                min_child_weight=3,  # Added for regularization
                gamma=0.1,  # Added for regularization
                reg_alpha=0.1,  # L1 regularization
                reg_lambda=1.0,  # L2 regularization
                random_state=42,
                eval_metric='mlogloss',
                use_label_encoder=False,
                tree_method='hist',  # Faster training
            )
        elif self.model_type == 'lightgbm':
            # Update LightGBM model with improved hyperparameters
            logger.info(f"Updating LightGBM model to use {num_classes_actual} classes with improved hyperparameters")
            self.model = lgb.LGBMClassifier(
                objective='multiclass',
                num_class=num_classes_actual,
                n_estimators=200,  # Increased from 100
                max_depth=8,  # Increased from 6
                learning_rate=0.05,  # Reduced from 0.1
                subsample=0.85,  # Increased
                colsample_bytree=0.85,  # Increased
                min_child_samples=20,  # Added for regularization
                reg_alpha=0.1,  # L1 regularization
                reg_lambda=1.0,  # L2 regularization
                random_state=42,
                verbose=-1,
                class_weight='balanced',  # Handle class imbalance
            )
        
        if self.model_type == 'xgboost':
            # XGBoost with early stopping and sample weights
            # Try different API versions based on XGBoost version
            fit_params = {}
            
            # Add sample weights if available
            if sample_weights is not None:
                fit_params['sample_weight'] = sample_weights
            
            # Try new API with callbacks (XGBoost 2.0+)
            try:
                fit_params['eval_set'] = [(X_val, y_val_enc)]
                fit_params['callbacks'] = [xgb.callback.EarlyStopping(rounds=10, save_best=True)]
                fit_params['verbose'] = True
                self.model.fit(X_train, y_train_enc, **fit_params)
            except (TypeError, AttributeError) as e:
                # New API not available, try old API (XGBoost < 2.0)
                logger.debug(f"New XGBoost API failed: {e}, trying old API")
                try:
                    fit_params.pop('callbacks', None)  # Remove callbacks
                    fit_params['eval_set'] = [(X_val, y_val_enc)]
                    fit_params['early_stopping_rounds'] = 10
                    fit_params['verbose'] = True
                    self.model.fit(X_train, y_train_enc, **fit_params)
                except TypeError as e2:
                    # Old API also not available, train without early stopping
                    logger.warning(f"Early stopping not supported: {e2}. Training without it.")
                    fit_params.pop('early_stopping_rounds', None)
                    fit_params.pop('eval_set', None)  # Remove eval_set if it causes issues
                    fit_params['verbose'] = True
                    try:
                        self.model.fit(X_train, y_train_enc, **fit_params)
                    except TypeError as e3:
                        # Last resort: minimal parameters
                        logger.warning(f"Using minimal fit parameters: {e3}")
                        minimal_params = {}
                        if sample_weights is not None:
                            minimal_params['sample_weight'] = sample_weights
                        self.model.fit(X_train, y_train_enc, **minimal_params)
        elif self.model_type == 'lightgbm':
            # LightGBM with early stopping
            self.model.fit(
                X_train, y_train_enc,
                eval_set=[(X_val, y_val_enc)],
                callbacks=[lgb.early_stopping(stopping_rounds=10), lgb.log_evaluation(period=10)]
            )
        else:
            # RandomForest (already has class_weight='balanced' in constructor)
            self.model.fit(X_train, y_train_enc)
        
        # Store training stats
        train_score = self.model.score(X_train, y_train_enc)
        val_score = self.model.score(X_val, y_val_enc)
        
        self.training_stats = {
            'model_type': self.model_type,
            'train_accuracy': float(train_score),
            'val_accuracy': float(val_score),
            'n_features': len(self.feature_names),
            'n_train': len(X_train),
            'n_val': len(X_val),
        }
        
        logger.info(f"Training complete!")
        logger.info(f"Train accuracy: {train_score:.4f}")
        logger.info(f"Val accuracy: {val_score:.4f}")
        
        # Analyze feature importance
        try:
            self._analyze_feature_importance(X_train)
        except Exception as e:
            logger.warning(f"Could not analyze feature importance: {e}")
        
        return self.model
    
    def _analyze_feature_importance(self, X_train):
        """Analyze and log feature importance"""
        try:
            if hasattr(self.model, 'feature_importances_'):
                importances = self.model.feature_importances_
            elif hasattr(self.model, 'get_booster'):
                # XGBoost - get feature importance
                booster = self.model.get_booster()
                importance_dict = booster.get_score(importance_type='weight')
                # Map to feature indices
                importances = np.array([importance_dict.get(f'f{i}', 0) for i in range(len(self.feature_names))])
            else:
                return
            
            # Create importance DataFrame
            importance_df = pd.DataFrame({
                'feature': self.feature_names,
                'importance': importances
            }).sort_values('importance', ascending=False)
            
            logger.info("=" * 70)
            logger.info("FEATURE IMPORTANCE ANALYSIS")
            logger.info("=" * 70)
            logger.info(f"Top 10 most important features:")
            for idx, (_, row) in enumerate(importance_df.head(10).iterrows(), 1):
                logger.info(f"  {idx:2d}. {row['feature']:30s}: {row['importance']:.4f}")
            logger.info("=" * 70)
            
            # Store in training stats
            if not hasattr(self, 'training_stats'):
                self.training_stats = {}
            self.training_stats['feature_importance'] = importance_df.to_dict('records')
            
        except Exception as e:
            logger.warning(f"Feature importance analysis failed: {e}")
    
    def save_model(self, filename: str = "export_model.pkl"):
        """Save trained model"""
        if self.model is None:
            raise ValueError("No model trained. Call train() first.")
        
        model_path = self.model_dir / filename
        
        # Save model and metadata
        model_data = {
            'model': self.model,
            'model_type': self.model_type,
            'label_encoder': self.label_encoder,
            'feature_names': self.feature_names,
            'action_classes': ACTION_CLASSES,
            'training_stats': self.training_stats,
        }
        
        with open(model_path, 'wb') as f:
            pickle.dump(model_data, f)
        
        logger.info(f"Model saved to {model_path}")
        
        # Also save to ml/ directory for easy access
        ml_model_path = Path(__file__).parent / filename
        with open(ml_model_path, 'wb') as f:
            pickle.dump(model_data, f)
        
        logger.info(f"Model also saved to {ml_model_path}")
        
        return model_path


def main():
    """Main training function"""
    import argparse
    
    parser = argparse.ArgumentParser(description='Train action prediction model')
    parser.add_argument('--dataset-dir', default='ml/datasets', help='Dataset directory')
    parser.add_argument('--model-dir', default='ml/models', help='Model directory')
    parser.add_argument('--model-type', choices=['xgboost', 'lightgbm', 'randomforest'],
                       help='Model type (uses preference order if not specified)')
    parser.add_argument('--output', default='export_model.pkl', help='Output model filename')
    
    args = parser.parse_args()
    
    # Create trainer
    trainer = ActionModelTrainer(args.dataset_dir, args.model_dir)
    
    # Load datasets
    datasets = trainer.load_datasets()
    
    # Train model
    trainer.train(datasets, args.model_type)
    
    # Save model
    trainer.save_model(args.output)
    
    logger.info("\n" + "="*50)
    logger.info("Training Complete!")
    logger.info("="*50)
    logger.info(f"Model type: {trainer.model_type}")
    logger.info(f"Train accuracy: {trainer.training_stats['train_accuracy']:.4f}")
    logger.info(f"Val accuracy: {trainer.training_stats['val_accuracy']:.4f}")
    logger.info(f"Model saved to: {trainer.model_dir / args.output}")


if __name__ == "__main__":
    main()

