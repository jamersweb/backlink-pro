<?php

/**
 * Script to reset test database
 * Run with: php tests/ResetTestDatabase.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "Resetting test database...\n";

try {
    // Set test environment
    putenv('APP_ENV=testing');
    putenv('DB_CONNECTION=sqlite');
    putenv('DB_DATABASE=:memory:');
    
    // Drop all tables if using SQLite file
    if (config('database.default') === 'sqlite' && config('database.connections.sqlite.database') !== ':memory:') {
        $database = config('database.connections.sqlite.database');
        if (file_exists($database)) {
            unlink($database);
            echo "Deleted SQLite database file: {$database}\n";
        }
    }
    
    // Run fresh migrations
    \Artisan::call('migrate:fresh', [
        '--env' => 'testing',
        '--force' => true,
    ]);
    
    echo "Migrations completed successfully!\n";
    echo "Test database reset complete.\n";
    
} catch (\Exception $e) {
    echo "Error resetting test database: " . $e->getMessage() . "\n";
    exit(1);
}

