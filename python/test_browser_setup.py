#!/usr/bin/env python3
"""
Diagnostic script to test browser setup and dependencies
Run this inside the Docker container to verify everything is configured correctly
"""
import subprocess
import sys
import os

def check_library(lib_name):
    """Check if a library is available"""
    print(f"\n=== Checking {lib_name} ===")

    # Check library cache
    result = subprocess.run(['ldconfig', '-p'], capture_output=True, text=True, timeout=5)
    if lib_name in result.stdout:
        print(f"✓ {lib_name} found in library cache")
        return True
    else:
        print(f"✗ {lib_name} NOT in library cache")

    # Check filesystem
    result = subprocess.run(
        ['find', '/usr/lib*', '/lib*', '-name', lib_name, '2>/dev/null'],
        shell=True,
        capture_output=True,
        text=True,
        timeout=10
    )
    if result.stdout.strip():
        lib_file = result.stdout.strip().split('\n')[0]
        print(f"✓ Found {lib_name} at: {lib_file}")

        # Test if it's loadable
        ldd_result = subprocess.run(['ldd', lib_file], capture_output=True, text=True, timeout=5)
        if ldd_result.returncode == 0:
            print(f"✓ Library is loadable")
            return True
        else:
            print(f"✗ Library has dependency issues: {ldd_result.stderr}")
    else:
        print(f"✗ {lib_name} not found on filesystem")

    return False

def check_playwright():
    """Check if Playwright browsers are installed"""
    print("\n=== Checking Playwright ===")
    try:
        import glob
        playwright_cache = os.path.expanduser('~/.cache/ms-playwright')
        chromium_paths = glob.glob(f"{playwright_cache}/chromium-*/chrome-linux/chrome")
        if chromium_paths:
            print(f"✓ Chromium found at: {chromium_paths[0]}")
            # Check if executable
            if os.access(chromium_paths[0], os.X_OK):
                print("✓ Chromium is executable")
                return True
            else:
                print("✗ Chromium is not executable")
        else:
            print("✗ Chromium not found in Playwright cache")
    except Exception as e:
        print(f"✗ Error checking Playwright: {e}")

    return False

def check_ld_library_path():
    """Check LD_LIBRARY_PATH"""
    print("\n=== Checking LD_LIBRARY_PATH ===")
    ld_path = os.environ.get('LD_LIBRARY_PATH', 'not set')
    print(f"LD_LIBRARY_PATH={ld_path}")

    if ld_path == 'not set':
        print("⚠ LD_LIBRARY_PATH not set in environment")
        print("  Note: This is OK if libraries are in standard paths and ldconfig cache")
        print("  Libraries will still be found via ldconfig cache")
        return False
    else:
        # Verify paths exist
        paths = ld_path.split(':')
        valid_paths = []
        invalid_paths = []
        for path in paths:
            if path and os.path.exists(path):
                valid_paths.append(path)
            elif path:
                invalid_paths.append(path)

        if valid_paths:
            print(f"✓ Found {len(valid_paths)} valid path(s)")
            for path in valid_paths[:3]:  # Show first 3
                print(f"  - {path}")
        if invalid_paths:
            print(f"⚠ Found {len(invalid_paths)} invalid path(s)")
            for path in invalid_paths[:3]:  # Show first 3
                print(f"  - {path} (does not exist)")

        return len(valid_paths) > 0

def main():
    print("Browser Setup Diagnostic Tool")
    print("=" * 50)

    libglib_ok = check_library('libglib-2.0.so.0')
    playwright_ok = check_playwright()
    ld_path_ok = check_ld_library_path()

    print("\n" + "=" * 50)
    print("SUMMARY:")
    print(f"  libglib-2.0.so.0: {'✓ OK' if libglib_ok else '✗ FAIL'}")
    print(f"  Playwright Chromium: {'✓ OK' if playwright_ok else '✗ FAIL'}")
    print(f"  LD_LIBRARY_PATH: {'✓ SET' if ld_path_ok else '⚠ NOT SET (OK if libglib in cache)'}")

    # Note: LD_LIBRARY_PATH not being set is OK if libraries are in standard paths
    # and registered with ldconfig (which they are, since libglib check passed)
    if libglib_ok and playwright_ok:
        print("\n✓ All critical checks passed! Browser should launch successfully.")
        if not ld_path_ok:
            print("\nNote: LD_LIBRARY_PATH is not set, but this is OK because:")
            print("  - libglib-2.0.so.0 is found in library cache (ldconfig)")
            print("  - The Python automation code will set LD_LIBRARY_PATH dynamically")
            print("  - Libraries in standard paths (/usr/lib, /lib) are found automatically")
        return 0
    else:
        print("\n✗ Some checks failed. Browser launch may fail.")
        print("\nTo fix:")
        if not libglib_ok:
            print("  - Run: apt-get update && apt-get install -y libglib2.0-0 libglib2.0-bin && ldconfig")
        if not playwright_ok:
            print("  - Run: python3 -m playwright install chromium")
        return 1

if __name__ == '__main__':
    sys.exit(main())
