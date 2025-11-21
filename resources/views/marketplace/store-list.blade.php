{{--
    Store Front - List View Template (V3)

    หน้าร้านค้าแบบ List View พร้อม sidebar filters
    แสดงรายละเอียดมากกว่า Grid View

    @extends layouts.user-arrow-x
--}}

@extends('layouts.user-arrow-x')

@section('title', $store->name ?? 'ร้านค้า')

@section('content')
<div class="container-fluid px-4 py-6" x-data="storeListComponent()">

    {{-- Store Header (เหมือน Grid View) --}}
    @if(isset($store))
    <div class="relative overflow-hidden bg-gradient-to-br from-purple-500 via-pink-500 to-red-500
                rounded-2xl p-8 mb-8 shadow-2xl">
        <div class="absolute inset-0 opacity-20">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_50%,rgba(255,255,255,0.2),transparent_50%)]"></div>
        </div>

        <div class="relative z-10 flex flex-col md:flex-row items-start md:items-center gap-6">
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
                </div>
            </div>

            <button @click="toggleFollow()"
                    class="px-6 py-3 bg-white/20 hover:bg-white/30 backdrop-blur-md
                           border border-white/30 text-white font-bold rounded-xl transition-all"
                    :class="{ 'bg-white text-purple-600': isFollowing }">
                <i class="fas" :class="isFollowing ? 'fa-check' : 'fa-plus'"></i>
                <span x-text="isFollowing ? 'กำลังติดตาม' : 'ติดตาม'"></span>
            </button>
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        {{-- Sidebar Filters --}}
        <aside class="lg:col-span-1">
            <div class="glass-fusion-card rounded-2xl p-6 backdrop-blur-xl
                        bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/30
                        shadow-2xl sticky top-6">

                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-filter text-purple-600"></i>
                    <span>ตัวกรอง</span>
                </h3>

                {{-- Search --}}
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ค้นหา
                    </label>
                    <input type="text"
                           x-model="filters.search"
                           @input.debounce.500ms="applyFilters()"
                           placeholder="ชื่อสินค้า..."
                           class="w-full px-4 py-2
                                  bg-white dark:bg-gray-700
                                  border border-gray-200 dark:border-gray-600
                                  rounded-lg
                                  focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20
                                  text-gray-900 dark:text-gray-100
                                  placeholder:text-gray-400
                                  transition-all">
                </div>

                {{-- Categories --}}
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                        หมวดหมู่
                    </label>
                    <div class="space-y-2">
                        <label class="flex items-center cursor-pointer group">
                            <input type="radio"
                                   x-model="filters.category"
                                   value=""
                                   @change="applyFilters()"
                                   class="w-4 h-4 text-purple-600 focus:ring-purple-500">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300 group-hover:text-purple-600 dark:group-hover:text-purple-400">
                                ทั้งหมด
                            </span>
                        </label>

                        @foreach($categories ?? [] as $category)
                        <label class="flex items-center cursor-pointer group">
                            <input type="radio"
                                   x-model="filters.category"
                                   value="{{ $category->id }}"
                                   @change="applyFilters()"
                                   class="w-4 h-4 text-purple-600 focus:ring-purple-500">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300 group-hover:text-purple-600 dark:group-hover:text-purple-400">
                                {{ $category->name }} ({{ $category->products_count ?? 0 }})
                            </span>
                        </label>
                        @endforeach
                    </div>
                </div>

                {{-- Price Range --}}
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                        ช่วงราคา (฿)
                    </label>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="number"
                               x-model="filters.minPrice"
                               @change="applyFilters()"
                               placeholder="ต่ำสุด"
                               class="w-full px-3 py-2
                                      bg-white dark:bg-gray-700
                                      border border-gray-200 dark:border-gray-600
                                      rounded-lg text-sm
                                      focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20
                                      text-gray-900 dark:text-gray-100">

                        <input type="number"
                               x-model="filters.maxPrice"
                               @change="applyFilters()"
                               placeholder="สูงสุด"
                               class="w-full px-3 py-2
                                      bg-white dark:bg-gray-700
                                      border border-gray-200 dark:border-gray-600
                                      rounded-lg text-sm
                                      focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20
                                      text-gray-900 dark:text-gray-100">
                    </div>
                </div>

                {{-- Rating Filter --}}
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                        คะแนนขั้นต่ำ
                    </label>
                    <div class="space-y-2">
                        @for($i = 5; $i >= 3; $i--)
                        <label class="flex items-center cursor-pointer group">
                            <input type="radio"
                                   x-model="filters.minRating"
                                   value="{{ $i }}"
                                   @change="applyFilters()"
                                   class="w-4 h-4 text-purple-600 focus:ring-purple-500">
                            <span class="ml-2 flex items-center text-sm">
                                @for($s = 1; $s <= 5; $s++)
                                <i class="fas fa-star text-xs {{ $s <= $i ? 'text-yellow-400' : 'text-gray-300 dark:text-gray-600' }}"></i>
                                @endfor
                                <span class="ml-1 text-gray-700 dark:text-gray-300">ขึ้นไป</span>
                            </span>
                        </label>
                        @endfor
                    </div>
                </div>

                {{-- Clear Filters --}}
                <button @click="clearFilters()"
                        class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-700
                               text-gray-700 dark:text-gray-300
                               rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600
                               transition-colors">
                    <i class="fas fa-redo mr-2"></i>
                    ล้างตัวกรอง
                </button>
            </div>
        </aside>

        {{-- Main Content --}}
        <main class="lg:col-span-3">
            {{-- Controls Bar --}}
            <div class="glass-fusion-card rounded-2xl p-4 mb-6 backdrop-blur-xl
                        bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/30
                        shadow-xl">

                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    {{-- Results Count --}}
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        แสดง <span class="font-bold text-gray-900 dark:text-white">{{ $products->total() ?? 0 }}</span> สินค้า
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        {{-- Sort --}}
                        <select x-model="filters.sort"
                                @change="applyFilters()"
                                class="px-4 py-2
                                       bg-white dark:bg-gray-700
                                       border border-gray-200 dark:border-gray-600
                                       rounded-lg text-sm
                                       focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20
                                       text-gray-900 dark:text-gray-100">
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

                        {{-- View Toggle --}}
                        <div class="flex gap-2">
                            <a href="{{ request()->fullUrlWithQuery(['view' => 'grid']) }}"
                               class="px-3 py-2 rounded-lg transition-all
                                      bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300
                                      hover:bg-gray-200 dark:hover:bg-gray-600">
                                <i class="fas fa-th"></i>
                            </a>
                            <a href="{{ request()->fullUrlWithQuery(['view' => 'list']) }}"
                               class="px-3 py-2 rounded-lg transition-all
                                      bg-purple-600 text-white">
                                <i class="fas fa-list"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Products List --}}
            <div x-show="!loading" x-transition>
                @if(isset($products) && $products->count() > 0)
                <div class="space-y-4 mb-8">
                    @foreach($products as $product)
                    <x-ecommerce.product-card-horizontal
                        :product="$product"
                        :showPv="auth()->check()"
                        :showCommission="auth()->check() && optional(auth()->user()->mlmMember)->exists()"
                        :compact="false"
                    />
                    @endforeach
                </div>

                {{-- Pagination --}}
                <div class="flex justify-center">
                    {{ $products->links() }}
                </div>
                @else
                {{-- Empty State --}}
                <div class="glass-fusion-card rounded-2xl p-12 text-center backdrop-blur-xl
                            bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/30">
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
        </main>
    </div>
