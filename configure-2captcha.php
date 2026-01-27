<?php

/**
 * Configure 2Captcha API Key
 * Adds the API key to .env file
 */

$apiKey = '0d5b93beb8635ba7662f0576ad832cab';
$envFile = __DIR__ . '/.env';

if (!file_exists($envFile)) {
    die("ERROR: .env file not found at: $envFile\n");
}

// Read current .env file
$envContent = file_get_contents($envFile);

// Check if 2CAPTCHA_API_KEY already exists
if (preg_match('/^2CAPTCHA_API_KEY=(.*)$/m', $envContent, $matches)) {
    echo "2CAPTCHA_API_KEY already exists. Updating...\n";
    $envContent = preg_replace('/^2CAPTCHA_API_KEY=.*$/m', "2CAPTCHA_API_KEY={$apiKey}", $envContent);
} else {
    // Add it after the captcha section or at the end
    if (preg_match('/CAPTCHA_PROVIDER=/i', $envContent)) {
        // Add after CAPTCHA_PROVIDER line
        $envContent = preg_replace(
            '/(CAPTCHA_PROVIDER=.*)/i',
            "$1\n2CAPTCHA_API_KEY={$apiKey}",
            $envContent
        );
    } else {
        // Add at the end
        $envContent .= "\n# 2Captcha API Configuration\n2CAPTCHA_API_KEY={$apiKey}\n";
    }
}

// Write back to .env
file_put_contents($envFile, $envContent);

echo "✅ 2Captcha API key configured successfully!\n";
echo "API Key: " . substr($apiKey, 0, 10) . "...\n\n";
echo "Please run: php artisan config:clear\n";
echo "Then test with: php test-captcha-service.php\n";


