# 🚀 Deployment Guide - v2.0.0 Phoenix

คู่มือการ Deploy สำหรับ Thaiprompt-Affiliate v2.0.0 Phoenix

---

## 📋 System Requirements

### PHP & Extensions
```
PHP >= 8.1
Extensions:
- BCMath
- Ctype
- cURL
- DOM
- Fileinfo
- Filter
- Hash
- Mbstring
- OpenSSL
- PCRE
- PDO
- Session
- Tokenizer
- XML
- GD or Imagick (for image processing)
- Zip
```

### Database
```
MySQL >= 5.7
MariaDB >= 10.3
PostgreSQL >= 10 (optional)
SQLite >= 3.8 (optional)
```

### Web Server
```
Apache >= 2.4 (with mod_rewrite)
Nginx >= 1.18
```

### Node.js & NPM
```
Node.js >= 16.x
NPM >= 8.x
```

---

## 📦 Composer Dependencies

### Production Dependencies (require)
```json
{
  "php": "^8.1",
  "google/cloud-translate": "^1.15",
  "google/cloud-vision": "^1.7",
  "guzzlehttp/guzzle": "^7.2",
  "intervention/image": "^3.11",
  "laravel/framework": "^11.0",
  "laravel/sanctum": "^4.0",
  "laravel/tinker": "^2.8"
}
```

### Development Dependencies (require-dev)
```json
{
  "fakerphp/faker": "^1.9.1",
  "laravel/pint": "^1.0",
  "laravel/sail": "^1.18",
  "mockery/mockery": "^1.4.4",
  "nunomaduro/collision": "^8.1",
  "phpunit/phpunit": "^11.0"
}
```

### Autoload Files
```
- app/Helpers/SeoHelper.php
- app/Helpers/IconHelper.php (NEW in v2.0.0)
```

---

## 🎨 NPM Dependencies

### DevDependencies
```json
{
  "@tailwindcss/forms": "^0.5.7",
  "autoprefixer": "^10.4.17",
  "axios": "^1.6.4",
  "laravel-vite-plugin": "^1.0.0",
  "postcss": "^8.4.35",
  "tailwindcss": "^3.4.1",
  "vite": "^5.0.0"
}
```

### Dependencies
```json
{
  "alpinejs": "^3.13.5",
  "chart.js": "^4.4.1",
  "d3": "^7.9.0",
  "gsap": "^3.12.5",
  "vis-network": "^10.0.2"
}
```

---

## 🔧 Deployment Steps

### 1. Clone Repository
```bash
git clone https://github.com/xjanova/Thaiprompt-Affiliate.git
cd Thaiprompt-Affiliate

# Checkout v2.0.0 branch
git checkout claude/prepare-v2-update-011CUtch2PvnQdtf6JErcaFF
```

### 2. Install Composer Dependencies
```bash
# For Production (no dev dependencies)
composer install --no-dev --optimize-autoloader --no-interaction

# For Development
composer install

# If composer is not installed
curl -sS https://getcomposer.org/installer | php
php composer.phar install --no-dev --optimize-autoloader
```

### 3. Install NPM Dependencies
```bash
npm install

# Build assets for production
npm run build

# For development
npm run dev
```

### 4. Environment Configuration
```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Edit .env file with your settings
nano .env
```

### 5. Configure .env File
```env
# Application
APP_NAME="TP-Affiliate"
APP_ENV=production
APP_KEY=base64:YOUR_KEY_HERE
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=thaiprompt_affiliate
DB_USERNAME=your_username
DB_PASSWORD=your_secure_password

# Cache & Session
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync

# Optional: Redis (for better performance)
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Optional: Google Cloud (for translations & vision)
GOOGLE_CLOUD_PROJECT_ID=your_project_id
GOOGLE_CLOUD_KEY_FILE=/path/to/service-account.json

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

### 6. Database Setup
```bash
# Create database first
mysql -u root -p
CREATE DATABASE thaiprompt_affiliate CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;

# Run migrations
php artisan migrate --force

# Seed database (includes Theme presets)
php artisan db:seed --force

# Seed Theme System specifically
php artisan db:seed --class=ThemeSeeder --force
```

### 7. Storage & Permissions
```bash
# Create storage link
php artisan storage:link

# Set permissions (Linux/Mac)
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# For shared hosting
chmod -R 755 storage bootstrap/cache

# Create icon directories
mkdir -p public/icons/{system,theme,custom,social,flags}
chmod -R 755 public/icons
```

### 8. Optimize for Production
```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize autoloader
composer dump-autoload --optimize

