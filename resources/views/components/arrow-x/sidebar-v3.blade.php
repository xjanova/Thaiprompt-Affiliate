{{--
/**
 * Sidebar V3 Component - Sidebar สำหรับ Admin Dashboard แบบ Dashboard4
 *
 * @props
 * @param string $title ชื่อแอพพลิเคชัน (default: config('app.name'))
 * @param string|null $logo URL ของโลโก้ (optional)
 * @param array $menuItems รายการเมนู (optional, ถ้าไม่ส่งจะใช้ default menu)
 *
 * @example
 * <x-arrow-x.sidebar-v3 title="TP-Affiliate" />
 *
 * @example with custom logo
 * <x-arrow-x.sidebar-v3
 *     title="Admin Panel"
 *     logo="{{ asset('images/logo.png') }}"
 * />
 *
 * @tip Component นี้ใช้ Alpine.js store สำหรับ sidebar state
 * @tip รองรับ dark mode อัตโนมัติผ่าน Tailwind dark: utilities
 * @tip Responsive: Mobile (drawer overlay), Desktop (fixed sidebar)
 */
--}}

@props([
    'title' => config('app.name'),
    'logo' => null,
])

{{-- Mobile Overlay (แสดงเมื่อ sidebarOpen = true บนมือถือ) --}}
<div x-show="sidebarOpen"
     @click="sidebarOpen = false"
     x-transition:enter="transition-opacity ease-linear duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition-opacity ease-linear duration-300"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 bg-black/50 z-30 md:hidden"
     x-cloak>
</div>

{{-- Sidebar Container --}}
<aside
    x-data="{
        hovered: false,
        autoHideMode: localStorage.getItem('sidebarAutoHide') === 'true' || false,
        toggleAutoHide() {
            this.autoHideMode = !this.autoHideMode;
            localStorage.setItem('sidebarAutoHide', this.autoHideMode);
            // เมื่อเปิด auto-hide ให้ปิด sidebar
            if (this.autoHideMode && window.innerWidth >= 768) {
                sidebarOpen = false;
            } else if (!this.autoHideMode && window.innerWidth >= 768) {
                sidebarOpen = true;
            }
        }
    }"
    @mouseenter="if (autoHideMode && !sidebarOpen && window.innerWidth >= 768) hovered = true"
    @mouseleave="if (autoHideMode && window.innerWidth >= 768) hovered = false"
    class="glass-fusion transition-all duration-300 flex flex-col border-r border-white/30 z-40
           fixed md:relative inset-y-0 left-0 w-64
           transform md:transform-none"
    :class="{
        'translate-x-0': sidebarOpen,
        '-translate-x-full': !sidebarOpen,
        'md:w-64': (autoHideMode && (sidebarOpen || hovered)) || (!autoHideMode && sidebarOpen),
        'md:w-20': autoHideMode && !sidebarOpen && !hovered
    }"
    x-cloak
