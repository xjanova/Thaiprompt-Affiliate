# ฟีเจอร์ใหม่ที่เพิ่มเข้ามา - Phase 2

## 🎨 1. ระบบ Theme Customization (Premium Feature)

### คุณสมบัติหลัก
ระบบปรับแต่งธีมที่ให้ร้านค้าสามารถสร้างเอกลักษณ์เฉพาะตัวได้

#### 1.1 การปรับแต่งสี
- **Primary Color** - สีหลักของธีม
- **Secondary Color** - สีรอง
- **Accent Color** - สีเน้น
- **Gradient Colors** - ไล่เฉดสีแบบ Professional (6 แบบสำเร็จรูป)

#### 1.2 Color Presets
ชุดสีสำเร็จรูปที่คัดสรรมาแล้ว:
- **Default** - สีน้ำเงิน-ชมพู (Indigo-Pink)
- **Ocean** - สีน้ำทะเล (Blue-Cyan-Teal)
- **Sunset** - สีพระอาทิตย์ตก (Orange-Red-Pink)
- **Forest** - สีป่าเขียว (Green-Lime)
- **Royal** - สีม่วงเข้ม (Purple)

#### 1.3 การปรับแต่งโลโก้
- Upload โลโก้ของร้าน
- Upload Favicon
- Upload Banner

#### 1.4 Custom CSS
- เขียน CSS เพิ่มเติมสำหรับการปรับแต่งขั้นสูง
- รองรับ CSS Variables
- Preview แบบ Real-time

#### 1.5 Premium Subscription
- **Monthly** - 299 บาท/เดือน
- **Yearly** - 2,990 บาท/ปี (ประหยัด 17%)
- **Lifetime** - 9,990 บาท (จ่ายครั้งเดียว)

### API Endpoints
```
GET    /admin/theme                 - แสดงหน้าปรับแต่งธีม
POST   /admin/theme                 - บันทึกการตั้งค่าธีม
POST   /admin/theme/logo            - Upload โลโก้
POST   /admin/theme/favicon         - Upload favicon
POST   /admin/theme/subscribe       - สมัครสมาชิก Premium
POST   /admin/theme/preview         - Preview ธีม
```

---

## 🔧 2. ระบบ Setup Wizard

### คุณสมบัติหลัก
ระบบติดตั้งผ่านหน้าเว็บที่ใช้งานง่าย ไม่ต้องใช้ CLI

#### 2.1 ขั้นตอนการติดตั้ง

**Step 1: ตรวจสอบความพร้อม**
- ✅ PHP Version (>= 8.1)
- ✅ PHP Extensions (PDO, mbstring, OpenSSL, etc.)
- ✅ Directory Permissions (storage/, bootstrap/cache/, public/)

**Step 2: ตั้งค่าฐานข้อมูล**
- กรอกข้อมูล Database Connection
- ทดสอบการเชื่อมต่อ
- บันทึก Configuration ลง .env

**Step 3: ติดตั้งฐานข้อมูล**
- Generate Application Key
- Run Migrations
- Run Seeders
- Create Storage Link

**Step 4: สร้าง Admin Account**
- กรอกข้อมูล Super Admin
- สร้าง Admin Role
- Assign Permissions

**Step 5: เสร็จสิ้น**
- สร้าง Backup อัตโนมัติ
- บันทึก System Info
- Redirect ไปยัง Dashboard

#### 2.2 UI Features
- Beautiful Wizard Interface
- Progress Indicator
- Smooth Animations (GSAP)
- Responsive Design
- Error Handling & Validation

### API Endpoints
```
GET    /setup                       - หน้าแรก Setup Wizard
GET    /setup/requirements          - ตรวจสอบความพร้อม
POST   /setup/database/test         - ทดสอบ Database Connection
POST   /setup/database/save         - บันทึก Database Config
POST   /setup/migrate               - Run Migrations
POST   /setup/seed                  - Run Seeders
POST   /setup/admin/create          - สร้าง Admin Account
POST   /setup/complete              - เสร็จสิ้นการติดตั้ง
GET    /setup/progress              - ตรวจสอบความคืบหน้า
```

---

## 💾 3. ระบบ Backup & Version Control

### คุณสมบัติหลัก

#### 3.1 ประเภท Backup
- **Full Backup** - สำรองทั้งไฟล์และฐานข้อมูล
- **Database Backup** - สำรองเฉพาะฐานข้อมูล

#### 3.2 Auto Backup
- สำรองข้อมูลอัตโนมัติก่อนอัพเดท
- Schedule Backup ตามเวลาที่กำหนด
- เก็บประวัติ Backup พร้อมข้อมูล:
  - Version ของระบบ
  - ขนาดไฟล์
  - เวลาที่สำรอง
  - ผู้สร้าง Backup

