{{--
    Official Shop Category Page - Ultra Premium Layout V3

    หน้าแสดงสินค้าตามหมวดหมู่ของร้านทางการ (Official Shop)
    ออกแบบให้สอดคล้องกับ index.blade.php - หรูหรา พรีเมี่ยม

    Features:
    - Premium Category Hero
    - Product Grid with Sorting
    - Glassmorphism Cards
    - Responsive Design
    - Dark Mode Support
--}}

@extends('layouts.storefront')

@section('title', $category->name . ' - Official Shop')

@section('meta')
<meta name="description" content="{{ $category->name }} - สินค้าพรีเมี่ยมคุณภาพระดับโลกจาก Official Shop รับประกัน 100% ของแท้">
@endsection

{{-- Premium Animated Lava Background --}}
@section('lava-background')
<div class="lava-background luxury-lava" aria-hidden="true">
    <div class="lava-blob luxury-blob-1"></div>
    <div class="lava-blob luxury-blob-2"></div>
    <div class="lava-blob luxury-blob-3"></div>
    <div class="lava-blob luxury-blob-4"></div>
    <div class="lava-blob luxury-blob-5"></div>
    <div class="lava-blob luxury-blob-6"></div>
</div>
@endsection

@section('content')
<div x-data="categoryShopManager()" x-init="init()" class="min-h-screen">

    {{-- ════════════════════════════════════════════════════
         ✨ CATEGORY HERO SECTION
         ════════════════════════════════════════════════════ --}}
    <section class="relative overflow-hidden">
        {{-- Background Gradient --}}
        <div class="absolute inset-0 bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900"></div>
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_left,rgba(168,85,247,0.4),transparent_50%)]"></div>
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_bottom_right,rgba(236,72,153,0.3),transparent_50%)]"></div>
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_center,rgba(251,191,36,0.2),transparent_60%)]"></div>

        {{-- Floating 3D Elements --}}
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute top-20 left-[10%] animate-float-slow opacity-20">
                <i class="fas fa-gem text-6xl text-amber-300"></i>
            </div>
            <div class="absolute top-32 right-[15%] animate-float-medium opacity-20">
                <i class="fas fa-crown text-5xl text-purple-300"></i>
            </div>
        </div>

        {{-- Hero Content --}}
        <div class="relative container mx-auto px-4 py-16 md:py-24 z-10">
            {{-- Breadcrumb --}}
            <nav class="flex items-center gap-2 text-sm mb-8">
                <a href="{{ route('home') }}" class="text-white/60 hover:text-white transition-colors">
                    <i class="fas fa-home"></i>
                </a>
                <i class="fas fa-chevron-right text-white/40 text-xs"></i>
                <a href="{{ route('official-shop.index') }}" class="text-white/60 hover:text-white transition-colors">
                    Official Shop
                </a>
                <i class="fas fa-chevron-right text-white/40 text-xs"></i>
                <span class="text-amber-400 font-semibold">{{ $category->name }}</span>
            </nav>

            <div class="text-center max-w-4xl mx-auto">
                {{-- Premium Badge --}}
                <div class="inline-flex items-center gap-3 px-6 py-3 mb-8
                           bg-gradient-to-r from-amber-500/20 via-purple-500/20 to-pink-500/20
                           backdrop-blur-xl border border-white/20
                           rounded-full shadow-2xl">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-amber-400 via-amber-500 to-amber-600
                               flex items-center justify-center shadow-lg
                               ring-2 ring-amber-400/30">
                        <i class="fas fa-crown text-white text-sm"></i>
                    </div>
                    <span class="text-white font-bold tracking-wide">OFFICIAL SHOP</span>
                    <i class="fas fa-check-circle text-green-400"></i>
                </div>

                {{-- Category Title --}}
                <h1 class="text-5xl md:text-6xl lg:text-7xl font-black mb-6 leading-tight animate-fade-in-up">
                    <span class="block bg-gradient-to-r from-amber-300 via-yellow-200 to-amber-400
                               bg-clip-text text-transparent
                               drop-shadow-[0_0_40px_rgba(251,191,36,0.5)]">
                        {{ $category->name }}
                    </span>
                </h1>

                {{-- Category Description --}}
                @if($category->description)
                <p class="text-lg md:text-xl text-white/80 mb-10 max-w-2xl mx-auto
                         font-light tracking-wide animate-fade-in-up animation-delay-100">
                    {{ $category->description }}
                </p>
                @endif

                {{-- Stats --}}
                <div class="flex flex-wrap justify-center gap-4 animate-fade-in-up animation-delay-200">
                    <div class="px-6 py-4 bg-white/10 backdrop-blur-xl rounded-2xl
                               border border-white/20 hover:border-amber-400/50
                               transition-all duration-300">
                        <div class="text-3xl font-black text-white mb-1
                                   bg-gradient-to-r from-amber-300 to-yellow-200
                                   bg-clip-text text-transparent">
                            {{ number_format($products->total()) }}
                        </div>
                        <div class="text-white/70 text-sm font-medium">
                            <i class="fas fa-box mr-1"></i> สินค้าในหมวดหมู่
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ════════════════════════════════════════════════════
         🛍️ PRODUCTS SECTION - LUXURY GRID
         ════════════════════════════════════════════════════ --}}
    <section class="py-20 bg-white dark:bg-gray-900">
        <div class="container mx-auto px-4">
            {{-- Section Header with Filters --}}
            <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6 mb-12">
                <div>
                    <div class="inline-flex items-center gap-2 px-4 py-2
                               bg-purple-100 dark:bg-purple-900/30
                               rounded-full mb-4">
                        <i class="fas fa-tag text-purple-600 dark:text-purple-400"></i>
                        <span class="text-purple-700 dark:text-purple-300 font-semibold text-sm">
                            {{ $category->name }}
                        </span>
                    </div>
                    <h2 class="text-4xl md:text-5xl font-black text-gray-900 dark:text-white mb-3">
                        สินค้าในหมวดหมู่
                    </h2>
                    <p class="text-gray-600 dark:text-gray-400 text-lg">
                        พบ <span class="font-bold text-amber-600">{{ number_format($products->total()) }}</span> รายการ
                    </p>
                </div>

                {{-- Filters --}}
                <div class="flex flex-wrap items-center gap-4">
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

                    {{-- Back to Shop --}}
                    <a href="{{ route('official-shop.index') }}"
                       class="inline-flex items-center gap-2 px-5 py-4
                             bg-gray-100 dark:bg-gray-800
                             border-2 border-gray-200 dark:border-gray-700
                             hover:border-amber-400 dark:hover:border-amber-500
                             rounded-xl text-sm font-medium
                             shadow-lg transition-all">
                        <i class="fas fa-th-large text-gray-600 dark:text-gray-400"></i>
                        <span class="text-gray-700 dark:text-gray-300">ดูทั้งหมด</span>
                    </a>
                </div>
            </div>

            {{-- Search Bar --}}
            <div class="max-w-2xl mb-12">
                <div class="relative">
                    <input type="text"
                           x-model="searchQuery"
                           @keyup.enter="applyFilters()"
                           placeholder="ค้นหาสินค้าในหมวด {{ $category->name }}..."
                           class="w-full px-6 py-4 pl-14
                                 bg-white dark:bg-gray-800
                                 border-2 border-gray-200 dark:border-gray-700
                                 hover:border-amber-400 dark:hover:border-amber-500
                                 rounded-xl text-sm
                                 focus:outline-none focus:ring-4 focus:ring-amber-500/20 focus:border-amber-500
                                 shadow-lg transition-all">
                    <div class="absolute left-5 top-1/2 -translate-y-1/2 text-gray-400">
                        <i class="fas fa-search"></i>
                    </div>
                    <button @click="applyFilters()"
                            class="absolute right-3 top-1/2 -translate-y-1/2
                                  px-5 py-2.5 bg-gradient-to-r from-amber-500 to-orange-500
                                  hover:from-amber-600 hover:to-orange-600
                                  text-white font-bold text-sm rounded-lg
                                  transition-all">
                        ค้นหา
                    </button>
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

                                <img src="{{ $product->primary_image_url ?? 'https://via.placeholder.com/300' }}"
                                     alt="{{ $product->name }}"
                                     class="w-full h-full object-cover
                                           group-hover:scale-110 transition-transform duration-700"
                                     loading="lazy"
                                     onerror="this.src='https://via.placeholder.com/300'">

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

                            {{-- Commission Badge --}}
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
                    ไม่พบสินค้าในหมวดหมู่นี้
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
         🏆 TRUST BADGES SECTION
         ════════════════════════════════════════════════════ --}}
    <section class="py-16 bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 relative overflow-hidden">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_50%,rgba(251,191,36,0.15),transparent_50%)]"></div>
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_80%_50%,rgba(168,85,247,0.15),transparent_50%)]"></div>

        <div class="container mx-auto px-4 relative z-10">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-6">
                {{-- Badge 1: Authentic --}}
                <div class="group p-6 bg-white/5 backdrop-blur-xl rounded-2xl
                           border border-white/10 hover:border-amber-500/50
                           transform hover:-translate-y-1 transition-all duration-500 text-center">
                    <div class="w-14 h-14 mx-auto mb-4
                               bg-gradient-to-br from-amber-400 to-orange-500
                               rounded-xl flex items-center justify-center
                               shadow-lg shadow-amber-500/30">
                        <i class="fas fa-gem text-white text-2xl"></i>
                    </div>
                    <h3 class="text-sm font-bold text-white mb-1">ของแท้ 100%</h3>
                    <p class="text-white/50 text-xs">รับประกันจากระบบ</p>
                </div>

                {{-- Badge 2: Free Shipping --}}
                <div class="group p-6 bg-white/5 backdrop-blur-xl rounded-2xl
                           border border-white/10 hover:border-green-500/50
                           transform hover:-translate-y-1 transition-all duration-500 text-center">
                    <div class="w-14 h-14 mx-auto mb-4
                               bg-gradient-to-br from-green-400 to-emerald-500
                               rounded-xl flex items-center justify-center
                               shadow-lg shadow-green-500/30">
                        <i class="fas fa-truck-fast text-white text-2xl"></i>
                    </div>
                    <h3 class="text-sm font-bold text-white mb-1">ส่งฟรีทั่วไทย</h3>
                    <p class="text-white/50 text-xs">จัดส่ง 1-3 วัน</p>
                </div>

                {{-- Badge 3: Easy Return --}}
                <div class="group p-6 bg-white/5 backdrop-blur-xl rounded-2xl
                           border border-white/10 hover:border-blue-500/50
                           transform hover:-translate-y-1 transition-all duration-500 text-center">
                    <div class="w-14 h-14 mx-auto mb-4
                               bg-gradient-to-br from-blue-400 to-cyan-500
                               rounded-xl flex items-center justify-center
                               shadow-lg shadow-blue-500/30">
                        <i class="fas fa-rotate-left text-white text-2xl"></i>
                    </div>
                    <h3 class="text-sm font-bold text-white mb-1">เปลี่ยนคืน 7 วัน</h3>
                    <p class="text-white/50 text-xs">ไม่พอใจยินดีคืนเงิน</p>
                </div>

                {{-- Badge 4: 24/7 Support --}}
                <div class="group p-6 bg-white/5 backdrop-blur-xl rounded-2xl
                           border border-white/10 hover:border-purple-500/50
                           transform hover:-translate-y-1 transition-all duration-500 text-center">
                    <div class="w-14 h-14 mx-auto mb-4
                               bg-gradient-to-br from-purple-400 to-pink-500
                               rounded-xl flex items-center justify-center
                               shadow-lg shadow-purple-500/30">
                        <i class="fas fa-headset text-white text-2xl"></i>
                    </div>
                    <h3 class="text-sm font-bold text-white mb-1">บริการ 24/7</h3>
                    <p class="text-white/50 text-xs">พร้อมให้บริการตลอด</p>
                </div>
            </div>
        </div>
    </section>
