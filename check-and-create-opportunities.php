<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Campaign;
use App\Models\Backlink;
use App\Models\Category;

echo "=== Checking Campaign 1 ===\n";
$campaign = Campaign::with(['user.plan', 'category', 'subcategory'])->find(1);

if (!$campaign) {
    echo "✗ Campaign 1 not found\n";
    exit(1);
}

echo "✓ Campaign: {$campaign->name}\n";
echo "  Category ID: " . ($campaign->category_id ?? 'null') . "\n";
echo "  Subcategory ID: " . ($campaign->subcategory_id ?? 'null') . "\n";
echo "  User Plan: " . ($campaign->user->plan->name ?? 'null') . "\n";

if (!$campaign->user->plan) {
    echo "✗ User has no plan assigned\n";
    exit(1);
}

$plan = $campaign->user->plan;
$minPa = $plan->min_pa ?? 0;
$maxPa = $plan->max_pa ?? 100;
$minDa = $plan->min_da ?? 0;
$maxDa = $plan->max_da ?? 100;

echo "  PA Range: {$minPa}-{$maxPa}\n";
echo "  DA Range: {$minDa}-{$maxDa}\n";

// Get category IDs
$categoryIds = array_filter([$campaign->category_id, $campaign->subcategory_id]);

if (empty($categoryIds)) {
    echo "✗ Campaign has no category or subcategory\n";
    exit(1);
}

echo "\n=== Checking Backlinks Store ===\n";
$backlinksCount = Backlink::where('status', Backlink::STATUS_ACTIVE)
    ->whereBetween('pa', [$minPa, $maxPa])
    ->whereBetween('da', [$minDa, $maxDa])
    ->whereHas('categories', function($q) use ($categoryIds) {
        $q->whereIn('categories.id', $categoryIds);
    })
    ->count();

echo "✓ Found {$backlinksCount} backlinks in store matching criteria\n";

if ($backlinksCount == 0) {
    echo "\n⚠ No backlinks found in store. Creating test backlinks...\n";
    
    // Get a category
    $category = Category::whereIn('id', $categoryIds)->first();
    
    if (!$category) {
        echo "✗ Category not found\n";
        exit(1);
    }
    
    // Create test backlinks in the store
    $testUrls = [
        'https://example-blog.com',
        'https://tech-news-site.com',
        'https://business-forum.com',
        'https://news-portal.com',
        'https://community-site.com',
    ];
    
    $created = 0;
    foreach ($testUrls as $url) {
        // Check if already exists
        if (Backlink::where('url', $url)->exists()) {
            continue;
        }
        
        $pa = rand($minPa, min($maxPa, 50));
        $da = rand($minDa, min($maxDa, 60));
        
        $backlink = Backlink::create([
            'url' => $url,
            'pa' => $pa,
            'da' => $da,
            'site_type' => 'comment',
            'status' => Backlink::STATUS_ACTIVE,
            'daily_site_limit' => 5,
        ]);
        
        $backlink->categories()->attach($category->id);
        echo "  ✓ Created backlink: {$backlink->url} (PA: {$backlink->pa}, DA: {$backlink->da})\n";
        $created++;
    }
    
    echo "\n✓ Created {$created} test backlinks in store\n";
} else {
    echo "\n✓ Backlinks already exist in store. No need to create.\n";
}

echo "\n=== Testing API Endpoint ===\n";
$controller = new \App\Http\Controllers\Api\OpportunityController();
$request = new \Illuminate\Http\Request();
$request->merge(['count' => 1, 'task_type' => 'comment']);

try {
    $response = $controller->getForCampaign($request, 1);
    $data = json_decode($response->getContent(), true);
    
    if ($data['success'] ?? false) {
        $opps = $data['opportunities'] ?? [];
        echo "✓ API returned " . count($opps) . " backlink(s) from store\n";
        if (!empty($opps)) {
            foreach ($opps as $opp) {
                echo "  - {$opp['url']} (PA: {$opp['pa']}, DA: {$opp['da']})\n";
            }
        }
    } else {
        echo "✗ API error: " . ($data['error'] ?? 'Unknown error') . "\n";
    }
} catch (\Exception $e) {
    echo "✗ API test failed: " . $e->getMessage() . "\n";
}
