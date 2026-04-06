<?php

namespace Tests\Feature\SeoAudit;

use App\Models\Audit;
use App\Models\AuditIssue;
use App\Models\AuditPage;
use App\Services\SeoAudit\AuditKpiBuilder;
use App\Services\SeoAudit\ReportModuleBuilder;
use Illuminate\Support\Str;
use Tests\TestCase;

class JsRenderingReportTest extends TestCase
{
    protected function seedAudit(): Audit
    {
        return Audit::create([
            'url' => 'https://example.com',
            'normalized_url' => 'https://example.com',
            'status' => Audit::STATUS_COMPLETED,
            'mode' => Audit::MODE_GUEST,
            'share_token' => Str::random(32),
            'crawl_module_flags' => [
                'js_rendering_enabled' => true,
                'near_duplicate_enabled' => false,
                'spelling_grammar_enabled' => false,
                'custom_source_search_enabled' => false,
                'custom_extraction_enabled' => false,
                'forms_auth_enabled' => false,
                'segmentation_enabled' => false,
                'link_metrics_enabled' => false,
                'site_visualisation_enabled' => false,
            ],
            'overall_score' => 80,
            'overall_grade' => 'B',
            'category_scores' => [
                'onpage' => 80,
                'technical' => 80,
                'performance' => 80,
                'links' => 80,
                'social' => 80,
                'usability' => 80,
                'local' => 80,
                'security' => 80,
            ],
        ]);
    }

    public function test_kpi_and_module_payload_include_js_rendering_section(): void
    {
        $audit = $this->seedAudit();
        $page = AuditPage::create([
            'audit_id' => $audit->id,
            'url' => 'https://example.com',
            'status_code' => 200,
            'title' => '',
            'title_len' => 0,
            'meta_description' => null,
            'meta_len' => 0,
            'h1_count' => 1,
            'h2_count' => 0,
            'h3_count' => 0,
            'word_count' => 100,
            'visible_text_length' => 400,
            'images_total' => 0,
            'images_missing_alt' => 0,
            'internal_links_count' => 1,
            'external_links_count' => 0,
            'js_render_snapshot' => [
                'navigation' => ['ok' => true],
                'rendered' => ['title' => 'X'],
            ],
        ]);

        AuditIssue::create([
            'audit_id' => $audit->id,
            'url' => $page->url,
            'module_key' => 'js_rendering',
            'issue_type' => 'JS_RENDER_TITLE_APPEARED',
            'code' => 'JS_RENDER_TITLE_APPEARED',
            'category' => 'technical',
            'title' => 'Title after JS',
            'description' => 'Test',
            'impact' => 'high',
            'effort' => 'medium',
            'score_penalty' => 10,
            'affected_count' => 1,
            'sample_urls' => [$page->url],
            'recommendation' => 'Use SSR',
            'severity' => 'critical',
            'status' => 'open',
            'message' => 'Title after JS',
            'details_json' => [
                'diff_type' => 'title_appeared_after_render',
                'filter_tags' => ['missing_in_raw'],
                'raw' => ['title' => ''],
                'rendered' => ['title' => 'X'],
            ],
        ]);

        $kpis = app(AuditKpiBuilder::class)->build($audit);
        $this->assertArrayHasKey('js_rendering', $kpis);
        $this->assertSame(1, $kpis['js_rendering']['diff_issue_count']);
        $this->assertNotEmpty($kpis['js_rendering']['affected_urls_table']);

        $audit->audit_kpis = $kpis;
        $audit->save();

        $report = app(ReportModuleBuilder::class)->build($audit);
        $js = collect($report['modules'] ?? [])->firstWhere('module_key', 'js_rendering');
        $this->assertNotNull($js);
        $this->assertNotEmpty($js['filters']['presets'] ?? []);
        $this->assertNotEmpty($js['issues']);
    }

    public function test_modules_csv_for_js_rendering_exports_issue_rows(): void
    {
        $audit = $this->seedAudit();
        AuditIssue::create([
            'audit_id' => $audit->id,
            'url' => 'https://example.com/u',
            'module_key' => 'js_rendering',
            'issue_type' => 'JS_RENDER_CANONICAL_DIVERGENCE',
            'code' => 'JS_RENDER_CANONICAL_DIVERGENCE',
            'category' => 'technical',
            'title' => 'Canonical',
            'description' => 'Desc',
            'impact' => 'high',
            'effort' => 'medium',
            'score_penalty' => 12,
            'affected_count' => 1,
            'sample_urls' => ['https://example.com/u'],
            'recommendation' => 'Fix canonical',
            'severity' => 'critical',
            'status' => 'open',
            'message' => 'Canonical',
            'details_json' => [
                'diff_type' => 'canonical_changed_after_render',
                'raw' => ['canonical_url' => 'https://example.com/a'],
                'rendered' => ['canonical_url' => 'https://example.com/b'],
            ],
        ]);

        $audit->audit_kpis = app(AuditKpiBuilder::class)->build($audit);
        $audit->report_modules = app(ReportModuleBuilder::class)->build($audit);
        $audit->save();

        $res = $this->get("/audit/{$audit->id}/export/modules.csv?token={$audit->share_token}&module_key=js_rendering");
        $res->assertOk();
        ob_start();
        $res->sendContent();
        $body = (string) ob_get_clean();
        $this->assertStringContainsString('Diff Type', $body);
        $this->assertStringContainsString('canonical_changed_after_render', $body);
    }
}
