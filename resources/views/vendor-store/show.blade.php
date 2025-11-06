@extends('layouts.app')

@section('title', $store->store_name)

@section('meta')
<meta name="description" content="{{ $store->store_description ?? 'ร้านค้าออนไลน์' }}">
@endsection

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-indigo-50/30">

    <!-- Premium Store Header -->
    <div class="relative overflow-hidden bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600">
        <!-- Background Pattern -->
        @if($store->store_banner)
            <div class="absolute inset-0">
                <img src="{{ $store->banner_url }}" alt="{{ $store->store_name }} Banner"
                     class="w-full h-full object-cover opacity-20">
            </div>
        @else
            <div class="absolute inset-0 opacity-10">
                <div class="absolute inset-0" style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 40px 40px;"></div>
            </div>
        @endif

        <div class="container mx-auto px-4 py-12 md:py-20 relative z-10">
            <div class="max-w-5xl mx-auto">
                <div class="flex flex-col md:flex-row items-center gap-8 text-white">
                    <!-- Store Logo -->
                    @if($store->store_logo)
                        <div class="w-32 h-32 md:w-40 md:h-40 bg-white/20 backdrop-blur-lg rounded-3xl shadow-2xl p-4 border-4 border-white/30 flex-shrink-0">
                            <img src="{{ $store->logo_url }}" alt="{{ $store->store_name }}" class="w-full h-full object-contain">
                        </div>
                    @else
                        <div class="w-32 h-32 md:w-40 md:h-40 bg-white/20 backdrop-blur-lg rounded-3xl shadow-2xl flex items-center justify-center text-6xl md:text-7xl border-4 border-white/30 flex-shrink-0">
                            🏪
                        </div>
                    @endif

                    <!-- Store Info -->
                    <div class="flex-1 text-center md:text-left">
                        @if($store->is_verified)
                        <div class="inline-flex items-center gap-2 bg-emerald-500/80 backdrop-blur-lg px-4 py-2 rounded-full mb-4 border border-white/30">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="font-bold">ร้านค้ายืนยันตัวตน</span>
                        </div>
                        @endif

                        <h1 class="text-4xl md:text-6xl font-black mb-4 tracking-tight drop-shadow-lg">
                            {{ $store->store_name }}
                        </h1>
                        @if($store->store_description)
                            <p class="text-xl md:text-2xl text-purple-100 mb-6 font-medium">
                                {{ $store->store_description }}
                            </p>
                        @endif

                        <!-- Stats -->
                        <div class="flex flex-wrap gap-3 justify-center md:justify-start">
                            <div class="flex items-center gap-2 bg-white/20 backdrop-blur-lg px-4 py-2 rounded-xl border border-white/30">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/>
                                </svg>
                                <span class="font-bold">{{ $stats['total_products'] }} สินค้า</span>
                            </div>
                            <div class="flex items-center gap-2 bg-white/20 backdrop-blur-lg px-4 py-2 rounded-xl border border-white/30">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/>
                                    <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z"/>
                                </svg>
                                <span class="font-bold">{{ $stats['total_sales'] }} ยอดขาย</span>
                            </div>
                            @if($stats['rating_count'] > 0)
                                <div class="flex items-center gap-2 bg-white/20 backdrop-blur-lg px-4 py-2 rounded-xl border border-white/30">
                                    <svg class="w-5 h-5 text-yellow-300" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                    <span class="font-bold">{{ number_format($stats['rating'], 1) }} ({{ $stats['rating_count'] }})</span>
                                </div>
                            @endif
                        </div>

                        <!-- Social Links -->
                        @if($store->facebook_url || $store->line_oa_id || $store->instagram_url || $store->twitter_url || $store->tiktok_url)
                            <div class="flex gap-2 mt-6 justify-center md:justify-start">
                                @if($store->facebook_url)
                                    <a href="{{ $store->facebook_url }}" target="_blank"
                                       class="w-10 h-10 bg-white/20 hover:bg-white/30 backdrop-blur-lg rounded-xl flex items-center justify-center transition-all transform hover:scale-110 border border-white/30">
                                        <span class="text-xl">📘</span>
                                    </a>
                                @endif
                                @if($store->line_oa_id)
                                    <a href="https://line.me/R/ti/p/{{ ltrim($store->line_oa_id, '@') }}" target="_blank"
                                       class="w-10 h-10 bg-white/20 hover:bg-white/30 backdrop-blur-lg rounded-xl flex items-center justify-center transition-all transform hover:scale-110 border border-white/30">
                                        <span class="text-xl">💚</span>
                                    </a>
                                @endif
                                @if($store->instagram_url)
                                    <a href="{{ $store->instagram_url }}" target="_blank"
                                       class="w-10 h-10 bg-white/20 hover:bg-white/30 backdrop-blur-lg rounded-xl flex items-center justify-center transition-all transform hover:scale-110 border border-white/30">
                                        <span class="text-xl">📷</span>
                                    </a>
                                @endif
                                @if($store->twitter_url)
                                    <a href="{{ $store->twitter_url }}" target="_blank"
                                       class="w-10 h-10 bg-white/20 hover:bg-white/30 backdrop-blur-lg rounded-xl flex items-center justify-center transition-all transform hover:scale-110 border border-white/30">
                                        <span class="text-xl">🐦</span>
                                    </a>
                                @endif
                                @if($store->tiktok_url)
                                    <a href="{{ $store->tiktok_url }}" target="_blank"
                                       class="w-10 h-10 bg-white/20 hover:bg-white/30 backdrop-blur-lg rounded-xl flex items-center justify-center transition-all transform hover:scale-110 border border-white/30">
                                        <span class="text-xl">🎵</span>
                                    </a>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Wave Divider -->
        <div class="absolute bottom-0 left-0 right-0">
            <svg viewBox="0 0 1440 48" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full">
                <path d="M0 48h1440V24C1440 24 1200 0 720 0S0 24 0 24v24z" fill="white"/>
            </svg>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8 -mt-6 relative z-10">
        <div class="flex flex-col lg:flex-row gap-6">

            <!-- Filters Sidebar -->
            <aside class="lg:w-80 flex-shrink-0">
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden sticky top-4">
                    <!-- Filters Header -->
                    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-6 py-4">
                        <h2 class="text-lg font-bold flex items-center gap-2">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clip-rule="evenodd"/>
                            </svg>
                            ค้นหา & กรอง
                        </h2>
                    </div>

                    <form method="GET" action="{{ route('vendor.store.show', $store->store_slug) }}" class="p-6 space-y-6">
                        <!-- Search -->
                        <div>
                            <label class="block text-sm font-bold text-gray-900 mb-2 flex items-center gap-2">
                                <svg class="w-4 h-4 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/>
                                </svg>
                                ค้นหาสินค้า
                            </label>
                            <input type="text" name="search" value="{{ request('search') }}"
                                   placeholder="ชื่อสินค้า..."
                                   class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 transition-all placeholder-gray-400">
                        </div>

                        <!-- Categories -->
                        @if($categories->count() > 0)
                            <div>
                                <label class="block text-sm font-bold text-gray-900 mb-3 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/>
                                    </svg>
                                    หมวดหมู่
                                </label>
                                <select name="category" class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 transition-all font-medium">
                                    <option value="">ทั้งหมด</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->slug }}" {{ request('category') == $category->slug ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <!-- Price Range -->
                        <div>
                            <label class="block text-sm font-bold text-gray-900 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/>
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/>
                                </svg>
                                ช่วงราคา (฿)
                            </label>
                            <div class="space-y-3">
                                <input type="number" name="min_price" value="{{ request('min_price') }}"
                                       placeholder="ต่ำสุด"
                                       class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 transition-all placeholder-gray-400">
                                <div class="flex items-center justify-center">
                                    <div class="h-0.5 w-4 bg-gray-300"></div>
                                </div>
                                <input type="number" name="max_price" value="{{ request('max_price') }}"
                                       placeholder="สูงสุด"
                                       class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 transition-all placeholder-gray-400">
                            </div>
                        </div>

                        <!-- Sort -->
                        <div>
                            <label class="block text-sm font-bold text-gray-900 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M3 3a1 1 0 000 2h11a1 1 0 100-2H3zM3 7a1 1 0 000 2h7a1 1 0 100-2H3zM3 11a1 1 0 100 2h4a1 1 0 100-2H3zM15 8a1 1 0 10-2 0v5.586l-1.293-1.293a1 1 0 00-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L15 13.586V8z"/>
                                </svg>
                                เรียงตาม
                            </label>
                            <select name="sort" class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 transition-all font-medium">
                                <option value="latest" {{ request('sort') == 'latest' ? 'selected' : '' }}>ล่าสุด</option>
                                <option value="popular" {{ request('sort') == 'popular' ? 'selected' : '' }}>ยอดนิยม</option>
                                <option value="price_low" {{ request('sort') == 'price_low' ? 'selected' : '' }}>ราคาต่ำ-สูง</option>
                                <option value="price_high" {{ request('sort') == 'price_high' ? 'selected' : '' }}>ราคาสูง-ต่ำ</option>
                                <option value="rating" {{ request('sort') == 'rating' ? 'selected' : '' }}>คะแนนสูงสุด</option>
                                <option value="name" {{ request('sort') == 'name' ? 'selected' : '' }}>ชื่อ A-Z</option>
                            </select>
                        </div>

                        <!-- Buttons -->
                        <div class="flex flex-col gap-3 pt-4 border-t-2 border-gray-100">
                            <button type="submit" class="w-full px-6 py-3.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition-all transform hover:scale-105 flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/>
                                </svg>
                                ค้นหา
                            </button>
                            @if(request()->hasAny(['search', 'category', 'min_price', 'max_price']))
                            <a href="{{ route('vendor.store.show', $store->store_slug) }}"
                               class="w-full px-6 py-3 border-2 border-gray-300 hover:border-red-500 text-gray-700 hover:text-red-500 font-bold rounded-xl transition-all flex items-center justify-center gap-2 bg-white hover:bg-red-50">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                                ล้างตัวกรอง
                            </a>
                            @endif
                        </div>
                    </form>
                </div>
            </aside>

            <!-- Products Grid -->
            <main class="flex-1 min-w-0">
                <!-- Results Header -->
                <div class="bg-white rounded-2xl shadow-lg p-6 mb-6 border border-gray-100">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-gradient-to-br from-indigo-100 to-purple-100 rounded-xl flex items-center justify-center">
                                <span class="text-xl font-black text-indigo-600">{{ $products->total() }}</span>
                            </div>
                            <div>
                                <div class="text-gray-900 font-bold">
                                    พบ <span class="text-indigo-600">{{ number_format($products->total()) }}</span> สินค้า
                                </div>
                                <div class="text-sm text-gray-500">
                                    แสดง {{ $products->count() }} รายการ
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @if($products->count() > 0)
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-8">
                        @foreach($products as $product)
                            <x-shop.product-card :product="$product" />
                        @endforeach
                    </div>

                    <!-- Pagination -->
                    @if($products->hasPages())
                        <div class="flex justify-center">
                            <div class="bg-white rounded-2xl shadow-lg p-4 border border-gray-100">
                                {{ $products->links() }}
                            </div>
                        </div>
                    @endif
                @else
                    <!-- Empty State -->
                    <div class="bg-white rounded-2xl shadow-lg p-12 text-center border border-gray-100">
                        <div class="inline-flex items-center justify-center w-24 h-24 bg-gradient-to-br from-gray-100 to-gray-200 rounded-full mb-6">
                            <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-3">ไม่พบสินค้า</h3>
                        <p class="text-gray-600 mb-6 max-w-md mx-auto">
                            {{ request()->hasAny(['search', 'category', 'min_price', 'max_price'])
                                ? 'ไม่พบสินค้าที่ตรงกับเงื่อนไขการค้นหา ลองปรับเปลี่ยนตัวกรอง'
                                : 'ร้านค้านี้ยังไม่มีสินค้า' }}
                        </p>
                        @if(request()->hasAny(['search', 'category', 'min_price', 'max_price']))
                            <a href="{{ route('vendor.store.show', $store->store_slug) }}"
                               class="inline-flex items-center gap-2 px-8 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/>
                                </svg>
                                ดูสินค้าทั้งหมด
                            </a>
                        @endif
                    </div>
                @endif
            </main>
        </div>
    </div>

    <!-- Store Contact Info -->
    @if($store->store_address || $store->store_phone || $store->store_email)
        <div class="bg-white border-t-2 border-gray-100 mt-12">
            <div class="container mx-auto px-4 py-12">
                <div class="max-w-5xl mx-auto">
                    <h3 class="text-2xl font-black text-gray-900 mb-8 flex items-center gap-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-500 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        ข้อมูลติดต่อร้านค้า
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        @if($store->store_address)
                            <div class="flex items-start gap-4 p-6 bg-gradient-to-br from-blue-50 to-indigo-50 rounded-2xl border border-blue-100">
                                <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center flex-shrink-0">
                                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <p class="font-bold text-gray-900 mb-2">ที่อยู่</p>
                                    <p class="text-gray-700 text-sm leading-relaxed">
                                        {{ $store->store_address }}
                                        @if($store->store_city), {{ $store->store_city }}@endif
                                        @if($store->store_postal_code) {{ $store->store_postal_code }}@endif
                                    </p>
                                </div>
                            </div>
                        @endif

                        @if($store->store_phone)
                            <div class="flex items-start gap-4 p-6 bg-gradient-to-br from-green-50 to-emerald-50 rounded-2xl border border-green-100">
                                <div class="w-12 h-12 bg-green-600 rounded-xl flex items-center justify-center flex-shrink-0">
                                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <p class="font-bold text-gray-900 mb-2">โทรศัพท์</p>
                                    <a href="tel:{{ $store->store_phone }}" class="text-green-600 hover:text-green-700 font-semibold text-lg">
                                        {{ $store->store_phone }}
                                    </a>
                                </div>
                            </div>
                        @endif

                        @if($store->store_email)
                            <div class="flex items-start gap-4 p-6 bg-gradient-to-br from-purple-50 to-pink-50 rounded-2xl border border-purple-100">
                                <div class="w-12 h-12 bg-purple-600 rounded-xl flex items-center justify-center flex-shrink-0">
                                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                                        <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <p class="font-bold text-gray-900 mb-2">อีเมล</p>
                                    <a href="mailto:{{ $store->store_email }}" class="text-purple-600 hover:text-purple-700 font-semibold break-all">
                                        {{ $store->store_email }}
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
