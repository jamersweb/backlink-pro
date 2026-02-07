<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\ScheduleCampaignJob;
use App\Jobs\RefreshGoogleTokensJob;
use App\Jobs\PullGscDailyJob;
use App\Jobs\PullGa4DailyJob;
use App\Jobs\RunRankChecksJob;
use App\Jobs\DetectSeoAnomaliesJob;
use App\Jobs\GenerateMonthlyExecutiveReportJob;
use App\Jobs\CleanupOldSeoDataJob;
use App\Models\Organization;
use App\Models\RankProject;
use App\Services\Billing\PlanLimiter;

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

// Sync Google Search Console and GA4 data daily
Schedule::command('integrations:sync-google')->dailyAt('02:10');

// Generate domain insights daily
Schedule::command('insights:generate')->dailyAt('03:10');

// Roll subscription periods daily
Schedule::command('billing:roll-periods')->dailyAt('00:20');

// ============================================
// SEO Tracking Scheduled Jobs
// ============================================

// Hourly: Refresh Google OAuth tokens (only when near expiry)
Schedule::call(function () {
    RefreshGoogleTokensJob::dispatch();
})->hourly()
  ->name('refresh-google-tokens')
  ->withoutOverlapping(5);

// Daily: Pull GSC and GA4 data (early morning, org timezone aware)
Schedule::call(function () {
    $organizations = Organization::whereHas('oauthConnections', function ($query) {
        $query->where('provider', 'google')->where('status', 'active');
    })->get();

    foreach ($organizations as $org) {
        PullGscDailyJob::dispatch($org->id)->onQueue('integrations');
        PullGa4DailyJob::dispatch($org->id)->onQueue('integrations');
    }
})->dailyAt('02:00')
  ->name('pull-gsc-ga4-daily')
  ->withoutOverlapping(30);

// Daily: Run rank checks (respect plan frequency settings)
Schedule::call(function () {
    $planLimiter = app(PlanLimiter::class);
    
    // Get all active projects
    $projects = RankProject::where('status', RankProject::STATUS_ACTIVE)
        ->with('organization')
        ->get();

    foreach ($projects as $project) {
        $frequency = $planLimiter->rankCheckFrequency($project->organization);
        
        // Only run daily checks for projects with daily frequency
        if ($frequency === 'daily') {
            // Check if we should run today (skip if already checked today)
            $lastCheck = $project->keywords()
                ->whereHas('results', function ($query) {
                    $query->whereDate('fetched_at', today());
                })
                ->exists();

            if (!$lastCheck) {
                RunRankChecksJob::dispatch($project->id)->onQueue('rank');
            }
        }
    }
})->dailyAt('03:00')
  ->name('run-rank-checks-daily')
  ->withoutOverlapping(60);

// Weekly: Run rank checks for weekly frequency projects
Schedule::call(function () {
    $planLimiter = app(PlanLimiter::class);
    
    $projects = RankProject::where('status', RankProject::STATUS_ACTIVE)
        ->with('organization')
        ->get();

    foreach ($projects as $project) {
        $frequency = $planLimiter->rankCheckFrequency($project->organization);
        
        if ($frequency === 'weekly') {
            // Check if we should run this week (skip if already checked this week)
            $lastCheck = $project->keywords()
                ->whereHas('results', function ($query) {
                    $query->whereDate('fetched_at', '>=', now()->startOfWeek());
                })
                ->exists();

            if (!$lastCheck) {
                RunRankChecksJob::dispatch($project->id)->onQueue('rank');
            }
        }
    }
})->weeklyOn(1, '03:00')
  ->name('run-rank-checks-weekly')
  ->withoutOverlapping(60); // Monday at 3 AM

// Daily: Detect SEO anomalies
Schedule::call(function () {
    $organizations = Organization::whereHas('seoAlerts', function ($query) {
        $query->whereHas('rule', function ($q) {
            $q->where('is_enabled', true);
        });
    })->orWhereHas('gscSites')
      ->orWhereHas('ga4Properties')
      ->orWhereHas('rankProjects')
      ->get();

    foreach ($organizations as $org) {
        DetectSeoAnomaliesJob::dispatch($org->id)->onQueue('alerts');
    }
})->dailyAt('04:00')
  ->name('detect-seo-anomalies')
  ->withoutOverlapping(30);

// Monthly: Generate executive reports (1st day of month)
Schedule::call(function () {
    $organizations = Organization::whereHas('gscSites')
        ->orWhereHas('ga4Properties')
        ->orWhereHas('rankProjects')
        ->get();

    $planLimiter = app(PlanLimiter::class);
    $lastMonth = now()->subMonth();

    foreach ($organizations as $org) {
        if ($planLimiter->canGenerateMonthlyReport($org)) {
            GenerateMonthlyExecutiveReportJob::dispatch($org->id, $lastMonth->format('Y-m'))
                ->onQueue('reports');
        }
    }
})->monthlyOn(1, '05:00')
  ->name('generate-exec-reports')
  ->withoutOverlapping(120); // 1st of month at 5 AM

// Weekly: Cleanup old SEO data (respect plan retention limits)
Schedule::call(function () {
    CleanupOldSeoDataJob::dispatch();
})->weeklyOn(0, '06:00')
  ->name('cleanup-old-seo-data')
  ->withoutOverlapping(60); // Sunday at 6 AM
