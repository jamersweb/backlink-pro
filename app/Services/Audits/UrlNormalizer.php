<?php

namespace App\Services\Audits;

class UrlNormalizer
{
    /**
     * Normalize a URL for crawling
     */
    public static function normalize(string $url, string $baseHost): ?string
    {
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

        // Normalize host (lowercase, remove www.)
        $host = strtolower($host);
        if (strpos($host, 'www.') === 0) {
            $host = substr($host, 4);
        }

        // Only allow same host
        $baseHostNormalized = strtolower($baseHost);
        if (strpos($baseHostNormalized, 'www.') === 0) {
            $baseHostNormalized = substr($baseHostNormalized, 4);
        }

        if ($host !== $baseHostNormalized) {
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
}


