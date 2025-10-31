# ระบบจัดการการแจ้งเตือนสำหรับแอดมิน

## ภาพรวม

ระบบจัดการการแจ้งเตือนที่อนุญาตให้แอดมินสามารถ:
- ส่งข้อความแจ้งเตือนไปหาสมาชิกรายบุคคลหรือทั้งหมด (ประกาศ)
- กำหนดให้การแจ้งเตือนแสดงเป็นป๊อบอัพทันที
- สมาชิกจะเห็นการแจ้งเตือนผ่านไอคอนกระดิ่งและป๊อบอัพแบบลอย

## ฟีเจอร์หลัก

### 1. ระบบส่งการแจ้งเตือน
- ✅ ส่งให้สมาชิกรายบุคคล
- ✅ ส่งให้หลายคน
- ✅ ส่งให้ทั้งหมด (ประกาศ)
- ✅ กำหนดระดับความสำคัญ (ต่ำ, ปกติ, สูง, เร่งด่วน)
- ✅ เลือกประเภทการแจ้งเตือน (ระบบ, ประกาศ, แจ้งเตือน, กระเป๋าเงิน, คอมมิชชั่น)
- ✅ ตั้งค่าให้แสดงป๊อบอัพทันที (show_immediately)

### 2. การแสดงผลฝั่งผู้ใช้
- ✅ ไอคอนกระดิ่งแสดงจำนวนการแจ้งเตือนที่ยังไม่อ่าน
- ✅ ดรอปดาวน์แสดงรายการการแจ้งเตือน
- ✅ ป๊อบอัพแบบลอยสำหรับการแจ้งเตือนแบบ immediate
- ✅ ป๊อบอัพจะหายไปอัตโนมัติหลังจาก 8 วินาที
- ✅ เสียงแจ้งเตือนเมื่อมีการแจ้งเตือนใหม่

## การติดตั้ง

### 1. Run Migration

```bash
php artisan migrate
```

Migration นี้จะเพิ่มฟิลด์ต่อไปนี้ใน `notifications` table:
- `show_immediately` - แสดงป๊อบอัพทันที
- `is_broadcast` - เป็นการแจ้งเตือนแบบประกาศหรือไม่
- `shown_at` - เวลาที่แสดงป๊อบอัพแล้ว

### 2. เพิ่ม Component ใน Layout

#### สำหรับ User Layout

แก้ไขไฟล์ `resources/views/layouts/user.blade.php` หรือ layout ที่ใช้:

```blade
<!-- เพิ่มใน header หรือ navigation -->
@auth
    <x-notification-bell />
@endauth

<!-- เพิ่มก่อน closing body tag -->
@auth
    <x-immediate-notification-popup />
@endauth
```

#### สำหรับ Admin Layout (ถ้าต้องการ)

```blade
<!-- เพิ่มใน header หรือ navigation -->
<x-notification-bell />

<!-- เพิ่มก่อน closing body tag -->
<x-immediate-notification-popup />
```

## การใช้งาน

### สำหรับแอดมิน

#### 1. เข้าสู่หน้าจัดการการแจ้งเตือน

```
/admin/notifications
```

#### 2. สร้างการแจ้งเตือนใหม่

1. คลิกปุ่ม "ส่งการแจ้งเตือนใหม่"
2. เลือกผู้รับ:
   - สมาชิกทั้งหมด (ประกาศ)
   - สมาชิกรายบุคคล
   - หลายคน
3. กรอกข้อมูล:
   - ประเภท (ระบบ, ประกาศ, แจ้งเตือน, กระเป๋าเงิน, คอมมิชชั่น)
   - หัวข้อ
   - ข้อความ
   - URL ปุ่มดำเนินการ (ถ้ามี)
   - ข้อความปุ่ม
   - ระดับความสำคัญ
   - ไอคอน (emoji)
   - สี
4. เลือกตัวเลือก:
   - ✅ ทำเครื่องหมายเป็นสำคัญ
   - ✅ **แสดงป๊อบอัพทันที** (ถ้าต้องการให้แสดงทันทีเมื่อสมาชิกอยู่ในระบบ)
