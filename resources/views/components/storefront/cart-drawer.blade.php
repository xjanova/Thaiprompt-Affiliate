{{--
/**
 * Cart Drawer Component สำหรับ Storefront
 *
 * Features:
 * - ✅ Slide-in drawer จากขวา
 * - ✅ Real-time cart count badge
 * - ✅ เพิ่ม/ลด/ลบสินค้าได้
 * - ✅ รองรับ Dark Mode
 * - ✅ Smooth animations
 * - ✅ Mobile responsive
 * - ✅ แก้ปัญหา backdrop-blur containing block
 *
 * @example
 * <x-storefront.cart-drawer />
 */
--}}

{{-- Cart Button พร้อม Badge (อยู่ใน navbar) --}}
<div x-data="cartDrawerButton()"
     x-init="init()"
     @cart-updated.window="loadCartCount()"
     class="relative">

    <button @click="openDrawer()"
            type="button"
            class="relative p-2.5 rounded-xl bg-gray-100 dark:bg-gray-700
                   hover:bg-orange-100 dark:hover:bg-orange-900/30
                   text-gray-600 dark:text-gray-300
                   hover:text-orange-600 dark:hover:text-orange-400
                   transition-all hover:scale-105"
            title="ตะกร้าสินค้า">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
        </svg>

        {{-- Cart Count Badge --}}
        <span x-show="cartCount > 0"
              x-text="cartCount > 99 ? '99+' : cartCount"
              x-transition:enter="transition ease-out duration-200"
              x-transition:enter-start="scale-0 opacity-0"
              x-transition:enter-end="scale-100 opacity-100"
              class="absolute -top-1.5 -right-1.5 min-w-[20px] h-5 px-1
                     bg-gradient-to-r from-orange-500 to-red-500
                     text-white text-xs font-bold
                     rounded-full flex items-center justify-center
                     shadow-lg animate-pulse">
        </span>
    </button>
</div>

<script>
/**
 * Cart Drawer Button Component - ปุ่มกดเปิดตะกร้า
 */
function cartDrawerButton() {
    return {
        cartCount: 0,

        /**
         * เริ่มต้น component
         */
        init() {
            @auth
            this.loadCartCount();
            // Polling ทุก 30 วินาที (เฉพาะผู้ที่ล็อกอินแล้วเท่านั้น)
            setInterval(() => this.loadCartCount(), 30000);
            @endauth
        },

        /**
         * โหลดจำนวนสินค้าในตะกร้า
         *
         * ⚠️ route cart.count อยู่ใน middleware 'auth' — ผู้เยี่ยมชมที่ยังไม่ล็อกอิน
         * จะโดน redirect 302 ไปหน้า login แล้วได้ HTML กลับมา ทำให้ JSON.parse พังทุกครั้ง
         * จึงต้องตัดจบตั้งแต่ต้นทาง ไม่ยิง request เลย
         */
        async loadCartCount() {
            @guest
            return;
            @endguest

            try {
                const response = await fetch('{{ route("cart.count") }}', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });
                // session หมดอายุกลางคัน → โดน redirect เป็น HTML หน้า login
                // ถ้าไม่เช็ค content-type จะ parse พังซ้ำทุก 30 วินาทีไม่รู้จบ
                const contentType = response.headers.get('content-type') || '';
                if (response.ok && contentType.includes('application/json')) {
                    const data = await response.json();
                    this.cartCount = data.count || 0;
                }
            } catch (error) {
                console.debug('Error loading cart count:', error);
            }
        },

        /**
         * เปิด Drawer (dispatch event ไปยัง drawer panel)
         */
        openDrawer() {
            window.dispatchEvent(new CustomEvent('cart-drawer-open'));
        }
    };
}
</script>

