"""
Dataset Preparation Script for BacklinkPro ML Training

This script:
1. Loads and cleans the training dataset
2. Normalizes categorical fields
3. Encodes categories (label/one-hot)
4. Handles missing PA/DA values
5. Removes duplicate domains
6. Splits dataset (70/15/15 train/val/test)
"""

# Fix Windows asyncio issue before any other imports
import sys
import os
if sys.platform == 'win32':
    # Workaround for Windows asyncio _overlapped import error
    # This happens when Windows system files have issues
    try:
        # Try to import and patch asyncio before joblib uses it
        import asyncio
        # Set to use SelectorEventLoop which doesn't require _overlapped
        try:
            if hasattr(asyncio, 'WindowsSelectorEventLoopPolicy'):
                asyncio.set_event_loop_policy(asyncio.WindowsSelectorEventLoopPolicy())
        except Exception:
            # If setting policy fails, try to create a new event loop
            try:
                loop = asyncio.new_event_loop()
                asyncio.set_event_loop(loop)
            except Exception:
                pass
    except OSError as e:
        # If asyncio import fails due to _overlapped, monkey-patch it
        if '10106' in str(e) or '_overlapped' in str(e):
            # Create a dummy asyncio module to prevent import errors
            class DummyAsyncio:
                def __getattr__(self, name):
                    return None
            import sys
            sys.modules['asyncio'] = DummyAsyncio()
    except Exception:
        pass

# Add user site-packages to path (for packages installed with --user)
user_site_added = False
try:
    import site
    user_site = site.getusersitepackages()
    if user_site and os.path.isdir(user_site) and user_site not in sys.path:
        sys.path.insert(0, user_site)
        user_site_added = True
except Exception:
    pass

if not user_site_added:
    import os
    home = (os.environ.get('USERPROFILE') or 
            os.environ.get('HOME') or 
            os.path.expanduser('~'))
    if home:
        python_version = f"{sys.version_info.major}{sys.version_info.minor}"
        common_paths = [
            os.path.join(home, 'AppData', 'Roaming', 'Python', f'Python{python_version}', 'site-packages'),
            os.path.join(home, 'AppData', 'Roaming', 'Python', 'Python312', 'site-packages'),
        ]
        for path in common_paths:
            if os.path.isdir(path) and path not in sys.path:
                sys.path.insert(0, path)
                break

import pandas as pd
import numpy as np
import os
import logging
from pathlib import Path
from sklearn.model_selection import train_test_split
from sklearn.preprocessing import LabelEncoder, StandardScaler
import pickle
import json

# Add parent directory to path
sys.path.insert(0, str(Path(__file__).parent.parent))

logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)


