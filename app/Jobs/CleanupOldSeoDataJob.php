<?php

namespace App\Jobs;

use App\Models\Organization;
use App\Models\GscDailyMetric;
use App\Models\GscQueryMetric;
use App\Models\GscPageMetric;
use App\Models\Ga4DailyMetric;
use App\Models\Ga4PageMetric;
use App\Services\Billing\PlanLimiter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CleanupOldSeoDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 1800; // 30 minutes
    public $tries = 1;
    public $queue = 'default';

    public function handle(): void
    {
        $planLimiter = app(PlanLimiter::class);
        $organizations = Organization::all();

        $totalDeleted = [
            'gsc_daily' => 0,
            'gsc_queries' => 0,
            'gsc_pages' => 0,
            'ga4_daily' => 0,
            'ga4_pages' => 0,
        ];

        foreach ($organizations as $org) {
            try {
                $retentionDays = $planLimiter->dataRetentionDays($org);
                $cutoffDate = Carbon::now()->subDays($retentionDays);

                // Cleanup GSC daily metrics
                $deleted = GscDailyMetric::where('organization_id', $org->id)
                    ->where('date', '<', $cutoffDate)
                    ->delete();
                $totalDeleted['gsc_daily'] += $deleted;

                // Cleanup GSC query metrics (keep only top N per day to control growth)
                // First, identify days older than retention
                $oldDates = GscQueryMetric::where('organization_id', $org->id)
                    ->where('date', '<', $cutoffDate)
                    ->distinct()
                    ->pluck('date');

                foreach ($oldDates as $date) {
                    // Keep only top 500 queries per day for historical data
                    $topQueries = GscQueryMetric::where('organization_id', $org->id)
                        ->where('date', $date)
                        ->orderByDesc('clicks')
                        ->limit(500)
                        ->pluck('id');

                    $deleted = GscQueryMetric::where('organization_id', $org->id)
                        ->where('date', $date)
                        ->whereNotIn('id', $topQueries)
                        ->delete();
                    $totalDeleted['gsc_queries'] += $deleted;
                }

                // Cleanup GSC page metrics (keep only top N per day)
                $oldPageDates = GscPageMetric::where('organization_id', $org->id)
                    ->where('date', '<', $cutoffDate)
                    ->distinct()
                    ->pluck('date');

                foreach ($oldPageDates as $date) {
                    $topPages = GscPageMetric::where('organization_id', $org->id)
                        ->where('date', $date)
                        ->orderByDesc('clicks')
                        ->limit(500)
                        ->pluck('id');

                    $deleted = GscPageMetric::where('organization_id', $org->id)
                        ->where('date', $date)
                        ->whereNotIn('id', $topPages)
                        ->delete();
                    $totalDeleted['gsc_pages'] += $deleted;
                }

                // Cleanup GA4 daily metrics
                $deleted = Ga4DailyMetric::where('organization_id', $org->id)
                    ->where('date', '<', $cutoffDate)
                    ->delete();
                $totalDeleted['ga4_daily'] += $deleted;

                // Cleanup GA4 page metrics (keep only top N per day)
                $oldGa4Dates = Ga4PageMetric::where('organization_id', $org->id)
                    ->where('date', '<', $cutoffDate)
                    ->distinct()
                    ->pluck('date');

                foreach ($oldGa4Dates as $date) {
                    $topPages = Ga4PageMetric::where('organization_id', $org->id)
                        ->where('date', $date)
                        ->orderByDesc('views')
                        ->limit(500)
                        ->pluck('id');

                    $deleted = Ga4PageMetric::where('organization_id', $org->id)
                        ->where('date', $date)
                        ->whereNotIn('id', $topPages)
                        ->delete();
                    $totalDeleted['ga4_pages'] += $deleted;
                }

            } catch (\Exception $e) {
                Log::error("Failed to cleanup SEO data for organization", [
                    'organization_id' => $org->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info("SEO data cleanup completed", $totalDeleted);
    }
}
