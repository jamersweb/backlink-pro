<?php

/**
 * Test LLM Content Service
 * Tests LLM content generation with various scenarios
 * 
 * Usage: php test-llm-service.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\LLMContentService;

echo "========================================\n";
echo "LLM CONTENT SERVICE TESTING\n";
echo "========================================\n\n";

$llmService = new LLMContentService();

// Check if LLM is enabled
$reflection = new ReflectionClass($llmService);
$enabledProperty = $reflection->getProperty('enabled');
$enabledProperty->setAccessible(true);
$enabled = $enabledProperty->getValue($llmService);

$apiKeyProperty = $reflection->getProperty('apiKey');
$apiKeyProperty->setAccessible(true);
$apiKey = $apiKeyProperty->getValue($llmService);

$providerProperty = $reflection->getProperty('provider');
$providerProperty->setAccessible(true);
$provider = $providerProperty->getValue($llmService);

echo "LLM Status:\n";
echo "  Enabled: " . ($enabled ? 'Yes' : 'No') . "\n";
echo "  Provider: " . ($provider ?: 'Not configured') . "\n";
echo "  API Key: " . ($apiKey ? substr($apiKey, 0, 10) . '...' : 'Not configured') . "\n\n";

if (!$enabled || !$apiKey) {
    echo "⚠️  LLM is not configured. To configure:\n";
    echo "  1. Set LLM API key in admin settings or .env\n";
    echo "  2. Configure provider (OpenAI, DeepSeek, or Anthropic)\n";
    echo "  3. Enable LLM in settings\n\n";
    echo "Example .env:\n";
    echo "  LLM_PROVIDER=deepseek\n";
    echo "  LLM_API_KEY=your-api-key-here\n\n";
    exit(1);
}

echo "Testing content generation...\n\n";

// Test 1: Generate Comment
echo "Test 1: Generate Comment\n";
try {
    $comment = $llmService->generateComment(
        'The Ultimate Guide to SEO in 2025',
        'This article covers all the latest SEO techniques and strategies...',
        'https://example.com',
        'professional'
    );
    if ($comment) {
        echo "  ✅ Success: Generated comment (" . strlen($comment) . " characters)\n";
        echo "  Preview: " . substr($comment, 0, 100) . "...\n\n";
    } else {
        echo "  ❌ Failed: No content generated\n\n";
    }
} catch (Exception $e) {
    echo "  ❌ Error: " . $e->getMessage() . "\n\n";
}

// Test 2: Generate Forum Post
echo "Test 2: Generate Forum Post\n";
try {
    $post = $llmService->generateForumPost(
        'Best practices for link building',
        'https://example.com',
        'professional'
    );
    if ($post) {
        echo "  ✅ Success: Generated forum post (" . strlen($post) . " characters)\n";
        echo "  Preview: " . substr($post, 0, 100) . "...\n\n";
    } else {
        echo "  ❌ Failed: No content generated\n\n";
    }
} catch (Exception $e) {
    echo "  ❌ Error: " . $e->getMessage() . "\n\n";
}

// Test 3: Generate Bio
echo "Test 3: Generate Bio\n";
try {
    $bio = $llmService->generateBio(
        'TechCorp',
        'A leading technology company specializing in AI solutions',
        'professional'
    );
    if ($bio) {
        echo "  ✅ Success: Generated bio (" . strlen($bio) . " characters)\n";
        echo "  Preview: " . substr($bio, 0, 100) . "...\n\n";
    } else {
        echo "  ❌ Failed: No content generated\n\n";
    }
} catch (Exception $e) {
    echo "  ❌ Error: " . $e->getMessage() . "\n\n";
}

echo "========================================\n";
echo "TEST COMPLETE\n";
echo "========================================\n";