class DatasetPreparator:
    """Prepare dataset for machine learning"""
    
    def __init__(self, input_file: str, output_dir: str = "ml/datasets"):
        """
        Initialize dataset preparator
        
        Args:
            input_file: Path to input CSV file
            output_dir: Directory to save processed datasets
        """
        self.input_file = input_file
        self.output_dir = Path(output_dir)
        self.output_dir.mkdir(parents=True, exist_ok=True)
        
        # Encoders and scalers (to be fitted)
        self.label_encoders = {}
        self.scaler = StandardScaler()
        self.feature_names = []
        
        # Data
        self.df_raw = None
        self.df_cleaned = None
        self.df_encoded = None
        self.X_train = None
        self.X_val = None
        self.X_test = None
        self.y_train = None
        self.y_val = None
        self.y_test = None
    
    def load_data(self) -> pd.DataFrame:
        """Load data from CSV file"""
        logger.info(f"Loading data from {self.input_file}")
        
        if not os.path.exists(self.input_file):
            raise FileNotFoundError(f"Input file not found: {self.input_file}")
        
        try:
            # Try different encodings
            encodings = ['utf-8', 'latin-1', 'iso-8859-1']
            df = None
            
            for encoding in encodings:
                try:
                    df = pd.read_csv(self.input_file, encoding=encoding)
                    logger.info(f"Successfully loaded with {encoding} encoding")
                    break
                except UnicodeDecodeError:
                    continue
            
            if df is None:
                raise ValueError("Could not read CSV with any encoding")
            
            # Normalize column names to lowercase for consistency
            # Map common uppercase column names
            column_mapping = {
                'URL': 'url',
                'TYPE': 'type',
                'PA': 'pa',
                'DA': 'da',
                'STATUS': 'status',
            }
            df.rename(columns=column_mapping, inplace=True)
            
            logger.info(f"Loaded {len(df)} rows, {len(df.columns)} columns")
            logger.info(f"Columns: {list(df.columns)}")
            
            self.df_raw = df
            return df
            
        except Exception as e:
            logger.error(f"Error loading data: {e}")
            raise
    
    def clean_data(self) -> pd.DataFrame:
        """Clean and preprocess data"""
        logger.info("Cleaning data...")
        
        if self.df_raw is None:
            raise ValueError("Data not loaded. Call load_data() first.")
        
        df = self.df_raw.copy()
        
        # 1. Remove completely empty rows
        initial_rows = len(df)
        df = df.dropna(how='all')
        logger.info(f"Removed {initial_rows - len(df)} completely empty rows")
        
        # 2. Extract domain from URL if domain column doesn't exist
        if 'domain' not in df.columns and 'url' in df.columns:
            logger.info("Extracting domain from URL...")
            df['domain'] = df['url'].apply(self._extract_domain)
        
        # 3. Remove duplicate domains (keep first occurrence)
        if 'domain' in df.columns:
            initial_rows = len(df)
            df = df.drop_duplicates(subset=['domain'], keep='first')
            logger.info(f"Removed {initial_rows - len(df)} duplicate domains")
        
        # 4. Handle missing PA/DA values
        logger.info("Handling missing PA/DA values...")
        
        # Check for PA/DA columns (case-insensitive)
        pa_col = None
        da_col = None
        
        for col in df.columns:
            col_lower = col.lower()
            if 'pa' in col_lower and 'page' in col_lower or col_lower == 'pa':
                pa_col = col
            if 'da' in col_lower and 'domain' in col_lower or col_lower == 'da':
                da_col = col
        
        if pa_col:
            pa_missing = df[pa_col].isna().sum()
            if pa_missing > 0:
                # Strategy: Fill with median, or 0 if all missing
                pa_median = df[pa_col].median()
                if pd.isna(pa_median):
                    df[pa_col] = df[pa_col].fillna(0)
                    logger.info(f"Filled {pa_missing} missing PA values with 0 (no valid data)")
                else:
                    df[pa_col] = df[pa_col].fillna(pa_median)
                    logger.info(f"Filled {pa_missing} missing PA values with median ({pa_median:.1f})")
        
        if da_col:
            da_missing = df[da_col].isna().sum()
            if da_missing > 0:
                # Strategy: Fill with median, or 0 if all missing
                da_median = df[da_col].median()
                if pd.isna(da_median):
                    df[da_col] = df[da_col].fillna(0)
                    logger.info(f"Filled {da_missing} missing DA values with 0 (no valid data)")
                else:
                    df[da_col] = df[da_col].fillna(da_median)
                    logger.info(f"Filled {da_missing} missing DA values with median ({da_median:.1f})")
        
        # 5. Normalize categorical fields
        logger.info("Normalizing categorical fields...")
        
        # Normalize site_type (handle variations)
        site_type_col = None
        for col in df.columns:
            if 'site_type' in col.lower() or 'type' in col.lower():
                if 'action' not in col.lower() and 'task' not in col.lower():
                    site_type_col = col
                    break
        
        if site_type_col:
            # Normalize values
            df[site_type_col] = df[site_type_col].str.lower().str.strip()
            # Map variations
            type_mapping = {
                'guestposting': 'guest',
                'guestpost': 'guest',
                'guest_post': 'guest',
                'guest': 'guest',
                'comment': 'comment',
                'profile': 'profile',
                'forum': 'forum',
                'other': 'other',
            }
            df[site_type_col] = df[site_type_col].map(type_mapping).fillna('other')
            logger.info(f"Normalized {site_type_col} values")
        
        # Normalize status field
        status_col = None
        for col in df.columns:
            if col.lower() == 'status':
                status_col = col
                break
        
        if status_col:
            df[status_col] = df[status_col].str.lower().str.strip()
            logger.info(f"Normalized {status_col} values")
        
        # 6. Handle target variable (success/outcome or action_type)
        target_col = None
        for col in df.columns:
            col_lower = col.lower()
            if col_lower in ['success', 'result', 'outcome', 'label', 'y', 'action_type', 'action_attempted', 'type']:
                target_col = col
                break
        
        if target_col:
            # Normalize target values
            df[target_col] = df[target_col].astype(str).str.lower().str.strip()
            
            # Check if it's action_type/type (multiclass) or success (binary)
            if 'action' in target_col.lower() or target_col.lower() == 'type':
                # For action_type/type, keep as categorical (will be encoded later)
                logger.info(f"Found multiclass target variable: {target_col}")
                logger.info(f"Unique values: {df[target_col].unique()}")
            else:
                # Map to binary for success/outcome columns
                target_mapping = {
                    'success': 1,
                    'true': 1,
                    '1': 1,
                    'yes': 1,
                    'failed': 0,
                    'false': 0,
                    '0': 0,
                    'no': 0,
                    'error': 0,
                }
                df[target_col] = df[target_col].map(target_mapping)
                # Fill any unmapped values with 0
                df[target_col] = df[target_col].fillna(0).astype(int)
                logger.info(f"Normalized binary target variable {target_col}")
        
        # 7. Remove rows with missing critical features
        critical_cols = []
        if pa_col:
            critical_cols.append(pa_col)
        if da_col:
            critical_cols.append(da_col)
        if target_col:
            critical_cols.append(target_col)
        
        if critical_cols:
            initial_rows = len(df)
            df = df.dropna(subset=critical_cols)
            logger.info(f"Removed {initial_rows - len(df)} rows with missing critical features")
        
        self.df_cleaned = df
        logger.info(f"Cleaned dataset: {len(df)} rows, {len(df.columns)} columns")
        
        return df
    
    def engineer_features(self) -> pd.DataFrame:
        """
        Engineer additional features from existing data
        
        Returns:
            DataFrame with engineered features
        """
        if self.df_cleaned is None:
            raise ValueError("Data not cleaned. Call clean_data() first.")
        
        logger.info("Engineering additional features...")
        try:
            df = self.df_cleaned.copy()
        except Exception as e:
            logger.error(f"Error copying cleaned dataframe: {e}")
            raise
        
        # 1. PA/DA derived features
        pa_col = None
        da_col = None
        for col in df.columns:
            col_lower = col.lower()
            if 'pa' in col_lower and 'page' in col_lower or col_lower == 'pa':
                pa_col = col
            if 'da' in col_lower and 'domain' in col_lower or col_lower == 'da':
                da_col = col
        
        if pa_col and da_col:
            # Ensure numeric
            df[pa_col] = pd.to_numeric(df[pa_col], errors='coerce').fillna(0)
            df[da_col] = pd.to_numeric(df[da_col], errors='coerce').fillna(0)
            
            # Derived features
            df['pa_da_sum'] = df[pa_col] + df[da_col]
            df['pa_da_diff'] = (df[pa_col] - df[da_col]).abs()
            df['pa_da_product'] = df[pa_col] * df[da_col]
            df['pa_da_ratio'] = df[pa_col] / (df[da_col] + 1)  # Avoid division by zero
            df['pa_squared'] = df[pa_col] ** 2
            df['da_squared'] = df[da_col] ** 2
            df['pa_da_mean'] = (df[pa_col] + df[da_col]) / 2
            df['pa_da_max'] = df[[pa_col, da_col]].max(axis=1)
            df['pa_da_min'] = df[[pa_col, da_col]].min(axis=1)
            
            logger.info("Added PA/DA derived features: pa_da_sum, pa_da_diff, pa_da_product, pa_da_ratio, pa_squared, da_squared, pa_da_mean, pa_da_max, pa_da_min")
        
        # 2. Domain features
        domain_col = None
        for col in df.columns:
            if col.lower() == 'domain':
                domain_col = col
                break
        
        if domain_col:
            try:
                df['domain_length'] = df[domain_col].astype(str).str.len()
                df['domain_has_subdomain'] = df[domain_col].astype(str).str.contains(r'\.', regex=True, na=False)
                df['domain_num_dots'] = df[domain_col].astype(str).str.count('\.')
                df['domain_has_hyphen'] = df[domain_col].astype(str).str.contains('-', na=False)
                
                # Extract TLD (handle empty domains)
                tld_series = df[domain_col].astype(str).str.split('.').str[-1]
                df['tld'] = tld_series.fillna('')
                df['tld_length'] = df['tld'].str.len().fillna(0)
                
                logger.info("Added domain features: domain_length, domain_has_subdomain, domain_num_dots, domain_has_hyphen, tld, tld_length")
            except Exception as e:
                logger.warning(f"Error creating domain features: {e}")
        
        # 3. URL features
        url_col = None
        for col in df.columns:
            if col.lower() == 'url':
                url_col = col
                break
        
        if url_col:
            df['url_length'] = df[url_col].astype(str).str.len()
            df['url_has_query'] = df[url_col].astype(str).str.contains('\?', regex=True, na=False)
            df['url_has_fragment'] = df[url_col].astype(str).str.contains('#', na=False)
            df['url_has_path'] = df[url_col].astype(str).str.contains('/', na=False)
            
            # Extract path depth
            df['url_path_depth'] = df[url_col].astype(str).str.split('/').str.len() - 1
            df['url_path_depth'] = df['url_path_depth'].fillna(0)
            
            # HTTPS
            df['url_is_https'] = df[url_col].astype(str).str.startswith('https://', na=False)
            
            logger.info("Added URL features: url_length, url_has_query, url_has_fragment, url_has_path, url_path_depth, url_is_https")
        
        # 4. Status features (if status column exists)
        status_col = None
        for col in df.columns:
            if col.lower() == 'status':
                status_col = col
                break
        
        if status_col:
            try:
                # Create binary features for common status values
                status_values = df[status_col].astype(str).str.lower().fillna('').unique()
                added_status_features = []
                for status_val in ['live', 'active', 'pending', 'inactive', 'banned']:
                    if status_val in status_values:
                        df[f'status_is_{status_val}'] = (df[status_col].astype(str).str.lower().fillna('') == status_val).astype(int)
                        added_status_features.append(status_val)
                
                if added_status_features:
                    logger.info(f"Added status binary features for: {added_status_features}")
            except Exception as e:
                logger.warning(f"Error creating status features: {e}")
        
        # 5. Time-based features (if timestamp columns exist)
        for time_col in ['created_at', 'updated_at', 'timestamp', 'date']:
            if time_col in df.columns:
                try:
                    df[time_col] = pd.to_datetime(df[time_col], errors='coerce')
                    df[f'{time_col}_hour'] = df[time_col].dt.hour.fillna(0)
                    df[f'{time_col}_day_of_week'] = df[time_col].dt.dayofweek.fillna(0)
                    df[f'{time_col}_month'] = df[time_col].dt.month.fillna(1)
                    df[f'{time_col}_is_weekend'] = (df[time_col].dt.dayofweek >= 5).fillna(False).astype(int)
                    logger.info(f"Added time-based features from {time_col}")
                except Exception as e:
                    logger.warning(f"Could not extract time features from {time_col}: {e}")
        
        try:
            self.df_cleaned = df
            logger.info(f"Feature engineering complete. Dataset now has {len(df.columns)} columns")
            return df
        except Exception as e:
            logger.error(f"Error in feature engineering: {e}")
            # Return original dataframe if feature engineering fails
            logger.warning("Returning original dataframe without engineered features")
            return self.df_cleaned
    
    def encode_features(self) -> pd.DataFrame:
        """Encode categorical features"""
        logger.info("Encoding categorical features...")
        
        if self.df_cleaned is None:
            raise ValueError("Data not cleaned. Call clean_data() first.")
        
        df = self.df_cleaned.copy()
        
        # Identify categorical columns
        categorical_cols = []
        numeric_cols = []
        
        for col in df.columns:
            col_lower = col.lower()
            # Skip target, ID, and URL columns
            if col_lower in ['id', 'url', 'domain', 'success', 'result', 'outcome', 'label', 'y', 'action_type', 'action_attempted', 'type']:
                continue
            
            # Check if categorical
            if df[col].dtype == 'object' or df[col].dtype.name == 'category':
                categorical_cols.append(col)
            elif df[col].dtype in ['int64', 'float64', 'int32', 'float32']:
                numeric_cols.append(col)
        
        logger.info(f"Categorical columns: {categorical_cols}")
        logger.info(f"Numeric columns: {numeric_cols}")
        
        # Encode categorical features
        encoded_features = []
        
        for col in categorical_cols:
            # Check cardinality
            unique_count = df[col].nunique()
            
            if unique_count <= 2:
                # Binary: Use label encoding
                logger.info(f"Label encoding {col} (binary: {unique_count} values)")
                le = LabelEncoder()
                df[f'{col}_encoded'] = le.fit_transform(df[col].astype(str).fillna('unknown'))
                self.label_encoders[col] = le
                encoded_features.append(f'{col}_encoded')
            elif unique_count <= 10:
                # Low cardinality: Use one-hot encoding
                logger.info(f"One-hot encoding {col} ({unique_count} values)")
                dummies = pd.get_dummies(df[col], prefix=col, dummy_na=True)
                df = pd.concat([df, dummies], axis=1)
                encoded_features.extend(dummies.columns.tolist())
            else:
                # High cardinality: Use label encoding (could use target encoding in future)
                logger.info(f"Label encoding {col} (high cardinality: {unique_count} values)")
                le = LabelEncoder()
                df[f'{col}_encoded'] = le.fit_transform(df[col].astype(str).fillna('unknown'))
                self.label_encoders[col] = le
                encoded_features.append(f'{col}_encoded')
        
        # Keep numeric columns
        all_features = numeric_cols + encoded_features
        
        # Store feature names
        self.feature_names = all_features
        
        self.df_encoded = df
        logger.info(f"Encoded dataset: {len(df)} rows, {len(df.columns)} columns")
        logger.info(f"Feature columns: {len(all_features)}")
        
        return df
    
    def split_dataset(self, test_size: float = 0.15, val_size: float = 0.15, random_state: int = 42):
        """
        Split dataset into train/val/test sets
        
        Args:
            test_size: Proportion for test set (default: 0.15)
            val_size: Proportion for validation set (default: 0.15)
            random_state: Random seed for reproducibility
        """
        logger.info(f"Splitting dataset: train={1-test_size-val_size:.0%}, val={val_size:.0%}, test={test_size:.0%}")
        
        if self.df_encoded is None:
            raise ValueError("Data not encoded. Call encode_features() first.")
        
        df = self.df_encoded
        
        # Find target column
        target_col = None
        for col in df.columns:
            col_lower = col.lower()
            if col_lower in ['success', 'result', 'outcome', 'label', 'y', 'action_type', 'action_attempted', 'type']:
                target_col = col
                break
        
        if target_col is None:
            raise ValueError(
                "Target column not found. Expected: success, result, outcome, label, y, action_type, action_attempted, or type. "
                f"Available columns: {list(df.columns)}"
            )
        
        # Prepare features and target
        X = df[self.feature_names].copy()
        y = df[target_col].copy()
        
        # Handle any remaining NaN values in features
        X = X.fillna(0)
        
        # First split: train+val vs test
        X_temp, X_test, y_temp, y_test = train_test_split(
            X, y, test_size=test_size, random_state=random_state, stratify=y
        )
        
        # Second split: train vs val
        val_size_adjusted = val_size / (1 - test_size)  # Adjust for already removed test set
        X_train, X_val, y_train, y_val = train_test_split(
            X_temp, y_temp, test_size=val_size_adjusted, random_state=random_state, stratify=y_temp
        )
        
        # Scale features
        logger.info("Scaling features...")
        X_train_scaled = pd.DataFrame(
            self.scaler.fit_transform(X_train),
            columns=X_train.columns,
            index=X_train.index
        )
        X_val_scaled = pd.DataFrame(
            self.scaler.transform(X_val),
            columns=X_val.columns,
            index=X_val.index
        )
        X_test_scaled = pd.DataFrame(
            self.scaler.transform(X_test),
            columns=X_test.columns,
            index=X_test.index
        )
        
        self.X_train = X_train_scaled
        self.X_val = X_val_scaled
        self.X_test = X_test_scaled
        self.y_train = y_train
        self.y_val = y_val
        self.y_test = y_test
        
        # Check if target is numeric/binary for logging
        is_binary = False
        try:
            # Check if y_train is numeric
            if pd.api.types.is_numeric_dtype(y_train):
                unique_vals = y_train.unique()
                # Check if it's binary (only 0 and 1, or True/False)
                if len(unique_vals) <= 2 and all(v in [0, 1, 0.0, 1.0, True, False] for v in unique_vals):
                    is_binary = True
            else:
                # Try to convert to numeric
                y_train_numeric = pd.to_numeric(y_train, errors='coerce')
                if not y_train_numeric.isna().all():
                    unique_vals = y_train_numeric.dropna().unique()
                    if len(unique_vals) <= 2 and all(v in [0, 1, 0.0, 1.0] for v in unique_vals):
                        is_binary = True
                        # Convert to int for consistency
                        y_train = y_train_numeric.fillna(0).astype(int)
                        y_val = pd.to_numeric(y_val, errors='coerce').fillna(0).astype(int)
                        y_test = pd.to_numeric(y_test, errors='coerce').fillna(0).astype(int)
                        # Update stored values
                        self.y_train = y_train
                        self.y_val = y_val
                        self.y_test = y_test
        except Exception:
            pass
        
        if is_binary:
            # Ensure we have numeric values for sum calculation
            y_train_sum = int(y_train.sum()) if pd.api.types.is_numeric_dtype(y_train) else 0
            y_val_sum = int(y_val.sum()) if pd.api.types.is_numeric_dtype(y_val) else 0
            y_test_sum = int(y_test.sum()) if pd.api.types.is_numeric_dtype(y_test) else 0
            logger.info(f"Train set: {len(X_train)} samples ({y_train_sum} positive, {len(y_train) - y_train_sum} negative)")
            logger.info(f"Val set: {len(X_val)} samples ({y_val_sum} positive, {len(y_val) - y_val_sum} negative)")
            logger.info(f"Test set: {len(X_test)} samples ({y_test_sum} positive, {len(y_test) - y_test_sum} negative)")
        else:
            # Multiclass or non-numeric target
            logger.info(f"Train set: {len(X_train)} samples (target: {target_col})")
            logger.info(f"Val set: {len(X_val)} samples (target: {target_col})")
            logger.info(f"Test set: {len(X_test)} samples (target: {target_col})")
            if hasattr(y_train, 'value_counts'):
                logger.info(f"Train target distribution: {y_train.value_counts().to_dict()}")
        
        return {
            'X_train': X_train_scaled,
            'X_val': X_val_scaled,
            'X_test': X_test_scaled,
            'y_train': y_train,
            'y_val': y_val,
            'y_test': y_test,
        }
    
    def save_datasets(self):
        """Save processed datasets and encoders"""
        logger.info("Saving datasets and encoders...")
        
        if self.X_train is None:
            raise ValueError("Dataset not split. Call split_dataset() first.")
        
        # Save datasets
        self.X_train.to_csv(self.output_dir / 'X_train.csv', index=False)
        self.X_val.to_csv(self.output_dir / 'X_val.csv', index=False)
        self.X_test.to_csv(self.output_dir / 'X_test.csv', index=False)
        self.y_train.to_csv(self.output_dir / 'y_train.csv', index=False, header=['target'])
        self.y_val.to_csv(self.output_dir / 'y_val.csv', index=False, header=['target'])
        self.y_test.to_csv(self.output_dir / 'y_test.csv', index=False, header=['target'])
        
        logger.info(f"Saved datasets to {self.output_dir}")
        
        # Save encoders and scaler
        encoders_file = self.output_dir / 'encoders.pkl'
        with open(encoders_file, 'wb') as f:
            pickle.dump({
                'label_encoders': self.label_encoders,
                'scaler': self.scaler,
                'feature_names': self.feature_names,
            }, f)
        
        logger.info(f"Saved encoders to {encoders_file}")
        
        # Save metadata
        # Check if target is binary/numeric
        is_binary = False
        try:
            if pd.api.types.is_numeric_dtype(self.y_train):
                unique_vals = self.y_train.unique()
                if len(unique_vals) <= 2 and all(v in [0, 1, 0.0, 1.0, True, False] for v in unique_vals):
                    is_binary = True
        except Exception:
            pass
        
        metadata = {
            'feature_names': self.feature_names,
            'num_features': len(self.feature_names),
            'train_samples': len(self.X_train),
            'val_samples': len(self.X_val),
            'test_samples': len(self.X_test),
        }
        
        if is_binary:
            # Binary classification - calculate positive/negative counts
            train_positive = int(self.y_train.sum()) if pd.api.types.is_numeric_dtype(self.y_train) else 0
            val_positive = int(self.y_val.sum()) if pd.api.types.is_numeric_dtype(self.y_val) else 0
            test_positive = int(self.y_test.sum()) if pd.api.types.is_numeric_dtype(self.y_test) else 0
            
            metadata.update({
                'train_positive': train_positive,
                'train_negative': int(len(self.y_train) - train_positive),
                'val_positive': val_positive,
                'val_negative': int(len(self.y_val) - val_positive),
                'test_positive': test_positive,
                'test_negative': int(len(self.y_test) - test_positive),
                'target_type': 'binary',
            })
        else:
            # Multiclass or non-numeric target - save class distribution
            if hasattr(self.y_train, 'value_counts'):
                # Convert to dict and ensure JSON serializable
                train_dist = self.y_train.value_counts().to_dict()
                val_dist = self.y_val.value_counts().to_dict()
                test_dist = self.y_test.value_counts().to_dict()
                
                # Convert any non-serializable types to strings
                train_dist = {str(k): int(v) for k, v in train_dist.items()}
                val_dist = {str(k): int(v) for k, v in val_dist.items()}
                test_dist = {str(k): int(v) for k, v in test_dist.items()}
                
                metadata.update({
                    'train_class_distribution': train_dist,
                    'val_class_distribution': val_dist,
                    'test_class_distribution': test_dist,
                    'target_type': 'multiclass',
                    'num_classes': len(self.y_train.unique()),
                })
            else:
                metadata.update({
                    'target_type': 'unknown',
                })
        
        metadata_file = self.output_dir / 'metadata.json'
        with open(metadata_file, 'w') as f:
            json.dump(metadata, f, indent=2)
        
        logger.info(f"Saved metadata to {metadata_file}")
    
    def _extract_domain(self, url: str) -> str:
        """Extract domain from URL"""
        if pd.isna(url):
            return 'unknown'
        
        try:
            from urllib.parse import urlparse
            parsed = urlparse(str(url))
            domain = parsed.netloc or parsed.path.split('/')[0]
            if ':' in domain:
                domain = domain.split(':')[0]
            return domain or 'unknown'
        except:
            return 'unknown'
    
    def get_summary(self) -> dict:
        """Get summary statistics"""
        if self.df_cleaned is None:
            return {}
        
        summary = {
            'raw_rows': len(self.df_raw) if self.df_raw is not None else 0,
            'cleaned_rows': len(self.df_cleaned),
            'encoded_rows': len(self.df_encoded) if self.df_encoded is not None else 0,
            'features': len(self.feature_names) if self.feature_names else 0,
            'train_samples': len(self.X_train) if self.X_train is not None else 0,
            'val_samples': len(self.X_val) if self.X_val is not None else 0,
            'test_samples': len(self.X_test) if self.X_test is not None else 0,
        }
        
        return summary


