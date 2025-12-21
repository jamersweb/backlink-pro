"""
Failure Reason Enums

Enterprise-grade failure classification for observability
"""

from enum import Enum


class FailureReason(Enum):
    """Enumeration of all possible failure reasons"""
    
    CAPTCHA_FAILED = "captcha_failed"
    CAPTCHA_PRESENT = "captcha_present"
    COMMENT_FORM_NOT_FOUND = "comment_form_not_found"
    REGISTRATION_FAILED = "registration_failed"
    EMAIL_VERIFICATION_FAILED = "email_verification_failed"
    BLOCKED = "blocked"
    TIMEOUT = "timeout"
    POPUP_BLOCKING = "popup_blocking"
    IFRAME_MISSED = "iframe_missed"
    ELEMENT_NOT_FOUND = "element_not_found"
    UNKNOWN = "unknown"
    
    @classmethod
    def values(cls):
        """Get all enum values as strings"""
        return [e.value for e in cls]
    
    @classmethod
    def from_string(cls, value: str):
        """Get enum from string value"""
        value_lower = value.lower() if value else ""
        for enum_member in cls:
            if enum_member.value == value_lower:
                return enum_member
        return cls.UNKNOWN

