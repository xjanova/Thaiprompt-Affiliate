{{--
    Official Shop Product Detail Page - Ultra Premium Layout V3

    หน้ารายละเอียดสินค้าของร้านทางการ (Official Shop)
    ออกแบบใหม่ทั้งหมด - หรูหรา พรีเมี่ยม ระดับ Luxury Brand

    Features:
    - Ultra Premium Product Hero
    - Luxury Image Gallery with Zoom
    - Premium Price & Discount Display
    - Commission & Cashback Info
    - Interactive Add to Cart
    - Premium Product Tabs
    - Luxury Related Products
    - Dark Mode Support
--}}

@extends('layouts.storefront')

@section('title', $product->name . ' - Official Shop')

@section('meta')
<meta name="description" content="{{ Str::limit($product->description, 160) }}">
<meta property="og:title" content="{{ $product->name }} - Official Shop">
<meta property="og:description" content="{{ Str::limit($product->description, 160) }}">
<meta property="og:image" content="{{ $product->main_image_url }}">
@endsection

{{-- Premium Animated Lava Background --}}
@section('lava-background')
<div class="lava-background luxury-lava" aria-hidden="true">
    <div class="lava-blob luxury-blob-1"></div>
    <div class="lava-blob luxury-blob-2"></div>
    <div class="lava-blob luxury-blob-3"></div>
    <div class="lava-blob luxury-blob-4"></div>
    <div class="lava-blob luxury-blob-5"></div>
    <div class="lava-blob luxury-blob-6"></div>
</div>
@endsection

