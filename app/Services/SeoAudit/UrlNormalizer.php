<?php

namespace App\Services\SeoAudit;

/**
 * URL Normalization Service for SEO Audits
 * 
 * Normalizes URLs for consistent comparison and crawling:
 * - Lowercase host
 * - Remove trailing slash (except root)
 * - Remove UTM and tracking params
 * - Handle relative URLs
 */
class UrlNormalizer
{
    /**
     * Normalize a URL for crawling
     */
    public static function normalize(string $url, ?string $baseUrl = null): ?string
    {
        // Handle relative URLs
        if ($baseUrl && !preg_match('/^https?:\/\//', $url)) {
            $url = self::resolveRelativeUrl($url, $baseUrl);
        }

        // Parse URL
        $parsed = parse_url($url);
        if (!$parsed || !isset($parsed['host'])) {
            return null;
        }

        // Normalize scheme (prefer https)
        $scheme = $parsed['scheme'] ?? 'https';
        if (!in_array($scheme, ['http', 'https'])) {
            return null;
        }

        // Normalize host (lowercase, remove www.)
        $host = strtolower($parsed['host']);
        if (strpos($host, 'www.') === 0) {
            $host = substr($host, 4);
        }

        // Normalize path (remove trailing slash except root)
        $path = $parsed['path'] ?? '/';
        if ($path !== '/' && substr($path, -1) === '/') {
            $path = rtrim($path, '/');
        }

        // Remove query params (or keep whitelisted ones)
        $query = null;
        if (isset($parsed['query'])) {
            // For Phase 2: drop all query params for normalization
            // Can be extended to keep whitelisted params like 'page='
            $query = null;
        }

        // Build normalized URL
        $normalized = $scheme . '://' . $host . $path;
        if ($query) {
            $normalized .= '?' . $query;
        }

        return $normalized;
    }

    /**
     * Resolve relative URL against base URL
     */
    protected static function resolveRelativeUrl(string $relative, string $baseUrl): string
    {
        // If relative starts with /, it's absolute path
        if (strpos($relative, '/') === 0) {
            $parsed = parse_url($baseUrl);
            return ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '') . $relative;
        }

        // Otherwise, resolve relative to base path
        $parsed = parse_url($baseUrl);
        $basePath = $parsed['path'] ?? '/';
        
        // Remove filename from base path if it exists
        if (substr($basePath, -1) !== '/') {
            $basePath = dirname($basePath) . '/';
        }

        return ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '') . $basePath . $relative;
    }

    /**
     * Extract base host from URL
     */
    public static function extractHost(string $url): ?string
    {
        $parsed = parse_url($url);
        if (!$parsed || !isset($parsed['host'])) {
            return null;
        }

        $host = strtolower($parsed['host']);
        if (strpos($host, 'www.') === 0) {
            $host = substr($host, 4);
        }

        return $host;
    }

    /**
     * Check if URL is internal (same host)
     */
    public static function isInternal(string $url, string $baseHost): bool
    {
        $urlHost = self::extractHost($url);
        return $urlHost === $baseHost;
    }

    /**
     * Check if URL should be skipped
     */
    public static function shouldSkip(string $url): bool
    {
        // Skip anchors
        if (strpos($url, '#') === 0) {
            return true;
        }

        // Skip non-HTTP protocols
        if (preg_match('/^(mailto|tel|javascript):/i', $url)) {
            return true;
        }

        // Skip file extensions that aren't HTML
        $extensions = ['.pdf', '.jpg', '.jpeg', '.png', '.gif', '.svg', '.css', '.js', '.zip', '.rar', '.exe'];
        foreach ($extensions as $ext) {
            if (stripos($url, $ext) !== false) {
                return true;
            }
        }

        return false;
    }
}
