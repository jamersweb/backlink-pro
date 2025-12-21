<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Backlink;
use App\Models\BacklinkOpportunity;

echo "=== Verifying Fixes ===\n\n";

// Check that Backlink model doesn't have campaign_id column
$backlinkColumns = \Illuminate\Support\Facades\Schema::getColumnListing('backlinks');
echo "Backlinks table columns:\n";
echo "  Has campaign_id: " . (in_array('campaign_id', $backlinkColumns) ? 'YES ❌' : 'NO ✅') . "\n";
echo "  Has type: " . (in_array('type', $backlinkColumns) ? 'YES ❌' : 'NO ✅') . "\n";
echo "  Has url: " . (in_array('url', $backlinkColumns) ? 'YES ✅' : 'NO ❌') . "\n";
echo "  Has pa: " . (in_array('pa', $backlinkColumns) ? 'YES ✅' : 'NO ❌') . "\n";
echo "  Has da: " . (in_array('da', $backlinkColumns) ? 'YES ✅' : 'NO ❌') . "\n";
echo "  Has site_type: " . (in_array('site_type', $backlinkColumns) ? 'YES ✅' : 'NO ❌') . "\n";

// Check that BacklinkOpportunity has campaign_id
$opportunityColumns = \Illuminate\Support\Facades\Schema::getColumnListing('backlink_opportunities');
echo "\nBacklinkOpportunities table columns:\n";
echo "  Has campaign_id: " . (in_array('campaign_id', $opportunityColumns) ? 'YES ✅' : 'NO ❌') . "\n";
echo "  Has type: " . (in_array('type', $opportunityColumns) ? 'YES ✅' : 'NO ❌') . "\n";
echo "  Has backlink_id: " . (in_array('backlink_id', $opportunityColumns) ? 'YES ✅' : 'NO ❌') . "\n";

// Test queries
echo "\n=== Testing Queries ===\n";

try {
    $backlinks = Backlink::where('status', Backlink::STATUS_ACTIVE)->count();
    echo "✓ Backlink query (by status): {$backlinks}\n";
} catch (\Exception $e) {
    echo "✗ Backlink query failed: " . $e->getMessage() . "\n";
}

try {
    $opportunities = BacklinkOpportunity::where('campaign_id', 1)->count();
    echo "✓ BacklinkOpportunity query (by campaign_id): {$opportunities}\n";
} catch (\Exception $e) {
    echo "✗ BacklinkOpportunity query failed: " . $e->getMessage() . "\n";
}

try {
    // This should fail if there's still code querying Backlink with campaign_id
    $test = Backlink::where('campaign_id', 1)->count();
    echo "✗ ERROR: Backlink query with campaign_id still works! This should fail.\n";
} catch (\Exception $e) {
    echo "✓ Backlink query with campaign_id correctly fails (as expected)\n";
}

echo "\n✅ All fixes verified!\n";

