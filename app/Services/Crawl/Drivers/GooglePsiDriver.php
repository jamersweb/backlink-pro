<?php

namespace App\Services\Crawl\Drivers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GooglePsiDriver implements CrawlDriverInterface
{
    protected array $settings;

    public function __construct(array $settings = [])
    {
        $this->settings = $settings;
    }

    public function supports(string $taskType): bool
    {
        return $taskType === 'speed.pagespeed';
    }

    public function validateSettings(array $settings): array
    {
        if (empty($settings['api_key'])) {
            return ['ok' => false, 'message' => 'API key is required'];
        }

        // Test with a simple request
        try {
            $response = Http::get('https://www.googleapis.com/pagespeedonline/v5/runPagespeed', [
                'url' => 'https://example.com',
                'key' => $settings['api_key'],
            ]);

            if ($response->successful()) {
                return ['ok' => true, 'message' => 'API key is valid'];
            } else {
                $error = $response->json('error.message', 'Invalid API key');
                return ['ok' => false, 'message' => $error];
            }
        } catch (\Exception $e) {
            return ['ok' => false, 'message' => 'Failed to validate: ' . $e->getMessage()];
        }
    }

    public function execute(array $taskPayload): array
    {
        $url = $taskPayload['url'] ?? null;
        $strategy = $taskPayload['strategy'] ?? 'mobile';
        $apiKey = $this->settings['api_key'] ?? env('PAGESPEED_API_KEY');

        if (!$url) {
            throw new \InvalidArgumentException('URL is required');
        }

        if (!$apiKey) {
            throw new \Exception('Google PageSpeed API key not configured');
        }

        try {
            $response = Http::timeout(30)->get('https://www.googleapis.com/pagespeedonline/v5/runPagespeed', [
                'url' => $url,
                'key' => $apiKey,
                'strategy' => $strategy,
            ]);

            if (!$response->successful()) {
                $error = $response->json('error.message', 'Unknown error');
                throw new \Exception('PageSpeed API error: ' . $error);
            }

            $data = $response->json();

            return [
                'success' => true,
                'url' => $url,
                'strategy' => $strategy,
                'performance_score' => $data['lighthouseResult']['categories']['performance']['score'] * 100 ?? null,
                'lcp' => $this->extractMetric($data, 'largest-contentful-paint'),
                'cls' => $this->extractMetric($data, 'cumulative-layout-shift'),
                'inp' => $this->extractMetric($data, 'interaction-to-next-paint'),
                'fcp' => $this->extractMetric($data, 'first-contentful-paint'),
                'ttfb' => $this->extractMetric($data, 'server-response-time'),
                'raw' => $data,
            ];
        } catch (\Exception $e) {
            Log::error('Google PSI driver error', ['error' => $e->getMessage(), 'url' => $url]);
            throw $e;
        }
    }

    public function estimateCost(array $taskPayload): array
    {
        // Google PSI is free but quota-limited
        return [
            'units' => 1.0,
            'unit_name' => 'requests',
            'cents' => 0, // Free
        ];
    }

    protected function extractMetric(array $data, string $metricId): ?int
    {
        $audits = $data['lighthouseResult']['audits'] ?? [];
        $metric = $audits[$metricId] ?? null;

        if (!$metric) {
            return null;
        }

        $value = $metric['numericValue'] ?? null;
        return $value ? (int) round($value) : null;
    }
}


