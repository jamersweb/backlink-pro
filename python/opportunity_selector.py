"""
Opportunity Selector for Python Automation
Selects backlink opportunities based on campaign category, plan limits, and daily limits
Now enhanced with AI decision engine for intelligent action type selection
"""
import logging
import random
from typing import Dict, List, Optional, Tuple
from api_client import LaravelAPIClient

logger = logging.getLogger(__name__)

# Try to import AI decision engine (preferred)
try:
    from ai_decision_engine import AIDecisionEngine, get_engine
    AI_ENGINE_AVAILABLE = True
except ImportError:
    AI_ENGINE_AVAILABLE = False
    logger.warning("AI Decision Engine not available. Using rules-based selection.")

# Try to import decision service (fallback)
try:
    from decision_service import DecisionService
    DECISION_SERVICE_AVAILABLE = True
except ImportError:
    DECISION_SERVICE_AVAILABLE = False


class OpportunitySelector:
    """Selects backlink opportunities for campaigns with AI-powered action type selection"""
    
    def __init__(self, api_client: LaravelAPIClient, use_ai: bool = True, shadow_mode: bool = False):
        """
        Initialize opportunity selector
        
        Args:
            api_client: Laravel API client
            use_ai: Whether to use AI decision engine (default: True)
            shadow_mode: If True, AI predicts but rule-based system executes (default: False)
        """
        self.api_client = api_client
        self.ai_engine = None
        self.decision_service = None
        self.use_ai = False
        self.shadow_mode = shadow_mode
        
        # Try to initialize AI Decision Engine (preferred)
        if use_ai and AI_ENGINE_AVAILABLE:
            try:
                self.ai_engine = get_engine()
                self.use_ai = True
                logger.info("AI Decision Engine enabled")
            except Exception as e:
                logger.warning(f"Failed to initialize AI Decision Engine: {e}")
                # Fallback to DecisionService if available
                if DECISION_SERVICE_AVAILABLE:
                    try:
                        self.decision_service = DecisionService(api_client)
                        self.use_ai = True
                        logger.info("Using DecisionService as fallback")
                    except Exception as e2:
                        logger.warning(f"Failed to initialize DecisionService: {e2}. Using rules-based selection.")
                else:
                    logger.warning("Using rules-based selection (no AI available)")
        
        # Fallback to DecisionService if AI engine not available
        elif use_ai and DECISION_SERVICE_AVAILABLE:
            try:
                self.decision_service = DecisionService(api_client)
                self.use_ai = True
                logger.info("Using DecisionService (AI Engine not available)")
            except Exception as e:
                logger.warning(f"Failed to initialize DecisionService: {e}. Using rules-based selection.")
    
    def select_opportunity(self, campaign_id: int, task_type: str = 'comment',
                          site_type: Optional[str] = None,
                          use_ai_recommendation: bool = True) -> Optional[Dict]:
        """
        Select a single opportunity for a campaign with AI-powered action type selection
        
        Args:
            campaign_id: Campaign ID
            task_type: Type of task (comment, profile, forum, guest) - can be overridden by AI
            site_type: Optional site type filter
            use_ai_recommendation: Whether to use AI to recommend action type (default: True)
        
        Returns:
            Opportunity dict with id, url, pa, da, etc. or None if no opportunities
            If AI is enabled, includes 'ai_recommended_action_type' and 'ai_probabilities'
            In shadow mode, AI predicts but rule-based action is used
        """
        # In shadow mode, get AI prediction but use rule-based selection
        if self.shadow_mode and self.ai_engine:
            return self._select_with_shadow_mode(campaign_id, task_type, site_type)
        
        # If AI is enabled, use AI Decision Engine
        if self.use_ai and use_ai_recommendation and self.ai_engine:
            try:
                return self._select_with_ai_engine(campaign_id, task_type, site_type)
            except Exception as e:
                logger.warning(f"AI Decision Engine error: {e}, falling back to rules-based selection")
        
        # Fallback to DecisionService if available
        if self.use_ai and use_ai_recommendation and self.decision_service:
            try:
                return self._select_with_decision_service(campaign_id, task_type, site_type)
            except Exception as e:
                logger.warning(f"DecisionService error: {e}, falling back to rules-based selection")
        
        # Rules-based fallback (original logic)
        return self._select_with_rules(campaign_id, task_type, site_type)
    
    def _select_with_shadow_mode(self, campaign_id: int, task_type: str,
                                 site_type: Optional[str]) -> Optional[Dict]:
        """Select opportunity in shadow mode: AI predicts, rules execute"""
        # Get opportunity using rules (what will actually be executed)
        opportunity = self._select_with_rules(campaign_id, task_type, site_type)
        
        if not opportunity:
            return None
        
        # Get AI prediction for logging (but don't use it)
        try:
            campaign = self.api_client.get_campaign(campaign_id) or {}
            
            site_features = {
                'pa': opportunity.get('pa', 0),
                'da': opportunity.get('da', 0),
                'site_type': opportunity.get('site_type', 'comment'),
                'campaign_daily_limit': campaign.get('daily_limit', 0),
                'campaign_total_limit': campaign.get('total_limit', 0),
                # Enriched features from feature_extractor (if available)
                'url_path_depth': opportunity.get('url_path_depth', 0),
                'https_enabled': opportunity.get('https_enabled', False),
                'platform_guess': opportunity.get('platform_guess', 'unknown'),
                'comment_supported': opportunity.get('comment_supported', False),
                'profile_supported': opportunity.get('profile_supported', False),
                'forum_supported': opportunity.get('forum_supported', False),
                'guest_supported': opportunity.get('guest_supported', False),
                'requires_login': opportunity.get('requires_login', False),
                'registration_detected': opportunity.get('registration_detected', False),
            }
            
            # Get AI prediction
            probabilities = self.ai_engine.predict(site_features)
            best_action, best_prob = self.ai_engine.get_best_action(site_features)
            
            # Store AI prediction in opportunity for logging
            opportunity['ai_recommended_action_type'] = best_action
            opportunity['ai_probability'] = best_prob
            opportunity['ai_probabilities'] = probabilities
            opportunity['shadow_mode'] = True  # Flag for worker to log
            
            logger.info(
                f"Shadow mode: AI predicts {best_action} ({best_prob:.2%}), "
                f"but executing {task_type} (rule-based)"
            )
            
        except Exception as e:
            logger.warning(f"Shadow mode AI prediction failed: {e}")
            # Still return opportunity with default AI fields
            opportunity['ai_recommended_action_type'] = task_type
            opportunity['ai_probability'] = 0.5
            opportunity['ai_probabilities'] = {
                'comment': 0.25, 'profile': 0.25, 'forum': 0.25, 'guest': 0.25
            }
            opportunity['shadow_mode'] = True
        
        return opportunity
    
    def _select_with_ai_engine(self, campaign_id: int, task_type: str, 
                               site_type: Optional[str]) -> Optional[Dict]:
        """Select opportunity using AI Decision Engine"""
        # Get multiple opportunities for AI to evaluate
        opportunities = self.api_client.get_opportunities_for_campaign(
            campaign_id=campaign_id,
            count=10,  # Get more for AI to rank
            task_type=None,  # Don't filter by type - let AI decide
            site_type=site_type
        )
        
        if not opportunities:
            logger.warning(f"No opportunities found for campaign {campaign_id}")
            return None
        
        # Get campaign info for context
        campaign = self.api_client.get_campaign(campaign_id) or {}
        
        # Score each opportunity with AI
        scored_opportunities = []
        for opp in opportunities:
            try:
                # Prepare site features for AI (include enriched features if available)
                site_features = {
                    'pa': opp.get('pa', 0),
                    'da': opp.get('da', 0),
                    'site_type': opp.get('site_type', 'comment'),
                    'campaign_daily_limit': campaign.get('daily_limit', 0),
                    'campaign_total_limit': campaign.get('total_limit', 0),
                    # Enriched features from feature_extractor (if available)
                    'url_path_depth': opp.get('url_path_depth', 0),
                    'https_enabled': opp.get('https_enabled', False),
                    'platform_guess': opp.get('platform_guess', 'unknown'),
                    'comment_supported': opp.get('comment_supported', False),
                    'profile_supported': opp.get('profile_supported', False),
                    'forum_supported': opp.get('forum_supported', False),
                    'guest_supported': opp.get('guest_supported', False),
                    'requires_login': opp.get('requires_login', False),
                    'registration_detected': opp.get('registration_detected', False),
                }
                
                # Get AI predictions
                probabilities = self.ai_engine.predict(site_features)
                best_action, best_prob = self.ai_engine.get_best_action(site_features)
                
                # If task_type was specified, prefer it but still use AI ranking
                if task_type and task_type in probabilities:
                    # Weight the specified task_type slightly higher
                    probabilities[task_type] = min(probabilities[task_type] * 1.2, 1.0)
                    # Renormalize
                    total = sum(probabilities.values())
                    probabilities = {k: v / total for k, v in probabilities.items()}
                    best_action = max(probabilities.items(), key=lambda x: x[1])[0]
                    best_prob = probabilities[best_action]
                
                scored_opportunities.append({
                    'opportunity': opp,
                    'ai_recommended_action_type': best_action,
                    'ai_probability': best_prob,
                    'ai_probabilities': probabilities,
                })
                
            except Exception as e:
                logger.warning(f"Error scoring opportunity {opp.get('id')}: {e}")
                # Include with default scores
                scored_opportunities.append({
                    'opportunity': opp,
                    'ai_recommended_action_type': task_type or 'comment',
                    'ai_probability': 0.25,
                    'ai_probabilities': {'comment': 0.25, 'profile': 0.25, 'forum': 0.25, 'guest': 0.25},
                })
        
        # Sort by AI probability (descending)
        scored_opportunities.sort(key=lambda x: x['ai_probability'], reverse=True)
        
        # Return best opportunity with AI recommendations
        if scored_opportunities:
            best = scored_opportunities[0]
            opp = best['opportunity']
            opp['ai_recommended_action_type'] = best['ai_recommended_action_type']
            opp['ai_probability'] = best['ai_probability']
            opp['ai_probabilities'] = best['ai_probabilities']
            
            logger.info(
                f"AI selected opportunity {opp.get('id')}: "
                f"{best['ai_recommended_action_type']} ({best['ai_probability']:.2%})"
            )
            
            return opp
        
        return None
    
    def _select_with_decision_service(self, campaign_id: int, task_type: str,
                                      site_type: Optional[str]) -> Optional[Dict]:
        """Select opportunity using DecisionService (fallback)"""
        opportunities = self.api_client.get_opportunities_for_campaign(
            campaign_id=campaign_id,
            count=5,
            task_type=None,
            site_type=site_type
        )
        
        if not opportunities:
            return None
        
        best_opportunities = self.decision_service.select_best_opportunity(
            campaign_id=campaign_id,
            count=1,
            preferred_action_type=task_type if task_type else None
        )
        
        if best_opportunities:
            return best_opportunities[0]
        
        return None
    
    def _select_with_rules(self, campaign_id: int, task_type: str,
                          site_type: Optional[str]) -> Optional[Dict]:
        """Select opportunity using rules-based logic (fallback)"""
        opportunities = self.api_client.get_opportunities_for_campaign(
            campaign_id=campaign_id,
            count=1,
            task_type=task_type,
            site_type=site_type
        )
        
        if not opportunities:
            logger.warning(f"No opportunities found for campaign {campaign_id}")
            return None
        
        opp = opportunities[0]
        
        # Add default AI fields for consistency
        opp['ai_recommended_action_type'] = task_type or 'comment'
        opp['ai_probability'] = 0.5  # Default confidence
        opp['ai_probabilities'] = {
            'comment': 0.25 if task_type != 'comment' else 0.5,
            'profile': 0.25 if task_type != 'profile' else 0.5,
            'forum': 0.25 if task_type != 'forum' else 0.5,
            'guest': 0.25 if task_type != 'guest' else 0.5,
        }
        
        logger.info(f"Rules-based selection: opportunity {opp.get('id')}, action: {opp['ai_recommended_action_type']}")
        
        return opp
    
    def select_opportunity_with_action(self, campaign_id: int, task_type: str = 'comment',
                                      site_type: Optional[str] = None) -> Optional[Tuple[Dict, str]]:
        """
        Select opportunity and get AI-recommended action type
        
        Args:
            campaign_id: Campaign ID
            task_type: Default task type (can be overridden by AI)
            site_type: Optional site type filter
        
        Returns:
            Tuple of (opportunity_dict, recommended_action_type) or None
        """
        opportunity = self.select_opportunity(campaign_id, task_type, site_type, use_ai_recommendation=True)
        
        if not opportunity:
            return None
        
        recommended_action = opportunity.get('ai_recommended_action_type', task_type)
        
        return (opportunity, recommended_action)
    
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

