{{--
  * หน้าจัดการสินค้า (Product Management)
  * แสดงรายการสินค้าทั้งหมดพร้อมฟังก์ชันค้นหา กรอง และจัดการ
  * รองรับ Dark Mode และ Responsive Design
  --}}

@extends('layouts.admin')

@section('title', 'จัดการสินค้า')

@section('content')
<div class="space-y-6">
    {{-- Alert Messages พร้อม Animation และ Close Button --}}
    @if(session('success'))
        <div x-data="{ show: true }" x-show="show" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-2" x-transition:enter-end="opacity-100 transform translate-y-0" class="bg-gradient-to-r from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 border-l-4 border-green-500 dark:border-green-400 rounded-lg shadow-lg p-4" role="alert">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0">
                        <svg class="w-6 h-6 text-green-500 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <span class="text-green-800 dark:text-green-200 font-medium">{{ session('success') }}</span>
                </div>
                <button @click="show = false" class="text-green-500 dark:text-green-400 hover:text-green-700 dark:hover:text-green-300 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div x-data="{ show: true }" x-show="show" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-2" x-transition:enter-end="opacity-100 transform translate-y-0" class="bg-gradient-to-r from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20 border-l-4 border-red-500 dark:border-red-400 rounded-lg shadow-lg p-4" role="alert">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0">
                        <svg class="w-6 h-6 text-red-500 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <span class="text-red-800 dark:text-red-200 font-medium">{{ session('error') }}</span>
                </div>
                <button @click="show = false" class="text-red-500 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div x-data="{ show: true }" x-show="show" x-transition class="bg-gradient-to-r from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20 border-l-4 border-red-500 dark:border-red-400 rounded-lg shadow-lg p-4" role="alert">
            <div class="flex justify-between items-start">
                <div>
                    <p class="font-bold text-red-800 dark:text-red-200 mb-2">🚫 กรุณาแก้ไขข้อผิดพลาดต่อไปนี้:</p>
                    <ul class="mt-2 space-y-1 list-disc list-inside text-red-700 dark:text-red-300">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                <button @click="show = false" class="text-red-500 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    @endif

    {{-- Header Section พร้อม Gradient และ Animation --}}
    <div class="relative overflow-hidden bg-gradient-to-br from-orange-500 to-pink-600 dark:from-orange-600 dark:to-pink-700 rounded-2xl shadow-2xl p-8">
        {{-- Background Pattern --}}
        <div class="absolute inset-0 bg-black/10 dark:bg-black/20" style="background-image: radial-gradient(circle at 1px 1px, rgba(255,255,255,0.15) 1px, transparent 0); background-size: 40px 40px;"></div>

        <div class="relative flex flex-col md:flex-row md:justify-between md:items-center gap-4">
            <div class="flex items-center gap-4">
                <div class="bg-white/20 dark:bg-white/10 backdrop-blur-sm rounded-2xl p-4">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <div class="text-white">
                    <h1 class="text-3xl md:text-4xl font-bold mb-1">จัดการสินค้า</h1>
                    <p class="text-white/90 text-sm md:text-base">จัดการและติดตามสินค้าทั้งหมดในระบบ</p>
                </div>
            </div>
            <button onclick="document.getElementById('createProductModal').classList.remove('hidden')" class="group relative bg-white dark:bg-gray-800 text-orange-600 dark:text-orange-400 px-6 py-3 rounded-xl hover:shadow-2xl transition-all duration-300 transform hover:scale-105 flex items-center gap-2 font-semibold">
                <svg class="w-5 h-5 transition-transform group-hover:rotate-90 duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>เพิ่มสินค้าใหม่</span>
                <div class="absolute inset-0 bg-gradient-to-r from-orange-400 to-pink-400 rounded-xl opacity-0 group-hover:opacity-20 transition-opacity"></div>
            </button>
        </div>
    </div>

    {{-- Filters Section พร้อม Icons และ Better UX --}}
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-gray-100 dark:border-gray-700 p-6 hover:shadow-xl transition-shadow duration-300">
        <div class="flex items-center gap-2 mb-4">
            <svg class="w-5 h-5 text-orange-500 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
            </svg>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">ตัวกรองการค้นหา</h3>
        </div>

        <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            {{-- Search Input --}}
            <div class="relative lg:col-span-2">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="ค้นหาสินค้า, SKU..." class="w-full pl-10 pr-4 py-2.5 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white focus:ring-2 focus:ring-orange-500 dark:focus:ring-orange-400 focus:border-transparent transition">
            </div>

            {{-- Category Select --}}
            <div class="relative">
                <select name="category" class="w-full px-4 py-2.5 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white focus:ring-2 focus:ring-orange-500 dark:focus:ring-orange-400 focus:border-transparent transition appearance-none">
                    <option value="">📁 ทุกหมวดหมู่</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </div>
            </div>

            {{-- Status Select --}}
            <div class="relative">
                <select name="status" class="w-full px-4 py-2.5 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white focus:ring-2 focus:ring-orange-500 dark:focus:ring-orange-400 focus:border-transparent transition appearance-none">
                    <option value="">🔄 ทุกสถานะ</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>✅ ใช้งาน</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>❌ ไม่ใช้งาน</option>
                </select>
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="flex gap-2">
                <button type="submit" class="flex-1 bg-gradient-to-r from-orange-500 to-pink-500 hover:from-orange-600 hover:to-pink-600 text-white px-6 py-2.5 rounded-lg font-medium transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <span class="hidden sm:inline">ค้นหา</span>
                </button>
                @if(request()->hasAny(['search', 'category', 'status']))
                    <a href="{{ route('admin.ecommerce.products.index') }}" class="flex items-center justify-center px-4 py-2.5 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg font-medium transition-all duration-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Products Table พร้อม Enhanced Design --}}
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden hover:shadow-2xl transition-shadow duration-300">
        {{-- Table Header --}}
        <div class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-slate-700 dark:to-slate-800 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-orange-500 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">รายการสินค้า</h3>
                    <span class="ml-2 px-2.5 py-0.5 text-xs font-semibold rounded-full bg-orange-100 dark:bg-orange-900 text-orange-800 dark:text-orange-200">
                        {{ $products->total() }} รายการ
                    </span>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-slate-700/50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                                สินค้า
                            </div>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">หมวดหมู่</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">ราคา</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">สต็อก</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">สถานะ</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">การกระทำ</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($products as $product)
                        <tr class="group hover:bg-gradient-to-r hover:from-orange-50 hover:to-pink-50 dark:hover:from-orange-900/10 dark:hover:to-pink-900/10 transition-all duration-200">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    @if($product->primary_image)
                                        <div class="relative">
                                            <img src="{{ $product->primary_image }}" alt="{{ $product->name }}" class="w-14 h-14 rounded-lg object-cover ring-2 ring-gray-200 dark:ring-gray-700 group-hover:ring-orange-400 dark:group-hover:ring-orange-500 transition-all duration-300">
                                            <div class="absolute inset-0 rounded-lg bg-gradient-to-tr from-transparent to-white/20 dark:to-white/10"></div>
                                        </div>
                                    @else
                                        <div class="w-14 h-14 bg-gradient-to-br from-gray-200 to-gray-300 dark:from-gray-700 dark:to-gray-800 rounded-lg flex items-center justify-center ring-2 ring-gray-200 dark:ring-gray-700 group-hover:ring-orange-400 dark:group-hover:ring-orange-500 transition-all duration-300">
                                            <svg class="w-7 h-7 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                            </svg>
                                        </div>
                                    @endif
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-semibold text-gray-900 dark:text-white truncate group-hover:text-orange-600 dark:group-hover:text-orange-400 transition-colors">
                                            {{ $product->name }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                            <span class="font-mono">SKU: {{ $product->sku }}</span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 text-xs font-medium">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                    </svg>
                                    {{ $product->category->name ?? 'ไม่ระบุ' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="text-base font-bold text-gray-900 dark:text-white">฿{{ number_format($product->price, 2) }}</span>
                                    @if($product->compare_at_price && $product->compare_at_price > $product->price)
                                        <span class="text-xs text-gray-500 dark:text-gray-400 line-through">฿{{ number_format($product->compare_at_price, 2) }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    @if($product->stock_quantity <= 0)
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300 text-sm font-semibold">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                            หมด
                                        </span>
                                    @elseif($product->stock_status == 'low_stock')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300 text-sm font-semibold">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                            </svg>
                                            {{ $product->stock_quantity }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 text-sm font-semibold">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            {{ $product->stock_quantity }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @if($product->is_active)
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-full bg-gradient-to-r from-green-100 to-green-200 dark:from-green-900 dark:to-green-800 text-green-800 dark:text-green-200 shadow-sm">
                                        <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                                        ใช้งาน
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 shadow-sm">
                                        <span class="w-2 h-2 bg-gray-400 rounded-full"></span>
                                        ปิดใช้งาน
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.ecommerce.products.show', $product) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-100 hover:bg-blue-200 dark:bg-blue-900/30 dark:hover:bg-blue-900/50 text-blue-700 dark:text-blue-300 rounded-lg text-sm font-medium transition-all duration-200 hover:shadow-md" title="ดูรายละเอียด">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        <span class="hidden sm:inline">ดู</span>
                                    </a>
                                    <a href="{{ route('admin.ecommerce.products.edit', $product) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-orange-100 hover:bg-orange-200 dark:bg-orange-900/30 dark:hover:bg-orange-900/50 text-orange-700 dark:text-orange-300 rounded-lg text-sm font-medium transition-all duration-200 hover:shadow-md" title="แก้ไข">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                        <span class="hidden sm:inline">แก้ไข</span>
                                    </a>
                                    <form action="{{ route('admin.ecommerce.products.delete', $product) }}" method="POST" class="inline" onsubmit="return confirm('⚠️ คุณแน่ใจหรือไม่ที่จะลบสินค้า &quot;{{ $product->name }}&quot;?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-100 hover:bg-red-200 dark:bg-red-900/30 dark:hover:bg-red-900/50 text-red-700 dark:text-red-300 rounded-lg text-sm font-medium transition-all duration-200 hover:shadow-md" title="ลบ">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                            <span class="hidden sm:inline">ลบ</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-20 h-20 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                                        <svg class="w-10 h-10 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                        </svg>
                                    </div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">ไม่พบสินค้า</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">ยังไม่มีสินค้าในระบบหรือไม่พบผลลัพธ์ที่ตรงกับการค้นหา</p>
                                    <button onclick="document.getElementById('createProductModal').classList.remove('hidden')" class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-orange-500 to-pink-500 hover:from-orange-600 hover:to-pink-600 text-white rounded-lg font-medium transition-all duration-300 transform hover:scale-105">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                        </svg>
                                        เพิ่มสินค้าแรก
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($products->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-slate-700/50">
                {{ $products->links() }}
            </div>
        @endif
    </div>
</div>

{{-- Create Product Modal พร้อม Enhanced Design --}}
<div id="createProductModal" class="hidden fixed inset-0 bg-black/70 dark:bg-black/80 backdrop-blur-sm flex items-center justify-center z-50 p-4 overflow-y-auto" x-data="{
    isUploading: false,
    handleSubmit(event) {
        this.isUploading = true;
    }
}" @click.self="$el.classList.add('hidden')">
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-3xl mx-auto my-8 overflow-hidden transform transition-all" @click.away="false">
        {{-- Modal Header --}}
        <div class="relative bg-gradient-to-r from-orange-500 to-pink-600 dark:from-orange-600 dark:to-pink-700 px-8 py-6">
            {{-- Background Pattern --}}
            <div class="absolute inset-0 bg-black/10 dark:bg-black/20" style="background-image: radial-gradient(circle at 1px 1px, rgba(255,255,255,0.15) 1px, transparent 0); background-size: 40px 40px;"></div>

            <div class="relative flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="bg-white/20 dark:bg-white/10 backdrop-blur-sm rounded-xl p-3">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                    </div>
                    <div class="text-white">
                        <h2 class="text-2xl font-bold">เพิ่มสินค้าใหม่</h2>
                        <p class="text-white/90 text-sm">กรอกข้อมูลสินค้าที่ต้องการเพิ่มลงในระบบ</p>
                    </div>
                </div>
                <button onclick="document.getElementById('createProductModal').classList.add('hidden')" class="bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white rounded-lg p-2 transition-all duration-200 hover:rotate-90 transform">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
        {{-- Modal Form --}}
        <form action="{{ route('admin.ecommerce.products.store') }}" method="POST" enctype="multipart/form-data" @submit="handleSubmit" class="flex flex-col h-full">
            @csrf
            <div class="px-8 py-6 space-y-5 max-h-[65vh] overflow-y-auto scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600">
                {{-- ชื่อสินค้า --}}
                <div>
                    <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                        </svg>
                        ชื่อสินค้า <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" value="{{ old('name') }}" required placeholder="กรอกชื่อสินค้า..." class="w-full px-4 py-2.5 rounded-lg border-2 border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white focus:border-orange-500 dark:focus:border-orange-400 focus:ring-2 focus:ring-orange-200 dark:focus:ring-orange-900/30 transition-all">
                    @error('name')
                        <p class="text-red-500 dark:text-red-400 text-sm mt-1 flex items-center gap-1">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- หมวดหมู่ --}}
                    <div>
                        <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                            </svg>
                            หมวดหมู่ <span class="text-red-500">*</span>
                        </label>
                        <select name="category_id" required class="w-full px-4 py-2.5 rounded-lg border-2 border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white focus:border-orange-500 dark:focus:border-orange-400 focus:ring-2 focus:ring-orange-200 dark:focus:ring-orange-900/30 transition-all">
                            <option value="">เลือกหมวดหมู่</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <p class="text-red-500 dark:text-red-400 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- SKU --}}
                    <div>
                        <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                            </svg>
                            SKU
                        </label>
                        <input type="text" name="sku" value="{{ old('sku') }}" placeholder="รหัสสินค้า (ถ้ามี)" class="w-full px-4 py-2.5 rounded-lg border-2 border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white focus:border-orange-500 dark:focus:border-orange-400 focus:ring-2 focus:ring-orange-200 dark:focus:ring-orange-900/30 transition-all font-mono">
                        @error('sku')
                            <p class="text-red-500 dark:text-red-400 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- คำอธิบาย --}}
                <div>
                    <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/>
                        </svg>
                        คำอธิบายสินค้า
                    </label>
                    <textarea name="description" rows="3" placeholder="รายละเอียดสินค้า..." class="w-full px-4 py-2.5 rounded-lg border-2 border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white focus:border-orange-500 dark:focus:border-orange-400 focus:ring-2 focus:ring-orange-200 dark:focus:ring-orange-900/30 transition-all resize-none">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="text-red-500 dark:text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- ราคา --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            ราคาขาย <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 font-semibold">฿</span>
                            <input type="number" name="price" step="0.01" value="{{ old('price') }}" required placeholder="0.00" class="w-full pl-10 pr-4 py-2.5 rounded-lg border-2 border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white focus:border-orange-500 dark:focus:border-orange-400 focus:ring-2 focus:ring-orange-200 dark:focus:ring-orange-900/30 transition-all">
                        </div>
                        @error('price')
                            <p class="text-red-500 dark:text-red-400 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                            </svg>
                            ราคาเปรียบเทียบ
                        </label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 font-semibold">฿</span>
                            <input type="number" name="compare_at_price" step="0.01" value="{{ old('compare_at_price') }}" placeholder="0.00" class="w-full pl-10 pr-4 py-2.5 rounded-lg border-2 border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white focus:border-orange-500 dark:focus:border-orange-400 focus:ring-2 focus:ring-orange-200 dark:focus:ring-orange-900/30 transition-all">
                        </div>
                    </div>
                </div>

                {{-- สต็อกและคอมมิชชั่น --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                            จำนวนสต็อก
                        </label>
                        <input type="number" name="stock_quantity" value="{{ old('stock_quantity', 0) }}" min="0" placeholder="0" class="w-full px-4 py-2.5 rounded-lg border-2 border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white focus:border-orange-500 dark:focus:border-orange-400 focus:ring-2 focus:ring-orange-200 dark:focus:ring-orange-900/30 transition-all">
                    </div>

                    <div>
                        <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                            % คอมมิชชั่น
                        </label>
                        <div class="relative">
                            <input type="number" name="commission_rate" step="0.01" value="{{ old('commission_rate', 15) }}" min="0" max="100" placeholder="15" class="w-full pl-4 pr-10 py-2.5 rounded-lg border-2 border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white focus:border-orange-500 dark:focus:border-orange-400 focus:ring-2 focus:ring-orange-200 dark:focus:ring-orange-900/30 transition-all">
                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 font-semibold">%</span>
                        </div>
                    </div>
                </div>

                {{-- รูปภาพ - Advanced Upload UI with Alpine.js --}}
                <div x-data="{
                    mainImage: null,
                    mainImagePreview: null,
                    galleryImages: [],
                    maxGalleryImages: 10,

                    // Handle main image selection
                    handleMainImage(event) {
                        const file = event.target.files[0];
                        if (file && this.validateImage(file)) {
                            this.mainImage = file;
                            this.mainImagePreview = URL.createObjectURL(file);
                        }
                    },

                    // Handle main image drop
                    handleMainDrop(event) {
                        event.preventDefault();
                        const file = event.dataTransfer.files[0];
                        if (file && this.validateImage(file)) {
                            this.mainImage = file;
                            this.mainImagePreview = URL.createObjectURL(file);
                            // Update input file
                            const dataTransfer = new DataTransfer();
                            dataTransfer.items.add(file);
                            this.$refs.mainImageInput.files = dataTransfer.files;
                        }
                    },

                    // Handle gallery images
                    handleGalleryImages(event) {
                        const files = Array.from(event.target.files);
                        this.addGalleryImages(files);
                    },

                    // Handle gallery drop
                    handleGalleryDrop(event) {
                        event.preventDefault();
                        const files = Array.from(event.dataTransfer.files);
                        this.addGalleryImages(files);
                    },

                    // Add gallery images
                    addGalleryImages(files) {
                        files.forEach(file => {
                            if (this.galleryImages.length < this.maxGalleryImages && this.validateImage(file)) {
                                this.galleryImages.push({
                                    file: file,
                                    preview: URL.createObjectURL(file),
                                    name: file.name,
                                    size: this.formatFileSize(file.size)
                                });
                            }
                        });
                        this.updateGalleryInput();
                    },

                    // Remove gallery image
                    removeGalleryImage(index) {
                        URL.revokeObjectURL(this.galleryImages[index].preview);
                        this.galleryImages.splice(index, 1);
                        this.updateGalleryInput();
                    },

                    // Update hidden input for gallery
                    updateGalleryInput() {
                        const dataTransfer = new DataTransfer();
                        this.galleryImages.forEach(img => dataTransfer.items.add(img.file));
                        this.$refs.galleryInput.files = dataTransfer.files;
                    },

                    // Remove main image
                    removeMainImage() {
                        if (this.mainImagePreview) {
                            URL.revokeObjectURL(this.mainImagePreview);
                        }
                        this.mainImage = null;
                        this.mainImagePreview = null;
                        this.$refs.mainImageInput.value = '';
                    },

                    // Validate image
                    validateImage(file) {
                        const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                        const maxSize = 5 * 1024 * 1024; // 5MB

                        if (!validTypes.includes(file.type)) {
                            alert('❌ รองรับเฉพาะไฟล์ JPG, PNG, GIF, WebP เท่านั้น');
                            return false;
                        }

                        if (file.size > maxSize) {
                            alert('❌ ขนาดไฟล์ต้องไม่เกิน 5MB');
                            return false;
                        }

                        return true;
                    },

                    // Format file size
                    formatFileSize(bytes) {
                        if (bytes === 0) return '0 Bytes';
                        const k = 1024;
                        const sizes = ['Bytes', 'KB', 'MB'];
                        const i = Math.floor(Math.log(bytes) / Math.log(k));
                        return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
                    }
                }" class="space-y-6">

                    {{-- รูปภาพหลัก (Main Image) --}}
                    <div class="space-y-3">
                        <label class="flex items-center gap-2 text-sm font-bold text-gray-900 dark:text-white">
                            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            รูปภาพหลัก
                            <span class="ml-auto text-xs px-2 py-0.5 bg-gradient-to-r from-green-100 to-emerald-100 dark:from-green-900 dark:to-emerald-900 text-green-700 dark:text-green-300 rounded-full font-semibold">
                                🚀 แปลง WebP อัตโนมัติ
                            </span>
                        </label>

                        <div class="relative">
                            {{-- Preview Area --}}
                            <div x-show="mainImagePreview" x-transition class="mb-4">
                                <div class="relative group bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-2xl p-4 border-2 border-blue-200 dark:border-blue-800">
                                    <img :src="mainImagePreview" class="w-full h-64 object-contain rounded-xl">
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-all duration-300 rounded-2xl flex items-end p-4">
                                        <div class="w-full flex items-center justify-between">
                                            <div class="text-white">
                                                <p class="text-sm font-semibold" x-text="mainImage?.name"></p>
                                                <p class="text-xs opacity-90" x-text="mainImage ? formatFileSize(mainImage.size) : ''"></p>
                                            </div>
                                            <button type="button" @click="removeMainImage()" class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg font-medium transition-all transform hover:scale-105 flex items-center gap-2">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                                ลบ
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Drop Zone --}}
                            <div x-show="!mainImagePreview"
                                @dragover.prevent
                                @drop="handleMainDrop($event)"
                                @click="$refs.mainImageInput.click()"
                                class="relative cursor-pointer group">
                                <div class="border-3 border-dashed border-blue-300 dark:border-blue-700 hover:border-blue-500 dark:hover:border-blue-500 rounded-2xl p-8 text-center bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/10 dark:to-indigo-900/10 hover:from-blue-100 hover:to-indigo-100 dark:hover:from-blue-900/20 dark:hover:to-indigo-900/20 transition-all duration-300">
                                    <div class="space-y-4">
                                        <div class="mx-auto w-20 h-20 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl flex items-center justify-center group-hover:scale-110 transition-transform duration-300 shadow-lg">
                                            <svg class="w-10 h-10 text-white animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-lg font-bold text-gray-900 dark:text-white mb-1">
                                                ลากไฟล์มาวางที่นี่ หรือคลิกเพื่อเลือก
                                            </p>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                                รองรับ JPG, PNG, GIF, WebP (สูงสุด 5MB)
                                            </p>
                                        </div>
                                        <div class="flex items-center justify-center gap-2 text-xs">
                                            <span class="px-3 py-1 bg-white dark:bg-gray-800 rounded-full text-gray-700 dark:text-gray-300 font-medium shadow-sm">📸 ขนาด 1200x1200px</span>
                                            <span class="px-3 py-1 bg-white dark:bg-gray-800 rounded-full text-gray-700 dark:text-gray-300 font-medium shadow-sm">⚡ คุณภาพ 90%</span>
                                            <span class="px-3 py-1 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-full font-semibold shadow-sm">🎯 WebP</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <input type="file"
                                   name="main_image"
                                   accept="image/*"
                                   x-ref="mainImageInput"
                                   @change="handleMainImage($event)"
                                   class="hidden">
                        </div>
                    </div>

                    {{-- รูปภาพเพิ่มเติม (Gallery) --}}
                    <div class="space-y-3">
                        <label class="flex items-center gap-2 text-sm font-bold text-gray-900 dark:text-white">
                            <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            รูปภาพแกลเลอรี
                            <span class="ml-auto flex items-center gap-2">
                                <span x-text="`${galleryImages.length}/${maxGalleryImages}`" class="text-xs px-2 py-0.5 bg-purple-100 dark:bg-purple-900 text-purple-700 dark:text-purple-300 rounded-full font-semibold"></span>
                                <span class="text-xs px-2 py-0.5 bg-gradient-to-r from-green-100 to-emerald-100 dark:from-green-900 dark:to-emerald-900 text-green-700 dark:text-green-300 rounded-full font-semibold">
                                    🚀 แปลง WebP อัตโนมัติ
                                </span>
                            </span>
                        </label>

                        {{-- Gallery Previews --}}
                        <div x-show="galleryImages.length > 0" x-transition class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3 mb-4">
                            <template x-for="(image, index) in galleryImages" :key="index">
                                <div class="relative group bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl overflow-hidden border-2 border-purple-200 dark:border-purple-800 hover:border-purple-400 dark:hover:border-purple-600 transition-all">
                                    <img :src="image.preview" class="w-full h-32 object-cover">
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent opacity-0 group-hover:opacity-100 transition-all duration-300 flex flex-col justify-end p-2">
                                        <p class="text-white text-xs font-semibold truncate" x-text="image.name"></p>
                                        <p class="text-white/80 text-xs mb-2" x-text="image.size"></p>
                                        <button type="button" @click="removeGalleryImage(index)" class="w-full px-2 py-1.5 bg-red-500 hover:bg-red-600 text-white text-xs rounded-lg font-medium transition-all flex items-center justify-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                            ลบ
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>

                        {{-- Drop Zone --}}
                        <div x-show="galleryImages.length < maxGalleryImages"
                            @dragover.prevent
                            @drop="handleGalleryDrop($event)"
                            @click="$refs.galleryInput.click()"
                            class="relative cursor-pointer group">
                            <div class="border-3 border-dashed border-purple-300 dark:border-purple-700 hover:border-purple-500 dark:hover:border-purple-500 rounded-2xl p-6 text-center bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/10 dark:to-pink-900/10 hover:from-purple-100 hover:to-pink-100 dark:hover:from-purple-900/20 dark:hover:to-pink-900/20 transition-all duration-300">
                                <div class="space-y-3">
                                    <div class="mx-auto w-16 h-16 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-300 shadow-lg">
                                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-base font-bold text-gray-900 dark:text-white mb-1">
                                            เพิ่มรูปภาพแกลเลอรี
                                        </p>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            เลือกหลายไฟล์พร้อมกัน (สูงสุด <span x-text="maxGalleryImages - galleryImages.length"></span> ภาพ)
                                        </p>
                                    </div>
                                    <div class="flex items-center justify-center gap-2 text-xs">
                                        <span class="px-2 py-1 bg-white dark:bg-gray-800 rounded-full text-gray-700 dark:text-gray-300 font-medium shadow-sm">📸 1200x1200px</span>
                                        <span class="px-2 py-1 bg-white dark:bg-gray-800 rounded-full text-gray-700 dark:text-gray-300 font-medium shadow-sm">⚡ คุณภาพ 85%</span>
                                        <span class="px-2 py-1 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-full font-semibold shadow-sm">🎯 WebP</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <input type="file"
                               name="images[]"
                               accept="image/*"
                               multiple
                               x-ref="galleryInput"
                               @change="handleGalleryImages($event)"
                               class="hidden">

                        {{-- Max limit message --}}
                        <div x-show="galleryImages.length >= maxGalleryImages" x-transition class="p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg flex items-center gap-2 text-sm text-yellow-800 dark:text-yellow-300">
                            <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                            <span class="font-medium">ถึงจำนวนสูงสุดแล้ว (10 ภาพ) กรุณาลบภาพบางภาพก่อนเพิ่มภาพใหม่</span>
                        </div>
                    </div>

                    {{-- Info Box --}}
                    <div class="p-4 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/10 dark:to-emerald-900/10 border border-green-200 dark:border-green-800 rounded-xl">
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h4 class="text-sm font-bold text-green-900 dark:text-green-100 mb-1">🚀 ระบบแปลง WebP อัตโนมัติ</h4>
                                <ul class="text-xs text-green-800 dark:text-green-200 space-y-1">
                                    <li class="flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                        <span>รูปทุกรูปจะถูกแปลงเป็น <strong>WebP</strong> โดยอัตโนมัติ เพื่อลดขนาดไฟล์ 30-50%</span>
                                    </li>
                                    <li class="flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                        <span>ปรับขนาดอัตโนมัติเป็น <strong>1200x1200px</strong> (รักษาอัตราส่วน)</span>
                                    </li>
                                    <li class="flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                        <span>คุณภาพสูง: รูปหลัก <strong>90%</strong> | แกลเลอรี <strong>85%</strong></span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ตัวเลือก --}}
                <div class="flex flex-wrap gap-4 p-4 bg-gray-50 dark:bg-slate-700/50 rounded-lg">
                    <label class="inline-flex items-center gap-2 cursor-pointer group">
                        <input type="checkbox" name="is_active" value="1" checked class="w-5 h-5 rounded border-2 border-gray-300 dark:border-gray-600 text-orange-600 focus:ring-2 focus:ring-orange-500 dark:focus:ring-orange-400 transition-all">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-orange-600 dark:group-hover:text-orange-400 transition-colors">✅ ใช้งาน</span>
                    </label>
                    <label class="inline-flex items-center gap-2 cursor-pointer group">
                        <input type="checkbox" name="is_featured" value="1" class="w-5 h-5 rounded border-2 border-gray-300 dark:border-gray-600 text-orange-600 focus:ring-2 focus:ring-orange-500 dark:focus:ring-orange-400 transition-all">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-orange-600 dark:group-hover:text-orange-400 transition-colors">⭐ สินค้าแนะนำ</span>
                    </label>
                    <label class="inline-flex items-center gap-2 cursor-pointer group">
                        <input type="checkbox" name="track_inventory" value="1" class="w-5 h-5 rounded border-2 border-gray-300 dark:border-gray-600 text-orange-600 focus:ring-2 focus:ring-orange-500 dark:focus:ring-orange-400 transition-all">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-orange-600 dark:group-hover:text-orange-400 transition-colors">📊 ติดตามสต็อก</span>
                    </label>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="px-8 py-6 bg-gray-50 dark:bg-slate-700/50 border-t border-gray-200 dark:border-gray-700 flex items-center justify-end gap-3">
                <button type="button" onclick="document.getElementById('createProductModal').classList.add('hidden')" class="px-6 py-2.5 bg-white dark:bg-slate-700 hover:bg-gray-100 dark:hover:bg-slate-600 text-gray-700 dark:text-gray-300 border-2 border-gray-300 dark:border-gray-600 rounded-lg font-medium transition-all duration-200 transform hover:scale-105">
                    ยกเลิก
                </button>
                <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-orange-500 to-pink-500 hover:from-orange-600 hover:to-pink-600 text-white rounded-lg font-semibold transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    บันทึกสินค้า
                </button>
            </div>
        </form>
    </div>

    {{-- Loading Overlay --}}
    <div x-show="isUploading" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="fixed inset-0 bg-black/80 backdrop-blur-sm flex items-center justify-center z-[60]" style="display: none;">
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-8 max-w-sm mx-4 text-center shadow-2xl border-4 border-orange-500 dark:border-orange-400">
            <div class="mb-6">
                <div class="relative inline-block">
                    <svg class="animate-spin h-20 w-20 text-orange-600 dark:text-orange-400 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <svg class="w-10 h-10 text-orange-500 dark:text-orange-300 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                </div>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">กำลังบันทึกสินค้า...</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-1">ระบบกำลังอัพโหลดภาพและบันทึกข้อมูล</p>
            <p class="text-sm text-orange-600 dark:text-orange-400 font-semibold">กรุณารอสักครู่ ⏳</p>
        </div>
    </div>
</div>

@if($errors->any() || old('name'))
<script>
    // เปิด modal อัตโนมัติเมื่อมี validation errors
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('createProductModal').classList.remove('hidden');
    });
</script>
@endif
@endsection
