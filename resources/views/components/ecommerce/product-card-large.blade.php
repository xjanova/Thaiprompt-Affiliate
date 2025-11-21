{{--
    Product Card - Large Image with Overlay Style (V3)

    การ์ดสินค้าแบบรูปใหญ่พร้อม overlay content
    เหมาะสำหรับ featured products หรือ hero sections

    @props
    - product: Product model object
    - showPv: boolean (แสดง PV หรือไม่)
    - showCommission: boolean (แสดง Commission preview หรือไม่)
    - featured: boolean (แสดงเป็น featured product)

    @example
    <x-ecommerce.product-card-large
        :product="$product"
        :featured="true"
    />
--}}

@props([
    'product',
    'showPv' => true,
    'showCommission' => true,
    'featured' => false,
])

<div x-data="productCardComponent({{ $product->id }})"
     class="group relative h-full {{ $featured ? 'min-h-[400px]' : 'min-h-[320px]' }} overflow-hidden rounded-2xl">

    {{-- Background Image --}}
    <div class="absolute inset-0">
        <img src="{{ $product->primary_image_url ?? asset('images/placeholder.png') }}"
             alt="{{ $product->name }}"
             loading="lazy"
             class="w-full h-full object-cover transform group-hover:scale-110
                    transition-transform duration-700">

        {{-- Gradient Overlay --}}
        <div class="absolute inset-0 bg-gradient-to-t
                    from-black via-black/60 to-transparent
                    group-hover:from-black/90 transition-all duration-500"></div>

        {{-- Blur Overlay (แสดงเมื่อ hover) --}}
        <div class="absolute inset-0 backdrop-blur-sm opacity-0 group-hover:opacity-100
                    transition-opacity duration-500"></div>
    </div>

    {{-- Content Overlay --}}
    <div class="relative h-full flex flex-col justify-between p-6">

        {{-- Top Section - Badges & Actions --}}
        <div class="flex items-start justify-between">
            {{-- Badges --}}
            <div class="flex flex-col gap-2">
                @if($product->is_new ?? false)
                <span class="inline-flex items-center px-3 py-1
                             bg-gradient-to-r from-blue-500 to-purple-600
                             text-white text-xs font-bold rounded-full shadow-lg">
                    <i class="fas fa-sparkles mr-1"></i>
                    ใหม่
                </span>
                @endif

                @if($product->discount_percentage ?? 0 > 0)
                <span class="inline-flex items-center px-3 py-1
                             bg-gradient-to-r from-red-500 to-pink-600
                             text-white text-xs font-bold rounded-full shadow-lg">
                    -{{ $product->discount_percentage }}%
                </span>
                @endif

                @if($featured)
                <span class="inline-flex items-center px-3 py-1
                             bg-gradient-to-r from-yellow-500 to-orange-600
                             text-white text-xs font-bold rounded-full shadow-lg">
                    <i class="fas fa-star mr-1"></i>
                    Featured
                </span>
                @endif
            </div>

            {{-- Quick Actions --}}
            <div class="flex gap-2">
                <button @click.prevent="toggleWishlist()"
                        class="w-10 h-10 bg-white/10 backdrop-blur-md border border-white/20
                               rounded-full flex items-center justify-center
                               hover:bg-white/20 transform hover:scale-110 transition-all"
                        :class="{ 'text-red-400': isInWishlist, 'text-white': !isInWishlist }">
                    <i class="fas fa-heart"></i>
                </button>

                <button @click.prevent="quickView()"
                        class="w-10 h-10 bg-white/10 backdrop-blur-md border border-white/20
                               text-white rounded-full flex items-center justify-center
                               hover:bg-white/20 transform hover:scale-110 transition-all">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
        </div>

        {{-- Bottom Section - Product Info --}}
        <div class="space-y-4">
            {{-- Category & Rating --}}
            <div class="flex items-center justify-between">
                @if($product->category)
                <a href="{{ route('marketplace.category', $product->category->slug) }}"
                   class="text-sm text-white/90 font-medium hover:text-white transition-colors">
                    {{ $product->category->name }}
                </a>
                @endif

                {{-- Rating --}}
                @if($product->average_rating ?? 0 > 0)
                <div class="flex items-center gap-2">
                    <div class="flex items-center text-yellow-400">
                        @for($i = 1; $i <= 5; $i++)
                            @if($i <= floor($product->average_rating))
                            <i class="fas fa-star text-xs"></i>
                            @elseif($i - 0.5 <= $product->average_rating)
                            <i class="fas fa-star-half-alt text-xs"></i>
                            @else
                            <i class="far fa-star text-xs"></i>
                            @endif
                        @endfor
                    </div>
                    <span class="text-xs text-white/80">
                        ({{ $product->reviews_count ?? 0 }})
                    </span>
                </div>
                @endif
            </div>

            {{-- Product Name --}}
            <h3 class="text-2xl font-bold text-white leading-tight
                       group-hover:text-yellow-200 transition-colors">
                <a href="{{ route('marketplace.product.show', $product->slug) }}">
                    {{ $product->name }}
                </a>
            </h3>

            {{-- PV & Commission Info --}}
            @if($showPv || $showCommission)
            <div class="flex flex-wrap gap-2">
                @if($showPv && ($product->pv_value ?? 0) > 0)
                <span class="inline-flex items-center gap-1 px-3 py-1
                             bg-white/10 backdrop-blur-md border border-white/20
                             text-white text-sm font-medium rounded-lg">
                    <i class="fas fa-coins"></i>
                    <span>{{ number_format($product->pv_value) }} PV</span>
                </span>
                @endif

                @if($showCommission && ($product->commission_rate ?? 0) > 0)
                <span class="inline-flex items-center gap-1 px-3 py-1
                             bg-white/10 backdrop-blur-md border border-white/20
                             text-white text-sm font-medium rounded-lg">
                    <i class="fas fa-percent"></i>
                    <span>{{ $product->commission_rate }}% Commission</span>
                </span>
                @endif

                @if(($product->customer_cashback ?? 0) > 0)
                <span class="inline-flex items-center gap-1 px-3 py-1
                             bg-green-500/30 backdrop-blur-md border border-green-400/30
                             text-green-300 text-sm font-medium rounded-lg">
                    <i class="fas fa-gift"></i>
                    <span>Cashback ฿{{ number_format($product->customer_cashback) }}</span>
                </span>
                @endif
            </div>
            @endif

            {{-- Price & Add to Cart --}}
            <div class="flex items-center gap-4">
                {{-- Price --}}
                <div class="flex-1">
                    @if($product->sale_price && $product->sale_price < $product->price)
                    <div class="flex items-baseline gap-3">
                        <span class="text-3xl font-bold text-white">
                            ฿{{ number_format($product->sale_price, 2) }}
                        </span>
                        <span class="text-lg text-white/60 line-through">
                            ฿{{ number_format($product->price, 2) }}
                        </span>
                    </div>
                    @else
                    <span class="text-3xl font-bold text-white">
                        ฿{{ number_format($product->price, 2) }}
                    </span>
                    @endif
                </div>

                {{-- Add to Cart Button --}}
                @if($product->stock_quantity > 0)
                <button @click="addToCart()"
                        :disabled="isAddingToCart"
                        class="px-6 py-3
                               bg-gradient-to-r from-purple-600 via-pink-600 to-red-500
                               hover:from-purple-700 hover:via-pink-700 hover:to-red-600
                               text-white font-bold rounded-xl
                               shadow-lg hover:shadow-2xl
                               transform hover:scale-105 active:scale-95
                               transition-all duration-300
                               disabled:opacity-50 disabled:cursor-not-allowed
                               flex items-center gap-2">
                    <i class="fas" :class="isAddingToCart ? 'fa-spinner fa-spin' : 'fa-cart-plus'"></i>
                    <span x-text="isAddingToCart ? 'กำลังเพิ่ม...' : 'เพิ่มลงตะกร้า'"></span>
                </button>
                @else
                <button disabled
                        class="px-6 py-3 bg-white/10 backdrop-blur-md border border-white/20
                               text-white/60 font-bold rounded-xl cursor-not-allowed">
                    <i class="fas fa-ban mr-2"></i>
                    สินค้าหมด
                </button>
                @endif
            </div>

            {{-- Additional Info (ซ่อนไว้ แสดงเมื่อ hover) --}}
            <div class="opacity-0 group-hover:opacity-100 transition-opacity duration-500">
                @if($product->short_description)
                <p class="text-sm text-white/80 line-clamp-2">
                    {{ $product->short_description }}
                </p>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
