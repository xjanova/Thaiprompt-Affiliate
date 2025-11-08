# ระบบหมอดูไพ่ทาโร่ต์ (Tarot Reading System)

## 📋 ภาพรวมระบบ

ระบบหมอดูไพ่ทาโร่ต์เป็นระบบที่สมบูรณ์แบบ มีอนิเมชั่นสวยงาม และรองรับการทำนายด้วยไพ่ทาโร่ต์ 78 ใบ (22 Major Arcana + 56 Minor Arcana)

### ✨ ฟีเจอร์หลัก

1. **การทำนายด้วยไพ่ทาโร่ต์**
   - เลือกไพ่แบบสุ่มจากชุดไพ่ทั้งหมด 78 ใบ
   - รองรับไพ่กลับหัว (Reversed) และหัวตั้ง (Upright)
   - มีความหมายภาษาไทยและภาษาอังกฤษครบถ้วน

2. **รูปแบบการเปิดไพ่ (Spread Types)**
   - ไพ่ใบเดียว (Single Card) - ทำนายรวดเร็ว
   - อดีต ปัจจุบัน อนาคต (Past, Present, Future) - 3 ใบ
   - ไม้กางเขนเซลติก (Celtic Cross) - 10 ใบ (ละเอียดลึกซึ้ง)
   - การทำนายความสัมพันธ์ (Relationship Spread) - 5 ใบ
   - เส้นทางอาชีพ (Career Path) - 5 ใบ

3. **หมวดหมู่การทำนาย (Reading Categories)**
   - ความรักและความสัมพันธ์ (Love & Relationships) - ฟรี
   - การงานและการเงิน (Career & Finance) - 99 บาท
   - การพัฒนาตนเอง (Personal Growth) - 79 บาท
   - สุขภาพและความเป็นอยู่ (Health & Wellness) - 89 บาท
   - การทำนายทั่วไป (General Reading) - ฟรี

4. **ระบบจำกัดการใช้งาน**
   - ดูฟรีได้วันละ 1 ครั้งต่อ 1 หมวดหมู่
   - หากต้องการดูเพิ่มในหมวดหมู่เดียวกัน ต้องชำระเงิน
   - สามารถดูหมวดหมู่อื่นได้ฟรีในวันเดียวกัน (ถ้ายังไม่เคยดู)

5. **การบันทึกคำทำนาย**
   - บันทึกผลการทำนายสำหรับสมาชิกที่ล็อกอิน
   - ดูประวัติการทำนายย้อนหลัง
   - จัดเก็บไพ่และคำทำนายอย่างละเอียด

6. **ระบบชำระเงิน**
   - รองรับ Wallet, PromptPay, Credit Card, Bank Transfer
   - บันทึกประวัติการชำระเงิน
   - ผสานเข้ากับระบบ Payment Gateway ที่มีอยู่

7. **รูปหลังไพ่ที่ปรับแต่งได้**
   - Admin สามารถอัพโหลดรูปหลังไพ่ได้
   - เลือกรูปหลังไพ่เริ่มต้น (Default)
   - รองรับหลายรูปแบบ

8. **ระบบจัดการ Admin**
   - จัดการไพ่ทาโร่ต์ทั้ง 78 ใบ
   - จัดการหมวดหมู่การทำนาย
   - จัดการรูปแบบการเปิดไพ่
   - ดูสถิติและการวิเคราะห์
   - ตั้งค่าระบบ

## 🗄️ โครงสร้างฐานข้อมูล

### ตารางหลัก

1. **tarot_cards** - ไพ่ทาโร่ต์ 78 ใบ
   - Major Arcana (22 ใบ): The Fool, The Magician, The High Priestess, ฯลฯ
   - Minor Arcana (56 ใบ): Wands, Cups, Swords, Pentacles (แต่ละชุด 14 ใบ)

2. **tarot_reading_categories** - หมวดหมู่การทำนาย
   - ชื่อภาษาไทย/อังกฤษ
   - ราคา
   - การตั้งค่าฟรีครั้งแรก

