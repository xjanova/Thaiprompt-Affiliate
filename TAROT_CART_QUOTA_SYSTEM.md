# ระบบตะกร้าและโควต้าทาโร่ต์

## 📦 ระบบตะกร้า (Shopping Cart System)

### ภาพรวม
ผู้ใช้สามารถเลือกหลายรายการทำนายก่อนชำระเงินในครั้งเดียว รองรับทั้ง Guest และ Member

### คุณสมบัติ
- ✅ เพิ่มรายการเข้าตะกร้าได้หลายรายการ
- ✅ เลือกหมวดหมู่และรูปแบบการเปิดไพ่ต่างกันได้
- ✅ แต่ละรายการมีคำถามเป็นของตัวเอง
- ✅ แสดงสรุปราคารวม
- ✅ Checkout แบบรวมยอด
- ✅ รองรับหลายช่องทางชำระเงิน

### ไฟล์ที่เกี่ยวข้อง

#### Model
```php
app/Models/TarotCartItem.php
```
**ฟิลด์:**
- `user_id` - สำหรับ member
- `session_id` - สำหรับ guest
- `ip_address` - tracking IP
- `category_id` - หมวดหมู่ที่เลือก
- `spread_type_id` - รูปแบบการเปิดไพ่
- `question` - คำถามของผู้ใช้
- `price` - ราคา (category + spread type)

**Methods สำคัญ:**
```php
// ดึงรายการในตะกร้า
TarotCartItem::getCartItems($userId, $sessionId, $ipAddress)

// คำนวณยอดรวม
TarotCartItem::getCartTotal($userId, $sessionId, $ipAddress)

// นับจำนวนรายการ
TarotCartItem::getCartCount($userId, $sessionId, $ipAddress)

// ล้างตะกร้า
TarotCartItem::clearCart($userId, $sessionId, $ipAddress)
```

#### Controller
```php
app/Http/Controllers/TarotCartController.php
```

**Routes:**
| Method | URL | Action | คำอธิบาย |
|--------|-----|--------|----------|
| GET | `/tarot/cart` | index() | แสดงตะกร้า |
| POST | `/tarot/cart/add` | addToCart() | เพิ่มรายการ |
| DELETE | `/tarot/cart/remove/{id}` | removeItem() | ลบรายการ |
| POST | `/tarot/cart/clear` | clearCart() | ล้างตะกร้า |
| GET | `/tarot/cart/checkout` | checkout() | หน้า checkout |
| POST | `/tarot/cart/checkout/process` | processCheckout() | ดำเนินการชำระเงิน |

#### Views
```
resources/views/frontend/tarot/cart.blade.php
resources/views/frontend/tarot/checkout.blade.php
```

---

## 🎯 ระบบโควต้า (Quota Management System)

### ภาพรวม
จำกัดจำนวนครั้งการทำนายต่อวัน แยกระหว่าง Guest และ Member พร้อม IP tracking

### โควต้าแบ่งเป็น 2 ระดับ

#### 1. **Guest (ไม่ต้องสมัคร)**
- ติดตามโดย: **IP Address + Cookie (Session)**
- ข้อจำกัดมากกว่า Member
- Default: **1 ครั้ง/วัน/หมวดหมู่**
- ตั้งค่าได้ที่: `tarot_settings.guest_daily_limit`

#### 2. **Member (สมาชิก)**
- ติดตามโดย: **User ID**
- ได้โควต้ามากกว่า
- Default: **5 ครั้ง/วัน/หมวดหมู่**
- ตั้งค่าได้ที่: `tarot_settings.member_daily_limit`

### การทำงานของระบบ

#### ตรวจสอบโควต้า
```php
use App\Models\TarotUserLimit;

$userId = Auth::id(); // null ถ้าเป็น guest
$sessionId = session()->getId();
$ipAddress = request()->ip();
$categoryId = 1;

// เช็คว่าใช้ฟรีได้หรือไม่
$canUseFree = TarotUserLimit::canUseFreeReading(
    $categoryId,
    $userId,
    $sessionId,
    $ipAddress
);

// ดูโควต้าที่เหลือ
$remaining = TarotUserLimit::getRemainingQuota(
    $categoryId,
    $userId,
    $sessionId,
    $ipAddress
);
```

#### เพิ่มการนับ
```php
// เมื่อทำนายฟรี
TarotUserLimit::incrementFreeReading(
    $categoryId,
    $userId,
    $sessionId,
    $ipAddress
);

// เมื่อทำนายแบบเสียเงิน
TarotUserLimit::incrementPaidReading(
    $categoryId,
    $userId,
    $sessionId,
    $ipAddress
);
```

### Database Schema

#### tarot_user_limits
```sql
- user_id (nullable) - สำหรับ member
- session_id (nullable) - สำหรับ guest
- ip_address (nullable) - tracking IP
- category_id - หมวดหมู่
- limit_date (date) - วันที่
- free_count (int) - จำนวนครั้งฟรีที่ใช้ไป
- paid_count (int) - จำนวนครั้งจ่ายเงินที่ใช้ไป
```

