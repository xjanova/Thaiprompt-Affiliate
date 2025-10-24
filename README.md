# ThaiPrompt Affiliate Marketplace

ระบบร้านค้าออนไลน์แบบ Multi-vendor Marketplace พร้อมระบบ MLM (Multi-Level Marketing) ที่ครบครัน พัฒนาด้วย Laravel

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

### 8. ระบบการตลาด
- ✅ **Line OA Integration** - เชิญเพื่อนผ่าน Line Official Account
- ✅ ระบบ Referral Link ส่วนตัว
- ✅ ติดตามสถานะการเชิญ
- ✅ Coupon/Discount Code
- ✅ ระบบ Invitation แบบหลายช่องทาง

### 9. ระบบสินค้า
- ✅ จัดการสินค้าแบบครบวงจร
- ✅ หลายหมวดหมู่ (Multi-category)
- ✅ จัดการ Stock/Inventory
- ✅ รูปภาพสินค้าหลายรูป
- ✅ Variations (สี, ไซส์, etc.)
- ✅ SEO-friendly

### 10. ระบบรีวิว
- ✅ รีวิวและให้คะแนนสินค้า
- ✅ Verified Purchase
- ✅ ผู้ขายตอบกลับรีวิวได้
- ✅ อัปโหลดรูปภาพ

### 11. Admin Dashboard
- ✅ ภาพรวมระบบทั้งหมด
- ✅ จัดการผู้ใช้งาน
- ✅ อนุมัติ/ปฏิเสธร้านค้า
- ✅ จัดการ Commission และ Withdrawal
- ✅ รายงานยอดขายแบบละเอียด
- ✅ ตั้งค่าระบบ

## 🛠️ เทคโนโลยีที่ใช้

- **Backend**: Laravel 10.x (PHP 8.1+)
- **Database**: MySQL 8.0+
- **Authentication**: Laravel Sanctum
- **Permissions**: Spatie Laravel Permission
- **Image Processing**: Intervention Image
- **PDF Generation**: DomPDF
- **Excel Export**: Maatwebsite Excel
- **Payment**: Stripe PHP SDK

## 📋 ความต้องการของระบบ

- PHP >= 8.1
- Composer
- MySQL >= 8.0
- Node.js >= 16.x
- NPM/Yarn

## 🚀 การติดตั้ง

### 1. Clone โปรเจค

```bash
git clone https://github.com/xjanova/Thaiprompt-Affiliate.git
cd Thaiprompt-Affiliate
```

### 2. ติดตั้ง Dependencies

```bash
composer install
npm install
```

### 3. ตั้งค่า Environment

```bash
cp .env.example .env
php artisan key:generate
```

### 4. แก้ไขไฟล์ .env

```env
# Database
DB_DATABASE=thaiprompt_marketplace
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Stripe
STRIPE_KEY=your_stripe_key
STRIPE_SECRET=your_stripe_secret

# PromptPay
PROMPTPAY_MERCHANT_ID=your_merchant_id

# Line OA
LINE_CHANNEL_ID=your_line_channel_id
LINE_CHANNEL_SECRET=your_line_channel_secret
LINE_CHANNEL_ACCESS_TOKEN=your_line_access_token

# MLM Settings
MLM_TYPE=unilevel
MLM_MAX_DEPTH=10
COMMISSION_RATE_LEVEL_1=10
COMMISSION_RATE_LEVEL_2=5
COMMISSION_RATE_LEVEL_3=3
```

### 5. สร้างฐานข้อมูล

```bash
php artisan migrate
php artisan db:seed
```

### 6. สร้าง Storage Link

```bash
php artisan storage:link
```

### 7. Build Assets

```bash
npm run build
```

### 8. รันเซิร์ฟเวอร์

```bash
php artisan serve
```

เปิดเบราว์เซอร์ที่ `http://localhost:8000`

## 👤 บัญชีผู้ใช้เริ่มต้น

หลังจากรัน Seeder แล้ว คุณจะได้บัญชีเริ่มต้น:

**Admin:**
- Email: admin@example.com
- Password: password

**Vendor:**
- Email: vendor@example.com
- Password: password

**Customer:**
- Email: customer@example.com
- Password: password

## 📊 โครงสร้างฐานข้อมูล

### ตารางหลัก

1. **users** - ผู้ใช้งานทั้งหมด
2. **vendors** - ข้อมูลร้านค้า
3. **products** - สินค้า
4. **categories** - หมวดหมู่สินค้า
5. **orders** - คำสั่งซื้อ
6. **order_items** - รายการสินค้าในคำสั่งซื้อ

### ตารางระบบ MLM

