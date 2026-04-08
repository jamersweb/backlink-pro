<?php

namespace Tests\Feature\SeoAudit;

use App\Models\Audit;
use App\Services\SeoAudit\ReportModuleBuilder;
use App\Services\SeoAudit\SpellingGrammarService;
use Illuminate\Support\Str;
use Tests\TestCase;

class SpellingGrammarReportTest extends TestCase
{
    public function test_spelling_module_appears_in_report_payload_with_kpis(): void
    {
        $audit = Audit::create([
            'url' => 'https://example.com',
            'normalized_url' => 'https://example.com',
            'status' => Audit::STATUS_COMPLETED,
            'mode' => Audit::MODE_GUEST,
            'share_token' => Str::random(32),
            'crawl_module_flags' => [
                'spelling_grammar_enabled' => true,
            ],
            'category_scores' => [
                'onpage' => 80, 'technical' => 80, 'performance' => 80, 'links' => 80,
                'social' => 80, 'usability' => 80, 'local' => 80, 'security' => 80,
            ],
            'overall_score' => 80,
            'overall_grade' => 'B',
            'audit_kpis' => [
                'spelling_grammar' => [
                    'module_enabled' => true,
                    'pages_with_issues' => 1,
                    'total_issues' => 2,
                    'high_confidence_issues' => 1,
                    'by_kind' => ['spelling' => 2],
                    'affected_urls_table' => [],
                ],
            ],
        ]);

        $report = app(ReportModuleBuilder::class)->build($audit);
        $moduleKeys = collect($report['modules'] ?? [])->pluck('module_key')->all();

        $this->assertContains('spelling_grammar', $moduleKeys);
        $sp = collect($report['modules'])->firstWhere('module_key', 'spelling_grammar');
        $this->assertNotNull($sp);
        $this->assertNotEmpty($sp['filters']['presets'] ?? []);
        $this->assertArrayHasKey('issues', $sp);
        $this->assertNotEmpty($sp['summary_metrics'] ?? []);
    }

    public function test_spelling_service_creates_issues_for_typos(): void
    {
        $audit = Audit::create([
            'url' => 'https://example.com',
            'normalized_url' => 'https://example.com',
            'status' => Audit::STATUS_COMPLETED,
            'mode' => Audit::MODE_GUEST,
            'share_token' => Str::random(32),
            'crawl_module_flags' => [
                'spelling_grammar_enabled' => true,
            ],
        ]);

        \App\Models\AuditPage::create([
            'audit_id' => $audit->id,
            'url' => 'https://example.com/page',
            'status_code' => 200,
            'title' => 'Demo',
            'meta_description' => 'Demo',
            'h1_count' => 1,
            'content_excerpt' => null,
            'visible_main_text' => 'Our recieved shipment includes teh newest widgets.',
            'word_count' => 8,
            'internal_links_count' => 0,
        ]);

        app(SpellingGrammarService::class)->run($audit);

        $issues = $audit->issues()->where('module_key', 'spelling_grammar')->get();
        $this->assertGreaterThanOrEqual(1, $issues->count());
        $codes = $issues->pluck('details_json.issue_kind', 'details_json.issue_text');
        $this->assertTrue($issues->contains(fn ($i) => ($i->details_json['issue_text'] ?? '') === 'teh'));
    }
}
