#!/usr/bin/env python3
"""
Check browser executable dependencies and identify missing libraries
Run this inside the Docker container to diagnose browser launch issues
"""
import subprocess
import sys
import os
import glob

def check_browser_dependencies():
    """Check what libraries the browser executable needs"""
    print("=" * 70)
    print("Browser Dependency Checker")
    print("=" * 70)

    # Find browser executable
    playwright_cache = os.path.expanduser('~/.cache/ms-playwright')
    chromium_paths = glob.glob(f"{playwright_cache}/chromium-*/chrome-linux/chrome")

    if not chromium_paths:
        print("❌ ERROR: Browser executable not found!")
        print(f"   Searched in: {playwright_cache}")
        return 1

    chrome_path = chromium_paths[0]
    print(f"\n✓ Found browser executable: {chrome_path}")

    # Check if executable
    if not os.access(chrome_path, os.X_OK):
        print("❌ ERROR: Browser executable is not executable!")
        return 1

    print(f"✓ Browser executable is executable")

    # Check LD_LIBRARY_PATH
    ld_path = os.environ.get('LD_LIBRARY_PATH', 'not set')
    print(f"\nLD_LIBRARY_PATH: {ld_path}")

    # Use ldd to check dependencies
    print("\n" + "=" * 70)
    print("Checking browser dependencies with 'ldd'...")
    print("=" * 70)

    try:
        ldd_result = subprocess.run(
            ['ldd', chrome_path],
            capture_output=True,
            text=True,
            timeout=30
        )

        if ldd_result.returncode != 0:
            print(f"❌ ERROR: ldd failed: {ldd_result.stderr}")
            return 1

        lines = ldd_result.stdout.split('\n')
        found_libs = []
        missing_libs = []

        for line in lines:
            line = line.strip()
            if not line:
                continue

            if 'not found' in line.lower():
                missing_libs.append(line)
            elif '=>' in line or line.startswith('/'):
                found_libs.append(line)

        print(f"\n✓ Found {len(found_libs)} libraries")
        print(f"{'❌' if missing_libs else '✓'} Missing libraries: {len(missing_libs)}")

        if missing_libs:
            print("\n" + "=" * 70)
            print("MISSING LIBRARIES:")
            print("=" * 70)
            for lib in missing_libs:
                print(f"  ❌ {lib}")

            print("\n" + "=" * 70)
            print("SUGGESTED FIX:")
            print("=" * 70)
            print("Try installing missing packages. Common packages:")
            print("  apt-get update")
            print("  apt-get install -y libdrm2 libxshmfence1 libxcomposite1 libxdamage1")
            print("  ldconfig")
        else:
            print("\n✓ All libraries found!")

        # Show some found libraries
        if found_libs:
            print("\n" + "=" * 70)
            print("SAMPLE OF FOUND LIBRARIES (first 10):")
            print("=" * 70)
            for lib in found_libs[:10]:
                print(f"  ✓ {lib}")
            if len(found_libs) > 10:
                print(f"  ... and {len(found_libs) - 10} more")

    except subprocess.TimeoutExpired:
        print("❌ ERROR: ldd command timed out")
        return 1
    except Exception as e:
        print(f"❌ ERROR: {e}")
        return 1

    # Try to run browser with --version
    print("\n" + "=" * 70)
    print("Testing browser executable (--version)...")
    print("=" * 70)

    try:
        env = dict(os.environ)
        if ld_path != 'not set':
            env['LD_LIBRARY_PATH'] = ld_path

        version_result = subprocess.run(
            [chrome_path, '--version'],
            capture_output=True,
            text=True,
            timeout=10,
            env=env
        )

        if version_result.returncode == 0:
            print(f"✓ Browser can run!")
            print(f"  Version: {version_result.stdout.strip()}")
        else:
            print(f"❌ Browser --version failed!")
            print(f"  Return code: {version_result.returncode}")
            print(f"  stderr: {version_result.stderr[:500]}")
            if version_result.stdout:
                print(f"  stdout: {version_result.stdout[:500]}")
    except subprocess.TimeoutExpired:
        print("❌ Browser --version timed out (may indicate dependency issues)")
    except Exception as e:
        print(f"❌ Error running browser: {e}")

    # Check library cache
    print("\n" + "=" * 70)
    print("Checking library cache (ldconfig -p)...")
    print("=" * 70)

    try:
        ldconfig_result = subprocess.run(
            ['ldconfig', '-p'],
            capture_output=True,
            text=True,
            timeout=10
        )

        if ldconfig_result.returncode == 0:
            # Check for common libraries
            common_libs = [
                'libglib-2.0.so.0',
                'libdrm.so.2',
                'libxshmfence.so.1',
                'libxcomposite.so.1',
                'libxdamage.so.1',
                'libgbm.so.1',
                'libnss3.so',
            ]

            found_in_cache = []
            missing_in_cache = []

            for lib in common_libs:
                if lib in ldconfig_result.stdout:
                    found_in_cache.append(lib)
                else:
                    missing_in_cache.append(lib)

            print(f"✓ Found in cache: {len(found_in_cache)}/{len(common_libs)}")
            for lib in found_in_cache:
                print(f"  ✓ {lib}")

            if missing_in_cache:
                print(f"\n⚠ Missing from cache: {len(missing_in_cache)}")
                for lib in missing_in_cache:
                    print(f"  ⚠ {lib}")
        else:
            print(f"⚠ ldconfig failed: {ldconfig_result.stderr[:200]}")
    except Exception as e:
        print(f"⚠ Could not check library cache: {e}")

    print("\n" + "=" * 70)
    print("SUMMARY")
    print("=" * 70)

    if missing_libs:
        print("❌ Browser has missing dependencies - launch will likely fail")
        print("\nNext steps:")
        print("1. Install missing packages based on the library names above")
        print("2. Run: ldconfig")
        print("3. Re-run this script to verify")
        return 1
    else:
        print("✓ All browser dependencies appear to be available")
        print("\nIf browser still fails to launch, check:")
        print("- Permissions on browser executable")
        print("- Disk space")
        print("- Memory availability")
        return 0

if __name__ == '__main__':
    sys.exit(check_browser_dependencies())

