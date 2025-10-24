# Hotel Management & Store Theme System Documentation

## 🎉 ระบบใหม่ที่เพิ่มเข้ามา

### 1. ระบบ Add-on/Module Purchase System
ระบบซื้อฟีเจอร์เพิ่มเติมสำหรับร้านค้า - สามารถเพิ่มความสามารถให้กับร้านค้าได้ตามต้องการ

**คุณสมบัติ:**
- ✅ ซื้อ Add-on ด้วย Wallet, Stripe, หรือ PromptPay
- ✅ จัดการ Add-on ที่ซื้อแล้ว
- ✅ เปิด/ปิดการใช้งาน Add-on
- ✅ ติดตามสถานะและวันหมดอายุ

**Add-ons ที่มีให้บริการ:**
1. **Hotel Management System** (4,999 บาท)
2. **Store Theme Customization** (1,999 บาท)
3. **Advanced Analytics** (999 บาท)
4. **Email Marketing** (1,499 บาท)

---

### 2. ระบบจัดการโรงแรม/รีสอร์ท (Hotel Management System)

ระบบจัดการที่พักแบบครบวงจร เทียบเท่า Agoda สำหรับร้านค้าที่ต้องการขายห้องพัก

#### 🏨 คุณสมบัติหลัก:

**Hotel Management:**
- ✅ จัดการโรงแรม/รีสอร์ท/โฮสเทล/อพาร์ทเม้นท์ ได้ไม่จำกัด
- ✅ ระบุที่อยู่พร้อม GPS coordinates
- ✅ อัพโหลดรูปภาพและวิดีโอ
- ✅ ระดับดาว (1-5 ดาว)
- ✅ นโยบายการยกเลิก, กฎของที่พัก
- ✅ เวลา Check-in/Check-out

**Room Type Management:**
- ✅ สร้างประเภทห้องได้หลายแบบ (Standard, Deluxe, Suite, etc.)
- ✅ กำหนดขนาดห้อง, ประเภทเตียง
- ✅ จำนวนผู้เข้าพักสูงสุด
- ✅ ราคาห้องพักต่อคืน
- ✅ ราคาวันหยุดสุดสัปดาห์
- ✅ ค่าบริการเพิ่มเติม (เตียงเสริม, ผู้เข้าพักเพิ่ม)
- ✅ สิ่งอำนวยความสะดวกในห้อง

**Room Management:**
- ✅ จัดการห้องพักแต่ละห้อง (เลขห้อง, ชั้น)
- ✅ สถานะห้อง (พร้อมใช้งาน, มีผู้เข้าพัก, ปิดปรับปรุง, กำลังทำความสะอาด)
- ✅ Auto-assign ห้องให้กับการจอง
- ✅ Bulk create rooms (สร้างห้องจำนวนมากพร้อมกัน)

**Booking System:**
- ✅ ระบบจองห้องพักออนไลน์
- ✅ ตรวจสอบห้องว่างตามวันที่
- ✅ คำนวณราคาอัตโนมัติ (รวม VAT 7% + Service Fee 3%)
- ✅ จัดการข้อมูลผู้เข้าพัก
- ✅ Special requests
- ✅ Check-in/Check-out tracking
- ✅ เลขที่การจองอัตโนมัติ (HTL-XXXXXXX)
- ✅ Multiple payment methods
- ✅ Booking status (pending, confirmed, checked_in, checked_out, cancelled)

**Promotion & Discount System:**
- ✅ สร้างโปรโมชั่นได้ไม่จำกัด
- ✅ ส่วนลดแบบ % หรือจำนวนเงินคงที่
- ✅ โปรโมชั่นแบบ "Stay 3 Pay 2"
- ✅ กำหนดเงื่อนไขขั้นต่ำ (คืนขั้นต่ำ, จำนวนห้องขั้นต่ำ)
- ✅ จำกัดจำนวนการใช้งาน
- ✅ Blackout dates (วันที่ไม่สามารถใช้โปรโมชั่นได้)
- ✅ กำหนดวันที่ใช้ได้ (จันทร์-อาทิตย์)
- ✅ Public/Private promo codes
- ✅ รองรับการใช้หลายโปรโมชั่น

**Dynamic Pricing System:**
- ✅ ราคาตามฤดูกาล (High Season, Low Season)
- ✅ ราคาวันหยุดสุดสัปดาห์
- ✅ Event-based pricing (เทศกาล, ปีใหม่, สงกรานต์)
- ✅ Priority-based rules (กฎที่มี priority สูงกว่าจะมีผลก่อน)

