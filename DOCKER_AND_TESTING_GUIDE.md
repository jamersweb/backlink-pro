# 🐳 Complete Docker Setup & Testing Guide

## Table of Contents
1. [Prerequisites](#prerequisites)
2. [Step 1: Docker Setup](#step-1-docker-setup)
3. [Step 2: Testing](#step-2-testing)
4. [Step 3: Running the Application](#step-3-running-the-application)
5. [Troubleshooting](#troubleshooting)

---

## Prerequisites

Before starting, ensure you have:

- ✅ **Docker Desktop** installed and running
  - Download: https://www.docker.com/products/docker-desktop
  - Verify: `docker --version` and `docker-compose --version`
- ✅ **Git** installed
- ✅ **At least 4GB RAM** available for Docker
- ✅ **Ports available**: 80, 3306, 6379 (or change in docker-compose.yml)

---

## Step 1: Docker Setup

### 1.1 Clone/Download Project

```bash
# If using Git
git clone <repository-url>
cd backlink-pro

# OR if you already have the project
cd backlink-pro
```

### 1.2 Configure Environment

```bash
# Copy environment template
cp .env.example .env

# OR if .env.example doesn't exist, create from env.blade.php
cp env.blade.php .env
```

### 1.3 Update .env File for Docker

Edit `.env` file and ensure these Docker-specific settings:

```env
APP_NAME="Auto Backlink Pro"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

# Database (Docker - IMPORTANT: Use 'mysql' as host, not '127.0.0.1')
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=backlink_pro
DB_USERNAME=root
DB_PASSWORD=root

# Redis (Docker - Use 'redis' as host)
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=null

# Queue (Use Redis for better performance)
QUEUE_CONNECTION=redis

# Python Worker API Token (generate a secure token)
PYTHON_API_TOKEN=your-secure-api-token-here-change-this
API_TOKEN=your-secure-api-token-here-change-this

# Gmail OAuth (configure later)
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=http://localhost/gmail/oauth/callback

# Stripe (configure later)
STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=
```

### 1.4 Build Docker Containers

```bash
# Build all Docker images (first time - takes 5-10 minutes)
docker-compose build

# Check if build was successful
docker-compose ps
```

### 1.5 Start Docker Containers

```bash
# Start all containers in detached mode
docker-compose up -d

# Check container status
docker-compose ps
```

You should see these containers running:
- ✅ `backlink-nginx` - Web server (port 80)
- ✅ `backlink-app` - Laravel PHP-FPM
- ✅ `backlink-mysql` - Database
- ✅ `backlink-redis` - Cache/Queue
- ✅ `backlink-queue` - Queue worker
- ✅ `backlink-horizon` - Queue dashboard
- ✅ `backlink-python-worker` - Python automation

### 1.6 Install Dependencies

```bash
# Install PHP dependencies (Composer)
docker-compose exec app composer install

# Install Node.js dependencies
docker-compose exec app npm install
```

### 1.7 Laravel Setup

```bash
# Generate application key
docker-compose exec app php artisan key:generate

# Run database migrations
docker-compose exec app php artisan migrate

# (Optional) Seed database with initial data
docker-compose exec app php artisan db:seed

# Set storage permissions
docker-compose exec app chmod -R 775 storage bootstrap/cache
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
```

### 1.8 Build Frontend Assets

```bash
# For development (watch mode)
docker-compose exec app npm run dev

# OR for production build
docker-compose exec app npm run build
```

**Note:** For development, run `npm run dev` in a separate terminal to watch for changes.

---

## Step 2: Testing

### 2.1 Verify Docker Containers Are Running

```bash
# Check all containers are up
docker-compose ps

# Should show all containers as "Up" or "running"
```

### 2.2 Run All Tests

```bash
# Run all tests inside Docker container
docker-compose exec app php artisan test

# Expected output:
# ✅ 41 tests passing
# ✅ 68 assertions
# ✅ 0 failures
```

### 2.3 Run Specific Test Suites

```bash
# Run only Unit tests
docker-compose exec app php artisan test --testsuite=Unit

# Run only Feature tests
docker-compose exec app php artisan test --testsuite=Feature

# Run specific test file
docker-compose exec app php artisan test --filter=UserTest

# Run specific test method
docker-compose exec app php artisan test --filter=test_user_can_login
```

### 2.4 Test Coverage Report

```bash
# Generate test coverage (requires Xdebug)
docker-compose exec app php artisan test --coverage

# Or with minimum coverage threshold
docker-compose exec app php artisan test --coverage --min=80
```

### 2.5 Test Database Reset (if needed)

If tests fail due to database issues:

```bash
# Reset test database
docker-compose exec app php artisan migrate:fresh --env=testing

# OR use the reset script
docker-compose exec app php tests/ResetTestDatabase.php
```

### 2.6 Common Test Commands

```bash
# Run tests with verbose output
docker-compose exec app php artisan test --verbose

# Run tests and stop on first failure
docker-compose exec app php artisan test --stop-on-failure

# Run tests with specific filter
docker-compose exec app php artisan test --filter="Campaign"

# Run tests and show coverage
docker-compose exec app php artisan test --coverage-text
```

### 2.7 Test Categories

#### Unit Tests
- ✅ Model tests (User, Campaign, Domain, Plan)
- ✅ Service tests (GmailService, LLMContentService, BacklinkVerificationService, ExportService)
- ✅ Job tests (ScheduleCampaignJob)

#### Feature Tests
- ✅ Authentication (Login, Register, Logout)
- ✅ Campaign CRUD operations
- ✅ Gmail OAuth flow

#### Expected Test Results

```
✅ 41 tests passing
✅ 68 assertions
✅ Duration: ~4 seconds
```

---

## Step 3: Running the Application

### 3.1 Access the Application

After setup, access the application:

- **Main Application**: http://localhost
- **Health Check**: http://localhost/health
- **Laravel Horizon**: http://localhost/horizon (queue dashboard)

### 3.2 Verify Health Check

```bash
# Check health endpoint
curl http://localhost/health

# Should return JSON with status: "ok"
```

### 3.3 View Logs

```bash
# View all logs
docker-compose logs

# View specific container logs
docker-compose logs app
docker-compose logs nginx
docker-compose logs mysql
docker-compose logs queue

# Follow logs in real-time
docker-compose logs -f app
```

### 3.4 Daily Development Workflow

```bash
# 1. Start containers (if stopped)
docker-compose up -d

# 2. Check container status
docker-compose ps

# 3. Run migrations (if needed)
docker-compose exec app php artisan migrate

# 4. Start frontend dev server (in separate terminal)
docker-compose exec app npm run dev

# 5. Access application
# http://localhost
```

---

## Troubleshooting

### Port Already in Use

**Problem**: Port 80, 3306, or 6379 is already in use (XAMPP, MySQL, Redis)

**Solution 1**: Stop conflicting services
```bash
# Stop XAMPP Apache
# Stop local MySQL service
# Stop local Redis service
```

**Solution 2**: Change ports in `docker-compose.yml`
```yaml
nginx:
  ports:
    - "8080:80"  # Use port 8080 instead

mysql:
  ports:
    - "3307:3306"  # Use port 3307 instead
```

Then access: http://localhost:8080

### Database Connection Errors

**Problem**: Cannot connect to database

**Solution**:
```bash
# 1. Check MySQL container is running
docker-compose ps mysql

# 2. Check MySQL logs
docker-compose logs mysql

# 3. Wait for MySQL to initialize (30-60 seconds on first start)
docker-compose exec app php artisan migrate

# 4. Verify .env has correct settings
# DB_HOST=mysql (not 127.0.0.1)
# DB_DATABASE=backlink_pro
# DB_USERNAME=root
# DB_PASSWORD=root
```

### Tests Failing

**Problem**: Tests fail with database errors

**Solution**:
```bash
# 1. Reset test database
docker-compose exec app php artisan migrate:fresh --env=testing

# 2. Clear test cache
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan cache:clear

# 3. Run tests again
docker-compose exec app php artisan test
```

### Container Won't Start

**Problem**: Container fails to start

**Solution**:
```bash
# 1. Check logs
docker-compose logs [container-name]

# 2. Rebuild container
docker-compose build --no-cache [container-name]
docker-compose up -d [container-name]

# 3. Check Docker resources
docker stats
```

### Permission Issues

**Problem**: Storage or cache permission errors

**Solution**:
```bash
# Fix permissions inside container
docker-compose exec app chmod -R 775 storage bootstrap/cache
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
```

### Frontend Assets Not Loading

**Problem**: CSS/JS files not loading

**Solution**:
```bash
# Rebuild frontend assets
docker-compose exec app npm run build

# OR for development
docker-compose exec app npm run dev

# Clear Laravel cache
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan view:clear
```

### Queue Worker Not Processing Jobs

**Problem**: Jobs stuck in queue

**Solution**:
```bash
# 1. Check queue worker logs
docker-compose logs queue

# 2. Restart queue worker
docker-compose restart queue

# 3. Check Horizon dashboard
# http://localhost/horizon
```

---

## Quick Reference Commands

### Docker Commands

```bash
# Start containers
docker-compose up -d

# Stop containers
docker-compose stop

# Restart containers
docker-compose restart

# View logs
docker-compose logs -f app

# Access container shell
docker-compose exec app bash

# Rebuild containers
docker-compose build

# Stop and remove containers
docker-compose down

# Stop and remove containers + volumes (⚠️ deletes data)
docker-compose down -v
```

### Laravel Commands (inside container)

```bash
# Artisan commands
docker-compose exec app php artisan migrate
docker-compose exec app php artisan tinker
docker-compose exec app php artisan test

# Composer commands
docker-compose exec app composer install
docker-compose exec app composer update

# NPM commands
docker-compose exec app npm install
docker-compose exec app npm run build
docker-compose exec app npm run dev
```

### Testing Commands

```bash
# Run all tests
docker-compose exec app php artisan test

# Run specific test
docker-compose exec app php artisan test --filter=UserTest

# Run with coverage
docker-compose exec app php artisan test --coverage

# Reset test database
docker-compose exec app php artisan migrate:fresh --env=testing
```

---

## Next Steps After Setup

1. ✅ **Configure Gmail OAuth**
   - Get credentials from Google Cloud Console
   - Update `.env` with `GOOGLE_CLIENT_ID` and `GOOGLE_CLIENT_SECRET`

2. ✅ **Configure Stripe**
   - Get keys from Stripe Dashboard
   - Update `.env` with Stripe keys

3. ✅ **Set up LLM API**
   - Get API key from OpenAI or DeepSeek
   - Update `.env` with `LLM_API_KEY`

4. ✅ **Configure Captcha Service**
   - Get API key from 2Captcha or AntiCaptcha
   - Update `.env` with captcha API key

5. ✅ **Create Admin User**
   ```bash
   docker-compose exec app php artisan tinker
   # Then create admin user in tinker
   ```

---

## Support

If you encounter issues:

1. Check container logs: `docker-compose logs [container-name]`
2. Verify `.env` file configuration
3. Ensure Docker Desktop is running
4. Check port availability
5. Review this guide's troubleshooting section

---

## Summary Checklist

- [ ] Docker Desktop installed and running
- [ ] Project cloned/downloaded
- [ ] `.env` file created and configured
- [ ] Docker containers built (`docker-compose build`)
- [ ] Containers started (`docker-compose up -d`)
- [ ] Dependencies installed (composer, npm)
- [ ] Application key generated
- [ ] Migrations run
- [ ] Frontend assets built
- [ ] Tests passing (41 tests)
- [ ] Application accessible at http://localhost
- [ ] Health check working at http://localhost/health

**🎉 You're all set!**

