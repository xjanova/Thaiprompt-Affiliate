# ระบบคุกกี้ PDPA (Cookie Consent System)

ระบบจัดการความยินยอมคุกกี้และการติดตามผู้ใช้งานที่สอดคล้องกับกฎหมาย PDPA ของประเทศไทย

## คุณสมบัติหลัก

### 1. Cookie Consent Banner
- ✅ แบนเนอร์ขอความยินยอมคุกกี้ภาษาไทย
- ✅ ปรับแต่งข้อความได้เอง
- ✅ แบ่งประเภทคุกกี้ได้ 4 ประเภท:
  - **จำเป็น (Necessary)**: เปิดอยู่เสมอ
  - **วิเคราะห์ (Analytics)**: ติดตามพฤติกรรมผู้ใช้
  - **การตลาด (Marketing)**: แสดงโฆษณาที่เกี่ยวข้อง
  - **การตั้งค่า (Preferences)**: จดจำการตั้งค่าผู้ใช้
- ✅ ลิงก์ไปหน้านโยบายคุกกี้
- ✅ ตัวเลือก "ยอมรับทั้งหมด" และ "ตั้งค่าคุกกี้"

### 2. ระบบติดตามผู้ใช้งาน (Cookie Tracking)
- ✅ **ข้อมูลพื้นฐาน**:
  - IP Address
  - ประเภทอุปกรณ์ (มือถือ/แท็บเล็ต/คอมพิวเตอร์)
  - เบราว์เซอร์และเวอร์ชัน
  - ระบบปฏิบัติการ

- ✅ **แหล่งที่มา (Referrer)**:
  - URL ที่มา
  - Domain ที่มา
  - พารามิเตอร์ UTM (source, medium, campaign, term, content)

- ✅ **พฤติกรรมการใช้งาน**:
  - หน้าที่เข้าชมครั้งแรก (Landing Page)
  - จำนวนหน้าที่เข้าชม
  - ระยะเวลาในเซสชัน
  - คำค้นหาที่ใช้
  - สินค้าที่ดู

- ✅ **การวิเคราะห์ความสนใจ (Interest Detection)**:
  - ตรวจจับความสนใจจากคีย์เวิร์ด
  - จัดหมวดหมู่: Shopping, Technology, Education, Business, Lifestyle
  - แท็กพฤติกรรม: active_browser, shopping_intent, researcher, engaged_visitor

### 3. Admin Dashboard
- ✅ **สถิติโดยรวม**:
  - จำนวนผู้เยี่ยมชมทั้งหมด
  - จำนวนเซสชัน
  - เวลาเฉลี่ยในเว็บไซต์
  - จำนวนการดูหน้า
  - อัตราการยินยอม (Consent Rate)

- ✅ **วิเคราะห์แหล่งที่มา**:
  - Top 10 Referrers
  - UTM Campaign Performance
  - แหล่งที่มาที่ดีที่สุด

- ✅ **วิเคราะห์คีย์เวิร์ด**:
  - คีย์เวิร์ดยอดนิยม
  - จำนวนการค้นหา
  - เซสชันที่เกี่ยวข้อง

- ✅ **วิเคราะห์อุปกรณ์และเบราว์เซอร์**:
  - สัดส่วน Mobile/Tablet/Desktop
  - เบราว์เซอร์ที่ใช้มากที่สุด
  - ระบบปฏิบัติการ

- ✅ **วิเคราะห์ความสนใจ**:
  - Interest Categories
  - จำนวนผู้ใช้ในแต่ละหมวดหมู่

- ✅ **Export ข้อมูล**:
  - ส่งออกเป็น CSV
  - ส่งออกเป็น JSON
  - เลือกช่วงวันที่ได้

### 4. หน้านโยบายคุกกี้ (Cookie Policy)
- ✅ อธิบายคุกกี้แต่ละประเภทอย่างละเอียด
- ✅ ระบุข้อมูลที่เก็บรวบรวม
- ✅ ระยะเวลาเก็บข้อมูล
- ✅ วิธีการจัดการคุกกี้
- ✅ สอดคล้องกับกฎหมาย PDPA

## โครงสร้างไฟล์

### Models
```
app/Models/
├── CookieConsent.php          # เก็บความยินยอมผู้ใช้
├── CookieTracking.php         # ติดตามพฤติกรรมผู้ใช้
├── CookieAnalyticsKeyword.php # วิเคราะห์คีย์เวิร์ด
└── CookieSetting.php          # การตั้งค่าระบบ
```

### Controllers
```
app/Http/Controllers/
├── CookieConsentController.php              # API จัดการคุกกี้
└── Admin/CookieAnalyticsController.php      # Admin Dashboard
```

### Views
```
resources/views/
├── components/cookie-consent-banner.blade.php  # แบนเนอร์คุกกี้
├── cookie-policy.blade.php                     # หน้านโยบายคุกกี้
└── admin/cookie-analytics/
    ├── index.blade.php                         # Dashboard
    └── settings.blade.php                      # ตั้งค่า
```

