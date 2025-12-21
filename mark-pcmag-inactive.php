<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Backlink;

$backlink = Backlink::where('url', 'https://pcmag.com')->first();
if ($backlink) {
    $backlink->update(['status' => 'inactive']);
    echo "✓ Marked PCMag as inactive\n";
    echo "  ID: {$backlink->id}\n";
    echo "  URL: {$backlink->url}\n";
} else {
    echo "✗ PCMag not found\n";
}

