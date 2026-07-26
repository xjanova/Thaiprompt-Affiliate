{{--
    Flash Deals Component - สไตล์ AliExpress

    แสดงสินค้าลดราคาพร้อม countdown timer
    รองรับ Dark Mode และ Responsive

    @param Collection $products - สินค้าลดราคา
    @param string $endTime - เวลาสิ้นสุด (ISO format)
--}}

@props([
    'products' => collect(),
    'endTime' => now()->addHours(6)->toIso8601String(),
    'title' => 'Flash Deals',
])

<div x-data="flashDeals('{{ $endTime }}')"
     x-init="init()"
     class="relative overflow-hidden rounded-3xl
            bg-gradient-to-r from-red-600 via-orange-500 to-yellow-500
            dark:from-red-800 dark:via-orange-700 dark:to-yellow-700
            shadow-2xl">

    {{-- Animated Background Effects --}}
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute -top-20 -right-20 w-80 h-80 bg-white/10 rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute -bottom-10 -left-10 w-60 h-60 bg-white/10 rounded-full blur-3xl animate-pulse delay-500"></div>

        {{-- Lightning effects --}}
        <div class="absolute top-5 left-1/4 text-yellow-300 animate-bounce" style="animation-delay: 0ms;">
            <svg class="w-8 h-8 opacity-50" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/>
            </svg>
        </div>
        <div class="absolute top-10 right-1/3 text-yellow-300 animate-bounce" style="animation-delay: 300ms;">
            <svg class="w-6 h-6 opacity-40" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/>
            </svg>
        </div>
    </div>

    <div class="relative z-10 p-6">
        {{-- Header --}}
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
            <div class="flex items-center gap-4">
                {{-- Flash Icon with Animation --}}
                <div class="relative">
                    <div class="absolute inset-0 bg-yellow-400 rounded-2xl blur-xl opacity-50 animate-pulse"></div>
                    <div class="relative w-16 h-16 bg-gradient-to-br from-yellow-300 to-orange-400
                               rounded-2xl flex items-center justify-center
                               shadow-lg transform -rotate-6">
                        <svg class="w-10 h-10 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                </div>

                <div>
                    <h2 class="text-3xl md:text-4xl font-black text-white tracking-tight">
                        {{ $title }}
                    </h2>
                    <p class="text-white/80 text-sm font-medium mt-1">
                        ลดกระหน่ำ! ดีลเด็ดประจำวัน
                    </p>
                </div>
            </div>

            {{-- Countdown Timer --}}
            <div class="flex items-center gap-2 bg-white/10 backdrop-blur-lg
                       rounded-2xl px-6 py-3 border border-white/20">
                <span class="text-white text-sm font-semibold">สิ้นสุดใน</span>

                <div class="flex items-center gap-1">
                    {{-- Hours --}}
                    <div class="flex flex-col items-center">
                        <div class="w-12 h-12 bg-white rounded-xl flex items-center justify-center shadow-lg">
                            <span class="text-2xl font-black text-red-600" x-text="timeLeft.hours.toString().padStart(2, '0')">00</span>
                        </div>
                        <span class="text-white/70 text-xs mt-1">ชั่วโมง</span>
                    </div>

                    <span class="text-white text-2xl font-bold animate-pulse mx-1">:</span>

                    {{-- Minutes --}}
                    <div class="flex flex-col items-center">
                        <div class="w-12 h-12 bg-white rounded-xl flex items-center justify-center shadow-lg">
                            <span class="text-2xl font-black text-red-600" x-text="timeLeft.minutes.toString().padStart(2, '0')">00</span>
                        </div>
                        <span class="text-white/70 text-xs mt-1">นาที</span>
                    </div>

                    <span class="text-white text-2xl font-bold animate-pulse mx-1">:</span>

                    {{-- Seconds --}}
                    <div class="flex flex-col items-center">
                        <div class="w-12 h-12 bg-white rounded-xl flex items-center justify-center shadow-lg">
                            <span class="text-2xl font-black text-red-600" x-text="timeLeft.seconds.toString().padStart(2, '0')">00</span>
                        </div>
                        <span class="text-white/70 text-xs mt-1">วินาที</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Products Carousel --}}
        <div class="relative">
            {{-- Scroll Container --}}
            <div class="flex gap-4 overflow-x-auto pb-4 snap-x snap-mandatory custom-scrollbar-light"
                 x-ref="scrollContainer">

                @forelse($products as $product)
                @php
                    $discount = 0;
                    if ($product->compare_at_price && $product->compare_at_price > $product->price) {
                        $discount = round((($product->compare_at_price - $product->price) / $product->compare_at_price) * 100);
                    }
                    $soldPercent = min(100, ($product->sales_count / max(1, $product->stock_quantity + $product->sales_count)) * 100);
                    // รูปสำรองเมื่อสินค้าไม่มีรูป หรือรูปปลายทาง (Lazada CDN) โหลดไม่ขึ้น
                    $fallbackImage = asset('images/no-image.png');
                @endphp

                <div class="flex-shrink-0 w-48 md:w-56 snap-start group">
                    <a href="{{ route('shop.show', $product->slug ?: $product->id) }}" class="block">
                        <div class="bg-white dark:bg-gray-800 rounded-2xl overflow-hidden
                                   shadow-xl hover:shadow-2xl
                                   transform hover:scale-105 hover:-translate-y-2
                                   transition-all duration-300">

                            {{-- Image Container --}}
                            <div class="relative aspect-square overflow-hidden bg-gray-100 dark:bg-gray-700">
                                <img src="{{ $product->main_image_url ?: $fallbackImage }}"
                                     alt="รูปสินค้า {{ $product->name }}"
                                     width="224"
                                     height="224"
                                     class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                                     loading="lazy"
                                     decoding="async"
                                     onerror="this.onerror=null; this.src='{{ $fallbackImage }}';">

                                {{-- Discount Badge --}}
                                @if($discount > 0)
                                <div class="absolute top-2 left-2 z-10">
                                    <div class="relative">
                                        <div class="absolute inset-0 bg-red-600 rounded-lg blur-sm opacity-50"></div>
                                        <div class="relative px-3 py-1 bg-gradient-to-r from-red-600 to-orange-500
                                                   text-white text-sm font-black rounded-lg shadow-lg">
                                            -{{ $discount }}%
                                        </div>
                                    </div>
                                </div>
                                @endif

                                {{-- Flash Badge --}}
                                <div class="absolute top-2 right-2 z-10">
                                    <div class="w-8 h-8 bg-yellow-400 rounded-full flex items-center justify-center
                                               shadow-lg animate-pulse">
                                        <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            {{-- Product Info --}}
                            <div class="p-3">
                                {{-- Product Name --}}
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white
                                          line-clamp-2 mb-2 h-10
                                          group-hover:text-orange-600 dark:group-hover:text-orange-400
                                          transition-colors">
                                    {{ $product->name }}
                                </h3>

                                {{-- Prices --}}
                                <div class="flex items-baseline gap-2 mb-2">
                                    <span class="text-xl font-black text-red-600 dark:text-red-500">
                                        ฿{{ number_format($product->price, 0) }}
                                    </span>
                                    @if($product->compare_at_price && $product->compare_at_price > $product->price)
                                    <span class="text-xs text-gray-400 line-through">
                                        ฿{{ number_format($product->compare_at_price, 0) }}
                                    </span>
                                    @endif
                                </div>

                                {{-- Progress Bar (Sold) --}}
                                <div class="relative mb-2">
                                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                        <div class="h-full bg-gradient-to-r from-red-500 via-orange-500 to-yellow-500
                                                   rounded-full transition-all duration-500 relative"
                                             style="width: {{ $soldPercent }}%;">
                                            {{-- Fire Animation --}}
                                            <div class="absolute -right-1 top-1/2 -translate-y-1/2
                                                       w-4 h-4 bg-yellow-400 rounded-full
                                                       animate-pulse"></div>
                                        </div>
                                    </div>
                                    <div class="absolute inset-0 flex items-center justify-center">
                                        <span class="text-xs font-bold text-white drop-shadow-md">
                                            ขายแล้ว {{ number_format($product->sales_count) }}
                                        </span>
                                    </div>
                                </div>

                                {{-- Quick Buy Button --}}
                                <button type="button"
                                        onclick="event.preventDefault(); event.stopPropagation(); addToCartFlash({{ $product->id }})"
                                        class="w-full py-2 bg-gradient-to-r from-red-600 to-orange-500
                                              hover:from-red-700 hover:to-orange-600
                                              text-white text-sm font-bold rounded-xl
                                              shadow-lg hover:shadow-xl
                                              transform hover:scale-105
                                              transition-all">
                                    <span class="flex items-center justify-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                        </svg>
                                        ซื้อเลย
                                    </span>
                                </button>
                            </div>
                        </div>
                    </a>
                </div>
                @empty
                {{-- Empty State --}}
                <div class="w-full py-12 text-center text-white">
                    <svg class="w-16 h-16 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                    </svg>
                    <p class="text-lg font-semibold">ไม่มีสินค้า Flash Deal ในขณะนี้</p>
                    <p class="text-sm opacity-70 mt-1">กลับมาตรวจสอบอีกครั้งในภายหลัง</p>
                </div>
                @endforelse
            </div>

            {{-- Scroll Buttons --}}
            @if($products->count() > 4)
            <button @click="scrollLeft()"
                    class="absolute left-0 top-1/2 -translate-y-1/2 z-10
                          w-10 h-10 bg-white/90 dark:bg-gray-800/90
                          rounded-full shadow-lg
                          flex items-center justify-center
                          hover:scale-110 transition-transform
                          text-gray-700 dark:text-gray-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>

            <button @click="scrollRight()"
                    class="absolute right-0 top-1/2 -translate-y-1/2 z-10
                          w-10 h-10 bg-white/90 dark:bg-gray-800/90
                          rounded-full shadow-lg
                          flex items-center justify-center
                          hover:scale-110 transition-transform
                          text-gray-700 dark:text-gray-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
            @endif
        </div>

        {{-- View All Link --}}
        <div class="flex justify-center mt-6">
            <a href="{{ route('storefront.index', ['sort_by' => 'discount']) }}"
               class="inline-flex items-center gap-2 px-8 py-3
                     bg-white hover:bg-gray-100
                     text-red-600 font-bold text-sm
                     rounded-xl shadow-lg hover:shadow-xl
                     transform hover:scale-105
                     transition-all group">
                <span>ดู Flash Deals ทั้งหมด</span>
                <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
    </div>
