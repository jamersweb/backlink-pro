<?php

namespace Tests\Unit\SeoAudit;

use App\Services\SeoAudit\LinkMetrics\LinkEquityScoring;
use Tests\TestCase;

class LinkEquityScoringTest extends TestCase
{
    public function test_tier_classification_respects_thresholds(): void
    {
        $tiers = [
            'high_referring_domains' => 50,
            'high_backlinks' => 200,
            'medium_referring_domains' => 10,
            'medium_backlinks' => 40,
        ];

        $this->assertSame('high', LinkEquityScoring::tier(['referring_domains' => 60, 'backlinks' => 0], $tiers));
        $this->assertSame('high', LinkEquityScoring::tier(['referring_domains' => 5, 'backlinks' => 250], $tiers));
        $this->assertSame('medium', LinkEquityScoring::tier(['referring_domains' => 15, 'backlinks' => 5], $tiers));
        $this->assertSame('low', LinkEquityScoring::tier(['referring_domains' => 1, 'backlinks' => 1], $tiers));
    }

    public function test_equity_score_weights_referring_domains(): void
    {
        $this->assertSame(12 * 3 + 5, LinkEquityScoring::equityScore(['referring_domains' => 3, 'backlinks' => 5]));
    }
}
