<?php

namespace App\Services\SeoAudit;

/**
 * Extracts user-visible main content text, excluding obvious boilerplate and non-content nodes.
 */
class VisibleTextExtractor
{
    public static function extractFromHtml(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $wrapped = '<?xml encoding="UTF-8">' . $html;
        $dom->loadHTML($wrapped, LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);
        $body = $xpath->query('//body')->item(0);
        if (!$body) {
            return '';
        }

        self::removeNodes($xpath, '//script|//style|//noscript|//template|//svg|//iframe');
        self::removeNodes($xpath, '//nav|//footer|//*[@role="navigation"]');
        self::removeNodes($xpath, '//aside[contains(concat(" ", normalize-space(@class), " "), " sidebar ") or contains(concat(" ", normalize-space(@class), " "), " navigation ")]');
        self::removeNodes($xpath, '//*[contains(concat(" ", normalize-space(@class), " "), " breadcrumb ")]');
        self::removeNodes($xpath, '//pre|//code|//kbd|//samp|//textarea');

        $text = $body->textContent ?? '';
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', trim($text)) ?? '';

        return is_string($text) ? $text : '';
    }

    protected static function removeNodes(\DOMXPath $xpath, string $expression): void
    {
        $nodes = $xpath->query($expression);
        if (!$nodes) {
            return;
        }
        $toRemove = [];
        foreach ($nodes as $node) {
            $toRemove[] = $node;
        }
        foreach ($toRemove as $node) {
            $parent = $node->parentNode;
            if ($parent) {
                $parent->removeChild($node);
            }
        }
    }
}
