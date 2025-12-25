@extends('layouts.admin-v3')

@section('title', 'จัดการผู้ใช้')

@section('content')
<div class="space-y-6">
    {{-- Success/Error Messages --}}
    @if(session('success'))
        <div class="border-2 px-6 py-4 rounded-xl shadow-lg" role="alert"
             style="background-color: color-mix(in srgb, var(--arrow-x-success) 15%, transparent); border-color: var(--arrow-x-success); color: var(--arrow-x-success)">
            <div class="flex items-center">
                <svg class="w-6 h-6 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                <span class="font-semibold">{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="border-2 px-6 py-4 rounded-xl shadow-lg" role="alert"
             style="background-color: color-mix(in srgb, var(--arrow-x-error) 15%, transparent); border-color: var(--arrow-x-error); color: var(--arrow-x-error)">
            <div class="flex items-center">
                <svg class="w-6 h-6 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg>
                <span class="font-semibold">{{ session('error') }}</span>
            </div>
        </div>
    @endif

    {{-- Premium Hero Header with Gradient & Animations --}}
    <div class="relative overflow-hidden bg-gradient-to-r from-green-500 via-emerald-500 to-teal-600 dark:from-green-700 dark:via-emerald-700 dark:to-teal-800 rounded-2xl shadow-2xl p-8">
        {{-- Animated Background Orbs --}}
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 right-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse" style="animation-delay: 0.5s"></div>
            <div class="absolute top-1/2 left-1/3 w-64 h-64 bg-white rounded-full blur-3xl animate-pulse" style="animation-delay: 1s"></div>
        </div>

        {{-- Floating Icons --}}
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute text-white/10 text-8xl top-10 right-20" style="animation: float 6s ease-in-out infinite">
                <i class="fas fa-users"></i>
            </div>
            <div class="absolute text-white/10 text-6xl bottom-10 right-40" style="animation: float 6s ease-in-out infinite; animation-delay: 0.3s">
                <i class="fas fa-user-circle"></i>
            </div>
            <div class="absolute text-white/10 text-7xl top-20 left-1/4" style="animation: float 6s ease-in-out infinite; animation-delay: 0.5s">
                <i class="fas fa-user-shield"></i>
            </div>
        </div>

        {{-- Header Content --}}
        <div class="relative z-10">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                {{-- Title Section --}}
                <div class="flex-1">
                    <div class="flex items-center gap-4 mb-3">
                        <div class="glass-fusion p-4 rounded-2xl">
                            <i class="fas fa-users text-4xl text-white drop-shadow-lg"></i>
                        </div>
                        <div>
                            <h1 class="text-4xl font-bold text-white drop-shadow-lg">จัดการผู้ใช้งาน</h1>
                            <p class="text-green-100 text-lg mt-1">จัดการผู้ใช้งานและสิทธิ์การเข้าถึงระบบ</p>
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('admin.roles.index') }}"
                       class="glass-fusion hover:bg-white/30 text-white px-6 py-3 rounded-xl text-sm font-semibold flex items-center gap-2 transition-all duration-300 shadow-lg border border-white/30 group">
                        <i class="fas fa-shield-alt group-hover:scale-110 transition-transform"></i>
                        จัดการบทบาท
                    </a>
                    <a href="{{ route('admin.users.create') }}"
                       class="bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white px-6 py-3 rounded-xl text-sm font-semibold flex items-center gap-2 transition-all duration-300 shadow-lg border border-white/30 group">
                        <i class="fas fa-user-plus group-hover:rotate-90 transition-transform duration-300"></i>
                        เพิ่มผู้ใช้ใหม่
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div x-data="{ isClassicTheme: false }"
         x-init="
             // เช็คว่าเป็น Classic Theme หรือไม่ (เช็คจาก settings)
             const checkClassic = () => {
                 const saved = localStorage.getItem('themeSettings');
                 if (saved) {
                     const settings = JSON.parse(saved);
                     // Classic = glassOpacity === 0 และ glassBlur === 0
                     isClassicTheme = (settings.glassOpacity === 0 || settings.glassOpacity === '0') &&
                                     (settings.glassBlur === 0 || settings.glassBlur === '0');
                 } else {
                     // ถ้าไม่มี settings = ใช้ค่าเริ่มต้น (glassmorphism)
                     isClassicTheme = false;
                 }
             };
             checkClassic();
             // เช็คซ้ำเมื่อมีการเปลี่ยนธีม
             setInterval(checkClassic, 500);
             window.addEventListener('storage', checkClassic);
         ">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <i class="fas fa-chart-bar text-green-600 dark:text-green-400"></i>
            สถิติภาพรวม
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            {{-- Total Users --}}
            <div class="group relative overflow-hidden rounded-2xl shadow-xl p-6 transform hover:scale-105 transition-all duration-300 cursor-pointer"
                 :class="isClassicTheme
                     ? 'bg-gradient-to-br from-blue-500 to-blue-700 dark:from-blue-600 dark:to-blue-900'
                     : 'bg-white dark:bg-slate-800'">
                {{-- Classic: Background Icon --}}
                <div class="absolute -right-8 -top-8 opacity-10" x-show="isClassicTheme">
                    <i class="fas fa-users text-9xl text-white"></i>
                </div>
                {{-- Modern: Gradient Icon --}}
                <div class="flex items-center gap-3" x-show="!isClassicTheme">
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-400 to-blue-600 rounded-lg flex items-center justify-center text-2xl shadow-lg">
                        <i class="fas fa-user-friends text-white"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-gray-500 dark:text-gray-400 text-sm">ผู้ใช้ทั้งหมด</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($users->total()) }}</p>
                    </div>
                </div>
                {{-- Classic: Content --}}
                <div class="relative z-10" x-show="isClassicTheme">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-5xl">👥</span>
                        <span class="px-2 py-1 bg-white/20 rounded-lg text-xs font-semibold text-white">Total</span>
                    </div>
                    <p class="text-blue-100 text-sm mb-1">ผู้ใช้ทั้งหมด</p>
                    <p class="text-3xl font-bold text-white">{{ number_format($users->total()) }}</p>
                    <p class="text-xs text-blue-100 mt-2">Total Users</p>
                </div>
            </div>

            {{-- Active Users --}}
            @php
                $activeCount = \App\Models\User::where('status', 'active')->count();
            @endphp
            <div class="group relative overflow-hidden rounded-2xl shadow-xl p-6 transform hover:scale-105 transition-all duration-300 cursor-pointer"
                 :class="isClassicTheme
                     ? 'bg-gradient-to-br from-green-500 to-emerald-700 dark:from-green-600 dark:to-emerald-900'
                     : 'bg-white dark:bg-slate-800'">
                {{-- Classic: Background Icon --}}
                <div class="absolute -right-8 -top-8 opacity-10" x-show="isClassicTheme">
                    <i class="fas fa-user-check text-9xl text-white"></i>
                </div>
                {{-- Modern: Gradient Icon --}}
                <div class="flex items-center gap-3" x-show="!isClassicTheme">
                    <div class="w-12 h-12 bg-gradient-to-br from-green-400 to-emerald-600 rounded-lg flex items-center justify-center text-2xl shadow-lg">
                        <i class="fas fa-check-circle text-white"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-gray-500 dark:text-gray-400 text-sm">ผู้ใช้ Active</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($activeCount) }}</p>
                    </div>
                </div>
                {{-- Classic: Content --}}
                <div class="relative z-10" x-show="isClassicTheme">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-5xl">✅</span>
                        <span class="px-2 py-1 bg-white/20 rounded-lg text-xs font-semibold text-white">Active</span>
                    </div>
                    <p class="text-green-100 text-sm mb-1">ผู้ใช้ Active</p>
                    <p class="text-3xl font-bold text-white">{{ number_format($activeCount) }}</p>
                    <p class="text-xs text-green-100 mt-2">Active Status</p>
                </div>
            </div>

            {{-- Email Verified --}}
            @php
                $verifiedCount = \App\Models\User::whereNotNull('email_verified_at')->count();
            @endphp
            <div class="group relative overflow-hidden rounded-2xl shadow-xl p-6 transform hover:scale-105 transition-all duration-300 cursor-pointer"
                 :class="isClassicTheme
                     ? 'bg-gradient-to-br from-cyan-500 to-blue-600 dark:from-cyan-600 dark:to-blue-800'
                     : 'bg-white dark:bg-slate-800'">
                {{-- Classic: Background Icon --}}
                <div class="absolute -right-8 -top-8 opacity-10" x-show="isClassicTheme">
                    <i class="fas fa-envelope-circle-check text-9xl text-white"></i>
                </div>
                {{-- Modern: Gradient Icon --}}
                <div class="flex items-center gap-3" x-show="!isClassicTheme">
                    <div class="w-12 h-12 bg-gradient-to-br from-cyan-400 to-blue-600 rounded-lg flex items-center justify-center text-2xl shadow-lg">
                        <i class="fas fa-shield-check text-white"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-gray-500 dark:text-gray-400 text-sm">ยืนยันอีเมลแล้ว</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($verifiedCount) }}</p>
                    </div>
                </div>
                {{-- Classic: Content --}}
                <div class="relative z-10" x-show="isClassicTheme">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-5xl">📧</span>
                        <span class="px-2 py-1 bg-white/20 rounded-lg text-xs font-semibold text-white">Verified</span>
                    </div>
                    <p class="text-cyan-100 text-sm mb-1">ยืนยันอีเมลแล้ว</p>
                    <p class="text-3xl font-bold text-white">{{ number_format($verifiedCount) }}</p>
                    <p class="text-xs text-cyan-100 mt-2">Email Verified</p>
                </div>
            </div>

            {{-- Total Roles --}}
            <div class="group relative overflow-hidden rounded-2xl shadow-xl p-6 transform hover:scale-105 transition-all duration-300 cursor-pointer"
                 :class="isClassicTheme
                     ? 'bg-gradient-to-br from-purple-500 to-pink-600 dark:from-purple-600 dark:to-pink-800'
                     : 'bg-white dark:bg-slate-800'">
                {{-- Classic: Background Icon --}}
                <div class="absolute -right-8 -top-8 opacity-10" x-show="isClassicTheme">
                    <i class="fas fa-user-tag text-9xl text-white"></i>
                </div>
                {{-- Modern: Gradient Icon --}}
                <div class="flex items-center gap-3" x-show="!isClassicTheme">
                    <div class="w-12 h-12 bg-gradient-to-br from-purple-400 to-pink-600 rounded-lg flex items-center justify-center text-2xl shadow-lg">
                        <i class="fas fa-shield-alt text-white"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-gray-500 dark:text-gray-400 text-sm">บทบาททั้งหมด</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($roles->count()) }}</p>
                    </div>
                </div>
                {{-- Classic: Content --}}
                <div class="relative z-10" x-show="isClassicTheme">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-5xl">🛡️</span>
                        <span class="px-2 py-1 bg-white/20 rounded-lg text-xs font-semibold text-white">Roles</span>
                    </div>
                    <p class="text-purple-100 text-sm mb-1">บทบาททั้งหมด</p>
                    <p class="text-3xl font-bold text-white">{{ number_format($roles->count()) }}</p>
                    <p class="text-xs text-purple-100 mt-2">Total Roles</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Links เมนูด่วน --}}
    <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <i class="fas fa-rocket text-green-600 dark:text-green-400"></i>
            เมนูด่วน
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {{-- จัดการบทบาท --}}
            <a href="{{ route('admin.roles.index') }}" class="glass-card dark:bg-gray-800/50 backdrop-blur-xl p-4 hover:bg-white/30 dark:hover:bg-gray-700/50 transition-all duration-300 rounded-xl border border-white/20 dark:border-gray-700/50 group">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-purple-500/30 dark:bg-purple-900/50 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <i class="fas fa-shield-alt text-2xl text-purple-600 dark:text-purple-300"></i>
                    </div>
                    <div class="flex-1">
                        <h4 class="text-gray-900 dark:text-white font-semibold group-hover:text-purple-600 dark:group-hover:text-purple-300 transition">จัดการบทบาท</h4>
                        <p class="text-gray-600 dark:text-gray-400 text-xs">Roles & Permissions</p>
                    </div>
                    <i class="fas fa-chevron-right text-gray-400 dark:text-gray-500 ml-auto group-hover:text-purple-600 dark:group-hover:text-purple-300 transition"></i>
                </div>
            </a>

            {{-- จัดการสิทธิ์ --}}
            <a href="{{ route('admin.permissions.index') }}" class="glass-card dark:bg-gray-800/50 backdrop-blur-xl p-4 hover:bg-white/30 dark:hover:bg-gray-700/50 transition-all duration-300 rounded-xl border border-white/20 dark:border-gray-700/50 group">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-blue-500/30 dark:bg-blue-900/50 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <i class="fas fa-key text-2xl text-blue-600 dark:text-blue-300"></i>
                    </div>
                    <div class="flex-1">
                        <h4 class="text-gray-900 dark:text-white font-semibold group-hover:text-blue-600 dark:group-hover:text-blue-300 transition">จัดการสิทธิ์</h4>
                        <p class="text-gray-600 dark:text-gray-400 text-xs">Permissions Management</p>
                    </div>
                    <i class="fas fa-chevron-right text-gray-400 dark:text-gray-500 ml-auto group-hover:text-blue-600 dark:group-hover:text-blue-300 transition"></i>
                </div>
            </a>

            {{-- ตั้งค่าระบบ --}}
            <a href="{{ route('admin.settings.index') }}" class="glass-card dark:bg-gray-800/50 backdrop-blur-xl p-4 hover:bg-white/30 dark:hover:bg-gray-700/50 transition-all duration-300 rounded-xl border border-white/20 dark:border-gray-700/50 group">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-orange-500/30 dark:bg-orange-900/50 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <i class="fas fa-cogs text-2xl text-orange-600 dark:text-orange-300"></i>
                    </div>
                    <div class="flex-1">
                        <h4 class="text-gray-900 dark:text-white font-semibold group-hover:text-orange-600 dark:group-hover:text-orange-300 transition">ตั้งค่าระบบ</h4>
                        <p class="text-gray-600 dark:text-gray-400 text-xs">System Settings</p>
                    </div>
                    <i class="fas fa-chevron-right text-gray-400 dark:text-gray-500 ml-auto group-hover:text-orange-600 dark:group-hover:text-orange-300 transition"></i>
                </div>
            </a>
        </div>
    </div>

    {{-- Filters Section --}}
    <div class="glass-card dark:bg-gray-800/50 backdrop-blur-xl rounded-2xl shadow-xl p-6 border border-white/20 dark:border-gray-700/50">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <i class="fas fa-filter text-green-600 dark:text-green-400"></i>
            ตัวกรองข้อมูล
        </h3>

        <form method="GET" action="{{ route('admin.users.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            {{-- Search --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fas fa-search mr-1 text-blue-600 dark:text-blue-400"></i>
                    ค้นหา
                </label>
                <div class="relative">
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="ชื่อ, อีเมล หรือเลขสมาชิก..."
                           class="w-full pl-12 pr-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:border-green-500 focus:ring-4 focus:ring-green-500/20 transition-all placeholder:text-gray-400">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <i class="fas fa-search text-gray-400"></i>
                    </div>
                </div>
            </div>

            {{-- Role Filter --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fas fa-user-tag mr-1 text-purple-600 dark:text-purple-400"></i>
                    บทบาท
                </label>
                <select name="role" class="w-full px-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:border-green-500 focus:ring-4 focus:ring-green-500/20 transition-all">
                    <option value="">ทั้งหมด</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}" {{ request('role') == $role->id ? 'selected' : '' }}>
                            {{ $role->display_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Per Page --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fas fa-list-ol mr-1 text-gray-600 dark:text-gray-400"></i>
                    จำนวนต่อหน้า
                </label>
                <select name="per_page" class="w-full px-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:border-green-500 focus:ring-4 focus:ring-green-500/20 transition-all">
                    <option value="10" {{ request('per_page') == 10 ? 'selected' : '' }}>10</option>
                    <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
                    <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                    <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                </select>
            </div>

            {{-- Filter Buttons --}}
            <div class="md:col-span-4 flex justify-end gap-3">
                <a href="{{ route('admin.users.index') }}" class="px-6 py-3 bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-200 rounded-xl transition-all flex items-center gap-2">
                    <i class="fas fa-redo"></i>
                    รีเซ็ต
                </a>
                <button type="submit" class="px-8 py-3 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white rounded-xl font-semibold transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105 flex items-center gap-2">
                    <i class="fas fa-search"></i>
                    ค้นหา
                </button>
            </div>
        </form>
    </div>

    {{-- Bulk Actions Bar --}}
    <div id="bulk-actions-bar" class="hidden glass-fusion rounded-2xl shadow-lg border-2 border-green-500 p-5">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="flex items-center justify-center w-10 h-10 rounded-full bg-green-500">
                    <i class="fas fa-check-circle text-white"></i>
                </div>
                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                    เลือกแล้ว <span id="selected-count" class="font-bold text-lg text-green-600">0</span> รายการ
                </span>
            </div>
            <div class="flex gap-3">
                <button onclick="bulkAction('delete')" class="px-5 py-2.5 bg-red-500 hover:bg-red-600 text-white font-semibold rounded-xl transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5 flex items-center gap-2">
                    <i class="fas fa-trash"></i>
                    ลบที่เลือก
                </button>
                <button onclick="clearSelection()" class="px-5 py-2.5 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-xl hover:bg-gray-300 dark:hover:bg-gray-500 transition-all duration-200 shadow-md">
                    ยกเลิก
                </button>
            </div>
        </div>
    </div>

    {{-- Users Table --}}
    <div class="glass-card dark:bg-gray-800/50 backdrop-blur-xl rounded-2xl shadow-xl overflow-hidden border border-white/20 dark:border-gray-700/50">
        {{-- Table Header --}}
        <div class="p-6 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-gray-50 to-white dark:from-gray-800 dark:to-gray-750">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="fas fa-list text-green-600 dark:text-green-400"></i>
                    รายการผู้ใช้งาน
                </h3>
                <span class="px-4 py-2 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded-xl text-sm font-semibold">
                    {{ number_format($users->total()) }} ผู้ใช้
                </span>
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800">
                    <tr>
                        <th class="px-6 py-4 text-left">
                            <input type="checkbox" id="select-all"
                                   class="rounded-lg border-gray-300 w-5 h-5 cursor-pointer focus:ring-2 focus:ring-green-500"
                                   onchange="toggleSelectAll(this)">
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                            <i class="fas fa-hashtag mr-1 text-blue-500"></i> เลขสมาชิก
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                            <i class="fas fa-user mr-1 text-green-500"></i> ผู้ใช้
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                            <i class="fas fa-envelope mr-1 text-cyan-500"></i> อีเมล
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                            <i class="fas fa-shield-alt mr-1 text-purple-500"></i> บทบาท
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                            <i class="fas fa-calendar mr-1 text-orange-500"></i> สร้างเมื่อ
                        </th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                            <i class="fas fa-cogs mr-1 text-indigo-500"></i> จัดการ
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($users as $user)
                        <tr class="hover:bg-gradient-to-r hover:from-green-50/50 hover:to-transparent dark:hover:from-gray-700/50 transition-all duration-200">
                            <td class="px-6 py-4">
                                <input type="checkbox"
                                       class="user-checkbox rounded-lg border-gray-300 w-5 h-5 cursor-pointer focus:ring-2 focus:ring-green-500"
                                       value="{{ $user->id }}"
                                       data-user-id="{{ $user->id }}"
                                       data-super-admin="{{ $user->is_super_admin ? '1' : '0' }}"
                                       onchange="updateBulkActions()"
                                       {{ $user->is_super_admin ? 'disabled' : '' }}>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($user->member_number)
                                    <span class="px-3 py-1.5 text-sm font-bold rounded-lg bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-700">
                                        {{ $user->member_number }}
                                    </span>
                                @else
                                    <form action="{{ route('admin.users.generate-member-number', $user) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit"
                                                onclick="return confirm('สร้างเลขสมาชิกให้ผู้ใช้นี้หรือไม่?')"
                                                class="px-3 py-1.5 bg-green-500 hover:bg-green-600 text-white text-xs font-semibold rounded-lg transition-all flex items-center gap-1">
                                            <i class="fas fa-plus"></i>
                                            สร้างเลขสมาชิก
                                        </button>
                                    </form>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-green-400 to-emerald-600 flex items-center justify-center text-white font-bold shadow-lg transform group-hover:scale-110 transition-transform">
                                        {{ strtoupper(substr($user->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900 dark:text-white">{{ $user->name }}</p>
                                        @if($user->is_super_admin)
                                            <span class="px-2 py-0.5 text-xs font-bold rounded-lg bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 border border-red-200 dark:border-red-700">
                                                <i class="fas fa-crown mr-1"></i>Super Admin
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-600 dark:text-gray-400">{{ $user->email }}</div>
                                @if($user->email_verified_at)
                                    <span class="inline-flex items-center text-xs font-medium text-green-600 dark:text-green-400">
                                        <i class="fas fa-check-circle mr-1"></i>ยืนยันแล้ว
                                    </span>
                                @else
                                    <span class="inline-flex items-center text-xs font-medium text-yellow-600 dark:text-yellow-400">
                                        <i class="fas fa-clock mr-1"></i>รอยืนยัน
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($user->roleModel)
                                    @php
                                        $roleColors = [
                                            'super_admin' => 'from-red-500 to-pink-600',
                                            'admin' => 'from-blue-500 to-indigo-600',
                                            'seller' => 'from-yellow-500 to-orange-600',
                                            'user' => 'from-green-500 to-emerald-600',
                                        ];
                                        $gradient = $roleColors[$user->roleModel->name] ?? 'from-gray-500 to-gray-600';
                                    @endphp
                                    <span class="px-3 py-1.5 inline-flex text-xs font-semibold rounded-xl bg-gradient-to-r {{ $gradient }} text-white shadow-lg">
                                        {{ $user->roleModel->display_name }}
                                    </span>
                                @else
                                    <span class="px-3 py-1.5 inline-flex text-xs font-semibold rounded-xl bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                        {{ $user->getRoleDisplayName() }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $user->created_at->format('d/m/Y') }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $user->created_at->format('H:i') }} น.</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-2">
                                    {{-- View --}}
                                    <a href="{{ route('admin.users.show', $user) }}"
                                       class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400 flex items-center justify-center hover:bg-blue-200 dark:hover:bg-blue-800 transition-all hover:scale-110 shadow-sm"
                                       title="ดูรายละเอียด">
                                        <i class="fas fa-eye"></i>
                                    </a>

                                    {{-- Edit --}}
                                    <a href="{{ route('admin.users.edit', $user) }}"
                                       class="w-10 h-10 rounded-xl bg-yellow-100 dark:bg-yellow-900/50 text-yellow-600 dark:text-yellow-400 flex items-center justify-center hover:bg-yellow-200 dark:hover:bg-yellow-800 transition-all hover:scale-110 shadow-sm"
                                       title="แก้ไข">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    {{-- Delete --}}
                                    @if(!$user->is_super_admin)
                                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline"
                                              onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะลบผู้ใช้นี้?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="w-10 h-10 rounded-xl bg-red-100 dark:bg-red-900/50 text-red-600 dark:text-red-400 flex items-center justify-center hover:bg-red-200 dark:hover:bg-red-800 transition-all hover:scale-110 shadow-sm"
                                                    title="ลบ">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    @else
                                        <div class="w-10 h-10 rounded-xl bg-gray-200 dark:bg-gray-700 text-gray-400 dark:text-gray-500 flex items-center justify-center cursor-not-allowed opacity-50">
                                            <i class="fas fa-lock"></i>
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-20 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-32 h-32 bg-gradient-to-br from-green-100 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/30 rounded-full flex items-center justify-center mb-6 shadow-xl">
                                        <i class="fas fa-users text-5xl text-green-500 dark:text-green-400"></i>
                                    </div>
                                    <h4 class="text-2xl font-bold text-gray-700 dark:text-gray-300 mb-3">ไม่พบผู้ใช้งาน</h4>
                                    <p class="text-gray-500 dark:text-gray-400 mb-6 max-w-md">ลองปรับเปลี่ยนตัวกรองหรือเพิ่มผู้ใช้ใหม่เข้าสู่ระบบ</p>
                                    <a href="{{ route('admin.users.create') }}"
                                       class="px-8 py-4 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white rounded-xl font-semibold hover:shadow-xl transform hover:scale-105 transition-all duration-300 flex items-center gap-2">
                                        <i class="fas fa-user-plus"></i>
                                        เพิ่มผู้ใช้แรก
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($users->hasPages())
            <div class="p-6 border-t border-gray-200 dark:border-gray-700 bg-gradient-to-r from-gray-50 to-white dark:from-gray-800 dark:to-gray-750">
                <div class="flex items-center justify-between">
                    <div class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        แสดง <span class="font-bold text-green-600">{{ $users->firstItem() ?? 0 }}</span>
                        ถึง <span class="font-bold text-green-600">{{ $users->lastItem() ?? 0 }}</span>
                        จากทั้งหมด <span class="font-bold text-green-600">{{ $users->total() }}</span> รายการ
                    </div>
                    <div>
                        {{ $users->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

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

@push('scripts')
<script>
// Bulk selection functionality
function toggleSelectAll(checkbox) {
    const checkboxes = document.querySelectorAll('.user-checkbox:not(:disabled)');
    checkboxes.forEach(cb => {
        cb.checked = checkbox.checked;
    });
    updateBulkActions();
}

function updateBulkActions() {
    const selectedCheckboxes = document.querySelectorAll('.user-checkbox:checked');
    const selectedCount = selectedCheckboxes.length;
    const bulkActionsBar = document.getElementById('bulk-actions-bar');
    const selectedCountSpan = document.getElementById('selected-count');

    if (selectedCount > 0) {
        bulkActionsBar.classList.remove('hidden');
        selectedCountSpan.textContent = selectedCount;
    } else {
        bulkActionsBar.classList.add('hidden');
    }

    // Update select-all checkbox state
    const allCheckboxes = document.querySelectorAll('.user-checkbox:not(:disabled)');
    const selectAllCheckbox = document.getElementById('select-all');
    selectAllCheckbox.checked = selectedCount === allCheckboxes.length && allCheckboxes.length > 0;
}

function clearSelection() {
    const checkboxes = document.querySelectorAll('.user-checkbox');
    checkboxes.forEach(cb => cb.checked = false);
    document.getElementById('select-all').checked = false;
    updateBulkActions();
}

function bulkAction(action) {
    const selectedCheckboxes = document.querySelectorAll('.user-checkbox:checked');
    const selectedIds = Array.from(selectedCheckboxes).map(cb => cb.value);

    if (selectedIds.length === 0) {
        alert('กรุณาเลือกผู้ใช้อย่างน้อย 1 คน');
        return;
    }

    if (action === 'delete') {
        if (!confirm(`คุณแน่ใจหรือไม่ที่จะลบผู้ใช้ ${selectedIds.length} คน?`)) {
            return;
        }

        // Create form to submit bulk delete
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("admin.users.index") }}/bulk-delete';

        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';
        form.appendChild(csrfToken);

        selectedIds.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'user_ids[]';
            input.value = id;
            form.appendChild(input);
        });

        document.body.appendChild(form);
        form.submit();
    }
}
</script>
@endpush
@endsection
