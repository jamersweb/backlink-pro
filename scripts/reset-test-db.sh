#!/bin/bash

# Reset Test Database Script
# This script resets the test database for clean test runs

echo "Resetting test database..."

# Set test environment
export APP_ENV=testing
export DB_CONNECTION=sqlite
export DB_DATABASE=:memory:

# Run fresh migrations
php artisan migrate:fresh --env=testing --force

echo "Test database reset complete!"

