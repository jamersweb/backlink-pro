<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Campaign;
use App\Models\AutomationTask;
use App\Models\Plan;

echo "=== Creating Tasks for Existing Campaigns ===\n\n";

// Get all active campaigns without tasks
$campaigns = Campaign::where('status', Campaign::STATUS_ACTIVE)
    ->whereDoesntHave('automationTasks')
    ->with('user.plan')
    ->get();

if ($campaigns->isEmpty()) {
    echo "No active campaigns without tasks found.\n";
    exit(0);
}

echo "Found {$campaigns->count()} campaign(s) without tasks:\n\n";

foreach ($campaigns as $campaign) {
    $user = $campaign->user;
    $plan = $user ? $user->plan : null;
    
    if (!$plan) {
        echo "⚠ Campaign ID {$campaign->id} ({$campaign->name}): User has no plan, skipping...\n";
        continue;
    }
    
    // Get backlink types from plan
    $backlinkTypes = $plan->backlink_types ?? ['comment', 'profile'];
    
    if (empty($backlinkTypes)) {
        echo "⚠ Campaign ID {$campaign->id} ({$campaign->name}): Plan has no backlink types, skipping...\n";
        continue;
    }
    
    // Calculate initial tasks per type
    $dailyLimit = $plan->daily_backlink_limit ?? 10;
    $tasksPerType = max(1, floor($dailyLimit / count($backlinkTypes)));
    
    // Handle keywords
    $keywords = $campaign->web_keyword ?? '';
    if (is_string($keywords)) {
        $keywords = !empty($keywords) ? explode(',', $keywords) : [];
        $keywords = array_map('trim', $keywords);
        $keywords = array_filter($keywords);
    }
    if (empty($keywords)) {
        $keywords = [$campaign->web_name ?? 'SEO'];
    }
    
    $settings = $campaign->settings ?? [];
    if (!is_array($settings)) {
        $settings = json_decode($settings, true) ?? [];
    }
    
    $totalCreated = 0;
    
    // Create tasks for each backlink type
    foreach ($backlinkTypes as $type) {
        if (!$plan->allowsBacklinkType($type)) {
            continue;
        }
        
        // Map 'guestposting' to 'guest' (task type enum uses 'guest')
        $taskType = $type === 'guestposting' ? 'guest' : $type;
        
        for ($i = 0; $i < $tasksPerType; $i++) {
            AutomationTask::create([
                'campaign_id' => $campaign->id,
                'type' => $taskType,
                'status' => AutomationTask::STATUS_PENDING,
                'payload' => [
                    'campaign_id' => $campaign->id,
                    'keywords' => $keywords,
                    'anchor_text_strategy' => $settings['anchor_text_strategy'] ?? 'variation',
                    'content_tone' => $settings['content_tone'] ?? 'professional',
                ],
                'max_retries' => 3,
                'retry_count' => 0,
            ]);
            $totalCreated++;
        }
    }
    
    echo "✓ Campaign ID {$campaign->id} ({$campaign->name}): Created {$totalCreated} task(s)\n";
}

echo "\n✅ Done!\n";

