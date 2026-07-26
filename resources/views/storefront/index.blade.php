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

@extends('layouts.storefront')

@section('title', 'ร้านค้าออนไลน์ - สินค้าคุณภาพ ราคาดี ส่งฟรี')

@section('meta')
<meta name="description" content="ช้อปสินค้าคุณภาพหลากหลายหมวดหมู่ ราคาพิเศษ ส่งฟรีทั่วประเทศ Flash Deals ทุกวัน ร้านของระบบและร้านผู้เช่าคุณภาพ">
<meta name="keywords" content="ร้านค้าออนไลน์,ช้อปปิ้ง,สินค้าราคาถูก,ส่งฟรี,flash deals">
@endsection

{{-- 🌈 ลาวาแลมป์ระดับทั้งหน้า — ใช้ slot ที่ layout เตรียมไว้ให้แล้ว
     วางตรึงหน้าจอ (fixed) หลังเนื้อหาทั้งหมด → เห็นตั้งแต่เปิดหน้ามาวินาทีแรก
     (ของเดิมใส่ไว้แค่หลังกริดสินค้าซึ่งอยู่ลึกลงไป ~1,500px จึงไม่เห็นถ้าไม่เลื่อน) --}}
@section('lava-background')
<div class="tp-lava-page" aria-hidden="true">
    <span class="tp-lava__blob tp-lava__blob--1"></span>
    <span class="tp-lava__blob tp-lava__blob--2"></span>
    <span class="tp-lava__blob tp-lava__blob--3"></span>
    <span class="tp-lava__blob tp-lava__blob--4"></span>
    <span class="tp-lava__blob tp-lava__blob--5"></span>
</div>
@endsection

