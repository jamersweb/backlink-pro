# ML Pipeline Implementation

## Overview

Complete ML pipeline for action selection and learning, from feature extraction to runtime inference.

## Components

### 1. Feature Extractor (`ml/feature_extractor.py`)

**Purpose:** Extract features from URLs and HTML for ML training

**Input:** CSV with URL, TYPE, PA, DA, STATUS

**Output:** `training_backlinks_enriched.csv`

**Extracted Features:**
- `domain` - Domain name
- `tld` - Top-level domain
- `url_path_depth` - URL path depth
- `https_enabled` - Boolean
- `platform_guess` - WordPress/XenForo/Disqus/Custom (via URL patterns + HTML)
- `site_type` - Blog/Forum/CMS
- `comment_supported` - Boolean
- `profile_supported` - Boolean
- `forum_supported` - Boolean
- `guest_supported` - Boolean
- `requires_login` - Boolean
- `registration_detected` - Boolean

**Features:**
- Uses `requests` + `BeautifulSoup` (no browser automation)
- Caching per-domain (saves HTML to `ml/cache/`)
- Lightweight HTML requests with retries
- Platform detection via URL patterns and HTML analysis

**Usage:**
```bash
python ml/feature_extractor.py input.csv output.csv [--limit N]
```

### 2. Dataset Preparation (`ml/prepare_dataset.py`)

**Purpose:** Normalize, encode, and split dataset

**Features:**
- Normalizes categorical fields
- Encodes categories (label/one-hot based on cardinality)
- Handles missing PA/DA (fills with median)
- Optional dedupe domains
- Splits 70/15/15 stratified

**Output:**
- `X_train.csv`, `X_val.csv`, `X_test.csv`
- `y_train.csv`, `y_val.csv`, `y_test.csv`
- `encoders.pkl` - Label encoders and scaler
- `metadata.json` - Dataset metadata

**Usage:**
```bash
python ml/prepare_dataset.py --input training_backlinks_enriched.csv --output ml/datasets
```

### 3. Model Training (`ml/train_action_model.py`)

**Purpose:** Train multiclass classifier for action prediction

**Model Preference:**
1. XGBoost (preferred)
2. LightGBM (fallback)
3. RandomForest (baseline)

**Target:** Multiclass classification (comment, profile, forum, guest)

**Output:** `ml/export_model.pkl`

**Model Contents:**
- Trained model
- Model type
- Label encoder
- Feature names
- Action classes
- Scaler (if used)

**Usage:**
```bash
python ml/train_action_model.py --dataset-dir ml/datasets --model-dir ml/models
```

### 4. Model Evaluation (`ml/evaluate_model.py`)

**Purpose:** Evaluate model performance

**Metrics:**
- Precision per class
- Confusion matrix
- Failure rate reduction simulation

**Output:**
- Evaluation report (console + file)
- Confusion matrix plot (if matplotlib available)

**Usage:**
```bash
python ml/evaluate_model.py --model ml/export_model.pkl --dataset-dir ml/datasets
```

### 5. AI Decision Engine (`ai_decision_engine.py`)

**Purpose:** Runtime inference for action prediction

**Input:** Site feature dict

**Output:** Ranked probabilities per action

**Example Output:**
```python
{
    "comment": 0.15,
    "profile": 0.67,
    "forum": 0.12,
    "guest": 0.06
}
```

**Features:**
- Fast inference (no browser interaction)
- Handles enriched features from feature_extractor
- Returns stable probability dict
- Singleton pattern for efficiency

**Usage:**
```python
from ai_decision_engine import get_engine

engine = get_engine()
probabilities = engine.predict({
    'pa': 45,
    'da': 60,
    'site_type': 'comment',
    'comment_supported': True,
    'platform_guess': 'wordpress',
    # ... other features
})
```

## Integration

### OpportunitySelector Integration

**Location:** `opportunity_selector.py`

**Flow:**
```
OpportunitySelector → AI Decision Engine → Action Selected → Agent Executes
```

**Code:**
```python
# In OpportunitySelector._select_with_ai_engine()
site_features = {
    'pa': opportunity.get('pa', 0),
    'da': opportunity.get('da', 0),
    'site_type': opportunity.get('site_type', 'unknown'),
    # Enriched features from feature_extractor (if available)
    'comment_supported': opportunity.get('comment_supported', False),
    'profile_supported': opportunity.get('profile_supported', False),
    'platform_guess': opportunity.get('platform_guess', 'unknown'),
    # ... other features
}

probabilities = self.ai_engine.predict(site_features)
best_action = max(probabilities.items(), key=lambda x: x[1])[0]

opportunity['ai_recommended_action_type'] = best_action
opportunity['ai_probability'] = probabilities[best_action]
opportunity['ai_probabilities'] = probabilities
```

