<?php

namespace App\Jobs\Audits;

use App\Models\DomainAudit;
use App\Models\DomainAuditPage;
use App\Services\Audits\CrawlBootstrapResolver;
use App\Services\Audits\SitemapDiscovery;
use App\Services\Audits\UrlNormalizer;
use App\Services\System\ActivityLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class StartDomainAuditJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 1;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $auditId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $audit = DomainAudit::findOrFail($this->auditId);
        $domain = $audit->domain;

        // Mark as running
        $audit->update([
            'status' => DomainAudit::STATUS_RUNNING,
            'started_at' => now(),
            'error_message' => null,
        ]);
        Cache::forget("audit:finalize_dispatched:{$this->auditId}");

        // Log started
        $logger = app(ActivityLogger::class);
        $logger->info(
            'audits',
            'started',
            "Audit started for domain {$domain->host}",
            $audit->user_id,
            $audit->domain_id,
            [
                'audit_id' => $this->auditId,
                'run_ref' => ActivityLogger::runRef('audits', $this->auditId),
            ]
        );

        try {
            $rawStartInput = trim((string) ($domain->host ?: $domain->url));
            $bootstrap = CrawlBootstrapResolver::resolve($rawStartInput);

            foreach ($bootstrap['attempts'] as $attempt) {
                if ($attempt['success']) {
                    Log::info('Audit crawl bootstrap URL succeeded', [
                        'audit_id' => $this->auditId,
                        'domain_id' => $audit->domain_id,
                        'url' => $attempt['url'],
                        'status' => $attempt['status'],
                    ]);
                } else {
                    Log::warning('Audit crawl bootstrap URL failed', [
                        'audit_id' => $this->auditId,
                        'domain_id' => $audit->domain_id,
                        'url' => $attempt['url'],
                        'error' => $attempt['error'],
                    ]);
                }
            }

            if (!$bootstrap['success']) {
                throw new \Exception(
                    ($bootstrap['error'] ?? 'Unable to bootstrap crawl URL.') .
                    ' Tried: ' . implode(', ', array_column($bootstrap['attempts'], 'url'))
                );
            }

            $workingBaseUrl = $bootstrap['working_base_url'];
            $workingHost = parse_url($workingBaseUrl, PHP_URL_HOST);
            if (!$workingHost) {
                throw new \Exception('Unable to determine crawl host from bootstrap URL.');
            }

            // Build seed URLs
            $seedUrls = [];

            // Start with domain URL
            $startUrl = $workingBaseUrl . '/';
            $normalized = UrlNormalizer::normalize($startUrl, $workingHost);
            if ($normalized) {
                $seedUrls[] = $normalized;
            }

            // If sitemap is enabled, discover URLs from sitemap
            $settings = $audit->settings_json ?? [];
            $maxDepth = (int) ($settings['max_depth'] ?? 3);
            if ($settings['include_sitemap'] ?? false) {
                $sitemapUrls = SitemapDiscovery::discover($workingBaseUrl, $settings['crawl_limit'] ?? 100);
                $seedUrls = array_merge($seedUrls, $sitemapUrls);
                $seedUrls = array_unique($seedUrls);
            }

            // Limit to crawl_limit
            $crawlLimit = $settings['crawl_limit'] ?? 100;
            $seedUrls = array_slice($seedUrls, 0, $crawlLimit);

            if (empty($seedUrls)) {
                throw new \Exception('No URLs to crawl');
            }

            // Queue initial seeds. Homepage starts at depth 0, sitemap seeds at depth 1.
            foreach ($seedUrls as $url) {
                $depth = $url === $normalized ? 0 : 1;
                if ($depth > $maxDepth) {
                    continue;
                }

                $queued = DomainAuditPage::firstOrCreate(
                    [
                        'domain_audit_id' => $this->auditId,
                        'url' => $url,
                    ],
                    [
                        'path' => UrlNormalizer::extractPath($url),
                        'is_indexable' => false,
                        'issues_count' => 0,
                        'crawl_depth' => $depth,
                        'discovered_from_url' => $depth === 0 ? null : $normalized,
                    ]
                );

                if ($queued->wasRecentlyCreated) {
                    CrawlUrlJob::dispatch($this->auditId, $url, $depth)->onQueue('audits');
                }
            }

            // In case all seeds are filtered out by depth, finalize immediately.
            $pendingCount = DomainAuditPage::where('domain_audit_id', $this->auditId)
                ->whereNull('status_code')
                ->count();
            if ($pendingCount === 0) {
                FinalizeDomainAuditJob::dispatch($this->auditId)->onQueue('audits');
            }
        } catch (\Exception $e) {
            $audit->update([
                'status' => DomainAudit::STATUS_FAILED,
                'error_message' => $e->getMessage(),
                'finished_at' => now(),
            ]);

            $logger = app(ActivityLogger::class);
            $logger->logJobFailure(
                'audits',
                'StartDomainAuditJob',
                $e,
                $audit->domain_id,
                $audit->user_id,
                ActivityLogger::runRef('audits', $this->auditId),
                ['audit_id' => $this->auditId]
            );

            Log::error('Domain audit start failed', [
                'audit_id' => $this->auditId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
