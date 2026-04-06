<?php

namespace App\Services\SeoAudit;

use App\Models\Audit;
use App\Models\AuditCustomSearchResult;
use App\Models\AuditIssue;
use App\Models\AuditPage;

class CustomSourceSearchService
{
    /** @var list<string> */
    protected const CRAWL_SCOPES = ['raw_html', 'response_headers', 'visible_text'];

    public function shouldRun(Audit $audit): bool
    {
        return !empty($audit->crawl_module_flags['custom_source_search_enabled']);
    }

    public function runCrawlScopes(
        Audit $audit,
        AuditPage $page,
        string $rawHtml,
        array $responseHeaders,
        string $visibleText
    ): void {
        if (!$this->shouldRun($audit)) {
            return;
        }

        $rules = CustomAuditRulesCatalog::mergedSearchRules($audit);
        if ($rules === []) {
            return;
        }

        AuditCustomSearchResult::where('audit_page_id', $page->id)
            ->whereIn('target_scope', self::CRAWL_SCOPES)
            ->delete();

        foreach ($rules as $rule) {
            if (!in_array($rule['target_scope'] ?? '', self::CRAWL_SCOPES, true)) {
                continue;
            }
            $this->persistResult($audit, $page, $rule, $rawHtml, $responseHeaders, $visibleText, null);
        }
    }

    public function runRenderedScope(Audit $audit): void
    {
        if (!$this->shouldRun($audit)) {
            return;
        }

        $rules = CustomAuditRulesCatalog::mergedSearchRules($audit);
        $renderRules = array_values(array_filter($rules, fn ($r) => ($r['target_scope'] ?? '') === 'rendered_html'));
        if ($renderRules === []) {
            return;
        }

        foreach ($audit->pages()->get() as $page) {
            AuditCustomSearchResult::where('audit_page_id', $page->id)
                ->where('target_scope', 'rendered_html')
                ->delete();

            $rendered = data_get($page->js_render_snapshot, 'rendered.body_html');
            $headers = is_array($page->response_headers_json) ? $page->response_headers_json : [];
            $visible = (string) ($page->visible_main_text ?? $page->content_excerpt ?? '');

            foreach ($renderRules as $rule) {
                $this->persistResult(
                    $audit,
                    $page,
                    $rule,
                    '',
                    $headers,
                    $visible,
                    is_string($rendered) ? $rendered : null
                );
            }
        }
    }

    /**
     * @param  array<string, string|list<string>>  $responseHeaders
     */
    protected function persistResult(
        Audit $audit,
        AuditPage $page,
        array $rule,
        string $rawHtml,
        array $responseHeaders,
        string $visibleText,
        ?string $renderedHtml
    ): void {
        $scope = $rule['target_scope'] ?? 'raw_html';

        $eval = CustomSourceSearchEvaluator::evaluate(
            $rule,
            $rawHtml,
            $responseHeaders,
            $visibleText,
            $renderedHtml
        );

        AuditCustomSearchResult::create([
            'audit_id' => $audit->id,
            'audit_page_id' => $page->id,
            'url' => $page->url,
            'rule_key' => (string) $rule['id'],
            'rule_name' => (string) ($rule['rule_name'] ?? $rule['id']),
            'target_scope' => $scope,
            'match_type' => (string) ($rule['match_type'] ?? ''),
            'pattern_preview' => mb_substr((string) ($rule['pattern'] ?? ''), 0, 500),
            'expect_match' => (bool) ($rule['expect_match'] ?? true),
            'matched' => $eval['matched'],
            'match_count' => $eval['match_count'],
            'sample_match' => $eval['sample_match'],
            'severity' => (string) ($rule['severity'] ?? 'warning'),
            'error_message' => $eval['error_message'],
            'segment_key' => $page->segment_key,
        ]);
    }

    public function syncSegments(Audit $audit): void
    {
        if (!$this->shouldRun($audit)) {
            return;
        }
        foreach ($audit->pages()->get() as $page) {
            AuditCustomSearchResult::where('audit_page_id', $page->id)
                ->update(['segment_key' => $page->segment_key]);
        }
    }

    public function createIssues(Audit $audit): int
    {
        if (!$this->shouldRun($audit)) {
            return 0;
        }

        AuditIssue::where('audit_id', $audit->id)
            ->where('module_key', 'custom_source_search')
            ->delete();

        $results = AuditCustomSearchResult::where('audit_id', $audit->id)->get();
        $rulesEngine = new RulesEngine;
        $penalty = 0;

        foreach ($results as $row) {
            if ($row->error_message) {
                continue;
            }
            $expect = $row->expect_match;
            $violates = ($expect && ! $row->matched) || (! $expect && $row->matched);
            if (! $violates) {
                continue;
            }

            $sev = strtolower((string) $row->severity);
            if ($sev === 'info') {
                continue;
            }

            $impact = $sev === 'critical' ? AuditIssue::IMPACT_HIGH : AuditIssue::IMPACT_MEDIUM;
            $severity = $sev === 'critical' ? AuditIssue::SEVERITY_CRITICAL : AuditIssue::SEVERITY_WARNING;
            $p = $sev === 'critical' ? 4 : 2;
            $penalty += $p;

            $msg = $expect
                ? 'Expected pattern did not match for rule "'.$row->rule_name.'".'
                : 'Forbidden pattern matched for rule "'.$row->rule_name.'".';

            $rulesEngine->createCustomIssue($audit, [
                'url' => $row->url,
                'code' => 'CUSTOM_SOURCE_RULE_'.$row->rule_key,
                'category' => 'technical',
                'module_key' => 'custom_source_search',
                'title' => $row->rule_name,
                'message' => $msg,
                'description' => $msg,
                'impact' => $impact,
                'effort' => AuditIssue::EFFORT_MEDIUM,
                'severity' => $severity,
                'score_penalty' => $p,
                'recommendation' => 'Adjust page content, headers, or rendering so the rule outcome matches your policy.',
                'details_json' => [
                    'rule_key' => $row->rule_key,
                    'rule_name' => $row->rule_name,
                    'target_scope' => $row->target_scope,
                    'match_type' => $row->match_type,
                    'pattern_preview' => $row->pattern_preview,
                    'expect_match' => $row->expect_match,
                    'matched' => $row->matched,
                    'match_count' => $row->match_count,
                    'sample_match' => $row->sample_match,
                    'segment' => $row->segment_key,
                ],
            ]);
        }

        return $penalty;
    }
}
