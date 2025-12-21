"""
Failure Mapper

Maps Playwright exceptions and error messages to standardized failure enums
"""

import re
from typing import Optional, Union
from playwright.sync_api import TimeoutError as PlaywrightTimeoutError, Error as PlaywrightError
from core.failure_enums import FailureReason


class FailureMapper:
    """Maps exceptions and messages to failure reason enums"""
    
    # Patterns for different failure types
    PATTERNS = {
        FailureReason.CAPTCHA_FAILED: [
            r'captcha.*fail',
            r'captcha.*error',
            r'failed.*captcha',
            r'captcha.*unsolved',
            r'recaptcha.*fail',
            r'hcaptcha.*fail',
        ],
        FailureReason.CAPTCHA_PRESENT: [
            r'captcha.*present',
            r'captcha.*detected',
            r'captcha.*found',
            r'recaptcha',
            r'hcaptcha',
            r'captcha.*challenge',
        ],
        FailureReason.COMMENT_FORM_NOT_FOUND: [
            r'comment.*form.*not.*found',
            r'comment.*form.*missing',
            r'no.*comment.*form',
            r'comment.*field.*not.*found',
            r'comment.*textarea.*not.*found',
        ],
        FailureReason.REGISTRATION_FAILED: [
            r'registration.*fail',
            r'register.*fail',
            r'signup.*fail',
            r'account.*creation.*fail',
            r'registration.*error',
        ],
        FailureReason.EMAIL_VERIFICATION_FAILED: [
            r'email.*verification.*fail',
            r'email.*verify.*fail',
            r'verification.*email.*fail',
            r'confirm.*email.*fail',
            r'email.*confirmation.*fail',
        ],
        FailureReason.BLOCKED: [
            r'blocked',
            r'forbidden',
            r'403',
            r'access.*denied',
            r'ip.*ban',
            r'rate.*limit',
            r'too.*many.*requests',
            r'429',
            r'bot.*detected',
            r'cloudflare',
            r'cloud.*flare',
        ],
        FailureReason.TIMEOUT: [
            r'timeout',
            r'timed.*out',
            r'exceeded.*time',
            r'waiting.*too.*long',
            r'operation.*timeout',
        ],
        FailureReason.POPUP_BLOCKING: [
            r'popup.*block',
            r'pop.*up.*block',
            r'window.*block',
            r'new.*window.*block',
        ],
        FailureReason.IFRAME_MISSED: [
            r'iframe.*not.*found',
            r'iframe.*missing',
            r'frame.*not.*found',
            r'no.*iframe',
            r'iframe.*error',
        ],
        FailureReason.ELEMENT_NOT_FOUND: [
            r'element.*not.*found',
            r'selector.*not.*found',
            r'locator.*not.*found',
            r'element.*missing',
            r'no.*element',
            r'element.*timeout',
        ],
    }
    
    @classmethod
    def map_exception(cls, exception: Exception) -> FailureReason:
        """
        Map an exception to a failure reason
        
        Args:
            exception: Exception object
        
        Returns:
            FailureReason enum
        """
        # Check exception type
        if isinstance(exception, PlaywrightTimeoutError):
            return FailureReason.TIMEOUT
        
        if isinstance(exception, PlaywrightError):
            # Try to extract more specific reason from message
            return cls.map_message(str(exception))
        
        # Check exception class name
        exception_type = type(exception).__name__.lower()
        
        if 'timeout' in exception_type:
            return FailureReason.TIMEOUT
        if 'blocked' in exception_type or 'forbidden' in exception_type:
            return FailureReason.BLOCKED
        
        # Fall back to message mapping
        return cls.map_message(str(exception))
    
    @classmethod
    def map_message(cls, message: str) -> FailureReason:
        """
        Map an error message to a failure reason
        
        Args:
            message: Error message string
        
        Returns:
            FailureReason enum
        """
        if not message:
            return FailureReason.UNKNOWN
        
        message_lower = message.lower()
        
        # Check patterns in order of specificity (most specific first)
        # Check for captcha first (before other failures)
        for reason, patterns in cls.PATTERNS.items():
            for pattern in patterns:
                if re.search(pattern, message_lower, re.IGNORECASE):
                    return reason
        
        # Check for common Playwright errors
        if 'timeout' in message_lower:
            return FailureReason.TIMEOUT
        if 'target closed' in message_lower or 'browser closed' in message_lower:
            return FailureReason.BLOCKED
        if 'context closed' in message_lower:
            return FailureReason.BLOCKED
        if 'element' in message_lower and ('not found' in message_lower or 'missing' in message_lower):
            return FailureReason.ELEMENT_NOT_FOUND
        
        return FailureReason.UNKNOWN
    
    @classmethod
    def map(cls, exception_or_message: Union[Exception, str, None]) -> FailureReason:
        """
        Universal mapper - accepts exception or message string
        
        Args:
            exception_or_message: Exception object or error message string
        
        Returns:
            FailureReason enum
        """
        if exception_or_message is None:
            return FailureReason.UNKNOWN
        
        if isinstance(exception_or_message, Exception):
            return cls.map_exception(exception_or_message)
        
        if isinstance(exception_or_message, str):
            return cls.map_message(exception_or_message)
        
        return FailureReason.UNKNOWN

