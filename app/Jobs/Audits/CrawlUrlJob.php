<?php

namespace App\Jobs\Audits;

use Illuminate\Bus\Batchable;
use App\Models\DomainAudit;
use App\Models\DomainAuditPage;
use App\Services\Audits\HtmlExtractor;
use App\Services\Audits\IssueRulesEngine;
use App\Services\Audits\UrlNormalizer;
use Illuminate\Database\QueryException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CrawlUrlJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60; // 1 minute
    public $tries = 2;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $auditId,
        public string $url,
        public int $depth = 0
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $startTime = microtime(true);
        $audit = DomainAudit::with('domain')->find($this->auditId);
        if (!$audit || $audit->status !== DomainAudit::STATUS_RUNNING) {
            return;
        }

        $settings = $audit->settings_json ?? [];
        $crawlLimit = (int) ($settings['crawl_limit'] ?? 100);
        $maxDepth = (int) ($settings['max_depth'] ?? 3);
        $domainHost = strtolower((string) ($audit->domain?->host ?? parse_url($this->url, PHP_URL_HOST)));

        try {
            // Fetch URL
            $response = Http::timeout(20)
                ->retry(2, 100)
                ->withOptions([
                    'allow_redirects' => [
                        'max' => 5,
                        'strict' => true,
                        'referer' => true,
                        'protocols' => ['http', 'https'],
                    ],
                ])
                ->get($this->url);

            $responseTime = (int) ((microtime(true) - $startTime) * 1000);
            $statusCode = $response->status();
            $finalUrl = $response->effectiveUri() ?? $this->url;
            $contentType = $response->header('Content-Type');
            $body = $response->body();

            // Determine if indexable
            $isIndexable = $statusCode === 200;
            $robotsMeta = null;
            $htmlData = [];
            $outlinksCount = 0;

            // If HTML, extract data
            $isHtml = strpos($contentType ?? '', 'text/html') !== false;
            if ($isHtml) {
                $htmlData = HtmlExtractor::extract($body);
                $robotsMeta = $htmlData['robots_meta'] ?? null;

                // Check if robots meta has noindex
                if ($robotsMeta && stripos($robotsMeta, 'noindex') !== false) {
                    $isIndexable = false;
                }
            }

            // Extract path
            $path = UrlNormalizer::extractPath($finalUrl);

            // Create or update page as crawled
            $page = DomainAuditPage::updateOrCreate(
                [
                    'domain_audit_id' => $this->auditId,
                    'url' => $this->url,
                ],
                [
                    'path' => $path,
                    'crawl_depth' => $this->depth,
                    'status_code' => $statusCode,
                    'final_url' => $finalUrl !== $this->url ? $finalUrl : null,
                    'response_time_ms' => $responseTime,
                    'content_type' => $contentType,
                    'title' => $htmlData['title'] ?? null,
                    'meta_description' => $htmlData['meta_description'] ?? null,
                    'canonical' => $htmlData['canonical'] ?? null,
                    'robots_meta' => $robotsMeta,
                    'h1_count' => $htmlData['h1_count'] ?? 0,
                    'word_count' => $htmlData['word_count'] ?? 0,
                    'outlinks_count' => 0,
                    'is_indexable' => $isIndexable,
                ]
            );

            // Generate issues
            $page->issues()->delete();
            $issues = IssueRulesEngine::generateIssues($page);
            $issuesCount = count($issues);

            // Save issues
            foreach ($issues as $issue) {
                $page->issues()->create([
                    'domain_audit_id' => $this->auditId,
                    'severity' => $issue['severity'],
                    'type' => $issue['type'],
                    'message' => $issue['message'],
                    'data_json' => $issue['data'] ?? null,
                ]);
            }

            // Update issues count
            $page->update(['issues_count' => $issuesCount]);

            // Recursive internal crawl for HTML pages only
            if ($isHtml && $this->depth < $maxDepth) {
                $internalLinks = HtmlExtractor::extractInternalLinks($body, $domainHost);
                $internalLinks = array_values(array_filter($internalLinks, fn ($link) => $this->isCrawlableLink($link)));
                $outlinksCount = count($internalLinks);
                if ($outlinksCount > 0) {
                    $page->update(['outlinks_count' => $outlinksCount]);
                }

                foreach ($internalLinks as $childUrl) {
                    $currentTotal = DomainAuditPage::where('domain_audit_id', $this->auditId)->count();
                    if ($currentTotal >= $crawlLimit) {
                        break;
                    }

                    $nextDepth = $this->depth + 1;
                    $childQueued = $this->queueChildUrl($childUrl, $nextDepth, $this->url);
                    if ($childQueued) {
                        self::dispatch($this->auditId, $childUrl, $nextDepth)->onQueue('audits');
                    }
                }
            }

        } catch (\Exception $e) {
            Log::error('Crawl URL job failed', [
                'audit_id' => $this->auditId,
                'url' => $this->url,
                'error' => $e->getMessage(),
            ]);

            // Create page record with error status
            DomainAuditPage::updateOrCreate(
                [
                    'domain_audit_id' => $this->auditId,
                    'url' => $this->url,
                ],
                [
                    'status_code' => 0,
                    'crawl_depth' => $this->depth,
                    'is_indexable' => false,
                    'issues_count' => 0,
                ]
            );
        } finally {
            $this->dispatchFinalizeIfComplete();
        }
    }

    protected function queueChildUrl(string $url, int $depth, string $discoveredFrom): bool
    {
        try {
            $row = DomainAuditPage::firstOrCreate(
                [
                    'domain_audit_id' => $this->auditId,
                    'url' => $url,
                ],
                [
                    'path' => UrlNormalizer::extractPath($url),
                    'crawl_depth' => $depth,
                    'discovered_from_url' => $discoveredFrom,
                    'is_indexable' => false,
                    'issues_count' => 0,
                ]
            );

            return $row->wasRecentlyCreated;
        } catch (QueryException $e) {
            // Unique key race means URL already queued/crawled by another worker.
            return false;
        }
    }

    protected function dispatchFinalizeIfComplete(): void
    {
        $pending = DomainAuditPage::where('domain_audit_id', $this->auditId)
            ->whereNull('status_code')
            ->count();

        if ($pending > 0) {
            return;
        }

        $audit = DomainAudit::find($this->auditId);
        if (!$audit || $audit->status !== DomainAudit::STATUS_RUNNING) {
            return;
        }

        if (!Cache::add("audit:finalize_dispatched:{$this->auditId}", true, now()->addHour())) {
            return;
        }

        FinalizeDomainAuditJob::dispatch($this->auditId)->onQueue('audits');
    }

    protected function isCrawlableLink(string $url): bool
    {
        $lower = strtolower($url);
        if (str_starts_with($lower, 'mailto:') || str_starts_with($lower, 'tel:') || str_starts_with($lower, 'javascript:')) {
            return false;
        }

        $path = strtolower((string) parse_url($url, PHP_URL_PATH));
        if ($path !== '' && preg_match('/\.(?:jpg|jpeg|png|gif|webp|svg|ico|pdf|zip|rar|7z|js|css|woff2?|ttf|eot|mp4|mp3|avi|mov|webm|xml)$/', $path)) {
            return false;
        }

        return true;
    }
}
