"""Test Playwright on Windows"""
import sys
import platform
from playwright.sync_api import sync_playwright

print(f"Platform: {platform.system()}")
print(f"Python: {sys.version}")

try:
    with sync_playwright() as p:
        print("✓ Playwright initialized successfully")
        browser = p.chromium.launch(headless=True)
        print("✓ Browser launched successfully")
        page = browser.new_page()
        print("✓ Page created successfully")
        page.goto("https://example.com")
        print("✓ Page navigation successful")
        browser.close()
        print("\n✅ All tests passed! Playwright works on Windows.")
except Exception as e:
    print(f"\n❌ Error: {e}")
    import traceback
    traceback.print_exc()
    sys.exit(1)

