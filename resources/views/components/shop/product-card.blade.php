@props([
    'product',
    'featured' => false,
    'showQuickView' => true,
])

<div class="group relative bg-white rounded-2xl overflow-hidden shadow-md hover:shadow-2xl transition-all duration-300 border border-gray-100/50 hover:border-indigo-200">
    <a href="{{ route('shop.show', $product->slug) }}" class="block">
        <!-- Product Image -->
        <div class="relative bg-gradient-to-br from-gray-50 via-white to-gray-50 aspect-square overflow-hidden">
            @if($product->main_image_url)
                <img src="{{ $product->main_image_url }}"
                     alt="{{ $product->name }}"
                     loading="lazy"
                     class="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-500">
            @else
                <div class="w-full h-full flex items-center justify-center">
                    <svg class="w-24 h-24 text-gray-200" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/>
                    </svg>
                </div>
            @endif

            <!-- Overlay Gradient -->
            <div class="absolute inset-0 bg-gradient-to-t from-black/20 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>

            <!-- Top Badges -->
            <div class="absolute top-3 left-3 flex flex-col gap-2 z-10">
                @if($featured)
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-gradient-to-r from-amber-400 via-yellow-400 to-orange-400 text-white text-xs font-bold rounded-lg shadow-lg backdrop-blur-sm">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        แนะนำ
                    </span>
                @endif

                @if($product->compare_at_price && $product->compare_at_price > $product->price)
                    @php
                        $discount = round((($product->compare_at_price - $product->price) / $product->compare_at_price) * 100);
                    @endphp
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-gradient-to-r from-red-500 via-rose-500 to-pink-500 text-white text-xs font-bold rounded-lg shadow-lg backdrop-blur-sm">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM7 9a1 1 0 000 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/>
                        </svg>
                        -{{ $discount }}%
                    </span>
                @endif

                @if($product->stock_status === 'out_of_stock')
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-gray-900/90 text-white text-xs font-bold rounded-lg shadow-lg backdrop-blur-sm">
                        หมดสต็อก
                    </span>
                @elseif($product->track_inventory && $product->stock_quantity > 0 && $product->stock_quantity < $product->low_stock_threshold)
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-orange-500/90 text-white text-xs font-bold rounded-lg shadow-lg backdrop-blur-sm">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        เหลือน้อย
                    </span>
                @endif
            </div>

            <!-- Quick Actions -->
            @if($showQuickView)
                <div class="absolute top-3 right-3 flex flex-col gap-2 opacity-0 group-hover:opacity-100 transition-all duration-300 transform translate-x-2 group-hover:translate-x-0">
                    <button onclick="quickView(event, {{ $product->id }})"
                            class="w-9 h-9 bg-white/95 hover:bg-indigo-600 text-gray-700 hover:text-white rounded-xl shadow-lg flex items-center justify-center transition-all transform hover:scale-110 backdrop-blur-sm"
                            title="ดูด่วน">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                    <button onclick="toggleWishlist(event, {{ $product->id }})"
                            class="w-9 h-9 bg-white/95 hover:bg-pink-500 text-gray-700 hover:text-white rounded-xl shadow-lg flex items-center justify-center transition-all transform hover:scale-110 backdrop-blur-sm"
                            title="เพิ่มในรายการโปรด">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                        </svg>
                    </button>
                </div>
            @endif

            <!-- Stock Bar -->
            @if($product->track_inventory && $product->stock_quantity > 0 && $product->stock_quantity < $product->low_stock_threshold)
                <div class="absolute bottom-0 left-0 right-0">
                    <div class="bg-gradient-to-r from-orange-500 to-red-500 text-white text-xs font-semibold py-1.5 px-3 text-center backdrop-blur-sm">
                        <span class="inline-flex items-center gap-1">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z"/>
                            </svg>
                            เหลือเพียง {{ $product->stock_quantity }} ชิ้น - รีบสั่งเลย!
                        </span>
                    </div>
                </div>
            @endif
        </div>

        <!-- Product Info -->
        <div class="p-4">
            <!-- Category Badge -->
            @if($product->category)
                <div class="mb-2">
                    <span class="inline-block px-2.5 py-1 bg-gradient-to-r from-indigo-50 to-purple-50 text-indigo-600 text-xs font-semibold rounded-lg border border-indigo-100">
                        {{ $product->category->name }}
                    </span>
                </div>
            @endif

            <!-- Product Name -->
            <h3 class="text-base font-bold text-gray-900 mb-2 line-clamp-2 min-h-[2.5rem] group-hover:text-indigo-600 transition-colors leading-tight">
                {{ $product->name }}
            </h3>

            <!-- Brand -->
            @if($product->brand)
                <p class="text-xs text-gray-500 mb-2">
                    <span class="font-medium">ยี่ห้อ:</span> {{ $product->brand }}
                </p>
            @endif

            <!-- Rating & Sales -->
            <div class="flex items-center gap-2 mb-3 text-xs">
                @if($product->rating_average > 0)
                    <div class="flex items-center gap-1 bg-amber-50 px-2 py-1 rounded-lg">
                        <svg class="w-3.5 h-3.5 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        <span class="font-bold text-gray-900">{{ number_format($product->rating_average, 1) }}</span>
                    </div>
                @endif

                @if($product->rating_count > 0)
                    <span class="text-gray-500">({{ number_format($product->rating_count) }})</span>
                @endif

                @if($product->sales_count > 0)
                    <span class="text-gray-400">•</span>
                    <span class="text-gray-600">
                        ขายแล้ว <span class="font-semibold text-gray-900">{{ number_format($product->sales_count) }}</span>
                    </span>
                @endif
            </div>

            <!-- Price -->
            <div class="mb-3">
                @if($product->compare_at_price && $product->compare_at_price > $product->price)
                    <div class="flex items-baseline gap-2 flex-wrap">
                        <span class="text-2xl font-black text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-purple-600">
                            ฿{{ number_format($product->price, 2) }}
                        </span>
                        <span class="text-sm text-gray-400 line-through">
                            ฿{{ number_format($product->compare_at_price, 2) }}
                        </span>
                    </div>
                @else
                    <div class="text-2xl font-black text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-purple-600">
                        ฿{{ number_format($product->price, 2) }}
                    </div>
                @endif
            </div>

            <!-- PV Display -->
            @php
                $totalPv = 0;
                if ($product->mlmProductPv && $product->mlmProductPv->count() > 0) {
                    foreach ($product->mlmProductPv as $pv) {
                        $totalPv += $pv->pv_value;
                    }
                    $totalPv = $totalPv / $product->mlmProductPv->count(); // Average if multiple plans
                }
            @endphp
            <div class="mb-3">
                <div class="flex items-center gap-1.5 text-xs font-bold bg-gradient-to-r from-yellow-50 to-amber-50 px-2.5 py-1.5 rounded-lg border border-yellow-200">
                    <svg class="w-4 h-4 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                    <span class="text-yellow-800">PV: {{ number_format($totalPv, 2) }}</span>
                </div>
            </div>

            <!-- Free Shipping Badge -->
            @if($product->price >= 500)
                <div class="flex items-center gap-1.5 text-xs text-emerald-600 font-semibold mb-3 bg-emerald-50 px-2.5 py-1.5 rounded-lg border border-emerald-100">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/>
                        <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z"/>
                    </svg>
                    ส่งฟรี
                </div>
            @endif

            <!-- Cashback Badge -->
            @php
                use App\Services\CashbackService;
                use App\Services\WalletService;
                $cashbackService = new CashbackService(new WalletService());
                $cashbackInfo = $cashbackService->calculateProductCashback($product, $product->price, 1);
            @endphp
            @if($cashbackInfo && $cashbackInfo['cashback'] > 0)
                <div class="flex items-center gap-1.5 text-xs text-amber-700 font-bold mb-3 bg-gradient-to-r from-amber-50 to-orange-50 px-2.5 py-1.5 rounded-lg border border-amber-300">
                    <svg class="w-4 h-4 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/>
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/>
                    </svg>
                    <span>Cashback ฿{{ number_format($cashbackInfo['cashback'], 2) }}</span>
                </div>
            @endif
        </div>
    </a>

    <!-- Action Buttons -->
    <div class="px-4 pb-4 pt-0">
        <div class="flex gap-2">
            @if($product->stock_status === 'in_stock')
                <button onclick="addToCartQuick(event, {{ $product->id }})"
                        class="flex-1 px-4 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-bold text-sm text-center rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200 flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    เพิ่มลงตะกร้า
                </button>
            @else
                <button disabled
                        class="flex-1 px-4 py-2.5 bg-gray-200 text-gray-500 font-bold text-sm text-center rounded-xl cursor-not-allowed">
                    สินค้าหมด
                </button>
            @endif
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

