<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Backlink;
use App\Models\Campaign;
use App\Models\Category;

$campaign = Campaign::with(['category', 'subcategory'])->find(1);
if (!$campaign) {
    echo "Campaign 1 not found. Aborting.\n";
    exit(1);
}

$categoryIds = array_filter([$campaign->category_id, $campaign->subcategory_id]);
$category = Category::whereIn('id', $categoryIds)->first();

if (!$category) {
    echo "No category found for campaign 1. Aborting.\n";
    exit(1);
}

$backlink = Backlink::updateOrCreate(
    ['url' => 'http://127.0.0.1:8000/test-comment'],
    [
        'pa' => 50,
        'da' => 50,
        'site_type' => 'comment',
        'status' => 'active',
        'daily_site_limit' => 5,
        'metadata' => ['source' => 'test_comment_form'],
    ]
);

if (!$backlink->categories()->where('category_id', $category->id)->exists()) {
    $backlink->categories()->attach($category->id);
}

echo "Created/updated test comment backlink:\n";
echo "ID: {$backlink->id}\n";
echo "URL: {$backlink->url}\n";
echo "Category: {$category->name}\n";

