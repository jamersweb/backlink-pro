<?php

namespace App\Services\KeywordResearch;

use App\Models\KeywordResearchRun;
use App\Models\Organization;
use App\Models\User;
use App\Services\AI\LLMClient;
use Illuminate\Support\Str;

class KeywordResearchService
{
    public function __construct(
        protected LLMClient $llmClient
    ) {}

    public function generateAndStore(User $user, Organization $organization, array $data): KeywordResearchRun
    {
        $run = KeywordResearchRun::create([
            'user_id' => $user->id,
            'project_id' => $data['project_id'] ?? null,
            'input_type' => $data['input_type'],
            'seed_query' => $this->resolveSeedQuery($data),
            'seed_url' => $data['page_url'] ?? null,
            'context_text' => $data['input_text'] ?? null,
            'locale_country' => $data['locale_country'] ?? null,
            'locale_language' => $data['locale_language'] ?? null,
            'status' => 'completed',
        ]);

        $parsed = $this->generateWithAi($organization, $data);

        if (!$parsed) {
            $parsed = $this->fallbackResponse($data);
        }

        $items = $this->normalizeItems($parsed['keywords'] ?? []);
        if (empty($items)) {
            $items = $this->normalizeItems($this->fallbackResponse($data)['keywords']);
        }

        $run->items()->createMany($items);

        $run->update([
            'summary_text' => $parsed['summary'] ?? 'Keyword suggestions generated.',
            'result_count' => count($items),
        ]);

        return $run->load('items');
    }

    protected function generateWithAi(Organization $organization, array $data): ?array
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
- 30 to 50 relevant keywords.
- No duplicates or junk.
- Keep scores between 0 and 100.
- Keep fields concise and meaningful.
PROMPT;

        $userPrompt = $this->buildUserPrompt($organization, $data);

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

    protected function buildUserPrompt(Organization $organization, array $data): string
    {
        $mode = $data['input_type'];
        $inputText = trim((string) ($data['input_text'] ?? ''));
        $pageUrl = trim((string) ($data['page_url'] ?? ''));
        $country = trim((string) ($data['locale_country'] ?? ''));
        $language = trim((string) ($data['locale_language'] ?? ''));

        return <<<PROMPT
Generate keyword ideas for organization "{$organization->name}".
Input mode: {$mode}
Primary input text: {$inputText}
Page URL (if provided): {$pageUrl}
Target country: {$country}
Target language: {$language}

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
                'source' => 'ai',
                'intent' => $this->normalizeIntent($keywordData['intent'] ?? null),
                'funnel_stage' => $this->normalizeFunnelStage($keywordData['funnel_stage'] ?? null),
                'cluster_name' => $this->nullableString($keywordData['cluster_name'] ?? null),
                'recommended_content_type' => $this->normalizeContentType($keywordData['recommended_content_type'] ?? null),
                'confidence_score' => $this->normalizeScore($keywordData['confidence_score'] ?? null),
                'business_relevance_score' => $this->normalizeScore($keywordData['business_relevance_score'] ?? null),
                'ai_reason' => $this->nullableString($keywordData['ai_reason'] ?? null),
                'is_saved' => false,
            ];

            if (count($normalized) >= 50) {
                break;
            }
        }

        return $normalized;
    }

    protected function fallbackResponse(array $data): array
    {
        $seed = trim((string) ($this->resolveSeedQuery($data) ?? $data['input_text'] ?? 'keyword'));
        $modifiers = [
            'best',
            'services',
            'software',
            'tools',
            'near me',
            'pricing',
            'company',
            'guide',
            'tips',
            'for small business',
            'for beginners',
            'how to',
            'what is',
            'vs',
            'alternatives',
        ];

        $keywords = [$seed];
        foreach ($modifiers as $modifier) {
            $keywords[] = trim($seed . ' ' . $modifier);
            $keywords[] = trim($modifier . ' ' . $seed);
        }

        $items = [];
        foreach (array_slice(array_unique($keywords), 0, 35) as $keyword) {
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
            'summary' => 'Keyword ideas generated with fallback heuristics.',
            'keywords' => $items,
        ];
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
