{{--
  * หน้าแก้ไขสินค้า (Product Edit)
  * ฟอร์มสำหรับแก้ไขข้อมูลสินค้า
  * รองรับ Dark Mode และ Responsive Design
  --}}

@extends('layouts.admin-v3')

@section('title', 'แก้ไขสินค้า: ' . $product->name)

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

    @if(session('error'))
        <div x-data="{ show: true }" x-show="show" x-transition class="bg-gradient-to-r from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20 border-l-4 border-red-500 dark:border-red-400 rounded-xl shadow-lg p-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <svg class="w-6 h-6 text-red-500 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
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

    {{-- Header Section --}}
    <div class="relative overflow-hidden bg-gradient-to-br from-orange-500 to-pink-600 dark:from-orange-600 dark:to-pink-700 rounded-2xl shadow-2xl p-8">
        {{-- Background Pattern --}}
        <div class="absolute inset-0 bg-black/10 dark:bg-black/20" style="background-image: radial-gradient(circle at 1px 1px, rgba(255,255,255,0.15) 1px, transparent 0); background-size: 40px 40px;"></div>

        <div class="relative flex flex-col md:flex-row md:justify-between md:items-start gap-4">
            <div>
                <a href="{{ route('admin.ecommerce.products.index') }}" class="inline-flex items-center gap-2 text-white/90 hover:text-white text-sm mb-3 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span data-translate>กลับไปรายการสินค้า</span>
                </a>
                <div class="flex items-center gap-4">
                    <div class="glass-fusion dark:glass-fusion backdrop-blur-sm rounded-2xl p-4" border border-white/20 dark:border-white/10>
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </div>
                    <div class="text-white">
                        <h1 class="text-3xl md:text-4xl font-bold mb-1" data-translate>แก้ไขสินค้า</h1>
                        <p class="text-white/90 text-sm md:text-base">{{ $product->name }}</p>
                    </div>
                </div>
            </div>

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

    {{-- Form --}}
    <form action="{{ route('admin.ecommerce.products.update', $product) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main Content --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Basic Information --}}
                <div class="glass-fusion dark:bg-slate-800 rounded-xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden" border border-white/20 dark:border-white/10>
                    <div class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-slate-700 dark:to-slate-800 px-6 py-4 border-b border-gray-200 dark:border-gray-700 dark:border-gray-700">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-blue-500 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white" data-translate>ข้อมูลพื้นฐาน</h3>
                        </div>
                    </div>

                    <div class="p-6 space-y-5">
                        {{-- ชื่อสินค้า --}}
                        <div>
                            <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                </svg>
                                <span data-translate>ชื่อสินค้า</span> <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="name" value="{{ old('name', $product->name) }}" required placeholder="กรอกชื่อสินค้า..." data-translate-placeholder class="w-full px-4 py-2.5 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:border-gray-600 dark:bg-slate-700 dark:text-white focus:border-blue-500 dark:focus:border-blue-400 focus:ring-2 focus:ring-blue-200 dark:focus:ring-blue-900/30 transition-all">
                            @error('name')
                                <p class="text-red-500 dark:text-red-400 text-sm mt-1 flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        {{-- SKU --}}
                        <div>
                            <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                                </svg>
                                <span data-translate>SKU</span>
                            </label>
                            <input type="text" name="sku" value="{{ old('sku', $product->sku) }}" placeholder="รหัสสินค้า" data-translate-placeholder class="w-full px-4 py-2.5 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:border-gray-600 dark:bg-slate-700 dark:text-white focus:border-blue-500 dark:focus:border-blue-400 focus:ring-2 focus:ring-blue-200 dark:focus:ring-blue-900/30 transition-all font-mono">
                            @error('sku')
                                <p class="text-red-500 dark:text-red-400 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- คำอธิบายสั้น --}}
                        <div>
                            <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h8m-8 6h16"/>
                                </svg>
                                <span data-translate>คำอธิบายสั้น</span>
                            </label>
                            <textarea name="short_description" rows="2" placeholder="คำอธิบายสินค้าแบบย่อ..." data-translate-placeholder class="w-full px-4 py-2.5 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:border-gray-600 dark:bg-slate-700 dark:text-white focus:border-blue-500 dark:focus:border-blue-400 focus:ring-2 focus:ring-blue-200 dark:focus:ring-blue-900/30 transition-all resize-none">{{ old('short_description', $product->short_description) }}</textarea>
                        </div>

                        {{-- คำอธิบายสินค้า --}}
                        <div>
                            <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/>
                                </svg>
                                <span data-translate>คำอธิบายสินค้า</span>
                            </label>
                            <textarea name="description" rows="6" placeholder="รายละเอียดสินค้าแบบครบถ้วน..." data-translate-placeholder class="w-full px-4 py-2.5 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:border-gray-600 dark:bg-slate-700 dark:text-white focus:border-blue-500 dark:focus:border-blue-400 focus:ring-2 focus:ring-blue-200 dark:focus:ring-blue-900/30 transition-all resize-none">{{ old('description', $product->description) }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Pricing --}}
                <div class="glass-fusion dark:bg-slate-800 rounded-xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden" border border-white/20 dark:border-white/10>
                    <div class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-slate-700 dark:to-slate-800 px-6 py-4 border-b border-gray-200 dark:border-gray-700 dark:border-gray-700">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-green-500 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white" data-translate>ราคา</h3>
                        </div>
                    </div>

                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            {{-- ราคาขาย --}}
                            <div>
                                <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                                    <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span data-translate>ราคาขาย</span> <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 dark:text-gray-400 font-semibold">฿</span>
                                    <input type="number" name="price" value="{{ old('price', $product->price) }}" step="0.01" min="0" required placeholder="0.00" class="w-full pl-10 pr-4 py-2.5 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:border-gray-600 dark:bg-slate-700 dark:text-white focus:border-green-500 dark:focus:border-green-400 focus:ring-2 focus:ring-green-200 dark:focus:ring-green-900/30 transition-all">
                                </div>
                                @error('price')
                                    <p class="text-red-500 dark:text-red-400 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- ราคาเปรียบเทียบ --}}
                            <div>
                                <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                                    <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                    </svg>
                                    <span data-translate>ราคาเปรียบเทียบ</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 dark:text-gray-400 font-semibold">฿</span>
                                    <input type="number" name="compare_at_price" value="{{ old('compare_at_price', $product->compare_at_price) }}" step="0.01" min="0" placeholder="0.00" class="w-full pl-10 pr-4 py-2.5 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:border-gray-600 dark:bg-slate-700 dark:text-white focus:border-green-500 dark:focus:border-green-400 focus:ring-2 focus:ring-green-200 dark:focus:ring-green-900/30 transition-all">
                                </div>
                            </div>

                            {{-- ราคาทุน --}}
                            <div>
                                <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                                    <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                    </svg>
                                    <span data-translate>ราคาทุน</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 dark:text-gray-400 font-semibold">฿</span>
                                    <input type="number" name="cost_price" value="{{ old('cost_price', $product->cost_price) }}" step="0.01" min="0" placeholder="0.00" class="w-full pl-10 pr-4 py-2.5 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:border-gray-600 dark:bg-slate-700 dark:text-white focus:border-green-500 dark:focus:border-green-400 focus:ring-2 focus:ring-green-200 dark:focus:ring-green-900/30 transition-all">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Inventory --}}
                <div class="glass-fusion dark:bg-slate-800 rounded-xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden" border border-white/20 dark:border-white/10>
                    <div class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-slate-700 dark:to-slate-800 px-6 py-4 border-b border-gray-200 dark:border-gray-700 dark:border-gray-700">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-orange-500 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white" data-translate>สต็อกและคลังสินค้า</h3>
                        </div>
                    </div>

                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {{-- จำนวนสต็อก --}}
                            <div>
                                <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                                    <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                    <span data-translate>จำนวนสต็อก</span> <span class="text-red-500">*</span>
                                </label>
                                <input type="number" name="stock_quantity" value="{{ old('stock_quantity', $product->stock_quantity) }}" min="0" required placeholder="0" class="w-full px-4 py-2.5 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:border-gray-600 dark:bg-slate-700 dark:text-white focus:border-orange-500 dark:focus:border-orange-400 focus:ring-2 focus:ring-orange-200 dark:focus:ring-orange-900/30 transition-all">
                            </div>

                            {{-- น้ำหนัก --}}
                            <div>
                                <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                                    <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/>
                                    </svg>
                                    <span data-translate>น้ำหนัก (กรัม)</span>
                                </label>
                                <input type="number" name="weight" value="{{ old('weight', $product->weight) }}" step="0.01" min="0" placeholder="0" class="w-full px-4 py-2.5 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:border-gray-600 dark:bg-slate-700 dark:text-white focus:border-orange-500 dark:focus:border-orange-400 focus:ring-2 focus:ring-orange-200 dark:focus:ring-orange-900/30 transition-all">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Images --}}
                <div class="glass-fusion dark:bg-slate-800 rounded-xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden" border border-white/20 dark:border-white/10>
                    <div class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-slate-700 dark:to-slate-800 px-6 py-4 border-b border-gray-200 dark:border-gray-700 dark:border-gray-700">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-purple-500 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white" data-translate>รูปภาพสินค้า</h3>
                        </div>
                    </div>

                    <div class="p-6 space-y-4">
                        {{-- รูปหลัก --}}
                        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border-2 border-dashed border-blue-200 dark:border-blue-800">
                            <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2 cursor-pointer">
                                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span data-translate>รูปภาพหลัก</span>
                            </label>
                            <input type="file" name="main_image" accept="image/*" class="w-full text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-blue-900/30 dark:file:text-blue-400 transition-all">
                            <p class="text-xs text-blue-600 dark:text-blue-400 mt-2 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                                <span data-translate>รองรับ JPG, PNG, GIF, WebP (ขนาดไม่เกิน 5MB)</span>
                            </p>
                            @if($product->main_image_url ?? $product->primary_image)
                                <div class="mt-3">
                                    <p class="text-xs text-gray-600 dark:text-gray-400 dark:text-gray-400 mb-2" data-translate>รูปภาพปัจจุบัน:</p>
                                    <img src="{{ $product->primary_image ?? Storage::url($product->main_image_url) }}" alt="Current image" class="w-32 h-32 object-cover rounded-xl ring-2 ring-blue-200 dark:ring-blue-800">
                                </div>
                            @endif
                        </div>

                        {{-- รูปเพิ่มเติม --}}
                        <div class="p-4 bg-purple-50 dark:bg-purple-900/20 rounded-xl border-2 border-dashed border-purple-200 dark:border-purple-800">
                            <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2 cursor-pointer">
                                <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span data-translate>รูปภาพเพิ่มเติม (สูงสุด 10 ภาพ)</span>
                            </label>
                            <input type="file" name="images[]" accept="image/*" multiple class="w-full text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100 dark:file:bg-purple-900/30 dark:file:text-purple-400 transition-all">
                            <p class="text-xs text-purple-600 dark:text-purple-400 mt-2 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                                <span data-translate>สามารถเลือกหลายไฟล์พร้อมกันได้</span>
                            </p>
                            @if($product->images && $product->images->count() > 0)
                                <div class="mt-3">
                                    <p class="text-xs text-gray-600 dark:text-gray-400 dark:text-gray-400 mb-2" data-translate>รูปภาพเพิ่มเติมปัจจุบัน:</p>
                                    <div class="grid grid-cols-4 gap-2">
                                        @foreach($product->images->take(8) as $image)
                                            <img src="{{ Storage::url($image->image_url) }}" alt="Gallery image" class="w-full h-20 object-cover rounded-xl ring-2 ring-purple-200 dark:ring-purple-800">
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                {{-- Category --}}
                <div class="glass-fusion dark:bg-slate-800 rounded-xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden" border border-white/20 dark:border-white/10>
                    <div class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-slate-700 dark:to-slate-800 px-6 py-4 border-b border-gray-200 dark:border-gray-700 dark:border-gray-700">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-500 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                            </svg>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white" data-translate>หมวดหมู่</h3>
                        </div>
                    </div>

                    <div class="p-6">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                            <span data-translate>เลือกหมวดหมู่</span> <span class="text-red-500">*</span>
                        </label>
                        <select name="category_id" required class="w-full px-4 py-2.5 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:border-gray-600 dark:bg-slate-700 dark:text-white focus:border-indigo-500 dark:focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 dark:focus:ring-indigo-900/30 transition-all">
                            <option value="" data-translate>-- เลือกหมวดหมู่ --</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <p class="text-red-500 dark:text-red-400 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Additional Details --}}
                <div class="glass-fusion dark:bg-slate-800 rounded-xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden" border border-white/20 dark:border-white/10>
                    <div class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-slate-700 dark:to-slate-800 px-6 py-4 border-b border-gray-200 dark:border-gray-700 dark:border-gray-700">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-pink-500 dark:text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                            </svg>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white" data-translate>รายละเอียดเพิ่มเติม</h3>
                        </div>
                    </div>

                    <div class="p-6 space-y-4">
                        {{-- แบรนด์ --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2" data-translate>
                                แบรนด์
                            </label>
                            <input type="text" name="brand" value="{{ old('brand', $product->brand) }}" placeholder="ชื่อแบรนด์" data-translate-placeholder class="w-full px-4 py-2.5 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:border-gray-600 dark:bg-slate-700 dark:text-white focus:border-pink-500 dark:focus:border-pink-400 focus:ring-2 focus:ring-pink-200 dark:focus:ring-pink-900/30 transition-all">
                        </div>

                        {{-- คอมมิชชั่น --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2" data-translate>
                                % คอมมิชชั่น
                            </label>
                            <div class="relative">
                                <input type="number" name="commission_rate" value="{{ old('commission_rate', $product->commission_rate) }}" step="0.01" min="0" max="100" placeholder="15" class="w-full pl-4 pr-10 py-2.5 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:border-gray-600 dark:bg-slate-700 dark:text-white focus:border-pink-500 dark:focus:border-pink-400 focus:ring-2 focus:ring-pink-200 dark:focus:ring-pink-900/30 transition-all">
                                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 dark:text-gray-400 font-semibold">%</span>
                            </div>
                        </div>

                        {{-- ตัวเลือก --}}
                        <div class="pt-4 border-t border-gray-200 dark:border-gray-700 dark:border-gray-700 space-y-3">
                            <label class="inline-flex items-center gap-2 cursor-pointer group">
                                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $product->is_active) ? 'checked' : '' }} class="w-5 h-5 rounded border-2 border-gray-300 dark:border-gray-600 dark:border-gray-600 text-green-600 focus:ring-2 focus:ring-green-500 dark:focus:ring-green-400 transition-all">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 group-hover:text-green-600 dark:group-hover:text-green-400 transition-colors" data-translate>✅ ใช้งาน</span>
                            </label>

                            <label class="inline-flex items-center gap-2 cursor-pointer group">
                                <input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $product->is_featured) ? 'checked' : '' }} class="w-5 h-5 rounded border-2 border-gray-300 dark:border-gray-600 dark:border-gray-600 text-yellow-600 focus:ring-2 focus:ring-yellow-500 dark:focus:ring-yellow-400 transition-all">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 group-hover:text-yellow-600 dark:group-hover:text-yellow-400 transition-colors" data-translate>⭐ สินค้าแนะนำ</span>
                            </label>

                            <label class="inline-flex items-center gap-2 cursor-pointer group">
                                <input type="checkbox" name="track_inventory" value="1" {{ old('track_inventory', $product->track_inventory) ? 'checked' : '' }} class="w-5 h-5 rounded border-2 border-gray-300 dark:border-gray-600 dark:border-gray-600 text-blue-600 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 transition-all">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors" data-translate>📊 ติดตามสต็อก</span>
                            </label>
                        </div>

                        {{-- Meta Info --}}
                        <div class="pt-4 border-t border-gray-200 dark:border-gray-700 dark:border-gray-700 space-y-2">
                            <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                </svg>
                                <span><span data-translate>ยอดขาย:</span> <strong class="text-gray-900 dark:text-white">{{ number_format($product->sales_count ?? 0) }}</strong> <span data-translate>ชิ้น</span></span>
                            </div>
                            <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span><span data-translate>สร้างเมื่อ:</span> <strong class="text-gray-900 dark:text-white">{{ $product->created_at->format('d/m/Y') }}</strong></span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="glass-fusion dark:bg-slate-800 rounded-xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden" border border-white/20 dark:border-white/10>
                    <div class="p-6 space-y-3">
                        <button type="submit" class="flex items-center justify-center gap-3 w-full px-6 py-3 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white rounded-xl font-semibold transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span data-translate>บันทึกการเปลี่ยนแปลง</span>
                        </button>

                        <a href="{{ route('admin.ecommerce.products.index') }}" class="flex items-center justify-center gap-3 w-full px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-xl font-medium transition-all duration-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                            <span data-translate>ยกเลิก</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
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
    const placeholderElements = document.querySelectorAll('[data-translate-placeholder]');

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

        // เรียก Google Translate API สำหรับ text content
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

        // แปล placeholders
        for (const el of placeholderElements) {
            if (!el.dataset.originalPlaceholder) {
                el.dataset.originalPlaceholder = el.placeholder;
            }

            const placeholderResponse = await fetch(`https://translation.googleapis.com/language/translate/v2?key=${apiKey}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    q: [el.dataset.originalPlaceholder],
                    source: 'th',
                    target: targetLang,
                    format: 'text'
                })
            });

            const placeholderData = await placeholderResponse.json();
            if (placeholderData.data && placeholderData.data.translations[0]) {
                el.placeholder = placeholderData.data.translations[0].translatedText;
            }
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
