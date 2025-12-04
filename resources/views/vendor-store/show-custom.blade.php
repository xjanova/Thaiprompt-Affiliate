@extends('layouts.storefront')

@section('title', $layoutSettings->meta_title ?? $store->store_name)

@section('meta')
<meta name="description" content="{{ $layoutSettings->meta_description ?? $store->store_description ?? 'ร้านค้าออนไลน์' }}">
@if($layoutSettings->meta_keywords)
<meta name="keywords" content="{{ $layoutSettings->meta_keywords }}">
@endif
@endsection

@push('styles')
<style>
    :root {
        --store-primary: {{ $layoutSettings->primary_color ?? '#6366f1' }};
        --store-secondary: {{ $layoutSettings->secondary_color ?? '#8b5cf6' }};
        --store-accent: {{ $layoutSettings->accent_color ?? '#ec4899' }};
        --store-text: {{ $layoutSettings->text_color ?? '#1f2937' }};
        --store-bg: {{ $layoutSettings->background_color ?? '#ffffff' }};
    }

    .store-bg {
        background-color: var(--store-bg);
    }

    .store-primary-text {
        color: var(--store-primary);
    }

    .store-primary-bg {
        background-color: var(--store-primary);
    }

    .store-button {
        background: linear-gradient(135deg, var(--store-primary), var(--store-secondary));
    }

    .store-button:hover {
        filter: brightness(1.1);
    }

    .store-accent-bg {
        background-color: var(--store-accent);
    }

    .product-card-default {
        @apply bg-white/80 dark:bg-gray-800/80 backdrop-blur-lg rounded-xl shadow-lg overflow-hidden transition-all duration-300 hover:shadow-xl hover:-translate-y-1;
    }

    .product-card-minimal {
        @apply bg-white dark:bg-gray-800 rounded-lg overflow-hidden transition-all duration-300 hover:shadow-lg;
    }

    .product-card-detailed {
        @apply bg-white/80 dark:bg-gray-800/80 backdrop-blur-lg rounded-2xl shadow-xl overflow-hidden transition-all duration-300 hover:shadow-2xl;
    }
</style>

{{-- Custom CSS จากผู้ใช้ --}}
@if($layoutSettings->custom_css)
<style>
{!! $layoutSettings->custom_css !!}
</style>
@endif
@endpush

