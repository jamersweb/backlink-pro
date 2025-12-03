# 🚀 Quick Start Guide - Docker & Testing

## ⚡ Quick Commands

### Docker Setup (First Time)

```bash
# 1. Copy environment file
cp .env.example .env

# 2. Update .env with Docker settings (DB_HOST=mysql, REDIS_HOST=redis)

# 3. Build and start containers
docker-compose build
docker-compose up -d

# 4. Install dependencies
docker-compose exec app composer install
docker-compose exec app npm install

# 5. Setup Laravel
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate
docker-compose exec app npm run build

# 6. Access application
# http://localhost
```

### Daily Use

```bash
# Start containers
docker-compose up -d

# Run tests
docker-compose exec app php artisan test

# View logs
docker-compose logs -f app

# Access application
# http://localhost
```

---

## 📚 Detailed Guides

- **Complete Docker Setup**: See `DOCKER_AND_TESTING_GUIDE.md`
- **Step-by-Step Testing**: See `TESTING_STEP_BY_STEP.md`
- **Docker Commands**: See `DOCKER_SETUP.md`

---

## ✅ Current Status

Your Docker containers are **running**:
- ✅ backlink-nginx (Web server)
- ✅ backlink-app (Laravel)
- ✅ backlink-mysql (Database)
- ✅ backlink-redis (Cache/Queue)
- ✅ backlink-queue (Queue worker)
- ✅ backlink-horizon (Queue dashboard)
- ✅ backlink-python-worker (Python automation)

**Access**: http://localhost

---

## 🧪 Testing - Quick Steps

### Step 1: Verify Setup

```bash
# Check containers
docker-compose ps

# Should show all containers as "Up"
```

### Step 2: Run Tests

```bash
# Run all tests
docker-compose exec app php artisan test

# Expected: ✅ 41 tests passing, 68 assertions
```

### Step 3: Verify Results

```
✅ 41 tests passing
✅ 68 assertions
✅ Duration: ~4 seconds
```

---

## 🔧 Common Commands

```bash
# Docker
docker-compose up -d          # Start containers
docker-compose stop            # Stop containers
docker-compose restart         # Restart containers
docker-compose logs -f app     # View logs
docker-compose exec app bash   # Access container

# Laravel
docker-compose exec app php artisan migrate
docker-compose exec app php artisan test
docker-compose exec app composer install

# Testing
docker-compose exec app php artisan test                    # All tests
docker-compose exec app php artisan test --testsuite=Unit  # Unit tests only
docker-compose exec app php artisan test --filter=UserTest  # Specific test
```

---

## 📖 Full Documentation

For complete instructions, see:
- `DOCKER_AND_TESTING_GUIDE.md` - Complete Docker & testing guide
- `TESTING_STEP_BY_STEP.md` - Detailed testing instructions
- `DOCKER_SETUP.md` - Docker setup reference
- `API_DOCUMENTATION.md` - Python worker API docs
- `SETUP.md` - Development setup guide