### Worker Integration

**Location:** `worker.py`

**Flow:**
1. Worker receives task
2. OpportunitySelector uses AI Decision Engine
3. Agent executes with AI-recommended action
4. Results logged for learning

## Complete Pipeline Flow

### Training Phase

1. **Feature Extraction:**
   ```bash
   python ml/feature_extractor.py raw_data.csv training_backlinks_enriched.csv
   ```

2. **Dataset Preparation:**
   ```bash
   python ml/prepare_dataset.py --input training_backlinks_enriched.csv --output ml/datasets
   ```

3. **Model Training:**
   ```bash
   python ml/train_action_model.py --dataset-dir ml/datasets --model-dir ml/models
   ```

4. **Model Evaluation:**
   ```bash
   python ml/evaluate_model.py --model ml/export_model.pkl --dataset-dir ml/datasets
   ```

### Runtime Phase

1. **Load Model:**
   - AI Decision Engine loads `ml/export_model.pkl` on first use

2. **Feature Extraction (Runtime):**
   - OpportunitySelector extracts features from opportunity data
   - Can use enriched features if available from feature_extractor

3. **Inference:**
   - AI Decision Engine predicts probabilities
   - Best action selected

4. **Execution:**
   - Agent executes with selected action
   - Results logged for future learning

## Feature Engineering

### From Feature Extractor

**URL Features:**
- `domain` - Domain name
- `tld` - Top-level domain
- `url_path_depth` - URL path depth
- `https_enabled` - Boolean

**Platform Detection:**
- WordPress (wp-content, /wp-admin)
- XenForo (xenforo, /forums/)
- Disqus (disqus.com)
- Custom (fallback)

**Site Type Detection:**
- Blog (blog in URL/HTML)
- Forum (forum in URL/HTML)
- CMS (article, post, content)

**Feature Detection:**
- Comment support (comment forms, Disqus)
- Profile support (registration forms)
- Forum support (forum forms)
- Guest support (guest post forms)
- Login requirement (login forms, auth text)
- Registration (registration links/forms)

### Feature Encoding

**Categorical Features:**
- Binary (≤2 values): Label encoding
- Low cardinality (≤10 values): One-hot encoding
- High cardinality (>10 values): Label encoding

**Numeric Features:**
- StandardScaler normalization
- Missing values filled with median

## Acceptance Criteria

✅ **Model trains and exports**
- Feature extraction creates enriched CSV
- Dataset preparation normalizes and encodes
- Model training produces `ml/export_model.pkl`
- Model evaluation reports metrics

✅ **Runtime inference returns stable probability dict**
- AI Decision Engine loads model successfully
- Predictions return probabilities for all actions
- Probabilities sum to 1.0
- Integration with OpportunitySelector works

## Example Usage

### Training

```bash
# Step 1: Extract features
python ml/feature_extractor.py data/backlinks.csv ml/training_backlinks_enriched.csv

# Step 2: Prepare dataset
python ml/prepare_dataset.py --input ml/training_backlinks_enriched.csv --output ml/datasets

# Step 3: Train model
python ml/train_action_model.py --dataset-dir ml/datasets --model-dir ml/models

# Step 4: Evaluate
python ml/evaluate_model.py --model ml/export_model.pkl --dataset-dir ml/datasets
```

### Runtime

```python
from ai_decision_engine import get_engine

engine = get_engine()

# Predict for a site
site_features = {
    'pa': 45,
    'da': 60,
    'site_type': 'comment',
    'comment_supported': True,
    'platform_guess': 'wordpress',
    'url_path_depth': 2,
    'https_enabled': True,
}

probabilities = engine.predict(site_features)
# {'comment': 0.67, 'profile': 0.15, 'forum': 0.12, 'guest': 0.06}

best_action, best_prob = engine.get_best_action(site_features)
# ('comment', 0.67)
```

## Notes

- **Feature Extraction:** Lightweight, no browser automation
- **Caching:** HTML responses cached per-domain
- **Model Preference:** XGBoost > LightGBM > RandomForest
- **Stratified Split:** Maintains class distribution
- **Runtime Efficiency:** Singleton pattern, fast inference
- **Integration:** Seamless with OpportunitySelector and Agent

