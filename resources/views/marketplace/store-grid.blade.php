{{--
    Store Front - Grid View Template (V3)

    หน้าร้านค้าแบบ Grid View พร้อมระบบ filter และ sort
    รองรับ Multi-vendor และ MLM features

    @extends layouts.user-arrow-x
--}}

@extends('layouts.user-arrow-x')

@section('title', $store->name ?? 'ร้านค้า')

@section('content')
<div class="container-fluid px-4 py-6" x-data="storeGridComponent()">

    {{-- Store Header --}}
    @if(isset($store))
    <div class="relative overflow-hidden bg-gradient-to-br from-purple-500 via-pink-500 to-red-500
                rounded-2xl p-8 mb-8 shadow-2xl">
        {{-- Pattern Background --}}
        <div class="absolute inset-0 opacity-20">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_50%,rgba(255,255,255,0.2),transparent_50%)]"></div>
        </div>

        <div class="relative z-10 flex flex-col md:flex-row items-start md:items-center gap-6">
            {{-- Store Logo --}}
            @if($store->logo_url)
            <img src="{{ $store->logo_url }}"
                 alt="{{ $store->name }}"
                 class="w-20 h-20 md:w-24 md:h-24 rounded-xl shadow-lg
                        bg-white/20 backdrop-blur-sm border-2 border-white/30
                        object-cover">
            @endif

            <div class="flex-1">
                <h1 class="text-3xl md:text-4xl font-bold text-white mb-2">
                    {{ $store->name }}
                </h1>

                @if($store->description)
                <p class="text-white/90 mb-4">
                    {{ $store->description }}
                </p>
                @endif

                {{-- Store Stats --}}
                <div class="flex flex-wrap gap-4 text-sm text-white/90">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-box"></i>
                        <span>{{ number_format($store->products_count ?? 0) }} สินค้า</span>
                    </div>

                    @if($store->average_rating ?? 0 > 0)
                    <div class="flex items-center gap-2">
                        <i class="fas fa-star text-yellow-300"></i>
                        <span>{{ number_format($store->average_rating, 1) }} ({{ $store->reviews_count ?? 0 }})</span>
                    </div>
                    @endif

                    <div class="flex items-center gap-2">
                        <i class="fas fa-shipping-fast"></i>
                        <span>จัดส่งฟรีเมื่อซื้อครบ ฿{{ number_format($store->free_shipping_threshold ?? 500) }}</span>
                    </div>
                </div>
            </div>

            {{-- Follow Button --}}
            <button @click="toggleFollow()"
                    class="px-6 py-3 bg-white/20 hover:bg-white/30 backdrop-blur-md
                           border border-white/30 text-white font-bold rounded-xl
                           transition-all"
                    :class="{ 'bg-white text-purple-600': isFollowing }">
                <i class="fas" :class="isFollowing ? 'fa-check' : 'fa-plus'"></i>
                <span x-text="isFollowing ? 'กำลังติดตาม' : 'ติดตาม'"></span>
            </button>
        </div>
    </div>
    @endif

    {{-- Filters & Controls --}}
    <div class="glass-fusion-card rounded-2xl p-6 mb-8 backdrop-blur-xl
                bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/30
                shadow-2xl">

        <div class="flex flex-col lg:flex-row gap-4">
            {{-- Search Bar --}}
            <div class="flex-1">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <i class="fas fa-search text-gray-400 dark:text-gray-500"></i>
                    </div>

                    <input type="text"
                           x-model="filters.search"
                           @input.debounce.500ms="applyFilters()"
                           placeholder="ค้นหาสินค้า..."
                           class="w-full pl-12 pr-4 py-3
                                  bg-white dark:bg-gray-700
                                  border-2 border-gray-200 dark:border-gray-600
                                  rounded-xl
                                  focus:border-purple-500 focus:ring-4 focus:ring-purple-500/20
                                  text-gray-900 dark:text-gray-100
                                  placeholder:text-gray-400 dark:placeholder:text-gray-500
                                  transition-all">
                </div>
            </div>

            {{-- Category Filter --}}
            <div class="w-full lg:w-48">
                <select x-model="filters.category"
                        @change="applyFilters()"
                        class="w-full px-4 py-3
                               bg-white dark:bg-gray-700
                               border-2 border-gray-200 dark:border-gray-600
                               rounded-xl
                               focus:border-purple-500 focus:ring-4 focus:ring-purple-500/20
                               text-gray-900 dark:text-gray-100
                               transition-all">
                    <option value="">ทุกหมวดหมู่</option>
                    @foreach($categories ?? [] as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Sort --}}
            <div class="w-full lg:w-48">
                <select x-model="filters.sort"
                        @change="applyFilters()"
                        class="w-full px-4 py-3
                               bg-white dark:bg-gray-700
                               border-2 border-gray-200 dark:border-gray-600
                               rounded-xl
                               focus:border-purple-500 focus:ring-4 focus:ring-purple-500/20
                               text-gray-900 dark:text-gray-100
                               transition-all">
                    <option value="latest">ใหม่ล่าสุด</option>
                    <option value="price_asc">ราคา: ต่ำ-สูง</option>
                    <option value="price_desc">ราคา: สูง-ต่ำ</option>
                    <option value="popular">ยอดนิยม</option>
                    <option value="rating">คะแนนสูงสุด</option>
                    @if(auth()->check())
                    <option value="pv_desc">PV: สูง-ต่ำ</option>
                    <option value="commission_desc">Commission: สูง-ต่ำ</option>
                    @endif
                </select>
            </div>

            {{-- View Toggle --}}
            <div class="flex gap-2">
                <a href="{{ request()->fullUrlWithQuery(['view' => 'grid']) }}"
                   class="px-4 py-3 rounded-xl transition-all
                          {{ (!request('view') || request('view') === 'grid') ? 'bg-purple-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300' }}">
                    <i class="fas fa-th"></i>
                </a>
                <a href="{{ request()->fullUrlWithQuery(['view' => 'list']) }}"
                   class="px-4 py-3 rounded-xl transition-all
                          {{ request('view') === 'list' ? 'bg-purple-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300' }}">
                    <i class="fas fa-list"></i>
                </a>
            </div>
        </div>

        {{-- Active Filters --}}
        <div x-show="hasActiveFilters()" x-transition class="flex flex-wrap gap-2 mt-4">
            <button @click="clearFilters()"
                    class="px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300
                           text-sm rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                <i class="fas fa-times mr-1"></i>
                ล้างทั้งหมด
            </button>

            <template x-if="filters.search">
                <span class="px-3 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300
                             text-sm rounded-lg">
                    ค้นหา: <span x-text="filters.search"></span>
                </span>
            </template>
        </div>
    </div>

    {{-- Products Grid --}}
    <div x-show="!loading" x-transition>
        @if(isset($products) && $products->count() > 0)
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-8">
            @foreach($products as $product)
            {{-- สามารถเลือกใช้ card style ได้ตามต้องการ --}}
            <x-ecommerce.product-card-premium
                :product="$product"
                :showPv="auth()->check()"
                :showCommission="auth()->check() && optional(auth()->user()->mlmMember)->exists()"
            />
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="flex justify-center">
            {{ $products->links() }}
        </div>
        @else
        {{-- Empty State --}}
        <div class="glass-fusion-card rounded-2xl p-12 text-center
                    backdrop-blur-xl bg-white/80 dark:bg-gray-800/80
                    border border-white/20 dark:border-gray-700/30">
            <div class="text-6xl text-gray-300 dark:text-gray-600 mb-4">
                <i class="fas fa-box-open"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                ไม่พบสินค้า
            </h3>
            <p class="text-gray-600 dark:text-gray-400 mb-6">
                ลองเปลี่ยนตัวกรองหรือค้นหาด้วยคำอื่น
            </p>
            <button @click="clearFilters()"
                    class="px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600
                           text-white font-bold rounded-xl
                           hover:shadow-lg transform hover:scale-105 transition-all">
                <i class="fas fa-redo mr-2"></i>
                รีเซ็ตตัวกรอง
            </button>
        </div>
        @endif
    </div>

    {{-- Loading State --}}
    <div x-show="loading" x-transition class="flex justify-center items-center py-12">
        <div class="text-center">
            <i class="fas fa-spinner fa-spin text-4xl text-purple-600 mb-4"></i>
            <p class="text-gray-600 dark:text-gray-400">กำลังโหลดสินค้า...</p>
        </div>
    </div>
