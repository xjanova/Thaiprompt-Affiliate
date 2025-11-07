# 🎨 Icon System Guide

ระบบจัดการ Icons สำหรับโครงการ Thaiprompt-Affiliate v2.0.0

---

## 📁 โครงสร้างโฟลเดอร์

```
public/icons/
├── system/         # Icons สำหรับระบบ (dashboard, settings, users, etc.)
├── theme/          # Icons เกี่ยวกับ theme (color palette, brush, etc.)
├── custom/         # Icons ที่แอดมินอัพโหลดเอง
├── social/         # Social media icons (Facebook, LINE, Twitter, etc.)
└── flags/          # ธงประเทศต่างๆ

storage/app/public/icons/  # สำรองเก็บใน storage (optional)
├── system/
├── theme/
├── custom/
├── social/
└── flags/
```

---

## 📤 วิธีอัพโหลด Icons

### 1. ผ่าน Admin UI (แนะนำ)

1. เข้าสู่ระบบเป็น Admin
2. ไปที่ `/admin/icons`
3. เลือก Category ที่ต้องการ (system, theme, custom, social, flags)
4. คลิก "Upload Icon"
5. เลือกไฟล์ icon (SVG, PNG, JPG, WebP)
6. ระบุชื่อ (optional)
7. อัพโหลด

### 2. อัพโหลดด้วยตนเอง (Manual)

วางไฟล์ icon ลงในโฟลเดอร์ที่ต้องการ:

```bash
# ตัวอย่าง: อัพโหลด LINE icon
cp line-logo.svg public/icons/social/line-logo.svg

# ตัวอย่าง: อัพโหลด custom icon
cp my-icon.png public/icons/custom/my-icon.png
```

---

## 🎯 วิธีใช้งาน Icons

### 1. ใช้ Blade Component (แนะนำ)

```blade
{{-- Basic Usage --}}
<x-icon name="dashboard" category="system" size="md" />

{{-- Custom Size --}}
<x-icon name="line-logo" category="social" size="lg" />

{{-- With Custom Class --}}
<x-icon name="settings" category="system" size="sm" class="text-blue-500" />

{{-- Inline SVG (for better control) --}}
<x-icon name="theme" category="theme" type="inline" size="xl" />
```

#### ขนาดที่รองรับ (Size Options):
- `xs` - 12px (w-3 h-3)
- `sm` - 16px (w-4 h-4)
- `md` - 20px (w-5 h-5) - **default**
- `lg` - 24px (w-6 h-6)
- `xl` - 32px (w-8 h-8)
- `2xl` - 40px (w-10 h-10)

---

### 2. ใช้ IconHelper Class

```php
use App\Helpers\IconHelper;

// Get icon URL
$iconUrl = IconHelper::url('dashboard', 'system');
// Returns: http://yourdomain.com/icons/system/dashboard.svg

// Check if icon exists
if (IconHelper::exists('line-logo', 'social')) {
    // Do something
}

// Get icon path
$path = IconHelper::path('settings', 'system');
// Returns: /var/www/html/public/icons/system/settings.svg

// Get list of all icons in category
$icons = IconHelper::list('social');
// Returns array of all social icons

// Get SVG content
$svgContent = IconHelper::svg('dashboard', 'system');

// Get inline SVG with custom attributes
$svg = IconHelper::inline('dashboard', 'system', [
    'class' => 'w-6 h-6 text-blue-500',
    'width' => '24',
    'height' => '24'
]);
```

---

### 3. ใช้ในไฟล์ Blade โดยตรง

```blade
{{-- Using Helper --}}
<img src="{{ IconHelper::url('dashboard', 'system') }}" class="w-6 h-6" alt="Dashboard">

{{-- Inline SVG --}}
{!! IconHelper::inline('settings', 'system', ['class' => 'w-5 h-5 text-gray-600']) !!}

{{-- Direct asset --}}
<img src="{{ asset('icons/social/line-logo.svg') }}" class="w-8 h-8" alt="LINE">
```

---

### 4. ใช้ใน Controller

```php
use App\Helpers\IconHelper;

class MyController extends Controller
{
    public function index()
    {
        // Upload icon
        if ($request->hasFile('icon')) {
            $filename = IconHelper::upload($request->file('icon'), 'custom', 'my-icon');
            if ($filename) {
                // Success!
                $url = IconHelper::url($filename, 'custom');
            }
        }

        // Get all icons
        $systemIcons = IconHelper::list('system');
        $socialIcons = IconHelper::list('social');

        // Delete icon
        IconHelper::delete('old-icon', 'custom');

        return view('my-view', compact('systemIcons', 'socialIcons'));
    }
}
```

---

## 🎨 รูปแบบไฟล์ที่รองรับ

