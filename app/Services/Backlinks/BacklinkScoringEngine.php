<?php

namespace App\Services\Backlinks;

use App\Models\DomainBacklink;
use App\Models\BacklinkRefDomain;

class BacklinkScoringEngine
{
    // Suspicious TLDs
    protected static $suspiciousTlds = [
        '.xyz', '.top', '.icu', '.online', '.site', '.website', '.space',
        '.click', '.download', '.stream', '.win', '.bid', '.trade', '.loan',
    ];

    // Spam path patterns
    protected static $spamPaths = [
        '/links', '/directory', '/seo', '/guest-post', '/guestpost',
        '/casino', '/viagra', '/poker', '/bet', '/gambling',
        '/buy-backlinks', '/sell-backlinks',
    ];

    // Spam anchor keywords
    protected static $spamAnchorKeywords = [
        'viagra', 'casino', 'poker', 'gambling', 'bet', 'buy now',
        'click here', 'cheap', 'discount', 'free', 'adult', 'xxx',
        'porn', 'loan', 'debt', 'credit card', 'payday',
    ];

    /**
     * Score a backlink item
     */
    public function scoreBacklink(DomainBacklink $backlink, array $metrics = []): array
    {
        $riskScore = 0;
        $qualityScore = 0;
        $flags = [];

        // Risk signals
        $riskScore += $this->scoreTldRisk($backlink->tld, $flags);
        $riskScore += $this->scorePathRisk($backlink->source_url, $flags);
        $riskScore += $this->scoreAnchorRisk($backlink->anchor, $flags);
        $riskScore += $this->scoreRelRisk($backlink->rel, $flags);
        $riskScore += $this->scoreProviderMetrics($metrics, $flags);

        // Quality signals
        $qualityScore += $this->scoreRelQuality($backlink->rel, $flags);
        $qualityScore += $this->scoreAnchorQuality($backlink->anchor, $flags);
        $qualityScore += $this->scoreProviderAuthority($metrics, $flags);

        // Normalize scores
        $riskScore = min(100, max(0, $riskScore));
        $qualityScore = min(100, max(0, $qualityScore));

        return [
            'risk_score' => (int)$riskScore,
            'quality_score' => (int)$qualityScore,
            'flags' => $flags,
        ];
    }

    /**
     * Score referring domain (aggregate from backlinks)
     */
    public function scoreRefDomain(BacklinkRefDomain $refDomain): array
    {
        $backlinks = $refDomain->backlinks()
            ->where('rel', DomainBacklink::REL_FOLLOW)
            ->get();

        if ($backlinks->isEmpty()) {
            return [
                'risk_score' => 0,
                'quality_score' => 0,
            ];
        }

        // Risk: use max risk from top links (most conservative)
        $riskScore = $backlinks->sortByDesc('risk_score')
            ->take(10)
            ->max('risk_score') ?? 0;

        // Quality: average of follow links
        $qualityScore = (int)$backlinks->avg('quality_score');

        // Auto-set status based on risk
        $status = $refDomain->status; // Keep manual override if set
        if ($refDomain->status === BacklinkRefDomain::STATUS_OK) {
            if ($riskScore >= 80) {
                $status = BacklinkRefDomain::STATUS_TOXIC;
            } elseif ($riskScore >= 55) {
                $status = BacklinkRefDomain::STATUS_REVIEW;
            }
        }

        return [
            'risk_score' => (int)$riskScore,
            'quality_score' => $qualityScore,
            'status' => $status,
        ];
    }

    /**
     * Score TLD risk
     */
    protected function scoreTldRisk(?string $tld, array &$flags): int
    {
        if (!$tld) {
            return 0;
        }

        $tldLower = strtolower('.' . ltrim($tld, '.'));
        foreach (self::$suspiciousTlds as $suspicious) {
            if ($tldLower === $suspicious || str_ends_with($tldLower, $suspicious)) {
                $flags['suspicious_tld'] = true;
                return 10;
            }
        }

        return 0;
    }

