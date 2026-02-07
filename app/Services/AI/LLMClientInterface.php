<?php

namespace App\Services\AI;

interface LLMClientInterface
{
    /**
     * Generate text from prompt
     */
    public function generateText(string $prompt, array $options = []): LLMResponse;

    /**
     * Generate text with system and user prompts
     */
    public function generateWithSystemPrompt(string $systemPrompt, string $userPrompt, array $options = []): LLMResponse;

    /**
     * Generate embeddings (optional)
     */
    public function embed(string $text): array;
}
