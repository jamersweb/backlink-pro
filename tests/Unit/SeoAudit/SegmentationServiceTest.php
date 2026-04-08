<?php

namespace Tests\Unit\SeoAudit;

use App\Models\Audit;
use App\Models\AuditPage;
use App\Services\SeoAudit\SegmentationService;
use Illuminate\Support\Str;
use Tests\TestCase;

class SegmentationServiceTest extends TestCase
{
    public function test_assigns_segments_from_url_and_indexability(): void
    {
        $audit = Audit::create([
            'url' => 'https://example.com',
            'normalized_url' => 'https://example.com',
            'status' => Audit::STATUS_COMPLETED,
            'mode' => Audit::MODE_GUEST,
            'share_token' => Str::random(32),
            'crawl_module_flags' => ['segmentation_enabled' => true],
        ]);

        AuditPage::create([
            'audit_id' => $audit->id,
            'url' => 'https://example.com/blog/intro',
            'status_code' => 200,
            'title' => 'Blog Intro',
            'word_count' => 300,
        ]);
        AuditPage::create([
            'audit_id' => $audit->id,
            'url' => 'https://example.com/products/widget',
            'status_code' => 200,
            'title' => 'Widget',
            'word_count' => 200,
        ]);
        AuditPage::create([
            'audit_id' => $audit->id,
            'url' => 'https://example.com/search?q=abc',
            'status_code' => 200,
            'title' => 'Search',
            'word_count' => 20,
        ]);
        AuditPage::create([
            'audit_id' => $audit->id,
            'url' => 'https://example.com/private',
            'status_code' => 200,
            'title' => 'Private',
            'robots_meta' => 'noindex,follow',
            'word_count' => 30,
        ]);

        $result = app(SegmentationService::class)->run($audit);
        $this->assertNotEmpty($result['counts']);

        $byUrl = $audit->pages()->pluck('segment_key', 'url');
        $this->assertSame('blog', $byUrl['https://example.com/blog/intro']);
        $this->assertSame('product', $byUrl['https://example.com/products/widget']);
        $this->assertSame('parameterized', $byUrl['https://example.com/search?q=abc']);
        $this->assertSame('noindex', $byUrl['https://example.com/private']);
    }
}

