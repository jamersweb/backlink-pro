# Docker Setup Guide - Auto Backlink Pro

## Prerequisites

- Docker Desktop installed and running
- Docker Compose (included with Docker Desktop)
- At least 4GB RAM available for Docker

---

## Step 1: Environment Configuration

### Create `.env` file

Copy the example environment file (if exists) or create a new `.env` file in the project root:

```bash
# Copy from env.blade.php if it exists, or create new
cp env.blade.php .env
```

### Update `.env` file with Docker settings

Make sure your `.env` file has these Docker-specific settings:

```env
APP_NAME="Auto Backlink Pro"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

# Database (Docker)
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=backlink_pro
DB_USERNAME=root
DB_PASSWORD=root

# Redis (Docker)
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=null

# Queue
QUEUE_CONNECTION=redis

# Python Worker API Token (generate a secure token)
PYTHON_API_TOKEN=your-secure-api-token-here-change-this

# Gmail OAuth (will be configured later)
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=http://localhost/auth/google/callback

# Stripe (will be configured later)
STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=
```

---

## Step 2: Build and Start Docker Containers

### First Time Setup

```bash
# 1. Build all Docker images
docker-compose build

# 2. Start all containers
docker-compose up -d

# 3. Check container status
docker-compose ps
```

You should see these containers running:
- `backlink-nginx` (Web server)
- `backlink-app` (Laravel PHP-FPM)
- `backlink-mysql` (Database)
- `backlink-redis` (Cache/Queue)
- `backlink-queue` (Queue worker)
- `backlink-horizon` (Queue dashboard)
- `backlink-python-worker` (Python automation)

---

## Step 3: Install Dependencies

### Install PHP Dependencies (Composer)

```bash
# Enter the app container
docker-compose exec app bash

# Inside container, install dependencies
composer install

# Exit container
exit
```

**OR** run directly without entering container:

```bash
docker-compose exec app composer install
```

### Install Node.js Dependencies

```bash
# Enter the app container
docker-compose exec app bash

# Install npm dependencies
npm install

# Exit container
exit
```

**OR** run directly:

```bash
docker-compose exec app npm install
```

---

## Step 4: Laravel Setup

### Generate Application Key

```bash
docker-compose exec app php artisan key:generate
```

### Run Database Migrations

```bash
docker-compose exec app php artisan migrate
```

### (Optional) Seed Database

```bash
# If you have seeders
docker-compose exec app php artisan db:seed
```

### Set Storage Permissions

```bash
docker-compose exec app chmod -R 775 storage bootstrap/cache
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
```

---

## Step 5: Build Frontend Assets

```bash
# Development build (watch mode)
docker-compose exec app npm run dev

# OR Production build
docker-compose exec app npm run build
```

---

## Step 6: Access the Application

### Web Application
- **URL**: http://localhost
- **Port**: 80 (Nginx)

### Laravel Horizon (Queue Dashboard)
- **URL**: http://localhost/horizon
- Requires authentication (configure in `config/horizon.php`)

### Database
- **Host**: localhost
- **Port**: 3306
- **Database**: backlink_pro
- **Username**: root
- **Password**: root

### Redis
- **Host**: localhost
- **Port**: 6379

---

## Common Docker Commands

### View Logs

```bash
# All containers
docker-compose logs

# Specific container
docker-compose logs app
docker-compose logs queue
docker-compose logs python-worker

# Follow logs (live)
docker-compose logs -f app
```

### Stop Containers

```bash
# Stop all containers
docker-compose stop

# Stop specific container
docker-compose stop app
```

### Start Containers

```bash
# Start all containers
docker-compose start

# Start specific container
docker-compose start app
```

### Restart Containers

```bash
# Restart all containers
docker-compose restart

# Restart specific container
docker-compose restart app
```

### Rebuild Containers

```bash
# Rebuild after code changes
docker-compose build

# Rebuild without cache
docker-compose build --no-cache

# Rebuild and restart
docker-compose up -d --build
```

### Execute Commands in Containers

```bash
# Run artisan commands
docker-compose exec app php artisan migrate
docker-compose exec app php artisan tinker
docker-compose exec app php artisan queue:work

# Run composer commands
docker-compose exec app composer install
docker-compose exec app composer update

# Run npm commands
docker-compose exec app npm install
docker-compose exec app npm run dev

# Access container shell
docker-compose exec app bash
docker-compose exec python-worker bash
```

