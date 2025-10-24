# 📘 คู่มือการติดตั้ง ThaiPrompt Marketplace

คู่มือนี้จะแนะนำขั้นตอนการติดตั้งระบบ ThaiPrompt Multi-vendor Marketplace พร้อมระบบ MLM อย่างละเอียดทุกขั้นตอน

---

## 📋 สารบัญ

1. [ความต้องการของระบบ](#ความต้องการของระบบ)
2. [การติดตั้งบน Windows](#การติดตั้งบน-windows)
3. [การติดตั้งบน macOS](#การติดตั้งบน-macos)
4. [การติดตั้งบน Linux (Ubuntu/Debian)](#การติดตั้งบน-linux-ubuntudebian)
5. [การตั้งค่าฐานข้อมูล](#การตั้งค่าฐานข้อมูล)
6. [การติดตั้งโปรเจค Laravel](#การติดตั้งโปรเจค-laravel)
7. [การตั้งค่า Environment Variables](#การตั้งค่า-environment-variables)
8. [การ Migrate Database](#การ-migrate-database)
9. [การติดตั้ง Frontend Assets](#การติดตั้ง-frontend-assets)
10. [การทดสอบระบบ](#การทดสอบระบบ)
11. [การแก้ไขปัญหาที่พบบ่อย](#การแก้ไขปัญหาที่พบบ่อย)
12. [การตั้งค่าเพิ่มเติม](#การตั้งค่าเพิ่มเติม)

---

## ความต้องการของระบบ

### ซอฟต์แวร์ที่จำเป็น

| Software | Version | หมายเหตุ |
|----------|---------|----------|
| **PHP** | >= 8.1 | พร้อม Extensions: BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML, GD |
| **Composer** | >= 2.0 | Dependency Manager สำหรับ PHP |
| **MySQL** | >= 8.0 | หรือ MariaDB >= 10.3 |
| **Node.js** | >= 16.x | สำหรับ build frontend assets |
| **NPM** | >= 8.x | มากับ Node.js |
| **Git** | >= 2.0 | สำหรับ version control |

### PHP Extensions ที่จำเป็น

ตรวจสอบว่าติดตั้งครบแล้ว:

```bash
# ตรวจสอบ PHP version
php -v

# ตรวจสอบ extensions ที่ติดตั้ง
php -m
```

Extensions ที่ต้องมี:
- ✅ BCMath
- ✅ Ctype
- ✅ Fileinfo
- ✅ JSON
- ✅ Mbstring
- ✅ OpenSSL
- ✅ PDO
- ✅ PDO_MySQL
- ✅ Tokenizer
- ✅ XML
- ✅ GD (สำหรับ image processing)
- ✅ cURL
- ✅ Zip

### ฮาร์ดแวร์ขั้นต่ำ

- **RAM:** 2GB ขึ้นไป (แนะนำ 4GB)
- **Storage:** 5GB ว่าง
- **Processor:** Dual-core 2GHz ขึ้นไป

---

## การติดตั้งบน Windows

### 1. ติดตั้ง PHP

#### วิธีที่ 1: ใช้ XAMPP (แนะนำสำหรับมือใหม่)

1. ดาวน์โหลด XAMPP จาก https://www.apachefriends.org/
2. เลือกเวอร์ชั่น PHP 8.1 ขึ้นไป
3. ติดตั้งและเปิดใช้งาน Apache และ MySQL
4. เพิ่ม PHP ใน System PATH:
   ```
   Control Panel → System → Advanced → Environment Variables
   เพิ่ม C:\xampp\php ใน PATH
   ```

#### วิธีที่ 2: ติดตั้ง PHP แบบ Manual

1. ดาวน์โหลดจาก https://windows.php.net/download/
2. แตกไฟล์ไปที่ `C:\php`
3. คัดลอก `php.ini-development` เป็น `php.ini`
4. แก้ไข `php.ini`:
   ```ini
   extension=gd
   extension=mbstring
   extension=pdo_mysql
   extension=zip
   extension=curl
   extension=fileinfo
   extension=openssl
   ```
5. เพิ่มใน System PATH: `C:\php`

### 2. ติดตั้ง Composer

1. ดาวน์โหลด Composer-Setup.exe จาก https://getcomposer.org/download/
2. รันไฟล์ติดตั้ง
3. ทดสอบ:
   ```bash
   composer --version
   ```

### 3. ติดตั้ง Node.js

1. ดาวน์โหลดจาก https://nodejs.org/ (เลือก LTS version)
2. ติดตั้งตามขั้นตอน
3. ทดสอบ:
   ```bash
   node --version
   npm --version
   ```

### 4. ติดตั้ง MySQL

#### ถ้าใช้ XAMPP:
- MySQL มาพร้อมกับ XAMPP แล้ว
- เปิดใช้งานผ่าน XAMPP Control Panel

#### ถ้าติดตั้งแยก:
1. ดาวน์โหลด MySQL Community Server จาก https://dev.mysql.com/downloads/
2. ติดตั้งและจดจำ root password
3. เพิ่มใน System PATH: `C:\Program Files\MySQL\MySQL Server 8.0\bin`

### 5. ติดตั้ง Git

1. ดาวน์โหลดจาก https://git-scm.com/download/win
2. ติดตั้งพร้อม Git Bash
3. ทดสอบ:
   ```bash
   git --version
   ```

---

## การติดตั้งบน macOS

### 1. ติดตั้ง Homebrew

```bash
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
```

### 2. ติดตั้ง PHP

```bash
brew install php@8.1
```

ตรวจสอบ PHP extensions:
```bash
php -m
```

### 3. ติดตั้ง Composer

```bash
brew install composer
composer --version
```

### 4. ติดตั้ง MySQL

```bash
brew install mysql
brew services start mysql
mysql_secure_installation
```

### 5. ติดตั้ง Node.js

```bash
brew install node@16
node --version
npm --version
```

### 6. ติดตั้ง Git

```bash
brew install git
git --version
```

---

## การติดตั้งบน Linux (Ubuntu/Debian)

### 1. อัพเดทระบบ

```bash
sudo apt update
sudo apt upgrade -y
```

### 2. ติดตั้ง PHP 8.1 และ Extensions

```bash
# เพิ่ม Repository
sudo add-apt-repository ppa:ondrej/php
sudo apt update

# ติดตั้ง PHP และ Extensions
sudo apt install -y php8.1 php8.1-cli php8.1-common php8.1-mysql \
  php8.1-zip php8.1-gd php8.1-mbstring php8.1-curl php8.1-xml \
  php8.1-bcmath php8.1-fpm php8.1-intl

# ตรวจสอบ
php -v
php -m
```

### 3. ติดตั้ง Composer

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer
composer --version
```

### 4. ติดตั้ง MySQL

```bash
sudo apt install -y mysql-server
sudo mysql_secure_installation
```

ตั้งค่า root password:
```bash
sudo mysql
ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'your_password';
FLUSH PRIVILEGES;
EXIT;
```

### 5. ติดตั้ง Node.js

```bash
curl -fsSL https://deb.nodesource.com/setup_16.x | sudo -E bash -
sudo apt install -y nodejs
node --version
npm --version
```

### 6. ติดตั้ง Git

```bash
sudo apt install -y git
git --version
```

---

## การตั้งค่าฐานข้อมูล

### 1. สร้าง Database

#### ผ่าน Command Line:

```bash
mysql -u root -p
```

```sql
CREATE DATABASE thaiprompt_marketplace CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'thaiprompt'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON thaiprompt_marketplace.* TO 'thaiprompt'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

#### ผ่าน phpMyAdmin:

1. เปิด phpMyAdmin (http://localhost/phpmyadmin)
2. ไปที่แท็บ "Databases"
3. ชื่อ Database: `thaiprompt_marketplace`
4. Collation: `utf8mb4_unicode_ci`
5. คลิก "Create"

### 2. ทดสอบการเชื่อมต่อ

```bash
mysql -u thaiprompt -p thaiprompt_marketplace
```

---

## การติดตั้งโปรเจค Laravel

### 1. Clone โปรเจค

```bash
# เลือก directory ที่ต้องการติดตั้ง
cd /path/to/your/projects

# Clone repository
git clone https://github.com/xjanova/Thaiprompt-Affiliate.git

# เข้าไปใน directory
cd Thaiprompt-Affiliate
```

### 2. ติดตั้ง PHP Dependencies

```bash
composer install
```

**หมายเหตุ:** ขั้นตอนนี้อาจใช้เวลา 2-5 นาที ขึ้นอยู่กับความเร็วอินเทอร์เน็ต

ถ้าเจอ error `The requested PHP extension ... is missing`:
```bash
# ติดตั้ง extension ที่ขาด (ตัวอย่างบน Ubuntu)
sudo apt install php8.1-[extension-name]
```

### 3. ติดตั้ง JavaScript Dependencies

```bash
npm install
```

**หมายเหตุ:** ขั้นตอนนี้อาจใช้เวลา 3-10 นาที

### 4. คัดลอกไฟล์ Environment

```bash
cp .env.example .env
```

### 5. สร้าง Application Key

```bash
php artisan key:generate
```

คำสั่งนี้จะสร้าง APP_KEY ใน `.env` file อัตโนมัติ

---

## การตั้งค่า Environment Variables

แก้ไขไฟล์ `.env` ตามรายละเอียดด้านล่าง:

### 1. การตั้งค่าพื้นฐาน

```env
APP_NAME="ThaiPrompt Marketplace"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
```

**สำคัญ:**
- `APP_ENV=local` สำหรับการพัฒนา
- `APP_ENV=production` สำหรับ production server
- `APP_DEBUG=false` เมื่อ deploy จริง

### 2. การตั้งค่าฐานข้อมูล

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=thaiprompt_marketplace
DB_USERNAME=thaiprompt
DB_PASSWORD=your_secure_password
```

### 3. การตั้งค่า Mail (Development)

#### ใช้ Mailpit (แนะนำสำหรับ local development):

```env
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@thaiprompt.local"
MAIL_FROM_NAME="${APP_NAME}"
```

#### ติดตั้ง Mailpit:

```bash
# macOS
brew install mailpit
mailpit

# Linux
wget https://github.com/axllent/mailpit/releases/latest/download/mailpit-linux-amd64.tar.gz
tar -xzf mailpit-linux-amd64.tar.gz
sudo mv mailpit /usr/local/bin/
mailpit

# Windows - ดาวน์โหลดจาก https://github.com/axllent/mailpit/releases
```

เปิด Mailpit UI: http://localhost:8025

#### ใช้ Gmail (สำหรับ production):

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@thaiprompt.com"
MAIL_FROM_NAME="${APP_NAME}"
```

**หมายเหตุ:** ต้องสร้าง App Password จาก Google Account Settings

### 4. การตั้งค่า Payment Gateway

#### Stripe:

1. สมัครบัญชีที่ https://stripe.com
2. ไปที่ Dashboard → Developers → API Keys
3. คัดลอก Publishable key และ Secret key

```env
STRIPE_KEY=pk_test_xxxxxxxxxxxxxxxxxxxx
STRIPE_SECRET=sk_test_xxxxxxxxxxxxxxxxxxxx
STRIPE_WEBHOOK_SECRET=whsec_xxxxxxxxxxxxxxxxxxxx
```

#### PromptPay:

```env
PROMPTPAY_MERCHANT_ID=your_merchant_id
PROMPTPAY_TERMINAL_ID=your_terminal_id
PROMPTPAY_API_KEY=your_api_key
```

### 5. การตั้งค่า LINE Official Account

1. สร้าง LINE Official Account ที่ https://developers.line.biz/
2. สร้าง Messaging API channel
3. คัดลอก Channel ID, Channel Secret, และ Access Token

```env
LINE_CHANNEL_ID=1234567890
LINE_CHANNEL_SECRET=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
LINE_CHANNEL_ACCESS_TOKEN=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

### 6. การตั้งค่า MLM

```env
# ประเภท MLM: unilevel, binary, matrix
MLM_TYPE=unilevel

# ระดับสูงสุดที่จะคำนวณค่าคอมมิชชั่น
MLM_MAX_DEPTH=10

# อัตราค่าคอมมิชชั่นแต่ละระดับ (%)
COMMISSION_RATE_LEVEL_1=10
COMMISSION_RATE_LEVEL_2=5
COMMISSION_RATE_LEVEL_3=3
COMMISSION_RATE_LEVEL_4=2
COMMISSION_RATE_LEVEL_5=1

# อัตราแบ่งรายได้ระหว่าง Vendor และ Admin (%)
VENDOR_COMMISSION_RATE=70
ADMIN_COMMISSION_RATE=30
```

### 7. การตั้งค่า Session และ Cache

```env
SESSION_DRIVER=file
SESSION_LIFETIME=120

CACHE_DRIVER=file
QUEUE_CONNECTION=database
```

### 8. การตั้งค่า GitHub Webhook (สำหรับ Auto-Update)

```env
GITHUB_WEBHOOK_SECRET=your_random_secret_string
```

---

## การ Migrate Database

### 1. ทดสอบการเชื่อมต่อ Database

```bash
php artisan db:show
```

ถ้าเชื่อมต่อสำเร็จ จะแสดงข้อมูล database

### 2. รัน Migrations

```bash
php artisan migrate
```

คำสั่งนี้จะสร้างตารางทั้งหมด (28 ตาราง):

```
✓ users
✓ vendors
✓ products
✓ categories
✓ orders
✓ order_items
✓ carts
✓ cart_items
✓ reviews
✓ review_responses
✓ wishlists
✓ mlm_networks
✓ mlm_ranks
✓ user_ranks
✓ mlm_genealogy
✓ commissions
✓ commission_settings
✓ bonuses
✓ wallets
✓ wallet_transactions
✓ withdrawals
✓ invitations
✓ line_messages
✓ coupons
✓ coupon_usage
✓ pos_sessions
✓ pos_sales
✓ pos_sale_items
```

### 3. Seed ข้อมูลเริ่มต้น (Optional)

```bash
php artisan db:seed
```

ข้อมูลที่จะถูกสร้าง:
- ✅ Admin user: admin@example.com / password
- ✅ Vendor user: vendor@example.com / password
- ✅ Customer users (10 users)
- ✅ Categories (5-10 categories)
- ✅ Products (50-100 products)
- ✅ MLM Ranks (Bronze, Silver, Gold, Platinum, Diamond)
- ✅ Commission Settings

### 4. ตรวจสอบข้อมูล

```bash
# ดูจำนวนข้อมูลในแต่ละตาราง
php artisan tinker
```

```php
User::count();        // ควรได้ 12+ users
Product::count();     // ควรได้ 50+ products
Category::count();    // ควรได้ 5+ categories
exit
```

### 5. สร้าง Storage Link

```bash
php artisan storage:link
```

คำสั่งนี้สร้าง symbolic link จาก `public/storage` ไปยัง `storage/app/public`

---

## การติดตั้ง Frontend Assets

### 1. Build Assets สำหรับ Development

```bash
npm run dev
```

คำสั่งนี้จะ compile:
- ✅ Tailwind CSS
- ✅ JavaScript files
- ✅ NFC Scanner module

### 2. Build Assets สำหรับ Production

```bash
npm run build
```

คำสั่งนี้จะ compile และ minify assets เพื่อใช้งานจริง

### 3. Watch Mode (สำหรับการพัฒนา)

```bash
npm run watch
```

คำสั่งนี้จะ compile assets ใหม่ทุกครั้งที่มีการแก้ไขไฟล์

---

## การทดสอบระบบ

### 1. เริ่มต้น Development Server

```bash
php artisan serve
```

Server จะรันที่: http://localhost:8000

**เปิดหน้าเว็บเพิ่มเติม (Terminal แยก):**

```bash
# Terminal 2: Watch assets
npm run dev

# Terminal 3: Queue Worker (ถ้าใช้)
php artisan queue:work
```

### 2. ทดสอบหน้าเว็บ

เปิดเบราว์เซอร์และไปที่:

- ✅ **หน้าแรก:** http://localhost:8000
- ✅ **หน้า Login:** http://localhost:8000/login
- ✅ **หน้า Register:** http://localhost:8000/register
- ✅ **สินค้า:** http://localhost:8000/products
- ✅ **Admin Dashboard:** http://localhost:8000/admin/dashboard

### 3. ทดสอบ Login

**Admin Account:**
- Email: `admin@example.com`
- Password: `password`

**Vendor Account:**
- Email: `vendor@example.com`
- Password: `password`

**Customer Account:**
- Email: `customer@example.com`
- Password: `password`

### 4. รัน Tests

```bash
# รัน Unit Tests และ Feature Tests ทั้งหมด
php artisan test

# รัน test เฉพาะ class
php artisan test --filter=MlmServiceTest

# รัน test พร้อม coverage report
php artisan test --coverage
```

### 5. ทดสอบ API

#### ใช้ cURL:

```bash
# Register
curl -X POST http://localhost:8000/api/v1/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'

# Login
curl -X POST http://localhost:8000/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123"
  }'
```

#### ใช้ Postman:

1. ดาวน์โหลด Postman จาก https://www.postman.com/
2. Import OpenAPI spec จาก `storage/api-docs/openapi.yaml`
3. ทดสอบ endpoints ต่างๆ

---

## การแก้ไขปัญหาที่พบบ่อย

### 1. Error: "php command not found"

**สาเหตุ:** PHP ไม่ได้อยู่ใน System PATH

**แก้ไข:**
- Windows: เพิ่ม `C:\xampp\php` ใน Environment Variables
- macOS/Linux: `export PATH="/usr/local/bin/php:$PATH"`

### 2. Error: "composer command not found"

**สาเหตุ:** Composer ไม่ได้ติดตั้งหรือไม่อยู่ใน PATH

**แก้ไข:**
```bash
# ติดตั้งใหม่
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### 3. Error: "SQLSTATE[HY000] [1049] Unknown database"

**สาเหตุ:** Database ยังไม่ถูกสร้าง

**แก้ไข:**
```bash
mysql -u root -p
CREATE DATABASE thaiprompt_marketplace;
EXIT;
```

### 4. Error: "The stream or file could not be opened"

**สาเหตุ:** Permission ไม่ถูกต้อง

**แก้ไข:**
```bash
# Linux/macOS
sudo chmod -R 775 storage
sudo chmod -R 775 bootstrap/cache

# Windows (Run as Administrator)
icacls storage /grant Users:F /T
icacls bootstrap/cache /grant Users:F /T
```

### 5. Error: "Class 'GD' not found"

**สาเหตุ:** GD extension ไม่ได้ติดตั้ง

**แก้ไข:**
```bash
# Ubuntu/Debian
sudo apt install php8.1-gd

# macOS
brew install php@8.1
```

### 6. Error: "npm ERR! code ENOENT"

**สาเหตุ:** Node.js/NPM ไม่ได้ติดตั้ง

**แก้ไข:**
```bash
# ติดตั้ง Node.js จาก https://nodejs.org/
# หรือใช้ package manager
```

### 7. Error: "Vite manifest not found"

**สาเหตุ:** Assets ยังไม่ได้ build

**แก้ไข:**
```bash
npm run build
```

### 8. Stripe Webhook ไม่ทำงาน

**สาเหตุ:** Webhook signature ไม่ถูกต้อง

**แก้ไข:**
1. ตรวจสอบ `STRIPE_WEBHOOK_SECRET` ใน `.env`
2. ใช้ Stripe CLI สำหรับทดสอบ local:
```bash
stripe listen --forward-to localhost:8000/api/webhooks/stripe
```

### 9. Email ไม่ถูกส่ง

**สาเหตุ:** Mail configuration ไม่ถูกต้อง

**แก้ไข:**
```bash
# ตรวจสอบ config
php artisan config:cache

# ดู log
tail -f storage/logs/laravel.log
```

### 10. NFC ไม่ทำงาน

**สาเหตุ:** Web NFC ต้องใช้ HTTPS หรือ localhost

**แก้ไข:**
- ใช้ `https://` สำหรับ production
- ใช้ `localhost` สำหรับ development
- ใช้เฉพาะ Chrome บน Android

---

## การตั้งค่าเพิ่มเติม

### 1. ตั้งค่า Queue Worker

แก้ไข `.env`:
```env
QUEUE_CONNECTION=database
```

รัน queue worker:
```bash
php artisan queue:work
```

สำหรับ production ใช้ Supervisor:
```bash
sudo apt install supervisor
```

สร้างไฟล์ `/etc/supervisor/conf.d/laravel-worker.conf`:
```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/application/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=8
redirect_stderr=true
stdout_logfile=/path/to/application/storage/logs/worker.log
```

เริ่มใช้งาน:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

### 2. ตั้งค่า Task Scheduler

เพิ่มใน crontab:
```bash
crontab -e
```

เพิ่มบรรทัดนี้:
```
* * * * * cd /path/to/application && php artisan schedule:run >> /dev/null 2>&1
```

### 3. ตั้งค่า Webhooks

#### GitHub Webhook (Auto-Deploy):

1. ไปที่ Repository Settings → Webhooks
2. Add webhook:
   - **Payload URL:** `https://yourdomain.com/api/webhooks/github`
   - **Content type:** `application/json`
   - **Secret:** (ใช้ค่าจาก GITHUB_WEBHOOK_SECRET)
   - **Events:** Just the push event
3. Save

#### Stripe Webhook:

1. ไปที่ Stripe Dashboard → Developers → Webhooks
2. Add endpoint:
   - **URL:** `https://yourdomain.com/api/webhooks/stripe`
   - **Events:** `payment_intent.succeeded`, `payment_intent.payment_failed`, `charge.refunded`
3. คัดลอก Signing secret ไปใส่ใน `.env`

### 4. ตั้งค่า SSL Certificate (Production)

#### ใช้ Let's Encrypt (ฟรี):

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com
```

อัพเดท `.env`:
```env
APP_URL=https://yourdomain.com
```

### 5. ตั้งค่า Performance Optimization

```bash
# Cache config
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize autoloader
composer install --optimize-autoloader --no-dev
```

### 6. ตั้งค่า Backup Database (แนะนำ)

#### ติดตั้ง spatie/laravel-backup:

```bash
composer require spatie/laravel-backup
php artisan vendor:publish --provider="Spatie\Backup\BackupServiceProvider"
```

แก้ไข `config/backup.php`:
```php
'destination' => [
    'disks' => [
        'local',
        's3', // ถ้าใช้ AWS S3
    ],
],
```

รัน backup:
```bash
php artisan backup:run
```

ตั้งเวลา auto backup ใน `app/Console/Kernel.php`:
```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('backup:clean')->daily()->at('01:00');
    $schedule->command('backup:run')->daily()->at('02:00');
}
```

---

## ✅ Checklist การติดตั้งสำเร็จ

ตรวจสอบว่าทุกอย่างพร้อมใช้งาน:

- [ ] PHP >= 8.1 ติดตั้งพร้อม extensions ครบ
- [ ] Composer ติดตั้งแล้ว
- [ ] MySQL ติดตั้งและสร้าง database แล้ว
- [ ] Node.js และ NPM ติดตั้งแล้ว
- [ ] Clone repository สำเร็จ
- [ ] `composer install` สำเร็จ
- [ ] `npm install` สำเร็จ
- [ ] `.env` file ตั้งค่าครบถ้วน
- [ ] `php artisan key:generate` แล้ว
- [ ] `php artisan migrate` สำเร็จ
- [ ] `php artisan db:seed` สำเร็จ (optional)
- [ ] `php artisan storage:link` สำเร็จ
- [ ] `npm run build` สำเร็จ
- [ ] `php artisan serve` รันได้
- [ ] เข้าหน้าเว็บได้ที่ http://localhost:8000
- [ ] Login ด้วย admin account สำเร็จ
- [ ] API ทำงานได้ถูกต้อง
- [ ] Tests ผ่านหมด (`php artisan test`)
- [ ] Email notifications ทำงาน (ถ้าตั้งค่าแล้ว)
- [ ] Webhooks ตั้งค่าเรียบร้อย (ถ้าต้องการ)

---

## 📞 ขอความช่วยเหลือ

หากพบปัญหาในการติดตั้ง:

1. **ตรวจสอบ Logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

2. **ดูเอกสารเพิ่มเติม:**
   - [Laravel Documentation](https://laravel.com/docs/10.x)
   - [API Documentation](./API_DOCUMENTATION.md)
   - [Configuration Guide](./CONFIGURATION.md)

3. **ติดต่อทีมพัฒนา:**
   - GitHub Issues: https://github.com/xjanova/Thaiprompt-Affiliate/issues
   - Email: support@thaiprompt.com

---

**🎉 ยินดีด้วย! คุณติดตั้ง ThaiPrompt Marketplace สำเร็จแล้ว**

ถัดไป: อ่าน [Configuration Guide](./CONFIGURATION.md) เพื่อศึกษาการตั้งค่าเพิ่มเติม
