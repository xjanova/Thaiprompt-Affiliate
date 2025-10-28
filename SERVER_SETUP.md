# คู่มือติดตั้งเซิร์ฟเวอร์สำหรับ ThaiPrompt Marketplace

คู่มือนี้จะแนะนำทุกขั้นตอนการติดตั้งเซิร์ฟเวอร์ Ubuntu สำหรับ production อย่างละเอียด

---

## 📋 สารบัญ

1. [ข้อกำหนดเซิร์ฟเวอร์](#ข้อกำหนดเซิร์ฟเวอร์)
2. [เตรียมเซิร์ฟเวอร์เบื้องต้น](#เตรียมเซิร์ฟเวอร์เบื้องต้น)
3. [ติดตั้ง PHP 8.2](#ติดตั้ง-php-82)
4. [ติดตั้ง MySQL](#ติดตั้ง-mysql)
5. [ติดตั้ง Nginx](#ติดตั้ง-nginx)
6. [ติดตั้ง Composer](#ติดตั้ง-composer)
7. [ติดตั้ง Node.js](#ติดตั้ง-nodejs)
8. [ติดตั้ง Redis](#ติดตั้ง-redis)
9. [ติดตั้งโปรเจคจาก GitHub](#ติดตั้งโปรเจคจาก-github)
10. [ตั้งค่า Nginx](#ตั้งค่า-nginx)
11. [ตั้งค่า SSL](#ตั้งค่า-ssl)
12. [ตั้งค่า Supervisor](#ตั้งค่า-supervisor)
13. [ทดสอบระบบ](#ทดสอบระบบ)

---

## ข้อกำหนดเซิร์ฟเวอร์

### ความต้องการขั้นต่ำ
- **CPU:** 2 cores
- **RAM:** 4GB
- **Storage:** 50GB SSD
- **OS:** Ubuntu 22.04 LTS หรือ 24.04 LTS

### แนะนำสำหรับ Production
- **CPU:** 4 cores
- **RAM:** 8GB
- **Storage:** 100GB SSD
- **OS:** Ubuntu 22.04 LTS หรือ 24.04 LTS

---

## เตรียมเซิร์ฟเวอร์เบื้องต้น

### 1. เชื่อมต่อเข้าเซิร์ฟเวอร์

```bash
ssh root@your-server-ip
```

### 2. อัพเดทระบบ

```bash
apt update
apt upgrade -y
```

### 3. ตั้งค่า Timezone

```bash
timedatectl set-timezone Asia/Bangkok
```

### 4. สร้าง Swap (ถ้า RAM น้อย)

```bash
# สร้าง swap 2GB
fallocate -l 2G /swapfile
chmod 600 /swapfile
mkswap /swapfile
swapon /swapfile

# ให้ทำงานอัตโนมัติหลัง reboot
echo '/swapfile none swap sw 0 0' | tee -a /etc/fstab

# ตรวจสอบ
free -h
```

### 5. ติดตั้งเครื่องมือพื้นฐาน

```bash
apt install -y curl wget git unzip software-properties-common
```

---

## ติดตั้ง PHP 8.2

### 1. เพิ่ม Repository

```bash
add-apt-repository ppa:ondrej/php -y
apt update
```

### 2. ติดตั้ง PHP และ Extensions

```bash
apt install -y \
  php8.2-fpm \
  php8.2-cli \
  php8.2-common \
  php8.2-mysql \
  php8.2-zip \
  php8.2-gd \
  php8.2-mbstring \
  php8.2-curl \
  php8.2-xml \
  php8.2-bcmath \
  php8.2-intl \
  php8.2-redis
```

### 3. ตรวจสอบ PHP

```bash
php -v
# ควรได้: PHP 8.2.x
```

### 4. แก้ไขการตั้งค่า PHP-FPM

แก้ไขไฟล์ `/etc/php/8.2/fpm/php.ini`:

```bash
nano /etc/php/8.2/fpm/php.ini
```

เปลี่ยนค่าเหล่านี้:

```ini
upload_max_filesize = 100M
post_max_size = 100M
memory_limit = 512M
max_execution_time = 300
```

### 5. เริ่มบริการ PHP-FPM

```bash
systemctl start php8.2-fpm
systemctl enable php8.2-fpm
systemctl status php8.2-fpm
```

---

## ติดตั้ง MySQL

### 1. ติดตั้ง MySQL Server

```bash
apt install -y mysql-server
```

### 2. รันการตั้งค่าความปลอดภัย

```bash
mysql_secure_installation
```

ตอบคำถาม:
- **Set root password?** Yes - ใส่รหัสผ่านที่แข็งแรง
- **Remove anonymous users?** Yes
- **Disallow root login remotely?** Yes
- **Remove test database?** Yes
- **Reload privilege tables?** Yes

### 3. สร้าง Database และ User

```bash
mysql -u root -p
```

ใน MySQL shell:

```sql
-- สร้าง database
CREATE DATABASE thaiprompt_marketplace CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- สร้าง user (เปลี่ยน password เป็นของคุณ)
CREATE USER 'thaiprompt'@'localhost' IDENTIFIED BY 'YOUR_STRONG_PASSWORD_HERE';

-- ให้สิทธิ์
GRANT ALL PRIVILEGES ON thaiprompt_marketplace.* TO 'thaiprompt'@'localhost';
FLUSH PRIVILEGES;

-- ออกจาก MySQL
EXIT;
```

### 4. ทดสอบการเชื่อมต่อ

```bash
mysql -u thaiprompt -p
# ใส่รหัสผ่านที่สร้างไว้
# ถ้าเข้าได้แสดงว่าสำเร็จ
```

---

## ติดตั้ง Nginx

### 1. ติดตั้ง Nginx

```bash
apt install -y nginx
```

### 2. เริ่มบริการ Nginx

```bash
systemctl start nginx
systemctl enable nginx
systemctl status nginx
```

### 3. ตั้งค่า Firewall (ถ้าใช้ UFW)

```bash
ufw allow 'Nginx Full'
ufw allow OpenSSH
ufw enable
```

### 4. ทดสอบ

เปิดเบราว์เซอร์ไปที่ `http://your-server-ip` ควรเห็นหน้า Nginx default

---

## ติดตั้ง Apache (ทางเลือกแทน Nginx)

**หมายเหตุ:** ถ้าคุณติดตั้ง Nginx แล้ว ไม่ต้องติดตั้ง Apache (เลือกอย่างใดอย่างหนึ่ง)

### 1. ติดตั้ง Apache

```bash
apt install -y apache2
```

### 2. เริ่มบริการ Apache

```bash
systemctl start apache2
systemctl enable apache2
systemctl status apache2
```

### 3. เปิดใช้งาน Modules ที่จำเป็น

```bash
# เปิดใช้งาน mod_rewrite (สำคัญสำหรับ Laravel)
a2enmod rewrite

# เปิดใช้งาน mod_headers
a2enmod headers

# เปิดใช้งาน mod_ssl (สำหรับ HTTPS)
a2enmod ssl

# Restart Apache
systemctl restart apache2
```

### 4. ตั้งค่า Firewall (ถ้าใช้ UFW)

```bash
ufw allow 'Apache Full'
ufw allow OpenSSH
ufw enable
```

### 5. ทดสอบ

เปิดเบราว์เซอร์ไปที่ `http://your-server-ip` ควรเห็นหน้า Apache default

---

## ติดตั้ง Composer

```bash
# ดาวน์โหลด Composer installer
curl -sS https://getcomposer.org/installer -o composer-setup.php

# ติดตั้ง
php composer-setup.php --install-dir=/usr/local/bin --filename=composer

# ลบไฟล์ installer
rm composer-setup.php

# ทดสอบ
composer --version
```

---

## ติดตั้ง Node.js

### วิธีที่ 1: ใช้ NodeSource Repository (แนะนำ)

```bash
# ติดตั้ง Node.js 20 LTS
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt install -y nodejs

# ทดสอบ
node --version
npm --version
```

### วิธีที่ 2: ใช้ NVM (สำหรับการจัดการหลายเวอร์ชัน)

```bash
# ติดตั้ง NVM
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.0/install.sh | bash

# โหลด NVM
source ~/.bashrc

# ติดตั้ง Node.js
nvm install 20
nvm use 20
```

---

## ติดตั้ง Redis

```bash
# ติดตั้ง Redis
apt install -y redis-server

# เริ่มบริการ
systemctl start redis-server
systemctl enable redis-server

# ทดสอบ
redis-cli ping
# ควรได้: PONG
```

---

## ติดตั้งโปรเจคจาก GitHub

### เตรียมข้อมูลก่อนเริ่ม

**คุณต้องเลือกวิธีการ Clone อย่างใดอย่างหนึ่ง:**

| วิธี | ข้อดี | ข้อเสี | เหมาะกับ |
|------|-------|--------|----------|
| **HTTPS** | ง่าย ไม่ต้องตั้งค่าอะไร | ต้องใส่ข้อมูลทุกครั้ง | ผู้เริ่มต้น, ใช้งานครั้งเดียว |
| **SSH** | ไม่ต้องใส่รหัสผ่าน | ต้องตั้งค่า SSH key | Production server, ใช้งานบ่อย |

---

### 1. สร้างโฟลเดอร์สำหรับโปรเจค

```bash
mkdir -p /var/www
cd /var/www
```

---

### 2. Clone Repository

เลือกวิธีที่เหมาะกับคุณ:

#### 📌 วิธีที่ 1: Clone ด้วย HTTPS (แนะนำสำหรับผู้เริ่มต้น)

**ขั้นตอนที่ 1: สร้าง Personal Access Token (PAT)**

1. เปิดเว็บ GitHub แล้วเข้าสู่ระบบ
2. คลิกที่รูปโปรไฟล์ (มุมขวาบน) → **Settings**
3. เลื่อนลงล่างสุด → **Developer settings**
4. **Personal access tokens** → **Tokens (classic)**
5. **Generate new token** → **Generate new token (classic)**
6. กรอกข้อมูล:
   - **Note:** `Production Server` (ชื่ออะไรก็ได้)
   - **Expiration:** `No expiration` หรือตามที่ต้องการ
   - **Select scopes:**
     - ✅ **repo** (เลือกทั้งหมดใน repo)
     - ✅ **workflow** (ถ้าใช้ GitHub Actions)
7. คลิก **Generate token**
8. **คัดลอก token ที่ได้** (จะไม่สามารถดูอีกครั้ง!)
   - ตัวอย่าง: `ghp_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx`

**ขั้นตอนที่ 2: Clone Repository ด้วย Token**

```bash
# แทนที่ YOUR_TOKEN ด้วย token ที่คัดลอกไว้
git clone https://YOUR_TOKEN@github.com/xjanova/Thaiprompt-Affiliate.git thaiprompt

# ตัวอย่าง:
# git clone https://ghp_AbCd1234XyZ9876543210@github.com/xjanova/Thaiprompt-Affiliate.git thaiprompt
```

**หรือจะให้ Git ถามรหัสผ่าน:**

```bash
git clone https://github.com/xjanova/Thaiprompt-Affiliate.git thaiprompt

# Git จะถาม:
# Username: [ชื่อ GitHub ของคุณ]
# Password: [ใส่ Personal Access Token ที่สร้างไว้]
```

**ตั้งค่าให้จำ Token (ไม่ต้องใส่ทุกครั้ง):**

```bash
cd thaiprompt
git config credential.helper store
# หรือให้จำเป็นเวลา (เช่น 1 ชั่วโมง)
git config credential.helper 'cache --timeout=3600'
```

---

#### 📌 วิธีที่ 2: Clone ด้วย SSH (แนะนำสำหรับ Production)

**ขั้นตอนที่ 1: สร้าง SSH Key บนเซิร์ฟเวอร์**

```bash
# สร้าง SSH key (กด Enter ทุกคำถาม)
ssh-keygen -t ed25519 -C "your-email@example.com"

# ดู Public Key ที่สร้างขึ้น
cat ~/.ssh/id_ed25519.pub
```

**คัดลอก output ทั้งหมด** ตัวอย่าง:
```
ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIAbCdEfGhIjKlMnOpQrStUvWxYz1234567890 your-email@example.com
```

**ขั้นตอนที่ 2: เพิ่ม SSH Key เข้า GitHub**

1. เปิดเว็บ GitHub แล้วเข้าสู่ระบบ
2. คลิกที่รูปโปรไฟล์ (มุมขวาบน) → **Settings**
3. **SSH and GPG keys**
4. **New SSH key**
5. กรอกข้อมูล:
   - **Title:** `Production Server` (ชื่ออะไรก็ได้)
   - **Key:** วาง Public Key ที่คัดลอกไว้
6. **Add SSH key**
7. ใส่รหัสผ่าน GitHub เพื่อยืนยัน

**ขั้นตอนที่ 3: ทดสอบการเชื่อมต่อ**

```bash
ssh -T git@github.com

# ถ้าสำเร็จจะได้:
# Hi xjanova! You've successfully authenticated, but GitHub does not provide shell access.
```

**ขั้นตอนที่ 4: Clone Repository**

```bash
git clone git@github.com:xjanova/Thaiprompt-Affiliate.git thaiprompt
```

---

### 3. เข้าไปในโฟลเดอร์โปรเจค

```bash
cd thaiprompt
```

**ตรวจสอบว่า Clone สำเร็จ:**

```bash
ls -la
# ควรเห็นไฟล์ composer.json, artisan, .env.example ฯลฯ

git status
# ควรเห็น: On branch main (หรือ master)
```

---

### 4. สร้างไฟล์ .env

```bash
cp .env.example .env
nano .env
```

แก้ไขค่าเหล่านี้:

```env
APP_NAME="ThaiPrompt Marketplace"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=thaiprompt_marketplace
DB_USERNAME=thaiprompt
DB_PASSWORD=YOUR_STRONG_PASSWORD_HERE

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### 5. ติดตั้ง Dependencies

```bash
# PHP dependencies
composer install --no-interaction --optimize-autoloader --no-dev

# JavaScript dependencies
npm install

# Build frontend assets
npm run build
```

### 6. Generate Application Key

```bash
php artisan key:generate
```

### 7. Run Migrations

```bash
php artisan migrate --force
```

### 8. สร้าง Storage Link

```bash
php artisan storage:link
```

### 9. ตั้งค่า Permissions

```bash
chown -R www-data:www-data /var/www/thaiprompt
chmod -R 755 /var/www/thaiprompt/storage
chmod -R 755 /var/www/thaiprompt/bootstrap/cache
```

### 10. Cache การตั้งค่า

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## ตั้งค่า Nginx

### 1. สร้างไฟล์ Configuration

```bash
nano /etc/nginx/sites-available/thaiprompt
```

วางโค้ดนี้:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name your-domain.com www.your-domain.com;
    root /var/www/thaiprompt/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    # Logs
    access_log /var/log/nginx/thaiprompt-access.log;
    error_log /var/log/nginx/thaiprompt-error.log;

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
        fastcgi_hide_header X-Powered-By;
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

**แก้ไข:** เปลี่ยน `your-domain.com` เป็นโดเมนของคุณ

### 2. เปิดใช้งาน Site

```bash
ln -s /etc/nginx/sites-available/thaiprompt /etc/nginx/sites-enabled/
```

### 3. ปิดใช้งาน Default Site

```bash
rm /etc/nginx/sites-enabled/default
```

### 4. ทดสอบและ Restart Nginx

```bash
nginx -t
systemctl restart nginx
```

---

## ตั้งค่า Apache (ทางเลือกแทน Nginx)

**หมายเหตุ:** ใช้ส่วนนี้ถ้าคุณเลือกใช้ Apache แทน Nginx

### 1. สร้างไฟล์ Virtual Host

```bash
nano /etc/apache2/sites-available/thaiprompt.conf
```

วางโค้ดนี้:

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    ServerAlias www.your-domain.com
    ServerAdmin admin@your-domain.com

    DocumentRoot /var/www/thaiprompt/public

    <Directory /var/www/thaiprompt/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # Logging
    ErrorLog ${APACHE_LOG_DIR}/thaiprompt-error.log
    CustomLog ${APACHE_LOG_DIR}/thaiprompt-access.log combined

    # Security Headers
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"

    # PHP Configuration
    <FilesMatch \.php$>
        SetHandler "proxy:unix:/var/run/php/php8.2-fpm.sock|fcgi://localhost"
    </FilesMatch>

    # Deny access to hidden files
    <DirectoryMatch "/\.">
        Require all denied
    </DirectoryMatch>
</VirtualHost>
```

**แก้ไข:** เปลี่ยน `your-domain.com` เป็นโดเมนของคุณ

### 2. เปิดใช้งาน PHP-FPM กับ Apache

```bash
# เปิดใช้งาน proxy modules
a2enmod proxy_fcgi
a2enconf php8.2-fpm

# Restart Apache
systemctl restart apache2
```

### 3. เปิดใช้งาน Site

```bash
# เปิดใช้งาน site ใหม่
a2ensite thaiprompt.conf

# ปิดใช้งาน default site
a2dissite 000-default.conf

# ทดสอบ configuration
apache2ctl configtest

# Restart Apache
systemctl restart apache2
```

### 4. ตรวจสอบสถานะ

```bash
systemctl status apache2
```

---

## ตั้งค่า SSL

### ใช้ Let's Encrypt (ฟรี)

### สำหรับ Nginx

#### 1. ติดตั้ง Certbot

```bash
apt install -y certbot python3-certbot-nginx
```

#### 2. สร้าง SSL Certificate

```bash
certbot --nginx -d your-domain.com -d www.your-domain.com
```

ตอบคำถาม:
- **Email:** your-email@example.com
- **Terms of Service:** Agree
- **Share email:** No
- **Redirect HTTP to HTTPS:** Yes (เลือก 2)

#### 3. ทดสอบ Auto-renewal

```bash
certbot renew --dry-run
```

Certificate จะ auto-renew ทุก 90 วัน

---

### สำหรับ Apache

#### 1. ติดตั้ง Certbot

```bash
apt install -y certbot python3-certbot-apache
```

#### 2. สร้าง SSL Certificate

```bash
certbot --apache -d your-domain.com -d www.your-domain.com
```

ตอบคำถาม:
- **Email:** your-email@example.com
- **Terms of Service:** Agree
- **Share email:** No
- **Redirect HTTP to HTTPS:** Yes (เลือก 2)

Certbot จะแก้ไข Virtual Host Configuration ให้อัตโนมัติ

#### 3. ตรวจสอบ Configuration

```bash
apache2ctl configtest
systemctl restart apache2
```

#### 4. ทดสอบ Auto-renewal

```bash
certbot renew --dry-run
```

Certificate จะ auto-renew ทุก 90 วัน

---

### อัพเดท .env (ทั้ง Nginx และ Apache)

```bash
nano /var/www/thaiprompt/.env
```

เปลี่ยน:

```env
APP_URL=https://your-domain.com
SESSION_SECURE_COOKIE=true
```

Reload config:

```bash
cd /var/www/thaiprompt
php artisan config:cache
```

---

## ตั้งค่า Supervisor

Supervisor ใช้สำหรับรัน Queue Worker แบบถาวร

### 1. ติดตั้ง Supervisor

```bash
apt install -y supervisor
```

### 2. สร้าง Configuration

```bash
nano /etc/supervisor/conf.d/thaiprompt.conf
```

วางโค้ดนี้:

```ini
[program:thaiprompt-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/thaiprompt/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/thaiprompt/storage/logs/worker.log
stopwaitsecs=3600
```

### 3. โหลด Configuration และเริ่มใช้งาน

```bash
supervisorctl reread
supervisorctl update
supervisorctl start thaiprompt-worker:*
supervisorctl status
```

### 4. ตั้งค่า Cron สำหรับ Scheduler

```bash
crontab -e -u www-data
```

เพิ่มบรรทัดนี้:

```
* * * * * cd /var/www/thaiprompt && php artisan schedule:run >> /dev/null 2>&1
```

---

## ทดสอบระบบ

### 1. ตรวจสอบบริการทั้งหมด

```bash
systemctl status nginx
systemctl status php8.2-fpm
systemctl status mysql
systemctl status redis-server
supervisorctl status
```

### 2. ทดสอบเว็บไซต์

เปิดเบราว์เซอร์ไปที่: `https://your-domain.com`

### 3. ตรวจสอบ Logs

```bash
# Laravel logs
tail -f /var/www/thaiprompt/storage/logs/laravel.log

# Nginx logs
tail -f /var/log/nginx/thaiprompt-error.log

# Worker logs
tail -f /var/www/thaiprompt/storage/logs/worker.log
```

---

## 🎉 เสร็จสิ้น!

ระบบของคุณพร้อมใช้งานแล้ว!

### ขั้นตอนต่อไป

1. **สร้างผู้ดูแลระบบ:**
   ```bash
   cd /var/www/thaiprompt
   php artisan tinker
   ```
   
   ใน tinker:
   ```php
   $user = new App\Models\User;
   $user->name = 'Admin';
   $user->email = 'admin@example.com';
   $user->password = bcrypt('your-password');
   $user->role = 'admin';
   $user->save();
   ```

2. **ตั้งค่าระบบผ่าน Admin Panel:**
   - เข้าสู่ระบบที่ `/login`
   - ไปที่ Admin > Settings
   - กำหนดค่าต่างๆ

3. **Deploy อัพเดทใหม่ในอนาคต:**
   ```bash
   cd /var/www/thaiprompt
   ./deploy.sh
   ```

---

## 🆘 Troubleshooting

### ปัญหา: 500 Internal Server Error

```bash
# ตรวจสอบ logs
tail -f /var/www/thaiprompt/storage/logs/laravel.log
tail -f /var/log/nginx/thaiprompt-error.log

# ตรวจสอบ permissions
chown -R www-data:www-data /var/www/thaiprompt
chmod -R 755 /var/www/thaiprompt/storage
chmod -R 755 /var/www/thaiprompt/bootstrap/cache
```

### ปัญหา: Database Connection Failed

```bash
# ทดสอบ MySQL
mysql -u thaiprompt -p

# ตรวจสอบ .env
cat /var/www/thaiprompt/.env | grep DB_
```

### ปัญหา: CSS/JS ไม่โหลด

```bash
cd /var/www/thaiprompt
npm run build
php artisan config:cache
```

---

**สำหรับข้อมูลเพิ่มเติม:**
- [DEPLOYMENT.md](./DEPLOYMENT.md) - คู่มือ Deployment แบบละเอียด
- [GitHub Issues](https://github.com/xjanova/Thaiprompt-Affiliate/issues) - รายงานปัญหา
