<?php

namespace Tests\Unit\KeywordResearch;

use App\Services\AI\LLMClient;
use App\Services\KeywordResearch\KeywordResearchService;
use App\Services\KeywordResearch\Metrics\KeywordMetricsProviderManager;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class KeywordResearchServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_fallback_keywords_are_bucketed_and_diverse(): void
    {
        $service = $this->makeService();

        $response = $service->exposedFallbackResponse([
            'input_type' => 'keyword',
            'input_text' => 'trusted Apple',
            'locale_country' => 'PK',
        ]);

        $keywords = collect($response['keywords']);

        $this->assertGreaterThan(12, $keywords->count());
        $this->assertSame($keywords->count(), $keywords->pluck('keyword')->unique()->count());
        $this->assertGreaterThan(4, $keywords->pluck('pattern_type')->unique()->count());
        $this->assertTrue($keywords->every(fn ($row) => $row['source'] === 'fallback_generated'));
    }

    public function test_density_stays_not_analyzed_without_target_url(): void
    {
        $service = $this->makeService();

        $densityContext = $service->exposedBuildDensityContext([
            'input_type' => 'keyword',
            'input_text' => 'apple seo tools',
        ]);

        $analysis = $service->exposedAnalyzeKeywordDensity('apple seo tools', $densityContext);

        $this->assertSame('not_analyzed', $analysis['density_status']);
        $this->assertNull($analysis['keyword_density_pct']);
    }

    public function test_density_is_calculated_when_page_content_exists(): void
    {
        Http::fake([
            'https://example.com/*' => Http::response('<html><body>Apple SEO tools help teams compare apple seo tools and buy better apple seo tools today.</body></html>'),
        ]);

        $service = $this->makeService();

        $densityContext = $service->exposedBuildDensityContext([
            'input_type' => 'page',
            'page_url' => 'https://example.com/page',
        ]);

        $analysis = $service->exposedAnalyzeKeywordDensity('apple seo tools', $densityContext);

        $this->assertSame('completed', $analysis['density_status']);
        $this->assertGreaterThan(0, $analysis['keyword_density_pct']);
        $this->assertGreaterThan(0, $analysis['density_exact_match_count']);
    }

    public function test_metrics_are_marked_not_configured_when_provider_is_unavailable(): void
    {
        $manager = Mockery::mock(KeywordMetricsProviderManager::class);
        $manager->shouldReceive('fetch')
            ->once()
            ->andReturn([
                'provider' => 'none',
                'status' => 'not_configured',
                'error' => 'Keyword metrics provider is not configured.',
                'items' => [],
            ]);

        $service = $this->makeService($manager);

        $rows = $service->exposedEnrichItemsWithRealMetrics([
            [
                'keyword' => 'apple seo tools',
                'normalized_keyword' => 'apple seo tools',
                'source' => 'ai_generated',
                'intent' => 'commercial',
                'funnel_stage' => 'mofu',
                'cluster_name' => 'Apple SEO',
                'recommended_content_type' => 'landing_page',
                'confidence_score' => 80,
                'business_relevance_score' => 85,
                'search_volume' => null,
                'keyword_traffic' => null,
                'metrics_status' => 'pending',
                'keyword_density_pct' => null,
                'density_status' => 'not_analyzed',
                'ai_reason' => 'Test row.',
                'generation_meta_json' => [],
                'is_saved' => false,
            ],
        ], [
            'input_type' => 'keyword',
            'input_text' => 'apple seo tools',
        ]);

        $this->assertSame('not_configured', $rows[0]['metrics_status']);
        $this->assertNull($rows[0]['search_volume']);
        $this->assertSame('none', $rows[0]['metrics_provider']);
    }

    protected function makeService(?KeywordMetricsProviderManager $manager = null): KeywordResearchService
    {
        $llmClient = Mockery::mock(LLMClient::class);
        $manager ??= Mockery::mock(KeywordMetricsProviderManager::class);

        return new class($llmClient, $manager) extends KeywordResearchService {
            public function exposedFallbackResponse(array $data): array
            {
                return $this->fallbackResponse($data);
            }

            public function exposedBuildDensityContext(array $data): array
            {
                return $this->buildDensityContext($data);
            }

            public function exposedAnalyzeKeywordDensity(string $keyword, array $context): array
            {
                return $this->analyzeKeywordDensity($keyword, $context);
            }

            public function exposedEnrichItemsWithRealMetrics(array $items, array $data): array
            {
                return $this->enrichItemsWithRealMetrics($items, $data);
            }
        };
    }
}
