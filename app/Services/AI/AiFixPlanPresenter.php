<?php

namespace App\Services\AI;

use App\Models\AiGeneration;
use App\Models\Audit;
use Illuminate\Support\Facades\Schema;

class AiFixPlanPresenter
{
    /**
     * Payload for Inertia / JSON (audit report UI).
     */
    public static function forAudit(Audit $audit): array
    {
        $llmConfigured = PostAuditAiJobDispatcher::llmConfigured();

        $promptBuilder = new PromptBuilder(new LLMClient());
        $deterministic = $promptBuilder->prioritizedIssuesForAudit($audit);

        if (! Schema::hasTable('ai_generations')) {
            return [
                'llm_configured' => $llmConfigured,
                'fix_plan_status' => 'missing',
                'fix_plan_error' => null,
                'priority_fixes' => [],
                'week_plan' => null,
                'deterministic_priorities' => $deterministic,
            ];
        }

        $latest = AiGeneration::query()
            ->where('audit_id', $audit->id)
            ->where('type', AiGeneration::TYPE_FIX_PLAN)
            ->orderByDesc('id')
            ->first();

        $output = null;
        $error = null;
        $status = 'missing';

        if ($latest) {
            $status = $latest->status;
            $error = $latest->error;
            if ($latest->status === AiGeneration::STATUS_COMPLETED) {
                $output = $latest->output;
            }
        } elseif ($llmConfigured && $audit->status === Audit::STATUS_COMPLETED) {
            $status = 'pending';
        }

        $priorityFixes = is_array($output) ? ($output['priority_fixes'] ?? null) : null;
        $weekPlan = is_array($output) ? ($output['week_plan'] ?? null) : null;

        return [
            'llm_configured' => $llmConfigured,
            'fix_plan_status' => $status,
            'fix_plan_error' => $error,
            'priority_fixes' => is_array($priorityFixes) ? $priorityFixes : [],
            'week_plan' => is_array($weekPlan) ? $weekPlan : null,
            'deterministic_priorities' => $deterministic,
        ];
    }
}
