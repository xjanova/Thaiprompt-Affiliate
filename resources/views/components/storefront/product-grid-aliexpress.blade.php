{{--
    Product Grid Component - สไตล์ AliExpress

    แสดงสินค้าแบบ grid ด้วยการ์ดสไตล์ AliExpress
    รองรับ Dark Mode, Responsive, Lazy Loading

    @param Collection $products - รายการสินค้า
    @param string $columns - จำนวนคอลัมน์ (auto, 2, 3, 4, 5, 6)
--}}

@props([
    'products' => collect(),
    'columns' => 'auto',
    'showPv' => true,
    'showCommission' => false,
])

@php
    // กำหนด grid classes ตาม columns
    $gridClasses = match($columns) {
        '2' => 'grid-cols-2',
        '3' => 'grid-cols-2 md:grid-cols-3',
        '4' => 'grid-cols-2 md:grid-cols-3 lg:grid-cols-4',
        '5' => 'grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5',
        '6' => 'grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6',
        default => 'grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6'
    };

    // รูปสำรองเมื่อสินค้าไม่มีรูป หรือรูปปลายทาง (Lazada CDN) โหลดไม่ขึ้น
    $fallbackImage = asset('images/no-image.png');

    // รายการโปรดของผู้ใช้ปัจจุบัน — ดึงครั้งเดียวต่อการ render (query เดียว)
    // ใช้ตั้งสถานะหัวใจให้ตรงกับฐานข้อมูล ไม่ให้ "ลืม" หลังรีเฟรชหน้า
    $favoritedProductIds = [];
    if (auth()->check()) {
        // ⚠️ $products มักเป็น LengthAwarePaginator (getProducts ใช้ ->paginate())
        //    collect($paginator) จะได้ "เมทาดาทาของ paginator" (current_page/data/...) ไม่ใช่ตัวสินค้า
        //    → pluck('id') ได้ค่าว่าง หัวใจเลยไม่เคยติดสถานะถูกใจเลย ต้องดึง collection จริงออกมาก่อน
        $productItems = method_exists($products, 'getCollection')
            ? $products->getCollection()
            : collect($products);
        $productIds = $productItems->pluck('id')->filter()->values()->all();
        if (! empty($productIds)) {
            $favoritedProductIds = \App\Models\ProductFavorite::query()
                ->where('user_id', auth()->id())
                ->whereIn('product_id', $productIds)
                ->pluck('product_id')
                ->all();
        }
    }
@endphp

