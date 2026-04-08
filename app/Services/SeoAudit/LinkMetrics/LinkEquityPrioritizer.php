<?php

namespace App\Services\SeoAudit\LinkMetrics;

use App\Models\Audit;
use App\Models\AuditIssue;
use App\Models\AuditPage;
use App\Services\SeoAudit\UrlNormalizer;

class LinkEquityPrioritizer
{
    /**
     * Annotate issues with link equity and optionally increase score_penalty / impact.
     *
     * @return array<string, int> Category => penalty delta for rules scoring
     */
    public function apply(Audit $audit): array
    {
        $flags = $audit->crawl_module_flags ?? [];
        if (empty($flags['link_metrics_enabled'])) {
            return [];
        }

        $deltas = [
            'onpage' => 0,
            'technical' => 0,
            'performance' => 0,
            'links' => 0,
            'social' => 0,
            'usability' => 0,
            'local' => 0,
            'security' => 0,
        ];

        $tiersCfg = config('seo_audit.link_metrics.tiers', []);
        $bonuses = config('seo_audit.link_metrics.priority_score_bonus_by_tier', []);
        $minSeverity = (string) config('seo_audit.link_metrics.priority_min_severity', 'warning');
        $severityRank = ['critical' => 3, 'warning' => 2, 'info' => 1];
        $minRank = $severityRank[$minSeverity] ?? 2;

        $pages = AuditPage::query()->where('audit_id', $audit->id)->get();
        $byNorm = [];
        foreach ($pages as $page) {
            $n = UrlNormalizer::normalize($page->url, $audit->normalized_url);
            if ($n) {
                $byNorm[$n] = $page;
            }
        }

        foreach ($audit->issues()->cursor() as $issue) {
            $url = $issue->url;
            $metrics = LinkMetricsEnrichmentService::emptyPayload((string) config('seo_audit.link_metrics.driver', 'null'));

            if ($url) {
                $n = UrlNormalizer::normalize($url, $audit->normalized_url);
                $page = $n && isset($byNorm[$n]) ? $byNorm[$n] : null;
                if ($page && is_array($page->link_metrics_json)) {
                    $metrics = $page->link_metrics_json;
                }
            }

            $tier = LinkEquityScoring::tier($metrics, $tiersCfg);
            $details = $issue->details_json ?? [];
            $details['link_equity'] = [
                'tier' => $tier,
                'referring_domains' => (int) ($metrics['referring_domains'] ?? 0),
                'backlinks' => (int) ($metrics['backlinks'] ?? 0),
                'tier_labels' => $tiersCfg,
                'authority_score' => $metrics['authority_score'] ?? null,
                'provider' => $metrics['provider'] ?? null,
            ];
            $issue->details_json = $details;

            $bonus = (int) ($bonuses[$tier] ?? 0);
            $sevRank = $severityRank[$issue->severity] ?? 1;
            $shouldBoost = $bonus > 0 && $sevRank >= $minRank && in_array($tier, ['high', 'medium'], true);

            if ($shouldBoost) {
                $old = (int) ($issue->score_penalty ?? 0);
                $cap = (int) config('seo_audit.link_metrics.max_score_penalty', 40);
                $new = min($cap, $old + $bonus);
                $delta = $new - $old;
                if ($delta > 0) {
                    $issue->score_penalty = $new;
                    $cat = (string) ($issue->category ?? 'technical');
                    if (! isset($deltas[$cat])) {
                        $deltas[$cat] = 0;
                    }
                    $deltas[$cat] += $delta;
                }

                if ($tier === 'high' && $issue->impact === AuditIssue::IMPACT_MEDIUM) {
                    $issue->impact = AuditIssue::IMPACT_HIGH;
                    $issue->severity = AuditIssue::SEVERITY_CRITICAL;
                }
            }

            $issue->saveQuietly();
        }

        return array_filter($deltas);
    }
}
