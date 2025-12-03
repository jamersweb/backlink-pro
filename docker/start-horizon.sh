#!/bin/bash
set -e

# Wait for composer dependencies to be available (max 60 seconds)
echo "Waiting for composer dependencies..."
counter=0
while [ ! -f "/var/www/html/vendor/autoload.php" ] && [ $counter -lt 30 ]; do
    echo "Waiting for vendor/autoload.php... ($counter/30)"
    sleep 2
    counter=$((counter + 1))
done

if [ ! -f "/var/www/html/vendor/autoload.php" ]; then
    echo "Installing composer dependencies..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# Run package discovery to register Horizon commands
echo "Running package discovery..."
php artisan package:discover --ansi || true

# Start Horizon
echo "Starting Horizon..."
exec php artisan horizon