<div class="grid {{ $gridClasses }} gap-3 md:gap-4">
    @forelse($products as $product)
    @php
        $discount = 0;
        if ($product->compare_at_price && $product->compare_at_price > $product->price) {
            $discount = round((($product->compare_at_price - $product->price) / $product->compare_at_price) * 100);
        }

        // คำนวณ PV — ใช้ mlm_product_pv ถ้ามี ไม่งั้น fallback ไป pv_value บนสินค้า (สินค้า affiliate)
        $totalPv = 0;
        if ($product->mlmProductPv && $product->mlmProductPv->count() > 0) {
            foreach ($product->mlmProductPv as $pv) {
                $totalPv += $pv->pv_value;
            }
            $totalPv = $totalPv / $product->mlmProductPv->count();
        }
        // สินค้า affiliate — ปุ่ม/ลิงก์วิ่งไปแพลตฟอร์มนอก (Lazada/AliExpress) ไม่เข้าตะกร้าเรา
        $isAffiliate = (bool) ($product->is_affiliate ?? false) && ! empty($product->affiliate_url);
        // affiliate เก็บ PV ที่คอลัมน์ pv_value (ไม่มี mlm_product_pv) → fallback เฉพาะ affiliate กันกระทบสินค้าปกติ
        if ($totalPv <= 0 && $isAffiliate) {
            $totalPv = (float) ($product->pv_value ?? 0);
        }
        $affiliateUrl = (string) ($product->affiliate_url ?? '');
        $platform = (string) ($product->external_platform ?? '');
        $platformLabel = $platform === 'aliexpress' ? 'AliExpress' : 'Lazada';
        $targetUrl = $isAffiliate ? $affiliateUrl : route('shop.show', $product->slug ?: $product->id);
        // สินค้านี้อยู่ในรายการโปรดของผู้ใช้แล้วหรือยัง
        $isFavorited = in_array($product->id, $favoritedProductIds);
    @endphp

    <div class="group">
        <a href="{{ $targetUrl }}"
           @if($isAffiliate) target="_blank" rel="noopener nofollow sponsored" @endif
           class="block bg-white dark:bg-gray-800
                 rounded-xl md:rounded-2xl overflow-hidden
                 shadow hover:shadow-xl
                 transform hover:-translate-y-1
                 transition-all duration-300
                 border border-gray-100 dark:border-gray-700
                 hover:border-orange-200 dark:hover:border-orange-700">

            {{-- Product Image --}}
            <div class="relative aspect-square overflow-hidden bg-gray-100 dark:bg-gray-700">
                <img src="{{ $product->main_image_url ?: $fallbackImage }}"
                     alt="รูปสินค้า {{ $product->name }}"
                     width="300"
                     height="300"
                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                     loading="lazy"
                     decoding="async"
                     onerror="this.onerror=null; this.src='{{ $fallbackImage }}';">

                {{-- Top Badges --}}
                <div class="absolute top-2 left-2 flex flex-col gap-1 z-10">
                    {{-- Platform Badge (affiliate) --}}
                    @if($isAffiliate)
                    <span class="px-2 py-0.5 text-white text-xs font-bold rounded"
                          style="background: {{ $platform === 'aliexpress' ? '#e62e04' : '#0f146d' }};">
                        {{ $platformLabel }}
                    </span>
                    @endif

                    {{-- Discount Badge --}}
                    @if($discount > 0)
                    <span class="px-2 py-0.5 bg-red-500 text-white text-xs font-bold rounded">
                        -{{ $discount }}%
                    </span>
                    @endif

                    {{-- Official Badge --}}
                    @if(!$product->seller_id)
                    <span class="px-2 py-0.5 bg-gradient-to-r from-orange-500 to-red-500
                               text-white text-xs font-bold rounded
                               flex items-center gap-0.5">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        Official
                    </span>
                    @endif

                    {{-- Featured Badge --}}
                    @if($product->is_featured)
                    <span class="px-2 py-0.5 bg-gradient-to-r from-yellow-400 to-orange-400
                               text-white text-xs font-bold rounded">
                        HOT
                    </span>
                    @endif
                </div>

                {{-- Wishlist Button (ผูกกับ user.interactions.products.favorite จริง) --}}
                {{-- บนมือถือไม่มี hover → แสดงตลอด, เดสก์ท็อปค่อยโผล่ตอน hover (ยกเว้นที่ถูกใจแล้ว) --}}
                <button type="button"
                        data-favorited="{{ $isFavorited ? '1' : '0' }}"
                        aria-pressed="{{ $isFavorited ? 'true' : 'false' }}"
                        aria-label="{{ $isFavorited ? 'นำออกจากรายการโปรด' : 'เพิ่มในรายการโปรด' }}"
                        title="{{ $isFavorited ? 'นำออกจากรายการโปรด' : 'เพิ่มในรายการโปรด' }}"
                        onclick="event.preventDefault(); event.stopPropagation(); toggleWishlistAli({{ $product->id }}, this)"
                        class="absolute top-2 right-2 z-10
                              w-8 h-8 rounded-full
                              bg-white/80 hover:bg-white
                              dark:bg-gray-900/80 dark:hover:bg-gray-900
                              flex items-center justify-center
                              shadow-md hover:shadow-lg
                              opacity-100 md:group-hover:opacity-100
                              transition-all duration-300
                              {{ $isFavorited ? 'md:opacity-100' : 'md:opacity-0' }}">
                    <svg class="w-4 h-4 {{ $isFavorited ? 'text-red-500' : 'text-gray-600 dark:text-gray-400' }} hover:text-red-500"
                         fill="{{ $isFavorited ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                    </svg>
                </button>

                {{-- Shipping Badge --}}
                @if($isAffiliate)
                    @if(($product->shipping_speed ?? '') === 'slow')
                    <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-r from-amber-500 to-orange-500 text-white text-xs font-semibold py-1 px-2 text-center">
                        🔴 ส่งช้า (จากจีน)
                    </div>
                    @else
                    <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-r from-green-500 to-emerald-500 text-white text-xs font-semibold py-1 px-2 text-center">
                        🟢 ส่งไว (ในไทย)
                    </div>
                    @endif
                @else
                @php
                    $shippingMethod = $product->shipping_method ?? 'store_default';
                    $isFreeShipping = $shippingMethod === 'free' || ($shippingMethod === 'store_default' && $product->price >= 500);
                @endphp
                @if($isFreeShipping)
                <div class="absolute bottom-0 left-0 right-0
                           bg-gradient-to-r from-green-500 to-emerald-500
                           text-white text-xs font-semibold
                           py-1 px-2 text-center">
                    <span class="flex items-center justify-center gap-1">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/>
                            <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z"/>
                        </svg>
                        จัดส่งฟรี
                    </span>
                </div>
                @elseif($shippingMethod === 'flat_rate' && ($product->shipping_fee ?? 0) > 0)
                <div class="absolute bottom-0 left-0 right-0
                           bg-gradient-to-r from-sky-500 to-blue-500
                           text-white text-xs font-semibold
                           py-1 px-2 text-center">
                    <span class="flex items-center justify-center gap-1">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/>
                            <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z"/>
                        </svg>
                        ค่าส่ง ฿{{ number_format($product->shipping_fee, 0) }}
                    </span>
                </div>
                @endif
                @endif
            </div>

            {{-- Product Info --}}
            <div class="p-2 md:p-3">
                {{-- Product Name --}}
                <h3 class="text-xs md:text-sm font-medium text-gray-800 dark:text-gray-200
                          line-clamp-2 mb-1.5 min-h-[2.5rem]
                          group-hover:text-orange-600 dark:group-hover:text-orange-400
                          transition-colors leading-tight">
                    {{ $product->name }}
                </h3>

                {{-- Price Section --}}
                <div class="flex items-baseline gap-1.5 mb-1.5">
                    <span class="text-sm md:text-lg font-bold text-red-600 dark:text-red-500">
                        ฿{{ number_format($product->price, 0) }}
                    </span>
                    @if($product->compare_at_price && $product->compare_at_price > $product->price)
                    <span class="text-xs text-gray-400 line-through">
                        ฿{{ number_format($product->compare_at_price, 0) }}
                    </span>
                    @endif
                </div>

                {{-- Rating & Sales --}}
                <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 mb-1.5">
                    @if($product->rating_average > 0)
                    <div class="flex items-center gap-0.5">
                        <svg class="w-3 h-3 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        <span>{{ number_format($product->rating_average, 1) }}</span>
                    </div>
                    @endif

                    @if($product->sales_count > 0)
                    <span>{{ number_format($product->sales_count) }} ขายแล้ว</span>
                    @endif
                </div>

                {{-- PV Badge --}}
                @if($showPv && $totalPv > 0)
                <div class="flex items-center gap-1 text-xs">
                    <span class="px-1.5 py-0.5 bg-yellow-100 dark:bg-yellow-900/30
                               text-yellow-700 dark:text-yellow-400
                               rounded font-semibold">
                        PV: {{ number_format($totalPv, 0) }}
                    </span>
                </div>
                @endif

                {{-- Commission Badge --}}
                @if(($showCommission || $isAffiliate) && $product->commission_rate > 0)
                <div class="flex items-center gap-1 text-xs mt-1">
                    <span class="px-1.5 py-0.5 bg-green-100 dark:bg-green-900/30
                               text-green-700 dark:text-green-400
                               rounded font-semibold">
                        คอมมิชชั่น {{ number_format($product->commission_rate, 0) }}%
                    </span>
                </div>
                @endif
            </div>
        </a>

        {{-- Quick action — มือถือแสดงตลอด (ไม่มี hover ให้กด), เดสก์ท็อปโผล่ตอน hover --}}
        <div class="mt-2 opacity-100 md:opacity-0 md:group-hover:opacity-100
                   transform translate-y-0 md:translate-y-2 md:group-hover:translate-y-0
                   transition-all duration-300">
            @if($isAffiliate)
            {{-- สินค้า affiliate → วิ่งไปซื้อที่แพลตฟอร์มต้นทาง (ได้ค่าคอม/PV) --}}
            <a href="{{ $affiliateUrl }}" target="_blank" rel="noopener nofollow sponsored"
               class="block w-full py-2 text-center text-white text-sm font-semibold rounded-lg shadow hover:shadow-lg transition-all"
               style="background: {{ $platform === 'aliexpress' ? '#e62e04' : '#0f146d' }};">
                <span class="flex items-center justify-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                    ดูที่ {{ $platformLabel }}
                </span>
            </a>
            @else
            <button type="button"
                    onclick="addToCartAli({{ $product->id }})"
                    class="w-full py-2 text-center
                          bg-gradient-to-r from-orange-500 to-red-500
                          hover:from-orange-600 hover:to-red-600
                          text-white text-sm font-semibold rounded-lg
                          shadow hover:shadow-lg
                          transition-all">
                <span class="flex items-center justify-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <span class="hidden sm:inline">เพิ่มลงตะกร้า</span>
                    <span class="sm:hidden">ซื้อ</span>
                </span>
            </button>
            @endif
        </div>
    </div>
    @empty
    {{-- Empty State --}}
    <div class="col-span-full py-16 text-center">
        <div class="w-24 h-24 mx-auto mb-6 rounded-full
                   bg-gray-100 dark:bg-gray-800
                   flex items-center justify-center">
            <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
            </svg>
        </div>
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
            ไม่พบสินค้า
        </h3>
        <p class="text-gray-500 dark:text-gray-400">
            ลองค้นหาด้วยคำค้นอื่น หรือเปลี่ยนตัวกรอง
        </p>
    </div>
    @endforelse
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
/**
 * เพิ่มสินค้าลงตะกร้า (AliExpress style)
 *
 * @param {number} productId - ID ของสินค้า
 */
