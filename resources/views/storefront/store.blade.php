{{--
    Individual Store Page - สไตล์ AliExpress Premium

    หน้าร้านค้าแต่ละร้านที่รองรับการ customization
    รองรับ Dark Mode, Responsive, และ Custom Theme Colors

    Features:
    - Customizable Banner
    - Store Logo & Info
    - Custom Theme Colors (primary_color, secondary_color)
    - Custom CSS Support
    - Store Categories
    - Product Grid
--}}

@extends('layouts.user-arrow-x')

@section('title', ($store->store_name ?? 'ร้านค้า') . ' - สินค้าคุณภาพดี')

@section('meta')
<meta name="description" content="{{ $store->store_description ?? 'สินค้าคุณภาพจากร้านค้าที่ได้รับการยืนยัน' }}">
@endsection

{{-- Custom Theme Colors --}}
@push('styles')
<style>
    :root {
        --store-primary: {{ $store->primary_color ?? '#f97316' }};
        --store-secondary: {{ $store->secondary_color ?? '#ea580c' }};
    }

    .store-primary-bg {
        background-color: var(--store-primary);
    }

    .store-primary-text {
        color: var(--store-primary);
    }

    .store-gradient {
        background: linear-gradient(135deg, var(--store-primary), var(--store-secondary));
    }

    .store-border {
        border-color: var(--store-primary);
    }

    /* Custom CSS จากร้าน */
    {{ $store->custom_css ?? '' }}
</style>
@endpush

