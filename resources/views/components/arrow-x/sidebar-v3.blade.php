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
    'title' => config('app.brand_name', 'Thai Prompt'),
    'logo' => null,
    'type' => 'admin', // Type: admin, user, seller
])

@php
    // โลโก้เว็บไซต์ถ้ามี ไม่งั้นใช้เครื่องหมาย T ทองของแบรนด์
    $themeLogo = $logo ?: asset('images/brand/thaiprompt-mark.png');
    $themeBrandName = config('app.brand_name', 'Thai Prompt');

    // โหลดเมนูจาก MenuService สำหรับทุก type (admin, user, seller)
    $menuService = app(\App\Services\MenuService::class);
    $menus = $menuService->getMenuForRole($type, auth()->user());
@endphp

{{-- Mobile Overlay (แสดงเมื่อเปิด sidebar บนมือถือ) --}}
<div x-show="$store.sidebar.isOpen && !$store.sidebar.isDesktop"
     @click="$store.sidebar.close()"
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
    @mouseenter="$store.sidebar.setHovered(true)"
    @mouseleave="$store.sidebar.setHovered(false)"
    class="glass-fusion transition-all duration-300 flex flex-col border-r border-white/30 z-40
           fixed md:relative inset-y-0 left-0
           transform md:transform-none"
    :class="{
        'translate-x-0': $store.sidebar.shouldShow,
        '-translate-x-full': !$store.sidebar.shouldShow
    }"
    :style="'width: ' + $store.sidebar.sidebarWidth + 'px'"
    x-cloak
