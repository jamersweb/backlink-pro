<?php

namespace Tests\Unit\SeoAudit;

use App\Models\Audit;
use App\Models\AuditPage;
use App\Services\SeoAudit\JsRenderingDiffService;
use Illuminate\Support\Str;
use Tests\TestCase;

class JsRenderingDiffServiceTest extends TestCase
{
    protected function makeAudit(): Audit
    {
        return Audit::create([
            'url' => 'https://example.com',
            'normalized_url' => 'https://example.com',
            'status' => Audit::STATUS_COMPLETED,
            'mode' => Audit::MODE_GUEST,
            'share_token' => Str::random(32),
            'crawl_module_flags' => ['js_rendering_enabled' => true],
        ]);
    }

    public function test_detects_title_only_after_render(): void
    {
        $audit = $this->makeAudit();
        $page = AuditPage::create([
            'audit_id' => $audit->id,
            'url' => 'https://example.com/app',
            'status_code' => 200,
            'title' => '',
            'title_len' => 0,
            'meta_description' => 'raw meta',
            'meta_len' => 8,
            'canonical_url' => null,
            'robots_meta' => '',
            'x_robots_tag' => null,
            'visible_text_length' => 100,
            'word_count' => 20,
            'internal_links_count' => 0,
            'h1_count' => 1,
            'h2_count' => 0,
            'h3_count' => 0,
            'images_total' => 0,
            'images_missing_alt' => 0,
            'external_links_count' => 0,
        ]);

        $page->js_render_snapshot = [
            'navigation' => ['ok' => true, 'error' => null, 'http_status' => 200],
            'rendered' => [
                'title' => 'Client title',
                'meta_description' => 'raw meta',
                'canonical_url' => null,
                'robots_meta' => '',
                'x_robots_tag' => null,
                'visible_text_length' => 100,
                'word_count' => 20,
                'internal_links_count' => 0,
                'indexability' => ['noindex' => false, 'nofollow' => false],
            ],
        ];
        $page->save();

        app(JsRenderingDiffService::class)->analyzePage($audit, $page->fresh());

        $issue = $audit->issues()->where('code', 'JS_RENDER_TITLE_APPEARED')->first();
        $this->assertNotNull($issue);
        $this->assertSame('js_rendering', $issue->module_key);
        $tags = $issue->details_json['filter_tags'] ?? [];
        $this->assertContains('missing_in_raw', $tags);
    }

    public function test_detects_internal_links_only_after_render(): void
    {
        $audit = $this->makeAudit();
        $page = AuditPage::create([
            'audit_id' => $audit->id,
            'url' => 'https://example.com/spa',
            'status_code' => 200,
            'title' => 'SPA',
            'title_len' => 3,
            'meta_description' => 'm',
            'meta_len' => 1,
            'canonical_url' => 'https://example.com/spa',
            'robots_meta' => 'index, follow',
            'x_robots_tag' => null,
            'visible_text_length' => 200,
            'word_count' => 40,
            'internal_links_count' => 0,
            'h1_count' => 1,
            'h2_count' => 0,
            'h3_count' => 0,
            'images_total' => 0,
            'images_missing_alt' => 0,
            'external_links_count' => 0,
        ]);

        $page->js_render_snapshot = [
            'navigation' => ['ok' => true],
            'rendered' => [
                'title' => 'SPA',
                'meta_description' => 'm',
                'canonical_url' => 'https://example.com/spa',
                'robots_meta' => 'index, follow',
                'visible_text_length' => 200,
                'word_count' => 40,
                'internal_links_count' => 4,
                'indexability' => ['noindex' => false, 'nofollow' => false],
            ],
        ];
        $page->save();

        app(JsRenderingDiffService::class)->analyzePage($audit, $page->fresh());

        $this->assertNotNull($audit->issues()->where('code', 'JS_RENDER_INTERNAL_LINKS_AFTER_RENDER')->first());
    }
}
