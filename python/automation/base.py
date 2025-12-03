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

logger = logging.getLogger(__name__)

# Import captcha solver
try:
    from automation.captcha_solver import CaptchaSolver
except ImportError:
    CaptchaSolver = None


class BaseAutomation(ABC):
    """Base class for all automation tasks"""
    
    def __init__(self, api_client, proxy: Optional[Dict] = None, headless: bool = True):
        self.api_client = api_client
        self.proxy = proxy
        self.headless = headless
        self.browser: Optional[Browser] = None
        self.context: Optional[BrowserContext] = None
        self.page: Optional[Page] = None
        self.captcha_solver = CaptchaSolver(api_client) if CaptchaSolver else None
    
    def setup_browser(self):
        """Setup browser with proxy and stealth settings"""
        playwright = sync_playwright().start()
        
        # Browser launch options
        launch_options = {
            'headless': self.headless,
            'args': [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--disable-blink-features=AutomationControlled',
            ],
        }
        
        # Add proxy if provided
        if self.proxy:
            launch_options['proxy'] = {
                'server': f"http://{self.proxy['host']}:{self.proxy['port']}",
                'username': self.proxy.get('username'),
                'password': self.proxy.get('password'),
            }
        
        self.browser = playwright.chromium.launch(**launch_options)
        
        # Create context with stealth settings
        self.context = self.browser.new_context(
            viewport={'width': 1920, 'height': 1080},
            user_agent=self._get_random_user_agent(),
            locale='en-US',
            timezone_id='America/New_York',
        )
        
        # Add stealth scripts
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
        
        self.page = self.context.new_page()
    
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
        """Cleanup browser resources"""
        if self.page:
            self.page.close()
        if self.context:
            self.context.close()
        if self.browser:
            self.browser.close()
    
    @abstractmethod
    def execute(self, task: Dict) -> Dict:
        """Execute the automation task"""
        pass
    
    def __enter__(self):
        self.setup_browser()
        return self
    
    def __exit__(self, exc_type, exc_val, exc_tb):
        self.cleanup()