</div>

@push('scripts')
<script>
/**
 * Store Grid Component - จัดการ filtering และ UI สำหรับหน้าร้านค้า
 */
function storeGridComponent() {
    return {
        loading: false,
        isFollowing: false,

        filters: {
            search: '{{ request('search', '') }}',
            category: '{{ request('category', '') }}',
            sort: '{{ request('sort', 'latest') }}',
            minPrice: null,
            maxPrice: null,
        },

        /**
         * เริ่มต้น component
         */
        init() {
            // ตรวจสอบสถานะ following
            this.checkFollowingStatus();
        },

        /**
         * ตรวจสอบว่า following หรือไม่
         */
        checkFollowingStatus() {
            @if(isset($store) && auth()->check())
            // ตรวจสอบจาก API หรือ localStorage
            this.isFollowing = false; // TODO: implement
            @endif
        },

        /**
         * Toggle follow store
         */
        async toggleFollow() {
            @if(!auth()->check())
            window.location.href = '{{ route('login') }}';
            return;
            @endif

            this.isFollowing = !this.isFollowing;

            try {
                await fetch('/api/stores/{{ $store->id ?? 0 }}/follow', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                this.$dispatch('notify', {
                    message: this.isFollowing ? 'ติดตามร้านค้าแล้ว' : 'เลิกติดตามร้านค้าแล้ว',
                    type: 'success'
                });
            } catch (error) {
                console.error('Follow error:', error);
                this.isFollowing = !this.isFollowing; // Revert
            }
        },

        /**
         * ตรวจสอบว่ามี active filters หรือไม่
         */
        hasActiveFilters() {
            return this.filters.search ||
                   this.filters.category ||
                   this.filters.sort !== 'latest';
        },

        /**
         * ล้างตัวกรองทั้งหมด
         */
        clearFilters() {
            this.filters = {
                search: '',
                category: '',
                sort: 'latest',
                minPrice: null,
                maxPrice: null,
            };

            this.applyFilters();
        },

        /**
         * Apply filters (reload page with query params)
         */
        applyFilters() {
            const params = new URLSearchParams();

            if (this.filters.search) params.set('search', this.filters.search);
            if (this.filters.category) params.set('category', this.filters.category);
            if (this.filters.sort && this.filters.sort !== 'latest') params.set('sort', this.filters.sort);

            const queryString = params.toString();
            const url = queryString ? `?${queryString}` : window.location.pathname;

            window.location.href = url;
        }
    };
}
</script>
@endpush
@endsection
