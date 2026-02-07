<?php

namespace App\Jobs;

use App\Models\Audit;
use App\Models\AuditLink;
use App\Models\AuditPage;
use App\Jobs\RunPageSpeedJob;
use App\Services\SeoAudit\RulesEngine;
use App\Services\SeoAudit\AuditKpiBuilder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FinalizeAuditJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes for large audits
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
            // Validate broken links
            $this->validateLinks($audit);

            // Run Phase 2 rules engine
            $rulesEngine = new RulesEngine();
            $evaluation = $rulesEngine->evaluateCollection($audit);

            // Calculate scores
            $categoryScores = $rulesEngine->calculateCategoryScores($evaluation['categoryPenalties']);
            $overallScore = $rulesEngine->calculateOverallScore($categoryScores);
            $overallGrade = $rulesEngine->scoreToGrade($overallScore);

            // Calculate crawl stats
            $crawlStats = $this->calculateCrawlStats($audit);

            // Create summary
            $summary = [
                'total_issues' => count($evaluation['issues']),
                'high_impact_issues' => collect($evaluation['issues'])->where('impact', 'high')->count(),
                'medium_impact_issues' => collect($evaluation['issues'])->where('impact', 'medium')->count(),
                'low_impact_issues' => collect($evaluation['issues'])->where('impact', 'low')->count(),
                'pages_scanned' => $audit->pages_scanned,
                'pages_discovered' => $audit->pages_discovered,
            ];

            // Update audit
            $audit->overall_score = $overallScore;
            $audit->overall_grade = $overallGrade;
            $audit->category_scores = $categoryScores;
            $audit->summary = $summary;
            $audit->crawl_stats = $crawlStats;
            $audit->progress_percent = 100;

            // Build KPI payload
            $kpiBuilder = new AuditKpiBuilder();
            $audit->audit_kpis = $kpiBuilder->build($audit);
            $audit->category_grades = $audit->audit_kpis['overview']['category_grades'] ?? null;
            $audit->recommendations_count = $audit->audit_kpis['overview']['recommendations_count'] ?? $summary['total_issues'];

            $audit->status = Audit::STATUS_COMPLETED;
            $audit->finished_at = now();
            $audit->save();

            $organization = $audit->organization;
            $hasSharedKey = (bool) config('services.google.pagespeed_api_key');
            $hasByokKey = $organization
                && $organization->pagespeed_byok_enabled
                && $organization->pagespeed_last_key_verified_at
                && $organization->pagespeed_api_key_encrypted;

            if ($organization && ($organization->plan_key ?? 'free') !== 'free' && ($hasSharedKey || $hasByokKey)) {
                $pages = AuditPage::where('audit_id', $audit->id)
                    ->where('url', '!=', $audit->normalized_url)
                    ->orderByDesc('internal_links_count')
                    ->limit(3)
                    ->get();

                foreach ($pages as $page) {
                    RunPageSpeedJob::dispatch($audit->id, $page->url)
                        ->onQueue('integrations')
                        ->delay(now()->addSeconds(10));
                }
            }

            // Dispatch performance audit batch job for Phase 3
            RunPerformanceAuditBatchJob::dispatch($audit->id)
                ->delay(now()->addSeconds(5));

        } catch (\Exception $e) {
            Log::error("FinalizeAuditJob failed: {$e->getMessage()}", [
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
     * Validate links and mark broken ones
     */
    protected function validateLinks(Audit $audit): void
    {
        // Get internal links that haven't been validated yet
        $internalLinks = AuditLink::where('audit_id', $audit->id)
            ->where('type', AuditLink::TYPE_INTERNAL)
            ->whereNull('status_code')
            ->get();

        foreach ($internalLinks as $link) {
            // Check if target URL was crawled (has a page)
            $targetPage = AuditPage::where('audit_id', $audit->id)
                ->where('url', $link->to_url_normalized)
                ->first();

            if ($targetPage) {
                // Link is valid (page exists)
                $link->status_code = $targetPage->status_code ?? 200;
                $link->is_broken = ($targetPage->status_code >= 400);
                $link->save();
            } else {
                // Do a light HEAD request to validate
                try {
                    $response = Http::timeout(10)
                        ->head($link->to_url);

                    $link->status_code = $response->status();
                    $link->final_url = $response->effectiveUri() ?? $link->to_url;
                    $link->redirect_hops = count($response->redirectHistory() ?? []);
                    $link->is_broken = ($response->status() >= 400);
                    $link->save();
                } catch (\Exception $e) {
                    // Mark as broken
                    $link->is_broken = true;
                    $link->error = $e->getMessage();
                    $link->save();
                }
            }
        }

        // Validate external links (limited sample)
        $externalLinks = AuditLink::where('audit_id', $audit->id)
            ->where('type', AuditLink::TYPE_EXTERNAL)
            ->whereNull('status_code')
            ->limit(20) // Only validate first 20 external links
            ->get();

        foreach ($externalLinks as $link) {
            try {
                $response = Http::timeout(10)
                    ->head($link->to_url);

                $link->status_code = $response->status();
                $link->final_url = $response->effectiveUri() ?? $link->to_url;
                $link->redirect_hops = count($response->redirectHistory() ?? []);
                $link->is_broken = ($response->status() >= 400);
                $link->save();
            } catch (\Exception $e) {
                // Mark as broken
                $link->is_broken = true;
                $link->error = $e->getMessage();
                $link->save();
            }
        }
    }

    /**
     * Calculate crawl statistics
     */
    protected function calculateCrawlStats(Audit $audit): array
    {
        $brokenLinksCount = AuditLink::where('audit_id', $audit->id)
            ->where('is_broken', true)
            ->count();

        $redirectChainCount = AuditLink::where('audit_id', $audit->id)
            ->where('redirect_hops', '>=', 2)
            ->count();

        // Duplicate titles
        $duplicateTitles = AuditPage::where('audit_id', $audit->id)
            ->whereNotNull('title')
            ->selectRaw('title, COUNT(*) as count')
            ->groupBy('title')
            ->having('count', '>', 1)
            ->get();
        $duplicateTitlesGroups = $duplicateTitles->count();

        // Duplicate meta descriptions
        $duplicateMeta = AuditPage::where('audit_id', $audit->id)
            ->whereNotNull('meta_description')
            ->selectRaw('meta_description, COUNT(*) as count')
            ->groupBy('meta_description')
            ->having('count', '>', 1)
            ->get();
        $duplicateMetaGroups = $duplicateMeta->count();

        // Orphan pages (simplified - pages from sitemap that weren't crawled)
        $orphanPagesCount = 0; // Will be calculated if sitemap was used

        return [
            'broken_links_count' => $brokenLinksCount,
            'redirect_chain_count' => $redirectChainCount,
            'duplicate_titles_groups' => $duplicateTitlesGroups,
            'duplicate_meta_groups' => $duplicateMetaGroups,
            'orphan_pages_count' => $orphanPagesCount,
            'pages_scanned' => $audit->pages_scanned,
            'pages_discovered' => $audit->pages_discovered,
        ];
    }
}
