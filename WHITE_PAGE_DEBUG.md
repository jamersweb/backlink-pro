# 🔍 Debugging White Page Issue - Admin Dashboard

## Common Causes & Solutions

### 1. **Check Browser Console (F12)**
Open browser DevTools (F12) and check the Console tab for JavaScript errors.

**Common errors:**
- `Cannot find module` - Component path issue
- `React is not defined` - React not loading
- `Inertia is not defined` - Inertia not loading
- `Failed to load resource` - Assets not found

### 2. **Verify Vite Assets Are Loading**

**Check Network Tab:**
- Open DevTools → Network tab
- Refresh the page
- Look for:
  - `app.js` or `app-[hash].js` - Should load successfully
  - `app.css` or `app-[hash].css` - Should load successfully
  - Any 404 errors?

### 3. **Development vs Production Mode**

**If using Docker/Nginx:**
- Make sure `npm run build` was run (production build)
- Assets should be in `public/build/` directory

**If using Laravel server:**
- Run `npm run dev` in a separate terminal (development mode)
- This enables hot reload and proper asset serving

### 4. **Check Component Path**

The component should be at:
- `resources/js/Pages/Admin/Dashboard.jsx`

Inertia looks for: `Admin/Dashboard` → `Pages/Admin/Dashboard.jsx`

### 5. **Verify Inertia Middleware**

Check that `HandleInertiaRequests` middleware is active:
- File: `app/Http/Middleware/HandleInertiaRequests.php`
- Registered in: `bootstrap/app.php`

### 6. **Quick Test - Add Simple Component**

Try accessing a simpler page first:
- `/dashboard` (user dashboard) - Does this work?
- If user dashboard works but admin doesn't, it's a component-specific issue

### 7. **Check Server Response**

In browser DevTools → Network tab:
- Find the request to `/admin/dashboard`
- Check Response tab
- Should see HTML with `<div id="app" data-page="...">` containing JSON

---

## 🔧 Quick Fixes

### Fix 1: Rebuild Assets
```bash
npm run build
php artisan view:clear
php artisan config:clear
```

### Fix 2: Start Vite Dev Server
```bash
# Terminal 1
npm run dev

# Terminal 2  
php artisan serve
```

### Fix 3: Check File Permissions
```bash
chmod -R 775 storage bootstrap/cache
chmod -R 775 public/build
```

### Fix 4: Verify Component Export
Make sure `Dashboard.jsx` has:
```jsx
export default function AdminDashboard({ ... }) {
    return (...);
}
```

---

## 🐛 Debug Steps

1. **Open browser console (F12)**
2. **Go to Network tab**
3. **Refresh `/admin/dashboard`**
4. **Check for:**
   - Red errors in Console
   - Failed requests in Network
   - Missing JavaScript files
   - 404 errors

5. **Check Response:**
   - Click on `/admin/dashboard` request
   - View Response tab
   - Should see HTML with Inertia data

6. **Check Sources:**
   - Go to Sources tab
   - Look for `app.js` or `app-[hash].js`
   - Verify it's loading

---

## ✅ Expected Behavior

When working correctly:
1. Page loads HTML from Laravel
2. HTML contains `<div id="app" data-page="...">` with JSON
3. JavaScript loads (`app.js`)
4. React/Inertia initializes
5. Component renders

---

## 📝 Next Steps

1. Check browser console for errors
2. Verify assets are loading
3. Try development mode: `npm run dev`
4. Check if other Inertia pages work
5. Share console errors if still not working

