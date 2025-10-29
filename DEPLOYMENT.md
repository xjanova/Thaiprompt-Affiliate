# 🚀 Deployment Guide - TP-Affiliate

## การ Deploy สู่ Production แบบง่าย

TP-Affiliate มาพร้อมระบบ Deploy ในคำสั่งเดียว!

---

## ⚡ Quick Deployment

### วิธีที่ 1: ใช้ Shell Script (แนะนำ)

```bash
./deploy.sh
```

### วิธีที่ 2: ใช้ Artisan Command

```bash
php artisan deploy
```

### วิธีที่ 3: Manual Deployment

```bash
# 1. Enable maintenance mode
php artisan down --retry=60

# 2. Pull latest code
git pull origin main

# 3. Install dependencies
composer install --no-dev --optimize-autoloader

# 4. Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 5. Run migrations
php artisan migrate --force

# 6. Optimize application
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# 7. Disable maintenance mode
php artisan up
```

---

## 📋 Pre-Deployment Checklist

ก่อน Deploy ให้ตรวจสอบสิ่งเหล่านี้:

- [ ] Backup database
- [ ] Backup files
- [ ] Test on staging environment
- [ ] Check `.env` configuration
- [ ] Verify APP_ENV=production
- [ ] Set APP_DEBUG=false
- [ ] Configure proper database credentials
- [ ] Setup mail configuration
- [ ] Configure cache driver (Redis recommended)
- [ ] Setup queue worker (if using queues)
- [ ] Configure file storage
- [ ] Setup SSL certificate
- [ ] Configure web server (Nginx/Apache)

---

## 🔧 Server Requirements

### Minimum Requirements
- PHP 8.1 or higher
- Composer
- MySQL 5.7+ or MariaDB 10.3+
- Nginx or Apache
- SSL Certificate

### Recommended Setup
- PHP 8.2+
- Redis (for cache and sessions)
- Supervisor (for queue workers)
- Node.js & NPM (for assets)
- Git

### PHP Extensions
```
- BCMath
- Ctype
- JSON
- Mbstring
- OpenSSL
- PDO
- Tokenizer
- XML
- cURL
- fileinfo
- GD
```

---

## 🌐 Web Server Configuration

### Nginx Configuration

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name your-domain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name your-domain.com;
    root /var/www/thaiprompt-affiliate/public;

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

    # SSL Configuration
    ssl_certificate /path/to/ssl/cert.pem;
    ssl_certificate_key /path/to/ssl/key.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
}
```

### Apache Configuration

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    Redirect permanent / https://your-domain.com/
</VirtualHost>

<VirtualHost *:443>
    ServerName your-domain.com
    DocumentRoot /var/www/thaiprompt-affiliate/public

    <Directory /var/www/thaiprompt-affiliate/public>
        AllowOverride All
        Require all granted
    </Directory>

    SSLEngine on
    SSLCertificateFile /path/to/ssl/cert.pem
    SSLCertificateKeyFile /path/to/ssl/key.pem

    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
```

---

## ⚙️ Production Configuration

### Environment Variables

สร้างไฟล์ `.env` สำหรับ production:

```bash
cp .env.production .env
```

แก้ไขค่าที่สำคัญ:

```env
APP_NAME="TP-Affiliate"
APP_ENV=production
APP_KEY=base64:your-app-key-here
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_secure_password

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### Generate Application Key

```bash
php artisan key:generate
```

---

## 🔄 Deployment Strategies

### Zero-Downtime Deployment

สำหรับเว็บไซต์ที่ต้องการ uptime 99.99%:

1. **Blue-Green Deployment**
```bash
# Deploy to blue environment
cd /var/www/thaiprompt-affiliate-blue
./deploy.sh

# Switch traffic to blue
ln -sfn /var/www/thaiprompt-affiliate-blue /var/www/thaiprompt-affiliate

# Reload web server
sudo systemctl reload nginx
```

2. **Rolling Deployment** (สำหรับ multiple servers)
```bash
# Deploy to servers one by one
for server in server1 server2 server3; do
    ssh $server "cd /var/www/thaiprompt-affiliate && ./deploy.sh"
    sleep 30
done
```

---

## 📊 Post-Deployment

### Health Check

```bash
# Check application status
curl https://your-domain.com/up

# Check database connection
php artisan tinker
>>> DB::connection()->getPdo();
```

### Performance Monitoring

```bash
# Check response time
curl -o /dev/null -s -w '%{time_total}\n' https://your-domain.com

# Monitor logs
tail -f storage/logs/laravel.log

# Check queue workers
php artisan queue:work --daemon
```

### Verify Deployment

```bash
# Check current version
php artisan --version

# Check configuration
php artisan config:show app

# Check routes
php artisan route:list

# Check database migrations
php artisan migrate:status
```

---

## 🔐 Security Hardening

### File Permissions

```bash
# Set proper permissions
sudo chown -R www-data:www-data /var/www/thaiprompt-affiliate
sudo find /var/www/thaiprompt-affiliate -type f -exec chmod 644 {} \;
sudo find /var/www/thaiprompt-affiliate -type d -exec chmod 755 {} \;

# Storage and bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### Environment File Security

```bash
# Protect .env file
chmod 600 .env

# Never commit .env to git
echo ".env" >> .gitignore
```

---

## 🔄 Rollback Strategy

### Quick Rollback

```bash
# Revert to previous commit
git reset --hard HEAD~1
./deploy.sh

# Restore database backup
php artisan db:restore --backup=latest
```

### Using Deploy Script

```bash
# Keep multiple releases
./deploy.sh --keep-releases=5

# Rollback to previous release
./deploy.sh --rollback
```

---

## 🚨 Troubleshooting

### Common Issues

#### 1. Permission Denied

```bash
sudo chmod -R 775 storage bootstrap/cache
sudo chown -R www-data:www-data storage bootstrap/cache
```

#### 2. 500 Internal Server Error

```bash
# Check logs
tail -f storage/logs/laravel.log

# Clear and rebuild cache
php artisan optimize:clear
php artisan optimize
```

#### 3. Database Connection Failed

```bash
# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();

# Check credentials in .env
cat .env | grep DB_
```

#### 4. Session/Cache Not Working

```bash
# Check Redis connection
redis-cli ping

# Restart Redis
sudo systemctl restart redis
```

---

## 📈 Performance Optimization

### Enable OPcache

```ini
; php.ini
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
```

### Use Redis for Cache

```bash
# Install Redis
sudo apt-get install redis-server

# Configure Laravel
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

### Enable Gzip Compression

```nginx
# nginx.conf
gzip on;
gzip_vary on;
gzip_min_length 1024;
gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml+rss application/json;
```

---

## 🔄 Continuous Deployment

### GitHub Actions Example

```yaml
name: Deploy

on:
  push:
    branches: [ main ]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2

      - name: Deploy to Production
        uses: appleboy/ssh-action@master
        with:
          host: ${{ secrets.HOST }}
          username: ${{ secrets.USERNAME }}
          key: ${{ secrets.SSH_KEY }}
          script: |
            cd /var/www/thaiprompt-affiliate
            git pull origin main
            ./deploy.sh
```

---

## 📞 Support

หากพบปัญหาในการ Deploy:

- 📖 อ่าน [README.md](README.md)
- 🐛 เปิด [Issue](https://github.com/xjanova/Thaiprompt-Affiliate/issues)
- 💬 ติดต่อ: support@thaiprompt.com

---

**Happy Deploying! 🚀**
