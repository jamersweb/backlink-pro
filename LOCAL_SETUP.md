# Local Setup Guide (XAMPP)

## 🚀 How to View/Check the Site

### Option 1: Using Laravel Development Server (Recommended)

1. **Build Frontend Assets** (First time only):
   ```bash
   npm install
   npm run build
   ```
   
   Or for development with hot reload:
   ```bash
   npm run dev
   ```
   (Keep this terminal open - it will watch for changes)

2. **Start Laravel Server** (in a new terminal):
   ```bash
   php artisan serve
   ```

3. **Access the Site**:
   - Open your browser and go to: **http://localhost:8000**
   - Or: **http://127.0.0.1:8000**

### Option 2: Using XAMPP Apache

1. **Build Frontend Assets**:
   ```bash
   npm install
   npm run build
   ```

2. **Configure XAMPP Virtual Host** (optional):
   - Edit `C:\xampp\apache\conf\extra\httpd-vhosts.conf`
   - Add:
     ```apache
     <VirtualHost *:80>
         DocumentRoot "D:/XAMPP/htdocs/backlink-pro/public"
         ServerName backlink-pro.test
         <Directory "D:/XAMPP/htdocs/backlink-pro/public">
             AllowOverride All
             Require all granted
         </Directory>
     </VirtualHost>
     ```
   - Add to `C:\Windows\System32\drivers\etc\hosts`:
     ```
     127.0.0.1    backlink-pro.test
     ```
   - Restart Apache

3. **Access the Site**:
   - **http://backlink-pro.test** (if virtual host configured)
   - Or: **http://localhost/backlink-pro/public**

---

## 📍 Available Routes

### Public Routes:
- **Login**: http://localhost:8000/login
- **Register**: http://localhost:8000/register
- **Pricing**: http://localhost:8000/pricing

### Protected Routes (after login):
- **Dashboard**: http://localhost:8000/dashboard
- **Campaigns**: http://localhost:8000/campaign
- **Create Campaign**: http://localhost:8000/campaign/create

### Admin Routes (admin role required):
- **Admin Dashboard**: http://localhost:8000/admin/dashboard

---

## 🔧 Quick Start Commands

```bash
# 1. Install dependencies (if not done)
npm install
composer install

# 2. Build frontend assets
npm run build

# 3. Start development server
php artisan serve

# 4. Open browser
# Go to: http://localhost:8000
```

---

## 🐛 Troubleshooting

### Frontend not loading?
- Make sure you ran `npm run build` or `npm run dev`
- Check browser console for errors
- Clear browser cache

### 404 errors?
- Make sure Laravel server is running (`php artisan serve`)
- Check `.env` file has correct `APP_URL`

### Database errors?
- Make sure XAMPP MySQL is running
- Check `.env` database credentials match XAMPP

### Assets not found?
- Run `npm run build` to compile assets
- Or run `npm run dev` for development mode

---

## 📝 Development Mode

For active development with hot reload:

**Terminal 1** (Frontend):
```bash
npm run dev
```

**Terminal 2** (Backend):
```bash
php artisan serve
```

This will automatically reload when you change React/JS files.


