# ThaiPrompt Affiliate Marketplace

ระบบร้านค้าออนไลน์แบบ Multi-vendor Marketplace พร้อมระบบ MLM (Multi-Level Marketing) ที่ครบครัน พัฒนาด้วย Laravel

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![Laravel](https://img.shields.io/badge/Laravel-10.x-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.1+-purple.svg)](https://php.net)

## เวอร์ชัน 1.1.0

---

## 🌟 คุณสมบัติหลัก

### 1. Multi-Vendor Marketplace
- ✅ ให้ผู้ใช้สามารถเปิดร้านค้าออนไลน์ของตัวเองได้
- ✅ จัดการสินค้า คำสั่งซื้อ และรายได้ของแต่ละร้าน
- ✅ ระบบอนุมัติร้านค้าโดย Admin
- ✅ Dashboard สำหรับผู้ขาย (Vendor Dashboard)

### 2. ระบบ MLM (Multi-Level Marketing)
- ✅ Unilevel/Binary MLM Structure
- ✅ ติดตาม Downline แบบไม่จำกัดระดับ
- ✅ การจัดการโครงสร้าง MLM Network อัตโนมัติ
- ✅ Genealogy Tree สำหรับดู Upline/Downline
- ✅ การคำนวณ Commission แบบหลายระดับ

### 3. ระบบ Commission และ Bonus
- ✅ Level Commission (ค่าคอมมิชชั่นตามระดับ)
- ✅ Rank Achievement Bonus (โบนัสจากการขึ้นยศ)
- ✅ Performance Bonus
- ✅ Matching Bonus (สำหรับ Binary MLM)
- ✅ ตั้งค่า Commission แบบยืดหยุ่น

### 4. ระบบ Rank
- ✅ หลายระดับยศ (Bronze, Silver, Gold, Platinum, Diamond)
- ✅ เงื่อนไขการขึ้นยศที่กำหนดได้
- ✅ โบนัสพิเศษสำหรับแต่ละระดับ
- ✅ ติดตามประวัติการขึ้นยศ

### 5. ระบบ Wallet
- ✅ กระเป๋าเงินภายในระบบสำหรับแต่ละผู้ใช้
- ✅ เติมเงิน/ถอนเงิน
- ✅ ประวัติการทำรายการแบบละเอียด
- ✅ ระบบอนุมัติการถอนเงินโดย Admin
- ✅ ใช้ Wallet ชำระเงินได้

### 6. ระบบ POS (Point of Sale)
- ✅ ขายหน้าร้านแบบ Real-time
- ✅ จัดการ Session การขาย
- ✅ พิมพ์ใบเสร็จ
- ✅ รองรับหลายช่องทางการชำระเงิน
- ✅ เชื่อมโยงกับ Inventory

### 7. ระบบ Payment Gateway
- ✅ **Stripe** - รับชำระเงินผ่านบัตรเครดิต/เดบิต
- ✅ **PromptPay** - QR Code ชำระเงินแบบไทย
- ✅ **Wallet** - ชำระผ่านกระเป๋าเงิน
- ✅ **Cash** - เงินสด (สำหรับ POS)

### 8. UI & Frontend **[🆕 เพิ่งเพิ่ม]**
- ✅ **Blade Templates** - 38 view files ครบทุกหน้า
- ✅ **Responsive Design** - Tailwind CSS + Alpine.js
- ✅ **Dashboard Widgets** - Charts & Analytics (Chart.js)
- ✅ **Default Avatar & Images** - SVG placeholders

### 9. NFC Integration **[🆕 เพิ่งเพิ่ม]**
- ✅ **Web NFC API** - สำหรับ Chrome Android
- ✅ **Product Scanning** - สแกนสินค้าผ่าน NFC tag
- ✅ **PromptPay QR** - สแกน QR code ผ่าน NFC
- ✅ **Tag Writing** - เขียนข้อมูลลง NFC tag

### 10. Webhook System **[🆕 เพิ่งเพิ่ม]**
- ✅ **Stripe Webhook** - Payment event handling
- ✅ **PromptPay Webhook** - Payment confirmation
- ✅ **LINE Webhook** - Message & event handling
- ✅ **GitHub Webhook** - Auto-deployment

### 11. Email Notifications **[🆕 เพิ่งเพิ่ม]**
- ✅ **Order Confirmation** - ยืนยันคำสั่งซื้อ
- ✅ **Commission Notification** - แจ้งค่าคอมมิชชั่น
- ✅ **Withdrawal Approved** - อนุมัติการถอนเงิน
- ✅ **Referral Invitation** - เชิญเพื่อนเข้าร่วม

### 12. Testing & Documentation **[🆕 เพิ่งเพิ่ม]**
- ✅ **Unit Tests** - 18+ test cases (MLM, Wallet)
- ✅ **Feature Tests** - 15+ test cases (Auth, Products)
- ✅ **API Documentation** - OpenAPI 3.0 + Markdown (50+ endpoints)

### 13. Admin Dashboard
- ✅ ภาพรวมระบบทั้งหมด
- ✅ จัดการผู้ใช้งาน
- ✅ อนุมัติ/ปฏิเสธร้านค้า
- ✅ จัดการ Commission และ Withdrawal
- ✅ รายงานยอดขายแบบละเอียด
- ✅ ตั้งค่าระบบ
- 🆕 **กราฟและ Analytics แบบ Real-time** พร้อม GSAP Animations
- 🆕 **ตั้งค่าโลโก้และราคา** สำหรับ Super Admin
- 🆕 **ดูข้อมูลร้านค้าและสมาชิกทั้งหมด** แบบครบถ้วน

### 14. 🎨 ระบบ Theme Customization (Premium Feature)
- 🆕 **ปรับแต่งสีธีม** - เปลี่ยนสีหลัก, สีรอง, สีเน้น
- 🆕 **Gradient Colors** - ไล่เฉดสีแบบสวยงามมืออาชีพ
- 🆕 **เปลี่ยนโลโก้และ Favicon** - สร้างแบรนด์เฉพาะของร้าน
- 🆕 **Custom CSS** - ปรับแต่งการแสดงผลเพิ่มเติม
- 🆕 **Color Presets** - โทนสีสำเร็จรูปให้เลือกใช้
- 🆕 **Premium Subscription** - ระบบสมัครสมาชิกแบบรายเดือน/ปี/ตลอดชีพ

### 15. 🔧 ระบบ Setup Wizard
- 🆕 **Web-based Installation** - ติดตั้งผ่านหน้าเว็บโดยไม่ต้องใช้ CLI
- 🆕 **ตรวจสอบความพร้อมของระบบ** - PHP version, Extensions, Permissions
- 🆕 **ตั้งค่า Database** - พร้อมทดสอบการเชื่อมต่อ
- 🆕 **สร้าง Admin Account** - ในขั้นตอนการติดตั้ง
- 🆕 **Auto Migration & Seeding** - ติดตั้งฐานข้อมูลอัตโนมัติ
- 🆕 **Beautiful UI with Animations** - ติดตั้งง่ายด้วย UI ที่สวยงาม

### 16. 💾 ระบบ Backup & Version Control
- 🆕 **Auto Backup** - สำรองข้อมูลอัตโนมัติก่อนอัพเดท
- 🆕 **Full System Backup** - สำรองทั้งไฟล์และฐานข้อมูล
- 🆕 **Database Backup** - สำรองเฉพาะฐานข้อมูล
- 🆕 **Version Checking** - ตรวจสอบเวอร์ชั่นก่อนอัพเดท
- 🆕 **Backup Management** - จัดการไฟล์สำรองข้อมูล
- 🆕 **One-Click Restore** - กู้คืนข้อมูลได้ทันที

### 17. 💳 ระบบ NFC Payment (v1.1.0)
- 🆕 **Web NFC API** - รองรับ NFC บนเว็บไซต์
- 🆕 สแกนบัตร NFC ผ่านอุปกรณ์ที่รองรับ
- 🆕 ผูกบัตรกับ Wallet ของ User
- 🆕 ชำระเงินแบบ Contactless
- 🆕 เช็คยอดเงินผ่าน NFC
- 🆕 รองรับการใช้งานผ่าน POS
- 🆕 เตรียมพร้อมสำหรับ Mobile App

### 18. ✅ ระบบยืนยันตัวตนร้านค้า (v1.1.0)
- 🆕 **Shop Verification System**
- 🆕 อัปโหลดเอกสารยืนยันตัวตน
- 🆕 ระบบ KYC แบบหลายระดับ
- 🆕 เหรียญตรา 4 ระดับ: 🥉Bronze | 🥈Silver | 🥇Gold | 💎Platinum
- 🆕 ตรวจสอบและอนุมัติโดย Admin
- 🆕 แสดงเหรียญยืนยันบนหน้าร้าน
- 🆕 เพิ่มความน่าเชื่อถือให้ร้านค้า

### 19. 🔄 ระบบอัปเดทอัตโนมัติ (v1.1.0)
- 🆕 **Auto Version Update System**
- 🆕 เช็คเวอร์ชันจาก GitHub อัตโนมัติ
- 🆕 แจ้งเตือน Admin เมื่อมีอัปเดท
- 🆕 แสดง Changelog และ Release Notes
- 🆕 ติดตามประวัติการอัปเดท
- 🆕 คำแนะนำการอัปเดทแบบ Step-by-step

### 20. 🌳 ผังโครงสร้าง MLM แบบ Interactive (v1.1.0)
- 🆕 **D3.js Tree Visualization**
- 🆕 ผังองค์กรแบบ Interactive
- 🆕 Zoom และ Pan ได้อย่างลื่นไหล
- 🆕 แสดงข้อมูลสมาชิกเมื่อ Hover
- 🆕 คลิกดูรายละเอียดสมาชิก
- 🆕 เพิ่มสมาชิกได้จากผังเลย
- 🆕 **กรอบสีแดง** สำหรับสมาชิกที่ยังไม่รักษายอด
- 🆕 **กรอบสีเขียว** สำหรับสมาชิกที่ KYC แล้ว
- 🆕 แสดง Avatar จาก LINE หรือรูปที่อัปโหลด

### 21. 📸 การจัดการรูปโปรไฟล์ (v1.1.0)
- 🆕 รูป Avatar เริ่มต้นสำหรับผู้ใช้ใหม่
- 🆕 ดึงรูปจาก LINE หลัง KYC
- 🆕 อัปโหลดรูปโปรไฟล์เองได้
- 🆕 ปรับขนาดรูปอัตโนมัติ

---

### 12. Vendor Feature Manager 🆕
- ✅ ฟีเจอร์เสริมสำหรับร้านค้า (Advanced Analytics, Email Marketing, Multi-Channel Selling, etc.)
- ✅ ซื้อได้ทุกเวลา - รองรับทั้ง One-time, Monthly และ Yearly
- ✅ Version Tracking - ติดตามเวอร์ชันและประวัติการอัพเดท
- ✅ Changelog System - ดูรายละเอียดการพัฒนาแต่ละฟีเจอร์
- ✅ Multiple Payment Methods - ชำระผ่าน Wallet, Stripe หรือ PromptPay
- ✅ Auto-renewal - ต่ออายุ Subscription อัตโนมัติ
- ✅ Usage Tracking - บันทึกการใช้งานทุกฟีเจอร์
- ✅ UI สวยงาม - แสดงผลแบบ Card พร้อมไอคอน

### 13. LINE OA KYC System 🆕
- ✅ ยืนยันตัวตนผ่าน LINE Official Account อัตโนมัติ
- ✅ บังคับ KYC สำหรับการถอนเงิน (ตั้งค่าได้)
- ✅ ตั้งค่าวงเงินขั้นต่ำที่ต้อง KYC
- ✅ Webhook Integration - รับและประมวลผลข้อความอัตโนมัติ
- ✅ ส่งการแจ้งเตือนผ่าน LINE (ถอนเงิน, ออเดอร์, โปรโมชั่น)
- ✅ บันทึกประวัติการสนทนาทั้งหมด
- ✅ คู่มือการตั้งค่า LINE Developer อย่างละเอียด
- ✅ รองรับ Rich Menu และ Template Message

### 14. Database Migration & Update System 🆕
- ✅ Automatic Database Backup - สำรองอัตโนมัติก่อนอัพเดททุกครั้ง
- ✅ Real-time Progress Tracking - ติดตามความคืบหน้าแบบเรียลไทม์
- ✅ Maintenance Mode - ปิดระบบชั่วคราวพร้อมข้อความแจ้งผู้ใช้
- ✅ Rollback Support - ย้อนกลับได้ถ้าเกิดข้อผิดพลาด
- ✅ Step-by-step Migration Logs - บันทึก Log ทุกขั้นตอน
- ✅ Health Checks - ตรวจสอบสุขภาพระบบอัตโนมัติ
- ✅ Super Admin Interface - หน้า UI สวยงามง่ายต่อการใช้งาน
- ✅ Version Control - ติดตามเวอร์ชันระบบ
- ✅ Backup Verification - ตรวจสอบความถูกต้องของไฟล์สำรอง
- ✅ Auto-cleanup - ลบไฟล์สำรองเก่าอัตโนมัติ

## 🛠️ เทคโนโลยีที่ใช้

### Backend
- **Framework**: Laravel 10.x (PHP 8.1+)
- **Database**: MySQL 8.0+
- **Cache & Queue**: Redis
- **Authentication**: Laravel Sanctum (API Authentication)
- **Permissions**: Spatie Laravel Permission (Role-based Access)
- **Image Processing**: Intervention Image
- **PDF Generation**: DomPDF
- **Excel Export**: Maatwebsite Excel
- **Payment**: Stripe PHP SDK, PromptPay API

### Frontend
- **CSS Framework**: Tailwind CSS 3.x (Responsive Design)
- **JavaScript**: Alpine.js 3.x (Interactive Components)
- **Charts**: Chart.js 4.x (Dashboard Charts)
- **Animations**: GSAP 3.x
- **Tree Visualization**: D3.js 7.x
- **Icons**: Iconify
- **Notifications**: SweetAlert2
- **Build Tool**: Vite 4.x (Asset Bundling)
- **NFC**: Web NFC API (NFC Scanner)

## 📋 ความต้องการของระบบ

- **PHP** >= 8.1 (พร้อม Extensions: BCMath, Ctype, JSON, Mbstring, OpenSSL, PDO, XML, GD, cURL)
- **Composer** >= 2.0
- **MySQL** >= 8.0 หรือ MariaDB >= 10.3
- **Node.js** >= 16.x
- **NPM** >= 8.x
- **Git** >= 2.0

---

## 🚀 การติดตั้ง

### วิธีที่ 1: ติดตั้งผ่าน Web Setup Wizard (แนะนำ) 🌟

1. **Clone โปรเจคและติดตั้ง Dependencies**

```bash
git clone https://github.com/xjanova/Thaiprompt-Affiliate.git
cd Thaiprompt-Affiliate
composer install
npm install && npm run build
```

2. **เริ่มเซิร์ฟเวอร์**

```bash
php artisan serve
```

3. **เปิดเบราว์เซอร์และเข้าสู่ Setup Wizard**

```
http://localhost:8000/setup
```

4. **ทำตามขั้นตอนใน Setup Wizard**
   - ✅ ตรวจสอบความพร้อมของระบบ
   - ✅ ตั้งค่าฐานข้อมูล (พร้อมทดสอบการเชื่อมต่อ)
   - ✅ ติดตั้งและ Migrate ฐานข้อมูล
   - ✅ สร้างบัญชี Super Admin
   - ✅ เสร็จสิ้นการติดตั้ง

---

### วิธีที่ 2: ติดตั้งแบบ Manual (สำหรับผู้เชี่ยวชาญ)

1. **Clone โปรเจค**

```bash
git clone https://github.com/xjanova/Thaiprompt-Affiliate.git
cd Thaiprompt-Affiliate
```

2. **ติดตั้ง Dependencies**

```bash
composer install
npm install
```

3. **ตั้งค่า Environment**

```bash
cp .env.example .env
php artisan key:generate
```

4. **แก้ไขไฟล์ .env**

```env
# Database
DB_DATABASE=thaiprompt_marketplace
DB_USERNAME=your_username
DB_PASSWORD=your_password

# App Settings
APP_NAME="ThaiPrompt Marketplace"
APP_URL=http://localhost:8000

# Stripe
STRIPE_KEY=your_stripe_key
STRIPE_SECRET=your_stripe_secret

# PromptPay
PROMPTPAY_MERCHANT_ID=your_merchant_id

# LINE OA (สำหรับระบบ KYC)
LINE_CHANNEL_ID=your_line_channel_id
LINE_CHANNEL_SECRET=your_line_channel_secret
LINE_CHANNEL_ACCESS_TOKEN=your_line_access_token
LINE_REQUIRE_KYC_FOR_WITHDRAWAL=true
LINE_MIN_WITHDRAWAL_WITHOUT_KYC=1000

# MLM Settings
MLM_TYPE=unilevel
MLM_MAX_DEPTH=10
COMMISSION_RATE_LEVEL_1=10
COMMISSION_RATE_LEVEL_2=5
COMMISSION_RATE_LEVEL_3=3

# NFC Configuration (v1.1.0)
NFC_ENABLED=true
NFC_TIMEOUT=30

# Version Update (v1.1.0)
GITHUB_REPO=xjanova/Thaiprompt-Affiliate
VERSION_CHECK_INTERVAL=24
VERSION_AUTO_CHECK=true

# Verification (v1.1.0)
VERIFICATION_ENABLED=true
VERIFICATION_DOCUMENT_DISK=public
MAX_DOCUMENT_SIZE=5120

# MLM Tree (v1.1.0)
MLM_TREE_MAX_DEPTH=5
```

5. **สร้างฐานข้อมูล**

```bash
php artisan migrate
php artisan db:seed
```

6. **สร้าง Storage Link**

```bash
php artisan storage:link
```

7. **Build Assets**

```bash
npm run build
```

8. **รันเซิร์ฟเวอร์**

```bash
php artisan serve
```

เปิดเบราว์เซอร์ที่ `http://localhost:8000`

**📚 สำหรับขั้นตอนการติดตั้งแบบละเอียด อ่านที่:**
- **[📘 คู่มือการติดตั้ง (INSTALLATION_GUIDE.md)](./INSTALLATION_GUIDE.md)** - ละเอียดทุกขั้นตอนสำหรับ Windows, macOS, Linux

---

## 👤 บัญชีผู้ใช้เริ่มต้น

หลังจากรัน `php artisan db:seed` แล้ว คุณจะได้บัญชีเริ่มต้น:

| Role | Email | Password |
|------|-------|----------|
| **Admin** | admin@example.com | password |
| **Vendor** | vendor@example.com | password |
| **Customer** | customer@example.com | password |

---

## 📊 โครงสร้างฐานข้อมูล

### ตารางหลัก (28 ตาราง)

**Core:**
- users, vendors, products, categories
- orders, order_items, carts, cart_items
- reviews, review_responses, wishlists

**MLM System:**
- mlm_networks, mlm_ranks, user_ranks, mlm_genealogy
- commissions, commission_settings, bonuses

**Wallet System:**
- wallets, wallet_transactions, withdrawals

**Others:**
- invitations, line_messages, coupons, coupon_usage
- pos_sessions, pos_sales, pos_sale_items

---

## 🔧 คำสั่ง Artisan ที่มีประโยชน์

```bash
# Development
php artisan serve                  # รัน dev server
npm run dev                        # Build assets (watch mode)
php artisan migrate:fresh --seed   # Reset database พร้อม seed

# Production
npm run build                      # Build assets for production
php artisan config:cache           # Cache config
php artisan route:cache            # Cache routes
php artisan view:cache             # Cache views

# Testing
php artisan test                   # รัน tests ทั้งหมด
php artisan test --filter=MlmServiceTest  # รัน test เฉพาะ class

# Maintenance
php artisan cache:clear            # ล้าง cache
php artisan queue:work             # รัน queue worker
php artisan schedule:run           # รัน scheduled tasks
```

---

## 📱 API Documentation

API Endpoints มีอยู่ที่ `/api/v1/` - รองรับ **50+ endpoints**

### ตัวอย่าง Endpoints

**Authentication:**
- `POST /api/v1/register` - สมัครสมาชิก
- `POST /api/v1/login` - เข้าสู่ระบบ
- `POST /api/v1/logout` - ออกจากระบบ

**Products:**
- `GET /api/v1/products` - รายการสินค้าทั้งหมด (พร้อม filters)
- `GET /api/v1/products/{id}` - รายละเอียดสินค้า

**Cart:**
- `GET /api/v1/cart` - ดูตะกร้า
- `POST /api/v1/cart/add` - เพิ่มสินค้า
- `PUT /api/v1/cart/{item}` - อัพเดทจำนวน
- `DELETE /api/v1/cart/{item}` - ลบสินค้า

**Orders:**
- `GET /api/v1/orders` - รายการคำสั่งซื้อ
- `POST /api/v1/orders` - สร้างคำสั่งซื้อ
- `GET /api/v1/orders/{id}` - รายละเอียดคำสั่งซื้อ

**MLM:**
- `GET /api/v1/mlm/stats` - สถิติ MLM
- `GET /api/v1/mlm/network` - โครงข่าย MLM
- `GET /api/v1/mlm/commissions` - ประวัติค่าคอมมิชชั่น
- `POST /api/v1/mlm/invite` - ส่งคำเชิญ

**Wallet:**
- `GET /api/v1/wallet` - ข้อมูล Wallet
- `GET /api/v1/wallet/transactions` - ประวัติธุรกรรม
- `POST /api/v1/wallet/withdraw` - ขอถอนเงิน

**📚 API Documentation แบบเต็ม:**
- **[📡 API_DOCUMENTATION.md](./API_DOCUMENTATION.md)** - เอกสาร 500+ บรรทัดพร้อมตัวอย่าง
- **[📋 OpenAPI Specification](./storage/api-docs/openapi.yaml)** - Import ใน Postman/Swagger UI

---

## 🔐 Security Features

- ✅ Password Hashing (bcrypt)
- ✅ CSRF Protection
- ✅ SQL Injection Protection (Eloquent ORM)
- ✅ XSS Protection (Blade escaping)
- ✅ Role-based Access Control (Spatie Permission)
- ✅ API Token Authentication (Sanctum)
- ✅ Rate Limiting (60 req/min)
- ✅ HTTPS Support (SSL)

---

## 📈 การทดสอบ

### Unit Tests (18 tests)

```bash
# รัน Unit Tests ทั้งหมด
php artisan test --testsuite=Unit

# รัน test เฉพาะ class
php artisan test --filter=MlmServiceTest
php artisan test --filter=WalletServiceTest
```

**Test Coverage:**
- ✅ MLM Service (8 tests) - registerUser, buildGenealogy, distributeCommissions, etc.
- ✅ Wallet Service (10 tests) - createWallet, credit, debit, withdrawal, etc.

### Feature Tests (15 tests)

```bash
# รัน Feature Tests ทั้งหมด
php artisan test --testsuite=Feature

# รัน test เฉพาะ class
php artisan test --filter=ProductTest
php artisan test --filter=AuthenticationTest
```

**Test Coverage:**
- ✅ Authentication (7 tests) - register, login, logout, referral code
- ✅ Products (8 tests) - listing, filters, search, cart operations

---

## 🤝 Contributing

ขอบคุณสำหรับการมีส่วนร่วม! กรุณา:

1. Fork โปรเจค
2. สร้าง Feature Branch (`git checkout -b feature/AmazingFeature`)
3. Commit การเปลี่ยนแปลง (`git commit -m 'Add some AmazingFeature'`)
4. Push ไปยัง Branch (`git push origin feature/AmazingFeature`)
5. เปิด Pull Request

**Code Style:**
- ใช้ PSR-12 coding standard
- รัน `php artisan pint` ก่อน commit (Laravel Pint)
- เขียน tests สำหรับ features ใหม่

---

## 📄 License

โปรเจคนี้เป็น open-source ภายใต้ [MIT license](LICENSE)

---

## 📞 ติดต่อ

- **GitHub:** [@xjanova](https://github.com/xjanova)
- **Repository:** [Thaiprompt-Affiliate](https://github.com/xjanova/Thaiprompt-Affiliate)
- **Email:** support@thaiprompt.com

---

## 🎯 Roadmap

### Phase 1 (✅ เสร็จสมบูรณ์)
- [x] ระบบพื้นฐาน Multi-vendor
- [x] ระบบ MLM (Unilevel/Binary)
- [x] ระบบ Wallet & Withdrawal
- [x] ระบบ Payment (Stripe, PromptPay)
- [x] ระบบ POS
- [x] **Blade Templates & Views** - UI ครบทุกหน้า (38 files)
- [x] **NFC Scanner** - สำหรับ Chrome Android
- [x] **Webhook System** - Stripe, PromptPay, LINE, GitHub
- [x] **Email Notifications** - 4 Mailable Classes
- [x] **Admin Dashboard Widgets** - Charts & Analytics
- [x] **Unit & Feature Tests** - 33 Test Cases
- [x] **API Documentation** - OpenAPI 3.0 + Markdown

### Phase 2 (✅ เสร็จแล้ว)
- [x] **Vendor Feature Manager** - ระบบจัดการฟีเจอร์สำหรับร้านค้า
- [x] **LINE OA KYC System** - ระบบยืนยันตัวตนผ่าน LINE Official Account
- [x] **Database Migration & Update System** - ระบบอัพเดทฐานข้อมูลแบบมืออาชีพ
- [x] **System Update Notifications** - แจ้งเตือนอัพเดทระบบอัตโนมัติ

### Phase 3 (🔜 อนาคต)
- [ ] Mobile App (React Native/Flutter)
- [ ] Advanced Analytics Dashboard
- [ ] AI-powered Product Recommendations
- [ ] Multi-language Support (EN, CN, JP)
- [ ] Advanced SEO Tools
- [ ] Social Media Integration (Facebook, Instagram)
- [ ] Live Chat Support
- [ ] Advanced Reporting & Export
- [ ] Vendor Mobile App
- [ ] Customer Loyalty Program
- [ ] Blockchain Integration
- [ ] NFT Marketplace
- [ ] Cryptocurrency Payment

---

### 📖 คู่มือระบบใหม่ (Phase 2)

- 🚀 [**Feature Manager Guide**](docs/FEATURE_MANAGER_GUIDE.md) - คู่มือระบบจัดการฟีเจอร์สำหรับร้านค้าอย่างละเอียด
- 📱 [**LINE OA Setup Guide**](docs/LINE_OA_SETUP_GUIDE.md) - คู่มือการตั้งค่า LINE Official Account แบบ Step-by-Step พร้อมภาพประกอบ
- 🔧 [**System Update Guide**](docs/SYSTEM_UPDATE_GUIDE.md) - คู่มือการอัพเดทระบบและฐานข้อมูลอย่างปลอดภัย

---

## 🎉 ฟีเจอร์เด่นใน v1.1.0

### 🎨 Theme Customization - ปรับแต่งธีมของคุณ
ระบบปรับแต่งสีธีมและรูปลักษณ์แบบครบวงจร
- เปลี่ยนสีหลัก, สีรอง, สีเน้นได้ตามใจชอบ
- รองรับ Gradient Colors แบบมืออาชีพ
- อัปโหลดโลโก้และ Favicon ของคุณเอง
- ระบบ Premium Subscription แบบรายเดือน/ปี/ตลอดชีพ

### 🔧 Web Setup Wizard - ติดตั้งง่ายผ่านเว็บ
ติดตั้งระบบได้ง่ายๆ ผ่านหน้าเว็บ ไม่ต้องใช้ Command Line
- ตรวจสอบความพร้อมของระบบอัตโนมัติ
- ตั้งค่า Database และทดสอบการเชื่อมต่อ
- สร้างบัญชี Admin ในขั้นตอนการติดตั้ง
- UI สวยงามพร้อม Animations

### 💾 Backup & Version Control - ปลอดภัยทุกการอัพเดท
ระบบสำรองข้อมูลอัตโนมัติก่อนทำการอัพเดท
- Full System Backup (ไฟล์ + ฐานข้อมูล)
- Database Backup
- Backup Management และกู้คืนข้อมูลทันที
- Version Checking ก่อนอัพเดท

### 💳 NFC Payment - ชำระเงินแบบไร้สัมผัส
ระบบชำระเงินผ่าน NFC ที่ทันสมัย รองรับ Web NFC API สำหรับอุปกรณ์ที่รองรับ (Chrome บน Android)
- สแกนบัตรผ่านเว็บได้เลย ไม่ต้องติดตั้งแอป
- ผูกบัตรกับ Wallet ของ User
- ใช้งานผ่าน POS ได้
- เตรียมพร้อมสำหรับ Mobile App

### ✅ Shop Verification - ยืนยันตัวตนร้านค้า
ระบบยืนยันตัวตนแบบหลายระดับ เพิ่มความน่าเชื่อถือให้ร้านค้า
- 🥉 **Bronze**: ยืนยันบัตรประชาชน
- 🥈 **Silver**: + ทะเบียนการค้า + ภ.พ.20
- 🥇 **Gold**: + การยืนยันบัญชีธนาคาร
- 💎 **Platinum**: + ใบอนุญาตประกอบการ

### 🔄 Auto Update - อัปเดทอัตโนมัติ
เช็คเวอร์ชันจาก GitHub อัตโนมัติ แจ้งเตือนเมื่อมีอัปเดทใหม่
- ตรวจสอบอัปเดทอัตโนมัติทุก 24 ชั่วโมง
- แสดง Changelog และ Release Notes
- คำแนะนำการอัปเดทแบบละเอียด

### 🌳 MLM Tree - ผังโครงสร้างแบบ Interactive
ผังองค์กร MLM แบบ Interactive ด้วย D3.js
- Zoom และ Pan ได้อย่างลื่นไหล
- แสดงสถานะสมาชิกแบบ Real-time
- กรอบสีแดงสำหรับสมาชิกที่ยังไม่รักษายอด
- กรอบสีเขียวสำหรับสมาชิกที่ KYC แล้ว
- เพิ่มสมาชิกได้จากผังเลย

### 📸 Profile Management - จัดการรูปโปรไฟล์
- รูป Avatar เริ่มต้นสำหรับผู้ใช้ใหม่
- ดึงรูปจาก LINE หลัง KYC
- อัปโหลดรูปโปรไฟล์เองได้
- ปรับขนาดรูปอัตโนมัติ

### 🔧 Browser Support สำหรับ NFC
- ✅ Chrome 89+ (Android)
- ✅ Chrome 114+ (Desktop - ต้องเปิด flag)
- ✅ Edge 89+ (Android)
- ❌ Safari (ยังไม่รองรับ)
- ❌ Firefox (ยังไม่รองรับ)

---

## 📚 เอกสารประกอบ

### 📘 สำหรับผู้ติดตั้งและใช้งาน

- **[📘 INSTALLATION_GUIDE.md](./INSTALLATION_GUIDE.md)** - คู่มือการติดตั้งแบบละเอียดทุกขั้นตอน
  - ✅ ติดตั้งบน Windows, macOS, Linux (Ubuntu/Debian)
  - ✅ การแก้ไขปัญหาที่พบบ่อย (10+ ปัญหา)
  - ✅ Checklist การติดตั้งสำเร็จ
  - ✅ การตั้งค่าเพิ่มเติม (Queue, Scheduler, Webhooks)

- **[⚙️ CONFIGURATION.md](./CONFIGURATION.md)** - คู่มือการตั้งค่าระบบ
  - ✅ Environment Variables ทั้งหมด
  - ✅ Database, Email, Payment Gateway
  - ✅ MLM Configuration (Rates, Ranks, Structure)
  - ✅ LINE Official Account Setup
  - ✅ NFC Configuration
  - ✅ File Storage (Local, S3, Spaces)
  - ✅ Cache & Queue (Redis, Database)
  - ✅ Security Settings

- **[🚀 DEPLOYMENT.md](./DEPLOYMENT.md)** - คู่มือการ Deploy Production
  - ✅ ตั้งค่า Production Server (Ubuntu)
  - ✅ Nginx + PHP-FPM Configuration
  - ✅ SSL Certificate (Let's Encrypt)
  - ✅ Performance Optimization
  - ✅ Monitoring & Logging
  - ✅ Backup Strategy
  - ✅ CI/CD Pipeline (GitHub Actions)
  - ✅ Troubleshooting Guide

### 📡 สำหรับนักพัฒนา

- **[📡 API_DOCUMENTATION.md](./API_DOCUMENTATION.md)** - API Documentation แบบละเอียด
  - ✅ 50+ API Endpoints
  - ✅ Authentication & Authorization
  - ✅ Request/Response Examples
  - ✅ Rate Limiting & Error Handling
  - ✅ cURL & Postman Examples
  - ✅ Webhook Documentation

- **[📋 OpenAPI Specification](./storage/api-docs/openapi.yaml)** - OpenAPI 3.0 Spec
  - ✅ Import ใน Postman, Swagger UI, Insomnia
  - ✅ Auto-generated Client SDKs
  - ✅ Schema Definitions
  - ✅ Complete API Reference

### 🔍 เอกสารเพิ่มเติม

- **[📖 README.md](./README.md)** - Overview และ Quick Start (ไฟล์นี้)
- **[🧪 Tests](./tests/)** - Unit Tests & Feature Tests (33 tests)
- **[📝 CHANGELOG.md](./CHANGELOG.md)** - ประวัติการอัพเดท *(Coming Soon)*

---

## 📊 สถิติโปรเจค

- **📁 Total Files:** 200+ files
- **💻 Lines of Code:** 15,000+ lines
- **🎨 Blade Views:** 38 templates
- **🧪 Test Cases:** 33 tests
- **📡 API Endpoints:** 50+ endpoints
- **📊 Database Tables:** 28 tables
- **📚 Documentation:** 4 comprehensive guides

---

## 🙏 Credits & Acknowledgments

**Built with:**
- [Laravel](https://laravel.com/) - The PHP Framework
- [Tailwind CSS](https://tailwindcss.com/) - Utility-first CSS
- [Alpine.js](https://alpinejs.dev/) - Lightweight JavaScript
- [Chart.js](https://www.chartjs.org/) - Simple yet flexible JavaScript charting
- [Stripe](https://stripe.com/) - Payment processing
- [Spatie Laravel Permission](https://spatie.be/docs/laravel-permission/) - Role & Permission management

**Special Thanks:**
- Laravel Community
- ThaiPrompt Team
- All Contributors

---

<div align="center">

**Made with ❤️ by [ThaiPrompt Team](https://github.com/xjanova)**

[⭐ Star this repo](https://github.com/xjanova/Thaiprompt-Affiliate) | [🐛 Report Bug](https://github.com/xjanova/Thaiprompt-Affiliate/issues) | [💡 Request Feature](https://github.com/xjanova/Thaiprompt-Affiliate/issues)

</div>
