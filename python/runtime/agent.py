"""
Runtime Agent

Goal-oriented agent that decides next steps like a human
"""

import logging
from enum import Enum
from typing import Dict, List, Optional, Any, Tuple
from dataclasses import dataclass, field
from playwright.sync_api import Page, Locator

from core.state_detector import StateDetector, PageState
from core.popup_controller import PopupController
from core.locator_engine import LocatorEngine
from core.field_role_matcher import FieldRoleMatcher
from core.domain_memory import get_domain_memory
from core.budget_guard import BudgetGuard, BudgetExceededException
from core.telemetry import log_step
from core.failure_enums import FailureReason

logger = logging.getLogger(__name__)


class Subgoal(Enum):
    """Agent subgoals"""
    OPEN_COMMENT_EDITOR = "open_comment_editor"
    SUBMIT_COMMENT = "submit_comment"
    GO_TO_LOGIN = "go_to_login"
    REGISTER_ACCOUNT = "register_account"
    VERIFY_EMAIL = "verify_email"
    RETURN_AND_SUBMIT = "return_and_submit"
    ABORT_SKIP_DOMAIN = "abort_skip_domain"


@dataclass
class AgentState:
    """Current state of the agent"""
    task_id: int
    page: Page
    domain: str
    current_url: str
    goal: str  # Main goal: comment, profile, forum, guest
    subgoal: Optional[Subgoal] = None
    history: List[Dict] = field(default_factory=list)
    context: Dict = field(default_factory=dict)
    success: bool = False
    failure_reason: Optional[str] = None
    
    def add_history(self, action: str, result: Dict):
        """Add entry to history"""
        self.history.append({
            'action': action,
            'result': result,
            'timestamp': self._get_timestamp()
        })
    
    def _get_timestamp(self) -> str:
        """Get current timestamp"""
        from datetime import datetime
        return datetime.utcnow().isoformat() + 'Z'
    
    def has_tried(self, action: str) -> bool:
        """Check if action has been tried"""
        return any(h['action'] == action for h in self.history)
    
    def get_try_count(self, action: str) -> int:
        """Get number of times action has been tried"""
        return sum(1 for h in self.history if h['action'] == action)


