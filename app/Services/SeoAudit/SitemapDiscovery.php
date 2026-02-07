<?php

namespace App\Services\SeoAudit;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sitemap Discovery Service
 * 
 * Discovers sitemap URLs from robots.txt and extracts URLs
 */
class SitemapDiscovery
{
    /**
     * Discover sitemap URLs from robots.txt
     */
    public static function discoverSitemaps(string $baseUrl): array
    {
        $sitemaps = [];
        
        try {
            $robotsUrl = self::getRobotsUrl($baseUrl);
            $response = Http::timeout(10)->get($robotsUrl);
            
            if ($response->successful()) {
                $content = $response->body();
                $lines = explode("\n", $content);
                
                foreach ($lines as $line) {
                    $line = trim($line);
                    // Look for Sitemap: entries
                    if (preg_match('/^Sitemap:\s*(.+)$/i', $line, $matches)) {
                        $sitemapUrl = trim($matches[1]);
                        if (filter_var($sitemapUrl, FILTER_VALIDATE_URL)) {
                            $sitemaps[] = $sitemapUrl;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::debug("Failed to discover sitemaps: " . $e->getMessage());
        }

        return $sitemaps;
    }

    /**
     * Extract URLs from sitemap XML
     */
    public static function extractUrlsFromSitemap(string $sitemapUrl, int $limit = 100): array
    {
        $urls = [];
        
        try {
            $response = Http::timeout(15)->get($sitemapUrl);
            
            if ($response->successful()) {
                $xml = $response->body();
                
                // Simple XML parsing for sitemap
                // Look for <loc> tags
                if (preg_match_all('/<loc>(.*?)<\/loc>/i', $xml, $matches)) {
                    foreach ($matches[1] as $url) {
                        $url = trim($url);
                        if (filter_var($url, FILTER_VALIDATE_URL)) {
                            $urls[] = $url;
                            if (count($urls) >= $limit) {
                                break;
                            }
                        }
                    }
                }
                
                // Also check for sitemap index (sitemaps within sitemap)
                if (preg_match_all('/<sitemap>.*?<loc>(.*?)<\/loc>.*?<\/sitemap>/is', $xml, $sitemapMatches)) {
                    foreach ($sitemapMatches[1] as $nestedSitemap) {
                        $nestedUrls = self::extractUrlsFromSitemap(trim($nestedSitemap), $limit - count($urls));
                        $urls = array_merge($urls, $nestedUrls);
                        if (count($urls) >= $limit) {
                            break;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::debug("Failed to extract URLs from sitemap: " . $e->getMessage());
        }

        return array_slice($urls, 0, $limit);
    }

    /**
     * Get robots.txt URL from base URL
     */
    protected static function getRobotsUrl(string $baseUrl): string
    {
        $parsed = parse_url($baseUrl);
        $scheme = $parsed['scheme'] ?? 'https';
        $host = $parsed['host'] ?? '';
        return $scheme . '://' . $host . '/robots.txt';
    }
}