@section('content')
<div x-data="storefrontManager()"
     x-init="init()"
     class="min-h-screen">

    {{-- ========================================
         TOP NAVIGATION SECTION
         ======================================== --}}
    <div class="sticky top-0 z-50 bg-white/80 dark:bg-gray-800/80 backdrop-blur-lg shadow-md border-b border-white/20 dark:border-gray-700/50">
        <div class="container mx-auto px-4">
            <div class="flex items-center gap-4 py-3">
                {{-- Logo --}}
                @php
                    $themeSetting = \App\Models\ThemeSetting::active();
                    $logoPath = $themeSetting && $themeSetting->logo_path
                        ? asset('storage/' . $themeSetting->logo_path)
                        : asset('images/logo.png');
                @endphp
                <a href="{{ route('storefront.index') }}" class="flex-shrink-0">
                    <img src="{{ $logoPath }}"
                         alt="{{ config('app.name') }}"
                         class="h-10 w-auto object-contain"
                         onerror="this.onerror=null; this.src='{{ asset('images/logo.png') }}';">
                </a>

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
                            <a :href="`{{ url('shop') }}/${suggestion.slug}`"
                               class="flex items-center gap-3 px-4 py-3
                                     hover:bg-gray-50 dark:hover:bg-gray-700
                                     transition-colors">
                                {{-- รูปสำรองใช้ไฟล์ในเครื่อง (via.placeholder.com ใช้งานไม่ได้แล้ว) --}}
                                <img :src="suggestion.main_image_url || '{{ asset('images/no-image.png') }}'"
                                     :alt="suggestion.name"
                                     class="w-10 h-10 rounded-lg object-cover">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate"
                                       x-text="suggestion.name"></p>
                                    {{-- ราคาที่ส่งมาจาก API เป็น decimal string ต้องแปลงเป็นตัวเลขก่อนจัดรูปแบบ --}}
                                    <p class="text-sm text-orange-600 dark:text-orange-400 font-bold"
                                       x-text="`฿${Number(suggestion.price ?? 0).toLocaleString()}`"></p>
                                </div>
                            </a>
                        </template>
                    </div>
                </div>

                {{-- Quick Filter Links --}}
                <div class="hidden lg:flex items-center gap-4">
                    <a href="{{ route('storefront.index', ['sort_by' => 'popular']) }}"
                       class="text-sm font-semibold transition-colors
                             {{ request('sort_by') === 'popular' ? 'text-orange-600 dark:text-orange-400' : 'text-gray-600 dark:text-gray-400 hover:text-orange-600 dark:hover:text-orange-400' }}">
                        <i class="fas fa-fire-alt mr-1"></i>
                        ยอดนิยม
                    </a>
                    <a href="{{ route('official-shop.index') }}"
                       class="text-sm font-semibold transition-colors
                             text-gray-600 dark:text-gray-400 hover:text-orange-600 dark:hover:text-orange-400">
                        <i class="fas fa-check-circle mr-1"></i>
                        ร้านทางการ
                    </a>
                    <a href="{{ route('storefront.index', ['sort_by' => 'newest']) }}"
                       class="text-sm font-semibold transition-colors
                             {{ request('sort_by') === 'newest' ? 'text-orange-600 dark:text-orange-400' : 'text-gray-600 dark:text-gray-400 hover:text-orange-600 dark:hover:text-orange-400' }}">
                        <i class="fas fa-star mr-1"></i>
                        สินค้าใหม่
                    </a>
                </div>

                {{-- Right Side Actions: Cart, Dark Mode, User --}}
                <div class="flex items-center gap-3 ml-auto">
                    {{-- Dark Mode Toggle --}}
                    <button @click="toggleDarkMode()"
                            type="button"
                            class="p-2.5 rounded-xl bg-gray-100 dark:bg-gray-700
                                   hover:bg-gray-200 dark:hover:bg-gray-600
                                   text-gray-600 dark:text-gray-300
                                   transition-all hover:scale-105"
                            title="สลับโหมดมืด/สว่าง">
                        <svg class="w-5 h-5 hidden dark:block" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd"/>
                        </svg>
                        <svg class="w-5 h-5 block dark:hidden" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/>
                        </svg>
                    </button>

                    {{-- Cart Button with Drawer --}}
                    <x-storefront.cart-drawer />

                    {{-- User Menu --}}
                    @auth
                    <div x-data="{ userMenuOpen: false }" class="relative">
                        <button @click="userMenuOpen = !userMenuOpen"
                                type="button"
                                class="flex items-center gap-2 p-2 pr-3 rounded-xl
                                       bg-gray-100 dark:bg-gray-700
                                       hover:bg-gray-200 dark:hover:bg-gray-600
                                       transition-all hover:scale-105">
                            <img src="{{ auth()->user()->profile_picture_url }}"
                                 alt="{{ auth()->user()->name }}"
                                 class="w-8 h-8 rounded-lg object-cover"
                                 onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name={{ urlencode(substr(auth()->user()->name, 0, 1)) }}&background=F59E0B&color=fff&size=64';">
                            <span class="hidden md:block text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ auth()->user()->name }}
                            </span>
                            <svg class="w-4 h-4 text-gray-400 transition-transform duration-200"
                                 :class="userMenuOpen && 'rotate-180'"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        {{-- User Dropdown --}}
                        <div x-show="userMenuOpen"
                             x-cloak
                             @click.outside="userMenuOpen = false"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             class="absolute top-full right-0 mt-2 w-56
                                    bg-white dark:bg-gray-800
                                    rounded-xl shadow-xl
                                    border border-gray-100 dark:border-gray-700
                                    overflow-hidden z-50">
                            <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ auth()->user()->email }}</p>
                            </div>
                            <div class="py-2">
                                <a href="{{ route('user.dashboard') }}"
                                   class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300
                                          hover:bg-gray-100 dark:hover:bg-gray-700">
                                    <i class="fas fa-tachometer-alt w-5"></i>
                                    แดชบอร์ด
                                </a>
                                <a href="{{ route('orders.index') }}"
                                   class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300
                                          hover:bg-gray-100 dark:hover:bg-gray-700">
                                    <i class="fas fa-shopping-bag w-5"></i>
                                    คำสั่งซื้อของฉัน
                                </a>
                                <a href="{{ route('user.profile') }}"
                                   class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300
                                          hover:bg-gray-100 dark:hover:bg-gray-700">
                                    <i class="fas fa-user-circle w-5"></i>
                                    โปรไฟล์
                                </a>
                            </div>
                            <div class="py-2 border-t border-gray-100 dark:border-gray-700">
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button type="submit"
                                            class="flex items-center gap-3 w-full px-4 py-2 text-sm text-red-600 dark:text-red-400
                                                   hover:bg-red-50 dark:hover:bg-red-900/20">
                                        <i class="fas fa-sign-out-alt w-5"></i>
                                        ออกจากระบบ
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    @else
                    <div class="flex items-center gap-2">
                        <a href="{{ route('login') }}"
                           class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300
                                  hover:text-orange-600 dark:hover:text-orange-400 transition-colors">
                            เข้าสู่ระบบ
                        </a>
                        <a href="{{ route('register') }}"
                           class="px-4 py-2 bg-gradient-to-r from-orange-500 to-red-500
                                  hover:from-orange-600 hover:to-red-600
                                  text-white text-sm font-bold rounded-lg
                                  transition-all hover:scale-105">
                            สมัครสมาชิก
                        </a>
                    </div>
                    @endauth
                </div>
            </div>
        </div>
    </div>

    {{-- ========================================
         HERO SECTION - Banner + Side Panels
         ⚠️ โชว์เฉพาะ "หน้าแรก" เท่านั้น
         พอเลือกหมวด/ค้นหา แบนเนอร์ใหญ่ 350-450px จะดันสินค้าที่ลูกค้าขอดูลงไปนอกจอ
         ======================================== --}}
    @if($browseMode === 'home')
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
                        <img src="{{ auth()->user()->profile_picture_url }}"
                             alt="{{ auth()->user()->name }}"
                             class="w-12 h-12 rounded-full object-cover ring-2 ring-orange-500"
                             onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name={{ urlencode(substr(auth()->user()->name, 0, 1)) }}&background=F59E0B&color=fff&size=96';">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">ยินดีต้อนรับ</p>
                            <p class="font-bold text-gray-900 dark:text-white">{{ auth()->user()->name }}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <a href="{{ route('orders.index') }}"
                           class="text-center py-2 bg-gray-100 dark:bg-gray-700 rounded-lg text-xs font-medium
                                 hover:bg-orange-100 dark:hover:bg-orange-900/30 transition-colors">
                            คำสั่งซื้อ
                        </a>
                        <a href="{{ route('user.dashboard') }}"
                           class="text-center py-2 bg-gray-100 dark:bg-gray-700 rounded-lg text-xs font-medium
                                 hover:bg-orange-100 dark:hover:bg-orange-900/30 transition-colors">
                            แดชบอร์ด
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
                    {{--
                        ไม่ประกาศมูลค่า/เงื่อนไขคูปองตายตัวที่หน้านี้
                        เพราะคอนโทรลเลอร์ไม่ได้ส่งข้อมูลคูปองมา (ตัวเลขเดิม ฿100/฿1,000 เป็นข้อความหลอก)
                        ลิงก์ไปหน้าคูปองจริงของระบบแทนปุ่มที่ไม่มี handler
                    --}}
                    <p class="text-sm text-white/80 mb-3">เก็บคูปองส่วนลดจากร้านค้าที่ร่วมรายการ</p>
                    <a href="{{ auth()->check() ? route('user.coupons.available') : route('login') }}"
                       class="block w-full py-2 bg-white text-purple-600 rounded-lg font-bold text-sm text-center
                             hover:bg-purple-100 transition-colors">
                        ดูคูปองที่รับได้
                    </a>
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
    @endif

    {{-- หัวหมวด — แทนที่แบนเนอร์ใหญ่เมื่อเลือกดูหมวด --}}
    @if($browseMode === 'browse' && $activeCategory)
        <x-storefront.category-hero
            :category="$activeCategory"
            :cover="$categoryCover"
            :total="method_exists($products, 'total') ? $products->total() : $products->count()" />
    @endif

    {{-- แบนเนอร์โปรโมทรงเตี้ย — โหมดเลือกดูยังโปรโมได้ แต่ไม่แย่งที่สินค้า --}}
    @if($browseMode === 'browse' && $banners && count($banners) > 0)
    <div class="container mx-auto px-4 pb-2">
        <x-storefront.banner-carousel
            :banners="$banners"
            :autoPlayInterval="7000"
            height="h-[110px] md:h-[140px]" />
    </div>
    @endif

    {{-- ========================================
         FLASH DEALS SECTION
         ======================================== --}}
    @if($browseMode === 'home' && $flashDeals && $flashDeals->count() > 0)
    <div class="container mx-auto px-4 py-6">
        <x-storefront.flash-deals
            :products="$flashDeals"
            :endTime="$flashDealEndTime ?? now()->addHours(6)->toIso8601String()"
            title="Flash Deals" />
    </div>
    @endif

    {{-- ========================================
         CATEGORY SHOWCASE SECTION
         โชว์เฉพาะหน้าแรก — ตอนเลือกหมวดแล้วมีชิปหมวดย่อยในหัวหมวดแทนแล้ว
         ======================================== --}}
    @if($browseMode === 'home' && $categories && $categories->count() > 0)
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
    {{--
        ไม่ต้องมีเงื่อนไขครอบ: คอมโพเนนต์ featured-stores แสดงการ์ด "ร้านทางการ" เสมอเมื่อ showOfficial=true
        (เงื่อนไขเดิม `... || true` เป็นเงื่อนไขตายที่เป็นจริงตลอด จึงตัดทิ้ง)
    --}}
    <div class="container mx-auto px-4">
        <x-storefront.featured-stores
            :stores="$featuredStores ?? collect()"
            :showOfficial="true"
            title="ร้านค้าแนะนำ" />
    </div>

    {{-- ========================================
         ACTIVE FILTERS INDICATOR
         ======================================== --}}
    @if(request('tag') || request('search') || request('category'))
    <div class="container mx-auto px-4 py-4">
        <div class="flex items-center flex-wrap gap-3">
            <span class="text-sm text-gray-500 dark:text-gray-400">กำลังกรอง:</span>

            @if(request('tag'))
            <span class="inline-flex items-center gap-2 px-4 py-2
                        bg-gradient-to-r from-orange-100 to-red-100 dark:from-orange-900/30 dark:to-red-900/30
                        text-orange-700 dark:text-orange-300 text-sm font-semibold
                        rounded-full border border-orange-200 dark:border-orange-700">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M17.707 9.293a1 1 0 010 1.414l-7 7a1 1 0 01-1.414 0l-7-7A.997.997 0 012 10V5a3 3 0 013-3h5c.256 0 .512.098.707.293l7 7zM5 6a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                </svg>
                แท็ก: {{ is_scalar(request('tag')) ? request('tag') : '' }}
                <a href="{{ route('storefront.index', array_filter(request()->except('tag'))) }}"
                   class="ml-1 hover:text-red-600 transition-colors">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </a>
            </span>
            @endif

            @if(request('search'))
            <span class="inline-flex items-center gap-2 px-4 py-2
                        bg-blue-100 dark:bg-blue-900/30
                        text-blue-700 dark:text-blue-300 text-sm font-semibold
                        rounded-full border border-blue-200 dark:border-blue-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                ค้นหา: "{{ is_scalar(request('search')) ? request('search') : '' }}"
                <a href="{{ route('storefront.index', array_filter(request()->except('search'))) }}"
                   class="ml-1 hover:text-red-600 transition-colors">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </a>
            </span>
            @endif

            @if(request('category'))
            <span class="inline-flex items-center gap-2 px-4 py-2
                        bg-purple-100 dark:bg-purple-900/30
                        text-purple-700 dark:text-purple-300 text-sm font-semibold
                        rounded-full border border-purple-200 dark:border-purple-700">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6z"/>
                </svg>
                หมวดหมู่: {{ is_scalar(request('category')) ? request('category') : '' }}
                <a href="{{ route('storefront.index', array_filter(request()->except('category'))) }}"
                   class="ml-1 hover:text-red-600 transition-colors">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </a>
            </span>
            @endif

            <a href="{{ route('storefront.index') }}"
               class="text-sm text-red-600 dark:text-red-400 hover:underline font-medium ml-2">
                ล้างตัวกรองทั้งหมด
            </a>
        </div>
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
                        ร้านทางการ
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
                        {{-- สถิติ premium นับ "สินค้า" ที่ rating_average >= 4.5 ไม่ใช่จำนวนร้าน จึงใช้ป้ายตามความจริง --}}
                        สินค้าเรตติ้งสูง
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

            {{-- Products Content with Infinite Scroll --}}
            {{-- tp-lava-wrap = ฉากหลังลาวาแลมป์สีสด ทำให้การ์ดกระจกมีสีให้สะท้อน --}}
            <div class="tp-lava-wrap p-4 md:p-6"
                 x-data="infiniteProducts()"
                 x-init="init()">

                {{-- ก้อนสีลอยช้าๆ (ตกแต่งล้วน คลิกทะลุได้ ไม่กินอีเวนต์) --}}
                <div class="tp-lava" aria-hidden="true">
                    <span class="tp-lava__blob tp-lava__blob--1"></span>
                    <span class="tp-lava__blob tp-lava__blob--2"></span>
                    <span class="tp-lava__blob tp-lava__blob--3"></span>
                    <span class="tp-lava__blob tp-lava__blob--4"></span>
                    <span class="tp-lava__blob tp-lava__blob--5"></span>
                </div>

                {{-- Initial Products Grid --}}
                <div id="products-container" class="tp-lava-content">
                    <x-storefront.product-grid-aliexpress
                        :products="$products"
                        columns="auto"
                        :showPv="true"
                        :showCommission="auth()->check()" />
                </div>

                {{-- Additional Products (loaded via infinite scroll) --}}
                <div id="additional-products"
                     class="tp-lava-content grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3 md:gap-4 mt-4">
                    <template x-for="product in additionalProducts" :key="product.id">
                        <div class="group">
                            {{-- ต้องใช้คลาสชุดเดียวกับการ์ดหน้าแรก (tp-glass/tp-3d/tp-sheen)
                                 ไม่งั้นสินค้าที่โหลดเพิ่มจะหน้าตาคนละแบบกับของเดิม --}}
                            <a :href="product.url"
                               :target="product.is_affiliate ? '_blank' : null"
                               :rel="product.is_affiliate ? 'noopener nofollow sponsored' : null"
                               class="tp-glass tp-3d tp-sheen
                                     block rounded-xl md:rounded-2xl overflow-hidden
                                     hover:border-orange-300/70 dark:hover:border-orange-500/40">

                                {{-- Product Image --}}
                                <div class="relative aspect-square overflow-hidden bg-gray-100 dark:bg-gray-700">
                                    <img :src="product.main_image_url"
                                         :alt="product.name"
                                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                         loading="lazy">

                                    {{-- Badges --}}
                                    <div class="absolute top-2 left-2 flex flex-col gap-1 z-10">
                                        <template x-if="product.discount > 0">
                                            <span class="px-2 py-0.5 bg-red-500 text-white text-xs font-bold rounded"
                                                  x-text="`-${product.discount}%`"></span>
                                        </template>
                                        <template x-if="product.is_official">
                                            <span class="px-2 py-0.5 bg-gradient-to-r from-orange-500 to-red-500
                                                       text-white text-xs font-bold rounded flex items-center gap-0.5">
                                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                </svg>
                                                ทางการ
                                            </span>
                                        </template>
                                        <template x-if="product.is_featured">
                                            <span class="px-2 py-0.5 bg-gradient-to-r from-yellow-400 to-orange-400
                                                       text-white text-xs font-bold rounded">ขายดี</span>
                                        </template>
                                    </div>

                                    {{-- Free Shipping --}}
                                    <template x-if="product.free_shipping">
                                        <div class="absolute bottom-0 left-0 right-0
                                                   bg-gradient-to-r from-green-500 to-emerald-500
                                                   text-white text-xs font-semibold py-1 px-2 text-center">
                                            <span class="flex items-center justify-center gap-1">
                                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/>
                                                    <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z"/>
                                                </svg>
                                                ส่งฟรี
                                            </span>
                                        </div>
                                    </template>
                                </div>

                                {{-- Product Info --}}
                                <div class="p-2 md:p-3">
                                    <h3 class="text-xs md:text-sm font-medium text-gray-800 dark:text-gray-200
                                              line-clamp-2 mb-1.5 min-h-[2.5rem]
                                              group-hover:text-orange-600 dark:group-hover:text-orange-400
                                              transition-colors leading-tight"
                                        x-text="product.name"></h3>

                                    {{--
                                        ราคาจาก API เป็น decimal string ("1250.00") ต้องแปลงด้วย Number() ก่อน
                                        ทั้งตอนจัดรูปแบบและตอนเปรียบเทียบ (ไม่งั้น "9.00" > "10.00" จะเป็นจริงแบบผิด ๆ)
                                    --}}
                                    <div class="flex items-baseline gap-1.5 mb-1.5">
                                        <span class="text-sm md:text-lg font-bold text-red-600 dark:text-red-500"
                                              x-text="`฿${Number(product.price ?? 0).toLocaleString()}`"></span>
                                        <template x-if="product.compare_at_price && Number(product.compare_at_price) > Number(product.price ?? 0)">
                                            <span class="text-xs text-gray-400 line-through"
                                                  x-text="`฿${Number(product.compare_at_price).toLocaleString()}`"></span>
                                        </template>
                                    </div>

                                    <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 mb-1.5">
                                        <template x-if="product.rating_average > 0">
                                            <div class="flex items-center gap-0.5">
                                                <svg class="w-3 h-3 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                </svg>
                                                {{-- rating_average เป็น decimal string จึงต้อง Number() ก่อนเรียก toFixed --}}
                                                <span x-text="Number(product.rating_average ?? 0).toFixed(1)"></span>
                                            </div>
                                        </template>
                                        <template x-if="product.sales_count > 0">
                                            <span x-text="`${Number(product.sales_count ?? 0).toLocaleString()}+ ขายแล้ว`"></span>
                                        </template>
                                    </div>

                                    {{-- PV Badge --}}
                                    <template x-if="product.pv > 0">
                                        <div class="flex items-center gap-1 text-xs">
                                            <span class="px-1.5 py-0.5 bg-yellow-100 dark:bg-yellow-900/30
                                                       text-yellow-700 dark:text-yellow-400
                                                       rounded font-semibold"
                                                  x-text="`PV: ${Number(product.pv ?? 0).toLocaleString()}`"></span>
                                        </div>
                                    </template>
                                </div>
                            </a>
                        </div>
                    </template>
                </div>

                {{-- Load More / Infinite Scroll Trigger --}}
                <div class="mt-8 flex flex-col items-center gap-4"
                     x-intersect:enter.margin.300px="!isLoading && hasMore && loadMore()">

                    {{-- Loading Indicator --}}
                    <div x-show="isLoading" class="flex items-center gap-3">
                        <svg class="animate-spin h-6 w-6 text-orange-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-gray-600 dark:text-gray-400 font-medium">กำลังโหลดสินค้าเพิ่มเติม...</span>
                    </div>

                    {{-- Load More Button (fallback) --}}
                    <button x-show="!isLoading && hasMore"
                            @click="loadMore()"
                            class="px-8 py-3 bg-gradient-to-r from-orange-500 to-red-500
                                  hover:from-orange-600 hover:to-red-600
                                  text-white font-bold rounded-xl
                                  shadow-lg hover:shadow-xl
                                  transform hover:scale-105
                                  transition-all duration-300">
                        <span class="flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                            โหลดสินค้าเพิ่มเติม
                        </span>
                    </button>

                    {{-- End of Products --}}
                    <div x-show="!hasMore && totalProducts > 0" class="text-center py-4">
                        <div class="inline-flex items-center gap-2 px-6 py-3 bg-gray-100 dark:bg-gray-800
                                   text-gray-600 dark:text-gray-400 rounded-xl">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>แสดงสินค้าทั้งหมด <span x-text="totalProducts"></span> รายการแล้ว</span>
                        </div>
                    </div>

                    {{-- Products Count --}}
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        กำลังแสดง <span class="font-bold text-gray-700 dark:text-gray-300" x-text="displayedCount"></span>
                        จาก <span class="font-bold text-gray-700 dark:text-gray-300" x-text="totalProducts"></span> รายการ
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ========================================
         BENEFITS SECTION
         โชว์เฉพาะหน้าแรก — คนที่กำลังเลือกสินค้าไม่ต้องอ่านข้อดีเว็บซ้ำทุกหน้า
         ======================================== --}}
    @if($browseMode === 'home')
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
    @endif

    {{-- ========================================
         NEWSLETTER SECTION
         ======================================== --}}
    @if($browseMode === 'home')
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

                {{-- ข้อความแจ้งสถานะตามจริง (ยังไม่มีปลายทางรับอีเมล จึงไม่แสดงว่าสมัครสำเร็จ) --}}
                <p x-show="newsletterMessage"
                   x-cloak
                   x-transition
                   x-text="newsletterMessage"
                   role="status"
                   aria-live="polite"
                   class="mt-4 text-sm font-medium text-white/95
                         bg-black/20 dark:bg-black/30 backdrop-blur-sm
                         rounded-xl px-4 py-3 max-w-lg mx-auto"></p>
            </div>
        </div>
    </div>
    @endif
