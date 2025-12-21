"""
Forum backlink automation
"""
from typing import Dict, Optional
import logging
import random
from .base import BaseAutomation

logger = logging.getLogger(__name__)


class ForumAutomation(BaseAutomation):
    """Automation for forum backlinks"""
    
    def execute(self, task: Dict) -> Dict:
        """Execute forum backlink task"""
        payload = task.get('payload', {})
        keywords = payload.get('keywords', [])
        campaign_id = task.get('campaign_id')
        
        # Use opportunity selector if available, otherwise fallback to payload target_urls
        target_url = None
        opportunity = None
        
        if self.opportunity_selector and campaign_id:
            try:
                opportunity = self.opportunity_selector.select_opportunity(
                    campaign_id=campaign_id,
                    task_type='forum'
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
            logger.info(f"Processing forum backlink for {target_url}")
            
            # Check if site account exists
            site_account = self._get_site_account(target_url)
            
            if not site_account:
                # Need to create account first
                return {
                    'success': False,
                    'error': 'Site account not found. Create profile first.',
                }
            
            # Login if needed
            if not self._is_logged_in():
                self._login(site_account)
            
            # Find or create thread
            thread_url = self._find_or_create_thread(keywords)
            
            if not thread_url:
                return {
                    'success': False,
                    'error': 'Failed to find or create thread',
                }
            
            # Generate forum post
            post_text = self._generate_post(keywords, task)
            
            # Post reply
            post_url = self._post_reply(thread_url, post_text)
            
            if post_url:
                result = {
                    'success': True,
                    'url': post_url,
                    'type': 'forum',
                }
                
                # Include opportunity ID if available
                if opportunity:
                    result['backlink_id'] = opportunity.get('id')
                
                return result
            else:
                return {
                    'success': False,
                    'error': 'Failed to post reply',
                }
                
        except Exception as e:
            logger.error(f"Forum automation failed: {e}", exc_info=True)
            return {
                'success': False,
                'error': str(e),
            }
    
    def _get_site_account(self, domain: str):
        """Get site account for domain"""
        # This would query the API for site account
        # For now, return None
        return None
    
    def _is_logged_in(self) -> bool:
        """Check if user is logged in"""
        try:
            # Look for logout link or user profile indicator
            logout_link = self.page.locator('a[href*="logout"], a[href*="signout"]').first
            return logout_link.is_visible(timeout=3000)
        except:
            return False
    
    def _login(self, site_account: Dict):
        """Login to forum"""
        # Find login form
        login_form = self.page.locator('form[class*="login"], form[id*="login"]').first
        
        if login_form.is_visible():
            # Fill login form
            username_field = login_form.locator('input[name*="user"], input[type="text"]').first
            password_field = login_form.locator('input[type="password"]').first
            
            if username_field.is_visible() and password_field.is_visible():
                self.human_type(self.page, username_field, site_account.get('username', ''))
                self.human_type(self.page, password_field, site_account.get('password', ''))
                
                # Submit
                submit_button = login_form.locator('button[type="submit"], input[type="submit"]').first
                submit_button.click()
                
                self.random_delay(2, 4)
    
    def _find_or_create_thread(self, keywords: list) -> str:
        """Find or create thread"""
        # Search for existing thread
        search_url = f"{self.page.url}/search"
        if not self._safe_navigate(search_url, wait_until='networkidle'):
            return None
        
        # Try to find thread with keywords
        keyword = keywords[0] if keywords else "discussion"
        search_field = self.page.locator('input[name*="search"], input[type="search"]').first
        
        if search_field.is_visible():
            self.human_type(self.page, search_field, keyword)
            search_button = self.page.locator('button:has-text("Search"), button[type="submit"]').first
            search_button.click()
            self.random_delay(2, 4)
            
            # Check if results found
            thread_link = self.page.locator('a[href*="thread"], a[href*="topic"]').first
            if thread_link.is_visible():
                return thread_link.get_attribute('href')
        
        # Create new thread if not found
        return self._create_thread(keywords)
    
    def _create_thread(self, keywords: list) -> str:
        """Create new thread"""
        # Find new thread button
        new_thread_button = self.page.locator('a:has-text("New Thread"), a:has-text("Create Thread"), a[href*="new"]').first
        
        if new_thread_button.is_visible():
            new_thread_button.click()
            self.random_delay(2, 4)
            
            # Fill thread form
            title_field = self.page.locator('input[name*="title"], input[name*="subject"]').first
            content_field = self.page.locator('textarea, div[contenteditable="true"]').first
            
            if title_field.is_visible() and content_field.is_visible():
                keyword = keywords[0] if keywords else "Discussion"
                self.human_type(self.page, title_field, f"Discussion about {keyword}")
                self.human_type(self.page, content_field, f"I'd like to discuss {keyword}...")
                
                # Submit
                submit_button = self.page.locator('button[type="submit"], button:has-text("Post")').first
                submit_button.click()
                self.random_delay(2, 4)
                
                return self.page.url
        
        return None
    
    def _post_reply(self, thread_url: str, post_text: str) -> str:
        """Post reply to thread"""
        if not self._safe_navigate(thread_url, wait_until='networkidle'):
            return None
        self.random_delay(1, 2)
        
        # Find reply form
        reply_form = self.page.locator('form[class*="reply"], textarea[name*="reply"]').first
        
        if reply_form.is_visible():
            textarea = reply_form.locator('textarea, div[contenteditable="true"]').first
            if textarea.is_visible():
                self.human_type(self.page, textarea, post_text)
                
                # Submit
                submit_button = reply_form.locator('button[type="submit"], button:has-text("Post")').first
                submit_button.click()
                self.random_delay(2, 4)
                
                return self.page.url
        
        return None
    
    def _generate_post(self, keywords: list, task: Dict) -> str:
        """Generate forum post using LLM"""
        try:
            topic = keywords[0] if keywords else "topic"
            target_url = task.get('payload', {}).get('target_url', '')
            tone = task.get('payload', {}).get('content_tone', 'professional')
            
            # Generate using LLM
            post = self.api_client.generate_content(
                'forum_post',
                {
                    'topic': topic,
                    'target_url': target_url,
                },
                tone
            )
            
            if post:
                return post.strip()
        except Exception as e:
            logger.warning(f"LLM forum post generation failed: {e}")
        
        # Fallback to simple post
        keyword = keywords[0] if keywords else "topic"
        return f"This is a great discussion about {keyword}. I found this very informative!"

