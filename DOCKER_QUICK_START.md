# 🐳 Docker Quick Start Guide

## ✅ Your Docker Containers Status

I can see your Docker containers are running! Here's how to access the site:

---

## 🚀 Access the Site

### Option 1: Via Nginx (Port 80)
**Open your browser and go to:**
- **http://localhost**
- **http://localhost:80**

### Option 2: Direct Laravel Server (if nginx not running)
**http://localhost:8000** (if app container exposes port 8000)

---

## 🔧 Complete Docker Setup

### Step 1: Update .env for Docker

Make sure your `.env` file has Docker settings:

```env
APP_URL=http://localhost

# Database (Docker)
DB_HOST=mysql
DB_DATABASE=backlink_pro
DB_USERNAME=root
DB_PASSWORD=root

# Redis (Docker)
REDIS_HOST=redis
REDIS_PORT=6379
QUEUE_CONNECTION=redis
```

### Step 2: Start All Containers

```bash
# Start all containers
docker-compose up -d

# Check status
docker-compose ps
```

You should see these containers:
- ✅ `backlink-nginx` - Web server (port 80)
- ✅ `backlink-app` - Laravel PHP-FPM
- ✅ `backlink-mysql` - Database
- ✅ `backlink-redis` - Cache/Queue
- ✅ `backlink-queue` - Queue worker
- ✅ `backlink-horizon` - Queue dashboard
- ✅ `backlink-python-worker` - Python automation

### Step 3: Setup Inside Containers

```bash
# Install dependencies
docker-compose exec app composer install
docker-compose exec app npm install

# Build frontend assets
docker-compose exec app npm run build

# Generate app key
docker-compose exec app php artisan key:generate

# Run migrations
docker-compose exec app php artisan migrate

# Seed plans
docker-compose exec app php artisan db:seed --class=PlanSeeder

# Set permissions
docker-compose exec app chmod -R 775 storage bootstrap/cache
```

---

## 🌐 Access Points

### Main Application:
- **http://localhost** (via Nginx)
- **http://localhost:8000** (direct Laravel, if exposed)

### Admin/Management:
- **Horizon Dashboard**: http://localhost/horizon (queue monitoring)

### Pages:
- **Login**: http://localhost/login
- **Register**: http://localhost/register
- **Pricing**: http://localhost/pricing
- **Dashboard**: http://localhost/dashboard (after login)
- **Admin**: http://localhost/admin/dashboard (admin role)

---

## 🔍 Check Container Logs

```bash
# View all logs
docker-compose logs

# View specific container
docker-compose logs app
docker-compose logs nginx
docker-compose logs mysql

# Follow logs (live)
docker-compose logs -f app
```

---

## 🛠️ Common Commands

```bash
# Start containers
docker-compose up -d

# Stop containers
docker-compose stop

# Restart containers
docker-compose restart

# Rebuild containers (after code changes)
docker-compose build
docker-compose up -d

# Access container shell
docker-compose exec app bash
docker-compose exec app php artisan [command]

# View container status
docker-compose ps

# Stop and remove containers
docker-compose down

# Stop and remove containers + volumes (⚠️ deletes data)
docker-compose down -v
```

---

## ⚠️ Troubleshooting

### Port 80 already in use?
- Stop XAMPP Apache if running
- Or change port in `docker-compose.yml`:
  ```yaml
  ports:
    - "8080:80"  # Use port 8080 instead
  ```
  Then access: http://localhost:8080

### Database connection errors?
- Make sure `.env` has `DB_HOST=mysql` (not 127.0.0.1)
- Wait 30 seconds for MySQL to initialize
- Check logs: `docker-compose logs mysql`

### Frontend not loading?
- Build assets: `docker-compose exec app npm run build`
- Or for dev: `docker-compose exec app npm run dev`

### Container won't start?
- Check logs: `docker-compose logs [container-name]`
- Rebuild: `docker-compose build --no-cache`

---

## 📝 Current Status

✅ **Docker Containers**: Running
✅ **Database**: MySQL container active
✅ **Redis**: Running
✅ **Queue Workers**: Active
✅ **Python Worker**: Running

**Next**: Access http://localhost in your browser!


