# Setup Commands - Run These in Order

## Step 1: Stop Old Containers
```bash
docker-compose down
```

## Step 2: Rebuild All Containers (with new dependencies)
```bash
docker-compose build --no-cache
```

This will take 10-15 minutes as it rebuilds everything from scratch.

## Step 3: Start Containers
```bash
docker-compose up -d
```

## Step 4: Wait for MySQL to Initialize (30 seconds)
```bash
# Just wait, or check logs
docker-compose logs mysql
```

## Step 5: Update Composer Dependencies
```bash
docker-compose exec app composer update
```

This will install all the new packages we added (Inertia, Horizon, Telescope, Stripe, etc.)

## Step 6: Install Node.js Dependencies
```bash
docker-compose exec app npm install
```

## Step 7: Generate App Key
```bash
docker-compose exec app php artisan key:generate
```

## Step 8: Run Migrations
```bash
docker-compose exec app php artisan migrate
```

## Step 9: Set Permissions
```bash
docker-compose exec app chmod -R 775 storage bootstrap/cache
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
```

## Step 10: Verify Everything Works
```bash
# Check containers are running
docker-compose ps

# Check logs
docker-compose logs app
```

## Access Application
- Web: http://localhost
- Horizon: http://localhost/horizon (after setup)

