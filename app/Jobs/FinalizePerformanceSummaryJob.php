<?php

namespace App\Jobs;

use App\Models\Audit;
use App\Models\AuditPage;
use App\Services\SeoAudit\RulesEngine;
use App\Services\SeoAudit\AuditKpiBuilder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FinalizePerformanceSummaryJob implements ShouldQueue
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
            // Calculate performance summary
            $performanceSummary = $this->calculatePerformanceSummary($audit);
            $audit->performance_summary = $performanceSummary;
            
            // Run Phase 3 rules (Performance + Security)
            $rulesEngine = new RulesEngine();
            $phase3Evaluation = $rulesEngine->evaluatePhase3($audit);

            // Update category scores with performance data
            $categoryScores = $audit->category_scores ?? [];
            
            // Set performance score (from summary)
            if (isset($performanceSummary['performance_score'])) {
                $categoryScores['performance'] = $performanceSummary['performance_score'];
            } else {
                $categoryScores['performance'] = 70; // Default
            }
            
            // Apply performance penalties
            if (isset($phase3Evaluation['categoryPenalties']['performance'])) {
                $categoryScores['performance'] = max(0, $categoryScores['performance'] - $phase3Evaluation['categoryPenalties']['performance']);
            }
            
            // Apply security penalties (start from 100, subtract penalties)
            $categoryScores['security'] = 100;
            if (isset($phase3Evaluation['categoryPenalties']['security'])) {
                $categoryScores['security'] = max(0, 100 - $phase3Evaluation['categoryPenalties']['security']);
            }
            
            $audit->category_scores = $categoryScores;

            // Recalculate overall score
            $overallScore = $rulesEngine->calculateOverallScore($categoryScores);
            $overallGrade = $rulesEngine->scoreToGrade($overallScore);
            
            $audit->overall_score = $overallScore;
            $audit->overall_grade = $overallGrade;

            // Rebuild KPI payload after performance updates
            $kpiBuilder = new AuditKpiBuilder();
            $audit->audit_kpis = $kpiBuilder->build($audit);
            $audit->category_grades = $audit->audit_kpis['overview']['category_grades'] ?? $audit->category_grades;
            $audit->recommendations_count = $audit->audit_kpis['overview']['recommendations_count'] ?? $audit->recommendations_count;

            
            // Generate public summary if gated
            if ($audit->is_gated) {
                $publicSummary = $this->generatePublicSummary($audit);
                $audit->public_summary = $publicSummary;
            }

            // Mark audit as completed
            $audit->status = Audit::STATUS_COMPLETED;
            $audit->finished_at = now();
            $audit->progress_percent = 100;
            $audit->save();

            // Send email to lead if exists
            if ($audit->lead_email) {
                try {
                    \Illuminate\Support\Facades\Mail::to($audit->lead_email)
                        ->send(new \App\Mail\AuditReadyMail($audit));
                } catch (\Exception $e) {
                    Log::warning("Failed to send audit ready email: {$e->getMessage()}");
                }
            }

            // Build knowledge chunks for RAG
            try {
                $ragRetriever = new \App\Services\AI\RAGRetriever(new \App\Services\AI\LLMClient());
                $ragRetriever->buildChunks($audit);
            } catch (\Exception $e) {
                Log::warning("Failed to build knowledge chunks: {$e->getMessage()}");
            }

            // Trigger AI generation
            \App\Jobs\GenerateAiReportSummaryJob::dispatch($audit->id)
                ->delay(now()->addSeconds(5));
            
            \App\Jobs\GenerateAiFixPlanJob::dispatch($audit->id)
                ->delay(now()->addSeconds(10));

            // If this audit was triggered by a monitor, compare snapshots
            if ($audit->monitor_id) {
                \App\Jobs\CompareSnapshotsAndAlertJob::dispatch($audit->monitor_id, $audit->id)
                    ->delay(now()->addSeconds(15));
            }

        } catch (\Exception $e) {
            Log::error("FinalizePerformanceSummaryJob failed: {$e->getMessage()}", [
                'audit_id' => $this->auditId,
                'exception' => $e,
            ]);
        }
    }

    /**
     * Calculate performance summary
     */
    protected function calculatePerformanceSummary(Audit $audit): array
    {
        $pages = AuditPage::where('audit_id', $audit->id)
            ->whereNotNull('performance_metrics')
            ->get();

        $mobileScores = [];
        $desktopScores = [];
        $worstLcp = null;
        $worstLcpPage = null;
        $totalBytes = 0;

        foreach ($pages as $page) {
            $metrics = $page->performance_metrics ?? [];
            
            if (isset($metrics['mobile']['score'])) {
                $mobileScores[] = $metrics['mobile']['score'];
            }
            
            if (isset($metrics['desktop']['score'])) {
                $desktopScores[] = $metrics['desktop']['score'];
            }

            // Track worst LCP
            if (isset($metrics['mobile']['lcp'])) {
                $lcp = $metrics['mobile']['lcp'];
                if ($worstLcp === null || $lcp > $worstLcp) {
                    $worstLcp = $lcp;
                    $worstLcpPage = $page->url;
                }
            }

            // Calculate total assets size for this page
            $pageAssets = $page->assets()->sum('size_bytes');
            if ($pageAssets > $totalBytes) {
                $totalBytes = $pageAssets;
            }
        }

        $mobileAvg = !empty($mobileScores) ? round(array_sum($mobileScores) / count($mobileScores)) : null;
        $desktopAvg = !empty($desktopScores) ? round(array_sum($desktopScores) / count($desktopScores)) : null;

        // Calculate performance score (70% mobile, 30% desktop)
        $performanceScore = null;
        if ($mobileAvg !== null && $desktopAvg !== null) {
            $performanceScore = round($mobileAvg * 0.7 + $desktopAvg * 0.3);
        } elseif ($mobileAvg !== null) {
            $performanceScore = $mobileAvg;
        } elseif ($desktopAvg !== null) {
            $performanceScore = $desktopAvg;
        }

        return [
            'mobile_avg_score' => $mobileAvg,
            'desktop_avg_score' => $desktopAvg,
            'performance_score' => $performanceScore,
            'worst_lcp' => $worstLcp,
            'worst_lcp_page' => $worstLcpPage,
            'total_bytes_top_page' => $totalBytes,
        ];
    }

    /**
     * Generate public summary for gated reports
     */
    protected function generatePublicSummary(Audit $audit): array
    {
        $topIssues = $audit->issues()
            ->orderByRaw("FIELD(impact, 'high', 'medium', 'low')")
            ->orderBy('score_penalty', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($issue) => [
                'title' => $issue->title,
                'impact' => $issue->impact,
            ])
            ->toArray();

        $performanceMetrics = null;
        if ($audit->performance_summary) {
            $perf = $audit->performance_summary;
            $performanceMetrics = [
                'mobile_score' => $perf['mobile_avg_score'] ?? null,
                'desktop_score' => $perf['desktop_avg_score'] ?? null,
                'worst_lcp' => $perf['worst_lcp'] ?? null,
            ];
        }

        return [
            'overall_score' => $audit->overall_score,
            'overall_grade' => $audit->overall_grade,
            'category_scores' => $audit->category_scores,
            'top_issues' => $topIssues,
            'performance_metrics' => $performanceMetrics,
        ];
    }
}
