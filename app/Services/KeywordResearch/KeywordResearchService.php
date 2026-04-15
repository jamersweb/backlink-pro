<?php

namespace App\Services\KeywordResearch;

use App\Models\Setting;
use App\Models\KeywordResearchItem;
use App\Models\KeywordResearchRun;
use App\Models\Organization;
use App\Models\User;
use App\Services\AI\LLMClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class KeywordResearchService
{
    protected const TARGET_KEYWORD_COUNT = 500;

    public function __construct(
        protected LLMClient $llmClient
    ) {}

    public function generateAndStore(User $user, ?Organization $organization, array $data): KeywordResearchRun
    {
        $run = KeywordResearchRun::create([
            'user_id' => $user->id,
            'input_type' => $data['input_type'],
            'seed_query' => $this->resolveSeedQuery($data),
            'seed_url' => $data['page_url'] ?? null,
            'context_text' => $data['input_text'] ?? null,
            'locale_country' => $data['locale_country'] ?? null,
            'locale_language' => $data['locale_language'] ?? null,
            'status' => 'completed',
        ]);

        $externalKeywordIdeas = $this->fetchExternalKeywordIdeas($data);
        $parsed = $this->generateWithAi($organization, $data, $externalKeywordIdeas);
        $usedFallback = false;

        if (!$parsed) {
            $parsed = $this->fallbackResponse($data);
            $usedFallback = true;
        }

        $mergedKeywords = array_merge($externalKeywordIdeas, $parsed['keywords'] ?? []);
        $items = $this->normalizeItems($mergedKeywords);

        if (empty($items)) {
            $items = $this->normalizeItems($this->fallbackResponse($data)['keywords']);
            $usedFallback = true;
        }

        if (count($items) < self::TARGET_KEYWORD_COUNT) {
            $items = $this->normalizeItems(array_merge(
                $mergedKeywords,
                $this->fallbackResponse($data)['keywords']
            ));
            $usedFallback = true;
        }

        $items = $this->enrichItemsWithRealMetrics($items, $data);

        $run->items()->createMany($items);
        $summary = $parsed['summary'] ?? 'Keyword suggestions generated.';

        if (!empty($externalKeywordIdeas)) {
            $summary .= ' Enriched with external keyword sources.';
        }
        if ($usedFallback) {
            $summary .= ' Fallback expansion applied for broader coverage.';
        }

        $run->update([
            'summary_text' => $summary,
            'result_count' => count($items),
        ]);

        return $run->load('items');
    }

    public function enrichMissingMetricsForRun(KeywordResearchRun $run): void
    {
        $run->loadMissing('items');
        $items = $run->items->map(function (KeywordResearchItem $item) {
            return [
                'id' => $item->id,
                'keyword' => $item->keyword,
                'normalized_keyword' => $item->normalized_keyword,
                'source' => $item->source,
                'intent' => $item->intent,
                'funnel_stage' => $item->funnel_stage,
                'cluster_name' => $item->cluster_name,
                'recommended_content_type' => $item->recommended_content_type,
                'confidence_score' => $item->confidence_score,
                'business_relevance_score' => $item->business_relevance_score,
                'keyword_traffic' => $item->keyword_traffic,
                'keyword_density_pct' => $item->keyword_density_pct,
                'ai_reason' => $item->ai_reason,
                'is_saved' => (bool) $item->is_saved,
            ];
        })->all();

        $enriched = $this->enrichItemsWithRealMetrics($items, [
            'input_type' => $run->input_type,
            'input_text' => $run->context_text,
            'page_url' => $run->seed_url,
            'locale_country' => $run->locale_country,
            'locale_language' => $run->locale_language,
            'seed_query' => $run->seed_query,
        ]);

        foreach ($enriched as $row) {
            if (!isset($row['id'])) {
                continue;
            }

            KeywordResearchItem::query()
                ->where('id', $row['id'])
                ->update([
                    'keyword_traffic' => $row['keyword_traffic'] ?? null,
                    'keyword_density_pct' => $row['keyword_density_pct'] ?? null,
                ]);
        }
    }

    protected function generateWithAi(?Organization $organization, array $data, array $externalKeywordIdeas = []): ?array
    {
        $systemPrompt = <<<PROMPT
You are an SEO keyword research assistant.
Return strict JSON only with this exact structure:
{
  "summary": "string",
  "keywords": [
    {
      "keyword": "string",
      "intent": "informational|commercial|transactional|navigational|local|unknown",
      "funnel_stage": "tofu|mofu|bofu|unknown",
      "cluster_name": "string",
      "recommended_content_type": "blog|landing_page|service_page|category_page|existing_page|unknown",
      "confidence_score": 0,
      "business_relevance_score": 0,
      "ai_reason": "string"
    }
  ]
}
Rules:
- Target 300 to 500 relevant keywords.
- No duplicates or junk.
- Keep scores between 0 and 100.
- Keep fields concise and meaningful.
PROMPT;

        $userPrompt = $this->buildUserPrompt($organization, $data, $externalKeywordIdeas);

        try {
            $response = $this->llmClient->generateWithSystemPrompt($systemPrompt, $userPrompt, [
                'json_mode' => true,
                'temperature' => 0.35,
                'max_tokens' => 2500,
            ]);

            $decoded = $this->decodeJson($response->content);
            if (!$decoded || !is_array($decoded)) {
                return null;
            }

            if (!isset($decoded['keywords']) || !is_array($decoded['keywords'])) {
                return null;
            }

            return $decoded;
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function buildUserPrompt(?Organization $organization, array $data, array $externalKeywordIdeas = []): string
    {
        $mode = $data['input_type'];
        $inputText = trim((string) ($data['input_text'] ?? ''));
        $pageUrl = trim((string) ($data['page_url'] ?? ''));
        $country = trim((string) ($data['locale_country'] ?? ''));
        $language = trim((string) ($data['locale_language'] ?? ''));
        $organizationName = trim((string) ($organization?->name ?? 'your business'));
        $externalPreview = collect($externalKeywordIdeas)
            ->pluck('keyword')
            ->filter()
            ->unique()
            ->take(80)
            ->implode(', ');
        $externalBlock = $externalPreview !== '' ? "\nExternal keyword signals: {$externalPreview}" : '';

        return <<<PROMPT
Generate keyword ideas for organization "{$organizationName}".
Input mode: {$mode}
Primary input text: {$inputText}
Page URL (if provided): {$pageUrl}
Target country: {$country}
Target language: {$language}
{$externalBlock}

For page mode, infer topic from provided URL and/or page description.
Return strict JSON only.
PROMPT;
    }

    protected function normalizeItems(array $keywords): array
    {
        $normalized = [];
        $seen = [];

        foreach ($keywords as $keywordData) {
            if (!is_array($keywordData)) {
                continue;
            }

            $keyword = trim((string) ($keywordData['keyword'] ?? ''));
            if ($keyword === '') {
                continue;
            }

            $normalizedKeyword = $this->normalizedKeyword($keyword);
            if ($normalizedKeyword === '' || isset($seen[$normalizedKeyword])) {
                continue;
            }

            $seen[$normalizedKeyword] = true;
            $normalized[] = [
                'keyword' => $keyword,
                'normalized_keyword' => $normalizedKeyword,
                'source' => $this->nullableString($keywordData['source'] ?? null) ?? 'ai',
                'intent' => $this->normalizeIntent($keywordData['intent'] ?? null),
                'funnel_stage' => $this->normalizeFunnelStage($keywordData['funnel_stage'] ?? null),
                'cluster_name' => $this->nullableString($keywordData['cluster_name'] ?? null),
                'recommended_content_type' => $this->normalizeContentType($keywordData['recommended_content_type'] ?? null),
                'confidence_score' => $this->normalizeScore($keywordData['confidence_score'] ?? null),
                'business_relevance_score' => $this->normalizeScore($keywordData['business_relevance_score'] ?? null),
                'keyword_traffic' => $this->normalizeTraffic($keywordData['keyword_traffic'] ?? null),
                'keyword_density_pct' => $keywordData['keyword_density_pct'] ?? null,
                'ai_reason' => $this->nullableString($keywordData['ai_reason'] ?? null),
                'is_saved' => false,
            ];

            if (count($normalized) >= self::TARGET_KEYWORD_COUNT) {
                break;
            }
        }

        return $normalized;
    }

    protected function fallbackResponse(array $data): array
    {
        $seed = trim((string) ($this->resolveSeedQuery($data) ?? $data['input_text'] ?? 'keyword'));
        $seed = $seed !== '' ? $seed : 'keyword';

        $prefixes = [
            'best',
            'top',
            'cheap',
            'affordable',
            'enterprise',
            'local',
            'online',
            'professional',
            'trusted',
            'advanced',
            'free',
            'premium',
            'small business',
            'beginner',
            'expert',
            'b2b',
            'b2c',
            'quick',
            'easy',
            'complete',
        ];

        $suffixes = [
            'services',
            'software',
            'tools',
            'platform',
            'pricing',
            'cost',
            'plans',
            'guide',
            'tips',
            'strategy',
            'checklist',
            'examples',
            'agency',
            'near me',
            'for startups',
            'for ecommerce',
            'for saas',
            'for doctors',
            'for real estate',
            'for education',
        ];

        $questionStarters = [
            'how to',
            'what is',
            'why use',
            'when to use',
            'which is best for',
            'where to buy',
            'can I use',
            'is it worth',
            'how much does',
            'how to choose',
        ];

        $commercialTerms = [
            'buy',
            'compare',
            'review',
            'alternatives',
            'vs',
            'discount',
            'trial',
            'demo',
            'quote',
            'consultation',
        ];

        $locations = [
            'in pakistan',
            'in lahore',
            'in karachi',
            'in islamabad',
            'in dubai',
            'in uk',
            'in usa',
            'in canada',
            'in australia',
            'globally',
        ];

        $keywords = [$seed];

        foreach ($prefixes as $prefix) {
            foreach ($suffixes as $suffix) {
                $keywords[] = trim("{$prefix} {$seed} {$suffix}");
            }
            $keywords[] = trim("{$prefix} {$seed}");
        }

        foreach ($suffixes as $suffix) {
            $keywords[] = trim("{$seed} {$suffix}");
        }

        foreach ($questionStarters as $questionStarter) {
            $keywords[] = trim("{$questionStarter} {$seed}");
            foreach ($suffixes as $suffix) {
                $keywords[] = trim("{$questionStarter} {$seed} {$suffix}");
            }
        }

        foreach ($commercialTerms as $commercialTerm) {
            $keywords[] = trim("{$seed} {$commercialTerm}");
            $keywords[] = trim("{$commercialTerm} {$seed}");
        }

        foreach ($locations as $location) {
            $keywords[] = trim("{$seed} {$location}");
            foreach ($suffixes as $suffix) {
                $keywords[] = trim("{$seed} {$suffix} {$location}");
            }
        }

        $items = [];
        foreach (array_slice(array_unique($keywords), 0, self::TARGET_KEYWORD_COUNT) as $keyword) {
            $items[] = [
                'keyword' => $keyword,
                'intent' => $this->guessIntent($keyword),
                'funnel_stage' => 'unknown',
                'cluster_name' => Str::title($seed),
                'recommended_content_type' => 'unknown',
                'confidence_score' => 55,
                'business_relevance_score' => 60,
                'ai_reason' => 'Generated from fallback keyword pattern rules.',
            ];
        }

        return [
            'summary' => 'Keyword ideas generated with fallback heuristics (extended set).',
            'keywords' => $items,
        ];
    }

    protected function fetchExternalKeywordIdeas(array $data): array
    {
        $ideas = [];
        $ideas = array_merge($ideas, $this->fetchDataForSeoKeywordIdeas($data));
        $ideas = array_merge($ideas, $this->fetchGoogleAdsKeywordIdeas($data));
        $ideas = array_merge($ideas, $this->fetchGoogleSuggestKeywordIdeas($data));
        $ideas = array_merge($ideas, $this->fetchSerpApiKeywordIdeas($data));

        return $this->dedupeKeywordIdeaRows($ideas);
    }

    protected function fetchGoogleAdsKeywordIdeas(array $data): array
    {
        $seed = trim((string) ($this->resolveSeedQuery($data) ?? ''));
        if ($seed === '') {
            return [];
        }

        $developerToken = (string) (Setting::get('google_ads_developer_token')
            ?: config('services.google_ads.developer_token')
            ?: env('GOOGLE_ADS_DEVELOPER_TOKEN', ''));
        $customerId = preg_replace('/\D+/', '', (string) (Setting::get('google_ads_customer_id')
            ?: config('services.google_ads.customer_id')
            ?: env('GOOGLE_ADS_CUSTOMER_ID', '')));
        $accessToken = (string) (Setting::get('google_ads_access_token')
            ?: config('services.google_ads.access_token')
            ?: env('GOOGLE_ADS_ACCESS_TOKEN', ''));
        $apiKey = (string) (Setting::get('google_ads_api_key')
            ?: config('services.google_ads.api_key')
            ?: env('GOOGLE_ADS_API_KEY', ''));
        $loginCustomerId = preg_replace('/\D+/', '', (string) (Setting::get('google_ads_login_customer_id')
            ?: config('services.google_ads.login_customer_id')
            ?: env('GOOGLE_ADS_LOGIN_CUSTOMER_ID', '')));

        if ($customerId === '' || ($accessToken === '' && $apiKey === '') || $developerToken === '') {
            return [];
        }

        $countryCode = strtoupper((string) ($data['locale_country'] ?? 'PK'));
        $languageCode = strtolower((string) ($data['locale_language'] ?? 'en'));

        $url = "https://googleads.googleapis.com/v17/customers/{$customerId}:generateKeywordIdeas";
        if ($apiKey !== '') {
            $url .= '?key=' . urlencode($apiKey);
        }

        $headers = [
            'developer-token' => $developerToken,
            'Content-Type' => 'application/json',
        ];
        if ($loginCustomerId !== '') {
            $headers['login-customer-id'] = $loginCustomerId;
        }
        if ($accessToken !== '') {
            $headers['Authorization'] = 'Bearer ' . $accessToken;
        }

        $payload = [
            'keyword_seed' => [
                'keywords' => [$seed],
            ],
            'language' => "languageConstants/{$this->googleAdsLanguageId($languageCode)}",
            'geo_target_constants' => [
                "geoTargetConstants/{$this->googleAdsCountryId($countryCode)}",
            ],
            'keyword_plan_network' => 'GOOGLE_SEARCH_AND_PARTNERS',
            'include_adult_keywords' => false,
            'page_size' => 1000,
        ];

        try {
            $response = Http::withHeaders($headers)
                ->timeout(25)
                ->post($url, $payload);

            if (!$response->successful()) {
                Log::warning('Google Ads keyword ideas request failed', [
                    'status' => $response->status(),
                    'body' => Str::limit((string) $response->body(), 500),
                ]);
                return [];
            }

            $rows = [];
            foreach (($response->json('results') ?? []) as $result) {
                $keyword = trim((string) ($result['text'] ?? ''));
                if ($keyword === '') {
                    continue;
                }
                $rows[] = [
                    'keyword' => $keyword,
                    'source' => 'google_ads',
                    'intent' => 'unknown',
                    'funnel_stage' => 'unknown',
                    'cluster_name' => Str::title($seed),
                    'recommended_content_type' => 'unknown',
                    'confidence_score' => 74,
                    'business_relevance_score' => 72,
                    'keyword_traffic' => (int) ($result['keyword_idea_metrics']['avg_monthly_searches'] ?? 0),
                    'ai_reason' => 'Derived from Google Ads keyword ideas.',
                ];
            }

            return $rows;
        } catch (\Throwable $exception) {
            Log::warning('Google Ads keyword ideas fetch error', [
                'message' => $exception->getMessage(),
            ]);
            return [];
        }
    }

    protected function fetchDataForSeoKeywordIdeas(array $data): array
    {
        $seed = trim((string) ($this->resolveSeedQuery($data) ?? ''));
        if ($seed === '') {
            return [];
        }

        $login = (string) (Setting::get('dataforseo_login') ?: config('services.backlinks.dataforseo.login') ?: '');
        $password = (string) (Setting::get('dataforseo_password') ?: config('services.backlinks.dataforseo.password') ?: '');
        if ($login === '' || $password === '') {
            return [];
        }

        $locationCode = $this->dataForSeoLocationCode((string) ($data['locale_country'] ?? 'PK'));
        $languageCode = $this->dataForSeoLanguageCode((string) ($data['locale_language'] ?? 'en'));

        try {
            $response = Http::withBasicAuth($login, $password)
                ->timeout(25)
                ->post('https://api.dataforseo.com/v3/keywords_data/google_ads/keywords_for_keywords/live', [[
                    'keywords' => [$seed],
                    'location_code' => $locationCode,
                    'language_code' => $languageCode,
                    'limit' => 700,
                ]]);

            if (!$response->successful()) {
                return [];
            }

            $items = $response->json('tasks.0.result.0.items', []);
            $rows = [];

            foreach ($items as $item) {
                $keyword = trim((string) ($item['keyword'] ?? ''));
                if ($keyword === '') {
                    continue;
                }

                $competition = (float) ($item['competition'] ?? $item['competition_index'] ?? 0);
                $competitionScore = max(0, min(100, (int) round($competition * 100)));
                $searchVolume = (int) ($item['search_volume'] ?? 0);
                $confidence = $searchVolume > 0 ? min(100, 45 + (int) round(log10($searchVolume + 1) * 18)) : 55;

                $rows[] = [
                    'keyword' => $keyword,
                    'source' => 'dataforseo_google_ads',
                    'intent' => 'unknown',
                    'funnel_stage' => 'unknown',
                    'cluster_name' => Str::title($seed),
                    'recommended_content_type' => 'unknown',
                    'confidence_score' => $confidence,
                    'business_relevance_score' => max(40, 100 - $competitionScore),
                    'keyword_traffic' => $searchVolume,
                    'ai_reason' => 'Derived from DataForSEO Google Ads keyword database.',
                ];
            }

            return $rows;
        } catch (\Throwable $exception) {
            Log::warning('DataForSEO keyword ideas fetch error', [
                'message' => $exception->getMessage(),
            ]);
            return [];
        }
    }

    protected function fetchGoogleSuggestKeywordIdeas(array $data): array
    {
        $seed = trim((string) ($this->resolveSeedQuery($data) ?? ''));
        if ($seed === '') {
            return [];
        }

        $languageCode = strtolower((string) ($data['locale_language'] ?? 'en'));
        $countryCode = strtoupper((string) ($data['locale_country'] ?? 'PK'));
        $queries = [$seed];
        foreach (['best', 'top', 'cheap', 'near me', 'how to', 'vs', 'for small business'] as $modifier) {
            $queries[] = trim("{$seed} {$modifier}");
        }

        $rows = [];
        foreach (array_unique($queries) as $query) {
            try {
                $response = Http::timeout(12)->get('https://suggestqueries.google.com/complete/search', [
                    'client' => 'firefox',
                    'q' => $query,
                    'hl' => $languageCode,
                    'gl' => $countryCode,
                ]);

                if (!$response->successful()) {
                    continue;
                }

                $suggestions = $response->json()[1] ?? [];
                foreach ($suggestions as $suggestion) {
                    $keyword = trim((string) $suggestion);
                    if ($keyword === '') {
                        continue;
                    }
                    $rows[] = [
                        'keyword' => $keyword,
                        'source' => 'google_suggest',
                        'intent' => 'unknown',
                        'funnel_stage' => 'unknown',
                        'cluster_name' => Str::title($seed),
                        'recommended_content_type' => 'unknown',
                        'confidence_score' => 66,
                        'business_relevance_score' => 65,
                        'ai_reason' => 'Derived from Google autosuggest trends.',
                    ];
                }
            } catch (\Throwable $exception) {
                continue;
            }
        }

        return $rows;
    }

    protected function fetchSerpApiKeywordIdeas(array $data): array
    {
        $apiKey = (string) (Setting::get('serpapi_api_key') ?: env('SERPAPI_API_KEY', ''));
        $seed = trim((string) ($this->resolveSeedQuery($data) ?? ''));
        if ($apiKey === '' || $seed === '') {
            return [];
        }

        try {
            $response = Http::timeout(20)->get('https://serpapi.com/search.json', [
                'api_key' => $apiKey,
                'engine' => 'google',
                'q' => $seed,
                'hl' => strtolower((string) ($data['locale_language'] ?? 'en')),
                'gl' => strtolower((string) ($data['locale_country'] ?? 'pk')),
            ]);

            if (!$response->successful()) {
                return [];
            }

            $rows = [];
            foreach (($response->json('related_questions') ?? []) as $question) {
                $keyword = trim((string) ($question['question'] ?? ''));
                if ($keyword !== '') {
                    $rows[] = [
                        'keyword' => $keyword,
                        'source' => 'serpapi',
                        'intent' => 'informational',
                        'funnel_stage' => 'tofu',
                        'cluster_name' => Str::title($seed),
                        'recommended_content_type' => 'blog',
                        'confidence_score' => 64,
                        'business_relevance_score' => 62,
                        'ai_reason' => 'Derived from SERP people-also-ask signals.',
                    ];
                }
            }

            foreach (($response->json('related_searches') ?? []) as $relatedSearch) {
                $keyword = trim((string) ($relatedSearch['query'] ?? ''));
                if ($keyword !== '') {
                    $rows[] = [
                        'keyword' => $keyword,
                        'source' => 'serpapi',
                        'intent' => 'unknown',
                        'funnel_stage' => 'unknown',
                        'cluster_name' => Str::title($seed),
                        'recommended_content_type' => 'unknown',
                        'confidence_score' => 67,
                        'business_relevance_score' => 64,
                        'ai_reason' => 'Derived from SERP related searches.',
                    ];
                }
            }

            return $rows;
        } catch (\Throwable $exception) {
            return [];
        }
    }

    protected function dedupeKeywordIdeaRows(array $rows): array
    {
        $deduped = [];
        $seen = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $keyword = trim((string) ($row['keyword'] ?? ''));
            if ($keyword === '') {
                continue;
            }
            $normalized = $this->normalizedKeyword($keyword);
            if ($normalized === '' || isset($seen[$normalized])) {
                continue;
            }

            $seen[$normalized] = true;
            $deduped[] = $row;

            if (count($deduped) >= self::TARGET_KEYWORD_COUNT) {
                break;
            }
        }

        return $deduped;
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

    protected function dataForSeoLocationCode(string $countryCode): int
    {
        return match (strtoupper($countryCode)) {
            'PK' => 2586,
            'US' => 2840,
            'GB' => 2826,
            'IN' => 2356,
            'AE' => 2784,
            'CA' => 2124,
            'AU' => 2036,
            'SA' => 2682,
            'DE' => 2276,
            'FR' => 2250,
            default => 2586,
        };
    }

    protected function dataForSeoLanguageCode(string $languageCode): string
    {
        return match (strtolower($languageCode)) {
            'en' => 'en',
            'ur' => 'ur',
            'ar' => 'ar',
            'es' => 'es',
            'fr' => 'fr',
            'de' => 'de',
            'pt' => 'pt',
            'it' => 'it',
            'tr' => 'tr',
            'ru' => 'ru',
            'hi' => 'hi',
            'bn' => 'bn',
            'id' => 'id',
            'zh' => 'zh',
            'ja' => 'ja',
            default => 'en',
        };
    }

    protected function resolveSeedQuery(array $data): ?string
    {
        return match ($data['input_type']) {
            'keyword', 'product' => trim((string) ($data['input_text'] ?? '')),
            'page' => trim((string) ($data['page_url'] ?? $data['input_text'] ?? '')),
            default => trim((string) ($data['input_text'] ?? '')),
        };
    }

    protected function decodeJson(string $content): ?array
    {
        $trimmed = trim($content);
        if (str_starts_with($trimmed, '```')) {
            $trimmed = preg_replace('/^```(?:json)?\s*/', '', $trimmed) ?? $trimmed;
            $trimmed = preg_replace('/\s*```$/', '', $trimmed) ?? $trimmed;
            $trimmed = trim($trimmed);
        }

        $decoded = json_decode($trimmed, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
    }

    protected function normalizedKeyword(string $keyword): string
    {
        $normalized = Str::of($keyword)->lower()->replaceMatches('/\s+/', ' ')->trim()->value();
        return preg_replace('/[^a-z0-9\s]/', '', $normalized) ?? '';
    }

    protected function normalizeIntent(?string $intent): ?string
    {
        $allowed = ['informational', 'commercial', 'transactional', 'navigational', 'local', 'unknown'];
        $value = Str::lower((string) $intent);
        return in_array($value, $allowed, true) ? $value : 'unknown';
    }

    protected function normalizeFunnelStage(?string $funnelStage): ?string
    {
        $allowed = ['tofu', 'mofu', 'bofu', 'unknown'];
        $value = Str::lower((string) $funnelStage);
        return in_array($value, $allowed, true) ? $value : 'unknown';
    }

    protected function normalizeContentType(?string $contentType): ?string
    {
        $allowed = ['blog', 'landing_page', 'service_page', 'category_page', 'existing_page', 'unknown'];
        $value = Str::lower((string) $contentType);
        return in_array($value, $allowed, true) ? $value : 'unknown';
    }

    protected function normalizeScore(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $score = (int) round((float) $value);
        return max(0, min(100, $score));
    }

    protected function normalizeTraffic(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $traffic = (int) round((float) $value);
        return max(0, $traffic);
    }

    protected function enrichItemsWithRealMetrics(array $items, array $data): array
    {
        if (empty($items)) {
            return $items;
        }

        $keywords = collect($items)
            ->pluck('keyword')
            ->filter()
            ->map(fn ($keyword) => trim((string) $keyword))
            ->unique()
            ->values()
            ->all();

        $historicalTrafficMap = $this->fetchGoogleAdsHistoricalMetrics($keywords, $data);
        $densityContext = $this->buildDensityContext($data);

        return array_map(function ($item) use ($historicalTrafficMap, $densityContext) {
            $normalized = $this->normalizedKeyword((string) ($item['keyword'] ?? ''));
            $realTraffic = $this->normalizeTraffic($item['keyword_traffic'] ?? null);

            if ($realTraffic === null && $normalized !== '' && isset($historicalTrafficMap[$normalized])) {
                $realTraffic = $historicalTrafficMap[$normalized];
            }

            $item['keyword_traffic'] = $realTraffic;
            $item['keyword_density_pct'] = $this->calculateRealKeywordDensityPercent(
                (string) ($item['keyword'] ?? ''),
                $densityContext
            );

            return $item;
        }, $items);
    }

    protected function fetchGoogleAdsHistoricalMetrics(array $keywords, array $data): array
    {
        $keywords = array_values(array_filter(array_map(
            fn ($keyword) => trim((string) $keyword),
            $keywords
        )));
        if (empty($keywords)) {
            return [];
        }

        $developerToken = (string) (Setting::get('google_ads_developer_token')
            ?: config('services.google_ads.developer_token')
            ?: env('GOOGLE_ADS_DEVELOPER_TOKEN', ''));
        $customerId = preg_replace('/\D+/', '', (string) (Setting::get('google_ads_customer_id')
            ?: config('services.google_ads.customer_id')
            ?: env('GOOGLE_ADS_CUSTOMER_ID', '')));
        $accessToken = (string) (Setting::get('google_ads_access_token')
            ?: config('services.google_ads.access_token')
            ?: env('GOOGLE_ADS_ACCESS_TOKEN', ''));
        $apiKey = (string) (Setting::get('google_ads_api_key')
            ?: config('services.google_ads.api_key')
            ?: env('GOOGLE_ADS_API_KEY', ''));
        $loginCustomerId = preg_replace('/\D+/', '', (string) (Setting::get('google_ads_login_customer_id')
            ?: config('services.google_ads.login_customer_id')
            ?: env('GOOGLE_ADS_LOGIN_CUSTOMER_ID', '')));

        if ($customerId === '' || ($accessToken === '' && $apiKey === '') || $developerToken === '') {
            return [];
        }

        $countryCode = strtoupper((string) ($data['locale_country'] ?? 'PK'));
        $languageCode = strtolower((string) ($data['locale_language'] ?? 'en'));
        $url = "https://googleads.googleapis.com/v17/customers/{$customerId}:generateKeywordHistoricalMetrics";
        if ($apiKey !== '') {
            $url .= '?key=' . urlencode($apiKey);
        }

        $headers = [
            'developer-token' => $developerToken,
            'Content-Type' => 'application/json',
        ];
        if ($loginCustomerId !== '') {
            $headers['login-customer-id'] = $loginCustomerId;
        }
        if ($accessToken !== '') {
            $headers['Authorization'] = 'Bearer ' . $accessToken;
        }

        $metrics = [];
        foreach (array_chunk($keywords, 700) as $batch) {
            try {
                $payload = [
                    'keywords' => $batch,
                    'language' => "languageConstants/{$this->googleAdsLanguageId($languageCode)}",
                    'geo_target_constants' => [
                        "geoTargetConstants/{$this->googleAdsCountryId($countryCode)}",
                    ],
                    'keyword_plan_network' => 'GOOGLE_SEARCH_AND_PARTNERS',
                    'include_adult_keywords' => false,
                ];

                $response = Http::withHeaders($headers)
                    ->timeout(30)
                    ->post($url, $payload);

                if (!$response->successful()) {
                    Log::warning('Google Ads historical metrics failed', [
                        'status' => $response->status(),
                        'body' => Str::limit((string) $response->body(), 500),
                    ]);
                    continue;
                }

                foreach (($response->json('results') ?? []) as $result) {
                    $keyword = trim((string) ($result['text'] ?? ''));
                    if ($keyword === '') {
                        continue;
                    }

                    $normalized = $this->normalizedKeyword($keyword);
                    if ($normalized === '') {
                        continue;
                    }

                    $metrics[$normalized] = (int) ($result['keyword_metrics']['avg_monthly_searches'] ?? 0);
                }
            } catch (\Throwable $exception) {
                Log::warning('Google Ads historical metrics error', [
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        return $metrics;
    }

    protected function buildDensityContext(array $data): array
    {
        $parts = [];
        $inputText = trim((string) ($data['input_text'] ?? ''));
        if ($inputText !== '') {
            $parts[] = $inputText;
        }

        $seed = trim((string) ($data['seed_query'] ?? $this->resolveSeedQuery($data) ?? ''));
        if ($seed !== '') {
            $parts[] = $seed;
        }

        $pageUrl = trim((string) ($data['page_url'] ?? ''));
        if ($pageUrl !== '') {
            try {
                $response = Http::timeout(15)->get($pageUrl);
                if ($response->successful()) {
                    $parts[] = strip_tags((string) $response->body());
                }
            } catch (\Throwable $exception) {
                // Best effort only.
            }
        }

        $serpapiKey = (string) (Setting::get('serpapi_api_key') ?: env('SERPAPI_API_KEY', ''));
        if ($serpapiKey !== '' && $seed !== '') {
            try {
                $serp = Http::timeout(20)->get('https://serpapi.com/search.json', [
                    'api_key' => $serpapiKey,
                    'engine' => 'google',
                    'q' => $seed,
                    'hl' => strtolower((string) ($data['locale_language'] ?? 'en')),
                    'gl' => strtolower((string) ($data['locale_country'] ?? 'pk')),
                ]);

                if ($serp->successful()) {
                    foreach (($serp->json('organic_results') ?? []) as $result) {
                        $parts[] = trim((string) ($result['title'] ?? ''));
                        $parts[] = trim((string) ($result['snippet'] ?? ''));
                    }
                    foreach (($serp->json('related_questions') ?? []) as $question) {
                        $parts[] = trim((string) ($question['question'] ?? ''));
                        $parts[] = trim((string) ($question['snippet'] ?? ''));
                    }
                }
            } catch (\Throwable $exception) {
                // Best effort only.
            }
        }

        $corpus = $this->normalizedKeyword(implode(' ', $parts));
        $tokens = preg_split('/\s+/', $corpus, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return [
            'text' => $corpus,
            'total_words' => count($tokens),
        ];
    }

    protected function calculateRealKeywordDensityPercent(string $keyword, array $densityContext): ?float
    {
        $corpus = (string) ($densityContext['text'] ?? '');
        $totalWords = (int) ($densityContext['total_words'] ?? 0);
        if ($corpus === '' || $totalWords <= 0) {
            return null;
        }

        $normalizedKeyword = $this->normalizedKeyword($keyword);
        if ($normalizedKeyword === '') {
            return null;
        }

        $phraseWords = preg_split('/\s+/', $normalizedKeyword, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (empty($phraseWords)) {
            return null;
        }

        $pattern = '/\b' . preg_quote($normalizedKeyword, '/') . '\b/u';
        $occurrences = preg_match_all($pattern, $corpus);
        if ($occurrences === false) {
            return null;
        }

        $density = (($occurrences * count($phraseWords)) / $totalWords) * 100;
        return round($density, 2);
    }

    protected function nullableString(mixed $value): ?string
    {
        $string = trim((string) $value);
        return $string === '' ? null : $string;
    }

    protected function guessIntent(string $keyword): string
    {
        $value = Str::lower($keyword);
        if (Str::contains($value, ['buy', 'price', 'pricing', 'cost'])) {
            return 'transactional';
        }
        if (Str::contains($value, ['best', 'top', 'vs', 'compare'])) {
            return 'commercial';
        }
        if (Str::contains($value, ['near me', 'local'])) {
            return 'local';
        }

        return 'informational';
    }
}
