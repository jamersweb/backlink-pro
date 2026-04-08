<?php

namespace App\Services\SeoAudit;

use Symfony\Component\CssSelector\CssSelectorConverter;

class CustomExtractionEvaluator
{
    /**
     * @return array{values: list<string>, missing: bool, error_message: ?string}
     */
    public static function extract(
        array $rule,
        string $rawHtml,
        array $responseHeaders,
        string $visibleText,
        ?string $renderedHtml
    ): array {
        $scope = $rule['target_scope'] ?? 'raw_html';
        if ($scope === 'rendered_html' && $renderedHtml === null) {
            return ['values' => [], 'missing' => true, 'error_message' => 'Rendered HTML not available'];
        }

        $blob = match ($scope) {
            'raw_html' => $rawHtml,
            'rendered_html' => (string) $renderedHtml,
            'response_headers' => self::flattenHeaders($responseHeaders),
            'visible_text' => $visibleText,
            default => '',
        };

        $type = $rule['extraction_type'] ?? 'regex';
        $extractor = (string) ($rule['extractor'] ?? '');
        $attribute = $rule['attribute'] ?? null;
        $attribute = is_string($attribute) && $attribute !== '' ? $attribute : null;
        $multiple = !empty($rule['multiple']);

        return match ($type) {
            'css' => self::extractCss($blob, $extractor, $attribute, $multiple),
            'xpath' => self::extractXpath($blob, $extractor, $attribute, $multiple),
            'regex' => self::extractRegex($blob, $extractor, $multiple),
            'meta_tag' => self::extractMeta($blob, $extractor, $attribute, $multiple),
            'header' => self::extractHeader($responseHeaders, $extractor, $multiple),
            'json_ld' => self::extractJsonLdKey($blob, $extractor, $multiple),
            default => ['values' => [], 'missing' => true, 'error_message' => 'Unknown extraction_type'],
        };
    }

    /**
     * @param  array<string, string|list<string>>  $headers
     * @return list<string>
     */
    protected static function extractHeader(array $headers, string $name, bool $multiple): array
    {
        $key = strtolower($name);
        $vals = [];
        foreach ($headers as $k => $v) {
            if (strtolower((string) $k) === $key) {
                $parts = is_array($v) ? $v : [$v];
                foreach ($parts as $p) {
                    $vals[] = trim((string) $p);
                }
            }
        }
        $vals = array_values(array_filter($vals, fn ($s) => $s !== ''));
        if (!$multiple && count($vals) > 1) {
            $vals = [reset($vals)];
        }

        return ['values' => $vals, 'missing' => $vals === [], 'error_message' => null];
    }

    /**
     * Meta extractor: "name=description" or "property=og:title"
     *
     * @return array{values: list<string>, missing: bool, error_message: ?string}
     */
    protected static function extractMeta(string $html, string $spec, ?string $attributeOverride, bool $multiple): array
    {
        $mode = 'name';
        $needle = $spec;
        if (str_contains($spec, '=')) {
            [$mode, $needle] = explode('=', $spec, 2);
            $mode = strtolower(trim($mode));
            $needle = trim($needle);
        }
        if ($needle === '') {
            return ['values' => [], 'missing' => true, 'error_message' => 'Invalid meta extractor'];
        }

        $dom = new \DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();
        $attr = $attributeOverride ?? 'content';
        $needleLc = strtolower($needle);

        $out = [];
        foreach ($dom->getElementsByTagName('meta') as $node) {
            if (!$node instanceof \DOMElement) {
                continue;
            }
            $match = match ($mode) {
                'property' => strtolower($node->getAttribute('property')) === $needleLc,
                'name' => strtolower($node->getAttribute('name')) === $needleLc,
                default => strtolower($node->getAttribute($mode)) === $needleLc,
            };
            if (!$match) {
                continue;
            }
            $val = $node->getAttribute($attr);
            if ($val === '' && $attr === 'content') {
                $val = trim($node->getAttribute('href') ?? '');
            }
            if ($val !== '') {
                $out[] = $val;
            }
            if (!$multiple) {
                break;
            }
        }

        return ['values' => $out, 'missing' => $out === [], 'error_message' => null];
    }

