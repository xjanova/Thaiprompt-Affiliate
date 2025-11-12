# TP-Affiliate Installation Guide

ยินดีต้อนรับสู่คู่มือการติดตั้ง TP-Affiliate! คู่มือนี้จะแนะนำคุณทีละขั้นตอนในการติดตั้งและตั้งค่าระบบให้พร้อมใช้งาน

## 📋 สารบัญ

1. [ความต้องการของระบบ](#ความต้องการของระบบ)
2. [การติดตั้งครั้งแรก](#การติดตั้งครั้งแรก)
3. [การตั้งค่า GitHub สำหรับ Deployment](#การตั้งค่า-github-สำหรับ-deployment)
4. [การตั้งค่า Web Server](#การตั้งค่า-web-server)
5. [การ Deploy อัปเดต](#การ-deploy-อัปเดต)
6. [การแก้ไขปัญหา](#การแก้ไขปัญหา)

---

## ความต้องการของระบบ

### Server Requirements

- **PHP**: 8.1.0 หรือสูงกว่า
- **MySQL**: 5.7+ หรือ MariaDB 10.3+
- **Composer**: 2.0+
- **Git**: สำหรับ version control และ deployment
- **Web Server**: Nginx หรือ Apache
- **RAM**: ขั้นต่ำ 512MB (แนะนำ 1GB+)
- **Disk Space**: ขั้นต่ำ 1GB

### PHP Extensions (Required)

```
- BCMath
- Ctype
- JSON
- Mbstring
- OpenSSL
- PDO
- PDO MySQL
- Tokenizer
- XML
- cURL
- Fileinfo
- GD
- Zip
```

### ตรวจสอบ PHP Extensions

```bash
php -m | grep -E 'bcmath|ctype|json|mbstring|openssl|pdo|pdo_mysql|tokenizer|xml|curl|fileinfo|gd|zip'
```

---

## การติดตั้งครั้งแรก

### ขั้นตอนที่ 1: Clone Repository

```bash
# Clone repository จาก GitHub
git clone https://github.com/YOUR_USERNAME/Thaiprompt-Affiliate.git
cd Thaiprompt-Affiliate

# หรือถ้าได้รับไฟล์ ZIP แล้ว
unzip Thaiprompt-Affiliate.zip
cd Thaiprompt-Affiliate
```

### ขั้นตอนที่ 2: รันสคริปต์ติดตั้ง

ระบบมีสคริปต์ติดตั้งอัตโนมัติที่จะช่วยให้คุณตั้งค่าทุกอย่างได้อย่างง่ายดาย:

```bash
chmod +x install.sh
./install.sh
```

สคริปต์จะถามข้อมูลดังนี้:

#### 1. Application Configuration
- **Application Name**: ชื่อแอปพลิเคชัน (เช่น "TP-Affiliate")
- **Application URL**: URL ของเว็บไซต์ (เช่น "https://yourdomain.com")
- **Environment**: เลือก Production หรือ Local/Development

#### 2. Database Configuration
- **Database Host**: ที่อยู่ของ MySQL server (ปกติคือ "127.0.0.1")
- **Database Port**: Port ของ MySQL (ปกติคือ "3306")
- **Database Name**: ชื่อฐานข้อมูล
- **Database Username**: ชื่อผู้ใช้ฐานข้อมูล
- **Database Password**: รหัสผ่านฐานข้อมูล

#### 3. Super Admin Account
- **Admin Name**: ชื่อผู้ดูแลระบบ
- **Admin Email**: อีเมลสำหรับเข้าสู่ระบบ
- **Admin Password**: รหัสผ่าน (ขั้นต่ำ 8 ตัวอักษร)

### สิ่งที่สคริปต์จะทำให้อัตโนมัติ

1. ✅ ตรวจสอบความต้องการของระบบ (PHP version, extensions)
2. ✅ สร้างไฟล์ `.env` จาก `.env.example`
3. ✅ Generate Application Key
4. ✅ สร้างโฟลเดอร์ที่จำเป็น (storage, bootstrap/cache)
5. ✅ ติดตั้ง Composer dependencies
6. ✅ สร้างฐานข้อมูลถ้ายังไม่มี
7. ✅ รัน database migrations
8. ✅ รัน database seeders
9. ✅ สร้างบัญชี Super Admin
10. ✅ ตั้งค่าพื้นฐาน (settings)
11. ✅ สร้าง storage symlink
12. ✅ Cache configuration

### หลังจากติดตั้งเสร็จ

เมื่อสคริปต์ทำงานเสร็จ คุณจะเห็นสรุปข้อมูลการติดตั้ง:

```
✅ Installation Complete!

📊 Installation Summary:

  Application:    TP-Affiliate
  URL:            https://yourdomain.com
  Environment:    production
  Database:       your_database@127.0.0.1
  Admin Email:    admin@example.com
```

---

## การตั้งค่า GitHub สำหรับ Deployment

ระบบใช้ `deploy.sh` สำหรับการอัปเดตอัตโนมัติจาก GitHub เมื่อมีการพัฒนาใหม่

### ขั้นตอนที่ 1: สร้าง GitHub Personal Access Token

1. ไปที่ GitHub Settings
   ```
   https://github.com/settings/tokens
   ```

2. คลิก **"Developer settings"** → **"Personal access tokens"** → **"Tokens (classic)"**

3. คลิก **"Generate new token (classic)"**

4. ตั้งค่า Token:
   - **Note**: "TP-Affiliate Deployment" (หรือชื่ออื่นที่คุณต้องการ)
   - **Expiration**: เลือกอายุของ token (แนะนำ: 90 days หรือ No expiration)
   - **Select scopes**: เลือก `repo` (รวมถึง sub-scopes ทั้งหมด)

5. คลิก **"Generate token"** และ**คัดลอก token ไว้ทันที** (จะไม่สามารถดูได้อีก!)

### ขั้นตอนที่ 2: ตั้งค่า Git Remote

```bash
# เพิ่ม remote URL พร้อม token
git remote set-url origin https://YOUR_TOKEN@github.com/YOUR_USERNAME/Thaiprompt-Affiliate.git

# ตรวจสอบว่าตั้งค่าถูกต้อง
git remote -v
```

**ตัวอย่าง:**
```bash
git remote set-url origin https://ghp_xxxxxxxxxxxxxxxxxxxx@github.com/xjanova/Thaiprompt-Affiliate.git
```

### ขั้นตอนที่ 3: ทดสอบการเชื่อมต่อ

```bash
# ทดสอบ fetch จาก remote
git fetch origin

# ถ้าสำเร็จ จะแสดง branches ที่มีอยู่
```

### การเก็บ Token ไว้อย่างปลอดภัย

**⚠️ สำคัญมาก:** อย่าแชร์ Personal Access Token ของคุณกับใคร และอย่า commit ลง repository!

**วิธีการเก็บที่ปลอดภัย:**

1. **ใช้ Git Credential Helper** (แนะนำ):
   ```bash
   # บน Linux
   git config --global credential.helper store
   
   # บน macOS
   git config --global credential.helper osxkeychain
   ```

2. **สร้างไฟล์ `.env.local`** (สำหรับ server production):
   ```bash
   # สร้างไฟล์เก็บ token
   echo "GITHUB_TOKEN=your_token_here" > ~/.github_token
   chmod 600 ~/.github_token
   
   # ใช้ใน deploy.sh (optional)
   source ~/.github_token
   ```

3. **ใช้ SSH แทน HTTPS** (alternative):
   ```bash
   # Generate SSH key
   ssh-keygen -t ed25519 -C "your_email@example.com"
   
   # เพิ่ม SSH key ไปที่ GitHub
   # Settings → SSH and GPG keys → New SSH key
   
   # เปลี่ยน remote เป็น SSH
   git remote set-url origin git@github.com:YOUR_USERNAME/Thaiprompt-Affiliate.git
   ```

---

## การตั้งค่า Web Server

### Nginx Configuration

สร้างไฟล์ `/etc/nginx/sites-available/tpaff iliate`:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name yourdomain.com www.yourdomain.com;
    
    # Redirect HTTP to HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;
    
    root /var/www/Thaiprompt-Affiliate/public;
    index index.php index.html;
    
    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    
    # Logs
    access_log /var/log/nginx/tpaffiliate_access.log;
    error_log /var/log/nginx/tpaffiliate_error.log;
    
    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    
    # Laravel Configuration
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    location ~ /\.(?!well-known).* {
        deny all;
    }
    
    # Static files caching
    location ~* \.(jpg|jpeg|gif|png|css|js|ico|xml|svg|woff|woff2|ttf|eot)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }
}
```

Enable site:
```bash
sudo ln -s /etc/nginx/sites-available/tpaffiliate /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### Apache Configuration

สร้างไฟล์ `/etc/apache2/sites-available/tpaffiliate.conf`:

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    
    # Redirect to HTTPS
    Redirect permanent / https://yourdomain.com/
</VirtualHost>

<VirtualHost *:443>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    
    DocumentRoot /var/www/Thaiprompt-Affiliate/public
    
    <Directory /var/www/Thaiprompt-Affiliate/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    # SSL Configuration
    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/yourdomain.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/yourdomain.com/privkey.pem
    
    # Logs
    ErrorLog ${APACHE_LOG_DIR}/tpaffiliate_error.log
    CustomLog ${APACHE_LOG_DIR}/tpaffiliate_access.log combined
</VirtualHost>
```

Enable site:
```bash
sudo a2ensite tpaffiliate
sudo a2enmod rewrite ssl
sudo systemctl reload apache2
```

### SSL Certificate (Let's Encrypt)

```bash
# ติดตั้ง Certbot
sudo apt install certbot python3-certbot-nginx  # สำหรับ Nginx
# หรือ
sudo apt install certbot python3-certbot-apache  # สำหรับ Apache

# สร้าง SSL certificate
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com
# หรือ
sudo certbot --apache -d yourdomain.com -d www.yourdomain.com

# Auto-renewal
sudo certbot renew --dry-run
```

### File Permissions

```bash
cd /var/www/Thaiprompt-Affiliate

# Set ownership
sudo chown -R www-data:www-data storage bootstrap/cache

# Set permissions
sudo chmod -R 775 storage bootstrap/cache
sudo find storage -type f -exec chmod 664 {} \;
sudo find bootstrap/cache -type f -exec chmod 664 {} \;
```

---

## การ Deploy อัปเดต

หลังจากติดตั้งครั้งแรกแล้ว ใช้ `deploy.sh` สำหรับการอัปเดตในอนาคต

### วิธีการ Deploy

```bash
# Deploy branch ปัจจุบัน
./deploy.sh

# หรือระบุ branch ที่ต้องการ deploy
./deploy.sh branch-name
```

### สิ่งที่ deploy.sh จะทำ

1. ✅ ตรวจสอบ branch ว่ามีอยู่บน remote
2. ✅ เปิด maintenance mode
3. ✅ Backup database อัตโนมัติ
4. ✅ Backup ไฟล์สำคัญ (.env, uploads, credentials)
5. ✅ Force sync code จาก GitHub (รีเซ็ตให้ตรงกับ remote)
6. ✅ Restore ไฟล์สำคัญที่ backup ไว้
7. ✅ Sync .env กับ .env.example (เพิ่ม variables ใหม่อัตโนมัติ)
8. ✅ Install/Update Composer dependencies
9. ✅ Run database migrations
10. ✅ Run database seeders (ถ้ามีการเปลี่ยนแปลง)
11. ✅ Cache config, routes, views
12. ✅ ปิด maintenance mode

### Auto-Retry on Timeout

`deploy.sh` มีระบบ auto-retry ในกรณีที่เกิด timeout:
- จะลองทำใหม่อัตโนมัติสูงสุด 3 ครั้ง
- รอ 10 วินาทีระหว่างการลองใหม่
- แสดง progress ของการลองใหม่

### Rollback (ในกรณีที่ deploy ผิดพลาด)

หลังจาก deploy เสร็จ จะมี rollback commands แสดงอัตโนมัติ:

```bash
# ตัวอย่าง rollback commands
git reset --hard abc123de && composer install --no-dev --optimize-autoloader && \
php artisan migrate:rollback && php artisan config:cache && \
php artisan route:cache && php artisan view:cache && php artisan up
```

คุณสามารถ copy command เหล่านี้ไปรันเพื่อ rollback ได้ทันที

---

## การตั้งค่าเพิ่มเติม

### Email Configuration

แก้ไขไฟล์ `.env`:

#### SMTP (Generic)
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.yourdomain.com
MAIL_PORT=587
MAIL_USERNAME=your-email@yourdomain.com
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"
```

#### Gmail SMTP
```env
GMAIL_SMTP_ENABLED=true
GMAIL_SMTP_HOST=smtp.gmail.com
GMAIL_SMTP_PORT=587
GMAIL_SMTP_USERNAME=your-gmail@gmail.com
GMAIL_SMTP_PASSWORD=your-app-password
GMAIL_SMTP_ENCRYPTION=tls
GMAIL_SMTP_FROM_EMAIL=your-gmail@gmail.com
GMAIL_SMTP_FROM_NAME="${APP_NAME}"
```

**หมายเหตุ:** สำหรับ Gmail ต้องสร้าง [App Password](https://myaccount.google.com/apppasswords) ก่อน

### Cloudflare Turnstile (Security)

```env
CLOUDFLARE_TURNSTILE_ENABLED=true
CLOUDFLARE_TURNSTILE_SITE_KEY=your-site-key
CLOUDFLARE_TURNSTILE_SECRET_KEY=your-secret-key
```

สมัครได้ที่: https://dash.cloudflare.com/turnstile

### Google Translate API (Optional)

```env
GOOGLE_TRANSLATE_ENABLED=true
GOOGLE_TRANSLATE_API_KEY=your-api-key
```

### Queue Configuration (สำหรับ high-traffic sites)

```env
QUEUE_CONNECTION=database

# หรือใช้ Redis (แนะนำ)
QUEUE_CONNECTION=redis
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

รัน Queue Worker:
```bash
# สร้าง systemd service
sudo nano /etc/systemd/system/tpaffiliate-worker.service
```

เพิ่มเนื้อหา:
```ini
[Unit]
Description=TP-Affiliate Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/Thaiprompt-Affiliate
ExecStart=/usr/bin/php /var/www/Thaiprompt-Affiliate/artisan queue:work --sleep=3 --tries=3
Restart=always

[Install]
WantedBy=multi-user.target
```

Enable และ start:
```bash
sudo systemctl enable tpaffiliate-worker
sudo systemctl start tpaffiliate-worker
sudo systemctl status tpaffiliate-worker
```

---

## การแก้ไขปัญหา

### ปัญหา: Permission Denied

```bash
# แก้ไข permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### ปัญหา: 500 Internal Server Error

1. ตรวจสอบ logs:
   ```bash
   tail -f storage/logs/laravel.log
   tail -f /var/log/nginx/error.log  # หรือ Apache
   ```

2. ตรวจสอบ .env:
   ```bash
   php artisan config:clear
   php artisan config:cache
   ```

3. ตรวจสอบ file permissions:
   ```bash
   ls -la storage/
   ls -la bootstrap/cache/
   ```

### ปัญหา: Storage Symlink ไม่ทำงาน

```bash
# ลบ symlink เดิม
rm public/storage

# สร้างใหม่
php artisan storage:link --force
```

### ปัญหา: Database Connection Failed

1. ตรวจสอบ MySQL service:
   ```bash
   sudo systemctl status mysql
   ```

2. ตรวจสอบ credentials ใน `.env`:
   ```bash
   # ทดสอบการเชื่อมต่อ
   mysql -h DB_HOST -u DB_USERNAME -p DB_DATABASE
   ```

3. ตรวจสอบว่า database มีอยู่:
   ```sql
   SHOW DATABASES;
   ```

### ปัญหา: Composer Install ล้มเหลว

```bash
# ลบ vendor และ composer.lock
rm -rf vendor composer.lock

# Clear cache
composer clear-cache

# Install ใหม่
composer install --no-dev --optimize-autoloader
```

### ปัญหา: Git Pull/Deploy ล้มเหลว

1. ตรวจสอบ git status:
   ```bash
   git status
   ```

2. Reset local changes:
   ```bash
   git reset --hard origin/your-branch
   git clean -fdx
   ```

3. ตรวจสอบ remote:
   ```bash
   git remote -v
   ```

### ปัญหา: Migration ล้มเหลว

```bash
# ดู migration status
php artisan migrate:status

# Rollback และ migrate ใหม่
php artisan migrate:rollback
php artisan migrate --force
```

### ปัญหา: Cache Issues

```bash
# ล้าง cache ทั้งหมด
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear

# Re-cache
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### ตรวจสอบ System Health

```bash
# ตรวจสอบ PHP version และ extensions
php -v
php -m

# ตรวจสอบ disk space
df -h

# ตรวจสอบ memory usage
free -h

# ตรวจสอบ database
php artisan db:show

# ตรวจสอบ routes
php artisan route:list

# ตรวจสอบ queue
php artisan queue:monitor
```

---

## Performance Optimization

### OPcache (PHP)

แก้ไข `/etc/php/8.2/fpm/php.ini`:

```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
opcache.fast_shutdown=1
```

### Redis Cache (แนะนำสำหรับ production)

```bash
# ติดตั้ง Redis
sudo apt install redis-server

# ติดตั้ง PHP Redis extension
sudo apt install php-redis

# Restart PHP-FPM
sudo systemctl restart php8.2-fpm
```

แก้ไข `.env`:
```env
CACHE_DRIVER=redis
SESSION_DRIVER=redis
```

---

## Backup และ Restore

### Automatic Backup (ใน deploy.sh)

`deploy.sh` จะ backup อัตโนมัติทุกครั้งที่ deploy:
- Database: `backups/db_backup_YYYYMMDD_HHMMSS.sql`
- Critical files: `backups/critical_YYYYMMDD_HHMMSS/`

### Manual Backup

```bash
# Backup database
mysqldump -u DB_USERNAME -p DB_DATABASE > backup.sql

# Backup uploads
tar -czf uploads_backup.tar.gz storage/app/public/

# Backup .env
cp .env .env.backup
```

### Restore

```bash
# Restore database
mysql -u DB_USERNAME -p DB_DATABASE < backup.sql

# Restore uploads
tar -xzf uploads_backup.tar.gz -C storage/app/

# Restore .env
cp .env.backup .env
```

---

## Security Checklist

- [ ] เปลี่ยน `APP_ENV` เป็น `production`
- [ ] ตั้ง `APP_DEBUG` เป็น `false`
- [ ] ใช้ HTTPS (SSL certificate)
- [ ] สร้าง `APP_KEY` ที่ unique
- [ ] เปลี่ยนรหัสผ่าน database เป็นรหัสที่ปลอดภัย
- [ ] Enable Cloudflare Turnstile
- [ ] ตั้งค่า file permissions อย่างถูกต้อง
- [ ] Enable firewall (UFW)
- [ ] ตั้งค่า fail2ban
- [ ] Backup database เป็นประจำ
- [ ] ตรวจสอบ logs เป็นประจำ

---

## การติดต่อและการสนับสนุน

ถ้าคุณพบปัญหาหรือต้องการความช่วยเหลือ:

1. ตรวจสอบ [README.md](README.md) สำหรับข้อมูลเพิ่มเติม
2. ตรวจสอบ logs: `storage/logs/laravel.log`
3. ตรวจสอบ deployment logs: `storage/logs/deployment.log`
4. ดู documentation เพิ่มเติมใน `/docs`

---

## สรุป Flow การติดตั้ง

```
1. Clone/Download โปรเจค
   ↓
2. รัน ./install.sh (ครั้งแรกเท่านั้น)
   ├─ ตั้งค่า .env
   ├─ สร้าง database
   ├─ ติดตั้ง dependencies
   ├─ รัน migrations
   └─ สร้าง super admin
   ↓
3. ตั้งค่า Web Server (Nginx/Apache)
   ├─ Point DocumentRoot to /public
   ├─ ตั้งค่า SSL
   └─ ตั้งค่า permissions
   ↓
4. ตั้งค่า GitHub Token
   └─ สำหรับใช้กับ deploy.sh
   ↓
5. Deploy อัปเดตในอนาคต: ./deploy.sh
```

---

**สุดท้าย:** อย่าลืมตั้งค่า backup อัตโนมัติและตรวจสอบ logs เป็นประจำ!

**Happy deploying! 🚀**