@section('content')
<div class="min-h-screen bg-transparent">

    {{-- Custom Store Header --}}
    @php
        $headerStyle = $layoutSettings->header_style ?? 'gradient';
        $headerHeight = $layoutSettings->header_height ?? 200;
    @endphp

    <header class="relative overflow-hidden"
            style="min-height: {{ $headerHeight }}px;
                   @if($headerStyle === 'image' && $layoutSettings->header_image)
                       background-image: url('{{ Storage::url($layoutSettings->header_image) }}');
                       background-size: cover;
                       background-position: center;
                   @elseif($headerStyle === 'solid')
                       background-color: {{ $layoutSettings->primary_color ?? '#6366f1' }};
                   @elseif($headerStyle === 'transparent')
                       background: transparent;
                   @else
                       background: linear-gradient(135deg, {{ $layoutSettings->primary_color ?? '#6366f1' }}, {{ $layoutSettings->secondary_color ?? '#8b5cf6' }});
                   @endif
            ">
        {{-- Overlay for image header --}}
        @if($headerStyle === 'image' && $layoutSettings->header_image)
            <div class="absolute inset-0 bg-black/40"></div>
        @endif

        {{-- Background Pattern for gradient/solid --}}
        @if($headerStyle !== 'image')
            <div class="absolute inset-0 opacity-10">
                <div class="absolute inset-0" style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 40px 40px;"></div>
            </div>
        @endif

        <div class="container mx-auto px-4 py-12 md:py-16 relative z-10">
            <div class="max-w-5xl mx-auto">
                <div class="flex flex-col md:flex-row items-center gap-8 text-white">
                    {{-- Store Logo --}}
                    @if($layoutSettings->show_store_logo)
                        @if($store->store_logo)
                            <div class="w-28 h-28 md:w-36 md:h-36 bg-white/20 backdrop-blur-lg rounded-3xl shadow-2xl p-3 border-4 border-white/30 flex-shrink-0">
                                <img src="{{ $store->logo_url }}" alt="{{ $store->store_name }}" class="w-full h-full object-contain">
                            </div>
                        @else
                            <div class="w-28 h-28 md:w-36 md:h-36 bg-white/20 backdrop-blur-lg rounded-3xl shadow-2xl flex items-center justify-center text-5xl md:text-6xl border-4 border-white/30 flex-shrink-0">
                                🏪
                            </div>
                        @endif
                    @endif

                    {{-- Store Info --}}
                    <div class="flex-1 text-center md:text-left">
                        @if($store->is_verified)
                        <div class="inline-flex items-center gap-2 bg-emerald-500/80 backdrop-blur-lg px-4 py-2 rounded-full mb-4 border border-white/30">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="font-bold">ร้านค้ายืนยันตัวตน</span>
                        </div>
                        @endif

                        @if($layoutSettings->show_store_name)
                            <h1 class="text-3xl md:text-5xl font-black mb-3 tracking-tight drop-shadow-lg">
                                {{ $store->store_name }}
                            </h1>
                        @endif

                        @if($layoutSettings->show_store_description && $store->store_description)
                            <p class="text-lg md:text-xl text-white/90 mb-4">
                                {{ Str::limit($store->store_description, 150) }}
                            </p>
                        @endif

                        {{-- Stats --}}
                        @if($layoutSettings->show_store_stats)
                            <div class="flex flex-wrap gap-3 justify-center md:justify-start">
                                <div class="flex items-center gap-2 bg-white/20 backdrop-blur-lg px-4 py-2 rounded-xl border border-white/30">
                                    <span>📦</span>
                                    <span class="font-bold">{{ $stats['total_products'] }} สินค้า</span>
                                </div>
                                <div class="flex items-center gap-2 bg-white/20 backdrop-blur-lg px-4 py-2 rounded-xl border border-white/30">
                                    <span>🛒</span>
                                    <span class="font-bold">{{ $stats['total_sales'] }} ยอดขาย</span>
                                </div>
                                @if($stats['rating_count'] > 0)
                                    <div class="flex items-center gap-2 bg-white/20 backdrop-blur-lg px-4 py-2 rounded-xl border border-white/30">
                                        <span class="text-yellow-300">⭐</span>
                                        <span class="font-bold">{{ number_format($stats['rating'], 1) }} ({{ $stats['rating_count'] }})</span>
                                    </div>
                                @endif
                            </div>
                        @endif

                        {{-- Social Links from Layout Settings --}}
                        @if($layoutSettings->show_social_links && $layoutSettings->social_links)
                            <div class="flex gap-2 mt-4 justify-center md:justify-start">
                                @if(!empty($layoutSettings->social_links['facebook']))
                                    <a href="{{ $layoutSettings->social_links['facebook'] }}" target="_blank"
                                       class="w-10 h-10 bg-white/20 hover:bg-white/30 backdrop-blur-lg rounded-xl flex items-center justify-center transition-all transform hover:scale-110 border border-white/30">
                                        <span class="text-xl">📘</span>
                                    </a>
                                @endif
                                @if(!empty($layoutSettings->social_links['line']))
                                    <a href="https://line.me/R/ti/p/{{ ltrim($layoutSettings->social_links['line'], '@') }}" target="_blank"
                                       class="w-10 h-10 bg-white/20 hover:bg-white/30 backdrop-blur-lg rounded-xl flex items-center justify-center transition-all transform hover:scale-110 border border-white/30">
                                        <span class="text-xl">💚</span>
                                    </a>
                                @endif
                                @if(!empty($layoutSettings->social_links['instagram']))
                                    <a href="{{ $layoutSettings->social_links['instagram'] }}" target="_blank"
                                       class="w-10 h-10 bg-white/20 hover:bg-white/30 backdrop-blur-lg rounded-xl flex items-center justify-center transition-all transform hover:scale-110 border border-white/30">
                                        <span class="text-xl">📷</span>
                                    </a>
                                @endif
                                @if(!empty($layoutSettings->social_links['tiktok']))
                                    <a href="{{ $layoutSettings->social_links['tiktok'] }}" target="_blank"
                                       class="w-10 h-10 bg-white/20 hover:bg-white/30 backdrop-blur-lg rounded-xl flex items-center justify-center transition-all transform hover:scale-110 border border-white/30">
                                        <span class="text-xl">🎵</span>
                                    </a>
                                @endif
                                @if(!empty($layoutSettings->social_links['youtube']))
                                    <a href="{{ $layoutSettings->social_links['youtube'] }}" target="_blank"
                                       class="w-10 h-10 bg-white/20 hover:bg-white/30 backdrop-blur-lg rounded-xl flex items-center justify-center transition-all transform hover:scale-110 border border-white/30">
                                        <span class="text-xl">📺</span>
                                    </a>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Wave Divider --}}
        <div class="absolute bottom-0 left-0 right-0">
            <svg viewBox="0 0 1440 48" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full">
                <path d="M0 48h1440V24C1440 24 1200 0 720 0S0 24 0 24v24z" fill="white" fill-opacity="0.9"/>
            </svg>
        </div>
    </header>

    {{-- Banner Slider --}}
    @if($layoutSettings->slider_enabled && !empty($layoutSettings->slider_images))
        <section class="relative -mt-6 z-20">
            <div class="container mx-auto px-4">
                <div class="rounded-2xl overflow-hidden shadow-2xl">
                    <div class="swiper-container" id="store-slider">
                        <div class="swiper-wrapper">
                            @foreach($layoutSettings->slider_images as $slide)
                                @if(!empty($slide['image']))
                                    <div class="swiper-slide">
                                        <a href="{{ $slide['link'] ?? '#' }}" class="block relative">
                                            <img src="{{ str_starts_with($slide['image'], 'http') ? $slide['image'] : Storage::url($slide['image']) }}"
                                                 alt="{{ $slide['title'] ?? 'Banner' }}"
                                                 class="w-full h-[250px] md:h-[400px] object-cover">
                                            @if(!empty($slide['title']))
                                                <div class="absolute inset-0 flex items-center justify-center bg-black/30">
                                                    <div class="text-center text-white px-4">
                                                        <h3 class="text-2xl md:text-4xl font-bold drop-shadow-lg">{{ $slide['title'] }}</h3>
                                                        @if(!empty($slide['subtitle']))
                                                            <p class="text-lg mt-2 drop-shadow">{{ $slide['subtitle'] }}</p>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endif
                                        </a>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                        @if($layoutSettings->slider_show_arrows)
                            <div class="swiper-button-prev !text-white !bg-black/30 !rounded-full !w-10 !h-10 after:!text-lg"></div>
                            <div class="swiper-button-next !text-white !bg-black/30 !rounded-full !w-10 !h-10 after:!text-lg"></div>
                        @endif
                        @if($layoutSettings->slider_show_dots)
                            <div class="swiper-pagination"></div>
                        @endif
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- Main Content --}}
    <div class="container mx-auto px-4 py-8">
        @php
            $productsPerRow = $layoutSettings->products_per_row ?? 4;
            $gridCols = match($productsPerRow) {
                2 => 'grid-cols-1 sm:grid-cols-2',
                3 => 'grid-cols-1 sm:grid-cols-2 md:grid-cols-3',
                4 => 'grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4',
                5 => 'grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5',
                6 => 'grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6',
                default => 'grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4',
            };
            $productCardStyle = $layoutSettings->product_card_style ?? 'default';
        @endphp

        {{-- Featured Products Section --}}
        @if($layoutSettings->show_featured_products && $featuredProducts && $featuredProducts->count() > 0)
            <section class="mb-12">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl md:text-3xl font-bold flex items-center gap-2" style="color: var(--store-primary)">
                        <span>⭐</span>
                        {{ $layoutSettings->featured_title ?? 'สินค้าแนะนำ' }}
                    </h2>
                    <a href="{{ route('store.show', $store->store_slug) }}" class="store-button text-white px-4 py-2 rounded-lg text-sm transition hover:shadow-lg">
                        ดูทั้งหมด →
                    </a>
                </div>

                <div class="grid {{ $gridCols }} gap-4 md:gap-6">
                    @foreach($featuredProducts as $product)
                        <a href="{{ route('product.show', $product->slug) }}" class="product-card-{{ $productCardStyle }} block group">
                            {{-- Product Image --}}
                            <div class="aspect-square relative overflow-hidden">
                                @if($product->primary_image_url)
                                    <img src="{{ $product->primary_image_url }}"
                                         alt="{{ $product->name }}"
                                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                                @else
                                    <div class="w-full h-full bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center text-6xl text-gray-300">
                                        📦
                                    </div>
                                @endif
                                {{-- Discount Badge --}}
                                @if($product->discount_percent > 0)
                                    <div class="absolute top-2 left-2 store-accent-bg text-white text-xs font-bold px-2 py-1 rounded">
                                        -{{ $product->discount_percent }}%
                                    </div>
                                @endif
                            </div>
                            {{-- Product Info --}}
                            <div class="p-4">
                                <h3 class="font-semibold text-gray-800 dark:text-gray-200 line-clamp-2 mb-2 group-hover:store-primary-text transition">
                                    {{ $product->name }}
                                </h3>
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="text-lg font-bold" style="color: var(--store-primary)">
                                        ฿{{ number_format($product->sale_price ?? $product->price) }}
                                    </span>
                                    @if($product->sale_price && $product->price > $product->sale_price)
                                        <span class="text-sm text-gray-400 line-through">
                                            ฿{{ number_format($product->price) }}
                                        </span>
                                    @endif
                                </div>
                                @if($product->rating_average > 0 || $product->sales_count > 0)
                                    <div class="flex items-center gap-1 text-sm text-gray-500 dark:text-gray-400">
                                        @if($product->rating_average > 0)
                                            <span class="text-yellow-400">★</span>
                                            <span>{{ number_format($product->rating_average, 1) }}</span>
                                            <span class="text-gray-300">|</span>
                                        @endif
                                        <span>ขายแล้ว {{ $product->sales_count }}</span>
                                    </div>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Categories Section --}}
        @if($layoutSettings->show_categories && $categories->count() > 0)
            <section class="mb-12">
                <h2 class="text-2xl md:text-3xl font-bold mb-6 flex items-center gap-2" style="color: var(--store-primary)">
                    <span>📁</span>
                    {{ $layoutSettings->categories_title ?? 'หมวดหมู่สินค้า' }}
                </h2>

                @php $categoriesStyle = $layoutSettings->categories_style ?? 'grid'; @endphp

                @if($categoriesStyle === 'grid')
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                        @foreach($categories as $category)
                            <a href="{{ route('store.show', ['slug' => $store->store_slug, 'category' => $category->slug]) }}"
                               class="group bg-white/80 dark:bg-gray-800/80 backdrop-blur-lg rounded-xl p-4 text-center shadow-md hover:shadow-xl transition border border-gray-100 dark:border-gray-700 hover:border-transparent hover:ring-2"
                               style="--tw-ring-color: var(--store-primary)">
                                <div class="w-14 h-14 mx-auto mb-3 rounded-full flex items-center justify-center text-2xl"
                                     style="background: linear-gradient(135deg, {{ $layoutSettings->primary_color ?? '#6366f1' }}22, {{ $layoutSettings->secondary_color ?? '#8b5cf6' }}22)">
                                    {{ $category->icon ?? '📦' }}
                                </div>
                                <span class="font-medium text-gray-700 dark:text-gray-200 group-hover:store-primary-text transition">
                                    {{ $category->name }}
                                </span>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="flex flex-wrap gap-3">
                        @foreach($categories as $category)
                            <a href="{{ route('store.show', ['slug' => $store->store_slug, 'category' => $category->slug]) }}"
                               class="px-4 py-2 bg-white/80 dark:bg-gray-800/80 backdrop-blur-lg rounded-full shadow hover:shadow-md transition border hover:store-primary-text"
                               style="border-color: var(--store-primary)33">
                                {{ $category->icon ?? '📦' }} {{ $category->name }}
                            </a>
                        @endforeach
                    </div>
                @endif
            </section>
        @endif

        {{-- Main Products with Filter Sidebar --}}
        <div class="flex flex-col lg:flex-row gap-6">
            {{-- Filters Sidebar --}}
            @if($layoutSettings->show_sidebar ?? true)
                <aside class="lg:w-80 flex-shrink-0" style="order: {{ $layoutSettings->sidebar_position === 'right' ? '2' : '1' }}">
                    <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-lg rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden sticky top-4">
                        {{-- Filters Header --}}
                        <div class="text-white px-6 py-4" style="background: linear-gradient(135deg, var(--store-primary), var(--store-secondary))">
                            <h2 class="text-lg font-bold flex items-center gap-2">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clip-rule="evenodd"/>
                                </svg>
                                ค้นหา & กรอง
                            </h2>
                        </div>

                        <form method="GET" action="{{ route('store.show', $store->store_slug) }}" class="p-6 space-y-6">
                            {{-- Search --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    ค้นหาสินค้า
                                </label>
                                <input type="text" name="search" value="{{ request('search') }}"
                                       placeholder="พิมพ์ชื่อสินค้า..."
                                       class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 dark:border-gray-600 dark:bg-gray-700 focus:ring-2 focus:ring-opacity-50 transition"
                                       style="--tw-ring-color: var(--store-primary)">
                            </div>

                            {{-- Categories --}}
                            @if($categories->count() > 0)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    หมวดหมู่
                                </label>
                                <select name="category" class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 dark:border-gray-600 dark:bg-gray-700 focus:ring-2 focus:ring-opacity-50 transition">
                                    <option value="">ทุกหมวดหมู่</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->slug }}" {{ request('category') == $cat->slug ? 'selected' : '' }}>
                                            {{ $cat->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @endif

                            {{-- Sort --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    เรียงตาม
                                </label>
                                <select name="sort" class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 dark:border-gray-600 dark:bg-gray-700 focus:ring-2 focus:ring-opacity-50 transition">
                                    <option value="latest" {{ request('sort') == 'latest' ? 'selected' : '' }}>ล่าสุด</option>
                                    <option value="price_low" {{ request('sort') == 'price_low' ? 'selected' : '' }}>ราคาต่ำ-สูง</option>
                                    <option value="price_high" {{ request('sort') == 'price_high' ? 'selected' : '' }}>ราคาสูง-ต่ำ</option>
                                    <option value="popular" {{ request('sort') == 'popular' ? 'selected' : '' }}>ยอดนิยม</option>
                                </select>
                            </div>

                            {{-- Submit --}}
                            <button type="submit" class="store-button w-full text-white font-semibold py-3 rounded-xl transition hover:shadow-lg">
                                ค้นหา
                            </button>
                        </form>
                    </div>
                </aside>
            @endif

            {{-- Products Grid --}}
            <div class="flex-1" style="order: {{ $layoutSettings->sidebar_position === 'right' ? '1' : '2' }}">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-bold text-gray-800 dark:text-gray-200">
                        🛍️ สินค้าทั้งหมด
                        <span class="text-gray-500 text-base font-normal">({{ $products->total() }} รายการ)</span>
                    </h2>
                </div>

                @if($products->count() > 0)
                    <div class="grid {{ $gridCols }} gap-4 md:gap-6">
                        @foreach($products as $product)
                            <a href="{{ route('product.show', $product->slug) }}" class="product-card-{{ $productCardStyle }} block group">
                                {{-- Product Image --}}
                                <div class="aspect-square relative overflow-hidden">
                                    @if($product->primary_image_url)
                                        <img src="{{ $product->primary_image_url }}"
                                             alt="{{ $product->name }}"
                                             class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                                             loading="lazy">
                                    @else
                                        <div class="w-full h-full bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center text-6xl text-gray-300">
                                            📦
                                        </div>
                                    @endif
                                    @if($product->discount_percent > 0)
                                        <div class="absolute top-2 left-2 store-accent-bg text-white text-xs font-bold px-2 py-1 rounded">
                                            -{{ $product->discount_percent }}%
                                        </div>
                                    @endif
                                </div>
                                {{-- Product Info --}}
                                <div class="p-4">
                                    <h3 class="font-semibold text-gray-800 dark:text-gray-200 line-clamp-2 mb-2 group-hover:store-primary-text transition">
                                        {{ $product->name }}
                                    </h3>
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="text-lg font-bold" style="color: var(--store-primary)">
                                            ฿{{ number_format($product->sale_price ?? $product->price) }}
                                        </span>
                                        @if($product->sale_price && $product->price > $product->sale_price)
                                            <span class="text-sm text-gray-400 line-through">
                                                ฿{{ number_format($product->price) }}
                                            </span>
                                        @endif
                                    </div>
                                    @if($product->rating_average > 0 || $product->sales_count > 0)
                                        <div class="flex items-center gap-1 text-sm text-gray-500 dark:text-gray-400">
                                            @if($product->rating_average > 0)
                                                <span class="text-yellow-400">★</span>
                                                <span>{{ number_format($product->rating_average, 1) }}</span>
                                                <span class="text-gray-300">|</span>
                                            @endif
                                            <span>ขายแล้ว {{ $product->sales_count }}</span>
                                        </div>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>

                    {{-- Pagination --}}
                    <div class="mt-8">
                        {{ $products->links() }}
                    </div>
                @else
                    <div class="text-center py-16 bg-white/80 dark:bg-gray-800/80 backdrop-blur-lg rounded-2xl">
                        <div class="text-6xl mb-4">🔍</div>
                        <h3 class="text-xl font-semibold text-gray-700 dark:text-gray-300 mb-2">ไม่พบสินค้า</h3>
                        <p class="text-gray-500 dark:text-gray-400">ลองค้นหาด้วยคำค้นอื่น หรือเลือกหมวดหมู่อื่น</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Custom Footer --}}
    @if($layoutSettings->show_footer)
        <footer class="border-t border-gray-200 dark:border-gray-700 mt-12" style="background-color: {{ $layoutSettings->footer_bg_color ?? '#f9fafb' }}">
            <div class="container mx-auto px-4 py-12">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    {{-- Store Info --}}
                    <div>
                        <h3 class="font-bold text-xl mb-4" style="color: var(--store-primary)">{{ $store->store_name }}</h3>
                        <p class="text-gray-600 dark:text-gray-400">{{ $store->store_description }}</p>
                    </div>

                    {{-- Contact Info --}}
                    @if($layoutSettings->show_contact_info)
                        <div>
                            <h3 class="font-bold text-lg mb-4 text-gray-800 dark:text-gray-200">ติดต่อเรา</h3>
                            <ul class="space-y-2 text-gray-600 dark:text-gray-400">
                                @if($store->store_email)
                                    <li class="flex items-center gap-2">
                                        <span>📧</span>
                                        {{ $store->store_email }}
                                    </li>
                                @endif
                                @if($store->store_phone)
                                    <li class="flex items-center gap-2">
                                        <span>📞</span>
                                        {{ $store->store_phone }}
                                    </li>
                                @endif
                                @if($store->store_address)
                                    <li class="flex items-center gap-2">
                                        <span>📍</span>
                                        {{ $store->store_address }}
                                    </li>
                                @endif
                            </ul>
                        </div>
                    @endif

                    {{-- Social Links --}}
                    @if($layoutSettings->show_social_links && $layoutSettings->social_links)
                        <div>
                            <h3 class="font-bold text-lg mb-4 text-gray-800 dark:text-gray-200">ติดตามเรา</h3>
                            <div class="flex gap-3">
                                @if(!empty($layoutSettings->social_links['facebook']))
                                    <a href="{{ $layoutSettings->social_links['facebook'] }}" target="_blank"
                                       class="w-10 h-10 bg-blue-600 text-white rounded-full flex items-center justify-center hover:scale-110 transition">📘</a>
                                @endif
                                @if(!empty($layoutSettings->social_links['line']))
                                    <a href="https://line.me/ti/p/{{ ltrim($layoutSettings->social_links['line'], '@') }}" target="_blank"
                                       class="w-10 h-10 bg-green-500 text-white rounded-full flex items-center justify-center hover:scale-110 transition">💚</a>
                                @endif
                                @if(!empty($layoutSettings->social_links['instagram']))
                                    <a href="{{ $layoutSettings->social_links['instagram'] }}" target="_blank"
                                       class="w-10 h-10 bg-gradient-to-br from-purple-600 to-pink-500 text-white rounded-full flex items-center justify-center hover:scale-110 transition">📸</a>
                                @endif
                                @if(!empty($layoutSettings->social_links['tiktok']))
                                    <a href="{{ $layoutSettings->social_links['tiktok'] }}" target="_blank"
                                       class="w-10 h-10 bg-black text-white rounded-full flex items-center justify-center hover:scale-110 transition">🎵</a>
                                @endif
                                @if(!empty($layoutSettings->social_links['youtube']))
                                    <a href="{{ $layoutSettings->social_links['youtube'] }}" target="_blank"
                                       class="w-10 h-10 bg-red-600 text-white rounded-full flex items-center justify-center hover:scale-110 transition">📺</a>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Custom Footer Content --}}
                @if($layoutSettings->footer_content)
                    <div class="mt-8 pt-8 border-t border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400">
                        {!! $layoutSettings->footer_content !!}
                    </div>
                @endif

                {{-- Copyright --}}
                <div class="mt-8 pt-8 border-t border-gray-200 dark:border-gray-700 text-center text-gray-500 text-sm">
                    <p>© {{ date('Y') }} {{ $store->store_name }}. สงวนลิขสิทธิ์.</p>
                    <p class="mt-1">Powered by <span style="color: var(--store-primary)" class="font-semibold">TP-Affiliate</span></p>
                </div>
            </div>
        </footer>
    @endif
</div>
@endsection

@push('scripts')
{{-- Swiper JS for Slider --}}
@if($layoutSettings->slider_enabled && !empty($layoutSettings->slider_images))
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        new Swiper('#store-slider', {
            loop: true,
            autoplay: {
                delay: {{ $layoutSettings->slider_autoplay_speed ?? 5000 }},
                disableOnInteraction: false,
            },
            effect: '{{ $layoutSettings->slider_effect ?? 'slide' }}',
            @if($layoutSettings->slider_show_arrows)
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
            @endif
            @if($layoutSettings->slider_show_dots)
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
            @endif
        });
    });
</script>
@endif

{{-- Custom JS --}}
@if($layoutSettings->custom_js)
<script>
{!! $layoutSettings->custom_js !!}
</script>
@endif
@endpush