>
    {{-- Logo Section --}}
    <div class="h-16 flex items-center justify-between px-4 border-b border-white/30">
        <div class="flex items-center gap-3 transition-all" x-show="sidebarOpen || hovered" x-transition>
            @if($logo)
                <img src="{{ $logo }}" alt="{{ $title }}" class="w-10 h-10 rounded-xl object-cover shadow-lg">
            @else
                <div class="w-10 h-10 bg-gradient-to-br from-cyan-400 via-blue-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg">
                    <i class="fas fa-rocket text-white text-lg"></i>
                </div>
            @endif

            <div>
                <h1 class="font-bold text-white text-sm drop-shadow-lg">{{ $title }}</h1>
                <p class="text-xs text-white/90 drop-shadow">V3 Dashboard</p>
            </div>
        </div>

        {{-- Toggle Button (แสดงเฉพาะบน Desktop) - เปลี่ยนไอคอนตามโหมด --}}
        <button @click="toggleAutoHide()"
                class="hidden md:block p-2 rounded-lg hover:bg-white/20 transition-all hover:scale-110 active:scale-95 group"
                type="button"
                :title="autoHideMode ? 'เปิดโหมดปกติ (ล็อค sidebar)' : 'เปิดโหมด Auto-hide'">
            {{-- Icon แม่กุญแจ (โหมดปกติ - locked) --}}
            <i x-show="!autoHideMode" class="fas fa-lock text-white drop-shadow group-hover:text-blue-300 transition"></i>
            {{-- Icon Burger Menu (โหมด Auto-hide) --}}
            <i x-show="autoHideMode" class="fas fa-bars text-white drop-shadow group-hover:text-purple-300 transition"></i>
        </button>

        {{-- Close Button (แสดงเฉพาะบน Mobile) --}}
        <button @click="sidebarOpen = false"
                class="md:hidden p-2 rounded-lg hover:bg-white/20 transition-all hover:scale-110 active:scale-95"
                type="button">
            <i class="fas fa-times text-white drop-shadow"></i>
        </button>
    </div>

    {{-- Navigation Menu --}}
    <nav class="flex-1 overflow-y-auto p-4 space-y-2 scrollbar-thin scrollbar-thumb-white/20 scrollbar-track-transparent"
         @click.away="if (!sidebarOpen && hovered && window.innerWidth >= 768) { hovered = false }">
        {{-- Dashboard --}}
        <a href="{{ route('admin.dashboard') }}"
           @click="if (window.innerWidth >= 768 && autoHideMode && hovered) { hovered = false }"
           class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.dashboard') ? 'bg-gradient-to-r from-blue-500 to-purple-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
            <i class="fas fa-home w-5 text-center drop-shadow"></i>
            <span x-show="sidebarOpen || hovered" x-transition class="font-medium drop-shadow whitespace-nowrap">แดชบอร์ด</span>
        </a>

        {{-- Users --}}
        <a href="{{ route('admin.users.index') }}"
           @click="if (window.innerWidth >= 768 && autoHideMode && hovered) { hovered = false }"
           class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.users.*') ? 'bg-gradient-to-r from-blue-500 to-purple-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
            <i class="fas fa-users w-5 text-center drop-shadow"></i>
            <span x-show="sidebarOpen || hovered" x-transition class="font-medium drop-shadow whitespace-nowrap">ผู้ใช้งาน</span>
        </a>

        {{-- Affiliates --}}
        <a href="{{ route('admin.affiliates.index') }}"
           @click="if (window.innerWidth >= 768 && autoHideMode && hovered) { hovered = false }"
           class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.affiliates.*') ? 'bg-gradient-to-r from-blue-500 to-purple-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
            <i class="fas fa-network-wired w-5 text-center drop-shadow"></i>
            <span x-show="sidebarOpen || hovered" x-transition class="font-medium drop-shadow whitespace-nowrap">Affiliate</span>
        </a>

        {{-- Commissions --}}
        <a href="{{ route('admin.commissions.index') }}"
           @click="if (window.innerWidth >= 768 && autoHideMode && hovered) { hovered = false }"
           class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.commissions.*') ? 'bg-gradient-to-r from-blue-500 to-purple-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
            <i class="fas fa-coins w-5 text-center drop-shadow"></i>
            <span x-show="sidebarOpen || hovered" x-transition class="font-medium drop-shadow whitespace-nowrap">คอมมิชชั่น</span>
        </a>

        {{-- Wallet --}}
        <a href="{{ route('admin.wallet.index') }}"
           @click="if (window.innerWidth >= 768 && autoHideMode && hovered) { hovered = false }"
           class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.wallet.*') ? 'bg-gradient-to-r from-blue-500 to-purple-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
            <i class="fas fa-wallet w-5 text-center drop-shadow"></i>
            <span x-show="sidebarOpen || hovered" x-transition class="font-medium drop-shadow whitespace-nowrap">กระเป๋าเงิน</span>
        </a>

        {{-- TPIX Blockchain --}}
        <a href="{{ route('admin.tpix.dashboard') }}"
           @click="if (window.innerWidth >= 768 && autoHideMode && hovered) { hovered = false }"
           class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.tpix.*') ? 'bg-gradient-to-r from-cyan-500 to-blue-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
            <i class="fas fa-cube w-5 text-center drop-shadow"></i>
            <span x-show="sidebarOpen || hovered" x-transition class="font-medium drop-shadow whitespace-nowrap">TPIX Blockchain</span>
        </a>

        {{-- Token Management --}}
        <a href="{{ route('admin.tokens.index') }}"
           @click="if (window.innerWidth >= 768 && autoHideMode && hovered) { hovered = false }"
           class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.tokens.*') ? 'bg-gradient-to-r from-yellow-500 to-orange-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
            <i class="fas fa-coins w-5 text-center drop-shadow"></i>
            <span x-show="sidebarOpen || hovered" x-transition class="font-medium drop-shadow whitespace-nowrap">Token Management</span>
        </a>

        {{-- LINE Bot --}}
        <a href="{{ route('admin.line-bot.ai.index') }}"
           @click="if (window.innerWidth >= 768 && autoHideMode && hovered) { hovered = false }"
           class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.line-bot.*') ? 'bg-gradient-to-r from-green-500 to-emerald-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
            <i class="fab fa-line w-5 text-center drop-shadow"></i>
            <span x-show="sidebarOpen || hovered" x-transition class="font-medium drop-shadow whitespace-nowrap">LINE Bot</span>
        </a>

        {{-- Trading Bot --}}
        <a href="{{ route('admin.trading-bot.dashboard') }}"
           @click="if (window.innerWidth >= 768 && autoHideMode && hovered) { hovered = false }"
           class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.trading-bot.*') ? 'bg-gradient-to-r from-orange-500 to-red-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
            <i class="fas fa-chart-line w-5 text-center drop-shadow"></i>
            <span x-show="sidebarOpen || hovered" x-transition class="font-medium drop-shadow whitespace-nowrap">Trading Bot</span>
        </a>

        {{-- Bot Automation --}}
        <a href="{{ route('admin.bot-automation.dashboard') }}"
           @click="if (window.innerWidth >= 768 && autoHideMode && hovered) { hovered = false }"
           class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.bot-automation.*') ? 'bg-gradient-to-r from-indigo-500 to-purple-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
            <i class="fas fa-robot w-5 text-center drop-shadow"></i>
            <span x-show="sidebarOpen || hovered" x-transition class="font-medium drop-shadow whitespace-nowrap">Bot Automation</span>
        </a>

        {{-- LINE OA --}}
        <a href="{{ route('admin.line-oa.index') }}"
           @click="if (window.innerWidth >= 768 && autoHideMode && hovered) { hovered = false }"
           class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.line-oa.*') || request()->routeIs('admin.line-analytics.*') ? 'bg-gradient-to-r from-green-600 to-teal-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
            <i class="fab fa-line w-5 text-center drop-shadow"></i>
            <span x-show="sidebarOpen || hovered" x-transition class="font-medium drop-shadow whitespace-nowrap">LINE OA</span>
        </a>

        {{-- Products --}}
        <a href="{{ route('admin.ecommerce.products.index') }}"
           class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.ecommerce.products.*') ? 'bg-gradient-to-r from-blue-500 to-purple-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
            <i class="fas fa-box w-5 text-center drop-shadow"></i>
            <span x-show="sidebarOpen || hovered" x-transition class="font-medium drop-shadow whitespace-nowrap">สินค้า</span>
        </a>

        {{-- Orders --}}
        <a href="{{ route('admin.ecommerce.orders.index') }}"
           class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.ecommerce.orders.*') ? 'bg-gradient-to-r from-blue-500 to-purple-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
            <i class="fas fa-shopping-cart w-5 text-center drop-shadow"></i>
            <span x-show="sidebarOpen || hovered" x-transition class="font-medium drop-shadow whitespace-nowrap">คำสั่งซื้อ</span>
        </a>

        {{-- Reports --}}
        <a href="{{ route('admin.ecommerce.reports') }}"
           class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.ecommerce.reports') ? 'bg-gradient-to-r from-blue-500 to-purple-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
            <i class="fas fa-chart-bar w-5 text-center drop-shadow"></i>
            <span x-show="sidebarOpen || hovered" x-transition class="font-medium drop-shadow whitespace-nowrap">รายงาน</span>
        </a>

        {{-- Divider --}}
        <div x-show="sidebarOpen" x-transition class="border-t border-white/30 my-4"></div>

        {{-- Settings --}}
        <a href="{{ route('admin.settings.index') }}"
           class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.settings.index') ? 'bg-gradient-to-r from-blue-500 to-purple-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
            <i class="fas fa-cog w-5 text-center drop-shadow"></i>
            <span x-show="sidebarOpen || hovered" x-transition class="font-medium drop-shadow whitespace-nowrap">ตั้งค่า</span>
        </a>

        {{-- Site Settings (โลโก้, SEO, Social Media) --}}
        <a href="{{ route('admin.site-settings.index') }}"
           class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.site-settings.*') ? 'bg-gradient-to-r from-blue-500 to-purple-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
            <i class="fas fa-palette w-5 text-center drop-shadow"></i>
            <span x-show="sidebarOpen || hovered" x-transition class="font-medium drop-shadow whitespace-nowrap">ตั้งค่าเว็บไซต์</span>
        </a>

        {{-- Arrow X Theme Customizer ⭐ NEW --}}
        <a href="{{ route('admin.arrow-x-theme.index') }}"
           @click="if (window.innerWidth >= 768 && autoHideMode && hovered) { hovered = false }"
           class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.arrow-x-theme.*') ? 'bg-gradient-to-r from-purple-500 to-pink-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
            <i class="fas fa-paint-brush w-5 text-center drop-shadow"></i>
            <span x-show="sidebarOpen || hovered" x-transition class="font-medium drop-shadow whitespace-nowrap">ปรับแต่งทีม Arrow X</span>
        </a>

        {{-- Help --}}
        <a href="#"
           class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform glass-neu text-white/90 hover:bg-white/20 hover:scale-105">
            <i class="fas fa-question-circle w-5 text-center drop-shadow"></i>
            <span x-show="sidebarOpen || hovered" x-transition class="font-medium drop-shadow whitespace-nowrap">ช่วยเหลือ</span>
        </a>
    </nav>

    {{-- Footer: Premium Logo + Version + License (Ultra 3D Style) --}}
    <div class="p-4 border-t border-white/30 relative overflow-hidden">
        {{-- Premium Background Glow --}}
        <div class="absolute inset-0 bg-gradient-to-t from-purple-500/10 via-transparent to-transparent pointer-events-none"></div>

        <div class="relative flex items-center gap-3">
            {{-- Ultra Premium 3D Logo with Multiple Layers --}}
            <div class="relative w-14 h-14 flex-shrink-0 group">
                {{-- Outer Glow Ring (Rotating) --}}
                <div class="absolute inset-0 rounded-2xl bg-gradient-to-r from-cyan-400 via-purple-500 to-pink-500 opacity-50 blur-md animate-spin-slow"></div>

                {{-- Middle Shine Layer --}}
                <div class="absolute inset-0.5 rounded-2xl bg-gradient-to-br from-white/30 to-transparent opacity-40"></div>

                {{-- Main Logo Container (3D Rotating) --}}
                <div class="relative w-full h-full bg-gradient-to-br from-cyan-400 via-blue-500 to-purple-600 rounded-2xl flex items-center justify-center shadow-2xl animate-rotate-y-3d group-hover:shadow-cyan-500/50 transition-all duration-300">
                    {{-- Inner Glow --}}
                    <div class="absolute inset-2 bg-gradient-to-br from-white/20 to-transparent rounded-xl"></div>

                    {{-- Rocket Icon with Pulse --}}
                    <i class="fas fa-rocket text-white text-2xl drop-shadow-2xl relative z-10 animate-float"></i>

                    {{-- Shine Effect --}}
                    <div class="absolute inset-0 bg-gradient-to-tr from-transparent via-white/30 to-transparent rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-300 shine-effect"></div>
                </div>

                {{-- Corner Sparkles (Absolute positioned) --}}
                <div class="absolute -top-1 -right-1 w-2 h-2 bg-yellow-300 rounded-full animate-ping opacity-75"></div>
                <div class="absolute -bottom-1 -left-1 w-1.5 h-1.5 bg-cyan-300 rounded-full animate-pulse delay-300"></div>
            </div>

            {{-- Version + License Info (Premium Style) - รองรับ Auto-hide Mode --}}
            <div x-show="sidebarOpen || hovered" x-transition class="flex-1 min-w-0">
                {{-- App Name with Gradient Text --}}
                <div class="font-black text-base tracking-wider mb-1 bg-gradient-to-r from-white via-cyan-200 to-purple-200 bg-clip-text text-transparent drop-shadow-2xl">
                    TP-AFFILIATE
                </div>

                {{-- Version Badge (Premium Chip) --}}
                @php
                    $version = file_exists(base_path('VERSION'))
                        ? trim(file_get_contents(base_path('VERSION')))
                        : '3.0.0';
                @endphp
                <div class="inline-flex items-center gap-1.5 px-2 py-0.5 bg-gradient-to-r from-blue-500/30 to-purple-500/30 rounded-full border border-blue-400/50 backdrop-blur-sm mb-1.5">
                    <i class="fas fa-code-branch text-[10px] text-blue-300 drop-shadow"></i>
                    <span class="text-[10px] font-bold font-mono text-white drop-shadow tracking-wide">v{{ $version }}</span>
                    <div class="w-1 h-1 bg-green-400 rounded-full animate-pulse shadow-lg shadow-green-400/50"></div>
                </div>

                {{-- License Badge (Premium with Animation) --}}
                <div class="inline-flex items-center gap-1.5 px-2 py-0.5 bg-gradient-to-r from-emerald-500/30 to-green-500/30 rounded-full border border-emerald-400/50 backdrop-blur-sm animate-glow-pulse">
                    <i class="fas fa-shield-check text-[10px] text-emerald-300 drop-shadow-lg animate-pulse"></i>
                    <span class="text-[10px] font-bold text-white drop-shadow tracking-wide">LICENSED</span>
                    <i class="fas fa-check-circle text-[8px] text-green-300"></i>
                </div>

                {{-- Premium Edition Label (Subtle) --}}
                <div class="text-[9px] text-white/60 font-medium tracking-widest mt-1 drop-shadow">
                    <i class="fas fa-star text-yellow-300 text-[8px] mr-0.5 animate-pulse"></i>
                    PREMIUM EDITION
                </div>
            </div>

            {{-- License Icon Only (แสดงในโหมด Auto-hide) --}}
            <div x-show="!sidebarOpen && !hovered && autoHideMode"
                 x-transition
                 class="flex flex-col items-center gap-1.5 py-1"
                 title="Licensed - v{{ $version }}">
                {{-- Shield Icon with Badge --}}
                <div class="relative">
                    <i class="fas fa-shield-check text-emerald-300 text-2xl drop-shadow-lg animate-pulse"></i>
                    <div class="absolute -top-1 -right-1 w-2 h-2 bg-green-400 rounded-full animate-pulse shadow-lg shadow-green-400/50"></div>
                </div>
                {{-- Version Text - Larger & More Visible --}}
                <span class="text-[10px] font-bold text-white drop-shadow-lg bg-gradient-to-r from-emerald-400 to-cyan-400 bg-clip-text text-transparent">
                    v{{ $version }}
                </span>
            </div>
        </div>

        {{-- Bottom Shine Line --}}
        <div class="absolute bottom-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-cyan-400/50 to-transparent"></div>
    </div>