3. **tarot_spread_types** - รูปแบบการเปิดไพ่
   - จำนวนไพ่
   - ตำแหน่งและความหมายของแต่ละตำแหน่ง

4. **tarot_readings** - บันทึกการทำนาย
   - ผู้ใช้
   - หมวดหมู่
   - รูปแบบการเปิดไพ่
   - ยอดชำระ
   - สถานะฟรี/จ่ายเงิน

5. **tarot_reading_cards** - ไพ่ที่ถูกเลือก
   - ไพ่ที่ถูกเลือก
   - ตำแหน่งในการเปิดไพ่
   - สถานะกลับหัว/หัวตั้ง

6. **tarot_user_limits** - ข้อจำกัดการใช้งาน
   - จำกัดการดูฟรีต่อวัน
   - ติดตามจำนวนครั้งที่ใช้

7. **tarot_card_back_images** - รูปหลังไพ่
   - รูปภาพที่อัพโหลด
   - รูปเริ่มต้น

8. **tarot_settings** - การตั้งค่าระบบ
   - เปิด/ปิดระบบ
   - ความเร็วอนิเมชั่น
   - การตั้งค่าต่างๆ

## 📁 โครงสร้างไฟล์

### Models
```
app/Models/
├── TarotCard.php                   # ไพ่ทาโร่ต์
├── TarotCardBackImage.php          # รูปหลังไพ่
├── TarotSpreadType.php             # รูปแบบการเปิดไพ่
├── TarotReadingCategory.php        # หมวดหมู่การทำนาย
├── TarotReading.php                # บันทึกการทำนาย
├── TarotReadingCard.php            # ไพ่ที่ถูกเลือก
├── TarotUserLimit.php              # ข้อจำกัดผู้ใช้
└── TarotSetting.php                # การตั้งค่า
```

### Controllers
```
app/Http/Controllers/
├── TarotReadingController.php                 # Frontend Controller
└── Admin/
    └── TarotManagementController.php          # Admin Controller
```

### Migrations
```
database/migrations/
├── 2025_11_07_000001_create_tarot_cards_table.php
├── 2025_11_07_000002_create_tarot_card_back_images_table.php
├── 2025_11_07_000003_create_tarot_spread_types_table.php
├── 2025_11_07_000004_create_tarot_reading_categories_table.php
├── 2025_11_07_000005_create_tarot_readings_table.php
├── 2025_11_07_000006_create_tarot_reading_cards_table.php
├── 2025_11_07_000007_create_tarot_user_limits_table.php
└── 2025_11_07_000008_create_tarot_settings_table.php
```

### Seeder
```
database/seeders/
└── TarotSystemSeeder.php           # ข้อมูลไพ่ 78 ใบและข้อมูลเริ่มต้น
```

## 🚀 การติดตั้ง

### 1. รัน Migrations

```bash
php artisan migrate
```

### 2. รัน Seeders

```bash
php artisan db:seed --class=TarotSystemSeeder
```

หรือเพิ่มใน DatabaseSeeder:

```php
// database/seeders/DatabaseSeeder.php
public function run()
{
    $this->call([
        TarotSystemSeeder::class,
    ]);
}
```

### 3. สร้างโฟลเดอร์สำหรับรูปภาพ

```bash
php artisan storage:link
mkdir -p public/images/tarot
mkdir -p public/images/tarot/cards
mkdir -p public/images/tarot/card-backs
```

### 4. เพิ่มรูปภาพไพ่ทาโร่ต์

วางรูปไพ่ทาโร่ต์ 78 ใบใน `public/images/tarot/cards/`

## 🎨 Routes

### Frontend Routes (Public)

```
GET  /tarot                          - หน้าหลักทาโร่ต์
GET  /tarot/category/{slug}          - เลือกรูปแบบการเปิดไพ่
POST /tarot/start                    - เริ่มการทำนาย
GET  /tarot/reading/{id}             - แสดงผลการทำนาย
GET  /tarot/payment                  - หน้าชำระเงิน
POST /tarot/payment/process          - ประมวลผลการชำระเงิน
```

