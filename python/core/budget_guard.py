"""
Budget Guard

Enforces limits to prevent infinite loops and resource exhaustion
"""

import time
import logging
from enum import Enum
from typing import Dict, Optional
from dataclasses import dataclass, field

logger = logging.getLogger(__name__)


class BudgetExceededReason(Enum):
    """Reasons for budget exceeded"""
    MAX_RUNTIME_EXCEEDED = "max_runtime_exceeded"
    MAX_RETRIES_EXCEEDED = "max_retries_exceeded"
    MAX_POPUP_DISMISS_ATTEMPTS = "max_popup_dismiss_attempts"
    MAX_LOCATOR_CANDIDATES = "max_locator_candidates"


class BudgetExceededException(Exception):
    """Exception raised when budget is exceeded"""
    
    def __init__(self, reason: BudgetExceededReason, details: Optional[Dict] = None):
        self.reason = reason
        self.details = details or {}
        super().__init__(f"Budget exceeded: {reason.value}")


@dataclass
class BudgetConfig:
    """Budget configuration"""
    max_runtime_seconds: int = 300  # 5 minutes default
    max_retries_per_step: int = 3
    max_popup_dismiss_attempts: int = 5
    max_locator_candidates: int = 10


@dataclass
class BudgetState:
    """Current budget state for a task"""
    task_id: int
    config: BudgetConfig
    start_time: float = field(default_factory=time.time)
    step_retries: Dict[str, int] = field(default_factory=dict)
    popup_dismiss_attempts: int = 0
    locator_candidates_tried: int = 0
    
    def get_elapsed_time(self) -> float:
        """Get elapsed time in seconds"""
        return time.time() - self.start_time
    
    def reset_step_retries(self, step_name: str):
        """Reset retry count for a step"""
        self.step_retries[step_name] = 0
    
    def increment_step_retry(self, step_name: str) -> int:
        """Increment retry count for a step"""
        if step_name not in self.step_retries:
            self.step_retries[step_name] = 0
        self.step_retries[step_name] += 1
        return self.step_retries[step_name]
    
    def increment_popup_dismiss(self) -> int:
        """Increment popup dismiss attempts"""
        self.popup_dismiss_attempts += 1
        return self.popup_dismiss_attempts
    
    def increment_locator_candidates(self) -> int:
        """Increment locator candidates tried"""
        self.locator_candidates_tried += 1
        return self.locator_candidates_tried


class BudgetGuard:
    """Enforces budget limits to prevent infinite loops"""
    
    _active_budgets: Dict[int, BudgetState] = {}
    
    @classmethod
    def init_task(cls, task_id: int, config: Optional[BudgetConfig] = None) -> BudgetState:
        """
        Initialize budget for a task
        
        Args:
            task_id: Task ID
            config: Optional budget configuration
        
        Returns:
            BudgetState object
        """
        if config is None:
            config = BudgetConfig()
        
        state = BudgetState(task_id=task_id, config=config)
        cls._active_budgets[task_id] = state
        
        logger.debug(f"Budget initialized for task {task_id}: {config}")
        return state
    
    @classmethod
    def get_state(cls, task_id: int) -> Optional[BudgetState]:
        """Get budget state for a task"""
        return cls._active_budgets.get(task_id)
    
    @classmethod
    def cleanup_task(cls, task_id: int):
        """Clean up budget state for a task"""
        if task_id in cls._active_budgets:
            del cls._active_budgets[task_id]
            logger.debug(f"Budget cleaned up for task {task_id}")
    
    @classmethod
    def check_runtime(cls, task_id: int) -> None:
        """
        Check if runtime budget is exceeded
        
        Raises:
            BudgetExceededException if exceeded
        """
        state = cls.get_state(task_id)
        if not state:
            return
        
        elapsed = state.get_elapsed_time()
        if elapsed > state.config.max_runtime_seconds:
            cls.cleanup_task(task_id)
            raise BudgetExceededException(
                BudgetExceededReason.MAX_RUNTIME_EXCEEDED,
                {
                    'elapsed_seconds': elapsed,
                    'max_seconds': state.config.max_runtime_seconds,
                    'task_id': task_id
                }
            )
    
    @classmethod
    def check_step_retry(cls, task_id: int, step_name: str) -> int:
        """
        Check and increment step retry count
        
        Args:
            task_id: Task ID
            step_name: Step name
        
        Returns:
            Current retry count
        
        Raises:
            BudgetExceededException if exceeded
        """
        state = cls.get_state(task_id)
        if not state:
            return 0
        
        retry_count = state.increment_step_retry(step_name)
        
        if retry_count > state.config.max_retries_per_step:
            cls.cleanup_task(task_id)
            raise BudgetExceededException(
                BudgetExceededReason.MAX_RETRIES_EXCEEDED,
                {
                    'step_name': step_name,
                    'retry_count': retry_count,
                    'max_retries': state.config.max_retries_per_step,
                    'task_id': task_id
                }
            )
        
        return retry_count
    
    @classmethod
    def check_popup_dismiss(cls, task_id: int) -> int:
        """
        Check and increment popup dismiss attempts
        
        Returns:
            Current attempt count
        
        Raises:
            BudgetExceededException if exceeded
        """
        state = cls.get_state(task_id)
        if not state:
            return 0
        
        attempt_count = state.increment_popup_dismiss()
        
        if attempt_count > state.config.max_popup_dismiss_attempts:
            cls.cleanup_task(task_id)
            raise BudgetExceededException(
                BudgetExceededReason.MAX_POPUP_DISMISS_ATTEMPTS,
                {
                    'attempt_count': attempt_count,
                    'max_attempts': state.config.max_popup_dismiss_attempts,
                    'task_id': task_id
                }
            )
        
        return attempt_count
    
    @classmethod
    def check_locator_candidates(cls, task_id: int) -> int:
        """
        Check and increment locator candidates tried
        
        Returns:
            Current candidate count
        
        Raises:
            BudgetExceededException if exceeded
        """
        state = cls.get_state(task_id)
        if not state:
            return 0
        
        candidate_count = state.increment_locator_candidates()
        
        if candidate_count > state.config.max_locator_candidates:
            cls.cleanup_task(task_id)
            raise BudgetExceededException(
                BudgetExceededReason.MAX_LOCATOR_CANDIDATES,
                {
                    'candidate_count': candidate_count,
                    'max_candidates': state.config.max_locator_candidates,
                    'task_id': task_id
                }
            )
        
        return candidate_count
    
    @classmethod
    def reset_step_retries(cls, task_id: int, step_name: str):
        """Reset retry count for a step (e.g., after successful completion)"""
        state = cls.get_state(task_id)
        if state:
            state.reset_step_retries(step_name)

