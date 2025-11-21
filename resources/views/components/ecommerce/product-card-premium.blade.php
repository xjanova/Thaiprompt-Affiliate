{{--
    Product Card - Premium 3D Style (V3)

    การ์ดสินค้าแบบ Premium พร้อม 3D hover effect
    รองรับ PV display และ Commission preview สำหรับ MLM/Affiliate

    @props
    - product: Product model object
    - showPv: boolean (แสดง PV หรือไม่)
    - showCommission: boolean (แสดง Commission preview หรือไม่)

    @example
    <x-ecommerce.product-card-premium
        :product="$product"
        :showPv="true"
        :showCommission="true"
    />
--}}

@props([
    'product',
    'showPv' => true,
    'showCommission' => true,
])

<div x-data="productCardComponent({{ $product->id }})"
     class="group perspective-1000 h-full">

    <div class="relative h-full transform-gpu transition-all duration-500
                group-hover:scale-105 group-hover:rotate-y-2">

        {{-- Glow Effect --}}
        <div class="absolute inset-0 bg-gradient-to-br from-purple-500 to-pink-500
                    rounded-2xl blur-xl opacity-0 group-hover:opacity-50
                    transition-opacity duration-500"></div>

        {{-- Card Body --}}
        <div class="relative h-full bg-white dark:bg-gray-800 rounded-2xl shadow-xl
                    hover:shadow-2xl border border-gray-200 dark:border-gray-700
                    overflow-hidden transition-all duration-300 flex flex-col">

            {{-- Image Container --}}
            <div class="relative aspect-square overflow-hidden bg-gray-100 dark:bg-gray-700">
                <img src="{{ $product->primary_image_url ?? asset('images/placeholder.png') }}"
                     alt="{{ $product->name }}"
                     loading="lazy"
                     class="w-full h-full object-cover transform group-hover:scale-110
                            transition-transform duration-500">

                {{-- Badges --}}
                <div class="absolute top-3 left-3 flex flex-col gap-2">
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
                </div>

                {{-- Quick Actions --}}
                <div class="absolute top-3 right-3 flex flex-col gap-2
                            opacity-0 group-hover:opacity-100 transition-opacity">
                    {{-- Wishlist --}}
                    <button @click.prevent="toggleWishlist()"
                            class="w-10 h-10 bg-white/90 dark:bg-gray-800/90 backdrop-blur-sm
                                   rounded-full flex items-center justify-center
                                   hover:bg-white dark:hover:bg-gray-700
                                   transform hover:scale-110 transition-all shadow-lg"
                            :class="{ 'text-red-500': isInWishlist, 'text-gray-400': !isInWishlist }">
                        <i class="fas fa-heart"></i>
                    </button>

                    {{-- Quick View --}}
                    <button @click.prevent="quickView()"
                            class="w-10 h-10 bg-white/90 dark:bg-gray-800/90 backdrop-blur-sm
                                   text-gray-600 dark:text-gray-300 rounded-full
                                   flex items-center justify-center
                                   hover:bg-white dark:hover:bg-gray-700
                                   transform hover:scale-110 transition-all shadow-lg">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>

                {{-- Out of Stock Overlay --}}
                @if($product->stock_quantity <= 0)
                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm
                            flex items-center justify-center">
                    <span class="text-white text-lg font-bold">สินค้าหมด</span>
                </div>
                @endif
            </div>

            {{-- Content --}}
            <div class="flex-1 p-4 flex flex-col">
                {{-- Category --}}
                @if($product->category)
                <a href="{{ route('marketplace.category', $product->category->slug) }}"
                   class="text-xs text-purple-600 dark:text-purple-400 font-medium
                          hover:underline mb-2 inline-block">
                    {{ $product->category->name }}
                </a>
                @endif

                {{-- Product Name --}}
                <h3 class="text-base md:text-lg font-bold text-gray-900 dark:text-white
                           mb-2 line-clamp-2 group-hover:text-purple-600
                           dark:group-hover:text-purple-400 transition-colors">
                    <a href="{{ route('marketplace.product.show', $product->slug) }}">
                        {{ $product->name }}
                    </a>
                </h3>

                {{-- Rating (ถ้ามี) --}}
                @if($product->average_rating ?? 0 > 0)
                <div class="flex items-center gap-2 mb-3">
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
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        ({{ $product->reviews_count ?? 0 }})
                    </span>
                </div>
                @endif

                {{-- PV & Commission Info --}}
                @if($showPv || $showCommission)
                <div class="flex flex-wrap gap-2 mb-3">
                    {{-- PV Badge --}}
                    @if($showPv && ($product->pv_value ?? 0) > 0)
                    <span class="inline-flex items-center gap-1 px-2 py-1
                                 bg-blue-100 dark:bg-blue-900/30
                                 text-blue-700 dark:text-blue-300
                                 text-xs font-medium rounded-lg">
                        <i class="fas fa-coins"></i>
                        <span>{{ number_format($product->pv_value) }} PV</span>
                    </span>
                    @endif

                    {{-- Commission Preview --}}
                    @if($showCommission && ($product->commission_rate ?? 0) > 0)
                    <span class="inline-flex items-center gap-1 px-2 py-1
                                 bg-green-100 dark:bg-green-900/30
                                 text-green-700 dark:text-green-300
                                 text-xs font-medium rounded-lg">
                        <i class="fas fa-percent"></i>
                        <span>{{ $product->commission_rate }}% Commission</span>
                    </span>
                    @endif
                </div>
                @endif

                {{-- Price --}}
                <div class="mb-4 mt-auto">
                    @if($product->sale_price && $product->sale_price < $product->price)
                    <div class="flex items-baseline gap-2">
                        <span class="text-2xl font-bold bg-gradient-to-r
                                     from-purple-600 to-pink-600
                                     bg-clip-text text-transparent">
                            ฿{{ number_format($product->sale_price, 2) }}
                        </span>
                        <span class="text-sm text-gray-400 dark:text-gray-500 line-through">
                            ฿{{ number_format($product->price, 2) }}
                        </span>
                    </div>
                    @else
                    <span class="text-2xl font-bold bg-gradient-to-r
                                 from-purple-600 to-pink-600
                                 bg-clip-text text-transparent">
                        ฿{{ number_format($product->price, 2) }}
                    </span>
                    @endif
                </div>

                {{-- Add to Cart Button --}}
                @if($product->stock_quantity > 0)
                <button @click="addToCart()"
                        :disabled="isAddingToCart"
                        class="w-full px-4 py-3
                               bg-gradient-to-r from-purple-600 via-pink-600 to-red-500
                               hover:from-purple-700 hover:via-pink-700 hover:to-red-600
                               text-white font-bold rounded-xl
                               shadow-lg hover:shadow-xl
                               transform hover:scale-105 active:scale-95
                               transition-all duration-300
                               disabled:opacity-50 disabled:cursor-not-allowed
                               disabled:transform-none
                               flex items-center justify-center gap-2">
                    <i class="fas" :class="isAddingToCart ? 'fa-spinner fa-spin' : 'fa-cart-plus'"></i>
                    <span x-text="isAddingToCart ? 'กำลังเพิ่ม...' : 'เพิ่มลงตะกร้า'"></span>
                </button>
                @else
                <button disabled
                        class="w-full px-4 py-3 bg-gray-300 dark:bg-gray-700
                               text-gray-500 dark:text-gray-400 font-bold rounded-xl
                               cursor-not-allowed">
                    <i class="fas fa-ban mr-2"></i>
                    สินค้าหมด
                </button>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
