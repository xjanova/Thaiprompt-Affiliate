@extends('layouts.app')

@section('title', 'ร้านค้าทั้งหมด')

@section('meta')
<meta name="description" content="เลือกซื้อสินค้าจากร้านค้าต่างๆ มากมายบนแพลตฟอร์ม">
@endsection

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-indigo-50/30">

    <!-- Header Section -->
    <div class="relative overflow-hidden bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute inset-0" style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 40px 40px;"></div>
        </div>

        <div class="container mx-auto px-4 py-12 md:py-20 relative z-10">
            <div class="max-w-4xl mx-auto text-center text-white">
                <h1 class="text-4xl md:text-6xl font-black mb-4 tracking-tight drop-shadow-lg">
                    🛍️ ร้านค้าทั้งหมด
                </h1>
                <p class="text-xl md:text-2xl text-purple-100 font-medium">
                    เลือกซื้อสินค้าจากร้านค้าคุณภาพ
                </p>
            </div>
        </div>
    </div>

    <!-- Search and Filter Section -->
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-7xl mx-auto">
            <form method="GET" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- ค้นหา -->
                    <div>
                        <label class="block text-sm font-bold text-gray-900 dark:text-gray-100 mb-2">
                            🔍 ค้นหาร้านค้า
                        </label>
                        <input type="text"
                               name="search"
                               value="{{ request('search') }}"
                               placeholder="ค้นหาชื่อร้านค้า..."
                               class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 dark:bg-gray-700 dark:text-white transition-all">
                    </div>

                    <!-- เรียงลำดับ -->
                    <div>
                        <label class="block text-sm font-bold text-gray-900 dark:text-gray-100 mb-2">
                            📊 เรียงลำดับ
                        </label>
                        <select name="sort"
                                onchange="this.form.submit()"
                                class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 dark:bg-gray-700 dark:text-white transition-all">
                            <option value="latest" {{ request('sort') == 'latest' ? 'selected' : '' }}>ล่าสุด</option>
                            <option value="name" {{ request('sort') == 'name' ? 'selected' : '' }}>ชื่อ (A-Z)</option>
                            <option value="popular" {{ request('sort') == 'popular' ? 'selected' : '' }}>ยอดนิยม</option>
                            <option value="rating" {{ request('sort') == 'rating' ? 'selected' : '' }}>คะแนนสูงสุด</option>
                        </select>
                    </div>
                </div>

                <div class="flex gap-3 mt-4">
                    <button type="submit"
                            class="flex-1 px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all">
                        🔍 ค้นหา
                    </button>
                    @if(request()->hasAny(['search', 'sort']))
                    <a href="{{ route('vendor.stores.index') }}"
                       class="px-6 py-3 border-2 border-gray-300 hover:border-red-500 text-gray-700 dark:text-gray-200 hover:text-red-500 font-bold rounded-xl transition-all bg-white dark:bg-gray-700 hover:bg-red-50 dark:hover:bg-red-900/20">
                        ล้างตัวกรอง
                    </a>
                    @endif
                </div>
            </form>

            <!-- Stores Grid -->
            @if($stores->count() > 0)
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-8">
                    @foreach($stores as $store)
                        <a href="{{ route('vendor.stores.show', $store->store_slug) }}"
                           class="group bg-white dark:bg-gray-800 rounded-xl shadow-lg hover:shadow-2xl transition-all transform hover:-translate-y-2 overflow-hidden border-2 border-transparent hover:border-indigo-500">

                            <!-- Store Banner/Logo -->
                            <div class="relative h-48 bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500 overflow-hidden">
                                @if($store->store_banner)
                                    <img src="{{ $store->banner_url }}"
                                         alt="{{ $store->store_name }}"
                                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                                @else
                                    <div class="absolute inset-0 flex items-center justify-center text-white text-6xl">
                                        🏪
                                    </div>
                                @endif

                                <!-- Verified Badge -->
                                @if($store->is_verified)
                                    <div class="absolute top-3 right-3 bg-emerald-500 text-white p-2 rounded-full shadow-lg">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                @endif

                                <!-- Store Logo Overlay -->
                                @if($store->store_logo)
                                    <div class="absolute -bottom-10 left-4 w-20 h-20 bg-white dark:bg-gray-700 rounded-xl shadow-xl p-2 border-4 border-white dark:border-gray-800">
                                        <img src="{{ $store->logo_url }}"
                                             alt="{{ $store->store_name }}"
                                             class="w-full h-full object-contain">
                                    </div>
                                @endif
                            </div>

                            <!-- Store Info -->
                            <div class="p-6 pt-8">
                                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors line-clamp-1">
                                    {{ $store->store_name }}
                                </h3>

                                @if($store->store_description)
                                    <p class="text-gray-600 dark:text-gray-400 text-sm mb-4 line-clamp-2">
                                        {{ $store->store_description }}
                                    </p>
                                @endif

                                <!-- Stats -->
                                <div class="flex flex-wrap gap-2 mb-4">
                                    <div class="flex items-center gap-1 text-xs text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-3 py-1 rounded-full">
                                        <span>📦</span>
                                        <span>{{ $store->products_count ?? 0 }} สินค้า</span>
                                    </div>
                                    <div class="flex items-center gap-1 text-xs text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-3 py-1 rounded-full">
                                        <span>🛒</span>
                                        <span>{{ $store->total_orders ?? 0 }} ยอดขาย</span>
                                    </div>
                                    @if($store->rating_count > 0)
                                        <div class="flex items-center gap-1 text-xs text-yellow-600 dark:text-yellow-400 bg-yellow-100 dark:bg-yellow-900/30 px-3 py-1 rounded-full">
                                            <span>⭐</span>
                                            <span>{{ number_format($store->rating_average, 1) }}</span>
                                        </div>
                                    @endif
                                </div>

                                <!-- View Store Button -->
                                <div class="flex items-center justify-between text-indigo-600 dark:text-indigo-400 font-bold group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors">
                                    <span>เข้าชมร้านค้า</span>
                                    <svg class="w-5 h-5 transform group-hover:translate-x-2 transition-transform" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="mt-8">
                    {{ $stores->links() }}
                </div>
            @else
                <!-- Empty State -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-12 text-center">
                    <div class="text-6xl mb-4">🔍</div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                        ไม่พบร้านค้า
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        ลองค้นหาด้วยคำอื่นหรือล้างตัวกรองเพื่อดูร้านค้าทั้งหมด
                    </p>
                    @if(request()->hasAny(['search', 'sort']))
                        <a href="{{ route('vendor.stores.index') }}"
                           class="inline-flex items-center gap-2 px-8 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"/>
                            </svg>
                            ดูร้านค้าทั้งหมด
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
