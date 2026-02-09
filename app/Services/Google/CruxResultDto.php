<?php

namespace App\Services\Google;

class CruxResultDto
{
    public function __construct(
        public string $status,
        public bool $cacheHit,
        public ?array $kpis,
        public ?array $rawPayload,
        public ?string $errorMessage,
        public ?string $targetType,
        public ?string $targetValue,
        public ?string $formFactor,
        public ?string $fetchedAt,
        public ?array $formFactors = null
    ) {}

    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'cache_hit' => $this->cacheHit,
            'kpis' => $this->kpis,
            'raw_payload' => $this->rawPayload,
            'error' => $this->errorMessage,
            'target_type' => $this->targetType,
            'target_value' => $this->targetValue,
            'form_factor' => $this->formFactor,
            'fetched_at' => $this->fetchedAt,
            'form_factors' => $this->formFactors,
        ];
    }
}
