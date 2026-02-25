# กฎเกณฑ์การจัดการระบบเมนู - Menu System Rules (V3.2)

## 🚨 กฎสำคัญที่สุด - CRITICAL RULE

> **เมื่อเพิ่มหน้าใหม่ ต้องเพิ่มเมนูใน `config/menus.php` เสมอ!**
>
> ถ้าเพิ่มแค่ใน blade = เมนูไม่แสดง ❌

**วันที่อัพเดท:** 2026-02-25
**เวอร์ชั่น:** 3.2.0

---

## 📐 สถาปัตยกรรมระบบเมนู

### ระบบเมนูมี 2 ส่วน (ต้องรู้ทั้ง 2!)

```
┌─────────────────────────────────────────────────────┐
│           sidebar-v3.blade.php                       │
│                                                      │
│  @if($useMenuService)   ← ✅ ระบบหลัก (ใช้จริง!)   │
│      @foreach($menus)                                │
│          // โหลดจาก config/menus.php                 │
│          // ผ่าน MenuService                         │
│      @endforeach                                     │
│  @else                  ← ❌ Fallback (ไม่ถูกเรียก!) │
│      // เมนู hardcode ใน blade                       │
│      // ไม่แสดง! เพราะ $useMenuService = true เสมอ  │
│  @endif                                              │
└─────────────────────────────────────────────────────┘
```

### ระบบที่ 1: Config-Based (ระบบหลัก ✅)

**ไฟล์:** `config/menus.php`
**ใช้โดย:** ทั้ง Admin + User + Seller
**สถานะ:** `$useMenuService = true` (เปิดเสมอ)

**Flow:**
```
config/menus.php → MenuService::getMenuForRole() → sidebar-v3.blade.php
```

**โครงสร้าง:**
```php
return [
    'admin' => [
        // เมนู Admin ทั้งหมด (~100+ items)
        [
            'id' => 'fortune-telling',
            'label' => 'ระบบดูดวง',
            'icon' => '🔮',
            'route' => null,
            'order' => 24.3,
            'permissions' => [],
            'submenu' => [
                ['label' => '🔮 ตั้งค่า', 'route' => 'admin.fortune.settings.index'],
                ['label' => '💰 บิลดูดวง', 'route' => 'admin.fortune.billing.index'],
                // ... submenu items
            ],
        ],
        // ... กลุ่มเมนูอื่นๆ
    ],
    'user' => [
        // เมนู User ทั้งหมด (~60+ items)
    ],
    'instructor' => [ /* ... */ ],
    'provider' => [ /* ... */ ],
];
```

### ระบบที่ 2: Hardcode ใน Blade (Legacy Fallback ❌)

**ไฟล์:** `resources/views/components/arrow-x/sidebar-v3.blade.php`
**ใช้เมื่อ:** `$useMenuService = false` เท่านั้น (ปัจจุบันไม่เคยเกิด)
**ตำแหน่ง:** อยู่ใน `@else` block (line 185+)

> ⚠️ **เมนูที่เพิ่มในส่วน `@else` จะไม่แสดงเลย!**
> เพราะ `$useMenuService` ถูกตั้งเป็น `true` เสมอ (line 40)

---

## ✅ วิธีเพิ่มเมนูที่ถูกต้อง

### สำหรับ Admin

**ไฟล์:** `config/menus.php` → ส่วน `'admin'` array

```php
// ตัวอย่าง: เพิ่มเมนูใหม่ใน submenu ของกลุ่มที่มีอยู่แล้ว
// หา group ที่ต้องการ (เช่น fortune-telling) แล้วเพิ่ม item ใน submenu

'submenu' => [
    // ... items เดิม ...
    ['label' => '📊 ภาพรวมคอมมิชชั่น', 'route' => 'admin.fortune.commissions.index', 'description' => 'สถิติคอมมิชชั่น'],
    // ↑ เพิ่มตรงนี้
],
```

```php
// ตัวอย่าง: เพิ่มกลุ่มเมนูใหม่ทั้งกลุ่ม
[
    'id' => 'new-feature',           // ID ไม่ซ้ำ
    'label' => 'ฟีเจอร์ใหม่',         // ชื่อที่แสดง (ภาษาไทย)
    'icon' => '🆕',                  // Emoji icon
    'route' => null,                 // null = มี submenu
    'order' => 25,                   // ลำดับแสดง
    'permissions' => [],             // สิทธิ์ (ว่าง = ทุกคนเห็น)
    'submenu' => [
        ['label' => 'หน้าหลัก', 'route' => 'admin.new-feature.index', 'description' => 'คำอธิบาย'],
        ['label' => 'จัดการ', 'route' => 'admin.new-feature.manage', 'description' => 'คำอธิบาย'],
    ],
],
```

### สำหรับ User

**ไฟล์:** `config/menus.php` → ส่วน `'user'` array