</aside>

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
 * Glass Neumorphic Effect - สำหรับ menu items
 */
.glass-neu {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}

/**
 * Custom Scrollbar - Thin scrollbar สำหรับ navigation
 */
.scrollbar-thin::-webkit-scrollbar {
    width: 4px;
}

.scrollbar-thin::-webkit-scrollbar-track {
    background: transparent;
}

.scrollbar-thin::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.2);
    border-radius: 4px;
}

.scrollbar-thin::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.3);
}

/**
 * 3D RotateY Animation - หมุนโลโก้แบบ 3D (ไปทางขวา ไม่ใช่แบบนาฬิกา)
 */
@keyframes rotate-y-3d {
    0% {
        transform: perspective(400px) rotateY(0deg);
    }
    100% {
        transform: perspective(400px) rotateY(360deg);
    }
}

.animate-rotate-y-3d {
    animation: rotate-y-3d 4s ease-in-out infinite;
    transform-style: preserve-3d;
}

.animate-rotate-y-3d:hover {
    animation-play-state: paused;
}

/**
 * Premium Animations สำหรับ Footer
 */

/* Float Animation - ลอยขึ้นลงนิดหน่อย */
@keyframes float {
    0%, 100% {
        transform: translateY(0px);
    }
    50% {
        transform: translateY(-3px);
    }
}

.animate-float {
    animation: float 3s ease-in-out infinite;
}

/* Slow Spin - หมุนช้าๆ สำหรับ glow ring */
@keyframes spin-slow {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

.animate-spin-slow {
    animation: spin-slow 8s linear infinite;
}

/* Glow Pulse - กระพริบแสงนุ่มๆ สำหรับ license badge */
@keyframes glow-pulse {
    0%, 100% {
        box-shadow: 0 0 5px rgba(16, 185, 129, 0.3);
    }
    50% {
        box-shadow: 0 0 15px rgba(16, 185, 129, 0.6), 0 0 25px rgba(16, 185, 129, 0.3);
    }
}

.animate-glow-pulse {
    animation: glow-pulse 2s ease-in-out infinite;
}

/* Shine Effect - แสงวิ่งผ่าน */
@keyframes shine {
    0% {
        background-position: -100% 0;
    }
    100% {
        background-position: 200% 0;
    }
}

.shine-effect {
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
    background-size: 200% 100%;
    animation: shine 3s ease-in-out infinite;
}

/* Delay utilities */
.delay-300 {
    animation-delay: 300ms;
}
</style>