### Frontend Routes (Authenticated)

```
POST /tarot/reading/{id}/save        - บันทึกการทำนาย
GET  /tarot/history                  - ประวัติการทำนาย
GET  /tarot/saved                    - การทำนายที่บันทึกไว้
```

### Admin Routes

```
GET    /admin/tarot                              - Dashboard
GET    /admin/tarot/analytics                    - สถิติและการวิเคราะห์

# การจัดการไพ่
GET    /admin/tarot/cards                        - รายการไพ่
GET    /admin/tarot/cards/create                 - เพิ่มไพ่ใหม่
POST   /admin/tarot/cards                        - บันทึกไพ่ใหม่
GET    /admin/tarot/cards/{id}/edit              - แก้ไขไพ่
PUT    /admin/tarot/cards/{id}                   - อัพเดทไพ่
DELETE /admin/tarot/cards/{id}                   - ลบไพ่

# การจัดการหมวดหมู่
GET    /admin/tarot/categories                   - รายการหมวดหมู่
GET    /admin/tarot/categories/create            - เพิ่มหมวดหมู่
POST   /admin/tarot/categories                   - บันทึกหมวดหมู่
GET    /admin/tarot/categories/{id}/edit         - แก้ไขหมวดหมู่
PUT    /admin/tarot/categories/{id}              - อัพเดทหมวดหมู่
DELETE /admin/tarot/categories/{id}              - ลบหมวดหมู่

# การจัดการรูปหลังไพ่
GET    /admin/tarot/card-backs                   - รายการรูปหลังไพ่
POST   /admin/tarot/card-backs                   - อัพโหลดรูปใหม่
POST   /admin/tarot/card-backs/{id}/set-default  - ตั้งเป็นรูปเริ่มต้น
DELETE /admin/tarot/card-backs/{id}              - ลบรูปหลังไพ่

# การจัดการรูปแบบการเปิดไพ่
GET    /admin/tarot/spread-types                 - รายการรูปแบบการเปิดไพ่

# การจัดการบันทึกการทำนาย
GET    /admin/tarot/readings                     - รายการบันทึก
GET    /admin/tarot/readings/{id}                - รายละเอียด
DELETE /admin/tarot/readings/{id}                - ลบบันทึก

# การตั้งค่า
GET    /admin/tarot/settings                     - หน้าตั้งค่า
PUT    /admin/tarot/settings                     - บันทึกการตั้งค่า
```

## 💻 การใช้งาน API

### เริ่มการทำนาย

```javascript
// POST /tarot/start
{
    "category_id": 1,
    "spread_type_id": 2,
    "question": "คำถามของคุณ",
    "use_free": true
}

// Response
{
    "success": true,
    "reading_id": 123,
    "cards": [
        {
            "card": { /* ข้อมูลไพ่ */ },
            "is_reversed": false,
            "position": 1,
            "position_name": "อดีต"
        },
        // ...
    ],
    "redirect_url": "/tarot/reading/123"
}
```

### ตรวจสอบความจำเป็นในการชำระเงิน

หากหมวดหมู่มีราคาและผู้ใช้หมดสิทธิ์ฟรี:

```javascript
// Response
{
    "requires_payment": true,
    "amount": 99.00,
    "category": "การงานและการเงิน",
    "payment_url": "/tarot/payment?..."
}
```

## ⚙️ การตั้งค่าระบบ

### Settings ที่มีให้ใช้งาน

1. **enable_tarot_system** (boolean) - เปิด/ปิดระบบ
2. **allow_guest_readings** (boolean) - อนุญาตให้ผู้เยี่ยมชมทำนาย
3. **show_reversed_cards** (boolean) - แสดงไพ่กลับหัว
4. **enable_ai_interpretation** (boolean) - ใช้ AI ในการแปลความหมาย
5. **save_readings_days** (integer) - จำนวนวันที่เก็บบันทึก
6. **animation_speed** (string) - ความเร็วอนิเมชั่น: slow, medium, fast
7. **require_payment_gateway** (string) - Payment gateway เริ่มต้น

