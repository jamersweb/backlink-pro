<?php

namespace App\Jobs\Integrations;

use App\Models\Domain;
use App\Models\DomainGoogleIntegration;
use App\Models\Ga4DailyMetric;
use App\Models\Ga4LandingPage;
use App\Services\Google\Ga4Service;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncGa4DomainJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes
    public $tries = 2;
    public $queue = 'integrations';

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $domainId,
        public int $days = 90
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $domain = Domain::findOrFail($this->domainId);
        $integration = $domain->googleIntegration;

        if (!$integration || !$integration->ga4_property_id) {
            Log::info('GA4 sync skipped: no property configured', ['domain_id' => $this->domainId]);
            return;
        }

        if (!$integration->connectedAccount || !$integration->connectedAccount->isActive()) {
            Log::warning('GA4 sync skipped: account not active', ['domain_id' => $this->domainId]);
            return;
        }

        try {
            $service = new Ga4Service($integration->connectedAccount);
            $endDate = now();
            $startDate = now()->subDays($this->days);

            // Fetch daily metrics
            $dailyMetrics = $service->runDailyReport(
                $integration->ga4_property_id,
                $startDate,
                $endDate
            );

            // Upsert daily metrics
            foreach ($dailyMetrics as $metric) {
                Ga4DailyMetric::updateOrCreate(
                    [
                        'domain_id' => $domain->id,
                        'date' => $metric['date'],
                    ],
                    [
                        'sessions' => $metric['sessions'],
                        'total_users' => $metric['total_users'],
                        'engaged_sessions' => $metric['engaged_sessions'],
                        'engagement_rate' => $metric['engagement_rate'],
                    ]
                );
            }

            // Fetch and store landing pages (snapshot)
            $landingPages = $service->runLandingPagesReport(
                $integration->ga4_property_id,
                $startDate,
                $endDate,
                250
            );

            $snapshotDate = now()->toDateString();
            Ga4LandingPage::where('domain_id', $domain->id)
                ->where('date', $snapshotDate)
                ->delete();

            foreach ($landingPages as $page) {
                Ga4LandingPage::create([
                    'domain_id' => $domain->id,
                    'date' => $snapshotDate,
                    'landing_page' => $page['landing_page'],
                    'sessions' => $page['sessions'],
                    'total_users' => $page['total_users'],
                ]);
            }

            // Update last synced timestamp
            $integration->update(['last_synced_at' => now()]);

            Log::info('GA4 sync completed', [
                'domain_id' => $domain->id,
                'metrics_count' => count($dailyMetrics),
                'pages_count' => count($landingPages),
            ]);
        } catch (\Exception $e) {
            Log::error('GA4 sync failed', [
                'domain_id' => $domain->id,
                'error' => $e->getMessage(),
            ]);

            $integration->update([
                'status' => DomainGoogleIntegration::STATUS_ERROR,
                'last_sync_error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
