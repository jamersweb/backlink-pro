<?php

namespace App\Services\SeoAudit\LinkMetrics;

use App\Models\Audit;
use App\Models\Domain;
use App\Models\DomainAnchorSummary;
use App\Models\DomainBacklink;
use App\Models\DomainBacklinkRun;
use App\Services\SeoAudit\UrlNormalizer;
use Illuminate\Support\Facades\DB;

class DomainBacklinkLinkMetricsProvider implements LinkMetricsProviderContract
{
    public function metricsForNormalizedUrls(Audit $audit, array $normalizedUrls): array
    {
        $wanted = array_fill_keys($normalizedUrls, true);
        $run = $this->resolveRun($audit);
        if (! $run) {
            return $this->fillEmpty('domain_backlinks', $normalizedUrls);
        }

        $globalThemes = DomainAnchorSummary::query()
            ->where('run_id', $run->id)
            ->orderByDesc('count')
            ->limit(15)
            ->get(['anchor', 'count', 'type'])
            ->map(fn ($row) => [
                'anchor' => $row->anchor,
                'count' => (int) $row->count,
                'type' => $row->type,
            ])
            ->values()
            ->all();

        $aggregator = [];
        $groups = DB::table('domain_backlinks')
            ->where('run_id', $run->id)
            ->groupBy('target_url')
            ->selectRaw('target_url, COUNT(*) as backlinks_count, COUNT(DISTINCT source_domain) as referring_domains, AVG(quality_score) as avg_quality')
            ->get();

        foreach ($groups as $row) {
            $norm = UrlNormalizer::normalize($row->target_url);
            if (! $norm || ! isset($wanted[$norm])) {
                continue;
            }
            if (! isset($aggregator[$norm])) {
                $aggregator[$norm] = [
                    'raw_targets' => [],
                    'backlinks' => 0,
                    'quality_sum' => 0.0,
                    'quality_n' => 0,
                ];
            }
            $aggregator[$norm]['raw_targets'][] = $row->target_url;
            $aggregator[$norm]['backlinks'] += (int) $row->backlinks_count;
            $avgQ = $row->avg_quality;
            $bc = (int) $row->backlinks_count;
            if ($avgQ !== null && $bc > 0) {
                $aggregator[$norm]['quality_sum'] += (float) $avgQ * $bc;
                $aggregator[$norm]['quality_n'] += $bc;
            }
        }

        $out = [];
        foreach ($normalizedUrls as $normUrl) {
            if (! isset($aggregator[$normUrl])) {
                $payload = LinkMetricsEnrichmentService::emptyPayload('domain_backlinks');
                $payload['provider_run_id'] = $run->id;
                $payload['global_anchor_themes'] = $globalThemes;
                $out[$normUrl] = $payload;

                continue;
            }

            $bucket = $aggregator[$normUrl];
            $rd = $this->distinctReferringDomains($run->id, $bucket['raw_targets']);
            $authority = $bucket['quality_n'] > 0 ? round($bucket['quality_sum'] / $bucket['quality_n'], 2) : null;

            $out[$normUrl] = [
                'provider' => 'domain_backlinks',
                'provider_run_id' => $run->id,
                'referring_domains' => $rd,
                'backlinks' => (int) $bucket['backlinks'],
                'authority_score' => $authority,
                'top_anchors' => $this->topAnchorsForTargets($run->id, $bucket['raw_targets']),
                'global_anchor_themes' => $globalThemes,
                'enriched_at' => now()->toIso8601String(),
            ];
        }

        return $out;
    }

    /**
     * @param  array<int, string>  $normalizedUrls
     * @return array<string, array<string, mixed>>
     */
    protected function fillEmpty(string $provider, array $normalizedUrls): array
    {
        $out = [];
        foreach ($normalizedUrls as $u) {
            $out[$u] = LinkMetricsEnrichmentService::emptyPayload($provider);
        }

        return $out;
    }

    protected function resolveRun(Audit $audit): ?DomainBacklinkRun
    {
        if (! $audit->user_id) {
            return null;
        }

        $host = UrlNormalizer::extractHost($audit->normalized_url);
        if (! $host) {
            return null;
        }

        $domain = Domain::query()
            ->where('user_id', $audit->user_id)
            ->where(function ($q) use ($host) {
                $q->where('host', $host)->orWhere('name', $host);
            })
            ->orderByDesc('id')
            ->first();

        if (! $domain) {
            return null;
        }

        return DomainBacklinkRun::query()
            ->where('domain_id', $domain->id)
            ->where('status', DomainBacklinkRun::STATUS_COMPLETED)
            ->orderByDesc('finished_at')
            ->orderByDesc('id')
            ->first();
    }

       /**
     * @param  array<int, string>  $rawTargets
     */
    protected function distinctReferringDomains(int $runId, array $rawTargets): int
    {
        $rawTargets = array_values(array_unique(array_filter($rawTargets)));
        if ($rawTargets === []) {
            return 0;
        }

        return (int) DomainBacklink::query()
            ->where('run_id', $runId)
            ->whereIn('target_url', $rawTargets)
            ->distinct()
            ->count('source_domain');
    }

    /**
     * @param  array<int, string>  $rawTargets
     * @return array<int, array{anchor: string, count: int}>
     */
    protected function topAnchorsForTargets(int $runId, array $rawTargets): array
    {
        $rawTargets = array_values(array_unique(array_filter($rawTargets)));
        if ($rawTargets === []) {
            return [];
        }

        $rows = DomainBacklink::query()
            ->where('run_id', $runId)
            ->whereIn('target_url', $rawTargets)
            ->whereNotNull('anchor')
            ->where('anchor', '!=', '')
            ->selectRaw('anchor, COUNT(*) as c')
            ->groupBy('anchor')
            ->orderByDesc('c')
            ->limit(8)
            ->get();

        return $rows->map(fn ($r) => ['anchor' => $r->anchor, 'count' => (int) $r->c])->values()->all();
    }
}