</div>

<style>
/* Custom Scrollbar for Light Background */
.custom-scrollbar-light::-webkit-scrollbar {
    height: 6px;
}

.custom-scrollbar-light::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.2);
    border-radius: 10px;
}

.custom-scrollbar-light::-webkit-scrollbar-thumb {
    background: white;
    border-radius: 10px;
}

.custom-scrollbar-light::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.9);
}

/* Line Clamp */
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>

<script>
/**
 * Flash Deals Alpine Component
 *
 * จัดการ countdown timer และ scroll functionality
 *
 * @param {string} endTime - เวลาสิ้นสุดในรูปแบบ ISO
 */
function flashDeals(endTime) {
    return {
        endTime: new Date(endTime),
        timeLeft: {
            hours: 0,
            minutes: 0,
            seconds: 0
        },
        intervalId: null,

        /**
         * เริ่มต้น component
         */
        init() {
            this.updateCountdown();
            this.intervalId = setInterval(() => {
                this.updateCountdown();
            }, 1000);
        },

        /**
         * อัพเดท countdown
         */
        updateCountdown() {
            const now = new Date();
            const diff = this.endTime - now;

            if (diff <= 0) {
                this.timeLeft = { hours: 0, minutes: 0, seconds: 0 };
                if (this.intervalId) {
                    clearInterval(this.intervalId);
                }
                return;
            }

            this.timeLeft = {
                hours: Math.floor(diff / (1000 * 60 * 60)),
                minutes: Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60)),
                seconds: Math.floor((diff % (1000 * 60)) / 1000)
            };
        },

        /**
         * เลื่อนไปทางซ้าย
         */
        scrollLeft() {
            const container = this.$refs.scrollContainer;
            container.scrollBy({ left: -300, behavior: 'smooth' });
        },

        /**
         * เลื่อนไปทางขวา
         */
        scrollRight() {
            const container = this.$refs.scrollContainer;
            container.scrollBy({ left: 300, behavior: 'smooth' });
        },

        /**
         * ทำลาย component
         */
        destroy() {
            if (this.intervalId) {
                clearInterval(this.intervalId);
            }
        }
    };
}

/**
 * เพิ่มสินค้า Flash Deal ลงตะกร้า
 *
 * @param {number} productId - ID ของสินค้า
 */
function addToCartFlash(productId) {
    // ต้อง login ก่อน
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
</script>
