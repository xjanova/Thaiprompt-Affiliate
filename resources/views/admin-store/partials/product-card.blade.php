<div class="group bg-white rounded-2xl shadow-lg hover:shadow-2xl transform hover:-translate-y-2 transition-all duration-300 overflow-hidden border border-gray-100">
    <!-- Product Image -->
    <div class="relative bg-gradient-to-br from-gray-50 to-gray-100 aspect-square overflow-hidden">
        @if($product->main_image_url)
            <img src="{{ $product->main_image_url }}"
                 alt="{{ $product->name }}"
                 class="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-300">
        @else
            <div class="w-full h-full flex items-center justify-center text-gray-400">
                <span class="text-6xl">📦</span>
            </div>
        @endif

        <!-- Badges -->
        <div class="absolute top-4 left-4 flex flex-col gap-2">
            @if($featured ?? false)
                <span class="px-3 py-1 bg-gradient-to-r from-yellow-500 to-orange-500 text-white text-xs font-bold rounded-full shadow-lg">
                    ⭐ แนะนำ
                </span>
            @endif

            @if($product->compare_at_price && $product->compare_at_price > $product->price)
                @php
                    $discount = round((($product->compare_at_price - $product->price) / $product->compare_at_price) * 100);
                @endphp
                <span class="px-3 py-1 bg-gradient-to-r from-red-500 to-pink-500 text-white text-xs font-bold rounded-full shadow-lg">
                    -{{ $discount }}%
                </span>
            @endif

            @if($product->stock_status === 'out_of_stock')
                <span class="px-3 py-1 bg-gray-800 text-white text-xs font-bold rounded-full shadow-lg">
                    หมด
                </span>
            @elseif($product->stock_quantity < $product->low_stock_threshold)
                <span class="px-3 py-1 bg-orange-500 text-white text-xs font-bold rounded-full shadow-lg">
                    เหลือน้อย
                </span>
            @endif
        </div>

        <!-- Quick Actions -->
        <div class="absolute top-4 right-4 flex flex-col gap-2 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
            <button class="w-10 h-10 bg-white hover:bg-indigo-600 text-gray-700 hover:text-white rounded-full shadow-lg flex items-center justify-center transition-all transform hover:scale-110">
                👁️
            </button>
            <button class="w-10 h-10 bg-white hover:bg-pink-600 text-gray-700 hover:text-white rounded-full shadow-lg flex items-center justify-center transition-all transform hover:scale-110">
                ❤️
            </button>
        </div>

        <!-- Stock Status Bar -->
        @if($product->track_inventory && $product->stock_quantity > 0 && $product->stock_quantity < $product->low_stock_threshold)
            <div class="absolute bottom-0 left-0 right-0 bg-orange-500 text-white text-xs font-semibold py-1 px-3 text-center">
                เหลือเพียง {{ $product->stock_quantity }} ชิ้น
            </div>
        @endif
    </div>

    <!-- Product Info -->
    <div class="p-5">
        <!-- Category -->
        <div class="mb-2">
            <span class="inline-block px-3 py-1 bg-indigo-50 text-indigo-600 text-xs font-semibold rounded-full">
                {{ $product->category->name ?? 'ทั่วไป' }}
            </span>
        </div>

        <!-- Product Name -->
        <h3 class="text-lg font-bold text-gray-800 mb-2 line-clamp-2 min-h-[3.5rem] group-hover:text-indigo-600 transition-colors">
            {{ $product->name }}
        </h3>

        <!-- Rating & Sales -->
        <div class="flex items-center gap-3 mb-3 text-xs">
            <div class="flex items-center gap-1">
                <span class="text-yellow-500">⭐</span>
                <span class="font-semibold text-gray-700">{{ number_format($product->rating_average, 1) }}</span>
                <span class="text-gray-500">({{ $product->rating_count }})</span>
            </div>
            <div class="text-gray-400">•</div>
            <div class="text-gray-600">
                ขายแล้ว {{ $product->sales_count }} ชิ้น
            </div>
        </div>

        <!-- Price -->
        <div class="mb-4">
            @if($product->compare_at_price && $product->compare_at_price > $product->price)
                <div class="flex items-baseline gap-2 mb-1">
                    <span class="text-2xl font-black text-indigo-600">
                        ฿{{ number_format($product->price, 2) }}
                    </span>
                    <span class="text-sm text-gray-400 line-through">
                        ฿{{ number_format($product->compare_at_price, 2) }}
                    </span>
                </div>
            @else
                <div class="text-2xl font-black text-indigo-600">
                    ฿{{ number_format($product->price, 2) }}
                </div>
            @endif
        </div>

        <!-- Actions -->
        <div class="flex gap-2">
            <a href="{{ route('admin-store.show', $product->id) }}"
               class="flex-1 px-4 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-bold text-center rounded-xl shadow-lg hover:shadow-xl transform group-hover:scale-105 transition-all duration-200 text-sm">
                ดูรายละเอียด
            </a>
            <button class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl shadow hover:shadow-lg transition-all text-sm">
                🛒
            </button>
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
