# Docker Scheduler Setup Guide

## Overview

The Laravel Scheduler service (`backlink-scheduler`) automatically runs scheduled tasks in Docker. This eliminates the need for manual cron job setup.

## What Gets Scheduled

### Every Minute
- **Automation Tasks Auto-Run**: Automatically processes pending automation tasks via `automation:run-worker` command
  - Fetches up to 5 pending tasks
  - Executes Python worker in single-pass mode
  - Processes tasks without manual intervention

### Hourly
- **Campaign Scheduling**: Creates new automation tasks for active campaigns
- **Proxy Health Checks**: Monitors unhealthy proxies

### Daily
- **Full Proxy Health Check**: Comprehensive proxy status check

## Docker Service Configuration

The scheduler service is defined in `docker-compose.yml`:

```yaml
scheduler:
  build:
    context: .
    dockerfile: Dockerfile
  container_name: backlink-scheduler
  working_dir: /var/www/html
  command: bash -c "chmod +x /var/www/html/docker/start-scheduler.sh 2>/dev/null || true && /var/www/html/docker/start-scheduler.sh"
  # ... environment variables
  restart: unless-stopped
```

## Starting the Scheduler

The scheduler starts automatically when you run:

```bash
docker-compose up -d
```

To start only the scheduler:

```bash
docker-compose up -d scheduler
```

## Monitoring

### View Scheduler Logs

```bash
# Follow logs in real-time
docker-compose logs -f scheduler

# View last 100 lines
docker-compose logs --tail=100 scheduler
```

### Check Scheduler Status

```bash
# Check if scheduler is running
docker-compose ps scheduler

# View scheduler container stats
docker stats backlink-scheduler
```

### Test Automation Worker Manually

```bash
# Run automation worker once (for testing)
docker-compose exec scheduler php artisan automation:run-worker

# Run with custom limit
docker-compose exec scheduler php artisan automation:run-worker --limit=10
```

## Environment Variables

The scheduler requires these environment variables:

- `APP_API_TOKEN`: API token for Python worker authentication
- `LARAVEL_API_URL`: Laravel API URL (default: `http://app:8000`)
- `PYTHON_BINARY`: Python binary path (default: `python3`)
- Database and Redis connection variables

Set these in your `.env` file or `docker-compose.yml`.

## Troubleshooting

### Scheduler Not Running

1. **Check if container is running:**
   ```bash
   docker-compose ps scheduler
   ```

2. **Check logs for errors:**
   ```bash
   docker-compose logs scheduler
   ```

3. **Restart scheduler:**
   ```bash
   docker-compose restart scheduler
   ```

### Automation Tasks Not Processing

1. **Check if Python worker can be executed:**
   ```bash
   docker-compose exec scheduler php artisan automation:run-worker --limit=1
   ```

2. **Verify API token is set:**
   ```bash
   docker-compose exec scheduler php artisan tinker
   # Then run: config('app.api_token')
   ```

3. **Check Python worker logs:**
   ```bash
   docker-compose logs python-worker
   ```

### Database Connection Issues

The scheduler waits up to 60 seconds for the database to be ready. If you see database connection errors:

1. **Check MySQL container:**
   ```bash
   docker-compose ps mysql
   ```

2. **Wait for MySQL to be ready:**
   ```bash
   docker-compose exec mysql mysqladmin ping -h localhost
   ```

3. **Restart scheduler after MySQL is ready:**
   ```bash
   docker-compose restart scheduler
   ```

## Manual Cron Alternative (Not Recommended)

If you prefer using system cron instead of the scheduler service, you can:

1. Remove the scheduler service from `docker-compose.yml`
2. Add a cron job on your host system:
   ```bash
   * * * * * cd /path-to-project && docker-compose exec -T scheduler php artisan schedule:run >> /dev/null 2>&1
   ```

However, using the Docker scheduler service is recommended as it:
- Runs automatically with Docker
- Handles container restarts
- Provides better logging
- Is easier to manage

## Schedule Configuration

Scheduled tasks are defined in `routes/console.php`:

```php
// Auto-run pending automation tasks every minute
Schedule::command('automation:run-worker --limit=5')->everyMinute();

// Schedule campaign job to run every hour
Schedule::job(new ScheduleCampaignJob)->hourly();

// Schedule proxy health checks
Schedule::command('proxy:check-health --unhealthy')->hourly();
Schedule::command('proxy:check-health --all')->daily();
```

To modify the schedule, edit `routes/console.php` and restart the scheduler:

```bash
docker-compose restart scheduler
```
