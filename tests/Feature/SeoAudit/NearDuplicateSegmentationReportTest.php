<?php

namespace Tests\Feature\SeoAudit;

use App\Models\Audit;
use App\Models\AuditIssue;
use App\Models\AuditPage;
use App\Services\SeoAudit\AuditKpiBuilder;
use App\Services\SeoAudit\NearDuplicateContentService;
use App\Services\SeoAudit\ReportModuleBuilder;
use App\Services\SeoAudit\SegmentationService;
use Illuminate\Support\Str;
use Tests\TestCase;

class NearDuplicateSegmentationReportTest extends TestCase
{
    public function test_report_builder_contains_near_duplicate_segmentation_and_visualisation_modules(): void
    {
        $audit = Audit::create([
            'url' => 'https://example.com',
            'normalized_url' => 'https://example.com',
            'status' => Audit::STATUS_COMPLETED,
            'mode' => Audit::MODE_GUEST,
            'share_token' => Str::random(32),
            'crawl_module_flags' => [
                'near_duplicate_enabled' => true,
                'segmentation_enabled' => true,
                'site_visualisation_enabled' => true,
            ],
            'category_scores' => [
                'onpage' => 80, 'technical' => 80, 'performance' => 80, 'links' => 80,
                'social' => 80, 'usability' => 80, 'local' => 80, 'security' => 80,
            ],
            'overall_score' => 80,
            'overall_grade' => 'B',
        ]);

        AuditPage::create([
            'audit_id' => $audit->id,
            'url' => 'https://example.com/blog/a',
            'status_code' => 200,
            'title' => 'A',
            'meta_description' => 'A',
            'h1_count' => 1,
            'content_excerpt' => 'blue widget buying guide and comparison for beginners and experts',
            'word_count' => 380,
            'internal_links_count' => 15,
        ]);
        AuditPage::create([
            'audit_id' => $audit->id,
            'url' => 'https://example.com/blog/b',
            'status_code' => 200,
            'title' => 'B',
            'meta_description' => 'B',
            'h1_count' => 1,
            'content_excerpt' => 'blue widget buying guide comparison for experts and beginners with checklist',
            'word_count' => 300,
            'internal_links_count' => 8,
        ]);
        AuditPage::create([
            'audit_id' => $audit->id,
            'url' => 'https://example.com/products/widget',
            'status_code' => 200,
            'title' => 'Widget',
            'meta_description' => 'Widget',
            'h1_count' => 1,
            'content_excerpt' => 'product details pricing variants technical specification',
            'word_count' => 120,
            'internal_links_count' => 3,
        ]);

        AuditIssue::create([
            'audit_id' => $audit->id,
            'module_key' => 'technical',
            'code' => 'TECH_BROKEN_INTERNAL_LINKS',
            'issue_type' => 'TECH_BROKEN_INTERNAL_LINKS',
            'severity' => 'warning',
            'status' => 'open',
            'message' => 'Broken links',
            'impact' => 'medium',
            'effort' => 'easy',
            'score_penalty' => 4,
            'title' => 'Broken links',
            'description' => 'desc',
            'details_json' => [],
        ]);

        app(SegmentationService::class)->run($audit);
        app(NearDuplicateContentService::class)->run($audit);

        $audit->audit_kpis = app(AuditKpiBuilder::class)->build($audit);
        $audit->report_modules = app(ReportModuleBuilder::class)->build($audit);
        $audit->save();

        $modules = collect($audit->report_modules['modules'] ?? []);
        $near = $modules->firstWhere('module_key', 'near_duplicate_content');
        $seg = $modules->firstWhere('module_key', 'segmentation');
        $viz = $modules->firstWhere('module_key', 'site_visualisations');

        $this->assertNotNull($near);
        $this->assertNotNull($seg);
        $this->assertNotNull($viz);
        $this->assertNotEmpty($near['tables'] ?? []);
        $this->assertNotEmpty($seg['tables'] ?? []);
        $this->assertNotEmpty($viz['charts'] ?? []);
    }
}

