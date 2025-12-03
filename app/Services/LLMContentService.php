<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class LLMContentService
{
    protected $apiKey;
    protected $provider; // 'openai' or 'deepseek'
    protected $baseUrl;

    public function __construct()
    {
        $this->provider = config('services.llm.provider', 'openai');
        $this->apiKey = config('services.llm.api_key');
        
        if ($this->provider === 'deepseek') {
            $this->baseUrl = config('services.llm.deepseek_url', 'https://api.deepseek.com/v1');
        } else {
            $this->baseUrl = config('services.llm.openai_url', 'https://api.openai.com/v1');
        }
    }

    /**
     * Generate content using LLM
     */
    public function generate(string $prompt, array $options = []): ?string
    {
        if (!$this->apiKey) {
            Log::warning('LLM API key not configured');
            return null;
        }

        $model = $options['model'] ?? ($this->provider === 'deepseek' ? 'deepseek-chat' : 'gpt-3.5-turbo');
        $maxTokens = $options['max_tokens'] ?? 500;
        $temperature = $options['temperature'] ?? 0.7;

        try {
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
            } else {
                Log::error('LLM API request failed', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
                return null;
            }
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

