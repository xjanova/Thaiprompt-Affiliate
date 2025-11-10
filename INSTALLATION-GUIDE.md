# 📦 Installation Guide - TP-Affiliate

## คู่มือการติดตั้งแบบละเอียด Step-by-Step

คู่มือนี้จะพาคุณติดตั้ง TP-Affiliate ตั้งแต่เริ่มต้นจนระบบรันได้จริง 🚀

---

## 📋 Table of Contents

1. [ความต้องการของระบบ](#ความต้องการของระบบ)
2. [วิธีการติดตั้ง](#วิธีการติดตั้ง)
   - [วิธีที่ 1: Web-based Installation Wizard (แนะนำ)](#วิธีที่-1-web-based-installation-wizard-แนะนำ)
   - [วิธีที่ 2: Command Line Installation](#วิธีที่-2-command-line-installation)
3. [การอัพเดทระบบ](#การอัพเดทระบบ)
4. [Troubleshooting](#troubleshooting)
5. [FAQ](#faq)

---

## ความต้องการของระบบ

### ✅ ต้องมีติดตั้งก่อน

| Software | Version | วิธีเช็ค | วิธีติดตั้ง |
|----------|---------|----------|-------------|
| PHP | 8.1+ | `php --version` | [ดูด้านล่าง](#ติดตั้ง-php) |
| Composer | Latest | `composer --version` | [ดูด้านล่าง](#ติดตั้ง-composer) |
| Git | Any | `git --version` | [ดูด้านล่าง](#ติดตั้ง-git) |

### 📦 PHP Extensions ที่จำเป็น

```bash
# เช็ค extensions ที่ติดตั้งแล้ว
php -m

# Extensions ที่ต้องมี:
- BCMath
- Ctype
- JSON
- Mbstring
- OpenSSL
- PDO
- PDO_SQLite (สำหรับ SQLite)
- PDO_MySQL (สำหรับ MySQL)
- Tokenizer
- XML
- cURL
- fileinfo
```

---

## การเตรียมความพร้อม

### ติดตั้ง PHP

#### macOS
```bash
# ใช้ Homebrew
brew install php@8.2
brew link php@8.2

# เช็คว่าติดตั้งสำเร็จ
php --version
```

#### Ubuntu/Debian
```bash
# เพิ่ม PPA repository
sudo add-apt-repository ppa:ondrej/php
sudo apt update

# ติดตั้ง PHP และ extensions
sudo apt install php8.2 \
    php8.2-cli \
    php8.2-common \
    php8.2-curl \
    php8.2-mbstring \
    php8.2-xml \
    php8.2-bcmath \
    php8.2-sqlite3 \
    php8.2-mysql

# เช็คว่าติดตั้งสำเร็จ
php --version
```

#### Windows
```bash
# 1. ดาวน์โหลด PHP จาก: https://windows.php.net/download/
# 2. แตกไฟล์ไปที่ C:\php
# 3. เพิ่ม C:\php ใน PATH
# 4. คัดลอก php.ini-development เป็น php.ini
# 5. เปิด extensions ที่จำเป็นใน php.ini

# เช็คว่าติดตั้งสำเร็จ
php --version
```

### ติดตั้ง Composer

```bash
# ดาวน์โหลดและติดตั้ง
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# หรือใช้ installer script
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"
sudo mv composer.phar /usr/local/bin/composer

# เช็คว่าติดตั้งสำเร็จ
composer --version
```

### ติดตั้ง Git

#### macOS
```bash
brew install git
```

#### Ubuntu/Debian
```bash
sudo apt update
sudo apt install git
```

#### Windows
```bash
# ดาวน์โหลดจาก: https://git-scm.com/download/win
```

---

## วิธีการติดตั้ง

TP-Affiliate รองรับ 2 วิธีการติดตั้ง:

### วิธีที่ 1: Web-based Installation Wizard (แนะนำ) ⭐

**ข้อดี:**
- ✅ ติดตั้งง่าย ผ่าน Web Interface
- ✅ ตรวจสอบ requirements อัตโนมัติ
- ✅ แสดง progress และ error แบบละเอียด
- ✅ ไม่ต้องใช้ Command Line
- ✅ เหมาะสำหรับ Shared Hosting

**ขั้นตอน:**

1. **อัพโหลดไฟล์ไปยัง Server**
   - ดาวน์โหลด source code จาก [GitHub Releases](https://github.com/xjanova/Thaiprompt-Affiliate/releases)
   - แตก zip file
   - อัพโหลดทั้งหมดไปยัง web server (ผ่าน FTP/SFTP)

2. **ตั้งค่า Web Server**
   - ตั้ง Document Root ให้ชี้ไปที่ `public/` folder
   - ตรวจสอบ PHP version 8.1+

3. **เปิด Browser**
   - ไปที่ `https://yourdomain.com`
   - ระบบจะ redirect ไป `/setup` อัตโนมัติ

4. **ทำตาม Installation Wizard**

   **Step 1: ยินดีต้อนรับ**
   - อ่านข้อมูลและคลิก "เริ่มการติดตั้ง"

   **Step 2: ตรวจสอบความพร้อม**
   - ระบบจะตรวจสอบ:
     - PHP version และ extensions
     - File permissions
     - Composer availability
   - หากมีปัญหา ระบบจะแจ้งให้แก้ไข

   **Step 3: ตั้งค่า Database**
   - กรอกข้อมูล MySQL:
     - Host (เช่น 127.0.0.1)
     - Port (เช่น 3306)
     - Database Name
     - Username
     - Password
   - คลิก "ทดสอบการเชื่อมต่อ"
   - หากสำเร็จ คลิก "ดำเนินการต่อ"

   **Step 4: ติดตั้ง Dependencies**
   - คลิก "เริ่มติดตั้ง Dependencies"
   - รอประมาณ 2-3 นาที (ขึ้นอยู่กับความเร็วของ server)

   **Step 5: สร้าง Database Tables**
   - คลิก "เริ่มสร้าง Tables"
   - ระบบจะรัน migrations อัตโนมัติ

   **Step 6: สร้างบัญชี Super Admin**
   - กรอกข้อมูล:
     - ชื่อ-นามสกุล
     - อีเมล
     - รหัสผ่าน (อย่างน้อย 8 ตัวอักษร)
   - คลิก "สร้างบัญชี Super Admin"

5. **เสร็จสิ้น! 🎉**
   - คลิก "เข้าสู่ระบบ"
   - เข้าสู่ระบบด้วยบัญชีที่สร้างไว้

**หมายเหตุสำคัญ:**
- ✅ หลังติดตั้งเสร็จ ระบบจะไม่สามารถเข้า `/setup` ได้อีก (เพื่อความปลอดภัย)
- ✅ ข้อมูล database จะถูกเก็บใน `.env` อัตโนมัติ
- ✅ App Key จะถูกสร้างอัตโนมัติ

---

### วิธีที่ 2: Command Line Installation

**ข้อดี:**
- ✅ เหมาะสำหรับ VPS/Dedicated Server
- ✅ ควบคุมได้มากกว่า
- ✅ รวดเร็วกว่า

**ข้อเสีย:**
- ❌ ต้องใช้ Command Line
- ❌ ต้องมี SSH access

**ขั้นตอน:**

## Step 1: Clone Repository

### 1.1 สร้าง Personal Access Token

> ⚠️ **สำคัญ:** Repository นี้เป็น private คุณต้องมี token

1. เปิด browser ไปที่: https://github.com/settings/tokens
2. คลิก **"Generate new token"** → **"Generate new token (classic)"**
3. กรอกข้อมูล:
   - **Note:** `TP-Affiliate Installation`
   - **Expiration:** `90 days` (หรือตามต้องการ)
   - **Select scopes:** ✅ `repo` (Full control of private repositories)
4. คลิก **"Generate token"**
5. **คัดลอก token ทันที** (จะไม่สามารถดูอีกครั้ง)

### 1.2 Clone Repository

```bash
# เลือกที่ที่จะติดตั้ง (แนะนำ)
cd ~
# หรือ
cd /var/www

# Clone repository
git clone https://github.com/xjanova/Thaiprompt-Affiliate.git

# จะถาม username และ password
Username: your-github-username
Password: ghp_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx  # ใส่ token ที่คัดลอกมา
```

**ตัวอย่าง Output ที่ถูกต้อง:**
```
Cloning into 'Thaiprompt-Affiliate'...
remote: Enumerating objects: 150, done.
remote: Counting objects: 100% (150/150), done.
remote: Compressing objects: 100% (120/120), done.
remote: Total 150 (delta 30), reused 150 (delta 30), pack-reused 0
Receiving objects: 100% (150/150), 45.67 KiB | 1.52 MiB/s, done.
Resolving deltas: 100% (30/30), done.
```

### 1.3 เข้าสู่โฟลเดอร์โปรเจค

```bash
cd Thaiprompt-Affiliate

# ตรวจสอบไฟล์
ls -la
```

**ควรเห็นไฟล์เหล่านี้:**
```
.env.example
.gitignore
AUTHENTICATION.md
DEPLOYMENT.md
DEVELOPMENT.md
GETTING-STARTED.md
README.md
artisan
composer.json
deploy.sh
install.sh      ← ไฟล์ที่เราจะใช้
package.json
```

---

## Step 2: รัน Installation Script

### 2.1 ให้สิทธิ์ execute

```bash
# ตรวจสอบสิทธิ์
ls -l install.sh

# ถ้ายังไม่มีสิทธิ์ execute (-rwxr-xr-x) ให้รันคำสั่งนี้
chmod +x install.sh

# ตรวจสอบอีกครั้ง
ls -l install.sh
# ควรเห็น: -rwxr-xr-x ... install.sh
```

### 2.2 รัน Installation Script

```bash
./install.sh
```

### 2.3 ดู Output ตัวอย่าง

**Installation จะแสดง Output แบบนี้:**

```
╔══════════════════════════════════════════════════╗
║   🚀 TP-Affiliate Installation Wizard            ║
║   Thai Prompt Affiliate Marketing Platform      ║
╚══════════════════════════════════════════════════╝

กำลังตรวจสอบระบบ...

✓ PHP 8.2.12 detected
✓ Composer detected

════════════════════════════════════════
  🔧 Installing TP-Affiliate
════════════════════════════════════════

ℹ [1/6] Preparing directories...
✓ Directories prepared

ℹ [2/6] Installing Composer dependencies...
Loading composer repositories with package information
Installing dependencies from lock file (including require-dev)
Package operations: 120 installs, 0 updates, 0 removals
  - Installing doctrine/inflector (2.0.8): Extracting archive
  - Installing doctrine/lexer (3.0.0): Extracting archive
  - Installing symfony/polyfill-ctype (v1.28.0): Extracting archive
  ...
✓ Dependencies installed

ℹ [3/6] Setting up environment...
✓ Environment file created

ℹ [4/6] Generating application key...
Application key set successfully.
✓ Key generated

════════════════════════════════════════
  📊 Database Configuration
════════════════════════════════════════

เลือก Database ที่ต้องการใช้:
  1) SQLite (แนะนำสำหรับ development)
  2) MySQL (แนะนำสำหรับ production)

เลือก (1 หรือ 2) [1]: 2

ℹ กรุณากรอกข้อมูล MySQL:

  DB Host [127.0.0.1]: 127.0.0.1
  DB Port [3306]: 3306
  Database Name [thaiprompt_affiliate]: thaiprompt_affiliate
  DB Username [root]: your_username
  DB Password: ********

ℹ [5/6] Configuring database...
ℹ ทดสอบการเชื่อมต่อ MySQL...
✓ MySQL connection successful
ℹ สร้าง database 'thaiprompt_affiliate'...
✓ Database configured (MySQL)

ℹ [6/6] Running migrations...
   INFO  Preparing database.

  Creating migration table ...................... 26ms DONE

   INFO  Running migrations.

  2024_01_01_000001_create_users_table .......... 15ms DONE
  2024_01_01_000002_create_affiliates_table ..... 12ms DONE
  2024_01_01_000003_create_commissions_table .... 10ms DONE
  2024_01_01_000004_create_settings_table ....... 8ms DONE
  2024_01_01_000005_create_cache_table .......... 7ms DONE
  2024_01_01_000006_create_jobs_table ........... 9ms DONE

✓ Database migrated

════════════════════════════════════════
✓ Installation Complete! 🎉
════════════════════════════════════════

รันเซิร์ฟเวอร์ด้วย:

  php artisan serve

จากนั้นเปิด browser ไปที่:

  http://localhost:8000

ระบบจะพาคุณไปหน้า Setup Wizard เพื่อสร้าง Super Admin

📚 อ่านเอกสารเพิ่มเติมได้ที่: README.md

✓ Happy coding! 🚀
```

### 2.4 การเลือก Database

ในระหว่างการติดตั้ง script จะถามให้คุณเลือก database ที่ต้องการใช้:

#### ตัวเลือกที่ 1: SQLite (แนะนำสำหรับ Development)

```bash
เลือก (1 หรือ 2) [1]: 1
```

**ข้อดี:**
- ✅ ติดตั้งง่าย ไม่ต้องตั้งค่าอะไรเพิ่ม
- ✅ ไม่ต้องมี MySQL Server
- ✅ เหมาะสำหรับ development และ testing

**ข้อเสีย:**
- ⚠️ ไม่เหมาะสำหรับ production ที่มี traffic สูง
- ⚠️ ไม่รองรับ concurrent writes มากนัก

#### ตัวเลือกที่ 2: MySQL (แนะนำสำหรับ Production)

```bash
เลือก (1 หรือ 2) [1]: 2
```

**ข้อมูลที่ต้องเตรียม:**

| ข้อมูล | คำอธิบาย | ค่า Default | ตัวอย่าง |
|--------|----------|-------------|----------|
| **DB Host** | IP หรือ hostname ของ MySQL server | `127.0.0.1` | `127.0.0.1` หรือ `db.example.com` |
| **DB Port** | Port ของ MySQL | `3306` | `3306` |
| **Database Name** | ชื่อ database | `thaiprompt_affiliate` | `thaiprompt_affiliate` |
| **DB Username** | Username สำหรับเข้าถึง database | `root` | `tpadmin` |
| **DB Password** | Password (จะไม่แสดงตอนพิมพ์) | ไม่มี | `your_secure_password` |

**วิธีเตรียมข้อมูล MySQL:**

1. **เข้า MySQL:**
   ```bash
   mysql -u root -p
   ```

2. **สร้าง database และ user (optional):**
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

3. **กรอกข้อมูลในระหว่างติดตั้ง:**
   ```bash
   DB Host [127.0.0.1]: 127.0.0.1          # หรือ IP ของ MySQL server
   DB Port [3306]: 3306
   Database Name [thaiprompt_affiliate]: thaiprompt_affiliate
   DB Username [root]: tpadmin              # ใช้ user ที่สร้างใหม่
   DB Password: your_secure_password        # จะไม่แสดงตอนพิมพ์
   ```

**หมายเหตุ:**
- ✅ Script จะทดสอบการเชื่อมต่อ MySQL อัตโนมัติ
- ✅ Script จะสร้าง database ให้อัตโนมัติถ้ายังไม่มี
- ⚠️ ถ้าใช้ remote MySQL (ไม่ใช่ localhost) ต้องแน่ใจว่า firewall อนุญาตการเชื่อมต่อ
- ⚠️ สำหรับ production ควรใช้ user เฉพาะแทนการใช้ root

---

### 2.5 เช็คว่าติดตั้งสำเร็จ

```bash
# เช็คว่าไฟล์สำคัญถูกสร้างแล้ว
ls -la .env                    # ควรมีไฟล์
ls -la database/database.sqlite # ควรมีไฟล์
ls -la vendor/                 # ควรมีโฟลเดอร์นี้

# เช็คว่า APP_KEY ถูกสร้างแล้ว
cat .env | grep APP_KEY
# ควรเห็น: APP_KEY=base64:xxxxxxxxxxxxx
```

---

## Step 3: ตรวจสอบการติดตั้ง

### 3.1 ทดสอบรันเซิร์ฟเวอร์

```bash
# รันเซิร์ฟเวอร์ development
php artisan serve
```

**Output ที่ถูกต้อง:**
```
   INFO  Server running on [http://127.0.0.1:8000].

  Press Ctrl+C to stop the server
```

### 3.2 ทดสอบเปิดเว็บไซต์

1. **เปิด browser**
2. **ไปที่:** `http://localhost:8000`
3. **ควรเห็น:** หน้า Setup Wizard หรือ Homepage

### 3.3 ทดสอบ Artisan Commands

เปิด Terminal ใหม่ (อย่าปิดตัวที่รัน serve):

```bash
# ไปที่โฟลเดอร์โปรเจค
cd ~/Thaiprompt-Affiliate

# ทดสอบ artisan commands
php artisan --version
# ควรเห็น: Laravel Framework 11.x.x

php artisan list
# ควรเห็นรายการ commands

php artisan route:list
# ควรเห็นรายการ routes
```

---

## Step 4: เข้าใช้งานครั้งแรก

### 4.1 Setup Wizard

1. **เปิด browser ไปที่:** `http://localhost:8000`

2. **ถ้ายังไม่มี Super Admin** ระบบจะ redirect ไป `/setup` อัตโนมัติ

3. **กรอกข้อมูล Super Admin:**
   ```
   ชื่อ:           Admin (หรือชื่อของคุณ)
   อีเมล:          admin@example.com (เปลี่ยนเป็นอีเมลจริง)
   รหัสผ่าน:        อย่างน้อย 8 ตัวอักษร (เช่น Password123!)
   ยืนยันรหัสผ่าน:  [ซ้ำกับด้านบน]
   ```

4. **คลิก "สร้างบัญชี Super Admin"**

5. **เข้าสู่ระบบสำเร็จ!** จะถูกนำไปที่ Admin Dashboard

### 4.2 ทดสอบฟีเจอร์ต่างๆ

#### ทดสอบ Admin Dashboard
```
URL: http://localhost:8000/admin/dashboard

ควรเห็น:
- สถิติต่างๆ (ผู้ใช้, Affiliates, คอมมิชชั่น)
- กราฟรายได้
- รายการคอมมิชชั่นล่าสุด
- Top Affiliates
```

#### ทดสอบ User Management
```
URL: http://localhost:8000/admin/users

ควรเห็น:
- รายการผู้ใช้ (มี Super Admin ที่สร้างไว้)
- ปุ่มเพิ่มผู้ใช้ใหม่
```

#### ทดสอบ Frontend
```
URL: http://localhost:8000

ควรเห็น:
- หน้าแรกที่สวยงาม
- ข้อมูลสถิติ
- ปุ่มเข้าสู่ระบบ/สมัครสมาชิก
```

### 4.3 ทดสอบ Authentication

1. **Logout:** คลิกที่ชื่อผู้ใช้ → "ออกจากระบบ"
2. **Login อีกครั้ง:** ไปที่ `/login` และใส่ข้อมูลที่สร้างไว้
3. **ควรเข้าสู่ระบบได้สำเร็จ**

---

## Troubleshooting

### 🐛 ปัญหา: "composer: command not found"

**สาเหตุ:** ยังไม่ได้ติดตั้ง Composer

**วิธีแก้:**
```bash
# ติดตั้ง Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# ทดสอบอีกครั้ง
composer --version
```

---

### 🐛 ปัญหา: "PHP version ต้อง 8.1 หรือสูงกว่า"

**สาเหตุ:** PHP version ต่ำเกินไป

**วิธีเช็ค:**
```bash
php --version
```

**วิธีแก้ (Ubuntu):**
```bash
# เพิ่ม PPA repository
sudo add-apt-repository ppa:ondrej/php
sudo apt update

# ติดตั้ง PHP 8.2
sudo apt install php8.2 php8.2-cli php8.2-common

# ตั้งเป็น default
sudo update-alternatives --set php /usr/bin/php8.2

# เช็คอีกครั้ง
php --version
```

**วิธีแก้ (macOS):**
```bash
# ติดตั้ง PHP 8.2
brew install php@8.2
brew link php@8.2 --force --overwrite

# เช็คอีกครั้ง
php --version
```

---

### 🐛 ปัญหา: "Permission denied: ./install.sh"

**สาเหตุ:** ไฟล์ไม่มีสิทธิ์ execute

**วิธีแก้:**
```bash
chmod +x install.sh
./install.sh
```

---

### 🐛 ปัญหา: "Class 'PDO' not found"

**สาเหตุ:** PHP ไม่มี PDO extension

**วิธีแก้ (Ubuntu):**
```bash
# ติดตั้ง PDO SQLite
sudo apt install php8.2-sqlite3

# รีสตาร์ท PHP
sudo systemctl restart php8.2-fpm
```

**วิธีแก้ (macOS):**
```bash
# เช็คว่ามี extension
php -m | grep PDO
php -m | grep sqlite

# ถ้าไม่มี ให้ติดตั้ง PHP ใหม่
brew reinstall php@8.2
```

---

### 🐛 ปัญหา: "could not find driver"

**สาเหตุ:** ไม่มี SQLite หรือ MySQL driver

**วิธีแก้ (SQLite):**
```bash
# Ubuntu/Debian
sudo apt install php8.2-sqlite3

# macOS
brew install sqlite3

# ทดสอบ
php -m | grep -i sqlite
```

**วิธีแก้ (MySQL):**
```bash
# Ubuntu/Debian
sudo apt install php8.2-mysql

# macOS (included in PHP)
brew reinstall php@8.2

# ทดสอบ
php -m | grep -i mysql
```

---

### 🐛 ปัญหา: "ไม่สามารถเชื่อมต่อ MySQL ได้"

**สาเหตุ:** MySQL server ไม่รัน หรือ credentials ผิด

**ตัวอย่าง Error:**
```
⚠ ไม่สามารถเชื่อมต่อ MySQL ได้ (จะพยายาม migrate ต่อ)
ℹ กรุณาตรวจสอบว่า:
  - MySQL Server รันอยู่
  - Username/Password ถูกต้อง
  - Database 'thaiprompt_affiliate' ถูกสร้างแล้ว
```

**วิธีแก้:**

1. **เช็คว่า MySQL รันอยู่:**
   ```bash
   # Ubuntu/Debian
   sudo systemctl status mysql
   sudo systemctl start mysql

   # macOS
   brew services list
   brew services start mysql
   ```

2. **ทดสอบการเชื่อมต่อ:**
   ```bash
   mysql -h 127.0.0.1 -P 3306 -u your_username -p
   # ถ้าเข้าได้แสดงว่า credentials ถูกต้อง
   ```

3. **เช็คว่า database มีอยู่:**
   ```sql
   mysql -u root -p
   SHOW DATABASES;
   -- ถ้าไม่มี ให้สร้าง
   CREATE DATABASE thaiprompt_affiliate CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   EXIT;
   ```

4. **เช็คสิทธิ์ user:**
   ```sql
   mysql -u root -p
   SHOW GRANTS FOR 'your_username'@'localhost';
   -- ถ้าไม่มีสิทธิ์ ให้เพิ่ม
   GRANT ALL PRIVILEGES ON thaiprompt_affiliate.* TO 'your_username'@'localhost';
   FLUSH PRIVILEGES;
   EXIT;
   ```

5. **รัน install.sh ใหม่:**
   ```bash
   ./install.sh
   # เลือก MySQL และกรอกข้อมูลใหม่
   ```

---

### 🐛 ปัญหา: "Access denied for user"

**สาเหตุ:** Username หรือ Password ผิด

**ตัวอย่าง Error:**
```
ERROR 1045 (28000): Access denied for user 'tpadmin'@'localhost'
```

**วิธีแก้:**

1. **รีเซ็ต password:**
   ```bash
   mysql -u root -p
   ```
   ```sql
   ALTER USER 'tpadmin'@'localhost' IDENTIFIED BY 'new_password';
   FLUSH PRIVILEGES;
   EXIT;
   ```

2. **แก้ไข .env โดยตรง:**
   ```bash
   nano .env
   # แก้บรรทัดเหล่านี้
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=thaiprompt_affiliate
   DB_USERNAME=tpadmin
   DB_PASSWORD=correct_password
   ```

3. **ทดสอบ migration:**
   ```bash
   php artisan migrate
   ```

---

### 🐛 ปัญหา: "failed to open stream: Permission denied"

**สาเหตุ:** ไม่มีสิทธิ์เขียนไฟล์ใน storage/framework/views หรือ storage directory อื่นๆ

**ตัวอย่าง Error:**
```
file_put_contents(/path/to/storage/framework/views/xxx.php):
Failed to open stream: Permission denied

ErrorException in Filesystem.php (line 204)
```

**วิธีแก้ที่ง่ายที่สุด - ใช้สคริปต์อัตโนมัติ:**
```bash
# รันสคริปต์แก้ไข permissions อัตโนมัติ
./fix-permissions.sh
```

**วิธีแก้แบบ Manual:**

1. **แก้ไขทันที (Quick Fix):**
   ```bash
   # ตั้งสิทธิ์โฟลเดอร์และไฟล์
   chmod -R 775 storage bootstrap/cache
   find storage -type f -exec chmod 664 {} \;
   find bootstrap/cache -type f -exec chmod 664 {} \;
   ```

2. **ตั้งค่า Ownership (แนะนำ):**
   ```bash
   # เช็ค web server user
   ps aux | grep -E 'nginx|apache|php-fpm' | head -1

   # ตั้งค่า ownership ให้ถูกต้อง (แทน www-data ด้วย user ของ web server)
   sudo chown -R $USER:www-data storage bootstrap/cache
   sudo chmod -R 775 storage bootstrap/cache
   ```

3. **ใช้ ACL (Advanced - แนะนำสำหรับ Production):**
   ```bash
   # ตั้งค่า ACL ให้ web server user เขียนได้
   sudo setfacl -R -m u:www-data:rwX storage bootstrap/cache
   sudo setfacl -R -d -m u:www-data:rwX storage bootstrap/cache
   ```

4. **ล้าง Cache และทดสอบ:**
   ```bash
   php artisan cache:clear
   php artisan view:clear
   php artisan config:clear

   # รีสตาร์ท web server
   sudo systemctl restart php8.2-fpm
   sudo systemctl restart nginx
   ```

**ตรวจสอบ Web Server User:**
```bash
# สำหรับ Nginx + PHP-FPM
ps aux | grep php-fpm | head -1
# จะเห็น user เช่น www-data, nginx, admin

# สำหรับ Apache
ps aux | grep apache2 | head -1
# จะเห็น user เช่น www-data, apache

# ใช้ user ที่เจอในคำสั่งด้านบน
sudo chown -R $USER:[web-server-user] storage bootstrap/cache
```

---

### 🐛 ปัญหา: "The bootstrap/cache directory must be present and writable"

**สาเหตุ:** โฟลเดอร์ `bootstrap/cache` ไม่มีหรือไม่สามารถเขียนได้

**ตัวอย่าง Error:**
```
In PackageManifest.php line 179:
  The /path/to/bootstrap/cache directory must be present and writable.

Script @php artisan package:discover --ansi handling the
post-autoload-dump event returned with error code 1
```

**วิธีแก้:**
```bash
# สร้างโฟลเดอร์ที่จำเป็น
mkdir -p bootstrap/cache
mkdir -p storage/{app,framework,logs}
mkdir -p storage/framework/{cache,sessions,views}

# ตั้งสิทธิ์
chmod -R 775 storage bootstrap/cache

# ถ้ายังไม่ได้ ให้เปลี่ยน owner
sudo chown -R $USER:$USER bootstrap/cache storage

# รัน composer install อีกครั้ง
composer install --no-interaction
```

**หมายเหตุ:** ตั้งแต่เวอร์ชันใหม่ของ install.sh จะสร้างโฟลเดอร์เหล่านี้อัตโนมัติแล้ว

---

### 🐛 ปัญหา: "No application encryption key has been specified"

**สาเหตุ:** ไม่มี APP_KEY ใน .env

**วิธีแก้:**
```bash
# Generate key ใหม่
php artisan key:generate

# เช็คว่า .env มี APP_KEY แล้ว
cat .env | grep APP_KEY
```

---

### 🐛 ปัญหา: "Database file not found"

**สาเหตุ:** ไม่มีไฟล์ database

**วิธีแก้:**
```bash
# สร้างไฟล์ database
touch database/database.sqlite

# รัน migration อีกครั้ง
php artisan migrate
```

---

### 🐛 ปัญหา: "Port 8000 is already in use"

**สาเหตุ:** มี process อื่นใช้ port 8000 อยู่

**วิธีแก้:**
```bash
# วิธีที่ 1: ใช้ port อื่น
php artisan serve --port=8080

# วิธีที่ 2: หา process ที่ใช้ port 8000
lsof -ti:8000

# Kill process
lsof -ti:8000 | xargs kill -9
```

---

### 🐛 ปัญหา: "Authentication failed" ตอน git clone

**สาเหตุ:** Token หมดอายุหรือไม่ถูกต้อง

**วิธีแก้:**
```bash
# ลบ cached credentials
git credential-cache exit

# Clone อีกครั้งและใส่ token ใหม่
git clone https://github.com/xjanova/Thaiprompt-Affiliate.git
```

---

### 🐛 ปัญหา: Composer ช้ามาก

**สาเหตุ:** Network หรือ repository mirror ช้า

**วิธีแก้:**
```bash
# ใช้ Packagist mirror ที่เร็วกว่า
composer config -g repos.packagist composer https://packagist.org

# หรือยกเลิก timeout
composer install --no-interaction --timeout=0
```

---

## การอัพเดทระบบ

TP-Affiliate มีระบบอัพเดทที่สะดวกและปลอดภัย รองรับ 2 วิธี:

### วิธีที่ 1: อัพเดทผ่าน Admin Panel (แนะนำ) ⭐

**ข้อดี:**
- ✅ อัพเดทง่าย ผ่าน Web Interface
- ✅ ตรวจสอบ compatibility อัตโนมัติ
- ✅ Backup อัตโนมัติก่อนอัพเดท
- ✅ แสดง changelog และ breaking changes
- ✅ Rollback ได้หากเกิดปัญหา

**ขั้นตอน:**

1. **เข้าสู่ Admin Panel**
   ```
   https://yourdomain.com/admin/updates
   ```

2. **ตรวจสอบอัพเดทใหม่**
   - คลิกปุ่ม "🔍 ตรวจสอบอัพเดท"
   - ระบบจะเช็คจาก GitHub Releases อัตโนมัติ

3. **ดูรายละเอียดอัพเดท**
   - คลิก "ดูรายละเอียด" ที่อัพเดทที่ต้องการ
   - อ่าน:
     - **Changelog**: สิ่งที่เปลี่ยนแปลง
     - **Breaking Changes**: สิ่งที่อาจทำให้ระบบเดิมเสีย
     - **Requirements**: ความต้องการของระบบ

4. **ติดตั้งอัพเดท**
   - คลิกปุ่ม "ติดตั้ง"
   - ยืนยันการอัพเดท
   - ระบบจะ:
     - ✓ สร้าง backup อัตโนมัติ
     - ✓ ดาวน์โหลด update
     - ✓ รัน database migrations
     - ✓ อัพเดท version
     - ✓ Clear caches

5. **ตรวจสอบผลลัพธ์**
   - ดูใน "ประวัติการอัพเดท"
   - ตรวจสอบว่าอัพเดทสำเร็จหรือไม่

**หมายเหตุ:**
- ⚠️ ควร backup database ด้วยตนเองก่อนอัพเดท major version
- ✅ ระบบจะ backup อัตโนมัติทุกครั้ง
- ✅ สามารถ rollback ได้หากเกิดปัญหา

---

### วิธีที่ 2: อัพเดทผ่าน Command Line

**สำหรับผู้ที่มี SSH access:**

```bash
# เข้าสู่โฟลเดอร์โปรเจค
cd /path/to/Thaiprompt-Affiliate

# เช็ค version ปัจจุบัน
php artisan --version

# รันคำสั่งอัพเดท (ไปเวอร์ชันล่าสุด)
php artisan app:update

# หรืออัพเดทไปเวอร์ชันเฉพาะ
php artisan app:update v2.0.0

# พร้อม options
php artisan app:update --force           # ข้าม confirmation
php artisan app:update --no-backup       # ไม่ต้อง backup
php artisan app:update --skip-deps       # ข้ามการติดตั้ง dependencies
```

**คำสั่งที่เป็นประโยชน์:**

```bash
# เช็คว่ามีอัพเดทใหม่หรือไม่
php artisan app:check-update

# ดู changelog
php artisan app:changelog

# ดูประวัติการอัพเดท
php artisan app:update-history

# Rollback (ถ้าเกิดปัญหา)
php artisan app:rollback
```

---

### การอัพเดทแบบ Manual (Git)

**สำหรับ Developer:**

```bash
# Backup database ก่อน
mysqldump -u root -p thaiprompt_affiliate > backup.sql

# หรือ SQLite
cp database/database.sqlite database/backup.sqlite

# Enable maintenance mode
php artisan down

# Stash local changes (ถ้ามี)
git stash

# Fetch และ pull เวอร์ชันใหม่
git fetch origin --tags
git checkout main  # หรือ branch ที่ต้องการ
git pull origin main

# หรือ checkout specific version
git checkout v2.0.0

# Update dependencies
composer install --no-dev --optimize-autoloader

# Run migrations
php artisan migrate --force

# Clear และ cache
php artisan optimize

# Disable maintenance mode
php artisan up

# ตรวจสอบว่าทำงานได้
php artisan --version
```

---

### Rollback หากเกิดปัญหา

**ผ่าน Admin Panel:**
1. ไปที่ `/admin/updates/logs`
2. คลิกที่การอัพเดทที่ต้องการ rollback
3. คลิกปุ่ม "Rollback"

**ผ่าน Command Line:**
```bash
# แสดง update logs
php artisan app:update-history

# Rollback โดยระบุ log ID
php artisan app:rollback {log_id}

# หรือ restore จาก backup manual
php artisan down
git checkout v1.0.0  # version เดิม
composer install --no-dev --optimize-autoloader
mysql -u root -p thaiprompt_affiliate < backup.sql
php artisan optimize
php artisan up
```

---

### Best Practices สำหรับการอัพเดท

1. **Backup ทุกครั้ง**
   ```bash
   # Database
   mysqldump -u root -p thaiprompt_affiliate > backup-$(date +%Y%m%d).sql

   # Files
   tar -czf backup-files-$(date +%Y%m%d).tar.gz .
   ```

2. **ทดสอบบน Staging ก่อน**
   - อัพเดทบน staging environment ก่อน
   - ทดสอบฟีเจอร์ทั้งหมด
   - แล้วค่อยอัพเดทบน production

3. **อ่าน Changelog ทุกครั้ง**
   - ดู breaking changes
   - ดู new features
   - ดู bug fixes

4. **เช็ค Requirements**
   - PHP version
   - PHP extensions
   - Database version

5. **Maintenance Mode**
   - เปิด maintenance mode ขณะอัพเดท
   - แจ้งผู้ใช้ล่วงหน้า

---

## FAQ

### Q: ติดตั้งเสร็จแล้ว แต่เข้าเว็บไม่ได้

**A:** ตรวจสอบดังนี้:
```bash
# 1. ตรวจสอบว่า serve ยังรันอยู่หรือไม่
ps aux | grep "php artisan serve"

# 2. ตรวจสอบ port
lsof -i:8000

# 3. ลองรันใหม่
php artisan serve

# 4. ตรวจสอบ logs
tail -f storage/logs/laravel.log
```

---

### Q: ลืมรหัสผ่าน Super Admin

**A:** สร้าง Super Admin ใหม่:
```bash
php artisan tinker

# ในหน้า tinker
$user = new App\Models\User();
$user->name = 'New Admin';
$user->email = 'newadmin@example.com';
$user->password = bcrypt('newpassword');
$user->role = 'super_admin';
$user->is_super_admin = true;
$user->save();
exit
```

---

### Q: ต้องการใช้ MySQL แทน SQLite

**A:** แก้ไขไฟล์ .env:
```bash
# เปิดไฟล์ .env
nano .env

# แก้ไข
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=thaiprompt_affiliate
DB_USERNAME=root
DB_PASSWORD=your_password

# สร้าง database
mysql -u root -p -e "CREATE DATABASE thaiprompt_affiliate;"

# รัน migration
php artisan migrate
```

---

### Q: ต้องการรัน production mode

**A:** อ่านคู่มือ:
```bash
# ดู deployment guide
cat DEPLOYMENT.md

# หรือใช้ deploy script
./deploy.sh
```

---

### Q: ต้องการ backup ข้อมูล

**A:** Backup database:
```bash
# SQLite
cp database/database.sqlite database/backup-$(date +%Y%m%d).sqlite

# MySQL
mysqldump -u root -p thaiprompt_affiliate > backup-$(date +%Y%m%d).sql
```

---

## 📞 ติดปัญหา?

ถ้าคุณยังติดปัญหาหลังจากลองทุกวิธีแล้ว:

1. **เปิด Issue:** https://github.com/xjanova/Thaiprompt-Affiliate/issues
2. **ส่ง Email:** support@thaiprompt.com
3. **แนบข้อมูล:**
   - Output ของคำสั่งที่รัน
   - Error message
   - PHP version: `php --version`
   - OS: `uname -a` (Linux/macOS) หรือ `ver` (Windows)

---

## 🎉 สำเร็จแล้ว!

ตอนนี้คุณมี TP-Affiliate ทำงานบนเครื่องแล้ว!

### ขั้นตอนถัดไป:

1. ✅ ปรับแต่งการตั้งค่าใน Admin Dashboard
2. ✅ สร้างผู้ใช้และ Affiliates
3. ✅ ทดสอบระบบ Referral Code
4. ✅ อ่านเอกสารเพิ่มเติม:
   - [DEVELOPMENT.md](DEVELOPMENT.md) - คู่มือนักพัฒนา
   - [DEPLOYMENT.md](DEPLOYMENT.md) - คู่มือ Deploy
   - [AUTHENTICATION.md](AUTHENTICATION.md) - คู่มือ Authentication

---

**Happy coding! 🚀**
