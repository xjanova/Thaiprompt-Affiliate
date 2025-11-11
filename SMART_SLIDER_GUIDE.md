# 🎨 Smart Slider Pro - คู่มือการใช้งาน

**Smart Slider Pro for Laravel** - ระบบ Slider ที่ทรงพลังกว่า Smart Slider 3 สำหรับ Laravel

---

## 📋 สารบัญ

1. [ติดตั้งและเริ่มต้นใช้งาน](#installation)
2. [การใช้งานพื้นฐาน](#basic-usage)
3. [สร้าง Slider ด้วย Code](#creating-slider)
4. [Layer System](#layer-system)
5. [Responsive Settings](#responsive)
6. [Animation Effects](#animations)
7. [API Reference](#api)

---

## 🚀 ติดตั้งและเริ่มต้นใช้งาน {#installation}

### 1. Run Migration

```bash
php artisan migrate
```

### 2. เข้าใช้งาน Admin Panel

เข้าไปที่: `http://your-domain.com/admin/smart-sliders`

---

## 💡 การใช้งานพื้นฐาน {#basic-usage}

### แสดง Slider บนหน้าเว็บ

#### วิธีที่ 1: ใช้ Blade Component (แนะนำ)

```blade
{{-- ใช้ Slider ID --}}
<x-smart-slider :slider="1" />

{{-- หรือใช้ Alias --}}
<x-smart-slider slider="hero-slider" />

{{-- หรือส่ง Object --}}
@php
    $slider = \App\Models\SmartSlider::findByAlias('hero-slider');
@endphp
<x-smart-slider :slider="$slider" />
```

#### วิธีที่ 2: ใช้ Include

```blade
@include('components.smart-slider', ['slider' => 'hero-slider'])
```

### แสดงบนหน้าแรก (Hero Section)

เปิดไฟล์ `resources/views/frontend/home.blade.php`:

```blade
@extends('layouts.app')

@section('content')
    {{-- Hero Slider --}}
    <x-smart-slider slider="homepage-hero" />

    {{-- เนื้อหาส่วนอื่นๆ --}}
    <section>
        ...
    </section>
@endsection
```

---

## 🎯 สร้าง Slider ด้วย Code {#creating-slider}

### ตัวอย่าง: สร้าง Hero Slider

```php
use App\Models\SmartSlider;
use App\Models\SmartSlide;
use App\Models\SmartSlideLayer;

// 1. สร้าง Slider
$slider = SmartSlider::create([
    'name' => 'Homepage Hero',
    'alias' => 'homepage-hero',
    'type' => 'simple',
    'width' => 1920,
    'height' => 800,
    'responsive_mode' => 'fullwidth',
    'is_published' => true,
]);

// 2. สร้าง Slide แรก
$slide1 = $slider->slides()->create([
    'title' => 'Welcome to Thai Prompt',
    'background' => [
        'type' => 'gradient',
        'gradient' => [
            'type' => 'linear',
            'angle' => 135,
            'colors' => ['#667eea', '#764ba2'],
        ],
    ],
    'order' => 0,
]);

// 3. เพิ่ม Layer: Heading
$slide1->layers()->create([
    'type' => 'heading',
    'content' => 'ยินดีต้อนรับสู่ไทยพร๊อม',
    'position' => [
        'mode' => 'default',
        'align' => 'center',
        'justify' => 'center',
    ],
    'style' => [
        'font_size' => 72,
        'font_weight' => 700,
        'color' => '#ffffff',
        'text_align' => 'center',
    ],
    'animation' => [
        'animation_in' => 'fadeInDown',
        'delay' => 0,
        'duration' => 1000,
    ],
    'order' => 0,
]);

// 4. เพิ่ม Layer: Text
$slide1->layers()->create([
    'type' => 'text',
    'content' => 'แพลตฟอร์มธุรกิจออนไลน์ครบวงจร',
    'position' => [
        'mode' => 'default',
        'align' => 'center',
        'justify' => 'center',
    ],
    'style' => [
        'font_size' => 24,
        'color' => '#ffffff',
        'margin' => [20, 0, 0, 0],
    ],
    'animation' => [
        'animation_in' => 'fadeInUp',
        'delay' => 300,
        'duration' => 1000,
    ],
    'order' => 1,
]);

// 5. เพิ่ม Layer: Button
$slide1->layers()->create([
    'type' => 'button',
    'content' => 'เริ่มต้นใช้งานฟรี',
    'link_url' => '/register',
    'link_target' => '_self',
    'position' => [
        'mode' => 'default',
        'align' => 'center',
        'justify' => 'center',
    ],
    'style' => [
        'font_size' => 18,
        'font_weight' => 600,
        'color' => '#ffffff',
        'background_color' => '#3B82F6',
        'padding' => [16, 32, 16, 32],
        'border' => [
            'radius' => 8,
        ],
        'margin' => [40, 0, 0, 0],
    ],
    'animation' => [
        'animation_in' => 'zoomIn',
        'delay' => 600,
        'duration' => 800,
    ],
    'order' => 2,
]);
```

---

## 🎨 Layer System {#layer-system}

### ประเภท Layer ที่รองรับ

1. **Heading** - หัวข้อใหญ่
2. **Text** - ข้อความทั่วไป
3. **Image** - รูปภาพ
4. **Button** - ปุ่มกดได้
5. **Video YouTube** - วิดีโอจาก YouTube
6. **Video Vimeo** - วิดีโอจาก Vimeo
7. **Video Upload** - วิดีโอที่อัปโหลดเอง
8. **HTML** - โค้ด HTML แบบกำหนดเอง

### การจัด Position

#### Default Mode (Flexbox)

```php
'position' => [
    'mode' => 'default',
    'align' => 'center',      // left, center, right
    'justify' => 'center',    // top, center, bottom
]
```

#### Absolute Mode (Drag & Drop)

```php
'position' => [
    'mode' => 'absolute',
    'x' => 100,               // ตำแหน่ง X (px)
    'y' => 200,               // ตำแหน่ง Y (px)
    'width' => '300px',       // ความกว้าง
    'height' => 'auto',       // ความสูง
    'z_index' => 10,          // ลำดับชั้น
]
```

---

## 📱 Responsive Settings {#responsive}

### กำหนดการแสดงผลตามอุปกรณ์

```php
'responsive' => [
    'desktop' => [
        'visible' => true,
        'font_size_scale' => 1,
    ],
    'tablet' => [
        'visible' => true,
        'font_size_scale' => 0.8,
    ],
    'mobile' => [
        'visible' => true,
        'font_size_scale' => 0.6,
    ],
]
```

---

## ✨ Animation Effects {#animations}

### Animation Presets (Animate.css)

**Fade Effects:**
- `fadeIn`, `fadeInDown`, `fadeInUp`, `fadeInLeft`, `fadeInRight`
- `fadeOut`, `fadeOutDown`, `fadeOutUp`, `fadeOutLeft`, `fadeOutRight`

**Slide Effects:**
- `slideInDown`, `slideInUp`, `slideInLeft`, `slideInRight`
- `slideOutDown`, `slideOutUp`, `slideOutLeft`, `slideOutRight`

**Zoom Effects:**
- `zoomIn`, `zoomInDown`, `zoomInUp`, `zoomInLeft`, `zoomInRight`
- `zoomOut`, `zoomOutDown`, `zoomOutUp`, `zoomOutLeft`, `zoomOutRight`

**Bounce Effects:**
- `bounceIn`, `bounceInDown`, `bounceInUp`, `bounceInLeft`, `bounceInRight`

**Flip Effects:**
- `flipInX`, `flipInY`, `flipOutX`, `flipOutY`

**Rotate Effects:**
- `rotateIn`, `rotateInDownLeft`, `rotateInDownRight`, `rotateInUpLeft`, `rotateInUpRight`

### ตัวอย่างการใช้งาน

```php
'animation' => [
    'animation_in' => 'fadeInDown',
    'animation_out' => 'fadeOut',
    'delay' => 500,           // หน่วงเวลา (ms)
    'duration' => 1000,       // ระยะเวลา (ms)
    'easing' => 'ease-in-out',
]
```

---

## 🔧 API Reference {#api}

### Slider Settings

```php
'settings' => [
    'slide_duration' => 5000,          // เวลาแต่ละสไลด์ (ms)
    'animation_duration' => 800,       // ความเร็วแอนิเมชัน (ms)
    'animation_type' => 'horizontal',  // horizontal, vertical, fade
    'autoplay' => true,
    'loop' => true,
    'pause_on_hover' => true,
    'keyboard_navigation' => true,
    'touch_swipe' => true,
]
```

### Control Settings

```php
'controls' => [
    'arrows' => [
        'enabled' => true,
        'style' => 'default',
        'position' => 'inside',        // inside, outside
        'color' => '#ffffff',
    ],
    'bullets' => [
        'enabled' => true,
        'style' => 'default',
        'position' => 'bottom',        // top, bottom
    ],
    'progress_bar' => [
        'enabled' => false,
        'position' => 'top',           // top, bottom
    ],
]
```

### Background Types

#### Image Background
```php
'background' => [
    'type' => 'image',
    'image' => 'sliders/hero-bg.jpg',
    'size' => 'cover',                 // cover, contain
    'position' => 'center center',
]
```

#### Gradient Background
```php
'background' => [
    'type' => 'gradient',
    'gradient' => [
        'type' => 'linear',
        'angle' => 135,
        'colors' => ['#667eea', '#764ba2', '#f093fb'],
    ],
]
```

#### Video Background
```php
'background' => [
    'type' => 'video',
    'video' => 'sliders/hero-video.mp4',
]
```

---

## 📊 Analytics & Tracking

### Track Slider Views

```javascript
// ติดตามอัตโนมัติเมื่อโหลด Slider
```

### Track Clicks

```javascript
// ติดตามอัตโนมัติเมื่อคลิก Link ใน Slide
```

### ดูสถิติใน Admin

```
/admin/smart-sliders/{id}/analytics
```

---

## 🎯 ตัวอย่างการใช้งานจริง

### 1. Hero Slider แบบ Fullscreen

```php
$slider = SmartSlider::create([
    'name' => 'Homepage Hero',
    'responsive_mode' => 'fullpage',   // Fullscreen
    'width' => 1920,
    'height' => 1080,
]);
```

### 2. Product Showcase Slider

```php
$slider = SmartSlider::create([
    'name' => 'Product Showcase',
    'type' => 'carousel',
    'width' => 1200,
    'height' => 600,
]);
```

### 3. Testimonial Slider

```php
$slider = SmartSlider::create([
    'name' => 'Customer Reviews',
    'type' => 'block',
    'width' => 800,
    'height' => 400,
]);
```

---

## 🚀 ขั้นตอนต่อไป

1. **Run Migration**: `php artisan migrate`
2. **เข้า Admin Panel**: สร้าง Slider แรกของคุณ
3. **แสดงบนหน้าเว็บ**: ใช้ `<x-smart-slider slider="your-alias" />`
4. **Customize**: ปรับแต่งตามความต้องการ

---

## 📚 Resources

- **Swiper.js Docs**: https://swiperjs.com/
- **Animate.css**: https://animate.style/
- **Tailwind CSS**: https://tailwindcss.com/

---

## ⚡ Pro Tips

1. ใช้ **Gradient Background** แทน Image เพื่อ Performance ดีขึ้น
2. ตั้งค่า **Lazy Loading** สำหรับ Image Layer
3. ใช้ **WebP Format** สำหรับรูปภาพ
4. จำกัด **Animation Delay** ไม่เกิน 1000ms
5. ใช้ **Absolute Position** แค่ Layer ที่จำเป็น

---

**Made with ❤️ for Thai Prompt Platform**
