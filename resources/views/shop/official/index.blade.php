{{--
    Official Shop Index Page - Ultra Premium Layout V3

    หน้าหลักร้านของระบบ (Official Shop) - ออกแบบใหม่ทั้งหมด
    หรูหรา พรีเมี่ยม ระดับ Luxury Brand

    Features:
    - Ultra Premium Hero with 3D Animation
    - Animated Mesh Gradient Background
    - Floating 3D Elements
    - Glassmorphism Cards
    - Premium Product Showcase
    - Luxury Category Display
    - Interactive Animations
    - Dark Mode Support
--}}

@extends('layouts.storefront')

@section('title', 'Official Shop - สินค้าพรีเมี่ยมจากระบบ')

@section('meta')
<meta name="description" content="Official Shop - สินค้าพรีเมี่ยมคุณภาพระดับโลกจากทางระบบโดยตรง รับประกัน 100% ของแท้ ส่งฟรีทั่วประเทศ">
@endsection

{{-- Premium Animated Lava Background --}}
@section('lava-background')
<div class="lava-background luxury-lava" aria-hidden="true">
    {{-- Luxury blobs - Gold, Rose Gold, Platinum --}}
    <div class="lava-blob luxury-blob-1"></div>
    <div class="lava-blob luxury-blob-2"></div>
    <div class="lava-blob luxury-blob-3"></div>
    <div class="lava-blob luxury-blob-4"></div>
    <div class="lava-blob luxury-blob-5"></div>
    <div class="lava-blob luxury-blob-6"></div>
</div>
@endsection

