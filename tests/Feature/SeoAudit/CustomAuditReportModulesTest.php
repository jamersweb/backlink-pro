<?php

namespace Tests\Feature\SeoAudit;

use App\Models\Audit;
use App\Models\AuditCustomExtractionResult;
use App\Models\AuditCustomSearchResult;
use App\Models\AuditPage;
use App\Services\SeoAudit\AuditKpiBuilder;
use App\Services\SeoAudit\ReportModuleBuilder;
use Illuminate\Support\Str;
use Tests\TestCase;

class CustomAuditReportModulesTest extends TestCase
{
    protected function seedAuditWithCustomModules(): Audit
    {
        $audit = Audit::create([
            'url' => 'https://example.com',
            'normalized_url' => 'https://example.com',
            'status' => Audit::STATUS_COMPLETED,
            'mode' => Audit::MODE_GUEST,
            'share_token' => Str::random(32),
            'crawl_module_flags' => [
                'js_rendering_enabled' => false,
                'near_duplicate_enabled' => false,
                'spelling_grammar_enabled' => false,
                'custom_source_search_enabled' => true,
                'custom_extraction_enabled' => true,
                'forms_auth_enabled' => false,
                'segmentation_enabled' => true,
                'link_metrics_enabled' => false,
                'site_visualisation_enabled' => false,
            ],
        ]);

        $page = AuditPage::create([
            'audit_id' => $audit->id,
            'url' => 'https://example.com/',
            'status_code' => 200,
            'title' => 'T',
            'title_len' => 1,
            'meta_description' => null,
            'meta_len' => 0,
            'h1_count' => 1,
            'word_count' => 50,
            'images_total' => 0,
            'images_missing_alt' => 0,
            'internal_links_count' => 0,
            'external_links_count' => 0,
        ]);

        AuditCustomSearchResult::create([
            'audit_id' => $audit->id,
            'audit_page_id' => $page->id,
            'url' => $page->url,
            'rule_key' => 'ga_id',
            'rule_name' => 'GA present',
            'target_scope' => 'raw_html',
            'match_type' => 'contains',
            'pattern_preview' => 'G-',
            'expect_match' => true,
            'matched' => true,
            'match_count' => 1,
            'sample_match' => 'G-TEST',
            'severity' => 'warning',
            'error_message' => null,
            'segment_key' => 'main',
        ]);

        AuditCustomExtractionResult::create([
            'audit_id' => $audit->id,
            'audit_page_id' => $page->id,
            'url' => $page->url,
            'rule_key' => 'canon',
            'rule_name' => 'Canonical href',
            'target_scope' => 'raw_html',
            'extraction_type' => 'css',
            'extractor' => 'link[rel=canonical]',
            'attribute' => 'href',
            'multiple' => false,
            'values' => ['https://example.com/'],
            'missing' => false,
            'error_message' => null,
            'segment_key' => 'main',
            'fingerprint' => md5('https://example.com/'),
        ]);

        $audit->audit_kpis = app(AuditKpiBuilder::class)->build($audit);
        $audit->report_modules = app(ReportModuleBuilder::class)->build($audit);
        $audit->save();

        return $audit->fresh();
    }

    public function test_report_modules_include_custom_sections_with_tables(): void
    {
        $audit = $this->seedAuditWithCustomModules();
        $modules = collect($audit->report_modules['modules'] ?? []);
        $search = $modules->firstWhere('module_key', 'custom_source_search');
        $extract = $modules->firstWhere('module_key', 'custom_extraction');

        $this->assertNotNull($search);
        $this->assertNotNull($extract);
        $tableKeys = collect($search['tables'] ?? [])->pluck('key')->all();
        $this->assertContains('custom_search_rule_summaries', $tableKeys);
        $this->assertContains('custom_search_results', $tableKeys);
        $extKeys = collect($extract['tables'] ?? [])->pluck('key')->all();
        $this->assertContains('custom_extraction_per_url', $extKeys);
    }

    public function test_module_csv_exports_for_custom_modules(): void
    {
        $audit = $this->seedAuditWithCustomModules();

        $csvSearch = $this->get("/audit/{$audit->id}/export/modules.csv?token={$audit->share_token}&module_key=custom_source_search");
        $csvSearch->assertOk();
        $bodySearch = $csvSearch->streamedContent();
        $this->assertStringContainsString('Rule Key', $bodySearch);
        $this->assertStringContainsString('ga_id', $bodySearch);

        $csvExt = $this->get("/audit/{$audit->id}/export/modules.csv?token={$audit->share_token}&module_key=custom_extraction");
        $csvExt->assertOk();
        $bodyExt = $csvExt->streamedContent();
        $this->assertStringContainsString('Extraction Type', $bodyExt);
        $this->assertStringContainsString('canon', $bodyExt);
    }
}
