<?php

namespace App\Services\SeoAudit;

class PageParser
{
    public static function parse(string $html, string $finalUrl, string $normalizedUrl, array $headers = []): array
    {
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
        $dom->loadHTML($html, LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();
        $xpath = new \DOMXPath($dom);

        $title = self::getNodeText($xpath, '//title');
        $metaDescription = self::getMetaContent($xpath, 'description');
        $canonicalUrl = self::getLinkHref($xpath, 'canonical');
        $robotsMeta = self::getMetaContent($xpath, 'robots');

        $h1Count = $xpath->query('//h1')->length;
        $h2Count = $xpath->query('//h2')->length;
        $h3Count = $xpath->query('//h3')->length;
        $h4Count = $xpath->query('//h4')->length;
        $h5Count = $xpath->query('//h5')->length;
        $h6Count = $xpath->query('//h6')->length;
        $h1Text = self::getNodeText($xpath, '//h1');

        $lang = null;
        $htmlNode = $xpath->query('//html')->item(0);
        if ($htmlNode) {
            $lang = trim($htmlNode->getAttribute('lang') ?? '') ?: null;
        }

        $hreflangPresent = $xpath->query('//link[@rel="alternate" and @hreflang]')->length > 0;
        $viewportPresent = $xpath->query('//meta[@name="viewport"]')->length > 0;
        $faviconPresent = $xpath->query('//link[contains(@rel,"icon")]')->length > 0;
        $iframeCount = $xpath->query('//iframe')->length;
        $flashUsed = $xpath->query('//object[contains(@type,"flash")]')->length > 0
            || $xpath->query('//embed[contains(@type,"flash")]')->length > 0
            || $xpath->query('//embed[contains(@src,".swf")]')->length > 0;

        $charset = self::detectCharset($xpath, $headers);
        $analyticsTool = self::detectAnalyticsTool($html);
        $socialLinks = self::detectSocialLinks($xpath);

        $bodyText = self::getBodyText($xpath);
        $wordCount = self::countWords($bodyText);
        $contentExcerpt = mb_substr($bodyText, 0, 2000) ?: null;

        $images = $xpath->query('//img');
        $imagesTotal = $images->length;
        $imagesMissingAlt = 0;
        foreach ($images as $img) {
            $alt = $img->getAttribute('alt');
            if (empty($alt) || trim($alt) === '') {
                $imagesMissingAlt++;
            }
        }

        $baseHost = parse_url($normalizedUrl, PHP_URL_HOST);
        $internalLinksCount = 0;
        $externalLinksCount = 0;
        $links = $xpath->query('//a[@href]');
        foreach ($links as $link) {
            $href = $link->getAttribute('href');
            if (empty($href) || str_starts_with($href, '#')) {
                continue;
            }
            $parsedHref = parse_url($href);
            if (!$parsedHref) {
                continue;
            }
            $hrefHost = $parsedHref['host'] ?? null;
            if (!$hrefHost) {
                $internalLinksCount++;
                continue;
            }
            $hrefHost = strtolower(preg_replace('/^www\./', '', $hrefHost));
            $baseHostNormalized = strtolower(preg_replace('/^www\./', '', $baseHost ?? ''));
            if ($hrefHost === $baseHostNormalized) {
                $internalLinksCount++;
            } else {
                $externalLinksCount++;
            }
        }

        $ogPresent = $xpath->query('//meta[starts-with(@property,"og:")]')->length > 0;
        $twitterCardsPresent = $xpath->query('//meta[@name="twitter:card"]')->length > 0;

        $schemaTypes = self::extractSchemaTypes($xpath);

        return [
            'title' => $title,
            'title_len' => mb_strlen($title),
            'meta_description' => $metaDescription,
            'meta_len' => mb_strlen($metaDescription),
            'canonical_url' => $canonicalUrl,
            'robots_meta' => $robotsMeta,
            'h1_count' => $h1Count,
            'h2_count' => $h2Count,
            'h3_count' => $h3Count,
            'h4_count' => $h4Count,
            'h5_count' => $h5Count,
            'h6_count' => $h6Count,
            'h1_text' => $h1Text,
            'lang' => $lang,
            'hreflang_present' => $hreflangPresent,
            'viewport_present' => $viewportPresent,
            'favicon_present' => $faviconPresent,
            'analytics_tool' => $analyticsTool,
            'iframes_count' => $iframeCount,
            'flash_used' => $flashUsed,
            'social_links' => $socialLinks,
            'x_robots_tag' => $headers['x_robots_tag'] ?? null,
            'server_header' => $headers['server'] ?? null,
            'x_powered_by' => $headers['x_powered_by'] ?? null,
            'content_type' => $headers['content_type'] ?? null,
            'charset' => $charset,
            'content_excerpt' => $contentExcerpt,
            'word_count' => $wordCount,
            'images_total' => $imagesTotal,
            'images_missing_alt' => $imagesMissingAlt,
            'internal_links_count' => $internalLinksCount,
            'external_links_count' => $externalLinksCount,
            'og_present' => $ogPresent,
            'twitter_cards_present' => $twitterCardsPresent,
            'schema_types' => array_values($schemaTypes),
        ];
    }

    protected static function getNodeText(\DOMXPath $xpath, string $query): string
    {
        $node = $xpath->query($query)->item(0);
        if (!$node) {
            return '';
        }
        return trim($node->textContent ?? '');
    }

    protected static function getMetaContent(\DOMXPath $xpath, string $name): string
    {
        $node = $xpath->query('//meta[@name="' . $name . '"]')->item(0);
        if (!$node) {
            return '';
        }
        return trim($node->getAttribute('content') ?? '');
    }

    protected static function getLinkHref(\DOMXPath $xpath, string $rel): ?string
    {
        $node = $xpath->query('//link[@rel="' . $rel . '"]')->item(0);
        if (!$node) {
            return null;
        }
        return trim($node->getAttribute('href') ?? '') ?: null;
    }

    protected static function getBodyText(\DOMXPath $xpath): string
    {
        $bodyNode = $xpath->query('//body')->item(0);
        if (!$bodyNode) {
            return '';
        }
        $bodyText = $bodyNode->textContent ?? '';
        $bodyText = preg_replace('/\s+/', ' ', trim($bodyText));
        return $bodyText ?? '';
    }

    protected static function countWords(string $text): int
    {
        if ($text === '') {
            return 0;
        }
        $words = preg_split('/\s+/', $text);
        $words = array_filter($words, fn($w) => $w !== '');
        return count($words);
    }

    protected static function detectCharset(\DOMXPath $xpath, array $headers): ?string
    {
        $metaCharset = $xpath->query('//meta[@charset]')->item(0);
        if ($metaCharset) {
            $charset = trim($metaCharset->getAttribute('charset') ?? '');
            if ($charset) {
                return $charset;
            }
        }

        $contentType = $headers['content_type'] ?? '';
        if ($contentType && preg_match('/charset=([^;]+)/i', $contentType, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    protected static function detectAnalyticsTool(string $html): ?string
    {
        $lower = strtolower($html);
        if (str_contains($lower, 'googletagmanager.com/gtm.js') || preg_match('/gtm-[a-z0-9]+/i', $html)) {
            return 'Google Tag Manager';
        }
        if (str_contains($lower, 'gtag/js?id=') || str_contains($lower, 'google-analytics.com/analytics.js')) {
            return 'Google Analytics';
        }
        if (str_contains($lower, 'connect.facebook.net') || str_contains($lower, 'fbq(')) {
            return 'Facebook Pixel';
        }
        return null;
    }

    protected static function detectSocialLinks(\DOMXPath $xpath): array
    {
        $links = [
            'facebook' => null,
            'x' => null,
            'instagram' => null,
            'linkedin' => null,
            'youtube' => null,
        ];

        $anchors = $xpath->query('//a[@href]');
        foreach ($anchors as $anchor) {
            $href = trim($anchor->getAttribute('href') ?? '');
            if (!$href) {
                continue;
            }

            $lower = strtolower($href);
            if (!$links['facebook'] && str_contains($lower, 'facebook.com')) {
                $links['facebook'] = $href;
            } elseif (!$links['x'] && (str_contains($lower, 'x.com') || str_contains($lower, 'twitter.com'))) {
                $links['x'] = $href;
            } elseif (!$links['instagram'] && str_contains($lower, 'instagram.com')) {
                $links['instagram'] = $href;
            } elseif (!$links['linkedin'] && str_contains($lower, 'linkedin.com')) {
                $links['linkedin'] = $href;
            } elseif (!$links['youtube'] && (str_contains($lower, 'youtube.com') || str_contains($lower, 'youtu.be'))) {
                $links['youtube'] = $href;
            }
        }

        return $links;
    }

    protected static function extractSchemaTypes(\DOMXPath $xpath): array
    {
        $schemaTypes = [];
        $schemaScripts = $xpath->query('//script[@type="application/ld+json"]');
        foreach ($schemaScripts as $script) {
            $json = $script->textContent ?? '';
            $data = json_decode($json, true);
            if ($data && isset($data['@type'])) {
                $types = is_array($data['@type']) ? $data['@type'] : [$data['@type']];
                $schemaTypes = array_merge($schemaTypes, $types);
            } elseif ($data && isset($data['@graph']) && is_array($data['@graph'])) {
                foreach ($data['@graph'] as $item) {
                    if (isset($item['@type'])) {
                        $types = is_array($item['@type']) ? $item['@type'] : [$item['@type']];
                        $schemaTypes = array_merge($schemaTypes, $types);
                    }
                }
            }
        }

        return array_unique($schemaTypes);
    }
}
