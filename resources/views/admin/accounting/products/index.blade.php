@extends('layouts.admin-v3')

@section('title', 'สินค้าและบริการ')

@section('content')
<div class="p-6 space-y-6" x-data="{ language: 'th' }">
    <!-- Modern Header with Language Switcher & Gradient -->
    <div class="relative overflow-hidden bg-gradient-to-br from-emerald-500 via-teal-600 to-cyan-700 dark:from-emerald-900 dark:via-teal-900 dark:to-cyan-950 rounded-3xl shadow-2xl p-8">
        <!-- Language Switcher -->
        <div class="absolute top-4 right-4 z-10">
            <div class="relative inline-block" x-data="{ open: false }">
                <button @click="open = !open" class="px-4 py-2 glass-fusion backdrop-blur-sm text-white rounded-xl hover:glass-fusion transition-all duration-300 flex items-center gap-2 shadow-lg">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M7 2a1 1 0 011 1v1h3a1 1 0 110 2H9.578a18.87 18.87 0 01-1.724 4.78c.29.354.596.696.914 1.026a1 1 0 11-1.44 1.389c-.188-.196-.373-.396-.554-.6a19.098 19.098 0 01-3.107 3.567 1 1 0 01-1.334-1.49 17.087 17.087 0 003.13-3.733 18.992 18.992 0 01-1.487-2.494 1 1 0 111.79-.89c.234.47.489.928.764 1.372.417-.934.752-1.913.997-2.927H3a1 1 0 110-2h3V3a1 1 0 011-1zm6 6a1 1 0 01.894.553l2.991 5.982a.869.869 0 01.02.037l.99 1.98a1 1 0 11-1.79.895L15.383 16h-4.764l-.724 1.447a1 1 0 11-1.788-.894l.99-1.98.019-.038 2.99-5.982A1 1 0 0113 8zm-1.382 6h2.764L13 11.236 11.618 14z" clip-rule="evenodd"/>
                    </svg>
                    <span x-text="language === 'th' ? '🇹🇭 ไทย' : language === 'en' ? '🇬🇧 English' : language === 'zh' ? '🇨🇳 中文' : '🇯🇵 日本語'"></span>
                    <svg class="w-4 h-4 transition-transform" :class="open ? 'rotate-180' : ''" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>

                <div x-show="open" @click.away="open = false" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="absolute right-0 mt-2 w-56 glass-fusion dark:bg-gray-800 rounded-xl shadow-2xl overflow-hidden z-20" border border-white/20 dark:border-white/10 style="display: none;">
                    <button @click="language = 'th'; open = false" class="w-full px-4 py-3 text-left hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-colors flex items-center gap-3">
                        <span class="text-2xl">🇹🇭</span>
                        <span class="text-gray-700 dark:text-gray-300 dark:text-gray-300">ภาษาไทย</span>
                    </button>
                    <button @click="language = 'en'; open = false" class="w-full px-4 py-3 text-left hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-colors flex items-center gap-3">
                        <span class="text-2xl">🇬🇧</span>
                        <span class="text-gray-700 dark:text-gray-300 dark:text-gray-300">English</span>
                    </button>
                    <button @click="language = 'zh'; open = false" class="w-full px-4 py-3 text-left hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-colors flex items-center gap-3">
                        <span class="text-2xl">🇨🇳</span>
                        <span class="text-gray-700 dark:text-gray-300 dark:text-gray-300">中文</span>
                    </button>
                    <button @click="language = 'ja'; open = false" class="w-full px-4 py-3 text-left hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-colors flex items-center gap-3">
                        <span class="text-2xl">🇯🇵</span>
                        <span class="text-gray-700 dark:text-gray-300 dark:text-gray-300">日本語</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Header Content -->
        <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-6">
            <div class="flex-1">
                <div class="flex items-center gap-4 mb-3">
                    <div class="p-3 glass-fusion backdrop-blur-sm rounded-2xl" border border-white/20 dark:border-white/10>
                        <svg class="w-10 h-10 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 2a4 4 0 00-4 4v1H5a1 1 0 00-.994.89l-1 9A1 1 0 004 18h12a1 1 0 00.994-1.11l-1-9A1 1 0 0015 7h-1V6a4 4 0 00-4-4zm2 5V6a2 2 0 10-4 0v1h4zm-6 3a1 1 0 112 0 1 1 0 01-2 0zm7-1a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-4xl font-bold text-white mb-2" data-translate>สินค้าและบริการ</h1>
                        <p class="text-emerald-100 text-lg" data-translate>จัดการสินค้าและบริการในระบบบัญชี</p>
                    </div>
                </div>
            </div>

            @if(auth()->user()->hasPermission('accounting.create_products') || auth()->user()->isSuperAdmin())
            <a href="{{ route('admin.accounting.products.create') }}"
               class="inline-flex items-center gap-2 px-6 py-3 glass-fusion backdrop-blur-sm text-white rounded-xl hover:glass-fusion transition-all duration-300 shadow-lg transform hover:scale-105">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"/>
                </svg>
                <span data-translate>เพิ่มสินค้า/บริการ</span>
            </a>
            @endif
        </div>
    </div>

    <!-- Stats Cards with Gradient -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <!-- Total Card -->
        <div class="relative overflow-hidden bg-gradient-to-br from-blue-500 to-indigo-600 dark:from-blue-900 dark:to-indigo-950 rounded-2xl shadow-xl p-6 text-white">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 glass-fusion rounded-full blur-2xl" border border-white/20 dark:border-white/10></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-3">
                    <div class="p-3 glass-fusion backdrop-blur-sm rounded-xl" border border-white/20 dark:border-white/10>
                        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3zM16 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM6.5 18a1.5 1.5 0 100-3 1.5 1.5 0 000 3z"/>
                        </svg>
                    </div>
                </div>
                <h3 class="text-sm font-medium opacity-90 mb-1" data-translate>ทั้งหมด</h3>
                <p class="text-3xl font-bold">{{ number_format($stats['total'] ?? 0) }}</p>
            </div>
        </div>

        <!-- Goods Card -->
        <div class="relative overflow-hidden bg-gradient-to-br from-cyan-500 to-blue-600 dark:from-cyan-700 dark:to-blue-900 rounded-2xl shadow-xl p-6 text-white">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 glass-fusion rounded-full blur-2xl" border border-white/20 dark:border-white/10></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-3">
                    <div class="p-3 glass-fusion backdrop-blur-sm rounded-xl" border border-white/20 dark:border-white/10>
                        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/>
                        </svg>
                    </div>
                </div>
                <h3 class="text-sm font-medium opacity-90 mb-1" data-translate>สินค้า</h3>
                <p class="text-3xl font-bold">{{ number_format($stats['goods'] ?? 0) }}</p>
            </div>
        </div>

        <!-- Services Card -->
        <div class="relative overflow-hidden bg-gradient-to-br from-purple-500 to-pink-600 dark:from-purple-700 dark:to-pink-900 rounded-2xl shadow-xl p-6 text-white">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 glass-fusion rounded-full blur-2xl" border border-white/20 dark:border-white/10></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-3">
                    <div class="p-3 glass-fusion backdrop-blur-sm rounded-xl" border border-white/20 dark:border-white/10>
                        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M6 6V5a3 3 0 013-3h2a3 3 0 013 3v1h2a2 2 0 012 2v3.57A22.952 22.952 0 0110 13a22.95 22.95 0 01-8-1.43V8a2 2 0 012-2h2zm2-1a1 1 0 011-1h2a1 1 0 011 1v1H8V5zm1 5a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1z" clip-rule="evenodd"/>
                            <path d="M2 13.692V16a2 2 0 002 2h12a2 2 0 002-2v-2.308A24.974 24.974 0 0110 15c-2.796 0-5.487-.46-8-1.308z"/>
                        </svg>
                    </div>
                </div>
                <h3 class="text-sm font-medium opacity-90 mb-1" data-translate>บริการ</h3>
                <p class="text-3xl font-bold">{{ number_format($stats['services'] ?? 0) }}</p>
            </div>
        </div>

        <!-- Active Card -->
        <div class="relative overflow-hidden style="background: var(--arrow-x-success-gradient)" dark:from-green-700 dark:to-emerald-900 rounded-2xl shadow-xl p-6 text-white">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 glass-fusion rounded-full blur-2xl" border border-white/20 dark:border-white/10></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-3">
                    <div class="p-3 glass-fusion backdrop-blur-sm rounded-xl" border border-white/20 dark:border-white/10>
                        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                </div>
                <h3 class="text-sm font-medium opacity-90 mb-1" data-translate>ใช้งานอยู่</h3>
                <p class="text-3xl font-bold">{{ number_format($stats['active'] ?? 0) }}</p>
            </div>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden" border border-white/20 dark:border-white/10>
        <div class="bg-gradient-to-r from-emerald-500 to-teal-600 dark:from-emerald-700 dark:to-teal-800 px-6 py-4">
            <div class="flex items-center gap-3">
                <div class="p-2 glass-fusion backdrop-blur-sm rounded-xl" border border-white/20 dark:border-white/10>
                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <h2 class="text-xl font-bold text-white" data-translate>กรองข้อมูล</h2>
            </div>
        </div>

        <div class="p-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Search Input -->
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="ค้นหาชื่อสินค้า, รหัส..."
                           class="w-full pl-10 pr-4 py-3 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all">
                </div>

                <!-- Type Select -->
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M7 3a1 1 0 000 2h6a1 1 0 100-2H7zM4 7a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zM2 11a2 2 0 012-2h12a2 2 0 012 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-4z"/>
                        </svg>
                    </div>
                    <select name="type"
                            class="w-full pl-10 pr-4 py-3 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all">
                        <option value="">ประเภททั้งหมด</option>
                        <option value="goods" {{ request('type') === 'goods' ? 'selected' : '' }}>สินค้า</option>
                        <option value="service" {{ request('type') === 'service' ? 'selected' : '' }}>บริการ</option>
                    </select>
                </div>

                <!-- Status Select -->
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <select name="status"
                            class="w-full pl-10 pr-4 py-3 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all">
                        <option value="">สถานะทั้งหมด</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>ใช้งาน</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>ไม่ใช้งาน</option>
                    </select>
                </div>

                <!-- Search Button -->
                <button type="submit"
                        class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-700 hover:to-teal-800 text-white rounded-xl shadow-lg transition-all duration-300 transform hover:scale-105">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/>
                    </svg>
                    <span data-translate>ค้นหา</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Products Table -->
    <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden" border border-white/20 dark:border-white/10>
        <div class="bg-gradient-to-r from-emerald-500 to-teal-600 dark:from-emerald-700 dark:to-teal-800 px-6 py-4">
            <div class="flex items-center gap-3">
                <div class="p-2 glass-fusion backdrop-blur-sm rounded-xl" border border-white/20 dark:border-white/10>
                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                        <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <h2 class="text-xl font-bold text-white" data-translate>รายการทั้งหมด</h2>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-750">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 dark:text-gray-300 uppercase tracking-wider" data-translate>รหัส</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 dark:text-gray-300 uppercase tracking-wider" data-translate>ชื่อสินค้า/บริการ</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 dark:text-gray-300 uppercase tracking-wider" data-translate>ประเภท</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-300 dark:text-gray-300 uppercase tracking-wider" data-translate>ราคาขาย</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-300 dark:text-gray-300 uppercase tracking-wider" data-translate>ราคาต้นทุน</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-700 dark:text-gray-300 dark:text-gray-300 uppercase tracking-wider" data-translate>สถานะ</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-700 dark:text-gray-300 dark:text-gray-300 uppercase tracking-wider" data-translate>การดำเนินการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($products ?? [] as $product)
                    <tr class="hover:bg-emerald-50 dark:hover:bg-emerald-900/10 transition-colors duration-200">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <a href="{{ route('admin.accounting.products.show', $product) }}"
                               class="flex items-center gap-2 text-emerald-600 dark:text-emerald-400 hover:text-emerald-800 dark:hover:text-emerald-300 font-semibold transition-colors">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/>
                                </svg>
                                {{ $product->code }}
                            </a>
                        </td>
                        <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-300">
                            {{ $product->name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">
                            @if($product->type === 'goods')
                                <span class="inline-flex items-center gap-2">
                                    <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/>
                                    </svg>
                                    สินค้า
                                </span>
                            @else
                                <span class="inline-flex items-center gap-2">
                                    <svg class="w-5 h-5 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M6 6V5a3 3 0 013-3h2a3 3 0 013 3v1h2a2 2 0 012 2v3.57A22.952 22.952 0 0110 13a22.95 22.95 0 01-8-1.43V8a2 2 0 012-2h2zm2-1a1 1 0 011-1h2a1 1 0 011 1v1H8V5zm1 5a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1z" clip-rule="evenodd"/>
                                        <path d="M2 13.692V16a2 2 0 002 2h12a2 2 0 002-2v-2.308A24.974 24.974 0 0110 15c-2.796 0-5.487-.46-8-1.308z"/>
                                    </svg>
                                    บริการ
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-semibold text-gray-900 dark:text-gray-300">
                            ฿{{ number_format($product->sale_price, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-gray-300">
                            ฿{{ number_format($product->cost_price ?? 0, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full
                                {{ $product->is_active ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300' : 'bg-gray-100/50 dark:bg-gray-800/50 dark:bg-gray-700 text-gray-900 dark:text-white dark:text-gray-300' }}">
                                {{ $product->is_active ? 'ใช้งาน' : 'ไม่ใช้งาน' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                            <div class="flex items-center justify-center gap-3">
                                <a href="{{ route('admin.accounting.products.show', $product) }}"
                                   class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition-colors"
                                   title="ดูรายละเอียด">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                        <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                                    </svg>
                                </a>

                                @if(auth()->user()->hasPermission('accounting.edit_products') || auth()->user()->isSuperAdmin())
                                <a href="{{ route('admin.accounting.products.edit', $product) }}"
                                   class="text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-300 transition-colors"
                                   title="แก้ไข">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
                                    </svg>
                                </a>
                                @endif

                                @if(auth()->user()->hasPermission('accounting.delete_products') || auth()->user()->isSuperAdmin())
                                <form action="{{ route('admin.accounting.products.destroy', $product) }}"
                                      method="POST" class="inline"
                                      onsubmit="return confirm('ยืนยันการลบสินค้า/บริการนี้?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 transition-colors"
                                            title="ลบ">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                        </svg>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center justify-center text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                <svg class="w-16 h-16 mb-4 opacity-50" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 2a4 4 0 00-4 4v1H5a1 1 0 00-.994.89l-1 9A1 1 0 004 18h12a1 1 0 00.994-1.11l-1-9A1 1 0 0015 7h-1V6a4 4 0 00-4-4zm2 5V6a2 2 0 10-4 0v1h4zm-6 3a1 1 0 112 0 1 1 0 01-2 0zm7-1a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd"/>
                                </svg>
                                <p class="text-lg font-medium" data-translate>ไม่พบข้อมูลสินค้าและบริการ</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if(isset($products) && $products->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 dark:border-gray-700 bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-gray-750">
            {{ $products->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