@section('content')
<div x-data="luxuryShopManager()" x-init="init()" class="min-h-screen">

    {{-- ════════════════════════════════════════════════════
         ✨ ULTRA PREMIUM HERO SECTION
         ════════════════════════════════════════════════════ --}}
    <section class="relative min-h-[90vh] flex items-center overflow-hidden">
        {{-- Animated Mesh Gradient Background --}}
        <div class="absolute inset-0">
            <div class="absolute inset-0 bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900"></div>
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_left,rgba(168,85,247,0.4),transparent_50%)]"></div>
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_bottom_right,rgba(236,72,153,0.3),transparent_50%)]"></div>
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_center,rgba(251,191,36,0.2),transparent_60%)]"></div>
        </div>

        {{-- Animated Particles --}}
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="luxury-particle particle-1"></div>
            <div class="luxury-particle particle-2"></div>
            <div class="luxury-particle particle-3"></div>
            <div class="luxury-particle particle-4"></div>
            <div class="luxury-particle particle-5"></div>
        </div>

        {{-- Floating 3D Elements --}}
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            {{-- Diamond Icon --}}
            <div class="absolute top-20 left-[10%] animate-float-slow opacity-20">
                <i class="fas fa-gem text-8xl text-amber-300"></i>
            </div>
            {{-- Crown Icon --}}
            <div class="absolute top-40 right-[15%] animate-float-medium opacity-20">
                <i class="fas fa-crown text-7xl text-purple-300"></i>
            </div>
            {{-- Star Icons --}}
            <div class="absolute bottom-32 left-[20%] animate-float-fast opacity-15">
                <i class="fas fa-star text-6xl text-pink-300"></i>
            </div>
            <div class="absolute top-1/2 right-[8%] animate-float-slow opacity-15">
                <i class="fas fa-sparkles text-5xl text-amber-200"></i>
            </div>
        </div>

        {{-- Hero Content --}}
        <div class="relative container mx-auto px-4 py-20 z-10">
            <div class="text-center max-w-5xl mx-auto">

                {{-- Premium Badge --}}
                <div class="inline-flex items-center gap-3 px-8 py-4 mb-10
                           bg-gradient-to-r from-amber-500/20 via-purple-500/20 to-pink-500/20
                           backdrop-blur-xl border border-white/20
                           rounded-full shadow-2xl
                           animate-fade-in-up">
                    <div class="relative">
                        <div class="w-14 h-14 rounded-full bg-gradient-to-br from-amber-400 via-amber-500 to-amber-600
                                   flex items-center justify-center shadow-lg
                                   ring-4 ring-amber-400/30">
                            <i class="fas fa-crown text-white text-xl"></i>
                        </div>
                        <div class="absolute -top-1 -right-1 w-5 h-5 bg-green-500 rounded-full
                                   border-2 border-white flex items-center justify-center">
                            <i class="fas fa-check text-white text-[8px]"></i>
                        </div>
                    </div>
                    <div class="text-left">
                        <div class="text-white font-bold text-xl tracking-wide">OFFICIAL SHOP</div>
                        <div class="text-amber-300/90 text-sm font-medium">Premium Quality • 100% Authentic</div>
                    </div>
                </div>

                {{-- Main Title with Gradient --}}
                <h1 class="text-6xl md:text-7xl lg:text-8xl font-black mb-8 leading-tight animate-fade-in-up animation-delay-100">
                    <span class="block text-white mb-2 drop-shadow-2xl">
                        สินค้า
                    </span>
                    <span class="block bg-gradient-to-r from-amber-300 via-yellow-200 to-amber-400
                               bg-clip-text text-transparent
                               drop-shadow-[0_0_40px_rgba(251,191,36,0.5)]">
                        พรีเมี่ยม
                    </span>
                </h1>

                {{-- Subtitle --}}
                <p class="text-xl md:text-2xl text-white/80 mb-12 max-w-2xl mx-auto
                         font-light tracking-wide animate-fade-in-up animation-delay-200">
                    คัดสรรสินค้าคุณภาพระดับพรีเมี่ยมจากทางระบบโดยตรง
                    <br class="hidden md:block">
                    <span class="text-amber-300">ของแท้ 100%</span> •
                    <span class="text-purple-300">ส่งฟรีทั่วไทย</span> •
                    <span class="text-pink-300">รับประกัน 7 วัน</span>
                </p>

                {{-- Premium Search Bar --}}
                <div class="max-w-3xl mx-auto mb-16 animate-fade-in-up animation-delay-300">
                    <div class="relative group">
                        {{-- Glow Effect --}}
                        <div class="absolute -inset-1 bg-gradient-to-r from-amber-500 via-purple-500 to-pink-500
                                   rounded-3xl blur-lg opacity-50 group-hover:opacity-75 transition-opacity"></div>

                        {{-- Search Input --}}
                        <div class="relative flex items-center">
                            <input type="text"
                                   x-model="searchQuery"
                                   @keyup.enter="submitSearch()"
                                   placeholder="ค้นหาสินค้าพรีเมี่ยม..."
                                   class="w-full px-8 py-6 pl-16 pr-48
                                         bg-white/95 dark:bg-gray-900/95
                                         backdrop-blur-xl
                                         border-0
                                         rounded-2xl
                                         text-gray-900 dark:text-white text-lg
                                         placeholder-gray-400
                                         focus:outline-none focus:ring-4 focus:ring-amber-500/30
                                         shadow-2xl">

                            <div class="absolute left-6 text-gray-400">
                                <i class="fas fa-search text-xl"></i>
                            </div>

                            <button @click="submitSearch()"
                                    class="absolute right-3 px-8 py-4
                                          bg-gradient-to-r from-amber-500 via-amber-600 to-orange-500
                                          hover:from-amber-600 hover:via-amber-700 hover:to-orange-600
                                          text-white font-bold text-lg rounded-xl
                                          shadow-lg hover:shadow-xl
                                          transform hover:scale-105
                                          transition-all duration-300
                                          flex items-center gap-2">
                                <span>ค้นหา</span>
                                <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Premium Stats --}}
                <div class="flex flex-wrap justify-center gap-4 md:gap-8 animate-fade-in-up animation-delay-400">
                    {{-- Total Products --}}
                    <div class="group px-8 py-6 bg-white/10 backdrop-blur-xl rounded-2xl
                               border border-white/20 hover:border-amber-400/50
                               transition-all duration-300 hover:scale-105">
                        <div class="text-5xl font-black text-white mb-2
                                   bg-gradient-to-r from-amber-300 to-yellow-200
                                   bg-clip-text text-transparent">
                            {{ number_format($stats['official'] ?? 0) }}+
                        </div>
                        <div class="text-white/70 text-sm font-medium tracking-wide">
                            <i class="fas fa-box mr-1"></i> สินค้าพรีเมี่ยม
                        </div>
                    </div>

                    {{-- Featured Products --}}
                    <div class="group px-8 py-6 bg-white/10 backdrop-blur-xl rounded-2xl
                               border border-white/20 hover:border-purple-400/50
                               transition-all duration-300 hover:scale-105">
                        <div class="text-5xl font-black text-white mb-2
                                   bg-gradient-to-r from-purple-300 to-pink-300
                                   bg-clip-text text-transparent">
                            {{ number_format($stats['featured'] ?? 0) }}
                        </div>
                        <div class="text-white/70 text-sm font-medium tracking-wide">
                            <i class="fas fa-star mr-1"></i> สินค้าแนะนำ
                        </div>
                    </div>

                    {{-- Categories --}}
                    <div class="group px-8 py-6 bg-white/10 backdrop-blur-xl rounded-2xl
                               border border-white/20 hover:border-pink-400/50
                               transition-all duration-300 hover:scale-105">
                        <div class="text-5xl font-black text-white mb-2
                                   bg-gradient-to-r from-pink-300 to-rose-300
                                   bg-clip-text text-transparent">
                            {{ number_format($stats['categories'] ?? 0) }}
                        </div>
                        <div class="text-white/70 text-sm font-medium tracking-wide">
                            <i class="fas fa-layer-group mr-1"></i> หมวดหมู่
                        </div>
                    </div>

                    {{-- Satisfied Customers --}}
                    <div class="group px-8 py-6 bg-white/10 backdrop-blur-xl rounded-2xl
                               border border-white/20 hover:border-green-400/50
                               transition-all duration-300 hover:scale-105">
                        <div class="text-5xl font-black text-white mb-2
                                   bg-gradient-to-r from-green-300 to-emerald-300
                                   bg-clip-text text-transparent">
                            99%
                        </div>
                        <div class="text-white/70 text-sm font-medium tracking-wide">
                            <i class="fas fa-heart mr-1"></i> ลูกค้าพึงพอใจ
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Scroll Indicator --}}
        <div class="absolute bottom-8 left-1/2 -translate-x-1/2 animate-bounce">
            <div class="flex flex-col items-center text-white/50">
                <span class="text-sm mb-2">เลื่อนลงเพื่อดูสินค้า</span>
                <i class="fas fa-chevron-down text-2xl"></i>
            </div>
        </div>
    </section>

    {{-- ════════════════════════════════════════════════════
         🏷️ PREMIUM CATEGORIES SECTION
         ════════════════════════════════════════════════════ --}}
    @if($categories && $categories->count() > 0)
    <section class="py-16 bg-gradient-to-b from-gray-50 to-white dark:from-gray-900 dark:to-gray-800">
        <div class="container mx-auto px-4">
            {{-- Section Header --}}
            <div class="text-center mb-12">
                <div class="inline-flex items-center gap-2 px-5 py-2.5
                           bg-gradient-to-r from-amber-100 to-orange-100
                           dark:from-amber-900/30 dark:to-orange-900/30
                           rounded-full mb-4">
                    <i class="fas fa-layer-group text-amber-600 dark:text-amber-400"></i>
                    <span class="text-amber-700 dark:text-amber-300 font-semibold">Shop by Category</span>
                </div>
                <h2 class="text-4xl md:text-5xl font-black text-gray-900 dark:text-white mb-4">
                    หมวดหมู่สินค้า
                </h2>
                <p class="text-gray-600 dark:text-gray-400 text-lg max-w-xl mx-auto">
                    เลือกชมสินค้าตามหมวดหมู่ที่คุณสนใจ
                </p>
            </div>

            {{-- Category Cards Grid --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                {{-- All Categories --}}
                <a href="{{ route('official-shop.index') }}"
                   class="group relative overflow-hidden rounded-2xl
                         {{ !request('category') ? 'ring-4 ring-amber-500 ring-offset-2 dark:ring-offset-gray-800' : '' }}">
                    <div class="aspect-square bg-gradient-to-br from-amber-500 via-orange-500 to-red-500
                               flex flex-col items-center justify-center p-4
                               transition-transform duration-500 group-hover:scale-105">
                        <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-2xl
                                   flex items-center justify-center mb-3
                                   group-hover:scale-110 transition-transform">
                            <i class="fas fa-th-large text-white text-2xl"></i>
                        </div>
                        <span class="text-white font-bold text-center">ทั้งหมด</span>
                    </div>
                </a>

                {{-- Category Items --}}
                @foreach($categories as $category)
                <a href="{{ route('official-shop.category', $category->slug) }}"
                   class="group relative overflow-hidden rounded-2xl
                         {{ request('category') === $category->slug ? 'ring-4 ring-amber-500 ring-offset-2 dark:ring-offset-gray-800' : '' }}">
                    <div class="aspect-square bg-gradient-to-br from-purple-500 via-pink-500 to-rose-500
                               flex flex-col items-center justify-center p-4
                               transition-transform duration-500 group-hover:scale-105">
                        {{-- ไอคอนตามหมวดหมู่ (สามารถเปลี่ยนได้) --}}
                        <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-2xl
                                   flex items-center justify-center mb-3
                                   group-hover:scale-110 transition-transform">
                            <i class="fas fa-tag text-white text-2xl"></i>
                        </div>
                        <span class="text-white font-bold text-center text-sm line-clamp-2">
                            {{ $category->name }}
                        </span>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- ════════════════════════════════════════════════════
         ⭐ FEATURED PRODUCTS - PREMIUM SHOWCASE
         ════════════════════════════════════════════════════ --}}
    @if($featuredProducts && $featuredProducts->count() > 0)
    <section class="py-20 relative overflow-hidden">
        {{-- Background Decorations --}}
        <div class="absolute inset-0 bg-gradient-to-b from-white to-gray-50 dark:from-gray-800 dark:to-gray-900"></div>
        <div class="absolute top-0 left-0 w-full h-32 bg-gradient-to-b from-gray-50 to-transparent dark:from-gray-900"></div>

        <div class="container mx-auto px-4 relative z-10">
            {{-- Section Header --}}
            <div class="text-center mb-16">
                <div class="inline-flex items-center gap-3 px-6 py-3
                           bg-gradient-to-r from-amber-100 via-yellow-100 to-orange-100
                           dark:from-amber-900/30 dark:via-yellow-900/30 dark:to-orange-900/30
                           rounded-full mb-6 shadow-lg">
                    <div class="w-8 h-8 bg-gradient-to-br from-amber-400 to-orange-500
                               rounded-full flex items-center justify-center">
                        <i class="fas fa-crown text-white text-sm"></i>
                    </div>
                    <span class="text-amber-700 dark:text-amber-300 font-bold tracking-wide">
                        EDITOR'S CHOICE
                    </span>
                </div>

                <h2 class="text-5xl md:text-6xl font-black mb-6">
                    <span class="text-gray-900 dark:text-white">สินค้า</span>
                    <span class="bg-gradient-to-r from-amber-500 via-orange-500 to-red-500
                               bg-clip-text text-transparent">แนะนำ</span>
                </h2>

                <p class="text-gray-600 dark:text-gray-400 text-xl max-w-2xl mx-auto">
                    คัดสรรอย่างพิถีพิถันจากทีมผู้เชี่ยวชาญ
                    <br>เพื่อประสบการณ์ช้อปปิ้งที่ดีที่สุด
                </p>
            </div>

            {{-- Featured Products Grid --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                @foreach($featuredProducts as $product)
                <div class="group perspective-1000">
                    <div class="relative transform-gpu transition-all duration-700
                               group-hover:scale-[1.02] group-hover:-rotate-1">

                        {{-- Premium Glow Effect --}}
                        <div class="absolute -inset-2 bg-gradient-to-br from-amber-400 via-orange-500 to-pink-500
                                   rounded-[2rem] blur-2xl opacity-0 group-hover:opacity-40
                                   transition-opacity duration-700"></div>

                        {{-- Card --}}
                        <div class="relative bg-white dark:bg-gray-800 rounded-3xl shadow-xl overflow-hidden
                                   border border-gray-100 dark:border-gray-700
                                   group-hover:border-amber-300 dark:group-hover:border-amber-600
                                   transition-all duration-500">

                            {{-- Image Container --}}
                            <div class="relative aspect-square overflow-hidden">
                                {{-- Premium Official Badge --}}
                                <div class="absolute top-4 left-4 z-20">
                                    <div class="px-4 py-2 bg-gradient-to-r from-amber-500 to-orange-500
                                               text-white text-xs font-bold rounded-full
                                               shadow-lg shadow-amber-500/30
                                               flex items-center gap-2
                                               transform group-hover:scale-110 transition-transform">
                                        <i class="fas fa-crown"></i>
                                        <span>OFFICIAL</span>
                                    </div>
                                </div>

                                {{-- Featured Star Badge --}}
                                <div class="absolute top-4 right-4 z-20">
                                    <div class="w-12 h-12 bg-gradient-to-br from-yellow-400 via-amber-500 to-orange-500
                                               rounded-xl flex items-center justify-center
                                               shadow-lg shadow-amber-500/30
                                               rotate-12 group-hover:rotate-0 transition-transform duration-500">
                                        <i class="fas fa-star text-white text-xl"></i>
                                    </div>
                                </div>

                                {{-- Discount Badge --}}
                                @if($product->compare_at_price && $product->compare_at_price > $product->price)
                                @php
                                    $discount = round((($product->compare_at_price - $product->price) / $product->compare_at_price) * 100);
                                @endphp
                                <div class="absolute bottom-4 left-4 z-20">
                                    <div class="px-3 py-1.5 bg-red-500 text-white text-sm font-bold rounded-lg
                                               shadow-lg animate-pulse">
                                        ลด {{ $discount }}%
                                    </div>
                                </div>
                                @endif

                                {{-- Product Image --}}
                                <img src="{{ $product->main_image_url ?? 'https://via.placeholder.com/400' }}"
                                     alt="{{ $product->name }}"
                                     class="w-full h-full object-cover
                                           transform group-hover:scale-110
                                           transition-transform duration-700"
                                     loading="lazy">

                                {{-- Hover Overlay --}}
                                <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent
                                           opacity-0 group-hover:opacity-100 transition-opacity duration-500
                                           flex items-end justify-center pb-6">
                                    <a href="{{ route('official-shop.show', $product->slug) }}"
                                       class="px-6 py-3 bg-white text-gray-900 font-bold rounded-xl
                                             transform translate-y-4 group-hover:translate-y-0
                                             transition-transform duration-500
                                             hover:bg-amber-500 hover:text-white">
                                        <i class="fas fa-eye mr-2"></i>
                                        ดูรายละเอียด
                                    </a>
                                </div>
                            </div>

                            {{-- Product Info --}}
                            <div class="p-6">
                                {{-- Category --}}
                                @if($product->category)
                                <div class="text-amber-600 dark:text-amber-400 text-xs font-semibold mb-2 uppercase tracking-wider">
                                    {{ $product->category->name }}
                                </div>
                                @endif

                                {{-- Product Name --}}
                                <h3 class="font-bold text-gray-900 dark:text-white text-lg mb-3 line-clamp-2 h-14
                                          group-hover:text-amber-600 dark:group-hover:text-amber-400 transition-colors">
                                    {{ $product->name }}
                                </h3>

                                {{-- Rating --}}
                                @if($product->rating_average > 0)
                                <div class="flex items-center gap-2 mb-4">
                                    <div class="flex items-center">
                                        @for($i = 1; $i <= 5; $i++)
                                            @if($i <= floor($product->rating_average))
                                                <i class="fas fa-star text-amber-400"></i>
                                            @else
                                                <i class="far fa-star text-gray-300"></i>
                                            @endif
                                        @endfor
                                    </div>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ number_format($product->rating_average, 1) }}
                                        ({{ $product->rating_count }})
                                    </span>
                                </div>
                                @endif

                                {{-- Price --}}
                                <div class="flex items-end gap-3 mb-5">
                                    <span class="text-3xl font-black bg-gradient-to-r from-amber-600 to-orange-600
                                               bg-clip-text text-transparent">
                                        ฿{{ number_format($product->price, 0) }}
                                    </span>
                                    @if($product->compare_at_price && $product->compare_at_price > $product->price)
                                    <span class="text-lg text-gray-400 line-through">
                                        ฿{{ number_format($product->compare_at_price, 0) }}
                                    </span>
                                    @endif
                                </div>

                                {{-- CTA Button --}}
                                <button @click="quickAddToCart({{ $product->id }})"
                                        class="w-full py-4 text-center font-bold
                                              bg-gradient-to-r from-amber-500 via-orange-500 to-red-500
                                              hover:from-amber-600 hover:via-orange-600 hover:to-red-600
                                              text-white rounded-xl
                                              shadow-lg shadow-amber-500/30 hover:shadow-xl
                                              transform hover:scale-105
                                              transition-all duration-300
                                              flex items-center justify-center gap-2">
                                    <i class="fas fa-cart-plus"></i>
                                    <span>เพิ่มลงตะกร้า</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- View All Button --}}
            <div class="text-center mt-16">
                <a href="{{ route('official-shop.featured') }}"
                   class="inline-flex items-center gap-3 px-10 py-5
                         bg-gradient-to-r from-gray-900 to-gray-800
                         dark:from-white dark:to-gray-100
                         text-white dark:text-gray-900 font-bold text-lg
                         rounded-2xl shadow-2xl
                         hover:shadow-3xl hover:scale-105
                         transition-all duration-300
                         group">
                    <span>ดูสินค้าแนะนำทั้งหมด</span>
                    <i class="fas fa-arrow-right transform group-hover:translate-x-2 transition-transform"></i>
                </a>
            </div>
        </div>
    </section>
    @endif

    {{-- ════════════════════════════════════════════════════
         🛍️ ALL PRODUCTS SECTION - LUXURY GRID
         ════════════════════════════════════════════════════ --}}
    <section class="py-20 bg-white dark:bg-gray-900">
        <div class="container mx-auto px-4">
            {{-- Section Header with Filters --}}
            <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6 mb-12">
                <div>
                    <div class="inline-flex items-center gap-2 px-4 py-2
                               bg-purple-100 dark:bg-purple-900/30
                               rounded-full mb-4">
                        <i class="fas fa-store text-purple-600 dark:text-purple-400"></i>
                        <span class="text-purple-700 dark:text-purple-300 font-semibold text-sm">
                            OFFICIAL COLLECTION
                        </span>
                    </div>
                    <h2 class="text-4xl md:text-5xl font-black text-gray-900 dark:text-white mb-3">
                        สินค้าทั้งหมด
                    </h2>
                    <p class="text-gray-600 dark:text-gray-400 text-lg">
                        พบ <span class="font-bold text-amber-600">{{ number_format($products->total()) }}</span> รายการ
                    </p>
                </div>

                {{-- Filters --}}
                <div class="flex flex-wrap items-center gap-4">
                    {{-- Brand Filter --}}
                    @if($brands && $brands->count() > 0)
                    <div class="relative">
                        <select x-model="brandFilter"
                                @change="applyFilters()"
                                class="appearance-none pl-5 pr-12 py-4
                                      bg-white dark:bg-gray-800
                                      border-2 border-gray-200 dark:border-gray-700
                                      hover:border-amber-400 dark:hover:border-amber-500
                                      rounded-xl text-sm font-medium
                                      focus:ring-4 focus:ring-amber-500/20 focus:border-amber-500
                                      shadow-lg cursor-pointer
                                      transition-all">
                            <option value="">ยี่ห้อทั้งหมด</option>
                            @foreach($brands as $brand)
                            <option value="{{ $brand }}" {{ request('brand') === $brand ? 'selected' : '' }}>
                                {{ $brand }}
                            </option>
                            @endforeach
                        </select>
                        <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                    </div>
                    @endif

                    {{-- Sort Filter --}}
                    <div class="relative">
                        <select x-model="sortBy"
                                @change="applyFilters()"
                                class="appearance-none pl-5 pr-12 py-4
                                      bg-white dark:bg-gray-800
                                      border-2 border-gray-200 dark:border-gray-700
                                      hover:border-amber-400 dark:hover:border-amber-500
                                      rounded-xl text-sm font-medium
                                      focus:ring-4 focus:ring-amber-500/20 focus:border-amber-500
                                      shadow-lg cursor-pointer
                                      transition-all">
                            <option value="newest">ใหม่ล่าสุด</option>
                            <option value="popular">ยอดนิยม</option>
                            <option value="price_low">ราคาต่ำ → สูง</option>
                            <option value="price_high">ราคาสูง → ต่ำ</option>
                            <option value="rating">คะแนนสูงสุด</option>
                        </select>
                        <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                    </div>

                    {{-- View Toggle --}}
                    <div class="hidden md:flex items-center gap-2 p-1.5 bg-gray-100 dark:bg-gray-800 rounded-xl">
                        <button @click="viewMode = 'grid'"
                                :class="viewMode === 'grid' ? 'bg-white dark:bg-gray-700 shadow-md' : ''"
                                class="p-3 rounded-lg transition-all">
                            <i class="fas fa-grid-2 text-gray-700 dark:text-gray-300"></i>
                        </button>
                        <button @click="viewMode = 'list'"
                                :class="viewMode === 'list' ? 'bg-white dark:bg-gray-700 shadow-md' : ''"
                                class="p-3 rounded-lg transition-all">
                            <i class="fas fa-list text-gray-700 dark:text-gray-300"></i>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Products Grid --}}
            @if($products->count() > 0)
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 md:gap-6">
                @foreach($products as $product)
                <div class="group">
                    <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden
                               border border-gray-100 dark:border-gray-700
                               hover:shadow-2xl hover:border-amber-300 dark:hover:border-amber-600
                               hover:-translate-y-2
                               transition-all duration-500">

                        {{-- Product Image --}}
                        <a href="{{ route('official-shop.show', $product->slug) }}" class="block">
                            <div class="aspect-square relative overflow-hidden bg-gray-50 dark:bg-gray-700">
                                {{-- Official Badge --}}
                                <div class="absolute top-3 left-3 z-10">
                                    <div class="px-3 py-1.5 bg-gradient-to-r from-amber-500 to-orange-500
                                               text-white text-xs font-bold rounded-full
                                               shadow-md flex items-center gap-1.5">
                                        <i class="fas fa-crown text-[10px]"></i>
                                        <span>Official</span>
                                    </div>
                                </div>

                                {{-- Discount Badge --}}
                                @if($product->compare_at_price && $product->compare_at_price > $product->price)
                                @php
                                    $discount = round((($product->compare_at_price - $product->price) / $product->compare_at_price) * 100);
                                @endphp
                                <div class="absolute top-3 right-3 z-10">
                                    <div class="px-2.5 py-1 bg-red-500 text-white text-xs font-bold rounded-lg shadow-md">
                                        -{{ $discount }}%
                                    </div>
                                </div>
                                @endif

                                <img src="{{ $product->main_image_url ?? 'https://via.placeholder.com/300' }}"
                                     alt="{{ $product->name }}"
                                     class="w-full h-full object-cover
                                           group-hover:scale-110 transition-transform duration-700"
                                     loading="lazy">

                                {{-- Quick View Overlay --}}
                                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100
                                           flex items-center justify-center transition-opacity duration-300">
                                    <span class="px-4 py-2 bg-white text-gray-900 text-sm font-semibold rounded-lg
                                                transform translate-y-4 group-hover:translate-y-0 transition-transform">
                                        <i class="fas fa-eye mr-2"></i> ดูเพิ่มเติม
                                    </span>
                                </div>
                            </div>
                        </a>

                        {{-- Product Details --}}
                        <div class="p-4">
                            <a href="{{ route('official-shop.show', $product->slug) }}" class="block">
                                <h3 class="font-semibold text-gray-900 dark:text-white text-sm mb-2
                                          line-clamp-2 h-10
                                          hover:text-amber-600 dark:hover:text-amber-400 transition-colors">
                                    {{ $product->name }}
                                </h3>
                            </a>

                            {{-- Rating --}}
                            @if($product->rating_average > 0)
                            <div class="flex items-center gap-1.5 mb-3">
                                <i class="fas fa-star text-amber-400 text-xs"></i>
                                <span class="text-xs font-medium text-gray-700 dark:text-gray-300">
                                    {{ number_format($product->rating_average, 1) }}
                                </span>
                                <span class="text-xs text-gray-400">
                                    ({{ $product->rating_count }})
                                </span>
                            </div>
                            @endif

                            {{-- Price --}}
                            <div class="flex items-end gap-2 mb-4">
                                <span class="text-xl font-bold text-amber-600 dark:text-amber-400">
                                    ฿{{ number_format($product->price, 0) }}
                                </span>
                                @if($product->compare_at_price && $product->compare_at_price > $product->price)
                                <span class="text-sm text-gray-400 line-through">
                                    ฿{{ number_format($product->compare_at_price, 0) }}
                                </span>
                                @endif
                            </div>

                            {{-- Commission Badge (for logged-in affiliates) --}}
                            @auth
                            @if($product->commission_rate > 0)
                            <div class="mb-3 px-3 py-1.5 bg-green-50 dark:bg-green-900/20 rounded-lg
                                       flex items-center gap-2">
                                <i class="fas fa-percentage text-green-500 text-xs"></i>
                                <span class="text-xs font-medium text-green-600 dark:text-green-400">
                                    คอมมิชชั่น {{ number_format($product->commission_rate, 0) }}%
                                </span>
                            </div>
                            @endif
                            @endauth

                            {{-- Add to Cart Button --}}
                            <button @click="quickAddToCart({{ $product->id }})"
                                    class="w-full py-3 text-center text-sm font-bold
                                          bg-gradient-to-r from-amber-500 to-orange-500
                                          hover:from-amber-600 hover:to-orange-600
                                          text-white rounded-xl
                                          shadow-md hover:shadow-lg
                                          transform hover:scale-[1.02]
                                          transition-all duration-300
                                          flex items-center justify-center gap-2">
                                <i class="fas fa-cart-plus"></i>
                                <span>เพิ่มตะกร้า</span>
                            </button>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            @if($products->hasPages())
            <div class="mt-16 flex justify-center">
                <div class="inline-flex items-center gap-2 p-2 bg-white dark:bg-gray-800 rounded-2xl shadow-xl">
                    {{ $products->links() }}
                </div>
            </div>
            @endif

            @else
            {{-- Empty State --}}
            <div class="text-center py-24">
                <div class="w-32 h-32 mx-auto mb-8 bg-gradient-to-br from-amber-100 to-orange-100
                           dark:from-amber-900/20 dark:to-orange-900/20
                           rounded-full flex items-center justify-center">
                    <i class="fas fa-box-open text-5xl text-amber-400"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                    ไม่พบสินค้า
                </h3>
                <p class="text-gray-600 dark:text-gray-400 mb-8 text-lg">
                    ลองค้นหาด้วยคำอื่น หรือดูสินค้าทั้งหมด
                </p>
                <a href="{{ route('official-shop.index') }}"
                   class="inline-flex items-center gap-3 px-8 py-4
                         bg-gradient-to-r from-amber-500 to-orange-500
                         text-white font-bold rounded-xl
                         shadow-lg hover:shadow-xl hover:scale-105
                         transition-all">
                    <i class="fas fa-arrow-left"></i>
                    <span>ดูสินค้าทั้งหมด</span>
                </a>
            </div>
            @endif
        </div>
    </section>

    {{-- ════════════════════════════════════════════════════
         🏆 LUXURY TRUST BADGES SECTION
         ════════════════════════════════════════════════════ --}}
    <section class="py-20 bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 relative overflow-hidden">
        {{-- Background Decorations --}}
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_50%,rgba(251,191,36,0.15),transparent_50%)]"></div>
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_80%_50%,rgba(168,85,247,0.15),transparent_50%)]"></div>

        <div class="container mx-auto px-4 relative z-10">
            {{-- Section Header --}}
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-black text-white mb-4">
                    ทำไมต้องเลือก
                    <span class="bg-gradient-to-r from-amber-400 to-orange-400 bg-clip-text text-transparent">
                        Official Shop
                    </span>
                </h2>
                <p class="text-white/70 text-lg max-w-xl mx-auto">
                    เราใส่ใจในทุกรายละเอียด เพื่อประสบการณ์ช้อปปิ้งที่ดีที่สุด
                </p>
            </div>

            {{-- Trust Badges Grid --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                {{-- Badge 1: Authentic --}}
                <div class="group p-8 bg-white/5 backdrop-blur-xl rounded-3xl
                           border border-white/10 hover:border-amber-500/50
                           transform hover:-translate-y-2 hover:scale-105
                           transition-all duration-500">
                    <div class="w-20 h-20 mx-auto mb-6
                               bg-gradient-to-br from-amber-400 to-orange-500
                               rounded-2xl flex items-center justify-center
                               shadow-lg shadow-amber-500/30
                               group-hover:scale-110 group-hover:rotate-6
                               transition-transform duration-500">
                        <i class="fas fa-gem text-white text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-3 text-center">ของแท้ 100%</h3>
                    <p class="text-white/60 text-center text-sm leading-relaxed">
                        รับประกันสินค้าแท้ทุกชิ้น
                        <br>จากทางระบบโดยตรง
                    </p>
                </div>

                {{-- Badge 2: Free Shipping --}}
                <div class="group p-8 bg-white/5 backdrop-blur-xl rounded-3xl
                           border border-white/10 hover:border-green-500/50
                           transform hover:-translate-y-2 hover:scale-105
                           transition-all duration-500">
                    <div class="w-20 h-20 mx-auto mb-6
                               bg-gradient-to-br from-green-400 to-emerald-500
                               rounded-2xl flex items-center justify-center
                               shadow-lg shadow-green-500/30
                               group-hover:scale-110 group-hover:rotate-6
                               transition-transform duration-500">
                        <i class="fas fa-truck-fast text-white text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-3 text-center">ส่งฟรีทั่วไทย</h3>
                    <p class="text-white/60 text-center text-sm leading-relaxed">
                        ไม่มีขั้นต่ำ ส่งฟรีทุกออเดอร์
                        <br>จัดส่งรวดเร็ว 1-3 วัน
                    </p>
                </div>

                {{-- Badge 3: Easy Return --}}
                <div class="group p-8 bg-white/5 backdrop-blur-xl rounded-3xl
                           border border-white/10 hover:border-blue-500/50
                           transform hover:-translate-y-2 hover:scale-105
                           transition-all duration-500">
                    <div class="w-20 h-20 mx-auto mb-6
                               bg-gradient-to-br from-blue-400 to-cyan-500
                               rounded-2xl flex items-center justify-center
                               shadow-lg shadow-blue-500/30
                               group-hover:scale-110 group-hover:rotate-6
                               transition-transform duration-500">
                        <i class="fas fa-rotate-left text-white text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-3 text-center">เปลี่ยนคืน 7 วัน</h3>
                    <p class="text-white/60 text-center text-sm leading-relaxed">
                        ไม่พอใจยินดีคืนเงิน
                        <br>เงื่อนไขง่าย ไม่ยุ่งยาก
                    </p>
                </div>

                {{-- Badge 4: 24/7 Support --}}
                <div class="group p-8 bg-white/5 backdrop-blur-xl rounded-3xl
                           border border-white/10 hover:border-purple-500/50
                           transform hover:-translate-y-2 hover:scale-105
                           transition-all duration-500">
                    <div class="w-20 h-20 mx-auto mb-6
                               bg-gradient-to-br from-purple-400 to-pink-500
                               rounded-2xl flex items-center justify-center
                               shadow-lg shadow-purple-500/30
                               group-hover:scale-110 group-hover:rotate-6
                               transition-transform duration-500">
                        <i class="fas fa-headset text-white text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-3 text-center">บริการ 24/7</h3>
                    <p class="text-white/60 text-center text-sm leading-relaxed">
                        ทีมงานพร้อมให้บริการ
                        <br>ตอบทุกคำถามตลอด 24 ชม.
                    </p>
                </div>
            </div>

            {{-- Additional Trust Elements --}}
            <div class="mt-16 flex flex-wrap justify-center items-center gap-8">
                <div class="flex items-center gap-3 px-6 py-3 bg-white/10 backdrop-blur-sm rounded-full">
                    <i class="fas fa-shield-check text-green-400 text-xl"></i>
                    <span class="text-white/80 font-medium">SSL Secure</span>
                </div>
                <div class="flex items-center gap-3 px-6 py-3 bg-white/10 backdrop-blur-sm rounded-full">
                    <i class="fas fa-credit-card text-blue-400 text-xl"></i>
                    <span class="text-white/80 font-medium">ชำระเงินปลอดภัย</span>
                </div>
                <div class="flex items-center gap-3 px-6 py-3 bg-white/10 backdrop-blur-sm rounded-full">
                    <i class="fas fa-lock text-purple-400 text-xl"></i>
                    <span class="text-white/80 font-medium">ข้อมูลปลอดภัย</span>
                </div>
                <div class="flex items-center gap-3 px-6 py-3 bg-white/10 backdrop-blur-sm rounded-full">
                    <i class="fas fa-award text-amber-400 text-xl"></i>
                    <span class="text-white/80 font-medium">รางวัลการันตี</span>
                </div>
            </div>
        </div>
    </section>

    {{-- ════════════════════════════════════════════════════
         📧 NEWSLETTER SECTION
         ════════════════════════════════════════════════════ --}}
    <section class="py-16 bg-gradient-to-r from-amber-500 via-orange-500 to-red-500 relative overflow-hidden">
        {{-- Decorative Elements --}}
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_30%_50%,rgba(255,255,255,0.2),transparent_50%)]"></div>

        <div class="container mx-auto px-4 relative z-10">
            <div class="max-w-4xl mx-auto text-center">
                <div class="w-20 h-20 mx-auto mb-6 bg-white/20 backdrop-blur-sm rounded-2xl
                           flex items-center justify-center">
                    <i class="fas fa-envelope text-white text-3xl"></i>
                </div>

                <h2 class="text-3xl md:text-4xl font-black text-white mb-4">
                    รับโปรโมชั่นพิเศษก่อนใคร
                </h2>
                <p class="text-white/90 text-lg mb-8">
                    สมัครรับข่าวสาร รับส่วนลดพิเศษและโปรโมชั่นสุดคุ้มก่อนใคร
                </p>

                <form class="flex flex-col sm:flex-row gap-4 max-w-lg mx-auto">
                    <input type="email"
                           placeholder="กรอกอีเมลของคุณ"
                           class="flex-1 px-6 py-4 bg-white/95 rounded-xl
                                 text-gray-900 placeholder-gray-500
                                 focus:outline-none focus:ring-4 focus:ring-white/30
                                 shadow-xl">
                    <button type="submit"
                            class="px-8 py-4 bg-gray-900 hover:bg-gray-800
                                  text-white font-bold rounded-xl
                                  shadow-xl hover:shadow-2xl
                                  transform hover:scale-105
                                  transition-all duration-300
                                  flex items-center justify-center gap-2">
                        <span>สมัครรับข่าว</span>
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </form>

                <p class="text-white/70 text-sm mt-4">
                    <i class="fas fa-lock mr-1"></i>
                    เราจะไม่แชร์อีเมลของคุณกับบุคคลที่สาม
                </p>
            </div>
        </div>
    </section>
