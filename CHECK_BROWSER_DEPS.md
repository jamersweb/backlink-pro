# How to Check Browser Library Dependencies

## Quick Check Commands

Run these commands inside the Docker container to check browser dependencies:

### 1. Check Browser Executable Location
```bash
docker-compose exec scheduler find /root/.cache/ms-playwright -name chrome -type f
```

### 2. Check Browser Dependencies with ldd
```bash
docker-compose exec scheduler bash -c "ldd /root/.cache/ms-playwright/chromium-*/chrome-linux/chrome | grep 'not found'"
```

### 3. Test Browser Executable Directly
```bash
docker-compose exec scheduler bash -c "/root/.cache/ms-playwright/chromium-*/chrome-linux/chrome --version"
```

### 4. Check Library Cache
```bash
docker-compose exec scheduler ldconfig -p | grep -E "libglib|libdrm|libxshmfence|libxcomposite"
```

### 5. Run the Diagnostic Script
```bash
docker-compose exec scheduler python3 /var/www/html/python/check_browser_deps.py
```

## Manual Library Check

### Check for specific missing libraries:
```bash
# Enter the container
docker-compose exec scheduler bash

# Find browser executable
CHROME=$(find /root/.cache/ms-playwright -name chrome -type f | head -1)
echo "Browser: $CHROME"

# Check dependencies
ldd "$CHROME" | grep "not found"

# Check library cache
ldconfig -p | grep libglib-2.0.so.0

# Test browser
LD_LIBRARY_PATH=/usr/lib/x86_64-linux-gnu:/usr/lib:/lib/x86_64-linux-gnu:/lib "$CHROME" --version
```

## Common Missing Libraries and Fixes

If you see libraries marked as "not found":

1. **libdrm.so.2** → Install: `apt-get install -y libdrm2`
2. **libxshmfence.so.1** → Install: `apt-get install -y libxshmfence1`
3. **libxcomposite.so.1** → Install: `apt-get install -y libxcomposite1`
4. **libxdamage.so.1** → Install: `apt-get install -y libxdamage1`
5. **libgbm.so.1** → Install: `apt-get install -y libgbm1`

After installing, run:
```bash
ldconfig
```

## Full Dependency Check Script

The script `python/check_browser_deps.py` will:
- Find the browser executable
- Check all dependencies with `ldd`
- List missing libraries
- Test if browser can run with `--version`
- Check library cache
- Provide suggestions for fixes

Run it with:
```bash
docker-compose exec scheduler python3 /var/www/html/python/check_browser_deps.py
```








