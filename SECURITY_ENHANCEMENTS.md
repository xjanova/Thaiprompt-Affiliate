# Security Enhancements Documentation

## ภาพรวม (Overview)

เอกสารนี้อธิบายการปรับปรุงความปลอดภัยที่เพิ่มเข้ามาในระบบ Thaiprompt Affiliate Marketplace รวมถึง:

1. **ระบบรักษาความปลอดภัยแบบครอบคลุม** - Security Headers, Input Sanitization, Rate Limiting
2. **Cloudflare Tunnel Integration** - ระบบกรองสแปมและป้องกันการโจมตี
3. **ระบบจัดการสิทธิ์พนักงาน** - แยกเป็น Super Admin Staff และ Vendor Staff

---

## 1. ระบบรักษาความปลอดภัยพื้นฐาน (Core Security Features)

### 1.1 Security Headers Middleware

**ไฟล์:** `app/Http/Middleware/SecurityHeaders.php`

ป้องกันการโจมตีประเภทต่างๆ ผ่าน HTTP Headers:

- **X-Frame-Options**: ป้องกัน Clickjacking
- **X-Content-Type-Options**: ป้องกัน MIME Type Sniffing
- **X-XSS-Protection**: เปิดใช้งาน XSS Protection ในเบราว์เซอร์
- **Strict-Transport-Security**: บังคับใช้ HTTPS
- **Content-Security-Policy**: ควบคุมแหล่งที่มาของ Content
- **Referrer-Policy**: ควบคุมการส่ง Referrer
- **Permissions-Policy**: จำกัดสิทธิ์การใช้งาน API ของเบราว์เซอร์

**การตั้งค่า:**

```php
// config/security.php
'csp' => [
    'enabled' => true,
    'policy' => 'default-src \'self\'; script-src \'self\' \'unsafe-inline\'...'
]
```

### 1.2 Input Sanitization Middleware

**ไฟล์:** `app/Http/Middleware/SanitizeInput.php`

ทำความสะอาดข้อมูลที่รับเข้ามาทั้งหมด:
- ลบ HTML tags ที่ไม่ปลอดภัย
- ลบ null bytes
- ตัดช่องว่างส่วนเกิน

**การตั้งค่า:**

```php
// config/security.php
'allowed_tags' => '<b><i><u><strong><em><p><br><ul><ol><li>'
```

### 1.3 Rate Limiting Configuration

**ไฟล์:** `config/security.php`

กำหนดขีดจำกัดการเรียกใช้งานแต่ละฟังก์ชัน:

```php
'rate_limiting' => [
    'api' => ['max_attempts' => 60, 'decay_minutes' => 1],
    'login' => ['max_attempts' => 5, 'decay_minutes' => 15],
    'registration' => ['max_attempts' => 3, 'decay_minutes' => 60],
    'order' => ['max_attempts' => 10, 'decay_minutes' => 60],
    'review' => ['max_attempts' => 5, 'decay_minutes' => 60],
]
```

---

## 2. Cloudflare Tunnel & Spam Filtering System

### 2.1 Cloudflare Configuration Model

**ไฟล์:** `app/Models/CloudflareConfig.php`

**ฟีเจอร์หลัก:**

1. **Cloudflare Tunnel Settings**
   - `tunnel_enabled`: เปิด/ปิด Cloudflare Tunnel
   - `tunnel_id`: Tunnel ID จาก Cloudflare
   - `tunnel_secret`: Secret Key สำหรับการยืนยันตัวตน
   - `verify_cf_headers`: ตรวจสอบ Cloudflare Headers

2. **Spam Filter Settings**
   - `filter_comments`: กรองคอมเมนต์/รีวิว
   - `filter_orders`: กรองการสั่งซื้อ
   - `filter_contact_forms`: กรองฟอร์มติดต่อ
   - `filter_registrations`: กรองการสมัครสมาชิก

3. **Security Level Settings**
   - `challenge_mode`: off, managed, high, under_attack
   - `security_level`: off, essentially_off, low, medium, high, under_attack
   - `enable_bot_fight_mode`: ป้องกัน Bot
   - `enable_ddos_protection`: ป้องกัน DDoS

4. **Rate Limiting Rules**
   ```json
   {
     "comment": {"max_attempts": 5, "decay_minutes": 60},
     "order": {"max_attempts": 10, "decay_minutes": 60}
   }
   ```

