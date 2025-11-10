# กฎเกณฑ์การจัดการระบบเมนู - Menu System Rules

## 🚨 กฎสำคัญ - CRITICAL RULES

### ❌ ห้ามทำ (NEVER DO THIS):
1. **ห้าม hard-code เมนูในไฟล์ Blade Component โดยเด็ดขาด**
   - ❌ ห้ามเขียนเมนูแบบ hard-code ใน `millennium-start-menu.blade.php`
   - ❌ ห้ามเขียนเมนูแบบ hard-code ใน `windows-taskbar.blade.php`
   - ❌ ห้ามเขียนเมนูแบบ hard-code ใน `windows-system-tray.blade.php`

2. **ห้าม hard-code เมนูใน Migration Files**
   - ❌ ห้ามใส่ default menu items ใน migration
   - ❌ Migration ใช้สำหรับสร้างโครงสร้าง table เท่านั้น

3. **ห้ามสร้างเมนูใหม่โดยไม่อิงจาก Windows UI Settings**
   - ❌ ห้ามสร้าง array ของเมนูในไฟล์ .blade.php
   - ❌ ห้ามใช้ route() helper โดยตรงในลูป foreach ของเมนู hard-coded

### ✅ ต้องทำ (ALWAYS DO THIS):
1. **ใช้ Windows UI Settings เท่านั้น**
   - ✅ อ่านเมนูจาก `WindowsUiSetting::get()` เสมอ
   - ✅ เก็บเมนูทั้งหมดใน `windows_ui_settings` table

2. **ใช้ Seeder สำหรับข้อมูลเริ่มต้น**
   - ✅ ใส่เมนูทั้งหมดใน `WindowsUiSeeder.php`
   - ✅ รัน seeder เมื่อต้องการอัพเดทเมนู

3. **ให้ผู้ใช้จัดการเมนูผ่าน UI**
   - ✅ ใช้หน้า Admin Panel: `/admin/windows-ui/start-menu`
   - ✅ ให้ผู้ดูแลระบบเปลี่ยนแปลงเมนูผ่าน UI

---

## 📋 โครงสร้างระบบเมนู

### 1. การเก็บข้อมูลเมนู
เมนูทั้งหมดเก็บใน `windows_ui_settings` table โดยมี key ดังนี้:

```php
// Admin Menu
'windows_start_menu_items_admin' => JSON array

// Seller Menu
'windows_start_menu_items_seller' => JSON array

// User Menu
'windows_start_menu_items_user' => JSON array

// Taskbar Apps
'windows_taskbar_apps' => JSON array

// System Tray Icons
'windows_system_tray_icons' => JSON array
```

### 2. โครงสร้าง JSON ของเมนู

#### เมนูหลัก (Start Menu Items)
```json
[
  {
    "icon": "📊",
    "label": "แดชบอร์ด",
    "route": "admin.dashboard",
    "order": 0
  },
  {
    "icon": "👥",
    "label": "ผู้ใช้งาน",
    "route": null,
    "order": 1,
    "submenu": [
      {
        "label": "รายชื่อผู้ใช้",
        "route": "admin.users.index"
      },
      {
        "label": "บทบาท (Roles)",
        "route": "admin.roles.index"
      }
    ]
  }
]
```

**สำคัญ:**
- ใช้ `route` (ชื่อ route) แทน `url` (URL จริง)
- Component จะแปลง route เป็น URL โดยอัตโนมัติ
- ถ้ามี submenu ให้ตั้ง `route: null`

#### Taskbar Apps
```json
[
  {
    "icon": "📊",
    "label": "Dashboard",
    "route": "admin.dashboard",
    "order": 0
  }
]
```

#### System Tray Icons
```json
[
  {
    "icon": "🔔",
    "label": "Notifications",
    "route": "notifications.index",
    "requires_auth": true,
    "requires_guest": false,
    "order": 0
  }
]
```

---

## 🔧 วิธีการเพิ่มเมนูใหม่

### ขั้นตอนที่ 1: อัพเดท Seeder
แก้ไขไฟล์: `database/seeders/WindowsUiSeeder.php`

```php
$adminMenuItems = [
    // ... เมนูเดิม ...

    // เพิ่มเมนูใหม่
    [
        'icon' => '🆕',
        'label' => 'ฟีเจอร์ใหม่',
        'route' => 'admin.new-feature.index',
        'order' => 26,  // ใส่ order ถัดจากเมนูสุดท้าย
        'submenu' => [
            ['label' => 'หน้าย่อย 1', 'route' => 'admin.new-feature.sub1'],
            ['label' => 'หน้าย่อย 2', 'route' => 'admin.new-feature.sub2'],
        ]
    ],
];
```

