# 🧪 Testing Guide - Step by Step

## Overview

This guide walks you through testing the Auto Backlink Pro application, including setup, running tests, and interpreting results.

---

## Prerequisites

Before running tests, ensure:

1. ✅ Docker containers are running
2. ✅ Database migrations have been run
3. ✅ Dependencies are installed

---

## Step 1: Initial Setup

### 1.1 Verify Docker Containers

```bash
# Check containers are running
docker-compose ps

# Should show all containers as "Up"
```

### 1.2 Install Dependencies (if not done)

```bash
# Install PHP dependencies
docker-compose exec app composer install

# Install Node.js dependencies
docker-compose exec app npm install
```

### 1.3 Setup Test Database

```bash
# Run migrations for test environment
docker-compose exec app php artisan migrate --env=testing

# OR reset test database
docker-compose exec app php artisan migrate:fresh --env=testing
```

---

## Step 2: Running Tests

### 2.1 Run All Tests (First Test)

```bash
# Run complete test suite
docker-compose exec app php artisan test

# Expected output:
# ✅ 41 tests passing
# ✅ 68 assertions
# ✅ Duration: ~4 seconds
```

**What this does:**
- Runs all unit tests (models, services, jobs)
- Runs all feature tests (authentication, CRUD operations)
- Uses SQLite in-memory database for speed
- Each test runs in a transaction (rolled back after)

### 2.2 Run Specific Test Suites

#### Unit Tests Only

```bash
docker-compose exec app php artisan test --testsuite=Unit
```

**Tests included:**
- ✅ Model tests (User, Campaign)
- ✅ Service tests (GmailService, LLMContentService, BacklinkVerificationService, ExportService)
- ✅ Job tests (ScheduleCampaignJob)

#### Feature Tests Only

```bash
docker-compose exec app php artisan test --testsuite=Feature
```

**Tests included:**
- ✅ Authentication (Login, Register, Logout)
- ✅ Campaign CRUD operations
- ✅ Gmail OAuth flow

### 2.3 Run Specific Tests

```bash
# Run specific test class
docker-compose exec app php artisan test --filter=UserTest

# Run specific test method
docker-compose exec app php artisan test --filter=test_user_can_login

# Run tests matching pattern
docker-compose exec app php artisan test --filter="Campaign"
```

### 2.4 Test with Verbose Output

```bash
# Show detailed test output
docker-compose exec app php artisan test --verbose

# Stop on first failure
docker-compose exec app php artisan test --stop-on-failure
```

---

## Step 3: Understanding Test Results

### 3.1 Successful Test Output

```
PASS  Tests\Unit\ExampleTest
✓ that true is true                                                    0.01s

PASS  Tests\Unit\Models\UserTest
✓ user has many campaigns                                              0.03s
✓ user has many domains                                                0.02s
✓ user belongs to plan                                                 0.02s
✓ user has subscription                                                0.02s

Tests:    41 passed (68 assertions)
Duration: 3.88s
```

**What this means:**
- ✅ All tests passed
- ✅ 68 assertions verified
- ✅ Completed in ~4 seconds

### 3.2 Failed Test Output

```
FAIL  Tests\Unit\Models\UserTest
✓ user has many campaigns                                              0.03s
⨯ user belongs to plan                                                 0.05s

───────────────────────────────────────────────────────────────────────────
FAILED  Tests\Unit\Models\UserTest > user belongs to plan
Failed asserting that two strings are equal.
Expected: 'premium'
Actual:   'basic'

at tests/Unit/Models/UserTest.php:45
```

**What this means:**
- ❌ Test failed
- Shows expected vs actual values
- Shows file and line number where assertion failed

### 3.3 Test Categories Explained

#### Unit Tests
- **Purpose**: Test individual components in isolation
- **Examples**: Model relationships, service methods, job logic
- **Speed**: Fast (~1-2 seconds)

#### Feature Tests
- **Purpose**: Test complete user workflows
- **Examples**: Login flow, creating campaigns, OAuth
- **Speed**: Slower (~2-3 seconds)

---

## Step 4: Common Test Scenarios

### 4.1 Testing After Code Changes

```bash
# 1. Make code changes
# 2. Run tests to verify nothing broke
docker-compose exec app php artisan test

# 3. If tests fail, fix issues and re-run
docker-compose exec app php artisan test --filter=[specific-test]
```

### 4.2 Testing Before Committing

```bash
# Run full test suite
docker-compose exec app php artisan test

# Run with stop on failure (faster feedback)
docker-compose exec app php artisan test --stop-on-failure
```

### 4.3 Testing Specific Features

```bash
# Test authentication only
docker-compose exec app php artisan test --filter="Auth"

# Test campaigns only
docker-compose exec app php artisan test --filter="Campaign"

# Test Gmail integration only
docker-compose exec app php artisan test --filter="Gmail"
```

---

## Step 5: Test Coverage

### 5.1 Generate Coverage Report

