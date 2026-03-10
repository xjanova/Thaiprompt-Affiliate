{{--
/**
 * Cart Badge V3 Component - Slide Panel Style สำหรับ Navbar V3
 *
 * Component นี้แสดงไอคอนตะกร้าสินค้าแบบ modern พร้อม:
 * - ✅ Slide-in panel จากขวา
 * - ✅ Glassmorphism effects
 * - ✅ Real-time cart count
 * - ✅ Offline mode support (cache)
 * - ✅ Keyboard navigation
 * - ✅ Accessibility (ARIA labels)
 * - ✅ Smooth animations
 *
 * @example
 * <x-arrow-x.navbar.cart-badge-v3 />
 */
--}}

{{-- Cart Button - อยู่ใน Navbar --}}
<div x-data="{
    open: false,
    loading: false,
    cartItems: [],
    cartCount: 0,
    cartTotal: 0,
    selectedIndex: -1,

    // 📡 Offline Support
    isOnline: navigator.onLine,
    cacheKey: 'cart_cache_{{ auth()->id() ?? 'guest' }}',
    cacheExpiry: 5 * 60 * 1000, // 5 นาที

    /**
     * โหลดข้อมูลตะกร้าจาก API
     */
    async loadCart() {
        this.loading = true;

        // ตรวจสอบสถานะ offline
        if (!this.isOnline) {
            console.info('📴 Offline mode: Loading cart from cache');
            this.loadFromCache();
            this.loading = false;
            return;
        }

        try {
            const response = await fetch('{{ route('cart.mini') }}', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                }
            });
            if (response.ok) {
                const data = await response.json();
                this.cartItems = data.items || [];
                this.cartCount = data.count || 0;
                this.cartTotal = data.total || 0;

                // 💾 บันทึกลง cache
                this.saveToCache(data);
            } else {
                // หาก API ล้มเหลว ใช้ cache
                this.loadFromCache();
            }
        } catch (error) {
            console.error('Error loading cart:', error);
            // หาก fetch ล้มเหลว ใช้ cache
            this.loadFromCache();
        }
        this.loading = false;
    },

    /**
     * โหลดจำนวนสินค้าอย่างเดียว (สำหรับ polling)
     */
    async loadCartCount() {
        if (!this.isOnline) return;

        try {
            const response = await fetch('{{ route('cart.count') }}', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                }
            });
            if (response.ok) {
                const data = await response.json();
                this.cartCount = data.count || 0;
            }
        } catch (error) {
            console.debug('Error loading cart count:', error);
        }
    },

    /**
     * 💾 บันทึกข้อมูลลง localStorage
     */
    saveToCache(data) {
        try {
            const cache = {
                data: data,
                timestamp: Date.now()
            };
            localStorage.setItem(this.cacheKey, JSON.stringify(cache));
            console.debug('✅ Cart cached successfully');
        } catch (error) {
            console.error('Failed to cache cart:', error);
        }
    },

    /**
     * 📂 โหลดข้อมูลจาก localStorage
     */
    loadFromCache() {
        try {
            const cached = localStorage.getItem(this.cacheKey);
            if (!cached) {
                console.info('No cached cart found');
                return;
            }

            const cache = JSON.parse(cached);

            // ตรวจสอบว่า cache หมดอายุหรือยัง
            if (Date.now() - cache.timestamp > this.cacheExpiry) {
                console.info('⏰ Cache expired, removing...');
                localStorage.removeItem(this.cacheKey);
                return;
            }

            // โหลดข้อมูลจาก cache
            this.cartItems = cache.data.items || [];
            this.cartCount = cache.data.count || 0;
            this.cartTotal = cache.data.total || 0;
            console.info('✅ Loaded cart from cache');
        } catch (error) {
            console.error('Failed to load from cache:', error);
        }
    },

    /**
     * ลบสินค้าออกจากตะกร้า
     */
    async removeItem(itemId) {
        try {
            const response = await fetch(`/cart/${itemId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').content,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                }
            });

            if (response.ok) {
                // รีโหลดตะกร้า
                await this.loadCart();
                // Dispatch event เพื่ออัพเดท badge
                window.dispatchEvent(new CustomEvent('cart-updated'));
            }
        } catch (error) {
            console.error('Error removing item:', error);
        }
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
    },

    /**
     * ตัดข้อความให้สั้นลง
     */
    truncate(text, length = 30) {
        if (!text) return '';
        return text.length > length ? text.substring(0, length) + '...' : text;
    },

    // 🎹 Keyboard Navigation Methods
    selectNext() {
        if (this.cartItems.length === 0) return;
        this.selectedIndex = (this.selectedIndex + 1) % this.cartItems.length;
        this.scrollToSelected();
    },

    selectPrevious() {
        if (this.cartItems.length === 0) return;
        this.selectedIndex = this.selectedIndex <= 0
            ? this.cartItems.length - 1
            : this.selectedIndex - 1;
        this.scrollToSelected();
    },

    scrollToSelected() {
        this.$nextTick(() => {
            const container = this.$refs.cartList;
            const selected = container?.querySelector(`[data-index='${this.selectedIndex}']`);
            if (selected) {
                selected.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            }
        });
    },

    openPanel() {
        this.open = true;
        this.loadCart();
        // ป้องกัน body scroll เมื่อเปิด panel
        document.body.style.overflow = 'hidden';
    },

    closePanel() {
        this.open = false;
        this.selectedIndex = -1;
        // คืนค่า body scroll
        document.body.style.overflow = '';
    }
}"
x-init="
    // โหลดจำนวนครั้งแรก
    loadCartCount();

    // Polling ทุก 30 วินาที
    setInterval(() => loadCartCount(), 30000);

    // 📡 ฟัง online/offline events
    window.addEventListener('online', () => {
        console.info('🌐 Connection restored');
        this.isOnline = true;
        this.loadCartCount();
    });

    window.addEventListener('offline', () => {
        console.info('📴 Connection lost');
        this.isOnline = false;
    });

    // 🛒 ฟัง cart update events (dispatch จาก add to cart)
    window.addEventListener('cart-updated', () => {
        this.loadCartCount();
        if (this.open) {
            this.loadCart();
        }
    });
"
@keydown.escape.window="if (open) closePanel()"
@keydown.arrow-down.prevent="if (open) selectNext()"
@keydown.arrow-up.prevent="if (open) selectPrevious()"
class="relative">

    {{-- Cart Button พร้อม Glassmorphism Style --}}
    <button
        id="cart-button"
        @click="openPanel()"
        :aria-expanded="open.toString()"
        aria-haspopup="dialog"
        :aria-label="cartCount > 0 ? `ตะกร้าสินค้า ${cartCount} รายการ` : 'ตะกร้าสินค้า'"
        type="button"
        class="relative p-3 rounded-xl glass-neu hover:bg-white/20 transition-all hover:scale-110 active:scale-95"
        title="ตะกร้าสินค้า">
        <i class="fas fa-shopping-cart text-white drop-shadow"></i>

        {{-- Cart Count Badge --}}
        <span
            x-show="cartCount > 0"
            x-text="cartCount > 99 ? '99+' : cartCount"
            x-transition
            aria-live="polite"
            :aria-label="`${cartCount} รายการในตะกร้า`"
            class="absolute top-0 right-0 inline-flex items-center justify-center min-w-[20px] h-5 px-1 text-xs font-bold text-white bg-gradient-to-r from-orange-500 to-red-500 rounded-full shadow-lg"
        ></span>

        {{-- Offline Indicator --}}
        <span
            x-show="!isOnline"
            title="ออฟไลน์ - แสดงข้อมูลแคช"
            aria-label="ออฟไลน์"
            class="absolute -bottom-1 -right-1 w-3 h-3 bg-gray-400 border-2 border-white/50 rounded-full"
        ></span>
    </button>

    {{-- Backdrop Overlay - ใช้ fixed positioning กับ isolation --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="closePanel()"
        class="cart-backdrop"
        style="display: none;"
        aria-hidden="true">
    </div>

    {{-- Slide Panel จากขวา --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        role="dialog"
        aria-modal="true"
        aria-labelledby="cart-panel-title"
        class="cart-slide-panel"
        style="display: none;">

        {{-- Header --}}
        <div class="flex-shrink-0 px-6 py-4 border-b border-white/10 bg-black/20">
            <div class="flex items-center justify-between">
                <h2 id="cart-panel-title" class="text-xl font-bold text-white flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-orange-500 to-red-500 rounded-xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-shopping-cart text-white"></i>
                    </div>
                    <span>ตะกร้าสินค้า</span>
                    <span x-show="cartCount > 0"
                          class="px-2.5 py-1 text-sm bg-white/10 rounded-full"
                          x-text="`${cartCount} รายการ`"></span>
                </h2>
                <button
                    @click="closePanel()"
                    type="button"
                    class="p-2 rounded-xl text-white/60 hover:text-white hover:bg-white/10 transition-all"
                    aria-label="ปิด">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            {{-- Offline Warning --}}
            <div x-show="!isOnline" class="flex items-center gap-2 px-4 py-2 mt-3 bg-yellow-500/20 border border-yellow-500/30 rounded-xl text-sm text-yellow-200">
                <i class="fas fa-wifi-slash"></i>
                <span>ออฟไลน์ - แสดงข้อมูลจากแคช</span>
            </div>
        </div>

        {{-- Cart Items List --}}
        <div class="flex-1 overflow-y-auto" x-ref="cartList">
            {{-- Loading --}}
            <template x-if="loading">
                <div class="flex items-center justify-center py-16">
                    <div class="text-center">
                        <svg class="animate-spin h-12 w-12 text-orange-500 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p class="text-white/60">กำลังโหลดตะกร้า...</p>
                    </div>
                </div>
            </template>

            {{-- Empty State --}}
            <template x-if="!loading && cartItems.length === 0">
                <div class="flex flex-col items-center justify-center py-16 px-6">
                    <div class="w-24 h-24 mb-6 bg-gradient-to-br from-gray-700 to-gray-800 rounded-full flex items-center justify-center">
                        <i class="fas fa-shopping-cart text-4xl text-gray-500"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">ตะกร้าว่างเปล่า</h3>
                    <p class="text-white/60 text-center mb-6">ยังไม่มีสินค้าในตะกร้า<br>ไปเลือกซื้อสินค้ากันเถอะ!</p>
                    <a href="{{ route('official-shop.index') }}"
                       @click="closePanel()"
                       class="inline-flex items-center gap-2 px-6 py-3 text-white bg-gradient-to-r from-orange-500 to-red-500 rounded-xl hover:shadow-lg hover:shadow-orange-500/30 transition-all font-medium">
                        <i class="fas fa-store"></i>
                        ไปช้อปปิ้ง
                    </a>
                </div>
            </template>

            {{-- Cart Items --}}
            <div x-show="!loading && cartItems.length > 0" class="divide-y divide-white/10">
                <template x-for="(item, index) in cartItems" :key="item.id">
                    <div
                        :data-index="index"
                        :class="{
                            'bg-white/5': selectedIndex === index
                        }"
                        role="listitem"
                        :tabindex="selectedIndex === index ? 0 : -1"
                        class="p-4 hover:bg-white/5 transition-all">
                        <div class="flex gap-4">
                            {{-- Product Image --}}
                            <div class="flex-shrink-0 w-20 h-20 rounded-xl overflow-hidden bg-white/5 border border-white/10">
                                <img :src="item.image || '/images/no-image.png'"
                                     :alt="item.name"
                                     class="w-full h-full object-cover"
                                     onerror="this.src='/images/no-image.png'">
                            </div>

                            {{-- Product Info --}}
                            <div class="flex-1 min-w-0">
                                <p class="text-base font-medium text-white mb-1 line-clamp-2" x-text="item.name"></p>
                                <div class="flex items-center gap-2 text-sm text-white/60 mb-2">
                                    <span x-text="formatPrice(item.price)"></span>
                                    <span>×</span>
                                    <span x-text="item.quantity" class="font-medium text-white"></span>
                                </div>
                                <p class="text-lg font-bold text-orange-400" x-text="formatPrice(item.price * item.quantity)"></p>
                            </div>

                            {{-- Remove Button --}}
                            <button
                                @click.stop="removeItem(item.id)"
                                type="button"
                                class="flex-shrink-0 self-start p-2 rounded-xl text-white/40 hover:text-red-400 hover:bg-red-500/10 transition-all"
                                title="ลบสินค้า">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- Footer with Total and Buttons --}}
        <div class="flex-shrink-0 border-t border-white/10 bg-black/30 p-6">
            {{-- Total Summary --}}
            <div x-show="cartCount > 0" class="mb-4 p-4 bg-white/5 rounded-xl">
                <div class="flex items-center justify-between text-white/70 mb-2">
                    <span>จำนวนสินค้า</span>
                    <span x-text="`${cartCount} ชิ้น`"></span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-lg font-medium text-white">รวมทั้งหมด</span>
                    <span class="text-2xl font-bold text-orange-400" x-text="formatPrice(cartTotal)"></span>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="flex flex-col gap-3">
                <a href="{{ route('checkout.index') }}"
                   x-show="cartCount > 0"
                   @click="closePanel()"
                   class="w-full py-3.5 text-center text-base font-bold text-white bg-gradient-to-r from-orange-500 to-red-500 hover:from-orange-600 hover:to-red-600 rounded-xl shadow-lg hover:shadow-orange-500/30 transition-all flex items-center justify-center gap-2">
                    <i class="fas fa-credit-card"></i>
                    ดำเนินการชำระเงิน
                </a>
                <a href="{{ route('cart.index') }}"
                   @click="closePanel()"
                   class="w-full py-3 text-center text-base font-medium text-white/80 hover:text-white bg-white/10 hover:bg-white/20 rounded-xl transition-all">
                    ดูตะกร้าแบบเต็ม
                </a>
            </div>
        </div>
    </div>
</div>

{{-- CSS สำหรับ Cart Panel - ใช้ fixed positioning ที่ทำงานได้ทั่วทั้งหน้าจอ --}}
<style>
.cart-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    z-index: 9998;
}

.cart-slide-panel {
    position: fixed;
    top: 0;
    right: 0;
    width: 100%;
    max-width: 28rem;
    height: 100vh;
    height: 100dvh;
    display: flex;
    flex-direction: column;
    background: linear-gradient(to bottom, rgba(17, 24, 39, 0.95), rgba(31, 41, 55, 0.95));
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border-left: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: -10px 0 40px rgba(0, 0, 0, 0.3);
    z-index: 9999;
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