    /**
     * Score path risk
     */
    protected function scorePathRisk(string $sourceUrl, array &$flags): int
    {
        $path = parse_url($sourceUrl, PHP_URL_PATH) ?? '';
        $pathLower = strtolower($path);

        foreach (self::$spamPaths as $spamPath) {
            if (str_contains($pathLower, $spamPath)) {
                if (str_contains($pathLower, '/directory')) {
                    $flags['directory_path'] = true;
                } elseif (str_contains($pathLower, '/links')) {
                    $flags['links_page'] = true;
                } else {
                    $flags['spam_path'] = true;
                }
                return 15;
            }
        }

        return 0;
    }

    /**
     * Score anchor risk
     */
    protected function scoreAnchorRisk(?string $anchor, array &$flags): int
    {
        if (!$anchor) {
            return 0;
        }

        $anchorLower = strtolower($anchor);
        foreach (self::$spamAnchorKeywords as $keyword) {
            if (str_contains($anchorLower, $keyword)) {
                $flags['spam_anchor'] = true;
                if (str_contains($anchorLower, 'viagra') || str_contains($anchorLower, 'casino') || str_contains($anchorLower, 'adult')) {
                    $flags['adult_anchor'] = true;
                }
                return 30;
            }
        }

        return 0;
    }

    /**
     * Score rel risk
     */
    protected function scoreRelRisk(string $rel, array &$flags): int
    {
        // Follow links are riskier if other spam signals present
        if ($rel === DomainBacklink::REL_FOLLOW) {
            // Risk is already factored in via other signals
            return 0;
        }

        return 0;
    }

    /**
     * Score provider metrics (spam score, etc)
     */
    protected function scoreProviderMetrics(array $metrics, array &$flags): int
    {
        if (isset($metrics['spam_score']) && is_numeric($metrics['spam_score'])) {
            $spamScore = (float)$metrics['spam_score'];
            // Scale 0-100 spam score to 0-30 risk
            $risk = (int)($spamScore * 0.3);
            if ($risk > 0) {
                $flags['provider_spam_score'] = true;
            }
            return $risk;
        }

        return 0;
    }

    /**
     * Score rel quality
     */
    protected function scoreRelQuality(string $rel, array &$flags): int
    {
        // Nofollow/sponsored/ugc are neutral to risk, small quality boost
        if (in_array($rel, [DomainBacklink::REL_NOFOLLOW, DomainBacklink::REL_SPONSORED, DomainBacklink::REL_UGC])) {
            return 5;
        }

        return 0;
    }

    /**
     * Score anchor quality
     */
    protected function scoreAnchorQuality(?string $anchor, array &$flags): int
    {
        if (!$anchor) {
            return 0;
        }

        $anchorLower = strtolower(trim($anchor));

        // Branded/URL/natural anchors are good
        if (preg_match('/^(https?:\/\/|www\.)/', $anchorLower)) {
            $flags['url_anchor'] = true;
            return 10;
        }

        // Short, natural-looking anchors (not exact match spam)
        if (strlen($anchor) > 5 && strlen($anchor) < 50 && !preg_match('/^\w+$/i', $anchor)) {
            $flags['natural_anchor'] = true;
            return 10;
        }

        return 0;
    }

    /**
     * Score provider authority metrics
     */
    protected function scoreProviderAuthority(array $metrics, array &$flags): int
    {
        $quality = 0;

        // Traffic metric
        if (isset($metrics['traffic']) && is_numeric($metrics['traffic']) && $metrics['traffic'] > 0) {
            // Scale traffic to 0-40 quality score
            $traffic = (float)$metrics['traffic'];
            $quality += min(40, (int)log10($traffic + 1) * 5);
            $flags['has_traffic'] = true;
        }

        // Authority metric (DA/DR/etc)
        if (isset($metrics['authority']) && is_numeric($metrics['authority'])) {
            $authority = (float)$metrics['authority'];
            // Scale 0-100 authority to 0-40 quality
            $quality += (int)($authority * 0.4);
            $flags['has_authority'] = true;
        }

        return min(40, $quality);
    }
}


