# How to Run Jobs - Complete Guide

## Overview

This system has several types of jobs:
1. **Automation Tasks** - Python automation worker processes pending tasks
2. **Campaign Scheduling** - Creates new automation tasks for campaigns
3. **Proxy Health Checks** - Monitors proxy status

## Method 1: Run Automation Worker (Process Pending Tasks)

### Inside Docker Container (Recommended)

```bash
# Run once with default limit (5 tasks)
docker-compose exec scheduler php artisan automation:run-worker

# Run with custom limit (e.g., 10 tasks)
docker-compose exec scheduler php artisan automation:run-worker --limit=10

# Run with limit of 1 (for testing)
docker-compose exec scheduler php artisan automation:run-worker --limit=1
```

### Direct Python Worker (Alternative)

```bash
# Run Python worker directly in scheduler container
docker-compose exec scheduler python3 /var/www/html/python/worker.py --once --limit=5

# Or in python-worker container
docker-compose exec python-worker python worker.py --once --limit=5
```

## Method 2: Run Campaign Scheduling Job

This creates new automation tasks for active campaigns:

```bash
# Run campaign scheduling job
docker-compose exec scheduler php artisan queue:work --once

# Or trigger it directly
docker-compose exec app php artisan tinker
# Then in tinker:
>>> dispatch(new \App\Jobs\ScheduleCampaignJob);
```

## Method 3: Run Proxy Health Check

```bash
# Check only unhealthy proxies
docker-compose exec scheduler php artisan proxy:check-health --unhealthy

# Check all proxies
docker-compose exec scheduler php artisan proxy:check-health --all
```

## Method 4: Check Scheduled Jobs Status

```bash
# View all scheduled jobs
docker-compose exec scheduler php artisan schedule:list

# See what's scheduled to run
docker-compose exec scheduler php artisan schedule:test
```

## Method 5: Run Jobs via Queue Worker

```bash
# Process one job from queue
docker-compose exec queue php artisan queue:work --once

# Process multiple jobs (runs until stopped)
docker-compose exec queue php artisan queue:work
```

## Automatic Job Execution

Jobs run automatically via the scheduler:

- **Every Minute**: `automation:run-worker --limit=5` (processes 5 pending tasks)
- **Every Hour**: Campaign scheduling job (creates new tasks)
- **Every Hour**: Proxy health check (unhealthy proxies only)
- **Daily**: Full proxy health check (all proxies)

## Testing a Single Task

To test processing a single automation task:

```bash
# 1. Check if there are pending tasks
docker-compose exec app php artisan tinker
>>> \App\Models\AutomationTask::where('status', 'pending')->count();

# 2. Run worker with limit 1
docker-compose exec scheduler php artisan automation:run-worker --limit=1

# 3. Check the result
>>> \App\Models\AutomationTask::latest()->first();
```

## Viewing Job Logs

```bash
# View scheduler logs (shows automation worker output)
docker-compose logs -f scheduler

# View Python worker logs (if running separately)
docker-compose logs -f python-worker

# View queue worker logs
docker-compose logs -f queue

# View last 100 lines
docker-compose logs --tail=100 scheduler
```

## Troubleshooting

### Check if jobs are running automatically:

```bash
# Check scheduler is running
docker-compose ps scheduler

# Check scheduler logs for errors
docker-compose logs scheduler | tail -50
```

### Manually trigger a job if scheduler isn't working:

```bash
# Run automation worker manually
docker-compose exec scheduler php artisan automation:run-worker --limit=1

# Check for errors
docker-compose logs scheduler | grep -i error
```

### Check pending tasks:

```bash
docker-compose exec app php artisan tinker
>>> \App\Models\AutomationTask::where('status', 'pending')->get(['id', 'type', 'status', 'created_at']);
```

## Quick Reference

| Job Type | Command | Frequency |
|----------|---------|-----------|
| Automation Tasks | `automation:run-worker --limit=5` | Every minute (auto) |
| Campaign Scheduling | `ScheduleCampaignJob` | Every hour (auto) |
| Proxy Health Check | `proxy:check-health --unhealthy` | Every hour (auto) |
| Full Proxy Check | `proxy:check-health --all` | Daily (auto) |

## Example: Complete Test Run

```bash
# 1. Check pending tasks
docker-compose exec app php artisan tinker
>>> \App\Models\AutomationTask::where('status', 'pending')->count();

# 2. Run worker to process one task
docker-compose exec scheduler php artisan automation:run-worker --limit=1

# 3. Check logs for results
docker-compose logs --tail=50 scheduler

# 4. Verify task was processed
docker-compose exec app php artisan tinker
>>> \App\Models\AutomationTask::latest()->first();
```
