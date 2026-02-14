"""
Guest post submission automation
"""
from typing import Dict, Optional
import logging
from .base import BaseAutomation

logger = logging.getLogger(__name__)


class GuestPostAutomation(BaseAutomation):
    """Automation for guest post submissions"""
    
    def execute(self, task: Dict) -> Dict:
        """Execute guest post submission task"""
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
                    task_type='guest'
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
            logger.info(f"Processing guest post submission for {target_url}")
            
            # Navigate to submission page
            submission_url = self._find_submission_url(target_url)
            if not self._safe_navigate(submission_url, wait_until='networkidle'):
                return {
                    'success': False,
                    'error': 'Browser crashed during navigation',
                    'backlink_id': opportunity.get('id') if opportunity else None,
                }
            self.random_delay(2, 4)
            
            # Generate guest post pitch
            pitch = self._generate_pitch(keywords, task)
            
            # Fill submission form
            form_data = self._extract_form_fields()
            self._fill_submission_form(form_data, pitch, task)
            
            # Submit form
            try:
                self.solve_captcha_if_present()
            except Exception as e:
                logger.debug(f"Captcha solve attempt failed: {e}")

            submit_button = self.page.locator('button[type="submit"], button:has-text("Submit"), button:has-text("Send")').first
            submit_button.click()
            
            self.random_delay(3, 5)
            
            # Check if submission was successful
            if self._verify_submission_success():
                result = {
                    'success': True,
                    'url': self.page.url,
                    'type': 'guestposting',
                    'status': 'submitted',
                }
                
                # Include opportunity ID if available
                if opportunity:
                    result['backlink_id'] = opportunity.get('id')
                
                return result
            else:
                return {
                    'success': False,
                    'error': 'Submission verification failed',
                }
                
        except Exception as e:
            logger.error(f"Guest post automation failed: {e}", exc_info=True)
            return {
                'success': False,
                'error': str(e),
            }
    
    def _find_submission_url(self, base_url: str) -> str:
        """Find guest post submission URL"""
        submission_paths = [
            '/write-for-us',
            '/guest-post',
            '/submit-article',
            '/contribute',
            '/contact',
        ]
        
        for path in submission_paths:
            try:
                url = f"{base_url.rstrip('/')}{path}"
                if not self._safe_navigate(url, wait_until='networkidle', timeout=5000):
                    logger.warning(f"Failed to navigate to {url}")
                    continue
                if any(keyword in self.page.url.lower() for keyword in ['write', 'guest', 'submit', 'contribute']):
                    return self.page.url
            except:
                continue
        
        return base_url
    
    def _extract_form_fields(self) -> Dict:
        """Extract form field selectors"""
        fields = {}
        
        # Common field patterns
        field_patterns = {
            'name': ['input[name*="name"]', 'input[id*="name"]'],
            'email': ['input[type="email"]', 'input[name*="email"]'],
            'subject': ['input[name*="subject"]', 'input[name*="title"]'],
            'message': ['textarea', 'textarea[name*="message"]', 'textarea[name*="content"]'],
            'website': ['input[name*="url"]', 'input[name*="website"]', 'input[name*="site"]'],
        }
        
        for field_name, selectors in field_patterns.items():
            for selector in selectors:
                try:
                    element = self.page.locator(selector).first
                    if element.is_visible():
                        fields[field_name] = selector
                        break
                except:
                    continue
        
        return fields
    
    def _fill_submission_form(self, fields: Dict, pitch: str, task: Dict):
        """Fill guest post submission form"""
        # Fill name
        if 'name' in fields:
            self.human_type(self.page, fields['name'], "John Doe")
        
        # Fill email
        if 'email' in fields:
            self.human_type(self.page, fields['email'], "writer@example.com")
        
        # Fill subject/title
        if 'subject' in fields:
            campaign = self.api_client.get_campaign(task['campaign_id'])
            subject = f"Guest Post: {campaign.get('web_name', 'Article Submission')}"
            self.human_type(self.page, fields['subject'], subject)
        
        # Fill message/pitch
        if 'message' in fields:
            self.human_type(self.page, fields['message'], pitch)
        
        # Fill website URL
        if 'website' in fields:
            campaign = self.api_client.get_campaign(task['campaign_id'])
            website_url = campaign.get('web_url', '')
            if website_url:
                self.human_type(self.page, fields['website'], website_url)
        
        self.random_delay(1, 2)
    
    def _generate_pitch(self, keywords: list, task: Dict) -> str:
        """Generate guest post pitch using LLM"""
        try:
            campaign = self.api_client.get_campaign(task['campaign_id'])
            keyword = keywords[0] if keywords else "topic"
            target_url = task.get('payload', {}).get('target_url', '')
            tone = task.get('payload', {}).get('content_tone', 'professional')
            
            # Get blog name from page
            blog_name = self.page.title() if self.page else ""
            try:
                # Try to get site name from meta or header
                site_name = self.page.locator('meta[property="og:site_name"]').get_attribute('content')
                if site_name:
                    blog_name = site_name
            except:
                pass
            
            # Generate using LLM
            pitch = self.api_client.generate_content(
                'guest_post_pitch',
                {
                    'blog_name': blog_name,
                    'target_url': target_url,
                    'proposed_topic': keyword,
                },
                tone
            )
            
            if pitch:
                return pitch.strip()
        except Exception as e:
            logger.warning(f"LLM guest post pitch generation failed: {e}")
        
        # Fallback to simple pitch
        campaign = self.api_client.get_campaign(task['campaign_id'])
        keyword = keywords[0] if keywords else "topic"
        
        return f"""
        Hi there,
        
        I'm interested in submitting a guest post about {keyword} for your blog.
        I have extensive experience in this area and believe my content would be valuable for your readers.
        
        My website: {campaign.get('web_url', '')}
        
        Please let me know if you'd be interested in reviewing my submission.
        
        Best regards
        """.strip()
    
    def _verify_submission_success(self) -> bool:
        """Verify submission was successful"""
        try:
            # Look for success message
            success_indicators = [
                'thank you',
                'submitted',
                'received',
                'success',
            ]
            
            page_text = self.page.content().lower()
            return any(indicator in page_text for indicator in success_indicators)
        except:
            return False

