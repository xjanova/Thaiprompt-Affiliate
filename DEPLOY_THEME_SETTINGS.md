# 🎨 Deploy Theme Settings (Footer Logo & Background Effects)

## ⚠️ สิ่งที่ต้องทำในเซิร์ฟเวอร์จริง

หลังจาก pull code ใหม่แล้ว รันคำสั่งเหล่านี้ตามลำดับ:

### 1. รัน Migration (เพิ่ม columns ใหม่ใน database)

```bash
php artisan migrate --force
```

**คำอธิบาย**: เพิ่ม 12 columns ใหม่ใน `theme_settings` table:
- `footer_logo_path` - เส้นทางโลโก้มุมล่างซ้าย
- `bg_effects_enabled` - เปิด/ปิด background effects
- `bg_circle1_color1`, `bg_circle1_color2` - สีวงกลมที่ 1 (Cyan-Blue)
- `bg_circle2_color1`, `bg_circle2_color2` - สีวงกลมที่ 2 (Pink-Purple)
- `bg_circle3_color1`, `bg_circle3_color2` - สีวงกลมที่ 3 (Yellow-Orange)
- `bg_animation_speed` - ความเร็วแอนิเมชั่น (slow/normal/fast)
- `bg_circle_opacity` - ความโปร่งใส (0-100)
- `bg_circle_blur` - ความเบลอ (0-200)
- `bg_circle_size` - ขนาดวงกลม (200-800 px)

### 2. Clear Theme Cache

```bash
php artisan arrowx:clear
```

**คำอธิบาย**: ลบ cache ของ theme เก่าทิ้ง

### 3. Compile Theme ใหม่

```bash
php artisan arrowx:compile
```

**คำอธิบาย**: Generate CSS Variables จากการตั้งค่าใหม่

### 4. Clear Laravel Cache

```bash
php artisan optimize:clear
```

**คำอธิบาย**: ลบ cache ทั้งหมด (config, routes, views)

### 5. Build Assets (ถ้ายังไม่ได้ build)

```bash
npm run build
```

---

## ✅ หลังรันคำสั่งเสร็จแล้ว

เข้าไปที่หน้า **การตั้งค่าธีม (Arrow X Theme Settings)** คุณจะเห็น:

### 📸 โลโก้ (3 แบบ)
- **Header Logo** (200×200px) - โลโก้หลักด้านบน
- **Footer Logo** (150×150px) - โลโก้มุมล่างซ้าย sidebar ⭐ **ใหม่!**
- **Favicon** (64×64px) - ไอคอนแท็บเบราว์เซอร์

### 🎨 เอฟเฟคพื้นหลัง ⭐ **ใหม่!**
- **เปิด/ปิด** Background Effects
- **3 วงกลม Gradient** - แต่ละวงกลมเลือกได้ 2 สี
- **ความเร็ว** - Slow (10s) / Normal (6s) / Fast (3s)
- **ความโปร่งใส** - ปรับ opacity 0-100%
- **ความเบลอ** - ปรับ blur 0-200px
- **ขนาดวงกลม** - ปรับ size 200-800px

---

## 🔧 การทดสอบ

1. **ทดสอบอัพโหลด Footer Logo**:
   - ไปที่ การตั้งค่าธีม → ส่วน "โลโก้"
   - อัพโหลดไฟล์ภาพใน "Footer Logo"
   - บันทึก
   - ดูที่มุมล่างซ้าย sidebar (จะเห็นโลโก้ใหม่)

2. **ทดสอบ Background Effects**:
   - ไปที่ การตั้งค่าธีม → ส่วน "เอฟเฟคพื้นหลัง"
   - เปิด toggle switch
   - เปลี่ยนสีวงกลม 1/2/3
   - ปรับความเร็ว, opacity, blur, size
   - บันทึก
   - ดูพื้นหลังหน้าแอดมิน (จะเห็นวงกลม gradient เคลื่อนไหว)

---

## 🐛 Troubleshooting

### ปัญหา: อัพรูปแล้วไม่เห็นเปลี่ยน
**แก้ไข**: รันคำสั่ง cache clearing อีกครั้ง
```bash
php artisan arrowx:clear
php artisan arrowx:compile
php artisan optimize:clear
```

### ปัญหา: Background effects ไม่ปรากฏ
**ตรวจสอบ**:
1. ตรวจสอบว่า migration รันสำเร็จ: `php artisan migrate:status`
2. ตรวจสอบว่า `bg_effects_enabled = 1` ใน database
3. Clear browser cache (Ctrl+Shift+R)

### ปัญหา: Footer logo ไม่แสดง
**ตรวจสอบ**:
1. ตรวจสอบว่าไฟล์อัพโหลดสำเร็จ: ดูใน `storage/app/public/theme-logos/`
2. ตรวจสอบว่า symlink ถูกต้อง: `php artisan storage:link`
3. ตรวจสอบ permissions: `chmod -R 775 storage`

---

## 📁 Files Changed in This Update

### Backend
- ✅ `database/migrations/2025_11_17_000001_add_background_effects_to_theme_settings_table.php` - New migration
- ✅ `app/Http/Controllers/Admin/ArrowXThemeController.php` - Added validation & upload handling
- ✅ `app/Services/ThemeService.php` - Generate CSS Variables for background effects

### Frontend
- ✅ `resources/views/admin/arrow-x-theme/general-settings.blade.php` - Added footer logo upload & background effects form
- ✅ `resources/views/layouts/admin-v3.blade.php` - Dynamic background circles using CSS Variables
- ✅ `resources/views/components/arrow-x/sidebar-v3.blade.php` - Display footer logo

---

## 🎯 Summary

**Before**: Theme settings ไม่สามารถอัพโหลด footer logo และปรับแต่ง background effects ได้

**After**: ✨ สามารถ:
- อัพโหลด footer logo (มุมล่างซ้าย sidebar)
- ปรับแต่ง background effects แบบเรียลไทม์
- เลือกสี 6 สี สำหรับ 3 วงกลม gradient
- ปรับความเร็ว, opacity, blur, size ของแอนิเมชั่น

---

**Document Version**: 1.0
**Created**: 2025-11-17
**Branch**: `claude/dashboard-color-theme-01WrWZDEhywL1zQkJKkpjgDo`
