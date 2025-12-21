# Environment Variables Fix Summary

## Issues Fixed

### 1. **API URL Configuration**
- **Problem**: Worker was trying to connect to `http://app:8000` which doesn't resolve in Docker network
- **Fix**: All services now use `http://nginx` as the API URL
- **Files Changed**:
  - `docker-compose.yml` - scheduler and python-worker services
  - `python/worker.py` - Added fallback to `http://nginx` if invalid URL detected
  - `app/Console/Commands/RunAutomationWorker.php` - Already had correct fallback

### 2. **API Token Environment Variables**
- **Problem**: Inconsistent use of `APP_API_TOKEN` vs `LARAVEL_API_TOKEN`
- **Fix**: 
  - Added `LARAVEL_API_TOKEN` to scheduler environment (duplicates `APP_API_TOKEN`)
  - Updated `worker.py` to check both `LARAVEL_API_TOKEN` and `APP_API_TOKEN`
  - Updated supervisor config to pass both tokens
- **Files Changed**:
  - `docker-compose.yml` - Added `LARAVEL_API_TOKEN` to scheduler
  - `python/worker.py` - Now checks both env vars with fallback
  - `supervisor/python-worker.conf` - Added `APP_API_TOKEN` to environment

### 3. **Browser Dependencies**
- **Problem**: Missing browser dependencies causing Playwright launch failures
- **Fix**: 
  - Updated `install-browser-deps.sh` with all required dependencies
  - Updated `start-scheduler.sh` to call the install script
  - Added fallback manual installation if script not found
- **Files Changed**:
  - `docker/install-browser-deps.sh` - Added missing packages (libnspr4, libnss3, libvulkan1, etc.)
  - `docker/start-scheduler.sh` - Now calls install-browser-deps.sh script

## Environment Variables Summary

### Scheduler Container (`backlink-scheduler`)
```yaml
LARAVEL_API_URL: http://nginx
APP_API_TOKEN: ${APP_API_TOKEN:-${PYTHON_API_TOKEN:-your-secure-api-token-change-in-production}}
LARAVEL_API_TOKEN: ${APP_API_TOKEN:-${PYTHON_API_TOKEN:-your-secure-api-token-change-in-production}}
LD_LIBRARY_PATH: /usr/lib/x86_64-linux-gnu:/usr/lib:/lib/x86_64-linux-gnu:/lib
```

### Python Worker Container (`backlink-python-worker`)
```yaml
LARAVEL_API_URL: http://nginx
LARAVEL_API_TOKEN: ${PYTHON_API_TOKEN:-${APP_API_TOKEN:-your-secure-api-token-change-in-production}}
```

### Supervisor Configuration
```ini
environment=LARAVEL_API_URL="http://nginx",LARAVEL_API_TOKEN="%(ENV_LARAVEL_API_TOKEN)s",APP_API_TOKEN="%(ENV_APP_API_TOKEN)s",WORKER_ID="worker-%(process_num)02d",LD_LIBRARY_PATH="/usr/lib/x86_64-linux-gnu:/usr/lib:/lib/x86_64-linux-gnu:/lib"
```

## How Worker Gets Environment Variables

1. **From Docker Compose**: Environment variables are set in `docker-compose.yml`
2. **From Supervisor**: If running via supervisor, it passes env vars from container
3. **From RunAutomationWorker Command**: When started via `php artisan automation:run-worker`, it sets:
   - `LARAVEL_API_URL` (from env or defaults to `http://nginx`)
   - `LARAVEL_API_TOKEN` (from config or env)
   - `APP_API_TOKEN` (same as LARAVEL_API_TOKEN for compatibility)
   - `WORKER_ID` = `scheduler-worker`

4. **Worker.py Priority**:
   - Checks `LARAVEL_API_TOKEN` first
   - Falls back to `APP_API_TOKEN`
   - Falls back to module default (empty string, which will show error)

## Next Steps

1. **Rebuild containers** to apply environment changes:
   ```bash
   docker-compose build scheduler
   docker-compose up -d scheduler
   ```

2. **Verify environment variables** in running container:
   ```bash
   docker-compose exec scheduler env | grep -E "LARAVEL_API|APP_API"
   ```

3. **Test worker** manually:
   ```bash
   docker-compose exec scheduler php artisan automation:run-worker --limit=1
   ```

4. **Check logs** for any remaining issues:
   ```bash
   docker-compose logs -f scheduler
   ```

## Browser Dependencies Installed

The following packages are now installed in the scheduler container:
- libglib2.0-0, libglib2.0-bin
- libnspr4, libnss3
- libdrm2, libxshmfence1, libxcomposite1, libxdamage1
- libxfixes3, libxrandr2, libgbm1, libvulkan1
- libasound2, libatk1.0-0, libatk-bridge2.0-0, libatspi2.0-0
- libcups2, libdbus-1-3, libxkbcommon0, libxss1, libxtst6
- libgtk-3-0, libgdk-pixbuf2.0-0, libpango-1.0-0, libpangocairo-1.0-0
- libcairo2, libfontconfig1, libfreetype6
- libx11-6, libx11-xcb1, libxcb1, libxcb-dri3-0, libxcb-shm0
- libxcb-xfixes0, libxext6, libxrender1
- fonts-liberation, libu2f-udev

These should resolve the Playwright browser launch issues.
