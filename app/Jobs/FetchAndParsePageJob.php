<?php

namespace App\Jobs;

use App\Models\Audit;
use App\Models\AuditLink;
use App\Models\AuditPage;
use App\Models\AuditUrlQueue;
use App\Services\SeoAudit\UrlNormalizer;
use App\Services\SeoAudit\PageParser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchAndParsePageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;
    public $tries = 2;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $auditId,
        public int $queueItemId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $audit = Audit::find($this->auditId);
        $queueItem = AuditUrlQueue::find($this->queueItemId);
        
        if (!$audit || !$queueItem) {
            Log::warning("Audit or queue item not found", [
                'audit_id' => $this->auditId,
                'queue_id' => $this->queueItemId,
            ]);
            return;
        }

        // Check if we've hit the pages limit
        if ($audit->pages_scanned >= $audit->pages_limit) {
            $queueItem->status = AuditUrlQueue::STATUS_DONE;
            $queueItem->save();
            $this->checkAndFinalize($audit);
            return;
        }

        try {
            // Mark as processing
            $queueItem->status = AuditUrlQueue::STATUS_PROCESSING;
            $queueItem->save();

            // Fetch page
            $response = Http::timeout(20)
                ->connectTimeout(8)
                ->withUserAgent('BacklinkProBot/1.0')
                ->withOptions([
                    'allow_redirects' => [
                        'max' => 5,
                        'strict' => true,
                        'referer' => true,
                        'protocols' => ['http', 'https'],
                        'track_redirects' => true,
                    ],
                ])
                ->get($queueItem->url);

            $statusCode = $response->status();
            $finalUrl = $response->effectiveUri() ?? $queueItem->url;
            $html = $response->body();
            $htmlSizeBytes = strlen($html);

            // Track redirect hops
            $redirectHops = count($response->redirectHistory() ?? []);

            // Skip if too large (optional)
            if ($htmlSizeBytes > 5 * 1024 * 1024) {
                Log::warning("Page too large, skipping: {$queueItem->url}");
                $queueItem->status = AuditUrlQueue::STATUS_DONE;
                $queueItem->save();
                $this->checkAndFinalize($audit);
                return;
            }

            // Parse HTML
            $headers = [
                'server' => $response->header('Server'),
                'x_powered_by' => $response->header('X-Powered-By'),
                'content_type' => $response->header('Content-Type'),
                'x_robots_tag' => $response->header('X-Robots-Tag'),
            ];
            $parsed = PageParser::parse($html, $finalUrl, $queueItem->url_normalized, $headers);

            // Create or update audit page
            $page = AuditPage::updateOrCreate(
                [
                    'audit_id' => $audit->id,
                    'url' => $finalUrl,
                ],
                array_merge($parsed, [
                    'status_code' => $statusCode,
                    'html_size_bytes' => $htmlSizeBytes,
                ])
            );

            // Extract and store links
            $this->extractAndStoreLinks($audit, $queueItem->url_normalized, $html, $finalUrl);

            // Enqueue new internal links
            $this->enqueueNewLinks($audit, $queueItem->depth);

            // Mark queue item as done
            $queueItem->status = AuditUrlQueue::STATUS_DONE;
            $queueItem->save();

            // Record usage: page_crawled
            if ($audit->organization_id) {
                \App\Services\Billing\UsageRecorder::record(
                    $audit->organization_id,
                    \App\Models\UsageEvent::TYPE_PAGE_CRAWLED,
                    1,
                    $audit->id,
                    ['page_url' => $finalUrl]
                );
            }

            // Update audit progress
            $audit->pages_scanned = AuditPage::where('audit_id', $audit->id)->count();
            $audit->progress_percent = min(100, (int) (($audit->pages_scanned / $audit->pages_limit) * 100));
            $audit->save();

            // Dispatch next batch or finalize
            $this->dispatchNextBatchOrFinalize($audit);

        } catch (\Exception $e) {
            Log::error("FetchAndParsePageJob failed: {$e->getMessage()}", [
                'audit_id' => $this->auditId,
                'queue_id' => $this->queueItemId,
                'exception' => $e,
            ]);

            $queueItem->status = AuditUrlQueue::STATUS_FAILED;
            $queueItem->last_error = $e->getMessage();
            $queueItem->save();

            // Continue with other pages
            $this->dispatchNextBatchOrFinalize($audit);
        }
    }

    

    /**
     * Extract and store links from page
     */
    protected function extractAndStoreLinks(Audit $audit, string $fromUrl, string $html, string $finalUrl): void
    {
        $baseHost = UrlNormalizer::extractHost($audit->normalized_url);
        $externalLinksProcessed = 0;
        $maxExternalLinksPerPage = 20;
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
        $dom->loadHTML($html, LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();
        $xpath = new \DOMXPath($dom);

        try {
            $links = $xpath->query('//a[@href]');
            foreach ($links as $link) {
                $href = $link->getAttribute('href');
                if (empty($href) || UrlNormalizer::shouldSkip($href)) {
                    continue;
                }

                // Resolve relative URLs
                $absoluteUrl = $this->resolveUrl($href, $fromUrl);
                if (!$absoluteUrl) {
                    continue;
                }

                $normalized = UrlNormalizer::normalize($absoluteUrl, $fromUrl);
                if (!$normalized) {
                    continue;
                }

                $isInternal = UrlNormalizer::isInternal($normalized, $baseHost);
                $type = $isInternal ? AuditLink::TYPE_INTERNAL : AuditLink::TYPE_EXTERNAL;

                // Limit external link processing
                if (!$isInternal && $externalLinksProcessed >= $maxExternalLinksPerPage) {
                    continue;
                }

                $relNofollow = strpos($link->getAttribute('rel') ?? '', 'nofollow') !== false;
                $anchorText = trim($link->textContent ?? '');

                // Check if link already exists
                $existingLink = AuditLink::where('audit_id', $audit->id)
                    ->where('from_url', $fromUrl)
                    ->where('to_url_normalized', $normalized)
                    ->first();

                if (!$existingLink) {
                    AuditLink::create([
                        'audit_id' => $audit->id,
                        'from_url' => $fromUrl,
                        'to_url' => $absoluteUrl,
                        'to_url_normalized' => $normalized,
                        'type' => $type,
                        'rel_nofollow' => $relNofollow,
                        'anchor_text' => $anchorText ?: null,
                    ]);
                }

                if (!$isInternal) {
                    $externalLinksProcessed++;
                }
            }
        } catch (\Exception $e) {
            Log::debug("Link extraction error: " . $e->getMessage());
        }
    }

    /**
     * Enqueue new internal links for crawling
     */
    protected function enqueueNewLinks(Audit $audit, int $currentDepth): void
    {
        if ($currentDepth >= $audit->crawl_depth) {
            return;
        }

        $baseHost = UrlNormalizer::extractHost($audit->normalized_url);
        $newDepth = $currentDepth + 1;

        // Get internal links from this page that aren't in queue yet
        $pageLinks = AuditLink::where('audit_id', $audit->id)
            ->where('type', AuditLink::TYPE_INTERNAL)
            ->where('from_url', 'LIKE', '%' . parse_url($audit->normalized_url, PHP_URL_HOST) . '%')
            ->get();

        foreach ($pageLinks as $link) {
            // Check if already queued or processed
            $exists = AuditUrlQueue::where('audit_id', $audit->id)
                ->where('url_normalized', $link->to_url_normalized)
                ->exists();

            if (!$exists && $audit->pages_scanned < $audit->pages_limit) {
                AuditUrlQueue::create([
                    'audit_id' => $audit->id,
                    'url' => $link->to_url,
                    'url_normalized' => $link->to_url_normalized,
                    'depth' => $newDepth,
                    'status' => AuditUrlQueue::STATUS_QUEUED,
                    'discovered_from' => $link->from_url,
                ]);

                $audit->pages_discovered++;
            }
        }

        $audit->save();
    }

    /**
     * Resolve relative URL against base URL
     */
    protected function resolveUrl(string $relative, string $baseUrl): ?string
    {
        if (preg_match('/^https?:\/\//', $relative)) {
            return $relative;
        }

        $parsed = parse_url($baseUrl);
        $scheme = $parsed['scheme'] ?? 'https';
        $host = $parsed['host'] ?? '';

        if (strpos($relative, '/') === 0) {
            return $scheme . '://' . $host . $relative;
        }

        $basePath = $parsed['path'] ?? '/';
        if (substr($basePath, -1) !== '/') {
            $basePath = dirname($basePath) . '/';
        }

        return $scheme . '://' . $host . $basePath . $relative;
    }

    /**
     * Dispatch next batch of jobs or finalize
     */
    protected function dispatchNextBatchOrFinalize(Audit $audit): void
    {
        // Check if we should continue crawling
        if ($audit->pages_scanned >= $audit->pages_limit) {
            $this->checkAndFinalize($audit);
            return;
        }

        // Get next batch of queued URLs
        $concurrency = 5;
        $queuedUrls = AuditUrlQueue::where('audit_id', $audit->id)
            ->where('status', AuditUrlQueue::STATUS_QUEUED)
            ->where('depth', '<=', $audit->crawl_depth)
            ->limit($concurrency)
            ->get();

        foreach ($queuedUrls as $queueItem) {
            FetchAndParsePageJob::dispatch($audit->id, $queueItem->id)
                ->delay(now()->addMilliseconds(rand(150, 300)));
        }

        // If no more URLs, finalize
        $remaining = AuditUrlQueue::where('audit_id', $audit->id)
            ->where('status', AuditUrlQueue::STATUS_QUEUED)
            ->where('depth', '<=', $audit->crawl_depth)
            ->count();

        if ($remaining === 0) {
            $this->checkAndFinalize($audit);
        }
    }

    /**
     * Check if crawl is complete and dispatch finalize job
     */
    protected function checkAndFinalize(Audit $audit): void
    {
        // Check if there are any processing jobs still running
        $processing = AuditUrlQueue::where('audit_id', $audit->id)
            ->where('status', AuditUrlQueue::STATUS_PROCESSING)
            ->count();

        if ($processing === 0) {
            FinalizeAuditJob::dispatch($audit->id)->delay(now()->addSeconds(2));
        }
    }
}
