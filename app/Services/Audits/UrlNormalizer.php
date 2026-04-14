<?php

namespace App\Services\Audits;

class UrlNormalizer
{
    /**
     * Normalize a URL for crawling
     */
    public static function normalize(string $url, string $baseHost): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }

        // Parse the URL
        $parsed = parse_url($url);
        
        if (!$parsed) {
            return null;
        }

        // If relative URL, make it absolute
        if (!isset($parsed['scheme'])) {
            $url = 'https://' . $baseHost . (strpos($url, '/') === 0 ? '' : '/') . $url;
            $parsed = parse_url($url);
        }

        // Ensure scheme is https
        $scheme = $parsed['scheme'] ?? 'https';
        if (!in_array($scheme, ['http', 'https'])) {
            return null;
        }

        // Extract host
        $host = $parsed['host'] ?? null;
        if (!$host) {
            return null;
        }

        // Normalize host casing only. Keep www/non-www as provided.
        $host = strtolower($host);

        // Only allow same host or its www/non-www alternate.
        $baseHostNormalized = strtolower($baseHost);
        $alternateHost = self::alternateHost($baseHostNormalized);

        if ($host !== $baseHostNormalized && $host !== $alternateHost) {
            return null;
        }

        // Build normalized URL
        $normalized = $scheme . '://' . $host;

        // Add path (normalize trailing slash)
        $path = $parsed['path'] ?? '/';
        if ($path !== '/' && substr($path, -1) === '/') {
            $path = rtrim($path, '/');
        }
        $normalized .= $path;

        // Add query string (if exists)
        if (isset($parsed['query'])) {
            $normalized .= '?' . $parsed['query'];
        }

        // Note: We strip fragments (#) as they don't affect the page

        return $normalized;
    }

    /**
     * Extract path from URL
     */
    public static function extractPath(string $url): string
    {
        $parsed = parse_url($url);
        $path = $parsed['path'] ?? '/';
        
        if (isset($parsed['query'])) {
            $path .= '?' . $parsed['query'];
        }

        return $path;
    }

    /**
     * Check if URL is internal to the host
     */
    public static function isInternal(string $url, string $baseHost): bool
    {
        $normalized = self::normalize($url, $baseHost);
        return $normalized !== null;
    }

    protected static function alternateHost(string $host): string
    {
        if (str_starts_with($host, 'www.')) {
            return substr($host, 4);
        }

        return 'www.' . $host;
    }
}


