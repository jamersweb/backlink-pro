<?php

namespace App\Jobs;

use App\Models\Audit;
use App\Models\AuditUrlQueue;
use App\Services\SeoAudit\SitemapDiscovery;
use App\Services\SeoAudit\UrlNormalizer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class StartAuditPipelineJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;
    public $tries = 2;

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
        $audit = Audit::find($this->auditId);
        
        if (!$audit) {
            Log::warning("Audit not found: {$this->auditId}");
            return;
        }

        try {
            // Update status
            $audit->status = Audit::STATUS_RUNNING;
            $audit->started_at = now();
            $audit->error = null;
            $audit->pages_limit = $audit->pages_limit ?? 25;
            $audit->crawl_depth = $audit->crawl_depth ?? 2;
            $audit->save();

            // Extract base host
            $baseHost = UrlNormalizer::extractHost($audit->normalized_url);
            if (!$baseHost) {
                throw new \Exception("Invalid normalized URL");
            }

            // Seed URL queue with start URL
            $this->seedUrlQueue($audit, $baseHost);

            // Dispatch initial batch of page fetch jobs
            $this->dispatchPageFetchJobs($audit);

        } catch (\Exception $e) {
            Log::error("StartAuditPipelineJob failed: {$e->getMessage()}", [
                'audit_id' => $this->auditId,
                'exception' => $e,
            ]);

            $audit->status = Audit::STATUS_FAILED;
            $audit->error = $e->getMessage();
            $audit->finished_at = now();
            $audit->save();
        }
    }

    /**
     * Seed URL queue with start URL and optional sitemap URLs
     */
    protected function seedUrlQueue(Audit $audit, string $baseHost): void
    {
        // Add start URL
        AuditUrlQueue::firstOrCreate(
            [
                'audit_id' => $audit->id,
                'url_normalized' => $audit->normalized_url,
            ],
            [
                'url' => $audit->normalized_url,
                'depth' => 0,
                'status' => AuditUrlQueue::STATUS_QUEUED,
            ]
        );

        // Try to discover sitemap URLs
        try {
            $sitemaps = SitemapDiscovery::discoverSitemaps($audit->normalized_url);
            
            if (!empty($sitemaps)) {
                // Extract URLs from first sitemap (limit to pages_limit * 2)
                $sitemapUrls = SitemapDiscovery::extractUrlsFromSitemap(
                    $sitemaps[0],
                    $audit->pages_limit * 2
                );

                foreach ($sitemapUrls as $sitemapUrl) {
                    // Only add internal URLs
                    if (!UrlNormalizer::isInternal($sitemapUrl, $baseHost)) {
                        continue;
                    }

                    $normalized = UrlNormalizer::normalize($sitemapUrl, $audit->normalized_url);
                    if (!$normalized || UrlNormalizer::shouldSkip($normalized)) {
                        continue;
                    }

                    // Add to queue with depth 0 (will be crawled if within limits)
                    AuditUrlQueue::firstOrCreate(
                        [
                            'audit_id' => $audit->id,
                            'url_normalized' => $normalized,
                        ],
                        [
                            'url' => $sitemapUrl,
                            'depth' => 0,
                            'status' => AuditUrlQueue::STATUS_QUEUED,
                            'discovered_from' => 'sitemap',
                        ]
                    );
                }
            }
        } catch (\Exception $e) {
            Log::debug("Sitemap discovery failed (non-critical): " . $e->getMessage());
        }

        // Update discovered count
        $audit->pages_discovered = AuditUrlQueue::where('audit_id', $audit->id)->count();
        $audit->save();
    }

    /**
     * Dispatch initial batch of page fetch jobs
     */
    protected function dispatchPageFetchJobs(Audit $audit): void
    {
        // Get queued URLs up to concurrency limit (5-10)
        $concurrency = 5;
        $queuedUrls = AuditUrlQueue::where('audit_id', $audit->id)
            ->where('status', AuditUrlQueue::STATUS_QUEUED)
            ->where('depth', '<=', $audit->crawl_depth)
            ->limit($concurrency)
            ->get();

        foreach ($queuedUrls as $queueItem) {
            FetchAndParsePageJob::dispatch($audit->id, $queueItem->id)
                ->delay(now()->addMilliseconds(rand(150, 300))); // Rate limiting
        }

        // If no more URLs to process, dispatch finalize job
        $remaining = AuditUrlQueue::where('audit_id', $audit->id)
            ->where('status', AuditUrlQueue::STATUS_QUEUED)
            ->where('depth', '<=', $audit->crawl_depth)
            ->count();

        if ($remaining === 0) {
            // Wait a bit then finalize (in case more URLs are discovered)
            FinalizeAuditJob::dispatch($audit->id)->delay(now()->addSeconds(5));
        }
    }
}
