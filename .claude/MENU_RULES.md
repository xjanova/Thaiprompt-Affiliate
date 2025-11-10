# กฎเกณฑ์การจัดการระบบเมนู - Menu System Rules (HARD-CODED APPROACH)

## 🚨 การเปลี่ยนแปลงสำคัญ - CRITICAL CHANGE

**วันที่อัพเดท:** 2025-01-10
**เวอร์ชั่น:** 3.0.0 (Hard-Coded Menus)

### ⚠️ ประกาศการเปลี่ยนแปลง

เนื่องจาก**ระบบเมนูแบบ Database-Driven มีความซับซ้อนเกินไป** สำหรับการพัฒนาและบำรุงรักษา ระบบจึงได้เปลี่ยนกลับไปใช้ **Hard-Coded Menu System** แทน

---

## 🎯 กฎใหม่ - NEW RULES

### ✅ ต้องทำ (ALWAYS DO THIS):

#### 1. **Hard-Code เมนูทั้งหมดใน Component**
   - ✅ เขียนเมนูแบบ hard-code ใน `millennium-start-menu.blade.php`
   - ✅ กำหนด array ของเมนูสำหรับแต่ละ role (admin, seller, user)
   - ✅ ใช้ `route()` helper สำหรับสร้าง URL
   - ✅ เมนูทั้งหมดอยู่ในโค้ดเท่านั้น ไม่ใช้ database

#### 2. **เพิ่มเมนูใหม่ต่อเมื่อพัฒนาฟีเจอร์ใหม่**
   - ✅ เมื่อสร้างฟีเจอร์ใหม่ ให้เพิ่มเมนูโดยตรงใน component
   - ✅ ไม่ต้องรัน seeder หรือ migration
   - ✅ ไม่ต้องผ่าน Admin UI
   - ✅ แก้ไขโค้ดโดยตรงได้ทันที

#### 3. **รักษาการตั้งค่า Visual Customization ใน Database**
   - ✅ สี, ขนาด, ตำแหน่ง, animation ยังคงเก็บใน `WindowsUiSetting`
   - ✅ เฉพาะ**เนื้อหาเมนู**เท่านั้นที่ hard-code
   - ✅ ให้ผู้ใช้ปรับแต่งรูปลักษณ์ผ่าน Admin UI ได้ตามปกติ

### ❌ ห้ามทำ (NEVER DO THIS):

#### 1. **ห้ามใช้ Database สำหรับเก็บรายการเมนู**
   - ❌ ห้ามบันทึกเมนูลง `windows_ui_settings` table
   - ❌ ห้ามอ่านเมนูจาก `WindowsUiSetting::get()`
   - ❌ ห้ามสร้างหน้า Admin UI สำหรับจัดการเมนู

#### 2. **ห้ามใช้ Seeder สำหรับเมนู**
   - ❌ ห้ามใส่รายการเมนูใน `WindowsUiSeeder.php`
   - ❌ Seeder ใช้เฉพาะ visual settings เท่านั้น

#### 3. **ห้ามสร้างระบบ CRUD สำหรับเมนู**
   - ❌ ห้ามสร้างหน้า start-menu management
   - ❌ ห้ามสร้างหน้า taskbar-apps management
   - ❌ ห้ามสร้างหน้า system-tray management

---

## 📋 โครงสร้างระบบเมนูใหม่

### 1. ที่เก็บเมนู

**ไฟล์เดียว:** `/resources/views/components/millennium-start-menu.blade.php`

```php
// ========================================
// HARD-CODED MENU ITEMS - ไม่ใช้ Database
// ========================================

if ($type === 'admin') {
    $menuItems = [
        ['icon' => '📊', 'label' => 'แดชบอร์ด', 'url' => route('admin.dashboard'), 'order' => 0],
        [
            'icon' => '👥',
            'label' => 'ผู้ใช้งาน',
            'url' => '#',
            'order' => 1,
            'submenu' => [
                ['label' => 'รายชื่อผู้ใช้', 'url' => route('admin.users.index')],
                ['label' => 'บทบาท (Roles)', 'url' => route('admin.roles.index')],
            ]
        ],
        // ... เมนูอื่นๆ ทั้งหมด 26 รายการ
    ];
} elseif ($type === 'seller') {
    $menuItems = [
        // ... เมนู seller ทั้งหมด 9 รายการ
    ];
} else { // user
    $menuItems = [
        // ... เมนู user ทั้งหมด 15 รายการ
    ];
}
```

