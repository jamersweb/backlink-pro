<?php

namespace App\Services\KeywordResearch\Metrics;

class NullKeywordMetricsProvider implements KeywordMetricsProviderInterface
{
    public function name(): string
    {
        return 'none';
    }

    public function isConfigured(): bool
    {
        return false;
    }

    public function fetch(array $keywords, array $context): array
    {
        return [
            'provider' => $this->name(),
            'status' => 'not_configured',
            'error' => 'Keyword metrics provider is not configured.',
            'items' => [],
        ];
    }
}
