<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Campaign;

echo "=== Campaign Tasks Check ===\n\n";

Campaign::withCount('automationTasks')
    ->get(['id', 'name', 'status'])
    ->each(function($campaign) {
        echo sprintf(
            "Campaign %d: %s (%s) - %d tasks\n",
            $campaign->id,
            $campaign->name,
            $campaign->status,
            $campaign->automation_tasks_count
        );
    });

$totalTasks = \App\Models\AutomationTask::count();
echo "\nTotal Tasks: {$totalTasks}\n";

