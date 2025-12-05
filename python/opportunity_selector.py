"""
Opportunity Selector for Python Automation
Selects backlink opportunities based on campaign category, plan limits, and daily limits
"""
import logging
import random
from typing import Dict, List, Optional
from api_client import LaravelAPIClient

logger = logging.getLogger(__name__)


class OpportunitySelector:
    """Selects backlink opportunities for campaigns"""
    
    def __init__(self, api_client: LaravelAPIClient):
        self.api_client = api_client
    
    def select_opportunity(self, campaign_id: int, task_type: str = 'comment',
                          site_type: Optional[str] = None) -> Optional[Dict]:
        """
        Select a single opportunity for a campaign
        
        Args:
            campaign_id: Campaign ID
            task_type: Type of task (comment, profile, forum, guest)
            site_type: Optional site type filter
        
        Returns:
            Opportunity dict with id, url, pa, da, etc. or None if no opportunities
        """
        opportunities = self.api_client.get_opportunities_for_campaign(
            campaign_id=campaign_id,
            count=1,
            task_type=task_type,
            site_type=site_type
        )
        
        if not opportunities:
            logger.warning(f"No opportunities found for campaign {campaign_id}")
            return None
        
        return opportunities[0]
    
    def select_opportunities(self, campaign_id: int, count: int = 1,
                            task_type: str = 'comment',
                            site_type: Optional[str] = None) -> List[Dict]:
        """
        Select multiple opportunities for a campaign
        
        Args:
            campaign_id: Campaign ID
            count: Number of opportunities to select
            task_type: Type of task (comment, profile, forum, guest)
            site_type: Optional site type filter
        
        Returns:
            List of opportunity dicts
        """
        opportunities = self.api_client.get_opportunities_for_campaign(
            campaign_id=campaign_id,
            count=count,
            task_type=task_type,
            site_type=site_type
        )
        
        if not opportunities:
            logger.warning(f"No opportunities found for campaign {campaign_id}")
            return []
        
        return opportunities
    
    def get_opportunity_url(self, campaign_id: int, task_type: str = 'comment',
                           site_type: Optional[str] = None) -> Optional[str]:
        """
        Get a single opportunity URL (convenience method)
        
        Returns:
            URL string or None
        """
        opportunity = self.select_opportunity(campaign_id, task_type, site_type)
        return opportunity.get('url') if opportunity else None
    
    def get_opportunity_urls(self, campaign_id: int, count: int = 1,
                            task_type: str = 'comment',
                            site_type: Optional[str] = None) -> List[str]:
        """
        Get multiple opportunity URLs (convenience method)
        
        Returns:
            List of URL strings
        """
        opportunities = self.select_opportunities(campaign_id, count, task_type, site_type)
        return [opp.get('url') for opp in opportunities if opp.get('url')]

