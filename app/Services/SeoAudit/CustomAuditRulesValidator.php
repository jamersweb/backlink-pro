<?php

namespace App\Services\SeoAudit;

class CustomAuditRulesValidator
{
    protected static function isValidPcre(string $pattern): bool
    {
        set_error_handler(static function () {
            return true;
        });
        try {
            $probe = @preg_match($pattern, '');
            if ($probe === false) {
                return false;
            }

            return preg_last_error() === PREG_NO_ERROR;
        } finally {
            restore_error_handler();
        }
    }

    /** @var list<string> */
    protected static array $searchScopes = ['raw_html', 'rendered_html', 'response_headers', 'visible_text'];

    /** @var list<string> */
    protected static array $matchTypes = ['contains', 'regex', 'css_selector_exists', 'xpath_exists'];

    /** @var list<string> */
    protected static array $extractionScopes = ['raw_html', 'rendered_html', 'response_headers', 'visible_text'];

    /** @var list<string> */
    protected static array $extractionTypes = ['css', 'xpath', 'regex', 'meta_tag', 'header', 'json_ld'];

    /**
     * @return array{valid: bool, errors: list<string>, rules: list<array<string, mixed>>}
     */
    public static function validateSearchPayload(?array $payload): array
    {
        if ($payload === null) {
            return ['valid' => true, 'errors' => [], 'rules' => []];
        }
        $rules = $payload['rules'] ?? null;
        if (!is_array($rules)) {
            return ['valid' => false, 'errors' => ['rules must be an array'], 'rules' => []];
        }

        $errors = [];
        $normalized = [];
        foreach ($rules as $i => $rule) {
            if (!is_array($rule)) {
                $errors[] = "Rule index {$i}: must be object";
                continue;
            }
            $id = trim((string) ($rule['id'] ?? ''));
            if ($id === '' || strlen($id) > 64) {
                $errors[] = "Rule index {$i}: id is required (max 64 chars)";
                continue;
            }
            $name = trim((string) ($rule['rule_name'] ?? $id));
            $scope = $rule['target_scope'] ?? '';
            if (!in_array($scope, self::$searchScopes, true)) {
                $errors[] = "Rule {$id}: invalid target_scope";
                continue;
            }
            $matchType = $rule['match_type'] ?? '';
            if (!in_array($matchType, self::$matchTypes, true)) {
                $errors[] = "Rule {$id}: invalid match_type";
                continue;
            }
            $pattern = (string) ($rule['pattern'] ?? '');
            if ($pattern === '' && $matchType !== 'css_selector_exists' && $matchType !== 'xpath_exists') {
                $errors[] = "Rule {$id}: pattern required";
                continue;
            }
            if ($matchType === 'regex' && ! self::isValidPcre($pattern)) {
                $errors[] = "Rule {$id}: invalid regex pattern";
                continue;
            }
            $severity = strtolower((string) ($rule['severity'] ?? 'warning'));
            if (!in_array($severity, ['critical', 'warning', 'info'], true)) {
                $errors[] = "Rule {$id}: invalid severity";
                continue;
            }
            $expect = $rule['expect_match'] ?? true;
            $normalized[] = [
                'id' => $id,
                'rule_name' => $name !== '' ? $name : $id,
                'target_scope' => $scope,
                'match_type' => $matchType,
                'pattern' => $pattern,
                'severity' => $severity,
                'expect_match' => filter_var($expect, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? true,
            ];
        }

        return [
            'valid' => $errors === [],
            'errors' => $errors,
            'rules' => $normalized,
        ];
    }

    /**
     * @return array{valid: bool, errors: list<string>, rules: list<array<string, mixed>>}
     */
    public static function validateExtractionPayload(?array $payload): array
    {
        if ($payload === null) {
            return ['valid' => true, 'errors' => [], 'rules' => []];
        }
        $rules = $payload['rules'] ?? null;
        if (!is_array($rules)) {
            return ['valid' => false, 'errors' => ['rules must be an array'], 'rules' => []];
        }

        $errors = [];
        $normalized = [];
        foreach ($rules as $i => $rule) {
            if (!is_array($rule)) {
                $errors[] = "Extraction index {$i}: must be object";
                continue;
            }
            $id = trim((string) ($rule['id'] ?? ''));
            if ($id === '' || strlen($id) > 64) {
                $errors[] = "Extraction index {$i}: id is required (max 64 chars)";
                continue;
            }
            $name = trim((string) ($rule['rule_name'] ?? $id));
            $scope = $rule['target_scope'] ?? '';
            if (!in_array($scope, self::$extractionScopes, true)) {
                $errors[] = "Extraction {$id}: invalid target_scope";
                continue;
            }
            $extType = $rule['extraction_type'] ?? '';
            if (!in_array($extType, self::$extractionTypes, true)) {
                $errors[] = "Extraction {$id}: invalid extraction_type";
                continue;
            }
            $extractor = (string) ($rule['extractor'] ?? '');
            if ($extractor === '') {
                $errors[] = "Extraction {$id}: extractor required";
                continue;
            }
            if ($extType === 'regex' && ! self::isValidPcre($extractor)) {
                $errors[] = "Extraction {$id}: invalid regex extractor";
                continue;
            }
            $attr = $rule['attribute'] ?? null;
            $attr = is_string($attr) && $attr !== '' ? substr($attr, 0, 128) : null;
            $multiple = filter_var($rule['multiple'] ?? false, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? false;

            $normalized[] = [
                'id' => $id,
                'rule_name' => $name !== '' ? $name : $id,
                'target_scope' => $scope,
                'extraction_type' => $extType,
                'extractor' => $extractor,
                'attribute' => $attr,
                'multiple' => $multiple,
            ];
        }

        return [
            'valid' => $errors === [],
            'errors' => $errors,
            'rules' => $normalized,
        ];
    }
}
