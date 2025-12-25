# 🎨 Admin Design Guidelines - V3 Edition

> **แนวทางการออกแบบหน้า Admin สำหรับ Thaiprompt-Affiliate V3**
>
> **Version**: 3.0.0 | Last Updated: 2025-12-25
>
> 📌 **Reference**: ใช้ `admin/marketplace` เป็น Design Reference หลัก

---

## 📋 สารบัญ

1. [ปรัชญาการออกแบบ](#ปรัชญาการออกแบบ)
2. [โครงสร้างหน้ามาตรฐาน](#โครงสร้างหน้ามาตรฐาน)
3. [Components Library](#components-library)
4. [Color & Gradient System](#color--gradient-system)
5. [Typography & Icons](#typography--icons)
6. [Spacing & Layout](#spacing--layout)
7. [Animations & Effects](#animations--effects)
8. [Dark Mode](#dark-mode)
9. [Responsive Design](#responsive-design)
10. [เทมเพลตพร้อมใช้](#เทมเพลตพร้อมใช้)

---

## ปรัชญาการออกแบบ

### 🎯 หลักการหลัก (Admin V3 Design Philosophy)

**ทุกหน้าแอดมินต้องมีลักษณะเหล่านี้:**

1. ✨ **Premium Look** - ดูมีค่า มืออาชีพ น่าเชื่อถือ
2. 🎨 **Colorful & Vibrant** - ใช้ gradient สีสดใส มีชีวิตชีวา
3. 🌟 **Glassmorphism** - backdrop-blur, ความโปร่งใส
4. 🎭 **3D Effects** - shadows, depth, hover animations
5. 💫 **Micro-interactions** - hover, focus, click feedback ทันที
6. 🌓 **Dark Mode First** - รองรับ dark mode อย่างสมบูรณ์
7. 📱 **Mobile Responsive** - ใช้งานได้ดีทุกหน้าจอ
8. ⚡ **Performance** - animation smooth, ไม่กระตุก

### 🎨 Design DNA (ลักษณะเฉพาะ)

**สิ่งที่ทำให้หน้าแอดมิน V3 แตกต่าง:**

```
┌─────────────────────────────────────────┐
│  🎨 V3 Admin Design DNA                 │
├─────────────────────────────────────────┤
│  ✅ Premium Hero Header                 │
│     (Gradient + Animated Orbs)          │
│                                         │
│  ✅ Stats Cards                         │
│     (Gradient + Glass + Hover Scale)    │
│                                         │
│  ✅ Glass Filter Cards                  │
│     (backdrop-blur + border/shadow)     │
│                                         │
│  ✅ Modern Form Inputs                  │
│     (Rounded-xl + Focus Ring)           │
│                                         │
│  ✅ Gradient Buttons                    │
│     (CTA + Hover Effects)               │
│                                         │
│  ✅ Animated Icons                      │
│     (Floating + Pulse)                  │
│                                         │
│  ✅ Empty States                        │
│     (Gradient bg + Call-to-action)      │
└─────────────────────────────────────────┘
```

---

## โครงสร้างหน้ามาตรฐาน

### 📐 Layout Structure (โครงสร้างมาตรฐานทุกหน้า)

**ทุกหน้าแอดมินต้องมีโครงสร้างนี้:**

```blade
@extends('layouts.admin-v3')

@section('title', 'ชื่อหน้า')

@section('content')
<div class="space-y-6">
    {{-- 1️⃣ PREMIUM HERO HEADER (บังคับมี!) --}}
    <div class="relative overflow-hidden bg-gradient-to-r from-[color1] via-[color2] to-[color3] dark:from-[dark1] dark:via-[dark2] dark:to-[dark3] rounded-2xl shadow-2xl p-8">
        {{-- Animated Background Orbs --}}
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 right-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse" style="animation-delay: 0.5s"></div>
        </div>

        {{-- Floating Icons --}}
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute text-white/10 text-8xl top-10 right-20" style="animation: float 6s ease-in-out infinite">
                <i class="fas fa-[icon]"></i>
            </div>
        </div>

        {{-- Header Content --}}
        <div class="relative z-10">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                <div class="flex-1">
                    <div class="flex items-center gap-4 mb-3">
                        <div class="glass-fusion p-4 rounded-2xl">
                            <i class="fas fa-[icon] text-4xl text-white drop-shadow-lg"></i>
                        </div>
                        <div>
                            <h1 class="text-4xl font-bold text-white drop-shadow-lg">ชื่อหน้า</h1>
                            <p class="text-[color]-100 text-lg mt-1">คำอธิบายหน้า</p>
                        </div>
                    </div>
                </div>

                {{-- Action Button (ถ้ามี) --}}
                <div>
                    <a href="#" class="bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white px-6 py-3 rounded-xl text-sm font-semibold flex items-center gap-2 transition-all duration-300 shadow-lg border border-white/30 group">
                        <i class="fas fa-plus group-hover:rotate-90 transition-transform duration-300"></i>
                        เพิ่มใหม่
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- 2️⃣ STATS CARDS (ถ้ามีข้อมูลสถิติ) --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5 gap-6">
        {{-- Stats Cards ตาม pattern ด้านล่าง --}}
    </div>

    {{-- 3️⃣ FILTERS SECTION (ถ้ามีตัวกรอง) --}}
    <div class="glass-card dark:bg-gray-800/50 backdrop-blur-xl rounded-2xl shadow-xl p-6 border border-white/20 dark:border-gray-700/50">
        {{-- Filter form --}}
    </div>

    {{-- 4️⃣ MAIN CONTENT (Table / Grid / List) --}}
    <div class="glass-card dark:bg-gray-800/50 backdrop-blur-xl rounded-2xl shadow-xl overflow-hidden border border-white/20 dark:border-gray-700/50">
        {{-- Main content --}}
    </div>
</div>
@endsection

@push('styles')
<style>
    @keyframes float {
        0%, 100% { transform: translateY(0px) rotate(0deg); }
        50% { transform: translateY(-20px) rotate(5deg); }
    }
    .glass-card {
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(20px);
    }
    .dark .glass-card {
        background: rgba(31, 41, 55, 0.8);
    }
    .glass-fusion {
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
    }
</style>
@endpush
```

---

## Components Library

### 1️⃣ Premium Hero Header

**🎯 ใช้เมื่อ:** ทุกหน้าต้องมี Hero Header

**🎨 Gradient Colors สำหรับหน้าต่างๆ:**

| หน้า/Module | Light Gradient | Dark Gradient |
|-------------|----------------|---------------|
| **Dashboard** | `from-blue-500 via-purple-500 to-pink-600` | `from-blue-700 via-purple-700 to-pink-800` |
| **Users/Members** | `from-green-500 via-emerald-500 to-teal-600` | `from-green-700 via-emerald-700 to-teal-800` |
| **Products/E-commerce** | `from-purple-500 via-pink-500 to-rose-600` | `from-purple-700 via-pink-700 to-rose-800` |
| **Marketplace** | `from-orange-500 via-red-500 to-pink-600` | `from-orange-700 via-red-700 to-pink-800` |
| **Finance/Wallet** | `from-yellow-500 via-orange-500 to-red-600` | `from-yellow-700 via-orange-700 to-red-800` |
| **Analytics** | `from-cyan-500 via-blue-500 to-indigo-600` | `from-cyan-700 via-blue-700 to-indigo-800` |
| **Settings** | `from-gray-500 via-slate-500 to-zinc-600` | `from-gray-700 via-slate-700 to-zinc-800` |
| **AI/Bots** | `from-violet-500 via-purple-500 to-fuchsia-600` | `from-violet-700 via-purple-700 to-fuchsia-800` |

**Template:**

```blade
{{-- Premium Hero Header --}}
<div class="relative overflow-hidden bg-gradient-to-r from-[color1] via-[color2] to-[color3] dark:from-[dark1] dark:via-[dark2] dark:to-[dark3] rounded-2xl shadow-2xl p-8">
    {{-- Animated Background Orbs --}}
    <div class="absolute inset-0 opacity-10">
        <div class="absolute top-0 right-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute bottom-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse" style="animation-delay: 0.5s"></div>
        <div class="absolute top-1/2 left-1/3 w-64 h-64 bg-white rounded-full blur-3xl animate-pulse" style="animation-delay: 1s"></div>
    </div>

    {{-- Floating Icons (เลือก icon ที่เกี่ยวข้องกับหน้า) --}}
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute text-white/10 text-8xl top-10 right-20" style="animation: float 6s ease-in-out infinite">
            <i class="fas fa-[main-icon]"></i>
        </div>
        <div class="absolute text-white/10 text-6xl bottom-10 right-40" style="animation: float 6s ease-in-out infinite; animation-delay: 0.3s">
            <i class="fas fa-[secondary-icon]"></i>
        </div>
        <div class="absolute text-white/10 text-7xl top-20 left-1/4" style="animation: float 6s ease-in-out infinite; animation-delay: 0.5s">
            <i class="fas fa-[tertiary-icon]"></i>
        </div>
    </div>

    {{-- Header Content --}}
    <div class="relative z-10">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
            {{-- Title Section --}}
            <div class="flex-1">
                <div class="flex items-center gap-4 mb-3">
                    <div class="glass-fusion p-4 rounded-2xl">
                        <i class="fas fa-[icon] text-4xl text-white drop-shadow-lg"></i>
                    </div>
                    <div>
                        <h1 class="text-4xl font-bold text-white drop-shadow-lg">ชื่อหน้า</h1>
                        <p class="text-[gradient-color]-100 text-lg mt-1">คำอธิบายหน้า</p>
                    </div>
                </div>
            </div>

            {{-- Action Button (ถ้ามี) --}}
            <div>
                <a href="{{ route('admin.xxx.create') }}"
                   class="bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white px-6 py-3 rounded-xl text-sm font-semibold flex items-center gap-2 transition-all duration-300 shadow-lg border border-white/30 group">
                    <i class="fas fa-plus group-hover:rotate-90 transition-transform duration-300"></i>
                    เพิ่มใหม่
                </a>
            </div>
        </div>
    </div>
</div>
```

### 2️⃣ Stats Cards (การ์ดสถิติ)

**🎯 ใช้เมื่อ:** มีข้อมูลสถิติที่ต้องแสดง (ผลรวม, จำนวน, เปอร์เซ็นต์)

**Template:**

```blade
{{-- Stats Cards Grid --}}
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5 gap-6">
    {{-- Stat Card 1 --}}
    <div class="group relative overflow-hidden bg-gradient-to-br from-blue-500 to-blue-700 dark:from-blue-600 dark:to-blue-900 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition-all duration-300 cursor-pointer">
        {{-- Background Icon --}}
        <div class="absolute -right-8 -top-8 opacity-10">
            <i class="fas fa-[icon] text-9xl"></i>
        </div>

        {{-- Content --}}
        <div class="relative z-10">
            <div class="flex items-center justify-between mb-3">
                <p class="text-sm font-medium opacity-90">ชื่อสถิติ</p>
                <div class="glass-fusion p-3 rounded-xl">
                    <i class="fas fa-[icon] text-2xl"></i>
                </div>
            </div>
            <h3 class="text-4xl font-bold mb-2">{{ number_format($stat) }}</h3>
            <div class="flex items-center text-sm gap-1">
                <i class="fas fa-info-circle text-blue-200"></i>
                <span class="opacity-90">รายละเอียดเพิ่มเติม</span>
            </div>
        </div>
    </div>

    {{-- เพิ่ม cards อื่นๆ ตาม pattern เดียวกัน --}}
</div>
```

**🎨 Gradient Colors สำหรับ Stats Cards:**

| ประเภทสถิติ | Gradient Class |
|-------------|----------------|
| **จำนวนทั้งหมด** | `from-blue-500 to-blue-700 dark:from-blue-600 dark:to-blue-900` |
| **Active/เปิดใช้งาน** | `from-green-500 to-emerald-700 dark:from-green-600 dark:to-emerald-900` |
| **รออนุมัติ/Pending** | `from-yellow-500 to-orange-600 dark:from-yellow-600 dark:to-orange-800` |
| **ปิดใช้งาน/Inactive** | `from-gray-500 to-gray-700 dark:from-gray-600 dark:to-gray-900` |
| **ลบ/Deleted** | `from-red-500 to-rose-700 dark:from-red-600 dark:to-rose-900` |
| **รายได้/Revenue** | `from-emerald-500 to-teal-700 dark:from-emerald-600 dark:to-teal-900` |
| **สินค้า/Products** | `from-purple-500 to-pink-600 dark:from-purple-600 dark:to-pink-800` |
| **ผู้ใช้/Users** | `from-cyan-500 to-blue-600 dark:from-cyan-600 dark:to-blue-800` |
| **คลิก/Views** | `from-indigo-500 to-purple-600 dark:from-indigo-600 dark:to-purple-800` |

### 3️⃣ Glass Card (Container หลัก)

**🎯 ใช้เมื่อ:** ต้องการ container สำหรับเนื้อหาหลัก (table, form, content)

**Template:**

```blade
{{-- Glass Card Container --}}
<div class="glass-card dark:bg-gray-800/50 backdrop-blur-xl rounded-2xl shadow-xl overflow-hidden border border-white/20 dark:border-gray-700/50">
    {{-- Header (ถ้ามี) --}}
    <div class="p-6 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-gray-50 to-white dark:from-gray-800 dark:to-gray-750">
        <div class="flex items-center justify-between">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <i class="fas fa-[icon] text-[color]-600 dark:text-[color]-400"></i>
                ชื่อส่วน
            </h3>
            <span class="px-4 py-2 bg-[color]-100 dark:bg-[color]-900/30 text-[color]-700 dark:text-[color]-300 rounded-xl text-sm font-semibold">
                {{ number_format($count) }} รายการ
            </span>
        </div>
    </div>

    {{-- Content --}}
    <div class="p-6">
        {{-- เนื้อหา --}}
    </div>
</div>
```

### 4️⃣ Filter Section (ตัวกรองข้อมูล)

**🎯 ใช้เมื่อ:** หน้ามีข้อมูลจำนวนมากต้องกรอง (index pages)

**Template:**

```blade
{{-- Filters Section --}}
<div class="glass-card dark:bg-gray-800/50 backdrop-blur-xl rounded-2xl shadow-xl p-6 border border-white/20 dark:border-gray-700/50">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
        <i class="fas fa-filter text-[color]-600 dark:text-[color]-400"></i>
        ตัวกรองข้อมูล
    </h3>

    <form method="GET" action="{{ route('admin.xxx.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        {{-- Search Input --}}
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                <i class="fas fa-search mr-1 text-blue-600 dark:text-blue-400"></i>
                ค้นหา
            </label>
            <div class="relative">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="ค้นหา..."
                       class="w-full pl-12 pr-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:border-[color]-500 focus:ring-4 focus:ring-[color]-500/20 transition-all placeholder:text-gray-400">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <i class="fas fa-search text-gray-400"></i>
                </div>
            </div>
        </div>

        {{-- Select Filters --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                <i class="fas fa-[icon] mr-1 text-[color]-600 dark:text-[color]-400"></i>
                ตัวกรอง
            </label>
            <select name="filter" class="w-full px-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:border-[color]-500 focus:ring-4 focus:ring-[color]-500/20 transition-all">
                <option value="">ทั้งหมด</option>
                {{-- Options --}}
            </select>
        </div>

        {{-- Filter Buttons --}}
        <div class="md:col-span-4 flex justify-end gap-3">
            <a href="{{ route('admin.xxx.index') }}" class="px-6 py-3 bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-200 rounded-xl transition-all flex items-center gap-2">
                <i class="fas fa-redo"></i>
                รีเซ็ต
            </a>
            <button type="submit" class="px-8 py-3 bg-gradient-to-r from-[color1]-500 to-[color2]-600 hover:from-[color1]-600 hover:to-[color2]-700 text-white rounded-xl font-semibold transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105 flex items-center gap-2">
                <i class="fas fa-search"></i>
                ค้นหา
            </button>
        </div>
    </form>
</div>
```

### 5️⃣ Modern Table

**🎯 ใช้เมื่อ:** แสดงข้อมูลในรูปแบบตาราง

**Template:**

```blade
{{-- Table Container --}}
<div class="glass-card dark:bg-gray-800/50 backdrop-blur-xl rounded-2xl shadow-xl overflow-hidden border border-white/20 dark:border-gray-700/50">
    {{-- Header --}}
    <div class="p-6 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-gray-50 to-white dark:from-gray-800 dark:to-gray-750">
        <div class="flex items-center justify-between">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <i class="fas fa-list text-[color]-600 dark:text-[color]-400"></i>
                รายการ
            </h3>
            <span class="px-4 py-2 bg-[color]-100 dark:bg-[color]-900/30 text-[color]-700 dark:text-[color]-300 rounded-xl text-sm font-semibold">
                {{ number_format($items->total()) }} รายการ
            </span>
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                        <i class="fas fa-[icon] mr-1 text-[color]-500"></i> คอลัมน์
                    </th>
                    {{-- เพิ่ม th อื่นๆ --}}
                    <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                        <i class="fas fa-cogs mr-1 text-indigo-500"></i> จัดการ
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($items as $item)
                    <tr class="hover:bg-gradient-to-r hover:from-[color]-50/50 hover:to-transparent dark:hover:from-gray-700/50 transition-all duration-200">
                        <td class="px-6 py-4">
                            {{-- เนื้อหา --}}
                        </td>
                        {{-- เพิ่ม td อื่นๆ --}}
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-center gap-2">
                                {{-- Action Buttons --}}
                                <a href="#" class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400 flex items-center justify-center hover:bg-blue-200 dark:hover:bg-blue-800 transition-all hover:scale-110 shadow-sm" title="ดูรายละเอียด">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="#" class="w-10 h-10 rounded-xl bg-yellow-100 dark:bg-yellow-900/50 text-yellow-600 dark:text-yellow-400 flex items-center justify-center hover:bg-yellow-200 dark:hover:bg-yellow-800 transition-all hover:scale-110 shadow-sm" title="แก้ไข">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button type="button" class="w-10 h-10 rounded-xl bg-red-100 dark:bg-red-900/50 text-red-600 dark:text-red-400 flex items-center justify-center hover:bg-red-200 dark:hover:bg-red-800 transition-all hover:scale-110 shadow-sm" title="ลบ">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="[จำนวนคอลัมน์]" class="px-6 py-20 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <div class="w-32 h-32 bg-gradient-to-br from-[color]-100 to-[color2]-100 dark:from-[color]-900/30 dark:to-[color2]-900/30 rounded-full flex items-center justify-center mb-6 shadow-xl">
                                    <i class="fas fa-[icon] text-5xl text-[color]-500 dark:text-[color]-400"></i>
                                </div>
                                <h4 class="text-2xl font-bold text-gray-700 dark:text-gray-300 mb-3">ไม่พบข้อมูล</h4>
                                <p class="text-gray-500 dark:text-gray-400 mb-6 max-w-md">คำอธิบาย</p>
                                <a href="#" class="px-8 py-4 bg-gradient-to-r from-[color1]-500 to-[color2]-600 hover:from-[color1]-600 hover:to-[color2]-700 text-white rounded-xl font-semibold hover:shadow-xl transform hover:scale-105 transition-all duration-300 flex items-center gap-2">
                                    <i class="fas fa-plus"></i>
                                    เพิ่มรายการแรก
                                </a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($items->hasPages())
        <div class="p-6 border-t border-gray-200 dark:border-gray-700 bg-gradient-to-r from-gray-50 to-white dark:from-gray-800 dark:to-gray-750">
            {{ $items->links() }}
        </div>
    @endif
</div>
```

### 6️⃣ Product/Item Grid

**🎯 ใช้เมื่อ:** แสดงข้อมูลในรูปแบบ grid cards (สินค้า, โปรไฟล์, etc.)

**Template:**

```blade
{{-- Grid Container --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
    @forelse($items as $item)
        <div class="glass-card dark:bg-gray-800/50 backdrop-blur-xl rounded-2xl shadow-xl overflow-hidden border border-white/20 dark:border-gray-700/50 group hover:shadow-2xl transition-all duration-300 transform hover:scale-[1.02]">
            {{-- Image/Thumbnail --}}
            <div class="aspect-square bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 relative overflow-hidden">
                @if($item->image_url)
                    <img src="{{ $item->image_url }}" alt="{{ $item->name }}"
                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                @else
                    <div class="w-full h-full flex items-center justify-center text-gray-400 dark:text-gray-500">
                        <i class="fas fa-image text-6xl"></i>
                    </div>
                @endif

                {{-- Badges --}}
                <span class="absolute top-3 left-3 px-3 py-1.5 rounded-xl text-xs font-semibold bg-gradient-to-r from-[color1]-500 to-[color2]-600 text-white shadow-lg">
                    Badge
                </span>

                {{-- Overlay on Hover --}}
                <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
            </div>

            {{-- Info --}}
            <div class="p-5 space-y-3">
                <h3 class="font-semibold text-gray-900 dark:text-white line-clamp-2 min-h-[48px]" title="{{ $item->name }}">
                    {{ $item->name }}
                </h3>

                {{-- เพิ่มข้อมูลอื่นๆ --}}

                {{-- Actions --}}
                <div class="flex items-center gap-2 pt-3">
                    <a href="{{ route('admin.xxx.show', $item) }}"
                       class="flex-1 px-4 py-2.5 bg-gradient-to-r from-blue-500 to-blue-600 text-white text-center rounded-xl text-sm font-semibold hover:from-blue-600 hover:to-blue-700 transition-all shadow-md hover:shadow-lg transform hover:scale-105">
                        <i class="fas fa-eye mr-1"></i>ดูรายละเอียด
                    </a>
                    <button type="button"
                            class="px-4 py-2.5 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-xl text-sm font-semibold hover:bg-red-200 dark:hover:bg-red-800 transition-all">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    @empty
        <div class="col-span-full">
            {{-- Empty State (ใช้ template Empty State ด้านล่าง) --}}
        </div>
    @endforelse
</div>
```

### 7️⃣ Gradient Buttons

**🎯 ใช้เมื่อ:** ปุ่ม Call-to-Action, Submit, Actions

**Variants:**

```blade
{{-- Primary CTA Button --}}
<button class="px-8 py-3 bg-gradient-to-r from-[color1]-500 to-[color2]-600 hover:from-[color1]-600 hover:to-[color2]-700 text-white rounded-xl font-semibold transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105 flex items-center gap-2">
    <i class="fas fa-[icon]"></i>
    ตัวอักษร
</button>

{{-- Secondary Button --}}
<button class="px-6 py-3 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white rounded-xl font-semibold transition-all duration-300 shadow-lg border border-white/30">
    ตัวอักษร
</button>

{{-- Outline Button --}}
<button class="px-6 py-3 bg-transparent border-2 border-[color]-500 text-[color]-600 dark:text-[color]-400 hover:bg-[color]-500 hover:text-white rounded-xl font-semibold transition-all">
    ตัวอักษร
</button>

{{-- Icon Button --}}
<button class="w-10 h-10 rounded-xl bg-[color]-100 dark:bg-[color]-900/50 text-[color]-600 dark:text-[color]-400 flex items-center justify-center hover:bg-[color]-200 dark:hover:bg-[color]-800 transition-all hover:scale-110 shadow-sm" title="Tooltip">
    <i class="fas fa-[icon]"></i>
</button>

{{-- Loading State --}}
<button disabled class="px-8 py-3 bg-gradient-to-r from-[color1]-500 to-[color2]-600 text-white rounded-xl font-semibold opacity-50 cursor-not-allowed flex items-center gap-2">
    <i class="fas fa-spinner fa-spin"></i>
    กำลังโหลด...
</button>
```

---

## Color & Gradient System

### 🎨 Gradient Palette

**Hero Header Gradients:**

```css
/* Dashboard */
from-blue-500 via-purple-500 to-pink-600

/* E-commerce/Products */
from-purple-500 via-pink-500 to-rose-600

/* Marketplace */
from-orange-500 via-red-500 to-pink-600

/* Finance/Wallet */
from-yellow-500 via-orange-500 to-red-600

/* Users/Members */
from-green-500 via-emerald-500 to-teal-600

/* Analytics */
from-cyan-500 via-blue-500 to-indigo-600

/* AI/Bots */
from-violet-500 via-purple-500 to-fuchsia-600

/* Settings */
from-gray-500 via-slate-500 to-zinc-600
```

**Button Gradients:**

```css
/* Primary Actions */
from-blue-500 to-purple-600

/* Create/Add */
from-green-500 to-emerald-600

/* Edit/Update */
from-yellow-500 to-orange-600

/* Delete/Remove */
from-red-500 to-pink-600

/* Info/View */
from-cyan-500 to-blue-600
```

---

## Typography & Icons

### 📝 Typography Scale

```blade
{{-- Page Title --}}
<h1 class="text-4xl font-bold text-white drop-shadow-lg">

{{-- Section Title --}}
<h2 class="text-2xl font-bold text-gray-900 dark:text-white">

{{-- Card Title --}}
<h3 class="text-xl font-bold text-gray-900 dark:text-white">

{{-- Subsection Title --}}
<h4 class="text-lg font-semibold text-gray-900 dark:text-white">

{{-- Body Text --}}
<p class="text-base text-gray-700 dark:text-gray-300">

{{-- Small Text --}}
<p class="text-sm text-gray-600 dark:text-gray-400">

{{-- Tiny Text / Metadata --}}
<span class="text-xs text-gray-500 dark:text-gray-500">
```

### 🎯 Icon Usage

**กฎการใช้ Icons:**

1. ✅ ใช้ Font Awesome 6.5.1
2. ✅ ทุก heading ควรมี icon ข้างหน้า
3. ✅ ปุ่มสำคัญควรมี icon
4. ✅ ใช้ icon สอดคล้องกับเนื้อหา
5. ✅ Floating icons ใน Hero Header

**Icon Sizes:**

```blade
{{-- Tiny Icon --}}
<i class="fas fa-[icon] text-xs"></i>

{{-- Small Icon --}}
<i class="fas fa-[icon] text-sm"></i>

{{-- Default Icon --}}
<i class="fas fa-[icon]"></i>

{{-- Large Icon --}}
<i class="fas fa-[icon] text-xl"></i>
<i class="fas fa-[icon] text-2xl"></i>

{{-- Hero Icon --}}
<i class="fas fa-[icon] text-4xl"></i>

{{-- Floating Background Icon --}}
<i class="fas fa-[icon] text-8xl text-white/10"></i>
<i class="fas fa-[icon] text-9xl"></i>
```

---

## Spacing & Layout

### 📐 Spacing System

**Container Spacing:**

```blade
{{-- Page Container --}}
<div class="space-y-6">

{{-- Card Padding --}}
<div class="p-6">

{{-- Section Padding --}}
<div class="p-8">

{{-- Grid Gaps --}}
<div class="gap-4">  <!-- Small gap -->
<div class="gap-6">  <!-- Default gap -->
<div class="gap-8">  <!-- Large gap -->
```

**Responsive Padding:**

```blade
{{-- Mobile to Desktop --}}
<div class="p-4 md:p-6 lg:p-8">
```

### 🏗️ Grid Patterns

**Stats Cards Grid:**

```blade
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5 gap-6">
```

**Product/Item Grid:**

```blade
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
```

**Form Grid:**

```blade
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
```

**Filter Grid:**

```blade
<div class="grid grid-cols-1 md:grid-cols-4 gap-4">
```

---

## Animations & Effects

### ⚡ Required Animations

**1. Float Animation (สำหรับ Floating Icons):**

```css
@keyframes float {
    0%, 100% { transform: translateY(0px) rotate(0deg); }
    50% { transform: translateY(-20px) rotate(5deg); }
}
```

**2. Hover Effects:**

```blade
{{-- Card Hover --}}
class="hover:scale-105 transition-all duration-300"
class="hover:scale-[1.02] transition-all duration-300"

{{-- Button Hover --}}
class="hover:shadow-xl transform hover:scale-105 transition-all"

{{-- Icon Rotation on Hover --}}
class="group-hover:rotate-90 transition-transform duration-300"

{{-- Image Zoom on Hover --}}
class="group-hover:scale-110 transition-transform duration-300"
```

**3. Glass Effects:**

```css
.glass-card {
    background: rgba(255, 255, 255, 0.8);
    backdrop-filter: blur(20px);
}

.dark .glass-card {
    background: rgba(31, 41, 55, 0.8);
}

.glass-fusion {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
}
```

---

## Dark Mode

### 🌓 Dark Mode Rules

**ทุก element ต้องรองรับ dark mode:**

```blade
{{-- Background --}}
bg-white dark:bg-gray-800
bg-gray-50 dark:bg-gray-900
bg-gray-100 dark:bg-gray-800

{{-- Text --}}
text-gray-900 dark:text-white
text-gray-700 dark:text-gray-300
text-gray-600 dark:text-gray-400
text-gray-500 dark:text-gray-500

{{-- Border --}}
border-gray-200 dark:border-gray-700
border-white/20 dark:border-gray-700/50

{{-- Shadow --}}
shadow-xl dark:shadow-gray-900/50

{{-- Gradient Colors --}}
from-blue-500 dark:from-blue-700
to-purple-600 dark:to-purple-800
```

---

## Responsive Design

### 📱 Breakpoint System

```
sm:   640px   (Mobile Landscape)
md:   768px   (Tablet)
lg:   1024px  (Desktop)
xl:   1280px  (Large Desktop)
2xl:  1536px  (Extra Large)
```

### 🎯 Responsive Patterns

```blade
{{-- Typography --}}
<h1 class="text-2xl sm:text-3xl lg:text-4xl">

{{-- Grid --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">

{{-- Flex Direction --}}
<div class="flex flex-col lg:flex-row">

{{-- Padding --}}
<div class="p-4 md:p-6 lg:p-8">

{{-- Hide/Show --}}
<div class="block md:hidden">Mobile Only</div>
<div class="hidden md:block">Desktop Only</div>

{{-- Column Span --}}
<div class="md:col-span-2">
```

---

## เทมเพลตพร้อมใช้

### 📄 Template 1: Index Page (Table)

**ใช้เมื่อ:** หน้ารายการข้อมูลแบบตาราง

[ดู Template แบบเต็ม - อยู่ในส่วนถัดไป]

### 📄 Template 2: Index Page (Grid)

**ใช้เมื่อ:** หน้ารายการสินค้า/รูปภาพ

[ดู Template แบบเต็ม - อยู่ในส่วนถัดไป]

### 📄 Template 3: Create/Edit Form

**ใช้เมื่อ:** หน้าฟอร์มสร้าง/แก้ไข

[ดู Template แบบเต็ม - อยู่ในส่วนถัดไป]

### 📄 Template 4: Show/Detail Page

**ใช้เมื่อ:** หน้ารายละเอียดข้อมูล

[ดู Template แบบเต็ม - อยู่ในส่วนถัดไป]

---

## ✅ Checklist สำหรับทุกหน้า

**ก่อน Push Code ตรวจสอบ:**

- [ ] มี Premium Hero Header พร้อม gradient ที่เหมาะสม
- [ ] มี Animated Orbs และ Floating Icons
- [ ] มี Stats Cards (ถ้ามีข้อมูลสถิติ)
- [ ] ใช้ Glass Card สำหรับ container หลัก
- [ ] ใช้ Gradient Buttons สำหรับ CTA
- [ ] มี Filter Section (ถ้าเป็นหน้า index)
- [ ] มี Empty State ที่สวยงาม
- [ ] รองรับ Dark Mode ทุก element
- [ ] Responsive ทุก breakpoint (Mobile/Tablet/Desktop)
- [ ] มี Hover Effects ที่เหมาะสม
- [ ] ใช้ Icons อย่างสอดคล้อง
- [ ] มี Loading States (ถ้ามี async actions)
- [ ] มี @keyframes float ใน @push('styles')
- [ ] มี .glass-card และ .glass-fusion CSS

---

## 🚫 สิ่งที่ห้ามทำ

1. ❌ **ห้ามใช้ Bootstrap classes** - ใช้ Tailwind เท่านั้น
2. ❌ **ห้ามใช้ inline styles** - ใช้ Tailwind utilities
3. ❌ **ห้าม hard-code สี** - ใช้ Tailwind color system
4. ❌ **ห้ามลืม dark mode** - ทุก element ต้องรองรับ
5. ❌ **ห้ามใช้ gradient เดียวกันทุกหน้า** - แต่ละหน้าต้องมี identity
6. ❌ **ห้ามลืม responsive** - ต้องดูดีทุกขนาดหน้าจอ
7. ❌ **ห้ามใช้ icon ไม่เหมาะสม** - เลือก icon ให้สอดคล้องกับเนื้อหา
8. ❌ **ห้ามใช้ Hero Header แบบเรียบๆ** - ต้องมี animations เสมอ

---

## 📚 Resources

**อ้างอิง:**
- 📁 `resources/views/admin/marketplace/` - Design Reference หลัก
- 📁 `resources/views/admin/dashboard-v3.blade.php` - Dashboard Reference
- 📁 `.claude/V3_UI_DESIGN_SYSTEM.md` - V3 UI Components
- 📁 `.claude/V3_CODING_GUIDELINES.md` - V3 Coding Standards

**Tools:**
- 🎨 [Tailwind CSS Docs](https://tailwindcss.com/docs)
- 🎯 [Font Awesome Icons](https://fontawesome.com/icons)
- 🌈 [Color Gradient Generator](https://cssgradient.io/)

---

**สร้างโดย**: Design Team
**สำหรับ**: Thaiprompt-Affiliate V3 Admin Pages
**Version**: 3.0.0
**Last Updated**: 2025-12-25

---

*"Consistency is the key to great design. ทุกหน้าแอดมินต้องมี DNA เดียวกัน"*
