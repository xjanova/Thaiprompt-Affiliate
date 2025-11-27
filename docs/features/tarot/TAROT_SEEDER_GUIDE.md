# คู่มือการ Seed ข้อมูลระบบทาโร่ต์

## 📊 ข้อมูลที่มีใน Seeder

### 1. **ไพ่ทาโร่ต์ทั้งหมด 78 ใบ**
- **Major Arcana** 22 ใบ (0-21)
  - The Fool, The Magician, The High Priestess, The Empress, The Emperor, ฯลฯ
- **Minor Arcana** 56 ใบ (4 Suits x 14 ใบ)
  - Wands (ไม้เท้า) - พลัง, การกระทำ
  - Cups (ถ้วย) - ความรู้สึก, ความสัมพันธ์
  - Swords (ดาบ) - ความคิด, ความท้าทาย
  - Pentacles (เหรียญ) - ความมั่งคั่ง, การเงิน

### 2. **รูปแบบการเปิดไพ่ 5 แบบ**
- ไพ่ใบเดียว (1 ใบ) - คำตอบรวดเร็ว
- อดีต-ปัจจุบัน-อนาคต (3 ใบ)
- ไม้กางเขนเซลติก (10 ใบ) - ละเอียดลึกซึ้ง
- ความสัมพันธ์ (5 ใบ)
- เส้นทางอาชีพ (5 ใบ)

### 3. **หมวดหมู่การทำนาย 5 หมวด**
- ความรักและความสัมพันธ์ (ฟรี)
- การงานและการเงิน (99 บาท)
- การพัฒนาตนเอง (79 บาท)
- สุขภาพและความเป็นอยู่ (89 บาท)
- ทั่วไป (ฟรี)

### 4. **การตั้งค่าระบบ**
- เปิด/ปิด animation
- ความเร็ว animation
- จำนวนวันเก็บประวัติ

---

## 🚀 วิธีการ Run Seeder

### ขั้นตอนที่ 1: เตรียม Database
```bash
# ตรวจสอบว่า database พร้อมใช้งาน
php artisan migrate:status

# ถ้ายังไม่ได้ migrate ให้ run
php artisan migrate --force
```

### ขั้นตอนที่ 2: Run Tarot System Seeder
```bash
# Run seeder เพื่อเพิ่มข้อมูลทาโร่ต์ทั้งหมด
php artisan db:seed --class=TarotSystemSeeder --force
```

### ขั้นตอนที่ 3: ตรวจสอบข้อมูล
```bash
# ตรวจสอบจำนวนไพ่ทาโร่ต์
php artisan tinker
>>> \App\Models\TarotCard::count()
# ควรได้ 78 ใบ

>>> \App\Models\TarotReadingCategory::count()
# ควรได้ 5 หมวด

>>> \App\Models\TarotSpreadType::count()
# ควรได้ 5 รูปแบบ
```

---

## ✏️ การแก้ไขคำทำนายผ่าน Admin

### เข้าสู่หน้า Admin Tarot Management

1. **เข้าสู่ระบบ Admin**
   ```
   URL: /admin/tarot
   ```

2. **เมนูที่มีให้ใช้งาน:**

   **📇 จัดการไพ่ทาโร่ต์** (`/admin/tarot/cards`)
   - ดูรายการไพ่ทั้งหมด 78 ใบ
   - แก้ไขคำทำนาย (upright/reversed)
   - แก้ไขคำอธิบาย (ไทย/อังกฤษ)
   - แก้ไข keywords
   - อัพโหลดรูปไพ่

   **🏷️ จัดการหมวดหมู่** (`/admin/tarot/categories`)
   - เพิ่ม/แก้ไข/ลบหมวดหมู่
   - กำหนดราคาแต่ละหมวด
   - ตั้งค่าฟรี/เสียเงิน
   - เลือกไอคอนและสี

   **🃏 จัดการรูปหลังไพ่** (`/admin/tarot/card-backs`)
   - อัพโหลดรูปหลังไพ่หลายแบบ
   - ตั้งค่ารูปหลังไพ่เริ่มต้น
   - Preview รูปหลังไพ่

   **📊 ดูประวัติการทำนาย** (`/admin/tarot/readings`)
   - ดูการทำนายทั้งหมด
   - กรองตามหมวดหมู่
   - ดูรายละเอียดแต่ละครั้ง
   - Export ข้อมูล

   **⚙️ ตั้งค่าระบบ** (`/admin/tarot/settings`)
   - เปิด/ปิด animation
   - ตั้งความเร็ว animation
   - กำหนดวันเก็บประวัติ
   - ตั้งค่าต่างๆ

   **📈 Analytics** (`/admin/tarot/analytics`)
   - สถิติการใช้งาน
   - รายได้
   - หมวดหมู่ยอดนิยม
   - กราฟวิเคราะห์

---

## 📝 ตัวอย่างการแก้ไขคำทำนาย

### ผ่านหน้า Admin UI:

1. เข้า `/admin/tarot/cards`
2. คลิก "แก้ไข" ที่ไพ่ที่ต้องการ
3. แก้ไขฟิลด์ต่อไปนี้:
   - **ชื่อไพ่** (ไทย/อังกฤษ)
   - **Keywords** (คำสำคัญ)
   - **Upright Meaning** (ความหมายตรง)
   - **Reversed Meaning** (ความหมายกลับ)
   - **Description** (คำอธิบายละเอียด)
4. คลิก "บันทึก"

### ผ่าน Tinker (สำหรับ Developer):

