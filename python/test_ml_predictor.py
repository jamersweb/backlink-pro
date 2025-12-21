"""
Test script for ML Predictor and Decision Service
"""

import os
import sys
import logging
from dotenv import load_dotenv

# Add parent directory to path
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

from api_client import LaravelAPIClient
from ml_predictor import BacklinkPredictor
from decision_service import DecisionService

# Load environment variables
load_dotenv(override=False)

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)

def main():
    """Test ML predictor and decision service"""
    
    # Get API credentials
    api_url = os.getenv('LARAVEL_API_URL', 'http://nginx')
    api_token = os.getenv('LARAVEL_API_TOKEN') or os.getenv('APP_API_TOKEN') or ''
    
    if not api_token:
        logger.error("API token not set. Please set LARAVEL_API_TOKEN or APP_API_TOKEN")
        return
    
    logger.info(f"Connecting to API: {api_url}")
    
    # Create API client
    api_client = LaravelAPIClient(api_url, api_token)
    
    # Test 1: Fetch historical data
    logger.info("\n=== Test 1: Fetching Historical Data ===")
    historical_data = api_client.get_historical_backlink_data(limit=100)
    logger.info(f"Fetched {len(historical_data)} historical records")
    
    if historical_data:
        logger.info(f"Sample record keys: {list(historical_data[0].keys())}")
        logger.info(f"Sample success: {historical_data[0].get('success')}")
    
    # Test 2: Train predictor
    logger.info("\n=== Test 2: Training ML Predictor ===")
    predictor = BacklinkPredictor()
    
    if historical_data:
        training_result = predictor.train(historical_data)
        logger.info(f"Training result: {training_result}")
    else:
        logger.warning("No historical data available. Predictor will use default statistics.")
        predictor.load_or_train(api_client, force_retrain=False)
    
    # Test 3: Test prediction
    logger.info("\n=== Test 3: Testing Predictions ===")
    test_backlink = {
        'id': 1,
        'url': 'https://example.com',
        'pa': 45,
        'da': 60,
        'site_type': 'comment',
    }
    
    for action_type in ['comment', 'profile', 'forum', 'guest']:
        prob = predictor.predict_success_probability(test_backlink, action_type)
        logger.info(f"  {action_type}: {prob:.2%} success probability")
    
    # Test 4: Test recommendation
    logger.info("\n=== Test 4: Testing Recommendations ===")
    recommendation, prob = predictor.recommend_action_type(test_backlink)
    logger.info(f"Recommended action: {recommendation} (probability: {prob:.2%})")
    
    # Test 5: Test decision service
    logger.info("\n=== Test 5: Testing Decision Service ===")
    decision_service = DecisionService(api_client, predictor)
    
    # Test with a campaign (use campaign_id=1 if available)
    campaign_id = 1
    try:
        campaign = api_client.get_campaign(campaign_id)
        if campaign:
            logger.info(f"Testing with campaign {campaign_id}")
            
            # Get recommendation for a backlink
            action_type, probability, metadata = decision_service.decide_action_type(
                campaign_id=campaign_id,
                backlink=test_backlink
            )
            logger.info(f"Decision: {action_type} (probability: {probability:.2%})")
            logger.info(f"All probabilities: {metadata.get('all_probabilities', {})}")
        else:
            logger.warning(f"Campaign {campaign_id} not found")
    except Exception as e:
        logger.warning(f"Could not test with campaign: {e}")
    
    logger.info("\n=== Tests Complete ===")

if __name__ == "__main__":
    main()

