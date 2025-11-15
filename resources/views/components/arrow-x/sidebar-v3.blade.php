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
 * @tip Responsive: Mobile (drawer), Desktop (fixed sidebar)
 */
--}}

@props([
    'title' => config('app.name'),
    'logo' => null,
])

<aside
    :class="sidebarOpen ? 'w-64' : 'w-20'"
    class="glass-fusion transition-all duration-300 flex flex-col border-r border-white/30 relative z-20"
    x-cloak
>
    {{-- Logo Section --}}
    <div class="h-16 flex items-center justify-between px-4 border-b border-white/30">
        <div class="flex items-center gap-3 transition-all" x-show="sidebarOpen" x-transition>
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

        {{-- Toggle Button --}}
        <button @click="sidebarOpen = !sidebarOpen"
                class="p-2 rounded-lg hover:bg-white/20 transition-all hover:scale-110 active:scale-95"
                type="button">
            <i class="fas fa-bars text-white drop-shadow"></i>
        </button>
    </div>

    {{-- Navigation Menu --}}
    <nav class="flex-1 overflow-y-auto p-4 space-y-2 scrollbar-thin scrollbar-thumb-white/20 scrollbar-track-transparent">
        {{-- Dashboard --}}
        <a href="{{ route('admin.dashboard') }}"
           class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.dashboard') ? 'bg-gradient-to-r from-blue-500 to-purple-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
            <i class="fas fa-home w-5 text-center drop-shadow"></i>
            <span x-show="sidebarOpen" x-transition class="font-medium drop-shadow">แดชบอร์ด</span>
        </a>

        {{-- Users --}}
        <a href="{{ route('admin.users.index') }}"
           class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.users.*') ? 'bg-gradient-to-r from-blue-500 to-purple-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
            <i class="fas fa-users w-5 text-center drop-shadow"></i>
            <span x-show="sidebarOpen" x-transition class="font-medium drop-shadow">ผู้ใช้งาน</span>
        </a>

        {{-- Affiliates --}}
        <a href="{{ route('admin.affiliates.index') }}"
           class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.affiliates.*') ? 'bg-gradient-to-r from-blue-500 to-purple-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
            <i class="fas fa-network-wired w-5 text-center drop-shadow"></i>
            <span x-show="sidebarOpen" x-transition class="font-medium drop-shadow">Affiliate</span>
        </a>

        {{-- Commissions --}}
        <a href="{{ route('admin.commissions.index') }}"
           class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.commissions.*') ? 'bg-gradient-to-r from-blue-500 to-purple-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
            <i class="fas fa-coins w-5 text-center drop-shadow"></i>
            <span x-show="sidebarOpen" x-transition class="font-medium drop-shadow">คอมมิชชั่น</span>
        </a>

        {{-- Wallet --}}
        <a href="{{ route('admin.wallet.index') }}"
           class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.wallet.*') ? 'bg-gradient-to-r from-blue-500 to-purple-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
            <i class="fas fa-wallet w-5 text-center drop-shadow"></i>
            <span x-show="sidebarOpen" x-transition class="font-medium drop-shadow">กระเป๋าเงิน</span>
        </a>

        {{-- Products --}}
        <a href="{{ route('admin.ecommerce.products.index') }}"
           class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.ecommerce.products.*') ? 'bg-gradient-to-r from-blue-500 to-purple-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
            <i class="fas fa-box w-5 text-center drop-shadow"></i>
            <span x-show="sidebarOpen" x-transition class="font-medium drop-shadow">สินค้า</span>
        </a>

        {{-- Orders --}}
        <a href="{{ route('admin.ecommerce.orders.index') }}"
           class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.ecommerce.orders.*') ? 'bg-gradient-to-r from-blue-500 to-purple-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
            <i class="fas fa-shopping-cart w-5 text-center drop-shadow"></i>
            <span x-show="sidebarOpen" x-transition class="font-medium drop-shadow">คำสั่งซื้อ</span>
        </a>

        {{-- Reports --}}
        <a href="{{ route('admin.ecommerce.reports') }}"
           class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.ecommerce.reports') ? 'bg-gradient-to-r from-blue-500 to-purple-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
            <i class="fas fa-chart-bar w-5 text-center drop-shadow"></i>
            <span x-show="sidebarOpen" x-transition class="font-medium drop-shadow">รายงาน</span>
        </a>

        {{-- Divider --}}
        <div x-show="sidebarOpen" x-transition class="border-t border-white/30 my-4"></div>

        {{-- Settings --}}
        <a href="{{ route('admin.settings.index') }}"
           class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.settings.*') ? 'bg-gradient-to-r from-blue-500 to-purple-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
            <i class="fas fa-cog w-5 text-center drop-shadow"></i>
            <span x-show="sidebarOpen" x-transition class="font-medium drop-shadow">ตั้งค่า</span>
        </a>

        {{-- Help --}}
        <a href="#"
           class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform glass-neu text-white/90 hover:bg-white/20 hover:scale-105">
            <i class="fas fa-question-circle w-5 text-center drop-shadow"></i>
            <span x-show="sidebarOpen" x-transition class="font-medium drop-shadow">ช่วยเหลือ</span>
        </a>
    </nav>

    {{-- Version & License Footer --}}
    <div class="p-4 border-t border-white/30">
        <div class="flex items-center gap-3">
            {{-- 3D Flip Logo --}}
            <div class="flex-shrink-0 perspective-container">
                @php
                    $logoUrl = config('app.logo_url', null);
                    $logoText = config('app.logo_text', 'TP');
                @endphp

                @if($logoUrl)
                    <img src="{{ $logoUrl }}"
                         alt="Logo"
                         class="w-10 h-10 rounded-xl object-cover shadow-lg logo-3d-flip">
                @else
                    <div class="w-10 h-10 bg-gradient-to-br from-cyan-400 via-blue-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg logo-3d-flip">
                        <span class="text-white text-sm font-bold drop-shadow">{{ $logoText }}</span>
                    </div>
                @endif
            </div>

            {{-- Version & License Info (3D Text) --}}
            <div x-show="sidebarOpen" x-transition class="flex-1 min-w-0">
                {{-- Version 3D --}}
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-white/90 text-xs font-medium drop-shadow text-3d">Version</span>
                    <span class="px-2 py-0.5 bg-gradient-to-r from-green-500 to-emerald-600 text-white text-xs font-bold rounded-full shadow-lg version-badge-3d">
                        {{ config('app.version', '3.13.1') }}
                    </span>
                </div>

                {{-- License --}}
                <div class="flex items-center gap-2">
                    <i class="fas fa-shield-alt text-purple-400 text-xs drop-shadow"></i>
                    <span class="text-white/80 text-xs drop-shadow">
                        {{ config('app.license_text', 'Premium License') }}
                    </span>
                </div>

                {{-- Company/Owner --}}
                <p class="text-white/60 text-xs mt-1 truncate drop-shadow">
                    © {{ date('Y') }} {{ config('app.company', 'ThaiPrompt') }}
                </p>
            </div>
        </div>

        {{-- Collapsed Version --}}
        <div x-show="!sidebarOpen" x-transition class="mt-2 text-center">
            <span class="text-white/60 text-xs drop-shadow block">v{{ config('app.version', '3.13.1') }}</span>
        </div>
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
 * 3D Logo Flip Animation - หมุนไปทางขวาแบบ 3D
 */
.perspective-container {
    perspective: 1000px;
}

@keyframes flip-3d {
    0% {
        transform: rotateY(0deg);
    }
    50% {
        transform: rotateY(180deg);
    }
    100% {
        transform: rotateY(360deg);
    }
}

.logo-3d-flip {
    animation: flip-3d 4s ease-in-out infinite;
    transform-style: preserve-3d;
}

.logo-3d-flip:hover {
    animation-duration: 1s;
}

/**
 * 3D Text Effect - ข้อความแบบ 3D
 */
.text-3d {
    text-shadow:
        1px 1px 2px rgba(0, 0, 0, 0.3),
        2px 2px 4px rgba(0, 0, 0, 0.2),
        3px 3px 6px rgba(0, 0, 0, 0.1);
}

/**
 * 3D Version Badge - Badge แบบ 3D
 */
.version-badge-3d {
    transform: translateZ(10px);
    box-shadow:
        0 4px 8px rgba(0, 0, 0, 0.3),
        0 8px 16px rgba(0, 0, 0, 0.2);
}
</style>
