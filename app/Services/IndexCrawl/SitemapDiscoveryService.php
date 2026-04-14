<?php

namespace App\Services\IndexCrawl;

use App\Services\Audits\UrlNormalizer;
use Illuminate\Support\Facades\Http;

class SitemapDiscoveryService
{
    /**
     * @return array{files: array<int, array<string, mixed>>, urls: string[]}
     */
    public function discover(string $baseUrl, string $baseHost, array $robotsSitemaps = [], int $maxUrls = 5000): array
    {
        $seedSitemaps = array_values(array_unique(array_filter(array_merge(
            [
                rtrim($baseUrl, '/') . '/sitemap.xml',
                rtrim($baseUrl, '/') . '/sitemap_index.xml',
            ],
            $robotsSitemaps
        ))));

        $files = [];
        $allUrls = [];
        $visited = [];
        $queue = $seedSitemaps;

        while (!empty($queue) && count($allUrls) < $maxUrls) {
            $sitemapUrl = array_shift($queue);
            if (!$sitemapUrl || isset($visited[$sitemapUrl])) {
                continue;
            }
            $visited[$sitemapUrl] = true;

            $record = [
                'sitemap_url' => $sitemapUrl,
                'type' => 'sitemap',
                'fetch_status' => 'failed',
                'urls' => [],
                'fetched_at' => now(),
                'issues' => [],
            ];

            try {
                $response = Http::timeout(20)->get($sitemapUrl);
                $status = $response->status();

                if ($status >= 300 && $status < 400) {
                    $record['issues'][] = 'redirected_sitemap_url';
                }

                if (!$response->successful()) {
                    $record['issues'][] = 'broken_sitemap_url';
                    $files[] = $record;
                    continue;
                }

                $xml = $response->body();
                $parsed = $this->parseXml($xml, $baseHost, $maxUrls - count($allUrls));
                $record['type'] = $parsed['type'];
                $record['fetch_status'] = 'fetched';
                $record['urls'] = $parsed['urls'];

                if (!empty($parsed['sitemaps'])) {
                    foreach ($parsed['sitemaps'] as $childSitemap) {
                        if (!isset($visited[$childSitemap])) {
                            $queue[] = $childSitemap;
                        }
                    }
                }

                $allUrls = array_values(array_unique(array_merge($allUrls, $parsed['urls'])));
                $files[] = $record;
            } catch (\Throwable $e) {
                $record['issues'][] = 'sitemap_fetch_failed';
                $files[] = $record;
            }
        }

        return [
            'files' => $files,
            'urls' => array_values(array_unique($allUrls)),
        ];
    }

    /**
     * @return array{type: string, urls: string[], sitemaps: string[]}
     */
    protected function parseXml(string $xml, string $baseHost, int $limit): array
    {
        $result = [
            'type' => 'sitemap',
            'urls' => [],
            'sitemaps' => [],
        ];

        if ($limit <= 0) {
            return $result;
        }

        try {
            libxml_use_internal_errors(true);
            $dom = new \DOMDocument();
            $dom->loadXML($xml);

            $sitemapNodes = $dom->getElementsByTagName('sitemap');
            if ($sitemapNodes->length > 0) {
                $result['type'] = 'index';
                foreach ($sitemapNodes as $node) {
                    $locNode = $node->getElementsByTagName('loc')->item(0);
                    if ($locNode) {
                        $loc = trim($locNode->textContent);
                        if (filter_var($loc, FILTER_VALIDATE_URL)) {
                            $result['sitemaps'][] = $loc;
                        }
                    }
                }

                return $result;
            }

            $urlNodes = $dom->getElementsByTagName('url');
            foreach ($urlNodes as $node) {
                $locNode = $node->getElementsByTagName('loc')->item(0);
                if (!$locNode) {
                    continue;
                }
                $normalized = UrlNormalizer::normalize(trim($locNode->textContent), $baseHost);
                if (!$normalized) {
                    continue;
                }
                $result['urls'][] = $normalized;
                if (count($result['urls']) >= $limit) {
                    break;
                }
            }

            if ($urlNodes->length === 0) {
                $result['type'] = 'unknown';
            }
        } catch (\Throwable $e) {
            $result['type'] = 'invalid';
        }

        $result['urls'] = array_values(array_unique($result['urls']));
        $result['sitemaps'] = array_values(array_unique($result['sitemaps']));

        return $result;
    }
}