</div>

@push('scripts')
<script>
/**
 * Luxury Shop Manager - Alpine.js Component
 *
 * จัดการ Official Shop ด้วย Alpine.js
 */
function luxuryShopManager() {
    return {
        searchQuery: '{{ request("search", "") }}',
        sortBy: '{{ request("sort_by", "newest") }}',
        brandFilter: '{{ request("brand", "") }}',
        viewMode: 'grid',

        /**
         * Initialize component
         */
        init() {
            console.log('🏆 Luxury Shop Manager initialized');
        },

        /**
         * ส่งคำค้นหา
         */
        submitSearch() {
            if (this.searchQuery.trim()) {
                const url = new URL('{{ route("official-shop.index") }}');
                url.searchParams.set('search', this.searchQuery.trim());
                window.location.href = url.toString();
            }
        },

        /**
         * Apply filters และ redirect
         */
        applyFilters() {
            const url = new URL(window.location.href);

            if (this.sortBy) {
                url.searchParams.set('sort_by', this.sortBy);
            }

            if (this.brandFilter) {
                url.searchParams.set('brand', this.brandFilter);
            } else {
                url.searchParams.delete('brand');
            }

            window.location.href = url.toString();
        },

        /**
         * เพิ่มสินค้าลงตะกร้าอย่างรวดเร็ว
         *
         * @param {number} productId - ID ของสินค้า
         */
        async quickAddToCart(productId) {
            try {
                const response = await fetch('/cart/add', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        product_id: productId,
                        quantity: 1
                    })
                });

                const data = await response.json();

                if (data.success) {
                    // อัพเดท cart badge
                    window.dispatchEvent(new CustomEvent('cart-updated'));

                    // แสดง notification
                    window.dispatchEvent(new CustomEvent('notify', {
                        detail: {
                            message: '✅ เพิ่มสินค้าลงตะกร้าสำเร็จ',
                            type: 'success'
                        }
                    }));
                } else {
                    throw new Error(data.message || 'เกิดข้อผิดพลาด');
                }
            } catch (error) {
                console.error('Add to cart error:', error);
                window.dispatchEvent(new CustomEvent('notify', {
                    detail: {
                        message: error.message || '❌ ไม่สามารถเพิ่มสินค้าลงตะกร้าได้',
                        type: 'error'
                    }
                }));
            }
        }
    };
}

