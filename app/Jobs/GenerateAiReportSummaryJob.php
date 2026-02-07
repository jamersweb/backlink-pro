<?php

namespace App\Jobs;

use App\Models\Audit;
use App\Models\AiGeneration;
use App\Services\AI\LLMClient;
use App\Services\AI\PromptBuilder;
use App\Services\AI\SchemaValidator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

class GenerateAiReportSummaryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;
    public $tries = 2;

    public function __construct(
        public int $auditId
    ) {}

    public function handle(): void
    {
        $audit = Audit::find($this->auditId);
        if (!$audit) {
            return;
        }

        $promptBuilder = new PromptBuilder(new LLMClient());
        $validator = new SchemaValidator();

        // Build prompt
        $promptData = $promptBuilder->buildReportSummaryPrompt($audit);
        
        // Compute fingerprint
        $inputFingerprint = $this->computeFingerprint($audit, $promptData);

        // Check cache
        $existing = AiGeneration::where('audit_id', $audit->id)
            ->where('type', AiGeneration::TYPE_REPORT_SUMMARY)
            ->where('input_fingerprint', $inputFingerprint)
            ->where('status', AiGeneration::STATUS_COMPLETED)
            ->first();

        if ($existing) {
            Log::info("AI report summary cache hit", ['audit_id' => $audit->id]);
            return;
        }

        // Create generation record
        $generation = AiGeneration::create([
            'organization_id' => $audit->organization_id,
            'audit_id' => $audit->id,
            'type' => AiGeneration::TYPE_REPORT_SUMMARY,
            'input_fingerprint' => $inputFingerprint,
            'status' => AiGeneration::STATUS_RUNNING,
            'input' => $promptData,
        ]);

        try {
            // Generate
            $client = new LLMClient();
            $response = $client->generateWithSystemPrompt(
                $promptData['system_prompt'],
                $promptData['user_prompt'],
                ['json_mode' => true]
            );

            // Validate JSON
            $validation = $validator->validate($response->content);
            if (!$validation['valid']) {
                throw new \Exception("Invalid JSON: " . json_encode($validation['error'] ?? $validation['errors'] ?? []));
            }

            // Update generation
            $generation->update([
                'status' => AiGeneration::STATUS_COMPLETED,
                'output' => $validation['data'],
                'tokens_in' => $response->tokensIn,
                'tokens_out' => $response->tokensOut,
                'cost_cents' => (int) round($response->costCents ?? 0),
            ]);

        } catch (\Exception $e) {
            Log::error("AI report summary generation failed", [
                'audit_id' => $audit->id,
                'error' => $e->getMessage(),
            ]);

            $generation->update([
                'status' => AiGeneration::STATUS_FAILED,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function computeFingerprint(Audit $audit, array $promptData): string
    {
        $data = [
            'audit_id' => $audit->id,
            'overall_score' => $audit->overall_score,
            'pages_scanned' => $audit->pages_scanned,
            'issues_count' => $audit->issues()->count(),
            'prompt_version' => 'v1.0.0',
        ];
        
        return hash('sha256', json_encode($data));
    }
}
