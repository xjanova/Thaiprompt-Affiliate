{{--
    Storefront Index - สไตล์ AliExpress Premium

    หน้าร้านค้าหลักแบบ AliExpress ที่สวยงามระดับหลายล้าน
    รองรับ Dark Mode, Responsive, และ Modern UI

    Features:
    - Mega Menu Navigation
    - Hero Banner Carousel
    - Flash Deals with Countdown
    - Category Showcase
    - Featured Stores
    - Product Grid (AliExpress style)
    - Infinite Scroll / Load More
--}}

@extends('layouts.user-arrow-x')

@section('title', 'ร้านค้าออนไลน์ - สินค้าคุณภาพ ราคาดี ส่งฟรี')

@section('meta')
<meta name="description" content="ช้อปสินค้าคุณภาพหลากหลายหมวดหมู่ ราคาพิเศษ ส่งฟรีทั่วประเทศ Flash Deals ทุกวัน ร้านของระบบและร้านผู้เช่าคุณภาพ">
<meta name="keywords" content="ร้านค้าออนไลน์,ช้อปปิ้ง,สินค้าราคาถูก,ส่งฟรี,flash deals">
@endsection

@section('content')
<div x-data="storefrontManager()"
     x-init="init()"
     class="min-h-screen bg-gray-50 dark:bg-gray-900">

    {{-- ========================================
         TOP NAVIGATION SECTION
         ======================================== --}}
    <div class="sticky top-0 z-50 bg-white dark:bg-gray-800 shadow-md">
        <div class="container mx-auto px-4">
            <div class="flex items-center gap-4 py-3">
                {{-- Mega Menu --}}
                <x-storefront.mega-menu :categories="$categories" />

                {{-- Search Bar --}}
                <div class="flex-1 relative">
                    <div class="relative max-w-2xl">
                        <input type="text"
                               x-model="searchQuery"
                               @keyup.enter="search()"
                               @input.debounce.300ms="showSuggestions()"
                               placeholder="ค้นหาสินค้า ร้านค้า หรือหมวดหมู่..."
                               class="w-full pl-12 pr-32 py-3.5
                                     bg-gray-100 dark:bg-gray-700
                                     border-2 border-transparent
                                     focus:border-orange-500 focus:bg-white dark:focus:bg-gray-800
                                     rounded-xl
                                     text-gray-900 dark:text-gray-100
                                     font-medium
                                     transition-all duration-300
                                     placeholder:text-gray-400 dark:placeholder:text-gray-500">

                        <div class="absolute left-4 top-1/2 -translate-y-1/2">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>

                        <button @click="search()"
                                class="absolute right-2 top-1/2 -translate-y-1/2
                                      px-6 py-2
                                      bg-gradient-to-r from-orange-500 to-red-500
                                      hover:from-orange-600 hover:to-red-600
                                      text-white font-bold rounded-lg
                                      transition-all transform hover:scale-105">
                            ค้นหา
                        </button>
                    </div>

                    {{-- Search Suggestions Dropdown --}}
                    <div x-show="suggestions.length > 0"
                         x-transition
                         @click.away="suggestions = []"
                         class="absolute top-full left-0 right-0 mt-2 max-w-2xl
                               bg-white dark:bg-gray-800 rounded-xl shadow-2xl
                               border border-gray-100 dark:border-gray-700
                               overflow-hidden z-50">
                        <template x-for="suggestion in suggestions" :key="suggestion.id">
                            <a :href="`{{ route('shop.show', '') }}/${suggestion.slug}`"
                               class="flex items-center gap-3 px-4 py-3
                                     hover:bg-gray-50 dark:hover:bg-gray-700
                                     transition-colors">
                                <img :src="suggestion.main_image_url || 'https://via.placeholder.com/50'"
                                     :alt="suggestion.name"
                                     class="w-10 h-10 rounded-lg object-cover">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate"
                                       x-text="suggestion.name"></p>
                                    <p class="text-sm text-orange-600 dark:text-orange-400 font-bold"
                                       x-text="`฿${suggestion.price.toLocaleString()}`"></p>
                                </div>
                            </a>
                        </template>
                    </div>
                </div>

                {{-- Quick Links --}}
                <div class="hidden lg:flex items-center gap-4">
                    <a href="{{ route('storefront.index', ['sort_by' => 'popular']) }}"
                       class="text-sm font-semibold text-gray-600 dark:text-gray-400
                             hover:text-orange-600 dark:hover:text-orange-400 transition-colors">
                        ยอดนิยม
                    </a>
                    <a href="{{ route('storefront.index', ['filter' => 'official']) }}"
                       class="text-sm font-semibold text-gray-600 dark:text-gray-400
                             hover:text-orange-600 dark:hover:text-orange-400 transition-colors">
                        Official
                    </a>
                    <a href="{{ route('storefront.index', ['sort_by' => 'newest']) }}"
                       class="text-sm font-semibold text-gray-600 dark:text-gray-400
                             hover:text-orange-600 dark:hover:text-orange-400 transition-colors">
                        สินค้าใหม่
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- ========================================
         HERO SECTION - Banner + Side Panels
         ======================================== --}}
    <div class="container mx-auto px-4 py-6">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
            {{-- Main Banner Carousel --}}
            <div class="lg:col-span-3">
                <x-storefront.banner-carousel
                    :banners="$banners"
                    :autoPlayInterval="5000"
                    height="h-[350px] md:h-[400px] lg:h-[450px]" />
            </div>

            {{-- Side Panels --}}
            <div class="hidden lg:flex flex-col gap-4">
                {{-- User Welcome Card --}}
                @auth
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-lg
                           border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center gap-3 mb-3">
                        <img src="{{ auth()->user()->avatar_url ?? 'https://via.placeholder.com/50' }}"
                             alt="{{ auth()->user()->name }}"
                             class="w-12 h-12 rounded-full object-cover ring-2 ring-orange-500">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">ยินดีต้อนรับ</p>
                            <p class="font-bold text-gray-900 dark:text-white">{{ auth()->user()->name }}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <a href="{{ route('user.orders.index') }}"
                           class="text-center py-2 bg-gray-100 dark:bg-gray-700 rounded-lg text-xs font-medium
                                 hover:bg-orange-100 dark:hover:bg-orange-900/30 transition-colors">
                            คำสั่งซื้อ
                        </a>
                        <a href="{{ route('user.wishlist') }}"
                           class="text-center py-2 bg-gray-100 dark:bg-gray-700 rounded-lg text-xs font-medium
                                 hover:bg-orange-100 dark:hover:bg-orange-900/30 transition-colors">
                            รายการโปรด
                        </a>
                    </div>
                </div>
                @else
                <div class="bg-gradient-to-br from-orange-500 to-red-500 rounded-2xl p-4 text-white">
                    <h3 class="font-bold text-lg mb-2">เข้าสู่ระบบ</h3>
                    <p class="text-sm text-white/80 mb-3">รับสิทธิพิเศษมากมายเมื่อเป็นสมาชิก</p>
                    <div class="flex gap-2">
                        <a href="{{ route('login') }}"
                           class="flex-1 py-2 bg-white text-orange-600 rounded-lg text-sm font-bold text-center
                                 hover:bg-gray-100 transition-colors">
                            เข้าสู่ระบบ
                        </a>
                        <a href="{{ route('register') }}"
                           class="flex-1 py-2 bg-orange-600 rounded-lg text-sm font-bold text-center
                                 hover:bg-orange-700 transition-colors">
                            สมัครสมาชิก
                        </a>
                    </div>
                </div>
                @endauth

                {{-- Promo Cards --}}
                <div class="bg-gradient-to-br from-purple-600 to-pink-600 rounded-2xl p-4 text-white">
                    <div class="flex items-center gap-2 mb-2">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5 2a2 2 0 00-2 2v14l3.5-2 3.5 2 3.5-2 3.5 2V4a2 2 0 00-2-2H5zm2.5 3a1.5 1.5 0 100 3 1.5 1.5 0 000-3zm6.207.293a1 1 0 00-1.414 0l-6 6a1 1 0 101.414 1.414l6-6a1 1 0 000-1.414zM12.5 10a1.5 1.5 0 100 3 1.5 1.5 0 000-3z" clip-rule="evenodd"/>
                        </svg>
                        <span class="font-bold">คูปองส่วนลด</span>
                    </div>
                    <p class="text-3xl font-black mb-1">฿100</p>
                    <p class="text-sm text-white/80 mb-3">เมื่อซื้อครบ ฿1,000</p>
                    <button class="w-full py-2 bg-white text-purple-600 rounded-lg font-bold text-sm
                                 hover:bg-purple-100 transition-colors">
                        รับคูปอง
                    </button>
                </div>

                {{-- Quick Stats --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-lg
                           border border-gray-100 dark:border-gray-700">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="text-center">
                            <p class="text-2xl font-black text-orange-600 dark:text-orange-400">
                                {{ number_format($stats['all'] ?? 0) }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">สินค้าทั้งหมด</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-black text-green-600 dark:text-green-400">
                                {{ number_format($stats['stores'] ?? 0) }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">ร้านค้าคุณภาพ</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ========================================
         FLASH DEALS SECTION
         ======================================== --}}
    @if($flashDeals && $flashDeals->count() > 0)
    <div class="container mx-auto px-4 py-6">
        <x-storefront.flash-deals
            :products="$flashDeals"
            :endTime="$flashDealEndTime ?? now()->addHours(6)->toIso8601String()"
            title="Flash Deals" />
    </div>
    @endif

    {{-- ========================================
         CATEGORY SHOWCASE SECTION
         ======================================== --}}
    @if($categories && $categories->count() > 0)
    <div class="container mx-auto px-4">
        <x-storefront.category-showcase
            :categories="$categories"
            title="ช้อปตามหมวดหมู่"
            :limit="8" />
    </div>
    @endif

    {{-- ========================================
         FEATURED STORES SECTION
         ======================================== --}}
    @if(($featuredStores && $featuredStores->count() > 0) || true)
    <div class="container mx-auto px-4">
        <x-storefront.featured-stores
            :stores="$featuredStores ?? collect()"
            :showOfficial="true"
            title="ร้านค้าแนะนำ" />
    </div>
    @endif

    {{-- ========================================
         TABS SECTION - Shop by Type
         ======================================== --}}
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl overflow-hidden
                   border border-gray-100 dark:border-gray-700">

            {{-- Tabs Header --}}
            <div class="flex items-center border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                <button @click="activeTab = 'all'"
                        :class="activeTab === 'all'
                               ? 'bg-white dark:bg-gray-800 text-orange-600 dark:text-orange-400 border-b-2 border-orange-500'
                               : 'text-gray-600 dark:text-gray-400 hover:text-orange-600'"
                        class="flex-1 md:flex-none px-8 py-4 font-bold text-sm transition-all">
                    <span class="flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                        </svg>
                        ทั้งหมด
                        <span class="px-2 py-0.5 bg-gray-200 dark:bg-gray-700 rounded-full text-xs">
                            {{ number_format($stats['all'] ?? 0) }}
                        </span>
                    </span>
                </button>

                <button @click="activeTab = 'official'"
                        :class="activeTab === 'official'
                               ? 'bg-white dark:bg-gray-800 text-orange-600 dark:text-orange-400 border-b-2 border-orange-500'
                               : 'text-gray-600 dark:text-gray-400 hover:text-orange-600'"
                        class="flex-1 md:flex-none px-8 py-4 font-bold text-sm transition-all">
                    <span class="flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        Official Store
                        <span class="px-2 py-0.5 bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400 rounded-full text-xs">
                            {{ number_format($stats['official'] ?? 0) }}
                        </span>
                    </span>
                </button>

                <button @click="activeTab = 'premium'"
                        :class="activeTab === 'premium'
                               ? 'bg-white dark:bg-gray-800 text-orange-600 dark:text-orange-400 border-b-2 border-orange-500'
                               : 'text-gray-600 dark:text-gray-400 hover:text-orange-600'"
                        class="flex-1 md:flex-none px-8 py-4 font-bold text-sm transition-all">
                    <span class="flex items-center justify-center gap-2">
                        <svg class="w-5 h-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        ร้าน 5 ดาว
                        <span class="px-2 py-0.5 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400 rounded-full text-xs">
                            {{ number_format($stats['premium'] ?? 0) }}
                        </span>
                    </span>
                </button>

                {{-- Sort Dropdown --}}
                <div class="ml-auto hidden md:flex items-center gap-2 px-4">
                    <label class="text-sm text-gray-500 dark:text-gray-400">เรียงตาม:</label>
                    <select x-model="sortBy"
                            @change="applyFilters()"
                            class="px-3 py-2 bg-white dark:bg-gray-700
                                  border border-gray-200 dark:border-gray-600
                                  rounded-lg text-sm font-medium
                                  focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                        <option value="newest">ใหม่ล่าสุด</option>
                        <option value="popular">ยอดนิยม</option>
                        <option value="price_low">ราคาต่ำ-สูง</option>
                        <option value="price_high">ราคาสูง-ต่ำ</option>
                        <option value="rating">คะแนนสูงสุด</option>
                    </select>
                </div>
            </div>

            {{-- Products Content --}}
            <div class="p-4 md:p-6">
                <x-storefront.product-grid-aliexpress
                    :products="$products"
                    columns="auto"
                    :showPv="true"
                    :showCommission="auth()->check()" />

                {{-- Pagination / Load More --}}
                @if($products->hasPages())
                <div class="mt-8 flex justify-center">
                    <div class="inline-flex items-center gap-2">
                        {{-- Previous --}}
                        @if($products->onFirstPage())
                        <span class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-400 rounded-lg cursor-not-allowed">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </span>
                        @else
                        <a href="{{ $products->previousPageUrl() }}"
                           class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700
                                 text-gray-700 dark:text-gray-300 rounded-lg
                                 hover:bg-orange-50 dark:hover:bg-orange-900/20 hover:border-orange-500
                                 transition-all">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </a>
                        @endif

                        {{-- Page Numbers --}}
                        @foreach($products->getUrlRange(1, $products->lastPage()) as $page => $url)
                            @if($page == $products->currentPage())
                            <span class="px-4 py-2 bg-gradient-to-r from-orange-500 to-red-500
                                       text-white font-bold rounded-lg shadow">
                                {{ $page }}
                            </span>
                            @elseif($page == 1 || $page == $products->lastPage() || abs($page - $products->currentPage()) <= 2)
                            <a href="{{ $url }}"
                               class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700
                                     text-gray-700 dark:text-gray-300 rounded-lg
                                     hover:bg-orange-50 dark:hover:bg-orange-900/20 hover:border-orange-500
                                     transition-all">
                                {{ $page }}
                            </a>
                            @elseif(abs($page - $products->currentPage()) == 3)
                            <span class="px-2 text-gray-400">...</span>
                            @endif
                        @endforeach

                        {{-- Next --}}
                        @if($products->hasMorePages())
                        <a href="{{ $products->nextPageUrl() }}"
                           class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700
                                 text-gray-700 dark:text-gray-300 rounded-lg
                                 hover:bg-orange-50 dark:hover:bg-orange-900/20 hover:border-orange-500
                                 transition-all">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                        @else
                        <span class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-400 rounded-lg cursor-not-allowed">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </span>
                        @endif
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ========================================
         BENEFITS SECTION
         ======================================== --}}
    <div class="container mx-auto px-4 py-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            {{-- Free Shipping --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 text-center
                       shadow-lg border border-gray-100 dark:border-gray-700
                       hover:shadow-xl hover:-translate-y-1 transition-all">
                <div class="w-16 h-16 mx-auto mb-4 rounded-2xl
                           bg-gradient-to-br from-green-400 to-emerald-500
                           flex items-center justify-center shadow-lg">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                    </svg>
                </div>
                <h3 class="font-bold text-gray-900 dark:text-white mb-1">ส่งฟรีทั่วไทย</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">เมื่อซื้อครบ ฿500</p>
            </div>

            {{-- Quality Guarantee --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 text-center
                       shadow-lg border border-gray-100 dark:border-gray-700
                       hover:shadow-xl hover:-translate-y-1 transition-all">
                <div class="w-16 h-16 mx-auto mb-4 rounded-2xl
                           bg-gradient-to-br from-blue-400 to-indigo-500
                           flex items-center justify-center shadow-lg">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <h3 class="font-bold text-gray-900 dark:text-white mb-1">รับประกันคุณภาพ</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">สินค้าของแท้ 100%</p>
            </div>

            {{-- Easy Returns --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 text-center
                       shadow-lg border border-gray-100 dark:border-gray-700
                       hover:shadow-xl hover:-translate-y-1 transition-all">
                <div class="w-16 h-16 mx-auto mb-4 rounded-2xl
                           bg-gradient-to-br from-orange-400 to-red-500
                           flex items-center justify-center shadow-lg">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </div>
                <h3 class="font-bold text-gray-900 dark:text-white mb-1">เปลี่ยนคืนได้</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">ภายใน 7 วัน</p>
            </div>

            {{-- Secure Payment --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 text-center
                       shadow-lg border border-gray-100 dark:border-gray-700
                       hover:shadow-xl hover:-translate-y-1 transition-all">
                <div class="w-16 h-16 mx-auto mb-4 rounded-2xl
                           bg-gradient-to-br from-purple-400 to-pink-500
                           flex items-center justify-center shadow-lg">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <h3 class="font-bold text-gray-900 dark:text-white mb-1">ชำระเงินปลอดภัย</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">ระบบความปลอดภัยสูง</p>
            </div>
        </div>
    </div>

    {{-- ========================================
         NEWSLETTER SECTION
         ======================================== --}}
    <div class="container mx-auto px-4 py-8">
        <div class="relative overflow-hidden rounded-3xl
                   bg-gradient-to-br from-orange-500 via-red-500 to-pink-600
                   p-8 md:p-12">
            {{-- Background Pattern --}}
            <div class="absolute inset-0 opacity-20">
                <div class="absolute inset-0" style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 40px 40px;"></div>
            </div>

            <div class="relative z-10 max-w-3xl mx-auto text-center">
                <h2 class="text-3xl md:text-4xl font-black text-white mb-4">
                    รับข่าวสารและโปรโมชั่นพิเศษ
                </h2>
                <p class="text-white/90 text-lg mb-6">
                    สมัครรับข่าวสารเพื่อรับส่วนลดพิเศษและโปรโมชั่นก่อนใคร
                </p>

                <form @submit.prevent="subscribeNewsletter()" class="flex flex-col sm:flex-row gap-3 max-w-lg mx-auto">
                    <input type="email"
                           x-model="newsletterEmail"
                           placeholder="กรอกอีเมลของคุณ"
                           class="flex-1 px-6 py-4 rounded-xl
                                 bg-white/20 backdrop-blur-lg
                                 border-2 border-white/30
                                 text-white placeholder-white/70
                                 focus:bg-white/30 focus:border-white/50
                                 transition-all">
                    <button type="submit"
                            class="px-8 py-4 bg-white text-orange-600 font-bold rounded-xl
                                  shadow-lg hover:shadow-xl
                                  transform hover:scale-105
                                  transition-all">
                        สมัครรับข่าวสาร
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- ========================================
     Alpine.js Component
     ======================================== --}}
@push('scripts')
<script>
/**
 * Storefront Manager - จัดการหน้าร้านค้าหลัก
 *
 * ใช้ Alpine.js สำหรับจัดการ state และ interactions
 */
function storefrontManager() {
    return {
        // State
        searchQuery: '{{ request("search") }}',
        suggestions: [],
        activeTab: '{{ request("shop_type", "all") }}',
        sortBy: '{{ request("sort_by", "newest") }}',
        newsletterEmail: '',

        /**
         * เริ่มต้น component
         */
        init() {
            console.log('Storefront Manager initialized');

            // Listen for tab changes
            this.$watch('activeTab', (value) => {
                this.applyFilters();
            });
        },

        /**
         * ค้นหาสินค้า
         */
        search() {
            const params = new URLSearchParams();
            if (this.searchQuery) params.set('search', this.searchQuery);
            if (this.activeTab !== 'all') params.set('shop_type', this.activeTab);
            if (this.sortBy) params.set('sort_by', this.sortBy);

            window.location.href = '{{ route("storefront.index") }}?' + params.toString();
        },

        /**
         * แสดง suggestions ขณะพิมพ์
         */
        async showSuggestions() {
            if (this.searchQuery.length < 2) {
                this.suggestions = [];
                return;
            }

            try {
                const response = await fetch(`{{ route('storefront.search') }}?q=${encodeURIComponent(this.searchQuery)}`);
                const data = await response.json();
                this.suggestions = data;
            } catch (error) {
                console.error('Error fetching suggestions:', error);
            }
        },

        /**
         * ใช้ตัวกรอง
         */
        applyFilters() {
            const params = new URLSearchParams(window.location.search);

            if (this.activeTab === 'all') {
                params.delete('shop_type');
            } else {
                params.set('shop_type', this.activeTab);
            }

            params.set('sort_by', this.sortBy);

            window.location.href = '{{ route("storefront.index") }}?' + params.toString();
        },

        /**
         * สมัครรับข่าวสาร
         */
        async subscribeNewsletter() {
            if (!this.newsletterEmail) {
                alert('กรุณากรอกอีเมล');
                return;
            }

            try {
                // TODO: API call to subscribe
                alert('ขอบคุณที่สมัครรับข่าวสาร!');
                this.newsletterEmail = '';
            } catch (error) {
                console.error('Error:', error);
                alert('เกิดข้อผิดพลาด กรุณาลองใหม่');
            }
        }
    };
}
</script>
@endpush

@push('styles')
<style>
/* Custom Animations */
@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-slide-up {
    animation: slideUp 0.5s ease-out forwards;
}
</style>
@endpush
@endsection
