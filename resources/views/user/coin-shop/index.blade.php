{{--
    หน้าร้านค้า Coins - แสดงรายการสินค้าทั้งหมด

    @author Video Reward System
--}}

@extends('layouts.user-v4')

@section('title', $pageTitle ?? 'ร้านค้า Coins')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="{ showFilters: false }">
    {{-- Premium Hero Header (Yellow-Amber for Coin Shop) --}}
    <div class="relative overflow-hidden bg-gradient-to-r from-yellow-500 via-amber-500 to-orange-500 dark:from-yellow-700 dark:via-amber-700 dark:to-orange-700 rounded-2xl shadow-2xl p-8 mb-8">
        {{-- Animated Background Orbs --}}
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 right-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse" style="animation-delay: 0.5s"></div>
        </div>

        {{-- Floating Icon Background --}}
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute text-white/10 text-8xl top-10 right-20" style="animation: float 6s ease-in-out infinite">
                <i class="fas fa-shopping-bag"></i>
            </div>
        </div>

        {{-- Content --}}
        <div class="relative z-10">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                <div>
                    <div class="flex items-center gap-4 mb-4">
                        <div class="glass-fusion p-4 rounded-2xl">
                            <i class="fas fa-store text-3xl text-white drop-shadow-lg"></i>
                        </div>
                        <div>
                            <h1 class="text-3xl md:text-4xl font-bold text-white drop-shadow-lg">
                                🛒 ร้านค้า Coins
                            </h1>
                            <p class="text-yellow-100 mt-1">
                                แลก Coins เป็นของรางวัลมากมาย
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Coin Balance Card --}}
                <div class="glass-fusion rounded-2xl p-6 min-w-[220px]">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 bg-yellow-300 rounded-full flex items-center justify-center shadow-lg">
                            <span class="text-yellow-800 font-black text-xl">C</span>
                        </div>
                        <div>
                            <p class="text-yellow-50 text-sm font-medium">Coins ของคุณ</p>
                            <p class="text-white text-3xl font-bold drop-shadow-lg">{{ number_format($coinBalance, 2) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Official Shop Banner --}}
    <div class="mb-8">
        <a href="{{ route('official-shop.index') }}" class="block group">
            <div class="relative overflow-hidden bg-gradient-to-r from-amber-500 via-purple-600 to-pink-600 rounded-2xl shadow-xl p-6 hover:shadow-2xl transition-all duration-300 transform hover:scale-[1.02]">
                {{-- Background Pattern --}}
                <div class="absolute inset-0 bg-black/10" style="background-image: radial-gradient(circle at 1px 1px, rgba(255,255,255,0.15) 1px, transparent 0); background-size: 30px 30px;"></div>

                {{-- Crown Decoration --}}
                <div class="absolute top-2 right-4 opacity-20">
                    <svg class="w-24 h-24 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M5 16L3 5l5.5 5L12 4l3.5 6L21 5l-2 11H5z"/>
                    </svg>
                </div>

                <div class="relative flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center border border-white/30">
                            <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M5 16L3 5l5.5 5L12 4l3.5 6L21 5l-2 11H5z"/>
                            </svg>
                        </div>
                        <div class="text-white">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="px-2 py-0.5 bg-white/20 backdrop-blur-sm rounded-full text-xs font-semibold">ใช้ Coins ซื้อได้</span>
                            </div>
                            <h3 class="text-2xl font-bold">Official Shop Premium</h3>
                            <p class="text-white/80 text-sm">สินค้าคุณภาพจากร้านทางการ - รองรับการซื้อด้วย Coins</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 text-white font-semibold group-hover:gap-4 transition-all">
                        <span>เยี่ยมชม Official Shop</span>
                        <svg class="w-5 h-5 group-hover:translate-x-2 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                        </svg>
                    </div>
                </div>
            </div>
        </a>
    </div>

    {{-- Featured Products --}}
    @if($featuredProducts->count() > 0)
    <div class="mb-8">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <span class="text-2xl">⭐</span>
            สินค้าแนะนำ
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach($featuredProducts as $product)
                @include('user.coin-shop._product-card', ['product' => $product, 'featured' => true])
            @endforeach
        </div>
    </div>
    @endif

    {{-- Filters & Sort --}}
    <div class="tp-card rounded-xl p-4 mb-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            {{-- Search --}}
            <form action="{{ route('user.coin-shop.index') }}" method="GET" class="flex-1 max-w-md">
                <div class="relative">
                    <input type="text"
                           name="search"
                           value="{{ $search ?? '' }}"
                           placeholder="ค้นหาสินค้า..."
                           class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-yellow-500 focus:border-transparent">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                {{-- Keep existing filters --}}
                @if($currentType)<input type="hidden" name="type" value="{{ $currentType }}">@endif
                @if($currentCategory)<input type="hidden" name="category" value="{{ $currentCategory }}">@endif
            </form>

            {{-- Filter Toggle --}}
            <button type="button"
                    @click="showFilters = !showFilters"
                    class="lg:hidden px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                <span x-text="showFilters ? 'ซ่อนตัวกรอง' : 'แสดงตัวกรอง'"></span>
            </button>

            {{-- Filters --}}
            <div class="flex flex-wrap items-center gap-3" :class="{ 'hidden lg:flex': !showFilters }">
                {{-- Type Filter --}}
                <select name="type"
                        onchange="window.location.href='{{ route('user.coin-shop.index') }}?type=' + this.value + '{{ $currentCategory ? '&category=' . $currentCategory : '' }}{{ $search ? '&search=' . $search : '' }}'"
                        class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                    <option value="">ทุกประเภท</option>
                    @foreach(\App\Models\CoinShopProduct::TYPES as $key => $typeInfo)
                        <option value="{{ $key }}" {{ $currentType === $key ? 'selected' : '' }}>
                            {{ $typeInfo['icon'] }} {{ $typeInfo['name'] }}
                        </option>
                    @endforeach
                </select>

                {{-- Category Filter --}}
                @if($categories->count() > 0)
                <select name="category"
                        onchange="window.location.href='{{ route('user.coin-shop.index') }}?category=' + this.value + '{{ $currentType ? '&type=' . $currentType : '' }}{{ $search ? '&search=' . $search : '' }}'"
                        class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                    <option value="">ทุกหมวดหมู่</option>
                    @foreach($categories as $category)
                        <option value="{{ $category }}" {{ $currentCategory === $category ? 'selected' : '' }}>
                            {{ $category }}
                        </option>
                    @endforeach
                </select>
                @endif

                {{-- Sort --}}
                <select name="sort"
                        onchange="window.location.href='{{ route('user.coin-shop.index') }}?sort=' + this.value + '{{ $currentType ? '&type=' . $currentType : '' }}{{ $currentCategory ? '&category=' . $currentCategory : '' }}{{ $search ? '&search=' . $search : '' }}'"
                        class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                    <option value="recommended" {{ $currentSort === 'recommended' ? 'selected' : '' }}>แนะนำ</option>
                    <option value="price_low" {{ $currentSort === 'price_low' ? 'selected' : '' }}>ราคาต่ำ-สูง</option>
                    <option value="price_high" {{ $currentSort === 'price_high' ? 'selected' : '' }}>ราคาสูง-ต่ำ</option>
                    <option value="newest" {{ $currentSort === 'newest' ? 'selected' : '' }}>ใหม่ล่าสุด</option>
                    <option value="popular" {{ $currentSort === 'popular' ? 'selected' : '' }}>ยอดนิยม</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Products Grid --}}
    @if($products->count() > 0)
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        @foreach($products as $product)
            @include('user.coin-shop._product-card', ['product' => $product])
        @endforeach
    </div>

    {{-- Pagination --}}
    <div class="mt-8">
        {{ $products->withQueryString()->links() }}
    </div>
    @else
    {{-- Empty State --}}
    <div class="text-center py-16">
        <div class="text-6xl mb-4">🛒</div>
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">ไม่พบสินค้า</h3>
        <p class="text-gray-600 dark:text-gray-400 mb-6">
            @if($search)
                ไม่พบสินค้าที่ตรงกับคำค้นหา "{{ $search }}"
            @else
                ยังไม่มีสินค้าในหมวดหมู่นี้
            @endif
        </p>
        <a href="{{ route('user.coin-shop.index') }}"
           class="inline-flex items-center px-6 py-3 bg-yellow-500 hover:bg-yellow-600 text-white font-semibold rounded-lg transition">
            ดูสินค้าทั้งหมด
        </a>
    </div>
    @endif

    {{-- Quick Links --}}
    <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-4">
        <a href="{{ route('user.coin-shop.my-purchases') }}"
           class="tp-card flex items-center gap-4 p-4 rounded-xl hover:shadow-xl transition group">
            <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
            </div>
            <div>
                <h3 class="font-semibold text-gray-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-400 transition">
                    ประวัติการซื้อ
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">ดูรายการที่คุณซื้อทั้งหมด</p>
            </div>
            <svg class="w-5 h-5 text-gray-400 ml-auto group-hover:translate-x-1 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>

        <a href="{{ route('user.video-missions.index') }}"
           class="tp-card flex items-center gap-4 p-4 rounded-xl hover:shadow-xl transition group">
            <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <h3 class="font-semibold text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition">
                    ดูคลิปเก็บ Coins
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">ทำภารกิจดูคลิปรับรางวัล</p>
            </div>
            <svg class="w-5 h-5 text-gray-400 ml-auto group-hover:translate-x-1 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
    </div>
</div>
@endsection

@push('styles')
<style>
.glass-fusion {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}
@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-20px); }
}
</style>
@endpush
