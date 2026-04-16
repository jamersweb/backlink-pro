<?php

namespace App\Services\KeywordResearch\Metrics;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GoogleAdsKeywordMetricsProvider implements KeywordMetricsProviderInterface
{
    public function name(): string
    {
        return 'google_ads';
    }

    public function isConfigured(): bool
    {
        return $this->customerId() !== ''
            && ($this->accessToken() !== '' || $this->apiKey() !== '')
            && $this->developerToken() !== '';
    }

    public function fetch(array $keywords, array $context): array
    {
        $keywords = array_values(array_filter(array_map(
            fn ($keyword) => trim((string) $keyword),
            $keywords
        )));

        if (empty($keywords)) {
            return [
                'provider' => $this->name(),
                'status' => 'unavailable',
                'error' => null,
                'items' => [],
            ];
        }

        if (!$this->isConfigured()) {
            return [
                'provider' => $this->name(),
                'status' => 'not_configured',
                'error' => 'Google Ads credentials are missing for search volume enrichment.',
                'items' => [],
            ];
        }

        $url = "https://googleads.googleapis.com/v17/customers/{$this->customerId()}:generateKeywordHistoricalMetrics";
        if ($this->apiKey() !== '') {
            $url .= '?key=' . urlencode($this->apiKey());
        }

        $headers = [
            'developer-token' => $this->developerToken(),
            'Content-Type' => 'application/json',
        ];
        if ($this->loginCustomerId() !== '') {
            $headers['login-customer-id'] = $this->loginCustomerId();
        }
        if ($this->accessToken() !== '') {
            $headers['Authorization'] = 'Bearer ' . $this->accessToken();
        }

        $items = [];
        $countryCode = strtoupper((string) ($context['locale_country'] ?? 'PK'));
        $languageCode = strtolower((string) ($context['locale_language'] ?? 'en'));

        foreach (array_chunk($keywords, 700) as $batch) {
            try {
                $response = Http::withHeaders($headers)
                    ->timeout(30)
                    ->post($url, [
                        'keywords' => $batch,
                        'language' => "languageConstants/{$this->googleAdsLanguageId($languageCode)}",
                        'geo_target_constants' => [
                            "geoTargetConstants/{$this->googleAdsCountryId($countryCode)}",
                        ],
                        'keyword_plan_network' => 'GOOGLE_SEARCH_AND_PARTNERS',
                        'include_adult_keywords' => false,
                    ]);

                if (!$response->successful()) {
                    Log::warning('Google Ads keyword metrics failed', [
                        'status' => $response->status(),
                        'body' => Str::limit((string) $response->body(), 500),
                    ]);

                    return [
                        'provider' => $this->name(),
                        'status' => 'failed',
                        'error' => 'Google Ads keyword metrics request failed.',
                        'items' => [],
                    ];
                }

                foreach (($response->json('results') ?? []) as $result) {
                    $keyword = trim((string) ($result['text'] ?? ''));
                    $normalized = $this->normalizedKeyword($keyword);
                    if ($normalized === '') {
                        continue;
                    }

                    $keywordMetrics = $result['keyword_metrics'] ?? [];
                    $competitionIndex = $keywordMetrics['competition_index'] ?? null;
                    $cpcMicros = $keywordMetrics['average_cpc_micros'] ?? null;

                    $items[$normalized] = [
                        'search_volume' => isset($keywordMetrics['avg_monthly_searches'])
                            ? (int) $keywordMetrics['avg_monthly_searches']
                            : null,
                        'competition_score' => $competitionIndex !== null
                            ? max(0, min(100, (int) round((float) $competitionIndex)))
                            : null,
                        'cpc_value' => $cpcMicros !== null ? round(((float) $cpcMicros) / 1000000, 2) : null,
                        'trend_json' => $keywordMetrics['monthly_search_volumes'] ?? null,
                        'provider_response_json' => [
                            'competition' => $keywordMetrics['competition'] ?? null,
                            'competition_index' => $competitionIndex,
                        ],
                    ];
                }
            } catch (\Throwable $exception) {
                Log::warning('Google Ads keyword metrics exception', [
                    'message' => $exception->getMessage(),
                ]);

                return [
                    'provider' => $this->name(),
                    'status' => 'failed',
                    'error' => 'Google Ads keyword metrics request threw an exception.',
                    'items' => [],
                ];
            }
        }

        return [
            'provider' => $this->name(),
            'status' => empty($items) ? 'unavailable' : 'completed',
            'error' => empty($items) ? 'Google Ads returned no keyword metrics for this run.' : null,
            'items' => $items,
        ];
    }

    protected function developerToken(): string
    {
        return (string) (Setting::get('google_ads_developer_token')
            ?: config('services.google_ads.developer_token')
            ?: env('GOOGLE_ADS_DEVELOPER_TOKEN', ''));
    }

    protected function customerId(): string
    {
        return preg_replace('/\D+/', '', (string) (Setting::get('google_ads_customer_id')
            ?: config('services.google_ads.customer_id')
            ?: env('GOOGLE_ADS_CUSTOMER_ID', '')));
    }

    protected function accessToken(): string
    {
        return (string) (Setting::get('google_ads_access_token')
            ?: config('services.google_ads.access_token')
            ?: env('GOOGLE_ADS_ACCESS_TOKEN', ''));
    }

    protected function apiKey(): string
    {
        return (string) (Setting::get('google_ads_api_key')
            ?: config('services.google_ads.api_key')
            ?: env('GOOGLE_ADS_API_KEY', ''));
    }

    protected function loginCustomerId(): string
    {
        return preg_replace('/\D+/', '', (string) (Setting::get('google_ads_login_customer_id')
            ?: config('services.google_ads.login_customer_id')
            ?: env('GOOGLE_ADS_LOGIN_CUSTOMER_ID', '')));
    }

    protected function normalizedKeyword(string $keyword): string
    {
        $normalized = Str::of($keyword)->lower()->replaceMatches('/\s+/', ' ')->trim()->value();

        return preg_replace('/[^a-z0-9\s]/', '', $normalized) ?? '';
    }

    protected function googleAdsLanguageId(string $languageCode): string
    {
        return match ($languageCode) {
            'en' => '1000',
            'ur' => '1030',
            'ar' => '1019',
            'es' => '1003',
            'fr' => '1002',
            'de' => '1001',
            'pt' => '1014',
            'it' => '1004',
            'tr' => '1037',
            'ru' => '1031',
            'hi' => '1023',
            'bn' => '1020',
            'id' => '1025',
            'zh' => '1017',
            'ja' => '1005',
            default => '1000',
        };
    }

    protected function googleAdsCountryId(string $countryCode): string
    {
        return match ($countryCode) {
            'PK' => '2586',
            'US' => '2840',
            'GB' => '2826',
            'IN' => '2356',
            'AE' => '2784',
            'CA' => '2124',
            'AU' => '2036',
            'SA' => '2682',
            'DE' => '2276',
            'FR' => '2250',
            default => '2586',
        };
    }
}
