# 🎨 V3 UI Design System - Modern & Premium

> **ระบบออกแบบ UI สำหรับ Version 3**
> เน้น Modern, Premium, มีมิติ, สวยงามหลักล้าน

**Version**: 3.0.0
**Last Updated**: 2025-11-15

---

## 📋 สารบัญ

1. [ปรัชญาการออกแบบ](#ปรัชญาการออกแบบ)
2. [Color System](#color-system)
3. [Typography](#typography)
4. [Spacing & Layout](#spacing--layout)
5. [Components Library](#components-library)
6. [Animation & Transitions](#animation--transitions)
7. [Patterns & Effects](#patterns--effects)
8. [Dark Mode](#dark-mode)
9. [Responsive Design](#responsive-design)

---

## ปรัชญาการออกแบบ

### 🎯 หลักการหลัก

**V3 Design Philosophy**:

1. **🌈 สีสันสดใส** - ใช้ gradient และสีที่มีชีวิตชีวา
2. **📐 มีมิติ** - shadow, depth, 3D effects
3. **✨ Micro-interactions** - animation ตอบสนองทุกการกระทำ
4. **🎭 Glassmorphism** - ความโปร่งใส backdrop blur
5. **💎 Premium Feel** - ดูมีค่า น่าใช้งาน
6. **🚀 Performance** - สวยแต่ไม่หนัก

### 🎨 Design Trends ที่ใช้

```
┌─────────────────────────────────────┐
│  V3 Design Trends                   │
├─────────────────────────────────────┤
│  ✅ Glassmorphism                   │
│  ✅ Neumorphism (บางส่วน)           │
│  ✅ 3D Transforms                   │
│  ✅ Gradient Meshes                 │
│  ✅ Soft Shadows                    │
│  ✅ Micro-animations                │
│  ✅ Smooth Transitions              │
│  ✅ Interactive Feedback            │
└─────────────────────────────────────┘
```

---

## Color System

### 🎨 Primary Palette

```javascript
// tailwind.config.js
theme: {
    extend: {
        colors: {
            // Primary Brand Color
            primary: {
                50: '#eff6ff',
                100: '#dbeafe',
                200: '#bfdbfe',
                300: '#93c5fd',
                400: '#60a5fa',
                500: '#3b82f6',  // Main
                600: '#2563eb',
                700: '#1d4ed8',
                800: '#1e40af',
                900: '#1e3a8a',
            },

            // Secondary Colors
            secondary: {
                50: '#faf5ff',
                500: '#a855f7',  // Purple
                600: '#9333ea',
            },

            // Accent Colors
            accent: {
                blue: '#0ea5e9',
                purple: '#a855f7',
                pink: '#ec4899',
                green: '#10b981',
                yellow: '#f59e0b',
                red: '#ef4444',
            }
        }
    }
}
```

### 🌈 Gradient Combinations

**ใช้ gradient เหล่านี้สำหรับ V3**:

```blade
{{-- Primary Gradient --}}
<div class="bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500">

{{-- Success Gradient --}}
<div class="bg-gradient-to-r from-green-400 to-emerald-600">

{{-- Warning Gradient --}}
<div class="bg-gradient-to-r from-yellow-400 to-orange-500">

{{-- Danger Gradient --}}
<div class="bg-gradient-to-r from-red-500 to-pink-600">

{{-- Info Gradient --}}
<div class="bg-gradient-to-r from-cyan-400 to-blue-500">

{{-- Premium Gradient --}}
<div class="bg-gradient-to-br from-purple-600 via-pink-500 to-red-500">

{{-- Mesh Gradient (ซับซ้อน) --}}
<div class="bg-gradient-to-br from-blue-400 via-purple-500 to-pink-500 relative">
    <div class="absolute inset-0 bg-gradient-to-tr from-green-400/30 via-transparent to-yellow-400/30"></div>
</div>
```

### 🎨 Color Usage Guidelines

| สี | ใช้สำหรับ | ตัวอย่าง |
|---|-----------|---------|
| **Blue → Purple** | Primary actions, Headers | ปุ่มหลัก, Header |
| **Green → Emerald** | Success, Positive actions | ส่งออก, สำเร็จ |
| **Yellow → Orange** | Warnings, Attention | คำเตือน, รอดำเนินการ |
| **Red → Pink** | Errors, Danger actions | ลบ, ยกเลิก |
| **Cyan → Blue** | Info, Neutral actions | ข้อมูล, อ่านเพิ่ม |
| **Purple → Pink** | Premium features | Pro, Enterprise |

---

## Typography

### 📝 Font Stack

```css
/* Tailwind Config */
fontFamily: {
    sans: ['Inter', 'Noto Sans Thai', 'system-ui', 'sans-serif'],
    display: ['Poppins', 'Noto Sans Thai', 'sans-serif'],
    mono: ['Fira Code', 'monospace'],
}
```

### 📏 Font Sizes

```blade
{{-- Headings --}}
<h1 class="text-4xl md:text-5xl lg:text-6xl font-bold">Hero Title</h1>
<h2 class="text-3xl md:text-4xl font-bold">Section Title</h2>
<h3 class="text-2xl md:text-3xl font-semibold">Card Title</h3>
<h4 class="text-xl md:text-2xl font-semibold">Sub Title</h4>

{{-- Body Text --}}
<p class="text-base md:text-lg">Normal paragraph</p>
<p class="text-sm">Small text, descriptions</p>
<p class="text-xs">Tiny text, metadata</p>

{{-- Display Text (ใช้สำหรับตัวเลขใหญ่) --}}
<div class="text-6xl md:text-7xl lg:text-8xl font-bold font-display">
    1,234
</div>
```

### 🎨 Text Styles

```blade
{{-- Gradient Text --}}
<h1 class="text-5xl font-bold bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500 bg-clip-text text-transparent">
    Gradient Text
</h1>

{{-- Text with Shadow --}}
<h2 class="text-4xl font-bold text-white drop-shadow-[0_4px_8px_rgba(0,0,0,0.3)]">
    Text with Shadow
</h2>

{{-- Stroke Text --}}
<h3 class="text-5xl font-bold text-transparent"
    style="-webkit-text-stroke: 2px #3b82f6;">
    Stroke Text
</h3>
```

---

## Spacing & Layout

### 📐 Spacing Scale

ใช้ Tailwind spacing scale แบบ consistent:

```blade
{{-- Padding --}}
p-2   → 0.5rem (8px)
p-4   → 1rem (16px)    <!-- Component padding เริ่มต้น -->
p-6   → 1.5rem (24px)  <!-- Card padding -->
p-8   → 2rem (32px)    <!-- Section padding -->
p-12  → 3rem (48px)    <!-- Page padding -->

{{-- Margin --}}
space-y-2   → gap 0.5rem
space-y-4   → gap 1rem     <!-- Component gap เริ่มต้น -->
space-y-6   → gap 1.5rem   <!-- Section gap -->
space-y-8   → gap 2rem     <!-- Large section gap -->
```

### 🏗️ Layout Patterns

#### Grid Layout

```blade
{{-- Auto-fit Grid --}}
<div class="grid grid-cols-[repeat(auto-fit,minmax(280px,1fr))] gap-6">
    <!-- Cards auto-adjust -->
</div>

{{-- Responsive Grid --}}
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
    <!-- Responsive columns -->
</div>

{{-- Dashboard Layout --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- 2/3 Main Content -->
    <div class="lg:col-span-2">
        <!-- Main content -->
    </div>

    <!-- 1/3 Sidebar -->
    <div>
        <!-- Sidebar -->
    </div>
</div>
```

#### Flexbox Layout

```blade
{{-- Center Everything --}}
<div class="flex items-center justify-center min-h-screen">
    <!-- Centered content -->
</div>

{{-- Space Between --}}
<div class="flex items-center justify-between">
    <div>Left</div>
    <div>Right</div>
</div>

{{-- Vertical Stack --}}
<div class="flex flex-col gap-4">
    <!-- Stacked items -->
</div>
```

---

## Components Library

### 1. 💳 Premium Card

```blade
{{-- Modern Card with 3D Effect --}}
<div class="group perspective-1000">
    <div class="relative transform-gpu transition-all duration-500
                group-hover:scale-105 group-hover:rotate-y-2">

        {{-- Glow Effect --}}
        <div class="absolute inset-0 bg-gradient-to-br from-blue-500 to-purple-600
                    rounded-2xl blur-xl opacity-50 group-hover:opacity-75
                    transition-opacity"></div>

        {{-- Card Body --}}
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl
                    p-6 border border-gray-200 dark:border-gray-700">

            {{-- Icon --}}
            <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-purple-600
                        rounded-2xl flex items-center justify-center mb-4
                        shadow-lg">
                <i class="fas fa-rocket text-white text-2xl"></i>
            </div>

            {{-- Title --}}
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                Card Title
            </h3>

            {{-- Description --}}
            <p class="text-gray-600 dark:text-gray-400 mb-4">
                Card description goes here. Lorem ipsum dolor sit amet.
            </p>

            {{-- Action Button --}}
            <button class="px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-600
                           text-white rounded-xl hover:shadow-lg
                           transform hover:scale-105 transition-all">
                <i class="fas fa-arrow-right mr-2"></i>
                ดูรายละเอียด
            </button>
        </div>
    </div>
</div>

<style>
.perspective-1000 { perspective: 1000px; }
.rotate-y-2 { transform: rotateY(2deg); }
</style>
```

### 2. 🎭 Glassmorphism Card

```blade
{{-- Glass Card --}}
<div class="relative overflow-hidden rounded-2xl">
    {{-- Background Gradient --}}
    <div class="absolute inset-0 bg-gradient-to-br from-blue-400 to-purple-600"></div>

    {{-- Glass Layer --}}
    <div class="relative bg-white/10 dark:bg-black/10 backdrop-blur-lg
                border border-white/20 rounded-2xl p-6">

        <h3 class="text-2xl font-bold text-white mb-2">
            Glassmorphism Card
        </h3>

        <p class="text-white/90">
            This card uses backdrop blur and transparency for a modern glass effect.
        </p>

        <button class="mt-4 px-4 py-2 bg-white/20 hover:bg-white/30
                       backdrop-blur-sm border border-white/30
                       text-white rounded-lg transition-all">
            Click Me
        </button>
    </div>
</div>
```

### 3. 🔘 Modern Buttons

```blade
{{-- Premium Gradient Button --}}
<button class="group relative overflow-hidden px-8 py-4
               bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500
               text-white font-bold rounded-xl
               shadow-lg hover:shadow-2xl
               transform hover:scale-105
               transition-all duration-300">

    {{-- Shine Effect --}}
    <div class="absolute inset-0
                bg-gradient-to-r from-transparent via-white/20 to-transparent
                -translate-x-full group-hover:translate-x-full
                transition-transform duration-1000"></div>

    {{-- Content --}}
    <span class="relative z-10 flex items-center gap-2">
        <i class="fas fa-rocket"></i>
        <span>เริ่มต้นใช้งาน</span>
    </span>
</button>

{{-- Outline Button --}}
<button class="px-6 py-3 border-2 border-blue-500 text-blue-500
               rounded-xl hover:bg-blue-500 hover:text-white
               transition-all duration-300">
    Outline Button
</button>

{{-- Ghost Button --}}
<button class="px-6 py-3 text-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/20
               rounded-xl transition-all">
    Ghost Button
</button>

{{-- Icon Button --}}
<button class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600
               text-white rounded-xl hover:shadow-lg
               transform hover:scale-110 transition-all">
    <i class="fas fa-heart"></i>
</button>
```

### 4. 📝 Input Fields

```blade
{{-- Modern Input with Icon --}}
<div class="relative">
    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
        <i class="fas fa-search text-gray-400"></i>
    </div>

    <input type="text"
           placeholder="ค้นหา..."
           class="w-full pl-12 pr-4 py-3
                  bg-white dark:bg-gray-800
                  border-2 border-gray-200 dark:border-gray-700
                  rounded-xl
                  focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20
                  transition-all
                  placeholder:text-gray-400">
</div>

{{-- Floating Label Input --}}
<div class="relative" x-data="{ focused: false, filled: false }">
    <input type="text"
           @focus="focused = true"
           @blur="focused = false"
           @input="filled = $event.target.value !== ''"
           class="peer w-full px-4 pt-6 pb-2
                  bg-white dark:bg-gray-800
                  border-2 border-gray-200 dark:border-gray-700
                  rounded-xl
                  focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20
                  transition-all">

    <label class="absolute left-4 top-4
                  text-gray-400
                  transition-all duration-200
                  peer-focus:top-2 peer-focus:text-xs peer-focus:text-blue-500"
           :class="filled && 'top-2 text-xs'">
        ชื่อผู้ใช้
    </label>
</div>

{{-- Textarea with Character Count --}}
<div x-data="{ text: '', maxLength: 200 }">
    <textarea x-model="text"
              :maxlength="maxLength"
              class="w-full px-4 py-3
                     bg-white dark:bg-gray-800
                     border-2 border-gray-200 dark:border-gray-700
                     rounded-xl
                     focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20
                     transition-all
                     resize-none"
              rows="4"
              placeholder="เขียนข้อความ..."></textarea>

    <div class="flex justify-end mt-2 text-sm text-gray-500">
        <span x-text="text.length"></span>
        <span>/</span>
        <span x-text="maxLength"></span>
    </div>
</div>
```

### 5. 🎚️ Toggle Switch

```blade
{{-- Modern Toggle --}}
<label class="relative inline-flex items-center cursor-pointer">
    <input type="checkbox" class="sr-only peer" x-model="enabled">

    <div class="w-16 h-8
                bg-gray-300 dark:bg-gray-600
                rounded-full
                peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-500/20
                peer-checked:bg-gradient-to-r peer-checked:from-blue-500 peer-checked:to-purple-600
                transition-all
                relative">

        {{-- Toggle Circle --}}
        <div class="absolute top-1 left-1
                    bg-white
                    w-6 h-6
                    rounded-full
                    shadow-md
                    peer-checked:translate-x-8
                    transition-transform"></div>
    </div>

    <span class="ml-3 text-sm font-medium text-gray-900 dark:text-gray-300">
        เปิดใช้งาน
    </span>
</label>
```

### 6. 📊 Progress Bar

```blade
{{-- Animated Progress Bar --}}
<div x-data="{ progress: 0 }" x-init="setInterval(() => { progress = Math.min(progress + 1, 100) }, 50)">

    {{-- Label --}}
    <div class="flex justify-between mb-2">
        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
            กำลังโหลด...
        </span>
        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
            <span x-text="progress"></span>%
        </span>
    </div>

    {{-- Progress Track --}}
    <div class="w-full h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
        {{-- Progress Fill --}}
        <div class="h-full bg-gradient-to-r from-blue-500 to-purple-600
                    rounded-full transition-all duration-300 ease-out"
             :style="`width: ${progress}%`"></div>
    </div>
</div>

{{-- Striped Progress --}}
<div class="w-full h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
    <div class="h-full bg-gradient-to-r from-blue-500 to-purple-600
                rounded-full
                animate-pulse"
         style="width: 75%;
                background-image: linear-gradient(45deg,
                    rgba(255,255,255,.15) 25%,
                    transparent 25%,
                    transparent 50%,
                    rgba(255,255,255,.15) 50%,
                    rgba(255,255,255,.15) 75%,
                    transparent 75%,
                    transparent);
                background-size: 1rem 1rem;"></div>
</div>
```

### 7. 🎨 Badge & Tags

```blade
{{-- Gradient Badge --}}
<span class="inline-flex items-center px-3 py-1
             bg-gradient-to-r from-blue-500 to-purple-600
             text-white text-xs font-bold
             rounded-full">
    ใหม่
</span>

{{-- Outline Badge --}}
<span class="inline-flex items-center px-3 py-1
             border-2 border-blue-500 text-blue-500
             text-xs font-semibold
             rounded-full">
    Pro
</span>

{{-- Tag with Icon --}}
<span class="inline-flex items-center gap-1 px-3 py-1
             bg-blue-100 dark:bg-blue-900/30
             text-blue-700 dark:text-blue-300
             text-sm font-medium
             rounded-lg">
    <i class="fas fa-tag"></i>
    <span>หมวดหมู่</span>
    <button class="ml-1 hover:text-blue-900 dark:hover:text-blue-100">
        <i class="fas fa-times"></i>
    </button>
</span>
```

---

## Animation & Transitions

### ⚡ Utility Animations

```blade
{{-- Fade In --}}
<div class="animate-fade-in">Content</div>

{{-- Slide Up --}}
<div class="animate-slide-up">Content</div>

{{-- Scale In --}}
<div class="animate-scale-in">Content</div>

{{-- Bounce --}}
<div class="animate-bounce">Content</div>

{{-- Pulse --}}
<div class="animate-pulse">Loading...</div>

{{-- Spin --}}
<div class="animate-spin">
    <i class="fas fa-spinner"></i>
</div>
```

### 🎭 Custom Animations

**Tailwind Config**:

```javascript
// tailwind.config.js
theme: {
    extend: {
        animation: {
            'fade-in': 'fadeIn 0.3s ease-in-out',
            'slide-up': 'slideUp 0.4s ease-out',
            'scale-in': 'scaleIn 0.2s ease-out',
            'shimmer': 'shimmer 2s linear infinite',
        },
        keyframes: {
            fadeIn: {
                '0%': { opacity: '0' },
                '100%': { opacity: '1' },
            },
            slideUp: {
                '0%': { transform: 'translateY(20px)', opacity: '0' },
                '100%': { transform: 'translateY(0)', opacity: '1' },
            },
            scaleIn: {
                '0%': { transform: 'scale(0.95)', opacity: '0' },
                '100%': { transform: 'scale(1)', opacity: '1' },
            },
            shimmer: {
                '0%': { backgroundPosition: '-1000px 0' },
                '100%': { backgroundPosition: '1000px 0' },
            },
        },
    },
}
```

### 🌊 Transition Patterns

```blade
{{-- Smooth All Properties --}}
<div class="transition-all duration-300 ease-in-out">

{{-- Specific Properties --}}
<div class="transition-[background-color,transform] duration-300">

{{-- Stagger Animations (ใช้กับ list) --}}
<div class="space-y-4">
    <div class="animate-slide-up" style="animation-delay: 0ms;">Item 1</div>
    <div class="animate-slide-up" style="animation-delay: 100ms;">Item 2</div>
    <div class="animate-slide-up" style="animation-delay: 200ms;">Item 3</div>
</div>

{{-- Hover Grow --}}
<div class="transform hover:scale-110 transition-transform duration-300">

{{-- Hover Lift --}}
<div class="transform hover:-translate-y-2 hover:shadow-2xl
            transition-all duration-300">
```

---

## Patterns & Effects

### ✨ Background Patterns

```blade
{{-- Dots Pattern --}}
<div class="relative bg-blue-500">
    <div class="absolute inset-0 opacity-20"
         style="background-image: radial-gradient(circle, white 1px, transparent 1px);
                background-size: 20px 20px;"></div>
    <div class="relative z-10 p-8">
        Content with dots pattern
    </div>
</div>

{{-- Grid Pattern --}}
<div class="relative bg-purple-500">
    <div class="absolute inset-0 opacity-10"
         style="background-image: linear-gradient(white 1px, transparent 1px),
                                   linear-gradient(90deg, white 1px, transparent 1px);
                background-size: 20px 20px;"></div>
    <div class="relative z-10 p-8">
        Content with grid pattern
    </div>
</div>

{{-- Diagonal Lines --}}
<div class="relative bg-pink-500">
    <div class="absolute inset-0 opacity-20"
         style="background-image: repeating-linear-gradient(45deg,
                                   transparent,
                                   transparent 10px,
                                   white 10px,
                                   white 20px);"></div>
    <div class="relative z-10 p-8">
        Content with diagonal lines
    </div>
</div>
```

### 🌟 Glow Effects

```blade
{{-- Button Glow --}}
<button class="px-6 py-3 bg-blue-500 text-white rounded-xl
               shadow-[0_0_20px_rgba(59,130,246,0.5)]
               hover:shadow-[0_0_40px_rgba(59,130,246,0.8)]
               transition-shadow duration-300">
    Glowing Button
</button>

{{-- Card Glow --}}
<div class="bg-white dark:bg-gray-800 rounded-2xl p-6
            shadow-[0_0_50px_rgba(59,130,246,0.3)]">
    Glowing Card
</div>

{{-- Text Glow --}}
<h1 class="text-4xl font-bold text-white
           drop-shadow-[0_0_20px_rgba(255,255,255,0.8)]">
    Glowing Text
</h1>
```

---

## Dark Mode

### 🌓 Dark Mode Best Practices

```blade
{{-- ทุก Component ต้องรองรับ Dark Mode --}}

{{-- Background Colors --}}
<div class="bg-white dark:bg-gray-800">

{{-- Text Colors --}}
<p class="text-gray-900 dark:text-gray-100">
<p class="text-gray-600 dark:text-gray-400">

{{-- Border Colors --}}
<div class="border border-gray-200 dark:border-gray-700">

{{-- Shadows --}}
<div class="shadow-lg dark:shadow-gray-900/50">

{{-- Buttons --}}
<button class="bg-blue-500 hover:bg-blue-600
               dark:bg-blue-600 dark:hover:bg-blue-700">
```

### 🎨 Dark Mode Color Mapping

| Light Mode | Dark Mode |
|------------|-----------|
| `bg-white` | `dark:bg-gray-800` |
| `bg-gray-50` | `dark:bg-gray-900` |
| `bg-gray-100` | `dark:bg-gray-800` |
| `text-gray-900` | `dark:text-white` |
| `text-gray-600` | `dark:text-gray-400` |
| `border-gray-200` | `dark:border-gray-700` |

---

## Responsive Design

### 📱 Breakpoints

```
sm:   640px   (Mobile Landscape)
md:   768px   (Tablet)
lg:   1024px  (Desktop)
xl:   1280px  (Large Desktop)
2xl:  1536px  (Extra Large)
```

### 🎯 Responsive Patterns

```blade
{{-- Mobile-First Typography --}}
<h1 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl">

{{-- Responsive Grid --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">

{{-- Responsive Padding --}}
<div class="p-4 md:p-6 lg:p-8">

{{-- Hide/Show on Different Screens --}}
<div class="block md:hidden">Mobile Only</div>
<div class="hidden md:block">Desktop Only</div>

{{-- Responsive Flex Direction --}}
<div class="flex flex-col md:flex-row">
```

---

**สร้างโดย**: Design Team
**สำหรับ**: Thaiprompt-Affiliate V3
**ลิขสิทธิ์**: © 2025 Thaiprompt

*"Design is not just what it looks like. Design is how it works." - Steve Jobs*
