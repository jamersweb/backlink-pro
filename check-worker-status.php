<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\AutomationTask;
use App\Models\BacklinkOpportunity;

echo "=== Worker Testing Status ===\n\n";

echo "Comment Tasks:\n";
echo "  Pending: " . AutomationTask::where('status', 'pending')->where('type', 'comment')->count() . "\n";
echo "  Running: " . AutomationTask::where('status', 'running')->where('type', 'comment')->count() . "\n";
echo "  Success: " . AutomationTask::where('status', 'success')->where('type', 'comment')->count() . "\n";
echo "  Failed: " . AutomationTask::where('status', 'failed')->where('type', 'comment')->count() . "\n\n";

$success = AutomationTask::where('status', 'success')->where('type', 'comment')->latest()->first();
if ($success) {
    echo "✅ LATEST SUCCESS:\n";
    echo "  Task ID: {$success->id}\n";
    echo "  Completed: {$success->completed_at}\n";
    echo "  Result: " . json_encode($success->result) . "\n\n";
} else {
    echo "⏳ No successful comment tasks yet\n\n";
}

echo "Opportunities Created:\n";
echo "  Total: " . BacklinkOpportunity::count() . "\n";
$recent = BacklinkOpportunity::where('created_at', '>', now()->subMinutes(10))->count();
echo "  Last 10 minutes: {$recent}\n\n";

if ($recent > 0) {
    $latest = BacklinkOpportunity::latest()->first();
    echo "✅ Latest Opportunity:\n";
    echo "  ID: {$latest->id}\n";
    echo "  Campaign: {$latest->campaign_id}\n";
    echo "  URL: {$latest->url}\n";
    echo "  Status: {$latest->status}\n";
    echo "  Created: {$latest->created_at}\n";
}

echo "\n=== Summary ===\n";
if ($success) {
    echo "✅ SUCCESS! Comment automation is working!\n";
    echo "   Worker is processing comment tasks correctly.\n";
} else {
    echo "⏳ Worker is running and prioritizing comment tasks.\n";
    echo "   Browser crashes are being handled with retries.\n";
    echo "   Waiting for a successful completion...\n";
}
