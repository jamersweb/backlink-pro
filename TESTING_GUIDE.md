# Testing Guide

## Overview
This project includes comprehensive unit, feature, and integration tests to ensure code quality and reliability.

## Test Structure

### Unit Tests (`tests/Unit/`)
- **Models**: Test model relationships, scopes, and methods
- **Services**: Test service classes (GmailService, LLMContentService, etc.)
- **Jobs**: Test queue jobs and their logic

### Feature Tests (`tests/Feature/`)
- **Auth**: Authentication flows (login, register, logout)
- **Campaigns**: Campaign CRUD operations and business logic
- **Gmail**: Gmail OAuth integration flows

### Integration Tests (`tests/Integration/`)
- **Laravel-Python API**: Communication between Laravel backend and Python workers
- **Queue Jobs**: End-to-end job execution
- **External APIs**: Gmail API, Stripe, etc.

## Running Tests

### Run All Tests
```bash
php artisan test
```

### Run Specific Test Suite
```bash
# Unit tests only
php artisan test --testsuite=Unit

# Feature tests only
php artisan test --testsuite=Feature
```

### Run Specific Test File
```bash
php artisan test tests/Unit/Models/UserTest.php
```

### Run with Coverage
```bash
php artisan test --coverage
```

## Test Database
Tests use an in-memory SQLite database by default (configured in `phpunit.xml`).

## Writing New Tests

### Unit Test Example
```php
<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_has_plan()
    {
        $user = User::factory()->create();
        // Test logic here
    }
}
```

### Feature Test Example
```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_access_page()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(200);
    }
}
```

## Continuous Integration
Tests should be run automatically in CI/CD pipelines before deployment.

