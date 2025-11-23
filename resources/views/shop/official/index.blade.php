{{-- resources/views/shop/official/index.blade.php --}}

@extends('layouts.user-arrow-x')

@section('title', 'ร้านของระบบ - TP Affiliate')

@section('content')
<div x-data="officialShopManager()" x-init="init()" class="min-h-screen">

    {{-- ========================================
         Hero Section - Premium 3D Design
         ======================================== --}}
    <div class="relative overflow-hidden bg-gradient-to-br from-purple-600 via-pink-500 to-orange-500 py-20">

        {{-- Animated Background Pattern --}}
        <div class="absolute inset-0 opacity-20">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_50%,rgba(255,255,255,0.3),transparent_50%)]"></div>
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_80%_80%,rgba(255,255,255,0.2),transparent_50%)]"></div>
        </div>

        {{-- Floating Elements (3D Effect) --}}
        <div class="absolute top-10 left-10 w-20 h-20 bg-white/10 rounded-full blur-xl animate-pulse"></div>
        <div class="absolute bottom-10 right-20 w-32 h-32 bg-white/10 rounded-full blur-2xl animate-pulse delay-100"></div>

        <div class="container-fluid px-4 relative z-10">
            <div class="max-w-4xl mx-auto text-center">
                {{-- Store Badge --}}
                <div class="inline-flex items-center gap-2 px-6 py-3 mb-6 bg-white/20 backdrop-blur-lg border border-white/30 rounded-full">
                    <i class="fas fa-shield-check text-white text-xl"></i>
                    <span class="text-white font-semibold">ร้านทางการ TP Affiliate</span>
                </div>

                {{-- Main Heading with 3D Text Effect --}}
                <h1 class="text-5xl md:text-7xl font-black text-white mb-6 drop-shadow-[0_8px_16px_rgba(0,0,0,0.3)] transform hover:scale-105 transition-transform">
                    สินค้าคุณภาพพรีเมียม
                </h1>

                <p class="text-xl md:text-2xl text-white/90 mb-8 max-w-2xl mx-auto">
                    สินค้าจากทางระบบโดยตรง รับประกันคุณภาพ พร้อมคอมมิชชั่น MLM สูงสุด
                </p>

                {{-- Stats Cards (Glassmorphism) --}}
                <div class="grid grid-cols-3 gap-4 max-w-2xl mx-auto">
                    <div class="bg-white/10 backdrop-blur-lg border border-white/20 rounded-2xl p-4">
                        <div class="text-3xl font-bold text-white" x-text="stats.total_products">{{ $stats['official'] ?? 0 }}</div>
                        <div class="text-sm text-white/80">สินค้าทั้งหมด</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-lg border border-white/20 rounded-2xl p-4">
                        <div class="text-3xl font-bold text-white">100%</div>
                        <div class="text-sm text-white/80">รับประกันคุณภาพ</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-lg border border-white/20 rounded-2xl p-4">
                        <div class="text-3xl font-bold text-white">ถึง 40%</div>
                        <div class="text-sm text-white/80">คอมมิชชั่น MLM</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ========================================
         Filter Section with 3D Cards
         ======================================== --}}
    <div class="container-fluid px-4 -mt-10 relative z-20 mb-8">
        <div class="max-w-7xl mx-auto">

            {{-- Filter Card (Neumorphic Style) --}}
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl p-6 border-2 border-purple-200 dark:border-purple-700">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

                    {{-- Search --}}
                    <div class="md:col-span-2">
                        <div class="relative">
                            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            <input type="text"
                                   x-model="filters.search"
                                   @input.debounce.500ms="applyFilters()"
                                   placeholder="ค้นหาสินค้า..."
                                   class="w-full pl-12 pr-4 py-3 bg-gray-50 dark:bg-gray-700 border-2 border-transparent focus:border-purple-500 rounded-xl transition-all">
                        </div>
                    </div>

                    {{-- Category --}}
                    <div>
                        <select x-model="filters.category"
                                @change="applyFilters()"
                                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border-2 border-transparent focus:border-purple-500 rounded-xl transition-all">
                            <option value="">ทุกหมวดหมู่</option>
                            @foreach($categories as $category)
                            <option value="{{ $category->slug }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Sort --}}
                    <div>
                        <select x-model="filters.sort_by"
                                @change="applyFilters()"
                                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border-2 border-transparent focus:border-purple-500 rounded-xl transition-all">
                            <option value="newest">ใหม่ล่าสุด</option>
                            <option value="popular">ยอดนิยม</option>
                            <option value="price_low">ราคาต่ำ-สูง</option>
                            <option value="price_high">ราคาสูง-ต่ำ</option>
                            <option value="rating">คะแนนสูงสุด</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ========================================
         Products Grid - 3D Cards
         ======================================== --}}
    <div class="container-fluid px-4 pb-12">
        <div class="max-w-7xl mx-auto">

            {{-- Loading State --}}
            <div x-show="loading" class="text-center py-20">
                <div class="inline-block w-16 h-16 border-4 border-purple-500 border-t-transparent rounded-full animate-spin"></div>
                <p class="text-gray-600 dark:text-gray-400 mt-4">กำลังโหลดสินค้า...</p>
            </div>

            {{-- Products Grid --}}
            <div x-show="!loading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach($products as $product)
                <div class="group perspective-1000">
                    <div class="relative transform-gpu transition-all duration-500 group-hover:scale-105 group-hover:rotate-y-2">

                        {{-- Glow Effect (Purple/Pink Theme) --}}
                        <div class="absolute inset-0 bg-gradient-to-br from-purple-500 via-pink-500 to-orange-500 rounded-3xl blur-xl opacity-0 group-hover:opacity-50 transition-opacity"></div>

                        {{-- Product Card --}}
                        <div class="relative bg-white dark:bg-gray-800 rounded-3xl shadow-xl overflow-hidden border-2 border-purple-100 dark:border-purple-800">

                            {{-- Image Container --}}
                            <div class="relative aspect-square overflow-hidden bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20">
                                {{-- Official Badge --}}
                                <div class="absolute top-3 left-3 z-10">
                                    <div class="px-3 py-1 bg-gradient-to-r from-purple-600 to-pink-600 text-white text-xs font-bold rounded-full shadow-lg flex items-center gap-1">
                                        <i class="fas fa-shield-check"></i>
                                        <span>Official</span>
                                    </div>
                                </div>

                                {{-- Featured Badge --}}
                                @if($product->is_featured)
                                <div class="absolute top-3 right-3 z-10">
                                    <div class="px-3 py-1 bg-gradient-to-r from-orange-500 to-red-500 text-white text-xs font-bold rounded-full shadow-lg flex items-center gap-1">
                                        <i class="fas fa-star"></i>
                                        <span>แนะนำ</span>
                                    </div>
                                </div>
                                @endif

                                {{-- Product Image --}}
                                <img src="{{ $product->main_image_url ?? 'https://via.placeholder.com/400' }}"
                                     alt="{{ $product->name }}"
                                     class="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-500"
                                     loading="lazy">

                                {{-- Hover Overlay --}}
                                <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-4">
                                    <a href="{{ route('shop.show', $product->slug) }}"
                                       class="w-full px-4 py-2 bg-white/90 backdrop-blur-sm text-purple-600 font-semibold rounded-xl text-center hover:bg-white transition-colors">
                                        <i class="fas fa-eye mr-2"></i>
                                        ดูรายละเอียด
                                    </a>
                                </div>
                            </div>

                            {{-- Product Info --}}
                            <div class="p-4">
                                {{-- Product Name --}}
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2 line-clamp-2 group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors">
                                    {{ $product->name }}
                                </h3>

                                {{-- Rating --}}
                                <div class="flex items-center gap-2 mb-3">
                                    <div class="flex items-center">
                                        @for($i = 1; $i <= 5; $i++)
                                            @if($i <= floor($product->rating_average))
                                                <i class="fas fa-star text-yellow-400 text-sm"></i>
                                            @else
                                                <i class="far fa-star text-gray-300 text-sm"></i>
                                            @endif
                                        @endfor
                                    </div>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">
                                        ({{ number_format($product->rating_count) }})
                                    </span>
                                </div>

                                {{-- Price --}}
                                <div class="flex items-end gap-2 mb-3">
                                    <div class="text-2xl font-black bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">
                                        ฿{{ number_format($product->price, 2) }}
                                    </div>
                                    @if($product->compare_at_price && $product->compare_at_price > $product->price)
                                    <div class="text-sm text-gray-400 line-through">
                                        ฿{{ number_format($product->compare_at_price, 2) }}
                                    </div>
                                    @endif
                                </div>

                                {{-- Commission Badge --}}
                                <div class="flex items-center gap-2 mb-4">
                                    <div class="flex-1 px-3 py-2 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-lg border border-green-200 dark:border-green-700">
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs text-gray-600 dark:text-gray-400">คอมมิชชั่น</span>
                                            <span class="text-sm font-bold text-green-600 dark:text-green-400">
                                                {{ number_format($product->commission_rate, 0) }}%
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                {{-- Actions --}}
                                <div class="grid grid-cols-2 gap-2">
                                    <button @click="addToCart({{ $product->id }})"
                                            class="px-4 py-2 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white rounded-xl font-semibold transition-all transform hover:scale-105 shadow-lg">
                                        <i class="fas fa-cart-plus mr-1"></i>
                                        <span class="hidden sm:inline">ใส่ตะกร้า</span>
                                    </button>
                                    <a href="{{ route('shop.show', $product->slug) }}"
                                       class="px-4 py-2 bg-white dark:bg-gray-700 border-2 border-purple-600 dark:border-purple-400 text-purple-600 dark:text-purple-400 hover:bg-purple-50 dark:hover:bg-purple-900/20 rounded-xl font-semibold text-center transition-all">
                                        <i class="fas fa-info-circle"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Empty State --}}
            @if($products->isEmpty())
            <div class="text-center py-20">
                <div class="w-32 h-32 mx-auto mb-6 bg-gradient-to-br from-purple-100 to-pink-100 dark:from-purple-900/20 dark:to-pink-900/20 rounded-full flex items-center justify-center">
                    <i class="fas fa-box-open text-6xl text-purple-400"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                    ไม่พบสินค้า
                </h3>
                <p class="text-gray-600 dark:text-gray-400">
                    ลองเปลี่ยนคำค้นหาหรือตัวกรองอื่นดูสิ
                </p>
            </div>
            @endif

            {{-- Pagination --}}
            @if($products->hasPages())
            <div class="mt-12">
                {{ $products->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
/**
 * Official Shop Manager - จัดการหน้าร้านของระบบ
 *
 * @returns {object} Alpine component
 */
function officialShopManager() {
    return {
        // State
        loading: false,
        filters: {
            search: '',
            category: '',
            sort_by: 'newest',
        },
        stats: {
            total_products: {{ $stats['official'] ?? 0 }},
        },

        /**
         * เริ่มต้น component
         */
        init() {
            console.log('Official Shop Manager initialized');

            // Load from URL params
            const urlParams = new URLSearchParams(window.location.search);
            this.filters.search = urlParams.get('search') || '';
            this.filters.category = urlParams.get('category') || '';
            this.filters.sort_by = urlParams.get('sort_by') || 'newest';
        },

        /**
         * ใช้ตัวกรอง
         */
        applyFilters() {
            this.loading = true;

            // Build query string
            const params = new URLSearchParams();
            params.set('shop_type', 'official'); // บังคับให้เป็นร้านของระบบ

            if (this.filters.search) params.set('search', this.filters.search);
            if (this.filters.category) params.set('category', this.filters.category);
            if (this.filters.sort_by) params.set('sort_by', this.filters.sort_by);

            // Redirect with filters
            window.location.href = `${window.location.pathname}?${params.toString()}`;
        },

        /**
         * เพิ่มสินค้าลงตะกร้า
         *
         * @param {number} productId - ID สินค้า
         */
        async addToCart(productId) {
            try {
                const response = await fetch('/cart/add', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        product_id: productId,
                        quantity: 1
                    })
                });

                const data = await response.json();

                if (data.success) {
                    // แสดง notification
                    this.$dispatch('notify', {
                        message: 'เพิ่มสินค้าลงตะกร้าสำเร็จ',
                        type: 'success'
                    });

                    // อัพเดทจำนวนสินค้าในตะกร้า
                    this.$dispatch('cart-updated', { count: data.cart_count });
                } else {
                    throw new Error(data.message || 'เกิดข้อผิดพลาด');
                }
            } catch (error) {
                console.error('Error adding to cart:', error);
                this.$dispatch('notify', {
                    message: error.message || 'ไม่สามารถเพิ่มสินค้าลงตะกร้าได้',
                    type: 'error'
                });
            }
        }
    };
}

// Export for global use
window.officialShopManager = officialShopManager;
</script>
@endpush

@push('styles')
<style>
/* 3D Perspective */
.perspective-1000 {
    perspective: 1000px;
}

.rotate-y-2 {
    transform: rotateY(2deg);
}

/* Animation Delays */
.delay-100 {
    animation-delay: 100ms;
}

/* Line Clamp */
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
@endpush
@endsection