.perspective-1000 {
    perspective: 1000px;
}

.rotate-y-2 {
    transform: rotateY(2deg);
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>

@push('scripts')
<script>
/**
 * Product Card Component - จัดการ interactive สำหรับการ์ดสินค้า
 *
 * @param {number} productId - ID ของสินค้า
 * @returns {object} Alpine component object
 */
function productCardComponent(productId) {
    return {
        productId: productId,
        isInWishlist: false,
        isAddingToCart: false,

        /**
         * เริ่มต้น component
         */
        init() {
            this.checkWishlist();
        },

        /**
         * ตรวจสอบว่าสินค้าอยู่ใน wishlist หรือไม่
         */
        checkWishlist() {
            // ตรวจสอบจาก localStorage
            const wishlist = JSON.parse(localStorage.getItem('wishlist') || '[]');
            this.isInWishlist = wishlist.includes(this.productId);
        },

        /**
         * Toggle wishlist
         */
        toggleWishlist() {
            let wishlist = JSON.parse(localStorage.getItem('wishlist') || '[]');

            if (this.isInWishlist) {
                wishlist = wishlist.filter(id => id !== this.productId);
                this.$dispatch('notify', {
                    message: 'ลบออกจากรายการโปรดแล้ว',
                    type: 'info'
                });
            } else {
                wishlist.push(this.productId);
                this.$dispatch('notify', {
                    message: 'เพิ่มในรายการโปรดแล้ว',
                    type: 'success'
                });
            }

            localStorage.setItem('wishlist', JSON.stringify(wishlist));
            this.isInWishlist = !this.isInWishlist;
        },

        /**
         * เพิ่มสินค้าลงตะกร้า
         */
        async addToCart() {
            this.isAddingToCart = true;

            try {
                const response = await fetch('/api/cart/add', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        product_id: this.productId,
                        quantity: 1
                    })
                });

                if (response.ok) {
                    // Dispatch event เพื่ออัพเดท cart count
                    this.$dispatch('cart-updated');

                    // แสดงการแจ้งเตือน
                    this.$dispatch('notify', {
                        message: 'เพิ่มสินค้าลงตะกร้าแล้ว',
                        type: 'success'
                    });
                } else {
                    throw new Error('ไม่สามารถเพิ่มสินค้าได้');
                }
            } catch (error) {
                console.error('Add to cart error:', error);
                this.$dispatch('notify', {
                    message: 'เกิดข้อผิดพลาด กรุณาลองใหม่',
                    type: 'error'
                });
            } finally {
                this.isAddingToCart = false;
            }
        },

        /**
         * Quick View - เปิด modal รายละเอียดสินค้า
         */
        quickView() {
            this.$dispatch('quick-view-product', { productId: this.productId });
        }
    };
}

// Export สำหรับใช้งาน global
if (typeof window.productCardComponent === 'undefined') {
    window.productCardComponent = productCardComponent;
}
</script>
@endpush
