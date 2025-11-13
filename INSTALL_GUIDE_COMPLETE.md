# 📘 คู่มือติดตั้ง TP-Affiliate ฉบับสมบูรณ์

> **Ultimate Installation Guide** - คู่มือติดตั้งและตั้งค่าครบวงจรสำหรับ TP-Affiliate Pro
> เวอร์ชัน: 2.171.0 | อัปเดตล่าสุด: 2025-01-13

---

## 📋 สารบัญ

1. [ภาพรวมระบบ install.sh](#ภาพรวมระบบ-installsh)
2. [การเตรียมความพร้อมก่อนติดตั้ง](#การเตรียมความพร้อมก่อนติดตั้ง)
3. [การติดตั้งแบบ Step-by-Step](#การติดตั้งแบบ-step-by-step)
4. [การตั้งค่าหลังการติดตั้ง](#การตั้งค่าหลังการติดตั้ง)
5. [การตั้งค่า Web Server](#การตั้งค่า-web-server)
6. [การตั้งค่า Email และ Services](#การตั้งค่า-email-และ-services)
7. [การแก้ไขปัญหาที่อาจพบ](#การแก้ไขปัญหาที่อาจพบ)
8. [Performance Optimization](#performance-optimization)
9. [Security Hardening](#security-hardening)
10. [Backup และ Maintenance](#backup-และ-maintenance)

---

## 🎯 ภาพรวมระบบ install.sh

### ระดับความพร้อม: ★★★★★ (10/10)

ระบบ `install.sh` ของ TP-Affiliate ได้รับการพัฒนาอย่างครบถ้วน **พร้อมใช้งานจริง 100%** โดยมีคุณสมบัติระดับ Enterprise:

#### ✨ คุณสมบัติหลัก

| คุณสมบัติ | สถานะ | รายละเอียด |
|----------|-------|-----------|
| **System Requirements Check** | ✅ พร้อม | ตรวจสอบ PHP 8.1+, Extensions ครบทั้ง 12 ตัว |
| **Interactive Configuration** | ✅ พร้อม | Wizard แบบโต้ตอบพร้อม Validation |
| **Database Auto-Setup** | ✅ พร้อม | สร้าง DB อัตโนมัติ + Test Connection |
| **Environment Config** | ✅ พร้อม | สร้าง .env พร้อม App Key Generation |
| **Dependencies Management** | ✅ พร้อม | Composer Install + Optimization |
| **Database Migrations** | ✅ พร้อม | Auto Migrate + Seed |
| **Super Admin Account** | ✅ พร้อม | สร้างบัญชีพร้อม Validation |
| **File Permissions** | ✅ พร้อม | Auto-detect Web Server User |
| **Cache Optimization** | ✅ พร้อม | Config/Route/View Caching |
| **Post-Install Verification** | ✅ พร้อม | ตรวจสอบทุกส่วนอัตโนมัติ |
| **Error Handling** | ✅ พร้อม | Graceful Error Messages |
| **Beautiful Output** | ✅ พร้อม | Colored Terminal UI |

#### ⚡ ความเร็วในการติดตั้ง

- **ระยะเวลาโดยรวม**: 5-10 นาที
- **System Check**: 30 วินาที
- **User Input**: 2-3 นาที
- **Dependencies Install**: 2-4 นาที
- **Database Setup**: 1-2 นาที
- **Optimization**: 30 วินาที

#### 🛡️ ระดับความปลอดภัย

- ✅ Validation ทุก Input
- ✅ Database Connection Testing
- ✅ Password Strength Check (min 8 chars)
- ✅ Email Format Validation
- ✅ Secure Password Hashing (bcrypt)
- ✅ Auto-backup .env เดิม
- ✅ Prevention ติดตั้งซ้ำโดยไม่ตั้งใจ

---

## 🚀 การเตรียมความพร้อมก่อนติดตั้ง

### ความต้องการของระบบ (System Requirements)

#### 📌 ข้อกำหนดขั้นต่ำ (Minimum)

| Component | Minimum | Recommended | หมายเหตุ |
|-----------|---------|-------------|----------|
| **PHP** | 8.1.0 | 8.2+ | PHP 8.3 supported |
| **MySQL** | 5.7 | 8.0+ | หรือ MariaDB 10.3+ |
| **RAM** | 512 MB | 2 GB+ | สำหรับ production |
| **Disk Space** | 1 GB | 5 GB+ | รวม uploads & logs |
| **CPU** | 1 Core | 2 Cores+ | Multi-core แนะนำ |
| **Composer** | 2.0+ | 2.6+ | Latest stable |
| **Git** | 2.0+ | 2.40+ | สำหรับ deployment |
| **Web Server** | - | Nginx 1.18+ | หรือ Apache 2.4+ |

#### 📦 PHP Extensions ที่จำเป็น

**Required (ต้องมีทั้งหมด 12 ตัว):**

```bash
# Core Extensions
✓ BCMath      - การคำนวณทศนิยมความแม่นยำสูง
✓ Ctype       - Character type checking
✓ JSON        - JSON encoding/decoding
✓ Mbstring    - Multibyte string support
✓ OpenSSL     - Encryption & SSL/TLS
✓ PDO         - Database abstraction
✓ pdo_mysql   - MySQL driver for PDO
✓ Tokenizer   - PHP tokenizer
✓ XML         - XML parsing
✓ cURL        - HTTP client
✓ Fileinfo    - File type detection
✓ GD          - Image processing
✓ Zip         - Zip compression
```

#### ตรวจสอบ PHP Extensions

```bash
# วิธีที่ 1: ตรวจสอบทีละตัว
php -m | grep -E 'bcmath|ctype|json|mbstring|openssl|pdo|pdo_mysql|tokenizer|xml|curl|fileinfo|gd|zip'

# วิธีที่ 2: ตรวจสอบว่าครบทุกตัวหรือไม่
cat << 'EOF' | bash
REQUIRED=(bcmath ctype json mbstring openssl pdo pdo_mysql tokenizer xml curl fileinfo gd zip)
MISSING=()
for ext in "${REQUIRED[@]}"; do
    if ! php -m | grep -qi "^$ext$"; then
        MISSING+=("$ext")
    fi
done
if [ ${#MISSING[@]} -eq 0 ]; then
    echo "✅ All required PHP extensions are installed!"
else
    echo "❌ Missing extensions: ${MISSING[*]}"
fi
EOF
```

#### ติดตั้ง PHP Extensions (ถ้ายังไม่มี)

**Ubuntu/Debian:**
```bash
# PHP 8.2
sudo apt update
sudo apt install -y php8.2 php8.2-fpm php8.2-cli \
    php8.2-bcmath php8.2-ctype php8.2-json \
    php8.2-mbstring php8.2-openssl php8.2-pdo \
    php8.2-mysql php8.2-tokenizer php8.2-xml \
    php8.2-curl php8.2-fileinfo php8.2-gd \
    php8.2-zip

# ตรวจสอบเวอร์ชัน
php -v
```

**CentOS/RHEL:**
```bash
# PHP 8.2
sudo dnf install -y php php-fpm php-cli \
    php-bcmath php-ctype php-json \
    php-mbstring php-openssl php-pdo \
    php-mysqlnd php-tokenizer php-xml \
    php-curl php-fileinfo php-gd \
    php-zip
```

### การติดตั้ง Composer

```bash
# ดาวน์โหลดและติดตั้ง Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer

# ตรวจสอบ
composer --version
# ควรได้: Composer version 2.6.x หรือสูงกว่า
```

### การติดตั้ง MySQL/MariaDB

**Ubuntu/Debian:**
```bash
# MySQL 8.0
sudo apt install -y mysql-server mysql-client

# หรือ MariaDB
sudo apt install -y mariadb-server mariadb-client

# เริ่มต้น service
sudo systemctl start mysql
sudo systemctl enable mysql

# Secure installation
sudo mysql_secure_installation
```

### การสร้าง Database และ User

```bash
# เข้าสู่ MySQL
sudo mysql -u root -p

# สร้าง database
CREATE DATABASE thaiprompt_affiliate CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# สร้าง user และให้สิทธิ์
CREATE USER 'tpaffiliate'@'localhost' IDENTIFIED BY 'your-strong-password';
GRANT ALL PRIVILEGES ON thaiprompt_affiliate.* TO 'tpaffiliate'@'localhost';
FLUSH PRIVILEGES;

# ตรวจสอบ
SHOW DATABASES;
SELECT User, Host FROM mysql.user;

# ออกจาก MySQL
EXIT;

# ทดสอบการเชื่อมต่อ
mysql -u tpaffiliate -p thaiprompt_affiliate
```

---

## 📥 การติดตั้งแบบ Step-by-Step

### ขั้นตอนที่ 1: Clone Repository

```bash
# เปลี่ยนไปยังโฟลเดอร์ที่ต้องการติดตั้ง
cd /var/www  # หรือตำแหน่งที่คุณต้องการ

# Clone repository
git clone https://github.com/xjanova/Thaiprompt-Affiliate.git

# เข้าสู่โฟลเดอร์โปรเจค
cd Thaiprompt-Affiliate

# ตรวจสอบ branch
git branch
git log --oneline -5
```

**หรือถ้าดาวน์โหลด ZIP มา:**

```bash
# แตกไฟล์
unzip Thaiprompt-Affiliate.zip
cd Thaiprompt-Affiliate

# ตั้งค่า Git (ถ้าต้องการ)
git init
git remote add origin https://github.com/xjanova/Thaiprompt-Affiliate.git
```

### ขั้นตอนที่ 2: เตรียม install.sh

```bash
# ตรวจสอบว่ามีไฟล์ install.sh
ls -lh install.sh

# เพิ่มสิทธิ์ในการรัน
chmod +x install.sh

# ตรวจสอบสิทธิ์
ls -lh install.sh
# ควรเห็น: -rwxr-xr-x
```

### ขั้นตอนที่ 3: รัน install.sh

```bash
# รัน installation script
./install.sh
```

### ขั้นตอนที่ 4: ตอบคำถามจาก Installer

Installer จะถามคำถามแบบ interactive ดังนี้:

#### 📝 4.1 Application Configuration

**คำถาม 1: Application Name**
```
Application Name [TP-Affiliate]:
```
- กด Enter ใช้ค่า default: `TP-Affiliate`
- หรือพิมพ์ชื่อที่ต้องการ เช่น: `My Affiliate System`

**คำถาม 2: Application URL**
```
Application URL (e.g., https://example.com):
```
- พิมพ์ URL ของเว็บไซต์ เช่น: `https://affiliate.yourdomain.com`
- **สำคัญ**: ใช้ `https://` สำหรับ production, `http://` สำหรับ local
- ตัวอย่าง:
  - Production: `https://affiliate.com`
  - Local: `http://localhost:8000`

**คำถาม 3: Environment**
```
Select environment:
  1) Production (recommended)
  2) Local/Development
Environment [1]:
```
- Production: กด `1` หรือ Enter (แนะนำสำหรับ server จริง)
- Local: กด `2` (สำหรับทดสอบบนเครื่องตัวเอง)

#### 🗄️ 4.2 Database Configuration

**คำถาม 4: Database Host**
```
Database Host [127.0.0.1]:
```
- กด Enter ใช้ค่า default: `127.0.0.1` (local)
- หรือพิมพ์ IP/hostname ของ database server

**คำถาม 5: Database Port**
```
Database Port [3306]:
```
- กด Enter ใช้ port default ของ MySQL: `3306`
- หรือระบุ port อื่น (ถ้ามีการเปลี่ยน)

**คำถาม 6: Database Name**
```
Database Name:
```
- พิมพ์ชื่อ database ที่สร้างไว้ เช่น: `thaiprompt_affiliate`
- **จำเป็นต้องกรอก** (ไม่มีค่า default)

**คำถาม 7: Database Username**
```
Database Username [root]:
```
- กด Enter ใช้ `root` (ไม่แนะนำสำหรับ production)
- หรือพิมพ์ username ที่สร้างไว้ เช่น: `tpaffiliate`

**คำถาม 8: Database Password**
```
Database Password:
```
- พิมพ์รหัสผ่าน database (จะไม่แสดงตัวอักษรขณะพิมพ์)
- **สำคัญ**: ใช้รหัสผ่านที่แข็งแรงสำหรับ production

**🔍 ระบบจะทดสอบการเชื่อมต่อ:**
```
ℹ Testing database connection...
✓ Database connection successful!
ℹ Creating database if not exists...
✓ Database created
```

#### 👤 4.3 Super Admin Account

**คำถาม 9: Admin Name**
```
Admin Name:
```
- พิมพ์ชื่อผู้ดูแลระบบ เช่น: `Admin` หรือ `John Doe`
- **จำเป็นต้องกรอก**

**คำถาม 10: Admin Email**
```
Admin Email:
```
- พิมพ์อีเมลสำหรับ login เช่น: `admin@yourdomain.com`
- **จำเป็นต้องกรอก** และต้องเป็นรูปแบบ email ที่ถูกต้อง
- ระบบจะตรวจสอบรูปแบบอัตโนมัติ

**คำถาม 11: Admin Password**
```
Admin Password (min 8 characters):
```
- พิมพ์รหัสผ่าน (ขั้นต่ำ 8 ตัวอักษร)
- **จะไม่แสดงตัวอักษรขณะพิมพ์**
- แนะนำ: ใช้รหัสผ่านที่มีตัวเลขและอักขระพิเศษด้วย

**คำถาม 12: Confirm Password**
```
Confirm Password:
```
- พิมพ์รหัสผ่านอีกครั้งเพื่อยืนยัน
- ต้องตรงกับรหัสผ่านที่พิมพ์ก่อนหน้านี้

### ขั้นตอนที่ 5: ระบบจะติดตั้งอัตโนมัติ

หลังจากตอบคำถามครบแล้ว ระบบจะดำเนินการต่อไปนี้โดยอัตโนมัติ:

#### 📦 Step 5: Creating Environment File
```
ℹ Creating .env file from .env.example...
✓ .env file created
ℹ Configuring .env file...
✓ .env file configured
ℹ Generating application key...
✓ Application key generated
```

#### 📚 Step 6: Installing Dependencies
```
ℹ Creating required directories...
✓ Directories created
ℹ Installing Composer dependencies...
This may take a few minutes...
✓ Dependencies installed
```

**หมายเหตุ**: การติดตั้ง dependencies อาจใช้เวลา 2-5 นาที ขึ้นอยู่กับความเร็ว internet

#### 🗃️ Step 7: Database Setup
```
ℹ Clearing configuration cache...
ℹ Running database migrations...
✓ Database migrations completed
ℹ Running database seeders...
✓ Database seeders completed
```

#### 👑 Step 8: Creating Super Admin Account
```
ℹ Creating super admin user...
Super admin created successfully!
✓ Super admin account created
ℹ Creating default settings...
Default settings created!
✓ Default settings created
```

#### ⚙️ Step 9: Finalization & Optimization
```
ℹ Creating storage symlink...
✓ Storage symlink created
ℹ Setting file permissions...
✓ Ownership set to youruser:www-data
✓ File permissions configured
ℹ Optimizing Composer autoloader...
✓ Autoloader optimized
ℹ Clearing all caches...
✓ All caches cleared
ℹ Building production caches...
✓ Production caches built
ℹ Marking installation as completed...
✓ Installation marked as completed
```

#### 🔍 Post-Installation Verification
```
ℹ Verifying installation...
✓ ✓ .env file exists
✓ ✓ Storage is writable
✓ ✓ Database connection OK
✓ ✓ Migrations completed (42 tables)
✓ ✓ Super Admin account created
✓ ✓ Storage symlink exists
✓ All critical checks passed!
```

### ขั้นตอนที่ 6: ติดตั้งเสร็จสมบูรณ์!

เมื่อติดตั้งเสร็จ คุณจะเห็นหน้าจอสรุปดังนี้:

```
════════════════════════════════════════
  ✅ Installation Complete & Optimized!
════════════════════════════════════════

📊 Installation Summary:

  Application:    TP-Affiliate
  URL:            https://yourdomain.com
  Environment:    production
  Database:       thaiprompt_affiliate@127.0.0.1
  Admin Email:    admin@yourdomain.com

✨ What was installed and optimized:
  ✅ System requirements verified
  ✅ Environment configured (.env)
  ✅ Composer dependencies installed
  ✅ Database created and migrated
  ✅ Database seeded with initial data
  ✅ Super Admin account created
  ✅ File permissions configured
  ✅ Composer autoloader optimized
  ✅ All caches built (config, routes, views)
  ✅ Storage symlink created
  ✅ System ready for production use!

🎉 Congratulations! TP-Affiliate is fully installed and optimized.

📋 Next Steps:

  1. Configure your web server (Nginx/Apache):
      → Point DocumentRoot to /var/www/Thaiprompt-Affiliate/public
      → See INSTALLATION.md for detailed web server config

  2. Access your application:
      → Frontend: https://yourdomain.com
      → Admin Panel: https://yourdomain.com/admin

  3. Login with your admin credentials:
      → Email: admin@yourdomain.com
      → Password: (the password you just set)

  4. Configure additional settings (optional):
      → Email (MAIL_*, GMAIL_*, SMTP_*)
      → Cloudflare Turnstile (CLOUDFLARE_TURNSTILE_*)
      → Google Translate API (GOOGLE_TRANSLATE_*)
      → Edit .env file to configure

🚀 Your system is ready to use immediately!
```

---

## ⚙️ การตั้งค่าหลังการติดตั้ง

### 1. ทดสอบระบบด้วย PHP Built-in Server

ก่อนตั้งค่า Web Server จริง ทดสอบว่าติดตั้งสำเร็จหรือไม่:

```bash
# รัน PHP development server
php artisan serve

# หรือระบุ host และ port
php artisan serve --host=0.0.0.0 --port=8000
```

เปิดเบราว์เซอร์ไปที่: `http://localhost:8000`

**ทดสอบ Admin Panel:**
- URL: `http://localhost:8000/admin`
- Email: (ที่ตั้งไว้ใน install.sh)
- Password: (ที่ตั้งไว้ใน install.sh)

### 2. ตรวจสอบสถานะระบบ

```bash
# ตรวจสอบ environment
php artisan env

# ตรวจสอบ database connection
php artisan db:show

# ตรวจสอบ migrations
php artisan migrate:status

# ตรวจสอบ routes
php artisan route:list | head -20

# ตรวจสอบ config
php artisan config:show app
php artisan config:show database
```

### 3. ตรวจสอบ File Permissions

```bash
# ตรวจสอบ permissions
ls -la storage/
ls -la bootstrap/cache/

# ถ้า permissions ไม่ถูกต้อง แก้ไขด้วย:
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

---

## 🌐 การตั้งค่า Web Server

### Option 1: Nginx (แนะนำ)

#### 1.1 ติดตั้ง Nginx

```bash
# Ubuntu/Debian
sudo apt update
sudo apt install -y nginx

# Start และ Enable
sudo systemctl start nginx
sudo systemctl enable nginx

# ตรวจสอบสถานะ
sudo systemctl status nginx
```

#### 1.2 สร้างไฟล์ Configuration

```bash
# สร้างไฟล์ config
sudo nano /etc/nginx/sites-available/tpaffiliate
```

**เนื้อหาไฟล์ (HTTP Only - สำหรับทดสอบ):**

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name yourdomain.com www.yourdomain.com;

    root /var/www/Thaiprompt-Affiliate/public;
    index index.php index.html index.htm;

    # Logs
    access_log /var/log/nginx/tpaffiliate_access.log;
    error_log /var/log/nginx/tpaffiliate_error.log;

    # Laravel Routes
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-FPM
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;

        # Increase timeout for large requests
        fastcgi_read_timeout 300;
    }

    # Deny access to hidden files
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

**เนื้อหาไฟล์ (HTTPS - สำหรับ Production):**

```nginx
# HTTP -> HTTPS Redirect
server {
    listen 80;
    listen [::]:80;
    server_name yourdomain.com www.yourdomain.com;

    # Redirect all HTTP to HTTPS
    return 301 https://$server_name$request_uri;
}

# HTTPS Server
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
    ssl_ciphers 'ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384';
    ssl_prefer_server_ciphers off;

    # HSTS (Optional but recommended)
    add_header Strict-Transport-Security "max-age=63072000" always;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;

    # Logs
    access_log /var/log/nginx/tpaffiliate_ssl_access.log;
    error_log /var/log/nginx/tpaffiliate_ssl_error.log;

    # Laravel Routes
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-FPM
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;

        # Increase timeout
        fastcgi_read_timeout 300;
        fastcgi_connect_timeout 300;
        fastcgi_send_timeout 300;

        # Buffer settings
        fastcgi_buffer_size 128k;
        fastcgi_buffers 256 16k;
        fastcgi_busy_buffers_size 256k;
        fastcgi_temp_file_write_size 256k;
    }

    # Deny access to hidden files
    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Static files caching
    location ~* \.(jpg|jpeg|gif|png|webp|css|js|ico|xml|svg|woff|woff2|ttf|eot)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    # Deny access to sensitive files
    location ~* \.(env|log|git)$ {
        deny all;
        return 404;
    }
}
```

#### 1.3 Enable Site และ Restart Nginx

```bash
# Enable site
sudo ln -s /etc/nginx/sites-available/tpaffiliate /etc/nginx/sites-enabled/

# ตรวจสอบ syntax
sudo nginx -t

# Reload Nginx
sudo systemctl reload nginx

# หรือ restart
sudo systemctl restart nginx
```

#### 1.4 ตั้งค่า SSL Certificate (Let's Encrypt)

```bash
# ติดตั้ง Certbot
sudo apt install -y certbot python3-certbot-nginx

# สร้าง SSL certificate
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com

# ทดสอบ auto-renewal
sudo certbot renew --dry-run

# ตรวจสอบ certificate
sudo certbot certificates
```

Certbot จะ:
1. ✅ สร้าง SSL certificate อัตโนมัติ
2. ✅ แก้ไข Nginx config ให้รองรับ HTTPS
3. ✅ ตั้งค่า auto-renewal (จะต่ออายุอัตโนมัติก่อนหมดอายุ)

### Option 2: Apache (Alternative)

#### 2.1 ติดตั้ง Apache

```bash
# Ubuntu/Debian
sudo apt update
sudo apt install -y apache2

# Enable required modules
sudo a2enmod rewrite ssl headers

# Start และ Enable
sudo systemctl start apache2
sudo systemctl enable apache2

# ตรวจสอบสถานะ
sudo systemctl status apache2
```

#### 2.2 สร้างไฟล์ Configuration

```bash
# สร้างไฟล์ config
sudo nano /etc/apache2/sites-available/tpaffiliate.conf
```

**เนื้อหาไฟล์:**

```apache
# HTTP -> HTTPS Redirect
<VirtualHost *:80>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com

    Redirect permanent / https://yourdomain.com/
</VirtualHost>

# HTTPS VirtualHost
<VirtualHost *:443>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com

    DocumentRoot /var/www/Thaiprompt-Affiliate/public

    <Directory /var/www/Thaiprompt-Affiliate/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # SSL Configuration
    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/yourdomain.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/yourdomain.com/privkey.pem
    Include /etc/letsencrypt/options-ssl-apache.conf

    # Logs
    ErrorLog ${APACHE_LOG_DIR}/tpaffiliate_error.log
    CustomLog ${APACHE_LOG_DIR}/tpaffiliate_access.log combined

    # Security Headers
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"
</VirtualHost>
```

#### 2.3 Enable Site และ Restart Apache

```bash
# Enable site
sudo a2ensite tpaffiliate.conf

# Disable default site (optional)
sudo a2dissite 000-default.conf

# ตรวจสอบ syntax
sudo apache2ctl configtest

# Reload Apache
sudo systemctl reload apache2
```

#### 2.4 ตั้งค่า SSL Certificate

```bash
# ติดตั้ง Certbot
sudo apt install -y certbot python3-certbot-apache

# สร้าง SSL certificate
sudo certbot --apache -d yourdomain.com -d www.yourdomain.com
```

---

## 📧 การตั้งค่า Email และ Services

### 1. Email Configuration

แก้ไขไฟล์ `.env`:

```bash
nano .env
```

#### Option 1: Gmail SMTP (แนะนำสำหรับเริ่มต้น)

```env
# Gmail SMTP Configuration
GMAIL_SMTP_ENABLED=true
GMAIL_SMTP_HOST=smtp.gmail.com
GMAIL_SMTP_PORT=587
GMAIL_SMTP_USERNAME=your-email@gmail.com
GMAIL_SMTP_PASSWORD=your-app-password
GMAIL_SMTP_ENCRYPTION=tls
GMAIL_SMTP_FROM_EMAIL=your-email@gmail.com
GMAIL_SMTP_FROM_NAME="TP-Affiliate"
```

**วิธีสร้าง Gmail App Password:**

1. ไปที่ https://myaccount.google.com/security
2. เปิด 2-Step Verification (ถ้ายังไม่ได้เปิด)
3. ไปที่ https://myaccount.google.com/apppasswords
4. เลือก "Mail" และ "Other (Custom name)"
5. ตั้งชื่อ เช่น "TP-Affiliate"
6. Copy App Password ที่ได้มาใส่ใน `GMAIL_SMTP_PASSWORD`

#### Option 2: Generic SMTP

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.yourdomain.com
MAIL_PORT=587
MAIL_USERNAME=noreply@yourdomain.com
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"
```

#### Option 3: Gmail API (สำหรับ high volume)

```env
GMAIL_API_ENABLED=true
GMAIL_API_USER_EMAIL=your-email@gmail.com
GMAIL_API_FROM_EMAIL=your-email@gmail.com
GMAIL_API_FROM_NAME="TP-Affiliate"
```

**Setup OAuth2:**
1. สร้าง Project ใน Google Cloud Console
2. Enable Gmail API
3. สร้าง OAuth 2.0 Credentials
4. Download credentials JSON
5. วางไฟล์ที่ `storage/app/gmail-credentials.json`
6. Authorize ผ่าน Admin Panel: `/admin/email/gmail/setup`

### 2. Cloudflare Turnstile (Security)

```env
CLOUDFLARE_TURNSTILE_ENABLED=true
CLOUDFLARE_TURNSTILE_SITE_KEY=0x4AAAA...
CLOUDFLARE_TURNSTILE_SECRET_KEY=0x4AAAA...
```

**วิธีสมัคร:**
1. ไปที่ https://dash.cloudflare.com/turnstile
2. Add Site
3. Copy Site Key และ Secret Key
4. ใส่ใน `.env`

### 3. Google Translate API (Optional)

```env
GOOGLE_TRANSLATE_ENABLED=true
GOOGLE_TRANSLATE_API_KEY=AIzaSyD...
```

**วิธีสมัคร:**
1. ไปที่ Google Cloud Console
2. Enable Cloud Translation API
3. สร้าง API Key
4. ใส่ใน `.env`

### 4. Apply Configuration Changes

หลังจากแก้ไข `.env` แล้ว:

```bash
# Clear และ rebuild config cache
php artisan config:clear
php artisan config:cache

# ทดสอบ email
php artisan tinker
```

ใน Tinker:
```php
Mail::raw('Test email from TP-Affiliate', function ($message) {
    $message->to('your-email@example.com')
            ->subject('Test Email');
});
```

---

## 🔧 การแก้ไขปัญหาที่อาจพบ

### ปัญหา 1: Permission Denied

**อาการ:**
```
The stream or file "storage/logs/laravel.log" could not be opened: failed to open stream: Permission denied
```

**วิธีแก้:**
```bash
# ตั้งค่า permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# หรือใช้ script
chmod +x fix-permissions.sh
./fix-permissions.sh
```

### ปัญหา 2: 500 Internal Server Error

**วิธีตรวจสอบ:**

1. ดู Laravel logs:
```bash
tail -f storage/logs/laravel.log
```

2. ดู Web Server logs:
```bash
# Nginx
sudo tail -f /var/log/nginx/error.log

# Apache
sudo tail -f /var/log/apache2/error.log
```

3. Enable Debug Mode (ระหว่างแก้ปัญหาเท่านั้น):
```bash
# แก้ไข .env
APP_DEBUG=true

# Clear cache
php artisan config:clear
```

**อย่าลืม:** ปิด Debug Mode หลังแก้ปัญหาเสร็จ:
```bash
APP_DEBUG=false
php artisan config:cache
```

### ปัญหา 3: Database Connection Failed

**ตรวจสอบ:**

1. ตรวจสอบ MySQL service:
```bash
sudo systemctl status mysql
```

2. ทดสอบ connection:
```bash
mysql -h 127.0.0.1 -u tpaffiliate -p thaiprompt_affiliate
```

3. ตรวจสอบ credentials ใน `.env`:
```bash
grep DB_ .env
```

4. Test Laravel connection:
```bash
php artisan db:show
php artisan db:monitor
```

### ปัญหา 4: Storage Symlink ไม่ทำงาน

**อาการ:** ไม่สามารถแสดงรูปภาพที่อัปโหลดได้

**วิธีแก้:**
```bash
# ลบ symlink เดิม
rm public/storage

# สร้างใหม่
php artisan storage:link --force

# ตรวจสอบ
ls -la public/ | grep storage
# ควรเห็น: storage -> ../storage/app/public
```

### ปัญหา 5: Composer Install ล้มเหลว

**วิธีแก้:**
```bash
# Clear Composer cache
composer clear-cache

# ลบ vendor และ lock file
rm -rf vendor composer.lock

# Install ใหม่
composer install --no-dev --optimize-autoloader

# หรือถ้ายังไม่ได้ ให้ update Composer
composer self-update
```

### ปัญหา 6: Migration ล้มเหลว

**วิธีแก้:**

1. ดู migration status:
```bash
php artisan migrate:status
```

2. Reset และ migrate ใหม่:
```bash
php artisan migrate:fresh --seed
```

**⚠️ คำเตือน:** `migrate:fresh` จะลบข้อมูลทั้งหมด! ใช้เฉพาะบนระบบใหม่

### ปัญหา 7: Cache Issues

**วิธีแก้:**
```bash
# ล้าง cache ทั้งหมด
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear

# Re-cache (สำหรับ production)
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### ปัญหา 8: PHP Extension Missing

**ตรวจสอบ:**
```bash
php -m | grep -i extension_name
```

**ติดตั้ง:**
```bash
# Ubuntu/Debian
sudo apt install php8.2-extension_name

# Restart PHP-FPM
sudo systemctl restart php8.2-fpm
```

---

## ⚡ Performance Optimization

### 1. OPcache Configuration

แก้ไข `/etc/php/8.2/fpm/php.ini`:

```ini
[opcache]
opcache.enable=1
opcache.enable_cli=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.max_wasted_percentage=10
opcache.validate_timestamps=0
opcache.revalidate_freq=0
opcache.save_comments=1
opcache.fast_shutdown=1
```

Restart PHP-FPM:
```bash
sudo systemctl restart php8.2-fpm
```

### 2. Redis Cache (แนะนำสำหรับ Production)

#### ติดตั้ง Redis

```bash
# ติดตั้ง Redis
sudo apt install -y redis-server php-redis

# Start และ Enable
sudo systemctl start redis-server
sudo systemctl enable redis-server

# ทดสอบ
redis-cli ping
# ควรได้: PONG

# Restart PHP-FPM
sudo systemctl restart php8.2-fpm
```

#### แก้ไข `.env`

```env
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1
```

#### Apply changes

```bash
php artisan config:clear
php artisan config:cache
```

### 3. Queue Worker (สำหรับ Background Jobs)

#### สร้าง Systemd Service

```bash
sudo nano /etc/systemd/system/tpaffiliate-worker.service
```

**เนื้อหา:**
```ini
[Unit]
Description=TP-Affiliate Queue Worker
After=network.target redis-server.service

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/Thaiprompt-Affiliate
ExecStart=/usr/bin/php /var/www/Thaiprompt-Affiliate/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

#### Enable และ Start

```bash
# Reload systemd
sudo systemctl daemon-reload

# Enable
sudo systemctl enable tpaffiliate-worker

# Start
sudo systemctl start tpaffiliate-worker

# ตรวจสอบ
sudo systemctl status tpaffiliate-worker
```

### 4. Database Optimization

```sql
-- เข้าสู่ MySQL
mysql -u root -p

USE thaiprompt_affiliate;

-- Optimize tables
OPTIMIZE TABLE users, affiliates, transactions;

-- Analyze tables
ANALYZE TABLE users, affiliates, transactions;

-- Add indexes (ถ้ายังไม่มี)
SHOW INDEX FROM users;
```

### 5. Laravel Optimization

```bash
# Optimize everything
php artisan optimize

# Cache config, routes, views
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Optimize autoloader
composer dump-autoload --optimize --classmap-authoritative
```

---

## 🔒 Security Hardening

### 1. File Permissions Security

```bash
# ตั้งค่า ownership
sudo chown -R www-data:www-data /var/www/Thaiprompt-Affiliate

# ตั้งค่า directory permissions
sudo find /var/www/Thaiprompt-Affiliate -type d -exec chmod 755 {} \;

# ตั้งค่า file permissions
sudo find /var/www/Thaiprompt-Affiliate -type f -exec chmod 644 {} \;

# ตั้งค่า storage และ cache
sudo chmod -R 775 storage bootstrap/cache
```

### 2. .env File Security

```bash
# ตั้งค่า .env ให้อ่านได้เฉพาะ owner
chmod 600 .env

# ตรวจสอบ
ls -la .env
# ควรเห็น: -rw------- 1 www-data www-data
```

### 3. Firewall (UFW)

```bash
# ติดตั้ง UFW
sudo apt install -y ufw

# อนุญาต SSH (สำคัญ! ทำก่อนเปิด firewall)
sudo ufw allow 22/tcp

# อนุญาต HTTP และ HTTPS
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# อนุญาต MySQL (ถ้าต้องการ remote access)
# sudo ufw allow from your_ip_address to any port 3306

# Enable UFW
sudo ufw enable

# ตรวจสอบ
sudo ufw status verbose
```

### 4. Fail2Ban (ป้องกัน Brute Force)

```bash
# ติดตั้ง
sudo apt install -y fail2ban

# สร้าง config
sudo nano /etc/fail2ban/jail.local
```

**เนื้อหา:**
```ini
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 5

[sshd]
enabled = true

[nginx-http-auth]
enabled = true

[nginx-limit-req]
enabled = true
```

```bash
# Start และ Enable
sudo systemctl start fail2ban
sudo systemctl enable fail2ban

# ตรวจสอบ
sudo fail2ban-client status
```

### 5. Security Headers (Nginx)

เพิ่มใน Nginx config:

```nginx
# Security Headers
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-Content-Type-Options "nosniff" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header Referrer-Policy "no-referrer-when-downgrade" always;
add_header Content-Security-Policy "default-src 'self' https:; script-src 'self' 'unsafe-inline' 'unsafe-eval' https:; style-src 'self' 'unsafe-inline' https:;" always;
```

### 6. Security Checklist

- [ ] ตั้ง `APP_ENV=production` ใน `.env`
- [ ] ตั้ง `APP_DEBUG=false` ใน `.env`
- [ ] ใช้ HTTPS (SSL certificate)
- [ ] สร้าง `APP_KEY` ที่ unique
- [ ] ใช้รหัสผ่าน database ที่แข็งแรง
- [ ] Enable Cloudflare Turnstile
- [ ] ตั้งค่า file permissions ถูกต้อง
- [ ] Enable firewall (UFW)
- [ ] ติดตั้ง fail2ban
- [ ] Disable directory listing
- [ ] ซ่อน .env จาก public access
- [ ] Update packages เป็นประจำ
- [ ] Backup ข้อมูลเป็นประจำ

---

## 💾 Backup และ Maintenance

### 1. Database Backup

#### Manual Backup

```bash
# สร้างโฟลเดอร์ backup
mkdir -p /var/backups/tpaffiliate

# Backup database
mysqldump -u tpaffiliate -p thaiprompt_affiliate > /var/backups/tpaffiliate/db_$(date +%Y%m%d_%H%M%S).sql

# Compress
gzip /var/backups/tpaffiliate/db_$(date +%Y%m%d_%H%M%S).sql
```

#### Automatic Backup (Cron)

```bash
# แก้ไข crontab
crontab -e
```

เพิ่มบรรทัด (backup ทุกวันเวลา 2:00 AM):
```cron
0 2 * * * /usr/bin/mysqldump -u tpaffiliate -pyour_password thaiprompt_affiliate | gzip > /var/backups/tpaffiliate/db_$(date +\%Y\%m\%d_\%H\%M\%S).sql.gz
```

#### Backup Script

สร้างไฟล์ `backup.sh`:

```bash
#!/bin/bash

# Configuration
BACKUP_DIR="/var/backups/tpaffiliate"
DB_NAME="thaiprompt_affiliate"
DB_USER="tpaffiliate"
DB_PASS="your_password"
APP_DIR="/var/www/Thaiprompt-Affiliate"
RETENTION_DAYS=30

# Create backup directory
mkdir -p "$BACKUP_DIR"/{database,files}

# Timestamp
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Backup Database
echo "Backing up database..."
mysqldump -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" | gzip > "$BACKUP_DIR/database/db_$TIMESTAMP.sql.gz"

# Backup Files
echo "Backing up files..."
tar -czf "$BACKUP_DIR/files/uploads_$TIMESTAMP.tar.gz" -C "$APP_DIR" storage/app/public

# Backup .env
cp "$APP_DIR/.env" "$BACKUP_DIR/files/env_$TIMESTAMP"

# Delete old backups
echo "Cleaning old backups..."
find "$BACKUP_DIR/database" -name "*.sql.gz" -mtime +$RETENTION_DAYS -delete
find "$BACKUP_DIR/files" -name "*.tar.gz" -mtime +$RETENTION_DAYS -delete

echo "Backup completed: $TIMESTAMP"
```

```bash
# ให้สิทธิ์รัน
chmod +x backup.sh

# ทดสอบ
./backup.sh

# เพิ่ม cron (ทุกวัน 2:00 AM)
crontab -e
```

เพิ่ม:
```cron
0 2 * * * /var/www/Thaiprompt-Affiliate/backup.sh >> /var/log/tpaffiliate-backup.log 2>&1
```

### 2. Restore from Backup

#### Restore Database

```bash
# Extract backup
gunzip /var/backups/tpaffiliate/db_20250113_020000.sql.gz

# Restore
mysql -u tpaffiliate -p thaiprompt_affiliate < /var/backups/tpaffiliate/db_20250113_020000.sql
```

#### Restore Files

```bash
# Extract uploads
tar -xzf /var/backups/tpaffiliate/uploads_20250113_020000.tar.gz -C /var/www/Thaiprompt-Affiliate/

# Restore .env
cp /var/backups/tpaffiliate/env_20250113_020000 /var/www/Thaiprompt-Affiliate/.env

# Fix permissions
sudo chown -R www-data:www-data /var/www/Thaiprompt-Affiliate/storage
sudo chmod -R 775 /var/www/Thaiprompt-Affiliate/storage
```

### 3. Maintenance Tasks

#### Daily Tasks

```bash
# ล้าง cache
php artisan cache:clear

# Optimize
php artisan optimize

# ล้าง logs เก่า (เก็บ 7 วันล่าสุด)
find storage/logs -name "*.log" -mtime +7 -delete
```

#### Weekly Tasks

```bash
# อัปเดต dependencies
composer update --no-dev --optimize-autoloader

# ตรวจสอบ security updates
composer audit

# Optimize database
php artisan db:optimize
```

#### Monthly Tasks

```bash
# ตรวจสอบ disk space
df -h

# ตรวจสอบ logs
tail -100 storage/logs/laravel.log

# Review users และ activities
php artisan users:audit

# Update SSL certificate (ถ้าใช้ Let's Encrypt)
sudo certbot renew
```

### 4. Monitoring

#### Laravel Horizon (สำหรับ Queue Monitoring)

```bash
# ติดตั้ง
composer require laravel/horizon

# Publish config
php artisan horizon:install

# Run
php artisan horizon
```

#### Laravel Telescope (สำหรับ Debug)

**⚠️ ใช้เฉพาะ development environment:**

```bash
# ติดตั้ง
composer require laravel/telescope --dev

# Install
php artisan telescope:install

# Migrate
php artisan migrate

# Access: https://yourdomain.com/telescope
```

---

## 📞 การติดต่อและการสนับสนุน

### เอกสารเพิ่มเติม

- 📘 [README.md](README.md) - ภาพรวมโปรเจค
- 🚀 [DEPLOYMENT.md](DEPLOYMENT.md) - คู่มือ Deploy และ Update
- 💻 [DEVELOPMENT.md](DEVELOPMENT.md) - คู่มือนักพัฒนา
- 🔐 [GITHUB_TOKEN_SETUP.md](GITHUB_TOKEN_SETUP.md) - ตั้งค่า GitHub Token
- 🌍 [MULTI-LANGUAGE.md](MULTI-LANGUAGE.md) - ระบบหลายภาษา
- 👑 [SUPER-ADMIN.md](SUPER-ADMIN.md) - คู่มือ Super Admin

### ปัญหาและข้อเสนอแนะ

- **GitHub Issues**: https://github.com/xjanova/Thaiprompt-Affiliate/issues
- **Email**: support@thaiprompt.com
- **Website**: https://thaiprompt.com

### ตรวจสอบ Logs

```bash
# Laravel logs
tail -f storage/logs/laravel.log

# Deployment logs
tail -f storage/logs/deployment.log

# Nginx logs
sudo tail -f /var/log/nginx/error.log
sudo tail -f /var/log/nginx/access.log

# Apache logs
sudo tail -f /var/log/apache2/error.log
sudo tail -f /var/log/apache2/access.log

# PHP-FPM logs
sudo tail -f /var/log/php8.2-fpm.log
```

---

## 🎓 Flow การติดตั้งแบบสรุป

```
┌─────────────────────────────────────────┐
│  1. เตรียมความพร้อม                     │
│     ✓ ติดตั้ง PHP 8.1+                 │
│     ✓ ติดตั้ง Extensions               │
│     ✓ ติดตั้ง Composer                 │
│     ✓ ติดตั้ง MySQL                    │
│     ✓ สร้าง Database                   │
└──────────────┬──────────────────────────┘
               ↓
┌─────────────────────────────────────────┐
│  2. Clone Repository                    │
│     git clone repo                      │
│     cd Thaiprompt-Affiliate             │
└──────────────┬──────────────────────────┘
               ↓
┌─────────────────────────────────────────┐
│  3. รัน install.sh                      │
│     chmod +x install.sh                 │
│     ./install.sh                        │
│                                         │
│     ระบบจะถาม:                          │
│     - App Name, URL, Environment        │
│     - Database Credentials              │
│     - Super Admin Account               │
│                                         │
│     ทำอัตโนมัติ:                         │
│     ✓ สร้าง .env                        │
│     ✓ ติดตั้ง dependencies             │
│     ✓ Migrate database                  │
│     ✓ Seed data                         │
│     ✓ สร้าง Super Admin                │
│     ✓ Optimize cache                    │
│     ✓ Verify installation               │
└──────────────┬──────────────────────────┘
               ↓
┌─────────────────────────────────────────┐
│  4. ทดสอบด้วย PHP Server                │
│     php artisan serve                   │
│     เปิด http://localhost:8000          │
└──────────────┬──────────────────────────┘
               ↓
┌─────────────────────────────────────────┐
│  5. ตั้งค่า Web Server                  │
│     ✓ Nginx หรือ Apache                │
│     ✓ SSL Certificate (Let's Encrypt)   │
│     ✓ File Permissions                  │
└──────────────┬──────────────────────────┘
               ↓
┌─────────────────────────────────────────┐
│  6. ตั้งค่า Services (Optional)         │
│     ✓ Email (Gmail/SMTP)                │
│     ✓ Cloudflare Turnstile              │
│     ✓ Google Translate API              │
│     ✓ Redis Cache                       │
│     ✓ Queue Worker                      │
└──────────────┬──────────────────────────┘
               ↓
┌─────────────────────────────────────────┐
│  7. Security & Optimization             │
│     ✓ Firewall (UFW)                    │
│     ✓ Fail2Ban                          │
│     ✓ OPcache                           │
│     ✓ Security Headers                  │
└──────────────┬──────────────────────────┘
               ↓
┌─────────────────────────────────────────┐
│  8. Backup & Monitoring                 │
│     ✓ ตั้งค่า Auto Backup               │
│     ✓ ติดตั้ง Monitoring Tools          │
│     ✓ ตรวจสอบ Logs                      │
└──────────────┬──────────────────────────┘
               ↓
┌─────────────────────────────────────────┐
│  ✅ เสร็จสมบูรณ์!                        │
│     🎉 ระบบพร้อมใช้งาน                  │
│     🚀 Deploy updates: ./deploy.sh      │
└─────────────────────────────────────────┘
```

---

## ✅ Checklist การติดตั้ง

### Pre-Installation
- [ ] ติดตั้ง PHP 8.1+ พร้อม Extensions ครบ
- [ ] ติดตั้ง Composer 2.0+
- [ ] ติดตั้ง MySQL/MariaDB
- [ ] สร้าง Database และ User
- [ ] ติดตั้ง Git
- [ ] เตรียม Domain/Subdomain

### Installation
- [ ] Clone repository
- [ ] รัน `./install.sh`
- [ ] กรอกข้อมูล Application
- [ ] กรอกข้อมูล Database
- [ ] สร้าง Super Admin account
- [ ] ตรวจสอบ Post-Installation Verification

### Post-Installation
- [ ] ทดสอบด้วย `php artisan serve`
- [ ] Login Admin Panel สำเร็จ
- [ ] ตั้งค่า Web Server (Nginx/Apache)
- [ ] ติดตั้ง SSL Certificate
- [ ] ตั้งค่า File Permissions
- [ ] ทดสอบเว็บผ่าน domain จริง

### Configuration
- [ ] ตั้งค่า Email (Gmail/SMTP)
- [ ] ทดสอบส่ง email
- [ ] ตั้งค่า Cloudflare Turnstile (Optional)
- [ ] ตั้งค่า Google Translate (Optional)
- [ ] ตั้งค่า `.env` production values

### Optimization (Optional)
- [ ] ติดตั้ง Redis
- [ ] Enable OPcache
- [ ] ตั้งค่า Queue Worker
- [ ] Optimize Database

### Security
- [ ] ตั้ง `APP_DEBUG=false`
- [ ] ตั้ง `APP_ENV=production`
- [ ] ตั้งค่า Firewall (UFW)
- [ ] ติดตั้ง Fail2Ban
- [ ] ตั้งค่า Security Headers
- [ ] ตรวจสอบ File Permissions

### Backup & Monitoring
- [ ] ตั้งค่า Auto Backup (Database)
- [ ] ตั้งค่า Auto Backup (Files)
- [ ] ทดสอบ Restore
- [ ] ตั้งค่า Monitoring Tools
- [ ] ตั้งค่า Log Rotation

### Final Checks
- [ ] ทดสอบ Frontend
- [ ] ทดสอบ Admin Panel
- [ ] ทดสอบ User Registration
- [ ] ทดสอบ Affiliate System
- [ ] ทดสอบ Email Notifications
- [ ] ตรวจสอบ Mobile Responsive
- [ ] ตรวจสอบ Performance

---

## 🎊 สรุป

คู่มือนี้ครอบคลุมการติดตั้ง TP-Affiliate แบบครบวงจร ตั้งแต่:

1. ✅ การเตรียมความพร้อมของระบบ
2. ✅ การติดตั้งด้วย `install.sh` (พร้อมใช้งาน 100%)
3. ✅ การตั้งค่า Web Server (Nginx/Apache)
4. ✅ การตั้งค่า Email และ Services
5. ✅ การแก้ไขปัญหาที่อาจพบ
6. ✅ Performance Optimization
7. ✅ Security Hardening
8. ✅ Backup และ Maintenance

**ระบบ install.sh มีความพร้อม 10/10** พร้อมใช้งานจริงได้ทันที!

---

<div align="center">

**Made with ❤️ by ThaiPrompt Team**

[🏠 เว็บไซต์](https://thaiprompt.com) •
[📖 Documentation](README.md) •
[💬 Support](https://github.com/xjanova/Thaiprompt-Affiliate/issues)

**Version 2.171.0** | Last Updated: January 13, 2025

</div>
