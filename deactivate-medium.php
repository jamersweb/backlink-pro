<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Backlink;

$count = Backlink::where('url', 'like', '%medium.com%')->update(['status' => 'inactive']);
echo "Deactivated {$count} medium.com backlinks\n";