5. **IP & Country Filtering**
   - `ip_whitelist`: IP ที่อนุญาต
   - `ip_blacklist`: IP ที่ถูกบล็อก
   - `country_whitelist`: ประเทศที่อนุญาต
   - `country_blacklist`: ประเทศที่ถูกบล็อก

### 2.2 Spam Detection Service

**ไฟล์:** `app/Services/SpamDetectionService.php`

**การตรวจจับสแปม:**

1. **Keyword Detection**
   - คำที่เกี่ยวข้องกับการพนัน, ยา, เงินกู้
   - รองรับทั้งภาษาไทยและอังกฤษ

2. **Pattern Analysis**
   - URL ที่น่าสงสัย
   - JavaScript injection
   - HTML injection

3. **Content Analysis**
   - อัตราส่วนตัวอักษรพิมพ์ใหญ่
   - อัตราส่วนอักขระพิเศษ
   - การซ้ำซากมากเกินไป
   - จำนวน URL มากเกินไป

**Spam Score Thresholds:**
- Comment/Review: 30 คะแนน
- Order: 40 คะแนน
- Contact Form: 25 คะแนน
- Registration: 35 คะแนน

### 2.3 Spam Filter Middleware

**ไฟล์:** `app/Http/Middleware/SpamFilter.php`

**การใช้งาน:**

```php
// ในไฟล์ routes
Route::post('/review', [ReviewController::class, 'store'])
    ->middleware('spam.filter:review');

Route::post('/order', [OrderController::class, 'store'])
    ->middleware('spam.filter:order');
```

### 2.4 Cloudflare Configuration Controller

**ไฟล์:** `app/Http/Controllers/Admin/CloudflareConfigController.php`

**Endpoints:**

```php
GET  /admin/cloudflare          - แสดงหน้าการตั้งค่า
POST /admin/cloudflare/update   - อัปเดตการตั้งค่า
POST /admin/cloudflare/test     - ทดสอบการเชื่อมต่อ
GET  /admin/cloudflare/statistics - สถิติความปลอดภัย
GET  /admin/cloudflare/logs     - ดู Security Logs
```

**การเข้าถึง:** Super Admin เท่านั้น

---

## 3. Employee Permission System

### 3.1 Admin Employee System

**ไฟล์:** `app/Models/AdminEmployee.php`

**ฟีเจอร์:**

1. **สิทธิ์การจัดการ (Permissions)**
   - `can_manage_users`: จัดการผู้ใช้
   - `can_manage_vendors`: จัดการร้านค้า
   - `can_manage_products`: จัดการสินค้า
   - `can_manage_orders`: จัดการคำสั่งซื้อ
   - `can_manage_commissions`: จัดการค่าคอมมิชชั่น
   - `can_manage_withdrawals`: จัดการการถอนเงิน
   - `can_manage_settings`: จัดการการตั้งค่าระบบ
   - `can_manage_security`: จัดการความปลอดภัย (Cloudflare)
   - `can_manage_employees`: จัดการพนักงาน
   - `can_view_reports`: ดูรายงาน
   - `can_view_analytics`: ดูสถิติ
   - `can_manage_content`: จัดการเนื้อหา
   - `can_manage_coupons`: จัดการคูปอง
   - `can_manage_categories`: จัดการหมวดหมู่

2. **การควบคุมการเข้าถึง (Access Control)**
   - `allowed_ip_addresses`: IP ที่อนุญาต
   - `work_start_time`: เวลาเริ่มงาน
   - `work_end_time`: เวลาเลิกงาน
   - `allowed_days`: วันที่อนุญาต (จันทร์-อาทิตย์)

3. **ข้อมูลพนักงาน**
   - `employee_code`: รหัสพนักงาน (เช่น AE000001)
   - `department`: แผนก
   - `position`: ตำแหน่ง
   - `employment_status`: active, inactive, suspended, terminated

**Controller:** `app/Http/Controllers/Admin/AdminEmployeeController.php`

**Routes:**
```php
GET    /admin/employees         - รายการพนักงาน
GET    /admin/employees/create  - ฟอร์มเพิ่มพนักงาน
POST   /admin/employees         - บันทึกพนักงานใหม่
GET    /admin/employees/{id}    - รายละเอียดพนักงาน
GET    /admin/employees/{id}/edit - ฟอร์มแก้ไขพนักงาน
PUT    /admin/employees/{id}    - อัปเดตพนักงาน
DELETE /admin/employees/{id}    - ลบพนักงาน
```

