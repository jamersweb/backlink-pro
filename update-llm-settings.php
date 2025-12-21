<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Setting;

Setting::updateOrCreate(['key' => 'llm_provider'], ['value' => 'deepseek']);
Setting::updateOrCreate(['key' => 'llm_enabled'], ['value' => '1']);

echo "Updated llm_provider to deepseek and llm_enabled to 1" . PHP_EOL;

