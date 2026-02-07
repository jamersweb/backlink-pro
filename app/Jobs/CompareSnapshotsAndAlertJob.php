<?php

namespace App\Jobs;

use App\Models\Audit;
use App\Models\AuditMonitor;
use App\Models\AuditSnapshot;
use App\Models\AuditAlert;
use App\Services\Monitoring\SnapshotComparer;
use App\Services\Monitoring\AlertNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CompareSnapshotsAndAlertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;
    public $tries = 2;

    public function __construct(
        public int $monitorId,
        public int $auditId
    ) {}

    public function handle(): void
    {
        $monitor = AuditMonitor::find($this->monitorId);
        $audit = Audit::find($this->auditId);

        if (!$monitor || !$audit) {
            return;
        }

        try {
            // Use SnapshotComparer service
            $comparer = new SnapshotComparer();
            $alerts = $comparer->compareAndAlert($monitor, $audit);

            // Create alerts and send notifications
            $notifier = new AlertNotifier();
            
            foreach ($alerts as $alertData) {
                $alert = AuditAlert::create([
                    'monitor_id' => $monitor->id,
                    'audit_id' => $audit->id,
                    'severity' => $alertData['severity'],
                    'title' => $alertData['title'],
                    'message' => $alertData['message'],
                    'diff' => $alertData['diff'] ?? null,
                ]);

                $notifier->send($alert, $monitor);
            }

            Log::info("Compared snapshots and created alerts", [
                'monitor_id' => $monitor->id,
                'audit_id' => $audit->id,
                'alerts_count' => count($alerts),
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to compare snapshots", [
                'monitor_id' => $monitor->id,
                'audit_id' => $audit->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

}
