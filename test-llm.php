<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\LLMContentService;

$service = new LLMContentService();
$content = $service->generateComment('Test title', 'Test excerpt', 'https://example.com', 'professional');

var_dump($content);

