#!/bin/bash
set -e

# Wait for composer dependencies to be available (max 60 seconds)
echo "Waiting for composer dependencies..."
counter=0
while [ ! -f "/var/www/html/vendor/autoload.php" ] && [ $counter -lt 30 ]; do
    echo "Waiting for vendor/autoload.php... ($counter/30)"
    sleep 2
    counter=$((counter + 1))
done

if [ ! -f "/var/www/html/vendor/autoload.php" ]; then
    echo "Installing composer dependencies..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# Wait for database to be ready
echo "Waiting for database connection..."
counter=0
while ! php artisan db:show 2>/dev/null && [ $counter -lt 30 ]; do
    echo "Waiting for database... ($counter/30)"
    sleep 2
    counter=$((counter + 1))
done

# Run package discovery to register commands
echo "Running package discovery..."
php artisan package:discover --ansi || true

# Ensure Python dependencies are installed (for automation worker)
if [ -f "/var/www/html/python/requirements.txt" ]; then
    echo "Checking Python dependencies..."
    if ! python3 -c "import dotenv" 2>/dev/null; then
        echo "Installing Python dependencies..."
        pip3 install --break-system-packages --no-cache-dir -r /var/www/html/python/requirements.txt || true
    fi

    # Always ensure critical system libraries are installed
    echo "Ensuring system libraries are installed..."
    apt-get update -qq > /dev/null 2>&1 || true

    # Force install libglib2.0-0 (even if already installed, this ensures it's present)
    echo "Installing/verifying libglib2.0-0..."
    if ! apt-get install -y --no-install-recommends libglib2.0-0 libglib2.0-bin 2>&1; then
        echo "WARNING: Failed to install libglib2.0-0 via apt-get"
    fi

    # Update library cache - this is critical for dynamic linking
    echo "Updating library cache..."
    if ! ldconfig 2>&1; then
        echo "WARNING: ldconfig had issues"
    fi

    # Verify library is accessible - try multiple methods
    LIB_FOUND=0

    # Method 1: Check library cache
    if ldconfig -p 2>/dev/null | grep -q "libglib-2.0.so.0"; then
        echo "✓ libglib-2.0.so.0 found in library cache"
        LIB_FOUND=1
    fi

    # Method 2: Find library file directly
    if [ $LIB_FOUND -eq 0 ]; then
        LIB_PATH=$(find /usr/lib* /lib* -name "libglib-2.0.so.0" 2>/dev/null | head -1)
        if [ -n "$LIB_PATH" ] && [ -f "$LIB_PATH" ]; then
            echo "✓ Found library file at: $LIB_PATH"
            # Ensure the directory is in ldconfig paths
            LIB_DIR=$(dirname "$LIB_PATH")
            if [ -d "$LIB_DIR" ]; then
                # Add to ldconfig configuration
                echo "$LIB_DIR" > /etc/ld.so.conf.d/glib.conf 2>/dev/null || true
                # Update cache again
                ldconfig 2>&1 || true
                # Verify it's now in cache
                if ldconfig -p 2>/dev/null | grep -q "libglib-2.0.so.0"; then
                    echo "✓ libglib-2.0.so.0 now available in library cache"
                    LIB_FOUND=1
                fi
            fi
        fi
    fi

    # Method 3: Try to verify by checking package files
    if [ $LIB_FOUND -eq 0 ]; then
        echo "Checking package installation..."
        if dpkg -l | grep -q "^ii.*libglib2.0-0"; then
            echo "Package is installed, checking files..."
            LIB_FILES=$(dpkg -L libglib2.0-0 2>/dev/null | grep "libglib-2.0.so.0" | head -1)
            if [ -n "$LIB_FILES" ] && [ -f "$LIB_FILES" ]; then
                echo "✓ Found library via package: $LIB_FILES"
                LIB_DIR=$(dirname "$LIB_FILES")
                echo "$LIB_DIR" > /etc/ld.so.conf.d/glib.conf 2>/dev/null || true
                ldconfig 2>&1 || true
                LIB_FOUND=1
            fi
        fi
    fi

    if [ $LIB_FOUND -eq 0 ]; then
        echo "ERROR: libglib-2.0.so.0 not found after installation attempts!"
        echo "This will cause browser launch failures."
        echo "Attempting emergency installation..."
        # Try one more time with verbose output
        apt-get install -y libglib2.0-0 libglib2.0-bin 2>&1 | tail -20
        ldconfig
        # Final check
        if ldconfig -p 2>/dev/null | grep -q "libglib-2.0.so.0"; then
            echo "✓ Emergency installation successful"
        else
            echo "✗ Emergency installation failed - browser will not work"
        fi
    fi

    # Always check and install Playwright browsers if needed
    echo "Checking Playwright browsers..."
    # Check if browsers are installed by looking for the cache directory
    # Don't try to start Playwright as it might fail if there's an asyncio loop
    CHROMIUM_CACHE="/root/.cache/ms-playwright/chromium-1140"
    if [ ! -d "$CHROMIUM_CACHE" ] || [ ! -f "$CHROMIUM_CACHE/chrome-linux/chrome" ]; then
        echo "Playwright browsers not found. Installing (this may take a few minutes)..."
        # First ensure system dependencies are installed
        echo "Installing Playwright system dependencies..."
        python3 -m playwright install-deps chromium 2>&1 || {
            echo "Warning: Playwright install-deps had issues, but continuing..."
        }
        # Install browsers (dependencies should be installed now)
        python3 -m playwright install chromium 2>&1 || true
        # Verify browsers were actually installed (they may install despite validation warnings)
        if [ -f "$CHROMIUM_CACHE/chrome-linux/chrome" ]; then
            echo "Playwright browsers installed successfully"
        else
            echo "ERROR: Playwright browser installation failed - browsers not found after installation!"
            echo "This will cause automation tasks to fail."
            exit 1
        fi
    else
        echo "Playwright browsers already installed"
        # Still check and install dependencies in case they're missing
        echo "Verifying Playwright system dependencies..."
        python3 -m playwright install-deps chromium 2>&1 || {
            echo "Warning: Some dependencies may be missing, but browsers are installed"
        }
    fi
fi

# Set LD_LIBRARY_PATH if not already set (helps with library loading)
if [ -z "$LD_LIBRARY_PATH" ]; then
    export LD_LIBRARY_PATH="/usr/lib/x86_64-linux-gnu:/usr/lib:/lib/x86_64-linux-gnu:/lib"
    echo "Set LD_LIBRARY_PATH=$LD_LIBRARY_PATH"
fi

# Start Laravel Scheduler
echo "Starting Laravel Scheduler..."
echo "This will run scheduled tasks every minute (schedule:work)"
exec php artisan schedule:work
