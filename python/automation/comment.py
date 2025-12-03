"""
Comment backlink automation
"""
from typing import Dict
import logging
from .base import BaseAutomation

logger = logging.getLogger(__name__)


class CommentAutomation(BaseAutomation):
    """Automation for comment backlinks"""
    
    def execute(self, task: Dict) -> Dict:
        """Execute comment backlink task"""
        payload = task.get('payload', {})
        target_urls = payload.get('target_urls', [])
        keywords = payload.get('keywords', [])
        anchor_text_strategy = payload.get('anchor_text_strategy', 'variation')
        
        if not target_urls:
            return {
                'success': False,
                'error': 'No target URLs provided',
            }
        
        try:
            # Select random target URL
            target_url = target_urls[0] if len(target_urls) == 1 else target_urls[random.randint(0, len(target_urls) - 1)]
            
            logger.info(f"Processing comment backlink for {target_url}")
            
            # Navigate to page
            self.page.goto(target_url, wait_until='networkidle')
            self.random_delay(2, 4)
            
            # Find comment form
            comment_form = self._find_comment_form()
            if not comment_form:
                return {
                    'success': False,
                    'error': 'Comment form not found',
                }
            
            # Generate comment content using LLM
            comment_text = self._generate_comment(keywords, anchor_text_strategy, task)
            
            # Fill form fields
            self._fill_comment_form(comment_form, comment_text, task)
            
            # Submit form
            submit_button = comment_form.locator('button[type="submit"], input[type="submit"], button:has-text("Post"), button:has-text("Submit")').first
            submit_button.click()
            
            self.random_delay(2, 4)
            
            # Check if comment was posted
            if self._verify_comment_posted(comment_text):
                # Get comment URL
                comment_url = self.page.url
                
                return {
                    'success': True,
                    'url': comment_url,
                    'type': 'comment',
                }
            else:
                return {
                    'success': False,
                    'error': 'Comment verification failed',
                }
                
        except Exception as e:
            logger.error(f"Comment automation failed: {e}", exc_info=True)
            return {
                'success': False,
                'error': str(e),
            }
    
    def _find_comment_form(self):
        """Find comment form on page"""
        # Common comment form selectors
        selectors = [
            'form[class*="comment"]',
            'form[id*="comment"]',
            'textarea[name*="comment"]',
            'div[class*="comment-form"]',
            'form:has(textarea)',
        ]
        
        for selector in selectors:
            try:
                form = self.page.locator(selector).first
                if form.is_visible():
                    return form
            except:
                continue
        
        return None
    
    def _fill_comment_form(self, form, comment_text: str, task: Dict):
        """Fill comment form fields"""
        # Find textarea or input for comment
        textarea = form.locator('textarea, input[type="text"]').first
        if textarea.is_visible():
            self.human_type(self.page, textarea, comment_text)
        
        # Fill name field if exists
        name_field = form.locator('input[name*="name"], input[id*="name"]').first
        if name_field.is_visible():
            self.human_type(self.page, name_field, "John Doe")
        
        # Fill email field if exists
        email_field = form.locator('input[type="email"], input[name*="email"]').first
        if email_field.is_visible():
            self.human_type(self.page, email_field, "user@example.com")
        
        # Fill website field if exists
        website_field = form.locator('input[name*="url"], input[name*="website"]').first
        if website_field.is_visible():
            # Get website URL from campaign
            campaign = self.api_client.get_campaign(task['campaign_id'])
            website_url = campaign.get('web_url', '')
            if website_url:
                self.human_type(self.page, website_field, website_url)
        
        self.random_delay(1, 2)
    
    def _generate_comment(self, keywords: list, strategy: str, task: Dict) -> str:
        """Generate comment using LLM"""
        try:
            # Get article title/excerpt from page
            article_title = self.page.title() if self.page else ""
            article_excerpt = ""
            try:
                # Try to get article excerpt/meta description
                meta_desc = self.page.locator('meta[name="description"]').get_attribute('content')
                if meta_desc:
                    article_excerpt = meta_desc[:200]
                else:
                    # Fallback to first paragraph
                    first_p = self.page.locator('article p, .post p, .content p').first
                    if first_p.is_visible():
                        article_excerpt = first_p.text_content()[:200]
            except:
                pass
            
            target_url = task.get('payload', {}).get('target_url', '')
            tone = task.get('payload', {}).get('content_tone', 'professional')
            
            # Generate using LLM
            comment = self.api_client.generate_content(
                'comment',
                {
                    'article_title': article_title,
                    'article_excerpt': article_excerpt,
                    'target_url': target_url,
                },
                tone
            )
            
            if comment:
                return comment.strip()
        except Exception as e:
            logger.warning(f"LLM comment generation failed: {e}")
        
        # Fallback to simple comment
        keyword = keywords[0] if keywords else "great article"
        return f"This is a {keyword} article. Thanks for sharing!"
    
    def _verify_comment_posted(self, comment_text: str) -> bool:
        """Verify comment was posted"""
        try:
            # Wait for comment to appear
            self.page.wait_for_timeout(3000)
            
            # Check if comment text appears on page
            return self.page.locator(f'text={comment_text[:50]}').is_visible(timeout=5000)
        except:
            return False

