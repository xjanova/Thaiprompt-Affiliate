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

<header class="glass-fusion border-b border-white/30 flex items-center justify-between px-4 md:px-6 relative z-10"
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
                         class="absolute top-full left-0 right-0 mt-2 glass-dropdown rounded-xl shadow-2xl border border-white/30 overflow-hidden max-h-96 overflow-y-auto">
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

        {{-- User Profile Dropdown --}}
        <div x-data="{ profileOpen: false }" class="relative">
            <button @click="profileOpen = !profileOpen"
                    type="button"
                    class="flex items-center gap-2 p-2 pr-3 rounded-xl glass-neu hover:bg-white/20 transition-all hover:scale-105 active:scale-95">
                <div class="w-8 h-8 bg-gradient-to-br from-yellow-400 to-orange-600 rounded-lg flex items-center justify-center shadow-lg">
                    <span class="text-white text-sm font-bold drop-shadow">{{ substr(Auth::user()->name, 0, 1) }}</span>
                </div>
                <span class="hidden md:block text-white font-medium text-sm drop-shadow">{{ Auth::user()->name }}</span>
                <i class="fas fa-chevron-down text-white/60 text-xs drop-shadow"></i>
            </button>

            {{-- Profile Dropdown --}}
            <div x-show="profileOpen"
                 @click.outside="profileOpen = false"
                 x-transition
                 class="absolute top-full right-0 mt-2 w-56 glass-dropdown rounded-xl shadow-2xl border border-white/30 overflow-hidden">
                {{-- User Info --}}
                <div class="px-4 py-3 border-b border-white/20 bg-black/20">
                    <p class="font-medium text-white text-sm drop-shadow">{{ Auth::user()->name }}</p>
                    <p class="text-xs text-white/70 truncate">{{ Auth::user()->email }}</p>
                </div>

                {{-- Menu Items --}}
                <div class="py-2">
                    <a href="{{ route('admin.profile.index') }}"
                       class="flex items-center gap-3 px-4 py-2 hover:bg-white/10 transition-colors text-white text-sm">
                        <i class="fas fa-user-circle w-4 drop-shadow"></i>
                        <span class="drop-shadow">โปรไฟล์</span>
                    </a>
                    <a href="{{ route('admin.settings.index') }}"
                       class="flex items-center gap-3 px-4 py-2 hover:bg-white/10 transition-colors text-white text-sm">
                        <i class="fas fa-cog w-4 drop-shadow"></i>
                        <span class="drop-shadow">ตั้งค่า</span>
                    </a>
                    <a href="{{ route('admin.user-guide.index') }}"
                       class="flex items-center gap-3 px-4 py-2 hover:bg-white/10 transition-colors text-white text-sm">
                        <i class="fas fa-book-open w-4 drop-shadow"></i>
                        <span class="drop-shadow">คู่มือการใช้งาน</span>
                    </a>
                </div>

                {{-- Logout --}}
                <div class="border-t border-white/20">
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit"
                                class="w-full flex items-center gap-3 px-4 py-3 hover:bg-white/10 transition-colors text-white text-sm">
                            <i class="fas fa-sign-out-alt w-4 drop-shadow"></i>
                            <span class="drop-shadow">ออกจากระบบ</span>
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
