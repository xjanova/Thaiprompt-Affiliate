{{--
/**
 * Cart Badge V3 Component - Glassmorphism Style สำหรับ Navbar V3
 *
 * Component นี้แสดงไอคอนตะกร้าสินค้าแบบ modern พร้อม:
 * - ✅ Glassmorphism effects
 * - ✅ Mini cart dropdown
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

    closeDropdown() {
        this.open = false;
        this.selectedIndex = -1;
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
@keydown.escape.window="if (open) closeDropdown()"
@keydown.arrow-down.prevent="if (open) selectNext()"
@keydown.arrow-up.prevent="if (open) selectPrevious()"
class="relative">

    {{-- Cart Button พร้อม Glassmorphism Style --}}
    <button
        id="cart-button"
        @click="open = !open; if(open) loadCart()"
        :aria-expanded="open.toString()"
        aria-haspopup="true"
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
            class="absolute top-0 right-0 inline-flex items-center justify-center min-w-[20px] h-5 px-1 text-xs font-bold text-white bg-gradient-to-r from-orange-500 to-red-500 rounded-full shadow-lg animate-pulse"
        ></span>

        {{-- Offline Indicator --}}
        <span
            x-show="!isOnline"
            title="ออฟไลน์ - แสดงข้อมูลแคช"
            aria-label="ออฟไลน์"
            class="absolute -bottom-1 -right-1 w-3 h-3 bg-gray-400 border-2 border-white/50 rounded-full"
        ></span>
    </button>

    {{-- Mini Cart Dropdown พร้อม Glassmorphism --}}
    <div
        x-show="open"
        @click.away="closeDropdown()"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        role="menu"
        aria-labelledby="cart-button"
        aria-orientation="vertical"
        class="absolute right-0 z-50 w-96 mt-2 glass-dropdown rounded-xl shadow-2xl border border-white/30 max-h-[32rem] overflow-hidden"
        style="display: none;">

        {{-- Header --}}
        <div class="px-4 py-3 border-b border-white/20 bg-black/20">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-bold text-white drop-shadow">
                    <i class="fas fa-shopping-cart mr-2 text-orange-400"></i>
                    ตะกร้าสินค้า
                </h3>
                <span x-show="cartCount > 0" class="text-sm text-white/70" x-text="`${cartCount} รายการ`"></span>
            </div>

            {{-- Offline Warning --}}
            <div x-show="!isOnline" class="flex items-center gap-2 px-3 py-2 mt-2 glass-neu rounded-lg text-xs text-white/80">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.243 2.829a4.978 4.978 0 01-1.414-2.83m-1.414 5.658a9 9 0 01-2.167-9.238m7.824 2.167a1 1 0 111.414 1.414m-1.414-1.414L3 3m8.293 8.293l1.414 1.414"></path>
                </svg>
                <span>📴 ออฟไลน์ - แสดงข้อมูลจากแคช</span>
            </div>
        </div>

        {{-- Cart Items List --}}
        <div class="overflow-y-auto max-h-72" x-ref="cartList">
            {{-- Loading --}}
            <template x-if="loading">
                <div class="flex items-center justify-center py-8">
                    <svg class="animate-spin h-8 w-8 text-white/60" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
            </template>

            {{-- Empty State --}}
            <template x-if="!loading && cartItems.length === 0">
                <div class="text-center py-8 text-white/60">
                    <div class="w-16 h-16 mx-auto mb-4 bg-white/10 rounded-full flex items-center justify-center">
                        <i class="fas fa-shopping-cart text-3xl text-white/40"></i>
                    </div>
                    <p class="mb-2">ตะกร้าว่างเปล่า</p>
                    <a href="{{ route('official-shop.index') }}"
                       class="inline-flex items-center gap-2 px-4 py-2 text-sm text-white bg-gradient-to-r from-orange-500 to-red-500 rounded-lg hover:shadow-lg transition-all">
                        <i class="fas fa-store"></i>
                        ไปช้อปปิ้ง
                    </a>
                </div>
            </template>

            {{-- Cart Item --}}
            <template x-for="(item, index) in cartItems" :key="item.id">
                <div
                    :data-index="index"
                    :class="{
                        'ring-2 ring-white/50 ring-inset': selectedIndex === index
                    }"
                    role="menuitem"
                    :tabindex="selectedIndex === index ? 0 : -1"
                    class="px-4 py-3 border-b border-white/10 hover:bg-white/10 transition-all">
                    <div class="flex items-start gap-3">
                        {{-- Product Image --}}
                        <div class="flex-shrink-0 w-14 h-14 rounded-lg overflow-hidden bg-white/10">
                            <img :src="item.image || '/images/no-image.png'"
                                 :alt="item.name"
                                 class="w-full h-full object-cover"
                                 onerror="this.src='/images/no-image.png'">
                        </div>

                        {{-- Product Info --}}
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-white truncate" x-text="truncate(item.name, 25)"></p>
                            <p class="text-xs text-white/60 mt-0.5">
                                <span x-text="formatPrice(item.price)"></span>
                                <span class="mx-1">x</span>
                                <span x-text="item.quantity"></span>
                            </p>
                            <p class="text-sm font-bold text-orange-400 mt-1" x-text="formatPrice(item.price * item.quantity)"></p>
                        </div>

                        {{-- Remove Button --}}
                        <button
                            @click.stop="removeItem(item.id)"
                            type="button"
                            class="flex-shrink-0 p-1.5 rounded-lg text-white/60 hover:text-red-400 hover:bg-red-500/20 transition-all"
                            title="ลบสินค้า">
                            <i class="fas fa-times text-sm"></i>
                        </button>
                    </div>
                </div>
            </template>
        </div>

        {{-- Footer with Total and Buttons --}}
        <div class="px-4 py-3 border-t border-white/20 bg-black/20">
            {{-- Total --}}
            <div x-show="cartCount > 0" class="flex items-center justify-between mb-3">
                <span class="text-sm text-white/80">รวมทั้งหมด:</span>
                <span class="text-lg font-bold text-white" x-text="formatPrice(cartTotal)"></span>
            </div>

            {{-- Action Buttons --}}
            <div class="flex gap-2">
                <a href="{{ route('cart.index') }}"
                   class="flex-1 px-4 py-2.5 text-center text-sm font-medium text-white bg-white/10 hover:bg-white/20 rounded-xl transition-all">
                    ดูตะกร้า
                </a>
                <a href="{{ route('checkout.index') }}"
                   x-show="cartCount > 0"
                   class="flex-1 px-4 py-2.5 text-center text-sm font-medium text-white bg-gradient-to-r from-orange-500 to-red-500 hover:from-orange-600 hover:to-red-600 rounded-xl shadow-lg hover:shadow-orange-500/30 transition-all">
                    <i class="fas fa-credit-card mr-1"></i>
                    ชำระเงิน
                </a>
            </div>
        </div>
    </div>
</div>
