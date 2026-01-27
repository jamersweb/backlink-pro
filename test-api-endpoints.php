<?php

/**
 * Comprehensive API Endpoint Testing Script
 * Tests all Python worker API endpoints
 * 
 * Usage: php test-api-endpoints.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

$baseUrl = 'http://127.0.0.1:8000';
$apiToken = Config::get('app.api_token') ?: env('APP_API_TOKEN', 'test-token');

if (!$apiToken || $apiToken === 'test-token') {
    echo "⚠️  WARNING: API token not configured. Using 'test-token'. Set APP_API_TOKEN in .env\n\n";
}

$headers = [
    'X-API-Token' => $apiToken,
    'Accept' => 'application/json',
    'Content-Type' => 'application/json',
];

$results = [];

function testEndpoint($name, $method, $url, $headers, $data = null) {
    global $baseUrl, $results;
    
    $fullUrl = $baseUrl . $url;
    echo "Testing: $name\n";
    echo "  Method: $method\n";
    echo "  URL: $url\n";
    
    try {
        $response = Http::withHeaders($headers)->{strtolower($method)}($fullUrl, $data);
        $statusCode = $response->status();
        $body = $response->body();
        
        $result = [
            'name' => $name,
            'method' => $method,
            'url' => $url,
            'status_code' => $statusCode,
            'success' => $statusCode >= 200 && $statusCode < 300,
            'response' => json_decode($body, true) ?: $body,
        ];
        
        $results[] = $result;
        
        if ($result['success']) {
            echo "  ✅ Status: $statusCode\n";
        } else {
            echo "  ❌ Status: $statusCode\n";
        }
        
        if (is_array($result['response']) && isset($result['response']['error'])) {
            echo "  Error: " . $result['response']['error'] . "\n";
        }
        
        echo "\n";
        
        return $result;
        
    } catch (Exception $e) {
        echo "  ❌ Exception: " . $e->getMessage() . "\n\n";
        $results[] = [
            'name' => $name,
            'method' => $method,
            'url' => $url,
            'success' => false,
            'error' => $e->getMessage(),
        ];
        return null;
    }
}

echo "========================================\n";
echo "API ENDPOINT TESTING\n";
echo "========================================\n\n";
echo "Base URL: $baseUrl\n";
echo "API Token: " . substr($apiToken, 0, 10) . "...\n\n";

// Test 1: Get Pending Tasks
testEndpoint(
    'Get Pending Tasks',
    'GET',
    '/api/tasks/pending?limit=10',
    $headers
);

// Test 2: Get Opportunities (requires campaign_id - skip if no campaigns)
try {
    $campaigns = \App\Models\Campaign::limit(1)->get();
    if ($campaigns->count() > 0) {
        $campaignId = $campaigns->first()->id;
        testEndpoint(
            'Get Opportunities for Campaign',
            'GET',
            "/api/opportunities/for-campaign/$campaignId",
            $headers
        );
    } else {
        echo "⚠️  Skipping opportunities test: No campaigns found\n\n";
    }
} catch (Exception $e) {
    echo "⚠️  Skipping opportunities test: " . $e->getMessage() . "\n\n";
}

// Test 3: Get Proxies
testEndpoint(
    'Get Proxies',
    'GET',
    '/api/proxies',
    $headers
);

// Test 4: Get Campaigns
testEndpoint(
    'Get Campaigns',
    'GET',
    '/api/campaigns',
    $headers
);

// Test 5: Generate LLM Content (requires API key)
testEndpoint(
    'Generate LLM Content (Comment)',
    'POST',
    '/api/llm/generate',
    $headers,
    [
        'type' => 'comment',
        'data' => [
            'article_title' => 'Test Article',
            'article_excerpt' => 'This is a test article excerpt',
            'target_url' => 'https://example.com',
            'tone' => 'professional',
        ],
    ]
);

// Test 6: Solve Captcha (requires captcha API key)
testEndpoint(
    'Solve Captcha',
    'POST',
    '/api/captcha/solve',
    $headers,
    [
        'captcha_type' => 'recaptcha_v2',
        'data' => [
            'site_key' => '6Le-wvkSAAAAAPBMRTvw0Q4Muexq9bi0DJwx_mJ-',
            'page_url' => 'https://example.com',
        ],
    ]
);

// Summary
echo "========================================\n";
echo "TEST SUMMARY\n";
echo "========================================\n\n";

$passed = count(array_filter($results, fn($r) => $r['success'] ?? false));
$failed = count($results) - $passed;

echo "Total Tests: " . count($results) . "\n";
echo "Passed: $passed\n";
echo "Failed: $failed\n\n";

if ($failed > 0) {
    echo "Failed Tests:\n";
    foreach ($results as $result) {
        if (!($result['success'] ?? false)) {
            echo "  ❌ {$result['name']}\n";
            if (isset($result['error'])) {
                echo "     Error: {$result['error']}\n";
            } elseif (isset($result['status_code'])) {
                echo "     Status: {$result['status_code']}\n";
            }
        }
    }
}

echo "\n";