| Format | Extension | Recommended | Notes |
|--------|-----------|-------------|-------|
| **SVG** | `.svg` | ✅ แนะนำมากที่สุด | Scalable, เหมาะกับ responsive |
| **PNG** | `.png` | ⭐ ดี | รองรับความโปร่งใส |
| **JPG** | `.jpg`, `.jpeg` | ⚠️ ใช้ได้ | ไม่มีความโปร่งใส |
| **WebP** | `.webp` | ⭐ ดี | ขนาดเล็ก, quality ดี |

**ขนาดไฟล์สูงสุด**: 2MB

---

## 📝 ตัวอย่างการใช้งาน

### ตัวอย่างที่ 1: แสดง Social Icons

```blade
<div class="flex gap-4">
    <a href="https://facebook.com">
        <x-icon name="facebook" category="social" size="lg" class="text-blue-600" />
    </a>
    <a href="https://line.me">
        <x-icon name="line" category="social" size="lg" class="text-green-500" />
    </a>
    <a href="https://twitter.com">
        <x-icon name="twitter" category="social" size="lg" class="text-sky-500" />
    </a>
</div>
```

### ตัวอย่างที่ 2: Menu Icons

```blade
<nav class="sidebar">
    <a href="/admin/dashboard" class="menu-item">
        <x-icon name="dashboard" category="system" size="md" />
        <span>Dashboard</span>
    </a>
    <a href="/admin/users" class="menu-item">
        <x-icon name="users" category="system" size="md" />
        <span>Users</span>
    </a>
    <a href="/admin/settings" class="menu-item">
        <x-icon name="settings" category="system" size="md" />
        <span>Settings</span>
    </a>
</nav>
```

### ตัวอย่างที่ 3: Theme Selector

```blade
<div class="theme-grid">
    @foreach($themes as $theme)
        <div class="theme-card">
            <x-icon name="palette" category="theme" size="xl" />
            <h3>{{ $theme->name }}</h3>
        </div>
    @endforeach
</div>
```

### ตัวอย่างที่ 4: Dynamic Icons

```blade
@php
use App\Helpers\IconHelper;

$menuItems = [
    ['name' => 'Dashboard', 'icon' => 'dashboard', 'url' => '/admin/dashboard'],
    ['name' => 'Users', 'icon' => 'users', 'url' => '/admin/users'],
    ['name' => 'Themes', 'icon' => 'palette', 'url' => '/admin/themes'],
    ['name' => 'Settings', 'icon' => 'settings', 'url' => '/admin/settings'],
];
@endphp

<ul>
    @foreach($menuItems as $item)
        <li>
            <a href="{{ $item['url'] }}">
                {!! IconHelper::inline($item['icon'], 'system', ['class' => 'w-5 h-5']) !!}
                <span>{{ $item['name'] }}</span>
            </a>
        </li>
    @endforeach
</ul>
```

---

## 🛠️ API Methods

### IconHelper::url()
```php
IconHelper::url(string $name, string $category = 'system', ?string $default = null): string
```
คืนค่า URL ของ icon

### IconHelper::path()
```php
IconHelper::path(string $name, string $category = 'system'): ?string
```
คืนค่า absolute path ของ icon

### IconHelper::exists()
```php
IconHelper::exists(string $name, string $category = 'system'): bool
```
ตรวจสอบว่า icon มีอยู่หรือไม่

### IconHelper::list()
```php
IconHelper::list(string $category = 'system'): array
```
คืนค่า array ของ icons ทั้งหมดใน category

### IconHelper::svg()
```php
IconHelper::svg(string $name, string $category = 'system'): ?string
```
คืนค่าเนื้อหา SVG

### IconHelper::inline()
```php
IconHelper::inline(string $name, string $category = 'system', array $attributes = []): string
```
คืนค่า inline SVG พร้อม custom attributes

### IconHelper::upload()
```php
IconHelper::upload($file, string $category = 'custom', ?string $name = null): string|false
```
อัพโหลด icon

### IconHelper::delete()
```php
IconHelper::delete(string $name, string $category = 'custom'): bool
```
ลบ icon

---

## 🎯 Best Practices

### 1. ตั้งชื่อไฟล์

✅ **ดี:**
- `dashboard.svg`
- `user-circle.svg`
- `line-logo.svg`
- `facebook-icon.svg`

❌ **ไม่ดี:**
- `Icon-1.svg`
- `my icon.svg` (มีช่องว่าง)
- `ไอคอน.svg` (ใช้ภาษาไทย)
- `icon_test_2023_v2_final.svg` (ยาวเกินไป)

### 2. เลือกรูปแบบที่เหมาะสม

