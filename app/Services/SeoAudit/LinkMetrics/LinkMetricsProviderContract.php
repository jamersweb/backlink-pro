<?php

namespace App\Services\SeoAudit\LinkMetrics;

use App\Models\Audit;

interface LinkMetricsProviderContract
{
    /**
     * @param  array<int, string>  $normalizedUrls  Urls already normalized via UrlNormalizer
     * @return array<string, array<string, mixed>> Map normalized URL => metrics payload (referring_domains, backlinks, etc.)
     */
    public function metricsForNormalizedUrls(Audit $audit, array $normalizedUrls): array;
}