{{-- Drawer Panel (push ไปที่ modals stack เพื่อหลีกเลี่ยง backdrop-blur containing block) --}}
@push('modals')
<div x-data="cartDrawerPanel()"
     x-init="init()"
     @cart-drawer-open.window="openDrawer()"
     @cart-updated.window="loadCart()"
     class="cart-drawer-container">

    {{-- Backdrop --}}
    <div x-show="isOpen"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="closeDrawer()"
         class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[9998]"
         style="display: none;">
    </div>

    {{-- Drawer Panel --}}
    <div x-show="isOpen"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         @keydown.escape.window="closeDrawer()"
         class="fixed top-0 right-0 w-full max-w-md h-full
                bg-white dark:bg-gray-800
                shadow-2xl z-[9999]
                flex flex-col"
         style="display: none;">

        {{-- Header --}}
        <div class="flex items-center justify-between px-6 py-4
                    border-b border-gray-200 dark:border-gray-700
                    bg-gradient-to-r from-orange-500 to-red-500">
            <h2 class="text-xl font-bold text-white flex items-center gap-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                ตะกร้าสินค้า
                <span x-show="cartCount > 0"
                      x-text="`(${cartCount})`"
                      class="text-white/80"></span>
            </h2>
            <button @click="closeDrawer()"
                    type="button"
                    class="p-2 rounded-lg text-white/80 hover:text-white hover:bg-white/20 transition-all">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Content --}}
        <div class="flex-1 overflow-y-auto">
            {{-- Loading State --}}
            <template x-if="loading">
                <div class="flex items-center justify-center py-16">
                    <div class="text-center">
                        <svg class="animate-spin h-12 w-12 text-orange-500 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p class="text-gray-500 dark:text-gray-400">กำลังโหลดตะกร้า...</p>
                    </div>
                </div>
            </template>

            {{-- Empty State --}}
            <template x-if="!loading && items.length === 0">
                <div class="flex flex-col items-center justify-center py-16 px-6">
                    <div class="w-24 h-24 mb-6 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">ตะกร้าว่างเปล่า</h3>
                    <p class="text-gray-500 dark:text-gray-400 text-center mb-6">
                        ยังไม่มีสินค้าในตะกร้า<br>เลือกซื้อสินค้าได้เลย!
                    </p>
                    <button @click="closeDrawer()"
                            class="px-6 py-3 bg-gradient-to-r from-orange-500 to-red-500
                                   text-white font-bold rounded-xl
                                   hover:shadow-lg transition-all">
                        เลือกซื้อสินค้า
                    </button>
                </div>
            </template>

            {{-- Cart Items --}}
            <div x-show="!loading && items.length > 0" class="divide-y divide-gray-200 dark:divide-gray-700">
                <template x-for="item in items" :key="item.id">
                    <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-all">
                        <div class="flex gap-4">
                            {{-- Product Image --}}
                            <div class="flex-shrink-0 w-20 h-20 rounded-xl overflow-hidden bg-gray-100 dark:bg-gray-700">
                                <img :src="item.image || '{{ asset('images/no-image.png') }}'"
                                     :alt="'รูปสินค้า ' + (item.name || '')"
                                     width="80"
                                     height="80"
                                     class="w-full h-full object-cover"
                                     loading="lazy"
                                     decoding="async"
                                     onerror="this.onerror=null; this.src='{{ asset('images/no-image.png') }}';">
                            </div>

                            {{-- Product Info --}}
                            <div class="flex-1 min-w-0">
                                <h4 class="font-medium text-gray-900 dark:text-white line-clamp-2 mb-1"
                                    x-text="item.name"></h4>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-2"
                                   x-text="formatPrice(item.price) + ' / ชิ้น'"></p>

                                {{-- Quantity Controls --}}
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center border border-gray-300 dark:border-gray-600 rounded-lg overflow-hidden">
                                        <button @click="updateQuantity(item.id, item.quantity - 1)"
                                                :disabled="updating === item.id"
                                                class="w-8 h-8 flex items-center justify-center
                                                       text-gray-600 dark:text-gray-400
                                                       hover:bg-gray-100 dark:hover:bg-gray-700
                                                       disabled:opacity-50 transition-all">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                                            </svg>
                                        </button>
                                        <span class="w-10 text-center font-medium text-gray-900 dark:text-white"
                                              x-text="item.quantity"></span>
                                        <button @click="updateQuantity(item.id, item.quantity + 1)"
                                                :disabled="updating === item.id"
                                                class="w-8 h-8 flex items-center justify-center
                                                       text-gray-600 dark:text-gray-400
                                                       hover:bg-gray-100 dark:hover:bg-gray-700
                                                       disabled:opacity-50 transition-all">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                            </svg>
                                        </button>
                                    </div>

                                    {{-- Remove Button --}}
                                    <button @click="removeItem(item.id)"
                                            :disabled="updating === item.id"
                                            class="p-2 text-red-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-all">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            {{-- Item Total --}}
                            <div class="flex-shrink-0 text-right">
                                <p class="font-bold text-orange-600 dark:text-orange-400"
                                   x-text="formatPrice(item.price * item.quantity)"></p>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- Footer --}}
        <div x-show="items.length > 0"
             class="flex-shrink-0 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 p-4">
            {{-- Total Summary --}}
            <div class="mb-4 p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between text-gray-600 dark:text-gray-400 mb-2">
                    <span>จำนวนสินค้า</span>
                    <span x-text="`${cartCount} ชิ้น`"></span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-lg font-medium text-gray-900 dark:text-white">รวมทั้งหมด</span>
                    <span class="text-2xl font-bold text-orange-600 dark:text-orange-400"
                          x-text="formatPrice(cartTotal)"></span>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="flex flex-col gap-3">
                <a href="{{ route('checkout.index') }}"
                   @click="closeDrawer()"
                   class="w-full py-3.5 text-center text-base font-bold text-white
                          bg-gradient-to-r from-orange-500 to-red-500
                          hover:from-orange-600 hover:to-red-600
                          rounded-xl shadow-lg hover:shadow-xl transition-all
                          flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                    ดำเนินการชำระเงิน
                </a>
                <a href="{{ route('cart.index') }}"
                   @click="closeDrawer()"
                   class="w-full py-3 text-center text-base font-medium
                          text-gray-700 dark:text-gray-300
                          bg-white dark:bg-gray-700
                          hover:bg-gray-100 dark:hover:bg-gray-600
                          border border-gray-300 dark:border-gray-600
                          rounded-xl transition-all">
                    ดูตะกร้าแบบเต็ม
                </a>
            </div>
        </div>
    </div>
