<?php

namespace Tests\Feature\SeoAudit;

use App\Models\Audit;
use App\Models\AuditIssue;
use App\Models\AuditPage;
use App\Services\SeoAudit\AuditKpiBuilder;
use App\Services\SeoAudit\FormsAuthIssueService;
use App\Services\SeoAudit\ReportModuleBuilder;
use Illuminate\Support\Str;
use ReflectionMethod;
use Tests\TestCase;

class FormsAuthReportTest extends TestCase
{
    protected function makeAuditWithFormsAuth(): Audit
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
                'custom_source_search_enabled' => false,
                'custom_extraction_enabled' => false,
                'forms_auth_enabled' => true,
                'segmentation_enabled' => false,
                'link_metrics_enabled' => false,
                'site_visualisation_enabled' => false,
            ],
            'forms_auth_login_url' => 'https://example.com/login',
            'forms_auth_state' => [
                'login_success' => true,
                'username_masked' => 'a**n@test.com',
                'pages_likely_authenticated' => 1,
                'pages_blocked_http' => 0,
                'pages_login_redirect_suspected' => 0,
                'total_pages_crawled' => 1,
            ],
        ]);

        AuditPage::create([
            'audit_id' => $audit->id,
            'url' => 'https://example.com/app',
            'status_code' => 200,
            'title' => 'App',
            'title_len' => 3,
            'meta_description' => null,
            'meta_len' => 0,
            'h1_count' => 1,
            'word_count' => 120,
            'images_total' => 0,
            'images_missing_alt' => 0,
            'internal_links_count' => 0,
            'external_links_count' => 0,
            'auth_crawl_metadata' => [
                'likely_authenticated_content' => true,
                'http_auth_blocked' => false,
                'redirected_to_login_suspected' => false,
            ],
        ]);

        $engine = new \App\Services\SeoAudit\RulesEngine;
        $engine->createCustomIssue($audit, [
            'url' => $audit->normalized_url,
            'code' => 'FORMS_AUTH_HTTP_BLOCKED',
            'category' => 'technical',
            'module_key' => 'forms_auth_summary',
            'title' => 'Test',
            'message' => 'blocked',
            'description' => 'x',
            'impact' => AuditIssue::IMPACT_MEDIUM,
            'effort' => AuditIssue::EFFORT_MEDIUM,
            'severity' => AuditIssue::SEVERITY_WARNING,
            'score_penalty' => 2,
            'recommendation' => 'fix',
            'details_json' => ['issue_kind' => 'http_blocked'],
            'affected_count' => 2,
        ]);

        return $audit->fresh();
    }

    public function test_kpi_and_report_module_contain_forms_auth_summary(): void
    {
        $audit = $this->makeAuditWithFormsAuth();
        $m = new ReflectionMethod(AuditKpiBuilder::class, 'buildFormsAuthSummarySection');
        $m->setAccessible(true);
        $formsSection = $m->invoke(new AuditKpiBuilder, $audit);

        $audit->audit_kpis = [
            'segmentation' => ['url_counts' => []],
            'forms_auth_summary' => $formsSection,
        ];
        $audit->report_modules = app(ReportModuleBuilder::class)->build($audit);
        $audit->save();

        $fa = $audit->audit_kpis['forms_auth_summary'] ?? [];
        $this->assertTrue($fa['module_enabled'] ?? false);
        $this->assertTrue($fa['login_success'] ?? false);
        $this->assertSame('https://example.com/login', $fa['login_url_display'] ?? null);

        $mods = collect($audit->report_modules['modules'] ?? []);
        $m = $mods->firstWhere('module_key', 'forms_auth_summary');
        $this->assertNotNull($m);
        $this->assertNotEmpty($m['summary_metrics'] ?? []);
        $keys = collect($m['tables'] ?? [])->pluck('key')->all();
        $this->assertContains('forms_auth_summary_card', $keys);
    }

    public function test_forms_auth_issue_service_sync_creates_issues_on_login_failure(): void
    {
        $audit = Audit::create([
            'url' => 'https://example.org',
            'normalized_url' => 'https://example.org',
            'status' => Audit::STATUS_RUNNING,
            'mode' => Audit::MODE_GUEST,
            'share_token' => Str::random(32),
            'crawl_module_flags' => [
                'forms_auth_enabled' => true,
                'js_rendering_enabled' => false,
                'near_duplicate_enabled' => false,
                'spelling_grammar_enabled' => false,
                'custom_source_search_enabled' => false,
                'custom_extraction_enabled' => false,
                'segmentation_enabled' => false,
                'link_metrics_enabled' => false,
                'site_visualisation_enabled' => false,
            ],
            'forms_auth_state' => [
                'login_success' => false,
                'login_error' => 'Missing login URL, username, or password.',
            ],
        ]);

        $pen = app(FormsAuthIssueService::class)->sync($audit);
        $this->assertGreaterThan(0, $pen);
        $this->assertTrue($audit->issues()->where('module_key', 'forms_auth_summary')->exists());
    }
}