5. คลิก "ส่งการแจ้งเตือน"

### สำหรับผู้ใช้

#### 1. ดูการแจ้งเตือนผ่านกระดิ่ง

- คลิกที่ไอคอนกระดิ่งมุมขวาบน
- จะเห็นรายการการแจ้งเตือนล่าสุด
- คลิกที่การแจ้งเตือนเพื่อทำเครื่องหมายว่าอ่านแล้ว

#### 2. ป๊อบอัพแบบ Immediate

- เมื่อแอดมินส่งการแจ้งเตือนแบบ "แสดงทันที"
- ป๊อบอัพจะขึ้นที่มุมขวาบน
- จะหายไปอัตโนมัติหลังจาก 8 วินาที
- สามารถคลิกปิดได้ด้วยตนเอง
- มี progress bar แสดงเวลาที่เหลือ

#### 3. ดูการแจ้งเตือนทั้งหมด

```
/user/notifications
```

## API Endpoints

### สำหรับผู้ใช้

```
GET  /user/notifications          - ดูรายการการแจ้งเตือนทั้งหมด
GET  /user/notifications/unread   - ดูการแจ้งเตือนที่ยังไม่อ่าน (สำหรับกระดิ่ง)
GET  /user/notifications/immediate - ดูการแจ้งเตือนแบบ immediate ที่ยังไม่แสดง
POST /user/notifications/{id}/read - ทำเครื่องหมายว่าอ่านแล้ว
POST /user/notifications/read-all  - ทำเครื่องหมายอ่านทั้งหมด
```

### สำหรับแอดมิน

```
GET    /admin/notifications              - ดูรายการการแจ้งเตือนทั้งหมด
GET    /admin/notifications/create       - ฟอร์มสร้างการแจ้งเตือนใหม่
POST   /admin/notifications              - บันทึกการแจ้งเตือนใหม่
GET    /admin/notifications/{id}         - ดูรายละเอียด
DELETE /admin/notifications/{id}         - ลบการแจ้งเตือน
POST   /admin/notifications/bulk-delete  - ลบหลายรายการพร้อมกัน
GET    /admin/notifications/statistics   - ดูสถิติการแจ้งเตือน
```

## โครงสร้างไฟล์

### Backend

```
app/
├── Http/
│   └── Controllers/
│       ├── Admin/
│       │   └── NotificationManagementController.php  (จัดการการแจ้งเตือนฝั่งแอดมิน)
│       └── NotificationController.php                (API สำหรับผู้ใช้)
├── Models/
│   └── Notification.php                              (Model)
└── Services/
    └── NotificationService.php                       (Business Logic)

database/
└── migrations/
    └── 2025_10_31_000001_add_show_immediately_to_notifications_table.php
```

### Frontend

```
resources/views/
├── admin/
│   └── notifications/
│       ├── index.blade.php     (รายการการแจ้งเตือน)
│       ├── create.blade.php    (ฟอร์มส่งการแจ้งเตือน)
│       └── show.blade.php      (รายละเอียด)
└── components/
    ├── notification-bell.blade.php              (ไอคอนกระดิ่ง + ดรอปดาวน์)
    └── immediate-notification-popup.blade.php   (ป๊อบอัพแบบลอย)
```

### Routes

```
routes/
├── admin.php   (Admin routes)
└── user.php    (User routes)
```

## ฟีเจอร์เพิ่มเติม

### NotificationService Methods

```php
// ส่งให้ผู้ใช้รายคน
$notificationService->create(
    $user,
    'announcement',
    'หัวข้อ',
    'ข้อความ',
    [],              // data
    null,            // action_url
    null,            // action_text
    'normal',        // priority
    false,           // is_important
    true             // show_immediately ← เพิ่มใหม่!
);

// ส่งแบบ broadcast (ทั้งหมดหรือหลายคน)
$notificationService->broadcast(
    'announcement',
    'ประกาศสำคัญ',
    'มีการปรับปรุงระบบ',
    [],              // data
    null,            // action_url
    null,            // action_text
    'urgent',        // priority
    true,            // is_important
    true,            // show_immediately ← เพิ่มใหม่!
    '📢',            // icon
    'blue',          // color
    [1, 2, 3]        // user_ids (null = ทั้งหมด)
);

// ดึงการแจ้งเตือนแบบ immediate
$immediateNotifications = $notificationService->getImmediateNotifications($user);

// ทำเครื่องหมายว่าแสดงแล้ว
$notificationService->markAsShown($notification);
```

