# 🚀 คู่มือการ Deploy Production

คู่มือนี้แนะนำวิธีการ deploy ThaiPrompt Marketplace ไปยัง production server

---

## 📋 สารบัญ

1. [เตรียม Production Server](#เตรียม-production-server)
2. [ติดตั้ง Web Server](#ติดตั้ง-web-server)
3. [Deploy โปรเจค](#deploy-โปรเจค)
4. [การตั้งค่า Production](#การตั้งค่า-production)
5. [SSL Certificate](#ssl-certificate)
6. [Performance Optimization](#performance-optimization)
7. [Monitoring & Logging](#monitoring--logging)
8. [Backup Strategy](#backup-strategy)
9. [CI/CD Pipeline](#cicd-pipeline)
10. [Troubleshooting](#troubleshooting)

---

## เตรียม Production Server

### ความต้องการ Server

**Minimum Requirements:**
- CPU: 2 cores
- RAM: 4GB
- Storage: 50GB SSD
- Bandwidth: 100Mbps

**Recommended:**
- CPU: 4 cores
- RAM: 8GB
- Storage: 100GB SSD
- Bandwidth: 1Gbps

### เลือก Server Provider

**แนะนำ:**
- **AWS** - EC2 t3.medium ขึ้นไป
- **Digital Ocean** - Droplet $24/month ขึ้นไป
- **Vultr** - High Frequency $24/month ขึ้นไป
- **Linode** - Dedicated CPU 4GB ขึ้นไป

### OS แนะนำ

- **Ubuntu 22.04 LTS** (แนะนำ)
- **Ubuntu 20.04 LTS**
- **Debian 11**

---

## ติดตั้ง Web Server

### 1. อัพเดทระบบ

```bash
sudo apt update
sudo apt upgrade -y
sudo reboot
```

### 2. ติดตั้ง Nginx

```bash
sudo apt install -y nginx
sudo systemctl start nginx
sudo systemctl enable nginx
```

ทดสอบ: เปิดเบราว์เซอร์ไปที่ IP ของ server

### 3. ติดตั้ง PHP 8.1 และ Extensions

```bash
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

sudo apt install -y php8.1-fpm php8.1-cli php8.1-common \
  php8.1-mysql php8.1-zip php8.1-gd php8.1-mbstring \
  php8.1-curl php8.1-xml php8.1-bcmath php8.1-intl \
  php8.1-redis
```

ตรวจสอบ:
```bash
php -v
```

### 4. ติดตั้ง MySQL

```bash
sudo apt install -y mysql-server
sudo mysql_secure_installation
```

ตั้งค่า MySQL:
```bash
sudo mysql
```

```sql
CREATE DATABASE thaiprompt_marketplace CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'thaiprompt'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD_HERE';
GRANT ALL PRIVILEGES ON thaiprompt_marketplace.* TO 'thaiprompt'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 5. ติดตั้ง Redis

```bash
sudo apt install -y redis-server
sudo systemctl start redis
sudo systemctl enable redis
```

ทดสอบ:
```bash
redis-cli ping
# ควรได้: PONG
```

### 6. ติดตั้ง Composer

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer
```

### 7. ติดตั้ง Node.js

```bash
curl -fsSL https://deb.nodesource.com/setup_16.x | sudo -E bash -
sudo apt install -y nodejs
```

### 8. ติดตั้ง Git

```bash
sudo apt install -y git
```

### 9. สร้าง User สำหรับ Deploy

```bash
sudo adduser deployer
sudo usermod -aG www-data deployer
```

---

## Deploy โปรเจค

### 1. Clone Repository

```bash
sudo mkdir -p /var/www
sudo chown deployer:www-data /var/www
cd /var/www

# Clone โปรเจค
git clone https://github.com/xjanova/Thaiprompt-Affiliate.git thaiprompt
cd thaiprompt
```

### 2. ติดตั้ง Dependencies

```bash
# PHP dependencies
composer install --optimize-autoloader --no-dev

# JavaScript dependencies
npm install
npm run build
```

### 3. สร้างไฟล์ .env

```bash
cp .env.example .env
nano .env
```

แก้ไขค่าต่อไปนี้:

```env
APP_NAME="ThaiPrompt Marketplace"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=thaiprompt_marketplace
DB_USERNAME=thaiprompt
DB_PASSWORD=your_strong_password

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### 4. สร้าง Application Key

```bash
php artisan key:generate
```

### 5. Run Migrations

```bash
php artisan migrate --force
```

### 6. ตั้งค่า Permissions

```bash
sudo chown -R deployer:www-data /var/www/thaiprompt
sudo chmod -R 775 /var/www/thaiprompt/storage
sudo chmod -R 775 /var/www/thaiprompt/bootstrap/cache
```

### 7. สร้าง Storage Link

```bash
php artisan storage:link
```

### 8. Optimize Application

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## การตั้งค่า Production

### 1. ตั้งค่า Nginx

สร้างไฟล์ `/etc/nginx/sites-available/thaiprompt`:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name yourdomain.com www.yourdomain.com;
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
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Cache static assets
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml+rss application/json;
}
```

เปิดใช้งาน:
```bash
sudo ln -s /etc/nginx/sites-available/thaiprompt /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

### 2. ตั้งค่า PHP-FPM

แก้ไข `/etc/php/8.1/fpm/pool.d/www.conf`:

```ini
user = www-data
group = www-data
listen = /var/run/php/php8.1-fpm.sock
listen.owner = www-data
listen.group = www-data
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
```

Restart PHP-FPM:
```bash
sudo systemctl restart php8.1-fpm
```

### 3. ตั้งค่า Supervisor (Queue Worker)

สร้างไฟล์ `/etc/supervisor/conf.d/thaiprompt.conf`:

```ini
[program:thaiprompt-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/thaiprompt/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=deployer
numprocs=8
redirect_stderr=true
stdout_logfile=/var/www/thaiprompt/storage/logs/worker.log
stopwaitsecs=3600
```

เริ่มใช้งาน:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start thaiprompt-worker:*
```

### 4. ตั้งค่า Cron (Task Scheduler)

```bash
sudo crontab -e -u deployer
```

เพิ่ม:
```
* * * * * cd /var/www/thaiprompt && php artisan schedule:run >> /dev/null 2>&1
```

---

## SSL Certificate

### ใช้ Let's Encrypt (ฟรี)

#### 1. ติดตั้ง Certbot

```bash
sudo apt install -y certbot python3-certbot-nginx
```

#### 2. สร้าง Certificate

```bash
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com
```

ตอบคำถาม:
- Email: your-email@example.com
- Agree to terms: Y
- Share email: N (optional)
- Redirect HTTP to HTTPS: 2 (Yes)

#### 3. ทดสอบ Auto-renewal

```bash
sudo certbot renew --dry-run
```

Certificate จะ auto-renew ทุก 90 วัน

#### 4. อัพเดท .env

```env
APP_URL=https://yourdomain.com
SESSION_SECURE_COOKIE=true
```

Reload config:
```bash
php artisan config:cache
```

---

## Performance Optimization

### 1. Enable OPcache

แก้ไข `/etc/php/8.1/fpm/php.ini`:

```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.validate_timestamps=0
opcache.save_comments=1
opcache.fast_shutdown=1
```

Restart PHP-FPM:
```bash
sudo systemctl restart php8.1-fpm
```

### 2. MySQL Optimization

แก้ไข `/etc/mysql/mysql.conf.d/mysqld.cnf`:

```ini
[mysqld]
innodb_buffer_pool_size = 2G
innodb_log_file_size = 512M
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT
query_cache_size = 0
query_cache_type = 0
max_connections = 200
```

Restart MySQL:
```bash
sudo systemctl restart mysql
```

### 3. Redis Configuration

แก้ไข `/etc/redis/redis.conf`:

```ini
maxmemory 512mb
maxmemory-policy allkeys-lru
```

Restart Redis:
```bash
sudo systemctl restart redis
```

### 4. Nginx Optimization

เพิ่มใน `/etc/nginx/nginx.conf`:

```nginx
worker_processes auto;
worker_connections 4096;

gzip on;
gzip_vary on;
gzip_proxied any;
gzip_comp_level 6;
gzip_types text/plain text/css text/xml text/javascript application/json application/javascript application/xml+rss;

client_max_body_size 20M;
client_body_buffer_size 128k;

fastcgi_buffers 16 16k;
fastcgi_buffer_size 32k;
```

### 5. Laravel Optimization

```bash
# Optimize autoloader
composer install --optimize-autoloader --no-dev --classmap-authoritative

# Cache everything
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Clear old cache
php artisan cache:clear
```

---

## Monitoring & Logging

### 1. Application Logs

```bash
# ดู logs แบบ real-time
tail -f /var/www/thaiprompt/storage/logs/laravel.log

# ดู error logs
grep "ERROR" /var/www/thaiprompt/storage/logs/laravel.log
```

### 2. Nginx Logs

```bash
tail -f /var/log/nginx/access.log
tail -f /var/log/nginx/error.log
```

### 3. MySQL Slow Query Log

แก้ไข `/etc/mysql/mysql.conf.d/mysqld.cnf`:

```ini
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow-query.log
long_query_time = 2
```

### 4. ติดตั้ง Laravel Telescope (Development)

```bash
composer require laravel/telescope
php artisan telescope:install
php artisan migrate
```

เข้าถึงที่: `https://yourdomain.com/telescope`

### 5. ใช้ External Monitoring

**Uptime Monitoring:**
- [UptimeRobot](https://uptimerobot.com/) - Free
- [Pingdom](https://www.pingdom.com/)

**Application Monitoring:**
- [New Relic](https://newrelic.com/)
- [Datadog](https://www.datadoghq.com/)
- [Sentry](https://sentry.io/) - สำหรับ error tracking

---

## Backup Strategy

### 1. Database Backup

#### Manual Backup

```bash
# Backup
mysqldump -u thaiprompt -p thaiprompt_marketplace > backup_$(date +%Y%m%d).sql

# Restore
mysql -u thaiprompt -p thaiprompt_marketplace < backup_20250124.sql
```

#### Automated Backup Script

สร้าง `/usr/local/bin/backup-db.sh`:

```bash
#!/bin/bash
BACKUP_DIR="/var/backups/mysql"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
DB_NAME="thaiprompt_marketplace"
DB_USER="thaiprompt"
DB_PASS="your_password"

mkdir -p $BACKUP_DIR

mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/backup_$TIMESTAMP.sql.gz

# ลบ backup เก่าที่เกิน 30 วัน
find $BACKUP_DIR -name "*.sql.gz" -mtime +30 -delete
```

ให้สิทธิ์:
```bash
sudo chmod +x /usr/local/bin/backup-db.sh
```

เพิ่มใน crontab:
```bash
sudo crontab -e
```

```
0 2 * * * /usr/local/bin/backup-db.sh
```

### 2. File Backup

#### rsync ไป Remote Server

```bash
rsync -avz --delete /var/www/thaiprompt/storage/ user@backup-server:/backups/thaiprompt/
```

#### S3 Backup

ติดตั้ง AWS CLI:
```bash
curl "https://awscli.amazonaws.com/awscli-exe-linux-x86_64.zip" -o "awscliv2.zip"
unzip awscliv2.zip
sudo ./aws/install
```

Configure:
```bash
aws configure
```

Backup script:
```bash
aws s3 sync /var/www/thaiprompt/storage/ s3://your-bucket/thaiprompt-backups/
```

### 3. ใช้ Laravel Backup Package

```bash
composer require spatie/laravel-backup
php artisan vendor:publish --provider="Spatie\Backup\BackupServiceProvider"
```

แก้ไข `config/backup.php`:

```php
'destination' => [
    'disks' => [
        'local',
        's3',
    ],
],
```

รัน backup:
```bash
php artisan backup:run
```

Schedule ใน `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('backup:clean')->daily()->at('01:00');
    $schedule->command('backup:run')->daily()->at('02:00');
}
```

---

## CI/CD Pipeline

### GitHub Actions

สร้าง `.github/workflows/deploy.yml`:

```yaml
name: Deploy to Production

on:
  push:
    branches: [ main ]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2

      - name: Deploy to Server
        uses: appleboy/ssh-action@master
        with:
          host: ${{ secrets.HOST }}
          username: ${{ secrets.USERNAME }}
          key: ${{ secrets.SSH_KEY }}
          script: |
            cd /var/www/thaiprompt
            git pull origin main
            composer install --optimize-autoloader --no-dev
            npm install
            npm run build
            php artisan migrate --force
            php artisan config:cache
            php artisan route:cache
            php artisan view:cache
            sudo supervisorctl restart thaiprompt-worker:*
```

### Deploy Script

สร้าง `deploy.sh`:

```bash
#!/bin/bash

echo "🚀 Starting deployment..."

# 1. Pull latest code
git pull origin main

# 2. Install dependencies
composer install --optimize-autoloader --no-dev
npm install

# 3. Build assets
npm run build

# 4. Run migrations
php artisan migrate --force

# 5. Clear old cache
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# 6. Cache everything
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 7. Restart queue workers
sudo supervisorctl restart thaiprompt-worker:*

# 8. Reload PHP-FPM
sudo systemctl reload php8.1-fpm

echo "✅ Deployment completed!"
```

ใช้งาน:
```bash
chmod +x deploy.sh
./deploy.sh
```

---

## Troubleshooting

### 1. 500 Internal Server Error

```bash
# ตรวจสอบ logs
tail -f /var/www/thaiprompt/storage/logs/laravel.log
tail -f /var/log/nginx/error.log

# ตรวจสอบ permissions
sudo chown -R deployer:www-data /var/www/thaiprompt
sudo chmod -R 775 storage bootstrap/cache
```

### 2. Database Connection Failed

```bash
# ทดสอบการเชื่อมต่อ
php artisan db:show

# ตรวจสอบ MySQL running
sudo systemctl status mysql

# ตรวจสอบ credentials ใน .env
```

### 3. Queue Not Processing

```bash
# ตรวจสอบ worker status
sudo supervisorctl status thaiprompt-worker:*

# Restart workers
sudo supervisorctl restart thaiprompt-worker:*

# ดู worker logs
tail -f /var/www/thaiprompt/storage/logs/worker.log
```

### 4. High CPU Usage

```bash
# ตรวจสอบ processes
top
htop

# ตรวจสอบ slow queries
sudo tail -f /var/log/mysql/slow-query.log
```

### 5. Out of Memory

```bash
# เช็ค memory usage
free -h

# เพิ่ม swap space
sudo fallocate -l 4G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile
sudo swapon /swapfile
```

---

## Security Checklist

- [ ] ใช้ HTTPS (SSL Certificate)
- [ ] `APP_DEBUG=false`
- [ ] Strong database passwords
- [ ] Firewall configured (UFW)
- [ ] SSH key authentication (disable password login)
- [ ] Regular security updates
- [ ] Fail2ban installed
- [ ] File permissions ถูกต้อง
- [ ] Environment variables ปลอดภัย
- [ ] Rate limiting enabled
- [ ] CSRF protection enabled
- [ ] XSS protection enabled
- [ ] SQL injection protection

---

**🎉 Deployment สำเร็จ!**

Server พร้อมใช้งานแล้วที่: https://yourdomain.com

สำหรับข้อมูลเพิ่มเติม:
- [Installation Guide](./INSTALLATION_GUIDE.md)
- [Configuration Guide](./CONFIGURATION.md)
- [API Documentation](./API_DOCUMENTATION.md)
