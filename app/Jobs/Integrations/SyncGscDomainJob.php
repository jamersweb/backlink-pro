<?php

namespace App\Jobs\Integrations;

use App\Models\Domain;
use App\Models\DomainGoogleIntegration;
use App\Models\GscDailyMetric;
use App\Models\GscTopPage;
use App\Models\GscTopQuery;
use App\Services\Google\SearchConsoleService;
use App\Services\System\ActivityLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncGscDomainJob implements ShouldQueue
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

        if (!$integration || !$integration->gsc_property) {
            Log::info('GSC sync skipped: no property configured', ['domain_id' => $this->domainId]);
            return;
        }

        if (!$integration->connectedAccount || !$integration->connectedAccount->isActive()) {
            Log::warning('GSC sync skipped: account not active', ['domain_id' => $this->domainId]);
            return;
        }

        try {
            $service = new SearchConsoleService($integration->connectedAccount);
            $endDate = now();
            $startDate = now()->subDays($this->days);

            // Fetch daily metrics
            $dailyMetrics = $service->fetchDailyMetrics(
                $integration->gsc_property,
                $startDate,
                $endDate
            );

            // Upsert daily metrics
            foreach ($dailyMetrics as $metric) {
                GscDailyMetric::updateOrCreate(
                    [
                        'domain_id' => $domain->id,
                        'date' => $metric['date'],
                    ],
                    [
                        'clicks' => $metric['clicks'],
                        'impressions' => $metric['impressions'],
                        'ctr' => $metric['ctr'],
                        'position' => $metric['position'],
                    ]
                );
            }

            // Fetch and store top pages (snapshot - delete old then insert)
            $topPages = $service->fetchTopPages(
                $integration->gsc_property,
                $startDate,
                $endDate,
                250
            );

            $snapshotDate = now()->toDateString();
            GscTopPage::where('domain_id', $domain->id)
                ->where('date', $snapshotDate)
                ->delete();

            foreach ($topPages as $page) {
                GscTopPage::create([
                    'domain_id' => $domain->id,
                    'date' => $snapshotDate,
                    'page' => $page['page'],
                    'clicks' => $page['clicks'],
                    'impressions' => $page['impressions'],
                    'ctr' => $page['ctr'],
                    'position' => $page['position'],
                ]);
            }

            // Fetch and store top queries (snapshot)
            $topQueries = $service->fetchTopQueries(
                $integration->gsc_property,
                $startDate,
                $endDate,
                250
            );

            GscTopQuery::where('domain_id', $domain->id)
                ->where('date', $snapshotDate)
                ->delete();

            foreach ($topQueries as $query) {
                GscTopQuery::create([
                    'domain_id' => $domain->id,
                    'date' => $snapshotDate,
                    'query' => $query['query'],
                    'clicks' => $query['clicks'],
                    'impressions' => $query['impressions'],
                    'ctr' => $query['ctr'],
                    'position' => $query['position'],
                ]);
            }

            // Update last synced timestamp
            $integration->update(['last_synced_at' => now()]);

            // Log success
            $logger = app(ActivityLogger::class);
            $logger->success(
                'google',
                'sync_completed',
                "GSC sync completed: {$dailyMetrics} metrics, {$topPages} pages, {$topQueries} queries",
                $domain->user_id,
                $domain->id,
                ['metrics_count' => count($dailyMetrics), 'pages_count' => count($topPages), 'queries_count' => count($topQueries)]
            );

            Log::info('GSC sync completed', [
                'domain_id' => $domain->id,
                'metrics_count' => count($dailyMetrics),
                'pages_count' => count($topPages),
                'queries_count' => count($topQueries),
            ]);
        } catch (\Exception $e) {
            $logger = app(ActivityLogger::class);
            $logger->logJobFailure(
                'google',
                'SyncGscDomainJob',
                $e,
                $domain->id,
                $domain->user_id,
                null,
                ['domain_id' => $domain->id, 'property' => $integration->gsc_property]
            );

            Log::error('GSC sync failed', [
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
