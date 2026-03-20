{{--
    หน้ารายละเอียดสินค้า — ใช้ธีมร้านค้า (เอกเทศจากธีมระบบ)
    ใช้ CSS Variables + layout_classes ของร้านค้า เพื่อให้ตรงกับ storefront ทุกธีม
--}}
@extends('layouts.storefront')

@section('title', $product->name . ' - ' . $store->store_name)

@section('meta')
<meta name="description" content="{{ $product->short_description ?? Str::limit(strip_tags($product->description), 160) }}">
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

    .store-bg { background-color: var(--store-bg); }
    .store-primary-text { color: var(--store-primary); }
    .store-primary-bg { background-color: var(--store-primary); }
    .store-button {
        background: linear-gradient(135deg, var(--store-primary), var(--store-secondary));
    }
    .store-button:hover { filter: brightness(1.1); }
    .store-accent-bg { background-color: var(--store-accent); }

    .store-price-gradient {
        background: linear-gradient(135deg, var(--store-primary), var(--store-secondary));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .store-price-box {
        background: linear-gradient(135deg,
            color-mix(in srgb, var(--store-primary) 10%, white),
            color-mix(in srgb, var(--store-secondary) 10%, white)
        );
        border-color: color-mix(in srgb, var(--store-primary) 30%, white);
    }

    .store-tab-active {
        border-color: var(--store-primary);
        color: var(--store-primary);
        background: color-mix(in srgb, var(--store-primary) 8%, white);
    }

    .store-thumbnail-active {
        border-color: var(--store-primary) !important;
    }

    .store-badge-featured {
        background: linear-gradient(135deg, var(--store-accent), color-mix(in srgb, var(--store-accent) 80%, #ff6600));
    }
</style>

@if($layoutSettings->custom_css)
<style>{!! $layoutSettings->custom_css !!}</style>
@endif
@endpush

@php
    $lc = $layoutSettings->layout_classes;
@endphp

@section('content')
<div class="min-h-screen" style="background-color: var(--store-bg)">

    {{-- Store Mini Header — แสดงชื่อร้านด้านบน --}}
    <div class="text-white py-3" style="background: linear-gradient(135deg, var(--store-primary), var(--store-secondary))">
        <div class="{{ $lc['container'] }}">
            <div class="flex items-center gap-3">
                @if($store->store_logo)
                    <img src="{{ $store->logo_url }}" alt="{{ $store->store_name }}" class="w-8 h-8 rounded-full object-contain bg-white/20 p-0.5">
                @else
                    <span class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center text-sm">🏪</span>
                @endif
                <a href="{{ route('store.show', $store->store_slug) }}" class="font-bold text-white hover:text-white/80 transition">
                    {{ $store->store_name }}
                </a>
                <span class="text-white/60 text-sm">›</span>
                <span class="text-white/80 text-sm line-clamp-1">{{ Str::limit($product->name, 50) }}</span>
            </div>
        </div>
    </div>

    <div class="{{ $lc['container'] }} py-6">

        {{-- Breadcrumb --}}
        <nav class="mb-6" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-2 text-sm flex-wrap">
                <li>
                    <a href="{{ route('store.show', $store->store_slug) }}" class="text-gray-500 dark:text-gray-400 hover:store-primary-text transition flex items-center gap-1">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
                        </svg>
                        {{ $store->store_name }}
                    </a>
                </li>
                @if($product->category)
                <li><span class="text-gray-400">/</span></li>
                <li>
                    <a href="{{ route('store.show', ['slug' => $store->store_slug, 'category' => $product->category->slug]) }}"
                       class="text-gray-500 dark:text-gray-400 hover:store-primary-text transition">
                        {{ $product->category->name }}
                    </a>
                </li>
                @endif
                <li><span class="text-gray-400">/</span></li>
                <li class="text-gray-700 dark:text-gray-200 font-medium">{{ Str::limit($product->name, 50) }}</li>
            </ol>
        </nav>

        {{-- Main Product Section --}}
        <div class="bg-white dark:bg-gray-800 {{ $lc['border_radius'] }} shadow-xl overflow-hidden mb-8 border border-gray-100 dark:border-gray-700">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 p-6 lg:p-10">

                {{-- Product Images Gallery --}}
                <div class="space-y-4" x-data="{ mainImage: '{{ $product->main_image_url ?? '' }}' }">
                    {{-- Main Image --}}
                    <div class="relative aspect-square bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800 {{ $lc['border_radius'] }} overflow-hidden group">
                        @if($product->main_image_url)
                            <img :src="mainImage"
                                 alt="{{ $product->name }}"
                                 class="w-full h-full object-cover cursor-zoom-in transition-transform duration-500">
                        @else
                            <div class="w-full h-full flex items-center justify-center">
                                <svg class="w-32 h-32 text-gray-300 dark:text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                        @endif

                        {{-- Badges --}}
                        <div class="absolute top-4 left-4 flex flex-col gap-2 z-10">
                            @if($product->is_featured)
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 store-badge-featured text-white text-sm font-bold {{ $lc['border_radius'] }} shadow-lg">
                                    ⭐ สินค้าแนะนำ
                                </span>
                            @endif
                            @if($product->compare_at_price && $product->compare_at_price > $product->price)
                                @php $discount = round((($product->compare_at_price - $product->price) / $product->compare_at_price) * 100); @endphp
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 store-accent-bg text-white text-sm font-bold {{ $lc['border_radius'] }} shadow-lg">
                                    ลด {{ $discount }}%
                                </span>
                            @endif
                        </div>

                        {{-- Stock Status Overlay --}}
                        @if($product->stock_status === 'out_of_stock')
                            <div class="absolute inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center z-20">
                                <span class="inline-block px-6 py-3 bg-gray-900 text-white text-xl font-bold {{ $lc['border_radius'] }} shadow-2xl">
                                    สินค้าหมด
                                </span>
                            </div>
                        @elseif($product->track_inventory && $product->stock_quantity < ($product->low_stock_threshold ?? 5))
                            <div class="absolute bottom-4 left-4 right-4 z-10">
                                <div class="store-accent-bg text-white text-sm font-bold py-3 px-4 {{ $lc['border_radius'] }} text-center shadow-lg">
                                    เหลือเพียง {{ $product->stock_quantity }} ชิ้น - รีบสั่งซื้อเลย!
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Thumbnail Gallery --}}
                    @if($product->images && $product->images->count() > 0)
                    <div class="grid grid-cols-5 gap-3">
                        @if($product->main_image_url)
                        <button @click="mainImage = '{{ $product->main_image_url }}'"
                                :class="mainImage === '{{ $product->main_image_url }}' ? 'store-thumbnail-active' : 'border-gray-300 dark:border-gray-600'"
                                class="aspect-square {{ $lc['border_radius'] }} overflow-hidden border-2 hover:opacity-80 transition-all">
                            <img src="{{ $product->main_image_url }}" alt="Main" class="w-full h-full object-cover">
                        </button>
                        @endif
                        @foreach($product->images->take(4) as $image)
                        <button @click="mainImage = '{{ $image->url }}'"
                                :class="mainImage === '{{ $image->url }}' ? 'store-thumbnail-active' : 'border-gray-300 dark:border-gray-600'"
                                class="aspect-square {{ $lc['border_radius'] }} overflow-hidden border-2 hover:opacity-80 transition-all">
                            <img src="{{ $image->url }}" alt="Product Image" class="w-full h-full object-cover">
                        </button>
                        @endforeach
                    </div>
                    @endif
                </div>

                {{-- Product Info --}}
                <div class="space-y-6">

                    {{-- Category & Brand --}}
                    <div class="flex items-center gap-3 flex-wrap">
                        @if($product->category)
                            <a href="{{ route('store.show', ['slug' => $store->store_slug, 'category' => $product->category->slug]) }}"
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-bold {{ $lc['border_radius'] }} border transition-all"
                               style="background: color-mix(in srgb, var(--store-primary) 8%, white); color: var(--store-primary); border-color: color-mix(in srgb, var(--store-primary) 30%, white);">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/>
                                </svg>
                                {{ $product->category->name }}
                            </a>
                        @endif
                        @if($product->brand)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-semibold {{ $lc['border_radius'] }}">
                                {{ $product->brand }}
                            </span>
                        @endif
                    </div>

                    {{-- Tags --}}
                    @if($product->tags && count($product->tags) > 0)
                    <div class="flex flex-wrap gap-2">
                        @foreach($product->tags as $tag)
                            <a href="{{ route('store.show', ['slug' => $store->store_slug, 'search' => $tag]) }}"
                               class="inline-flex items-center gap-1 px-3 py-1.5 bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-sm rounded-full border border-gray-200 dark:border-gray-600 hover:border-current transition-all"
                               style="--tw-border-opacity: 1;">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M17.707 9.293a1 1 0 010 1.414l-7 7a1 1 0 01-1.414 0l-7-7A.997.997 0 012 10V5a3 3 0 013-3h5c.256 0 .512.098.707.293l7 7zM5 6a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                                </svg>
                                {{ $tag }}
                            </a>
                        @endforeach
                    </div>
                    @endif

                    {{-- Product Name --}}
                    <h1 class="text-3xl lg:text-4xl font-black leading-tight" style="color: var(--store-text)">
                        {{ $product->name }}
                    </h1>

                    {{-- Rating & Sales Stats --}}
                    <div class="flex items-center gap-4 flex-wrap pb-6 border-b-2 border-gray-100 dark:border-gray-700">
                        @php
                            $rating = $product->rating_average ?? 0;
                            $fullStars = floor($rating);
                            $hasHalfStar = ($rating - $fullStars) >= 0.5;
                        @endphp
                        <div class="flex items-center gap-2 bg-amber-50 dark:bg-amber-900/30 px-4 py-2 {{ $lc['border_radius'] }} border border-amber-100 dark:border-amber-800">
                            <div class="flex">
                                @for($i = 0; $i < 5; $i++)
                                    <svg class="w-5 h-5 {{ $i < $fullStars ? 'text-amber-400' : ($i == $fullStars && $hasHalfStar ? 'text-amber-400' : 'text-gray-300 dark:text-gray-600') }}" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                @endfor
                            </div>
                            <span class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ number_format($rating, 1) }}</span>
                            <span class="text-gray-600 dark:text-gray-400">({{ number_format($product->rating_count) }})</span>
                        </div>
                        <div class="h-6 w-px bg-gray-300 dark:bg-gray-600"></div>
                        <div class="flex items-center gap-2">
                            <span class="text-gray-600 dark:text-gray-400">ขายแล้ว</span>
                            <span class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($product->sales_count) }}</span>
                        </div>
                        <div class="h-6 w-px bg-gray-300 dark:bg-gray-600"></div>
                        <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>{{ number_format($product->view_count) }} ครั้ง</span>
                        </div>
                    </div>

                    {{-- Price Section --}}
                    <div class="store-price-box p-6 {{ $lc['border_radius'] }} border-2">
                        @if($product->compare_at_price && $product->compare_at_price > $product->price)
                            <div class="space-y-2">
                                <div class="text-sm font-semibold text-gray-600 dark:text-gray-400">ราคาปกติ</div>
                                <div class="text-2xl text-gray-400 line-through font-bold">
                                    ฿{{ number_format($product->compare_at_price, 2) }}
                                </div>
                                <span class="inline-block px-3 py-1 store-accent-bg text-white font-bold {{ $lc['border_radius'] }} text-sm">
                                    ประหยัด ฿{{ number_format($product->compare_at_price - $product->price, 2) }}
                                </span>
                            </div>
                            <div class="mt-4 pt-4 border-t-2" style="border-color: color-mix(in srgb, var(--store-primary) 25%, white)">
                                <div class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">ราคาพิเศษ</div>
                                <div class="text-5xl font-black store-price-gradient">
                                    ฿{{ number_format($product->price, 2) }}
                                </div>
                            </div>
                        @else
                            <div>
                                <div class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">ราคา</div>
                                <div class="text-5xl font-black store-price-gradient">
                                    ฿{{ number_format($product->price, 2) }}
                                </div>
                            </div>
                        @endif

                        {{-- ข้อมูลค่าจัดส่ง --}}
                        @if(isset($shippingInfo))
                            <div class="mt-4 flex items-center gap-2 font-semibold px-4 py-2.5 {{ $lc['border_radius'] }} border {{ $shippingInfo['badge_class'] }}">
                                <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/>
                                    <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z"/>
                                </svg>
                                <div>
                                    <div class="text-sm">{{ $shippingInfo['label'] }}</div>
                                    <div class="text-xs font-normal opacity-80">{{ $shippingInfo['details'] }}</div>
                                </div>
                            </div>
                        @endif

                        {{-- Cashback --}}
                        @if(isset($cashbackInfo) && $cashbackInfo['cashback'] > 0)
                            <div class="mt-4 flex items-center gap-2 text-amber-700 dark:text-amber-300 font-bold bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-900/30 dark:to-orange-900/30 px-4 py-3 {{ $lc['border_radius'] }} border-2 border-amber-300 dark:border-amber-700 shadow-md">
                                <svg class="w-6 h-6 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/>
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/>
                                </svg>
                                <div>
                                    <div class="text-lg">รับ Cashback คืน <span class="text-xl">฿{{ number_format($cashbackInfo['cashback'], 2) }}</span></div>
                                    @if($cashbackInfo['setting'])
                                        <div class="text-xs text-amber-600 dark:text-amber-400 mt-0.5">
                                            @if($cashbackInfo['setting']->value_type === 'percentage')
                                                ({{ number_format($cashbackInfo['setting']->value, 2) }}% ของราคาสินค้า)
                                            @else
                                                (Cashback คงที่)
                                            @endif
                                            @if($cashbackInfo['type'] === 'product')
                                                <span class="inline-flex items-center px-2 py-0.5 bg-amber-200 dark:bg-amber-800 text-amber-800 dark:text-amber-200 rounded text-xs ml-1">โปรโมชันพิเศษ</span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Short Description --}}
                    @if($product->short_description)
                    <div class="prose prose-sm max-w-none text-gray-700 dark:text-gray-300 leading-relaxed">
                        {!! nl2br(e($product->short_description)) !!}
                    </div>
                    @endif

                    {{-- Stock Status --}}
                    <div class="flex items-center gap-3 text-sm font-semibold flex-wrap">
                        @if($product->stock_status === 'in_stock')
                            <div class="flex items-center gap-2 text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/30 px-4 py-2 {{ $lc['border_radius'] }} border border-emerald-200 dark:border-emerald-800">
                                <span class="relative flex h-3 w-3">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                                </span>
                                มีสินค้าพร้อมส่ง
                            </div>
                        @else
                            <div class="flex items-center gap-2 text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/30 px-4 py-2 {{ $lc['border_radius'] }} border border-red-200 dark:border-red-800">
                                <span class="w-3 h-3 bg-red-600 rounded-full"></span>
                                สินค้าหมด
                            </div>
                        @endif
                        @if($product->sku)
                        <span class="text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-4 py-2 {{ $lc['border_radius'] }}">
                            SKU: <span class="font-mono font-bold text-gray-900 dark:text-gray-100">{{ $product->sku }}</span>
                        </span>
                        @endif
                    </div>

                    {{-- Shipping Info Card --}}
                    @if(isset($shippingInfo) && $shippingInfo['method'] !== 'virtual')
                    <div class="bg-gray-50 dark:bg-gray-800 {{ $lc['border_radius'] }} p-5 border border-gray-200 dark:border-gray-700 space-y-3">
                        <h3 class="flex items-center gap-2 text-base font-bold text-gray-900 dark:text-white">
                            <svg class="w-5 h-5" style="color: var(--store-primary)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a2 2 0 104 0m-4 0a2 2 0 11-4 0"/>
                            </svg>
                            ข้อมูลการจัดส่ง
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="flex items-start gap-3 bg-white dark:bg-gray-700 {{ $lc['border_radius'] }} p-3 border border-gray-100 dark:border-gray-600">
                                <div class="flex-shrink-0 w-10 h-10 rounded-lg flex items-center justify-center {{ $shippingInfo['is_free'] ? 'bg-emerald-100 dark:bg-emerald-900/50' : 'bg-sky-100 dark:bg-sky-900/50' }}">
                                    <svg class="w-5 h-5 {{ $shippingInfo['is_free'] ? 'text-emerald-600 dark:text-emerald-400' : 'text-sky-600 dark:text-sky-400' }}" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-sm font-bold text-gray-900 dark:text-white">{{ $shippingInfo['label'] }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $shippingInfo['details'] }}</div>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 bg-white dark:bg-gray-700 {{ $lc['border_radius'] }} p-3 border border-gray-100 dark:border-gray-600">
                                <div class="flex-shrink-0 w-10 h-10 rounded-lg flex items-center justify-center" style="background: color-mix(in srgb, var(--store-primary) 15%, white)">
                                    <svg class="w-5 h-5" style="color: var(--store-primary)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-sm font-bold text-gray-900 dark:text-white">
                                        @switch($shippingInfo['method'])
                                            @case('free') จัดส่งฟรีโดยผู้ขาย @break
                                            @case('flat_rate') อัตราค่าส่งเหมาจ่าย @break
                                            @case('weight_based') คำนวณตามน้ำหนัก @break
                                            @default ค่าจัดส่งมาตรฐาน
                                        @endswitch
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">จัดส่งภายใน 1-3 วันทำการ</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- Action Buttons --}}
                    <div class="space-y-4 pt-6">
                        @if($product->stock_status === 'in_stock')
                        {{-- Quantity Selector --}}
                        <div class="flex items-center gap-4">
                            <span class="text-gray-900 dark:text-gray-100 font-bold text-lg">จำนวน:</span>
                            <div class="flex items-center border-2 border-gray-300 dark:border-gray-600 {{ $lc['border_radius'] }} overflow-hidden bg-white dark:bg-gray-700">
                                <button onclick="decrementQty()" class="px-5 py-3 hover:bg-gray-100 dark:hover:bg-gray-600 font-bold text-lg transition-colors text-gray-900 dark:text-gray-100">−</button>
                                <input type="number" id="quantity" value="1" min="1" max="{{ $product->stock_quantity ?? 999 }}"
                                       class="w-20 text-center border-x-2 border-gray-300 dark:border-gray-600 py-3 font-bold text-lg focus:outline-none bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                                <button onclick="incrementQty()" class="px-5 py-3 hover:bg-gray-100 dark:hover:bg-gray-600 font-bold text-lg transition-colors text-gray-900 dark:text-gray-100">+</button>
                            </div>
                        </div>

                        {{-- Add to Cart --}}
                        <div class="flex gap-3">
                            <button onclick="addToCart()"
                                    class="flex-1 px-8 py-4 store-button text-white font-bold text-lg {{ $lc['button'] }} shadow-lg hover:shadow-2xl transform hover:scale-105 transition-all duration-200 flex items-center justify-center gap-2">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                เพิ่มลงตะกร้า
                            </button>
                        </div>

                        {{-- Buy Now --}}
                        <button onclick="buyNow()"
                                class="w-full px-8 py-4 text-white font-bold text-lg {{ $lc['button'] }} shadow-lg hover:shadow-2xl transform hover:scale-105 transition-all duration-200 flex items-center justify-center gap-2"
                                style="background: linear-gradient(135deg, var(--store-accent), color-mix(in srgb, var(--store-accent) 80%, #ff4400))">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            ซื้อทันที
                        </button>
                        @else
                        <button disabled class="w-full px-8 py-4 bg-gray-300 dark:bg-gray-600 text-gray-500 dark:text-gray-400 font-bold text-lg {{ $lc['button'] }} cursor-not-allowed flex items-center justify-center gap-2">
                            สินค้าหมด
                        </button>
                        @endif

                        {{-- Wishlist & Share --}}
                        <div class="grid grid-cols-2 gap-3">
                            <button class="flex items-center justify-center gap-2 px-6 py-3 border-2 border-gray-300 dark:border-gray-600 hover:border-pink-500 hover:bg-pink-50 dark:hover:bg-pink-900/30 hover:text-pink-600 text-gray-700 dark:text-gray-300 font-bold {{ $lc['border_radius'] }} transition-all">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                </svg>
                                บันทึก
                            </button>
                            <button onclick="shareProduct()"
                                    class="flex items-center justify-center gap-2 px-6 py-3 border-2 border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 font-bold {{ $lc['border_radius'] }} transition-all"
                                    style="--hover-color: var(--store-primary)">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                                </svg>
                                แชร์
                            </button>
                        </div>
                    </div>

                    {{-- Trust Badges --}}
                    <div class="grid grid-cols-3 gap-3 pt-6">
                        <div class="flex flex-col items-center gap-2 p-3 {{ $lc['border_radius'] }} border border-gray-100 dark:border-gray-700" style="background: color-mix(in srgb, var(--store-primary) 5%, white)">
                            <svg class="w-8 h-8" style="color: var(--store-primary)" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-xs font-bold text-gray-900 dark:text-gray-100 text-center">ของแท้ 100%</span>
                        </div>
                        <div class="flex flex-col items-center gap-2 p-3 {{ $lc['border_radius'] }} border border-gray-100 dark:border-gray-700 bg-emerald-50 dark:bg-emerald-900/20">
                            <svg class="w-8 h-8 text-emerald-600 dark:text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/>
                                <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z"/>
                            </svg>
                            <span class="text-xs font-bold text-gray-900 dark:text-gray-100 text-center">จัดส่งรวดเร็ว</span>
                        </div>
                        <div class="flex flex-col items-center gap-2 p-3 {{ $lc['border_radius'] }} border border-gray-100 dark:border-gray-700" style="background: color-mix(in srgb, var(--store-secondary) 5%, white)">
                            <svg class="w-8 h-8" style="color: var(--store-secondary)" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-xs font-bold text-gray-900 dark:text-gray-100 text-center">คืนเงิน 100%</span>
                        </div>
                    </div>

                    {{-- Seller/Store Info --}}
                    <div class="bg-gray-50 dark:bg-gray-800 p-5 {{ $lc['border_radius'] }} border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                @if($store->store_logo)
                                    <div class="w-12 h-12 {{ $lc['border_radius'] }} overflow-hidden shadow-lg flex-shrink-0">
                                        <img src="{{ $store->logo_url }}" alt="{{ $store->store_name }}" class="w-full h-full object-contain">
                                    </div>
                                @else
                                    <div class="w-12 h-12 store-button {{ $lc['border_radius'] }} flex items-center justify-center text-white font-bold text-xl flex-shrink-0">
                                        {{ mb_substr($store->store_name, 0, 1) }}
                                    </div>
                                @endif
                                <div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 font-semibold">ขายโดย</div>
                                    <div class="font-bold text-gray-900 dark:text-gray-100 text-lg">{{ $store->store_name }}</div>
                                </div>
                            </div>
                            <a href="{{ route('store.show', $store->store_slug) }}"
                               class="px-4 py-2 bg-white dark:bg-gray-700 border-2 border-gray-300 dark:border-gray-600 font-bold {{ $lc['border_radius'] }} transition-all shadow-sm hover:shadow-md"
                               style="color: var(--store-primary);"
                               onmouseover="this.style.borderColor='var(--store-primary)'" onmouseout="this.style.borderColor=''">
                                ดูร้านค้า
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Product Details Tabs --}}
        <div class="bg-white dark:bg-gray-800 {{ $lc['border_radius'] }} shadow-xl overflow-hidden mb-8 border border-gray-100 dark:border-gray-700" x-data="{ tab: 'description' }">
            <div class="border-b-2 border-gray-100 dark:border-gray-700">
                <div class="flex overflow-x-auto">
                    <button @click="tab = 'description'"
                            :class="tab === 'description' ? 'store-tab-active' : 'border-transparent text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700'"
                            class="px-8 py-4 font-bold border-b-4 whitespace-nowrap transition-all flex items-center gap-2">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                        </svg>
                        รายละเอียดสินค้า
                    </button>
                    <button @click="tab = 'reviews'"
                            :class="tab === 'reviews' ? 'store-tab-active' : 'border-transparent text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700'"
                            class="px-8 py-4 font-bold border-b-4 whitespace-nowrap transition-all flex items-center gap-2">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        รีวิวจากผู้ซื้อ ({{ $product->rating_count }})
                    </button>
                    <button @click="tab = 'shipping'"
                            :class="tab === 'shipping' ? 'store-tab-active' : 'border-transparent text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700'"
                            class="px-8 py-4 font-bold border-b-4 whitespace-nowrap transition-all flex items-center gap-2">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/>
                            <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z"/>
                        </svg>
                        การจัดส่ง
                    </button>
                </div>
            </div>

            <div class="p-8">
                {{-- Description Tab --}}
                <div x-show="tab === 'description'" class="prose prose-lg max-w-none dark:prose-invert">
                    @if($product->description)
                        {!! strip_tags($product->description, '<p><br><strong><em><ul><ol><li><h1><h2><h3><h4><h5><h6><a><img><blockquote><code><pre><table><thead><tbody><tr><th><td><hr><del><sup><sub><span><div>') !!}
                    @else
                        <p class="text-gray-500 dark:text-gray-400">ไม่มีรายละเอียดสินค้า</p>
                    @endif

                    @if($product->brand || $product->weight || $product->dimensions)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-8">
                        @if($product->brand)
                        <div class="p-5 {{ $lc['border_radius'] }} border" style="background: color-mix(in srgb, var(--store-primary) 5%, white); border-color: color-mix(in srgb, var(--store-primary) 20%, white)">
                            <h4 class="font-bold text-gray-900 dark:text-gray-100 mb-2 flex items-center gap-2">
                                <svg class="w-5 h-5" style="color: var(--store-primary)" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                ยี่ห้อ
                            </h4>
                            <p class="text-gray-700 dark:text-gray-300 font-semibold text-lg">{{ $product->brand }}</p>
                        </div>
                        @endif
                        @if($product->weight || $product->dimensions)
                        <div class="p-5 bg-emerald-50 dark:bg-emerald-900/20 {{ $lc['border_radius'] }} border border-emerald-200 dark:border-emerald-800">
                            <h4 class="font-bold text-gray-900 dark:text-gray-100 mb-2 flex items-center gap-2">
                                <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                </svg>
                                ข้อมูลการจัดส่ง
                            </h4>
                            @if($product->weight)
                            <p class="text-gray-700 dark:text-gray-300">น้ำหนัก: <span class="font-semibold">{{ $product->weight }} กก.</span></p>
                            @endif
                            @if($product->dimensions)
                            <p class="text-gray-700 dark:text-gray-300">ขนาด: <span class="font-semibold">{{ $product->dimensions }}</span></p>
                            @endif
                        </div>
                        @endif
                    </div>
                    @endif
                </div>

                {{-- Reviews Tab --}}
                <div x-show="tab === 'reviews'" class="space-y-6">
                    @if(class_exists(\Illuminate\View\ComponentAttributeBag::class))
                        <x-ecommerce.review-summary :product="$product" />
                    @endif

                    @if($product->approvedReviews && $product->approvedReviews->count() > 0)
                        @foreach($product->approvedReviews as $review)
                        <div class="border-b-2 border-gray-100 dark:border-gray-700 pb-6 last:border-0">
                            <div class="flex items-start gap-4">
                                <div class="w-14 h-14 store-button {{ $lc['border_radius'] }} flex items-center justify-center text-white font-bold text-xl flex-shrink-0">
                                    {{ mb_substr($review->user->name ?? 'U', 0, 1) }}
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2 flex-wrap">
                                        <span class="font-bold text-gray-900 dark:text-gray-100 text-lg">{{ $review->user->name ?? 'ผู้ใช้' }}</span>
                                        <div class="flex">
                                            @for($i = 0; $i < 5; $i++)
                                                <svg class="w-4 h-4 {{ $i < $review->rating ? 'text-amber-400' : 'text-gray-300 dark:text-gray-600' }}" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                </svg>
                                            @endfor
                                        </div>
                                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ $review->created_at->diffForHumans() }}</span>
                                        @if($review->is_verified_purchase)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-400 text-xs font-semibold rounded-full">
                                            ✓ ซื้อจริง
                                        </span>
                                        @endif
                                    </div>
                                    @if($review->title)
                                    <h4 class="font-semibold text-gray-800 dark:text-gray-200 mb-1">{{ $review->title }}</h4>
                                    @endif
                                    @if($review->comment)
                                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed">{{ $review->comment }}</p>
                                    @endif

                                    @if($review->images && count($review->images) > 0)
                                    <div class="flex flex-wrap gap-2 mt-3">
                                        @foreach($review->images as $image)
                                        <img src="{{ asset('storage/' . $image) }}" alt="รีวิว"
                                             class="w-20 h-20 object-cover {{ $lc['border_radius'] }} border border-gray-200 dark:border-gray-600 cursor-pointer hover:opacity-80 transition-opacity">
                                        @endforeach
                                    </div>
                                    @endif

                                    @if($review->seller_response)
                                    <div class="mt-3 ml-4 pl-4 border-l-2 {{ $lc['border_radius'] }} p-3" style="border-color: var(--store-accent); background: color-mix(in srgb, var(--store-accent) 5%, white)">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="text-sm font-bold" style="color: var(--store-accent)">ตอบกลับจากร้านค้า</span>
                                            @if($review->seller_responded_at)
                                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $review->seller_responded_at->diffForHumans() }}</span>
                                            @endif
                                        </div>
                                        <p class="text-sm text-gray-700 dark:text-gray-300">{{ $review->seller_response }}</p>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    @else
                        <div class="text-center py-16">
                            <div class="inline-flex items-center justify-center w-20 h-20 bg-gray-100 dark:bg-gray-700 rounded-full mb-4">
                                <svg class="w-10 h-10 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                                </svg>
                            </div>
                            <p class="text-gray-500 dark:text-gray-400 text-lg font-semibold">ยังไม่มีรีวิวสำหรับสินค้านี้</p>
                        </div>
                    @endif
                </div>

                {{-- Shipping Tab --}}
                <div x-show="tab === 'shipping'" class="space-y-4">
                    <div class="flex items-start gap-5 p-6 {{ $lc['border_radius'] }} border" style="background: color-mix(in srgb, var(--store-primary) 5%, white); border-color: color-mix(in srgb, var(--store-primary) 20%, white)">
                        <div class="w-14 h-14 store-button {{ $lc['border_radius'] }} flex items-center justify-center flex-shrink-0">
                            <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/>
                                <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z"/>
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900 dark:text-gray-100 mb-2 text-lg">จัดส่งฟรี</h4>
                            <p class="text-gray-700 dark:text-gray-300 leading-relaxed">สำหรับคำสั่งซื้อที่มีมูลค่าตั้งแต่ 500 บาทขึ้นไป</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-5 p-6 bg-emerald-50 dark:bg-emerald-900/20 {{ $lc['border_radius'] }} border border-emerald-200 dark:border-emerald-800">
                        <div class="w-14 h-14 bg-emerald-600 {{ $lc['border_radius'] }} flex items-center justify-center flex-shrink-0">
                            <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z"/>
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900 dark:text-gray-100 mb-2 text-lg">จัดส่งรวดเร็ว</h4>
                            <p class="text-gray-700 dark:text-gray-300 leading-relaxed">ส่งสินค้าภายใน 1-2 วันทำการหลังจากชำระเงิน</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-5 p-6 bg-amber-50 dark:bg-amber-900/20 {{ $lc['border_radius'] }} border border-amber-200 dark:border-amber-800">
                        <div class="w-14 h-14 {{ $lc['border_radius'] }} flex items-center justify-center flex-shrink-0" style="background: var(--store-accent)">
                            <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900 dark:text-gray-100 mb-2 text-lg">ติดตามพัสดุได้</h4>
                            <p class="text-gray-700 dark:text-gray-300 leading-relaxed">ตรวจสอบสถานะการจัดส่งได้แบบเรียลไทม์ผ่านระบบของเรา</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Related Products (จากร้านเดียวกัน) --}}
        @if($relatedProducts && $relatedProducts->count() > 0)
        @php
            $productCardStyle = $layoutSettings->product_card_style ?? 'default';
            $productsPerRow = $layoutSettings->products_per_row ?? 4;
            $gridCols = match($productsPerRow) {
                2 => 'grid-cols-1 sm:grid-cols-2',
                3 => 'grid-cols-1 sm:grid-cols-2 md:grid-cols-3',
                5 => 'grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5',
                6 => 'grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6',
                default => 'grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4',
            };
        @endphp
        <div class="mb-8">
            <div class="flex items-center justify-between mb-6">
                <h2 class="{{ $lc['heading'] }} flex items-center gap-3" style="color: var(--store-primary)">
                    สินค้าที่เกี่ยวข้อง
                </h2>
            </div>
            <div class="grid {{ $gridCols }} gap-4 md:gap-6">
                @foreach($relatedProducts as $relatedProduct)
                    <a href="{{ route('store.product', ['storeSlug' => $store->store_slug, 'productSlug' => $relatedProduct->slug]) }}"
                       class="product-card-{{ $productCardStyle }} block group {{ $lc['card_hover'] }}">
                        <div class="aspect-square relative overflow-hidden">
                            @if($relatedProduct->primary_image_url ?? $relatedProduct->main_image_url ?? null)
                                <img src="{{ $relatedProduct->primary_image_url ?? $relatedProduct->main_image_url }}"
                                     alt="{{ $relatedProduct->name }}"
                                     class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                                     loading="lazy">
                            @else
                                <div class="w-full h-full bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 flex items-center justify-center text-6xl text-gray-300">📦</div>
                            @endif
                        </div>
                        <div class="p-4">
                            <h3 class="font-semibold text-gray-800 dark:text-gray-200 line-clamp-2 mb-2">{{ $relatedProduct->name }}</h3>
                            <div class="flex items-center gap-2">
                                <span class="text-lg font-bold" style="color: var(--store-primary)">฿{{ number_format($relatedProduct->price, 0) }}</span>
                                @if($relatedProduct->compare_at_price && $relatedProduct->compare_at_price > $relatedProduct->price)
                                    <span class="text-sm text-gray-400 line-through">฿{{ number_format($relatedProduct->compare_at_price, 0) }}</span>
                                @endif
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
        @endif

    </div>
