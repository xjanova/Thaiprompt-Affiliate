{{--
    Shopping Cart Page (V3)

    ตะกร้าสินค้าพร้อมฟีเจอร์:
    - แก้ไขจำนวนสินค้า
    - ลบสินค้า
    - สรุปยอดรวม (PV, Commission, Cashback)
    - Coupon code
    - Proceed to checkout

    @extends layouts.user-arrow-x
--}}

@extends('layouts.user-arrow-x')

@section('title', 'ตะกร้าสินค้า')

@section('content')
<div class="container-fluid px-4 py-6" x-data="cartComponent()">

    {{-- Page Header --}}
    <div class="mb-8">
        <h1 class="text-3xl md:text-4xl font-bold bg-gradient-to-r from-purple-600 via-pink-600 to-orange-600
                   bg-clip-text text-transparent mb-2">
            <i class="fas fa-shopping-cart"></i>
            ตะกร้าสินค้า
        </h1>
        <p class="text-gray-600 dark:text-gray-400">
            <span x-text="cartItems.length"></span> รายการ
        </p>
    </div>

    <div x-show="cartItems.length > 0" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left Column - Cart Items --}}
        <div class="lg:col-span-2 space-y-4">
            <template x-for="(item, index) in cartItems" :key="item.id">
                <div class="glass-fusion-card rounded-2xl p-6 backdrop-blur-xl
                            bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/30
                            shadow-xl">

                    <div class="flex gap-4">
                        {{-- Product Image --}}
                        <a :href="`/products/${item.product.slug}`"
                           class="relative flex-shrink-0 w-24 h-24 rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-700">
                            <img :src="item.product.primary_image_url || '/images/placeholder.png'"
                                 :alt="item.product.name"
                                 class="w-full h-full object-cover">

                            {{-- Stock Badge --}}
                            <div x-show="item.product.stock_quantity <= 0"
                                 class="absolute inset-0 bg-black/60 backdrop-blur-sm
                                        flex items-center justify-center">
                                <span class="text-white text-xs font-bold">หมด</span>
                            </div>
                        </a>

                        {{-- Product Info --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-3 mb-2">
                                <div class="flex-1 min-w-0">
                                    <a :href="`/products/${item.product.slug}`"
                                       class="font-semibold text-gray-900 dark:text-white
                                              hover:text-purple-600 dark:hover:text-purple-400
                                              line-clamp-2 transition-colors"
                                       x-text="item.product.name"></a>

                                    {{-- PV & Commission Info --}}
                                    <div class="flex flex-wrap gap-2 mt-2">
                                        <template x-if="item.product.pv_value">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5
                                                         bg-blue-100 dark:bg-blue-900/30
                                                         text-blue-700 dark:text-blue-300
                                                         text-xs font-medium rounded">
                                                <i class="fas fa-coins"></i>
                                                <span x-text="formatNumber(item.product.pv_value)"></span> PV
                                            </span>
                                        </template>

                                        <template x-if="item.product.commission_rate && isAffiliate">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5
                                                         bg-green-100 dark:bg-green-900/30
                                                         text-green-700 dark:text-green-300
                                                         text-xs font-medium rounded">
                                                <i class="fas fa-percent"></i>
                                                <span x-text="item.product.commission_rate"></span>%
                                            </span>
                                        </template>
                                    </div>
                                </div>

                                {{-- Delete Button --}}
                                <button @click="removeItem(index)"
                                        class="flex-shrink-0 w-8 h-8 rounded-lg
                                               hover:bg-red-100 dark:hover:bg-red-900/30
                                               text-red-500 dark:text-red-400
                                               transition-colors">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>

                            {{-- Price & Quantity --}}
                            <div class="flex items-end justify-between gap-4">
                                {{-- Quantity Selector --}}
                                <div class="flex items-center border-2 border-gray-200 dark:border-gray-700
                                            rounded-lg overflow-hidden">
                                    <button @click="updateQuantity(index, item.quantity - 1)"
                                            :disabled="item.quantity <= 1"
                                            class="px-3 py-1.5 bg-gray-50 dark:bg-gray-700
                                                   hover:bg-gray-100 dark:hover:bg-gray-600
                                                   disabled:opacity-50 disabled:cursor-not-allowed
                                                   text-gray-700 dark:text-gray-300 transition-colors">
                                        <i class="fas fa-minus text-xs"></i>
                                    </button>

                                    <input type="number"
                                           :value="item.quantity"
                                           @change="updateQuantity(index, $event.target.value)"
                                           min="1"
                                           :max="item.product.stock_quantity"
                                           class="w-16 px-2 py-1.5 text-center font-semibold
                                                  bg-white dark:bg-gray-800
                                                  text-gray-900 dark:text-white
                                                  border-0 focus:ring-0">

                                    <button @click="updateQuantity(index, item.quantity + 1)"
                                            :disabled="item.quantity >= item.product.stock_quantity"
                                            class="px-3 py-1.5 bg-gray-50 dark:bg-gray-700
                                                   hover:bg-gray-100 dark:hover:bg-gray-600
                                                   disabled:opacity-50 disabled:cursor-not-allowed
                                                   text-gray-700 dark:text-gray-300 transition-colors">
                                        <i class="fas fa-plus text-xs"></i>
                                    </button>
                                </div>

                                {{-- Price --}}
                                <div class="text-right">
                                    <div x-show="item.product.sale_price && item.product.sale_price < item.product.price"
                                         class="text-xs text-gray-400 dark:text-gray-500 line-through">
                                        ฿<span x-text="formatNumber(item.product.price * item.quantity)"></span>
                                    </div>

                                    <div class="text-lg font-bold text-gray-900 dark:text-white">
                                        ฿<span x-text="formatNumber(getItemPrice(item) * item.quantity)"></span>
                                    </div>
                                </div>
                            </div>

                            {{-- Stock Warning --}}
                            <div x-show="item.product.stock_quantity > 0 && item.product.stock_quantity <= 10"
                                 class="mt-2 text-xs text-orange-500 dark:text-orange-400">
                                <i class="fas fa-exclamation-triangle"></i>
                                เหลือเพียง <span x-text="item.product.stock_quantity"></span> ชิ้น
                            </div>

                            <div x-show="item.product.stock_quantity <= 0"
                                 class="mt-2 text-xs text-red-500 dark:text-red-400">
                                <i class="fas fa-times-circle"></i>
                                สินค้าหมด - กรุณาลบออกจากตะกร้า
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            {{-- Continue Shopping --}}
            <div class="text-center pt-4">
                <a href="{{ route('marketplace.index') }}"
                   class="inline-flex items-center gap-2 px-6 py-3
                          text-purple-600 dark:text-purple-400
                          hover:bg-purple-50 dark:hover:bg-purple-900/20
                          rounded-xl transition-colors">
                    <i class="fas fa-arrow-left"></i>
                    <span>เลือกซื้อสินค้าต่อ</span>
                </a>
            </div>
        </div>

        {{-- Right Column - Summary --}}
        <div class="lg:col-span-1">
            <div class="glass-fusion-card rounded-2xl p-6 backdrop-blur-xl
                        bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/30
                        shadow-2xl sticky top-6 space-y-6">

                <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                    <i class="fas fa-receipt mr-2 text-purple-600"></i>
                    สรุปคำสั่งซื้อ
                </h2>

                {{-- Subtotal --}}
                <div class="space-y-3 pb-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex justify-between text-gray-700 dark:text-gray-300">
                        <span>ยอดรวม (<span x-text="getTotalItems()"></span> ชิ้น)</span>
                        <span>฿<span x-text="formatNumber(getSubtotal())"></span></span>
                    </div>

                    <div class="flex justify-between text-gray-700 dark:text-gray-300">
                        <span>ค่าจัดส่ง</span>
                        <span x-text="getShippingCost() > 0 ? `฿${formatNumber(getShippingCost())}` : 'ฟรี'"></span>
                    </div>

                    <div x-show="discount > 0" class="flex justify-between text-green-600 dark:text-green-400">
                        <span>ส่วนลด</span>
                        <span>-฿<span x-text="formatNumber(discount)"></span></span>
                    </div>
                </div>

                {{-- MLM Benefits Summary (ถ้า login) --}}
                @auth
                <div class="space-y-3 pb-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                        <i class="fas fa-gift text-purple-600"></i>
                        สิทธิประโยชน์
                    </h3>

                    <div class="grid grid-cols-2 gap-3">
                        {{-- Total PV --}}
                        <div class="text-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                            <div class="text-xs text-blue-600 dark:text-blue-400 mb-1">
                                <i class="fas fa-coins"></i> Total PV
                            </div>
                            <div class="text-lg font-bold text-blue-700 dark:text-blue-300"
                                 x-text="formatNumber(getTotalPV())"></div>
                        </div>

                        {{-- Total Commission (ถ้าเป็น affiliate) --}}
                        <div x-show="isAffiliate"
                             class="text-center p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                            <div class="text-xs text-green-600 dark:text-green-400 mb-1">
                                <i class="fas fa-percent"></i> Commission
                            </div>
                            <div class="text-lg font-bold text-green-700 dark:text-green-300">
                                ฿<span x-text="formatNumber(getTotalCommission())"></span>
                            </div>
                        </div>

                        {{-- Total Cashback --}}
                        <div x-show="getTotalCashback() > 0"
                             class="col-span-2 text-center p-3 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
                            <div class="text-xs text-orange-600 dark:text-orange-400 mb-1">
                                <i class="fas fa-gift"></i> Cashback
                            </div>
                            <div class="text-lg font-bold text-orange-700 dark:text-orange-300">
                                ฿<span x-text="formatNumber(getTotalCashback())"></span>
                            </div>
                        </div>
                    </div>
                </div>
                @endauth

                {{-- Coupon Code --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-ticket-alt mr-1"></i>
                        รหัสคูปอง
                    </label>

                    <div class="flex gap-2">
                        <input type="text"
                               x-model="couponCode"
                               @keydown.enter="applyCoupon()"
                               placeholder="ใส่รหัสคูปอง"
                               class="flex-1 px-4 py-2
                                      bg-white dark:bg-gray-700
                                      border border-gray-200 dark:border-gray-600
                                      rounded-lg
                                      focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20
                                      text-gray-900 dark:text-gray-100
                                      placeholder:text-gray-400">

                        <button @click="applyCoupon()"
                                :disabled="!couponCode || isApplyingCoupon"
                                class="px-4 py-2 bg-purple-600 hover:bg-purple-700
                                       text-white font-medium rounded-lg
                                       disabled:opacity-50 disabled:cursor-not-allowed
                                       transition-colors">
                            <span x-show="!isApplyingCoupon">ใช้</span>
                            <i x-show="isApplyingCoupon" class="fas fa-spinner fa-spin"></i>
                        </button>
                    </div>

                    <div x-show="couponError"
                         class="mt-2 text-xs text-red-500 dark:text-red-400">
                        <i class="fas fa-exclamation-circle"></i>
                        <span x-text="couponError"></span>
                    </div>

                    <div x-show="appliedCoupon"
                         class="mt-2 text-xs text-green-600 dark:text-green-400">
                        <i class="fas fa-check-circle"></i>
                        ใช้คูปอง "<span x-text="appliedCoupon"></span>" แล้ว
                    </div>
                </div>

                {{-- Total --}}
                <div class="pt-4 border-t-2 border-purple-200 dark:border-purple-700">
                    <div class="flex justify-between items-baseline mb-4">
                        <span class="text-lg font-semibold text-gray-700 dark:text-gray-300">
                            ยอดรวมทั้งหมด
                        </span>
                        <span class="text-3xl font-bold bg-gradient-to-r from-purple-600 to-pink-600
                                     bg-clip-text text-transparent">
                            ฿<span x-text="formatNumber(getTotal())"></span>
                        </span>
                    </div>

                    {{-- Shipping Info --}}
                    <div x-show="getShippingCost() > 0" class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg text-xs text-blue-700 dark:text-blue-300">
                        <i class="fas fa-info-circle"></i>
                        ซื้อเพิ่ม ฿<span x-text="formatNumber(freeShippingThreshold - getSubtotal())"></span> เพื่อรับฟรีค่าจัดส่ง
                    </div>

                    {{-- Checkout Button --}}
                    <a href="{{ route('checkout.index') }}"
                       class="block w-full px-6 py-4
                              bg-gradient-to-r from-purple-600 via-pink-600 to-red-500
                              hover:from-purple-700 hover:via-pink-700 hover:to-red-600
                              text-white text-center font-bold rounded-xl
                              shadow-lg hover:shadow-2xl
                              transform hover:scale-105 active:scale-95
                              transition-all duration-300">
                        <i class="fas fa-lock mr-2"></i>
                        ดำเนินการชำระเงิน
                    </a>

                    <div class="mt-3 text-center text-xs text-gray-500 dark:text-gray-400">
                        <i class="fas fa-shield-alt mr-1"></i>
                        ชำระเงินปลอดภัย 100%
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Empty Cart State --}}
    <div x-show="cartItems.length === 0" x-transition
         class="glass-fusion-card rounded-2xl p-12 text-center backdrop-blur-xl
                bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/30
                shadow-2xl">
        <div class="text-6xl text-gray-300 dark:text-gray-600 mb-4">
            <i class="fas fa-shopping-cart"></i>
        </div>

        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
            ตะกร้าสินค้าว่างเปล่า
        </h3>

        <p class="text-gray-600 dark:text-gray-400 mb-6">
            คุณยังไม่มีสินค้าในตะกร้า เริ่มเลือกซื้อสินค้ากันเถอะ!
        </p>

        <a href="{{ route('marketplace.index') }}"
           class="inline-flex items-center gap-2 px-8 py-4
                  bg-gradient-to-r from-purple-600 to-pink-600
                  hover:from-purple-700 hover:to-pink-700
                  text-white font-bold rounded-xl
                  shadow-lg hover:shadow-2xl
                  transform hover:scale-105
                  transition-all duration-300">
            <i class="fas fa-shopping-bag"></i>
            <span>เริ่มช็อปปิ้ง</span>
        </a>
    </div>
</div>

@push('scripts')
<script>
/**
 * Cart Component - จัดการตะกร้าสินค้า
 */
function cartComponent() {
    return {
        cartItems: @json($cartItems ?? []),
        isAffiliate: {{ auth()->check() && auth()->user()->mlmMember ? 'true' : 'false' }},
        couponCode: '',
        appliedCoupon: '',
        discount: 0,
        isApplyingCoupon: false,
        couponError: '',
        freeShippingThreshold: 500,
        shippingCost: 50,

        /**
         * รับราคาสินค้า (ใช้ sale price ถ้ามี)
         */
        getItemPrice(item) {
            return item.product.sale_price && item.product.sale_price < item.product.price
                ? item.product.sale_price
                : item.product.price;
        },

        /**
         * คำนวณยอดรวมย่อย
         */
        getSubtotal() {
            return this.cartItems.reduce((sum, item) => {
                return sum + (this.getItemPrice(item) * item.quantity);
            }, 0);
        },

        /**
         * คำนวณจำนวนสินค้าทั้งหมด
         */
        getTotalItems() {
            return this.cartItems.reduce((sum, item) => sum + item.quantity, 0);
        },

        /**
         * คำนวณค่าจัดส่ง
         */
        getShippingCost() {
            return this.getSubtotal() >= this.freeShippingThreshold ? 0 : this.shippingCost;
        },

        /**
         * คำนวณยอดรวมทั้งหมด
         */
        getTotal() {
            return this.getSubtotal() + this.getShippingCost() - this.discount;
        },

        /**
         * คำนวณ PV รวม
         */
        getTotalPV() {
            return this.cartItems.reduce((sum, item) => {
                const pv = item.product.pv_value || 0;
                return sum + (pv * item.quantity);
            }, 0);
        },

        /**
         * คำนวณ Commission รวม (สำหรับ affiliate)
         */
        getTotalCommission() {
            if (!this.isAffiliate) return 0;

            return this.cartItems.reduce((sum, item) => {
                const rate = item.product.commission_rate || 0;
                const price = this.getItemPrice(item);
                return sum + ((price * rate / 100) * item.quantity);
            }, 0);
        },

        /**
         * คำนวณ Cashback รวม
         */
        getTotalCashback() {
            return this.cartItems.reduce((sum, item) => {
                const cashback = item.product.customer_cashback || 0;
                return sum + (cashback * item.quantity);
            }, 0);
        },

        /**
         * อัพเดทจำนวนสินค้า
         */
        async updateQuantity(index, newQuantity) {
            newQuantity = Math.max(1, Math.min(this.cartItems[index].product.stock_quantity, parseInt(newQuantity)));

            try {
                const response = await fetch('/api/cart/update', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        cart_item_id: this.cartItems[index].id,
                        quantity: newQuantity
                    })
                });

                if (response.ok) {
                    this.cartItems[index].quantity = newQuantity;
                    this.$dispatch('cart-updated');
                }
            } catch (error) {
                console.error('Update quantity error:', error);
            }
        },

        /**
         * ลบสินค้าออกจากตะกร้า
         */
        async removeItem(index) {
            if (!confirm('ต้องการลบสินค้านี้ออกจากตะกร้าหรือไม่?')) return;

            try {
                const response = await fetch('/api/cart/remove', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        cart_item_id: this.cartItems[index].id
                    })
                });

                if (response.ok) {
                    this.cartItems.splice(index, 1);
                    this.$dispatch('cart-updated');
                    this.$dispatch('notify', {
                        message: 'ลบสินค้าออกจากตะกร้าแล้ว',
                        type: 'success'
                    });
                }
            } catch (error) {
                console.error('Remove item error:', error);
            }
        },

        /**
         * ใช้คูปอง
         */
        async applyCoupon() {
            if (!this.couponCode) return;

            this.isApplyingCoupon = true;
            this.couponError = '';

            try {
                const response = await fetch('/api/cart/apply-coupon', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        code: this.couponCode
                    })
                });

                const data = await response.json();

                if (response.ok) {
                    this.appliedCoupon = this.couponCode;
                    this.discount = data.discount || 0;
                    this.couponCode = '';

                    this.$dispatch('notify', {
                        message: 'ใช้คูปองสำเร็จ',
                        type: 'success'
                    });
                } else {
                    this.couponError = data.message || 'รหัสคูปองไม่ถูกต้อง';
                }
            } catch (error) {
                console.error('Apply coupon error:', error);
                this.couponError = 'เกิดข้อผิดพลาด กรุณาลองใหม่';
            } finally {
                this.isApplyingCoupon = false;
            }
        },

        /**
         * Format number
         */
        formatNumber(num) {
            return new Intl.NumberFormat('th-TH', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 2
            }).format(num);
        }
    };
}
</script>
@endpush
@endsection
