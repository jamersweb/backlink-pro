<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Setting;

echo "provider=" . Setting::get('llm_provider', '') . PHP_EOL;
echo "enabled=" . (Setting::get('llm_enabled', true) ? '1' : '0') . PHP_EOL;
echo "deepseek_key=" . (Setting::get('llm_deepseek_api_key', '') ? 'set' : 'empty') . PHP_EOL;
echo "openai_key=" . (Setting::get('llm_openai_api_key', '') ? 'set' : 'empty') . PHP_EOL;

