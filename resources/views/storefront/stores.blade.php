{{--
    Stores Listing - หน้ารายการร้านค้าทั้งหมด

    แสดงร้านค้าทั้งหมดในรูปแบบ Grid พร้อม Filter และ Sort
    รองรับ Dark Mode และ Responsive

    Features:
    - Search stores
    - Filter by featured
    - Sort by rating, newest, products count
    - Store cards with preview products
--}}

@extends('layouts.storefront')

@section('title', 'ร้านค้าทั้งหมด - ' . config('app.name'))

@section('meta')
<meta name="description" content="รายการร้านค้าคุณภาพทั้งหมดในระบบ ช้อปสินค้าจากร้านค้าที่ไว้วางใจได้">
@endsection

@section('content')
<div x-data="storesPage()" class="min-h-screen">

    {{-- Header Navigation Bar --}}
    <div class="sticky top-0 z-50 bg-white/80 dark:bg-gray-800/80 backdrop-blur-lg shadow-md border-b border-white/20 dark:border-gray-700/50">
        <div class="container mx-auto px-4">
            <div class="flex items-center gap-4 py-4">
                {{-- Back to Storefront --}}
                <a href="{{ route('storefront.index') }}"
                   class="flex items-center gap-2 px-4 py-2 rounded-xl
                          bg-gray-100 dark:bg-gray-700
                          hover:bg-gray-200 dark:hover:bg-gray-600
                          text-gray-700 dark:text-gray-300
                          transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span class="hidden sm:inline">กลับหน้าหลัก</span>
                </a>

                {{-- Search Bar --}}
                <form action="{{ route('storefront.stores') }}" method="GET" class="flex-1 max-w-xl">
                    <div class="relative">
                        <input type="text"
                               name="search"
                               value="{{ request('search') }}"
                               placeholder="ค้นหาร้านค้า..."
                               class="w-full pl-12 pr-4 py-3
                                      bg-gray-100 dark:bg-gray-700
                                      border-2 border-transparent
                                      focus:border-orange-500 focus:bg-white dark:focus:bg-gray-800
                                      rounded-xl
                                      text-gray-900 dark:text-gray-100
                                      transition-all">
                        <div class="absolute left-4 top-1/2 -translate-y-1/2">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                    </div>
                </form>

                {{-- Dark Mode Toggle --}}
                <button @click="toggleDarkMode()"
                        type="button"
                        class="p-3 rounded-xl bg-gray-100 dark:bg-gray-700
                               hover:bg-gray-200 dark:hover:bg-gray-600
                               text-gray-600 dark:text-gray-300
                               transition-all"
                        title="สลับโหมดมืด/สว่าง">
                    <svg class="w-5 h-5 hidden dark:block" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd"/>
                    </svg>
                    <svg class="w-5 h-5 block dark:hidden" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Hero Section --}}
    <div class="bg-gradient-to-r from-orange-500 via-red-500 to-pink-500 text-white py-12">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                <div>
                    <h1 class="text-3xl md:text-4xl font-black mb-2">
                        🏪 ร้านค้าทั้งหมด
                    </h1>
                    <p class="text-white/80 text-lg">
                        ค้นพบร้านค้าคุณภาพหลากหลายประเภท
                    </p>
                </div>
                <div class="flex gap-4 text-center">
                    <div class="bg-white/20 backdrop-blur-sm rounded-2xl px-6 py-4">
                        <div class="text-3xl font-black">{{ number_format($stats['total_stores']) }}</div>
                        <div class="text-sm text-white/80">ร้านค้า</div>
                    </div>
                    <div class="bg-white/20 backdrop-blur-sm rounded-2xl px-6 py-4">
                        <div class="text-3xl font-black">{{ number_format($stats['total_products']) }}</div>
                        <div class="text-sm text-white/80">สินค้า</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters & Sort --}}
    <div class="container mx-auto px-4 py-6">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-4 mb-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                {{-- Filter Buttons --}}
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('storefront.stores') }}"
                       class="px-4 py-2 rounded-xl font-medium transition-all
                              {{ !request('featured') ? 'bg-orange-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                        ทั้งหมด
                    </a>
                    <a href="{{ route('storefront.stores', ['featured' => 1]) }}"
                       class="px-4 py-2 rounded-xl font-medium transition-all
                              {{ request('featured') ? 'bg-orange-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                        ⭐ ร้านแนะนำ
                    </a>
                </div>

                {{-- Sort Dropdown --}}
                <div class="relative" x-data="{ sortOpen: false }">
                    <button @click="sortOpen = !sortOpen"
                            type="button"
                            class="flex items-center gap-2 px-4 py-2 rounded-xl
                                   bg-gray-100 dark:bg-gray-700
                                   text-gray-700 dark:text-gray-300
                                   hover:bg-gray-200 dark:hover:bg-gray-600
                                   transition-all">
                        <span>เรียงตาม: {{ ['rating' => 'คะแนน', 'newest' => 'ใหม่ล่าสุด', 'products' => 'สินค้ามากสุด', 'name' => 'ชื่อ'][$sortBy] ?? 'คะแนน' }}</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="sortOpen"
                         x-cloak
                         @click.outside="sortOpen = false"
                         class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden z-50">
                        <a href="{{ route('storefront.stores', array_merge(request()->except('sort_by'), ['sort_by' => 'rating'])) }}"
                           class="block px-4 py-3 hover:bg-gray-100 dark:hover:bg-gray-700 {{ $sortBy === 'rating' ? 'bg-orange-50 dark:bg-orange-900/20 text-orange-600' : 'text-gray-700 dark:text-gray-300' }}">
                            คะแนนสูงสุด
                        </a>
                        <a href="{{ route('storefront.stores', array_merge(request()->except('sort_by'), ['sort_by' => 'newest'])) }}"
                           class="block px-4 py-3 hover:bg-gray-100 dark:hover:bg-gray-700 {{ $sortBy === 'newest' ? 'bg-orange-50 dark:bg-orange-900/20 text-orange-600' : 'text-gray-700 dark:text-gray-300' }}">
                            ใหม่ล่าสุด
                        </a>
                        <a href="{{ route('storefront.stores', array_merge(request()->except('sort_by'), ['sort_by' => 'products'])) }}"
                           class="block px-4 py-3 hover:bg-gray-100 dark:hover:bg-gray-700 {{ $sortBy === 'products' ? 'bg-orange-50 dark:bg-orange-900/20 text-orange-600' : 'text-gray-700 dark:text-gray-300' }}">
                            สินค้ามากสุด
                        </a>
                        <a href="{{ route('storefront.stores', array_merge(request()->except('sort_by'), ['sort_by' => 'name'])) }}"
                           class="block px-4 py-3 hover:bg-gray-100 dark:hover:bg-gray-700 {{ $sortBy === 'name' ? 'bg-orange-50 dark:bg-orange-900/20 text-orange-600' : 'text-gray-700 dark:text-gray-300' }}">
                            ชื่อร้าน (ก-ฮ)
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Official Store Card --}}
        <div class="mb-8">
            <a href="{{ route('official-shop.index') }}"
               class="block bg-gradient-to-r from-amber-500 via-purple-600 to-pink-500 rounded-3xl p-6 text-white
                      transform hover:scale-[1.02] transition-all shadow-xl hover:shadow-2xl
                      ring-4 ring-amber-400/30">
                <div class="flex items-center gap-6">
                    <div class="w-20 h-20 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center
                               ring-4 ring-amber-400/30">
                        <i class="fas fa-crown text-4xl text-amber-300"></i>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="px-3 py-1 bg-gradient-to-r from-amber-400 to-amber-500 rounded-full text-sm font-bold text-white shadow-lg">
                                ✨ Official Store
                            </span>
                            <span class="px-3 py-1 bg-green-500/30 rounded-full text-sm font-bold text-green-100">
                                Premium V3
                            </span>
                        </div>
                        <h3 class="text-2xl font-black">TP Official Shop</h3>
                        <p class="text-white/80">สินค้าพรีเมี่ยมคุณภาพจากร้านทางการ รับประกันของแท้ 100% ✓</p>
                    </div>
                    <div class="flex flex-col items-center gap-2">
                        <span class="px-4 py-2 bg-white/20 backdrop-blur-sm rounded-xl text-sm font-bold">
                            ดูร้าน
                        </span>
                        <svg class="w-6 h-6 text-white/60 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </div>
            </a>
        </div>

        {{-- Stores Grid --}}
        @if($stores->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @foreach($stores as $store)
            <a href="{{ route('store.show', $store->store_slug) }}"
               class="group bg-white dark:bg-gray-800 rounded-2xl overflow-hidden
                      shadow-lg hover:shadow-2xl
                      transform hover:-translate-y-2
                      transition-all duration-300
                      border border-gray-100 dark:border-gray-700">

                {{-- Store Banner --}}
                <div class="relative h-32 overflow-hidden">
                    @if($store->banner_url)
                        <img src="{{ $store->banner_url }}"
                             alt="{{ $store->store_name }}"
                             class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                    @else
                        <div class="w-full h-full bg-gradient-to-br from-gray-200 to-gray-300 dark:from-gray-700 dark:to-gray-600"></div>
                    @endif
                    <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent"></div>

                    {{-- Featured Badge --}}
                    @if($store->is_featured_home)
                    <div class="absolute top-2 right-2 px-2 py-1 bg-yellow-500 text-white text-xs font-bold rounded-full">
                        ⭐ แนะนำ
                    </div>
                    @endif
                </div>

                {{-- Store Info --}}
                <div class="relative px-4 pb-4">
                    {{-- Logo --}}
                    <div class="relative -mt-8 mb-3 flex justify-center">
                        <div class="w-16 h-16 rounded-xl overflow-hidden
                                   ring-4 ring-white dark:ring-gray-800 shadow-lg
                                   bg-white dark:bg-gray-700">
                            @if($store->logo_url)
                                <img src="{{ $store->logo_url }}"
                                     alt="{{ $store->store_name }}"
                                     class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-orange-400 to-red-400 text-white text-2xl font-bold">
                                    {{ strtoupper(substr($store->store_name, 0, 1)) }}
                                </div>
                            @endif
                        </div>
                    </div>

                    <h3 class="text-lg font-bold text-gray-900 dark:text-white text-center mb-1 truncate">
                        {{ $store->store_name }}
                    </h3>

                    {{-- Rating --}}
                    <div class="flex items-center justify-center gap-1 mb-2">
                        <div class="flex items-center text-yellow-500">
                            @for($i = 1; $i <= 5; $i++)
                                @if($i <= floor($store->rating_average ?? 0))
                                    <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                @else
                                    <svg class="w-4 h-4 fill-current text-gray-300 dark:text-gray-600" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                @endif
                            @endfor
                        </div>
                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            {{ number_format($store->rating_average ?? 0, 1) }}
                        </span>
                    </div>

                    {{-- Stats --}}
                    <div class="flex items-center justify-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                        <span>{{ $store->products_count ?? 0 }} สินค้า</span>
                        <span>•</span>
                        <span>{{ number_format($store->visit_count ?? 0) }} เข้าชม</span>
                    </div>

                    {{-- Preview Products --}}
                    @if($store->products && $store->products->count() > 0)
                    <div class="mt-4 grid grid-cols-4 gap-1">
                        @foreach($store->products->take(4) as $product)
                        <div class="aspect-square rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-700">
                            <img src="{{ $product->main_image_url ?? 'https://via.placeholder.com/100' }}"
                                 alt="{{ $product->name }}"
                                 class="w-full h-full object-cover">
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </a>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="mt-8">
            {{ $stores->withQueryString()->links() }}
        </div>

        @else
        {{-- Empty State --}}
        <div class="text-center py-16">
            <div class="w-24 h-24 mx-auto mb-6 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">ไม่พบร้านค้า</h3>
            <p class="text-gray-500 dark:text-gray-400 mb-6">ลองค้นหาด้วยคำค้นอื่นหรือล้างตัวกรอง</p>
            <a href="{{ route('storefront.stores') }}"
               class="inline-flex items-center gap-2 px-6 py-3 bg-orange-500 hover:bg-orange-600 text-white font-bold rounded-xl transition-colors">
                ดูร้านค้าทั้งหมด
            </a>
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
function storesPage() {
    return {
        init() {
            this.initDarkMode();
        },

        initDarkMode() {
            if (localStorage.getItem('theme') === 'dark' ||
                (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        },

        toggleDarkMode() {
            document.documentElement.classList.toggle('dark');
            localStorage.setItem('theme', document.documentElement.classList.contains('dark') ? 'dark' : 'light');
        }
    };
}
</script>
@endpush
@endsection