- **SVG**: สำหรับ icons, logos, shapes
- **PNG**: สำหรับ icons ที่มีรายละเอียดมาก
- **WebP**: สำหรับ optimization

### 3. Optimize ก่อนอัพโหลด

- SVG: ใช้ SVGO หรือ SVGOMG
- PNG: ใช้ TinyPNG หรือ ImageOptim
- JPG: Compress ให้ได้ quality 80-85%

### 4. Category Organization

- `system/` - ใช้สำหรับ UI ภายในระบบ
- `theme/` - ใช้สำหรับ theme management
- `custom/` - ใช้สำหรับ icons ที่ไม่ใช่ระบบ
- `social/` - ใช้สำหรับ social media
- `flags/` - ใช้สำหรับธงชาติ

---

## 🔒 Security

- ✅ ตรวจสอบ file type ก่อนอัพโหลด
- ✅ จำกัดขนาดไฟล์ (max 2MB)
- ✅ Sanitize filename
- ✅ เฉพาะ Admin เท่านั้นที่อัพโหลดได้
- ✅ Custom category สามารถลบได้
- ✅ System/Theme categories ป้องกันการลบโดย super-admin เท่านั้น

---

## 📊 Icon Categories

| Category | Purpose | Examples |
|----------|---------|----------|
| **system** | System UI icons | dashboard, settings, users, menu, search |
| **theme** | Theme-related icons | palette, brush, color, dark-mode, light-mode |
| **custom** | Admin uploaded icons | Any custom icons |
| **social** | Social media | facebook, line, twitter, instagram, youtube |
| **flags** | Country flags | th, us, jp, cn, gb |

---

## 🚀 Performance Tips

1. **ใช้ SVG เมื่อทำได้**: Scalable และขนาดเล็ก
2. **Lazy Load สำหรับ PNG/JPG**: ใช้ loading="lazy"
3. **Cache Icons**: Icons ถูก cache โดยอัตโนมัติ
4. **Sprite Sheets**: สำหรับ icons ที่ใช้บ่อย
5. **WebP Fallback**: ใช้ WebP พร้อม PNG fallback

---

## 📚 Resources

### Free Icon Libraries
- [Heroicons](https://heroicons.com/) - Beautiful SVG icons
- [Feather Icons](https://feathericons.com/) - Simply beautiful icons
- [Font Awesome](https://fontawesome.com/) - Icon library
- [Material Icons](https://fonts.google.com/icons) - Google's icons
- [Flaticon](https://www.flaticon.com/) - Largest icon database
- [The Noun Project](https://thenounproject.com/) - Icons for everything

### Optimization Tools
- [SVGOMG](https://jakearchibald.github.io/svgomg/) - SVG optimizer
- [TinyPNG](https://tinypng.com/) - PNG compression
- [Squoosh](https://squoosh.app/) - Image compression

---

## 🐛 Troubleshooting

### Icons ไม่แสดง?

1. ตรวจสอบว่าไฟล์มีอยู่จริง
2. ตรวจสอบ permissions (755 for directories, 644 for files)
3. Clear cache: `php artisan cache:clear`
4. ตรวจสอบชื่อไฟล์และ category

### SVG ไม่แสดงสี?

- ใช้ `type="inline"` เพื่อควบคุมสีด้วย CSS
- ตรวจสอบว่า SVG ไม่มี fill/stroke ที่ hard-coded

### Icon เบลอ?

- ใช้ SVG แทน PNG/JPG
- ใช้ PNG ความละเอียดสูง (@2x, @3x)

---

## 💡 Advanced Usage

### Custom Icon Component

สร้าง component ของคุณเอง:

```blade
{{-- resources/views/components/social-icon.blade.php --}}
@props(['platform', 'size' => 'lg'])

@php
$colors = [
    'facebook' => 'text-blue-600 hover:text-blue-700',
    'line' => 'text-green-500 hover:text-green-600',
    'twitter' => 'text-sky-500 hover:text-sky-600',
];

$color = $colors[$platform] ?? 'text-gray-600';
@endphp

<a {{ $attributes->merge(['class' => "inline-flex items-center justify-center transition-colors {$color}"]) }}>
    <x-icon :name="$platform" category="social" :size="$size" />
</a>
```

ใช้งาน:
```blade
<x-social-icon platform="facebook" href="https://facebook.com" />
<x-social-icon platform="line" href="https://line.me" />
```

---

## 📞 Support

หากมีปัญหาหรือต้องการความช่วยเหลือ:
- เข้าสู่ `/admin/icons` เพื่อจัดการ icons
- อ่านเอกสารเพิ่มเติม: `ICON_SYSTEM_GUIDE.md`
- ติดต่อทีมพัฒนา

---

**Version**: 2.0.0 Phoenix
**Last Updated**: 2025-11-07
