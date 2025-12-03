# 🐳 Access Your Site via Docker

## ✅ Docker Containers Are Running!

Your site is now accessible via Docker. Here's how to check it:

---

## 🌐 **Access the Site**

### **Open your browser and go to:**
- **http://localhost** ← Main site (via Nginx)
- **http://localhost:80** ← Same as above

### **Available Pages:**
- **Login**: http://localhost/login
- **Register**: http://localhost/register
- **Pricing**: http://localhost/pricing
- **Dashboard**: http://localhost/dashboard (after login)
- **Admin**: http://localhost/admin/dashboard (admin role)

---

## 📊 **Check Container Status**

```bash
docker-compose ps
```

You should see all containers running:
- ✅ `backlink-nginx` - Web server
- ✅ `backlink-app` - Laravel application
- ✅ `backlink-mysql` - Database
- ✅ `backlink-redis` - Cache/Queue
- ✅ `backlink-queue` - Queue worker
- ✅ `backlink-horizon` - Queue dashboard
- ✅ `backlink-python-worker` - Python automation

---

## 🔧 **If Site Doesn't Load**

### 1. Check Nginx Container:
```bash
docker-compose logs nginx
```

### 2. Check App Container:
```bash
docker-compose logs app
```

### 3. Restart Containers:
```bash
docker-compose restart nginx app
```

### 4. Rebuild if Needed:
```bash
docker-compose build
docker-compose up -d
```

---

## 🛠️ **Run Commands Inside Docker**

```bash
# Run artisan commands
docker-compose exec app php artisan [command]

# Access container shell
docker-compose exec app bash

# Install dependencies
docker-compose exec app composer install
docker-compose exec app npm install

# Build frontend
docker-compose exec app npm run build
```

---

## ⚠️ **Important Notes**

1. **Port 80**: Make sure XAMPP Apache is stopped (it uses port 80)
2. **Database**: `.env` is now set to `DB_HOST=mysql` for Docker
3. **Frontend**: Assets need to be built inside Docker container

---

## 🚀 **Quick Commands**

```bash
# View logs
docker-compose logs -f app

# Restart everything
docker-compose restart

# Stop everything
docker-compose stop

# Start everything
docker-compose start
```

---

## ✅ **Your Site Should Be Live Now!**

**Go to: http://localhost** 🎉


