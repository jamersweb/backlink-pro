<?php

namespace App\Jobs;

use App\Models\Audit;
use App\Models\AuditPage;
use App\Services\SeoAudit\RulesEngine;
use App\Services\SeoAudit\PageParser;
use App\Services\SeoAudit\AuditKpiBuilder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * FAST core audit only: URL fetch, on-page checks, scoring, issues.
 * Target: complete in under 60 seconds. Enrichments (PSI/GA4/GSC) run in separate jobs.
 */
class RunSeoAuditCoreJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 90;

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
            $this->updateProgress($audit, 15, 'onpage', 'Fetching page...');

            $html = $this->fetchHtml($audit->normalized_url);
            $headers = [];
            $statusCode = 200;
            $finalUrl = $audit->normalized_url;

            if ($html === null) {
                $response = $this->fetchHtmlWithHttp($audit->normalized_url);
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

            $rulesEngine = new RulesEngine();
            $evaluation = $rulesEngine->evaluate($audit, $page);

            $categoryScores = $rulesEngine->calculateCategoryScores($evaluation['categoryPenalties']);
            $overallScore = $rulesEngine->calculateOverallScore($categoryScores);
            $overallGrade = $rulesEngine->scoreToGrade($overallScore);

            $summary = [
                'total_issues' => count($evaluation['issues']),
                'high_impact_issues' => collect($evaluation['issues'])->where('impact', 'high')->count(),
                'medium_impact_issues' => collect($evaluation['issues'])->where('impact', 'medium')->count(),
                'low_impact_issues' => collect($evaluation['issues'])->where('impact', 'low')->count(),
            ];

            $audit->overall_score = $overallScore;
            $audit->overall_grade = $overallGrade;
            $audit->category_scores = $categoryScores;
            $audit->summary = $summary;

            $kpiBuilder = new AuditKpiBuilder();
            $audit->audit_kpis = $kpiBuilder->build($audit);
            $audit->category_grades = $audit->audit_kpis['overview']['category_grades'] ?? null;
            $audit->recommendations_count = $audit->audit_kpis['overview']['recommendations_count'] ?? $summary['total_issues'];

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
            $audit->status = Audit::STATUS_FAILED;
            $audit->error = $e->getMessage();
            $audit->progress_stage = 'failed';
            $audit->finished_at = now();
            $audit->save();
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
    protected function fetchHtmlWithHttp(string $url)
    {
        try {
            return Http::timeout(12)
                ->retry(1, 500)
                ->withUserAgent('BacklinkProBot/1.0')
                ->withOptions([
                    'allow_redirects' => [
                        'max' => 5,
                        'strict' => true,
                        'referer' => true,
                        'protocols' => ['http', 'https'],
                    ],
                ])
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
            $audit->status = Audit::STATUS_FAILED;
            $audit->error = 'Job failed: ' . $e->getMessage();
            $audit->progress_stage = 'failed';
            $audit->finished_at = now();
            $audit->save();
        }
    }
}
