<?php

namespace Tests\Feature\SeoAudit;

use App\Models\Audit;
use App\Models\AuditPage;
use App\Models\Domain;
use App\Models\DomainBacklink;
use App\Models\DomainBacklinkRun;
use App\Models\User;
use App\Services\SeoAudit\AuditKpiBuilder;
use App\Services\SeoAudit\AuditKpiSanitizer;
use App\Services\SeoAudit\ReportModuleBuilder;
use Illuminate\Support\Str;
use Tests\TestCase;

class LinkMetricsReportTest extends TestCase
{
    public function test_kpi_and_report_module_include_link_metrics_tables_when_enabled(): void
    {
        config(['seo_audit.link_metrics.driver' => 'null']);

        $audit = Audit::create([
            'user_id' => null,
            'url' => 'https://demo.example/page',
            'normalized_url' => 'https://demo.example/page',
            'status' => Audit::STATUS_COMPLETED,
            'mode' => Audit::MODE_GUEST,
            'share_token' => Str::random(32),
            'crawl_module_flags' => [
                'js_rendering_enabled' => false,
                'near_duplicate_enabled' => false,
                'spelling_grammar_enabled' => false,
                'custom_source_search_enabled' => false,
                'custom_extraction_enabled' => false,
                'forms_auth_enabled' => false,
                'segmentation_enabled' => false,
                'link_metrics_enabled' => true,
                'site_visualisation_enabled' => false,
            ],
            'overall_score' => 80,
            'overall_grade' => 'B',
            'category_scores' => ['onpage' => 80, 'technical' => 80],
        ]);

        AuditPage::create([
            'audit_id' => $audit->id,
            'url' => 'https://demo.example/broken',
            'status_code' => 404,
            'title' => 'X',
            'title_len' => 1,
            'h1_count' => 1,
            'internal_links_count' => 1,
            'link_metrics_json' => [
                'provider' => 'null',
                'referring_domains' => 12,
                'backlinks' => 40,
                'authority_score' => null,
                'top_anchors' => [],
                'global_anchor_themes' => [],
            ],
        ]);

        $kpis = app(AuditKpiSanitizer::class)->sanitize((new AuditKpiBuilder())->build($audit));
        $this->assertTrue($kpis['link_metrics']['module_enabled'] ?? false);
        $this->assertNotEmpty($kpis['link_metrics']['top_linked_broken_pages'] ?? []);

        $audit->audit_kpis = $kpis;
        $audit->save();

        $modules = app(ReportModuleBuilder::class)->build($audit);
        $lm = collect($modules['modules'] ?? [])->firstWhere('module_key', 'link_metrics');
        $this->assertNotNull($lm);
        $keys = collect($lm['tables'] ?? [])->pluck('key')->all();
        $this->assertContains('top_linked_broken_pages', $keys);
    }

    public function test_domain_backlink_provider_maps_normalized_targets(): void
    {
        config(['seo_audit.link_metrics.driver' => 'domain_backlinks']);

        $user = User::factory()->create();
        $audit = Audit::create([
            'user_id' => $user->id,
            'url' => 'https://www.target.test/',
            'normalized_url' => 'https://target.test',
            'status' => Audit::STATUS_COMPLETED,
            'mode' => Audit::MODE_AUTH,
            'share_token' => Str::random(32),
            'crawl_module_flags' => ['link_metrics_enabled' => true],
            'overall_score' => 70,
            'overall_grade' => 'B',
            'category_scores' => ['onpage' => 70, 'technical' => 70],
        ]);

        $domain = Domain::create([
            'user_id' => $user->id,
            'name' => 'target.test',
            'host' => 'target.test',
            'status' => Domain::STATUS_ACTIVE,
        ]);

        $run = DomainBacklinkRun::create([
            'domain_id' => $domain->id,
            'user_id' => $user->id,
            'status' => DomainBacklinkRun::STATUS_COMPLETED,
            'provider' => 'test',
            'started_at' => now(),
            'finished_at' => now(),
        ]);

        DomainBacklink::create([
            'run_id' => $run->id,
            'fingerprint' => DomainBacklink::generateFingerprint('https://src/a', 'https://www.target.test/foo', 'follow', 'kw'),
            'source_url' => 'https://src/a',
            'source_domain' => 'src-a.test',
            'target_url' => 'https://www.target.test/foo',
            'anchor' => 'widgets',
            'rel' => DomainBacklink::REL_FOLLOW,
            'quality_score' => 70,
        ]);
        DomainBacklink::create([
            'run_id' => $run->id,
            'fingerprint' => DomainBacklink::generateFingerprint('https://src/b', 'https://www.target.test/foo', 'follow', null),
            'source_url' => 'https://src/b',
            'source_domain' => 'src-b.test',
            'target_url' => 'https://www.target.test/foo',
            'anchor' => 'widgets',
            'rel' => DomainBacklink::REL_FOLLOW,
            'quality_score' => 80,
        ]);

        AuditPage::create([
            'audit_id' => $audit->id,
            'url' => 'https://target.test/foo',
            'status_code' => 200,
            'title' => 'Foo',
            'title_len' => 3,
            'h1_count' => 1,
            'internal_links_count' => 3,
        ]);

        app(\App\Services\SeoAudit\LinkMetrics\LinkMetricsEnrichmentService::class)->enrich($audit);
        $page = AuditPage::where('audit_id', $audit->id)->first();
        $this->assertSame(2, (int) ($page->link_metrics_json['backlinks'] ?? 0));
        $this->assertSame(2, (int) ($page->link_metrics_json['referring_domains'] ?? 0));
        $this->assertSame('domain_backlinks', $page->link_metrics_json['provider'] ?? null);
    }
}
