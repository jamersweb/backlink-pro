<?php

namespace App\Services\SeoAudit\LinkMetrics;

use App\Models\Audit;

class NullLinkMetricsProvider implements LinkMetricsProviderContract
{
    public function metricsForNormalizedUrls(Audit $audit, array $normalizedUrls): array
    {
        $out = [];
        foreach ($normalizedUrls as $url) {
            $out[$url] = LinkMetricsEnrichmentService::emptyPayload('null');
        }

        return $out;
    }
}
