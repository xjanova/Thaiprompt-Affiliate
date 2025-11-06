@extends('layouts.app')

@section('title', 'ร้านค้าออนไลน์ - ช้อปสินค้าคุณภาพ ราคาดี')

@section('meta')
<meta name="description" content="ช้อปสินค้าคุณภาพหลากหลายหมวดหมู่ ราคาพิเศษ ส่งฟรีทั่วประเทศ">
@endsection

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-indigo-50/30">

    <!-- Premium Hero Banner -->
    <div class="relative overflow-hidden bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600">
        <!-- Animated Background Pattern -->
        <div class="absolute inset-0 opacity-10">
            <div class="absolute inset-0" style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 40px 40px;"></div>
        </div>

        <div class="container mx-auto px-4 py-12 md:py-20 relative z-10">
            <div class="max-w-4xl mx-auto text-center text-white">
                <div class="inline-flex items-center gap-2 bg-white/20 backdrop-blur-lg px-4 py-2 rounded-full mb-6 border border-white/30">
                    <svg class="w-5 h-5 text-yellow-300" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                    <span class="font-semibold">ร้านค้าออนไลน์คุณภาพพรีเมี่ยม</span>
                </div>

                <h1 class="text-4xl md:text-6xl font-black mb-4 tracking-tight drop-shadow-lg">
                    ค้นพบสินค้าที่ใช่สำหรับคุณ
                </h1>
                <p class="text-xl md:text-2xl text-purple-100 mb-8 font-medium">
                    สินค้าคุณภาพหลากหลาย ราคาดีที่สุด ส่งฟรีทั่วไทย
                </p>

                <!-- Search Bar -->
                <div class="max-w-2xl mx-auto">
                    <form method="GET" action="{{ route('shop.index') }}" class="relative">
                        <input type="text"
                               name="search"
                               value="{{ request('search') }}"
                               placeholder="ค้นหาสินค้า ยี่ห้อ หรือหมวดหมู่..."
                               class="w-full px-6 py-4 pr-14 rounded-2xl text-gray-900 font-medium shadow-2xl focus:outline-none focus:ring-4 focus:ring-white/50 transition-all">
                        <button type="submit"
                                class="absolute right-2 top-1/2 -translate-y-1/2 p-3 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white rounded-xl shadow-lg transition-all transform hover:scale-105">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </button>
                    </form>
                </div>

                <!-- Quick Stats -->
                <div class="flex flex-wrap gap-4 justify-center mt-8">
                    <div class="flex items-center gap-2 bg-white/20 backdrop-blur-lg px-4 py-2 rounded-xl border border-white/30">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/>
                            <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z"/>
                        </svg>
                        <span class="font-semibold">ส่งฟรีเมื่อซื้อครบ 500฿</span>
                    </div>
                    <div class="flex items-center gap-2 bg-white/20 backdrop-blur-lg px-4 py-2 rounded-xl border border-white/30">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="font-semibold">ของแท้ 100%</span>
                    </div>
                    <div class="flex items-center gap-2 bg-white/20 backdrop-blur-lg px-4 py-2 rounded-xl border border-white/30">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                        </svg>
                        <span class="font-semibold">จัดส่งรวดเร็ว 1-2 วัน</span>
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

        <!-- Categories Quick Nav (Only show when no filter applied) -->
        @if($categories && $categories->count() > 0 && !request()->hasAny(['search', 'category', 'min_price', 'max_price', 'brand', 'sort_by']))
        <div class="mb-8">
            <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                        <svg class="w-6 h-6 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/>
                        </svg>
                        หมวดหมู่สินค้า
                    </h2>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
                    @foreach($categories->take(12) as $category)
                        <a href="{{ route('shop.category', $category->slug) }}"
                           class="group flex flex-col items-center gap-3 p-4 rounded-xl bg-gradient-to-br from-gray-50 to-white hover:from-indigo-50 hover:to-purple-50 border-2 border-gray-100 hover:border-indigo-300 transition-all duration-300 transform hover:scale-105 hover:shadow-lg">
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-100 to-purple-100 flex items-center justify-center text-2xl group-hover:scale-110 transition-transform">
                                📦
                            </div>
                            <span class="text-sm font-semibold text-gray-700 group-hover:text-indigo-600 text-center transition-colors">
                                {{ $category->name }}
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        <div class="flex flex-col lg:flex-row gap-6">

            <!-- Advanced Filters Sidebar -->
            <aside class="lg:w-80 flex-shrink-0">
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden sticky top-4">
                    <!-- Filters Header -->
                    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-6 py-4">
                        <h2 class="text-lg font-bold flex items-center gap-2">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clip-rule="evenodd"/>
                            </svg>
                            ตัวกรองขั้นสูง
                        </h2>
                    </div>

                    <form method="GET" action="{{ route('shop.index') }}" id="filterForm" class="p-6 space-y-6">

                        <!-- Search -->
                        <div>
                            <label class="block text-sm font-bold text-gray-900 mb-2 flex items-center gap-2">
                                <svg class="w-4 h-4 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/>
                                </svg>
                                ค้นหาสินค้า
                            </label>
                            <input type="text"
                                   name="search"
                                   value="{{ request('search') }}"
                                   placeholder="ชื่อสินค้า, SKU, ยี่ห้อ..."
                                   class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 transition-all placeholder-gray-400">
                        </div>

                        <!-- Category Filter -->
                        @if($categories && $categories->count() > 0)
                        <div>
                            <label class="block text-sm font-bold text-gray-900 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/>
                                </svg>
                                หมวดหมู่
                            </label>
                            <div class="space-y-2 max-h-64 overflow-y-auto pr-2 custom-scrollbar">
                                @foreach($categories as $category)
                                <label class="flex items-center gap-3 cursor-pointer hover:bg-indigo-50 p-3 rounded-xl transition-all group">
                                    <input type="radio"
                                           name="category"
                                           value="{{ $category->slug }}"
                                           {{ request('category') === $category->slug ? 'checked' : '' }}
                                           onchange="document.getElementById('filterForm').submit()"
                                           class="w-5 h-5 text-indigo-600 focus:ring-indigo-500 focus:ring-2">
                                    <span class="text-sm font-medium text-gray-700 group-hover:text-indigo-600 flex-1">
                                        {{ $category->name }}
                                    </span>
                                </label>
                                @endforeach
                                @if(request('category'))
                                <button type="button"
                                        onclick="document.querySelector('input[name=category]:checked').checked = false; document.getElementById('filterForm').submit()"
                                        class="w-full text-sm text-indigo-600 hover:text-indigo-800 font-semibold py-2 hover:bg-indigo-50 rounded-lg transition-all">
                                    ล้างหมวดหมู่
                                </button>
                                @endif
                            </div>
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
                                <input type="number"
                                       name="min_price"
                                       value="{{ request('min_price') }}"
                                       placeholder="ราคาต่ำสุด"
                                       min="0"
                                       class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 transition-all placeholder-gray-400">
                                <div class="flex items-center justify-center">
                                    <div class="h-0.5 w-4 bg-gray-300"></div>
                                </div>
                                <input type="number"
                                       name="max_price"
                                       value="{{ request('max_price') }}"
                                       placeholder="ราคาสูงสุด"
                                       min="0"
                                       class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 transition-all placeholder-gray-400">
                            </div>
                        </div>

                        <!-- Brand Filter -->
                        @if($brands && $brands->count() > 0)
                        <div>
                            <label class="block text-sm font-bold text-gray-900 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                ยี่ห้อ
                            </label>
                            <select name="brand"
                                    onchange="document.getElementById('filterForm').submit()"
                                    class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 transition-all font-medium">
                                <option value="">ทุกยี่ห้อ</option>
                                @foreach($brands as $brand)
                                <option value="{{ $brand }}" {{ request('brand') === $brand ? 'selected' : '' }}>
                                    {{ $brand }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        <!-- Filter Buttons -->
                        <div class="flex flex-col gap-3 pt-4 border-t-2 border-gray-100">
                            <button type="submit"
                                    class="w-full px-6 py-3.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition-all transform hover:scale-105 flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/>
                                </svg>
                                ค้นหาด้วยตัวกรอง
                            </button>

                            @if(request()->hasAny(['search', 'category', 'min_price', 'max_price', 'brand']))
                            <a href="{{ route('shop.index') }}"
                               class="w-full px-6 py-3 border-2 border-gray-300 hover:border-red-500 text-gray-700 hover:text-red-500 font-bold rounded-xl transition-all flex items-center justify-center gap-2 bg-white hover:bg-red-50">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                                ล้างตัวกรองทั้งหมด
                            </a>
                            @endif
                        </div>
                    </form>
                </div>
            </aside>

            <!-- Products Section -->
            <div class="flex-1 min-w-0">

                <!-- Featured Products Carousel -->
                @if($featuredProducts && $featuredProducts->count() > 0 && !request()->hasAny(['search', 'category', 'min_price', 'max_price', 'brand', 'sort_by']))
                <div class="mb-8">
                    <div class="bg-gradient-to-r from-amber-50 via-yellow-50 to-orange-50 rounded-2xl shadow-lg p-6 border-2 border-amber-200">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-2xl font-black text-gray-900 flex items-center gap-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-amber-400 to-orange-500 rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                </div>
                                สินค้าแนะนำพิเศษ
                            </h2>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                            @foreach($featuredProducts as $product)
                                <x-shop.product-card :product="$product" :featured="true" />
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif

                <!-- Sort and Results Header -->
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

                        <form method="GET" action="{{ route('shop.index') }}" class="flex items-center gap-3">
                            @foreach(request()->except(['sort_by', 'page']) as $key => $value)
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endforeach

                            <label class="text-sm font-bold text-gray-700 whitespace-nowrap flex items-center gap-2">
                                <svg class="w-4 h-4 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M3 3a1 1 0 000 2h11a1 1 0 100-2H3zM3 7a1 1 0 000 2h7a1 1 0 100-2H3zM3 11a1 1 0 100 2h4a1 1 0 100-2H3zM15 8a1 1 0 10-2 0v5.586l-1.293-1.293a1 1 0 00-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L15 13.586V8z"/>
                                </svg>
                                เรียงตาม:
                            </label>
                            <select name="sort_by"
                                    onchange="this.form.submit()"
                                    class="px-4 py-2.5 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 font-semibold text-gray-700 transition-all">
                                <option value="newest" {{ request('sort_by') === 'newest' ? 'selected' : '' }}>ใหม่ล่าสุด</option>
                                <option value="popular" {{ request('sort_by') === 'popular' ? 'selected' : '' }}>ยอดนิยม</option>
                                <option value="price_low" {{ request('sort_by') === 'price_low' ? 'selected' : '' }}>ราคาต่ำ-สูง</option>
                                <option value="price_high" {{ request('sort_by') === 'price_high' ? 'selected' : '' }}>ราคาสูง-ต่ำ</option>
                                <option value="rating" {{ request('sort_by') === 'rating' ? 'selected' : '' }}>คะแนนสูงสุด</option>
                            </select>
                        </form>
                    </div>
                </div>

                <!-- Products Grid -->
                @if($products->count() > 0)
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-8">
                    @foreach($products as $product)
                        <x-shop.product-card :product="$product" />
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="flex justify-center">
                    <div class="bg-white rounded-2xl shadow-lg p-4 border border-gray-100">
                        {{ $products->appends(request()->query())->links() }}
                    </div>
                </div>
                @else
                <!-- Empty State -->
                <div class="bg-white rounded-2xl shadow-lg p-12 text-center border border-gray-100">
                    <div class="inline-flex items-center justify-center w-24 h-24 bg-gradient-to-br from-gray-100 to-gray-200 rounded-full mb-6">
                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">ไม่พบสินค้าที่คุณค้นหา</h3>
                    <p class="text-gray-600 mb-6 max-w-md mx-auto">
                        ลองปรับเปลี่ยนเงื่อนไขการค้นหา หรือลองค้นหาด้วยคำค้นอื่น
                    </p>
                    <a href="{{ route('shop.index') }}"
                       class="inline-flex items-center gap-2 px-8 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm0 6a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zm11-1a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z" clip-rule="evenodd"/>
                        </svg>
                        ดูสินค้าทั้งหมด
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
/* Custom Scrollbar */
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #c7d2fe;
    border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #a5b4fc;
}
</style>
@endsection
