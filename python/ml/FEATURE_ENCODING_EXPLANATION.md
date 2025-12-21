# Feature Encoding Choices Explanation

This document explains the feature encoding strategies used in the dataset preparation script.

## Overview

The dataset preparation script uses different encoding strategies based on the cardinality and nature of categorical features. This ensures optimal representation for machine learning models.

## Encoding Strategies

### 1. Label Encoding

**Used for:**
- Binary categorical variables (2 unique values)
- High cardinality categorical variables (>10 unique values)

**Example:**
- `status`: `active` → 0, `inactive` → 1
- `domain_encoded`: Each unique domain gets a unique integer

**Rationale:**
- **Binary variables**: Only 2 values, so label encoding is efficient and doesn't add dimensionality
- **High cardinality**: One-hot encoding would create too many features (curse of dimensionality). Label encoding preserves information while keeping dimensionality manageable

**Pros:**
- Preserves ordinal relationships (if any)
- Low dimensionality
- Fast encoding/decoding

**Cons:**
- May introduce artificial ordering for nominal categories
- For high cardinality, may not capture relationships well

### 2. One-Hot Encoding

**Used for:**
- Low cardinality categorical variables (3-10 unique values)

**Example:**
- `site_type`: 
  - `comment` → [1, 0, 0, 0]
  - `profile` → [0, 1, 0, 0]
  - `forum` → [0, 0, 1, 0]
  - `guest` → [0, 0, 0, 1]

**Rationale:**
- **Low cardinality**: Creates a manageable number of features (3-10)
- **Nominal categories**: No inherent ordering, so one-hot encoding avoids artificial ordinal relationships
- **Interpretability**: Each category gets its own feature, making model interpretation easier

**Pros:**
- No artificial ordering
- Each category is independent
- Good for tree-based models (can split on individual categories)
- Interpretable

**Cons:**
- Increases dimensionality
- Can create sparse matrices
- Not ideal for very high cardinality

### 3. Numerical Features

**Handling:**
- PA (Page Authority): 0-100 scale, kept as-is
- DA (Domain Authority): 0-100 scale, kept as-is
- Other numeric features: Kept as continuous values

**Missing Value Strategy:**
- **Median imputation**: For PA/DA, missing values are filled with the median
- **Rationale**: Median is robust to outliers and preserves the distribution better than mean
- **Fallback**: If all values are missing, fill with 0

**Scaling:**
- All features are standardized using `StandardScaler` (mean=0, std=1)
- Applied after train/val/test split to prevent data leakage
- Only fit on training data, then transform validation and test sets

## Specific Feature Encoding Decisions

### Site Type (`site_type`)

**Encoding:** One-hot encoding (if ≤10 values) or Label encoding (if >10)

**Reasoning:**
- Site types are nominal (no ordering)
- Typically 4-5 values: comment, profile, forum, guest, other
- One-hot encoding allows models to learn distinct patterns for each type

### Status (`status`)

**Encoding:** Label encoding

**Reasoning:**
- Usually binary or low cardinality (active, inactive, banned)
- If binary, label encoding is sufficient
- If more values, one-hot could be used, but label encoding is simpler

### Domain

**Encoding:** Label encoding (after deduplication)

**Reasoning:**
- Very high cardinality (potentially thousands of unique domains)
- One-hot encoding would create thousands of features
- Label encoding preserves domain identity while keeping dimensionality low
- **Note**: For better performance, could use target encoding or embedding in future

### PA/DA (Page/Domain Authority)

**Encoding:** Numerical (continuous)

**Reasoning:**
- Already numeric (0-100 scale)
- Missing values filled with median
- Standardized after split

**Missing Value Handling:**
- **Strategy**: Median imputation
- **Why Median**: 
  - Robust to outliers
  - Preserves distribution better than mean
  - Works well for skewed distributions
- **Fallback**: If all values missing, use 0 (assumes no authority data available)

## Feature Engineering

### Derived Features (if present in data)

The script preserves any derived features that might be in the dataset:
- `pa_da_sum`: Sum of PA and DA
- `pa_da_ratio`: Ratio of PA to DA
- Other computed features

### Domain Extraction

- Extracted from URL if domain column doesn't exist
- Used for deduplication
- Normalized (lowercase, remove ports)

## Data Splitting Strategy

### Stratified Splitting

**Used:** `stratify=y` in train_test_split

**Reasoning:**
- Ensures train/val/test sets have similar class distributions
- Prevents imbalanced splits (e.g., all positives in test set)
- Critical for imbalanced datasets

### Split Proportions

**Default:** 70% train, 15% validation, 15% test

**Reasoning:**
- **70% train**: Sufficient data for model training
- **15% validation**: Enough for hyperparameter tuning and early stopping
- **15% test**: Sufficient for final evaluation, not too large to waste data

**Alternative splits:**
- For small datasets (<1000 samples): 80/10/10
- For large datasets (>10000 samples): 60/20/20

## Preprocessing Pipeline Order

1. **Load data** → Handle encoding issues
2. **Remove empty rows** → Clean obvious noise
3. **Extract domain** → Create domain column if missing
4. **Remove duplicate domains** → Keep first occurrence
5. **Handle missing PA/DA** → Median imputation
6. **Normalize categoricals** → Lowercase, strip, map variations
7. **Normalize target** → Binary encoding
8. **Remove rows with missing critical features** → Final cleanup
9. **Encode features** → Label/one-hot encoding
10. **Split dataset** → Train/val/test with stratification
11. **Scale features** → StandardScaler (fit on train only)

## Why This Approach?

### 1. Prevents Data Leakage

- Scaling is fit only on training data
- Validation and test sets are transformed using training statistics
- No information from future data leaks into training

### 2. Handles Real-World Data Issues

- Missing values (common in PA/DA data)
- Duplicate domains (same site, multiple entries)
- Encoding variations (GUESTPOSTING vs guestposting)
- Mixed data types

### 3. Optimizes for ML Models

- **Tree-based models** (Random Forest, XGBoost): Work well with one-hot encoded low-cardinality features
- **Linear models** (Logistic Regression): Benefit from standardized features
- **Neural networks**: Require standardized inputs

### 4. Maintains Interpretability

- Feature names preserved
- Encoders saved for inverse transformation
- Metadata saved for reference

## Future Improvements

1. **Target Encoding**: For high-cardinality categoricals (domains), could use target encoding
2. **Embeddings**: For domains, could learn embeddings
3. **Feature Selection**: Remove low-variance or highly correlated features
4. **Outlier Handling**: Detect and handle outliers in PA/DA
5. **Balancing**: Handle class imbalance with SMOTE or undersampling
6. **Cross-Validation**: Use k-fold CV for small datasets

## Usage Example

```python
from ml.prepare_dataset import DatasetPreparator

preparator = DatasetPreparator('training_backlinks_enriched.csv')
preparator.load_data()
preparator.clean_data()
preparator.encode_features()
preparator.split_dataset(test_size=0.15, val_size=0.15)
preparator.save_datasets()
```

## Output Files

- `X_train.csv`, `X_val.csv`, `X_test.csv`: Feature matrices
- `y_train.csv`, `y_val.csv`, `y_test.csv`: Target vectors
- `encoders.pkl`: Saved encoders and scaler for inference
- `metadata.json`: Dataset statistics and feature names

