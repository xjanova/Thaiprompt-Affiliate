@extends('layouts.app')

@section('title', $store->store_name)

@section('meta')
<meta name="description" content="{{ $store->store_description ?? 'ร้านค้าออนไลน์' }}">
@endsection

@section('content')
<div class="min-h-screen bg-gray-50">
    {{-- Store Header --}}
    <div class="bg-gradient-to-r from-{{ $store->primary_color ?? 'indigo' }}-600 via-purple-600 to-pink-600 text-white">
        @if($store->store_banner)
            <div class="relative h-64 md:h-96 overflow-hidden">
                <img src="{{ $store->banner_url }}" alt="{{ $store->store_name }} Banner"
                     class="w-full h-full object-cover opacity-30">
                <div class="absolute inset-0 bg-gradient-to-b from-transparent to-black/50"></div>
            </div>
        @endif

        <div class="container mx-auto px-4 py-8 {{ $store->store_banner ? '-mt-32 relative z-10' : '' }}">
            <div class="flex flex-col md:flex-row items-center gap-6">
                {{-- Store Logo --}}
                @if($store->store_logo)
                    <img src="{{ $store->logo_url }}" alt="{{ $store->store_name }}"
                         class="w-32 h-32 md:w-40 md:h-40 rounded-2xl shadow-2xl border-4 border-white object-cover">
                @else
                    <div class="w-32 h-32 md:w-40 md:h-40 rounded-2xl bg-white/20 flex items-center justify-center text-5xl md:text-6xl shadow-2xl border-4 border-white">
                        🏪
                    </div>
                @endif

                {{-- Store Info --}}
                <div class="flex-1 text-center md:text-left">
                    <h1 class="text-3xl md:text-5xl font-bold mb-2">{{ $store->store_name }}</h1>
                    @if($store->store_description)
                        <p class="text-lg text-white/90 mb-4">{{ $store->store_description }}</p>
                    @endif

                    {{-- Stats --}}
                    <div class="flex flex-wrap gap-4 justify-center md:justify-start">
                        <div class="bg-white/20 px-4 py-2 rounded-lg backdrop-blur-sm">
                            <span class="text-2xl font-bold">{{ $stats['total_products'] }}</span>
                            <span class="text-sm ml-1">สินค้า</span>
                        </div>
                        <div class="bg-white/20 px-4 py-2 rounded-lg backdrop-blur-sm">
                            <span class="text-2xl font-bold">{{ $stats['total_sales'] }}</span>
                            <span class="text-sm ml-1">ยอดขาย</span>
                        </div>
                        @if($stats['rating_count'] > 0)
                            <div class="bg-white/20 px-4 py-2 rounded-lg backdrop-blur-sm">
                                <span class="text-2xl font-bold">{{ number_format($stats['rating'], 1) }}</span>
                                <span class="text-sm ml-1">⭐ ({{ $stats['rating_count'] }})</span>
                            </div>
                        @endif
                        @if($store->is_verified)
                            <div class="bg-green-500/80 px-4 py-2 rounded-lg backdrop-blur-sm">
                                <span class="text-sm font-semibold">✓ ยืนยันแล้ว</span>
                            </div>
                        @endif
                    </div>

                    {{-- Social Links --}}
                    @if($store->facebook_url || $store->line_oa_id || $store->instagram_url || $store->twitter_url || $store->tiktok_url)
                        <div class="flex gap-3 mt-4 justify-center md:justify-start">
                            @if($store->facebook_url)
                                <a href="{{ $store->facebook_url }}" target="_blank" class="w-10 h-10 bg-white/20 hover:bg-white/30 rounded-full flex items-center justify-center transition">
                                    <span class="text-xl">📘</span>
                                </a>
                            @endif
                            @if($store->line_oa_id)
                                <a href="https://line.me/R/ti/p/{{ ltrim($store->line_oa_id, '@') }}" target="_blank" class="w-10 h-10 bg-white/20 hover:bg-white/30 rounded-full flex items-center justify-center transition">
                                    <span class="text-xl">💚</span>
                                </a>
                            @endif
                            @if($store->instagram_url)
                                <a href="{{ $store->instagram_url }}" target="_blank" class="w-10 h-10 bg-white/20 hover:bg-white/30 rounded-full flex items-center justify-center transition">
                                    <span class="text-xl">📷</span>
                                </a>
                            @endif
                            @if($store->twitter_url)
                                <a href="{{ $store->twitter_url }}" target="_blank" class="w-10 h-10 bg-white/20 hover:bg-white/30 rounded-full flex items-center justify-center transition">
                                    <span class="text-xl">🐦</span>
                                </a>
                            @endif
                            @if($store->tiktok_url)
                                <a href="{{ $store->tiktok_url }}" target="_blank" class="w-10 h-10 bg-white/20 hover:bg-white/30 rounded-full flex items-center justify-center transition">
                                    <span class="text-xl">🎵</span>
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="container mx-auto px-4 py-8">
        <div class="flex flex-col lg:flex-row gap-6">
            {{-- Sidebar Filters --}}
            <aside class="lg:w-64 flex-shrink-0">
                <div class="bg-white rounded-2xl shadow-lg p-6 sticky top-4">
                    <h3 class="text-lg font-bold mb-4 text-gray-800">🔍 ค้นหา & กรอง</h3>

                    <form method="GET" action="{{ route('vendor.store.show', $store->store_slug) }}" class="space-y-4">
                        {{-- Search --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">ค้นหาสินค้า</label>
                            <input type="text" name="search" value="{{ request('search') }}"
                                   placeholder="ชื่อสินค้า..."
                                   class="w-full px-3 py-2 rounded-lg border-2 border-gray-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition">
                        </div>

                        {{-- Categories --}}
                        @if($categories->count() > 0)
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">หมวดหมู่</label>
                                <select name="category" class="w-full px-3 py-2 rounded-lg border-2 border-gray-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition">
                                    <option value="">ทั้งหมด</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->slug }}" {{ request('category') == $category->slug ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        {{-- Price Range --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">ราคา (฿)</label>
                            <div class="flex gap-2">
                                <input type="number" name="min_price" value="{{ request('min_price') }}"
                                       placeholder="ต่ำสุด"
                                       class="w-1/2 px-3 py-2 rounded-lg border-2 border-gray-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition">
                                <input type="number" name="max_price" value="{{ request('max_price') }}"
                                       placeholder="สูงสุด"
                                       class="w-1/2 px-3 py-2 rounded-lg border-2 border-gray-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition">
                            </div>
                        </div>

                        {{-- Sort --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">เรียงตาม</label>
                            <select name="sort" class="w-full px-3 py-2 rounded-lg border-2 border-gray-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition">
                                <option value="latest" {{ request('sort') == 'latest' ? 'selected' : '' }}>ล่าสุด</option>
                                <option value="popular" {{ request('sort') == 'popular' ? 'selected' : '' }}>ยอดนิยม</option>
                                <option value="price_low" {{ request('sort') == 'price_low' ? 'selected' : '' }}>ราคาต่ำ-สูง</option>
                                <option value="price_high" {{ request('sort') == 'price_high' ? 'selected' : '' }}>ราคาสูง-ต่ำ</option>
                                <option value="rating" {{ request('sort') == 'rating' ? 'selected' : '' }}>คะแนนสูงสุด</option>
                                <option value="name" {{ request('sort') == 'name' ? 'selected' : '' }}>ชื่อ A-Z</option>
                            </select>
                        </div>

                        {{-- Buttons --}}
                        <div class="flex gap-2">
                            <button type="submit" class="flex-1 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition">
                                ค้นหา
                            </button>
                            <a href="{{ route('vendor.store.show', $store->store_slug) }}"
                               class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold rounded-lg transition">
                                ล้าง
                            </a>
                        </div>
                    </form>
                </div>
            </aside>

            {{-- Products Grid --}}
            <main class="flex-1">
                @if($products->count() > 0)
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                        @foreach($products as $product)
                            <a href="{{ route('shop.show', $product->slug) }}"
                               class="group bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-2xl transform hover:-translate-y-1 transition duration-300">
                                {{-- Product Image --}}
                                <div class="aspect-square bg-gray-100 overflow-hidden">
                                    @if($product->main_image_url)
                                        <img src="{{ $product->main_image_url }}" alt="{{ $product->name }}"
                                             class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-6xl">
                                            📦
                                        </div>
                                    @endif
                                </div>

                                {{-- Product Info --}}
                                <div class="p-4">
                                    <h3 class="font-bold text-lg mb-2 text-gray-800 line-clamp-2 group-hover:text-indigo-600 transition">
                                        {{ $product->name }}
                                    </h3>

                                    @if($product->category)
                                        <p class="text-xs text-gray-500 mb-2">{{ $product->category->name }}</p>
                                    @endif

                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-2xl font-bold text-indigo-600">
                                                ฿{{ number_format($product->price, 0) }}
                                            </p>
                                            @if($product->compare_at_price && $product->compare_at_price > $product->price)
                                                <p class="text-sm text-gray-400 line-through">
                                                    ฿{{ number_format($product->compare_at_price, 0) }}
                                                </p>
                                            @endif
                                        </div>

                                        @if($product->rating_average > 0)
                                            <div class="text-sm">
                                                <span class="text-yellow-500">⭐</span>
                                                <span class="font-semibold">{{ number_format($product->rating_average, 1) }}</span>
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Stock Status --}}
                                    @if($product->track_inventory)
                                        @if($product->stock_quantity <= 0)
                                            <p class="text-xs text-red-600 mt-2 font-semibold">สินค้าหมด</p>
                                        @elseif($product->stock_quantity <= $product->low_stock_threshold)
                                            <p class="text-xs text-orange-600 mt-2 font-semibold">เหลือน้อย ({{ $product->stock_quantity }})</p>
                                        @endif
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>

                    {{-- Pagination --}}
                    @if($products->hasPages())
                        <div class="mt-8">
                            {{ $products->links() }}
                        </div>
                    @endif
                @else
                    <div class="bg-white rounded-2xl shadow-lg p-12 text-center">
                        <div class="text-6xl mb-4">📦</div>
                        <h3 class="text-2xl font-bold text-gray-800 mb-2">ไม่พบสินค้า</h3>
                        <p class="text-gray-600 mb-6">ร้านค้านี้ยังไม่มีสินค้า หรือสินค้าไม่ตรงกับเงื่อนไขการค้นหา</p>
                        @if(request()->hasAny(['search', 'category', 'min_price', 'max_price']))
                            <a href="{{ route('vendor.store.show', $store->store_slug) }}"
                               class="inline-block px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition">
                                ดูสินค้าทั้งหมด
                            </a>
                        @endif
                    </div>
                @endif
            </main>
        </div>
    </div>

    {{-- Store Info Footer --}}
    @if($store->store_address || $store->store_phone || $store->store_email)
        <div class="bg-white border-t mt-12">
            <div class="container mx-auto px-4 py-8">
                <h3 class="text-xl font-bold mb-4 text-gray-800">ข้อมูลติดต่อ</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    @if($store->store_address)
                        <div class="flex gap-3">
                            <span class="text-2xl">📍</span>
                            <div>
                                <p class="font-semibold text-gray-800">ที่อยู่</p>
                                <p class="text-gray-600">
                                    {{ $store->store_address }}
                                    @if($store->store_city), {{ $store->store_city }}@endif
                                    @if($store->store_postal_code) {{ $store->store_postal_code }}@endif
                                </p>
                            </div>
                        </div>
                    @endif

                    @if($store->store_phone)
                        <div class="flex gap-3">
                            <span class="text-2xl">📞</span>
                            <div>
                                <p class="font-semibold text-gray-800">โทรศัพท์</p>
                                <a href="tel:{{ $store->store_phone }}" class="text-indigo-600 hover:underline">
                                    {{ $store->store_phone }}
                                </a>
                            </div>
                        </div>
                    @endif

                    @if($store->store_email)
                        <div class="flex gap-3">
                            <span class="text-2xl">📧</span>
                            <div>
                                <p class="font-semibold text-gray-800">อีเมล</p>
                                <a href="mailto:{{ $store->store_email }}" class="text-indigo-600 hover:underline">
                                    {{ $store->store_email }}
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
