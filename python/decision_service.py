"""
AI Decision Service for BacklinkPro
Main service that uses ML predictor to decide which action type to take
"""

import logging
from typing import Dict, List, Optional, Tuple
from api_client import LaravelAPIClient
from ml_predictor import BacklinkPredictor

logger = logging.getLogger(__name__)


class DecisionService:
    """
    Service that makes intelligent decisions about which backlink action to take
    based on ML predictions
    """
    
    def __init__(self, api_client: LaravelAPIClient, predictor: Optional[BacklinkPredictor] = None):
        """
        Initialize decision service
        
        Args:
            api_client: Laravel API client
            predictor: Optional pre-initialized predictor (will create one if not provided)
        """
        self.api_client = api_client
        self.predictor = predictor or BacklinkPredictor()
        
        # Load or train predictor
        self.predictor.load_or_train(api_client, force_retrain=False)
    
    def decide_action_type(self, campaign_id: int, backlink: Dict,
                          available_types: Optional[List[str]] = None) -> Tuple[str, float, Dict]:
        """
        Decide which action type has highest success probability for a backlink
        
        Args:
            campaign_id: Campaign ID
            backlink: Backlink dictionary with pa, da, site_type, etc.
            available_types: Optional list of action types to consider
            
        Returns:
            Tuple of (recommended_action_type, probability, decision_metadata)
        """
        # Get campaign info
        campaign = self.api_client.get_campaign(campaign_id)
        if not campaign:
            logger.warning(f"Campaign {campaign_id} not found, using defaults")
            campaign = {}
        
        # Get recommendation from predictor
        action_type, probability = self.predictor.recommend_action_type(
            backlink=backlink,
            campaign=campaign,
            available_types=available_types
        )
        
        # Get probabilities for all action types for metadata
        all_probabilities = {}
        for at in (available_types or ['comment', 'profile', 'forum', 'guest']):
            prob = self.predictor.predict_success_probability(backlink, at, campaign)
            all_probabilities[at] = prob
        
        metadata = {
            'all_probabilities': all_probabilities,
            'recommended': action_type,
            'recommended_probability': probability,
            'backlink_id': backlink.get('id'),
            'backlink_pa': backlink.get('pa'),
            'backlink_da': backlink.get('da'),
            'backlink_site_type': backlink.get('site_type'),
        }
        
        logger.info(
            f"Decision for backlink {backlink.get('id')}: {action_type} "
            f"(probability: {probability:.2%})"
        )
        
        return (action_type, probability, metadata)
    
    def select_best_opportunity(self, campaign_id: int, count: int = 1,
                               preferred_action_type: Optional[str] = None) -> List[Dict]:
        """
        Select best opportunities for a campaign using AI recommendations
        
        This method:
        1. Gets available opportunities from API
        2. Predicts success probability for each
        3. Selects the ones with highest probability
        
        Args:
            campaign_id: Campaign ID
            count: Number of opportunities to select
            preferred_action_type: Optional preferred action type (will still use AI to rank)
            
        Returns:
            List of opportunity dictionaries with AI recommendations
        """
        # Get campaign info
        campaign = self.api_client.get_campaign(campaign_id)
        if not campaign:
            logger.warning(f"Campaign {campaign_id} not found")
            return []
        
        # Get all available opportunities (get more than needed for ranking)
        opportunities = self.api_client.get_opportunities_for_campaign(
            campaign_id=campaign_id,
            count=count * 5,  # Get 5x more for ranking
            task_type=preferred_action_type
        )
        
        if not opportunities:
            logger.warning(f"No opportunities found for campaign {campaign_id}")
            return []
        
        # Score each opportunity
        scored_opportunities = []
        for opp in opportunities:
            # Determine available action types (based on site_type)
            site_type = opp.get('site_type', 'comment')
            available_types = self._get_available_types_for_site_type(site_type)
            
            # If preferred action type is specified, prioritize it
            if preferred_action_type and preferred_action_type in available_types:
                available_types = [preferred_action_type] + [
                    at for at in available_types if at != preferred_action_type
                ]
            
            # Get AI recommendation
            action_type, probability, metadata = self.decide_action_type(
                campaign_id=campaign_id,
                backlink=opp,
                available_types=available_types
            )
            
            scored_opportunities.append({
                'opportunity': opp,
                'recommended_action_type': action_type,
                'success_probability': probability,
                'metadata': metadata,
            })
        
        # Sort by success probability (descending)
        scored_opportunities.sort(key=lambda x: x['success_probability'], reverse=True)
        
        # Return top N
        result = []
        for scored in scored_opportunities[:count]:
            opp = scored['opportunity']
            opp['ai_recommended_action_type'] = scored['recommended_action_type']
            opp['ai_success_probability'] = scored['success_probability']
            opp['ai_metadata'] = scored['metadata']
            result.append(opp)
        
        top_prob = result[0]['ai_success_probability'] if result else 0.0
        logger.info(
            f"Selected {len(result)} opportunities for campaign {campaign_id} "
            f"(top probability: {top_prob:.2%})"
        )
        
        return result
    
    def _get_available_types_for_site_type(self, site_type: str) -> List[str]:
        """
        Get available action types for a site type
        
        Args:
            site_type: Site type from backlink store
            
        Returns:
            List of available action types
        """
        type_map = {
            'comment': ['comment'],
            'profile': ['profile'],
            'forum': ['forum'],
            'guestposting': ['guest'],
            'other': ['comment', 'profile', 'forum', 'guest'],
        }
        return type_map.get(site_type, ['comment', 'profile', 'forum', 'guest'])
    
    def retrain_models(self, force: bool = True):
        """
        Retrain ML models with latest historical data
        
        Args:
            force: Force retraining even if models exist
        """
        logger.info("Retraining ML models...")
        self.predictor.load_or_train(self.api_client, force_retrain=force)
        logger.info("Model retraining complete")