>
    {{-- Logo Section --}}
    <div class="h-16 flex items-center justify-between px-4 border-b border-white/30">
        <div class="flex items-center gap-3 transition-all" x-show="$store.sidebar.shouldExpand" x-transition>
            @if($themeLogo)
                <img src="{{ $themeLogo }}" alt="{{ $themeBrandName }}" width="40" height="40" class="w-10 h-10 rounded-xl object-contain shadow-lg">
            @else
                <div class="w-10 h-10 bg-gradient-to-br from-cyan-400 via-blue-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg">
                    <i class="fas fa-rocket text-white text-lg"></i>
                </div>
            @endif

            <div>
                <h1 class="font-bold text-white text-sm drop-shadow-lg">{{ $themeBrandName }}</h1>
                <p class="text-xs text-white/90 drop-shadow">V3 Dashboard</p>
            </div>
        </div>

        {{-- Toggle Button (แสดงเฉพาะบน Desktop) - เปลี่ยนไอคอนตามโหมด --}}
        <button @click="$store.sidebar.toggleAutoHide()"
                class="hidden md:block p-2 rounded-lg hover:bg-white/20 transition-all hover:scale-110 active:scale-95 group"
                type="button"
                :title="$store.sidebar.autoHideMode ? 'เปิดโหมดปกติ (ล็อค sidebar)' : 'เปิดโหมด Auto-hide'">
            {{-- Icon แม่กุญแจ (โหมดปกติ - locked) --}}
            <i x-show="!$store.sidebar.autoHideMode" class="fas fa-lock text-white drop-shadow group-hover:text-blue-300 transition"></i>
            {{-- Icon Burger Menu (โหมด Auto-hide) --}}
            <i x-show="$store.sidebar.autoHideMode" class="fas fa-bars text-white drop-shadow group-hover:text-purple-300 transition"></i>
        </button>

        {{-- Close Button (แสดงเฉพาะบน Mobile) --}}
        <button @click="$store.sidebar.close()"
                class="md:hidden p-2 rounded-lg hover:bg-white/20 transition-all hover:scale-110 active:scale-95"
                type="button">
            <i class="fas fa-times text-white drop-shadow"></i>
        </button>
    </div>

    {{-- Navigation Menu --}}
    <nav class="flex-1 overflow-y-auto p-4 space-y-2 scrollbar-thin scrollbar-thumb-white/20 scrollbar-track-transparent"
         data-sidebar-nav
         x-ref="sidebarNav"
         x-data="{ mlmOpen: {{ request()->routeIs('admin.mlm.*') || request()->routeIs('admin.mlm-prospects.*') ? 'true' : 'false' }} }"
         x-init="$nextTick(() => { $store.sidebar.setNavElement($refs.sidebarNav); $store.sidebar.scrollToActiveMenu(); })">

        {{-- Pinned Menus Section --}}
        <x-menu.pinned-section :dashboardType="$type" />

            {{-- Dynamic Menu for User/Seller (from MenuService) --}}
            @foreach($menus as $menu)
                @if(isset($menu['submenu']) && count($menu['submenu']) > 0)
                    {{-- Menu with Submenu (Pinnable Group) --}}
                    @php
                        // ตรวจสอบว่า submenu item ใดตรงกับ URL ปัจจุบัน
                        // ใช้ทั้ง path match และ route match เพื่อความแม่นยำ
                        $isSubmenuActive = false;
                        $currentPath = rtrim(request()->path(), '/');
                        $firstSubmenuUrl = $menu['submenu'][0]['url'] ?? '#';
                        foreach ($menu['submenu'] as $child) {
                            // 1. Route name match (แม่นยำที่สุด)
                            if (!empty($child['route'])) {
                                if (request()->routeIs($child['route']) || request()->routeIs($child['route'] . '.*')) {
                                    $isSubmenuActive = true;
                                    break;
                                }
                            }
                            // 2. Path match (fallback)
                            $childPath = rtrim(ltrim(parse_url($child['url'] ?? '#', PHP_URL_PATH) ?? '', '/'), '/');
                            if ($childPath !== '' && ($currentPath === $childPath || str_starts_with($currentPath, $childPath . '/'))) {
                                $isSubmenuActive = true;
                                break;
                            }
                        }
                    @endphp
                    <x-menu.pinnable-menu-group
                        :menuKey="$menu['id'] ?? $menu['route'] ?? Str::slug($menu['label'])"
                        :label="$menu['label']"
                        :icon="$menu['icon'] ?? ''"
                        :dashboardType="$type"
                        :isActive="$isSubmenuActive"
                        :submenu="$menu['submenu']"
                        :defaultUrl="$firstSubmenuUrl"
                    />
                @else
                    {{-- Single Menu Item with Pin Support --}}
                    @php
                        // ตรวจสอบ active state ด้วย route name และ path match
                        $menuRoute = $menu['route'] ?? null;
                        $menuUrl = $menu['url'] ?? '#';
                        $menuPath = rtrim(ltrim(parse_url($menuUrl, PHP_URL_PATH) ?? '', '/'), '/');
                        $currentMenuPath = rtrim(request()->path(), '/');

                        // 1. Route name match (แม่นยำที่สุด)
                        $isSingleActive = false;
                        if ($menuRoute) {
                            $isSingleActive = request()->routeIs($menuRoute) || request()->routeIs($menuRoute . '.*');
                        }
                        // 2. Path exact match
                        if (!$isSingleActive && $menuPath !== '') {
                            $isSingleActive = $currentMenuPath === $menuPath;
                        }
                    @endphp
                    <x-menu.pinnable-menu-item
                        :menuKey="$menu['id'] ?? $menu['route'] ?? Str::slug($menu['label'])"
                        :label="$menu['label']"
                        :icon="$menu['icon'] ?? ''"
                        :url="$menuUrl"
                        :route="$menuRoute"
                        :dashboardType="$type"
                        :isActive="$isSingleActive"
                        :badge="$menu['badge'] ?? null"
                    />
                @endif
            @endforeach
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

                    <img src="{{ asset('images/brand/thaiprompt-footer-mark.webp') }}"
                         alt="Footer Logo"
                         class="w-10 h-10 object-contain drop-shadow-2xl relative z-10 animate-pulse">

                    {{-- Shine Effect --}}
                    <div class="absolute inset-0 bg-gradient-to-tr from-transparent via-white/30 to-transparent rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-300 shine-effect"></div>
                </div>

                {{-- Corner Sparkles (Absolute positioned) --}}
                <div class="absolute -top-1 -right-1 w-2 h-2 bg-yellow-300 rounded-full animate-ping opacity-75"></div>
                <div class="absolute -bottom-1 -left-1 w-1.5 h-1.5 bg-cyan-300 rounded-full animate-pulse delay-300"></div>
            </div>

            {{-- Version + License Info (Premium Style) - รองรับ Auto-hide Mode --}}
            <div x-show="$store.sidebar.shouldExpand" x-transition class="flex-1 min-w-0">
                {{-- App Name with Gradient Text --}}
                <div class="font-black text-base tracking-wider mb-1 bg-gradient-to-r from-white via-cyan-200 to-purple-200 bg-clip-text text-transparent drop-shadow-2xl">
                    THAI PROMPT
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
            <div x-show="!$store.sidebar.shouldExpand && $store.sidebar.autoHideMode"
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
