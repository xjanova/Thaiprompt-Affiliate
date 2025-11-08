# ระบบการขายซอฟต์แวร์ที่ปรับแต่งได้ (Customizable Software Sales System)

## 📋 ภาพรวมระบบ

ระบบการขายซอฟต์แวร์ที่ออกแบบมาเพื่อให้ธุรกิจสามารถขายซอฟต์แวร์/เว็บไซต์ที่ปรับแต่งได้ พร้อมระบบใบเสนอราคาอัตโนมัติและการผ่อนชำระ

## ✨ ฟีเจอร์หลัก

### 1. ระบบจัดการสินค้าที่ปรับแต่งได้
- **หมวดหมู่สินค้า**: จัดกลุ่มสินค้าตามประเภท (MLM, อีคอมเมิร์ซ, แอฟฟิลิเอท ฯลฯ)
- **สินค้าซอฟต์แวร์**: สร้างสินค้าพร้อมรายละเอียดครบถ้วน
- **ออฟชั่นแบบหลายระดับ**:
  - ประเภท Input: Select, Checkbox, Radio, Number, Text
  - รองรับการเลือกหลายรายการ
  - กำหนดราคาแบบ Fixed หรือ Percentage
  - ค่าติดตั้ง (Setup Fee) และค่าบริการรายเดือน (Monthly Fee)

### 2. ระบบใบเสนอราคาอัตโนมัติ
- **การคำนวณราคาแบบเรียลไทม์**:
  - ราคาพื้นฐาน + ราคาจากออฟชั่นที่เลือก
  - ส่วนลด (เปอร์เซ็นต์)
  - ภาษี VAT 7% (ปรับได้)
  - ค่าติดตั้งรวม
  - ค่าบริการรายเดือนรวม

- **การจัดการใบเสนอราคา**:
  - สถานะ: ร่าง, รอตรวจสอบ, ส่งแล้ว, ยอมรับ, ปฏิเสธ, หมดอายุ, แปลงเป็นคำสั่งซื้อ
  - กำหนดอายุใบเสนอราคา (Default 30 วัน)
  - ส่งใบเสนอราคาทางอีเมล
  - แปลงเป็นคำสั่งซื้อได้โดยตรง

### 3. ระบบผ่อนชำระ (Installment)
- **แผนผ่อนชำระที่ยืดหยุ่น**:
  - กำหนดเงินดาวน์
  - จำนวนงวด (ไม่จำกัด)
  - อัตราดอกเบี้ย
  - ความถี่การชำระ: รายเดือน, รายไตรมาส, รายปี

- **การติดตามการชำระเงิน**:
  - สถานะแต่ละงวด: รอชำระ, ชำระแล้ว, เกินกำหนด
  - คำนวณค่าปรับชำระช้าอัตโนมัติ
  - แจ้งเตือนครบกำหนดชำระ
  - ออกใบเสร็จสำหรับแต่ละงวด

### 4. ระบบแจ้งเตือนสำหรับ Admin
- แจ้งเตือนเมื่อมีใบเสนอราคาใหม่
- แจ้งเตือนเมื่อมีคำสั่งซื้อใหม่
- แจ้งเตือนผ่าน: Database, Email, LINE (ถ้าเปิดใช้)

### 5. ตัวอย่างสินค้า: ระบบ MLM แบบครบวงจร

ระบบมาพร้อมกับ Seeder ตัวอย่างสำหรับ **ระบบ MLM** ที่มีออฟชั่นครบถ้วน:

#### แผนการตลาด (MLM Plan)
- ✅ Binary Plan (แผนแบบทวิภาค) - ฟรี
- ✅ Unilevel Plan (แผนแบบระดับเดียว) - +25,000 บาท
- ✅ Matrix Plan (แผนแบบเมทริกซ์) - +35,000 บาท
- ✅ Hybrid Plan (แผนแบบผสม) - +50,000 บาท

#### จำนวนสมาชิกสูงสุด
- 1,000 สมาชิก - ฟรี
- 5,000 สมาชิก - +30,000 บาท (แนะนำ)
- 10,000 สมาชิก - +50,000 บาท
- ไม่จำกัดจำนวนสมาชิก - +100,000 บาท

#### ฟีเจอร์เพิ่มเติม
- Mobile Application (iOS & Android) - +80,000 บาท
- ระบบ E-Wallet และถอนเงิน - +45,000 บาท
- Replicated Website - +35,000 บาท
- ศูนย์ฝึกอบรมออนไลน์ (LMS) - +40,000 บาท
- ระบบแจ้งเตือน SMS - +15,000 บาท
- เชื่อมต่อ LINE OA - +25,000 บาท (แนะนำ)
- รองรับหลายสกุลเงิน - +20,000 บาท
- รองรับหลายภาษา - +18,000 บาท

#### การติดตั้งและอบรม
- แพ็คเกจพื้นฐาน - ฟรี
- แพ็คเกจมาตรฐาน - +25,000 บาท (แนะนำ)
- แพ็คเกจพรีเมียม - +50,000 บาท

