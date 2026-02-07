<?php

namespace App\Jobs;

use App\Models\Organization;
use App\Models\OrgMetricsDaily;
use App\Models\UsageEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class BuildDailyOrgMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job
     */
    public function handle(): void
    {
        $yesterday = now()->subDay()->startOfDay();
        
        $organizations = Organization::all();

        foreach ($organizations as $org) {
            $this->buildMetricsForOrg($org, $yesterday);
        }
    }

    /**
     * Build metrics for an organization
     */
    protected function buildMetricsForOrg(Organization $org, $date): void
    {
        // Check if metrics already exist
        $existing = OrgMetricsDaily::where('organization_id', $org->id)
            ->where('date', $date->format('Y-m-d'))
            ->first();

        if ($existing) {
            return; // Already computed
        }

        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();

        // Count audits created
        $auditsCreated = $org->audits()
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->count();

        // Count pages crawled (from usage events)
        $pagesCrawled = UsageEvent::where('organization_id', $org->id)
            ->where('event_type', 'page_crawled')
            ->whereBetween('occurred_at', [$startOfDay, $endOfDay])
            ->sum('quantity');

        // Count lighthouse runs
        $lighthouseRuns = UsageEvent::where('organization_id', $org->id)
            ->where('event_type', 'lighthouse_run')
            ->whereBetween('occurred_at', [$startOfDay, $endOfDay])
            ->sum('quantity');

        // Count PDF exports
        $pdfExports = UsageEvent::where('organization_id', $org->id)
            ->where('event_type', 'pdf_export')
            ->whereBetween('occurred_at', [$startOfDay, $endOfDay])
            ->sum('quantity');

        // Count leads generated
        $leadsGenerated = $org->leads()
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->count();

        // Create or update metrics
        OrgMetricsDaily::updateOrCreate(
            [
                'organization_id' => $org->id,
                'date' => $date->format('Y-m-d'),
            ],
            [
                'audits_created' => $auditsCreated,
                'pages_crawled' => $pagesCrawled,
                'lighthouse_runs' => $lighthouseRuns,
                'pdf_exports' => $pdfExports,
                'leads_generated' => $leadsGenerated,
            ]
        );
    }
}
