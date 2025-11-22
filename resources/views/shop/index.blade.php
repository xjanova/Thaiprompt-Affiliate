{{--
    หน้าร้านค้าออนไลน์ - Version 3

    ใช้ Arrow X Layout + Tailwind CSS + Alpine.js
    รองรับ Dark Mode, Glassmorphism, 3D Effects
    มีตัวกรองร้านระบบและร้านผู้เช่า 5 ดาว
--}}

@extends('layouts.user-arrow-x')

@section('title', 'ร้านค้าออนไลน์ - ช้อปสินค้าคุณภาพ ราคาดี')

@section('meta')
<meta name="description" content="ช้อปสินค้าคุณภาพหลากหลายหมวดหมู่ ราคาพิเศษ ส่งฟรีทั่วประเทศ รองรับร้านของระบบและร้านผู้เช่า 5 ดาว">
@endsection

@section('content')
<div class="container-fluid px-4 py-6" x-data="shopManager()" x-init="init()">

    {{-- Hero Section --}}
    <div class="mb-8">
        <div class="relative overflow-hidden rounded-3xl">
            {{-- Gradient Background --}}
            <div class="absolute inset-0 bg-gradient-to-br from-purple-600 via-pink-600 to-orange-600 dark:from-purple-800 dark:via-pink-800 dark:to-orange-800"></div>

            {{-- Pattern Overlay --}}
            <div class="absolute inset-0 opacity-10">
                <div class="absolute inset-0" style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 40px 40px;"></div>
            </div>

            {{-- Content --}}
            <div class="relative z-10 px-8 py-12 md:py-16">
                <div class="max-w-4xl mx-auto text-center text-white">
                    {{-- Badge --}}
                    <div class="inline-flex items-center gap-2
                                backdrop-blur-xl bg-white/20 dark:bg-black/20
                                border border-white/30 dark:border-white/10
                                px-4 py-2 rounded-full mb-6">
                        <i class="fas fa-star text-yellow-300"></i>
                        <span class="font-semibold text-sm">ร้านค้าออนไลน์คุณภาพพรีเมี่ยม</span>
                    </div>

                    {{-- Title --}}
                    <h1 class="text-4xl md:text-6xl font-black mb-4 tracking-tight
                               drop-shadow-2xl animate-fade-in">
                        ค้นพบสินค้าที่ใช่สำหรับคุณ
                    </h1>

                    <p class="text-xl md:text-2xl text-white/90 mb-8 font-medium">
                        สินค้าคุณภาพหลากหลาย ราคาดีที่สุด ส่งฟรีทั่วไทย
                    </p>

                    {{-- Search Bar with Alpine.js --}}
                    <div class="max-w-2xl mx-auto">
                        <div class="relative">
                            <input type="text"
                                   x-model="filters.search"
                                   @keyup.enter="applyFilters()"
                                   placeholder="ค้นหาสินค้า ยี่ห้อ หรือหมวดหมู่..."
                                   class="w-full px-6 py-4 pr-14 rounded-2xl
                                          text-gray-900 dark:text-gray-100
                                          bg-white dark:bg-gray-800
                                          font-medium shadow-2xl
                                          border-2 border-transparent
                                          focus:border-white/50 dark:focus:border-gray-600
                                          focus:outline-none focus:ring-4 focus:ring-white/30 dark:focus:ring-gray-700
                                          transition-all
                                          placeholder-gray-400 dark:placeholder-gray-500">

                            <button @click="applyFilters()"
                                    class="absolute right-2 top-1/2 -translate-y-1/2
                                           p-3 bg-gradient-to-r from-purple-600 to-pink-600
                                           hover:from-purple-700 hover:to-pink-700
                                           text-white rounded-xl shadow-lg
                                           transition-all transform hover:scale-105">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>

                    {{-- Quick Stats --}}
                    <div class="flex flex-wrap gap-4 justify-center mt-8">
                        <div class="flex items-center gap-2
                                    backdrop-blur-xl bg-white/20 dark:bg-black/20
                                    border border-white/30 dark:border-white/10
                                    px-4 py-2 rounded-xl">
                            <i class="fas fa-shipping-fast text-yellow-300"></i>
                            <span class="font-semibold text-sm">ส่งฟรีเมื่อซื้อครบ 500฿</span>
                        </div>

                        <div class="flex items-center gap-2
                                    backdrop-blur-xl bg-white/20 dark:bg-black/20
                                    border border-white/30 dark:border-white/10
                                    px-4 py-2 rounded-xl">
                            <i class="fas fa-shield-alt text-green-300"></i>
                            <span class="font-semibold text-sm">ของแท้ 100%</span>
                        </div>

                        <div class="flex items-center gap-2
                                    backdrop-blur-xl bg-white/20 dark:bg-black/20
                                    border border-white/30 dark:border-white/10
                                    px-4 py-2 rounded-xl">
                            <i class="fas fa-clock text-blue-300"></i>
                            <span class="font-semibold text-sm">จัดส่งรวดเร็ว 1-2 วัน</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Shop Type Filter Tabs (V3 Feature) --}}
    <div class="mb-8">
        <div class="glass-fusion-card rounded-2xl p-6 shadow-2xl">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-store text-purple-600 dark:text-purple-400 mr-2"></i>
                เลือกประเภทร้านค้า
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                {{-- All Shops --}}
                <button @click="setShopType('')"
                        :class="filters.shop_type === '' ?
                                'bg-gradient-to-r from-purple-600 to-pink-600 text-white shadow-lg scale-105' :
                                'glass-fusion-card hover:shadow-lg hover:scale-102'"
                        class="group p-6 rounded-2xl transition-all duration-300 border-2 border-transparent
                               hover:border-purple-300 dark:hover:border-purple-700">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 rounded-2xl flex items-center justify-center
                                    bg-gradient-to-br from-purple-100 to-pink-100 dark:from-purple-900/50 dark:to-pink-900/50
                                    group-hover:scale-110 transition-transform">
                            <i class="fas fa-th-large text-3xl text-purple-600 dark:text-purple-400"></i>
                        </div>

                        <div class="flex-1 text-left">
                            <h3 class="text-lg font-bold"
                                :class="filters.shop_type === '' ? 'text-white' : 'text-gray-900 dark:text-white'">
                                ร้านค้าทั้งหมด
                            </h3>
                            <p class="text-sm"
                               :class="filters.shop_type === '' ? 'text-white/80' : 'text-gray-600 dark:text-gray-400'">
                                {{ number_format($stats['all']) }} สินค้า
                            </p>
                        </div>

                        <i class="fas fa-check-circle text-2xl"
                           x-show="filters.shop_type === ''"
                           x-transition></i>
                    </div>
                </button>

                {{-- Official Store --}}
                <button @click="setShopType('official')"
                        :class="filters.shop_type === 'official' ?
                                'bg-gradient-to-r from-blue-600 to-cyan-600 text-white shadow-lg scale-105' :
                                'glass-fusion-card hover:shadow-lg hover:scale-102'"
                        class="group p-6 rounded-2xl transition-all duration-300 border-2 border-transparent
                               hover:border-blue-300 dark:hover:border-blue-700">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 rounded-2xl flex items-center justify-center
                                    bg-gradient-to-br from-blue-100 to-cyan-100 dark:from-blue-900/50 dark:to-cyan-900/50
                                    group-hover:scale-110 transition-transform">
                            <i class="fas fa-crown text-3xl text-blue-600 dark:text-blue-400"></i>
                        </div>

                        <div class="flex-1 text-left">
                            <h3 class="text-lg font-bold"
                                :class="filters.shop_type === 'official' ? 'text-white' : 'text-gray-900 dark:text-white'">
                                ร้านของระบบ
                            </h3>
                            <p class="text-sm"
                               :class="filters.shop_type === 'official' ? 'text-white/80' : 'text-gray-600 dark:text-gray-400'">
                                {{ number_format($stats['official']) }} สินค้า
                            </p>
                        </div>

                        <i class="fas fa-check-circle text-2xl"
                           x-show="filters.shop_type === 'official'"
                           x-transition></i>
                    </div>
                </button>

                {{-- Premium Sellers (5 Stars) --}}
                <button @click="setShopType('premium')"
                        :class="filters.shop_type === 'premium' ?
                                'bg-gradient-to-r from-yellow-500 to-orange-600 text-white shadow-lg scale-105' :
                                'glass-fusion-card hover:shadow-lg hover:scale-102'"
                        class="group p-6 rounded-2xl transition-all duration-300 border-2 border-transparent
                               hover:border-yellow-300 dark:hover:border-yellow-700">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 rounded-2xl flex items-center justify-center
                                    bg-gradient-to-br from-yellow-100 to-orange-100 dark:from-yellow-900/50 dark:to-orange-900/50
                                    group-hover:scale-110 transition-transform">
                            <i class="fas fa-star text-3xl text-yellow-600 dark:text-yellow-400"></i>
                        </div>

                        <div class="flex-1 text-left">
                            <h3 class="text-lg font-bold"
                                :class="filters.shop_type === 'premium' ? 'text-white' : 'text-gray-900 dark:text-white'">
                                ร้านผู้เช่า 5 ดาว
                            </h3>
                            <p class="text-sm"
                               :class="filters.shop_type === 'premium' ? 'text-white/80' : 'text-gray-600 dark:text-gray-400'">
                                {{ number_format($stats['premium']) }} สินค้า
                            </p>
                        </div>

                        <i class="fas fa-check-circle text-2xl"
                           x-show="filters.shop_type === 'premium'"
                           x-transition></i>
                    </div>
                </button>
            </div>
        </div>
    </div>

    {{-- Main Content Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">

        {{-- Filters Sidebar --}}
        <aside class="lg:col-span-1">
            <div class="glass-fusion-card rounded-2xl shadow-2xl overflow-hidden sticky top-4">
                {{-- Header --}}
                <div class="bg-gradient-to-r from-purple-600 via-pink-600 to-orange-600
                           dark:from-purple-800 dark:via-pink-800 dark:to-orange-800
                           px-6 py-4">
                    <h2 class="text-lg font-bold text-white flex items-center gap-2">
                        <i class="fas fa-filter"></i>
                        ตัวกรองขั้นสูง
                    </h2>
                </div>

                <div class="p-6 space-y-6">
                    {{-- Category Filter --}}
                    @if($categories && $categories->count() > 0)
                    <div>
                        <label class="block text-sm font-bold text-gray-900 dark:text-white mb-3">
                            <i class="fas fa-th text-purple-600 dark:text-purple-400 mr-2"></i>
                            หมวดหมู่
                        </label>

                        <div class="space-y-2 max-h-64 overflow-y-auto pr-2 custom-scrollbar">
                            @foreach($categories as $category)
                            <label class="flex items-center gap-3 cursor-pointer
                                         hover:bg-purple-50 dark:hover:bg-purple-900/20
                                         p-3 rounded-xl transition-all group">
                                <input type="radio"
                                       x-model="filters.category"
                                       value="{{ $category->slug }}"
                                       @change="applyFilters()"
                                       class="w-5 h-5 text-purple-600 focus:ring-purple-500 dark:bg-gray-700 dark:border-gray-600">

                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300
                                           group-hover:text-purple-600 dark:group-hover:text-purple-400 flex-1">
                                    {{ $category->name }}
                                </span>
                            </label>
                            @endforeach

                            <button type="button"
                                    x-show="filters.category"
                                    @click="filters.category = ''; applyFilters()"
                                    class="w-full text-sm text-purple-600 dark:text-purple-400
                                          hover:text-purple-800 dark:hover:text-purple-300
                                          font-semibold py-2 hover:bg-purple-50 dark:hover:bg-purple-900/20
                                          rounded-lg transition-all">
                                <i class="fas fa-times mr-1"></i> ล้างหมวดหมู่
                            </button>
                        </div>
                    </div>
                    @endif

                    {{-- Price Range --}}
                    <div>
                        <label class="block text-sm font-bold text-gray-900 dark:text-white mb-3">
                            <i class="fas fa-dollar-sign text-green-600 dark:text-green-400 mr-2"></i>
                            ช่วงราคา (฿)
                        </label>

                        <div class="space-y-3">
                            <input type="number"
                                   x-model="filters.min_price"
                                   @change="applyFilters()"
                                   placeholder="ราคาต่ำสุด"
                                   min="0"
                                   class="w-full px-4 py-3 rounded-xl
                                         border-2 border-gray-200 dark:border-gray-700
                                         bg-white dark:bg-gray-800
                                         text-gray-900 dark:text-gray-100
                                         focus:border-purple-500 dark:focus:border-purple-400
                                         focus:ring-4 focus:ring-purple-100 dark:focus:ring-purple-900/50
                                         transition-all">

                            <div class="flex items-center justify-center">
                                <div class="h-0.5 w-4 bg-gray-300 dark:bg-gray-600"></div>
                            </div>

                            <input type="number"
                                   x-model="filters.max_price"
                                   @change="applyFilters()"
                                   placeholder="ราคาสูงสุด"
                                   min="0"
                                   class="w-full px-4 py-3 rounded-xl
                                         border-2 border-gray-200 dark:border-gray-700
                                         bg-white dark:bg-gray-800
                                         text-gray-900 dark:text-gray-100
                                         focus:border-purple-500 dark:focus:border-purple-400
                                         focus:ring-4 focus:ring-purple-100 dark:focus:ring-purple-900/50
                                         transition-all">
                        </div>
                    </div>

                    {{-- Brand Filter --}}
                    @if($brands && $brands->count() > 0)
                    <div>
                        <label class="block text-sm font-bold text-gray-900 dark:text-white mb-3">
                            <i class="fas fa-certificate text-blue-600 dark:text-blue-400 mr-2"></i>
                            ยี่ห้อ
                        </label>

                        <select x-model="filters.brand"
                                @change="applyFilters()"
                                class="w-full px-4 py-3 rounded-xl
                                      border-2 border-gray-200 dark:border-gray-700
                                      bg-white dark:bg-gray-800
                                      text-gray-900 dark:text-gray-100
                                      focus:border-purple-500 dark:focus:border-purple-400
                                      focus:ring-4 focus:ring-purple-100 dark:focus:ring-purple-900/50
                                      transition-all font-medium">
                            <option value="">ทุกยี่ห้อ</option>
                            @foreach($brands as $brand)
                            <option value="{{ $brand }}">{{ $brand }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    {{-- Reset Button --}}
                    <button @click="resetFilters()"
                            class="w-full px-6 py-3.5
                                  bg-gradient-to-r from-red-500 to-pink-600
                                  hover:from-red-600 hover:to-pink-700
                                  text-white font-bold rounded-xl
                                  shadow-lg hover:shadow-xl
                                  transition-all transform hover:scale-105
                                  flex items-center justify-center gap-2">
                        <i class="fas fa-redo"></i>
                        <span>ล้างตัวกรองทั้งหมด</span>
                    </button>
                </div>
            </div>
        </aside>

        {{-- Products Grid --}}
        <div class="lg:col-span-3">
            {{-- Sort and Results Header --}}
            <div class="glass-fusion-card rounded-2xl shadow-lg p-6 mb-6">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    {{-- Results Count --}}
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-gradient-to-br from-purple-100 to-pink-100
                                   dark:from-purple-900/50 dark:to-pink-900/50
                                   rounded-xl flex items-center justify-center">
                            <span class="text-xl font-black text-purple-600 dark:text-purple-400">
                                {{ $products->total() }}
                            </span>
                        </div>

                        <div>
                            <div class="text-gray-900 dark:text-white font-bold">
                                พบ <span class="text-purple-600 dark:text-purple-400">{{ number_format($products->total()) }}</span> สินค้า
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                แสดง {{ $products->count() }} รายการ
                            </div>
                        </div>
                    </div>

                    {{-- Sort Dropdown --}}
                    <div class="flex items-center gap-3">
                        <label class="text-sm font-bold text-gray-700 dark:text-gray-300 whitespace-nowrap">
                            <i class="fas fa-sort text-purple-600 dark:text-purple-400 mr-1"></i>
                            เรียงตาม:
                        </label>

                        <select x-model="filters.sort_by"
                                @change="applyFilters()"
                                class="px-4 py-2.5
                                      border-2 border-gray-200 dark:border-gray-700
                                      bg-white dark:bg-gray-800
                                      text-gray-700 dark:text-gray-300
                                      rounded-xl
                                      focus:border-purple-500 dark:focus:border-purple-400
                                      focus:ring-4 focus:ring-purple-100 dark:focus:ring-purple-900/50
                                      font-semibold transition-all">
                            <option value="newest">ใหม่ล่าสุด</option>
                            <option value="popular">ยอดนิยม</option>
                            <option value="price_low">ราคาต่ำ-สูง</option>
                            <option value="price_high">ราคาสูง-ต่ำ</option>
                            <option value="rating">คะแนนสูงสุด</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Products Grid --}}
            @if($products->count() > 0)
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6 mb-8">
                @foreach($products as $product)
                    <x-shop.product-card :product="$product" />
                @endforeach
            </div>

            {{-- Pagination --}}
            <div class="flex justify-center">
                <div class="glass-fusion-card rounded-2xl shadow-lg p-4">
                    {{ $products->appends(request()->query())->links() }}
                </div>
            </div>
            @else
            {{-- Empty State --}}
            <div class="glass-fusion-card rounded-2xl shadow-lg p-12 text-center">
                <div class="inline-flex items-center justify-center
                           w-24 h-24
                           bg-gradient-to-br from-gray-100 to-gray-200
                           dark:from-gray-800 dark:to-gray-900
                           rounded-full mb-6">
                    <i class="fas fa-search text-4xl text-gray-400 dark:text-gray-600"></i>
                </div>

                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">
                    ไม่พบสินค้าที่คุณค้นหา
                </h3>

                <p class="text-gray-600 dark:text-gray-400 mb-6 max-w-md mx-auto">
                    ลองปรับเปลี่ยนเงื่อนไขการค้นหา หรือลองค้นหาด้วยคำค้นอื่น
                </p>

                <button @click="resetFilters()"
                        class="inline-flex items-center gap-2
                              px-8 py-3
                              bg-gradient-to-r from-purple-600 to-pink-600
                              hover:from-purple-700 hover:to-pink-700
                              text-white font-bold rounded-xl
                              shadow-lg hover:shadow-xl
                              transform hover:scale-105
                              transition-all">
                    <i class="fas fa-th"></i>
                    <span>ดูสินค้าทั้งหมด</span>
                </button>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Alpine.js Component --}}
