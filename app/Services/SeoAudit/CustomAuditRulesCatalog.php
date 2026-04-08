<?php

namespace App\Services\SeoAudit;

use App\Models\Audit;

class CustomAuditRulesCatalog
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function mergedSearchRules(Audit $audit): array
    {
        $orgRules = is_array($audit->organization?->custom_source_search_rules)
            ? ($audit->organization->custom_source_search_rules['rules'] ?? [])
            : [];
        $ownRules = is_array($audit->custom_source_search_rules)
            ? ($audit->custom_source_search_rules['rules'] ?? [])
            : [];

        return self::normalizeRulesList(self::mergeRuleListsById($orgRules, $ownRules));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function mergedExtractionRules(Audit $audit): array
    {
        $orgRules = is_array($audit->organization?->custom_extraction_rules)
            ? ($audit->organization->custom_extraction_rules['rules'] ?? [])
            : [];
        $ownRules = is_array($audit->custom_extraction_rules)
            ? ($audit->custom_extraction_rules['rules'] ?? [])
            : [];

        return self::normalizeRulesList(self::mergeRuleListsById($orgRules, $ownRules));
    }

    public static function auditNeedsRenderedHtmlBody(Audit $audit): bool
    {
        foreach (self::mergedSearchRules($audit) as $r) {
            if (($r['target_scope'] ?? '') === 'rendered_html') {
                return true;
            }
        }
        foreach (self::mergedExtractionRules($audit) as $r) {
            if (($r['target_scope'] ?? '') === 'rendered_html') {
                return true;
            }
        }

        return false;
    }

    /**
     * Organization rules first; audit-level rules with the same `id` override.
     *
     * @param  list<mixed>  $orgRules
     * @param  list<mixed>  $auditRules
     * @return list<array<string, mixed>>
     */
    protected static function mergeRuleListsById(array $orgRules, array $auditRules): array
    {
        $merged = [];
        $indexById = [];
        foreach ($orgRules as $r) {
            if (!is_array($r) || empty($r['id'])) {
                continue;
            }
            $id = (string) $r['id'];
            $merged[] = $r;
            $indexById[$id] = count($merged) - 1;
        }
        foreach ($auditRules as $r) {
            if (!is_array($r) || empty($r['id'])) {
                continue;
            }
            $id = (string) $r['id'];
            if (isset($indexById[$id])) {
                $merged[$indexById[$id]] = $r;
            } else {
                $merged[] = $r;
                $indexById[$id] = count($merged) - 1;
            }
        }

        return $merged;
    }

    /**
     * @param  list<array<string, mixed>>  $rules
     * @return list<array<string, mixed>>
     */
    protected static function normalizeRulesList(array $rules): array
    {
        $out = [];
        foreach ($rules as $r) {
            if (!is_array($r)) {
                continue;
            }
            $id = isset($r['id']) ? (string) $r['id'] : '';
            if ($id === '') {
                continue;
            }
            $out[] = $r;
        }

        return $out;
    }
}
