<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\AutomationTask;

$tasksToCreate = 5;
$created = 0;

for ($i = 0; $i < $tasksToCreate; $i++) {
    AutomationTask::create([
        'campaign_id' => 1,
        'type' => 'comment',
        'status' => 'pending',
        'payload' => [
            'keywords' => ['automation', 'test'],
            'anchor_text_strategy' => 'variation',
        ],
    ]);
    $created++;
}

echo "Created {$created} comment tasks for campaign 1\n";