    /**
     * @return array{values: list<string>, missing: bool, error_message: ?string}
     */
    protected static function extractJsonLdKey(string $html, string $key, bool $multiple): array
    {
        $dom = new \DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();
        $scripts = $dom->getElementsByTagName('script');
        $out = [];
        foreach ($scripts as $script) {
            if (!$script instanceof \DOMElement) {
                continue;
            }
            $t = strtolower($script->getAttribute('type') ?? '');
            if ($t !== 'application/ld+json') {
                continue;
            }
            $json = trim($script->textContent ?? '');
            if ($json === '') {
                continue;
            }
            $data = json_decode($json, true);
            if (!is_array($data)) {
                continue;
            }
            $flat = self::jsonLdFindKey($data, $key, 0);
            foreach ($flat as $v) {
                $out[] = $v;
                if (!$multiple) {
                    break 2;
                }
            }
        }

        return ['values' => $out, 'missing' => $out === [], 'error_message' => null];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    protected static function jsonLdFindKey(array $data, string $key, int $depth): array
    {
        if ($depth > 14) {
            return [];
        }
        $out = [];
        if (array_key_exists($key, $data)) {
            $v = $data[$key];
            if (is_string($v) || is_numeric($v)) {
                $out[] = (string) $v;
            } elseif (is_array($v) && isset($v['@type'])) {
                $out[] = json_encode($v, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
        }
        foreach ($data as $v) {
            if (is_array($v)) {
                $out = array_merge($out, self::jsonLdFindKey($v, $key, $depth + 1));
            }
        }

        return $out;
    }

    /**
     * @return array{values: list<string>, missing: bool, error_message: ?string}
     */
    protected static function extractRegex(string $blob, string $pattern, bool $multiple): array
    {
        if (@preg_match_all($pattern, $blob, $m) === false) {
            return ['values' => [], 'missing' => true, 'error_message' => 'Invalid regex'];
        }
        $cap = $m[1] ?? $m[0];
        $vals = [];
        foreach ($cap as $hit) {
            $hit = trim((string) $hit);
            if ($hit !== '') {
                $vals[] = $hit;
            }
            if (!$multiple && $vals !== []) {
                break;
            }
        }

        return ['values' => $vals, 'missing' => $vals === [], 'error_message' => null];
    }

    /**
     * @return array{values: list<string>, missing: bool, error_message: ?string}
     */
    protected static function extractCss(string $html, string $selector, ?string $attribute, bool $multiple): array
    {
        if ($html === '') {
            return ['values' => [], 'missing' => true, 'error_message' => null];
        }
        try {
            $converter = new CssSelectorConverter;
            $xpathExpr = $converter->toXPath($selector);
        } catch (\Throwable $e) {
            return ['values' => [], 'missing' => true, 'error_message' => 'Invalid CSS: '.$e->getMessage()];
        }

        return self::extractXpath($html, $xpathExpr, $attribute, $multiple);
    }

    /**
     * @return array{values: list<string>, missing: bool, error_message: ?string}
     */
    protected static function extractXpath(string $html, string $xpath, ?string $attribute, bool $multiple): array
    {
        $dom = new \DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();
        $xp = new \DOMXPath($dom);
        try {
            $nodes = $xp->query($xpath);
        } catch (\Throwable $e) {
            return ['values' => [], 'missing' => true, 'error_message' => 'XPath error: '.$e->getMessage()];
        }
        if ($nodes === false || $nodes->length === 0) {
            return ['values' => [], 'missing' => true, 'error_message' => null];
        }
        $vals = [];
        foreach ($nodes as $node) {
            if (!$node instanceof \DOMNode) {
                continue;
            }
            $val = '';
            if ($attribute !== null && $node instanceof \DOMElement) {
                $val = $node->getAttribute($attribute);
            }
            if ($val === '' && $node instanceof \DOMElement) {
                $val = trim($node->textContent ?? '');
            }
            if ($val !== '') {
                $vals[] = mb_substr($val, 0, 4000);
            }
            if (!$multiple && $vals !== []) {
                break;
            }
        }

        return ['values' => $vals, 'missing' => $vals === [], 'error_message' => null];
    }

    /**
     * @param  array<string, string|list<string>>  $headers
     */
    protected static function flattenHeaders(array $headers): string
    {
        $lines = [];
        foreach ($headers as $k => $v) {
            $val = is_array($v) ? implode(', ', $v) : (string) $v;
            $lines[] = strtolower((string) $k).': '.$val;
        }

        return implode("\n", $lines);
    }
}