**Review & Rating System:**
- ✅ รีวิวจากผู้ใช้งานจริง (Verified bookings)
- ✅ คะแนนแยกหมวด (ความสะอาด, บริการ, ทำเลที่ตั้ง, สิ่งอำนวยความสะดวก)
- ✅ Vendor สามารถตอบกลับรีวิว
- ✅ ระบบ moderation (อนุมัติก่อนแสดงผล)
- ✅ Helpful/Not helpful voting

**Reports & Analytics:**
- ✅ รายงานการจองทั้งหมด
- ✅ อัตราการเข้าพัก (Occupancy Rate)
- ✅ รายได้ต่อเดือน/ปี
- ✅ จำนวนผู้เข้าพักปัจจุบัน
- ✅ การจองที่กำลังจะมาถึง
- ✅ สถิติการยกเลิก

**Commission & MLM Integration:**
- ✅ แบ่งค้อมมิชชั่นให้ vendor (70/30 หรือกำหนดเอง)
- ✅ ผสานกับระบบ MLM
- ✅ แจกค้อมมิชชั่นให้ upline อัตโนมัติ
- ✅ ติดตามสถานะการจ่ายค้อมมิชชั่น

---

### 3. ระบบแต่งหน้าร้าน (Store Theme Customization)

ระบบปรับแต่งหน้าตาร้านค้าให้สวยงามและเข้ากับสินค้า

#### 🎨 คุณสมบัติหลัก:

**Theme Templates:**
- ✅ เลือกใช้ Theme สำเร็จรูปได้หลายแบบ
- ✅ Free & Premium themes
- ✅ Theme แบ่งตามประเภท (Fashion, Hotel, Electronics, Food, General)
- ✅ Responsive design (รองรับทุกอุปกรณ์)

**รายการ Themes:**
1. **Modern Minimalist** (ฟรี) - ดีไซน์เรียบง่าย เน้นสินค้า
2. **Fashion Boutique** (999 บาท) - สำหรับร้านแฟชั่น
3. **Hotel Paradise** (1,499 บาท) - สำหรับโรงแรม/รีสอร์ท
4. **Electronics Hub** (799 บาท) - สำหรับร้านอิเล็กทรอนิกส์
5. **Food & Restaurant** (ฟรี) - สำหรับร้านอาหาร

**Color Customization:**
- ✅ สีหลัก (Primary Color)
- ✅ สีรอง (Secondary Color)
- ✅ สีเน้น (Accent Color)
- ✅ สีข้อความ
- ✅ สีพื้นหลัง

**Typography:**
- ✅ เลือกฟอนต์หัวข้อ
- ✅ เลือกฟอนต์เนื้อหา
- ✅ ปรับขนาดตัวอักษร (12-24px)

**Layout Options:**
- ✅ แบบ Grid / List / Masonry
- ✅ จำนวนสินค้าต่อแถว (2-6 รายการ)
- ✅ แสดง/ซ่อน Sidebar
- ✅ รูปแบบ Header (Default, Centered, Minimal)

**Hero Banner:**
- ✅ อัพโหลดรูป Banner ขนาดใหญ่
- ✅ กำหนดหัวข้อและคำบรรยาย
- ✅ ปุ่ม Call-to-Action พร้อมลิงก์

**Promotional Banners:**
- ✅ สร้าง Banner โปรโมชั่นได้หลายแบบ
- ✅ กำหนดตำแหน่งการแสดงผล

**Branding:**
- ✅ อัพโหลดโลโก้
- ✅ อัพโหลด Favicon
- ✅ โลโก้สำหรับมือถือแยกต่างหาก

**Social Media Integration:**
- ✅ ลิงก์ Facebook
- ✅ ลิงก์ Instagram
- ✅ ลิงก์ Twitter
- ✅ ลิงก์ LINE Official
- ✅ ลิงก์ YouTube

**Contact Widget:**
- ✅ แสดง Contact Widget
- ✅ เลือกตำแหน่ง (มุมขวาล่าง, มุมซ้ายล่าง, etc.)

**Custom Code:**
- ✅ เพิ่ม Custom CSS
- ✅ เพิ่ม Custom JavaScript
- ✅ Full control สำหรับผู้ใช้ขั้นสูง

**Footer Customization:**
- ✅ เพิ่มข้อมูลเกี่ยวกับร้าน
- ✅ เมนูลิงก์ใน Footer
- ✅ ลิงก์ Social Media

---

## 📊 Database Schema

### New Tables Created:

1. **addons** - ข้อมูล Add-on ทั้งหมด
2. **addon_purchases** - บันทึกการซื้อ Add-on
3. **hotels** - ข้อมูลโรงแรม/รีสอร์ท
4. **room_types** - ประเภทห้องพัก
5. **rooms** - ห้องพักแต่ละห้อง
6. **hotel_bookings** - การจองห้องพัก
7. **booking_guests** - ข้อมูลผู้เข้าพัก
8. **hotel_promotions** - โปรโมชั่นสำหรับโรงแรม
9. **hotel_promotion_usage** - บันทึกการใช้โปรโมชั่น
10. **room_pricing_rules** - กฎการกำหนดราคาแบบ dynamic
11. **hotel_amenities** - สิ่งอำนวยความสะดวก
12. **hotel_reviews** - รีวิวโรงแรม
13. **store_themes** - Theme templates
14. **vendor_themes** - การปรับแต่ง Theme ของแต่ละร้าน

---

## 🔧 API Endpoints

### Public Endpoints (ไม่ต้อง Login)

**Hotels:**
```
GET  /api/v1/hotels                      - ค้นหาโรงแรมทั้งหมด
GET  /api/v1/hotels/{id}                 - ดูรายละเอียดโรงแรม
GET  /api/v1/hotels/{id}/availability    - ตรวจสอบห้องว่าง
```

**Themes:**
```
GET  /api/v1/themes                      - Theme ทั้งหมด
GET  /api/v1/themes/featured             - Theme แนะนำ
GET  /api/v1/themes/free                 - Theme ฟรี
GET  /api/v1/themes/premium              - Theme พรีเมี่ยม
GET  /api/v1/themes/category/{category}  - Theme ตามหมวดหมู่
GET  /api/v1/themes/{id}                 - รายละเอียด Theme
GET  /api/v1/vendor/{vendorId}/theme     - Theme ของร้านค้า (สาธารณะ)
```

**Addons:**
```
GET  /api/v1/addons                      - Add-on ทั้งหมด
GET  /api/v1/addons/{id}                 - รายละเอียด Add-on
```

### Protected Endpoints (ต้อง Login)

**Bookings:**
```
GET   /api/v1/bookings                      - การจองของฉัน
GET   /api/v1/bookings/{id}                 - รายละเอียดการจอง
POST  /api/v1/bookings                      - สร้างการจองใหม่
POST  /api/v1/bookings/calculate-price      - คำนวณราคา
POST  /api/v1/bookings/{id}/payment         - ชำระเงิน
POST  /api/v1/bookings/{id}/cancel          - ยกเลิกการจอง
```

**Addons:**
```
GET   /api/v1/my-addons                     - Add-on ที่ซื้อแล้ว
GET   /api/v1/addons/available              - Add-on ที่ยังไม่ได้ซื้อ
POST  /api/v1/addons/purchase               - ซื้อ Add-on
POST  /api/v1/addons/{purchaseId}/deactivate - ปิดการใช้งาน
```

**Themes:**
```
GET   /api/v1/my-theme                      - Theme ปัจจุบันของร้าน
POST  /api/v1/theme/apply                   - ใช้ Theme template
POST  /api/v1/theme/customize               - ปรับแต่ง Theme
POST  /api/v1/theme/reset                   - รีเซ็ตเป็นค่าเริ่มต้น
```

### Vendor Endpoints (เฉพาะ Vendor)

**Hotels:**
```
GET    /api/v1/vendor/hotels                - โรงแรมของฉัน
POST   /api/v1/vendor/hotels                - สร้างโรงแรมใหม่
PUT    /api/v1/vendor/hotels/{id}           - แก้ไขโรงแรม
DELETE /api/v1/vendor/hotels/{id}           - ลบโรงแรม
GET    /api/v1/vendor/hotels/{id}/stats     - สถิติโรงแรม
```

**Bookings:**
```
GET   /api/v1/vendor/bookings               - การจองทั้งหมด
GET   /api/v1/vendor/bookings/stats         - สถิติการจอง
POST  /api/v1/vendor/bookings/{id}/check-in - Check-in
POST  /api/v1/vendor/bookings/{id}/check-out - Check-out
```

### Admin Endpoints (เฉพาะ Admin)

**Addons:**
```
POST  /api/v1/admin/addons                  - สร้าง Add-on ใหม่
PUT   /api/v1/admin/addons/{id}             - แก้ไข Add-on
GET   /api/v1/admin/addons/purchases        - ดูการซื้อทั้งหมด
```

**Themes:**
```
POST  /api/v1/admin/themes                  - สร้าง Theme ใหม่
PUT   /api/v1/admin/themes/{id}             - แก้ไข Theme
```

---

## 💼 Business Logic Services

