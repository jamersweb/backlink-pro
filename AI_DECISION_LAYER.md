# AI Decision Layer for BacklinkPro

## Overview

The AI Decision Layer is a machine learning system that learns from historical backlink data and predicts which action type (comment, profile, forum, guest) has the highest probability of success before automation runs.

**Key Principle**: AI decides **WHAT** action to take, existing automation executes **HOW**.

## Architecture

### Components

1. **ML Predictor** (`python/ml_predictor.py`)
   - Trains ML models on historical backlink data
   - Predicts success probability for each action type
   - Uses scikit-learn (GradientBoostingClassifier) when available
   - Falls back to statistical analysis if ML libraries unavailable

2. **Decision Service** (`python/decision_service.py`)
   - Main service that uses ML predictor to make decisions
   - Recommends best action type for backlinks
   - Selects and ranks opportunities by success probability

3. **Enhanced Opportunity Selector** (`python/opportunity_selector.py`)
   - Integrated with AI decision layer
   - Automatically uses AI recommendations when selecting opportunities
   - Falls back to basic selection if AI unavailable

4. **Laravel API Endpoints** (`app/Http/Controllers/Api/MLController.php`)
   - `/api/ml/historical-data` - Exposes historical backlink data for training
   - `/api/ml/action-recommendation/{campaign_id}` - Basic recommendation endpoint

## How It Works

### 1. Training Phase

When the system starts or when retraining is requested:

1. Fetches historical data from Laravel API (tasks + opportunities with success/failure outcomes)
2. Extracts features:
   - Backlink metrics (PA, DA, site_type)
   - Historical success rates per backlink and action type
   - Campaign features (daily_limit, total_limit)
   - Time-based features (hour_of_day, day_of_week)
3. Trains separate ML models for each action type (comment, profile, forum, guest)
4. Stores models and statistics for later use

### 2. Decision Phase

When selecting an opportunity:

1. Gets available opportunities from API
2. For each opportunity, predicts success probability for each action type
3. Ranks opportunities by highest success probability
4. Returns top opportunities with AI recommendations

### 3. Integration with Automation

The AI decision layer integrates seamlessly:

- **Opportunity Selection**: When automation modules call `opportunity_selector.select_opportunity()`, AI recommendations are automatically used
- **No Changes to Automation**: Existing Playwright automation logic is unchanged
- **Graceful Fallback**: If AI is unavailable, system falls back to basic selection

## Features

### Machine Learning

- **Gradient Boosting Classifier** for each action type
- **Feature Engineering**: Extracts 15+ features from historical data
- **Model Persistence**: Saves trained models to disk for reuse
- **Incremental Learning**: Can retrain with new data

### Statistical Fallback

- If ML libraries unavailable, uses statistical analysis
- Calculates success rates per action type
- Adjusts probabilities based on PA/DA metrics

### Smart Recommendations

- Considers backlink characteristics (PA, DA, site_type)
- Considers campaign constraints (daily_limit, total_limit)
- Considers historical performance patterns
- Returns probability scores for all action types

## Usage

### Basic Usage

The AI decision layer is automatically used when selecting opportunities:

```python
from opportunity_selector import OpportunitySelector
from api_client import LaravelAPIClient

api_client = LaravelAPIClient(api_url, api_token)
selector = OpportunitySelector(api_client, use_ai=True)  # AI enabled by default

# Select opportunity - AI will recommend best action type
opportunity = selector.select_opportunity(
    campaign_id=1,
    task_type='comment',  # Can be overridden by AI
    use_ai_recommendation=True
)

# Opportunity now includes AI recommendations:
# - opportunity['ai_recommended_action_type']
# - opportunity['ai_success_probability']
# - opportunity['ai_metadata']
```

### Manual Decision Making

```python
from decision_service import DecisionService
from api_client import LaravelAPIClient

api_client = LaravelAPIClient(api_url, api_token)
decision_service = DecisionService(api_client)

# Get recommendation for a specific backlink
action_type, probability, metadata = decision_service.decide_action_type(
    campaign_id=1,
    backlink={
        'id': 123,
        'pa': 45,
        'da': 60,
        'site_type': 'comment',
    }
)

print(f"Recommended: {action_type} ({probability:.2%} success probability)")
```

### Retraining Models

```python
# Retrain with latest historical data
decision_service.retrain_models(force=True)
```

## API Endpoints

### GET /api/ml/historical-data

Fetches historical backlink data for ML training.

**Query Parameters:**
- `limit` (optional): Max records to fetch (default: 1000)
- `min_date` (optional): Filter by minimum date (ISO format)

**Response:**
```json
{
  "success": true,
  "count": 500,
  "data": [
    {
      "success": true,
      "created_at": "2024-01-15T10:30:00Z",
      "task": {
        "id": 123,
        "type": "comment",
        "status": "success"
      },
      "backlink": {
        "id": 456,
        "url": "https://example.com",
        "pa": 45,
        "da": 60,
        "site_type": "comment"
      },
      "campaign": {
        "id": 1,
        "daily_limit": 10,
        "total_limit": 100
      }
    }
  ]
}
```

## Configuration

### Environment Variables

No additional environment variables required. Uses existing:
- `LARAVEL_API_URL` - Laravel API base URL
- `LARAVEL_API_TOKEN` or `APP_API_TOKEN` - API authentication token

### Model Storage

Models are stored in `python/ml_models/`:
- `models.pkl` - Trained ML models
- `scalers.pkl` - Feature scalers
- `stats.pkl` - Statistical fallback data

## Dependencies

### Python Packages

Added to `requirements.txt`:
- `scikit-learn==1.3.2` - Machine learning library
- `numpy==1.24.3` - Numerical computing

### Optional

If scikit-learn is not available, the system falls back to statistical analysis. This ensures the system works even without ML libraries.

## Testing

Run the test script:

```bash
cd python
python test_ml_predictor.py
```

This will:
1. Fetch historical data from API
2. Train ML models
3. Test predictions
4. Test recommendations
5. Test decision service

## Performance

- **Training Time**: ~1-5 seconds for 500-1000 records
- **Prediction Time**: <10ms per prediction
- **Model Size**: ~100-500 KB per model

## Future Enhancements

Potential improvements:
1. **Real-time Learning**: Update models as new data arrives
2. **Feature Expansion**: Add more features (domain age, content type, etc.)
3. **Ensemble Methods**: Combine multiple models for better accuracy
4. **A/B Testing**: Compare AI recommendations vs. random selection
5. **Confidence Intervals**: Provide uncertainty estimates

## Notes

- **No Changes to Automation**: All Playwright automation logic remains unchanged
- **Backward Compatible**: System works with or without AI enabled
- **Production Ready**: Handles errors gracefully, falls back to basic selection if needed
- **Scalable**: Can handle thousands of historical records efficiently