### 2. การตั้งค่าที่ยังคงใช้ Database

**Visual Customization Settings** (ยังคงใช้ `WindowsUiSetting`):
- สีพื้นหลัง taskbar
- ขนาดเมนู
- ตำแหน่งเมนู
- RGB effects (RGB border ที่วิ่งรอบกรอบ)
- Animation settings
- ความโปร่งใส, blur effects
- ขนาดโลโก้ในเมนู
- การแสดง/ซ่อนโลโก้และข้อความ
- ระยะห่างและ padding ของเมนู

```php
// ✅ ยังคงใช้ WindowsUiSetting สำหรับ visual settings
$menuWidth = WindowsUiSetting::get('millennium_menu_width', '400');
$menuPosition = WindowsUiSetting::get('millennium_menu_position', 'center');
$rgbEnabled = WindowsUiSetting::get('millennium_menu_rgb_enabled', true);

// Logo & Text Settings
$logoSize = WindowsUiSetting::get('millennium_menu_logo_size', 40);
$showLogo = WindowsUiSetting::get('millennium_menu_show_logo', true);
$showAppName = WindowsUiSetting::get('millennium_menu_show_app_name', true);

// Menu Appearance
$menuItemSpacing = WindowsUiSetting::get('millennium_menu_item_spacing', 8);
$menuPadding = WindowsUiSetting::get('millennium_menu_padding', 12);

// RGB Hover Effect Settings
$menuItemHoverRgb = WindowsUiSetting::get('millennium_menu_item_hover_rgb', true);
$menuRgbSpeed = WindowsUiSetting::get('millennium_menu_rgb_speed', 5);
$menuRgbBorderWidth = WindowsUiSetting::get('millennium_menu_rgb_border_width', 2);
```

---

## 🔧 วิธีการเพิ่มเมนูใหม่

### ขั้นตอนเดียว: แก้ไข Component

**ไฟล์:** `resources/views/components/millennium-start-menu.blade.php`

```php
// เพิ่มเมนูใหม่ที่บรรทัดที่เหมาะสม

// สำหรับ Admin Menu
if ($type === 'admin') {
    $menuItems = [
        // ... เมนูเดิม ...

        // 🆕 เพิ่มเมนูใหม่ที่นี่
        [
            'icon' => '🆕',
            'label' => 'ฟีเจอร์ใหม่',
            'url' => route('admin.new-feature.index'),
            'order' => 26,
            'submenu' => [
                ['label' => 'หน้าย่อย 1', 'url' => route('admin.new-feature.sub1')],
                ['label' => 'หน้าย่อย 2', 'url' => route('admin.new-feature.sub2')],
            ]
        ],
    ];
}
```

**เท่านี้เสร็จ!** ไม่ต้องรัน seeder, ไม่ต้องรัน migration, refresh หน้าเว็บเห็นเมนูใหม่ทันที

---

## 📝 ตัวอย่างการใช้งาน

### ✅ ตัวอย่างที่ถูกต้อง

#### เพิ่มเมนู "จัดการคูปอง" สำหรับ Admin

```php
// ใน millennium-start-menu.blade.php
if ($type === 'admin') {
    $menuItems = [
        // ... เมนูอื่นๆ ...

        [
            'icon' => '🎟️',
            'label' => 'จัดการคูปอง',
            'url' => route('admin.coupons.index'),
            'order' => 27,
            'submenu' => [
                ['label' => 'คูปองทั้งหมด', 'url' => route('admin.coupons.index')],
                ['label' => 'สร้างคูปองใหม่', 'url' => route('admin.coupons.create')],
                ['label' => 'ประวัติการใช้', 'url' => route('admin.coupons.usage')],
            ]
        ],
    ];
}
```

#### เพิ่มเมนูธรรมดา (ไม่มี submenu)

```php
['icon' => '📧', 'label' => 'อีเมลมาร์เก็ตติ้ง', 'url' => route('admin.email-marketing.index'), 'order' => 28],
```

### ❌ ตัวอย่างที่ผิด (อย่าทำ)

```php
// ❌ ห้ามพยายามบันทึกเมนูลง database
WindowsUiSetting::set('windows_start_menu_items_admin', $menuItems);

// ❌ ห้ามพยายามอ่านเมนูจาก database
$menuItems = WindowsUiSetting::get('windows_start_menu_items_admin');

// ❌ ห้ามใส่เมนูใน seeder
$settings['windows_start_menu_items_admin'] = ['value' => $menuItems, 'type' => 'json'];
```