### View Container Status

```bash
# List running containers
docker-compose ps

# View resource usage
docker stats
```

### Clean Up

```bash
# Stop and remove containers (keeps volumes)
docker-compose down

# Stop and remove containers + volumes (WARNING: deletes database)
docker-compose down -v

# Remove unused images
docker image prune
```

---

## Troubleshooting

### Port Already in Use

If port 80, 3306, or 6379 is already in use:

1. **Stop conflicting services** (XAMPP, MySQL, Redis)
2. **OR** change ports in `docker-compose.yml`:
   ```yaml
   ports:
     - "8080:80"  # Change 80 to 8080
     - "3307:3306"  # Change 3306 to 3307
   ```

### Container Won't Start

```bash
# Check logs
docker-compose logs app

# Rebuild container
docker-compose build --no-cache app
docker-compose up -d app
```

### Database Connection Issues

1. **Check MySQL is running**:
   ```bash
   docker-compose ps mysql
   ```

2. **Check MySQL logs**:
   ```bash
   docker-compose logs mysql
   ```

3. **Wait for MySQL to be ready** (can take 30-60 seconds on first start):
   ```bash
   docker-compose exec app php artisan migrate
   ```

### Permission Issues

```bash
# Fix storage permissions
docker-compose exec app chmod -R 775 storage bootstrap/cache
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
```

### Queue Worker Not Processing Jobs

1. **Check queue worker logs**:
   ```bash
   docker-compose logs queue
   ```

2. **Restart queue worker**:
   ```bash
   docker-compose restart queue
   ```

3. **Check Horizon** (if using):
   ```bash
   docker-compose logs horizon
   ```

### Python Worker Issues

1. **Check Python worker logs**:
   ```bash
   docker-compose logs python-worker
   ```

2. **Rebuild Python container**:
   ```bash
   docker-compose build python-worker
   docker-compose up -d python-worker
   ```

---

## Development Workflow

### Daily Development

```bash
# 1. Start containers
docker-compose up -d

# 2. Run migrations (if needed)
docker-compose exec app php artisan migrate

# 3. Start frontend dev server (in separate terminal)
docker-compose exec app npm run dev

# 4. Access application
# http://localhost
```

### After Code Changes

```bash
# PHP/Composer changes
docker-compose exec app composer dump-autoload

# Database changes
docker-compose exec app php artisan migrate

# Frontend changes
docker-compose exec app npm run build
```

### Testing

```bash
# Run tests
docker-compose exec app php artisan test

# Run specific test
docker-compose exec app php artisan test --filter TestName
```

---

## Production Deployment

For production, you'll need to:

1. **Update `.env`** with production values
2. **Set `APP_ENV=production`** and `APP_DEBUG=false`
3. **Build frontend assets**: `npm run build`
4. **Optimize Laravel**: 
   ```bash
   docker-compose exec app php artisan config:cache
   docker-compose exec app php artisan route:cache
   docker-compose exec app php artisan view:cache
   ```
5. **Use proper SSL/TLS** (update Nginx config)
6. **Set up proper backup strategy** for MySQL volumes

---

## Quick Start Script

Create a `setup.sh` file for quick setup:

```bash
#!/bin/bash
echo "Building Docker containers..."
docker-compose build

echo "Starting containers..."
docker-compose up -d

echo "Waiting for MySQL..."
sleep 10

echo "Installing dependencies..."
docker-compose exec app composer install
docker-compose exec app npm install

echo "Generating app key..."
docker-compose exec app php artisan key:generate

echo "Running migrations..."
docker-compose exec app php artisan migrate

echo "Setting permissions..."
docker-compose exec app chmod -R 775 storage bootstrap/cache

echo "Setup complete! Access http://localhost"
```

Make it executable:
```bash
chmod +x setup.sh
./setup.sh
```

---

## Next Steps

After Docker is running:

1. ✅ **Install Inertia.js** (Phase 2)
2. ✅ **Set up Gmail OAuth** (Phase 4)
3. ✅ **Configure Stripe** (Billing)
4. ✅ **Set up monitoring** (Telescope, Horizon)

---

## Support

If you encounter issues:

1. Check container logs: `docker-compose logs [container-name]`
2. Verify `.env` file configuration
3. Ensure Docker Desktop is running
4. Check port availability