### 3.2 Vendor Employee System

**ไฟล์:** `app/Models/VendorEmployee.php`

**ฟีเจอร์:**

1. **สิทธิ์การจัดการ (Permissions)**
   - `can_manage_products`: จัดการสินค้าของร้าน
   - `can_manage_orders`: จัดการคำสั่งซื้อ
   - `can_manage_inventory`: จัดการสต็อก
   - `can_view_sales_reports`: ดูรายงานยอดขาย
   - `can_manage_coupons`: จัดการคูปองส่วนลด
   - `can_manage_reviews`: จัดการรีวิวสินค้า
   - `can_use_pos`: ใช้งานระบบ POS
   - `can_process_refunds`: คืนเงิน
   - `can_manage_shipping`: จัดการการจัดส่ง
   - `can_manage_employees`: จัดการพนักงานร้าน

2. **สิทธิ์ POS (POS Permissions)**
   - `can_open_pos_session`: เปิดรอบขาย
   - `can_close_pos_session`: ปิดรอบขาย
   - `can_void_transactions`: ยกเลิกรายการ
   - `can_apply_discounts`: ให้ส่วนลด
   - `max_discount_percentage`: % ส่วนลดสูงสุดที่ให้ได้
   - `max_transaction_amount`: จำนวนเงินสูงสุดต่อรายการ

3. **ค่าคอมมิชชั่นพนักงาน**
   - `commission_percentage`: % ค่าคอมมิชชั่นที่พนักงานได้รับ

4. **การควบคุมการเข้าถึง**
   - เหมือนกับ Admin Employee

**Controller:** `app/Http/Controllers/Vendor/VendorEmployeeController.php`

**Routes:**
```php
GET    /vendor/employees         - รายการพนักงาน
GET    /vendor/employees/create  - ฟอร์มเพิ่มพนักงาน
POST   /vendor/employees         - บันทึกพนักงานใหม่
GET    /vendor/employees/{id}    - รายละเอียดพนักงาน
GET    /vendor/employees/{id}/edit - ฟอร์มแก้ไขพนักงาน
PUT    /vendor/employees/{id}    - อัปเดตพนักงาน
DELETE /vendor/employees/{id}    - ลบพนักงาน
```

### 3.3 Employee Permission Middleware

**Admin Employee Middleware:**
- **ไฟล์:** `app/Http/Middleware/CheckAdminEmployeePermission.php`
- **Alias:** `admin.employee`

**Vendor Employee Middleware:**
- **ไฟล์:** `app/Http/Middleware/CheckVendorEmployeePermission.php`
- **Alias:** `vendor.employee`

**การใช้งาน:**

```php
// ตรวจสอบว่าเป็นพนักงาน Admin
Route::middleware('admin.employee')->group(function () {
    // routes
});

// ตรวจสอบสิทธิ์เฉพาะ
Route::middleware('admin.employee:manage_users')->group(function () {
    // ต้องมีสิทธิ์ manage_users
});

// สำหรับ Vendor Employee
Route::middleware('vendor.employee:manage_products')->group(function () {
    // ต้องมีสิทธิ์ manage_products
});
```

**การตรวจสอบอัตโนมัติ:**
1. ตรวจสอบสถานะพนักงาน (employment_status)
2. ตรวจสอบเวลาทำงาน (work hours)
3. ตรวจสอบวันทำงาน (work days)
4. ตรวจสอบ IP ที่อนุญาต
5. ตรวจสอบสิทธิ์เฉพาะที่กำหนด
6. บันทึก Security Log อัตโนมัติ

---

## 4. Security Logging System

### 4.1 Security Log Model

**ไฟล์:** `app/Models/SecurityLog.php`

**Event Types:**
- `login_attempt`, `login_success`, `login_failure`
- `logout`, `password_change`, `password_reset`
- `permission_change`, `spam_detected`
- `rate_limit_exceeded`, `suspicious_activity`
- `ip_blocked`, `account_locked`, `account_unlocked`
- `2fa_enabled`, `2fa_disabled`, `2fa_verified`
- `unauthorized_access`, `data_export`, `data_deletion`
- `employee_added`, `employee_removed`
- `cloudflare_config_changed`

