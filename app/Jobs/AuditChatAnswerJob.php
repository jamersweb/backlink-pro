<?php

namespace App\Jobs;

use App\Models\Audit;
use App\Models\AiGeneration;
use App\Services\AI\LLMClient;
use App\Services\AI\PromptBuilder;
use App\Services\AI\RAGRetriever;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AuditChatAnswerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60;
    public $tries = 2;

    public function __construct(
        public int $auditId,
        public string $question,
        public ?int $organizationId = null
    ) {}

    public function handle(): void
    {
        $audit = Audit::find($this->auditId);
        if (!$audit) {
            return;
        }

        $ragRetriever = new RAGRetriever(new LLMClient());
        $promptBuilder = new PromptBuilder(new LLMClient());

        // Retrieve relevant chunks
        $contextChunks = $ragRetriever->retrieve($audit, $this->question, 8);

        $promptData = $promptBuilder->buildChatPrompt($audit, $this->question, $contextChunks);
        $inputFingerprint = $this->computeFingerprint($audit, $this->question);

        // Create generation record (don't cache chat answers)
        $generation = AiGeneration::create([
            'organization_id' => $this->organizationId ?? $audit->organization_id,
            'audit_id' => $audit->id,
            'type' => AiGeneration::TYPE_CHAT_ANSWER,
            'input_fingerprint' => $inputFingerprint,
            'status' => AiGeneration::STATUS_RUNNING,
            'input' => [
                'question' => $this->question,
                'context_chunks_count' => count($contextChunks),
            ],
        ]);

        try {
            $client = new LLMClient();
            $response = $client->generateWithSystemPrompt(
                $promptData['system_prompt'],
                $promptData['user_prompt'],
                ['json_mode' => false, 'temperature' => 0.5]
            );

            // Extract citations from context chunks
            $citations = array_map(function ($chunk) {
                return [
                    'type' => $chunk['chunk_type'],
                    'source_id' => $chunk['source_id'],
                ];
            }, $contextChunks);

            $generation->update([
                'status' => AiGeneration::STATUS_COMPLETED,
                'output' => [
                    'answer' => $response->content,
                    'citations' => $citations,
                ],
                'tokens_in' => $response->tokensIn,
                'tokens_out' => $response->tokensOut,
                'cost_cents' => (int) round($response->costCents ?? 0),
            ]);

        } catch (\Exception $e) {
            Log::error("AI chat answer generation failed", [
                'audit_id' => $audit->id,
                'question' => $this->question,
                'error' => $e->getMessage(),
            ]);

            $generation->update([
                'status' => AiGeneration::STATUS_FAILED,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function computeFingerprint(Audit $audit, string $question): string
    {
        $data = [
            'audit_id' => $audit->id,
            'question' => strtolower(trim($question)),
        ];
        
        return hash('sha256', json_encode($data));
    }
}
