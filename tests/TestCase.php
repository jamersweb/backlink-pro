<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        // Force SQLite in-memory database for tests (override Docker/.env settings)
        // Set environment variables BEFORE parent::setUp() which bootstraps Laravel
        putenv('DB_CONNECTION=sqlite');
        putenv('DB_DATABASE=:memory:');
        $_ENV['DB_CONNECTION'] = 'sqlite';
        $_ENV['DB_DATABASE'] = ':memory:';
        $_SERVER['DB_CONNECTION'] = 'sqlite';
        $_SERVER['DB_DATABASE'] = ':memory:';

        parent::setUp();

        // Ensure SQLite uses in-memory database for tests (override config after Laravel loads)
        config(['database.connections.sqlite.database' => ':memory:']);
        config(['database.connections.sqlite.driver' => 'sqlite']);
        config(['database.default' => 'sqlite']);
    }
}
