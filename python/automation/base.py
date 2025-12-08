"""
Base automation class for all backlink types
"""
from abc import ABC, abstractmethod
from typing import Dict, Optional, List
from playwright.sync_api import Page, BrowserContext, Browser
from playwright.sync_api import sync_playwright
import logging
import random
import time
import os
import asyncio
import glob

# Skip Playwright's host requirement validation
# This allows browsers to launch even if dependency validation fails
# Dependencies are installed in Dockerfile, but validation can be overly strict
os.environ['PLAYWRIGHT_SKIP_VALIDATE_HOST_REQUIREMENTS'] = '1'

# IMPORTANT: We do NOT set a restrictive event loop policy here
# Playwright's sync API needs to create its own internal event loop
# We'll check for existing loops before Playwright starts, but allow Playwright to create one

logger = logging.getLogger(__name__)

# Import captcha solver
try:
    from automation.captcha_solver import CaptchaSolver
except ImportError:
    CaptchaSolver = None

# Import opportunity selector
try:
    from opportunity_selector import OpportunitySelector
except ImportError:
    OpportunitySelector = None


class BaseAutomation(ABC):
    """Base class for all automation tasks"""

    def __init__(self, api_client, proxy: Optional[Dict] = None, headless: bool = True):
        self.api_client = api_client
        self.proxy = proxy
        self.headless = headless
        self.playwright = None
        self.browser: Optional[Browser] = None
        self.context: Optional[BrowserContext] = None
        self.page: Optional[Page] = None
        self.captcha_solver = CaptchaSolver(api_client) if CaptchaSolver else None
        self.opportunity_selector = OpportunitySelector(api_client) if OpportunitySelector else None

    def setup_browser(self):
        """Setup browser with proxy and stealth settings"""
        # Ensure libglib and other libraries are available
        # Force install and verify libglib2.0-0 before browser launch
        import subprocess
        libglib_available = False

        try:
            # First check if library is in cache
            result = subprocess.run(
                ['ldconfig', '-p'],
                capture_output=True,
                text=True,
                timeout=5
            )
            if 'libglib-2.0.so.0' in result.stdout:
                libglib_available = True
                logger.debug("libglib-2.0.so.0 found in library cache")
            else:
                # Check if file exists directly
                find_result = subprocess.run(
                    ['find', '/usr/lib*', '/lib*', '-name', 'libglib-2.0.so.0', '2>/dev/null'],
                    shell=True,
                    capture_output=True,
                    text=True,
                    timeout=5
                )
                if find_result.stdout.strip():
                    libglib_available = True
                    logger.debug(f"libglib-2.0.so.0 found at: {find_result.stdout.strip().split(chr(10))[0]}")
        except Exception as e:
            logger.debug(f"Error checking libglib: {e}")

        if not libglib_available:
            logger.warning("libglib-2.0.so.0 not found. Attempting to install...")
            try:
                # Update package list
                update_result = subprocess.run(
                    ['apt-get', 'update', '-qq'],
                    capture_output=True,
                    timeout=30,
                    stderr=subprocess.PIPE
                )

                # Install libglib2.0-0 and libglib2.0-bin
                install_result = subprocess.run(
                    ['apt-get', 'install', '-y', '--no-install-recommends', 'libglib2.0-0', 'libglib2.0-bin'],
                    capture_output=True,
                    text=True,
                    timeout=120,
                    stderr=subprocess.PIPE
                )

                if install_result.returncode == 0:
                    logger.info("libglib2.0-0 installed successfully")
                    # Update library cache
                    ldconfig_result = subprocess.run(
                        ['ldconfig'],
                        capture_output=True,
                        timeout=10,
                        stderr=subprocess.PIPE
                    )
                    if ldconfig_result.returncode != 0:
                        logger.warning(f"ldconfig had issues: {ldconfig_result.stderr.decode()[:200]}")

                    # Verify installation
                    verify_result = subprocess.run(
                        ['ldconfig', '-p'],
                        capture_output=True,
                        text=True,
                        timeout=5
                    )
                    if 'libglib-2.0.so.0' in verify_result.stdout:
                        logger.info("libglib-2.0.so.0 verified in library cache")
                        libglib_available = True
                    else:
                        logger.error("libglib-2.0.so.0 still not found after installation!")
                else:
                    error_output = install_result.stderr if install_result.stderr else install_result.stdout
                    logger.error(f"Failed to install libglib2.0-0: {error_output[-500:]}")
            except subprocess.TimeoutExpired:
                logger.error("Timeout installing libglib2.0-0")
            except Exception as e:
                logger.error(f"Exception installing libglib2.0-0: {e}")

        if not libglib_available:
            raise RuntimeError(
                "libglib-2.0.so.0 is not available. Browser cannot launch without this library. "
                "Please ensure libglib2.0-0 is installed in the Docker container."
            )

        # Set LD_LIBRARY_PATH to help dynamic linker find libraries
        # This is critical for finding libglib-2.0.so.0
        # First, check if it's already set from environment (docker-compose.yml)
        existing_ld_path = os.environ.get('LD_LIBRARY_PATH', '')

        try:
            # First, ensure ldconfig is run to update library cache
            try:
                ldconfig_result = subprocess.run(
                    ['ldconfig'],
                    capture_output=True,
                    timeout=10,
                    stderr=subprocess.PIPE
                )
                if ldconfig_result.returncode != 0:
                    logger.debug(f"ldconfig warning: {ldconfig_result.stderr.decode()[:200]}")
            except Exception as e:
                logger.debug(f"Could not run ldconfig: {e}")

            # Try to find common library paths
            lib_paths = []
            # Start with standard library paths
            for path in ['/usr/lib/x86_64-linux-gnu', '/usr/lib', '/lib/x86_64-linux-gnu', '/lib', '/usr/local/lib']:
                if os.path.exists(path):
                    lib_paths.append(path)

            # If LD_LIBRARY_PATH was set from environment, include those paths too
            if existing_ld_path:
                env_paths = [p for p in existing_ld_path.split(':') if p and os.path.exists(p)]
                for env_path in env_paths:
                    if env_path not in lib_paths:
                        lib_paths.insert(0, env_path)  # Environment paths get priority
                logger.debug(f"Found LD_LIBRARY_PATH in environment: {existing_ld_path}")

            # Also try to find where libglib-2.0.so.0 actually is
            libglib_path = None
            try:
                find_result = subprocess.run(
                    ['find', '/usr/lib*', '/lib*', '/usr/local/lib*', '-name', 'libglib-2.0.so.0', '2>/dev/null'],
                    shell=True,
                    capture_output=True,
                    text=True,
                    timeout=10
                )
                if find_result.stdout.strip():
                    lib_file = find_result.stdout.strip().split('\n')[0]
                    lib_dir = os.path.dirname(lib_file)
                    libglib_path = lib_dir
                    if lib_dir not in lib_paths:
                        lib_paths.insert(0, lib_dir)  # Add to front for priority
                        logger.info(f"Found libglib-2.0.so.0 at {lib_file}, added {lib_dir} to library path")
            except Exception as e:
                logger.debug(f"Could not find libglib path: {e}")

            # Also check dpkg for installed package location
            if not libglib_path:
                try:
                    dpkg_result = subprocess.run(
                        ['dpkg', '-L', 'libglib2.0-0'],
                        capture_output=True,
                        text=True,
                        timeout=5
                    )
                    if dpkg_result.returncode == 0:
                        for line in dpkg_result.stdout.split('\n'):
                            if 'libglib-2.0.so.0' in line and os.path.exists(line):
                                lib_dir = os.path.dirname(line)
                                if lib_dir not in lib_paths:
                                    lib_paths.insert(0, lib_dir)
                                    logger.info(f"Found libglib-2.0.so.0 via dpkg at {line}, added {lib_dir} to library path")
                                break
                except Exception:
                    pass

            if lib_paths:
                # Merge paths, avoiding duplicates, preserving order (new paths first, then existing)
                final_paths = []
                seen = set()
                # Add discovered paths first (priority)
                for path in lib_paths:
                    if path not in seen:
                        final_paths.append(path)
                        seen.add(path)
                # Add any existing paths that weren't already included
                if existing_ld_path:
                    for path in existing_ld_path.split(':'):
                        if path and path not in seen:
                            final_paths.append(path)
                            seen.add(path)

                new_ld_path = ':'.join(final_paths)
                # Remove any empty paths (leading/trailing colons)
                new_ld_path = ':'.join([p for p in new_ld_path.split(':') if p])
                os.environ['LD_LIBRARY_PATH'] = new_ld_path
                logger.info(f"Set LD_LIBRARY_PATH to: {new_ld_path}")
            else:
                # If no paths found but environment had it set, keep the environment value
                if existing_ld_path:
                    # Clean up any empty paths (leading/trailing colons)
                    cleaned_path = ':'.join([p for p in existing_ld_path.split(':') if p])
                    os.environ['LD_LIBRARY_PATH'] = cleaned_path
                    logger.info(f"Using LD_LIBRARY_PATH from environment: {cleaned_path}")
                else:
                    logger.warning("No library paths found for LD_LIBRARY_PATH")
        except Exception as e:
            logger.warning(f"Could not set LD_LIBRARY_PATH: {e}")

        # Ensure we're not in an asyncio event loop when using sync_playwright
        # Playwright Sync API cannot be used inside an asyncio event loop
        # Note: asyncio is imported at module level, policy is set there to prevent auto-creation
        import threading

        def check_and_cleanup_event_loop():
            """Check for event loops and clean them up if possible"""
            try:
                # First check: Is there a running loop in this thread?
                try:
                    running_loop = asyncio.get_running_loop()
                    # If we get here, there's a running loop - this is a problem
                    logger.error("ERROR: Running asyncio event loop detected!")
                    logger.error("Playwright Sync API cannot be used inside an asyncio event loop.")
                    raise RuntimeError(
                        "Cannot use Playwright Sync API inside asyncio event loop. "
                        "Please ensure no async libraries are creating event loops."
                    )
                except RuntimeError as e:
                    if "no running event loop" not in str(e).lower() and "cannot use playwright" not in str(e).lower():
                        # Unexpected RuntimeError, re-raise it
                        raise
                    # No running loop, continue checking
                    pass

                # Second: Aggressively clear any existing event loop for this thread
                # Try multiple methods to ensure cleanup
                try:
                    # Method 1: Clear thread-local event loop
                    asyncio.set_event_loop(None)
                except Exception:
                    pass

                try:
                    # Method 2: Clear via policy (if we can access it)
                    # Note: We avoid calling get_event_loop_policy() if possible to prevent any side effects
                    policy = asyncio.get_event_loop_policy()
                    if hasattr(policy, '_local'):
                        if hasattr(policy._local, '_loop'):
                            policy._local._loop = None
                except Exception:
                    pass

                # Third: Ensure no event loop exists before Playwright starts
                # We don't prevent loop creation (Playwright needs to create one internally)
                # But we ensure we're not already in a running loop
                # This is the key: check for RUNNING loops, not just any loop

            except RuntimeError as e:
                if "Cannot use Playwright" in str(e):
                    raise
                # Re-raise other RuntimeErrors that aren't about missing loops
                if "no running event loop" not in str(e).lower():
                    raise
            except Exception:
                # Ignore other errors during cleanup
                pass

        # Simple check: Only verify we're not in a RUNNING event loop
        # Playwright's sync API will create its own internal event loop - we must allow this
        try:
            running_loop = asyncio.get_running_loop()
            # If we get here, there's a running loop - this is a problem
            logger.error(f"CRITICAL: Running event loop detected before Playwright start: {running_loop}")
            raise RuntimeError(
                "Cannot use Playwright Sync API inside an asyncio event loop. "
                "A running event loop was detected right before Playwright start."
            )
        except RuntimeError as e:
            if "no running event loop" not in str(e).lower() and "cannot use playwright" not in str(e).lower():
                raise
            # Good - no running loop, Playwright can create its own

        # Clear any non-running loops to ensure clean state
        try:
            asyncio.set_event_loop(None)
        except Exception:
            pass

        # Ensure default event loop policy (not a restrictive one)
        try:
            current_policy = asyncio.get_event_loop_policy()
            # If policy is not the default, reset it
            if not isinstance(current_policy, asyncio.DefaultEventLoopPolicy):
                logger.debug(f"Resetting event loop policy from {type(current_policy).__name__} to DefaultEventLoopPolicy")
                asyncio.set_event_loop_policy(asyncio.DefaultEventLoopPolicy())
        except Exception as e:
            logger.debug(f"Could not check/reset event loop policy: {e}")

        # Start Playwright - it will create its own internal event loop
        # This is expected and necessary for Playwright sync API to work
        logger.debug("Starting Playwright (no running event loops detected, Playwright will create its own)")
        self.playwright = sync_playwright().start()

        # Browser launch options
        launch_options = {
            'headless': self.headless,
            'args': [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--disable-blink-features=AutomationControlled',
                # Additional args to help with missing dependencies
                '--disable-gpu',
                '--disable-software-rasterizer',
            ],
        }

        # Add proxy if provided
        if self.proxy:
            launch_options['proxy'] = {
                'server': f"http://{self.proxy['host']}:{self.proxy['port']}",
                'username': self.proxy.get('username'),
                'password': self.proxy.get('password'),
            }

        # Final verification: Ensure library cache is updated and library is accessible
        libglib_verified = False
        try:
            # Run ldconfig one more time right before launch
            ldconfig_result = subprocess.run(['ldconfig'], capture_output=True, timeout=10, stderr=subprocess.PIPE)
            if ldconfig_result.returncode != 0:
                logger.warning(f"ldconfig had issues: {ldconfig_result.stderr.decode()[:200]}")

            # Verify libglib is accessible
            verify_result = subprocess.run(
                ['ldconfig', '-p'],
                capture_output=True,
                text=True,
                timeout=5
            )
            if 'libglib-2.0.so.0' in verify_result.stdout:
                libglib_verified = True
                logger.info("libglib-2.0.so.0 found in library cache")
            else:
                logger.warning("libglib-2.0.so.0 not in library cache, searching filesystem...")
                # Try to find it directly
                find_result = subprocess.run(
                    ['find', '/usr/lib*', '/lib*', '-name', 'libglib-2.0.so.0', '2>/dev/null'],
                    shell=True,
                    capture_output=True,
                    text=True,
                    timeout=10
                )
                if find_result.stdout.strip():
                    lib_file = find_result.stdout.strip().split('\n')[0]
                    logger.info(f"Found libglib-2.0.so.0 at {lib_file}")
                    # Try to actually load the library to verify it's accessible
                    try:
                        test_result = subprocess.run(
                            ['ldd', lib_file],
                            capture_output=True,
                            text=True,
                            timeout=5
                        )
                        if test_result.returncode == 0:
                            libglib_verified = True
                            logger.info(f"Library file is valid and loadable: {lib_file}")
                        else:
                            logger.warning(f"Library file found but may have dependency issues: {test_result.stderr}")
                    except Exception as e:
                        logger.debug(f"Could not test library loading: {e}")
                else:
                    logger.error("libglib-2.0.so.0 not found anywhere on filesystem!")

            # If still not verified, try to install it
            if not libglib_verified:
                logger.warning("libglib-2.0.so.0 not verified. Attempting emergency installation...")
                try:
                    install_result = subprocess.run(
                        ['apt-get', 'update', '-qq'],
                        capture_output=True,
                        timeout=30,
                        stderr=subprocess.PIPE
                    )
                    install_result = subprocess.run(
                        ['apt-get', 'install', '-y', '--no-install-recommends', 'libglib2.0-0', 'libglib2.0-bin'],
                        capture_output=True,
                        text=True,
                        timeout=120,
                        stderr=subprocess.PIPE
                    )
                    if install_result.returncode == 0:
                        subprocess.run(['ldconfig'], capture_output=True, timeout=10)
                        logger.info("Emergency libglib installation completed")
                        libglib_verified = True
                    else:
                        logger.error(f"Emergency installation failed: {install_result.stderr[:500]}")
                except Exception as e:
                    logger.error(f"Emergency installation exception: {e}")

        except Exception as e:
            logger.warning(f"Library verification warning: {e}")

        if not libglib_verified:
            logger.error("WARNING: libglib-2.0.so.0 verification failed. Browser launch may fail.")

        # Try to launch browser - if it fails due to dependencies, try to install them
        try:
            ld_path = os.environ.get('LD_LIBRARY_PATH', 'not set')
            logger.info(f"Launching browser with LD_LIBRARY_PATH={ld_path}")
            logger.info(f"libglib verified: {libglib_verified}")

            # Test if browser executable can actually run (check dependencies)
            try:
                import glob
                playwright_cache = os.path.expanduser('~/.cache/ms-playwright')
                chromium_paths = glob.glob(f"{playwright_cache}/chromium-*/chrome-linux/chrome")
                if chromium_paths:
                    chrome_path = chromium_paths[0]
                    logger.info(f"Testing browser executable dependencies: {chrome_path}")
                    # Try to check what libraries it needs
                    ldd_result = subprocess.run(
                        ['ldd', chrome_path],
                        capture_output=True,
                        text=True,
                        timeout=10
                    )
                    if ldd_result.returncode == 0:
                        # Check for missing libraries
                        missing_libs = []
                        for line in ldd_result.stdout.split('\n'):
                            if 'not found' in line.lower():
                                missing_libs.append(line.strip())
                        if missing_libs:
                            logger.error(f"Browser executable has MISSING dependencies:")
                            for lib in missing_libs[:5]:  # Show first 5
                                logger.error(f"  - {lib}")
                            logger.error("This will cause browser launch to fail!")
                            logger.error("Try: apt-get update && apt-get install -y <missing-package>")
                        else:
                            logger.info("✓ Browser executable dependencies OK (all libraries found)")
                    else:
                        logger.warning(f"Could not check browser dependencies: {ldd_result.stderr}")

                    # Also try to run the browser with --version to see if it can start
                    try:
                        version_result = subprocess.run(
                            [chrome_path, '--version'],
                            capture_output=True,
                            text=True,
                            timeout=5,
                            env=dict(os.environ, LD_LIBRARY_PATH=os.environ.get('LD_LIBRARY_PATH', ''))
                        )
                        if version_result.returncode == 0:
                            logger.info(f"✓ Browser executable can run: {version_result.stdout.strip()}")
                        else:
                            logger.warning(f"Browser --version failed: {version_result.stderr}")
                    except Exception as e:
                        logger.warning(f"Could not test browser --version: {e}")
            except Exception as e:
                logger.warning(f"Could not test browser executable: {e}")

            # Additional browser launch args for better compatibility
            launch_options['args'].extend([
                '--disable-extensions',
                '--disable-background-networking',
                '--disable-background-timer-throttling',
                '--disable-backgrounding-occluded-windows',
                '--disable-breakpad',
                '--disable-component-extensions-with-background-pages',
                '--disable-features=TranslateUI',
                '--disable-ipc-flooding-protection',
                '--disable-renderer-backgrounding',
                '--disable-sync',
                '--force-color-profile=srgb',
                '--metrics-recording-only',
                '--no-first-run',
                '--enable-automation',
                '--password-store=basic',
                '--use-mock-keychain',
                # Additional stability flags
                '--single-process',  # Run in single process mode (helps with some dependency issues)
                '--disable-software-rasterizer',
            ])

            self.browser = self.playwright.chromium.launch(**launch_options)
        except Exception as e:
            error_msg = str(e).lower()
            original_error = e

            # Check for common error types and provide helpful messages
            if "target closed" in error_msg or "browser has been closed" in error_msg or "process was closed" in error_msg:
                # Try to get more diagnostic information
                diagnostics = []
                try:
                    # Check library cache
                    ldconfig_result = subprocess.run(['ldconfig', '-p'], capture_output=True, text=True, timeout=5)
                    if 'libglib-2.0.so.0' not in ldconfig_result.stdout:
                        diagnostics.append("libglib-2.0.so.0 NOT found in library cache")
                    else:
                        diagnostics.append("libglib-2.0.so.0 found in library cache")
                except Exception:
                    pass

                try:
                    # Check LD_LIBRARY_PATH
                    ld_path = os.environ.get('LD_LIBRARY_PATH', 'not set')
                    diagnostics.append(f"LD_LIBRARY_PATH={ld_path}")
                except Exception:
                    pass

                try:
                    # Check if browser executable exists
                    playwright_cache = os.path.expanduser('~/.cache/ms-playwright')
                    chromium_paths = [
                        f"{playwright_cache}/chromium-*/chrome-linux/chrome",
                        f"{playwright_cache}/chromium-*/chrome-linux/chrome-wrapper",
                    ]
                    browser_found = False
                    for pattern in chromium_paths:
                        matches = glob.glob(pattern)
                        if matches:
                            browser_found = True
                            diagnostics.append(f"Browser executable found at: {matches[0]}")
                            break
                    if not browser_found:
                        diagnostics.append("Browser executable NOT found in Playwright cache")
                except Exception:
                    pass

                error_details = "\n".join(diagnostics) if diagnostics else "No diagnostics available"
                raise RuntimeError(
                    "Browser failed to launch - the browser process was closed immediately. "
                    "This usually indicates missing system dependencies (like libglib-2.0.so.0). "
                    "Check that all Playwright dependencies are installed.\n\n"
                    f"Diagnostics:\n{error_details}\n\n"
                    "Try rebuilding the Docker container or running: "
                    "apt-get update && apt-get install -y libglib2.0-0 libglib2.0-bin && ldconfig"
                ) from e

            if "executable doesn't exist" in error_msg or "browser executable" in error_msg:
                raise RuntimeError(
                    "Browser executable not found. Please run: python3 -m playwright install chromium"
                ) from e

            if "missing dependencies" in error_msg or "install-deps" in error_msg or "libglib" in error_msg:
                logger.warning("Playwright detected missing dependencies. Attempting to install...")
                import subprocess
                try:
                    # Try to install dependencies
                    result = subprocess.run(
                        ['python3', '-m', 'playwright', 'install-deps', 'chromium'],
                        capture_output=True,
                        text=True,
                        timeout=300
                    )
                    if result.returncode == 0:
                        logger.info("Dependencies installed, retrying browser launch...")
                        self.browser = self.playwright.chromium.launch(**launch_options)
                    else:
                        logger.error(f"Failed to install dependencies: {result.stderr}")
                        raise RuntimeError(
                            f"Failed to install Playwright dependencies: {result.stderr}. "
                            "Please ensure libglib2.0-0 and other dependencies are installed."
                        ) from e
                except subprocess.TimeoutExpired:
                    raise RuntimeError(
                        "Timeout installing Playwright dependencies. "
                        "Please install manually: apt-get install -y libglib2.0-0 libglib2.0-bin && ldconfig"
                    ) from e
                except Exception as install_error:
                    logger.error(f"Error installing dependencies: {install_error}")
                    # Try launching anyway - sometimes browsers work despite validation errors
                    logger.warning("Attempting browser launch despite dependency warnings...")
                    try:
                        self.browser = self.playwright.chromium.launch(**launch_options)
                    except Exception as retry_error:
                        raise RuntimeError(
                            f"Browser launch failed after dependency installation attempt. "
                            f"Original error: {original_error}. Retry error: {retry_error}"
                        ) from retry_error
            else:
                # Re-raise with more context
                raise RuntimeError(
                    f"Browser launch failed: {str(e)}. "
                    "Check that Chromium is installed and all system dependencies are available."
                ) from e

        # Create context with stealth settings
        try:
            self.context = self.browser.new_context(
                viewport={'width': 1920, 'height': 1080},
                user_agent=self._get_random_user_agent(),
                locale='en-US',
                timezone_id='America/New_York',
            )
        except Exception as e:
            raise RuntimeError(
                f"Failed to create browser context: {str(e)}. "
                "The browser may have closed unexpectedly."
            ) from e

        # Add stealth scripts
        try:
            self.context.add_init_script("""
                Object.defineProperty(navigator, 'webdriver', {
                    get: () => undefined
                });

                window.chrome = {
                    runtime: {}
                };

                Object.defineProperty(navigator, 'plugins', {
                    get: () => [1, 2, 3, 4, 5]
                });
            """)
        except Exception as e:
            logger.warning(f"Failed to add stealth scripts: {e}")
            # Continue anyway - stealth scripts are nice-to-have

        # Create page
        try:
            self.page = self.context.new_page()
        except Exception as e:
            raise RuntimeError(
                f"Failed to create browser page: {str(e)}. "
                "The browser context may have closed unexpectedly."
            ) from e

        logger.debug("Browser setup completed successfully")

    def _get_random_user_agent(self) -> str:
        """Get random user agent"""
        user_agents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        ]
        return random.choice(user_agents)

    def random_delay(self, min_seconds: float = 1.0, max_seconds: float = 3.0):
        """Random delay to mimic human behavior"""
        delay = random.uniform(min_seconds, max_seconds)
        time.sleep(delay)

    def human_type(self, page: Page, selector: str, text: str):
        """Type text with human-like delays"""
        element = page.locator(selector)
        element.click()
        self.random_delay(0.5, 1.0)

        for char in text:
            element.type(char, delay=random.uniform(50, 150))
            time.sleep(random.uniform(0.05, 0.15))

    def take_screenshot(self, filename: str):
        """Take screenshot for debugging"""
        if self.page:
            try:
                self.page.screenshot(path=f"screenshots/{filename}")
            except Exception as e:
                logger.warning(f"Failed to take screenshot: {e}")

    def solve_captcha_if_present(self) -> bool:
        """Detect and solve captcha if present on page"""
        if not self.captcha_solver or not self.page:
            return False

        try:
            solution = self.captcha_solver.detect_and_solve(self.page)
            return solution is not None
        except Exception as e:
            logger.warning(f"Captcha solving failed: {e}")
            return False

    def cleanup(self):
        """Cleanup browser resources - ensures all resources are properly closed"""
        cleanup_errors = []

        # Close page first
        if self.page:
            try:
                if not self.page.is_closed():
                    self.page.close()
            except Exception as e:
                cleanup_errors.append(f"page: {e}")
                logger.warning(f"Error closing page: {e}")
            finally:
                self.page = None

        # Close context
        if self.context:
            try:
                if not self.context.pages:  # Only close if no pages
                    self.context.close()
                else:
                    # Force close all pages first
                    for page in self.context.pages:
                        try:
                            if not page.is_closed():
                                page.close()
                        except:
                            pass
                    self.context.close()
            except Exception as e:
                cleanup_errors.append(f"context: {e}")
                logger.warning(f"Error closing context: {e}")
            finally:
                self.context = None

        # Close browser
        if self.browser:
            try:
                # Check if browser is still connected
                try:
                    self.browser.close()
                except Exception as e:
                    # Browser might already be closed
                    if "Target closed" not in str(e).lower() and "Browser closed" not in str(e).lower():
                        raise
            except Exception as e:
                cleanup_errors.append(f"browser: {e}")
                logger.warning(f"Error closing browser: {e}")
            finally:
                self.browser = None

        # Stop playwright
        if self.playwright:
            try:
                self.playwright.stop()
            except Exception as e:
                cleanup_errors.append(f"playwright: {e}")
                logger.warning(f"Error stopping playwright: {e}")
            finally:
                self.playwright = None

        # Log summary if there were cleanup errors
        if cleanup_errors:
            logger.debug(f"Cleanup completed with {len(cleanup_errors)} errors: {', '.join(cleanup_errors)}")
        else:
            logger.debug("Cleanup completed successfully")

    @abstractmethod
    def execute(self, task: Dict) -> Dict:
        """Execute the automation task"""
        pass

    def __enter__(self):
        """Context manager entry - setup browser"""
        try:
            self.setup_browser()
            return self
        except Exception as e:
            # If setup fails, ensure we clean up any partially initialized resources
            logger.error(f"Failed to setup browser in __enter__: {e}")
            try:
                self.cleanup()
            except Exception as cleanup_error:
                logger.warning(f"Error during cleanup after setup failure: {cleanup_error}")
            # Re-raise the original error
            raise

    def __exit__(self, exc_type, exc_val, exc_tb):
        """Context manager exit - always cleanup, even if there was an exception"""
        try:
            self.cleanup()
        except Exception as cleanup_error:
            # Log cleanup errors but don't suppress the original exception
            logger.warning(f"Error during cleanup in __exit__: {cleanup_error}")

        # Don't suppress exceptions - let them propagate
        return False

