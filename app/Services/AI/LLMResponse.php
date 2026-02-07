<?php

namespace App\Services\AI;

class LLMResponse
{
    public function __construct(
        public string $content,
        public ?int $tokensIn = null,
        public ?int $tokensOut = null,
        public ?float $costCents = null,
        public array $metadata = []
    ) {}

    public function toArray(): array
    {
        return [
            'content' => $this->content,
            'tokens_in' => $this->tokensIn,
            'tokens_out' => $this->tokensOut,
            'cost_cents' => $this->costCents,
            'metadata' => $this->metadata,
        ];
    }
}