</div>

@push('scripts')
<script>
/**
 * Category Shop Manager - Alpine.js Component
 *
 * จัดการหน้าหมวดหมู่สินค้า Official Shop
 */
function categoryShopManager() {
    return {
        searchQuery: '{{ request("search", "") }}',
        sortBy: '{{ request("sort_by", "newest") }}',

        /**
         * Initialize component
         */
        init() {
            console.log('🏷️ Category Shop Manager initialized');
        },

        /**
         * Apply filters และ redirect
         */
        applyFilters() {
            const url = new URL(window.location.href);

            if (this.sortBy) {
                url.searchParams.set('sort_by', this.sortBy);
            }

            if (this.searchQuery.trim()) {
                url.searchParams.set('search', this.searchQuery.trim());
            } else {
                url.searchParams.delete('search');
            }

            // รีเซ็ตหน้าเป็นหน้าแรกเมื่อเปลี่ยนตัวกรอง
            url.searchParams.delete('page');

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

window.categoryShopManager = categoryShopManager;
</script>
@endpush

@push('styles')
<style>
/* ════════════════════════════════════════════════════
   🎨 CUSTOM CSS ANIMATIONS & EFFECTS
   ════════════════════════════════════════════════════ */

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

@keyframes floatSlow {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    50% { transform: translateY(-30px) rotate(5deg); }
}

@keyframes floatMedium {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    50% { transform: translateY(-20px) rotate(-3deg); }
}

/* ═══════════════════════════════════════════
   LUXURY LAVA BACKGROUND
   ═══════════════════════════════════════════ */
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
    .dark .luxury-lava .lava-blob {
        filter: blur(60px);
    }
}

/* ═══════════════════════════════════════════
   REDUCED MOTION PREFERENCE
   ═══════════════════════════════════════════ */
@media (prefers-reduced-motion: reduce) {
    .luxury-lava .lava-blob,
    .animate-float-slow,
    .animate-float-medium {
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