### การตั้งค่าโควต้า

#### ผ่าน Database Seeder
```php
TarotSetting::set('guest_daily_limit', 1, 'integer', 'จำนวนครั้งฟรีต่อวันสำหรับ Guest');
TarotSetting::set('member_daily_limit', 5, 'integer', 'จำนวนครั้งฟรีต่อวันสำหรับ Member');
TarotSetting::set('enable_ip_tracking', true, 'boolean', 'เปิดใช้ IP tracking');
TarotSetting::set('enable_cart', true, 'boolean', 'เปิดใช้ระบบตะกร้า');
```

#### ผ่าน Admin Panel
```
/admin/tarot/settings

- Guest Daily Limit: [1]
- Member Daily Limit: [5]
- Enable IP Tracking: ✓
- Enable Cart: ✓
```

---

## 💰 ระบบกำหนดราคา (Pricing System)

### ราคาแบ่งเป็น 2 ส่วน

#### 1. ราคาหมวดหมู่ (Category Price)
```php
// ใน tarot_reading_categories table
$category->price = 99.00; // บาท
```

#### 2. ราคารูปแบบ (Spread Type Base Price)
```php
// ใน tarot_spread_types table
$spreadType->base_price = 49.00; // บาท
```

#### ราคาสุทธิ = ราคาหมวดหมู่ + ราคารูปแบบ
```php
$totalPrice = $category->price + $spreadType->base_price;
// เช่น: 99 + 49 = 148 บาท
```

### ตัวอย่างการกำหนดราคา

| หมวดหมู่ | ราคาหมวด | รูปแบบ | ราคารูปแบบ | รวม |
|---------|----------|--------|----------|-----|
| ความรัก | 0 | ไพ่ใบเดียว | 0 | ฟรี |
| ความรัก | 0 | Celtic Cross | 199 | 199 |
| การเงิน | 99 | ไพ่ใบเดียว | 0 | 99 |
| การเงิน | 99 | Celtic Cross | 199 | 298 |

### การชำระเงิน

#### ช่องทางที่รองรับ
1. **💰 กระเป๋าเงิน (Wallet)** - หักจาก wallet_balance
2. **📱 พร้อมเพย์ (PromptPay)** - QR Code
3. **💳 บัตรเครดิต (Credit Card)** - Visa, Mastercard, JCB
4. **🏦 โอนเงิน (Bank Transfer)** - โอนผ่านธนาคาร

#### สถานะการชำระเงิน
```php
// ใน tarot_readings table
payment_status enum:
- 'free' - ทำนายฟรี
- 'pending' - รอชำระเงิน
- 'paid' - จ่ายแล้ว
- 'failed' - ชำระไม่สำเร็จ
```

---

## 🎨 User Interface

### หน้า Category (เลือกรูปแบบ)
**แสดงข้อมูล:**
- ✅ โควต้าที่เหลือ (เรียลไทม์)
- ✅ ราคาหมวดหมู่
- ✅ ราคาแต่ละรูปแบบ
- ✅ ปุ่ม 2 แบบ:
  - **⚡ ทำนายทันที** - ไปหน้าทำนายเลย
  - **🛒 เพิ่มเข้าตะกร้า** - เก็บไว้ก่อน

### หน้า Cart (ตะกร้า)
**คุณสมบัติ:**
- แสดงรายการทั้งหมด
- แสดงหมวดหมู่ + รูปแบบ + คำถาม
- แสดงราคาแต่ละรายการ
- สรุปยอดรวม
- ปุ่มลบรายการ
- ปุ่มล้างตะกร้า
- ปุ่มชำระเงิน
- ปุ่มเลือกรายการเพิ่ม

### หน้า Checkout (ชำระเงิน)
**คุณสมบัติ:**
- เลือกช่องทางชำระเงิน
- แสดงรายการสั่งซื้อ
- สรุปยอดชำระ
- Security notice (SSL)
- ปุ่มยืนยันการชำระเงิน
- ปุ่มกลับไปตะกร้า

---

## 🔧 การติดตั้งและใช้งาน

### 1. Run Migration
```bash
php artisan migrate --force
```

**Migration ใหม่:**
- `2025_11_08_000001_add_cart_and_pricing_to_tarot_system.php`

**Tables ที่สร้าง/แก้ไข:**
- `tarot_cart_items` (ใหม่)
- `tarot_readings` (เพิ่ม price, payment_status, etc.)
- `tarot_user_limits` (เพิ่ม ip_address)
- `tarot_settings` (เพิ่ม quota settings)
- `tarot_spread_types` (เพิ่ม base_price)

### 2. Seed Settings
```bash
php artisan tinker
```