#### 3.3 Backup Management
- ดูรายการ Backup ทั้งหมด
- Download Backup File
- Restore จาก Backup
- ลบ Backup เก่า (Auto/Manual)

#### 3.4 Version Checking
- ตรวจสอบเวอร์ชั่นปัจจุบัน
- เปรียบเทียบกับเวอร์ชั่นใหม่
- แสดง Change Log
- แจ้งเตือนเมื่อมีเวอร์ชั่นใหม่

### API Endpoints
```
GET    /admin/backups               - แสดงรายการ Backup
POST   /admin/backups               - สร้าง Backup ใหม่
GET    /admin/backups/{id}/download - Download Backup
POST   /admin/backups/{id}/restore  - Restore จาก Backup
DELETE /admin/backups/{id}          - ลบ Backup
POST   /admin/backups/clean         - ล้าง Backup เก่า
```

---

## 📊 4. Super Admin Dashboard (Enhanced)

### คุณสมบัติหลัก

#### 4.1 Statistics Overview
- Total Users
- Total Vendors (Active/Pending)
- Total Products
- Total Orders
- Total Revenue (All-time/Monthly)
- Total Commissions (Paid/Pending)
- MLM Members

#### 4.2 Real-time Charts (Chart.js + GSAP)

**Sales Chart**
- กราฟยอดขาย 12 เดือนล่าสุด
- แสดงทั้งยอดขายและจำนวน Order
- Animation เมื่อโหลดข้อมูล

**Users Growth**
- กราฟจำนวนผู้ใช้ใหม่รายเดือน
- Trend Analysis

**Vendors by Status**
- Pie Chart แสดงสถานะร้านค้า
- Active, Pending, Rejected

**Commission by Type**
- Bar Chart แสดงค่าคอมมิชชั่นแยกตามประเภท
- Level Commission, Rank Bonus, Performance Bonus

**Top Products**
- ตารางสินค้าขายดี Top 10
- จำนวนขาย
- ยอดรวม

#### 4.3 Recent Activities
- Recent Orders (10 รายการล่าสุด)
- Recent Users (10 คนล่าสุด)
- Top Vendors (10 ร้านค้า)

#### 4.4 GSAP Animations
- Smooth scroll animations
- Counter animations สำหรับตัวเลข
- Chart entrance animations
- Card hover effects

### API Endpoints
```
GET    /admin/dashboard             - Dashboard หลัก
GET    /admin/dashboard/charts      - ข้อมูลกราฟ (JSON)
```

---

## ⚙️ 5. ระบบตั้งค่าแบบรวมศูนย์

### คุณสมบัติหลัก

#### 5.1 App Settings Groups
- **General** - ตั้งค่าทั่วไป
  - App Name
  - App Logo
  - Default Currency

- **Vendor** - ตั้งค่าร้านค้า
  - Enable Vendor Registration
  - Vendor Commission Rate

- **MLM** - ตั้งค่า MLM
  - MLM Type (Unilevel/Binary)
  - Max Depth
  - Commission Rates

- **Payment** - ตั้งค่าการชำระเงิน
  - Stripe Settings
  - PromptPay Settings

- **System** - ตั้งค่าระบบ
  - Maintenance Mode
  - Debug Mode
  - Cache Settings

#### 5.2 Settings Features
- Group-based Organization
- Type Casting (string, boolean, integer, json, file)
- Public/Private Settings
- Editable/Read-only Settings
- Cache Management

#### 5.3 File Uploads
- Logo Upload
- Favicon Upload
- ลบไฟล์เก่าอัตโนมัติ
- Image Optimization

### API Endpoints
```
GET    /admin/settings              - หน้าตั้งค่า
POST   /admin/settings              - บันทึกการตั้งค่า
POST   /admin/settings/logo         - Upload Logo
POST   /admin/settings/favicon      - Upload Favicon
GET    /admin/settings/public       - ดึงการตั้งค่าสาธารณะ (สำหรับ Frontend)
```

---

## 📦 Database Schema

### ตารางใหม่ที่เพิ่มเข้ามา

#### system_info
```sql
- id
- version
- build_number
- installed_at
- last_updated_at
- php_version (JSON)
- database_version (JSON)
- extensions (JSON)
- is_setup_completed
- setup_notes
- timestamps
```

#### app_settings
```sql
- id
- key (unique)
- value
- type (string, boolean, integer, json, file)
- group
- description
- is_public
- is_editable
- timestamps
```

