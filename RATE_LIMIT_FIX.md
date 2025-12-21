# Rate Limit Fix Summary

## Problem

The worker was hitting API rate limits (429 errors) because:
1. **Scheduler runs every minute** - making 60+ requests/hour just for polling
2. **Each task makes multiple API calls**:
   - Get pending tasks (1 call)
   - Lock task (1 call)
   - Update status (1-2 calls)
   - Unlock task (1 call)
   - Create backlink (1 call)
   - Get campaign/proxy info (2 calls)
   - **Total: ~7-8 calls per task**

3. **Processing 5 tasks per minute** = 35-40 API calls/minute = **2100-2400 calls/hour**
4. **Rate limit is 300 requests/hour** - way too low for this workload

## Solutions Applied

### 1. Reduced Scheduler Frequency
- **Changed from**: Every minute
- **Changed to**: Every 2 minutes
- **Result**: Reduces polling requests from 60/hour to 30/hour

### 2. Improved Rate Limit Handling
- Better extraction of `retry_after` from API response
- Added 10-second buffer after waiting for rate limit reset
- Better logging to show wait times

### 3. Increased Overlap Protection
- Increased timeout from 5 minutes to 10 minutes
- Prevents multiple workers from running simultaneously

## Recommended Additional Fixes

### Option 1: Increase Rate Limit (Recommended)
The rate limit is configurable in Admin Settings:
1. Go to Admin → Settings → API Settings
2. Increase "API Rate Limit" from 300 to **1000-2000 requests/hour**
3. This allows processing more tasks without hitting limits

### Option 2: Process Fewer Tasks Per Run
Change the scheduler command to process fewer tasks:
```php
Schedule::command('automation:run-worker --limit=3')
    ->everyTwoMinutes()
```

### Option 3: Increase Poll Interval
If running a continuous worker, increase the poll interval:
```bash
python worker.py --poll-interval=30  # 30 seconds = 120 requests/hour
```

## Current Configuration

- **Scheduler**: Runs every 2 minutes
- **Tasks per run**: 5
- **Rate limit**: 300 requests/hour (configurable)
- **Overlap protection**: 10 minutes

## Monitoring

To check if rate limits are still being hit:
```bash
# Check scheduler logs
docker-compose logs scheduler | grep -i "rate limit"

# Check for 429 errors
docker-compose logs scheduler | grep "429"
```

## Expected Behavior After Fix

- Scheduler runs every 2 minutes instead of every minute
- Worker waits properly when rate limit is hit
- Better error messages showing wait times
- Reduced chance of hitting rate limits

## If Still Hitting Rate Limits

1. **Increase rate limit** in Admin Settings (easiest fix)
2. **Reduce tasks per run** from 5 to 3
3. **Increase scheduler interval** from 2 minutes to 3-5 minutes
4. **Check for multiple workers** running simultaneously
