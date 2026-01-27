<?php

/**
 * Configure OpenAI as Backup LLM Provider
 * Sets up OpenAI API key and configuration
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Setting;

echo "========================================\n";
echo "OPENAI BACKUP LLM CONFIGURATION\n";
echo "========================================\n\n";

// Check if API key is provided as argument
$apiKey = $argv[1] ?? null;

if (!$apiKey) {
    echo "Usage: php configure-openai-backup.php YOUR_OPENAI_API_KEY\n\n";
    echo "Example: php configure-openai-backup.php sk-proj-...\n\n";
    echo "Current OpenAI Configuration:\n";
    $currentKey = Setting::get('llm_openai_api_key', 'not set');
    echo "  API Key: " . ($currentKey !== 'not set' && strlen($currentKey) > 10 ? substr($currentKey, 0, 15) . '...' : 'not set') . "\n\n";
    exit(1);
}

echo "Configuring OpenAI as backup provider...\n\n";

// Configure OpenAI API key (encrypted for security)
Setting::set('llm_openai_api_key', $apiKey, 'llm', 'string', true);

// Also set default model for OpenAI
Setting::set('llm_openai_model', 'gpt-3.5-turbo', 'llm', 'string', false);

echo "✅ OpenAI API key configured!\n";
echo "✅ Default model set: gpt-3.5-turbo\n\n";

// Verify configuration
$verifiedKey = Setting::get('llm_openai_api_key', 'not set');
$verifiedModel = Setting::get('llm_openai_model', 'not set');
$currentProvider = Setting::get('llm_provider', 'not set');

echo "Configuration Summary:\n";
echo "  Current Provider: {$currentProvider}\n";
echo "  OpenAI API Key: " . (strlen($verifiedKey) > 10 ? substr($verifiedKey, 0, 15) . '... (configured)' : 'not set') . "\n";
echo "  OpenAI Model: {$verifiedModel}\n";
echo "  DeepSeek API Key: " . (strlen(Setting::get('llm_deepseek_api_key', '')) > 10 ? 'configured' : 'not set') . "\n\n";

echo "✅ OpenAI is now configured as backup provider!\n\n";
echo "How it works:\n";
echo "  - Primary: DeepSeek (current provider)\n";
echo "  - Backup: OpenAI (configured and ready)\n";
echo "  - Switch providers via admin settings: /admin/settings\n\n";
echo "To test OpenAI:\n";
echo "  1. Change provider to 'openai' in admin settings\n";
echo "  2. Or update: Setting::set('llm_provider', 'openai', 'llm')\n";
echo "  3. Test: php test-llm-service.php\n\n";