```bash
# Basic coverage (requires Xdebug)
docker-compose exec app php artisan test --coverage

# Coverage with minimum threshold
docker-compose exec app php artisan test --coverage --min=80

# Coverage in text format
docker-compose exec app php artisan test --coverage-text
```

### 5.2 Current Test Coverage

**Covered Areas:**
- ✅ User model (relationships, subscriptions)
- ✅ Campaign model (relationships, status)
- ✅ Authentication (login, register, logout)
- ✅ Campaign CRUD operations
- ✅ Gmail OAuth flow
- ✅ Gmail service (email search, verification links)
- ✅ LLM content service
- ✅ Backlink verification service
- ✅ Export service
- ✅ Schedule campaign job

**Target Coverage:** 80%+ (currently ~40%)

---

## Step 6: Troubleshooting Tests

### 6.1 Database Errors

**Problem**: Tests fail with database errors

**Solution**:
```bash
# Reset test database
docker-compose exec app php artisan migrate:fresh --env=testing

# Clear cache
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan cache:clear
```

### 6.2 Factory Errors

**Problem**: "Call to undefined method factory()"

**Solution**:
```bash
# Ensure HasFactory trait is in models
# Check: app/Models/User.php, Campaign.php, etc.

# Clear autoload cache
docker-compose exec app composer dump-autoload
```

### 6.3 Environment Issues

**Problem**: Tests using wrong environment

**Solution**:
```bash
# Verify PHPUnit configuration
# Check: phpunit.xml

# Run with explicit environment
docker-compose exec app php artisan test --env=testing
```

### 6.4 Slow Tests

**Problem**: Tests taking too long

**Solution**:
```bash
# Run specific test suite instead of all
docker-compose exec app php artisan test --testsuite=Unit

# Use parallel testing (if available)
docker-compose exec app php artisan test --parallel
```

---

## Step 7: Writing New Tests

### 7.1 Create Test File

```bash
# Create unit test
docker-compose exec app php artisan make:test Unit/Services/MyServiceTest

# Create feature test
docker-compose exec app php artisan make:test Feature/MyFeatureTest
```

### 7.2 Test Structure

```php
<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\MyService;

class MyServiceTest extends TestCase
{
    public function test_service_method_works()
    {
        $service = new MyService();
        $result = $service->doSomething();
        
        $this->assertTrue($result);
    }
}
```

### 7.3 Best Practices

1. ✅ **One assertion per test** (when possible)
2. ✅ **Descriptive test names** (`test_user_can_login` not `test1`)
3. ✅ **Use factories** for test data
4. ✅ **Test edge cases** (empty values, null, etc.)
5. ✅ **Keep tests fast** (avoid external API calls, use mocks)

---

## Step 8: Continuous Testing

### 8.1 Watch Mode (Manual)

```bash
# Run tests after each change
# Use a file watcher or run manually after changes
docker-compose exec app php artisan test
```

### 8.2 Pre-commit Hook (Recommended)

Create `.git/hooks/pre-commit`:

```bash
#!/bin/bash
docker-compose exec app php artisan test
```

Make it executable:
```bash
chmod +x .git/hooks/pre-commit
```

### 8.3 CI/CD Integration

For GitHub Actions, create `.github/workflows/tests.yml`:

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Run tests
        run: docker-compose exec app php artisan test
```

---

## Quick Reference

### Test Commands

```bash
# All tests
docker-compose exec app php artisan test

# Specific suite
docker-compose exec app php artisan test --testsuite=Unit

# Specific test
docker-compose exec app php artisan test --filter=UserTest

# With coverage
docker-compose exec app php artisan test --coverage

# Verbose output
docker-compose exec app php artisan test --verbose

# Stop on failure
docker-compose exec app php artisan test --stop-on-failure
```

### Test Files Location

```
tests/
├── Unit/
│   ├── Models/
│   │   ├── UserTest.php
│   │   └── CampaignTest.php
│   ├── Services/
│   │   ├── GmailServiceTest.php
│   │   ├── LLMContentServiceTest.php
│   │   ├── BacklinkVerificationServiceTest.php
│   │   └── ExportServiceTest.php
│   └── Jobs/
│       └── ScheduleCampaignJobTest.php
└── Feature/
    ├── Auth/
    │   ├── LoginTest.php
    │   └── RegisterTest.php
    ├── Campaigns/
    │   └── CampaignCRUDTest.php
    └── Gmail/
        └── GmailOAuthTest.php
```

---

## Summary

✅ **Step 1**: Setup test environment  
✅ **Step 2**: Run all tests (`php artisan test`)  
✅ **Step 3**: Verify 41 tests pass  
✅ **Step 4**: Run specific tests as needed  
✅ **Step 5**: Check coverage  
✅ **Step 6**: Fix any failures  
✅ **Step 7**: Write new tests for new features  
✅ **Step 8**: Integrate into workflow  

**Expected Result**: 41 tests passing, 68 assertions, ~4 seconds duration

---

## Next Steps

1. ✅ Run tests regularly during development
2. ✅ Add tests for new features
3. ✅ Aim for 80%+ test coverage
4. ✅ Set up CI/CD for automated testing