</div>

{{-- ========================================
     Alpine.js Component
     ======================================== --}}
@push('scripts')
@php
    // ค่าตั้งต้นจาก query string — บังคับให้เป็น string เสมอ
    // กันกรณีส่งมาเป็น array (เช่น ?search[]=a) ซึ่งจะทำให้ชนิดข้อมูลใน Alpine เพี้ยน
    $initialSearchQuery = is_scalar(request('search')) ? (string) request('search') : '';
    $initialShopType = is_scalar(request('shop_type')) ? (string) request('shop_type') : 'all';
    $initialSortBy = is_scalar(request('sort_by')) ? (string) request('sort_by') : 'newest';
@endphp
<script>
/**
 * Storefront Manager - จัดการหน้าร้านค้าหลัก
 *
 * ใช้ Alpine.js สำหรับจัดการ state และ interactions
 */
function storefrontManager() {
    return {
        // State
        // ⚠️ ต้องใช้ Js::from() ทุกค่าที่มาจาก request()
        // ถ้าฝัง string ตรง ๆ ใน '...' แล้วมี ' หรือ \ ในคิวรี จะทำให้ JS syntax error
        // และ x-data ทั้งก้อนพัง (ค้นหา/แท็บ/เรียงลำดับ/โหมดมืด/สมัครข่าวสาร ตายทั้งหมด)
        searchQuery: {{ Js::from($initialSearchQuery) }},
        suggestions: [],
        activeTab: {{ Js::from($initialShopType) }},
        sortBy: {{ Js::from($initialSortBy) }},
        newsletterEmail: '',
        newsletterMessage: '',

        /**
         * เริ่มต้น component
         */
        init() {
            console.log('Storefront Manager initialized');

            // Listen for tab changes
            this.$watch('activeTab', (value) => {
                this.applyFilters();
            });

            // Initialize dark mode จาก localStorage
            this.initDarkMode();
        },

        /**
         * Initialize dark mode
         */
        initDarkMode() {
            if (localStorage.getItem('theme') === 'dark' ||
                (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        },

        /**
         * Toggle dark mode
         */
        toggleDarkMode() {
            document.documentElement.classList.toggle('dark');
            localStorage.setItem('theme', document.documentElement.classList.contains('dark') ? 'dark' : 'light');
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
         *
         * ⚠️ ในระบบยังไม่มี route/ตารางสำหรับเก็บอีเมลรับข่าวสาร
         * จึงไม่ยิง API มั่ว และไม่แจ้งว่า "สมัครสำเร็จ" ทั้งที่ไม่ได้บันทึกอะไรเลย
         * แจ้งสถานะตามจริงแบบ inline แทน alert() และไม่ล้างช่องกรอกทิ้ง
         */
        subscribeNewsletter() {
            const email = (this.newsletterEmail || '').trim();

            if (!email) {
                this.newsletterMessage = 'กรุณากรอกอีเมลของคุณ';
                return;
            }

            // ตรวจรูปแบบอีเมลอย่างง่ายก่อนแจ้งสถานะ
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                this.newsletterMessage = 'รูปแบบอีเมลไม่ถูกต้อง กรุณาตรวจสอบอีกครั้ง';
                return;
            }

            this.newsletterMessage = 'ขออภัย ระบบรับข่าวสารทางอีเมลยังไม่เปิดให้บริการ ' +
                'ระหว่างนี้ติดตามโปรโมชั่นและ Flash Deals ได้ที่หน้าร้านค้าโดยตรง';
        }
    };
}

/**
 * Infinite Products - จัดการ Infinite Scroll สำหรับสินค้า
 *
 * ใช้ Alpine.js + Intersection Observer สำหรับ lazy loading
 */
function infiniteProducts() {
    return {
        // State
        additionalProducts: [],
        currentPage: {{ $products->currentPage() }},
        lastPage: {{ $products->lastPage() }},
        totalProducts: {{ $products->total() }},
        initialCount: {{ $products->count() }},
        isLoading: false,
        hasMore: {{ $products->hasMorePages() ? 'true' : 'false' }},

        // Computed
        get displayedCount() {
            return this.initialCount + this.additionalProducts.length;
        },

        /**
         * เริ่มต้น component
         */
        init() {
            console.log('Infinite Products initialized', {
                currentPage: this.currentPage,
                lastPage: this.lastPage,
                totalProducts: this.totalProducts
            });
        },

        /**
         * โหลดสินค้าเพิ่มเติม
         */
        async loadMore() {
            if (this.isLoading || !this.hasMore) return;

            this.isLoading = true;
            const nextPage = this.currentPage + 1;

            try {
                // สร้าง URL พร้อม parameters ที่มีอยู่
                const params = new URLSearchParams(window.location.search);
                params.set('page', nextPage);

                const response = await fetch(`{{ route('storefront.products') }}?${params.toString()}`);
                const data = await response.json();

                if (data.products && data.products.length > 0) {
                    // เพิ่มสินค้าใหม่ลงใน array
                    this.additionalProducts = [...this.additionalProducts, ...data.products];
                    this.currentPage = data.current_page;
                    this.hasMore = data.has_more;
                    this.totalProducts = data.total;

                    console.log(`Loaded page ${nextPage}, ${data.products.length} products`);
                } else {
                    this.hasMore = false;
                }
            } catch (error) {
                console.error('Error loading more products:', error);
                // แสดง notification ถ้ามี
                if (window.showNotification) {
                    window.showNotification('ไม่สามารถโหลดสินค้าเพิ่มเติมได้', 'error');
                }
            } finally {
                this.isLoading = false;
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