function addToCartAli(productId) {
    @guest
    if (confirm('กรุณาเข้าสู่ระบบเพื่อเพิ่มสินค้าลงตะกร้า')) {
        window.location.href = '{{ route("login") }}';
    }
    return;
    @endguest

    fetch('{{ route("cart.add") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            product_id: productId,
            quantity: 1
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // แสดง notification
            window.dispatchEvent(new CustomEvent('notify', {
                detail: { message: 'เพิ่มสินค้าลงตะกร้าสำเร็จ!', type: 'success' }
            }));

            // แจ้ง component ที่ฟังอยู่ (cart-drawer) ให้โหลดจำนวน/รายการใหม่
            // ⚠️ หน้านี้ไม่มี element #cart-count จริง การเขียน DOM ตรง ๆ จึงไม่มีผลอะไรเลย
            window.dispatchEvent(new CustomEvent('cart-updated'));
        } else {
            throw new Error(data.message || 'เกิดข้อผิดพลาด');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert(error.message || 'ไม่สามารถเพิ่มสินค้าลงตะกร้าได้');
    });
}

/**
 * ตั้งสถานะหน้าตาของปุ่มหัวใจ (รายการโปรด)
 *
 * @param {HTMLElement} button - ปุ่มหัวใจ
 * @param {boolean} favorited - อยู่ในรายการโปรดหรือไม่
 */