#### แผนบำรุงรักษา (รายเดือน)
- แผนพื้นฐาน - 3,000 บาท/เดือน
- แผนมาตรฐาน - 5,000 บาท/เดือน (แนะนำ)
- แผนพรีเมียม - 10,000 บาท/เดือน
- แผนองค์กร - 20,000 บาท/เดือน

#### โฮสติ้งและโดเมน
- ติดตั้งบน Server ของลูกค้าเอง - ฟรี
- Cloud Hosting พื้นฐาน - 3,000 บาท/เดือน
- Cloud Hosting พรีเมียม - 8,000 บาท/เดือน (แนะนำ)
- Cloud Hosting องค์กร - 15,000 บาท/เดือน

**ราคาเริ่มต้น**: 150,000 บาท (สามารถปรับแต่งเพิ่มได้ตามต้องการ)

## 🗄️ โครงสร้างฐานข้อมูล

### ตารางหลัก

1. **software_product_categories** - หมวดหมู่สินค้า
2. **software_products** - สินค้าซอฟต์แวร์
3. **software_product_options** - ออฟชั่น/ตัวเลือกของสินค้า
4. **software_product_option_values** - ค่าของออฟชั่นพร้อมราคา
5. **software_quotations** - ใบเสนอราคา
6. **software_quotation_items** - รายการในใบเสนอราคา
7. **software_quotation_selected_options** - ออฟชั่นที่ลูกค้าเลือก
8. **installment_plans** - แผนผ่อนชำระ
9. **installment_payments** - การชำระเงินแต่ละงวด

## 🚀 การติดตั้ง

### 1. Run Migrations

```bash
php artisan migrate
```

### 2. Run Seeders

```bash
php artisan db:seed --class=SoftwareProductSeeder
```

สิ่งนี้จะสร้าง:
- 3 หมวดหมู่สินค้า (MLM, อีคอมเมิร์ซ, แอฟฟิลิเอท)
- 1 สินค้า MLM พร้อมออฟชั่นครบถ้วน
- 6 กลุ่มออฟชั่น (แผนการตลาด, จำนวนสมาชิก, ฟีเจอร์เพิ่มเติม, การติดตั้ง, บำรุงรักษา, โฮสติ้ง)
- มากกว่า 30 ตัวเลือกพร้อมราคา

### 3. เพิ่ม Routes ใน `routes/web.php`

```php
require __DIR__.'/software_sales.php';
```

### 4. กำหนดสิทธิ์ Admin

ตรวจสอบว่า Middleware `admin` มีอยู่และทำงานถูกต้อง

## 📚 การใช้งาน

### สำหรับลูกค้า

1. **เลือกดูสินค้า**: `/software-products`
2. **ดูรายละเอียดสินค้า**: `/software-products/{slug}`
3. **สร้างใบเสนอราคา**: กรอกฟอร์มเลือกออฟชั่น ระบบจะคำนวณราคาอัตโนมัติ
4. **ดูใบเสนอราคาของตัวเอง**: `/my-quotations`
5. **ยอมรับและสั่งซื้อ**: แปลงใบเสนอราคาเป็นคำสั่งซื้อ
6. **ชำระเงิน**: ผ่านระบบ checkout ปกติ หรือเลือกผ่อนชำระ

### สำหรับ Admin

1. **จัดการสินค้า**: `/admin/software-products`
   - สร้าง/แก้ไข/ลบสินค้า
   - จัดการออฟชั่นและราคา
   - ตั้งค่าราคาและโปรโมชั่น

2. **จัดการใบเสนอราคา**: `/admin/software-quotations`
   - ดูใบเสนอราคาทั้งหมด
   - อนุมัติ/ปฏิเสธ
   - ส่งใบเสนอราคาทางอีเมล
   - ติดตามสถานะ

3. **จัดการผ่อนชำระ**: `/admin/installment-plans`
   - ดูแผนผ่อนชำระทั้งหมด
   - บันทึกการชำระเงินแต่ละงวด
   - ติดตามการชำระที่ค้าง
   - ยกเลิกแผนผ่อนชำระ

4. **Dashboard และรายงาน**: `/admin/software-sales/dashboard`
   - สถิติการขาย
   - ยอดขายรวม
   - อัตราการแปลงใบเสนอราคา
   - รายการที่ค้างชำระ

## 🔧 API Endpoints

### คำนวณราคาแบบเรียลไทม์

```javascript
POST /api/software-products/{product}/calculate-price
{
    "selections": {
        "1": [1, 2],  // option_id: [value_id1, value_id2]
        "2": [3]
    },
    "tax_rate": 7,
    "discount_percentage": 0
}

Response:
{
    "base_price": 150000,
    "subtotal": 200000,
    "discount_amount": 0,
    "tax_amount": 14000,
    "total_amount": 214000,
    "setup_total": 25000,
    "monthly_total": 8000,
    "selected_options": [...]
}
```

### คำนวณแผนผ่อนชำระ

