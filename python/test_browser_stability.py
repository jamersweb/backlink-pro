"""Test browser stability on Windows"""
from playwright.sync_api import sync_playwright
import time
import logging

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

print("Testing Playwright browser stability on Windows...")
print("=" * 60)

try:
    with sync_playwright() as p:
        print("\n1. Launching browser...")
        browser = p.chromium.launch(
            headless=True,
            args=[
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--disable-blink-features=AutomationControlled',
                '--disable-gpu',
                # Remove --single-process as it can cause crashes on Windows
            ]
        )
        print("   ✓ Browser launched")
        
        print("\n2. Creating context...")
        context = browser.new_context(
            viewport={'width': 1920, 'height': 1080},
        )
        print("   ✓ Context created")
        
        print("\n3. Creating page...")
        page = context.new_page()
        print("   ✓ Page created")
        
        print("\n4. Testing navigation to simple site...")
        try:
            page.goto('https://example.com', wait_until='domcontentloaded', timeout=15000)
            print(f"   ✓ Navigation successful: {page.url}")
            time.sleep(2)
        except Exception as e:
            print(f"   ✗ Navigation failed: {e}")
            raise
        
        print("\n5. Testing navigation to PCMag...")
        try:
            page.goto('https://pcmag.com', wait_until='domcontentloaded', timeout=20000)
            print(f"   ✓ Navigation successful: {page.url}")
            time.sleep(2)
        except Exception as e:
            print(f"   ✗ Navigation failed: {e}")
            raise
        
        print("\n6. Closing browser...")
        browser.close()
        print("   ✓ Browser closed")
        
        print("\n" + "=" * 60)
        print("✅ Browser stability test PASSED")
        
except Exception as e:
    print(f"\n✗ Browser stability test FAILED: {e}")
    import traceback
    traceback.print_exc()
    exit(1)

