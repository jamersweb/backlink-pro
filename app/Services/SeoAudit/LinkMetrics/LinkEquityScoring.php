<?php

namespace App\Services\SeoAudit\LinkMetrics;

class LinkEquityScoring
{
    /**
     * @param  array<string, mixed>  $metrics
     * @param  array<string, int>  $tiers
     */
    public static function tier(array $metrics, array $tiers): string
    {
        $rd = (int) ($metrics['referring_domains'] ?? 0);
        $bl = (int) ($metrics['backlinks'] ?? 0);

        $highRd = (int) ($tiers['high_referring_domains'] ?? 50);
        $highBl = (int) ($tiers['high_backlinks'] ?? 200);
        $medRd = (int) ($tiers['medium_referring_domains'] ?? 10);
        $medBl = (int) ($tiers['medium_backlinks'] ?? 40);

        if ($rd >= $highRd || $bl >= $highBl) {
            return 'high';
        }
        if ($rd >= $medRd || $bl >= $medBl) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * @param  array<string, mixed>  $metrics
     */
    public static function equityScore(array $metrics): int
    {
        $rd = (int) ($metrics['referring_domains'] ?? 0);
        $bl = (int) ($metrics['backlinks'] ?? 0);

        return $rd * 12 + $bl;
    }
}