@section('content')
<div x-data="luxuryProductManager()" x-init="init()" class="min-h-screen">

    {{-- ════════════════════════════════════════════════════
         ✨ PREMIUM HERO HEADER
         ════════════════════════════════════════════════════ --}}
    <section class="relative overflow-hidden">
        {{-- Background Gradient --}}
        <div class="absolute inset-0 bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900"></div>
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_left,rgba(251,191,36,0.3),transparent_50%)]"></div>
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_bottom_right,rgba(168,85,247,0.2),transparent_50%)]"></div>

        {{-- Breadcrumb --}}
        <div class="relative container mx-auto px-4 pt-8 pb-4 z-10">
            <nav class="flex items-center gap-2 text-sm">
                <a href="{{ route('home') }}" class="text-white/60 hover:text-white transition-colors">
                    <i class="fas fa-home"></i>
                </a>
                <i class="fas fa-chevron-right text-white/40 text-xs"></i>
                <a href="{{ route('official-shop.index') }}" class="text-white/60 hover:text-white transition-colors">
                    Official Shop
                </a>
                @if($product->category)
                <i class="fas fa-chevron-right text-white/40 text-xs"></i>
                <a href="{{ route('official-shop.category', $product->category->slug) }}"
                   class="text-white/60 hover:text-white transition-colors">
                    {{ $product->category->name }}
                </a>
                @endif
                <i class="fas fa-chevron-right text-white/40 text-xs"></i>
                <span class="text-amber-400 font-semibold">{{ Str::limit($product->name, 40) }}</span>
            </nav>
        </div>

        {{-- Hero Content --}}
        <div class="relative container mx-auto px-4 pb-12 z-10">
            <div class="flex items-center gap-4">
                {{-- Official Badge --}}
                <div class="inline-flex items-center gap-2 px-5 py-2.5
                           bg-gradient-to-r from-amber-500/30 to-orange-500/30
                           backdrop-blur-xl border border-amber-400/30
                           rounded-full">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-amber-400 to-orange-500
                               flex items-center justify-center">
                        <i class="fas fa-crown text-white text-sm"></i>
                    </div>
                    <span class="text-amber-300 font-bold">OFFICIAL SHOP</span>
                    <i class="fas fa-check-circle text-green-400"></i>
                </div>

                {{-- Featured Badge --}}
                @if($product->is_featured)
                <div class="inline-flex items-center gap-2 px-4 py-2
                           bg-gradient-to-r from-yellow-500/30 to-orange-500/30
                           backdrop-blur-xl border border-yellow-400/30
                           rounded-full">
                    <i class="fas fa-star text-yellow-400"></i>
                    <span class="text-yellow-300 font-semibold text-sm">สินค้าแนะนำ</span>
                </div>
                @endif
            </div>
        </div>
    </section>

    {{-- ════════════════════════════════════════════════════
         🖼️ PRODUCT DETAIL SECTION
         ════════════════════════════════════════════════════ --}}
    <section class="relative -mt-4 z-20">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12">

                {{-- ═══════════════════════════════════════════
                     LEFT: PREMIUM IMAGE GALLERY
                     ═══════════════════════════════════════════ --}}
                <div class="space-y-4">
                    {{-- Main Image --}}
                    <div class="group relative bg-white dark:bg-gray-800 rounded-3xl shadow-2xl overflow-hidden
                               border-2 border-amber-200 dark:border-amber-700">

                        {{-- Badges --}}
                        <div class="absolute top-4 left-4 z-20 flex flex-col gap-2">
                            {{-- Official Badge --}}
                            <div class="px-4 py-2 bg-gradient-to-r from-amber-500 to-orange-500
                                       text-white text-sm font-bold rounded-full
                                       shadow-lg shadow-amber-500/30
                                       flex items-center gap-2">
                                <i class="fas fa-crown"></i>
                                <span>OFFICIAL</span>
                            </div>

                            {{-- Discount Badge --}}
                            @if($product->compare_at_price && $product->compare_at_price > $product->price)
                            @php
                                $discount = round((($product->compare_at_price - $product->price) / $product->compare_at_price) * 100);
                            @endphp
                            <div class="px-4 py-2 bg-gradient-to-r from-red-500 to-pink-500
                                       text-white text-sm font-bold rounded-full
                                       shadow-lg animate-pulse">
                                <i class="fas fa-tag mr-1"></i>
                                ลด {{ $discount }}%
                            </div>
                            @endif
                        </div>

                        {{-- Featured Star --}}
                        @if($product->is_featured)
                        <div class="absolute top-4 right-4 z-20">
                            <div class="w-14 h-14 bg-gradient-to-br from-yellow-400 via-amber-500 to-orange-500
                                       rounded-xl flex items-center justify-center
                                       shadow-lg shadow-amber-500/30
                                       rotate-12 hover:rotate-0 transition-transform duration-500">
                                <i class="fas fa-star text-white text-2xl"></i>
                            </div>
                        </div>
                        @endif

                        {{-- Main Image Display --}}
                        <div class="aspect-square relative overflow-hidden cursor-zoom-in"
                             @click="openLightbox(selectedImageIndex)">
                            <img :src="selectedImage"
                                 alt="{{ $product->name }}"
                                 class="w-full h-full object-cover
                                       transform group-hover:scale-105 transition-transform duration-700">

                            {{-- Hover Overlay --}}
                            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20
                                       flex items-center justify-center
                                       transition-all duration-300">
                                <div class="opacity-0 group-hover:opacity-100 transition-opacity">
                                    <div class="px-6 py-3 bg-white/90 rounded-xl shadow-xl">
                                        <i class="fas fa-search-plus mr-2"></i>
                                        คลิกเพื่อขยาย
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Thumbnail Gallery --}}
                    <div class="grid grid-cols-5 gap-3">
                        {{-- Main Image Thumbnail --}}
                        <button @click="selectImage(0)"
                                :class="selectedImageIndex === 0 ? 'ring-4 ring-amber-500 ring-offset-2' : 'ring-2 ring-transparent'"
                                class="aspect-square bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden
                                      transition-all duration-300 hover:scale-105">
                            <img src="{{ $product->primary_image_url ?? 'https://via.placeholder.com/150' }}"
                                 alt="Main"
                                 class="w-full h-full object-cover"
                                 onerror="this.src='https://via.placeholder.com/150'">
                        </button>

                        {{-- Additional Images --}}
                        @if($product->images && $product->images->count() > 0)
                        @foreach($product->images->take(4) as $index => $image)
                        <button @click="selectImage({{ $index + 1 }})"
                                :class="selectedImageIndex === {{ $index + 1 }} ? 'ring-4 ring-amber-500 ring-offset-2' : 'ring-2 ring-transparent'"
                                class="aspect-square bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden
                                      transition-all duration-300 hover:scale-105">
                            <img src="{{ $image->url }}"
                                 alt="Image {{ $loop->iteration }}"
                                 class="w-full h-full object-cover">
                        </button>
                        @endforeach
                        @endif
                    </div>
                </div>

                {{-- ═══════════════════════════════════════════
                     RIGHT: PRODUCT INFORMATION
                     ═══════════════════════════════════════════ --}}
                <div class="space-y-6">
                    {{-- Product Name --}}
                    <div>
                        @if($product->category)
                        <div class="text-amber-600 dark:text-amber-400 text-sm font-semibold mb-2 uppercase tracking-wider">
                            {{ $product->category->name }}
                        </div>
                        @endif

                        <h1 class="text-3xl md:text-4xl lg:text-5xl font-black text-gray-900 dark:text-white leading-tight">
                            {{ $product->name }}
                        </h1>
                    </div>

                    {{-- Rating & Stats --}}
                    <div class="flex flex-wrap items-center gap-4 py-4 border-y border-gray-200 dark:border-gray-700">
                        {{-- Rating Stars --}}
                        <div class="flex items-center gap-2">
                            <div class="flex items-center">
                                @for($i = 1; $i <= 5; $i++)
                                    @if($i <= floor($product->rating_average))
                                        <i class="fas fa-star text-amber-400 text-lg"></i>
                                    @elseif($i - 0.5 <= $product->rating_average)
                                        <i class="fas fa-star-half-alt text-amber-400 text-lg"></i>
                                    @else
                                        <i class="far fa-star text-gray-300 text-lg"></i>
                                    @endif
                                @endfor
                            </div>
                            <span class="text-lg font-bold text-gray-900 dark:text-white">
                                {{ number_format($product->rating_average, 1) }}
                            </span>
                            <span class="text-gray-500 dark:text-gray-400">
                                ({{ number_format($product->rating_count) }} รีวิว)
                            </span>
                        </div>

                        <div class="w-px h-6 bg-gray-300 dark:bg-gray-600"></div>

                        {{-- Views --}}
                        <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                            <i class="fas fa-eye"></i>
                            <span>{{ number_format($product->view_count) }} ครั้ง</span>
                        </div>

                        {{-- Sold --}}
                        @if($product->sold_count > 0)
                        <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                            <i class="fas fa-shopping-bag"></i>
                            <span>ขายแล้ว {{ number_format($product->sold_count) }}</span>
                        </div>
                        @endif
                    </div>

                    {{-- Premium Price Box --}}
                    <div class="relative overflow-hidden bg-gradient-to-br from-amber-50 via-orange-50 to-yellow-50
                               dark:from-amber-900/30 dark:via-orange-900/30 dark:to-yellow-900/30
                               rounded-3xl p-6 border-2 border-amber-200 dark:border-amber-700">
                        {{-- Decorative --}}
                        <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-amber-400/20 to-orange-400/20 rounded-full blur-2xl"></div>

                        <div class="relative">
                            {{-- Price Display --}}
                            <div class="flex items-end gap-4 mb-4">
                                <div class="text-5xl md:text-6xl font-black bg-gradient-to-r from-amber-600 via-orange-600 to-red-600
                                           bg-clip-text text-transparent">
                                    ฿{{ number_format($product->price, 2) }}
                                </div>
                                @if($product->compare_at_price && $product->compare_at_price > $product->price)
                                <div class="text-2xl text-gray-400 line-through mb-2">
                                    ฿{{ number_format($product->compare_at_price, 2) }}
                                </div>
                                @endif
                            </div>

                            {{-- Savings --}}
                            @if($product->compare_at_price && $product->compare_at_price > $product->price)
                            @php
                                $savings = $product->compare_at_price - $product->price;
                                $discount = round(($savings / $product->compare_at_price) * 100);
                            @endphp
                            <div class="inline-flex items-center gap-2 px-5 py-2.5
                                       bg-gradient-to-r from-red-500 to-pink-500
                                       text-white font-bold rounded-full shadow-lg">
                                <i class="fas fa-fire"></i>
                                <span>ประหยัด ฿{{ number_format($savings, 2) }} ({{ $discount }}%)</span>
                            </div>
                            @endif
                        </div>
                    </div>

                    {{-- Commission & Cashback Cards --}}
                    <div class="grid grid-cols-2 gap-4">
                        {{-- Commission Card --}}
                        @if($product->commission_rate > 0)
                        <div class="p-5 bg-gradient-to-br from-green-50 to-emerald-50
                                   dark:from-green-900/30 dark:to-emerald-900/30
                                   rounded-2xl border-2 border-green-200 dark:border-green-700">
                            <div class="flex items-center gap-4">
                                <div class="w-14 h-14 bg-gradient-to-br from-green-500 to-emerald-600
                                           rounded-xl flex items-center justify-center shadow-lg">
                                    <i class="fas fa-percentage text-white text-xl"></i>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400 mb-1">คอมมิชชั่น MLM</div>
                                    <div class="text-3xl font-black text-green-600 dark:text-green-400">
                                        {{ number_format($product->commission_rate, 0) }}%
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        {{-- Cashback Card --}}
                        @if(isset($cashbackInfo) && ($cashbackInfo['total_cashback'] ?? 0) > 0)
                        <div class="p-5 bg-gradient-to-br from-blue-50 to-cyan-50
                                   dark:from-blue-900/30 dark:to-cyan-900/30
                                   rounded-2xl border-2 border-blue-200 dark:border-blue-700">
                            <div class="flex items-center gap-4">
                                <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-cyan-600
                                           rounded-xl flex items-center justify-center shadow-lg">
                                    <i class="fas fa-gift text-white text-xl"></i>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400 mb-1">Cashback</div>
                                    <div class="text-3xl font-black text-blue-600 dark:text-blue-400">
                                        ฿{{ number_format($cashbackInfo['total_cashback'], 0) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>

                    {{-- Stock Status --}}
                    <div class="flex items-center gap-3">
                        @if($product->stock_status === 'in_stock' && $product->stock_quantity > 0)
                            <div class="flex items-center gap-2 px-4 py-2 bg-green-100 dark:bg-green-900/30
                                       text-green-700 dark:text-green-400 rounded-full font-semibold">
                                <span class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></span>
                                <span>พร้อมส่ง</span>
                            </div>
                            <span class="text-gray-600 dark:text-gray-400">
                                คงเหลือ {{ number_format($product->stock_quantity) }} ชิ้น
                            </span>
                        @elseif($product->stock_status === 'on_backorder')
                            <div class="flex items-center gap-2 px-4 py-2 bg-yellow-100 dark:bg-yellow-900/30
                                       text-yellow-700 dark:text-yellow-400 rounded-full font-semibold">
                                <i class="fas fa-clock"></i>
                                <span>สั่งซื้อล่วงหน้า</span>
                            </div>
                        @else
                            <div class="flex items-center gap-2 px-4 py-2 bg-red-100 dark:bg-red-900/30
                                       text-red-700 dark:text-red-400 rounded-full font-semibold">
                                <i class="fas fa-times-circle"></i>
                                <span>สินค้าหมด</span>
                            </div>
                        @endif
                    </div>

                    {{-- Quantity Selector --}}
                    <div class="space-y-3">
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300">
                            จำนวน
                        </label>
                        <div class="flex items-center gap-4">
                            <div class="inline-flex items-center bg-white dark:bg-gray-800
                                       border-2 border-gray-200 dark:border-gray-700
                                       rounded-xl overflow-hidden shadow-lg">
                                <button @click="decreaseQuantity()"
                                        class="px-5 py-4 bg-gray-50 dark:bg-gray-700
                                              hover:bg-amber-100 dark:hover:bg-amber-900/30
                                              transition-colors">
                                    <i class="fas fa-minus text-gray-600 dark:text-gray-400"></i>
                                </button>
                                <input type="number"
                                       x-model="quantity"
                                       min="1"
                                       max="{{ $product->stock_quantity }}"
                                       class="w-20 text-center py-4 bg-transparent border-x-2 border-gray-200 dark:border-gray-700
                                             focus:outline-none font-bold text-xl
                                             text-gray-900 dark:text-white">
                                <button @click="increaseQuantity()"
                                        class="px-5 py-4 bg-gray-50 dark:bg-gray-700
                                              hover:bg-amber-100 dark:hover:bg-amber-900/30
                                              transition-colors">
                                    <i class="fas fa-plus text-gray-600 dark:text-gray-400"></i>
                                </button>
                            </div>
                            <span class="text-gray-500 dark:text-gray-400 text-sm">
                                สูงสุด {{ number_format($product->stock_quantity) }} ชิ้น
                            </span>
                        </div>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="grid grid-cols-2 gap-4 pt-4">
                        {{-- Add to Cart --}}
                        <button @click="addToCart()"
                                :disabled="!canAddToCart"
                                :class="canAddToCart
                                    ? 'bg-gradient-to-r from-amber-500 via-orange-500 to-red-500 hover:from-amber-600 hover:via-orange-600 hover:to-red-600'
                                    : 'bg-gray-300 dark:bg-gray-600 cursor-not-allowed'"
                                class="relative overflow-hidden px-8 py-5
                                      text-white font-bold text-lg rounded-2xl
                                      shadow-xl hover:shadow-2xl
                                      transform hover:scale-105
                                      transition-all duration-300
                                      flex items-center justify-center gap-3
                                      group">
                            {{-- Shine Effect --}}
                            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent
                                       -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>
                            <i class="fas fa-cart-plus text-xl relative z-10"></i>
                            <span class="relative z-10">ใส่ตะกร้า</span>
                        </button>

                        {{-- Buy Now --}}
                        <button @click="buyNow()"
                                :disabled="!canAddToCart"
                                :class="canAddToCart
                                    ? 'bg-gradient-to-r from-purple-600 via-pink-600 to-red-600 hover:from-purple-700 hover:via-pink-700 hover:to-red-700'
                                    : 'bg-gray-300 dark:bg-gray-600 cursor-not-allowed'"
                                class="relative overflow-hidden px-8 py-5
                                      text-white font-bold text-lg rounded-2xl
                                      shadow-xl hover:shadow-2xl
                                      transform hover:scale-105
                                      transition-all duration-300
                                      flex items-center justify-center gap-3
                                      group">
                            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent
                                       -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>
                            <i class="fas fa-bolt text-xl relative z-10"></i>
                            <span class="relative z-10">ซื้อเลย</span>
                        </button>
                    </div>

                    {{-- ════════════════════════════════════════════════════
                         💰 BUY WITH COINS SECTION
                         ════════════════════════════════════════════════════ --}}
                    @if($product->allow_coin_purchase && $product->price_coins > 0)
                    <div class="relative overflow-hidden bg-gradient-to-br from-yellow-50 via-amber-50 to-orange-50
                               dark:from-yellow-900/30 dark:via-amber-900/30 dark:to-orange-900/30
                               rounded-3xl p-6 border-2 border-yellow-300 dark:border-yellow-600 mt-4">
                        {{-- Decorative Coins --}}
                        <div class="absolute top-2 right-2 opacity-20">
                            <svg class="w-20 h-20 text-yellow-500" fill="currentColor" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none"/>
                                <text x="12" y="16" text-anchor="middle" font-size="10" font-weight="bold" fill="currentColor">$</text>
                            </svg>
                        </div>

                        <div class="relative">
                            {{-- Header --}}
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-12 h-12 bg-gradient-to-br from-yellow-400 to-amber-500
                                           rounded-xl flex items-center justify-center shadow-lg
                                           animate-pulse">
                                    <i class="fas fa-coins text-white text-xl"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-yellow-800 dark:text-yellow-200">ซื้อด้วย Coins</h4>
                                    <p class="text-sm text-yellow-700 dark:text-yellow-300">แลกเหรียญเป็นสินค้าได้เลย!</p>
                                </div>
                            </div>

                            {{-- Coin Price Display --}}
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-2">
                                    <span class="text-3xl font-black text-yellow-600 dark:text-yellow-400">
                                        {{ number_format($product->price_coins, 0) }}
                                    </span>
                                    <span class="text-lg text-yellow-700 dark:text-yellow-300 font-semibold">Coins</span>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs text-yellow-700 dark:text-yellow-300">Coins ของคุณ</p>
                                    <p class="text-xl font-bold text-yellow-600 dark:text-yellow-400">
                                        {{ number_format($coinBalance ?? 0, 0) }} <i class="fas fa-coins text-sm"></i>
                                    </p>
                                </div>
                            </div>

                            {{-- Total Coins Needed --}}
                            <div class="p-3 bg-white/50 dark:bg-gray-800/50 rounded-xl mb-4">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-yellow-700 dark:text-yellow-300">ราคารวม:</span>
                                    <span class="font-bold text-yellow-800 dark:text-yellow-200"
                                          x-text="({{ $product->price_coins }} * quantity).toLocaleString() + ' Coins'">
                                    </span>
                                </div>
                            </div>

                            {{-- Buy with Coins Button --}}
                            @auth
                                @php
                                    $canBuyWithCoins = ($coinBalance ?? 0) >= $product->price_coins;
                                @endphp
                                <form action="{{ route('official-shop.purchase-with-coin', $product->slug) }}"
                                      method="POST"
                                      x-data="{ submitting: false }"
                                      @submit="submitting = true">
                                    @csrf
                                    <input type="hidden" name="quantity" :value="quantity">

                                    <button type="submit"
                                            :disabled="submitting || {{ ($coinBalance ?? 0) }} < ({{ $product->price_coins }} * quantity)"
                                            class="w-full relative overflow-hidden px-8 py-4
                                                  {{ $canBuyWithCoins ? 'bg-gradient-to-r from-yellow-500 via-amber-500 to-orange-500 hover:from-yellow-600 hover:via-amber-600 hover:to-orange-600' : 'bg-gray-300 dark:bg-gray-600 cursor-not-allowed' }}
                                                  text-white font-bold text-lg rounded-2xl
                                                  shadow-xl hover:shadow-2xl
                                                  transform hover:scale-105
                                                  transition-all duration-300
                                                  flex items-center justify-center gap-3
                                                  group disabled:transform-none disabled:hover:scale-100">
                                        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent
                                                   -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>
                                        <i class="fas fa-coins text-xl relative z-10" :class="submitting && 'animate-spin'"></i>
                                        <span class="relative z-10" x-text="submitting ? 'กำลังดำเนินการ...' : 'ซื้อด้วย Coins'"></span>
                                    </button>
                                </form>

                                @if(!$canBuyWithCoins)
                                <p class="text-center text-sm text-red-600 dark:text-red-400 mt-2">
                                    <i class="fas fa-exclamation-circle mr-1"></i>
                                    Coins ไม่เพียงพอ ต้องการ {{ number_format($product->price_coins, 0) }} Coins
                                </p>
                                @endif
                            @else
                                <a href="{{ route('login') }}?redirect={{ urlencode(request()->url()) }}"
                                   class="w-full block text-center px-8 py-4
                                         bg-gradient-to-r from-yellow-500 via-amber-500 to-orange-500
                                         hover:from-yellow-600 hover:via-amber-600 hover:to-orange-600
                                         text-white font-bold text-lg rounded-2xl
                                         shadow-xl hover:shadow-2xl
                                         transform hover:scale-105
                                         transition-all duration-300">
                                    <i class="fas fa-sign-in-alt mr-2"></i>
                                    เข้าสู่ระบบเพื่อซื้อด้วย Coins
                                </a>
                            @endauth
                        </div>
                    </div>
                    @endif

                    {{-- Trust Features --}}
                    <div class="grid grid-cols-2 gap-4 pt-4">
                        <div class="flex items-center gap-3 p-4 bg-white dark:bg-gray-800 rounded-xl
                                   border border-gray-200 dark:border-gray-700">
                            <i class="fas fa-shield-check text-amber-500 text-xl"></i>
                            <span class="text-sm text-gray-700 dark:text-gray-300">สินค้าของแท้ 100%</span>
                        </div>
                        <div class="flex items-center gap-3 p-4 bg-white dark:bg-gray-800 rounded-xl
                                   border border-gray-200 dark:border-gray-700">
                            <i class="fas fa-truck-fast text-green-500 text-xl"></i>
                            @php
                                $shippingMethod = $product->shipping_method ?? 'store_default';
                                $isFreeShipping = $shippingMethod === 'free' || ($shippingMethod === 'store_default' && $product->price >= 500);
                            @endphp
                            @if($isFreeShipping)
                                <span class="text-sm text-gray-700 dark:text-gray-300">จัดส่งฟรี</span>
                            @elseif($shippingMethod === 'flat_rate' && ($product->shipping_fee ?? 0) > 0)
                                <span class="text-sm text-gray-700 dark:text-gray-300">ค่าส่ง ฿{{ number_format($product->shipping_fee, 0) }}</span>
                            @else
                                <span class="text-sm text-gray-700 dark:text-gray-300">ค่าส่ง ฿50 | ฟรีเมื่อครบ ฿500</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-3 p-4 bg-white dark:bg-gray-800 rounded-xl
                                   border border-gray-200 dark:border-gray-700">
                            <i class="fas fa-rotate-left text-blue-500 text-xl"></i>
                            <span class="text-sm text-gray-700 dark:text-gray-300">เปลี่ยนคืน 7 วัน</span>
                        </div>
                        <div class="flex items-center gap-3 p-4 bg-white dark:bg-gray-800 rounded-xl
                                   border border-gray-200 dark:border-gray-700">
                            <i class="fas fa-headset text-purple-500 text-xl"></i>
                            <span class="text-sm text-gray-700 dark:text-gray-300">บริการ 24/7</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ════════════════════════════════════════════════════
         📋 PRODUCT DETAILS TABS
         ════════════════════════════════════════════════════ --}}
    <section class="py-16">
        <div class="container mx-auto px-4">
            <div x-data="{ tab: 'description' }">
                {{-- Tab Headers --}}
                <div class="flex flex-wrap gap-2 mb-8 p-2 bg-gray-100 dark:bg-gray-800 rounded-2xl">
                    <button @click="tab = 'description'"
                            :class="tab === 'description'
                                ? 'bg-white dark:bg-gray-700 text-amber-600 dark:text-amber-400 shadow-lg'
                                : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'"
                            class="flex-1 sm:flex-none px-8 py-4 rounded-xl font-bold transition-all">
                        <i class="fas fa-file-alt mr-2"></i>
                        รายละเอียด
                    </button>
                    <button @click="tab = 'specifications'"
                            :class="tab === 'specifications'
                                ? 'bg-white dark:bg-gray-700 text-amber-600 dark:text-amber-400 shadow-lg'
                                : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'"
                            class="flex-1 sm:flex-none px-8 py-4 rounded-xl font-bold transition-all">
                        <i class="fas fa-list-check mr-2"></i>
                        ข้อมูลจำเพาะ
                    </button>
                    <button @click="tab = 'reviews'"
                            :class="tab === 'reviews'
                                ? 'bg-white dark:bg-gray-700 text-amber-600 dark:text-amber-400 shadow-lg'
                                : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'"
                            class="flex-1 sm:flex-none px-8 py-4 rounded-xl font-bold transition-all">
                        <i class="fas fa-star mr-2"></i>
                        รีวิว ({{ $product->rating_count }})
                    </button>
                </div>

                {{-- Tab Contents --}}
                <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl p-8 lg:p-12
                           border border-gray-100 dark:border-gray-700">

                    {{-- Description Tab --}}
                    <div x-show="tab === 'description'" x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 transform translate-y-4"
                         x-transition:enter-end="opacity-100 transform translate-y-0">
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
                            รายละเอียดสินค้า
                        </h3>
                        <div class="prose dark:prose-invert max-w-none text-gray-700 dark:text-gray-300 leading-relaxed">
                            {!! nl2br(e($product->description)) !!}
                        </div>
                    </div>

                    {{-- Specifications Tab --}}
                    <div x-show="tab === 'specifications'" x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 transform translate-y-4"
                         x-transition:enter-end="opacity-100 transform translate-y-0"
                         x-cloak>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
                            ข้อมูลจำเพาะสินค้า
                        </h3>
                        <div class="divide-y divide-gray-200 dark:divide-gray-700">
                            <div class="py-4 grid grid-cols-3 gap-4">
                                <span class="font-semibold text-gray-700 dark:text-gray-300">SKU</span>
                                <span class="col-span-2 text-gray-600 dark:text-gray-400">{{ $product->sku }}</span>
                            </div>
                            @if($product->brand)
                            <div class="py-4 grid grid-cols-3 gap-4">
                                <span class="font-semibold text-gray-700 dark:text-gray-300">ยี่ห้อ</span>
                                <span class="col-span-2 text-gray-600 dark:text-gray-400">{{ $product->brand }}</span>
                            </div>
                            @endif
                            @if($product->category)
                            <div class="py-4 grid grid-cols-3 gap-4">
                                <span class="font-semibold text-gray-700 dark:text-gray-300">หมวดหมู่</span>
                                <span class="col-span-2 text-gray-600 dark:text-gray-400">{{ $product->category->name }}</span>
                            </div>
                            @endif
                            @if($product->weight)
                            <div class="py-4 grid grid-cols-3 gap-4">
                                <span class="font-semibold text-gray-700 dark:text-gray-300">น้ำหนัก</span>
                                <span class="col-span-2 text-gray-600 dark:text-gray-400">{{ number_format($product->weight / 1000, 2) }} กก.</span>
                            </div>
                            @endif
                            @if($product->dimensions)
                            <div class="py-4 grid grid-cols-3 gap-4">
                                <span class="font-semibold text-gray-700 dark:text-gray-300">ขนาด</span>
                                <span class="col-span-2 text-gray-600 dark:text-gray-400">{{ $product->dimensions }}</span>
                            </div>
                            @endif
                        </div>
                    </div>

                    {{-- Reviews Tab --}}
                    <div x-show="tab === 'reviews'" x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 transform translate-y-4"
                         x-transition:enter-end="opacity-100 transform translate-y-0"
                         x-cloak>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
                            รีวิวจากลูกค้า
                        </h3>

                        @if($product->approvedReviews && $product->approvedReviews->count() > 0)
                            {{-- Review Summary --}}
                            <div class="flex flex-col md:flex-row gap-8 mb-10 p-6 bg-gray-50 dark:bg-gray-700/50 rounded-2xl">
                                <div class="text-center md:border-r md:border-gray-200 md:dark:border-gray-600 md:pr-8">
                                    <div class="text-6xl font-black text-amber-500 mb-2">
                                        {{ number_format($product->rating_average, 1) }}
                                    </div>
                                    <div class="flex items-center justify-center gap-1 mb-2">
                                        @for($i = 1; $i <= 5; $i++)
                                            @if($i <= floor($product->rating_average))
                                                <i class="fas fa-star text-amber-400"></i>
                                            @else
                                                <i class="far fa-star text-gray-300"></i>
                                            @endif
                                        @endfor
                                    </div>
                                    <div class="text-gray-500 dark:text-gray-400">
                                        จาก {{ number_format($product->rating_count) }} รีวิว
                                    </div>
                                </div>
                                <div class="flex-1">
                                    {{-- ในที่นี้สามารถเพิ่ม rating distribution bars ได้ --}}
                                </div>
                            </div>

                            {{-- Review List --}}
                            <div class="space-y-6">
                                @foreach($product->approvedReviews as $review)
                                <div class="p-6 bg-gray-50 dark:bg-gray-700/30 rounded-2xl">
                                    <div class="flex items-start gap-4">
                                        <x-user-avatar :user="$review->user" size="lg" :ring="false" />
                                        <div class="flex-1">
                                            <div class="flex flex-wrap items-center gap-3 mb-3">
                                                <span class="font-bold text-gray-900 dark:text-white">
                                                    {{ $review->user->name }}
                                                </span>
                                                <div class="flex items-center">
                                                    @for($i = 1; $i <= 5; $i++)
                                                        @if($i <= $review->rating)
                                                            <i class="fas fa-star text-amber-400"></i>
                                                        @else
                                                            <i class="far fa-star text-gray-300"></i>
                                                        @endif
                                                    @endfor
                                                </div>
                                                <span class="text-sm text-gray-500 dark:text-gray-400">
                                                    {{ $review->created_at->diffForHumans() }}
                                                </span>
                                            </div>
                                            <p class="text-gray-700 dark:text-gray-300 leading-relaxed">
                                                {{ $review->comment }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-16">
                                <div class="w-24 h-24 mx-auto mb-6 bg-gray-100 dark:bg-gray-700 rounded-full
                                           flex items-center justify-center">
                                    <i class="fas fa-comments text-4xl text-gray-400"></i>
                                </div>
                                <h4 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                                    ยังไม่มีรีวิว
                                </h4>
                                <p class="text-gray-500 dark:text-gray-400">
                                    เป็นคนแรกที่รีวิวสินค้านี้
                                </p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ════════════════════════════════════════════════════
         🔗 RELATED PRODUCTS
         ════════════════════════════════════════════════════ --}}
    @if($relatedProducts && $relatedProducts->count() > 0)
    <section class="py-16 bg-gradient-to-b from-gray-50 to-white dark:from-gray-800 dark:to-gray-900">
        <div class="container mx-auto px-4">
            {{-- Section Header --}}
            <div class="text-center mb-12">
                <div class="inline-flex items-center gap-2 px-5 py-2.5
                           bg-gradient-to-r from-purple-100 to-pink-100
                           dark:from-purple-900/30 dark:to-pink-900/30
                           rounded-full mb-4">
                    <i class="fas fa-heart text-purple-600 dark:text-purple-400"></i>
                    <span class="text-purple-700 dark:text-purple-300 font-semibold">You May Also Like</span>
                </div>
                <h2 class="text-4xl font-black text-gray-900 dark:text-white">
                    สินค้าที่เกี่ยวข้อง
                </h2>
            </div>

            {{-- Products Grid --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-6">
                @foreach($relatedProducts as $related)
                <div class="group">
                    <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden
                               border border-gray-100 dark:border-gray-700
                               hover:shadow-2xl hover:border-amber-300 dark:hover:border-amber-600
                               hover:-translate-y-2
                               transition-all duration-500">

                        <a href="{{ route('official-shop.show', $related->slug) }}" class="block">
                            <div class="aspect-square relative overflow-hidden bg-gray-50 dark:bg-gray-700">
                                {{-- Official Badge --}}
                                <div class="absolute top-3 left-3 z-10">
                                    <div class="px-2.5 py-1 bg-gradient-to-r from-amber-500 to-orange-500
                                               text-white text-xs font-bold rounded-full shadow-md">
                                        Official
                                    </div>
                                </div>

                                <img src="{{ $related->primary_image_url ?? 'https://via.placeholder.com/300' }}"
                                     alt="{{ $related->name }}"
                                     class="w-full h-full object-cover
                                           group-hover:scale-110 transition-transform duration-700"
                                     loading="lazy"
                                     onerror="this.src='https://via.placeholder.com/300'">
                            </div>
                        </a>

                        <div class="p-4">
                            <a href="{{ route('official-shop.show', $related->slug) }}" class="block">
                                <h3 class="font-semibold text-gray-900 dark:text-white text-sm mb-2
                                          line-clamp-2 h-10
                                          hover:text-amber-600 dark:hover:text-amber-400 transition-colors">
                                    {{ $related->name }}
                                </h3>
                            </a>

                            <div class="text-xl font-bold text-amber-600 dark:text-amber-400 mb-4">
                                ฿{{ number_format($related->price, 0) }}
                            </div>

                            <a href="{{ route('official-shop.show', $related->slug) }}"
                               class="block w-full py-2.5 text-center text-sm font-bold
                                     bg-gradient-to-r from-amber-500 to-orange-500
                                     hover:from-amber-600 hover:to-orange-600
                                     text-white rounded-xl
                                     transition-all">
                                ดูรายละเอียด
                            </a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- ════════════════════════════════════════════════════
         🖼️ IMAGE LIGHTBOX MODAL
         ════════════════════════════════════════════════════ --}}
    <div x-show="lightboxOpen"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @keydown.escape.window="lightboxOpen = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/90"
         x-cloak>
        {{-- Close Button --}}
        <button @click="lightboxOpen = false"
                class="absolute top-6 right-6 w-12 h-12 bg-white/10 hover:bg-white/20
                      rounded-full flex items-center justify-center
                      text-white text-2xl transition-colors">
            <i class="fas fa-times"></i>
        </button>

        {{-- Image --}}
        <img :src="lightboxImage"
             alt="Product Image"
             class="max-w-full max-h-[85vh] object-contain rounded-lg shadow-2xl">

        {{-- Navigation --}}
        <button @click="prevLightboxImage()"
                class="absolute left-6 w-14 h-14 bg-white/10 hover:bg-white/20
                      rounded-full flex items-center justify-center
                      text-white text-2xl transition-colors">
            <i class="fas fa-chevron-left"></i>
        </button>
        <button @click="nextLightboxImage()"
                class="absolute right-6 w-14 h-14 bg-white/10 hover:bg-white/20
                      rounded-full flex items-center justify-center
                      text-white text-2xl transition-colors">
            <i class="fas fa-chevron-right"></i>
        </button>
    </div>