---

## 🎯 Best Practices

### 1. การตั้ง Route

```php
// ✅ ดี - ใช้ route() helper
'url' => route('admin.users.index')
'url' => route('seller.products.create')
'url' => route('user.wallet.withdraw')

// ❌ ไม่ดี - hard-code URL
'url' => '/admin/users'
'url' => '/seller/products/create'
```

**เหตุผล:** ถ้า route name เปลี่ยน Laravel จะ error ทันที ทำให้แก้ไขได้ง่าย

### 2. การเรียงลำดับเมนู

```php
// ใช้ order เป็นตัวเลข
'order' => 0   // เมนูแรก
'order' => 1   // เมนูที่สอง
'order' => 2   // เมนูที่สาม
```

### 3. การจัดการ Submenu

```php
// เมนูที่มี submenu
[
    'icon' => '👥',
    'label' => 'ผู้ใช้งาน',
    'url' => '#',  // ← ใช้ # สำหรับเมนูที่มี submenu
    'order' => 1,
    'submenu' => [
        ['label' => 'รายชื่อผู้ใช้', 'url' => route('admin.users.index')],
        ['label' => 'บทบาท', 'url' => route('admin.roles.index')],
    ]
]

// เมนูที่ไม่มี submenu
[
    'icon' => '📊',
    'label' => 'แดชบอร์ด',
    'url' => route('admin.dashboard'),  // ← ใส่ route ตรงนี้
    'order' => 0
]
```

---

## 🗑️ สิ่งที่ถูกลบออก

### ไฟล์ที่ถูกลบ:
- ✅ `/resources/views/admin/windows-ui/start-menu.blade.php` - หน้าจัดการ Start Menu
- ✅ `/resources/views/admin/windows-ui/taskbar-apps.blade.php` - หน้าจัดการ Taskbar Apps
- ✅ `/resources/views/admin/windows-ui/system-tray.blade.php` - หน้าจัดการ System Tray

### Controller Methods ที่ถูกลบ:
- ✅ `WindowsUiController::startMenu()` - แสดงหน้า start menu
- ✅ `WindowsUiController::updateStartMenu()` - อัพเดท start menu
- ✅ `WindowsUiController::taskbarApps()` - แสดงหน้า taskbar apps
- ✅ `WindowsUiController::updateTaskbarApps()` - อัพเดท taskbar apps
- ✅ `WindowsUiController::systemTray()` - แสดงหน้า system tray
- ✅ `WindowsUiController::updateSystemTray()` - อัพเดท system tray

### Routes ที่ถูกลบ:
- ✅ `GET /admin/windows-ui/start-menu`
- ✅ `PUT /admin/windows-ui/start-menu`
- ✅ `GET /admin/windows-ui/taskbar-apps`
- ✅ `PUT /admin/windows-ui/taskbar-apps`
- ✅ `GET /admin/windows-ui/system-tray`
- ✅ `PUT /admin/windows-ui/system-tray`

### Seeder Data ที่ถูกลบ:
- ✅ `windows_start_menu_items_admin`
- ✅ `windows_start_menu_items_seller`
- ✅ `windows_start_menu_items_user`
- ✅ `windows_taskbar_apps`
- ✅ `windows_system_tray_icons`

---

## 📚 ไฟล์ที่เกี่ยวข้อง (ที่ยังใช้งาน)

### 1. Component (เมนู hard-coded อยู่ที่นี่)
- `resources/views/components/millennium-start-menu.blade.php` - **ไฟล์หลักสำหรับจัดการเมนู**

### 2. Seeder (เฉพาะ visual settings)
- `database/seeders/WindowsUiSeeder.php` - ข้อมูลเริ่มต้นของ visual settings (ไม่มีเมนูแล้ว)

### 3. Model
- `app/Models/WindowsUiSetting.php` - Model สำหรับจัดการ Windows UI Settings (visual only)

### 4. Controllers (visual settings only)
- `app/Http/Controllers/Admin/WindowsUiController.php` - จัดการเฉพาะ visual customization

### 5. Routes (visual settings only)
- `routes/admin.php` - Routes สำหรับตั้งค่า visual customization