</div>

{{-- JavaScript --}}
<script>
function incrementQty() {
    const input = document.getElementById('quantity');
    const max = parseInt(input.max);
    const current = parseInt(input.value);
    if (current < max) input.value = current + 1;
}

function decrementQty() {
    const input = document.getElementById('quantity');
    const min = parseInt(input.min);
    const current = parseInt(input.value);
    if (current > min) input.value = current - 1;
}

function addToCart() {
    const quantity = document.getElementById('quantity').value;

    @guest
    if (confirm('กรุณาเข้าสู่ระบบเพื่อเพิ่มสินค้าลงตะกร้า')) {
        window.location.href = '{{ route("login") }}';
    }
    return;
    @endguest

    const button = event.target.closest('button');
    const originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<svg class="animate-spin h-6 w-6 text-white mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';

    fetch('{{ route("cart.add") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            product_id: {{ $product->id }},
            quantity: parseInt(quantity),
            attributes: {}
        })
    })
    .then(response => {
        if (response.status === 419) { alert('Session หมดอายุ กรุณารีเฟรชหน้าเว็บ'); window.location.reload(); return; }
        if (response.status === 401) { window.location.href = '{{ route("login") }}'; return; }
        if (response.redirected) { window.location.href = response.url; return; }
        if (!response.ok) return response.json().then(data => { throw new Error(data.message || 'เกิดข้อผิดพลาด'); });
        return response.json();
    })
    .then(data => { if (data) window.location.href = '{{ route("cart.index") }}'; })
    .catch(error => {
        console.error('Error:', error);
        alert(error.message || 'เกิดข้อผิดพลาดในการเพิ่มสินค้าลงตะกร้า');
        button.disabled = false;
        button.innerHTML = originalText;
    });
}

