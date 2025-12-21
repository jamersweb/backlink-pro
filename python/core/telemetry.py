"""
Enterprise-Grade Telemetry System

Generates run artifacts for every task execution, even on failure
"""

import json
import os
import logging
from pathlib import Path
from datetime import datetime
from typing import Dict, Optional, Any
from playwright.sync_api import Page

logger = logging.getLogger(__name__)


class Telemetry:
    """Telemetry system for task run observability"""
    
    def __init__(self, runs_dir: str = "runs"):
        """
        Initialize telemetry
        
        Args:
            runs_dir: Directory for run artifacts
        """
        self.runs_dir = Path(runs_dir)
        self.runs_dir.mkdir(parents=True, exist_ok=True)
        self.active_runs: Dict[int, Dict] = {}
    
    def init_run(self, task_id: int, meta: Optional[Dict] = None) -> Path:
        """
        Initialize a new run
        
        Args:
            task_id: Task ID
            meta: Optional metadata (task_type, campaign_id, etc.)
        
        Returns:
            Path to run directory
        """
        run_dir = self.runs_dir / str(task_id)
        run_dir.mkdir(parents=True, exist_ok=True)
        
        run_meta = {
            'task_id': task_id,
            'started_at': datetime.utcnow().isoformat() + 'Z',
            'meta': meta or {},
        }
        
        self.active_runs[task_id] = {
            'run_dir': run_dir,
            'started_at': datetime.utcnow(),
            'meta': run_meta,
        }
        
        # Write initial metadata
        init_file = run_dir / 'init.json'
        with open(init_file, 'w', encoding='utf-8') as f:
            json.dump(run_meta, f, indent=2)
        
        logger.debug(f"Initialized run telemetry for task {task_id}: {run_dir}")
        
        return run_dir
    
    def log_step(self, task_id: int, step_name: str, meta: Optional[Dict] = None) -> bool:
        """
        Log a step event
        
        Args:
            task_id: Task ID
            step_name: Name of the step
            meta: Optional step metadata
        
        Returns:
            True if logged successfully
        """
        if task_id not in self.active_runs:
            logger.warning(f"Run not initialized for task {task_id}, initializing now")
            self.init_run(task_id)
        
        run_dir = self.active_runs[task_id]['run_dir']
        steps_file = run_dir / 'steps.jsonl'
        
        step_event = {
            'timestamp': datetime.utcnow().isoformat() + 'Z',
            'step': step_name,
            'meta': meta or {},
        }
        
        try:
            with open(steps_file, 'a', encoding='utf-8') as f:
                f.write(json.dumps(step_event, ensure_ascii=False) + '\n')
            
            logger.debug(f"Logged step '{step_name}' for task {task_id}")
            return True
        except Exception as e:
            logger.error(f"Failed to log step for task {task_id}: {e}")
            return False
    
    def save_snapshot(self, task_id: int, page: Page, name_prefix: str = "snapshot") -> Dict[str, Optional[str]]:
        """
        Save DOM snapshot and screenshot
        
        Args:
            task_id: Task ID
            page: Playwright Page object
            name_prefix: Prefix for snapshot files
        
        Returns:
            Dict with paths to saved files (or None if failed)
        """
        if task_id not in self.active_runs:
            logger.warning(f"Run not initialized for task {task_id}, initializing now")
            self.init_run(task_id)
        
        run_dir = self.active_runs[task_id]['run_dir']
        timestamp = datetime.utcnow().strftime('%Y%m%d_%H%M%S')
        
        result = {
            'dom_snapshot': None,
            'screenshot': None,
        }
        
        try:
            # Save DOM snapshot (timestamped and default)
            try:
                dom_content = page.content()
                
                # Save timestamped version
                dom_file_timestamped = run_dir / f'{name_prefix}_dom_{timestamp}.html'
                with open(dom_file_timestamped, 'w', encoding='utf-8') as f:
                    f.write(dom_content)
                result['dom_snapshot'] = str(dom_file_timestamped)
                
                # Save default dom_snapshot.html (latest)
                dom_file_default = run_dir / 'dom_snapshot.html'
                with open(dom_file_default, 'w', encoding='utf-8') as f:
                    f.write(dom_content)
                
                logger.debug(f"Saved DOM snapshot for task {task_id}: {dom_file_default}")
            except Exception as e:
                logger.warning(f"Failed to save DOM snapshot for task {task_id}: {e}")
            
            # Save screenshot (timestamped and default)
            try:
                # Save timestamped version
                screenshot_file_timestamped = run_dir / f'{name_prefix}_screenshot_{timestamp}.png'
                page.screenshot(path=str(screenshot_file_timestamped), full_page=True)
                result['screenshot'] = str(screenshot_file_timestamped)
                
                # Save default screenshot.png (latest)
                screenshot_file_default = run_dir / 'screenshot.png'
                page.screenshot(path=str(screenshot_file_default), full_page=True)
                
                logger.debug(f"Saved screenshot for task {task_id}: {screenshot_file_default}")
            except Exception as e:
                logger.warning(f"Failed to save screenshot for task {task_id}: {e}")
        
        except Exception as e:
            logger.error(f"Error saving snapshot for task {task_id}: {e}")
        
        return result
    
    def finalize_run(self, task_id: int, result_meta: Dict) -> Path:
        """
        Finalize a run and save final result
        
        Args:
            task_id: Task ID
            result_meta: Result metadata (success, failure_reason, execution_time, etc.)
        
        Returns:
            Path to final result file
        """
        if task_id not in self.active_runs:
            logger.warning(f"Run not initialized for task {task_id}, initializing now")
            self.init_run(task_id)
        
        run_info = self.active_runs[task_id]
        run_dir = run_info['run_dir']
        started_at = run_info['started_at']
        
        # Calculate execution time if not provided
        if 'execution_time' not in result_meta:
            execution_time = (datetime.utcnow() - started_at).total_seconds()
            result_meta['execution_time'] = round(execution_time, 2)
        
        # Build final result
        final_result = {
            'task_id': task_id,
            'started_at': started_at.isoformat() + 'Z',
            'completed_at': datetime.utcnow().isoformat() + 'Z',
            'execution_time': result_meta.get('execution_time'),
            'result': result_meta,
        }
        
        # Save final result
        result_file = run_dir / 'final_result.json'
        try:
            with open(result_file, 'w', encoding='utf-8') as f:
                json.dump(final_result, f, indent=2, ensure_ascii=False)
            
            logger.info(f"Finalized run telemetry for task {task_id}: {result_file}")
            
            # Clean up active run
            if task_id in self.active_runs:
                del self.active_runs[task_id]
            
            return result_file
        except Exception as e:
            logger.error(f"Failed to finalize run for task {task_id}: {e}")
            return result_file
    
    def get_run_dir(self, task_id: int) -> Optional[Path]:
        """Get run directory for a task"""
        if task_id in self.active_runs:
            return self.active_runs[task_id]['run_dir']
        
        # Check if run directory exists even if not active
        run_dir = self.runs_dir / str(task_id)
        if run_dir.exists():
            return run_dir
        
        return None


# Global telemetry instance
_telemetry_instance: Optional[Telemetry] = None


def get_telemetry() -> Telemetry:
    """Get global telemetry instance"""
    global _telemetry_instance
    if _telemetry_instance is None:
        runs_dir = os.getenv('TELEMETRY_RUNS_DIR', 'runs')
        _telemetry_instance = Telemetry(runs_dir=runs_dir)
    return _telemetry_instance


# Convenience functions
def init_run(task_id: int, meta: Optional[Dict] = None) -> Path:
    """Initialize a new run"""
    return get_telemetry().init_run(task_id, meta)


def log_step(task_id: int, step_name: str, meta: Optional[Dict] = None) -> bool:
    """Log a step event"""
    return get_telemetry().log_step(task_id, step_name, meta)


def save_snapshot(task_id: int, page: Page, name_prefix: str = "snapshot") -> Dict[str, Optional[str]]:
    """Save DOM snapshot and screenshot"""
    return get_telemetry().save_snapshot(task_id, page, name_prefix)


def finalize_run(task_id: int, result_meta: Dict) -> Path:
    """Finalize a run and save final result"""
    return get_telemetry().finalize_run(task_id, result_meta)