### ขั้นตอนที่ 2: รัน Seeder
```bash
php artisan db:seed --class=WindowsUiSeeder
```

**หมายเหตุ:** Seeder ใช้ Smart Seeding Strategy
- Fresh Install: seed ทุกอย่าง
- Update Mode: เพิ่มเฉพาะ settings ที่ขาดหาย (รักษาการปรับแต่งของผู้ใช้)

### ขั้นตอนที่ 3: ตรวจสอบผลลัพธ์
1. เข้าหน้า Admin: `/admin/windows-ui/start-menu`
2. ตรวจสอบว่าเมนูใหม่ปรากฏ
3. ทดสอบการคลิกและการทำงาน

---

## 📝 ตัวอย่างการใช้งาน

### ✅ ตัวอย่างที่ถูกต้อง

#### Blade Component (millennium-start-menu.blade.php)
```php
@php
    // โหลดเมนูจาก Windows UI Settings
    $menuItemsRaw = WindowsUiSetting::get("windows_start_menu_items_{$type}", []);

    // แปลง route เป็น URL
    $menuItems = collect($menuItemsRaw)->map(function($item) {
        if (isset($item['route']) && $item['route']) {
            try {
                $item['url'] = route($item['route']);
            } catch (\Exception $e) {
                $item['url'] = '#';
            }
        }

        // Process submenu...

        return $item;
    })->sortBy('order')->values()->toArray();
@endphp

<!-- แสดงเมนู -->
@foreach($menuItems as $index => $item)
    <!-- ... render menu ... -->
@endforeach
```

### ❌ ตัวอย่างที่ผิด

```php
@php
    // ❌ ห้ามทำแบบนี้!
    $menuItems = [
        ['icon' => '📊', 'label' => 'แดชบอร์ด', 'url' => route('admin.dashboard')],
        ['icon' => '👥', 'label' => 'ผู้ใช้งาน', 'url' => route('admin.users.index')],
        // ...
    ];
@endphp
```

---

## 🎯 Best Practices

### 1. การตั้งชื่อ Route
```php
// ✅ ดี - ชัดเจน สอดคล้องกัน
'admin.users.index'
'admin.users.create'
'seller.products.index'
'user.wallet.index'

// ❌ ไม่ดี - ไม่สอดคล้อง
'users'
'admin-users'
'AdminUsersIndex'
```

### 2. การเรียงลำดับเมนู
```php
// ใช้ order เป็นตัวเลข
'order' => 0   // เมนูแรก
'order' => 1   // เมนูที่สอง
'order' => 2   // เมนูที่สาม

// Component จะเรียงตาม order โดยอัตโนมัติ
```

### 3. การจัดการ Icon
```php
// ✅ ใช้ emoji
'icon' => '📊'
'icon' => '👥'
'icon' => '🛒'

// หรือใช้ icon class (ถ้ารองรับ)
'icon' => 'fa-dashboard'
```

### 4. การจัดการ Submenu
```php
// เมนูที่มี submenu
[
    'icon' => '👥',
    'label' => 'ผู้ใช้งาน',
    'route' => null,  // ← ต้องเป็น null
    'order' => 1,
    'submenu' => [
        ['label' => 'รายชื่อผู้ใช้', 'route' => 'admin.users.index'],
        ['label' => 'บทบาท', 'route' => 'admin.roles.index'],
    ]
]

// เมนูที่ไม่มี submenu
[
    'icon' => '📊',
    'label' => 'แดชบอร์ด',
    'route' => 'admin.dashboard',  // ← ใส่ route ตรงนี้
    'order' => 0
]
```

---

## 🔄 การอัพเดทเมนูในอนาคต

### สถานการณ์ที่ 1: เพิ่มเมนูใหม่
1. แก้ไข `WindowsUiSeeder.php` เพิ่มเมนูใหม่
2. รัน `php artisan db:seed --class=WindowsUiSeeder`
3. Seeder จะเพิ่มเฉพาะเมนูใหม่ (ไม่ลบเมนูเดิม)

### สถานการณ์ที่ 2: แก้ไขเมนูที่มีอยู่
1. ผู้ใช้สามารถแก้ไขผ่าน Admin UI: `/admin/windows-ui/start-menu`
2. หรือแก้ไขใน Seeder แล้วรัน migration:fresh (ข้อมูลทั้งหมดจะหาย!)

### สถานการณ์ที่ 3: ลบเมนู
1. ผู้ใช้สามารถลบผ่าน Admin UI
2. หรือสร้าง migration script ใหม่เพื่อลบเมนูที่ไม่ต้องการ

