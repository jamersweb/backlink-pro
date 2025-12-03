"""
Automation modules for different backlink types
"""
from .base import BaseAutomation
from .comment import CommentAutomation
from .profile import ProfileAutomation
from .forum import ForumAutomation
from .guest import GuestPostAutomation

__all__ = ['BaseAutomation', 'CommentAutomation', 'ProfileAutomation', 'ForumAutomation', 'GuestPostAutomation']

