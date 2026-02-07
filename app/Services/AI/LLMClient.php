<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LLMClient implements LLMClientInterface
{
    protected string $provider;
    protected string $apiKey;
    protected string $baseUrl;
    protected string $model;

    public function __construct()
    {
        $this->provider = config('services.llm.provider', 'openai');
        $this->apiKey = config('services.llm.api_key') ?? config('services.openai.api_key');
        $this->baseUrl = $this->getBaseUrl();
        $this->model = config('services.llm.model', 'gpt-4o-mini');
    }

    /**
     * Generate text from prompt
     */
    public function generateText(string $prompt, array $options = []): LLMResponse
    {
        return $this->generateWithSystemPrompt(
            $options['system_prompt'] ?? 'You are a helpful SEO assistant.',
            $prompt,
            $options
        );
    }

    /**
     * Generate text with system and user prompts
     */
    public function generateWithSystemPrompt(string $systemPrompt, string $userPrompt, array $options = []): LLMResponse
    {
        try {
            $response = $this->makeRequest($systemPrompt, $userPrompt, $options);
            
            return new LLMResponse(
                content: $response['content'],
                tokensIn: $response['tokens_in'] ?? null,
                tokensOut: $response['tokens_out'] ?? null,
                costCents: $response['cost_cents'] ?? null,
                metadata: $response['metadata'] ?? []
            );
        } catch (\Exception $e) {
            Log::error('LLM generation failed', [
                'provider' => $this->provider,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Generate embeddings
     */
    public function embed(string $text): array
    {
        // For now, return empty array - implement if needed
        // OpenAI: POST /v1/embeddings
        return [];
    }

    /**
     * Make API request
     */
    protected function makeRequest(string $systemPrompt, string $userPrompt, array $options): array
    {
        if ($this->provider === 'openai') {
            return $this->makeOpenAIRequest($systemPrompt, $userPrompt, $options);
        }

        // Add other providers here
        throw new \Exception("Provider {$this->provider} not implemented");
    }

    /**
     * Make OpenAI API request
     */
    protected function makeOpenAIRequest(string $systemPrompt, string $userPrompt, array $options): array
    {
        $url = rtrim($this->baseUrl, '/') . '/chat/completions';

        $payload = [
            'model' => $options['model'] ?? $this->model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            'temperature' => $options['temperature'] ?? 0.3,
            'max_tokens' => $options['max_tokens'] ?? 2000,
            'response_format' => $options['response_format'] ?? null,
        ];

        // Force JSON mode if requested
        if ($options['json_mode'] ?? false) {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(60)->post($url, $payload);

        if (!$response->successful()) {
            throw new \Exception("OpenAI API error: " . $response->body());
        }

        $data = $response->json();
        $content = $data['choices'][0]['message']['content'] ?? '';
        $usage = $data['usage'] ?? [];

        // Calculate cost (approximate - adjust based on model pricing)
        $costCents = $this->calculateCost(
            $usage['prompt_tokens'] ?? 0,
            $usage['completion_tokens'] ?? 0,
            $payload['model']
        );

        return [
            'content' => $content,
            'tokens_in' => $usage['prompt_tokens'] ?? null,
            'tokens_out' => $usage['completion_tokens'] ?? null,
            'cost_cents' => $costCents,
            'metadata' => ['model' => $payload['model']],
        ];
    }

    /**
     * Calculate cost in cents
     */
    protected function calculateCost(int $tokensIn, int $tokensOut, string $model): float
    {
        // Pricing per 1M tokens (in cents)
        $pricing = [
            'gpt-4o-mini' => ['in' => 0.15, 'out' => 0.60],
            'gpt-4o' => ['in' => 2.50, 'out' => 10.00],
            'gpt-4-turbo' => ['in' => 10.00, 'out' => 30.00],
        ];

        $modelPricing = $pricing[$model] ?? $pricing['gpt-4o-mini'];
        
        return ($tokensIn / 1000000 * $modelPricing['in']) + 
               ($tokensOut / 1000000 * $modelPricing['out']);
    }

    /**
     * Get base URL for provider
     */
    protected function getBaseUrl(): string
    {
        return match($this->provider) {
            'openai' => config('services.llm.openai_url', 'https://api.openai.com/v1'),
            'deepseek' => config('services.llm.deepseek_url', 'https://api.deepseek.com/v1'),
            'anthropic' => config('services.llm.anthropic_url', 'https://api.anthropic.com/v1'),
            default => config('services.llm.openai_url', 'https://api.openai.com/v1'),
        };
    }
}
