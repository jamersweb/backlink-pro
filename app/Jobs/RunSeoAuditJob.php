<?php

namespace App\Jobs;

use App\Models\Audit;
use App\Models\AuditPage;
use App\Models\ConnectedAccount;
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
use App\Services\Google\PageSpeedService;
use App\Services\Google\SearchConsoleService;
use App\Services\Google\Ga4Service;
use App\Jobs\StartAuditPipelineJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RunSeoAuditJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;
    public $tries = 2;

    public function __construct(
        public int $auditId
    ) {}

    public function handle(): void
    {
        Log::info('AuditJob started', ['audit_id' => $this->auditId]);
        
        $audit = Audit::find($this->auditId);
        if (!$audit) {
            Log::error('AuditJob: audit not found', ['audit_id' => $this->auditId]);
            return;
        }

        $audit->status = Audit::STATUS_RUNNING;
        $audit->progress_percent = 5;
        $audit->progress_stage = 'starting';
        $audit->save();

        try {
            $kpis = $audit->audit_kpis ?? [];

            // Stage 1: Fetch & parse homepage
            $this->updateProgress($audit, 10, 'onpage', 'Analyzing on-page SEO...');
            $this->handleSinglePage($audit);
            
            // Stage 2: PageSpeed Insights
            $this->updateProgress($audit, 35, 'psi', 'Running PageSpeed Insights...');
            $this->runPageSpeed($audit, $kpis);
            
            // Stage 3: Google Analytics (if connected)
            $this->updateProgress($audit, 55, 'ga4', 'Fetching Google Analytics data...');
            $this->runGa4($audit, $kpis);
            
            // Stage 4: Google Search Console (if connected)
            $this->updateProgress($audit, 70, 'gsc', 'Fetching Search Console data...');
            $this->runGsc($audit, $kpis);
            
            // Stage 5: Compile final report
            $this->updateProgress($audit, 85, 'compiling', 'Compiling report...');
            
            // Persist all KPIs
            $audit->refresh();
            $mergedKpis = array_merge($audit->audit_kpis ?? [], $kpis);
            $audit->audit_kpis = $this->sanitizeKpis($mergedKpis);
            $audit->status = Audit::STATUS_COMPLETED;
            $audit->progress_percent = 100;
            $audit->progress_stage = 'completed';
            $audit->finished_at = now();
            $audit->save();
            
            if ($audit->lead_email) {
                try {
                    \Illuminate\Support\Facades\Mail::to($audit->lead_email)
                        ->queue(new \App\Mail\UserAuditReadyMail($audit));
                } catch (\Exception $e) {
                    Log::warning('Failed to queue audit email', ['audit_id' => $audit->id, 'error' => $e->getMessage()]);
                }
            }
            
            Log::info('RunSeoAuditJob completed', [
                'audit_id' => $audit->id,
                'score' => $audit->overall_score,
            ]);

            \App\Services\AI\PostAuditAiJobDispatcher::dispatchForAudit($audit->id);
            
        } catch (\Exception $e) {
            Log::error('AuditJob failed', [
                'audit_id' => $this->auditId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if (! empty($audit->crawl_module_flags['forms_auth_enabled'])) {
                FormsAuthService::scrubSecrets($audit);
                $audit->save();
            }

            Audit::whereKey($audit->id)->update([
                'status' => Audit::STATUS_FAILED,
                'error' => $e->getMessage(),
                'progress_stage' => 'failed',
                'finished_at' => now(),
            ]);
            
            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('AuditJob failed (queue)', [
            'audit_id' => $this->auditId,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

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

    protected function updateProgress(Audit $audit, int $percent, string $stage, string $logMessage): void
    {
        $audit->progress_percent = $percent;
        $audit->progress_stage = $stage;
        $audit->save();
        Log::info($logMessage, ['audit_id' => $audit->id]);
    }

    protected function handleSinglePage(Audit $audit): void
    {
        if (FormsAuthService::isEnabled($audit)) {
            app(FormsAuthService::class)->establishWithRetries($audit);
            $audit->refresh();
        }

        $response = SeoAuditHttp::browserLikeGet($audit, $audit->normalized_url)
            ->get($audit->normalized_url);

        $this->assertFetchableResponse($response, $audit->normalized_url);

        $statusCode = $response->status();
        $finalUrl = $response->effectiveUri() ?? $audit->normalized_url;
        $html = $response->body();
        $htmlSizeBytes = strlen($html);

        $headers = [
            'server' => $response->header('Server'),
            'x_powered_by' => $response->header('X-Powered-By'),
            'content_type' => $response->header('Content-Type'),
            'x_robots_tag' => $response->header('X-Robots-Tag'),
        ];
        $parsed = PageParser::parse($html, $finalUrl, $audit->normalized_url, $headers);

        $flags = $audit->crawl_module_flags ?? [];
        $headerStore = null;
        if (!empty($flags['custom_source_search_enabled']) || !empty($flags['custom_extraction_enabled'])) {
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
        if (FormsAuthService::isEnabled($audit)) {
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
        if (!empty(($audit->crawl_module_flags ?? [])['segmentation_enabled'])) {
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
        $audit->save();
    }

    protected function assertFetchableResponse($response, string $url): void
    {
        $status = $response->status();
        $body = strtolower(substr((string) $response->body(), 0, 4000));

        if ($status >= 200 && $status < 400 && !str_contains($body, 'just a moment')) {
            return;
        }

        $host = parse_url($url, PHP_URL_HOST) ?: $url;

        if (
            $status === 403 &&
            (
                str_contains($body, 'just a moment') ||
                str_contains($body, 'cloudflare') ||
                str_contains($body, 'attention required')
            )
        ) {
            throw new \RuntimeException("This website is protected by bot/security checks and blocked the audit request. Please try auditing your own site or a URL without Cloudflare-style protection. ({$host})");
        }

        throw new \RuntimeException("The website blocked the audit request with HTTP {$status}. Please try another URL or a page that allows automated analysis. ({$host})");
    }

    protected function runPageSpeed(Audit $audit, array &$kpis): void
    {
        $hasKey = (bool) config('services.google.pagespeed_api_key');
        if (!$hasKey) {
            Log::info('PageSpeed API key not configured, skipping', ['audit_id' => $audit->id]);
            $kpis['google']['pagespeed'] = ['status' => 'skipped', 'error' => 'API key not configured'];
            return;
        }

        try {
            $service = new PageSpeedService();
            $mobile = $service->run($audit->normalized_url, 'mobile', $audit->organization);
            $desktop = $service->run($audit->normalized_url, 'desktop', $audit->organization);

            $kpis['google']['pagespeed'] = [
                'url' => $audit->normalized_url,
                'mobile' => $mobile,
                'desktop' => $desktop,
                'source' => $mobile['source'] ?? 'shared_key',
                'fetched_at' => $mobile['fetched_at'] ?? now()->toIso8601String(),
            ];
            
            // Also store on AuditPage for Lighthouse data
            $page = $audit->pages()->first();
            if ($page) {
                $page->lighthouse_mobile = $mobile['kpis'] ?? null;
                $page->lighthouse_desktop = $desktop['kpis'] ?? null;
                $page->save();
            }
        } catch (\Exception $e) {
            Log::warning('PageSpeed failed, continuing', ['audit_id' => $audit->id, 'error' => $e->getMessage()]);
            $kpis['google']['pagespeed'] = ['status' => 'failed', 'error' => $e->getMessage()];
        }
    }

    protected function runGa4(Audit $audit, array &$kpis): void
    {
        if (!$audit->user_id) return;

        $account = ConnectedAccount::where('user_id', $audit->user_id)
            ->where('provider', 'google')
            ->where('service', 'seo')
            ->where('status', 'active')
            ->first();

        if (!$account) {
            $kpis['ga4'] = ['connected' => false, 'message' => 'Google Analytics not connected'];
            return;
        }

        try {
            $ga4 = new Ga4Service($account);
            $properties = $ga4->listProperties();
            
            if (empty($properties)) {
                $kpis['ga4'] = ['connected' => true, 'message' => 'No GA4 properties found', 'data' => null];
                return;
            }

            $selectedPropertyId = data_get($audit->audit_kpis, 'ga4.selected_property_id')
                ?: data_get($audit->audit_kpis, 'ga4.property_id');
            $propertyId = $selectedPropertyId ?: ($properties[0]['propertyName'] ?? null);
            if ($propertyId && !str_starts_with($propertyId, 'properties/')) {
                $propertyId = 'properties/' . $propertyId;
            }
            if (!$propertyId) {
                $kpis['ga4'] = ['connected' => true, 'message' => 'No GA4 property selected', 'data' => null];
                return;
            }

            $selectedProperty = collect($properties)->first(function ($property) use ($propertyId) {
                return ($property['propertyName'] ?? null) === $propertyId;
            }) ?: $properties[0];

            $endDate = new \DateTime('now');
            $startDate = (clone $endDate)->modify('-' . $this->resolveReportPeriodDays($audit) . ' days');

            $dailyMetrics = $ga4->runDailyReport($propertyId, $startDate, $endDate);
            
            $landingPages = [];
            try {
                $landingPages = $ga4->runLandingPagesReport($propertyId, $startDate, $endDate, 20);
            } catch (\Exception $e) {
                Log::warning('GA4 landing pages failed', ['error' => $e->getMessage()]);
            }
            $topSources = [];
            try {
                $topSources = $ga4->runTopSourcesReport($propertyId, $startDate, $endDate, 20);
            } catch (\Exception $e) {
                Log::warning('GA4 top sources failed', ['error' => $e->getMessage()]);
            }

            $totalSessions = array_sum(array_column($dailyMetrics, 'sessions'));
            $totalUsers = array_sum(array_column($dailyMetrics, 'total_users'));

            $kpis['ga4'] = [
                'connected' => true,
                'property' => $selectedProperty['displayName'] ?? $propertyId,
                'property_id' => $propertyId,
                'selected_property_id' => $propertyId,
                'period' => $startDate->format('Y-m-d') . ' to ' . $endDate->format('Y-m-d'),
                'summary' => [
                    'total_sessions' => $totalSessions,
                    'total_users' => $totalUsers,
                    'avg_engagement_rate' => !empty($dailyMetrics) ? round(array_sum(array_column($dailyMetrics, 'engagement_rate')) / count($dailyMetrics) * 100, 1) : 0,
                ],
                'daily' => $dailyMetrics,
                'top_pages' => $landingPages,
                'top_sources' => $topSources,
            ];
        } catch (\Exception $e) {
            Log::warning('GA4 integration failed, continuing', ['audit_id' => $audit->id, 'error' => $e->getMessage()]);
            $kpis['ga4'] = ['connected' => true, 'error' => $e->getMessage(), 'data' => null];
        }
    }

    protected function runGsc(Audit $audit, array &$kpis): void
    {
        if (!$audit->user_id) return;

        $account = ConnectedAccount::where('user_id', $audit->user_id)
            ->where('provider', 'google')
            ->where('service', 'seo')
            ->where('status', 'active')
            ->first();

        if (!$account) {
            $kpis['gsc'] = ['connected' => false, 'message' => 'Search Console not connected'];
            return;
        }

        try {
            $gsc = new SearchConsoleService($account);
            $sites = $gsc->listSites();
            
            // Find matching site URL
            $auditHost = parse_url($audit->normalized_url, PHP_URL_HOST);
            $siteUrl = null;
            foreach ($sites as $site) {
                $siteHost = parse_url($site['siteUrl'], PHP_URL_HOST) ?? str_replace('sc-domain:', '', $site['siteUrl']);
                if ($siteHost === $auditHost || str_contains($site['siteUrl'], $auditHost)) {
                    $siteUrl = $site['siteUrl'];
                    break;
                }
            }
            
            if (!$siteUrl && !empty($sites)) {
                $siteUrl = $sites[0]['siteUrl'];
            }

            if (!$siteUrl) {
                $kpis['gsc'] = ['connected' => true, 'message' => 'No matching Search Console property found', 'data' => null];
                return;
            }

            $endDate = new \DateTime('now');
            $startDate = (clone $endDate)->modify('-' . $this->resolveReportPeriodDays($audit) . ' days');

            $dailyMetrics = $gsc->fetchDailyMetrics($siteUrl, $startDate, $endDate);
            $topQueries = $gsc->fetchTopQueries($siteUrl, $startDate, $endDate, 20);
            $topPages = $gsc->fetchTopPages($siteUrl, $startDate, $endDate, 20);

            $totalClicks = array_sum(array_column($dailyMetrics, 'clicks'));
            $totalImpressions = array_sum(array_column($dailyMetrics, 'impressions'));
            $avgPosition = !empty($dailyMetrics) ? round(array_sum(array_column($dailyMetrics, 'position')) / count($dailyMetrics), 1) : 0;
            $avgCtr = $totalImpressions > 0 ? round(($totalClicks / $totalImpressions) * 100, 2) : 0;

            $kpis['gsc'] = [
                'connected' => true,
                'site_url' => $siteUrl,
                'period' => $startDate->format('Y-m-d') . ' to ' . $endDate->format('Y-m-d'),
                'summary' => [
                    'total_clicks' => $totalClicks,
                    'total_impressions' => $totalImpressions,
                    'avg_ctr' => $avgCtr,
                    'avg_position' => $avgPosition,
                ],
                'daily' => $dailyMetrics,
                'top_queries' => $topQueries,
                'top_pages' => $topPages,
            ];
        } catch (\Exception $e) {
            Log::warning('GSC integration failed, continuing', ['audit_id' => $audit->id, 'error' => $e->getMessage()]);
            $kpis['gsc'] = ['connected' => true, 'error' => $e->getMessage(), 'data' => null];
        }
    }

    protected function sanitizeKpis(array $kpis): array
    {
        return app(AuditKpiSanitizer::class)->sanitize($kpis);
    }

    protected function resolveReportPeriodDays(Audit $audit): int
    {
        $audit->loadMissing('organization.brandingProfile');

        $days = (int) ($audit->organization?->brandingProfile?->report_period_days ?: 30);

        return in_array($days, [7, 15, 30], true) ? $days : 30;
    }
}