function applyWishlistStateAli(button, favorited) {
    const label = favorited ? 'นำออกจากรายการโปรด' : 'เพิ่มในรายการโปรด';

    button.dataset.favorited = favorited ? '1' : '0';
    button.setAttribute('aria-pressed', favorited ? 'true' : 'false');
    button.setAttribute('aria-label', label);
    button.setAttribute('title', label);

    // ที่ถูกใจแล้วต้องเห็นตลอดบนเดสก์ท็อป ไม่ซ่อนรอ hover
    button.classList.toggle('md:opacity-100', favorited);
    button.classList.toggle('md:opacity-0', !favorited);

    const svg = button.querySelector('svg');
    if (!svg) {
        return;
    }

    if (favorited) {
        svg.setAttribute('fill', 'currentColor');
        svg.classList.add('text-red-500');
        svg.classList.remove('text-gray-600', 'dark:text-gray-400');
    } else {
        svg.setAttribute('fill', 'none');
        svg.classList.remove('text-red-500');
        svg.classList.add('text-gray-600', 'dark:text-gray-400');
    }
}

/**
 * Toggle รายการโปรด (wishlist) — บันทึกลงตาราง product_favorites จริง
 *
 * @param {number} productId - ID ของสินค้า
 * @param {HTMLElement} button - ปุ่มที่กด
 */