```php
// เพิ่มใน user array (ตำแหน่งประมาณ line 1370+)
[
    'id' => 'new-feature',
    'label' => 'ฟีเจอร์ใหม่',
    'icon' => '🆕',
    'route' => null,
    'order' => 12.5,                 // ใช้ทศนิยมเพื่อแทรกระหว่างเมนูเดิม
    'permissions' => [],
    'submenu' => [
        ['label' => '📊 Dashboard', 'route' => 'user.new-feature.index', 'icon' => '📊', 'description' => 'คำอธิบาย'],
    ],
],
```

---

## ❌ สิ่งที่ห้ามทำ

### 1. ห้ามเพิ่มเมนูเฉพาะใน sidebar-v3.blade.php โดยไม่เพิ่มใน config/menus.php

```php
// ❌ ผิด! เพิ่มแค่ใน blade (อยู่ใน @else block → ไม่แสดง!)
<a href="{{ route('admin.fortune.commissions.index') }}">
    ภาพรวมคอมมิชชั่น
</a>

// ✅ ถูก! ต้องเพิ่มใน config/menus.php
'submenu' => [
    ['label' => '📊 ภาพรวมคอมมิชชั่น', 'route' => 'admin.fortune.commissions.index'],
],
```

### 2. ห้ามเพิ่มเมนูเฉพาะใน config/menus.php โดยไม่สร้าง route

```php
// ❌ ผิด! route ยังไม่มี → Error 404
['label' => 'ฟีเจอร์ใหม่', 'route' => 'admin.new-feature.index'],

// ✅ ถูก! สร้าง route ก่อนแล้วค่อยเพิ่มเมนู
// routes/admin.php:
Route::get('/new-feature', [NewFeatureController::class, 'index'])->name('new-feature.index');
// แล้วค่อยเพิ่มใน config/menus.php
```

### 3. ห้ามใช้ Database สำหรับเก็บรายการเมนู

```php
// ❌ ห้ามบันทึกเมนูลง database
WindowsUiSetting::set('menu_items', $items);

// ✅ เมนูอยู่ใน config/menus.php เท่านั้น
```

---

## 📋 Checklist เมื่อเพิ่มหน้าใหม่

เมื่อสร้างฟีเจอร์ใหม่ที่ต้องมีเมนู ต้องทำครบทุกข้อ:

### Admin:
- [ ] สร้าง Controller + Route + View
- [ ] **เพิ่มเมนูใน `config/menus.php`** (ส่วน `'admin'` array)
- [ ] ตรวจสอบว่า route name ตรงกับที่ใส่ในเมนู

### User:
- [ ] สร้าง Controller + Route + View
- [ ] **เพิ่มเมนูใน `config/menus.php`** (ส่วน `'user'` array)
- [ ] ตรวจสอบว่า route name ตรงกับที่ใส่ในเมนู

---

## 🗂️ ไฟล์สำคัญ

| ไฟล์ | หน้าที่ | ต้องแก้เมื่อเพิ่มเมนู? |
|------|--------|----------------------|
| `config/menus.php` | แหล่งข้อมูลเมนูทั้งหมด | ✅ **ต้องแก้เสมอ** |
| `app/Services/MenuService.php` | ประมวลผลเมนู (permission, sort, resolve routes) | ❌ ไม่ต้องแก้ |
| `resources/views/components/arrow-x/sidebar-v3.blade.php` | แสดงผล sidebar | ❌ ไม่ต้องแก้ (ยกเว้นเปลี่ยน layout) |
| `resources/views/components/menu/pinnable-menu-group.blade.php` | Component เมนูย่อย | ❌ ไม่ต้องแก้ |
| `resources/views/components/menu/pinnable-menu-item.blade.php` | Component เมนูเดี่ยว | ❌ ไม่ต้องแก้ |

---

## 🔧 MenuService Pipeline

```
1. getMenuForRole($role, $user)
   ↓
2. โหลดจาก config/menus.php
   ↓
3. โหลดจาก Feature Providers (ถ้ามี)
   ↓
4. Merge ทั้ง 2 แหล่ง
   ↓
5. Apply Database Overrides (ถ้าเปิด MENU_USE_DB_OVERRIDES=true)
   ↓
6. Filter ตาม Permissions ของ user
   ↓
7. Resolve route names → URLs
   ↓
8. Sort ตาม order
   ↓
9. Return → sidebar-v3.blade.php แสดงผล
```

---

## 🎯 สรุปกฎทอง

1. **เมนูทุกตัวต้องอยู่ใน `config/menus.php`** — นี่คือแหล่งเดียวที่ระบบอ่าน
2. **ไม่ต้องแก้ sidebar-v3.blade.php** เมื่อเพิ่มเมนูใหม่
3. **ใช้ route name** ไม่ใช่ URL — เช่น `'route' => 'admin.fortune.commissions.index'`
4. **ใช้ order ทศนิยม** เพื่อแทรกระหว่างเมนูเดิม — เช่น `12.5` แทรกระหว่าง `12` กับ `13`
5. **Admin + User ใช้ระบบเดียวกัน** — แยกแค่ key ใน array (`'admin'` vs `'user'`)

---

**จัดทำโดย:** Claude AI
**วันที่สร้าง:** 2026-02-25
**เวอร์ชั่น:** 3.2.0 - Config-Based Menu System (V3)
**โปรเจกต์:** TP-Affiliate Platform
