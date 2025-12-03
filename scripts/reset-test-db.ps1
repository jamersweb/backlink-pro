# Reset Test Database Script (PowerShell)
# This script resets the test database for clean test runs

Write-Host "Resetting test database..." -ForegroundColor Green

# Set test environment variables
$env:APP_ENV = "testing"
$env:DB_CONNECTION = "sqlite"
$env:DB_DATABASE = ":memory:"

# Run fresh migrations
php artisan migrate:fresh --env=testing --force

Write-Host "Test database reset complete!" -ForegroundColor Green

