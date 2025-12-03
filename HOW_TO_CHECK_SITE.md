# ✅ How to Check/View Your Site

## 🚀 Quick Start (3 Steps)

### Step 1: Start Laravel Server
Open a terminal and run:
```bash
cd D:\XAMPP\htdocs\backlink-pro
php artisan serve
```

You should see:
```
INFO  Server running on [http://127.0.0.1:8000]
```

### Step 2: Open Your Browser
Go to: **http://localhost:8000** or **http://127.0.0.1:8000**

### Step 3: Test the Site
- **Login Page**: http://localhost:8000/login
- **Register Page**: http://localhost:8000/register
- **Pricing Page**: http://localhost:8000/pricing

---

## 📍 Available Pages

### Public Pages (No Login Required):
- ✅ **Login**: http://localhost:8000/login
- ✅ **Register**: http://localhost:8000/register  
- ✅ **Pricing**: http://localhost:8000/pricing

### User Pages (Login Required):
- ✅ **Dashboard**: http://localhost:8000/dashboard
- ✅ **Campaigns List**: http://localhost:8000/campaign
- ✅ **Create Campaign**: http://localhost:8000/campaign/create

### Admin Pages (Admin Role Required):
- ✅ **Admin Dashboard**: http://localhost:8000/admin/dashboard

---

## 🔐 Test Login

### Create a Test User:
```bash
php artisan tinker
```

Then run:
```php
$user = App\Models\User::create([
    'name' => 'Test User',
    'email' => 'test@example.com',
    'password' => bcrypt('password'),
]);
$user->assignRole('admin'); // For admin access
```

**Login Credentials:**
- Email: `test@example.com`
- Password: `password`

---

## 🛠️ Development Mode (Hot Reload)

For development with automatic reloading:

**Terminal 1** (Keep running):
```bash
npm run dev
```

**Terminal 2** (Keep running):
```bash
php artisan serve
```

Now when you edit React/JS files, the browser will auto-reload!

---

## ⚠️ Troubleshooting

### "Page not found" or 404 errors?
- ✅ Make sure `php artisan serve` is running
- ✅ Check the terminal for errors
- ✅ Try: http://127.0.0.1:8000 instead of localhost

### Frontend not loading (blank page)?
- ✅ Make sure you ran `npm run build` (already done ✅)
- ✅ Check browser console (F12) for errors
- ✅ Clear browser cache (Ctrl+Shift+R)

### Database errors?
- ✅ Make sure XAMPP MySQL is running
- ✅ Check `.env` file has correct database settings

### Assets not loading?
- ✅ Frontend is already built ✅
- ✅ If you make changes, run `npm run build` again
- ✅ Or use `npm run dev` for development

---

## 📝 Current Status

✅ **Frontend Assets**: Built and ready
✅ **Database**: Migrated and seeded
✅ **Plans**: Created (Free, Starter, Pro, Agency)
✅ **Server**: Ready to start

**Just run**: `php artisan serve` and open http://localhost:8000


