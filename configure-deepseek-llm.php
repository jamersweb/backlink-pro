<?php

/**
 * Configure DeepSeek LLM Settings
 * Sets up DeepSeek as the LLM provider in the settings table
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Setting;

echo "========================================\n";
echo "DEEPSEEK LLM CONFIGURATION\n";
echo "========================================\n\n";

// Get current settings
$currentProvider = Setting::get('llm_provider', 'not set');
$currentKey = Setting::get('llm_deepseek_api_key', 'not set');
$currentModel = Setting::get('llm_model', 'not set');
$currentEnabled = Setting::get('llm_enabled', 'not set');

echo "Current Configuration:\n";
echo "  Provider: {$currentProvider}\n";
echo "  Model: {$currentModel}\n";
echo "  Enabled: {$currentEnabled}\n";
echo "  API Key: " . (strlen($currentKey) > 10 ? substr($currentKey, 0, 15) . '...' : 'not set') . "\n\n";

// Configure DeepSeek
echo "Configuring DeepSeek...\n";

Setting::set('llm_provider', 'deepseek', 'llm', 'string', false);
Setting::set('llm_model', 'deepseek-chat', 'llm', 'string', false);
Setting::set('llm_enabled', true, 'llm', 'boolean', false);

// API key is already set, just verify
if ($currentKey && $currentKey !== 'not set' && strlen($currentKey) > 10) {
    echo "  ✅ API Key already configured\n";
} else {
    echo "  ⚠️  API Key needs to be set\n";
    echo "  Use: Setting::set('llm_deepseek_api_key', 'your-key', 'llm', 'string', true);\n";
}

echo "\n✅ DeepSeek configuration updated!\n";
echo "\nNext steps:\n";
echo "1. Ensure your DeepSeek account has sufficient balance\n";
echo "2. Test with: php test-llm-service.php\n";
echo "3. Check logs: storage/logs/laravel.log\n\n";

// Test the configuration
echo "Testing configuration...\n";
$testProvider = Setting::get('llm_provider', 'not set');
$testModel = Setting::get('llm_model', 'not set');
$testKey = Setting::get('llm_deepseek_api_key', 'not set');

if ($testProvider === 'deepseek' && $testModel === 'deepseek-chat' && strlen($testKey) > 10) {
    echo "✅ Configuration verified!\n\n";
    echo "Status: READY (but requires account balance)\n";
} else {
    echo "⚠️  Configuration incomplete\n\n";
}


