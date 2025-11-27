{{--
/**
 * Navbar V3 Component - Top Navigation Bar สำหรับ Admin Dashboard แบบ Dashboard4
 *
 * @props
 * @param string $pageTitle หัวข้อหน้า (optional)
 * @param bool $showSearch แสดง search bar หรือไม่ (default: true)
 *
 * @example
 * <x-arrow-x.navbar-v3 />
 *
 * @example with custom title
 * <x-arrow-x.navbar-v3 pageTitle="จัดการผู้ใช้" />
 *
 * @example without search
 * <x-arrow-x.navbar-v3 :showSearch="false" />
 *
 * @tip Component นี้รองรับ dark mode toggle
 * @tip แสดง notifications และ user profile dropdown
 */
--}}

@props([
    'pageTitle' => null,
    'showSearch' => true,
])

<header class="glass-fusion border-b border-white/30 flex items-center justify-between px-4 md:px-6 relative z-50"
         style="height: var(--arrow-x-navbar-height, 64px)">
    {{-- Left Section: Page Title --}}
    <div class="flex items-center gap-4">
        {{-- Mobile Menu Toggle (Burger Menu) - กระพริบทุก 30 วินาที + Tooltip ทุก 15 วัน --}}
        <div x-data="{
            blinking: false,
            showTooltip: false,
            startBlink() {
                this.blinking = true;
                setTimeout(() => { this.blinking = false; }, 1500);
            },
            checkTooltip() {
                const lastShown = localStorage.getItem('burgerTooltipLastShown');
                const now = new Date().getTime();
                const fifteenDays = 15 * 24 * 60 * 60 * 1000;

                if (!lastShown || (now - parseInt(lastShown)) > fifteenDays) {
                    // แสดง tooltip หลังจาก 2 วินาที
                    setTimeout(() => {
                        this.showTooltip = true;
                        // ซ่อนหลัง 10 วินาที
                        setTimeout(() => {
                            this.showTooltip = false;
                            localStorage.setItem('burgerTooltipLastShown', now.toString());
                        }, 10000);
                    }, 2000);
                }
            }
        }"
        x-init="
            setInterval(() => { startBlink(); }, 30000);
            checkTooltip();
        "
        class="md:hidden relative">
            <button @click="$store.sidebar.toggle(); showTooltip = false"
                    type="button"
                    class="p-2 rounded-lg hover:bg-white/20 transition-all hover:scale-110 active:scale-95"
                    :class="blinking ? 'animate-blink-burger' : ''">
                <i class="fas fa-bars text-white text-lg drop-shadow"></i>
            </button>

            {{-- Tooltip --}}
            <div x-show="showTooltip"
                 x-transition
                 @click="showTooltip = false"
                 class="absolute top-full left-0 mt-2 w-64 glass-dropdown rounded-xl shadow-2xl border border-white/30 p-4 z-50 cursor-pointer">
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-info-circle text-white"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-white font-medium text-sm mb-1">💡 เคล็ดลับ</p>
                        <p class="text-white/80 text-xs">กดที่ปุ่ม <i class="fas fa-bars mx-1"></i> นี้เพื่อเปิดเมนู</p>
                    </div>
                    <button @click.stop="showTooltip = false; localStorage.setItem('burgerTooltipLastShown', new Date().getTime().toString())"
                            class="flex-shrink-0 text-white/60 hover:text-white">
                        <i class="fas fa-times text-sm"></i>
                    </button>
                </div>
            </div>
        </div>

        {{-- Page Title --}}
        @if($pageTitle)
            <h1 class="text-xl md:text-2xl font-bold text-white drop-shadow-lg">
                {{ $pageTitle }}
            </h1>
        @else
            <h1 class="text-xl md:text-2xl font-bold text-white drop-shadow-lg">
                @yield('page-title', 'Dashboard')
            </h1>
        @endif
    </div>

    {{-- Right Section: Search, Notifications, Dark Mode, Profile --}}
    <div class="flex items-center gap-2 md:gap-4">
        {{-- 🇹🇭 Wiki Button - ลิงก์ไปหน้า Wiki หลัก (แสดงทุกขนาดหน้าจอ) --}}
        <a href="{{ route('wiki.index') }}"
           class="inline-flex items-center gap-1.5 sm:gap-2 px-2 sm:px-3 py-1.5 sm:py-2 text-xs sm:text-sm font-bold rounded-lg sm:rounded-xl transition-all duration-300 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white shadow-lg hover:shadow-xl hover:shadow-orange-500/30 transform hover:scale-105 relative group"
           title="🇹🇭 เรื่องราว ThaiPrompt - ทำไมต้องมีระบบของคนไทย">
            <span class="text-sm sm:text-base group-hover:scale-125 group-hover:rotate-12 transition-all duration-300">🇹🇭</span>
            <span class="hidden xs:inline group-hover:translate-x-0.5 transition-transform duration-300">wiki</span>
            <span class="absolute -top-1 -right-1 w-2 h-2 sm:w-2.5 sm:h-2.5 bg-red-500 rounded-full animate-ping"></span>
            <span class="absolute -top-1 -right-1 w-2 h-2 sm:w-2.5 sm:h-2.5 bg-red-500 rounded-full"></span>
        </a>

        {{-- Search Bar (Hidden on Mobile) --}}
        @if($showSearch)
            <div class="hidden md:block" x-data="{ searchOpen: false }">
                <div class="relative">
                    <input type="text"
                           placeholder="ค้นหา..."
                           x-model="searchQuery"
                           @focus="searchOpen = true"
                           @blur="setTimeout(() => searchOpen = false, 200)"
                           class="w-64 px-4 py-2 pl-10 glass-neu text-white placeholder-white/60 rounded-xl border border-white/20 focus:border-white/40 focus:ring-2 focus:ring-white/20 transition-all">
                    <div class="absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none">
                        <i class="fas fa-search text-white/60 drop-shadow"></i>
                    </div>

                    {{-- Search Results Dropdown --}}
                    <div x-show="searchOpen && searchQuery.length > 0"
                         x-transition
                         class="absolute top-full left-0 right-0 mt-2 glass-dropdown rounded-xl shadow-2xl border border-white/30 overflow-hidden max-h-96 overflow-y-auto z-[9999]">
                        <div class="p-4 bg-black/20">
                            <p class="text-white/80 text-sm">
                                <i class="fas fa-search mr-2"></i>
                                ค้นหา: <span x-text="searchQuery" class="font-bold"></span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Notifications (ใช้ Notification Bell V3 Component) --}}
        <x-arrow-x.navbar.notification-bell-v3 />

        {{-- Language Switcher --}}
        <x-arrow-x.language-switcher />

        {{-- Theme Customizer --}}
        <button @click="$dispatch('toggle-customizer')"
                type="button"
                class="p-3 rounded-xl glass-neu hover:bg-white/20 transition-all hover:scale-110 active:scale-95"
                title="ปรับแต่งธีม">
            <i class="fas fa-paint-brush text-white drop-shadow"></i>
        </button>

        {{-- Dark Mode Toggle --}}
        <button @click="$store.theme.toggle()"
                type="button"
                class="p-3 rounded-xl glass-neu hover:bg-white/20 transition-all hover:scale-110 active:scale-95"
                :title="$store.theme.isDark ? 'เปลี่ยนเป็นโหมดสว่าง' : 'เปลี่ยนเป็นโหมดมืด'">
            <i x-show="!$store.theme.isDark" class="fas fa-moon text-white drop-shadow"></i>
            <i x-show="$store.theme.isDark" class="fas fa-sun text-yellow-300 drop-shadow"></i>
        </button>

        {{-- User Profile Dropdown with Dashboard Switcher --}}
        @php
            $user = Auth::user();
            $currentRoute = request()->route() ? request()->route()->getName() : '';

            // ตรวจสอบว่ากำลังอยู่ที่ dashboard ไหน
            $isAdmin = str_starts_with($currentRoute, 'admin.');
            $isSeller = str_starts_with($currentRoute, 'seller.');
            $isUser = str_starts_with($currentRoute, 'user.');

            // ตรวจสอบสิทธิ์ที่ผู้ใช้มี
            $hasAdminAccess = $user->hasRole(['admin', 'super_admin']);
            $hasSellerAccess = $user->hasRole(['seller', 'super_admin']);
            $hasUserAccess = true; // ทุกคนสามารถเข้าใช้ user dashboard ได้

            // นับจำนวน dashboard ที่สามารถเข้าถึงได้
            $availableDashboards = ($hasAdminAccess ? 1 : 0) + ($hasSellerAccess ? 1 : 0) + ($hasUserAccess ? 1 : 0);
        @endphp

        <div x-data="{ profileOpen: false }" class="relative">
            <button @click="profileOpen = !profileOpen"
                    type="button"
                    class="flex items-center gap-2 p-2 pr-3 rounded-xl glass-neu hover:bg-white/20 transition-all hover:scale-105 active:scale-95">
                {{-- Avatar - ใช้ profile_picture_url accessor พร้อม fallback --}}
                <img src="{{ $user->profile_picture_url }}"
                     alt="{{ $user->name }}"
                     class="w-8 h-8 rounded-lg object-cover shadow-lg"
                     onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name={{ urlencode(substr($user->name, 0, 1)) }}&background=F59E0B&color=fff&size=64';">
                <span class="hidden md:block text-white font-medium text-sm drop-shadow">{{ $user->name }}</span>
                <i class="fas fa-chevron-down text-white/60 text-xs drop-shadow transition-transform duration-200" :class="profileOpen ? 'rotate-180' : ''"></i>
            </button>

            {{-- Profile Dropdown --}}
            <div x-show="profileOpen"
                 x-cloak
                 @click.outside="profileOpen = false"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="absolute top-full right-0 mt-2 w-72 glass-dropdown rounded-2xl shadow-2xl border border-white/30 overflow-hidden z-[9999]">

                {{-- User Info Header --}}
                <div class="px-4 py-4 bg-gradient-to-br from-indigo-600/30 to-purple-600/30 border-b border-white/20">
                    <div class="flex items-center gap-3">
                        {{-- Avatar ใหญ่ - ใช้ profile_picture_url accessor พร้อม fallback --}}
                        <img src="{{ $user->profile_picture_url }}"
                             alt="{{ $user->name }}"
                             class="w-12 h-12 rounded-xl object-cover shadow-lg"
                             onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name={{ urlencode(substr($user->name, 0, 1)) }}&background=F59E0B&color=fff&size=96';">
                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-white text-sm drop-shadow truncate">{{ $user->name }}</p>
                            <p class="text-xs text-white/70 truncate">{{ $user->email }}</p>
                            <div class="flex items-center gap-1 mt-1">
                                @if($isAdmin)
                                    <span class="px-2 py-0.5 bg-red-500/80 rounded-full text-xs font-medium text-white">แอดมิน</span>
                                @elseif($isSeller)
                                    <span class="px-2 py-0.5 bg-green-500/80 rounded-full text-xs font-medium text-white">ร้านค้า</span>
                                @else
                                    <span class="px-2 py-0.5 bg-blue-500/80 rounded-full text-xs font-medium text-white">ผู้ใช้</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Dashboard Switcher Section --}}
                @if($availableDashboards > 1)
                <div class="px-2 py-2 border-b border-white/20">
                    <p class="px-2 py-1 text-xs font-bold text-white/60 uppercase tracking-wider">สลับแดชบอร์ด</p>

                    @if($hasAdminAccess)
                    <a href="{{ route('admin.dashboard') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-white/10 transition-all group {{ $isAdmin ? 'bg-white/10' : '' }}">
                        <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-red-500 to-pink-500 flex items-center justify-center text-white shadow group-hover:scale-110 transition-transform">
                            <i class="fas fa-cog text-sm"></i>
                        </div>
                        <div class="flex-1">
                            <p class="font-medium text-white text-sm drop-shadow">แอดมิน</p>
                            <p class="text-xs text-white/60">จัดการระบบ</p>
                        </div>
                        @if($isAdmin)
                            <i class="fas fa-check-circle text-green-400"></i>
                        @endif
                    </a>
                    @endif

                    @if($hasSellerAccess)
                    <a href="{{ route('seller.dashboard') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-white/10 transition-all group {{ $isSeller ? 'bg-white/10' : '' }}">
                        <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-green-500 to-emerald-500 flex items-center justify-center text-white shadow group-hover:scale-110 transition-transform">
                            <i class="fas fa-store text-sm"></i>
                        </div>
                        <div class="flex-1">
                            <p class="font-medium text-white text-sm drop-shadow">ร้านค้า</p>
                            <p class="text-xs text-white/60">จัดการสินค้า</p>
                        </div>
                        @if($isSeller)
                            <i class="fas fa-check-circle text-green-400"></i>
                        @endif
                    </a>
                    @endif

                    @if($hasUserAccess)
                    <a href="{{ route('user.dashboard') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-white/10 transition-all group {{ $isUser ? 'bg-white/10' : '' }}">
                        <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-blue-500 to-indigo-500 flex items-center justify-center text-white shadow group-hover:scale-110 transition-transform">
                            <i class="fas fa-user text-sm"></i>
                        </div>
                        <div class="flex-1">
                            <p class="font-medium text-white text-sm drop-shadow">ผู้ใช้งาน</p>
                            <p class="text-xs text-white/60">แดชบอร์ดส่วนตัว</p>
                        </div>
                        @if($isUser)
                            <i class="fas fa-check-circle text-green-400"></i>
                        @endif
                    </a>
                    @endif
                </div>
                @endif

                {{-- Menu Items --}}
                <div class="px-2 py-2 border-b border-white/20">
                    <p class="px-2 py-1 text-xs font-bold text-white/60 uppercase tracking-wider">เมนูลัด</p>

                    <a href="{{ route('admin.profile.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-white/10 transition-all group">
                        <div class="w-9 h-9 rounded-lg bg-white/10 flex items-center justify-center text-white/80 group-hover:bg-indigo-500/30 group-hover:text-white transition-all">
                            <i class="fas fa-user-circle"></i>
                        </div>
                        <span class="text-white text-sm drop-shadow">โปรไฟล์</span>
                    </a>

                    <a href="{{ route('admin.settings.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-white/10 transition-all group">
                        <div class="w-9 h-9 rounded-lg bg-white/10 flex items-center justify-center text-white/80 group-hover:bg-indigo-500/30 group-hover:text-white transition-all">
                            <i class="fas fa-cog"></i>
                        </div>
                        <span class="text-white text-sm drop-shadow">ตั้งค่า</span>
                    </a>

                    <a href="{{ route('admin.user-guide.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-white/10 transition-all group">
                        <div class="w-9 h-9 rounded-lg bg-white/10 flex items-center justify-center text-white/80 group-hover:bg-indigo-500/30 group-hover:text-white transition-all">
                            <i class="fas fa-book-open"></i>
                        </div>
                        <span class="text-white text-sm drop-shadow">คู่มือการใช้งาน</span>
                    </a>
                </div>

                {{-- Logout --}}
                <div class="px-2 py-2">
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit"
                                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-red-500/20 transition-all group">
                            <div class="w-9 h-9 rounded-lg bg-white/10 flex items-center justify-center text-white/80 group-hover:bg-red-500/30 group-hover:text-red-300 transition-all">
                                <i class="fas fa-sign-out-alt"></i>
                            </div>
                            <span class="text-white text-sm drop-shadow group-hover:text-red-300">ออกจากระบบ</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>

<style>
/**
 * Glass Fusion Effect - ความโปร่งใสพร้อม backdrop blur
 */
.glass-fusion {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
}

/**
 * Glass Dropdown Effect - เข้มขึ้นสำหรับ dropdown menus
 */
.glass-dropdown {
    background: rgba(0, 0, 0, 0.4);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
}

/**
 * Glass Neumorphic Effect - สำหรับ buttons
 */
.glass-neu {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}

/**
 * Blink Animation สำหรับ Burger Menu - กระพริบทุก 30 วินาที
 */
@keyframes blink-burger {
    0%, 100% {
        opacity: 1;
        transform: scale(1);
    }
    25% {
        opacity: 0.3;
        transform: scale(0.95);
    }
    50% {
        opacity: 1;
        transform: scale(1.1);
    }
    75% {
        opacity: 0.3;
        transform: scale(0.95);
    }
}

.animate-blink-burger {
    animation: blink-burger 0.6s ease-in-out 3;
}
</style>
