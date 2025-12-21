<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Campaign;
use App\Models\Backlink;
use App\Models\Category;

echo "=== Creating Test Backlinks for Campaign 1 ===\n\n";

// Get campaign 1 details
$campaign = Campaign::with(['user.plan', 'category', 'subcategory'])->find(1);

if (!$campaign) {
    echo "✗ Campaign 1 not found\n";
    exit(1);
}

echo "Campaign: {$campaign->name}\n";
echo "Category ID: " . ($campaign->category_id ?? 'null') . "\n";
echo "Subcategory ID: " . ($campaign->subcategory_id ?? 'null') . "\n";

if (!$campaign->user->plan) {
    echo "✗ User has no plan assigned\n";
    exit(1);
}

$plan = $campaign->user->plan;
$minPa = $plan->min_pa ?? 0;
$maxPa = $plan->max_pa ?? 100;
$minDa = $plan->min_da ?? 0;
$maxDa = $plan->max_da ?? 100;

echo "Plan: {$plan->name}\n";
echo "PA Range: {$minPa}-{$maxPa}\n";
echo "DA Range: {$minDa}-{$maxDa}\n\n";

// Get category
$categoryIds = array_filter([$campaign->category_id, $campaign->subcategory_id]);
if (empty($categoryIds)) {
    echo "✗ Campaign has no category\n";
    exit(1);
}

$category = Category::whereIn('id', $categoryIds)->first();
if (!$category) {
    echo "✗ Category not found\n";
    exit(1);
}

echo "Category: {$category->name}\n\n";

// Test URLs for comments (most common type)
$commentUrls = [
    'https://techcrunch.com',
    'https://mashable.com',
    'https://theverge.com',
    'https://engadget.com',
    'https://wired.com',
    'https://arstechnica.com',
    'https://gizmodo.com',
    'https://cnet.com',
    'https://zdnet.com',
    'https://venturebeat.com',
    'https://readwrite.com',
    'https://thenextweb.com',
    'https://techradar.com',
    'https://pcmag.com',
    'https://digitaltrends.com',
    'https://slashdot.org',
    'https://reddit.com',
    'https://medium.com',
    'https://dev.to',
    'https://hackernoon.com',
];

// Test URLs for profiles
$profileUrls = [
    'https://github.com',
    'https://linkedin.com',
    'https://twitter.com',
    'https://facebook.com',
    'https://instagram.com',
    'https://pinterest.com',
    'https://tumblr.com',
    'https://flickr.com',
    'https://dribbble.com',
    'https://behance.net',
];

// Test URLs for forums
$forumUrls = [
    'https://stackoverflow.com',
    'https://quora.com',
    'https://discourse.org',
    'https://phpbb.com',
    'https://vbulletin.com',
];

// Create comment backlinks
echo "Creating COMMENT backlinks...\n";
$createdComments = 0;
foreach ($commentUrls as $url) {
    // Check if already exists
    if (Backlink::where('url', $url)->exists()) {
        continue;
    }
    
    $pa = rand($minPa, min($maxPa, 60));
    $da = rand($minDa, min($maxDa, 80));
    
    $backlink = Backlink::create([
        'url' => $url,
        'pa' => $pa,
        'da' => $da,
        'site_type' => 'comment',
        'status' => Backlink::STATUS_ACTIVE,
        'daily_site_limit' => rand(3, 10),
    ]);
    
    $backlink->categories()->attach($category->id);
    echo "  ✓ Created: {$backlink->url} (PA: {$backlink->pa}, DA: {$backlink->da})\n";
    $createdComments++;
}

echo "\n✓ Created {$createdComments} comment backlinks\n\n";

// Create profile backlinks
echo "Creating PROFILE backlinks...\n";
$createdProfiles = 0;
foreach ($profileUrls as $url) {
    if (Backlink::where('url', $url)->exists()) {
        continue;
    }
    
    $pa = rand($minPa, min($maxPa, 70));
    $da = rand($minDa, min($maxDa, 90));
    
    $backlink = Backlink::create([
        'url' => $url,
        'pa' => $pa,
        'da' => $da,
        'site_type' => 'profile',
        'status' => Backlink::STATUS_ACTIVE,
        'daily_site_limit' => rand(2, 5),
    ]);
    
    $backlink->categories()->attach($category->id);
    echo "  ✓ Created: {$backlink->url} (PA: {$backlink->pa}, DA: {$backlink->da})\n";
    $createdProfiles++;
}

echo "\n✓ Created {$createdProfiles} profile backlinks\n\n";

// Create forum backlinks
echo "Creating FORUM backlinks...\n";
$createdForums = 0;
foreach ($forumUrls as $url) {
    if (Backlink::where('url', $url)->exists()) {
        continue;
    }
    
    $pa = rand($minPa, min($maxPa, 65));
    $da = rand($minDa, min($maxDa, 85));
    
    $backlink = Backlink::create([
        'url' => $url,
        'pa' => $pa,
        'da' => $da,
        'site_type' => 'forum',
        'status' => Backlink::STATUS_ACTIVE,
        'daily_site_limit' => rand(1, 3),
    ]);
    
    $backlink->categories()->attach($category->id);
    echo "  ✓ Created: {$backlink->url} (PA: {$backlink->pa}, DA: {$backlink->da})\n";
    $createdForums++;
}

echo "\n✓ Created {$createdForums} forum backlinks\n\n";

// Summary
$totalCreated = $createdComments + $createdProfiles + $createdForums;
echo "=== Summary ===\n";
echo "Total backlinks created: {$totalCreated}\n";
echo "  - Comments: {$createdComments}\n";
echo "  - Profiles: {$createdProfiles}\n";
echo "  - Forums: {$createdForums}\n\n";

// Verify API can find them
echo "=== Testing API ===\n";
$controller = new \App\Http\Controllers\Api\OpportunityController();
$request = new \Illuminate\Http\Request();
$request->merge(['count' => 5, 'task_type' => 'comment']);

try {
    $response = $controller->getForCampaign($request, 1);
    $data = json_decode($response->getContent(), true);
    
    if ($data['success'] ?? false) {
        $opps = $data['opportunities'] ?? [];
        echo "✓ API returned " . count($opps) . " comment backlink(s)\n";
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

echo "\n✅ Done! Backlinks are ready for testing.\n";

