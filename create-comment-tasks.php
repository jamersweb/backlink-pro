<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\AutomationTask;
use App\Models\Campaign;

$campaignId = 1;
$count = 5;
$keywords = ['technology', 'software', 'innovation'];
$tone = 'professional';
$anchorStrategy = 'variation';

$campaign = Campaign::find($campaignId);
if (!$campaign) {
    echo "Campaign {$campaignId} not found\n";
    exit(1);
}

for ($i = 0; $i < $count; $i++) {
    $task = AutomationTask::create([
        'campaign_id' => $campaignId,
        'type' => 'comment',
        'status' => 'pending',
        'payload' => [
            'campaign_id' => $campaignId,
            'keywords' => $keywords,
            'anchor_text_strategy' => $anchorStrategy,
            'content_tone' => $tone,
        ],
        'retry_count' => 0,
        'max_retries' => 3,
    ]);
    echo "Created comment task ID {$task->id}\n";
}