---

## 🚀 การทดสอบ

### 1. ทดสอบการโหลดเมนู
```bash
# เข้า tinker
php artisan tinker

# ทดสอบโหลดเมนู admin
App\Models\WindowsUiSetting::get('windows_start_menu_items_admin');

# ทดสอบโหลดเมนู seller
App\Models\WindowsUiSetting::get('windows_start_menu_items_seller');

# ทดสอบโหลดเมนู user
App\Models\WindowsUiSetting::get('windows_start_menu_items_user');
```

### 2. ทดสอบการแสดงผล
1. Login ด้วย admin account
2. เปิด start menu
3. ตรวจสอบว่าเมนูแสดงครบ
4. คลิกทุกเมนูเพื่อทดสอบ route

---

## 📚 ไฟล์ที่เกี่ยวข้อง

### 1. Seeder
- `database/seeders/WindowsUiSeeder.php` - ข้อมูลเริ่มต้นของเมนูทั้งหมด

### 2. Model
- `app/Models/WindowsUiSetting.php` - Model สำหรับจัดการ Windows UI Settings

### 3. Components
- `resources/views/components/millennium-start-menu.blade.php` - Start Menu Component
- `resources/views/components/windows-taskbar.blade.php` - Taskbar Component
- `resources/views/components/windows-system-tray.blade.php` - System Tray Component

### 4. Controllers
- `app/Http/Controllers/Admin/WindowsUiController.php` - Controller สำหรับจัดการ Windows UI

### 5. Routes
- `routes/admin.php` (lines 205-221) - Admin routes สำหรับจัดการ Windows UI

### 6. Views (Admin UI)
- `resources/views/admin/windows-ui/start-menu.blade.php` - หน้าจัดการ Start Menu
- `resources/views/admin/windows-ui/taskbar-apps.blade.php` - หน้าจัดการ Taskbar
- `resources/views/admin/windows-ui/system-tray.blade.php` - หน้าจัดการ System Tray

---

## ⚡ Performance Tips

### 1. Caching
Windows UI Settings มี cache อยู่แล้ว:
```php
// WindowsUiSetting::get() ใช้ cache อัตโนมัติ
$menuItems = WindowsUiSetting::get('windows_start_menu_items_admin');
```

### 2. ลดการ Query
Component โหลดเมนูครั้งเดียวต่อ page load:
```php
// ✅ ดี - query ครั้งเดียว
$menuItemsRaw = WindowsUiSetting::get("windows_start_menu_items_{$type}", []);

// ❌ ไม่ดี - query หลายครั้ง
foreach ($types as $type) {
    $items = WindowsUiSetting::get("windows_start_menu_items_{$type}");
}
```

---

## 🐛 Troubleshooting

### ปัญหา: เมนูไม่แสดง
**วิธีแก้:**
1. ตรวจสอบว่า seeder รันแล้ว: `php artisan db:seed --class=WindowsUiSeeder`
2. เช็คในฐานข้อมูล: `select * from windows_ui_settings where key like '%menu%';`
3. Clear cache: `php artisan cache:clear`

### ปัญหา: Route ไม่ทำงาน
**วิธีแก้:**
1. ตรวจสอบว่า route มีอยู่จริง: `php artisan route:list | grep "admin.users"`
2. ตรวจสอบชื่อ route ใน seeder ให้ตรงกับใน routes file
3. Run route cache: `php artisan route:cache`

### ปัญหา: Seeder ไม่อัพเดท
**วิธีแก้:**
1. ลบข้อมูลเก่า: `delete from windows_ui_settings where key like 'windows_start_menu_%';`
2. รัน seeder ใหม่: `php artisan db:seed --class=WindowsUiSeeder`

---

## 📖 สรุป

**กฎสำคัญที่ต้องจำ:**
1. ✅ ใช้ Windows UI Settings เสมอ
2. ❌ ห้าม hard-code เมนูใน component
3. ✅ ใช้ Seeder สำหรับข้อมูลเริ่มต้น
4. ✅ ให้ผู้ใช้จัดการผ่าน Admin UI
5. ✅ ใช้ `route` แทน `url` ใน JSON

**ติดต่อ:**
- เอกสารนี้สร้างขึ้นเพื่อให้ Claude และทีมพัฒนาใช้เป็นแนวทางในการทำงานกับระบบเมนู
- อัพเดทล่าสุด: 2025-01-10
- Version: 1.0.0

---

**จัดทำโดย:** Claude AI
**วันที่:** 2025-01-10
**โปรเจกต์:** TP-Affiliate Platform