### 1. HotelService
- `createHotel()` - สร้างโรงแรม
- `updateHotel()` - แก้ไขข้อมูล
- `createRoomType()` - สร้างประเภทห้อง
- `createRooms()` - สร้างห้องพัก
- `bulkCreateRooms()` - สร้างห้องจำนวนมาก
- `getAvailability()` - ตรวจสอบห้องว่าง
- `calculatePriceRange()` - คำนวณราคา
- `getHotelStats()` - สถิติโรงแรม
- `searchHotels()` - ค้นหาโรงแรม

### 2. BookingService
- `createBooking()` - สร้างการจอง
- `calculateBookingPrice()` - คำนวณราคารวมโปรโมชั่น
- `processPayment()` - ชำระเงิน
- `calculateCommissions()` - คำนวณค้อมมิชชั่น
- `cancelBooking()` - ยกเลิกการจอง
- `processRefund()` - คืนเงิน
- `checkIn()` - Check-in
- `checkOut()` - Check-out
- `getVendorBookingStats()` - สถิติการจอง

### 3. AddonService
- `purchaseAddon()` - ซื้อ Add-on
- `processWalletPayment()` - ชำระเงินผ่าน Wallet
- `activateAddonFeatures()` - เปิดใช้งานฟีเจอร์
- `createDefaultVendorTheme()` - สร้าง Theme เริ่มต้น
- `applyStoreTheme()` - ใช้ Theme template
- `customizeVendorTheme()` - ปรับแต่ง Theme
- `deactivateAddon()` - ปิดการใช้งาน
- `getAvailableAddons()` - Add-on ที่พร้อมใช้
- `getAddonStats()` - สถิติ Add-on

---

## 🚀 การติดตั้งและใช้งาน

### 1. Run Migrations
```bash
php artisan migrate
```

### 2. Run Seeders
```bash
php artisan db:seed
```

### 3. สร้าง Symbolic Link สำหรับไฟล์ (ถ้ายังไม่ได้ทำ)
```bash
php artisan storage:link
```

---

## 📝 ตัวอย่างการใช้งาน

### Vendor ซื้อ Hotel Management Addon
```php
POST /api/v1/addons/purchase
{
  "addon_id": 1,
  "payment_method": "wallet"
}
```

### Vendor สร้างโรงแรม
```php
POST /api/v1/vendor/hotels
{
  "name": "Sunset Resort & Spa",
  "type": "resort",
  "star_rating": 5,
  "city": "Phuket",
  "address": "123 Beach Road",
  ...
}
```

### สร้างประเภทห้องพัก
```php
POST /api/v1/vendor/room-types
{
  "hotel_id": 1,
  "name": "Deluxe Sea View",
  "base_price": 3500,
  "weekend_price": 4500,
  "max_adults": 2,
  "max_children": 1,
  ...
}
```

### ลูกค้าจองห้อง
```php
POST /api/v1/bookings
{
  "hotel_id": 1,
  "room_type_id": 1,
  "check_in_date": "2024-06-15",
  "check_out_date": "2024-06-18",
  "adults": 2,
  "rooms_count": 1,
  "guest_name": "John Doe",
  "guest_email": "john@example.com",
  "guest_phone": "0812345678",
  "promo_code": "SUMMER2024",
  "payment_method": "wallet"
}
```

### Vendor ปรับแต่ง Theme
```php
POST /api/v1/theme/customize
{
  "primary_color": "#FF6B6B",
  "secondary_color": "#4ECDC4",
  "heading_font": "Kanit",
  "body_font": "Sarabun",
  "hero_title": "ยินดีต้อนรับสู่ร้านของเรา",
  "hero_banner": "/uploads/hero-banner.jpg",
  ...
}
```

---

## 🎯 Features Checklist

### Hotel Management ✅
- [x] Multi-property management
- [x] Room type & room management
- [x] Online booking system
- [x] Dynamic pricing rules
- [x] Promotion system
- [x] Check-in/check-out tracking
- [x] Guest management
- [x] Review & rating system
- [x] Revenue tracking
- [x] MLM commission integration

### Store Theme ✅
- [x] Theme templates
- [x] Color customization
- [x] Typography settings
- [x] Layout options
- [x] Banner management
- [x] Branding (logo, favicon)
- [x] Social media links
- [x] Custom CSS/JS
- [x] Responsive design

### Addon System ✅
- [x] Addon marketplace
- [x] Purchase system
- [x] Wallet integration
- [x] Activation/deactivation
- [x] Feature management

---

## 📞 Support

หากมีคำถามหรือต้องการความช่วยเหลือ กรุณาติดต่อ:
- Email: support@thaiprompt.com
- LINE: @thaiprompt
- Tel: 02-XXX-XXXX

---

สร้างโดย **Thaiprompt Affiliate Team** 🇹🇭
