# 🚀 TP-Affiliate Installation Guide

> **คู่มือติดตั้ง TP-Affiliate แบบ Step-by-Step**
>
> Version: 3.3.1 | Last Updated: 2025-12-03

---

## 📋 สารบัญ

1. [ความต้องการของระบบ](#-ความต้องการของระบบ)
2. [วิธีติดตั้งแบบง่าย (แนะนำ)](#-วิธีติดตั้งแบบง่าย-แนะนำ)
3. [ขั้นตอนการติดตั้งโดยละเอียด](#-ขั้นตอนการติดตั้งโดยละเอียด)
4. [ตัวเลือกการติดตั้ง](#-ตัวเลือกการติดตั้ง)
5. [หลังติดตั้งเสร็จ](#-หลังติดตั้งเสร็จ)
6. [การแก้ปัญหา](#-การแก้ปัญหา)
7. [การอัพเดท](#-การอัพเดท)
8. [**ตั้งค่า deploy.sh ให้ Deploy สำเร็จ 100%**](#-ตั้งค่า-deploysh-ให้-deploy-สำเร็จ-100) ⭐ NEW

---

## 💪 ความต้องการของระบบ

### Minimum Requirements

| Component | Version |
|-----------|---------|
| PHP | 8.1+ |
| MySQL | 5.7+ / MariaDB 10.3+ |
| Composer | 2.0+ |
| Git | 2.0+ |
| Node.js | 16+ (optional) |

### PHP Extensions Required

```
bcmath, ctype, curl, dom, fileinfo, json,
mbstring, openssl, pdo, pdo_mysql, tokenizer, xml, gd, zip
```

### ตรวจสอบ PHP Extensions

```bash
php -m | grep -E "bcmath|ctype|curl|dom|fileinfo|json|mbstring|openssl|pdo|tokenizer|xml|gd|zip"
```

---

## ⚡ วิธีติดตั้งแบบง่าย (แนะนำ)

### One-Line Installation

```bash
# วิธีที่ 1: ใช้ curl
curl -fsSL https://raw.githubusercontent.com/xjanova/Thaiprompt-Affiliate/claude/Main/install.sh -o install.sh && bash install.sh

# วิธีที่ 2: ใช้ wget
wget -qO install.sh https://raw.githubusercontent.com/xjanova/Thaiprompt-Affiliate/claude/Main/install.sh && bash install.sh
```

### หรือ Clone แล้วรัน

```bash
# สร้างโฟลเดอร์ว่าง
mkdir my-affiliate-site
cd my-affiliate-site

# ดาวน์โหลด install.sh
curl -fsSL https://raw.githubusercontent.com/xjanova/Thaiprompt-Affiliate/claude/Main/install.sh -o install.sh

# รัน installer
bash install.sh
```

---

## 📝 ขั้นตอนการติดตั้งโดยละเอียด

เมื่อรัน `install.sh` ในโฟลเดอร์ว่าง ระบบจะถามทีละขั้นตอน:

### ขั้นตอนที่ 1: เลือกตำแหน่งติดตั้ง

```
╔════════════════════════════════════════════════════════════════╗
║    🎯 ยินดีต้อนรับสู่ TP-Affiliate Installer                  ║
╚════════════════════════════════════════════════════════════════╝

⚠️  ตรวจพบว่าโฟลเดอร์นี้ว่างเปล่า

เราจะช่วยคุณติดตั้ง TP-Affiliate ตั้งแต่ต้น

📁 ขั้นตอนที่ 1: เลือกตำแหน่งติดตั้ง

  1) ติดตั้งในโฟลเดอร์ปัจจุบัน (/path/to/current)
  2) สร้างโฟลเดอร์ใหม่

เลือกตัวเลือก [1]: _
```

**ตัวเลือก:**
- **1** = ติดตั้งในโฟลเดอร์ที่อยู่ตอนนี้
- **2** = สร้างโฟลเดอร์ใหม่ (จะถามชื่อโฟลเดอร์)

---

### ขั้นตอนที่ 2: เลือกโหมดการติดตั้ง

```
⚙️  ขั้นตอนที่ 2: เลือกโหมดการติดตั้ง

  1) 🧙 Wizard Mode (แนะนำ) - ถามทีละขั้นตอน ง่ายสำหรับผู้เริ่มต้น
  2) ⚡ Auto Mode - ติดตั้งอัตโนมัติด้วยค่าเริ่มต้น
  3) 📥 Clone Only - ดาวน์โหลดโค้ดอย่างเดียว ตั้งค่าเองทีหลัง
  0) ❌ ยกเลิก

เลือกตัวเลือก [1]: _
```

**คำอธิบายแต่ละโหมด:**

| โหมด | คำอธิบาย | เหมาะสำหรับ |
|------|----------|-------------|
| 🧙 **Wizard Mode** | ถามทีละขั้นตอน มี progress bar | ผู้เริ่มต้น, ต้องการควบคุมทุกขั้นตอน |
| ⚡ **Auto Mode** | ใช้ค่าเริ่มต้นทั้งหมด | CI/CD, ติดตั้งซ้ำ, ผู้เชี่ยวชาญ |
| 📥 **Clone Only** | ดาวน์โหลดโค้ดเท่านั้น | ต้องการตั้งค่าเอง |

---

### ขั้นตอนที่ 3: Wizard Mode (ถ้าเลือก)

เมื่อเลือก **Wizard Mode** จะมี 5 ขั้นตอนย่อย:

#### 3.1 ยินดีต้อนรับ

```
╔════════════════════════════════════════════════════════════════╗
║    🧙 TP-Affiliate Installation Wizard                       ║
╚════════════════════════════════════════════════════════════════╝

┌────────────────────────────────────────────────────────────────┐
│ ◉────○────○────○────○ │
│ ยินดีต้อนรับ  ตั้งค่า Database  ตั้งค่าแอป  สร้าง Admin  ยืนยัน │
└────────────────────────────────────────────────────────────────┘

Wizard จะช่วยคุณติดตั้งผ่าน 5 ขั้นตอนง่ายๆ:

  1. ยินดีต้อนรับ - แนะนำระบบ
  2. ตั้งค่า Database - เชื่อมต่อฐานข้อมูล MySQL
  3. ตั้งค่าแอป - ชื่อเว็บไซต์และ URL
  4. สร้าง Admin - บัญชีผู้ดูแลระบบ
  5. ยืนยัน - ตรวจสอบและเริ่มติดตั้ง

กด Enter เพื่อเริ่มต้น...
```

#### 3.2 ตั้งค่า Database

```
┌────────────────────────────────────────────────────────────────┐
│ ●────◉────○────○────○ │
│ ยินดีต้อนรับ  ตั้งค่า Database  ตั้งค่าแอป  สร้าง Admin  ยืนยัน │
└────────────────────────────────────────────────────────────────┘

📦 ขั้นตอนที่ 2: ตั้งค่า Database

คุณต้องมี MySQL/MariaDB database พร้อมใช้งาน
ถ้ายังไม่มี กรุณาสร้างก่อน:
  mysql -u root -p -e "CREATE DATABASE thaiprompt_affiliate;"

  ? Database Host [127.0.0.1]: _
  ? Database Port [3306]: _
  ? Database Name [thaiprompt_affiliate]: _
  ? Database Username [root]: _
  ? Database Password: _
```

**ข้อมูลที่ต้องเตรียม:**
- Host: ที่อยู่ MySQL server (ปกติคือ `127.0.0.1` หรือ `localhost`)
- Port: พอร์ต MySQL (ปกติคือ `3306`)
- Database Name: ชื่อฐานข้อมูลที่สร้างไว้
- Username: ชื่อผู้ใช้ MySQL
- Password: รหัสผ่าน MySQL

#### 3.3 ตั้งค่าแอป

```
┌────────────────────────────────────────────────────────────────┐
│ ●────●────◉────○────○ │
│ ยินดีต้อนรับ  ตั้งค่า Database  ตั้งค่าแอป  สร้าง Admin  ยืนยัน │
└────────────────────────────────────────────────────────────────┘

🌐 ขั้นตอนที่ 3: ตั้งค่าแอปพลิเคชัน

  ? ชื่อแอปพลิเคชัน [TP-Affiliate]: _
  ? URL ของเว็บไซต์ (เช่น https://example.com) [http://localhost]: _
```

#### 3.4 สร้าง Admin

```
┌────────────────────────────────────────────────────────────────┐
│ ●────●────●────◉────○ │
│ ยินดีต้อนรับ  ตั้งค่า Database  ตั้งค่าแอป  สร้าง Admin  ยืนยัน │
└────────────────────────────────────────────────────────────────┘

👤 ขั้นตอนที่ 4: สร้าง Admin Account

บัญชีนี้จะเป็น Super Admin ของระบบ

  ? ชื่อผู้ดูแลระบบ [Admin]: _
  ? Email ผู้ดูแลระบบ: _

🔐 ตั้งรหัสผ่าน Admin
  ? รหัสผ่าน (อย่างน้อย 8 ตัว): _
  ? ยืนยันรหัสผ่าน: _
```

**ข้อกำหนดรหัสผ่าน:**
- ต้องมีอย่างน้อย 8 ตัวอักษร
- ต้องยืนยันรหัสผ่านให้ตรงกัน

#### 3.5 ยืนยันการติดตั้ง

```
┌────────────────────────────────────────────────────────────────┐
│ ●────●────●────●────◉ │
│ ยินดีต้อนรับ  ตั้งค่า Database  ตั้งค่าแอป  สร้าง Admin  ยืนยัน │
└────────────────────────────────────────────────────────────────┘

✅ ขั้นตอนที่ 5: ยืนยันการติดตั้ง

┌────────────────────────────────────────────────────────────────┐
│ สรุปการตั้งค่า                                                │
├────────────────────────────────────────────────────────────────┤
│                                                                │
│  🌐 App Name:  TP-Affiliate                                    │
│  🔗 App URL:   https://example.com                             │
│                                                                │
│  🗄️  Database:  thaiprompt_affiliate@127.0.0.1:3306            │
│  👤 DB User:   root                                            │
│                                                                │
│  📧 Admin:     admin@example.com                               │
│                                                                │
└────────────────────────────────────────────────────────────────┘

ดำเนินการติดตั้งต่อ? (y/n) [y]: _
```

---

### ขั้นตอนที่ 4: การติดตั้งอัตโนมัติ

หลังจากยืนยัน ระบบจะทำงานต่อไปนี้อัตโนมัติ:

```
🚀 เริ่มการติดตั้ง...

📋 STEP 1: ตรวจสอบ System Requirements
  ✓ PHP 8.2.0 ✓
  ✓ BCMath Extension ✓
  ✓ cURL Extension ✓
  ... (ตรวจสอบ extensions อื่นๆ)

📥 STEP 2: ติดตั้ง Dependencies
  ▶ กำลังติดตั้ง Composer dependencies...
  ✓ Composer dependencies ติดตั้งเรียบร้อย

⚙️  STEP 3: ตั้งค่าระบบ
  ✓ สร้าง .env file
  ✓ Generate APP_KEY
  ✓ ตั้งค่า Database connection

🗄️  STEP 4: Database Migration
  ▶ กำลังรัน migrations...
  ✓ Migrations เสร็จสิ้น

🌱 STEP 5: Seed Data
  ▶ กำลังรัน DatabaseSeeder...
  ✓ Seeders เสร็จสิ้น

👤 STEP 6: สร้าง Super Admin
  ✓ สร้างบัญชี Super Admin เรียบร้อย

🔧 STEP 7: Optimization
  ✓ Cache configuration
  ✓ Cache routes
  ✓ Cache views
  ✓ Storage link created

╔════════════════════════════════════════════════════════════════╗
║                                                                ║
║    ✅ ติดตั้ง TP-Affiliate สำเร็จ!                            ║
║                                                                ║
║    🌐 เปิดเบราว์เซอร์ไปที่: http://localhost:8000             ║
║    👤 Admin Login: admin@example.com                           ║
║                                                                ║
║    📖 คำสั่งเริ่มต้น: php artisan serve                        ║
║                                                                ║
╚════════════════════════════════════════════════════════════════╝
```

---

## 🎛️ ตัวเลือกการติดตั้ง

### Command Line Options

```bash
# แสดง help
./install.sh --help

# Wizard Mode (แนะนำ)
./install.sh --wizard

# Auto Mode (ใช้ค่าเริ่มต้น)
./install.sh --auto

# เลือกโหมดการติดตั้ง
./install.sh --mode=minimal     # Core settings เท่านั้น
./install.sh --mode=standard    # แนะนำ (มี demo data)
./install.sh --mode=full        # ครบทุกอย่าง

# Clone และติดตั้ง
./install.sh --clone --wizard

# บังคับติดตั้งใหม่
./install.sh --force
```

### Installation Modes

| Mode | รายละเอียด |
|------|------------|
| `minimal` | Core settings เท่านั้น ไม่มี demo data (เหมาะสำหรับ production) |
| `standard` | Core + demo users + essential data (แนะนำ) |
| `full` | ทุกอย่าง รวม demo products, test orders |

---

## ✅ หลังติดตั้งเสร็จ

### 1. ทดสอบด้วย Built-in Server

```bash
php artisan serve
```

เปิดเบราว์เซอร์: http://localhost:8000

### 2. Login Admin Dashboard

```
URL: http://localhost:8000/admin
Email: [อีเมลที่กรอกตอนติดตั้ง]
Password: [รหัสผ่านที่กรอกตอนติดตั้ง]
```

### 3. ตั้งค่าระบบเบื้องต้น

1. ไปที่ **Settings** → **General Settings**
2. ตั้งค่า Site Name, Logo, Favicon
3. ตั้งค่า Email SMTP
4. ตั้งค่า Payment Gateway

### 4. Production Deployment

#### Nginx Configuration

```nginx
server {
    listen 80;
    server_name example.com;
    root /path/to/thaiprompt-affiliate/public;

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

---

## ❓ การแก้ปัญหา

### ปัญหา: Composer not found

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### ปัญหา: PHP version ไม่ตรง

```bash
# Ubuntu/Debian
sudo apt update
sudo apt install php8.1 php8.1-{cli,fpm,mysql,xml,mbstring,curl,zip,gd,bcmath}

# CentOS/RHEL
sudo yum install php81 php81-{cli,fpm,mysqlnd,xml,mbstring,curl,zip,gd,bcmath}
```

### ปัญหา: Database connection failed

```bash
# ตรวจสอบ MySQL status
sudo systemctl status mysql

# สร้างฐานข้อมูล
mysql -u root -p -e "CREATE DATABASE thaiprompt_affiliate CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# ให้สิทธิ์
mysql -u root -p -e "GRANT ALL PRIVILEGES ON thaiprompt_affiliate.* TO 'username'@'localhost';"
```

### ปัญหา: Permission denied

```bash
chmod -R 775 storage bootstrap/cache
sudo chown -R $USER:www-data storage bootstrap/cache
```

### ปัญหา: รหัสผ่านไม่ตรงกัน loop

ถ้าเจอปัญหานี้ ให้อัพเดท install.sh เป็นเวอร์ชันล่าสุด:

```bash
curl -fsSL https://raw.githubusercontent.com/xjanova/Thaiprompt-Affiliate/claude/Main/install.sh -o install.sh
```

---

## 🔄 การอัพเดท

```bash
# ใช้ deploy script
./deploy.sh

# หรืออัพเดทด้วยมือ
git pull origin claude/Main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize
```

---

## 🚀 ตั้งค่า deploy.sh ให้ Deploy สำเร็จ 100%

> **คู่มือการตั้งค่า deploy.sh อย่างละเอียด เพื่อให้ Deploy สำเร็จทุกครั้ง**

### ✅ Checklist ก่อนรัน deploy.sh

**สิ่งที่ต้องมีก่อนรัน:**

| # | รายการ | คำสั่งตรวจสอบ | ต้องได้ผลลัพธ์ |
|---|--------|--------------|----------------|
| 1 | Git repository | `ls -la .git` | พบโฟลเดอร์ .git |
| 2 | .env file | `ls -la .env` | พบไฟล์ .env |
| 3 | Database connection | `php artisan db:show` | แสดงข้อมูล DB |
| 4 | Composer | `composer --version` | แสดง version |
| 5 | PHP 8.1+ | `php --version` | PHP 8.1.x ขึ้นไป |
| 6 | mysqldump (optional) | `which mysqldump` | พบ path |

### 📝 ขั้นตอนที่ 1: ตรวจสอบและเตรียม .env

```bash
# 1. ตรวจสอบว่ามี .env
ls -la .env

# 2. ถ้าไม่มี ให้สร้างจาก .env.example
cp .env.example .env

# 3. แก้ไขค่าสำคัญ
nano .env
```

**ค่าที่ต้องตั้งใน .env (บังคับ):**

```env
# === Application ===
APP_NAME="TP-Affiliate"
APP_ENV=production          # ⚠️ ต้องเป็น production สำหรับ live server
APP_KEY=                    # จะถูก generate อัตโนมัติ
APP_DEBUG=false             # ⚠️ ต้องเป็น false ใน production
APP_URL=https://yourdomain.com

# === Database (บังคับ) ===
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=thaiprompt_affiliate
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 📝 ขั้นตอนที่ 2: ตั้งค่า Database

```bash
# 1. เข้า MySQL
mysql -u root -p

# 2. สร้าง Database
CREATE DATABASE thaiprompt_affiliate CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# 3. สร้าง User และให้สิทธิ์ (แนะนำ)
CREATE USER 'tpaffiliate'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON thaiprompt_affiliate.* TO 'tpaffiliate'@'localhost';
FLUSH PRIVILEGES;
exit;

# 4. ทดสอบ connection
php artisan db:show
```

### 📝 ขั้นตอนที่ 3: ตั้งค่า Permissions

```bash
# ให้สิทธิ์เขียนโฟลเดอร์สำคัญ
chmod -R 775 storage bootstrap/cache

# ตั้ง owner (Linux - Apache)
sudo chown -R $USER:www-data storage bootstrap/cache

# หรือ (Linux - Nginx)
sudo chown -R $USER:nginx storage bootstrap/cache

# สร้างโฟลเดอร์ที่จำเป็น (ถ้าไม่มี)
mkdir -p storage/{app,framework,logs}
mkdir -p storage/framework/{cache,sessions,views}
mkdir -p bootstrap/cache
```

### 📝 ขั้นตอนที่ 4: ตั้งค่า GitHub Token (แนะนำ)

> **ทำไมต้องใช้?** เพิ่ม rate limit จาก 60 → 5,000 requests/hour ป้องกัน error ขณะ deploy

```bash
# 1. สร้าง GitHub Personal Access Token
#    ไปที่: https://github.com/settings/tokens
#    กด "Generate new token (classic)"
#    เลือก: repo (read access)

# 2. ตั้งค่า environment variable (ชั่วคราว)
export GITHUB_TOKEN=ghp_your_token_here

# 3. หรือเพิ่มใน .bashrc/.zshrc (ถาวร)
echo 'export GITHUB_TOKEN=ghp_your_token_here' >> ~/.bashrc
source ~/.bashrc
```

### 📝 ขั้นตอนที่ 5: ตั้งค่า Cloudflare (ถ้าใช้)

> **สำหรับเว็บที่ใช้ Cloudflare CDN** - deploy.sh จะ purge cache ให้อัตโนมัติ

```env
# เพิ่มใน .env
CLOUDFLARE_ZONE_ID=your_zone_id_here
CLOUDFLARE_API_TOKEN=your_api_token_here
```

**วิธีหา Zone ID และ API Token:**
1. เข้า Cloudflare Dashboard
2. เลือก Domain ของคุณ
3. Zone ID อยู่ที่ sidebar ขวามือ
4. API Token: ไปที่ My Profile → API Tokens → Create Token

### 📝 ขั้นตอนที่ 6: ทดสอบก่อน Deploy จริง

```bash
# 1. ทดสอบ database connection
php artisan db:show

# 2. ทดสอบ artisan commands
php artisan --version

# 3. ทดสอบ composer
composer diagnose

# 4. ทดสอบ git connection
git fetch origin --dry-run

# 5. ทดสอบ permissions
touch storage/logs/test.log && rm storage/logs/test.log && echo "✓ Permissions OK"
```

### 🎯 รัน deploy.sh

```bash
# วิธีที่ 1: Deploy จาก branch ปัจจุบัน
./deploy.sh

# วิธีที่ 2: Deploy จาก branch ที่ระบุ
./deploy.sh claude/Main

# วิธีที่ 3: Deploy พร้อม GitHub token (ถ้าไม่ได้ตั้งถาวร)
GITHUB_TOKEN=ghp_xxx ./deploy.sh
```

### 🔧 การตั้งค่าเพิ่มเติมสำหรับ Production

#### ตั้งค่า PHP-FPM ให้ restart ได้โดยไม่ต้องใส่ password

```bash
# 1. แก้ไข sudoers
sudo visudo

# 2. เพิ่มบรรทัดนี้ (แทน your_username ด้วย username จริง)
your_username ALL=(ALL) NOPASSWD: /usr/bin/systemctl reload php8.2-fpm
your_username ALL=(ALL) NOPASSWD: /usr/bin/systemctl reload php8.3-fpm
your_username ALL=(ALL) NOPASSWD: /usr/sbin/service php8.2-fpm reload

# 3. บันทึกและออก (Ctrl+X, Y, Enter)
```

#### ตั้งค่า Cron สำหรับ Laravel Scheduler

```bash
# เปิด crontab
crontab -e

# เพิ่มบรรทัดนี้
* * * * * cd /path/to/your/project && php artisan schedule:run >> /dev/null 2>&1
```

#### ตั้งค่า Supervisor สำหรับ Queue Worker

```bash
# 1. ติดตั้ง supervisor
sudo apt install supervisor

# 2. สร้าง config file
sudo nano /etc/supervisor/conf.d/tpaffiliate-worker.conf
```

```ini
[program:tpaffiliate-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/project/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/your/project/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
# 3. อัพเดท supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start tpaffiliate-worker:*
```

### ❌ การแก้ปัญหาที่พบบ่อยขณะ Deploy

#### ปัญหา: "Database connection failed"

```bash
# ตรวจสอบ MySQL service
sudo systemctl status mysql

# ตรวจสอบ credentials ใน .env
grep "^DB_" .env

# ทดสอบ connection ด้วย mysql client
mysql -h 127.0.0.1 -u your_username -p your_database
```

#### ปัญหา: "Permission denied"

```bash
# Reset permissions ทั้งหมด
chmod -R 775 storage bootstrap/cache
sudo chown -R $USER:www-data storage bootstrap/cache

# ตรวจสอบ SELinux (CentOS/RHEL)
sudo setenforce 0  # ปิดชั่วคราว
# หรือ
sudo chcon -R -t httpd_sys_rw_content_t storage/
```

#### ปัญหา: "Composer install failed - timeout"

```bash
# เพิ่ม timeout
COMPOSER_PROCESS_TIMEOUT=600 composer install

# ใช้ GitHub token
export GITHUB_TOKEN=ghp_your_token
./deploy.sh
```

#### ปัญหา: "OPcache not cleared"

```bash
# Restart PHP-FPM ด้วยมือ
sudo systemctl reload php8.2-fpm

# หรือ restart apache
sudo systemctl restart apache2
```

#### ปัญหา: "Migration failed - table already exists"

```bash
# ดูสถานะ migrations
php artisan migrate:status

# รัน smart migration
php artisan migrate:smart --force

# หรือ sync schema
php artisan schema:verify --auto-fix --force
```

### 📊 สิ่งที่ deploy.sh ทำโดยอัตโนมัติ

| ขั้นตอน | รายละเอียด | Auto-retry |
|--------|------------|------------|
| 1 | เปิด Maintenance Mode | ❌ |
| 2 | Backup Database | ❌ |
| 3 | Backup Critical Files (.env, uploads) | ❌ |
| 4 | Git Force Sync กับ GitHub | ✅ 3 ครั้ง |
| 5 | Sync .env กับ .env.example | ❌ |
| 6 | Composer Install | ✅ 3 ครั้ง |
| 7 | Sanctum Install | ❌ |
| 8 | Clear All Caches | ❌ |
| 9 | Smart Database Migration | ❌ |
| 10 | Smart Database Seeding | ❌ |
| 11 | Create Storage Symlink | ❌ |
| 12 | Set File Permissions | ❌ |
| 13-16 | Cache Config/Routes/Views/Autoloader | ❌ |
| 17 | Restart PHP-FPM & Services | ❌ |
| 18-19 | Verify & Disable Maintenance | ❌ |
| 20 | Cloudflare Cache Purge | ❌ |
| 21-22 | Post-Deploy Verification | ❌ |

### ✅ Checklist หลัง Deploy สำเร็จ

```bash
# 1. ตรวจสอบ HTTP response
curl -I https://yourdomain.com

# 2. ตรวจสอบ logs
tail -f storage/logs/laravel.log

# 3. ตรวจสอบ deployment logs
tail -f storage/logs/deployment.log

# 4. ตรวจสอบ queue workers
php artisan queue:monitor

# 5. ตรวจสอบ database migrations
php artisan migrate:status
```

---

## 📚 เอกสารเพิ่มเติม

- [README.md](README.md) - ภาพรวมโปรเจค
- [CLAUDE.md](CLAUDE.md) - Guidelines สำหรับนักพัฒนา
- [ARCHITECTURE.md](ARCHITECTURE.md) - โครงสร้างระบบ

---

## 🆘 Support

- 📖 Documentation: [README.md](README.md)
- 🐛 Report Issues: [GitHub Issues](https://github.com/xjanova/Thaiprompt-Affiliate/issues)

---

**🎉 ขอให้ติดตั้งสำเร็จและใช้งานได้อย่างราบรื่น!**
