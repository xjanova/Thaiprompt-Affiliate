# 🚀 TP-Affiliate Installation Guide

> **คู่มือติดตั้ง TP-Affiliate แบบ Step-by-Step**
>
> Version: 3.3.0 | Last Updated: 2025-11-30

---

## 📋 สารบัญ

1. [ความต้องการของระบบ](#-ความต้องการของระบบ)
2. [วิธีติดตั้งแบบง่าย (แนะนำ)](#-วิธีติดตั้งแบบง่าย-แนะนำ)
3. [ขั้นตอนการติดตั้งโดยละเอียด](#-ขั้นตอนการติดตั้งโดยละเอียด)
4. [ตัวเลือกการติดตั้ง](#-ตัวเลือกการติดตั้ง)
5. [หลังติดตั้งเสร็จ](#-หลังติดตั้งเสร็จ)
6. [การแก้ปัญหา](#-การแก้ปัญหา)

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
