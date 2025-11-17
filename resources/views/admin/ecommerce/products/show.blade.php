{{--
  * หน้ารายละเอียดสินค้า (Product Details)
  * แสดงข้อมูลสินค้าแบบละเอียด พร้อมสถิติและการจัดการ
  * รองรับ Dark Mode และ Responsive Design
  --}}

@extends('layouts.admin-v3')

@section('title', 'รายละเอียดสินค้า')

@section('content')
<div class="space-y-6" x-data="{ language: 'th' }">
    {{-- Alert Messages --}}
    @if(session('success'))
        <div x-data="{ show: true }" x-show="show" x-transition class="bg-gradient-to-r from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 border-l-4 border-green-500 dark:border-green-400 rounded-xl shadow-lg p-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <svg class="w-6 h-6 text-green-500 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
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

    {{-- Header Section --}}
    <div class="relative overflow-hidden bg-gradient-to-br from-blue-500 to-purple-600 dark:from-blue-600 dark:to-purple-700 rounded-2xl shadow-2xl p-8">
        {{-- Background Pattern --}}
        <div class="absolute inset-0 bg-black/10 dark:bg-black/20" style="background-image: radial-gradient(circle at 1px 1px, rgba(255,255,255,0.15) 1px, transparent 0); background-size: 40px 40px;"></div>

        <div class="relative flex flex-col md:flex-row md:justify-between md:items-center gap-4">
            <div class="flex items-center gap-4">
                <div class="glass-fusion dark:glass-fusion backdrop-blur-sm rounded-2xl p-4" border border-white/20 dark:border-white/10>
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <div class="text-white">
                    <h1 class="text-3xl md:text-4xl font-bold mb-1" data-translate>รายละเอียดสินค้า</h1>
                    <p class="text-white/90 text-sm md:text-base" data-translate>ข้อมูลสินค้าและสถิติการขาย</p>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.ecommerce.products.edit', $product) }}" class="group relative glass-fusion dark:bg-gray-800 text-blue-600 dark:text-blue-400 px-6 py-3 rounded-xl hover:shadow-2xl transition-all duration-300 transform hover:scale-105 flex items-center gap-2 font-semibold">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    <span data-translate>แก้ไข</span>
                    <div class="absolute inset-0 bg-gradient-to-r from-blue-400 to-purple-400 rounded-xl opacity-0 group-hover:opacity-20 transition-opacity"></div>
                </a>
                <a href="{{ route('admin.ecommerce.products.index') }}" class="glass-fusion hover:glass-fusion backdrop-blur-sm text-white px-6 py-3 rounded-xl transition-all duration-200 flex items-center gap-2 font-semibold">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span data-translate>กลับ</span>
                </a>

                {{-- Language Switcher --}}
                <div class="relative inline-block" x-data="{ open: false }">
                    <button @click="open = !open" class="px-4 py-2 glass-fusion backdrop-blur-sm text-white rounded-xl hover:glass-fusion transition-all duration-200 border border-white/30 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                        </svg>
                        <span data-translate>ภาษา</span>
                    </button>

                    <div x-show="open" @click.away="open = false" x-transition
                         class="absolute right-0 mt-2 w-48 glass-fusion dark:bg-slate-800 rounded-xl shadow-2xl border border-gray-200 dark:border-gray-700 dark:border-slate-700 overflow-hidden z-50" border border-white/20 dark:border-white/10>
                        <a href="#" @click.prevent="language = 'th'" class="block px-4 py-3 hover:bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:hover:bg-slate-700 transition-colors">
                            <span class="mr-2">🇹🇭</span> <span data-translate>ไทย</span>
                        </a>
                        <a href="#" @click.prevent="language = 'en'" class="block px-4 py-3 hover:bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:hover:bg-slate-700 transition-colors">
                            <span class="mr-2">🇬🇧</span> English
                        </a>
                        <a href="#" @click.prevent="language = 'zh'" class="block px-4 py-3 hover:bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:hover:bg-slate-700 transition-colors">
                            <span class="mr-2">🇨🇳</span> 中文
                        </a>
                        <a href="#" @click.prevent="language = 'ja'" class="block px-4 py-3 hover:bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:hover:bg-slate-700 transition-colors">
                            <span class="mr-2">🇯🇵</span> 日本語
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Product Info Card --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Product Details --}}
            <div class="glass-fusion dark:bg-slate-800 rounded-xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden hover:shadow-2xl transition-shadow duration-300" border border-white/20 dark:border-white/10>
                <div class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-slate-700 dark:to-slate-800 px-6 py-4 border-b border-gray-200 dark:border-gray-700 dark:border-gray-700">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-500 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white" data-translate>ข้อมูลสินค้า</h3>
                    </div>
                </div>

                <div class="p-6">
                    <div class="flex flex-col md:flex-row gap-6">
                        {{-- Product Image --}}
                        <div class="flex-shrink-0">
                            @if($product->primary_image)
                                <div class="relative group">
                                    <img src="{{ $product->primary_image }}" alt="{{ $product->name }}" class="w-full md:w-48 h-48 object-cover rounded-xl shadow-lg ring-2 ring-gray-200 dark:ring-gray-700 group-hover:ring-blue-400 dark:group-hover:ring-blue-500 transition-all duration-300">
                                    <div class="absolute inset-0 rounded-xl bg-gradient-to-tr from-transparent to-white/20 dark:to-white/10"></div>
                                </div>
                            @else
                                <div class="w-full md:w-48 h-48 bg-gradient-to-br from-gray-200 to-gray-300 dark:from-gray-700 dark:to-gray-800 rounded-xl shadow-lg flex items-center justify-center">
                                    <svg class="w-20 h-20 text-gray-400 dark:text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                </div>
                            @endif
                        </div>

                        {{-- Product Info --}}
                        <div class="flex-1 space-y-4">
                            <div>
                                <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">{{ $product->name }}</h2>
                                @if($product->is_active)
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-full bg-gradient-to-r from-green-100 to-green-200 dark:from-green-900 dark:to-green-800 text-green-800 dark:text-green-200 shadow-sm">
                                        <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                                        <span data-translate>ใช้งาน</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-full bg-gray-100/50 dark:bg-gray-800/50 dark:bg-gray-800 text-gray-600 dark:text-gray-400 dark:text-gray-400 shadow-sm">
                                        <span class="w-2 h-2 bg-gray-400 rounded-full"></span>
                                        <span data-translate>ปิดใช้งาน</span>
                                    </span>
                                @endif
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="flex items-start gap-3 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                                    <svg class="w-5 h-5 text-blue-500 dark:text-blue-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                                    </svg>
                                    <div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 mb-0.5">SKU</p>
                                        <p class="font-mono font-semibold text-gray-900 dark:text-white">{{ $product->sku }}</p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3 p-3 bg-purple-50 dark:bg-purple-900/20 rounded-xl">
                                    <svg class="w-5 h-5 text-purple-500 dark:text-purple-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                    </svg>
                                    <div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 mb-0.5" data-translate>หมวดหมู่</p>
                                        <p class="font-semibold text-gray-900 dark:text-white">{{ $product->category->name ?? 'ไม่ระบุ' }}</p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3 p-3 bg-green-50 dark:bg-green-900/20 rounded-xl">
                                    <svg class="w-5 h-5 text-green-500 dark:text-green-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 mb-0.5" data-translate>ราคาขาย</p>
                                        <div class="flex items-baseline gap-2">
                                            <p class="text-2xl font-bold text-gray-900 dark:text-white">฿{{ number_format($product->price, 2) }}</p>
                                            @if($product->compare_at_price && $product->compare_at_price > $product->price)
                                                <span class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400 line-through">฿{{ number_format($product->compare_at_price, 2) }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3 p-3 bg-orange-50 dark:bg-orange-900/20 rounded-xl">
                                    <svg class="w-5 h-5 text-orange-500 dark:text-orange-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                    <div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 mb-0.5" data-translate>สต็อก</p>
                                        <p class="text-xl font-bold {{ $product->stock_quantity <= 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">
                                            {{ number_format($product->stock_quantity) }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3 p-3 bg-pink-50 dark:bg-pink-900/20 rounded-xl">
                                    <svg class="w-5 h-5 text-pink-500 dark:text-pink-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                    </svg>
                                    <div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 mb-0.5" data-translate>คอมมิชชั่น</p>
                                        <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $product->commission_rate }}%</p>
                                    </div>
                                </div>
                            </div>

                            @if($product->description)
                                <div class="pt-4 border-t border-gray-200 dark:border-gray-700 dark:border-gray-700">
                                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2" data-translate>📝 คำอธิบาย</h4>
                                    <p class="text-gray-600 dark:text-gray-400 dark:text-gray-400 leading-relaxed">{{ $product->description }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Statistics Grid --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="group style="background: var(--arrow-x-primary-gradient)" dark:from-blue-600 dark:to-blue-700 rounded-xl shadow-lg p-5 hover:shadow-2xl transition-all duration-300 transform hover:scale-105">
                    <div class="flex items-center justify-between mb-3">
                        <div class="glass-fusion backdrop-blur-sm rounded-xl p-2" border border-white/20 dark:border-white/10>
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-white/80 text-sm mb-1" data-translate>ยอดขาย</p>
                    <p class="text-3xl font-bold text-white">{{ number_format($stats['total_sales'] ?? 0) }}</p>
                    <p class="text-white/70 text-xs mt-1" data-translate>ชิ้น</p>
                </div>

                <div class="group bg-gradient-to-br from-green-500 to-green-600 dark:from-green-600 dark:to-green-700 rounded-xl shadow-lg p-5 hover:shadow-2xl transition-all duration-300 transform hover:scale-105">
                    <div class="flex items-center justify-between mb-3">
                        <div class="glass-fusion backdrop-blur-sm rounded-xl p-2" border border-white/20 dark:border-white/10>
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-white/80 text-sm mb-1" data-translate>รายได้</p>
                    <p class="text-3xl font-bold text-white">฿{{ number_format($stats['total_revenue'] ?? 0) }}</p>
                    <p class="text-white/70 text-xs mt-1" data-translate>บาท</p>
                </div>

                <div class="group bg-gradient-to-br from-yellow-500 to-orange-600 dark:from-yellow-600 dark:to-orange-700 rounded-xl shadow-lg p-5 hover:shadow-2xl transition-all duration-300 transform hover:scale-105">
                    <div class="flex items-center justify-between mb-3">
                        <div class="glass-fusion backdrop-blur-sm rounded-xl p-2" border border-white/20 dark:border-white/10>
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-white/80 text-sm mb-1" data-translate>รีวิว</p>
                    <p class="text-3xl font-bold text-white">{{ number_format($stats['total_reviews'] ?? 0) }}</p>
                    <p class="text-white/70 text-xs mt-1" data-translate>รายการ</p>
                </div>

                <div class="group bg-gradient-to-br from-purple-500 to-pink-600 dark:from-purple-600 dark:to-pink-700 rounded-xl shadow-lg p-5 hover:shadow-2xl transition-all duration-300 transform hover:scale-105">
                    <div class="flex items-center justify-between mb-3">
                        <div class="glass-fusion backdrop-blur-sm rounded-xl p-2" border border-white/20 dark:border-white/10>
                            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-white/80 text-sm mb-1" data-translate>คะแนน</p>
                    <p class="text-3xl font-bold text-white">{{ number_format($stats['average_rating'] ?? 0, 1) }}</p>
                    <p class="text-white/70 text-xs mt-1" data-translate>⭐ ดาว</p>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Quick Actions --}}
            <div class="glass-fusion dark:bg-slate-800 rounded-xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden" border border-white/20 dark:border-white/10>
                <div class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-slate-700 dark:to-slate-800 px-6 py-4 border-b border-gray-200 dark:border-gray-700 dark:border-gray-700">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-purple-500 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white" data-translate>การกระทำ</h3>
                    </div>
                </div>

                <div class="p-6 space-y-3">
                    <a href="{{ route('admin.ecommerce.products.edit', $product) }}" class="flex items-center gap-3 w-full px-4 py-3 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white rounded-xl font-medium transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        <span data-translate>แก้ไขสินค้า</span>
                    </a>

                    <a href="{{ route('admin.ecommerce.products.index') }}" class="flex items-center gap-3 w-full px-4 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-xl font-medium transition-all duration-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        <span data-translate>กลับรายการสินค้า</span>
                    </a>

                    <form action="{{ route('admin.ecommerce.products.delete', $product) }}" method="POST" onsubmit="return confirm('⚠️ คุณแน่ใจหรือไม่ที่จะลบสินค้า &quot;{{ $product->name }}&quot;?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="flex items-center gap-3 w-full px-4 py-3 bg-red-600 hover:bg-red-700 text-white rounded-xl font-medium transition-all duration-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            <span data-translate>ลบสินค้า</span>
                        </button>
                    </form>
                </div>
            </div>

            {{-- Meta Information --}}
            <div class="glass-fusion dark:bg-slate-800 rounded-xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden" border border-white/20 dark:border-white/10>
                <div class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-slate-700 dark:to-slate-800 px-6 py-4 border-b border-gray-200 dark:border-gray-700 dark:border-gray-700">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-500 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white" data-translate>ข้อมูลเพิ่มเติม</h3>
                    </div>
                </div>

                <div class="p-6 space-y-4">
                    <div class="flex items-start gap-3 p-3 bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-slate-700/50 rounded-xl">
                        <svg class="w-5 h-5 text-gray-500 dark:text-gray-400 dark:text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 mb-0.5" data-translate>สร้างเมื่อ</p>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $product->created_at->format('d/m/Y H:i น.') }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 mt-0.5">{{ $product->created_at->diffForHumans() }}</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3 p-3 bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-slate-700/50 rounded-xl">
                        <svg class="w-5 h-5 text-gray-500 dark:text-gray-400 dark:text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 mb-0.5" data-translate>แก้ไขล่าสุด</p>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $product->updated_at->format('d/m/Y H:i น.') }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 mt-0.5">{{ $product->updated_at->diffForHumans() }}</p>
                        </div>
                    </div>

                    @if($product->is_featured)
                        <div class="flex items-center gap-3 p-3 bg-gradient-to-r from-yellow-50 to-orange-50 dark:from-yellow-900/20 dark:to-orange-900/20 rounded-xl border border-yellow-200 dark:border-yellow-800">
                            <svg class="w-5 h-5 text-yellow-500 dark:text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                            <span class="text-sm font-semibold text-yellow-800 dark:text-yellow-200" data-translate>⭐ สินค้าแนะนำ</span>
                        </div>
                    @endif

                    @if($product->track_inventory)
                        <div class="flex items-center gap-3 p-3 bg-gradient-to-r from-green-50 to-teal-50 dark:from-green-900/20 dark:to-teal-900/20 rounded-xl border border-green-200 dark:border-green-800">
                            <svg class="w-5 h-5 text-green-500 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            <span class="text-sm font-semibold text-green-800 dark:text-green-200" data-translate>📊 ติดตามสต็อก</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
