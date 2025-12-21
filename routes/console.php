<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\ScheduleCampaignJob;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule campaign job to run every hour
Schedule::job(new ScheduleCampaignJob)->hourly();

// Schedule proxy health checks
Schedule::command('proxy:check-health --unhealthy')->hourly();
Schedule::command('proxy:check-health --all')->daily();

// Auto-run pending automation tasks every 2 minutes (short single-pass)
// Reduced frequency to respect API rate limits (300 requests/hour)
// Each task makes multiple API calls, so running every minute would exceed the limit
// Prevent overlapping executions - if a task is still running, skip the next scheduled run
Schedule::command('automation:run-worker --limit=5')
    ->everyTwoMinutes()
    ->withoutOverlapping(10); // 10 minute timeout to prevent stuck processes