```php
php artisan tinker

// แก้ไขไพ่ The Fool (ใบแรก)
$card = \App\Models\TarotCard::where('name_en', 'The Fool')->first();
$card->upright_meaning_th = 'คำทำนายใหม่ที่คุณต้องการ';
$card->reversed_meaning_th = 'คำทำนายกลับใบใหม่';
$card->keywords_th = ['คำสำคัญ1', 'คำสำคัญ2', 'คำสำคัญ3'];
$card->save();

// แก้ไขหมวดหมู่
$category = \App\Models\TarotReadingCategory::where('slug', 'love-relationships')->first();
$category->price = 149.00; // เปลี่ยนราคา
$category->save();
```

---

## 🎨 การเพิ่มรูปไพ่

### วิธีการ 1: ผ่าน Admin UI
1. เข้า `/admin/tarot/cards/{id}/edit`
2. เลือก "Upload Image"
3. อัพโหลดรูป (แนะนำขนาด 600x1000px)
4. คลิก "บันทึก"

### วิธีการ 2: เพิ่มผ่าน Migration/Seeder
```php
// ใน TarotSystemSeeder.php
TarotCard::where('name_en', 'The Fool')->update([
    'image_url' => '/images/tarot/major/00-the-fool.png'
]);
```

---

## 📦 โครงสร้างไฟล์ที่เกี่ยวข้อง

```
database/
├── migrations/
│   ├── 2025_11_07_000001_create_tarot_cards_table.php
│   ├── 2025_11_07_000002_create_tarot_card_back_images_table.php
│   ├── 2025_11_07_000003_create_tarot_spread_types_table.php
│   ├── 2025_11_07_000004_create_tarot_reading_categories_table.php
│   ├── 2025_11_07_000005_create_tarot_readings_table.php
│   ├── 2025_11_07_000006_create_tarot_reading_cards_table.php
│   ├── 2025_11_07_000007_create_tarot_user_limits_table.php
│   └── 2025_11_07_000008_create_tarot_settings_table.php
└── seeders/
    └── TarotSystemSeeder.php

app/
├── Models/
│   ├── TarotCard.php
│   ├── TarotCardBackImage.php
│   ├── TarotSpreadType.php
│   ├── TarotReadingCategory.php
│   ├── TarotReading.php
│   ├── TarotReadingCard.php
│   ├── TarotUserLimit.php
│   └── TarotSetting.php
└── Http/Controllers/
    ├── TarotReadingController.php (Frontend)
    └── Admin/
        └── TarotManagementController.php (Admin)

resources/views/
├── frontend/tarot/
│   ├── index.blade.php
│   ├── category.blade.php
│   ├── reading.blade.php
│   └── payment.blade.php
└── admin/tarot/
    ├── index.blade.php
    ├── cards/
    ├── categories/
    ├── card-backs/
    ├── readings/
    ├── settings.blade.php
    └── analytics.blade.php
```

---

## 🔍 การตรวจสอบว่า Seed สำเร็จ

```sql
-- ตรวจสอบจำนวนไพ่
SELECT COUNT(*) FROM tarot_cards;
-- ควรได้ 78

-- ตรวจสอบ Major Arcana
SELECT COUNT(*) FROM tarot_cards WHERE type = 'major_arcana';
-- ควรได้ 22

-- ตรวจสอบ Minor Arcana
SELECT COUNT(*) FROM tarot_cards WHERE type = 'minor_arcana';
-- ควรได้ 56

-- ตรวจสอบหมวดหมู่
SELECT name_th, price, is_free_first FROM tarot_reading_categories;

-- ตรวจสอบรูปแบบการเปิดไพ่
SELECT name_th, card_count FROM tarot_spread_types;
```

---

## ✅ Checklist หลัง Seed

- [ ] ไพ่ทาโร่ต์ครบ 78 ใบ
- [ ] หมวดหมู่ครบ 5 หมวด
- [ ] รูปแบบการเปิดไพ่ครบ 5 แบบ
- [ ] Settings ถูกสร้าง
- [ ] ทดสอบเข้าหน้า `/tarot` ได้
- [ ] ทดสอบเข้าหน้า `/admin/tarot` ได้
- [ ] ทดสอบการทำนายได้
- [ ] ทดสอบแก้ไขข้อมูลใน admin ได้

---

## 🆘 แก้ปัญหาที่พบบ่อย

### ปัญหา: Run seeder แล้วเกิด error duplicate
```bash
# ลบข้อมูลเก่าก่อน seed ใหม่
php artisan migrate:fresh --seed --force
# หรือ
php artisan db:seed --class=TarotSystemSeeder --force
```

### ปัญหา: ไพ่ไม่ครบ 78 ใบ
```bash
# ตรวจสอบ log
tail -f storage/logs/laravel.log

# หรือ run แบบ verbose
php artisan db:seed --class=TarotSystemSeeder -v
```

### ปัญหา: รูปภาพไม่แสดง
```bash
# สร้าง symbolic link
php artisan storage:link

# ตรวจสอบ permission
chmod -R 775 storage public/storage
```

---

## 📚 เอกสารเพิ่มเติม

- [TAROT_SYSTEM_README.md](TAROT_SYSTEM_README.md) - เอกสารระบบทั้งหมด
- [API Documentation](#) - API endpoints
- [Frontend Guide](#) - คู่มือใช้งานหน้าบ้าน

---

**สร้างโดย:** Claude AI
**วันที่:** 2025-11-08
**เวอร์ชัน:** 2.13.0
