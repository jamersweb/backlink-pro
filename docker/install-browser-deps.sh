#!/bin/bash
# Install common browser dependencies for Chromium/Playwright

set -e

echo "=========================================="
echo "Installing Browser Dependencies"
echo "=========================================="

# Update package list
echo "Updating package list..."
apt-get update -qq

# Install common Chromium dependencies
echo "Installing browser dependencies..."
apt-get install -y --no-install-recommends \
    libglib2.0-0 \
    libglib2.0-bin \
    libnspr4 \
    libnss3 \
    libdrm2 \
    libxshmfence1 \
    libxcomposite1 \
    libxdamage1 \
    libxfixes3 \
    libxrandr2 \
    libgbm1 \
    libvulkan1 \
    libasound2 \
    libatk1.0-0 \
    libatk-bridge2.0-0 \
    libatspi2.0-0 \
    libcups2 \
    libdbus-1-3 \
    libdrm2 \
    libxkbcommon0 \
    libxss1 \
    libxtst6 \
    libgtk-3-0 \
    libgdk-pixbuf2.0-0 \
    libpango-1.0-0 \
    libpangocairo-1.0-0 \
    libcairo2 \
    libfontconfig1 \
    libfreetype6 \
    libx11-6 \
    libx11-xcb1 \
    libxcb1 \
    libxcb-dri3-0 \
    libxcb-shm0 \
    libxcb-xfixes0 \
    libxext6 \
    libxrender1 \
    fonts-liberation \
    libu2f-udev

# Update library cache
echo "Updating library cache..."
ldconfig

echo ""
echo "=========================================="
echo "Verification"
echo "=========================================="

# Check if libglib is available
if ldconfig -p | grep -q libglib-2.0.so.0; then
    echo "✓ libglib-2.0.so.0 found"
else
    echo "✗ libglib-2.0.so.0 NOT found"
fi

# Check browser executable
CHROME=$(find /root/.cache/ms-playwright -name chrome -type f 2>/dev/null | head -1)
if [ -n "$CHROME" ]; then
    echo "✓ Browser executable found: $CHROME"

    # Check for missing dependencies
    MISSING=$(ldd "$CHROME" 2>&1 | grep -c "not found" || true)
    if [ "$MISSING" -eq 0 ]; then
        echo "✓ All browser dependencies found"
    else
        echo "⚠ Still missing $MISSING dependencies:"
        ldd "$CHROME" 2>&1 | grep "not found"
    fi
else
    echo "⚠ Browser executable not found (may need to install Playwright browsers)"
fi

echo ""
echo "=========================================="
echo "Done!"
echo "=========================================="
