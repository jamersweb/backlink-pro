"""
Page State Detector

Detects page state: overlays, modals, login requirements, registration hints, etc.
"""

import re
import logging
from typing import Dict, Optional, List
from playwright.sync_api import Page

logger = logging.getLogger(__name__)


class PageState:
    """Page state information"""
    
    def __init__(self):
        # Overlay/Modal detection
        self.overlay_present = False
        self.modal_present = False
        self.cookie_banner_present = False
        self.newsletter_modal_present = False
        self.login_modal_present = False
        
        # Authentication/Registration
        self.login_required = False
        self.registration_hints = False
        self.email_verification_hints = False
        
        # Technical
        self.iframe_count = 0
        self.captcha_present = False
        
        # Blocking/Bot detection
        self.blocked_hints = False
        self.bot_check_hints = False
        
        # Intent guess
        self.intent_guess = "unknown"  # login, comment, profile, forum, guest, unknown
    
    def to_dict(self) -> Dict:
        """Convert to dictionary"""
        return {
            'overlay_present': self.overlay_present,
            'modal_present': self.modal_present,
            'cookie_banner_present': self.cookie_banner_present,
            'newsletter_modal_present': self.newsletter_modal_present,
            'login_modal_present': self.login_modal_present,
            'login_required': self.login_required,
            'registration_hints': self.registration_hints,
            'email_verification_hints': self.email_verification_hints,
            'iframe_count': self.iframe_count,
            'captcha_present': self.captcha_present,
            'blocked_hints': self.blocked_hints,
            'bot_check_hints': self.bot_check_hints,
            'intent_guess': self.intent_guess,
        }