def main():
    """Main function"""
    import argparse
    
    parser = argparse.ArgumentParser(description='Prepare dataset for ML training')
    parser.add_argument('--input', '-i', required=True, help='Input CSV file path')
    parser.add_argument('--output', '-o', default='ml/datasets', help='Output directory')
    parser.add_argument('--test-size', type=float, default=0.15, help='Test set proportion')
    parser.add_argument('--val-size', type=float, default=0.15, help='Validation set proportion')
    parser.add_argument('--random-state', type=int, default=42, help='Random seed')
    
    args = parser.parse_args()
    
    # Create preparator
    preparator = DatasetPreparator(args.input, args.output)
    
    # Process dataset
    preparator.load_data()
    preparator.clean_data()
    preparator.encode_features()
    preparator.split_dataset(
        test_size=args.test_size,
        val_size=args.val_size,
        random_state=args.random_state
    )
    preparator.save_datasets()
    
    # Print summary
    summary = preparator.get_summary()
    logger.info("\n" + "="*50)
    logger.info("Dataset Preparation Summary")
    logger.info("="*50)
    logger.info(f"Raw rows: {summary['raw_rows']}")
    logger.info(f"Cleaned rows: {summary['cleaned_rows']}")
    logger.info(f"Features: {summary['features']}")
    logger.info(f"Train samples: {summary['train_samples']}")
    logger.info(f"Val samples: {summary['val_samples']}")
    logger.info(f"Test samples: {summary['test_samples']}")
    logger.info("="*50)


if __name__ == "__main__":
    main()

