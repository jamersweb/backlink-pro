<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\AutomationTask;

$task = AutomationTask::first();
var_dump($task ? $task->toArray() : null);

