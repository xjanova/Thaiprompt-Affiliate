{{--
    Product Card Component สำหรับ Coin Shop

    @param $product CoinShopProduct
    @param $featured bool (optional)
--}}

@php
    $featured = $featured ?? false;
    $typeColor = $product->type_color;
@endphp

<a href="{{ route('user.coin-shop.product', $product) }}"
   class="group bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 overflow-hidden {{ $featured ? 'ring-2 ring-yellow-400' : '' }}">

    {{-- Image Container --}}
    <div class="relative aspect-square bg-gray-100 dark:bg-gray-700 overflow-hidden">
        {{-- Product Image --}}
        @if($product->thumbnail)
            <img src="{{ $product->thumbnail_url }}"
                 alt="{{ $product->display_name }}"
                 class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
        @else
            <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-{{ $typeColor }}-100 to-{{ $typeColor }}-200 dark:from-{{ $typeColor }}-900 dark:to-{{ $typeColor }}-800">
                <span class="text-6xl">{{ $product->type_icon }}</span>
            </div>
        @endif

        {{-- Badges --}}
        <div class="absolute top-2 left-2 flex flex-col gap-1">
            @if($featured || $product->is_featured)
                <span class="px-2 py-1 text-xs font-bold bg-yellow-400 text-yellow-900 rounded-full">
                    ⭐ แนะนำ
                </span>
            @endif

            @if($product->is_new)
                <span class="px-2 py-1 text-xs font-bold bg-green-500 text-white rounded-full">
                    ใหม่
                </span>
            @endif

            @if($product->is_limited)
                <span class="px-2 py-1 text-xs font-bold bg-red-500 text-white rounded-full">
                    Limited
                </span>
            @endif

            @if($product->has_discount)
                <span class="px-2 py-1 text-xs font-bold bg-orange-500 text-white rounded-full">
                    -{{ $product->discount_percent }}%
                </span>
            @endif
        </div>

        {{-- Type Badge --}}
        <div class="absolute top-2 right-2">
            <span class="px-2 py-1 text-xs font-medium bg-{{ $typeColor }}-100 dark:bg-{{ $typeColor }}-900 text-{{ $typeColor }}-800 dark:text-{{ $typeColor }}-200 rounded-full">
                {{ $product->type_icon }} {{ $product->type_name }}
            </span>
        </div>

        {{-- Out of Stock Overlay --}}
        @if($product->is_out_of_stock)
            <div class="absolute inset-0 bg-black/60 flex items-center justify-center">
                <span class="px-4 py-2 bg-red-500 text-white font-bold rounded-lg">
                    สินค้าหมด
                </span>
            </div>
        @endif
    </div>

    {{-- Content --}}
    <div class="p-4">
        {{-- Name --}}
        <h3 class="font-semibold text-gray-900 dark:text-white group-hover:text-yellow-600 dark:group-hover:text-yellow-400 transition line-clamp-2 min-h-[48px]">
            {{ $product->display_name }}
        </h3>

        {{-- Description (short) --}}
        @if($product->display_description)
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                {{ Str::limit($product->display_description, 60) }}
            </p>
        @endif

        {{-- Price --}}
        <div class="mt-3 flex items-center justify-between">
            <div>
                @if($product->has_discount)
                    <span class="text-sm text-gray-400 line-through">
                        {{ number_format($product->original_price_coins, 0) }}
                    </span>
                @endif
                <div class="flex items-center gap-1">
                    <span class="text-lg font-bold text-yellow-600 dark:text-yellow-400">
                        {{ number_format($product->price_coins, 0) }}
                    </span>
                    <span class="text-sm text-yellow-600 dark:text-yellow-400">Coins</span>
                </div>
            </div>

            {{-- Stock Info --}}
            @if($product->stock_quantity !== null && $product->stock_quantity > 0 && $product->stock_quantity <= 10)
                <span class="text-xs text-red-500 font-medium">
                    เหลือ {{ $product->stock_quantity }}
                </span>
            @endif
        </div>

        {{-- Min Level Requirement --}}
        @if($product->min_level > 1)
            <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                ต้องการ Level {{ $product->min_level }}+
            </div>
        @endif

        {{-- Buy Button --}}
        <div class="mt-3">
            @if($product->is_purchasable)
                <span class="block w-full py-2 text-center text-sm font-semibold bg-gradient-to-r from-yellow-400 to-orange-500 text-white rounded-lg group-hover:from-yellow-500 group-hover:to-orange-600 transition">
                    ซื้อเลย
                </span>
            @else
                <span class="block w-full py-2 text-center text-sm font-semibold bg-gray-300 dark:bg-gray-600 text-gray-600 dark:text-gray-400 rounded-lg">
                    ไม่สามารถซื้อได้
                </span>
            @endif
        </div>
    </div>
</a>
