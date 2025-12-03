"""
Email Confirmation Click Automation
Automatically clicks verification links from emails
"""

from typing import Dict
from automation.base import BaseAutomation
import logging

logger = logging.getLogger(__name__)


class EmailConfirmationAutomation(BaseAutomation):
    """Automation for clicking email verification links"""
    
    def execute(self, task: Dict) -> Dict:
        """
        Click verification link from email
        
        Expected task payload:
        {
            'verification_link': 'https://example.com/verify?token=...',
            'site_account_id': 123,
        }
        """
        try:
            payload = task.get('payload', {})
            verification_link = payload.get('verification_link')
            site_account_id = payload.get('site_account_id')
            
            if not verification_link:
                return {
                    'success': False,
                    'error': 'No verification link provided in task payload',
                }
            
            logger.info(f"Clicking verification link for site_account_id: {site_account_id}")
            logger.info(f"Verification link: {verification_link}")
            
            # Navigate to verification link
            self.page.goto(verification_link, wait_until='networkidle', timeout=30000)
            self.random_delay(2, 4)
            
            # Take screenshot for debugging
            self.take_screenshot(f'email_confirmation_{site_account_id}.png')
            
            # Look for common success indicators
            success_indicators = [
                'verified',
                'confirmed',
                'activated',
                'success',
                'thank you',
                'email confirmed',
            ]
            
            page_text = self.page.content().lower()
            page_title = self.page.title().lower()
            
            # Check if verification was successful
            is_success = any(indicator in page_text or indicator in page_title 
                           for indicator in success_indicators)
            
            # Also check for common success elements
            try:
                # Look for success messages
                success_selectors = [
                    '.success',
                    '.alert-success',
                    '[class*="success"]',
                    '[id*="success"]',
                    'h1:has-text("verified")',
                    'h1:has-text("confirmed")',
                    'h1:has-text("activated")',
                ]
                
                for selector in success_selectors:
                    try:
                        element = self.page.locator(selector).first
                        if element.is_visible(timeout=2000):
                            is_success = True
                            break
                    except:
                        continue
            except Exception as e:
                logger.warning(f"Error checking success indicators: {e}")
            
            # Wait a bit to ensure page fully loads
            self.random_delay(2, 3)
            
            # Update site account status via API
            if is_success:
                try:
                    # Update site account to verified status
                    self.api_client.update_site_account(
                        site_account_id,
                        {
                            'status': 'verified',
                            'email_verification_status': 'clicked',
                        }
                    )
                    logger.info(f"Successfully clicked verification link for site_account_id: {site_account_id}")
                except Exception as e:
                    logger.warning(f"Failed to update site account status: {e}")
                    # Still return success if link was clicked
            
            return {
                'success': is_success,
                'url': self.page.url,
                'site_account_id': site_account_id,
                'message': 'Verification link clicked successfully' if is_success else 'Verification link clicked but success not confirmed',
            }
            
        except Exception as e:
            logger.error(f"Error clicking verification link: {e}", exc_info=True)
            return {
                'success': False,
                'error': str(e),
                'site_account_id': payload.get('site_account_id'),
            }