#### theme_customizations
```sql
- id
- vendor_id (FK)
- theme_name
- primary_color
- secondary_color
- accent_color
- gradient_colors (JSON)
- font_family
- logo_url
- favicon_url
- banner_url
- custom_css (JSON)
- is_premium
- premium_expires_at
- is_active
- timestamps
```

#### premium_features
```sql
- id
- feature_name
- feature_key (unique)
- description
- price
- billing_cycle (monthly, yearly, lifetime)
- is_active
- timestamps
```

#### vendor_premium_subscriptions
```sql
- id
- vendor_id (FK)
- premium_feature_id (FK)
- started_at
- expires_at
- status (active, expired, cancelled)
- amount_paid
- timestamps
```

#### backups
```sql
- id
- name
- type (full, database, files)
- file_path
- file_size
- version
- status (pending, completed, failed)
- error_message
- started_at
- completed_at
- created_by (FK)
- auto_backup
- timestamps
```

---

## 🎯 การใช้งาน

### 1. Setup ครั้งแรก
```bash
# Clone และติดตั้ง
git clone https://github.com/xjanova/Thaiprompt-Affiliate.git
cd Thaiprompt-Affiliate
composer install
npm install && npm run build

# เริ่ม Server
php artisan serve

# เข้าสู่ Setup Wizard
# เปิด http://localhost:8000/setup
```

### 2. การสร้าง Backup
```php
use App\Services\Backup\BackupService;

$backupService = new BackupService();

// สร้าง Full Backup
$backup = $backupService->createFullBackup(auth()->id());

// สร้าง Database Backup
$backup = $backupService->createDatabaseBackup(auth()->id());

// Restore จาก Backup
$backupService->restoreBackup($backupId);
```

### 3. การใช้ Theme Customization
```php
use App\Services\Theme\ThemeService;

$themeService = new ThemeService();

// บันทึกธีม
$theme = $themeService->saveTheme($vendorId, [
    'primary_color' => '#4F46E5',
    'secondary_color' => '#EC4899',
    'gradient_colors' => ['#FF6B6B', '#FFE66D', '#4ECDC4'],
]);

// Upload โลโก้
$url = $themeService->uploadLogo($vendorId, $file);

// สมัครสมาชิก Premium
$subscription = $themeService->subscribeToPremium($vendorId, 'monthly');
```

### 4. การใช้ Settings
```php
use App\Models\AppSetting;

// Get Setting
$appName = AppSetting::get('app_name');

// Set Setting
AppSetting::set('app_name', 'My Store', 'string', 'general');

// Get Public Settings (สำหรับ Frontend)
$settings = AppSetting::getPublicSettings();

// Get Settings by Group
$generalSettings = AppSetting::getByGroup('general');
```

---

## 🔒 Security Features

1. **Setup Wizard Security**
   - ป้องกันการเข้าถึง Setup Wizard หลังติดตั้งเสร็จแล้ว
   - Middleware `RedirectIfSetupCompleted`

2. **Backup Security**
   - เฉพาะ Admin เท่านั้นที่สามารถสร้าง/กู้คืน Backup
   - บันทึก Log ทุกครั้งที่มีการสร้าง/กู้คืน

3. **Premium Features**
   - ตรวจสอบ Subscription Status
   - ป้องกันการใช้งาน Premium Feature โดยไม่ได้สมัคร

4. **Settings Security**
   - แยก Public/Private Settings
   - ป้องกันการแก้ไข Read-only Settings
   - Role-based Access Control

---

## 📈 Performance Optimization

1. **Caching**
   - Settings Cache (1 hour TTL)
   - Theme CSS Cache
   - Chart Data Cache

2. **Lazy Loading**
   - Chart.js โหลดเมื่อต้องการใช้งาน
   - GSAP โหลดแบบ Async

3. **Database Optimization**
   - Index สำหรับ Frequently Queried Columns
   - Eager Loading สำหรับ Relationships

---

## 🎨 Frontend Technologies

- **Tailwind CSS 3.x** - Utility-first CSS Framework
- **Alpine.js 3.x** - Lightweight JavaScript Framework
- **Chart.js 4.x** - Beautiful Charts
- **GSAP 3.x** - Professional Animations
- **Iconify** - Icon Framework
- **SweetAlert2** - Beautiful Alerts
- **Vite 4.x** - Fast Build Tool

---

## 🚀 What's Next?

ฟีเจอร์ที่กำลังพัฒนาใน Phase 3:
- [ ] Mobile App (React Native)
- [ ] AI-powered Product Recommendations
- [ ] Multi-language Support
- [ ] Advanced SEO Tools
- [ ] Social Media Integration
- [ ] Live Chat Support
- [ ] Advanced Reporting Dashboard
- [ ] Email Marketing Integration
