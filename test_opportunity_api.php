<?php

/**
 * Quick test script for opportunity selection API
 * Run: php test_opportunity_api.php
 */

require __DIR__ . '/vendor/autoload.php';

use App\Models\Campaign;
use App\Models\BacklinkOpportunity;
use App\Models\Category;
use App\Models\User;
use App\Models\Plan;
use App\Models\Backlink;
use Illuminate\Support\Facades\DB;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=" . str_repeat("=", 60) . "\n";
echo "Testing Opportunity Selection Algorithm\n";
echo "=" . str_repeat("=", 60) . "\n\n";

// Step 1: Check if we have a campaign
echo "[Step 1] Checking Campaign...\n";
$campaign = Campaign::with(['user.plan', 'category', 'subcategory'])->first();

if (!$campaign) {
    echo "✗ No campaign found. Please create a campaign first.\n";
    exit(1);
}

echo "✓ Campaign found: ID {$campaign->id}\n";
echo "  Name: {$campaign->name}\n";
echo "  Category ID: " . ($campaign->category_id ?? 'NULL') . "\n";
echo "  Subcategory ID: " . ($campaign->subcategory_id ?? 'NULL') . "\n";

if (!$campaign->category_id && !$campaign->subcategory_id) {
    echo "⚠ Campaign has no category/subcategory. Assigning automatically...\n";
    
    // Get first main category
    $category = Category::whereNull('parent_id')->first();
    $subcategory = Category::where('parent_id', $category->id)->first();
    
    if ($category && $subcategory) {
        $campaign->update([
            'category_id' => $category->id,
            'subcategory_id' => $subcategory->id,
        ]);
        echo "✓ Assigned category: {$category->name} / {$subcategory->name}\n";
        
        // Reload campaign
        $campaign = Campaign::with(['user.plan', 'category', 'subcategory'])->find($campaign->id);
    } else {
        echo "✗ No categories available. Please run CategorySeeder first.\n";
        exit(1);
    }
}

// Step 2: Check plan
echo "\n[Step 2] Checking Plan...\n";
$plan = $campaign->user->plan;

if (!$plan) {
    echo "✗ User has no plan assigned.\n";
    exit(1);
}

echo "✓ Plan found: {$plan->name}\n";
echo "  PA Range: {$plan->min_pa} - {$plan->max_pa}\n";
echo "  DA Range: {$plan->min_da} - {$plan->max_da}\n";

// Step 3: Check opportunities
echo "\n[Step 3] Checking Opportunities...\n";
$categoryIds = array_filter([$campaign->category_id, $campaign->subcategory_id]);

$opportunitiesCount = BacklinkOpportunity::where('status', 'active')
    ->whereBetween('pa', [$plan->min_pa ?? 0, $plan->max_pa ?? 100])
    ->whereBetween('da', [$plan->min_da ?? 0, $plan->max_da ?? 100])
    ->whereHas('categories', function($q) use ($categoryIds) {
        $q->whereIn('categories.id', $categoryIds);
    })
    ->count();

echo "✓ Found {$opportunitiesCount} opportunities matching criteria\n";

if ($opportunitiesCount == 0) {
    echo "\n⚠ No opportunities found. Creating test opportunities...\n";
    
    // Get a category
    $category = Category::whereIn('id', $categoryIds)->first();
    
    if ($category) {
        // Create a few test opportunities
        for ($i = 1; $i <= 5; $i++) {
            $opp = BacklinkOpportunity::create([
                'url' => "https://test-site-{$i}.example.com",
                'pa' => rand($plan->min_pa ?? 0, min(($plan->max_pa ?? 50), 50)),
                'da' => rand($plan->min_da ?? 0, min(($plan->max_da ?? 60), 60)),
                'site_type' => 'comment',
                'status' => 'active',
                'daily_site_limit' => 5,
            ]);
            
            $opp->categories()->attach($category->id);
            echo "  Created opportunity: {$opp->url} (PA: {$opp->pa}, DA: {$opp->da})\n";
        }
        
        $opportunitiesCount = 5;
    }
}

// Step 4: Test API endpoint
echo "\n[Step 4] Testing API Endpoint...\n";
$controller = new \App\Http\Controllers\Api\OpportunityController();
$request = new \Illuminate\Http\Request();
$request->merge(['count' => 3, 'task_type' => 'comment']);

try {
    $response = $controller->getForCampaign($request, $campaign->id);
    $data = json_decode($response->getContent(), true);
    
    if ($data['success']) {
        echo "✓ API endpoint working correctly\n";
        echo "  Returned " . count($data['opportunities']) . " opportunities\n";
        
        if (count($data['opportunities']) > 0) {
            echo "\n  Sample opportunities:\n";
            foreach (array_slice($data['opportunities'], 0, 3) as $opp) {
                echo "    - {$opp['url']} (PA: {$opp['pa']}, DA: {$opp['da']})\n";
            }
        }
        
        echo "\n  Plan Limits Applied:\n";
        echo "    PA: {$data['plan_limits']['min_pa']} - {$data['plan_limits']['max_pa']}\n";
        echo "    DA: {$data['plan_limits']['min_da']} - {$data['plan_limits']['max_da']}\n";
    } else {
        echo "✗ API returned error: {$data['error']}\n";
    }
} catch (\Exception $e) {
    echo "✗ Error testing API: {$e->getMessage()}\n";
    echo "  " . $e->getTraceAsString() . "\n";
}

// Step 5: Test daily limits
echo "\n[Step 5] Testing Daily Limits...\n";
$today = \Carbon\Carbon::today();
$campaignTodayCount = Backlink::where('campaign_id', $campaign->id)
    ->whereDate('created_at', $today)
    ->count();

echo "  Campaign backlinks today: {$campaignTodayCount}\n";
if ($campaign->daily_limit) {
    echo "  Campaign daily limit: {$campaign->daily_limit}\n";
    if ($campaignTodayCount >= $campaign->daily_limit) {
        echo "  ⚠ Campaign daily limit reached!\n";
    }
}

echo "\n" . str_repeat("=", 62) . "\n";
echo "Test Complete!\n";
echo str_repeat("=", 62) . "\n";

