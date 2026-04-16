<?php

namespace App\Services\KeywordResearch\Metrics;

interface KeywordMetricsProviderInterface
{
    public function name(): string;

    public function isConfigured(): bool;

    public function fetch(array $keywords, array $context): array;
}