7. **mlm_networks** - โครงสร้าง MLM Network
8. **mlm_ranks** - ระดับยศ
9. **user_ranks** - ยศของผู้ใช้
10. **mlm_genealogy** - Genealogy Tree
11. **commissions** - ค่าคอมมิชชั่น
12. **commission_settings** - ตั้งค่าค่าคอมมิชชั่น
13. **bonuses** - โบนัส

### ตารางระบบ Wallet

14. **wallets** - กระเป๋าเงิน
15. **wallet_transactions** - รายการทำธุรกรรม
16. **withdrawals** - การถอนเงิน

### ตารางอื่นๆ

17. **carts** - ตะกร้าสินค้า
18. **cart_items** - รายการในตะกร้า
19. **reviews** - รีวิวสินค้า
20. **invitations** - การเชิญ
21. **coupons** - คูปองส่วนลด
22. **pos_sessions** - Session POS
23. **pos_sales** - การขาย POS
24. **line_messages** - ข้อความ Line

## 🔧 คำสั่ง Artisan ที่มีประโยชน์

```bash
# สร้าง Admin User
php artisan make:admin

# คำนวณ Commission
php artisan commissions:process

# อัปเดต Ranks
php artisan ranks:update

# Process Withdrawals
php artisan withdrawals:process

# ล้างข้อมูล Cache
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

## 📱 API Documentation

API Endpoints มีอยู่ที่ `/api/v1/`

### Authentication
- POST `/api/v1/register` - สมัครสมาชิก
- POST `/api/v1/login` - เข้าสู่ระบบ
- POST `/api/v1/logout` - ออกจากระบบ

### Products
- GET `/api/v1/products` - รายการสินค้าทั้งหมด
- GET `/api/v1/products/{id}` - รายละเอียดสินค้า

### Cart
- GET `/api/v1/cart` - ดูตะกร้า
- POST `/api/v1/cart/add` - เพิ่มสินค้า
- PUT `/api/v1/cart/{item}` - แก้ไข
- DELETE `/api/v1/cart/{item}` - ลบ

### MLM
- GET `/api/v1/mlm/stats` - สถิติ MLM
- GET `/api/v1/mlm/network` - โครงข่าย
- GET `/api/v1/mlm/commissions` - ค่าคอมมิชชั่น

### Wallet
- GET `/api/v1/wallet` - ข้อมูล Wallet
- GET `/api/v1/wallet/transactions` - ประวัติธุรกรรม
- POST `/api/v1/wallet/withdraw` - ถอนเงิน

## 🔐 Security Features

- ✅ Password Hashing (bcrypt)
- ✅ CSRF Protection
- ✅ SQL Injection Protection
- ✅ XSS Protection
- ✅ Role-based Access Control
- ✅ API Token Authentication

## 📈 การทดสอบ

```bash
# Run Tests
php artisan test

# Run Specific Test
php artisan test --filter MlmServiceTest
```

## 🤝 Contributing

ขอบคุณสำหรับการมีส่วนร่วม! กรุณา:
1. Fork โปรเจค
2. สร้าง Feature Branch (`git checkout -b feature/AmazingFeature`)
3. Commit การเปลี่ยนแปลง (`git commit -m 'Add some AmazingFeature'`)
4. Push ไปยัง Branch (`git push origin feature/AmazingFeature`)
5. เปิด Pull Request

## 📄 License

โปรเจคนี้เป็น open-source ภายใต้ [MIT license](LICENSE)

## 📞 ติดต่อ

- GitHub: [@xjanova](https://github.com/xjanova)
- Repository: [Thaiprompt-Affiliate](https://github.com/xjanova/Thaiprompt-Affiliate)

## 🎯 Roadmap

### Phase 1 (ปัจจุบัน)
- [x] ระบบพื้นฐาน Multi-vendor
- [x] ระบบ MLM
- [x] ระบบ Wallet
- [x] ระบบ Payment
- [x] ระบบ POS

### Phase 2 (อนาคต)
- [ ] Mobile App (React Native)
- [ ] Advanced Analytics
- [ ] AI-powered Product Recommendations
- [ ] Multi-language Support
- [ ] Advanced SEO Tools
- [ ] Social Media Integration
- [ ] Live Chat Support
- [ ] Advanced Reporting

## 📚 เอกสารเพิ่มเติม

สำหรับข้อมูลเพิ่มเติม กรุณาดูที่:
- [Installation Guide](docs/installation.md)
- [User Guide](docs/user-guide.md)
- [Developer Guide](docs/developer-guide.md)
- [API Documentation](docs/api.md)

---

Made with ❤️ by [ThaiPrompt Team](https://github.com/xjanova)
