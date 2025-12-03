<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\LLMContentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

class LLMContentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_llm_service_initializes()
    {
        $service = new LLMContentService();
        $this->assertInstanceOf(LLMContentService::class, $service);
    }

    public function test_generate_comment_without_api_key()
    {
        config(['services.llm.api_key' => null]);
        
        $service = new LLMContentService();
        $result = $service->generateComment('Test Article', 'Excerpt', 'https://example.com');
        
        $this->assertNull($result);
    }

    public function test_generate_anchor_text_variations_fallback()
    {
        config(['services.llm.api_key' => null]);
        
        $service = new LLMContentService();
        $variations = $service->generateAnchorTextVariations('test keyword', 'context', 5);
        
        $this->assertIsArray($variations);
        $this->assertCount(5, $variations);
        $this->assertContains('test keyword', $variations);
    }
}