## การปรับแต่ง

### 1. เปลี่ยนเวลาหายของป๊อบอัพ

แก้ไขไฟล์ `resources/views/components/immediate-notification-popup.blade.php`:

```javascript
autoDismissTime: 8000, // เปลี่ยนเป็น 10000 = 10 วินาที
```

### 2. เปลี่ยนความถี่ตรวจสอบการแจ้งเตือน

แก้ไขไฟล์ `resources/views/components/notification-bell.blade.php`:

```javascript
setInterval(() => {
    this.loadNotifications();
    this.checkImmediateNotifications();
}, 30000); // เปลี่ยนเป็น 60000 = 1 นาที
```

### 3. ปิดเสียงแจ้งเตือน

แก้ไขไฟล์ `resources/views/components/immediate-notification-popup.blade.php`:

```javascript
// คอมเมนต์บรรทัดนี้
// this.playNotificationSound();
```

## ตัวอย่างการใช้งาน

### ส่งประกาศให้ทุกคน

```php
use App\Services\NotificationService;

$notificationService = app(NotificationService::class);

$notificationService->broadcast(
    type: 'announcement',
    title: '🎉 ระบบใหม่เปิดให้ใช้งานแล้ว!',
    message: 'เราได้เพิ่มระบบกระเป๋าเงินใหม่ สามารถเติมเงินและถอนเงินได้ง่ายขึ้น',
    actionUrl: '/user/wallet',
    actionText: 'ดูกระเป๋าเงิน',
    priority: 'high',
    isImportant: true,
    showImmediately: true,
    icon: '🎉',
    color: 'green'
);
```

### ส่งแจ้งเตือนให้สมาชิกเฉพาะ

```php
$users = User::whereIn('id', [1, 5, 10])->get();

foreach ($users as $user) {
    $notificationService->create(
        user: $user,
        type: 'system',
        title: 'แจ้งเตือนสำคัญ',
        message: 'กรุณาอัปเดตข้อมูลของคุณ',
        showImmediately: true
    );
}
```

## การแก้ไขปัญหา

### ป๊อบอัพไม่ขึ้น

1. ตรวจสอบว่าเพิ่ม component แล้ว:
   ```blade
   <x-immediate-notification-popup />
   ```

2. ตรวจสอบ Console ใน Browser (F12) หาข้อผิดพลาด

3. ตรวจสอบว่าติ๊กถูกที่ "แสดงป๊อบอัพทันที" เมื่อสร้างการแจ้งเตือน

### กระดิ่งไม่แสดงจำนวน

1. ตรวจสอบว่าเพิ่ม component แล้ว:
   ```blade
   <x-notification-bell />
   ```

2. ตรวจสอบว่า Alpine.js โหลดแล้ว

3. ดู Console หาข้อผิดพลาด API

### Migration ล้มเหลว

ถ้า migration ล้มเหลว ให้ rollback แล้วลองใหม่:

```bash
php artisan migrate:rollback
php artisan migrate
```

## สรุป

ระบบนี้ให้แอดมินควบคุมการสื่อสารกับสมาชิกได้อย่างมีประสิทธิภาพ พร้อมด้วย:
- ✅ หน้าจัดการที่ใช้งานง่าย
- ✅ ส่งถึงรายบุคคลหรือทั้งหมด
- ✅ ป๊อบอัพแบบลอยที่สวยงาม
- ✅ การแจ้งเตือนแบบ real-time
- ✅ ปรับแต่งได้ง่าย

สนุกกับการใช้งาน! 🎉
