{{--
    Official Shop Index Page - Premium Layout

    หน้าหลักร้านของระบบ (Official Shop)
    ออกแบบให้หรูหราและพรีเมียมกว่าร้านผู้เช่าทั่วไป

    Features:
    - Premium Lava Background (Purple, Pink, Gold)
    - Glassmorphism Hero Section
    - Featured Products Carousel
    - Category Filter
    - Premium Product Grid
    - Dark Mode Support
--}}

@extends('layouts.storefront')

@section('title', 'Official Shop - สินค้าคุณภาพจากระบบ')

@section('meta')
<meta name="description" content="Official Shop - สินค้าคุณภาพดีจากทางระบบโดยตรง รับประกัน 100% ของแท้ พร้อมส่งทั่วประเทศ">
@endsection

{{-- Premium Lava Background สำหรับ Official Shop --}}
@section('lava-background')
<div class="lava-background premium-lava" aria-hidden="true">
    <div class="lava-blob premium-blob-1"></div>
    <div class="lava-blob premium-blob-2"></div>
    <div class="lava-blob premium-blob-3"></div>
    <div class="lava-blob premium-blob-4"></div>
    <div class="lava-blob premium-blob-5"></div>
    <div class="lava-blob premium-blob-6"></div>
</div>
@endsection

