"""
Comment backlink automation
"""
from typing import Dict, Optional
import logging
import random
from .base import BaseAutomation

# Import iframe router
try:
    from core.iframe_router import IframeRouter
except ImportError:
    IframeRouter = None

logger = logging.getLogger(__name__)


class CommentAutomation(BaseAutomation):
    """Automation for comment backlinks"""
    
    def execute(self, task: Dict) -> Dict:
        """Execute comment backlink task"""
        payload = task.get('payload', {})
        keywords = payload.get('keywords', [])
        anchor_text_strategy = payload.get('anchor_text_strategy', 'variation')
        campaign_id = task.get('campaign_id')
        
        # Use opportunity selector if available, otherwise fallback to payload target_urls
        target_url = None
        opportunity = None
        
        if self.opportunity_selector and campaign_id:
            try:
                opportunity = self.opportunity_selector.select_opportunity(
                    campaign_id=campaign_id,
                    task_type='comment'
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
                    'backlink_id': opportunity.get('id') if opportunity else None,
                }
            target_url = target_urls[0] if len(target_urls) == 1 else target_urls[random.randint(0, len(target_urls) - 1)]
        
        try:
            logger.info(f"Processing comment backlink for {target_url}")
            
            # Navigate to page with safe navigation (includes retry logic)
            # Use 'domcontentloaded' instead of 'networkidle' for better stability
            if not self._safe_navigate(target_url, wait_until='domcontentloaded', timeout=30000):
                logger.error(f"Failed to navigate to {target_url} after retries")
                return {
                    'success': False,
                    'error': 'Browser crashed during navigation',
                    'backlink_id': opportunity.get('id') if opportunity else None,
                }  # Re-raise if it's a different error
            self.random_delay(2, 4)
            
            # Find comment form
            comment_form = self._find_comment_form()
            if not comment_form:
                return {
                    'success': False,
                    'error': 'Comment form not found',
                    'backlink_id': opportunity.get('id') if opportunity else None,
                    'failure_reason': 'comment_form_not_found',
                }
            
            # Generate comment content using LLM
            comment_text = self._generate_comment(keywords, anchor_text_strategy, task)
            
            # Check for captcha before filling form
            captcha_type = None
            if self.captcha_solver and self.page:
                try:
                    # Check if captcha is present
                    page_html = self.page.content()
                    if 'recaptcha' in page_html.lower() or 'hcaptcha' in page_html.lower() or 'captcha' in page_html.lower():
                        # Try to solve captcha
                        captcha_solved = self.solve_captcha_if_present()
                        if not captcha_solved:
                            # Extract captcha type
                            if 'recaptcha' in page_html.lower():
                                captcha_type = 'recaptcha_v2' if 'v3' not in page_html.lower() else 'recaptcha_v3'
                            elif 'hcaptcha' in page_html.lower():
                                captcha_type = 'hcaptcha'
                            else:
                                captcha_type = 'image_captcha'
                except Exception as e:
                    logger.debug(f"Error checking for captcha: {e}")
            
            # Fill form fields (handles regular forms, textareas, and third-party systems)
            fill_result = self._fill_comment_form(comment_form, comment_text, task)
            
            # For third-party systems, submission is handled in _fill_comment_form
            if isinstance(comment_form, dict) and comment_form.get('type') == 'disqus':
                if fill_result:
                    # Disqus form was submitted, wait a bit and check result
                    self.random_delay(3, 5)
                    return {
                        'success': True,
                        'message': 'Comment submitted via Disqus',
                        'backlink_id': opportunity.get('id') if opportunity else None,
                    }
                else:
                    return {
                        'success': False,
                        'error': 'Failed to submit Disqus comment',
                        'backlink_id': opportunity.get('id') if opportunity else None,
                    }
            
            # For textareas, find submit button in parent form or nearby
            try:
                # Check if comment_form is a textarea
                tag = comment_form.evaluate('el => el.tagName ? el.tagName.toLowerCase() : null')
                if tag == 'textarea':
                    logger.info("Looking for submit button for textarea...")
                    submit_found = False
                    
                    # Strategy 1: Try to find parent form
                    try:
                        parent_form = comment_form.locator('xpath=ancestor::form[1]')
                        if parent_form.count() > 0:
                            submit_selectors = [
                                'button[type="submit"]',
                                'input[type="submit"]',
                                'button:has-text("Post")',
                                'button:has-text("Submit")',
                                'button:has-text("Comment")',
                                'button:has-text("Reply")',
                                'button:has-text("Send")',
                                'button[class*="submit" i]',
                                'button[class*="post" i]',
                                'button',
                            ]
                            
                            for selector in submit_selectors:
                                try:
                                    submit_button = parent_form.locator(selector).first
                                    if submit_button.count() > 0 and submit_button.is_visible(timeout=2000):
                                        logger.info(f"Found submit button in parent form: {selector}")
                                        submit_button.click()
                                        submit_found = True
                                        break
                                except:
                                    continue
                    except Exception as e:
                        logger.debug(f"Could not find parent form: {e}")
                    
                    # Strategy 2: Look for submit button near the textarea (same container)
                    if not submit_found:
                        try:
                            # Get textarea's parent container
                            container = comment_form.locator('xpath=ancestor::div[1]')
                            if container.count() > 0:
                                submit_selectors = [
                                    'button[type="submit"]',
                                    'input[type="submit"]',
                                    'button:has-text("Post")',
                                    'button:has-text("Submit")',
                                    'button:has-text("Comment")',
                                    'button',
                                ]
                                
                                for selector in submit_selectors:
                                    try:
                                        submit_button = container.locator(selector).first
                                        if submit_button.count() > 0 and submit_button.is_visible(timeout=2000):
                                            logger.info(f"Found submit button in container: {selector}")
                                            submit_button.click()
                                            submit_found = True
                                            break
                                    except:
                                        continue
                        except:
                            pass
                    
                    # Strategy 3: Look anywhere on page for submit button
                    if not submit_found:
                        try:
                            submit_selectors = [
                                'button[type="submit"]',
                                'input[type="submit"]',
                                'button:has-text("Post")',
                                'button:has-text("Submit")',
                            ]
                            
                            for selector in submit_selectors:
                                try:
                                    submit_button = self.page.locator(selector).first
                                    if submit_button.count() > 0 and submit_button.is_visible(timeout=2000):
                                        logger.info(f"Found submit button on page: {selector}")
                                        submit_button.click()
                                        submit_found = True
                                        break
                                except:
                                    continue
                        except:
                            pass
                    
                    if not submit_found:
                        logger.warning("Could not find submit button, trying Enter key on textarea")
                        # Last resort: Try pressing Enter in the textarea
                        try:
                            comment_form.press('Enter')
                            self.random_delay(1, 2)
                            # Also try Ctrl+Enter (common for comments)
                            comment_form.press('Control+Enter')
                            submit_found = True
                        except:
                            logger.error("Could not submit comment - no submit button found")
                            return {
                                'success': False,
                                'error': 'Submit button not found for textarea',
                                'backlink_id': opportunity.get('id') if opportunity else None,
                            }
                    if not submit_found:
                        return {
                            'success': False,
                            'error': 'Submit button not found',
                            'backlink_id': opportunity.get('id') if opportunity else None,
                        }
                else:
                    # Regular form - find submit button in form
                    submit_button = comment_form.locator('button[type="submit"], input[type="submit"], button:has-text("Post"), button:has-text("Submit")').first
                    submit_button.click()
            except Exception as e:
                logger.error(f"Error submitting form: {e}")
                return {
                    'success': False,
                    'error': f'Could not submit form: {e}',
                    'backlink_id': opportunity.get('id') if opportunity else None,
                }
            
            self.random_delay(2, 4)
            
            # Check if comment was posted
            if self._verify_comment_posted(comment_text):
                # Get comment URL
                comment_url = self.page.url
                
                result = {
                    'success': True,
                    'url': comment_url,
                    'type': 'comment',
                }
                
                # Include backlink ID from store if available
                if opportunity:
                    result['backlink_id'] = opportunity.get('id')  # This is now the backlink store ID
                
                return result
            else:
                return {
                    'success': False,
                    'error': 'Comment verification failed',
                    'backlink_id': opportunity.get('id') if opportunity else None,
                    'captcha_type': captcha_type,
                }
                
        except Exception as e:
            error_msg = str(e)
            logger.error(f"Comment automation failed: {error_msg}", exc_info=True)
            
            # Check if browser crashed
            if 'Target closed' in error_msg or 'browser has been closed' in error_msg or 'context has been closed' in error_msg:
                logger.error(f"Browser crashed during automation: {error_msg}")
            
            # Extract captcha type from error if present
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
                'captcha_type': captcha_type,
            }
    
    def _find_comment_form(self):
        """Find comment form on page with improved detection and iframe support"""
        # Wait for page to fully load and any dynamic content
        self.random_delay(3, 5)
        
        # Try to wait for any comment-related elements to appear
        try:
            # Wait for any textarea or comment-related element
            self.page.wait_for_selector('textarea, form, [class*="comment" i], [id*="comment" i]', timeout=5000, state='attached')
        except:
            pass  # Continue even if nothing found
        
        # Additional wait for dynamic content (Disqus, etc.)
        self.random_delay(2, 3)
        
        logger.info("Searching for comment form...")
        
        # Strategy 1: Look for textareas first (most reliable indicator)
        # Try iframe router first
        try:
            if IframeRouter:
                textarea_locator, textarea_frame, textarea_source = IframeRouter.find_input_in_frames(
                    self.page,
                    input_selector='textarea',
                    task_id=getattr(self, '_current_task_id', None)
                )
                if textarea_locator and textarea_locator.count() > 0:
                    textareas = textarea_locator
                    logger.info(f"Found textarea via iframe router in {textarea_source}")
                else:
                    textareas = self.page.locator('textarea')
            else:
                textareas = self.page.locator('textarea')
            textarea_count = textareas.count()
            logger.info(f"Found {textarea_count} textarea(s) on page")
            
            if textarea_count > 0:
                # Special case: if there's only one textarea, use it immediately
                if textarea_count == 1:
                    try:
                        textarea = textareas.first
                        logger.info("Only one textarea found, attempting to use it")
                        # Try to make it visible/usable
                        try:
                            textarea.scroll_into_view_if_needed()
                            self.random_delay(1, 2)
                        except:
                            pass
                        logger.info("Using the only textarea on page")
                        return textarea
                    except Exception as e:
                        logger.warning(f"Could not use single textarea: {e}")
                
                logger.info(f"Checking {min(textarea_count, 5)} textarea(s)...")
                # Check each textarea to see if it's in a comment form
                for i in range(min(textarea_count, 5)):
                    try:
                        textarea = textareas.nth(i)
                        logger.info(f"Checking textarea #{i+1}...")
                        is_visible = False
                        try:
                            is_visible = textarea.is_visible(timeout=2000)
                            logger.info(f"Textarea #{i+1} visibility: {is_visible}")
                        except Exception as vis_e:
                            logger.warning(f"Could not check visibility of textarea #{i+1}: {vis_e}")
                            is_visible = False
                        
                        if is_visible:
                            logger.info(f"Textarea #{i+1} is visible, checking attributes...")
                            # Get textarea attributes
                            name_attr = textarea.get_attribute('name') or ''
                            id_attr = textarea.get_attribute('id') or ''
                            placeholder_attr = textarea.get_attribute('placeholder') or ''
                            class_attr = textarea.get_attribute('class') or ''
                            
                            # Check if textarea has comment-related attributes
                            has_comment_keywords = any(keyword in (name_attr + id_attr + placeholder_attr + class_attr).lower() 
                                                      for keyword in ['comment', 'reply', 'message', 'content', 'post', 'response'])
                            
                            # Try to find parent form
                            parent_form = None
                            try:
                                # Use XPath to find ancestor form
                                parent_form_locator = textarea.locator('xpath=ancestor::form[1]')
                                if parent_form_locator.count() > 0:
                                    try:
                                        if parent_form_locator.is_visible(timeout=1000):
                                            parent_form = parent_form_locator
                                    except:
                                        pass
                            except:
                                pass
                            
                            # If we found a parent form, use it
                            if parent_form:
                                logger.info(f"Found comment form via textarea #{i+1} (parent form)")
                                return parent_form
                            
                            # If textarea has comment keywords, use it directly (even without form)
                            if has_comment_keywords:
                                logger.info(f"Found comment textarea #{i+1} with comment keywords (name={name_attr}, id={id_attr})")
                                return textarea
                            
                            # If no parent form but textarea is large (likely comment field), use it
                            try:
                                bounding_box = textarea.bounding_box()
                                if bounding_box:
                                    # If textarea is reasonably sized (not a tiny search box)
                                    if bounding_box['height'] > 50 and bounding_box['width'] > 200:
                                        logger.info(f"Found large textarea #{i+1} (likely comment field, {bounding_box['width']}x{bounding_box['height']})")
                                        return textarea
                            except Exception as e:
                                logger.debug(f"Could not get bounding box: {e}")
                            
                            # Last resort: if it's the only textarea and it's visible, use it
                            if textarea_count == 1:
                                logger.info(f"Using only textarea on page (textarea #{i+1})")
                                return textarea
                            
                            # If we have multiple textareas, check if this one is likely a comment field
                            # by checking its position and size relative to the page
                            if textarea_count > 1:
                                try:
                                    bounding_box = textarea.bounding_box()
                                    if bounding_box:
                                        # Prefer larger textareas (likely comment fields)
                                        if bounding_box['height'] > 80:
                                            logger.info(f"Using large textarea #{i+1} (height={bounding_box['height']})")
                                            return textarea
                                except:
                                    pass
                                
                    except Exception as e:
                        logger.debug(f"Error checking textarea #{i+1}: {e}")
                        continue
        except Exception as e:
            logger.debug(f"Error searching for textareas: {e}")
        
        # Strategy 2: Look for forms with comment-related classes/IDs
        form_selectors = [
            'form[class*="comment" i]',
            'form[id*="comment" i]',
            'form[class*="reply" i]',
            'form[id*="reply" i]',
            'form#commentform',
            'form.comment-form',
            'form.commentform',
            'form[data-form-id]',  # Disqus
            'form[data-disqus-form]',  # Disqus
        ]
        
        for selector in form_selectors:
            try:
                forms = self.page.locator(selector)
                count = forms.count()
                if count > 0:
                    for i in range(min(count, 3)):
                        form = forms.nth(i)
                        try:
                            if form.is_visible(timeout=2000):
                                # Check if form has a textarea
                                textarea_in_form = form.locator('textarea')
                                if textarea_in_form.count() > 0:
                                    logger.info(f"Found comment form with selector: {selector} (match #{i+1})")
                                    return form
                        except:
                            continue
            except Exception as e:
                logger.debug(f"Selector '{selector}' failed: {e}")
                continue
        
        # Strategy 3: Look for comment form containers
        container_selectors = [
            'div[class*="comment-form" i] form',
            'div[id*="comment-form" i] form',
            'div[class*="reply-form" i] form',
            'section[class*="comment" i] form',
            'article form:has(textarea)',
        ]
        
        for selector in container_selectors:
            try:
                forms = self.page.locator(selector)
                count = forms.count()
                if count > 0:
                    for i in range(min(count, 2)):
                        form = forms.nth(i)
                        try:
                            if form.is_visible(timeout=2000):
                                logger.info(f"Found comment form in container: {selector} (match #{i+1})")
                                return form
                        except:
                            continue
            except:
                continue
        
        # Strategy 4: Generic form with textarea (last resort)
        try:
            forms_with_textarea = self.page.locator('form:has(textarea)')
            count = forms_with_textarea.count()
            if count > 0:
                # Filter out non-comment forms (like search forms)
                for i in range(min(count, 5)):
                    form = forms_with_textarea.nth(i)
                    try:
                        if form.is_visible(timeout=2000):
                            # Check if it's likely a comment form (not search, login, etc.)
                            form_id = form.get_attribute('id') or ''
                            form_class = form.get_attribute('class') or ''
                            form_action = form.get_attribute('action') or ''
                            
                            # Skip if it's clearly not a comment form
                            if any(skip in (form_id + form_class + form_action).lower() 
                                   for skip in ['search', 'login', 'signin', 'register', 'subscribe']):
                                continue
                            
                            logger.info(f"Found generic form with textarea (match #{i+1})")
                            return form
                    except:
                        continue
        except Exception as e:
            logger.debug(f"Error with generic form search: {e}")
        
        # Strategy 5: Check for third-party comment systems (Disqus, Facebook, etc.)
        third_party_form = self._detect_third_party_comment_system()
        if third_party_form:
            return third_party_form
        
        # Take screenshot for debugging
        try:
            self.take_screenshot('comment_form_not_found.png')
            logger.info("Screenshot saved: comment_form_not_found.png")
        except:
            pass
        
        logger.warning("No comment form found with any detection strategy")
        return None
    
    def _detect_third_party_comment_system(self):
        """Detect and handle third-party comment systems like Disqus, Facebook Comments, etc."""
        logger.info("Checking for third-party comment systems...")
        
        # 1. Disqus Detection
        try:
            # Check for Disqus iframe
            disqus_iframe = self.page.locator('iframe[title*="disqus" i], iframe[id*="disqus" i], iframe[src*="disqus" i], iframe[src*="disquscdn.com"]')
            if disqus_iframe.count() > 0:
                logger.info("Disqus iframe detected, attempting to access Disqus comment form")
                try:
                    # Get the iframe
                    iframe = disqus_iframe.first
                    if iframe.is_visible(timeout=5000):
                        # Switch to iframe context
                        iframe_content = iframe.content_frame()
                        if iframe_content:
                            # Wait for Disqus to load
                            self.random_delay(3, 5)
                            
                            # Look for Disqus comment textarea (multiple selectors)
                            disqus_selectors = [
                                'textarea[placeholder*="Start the discussion" i]',
                                'textarea[data-placeholder*="Start" i]',
                                'textarea#textarea',
                                'textarea.textarea',
                                'textarea[class*="textarea" i]',
                                'textarea',  # Generic fallback
                            ]
                            
                            for selector in disqus_selectors:
                                disqus_textarea = iframe_content.locator(selector)
                                if disqus_textarea.count() > 0:
                                    try:
                                        if disqus_textarea.first.is_visible(timeout=3000):
                                            logger.info(f"Found Disqus comment textarea with selector: {selector}")
                                            return {'type': 'disqus', 'iframe': iframe_content, 'textarea': disqus_textarea.first}
                                    except:
                                        continue
                            
                            # Try to find Disqus form
                            disqus_form_selectors = [
                                'form[data-form-id]',
                                'form.comment-form',
                                'form[class*="comment" i]',
                                'form',
                            ]
                            
                            for selector in disqus_form_selectors:
                                disqus_form = iframe_content.locator(selector)
                                if disqus_form.count() > 0:
                                    try:
                                        if disqus_form.first.is_visible(timeout=3000):
                                            logger.info(f"Found Disqus comment form with selector: {selector}")
                                            return {'type': 'disqus', 'iframe': iframe_content, 'form': disqus_form.first}
                                    except:
                                        continue
                except Exception as e:
                    logger.warning(f"Could not access Disqus iframe: {e}")
        except Exception as e:
            logger.debug(f"Disqus detection error: {e}")
        
        # 2. Facebook Comments Detection
        try:
            fb_comments = self.page.locator('[class*="fb-comments" i], [id*="fb-comments" i], iframe[src*="facebook.com/plugins/comments"]')
            if fb_comments.count() > 0:
                logger.info("Facebook Comments detected - requires Facebook login, skipping")
        except:
            pass
        
        # 3. Commento Detection
        try:
            commento = self.page.locator('[id*="commento" i], [class*="commento" i], iframe[src*="commento"]')
            if commento.count() > 0:
                logger.info("Commento comments detected")
                commento_textarea = self.page.locator('textarea[placeholder*="comment" i], textarea#commento-textarea-root')
                if commento_textarea.count() > 0:
                    logger.info("Found Commento textarea")
                    return commento_textarea.first
        except:
            pass
        
        return None
    
    def _fill_comment_form(self, form, comment_text: str, task: Dict):
        """Fill comment form fields with improved detection"""
        logger.info("Filling comment form...")
        
        # Handle third-party comment systems
        if isinstance(form, dict) and 'type' in form:
            if form['type'] == 'disqus':
                return self._fill_disqus_form(form, comment_text, task)
        
        # Handle case where form might actually be a textarea directly
        try:
            # Try to get the tag name using evaluate
            tag = form.evaluate('el => el.tagName ? el.tagName.toLowerCase() : null')
            if tag == 'textarea':
                # It's a textarea, not a form - fill it directly
                logger.info("Form is actually a textarea, filling directly")
                form.click()  # Focus the textarea
                self.random_delay(0.5, 1)
                form.fill('')  # Clear any existing content
                self.human_type(self.page, form, comment_text)
                self.random_delay(1, 2)
                return
        except Exception as e:
            logger.debug(f"Could not check if element is textarea: {e}")
            # Continue with form handling
        
        # Use field role matcher to find fields
        try:
            from core.field_role_matcher import FieldRoleMatcher
            
            task_id = getattr(self, '_current_task_id', None)
            field_mappings = FieldRoleMatcher.match_fields(
                self.page,
                form_locator=form,
                task_id=task_id
            )
            
            # Fill comment field
            if 'comment' in field_mappings:
                comment_field, confidence = field_mappings['comment']
                logger.info(f"Found comment field via role matcher (confidence: {confidence:.2f})")
                if comment_field.is_visible():
                    self.human_type(self.page, comment_field, comment_text)
            else:
                # Fallback to hardcoded selector
                textarea = form.locator('textarea, input[type="text"]').first
                if textarea.is_visible():
                    self.human_type(self.page, textarea, comment_text)
            
            # Fill name field
            if 'name' in field_mappings:
                name_field, confidence = field_mappings['name']
                logger.info(f"Found name field via role matcher (confidence: {confidence:.2f})")
                if name_field.is_visible():
                    self.human_type(self.page, name_field, "John Doe")
            else:
                # Fallback to hardcoded selector
                name_field = form.locator('input[name*="name"], input[id*="name"]').first
                if name_field.is_visible():
                    self.human_type(self.page, name_field, "John Doe")
            
            # Fill email field
            if 'email' in field_mappings:
                email_field, confidence = field_mappings['email']
                logger.info(f"Found email field via role matcher (confidence: {confidence:.2f})")
                if email_field.is_visible():
                    self.human_type(self.page, email_field, "user@example.com")
            else:
                # Fallback to hardcoded selector
                email_field = form.locator('input[type="email"], input[name*="email"]').first
                if email_field.is_visible():
                    self.human_type(self.page, email_field, "user@example.com")
            
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
                # Fallback to hardcoded selector
                website_field = form.locator('input[name*="url"], input[name*="website"]').first
                if website_field.is_visible():
                    campaign = self.api_client.get_campaign(task['campaign_id'])
                    website_url = campaign.get('web_url', '')
                    if website_url:
                        self.human_type(self.page, website_field, website_url)
        except ImportError:
            # Fallback if field role matcher not available
            logger.debug("FieldRoleMatcher not available, using hardcoded selectors")
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
                campaign = self.api_client.get_campaign(task['campaign_id'])
                website_url = campaign.get('web_url', '')
                if website_url:
                    self.human_type(self.page, website_field, website_url)
        
        self.random_delay(1, 2)
    
    def _generate_comment(self, keywords: list, strategy: str, task: Dict) -> str:
        """Generate comment using LLM with fallback"""
        article_title = ""
        article_excerpt = ""
        try:
            article_title = self.page.title() if self.page else ""
            # Try to get article excerpt/meta description
            try:
                meta_desc = self.page.locator('meta[name="description"]').get_attribute('content')
                if meta_desc:
                    article_excerpt = meta_desc[:200]
                else:
                    first_p = self.page.locator('article p, .post p, .content p').first
                    if first_p.is_visible():
                        article_excerpt = first_p.text_content()[:200]
            except:
                pass
            
            target_url = task.get('payload', {}).get('target_url', '') or (self.page.url if self.page else "")
            tone = task.get('payload', {}).get('content_tone', 'professional')
            
            comment_text = self.api_client.generate_content(
                'comment',
                {
                    'article_title': article_title,
                    'article_excerpt': article_excerpt,
                    'target_url': target_url,
                },
                tone
            )
            
            if comment_text:
                logger.info("Comment generated successfully using LLM")
                return comment_text.strip()
        except Exception as e:
            logger.warning(f"LLM comment generation failed: {e}, using fallback")
        
        # Fallback
        return self._generate_fallback_comment(keywords, article_title, article_excerpt, strategy)
    
    def _generate_fallback_comment(self, keywords: list, article_title: str = "", article_excerpt: str = "", strategy: str = "variation") -> str:
        """Generate a realistic comment without LLM"""
        import random
        
        # Comment templates
        templates = [
            "Great article! I found the section about {keyword} particularly insightful. Thanks for sharing this valuable information.",
            "This is a really helpful post about {keyword}. I especially appreciated the practical examples you provided.",
            "Thanks for writing about {keyword}. This is exactly what I was looking for. Keep up the great work!",
            "Excellent article on {keyword}! I've bookmarked this for future reference. The tips you shared are very useful.",
            "I really enjoyed reading this post about {keyword}. Your perspective on this topic is refreshing.",
            "This is a comprehensive guide on {keyword}. I learned a lot from reading this. Thank you!",
            "Great insights on {keyword}! I'll definitely be implementing some of these suggestions.",
            "Thanks for sharing this valuable information about {keyword}. It's clear you put a lot of thought into this article.",
        ]
        
        # Get primary keyword
        primary_keyword = keywords[0] if keywords else "this topic"
        
        # Select template
        template = random.choice(templates)
        comment = template.format(keyword=primary_keyword)
        
        # Add variation based on strategy
        if strategy == "variation" and len(keywords) > 1:
            # Add a second keyword naturally
            variations = [
                f" The information about {keywords[1]} was also very helpful.",
                f" I also found the section on {keywords[1]} interesting.",
                f" Your explanation of {keywords[1]} was particularly clear.",
            ]
            comment += random.choice(variations)
        
        # Ensure comment is reasonable length (50-200 characters)
        if len(comment) < 50:
            comment += " Looking forward to more content like this!"
        elif len(comment) > 200:
            comment = comment[:197] + "..."
        
        return comment
    
    def _verify_comment_posted(self, comment_text: str) -> bool:
        """Verify comment was posted"""
        try:
            # Wait for comment to appear
            self.page.wait_for_timeout(3000)
            
            # Check if comment text appears on page
            return self.page.locator(f'text={comment_text[:50]}').is_visible(timeout=5000)
        except:
            return False

