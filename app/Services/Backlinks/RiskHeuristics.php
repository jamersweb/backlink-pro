<?php

namespace App\Services\Backlinks;

use App\Models\DomainBacklinkRun;
use App\Models\DomainBacklink;
use App\Models\DomainRefDomain;
use App\Models\DomainAnchorSummary;

class RiskHeuristics
{
    /**
     * Compute risk score for a run
     */
    public static function computeRunRiskScore(DomainBacklinkRun $run): int
    {
        $score = 100; // Start at 100, subtract for risks

        // Check anchor distribution
        $anchorRisk = self::checkAnchorDistribution($run);
        $score -= $anchorRisk;

        // Check domain concentration
        $domainRisk = self::checkDomainConcentration($run);
        $score -= $domainRisk;

        // Check TLD distribution
        $tldRisk = self::checkTldDistribution($run);
        $score -= $tldRisk;

        // Check empty/generic anchors
        $anchorQualityRisk = self::checkAnchorQuality($run);
        $score -= $anchorQualityRisk;

        return max(0, min(100, $score));
    }

    /**
     * Check anchor distribution (exact match percentage)
     */
    protected static function checkAnchorDistribution(DomainBacklinkRun $run): int
    {
        $totalAnchors = $run->anchorSummaries()->sum('count');
        if ($totalAnchors === 0) {
            return 0;
        }

        $exactAnchors = $run->anchorSummaries()
            ->where('type', DomainAnchorSummary::TYPE_EXACT)
            ->sum('count');

        $exactPercentage = ($exactAnchors / $totalAnchors) * 100;

        // If exact match > 20%, warn
        if ($exactPercentage > 20) {
            return min(15, (int)(($exactPercentage - 20) / 2)); // Max -15 points
        }

        return 0;
    }

    /**
     * Check domain concentration (one domain contributing too much)
     */
    protected static function checkDomainConcentration(DomainBacklinkRun $run): int
    {
        $totalBacklinks = $run->backlinks()->count();
        if ($totalBacklinks === 0) {
            return 0;
        }

        // Get top referring domain
        $topDomain = $run->refDomains()
            ->orderBy('backlinks_count', 'desc')
            ->first();

        if (!$topDomain) {
            return 0;
        }

        $topPercentage = ($topDomain->backlinks_count / $totalBacklinks) * 100;

        // If one domain > 30%, warn
        if ($topPercentage > 30) {
            return min(20, (int)(($topPercentage - 30) / 2)); // Max -20 points
        }

        return 0;
    }

    /**
     * Check TLD distribution (suspicious TLDs)
     */
    protected static function checkTldDistribution(DomainBacklinkRun $run): int
    {
        // Suspicious TLDs (configurable list)
        $suspiciousTlds = config('services.backlinks.suspicious_tlds', [
            '.xyz', '.top', '.click', '.download', '.online', '.site', '.website',
        ]);

        $totalBacklinks = $run->backlinks()->count();
        if ($totalBacklinks === 0) {
            return 0;
        }

        $suspiciousCount = $run->backlinks()
            ->whereIn('tld', $suspiciousTlds)
            ->count();

        $suspiciousPercentage = ($suspiciousCount / $totalBacklinks) * 100;

        // If suspicious TLDs > 15%, warn
        if ($suspiciousPercentage > 15) {
            return min(15, (int)(($suspiciousPercentage - 15) / 2)); // Max -15 points
        }

        return 0;
    }

    /**
     * Check anchor quality (empty/generic percentage)
     */
    protected static function checkAnchorQuality(DomainBacklinkRun $run): int
    {
        $totalAnchors = $run->anchorSummaries()->sum('count');
        if ($totalAnchors === 0) {
            return 0;
        }

        $lowQualityAnchors = $run->anchorSummaries()
            ->whereIn('type', [DomainAnchorSummary::TYPE_EMPTY, DomainAnchorSummary::TYPE_GENERIC])
            ->sum('count');

        $lowQualityPercentage = ($lowQualityAnchors / $totalAnchors) * 100;

        // If low quality > 40%, warn
        if ($lowQualityPercentage > 40) {
            return min(10, (int)(($lowQualityPercentage - 40) / 5)); // Max -10 points
        }

        return 0;
    }

    /**
     * Compute risk flags for a backlink
     */
    public static function computeLinkRiskFlags(array $backlinkData): array
    {
        $flags = [];

        // Check anchor
        if (empty($backlinkData['anchor'])) {
            $flags[] = 'empty_anchor';
        }

        // Check TLD
        $suspiciousTlds = config('services.backlinks.suspicious_tlds', []);
        if (in_array($backlinkData['tld'] ?? null, $suspiciousTlds)) {
            $flags[] = 'suspicious_tld';
        }

        // Check rel
        if (($backlinkData['rel'] ?? '') === 'nofollow') {
            $flags[] = 'nofollow';
        }

        return $flags;
    }

    /**
     * Compute risk score for a referring domain
     */
    public static function computeRefDomainRiskScore(DomainRefDomain $refDomain, int $totalBacklinks): int
    {
        $score = 100;

        // High concentration
        if ($totalBacklinks > 0) {
            $percentage = ($refDomain->backlinks_count / $totalBacklinks) * 100;
            if ($percentage > 30) {
                $score -= min(30, (int)(($percentage - 30) / 2));
            }
        }

        // Suspicious TLD
        $suspiciousTlds = config('services.backlinks.suspicious_tlds', []);
        if (in_array($refDomain->tld, $suspiciousTlds)) {
            $score -= 20;
        }

        return max(0, min(100, $score));
    }
}