class RuntimeAgent:
    """Goal-oriented runtime agent"""
    
    def __init__(self, task_id: int, page: Page, domain: str, goal: str):
        """
        Initialize agent
        
        Args:
            task_id: Task ID
            page: Playwright Page object
            domain: Domain name
            goal: Main goal (comment, profile, forum, guest)
        """
        self.state = AgentState(
            task_id=task_id,
            page=page,
            domain=domain,
            current_url=page.url,
            goal=goal
        )
        self.domain_memory = get_domain_memory()
        self.budget_guard = BudgetGuard
    
    def execute(self) -> Dict:
        """
        Execute agent flow
        
        Returns:
            Dict with success, result, and metadata
        """
        log_step(self.state.task_id, 'agent_execute_start', {
            'goal': self.state.goal,
            'domain': self.state.domain
        })
        
        try:
            # Check if domain should be skipped
            should_skip, skip_reason = self.domain_memory.should_skip(self.state.domain)
            if should_skip:
                log_step(self.state.task_id, 'agent_domain_skipped', {'reason': skip_reason})
                return {
                    'success': False,
                    'failure_reason': FailureReason.BLOCKED.value,
                    'error': f"Domain skipped: {skip_reason}",
                    'subgoal': Subgoal.ABORT_SKIP_DOMAIN.value
                }
            
            # Main flow based on goal
            if self.state.goal == 'comment':
                return self._execute_comment_flow()
            elif self.state.goal == 'profile':
                return self._execute_profile_flow()
            elif self.state.goal == 'forum':
                return self._execute_forum_flow()
            elif self.state.goal == 'guest':
                return self._execute_guest_flow()
            else:
                raise ValueError(f"Unknown goal: {self.state.goal}")
        
        except BudgetExceededException as e:
            log_step(self.state.task_id, 'agent_budget_exceeded', {
                'reason': e.reason.value,
                'details': e.details
            })
            return {
                'success': False,
                'failure_reason': FailureReason.TIMEOUT.value,
                'error': f"Budget exceeded: {e.reason.value}",
                'subgoal': self.state.subgoal.value if self.state.subgoal else None
            }
        except Exception as e:
            logger.error(f"Agent execution failed: {e}", exc_info=True)
            log_step(self.state.task_id, 'agent_execution_error', {'error': str(e)})
            return {
                'success': False,
                'failure_reason': FailureReason.UNKNOWN.value,
                'error': str(e),
                'subgoal': self.state.subgoal.value if self.state.subgoal else None
            }
    
    def _execute_comment_flow(self) -> Dict:
        """Execute comment flow"""
        log_step(self.state.task_id, 'agent_comment_flow_start')
        
        # Step 1: Clear popups
        if not self._clear_popups():
            return self._fail(FailureReason.POPUP_BLOCKING.value, "Failed to clear popups")
        
        # Step 2: Open comment editor
        self.state.subgoal = Subgoal.OPEN_COMMENT_EDITOR
        comment_form = self._open_comment_editor()
        if not comment_form:
            return self._fail(FailureReason.COMMENT_FORM_NOT_FOUND.value, "Comment form not found")
        
        # Step 3: Fill comment form (delegated to automation module)
        # Agent just ensures form is ready
        
        # Step 4: Submit comment
        self.state.subgoal = Subgoal.SUBMIT_COMMENT
        submit_result = self._submit_comment(comment_form)
        if not submit_result:
            return self._fail(FailureReason.UNKNOWN.value, "Failed to submit comment")
        
        return {
            'success': True,
            'subgoal': Subgoal.SUBMIT_COMMENT.value,
            'result': submit_result
        }
    
    def _execute_profile_flow(self) -> Dict:
        """Execute profile flow"""
        log_step(self.state.task_id, 'agent_profile_flow_start')
        
        # Step 1: Clear popups
        if not self._clear_popups():
            return self._fail(FailureReason.POPUP_BLOCKING.value, "Failed to clear popups")
        
        # Step 2: Check if login required
        page_state = StateDetector.analyze(self.state.page)
        if page_state.login_required:
            self.state.subgoal = Subgoal.GO_TO_LOGIN
            login_result = self._go_to_login()
            if not login_result:
                return self._fail(FailureReason.UNKNOWN.value, "Failed to navigate to login")
        
        # Step 3: Register account (delegated to automation module)
        self.state.subgoal = Subgoal.REGISTER_ACCOUNT
        # Agent ensures we're on registration page
        
        # Step 4: Check for email verification requirement
        page_state = StateDetector.analyze(self.state.page)
        if page_state.email_verification_hints:
            self.state.subgoal = Subgoal.VERIFY_EMAIL
            # Mark as pending, don't automate
            log_step(self.state.task_id, 'agent_email_verification_required')
            return {
                'success': False,
                'failure_reason': FailureReason.EMAIL_VERIFICATION_FAILED.value,
                'error': 'Email verification required',
                'subgoal': Subgoal.VERIFY_EMAIL.value,
                'pending_verification': True
            }
        
        return {
            'success': True,
            'subgoal': Subgoal.REGISTER_ACCOUNT.value
        }
    
    def _execute_forum_flow(self) -> Dict:
        """Execute forum flow (similar to comment)"""
        log_step(self.state.task_id, 'agent_forum_flow_start')
        
        # Similar to comment flow
        if not self._clear_popups():
            return self._fail(FailureReason.POPUP_BLOCKING.value, "Failed to clear popups")
        
        # Find forum form
        forum_form = self._find_forum_form()
        if not forum_form:
            return self._fail(FailureReason.ELEMENT_NOT_FOUND.value, "Forum form not found")
        
        return {
            'success': True,
            'subgoal': Subgoal.OPEN_COMMENT_EDITOR.value
        }
    
    def _execute_guest_flow(self) -> Dict:
        """Execute guest post flow"""
        log_step(self.state.task_id, 'agent_guest_flow_start')
        
        # Similar to comment flow
        if not self._clear_popups():
            return self._fail(FailureReason.POPUP_BLOCKING.value, "Failed to clear popups")
        
        # Find guest post form
        guest_form = self._find_guest_form()
        if not guest_form:
            return self._fail(FailureReason.ELEMENT_NOT_FOUND.value, "Guest post form not found")
        
        return {
            'success': True,
            'subgoal': Subgoal.OPEN_COMMENT_EDITOR.value
        }
    
    def _clear_popups(self) -> bool:
        """Clear popups using PopupController"""
        if self.state.has_tried('clear_popups') and self.state.get_try_count('clear_popups') >= 2:
            return False  # Already tried multiple times
        
        try:
            BudgetGuard.check_runtime(self.state.task_id)
            BudgetGuard.check_popup_dismiss(self.state.task_id)
        except BudgetExceededException:
            return False
        
        self.state.add_history('clear_popups', {'attempt': self.state.get_try_count('clear_popups') + 1})
        
        page_state = StateDetector.analyze(self.state.page)
        result = PopupController.clear_if_needed(
            self.state.page,
            self.state.task_id,
            state=page_state
        )
        
        if result.get('cleared'):
            log_step(self.state.task_id, 'agent_popup_cleared')
            return True
        
        # If popups weren't cleared but no errors, continue anyway
        # (popups might not exist or might be non-blocking)
        if not result.get('errors'):
            log_step(self.state.task_id, 'agent_popup_no_action_needed')
            return True  # Continue even if no popups were cleared
        
        # Only fail if there were actual errors
        logger.warning(f"Popup clearing had errors: {result.get('errors', [])}")
        return False
    
    def _open_comment_editor(self) -> Optional[Locator]:
        """Open comment editor using LocatorEngine"""
        logger.info(f"Opening comment editor for task {self.state.task_id}")
        logger.info(f"Current URL: {self.state.page.url}")
        logger.info(f"Page title: {self.state.page.title()}")
        if self.state.has_tried('open_comment_editor') and self.state.get_try_count('open_comment_editor') >= 3:
            return None
        
        try:
            BudgetGuard.check_runtime(self.state.task_id)
            BudgetGuard.check_locator_candidates(self.state.task_id)
        except BudgetExceededException:
            return None
        
        self.state.add_history('open_comment_editor', {'attempt': self.state.get_try_count('open_comment_editor') + 1})
        
        # Use LocatorEngine to find comment form
        logger.info("Searching for comment form using LocatorEngine...")
        locator, candidate, candidates = LocatorEngine.find(
            self.state.page,
            target_role='form',
            keywords=['comment', 'reply', 'message'],
            context={'domain': self.state.domain},
            task_id=self.state.task_id
        )
        
        if locator:
            logger.info(f"Found comment form! Strategy: {candidate.strategy if candidate else 'unknown'}, Confidence: {candidate.confidence if candidate else 0.0}")
            log_step(self.state.task_id, 'agent_comment_editor_found', {
                'strategy': candidate.strategy if candidate else 'unknown',
                'confidence': candidate.confidence if candidate else 0.0
            })
            return locator
        
        logger.info("Comment form not found, trying textarea fallback...")
        # Fallback: try textarea directly
        textarea_locator, candidate, candidates = LocatorEngine.find(
            self.state.page,
            target_role='input',
            keywords=['comment', 'message'],
            context={'domain': self.state.domain},
            task_id=self.state.task_id
        )
        
        if textarea_locator:
            logger.info("Found textarea for comment!")
            log_step(self.state.task_id, 'agent_comment_textarea_found')
            return textarea_locator
        
        logger.warning(f"Comment form not found on page: {self.state.page.url}")
        logger.warning(f"Tried {len(candidates) if candidates else 0} candidates")
        log_step(self.state.task_id, 'agent_comment_editor_not_found', {
            'url': self.state.page.url,
            'candidates_tried': len(candidates) if candidates else 0
        })
        return None
    
    def _submit_comment(self, form: Locator) -> bool:
        """Submit comment using LocatorEngine"""
        if self.state.has_tried('submit_comment') and self.state.get_try_count('submit_comment') >= 3:
            return False
        
        try:
            BudgetGuard.check_runtime(self.state.task_id)
            BudgetGuard.check_locator_candidates(self.state.task_id)
        except BudgetExceededException:
            return False
        
        self.state.add_history('submit_comment', {'attempt': self.state.get_try_count('submit_comment') + 1})
        
        # Find submit button
        submit_locator, candidate, candidates = LocatorEngine.find(
            self.state.page,
            target_role='button',
            keywords=['submit', 'post', 'send', 'comment'],
            context={'domain': self.state.domain, 'form_locator': form},
            task_id=self.state.task_id
        )
        
        if submit_locator:
            try:
                submit_locator.click(timeout=5000)
                log_step(self.state.task_id, 'agent_comment_submitted', {
                    'strategy': candidate.strategy if candidate else 'unknown'
                })
                return True
            except Exception as e:
                logger.warning(f"Failed to click submit button: {e}")
                return False
        
        return False
    
    def _go_to_login(self) -> bool:
        """Navigate to login page"""
        if self.state.has_tried('go_to_login') and self.state.get_try_count('go_to_login') >= 2:
            return False
        
        self.state.add_history('go_to_login', {'attempt': self.state.get_try_count('go_to_login') + 1})
        
        # Try to find login link/button
        login_locator, candidate, candidates = LocatorEngine.find(
            self.state.page,
            target_role='link',
            keywords=['login', 'sign in', 'log in'],
            context={'domain': self.state.domain},
            task_id=self.state.task_id
        )
        
        if login_locator:
            try:
                login_locator.click(timeout=5000)
                self.state.page.wait_for_load_state('networkidle', timeout=10000)
                log_step(self.state.task_id, 'agent_navigated_to_login')
                return True
            except Exception as e:
                logger.warning(f"Failed to navigate to login: {e}")
                return False
        
        return False
    
    def _find_forum_form(self) -> Optional[Locator]:
        """Find forum form"""
        locator, candidate, candidates = LocatorEngine.find(
            self.state.page,
            target_role='form',
            keywords=['forum', 'thread', 'post', 'reply'],
            context={'domain': self.state.domain},
            task_id=self.state.task_id
        )
        return locator
    
    def _find_guest_form(self) -> Optional[Locator]:
        """Find guest post form"""
        locator, candidate, candidates = LocatorEngine.find(
            self.state.page,
            target_role='form',
            keywords=['guest', 'post', 'submit', 'write'],
            context={'domain': self.state.domain},
            task_id=self.state.task_id
        )
        return locator
    
    def _fail(self, failure_reason: str, error: str) -> Dict:
        """Mark agent as failed"""
        self.state.success = False
        self.state.failure_reason = failure_reason
        log_step(self.state.task_id, 'agent_failed', {
            'failure_reason': failure_reason,
            'error': error,
            'subgoal': self.state.subgoal.value if self.state.subgoal else None
        })
        return {
            'success': False,
            'failure_reason': failure_reason,
            'error': error,
            'subgoal': self.state.subgoal.value if self.state.subgoal else None
        }

