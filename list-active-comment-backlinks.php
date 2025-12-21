<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Backlink;

$links = Backlink::where('site_type', 'comment')->where('status', 'active')->get(['id','url','status']);
foreach ($links as $link) {
    echo "{$link->id} | {$link->url} | {$link->status}\n";
}

