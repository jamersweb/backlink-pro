# ML Dataset Preparation

This directory contains tools for preparing datasets for machine learning training.

## Files

- `prepare_dataset.py` - Main dataset preparation script
- `FEATURE_ENCODING_EXPLANATION.md` - Detailed explanation of encoding choices

## Quick Start

### Basic Usage

```bash
cd python/ml
python prepare_dataset.py --input training_backlinks_enriched.csv --output datasets
```

### With Custom Split

```bash
python prepare_dataset.py \
    --input training_backlinks_enriched.csv \
    --output datasets \
    --test-size 0.15 \
    --val-size 0.15 \
    --random-state 42
```

### From Python

```python
from ml.prepare_dataset import DatasetPreparator

# Initialize
preparator = DatasetPreparator(
    input_file='training_backlinks_enriched.csv',
    output_dir='ml/datasets'
)

# Process
preparator.load_data()
preparator.clean_data()
preparator.encode_features()
preparator.split_dataset(test_size=0.15, val_size=0.15)
preparator.save_datasets()

# Get summary
summary = preparator.get_summary()
print(summary)
```

## Expected CSV Format

The script expects a CSV file with columns like:

- `url` - Backlink URL (optional, used to extract domain)
- `domain` - Domain name (optional, extracted from URL if missing)
- `pa` or `Page Authority` - Page Authority score (0-100)
- `da` or `Domain Authority` - Domain Authority score (0-100)
- `site_type` or `type` - Site type (comment, profile, forum, guest)
- `status` - Status (active, inactive, banned)
- `success` or `result` or `outcome` - Target variable (success/failed, 1/0, true/false)

The script is flexible and will auto-detect column names (case-insensitive).

## Output

The script creates:

- `X_train.csv`, `X_val.csv`, `X_test.csv` - Feature matrices
- `y_train.csv`, `y_val.csv`, `y_test.csv` - Target vectors
- `encoders.pkl` - Saved encoders and scaler (for inference)
- `metadata.json` - Dataset statistics

## Features

- ✅ Handles missing PA/DA values (median imputation)
- ✅ Removes duplicate domains
- ✅ Normalizes categorical fields
- ✅ Encodes categories (label/one-hot based on cardinality)
- ✅ Stratified train/val/test split (70/15/15)
- ✅ Feature scaling (StandardScaler)
- ✅ Saves encoders for inference

## See Also

- `FEATURE_ENCODING_EXPLANATION.md` - Detailed encoding strategy explanation

