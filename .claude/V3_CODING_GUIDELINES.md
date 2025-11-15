# 🚀 Version 3 Coding Guidelines - Thaiprompt-Affiliate

> **แนวทางการเขียนโค้ดสำหรับ Version 3**
> เน้น Tailwind CSS + Alpine.js + SortableJS
> UI สวยงาม มีมิติ ทันสมัย โค้ดเร็ว ไม่ซับซ้อน

**Version**: 3.0.0
**Last Updated**: 2025-11-15
**Status**: 🟢 Active - ใช้เป็นมาตรฐานหลักสำหรับ V3

---

## 📋 สารบัญ

1. [ภาพรวม Version 3](#ภาพรวม-version-3)
2. [เทคโนโลยีหลัก](#เทคโนโลยีหลัก)
3. [หลักการออกแบบ V3](#หลักการออกแบบ-v3)
4. [โครงสร้างโค้ด](#โครงสร้างโค้ด)
5. [UI Components V3](#ui-components-v3)
6. [Alpine.js Patterns](#alpinejs-patterns)
7. [SortableJS Integration](#sortablejs-integration)
8. [Performance Best Practices](#performance-best-practices)
9. [ตัวอย่างโค้ดสมบูรณ์](#ตัวอย่างโค้ดสมบูรณ์)

---

## ภาพรวม Version 3

### 🎯 เป้าหมายหลัก

**Version 3** มุ่งเน้นการพัฒนาที่:

1. **⚡ เร็ว** - Performance สูงสุด, โหลดเร็ว, ตอบสนองทันที
2. **🎨 สวยงาม** - UI/UX ระดับ premium, มีมิติ, น่าใช้งาน
3. **🧩 ง่าย** - โค้ดไม่ซับซ้อน, maintainable, reusable
4. **📱 ทันสมัย** - ใช้เทคโนโลยีล่าสุด, responsive, accessible

### 🔄 สิ่งที่เปลี่ยนแปลงจาก V2

| ด้าน | V2 (เก่า) | V3 (ใหม่) |
|------|-----------|----------|
| **JavaScript Framework** | jQuery + Vue.js | Alpine.js (เป็นหลัก) |
| **CSS Framework** | Bootstrap + Custom CSS | Tailwind CSS (pure) |
| **Drag & Drop** | jQuery UI Sortable | SortableJS |
| **State Management** | Vuex / Plain JS | Alpine.js Stores |
| **Component Pattern** | Mixed (Blade + Vue SFC) | Blade + Alpine Components |
| **Bundle Size** | ~500KB+ | ~150KB (target) |
| **Build Tools** | Laravel Mix | Vite |
| **UI Style** | Flat, Bootstrap-based | 3D, Glassmorphism, Gradients |

### ⚠️ สิ่งที่ยังคงใช้

- ✅ **Laravel 11** - Backend framework
- ✅ **Blade Templates** - Server-side rendering
- ✅ **Livewire** - สำหรับ real-time features เฉพาะที่จำเป็น
- ✅ **Chart.js, Three.js, D3.js** - สำหรับ visualization ที่ซับซ้อน

---

## เทคโนโลยีหลัก

### 1. 🎨 Tailwind CSS (Pure Utility-First)

**ทำไมใช้ Tailwind**:
- ⚡ Performance สูง - Purge unused CSS อัตโนมัติ
- 🎨 Customizable - ปรับแต่งได้ทุกอย่างผ่าน config
- 📱 Responsive - Mobile-first utilities
- 🌓 Dark Mode - Built-in dark mode support
- 🔧 No CSS conflicts - Scoped utilities

**กฎการใช้งาน**:

```blade
{{-- ✅ ถูกต้อง: ใช้ Tailwind utilities --}}
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 hover:shadow-2xl transition-all">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">หัวข้อ</h1>
</div>

{{-- ❌ ผิด: ห้ามใช้ inline styles --}}
<div style="background: white; padding: 24px;">
    <h1 style="font-size: 24px; color: black;">หัวข้อ</h1>
</div>

{{-- ❌ ผิด: ห้ามสร้าง custom CSS class ใหม่ (ยกเว้นกรณีพิเศษ) --}}
<div class="my-custom-card">
    <h1 class="my-custom-title">หัวข้อ</h1>
</div>
```

**Tailwind Configuration** (`tailwind.config.js`):

```javascript
export default {
    darkMode: 'class', // ใช้ class-based dark mode
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],
    theme: {
        extend: {
            colors: {
                // Brand colors
                primary: {
                    50: '#eff6ff',
                    // ... สีทั้งหมด
                    900: '#1e3a8a',
                },
                // Custom colors
            },
            fontFamily: {
                sans: ['Inter', 'Noto Sans Thai', 'sans-serif'],
            },
            animation: {
                'fade-in': 'fadeIn 0.3s ease-in-out',
                'slide-up': 'slideUp 0.4s ease-out',
                'scale-in': 'scaleIn 0.2s ease-out',
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
            },
        },
    },
}
```

### 2. 🏔️ Alpine.js (Lightweight JavaScript Framework)

**ทำไมใช้ Alpine.js**:
- 🪶 เบา - เพียง ~15KB gzipped
- 🚀 เร็ว - No virtual DOM overhead
- 🎯 เรียบง่าย - Syntax คล้าย Vue.js
- 🔧 Declarative - เขียนใน HTML attributes
- 📦 No build step - ใช้ได้ทันที

**กฎการใช้งาน**:

```blade
{{-- ✅ ถูกต้อง: Component pattern --}}
<div x-data="modalComponent()">
    <button @click="open()" class="btn-primary">เปิด Modal</button>

    <div x-show="isOpen"
         x-transition
         x-cloak
         class="modal">
        <div @click.outside="close()" class="modal-content">
            <h2 x-text="title"></h2>
            <button @click="close()">ปิด</button>
        </div>
    </div>
</div>

<script>
/**
 * Modal Component - จัดการ Modal dialog
 *
 * @returns {object} Alpine component object
 *
 * @example
 * <div x-data="modalComponent()">...</div>
 */
function modalComponent() {
    return {
        isOpen: false,
        title: 'Modal Title',

        /**
         * เปิด modal
         */
        open() {
            this.isOpen = true;
        },

        /**
         * ปิด modal
         */
        close() {
            this.isOpen = false;
        }
    };
}
</script>

{{-- ❌ ผิด: ไม่ควรใช้ jQuery สำหรับ DOM manipulation --}}
<button onclick="$('.modal').show()">เปิด Modal</button>

{{-- ❌ ผิด: ไม่ควรใช้ vanilla JS สำหรับ simple interactions --}}
<button onclick="document.getElementById('modal').style.display = 'block'">เปิด</button>
```

### 3. 📋 SortableJS (Drag & Drop Library)

**ทำไมใช้ SortableJS**:
- 🎯 Modern - รองรับ touch devices
- 🪶 เบา - ~20KB minified
- 🔧 Flexible - Customize ได้ทุกอย่าง
- 📱 Mobile-friendly - Touch gestures
- 🎨 Animation - Smooth transitions

**กฎการใช้งาน**:

```blade
{{-- ✅ ถูกต้อง: SortableJS + Alpine.js --}}
<div x-data="sortableList()">
    <ul id="sortable-list" class="space-y-2">
        <template x-for="item in items" :key="item.id">
            <li :data-id="item.id"
                class="bg-white p-4 rounded-lg shadow cursor-move">
                <span x-text="item.name"></span>
            </li>
        </template>
    </ul>
</div>

<script>
import Sortable from 'sortablejs';

/**
 * Sortable List Component - รายการที่ลากเรียงได้
 *
 * @returns {object} Alpine component with sortable functionality
 */
function sortableList() {
    return {
        items: [],
        sortable: null,

        init() {
            // เริ่มต้น SortableJS
            this.sortable = new Sortable(this.$el.querySelector('#sortable-list'), {
                animation: 150,
                ghostClass: 'opacity-50',
                onEnd: (evt) => {
                    this.updateOrder(evt);
                }
            });

            // โหลดข้อมูล
            this.loadItems();
        },

        /**
         * โหลดรายการจาก API
         */
        async loadItems() {
            const response = await fetch('/api/items');
            this.items = await response.json();
        },

        /**
         * อัพเดทลำดับเมื่อลากเสร็จ
         */
        async updateOrder(evt) {
            // อัพเดทลำดับใน array
            const item = this.items.splice(evt.oldIndex, 1)[0];
            this.items.splice(evt.newIndex, 0, item);

            // ส่งไปยัง backend
            await fetch('/api/items/reorder', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    items: this.items.map((item, index) => ({
                        id: item.id,
                        order: index + 1
                    }))
                })
            });
        }
    };
}
</script>

{{-- ❌ ผิด: ไม่ควรใช้ jQuery UI Sortable --}}
<script>
$('#sortable-list').sortable(); // ❌ เก่า ไม่ใช้แล้ว
</script>
```

---

## หลักการออกแบบ V3

### 1. 🎨 Modern UI Aesthetics

**V3 UI Principles**:

#### a) **Glassmorphism** - ความโปร่งใสและ backdrop blur

```blade
{{-- Glassmorphism Card --}}
<div class="bg-white/10 dark:bg-gray-900/10 backdrop-blur-lg border border-white/20 rounded-2xl p-6 shadow-xl">
    <h3 class="text-white font-bold">Glassmorphism Card</h3>
</div>
```

#### b) **Neomorphism** - เงาและความลึก

```blade
{{-- Neomorphic Button --}}
<button class="bg-gray-100 dark:bg-gray-800 shadow-[8px_8px_16px_#d1d1d1,-8px_-8px_16px_#ffffff] dark:shadow-[8px_8px_16px_#1a1a1a,-8px_-8px_16px_#2a2a2a] rounded-xl px-6 py-3 hover:shadow-[inset_8px_8px_16px_#d1d1d1,inset_-8px_-8px_16px_#ffffff] transition-all">
    Click me
</button>
```

#### c) **Gradient Meshes** - พื้นหลังสีไล่ระดับที่ซับซ้อน

```blade
{{-- Gradient Mesh Background --}}
<div class="bg-gradient-to-br from-purple-400 via-pink-500 to-red-500 relative overflow-hidden">
    {{-- Mesh overlay --}}
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_50%_50%,rgba(255,255,255,0.1),transparent_50%)]"></div>

    {{-- Content --}}
    <div class="relative z-10">
        <h1 class="text-4xl font-bold text-white">Beautiful Gradient</h1>
    </div>
</div>
```

#### d) **3D Transform Effects** - การหมุนและเอียงที่สมจริง

```blade
{{-- 3D Card with Hover Effect --}}
<div class="group perspective-1000">
    <div class="relative w-64 h-64 transition-transform duration-500 transform-gpu group-hover:rotate-y-12 group-hover:scale-105">
        <div class="absolute inset-0 bg-gradient-to-br from-blue-500 to-purple-600 rounded-2xl shadow-2xl p-6 text-white">
            <h3 class="text-2xl font-bold">3D Card</h3>
            <p class="mt-4">Hover to see the effect</p>
        </div>
    </div>
</div>

<style>
.perspective-1000 {
    perspective: 1000px;
}
.rotate-y-12 {
    transform: rotateY(12deg);
}
</style>
```

#### e) **Micro-interactions** - Animation เล็กๆ ที่ตอบสนอง

```blade
{{-- Button with Ripple Effect --}}
<button x-data="{ ripple: false }"
        @click="ripple = true; setTimeout(() => ripple = false, 600)"
        class="relative overflow-hidden bg-blue-500 text-white px-6 py-3 rounded-lg">

    {{-- Ripple Animation --}}
    <span x-show="ripple"
          x-transition:enter="transition-transform duration-600"
          x-transition:enter-start="scale-0"
          x-transition:enter-end="scale-150"
          class="absolute inset-0 bg-white/30 rounded-full"></span>

    <span class="relative z-10">Click me!</span>
</button>
```

### 2. ⚡ Performance-First

**กฎสำคัญ**:

```javascript
// ✅ ถูกต้อง: Lazy loading images
<img src="placeholder.jpg"
     data-src="large-image.jpg"
     loading="lazy"
     class="w-full h-auto">

// ✅ ถูกต้อง: Debounce search input
<input type="text"
       x-data="{ search: '' }"
       x-model="search"
       @input.debounce.500ms="performSearch()">

// ✅ ถูกต้อง: Virtual scrolling สำหรับรายการยาว
// ใช้ library เช่น vue-virtual-scroller หรือ Alpine Virtual Scroll

// ❌ ผิด: Load all 1000 items at once
<div x-for="item in allItems">...</div>

// ✅ ถูกต้อง: Pagination or infinite scroll
<div x-for="item in visibleItems">...</div>
```

### 3. 📱 Mobile-First Responsive

**Breakpoint Strategy**:

```blade
{{-- Mobile-first approach --}}
<div class="
    w-full          <!-- Mobile: full width -->
    p-4             <!-- Mobile: padding 1rem -->
    md:w-1/2        <!-- Tablet: half width -->
    md:p-6          <!-- Tablet: padding 1.5rem -->
    lg:w-1/3        <!-- Desktop: third width -->
    lg:p-8          <!-- Desktop: padding 2rem -->
    xl:w-1/4        <!-- Large: quarter width -->
">
    {{-- Content --}}
</div>

{{-- Touch-friendly buttons (≥44px) --}}
<button class="min-h-[44px] min-w-[44px] px-6 py-3 text-lg">
    Click me
</button>
```

### 4. 🌓 Dark Mode by Default

**ทุก Component ต้องรองรับ Dark Mode**:

```blade
{{-- ✅ ถูกต้อง: Dark mode support --}}
<div class="bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">
    <h1 class="text-gray-900 dark:text-white">Title</h1>
    <p class="text-gray-600 dark:text-gray-400">Description</p>

    <button class="bg-blue-500 hover:bg-blue-600 dark:bg-blue-600 dark:hover:bg-blue-700">
        Click
    </button>
</div>

{{-- ❌ ผิด: Hard-coded colors --}}
<div style="background: white; color: black;">
    <h1>Title</h1>
</div>
```

**Dark Mode Toggle Component**:

```blade
<div x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' || false }" x-init="$watch('darkMode', val => { localStorage.setItem('darkMode', val); document.documentElement.classList.toggle('dark', val); })">

    <button @click="darkMode = !darkMode"
            class="p-2 rounded-lg bg-gray-200 dark:bg-gray-700">
        <span x-show="!darkMode">🌙</span>
        <span x-show="darkMode">☀️</span>
    </button>
</div>
```

---

## โครงสร้างโค้ด

### 1. 📁 ไฟล์และโฟลเดอร์

```
resources/
├── css/
│   └── app.css                 # Tailwind imports + custom CSS (minimal)
├── js/
│   ├── app.js                  # Main entry point
│   ├── alpine/
│   │   ├── components/         # Alpine components
│   │   │   ├── modal.js
│   │   │   ├── dropdown.js
│   │   │   ├── sortable.js
│   │   │   └── ...
│   │   └── stores/             # Alpine stores (global state)
│   │       ├── auth.js
│   │       ├── cart.js
│   │       └── ...
│   └── utils/                  # Utility functions
│       ├── api.js              # API helpers
│       ├── formatters.js       # Format helpers
│       └── validators.js       # Validation helpers
└── views/
    ├── components/             # Blade components
    │   ├── ui/
    │   │   ├── button.blade.php
    │   │   ├── card.blade.php
    │   │   ├── modal.blade.php
    │   │   └── ...
    │   └── forms/
    │       ├── input.blade.php
    │       ├── select.blade.php
    │       └── ...
    └── [other views]
```

### 2. 🧩 Component Architecture

**Blade Component Pattern**:

```blade
{{-- resources/views/components/ui/card.blade.php --}}

@props([
    'title' => '',
    'gradient' => 'from-blue-500 to-purple-600',
    'icon' => '',
    'transparent' => false,
])

<div {{ $attributes->merge(['class' => 'rounded-2xl shadow-lg overflow-hidden']) }}>
    {{-- Header --}}
    @if($title || $icon)
    <div class="bg-gradient-to-r {{ $gradient }} px-6 py-4">
        <div class="flex items-center gap-3">
            @if($icon)
            <i class="{{ $icon }} text-2xl text-white"></i>
            @endif
            @if($title)
            <h2 class="text-xl font-bold text-white">{{ $title }}</h2>
            @endif
        </div>
    </div>
    @endif

    {{-- Body --}}
    <div class="{{ $transparent ? 'bg-transparent' : 'bg-white dark:bg-gray-800' }} p-6">
        {{ $slot }}
    </div>
</div>
```

**การใช้งาน**:

```blade
{{-- Simple usage --}}
<x-ui.card title="ข้อมูลผู้ใช้" icon="fas fa-user" gradient="from-green-500 to-emerald-600">
    <p>เนื้อหาข้างใน card</p>
</x-ui.card>

{{-- With Alpine.js --}}
<x-ui.card title="รายการสินค้า" x-data="productList()">
    <template x-for="product in products" :key="product.id">
        <div x-text="product.name"></div>
    </template>
</x-ui.card>
```

### 3. 🎯 Alpine Component Pattern

**Component File Structure**:

```javascript
// resources/js/alpine/components/modal.js

/**
 * Modal Component - จัดการ modal dialog
 *
 * @param {object} options - ตัวเลือกสำหรับ modal
 * @param {string} options.title - หัวข้อ modal
 * @param {boolean} options.closeOnOutside - ปิดเมื่อคลิกภายนอกหรือไม่
 *
 * @returns {object} Alpine component object
 *
 * @example
 * <div x-data="modalComponent({ title: 'ยืนยันการลบ' })">
 *   <button @click="open()">เปิด Modal</button>
 * </div>
 *
 * @tip ใช้ x-teleport เพื่อย้าย modal ไปที่ body เพื่อหลีกเลี่ยง z-index issues
 */
export function modalComponent(options = {}) {
    return {
        // State
        isOpen: false,
        title: options.title || '',
        closeOnOutside: options.closeOnOutside !== false,

        // Lifecycle
        init() {
            console.log('Modal initialized:', this.title);

            // Listen for Escape key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.isOpen) {
                    this.close();
                }
            });
        },

        // Methods

        /**
         * เปิด modal
         */
        open() {
            this.isOpen = true;
            document.body.style.overflow = 'hidden'; // ป้องกันการ scroll
        },

        /**
         * ปิด modal
         */
        close() {
            this.isOpen = false;
            document.body.style.overflow = ''; // คืนค่า scroll
        },

        /**
         * Toggle modal
         */
        toggle() {
            this.isOpen ? this.close() : this.open();
        },

        /**
         * Handle click outside
         */
        handleOutsideClick() {
            if (this.closeOnOutside) {
                this.close();
            }
        }
    };
}
```

**Registration** (`resources/js/app.js`):

```javascript
import Alpine from 'alpinejs';
import { modalComponent } from './alpine/components/modal';

// Register global components
window.modalComponent = modalComponent;

// Start Alpine
Alpine.start();
```

---

## UI Components V3

### 1. 💳 Modern Card

```blade
{{-- 3D Hoverable Card --}}
<div class="group perspective-1000">
    <div class="relative transform-gpu transition-all duration-500 group-hover:scale-105 group-hover:rotate-y-3">
        <div class="absolute inset-0 bg-gradient-to-br from-blue-500 to-purple-600 rounded-2xl blur-xl opacity-50 group-hover:opacity-75 transition-opacity"></div>

        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center">
                    <i class="fas fa-rocket text-white text-xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">Card Title</h3>
            </div>

            <p class="text-gray-600 dark:text-gray-400">Card content goes here...</p>

            <button class="mt-4 px-4 py-2 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-lg hover:shadow-lg transition-all">
                Action
            </button>
        </div>
    </div>
</div>

<style>
.perspective-1000 { perspective: 1000px; }
.rotate-y-3 { transform: rotateY(3deg); }
</style>
```

### 2. 🔘 Premium Buttons

```blade
{{-- Gradient Button with Ripple --}}
<button x-data="{ ripple: false }"
        @click="ripple = true; setTimeout(() => ripple = false, 600)"
        class="group relative overflow-hidden px-8 py-4 bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500 text-white font-bold rounded-xl shadow-lg hover:shadow-2xl hover:scale-105 transition-all duration-300">

    {{-- Shine Effect --}}
    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>

    {{-- Ripple --}}
    <span x-show="ripple"
          x-transition:enter="transition-transform duration-600"
          x-transition:enter-start="scale-0"
          x-transition:enter-end="scale-150"
          class="absolute inset-0 bg-white/30 rounded-full"></span>

    {{-- Text --}}
    <span class="relative z-10 flex items-center gap-2">
        <i class="fas fa-rocket"></i>
        <span>เริ่มต้นใช้งาน</span>
    </span>
</button>

{{-- Glassmorphism Button --}}
<button class="px-6 py-3 bg-white/10 backdrop-blur-lg border border-white/20 text-white rounded-xl hover:bg-white/20 transition-all">
    <i class="fas fa-sparkles mr-2"></i>
    Glassmorphic
</button>

{{-- Neomorphic Button --}}
<button class="px-6 py-3 bg-gray-100 dark:bg-gray-800 shadow-[8px_8px_16px_#d1d1d1,-8px_-8px_16px_#ffffff] dark:shadow-[8px_8px_16px_#1a1a1a,-8px_-8px_16px_#2a2a2a] rounded-xl hover:shadow-[inset_8px_8px_16px_#d1d1d1,inset_-8px_-8px_16px_#ffffff] transition-all">
    Neomorphic
</button>
```

### 3. 🎭 Modal (Modern)

```blade
{{-- Modal with Backdrop Blur --}}
<div x-data="modalComponent({ title: 'ยืนยันการดำเนินการ' })">

    {{-- Trigger Button --}}
    <button @click="open()"
            class="px-6 py-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600">
        เปิด Modal
    </button>

    {{-- Modal --}}
    <div x-show="isOpen"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="display: none;">

        {{-- Backdrop --}}
        <div @click="handleOutsideClick()"
             class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>

        {{-- Modal Content --}}
        <div x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="scale-95 opacity-0"
             x-transition:enter-end="scale-100 opacity-100"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="scale-100 opacity-100"
             x-transition:leave-end="scale-95 opacity-0"
             class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden">

            {{-- Header --}}
            <div class="bg-gradient-to-r from-blue-500 to-purple-600 px-6 py-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold text-white" x-text="title"></h3>
                    <button @click="close()"
                            class="text-white hover:text-gray-200 transition-colors">
                        <i class="fas fa-times text-2xl"></i>
                    </button>
                </div>
            </div>

            {{-- Body --}}
            <div class="p-6">
                <p class="text-gray-700 dark:text-gray-300">
                    คุณแน่ใจหรือไม่ว่าต้องการดำเนินการต่อ?
                </p>
            </div>

            {{-- Footer --}}
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
                <button @click="close()"
                        class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                    ยกเลิก
                </button>
                <button @click="close()"
                        class="px-4 py-2 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-lg hover:shadow-lg transition-all">
                    ยืนยัน
                </button>
            </div>
        </div>
    </div>
</div>
```

### 4. 📋 Sortable List

```blade
{{-- Drag & Drop Sortable List --}}
<div x-data="sortableListComponent()" x-init="init()">

    <ul id="sortable-list" class="space-y-3">
        <template x-for="(item, index) in items" :key="item.id">
            <li :data-id="item.id"
                class="group flex items-center gap-4 bg-white dark:bg-gray-800 rounded-xl shadow-md hover:shadow-xl p-4 cursor-move transition-all border-2 border-transparent hover:border-blue-300">

                {{-- Drag Handle --}}
                <div class="flex-shrink-0 text-gray-400 group-hover:text-blue-500">
                    <i class="fas fa-grip-vertical text-xl"></i>
                </div>

                {{-- Order Number --}}
                <div class="flex-shrink-0 w-8 h-8 bg-gradient-to-br from-blue-500 to-purple-600 text-white rounded-lg flex items-center justify-center font-bold text-sm">
                    <span x-text="index + 1"></span>
                </div>

                {{-- Content --}}
                <div class="flex-1">
                    <h4 class="font-bold text-gray-900 dark:text-white" x-text="item.name"></h4>
                    <p class="text-sm text-gray-500 dark:text-gray-400" x-text="item.description"></p>
                </div>

                {{-- Actions --}}
                <div class="flex-shrink-0">
                    <button @click="deleteItem(item.id)"
                            class="px-3 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </li>
        </template>
    </ul>

    {{-- Empty State --}}
    <div x-show="items.length === 0"
         class="text-center py-12 text-gray-500 dark:text-gray-400">
        <i class="fas fa-inbox text-6xl mb-4"></i>
        <p class="text-lg">ไม่มีรายการ</p>
    </div>
</div>

<script>
import Sortable from 'sortablejs';

/**
 * Sortable List Component - รายการที่ลากเรียงได้
 */
function sortableListComponent() {
    return {
        items: [],
        sortable: null,

        /**
         * เริ่มต้น component
         */
        init() {
            // โหลดข้อมูล
            this.loadItems();

            // รอให้ DOM render เสร็จก่อน
            this.$nextTick(() => {
                this.initSortable();
            });
        },

        /**
         * เริ่มต้น SortableJS
         */
        initSortable() {
            const listEl = document.getElementById('sortable-list');

            this.sortable = new Sortable(listEl, {
                animation: 200,
                easing: 'cubic-bezier(0.4, 0, 0.2, 1)',
                ghostClass: 'opacity-30',
                chosenClass: 'ring-2 ring-blue-500',
                dragClass: 'shadow-2xl scale-105',

                onEnd: (evt) => {
                    this.updateOrder(evt);
                }
            });
        },

        /**
         * โหลดรายการ
         */
        async loadItems() {
            try {
                const response = await fetch('/api/items');
                this.items = await response.json();
            } catch (error) {
                console.error('Error loading items:', error);
            }
        },

        /**
         * อัพเดทลำดับ
         */
        async updateOrder(evt) {
            // อัพเดทลำดับใน items array
            const item = this.items.splice(evt.oldIndex, 1)[0];
            this.items.splice(evt.newIndex, 0, item);

            // ส่งไปยัง backend
            try {
                await fetch('/api/items/reorder', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        items: this.items.map((item, index) => ({
                            id: item.id,
                            order: index + 1
                        }))
                    })
                });

                // แสดงข้อความสำเร็จ
                this.$dispatch('notify', {
                    message: 'อัพเดทลำดับสำเร็จ',
                    type: 'success'
                });
            } catch (error) {
                console.error('Error updating order:', error);

                // แสดงข้อความผิดพลาด
                this.$dispatch('notify', {
                    message: 'เกิดข้อผิดพลาดในการอัพเดทลำดับ',
                    type: 'error'
                });
            }
        },

        /**
         * ลบรายการ
         */
        async deleteItem(id) {
            if (!confirm('คุณแน่ใจหรือไม่ว่าต้องการลบรายการนี้?')) {
                return;
            }

            try {
                await fetch(`/api/items/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                // ลบออกจาก array
                this.items = this.items.filter(item => item.id !== id);

                this.$dispatch('notify', {
                    message: 'ลบรายการสำเร็จ',
                    type: 'success'
                });
            } catch (error) {
                console.error('Error deleting item:', error);

                this.$dispatch('notify', {
                    message: 'เกิดข้อผิดพลาดในการลบรายการ',
                    type: 'error'
                });
            }
        }
    };
}

// Export for global use
window.sortableListComponent = sortableListComponent;
</script>
```

---

## ตัวอย่างโค้ดสมบูรณ์

### 📄 หน้า Dashboard ตัวอย่าง

```blade
{{-- resources/views/admin/dashboard.blade.php --}}

@extends('admin.layouts.app')

@section('content')
<div class="p-6 space-y-6" x-data="dashboardComponent()" x-init="init()">

    {{-- Page Header --}}
    <div class="relative overflow-hidden bg-gradient-to-br from-blue-500 via-purple-600 to-pink-500 rounded-2xl p-8 shadow-2xl">
        {{-- Pattern Background --}}
        <div class="absolute inset-0 opacity-20">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_50%,rgba(255,255,255,0.2),transparent_50%)]"></div>
        </div>

        <div class="relative z-10">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center">
                    <i class="fas fa-chart-line text-3xl text-white"></i>
                </div>
                <div>
                    <h1 class="text-4xl font-bold text-white mb-2">Dashboard</h1>
                    <p class="text-white/90 text-lg">ภาพรวมและสถิติของระบบ</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Stats Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <template x-for="stat in stats" :key="stat.id">
            <div class="group perspective-1000">
                <div class="relative transform-gpu transition-all duration-500 group-hover:scale-105 group-hover:rotate-y-3">
                    {{-- Glow Effect --}}
                    <div class="absolute inset-0 bg-gradient-to-br opacity-50 group-hover:opacity-75 transition-opacity rounded-2xl blur-xl"
                         :class="stat.gradient"></div>

                    {{-- Card --}}
                    <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between mb-4">
                            <div :class="stat.iconBg"
                                 class="w-12 h-12 rounded-xl flex items-center justify-center">
                                <i :class="stat.icon" class="text-white text-xl"></i>
                            </div>
                            <span :class="stat.badgeColor"
                                  class="px-2 py-1 rounded-lg text-xs font-bold"
                                  x-text="stat.change"></span>
                        </div>

                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-1"
                            x-text="stat.value"></h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400"
                           x-text="stat.label"></p>
                    </div>
                </div>
            </div>
        </template>
    </div>

    {{-- Main Content Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Left Column - Chart --}}
        <div class="lg:col-span-2">
            <x-ui.card title="สถิติการใช้งาน" icon="fas fa-chart-bar" gradient="from-blue-500 to-cyan-600">
                <canvas id="usage-chart" class="w-full h-64"></canvas>
            </x-ui.card>
        </div>

        {{-- Right Column - Recent Activity --}}
        <div class="space-y-6">
            <x-ui.card title="กิจกรรมล่าสุด" icon="fas fa-history" gradient="from-purple-500 to-pink-600">
                <div class="space-y-3">
                    <template x-for="activity in recentActivities" :key="activity.id">
                        <div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                            <div :class="activity.iconBg"
                                 class="flex-shrink-0 w-10 h-10 rounded-lg flex items-center justify-center">
                                <i :class="activity.icon" class="text-white"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate"
                                   x-text="activity.title"></p>
                                <p class="text-xs text-gray-500 dark:text-gray-400"
                                   x-text="activity.time"></p>
                            </div>
                        </div>
                    </template>
                </div>
            </x-ui.card>
        </div>
    </div>
</div>

<script>
import Chart from 'chart.js/auto';

/**
 * Dashboard Component - หน้า dashboard หลัก
 */
function dashboardComponent() {
    return {
        // State
        stats: [
            {
                id: 1,
                label: 'ผู้ใช้งานทั้งหมด',
                value: '1,234',
                change: '+12%',
                icon: 'fas fa-users',
                iconBg: 'bg-gradient-to-br from-blue-500 to-cyan-600',
                gradient: 'from-blue-500 to-cyan-600',
                badgeColor: 'bg-green-100 text-green-600'
            },
            {
                id: 2,
                label: 'ยอดขายวันนี้',
                value: '฿45,678',
                change: '+8%',
                icon: 'fas fa-shopping-cart',
                iconBg: 'bg-gradient-to-br from-green-500 to-emerald-600',
                gradient: 'from-green-500 to-emerald-600',
                badgeColor: 'bg-green-100 text-green-600'
            },
            {
                id: 3,
                label: 'คำสั่งซื้อใหม่',
                value: '89',
                change: '-3%',
                icon: 'fas fa-file-invoice',
                iconBg: 'bg-gradient-to-br from-purple-500 to-pink-600',
                gradient: 'from-purple-500 to-pink-600',
                badgeColor: 'bg-red-100 text-red-600'
            },
            {
                id: 4,
                label: 'รายได้รวม',
                value: '฿123,456',
                change: '+15%',
                icon: 'fas fa-dollar-sign',
                iconBg: 'bg-gradient-to-br from-orange-500 to-red-600',
                gradient: 'from-orange-500 to-red-600',
                badgeColor: 'bg-green-100 text-green-600'
            }
        ],

        recentActivities: [
            {
                id: 1,
                title: 'มีคำสั่งซื้อใหม่ #1234',
                time: '5 นาทีที่แล้ว',
                icon: 'fas fa-shopping-cart',
                iconBg: 'bg-green-500'
            },
            {
                id: 2,
                title: 'ผู้ใช้ใหม่ลงทะเบียน',
                time: '15 นาทีที่แล้ว',
                icon: 'fas fa-user-plus',
                iconBg: 'bg-blue-500'
            },
            {
                id: 3,
                title: 'สินค้าใกล้หมด: Product X',
                time: '1 ชั่วโมงที่แล้ว',
                icon: 'fas fa-exclamation-triangle',
                iconBg: 'bg-yellow-500'
            }
        ],

        chart: null,

        /**
         * เริ่มต้น component
         */
        init() {
            this.$nextTick(() => {
                this.initChart();
            });
        },

        /**
         * เริ่มต้น chart
         */
        initChart() {
            const ctx = document.getElementById('usage-chart');

            this.chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.'],
                    datasets: [{
                        label: 'ยอดขาย',
                        data: [12, 19, 3, 5, 2, 3],
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
    };
}

// Export for global use
window.dashboardComponent = dashboardComponent;
</script>
@endsection
```

---

## 📚 สรุป

### ✅ สิ่งที่ต้องทำ (DO)

- ✅ ใช้ Tailwind CSS เป็นหลัก (utility-first)
- ✅ ใช้ Alpine.js สำหรับ interactivity
- ✅ ใช้ SortableJS สำหรับ drag & drop
- ✅ รองรับ dark mode ทุก component
- ✅ Mobile-first responsive design
- ✅ Modern UI (glassmorphism, 3D effects, gradients)
- ✅ Performance optimization (lazy loading, debounce)
- ✅ คอมเม้นต์ภาษาไทย 100% พร้อม @example และ @tip
- ✅ Component-based architecture
- ✅ Reusable Blade components

### ❌ สิ่งที่ห้ามทำ (DON'T)

- ❌ ใช้ jQuery สำหรับ DOM manipulation
- ❌ ใช้ jQuery UI Sortable
- ❌ สร้าง custom CSS classes (ยกเว้นจำเป็น)
- ❌ Inline styles
- ❌ Hard-coded colors (ต้องรองรับ dark mode)
- ❌ ไม่ responsive
- ❌ Performance bottlenecks (load all data at once)
- ❌ คอมเม้นต์ภาษาอังกฤษ

### 🎯 เป้าหมาย Performance

- Bundle size: ≤ 150KB (gzipped)
- First Contentful Paint: ≤ 1.5s
- Time to Interactive: ≤ 3s
- Lighthouse Score: ≥ 90

---

**สร้างโดย**: Development Team
**สำหรับ**: Thaiprompt-Affiliate V3
**ลิขสิทธิ์**: © 2025 Thaiprompt

*"Code for tomorrow, not just for today" - เขียนโค้ดที่ยั่งยืน*