**Severity Levels:**
- `low` - ปกติ
- `medium` - ต้องติดตาม
- `high` - อันตราย
- `critical` - วิกฤต

**การใช้งาน:**

```php
use App\Models\SecurityLog;

// บันทึก Event
SecurityLog::logEvent('login_failure', [
    'severity' => 'medium',
    'description' => 'Failed login attempt',
    'metadata' => ['attempts' => 3]
]);

// Query Logs
$recentFailures = SecurityLog::byEventType('login_failure')
    ->recent(24)
    ->get();

$suspiciousLogs = SecurityLog::suspicious()
    ->bySeverity('high')
    ->get();
```

---

## 5. Database Migrations

### 5.1 Cloudflare Configs Table

**ไฟล์:** `database/migrations/2024_01_20_000001_create_cloudflare_configs_table.php`

```bash
php artisan migrate
```

**สร้าง Default Configuration อัตโนมัติ**

### 5.2 Admin Employees Table

**ไฟล์:** `database/migrations/2024_01_20_000002_create_admin_employees_table.php`

### 5.3 Vendor Employees Table

**ไฟล์:** `database/migrations/2024_01_20_000003_create_vendor_employees_table.php`

### 5.4 Security Logs Table

**ไฟล์:** `database/migrations/2024_01_20_000004_create_security_logs_table.php`

---

## 6. การติดตั้งและการใช้งาน

### 6.1 การติดตั้ง

```bash
# 1. รัน Migrations
php artisan migrate

# 2. Clear Cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# 3. สร้าง Config Cache (Production)
php artisan config:cache
php artisan route:cache
```

### 6.2 การตั้งค่า Environment Variables

เพิ่มใน `.env`:

```env
# Security Settings
CSP_ENABLED=true
RATE_LIMITING_ENABLED=true
API_RATE_LIMIT=60
LOGIN_RATE_LIMIT=5
AUDIT_LOGGING_ENABLED=true

# Password Requirements
PASSWORD_MIN_LENGTH=8
PASSWORD_REQUIRE_UPPERCASE=true
PASSWORD_REQUIRE_NUMBERS=true
PASSWORD_REQUIRE_SPECIAL=true

# Session Security
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_TIMEOUT=120
```

### 6.3 การตั้งค่า Cloudflare Tunnel

1. เข้าสู่ระบบด้วย Super Admin
2. ไปที่ `/admin/cloudflare`
3. กรอกข้อมูล:
   - Tunnel ID (จาก Cloudflare Dashboard)
   - Tunnel Secret
   - เลือกฟีเจอร์ที่ต้องการเปิดใช้งาน
4. กำหนด Rate Limiting Rules
5. เพิ่ม Spam Keywords (ถ้าต้องการ)
6. บันทึกการตั้งค่า

### 6.4 การเพิ่มพนักงาน Admin

1. เข้าสู่ระบบด้วย Super Admin
2. ไปที่ `/admin/employees`
3. คลิก "เพิ่มพนักงานใหม่"
4. กรอกข้อมูลพนักงาน:
   - ชื่อ, อีเมล, เบอร์โทร
   - แผนก, ตำแหน่ง
   - เลือกสิทธิ์ที่ต้องการให้
   - กำหนดเวลาทำงาน (ถ้าต้องการ)
   - กำหนด IP ที่อนุญาต (ถ้าต้องการ)
5. บันทึกข้อมูล

### 6.5 การเพิ่มพนักงานร้านค้า

1. เข้าสู่ระบบด้วยบัญชีเจ้าของร้าน
2. ไปที่ `/vendor/employees`
3. คลิก "เพิ่มพนักงานใหม่"
4. กรอกข้อมูลเหมือนพนักงาน Admin
5. กำหนดสิทธิ์ POS (ถ้าใช้งาน POS)
6. กำหนดค่าคอมมิชชั่น (ถ้ามี)
7. บันทึกข้อมูล

---

## 7. API Endpoints

### 7.1 Cloudflare API

```
GET  /admin/cloudflare/statistics
Response:
{
  "spam_blocked_24h": 15,
  "rate_limit_24h": 8,
  "unauthorized_access_24h": 3,
  "total_security_events_24h": 26,
  "login_failures_24h": 12
}
```

### 7.2 Security Logs API

