# 🎨 How to Check Frontend

## ✅ Quick Access Guide

All containers are running! Here's how to access and check your frontend:

---

## 🌐 **Step 1: Open Your Browser**

### Main Application
**Open in your browser:**
- **http://localhost** ← Main site (via Nginx on port 80)

---

## 📍 **Step 2: Access Key Pages**

### Public Pages (No Login Required)
1. **Homepage**: `http://localhost/`
2. **Login Page**: `http://localhost/login`
3. **Register Page**: `http://localhost/register`
4. **Pricing Page**: `http://localhost/pricing`

### Protected Pages (Login Required)
After logging in, you can access:

1. **Dashboard**: `http://localhost/dashboard`
   - Stats cards
   - Quick actions
   - Recent backlinks

2. **Campaigns List**: `http://localhost/campaign`
   - View all your campaigns
   - Create new campaign button

3. **Create Campaign**: `http://localhost/campaign/create`
   - 7-step wizard form
   - Multi-step campaign creation

4. **View Campaign**: `http://localhost/campaign/{id}`
   - Campaign details
   - Company info
   - Settings

5. **Edit Campaign**: `http://localhost/campaign/{id}/edit`
   - Edit campaign form

### Admin Pages (Admin Role Required)
- **Admin Dashboard**: `http://localhost/admin/dashboard`

### Monitoring
- **Horizon Dashboard**: `http://localhost/horizon`
  - Queue monitoring
  - Job statistics
  - Failed jobs

---

## 🔍 **Step 3: Verify Frontend is Working**

### Check 1: Homepage Loads
1. Open `http://localhost` in your browser
2. You should see the application (login page or homepage)

### Check 2: Frontend Assets Load
1. Open browser DevTools (F12)
2. Go to **Network** tab
3. Refresh the page
4. Look for files loading from `/build/assets/`
5. ✅ If you see `app-*.js` and `app-*.css` loading → Frontend is working!

### Check 3: React/Inertia is Working
1. Open browser DevTools (F12)
2. Go to **Console** tab
3. Look for any errors
4. ✅ No errors = Frontend is working!

### Check 4: Test a Page
1. Go to `http://localhost/pricing`
2. You should see pricing cards with plans
3. ✅ If you see styled cards → Frontend is working!

---

## 🛠️ **Troubleshooting**

### ❌ Page Shows "404" or "Not Found"
**Solution:**
```bash
# Check if containers are running
docker-compose ps

# Restart containers
docker-compose restart nginx app
```

### ❌ Page Loads but No Styles (Plain HTML)
**Solution:**
```bash
# Rebuild frontend assets
docker-compose exec app npm run build

# Or for development (with hot reload)
docker-compose exec app npm run dev
```

### ❌ JavaScript Errors in Console
**Solution:**
```bash
# Check if assets are built
docker-compose exec app ls -la public/build

# Rebuild if missing
docker-compose exec app npm run build
```

### ❌ Can't Access http://localhost
**Check:**
1. Is port 80 in use?
   ```bash
   # Check what's using port 80
   netstat -ano | findstr :80
   ```
2. Stop XAMPP Apache if running
3. Or change port in `docker-compose.yml`:
   ```yaml
   ports:
     - "8080:80"  # Use port 8080 instead
   ```
   Then access: `http://localhost:8080`

---

## 🧪 **Quick Test Checklist**

- [ ] Open `http://localhost` → Page loads
- [ ] Open `http://localhost/login` → Login form appears
- [ ] Open `http://localhost/pricing` → Pricing cards visible
- [ ] Login → Dashboard shows stats
- [ ] Click "Create Campaign" → Form wizard appears
- [ ] Check browser console → No errors
- [ ] Check Network tab → Assets loading from `/build/`

---

## 📱 **Testing Different Pages**

### Test Campaign Creation Flow:
1. Login → `http://localhost/login`
2. Dashboard → `http://localhost/dashboard`
3. Click "Create Campaign" or go to `http://localhost/campaign/create`
4. Fill out the 7-step form
5. Submit → Should redirect to campaigns list

### Test Subscription Flow:
1. Go to `http://localhost/pricing`
2. Click "Subscribe" on a plan
3. Should redirect to Stripe checkout
4. After payment → `http://localhost/subscription/success`
5. After cancel → `http://localhost/subscription/cancel`

---

## 🎯 **What to Look For**

### ✅ **Frontend is Working If:**
- Pages load with styling (not plain HTML)
- Buttons and forms are interactive
- Navigation works
- No JavaScript errors in console
- Assets load from `/build/assets/`

### ❌ **Frontend Issues If:**
- Plain HTML with no styles
- JavaScript errors in console
- Buttons don't work
- 404 errors for asset files
- Blank white page

---

## 🚀 **Quick Commands**

```bash
# Check if frontend assets exist
docker-compose exec app ls -la public/build

# Rebuild frontend (production)
docker-compose exec app npm run build

# Run dev server (with hot reload)
docker-compose exec app npm run dev

# Check nginx logs
docker-compose logs nginx

# Check app logs
docker-compose logs app

# Restart everything
docker-compose restart
```

---

## 📞 **Need Help?**

If frontend isn't working:

1. **Check containers**: `docker-compose ps`
2. **Check logs**: `docker-compose logs app`
3. **Rebuild assets**: `docker-compose exec app npm run build`
4. **Restart**: `docker-compose restart`

---

**Current Status**: ✅ All containers running, frontend assets built!

**Next Step**: Open `http://localhost` in your browser! 🎉