class StateDetector:
    """Detects page state and intent"""
    
    # Patterns for detection
    COOKIE_BANNER_SELECTORS = [
        '[id*="cookie"]',
        '[class*="cookie"]',
        '[id*="consent"]',
        '[class*="consent"]',
        '[id*="gdpr"]',
        '[class*="gdpr"]',
        '[id*="privacy"]',
        '[class*="privacy"]',
        '[data-testid*="cookie"]',
        '[aria-label*="cookie" i]',
    ]
    
    MODAL_SELECTORS = [
        '[role="dialog"]',
        '[class*="modal"]',
        '[id*="modal"]',
        '[class*="popup"]',
        '[id*="popup"]',
        '[class*="overlay"]',
        '[id*="overlay"]',
        '[class*="lightbox"]',
        '[id*="lightbox"]',
    ]
    
    LOGIN_SELECTORS = [
        'input[type="email"]',
        'input[type="password"]',
        'input[name*="email" i]',
        'input[name*="password" i]',
        'input[name*="login" i]',
        'input[name*="username" i]',
        'form[action*="login" i]',
        'form[action*="signin" i]',
        '[id*="login"]',
        '[class*="login"]',
        '[id*="signin"]',
        '[class*="signin"]',
    ]
    
    REGISTRATION_SELECTORS = [
        'input[name*="register" i]',
        'input[name*="signup" i]',
        'form[action*="register" i]',
        'form[action*="signup" i]',
        '[id*="register"]',
        '[class*="register"]',
        '[id*="signup"]',
        '[class*="signup"]',
        'a[href*="register" i]',
        'a[href*="signup" i]',
    ]
    
    EMAIL_VERIFICATION_SELECTORS = [
        '[class*="verify" i]',
        '[id*="verify" i]',
        '[class*="verification" i]',
        '[id*="verification" i]',
        '[class*="confirm" i]',
        '[id*="confirm" i]',
        'text=/verify.*email/i',
        'text=/confirm.*email/i',
    ]
    
    CAPTCHA_SELECTORS = [
        '[class*="recaptcha"]',
        '[id*="recaptcha"]',
        '[class*="g-recaptcha"]',
        '[id*="g-recaptcha"]',
        '[class*="hcaptcha"]',
        '[id*="hcaptcha"]',
        'iframe[src*="recaptcha"]',
        'iframe[src*="hcaptcha"]',
        '[data-sitekey]',
    ]
    
    BLOCKED_INDICATORS = [
        'text=/403/i',
        'text=/forbidden/i',
        'text=/access denied/i',
        'text=/blocked/i',
        'text=/cloudflare/i',
        'text=/cloud.*flare/i',
        '[class*="cf-"]',
        '[id*="cf-"]',
    ]
    
    BOT_CHECK_INDICATORS = [
        'text=/checking your browser/i',
        'text=/please wait/i',
        'text=/verifying you are human/i',
        'text=/bot.*check/i',
        '[class*="challenge"]',
        '[id*="challenge"]',
    ]
    
    COMMENT_INDICATORS = [
        'textarea[name*="comment" i]',
        'textarea[id*="comment" i]',
        'textarea[class*="comment" i]',
        'form[action*="comment" i]',
        '[id*="comment-form"]',
        '[class*="comment-form"]',
        'button[type="submit"][value*="comment" i]',
    ]
    
    PROFILE_INDICATORS = [
        '[class*="profile"]',
        '[id*="profile"]',
        '[class*="user-profile"]',
        '[id*="user-profile"]',
        'a[href*="profile" i]',
        'a[href*="user" i]',
    ]
    
    FORUM_INDICATORS = [
        '[class*="forum"]',
        '[id*="forum"]',
        '[class*="discussion"]',
        '[id*="discussion"]',
        'a[href*="forum" i]',
        'a[href*="discussion" i]',
    ]
    
    GUEST_POST_INDICATORS = [
        'a[href*="guest" i]',
        'a[href*="submit" i]',
        '[class*="submission"]',
        '[id*="submission"]',
        'form[action*="submit" i]',
    ]
    
    @classmethod
    def analyze(cls, page: Page) -> PageState:
        """
        Analyze page state
        
        Args:
            page: Playwright Page object
        
        Returns:
            PageState object
        """
        state = PageState()
        
        try:
            # Get page content
            content = page.content()
            content_lower = content.lower()
            
            # Detect overlays/modals
            state.overlay_present = cls._detect_overlay(page)
            state.modal_present = cls._detect_modal(page)
            state.cookie_banner_present = cls._detect_cookie_banner(page)
            state.newsletter_modal_present = cls._detect_newsletter_modal(page, content_lower)
            state.login_modal_present = cls._detect_login_modal(page)
            
            # Detect authentication requirements
            state.login_required = cls._detect_login_required(page, content_lower)
            state.registration_hints = cls._detect_registration_hints(page, content_lower)
            state.email_verification_hints = cls._detect_email_verification(page, content_lower)
            
            # Technical detection
            state.iframe_count = len(page.query_selector_all('iframe'))
            state.captcha_present = cls._detect_captcha(page, content_lower)
            
            # Blocking detection
            state.blocked_hints = cls._detect_blocked(page, content_lower)
            state.bot_check_hints = cls._detect_bot_check(page, content_lower)
            
            # Intent guess
            state.intent_guess = cls._guess_intent(page, content_lower)
            
        except Exception as e:
            logger.warning(f"Error analyzing page state: {e}")
        
        return state
    
    @classmethod
    def _detect_overlay(cls, page: Page) -> bool:
        """Detect if overlay is present"""
        try:
            # Check for common overlay patterns
            overlay_selectors = [
                '[class*="overlay"]',
                '[id*="overlay"]',
                '[class*="backdrop"]',
                '[id*="backdrop"]',
                '[class*="mask"]',
                '[id*="mask"]',
            ]
            
            for selector in overlay_selectors:
                try:
                    elements = page.query_selector_all(selector)
                    for element in elements:
                        # Check if visible
                        try:
                            if element.is_visible():
                                return True
                        except:
                            continue
                except:
                    continue
        except:
            pass
        return False
    
    @classmethod
    def _detect_modal(cls, page: Page) -> bool:
        """Detect if modal is present"""
        try:
            for selector in cls.MODAL_SELECTORS:
                elements = page.query_selector_all(selector)
                for element in elements:
                    if element.is_visible():
                        return True
        except:
            pass
        return False
    
    @classmethod
    def _detect_cookie_banner(cls, page: Page) -> bool:
        """Detect cookie banner"""
        try:
            for selector in cls.COOKIE_BANNER_SELECTORS:
                elements = page.query_selector_all(selector)
                for element in elements:
                    if element.is_visible():
                        # Check if it contains cookie-related text
                        text = element.inner_text().lower()
                        if any(word in text for word in ['cookie', 'consent', 'privacy', 'gdpr']):
                            return True
        except:
            pass
        return False
    
    @classmethod
    def _detect_newsletter_modal(cls, page: Page, content_lower: str) -> bool:
        """Detect newsletter modal"""
        try:
            # Check for newsletter-related text
            newsletter_keywords = ['newsletter', 'subscribe', 'email updates', 'sign up']
            if any(keyword in content_lower for keyword in newsletter_keywords):
                # Check if modal is visible
                for selector in cls.MODAL_SELECTORS:
                    elements = page.query_selector_all(selector)
                    for element in elements:
                        if element.is_visible():
                            text = element.inner_text().lower()
                            if any(keyword in text for keyword in newsletter_keywords):
                                return True
        except:
            pass
        return False
    
    @classmethod
    def _detect_login_modal(cls, page: Page) -> bool:
        """Detect login modal"""
        try:
            # Check if modal contains login form
            for selector in cls.MODAL_SELECTORS:
                elements = page.query_selector_all(selector)
                for element in elements:
                    if element.is_visible():
                        # Check for login inputs inside modal
                        login_inputs = element.query_selector_all('input[type="email"], input[type="password"]')
                        if len(login_inputs) > 0:
                            return True
        except:
            pass
        return False
    
    @classmethod
    def _detect_login_required(cls, page: Page, content_lower: str) -> bool:
        """Detect login required/auth wall"""
        try:
            # Check for login form
            login_elements = page.query_selector_all(', '.join(cls.LOGIN_SELECTORS))
            if len(login_elements) > 0:
                return True
            
            # Check for auth wall text
            auth_wall_keywords = [
                'please log in',
                'login required',
                'sign in to continue',
                'you must be logged in',
                'authentication required',
            ]
            if any(keyword in content_lower for keyword in auth_wall_keywords):
                return True
        except:
            pass
        return False
    
    @classmethod
    def _detect_registration_hints(cls, page: Page, content_lower: str) -> bool:
        """Detect registration hints"""
        try:
            # Check for registration form
            reg_elements = page.query_selector_all(', '.join(cls.REGISTRATION_SELECTORS))
            if len(reg_elements) > 0:
                return True
            
            # Check for registration text
            reg_keywords = ['register', 'sign up', 'create account', 'join us']
            if any(keyword in content_lower for keyword in reg_keywords):
                return True
        except:
            pass
        return False
    
    @classmethod
    def _detect_email_verification(cls, page: Page, content_lower: str) -> bool:
        """Detect email verification hints"""
        try:
            # Check for verification elements
            verif_elements = page.query_selector_all(', '.join(cls.EMAIL_VERIFICATION_SELECTORS))
            if len(verif_elements) > 0:
                return True
            
            # Check for verification text
            verif_keywords = [
                'verify your email',
                'confirm your email',
                'check your inbox',
                'email verification',
            ]
            if any(keyword in content_lower for keyword in verif_keywords):
                return True
        except:
            pass
        return False
    
    @classmethod
    def _detect_captcha(cls, page: Page, content_lower: str) -> bool:
        """Detect captcha presence (detect only, don't solve)"""
        try:
            # Check for captcha elements
            captcha_elements = page.query_selector_all(', '.join(cls.CAPTCHA_SELECTORS))
            if len(captcha_elements) > 0:
                return True
            
            # Check for captcha in content
            if 'recaptcha' in content_lower or 'hcaptcha' in content_lower:
                return True
        except:
            pass
        return False
    
    @classmethod
    def _detect_blocked(cls, page: Page, content_lower: str) -> bool:
        """Detect blocked/bot-check hints"""
        try:
            # Check for blocked indicators
            for indicator in cls.BLOCKED_INDICATORS:
                try:
                    if indicator.startswith('text='):
                        # Text pattern
                        pattern = indicator[5:].strip('/')
                        if re.search(pattern, content_lower, re.IGNORECASE):
                            return True
                    else:
                        # Selector
                        elements = page.query_selector_all(indicator)
                        if len(elements) > 0:
                            return True
                except:
                    pass
        except:
            pass
        return False
    
    @classmethod
    def _detect_bot_check(cls, page: Page, content_lower: str) -> bool:
        """Detect bot check hints"""
        try:
            # Check for bot check indicators
            for indicator in cls.BOT_CHECK_INDICATORS:
                try:
                    if indicator.startswith('text='):
                        # Text pattern
                        pattern = indicator[5:].strip('/')
                        if re.search(pattern, content_lower, re.IGNORECASE):
                            return True
                    else:
                        # Selector
                        elements = page.query_selector_all(indicator)
                        if len(elements) > 0:
                            return True
                except:
                    pass
        except:
            pass
        return False
    
    @classmethod
    def _guess_intent(cls, page: Page, content_lower: str) -> str:
        """Guess page intent"""
        try:
            # Check for comment indicators
            comment_elements = page.query_selector_all(', '.join(cls.COMMENT_INDICATORS))
            if len(comment_elements) > 0:
                return "comment"
            
            # Check for profile indicators
            profile_elements = page.query_selector_all(', '.join(cls.PROFILE_INDICATORS))
            if len(profile_elements) > 0:
                return "profile"
            
            # Check for forum indicators
            forum_elements = page.query_selector_all(', '.join(cls.FORUM_INDICATORS))
            if len(forum_elements) > 0:
                return "forum"
            
            # Check for guest post indicators
            guest_elements = page.query_selector_all(', '.join(cls.GUEST_POST_INDICATORS))
            if len(guest_elements) > 0:
                return "guest"
            
            # Check for login
            if cls._detect_login_required(page, content_lower):
                return "login"
            
        except:
            pass
        
        return "unknown"

