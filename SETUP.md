# Development Environment Setup Guide

## Prerequisites

- PHP 8.2 or higher
- Composer
- Node.js 18+ and npm
- MySQL 8.0+ or MariaDB
- Redis (optional, for queue/cache)
- Git

---

## Step 1: Clone Repository

```bash
git clone <repository-url>
cd backlink-pro
```

---

## Step 2: Install Dependencies

### PHP Dependencies (Composer)

```bash
composer install
```

### JavaScript Dependencies (npm)

```bash
npm install
```

---

## Step 3: Environment Configuration

### Copy Environment File

```bash
cp .env.example .env
```

### Generate Application Key

```bash
php artisan key:generate
```

### Configure Environment Variables

Edit `.env` file and update:

**Required:**
- `APP_NAME`: Your application name
- `APP_URL`: Your application URL (e.g., `http://localhost:8000`)
- `DB_*`: Database configuration
- `API_TOKEN`: Generate a secure token: `php artisan tinker` then `Str::random(60)`

**Optional (for full functionality):**
- `GOOGLE_CLIENT_ID` & `GOOGLE_CLIENT_SECRET`: For Gmail OAuth
- `STRIPE_KEY` & `STRIPE_SECRET`: For payments
- `LLM_API_KEY`: For content generation
- `2CAPTCHA_API_KEY` or `ANTICAPTCHA_API_KEY`: For captcha solving
- `REDIS_*`: For queue/cache (if using Redis)

---

## Step 4: Database Setup

### Create Database

```sql
CREATE DATABASE backlink_pro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Run Migrations

```bash
php artisan migrate
```

### Seed Database (Optional)

```bash
php artisan db:seed
```

---

## Step 5: Build Frontend Assets

### Development

```bash
npm run dev
```

### Production

```bash
npm run build
```

---

## Step 6: Start Development Server

### Using PHP Built-in Server

```bash
php artisan serve
```

### Using Laravel Sail (Docker)

```bash
./vendor/bin/sail up -d
```

---

## Step 7: Setup Queue Workers (Optional)

### Using Database Queue

```bash
php artisan queue:work
```

### Using Redis Queue

1. Install and start Redis
2. Update `.env`: `QUEUE_CONNECTION=redis`
3. Run: `php artisan queue:work redis`

### Using Supervisor (Production)

See `supervisor/laravel-worker.conf` for configuration.

---

## Step 8: Setup Python Worker (Optional)

### Install Python Dependencies

```bash
cd python
pip install -r requirements.txt
playwright install chromium
```

### Configure Environment

Set environment variables:
- `LARAVEL_API_URL`: Laravel API URL (e.g., `http://localhost:8000`)
- `LARAVEL_API_TOKEN`: API token from `.env`

### Run Python Worker

```bash
python worker.py
```

---

## Step 9: Run Tests

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature

# Run specific test
php artisan test --filter=UserTest
```

---

## Step 10: Access Application

- **Web Application:** http://localhost:8000
- **Health Check:** http://localhost:8000/health
- **Laravel Telescope:** http://localhost:8000/telescope (if installed)

---

## Troubleshooting

### Permission Issues

```bash
# Fix storage permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Database Connection Issues

- Verify database credentials in `.env`
- Ensure MySQL is running
- Check database exists

### Queue Not Processing

- Verify queue connection in `.env`
- Ensure queue worker is running
- Check `storage/logs/laravel.log` for errors

### Frontend Assets Not Loading

```bash
# Rebuild assets
npm run build

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

---

## Development Tools

### Laravel Telescope (Debugging)

Already installed! Access at `/telescope` after running migrations.

### Laravel Tinker (Interactive Shell)

```bash
php artisan tinker
```

### Clear All Caches

```bash
php artisan optimize:clear
```

---

## Next Steps

1. Configure Google OAuth (see `config/services.php`)
2. Set up Stripe keys for payments
3. Configure LLM API for content generation
4. Set up captcha solving service
5. Review `API_DOCUMENTATION.md` for Python worker integration

---

## Additional Resources

- [Laravel Documentation](https://laravel.com/docs)
- [Inertia.js Documentation](https://inertiajs.com)
- [API Documentation](./API_DOCUMENTATION.md)
- [Testing Guide](./TESTING_STATUS.md)