## 📊 สถิติที่แสดงใน Admin Dashboard

- จำนวนการทำนายทั้งหมด
- จำนวนการทำนายวันนี้
- จำนวนการทำนายฟรี
- จำนวนการทำนายแบบชำระเงิน
- รายได้รวม
- จำนวนไพ่ทั้งหมด
- จำนวนไพ่ที่ใช้งานอยู่
- จำนวนหมวดหมู่

### Analytics

- การทำนายแยกตามหมวดหมู่
- การทำนายแยกตามรูปแบบการเปิดไพ่
- ไพ่ยอดนิยม
- กราฟรายได้
- สถิติผู้ใช้

## 🎯 ฟีเจอร์ที่เตรียมพร้อมสำหรับอนาคต

1. **AI-Powered Interpretation**
   - ใช้ AI ในการแปลความหมายไพ่อัตโนมัติ
   - ปรับความหมายตามบริบทของคำถาม

2. **Customizable Spread Layouts**
   - สร้างรูปแบบการเปิดไพ่ของตัวเอง
   - กำหนดตำแหน่งและความหมาย

3. **Multiple Tarot Decks**
   - รองรับชุดไพ่หลายแบบ
   - Rider-Waite, Thoth, Marseille, ฯลฯ

4. **Social Sharing**
   - แชร์ผลการทำนายบน Social Media
   - สร้างการ์ดสวยงาม

5. **Daily Horoscope Integration**
   - เชื่อมโยงกับดวงรายวัน
   - คำแนะนำประจำวัน

## 🔒 ความปลอดภัย

- ตรวจสอบสิทธิ์ผู้ใช้ในการเข้าถึงบันทึก
- จำกัดการทำนายฟรีต่อวัน (ป้องกัน abuse)
- บันทึก IP address สำหรับผู้เยี่ยมชม
- Session-based tracking สำหรับผู้ใช้ที่ยังไม่ login

## 🎨 Customization

### เปลี่ยนสีหมวดหมู่

```php
// Admin -> Tarot -> Categories -> Edit
$category->color = '#FF6B9D'; // Hex color code
```

### เปลี่ยนไอคอนหมวดหมู่

```php
// ใช้ Font Awesome icons
$category->icon = 'fa-heart';
```

### ปรับราคา

```php
$category->price = 99.00; // บาท
$category->is_free_first = true; // ฟรีครั้งแรกต่อวัน
```

## 🐛 Troubleshooting

### ไพ่ไม่แสดงผล
- ตรวจสอบว่ารูปภาพอยู่ใน `public/images/tarot/cards/`
- ตรวจสอบสิทธิ์โฟลเดอร์ (755 หรือ 775)
- รัน `php artisan storage:link`

### การชำระเงินไม่ทำงาน
- ตรวจสอบการตั้งค่า Payment Gateway
- ดูใน `config/payment.php`
- ตรวจสอบ API keys

### อนิเมชั่นช้า
- เปลี่ยนการตั้งค่า `animation_speed` เป็น 'fast'
- เพิ่มประสิทธิภาพ JavaScript

## 📞 การสนับสนุน

หากมีปัญหาหรือข้อสงสัย:
1. ตรวจสอบ error logs: `storage/logs/laravel.log`
2. ตรวจสอบ console ของ browser
3. ดู README นี้อีกครั้ง

## 🎉 สำเร็จ!

คุณได้ติดตั้งระบบหมอดูไพ่ทาโร่ต์เรียบร้อยแล้ว!

เข้าถึงระบบได้ที่:
- Frontend: `https://yourdomain.com/tarot`
- Admin: `https://yourdomain.com/admin/tarot`

ขอให้โชคดีกับการทำนาย! 🔮✨
