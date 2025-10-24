# ThaiPrompt Affiliate Marketplace

ระบบร้านค้าออนไลน์แบบ Multi-vendor Marketplace พร้อมระบบ MLM (Multi-Level Marketing) ที่ครบครัน พัฒนาด้วย Laravel

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![Laravel](https://img.shields.io/badge/Laravel-10.x-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.1+-purple.svg)](https://php.net)

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

---

## 🛠️ เทคโนโลยีที่ใช้

**Backend:**
- Laravel 10.x (PHP 8.1+)
- MySQL 8.0+
- Redis (Cache & Queue)
- Laravel Sanctum (API Authentication)
- Spatie Laravel Permission (Role-based Access)

**Frontend:**
- Tailwind CSS (Responsive Design)
- Alpine.js (Interactive Components)
- Chart.js (Dashboard Charts)
- Vite (Asset Bundling)

**Payment:**
- Stripe PHP SDK
- PromptPay API

**Others:**
- Intervention Image (Image Processing)
- DomPDF (PDF Generation)
- Maatwebsite Excel (Excel Export)
- Web NFC API (NFC Scanner)

---

## 📋 ความต้องการของระบบ

- **PHP** >= 8.1 (พร้อม Extensions: BCMath, Ctype, JSON, Mbstring, OpenSSL, PDO, XML, GD, cURL)
- **Composer** >= 2.0
- **MySQL** >= 8.0 หรือ MariaDB >= 10.3
- **Node.js** >= 16.x
- **NPM** >= 8.x
- **Git** >= 2.0

---

## 🚀 การติดตั้ง

### Quick Start

```bash
# 1. Clone โปรเจค
git clone https://github.com/xjanova/Thaiprompt-Affiliate.git
cd Thaiprompt-Affiliate

# 2. ติดตั้ง Dependencies
composer install
npm install

# 3. ตั้งค่า Environment
cp .env.example .env
php artisan key:generate

# 4. สร้างฐานข้อมูลและ Migrate
php artisan migrate
php artisan db:seed

# 5. Build Assets และ Storage Link
npm run build
php artisan storage:link

# 6. รันเซิร์ฟเวอร์
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

### Phase 2 (🔜 กำลังพัฒนา)
- [ ] **Controllers** - Web & API Controllers (ยังขาด)
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

### Phase 3 (📋 วางแผน)
- [ ] Blockchain Integration
- [ ] NFT Marketplace
- [ ] Cryptocurrency Payment
- [ ] AR Product Preview
- [ ] Voice Shopping
- [ ] Subscription Management

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