</div>

@push('scripts')
<script>
/**
 * Luxury Product Manager - Alpine.js Component
 *
 * จัดการหน้ารายละเอียดสินค้า Official Shop
 */
function luxuryProductManager() {
    return {
        quantity: 1,
        maxQuantity: {{ $product->stock_quantity }},
        selectedImageIndex: 0,
        lightboxOpen: false,
        lightboxImage: '',

        // รายการรูปภาพทั้งหมด
        images: [
            '{{ $product->main_image_url ?? "https://via.placeholder.com/800" }}',
            @if($product->images)
            @foreach($product->images as $image)
            '{{ $image->url }}',
            @endforeach
            @endif
        ],

        /**
         * คำนวณว่าสามารถเพิ่มลงตะกร้าได้หรือไม่
         */
        get canAddToCart() {
            return this.quantity > 0
                && this.quantity <= this.maxQuantity
                && '{{ $product->stock_status }}' === 'in_stock';
        },

        /**
         * รูปภาพที่เลือกแสดงปัจจุบัน
         */
        get selectedImage() {
            return this.images[this.selectedImageIndex] || this.images[0];
        },

        /**
         * Initialize component
         */
        init() {
            console.log('🏆 Luxury Product Manager initialized');
        },

        /**
         * เลือกรูปภาพ
         */
        selectImage(index) {
            this.selectedImageIndex = index;
        },

        /**
         * เพิ่มจำนวน
         */
        increaseQuantity() {
            if (this.quantity < this.maxQuantity) {
                this.quantity++;
            }
        },

        /**
         * ลดจำนวน
         */
        decreaseQuantity() {
            if (this.quantity > 1) {
                this.quantity--;
            }
        },

        /**
         * เปิด lightbox
         */
        openLightbox(index) {
            this.lightboxImage = this.images[index];
            this.lightboxOpen = true;
        },

        /**
         * รูปภาพก่อนหน้าใน lightbox
         */
        prevLightboxImage() {
            const currentIndex = this.images.indexOf(this.lightboxImage);
            const newIndex = currentIndex > 0 ? currentIndex - 1 : this.images.length - 1;
            this.lightboxImage = this.images[newIndex];
        },

        /**
         * รูปภาพถัดไปใน lightbox
         */
        nextLightboxImage() {
            const currentIndex = this.images.indexOf(this.lightboxImage);
            const newIndex = currentIndex < this.images.length - 1 ? currentIndex + 1 : 0;
            this.lightboxImage = this.images[newIndex];
        },

        /**
         * เพิ่มสินค้าลงตะกร้า
         */
        async addToCart() {
            if (!this.canAddToCart) return;

            try {
                const response = await fetch('/cart/add', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        product_id: {{ $product->id }},
                        quantity: this.quantity
                    })
                });

                const data = await response.json();

                if (data.success) {
                    // อัพเดท cart badge
                    window.dispatchEvent(new CustomEvent('cart-updated'));

                    // แสดง notification
                    window.dispatchEvent(new CustomEvent('notify', {
                        detail: {
                            message: '✅ เพิ่มสินค้าลงตะกร้าสำเร็จ',
                            type: 'success'
                        }
                    }));
                } else {
                    throw new Error(data.message || 'เกิดข้อผิดพลาด');
                }
            } catch (error) {
                console.error('Error:', error);
                window.dispatchEvent(new CustomEvent('notify', {
                    detail: {
                        message: error.message || '❌ ไม่สามารถเพิ่มสินค้าลงตะกร้าได้',
                        type: 'error'
                    }
                }));
            }
        },

        /**
         * ซื้อเลย (เพิ่มตะกร้าแล้วไปหน้าตะกร้า)
         */
        async buyNow() {
            await this.addToCart();
            setTimeout(() => {
                window.location.href = '/cart';
            }, 500);
        }
    };
}

