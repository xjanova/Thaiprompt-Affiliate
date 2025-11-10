# 🎨 Taskbar Color Customization Guide

## การตั้งค่าสีของ Taskbar

ระบบรองรับการปรับแต่งสีของ Taskbar ได้หลากหลาย ผ่านการตั้งค่าใน Windows UI Settings

### 🎯 การตั้งค่าสีที่มี:

#### 1. **สีพื้นหลัง (Background Color)**
- Key: `windows_taskbar_bg_color`
- Type: `color`
- Default: `#1e293b` (Slate 800)
- ใช้เมื่อ: `windows_taskbar_use_gradient` = `false`

#### 2. **สีข้อความ (Text Color)**
- Key: `windows_taskbar_text_color`
- Type: `color`
- Default: `#ffffff` (White)
- ใช้สำหรับ: ข้อความ, ไอคอน, และองค์ประกอบต่างๆ บน Taskbar

#### 3. **สีเมื่อ Hover (Hover Background)**
- Key: `windows_taskbar_hover_bg_color`
- Type: `color`
- Default: `#334155` (Slate 700)
- ใช้เมื่อ: เมาส์ชี้ที่ปุ่มหรือเมนู

#### 4. **สีเมื่อ Active (Active Background)**
- Key: `windows_taskbar_active_bg_color`
- Type: `color`
- Default: `#475569` (Slate 600)
- ใช้เมื่อ: คลิกหรือเปิดเมนูอยู่

#### 5. **สีขอบ (Border Color)**
- Key: `windows_taskbar_border_color`
- Type: `color`
- Default: `#475569` (Slate 600)
- ใช้สำหรับ: ขอบของ Taskbar

#### 6. **Gradient Mode (โหมด Gradient)**
- Key: `windows_taskbar_use_gradient`
- Type: `boolean`
- Default: `false`
- ถ้า `true`: ใช้ gradient จาก 2 สี
- ถ้า `false`: ใช้สีเดียวจาก `windows_taskbar_bg_color`

#### 7. **สีเริ่มต้น Gradient (Gradient From)**
- Key: `windows_taskbar_gradient_from`
- Type: `color`
- Default: `#1e293b` (Slate 800)
- ใช้เมื่อ: `windows_taskbar_use_gradient` = `true`

#### 8. **สีสิ้นสุด Gradient (Gradient To)**
- Key: `windows_taskbar_gradient_to`
- Type: `color`
- Default: `#0f172a` (Slate 900)
- ใช้เมื่อ: `windows_taskbar_use_gradient` = `true`

---

## 📝 วิธีใช้งาน:

### ผ่าน Database Seeder:
```php
WindowsUiSetting::set('windows_taskbar_bg_color', '#1e293b', 'color');
WindowsUiSetting::set('windows_taskbar_text_color', '#ffffff', 'color');
WindowsUiSetting::set('windows_taskbar_use_gradient', true, 'boolean');
WindowsUiSetting::set('windows_taskbar_gradient_from', '#ec4899', 'color');
WindowsUiSetting::set('windows_taskbar_gradient_to', '#8b5cf6', 'color');
```

### ผ่าน Admin UI (Coming Soon):
จะมีหน้า Windows UI Settings ที่สามารถเลือกสีผ่าน Color Picker ได้โดยตรง

---

## 🎨 ตัวอย่างธีมสี:

### 1. **Dark Blue Theme** (Default)
```php
'windows_taskbar_bg_color' => '#1e293b',
'windows_taskbar_text_color' => '#ffffff',
'windows_taskbar_hover_bg_color' => '#334155',
'windows_taskbar_active_bg_color' => '#475569',
```

### 2. **Purple Gradient Theme**
```php
'windows_taskbar_use_gradient' => true,
'windows_taskbar_gradient_from' => '#ec4899',
'windows_taskbar_gradient_to' => '#8b5cf6',
'windows_taskbar_text_color' => '#ffffff',
```

### 3. **Dark Green Theme**
```php
'windows_taskbar_bg_color' => '#065f46',
'windows_taskbar_text_color' => '#ffffff',
'windows_taskbar_hover_bg_color' => '#047857',
'windows_taskbar_active_bg_color' => '#059669',
```

### 4. **Ocean Blue Gradient**
```php
'windows_taskbar_use_gradient' => true,
'windows_taskbar_gradient_from' => '#0891b2',
'windows_taskbar_gradient_to' => '#0e7490',
'windows_taskbar_text_color' => '#ffffff',
```

---

## 🔧 Technical Details:

### CSS Variables Used:
```css
--taskbar-text-color: ค่าจาก windows_taskbar_text_color
--taskbar-hover-bg: ค่าจาก windows_taskbar_hover_bg_color
--taskbar-active-bg: ค่าจาก windows_taskbar_active_bg_color
```

### Background Style:
```php
@if($taskbarUseGradient)
    background: linear-gradient(to right, {{ $taskbarGradientFrom }}, {{ $taskbarGradientTo }});
@else
    background-color: {{ $taskbarBgColor }};
@endif
```

---

## ⚠️ หมายเหตุ:

1. **RGB Border**: การตั้งค่าสีไม่มีผลต่อ RGB border animation (ควบคุมผ่าน `millennium_rgb_enabled`)
2. **Transparency**: ความโปร่งใสควบคุมผ่าง `windows_taskbar_transparency` (0-100)
3. **Blur**: Backdrop blur ควบคุมผ่าง `windows_taskbar_blur` และ `millennium_taskbar_blur_amount`
4. **Cache**: หลังจากเปลี่ยนค่า ต้อง clear cache: `php artisan optimize:clear`

---

## 🚀 ขั้นตอนการปรับแต่ง:

1. **เลือกสีที่ต้องการ** - ใช้ color picker หรือ hex code
2. **อัปเดตการตั้งค่า** - ผ่าน admin UI หรือ database
3. **Clear cache** - `php artisan optimize:clear`
4. **Reload หน้า** - กด Ctrl+F5 หรือ Cmd+Shift+R

---

สร้างโดย: Claude Code Assistant
อัปเดตล่าสุด: 2025-01-10
