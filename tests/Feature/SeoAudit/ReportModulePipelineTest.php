<?php

namespace Tests\Feature\SeoAudit;

use App\Models\Audit;
use App\Models\AuditPage;
use App\Services\SeoAudit\ReportModuleBuilder;
use App\Services\SeoAudit\RulesEngine;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReportModulePipelineTest extends TestCase
{
    protected function seedAudit(array $overrides = []): Audit
    {
        return Audit::create(array_merge([
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
                'segmentation_enabled' => true,
                'link_metrics_enabled' => false,
                'site_visualisation_enabled' => false,
            ],
            'audit_kpis' => [
                'overview' => [
                    'overall_score' => 70,
                    'issues_total' => 0,
                    'pages_crawled_count' => 1,
                ],
                'on_page_seo' => [
                    'title_length' => 0,
                    'duplicate_titles_table' => [],
                    'missing_meta_table' => [],
                ],
                'technical' => [
                    'broken_links_examples' => [],
                ],
            ],
        ], $overrides));
    }

    public function test_issue_persistence_populates_normalized_fields(): void
    {
        $audit = $this->seedAudit();
        $page = AuditPage::create([
            'audit_id' => $audit->id,
            'url' => 'https://example.com',
            'status_code' => 200,
            'title' => null,
            'title_len' => 0,
            'meta_description' => null,
            'meta_len' => 0,
            'h1_count' => 0,
            'word_count' => 50,
            'images_total' => 1,
            'images_missing_alt' => 1,
            'internal_links_count' => 5,
            'external_links_count' => 1,
        ]);

        $engine = new RulesEngine();
        $engine->evaluate($audit, $page);

        $issue = $audit->issues()->first();
        $this->assertNotNull($issue);
        $this->assertSame($audit->id, $issue->audit_run_id);
        $this->assertNotEmpty($issue->module_key);
        $this->assertNotEmpty($issue->issue_type);
        $this->assertContains($issue->severity, ['critical', 'warning', 'info']);
        $this->assertSame('open', $issue->status);
        $this->assertNotEmpty($issue->message);
        $this->assertNotNull($issue->details_json);
        $this->assertNotNull($issue->discovered_at);
    }

    public function test_report_response_contains_module_payload(): void
    {
        $audit = $this->seedAudit();
        $audit->report_modules = app(ReportModuleBuilder::class)->build($audit);
        $audit->save();

        $response = $this->getJson("/audit/{$audit->id}?token={$audit->share_token}");
        $response->assertOk();
        $response->assertJsonStructure([
            'audit' => [
                'report_modules' => [
                    'version',
                    'module_order',
                    'modules',
                ],
            ],
        ]);
    }

    public function test_modules_exports_return_csv_and_json(): void
    {
        $audit = $this->seedAudit();
        $audit->report_modules = app(ReportModuleBuilder::class)->build($audit);
        $audit->save();

        $csv = $this->get("/audit/{$audit->id}/export/modules.csv?token={$audit->share_token}&module_key=overview");
        $csv->assertOk();
        $this->assertStringContainsString('text/csv', $csv->headers->get('Content-Type'));

        $json = $this->getJson("/audit/{$audit->id}/export/modules.json?token={$audit->share_token}&module_key=overview");
        $json->assertOk();
        $json->assertJsonStructure(['module_order', 'modules']);
    }

}