### Database
```
database/
├── migrations/2025_11_08_000001_create_cookie_consents_table.php
└── seeders/CookieSettingsSeeder.php
```

## การติดตั้ง

### 1. รัน Migration
```bash
php artisan migrate
```

### 2. รัน Seeder (ข้อมูลเริ่มต้น)
```bash
php artisan db:seed --class=CookieSettingsSeeder
```

### 3. Clear Cache
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

## การใช้งาน

### 1. แบนเนอร์คุกกี้จะปรากฏอัตโนมัติ
แบนเนอร์จะแสดงที่ด้านล่างของหน้าเว็บทุกหน้าเมื่อผู้ใช้เข้าชมครั้งแรก

### 2. เข้าถึง Admin Dashboard
```
URL: /admin/cookie-analytics
```

เข้าสู่ระบบด้วยบัญชี Admin แล้วไปที่เมนู "Cookie Analytics"

### 3. ตั้งค่าระบบ
```
URL: /admin/cookie-analytics/settings
```

สามารถปรับแต่ง:
- เปิด/ปิดแบนเนอร์
- ข้อความในแบนเนอร์
- URL นโยบายคุกกี้
- ระยะเวลาหมดอายุของคุกกี้

### 4. ดูข้อมูลผู้เยี่ยมชม
```
URL: /admin/cookie-analytics/visitors
```

### 5. Export ข้อมูล
```
URL: /admin/cookie-analytics/export?format=csv&range=30days
```

## API Endpoints

### บันทึกความยินยอม
```javascript
POST /api/cookie-consent
Body: {
  necessary: true,
  analytics: true,
  marketing: false,
  preferences: true
}
```

### ติดตามการดูหน้า
```javascript
POST /api/cookie-track-page
Body: {
  page_url: "https://example.com/page"
}
```

### ติดตามคีย์เวิร์ด
```javascript
POST /api/cookie-track-keyword
Body: {
  keyword: "สินค้า"
}
```

### ติดตามการดูสินค้า
```javascript
POST /api/cookie-track-product
Body: {
  product_id: 123,
  product_name: "สินค้าตัวอย่าง",
  category: "อิเล็กทรอนิกส์",
  price: 1000
}
```

## การติดตามอัตโนมัติ

### ติดตามหน้าที่เข้าชม
ระบบจะติดตามการดูหน้าอัตโนมัติเมื่อผู้ใช้ยินยอม Analytics

### ติดตามคีย์เวิร์ดในฟอร์มค้นหา
เพิ่มในฟอร์มค้นหา:
```javascript
<form onsubmit="trackKeyword(this.search.value)">
  <input name="search" type="text">
</form>
```

### ติดตามการดูสินค้า
เพิ่มในหน้าสินค้า:
```javascript
trackProduct({
  product_id: {{ $product->id }},
  product_name: "{{ $product->name }}",
  category: "{{ $product->category }}",
  price: {{ $product->price }}
});
```

## การปรับแต่ง

### เปลี่ยนข้อความแบนเนอร์
1. เข้า Admin → Cookie Analytics → Settings
2. แก้ไข "หัวข้อแบนเนอร์" และ "คำอธิบาย"
3. บันทึก

### เพิ่มหมวดความสนใจใหม่
แก้ไขใน `app/Models/CookieTracking.php`:
```php
public function detectInterests(): array
{
    $categories = [
        'shopping' => ['ซื้อ', 'สั่ง', 'ช้อป'],
        'your_category' => ['keyword1', 'keyword2'],
        // เพิ่มเติม...
    ];
}
```

## ความปลอดภัยและ PDPA

### ข้อมูลที่เก็บ
- ข้อมูลถูกเก็บเฉพาะเมื่อผู้ใช้ยินยอม
- IP Address ถูกจัดเก็บสำหรับการวิเคราะห์เท่านั้น
- ไม่มีการเก็บข้อมูลส่วนบุคคลที่ระบุตัวตนได้

### สิทธิของผู้ใช้
- ผู้ใช้สามารถเลือกยินยอมหรือปฏิเสธได้
- ผู้ใช้สามารถเปลี่ยนแปลงความยินยอมได้ทุกเมื่อ
- ข้อมูลจะหมดอายุตามที่ตั้งค่าไว้

### การลบข้อมูล
ข้อมูลจะถูกลบอัตโนมัติเมื่อหมดอายุ (ค่าเริ่มต้น 365 วัน)

## เมนูแอดมิน

แนะนำให้เพิ่มลิงก์ในเมนูแอดมิน:
```php
// ในไฟล์เมนูแอดมิน
[
    'name' => 'Cookie Analytics',
    'icon' => 'fa-cookie',
    'route' => 'admin.cookie-analytics.index',
    'permission' => 'view_cookie_analytics'
]
```

## Support

สำหรับคำถามหรือปัญหา:
- อ่านเอกสารนี้อย่างละเอียด
- ตรวจสอบ Console ของเบราว์เซอร์
- ตรวจสอบ Laravel Logs

## License

ระบบนี้เป็นส่วนหนึ่งของ Thaiprompt Affiliate Platform
