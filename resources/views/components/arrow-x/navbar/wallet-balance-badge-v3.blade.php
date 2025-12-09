{{--
/**
 * Wallet Balance Badge V3 Component - แสดงยอดคงเหลือกระเป๋าเงินใน Navbar
 *
 * Component นี้แสดงยอดคงเหลือกระเป๋าเงินแบบ modern พร้อม:
 * - ✅ Glassmorphism effects
 * - ✅ Real-time balance refresh
 * - ✅ Offline mode support
 * - ✅ Quick action buttons (เติมเงิน, โอนเงิน)
 * - ✅ Animated number counter
 * - ✅ Accessibility (ARIA labels)
 * - ✅ Smooth animations
 *
 * @example
 * <x-arrow-x.navbar.wallet-balance-badge-v3 />
 */
--}}

@auth
@php
    // ดึงข้อมูล wallet ของผู้ใช้ปัจจุบัน
    $user = Auth::user();
    $wallet = $user->wallet;
    $balance = $wallet ? (float) $wallet->balance : 0;
    $currency = $wallet->currency ?? 'THB';

    // ตรวจสอบ route prefix ปัจจุบัน (user/seller/admin)
    $routePrefix = '';
    if (request()->is('user/*') || request()->is('user')) {
        $routePrefix = 'user';
    } elseif (request()->is('seller/*') || request()->is('seller')) {
        $routePrefix = 'seller';
    } elseif (request()->is('admin/*') || request()->is('admin')) {
        $routePrefix = 'admin';
    } else {
        $routePrefix = 'user'; // default
    }

    // สร้าง route สำหรับ wallet
    $walletRoute = match($routePrefix) {
        'admin' => route('admin.wallet.index'),
        'seller' => route('user.wallet.index'), // seller ใช้ user wallet
        default => route('user.wallet.index'),
    };

    // Route สำหรับ topup
    $topupRoute = route('user.wallet.topup');
    $transferRoute = route('user.wallet.transfer');
@endphp

<div x-data="{
    open: false,
    balance: {{ $balance }},
    displayBalance: {{ $balance }},
    currency: '{{ $currency }}',
    loading: false,
    routePrefix: '{{ $routePrefix }}',

    // 📡 Offline Support
    isOnline: navigator.onLine,
    cacheKey: 'wallet_balance_cache_{{ $user->id }}',
    cacheExpiry: 2 * 60 * 1000, // 2 นาที

    // 💵 Format ตัวเลขเป็นสกุลเงิน
    formatCurrency(amount) {
        return new Intl.NumberFormat('th-TH', {
            style: 'decimal',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(amount);
    },

    // 📊 Animate counter
    animateBalance(newBalance) {
        const startBalance = this.displayBalance;
        const diff = newBalance - startBalance;
        const duration = 500; // ms
        const startTime = performance.now();

        const animate = (currentTime) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);

            // Easing function (ease-out)
            const easeOut = 1 - Math.pow(1 - progress, 3);

            this.displayBalance = startBalance + (diff * easeOut);

            if (progress < 1) {
                requestAnimationFrame(animate);
            } else {
                this.displayBalance = newBalance;
            }
        };

        requestAnimationFrame(animate);
    },

    // 🔄 โหลดยอดคงเหลือจาก API (ใช้ web session auth)
    async loadBalance() {
        if (!this.isOnline) {
            console.info('📴 Offline mode: Using cached balance');
            this.loadFromCache();
            return;
        }

        this.loading = true;

        try {
            // ใช้ route ตาม prefix ปัจจุบัน (user/seller ใช้ user wallet)
            const url = this.routePrefix === 'admin'
                ? '/user/wallet/balance-ajax'
                : '/user/wallet/balance-ajax';

            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                }
            });

            if (response.ok) {
                const data = await response.json();
                const newBalance = parseFloat(data.balance) || 0;

                if (newBalance !== this.balance) {
                    this.animateBalance(newBalance);
                    this.balance = newBalance;
                }

                // 💾 บันทึกลง cache
                this.saveToCache(data);
            } else {
                this.loadFromCache();
            }
        } catch (error) {
            console.error('Error loading wallet balance:', error);
            this.loadFromCache();
        }

        this.loading = false;
    },

    // 💾 บันทึกข้อมูลลง localStorage
    saveToCache(data) {
        try {
            const cache = {
                data: data,
                timestamp: Date.now()
            };
            localStorage.setItem(this.cacheKey, JSON.stringify(cache));
        } catch (error) {
            console.error('Failed to cache wallet balance:', error);
        }
    },

    // 📂 โหลดข้อมูลจาก localStorage
    loadFromCache() {
        try {
            const cached = localStorage.getItem(this.cacheKey);
            if (!cached) return;

            const cache = JSON.parse(cached);

            // ตรวจสอบว่า cache หมดอายุหรือยัง
            if (Date.now() - cache.timestamp > this.cacheExpiry) {
                localStorage.removeItem(this.cacheKey);
                return;
            }

            const newBalance = parseFloat(cache.data.balance) || 0;
            if (newBalance !== this.balance) {
                this.animateBalance(newBalance);
                this.balance = newBalance;
            }
        } catch (error) {
            console.error('Failed to load from cache:', error);
        }
    },

    closeDropdown() {
        this.open = false;
    }
}"
x-init="
    // ฟัง online/offline events
    window.addEventListener('online', () => {
        this.isOnline = true;
        this.loadBalance();
    });

    window.addEventListener('offline', () => {
        this.isOnline = false;
    });

    // รีเฟรชทุก 60 วินาที
    setInterval(() => {
        if (this.isOnline) {
            this.loadBalance();
        }
    }, 60000);

    // ฟัง custom event สำหรับ refresh balance (เช่น หลังโอนเงิน)
    window.addEventListener('wallet-updated', () => {
        this.loadBalance();
    });
