<?php

namespace App\Services\Content;

class OpportunityScorer
{
    /**
     * Score an opportunity (0-100)
     */
    public function score(int $impressions, float $position, float $ctr): int
    {
        // Base score from impressions (log scale, 0-50)
        $impressionsScore = $this->scoreImpressions($impressions);

        // Position bonus (0-30)
        // Best positions: 8-25 (sweet spot for quick wins)
        $positionBonus = $this->scorePosition($position);

        // CTR bonus (0-20)
        // Low CTR (< 2%) indicates opportunity
        $ctrBonus = $this->scoreCtr($ctr, $impressions);

        $total = $impressionsScore + $positionBonus + $ctrBonus;

        return (int) min(100, max(0, $total));
    }

    /**
     * Score impressions using log scale
     */
    protected function scoreImpressions(int $impressions): float
    {
        if ($impressions <= 0) {
            return 0;
        }

        // Log scale: 100 impressions = ~10, 1000 = ~20, 10000 = ~35, 100000 = ~50
        $logScore = log10($impressions + 1) * 10;
        return min(50, $logScore);
    }

    /**
     * Score position (sweet spot: 8-25)
     */
    protected function scorePosition(float $position): float
    {
        if ($position < 1) {
            return 0; // Already ranking well
        }

        if ($position >= 8 && $position <= 25) {
            // Sweet spot: maximum bonus
            $distanceFromCenter = abs($position - 16.5); // Center of sweet spot
            $maxDistance = 8.5; // Distance from center to edge
            $normalized = 1 - ($distanceFromCenter / $maxDistance);
            return 30 * $normalized;
        }

        if ($position > 25 && $position <= 50) {
            // Still good opportunity, but less urgent
            $normalized = 1 - (($position - 25) / 25);
            return 15 * $normalized;
        }

        if ($position > 50 && $position <= 100) {
            // Long tail opportunity
            $normalized = 1 - (($position - 50) / 50);
            return 5 * $normalized;
        }

        return 0; // Beyond page 10, very low priority
    }

    /**
     * Score CTR (low CTR with high impressions = opportunity)
     */
    protected function scoreCtr(float $ctr, int $impressions): float
    {
        if ($impressions < 100) {
            return 0; // Not enough data
        }

        // Low CTR (< 2%) with high impressions = big opportunity
        if ($ctr < 0.02) {
            // Very low CTR = high opportunity
            $ctrNormalized = $ctr / 0.02; // 0-1 scale
            return 20 * (1 - $ctrNormalized);
        }

        if ($ctr < 0.05) {
            // Low CTR = moderate opportunity
            $ctrNormalized = ($ctr - 0.02) / 0.03; // 0-1 scale
            return 10 * (1 - $ctrNormalized);
        }

        return 0; // CTR is reasonable
    }
}

