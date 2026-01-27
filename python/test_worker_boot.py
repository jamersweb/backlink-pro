"""
Dry-run test for worker boot
Tests that all imports work and worker can initialize without errors
"""

import sys
import os
import logging

# Set up basic logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)

def test_imports():
    """Test that all critical modules can be imported"""
    logger.info("=" * 70)
    logger.info("TEST 1: Importing Core Modules")
    logger.info("=" * 70)
    
    try:
        logger.info("Testing core.iframe_router...")
        from core.iframe_router import IframeRouter
        logger.info("✅ core.iframe_router imported successfully")
    except Exception as e:
        logger.error(f"❌ core.iframe_router import failed: {e}")
        return False
    
    try:
        logger.info("Testing core.locator_engine...")
        from core.locator_engine import LocatorEngine
        logger.info("✅ core.locator_engine imported successfully")
    except Exception as e:
        logger.error(f"❌ core.locator_engine import failed: {e}")
        return False
    
    try:
        logger.info("Testing automation.profile...")
        from automation.profile import ProfileAutomation
        logger.info("✅ automation.profile imported successfully")
    except Exception as e:
        logger.error(f"❌ automation.profile import failed: {e}")
        return False
    
    try:
        logger.info("Testing automation.base...")
        from automation.base import BaseAutomation
        logger.info("✅ automation.base imported successfully")
    except Exception as e:
        logger.error(f"❌ automation.base import failed: {e}")
        return False
    
    try:
        logger.info("Testing runtime.agent...")
        from runtime.agent import RuntimeAgent
        logger.info("✅ runtime.agent imported successfully")
    except Exception as e:
        logger.error(f"❌ runtime.agent import failed: {e}")
        return False
    
    return True

def test_worker_imports():
    """Test that worker.py can import all its dependencies"""
    logger.info("=" * 70)
    logger.info("TEST 2: Importing Worker Dependencies")
    logger.info("=" * 70)
    
    try:
        logger.info("Testing worker module imports...")
        # Import all the modules that worker.py imports
        from api_client import LaravelAPIClient
        from automation.comment import CommentAutomation
        from automation.profile import ProfileAutomation
        from automation.forum import ForumAutomation
        from automation.guest import GuestPostAutomation
        from automation.email_confirmation import EmailConfirmationAutomation
        from automation_logger import get_logger
        from shadow_mode_logger import get_shadow_logger
        from core.telemetry import init_run, log_step, save_snapshot, finalize_run
        from core.failure_mapper import FailureMapper
        from core.failure_enums import FailureReason
        from core.state_detector import StateDetector
        from core.popup_controller import PopupController
        from core.budget_guard import BudgetGuard, BudgetConfig, BudgetExceededException, BudgetExceededReason
        from core.domain_memory import get_domain_memory
        from runtime.agent import RuntimeAgent
        from runtime.healer import RuntimeHealer
        
        logger.info("✅ All worker dependencies imported successfully")
        return True
    except Exception as e:
        logger.error(f"❌ Worker dependency import failed: {e}")
        import traceback
        logger.error(traceback.format_exc())
        return False

def test_worker_initialization():
    """Test that worker can initialize without errors"""
    logger.info("=" * 70)
    logger.info("TEST 3: Worker Initialization")
    logger.info("=" * 70)
    
    try:
        logger.info("Testing worker module import...")
        import worker
        logger.info("✅ worker module imported successfully")
        
        logger.info("Testing worker configuration...")
        # Check that worker has required functions
        assert hasattr(worker, 'get_automation_class'), "Missing get_automation_class"
        assert hasattr(worker, 'process_task'), "Missing process_task"
        assert hasattr(worker, 'run_worker'), "Missing run_worker"
        assert hasattr(worker, 'parse_args'), "Missing parse_args"
        
        logger.info("✅ Worker module structure verified")
        
        # Test get_automation_class
        logger.info("Testing get_automation_class...")
        automation_class = worker.get_automation_class('profile')
        assert automation_class is not None, "get_automation_class returned None"
        logger.info(f"✅ get_automation_class('profile') = {automation_class.__name__}")
        
        return True
    except Exception as e:
        logger.error(f"❌ Worker initialization failed: {e}")
        import traceback
        logger.error(traceback.format_exc())
        return False

def test_playwright_availability():
    """Test that Playwright is available"""
    logger.info("=" * 70)
    logger.info("TEST 4: Playwright Availability")
    logger.info("=" * 70)
    
    try:
        logger.info("Testing Playwright import...")
        from playwright.sync_api import sync_playwright
        logger.info("✅ Playwright imported successfully")
        
        # Don't actually start browser in dry-run (too slow)
        logger.info("✅ Playwright available (browser not started in dry-run)")
        return True
    except ImportError as e:
        logger.warning(f"⚠️ Playwright not available: {e}")
        logger.warning("This is OK for dry-run, but browser automation won't work")
        return True  # Not a blocker for import test
    except Exception as e:
        logger.error(f"❌ Playwright test failed: {e}")
        return False

def main():
    """Run all dry-run tests"""
    logger.info("=" * 70)
    logger.info("WORKER DRY-RUN TEST")
    logger.info("=" * 70)
    logger.info("Testing worker boot and initialization...")
    logger.info("")
    
    results = []
    
    # Test 1: Core imports
    results.append(("Core Imports", test_imports()))
    
    # Test 2: Worker dependencies
    results.append(("Worker Dependencies", test_worker_imports()))
    
    # Test 3: Worker initialization
    results.append(("Worker Initialization", test_worker_initialization()))
    
    # Test 4: Playwright
    results.append(("Playwright Availability", test_playwright_availability()))
    
    # Summary
    logger.info("")
    logger.info("=" * 70)
    logger.info("DRY-RUN TEST SUMMARY")
    logger.info("=" * 70)
    
    all_passed = True
    for test_name, passed in results:
        status = "✅ PASS" if passed else "❌ FAIL"
        logger.info(f"{status} - {test_name}")
        if not passed:
            all_passed = False
    
    logger.info("")
    if all_passed:
        logger.info("🎉 ALL TESTS PASSED - Worker is ready to boot!")
        logger.info("You can now run: python worker.py --once --limit 1")
        return 0
    else:
        logger.error("❌ SOME TESTS FAILED - Worker may not boot correctly")
        return 1

if __name__ == "__main__":
    sys.exit(main())


