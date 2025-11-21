{{--
    Product Card - Horizontal Compact Style (V3)

    การ์ดสินค้าแนวนอนแบบกระชับ
    เหมาะสำหรับ list view, search results, หรือ cart items

    @props
    - product: Product model object
    - showPv: boolean (แสดง PV หรือไม่)
    - showCommission: boolean (แสดง Commission preview หรือไม่)
    - compact: boolean (แสดงแบบกระชับยิ่งขึ้น)

    @example
    <x-ecommerce.product-card-horizontal
        :product="$product"
        :compact="false"
    />
--}}

@props([
    'product',
    'showPv' => true,
    'showCommission' => true,
    'compact' => false,
])

<div x-data="productCardComponent({{ $product->id }})"
     class="group bg-white dark:bg-gray-800
            border border-gray-200 dark:border-gray-700
            rounded-xl hover:shadow-lg
            transition-all duration-300">

    <div class="flex gap-4 {{ $compact ? 'p-3' : 'p-4' }}">
        {{-- Image --}}
        <a href="{{ route('marketplace.product.show', $product->slug) }}"
           class="relative flex-shrink-0 {{ $compact ? 'w-20 h-20' : 'w-24 h-24 sm:w-32 sm:h-32' }}
                  rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-700">
            <img src="{{ $product->primary_image_url ?? asset('images/placeholder.png') }}"
                 alt="{{ $product->name }}"
                 loading="lazy"
                 class="w-full h-full object-cover transform group-hover:scale-110
                        transition-transform duration-500">

            {{-- Badge Overlay --}}
            @if($product->discount_percentage ?? 0 > 0)
            <span class="absolute top-1 right-1 px-2 py-0.5
                         bg-red-500 text-white text-xs font-bold rounded">
                -{{ $product->discount_percentage }}%
            </span>
            @endif

            @if($product->stock_quantity <= 0)
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm
                        flex items-center justify-center">
                <span class="text-white text-xs font-bold">หมด</span>
            </div>
            @endif
        </a>

        {{-- Content --}}
        <div class="flex-1 min-w-0 flex flex-col {{ $compact ? 'gap-1' : 'gap-2' }}">
            {{-- Category & Rating --}}
            <div class="flex items-center justify-between gap-2">
                @if($product->category)
                <a href="{{ route('marketplace.category', $product->category->slug) }}"
                   class="text-xs text-purple-600 dark:text-purple-400 font-medium hover:underline">
                    {{ $product->category->name }}
                </a>
                @endif

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
            <h3 class="{{ $compact ? 'text-sm' : 'text-base' }} font-semibold
                       text-gray-900 dark:text-white
                       {{ $compact ? 'line-clamp-1' : 'line-clamp-2' }}">
                <a href="{{ route('marketplace.product.show', $product->slug) }}"
                   class="hover:text-purple-600 dark:hover:text-purple-400 transition-colors">
                    {{ $product->name }}
                </a>
            </h3>

            {{-- Short Description (ไม่แสดงใน compact mode) --}}
            @if(!$compact && $product->short_description)
            <p class="text-xs text-gray-600 dark:text-gray-400 line-clamp-2 hidden sm:block">
                {{ $product->short_description }}
            </p>
            @endif

            {{-- PV & Commission Badges --}}
            @if($showPv || $showCommission)
            <div class="flex flex-wrap gap-2">
                @if($showPv && ($product->pv_value ?? 0) > 0)
                <span class="inline-flex items-center gap-1 px-2 py-0.5
                             bg-blue-100 dark:bg-blue-900/30
                             text-blue-700 dark:text-blue-300
                             text-xs font-medium rounded">
                    <i class="fas fa-coins text-xs"></i>
                    <span>{{ number_format($product->pv_value) }} PV</span>
                </span>
                @endif

                @if($showCommission && ($product->commission_rate ?? 0) > 0)
                <span class="inline-flex items-center gap-1 px-2 py-0.5
                             bg-green-100 dark:bg-green-900/30
                             text-green-700 dark:text-green-300
                             text-xs font-medium rounded">
                    <i class="fas fa-percent text-xs"></i>
                    <span>{{ $product->commission_rate }}%</span>
                </span>
                @endif
            </div>
            @endif

            {{-- Price & Actions --}}
            <div class="mt-auto flex items-center justify-between gap-3">
                {{-- Price --}}
                <div class="flex flex-col">
                    @if($product->sale_price && $product->sale_price < $product->price)
                    <div class="flex items-baseline gap-2">
                        <span class="{{ $compact ? 'text-base' : 'text-lg' }} font-bold
                                     text-gray-900 dark:text-white">
                            ฿{{ number_format($product->sale_price) }}
                        </span>
                        <span class="text-xs text-gray-400 dark:text-gray-500 line-through">
                            ฿{{ number_format($product->price) }}
                        </span>
                    </div>
                    @else
                    <span class="{{ $compact ? 'text-base' : 'text-lg' }} font-bold
                                 text-gray-900 dark:text-white">
                        ฿{{ number_format($product->price) }}
                    </span>
                    @endif

                    {{-- Stock Info --}}
                    @if($product->stock_quantity > 0 && $product->stock_quantity <= 10)
                    <span class="text-xs text-orange-500 dark:text-orange-400">
                        เหลือ {{ $product->stock_quantity }} ชิ้น
                    </span>
                    @endif
                </div>

                {{-- Actions --}}
                <div class="flex items-center gap-2">
                    {{-- Wishlist --}}
                    <button @click.prevent="toggleWishlist()"
                            class="w-8 h-8 rounded-full
                                   hover:bg-gray-100 dark:hover:bg-gray-700
                                   flex items-center justify-center
                                   transition-colors"
                            :class="{ 'text-red-500': isInWishlist, 'text-gray-400 dark:text-gray-500': !isInWishlist }">
                        <i class="fas fa-heart text-sm"></i>
                    </button>

                    {{-- Add to Cart --}}
                    @if($product->stock_quantity > 0)
                    <button @click="addToCart()"
                            :disabled="isAddingToCart"
                            class="px-4 py-2 bg-gradient-to-r from-purple-600 to-pink-600
                                   hover:from-purple-700 hover:to-pink-700
                                   text-white text-sm font-medium rounded-lg
                                   transform hover:scale-105 active:scale-95
                                   transition-all
                                   disabled:opacity-50 disabled:cursor-not-allowed
                                   flex items-center gap-1.5">
                        <i class="fas text-xs"
                           :class="isAddingToCart ? 'fa-spinner fa-spin' : 'fa-cart-plus'"></i>
                        <span class="hidden sm:inline"
                              x-text="isAddingToCart ? 'กำลังเพิ่ม...' : 'เพิ่มลงตะกร้า'"></span>
                    </button>
                    @else
                    <button disabled
                            class="px-4 py-2 bg-gray-300 dark:bg-gray-700
                                   text-gray-500 dark:text-gray-400 text-sm font-medium
                                   rounded-lg cursor-not-allowed">
                        หมดแล้ว
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.line-clamp-1 {
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
