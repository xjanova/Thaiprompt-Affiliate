# Arrow X Theme System - Deployment Guide

> **Complete Deployment Checklist and Configuration Guide**
>
> **Version:** 1.0.0 | **Last Updated:** 2025-11-15

---

## 📋 Table of Contents

1. [Pre-Deployment Checklist](#pre-deployment-checklist)
2. [Environment Requirements](#environment-requirements)
3. [Deployment Steps](#deployment-steps)
4. [Post-Deployment Verification](#post-deployment-verification)
5. [Production Configuration](#production-configuration)
6. [Performance Optimization](#performance-optimization)
7. [Rollback Procedures](#rollback-procedures)
8. [Troubleshooting](#troubleshooting)
9. [Monitoring & Maintenance](#monitoring--maintenance)

---

## Pre-Deployment Checklist

### 📝 Code Review Checklist

- [ ] All migrations tested locally
- [ ] All seeders tested and synchronized with DatabaseSeeder.php
- [ ] All tests passing (`php artisan test`)
- [ ] Code formatted with Laravel Pint (`./vendor/bin/pint`)
- [ ] No debug code or `dd()` statements
- [ ] All TODO comments resolved or documented
- [ ] Git hooks installed and passing
- [ ] Documentation updated

### 🗃️ Database Checklist

- [ ] Database backup created
- [ ] Migration files reviewed for `Schema::hasTable()` checks
- [ ] Seeders are idempotent (can run multiple times safely)
- [ ] Foreign key constraints validated
- [ ] Index names under 64 characters
- [ ] No hardcoded IDs in seeders

### 🎨 Frontend Checklist

- [ ] Assets compiled (`npm run build`)
- [ ] CSS/JS minified for production
- [ ] No console.log() in production code
- [ ] Images optimized
- [ ] Fonts loaded correctly
- [ ] Dark/Light mode tested
- [ ] Responsive design tested (mobile/tablet/desktop)

### 🔐 Security Checklist

- [ ] `.env` file configured correctly
- [ ] `APP_DEBUG=false` in production
- [ ] Strong `APP_KEY` generated
- [ ] Database credentials secure
- [ ] API keys stored in `.env` (not committed)
- [ ] CSRF protection enabled
- [ ] XSS protection in place
- [ ] SQL injection prevention verified

### ⚡ Performance Checklist

- [ ] Cache drivers configured (Redis recommended)
- [ ] Queue workers configured
- [ ] Session driver optimized
- [ ] Opcache enabled
- [ ] CDN configured (if applicable)

---

## Environment Requirements

### Server Requirements

**Minimum Requirements:**
```
PHP: 8.1+
MySQL: 8.0+ or MariaDB 10.3+
Memory: 512MB RAM
Storage: 1GB free space
```

**Recommended Requirements:**
```
PHP: 8.2+
MySQL: 8.0+
Memory: 2GB RAM
Storage: 5GB free space
Redis: Latest
```

### PHP Extensions Required

```bash
# Required extensions
php -m | grep -E 'openssl|pdo|mbstring|tokenizer|xml|ctype|json|bcmath|fileinfo|gd|curl|zip'

# Required extensions list:
✓ OpenSSL
✓ PDO (MySQL)
✓ Mbstring
✓ Tokenizer
✓ XML
✓ Ctype
✓ JSON
✓ BCMath
✓ Fileinfo
✓ GD or Imagick
✓ cURL
✓ Zip
```

### Node.js Requirements

```bash
node --version  # v18+ recommended
npm --version   # v9+ recommended
```

### Database Setup

**Create Database:**
```sql
CREATE DATABASE thaiprompt_affiliate
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
```

**Create User:**
```sql
CREATE USER 'tpaffil'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON thaiprompt_affiliate.* TO 'tpaffil'@'localhost';
FLUSH PRIVILEGES;
```

---

## Deployment Steps

### Method 1: Using deploy.sh Script (Recommended)

```bash
# 1. Navigate to project directory
cd /path/to/Thaiprompt-Affiliate

# 2. Backup current installation
./backup.sh  # If available

# 3. Pull latest changes
git pull origin main

# 4. Run deployment script
./deploy.sh

# The script will automatically:
# - Enable maintenance mode
# - Backup database
# - Update composer dependencies
# - Run migrations
# - Build frontend assets
# - Clear and optimize caches
# - Disable maintenance mode
```

### Method 2: Manual Deployment

```bash
# 1. Enable maintenance mode
php artisan down --message="Deploying Arrow X Theme System..." --retry=60

# 2. Pull latest code
git pull origin main

# 3. Update dependencies
composer install --no-dev --optimize-autoloader
npm ci

# 4. Run migrations
php artisan migrate --force

# 5. Run Arrow X seeder (if first deployment)
php artisan db:seed --class=ArrowXThemeSeeder --force

# 6. Build assets
npm run build

# 7. Clear and optimize caches
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 8. Compile Arrow X theme
php artisan arrowx:compile --all

# 9. Warm up Arrow X cache
php artisan arrowx:warmup

# 10. Fix permissions
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# 11. Restart queue workers (if applicable)
php artisan queue:restart

# 12. Disable maintenance mode
php artisan up
```

### Method 3: Zero-Downtime Deployment (Advanced)

For production environments requiring zero downtime, use blue-green deployment:

```bash
# 1. Deploy to green environment
rsync -avz --exclude='.git' --exclude='node_modules' \
  /current/ /green/

# 2. Update green environment
cd /green
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan arrowx:compile --all

# 3. Switch traffic from blue to green
# (Update load balancer or symlink)

# 4. Verify green environment
curl -I https://yoursite.com

# 5. Keep blue as rollback option (24-48 hours)
```

---

## Post-Deployment Verification

### ✅ Automated Tests

```bash
# Run test suite
php artisan test

# Run specific Arrow X tests
php artisan test --filter=ThemeCompiler
php artisan test --filter=ComponentService

# Run benchmark
php artisan arrowx:benchmark --iterations=5
```

### ✅ Manual Verification Checklist

**Admin Panel:**
- [ ] Login to admin panel successful
- [ ] Navigate to `/admin/arrow-x-theme`
- [ ] Dashboard loads without errors
- [ ] General Settings page works
- [ ] Color Settings page works
- [ ] RGB Effects page works
- [ ] Typography page works
- [ ] Cache compile button works
- [ ] Clear cache button works

**Frontend:**
- [ ] Homepage loads correctly
- [ ] Arrow X styles applied
- [ ] Dark/Light mode toggle works
- [ ] Language switcher works (14 languages)
- [ ] RGB effects visible (if enabled)
- [ ] Responsive design works (mobile/tablet/desktop)
- [ ] No console errors in browser

**Database:**
- [ ] 7 Arrow X tables exist:
  - `theme_settings`
  - `theme_colors`
  - `theme_rgb_effects`
  - `theme_typography`
  - `theme_components`
  - `translation_caches`
  - `google_translate_settings`
- [ ] Default theme seeded
- [ ] Active theme exists

**Performance:**
```bash
# Check cache is working
php artisan arrowx:benchmark

# Expected results:
# - Cached compile: < 100ms
# - Cache improvement: > 80%
```

**Assets:**
- [ ] CSS loaded from `/build/` (Vite compiled)
- [ ] JavaScript loaded without errors
- [ ] Fonts loaded correctly
- [ ] Images display correctly

---

## Production Configuration

### .env Configuration

**Essential Settings:**
```bash
# Application
APP_NAME="TP-Affiliate"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=thaiprompt_affiliate
DB_USERNAME=tpaffil
DB_PASSWORD=secure_password

# Cache (Redis recommended for production)
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your@email.com
MAIL_PASSWORD=app_specific_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"

# Google Cloud (for translation)
GOOGLE_CLOUD_TRANSLATE_API_KEY=your_api_key_here

# Queue Worker
QUEUE_DRIVER=redis
```

### Cache Configuration

**config/cache.php:**
```php
// Recommended for production
'default' => env('CACHE_DRIVER', 'redis'),

'stores' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'cache',
        'lock_connection' => 'default',
    ],
],
```

### Session Configuration

**config/session.php:**
```php
// For production (Redis recommended)
'driver' => env('SESSION_DRIVER', 'redis'),
'lifetime' => 120,
'expire_on_close' => false,
'encrypt' => true,
'secure' => true,  // HTTPS only
'http_only' => true,
'same_site' => 'lax',
```

### Queue Configuration

**Setup queue worker as systemd service:**

```bash
# /etc/systemd/system/tpaffil-worker.service
[Unit]
Description=TP-Affiliate Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/Thaiprompt-Affiliate
ExecStart=/usr/bin/php artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

**Enable and start:**
```bash
sudo systemctl enable tpaffil-worker
sudo systemctl start tpaffil-worker
sudo systemctl status tpaffil-worker
```

---

## Performance Optimization

### 1. Opcache Configuration

**php.ini:**
```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.revalidate_freq=60
opcache.fast_shutdown=1
opcache.enable_cli=0
```

### 2. Laravel Optimizations

```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Cache events
php artisan event:cache

# Optimize autoloader
composer dump-autoload --optimize --no-dev
```

### 3. Arrow X Optimizations

```bash
# Compile all themes to cache
php artisan arrowx:compile --all

# Warm up cache
php artisan arrowx:warmup

# (Optional) Export to static files
# Then serve from CDN or Nginx directly
php artisan arrowx:export-files
```

### 4. Database Optimizations

```sql
-- Add indexes for frequently queried columns
CREATE INDEX idx_theme_is_active ON theme_settings(is_active);
CREATE INDEX idx_theme_updated_at ON theme_settings(updated_at);
CREATE INDEX idx_rgb_is_active ON theme_rgb_effects(is_active);

-- Analyze tables for query optimization
ANALYZE TABLE theme_settings;
ANALYZE TABLE theme_colors;
ANALYZE TABLE theme_rgb_effects;
```

### 5. Nginx Configuration (if applicable)

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/Thaiprompt-Affiliate/public;

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml application/xml+rss text/javascript;

    # Browser caching
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff|woff2|ttf|svg)$ {
        expires 365d;
        add_header Cache-Control "public, immutable";
    }

    # PHP-FPM
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
}
```

---

## Rollback Procedures

### Quick Rollback (Using deploy.sh)

```bash
# If rollback.sh script exists
./rollback.sh

# Or manual git rollback
git log --oneline -5  # Find previous commit
git reset --hard <commit-hash>
php artisan migrate:rollback
php artisan arrowx:compile --all
php artisan optimize:clear
```

### Database Rollback

```bash
# Rollback last migration batch
php artisan migrate:rollback

# Rollback specific steps
php artisan migrate:rollback --step=3

# Rollback to specific batch
php artisan migrate:rollback --batch=5

# After rollback, recompile theme
php artisan arrowx:compile --all
```

### Emergency Rollback (Blue-Green Deployment)

```bash
# Switch traffic back to blue environment
# (Update load balancer or symlink)
ln -sfn /blue /current

# Verify blue environment
curl -I https://yoursite.com
```

### Backup Restoration

```bash
# Restore database from backup
mysql -u tpaffil -p thaiprompt_affiliate < backup_2025-11-15.sql

# Restore files from backup
rsync -avz /backups/2025-11-15/ /var/www/Thaiprompt-Affiliate/

# Clear caches
php artisan optimize:clear
php artisan arrowx:compile --all
```

---

## Troubleshooting

### Issue 1: Arrow X Admin Not Loading

**Symptoms:**
- `/admin/arrow-x-theme` returns 404 or 500 error

**Solutions:**
```bash
# Clear route cache
php artisan route:clear
php artisan route:cache

# Check routes exist
php artisan route:list | grep arrow-x

# Verify controller exists
ls -l app/Http/Controllers/Admin/ArrowXThemeController.php

# Check permissions
chmod -R 775 storage bootstrap/cache
```

### Issue 2: Styles Not Applied

**Symptoms:**
- Arrow X styles not visible on frontend
- Default styles showing instead

**Solutions:**
```bash
# Compile theme
php artisan arrowx:compile

# Clear all caches
php artisan optimize:clear

# Clear browser cache
# Hard reload: Ctrl+Shift+R (Windows/Linux) or Cmd+Shift+R (Mac)

# Check theme is active
mysql -u tpaffil -p -e "SELECT * FROM theme_settings WHERE is_active = 1;" thaiprompt_affiliate

# Verify theme-styles component is included
grep -r "arrow-x.theme-styles" resources/views/layouts/
```

### Issue 3: Migration Errors

**Symptoms:**
- "Table already exists" error during migration

**Solution:**
```bash
# This should not happen if migrations use Schema::hasTable()
# But if it does, manually check:
mysql -u tpaffil -p -e "SHOW TABLES LIKE 'theme_%';" thaiprompt_affiliate

# Drop and recreate (CAUTION: data loss!)
php artisan migrate:fresh --seed

# Or skip existing tables (migrations should handle this automatically)
php artisan migrate --force
```

### Issue 4: Seeder Not Running

**Symptoms:**
- Default theme not created after `db:seed`

**Solutions:**
```bash
# Run Arrow X seeder specifically
php artisan db:seed --class=ArrowXThemeSeeder --force

# Verify seeder in DatabaseSeeder.php
grep -n "ArrowXThemeSeeder" database/seeders/DatabaseSeeder.php

# Check database
mysql -u tpaffil -p -e "SELECT COUNT(*) FROM theme_settings;" thaiprompt_affiliate
```

### Issue 5: Cache Not Working

**Symptoms:**
- `arrowx:benchmark` shows no cache improvement
- Every compile takes full time

**Solutions:**
```bash
# Check cache driver
php artisan config:show cache

# Clear and recompile
php artisan arrowx:clear
php artisan arrowx:compile

# Verify Redis (if using)
redis-cli ping  # Should return "PONG"

# Test cache manually
php artisan tinker
>>> Cache::put('test', 'value', 60);
>>> Cache::get('test');  // Should return "value"
```

### Issue 6: RGB Effects Not Showing

**Symptoms:**
- RGB effects configured but not visible

**Solutions:**
```bash
# Recompile theme
php artisan arrowx:compile --clear-cache

# Check RGB effects in database
mysql -u tpaffil -p -e "SELECT * FROM theme_rgb_effects WHERE is_active = 1;" thaiprompt_affiliate

# Verify effect settings
# - Intensity: should be medium or higher
# - Trigger: check if trigger condition is met (hover/click/always)

# Check browser console for JS errors
# Open DevTools > Console tab
```

### Issue 7: Language Switcher Not Working

**Symptoms:**
- Language switcher visible but doesn't change language

**Solutions:**
```bash
# Clear translation cache
php artisan arrowx:clear-translations --all

# Clear application cache
php artisan cache:clear

# Verify routes
php artisan route:list | grep language

# Check session driver is working
php artisan tinker
>>> session(['test' => 'value']);
>>> session('test');  // Should return "value"
```

### Issue 8: Performance Issues

**Symptoms:**
- Slow page loads
- High server load

**Solutions:**
```bash
# Enable opcache
php -i | grep opcache.enable  # Should be 1

# Optimize Laravel
php artisan optimize

# Warm up Arrow X cache
php artisan arrowx:warmup

# Check query performance
# Enable query log in .env:
DB_QUERY_LOG=true

# Verify Redis is running
systemctl status redis-server

# Check MySQL slow query log
mysql -u root -p -e "SHOW VARIABLES LIKE 'slow_query%';"
```

---

## Monitoring & Maintenance

### Daily Tasks

```bash
# Check logs for errors
tail -f storage/logs/laravel.log

# Monitor queue workers
php artisan queue:monitor

# Check cache hit rate
php artisan arrowx:benchmark
```

### Weekly Tasks

```bash
# Backup database
mysqldump -u tpaffil -p thaiprompt_affiliate > backup_$(date +%Y-%m-%d).sql

# Clear old logs (keep last 30 days)
find storage/logs -name "*.log" -mtime +30 -delete

# Optimize database tables
mysqlcheck -u tpaffil -p --optimize thaiprompt_affiliate
```

### Monthly Tasks

```bash
# Review and optimize database indexes
# Check slow query log
# Update dependencies (test first!)
composer update --dry-run
npm outdated

# Performance audit
php artisan arrowx:benchmark --iterations=20 > performance_$(date +%Y-%m-%d).txt
```

### Monitoring Metrics

**Key Performance Indicators:**
- Average page load time: < 2 seconds
- Theme compile time (cached): < 100ms
- Cache hit rate: > 80%
- Database query time: < 50ms
- Server CPU usage: < 70%
- Server memory usage: < 80%

**Set up monitoring alerts for:**
- Error rate > 1%
- Response time > 5 seconds
- Queue backlog > 1000 jobs
- Disk space < 10% free
- Database connections > 80% of max

---

## Production Checklist Summary

### Pre-Go-Live

- [ ] All code merged to main branch
- [ ] Tests passing (100%)
- [ ] Database backup created
- [ ] `.env` configured for production
- [ ] Assets compiled (`npm run build`)
- [ ] Caches optimized
- [ ] Queue workers configured
- [ ] Monitoring set up
- [ ] SSL certificate installed
- [ ] Domain DNS configured

### Go-Live

- [ ] Maintenance mode enabled
- [ ] Deploy code to production
- [ ] Run migrations
- [ ] Run seeders
- [ ] Compile Arrow X themes
- [ ] Clear caches
- [ ] Verify deployment
- [ ] Maintenance mode disabled
- [ ] Monitor logs

### Post-Go-Live

- [ ] Verify all features working
- [ ] Check performance metrics
- [ ] Monitor error logs (24 hours)
- [ ] Test rollback procedure
- [ ] Document any issues
- [ ] Update internal documentation
- [ ] Notify stakeholders

---

## Emergency Contacts & Resources

### Documentation

- **Arrow X README**: [ARROW_X_README.md](ARROW_X_README.md)
- **Arrow X Changelog**: [ARROW_X_CHANGELOG.md](ARROW_X_CHANGELOG.md)
- **Arrow X Summary**: [ARROW_X_SUMMARY.md](ARROW_X_SUMMARY.md)
- **Migration Guide**: [ARROW_X_MIGRATION.md](ARROW_X_MIGRATION.md)

### Support Commands

```bash
# Health check
php artisan about

# Check environment
php artisan env

# List all Arrow X commands
php artisan list arrowx

# Get help for specific command
php artisan arrowx:compile --help
```

### Logs

```bash
# Application logs
tail -f storage/logs/laravel.log

# Deployment logs (if using deploy.sh)
tail -f storage/logs/deployment.log

# Queue logs
tail -f storage/logs/queue.log

# Web server logs (Nginx example)
tail -f /var/log/nginx/error.log
```

---

**Document Version:** 1.0.0
**Last Updated:** 2025-11-15
**Status:** Production Ready

**Remember**: Always test deployments in staging environment before production!

---

*Deploy with confidence. Monitor proactively. Scale gracefully. 🚀*