</div>

<script>
/**
 * Cart Drawer Panel Component - แผง drawer ที่แสดงรายการสินค้า
 */
function cartDrawerPanel() {
    return {
        isOpen: false,
        loading: false,
        updating: null,
        items: [],
        cartCount: 0,
        cartTotal: 0,

        /**
         * เริ่มต้น component
         */
        init() {
            // ไม่ต้อง load อะไรตอน init เพราะจะ load เมื่อเปิด drawer
        },

        /**
         * โหลดข้อมูลตะกร้าทั้งหมด
         */
        async loadCart() {
            @guest
            // route cart.mini เป็น auth-only เช่นกัน — ผู้เยี่ยมชมไม่มีตะกร้าอยู่แล้ว
            // ยิงไปก็ได้ HTML หน้า login กลับมาแล้ว parse พัง จึงแสดงสถานะ "ตะกร้าว่าง" ไปเลย
            this.items = [];
            this.cartCount = 0;
            this.cartTotal = 0;
            this.loading = false;
            return;
            @endguest

            this.loading = true;
            try {
                const response = await fetch('{{ route("cart.mini") }}', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });
                if (response.ok) {
                    const data = await response.json();
                    this.items = data.items || [];
                    this.cartCount = data.count || 0;
                    this.cartTotal = data.total || 0;
                }
            } catch (error) {
                console.error('Error loading cart:', error);
            }
            this.loading = false;
        },

        /**
         * อัพเดทจำนวนสินค้า
         */
        async updateQuantity(itemId, newQuantity) {
            if (newQuantity < 1) {
                this.removeItem(itemId);
                return;
            }

            this.updating = itemId;
            try {
                const response = await fetch(`/cart/${itemId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ quantity: newQuantity })
                });

                if (response.ok) {
                    await this.loadCart();
                    window.dispatchEvent(new CustomEvent('cart-updated'));
                } else {
                    const data = await response.json();
                    alert(data.message || 'เกิดข้อผิดพลาด');
                }
            } catch (error) {
                console.error('Error updating quantity:', error);
            }
            this.updating = null;
        },

        /**
         * ลบสินค้าออกจากตะกร้า
         */
        async removeItem(itemId) {
            this.updating = itemId;
            try {
                const response = await fetch(`/cart/${itemId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                if (response.ok) {
                    await this.loadCart();
                    window.dispatchEvent(new CustomEvent('cart-updated'));
                }
            } catch (error) {
                console.error('Error removing item:', error);
            }
            this.updating = null;
        },

        /**
         * เปิด Drawer
         */
        openDrawer() {
            this.isOpen = true;
            this.loadCart();
            document.body.style.overflow = 'hidden';
        },

        /**
         * ปิด Drawer
         */
        closeDrawer() {
            this.isOpen = false;
            document.body.style.overflow = '';
        },

        /**
         * จัดรูปแบบราคา
         */
        formatPrice(price) {
            return new Intl.NumberFormat('th-TH', {
                style: 'currency',
                currency: 'THB',
                minimumFractionDigits: 0
            }).format(price);
        }
    };
}
</script>
@endpush
