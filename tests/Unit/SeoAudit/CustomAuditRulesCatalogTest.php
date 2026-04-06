<?php

namespace Tests\Unit\SeoAudit;

use App\Models\Audit;
use App\Models\Organization;
use App\Models\User;
use App\Services\SeoAudit\CustomAuditRulesCatalog;
use Illuminate\Support\Str;
use Tests\TestCase;

class CustomAuditRulesCatalogTest extends TestCase
{
    public function test_audit_rules_override_organization_by_id(): void
    {
        $user = User::factory()->create();
        $org = Organization::create([
            'name' => 'Acme Org',
            'slug' => 'acme-'.Str::random(8),
            'owner_user_id' => $user->id,
            'custom_source_search_rules' => ['rules' => [
                [
                    'id' => 'rule_a',
                    'rule_name' => 'Org name',
                    'target_scope' => 'raw_html',
                    'match_type' => 'contains',
                    'pattern' => 'orgpat',
                    'severity' => 'warning',
                    'expect_match' => true,
                ],
            ]],
        ]);

        $audit = Audit::create([
            'organization_id' => $org->id,
            'url' => 'https://example.com',
            'normalized_url' => 'https://example.com',
            'status' => Audit::STATUS_COMPLETED,
            'mode' => Audit::MODE_GUEST,
            'share_token' => Str::random(32),
            'custom_source_search_rules' => ['rules' => [
                [
                    'id' => 'rule_a',
                    'rule_name' => 'Audit override',
                    'target_scope' => 'raw_html',
                    'match_type' => 'contains',
                    'pattern' => 'auditpat',
                    'severity' => 'critical',
                    'expect_match' => false,
                ],
            ]],
        ]);

        $merged = CustomAuditRulesCatalog::mergedSearchRules($audit);
        $this->assertCount(1, $merged);
        $this->assertSame('auditpat', $merged[0]['pattern']);
        $this->assertSame('Audit override', $merged[0]['rule_name']);
        $this->assertFalse($merged[0]['expect_match']);
    }

    public function test_new_audit_rule_appends_after_org_rules(): void
    {
        $user = User::factory()->create();
        $org = Organization::create([
            'name' => 'Beta',
            'slug' => 'beta-'.Str::random(8),
            'owner_user_id' => $user->id,
            'custom_extraction_rules' => ['rules' => [
                ['id' => 'a', 'rule_name' => 'A', 'target_scope' => 'raw_html', 'extraction_type' => 'css', 'extractor' => 'h1', 'multiple' => false],
            ]],
        ]);
        $audit = Audit::create([
            'organization_id' => $org->id,
            'url' => 'https://ex.test',
            'normalized_url' => 'https://ex.test',
            'status' => Audit::STATUS_COMPLETED,
            'mode' => Audit::MODE_GUEST,
            'share_token' => Str::random(32),
            'custom_extraction_rules' => ['rules' => [
                ['id' => 'b', 'rule_name' => 'B', 'target_scope' => 'raw_html', 'extraction_type' => 'xpath', 'extractor' => '//p', 'multiple' => false],
            ]],
        ]);
        $merged = CustomAuditRulesCatalog::mergedExtractionRules($audit);
        $this->assertCount(2, $merged);
        $this->assertSame('a', $merged[0]['id']);
        $this->assertSame('b', $merged[1]['id']);
    }
}