```
GET /admin/cloudflare/logs?event_type=spam_detected&severity=high
```

---

## 8. Best Practices

### 8.1 สำหรับ Super Admin

1. **อย่าแชร์ข้อมูล Cloudflare Tunnel Secret**
2. **ตั้ง IP Whitelist สำหรับพนักงานที่มีสิทธิ์สูง**
3. **ตรวจสอบ Security Logs เป็นประจำ**
4. **ปรับ Rate Limit ให้เหมาะสมกับการใช้งาน**
5. **เพิ่ม Custom Spam Keywords ตามที่เจอ**

### 8.2 สำหรับเจ้าของร้าน

1. **กำหนดสิทธิ์พนักงานให้เหมาะสมกับหน้าที่**
2. **ตั้ง Max Discount Percentage สำหรับพนักงาน POS**
3. **ตั้ง Max Transaction Amount เพื่อจำกัดความเสี่ยง**
4. **ตรวจสอบ Activity ของพนักงานเป็นประจำ**

### 8.3 สำหรับพัฒนา

1. **ใช้ `spam.filter` middleware บน routes ที่รับ user input**
2. **ใช้ `sanitize` middleware บน forms ทั้งหมด**
3. **Log security events ด้วย SecurityLog::logEvent()**
4. **ตรวจสอบ permissions ด้วย middleware**

---

## 9. Troubleshooting

### 9.1 Cloudflare Tunnel ไม่ทำงาน

1. ตรวจสอบว่า `tunnel_enabled` เปิดอยู่
2. ตรวจสอบ Tunnel ID และ Secret
3. ทดสอบการเชื่อมต่อที่ `/admin/cloudflare/test`
4. ตรวจสอบว่าเว็บไซต์ผ่าน Cloudflare จริง

### 9.2 พนักงานเข้าถึงไม่ได้

1. ตรวจสอบ `employment_status` = 'active'
2. ตรวจสอบเวลาและวันที่อนุญาต
3. ตรวจสอบ IP Address
4. ตรวจสอบสิทธิ์ที่กำหนด

### 9.3 Spam Filter บล็อกผิด

1. ลด threshold ใน SpamDetectionService
2. ลบ keyword ที่บล็อกผิดออก
3. เพิ่ม whitelist สำหรับ user ที่เชื่อถือได้

### 9.4 Rate Limit เกินบ่อย

1. เพิ่ม `max_attempts` ใน config/security.php
2. เพิ่ม `decay_minutes` ให้ช้าลง
3. ตั้ง IP Whitelist สำหรับ user ที่เชื่อถือได้

---

## 10. Security Checklist

### ✅ ก่อน Deploy Production

- [ ] เปลี่ยน `APP_DEBUG=false`
- [ ] ตั้ง `APP_ENV=production`
- [ ] ใช้ HTTPS (SSL Certificate)
- [ ] ตั้ง `SESSION_SECURE_COOKIE=true`
- [ ] กำหนด `ALLOWED_HTML_TAGS` ที่จำเป็นเท่านั้น
- [ ] ตรวจสอบ Rate Limits ทั้งหมด
- [ ] เปิดใช้งาน Cloudflare Tunnel
- [ ] ตั้ง IP Whitelist สำหรับ Admin
- [ ] ทดสอบ Spam Filter
- [ ] ทดสอบ Employee Permissions
- [ ] Backup Database ก่อน Migrate
- [ ] ตั้งค่า Auto-backup Security Logs

---

## 11. Updates & Maintenance

### การอัปเดต Spam Keywords

```php
use App\Services\SpamDetectionService;

$spamService = app(SpamDetectionService::class);
$spamService->addSpamKeyword('คีย์เวิร์ดใหม่');
```

### การล้าง Security Logs เก่า

```bash
# ลบ logs เก่ากว่า 365 วัน
php artisan db:query "DELETE FROM security_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 365 DAY)"
```

---

## 12. Support & Contact

หากพบปัญหาหรือต้องการความช่วยเหลือ:

1. ตรวจสอบ Security Logs ที่ `/admin/cloudflare/logs`
2. ตรวจสอบ Laravel Logs ที่ `storage/logs/laravel.log`
3. ติดต่อทีม DevOps หรือ Security Team

---

**เอกสารนี้อัปเดตล่าสุด:** 2024-01-20
**เวอร์ชั่น:** 1.0.0
