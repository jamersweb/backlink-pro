<?php

namespace App\Services\SeoAudit\LinkMetrics;

use App\Models\Audit;
use App\Models\AuditPage;
use App\Services\SeoAudit\UrlNormalizer;

class LinkMetricsEnrichmentService
{
    public function __construct(
        protected LinkMetricsProviderContract $provider
    ) {}

    /**
     * @return array<string, mixed>
     */
    public static function emptyPayload(string $providerKey): array
    {
        return [
            'provider' => $providerKey,
            'provider_run_id' => null,
            'referring_domains' => 0,
            'backlinks' => 0,
            'authority_score' => null,
            'top_anchors' => [],
            'global_anchor_themes' => [],
            'enriched_at' => now()->toIso8601String(),
        ];
    }

    public function enrich(Audit $audit): void
    {
        $flags = $audit->crawl_module_flags ?? [];
        if (empty($flags['link_metrics_enabled'])) {
            return;
        }

        $fallbackProvider = (string) config('seo_audit.link_metrics.driver', 'null');

        $pages = AuditPage::query()->where('audit_id', $audit->id)->get();
        $wanted = [];
        foreach ($pages as $page) {
            foreach (array_filter([$page->url, $page->canonical_url]) as $u) {
                $n = UrlNormalizer::normalize($u, $audit->normalized_url);
                if ($n) {
                    $wanted[$n] = true;
                }
            }
        }

        $normList = array_keys($wanted);
        $byNorm = $this->provider->metricsForNormalizedUrls($audit, $normList);

        foreach ($pages as $page) {
            $norm = UrlNormalizer::normalize($page->url, $audit->normalized_url);
            $canon = $page->canonical_url
                ? UrlNormalizer::normalize($page->canonical_url, $audit->normalized_url)
                : null;

            $payload = null;
            if ($norm && isset($byNorm[$norm])) {
                $payload = $byNorm[$norm];
            } elseif ($canon && isset($byNorm[$canon])) {
                $payload = $byNorm[$canon];
            }

            if (! is_array($payload)) {
                $payload = self::emptyPayload($fallbackProvider);
            }

            $page->link_metrics_json = $payload;
            $page->saveQuietly();
        }
    }
}
