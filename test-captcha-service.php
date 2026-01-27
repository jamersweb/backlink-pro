<?php

/**
 * Test Captcha Solving Service
 * Tests captcha solving with various scenarios
 * 
 * Usage: php test-captcha-service.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\CaptchaSolvingService;

echo "========================================\n";
echo "CAPTCHA SOLVING SERVICE TESTING\n";
echo "========================================\n\n";

$captchaService = new CaptchaSolvingService();

// Check configuration
$reflection = new ReflectionClass($captchaService);
$providerProperty = $reflection->getProperty('provider');
$providerProperty->setAccessible(true);
$provider = $providerProperty->getValue($captchaService);

$apiKeyProperty = $reflection->getProperty('apiKey');
$apiKeyProperty->setAccessible(true);
$apiKey = $apiKeyProperty->getValue($captchaService);

echo "Captcha Service Status:\n";
echo "  Provider: " . ($provider ?: 'Not configured') . "\n";
echo "  API Key: " . ($apiKey ? substr($apiKey, 0, 10) . '...' : 'Not configured') . "\n\n";

if (!$provider || !$apiKey) {
    echo "⚠️  Captcha service is not configured. To configure:\n";
    echo "  1. Set 2CAPTCHA_API_KEY or ANTICAPTCHA_API_KEY in .env\n";
    echo "  2. Configure provider in admin settings\n\n";
    echo "Example .env:\n";
    echo "  2CAPTCHA_API_KEY=your-api-key-here\n";
    echo "  # OR\n";
    echo "  ANTICAPTCHA_API_KEY=your-api-key-here\n\n";
    exit(1);
}

echo "Testing captcha solving...\n\n";

// Test 1: Solve reCAPTCHA v2
echo "Test 1: Solve reCAPTCHA v2\n";
echo "  Note: This will make an actual API call and may incur costs\n";
echo "  Using test site key (will likely fail but tests the integration)\n\n";

try {
    $result = $captchaService->solve(
        'recaptcha_v2',
        [
            'site_key' => '6Le-wvkSAAAAAPBMRTvw0Q4Muexq9bi0DJwx_mJ-',
            'page_url' => 'https://www.google.com/recaptcha/api2/demo',
        ],
        null // campaign_id
    );
    
    if ($result && isset($result['solution'])) {
        echo "  ✅ Success: Captcha solved\n";
        echo "  Solution: " . substr($result['solution'], 0, 50) . "...\n";
        if (isset($result['cost'])) {
            echo "  Cost: $" . $result['cost'] . "\n";
        }
        echo "\n";
    } else {
        echo "  ❌ Failed: No solution returned\n";
        if (isset($result['error'])) {
            echo "  Error: " . $result['error'] . "\n";
        }
        echo "\n";
    }
} catch (Exception $e) {
    echo "  ❌ Error: " . $e->getMessage() . "\n\n";
}

echo "========================================\n";
echo "TEST COMPLETE\n";
echo "========================================\n";
echo "\nNote: Actual captcha solving requires valid API keys and will incur costs.\n";
echo "This test verifies the integration is working correctly.\n\n";


