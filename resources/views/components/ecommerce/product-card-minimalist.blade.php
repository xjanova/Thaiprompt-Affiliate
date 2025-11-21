{{--
    Product Card - Minimalist Modern Style (V3)

    การ์ดสินค้าแบบเรียบง่ายสมัยใหม่ เน้นความสะอาดตา
    เหมาะสำหรับหน้าที่ต้องการ clean look

    @props
    - product: Product model object
    - showPv: boolean (แสดง PV หรือไม่)
    - showCommission: boolean (แสดง Commission preview หรือไม่)

    @example
    <x-ecommerce.product-card-minimalist
        :product="$product"
    />
--}}

@props([
    'product',
    'showPv' => false,
    'showCommission' => false,
])

<div x-data="productCardComponent({{ $product->id }})"
     class="group h-full">

    <div class="relative h-full bg-white dark:bg-gray-800
                border border-gray-100 dark:border-gray-700
                rounded-xl overflow-hidden
                hover:shadow-xl hover:border-gray-200 dark:hover:border-gray-600
                transition-all duration-300 flex flex-col">

        {{-- Image Container --}}
        <a href="{{ route('marketplace.product.show', $product->slug) }}"
           class="relative aspect-square overflow-hidden bg-gray-50 dark:bg-gray-700/50">
            <img src="{{ $product->primary_image_url ?? asset('images/placeholder.png') }}"
                 alt="{{ $product->name }}"
                 loading="lazy"
                 class="w-full h-full object-cover transform group-hover:scale-105
                        transition-transform duration-500">

            {{-- Simple Badges (มุมบน) --}}
            @if($product->is_new ?? false)
            <span class="absolute top-3 left-3 px-2 py-1
                         bg-black/80 text-white text-xs font-medium rounded">
                NEW
            </span>
            @endif

            @if($product->discount_percentage ?? 0 > 0)
            <span class="absolute top-3 right-3 px-2 py-1
                         bg-red-500 text-white text-xs font-bold rounded">
                -{{ $product->discount_percentage }}%
            </span>
            @endif

            {{-- Wishlist Icon (มุมล่างขวา) --}}
            <button @click.prevent="toggleWishlist()"
                    class="absolute bottom-3 right-3 w-9 h-9
                           bg-white dark:bg-gray-800 rounded-full
                           flex items-center justify-center
                           opacity-0 group-hover:opacity-100
                           transform translate-y-2 group-hover:translate-y-0
                           transition-all duration-300 shadow-lg"
                    :class="{ 'text-red-500': isInWishlist, 'text-gray-400 dark:text-gray-500': !isInWishlist }">
                <i class="fas fa-heart text-sm"></i>
            </button>
        </a>

        {{-- Content --}}
        <div class="flex-1 p-4 flex flex-col">
            {{-- Category & Rating --}}
            <div class="flex items-center justify-between mb-2">
                @if($product->category)
                <a href="{{ route('marketplace.category', $product->category->slug) }}"
                   class="text-xs text-gray-500 dark:text-gray-400 hover:text-gray-700
                          dark:hover:text-gray-300 transition-colors">
                    {{ $product->category->name }}
                </a>
                @else
                <span></span>
                @endif

                {{-- Rating --}}
                @if($product->average_rating ?? 0 > 0)
                <div class="flex items-center gap-1">
                    <i class="fas fa-star text-yellow-400 text-xs"></i>
                    <span class="text-xs text-gray-600 dark:text-gray-400 font-medium">
                        {{ number_format($product->average_rating, 1) }}
                    </span>
                </div>
                @endif
            </div>

            {{-- Product Name --}}
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white
                       mb-2 line-clamp-2">
                <a href="{{ route('marketplace.product.show', $product->slug) }}"
                   class="hover:text-purple-600 dark:hover:text-purple-400 transition-colors">
                    {{ $product->name }}
                </a>
            </h3>

            {{-- PV & Commission (แบบเรียบง่าย) --}}
            @if(($showPv && ($product->pv_value ?? 0) > 0) || ($showCommission && ($product->commission_rate ?? 0) > 0))
            <div class="flex items-center gap-3 mb-3 text-xs">
                @if($showPv && ($product->pv_value ?? 0) > 0)
                <span class="text-blue-600 dark:text-blue-400 font-medium">
                    {{ number_format($product->pv_value) }} PV
                </span>
                @endif

                @if($showCommission && ($product->commission_rate ?? 0) > 0)
                <span class="text-green-600 dark:text-green-400 font-medium">
                    {{ $product->commission_rate }}% Com.
                </span>
                @endif
            </div>
            @endif

            {{-- Price & Add to Cart --}}
            <div class="mt-auto flex items-center justify-between gap-3">
                {{-- Price --}}
                <div class="flex flex-col">
                    @if($product->sale_price && $product->sale_price < $product->price)
                    <span class="text-lg font-bold text-gray-900 dark:text-white">
                        ฿{{ number_format($product->sale_price) }}
                    </span>
                    <span class="text-xs text-gray-400 dark:text-gray-500 line-through">
                        ฿{{ number_format($product->price) }}
                    </span>
                    @else
                    <span class="text-lg font-bold text-gray-900 dark:text-white">
                        ฿{{ number_format($product->price) }}
                    </span>
                    @endif
                </div>

                {{-- Add to Cart Icon Button --}}
                @if($product->stock_quantity > 0)
                <button @click="addToCart()"
                        :disabled="isAddingToCart"
                        class="w-10 h-10 bg-black dark:bg-white
                               text-white dark:text-black
                               rounded-full flex items-center justify-center
                               hover:bg-gray-800 dark:hover:bg-gray-100
                               transform hover:scale-110 active:scale-95
                               transition-all
                               disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="fas text-sm"
                       :class="isAddingToCart ? 'fa-spinner fa-spin' : 'fa-plus'"></i>
                </button>
                @else
                <span class="text-xs text-red-500 dark:text-red-400 font-medium">
                    หมดแล้ว
                </span>
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