/**
 * ฟังก์ชันแปลภาษาด้วย Google Translate API
 *
 * @param {string} targetLang - ภาษาปลายทาง (en, zh, ja)
 * @return {Promise<void>}
 */
async function translatePage(targetLang) {
    // ถ้าเลือกภาษาไทย ให้ reload หน้าเพื่อกลับไปใช้ค่าเริ่มต้น
    if (targetLang === 'th') {
        location.reload();
        return;
    }

    // ดึง elements ที่มี data-translate attribute
    const elements = document.querySelectorAll('[data-translate]');

    try {
        // เตรียมข้อความที่จะแปล
        const textsToTranslate = Array.from(elements).map(el => {
            // เก็บข้อความต้นฉบับไว้ในกรณีที่ต้องการกลับมาใช้
            if (!el.dataset.originalText) {
                el.dataset.originalText = el.textContent.trim();
            }
            return el.dataset.originalText;
        });

        // Google Translate API key
        const apiKey = '{{ config("services.google_translate.key", "") }}';

        if (!apiKey) {
            console.warn('Google Translate API key not configured');
            return;
        }

        // เรียก Google Translate API
        const response = await fetch(`https://translation.googleapis.com/language/translate/v2?key=${apiKey}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                q: textsToTranslate,
                source: 'th',
                target: targetLang,
                format: 'text'
            })
        });

        const data = await response.json();

        // อัพเดทข้อความที่แปลแล้ว
        if (data.data && data.data.translations) {
            data.data.translations.forEach((translation, index) => {
                if (elements[index]) {
                    elements[index].textContent = translation.translatedText;
                }
            });
        }

    } catch (error) {
        console.error('Translation error:', error);
    }
}

// ติดตามการเปลี่ยนแปลงของ language variable ใน Alpine.js
document.addEventListener('alpine:init', () => {
    Alpine.watch('language', (value) => {
        if (value !== 'th') {
            translatePage(value);
        }
    });
});
</script>
@endpush

@endsection
