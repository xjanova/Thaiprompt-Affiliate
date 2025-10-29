# 🚀 คู่มือติดตั้ง TP-Affiliate บน Production Server

คู่มือนี้สำหรับการติดตั้งบน **Production Server** ที่มี **PHP** และ **MySQL** เตรียมไว้แล้ว

---

## 📋 สิ่งที่ต้องเตรียม

### 1. ข้อมูล MySQL Database

| ข้อมูล | ตัวอย่าง | คำอธิบาย |
|--------|----------|----------|
| **DB Host** | `127.0.0.1` หรือ `localhost` | IP/hostname ของ MySQL server |
| **DB Port** | `3306` | Port ของ MySQL (default: 3306) |
| **Database Name** | `thaiprompt_affiliate` | ชื่อ database ที่สร้างไว้แล้ว |
| **DB Username** | `tpadmin` | Username สำหรับเข้าถึง database |
| **DB Password** | `your_password` | Password ของ username |

### 2. สิ่งที่ต้องมีบน Server

✅ PHP 8.1 ขึ้นไป (พร้อม extensions: mysql, pdo, mbstring, xml, curl, zip)
✅ Composer
✅ MySQL/MariaDB
✅ Git
✅ Web Server (Nginx หรือ Apache)

---

## 🔧 ขั้นตอนการติดตั้ง

### Step 1: เตรียม MySQL Database

เข้า MySQL และสร้าง database:

```bash
mysql -u root -p
```

```sql
-- สร้าง database
CREATE DATABASE thaiprompt_affiliate CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- สร้าง user เฉพาะสำหรับ application (แนะนำ)
CREATE USER 'tpadmin'@'localhost' IDENTIFIED BY 'your_secure_password';

-- ให้สิทธิ์
GRANT ALL PRIVILEGES ON thaiprompt_affiliate.* TO 'tpadmin'@'localhost';
FLUSH PRIVILEGES;

-- ออกจาก MySQL
EXIT;
```

**หมายเหตุ:**
- แทนที่ `your_secure_password` ด้วย password จริง
- ถ้าใช้ remote MySQL ให้เปลี่ยน `'localhost'` เป็น `'%'` หรือ IP ที่ต้องการ

---

### Step 2: Clone Repository

```bash
# ไปที่ directory ที่ต้องการติดตั้ง (เช่น /var/www หรือ public_html)
cd /var/www

# Clone repository (ต้องมี Personal Access Token)
git clone https://github.com/xjanova/Thaiprompt-Affiliate.git
cd Thaiprompt-Affiliate
```

💡 **ไม่มี Token?** ดู [AUTHENTICATION.md](AUTHENTICATION.md)

---

### Step 3: รัน Installation Script

```bash
# ให้สิทธิ์ execute
chmod +x install.sh

# รัน script
./install.sh
```

### ตัวอย่าง Output และการกรอกข้อมูล

