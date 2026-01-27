<?php

namespace App\Services\Backlinks\Providers;

use App\Models\Setting;
use App\Services\Backlinks\BacklinkProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DataForSeoBacklinkProvider implements BacklinkProviderInterface
{
    protected $login;
    protected $password;
    protected $baseUrl = 'https://api.dataforseo.com/v3/backlinks';

    public function __construct()
    {
        $this->login = Setting::get('dataforseo_login') ?: config('services.backlinks.dataforseo.login');
        $this->password = Setting::get('dataforseo_password') ?: config('services.backlinks.dataforseo.password');

        if (empty($this->login) || empty($this->password)) {
            throw new \Exception('DataForSEO credentials are not configured. Please set DATAFORSEO_LOGIN and DATAFORSEO_PASSWORD in Settings or .env');
        }
    }

    /**
     * Fetch summary data
     */
    public function fetchSummary(string $host): array
    {
        try {
            $response = $this->makeRequest('/summary/live', [
                [
                    'target' => $host,
                ],
            ]);

            if (empty($response) || !isset($response[0]['result'])) {
                return [
                    'total_backlinks' => 0,
                    'ref_domains' => 0,
                    'follow' => 0,
                    'nofollow' => 0,
                ];
            }

            $result = $response[0]['result'][0] ?? [];
            
            return [
                'total_backlinks' => $result['backlinks'] ?? 0,
                'ref_domains' => $result['referring_domains'] ?? 0,
                'follow' => $result['referring_domains_nofollow'] ?? 0, // Note: DataForSEO may have different structure
                'nofollow' => $result['referring_domains_nofollow'] ?? 0,
            ];
        } catch (\Exception $e) {
            Log::error('DataForSEO fetch summary failed', [
                'host' => $host,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Fetch backlinks
     */
    public function fetchBacklinks(string $host, int $limit, int $offset = 0): array
    {
        try {
            $response = $this->makeRequest('/backlinks/live', [
                [
                    'target' => $host,
                    'limit' => min($limit, 1000), // API limit
                    'offset' => $offset,
                ],
            ]);

            if (empty($response) || !isset($response[0]['result'])) {
                return ['items' => [], 'total' => 0];
            }

            $result = $response[0]['result'][0] ?? [];
            $items = $result['items'] ?? [];
            $total = $result['total_count'] ?? count($items);

            $normalized = [];
            foreach ($items as $item) {
                $normalized[] = $this->normalizeBacklink($item);
            }

            return [
                'items' => $normalized,
                'total' => $total,
            ];
        } catch (\Exception $e) {
            Log::error('DataForSEO fetch backlinks failed', [
                'host' => $host,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Fetch referring domains
     */
    public function fetchRefDomains(string $host, int $limit, int $offset = 0): array
    {
        try {
            $response = $this->makeRequest('/referring_domains/live', [
                [
                    'target' => $host,
                    'limit' => min($limit, 1000),
                    'offset' => $offset,
                ],
            ]);

            if (empty($response) || !isset($response[0]['result'])) {
                return ['items' => [], 'total' => 0];
            }

            $result = $response[0]['result'][0] ?? [];
            $items = $result['items'] ?? [];
            $total = $result['total_count'] ?? count($items);

            $normalized = [];
            foreach ($items as $item) {
                $normalized[] = $this->normalizeRefDomain($item);
            }

            return [
                'items' => $normalized,
                'total' => $total,
            ];
        } catch (\Exception $e) {
            Log::error('DataForSEO fetch ref domains failed', [
                'host' => $host,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Fetch anchor text summaries
     */
    public function fetchAnchors(string $host, int $limit, int $offset = 0): array
    {
        try {
            $response = $this->makeRequest('/anchors/live', [
                [
                    'target' => $host,
                    'limit' => min($limit, 1000),
                    'offset' => $offset,
                ],
            ]);

            if (empty($response) || !isset($response[0]['result'])) {
                return ['items' => [], 'total' => 0];
            }

            $result = $response[0]['result'][0] ?? [];
            $items = $result['items'] ?? [];
            $total = $result['total_count'] ?? count($items);

            $normalized = [];
            foreach ($items as $item) {
                $normalized[] = $this->normalizeAnchor($item);
            }

            return [
                'items' => $normalized,
                'total' => $total,
            ];
        } catch (\Exception $e) {
            Log::error('DataForSEO fetch anchors failed', [
                'host' => $host,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Make API request with retry logic
     */
    protected function makeRequest(string $endpoint, array $data, int $retries = 2): array
    {
        $attempt = 0;
        
        while ($attempt <= $retries) {
            try {
                $response = Http::timeout(60)
                    ->withBasicAuth($this->login, $this->password)
                    ->post($this->baseUrl . $endpoint, $data);

                if ($response->status() === 429) {
                    // Rate limit - wait and retry
                    sleep(2);
                    $attempt++;
                    continue;
                }

                if (!$response->successful()) {
                    throw new \Exception('API request failed: ' . $response->body());
                }

                return $response->json();
            } catch (\Exception $e) {
                if ($attempt >= $retries) {
                    throw $e;
                }
                $attempt++;
                sleep(1);
            }
        }

        return [];
    }

    /**
     * Normalize backlink item
     */
    protected function normalizeBacklink(array $item): array
    {
        $sourceUrl = $item['url_from'] ?? '';
        $targetUrl = $item['url_to'] ?? '';
        $anchor = $item['anchor'] ?? null;
        $rel = $this->normalizeRel($item['dofollow'] ?? true, $item['sponsored'] ?? false, $item['ugc'] ?? false);

        return [
            'source_url' => $sourceUrl,
            'source_domain' => parse_url($sourceUrl, PHP_URL_HOST) ?? '',
            'target_url' => $targetUrl,
            'anchor' => $anchor,
            'rel' => $rel,
            'first_seen' => isset($item['first_seen']) ? date('Y-m-d', strtotime($item['first_seen'])) : null,
            'last_seen' => isset($item['last_seen']) ? date('Y-m-d', strtotime($item['last_seen'])) : null,
            'country' => $item['country'] ?? null,
            'tld' => $this->extractTld($sourceUrl),
        ];
    }

    /**
     * Normalize referring domain item
     */
    protected function normalizeRefDomain(array $item): array
    {
        $domain = $item['domain'] ?? '';

        return [
            'domain' => $domain,
            'backlinks_count' => $item['backlinks'] ?? 0,
            'first_seen' => isset($item['first_seen']) ? date('Y-m-d', strtotime($item['first_seen'])) : null,
            'last_seen' => isset($item['last_seen']) ? date('Y-m-d', strtotime($item['last_seen'])) : null,
            'tld' => $this->extractTld($domain),
            'country' => $item['country'] ?? null,
        ];
    }

    /**
     * Normalize anchor item
     */
    protected function normalizeAnchor(array $item): array
    {
        $anchor = $item['anchor'] ?? '';

        return [
            'anchor' => $anchor,
            'count' => $item['backlinks'] ?? $item['count'] ?? 0,
            'type' => $this->classifyAnchorType($anchor),
        ];
    }

    /**
     * Normalize rel attribute
     */
    protected function normalizeRel(bool $dofollow, bool $sponsored, bool $ugc): string
    {
        if ($sponsored) {
            return 'sponsored';
        }
        if ($ugc) {
            return 'ugc';
        }
        return $dofollow ? 'follow' : 'nofollow';
    }

    /**
     * Extract TLD from URL or domain
     */
    protected function extractTld(?string $url): ?string
    {
        if (!$url) {
            return null;
        }

        $host = parse_url($url, PHP_URL_HOST) ?? $url;
        $parts = explode('.', $host);
        
        if (count($parts) >= 2) {
            return '.' . end($parts);
        }

        return null;
    }

    /**
     * Classify anchor type (heuristic)
     */
    protected function classifyAnchorType(?string $anchor): string
    {
        if (empty($anchor) || trim($anchor) === '') {
            return 'empty';
        }

        $anchor = mb_strtolower(trim($anchor));

        // URL pattern
        if (preg_match('/^https?:\/\//', $anchor)) {
            return 'url';
        }

        // Generic anchors
        $generic = ['click here', 'read more', 'here', 'link', 'website', 'page', 'more', 'this', 'view'];
        if (in_array($anchor, $generic)) {
            return 'generic';
        }

        // Exact match (single word, likely brand)
        if (!str_contains($anchor, ' ') && strlen($anchor) > 2) {
            return 'exact';
        }

        // Partial (contains brand-like terms)
        if (strlen($anchor) <= 30 && !str_contains($anchor, ' ')) {
            return 'partial';
        }

        // Brand (longer, descriptive)
        if (strlen($anchor) > 10) {
            return 'brand';
        }

        return 'generic';
    }
}