window.luxuryShopManager = luxuryShopManager;
</script>
@endpush

@push('styles')
<style>
/* ════════════════════════════════════════════════════
   🎨 CUSTOM CSS ANIMATIONS & EFFECTS
   ════════════════════════════════════════════════════ */

/* Perspective for 3D effects */
.perspective-1000 {
    perspective: 1000px;
}

/* Line Clamp */
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* ═══════════════════════════════════════════
   FADE IN UP ANIMATION
   ═══════════════════════════════════════════ */
.animate-fade-in-up {
    animation: fadeInUp 0.8s ease-out forwards;
    opacity: 0;
}

.animation-delay-100 { animation-delay: 0.1s; }
.animation-delay-200 { animation-delay: 0.2s; }
.animation-delay-300 { animation-delay: 0.3s; }
.animation-delay-400 { animation-delay: 0.4s; }

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(40px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* ═══════════════════════════════════════════
   FLOATING ANIMATIONS
   ═══════════════════════════════════════════ */
.animate-float-slow {
    animation: floatSlow 8s ease-in-out infinite;
}

.animate-float-medium {
    animation: floatMedium 6s ease-in-out infinite;
}

.animate-float-fast {
    animation: floatFast 4s ease-in-out infinite;
}

@keyframes floatSlow {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    50% { transform: translateY(-30px) rotate(5deg); }
}

@keyframes floatMedium {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    50% { transform: translateY(-20px) rotate(-3deg); }
}

@keyframes floatFast {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-15px); }
}

