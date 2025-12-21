<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Backlink;

$count = Backlink::where('site_type', 'comment')
    ->where('url', '!=', 'http://127.0.0.1:8000/test-comment')
    ->update(['status' => 'inactive']);

echo "Deactivated {$count} comment backlinks (except test-comment)\n";

