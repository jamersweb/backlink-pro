<?php

namespace App\Services\Audits;

use DOMDocument;
use DOMXPath;

class HtmlExtractor
{
    /**
     * Extract SEO data from HTML content
     */
    public static function extract(string $html): array
    {
        $data = [
            'title' => null,
            'meta_description' => null,
            'canonical' => null,
            'robots_meta' => null,
            'h1_count' => 0,
            'word_count' => 0,
        ];

        try {
            libxml_use_internal_errors(true);
            $dom = new DOMDocument();
            @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
            $xpath = new DOMXPath($dom);

            // Extract title
            $titleNodes = $xpath->query('//title');
            if ($titleNodes->length > 0) {
                $data['title'] = trim($titleNodes->item(0)->textContent);
            }

            // Extract meta description
            $metaDescNodes = $xpath->query('//meta[@name="description"]/@content');
            if ($metaDescNodes->length > 0) {
                $data['meta_description'] = trim($metaDescNodes->item(0)->value);
            }

            // Extract canonical
            $canonicalNodes = $xpath->query('//link[@rel="canonical"]/@href');
            if ($canonicalNodes->length > 0) {
                $data['canonical'] = trim($canonicalNodes->item(0)->value);
            }

            // Extract robots meta
            $robotsNodes = $xpath->query('//meta[@name="robots"]/@content');
            if ($robotsNodes->length > 0) {
                $data['robots_meta'] = trim($robotsNodes->item(0)->value);
            }

            // Count H1 tags
            $h1Nodes = $xpath->query('//h1');
            $data['h1_count'] = $h1Nodes->length;

            // Extract body text and count words
            $bodyNodes = $xpath->query('//body');
            if ($bodyNodes->length > 0) {
                $bodyText = $bodyNodes->item(0)->textContent;
                // Remove extra whitespace and count words
                $bodyText = preg_replace('/\s+/', ' ', trim($bodyText));
                $words = array_filter(explode(' ', $bodyText));
                $data['word_count'] = count($words);
            }
        } catch (\Exception $e) {
            // If parsing fails, return defaults
        }

        return $data;
    }

    /**
     * Extract internal links from HTML
     */
    public static function extractInternalLinks(string $html, string $baseHost): array
    {
        $links = [];

        try {
            libxml_use_internal_errors(true);
            $dom = new DOMDocument();
            @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
            $xpath = new DOMXPath($dom);

            $linkNodes = $xpath->query('//a[@href]');
            foreach ($linkNodes as $linkNode) {
                $href = $linkNode->getAttribute('href');
                $normalized = UrlNormalizer::normalize($href, $baseHost);
                if ($normalized) {
                    $links[] = $normalized;
                }
            }
        } catch (\Exception $e) {
            // If parsing fails, return empty array
        }

        return array_unique($links);
    }
}