@section('content')
<div x-data="officialShopManager()" x-init="init()" class="min-h-screen">

    {{-- ========================================
         PREMIUM HERO SECTION
         ======================================== --}}
    <div class="relative overflow-hidden">
        {{-- Gradient Overlay --}}
        <div class="absolute inset-0 bg-gradient-to-br from-purple-900/90 via-pink-800/80 to-orange-700/70"></div>

        {{-- Animated Pattern --}}
        <div class="absolute inset-0 opacity-20">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_30%_30%,rgba(255,255,255,0.4),transparent_50%)]"></div>
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_70%_70%,rgba(255,255,255,0.3),transparent_50%)]"></div>
        </div>

        {{-- Floating Elements --}}
        <div class="absolute top-20 left-10 w-72 h-72 bg-gradient-to-br from-purple-500/30 to-pink-500/30 rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute bottom-10 right-10 w-96 h-96 bg-gradient-to-br from-orange-500/30 to-yellow-500/30 rounded-full blur-3xl animate-pulse delay-1000"></div>

        {{-- Hero Content --}}
        <div class="relative container mx-auto px-4 py-16 md:py-24">
            <div class="text-center max-w-4xl mx-auto">
                {{-- Official Badge --}}
                <div class="inline-flex items-center gap-3 px-6 py-3 mb-8
                           bg-white/10 backdrop-blur-xl border border-white/20
                           rounded-full shadow-2xl">
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-purple-500 to-pink-600
                               flex items-center justify-center shadow-lg">
                        <i class="fas fa-shield-check text-white text-xl"></i>
                    </div>
                    <div class="text-left">
                        <div class="text-white font-bold text-lg">Official Shop</div>
                        <div class="text-white/70 text-sm">ร้านของระบบ • การันตี 100%</div>
                    </div>
                </div>

                {{-- Title --}}
                <h1 class="text-5xl md:text-7xl font-black text-white mb-6
                          [text-shadow:_0_4px_30px_rgba(0,0,0,0.3)]">
                    <span class="bg-gradient-to-r from-white via-purple-200 to-pink-200 bg-clip-text text-transparent">
                        Official Shop
                    </span>
                </h1>

                <p class="text-xl md:text-2xl text-white/90 mb-8 max-w-2xl mx-auto">
                    สินค้าคุณภาพพรีเมียมจากระบบโดยตรง
                    <br>
                    <span class="text-purple-200">ของแท้ • ส่งฟรี • รับประกัน 7 วัน</span>
                </p>

                {{-- Search Bar --}}
                <div class="max-w-2xl mx-auto">
                    <div class="relative group">
                        <input type="text"
                               x-model="searchQuery"
                               @keyup.enter="submitSearch()"
                               placeholder="ค้นหาสินค้าในร้านของระบบ..."
                               class="w-full px-8 py-5 pl-14 text-lg
                                     bg-white/95 dark:bg-gray-800/95
                                     backdrop-blur-xl
                                     border-2 border-white/30 dark:border-gray-600/30
                                     rounded-2xl
                                     text-gray-900 dark:text-white
                                     placeholder-gray-500 dark:placeholder-gray-400
                                     focus:outline-none focus:ring-4 focus:ring-purple-500/50
                                     shadow-2xl
                                     transition-all">
                        <div class="absolute left-5 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="fas fa-search text-xl"></i>
                        </div>
                        <button @click="submitSearch()"
                                class="absolute right-3 top-1/2 -translate-y-1/2
                                      px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600
                                      hover:from-purple-700 hover:to-pink-700
                                      text-white font-bold rounded-xl
                                      shadow-lg hover:shadow-xl
                                      transition-all transform hover:scale-105">
                            ค้นหา
                        </button>
                    </div>
                </div>

                {{-- Stats Row --}}
                <div class="flex flex-wrap justify-center gap-8 mt-12">
                    <div class="text-center">
                        <div class="text-4xl font-black text-white mb-1">
                            {{ number_format($stats['official'] ?? 0) }}
                        </div>
                        <div class="text-white/70 text-sm">สินค้าทั้งหมด</div>
                    </div>
                    <div class="w-px h-16 bg-white/20 hidden md:block"></div>
                    <div class="text-center">
                        <div class="text-4xl font-black text-white mb-1">
                            {{ number_format($stats['featured'] ?? 0) }}
                        </div>
                        <div class="text-white/70 text-sm">สินค้าแนะนำ</div>
                    </div>
                    <div class="w-px h-16 bg-white/20 hidden md:block"></div>
                    <div class="text-center">
                        <div class="text-4xl font-black text-white mb-1">
                            {{ number_format($stats['categories'] ?? 0) }}
                        </div>
                        <div class="text-white/70 text-sm">หมวดหมู่</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ========================================
         FEATURED PRODUCTS SECTION
         ======================================== --}}
    @if($featuredProducts && $featuredProducts->count() > 0)
    <div class="py-16 relative">
        <div class="container mx-auto px-4">
            {{-- Section Header --}}
            <div class="text-center mb-12">
                <div class="inline-flex items-center gap-2 px-4 py-2
                           bg-gradient-to-r from-purple-100 to-pink-100
                           dark:from-purple-900/30 dark:to-pink-900/30
                           rounded-full mb-4">
                    <i class="fas fa-star text-purple-600 dark:text-purple-400"></i>
                    <span class="text-purple-700 dark:text-purple-300 font-semibold">แนะนำสำหรับคุณ</span>
                </div>
                <h2 class="text-4xl font-black text-gray-900 dark:text-white mb-4">
                    สินค้าแนะนำ
                </h2>
                <p class="text-gray-600 dark:text-gray-400 max-w-xl mx-auto">
                    สินค้าคัดสรรคุณภาพพรีเมียม จากทีมงานร้านของระบบ
                </p>
            </div>

            {{-- Featured Products Grid --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($featuredProducts as $product)
                <div class="group perspective-1000">
                    <div class="relative transform-gpu transition-all duration-500 group-hover:scale-105">
                        {{-- Glow Effect --}}
                        <div class="absolute inset-0 bg-gradient-to-br from-purple-500 to-pink-500
                                   rounded-3xl blur-xl opacity-0 group-hover:opacity-30
                                   transition-opacity duration-500"></div>

                        {{-- Card --}}
                        <div class="relative bg-white/90 dark:bg-gray-800/90 backdrop-blur-xl
                                   rounded-3xl shadow-xl overflow-hidden
                                   border border-purple-100 dark:border-purple-800/50">
                            {{-- Image --}}
                            <div class="aspect-square relative overflow-hidden">
                                {{-- Official Badge --}}
                                <div class="absolute top-3 left-3 z-10">
                                    <div class="px-3 py-1.5 bg-gradient-to-r from-purple-600 to-pink-600
                                               text-white text-xs font-bold rounded-full
                                               shadow-lg flex items-center gap-1">
                                        <i class="fas fa-shield-check"></i>
                                        <span>Official</span>
                                    </div>
                                </div>

                                {{-- Featured Star --}}
                                <div class="absolute top-3 right-3 z-10">
                                    <div class="w-10 h-10 bg-gradient-to-br from-yellow-400 to-orange-500
                                               rounded-full flex items-center justify-center
                                               shadow-lg animate-pulse">
                                        <i class="fas fa-star text-white"></i>
                                    </div>
                                </div>

                                <img src="{{ $product->main_image_url ?? 'https://via.placeholder.com/400' }}"
                                     alt="{{ $product->name }}"
                                     class="w-full h-full object-cover
                                           group-hover:scale-110 transition-transform duration-500"
                                     loading="lazy">
                            </div>

                            {{-- Content --}}
                            <div class="p-5">
                                <h3 class="font-bold text-gray-900 dark:text-white mb-2 line-clamp-2 h-12">
                                    {{ $product->name }}
                                </h3>

                                {{-- Rating --}}
                                @if($product->rating_average > 0)
                                <div class="flex items-center gap-1 mb-3">
                                    @for($i = 1; $i <= 5; $i++)
                                        @if($i <= floor($product->rating_average))
                                            <i class="fas fa-star text-yellow-400 text-sm"></i>
                                        @else
                                            <i class="far fa-star text-gray-300 text-sm"></i>
                                        @endif
                                    @endfor
                                    <span class="text-xs text-gray-500 ml-1">
                                        ({{ $product->rating_count }})
                                    </span>
                                </div>
                                @endif

                                {{-- Price --}}
                                <div class="flex items-end gap-2 mb-4">
                                    <span class="text-2xl font-black bg-gradient-to-r from-purple-600 to-pink-600
                                               bg-clip-text text-transparent">
                                        ฿{{ number_format($product->price, 0) }}
                                    </span>
                                    @if($product->compare_at_price && $product->compare_at_price > $product->price)
                                    <span class="text-sm text-gray-400 line-through">
                                        ฿{{ number_format($product->compare_at_price, 0) }}
                                    </span>
                                    @endif
                                </div>

                                {{-- CTA Button --}}
                                <a href="{{ route('official-shop.show', $product->slug) }}"
                                   class="block w-full py-3 text-center
                                         bg-gradient-to-r from-purple-600 to-pink-600
                                         hover:from-purple-700 hover:to-pink-700
                                         text-white font-bold rounded-xl
                                         shadow-lg hover:shadow-xl
                                         transition-all transform hover:scale-105">
                                    ดูรายละเอียด
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- View All Featured Button --}}
            <div class="text-center mt-10">
                <a href="{{ route('official-shop.featured') }}"
                   class="inline-flex items-center gap-2 px-8 py-4
                         bg-white dark:bg-gray-800
                         border-2 border-purple-200 dark:border-purple-700
                         text-purple-700 dark:text-purple-400 font-bold
                         rounded-2xl shadow-lg hover:shadow-xl
                         transition-all transform hover:scale-105">
                    <span>ดูสินค้าแนะนำทั้งหมด</span>
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
    @endif

    {{-- ========================================
         CATEGORIES SECTION
         ======================================== --}}
    @if($categories && $categories->count() > 0)
    <div class="py-12 bg-white/50 dark:bg-gray-800/50 backdrop-blur-sm">
        <div class="container mx-auto px-4">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 text-center">
                หมวดหมู่สินค้า
            </h2>

            <div class="flex gap-3 overflow-x-auto pb-4 scrollbar-hide justify-center flex-wrap">
                <a href="{{ route('official-shop.index') }}"
                   class="flex-shrink-0 px-6 py-3 rounded-2xl font-semibold transition-all
                         {{ !request('category') ? 'bg-gradient-to-r from-purple-600 to-pink-600 text-white shadow-lg' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-600 hover:border-purple-400' }}">
                    <i class="fas fa-grid-2 mr-2"></i>
                    ทั้งหมด
                </a>

                @foreach($categories as $category)
                <a href="{{ route('official-shop.category', $category->slug) }}"
                   class="flex-shrink-0 px-6 py-3 rounded-2xl font-semibold transition-all
                         {{ request('category') === $category->slug ? 'bg-gradient-to-r from-purple-600 to-pink-600 text-white shadow-lg' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-600 hover:border-purple-400' }}">
                    {{ $category->name }}
                </a>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- ========================================
         ALL PRODUCTS SECTION
         ======================================== --}}
    <div class="py-16">
        <div class="container mx-auto px-4">
            {{-- Section Header --}}
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
                <div>
                    <h2 class="text-3xl font-black text-gray-900 dark:text-white mb-2">
                        สินค้าทั้งหมด
                    </h2>
                    <p class="text-gray-600 dark:text-gray-400">
                        พบ {{ number_format($products->total()) }} รายการ
                    </p>
                </div>

                {{-- Filters --}}
                <div class="flex flex-wrap items-center gap-4">
                    {{-- Brand Filter --}}
                    @if($brands && $brands->count() > 0)
                    <select x-model="brandFilter"
                            @change="applyFilters()"
                            class="px-4 py-3 bg-white dark:bg-gray-800
                                  border border-gray-200 dark:border-gray-700
                                  rounded-xl text-sm font-medium
                                  focus:ring-2 focus:ring-purple-500
                                  shadow-lg">
                        <option value="">ยี่ห้อทั้งหมด</option>
                        @foreach($brands as $brand)
                        <option value="{{ $brand }}" {{ request('brand') === $brand ? 'selected' : '' }}>
                            {{ $brand }}
                        </option>
                        @endforeach
                    </select>
                    @endif

                    {{-- Sort --}}
                    <select x-model="sortBy"
                            @change="applyFilters()"
                            class="px-4 py-3 bg-white dark:bg-gray-800
                                  border border-gray-200 dark:border-gray-700
                                  rounded-xl text-sm font-medium
                                  focus:ring-2 focus:ring-purple-500
                                  shadow-lg">
                        <option value="newest">ใหม่ล่าสุด</option>
                        <option value="popular">ยอดนิยม</option>
                        <option value="price_low">ราคาต่ำ-สูง</option>
                        <option value="price_high">ราคาสูง-ต่ำ</option>
                        <option value="rating">คะแนนสูงสุด</option>
                    </select>
                </div>
            </div>

            {{-- Products Grid --}}
            @if($products->count() > 0)
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 md:gap-6">
                @foreach($products as $product)
                <div class="group">
                    <div class="relative bg-white/90 dark:bg-gray-800/90 backdrop-blur-sm
                               rounded-2xl shadow-lg overflow-hidden
                               border border-gray-100 dark:border-gray-700
                               hover:shadow-xl hover:border-purple-300 dark:hover:border-purple-600
                               transition-all duration-300
                               transform hover:-translate-y-1">
                        {{-- Image --}}
                        <a href="{{ route('official-shop.show', $product->slug) }}" class="block">
                            <div class="aspect-square relative overflow-hidden bg-gray-100 dark:bg-gray-700">
                                {{-- Official Badge --}}
                                <div class="absolute top-2 left-2 z-10">
                                    <div class="px-2 py-1 bg-gradient-to-r from-purple-600 to-pink-600
                                               text-white text-xs font-bold rounded-full
                                               shadow flex items-center gap-1">
                                        <i class="fas fa-shield-check text-[10px]"></i>
                                        <span>Official</span>
                                    </div>
                                </div>

                                {{-- Discount Badge --}}
                                @if($product->compare_at_price && $product->compare_at_price > $product->price)
                                @php
                                    $discount = round((($product->compare_at_price - $product->price) / $product->compare_at_price) * 100);
                                @endphp
                                <div class="absolute top-2 right-2 z-10">
                                    <div class="px-2 py-1 bg-red-500 text-white text-xs font-bold rounded-full shadow">
                                        -{{ $discount }}%
                                    </div>
                                </div>
                                @endif

                                <img src="{{ $product->main_image_url ?? 'https://via.placeholder.com/300' }}"
                                     alt="{{ $product->name }}"
                                     class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                                     loading="lazy">
                            </div>
                        </a>

                        {{-- Content --}}
                        <div class="p-4">
                            <a href="{{ route('official-shop.show', $product->slug) }}" class="block">
                                <h3 class="font-semibold text-gray-900 dark:text-white text-sm mb-2
                                          line-clamp-2 h-10 hover:text-purple-600 dark:hover:text-purple-400 transition-colors">
                                    {{ $product->name }}
                                </h3>
                            </a>

                            {{-- Rating --}}
                            @if($product->rating_average > 0)
                            <div class="flex items-center gap-1 mb-2">
                                <i class="fas fa-star text-yellow-400 text-xs"></i>
                                <span class="text-xs text-gray-600 dark:text-gray-400">
                                    {{ number_format($product->rating_average, 1) }}
                                </span>
                                <span class="text-xs text-gray-400">
                                    ({{ $product->rating_count }})
                                </span>
                            </div>
                            @endif

                            {{-- Price --}}
                            <div class="flex items-end gap-2">
                                <span class="text-lg font-bold text-purple-600 dark:text-purple-400">
                                    ฿{{ number_format($product->price, 0) }}
                                </span>
                                @if($product->compare_at_price && $product->compare_at_price > $product->price)
                                <span class="text-xs text-gray-400 line-through">
                                    ฿{{ number_format($product->compare_at_price, 0) }}
                                </span>
                                @endif
                            </div>

                            {{-- Commission Badge --}}
                            @auth
                            @if($product->commission_rate > 0)
                            <div class="mt-2 text-xs text-green-600 dark:text-green-400 font-medium">
                                <i class="fas fa-percentage mr-1"></i>
                                คอมมิชชั่น {{ number_format($product->commission_rate, 0) }}%
                            </div>
                            @endif
                            @endauth
                        </div>

                        {{-- Quick Add Button --}}
                        <div class="px-4 pb-4">
                            <button @click="quickAddToCart({{ $product->id }})"
                                    class="w-full py-2.5 text-center text-sm font-bold
                                          bg-gradient-to-r from-purple-600 to-pink-600
                                          hover:from-purple-700 hover:to-pink-700
                                          text-white rounded-xl
                                          shadow hover:shadow-lg
                                          transition-all transform hover:scale-105
                                          flex items-center justify-center gap-2">
                                <i class="fas fa-cart-plus"></i>
                                <span>เพิ่มลงตะกร้า</span>
                            </button>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            @if($products->hasPages())
            <div class="mt-12">
                {{ $products->links() }}
            </div>
            @endif

            @else
            {{-- Empty State --}}
            <div class="text-center py-20">
                <div class="w-24 h-24 mx-auto mb-6 bg-gray-100 dark:bg-gray-700 rounded-full
                           flex items-center justify-center">
                    <i class="fas fa-box-open text-4xl text-gray-400"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                    ไม่พบสินค้า
                </h3>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    ลองค้นหาด้วยคำอื่น หรือดูสินค้าทั้งหมด
                </p>
                <a href="{{ route('official-shop.index') }}"
                   class="inline-flex items-center gap-2 px-6 py-3
                         bg-gradient-to-r from-purple-600 to-pink-600
                         text-white font-bold rounded-xl
                         shadow-lg hover:shadow-xl transition-all">
                    <i class="fas fa-arrow-left"></i>
                    <span>ดูสินค้าทั้งหมด</span>
                </a>
            </div>
            @endif
        </div>
    </div>

    {{-- ========================================
         TRUST BADGES SECTION
         ======================================== --}}
    <div class="py-12 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                {{-- Badge 1 --}}
                <div class="text-center p-6 bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-2xl shadow-lg">
                    <div class="w-16 h-16 mx-auto mb-4 bg-gradient-to-br from-purple-500 to-pink-600
                               rounded-2xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-shield-check text-white text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-900 dark:text-white mb-1">ของแท้ 100%</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">รับประกันสินค้าทุกชิ้น</p>
                </div>

                {{-- Badge 2 --}}
                <div class="text-center p-6 bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-2xl shadow-lg">
                    <div class="w-16 h-16 mx-auto mb-4 bg-gradient-to-br from-green-500 to-emerald-600
                               rounded-2xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-truck-fast text-white text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-900 dark:text-white mb-1">ส่งฟรีทั่วไทย</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">ไม่มีขั้นต่ำ</p>
                </div>

                {{-- Badge 3 --}}
                <div class="text-center p-6 bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-2xl shadow-lg">
                    <div class="w-16 h-16 mx-auto mb-4 bg-gradient-to-br from-blue-500 to-cyan-600
                               rounded-2xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-rotate-left text-white text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-900 dark:text-white mb-1">เปลี่ยนคืน 7 วัน</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">ไม่พอใจคืนเงิน</p>
                </div>

                {{-- Badge 4 --}}
                <div class="text-center p-6 bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-2xl shadow-lg">
                    <div class="w-16 h-16 mx-auto mb-4 bg-gradient-to-br from-orange-500 to-red-600
                               rounded-2xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-headset text-white text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-900 dark:text-white mb-1">บริการ 24/7</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">ทีมงานพร้อมช่วยเหลือ</p>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
/**
 * Official Shop Manager - Alpine.js Component
 */
function officialShopManager() {
    return {
        searchQuery: '{{ request("search", "") }}',
        sortBy: '{{ request("sort_by", "newest") }}',
        brandFilter: '{{ request("brand", "") }}',

        init() {
            console.log('Official Shop Manager initialized');
        },

        submitSearch() {
            if (this.searchQuery.trim()) {
                const url = new URL('{{ route("official-shop.index") }}');
                url.searchParams.set('search', this.searchQuery.trim());
                window.location.href = url.toString();
            }
        },

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
                    // Dispatch event to update cart badge
                    window.dispatchEvent(new CustomEvent('cart-updated'));

                    // Show success notification
                    window.dispatchEvent(new CustomEvent('notify', {
                        detail: {
                            message: 'เพิ่มสินค้าลงตะกร้าสำเร็จ',
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
                        message: error.message || 'ไม่สามารถเพิ่มสินค้าลงตะกร้าได้',
                        type: 'error'
                    }
                }));
            }
        }
    };
}

