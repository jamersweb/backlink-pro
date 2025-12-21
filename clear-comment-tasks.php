<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\AutomationTask;

$deleted = AutomationTask::where('type', 'comment')->delete();
echo "Deleted {$deleted} comment tasks\n";