```
╔══════════════════════════════════════════════════╗
║   🚀 TP-Affiliate Installation Wizard            ║
║   Thai Prompt Affiliate Marketing Platform      ║
║   Production Server Setup                        ║
╚══════════════════════════════════════════════════╝

ℹ กำลังตรวจสอบระบบ...

✓ PHP 8.2.12
✓ Composer 2.6.5

════════════════════════════════════════
  🔧 Laravel Installation
════════════════════════════════════════

ℹ [1/7] Preparing Laravel directories...
✓ Directories created

ℹ [2/7] Installing Composer dependencies...
Loading composer repositories with package information
Installing dependencies from lock file (including require-dev)
Package operations: 120 installs, 0 updates, 0 removals
  ...
✓ Dependencies installed

ℹ [3/7] Setting up environment file...
✓ Environment file created

ℹ [4/7] Generating application key...
✓ Application key generated

════════════════════════════════════════
  📊 MySQL Database Configuration
════════════════════════════════════════

ℹ กรุณากรอกข้อมูล MySQL ที่เตรียมไว้:

  DB Host [127.0.0.1]: 127.0.0.1
  DB Port [3306]: 3306
  Database Name [thaiprompt_affiliate]: thaiprompt_affiliate
  DB Username: tpadmin
  DB Password: ********

ℹ [5/7] Configuring database connection...
✓ Database configuration updated
ℹ ทดสอบการเชื่อมต่อ MySQL...
✓ MySQL connection successful

ℹ [6/7] Running database migrations...
   INFO  Preparing database.

  Creating migration table ....................... 28ms DONE

   INFO  Running migrations.

  2024_01_01_000001_create_users_table ........... 18ms DONE
  2024_01_01_000002_create_affiliates_table ...... 15ms DONE
  2024_01_01_000003_create_commissions_table ..... 12ms DONE
  2024_01_01_000004_create_settings_table ........ 9ms DONE
  2024_01_01_000005_create_cache_table ........... 8ms DONE
  2024_01_01_000006_create_jobs_table ............ 10ms DONE

✓ Database migrated successfully

ℹ [7/7] Setting permissions...
✓ Permissions configured

════════════════════════════════════════
  ⚡ Optimizing for Production
════════════════════════════════════════

ℹ Caching configuration...
ℹ Caching routes...
ℹ Caching views...
✓ Optimization complete

════════════════════════════════════════
  ✅ Installation Complete!
════════════════════════════════════════

✓ ติดตั้ง TP-Affiliate สำเร็จ!

ℹ ขั้นตอนถัดไป:

  1. ตั้งค่า Web Server (Nginx/Apache) ให้ชี้ไปที่ public/
  2. ตั้งค่า DocumentRoot: /var/www/Thaiprompt-Affiliate/public
  3. เปิด browser ไปที่ domain ของคุณ
  4. ระบบจะพาไปหน้า Setup Wizard เพื่อสร้าง Super Admin

ℹ ข้อมูลเพิ่มเติม:

  📖 Documentation: README.md
  🚀 Deployment Guide: DEPLOYMENT.md
  🔐 .env file: ตั้งค่า APP_ENV=production สำหรับ production

⚠ คำแนะนำความปลอดภัย:

  - เปลี่ยน APP_ENV=production ใน .env
  - ตั้ง APP_DEBUG=false ใน .env
  - ตรวจสอบ file permissions (storage และ bootstrap/cache)
  - ตั้งค่า SSL certificate
  - Backup database เป็นประจำ

✓ Installation Complete! 🎉
```

---

### Step 4: ตั้งค่า Environment สำหรับ Production

แก้ไขไฟล์ `.env`:

```bash
nano .env
```

แก้ไขค่าเหล่านี้:

```env
APP_NAME="TP-Affiliate"
APP_ENV=production          # เปลี่ยนเป็น production
APP_DEBUG=false             # ปิด debug mode
APP_URL=https://yourdomain.com  # URL จริงของคุณ

# Database (ตรวจสอบว่าถูกต้อง)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=thaiprompt_affiliate
DB_USERNAME=tpadmin
DB_PASSWORD=your_password

# Session (แนะนำ)
SESSION_DRIVER=database

# Cache (แนะนำ Redis ถ้ามี)
CACHE_DRIVER=file

# Queue (แนะนำ database หรือ redis)
QUEUE_CONNECTION=database
```

บันทึกและออก (Ctrl+X, Y, Enter)

---

### Step 5: ตั้งค่า Web Server

#### สำหรับ Nginx

สร้างไฟล์ config:

```bash
sudo nano /etc/nginx/sites-available/thaiprompt-affiliate
```

เพิ่ม configuration:

```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
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
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable site และ restart Nginx:

```bash
sudo ln -s /etc/nginx/sites-available/thaiprompt-affiliate /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

#### สำหรับ Apache

สร้างไฟล์ config:

```bash
sudo nano /etc/apache2/sites-available/thaiprompt-affiliate.conf
```

เพิ่ม configuration:

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    DocumentRoot /var/www/Thaiprompt-Affiliate/public

    <Directory /var/www/Thaiprompt-Affiliate/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/thaiprompt-error.log
    CustomLog ${APACHE_LOG_DIR}/thaiprompt-access.log combined
</VirtualHost>
```

Enable site และ restart Apache:

```bash
sudo a2ensite thaiprompt-affiliate
sudo a2enmod rewrite
sudo systemctl restart apache2
```

---

### Step 6: ตั้งค่า File Permissions

```bash
cd /var/www/Thaiprompt-Affiliate

