<?php

namespace App\Services\AI;

use App\Jobs\GenerateAiFixPlanJob;
use App\Jobs\GenerateAiReportSummaryJob;
use Illuminate\Support\Facades\Log;

class PostAuditAiJobDispatcher
{
    public static function llmConfigured(): bool
    {
        $key = config('services.llm.api_key') ?: config('services.openai.api_key');

        return $key !== null && trim((string) $key) !== '';
    }

    /**
     * Queue AI report summary + fix plan after an audit completes (RunSeoAuditJob / RunSeoAuditCoreJob).
     * Multi-page audits that use FinalizePerformanceSummaryJob already dispatch these separately.
     */
    public static function dispatchForAudit(int $auditId): void
    {
        if (! self::llmConfigured()) {
            return;
        }

        try {
            GenerateAiReportSummaryJob::dispatch($auditId)->delay(now()->addSeconds(5));
            GenerateAiFixPlanJob::dispatch($auditId)->delay(now()->addSeconds(12));
        } catch (\Throwable $e) {
            Log::warning('PostAuditAiJobDispatcher: failed to queue AI jobs', [
                'audit_id' => $auditId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
