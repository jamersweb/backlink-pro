<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class LLMContentService
{
    protected $apiKey;
    protected $provider; // 'openai', 'deepseek', or 'anthropic'
    protected $baseUrl;
    protected $model;
    protected $enabled;

    public function __construct()
    {
        // Read from Settings table (set via Admin Settings UI)
        $this->provider = Setting::get('llm_provider', 'deepseek');
        $this->model = Setting::get('llm_model', 'deepseek-chat');
        $this->enabled = Setting::get('llm_enabled', true);

        // Get API key based on provider
        if ($this->provider === 'deepseek') {
            $this->apiKey = Setting::get('llm_deepseek_api_key', '');
            $this->baseUrl = config('services.llm.deepseek_url', 'https://api.deepseek.com/v1');
        } elseif ($this->provider === 'anthropic') {
            $this->apiKey = Setting::get('llm_anthropic_api_key', '');
            $this->baseUrl = config('services.llm.anthropic_url', 'https://api.anthropic.com/v1');
        } else {
            // Default to OpenAI
            $this->apiKey = Setting::get('llm_openai_api_key', '');
            $this->baseUrl = config('services.llm.openai_url', 'https://api.openai.com/v1');
        }

        // Fallback to config if Settings table doesn't have values (for backward compatibility)
        if (!$this->apiKey) {
            $this->apiKey = config('services.llm.api_key', '');
            if (!$this->provider || $this->provider === 'openai') {
                $this->provider = config('services.llm.provider', 'openai');
            }
        }
    }

    /**
     * Generate content using LLM
     */
    public function generate(string $prompt, array $options = []): ?string
    {
        if (!$this->enabled) {
            Log::info('LLM content generation is disabled');
            return null;
        }

        if (!$this->apiKey) {
            Log::warning('LLM API key not configured');
            return null;
        }

        // Use model from settings or options, with provider-specific defaults
        $model = $options['model'] ?? $this->model;
        if (!$model) {
            // Fallback defaults based on provider
            if ($this->provider === 'deepseek') {
                $model = 'deepseek-chat';
            } elseif ($this->provider === 'anthropic') {
                $model = 'claude-3-opus-20240229';
            } else {
                $model = 'gpt-3.5-turbo';
            }
        }

        $maxTokens = $options['max_tokens'] ?? 500;
        $temperature = $options['temperature'] ?? 0.7;

        try {
            // Anthropic API uses a different format
            if ($this->provider === 'anthropic') {
                $response = Http::timeout(60)
                    ->withHeaders([
                        'x-api-key' => $this->apiKey,
                        'anthropic-version' => '2023-06-01',
                        'Content-Type' => 'application/json',
                    ])
                    ->post("{$this->baseUrl}/messages", [
                        'model' => $model,
                        'max_tokens' => $maxTokens,
                        'temperature' => $temperature,
                        'messages' => [
                            ['role' => 'user', 'content' => $prompt],
                        ],
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    // Anthropic returns content in a different structure
                    if (isset($data['content']) && is_array($data['content']) && count($data['content']) > 0) {
                        return $data['content'][0]['text'] ?? null;
                    }
                    return null;
                }
            } else {
                // OpenAI and DeepSeek use the same format
                $response = Http::timeout(60)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Content-Type' => 'application/json',
                    ])
                    ->post("{$this->baseUrl}/chat/completions", [
                        'model' => $model,
                        'messages' => [
                            ['role' => 'user', 'content' => $prompt],
                        ],
                        'max_tokens' => $maxTokens,
                        'temperature' => $temperature,
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    return $data['choices'][0]['message']['content'] ?? null;
                }
            }

            // If we get here, the request failed
            Log::error('LLM API request failed', [
                'status' => $response->status(),
                'response' => $response->body(),
                'provider' => $this->provider,
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error('LLM content generation failed', [
                'error' => $e->getMessage(),
                'provider' => $this->provider,
            ]);
            return null;
        }
    }

    /**
     * Generate comment content
     */
    public function generateComment(string $articleTitle, string $articleExcerpt, string $targetUrl, string $tone = 'professional'): ?string
    {
        $prompt = "Write a thoughtful, relevant comment for a blog post.\n\n";
        $prompt .= "Article Title: {$articleTitle}\n";
        $prompt .= "Article Excerpt: {$articleExcerpt}\n";
        $prompt .= "Target Website: {$targetUrl}\n";
        $prompt .= "Tone: {$tone}\n\n";
        $prompt .= "Requirements:\n";
        $prompt .= "- 2-4 sentences\n";
        $prompt .= "- Add value to the discussion\n";
        $prompt .= "- Natural and conversational\n";
        $prompt .= "- No promotional language\n";
        $prompt .= "- Relevant to the article content\n\n";
        $prompt .= "Comment:";

        return $this->generate($prompt, ['max_tokens' => 200, 'temperature' => 0.8]);
    }

    /**
     * Generate forum post content
     */
    public function generateForumPost(string $topic, string $targetUrl, string $tone = 'professional'): ?string
    {
        $prompt = "Write a forum post about: {$topic}\n\n";
        $prompt .= "Target Website: {$targetUrl}\n";
        $prompt .= "Tone: {$tone}\n\n";
        $prompt .= "Requirements:\n";
        $prompt .= "- 3-5 sentences\n";
        $prompt .= "- Engaging and discussion-worthy\n";
        $prompt .= "- Natural forum language\n";
        $prompt .= "- Ask a question or share insight\n\n";
        $prompt .= "Forum Post:";

        return $this->generate($prompt, ['max_tokens' => 300, 'temperature' => 0.8]);
    }

    /**
     * Generate profile bio
     */
    public function generateBio(string $companyName, string $companyDescription, string $tone = 'professional'): ?string
    {
        $prompt = "Write a professional bio/profile description.\n\n";
        $prompt .= "Company Name: {$companyName}\n";
        $prompt .= "Company Description: {$companyDescription}\n";
        $prompt .= "Tone: {$tone}\n\n";
        $prompt .= "Requirements:\n";
        $prompt .= "- 2-3 sentences\n";
        $prompt .= "- Professional but personable\n";
        $prompt .= "- Highlight expertise\n";
        $prompt .= "- No promotional language\n\n";
        $prompt .= "Bio:";

        return $this->generate($prompt, ['max_tokens' => 150, 'temperature' => 0.7]);
    }

    /**
     * Generate guest post pitch
     */
    public function generateGuestPostPitch(string $blogName, string $targetUrl, string $proposedTopic, string $tone = 'professional'): ?string
    {
        $prompt = "Write a guest post pitch email.\n\n";
        $prompt .= "Blog Name: {$blogName}\n";
        $prompt .= "Target Website: {$targetUrl}\n";
        $prompt .= "Proposed Topic: {$proposedTopic}\n";
        $prompt .= "Tone: {$tone}\n\n";
        $prompt .= "Requirements:\n";
        $prompt .= "- Professional email format\n";
        $prompt .= "- Brief introduction\n";
        $prompt .= "- Explain why the topic fits their blog\n";
        $prompt .= "- Highlight your expertise\n";
        $prompt .= "- Call to action\n";
        $prompt .= "- 4-6 sentences\n\n";
        $prompt .= "Email Pitch:";

        return $this->generate($prompt, ['max_tokens' => 400, 'temperature' => 0.7]);
    }

    /**
     * Generate anchor text variations
     */
    public function generateAnchorTextVariations(string $keyword, string $context, int $count = 5): array
    {
        $prompt = "Generate {$count} natural anchor text variations for the keyword: '{$keyword}'\n\n";
        $prompt .= "Context: {$context}\n\n";
        $prompt .= "Requirements:\n";
        $prompt .= "- Natural and varied\n";
        $prompt .= "- Include the keyword\n";
        $prompt .= "- Each variation should be different\n";
        $prompt .= "- Return only the variations, one per line\n\n";
        $prompt .= "Anchor Text Variations:";

        $result = $this->generate($prompt, ['max_tokens' => 200, 'temperature' => 0.9]);

        if ($result) {
            $variations = array_filter(array_map('trim', explode("\n", $result)));
            return array_slice($variations, 0, $count);
        }

        // Fallback: simple variations
        return [
            $keyword,
            "Learn more about {$keyword}",
            "{$keyword} information",
            "Discover {$keyword}",
            "{$keyword} guide",
        ];
    }
}