<script>
function quickView(event, productId) {
    event.preventDefault();
    event.stopPropagation();
    // Implement quick view modal
    console.log('Quick view product:', productId);
}

function toggleWishlist(event, productId) {
    event.preventDefault();
    event.stopPropagation();
    // Implement wishlist toggle
    console.log('Toggle wishlist:', productId);
}

function addToCartQuick(event, productId) {
    event.preventDefault();
    event.stopPropagation();

    @guest
    if (confirm('กรุณาเข้าสู่ระบบเพื่อเพิ่มสินค้าลงตะกร้า')) {
        window.location.href = '{{ route("login") }}';
    }
    return;
    @endguest

    const button = event.currentTarget;
    const originalContent = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<svg class="animate-spin h-4 w-4 text-white mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';

    fetch('{{ route("cart.add") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            product_id: productId,
            quantity: 1,
            attributes: {}
        })
    })
    .then(response => response.json())
    .then(data => {
        // Show success message
        button.innerHTML = '<svg class="w-4 h-4 mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>';

        // Update cart count if element exists
        const cartCount = document.getElementById('cart-count');
        if (cartCount && data.cart_count) {
            cartCount.textContent = data.cart_count;
        }

        setTimeout(() => {
            button.innerHTML = originalContent;
            button.disabled = false;
        }, 2000);
    })
    .catch(error => {
        console.error('Error:', error);
        alert('เกิดข้อผิดพลาดในการเพิ่มสินค้าลงตะกร้า');
        button.innerHTML = originalContent;
        button.disabled = false;
    });
}
</script>