# ตั้งค่า owner เป็น web server user
sudo chown -R www-data:www-data storage bootstrap/cache

# ตั้งค่า permissions
chmod -R 755 storage bootstrap/cache
```

**หมายเหตุ:** `www-data` อาจเป็น `nginx` หรือ `apache` ขึ้นอยู่กับระบบ

---

### Step 7: ตั้งค่า SSL (แนะนำ)

ใช้ Let's Encrypt (ฟรี):

```bash
# ติดตั้ง Certbot
sudo apt install certbot python3-certbot-nginx

# สร้าง SSL certificate
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com

# Auto-renewal test
sudo certbot renew --dry-run
```

---

### Step 8: เปิดเว็บไซต์และสร้าง Super Admin

1. เปิด browser ไปที่ `https://yourdomain.com`
2. ระบบจะ redirect ไปหน้า Setup Wizard อัตโนมัติ
3. กรอกข้อมูล Super Admin:
   - **ชื่อ**: ชื่อของคุณ
   - **อีเมล**: admin@yourdomain.com
   - **รหัสผ่าน**: รหัสผ่านที่แข็งแรง (8+ ตัวอักษร)
   - **ยืนยันรหัสผ่าน**: ใส่รหัสผ่านอีกครั้ง
4. คลิก **"สร้างบัญชี Super Admin"**
5. เข้าสู่ระบบสำเร็จ!

---

## 🔒 Security Checklist

- ✅ ตั้ง `APP_ENV=production` ใน .env
- ✅ ตั้ง `APP_DEBUG=false` ใน .env
- ✅ ใช้ SSL certificate (HTTPS)
- ✅ ตั้งค่า firewall (UFW/iptables)
- ✅ ตรวจสอบ file permissions
- ✅ สร้าง MySQL user เฉพาะแทนการใช้ root
- ✅ ตั้งค่า backup อัตโนมัติ
- ✅ Enable fail2ban
- ✅ อัพเดท packages เป็นประจำ

---

## 🐛 Troubleshooting

### ปัญหา: 500 Internal Server Error

**วิธีแก้:**
```bash
# เช็ค Laravel logs
tail -f storage/logs/laravel.log

# เช็ค web server logs
# Nginx:
sudo tail -f /var/log/nginx/error.log

# Apache:
sudo tail -f /var/log/apache2/error.log

# เช็ค permissions
ls -la storage bootstrap/cache
```

### ปัญหา: ไม่สามารถเขียนไฟล์ได้ (Permission denied)

**วิธีแก้:**
```bash
cd /var/www/Thaiprompt-Affiliate
sudo chown -R www-data:www-data storage bootstrap/cache
chmod -R 755 storage bootstrap/cache
```

### ปัญหา: Database connection failed

**วิธีแก้:**
```bash
# ทดสอบการเชื่อมต่อ MySQL
mysql -h 127.0.0.1 -u tpadmin -p thaiprompt_affiliate

# เช็คว่า MySQL รันอยู่
sudo systemctl status mysql

# เช็ค .env settings
cat .env | grep DB_
```

### ปัญหา: Route not found หรือ CSS ไม่โหลด

**วิธีแก้:**
```bash
# Clear cache ทั้งหมด
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# สร้าง cache ใหม่
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 🔄 การอัพเดทระบบ

```bash
cd /var/www/Thaiprompt-Affiliate

# เปิด maintenance mode
php artisan down

# Pull code ใหม่
git pull origin main

# อัพเดท dependencies
composer install --no-dev --optimize-autoloader

# รัน migrations
php artisan migrate --force

# Clear และ cache ใหม่
php artisan optimize

# ปิด maintenance mode
php artisan up
```

---

## 📞 ติดต่อ & สนับสนุน

- **GitHub**: [xjanova/Thaiprompt-Affiliate](https://github.com/xjanova/Thaiprompt-Affiliate)
- **Issues**: [Report a bug](https://github.com/xjanova/Thaiprompt-Affiliate/issues)
- **Documentation**: README.md

---

**🎉 ติดตั้งสำเร็จแล้ว! เริ่มใช้งาน TP-Affiliate ได้เลย!**
