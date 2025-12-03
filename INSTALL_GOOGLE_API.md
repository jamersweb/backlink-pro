# Installing Google API Client - Special Instructions

The `google/apiclient-services` package is extremely large (400+ MB) and can timeout during installation.

## Solution 1: Install with Maximum Timeout (Recommended)

```bash
# Set very long timeout and install
docker-compose exec -e COMPOSER_PROCESS_TIMEOUT=3600 app composer require google/apiclient --no-scripts --prefer-dist

# Then run scripts separately
docker-compose exec app composer dump-autoload
```

## Solution 2: Install Without Dev Dependencies First

```bash
# Install production dependencies only (faster)
docker-compose exec -e COMPOSER_PROCESS_TIMEOUT=3600 app composer install --no-dev --prefer-dist --no-scripts

# Then install dev dependencies
docker-compose exec app composer install --prefer-dist
```

## Solution 3: Increase PHP Memory Limit

```bash
# Install with increased memory limit
docker-compose exec -e COMPOSER_MEMORY_LIMIT=-1 -e COMPOSER_PROCESS_TIMEOUT=3600 app composer require google/apiclient --prefer-dist --no-scripts
```

## Solution 4: Manual Installation (If Above Fails)

If all else fails, you can temporarily remove google/apiclient from composer.json, install other packages, then add it back:

1. **Remove from composer.json temporarily:**
   ```json
   // Remove this line:
   "google/apiclient": "^2.15",
   ```

2. **Install other packages:**
   ```bash
   docker-compose exec app composer update
   ```

3. **Add Google API back and install separately:**
   ```bash
   docker-compose exec -e COMPOSER_PROCESS_TIMEOUT=3600 -e COMPOSER_MEMORY_LIMIT=-1 app composer require google/apiclient --prefer-dist --no-scripts --ignore-platform-reqs
   ```

## Why This Happens

The `google/apiclient-services` package contains API definitions for ALL Google services (Gmail, Drive, Calendar, etc.) which makes it extremely large. The unzip process can take 30+ minutes on slower systems.

## Alternative: Use Minimal Google API Package

If you only need Gmail API, consider using a lighter package:
- `google/apiclient` (core) + `google/apiclient-services` (only Gmail services)

But this requires manual configuration.

---

**Try Solution 1 first - it should work with the increased timeout.**

