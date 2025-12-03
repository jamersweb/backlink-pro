"""
Captcha solving integration for automation tasks
"""

import logging
import time
from typing import Optional, Dict
from playwright.sync_api import Page

logger = logging.getLogger(__name__)


class CaptchaSolver:
    """Helper class for solving captchas"""
    
    def __init__(self, api_client):
        self.api_client = api_client
    
    def detect_and_solve(self, page: Page) -> Optional[Dict]:
        """
        Detect captcha on page and solve it
        Returns solution dict or None
        """
        try:
            # Detect captcha type
            page_html = page.content()
            captcha_type = self._detect_captcha_type(page_html)
            
            if not captcha_type:
                return None
            
            logger.info(f"Detected captcha type: {captcha_type}")
            
            # Extract captcha details
            captcha_data = self._extract_captcha_data(page, captcha_type)
            if not captcha_data:
                logger.warning("Could not extract captcha data")
                return None
            
            # Solve captcha via API
            solution = self.api_client.solve_captcha(captcha_type, captcha_data)
            if not solution:
                logger.error("Failed to solve captcha")
                return None
            
            # Inject solution
            self._inject_solution(page, captcha_type, solution)
            
            logger.info("Captcha solved and injected successfully")
            return solution
            
        except Exception as e:
            logger.error(f"Captcha solving failed: {e}", exc_info=True)
            return None
    
    def _detect_captcha_type(self, page_html: str) -> Optional[str]:
        """Detect captcha type from page HTML"""
        page_html_lower = page_html.lower()
        
        # Check for reCAPTCHA v2
        if 'recaptcha' in page_html_lower and ('data-sitekey' in page_html_lower or 'grecaptcha' in page_html_lower):
            if 'v3' in page_html_lower or 'recaptchaenterprise' in page_html_lower:
                return 'recaptcha_v3'
            return 'recaptcha_v2'
        
        # Check for hCaptcha
        if 'hcaptcha' in page_html_lower or 'h-captcha' in page_html_lower:
            return 'hcaptcha'
        
        # Check for image captcha
        if 'captcha' in page_html_lower and ('img' in page_html_lower or 'image' in page_html_lower):
            return 'image'
        
        return None
    
    def _extract_captcha_data(self, page: Page, captcha_type: str) -> Optional[Dict]:
        """Extract captcha data needed for solving"""
        page_url = page.url
        
        try:
            if captcha_type in ['recaptcha_v2', 'recaptcha_v3']:
                # Extract site key
                site_key = None
                try:
                    # Try to find site key in iframe or script
                    iframe = page.locator('iframe[src*="recaptcha"]').first
                    if iframe.is_visible():
                        src = iframe.get_attribute('src')
                        import re
                        match = re.search(r'k=([^&]+)', src)
                        if match:
                            site_key = match.group(1)
                except:
                    pass
                
                if not site_key:
                    # Try to find in page source
                    page_html = page.content()
                    import re
                    match = re.search(r'data-sitekey=["\']([^"\']+)["\']', page_html)
                    if match:
                        site_key = match.group(1)
                
                if not site_key:
                    return None
                
                return {
                    'site_key': site_key,
                    'page_url': page_url,
                }
            
            elif captcha_type == 'hcaptcha':
                # Extract hCaptcha site key
                site_key = None
                try:
                    iframe = page.locator('iframe[src*="hcaptcha"]').first
                    if iframe.is_visible():
                        src = iframe.get_attribute('src')
                        import re
                        match = re.search(r'sitekey=([^&]+)', src)
                        if match:
                            site_key = match.group(1)
                except:
                    pass
                
                if not site_key:
                    page_html = page.content()
                    import re
                    match = re.search(r'data-sitekey=["\']([^"\']+)["\']', page_html)
                    if match:
                        site_key = match.group(1)
                
                if not site_key:
                    return None
                
                return {
                    'site_key': site_key,
                    'page_url': page_url,
                }
            
            elif captcha_type == 'image':
                # Extract image captcha
                try:
                    captcha_img = page.locator('img[src*="captcha"], img[id*="captcha"]').first
                    if captcha_img.is_visible():
                        img_src = captcha_img.get_attribute('src')
                        # Download image and convert to base64
                        import base64
                        import requests
                        
                        if img_src.startswith('data:'):
                            # Already base64
                            img_data = img_src.split(',')[1]
                        else:
                            # Download image
                            if not img_src.startswith('http'):
                                img_src = page_url.rsplit('/', 1)[0] + '/' + img_src.lstrip('/')
                            response = requests.get(img_src)
                            img_data = base64.b64encode(response.content).decode('utf-8')
                        
                        return {
                            'image': img_data,
                        }
                except Exception as e:
                    logger.warning(f"Failed to extract image captcha: {e}")
                    return None
            
        except Exception as e:
            logger.error(f"Failed to extract captcha data: {e}")
            return None
        
        return None
    
    def _inject_solution(self, page: Page, captcha_type: str, solution: Dict):
        """Inject captcha solution into page"""
        try:
            solution_token = solution.get('solution') or solution.get('token')
            if not solution_token:
                logger.error("No solution token provided")
                return
            
            if captcha_type in ['recaptcha_v2', 'recaptcha_v3']:
                # Inject reCAPTCHA solution
                page.evaluate(f"""
                    (function() {{
                        // Find textarea for g-recaptcha-response
                        var textarea = document.querySelector('textarea[name="g-recaptcha-response"]');
                        if (textarea) {{
                            textarea.value = '{solution_token}';
                            textarea.dispatchEvent(new Event('input', {{ bubbles: true }}));
                        }}
                        
                        // Also set callback if exists
                        if (window.grecaptcha && window.grecaptcha.getResponse) {{
                            var callback = window.grecaptcha.getResponse();
                            if (callback) {{
                                window[callback]('{solution_token}');
                            }}
                        }}
                    }})();
                """)
            
            elif captcha_type == 'hcaptcha':
                # Inject hCaptcha solution
                page.evaluate(f"""
                    (function() {{
                        var textarea = document.querySelector('textarea[name="h-captcha-response"]');
                        if (textarea) {{
                            textarea.value = '{solution_token}';
                            textarea.dispatchEvent(new Event('input', {{ bubbles: true }}));
                        }}
                    }})();
                """)
            
            elif captcha_type == 'image':
                # Fill image captcha input
                solution_text = solution_token
                page.evaluate(f"""
                    (function() {{
                        var input = document.querySelector('input[name*="captcha"], input[id*="captcha"]');
                        if (input) {{
                            input.value = '{solution_text}';
                            input.dispatchEvent(new Event('input', {{ bubbles: true }}));
                        }}
                    }})();
                """)
            
            # Wait a bit for injection to take effect
            time.sleep(1)
            
        except Exception as e:
            logger.error(f"Failed to inject captcha solution: {e}")