```php
use App\Models\TarotSetting;

TarotSetting::set('guest_daily_limit', 1, 'integer');
TarotSetting::set('member_daily_limit', 5, 'integer');
TarotSetting::set('enable_ip_tracking', true, 'boolean');
TarotSetting::set('enable_cart', true, 'boolean');
```

### 3. กำหนดราคา (Optional)

```php
use App\Models\TarotReadingCategory;
use App\Models\TarotSpreadType;

// กำหนดราคาหมวดหมู่
$category = TarotReadingCategory::where('slug', 'career-finance')->first();
$category->price = 149.00;
$category->save();

// กำหนดราคารูปแบบ
$spread = TarotSpreadType::where('name_en', 'Celtic Cross')->first();
$spread->base_price = 199.00;
$spread->save();
```

---

## 📊 Flow การทำงาน

### Flow 1: ทำนายทันที (Direct Reading)
```
1. เลือกหมวดหมู่ → 2. เลือกรูปแบบ → 3. ใส่คำถาม
   → 4. คลิก "ทำนายทันที"
   → 5. ตรวจสอบโควต้า
   → 6a. ถ้ามีโควต้า: ทำนายเลย (ฟรี)
   → 6b. ถ้าหมดโควต้า: ไปหน้าชำระเงิน
   → 7. แสดงผลทำนาย
```

### Flow 2: เพิ่มเข้าตะกร้า (Cart Checkout)
```
1. เลือกหมวดหมู่ → 2. เลือกรูปแบบ → 3. ใส่คำถาม
   → 4. คลิก "เพิ่มเข้าตะกร้า"
   → 5. เลือกต่อหรือไปตะกร้า
   → 6. (เพิ่มรายการอื่นๆ ได้)
   → 7. ไปหน้าตะกร้า
   → 8. คลิก "ชำระเงิน"
   → 9. เลือกช่องทางชำระเงิน
   → 10. ยืนยันการชำระเงิน
   → 11. สร้างการทำนายทั้งหมดพร้อมกัน
   → 12. แสดงผลทำนายรายการแรก
```

### Flow 3: ตรวจสอบโควต้า
```
1. เข้าหน้าหมวดหมู่
   → 2. ระบบโหลด user_id/session_id/ip_address
   → 3. Query tarot_user_limits
   → 4. คำนวณโควต้าที่เหลือ
   → 5. แสดงผลบนหน้าเว็บ
      - "โควต้าฟรีเหลือ: X ครั้ง/วัน"
      - หรือ "หมดโควต้าฟรีวันนี้แล้ว"
```

---

## 🛡️ Security & Best Practices

### IP Tracking
- ✅ เก็บ IP แบบ IPv4/IPv6 (45 characters)
- ✅ Hash ไม่ได้ (ต้องค้นหาได้)
- ✅ ใช้ร่วมกับ Session ID

### Guest Tracking
```php
// ระบบจะตรวจสอบ
if (user_id) {
    // ใช้ user_id อย่างเดียว
} else {
    // ใช้ session_id OR ip_address
}
```

### Rate Limiting
- ป้องกัน spam โดยจำกัดตาม IP
- Reset ทุกวันตอน 00:00
- ตั้งค่าได้ผ่าน settings

---

## 🔍 Debugging & Troubleshooting

### ตรวจสอบโควต้าปัจจุบัน
```sql
SELECT * FROM tarot_user_limits
WHERE limit_date = CURDATE()
AND (user_id = ? OR session_id = ? OR ip_address = ?);
```

### ตรวจสอบรายการในตะกร้า
```sql
SELECT * FROM tarot_cart_items
WHERE user_id = ? OR session_id = ?;
```

### รีเซ็ตโควต้า (สำหรับทดสอบ)
```sql
DELETE FROM tarot_user_limits WHERE limit_date = CURDATE();
```

---

## 📈 สถิติและรายงาน

### Admin Dashboard สามารถดู:
- จำนวนการทำนายฟรีต่อวัน
- จำนวนการทำนายแบบจ่ายเงิน
- รายได้จากแต่ละหมวดหมู่
- Guest vs Member usage
- IP addresses ที่ active

---

## ✅ Checklist การใช้งาน

### ก่อนเปิดใช้งาน
- [ ] Run migration
- [ ] Seed tarot cards (78 ใบ)
- [ ] Set quota limits
- [ ] กำหนดราคาหมวดหมู่
- [ ] กำหนดราคารูปแบบ (optional)
- [ ] ทดสอบ guest quota
- [ ] ทดสอบ member quota
- [ ] ทดสอบ cart system
- [ ] ทดสอบ checkout
- [ ] เชื่อมต่อ payment gateway

### หลังเปิดใช้งาน
- [ ] Monitor ปริมาณการใช้งาน
- [ ] ตรวจสอบ rate limiting
- [ ] วิเคราะห์ conversion rate
- [ ] ปรับโควต้าตามความเหมาะสม

---

**เวอร์ชัน:** 2.14.0
**วันที่:** 2025-11-08
**สร้างโดย:** Claude AI
