<?php

namespace App\Services\SeoAudit;

use App\Models\Audit;
use App\Models\AuditIssue;

class SpellingGrammarService
{
    public function shouldRun(Audit $audit): bool
    {
        return !empty($audit->crawl_module_flags['spelling_grammar_enabled']);
    }

    public function run(Audit $audit): int
    {
        if (!$this->shouldRun($audit)) {
            return 0;
        }

        $audit->issues()->where('module_key', 'spelling_grammar')->delete();

        $allowlist = $this->resolveAllowlist($audit);
        $dictionary = new SpellingDictionary($allowlist);
        $analyzer = new SpellingGrammarAnalyzer($dictionary, $allowlist);
        $rules = new RulesEngine();

        $maxPerPage = (int) config('seo_audit.spelling.max_issues_per_page', 28);
        $penaltyTotal = 0;

        foreach ($audit->pages()->get() as $page) {
            $code = (int) ($page->status_code ?? 0);
            if ($code < 200 || $code >= 400) {
                continue;
            }

            $text = (string) ($page->visible_main_text ?? '');
            if ($text === '') {
                $text = (string) ($page->content_excerpt ?? '');
            }
            if (trim($text) === '') {
                continue;
            }

            $maxChars = (int) config('seo_audit.spelling.max_chars_analyzed', 96000);
            if (mb_strlen($text) > $maxChars) {
                $text = mb_substr($text, 0, $maxChars);
            }

            $findings = $analyzer->analyze($text);
            $slice = array_slice($findings, 0, $maxPerPage);

            foreach ($slice as $row) {
                $confidence = (int) ($row['confidence'] ?? 0);
                $high = (int) config('seo_audit.spelling.high_confidence', 78);
                $severity = $confidence >= $high ? AuditIssue::SEVERITY_WARNING : AuditIssue::SEVERITY_INFO;
                $impact = $confidence >= $high ? AuditIssue::IMPACT_MEDIUM : AuditIssue::IMPACT_LOW;
                $penalty = $severity === AuditIssue::SEVERITY_WARNING ? 2 : 1;
                $penaltyTotal += $penalty;

                $kind = (string) ($row['kind'] ?? 'spelling');
                $codeStr = 'SPELL_GRAMMAR_'.strtoupper($kind);

                $segment = $page->segment_key ?? null;
                $details = [
                    'issue_kind' => $kind,
                    'issue_text' => (string) ($row['text'] ?? ''),
                    'suggested_correction' => $row['suggestion'] ?? null,
                    'confidence' => $confidence,
                    'context_snippet' => (string) ($row['context'] ?? ''),
                    'offset' => $row['offset'] ?? null,
                    'filter_tags' => $row['filter_tags'] ?? [],
                ];
                if ($segment) {
                    $details['segment'] = $segment;
                }

                $rules->createCustomIssue($audit, [
                    'url' => $page->url,
                    'code' => $codeStr,
                    'category' => 'onpage',
                    'module_key' => 'spelling_grammar',
                    'issue_type' => $kind,
                    'title' => $this->titleForKind($kind, $row),
                    'description' => $details['context_snippet'],
                    'message' => $this->titleForKind($kind, $row),
                    'impact' => $impact,
                    'effort' => AuditIssue::EFFORT_EASY,
                    'severity' => $severity,
                    'score_penalty' => $penalty,
                    'recommendation' => $this->recommendationFor($row),
                    'details_json' => $details,
                ]);
            }
        }

        return $penaltyTotal;
    }

    /**
     * @return list<string>
     */
    protected function resolveAllowlist(Audit $audit): array
    {
        $terms = [];
        foreach ((array) config('seo_audit.spelling.brand_safe_terms', []) as $t) {
            $terms[] = strtolower(trim((string) $t));
        }
        foreach ((array) ($audit->organization?->spelling_allowlist ?? []) as $t) {
            $terms[] = strtolower(trim((string) $t));
        }
        foreach ((array) ($audit->spelling_allowlist ?? []) as $t) {
            $terms[] = strtolower(trim((string) $t));
        }

        $terms = array_values(array_filter(array_unique($terms)));

        return $terms;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function titleForKind(string $kind, array $row): string
    {
        $text = (string) ($row['text'] ?? '');
        return match ($kind) {
            'spelling' => 'Possible spelling issue: '.$text,
            'grammar' => 'Possible grammar issue',
            'repeated_word' => 'Repeated word: '.$text,
            'punctuation' => 'Punctuation spacing',
            default => 'Content quality',
        };
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function recommendationFor(array $row): string
    {
        $s = $row['suggestion'] ?? null;
        $kind = (string) ($row['kind'] ?? '');
        if ($kind === 'spelling' && is_string($s) && $s !== '') {
            return 'Replace with the suggested term or add it to your project dictionary if it is intentional.';
        }
        if ($kind === 'repeated_word') {
            return 'Remove the duplicated word or rephrase the sentence.';
        }
        if ($kind === 'punctuation') {
            return 'Adjust spacing around punctuation for readability.';
        }
        if ($kind === 'grammar') {
            return 'Review the phrase for grammar and fix if it does not match the intended meaning.';
        }

        return 'Review visible copy for clarity and correctness.';
    }
}