@push('scripts')
<script>
/**
 * Shop Manager Component - จัดการหน้าร้านค้า
 *
 * ใช้ Alpine.js สำหรับการจัดการ filters และ interactivity
 */
function shopManager() {
    return {
        // ตัวกรองทั้งหมด
        filters: {
            search: '{{ request("search") }}',
            shop_type: '{{ request("shop_type") }}',
            category: '{{ request("category") }}',
            min_price: '{{ request("min_price") }}',
            max_price: '{{ request("max_price") }}',
            brand: '{{ request("brand") }}',
            sort_by: '{{ request("sort_by", "newest") }}'
        },

        /**
         * เริ่มต้น component
         */
        init() {
            console.log('Shop Manager initialized');
        },

        /**
         * ตั้งค่าประเภทร้าน
         *
         * @param {string} type - ประเภทร้าน ('', 'official', 'premium')
         */
        setShopType(type) {
            this.filters.shop_type = type;
            this.applyFilters();
        },

        /**
         * ใช้ตัวกรองทั้งหมด
         */
        applyFilters() {
            // สร้าง URL parameters
            const params = new URLSearchParams();

            // เพิ่มตัวกรองที่มีค่า
            Object.keys(this.filters).forEach(key => {
                const value = this.filters[key];
                if (value && value !== '') {
                    params.append(key, value);
                }
            });

            // Redirect ไปยัง URL ใหม่
            window.location.href = '{{ route("shop.index") }}?' + params.toString();
        },

        /**
         * ล้างตัวกรองทั้งหมด
         */
        resetFilters() {
            window.location.href = '{{ route("shop.index") }}';
        }
    };
}
</script>
@endpush

{{-- Custom Styles --}}
@push('styles')
<style>
/* Custom Scrollbar */
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.05);
    border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: linear-gradient(to bottom, #9333ea, #ec4899);
    border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(to bottom, #7e22ce, #db2777);
}

/* Dark mode scrollbar */
.dark .custom-scrollbar::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.05);
}

/* Fade in animation */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in {
    animation: fadeIn 0.6s ease-out;
}

/* Hover scale animation */
.hover\:scale-102:hover {
    transform: scale(1.02);
}
</style>
@endpush
@endsection
