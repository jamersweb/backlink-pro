<?php

namespace App\Jobs\Audits;

use App\Models\DomainAudit;
use App\Services\Audits\IssueRulesEngine;
use App\Services\System\ActivityLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FinalizeDomainAuditJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 1;
    public $queue = 'audits';

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
        $audit = DomainAudit::findOrFail($this->auditId);

        try {
            // Aggregate counts
            $pagesCount = $audit->pages()->count();
            $criticalCount = $audit->issues()->where('severity', 'critical')->count();
            $warningCount = $audit->issues()->where('severity', 'warning')->count();
            $infoCount = $audit->issues()->where('severity', 'info')->count();

            // Check for duplicate titles
            $duplicateTitleIssues = IssueRulesEngine::checkDuplicateTitles($this->auditId);
            foreach ($duplicateTitleIssues as $issue) {
                $audit->issues()->create($issue);
                $infoCount++;
            }

            // Calculate health score
            $healthScore = 100;
            $healthScore -= min($criticalCount * 8, 60); // Cap at -60
            $healthScore -= min($warningCount * 3, 30); // Cap at -30
            $healthScore -= min($infoCount * 1, 10); // Cap at -10
            $healthScore = max(0, min(100, $healthScore)); // Clamp 0-100

            // Update audit
            $audit->update([
                'status' => DomainAudit::STATUS_COMPLETED,
                'finished_at' => now(),
                'health_score' => $healthScore,
                'summary_json' => [
                    'pages_crawled' => $pagesCount,
                    'issues_critical' => $criticalCount,
                    'issues_warning' => $warningCount,
                    'issues_info' => $infoCount,
                ],
            ]);

            // If CWV is enabled, fetch PageSpeed metrics for top pages
            $settings = $audit->settings_json ?? [];
            if ($settings['include_cwv'] ?? false) {
                // Get home page + first 5 pages
                $topPages = $audit->pages()
                    ->where('status_code', 200)
                    ->orderByRaw('CASE WHEN url = ? THEN 0 ELSE 1 END', [$audit->domain->url ?? 'https://' . $audit->domain->host])
                    ->limit(6)
                    ->get();

                foreach ($topPages as $page) {
                    \App\Jobs\Audits\FetchPageSpeedJob::dispatch($this->auditId, $page->url, 'mobile');
                    \App\Jobs\Audits\FetchPageSpeedJob::dispatch($this->auditId, $page->url, 'desktop');
                }
            }

            // Consume pages quota
            $quotaService = app(\App\Services\Usage\QuotaService::class);
            $quotaService->consume($audit->user, 'audits.pages_per_month', $pagesCount, 'month', [
                'audit_id' => $this->auditId,
                'domain_id' => $audit->domain_id,
            ]);

            // Log completion
            $logger = app(ActivityLogger::class);
            $logger->success(
                'audits',
                'completed',
                "Audit completed: {$pagesCount} pages, health score {$healthScore}",
                $audit->user_id,
                $audit->domain_id,
                ['audit_id' => $this->auditId, 'pages_crawled' => $pagesCount, 'health_score' => $healthScore]
            );

            Log::info('Domain audit finalized', [
                'audit_id' => $this->auditId,
                'health_score' => $healthScore,
                'pages_crawled' => $pagesCount,
            ]);
        } catch (\Exception $e) {
            $audit->update([
                'status' => DomainAudit::STATUS_FAILED,
                'error_message' => 'Finalization failed: ' . $e->getMessage(),
                'finished_at' => now(),
            ]);

            $logger = app(ActivityLogger::class);
            $logger->logJobFailure(
                'audits',
                'FinalizeDomainAuditJob',
                $e,
                $audit->domain_id,
                $audit->user_id,
                ActivityLogger::runRef('audits', $this->auditId),
                ['audit_id' => $this->auditId]
            );

            Log::error('Domain audit finalization failed', [
                'audit_id' => $this->auditId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
