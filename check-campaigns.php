<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Campaign;
use App\Models\User;

echo "=== Campaigns Check ===\n\n";

$totalCampaigns = Campaign::count();
echo "Total Campaigns: {$totalCampaigns}\n\n";

if ($totalCampaigns > 0) {
    echo "Recent Campaigns:\n";
    echo str_repeat("-", 80) . "\n";
    
    Campaign::with('user:id,name,email')
        ->latest()
        ->take(10)
        ->get(['id', 'name', 'user_id', 'status', 'web_url', 'created_at'])
        ->each(function($campaign) {
            echo sprintf(
                "ID: %-5s | Name: %-30s | User: %-25s | Status: %-10s | URL: %s\n",
                $campaign->id,
                substr($campaign->name ?? 'N/A', 0, 30),
                $campaign->user ? substr($campaign->user->email, 0, 25) : 'N/A',
                $campaign->status ?? 'N/A',
                substr($campaign->web_url ?? 'N/A', 0, 30)
            );
        });
    
    echo "\n";
    
    // Check user with campaigns
    $userWithCampaigns = User::whereHas('campaigns')->withCount('campaigns')->first();
    if ($userWithCampaigns) {
        echo "User with campaigns: {$userWithCampaigns->email} ({$userWithCampaigns->campaigns_count} campaigns)\n";
        echo "Plan: " . ($userWithCampaigns->plan ? $userWithCampaigns->plan->name : 'No plan') . "\n";
        echo "Subscription Status: " . ($userWithCampaigns->subscription_status ?? 'N/A') . "\n";
    }
} else {
    echo "No campaigns found. Run the seeder:\n";
    echo "php artisan db:seed --class=UserWithCampaignsSeeder\n";
}