"
@keydown.escape.window="if (open) closeDropdown()"
class="relative">

    {{-- Wallet Button พร้อม Glassmorphism Style --}}
    <button
        id="wallet-balance-button"
        @click="open = !open"
        :aria-expanded="open.toString()"
        aria-haspopup="true"
        :aria-label="`กระเป๋าเงิน ยอดคงเหลือ ${formatCurrency(displayBalance)} บาท`"
        type="button"
        class="relative flex items-center gap-2 px-3 py-2 rounded-xl glass-neu hover:bg-white/20 transition-all hover:scale-105 active:scale-95 group"
        title="กระเป๋าเงิน">

        {{-- Wallet Icon with gradient background --}}
        <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-gradient-to-br from-emerald-400 to-green-600 shadow-lg group-hover:shadow-emerald-500/30 transition-all">
            <i class="fas fa-wallet text-white text-sm drop-shadow"></i>
        </div>

        {{-- Balance Display --}}
        <div class="hidden sm:flex flex-col items-start">
            <span class="text-[10px] text-white/60 leading-none">ยอดคงเหลือ</span>
            <span class="text-sm font-bold text-white drop-shadow leading-tight"
                  :class="{ 'animate-pulse': loading }"
                  x-text="formatCurrency(displayBalance)"></span>
        </div>

        {{-- Mobile: แสดงเฉพาะตัวเลขสั้นๆ --}}
        <span class="sm:hidden text-xs font-bold text-white drop-shadow"
              x-text="balance >= 1000 ? (balance >= 1000000 ? (balance / 1000000).toFixed(1) + 'M' : (balance / 1000).toFixed(1) + 'K') : formatCurrency(displayBalance)"></span>

        {{-- Loading indicator --}}
        <div x-show="loading" class="absolute -top-1 -right-1">
            <svg class="animate-spin h-3 w-3 text-emerald-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>

        {{-- Offline Indicator --}}
        <span
            x-show="!isOnline"
            title="ออฟไลน์"
            class="absolute -bottom-1 -right-1 w-3 h-3 bg-gray-400 border-2 border-white/50 rounded-full"
        ></span>

        {{-- Dropdown arrow --}}
        <i class="fas fa-chevron-down text-white/60 text-[10px] hidden sm:block transition-transform duration-200"
           :class="open ? 'rotate-180' : ''"></i>
    </button>

    {{-- Dropdown พร้อม Glassmorphism --}}
    <div
        x-show="open"
        @click.away="closeDropdown()"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        role="menu"
        aria-labelledby="wallet-balance-button"
        class="absolute right-0 z-50 w-80 mt-2 glass-dropdown rounded-2xl shadow-2xl border border-white/30 overflow-hidden"
        style="display: none;">

        {{-- Header with Balance --}}
        <div class="px-5 py-4 bg-gradient-to-br from-emerald-600/40 to-green-600/40 border-b border-white/20">
            <div class="flex items-center gap-4">
                {{-- Big Wallet Icon --}}
                <div class="flex-shrink-0 w-14 h-14 bg-gradient-to-br from-emerald-400 to-green-600 rounded-2xl flex items-center justify-center shadow-xl">
                    <i class="fas fa-wallet text-white text-2xl drop-shadow-lg"></i>
                </div>

                <div class="flex-1 min-w-0">
                    <p class="text-xs text-white/70 mb-1">ยอดคงเหลือในกระเป๋าเงิน</p>
                    <p class="text-2xl font-bold text-white drop-shadow-lg"
                       :class="{ 'animate-pulse': loading }">
                        <span x-text="formatCurrency(displayBalance)"></span>
                        <span class="text-sm font-normal text-white/80">฿</span>
                    </p>
                </div>
            </div>

            {{-- Offline Warning --}}
            <div x-show="!isOnline" class="flex items-center gap-2 mt-3 px-3 py-2 glass-neu rounded-lg text-xs text-white/80">
                <i class="fas fa-wifi-slash"></i>
                <span>📴 ออฟไลน์ - แสดงข้อมูลจากแคช</span>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="p-3 border-b border-white/10">
            <p class="px-2 py-1 text-xs font-bold text-white/60 uppercase tracking-wider mb-2">จัดการเงิน</p>

            <div class="grid grid-cols-2 gap-2">
                {{-- เติมเงิน --}}
                <a href="{{ $topupRoute }}"
                   class="flex flex-col items-center gap-2 p-3 rounded-xl hover:bg-white/10 transition-all group">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center text-white shadow-lg group-hover:scale-110 group-hover:shadow-orange-500/40 transition-all">
                        <i class="fas fa-plus-circle"></i>
                    </div>
                    <span class="text-sm text-white font-medium">เติมเงิน</span>
                </a>

                {{-- โอนเงิน --}}
                <a href="{{ $transferRoute }}"
                   class="flex flex-col items-center gap-2 p-3 rounded-xl hover:bg-white/10 transition-all group">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-400 to-indigo-500 flex items-center justify-center text-white shadow-lg group-hover:scale-110 group-hover:shadow-blue-500/40 transition-all">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                    <span class="text-sm text-white font-medium">โอนเงิน</span>
                </a>
            </div>
        </div>

        {{-- Wallet Stats (ถ้ามีข้อมูล) --}}
        @if($wallet)
        <div class="p-3 border-b border-white/10">
            <p class="px-2 py-1 text-xs font-bold text-white/60 uppercase tracking-wider mb-2">สถิติ</p>

            <div class="grid grid-cols-2 gap-3">
                {{-- รายรับรวม --}}
                <div class="px-3 py-2 rounded-xl bg-green-500/10 border border-green-500/20">
                    <p class="text-xs text-white/60 mb-0.5">
                        <i class="fas fa-arrow-down text-green-400 mr-1"></i>รายรับรวม
                    </p>
                    <p class="text-sm font-bold text-green-400">
                        {{ number_format($wallet->total_income ?? 0, 2) }} ฿
                    </p>
                </div>

                {{-- รายจ่ายรวม --}}
                <div class="px-3 py-2 rounded-xl bg-red-500/10 border border-red-500/20">
                    <p class="text-xs text-white/60 mb-0.5">
                        <i class="fas fa-arrow-up text-red-400 mr-1"></i>รายจ่ายรวม
                    </p>
                    <p class="text-sm font-bold text-red-400">
                        {{ number_format($wallet->total_expense ?? 0, 2) }} ฿
                    </p>
                </div>
            </div>
        </div>
        @endif

        {{-- Footer --}}
        <div class="px-4 py-3 bg-black/20">
            <a href="{{ $walletRoute }}"
               class="flex items-center justify-center gap-2 w-full px-4 py-2.5 rounded-xl bg-gradient-to-r from-emerald-500 to-green-600 hover:from-emerald-600 hover:to-green-700 text-white font-medium text-sm shadow-lg hover:shadow-emerald-500/30 transition-all">
                <i class="fas fa-wallet"></i>
                <span>จัดการกระเป๋าเงิน</span>
                <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
    </div>
</div>
@endauth
