<?php

namespace App\Services\KeywordResearch\Metrics;

class KeywordMetricsProviderManager
{
    public function __construct(
        protected GoogleAdsKeywordMetricsProvider $googleAdsKeywordMetricsProvider,
        protected NullKeywordMetricsProvider $nullKeywordMetricsProvider
    ) {}

    public function fetch(array $keywords, array $context): array
    {
        return $this->resolve()->fetch($keywords, $context);
    }

    public function resolve(): KeywordMetricsProviderInterface
    {
        if ($this->googleAdsKeywordMetricsProvider->isConfigured()) {
            return $this->googleAdsKeywordMetricsProvider;
        }

        return $this->nullKeywordMetricsProvider;
    }
}
