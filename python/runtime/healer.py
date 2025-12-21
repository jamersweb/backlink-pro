"""
Runtime Healer

Attempts recovery on failures
"""

import logging
from typing import Dict, Optional, Any
from playwright.sync_api import Page, Locator

from core.state_detector import StateDetector
from core.popup_controller import PopupController
from core.locator_engine import LocatorEngine
from core.iframe_router import IframeRouter
from core.budget_guard import BudgetGuard, BudgetExceededException
from core.telemetry import log_step
from core.failure_enums import FailureReason

logger = logging.getLogger(__name__)


class RuntimeHealer:
    """Attempts recovery on failures"""
    
    def __init__(self, task_id: int, page: Page, domain: str):
        """
        Initialize healer
        
        Args:
            task_id: Task ID
            page: Playwright Page object
            domain: Domain name
        """
        self.task_id = task_id
        self.page = page
        self.domain = domain
    
    def heal(self, failure_reason: str, context: Optional[Dict] = None) -> Dict:
        """
        Attempt to heal from failure
        
        Args:
            failure_reason: Failure reason enum value
            context: Optional context (element, selector, etc.)
        
        Returns:
            Dict with success, recovery_action, and new_context
        """
        log_step(self.task_id, 'healer_attempt_start', {
            'failure_reason': failure_reason,
            'context': context or {}
        })
        
        try:
            # Check budget before healing
            BudgetGuard.check_runtime(self.task_id)
        except BudgetExceededException:
            return {
                'success': False,
                'recovery_action': 'budget_exceeded',
                'error': 'Budget exceeded, cannot heal'
            }
        
        # Route to appropriate healer
        if failure_reason == FailureReason.POPUP_BLOCKING.value:
            return self._heal_popup_blocking(context)
        elif failure_reason == FailureReason.IFRAME_MISSED.value:
            return self._heal_iframe_missed(context)
        elif failure_reason == FailureReason.ELEMENT_NOT_FOUND.value:
            return self._heal_element_not_found(context)
        else:
            # Fail fast for other failures
            log_step(self.task_id, 'healer_fail_fast', {
                'failure_reason': failure_reason,
                'reason': 'No recovery strategy available'
            })
            return {
                'success': False,
                'recovery_action': 'fail_fast',
                'error': f'No recovery strategy for {failure_reason}'
            }
    
    def _heal_popup_blocking(self, context: Optional[Dict]) -> Dict:
        """Heal popup blocking by clearing popups and retrying"""
        log_step(self.task_id, 'healer_popup_blocking_start')
        
        try:
            BudgetGuard.check_popup_dismiss(self.task_id)
        except BudgetExceededException:
            return {
                'success': False,
                'recovery_action': 'budget_exceeded',
                'error': 'Popup dismiss budget exceeded'
            }
        
        # Analyze page state
        page_state = StateDetector.analyze(self.page)
        
        # Clear popups
        result = PopupController.clear_if_needed(
            self.page,
            self.task_id,
            state=page_state,
            domain=self.domain
        )
        
        if result.get('cleared'):
            log_step(self.task_id, 'healer_popup_blocking_success', {
                'strategy': result.get('strategy', 'unknown')
            })
            return {
                'success': True,
                'recovery_action': 'popup_cleared',
                'new_context': {
                    'popup_cleared': True,
                    'strategy': result.get('strategy')
                }
            }
        else:
            log_step(self.task_id, 'healer_popup_blocking_failed', {
                'errors': result.get('errors', [])
            })
            return {
                'success': False,
                'recovery_action': 'popup_clear_failed',
                'error': 'Failed to clear popups'
            }
    
    def _heal_iframe_missed(self, context: Optional[Dict]) -> Dict:
        """Heal iframe missed by routing to iframe and retrying"""
        log_step(self.task_id, 'healer_iframe_missed_start')
        
        if not context or 'selector' not in context:
            return {
                'success': False,
                'recovery_action': 'no_context',
                'error': 'No selector in context for iframe routing'
            }
        
        selector = context['selector']
        description = context.get('description', 'element')
        
        # Try to find element in iframes
        def locator_builder(ctx):
            if isinstance(ctx, Page):
                return ctx.locator(selector)
            else:  # FrameLocator
                return ctx.locator(selector)
        
        locator, frame, source = IframeRouter.find_in_main_or_frames(
            self.page,
            locator_builder,
            task_id=self.task_id,
            description=f'healer_{description}'
        )
        
        if locator and source != "not_found":
            log_step(self.task_id, 'healer_iframe_missed_success', {
                'source': source,
                'frame_url': frame.url if frame else None
            })
            return {
                'success': True,
                'recovery_action': 'iframe_routed',
                'new_context': {
                    'locator': locator,
                    'frame': frame,
                    'source': source,
                    'selector': selector
                }
            }
        else:
            log_step(self.task_id, 'healer_iframe_missed_failed')
            return {
                'success': False,
                'recovery_action': 'iframe_not_found',
                'error': 'Element not found in main page or iframes'
            }
    
    def _heal_element_not_found(self, context: Optional[Dict]) -> Dict:
        """Heal element not found by using locator engine fallback"""
        log_step(self.task_id, 'healer_element_not_found_start')
        
        if not context:
            return {
                'success': False,
                'recovery_action': 'no_context',
                'error': 'No context for element recovery'
            }
        
        target_role = context.get('target_role', 'element')
        keywords = context.get('keywords', [])
        
        if not keywords:
            return {
                'success': False,
                'recovery_action': 'no_keywords',
                'error': 'No keywords provided for locator engine'
            }
        
        try:
            BudgetGuard.check_locator_candidates(self.task_id)
        except BudgetExceededException:
            return {
                'success': False,
                'recovery_action': 'budget_exceeded',
                'error': 'Locator candidates budget exceeded'
            }
        
        # Use LocatorEngine to find element
        locator, candidate, candidates = LocatorEngine.find(
            self.page,
            target_role=target_role,
            keywords=keywords,
            context={'domain': self.domain, **context.get('extra_context', {})},
            task_id=self.task_id
        )
        
        if locator:
            log_step(self.task_id, 'healer_element_not_found_success', {
                'strategy': candidate.strategy if candidate else 'unknown',
                'confidence': candidate.confidence if candidate else 0.0
            })
            return {
                'success': True,
                'recovery_action': 'locator_engine_fallback',
                'new_context': {
                    'locator': locator,
                    'strategy': candidate.strategy if candidate else 'unknown',
                    'confidence': candidate.confidence if candidate else 0.0
                }
            }
        else:
            log_step(self.task_id, 'healer_element_not_found_failed', {
                'candidates_tried': len(candidates)
            })
            return {
                'success': False,
                'recovery_action': 'locator_engine_failed',
                'error': f'LocatorEngine could not find element (tried {len(candidates)} candidates)'
            }

