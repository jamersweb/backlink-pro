<?php

namespace App\Services\SEO;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SerpProvider implements SerpProviderInterface
{
    protected string $provider;
    protected string $apiKey;

    public function __construct()
    {
        $this->provider = config('seo.serp_provider', 'serpapi');
        $this->apiKey = config('seo.serp_api_key');
    }

    /**
     * Get rank for keyword
     */
    public function getRank(string $keyword, string $domain, string $country = 'PK', string $device = 'desktop'): ?array
    {
        if ($this->provider === 'serpapi') {
            return $this->getRankSerpApi($keyword, $domain, $country, $device);
        }

        // Add other providers here
        throw new \Exception("Provider {$this->provider} not implemented");
    }

    /**
     * Get rank using SerpAPI
     */
    protected function getRankSerpApi(string $keyword, string $domain, string $country, string $device): ?array
    {
        try {
            $response = Http::get('https://serpapi.com/search', [
                'api_key' => $this->apiKey,
                'q' => $keyword,
                'location' => $country,
                'device' => $device,
                'num' => 100,
            ]);

            if (!$response->successful()) {
                Log::warning('SerpAPI request failed', [
                    'keyword' => $keyword,
                    'error' => $response->body(),
                ]);
                return null;
            }

            $data = $response->json();
            $organicResults = $data['organic_results'] ?? [];

            // Find domain in results
            foreach ($organicResults as $index => $result) {
                $resultDomain = parse_url($result['link'] ?? '', PHP_URL_HOST);
                if ($resultDomain && str_contains($resultDomain, $domain)) {
                    return [
                        'position' => $index + 1,
                        'url' => $result['link'] ?? null,
                        'title' => $result['title'] ?? null,
                        'serp_features' => $this->extractSerpFeatures($data),
                    ];
                }
            }

            // Domain not found in top 100
            return [
                'position' => null,
                'url' => null,
                'serp_features' => $this->extractSerpFeatures($data),
            ];

        } catch (\Exception $e) {
            Log::error('SerpAPI error', [
                'keyword' => $keyword,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Extract SERP features
     */
    protected function extractSerpFeatures(array $data): array
    {
        $features = [];

        if (isset($data['answer_box'])) {
            $features[] = 'featured_snippet';
        }
        if (isset($data['related_questions'])) {
            $features[] = 'people_also_ask';
        }
        if (isset($data['knowledge_graph'])) {
            $features[] = 'knowledge_panel';
        }

        return $features;
    }
}
