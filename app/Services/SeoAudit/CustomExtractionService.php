<?php

namespace App\Services\SeoAudit;

use App\Models\Audit;
use App\Models\AuditCustomExtractionResult;
use App\Models\AuditPage;

class CustomExtractionService
{
    /** @var list<string> */
    protected const CRAWL_SCOPES = ['raw_html', 'response_headers', 'visible_text'];

    public function shouldRun(Audit $audit): bool
    {
        return !empty($audit->crawl_module_flags['custom_extraction_enabled']);
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

        $rules = CustomAuditRulesCatalog::mergedExtractionRules($audit);
        if ($rules === []) {
            return;
        }

        AuditCustomExtractionResult::where('audit_page_id', $page->id)
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

        $rules = CustomAuditRulesCatalog::mergedExtractionRules($audit);
        $renderRules = array_values(array_filter($rules, fn ($r) => ($r['target_scope'] ?? '') === 'rendered_html'));
        if ($renderRules === []) {
            return;
        }

        foreach ($audit->pages()->get() as $page) {
            AuditCustomExtractionResult::where('audit_page_id', $page->id)
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
        $eval = CustomExtractionEvaluator::extract(
            $rule,
            $rawHtml,
            $responseHeaders,
            $visibleText,
            $renderedHtml
        );

        $values = $eval['values'];
        $fingerprint = $this->fingerprintValues($values);

        AuditCustomExtractionResult::create([
            'audit_id' => $audit->id,
            'audit_page_id' => $page->id,
            'url' => $page->url,
            'rule_key' => (string) $rule['id'],
            'rule_name' => (string) ($rule['rule_name'] ?? $rule['id']),
            'target_scope' => (string) ($rule['target_scope'] ?? ''),
            'extraction_type' => (string) ($rule['extraction_type'] ?? ''),
            'extractor' => mb_substr((string) ($rule['extractor'] ?? ''), 0, 1024),
            'attribute' => isset($rule['attribute']) && is_string($rule['attribute']) ? substr($rule['attribute'], 0, 128) : null,
            'multiple' => !empty($rule['multiple']),
            'values' => $values,
            'missing' => $eval['missing'],
            'error_message' => $eval['error_message'],
            'segment_key' => $page->segment_key,
            'fingerprint' => $fingerprint,
        ]);
    }

    /**
     * @param  list<string>  $values
     */
    protected function fingerprintValues(array $values): ?string
    {
        if ($values === []) {
            return null;
        }
        $norm = array_values(array_unique(array_map(
            fn ($v) => mb_strtolower(trim((string) $v)),
            $values
        )));
        sort($norm);

        return hash('sha256', json_encode($norm));
    }

    public function syncSegments(Audit $audit): void
    {
        if (!$this->shouldRun($audit)) {
            return;
        }
        foreach ($audit->pages()->get() as $page) {
            AuditCustomExtractionResult::where('audit_page_id', $page->id)
                ->update(['segment_key' => $page->segment_key]);
        }
    }
}
