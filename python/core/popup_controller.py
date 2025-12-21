"""
Popup Controller

Dismisses overlays, modals, and popups using various strategies
"""

import logging
from typing import Dict, Optional, List
from playwright.sync_api import Page, TimeoutError as PlaywrightTimeoutError
from core.state_detector import StateDetector, PageState
from core.telemetry import log_step
from core.budget_guard import BudgetGuard
from core.domain_memory import get_domain_memory

logger = logging.getLogger(__name__)


class PopupController:
    """Controls popup dismissal"""
    
    # Close button text patterns
    CLOSE_BUTTON_TEXT = [
        'close',
        'x',
        'accept',
        'not now',
        'dismiss',
        'decline',
        'reject',
        'no thanks',
        'maybe later',
        'skip',
        'cancel',
    ]
    
    # Close button selectors
    CLOSE_BUTTON_SELECTORS = [
        '[aria-label*="close" i]',
        '[aria-label*="dismiss" i]',
        '[title*="close" i]',
        '[class*="close"]',
        '[id*="close"]',
        '[class*="dismiss"]',
        '[id*="dismiss"]',
        'button[class*="close"]',
        'button[id*="close"]',
        '.close-button',
        '#close-button',
        '[data-dismiss="modal"]',
        '[data-close]',
    ]
    
    @classmethod
    def clear_if_needed(cls, page: Page, task_id: int, state: Optional[PageState] = None) -> Dict:
        """
        Clear popups/overlays if needed
        
        Args:
            page: Playwright Page object
            task_id: Task ID for logging
            state: Optional pre-analyzed PageState
        
        Returns:
            Dict with result information
        """
        result = {
            'cleared': False,
            'strategies_attempted': [],
            'errors': [],
        }
        
        try:
            # Analyze state if not provided
            if state is None:
                log_step(task_id, 'popup_analysis_start')
                state = StateDetector.analyze(page)
                log_step(task_id, 'popup_analysis_complete', state.to_dict())
            
            # Check if clearing is needed
            needs_clearing = (
                state.overlay_present or
                state.modal_present or
                state.cookie_banner_present or
                state.newsletter_modal_present
            )
            
            if not needs_clearing:
                log_step(task_id, 'popup_clear_not_needed')
                return result
            
            log_step(task_id, 'popup_clear_start', {
                'overlay_present': state.overlay_present,
                'modal_present': state.modal_present,
                'cookie_banner_present': state.cookie_banner_present,
                'newsletter_modal_present': state.newsletter_modal_present,
            })
            
            # Check domain memory for recurring popup selectors
            domain_memory = get_domain_memory()
            recurring_selectors = []
            if domain:
                domain_data = domain_memory.get(domain)
                recurring_selectors = domain_data.get('recurring_popup_selectors', [])
            
            # Try recurring selectors first (learned from previous runs)
            for selector in recurring_selectors:
                try:
                    BudgetGuard.check_popup_dismiss(task_id)
                except Exception as e:
                    log_step(task_id, 'popup_clear_budget_exceeded', {'error': str(e)})
                    break
                
                try:
                    elements = page.query_selector_all(selector)
                    for element in elements:
                        if element.is_visible():
                            log_step(task_id, 'popup_recurring_selector_found', {'selector': selector})
                            element.click(timeout=2000)
                            page.wait_for_timeout(500)
                            result['strategies_attempted'].append(f'recurring_{selector}')
                            
                            # Verify clearing worked
                            state_after = StateDetector.analyze(page)
                            if not (state_after.overlay_present or state_after.modal_present or 
                                   state_after.cookie_banner_present or state_after.newsletter_modal_present):
                                result['cleared'] = True
                                log_step(task_id, 'popup_clear_verified')
                                # Record successful selector in domain memory
                                domain_memory.record_popup_cleared(domain, selector, True)
                                return result
                except:
                    continue
            
            # Try different strategies
            strategies = [
                ('close_buttons', lambda: cls._try_close_buttons(page, task_id)),
                ('esc_key', lambda: cls._try_esc_key(page, task_id)),
            ]
            
            for strategy_name, strategy_func in strategies:
                try:
                    # Check budget before attempting
                    BudgetGuard.check_popup_dismiss(task_id)
                except Exception as e:
                    log_step(task_id, 'popup_clear_budget_exceeded', {'error': str(e)})
                    break
                
                try:
                    log_step(task_id, f'popup_strategy_{strategy_name}_start')
                    cleared = strategy_func()
                    result['strategies_attempted'].append(strategy_name)
                    
                    if cleared:
                        result['cleared'] = True
                        log_step(task_id, f'popup_strategy_{strategy_name}_success')
                        
                        # Verify clearing worked
                        state_after = StateDetector.analyze(page)
                        if not (state_after.overlay_present or state_after.modal_present or 
                               state_after.cookie_banner_present or state_after.newsletter_modal_present):
                            log_step(task_id, 'popup_clear_verified')
                            # Record successful popup clear in domain memory
                            if domain:
                                domain_memory.record_popup_cleared(domain, strategy_name, True)
                            break
                    else:
                        log_step(task_id, f'popup_strategy_{strategy_name}_failed')
                        
                except Exception as e:
                    error_msg = str(e)
                    result['errors'].append(f"{strategy_name}: {error_msg}")
                    log_step(task_id, f'popup_strategy_{strategy_name}_error', {'error': error_msg})
                    logger.warning(f"Popup strategy {strategy_name} failed: {e}")
            
            if result['cleared']:
                log_step(task_id, 'popup_clear_success')
            else:
                log_step(task_id, 'popup_clear_failed', {
                    'strategies_attempted': result['strategies_attempted'],
                    'errors': result['errors'],
                })
        
        except Exception as e:
            error_msg = str(e)
            result['errors'].append(f"clear_if_needed: {error_msg}")
            log_step(task_id, 'popup_clear_error', {'error': error_msg})
            logger.error(f"Error clearing popups: {e}")
        
        return result
    
    @classmethod
    def _try_close_buttons(cls, page: Page, task_id: int) -> bool:
        """
        Try clicking close buttons
        
        Args:
            page: Playwright Page object
            task_id: Task ID for logging
        
        Returns:
            True if cleared
        """
        try:
            # Try selectors first
            for selector in cls.CLOSE_BUTTON_SELECTORS:
                try:
                    elements = page.query_selector_all(selector)
                    for element in elements:
                        if element.is_visible():
                            log_step(task_id, 'popup_close_button_found', {'selector': selector})
                            element.click(timeout=2000)
                            page.wait_for_timeout(500)  # Wait for animation
                            return True
                except PlaywrightTimeoutError:
                    continue
                except Exception:
                    continue
            
            # Try text-based search
            for text_pattern in cls.CLOSE_BUTTON_TEXT:
                try:
                    # Try exact text match
                    button = page.query_selector(f'button:has-text("{text_pattern}")')
                    if button and button.is_visible():
                        log_step(task_id, 'popup_close_button_found', {'text': text_pattern})
                        button.click(timeout=2000)
                        page.wait_for_timeout(500)
                        return True
                    
                    # Try case-insensitive
                    buttons = page.query_selector_all('button')
                    for button in buttons:
                        if button.is_visible():
                            button_text = button.inner_text().lower().strip()
                            if text_pattern.lower() in button_text:
                                log_step(task_id, 'popup_close_button_found', {'text': button_text})
                                button.click(timeout=2000)
                                page.wait_for_timeout(500)
                                return True
                except PlaywrightTimeoutError:
                    continue
                except Exception:
                    continue
            
            # Try links with close text
            try:
                links = page.query_selector_all('a')
                for link in links:
                    if link.is_visible():
                        link_text = link.inner_text().lower().strip()
                        if any(pattern in link_text for pattern in cls.CLOSE_BUTTON_TEXT):
                            log_step(task_id, 'popup_close_link_found', {'text': link_text})
                            link.click(timeout=2000)
                            page.wait_for_timeout(500)
                            return True
            except:
                pass
            
        except Exception as e:
            logger.debug(f"Error trying close buttons: {e}")
        
        return False
    
    @classmethod
    def _try_esc_key(cls, page: Page, task_id: int) -> bool:
        """
        Try pressing ESC key
        
        Args:
            page: Playwright Page object
            task_id: Task ID for logging
        
        Returns:
            True if cleared
        """
        try:
            log_step(task_id, 'popup_esc_key_attempt')
            page.keyboard.press('Escape')
            page.wait_for_timeout(500)  # Wait for modal to close
            
            # Check if modal is still visible
            state_after = StateDetector.analyze(page)
            if not (state_after.modal_present or state_after.overlay_present):
                log_step(task_id, 'popup_esc_key_success')
                return True
            else:
                log_step(task_id, 'popup_esc_key_failed')
                return False
        except Exception as e:
            logger.debug(f"Error trying ESC key: {e}")
            return False

