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

class GenerateAiSnippetPackJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;
    public $tries = 2;

    public function __construct(
        public int $auditId,
        public array $issueCodes,
        public string $platform
    ) {}

    public function handle(): void
    {
        $audit = Audit::find($this->auditId);
        if (!$audit) {
            return;
        }

        $promptBuilder = new PromptBuilder(new LLMClient());
        $validator = new SchemaValidator();

        $promptData = $promptBuilder->buildSnippetPackPrompt($audit, $this->issueCodes, $this->platform);
        $inputFingerprint = $this->computeFingerprint($audit, $promptData);

        // Check cache
        $existing = AiGeneration::where('audit_id', $audit->id)
            ->where('type', AiGeneration::TYPE_SNIPPET_PACK)
            ->where('input_fingerprint', $inputFingerprint)
            ->where('status', AiGeneration::STATUS_COMPLETED)
            ->first();

        if ($existing) {
            Log::info("AI snippet pack cache hit", [
                'audit_id' => $audit->id,
                'platform' => $this->platform,
            ]);
            return;
        }

        $generation = AiGeneration::create([
            'organization_id' => $audit->organization_id,
            'audit_id' => $audit->id,
            'type' => AiGeneration::TYPE_SNIPPET_PACK,
            'input_fingerprint' => $inputFingerprint,
            'status' => AiGeneration::STATUS_RUNNING,
            'input' => array_merge($promptData, [
                'issue_codes' => $this->issueCodes,
                'platform' => $this->platform,
            ]),
        ]);

        try {
            $client = new LLMClient();
            $response = $client->generateWithSystemPrompt(
                $promptData['system_prompt'],
                $promptData['user_prompt'],
                ['json_mode' => true]
            );

            $validation = $validator->validate($response->content);
            if (!$validation['valid']) {
                throw new \Exception("Invalid JSON: " . json_encode($validation['error'] ?? $validation['errors'] ?? []));
            }

            $generation->update([
                'status' => AiGeneration::STATUS_COMPLETED,
                'output' => $validation['data'],
                'tokens_in' => $response->tokensIn,
                'tokens_out' => $response->tokensOut,
                'cost_cents' => (int) round($response->costCents ?? 0),
            ]);

        } catch (\Exception $e) {
            Log::error("AI snippet pack generation failed", [
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
            'issue_codes' => $this->issueCodes,
            'platform' => $this->platform,
            'prompt_version' => 'v1.0.0',
        ];
        
        return hash('sha256', json_encode($data));
    }
}