@section('content')
<div x-data="storePageManager()"
     x-init="init()"
     class="min-h-screen bg-gray-50 dark:bg-gray-900">

    {{-- ========================================
         STORE HEADER / BANNER SECTION
         ======================================== --}}
    <div class="relative">
        {{-- Banner Image/Gradient --}}
        <div class="relative h-64 md:h-80 lg:h-96 overflow-hidden">
            @if($store->store_banner)
            <img src="{{ $store->store_banner }}"
                 alt="{{ $store->store_name }}"
                 class="w-full h-full object-cover"
                 style="object-position: center {{ $store->banner_position_y ?? 50 }}%;">
            {{-- Gradient Overlay --}}
            <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/30 to-transparent"></div>
            @else
            {{-- Default Gradient Background --}}
            <div class="w-full h-full store-gradient">
                {{-- Pattern Overlay --}}
                <div class="absolute inset-0 opacity-20">
                    <div class="absolute inset-0" style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 40px 40px;"></div>
                </div>
            </div>
            @endif

            {{-- Floating Decorations --}}
            <div class="absolute top-10 right-10 w-32 h-32 bg-white/10 rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-20 left-20 w-48 h-48 bg-white/10 rounded-full blur-3xl animate-pulse delay-500"></div>
        </div>

        {{-- Store Info Card (Overlapping Banner) --}}
        <div class="container mx-auto px-4">
            <div class="relative -mt-24 md:-mt-32 mb-8">
                <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl overflow-hidden
                           border border-gray-100 dark:border-gray-700">
                    <div class="p-6 md:p-8">
                        <div class="flex flex-col md:flex-row gap-6 md:items-end">
                            {{-- Store Logo --}}
                            <div class="relative">
                                <div class="w-28 h-28 md:w-36 md:h-36 rounded-2xl overflow-hidden
                                           ring-4 ring-white dark:ring-gray-800 shadow-xl
                                           {{ $store->store_logo ? '' : 'store-gradient' }}">
                                    @if($store->store_logo)
                                    <img src="{{ $store->store_logo }}"
                                         alt="{{ $store->store_name }}"
                                         class="w-full h-full object-cover">
                                    @else
                                    <div class="w-full h-full flex items-center justify-center text-white">
                                        <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                        </svg>
                                    </div>
                                    @endif
                                </div>

                                {{-- Verified Badge --}}
                                @if($store->is_verified)
                                <div class="absolute -bottom-2 -right-2 w-10 h-10 rounded-full
                                           bg-blue-500 text-white
                                           flex items-center justify-center shadow-lg">
                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                @endif
                            </div>

                            {{-- Store Details --}}
                            <div class="flex-1">
                                <div class="flex flex-wrap items-start gap-3 mb-3">
                                    <h1 class="text-3xl md:text-4xl font-black text-gray-900 dark:text-white">
                                        {{ $store->store_name }}
                                    </h1>

                                    @if($store->is_verified)
                                    <span class="inline-flex items-center gap-1 px-3 py-1
                                               bg-blue-100 dark:bg-blue-900/30
                                               text-blue-700 dark:text-blue-400
                                               text-sm font-bold rounded-full">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                        Verified Seller
                                    </span>
                                    @endif
                                </div>

                                @if($store->store_description)
                                <p class="text-gray-600 dark:text-gray-400 mb-4 max-w-2xl">
                                    {{ $store->store_description }}
                                </p>
                                @endif

                                {{-- Store Stats --}}
                                <div class="flex flex-wrap items-center gap-4 md:gap-6 text-sm">
                                    {{-- Rating --}}
                                    @if($store->rating_average > 0)
                                    <div class="flex items-center gap-2">
                                        <div class="flex items-center gap-1 px-3 py-1.5
                                                   bg-yellow-100 dark:bg-yellow-900/30 rounded-lg">
                                            <svg class="w-5 h-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                            </svg>
                                            <span class="font-bold text-yellow-700 dark:text-yellow-400">
                                                {{ number_format($store->rating_average, 1) }}
                                            </span>
                                        </div>
                                        <span class="text-gray-500 dark:text-gray-400">
                                            ({{ number_format($store->rating_count) }} รีวิว)
                                        </span>
                                    </div>
                                    @endif

                                    {{-- Products Count --}}
                                    <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                        </svg>
                                        <span><strong>{{ $products->total() }}</strong> สินค้า</span>
                                    </div>

                                    {{-- Join Date --}}
                                    <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        <span>เปิดร้านเมื่อ {{ $store->created_at->diffForHumans() }}</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Action Buttons --}}
                            <div class="flex flex-col gap-2">
                                <button @click="followStore()"
                                        class="px-6 py-3 store-gradient text-white font-bold rounded-xl
                                              shadow-lg hover:shadow-xl
                                              transform hover:scale-105
                                              transition-all flex items-center justify-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                    </svg>
                                    <span x-text="isFollowing ? 'กำลังติดตาม' : 'ติดตามร้าน'">ติดตามร้าน</span>
                                </button>

                                {{-- Social Links --}}
                                @if($store->facebook_url || $store->line_oa_id || $store->instagram_url)
                                <div class="flex items-center justify-center gap-2">
                                    @if($store->facebook_url)
                                    <a href="{{ $store->facebook_url }}" target="_blank"
                                       class="w-10 h-10 bg-blue-600 text-white rounded-full
                                             flex items-center justify-center
                                             hover:bg-blue-700 transition-colors">
                                        <i class="fab fa-facebook-f"></i>
                                    </a>
                                    @endif

                                    @if($store->line_oa_id)
                                    <a href="https://line.me/R/ti/p/{{ $store->line_oa_id }}" target="_blank"
                                       class="w-10 h-10 bg-green-500 text-white rounded-full
                                             flex items-center justify-center
                                             hover:bg-green-600 transition-colors">
                                        <i class="fab fa-line"></i>
                                    </a>
                                    @endif

                                    @if($store->instagram_url)
                                    <a href="{{ $store->instagram_url }}" target="_blank"
                                       class="w-10 h-10 bg-gradient-to-br from-purple-600 via-pink-500 to-orange-400
                                             text-white rounded-full
                                             flex items-center justify-center
                                             hover:opacity-90 transition-opacity">
                                        <i class="fab fa-instagram"></i>
                                    </a>
                                    @endif
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Store Highlights Bar --}}
                    <div class="border-t border-gray-100 dark:border-gray-700
                               bg-gray-50 dark:bg-gray-800/50 px-6 py-4">
                        <div class="flex flex-wrap items-center justify-center md:justify-start gap-6 text-sm">
                            @if($store->free_shipping_threshold > 0)
                            <div class="flex items-center gap-2 text-green-600 dark:text-green-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                                </svg>
                                <span>ส่งฟรีเมื่อซื้อครบ ฿{{ number_format($store->free_shipping_threshold) }}</span>
                            </div>
                            @endif

                            @if($store->enable_cod)
                            <div class="flex items-center gap-2 text-blue-600 dark:text-blue-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                <span>รับเก็บเงินปลายทาง</span>
                            </div>
                            @endif

                            <div class="flex items-center gap-2 text-purple-600 dark:text-purple-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                                <span>รับประกันคุณภาพสินค้า</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ========================================
         STORE CATEGORIES (If any)
         ======================================== --}}
    @if($storeCategories && $storeCategories->count() > 0)
    <div class="container mx-auto px-4 mb-8">
        <div class="flex gap-3 overflow-x-auto pb-4 scrollbar-hide">
            <button @click="categoryFilter = ''"
                    :class="categoryFilter === '' ? 'store-gradient text-white shadow-lg' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700'"
                    class="flex-shrink-0 px-6 py-3 rounded-xl font-semibold transition-all">
                ทั้งหมด ({{ $products->total() }})
            </button>

            @foreach($storeCategories as $category)
            <button @click="filterByCategory('{{ $category->slug }}')"
                    :class="categoryFilter === '{{ $category->slug }}' ? 'store-gradient text-white shadow-lg' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700'"
                    class="flex-shrink-0 px-6 py-3 rounded-xl font-semibold transition-all">
                {{ $category->name }} ({{ $category->products_count }})
            </button>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ========================================
         PRODUCTS SECTION
         ======================================== --}}
    <div class="container mx-auto px-4 pb-12" id="products">
        {{-- Section Header --}}
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                สินค้าทั้งหมด
            </h2>

            {{-- Sort --}}
            <select x-model="sortBy"
                    @change="applySorting()"
                    class="px-4 py-2 bg-white dark:bg-gray-800
                          border border-gray-200 dark:border-gray-700
                          rounded-xl text-sm font-medium
                          focus:ring-2 focus:ring-orange-500">
                <option value="newest">ใหม่ล่าสุด</option>
                <option value="popular">ยอดนิยม</option>
                <option value="price_low">ราคาต่ำ-สูง</option>
                <option value="price_high">ราคาสูง-ต่ำ</option>
                <option value="rating">คะแนนสูงสุด</option>
            </select>
        </div>

        {{-- Products Grid --}}
        <x-storefront.product-grid-aliexpress
            :products="$products"
            columns="auto"
            :showPv="true"
            :showCommission="auth()->check()" />

        {{-- Pagination --}}
        @if($products->hasPages())
        <div class="mt-8">
            {{ $products->links() }}
        </div>
        @endif
    </div>

    {{-- ========================================
         STORE INFO FOOTER
         ======================================== --}}
    <div class="bg-white dark:bg-gray-800 border-t border-gray-100 dark:border-gray-700">
        <div class="container mx-auto px-4 py-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                {{-- Contact Info --}}
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                        ติดต่อร้าน
                    </h3>
                    <div class="space-y-3 text-sm text-gray-600 dark:text-gray-400">
                        @if($store->store_email)
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <span>{{ $store->store_email }}</span>
                        </div>
                        @endif

                        @if($store->store_phone)
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                            <span>{{ $store->store_phone }}</span>
                        </div>
                        @endif

                        @if($store->store_address)
                        <div class="flex items-start gap-2">
                            <svg class="w-5 h-5 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span>{{ $store->store_address }}</span>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Shipping Info --}}
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                        ข้อมูลการจัดส่ง
                    </h3>
                    <div class="space-y-3 text-sm text-gray-600 dark:text-gray-400">
                        @if($store->shipping_fee > 0)
                        <div>ค่าจัดส่ง: ฿{{ number_format($store->shipping_fee) }}</div>
                        @endif
                        @if($store->free_shipping_threshold > 0)
                        <div>ส่งฟรีเมื่อซื้อครบ: ฿{{ number_format($store->free_shipping_threshold) }}</div>
                        @endif
                        @if($store->minimum_order_amount > 0)
                        <div>ยอดสั่งซื้อขั้นต่ำ: ฿{{ number_format($store->minimum_order_amount) }}</div>
                        @endif
                    </div>
                </div>

                {{-- Store Policies --}}
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                        นโยบายร้าน
                    </h3>
                    <div class="space-y-3 text-sm text-gray-600 dark:text-gray-400">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>รับประกันสินค้าทุกชิ้น</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>เปลี่ยน/คืนสินค้าได้ภายใน 7 วัน</span>
                        </div>
                        @if($store->enable_reviews)
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>เปิดรับรีวิวจากลูกค้า</span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
/**
 * Store Page Manager
 */
function storePageManager() {
    return {
        isFollowing: false,
        categoryFilter: '',
        sortBy: '{{ request("sort_by", "newest") }}',

        init() {
            console.log('Store Page Manager initialized');
        },

        followStore() {
            @guest
            if (confirm('กรุณาเข้าสู่ระบบเพื่อติดตามร้าน')) {
                window.location.href = '{{ route("login") }}';
            }
            return;
            @endguest

            this.isFollowing = !this.isFollowing;
            // TODO: API call to follow store
        },

        filterByCategory(slug) {
            this.categoryFilter = slug;
            const url = new URL(window.location.href);
            url.searchParams.set('category', slug);
            window.location.href = url.toString();
        },

        applySorting() {
            const url = new URL(window.location.href);
            url.searchParams.set('sort_by', this.sortBy);
            window.location.href = url.toString();
        }
    };
}
</script>
@endpush
@endsection
