<?php

namespace App\Jobs\Backlinks;

use App\Models\Domain;
use App\Models\DomainBacklinkRun;
use App\Models\DomainBacklink;
use App\Models\BacklinkRefDomain;
use App\Models\DomainRefDomain;
use App\Models\DomainAnchorSummary;
use App\Models\DomainBacklinkDelta;
use App\Services\Backlinks\BacklinkProviderFactory;
use App\Services\Backlinks\BacklinkScoringEngine;
use App\Services\Backlinks\RiskHeuristics;
use App\Services\System\ActivityLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StartBacklinkRunJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes
    public $tries = 2;
    public $queue = 'backlinks';

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $runId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $run = DomainBacklinkRun::findOrFail($this->runId);
        $domain = $run->domain;

        // Mark as running
        $run->update([
            'status' => DomainBacklinkRun::STATUS_RUNNING,
            'started_at' => now(),
        ]);

        // Log started
        $logger = app(ActivityLogger::class);
        $logger->info(
            'backlinks',
            'started',
            "Backlink run started for domain {$domain->host}",
            $run->user_id,
            $run->domain_id,
            ['run_id' => $this->runId, 'provider' => $run->provider]
        );

        try {
            $provider = BacklinkProviderFactory::make();
            $host = $domain->host ?? parse_url($domain->url, PHP_URL_HOST);
            
            if (!$host) {
                throw new \Exception('Domain host is required');
            }

            $settings = $run->settings_json ?? [];
            $limitBacklinks = $settings['limit_backlinks'] ?? 1000;
            $limitRefDomains = $settings['limit_ref_domains'] ?? 500;
            $limitAnchors = $settings['limit_anchors'] ?? 200;

            // Fetch summary
            $summary = $provider->fetchSummary($host);
            $run->update(['totals_json' => $summary]);

            // Fetch and store backlinks (with pagination)
            $this->fetchAndStoreBacklinks($run, $provider, $host, $limitBacklinks);

            // Fetch and store referring domains
            $this->fetchAndStoreRefDomains($run, $provider, $host, $limitRefDomains);

            // Fetch and store anchors
            $this->fetchAndStoreAnchors($run, $provider, $host, $limitAnchors);

            // Compute quality/toxicity scores and ref domain aggregates (Feature 19)
            $this->processQualityScoring($run, $domain);

            // Compute risk heuristics
            $riskScore = RiskHeuristics::computeRunRiskScore($run);

            // Compute deltas vs previous run
            $delta = $this->computeDeltas($run, $domain);

            // Build summary
            $summaryJson = [
                'total_backlinks' => $run->backlinks()->count(),
                'ref_domains' => $run->refDomains()->count(),
                'follow' => $run->backlinks()->where('rel', 'follow')->count(),
                'nofollow' => $run->backlinks()->where('rel', 'nofollow')->count(),
                'anchors_total' => $run->anchorSummaries()->sum('count'),
                'risk_score' => $riskScore,
                'new_links' => $delta->new_links ?? 0,
                'lost_links' => $delta->lost_links ?? 0,
                'new_ref_domains' => $delta->new_ref_domains ?? 0,
                'lost_ref_domains' => $delta->lost_ref_domains ?? 0,
            ];

            // Update run
            $run->update([
                'status' => DomainBacklinkRun::STATUS_COMPLETED,
                'finished_at' => now(),
                'summary_json' => $summaryJson,
            ]);

            // Consume links fetched quota
            $totalLinks = $summaryJson['total_backlinks'];
            $quotaService = app(\App\Services\Usage\QuotaService::class);
            $quotaService->consume($run->user, 'backlinks.links_fetched_per_month', $totalLinks, 'month', [
                'run_id' => $run->id,
                'domain_id' => $domain->id,
            ]);

            // Log completion
            $logger->success(
                'backlinks',
                'completed',
                "Backlink run completed: {$summaryJson['total_backlinks']} backlinks found",
                $run->user_id,
                $run->domain_id,
                ['run_id' => $run->id, 'total_backlinks' => $summaryJson['total_backlinks']]
            );

            Log::info('Backlink run completed', [
                'run_id' => $run->id,
                'domain_id' => $domain->id,
                'backlinks_count' => $summaryJson['total_backlinks'],
            ]);
        } catch (\Exception $e) {
            $logger = app(ActivityLogger::class);
            $logger->logJobFailure(
                'backlinks',
                'StartBacklinkRunJob',
                $e,
                $run->domain_id,
                $run->user_id,
                ActivityLogger::runRef('backlinks', $this->runId),
                ['run_id' => $this->runId, 'provider' => $run->provider]
            );

            Log::error('Backlink run failed', [
                'run_id' => $this->runId,
                'error' => $e->getMessage(),
            ]);

            $run->update([
                'status' => DomainBacklinkRun::STATUS_FAILED,
                'error_message' => $e->getMessage(),
                'finished_at' => now(),
            ]);

            throw $e;
        }
    }

    /**
     * Fetch and store backlinks
     */
    protected function fetchAndStoreBacklinks(DomainBacklinkRun $run, $provider, string $host, int $limit): void
    {
        $offset = 0;
        $chunkSize = 500;
        $totalFetched = 0;

        while ($totalFetched < $limit) {
            $result = $provider->fetchBacklinks($host, min($chunkSize, $limit - $totalFetched), $offset);
            $items = $result['items'] ?? [];

            if (empty($items)) {
                break;
            }

            // Store in chunks
            $chunks = array_chunk($items, 100);
            foreach ($chunks as $chunk) {
                $data = [];
                foreach ($chunk as $item) {
                    $fingerprint = DomainBacklink::generateFingerprint(
                        $item['source_url'],
                        $item['target_url'],
                        $item['rel'],
                        $item['anchor'] ?? null
                    );

                    // Compute risk flags
                    $riskFlags = RiskHeuristics::computeLinkRiskFlags($item);

                    $data[] = [
                        'run_id' => $run->id,
                        'fingerprint' => $fingerprint,
                        'source_url' => $item['source_url'],
                        'source_domain' => $item['source_domain'],
                        'target_url' => $item['target_url'],
                        'anchor' => $item['anchor'] ?? null,
                        'rel' => $item['rel'],
                        'first_seen' => $item['first_seen'] ?? null,
                        'last_seen' => $item['last_seen'] ?? null,
                        'tld' => $item['tld'] ?? null,
                        'country' => $item['country'] ?? null,
                        'risk_flags_json' => $riskFlags,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                // Bulk insert (ignore duplicates)
                DB::table('domain_backlinks')->insertOrIgnore($data);
            }

            $totalFetched += count($items);
            $offset += count($items);

            if (count($items) < $chunkSize) {
                break; // No more data
            }
        }
    }

    /**
     * Fetch and store referring domains
     */
    protected function fetchAndStoreRefDomains(DomainBacklinkRun $run, $provider, string $host, int $limit): void
    {
        $offset = 0;
        $chunkSize = 500;
        $totalFetched = 0;
        $totalBacklinks = $run->backlinks()->count();

        while ($totalFetched < $limit) {
            $result = $provider->fetchRefDomains($host, min($chunkSize, $limit - $totalFetched), $offset);
            $items = $result['items'] ?? [];

            if (empty($items)) {
                break;
            }

            $data = [];
            foreach ($items as $item) {
                $data[] = [
                    'run_id' => $run->id,
                    'domain' => $item['domain'],
                    'backlinks_count' => $item['backlinks_count'],
                    'first_seen' => $item['first_seen'] ?? null,
                    'last_seen' => $item['last_seen'] ?? null,
                    'tld' => $item['tld'] ?? null,
                    'country' => $item['country'] ?? null,
                    'risk_score' => RiskHeuristics::computeRefDomainRiskScore(
                        new DomainRefDomain(['backlinks_count' => $item['backlinks_count'], 'tld' => $item['tld'] ?? null]),
                        $totalBacklinks
                    ),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Bulk insert (ignore duplicates)
            if (!empty($data)) {
                DB::table('domain_ref_domains')->insertOrIgnore($data);
            }

            $totalFetched += count($items);
            $offset += count($items);

            if (count($items) < $chunkSize) {
                break;
            }
        }
    }

    /**
     * Fetch and store anchors
     */
    protected function fetchAndStoreAnchors(DomainBacklinkRun $run, $provider, string $host, int $limit): void
    {
        $offset = 0;
        $chunkSize = 500;
        $totalFetched = 0;

        while ($totalFetched < $limit) {
            $result = $provider->fetchAnchors($host, min($chunkSize, $limit - $totalFetched), $offset);
            $items = $result['items'] ?? [];

            if (empty($items)) {
                break;
            }

            $data = [];
            foreach ($items as $item) {
                $data[] = [
                    'run_id' => $run->id,
                    'anchor' => $item['anchor'],
                    'anchor_hash' => DomainAnchorSummary::generateHash($item['anchor']),
                    'count' => $item['count'],
                    'type' => $item['type'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Bulk insert (ignore duplicates)
            if (!empty($data)) {
                DB::table('domain_anchor_summaries')->insertOrIgnore($data);
            }

            $totalFetched += count($items);
            $offset += count($items);

            if (count($items) < $chunkSize) {
                break;
            }
        }
    }

    /**
     * Compute deltas vs previous run
     */
    protected function computeDeltas(DomainBacklinkRun $currentRun, Domain $domain): DomainBacklinkDelta
    {
        // Find previous completed run
        $previousRun = DomainBacklinkRun::where('domain_id', $domain->id)
            ->where('id', '<', $currentRun->id)
            ->where('status', DomainBacklinkRun::STATUS_COMPLETED)
            ->latest()
            ->first();

        if (!$previousRun) {
            // No previous run, all are new
            $newLinks = $currentRun->backlinks()->count();
            $newRefDomains = $currentRun->refDomains()->count();

            return DomainBacklinkDelta::create([
                'domain_id' => $domain->id,
                'current_run_id' => $currentRun->id,
                'previous_run_id' => null,
                'new_links' => $newLinks,
                'lost_links' => 0,
                'new_ref_domains' => $newRefDomains,
                'lost_ref_domains' => 0,
            ]);
        }

        // Compare fingerprints to find new/lost
        $currentFingerprints = $currentRun->backlinks()->pluck('fingerprint')->toArray();
        $previousFingerprints = $previousRun->backlinks()->pluck('fingerprint')->toArray();

        $newFingerprints = array_diff($currentFingerprints, $previousFingerprints);
        $lostFingerprints = array_diff($previousFingerprints, $currentFingerprints);

        // Compare ref domains
        $currentDomains = $currentRun->refDomains()->pluck('domain')->toArray();
        $previousDomains = $previousRun->refDomains()->pluck('domain')->toArray();

        $newDomains = array_diff($currentDomains, $previousDomains);
        $lostDomains = array_diff($previousDomains, $currentDomains);

        return DomainBacklinkDelta::create([
            'domain_id' => $domain->id,
            'current_run_id' => $currentRun->id,
            'previous_run_id' => $previousRun->id,
            'new_links' => count($newFingerprints),
            'lost_links' => count($lostFingerprints),
            'new_ref_domains' => count($newDomains),
            'lost_ref_domains' => count($lostDomains),
        ]);
    }

    /**
     * Compute backlink quality/risk scores and ref domain aggregates
     */
    protected function processQualityScoring(DomainBacklinkRun $run, Domain $domain): void
    {
        $scoringEngine = app(BacklinkScoringEngine::class);

        // Build ref domain aggregates from backlinks in this run
        $refDomainStats = DomainBacklink::where('run_id', $run->id)
            ->select(
                'source_domain',
                DB::raw('MIN(first_seen) as first_seen_at'),
                DB::raw('MAX(last_seen) as last_seen_at'),
                DB::raw('COUNT(*) as links_count'),
                DB::raw("SUM(CASE WHEN rel = 'follow' THEN 1 ELSE 0 END) as follow_links_count")
            )
            ->groupBy('source_domain')
            ->get();

        foreach ($refDomainStats as $stat) {
            if (!$stat->source_domain) {
                continue;
            }

            BacklinkRefDomain::updateOrCreate(
                [
                    'domain_id' => $domain->id,
                    'ref_domain' => $stat->source_domain,
                ],
                [
                    'first_seen_at' => $stat->first_seen_at,
                    'last_seen_at' => $stat->last_seen_at,
                    'links_count' => (int) $stat->links_count,
                    'follow_links_count' => (int) $stat->follow_links_count,
                ]
            );
        }

        // Attach ref_domain_id and score backlinks
        DomainBacklink::where('run_id', $run->id)
            ->orderBy('id')
            ->chunkById(200, function ($backlinks) use ($domain, $scoringEngine) {
                $domains = $backlinks->pluck('source_domain')->filter()->unique()->values();
                $refMap = BacklinkRefDomain::where('domain_id', $domain->id)
                    ->whereIn('ref_domain', $domains)
                    ->pluck('id', 'ref_domain');

                foreach ($backlinks as $backlink) {
                    $refId = $refMap[$backlink->source_domain] ?? null;
                    $scores = $scoringEngine->scoreBacklink($backlink, []);

                    $actionStatus = $backlink->action_status;
                    if (!$actionStatus) {
                        if ($scores['risk_score'] >= 80) {
                            $actionStatus = DomainBacklink::ACTION_DISAVOW;
                        } elseif ($scores['risk_score'] >= 55) {
                            $actionStatus = DomainBacklink::ACTION_REVIEW;
                        } else {
                            $actionStatus = DomainBacklink::ACTION_KEEP;
                        }
                    }

                    $backlink->update([
                        'ref_domain_id' => $refId,
                        'risk_score' => $scores['risk_score'],
                        'quality_score' => $scores['quality_score'],
                        'flags_json' => $scores['flags'],
                        'action_status' => $actionStatus,
                    ]);
                }
            });

        // Score ref domains
        BacklinkRefDomain::where('domain_id', $domain->id)
            ->orderBy('id')
            ->chunkById(200, function ($refDomains) use ($scoringEngine) {
                foreach ($refDomains as $refDomain) {
                    $scores = $scoringEngine->scoreRefDomain($refDomain);
                    $refDomain->update($scores);
                }
            });
    }
}
