# 🚀 คู่มือการ Deploy Production

คู่มือนี้แนะนำวิธีการ deploy และอัพเดท ThaiPrompt Marketplace บน production server

---

## 📋 สารบัญ

1. [Quick Start](#quick-start)
2. [การติดตั้งครั้งแรก](#การติดตั้งครั้งแรก)
3. [การ Deploy อัพเดทใหม่](#การ-deploy-อัพเดทใหม่)
4. [CI/CD Pipeline](#cicd-pipeline)
5. [Rollback](#rollback)
6. [Troubleshooting](#troubleshooting)

---

## Quick Start

### การติดตั้งครั้งแรก

```bash
# 1. ติดตั้งระบบตามคู่มือ
# ดูรายละเอียดใน SERVER_SETUP.md

# 2. Clone โปรเจคจาก GitHub
cd /var/www
git clone https://github.com/xjanova/Thaiprompt-Affiliate.git thaiprompt
cd thaiprompt

# 3. ติดตั้ง dependencies และตั้งค่า
composer install --no-dev --optimize-autoloader
npm install
npm run build
cp .env.example .env
php artisan key:generate
php artisan migrate --force

# 4. ตั้งค่า permissions
chown -R www-data:www-data /var/www/thaiprompt
chmod -R 755 storage bootstrap/cache
```

### การ Deploy อัพเดทใหม่

```bash
cd /var/www/thaiprompt
./deploy.sh
```

**เพียงแค่นี้!** 🎉

---

## การติดตั้งครั้งแรก

### ขั้นตอนที่ 1: เตรียมเซิร์ฟเวอร์

**สำคัญ:** ติดตามทุกขั้นตอนใน [SERVER_SETUP.md](./SERVER_SETUP.md) เพื่อติดตั้ง:
- ✅ Ubuntu 22.04 LTS หรือใหม่กว่า
- ✅ PHP 8.2 + Extensions
- ✅ MySQL 8.0+
- ✅ Nginx
- ✅ Composer
- ✅ Node.js 20 LTS
- ✅ Redis
- ✅ Supervisor

### ขั้นตอนที่ 2: Clone Repository

```bash
cd /var/www
git clone https://github.com/xjanova/Thaiprompt-Affiliate.git thaiprompt
cd thaiprompt
```

หรือถ้าใช้ SSH:

```bash
git clone git@github.com:xjanova/Thaiprompt-Affiliate.git thaiprompt
```

### ขั้นตอนที่ 3: สร้างไฟล์ .env

```bash
cp .env.example .env
nano .env
```

**แก้ไขค่าสำคัญเหล่านี้:**

```env
# Application
APP_NAME="ThaiPrompt Marketplace"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=thaiprompt_marketplace
DB_USERNAME=thaiprompt
DB_PASSWORD=YOUR_STRONG_PASSWORD

# Cache & Queue
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### ขั้นตอนที่ 4: ติดตั้ง Dependencies

```bash
# Install PHP dependencies
composer install --no-dev --optimize-autoloader --no-interaction

# Install JavaScript dependencies
npm ci --only=production

# Build frontend assets
npm run build
```

### ขั้นตอนที่ 5: Setup Laravel

```bash
# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate --force

# Create storage link
php artisan storage:link

# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### ขั้นตอนที่ 6: Set Permissions

```bash
chown -R www-data:www-data /var/www/thaiprompt
chmod -R 755 /var/www/thaiprompt/storage
chmod -R 755 /var/www/thaiprompt/bootstrap/cache
```

### ขั้นตอนที่ 7: ตั้งค่า Web Server

**เลือกอย่างใดอย่างหนึ่ง: Nginx หรือ Apache**

#### สำหรับ Nginx

สร้างไฟล์ `/etc/nginx/sites-available/thaiprompt`:

```nginx
server {
    listen 80;
    server_name your-domain.com www.your-domain.com;
    root /var/www/thaiprompt/public;

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
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

เปิดใช้งาน:

```bash
ln -s /etc/nginx/sites-available/thaiprompt /etc/nginx/sites-enabled/
nginx -t
systemctl reload nginx
```

#### สำหรับ Apache

สร้างไฟล์ `/etc/apache2/sites-available/thaiprompt.conf`:

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    ServerAlias www.your-domain.com
    DocumentRoot /var/www/thaiprompt/public

    <Directory /var/www/thaiprompt/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/thaiprompt-error.log
    CustomLog ${APACHE_LOG_DIR}/thaiprompt-access.log combined

    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"

    <FilesMatch \.php$>
        SetHandler "proxy:unix:/var/run/php/php8.2-fpm.sock|fcgi://localhost"
    </FilesMatch>
</VirtualHost>
```

เปิดใช้งาน:

```bash
a2enmod rewrite headers proxy_fcgi
a2enconf php8.2-fpm
a2ensite thaiprompt.conf
a2dissite 000-default.conf
apache2ctl configtest
systemctl reload apache2
```

### ขั้นตอนที่ 8: ติดตั้ง SSL (Let's Encrypt)

**สำหรับ Nginx:**

```bash
apt install -y certbot python3-certbot-nginx
certbot --nginx -d your-domain.com -d www.your-domain.com
```

**สำหรับ Apache:**

```bash
apt install -y certbot python3-certbot-apache
certbot --apache -d your-domain.com -d www.your-domain.com
```

อัพเดท .env:

```bash
nano /var/www/thaiprompt/.env
# เปลี่ยน APP_URL=https://your-domain.com
# เพิ่ม SESSION_SECURE_COOKIE=true

php artisan config:cache
```

### ขั้นตอนที่ 9: สร้าง Admin User

```bash
cd /var/www/thaiprompt
php artisan tinker
```

ใน tinker shell:

```php
$user = new App\Models\User;
$user->name = 'Admin';
$user->email = 'admin@your-domain.com';
$user->password = bcrypt('your-secure-password');
$user->role = 'admin';
$user->save();
exit
```

### ✅ เสร็จสิ้น!

เว็บไซต์พร้อมใช้งานที่: `https://your-domain.com`

---

## การ Deploy อัพเดทใหม่

เมื่อมีการอัพเดทโค้ดใน GitHub และต้องการ deploy ไปยัง production:

### วิธีที่ 1: ใช้ deploy.sh (แนะนำ)

```bash
cd /var/www/thaiprompt
./deploy.sh
```

Script จะทำงานดังนี้อัตโนมัติ:
1. ✅ เปิด Maintenance Mode
2. ✅ Pull โค้ดล่าสุดจาก GitHub
3. ✅ ติดตั้ง/อัพเดท Composer dependencies
4. ✅ ติดตั้ง/อัพเดท NPM dependencies  
5. ✅ Build frontend assets
6. ✅ Run database migrations
7. ✅ Clear และ Cache configuration
8. ✅ Set permissions
9. ✅ Restart services
10. ✅ ปิด Maintenance Mode

### วิธีที่ 2: Manual Deploy

ถ้าต้องการควบคุมแต่ละขั้นตอน:

```bash
cd /var/www/thaiprompt

# 1. Enable maintenance mode
php artisan down

# 2. Pull latest code
git pull origin main

# 3. Update dependencies
composer install --no-dev --optimize-autoloader
npm ci --only=production
npm run build

# 4. Run migrations
php artisan migrate --force

# 5. Clear and cache
php artisan config:clear
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Restart services
sudo systemctl reload php8.2-fpm
sudo supervisorctl restart all

# 7. Disable maintenance mode
php artisan up
```

---

## CI/CD Pipeline

### GitHub Actions

สร้างไฟล์ `.github/workflows/deploy.yml`:

```yaml
name: Deploy to Production

on:
  push:
    branches: [ main ]

jobs:
  deploy:
    runs-on: ubuntu-latest
    
    steps:
      - name: Deploy to Server
        uses: appleboy/ssh-action@master
        with:
          host: ${{ secrets.SERVER_HOST }}
          username: ${{ secrets.SERVER_USER }}
          key: ${{ secrets.SSH_PRIVATE_KEY }}
          script: |
            cd /var/www/thaiprompt
            ./deploy.sh
```

ตั้งค่า Secrets ใน GitHub:
- `SERVER_HOST`: IP address หรือ domain ของเซิร์ฟเวอร์
- `SERVER_USER`: Username สำหรับ SSH
- `SSH_PRIVATE_KEY`: Private key สำหรับ SSH

### Webhook Deployment

ถ้าต้องการ auto-deploy เมื่อมี push:

1. สร้าง deploy endpoint:

```bash
# ในเซิร์ฟเวอร์
nano /var/www/deploy-webhook.php
```

```php
<?php
$secret = 'YOUR_SECRET_KEY';
$payload = file_get_contents('php://input');
$signature = hash_hmac('sha256', $payload, $secret);

if (hash_equals('sha256=' . $signature, $_SERVER['HTTP_X_HUB_SIGNATURE_256'])) {
    shell_exec('cd /var/www/thaiprompt && ./deploy.sh > /tmp/deploy.log 2>&1 &');
    echo 'Deployment started';
} else {
    http_response_code(403);
    echo 'Invalid signature';
}
```

2. ตั้งค่า webhook ใน GitHub:
   - ไปที่ Repository Settings > Webhooks
   - Add webhook: `https://your-domain.com/deploy-webhook.php`
   - Secret: ใส่ค่าเดียวกับในโค้ด
   - Events: Just the push event

---

## Rollback

### วิธีที่ 1: Rollback ด้วย Git

```bash
cd /var/www/thaiprompt

# ดู commit history
git log --oneline -10

# Rollback ไปยัง commit ที่ต้องการ
git reset --hard COMMIT_HASH

# Deploy ใหม่
./deploy.sh
```

### วิธีที่ 2: Rollback Database

```bash
# ถ้า migrate ผิดพลาด
php artisan migrate:rollback

# Rollback หลาย steps
php artisan migrate:rollback --step=3
```

### วิธีที่ 3: ใช้ Git Revert (แนะนำสำหรับ production)

```bash
# Revert commit ที่มีปัญหา (ปลอดภัยกว่า reset)
git revert COMMIT_HASH
git push origin main

# Deploy
./deploy.sh
```

---

## Troubleshooting

### ปัญหา: deploy.sh ไม่มีสิทธิ์รัน

```bash
chmod +x deploy.sh
```

### ปัญหา: Composer install ล้มเหลว

```bash
# ลบ cache แล้วลองใหม่
composer clear-cache
composer install --no-dev --optimize-autoloader
```

### ปัญหา: NPM build ล้มเหลว

```bash
# ลบ node_modules แล้วติดตั้งใหม่
rm -rf node_modules package-lock.json
npm install
npm run build
```

### ปัญหา: Permission denied

```bash
chown -R www-data:www-data /var/www/thaiprompt
chmod -R 755 /var/www/thaiprompt/storage
chmod -R 755 /var/www/thaiprompt/bootstrap/cache
```

### ปัญหา: 500 Internal Server Error

```bash
# ตรวจสอบ logs
tail -f /var/www/thaiprompt/storage/logs/laravel.log

# Web server logs
tail -f /var/log/nginx/error.log      # สำหรับ Nginx
tail -f /var/log/apache2/error.log    # สำหรับ Apache

# Clear cache
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Cache ใหม่
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### ปัญหา: Database connection failed

```bash
# ตรวจสอบ MySQL
systemctl status mysql

# ทดสอบ connection
mysql -u thaiprompt -p

# ตรวจสอบ .env
cat .env | grep DB_

# Test connection จาก Laravel
php artisan db:show
```

### ปัญหา: CSS/JS ไม่โหลด

```bash
# Build ใหม่
npm run build

# Clear cache
php artisan config:clear

# ตรวจสอบ permissions
ls -la public/build/
```

---

## Performance Optimization

### 1. Enable OPcache

แก้ไข `/etc/php/8.2/fpm/php.ini`:

```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.validate_timestamps=0
```

Restart PHP-FPM:

```bash
systemctl restart php8.2-fpm
```

### 2. Optimize Web Server

#### สำหรับ Nginx

แก้ไข `/etc/nginx/nginx.conf`:

```nginx
worker_processes auto;
worker_connections 4096;

gzip on;
gzip_vary on;
gzip_comp_level 6;
gzip_types text/plain text/css application/json application/javascript text/xml application/xml;

client_max_body_size 100M;
```

Restart Nginx:
```bash
systemctl restart nginx
```

#### สำหรับ Apache

เปิดใช้งาน compression modules:

```bash
a2enmod deflate
a2enmod expires
a2enmod headers
```

แก้ไข `/etc/apache2/apache2.conf` หรือใน Virtual Host:

```apache
# Enable compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript application/json
</IfModule>

# Enable caching
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
</IfModule>

# Increase upload size
LimitRequestBody 104857600
```

Restart Apache:
```bash
systemctl restart apache2
```

### 3. Optimize MySQL

แก้ไข `/etc/mysql/mysql.conf.d/mysqld.cnf`:

```ini
innodb_buffer_pool_size = 2G
innodb_log_file_size = 512M
max_connections = 200
```

### 4. Laravel Optimization

```bash
# Cache everything
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Optimize composer autoloader
composer install --optimize-autoloader --classmap-authoritative --no-dev
```

---

## Monitoring & Logs

### Application Logs

```bash
# Real-time monitoring
tail -f /var/www/thaiprompt/storage/logs/laravel.log

# Search for errors
grep "ERROR" /var/www/thaiprompt/storage/logs/laravel.log
```

### Web Server Logs

**Nginx:**

```bash
tail -f /var/log/nginx/access.log
tail -f /var/log/nginx/error.log
```

**Apache:**

```bash
tail -f /var/log/apache2/access.log
tail -f /var/log/apache2/error.log
```

### Queue Workers

```bash
# Check status
supervisorctl status

# Restart workers
supervisorctl restart thaiprompt-worker:*

# View worker logs
tail -f /var/www/thaiprompt/storage/logs/worker.log
```

---

## Backup & Restore

### Database Backup

```bash
# Manual backup
mysqldump -u thaiprompt -p thaiprompt_marketplace > backup_$(date +%Y%m%d).sql

# Restore
mysql -u thaiprompt -p thaiprompt_marketplace < backup_20250127.sql
```

### Automated Backup

สร้าง cron job:

```bash
crontab -e
```

เพิ่ม:

```
# Backup database ทุกวันเวลา 02:00
0 2 * * * mysqldump -u thaiprompt -p'PASSWORD' thaiprompt_marketplace | gzip > /var/backups/db_$(date +\%Y\%m\%d).sql.gz

# ลบ backup เก่าที่เกิน 30 วัน
0 3 * * * find /var/backups -name "db_*.sql.gz" -mtime +30 -delete
```

---

## เอกสารเพิ่มเติม

- 📖 [SERVER_SETUP.md](./SERVER_SETUP.md) - คู่มือติดตั้งเซิร์ฟเวอร์ละเอียด
- 📖 [README.md](./README.md) - ข้อมูลโปรเจคและ features
- 🐛 [GitHub Issues](https://github.com/xjanova/Thaiprompt-Affiliate/issues) - รายงานปัญหา

---

**🎉 Deployment สำเร็จ!**

Server พร้อมใช้งานแล้วที่: `https://your-domain.com`
