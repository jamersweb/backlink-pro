<?php

namespace App\Jobs\Integrations;

use App\Models\Audit;
use App\Models\ConnectedAccount;
use App\Services\Integrations\AuditGoogleKpiSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncAuditGa4Job implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600;

    public $tries = 2;

    public function __construct(
        public int $auditId,
        public int $userId,
        public ?string $propertyId = null
    ) {
        $this->onQueue('integrations');
    }

    public function handle(AuditGoogleKpiSyncService $sync): void
    {
        $audit = Audit::find($this->auditId);
        if (! $audit || $audit->user_id !== $this->userId) {
            return;
        }

        $account = ConnectedAccount::where('user_id', $this->userId)
            ->where('provider', 'google')
            ->where('service', 'seo')
            ->where('status', 'active')
            ->first();

        if (! $account) {
            $this->markFailed($audit, 'Google Analytics account is not connected.');
            return;
        }

        try {
            $kpis = is_array($audit->audit_kpis) ? $audit->audit_kpis : [];
            $g = $kpis['ga4'] ?? [];
            $g['sync_status'] = 'running';
            $kpis['ga4'] = $g;
            $audit->audit_kpis = $kpis;
            $audit->save();

            $sync->syncGa4($audit->fresh(), $account, $this->propertyId);
        } catch (\Throwable $e) {
            Log::warning('Audit GA4 sync job failed', [
                'audit_id' => $this->auditId,
                'error' => $e->getMessage(),
            ]);
            $this->markFailed($audit, $e->getMessage());
        }
    }

    protected function markFailed(Audit $audit, string $message): void
    {
        $audit->refresh();
        $kpis = is_array($audit->audit_kpis) ? $audit->audit_kpis : [];
        $g = $kpis['ga4'] ?? [];
        $g['sync_status'] = 'failed';
        $g['sync_error'] = $message;
        $kpis['ga4'] = $g;
        $audit->audit_kpis = $kpis;
        $audit->save();
    }
}
