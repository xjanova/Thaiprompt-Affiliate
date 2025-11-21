{{--
    Product Card - Glassmorphism Style (V3)

    การ์ดสินค้าแบบ Glassmorphism พร้อม backdrop blur
    เหมาะสำหรับหน้าที่มี background สีสดหรือ gradient

    @props
    - product: Product model object
    - showPv: boolean (แสดง PV หรือไม่)
    - showCommission: boolean (แสดง Commission preview หรือไม่)

    @example
    <x-ecommerce.product-card-glass
        :product="$product"
        :showPv="true"
        :showCommission="false"
    />
--}}

@props([
    'product',
    'showPv' => true,
    'showCommission' => true,
])

<div x-data="productCardComponent({{ $product->id }})"
     class="group h-full">

    {{-- Glass Card Container --}}
    <div class="relative h-full overflow-hidden rounded-2xl
                transform hover:scale-105 transition-all duration-300">

        {{-- Background Gradient (แสดงผ่าน glass) --}}
        <div class="absolute inset-0 bg-gradient-to-br from-purple-400 via-pink-500 to-red-500
                    opacity-80 group-hover:opacity-100 transition-opacity"></div>

        {{-- Glass Layer --}}
        <div class="relative h-full bg-white/10 dark:bg-black/20 backdrop-blur-xl
                    border border-white/20 dark:border-white/10
                    flex flex-col">

            {{-- Image Container --}}
            <div class="relative aspect-square overflow-hidden">
                <img src="{{ $product->primary_image_url ?? asset('images/placeholder.png') }}"
                     alt="{{ $product->name }}"
                     loading="lazy"
                     class="w-full h-full object-cover transform group-hover:scale-110
                            transition-transform duration-700">

                {{-- Glass Overlay on Image --}}
                <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>

                {{-- Badges --}}
                <div class="absolute top-3 left-3 flex flex-col gap-2 z-10">
                    @if($product->is_new ?? false)
                    <span class="inline-flex items-center px-3 py-1
                                 bg-white/20 backdrop-blur-md border border-white/30
                                 text-white text-xs font-bold rounded-full">
                        <i class="fas fa-sparkles mr-1"></i>
                        ใหม่
                    </span>
                    @endif

                    @if($product->discount_percentage ?? 0 > 0)
                    <span class="inline-flex items-center px-3 py-1
                                 bg-red-500/80 backdrop-blur-md border border-white/30
                                 text-white text-xs font-bold rounded-full">
                        -{{ $product->discount_percentage }}%
                    </span>
                    @endif
                </div>

                {{-- Quick Actions --}}
                <div class="absolute top-3 right-3 flex flex-col gap-2 z-10">
                    <button @click.prevent="toggleWishlist()"
                            class="w-10 h-10 bg-white/20 backdrop-blur-md border border-white/30
                                   rounded-full flex items-center justify-center
                                   hover:bg-white/30 transform hover:scale-110 transition-all"
                            :class="{ 'text-red-400': isInWishlist, 'text-white': !isInWishlist }">
                        <i class="fas fa-heart"></i>
                    </button>

                    <button @click.prevent="quickView()"
                            class="w-10 h-10 bg-white/20 backdrop-blur-md border border-white/30
                                   text-white rounded-full flex items-center justify-center
                                   hover:bg-white/30 transform hover:scale-110 transition-all">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>

                {{-- Price Overlay (ล่างซ้าย) --}}
                <div class="absolute bottom-3 left-3 z-10">
                    @if($product->sale_price && $product->sale_price < $product->price)
                    <div class="flex flex-col">
                        <span class="text-2xl font-bold text-white drop-shadow-lg">
                            ฿{{ number_format($product->sale_price, 2) }}
                        </span>
                        <span class="text-sm text-white/80 line-through">
                            ฿{{ number_format($product->price, 2) }}
                        </span>
                    </div>
                    @else
                    <span class="text-2xl font-bold text-white drop-shadow-lg">
                        ฿{{ number_format($product->price, 2) }}
                    </span>
                    @endif
                </div>
            </div>

            {{-- Content --}}
            <div class="flex-1 p-4 flex flex-col">
                {{-- Category --}}
                @if($product->category)
                <a href="{{ route('marketplace.category', $product->category->slug) }}"
                   class="text-xs text-white/90 font-medium hover:underline mb-2 inline-block">
                    {{ $product->category->name }}
                </a>
                @endif

                {{-- Product Name --}}
                <h3 class="text-lg font-bold text-white mb-2 line-clamp-2
                           group-hover:text-yellow-200 transition-colors">
                    <a href="{{ route('marketplace.product.show', $product->slug) }}">
                        {{ $product->name }}
                    </a>
                </h3>

                {{-- Rating --}}
                @if($product->average_rating ?? 0 > 0)
                <div class="flex items-center gap-2 mb-3">
                    <div class="flex items-center text-yellow-300">
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

                {{-- PV & Commission Info --}}
                @if($showPv || $showCommission)
                <div class="flex flex-wrap gap-2 mb-3">
                    @if($showPv && ($product->pv_value ?? 0) > 0)
                    <span class="inline-flex items-center gap-1 px-2 py-1
                                 bg-white/20 backdrop-blur-md border border-white/30
                                 text-white text-xs font-medium rounded-lg">
                        <i class="fas fa-coins"></i>
                        <span>{{ number_format($product->pv_value) }} PV</span>
                    </span>
                    @endif

                    @if($showCommission && ($product->commission_rate ?? 0) > 0)
                    <span class="inline-flex items-center gap-1 px-2 py-1
                                 bg-white/20 backdrop-blur-md border border-white/30
                                 text-white text-xs font-medium rounded-lg">
                        <i class="fas fa-percent"></i>
                        <span>{{ $product->commission_rate }}%</span>
                    </span>
                    @endif
                </div>
                @endif

                {{-- Add to Cart Button --}}
                @if($product->stock_quantity > 0)
                <button @click="addToCart()"
                        :disabled="isAddingToCart"
                        class="w-full mt-auto px-4 py-3
                               bg-white/20 hover:bg-white/30 backdrop-blur-md
                               border border-white/30
                               text-white font-bold rounded-xl
                               transform hover:scale-105 active:scale-95
                               transition-all duration-300
                               disabled:opacity-50 disabled:cursor-not-allowed
                               flex items-center justify-center gap-2">
                    <i class="fas" :class="isAddingToCart ? 'fa-spinner fa-spin' : 'fa-cart-plus'"></i>
                    <span x-text="isAddingToCart ? 'กำลังเพิ่ม...' : 'เพิ่มลงตะกร้า'"></span>
                </button>
                @else
                <button disabled
                        class="w-full mt-auto px-4 py-3
                               bg-white/10 backdrop-blur-md border border-white/20
                               text-white/60 font-bold rounded-xl cursor-not-allowed">
                    <i class="fas fa-ban mr-2"></i>
                    สินค้าหมด
                </button>
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
