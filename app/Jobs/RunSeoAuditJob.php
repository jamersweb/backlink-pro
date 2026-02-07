<?php

namespace App\Jobs;

use App\Models\Audit;
use App\Models\AuditPage;
use App\Services\SeoAudit\RulesEngine;
use App\Services\SeoAudit\PageParser;
use App\Services\SeoAudit\AuditKpiBuilder;
use App\Jobs\StartAuditPipelineJob;
use App\Jobs\RunPageSpeedJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RunSeoAuditJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 2;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $auditId
    ) {}

    /**
     * Execute the job.
     * 
     * Phase 1: If pages_limit=1 and crawl_depth=0, scan homepage only.
     * Phase 2: Otherwise, dispatch StartAuditPipelineJob for multi-page crawling.
     */
    public function handle(): void
    {
        $audit = Audit::find($this->auditId);
        
        if (!$audit) {
            Log::warning("Audit not found: {$this->auditId}");
            return;
        }

        $organization = $audit->organization;
        $hasSharedKey = (bool) config('services.google.pagespeed_api_key');
        $hasByokKey = $organization
            && $organization->pagespeed_byok_enabled
            && $organization->pagespeed_last_key_verified_at
            && $organization->pagespeed_api_key_encrypted;

        if ($hasSharedKey || $hasByokKey) {
            $shouldRunSync = app()->environment('local') || config('queue.default') === 'sync';
            if ($shouldRunSync) {
                RunPageSpeedJob::dispatchSync($audit->id, $audit->normalized_url);
            } else {
                RunPageSpeedJob::dispatch($audit->id, $audit->normalized_url)
                    ->onQueue('integrations');
            }
        }

        // Check if this is a Phase 1 single-page audit
        $pagesLimit = $audit->pages_limit ?? 25;
        $crawlDepth = $audit->crawl_depth ?? 2;
        
        if ($pagesLimit === 1 && $crawlDepth === 0) {
            // Phase 1: Single page audit
            $this->handleSinglePage($audit);
        } else {
            // Phase 2: Multi-page crawl - dispatch pipeline
            StartAuditPipelineJob::dispatch($this->auditId);
        }
    }

    /**
     * Handle Phase 1 single-page audit
     */
    protected function handleSinglePage(Audit $audit): void
    {
        try {
            // Update status to running
            $audit->status = Audit::STATUS_RUNNING;
            $audit->started_at = now();
            $audit->error = null;
            $audit->save();

            // Fetch homepage HTML
            $response = Http::timeout(20)
                ->withUserAgent('BacklinkProBot/1.0')
                ->withOptions([
                    'allow_redirects' => [
                        'max' => 5,
                        'strict' => true,
                        'referer' => true,
                        'protocols' => ['http', 'https'],
                    ],
                ])
                ->get($audit->normalized_url);

            $statusCode = $response->status();
            $finalUrl = $response->effectiveUri() ?? $audit->normalized_url;
            $html = $response->body();
            $htmlSizeBytes = strlen($html);

            // Parse HTML
            $headers = [
                'server' => $response->header('Server'),
                'x_powered_by' => $response->header('X-Powered-By'),
                'content_type' => $response->header('Content-Type'),
                'x_robots_tag' => $response->header('X-Robots-Tag'),
            ];
            $parsed = PageParser::parse($html, $finalUrl, $audit->normalized_url, $headers);

            // Create audit page
            $page = AuditPage::create([
                'audit_id' => $audit->id,
                'url' => $finalUrl,
                'status_code' => $statusCode,
                'title' => $parsed['title'],
                'title_len' => $parsed['title_len'],
                'meta_description' => $parsed['meta_description'],
                'meta_len' => $parsed['meta_len'],
                'canonical_url' => $parsed['canonical_url'],
                'robots_meta' => $parsed['robots_meta'],
                'h1_count' => $parsed['h1_count'],
                'h2_count' => $parsed['h2_count'],
                'h3_count' => $parsed['h3_count'],
                'word_count' => $parsed['word_count'],
                'images_total' => $parsed['images_total'],
                'images_missing_alt' => $parsed['images_missing_alt'],
                'internal_links_count' => $parsed['internal_links_count'],
                'external_links_count' => $parsed['external_links_count'],
                'og_present' => $parsed['og_present'],
                'twitter_cards_present' => $parsed['twitter_cards_present'],
                'schema_types' => $parsed['schema_types'],
                'html_size_bytes' => $htmlSizeBytes,
            ]);

            // Run rules engine
            $rulesEngine = new RulesEngine();
            $evaluation = $rulesEngine->evaluate($audit, $page);

            // Issues are already created and saved by createIssue() method
            // $evaluation['issues'] contains AuditIssue models

            // Calculate scores
            $categoryScores = $rulesEngine->calculateCategoryScores($evaluation['categoryPenalties']);
            $overallScore = $rulesEngine->calculateOverallScore($categoryScores);
            $overallGrade = $rulesEngine->scoreToGrade($overallScore);

            // Create summary
            $summary = [
                'total_issues' => count($evaluation['issues']),
                'high_impact_issues' => collect($evaluation['issues'])->where('impact', 'high')->count(),
                'medium_impact_issues' => collect($evaluation['issues'])->where('impact', 'medium')->count(),
                'low_impact_issues' => collect($evaluation['issues'])->where('impact', 'low')->count(),
            ];

            // Update audit
            $audit->overall_score = $overallScore;
            $audit->overall_grade = $overallGrade;
            $audit->category_scores = $categoryScores;
            $audit->summary = $summary;

            // Build KPI payload
            $kpiBuilder = new AuditKpiBuilder();
            $audit->audit_kpis = $kpiBuilder->build($audit);
            $audit->category_grades = $audit->audit_kpis['overview']['category_grades'] ?? null;
            $audit->recommendations_count = $audit->audit_kpis['overview']['recommendations_count'] ?? $summary['total_issues'];

            $audit->status = Audit::STATUS_COMPLETED;
            $audit->finished_at = now();
            $audit->save();

        } catch (\Exception $e) {
            Log::error("SEO Audit failed: {$e->getMessage()}", [
                'audit_id' => $this->auditId,
                'exception' => $e,
            ]);

            $audit->status = Audit::STATUS_FAILED;
            $audit->error = $e->getMessage();
            $audit->finished_at = now();
            $audit->save();
        }
    }

    
}
