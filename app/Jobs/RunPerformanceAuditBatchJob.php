<?php

namespace App\Jobs;

use App\Models\Audit;
use App\Models\AuditPage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RunPerformanceAuditBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600;
    public $tries = 1;
    public $queue = 'lighthouse'; // Use dedicated queue for Lighthouse jobs

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
            // Get lighthouse pages limit from plan
            $organization = $audit->organization;
            $planLimiter = new \App\Services\Billing\PlanLimiter();
            $maxLighthousePages = $organization 
                ? $planLimiter->maxLighthousePages($organization)
                : 1; // Default for audits without org
            
            // Select key pages (homepage + top N by internal links)
            $keyPages = $this->selectKeyPages($audit, $maxLighthousePages);

            if ($keyPages->isEmpty()) {
                Log::info("No key pages found for audit {$this->auditId}");
                return;
            }

            // Dispatch jobs for each key page
            foreach ($keyPages as $page) {
                // Run Lighthouse (mobile + desktop)
                RunLighthouseJob::dispatch($audit->id, $page->id, 'mobile')
                    ->onQueue('lighthouse');
                
                RunLighthouseJob::dispatch($audit->id, $page->id, 'desktop')
                    ->onQueue('lighthouse')
                    ->delay(now()->addSeconds(5)); // Stagger desktop runs

                // Collect assets
                CollectAssetsJob::dispatch($audit->id, $page->id)
                    ->delay(now()->addSeconds(10));

                // Check security headers
                CheckSecurityHeadersJob::dispatch($audit->id, $page->id)
                    ->delay(now()->addSeconds(15));
            }

            // Dispatch finalize job after all performance jobs complete
            // Estimate: 2 minutes per page (mobile + desktop lighthouse)
            $estimatedDelay = $keyPages->count() * 2 * 60; // seconds
            FinalizePerformanceSummaryJob::dispatch($audit->id)
                ->delay(now()->addSeconds($estimatedDelay + 30));

        } catch (\Exception $e) {
            Log::error("RunPerformanceAuditBatchJob failed: {$e->getMessage()}", [
                'audit_id' => $this->auditId,
                'exception' => $e,
            ]);
        }
    }

    /**
     * Select key pages for performance audit
     */
    protected function selectKeyPages(Audit $audit, int $maxPages = 5): \Illuminate\Database\Eloquent\Collection
    {
        // Always include homepage
        $homepage = AuditPage::where('audit_id', $audit->id)
            ->where('url', $audit->normalized_url)
            ->first();

        $pages = collect();

        if ($homepage) {
            $pages->push($homepage);
        }

        // Get top pages by internal links count (most linked pages)
        $topPages = AuditPage::where('audit_id', $audit->id)
            ->where('url', '!=', $audit->normalized_url) // Exclude homepage
            ->orderBy('internal_links_count', 'desc')
            ->limit($maxPages - ($homepage ? 1 : 0))
            ->get();

        return $pages->merge($topPages);
    }
}