/* ═══════════════════════════════════════════
   LUXURY PARTICLES
   ═══════════════════════════════════════════ */
.luxury-particle {
    position: absolute;
    width: 8px;
    height: 8px;
    background: linear-gradient(135deg, #fbbf24, #f59e0b);
    border-radius: 50%;
    animation: particleFloat 15s linear infinite;
}

.particle-1 { left: 10%; top: 20%; animation-delay: 0s; }
.particle-2 { left: 20%; top: 60%; animation-delay: -3s; width: 6px; height: 6px; }
.particle-3 { left: 80%; top: 30%; animation-delay: -6s; width: 10px; height: 10px; }
.particle-4 { left: 70%; top: 70%; animation-delay: -9s; width: 5px; height: 5px; }
.particle-5 { left: 50%; top: 40%; animation-delay: -12s; width: 7px; height: 7px; }

@keyframes particleFloat {
    0% {
        transform: translateY(100vh) rotate(0deg);
        opacity: 0;
    }
    10% {
        opacity: 1;
    }
    90% {
        opacity: 1;
    }
    100% {
        transform: translateY(-100vh) rotate(720deg);
        opacity: 0;
    }
}

/* ═══════════════════════════════════════════
   LUXURY LAVA BACKGROUND
   สีทอง, Rose Gold, Platinum - หรูหราสุดๆ
   ═══════════════════════════════════════════ */

/* Luxury blob 1 - Gold */
.luxury-blob-1 {
    width: 400px;
    height: 420px;
    background: linear-gradient(180deg, #f59e0b 0%, #fbbf24 40%, #fcd34d 70%, #f59e0b 100%);
    left: 5%;
    top: 10%;
    animation: luxuryFloat1 20s ease-in-out infinite, luxuryMorph1 14s ease-in-out infinite;
    filter: blur(80px);
    opacity: 0.25;
}

/* Luxury blob 2 - Rose Gold */
.luxury-blob-2 {
    width: 350px;
    height: 370px;
    background: linear-gradient(180deg, #ec4899 0%, #f472b6 40%, #fda4af 70%, #ec4899 100%);
    right: 10%;
    top: 20%;
    animation: luxuryFloat2 22s ease-in-out infinite, luxuryMorph2 16s ease-in-out infinite;
    animation-delay: -5s;
    filter: blur(80px);
    opacity: 0.25;
}

/* Luxury blob 3 - Platinum/Silver */
.luxury-blob-3 {
    width: 380px;
    height: 400px;
    background: linear-gradient(180deg, #a855f7 0%, #c084fc 40%, #e879f9 70%, #a855f7 100%);
    left: 35%;
    top: 50%;
    animation: luxuryFloat3 24s ease-in-out infinite, luxuryMorph1 18s ease-in-out infinite;
    animation-delay: -10s;
    filter: blur(80px);
    opacity: 0.2;
}

/* Luxury blob 4 - Deep Gold */
.luxury-blob-4 {
    width: 300px;
    height: 320px;
    background: linear-gradient(180deg, #d97706 0%, #f59e0b 40%, #fbbf24 70%, #d97706 100%);
    right: 5%;
    top: 60%;
    animation: luxuryFloat1 18s ease-in-out infinite, luxuryMorph2 12s ease-in-out infinite;
    animation-delay: -3s;
    filter: blur(80px);
    opacity: 0.25;
}

/* Luxury blob 5 - Warm Pink */
.luxury-blob-5 {
    width: 280px;
    height: 300px;
    background: linear-gradient(180deg, #db2777 0%, #ec4899 50%, #db2777 100%);
    left: 15%;
    top: 70%;
    animation: luxuryFloat2 21s ease-in-out infinite, luxuryMorph1 15s ease-in-out infinite;
    animation-delay: -7s;
    filter: blur(80px);
    opacity: 0.2;
}

/* Luxury blob 6 - Amber Glow */
.luxury-blob-6 {
    width: 260px;
    height: 280px;
    background: linear-gradient(180deg, #ea580c 0%, #f97316 50%, #ea580c 100%);
    left: 55%;
    top: 15%;
    animation: luxuryFloat3 19s ease-in-out infinite, luxuryMorph2 13s ease-in-out infinite;
    animation-delay: -12s;
    filter: blur(80px);
    opacity: 0.2;
}

/* Luxury Float Animations */
@keyframes luxuryFloat1 {
    0%, 100% { transform: translate(0, 0) scale(1) rotate(0deg); }
    25% { transform: translate(50px, -70px) scale(1.1) rotate(3deg); }
    50% { transform: translate(-40px, -140px) scale(0.95) rotate(-2deg); }
    75% { transform: translate(60px, -70px) scale(1.05) rotate(1deg); }
}

@keyframes luxuryFloat2 {
    0%, 100% { transform: translate(0, 0) scale(1) rotate(0deg); }
    33% { transform: translate(-60px, -120px) scale(1.12) rotate(-3deg); }
    66% { transform: translate(50px, -60px) scale(0.9) rotate(2deg); }
}

@keyframes luxuryFloat3 {
    0%, 100% { transform: translate(0, 0) scale(1); }
    50% { transform: translate(80px, -160px) scale(1.15); }
}

/* Luxury Morph Animations */
@keyframes luxuryMorph1 {
    0%, 100% { border-radius: 40% 60% 55% 45% / 55% 45% 60% 40%; }
    25% { border-radius: 55% 45% 40% 60% / 45% 55% 50% 50%; }
    50% { border-radius: 45% 55% 50% 50% / 50% 50% 55% 45%; }
    75% { border-radius: 50% 50% 60% 40% / 60% 40% 45% 55%; }
}

@keyframes luxuryMorph2 {
    0%, 100% { border-radius: 50% 50% 45% 55% / 45% 55% 50% 50%; }
    33% { border-radius: 45% 55% 50% 50% / 55% 45% 55% 45%; }
    66% { border-radius: 55% 45% 55% 45% / 45% 55% 45% 55%; }
}

/* ═══════════════════════════════════════════
   DARK MODE - LUXURY RGB GLOW
   ═══════════════════════════════════════════ */
.dark .luxury-blob-1 {
    background: linear-gradient(180deg, #fbbf24 0%, #fcd34d 50%, #fbbf24 100%);
    filter: blur(70px);
    opacity: 0.5;
    box-shadow:
        0 0 80px rgba(251, 191, 36, 0.8),
        0 0 160px rgba(251, 191, 36, 0.6),
        0 0 240px rgba(251, 191, 36, 0.4),
        inset 0 0 50px rgba(255, 255, 255, 0.2);
}

.dark .luxury-blob-2 {
    background: linear-gradient(180deg, #ec4899 0%, #f472b6 50%, #ec4899 100%);
    filter: blur(70px);
    opacity: 0.5;
    box-shadow:
        0 0 80px rgba(236, 72, 153, 0.8),
        0 0 160px rgba(236, 72, 153, 0.6),
        0 0 240px rgba(236, 72, 153, 0.4),
        inset 0 0 50px rgba(255, 255, 255, 0.2);
}

.dark .luxury-blob-3 {
    background: linear-gradient(180deg, #a855f7 0%, #c084fc 50%, #a855f7 100%);
    filter: blur(70px);
    opacity: 0.45;
    box-shadow:
        0 0 80px rgba(168, 85, 247, 0.8),
        0 0 160px rgba(168, 85, 247, 0.6),
        0 0 240px rgba(168, 85, 247, 0.4),
        inset 0 0 50px rgba(255, 255, 255, 0.2);
}

.dark .luxury-blob-4 {
    background: linear-gradient(180deg, #f59e0b 0%, #fbbf24 50%, #f59e0b 100%);
    filter: blur(70px);
    opacity: 0.5;
    box-shadow:
        0 0 80px rgba(245, 158, 11, 0.8),
        0 0 160px rgba(245, 158, 11, 0.6),
        0 0 240px rgba(245, 158, 11, 0.4),
        inset 0 0 50px rgba(255, 255, 255, 0.2);
}

.dark .luxury-blob-5 {
    background: linear-gradient(180deg, #db2777 0%, #ec4899 50%, #db2777 100%);
    filter: blur(70px);
    opacity: 0.45;
    box-shadow:
        0 0 80px rgba(219, 39, 119, 0.8),
        0 0 160px rgba(219, 39, 119, 0.6),
        0 0 240px rgba(219, 39, 119, 0.4),
        inset 0 0 50px rgba(255, 255, 255, 0.2);
}

.dark .luxury-blob-6 {
    background: linear-gradient(180deg, #f97316 0%, #fb923c 50%, #f97316 100%);
    filter: blur(70px);
    opacity: 0.45;
    box-shadow:
        0 0 80px rgba(249, 115, 22, 0.8),
        0 0 160px rgba(249, 115, 22, 0.6),
        0 0 240px rgba(249, 115, 22, 0.4),
        inset 0 0 50px rgba(255, 255, 255, 0.2);
}

/* ═══════════════════════════════════════════
   MOBILE OPTIMIZATION
   ═══════════════════════════════════════════ */
@media (max-width: 768px) {
    .luxury-lava .lava-blob {
        transform: scale(0.5);
        filter: blur(50px);
    }
    .luxury-blob-5,
    .luxury-blob-6 {
        display: none;
    }
    .luxury-particle {
        display: none;
    }
    .dark .luxury-lava .lava-blob {
        filter: blur(60px);
    }
}

/* ═══════════════════════════════════════════
   REDUCED MOTION PREFERENCE
   ═══════════════════════════════════════════ */
@media (prefers-reduced-motion: reduce) {
    .luxury-lava .lava-blob,
    .luxury-particle,
    .animate-float-slow,
    .animate-float-medium,
    .animate-float-fast {
        animation: none;
        transform: translateY(0);
    }
    .animate-fade-in-up {
        animation: none;
        opacity: 1;
    }
}
</style>
@endpush
@endsection
