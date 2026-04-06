<?php

namespace App\Jobs;

use App\Models\Audit;
use App\Models\AuditPage;
use App\Services\SeoAudit\RulesEngine;
use App\Services\SeoAudit\PageParser;
use App\Services\SeoAudit\AuditKpiBuilder;
use App\Services\SeoAudit\AuditKpiSanitizer;
use App\Services\SeoAudit\ReportModuleBuilder;
use App\Services\SeoAudit\JsRenderingService;
use App\Services\SeoAudit\SegmentationService;
use App\Services\SeoAudit\SpellingGrammarService;
use App\Services\SeoAudit\VisibleTextExtractor;
use App\Services\SeoAudit\CustomSourceSearchService;
use App\Services\SeoAudit\AuthCrawlMetadataBuilder;
use App\Services\SeoAudit\CustomExtractionService;
use App\Services\SeoAudit\FormsAuthIssueService;
use App\Services\SeoAudit\FormsAuthService;
use App\Services\SeoAudit\SeoAuditHttp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * FAST core audit only: URL fetch, on-page checks, scoring, issues.
 * Target: complete in under 60 seconds. Enrichments (PSI/GA4/GSC) run in separate jobs.
 */
class RunSeoAuditCoreJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;

    public function __construct(
        public int $auditId
    ) {}

    public function handle(): void
    {
        $start = microtime(true);
        Log::info('AuditCoreJob started', ['audit_id' => $this->auditId]);

        $audit = Audit::find($this->auditId);
        if (!$audit) {
            Log::error('AuditCoreJob: audit not found', ['audit_id' => $this->auditId]);
            return;
        }

        $audit->status = Audit::STATUS_RUNNING;
        $audit->progress_percent = 5;
        $audit->progress_stage = 'starting';
        $audit->save();

        try {
            if (FormsAuthService::isEnabled($audit)) {
                app(FormsAuthService::class)->establishWithRetries($audit);
                $audit->refresh();
            }

            $this->updateProgress($audit, 15, 'onpage', 'Fetching page...');

            $html = null;
            if (! FormsAuthService::isEnabled($audit)) {
                $html = $this->fetchHtml($audit->normalized_url);
            }
            $headers = [];
            $statusCode = 200;
            $finalUrl = $audit->normalized_url;
            $response = null;

            if ($html === null) {
                $response = $this->fetchHtmlWithHttp($audit, $audit->normalized_url);
                if (!$response) {
                    throw new \RuntimeException('Failed to fetch page (timeout or error). Please check the URL and try again.');
                }
                $statusCode = $response->status();
                $finalUrl = $response->effectiveUri() ? (string) $response->effectiveUri() : $audit->normalized_url;
                $html = $response->body();
                $headers = [
                    'server' => $response->header('Server'),
                    'x_powered_by' => $response->header('X-Powered-By'),
                    'content_type' => $response->header('Content-Type'),
                    'x_robots_tag' => $response->header('X-Robots-Tag'),
                ];
                $this->cacheHtml($audit->normalized_url, $html);
            }

            $this->updateProgress($audit, 35, 'onpage', 'Analyzing on-page SEO...');

            $htmlSizeBytes = strlen($html);
            $parsed = PageParser::parse($html, $finalUrl, $audit->normalized_url, $headers);

            $flags = $audit->crawl_module_flags ?? [];
            $headerStore = null;
            if ((!empty($flags['custom_source_search_enabled']) || !empty($flags['custom_extraction_enabled'])) && $response) {
                $headerStore = [];
                foreach ($response->headers() as $hKey => $hVals) {
                    $headerStore[strtolower((string) $hKey)] = is_array($hVals) ? implode(', ', $hVals) : (string) $hVals;
                }
            }

            $visibleMain = null;
            if (!empty($flags['spelling_grammar_enabled'])) {
                $visibleMain = VisibleTextExtractor::extractFromHtml($html);
                $maxStore = (int) config('seo_audit.spelling.max_chars_stored', 96000);
                if (mb_strlen($visibleMain) > $maxStore) {
                    $visibleMain = mb_substr($visibleMain, 0, $maxStore);
                }
            }

            $visForCustom = '';
            if (!empty($flags['custom_source_search_enabled']) || !empty($flags['custom_extraction_enabled'])) {
                $visForCustom = $visibleMain ?? VisibleTextExtractor::extractFromHtml($html);
            }

            $authMeta = null;
            if (! empty($flags['forms_auth_enabled'])) {
                $audit->refresh();
                $authMeta = AuthCrawlMetadataBuilder::build(
                    $audit,
                    $statusCode,
                    (string) $finalUrl,
                    $parsed['title'] ?? null,
                    isset($parsed['word_count']) ? (int) $parsed['word_count'] : null
                );
            }

            $page = AuditPage::create([
                'audit_id' => $audit->id,
                'url' => $finalUrl,
                'status_code' => $statusCode,
                'auth_crawl_metadata' => $authMeta,
                'title' => $parsed['title'],
                'title_len' => $parsed['title_len'],
                'meta_description' => $parsed['meta_description'],
                'meta_len' => $parsed['meta_len'],
                'canonical_url' => $parsed['canonical_url'],
                'robots_meta' => $parsed['robots_meta'],
                'x_robots_tag' => $parsed['x_robots_tag'] ?? null,
                'h1_count' => $parsed['h1_count'],
                'h2_count' => $parsed['h2_count'],
                'h3_count' => $parsed['h3_count'],
                'content_excerpt' => $parsed['content_excerpt'] ?? null,
                'visible_main_text' => $visibleMain,
                'visible_text_length' => $parsed['visible_text_length'] ?? null,
                'word_count' => $parsed['word_count'],
                'images_total' => $parsed['images_total'],
                'images_missing_alt' => $parsed['images_missing_alt'],
                'internal_links_count' => $parsed['internal_links_count'],
                'external_links_count' => $parsed['external_links_count'],
                'og_present' => $parsed['og_present'],
                'twitter_cards_present' => $parsed['twitter_cards_present'],
                'schema_types' => $parsed['schema_types'],
                'html_size_bytes' => $htmlSizeBytes,
                'response_headers_json' => $headerStore,
            ]);

            if (!empty($flags['custom_source_search_enabled'])) {
                app(CustomSourceSearchService::class)->runCrawlScopes($audit, $page, $html, $headerStore ?? [], $visForCustom);
            }
            if (!empty($flags['custom_extraction_enabled'])) {
                app(CustomExtractionService::class)->runCrawlScopes($audit, $page, $html, $headerStore ?? [], $visForCustom);
            }

            if (app(JsRenderingService::class)->shouldRun($audit)) {
                app(JsRenderingService::class)->runForPages($audit, collect([$page]));
                $page->refresh();
            }
            if (!empty($flags['segmentation_enabled'])) {
                app(SegmentationService::class)->run($audit);
                $page->refresh();
            }

            if (!empty($flags['custom_source_search_enabled'])) {
                app(CustomSourceSearchService::class)->syncSegments($audit);
                app(CustomSourceSearchService::class)->runRenderedScope($audit);
            }
            if (!empty($flags['custom_extraction_enabled'])) {
                app(CustomExtractionService::class)->syncSegments($audit);
                app(CustomExtractionService::class)->runRenderedScope($audit);
            }

            $rulesEngine = new RulesEngine();
            $evaluation = $rulesEngine->evaluate($audit, $page);

            $categoryPenalties = $evaluation['categoryPenalties'];
            $categoryPenalties['technical'] += (int) $audit->issues()
                ->where('module_key', 'js_rendering')
                ->sum('score_penalty');
            if (!empty($flags['custom_source_search_enabled'])) {
                $categoryPenalties['technical'] += app(CustomSourceSearchService::class)->createIssues($audit);
            }
            if (!empty($flags['spelling_grammar_enabled'])) {
                $categoryPenalties['onpage'] += app(SpellingGrammarService::class)->run($audit);
            }
            if (! empty($flags['forms_auth_enabled'])) {
                $f = app(FormsAuthIssueService::class);
                $f->refreshStateFromPages($audit);
                $audit->refresh();
                $categoryPenalties['technical'] += $f->sync($audit);
            }

            $categoryScores = $rulesEngine->calculateCategoryScores($categoryPenalties);
            $overallScore = $rulesEngine->calculateOverallScore($categoryScores);
            $overallGrade = $rulesEngine->scoreToGrade($overallScore);

            $audit->refresh();
            $allIssues = $audit->issues()->get();
            $summary = [
                'total_issues' => $allIssues->count(),
                'high_impact_issues' => $allIssues->where('impact', 'high')->count(),
                'medium_impact_issues' => $allIssues->where('impact', 'medium')->count(),
                'low_impact_issues' => $allIssues->where('impact', 'low')->count(),
            ];

            $audit->overall_score = $overallScore;
            $audit->overall_grade = $overallGrade;
            $audit->category_scores = $categoryScores;
            $audit->summary = $summary;

            $kpiBuilder = new AuditKpiBuilder();
            $audit->audit_kpis = $this->sanitizeKpis($kpiBuilder->build($audit));
            $audit->category_grades = $audit->audit_kpis['overview']['category_grades'] ?? null;
            $audit->recommendations_count = $audit->audit_kpis['overview']['recommendations_count'] ?? $summary['total_issues'];
            $audit->report_modules = app(ReportModuleBuilder::class)->build($audit);

            if (FormsAuthService::isEnabled($audit)) {
                FormsAuthService::scrubSecrets($audit);
            }

            $audit->status = Audit::STATUS_COMPLETED;
            $audit->progress_percent = 100;
            $audit->progress_stage = 'completed';
            $audit->finished_at = now();
            $audit->save();

            $elapsed = round(microtime(true) - $start, 1);
            Log::info('AuditCoreJob completed', ['audit_id' => $audit->id, 'score' => $overallScore, 'elapsed_s' => $elapsed]);

            $this->dispatchEnrichments($audit);

            if ($audit->lead_email) {
                try {
                    \Illuminate\Support\Facades\Mail::to($audit->lead_email)
                        ->queue(new \App\Mail\UserAuditReadyMail($audit->fresh()));
                } catch (\Exception $e) {
                    Log::warning('Failed to queue audit email', ['audit_id' => $audit->id, 'error' => $e->getMessage()]);
                }
            }
        } catch (\Throwable $e) {
            Log::error('AuditCoreJob failed', [
                'audit_id' => $this->auditId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            Audit::whereKey($audit->id)->update([
                'status' => Audit::STATUS_FAILED,
                'error' => $e->getMessage(),
                'progress_stage' => 'failed',
                'finished_at' => now(),
            ]);
            throw $e;
        }
    }

    protected function fetchHtml(string $normalizedUrl): ?string
    {
        $key = 'audit_html:' . md5($normalizedUrl);
        return Cache::get($key);
    }

    protected function cacheHtml(string $normalizedUrl, string $html): void
    {
        $key = 'audit_html:' . md5($normalizedUrl);
        Cache::put($key, $html, now()->addMinutes(30));
    }

    /** HTTP fetch with 12s timeout, 1 retry. */
    protected function fetchHtmlWithHttp(Audit $audit, string $url)
    {
        try {
            return SeoAuditHttp::crawlGet($audit, $url)
                ->timeout(12)
                ->retry(1, 500)
                ->get($url);
        } catch (\Throwable $e) {
            Log::warning('AuditCoreJob HTTP fetch failed', ['url' => $url, 'error' => $e->getMessage()]);
            return null;
        }
    }

    protected function dispatchEnrichments(Audit $audit): void
    {
        try {
            EnrichPsiJob::dispatch($audit->id)->onConnection('database');
            EnrichGa4Job::dispatch($audit->id)->onConnection('database');
            EnrichGscJob::dispatch($audit->id)->onConnection('database');
        } catch (\Exception $e) {
            Log::warning('Enrichment jobs dispatch failed', ['audit_id' => $audit->id, 'error' => $e->getMessage()]);
        }
    }

    protected function updateProgress(Audit $audit, int $percent, string $stage, string $logMessage): void
    {
        $audit->progress_percent = $percent;
        $audit->progress_stage = $stage;
        $audit->save();
        Log::info($logMessage, ['audit_id' => $audit->id]);
    }

    public function failed(\Throwable $e): void
    {
        $audit = Audit::find($this->auditId);
        if ($audit && $audit->status !== Audit::STATUS_COMPLETED) {
            if (! empty($audit->crawl_module_flags['forms_auth_enabled'])) {
                FormsAuthService::scrubSecrets($audit);
                $audit->save();
            }
            Audit::whereKey($audit->id)->update([
                'status' => Audit::STATUS_FAILED,
                'error' => 'Job failed: ' . $e->getMessage(),
                'progress_stage' => 'failed',
                'finished_at' => now(),
            ]);
        }
    }

    protected function sanitizeKpis(array $kpis): array
    {
        return app(AuditKpiSanitizer::class)->sanitize($kpis);
    }
}
