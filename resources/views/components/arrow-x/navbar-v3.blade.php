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

<header class="h-16 glass-fusion border-b border-white/30 flex items-center justify-between px-4 md:px-6 relative z-10">
    {{-- Left Section: Page Title --}}
    <div class="flex items-center gap-4">
        {{-- Mobile Menu Toggle (Burger Menu) - กระพริบทุก 30 วินาที --}}
        <div x-data="{
            blinking: false,
            startBlink() {
                this.blinking = true;
                setTimeout(() => { this.blinking = false; }, 1500);
            }
        }"
        x-init="
            setInterval(() => { startBlink(); }, 30000);
        "
        class="md:hidden">
            <button @click="sidebarOpen = !sidebarOpen"
                    type="button"
                    class="p-2 rounded-lg hover:bg-white/20 transition-all hover:scale-110 active:scale-95"
                    :class="blinking ? 'animate-blink-burger' : ''">
                <i class="fas fa-bars text-white text-lg drop-shadow"></i>
            </button>
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

        {{-- Notifications --}}
        <div x-data="{ notificationOpen: false }" class="relative">
            <button @click="notificationOpen = !notificationOpen"
                    type="button"
                    class="relative p-3 rounded-xl glass-neu hover:bg-white/20 transition-all hover:scale-110 active:scale-95">
                <i class="fas fa-bell text-white drop-shadow"></i>

                {{-- Notification Badge --}}
                @php
                    $unreadNotifications = Auth::user()->unreadNotifications->count();
                @endphp
                @if($unreadNotifications > 0)
                    <span class="absolute top-1 right-1 w-2 h-2 bg-gradient-to-r from-red-500 to-pink-600 rounded-full shadow-lg animate-pulse"></span>
                @endif
            </button>

            {{-- Notifications Dropdown --}}
            <div x-show="notificationOpen"
                 @click.outside="notificationOpen = false"
                 x-transition
                 class="absolute top-full right-0 mt-2 w-80 glass-dropdown rounded-xl shadow-2xl border border-white/30 overflow-hidden">
                {{-- Header --}}
                <div class="px-4 py-3 border-b border-white/20 bg-black/20">
                    <div class="flex items-center justify-between">
                        <h3 class="font-bold text-white drop-shadow">การแจ้งเตือน</h3>
                        @if($unreadNotifications > 0)
                            <span class="px-2 py-0.5 bg-gradient-to-r from-red-500 to-pink-600 text-white text-xs font-bold rounded-full shadow-lg">
                                {{ $unreadNotifications }}
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Notifications List --}}
                <div class="max-h-96 overflow-y-auto">
                    @forelse(Auth::user()->notifications->take(5) as $notification)
                        <a href="#"
                           class="block px-4 py-3 hover:bg-white/10 transition-colors border-b border-white/10 last:border-0">
                            <div class="flex items-start gap-3">
                                <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg">
                                    <i class="fas fa-info-circle text-white"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-white truncate">
                                        {{ $notification->data['title'] ?? 'แจ้งเตือน' }}
                                    </p>
                                    <p class="text-xs text-white/70 line-clamp-2">
                                        {{ $notification->data['message'] ?? '' }}
                                    </p>
                                    <p class="text-xs text-white/50 mt-1">
                                        {{ $notification->created_at->diffForHumans() }}
                                    </p>
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="px-4 py-8 text-center">
                            <i class="fas fa-bell-slash text-4xl text-white/30 mb-2"></i>
                            <p class="text-white/60 text-sm">ไม่มีการแจ้งเตือน</p>
                        </div>
                    @endforelse
                </div>

                {{-- Footer --}}
                @if($unreadNotifications > 0)
                    <div class="px-4 py-3 border-t border-white/20">
                        <a href="{{ route('admin.notifications.index') }}"
                           class="block text-center text-sm text-white/80 hover:text-white transition-colors">
                            ดูทั้งหมด
                            <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                @endif
            </div>
        </div>

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
                    <a href="#"
                       class="flex items-center gap-3 px-4 py-2 opacity-50 cursor-not-allowed text-white text-sm">
                        <i class="fas fa-user-circle w-4 drop-shadow"></i>
                        <span class="drop-shadow">โปรไฟล์ (Coming Soon)</span>
                    </a>
                    <a href="{{ route('admin.settings.index') }}"
                       class="flex items-center gap-3 px-4 py-2 hover:bg-white/10 transition-colors text-white text-sm">
                        <i class="fas fa-cog w-4 drop-shadow"></i>
                        <span class="drop-shadow">ตั้งค่า</span>
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
