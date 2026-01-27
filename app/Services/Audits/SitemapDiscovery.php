<?php

namespace App\Services\Audits;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SitemapDiscovery
{
    /**
     * Discover URLs from sitemap
     */
    public static function discover(string $baseUrl, int $limit = 1000): array
    {
        $urls = [];
        $baseHost = parse_url($baseUrl, PHP_URL_HOST);
        if (!$baseHost) {
            return $urls;
        }

        // Try common sitemap locations
        $sitemapUrls = [
            $baseUrl . '/sitemap.xml',
            $baseUrl . '/sitemap_index.xml',
        ];

        // Also check robots.txt for sitemap references
        try {
            $robotsResponse = Http::timeout(10)->get($baseUrl . '/robots.txt');
            if ($robotsResponse->successful()) {
                $robotsContent = $robotsResponse->body();
                preg_match_all('/^Sitemap:\s*(.+)$/mi', $robotsContent, $matches);
                if (!empty($matches[1])) {
                    foreach ($matches[1] as $sitemapUrl) {
                        $sitemapUrls[] = trim($sitemapUrl);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::debug('Failed to fetch robots.txt', ['url' => $baseUrl, 'error' => $e->getMessage()]);
        }

        // Try each sitemap URL
        foreach ($sitemapUrls as $sitemapUrl) {
            try {
                $response = Http::timeout(15)->get($sitemapUrl);
                if (!$response->successful()) {
                    continue;
                }

                $xml = $response->body();
                $urls = array_merge($urls, self::parseSitemap($xml, $baseHost, $limit - count($urls)));

                if (count($urls) >= $limit) {
                    break;
                }
            } catch (\Exception $e) {
                Log::debug('Failed to fetch sitemap', ['url' => $sitemapUrl, 'error' => $e->getMessage()]);
                continue;
            }
        }

        return array_unique(array_slice($urls, 0, $limit));
    }

    /**
     * Parse sitemap XML
     */
    protected static function parseSitemap(string $xml, string $baseHost, int $limit): array
    {
        $urls = [];

        try {
            libxml_use_internal_errors(true);
            $dom = new \DOMDocument();
            $dom->loadXML($xml);

            // Check if it's a sitemap index
            $sitemapIndex = $dom->getElementsByTagName('sitemapindex');
            if ($sitemapIndex->length > 0) {
                // It's a sitemap index, parse child sitemaps
                $sitemaps = $dom->getElementsByTagName('sitemap');
                foreach ($sitemaps as $sitemap) {
                    $loc = $sitemap->getElementsByTagName('loc')->item(0);
                    if ($loc) {
                        $sitemapUrl = trim($loc->textContent);
                        try {
                            $response = Http::timeout(15)->get($sitemapUrl);
                            if ($response->successful()) {
                                $childUrls = self::parseSitemap($response->body(), $baseHost, $limit - count($urls));
                                $urls = array_merge($urls, $childUrls);
                                if (count($urls) >= $limit) {
                                    break;
                                }
                            }
                        } catch (\Exception $e) {
                            Log::debug('Failed to fetch child sitemap', ['url' => $sitemapUrl]);
                        }
                    }
                }
            } else {
                // Regular sitemap with URLs
                $urlElements = $dom->getElementsByTagName('url');
                foreach ($urlElements as $urlElement) {
                    $loc = $urlElement->getElementsByTagName('loc')->item(0);
                    if ($loc) {
                        $url = trim($loc->textContent);
                        $normalized = UrlNormalizer::normalize($url, $baseHost);
                        if ($normalized) {
                            $urls[] = $normalized;
                            if (count($urls) >= $limit) {
                                break;
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::debug('Failed to parse sitemap XML', ['error' => $e->getMessage()]);
        }

        return $urls;
    }
}