function toggleWishlistAli(productId, button) {
    @guest
    if (confirm('กรุณาเข้าสู่ระบบเพื่อบันทึกสินค้าที่ชอบ')) {
        window.location.href = '{{ route("login") }}';
    }
    return;
    @endguest

    // กันกดรัว (double-tap) ไม่ให้ยิงซ้อนกันจนสถานะสลับมั่ว
    if (button.dataset.busy === '1') {
        return;
    }
    button.dataset.busy = '1';

    const wasFavorited = button.dataset.favorited === '1';

    // อัพเดทหน้าจอทันที (optimistic) แล้วค่อยยืนยันกับเซิร์ฟเวอร์
    applyWishlistStateAli(button, !wasFavorited);

    const url = '{{ route('user.interactions.products.favorite', ['product' => '__PRODUCT_ID__']) }}'
        .replace('__PRODUCT_ID__', productId);

    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: JSON.stringify({})
    })
    .then(response => {
        // บัญชีที่ไม่มีสิทธิ์ (เช่น seller) จะโดน redirect เป็น HTML ไม่ใช่ JSON
        const contentType = response.headers.get('content-type') || '';
        if (!response.ok || !contentType.includes('application/json')) {
            throw new Error('ไม่สามารถบันทึกรายการโปรดได้');
        }
        return response.json();
    })
    .then(data => {
        // ยึดสถานะจากเซิร์ฟเวอร์เป็นหลัก
        const favorited = !!data.favorited;
        applyWishlistStateAli(button, favorited);

        window.dispatchEvent(new CustomEvent('notify', {
            detail: {
                message: data.message || (favorited ? 'เพิ่มในรายการโปรดแล้ว' : 'นำออกจากรายการโปรดแล้ว'),
                type: 'success'
            }
        }));
    })
    .catch(error => {
        // ล้มเหลว → ย้อนสถานะกลับ ไม่หลอกว่าบันทึกสำเร็จ
        applyWishlistStateAli(button, wasFavorited);

        window.dispatchEvent(new CustomEvent('notify', {
            detail: { message: error.message || 'ไม่สามารถบันทึกรายการโปรดได้', type: 'error' }
        }));
    })
    .finally(() => {
        button.dataset.busy = '0';
    });
}
</script>
