{{--
  * หน้าแก้ไขสินค้า Official Shop (Product Edit)
  * ฟอร์มสำหรับแก้ไขข้อมูลสินค้าในร้านทางการ
  --}}

@extends('layouts.admin-v3')

@section('title', 'แก้ไขสินค้า: ' . $product->name)

@section('content')
<div class="space-y-6">
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

    {{-- Header Section - Premium Gold/Purple Gradient for Official Shop --}}
    <div class="relative overflow-hidden bg-gradient-to-br from-amber-500 via-purple-600 to-pink-600 dark:from-amber-600 dark:via-purple-700 dark:to-pink-700 rounded-2xl shadow-2xl p-8">
        {{-- Background Pattern --}}
        <div class="absolute inset-0 bg-black/10 dark:bg-black/20" style="background-image: radial-gradient(circle at 1px 1px, rgba(255,255,255,0.15) 1px, transparent 0); background-size: 40px 40px;"></div>

        {{-- Crown Decoration --}}
        <div class="absolute top-4 right-4 opacity-20">
            <svg class="w-32 h-32 text-white" fill="currentColor" viewBox="0 0 24 24">
                <path d="M5 16L3 5l5.5 5L12 4l3.5 6L21 5l-2 11H5z"/>
            </svg>
        </div>

        <div class="relative">
            <a href="{{ route('admin.official-shop.products.index') }}" class="inline-flex items-center gap-2 text-white/90 hover:text-white text-sm mb-3 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span>กลับไปรายการสินค้า Official Shop</span>
            </a>
            <div class="flex items-center gap-4">
                <div class="bg-white/20 backdrop-blur-sm rounded-2xl p-4 border border-white/30">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </div>
                <div class="text-white">
                    <div class="flex items-center gap-2 mb-1">
                        <svg class="w-5 h-5 text-amber-300" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M5 16L3 5l5.5 5L12 4l3.5 6L21 5l-2 11H5z"/>
                        </svg>
                        <span class="text-amber-200 text-sm font-semibold">Official Shop</span>
                    </div>
                    <h1 class="text-3xl md:text-4xl font-bold">แก้ไขสินค้า</h1>
                    <p class="text-white/80 text-sm mt-1">{{ $product->name }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Product Stats Bar --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">ยอดเข้าชม</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white">{{ number_format($product->view_count ?? 0) }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">ยอดขาย</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white">{{ number_format($product->sales_count ?? 0) }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">คะแนน</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white">{{ number_format($product->rating_average ?? 0, 1) }} <span class="text-xs font-normal text-gray-500">({{ $product->rating_count ?? 0 }})</span></p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">สร้างเมื่อ</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $product->created_at->format('d/m/Y') }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Form --}}
    <form action="{{ route('admin.official-shop.products.update', $product) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main Content --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Basic Information --}}
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-slate-700 dark:to-slate-800 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-blue-500 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">ข้อมูลพื้นฐาน</h3>
                        </div>
                    </div>

                    <div class="p-6 space-y-5">
                        {{-- ชื่อสินค้า --}}
                        <div>
                            <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                </svg>
                                <span>ชื่อสินค้า</span> <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="name" value="{{ old('name', $product->name) }}" required placeholder="กรอกชื่อสินค้า..." class="w-full px-4 py-2.5 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white focus:border-blue-500 dark:focus:border-blue-400 focus:ring-2 focus:ring-blue-200 dark:focus:ring-blue-900/30 transition-all">
                            @error('name')
                                <p class="text-red-500 dark:text-red-400 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- SKU --}}
                        <div>
                            <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                                </svg>
                                <span>SKU</span>
                            </label>
                            <input type="text" name="sku" value="{{ old('sku', $product->sku) }}" placeholder="รหัสสินค้า" class="w-full px-4 py-2.5 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white focus:border-blue-500 dark:focus:border-blue-400 focus:ring-2 focus:ring-blue-200 dark:focus:ring-blue-900/30 transition-all font-mono">
                            @error('sku')
                                <p class="text-red-500 dark:text-red-400 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- คำอธิบายสั้น --}}
                        <div>
                            <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <span>คำอธิบายสั้น</span>
                            </label>
                            <textarea name="short_description" rows="2" placeholder="คำอธิบายสินค้าแบบย่อ..." class="w-full px-4 py-2.5 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white focus:border-blue-500 dark:focus:border-blue-400 focus:ring-2 focus:ring-blue-200 dark:focus:ring-blue-900/30 transition-all resize-none">{{ old('short_description', $product->short_description) }}</textarea>
                        </div>

                        {{-- คำอธิบายสินค้า --}}
                        <div>
                            <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <span>คำอธิบายสินค้า</span>
                            </label>
                            <textarea name="description" rows="6" placeholder="รายละเอียดสินค้าแบบครบถ้วน..." class="w-full px-4 py-2.5 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white focus:border-blue-500 dark:focus:border-blue-400 focus:ring-2 focus:ring-blue-200 dark:focus:ring-blue-900/30 transition-all resize-none">{{ old('description', $product->description) }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Pricing --}}
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-slate-700 dark:to-slate-800 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-green-500 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">ราคา</h3>
                        </div>
                    </div>

                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            {{-- ราคาขาย --}}
                            <div>
                                <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    <span>ราคาขาย</span> <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 font-semibold">฿</span>
                                    <input type="number" name="price" value="{{ old('price', $product->price) }}" step="0.01" min="0" required placeholder="0.00" class="w-full pl-10 pr-4 py-2.5 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white focus:border-green-500 dark:focus:border-green-400 focus:ring-2 focus:ring-green-200 dark:focus:ring-green-900/30 transition-all">
                                </div>
                                @error('price')
                                    <p class="text-red-500 dark:text-red-400 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- ราคาเปรียบเทียบ --}}
                            <div>
                                <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    <span>ราคาเปรียบเทียบ</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 font-semibold">฿</span>
                                    <input type="number" name="compare_at_price" value="{{ old('compare_at_price', $product->compare_at_price) }}" step="0.01" min="0" placeholder="0.00" class="w-full pl-10 pr-4 py-2.5 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white focus:border-green-500 dark:focus:border-green-400 focus:ring-2 focus:ring-green-200 dark:focus:ring-green-900/30 transition-all">
                                </div>
                            </div>

                            {{-- ราคาทุน --}}
                            <div>
                                <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    <span>ราคาทุน</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 font-semibold">฿</span>
                                    <input type="number" name="cost_price" value="{{ old('cost_price', $product->cost_price) }}" step="0.01" min="0" placeholder="0.00" class="w-full pl-10 pr-4 py-2.5 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white focus:border-green-500 dark:focus:border-green-400 focus:ring-2 focus:ring-green-200 dark:focus:ring-green-900/30 transition-all">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Coin Purchase Section --}}
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-yellow-200 dark:border-yellow-700 overflow-hidden" x-data="{ allowCoinPurchase: {{ old('allow_coin_purchase', $product->allow_coin_purchase) ? 'true' : 'false' }} }">
                    <div class="bg-gradient-to-r from-yellow-50 to-amber-50 dark:from-yellow-900/20 dark:to-amber-900/20 px-6 py-4 border-b border-yellow-200 dark:border-yellow-700">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-yellow-500 dark:text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.736 6.979C9.208 6.193 9.696 6 10 6c.304 0 .792.193 1.264.979a1 1 0 001.715-1.029C12.279 4.784 11.232 4 10 4s-2.279.784-2.979 1.95c-.285.475-.507 1-.67 1.55H6a1 1 0 000 2h.013a9.358 9.358 0 000 1H6a1 1 0 100 2h.351c.163.55.385 1.075.67 1.55C7.721 15.216 8.768 16 10 16s2.279-.784 2.979-1.95a1 1 0 10-1.715-1.029c-.472.786-.96.979-1.264.979-.304 0-.792-.193-1.264-.979a4.265 4.265 0 01-.264-.521H10a1 1 0 100-2H8.017a7.36 7.36 0 010-1H10a1 1 0 100-2H8.472c.08-.185.167-.36.264-.521z"/>
                            </svg>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">ซื้อด้วย Coins</h3>
                            <span class="px-2 py-0.5 bg-yellow-100 dark:bg-yellow-900/50 text-yellow-700 dark:text-yellow-300 text-xs rounded-full font-medium">Premium Feature</span>
                            @if($product->allow_coin_purchase && $product->price_coins > 0)
                                <span class="px-2 py-0.5 bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300 text-xs rounded-full font-medium">เปิดใช้งาน</span>
                            @endif
                        </div>
                    </div>

                    <div class="p-6 space-y-4">
                        {{-- Toggle Allow Coin Purchase --}}
                        <div class="flex items-center justify-between p-4 bg-gradient-to-r from-yellow-50 to-amber-50 dark:from-yellow-900/10 dark:to-amber-900/10 rounded-xl border border-yellow-100 dark:border-yellow-800">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.736 6.979C9.208 6.193 9.696 6 10 6c.304 0 .792.193 1.264.979a1 1 0 001.715-1.029C12.279 4.784 11.232 4 10 4s-2.279.784-2.979 1.95c-.285.475-.507 1-.67 1.55H6a1 1 0 000 2h.013a9.358 9.358 0 000 1H6a1 1 0 100 2h.351c.163.55.385 1.075.67 1.55C7.721 15.216 8.768 16 10 16s2.279-.784 2.979-1.95a1 1 0 10-1.715-1.029c-.472.786-.96.979-1.264.979-.304 0-.792-.193-1.264-.979a4.265 4.265 0 01-.264-.521H10a1 1 0 100-2H8.017a7.36 7.36 0 010-1H10a1 1 0 100-2H8.472c.08-.185.167-.36.264-.521z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900 dark:text-white">อนุญาตให้ซื้อด้วย Coins</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">ลูกค้าสามารถใช้ Coins แทนเงินสดในการซื้อสินค้านี้</p>
                                </div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="allow_coin_purchase" value="1" {{ old('allow_coin_purchase', $product->allow_coin_purchase) ? 'checked' : '' }} class="sr-only peer" x-model="allowCoinPurchase">
                                <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-yellow-300 dark:peer-focus:ring-yellow-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:start-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all dark:border-gray-600 peer-checked:bg-yellow-500"></div>
                            </label>
                        </div>

                        {{-- Coin Price Input --}}
                        <div x-show="allowCoinPurchase" x-transition class="space-y-4">
                            <div>
                                <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    <svg class="w-4 h-4 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.736 6.979C9.208 6.193 9.696 6 10 6c.304 0 .792.193 1.264.979a1 1 0 001.715-1.029C12.279 4.784 11.232 4 10 4s-2.279.784-2.979 1.95c-.285.475-.507 1-.67 1.55H6a1 1 0 000 2h.013a9.358 9.358 0 000 1H6a1 1 0 100 2h.351c.163.55.385 1.075.67 1.55C7.721 15.216 8.768 16 10 16s2.279-.784 2.979-1.95a1 1 0 10-1.715-1.029c-.472.786-.96.979-1.264.979-.304 0-.792-.193-1.264-.979a4.265 4.265 0 01-.264-.521H10a1 1 0 100-2H8.017a7.36 7.36 0 010-1H10a1 1 0 100-2H8.472c.08-.185.167-.36.264-.521z"/>
                                    </svg>
                                    <span>ราคาเป็น Coins</span> <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-yellow-500 dark:text-yellow-400 font-semibold">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.736 6.979C9.208 6.193 9.696 6 10 6c.304 0 .792.193 1.264.979a1 1 0 001.715-1.029C12.279 4.784 11.232 4 10 4s-2.279.784-2.979 1.95c-.285.475-.507 1-.67 1.55H6a1 1 0 000 2h.013a9.358 9.358 0 000 1H6a1 1 0 100 2h.351c.163.55.385 1.075.67 1.55C7.721 15.216 8.768 16 10 16s2.279-.784 2.979-1.95a1 1 0 10-1.715-1.029c-.472.786-.96.979-1.264.979-.304 0-.792-.193-1.264-.979a4.265 4.265 0 01-.264-.521H10a1 1 0 100-2H8.017a7.36 7.36 0 010-1H10a1 1 0 100-2H8.472c.08-.185.167-.36.264-.521z"/>
                                        </svg>
                                    </span>
                                    <input type="number" name="price_coins" value="{{ old('price_coins', $product->price_coins) }}" step="1" min="1" placeholder="0" class="w-full pl-12 pr-20 py-2.5 rounded-xl border-2 border-yellow-300 dark:border-yellow-600 dark:bg-slate-700 dark:text-white focus:border-yellow-500 dark:focus:border-yellow-400 focus:ring-2 focus:ring-yellow-200 dark:focus:ring-yellow-900/30 transition-all">
                                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-yellow-600 dark:text-yellow-400 font-semibold">Coins</span>
                                </div>
                                <p class="text-xs text-yellow-600 dark:text-yellow-400 mt-2 flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                                    <span>จำนวน Coins ที่ลูกค้าต้องจ่ายเพื่อซื้อสินค้านี้</span>
                                </p>
                                @error('price_coins')
                                    <p class="text-red-500 dark:text-red-400 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Inventory --}}
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-slate-700 dark:to-slate-800 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-orange-500 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">สต็อกและคลังสินค้า</h3>
                        </div>
                    </div>

                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            {{-- จำนวนสต็อก --}}
                            <div>
                                <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    <span>จำนวนสต็อก</span> <span class="text-red-500">*</span>
                                </label>
                                <input type="number" name="stock_quantity" value="{{ old('stock_quantity', $product->stock_quantity) }}" min="0" required placeholder="0" class="w-full px-4 py-2.5 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white focus:border-orange-500 dark:focus:border-orange-400 focus:ring-2 focus:ring-orange-200 dark:focus:ring-orange-900/30 transition-all">
                            </div>

                            {{-- จุดเตือนสต็อกต่ำ --}}
                            <div>
                                <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    <span>จุดเตือนสต็อกต่ำ</span>
                                </label>
                                <input type="number" name="low_stock_threshold" value="{{ old('low_stock_threshold', $product->low_stock_threshold ?? 10) }}" min="0" placeholder="10" class="w-full px-4 py-2.5 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white focus:border-orange-500 dark:focus:border-orange-400 focus:ring-2 focus:ring-orange-200 dark:focus:ring-orange-900/30 transition-all">
                            </div>

                            {{-- น้ำหนัก --}}
                            <div>
                                <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    <span>น้ำหนัก (กรัม)</span>
                                </label>
                                <input type="number" name="weight" value="{{ old('weight', $product->weight) }}" step="0.01" min="0" placeholder="0" class="w-full px-4 py-2.5 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white focus:border-orange-500 dark:focus:border-orange-400 focus:ring-2 focus:ring-orange-200 dark:focus:ring-orange-900/30 transition-all">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Images --}}
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-slate-700 dark:to-slate-800 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-purple-500 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">รูปภาพสินค้า</h3>
                        </div>
                    </div>

                    <div class="p-6 space-y-4">
                        {{-- รูปหลัก --}}
                        <div class="p-4 bg-gradient-to-r from-amber-50 to-purple-50 dark:from-amber-900/20 dark:to-purple-900/20 rounded-xl border-2 border-dashed border-amber-200 dark:border-amber-700">
                            <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <span>รูปภาพหลัก</span>
                            </label>
                            <input type="file" name="main_image" accept="image/*" class="w-full text-sm text-gray-600 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-amber-100 file:text-amber-700 hover:file:bg-amber-200 dark:file:bg-amber-900/30 dark:file:text-amber-400 transition-all">
                            <p class="text-xs text-amber-600 dark:text-amber-400 mt-2">รองรับ JPG, PNG, GIF, WebP (ขนาดไม่เกิน 5MB)</p>
                            @if($product->main_image_url)
                                <div class="mt-3">
                                    <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">รูปภาพปัจจุบัน:</p>
                                    <img src="{{ Storage::url($product->main_image_url) }}" alt="Current image" class="w-32 h-32 object-cover rounded-xl ring-2 ring-amber-200 dark:ring-amber-800">
                                </div>
                            @endif
                        </div>

                        {{-- รูปเพิ่มเติม --}}
                        <div class="p-4 bg-purple-50 dark:bg-purple-900/20 rounded-xl border-2 border-dashed border-purple-200 dark:border-purple-800">
                            <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                <span>รูปภาพเพิ่มเติม (สูงสุด 10 ภาพ)</span>
                            </label>
                            <input type="file" name="images[]" accept="image/*" multiple class="w-full text-sm text-gray-600 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100 dark:file:bg-purple-900/30 dark:file:text-purple-400 transition-all">
                            <p class="text-xs text-purple-600 dark:text-purple-400 mt-2">สามารถเลือกหลายไฟล์พร้อมกันได้</p>
                            @if($product->images && $product->images->count() > 0)
                                <div class="mt-3">
                                    <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">รูปภาพเพิ่มเติมปัจจุบัน:</p>
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
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-slate-700 dark:to-slate-800 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-500 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                            </svg>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">หมวดหมู่</h3>
                        </div>
                    </div>

                    <div class="p-6">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            <span>เลือกหมวดหมู่</span> <span class="text-red-500">*</span>
                        </label>
                        <select name="category_id" required class="w-full px-4 py-2.5 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white focus:border-indigo-500 dark:focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 dark:focus:ring-indigo-900/30 transition-all">
                            <option value="">-- เลือกหมวดหมู่ --</option>
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

                {{-- Commission & MLM Settings --}}
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="bg-gradient-to-r from-amber-50 to-purple-50 dark:from-amber-900/20 dark:to-purple-900/20 px-6 py-4 border-b border-amber-200 dark:border-amber-700">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-amber-500" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M5 16L3 5l5.5 5L12 4l3.5 6L21 5l-2 11H5z"/>
                            </svg>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Commission & MLM</h3>
                        </div>
                    </div>

                    <div class="p-6 space-y-4">
                        {{-- คอมมิชชั่น --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                % คอมมิชชั่น
                            </label>
                            <div class="relative">
                                <input type="number" name="commission_rate" value="{{ old('commission_rate', $product->commission_rate) }}" step="0.01" min="0" max="100" placeholder="15" class="w-full pl-4 pr-10 py-2.5 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white focus:border-amber-500 dark:focus:border-amber-400 focus:ring-2 focus:ring-amber-200 dark:focus:ring-amber-900/30 transition-all">
                                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 font-semibold">%</span>
                            </div>
                        </div>

                        {{-- PV Value --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                ⭐ PV (Point Value)
                            </label>
                            <div class="relative">
                                <input type="number" name="pv_value" value="{{ old('pv_value', $product->pv_value ?? 0) }}" step="0.01" min="0" placeholder="0" class="w-full pl-4 pr-16 py-2.5 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white focus:border-purple-500 dark:focus:border-purple-400 focus:ring-2 focus:ring-purple-200 dark:focus:ring-purple-900/30 transition-all">
                                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 font-semibold text-sm">PV</span>
                            </div>
                        </div>

                        {{-- Cashback --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                🎁 Cashback
                            </label>
                            <div class="relative">
                                <input type="number" name="cashback_percentage" value="{{ old('cashback_percentage', $product->cashback_percentage ?? 0) }}" step="0.01" min="0" max="100" placeholder="0" class="w-full pl-4 pr-10 py-2.5 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white focus:border-green-500 dark:focus:border-green-400 focus:ring-2 focus:ring-green-200 dark:focus:ring-green-900/30 transition-all">
                                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 font-semibold">%</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Additional Settings --}}
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-slate-700 dark:to-slate-800 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-pink-500 dark:text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                            </svg>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">ตั้งค่าเพิ่มเติม</h3>
                        </div>
                    </div>

                    <div class="p-6 space-y-4">
                        {{-- แบรนด์ --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                แบรนด์
                            </label>
                            <input type="text" name="brand" value="{{ old('brand', $product->brand) }}" placeholder="ชื่อแบรนด์" class="w-full px-4 py-2.5 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white focus:border-pink-500 dark:focus:border-pink-400 focus:ring-2 focus:ring-pink-200 dark:focus:ring-pink-900/30 transition-all">
                        </div>

                        {{-- ตัวเลือก --}}
                        <div class="pt-4 border-t border-gray-200 dark:border-gray-700 space-y-3">
                            <label class="flex items-center gap-3 cursor-pointer group p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $product->is_active) ? 'checked' : '' }} class="w-5 h-5 rounded border-2 border-gray-300 dark:border-gray-600 text-green-600 focus:ring-2 focus:ring-green-500 dark:focus:ring-green-400 transition-all">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">✅ ใช้งาน</span>
                            </label>

                            <label class="flex items-center gap-3 cursor-pointer group p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                <input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $product->is_featured) ? 'checked' : '' }} class="w-5 h-5 rounded border-2 border-gray-300 dark:border-gray-600 text-amber-600 focus:ring-2 focus:ring-amber-500 dark:focus:ring-amber-400 transition-all">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">⭐ สินค้าแนะนำ</span>
                            </label>

                            <label class="flex items-center gap-3 cursor-pointer group p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                <input type="checkbox" name="track_inventory" value="1" {{ old('track_inventory', $product->track_inventory) ? 'checked' : '' }} class="w-5 h-5 rounded border-2 border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 transition-all">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">📊 ติดตามสต็อก</span>
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Quick Actions --}}
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-slate-700 dark:to-slate-800 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Quick Actions</h3>
                    </div>

                    <div class="p-6 space-y-3">
                        {{-- Toggle Featured --}}
                        <form action="{{ route('admin.official-shop.products.toggle-featured', $product) }}" method="POST" class="inline w-full">
                            @csrf
                            <button type="submit" class="w-full px-4 py-2 bg-amber-100 hover:bg-amber-200 dark:bg-amber-900/30 dark:hover:bg-amber-900/50 text-amber-700 dark:text-amber-300 rounded-lg font-medium transition flex items-center justify-center gap-2">
                                @if($product->is_featured)
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                    ยกเลิกสินค้าแนะนำ
                                @else
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                                    ตั้งเป็นสินค้าแนะนำ
                                @endif
                            </button>
                        </form>

                        {{-- Toggle Active --}}
                        <form action="{{ route('admin.official-shop.products.toggle-active', $product) }}" method="POST" class="inline w-full">
                            @csrf
                            <button type="submit" class="w-full px-4 py-2 {{ $product->is_active ? 'bg-red-100 hover:bg-red-200 dark:bg-red-900/30 dark:hover:bg-red-900/50 text-red-700 dark:text-red-300' : 'bg-green-100 hover:bg-green-200 dark:bg-green-900/30 dark:hover:bg-green-900/50 text-green-700 dark:text-green-300' }} rounded-lg font-medium transition flex items-center justify-center gap-2">
                                @if($product->is_active)
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                    ปิดการแสดงสินค้า
                                @else
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    เปิดการแสดงสินค้า
                                @endif
                            </button>
                        </form>

                        {{-- View on Store --}}
                        <a href="{{ route('official-shop.show', $product->slug) }}" target="_blank" class="w-full px-4 py-2 bg-purple-100 hover:bg-purple-200 dark:bg-purple-900/30 dark:hover:bg-purple-900/50 text-purple-700 dark:text-purple-300 rounded-lg font-medium transition flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                            ดูในร้าน Official Shop
                        </a>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="p-6 space-y-3">
                        <button type="submit" class="flex items-center justify-center gap-3 w-full px-6 py-3 bg-gradient-to-r from-amber-500 via-purple-500 to-pink-500 hover:from-amber-600 hover:via-purple-600 hover:to-pink-600 text-white rounded-xl font-bold transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span>บันทึกการเปลี่ยนแปลง</span>
                        </button>

                        <a href="{{ route('admin.official-shop.products.index') }}" class="flex items-center justify-center gap-3 w-full px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-xl font-medium transition-all duration-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                            <span>ยกเลิก</span>
                        </a>
                    </div>
                </div>

                {{-- Delete Section --}}
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-red-200 dark:border-red-800 overflow-hidden" x-data="{ showDeleteModal: false }">
                    <div class="p-6">
                        <h3 class="text-lg font-bold text-red-600 dark:text-red-400 mb-2">Danger Zone</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">การลบสินค้าจะไม่สามารถกู้คืนได้</p>

                        <button type="button" @click="showDeleteModal = true" class="w-full px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            ลบสินค้า
                        </button>

                        {{-- Delete Modal --}}
                        <div x-show="showDeleteModal" x-transition class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
                            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showDeleteModal = false"></div>
                            <div class="flex min-h-screen items-center justify-center p-4">
                                <div class="relative w-full max-w-md bg-white dark:bg-slate-800 rounded-2xl shadow-2xl p-6" @click.away="showDeleteModal = false">
                                    <div class="text-center">
                                        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                                            <svg class="w-8 h-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                            </svg>
                                        </div>
                                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">ยืนยันการลบสินค้า</h3>
                                        <p class="text-gray-600 dark:text-gray-400 mb-6">คุณแน่ใจหรือไม่ว่าต้องการลบสินค้า <strong class="text-gray-900 dark:text-white">{{ $product->name }}</strong>?</p>
                                    </div>
                                    <div class="flex gap-3">
                                        <button type="button" @click="showDeleteModal = false" class="flex-1 px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors font-medium">
                                            ยกเลิก
                                        </button>
                                        <form action="{{ route('admin.official-shop.products.destroy', $product) }}" method="POST" class="flex-1">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="w-full px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl transition-colors font-bold">
                                                ยืนยันลบ
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
