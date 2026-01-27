<?php

namespace App\Jobs\Audits;

use App\Models\DomainAudit;
use App\Services\Audits\SitemapDiscovery;
use App\Services\Audits\UrlNormalizer;
use App\Services\System\ActivityLogger;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

class StartDomainAuditJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 1;
    public $queue = 'audits';

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
        ]);

        // Log started
        $logger = app(ActivityLogger::class);
        $logger->info(
            ActivityLogger::runRef('audits', $this->auditId),
            'started',
            "Audit started for domain {$domain->host}",
            $audit->user_id,
            $audit->domain_id,
            ['audit_id' => $this->auditId]
        );

        try {
            // Build seed URLs
            $seedUrls = [];

            // Start with domain URL
            $startUrl = $domain->url ?? 'https://' . $domain->host;
            $normalized = UrlNormalizer::normalize($startUrl, $domain->host);
            if ($normalized) {
                $seedUrls[] = $normalized;
            }

            // If sitemap is enabled, discover URLs from sitemap
            $settings = $audit->settings_json ?? [];
            if ($settings['include_sitemap'] ?? false) {
                $sitemapUrls = SitemapDiscovery::discover($startUrl, $settings['crawl_limit'] ?? 100);
                $seedUrls = array_merge($seedUrls, $sitemapUrls);
                $seedUrls = array_unique($seedUrls);
            }

            // Limit to crawl_limit
            $crawlLimit = $settings['crawl_limit'] ?? 100;
            $seedUrls = array_slice($seedUrls, 0, $crawlLimit);

            if (empty($seedUrls)) {
                throw new \Exception('No URLs to crawl');
            }

            // Create batch of crawl jobs
            $jobs = [];
            foreach ($seedUrls as $url) {
                $jobs[] = new CrawlUrlJob($this->auditId, $url, 0);
            }

            // Dispatch batch
            Bus::batch($jobs)
                ->name("Audit #{$this->auditId} crawl")
                ->onQueue('audits')
                ->allowFailures()
                ->then(function (Batch $batch) {
                    // All jobs completed successfully
                    FinalizeDomainAuditJob::dispatch($this->auditId);
                })
                ->catch(function (Batch $batch, \Throwable $e) {
                    // Batch failed
                    $audit = DomainAudit::find($this->auditId);
                    if ($audit) {
                        $audit->update([
                            'status' => DomainAudit::STATUS_FAILED,
                            'error_message' => 'Batch failed: ' . $e->getMessage(),
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
                    }
                    Log::error('Domain audit batch failed', [
                        'audit_id' => $this->auditId,
                        'error' => $e->getMessage(),
                    ]);
                })
                ->finally(function (Batch $batch) {
                    // Always called
                    Log::info('Domain audit batch finished', [
                        'audit_id' => $this->auditId,
                        'total_jobs' => $batch->totalJobs,
                        'pending_jobs' => $batch->pendingJobs,
                        'failed_jobs' => $batch->failedJobs,
                    ]);
                })
                ->dispatch();
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
