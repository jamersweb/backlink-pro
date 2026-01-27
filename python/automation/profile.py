"""
Profile backlink automation
"""
from typing import Dict, Optional
import logging
import random
from .base import BaseAutomation

logger = logging.getLogger(__name__)


class ProfileAutomation(BaseAutomation):
    """Automation for profile backlinks"""
    
    def execute(self, task: Dict) -> Dict:
        """Execute profile backlink task"""
        payload = task.get('payload', {})
        campaign_id = task.get('campaign_id')
        
        # Use opportunity selector if available, otherwise fallback to payload target_urls
        target_url = None
        opportunity = None
        
        if self.opportunity_selector and campaign_id:
            try:
                opportunity = self.opportunity_selector.select_opportunity(
                    campaign_id=campaign_id,
                    task_type='profile'
                )
                if opportunity:
                    target_url = opportunity.get('url')
                    logger.info(f"Selected opportunity {opportunity.get('id')} with PA:{opportunity.get('pa')} DA:{opportunity.get('da')}")
                    # Store opportunity for shadow mode logging
                    self.last_opportunity = opportunity
            except Exception as e:
                logger.warning(f"Failed to get opportunity: {e}, falling back to payload URLs")
        
        # Fallback to payload target_urls if no opportunity found
        if not target_url:
            target_urls = payload.get('target_urls', [])
            if not target_urls:
                return {
                    'success': False,
                    'error': 'No target URLs provided and no opportunities available',
                }
            target_url = target_urls[0]
        
        try:
            logger.info(f"Processing profile backlink for {target_url}")
            
            # Navigate to registration page
            registration_url = self._find_registration_url(target_url)
            if not self._safe_navigate(registration_url, wait_until='networkidle'):
                return {
                    'success': False,
                    'error': 'Browser crashed during navigation',
                    'backlink_id': opportunity.get('id') if opportunity else None,
                }
            self.random_delay(2, 4)
            
            # Fill registration form
            form_data = self._generate_form_data()
            self._fill_registration_form(form_data, task)
            
            # Take screenshot before submit for debugging
            try:
                self.take_screenshot(f'profile_before_submit_{task.get("id", "unknown")}.png')
            except Exception as screenshot_error:
                logger.debug(f"Could not take screenshot: {screenshot_error}")
                pass
            
            # Wait a bit for form to be ready
            self.random_delay(1, 2)
            
            # Submit form with better error handling
            submit_success = self._submit_registration_form()
            if not submit_success:
                return {
                    'success': False,
                    'error': 'Could not find or click submit button',
                    'backlink_id': opportunity.get('id') if opportunity else None,
                }
            
            self.random_delay(3, 5)
            
            # Get profile URL
            profile_url = self._get_profile_url()
            
            if profile_url:
                # Create site account
                site_account = self.api_client.create_site_account(
                    campaign_id=task['campaign_id'],
                    site_domain=self._extract_domain(target_url),
                    login_email=form_data['email'],
                    username=form_data['username'],
                    password=form_data['password'],
                    status='created',
                )
                
                result = {
                    'success': True,
                    'url': profile_url,
                    'type': 'profile',
                    'site_account_id': site_account.get('id'),
                }
                
                # Include opportunity ID if available
                if opportunity:
                    result['backlink_id'] = opportunity.get('id')
                
                return result
            else:
                return {
                    'success': False,
                    'error': 'Profile URL not found',
                    'backlink_id': opportunity.get('id') if opportunity else None,
                }
                
        except Exception as e:
            error_msg = str(e)
            logger.error(f"Profile automation failed: {error_msg}", exc_info=True)
            
            # Take screenshot on error for debugging
            try:
                self.take_screenshot(f'profile_error_{task.get("id", "unknown")}.png')
            except:
                pass
            
            # Extract failure reason from error
            failure_reason = None
            if 'registration' in error_msg.lower() or 'signup' in error_msg.lower():
                failure_reason = 'registration_failed'
            elif 'captcha' in error_msg.lower():
                failure_reason = 'captcha_failed'
            elif 'timeout' in error_msg.lower():
                failure_reason = 'timeout'
            elif 'blocked' in error_msg.lower() or 'banned' in error_msg.lower():
                failure_reason = 'blocked'
            
            # Extract captcha type
            captcha_type = None
            if 'captcha' in error_msg.lower():
                if 'recaptcha' in error_msg.lower():
                    captcha_type = 'recaptcha_v2' if 'v3' not in error_msg.lower() else 'recaptcha_v3'
                elif 'hcaptcha' in error_msg.lower():
                    captcha_type = 'hcaptcha'
                else:
                    captcha_type = 'image_captcha'
            
            return {
                'success': False,
                'error': error_msg,
                'backlink_id': opportunity.get('id') if opportunity else None,
                'failure_reason': failure_reason,
                'captcha_type': captcha_type,
            }
    
    def _find_registration_url(self, base_url: str) -> str:
        """Find registration URL"""
        # Common registration URLs
        registration_paths = [
            '/register',
            '/signup',
            '/sign-up',
            '/create-account',
            '/join',
        ]
        
        for path in registration_paths:
            try:
                url = f"{base_url.rstrip('/')}{path}"
                if not self._safe_navigate(url, wait_until='networkidle', timeout=5000):
                    logger.warning(f"Failed to navigate to {url}")
                    continue
                if 'register' in self.page.url.lower() or 'signup' in self.page.url.lower():
                    return self.page.url
            except:
                continue
        
        return base_url
    
    def _generate_form_data(self) -> Dict:
        """Generate random form data"""
        import random
        import string
        
        username = ''.join(random.choices(string.ascii_lowercase + string.digits, k=8))
        email = f"{username}@example.com"
        password = ''.join(random.choices(string.ascii_letters + string.digits, k=12))
        
        return {
            'username': username,
            'email': email,
            'password': password,
            'first_name': 'John',
            'last_name': 'Doe',
        }
    
    def _fill_registration_form(self, form_data: Dict, task: Dict):
        """Fill registration form with better field detection"""
        logger.info("Filling registration form...")
        
        # Wait for form to be ready
        try:
            self.page.wait_for_selector('form, input[type="email"], input[type="password"]', timeout=10000)
        except:
            logger.warning("Form elements not found, continuing anyway...")
        
        # Use field role matcher to find fields
        try:
            from core.field_role_matcher import FieldRoleMatcher
            
            task_id = getattr(self, '_current_task_id', None)
            field_mappings = FieldRoleMatcher.match_fields(
                self.page,
                form_locator=None,  # Search entire page for registration form
                task_id=task_id
            )
            
            # Fill username
            if 'username' in field_mappings:
                username_field, confidence = field_mappings['username']
                logger.info(f"Found username field via role matcher (confidence: {confidence:.2f})")
                if username_field.is_visible():
                    self.human_type(self.page, username_field, form_data['username'])
            else:
                # Fallback to hardcoded selectors
                username_selectors = [
                    'input[name*="user"]',
                    'input[id*="user"]',
                    'input[placeholder*="user" i]',
                    'input[type="text"]',
                ]
                username_filled = False
                for selector in username_selectors:
                    try:
                        username_field = self.page.locator(selector).first
                        if username_field.count() > 0 and username_field.is_visible():
                            self.human_type(self.page, selector, form_data['username'])
                            logger.debug(f"Filled username with selector: {selector}")
                            username_filled = True
                            break
                    except:
                        continue
                
                if not username_filled:
                    logger.warning("Could not find username field")
            
            # Fill email
            if 'email' in field_mappings:
                email_field, confidence = field_mappings['email']
                logger.info(f"Found email field via role matcher (confidence: {confidence:.2f})")
                if email_field.is_visible():
                    self.human_type(self.page, email_field, form_data['email'])
            else:
                # Fallback to hardcoded selectors
                email_selectors = [
                    'input[type="email"]',
                    'input[name*="email"]',
                    'input[id*="email"]',
                    'input[placeholder*="email" i]',
                ]
                email_filled = False
                for selector in email_selectors:
                    try:
                        email_field = self.page.locator(selector).first
                        if email_field.count() > 0 and email_field.is_visible():
                            self.human_type(self.page, selector, form_data['email'])
                            logger.debug(f"Filled email with selector: {selector}")
                            email_filled = True
                            break
                    except:
                        continue
                
                if not email_filled:
                    logger.warning("Could not find email field")
            
            # Fill password
            if 'password' in field_mappings:
                password_field, confidence = field_mappings['password']
                logger.info(f"Found password field via role matcher (confidence: {confidence:.2f})")
                if password_field.is_visible():
                    self.human_type(self.page, password_field, form_data['password'])
                    
                    # Try to find confirm password (second password field)
                    password_fields = self.page.locator('input[type="password"]')
                    password_count = password_fields.count()
                    if password_count > 1:
                        try:
                            confirm_password = password_fields.nth(1)
                            if confirm_password.is_visible():
                                self.human_type(self.page, confirm_password, form_data['password'])
                                logger.debug("Filled confirm password field")
                        except:
                            pass
            else:
                # Fallback to hardcoded selector
                password_fields = self.page.locator('input[type="password"]')
                password_count = password_fields.count()
                
                if password_count > 0:
                    try:
                        password_field = password_fields.first
                        if password_field.is_visible():
                            self.human_type(self.page, 'input[type="password"]', form_data['password'])
                            logger.debug("Filled password field")
                            
                            # Fill confirm password if exists
                            if password_count > 1:
                                try:
                                    confirm_password = password_fields.nth(1)
                                    if confirm_password.is_visible():
                                        self.human_type(self.page, 'input[type="password"]', form_data['password'])
                                        logger.debug("Filled confirm password field")
                                except:
                                    pass
                    except Exception as e:
                        logger.warning(f"Error filling password: {e}")
                else:
                    logger.warning("Could not find password field")
            
            # Fill website field
            if 'website' in field_mappings:
                website_field, confidence = field_mappings['website']
                logger.info(f"Found website field via role matcher (confidence: {confidence:.2f})")
                if website_field.is_visible():
                    campaign = self.api_client.get_campaign(task['campaign_id'])
                    website_url = campaign.get('web_url', '')
                    if website_url:
                        self.human_type(self.page, website_field, website_url)
            else:
                # Fallback to hardcoded selectors
                website_selectors = [
                    'input[name*="url"]',
                    'input[name*="website"]',
                    'input[name*="site"]',
                    'input[id*="url"]',
                    'input[id*="website"]',
                ]
                for selector in website_selectors:
                    try:
                        website_field = self.page.locator(selector).first
                        if website_field.count() > 0 and website_field.is_visible():
                            campaign = self.api_client.get_campaign(task['campaign_id'])
                            website_url = campaign.get('web_url', '')
                            if website_url:
                                self.human_type(self.page, selector, website_url)
                                logger.debug(f"Filled website field with selector: {selector}")
                                break
                    except:
                        continue
        except ImportError:
            # Fallback if field role matcher not available
            logger.debug("FieldRoleMatcher not available, using hardcoded selectors")
            # Original hardcoded logic
            username_selectors = [
                'input[name*="user"]',
                'input[id*="user"]',
                'input[placeholder*="user" i]',
                'input[type="text"]',
            ]
            username_filled = False
            for selector in username_selectors:
                try:
                    username_field = self.page.locator(selector).first
                    if username_field.count() > 0 and username_field.is_visible():
                        self.human_type(self.page, selector, form_data['username'])
                        logger.debug(f"Filled username with selector: {selector}")
                        username_filled = True
                        break
                except:
                    continue
            
            if not username_filled:
                logger.warning("Could not find username field")
            
            # Find and fill email
            email_selectors = [
                'input[type="email"]',
                'input[name*="email"]',
                'input[id*="email"]',
                'input[placeholder*="email" i]',
            ]
            email_filled = False
            for selector in email_selectors:
                try:
                    email_field = self.page.locator(selector).first
                    if email_field.count() > 0 and email_field.is_visible():
                        self.human_type(self.page, selector, form_data['email'])
                        logger.debug(f"Filled email with selector: {selector}")
                        email_filled = True
                        break
                except:
                    continue
            
            if not email_filled:
                logger.warning("Could not find email field")
            
            # Find and fill password
            password_fields = self.page.locator('input[type="password"]')
            password_count = password_fields.count()
            
            if password_count > 0:
                try:
                    password_field = password_fields.first
                    if password_field.is_visible():
                        self.human_type(self.page, 'input[type="password"]', form_data['password'])
                        logger.debug("Filled password field")
                        
                        # Fill confirm password if exists
                        if password_count > 1:
                            try:
                                confirm_password = password_fields.nth(1)
                                if confirm_password.is_visible():
                                    self.human_type(self.page, 'input[type="password"]', form_data['password'])
                                    logger.debug("Filled confirm password field")
                            except:
                                pass
                except Exception as e:
                    logger.warning(f"Error filling password: {e}")
            else:
                logger.warning("Could not find password field")
            
            # Fill website field if exists
            website_selectors = [
                'input[name*="url"]',
                'input[name*="website"]',
                'input[name*="site"]',
                'input[id*="url"]',
                'input[id*="website"]',
            ]
            for selector in website_selectors:
                try:
                    website_field = self.page.locator(selector).first
                    if website_field.count() > 0 and website_field.is_visible():
                        campaign = self.api_client.get_campaign(task['campaign_id'])
                        website_url = campaign.get('web_url', '')
                        if website_url:
                            self.human_type(self.page, selector, website_url)
                            logger.debug(f"Filled website field with selector: {selector}")
                            break
                except:
                    continue
        
        self.random_delay(1, 2)
        logger.info("Form filling completed")
    
    def _submit_registration_form(self) -> bool:
        """Submit registration form with multiple selector attempts"""
        # Try multiple selectors in order of preference
        submit_selectors = [
            'button[type="submit"]',
            'input[type="submit"]',
            'button:has-text("Register")',
            'button:has-text("Sign Up")',
            'button:has-text("Create Account")',
            'button:has-text("Join")',
            'button:has-text("Submit")',
            'form button',
            'button.primary',
            'button.btn-primary',
            'button[class*="submit"]',
            'button[class*="register"]',
            'button[class*="signup"]',
        ]
        
        for selector in submit_selectors:
            try:
                submit_button = self.page.locator(selector).first
                
                # Check if button exists and is visible
                if submit_button.count() > 0:
                    # Wait for button to be visible and enabled
                    try:
                        submit_button.wait_for(state='visible', timeout=5000)
                        if submit_button.is_visible():
                            # Check if button is enabled
                            is_disabled = submit_button.get_attribute('disabled')
                            if is_disabled:
                                logger.warning(f"Submit button found but is disabled: {selector}")
                                continue
                            
                            logger.info(f"Clicking submit button with selector: {selector}")
                            submit_button.click(timeout=10000)
                            logger.info("Submit button clicked successfully")
                            return True
                    except Exception as e:
                        logger.debug(f"Button with selector '{selector}' not ready: {e}")
                        continue
            except Exception as e:
                logger.debug(f"Error trying selector '{selector}': {e}")
                continue
        
        # If no button found, try to find form and submit it directly
        try:
            form = self.page.locator('form').first
            if form.count() > 0:
                logger.info("Trying to submit form directly")
                form.evaluate('form => form.submit()')
                logger.info("Form submitted directly")
                return True
        except Exception as e:
            logger.warning(f"Could not submit form directly: {e}")
        
        logger.error("Could not find any submit button or form")
        return False
    
    def _get_profile_url(self) -> str:
        """Get profile URL after registration"""
        try:
            # Wait for redirect to profile
            self.page.wait_for_timeout(3000)
            
            # Check if we're on a profile page
            if '/profile' in self.page.url or '/user' in self.page.url:
                return self.page.url
            
            # Try to find profile link
            profile_link = self.page.locator('a[href*="profile"], a[href*="user"]').first
            if profile_link.is_visible():
                return profile_link.get_attribute('href')
            
            return self.page.url
        except:
            return self.page.url
    
    def _extract_domain(self, url: str) -> str:
        """Extract domain from URL"""
        from urllib.parse import urlparse
        parsed = urlparse(url)
        return parsed.netloc

