<?php

namespace App\Jobs;

use App\Models\AuditMonitor;
use App\Models\Audit;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RunMonitorAuditJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600;
    public $tries = 1;

    public function __construct(
        public int $monitorId
    ) {}

    public function handle(): void
    {
        $monitor = AuditMonitor::find($this->monitorId);
        if (!$monitor || !$monitor->is_enabled) {
            return;
        }

        try {
            // Create audit with monitor settings
            $audit = Audit::create([
                'organization_id' => $monitor->organization_id,
                'url' => $monitor->target_url,
                'status' => Audit::STATUS_QUEUED,
                'monitor_id' => $monitor->id,
                'pages_limit' => $monitor->pages_limit,
                'crawl_depth' => $monitor->crawl_depth,
            ]);

            // Dispatch audit pipeline job
            \App\Jobs\StartAuditPipelineJob::dispatch($audit->id);

            Log::info("Monitor audit started", [
                'monitor_id' => $monitor->id,
                'audit_id' => $audit->id,
            ]);

            // After audit completes, CompareSnapshotsAndAlertJob will be triggered

        } catch (\Exception $e) {
            Log::error("Failed to run monitor audit", [
                'monitor_id' => $monitor->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