### 6. Views (Admin UI - visual settings only)
- `resources/views/admin/windows-ui/index.blade.php` - หน้าตั้งค่า Windows UI (visual)
- `resources/views/admin/windows-ui/rgb-settings.blade.php` - หน้าตั้งค่า RGB

---

## 🎨 ฟีเจอร์ Visual Customization ล่าสุด

### 1. RGB Border Effect (สีวิ่งรอบกรอบเมนู) 🌈

**เทคโนโลยี:**
- ใช้ `conic-gradient` สร้าง gradient แบบวงกลม 4 สี
- ใช้ CSS `::before` pseudo-element วางเป็น border layer
- ใช้ `mask-composite: exclude` ตัดให้เหลือเฉพาะขอบ
- Animation `rgbRotate` หมุน 360° รอบกรอบ

**การทำงาน:**
```css
/* สร้างกรอบ RGB ที่วิ่งรอบ */
.millennium-menu-item-hover-rgb::before {
    content: '';
    position: absolute;
    background: conic-gradient(
        from 0deg,
        #FF0080 0deg,    /* Pink */
        #00F0FF 90deg,   /* Cyan */
        #7F00FF 180deg,  /* Purple */
        #FFD700 270deg,  /* Gold */
        #FF0080 360deg
    );
    animation: rgbRotate 5s linear infinite;
}
```

**Settings ที่ปรับได้:**
- `millennium_menu_item_hover_rgb` - เปิด/ปิด RGB hover effect
- `millennium_menu_rgb_speed` - ความเร็วหมุน (1-20 วินาที)
- `millennium_menu_rgb_border_width` - ความหนาขอบ (1-10px)
- `millennium_menu_rgb_glow_size` - ขนาด glow effect (0-50px)

### 2. Logo Customization (ปรับแต่งโลโก้) 🖼️

**ฟีเจอร์:**
- อัพโหลดโลโก้ custom สำหรับเมนู
- ถ้าไม่อัพโหลดจะใช้โลโก้หลักของระบบ
- ถ้าไม่มีโลโก้เลยจะแสดง gradient div กับตัวอักษรแรก
- ปรับขนาดโลโก้ได้ 20-100px

**Settings:**
- `millennium_menu_logo` - path ของโลโก้ custom (nullable)
- `millennium_menu_logo_size` - ขนาดโลโก้ในหน่วย px (20-100)
- `millennium_menu_show_logo` - แสดง/ซ่อนโลโก้

**โค้ด:**
```php
@if($showLogo)
    @if($menuLogo)
        <img src="{{ asset('storage/' . $menuLogo) }}" style="width: {{ $logoSize }}px; height: {{ $logoSize }}px;">
    @elseif($mainLogo)
        <img src="{{ Storage::url($mainLogo) }}" style="width: {{ $logoSize }}px; height: {{ $logoSize }}px;">
    @else
        <div style="width: {{ $logoSize }}px; height: {{ $logoSize }}px; font-size: {{ $logoSize * 0.5 }}px;">
            {{ substr($appName, 0, 1) }}
        </div>
    @endif
@endif
```

### 3. Text Customization (ปรับแต่งข้อความ) ✏️

**ฟีเจอร์:**
- ปรับแต่งชื่อแอปที่แสดงในเมนู
- ปรับแต่งข้อความ subtitle
- แสดง/ซ่อนชื่อแอปและ subtitle แยกกันได้

**Settings:**
- `millennium_menu_show_app_name` - แสดง/ซ่อนชื่อแอป
- `millennium_menu_show_subtitle` - แสดง/ซ่อน subtitle
- `millennium_menu_app_name` - ชื่อแอป custom (nullable, ถ้าไม่มีใช้ชื่อระบบ)
- `millennium_menu_subtitle` - subtitle custom (nullable, ถ้าไม่มีใช้ "{role} Dashboard")

### 4. Menu Spacing & Padding (ระยะห่างและขนาด) 📐

**Settings:**
- `millennium_menu_item_spacing` - ระยะห่างระหว่างเมนู (0-32px)
- `millennium_menu_padding` - padding ภายในเมนู (4-32px)

**การใช้งาน:**
```php
<div style="gap: {{ $menuItemSpacing }}px; padding: {{ $menuPadding }}px;">
```

---

## 🚀 การทดสอบ

### 1. ทดสอบการแสดงเมนู
1. Login ด้วย admin account
2. เปิด start menu
3. ตรวจสอบว่าเมนูแสดงครบทั้ง 26 รายการ
4. คลิกทุกเมนูเพื่อทดสอบ route

