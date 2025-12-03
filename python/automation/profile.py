"""
Profile backlink automation
"""
from typing import Dict
import logging
import random
from .base import BaseAutomation

logger = logging.getLogger(__name__)


class ProfileAutomation(BaseAutomation):
    """Automation for profile backlinks"""
    
    def execute(self, task: Dict) -> Dict:
        """Execute profile backlink task"""
        payload = task.get('payload', {})
        target_urls = payload.get('target_urls', [])
        
        if not target_urls:
            return {
                'success': False,
                'error': 'No target URLs provided',
            }
        
        try:
            target_url = target_urls[0]
            logger.info(f"Processing profile backlink for {target_url}")
            
            # Navigate to registration page
            registration_url = self._find_registration_url(target_url)
            self.page.goto(registration_url, wait_until='networkidle')
            self.random_delay(2, 4)
            
            # Fill registration form
            form_data = self._generate_form_data()
            self._fill_registration_form(form_data, task)
            
            # Submit form
            submit_button = self.page.locator('button[type="submit"], input[type="submit"], button:has-text("Register"), button:has-text("Sign Up")').first
            submit_button.click()
            
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
                
                return {
                    'success': True,
                    'url': profile_url,
                    'type': 'profile',
                    'site_account_id': site_account.get('id'),
                }
            else:
                return {
                    'success': False,
                    'error': 'Profile URL not found',
                }
                
        except Exception as e:
            logger.error(f"Profile automation failed: {e}", exc_info=True)
            return {
                'success': False,
                'error': str(e),
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
                self.page.goto(url, wait_until='networkidle', timeout=5000)
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
        """Fill registration form"""
        # Find and fill username
        username_field = self.page.locator('input[name*="user"], input[id*="user"], input[type="text"]').first
        if username_field.is_visible():
            self.human_type(self.page, username_field, form_data['username'])
        
        # Find and fill email
        email_field = self.page.locator('input[type="email"], input[name*="email"]').first
        if email_field.is_visible():
            self.human_type(self.page, email_field, form_data['email'])
        
        # Find and fill password
        password_field = self.page.locator('input[type="password"]').first
        if password_field.is_visible():
            self.human_type(self.page, password_field, form_data['password'])
        
        # Find and fill confirm password if exists
        confirm_password = self.page.locator('input[type="password"]').nth(1)
        if confirm_password.is_visible():
            self.human_type(self.page, confirm_password, form_data['password'])
        
        # Fill website field if exists
        website_field = self.page.locator('input[name*="url"], input[name*="website"], input[name*="site"]').first
        if website_field.is_visible():
            campaign = self.api_client.get_campaign(task['campaign_id'])
            website_url = campaign.get('web_url', '')
            if website_url:
                self.human_type(self.page, website_field, website_url)
        
        self.random_delay(1, 2)
    
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