```javascript
POST /api/calculate-installment
{
    "total_amount": 214000,
    "down_payment_percentage": 20,
    "total_installments": 12,
    "interest_rate": 0,
    "frequency": "monthly"
}

Response:
{
    "down_payment": 42800,
    "remaining_amount": 171200,
    "installment_amount": 14266.67,
    "total_interest": 0,
    ...
}
```

## 📧 Email Templates

ระบบจะส่งอีเมลอัตโนมัติใน cases ต่อไปนี้:

1. **ใบเสนอราคาให้ลูกค้า** - เมื่อ Admin กดส่งใบเสนอราคา
2. **แจ้งเตือน Admin** - เมื่อมีใบเสนอราคาหรือคำสั่งซื้อใหม่
3. **แจ้งเตือนครบกำหนดชำระ** - ก่อนถึงกำหนดชำระงวด 7 วัน
4. **แจ้งเตือนค้างชำระ** - เมื่อพ้นกำหนดชำระ

## 🎨 Customization

### เพิ่มสินค้าใหม่

```php
use App\Models\SoftwareProduct;

$product = SoftwareProduct::create([
    'category_id' => 1,
    'name' => 'ระบบอีคอมเมิร์ซ',
    'slug' => 'ecommerce-system',
    'base_price' => 80000,
    'is_customizable' => true,
    'is_active' => true,
    // ...
]);
```

### เพิ่มออฟชั่น

```php
use App\Models\SoftwareProductOption;
use App\Models\SoftwareProductOptionValue;

$option = SoftwareProductOption::create([
    'software_product_id' => $product->id,
    'name' => 'payment_gateway',
    'display_name' => 'Payment Gateway',
    'input_type' => 'checkbox',
    'allow_multiple' => true,
    // ...
]);

SoftwareProductOptionValue::create([
    'software_product_option_id' => $option->id,
    'value' => 'stripe',
    'display_label' => 'Stripe',
    'price_modifier' => 15000,
    'price_type' => 'fixed',
    // ...
]);
```

## 📊 Models และ Relationships

- `SoftwareProduct` hasMany `SoftwareProductOption`
- `SoftwareProductOption` hasMany `SoftwareProductOptionValue`
- `SoftwareQuotation` hasMany `SoftwareQuotationItem`
- `SoftwareQuotationItem` hasMany `SoftwareQuotationSelectedOption`
- `SoftwareQuotation` hasOne `Order` (เมื่อแปลงเป็นคำสั่งซื้อ)
- `Order` hasOne `InstallmentPlan`
- `InstallmentPlan` hasMany `InstallmentPayment`

## 🔐 Security

- ✅ CSRF Protection
- ✅ SQL Injection Prevention (Eloquent ORM)
- ✅ XSS Protection
- ✅ Authentication & Authorization
- ✅ Rate Limiting on API endpoints
- ✅ Input Validation

## ✅ อัพเดตล่าสุด (2025-11-08)

### Controllers เสร็จสมบูรณ์แล้ว!

**Customer Controllers:**
- ✅ SoftwareProductController - ดูสินค้า, ค้นหา, กรอง
- ✅ QuotationController - สร้างใบเสนอราคา, ยอมรับ/ปฏิเสธ, แปลงเป็นออเดอร์
- ✅ InstallmentController - ดูแผนผ่อนชำระ, ชำระเงิน

**Admin Controllers:**
- ✅ SoftwareCategoryController - จัดการหมวดหมู่
- ✅ SoftwareProductManagementController - จัดการสินค้า
- ✅ SoftwareQuotationManagementController - จัดการใบเสนอราคา, ส่งอีเมล
- ✅ InstallmentPlanController - จัดการผ่อนชำระ

**API Controllers:**
- ✅ QuotationCalculatorController - คำนวณราคาเรียลไทม์
- ✅ SoftwareProductController - ดึงข้อมูลออฟชั่น

### PDF System เสร็จสมบูรณ์!
- ✅ QuotationPdfService - สร้าง PDF (รองรับ DomPDF)
- ✅ Template PDF ภาษาไทยสวยงาม
- ✅ Download/Stream/Save PDF
- ✅ Fallback เป็น HTML

### Middleware & Security
- ✅ AdminMiddleware - ตรวจสอบสิทธิ์ admin
- ✅ Input Validation ครบทุก endpoint
- ✅ CSRF Protection
- ✅ XSS Protection

## 📝 TODO / Future Enhancements

- [ ] สร้าง Views/UI สำหรับหน้า Admin และลูกค้า (Blade templates)
- [ ] Integration กับ Payment Gateways (Stripe, PayPal, PromptPay)
- [ ] ระบบส่วนลดและโค้ดคูปอง
- [ ] Multi-language support (Laravel Localization)
- [ ] API Documentation (Swagger/OpenAPI)
- [ ] Unit Tests & Feature Tests
- [ ] Performance Optimization & Caching
- [ ] Excel/CSV Export สำหรับรายงาน
- [ ] Dashboard Analytics & Charts

## 🤝 Support

หากพบปัญหาหรือต้องการความช่วยเหลือ กรุณาติดต่อทีมพัฒนา

---

**Created Date**: 2025-11-08
**Version**: 1.0.0
**License**: Proprietary