### 2. ทดสอบการเพิ่มเมนูใหม่
1. แก้ไข `millennium-start-menu.blade.php`
2. เพิ่มเมนูใหม่ใน array
3. Refresh หน้าเว็บ
4. ตรวจสอบว่าเมนูใหม่ปรากฏ

### 3. ทดสอบ Visual Customization
```bash
# ทดสอบว่า visual settings ยังทำงาน
php artisan tinker

# ทดสอบอ่านค่า visual settings
App\Models\WindowsUiSetting::get('millennium_menu_width');
App\Models\WindowsUiSetting::get('millennium_menu_position');
```

---

## 🐛 Troubleshooting

### ปัญหา: เมนูไม่แสดง
**วิธีแก้:**
1. ตรวจสอบ syntax error ใน `millennium-start-menu.blade.php`
2. ตรวจสอบว่า route name ถูกต้อง: `php artisan route:list`
3. Clear cache: `php artisan optimize:clear`

### ปัญหา: Route ไม่ทำงาน
**วิธีแก้:**
1. ตรวจสอบว่า route ถูกสร้างแล้ว: `php artisan route:list | grep admin`
2. ตรวจสอบ route name ใน component ให้ตรงกับที่สร้างไว้
3. ตรวจสอบ middleware และ permission

### ปัญหา: Visual settings ไม่ทำงาน
**วิธีแก้:**
1. ตรวจสอบว่า seeder รันแล้ว: `php artisan db:seed --class=WindowsUiSeeder`
2. เช็คในฐานข้อมูล: `select * from windows_ui_settings where key like '%menu%';`
3. Clear cache: `php artisan cache:clear`

---

## 📖 สรุป

**กฎสำคัญที่ต้องจำ:**
1. ✅ เมนูทั้งหมด hard-code ใน `millennium-start-menu.blade.php`
2. ✅ ใช้ `route()` helper สำหรับ URL
3. ✅ Visual settings ยังคงใช้ database
4. ❌ ห้ามเก็บรายการเมนูใน database
5. ❌ ห้ามสร้าง Admin UI สำหรับจัดการเมนู

**ข้อดีของ Hard-Coded Approach:**
- 🚀 รวดเร็ว - แก้โค้ดแล้วเห็นผลทันที
- 🔍 ง่ายต่อการ track - อยู่ใน Git version control
- 🐛 Debug ง่าย - เห็นโค้ดทั้งหมดในที่เดียว
- 💪 ไม่ซับซ้อน - ไม่ต้องจัดการ database, seeder, admin UI

**เมื่อไหร่ควรใช้ Database:**
- ✅ ข้อมูลที่ผู้ใช้ควรปรับแต่งได้ (สี, ขนาด, animation)
- ✅ ข้อมูลที่เปลี่ยนแปลงบ่อยโดยไม่ต้องแก้โค้ด
- ❌ ไม่ใช้สำหรับรายการเมนู (hard-code แทน)

---

**จัดทำโดย:** Claude AI
**วันที่สร้าง:** 2025-01-10
**อัพเดทล่าสุด:** 2025-11-10 (เพิ่ม RGB border effect + logo customization)
**เวอร์ชั่น:** 3.1.0 - Hard-Coded Menu System with Advanced Visual Customization
**โปรเจกต์:** TP-Affiliate Platform

---

## 📝 Change Log

### v3.1.0 (2025-11-10)
- ✅ เพิ่ม RGB border effect ที่วิ่งรอบกรอบเมนูจริงๆ (ใช้ conic-gradient)
- ✅ เพิ่มการตั้งค่าขนาดโลโก้ (20-100px)
- ✅ เพิ่มการอัพโหลดโลโก้ custom สำหรับเมนู
- ✅ เพิ่มการแสดง/ซ่อนโลโก้และข้อความ
- ✅ เพิ่มการปรับแต่งชื่อแอปและ subtitle
- ✅ เพิ่มการตั้งค่าระยะห่างและ padding ของเมนู

### v3.0.0 (2025-01-10)
- ✅ เปลี่ยนจาก Database-Driven เป็น Hard-Coded Menu System
- ✅ ลบหน้า Admin UI สำหรับจัดการเมนู
- ✅ เก็บเฉพาะ Visual Settings ใน database
- ✅ Hard-code เมนูทั้งหมดใน component