window.officialShopManager = officialShopManager;
</script>
@endpush

@push('styles')
<style>
.perspective-1000 {
    perspective: 1000px;
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

.scrollbar-hide::-webkit-scrollbar {
    display: none;
}

/* ========================================
   Premium Lava Effect สำหรับ Official Shop
   สีม่วง, ชมพู, ทอง - หรูหราและพรีเมียม
   ======================================== */

/* Premium blob 1 - Purple/Violet */
.premium-blob-1 {
    width: 350px;
    height: 380px;
    background: linear-gradient(180deg, #7c3aed 0%, #a855f7 40%, #c084fc 70%, #7c3aed 100%);
    left: 5%;
    top: 15%;
    animation: premiumFloat1 18s ease-in-out infinite, premiumMorph1 12s ease-in-out infinite;
    filter: blur(70px);
    opacity: 0.35;
}

/* Premium blob 2 - Pink/Magenta */
.premium-blob-2 {
    width: 280px;
    height: 300px;
    background: linear-gradient(180deg, #ec4899 0%, #f472b6 40%, #fb7185 70%, #ec4899 100%);
    right: 15%;
    top: 25%;
    animation: premiumFloat2 20s ease-in-out infinite, premiumMorph2 14s ease-in-out infinite;
    animation-delay: -4s;
    filter: blur(70px);
    opacity: 0.35;
}

/* Premium blob 3 - Gold/Amber */
.premium-blob-3 {
    width: 300px;
    height: 320px;
    background: linear-gradient(180deg, #f59e0b 0%, #fbbf24 40%, #fcd34d 70%, #f59e0b 100%);
    left: 40%;
    top: 55%;
    animation: premiumFloat3 22s ease-in-out infinite, premiumMorph1 16s ease-in-out infinite;
    animation-delay: -8s;
    filter: blur(70px);
    opacity: 0.3;
}

/* Premium blob 4 - Deep Purple */
.premium-blob-4 {
    width: 250px;
    height: 270px;
    background: linear-gradient(180deg, #6d28d9 0%, #8b5cf6 40%, #a78bfa 70%, #6d28d9 100%);
    right: 5%;
    top: 65%;
    animation: premiumFloat1 16s ease-in-out infinite, premiumMorph2 10s ease-in-out infinite;
    animation-delay: -2s;
    filter: blur(70px);
    opacity: 0.35;
}

/* Premium blob 5 - Rose Pink */
.premium-blob-5 {
    width: 220px;
    height: 240px;
    background: linear-gradient(180deg, #db2777 0%, #f472b6 50%, #db2777 100%);
    left: 20%;
    top: 70%;
    animation: premiumFloat2 19s ease-in-out infinite, premiumMorph1 13s ease-in-out infinite;
    animation-delay: -6s;
    filter: blur(70px);
    opacity: 0.3;
}

/* Premium blob 6 - Warm Orange */
.premium-blob-6 {
    width: 200px;
    height: 220px;
    background: linear-gradient(180deg, #ea580c 0%, #f97316 50%, #ea580c 100%);
    left: 60%;
    top: 20%;
    animation: premiumFloat3 17s ease-in-out infinite, premiumMorph2 11s ease-in-out infinite;
    animation-delay: -10s;
    filter: blur(70px);
    opacity: 0.3;
}

/* Premium Float Animations */
@keyframes premiumFloat1 {
    0%, 100% { transform: translate(0, 0) scale(1) rotate(0deg); }
    25% { transform: translate(40px, -60px) scale(1.08) rotate(3deg); }
    50% { transform: translate(-30px, -120px) scale(0.95) rotate(-2deg); }
    75% { transform: translate(50px, -60px) scale(1.05) rotate(1deg); }
}

@keyframes premiumFloat2 {
    0%, 100% { transform: translate(0, 0) scale(1) rotate(0deg); }
    33% { transform: translate(-50px, -100px) scale(1.1) rotate(-3deg); }
    66% { transform: translate(40px, -50px) scale(0.92) rotate(2deg); }
}

@keyframes premiumFloat3 {
    0%, 100% { transform: translate(0, 0) scale(1); }
    50% { transform: translate(60px, -140px) scale(1.12); }
}

/* Premium Morph Animations */
@keyframes premiumMorph1 {
    0%, 100% { border-radius: 40% 60% 55% 45% / 55% 45% 60% 40%; }
    25% { border-radius: 55% 45% 40% 60% / 45% 55% 50% 50%; }
    50% { border-radius: 45% 55% 50% 50% / 50% 50% 55% 45%; }
    75% { border-radius: 50% 50% 60% 40% / 60% 40% 45% 55%; }
}

@keyframes premiumMorph2 {
    0%, 100% { border-radius: 50% 50% 45% 55% / 45% 55% 50% 50%; }
    33% { border-radius: 45% 55% 50% 50% / 55% 45% 55% 45%; }
    66% { border-radius: 55% 45% 55% 45% / 45% 55% 45% 55%; }
}

/* Dark Mode - Premium RGB Glow */
.dark .premium-blob-1 {
    background: linear-gradient(180deg, #8b5cf6 0%, #a78bfa 50%, #8b5cf6 100%);
    filter: blur(60px);
    opacity: 0.6;
    box-shadow: 0 0 60px rgba(139, 92, 246, 0.8), 0 0 120px rgba(139, 92, 246, 0.6), 0 0 180px rgba(139, 92, 246, 0.4);
}

.dark .premium-blob-2 {
    background: linear-gradient(180deg, #ec4899 0%, #f472b6 50%, #ec4899 100%);
    filter: blur(60px);
    opacity: 0.6;
    box-shadow: 0 0 60px rgba(236, 72, 153, 0.8), 0 0 120px rgba(236, 72, 153, 0.6), 0 0 180px rgba(236, 72, 153, 0.4);
}

.dark .premium-blob-3 {
    background: linear-gradient(180deg, #fbbf24 0%, #fcd34d 50%, #fbbf24 100%);
    filter: blur(60px);
    opacity: 0.55;
    box-shadow: 0 0 60px rgba(251, 191, 36, 0.8), 0 0 120px rgba(251, 191, 36, 0.6), 0 0 180px rgba(251, 191, 36, 0.4);
}

.dark .premium-blob-4 {
    background: linear-gradient(180deg, #7c3aed 0%, #8b5cf6 50%, #7c3aed 100%);
    filter: blur(60px);
    opacity: 0.6;
    box-shadow: 0 0 60px rgba(124, 58, 237, 0.8), 0 0 120px rgba(124, 58, 237, 0.6), 0 0 180px rgba(124, 58, 237, 0.4);
}

.dark .premium-blob-5 {
    background: linear-gradient(180deg, #db2777 0%, #ec4899 50%, #db2777 100%);
    filter: blur(60px);
    opacity: 0.55;
    box-shadow: 0 0 60px rgba(219, 39, 119, 0.8), 0 0 120px rgba(219, 39, 119, 0.6), 0 0 180px rgba(219, 39, 119, 0.4);
}

.dark .premium-blob-6 {
    background: linear-gradient(180deg, #f97316 0%, #fb923c 50%, #f97316 100%);
    filter: blur(60px);
    opacity: 0.55;
    box-shadow: 0 0 60px rgba(249, 115, 22, 0.8), 0 0 120px rgba(249, 115, 22, 0.6), 0 0 180px rgba(249, 115, 22, 0.4);
}

/* Mobile Optimization */
@media (max-width: 768px) {
    .premium-lava .lava-blob {
        transform: scale(0.6);
        filter: blur(40px);
    }
    .premium-blob-5,
    .premium-blob-6 {
        display: none;
    }
    .dark .premium-lava .lava-blob {
        filter: blur(50px);
    }
}

/* Reduced motion preference */
@media (prefers-reduced-motion: reduce) {
    .premium-lava .lava-blob {
        animation: none;
        transform: translateY(0);
    }
}
</style>
@endpush
@endsection