# Clear all caches
php artisan optimize:clear
```

### 9. Web Server Configuration

#### Apache (.htaccess already included)
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

#### Nginx
```nginx
server {
    listen 80;
    listen [::]:80;
    server_name yourdomain.com;
    root /var/www/Thaiprompt-Affiliate/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### 10. SSL Certificate (Let's Encrypt)
```bash
# Install certbot
apt-get install certbot python3-certbot-nginx

# Get certificate
certbot --nginx -d yourdomain.com -d www.yourdomain.com

# Auto-renewal (cron)
0 0 * * * certbot renew --quiet
```

### 11. Cron Jobs (for scheduled tasks)
```bash
# Edit crontab
crontab -e

# Add this line
* * * * * cd /var/www/Thaiprompt-Affiliate && php artisan schedule:run >> /dev/null 2>&1
```

### 12. Queue Worker (if using queues)
```bash
# Supervisor configuration
nano /etc/supervisor/conf.d/thaiprompt-worker.conf
```

```ini
[program:thaiprompt-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/Thaiprompt-Affiliate/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/Thaiprompt-Affiliate/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
# Start supervisor
supervisorctl reread
supervisorctl update
supervisorctl start thaiprompt-worker:*
```

---

## 🆕 v2.0.0 Specific Setup

### Theme System Initialization
```bash
# 1. Run theme migrations
php artisan migrate --path=database/migrations/2025_11_07_140000_create_themes_system_tables.php --force

# 2. Seed theme presets
php artisan db:seed --class=ThemeSeeder --force

# 3. Verify themes
php artisan tinker
>>> \App\Models\Theme::count();
>>> \App\Models\ThemePreset::count();
```

### Icon System Setup
```bash
# 1. Create icon directories
mkdir -p public/icons/{system,theme,custom,social,flags}
mkdir -p storage/app/public/icons/{system,theme,custom,social,flags}

# 2. Set permissions
chmod -R 755 public/icons
chmod -R 755 storage/app/public/icons

# 3. Copy sample icons (optional)
# Icons are already in public/icons/ from repo
```

### Auto-Update System (optional)
```bash
# Run update migrations
php artisan migrate --path=database/migrations/2025_11_07_140001_create_updates_system_tables.php --force

# Configure GitHub token in .env
echo "GITHUB_TOKEN=your_github_token_here" >> .env
```

---

## 🔍 Post-Deployment Verification

### 1. Check Application Health
```bash
# Check version
php artisan --version

# Check routes
php artisan route:list | grep themes
php artisan route:list | grep icons

# Check database connection
php artisan tinker
>>> DB::connection()->getPdo();
```

### 2. Test Theme System
- Visit `/admin/themes`
- Check if 6 theme presets are visible
- Try creating a new theme
- Switch between Light/Dark mode

### 3. Test Icon System
- Visit `/admin/icons`
- Check if sample icons are displayed
- Try uploading a new icon
- Test icon component: `<x-icon name="dashboard" category="system" />`

### 4. Check Logs
```bash
tail -f storage/logs/laravel.log
```

---

## 🐛 Troubleshooting

### Issue: Composer dependencies fail to install
```bash
# Update composer
composer self-update

# Clear cache
composer clear-cache

# Try again
composer install --no-dev --optimize-autoloader
```

### Issue: NPM build fails
```bash
# Clear npm cache
npm cache clean --force

# Remove node_modules
rm -rf node_modules package-lock.json

# Reinstall
npm install
npm run build
```

### Issue: Themes not working
```bash
# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Re-run theme seeder
php artisan db:seed --class=ThemeSeeder --force
```

### Issue: Icons not displaying
```bash
# Check permissions
ls -la public/icons

# Recreate directories
mkdir -p public/icons/{system,theme,custom,social,flags}
chmod -R 755 public/icons

# Copy icons again
git checkout public/icons/
```

### Issue: 500 Internal Server Error
```bash
# Check logs
tail -f storage/logs/laravel.log

# Check permissions
chmod -R 775 storage bootstrap/cache

# Clear caches
php artisan optimize:clear
```

---

## 📊 Performance Optimization

### 1. Enable OPcache (php.ini)
```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.validate_timestamps=0
opcache.save_comments=1
opcache.fast_shutdown=1
```

### 2. Use Redis for Cache & Sessions
```env
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

### 3. Enable Laravel Octane (optional)
```bash
composer require laravel/octane
php artisan octane:install
php artisan octane:start --server=swoole
```

---

## 🔒 Security Checklist

- [ ] Change APP_KEY
- [ ] Set APP_DEBUG=false in production
- [ ] Use strong database passwords
- [ ] Enable HTTPS/SSL
- [ ] Configure firewall (ufw/iptables)
- [ ] Set proper file permissions (755/644)
- [ ] Keep dependencies updated
- [ ] Enable CSRF protection (default in Laravel)
- [ ] Configure rate limiting
- [ ] Set up backups (database + files)

---

## 📦 Backup Strategy

### Database Backup
```bash
# Daily backup script
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u username -p password thaiprompt_affiliate > backup_$DATE.sql
gzip backup_$DATE.sql
mv backup_$DATE.sql.gz /backups/
```

### Files Backup
```bash
# Backup storage and uploads
tar -czf storage_backup_$(date +%Y%m%d).tar.gz storage/ public/uploads/
```

---

## 📞 Support & Resources

- **Documentation**: `/ICON_SYSTEM_GUIDE.md`, `/THEME_V2_IMPLEMENTATION_SUMMARY.md`
- **Version**: 2.0.0 Phoenix
- **Laravel Version**: 11.x
- **PHP Version**: 8.1+

---

## ✅ Deployment Checklist

- [ ] Clone repository
- [ ] Install Composer dependencies (--no-dev --optimize-autoloader)
- [ ] Install NPM dependencies
- [ ] Build assets (npm run build)
- [ ] Configure .env file
- [ ] Generate APP_KEY
- [ ] Run migrations
- [ ] Seed database (including ThemeSeeder)
- [ ] Create storage link
- [ ] Set permissions (775 storage, 755 public)
- [ ] Configure web server (Apache/Nginx)
- [ ] Setup SSL certificate
- [ ] Setup cron jobs
- [ ] Optimize for production (cache config, routes, views)
- [ ] Test application
- [ ] Setup backups
- [ ] Configure monitoring

---

**Deployment Date**: 2025-11-07
**Version**: 2.0.0 Phoenix
**Last Updated**: 2025-11-07
