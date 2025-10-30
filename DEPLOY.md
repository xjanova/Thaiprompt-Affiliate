# 📦 Deployment Instructions

## 🚀 Initial Setup (First Time Deploy)

### 1. Clone Repository
```bash
git clone <repository-url>
cd Thaiprompt-Affiliate
```

### 2. Install Dependencies
```bash
# Increase timeout if needed
export COMPOSER_PROCESS_TIMEOUT=300

# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Install Node dependencies (if needed)
npm install
npm run build
```

### 3. Environment Setup
```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure your .env file with database credentials
nano .env
```

### 4. Database Setup
```bash
# Run migrations
php artisan migrate --force

# Seed database (if needed)
php artisan db:seed --force
```

### 5. **⚠️ IMPORTANT: Create Storage Symlink**
```bash
# This is CRITICAL for logo, favicon, and slider images to work
php artisan storage:link

# Verify the symlink was created
ls -la public/storage
```

### 6. Set Permissions
```bash
# Set storage permissions
chmod -R 775 storage bootstrap/cache

# Set ownership (adjust user/group as needed)
chown -R www-data:www-data storage bootstrap/cache
```

### 7. Optimize Laravel
```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize autoloader
composer dump-autoload --optimize
```

---

## 🔄 Regular Deployment (Updates)

### 1. Pull Latest Code
```bash
cd /path/to/Thaiprompt-Affiliate
git pull origin <branch-name>
```

### 2. Update Dependencies
```bash
# Update PHP dependencies
composer install --no-dev --optimize-autoloader

# Update Node dependencies (if needed)
npm install
npm run build
```

### 3. Run Migrations
```bash
php artisan migrate --force
```

### 4. **⚠️ CRITICAL: Check Storage Link**
```bash
# If symlink is missing, recreate it
php artisan storage:link

# Verify symlink exists
ls -la public/storage
```

### 5. Clear All Caches
```bash
# Clear all caches
php artisan optimize:clear

# Or clear individually
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### 6. Re-optimize
```bash
# Re-cache everything
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 7. Restart Services
```bash
# Restart PHP-FPM (if using)
sudo systemctl restart php8.3-fpm

# Restart Web Server
sudo systemctl restart nginx
# OR
sudo systemctl restart apache2
```

---

## 🖼️ Important: File Storage

### Storage Structure
```
storage/app/public/
├── branding/          # Logo & Favicon files
│   ├── logo_xxx.png
│   └── favicon_xxx.ico
└── sliders/           # Slider images
    ├── slider_xxx.jpg
    └── slider_yyy.png
```

### Why Files Don't Disappear Anymore

**Old System (❌ DEPRECATED):**
- Files stored in `public/uploads/`
- Folder not in Git
- Files lost after each deployment

**New System (✅ CURRENT):**
- Files stored in `storage/app/public/`
- Linked via `public/storage` symlink
- `storage/app/public/` persists across deployments
- **MUST run `php artisan storage:link` after deploy**

### Troubleshooting Missing Images

If logo/favicon/sliders don't show after deployment:

1. **Check symlink exists:**
   ```bash
   ls -la public/storage
   # Should show: public/storage -> ../storage/app/public
   ```

2. **If missing, recreate:**
   ```bash
   php artisan storage:link
   ```

3. **Check permissions:**
   ```bash
   chmod -R 775 storage/app/public
   chown -R www-data:www-data storage/app/public
   ```

4. **Check files exist:**
   ```bash
   ls -la storage/app/public/branding/
   ls -la storage/app/public/sliders/
   ```

5. **Clear browser cache:**
   - Hard refresh: `Ctrl + Shift + R` (Windows/Linux)
   - Hard refresh: `Cmd + Shift + R` (Mac)
   - Or use Incognito mode

---

## 📋 Deployment Checklist

- [ ] Pull latest code
- [ ] Install/update dependencies (`composer install`)
- [ ] Run migrations (`php artisan migrate --force`)
- [ ] **Create storage link** (`php artisan storage:link`) ⚠️
- [ ] Clear caches (`php artisan optimize:clear`)
- [ ] Re-optimize (`php artisan config:cache`, etc.)
- [ ] Restart services (PHP-FPM, Nginx/Apache)
- [ ] Test website in browser
- [ ] Verify logo/favicon display correctly
- [ ] Clear browser cache and test again

---

## 🔧 Common Issues & Solutions

### Issue: "curl error 28" during composer install
**Solution:**
```bash
export COMPOSER_PROCESS_TIMEOUT=300
composer clear-cache
composer install --no-dev --optimize-autoloader --prefer-dist
```

### Issue: Logo/Favicon not showing
**Solution:**
```bash
php artisan storage:link
chmod -R 775 storage/app/public
```

### Issue: Blade syntax error
**Solution:**
```bash
php artisan view:clear
php artisan optimize:clear
```

### Issue: Route not found
**Solution:**
```bash
php artisan route:clear
php artisan route:cache
```

---

## 🎯 Production Best Practices

1. **Always backup database before deployment**
   ```bash
   mysqldump -u user -p database > backup_$(date +%Y%m%d_%H%M%S).sql
   ```

2. **Use environment variables for sensitive data**
   - Never commit `.env` file
   - Use `.env.example` as template

3. **Monitor logs during deployment**
   ```bash
   tail -f storage/logs/laravel.log
   ```

4. **Test in staging environment first**

5. **Keep `storage/app/public/` outside of Git**
   - Already configured in `.gitignore`
   - Files persist across deployments

6. **Run `php artisan storage:link` on every new server**

---

## 📞 Support

If you encounter issues during deployment:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Check web server logs: `/var/log/nginx/error.log`
3. Verify permissions and symlinks
4. Clear all caches
5. Restart services

---

**Last Updated:** 2025-01-30
**Laravel Version:** 11.46.1
**PHP Version:** 8.3.27
