<?php

namespace App\Services\SeoAudit;

use Symfony\Component\CssSelector\CssSelectorConverter;

class CustomSourceSearchEvaluator
{
    /**
     * @return array{matched: bool, match_count: int, sample_match: ?string, error_message: ?string}
     */
    public static function evaluate(
        array $rule,
        string $rawHtml,
        array $responseHeaders,
        string $visibleText,
        ?string $renderedHtml
    ): array {
        $scope = $rule['target_scope'] ?? 'raw_html';
        if ($scope === 'rendered_html' && $renderedHtml === null) {
            return ['matched' => false, 'match_count' => 0, 'sample_match' => null, 'error_message' => 'Rendered HTML not available for this page (JS rendering may be disabled or failed).'];
        }
        $haystack = self::haystackForScope($scope, $rawHtml, $responseHeaders, $visibleText, $renderedHtml);
        if ($haystack === null) {
            return ['matched' => false, 'match_count' => 0, 'sample_match' => null, 'error_message' => 'Scope unavailable'];
        }

        $matchType = $rule['match_type'] ?? 'contains';
        $pattern = (string) ($rule['pattern'] ?? '');

        return match ($matchType) {
            'contains' => self::matchContains($haystack, $pattern),
            'regex' => self::matchRegex($haystack, $pattern),
            'css_selector_exists' => self::matchCssExists($haystack, $pattern),
            'xpath_exists' => self::matchXpathExists($haystack, $pattern),
            default => ['matched' => false, 'match_count' => 0, 'sample_match' => null, 'error_message' => 'Unknown match_type'],
        };
    }

    protected static function haystackForScope(
        string $scope,
        string $rawHtml,
        array $responseHeaders,
        string $visibleText,
        ?string $renderedHtml
    ): ?string {
        return match ($scope) {
            'raw_html' => $rawHtml,
            'rendered_html' => $renderedHtml,
            'response_headers' => self::flattenHeaders($responseHeaders),
            'visible_text' => $visibleText,
            default => null,
        };
    }

    /**
     * @param  array<string, string|list<string>>  $headers
     */
    protected static function flattenHeaders(array $headers): string
    {
        $lines = [];
        foreach ($headers as $k => $v) {
            $k = strtolower((string) $k);
            $val = is_array($v) ? implode(', ', $v) : (string) $v;
            $lines[] = $k.': '.$val;
        }

        return implode("\n", $lines);
    }

    /**
     * @return array{matched: bool, match_count: int, sample_match: ?string, error_message: ?string}
     */
    protected static function matchContains(string $haystack, string $needle): array
    {
        if ($needle === '') {
            return ['matched' => false, 'match_count' => 0, 'sample_match' => null, 'error_message' => 'Empty pattern'];
        }
        $count = substr_count(strtolower($haystack), strtolower($needle));
        $sample = null;
        if ($count > 0) {
            $pos = stripos($haystack, $needle);
            $sample = $pos !== false ? mb_substr($haystack, $pos, min(500, mb_strlen($needle) + 80)) : $needle;
        }

        return [
            'matched' => $count > 0,
            'match_count' => $count,
            'sample_match' => $sample,
            'error_message' => null,
        ];
    }

    /**
     * @return array{matched: bool, match_count: int, sample_match: ?string, error_message: ?string}
     */
    protected static function matchRegex(string $haystack, string $pattern): array
    {
        if (@preg_match_all($pattern, $haystack, $m) === false) {
            return ['matched' => false, 'match_count' => 0, 'sample_match' => null, 'error_message' => 'Invalid regex during evaluation'];
        }
        $count = count($m[0] ?? []);
        $sample = $count > 0 ? mb_substr((string) ($m[0][0] ?? ''), 0, 500) : null;

        return ['matched' => $count > 0, 'match_count' => $count, 'sample_match' => $sample, 'error_message' => null];
    }

    /**
     * @return array{matched: bool, match_count: int, sample_match: ?string, error_message: ?string}
     */
    protected static function matchCssExists(string $html, string $selector): array
    {
        if ($selector === '' || $html === '') {
            return ['matched' => false, 'match_count' => 0, 'sample_match' => null, 'error_message' => null];
        }
        try {
            $converter = new CssSelectorConverter;
            $xpathExpr = $converter->toXPath($selector);
        } catch (\Throwable) {
            return ['matched' => false, 'match_count' => 0, 'sample_match' => null, 'error_message' => 'Invalid CSS selector'];
        }

        return self::matchXpathExists($html, $xpathExpr);
    }

    /**
     * @return array{matched: bool, match_count: int, sample_match: ?string, error_message: ?string}
     */
    protected static function matchXpathExists(string $html, string $xpath): array
    {
        if ($xpath === '' || $html === '') {
            return ['matched' => false, 'match_count' => 0, 'sample_match' => null, 'error_message' => null];
        }

        $dom = new \DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();
        $xp = new \DOMXPath($dom);
        try {
            $nodes = $xp->query($xpath);
        } catch (\Throwable $e) {
            return ['matched' => false, 'match_count' => 0, 'sample_match' => null, 'error_message' => 'Invalid XPath: '.$e->getMessage()];
        }
        if ($nodes === false) {
            return ['matched' => false, 'match_count' => 0, 'sample_match' => null, 'error_message' => 'XPath query failed'];
        }
        $count = $nodes->length;
        $sample = null;
        if ($count > 0 && $nodes->item(0) instanceof \DOMNode) {
            $sample = trim($nodes->item(0)->textContent ?? '');
            $sample = mb_substr($sample, 0, 500) ?: $selector;
        }

        return ['matched' => $count > 0, 'match_count' => $count, 'sample_match' => $sample, 'error_message' => null];
    }
}
