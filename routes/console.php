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