window.luxuryProductManager = luxuryProductManager;
</script>
@endpush

@push('styles')
<style>
/* ════════════════════════════════════════════════════
   🎨 CUSTOM CSS ANIMATIONS & EFFECTS
   ════════════════════════════════════════════════════ */

/* Line Clamp */
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* ═══════════════════════════════════════════
   LUXURY LAVA BACKGROUND
   สีทอง, Rose Gold, Platinum - หรูหราสุดๆ
   ═══════════════════════════════════════════ */

/* Luxury blob 1 - Gold */
.luxury-blob-1 {
    width: 400px;
    height: 420px;
    background: linear-gradient(180deg, #f59e0b 0%, #fbbf24 40%, #fcd34d 70%, #f59e0b 100%);
    left: 5%;
    top: 10%;
    animation: luxuryFloat1 20s ease-in-out infinite, luxuryMorph1 14s ease-in-out infinite;
    filter: blur(80px);
    opacity: 0.25;
}

/* Luxury blob 2 - Rose Gold */
.luxury-blob-2 {
    width: 350px;
    height: 370px;
    background: linear-gradient(180deg, #ec4899 0%, #f472b6 40%, #fda4af 70%, #ec4899 100%);
    right: 10%;
    top: 20%;
    animation: luxuryFloat2 22s ease-in-out infinite, luxuryMorph2 16s ease-in-out infinite;
    animation-delay: -5s;
    filter: blur(80px);
    opacity: 0.25;
}

/* Luxury blob 3 - Platinum/Silver */
.luxury-blob-3 {
    width: 380px;
    height: 400px;
    background: linear-gradient(180deg, #a855f7 0%, #c084fc 40%, #e879f9 70%, #a855f7 100%);
    left: 35%;
    top: 50%;
    animation: luxuryFloat3 24s ease-in-out infinite, luxuryMorph1 18s ease-in-out infinite;
    animation-delay: -10s;
    filter: blur(80px);
    opacity: 0.2;
}

/* Luxury blob 4 - Deep Gold */
.luxury-blob-4 {
    width: 300px;
    height: 320px;
    background: linear-gradient(180deg, #d97706 0%, #f59e0b 40%, #fbbf24 70%, #d97706 100%);
    right: 5%;
    top: 60%;
    animation: luxuryFloat1 18s ease-in-out infinite, luxuryMorph2 12s ease-in-out infinite;
    animation-delay: -3s;
    filter: blur(80px);
    opacity: 0.25;
}

/* Luxury blob 5 - Warm Pink */
.luxury-blob-5 {
    width: 280px;
    height: 300px;
    background: linear-gradient(180deg, #db2777 0%, #ec4899 50%, #db2777 100%);
    left: 15%;
    top: 70%;
    animation: luxuryFloat2 21s ease-in-out infinite, luxuryMorph1 15s ease-in-out infinite;
    animation-delay: -7s;
    filter: blur(80px);
    opacity: 0.2;
}

/* Luxury blob 6 - Amber Glow */
.luxury-blob-6 {
    width: 260px;
    height: 280px;
    background: linear-gradient(180deg, #ea580c 0%, #f97316 50%, #ea580c 100%);
    left: 55%;
    top: 15%;
    animation: luxuryFloat3 19s ease-in-out infinite, luxuryMorph2 13s ease-in-out infinite;
    animation-delay: -12s;
    filter: blur(80px);
    opacity: 0.2;
}

/* Luxury Float Animations */
@keyframes luxuryFloat1 {
    0%, 100% { transform: translate(0, 0) scale(1) rotate(0deg); }
    25% { transform: translate(50px, -70px) scale(1.1) rotate(3deg); }
    50% { transform: translate(-40px, -140px) scale(0.95) rotate(-2deg); }
    75% { transform: translate(60px, -70px) scale(1.05) rotate(1deg); }
}

@keyframes luxuryFloat2 {
    0%, 100% { transform: translate(0, 0) scale(1) rotate(0deg); }
    33% { transform: translate(-60px, -120px) scale(1.12) rotate(-3deg); }
    66% { transform: translate(50px, -60px) scale(0.9) rotate(2deg); }
}

@keyframes luxuryFloat3 {
    0%, 100% { transform: translate(0, 0) scale(1); }
    50% { transform: translate(80px, -160px) scale(1.15); }
}

/* Luxury Morph Animations */
@keyframes luxuryMorph1 {
    0%, 100% { border-radius: 40% 60% 55% 45% / 55% 45% 60% 40%; }
    25% { border-radius: 55% 45% 40% 60% / 45% 55% 50% 50%; }
    50% { border-radius: 45% 55% 50% 50% / 50% 50% 55% 45%; }
    75% { border-radius: 50% 50% 60% 40% / 60% 40% 45% 55%; }
}

@keyframes luxuryMorph2 {
    0%, 100% { border-radius: 50% 50% 45% 55% / 45% 55% 50% 50%; }
    33% { border-radius: 45% 55% 50% 50% / 55% 45% 55% 45%; }
    66% { border-radius: 55% 45% 55% 45% / 45% 55% 45% 55%; }
}

/* ═══════════════════════════════════════════
   DARK MODE - LUXURY RGB GLOW
   ═══════════════════════════════════════════ */
.dark .luxury-blob-1 {
    background: linear-gradient(180deg, #fbbf24 0%, #fcd34d 50%, #fbbf24 100%);
    filter: blur(70px);
    opacity: 0.5;
    box-shadow:
        0 0 80px rgba(251, 191, 36, 0.8),
        0 0 160px rgba(251, 191, 36, 0.6),
        0 0 240px rgba(251, 191, 36, 0.4);
}

.dark .luxury-blob-2 {
    background: linear-gradient(180deg, #ec4899 0%, #f472b6 50%, #ec4899 100%);
    filter: blur(70px);
    opacity: 0.5;
    box-shadow:
        0 0 80px rgba(236, 72, 153, 0.8),
        0 0 160px rgba(236, 72, 153, 0.6),
        0 0 240px rgba(236, 72, 153, 0.4);
}

.dark .luxury-blob-3 {
    background: linear-gradient(180deg, #a855f7 0%, #c084fc 50%, #a855f7 100%);
    filter: blur(70px);
    opacity: 0.45;
    box-shadow:
        0 0 80px rgba(168, 85, 247, 0.8),
        0 0 160px rgba(168, 85, 247, 0.6),
        0 0 240px rgba(168, 85, 247, 0.4);
}

.dark .luxury-blob-4 {
    background: linear-gradient(180deg, #f59e0b 0%, #fbbf24 50%, #f59e0b 100%);
    filter: blur(70px);
    opacity: 0.5;
    box-shadow:
        0 0 80px rgba(245, 158, 11, 0.8),
        0 0 160px rgba(245, 158, 11, 0.6),
        0 0 240px rgba(245, 158, 11, 0.4);
}

.dark .luxury-blob-5 {
    background: linear-gradient(180deg, #db2777 0%, #ec4899 50%, #db2777 100%);
    filter: blur(70px);
    opacity: 0.45;
    box-shadow:
        0 0 80px rgba(219, 39, 119, 0.8),
        0 0 160px rgba(219, 39, 119, 0.6),
        0 0 240px rgba(219, 39, 119, 0.4);
}

.dark .luxury-blob-6 {
    background: linear-gradient(180deg, #f97316 0%, #fb923c 50%, #f97316 100%);
    filter: blur(70px);
    opacity: 0.45;
    box-shadow:
        0 0 80px rgba(249, 115, 22, 0.8),
        0 0 160px rgba(249, 115, 22, 0.6),
        0 0 240px rgba(249, 115, 22, 0.4);
}

/* ═══════════════════════════════════════════
   MOBILE OPTIMIZATION
   ═══════════════════════════════════════════ */
@media (max-width: 768px) {
    .luxury-lava .lava-blob {
        transform: scale(0.5);
        filter: blur(50px);
    }
    .luxury-blob-5,
    .luxury-blob-6 {
        display: none;
    }
    .dark .luxury-lava .lava-blob {
        filter: blur(60px);
    }
}

/* ═══════════════════════════════════════════
   REDUCED MOTION PREFERENCE
   ═══════════════════════════════════════════ */
@media (prefers-reduced-motion: reduce) {
    .luxury-lava .lava-blob {
        animation: none;
        transform: translateY(0);
    }
}
</style>
@endpush
@endsection
