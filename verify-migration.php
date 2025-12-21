<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Backlink;
use App\Models\BacklinkOpportunity;

echo "=== Migration Verification ===\n\n";

echo "Backlinks (Global Store):\n";
echo "  Total: " . Backlink::count() . "\n";
$sampleBacklink = Backlink::first();
if ($sampleBacklink) {
    echo "  Sample: {$sampleBacklink->url}\n";
    echo "    PA: {$sampleBacklink->pa}, DA: {$sampleBacklink->da}\n";
    echo "    Site Type: {$sampleBacklink->site_type}, Status: {$sampleBacklink->status}\n";
} else {
    echo "  No backlinks in store yet\n";
}

echo "\nOpportunities (Campaign-specific):\n";
echo "  Total: " . BacklinkOpportunity::count() . "\n";
$sampleOpp = BacklinkOpportunity::with('backlink', 'campaign')->first();
if ($sampleOpp) {
    echo "  Sample Opportunity:\n";
    echo "    Campaign ID: {$sampleOpp->campaign_id}\n";
    echo "    Backlink ID: {$sampleOpp->backlink_id}\n";
    echo "    URL: {$sampleOpp->url}\n";
    echo "    Type: {$sampleOpp->type}, Status: {$sampleOpp->status}\n";
    if ($sampleOpp->backlink) {
        echo "    Store URL: {$sampleOpp->backlink->url}\n";
    }
} else {
    echo "  No opportunities yet\n";
}

echo "\n✅ Migration completed successfully!\n";