</div>

@push('scripts')
<script>
/**
 * Store List Component - จัดการ filtering สำหรับ List View
 */
function storeListComponent() {
    return {
        loading: false,
        isFollowing: false,

        filters: {
            search: '{{ request('search', '') }}',
            category: '{{ request('category', '') }}',
            sort: '{{ request('sort', 'latest') }}',
            minPrice: {{ request('min_price', 'null') }},
            maxPrice: {{ request('max_price', 'null') }},
            minRating: {{ request('min_rating', 'null') }},
        },

        init() {
            this.checkFollowingStatus();
        },

        checkFollowingStatus() {
            @if(isset($store) && auth()->check())
            this.isFollowing = false; // TODO: implement
            @endif
        },

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
                this.isFollowing = !this.isFollowing;
            }
        },

        clearFilters() {
            this.filters = {
                search: '',
                category: '',
                sort: 'latest',
                minPrice: null,
                maxPrice: null,
                minRating: null,
            };

            this.applyFilters();
        },

        applyFilters() {
            const params = new URLSearchParams();

            if (this.filters.search) params.set('search', this.filters.search);
            if (this.filters.category) params.set('category', this.filters.category);
            if (this.filters.sort && this.filters.sort !== 'latest') params.set('sort', this.filters.sort);
            if (this.filters.minPrice) params.set('min_price', this.filters.minPrice);
            if (this.filters.maxPrice) params.set('max_price', this.filters.maxPrice);
            if (this.filters.minRating) params.set('min_rating', this.filters.minRating);

            params.set('view', 'list');

            const url = `?${params.toString()}`;
            window.location.href = url;
        }
    };
}
</script>
@endpush
@endsection
