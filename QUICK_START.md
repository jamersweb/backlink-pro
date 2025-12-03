# Quick Start Guide - Docker

## 🚀 Fast Setup (5 minutes)

### 1. Create `.env` file
```bash
# Copy from env.blade.php or create new
cp env.blade.php .env
```

### 2. Update `.env` with these Docker settings:
```env
DB_HOST=mysql
DB_DATABASE=backlink_pro
DB_USERNAME=root
DB_PASSWORD=root
REDIS_HOST=redis
QUEUE_CONNECTION=redis
```

### 3. Build and Start
```bash
docker-compose build
docker-compose up -d
```

### 4. Install Dependencies
```bash
docker-compose exec app composer install
docker-compose exec app npm install
```

### 5. Setup Laravel
```bash
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate
docker-compose exec app chmod -R 775 storage bootstrap/cache
```

### 6. Access Application
- **Web**: http://localhost
- **Horizon**: http://localhost/horizon

---

## 📋 Useful Commands

```bash
# View logs
docker-compose logs -f app

# Run artisan commands
docker-compose exec app php artisan [command]

# Access container
docker-compose exec app bash

# Stop containers
docker-compose stop

# Start containers
docker-compose start

# Restart containers
docker-compose restart
```

---

## ⚠️ Troubleshooting

**Port conflict?** Stop XAMPP/MySQL/Redis on your machine first.

**Container won't start?** Check logs: `docker-compose logs app`

**Database error?** Wait 30 seconds for MySQL to initialize, then retry.

---

See `DOCKER_SETUP.md` for detailed instructions.

