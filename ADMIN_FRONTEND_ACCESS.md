# 🔐 Admin Dashboard & Frontend Access Guide

## ✅ Fixed Issues

1. **Removed `@routes` directive** - This was causing the literal text to appear. Ziggy package is not installed, so the directive was removed from `app.blade.php`
2. **Frontend assets rebuilt** - All React/Inertia components are compiled and ready
3. **Caches cleared** - Route, config, and view caches cleared

---

## 🚀 How to Access Admin Dashboard

### Step 1: Start Your Development Server

**Option A: Using Docker (Recommended)**
```bash
# Make sure Docker containers are running
docker-compose up -d

# Access via: http://localhost
```

**Option B: Using Laravel Built-in Server**
```bash
# Terminal 1: Start Laravel server
php artisan serve

# Terminal 2: Start Vite dev server (for hot reload)
npm run dev

# Access via: http://localhost:8000
```

### Step 2: Login as Admin

1. **Open your browser** and go to:
   - `http://localhost/login` (Docker)
   - `http://localhost:8000/login` (Laravel server)

2. **Login Credentials:**
   ```
   Email: admin@example.com
   Password: admin123
   ```

3. **After login**, you'll be automatically redirected to:
   - Admin Dashboard: `http://localhost/admin/dashboard`

---

## 📍 Available Admin Routes

### Admin Dashboard
- **URL**: `/admin/dashboard`
- **Features**: 
  - Total Users count
  - Active Campaigns count
  - Total Backlinks count
  - Pending Tasks count
  - Recent Campaigns list
  - Recent Backlinks list

### Admin Campaigns Management
- **List**: `/admin/campaigns`
- **Create**: `/admin/campaigns/create`
- **Show**: `/admin/campaigns/{id}`
- **Edit**: `/admin/campaigns/{id}/edit`

### Admin Locations Management
- **Create Location**: `/admin/locations/create`
- **Get States**: `/admin/locations/states/{country}`
- **Get Cities**: `/admin/locations/cities/{state}`

---

## 🌐 Frontend Development Mode

### For Development with Hot Reload:

**Terminal 1** (Keep running):
```bash
npm run dev
```

**Terminal 2** (Keep running):
```bash
php artisan serve
```

Now when you edit React/JSX files, the browser will auto-reload!

### For Production Build:

```bash
npm run build
```

This compiles all frontend assets for production use.

---

## 🔍 Troubleshooting

### Issue: Still seeing `@routes` text?
- ✅ **Fixed**: Removed `@routes` directive from `app.blade.php`
- Clear browser cache: `Ctrl + Shift + R` (Windows) or `Cmd + Shift + R` (Mac)

### Issue: Blank page or 404?
- ✅ Make sure `php artisan serve` is running
- ✅ Check terminal for errors
- ✅ Verify you're logged in as admin user
- ✅ Check browser console (F12) for JavaScript errors

### Issue: Admin dashboard not loading?
- ✅ Verify admin user exists:
  ```bash
  php artisan tinker
  ```
  Then:
  ```php
  $admin = App\Models\User::where('email', 'admin@example.com')->first();
  $admin->hasRole('admin'); // Should return true
  ```

### Issue: Frontend assets not loading?
- ✅ Run `npm run build` to compile assets
- ✅ Check `public/build/` directory exists
- ✅ Verify Vite is configured correctly in `vite.config.js`

---

## 📝 Quick Test Commands

```bash
# Check if admin user exists
php artisan tinker
# Then: App\Models\User::where('email', 'admin@example.com')->first();

# Clear all caches
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Rebuild frontend
npm run build

# Check routes
php artisan route:list --path=admin
```

---

## ✅ What's Working

- ✅ Admin login with credentials
- ✅ Admin dashboard route (`/admin/dashboard`)
- ✅ Inertia.js React components
- ✅ Admin layout with navigation
- ✅ Stats cards (Users, Campaigns, Backlinks, Tasks)
- ✅ Recent campaigns and backlinks lists
- ✅ Frontend assets compiled and ready

---

## 🎯 Next Steps

1. **Access the admin dashboard** using the credentials above
2. **Test all admin routes** to ensure they work
3. **Create test data** if needed to see stats populate
4. **Customize the dashboard** as needed

---

**Admin Credentials Summary:**
- **Email**: `admin@example.com`
- **Password**: `admin123`
- **Dashboard URL**: `http://localhost/admin/dashboard`