function buyNow() {
    const quantity = document.getElementById('quantity').value;

    @guest
    if (confirm('กรุณาเข้าสู่ระบบเพื่อทำการสั่งซื้อ')) {
        window.location.href = '{{ route("login") }}';
    }
    return;
    @endguest

    const button = event.target.closest('button');
    const originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<svg class="animate-spin h-6 w-6 text-white mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';

    fetch('{{ route("cart.add") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            product_id: {{ $product->id }},
            quantity: parseInt(quantity),
            attributes: {}
        })
    })
    .then(response => {
        if (response.status === 419) { alert('Session หมดอายุ กรุณารีเฟรชหน้าเว็บ'); window.location.reload(); return; }
        if (response.status === 401) { window.location.href = '{{ route("login") }}'; return; }
        if (response.redirected) { window.location.href = response.url; return; }
        if (!response.ok) return response.json().then(data => { throw new Error(data.message || 'เกิดข้อผิดพลาด'); });
        return response.json();
    })
    .then(data => { if (data) window.location.href = '{{ route("checkout.index") }}'; })
    .catch(error => {
        console.error('Error:', error);
        alert(error.message || 'เกิดข้อผิดพลาดในการทำรายการ');
        button.disabled = false;
        button.innerHTML = originalText;
    });
}

function shareProduct() {
    if (navigator.share) {
        navigator.share({ title: '{{ $product->name }}', text: 'ดูสินค้านี้สิ!', url: window.location.href });
    } else {
        navigator.clipboard.writeText(window.location.href);
        alert('คัดลอกลิงก์แล้ว!');
    }
}
</script>
@endsection
