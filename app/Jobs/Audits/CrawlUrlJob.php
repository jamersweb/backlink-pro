<?php

namespace App\Jobs\Audits;

use App\Models\DomainAuditPage;
use App\Services\Audits\HtmlExtractor;
use App\Services\Audits\IssueRulesEngine;
use App\Services\Audits\UrlNormalizer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CrawlUrlJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60; // 1 minute
    public $tries = 2;
    public $queue = 'audits';

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

            // If HTML, extract data
            if (strpos($contentType ?? '', 'text/html') !== false) {
                $htmlData = HtmlExtractor::extract($body);
                $robotsMeta = $htmlData['robots_meta'] ?? null;

                // Check if robots meta has noindex
                if ($robotsMeta && stripos($robotsMeta, 'noindex') !== false) {
                    $isIndexable = false;
                }
            }

            // Extract path
            $path = UrlNormalizer::extractPath($finalUrl);

            // Create or update page
            $page = DomainAuditPage::updateOrCreate(
                [
                    'domain_audit_id' => $this->auditId,
                    'url' => $this->url,
                ],
                [
                    'path' => $path,
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
                    'is_indexable' => $isIndexable,
                ]
            );

            // Generate issues
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
                    'is_indexable' => false,
                    'issues_count' => 0,
                ]
            );

            throw $e;
        }
    }
}
