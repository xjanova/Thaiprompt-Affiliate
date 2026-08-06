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
    'type' => 'admin', // Type: admin, user, seller
])

@php
    // ดึงโลโก้จาก ThemeSetting (โลโก้ธีม - แยกจากโลโก้เว็บไซต์)
    $themeSetting = \App\Models\ThemeSetting::active();
    $themeLogo = $themeSetting && $themeSetting->logo_path
        ? asset('storage/' . $themeSetting->logo_path)
        : $logo;
    $themeBrandName = $themeSetting->brand_name ?? $title;

    // โหลดเมนูจาก MenuService สำหรับทุก type (admin, user, seller)
    $useMenuService = true;
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
                <img src="{{ $themeLogo }}" alt="{{ $themeBrandName }}" class="w-10 h-10 rounded-xl object-cover shadow-lg">
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

        @if($useMenuService)
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
        @else
            {{--
            ╔══════════════════════════════════════════════════════════════════════════════╗
            ║  ⛔ DEAD CODE — DO NOT EDIT TO ADD MENUS ⛔                                  ║
            ║                                                                                ║
            ║  This @else branch is NEVER rendered because $useMenuService = true (line 40).║
            ║  Editing menu items here will NOT make them appear in the sidebar.            ║
            ║                                                                                ║
            ║  ✅ TO ADD/EDIT ADMIN MENU ITEMS — edit:  config/menus.php                    ║
            ║                                                                                ║
            ║  ✅ The actual rendered menu comes from:                                       ║
            ║       MenuService::getMenuForRole() → config('menus.admin') → @foreach above  ║
            ║                                                                                ║
            ║  This static block is kept ONLY as a fallback safety net if MenuService fails.║
            ║  Do not delete, but do not add new items here either.                          ║
            ╚══════════════════════════════════════════════════════════════════════════════╝
            --}}
            {{-- Static Admin Menu (DEAD — see warning above; edit config/menus.php instead) --}}
            {{-- Dashboard --}}
            <x-menu.pinnable-menu-item
                menuKey="admin.dashboard"
                label="แดชบอร์ด"
                icon="fas fa-home"
                :url="route('admin.dashboard')"
                route="admin.dashboard"
                dashboardType="admin"
                :isActive="request()->routeIs('admin.dashboard')"
            />

        {{-- Users & Roles (Collapsible Menu) 👥 --}}
        <x-menu.pinnable-menu-group
            menuKey="admin.users"
            label="ผู้ใช้งาน"
            icon="fas fa-users"
            dashboardType="admin"
            :isActive="request()->routeIs('admin.users.*') || request()->routeIs('admin.roles.*')"
            :submenu="[
                ['label' => 'รายชื่อผู้ใช้', 'url' => route('admin.users.index'), 'icon' => 'fas fa-list'],
                ['label' => 'บทบาทและสิทธิ์', 'url' => route('admin.roles.index'), 'icon' => 'fas fa-user-shield'],
            ]"
            :defaultUrl="route('admin.users.index')"
        />

        {{-- ========================================
             📱 Thaiapp-MANAGER · ตัวคุม Thaiprompt App (Flutter)
             ======================================== --}}
        <x-menu.pinnable-menu-group
            menuKey="admin.thaiapp"
            label="Thaiapp · แอป"
            icon="fas fa-mobile-screen"
            dashboardType="admin"
            :isActive="request()->routeIs('admin.thaiapp.*')"
            :submenu="[
                ['label' => 'ภาพรวม', 'url' => route('admin.thaiapp.hub'), 'icon' => 'fas fa-gauge-high'],
                ['label' => 'น้องหญิง · ตัวตน/TTS', 'url' => route('admin.thaiapp.nong-ying'), 'icon' => 'fas fa-comment-dots'],
                ['label' => 'AI Pool · API keys', 'url' => route('admin.thaiapp.ai-pool'), 'icon' => 'fas fa-key'],
                ['label' => 'AI Models · Gemma sync', 'url' => route('admin.thaiapp.ai-models'), 'icon' => 'fas fa-brain'],
                ['label' => 'แบนเนอร์ในแอป', 'url' => route('admin.thaiapp.banners'), 'icon' => 'fas fa-image'],
                ['label' => 'สไลด์ในแอป', 'url' => route('admin.thaiapp.sliders'), 'icon' => 'fas fa-images'],
                ['label' => 'เมนูในแอป', 'url' => route('admin.thaiapp.menus'), 'icon' => 'fas fa-bars'],
                ['label' => 'ค่าคอนฟิก (key-value)', 'url' => route('admin.thaiapp.config'), 'icon' => 'fas fa-sliders'],
                ['label' => 'ประวัติรุ่น (releases)', 'url' => route('admin.thaiapp.releases'), 'icon' => 'fas fa-clock-rotate-left'],
            ]"
            :defaultUrl="route('admin.thaiapp.hub')"
        />

        {{-- ========================================
             🏪 Storefront Management (Collapsible Menu)
             จัดการหน้าร้านค้าหลัก, แบนเนอร์, ธีม
             ======================================== --}}
        @php
            $isStorefrontActive = request()->routeIs('admin.storefront.*') || request()->routeIs('admin.featured-stores.*') || request()->routeIs('admin.ecommerce.*') || request()->routeIs('admin.official-shop.*');
        @endphp
        <div class="space-y-1"
             data-menu-active="{{ $isStorefrontActive ? 'true' : 'false' }}"
             data-menu-type="parent"
             data-menu-key="storefront"
             x-data="{ storefrontMenuOpen: {{ $isStorefrontActive ? 'true' : 'false' }} }">
            {{-- Storefront Header Button --}}
            <button @click="storefrontMenuOpen = !storefrontMenuOpen"
                    type="button"
                    class="w-full flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ $isStorefrontActive ? 'bg-gradient-to-r from-orange-500 to-pink-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
                <i class="fas fa-store w-5 text-center drop-shadow"></i>
                <span x-show="$store.sidebar.shouldExpand" x-transition class="flex-1 text-left font-medium drop-shadow whitespace-nowrap">จัดการหน้าร้าน</span>
                <i x-show="$store.sidebar.shouldExpand && storefrontMenuOpen" x-transition class="fas fa-chevron-down text-xs drop-shadow"></i>
                <i x-show="$store.sidebar.shouldExpand && !storefrontMenuOpen" x-transition class="fas fa-chevron-right text-xs drop-shadow"></i>
            </button>

            {{-- Storefront Submenu --}}
            <div x-show="storefrontMenuOpen" x-collapse x-cloak class="ml-8 space-y-1">
                {{-- Storefront Settings (Theme & Layout) --}}
                <a href="{{ route('admin.storefront.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.storefront.index') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.storefront.index') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-palette w-4 text-center"></i>
                    <span>ตั้งค่าธีม & เลย์เอาต์</span>
                </a>

                {{-- Banners Management --}}
                <a href="{{ route('admin.storefront.banners.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.storefront.banners.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.storefront.banners.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-images w-4 text-center"></i>
                    <span>แบนเนอร์/สไลด์</span>
                </a>

                {{-- Products --}}
                <a href="{{ route('admin.ecommerce.products.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.ecommerce.products.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.ecommerce.products.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-box w-4 text-center"></i>
                    <span>สินค้า</span>
                </a>

                {{-- Orders --}}
                <a href="{{ route('admin.ecommerce.orders.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.ecommerce.orders.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.ecommerce.orders.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-shopping-cart w-4 text-center"></i>
                    <span>คำสั่งซื้อ</span>
                </a>

                {{-- Categories --}}
                <a href="{{ route('admin.ecommerce.categories.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.ecommerce.categories.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.ecommerce.categories.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-tags w-4 text-center"></i>
                    <span>หมวดหมู่สินค้า</span>
                </a>

                {{-- Featured Stores --}}
                <a href="{{ route('admin.featured-stores.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.featured-stores.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.featured-stores.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-star w-4 text-center"></i>
                    <span>ร้านค้าแนะนำ</span>
                </a>

                {{-- Vendor Stores List --}}
                <a href="{{ route('admin.storefront.vendor-stores.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.storefront.vendor-stores.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.storefront.vendor-stores.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-store-alt w-4 text-center"></i>
                    <span>ร้านค้าทั้งหมด</span>
                </a>

                {{-- Reports --}}
                <a href="{{ route('admin.ecommerce.reports') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.ecommerce.reports') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.ecommerce.reports') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-chart-bar w-4 text-center"></i>
                    <span>รายงาน</span>
                </a>

                {{-- Official Shop (Premium) --}}
                <a href="{{ route('admin.official-shop.dashboard') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.official-shop.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.official-shop.*') ? 'bg-gradient-to-r from-amber-400 to-purple-500 text-white font-bold shadow-lg' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-crown w-4 text-center text-amber-300"></i>
                    <span>Official Shop</span>
                    <span class="ml-auto px-1.5 py-0.5 text-xs bg-amber-400/30 text-amber-200 rounded font-bold">Premium</span>
                </a>
            </div>
        </div>

        {{-- MLM System (Collapsible Menu) - เฉพาะแอดมิน --}}
        @if($type === 'admin')
        <x-menu.pinnable-menu-group
            menuKey="admin.mlm"
            label="MLM System"
            icon="fas fa-sitemap"
            dashboardType="admin"
            activeGradient="from-purple-500 to-pink-600"
            :isActive="request()->routeIs('admin.mlm.*') || request()->routeIs('admin.mlm-prospects.*') || request()->routeIs('admin.ranks.*') || request()->routeIs('admin.team-transfer.*')"
            :submenu="[
                {{-- === จัดการ === --}}
                ['label' => 'Dashboard', 'url' => route('admin.mlm.reports.dashboard'), 'icon' => 'fas fa-chart-pie'],
                ['label' => 'สมาชิก', 'url' => route('admin.mlm.members.index'), 'icon' => 'fas fa-users-cog'],
                ['label' => 'ระดับสมาชิก', 'url' => route('admin.ranks.index'), 'icon' => 'fas fa-medal'],
                ['label' => 'Prospects', 'url' => route('admin.mlm-prospects.index'), 'icon' => 'fas fa-user-plus'],
                {{-- === การเงิน === --}}
                ['label' => 'คอมมิชชั่น', 'url' => route('admin.mlm.commissions.index'), 'icon' => 'fas fa-money-bill-wave'],
                ['label' => 'Product PV', 'url' => route('admin.mlm.product-pv.index'), 'icon' => 'fas fa-tags'],
                ['label' => 'รายงาน', 'url' => route('admin.mlm.reports.index'), 'icon' => 'fas fa-chart-line'],
                {{-- === เครือข่าย === --}}
                ['label' => 'Genealogy', 'url' => route('admin.mlm.genealogy.index'), 'icon' => 'fas fa-project-diagram'],
                ['label' => 'แผน MLM', 'url' => route('admin.mlm.plans.index'), 'icon' => 'fas fa-layer-group'],
                ['label' => 'การย้ายทีม', 'url' => route('admin.team-transfer.index'), 'icon' => 'fas fa-exchange-alt'],
                ['label' => 'ย้ายทีมโดยตรง', 'url' => route('admin.team-transfer.direct'), 'icon' => 'fas fa-random'],
                {{-- === เครื่องมือ === --}}
                ['label' => 'เครื่องคิดเลข', 'url' => route('admin.mlm.calculator'), 'icon' => 'fas fa-calculator'],
                ['label' => 'ตัวอย่าง Placement', 'url' => route('admin.mlm.placement-examples'), 'icon' => 'fas fa-lightbulb'],
                {{-- === ตั้งค่า === --}}
                ['label' => 'ตั้งค่า', 'url' => route('admin.mlm.settings.index'), 'icon' => 'fas fa-cogs'],
            ]"
            :defaultUrl="route('admin.mlm.reports.dashboard')"
        />
        @endif

        {{-- Marketplace Affiliate (Collapsible Menu) 🛒 --}}
        @if($type === 'admin')
        <div class="space-y-1"
             data-menu-active="{{ request()->routeIs('admin.marketplace.*') ? 'true' : 'false' }}"
             data-menu-type="parent"
             data-menu-key="marketplace"
             x-data="{ marketplaceOpen: {{ request()->routeIs('admin.marketplace.*') ? 'true' : 'false' }} }">
            {{-- Marketplace Header Button --}}
            <button @click="marketplaceOpen = !marketplaceOpen"
                    type="button"
                    class="w-full flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.marketplace.*') ? 'bg-gradient-to-r from-orange-500 to-red-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
                <i class="fas fa-shopping-basket w-5 text-center drop-shadow"></i>
                <span x-show="$store.sidebar.shouldExpand" x-transition class="flex-1 text-left font-medium drop-shadow whitespace-nowrap">Marketplace Affiliate</span>
                <i x-show="$store.sidebar.shouldExpand && marketplaceOpen" x-transition class="fas fa-chevron-down text-xs drop-shadow"></i>
                <i x-show="$store.sidebar.shouldExpand && !marketplaceOpen" x-transition class="fas fa-chevron-right text-xs drop-shadow"></i>
            </button>

            {{-- Marketplace Submenu --}}
            <div x-show="marketplaceOpen" x-collapse x-cloak class="ml-8 space-y-1">
                {{-- บัญชี Marketplace 🔑 --}}
                <a href="{{ route('admin.marketplace.accounts.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.marketplace.accounts.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.marketplace.accounts.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-key w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">บัญชี API</span>
                </a>

                {{-- สินค้า Marketplace 📦 --}}
                <a href="{{ route('admin.marketplace.products.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.marketplace.products.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.marketplace.products.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-boxes w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">สินค้า</span>
                </a>

                {{-- ออเดอร์ 🛒 --}}
                <a href="{{ route('admin.marketplace.orders.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.marketplace.orders.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.marketplace.orders.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-shopping-bag w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">ออเดอร์</span>
                </a>

                {{-- คอมมิชชั่น 💰 --}}
                <a href="{{ route('admin.marketplace.commissions.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.marketplace.commissions.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.marketplace.commissions.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-coins w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">คอมมิชชั่น</span>
                </a>
            </div>
        </div>
        @endif

        {{-- LINE Membership Signup (Collapsible Menu) 🤖 --}}
        <div class="space-y-1"
             data-menu-active="{{ request()->routeIs('admin.line-membership-signup.*') ? 'true' : 'false' }}"
             data-menu-type="parent"
             data-menu-key="line-membership-signup"
             x-data="{ lineSignupOpen: {{ request()->routeIs('admin.line-membership-signup.*') ? 'true' : 'false' }} }">
            {{-- LINE Signup Header Button --}}
            <button @click="lineSignupOpen = !lineSignupOpen"
                    type="button"
                    class="w-full flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.line-membership-signup.*') ? 'bg-gradient-to-r from-green-500 to-teal-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
                <i class="fab fa-line w-5 text-center drop-shadow"></i>
                <span x-show="$store.sidebar.shouldExpand" x-transition class="flex-1 text-left font-medium drop-shadow whitespace-nowrap">LINE Membership</span>
                <i x-show="$store.sidebar.shouldExpand && lineSignupOpen" x-transition class="fas fa-chevron-down text-xs drop-shadow"></i>
                <i x-show="$store.sidebar.shouldExpand && !lineSignupOpen" x-transition class="fas fa-chevron-right text-xs drop-shadow"></i>
            </button>

            {{-- LINE Signup Submenu --}}
            <div x-show="lineSignupOpen" x-collapse x-cloak class="ml-8 space-y-1">
                {{-- Dashboard 📊 --}}
                <a href="{{ route('admin.line-membership-signup.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.line-membership-signup.index') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.line-membership-signup.index') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-chart-pie w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Dashboard</span>
                </a>

                {{-- Sessions 💬 --}}
                <a href="{{ route('admin.line-membership-signup.sessions') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.line-membership-signup.sessions*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.line-membership-signup.sessions*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-comments w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Sessions</span>
                </a>

                {{-- Templates 📝 --}}
                <a href="{{ route('admin.line-membership-signup.templates') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.line-membership-signup.templates*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.line-membership-signup.templates*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-file-alt w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Templates</span>
                </a>

                {{-- Rewards 🎁 --}}
                <a href="{{ route('admin.line-membership-signup.rewards.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.line-membership-signup.rewards.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.line-membership-signup.rewards.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-gift w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Rewards</span>
                </a>

                {{-- Invitations 📧 --}}
                <a href="{{ route('admin.line-membership-signup.invitations') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.line-membership-signup.invitations') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.line-membership-signup.invitations') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-envelope-open-text w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Invitations</span>
                </a>

                {{-- Settings ⚙️ --}}
                @php $lineSettingsActive = request()->routeIs('admin.line-oa.index') && request()->query('tab') === 'membership'; @endphp
                <a href="{{ route('admin.line-oa.index', ['tab' => 'membership']) }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ $lineSettingsActive ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ $lineSettingsActive ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-cog w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Settings</span>
                </a>
            </div>
        </div>

        {{-- Fortune Telling (Collapsible Menu) 🔮 --}}
        <div class="space-y-1"
             data-menu-active="{{ request()->routeIs('admin.fortune.*') ? 'true' : 'false' }}"
             data-menu-type="parent"
             data-menu-key="fortune-telling"
             x-data="{ fortuneOpen: {{ request()->routeIs('admin.fortune.*') ? 'true' : 'false' }} }">
            {{-- Fortune Telling Header Button --}}
            <button @click="fortuneOpen = !fortuneOpen"
                    type="button"
                    class="w-full flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.fortune.*') ? 'bg-gradient-to-r from-purple-500 to-indigo-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
                <i class="fas fa-wand-magic-sparkles w-5 text-center drop-shadow"></i>
                <span x-show="$store.sidebar.shouldExpand" x-transition class="flex-1 text-left font-medium drop-shadow whitespace-nowrap">ดูดวงออนไลน์</span>
                <i x-show="$store.sidebar.shouldExpand && fortuneOpen" x-transition class="fas fa-chevron-down text-xs drop-shadow"></i>
                <i x-show="$store.sidebar.shouldExpand && !fortuneOpen" x-transition class="fas fa-chevron-right text-xs drop-shadow"></i>
            </button>

            {{-- Fortune Telling Submenu --}}
            <div x-show="fortuneOpen" x-collapse x-cloak class="ml-8 space-y-1">
                {{-- Settings ⚙️ --}}
                <a href="{{ route('admin.fortune.settings.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.fortune.settings.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.fortune.settings.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-cog w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">ตั้งค่าระบบ</span>
                </a>

                {{-- Dashboard 📊 --}}
                <a href="{{ route('admin.fortune.dashboard') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.fortune.dashboard') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.fortune.dashboard') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-chart-pie w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Dashboard</span>
                </a>

                {{-- Astrology ✨ --}}
                <a href="{{ route('admin.fortune.astrology.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.fortune.astrology.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.fortune.astrology.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-star w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">โหราศาสตร์</span>
                </a>

                {{-- Categories 📂 --}}
                <a href="{{ route('admin.fortune.categories.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.fortune.categories.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.fortune.categories.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-folder-open w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">หมวดหมู่การทำนาย</span>
                </a>

                {{-- Readings 📊 --}}
                <a href="{{ route('admin.fortune.readings.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.fortune.readings.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.fortune.readings.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-history w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">ประวัติการทำนาย</span>
                </a>

                {{-- Response Templates 📝 --}}
                <a href="{{ route('admin.fortune.response-templates.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.fortune.response-templates.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.fortune.response-templates.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-file-lines w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">เทมเพลตตอบกลับ</span>
                </a>

                {{-- Billing 💰 --}}
                <a href="{{ route('admin.fortune.billing.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.fortune.billing.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.fortune.billing.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-file-invoice-dollar w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">จัดการบิล</span>
                </a>

                {{-- ภาพรวมคอมมิชชั่น 💰 --}}
                <a href="{{ route('admin.fortune.commissions.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.fortune.commissions.index') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.fortune.commissions.index') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-hand-holding-usd w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">ภาพรวมคอมมิชชั่น</span>
                </a>

                {{-- จัดการคอมมิชชั่น ⚙️ --}}
                <a href="{{ route('admin.fortune.commissions.manage') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.fortune.commissions.manage') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.fortune.commissions.manage') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-money-check-alt w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">จัดการคอมมิชชั่น</span>
                </a>

                {{-- ผังสายงานดูดวง 🔮 --}}
                <a href="{{ route('admin.fortune.referral-tree.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.fortune.referral-tree.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.fortune.referral-tree.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-sitemap w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">ผังสายงานดูดวง</span>
                </a>

                {{-- Marketing 📣 --}}
                <a href="{{ route('admin.fortune.marketing.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.fortune.marketing.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.fortune.marketing.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-bullhorn w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">การตลาดดูดวง</span>
                </a>

                {{-- Saved Questions 📝 --}}
                <a href="{{ route('admin.fortune.saved-questions.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.fortune.saved-questions.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.fortune.saved-questions.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-question-circle w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">คำถามรอตอบ</span>
                </a>

                {{-- ===== ดูดวงสาธารณะ (Horoscope Public) ===== --}}
                <div x-show="$store.sidebar.shouldExpand" x-transition class="mt-2 pt-2 border-t border-white/20">
                    <span class="px-3 py-1 text-xs font-semibold text-yellow-300/80 uppercase tracking-wider drop-shadow">🌙 ดูดวงสาธารณะ</span>
                </div>

                {{-- Horoscope Settings ⚙️ --}}
                <a href="{{ route('admin.fortune.horoscope-public.settings') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.fortune.horoscope-public.settings*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.fortune.horoscope-public.settings*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-sliders w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">ตั้งค่าดูดวงสาธารณะ</span>
                </a>

                {{-- Zodiac Management ♈ --}}
                <a href="{{ route('admin.fortune.horoscope-public.zodiac.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.fortune.horoscope-public.zodiac.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.fortune.horoscope-public.zodiac.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-sun w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">12 ราศี + ดวงรายวัน</span>
                </a>

                {{-- Dream Dictionary 🌙 --}}
                <a href="{{ route('admin.fortune.horoscope-public.dream.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.fortune.horoscope-public.dream.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.fortune.horoscope-public.dream.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-moon w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">พจนานุกรมฝัน</span>
                </a>

                {{-- Horoscope Analytics 📊 --}}
                <a href="{{ route('admin.fortune.horoscope-public.analytics') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.fortune.horoscope-public.analytics*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.fortune.horoscope-public.analytics*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-chart-line w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">สถิติดูดวงสาธารณะ</span>
                </a>
            </div>
        </div>

        {{-- Wallet & Finance (Collapsible Menu) 💰 --}}
        @php
            $walletActive = request()->routeIs('admin.wallet.*') || request()->routeIs('admin.withdrawals.*') || request()->routeIs('admin.wallet-settings.*') || request()->routeIs('admin.payment-gateways.*') || request()->routeIs('admin.cashback.*') || request()->routeIs('admin.investments.*') || request()->routeIs('admin.nfc-*');
        @endphp
        <div class="space-y-1"
             data-menu-active="{{ $walletActive ? 'true' : 'false' }}"
             data-menu-type="parent"
             data-menu-key="wallet"
             x-data="{ walletOpen: {{ $walletActive ? 'true' : 'false' }} }">
            {{-- Wallet Header Button --}}
            <button @click="walletOpen = !walletOpen"
                    type="button"
                    class="w-full flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.wallet.*') || request()->routeIs('admin.withdrawals.*') || request()->routeIs('admin.wallet-settings.*') || request()->routeIs('admin.payment-gateways.*') || request()->routeIs('admin.cashback.*') || request()->routeIs('admin.investments.*') || request()->routeIs('admin.nfc-*') ? 'bg-gradient-to-r from-blue-500 to-purple-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
                <i class="fas fa-wallet w-5 text-center drop-shadow"></i>
                <span x-show="$store.sidebar.shouldExpand" x-transition class="flex-1 text-left font-medium drop-shadow whitespace-nowrap">กระเป๋าเงิน</span>
                <i x-show="$store.sidebar.shouldExpand && walletOpen" x-transition class="fas fa-chevron-down text-xs drop-shadow"></i>
                <i x-show="$store.sidebar.shouldExpand && !walletOpen" x-transition class="fas fa-chevron-right text-xs drop-shadow"></i>
            </button>

            {{-- Wallet Submenu --}}
            <div x-show="walletOpen" x-collapse x-cloak class="ml-8 space-y-1">
                {{-- Wallet Dashboard --}}
                <a href="{{ route('admin.wallet.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.wallet.index') || request()->routeIs('admin.wallet.transactions') || request()->routeIs('admin.wallet.logs') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.wallet.index') || request()->routeIs('admin.wallet.transactions') || request()->routeIs('admin.wallet.logs') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-tachometer-alt w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Dashboard</span>
                </a>

                {{-- Withdrawals ⭐ สำคัญมาก! --}}
                <a href="{{ route('admin.withdrawals.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.withdrawals.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.withdrawals.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-money-bill-transfer w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">การถอนเงิน</span>
                    @if(isset($pendingWithdrawals) && $pendingWithdrawals > 0)
                        <span class="ml-auto bg-red-500 text-white text-xs px-2 py-0.5 rounded-full">{{ $pendingWithdrawals }}</span>
                    @endif
                </a>

                {{-- Wallet Settings --}}
                <a href="{{ route('admin.wallet-settings.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.wallet-settings.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.wallet-settings.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-cog w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">ตั้งค่า Wallet</span>
                </a>

                {{-- Payment Gateways --}}
                <a href="{{ route('admin.payment-gateways.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.payment-gateways.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.payment-gateways.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-credit-card w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Payment Gateways</span>
                </a>

                {{-- Cashback --}}
                <a href="{{ route('admin.cashback.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.cashback.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.cashback.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-gift w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Cashback</span>
                </a>

                {{-- Investments --}}
                <a href="{{ route('admin.investments.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.investments.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.investments.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-chart-line w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">ลงทุน (Staking)</span>
                </a>

                {{-- NFC System (Nested Collapsible) --}}
                <div class="space-y-1" x-data="{ nfcOpen: {{ request()->routeIs('admin.nfc-*') ? 'true' : 'false' }} }">
                    <button @click="nfcOpen = !nfcOpen"
                            type="button"
                            class="w-full flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.nfc-*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                        <i class="fas fa-nfc-symbol w-4 text-center drop-shadow"></i>
                        <span x-show="$store.sidebar.shouldExpand" x-transition class="flex-1 text-left drop-shadow whitespace-nowrap">NFC System</span>
                        <i x-show="$store.sidebar.shouldExpand && nfcOpen" x-transition class="fas fa-chevron-down text-xs"></i>
                        <i x-show="$store.sidebar.shouldExpand && !nfcOpen" x-transition class="fas fa-chevron-right text-xs"></i>
                    </button>

                    {{-- NFC Submenu --}}
                    <div x-show="nfcOpen" x-collapse x-cloak class="ml-8 space-y-1">
                        <a href="{{ route('admin.nfc-cards.index') }}"
                           @click="$store.sidebar.closeOnMenuClick()"
                           data-menu-active="{{ request()->routeIs('admin.nfc-cards.index') || request()->routeIs('admin.nfc-cards.show') || request()->routeIs('admin.nfc-cards.edit') ? 'true' : 'false' }}"
                           data-menu-type="submenu"
                           class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-xs {{ request()->routeIs('admin.nfc-cards.index') || request()->routeIs('admin.nfc-cards.show') || request()->routeIs('admin.nfc-cards.edit') ? 'bg-white/20 text-white font-bold' : 'text-white/70 hover:bg-white/10 hover:text-white' }}">
                            <i class="fas fa-id-card w-4 text-center"></i>
                            <span x-show="$store.sidebar.shouldExpand" x-transition class="whitespace-nowrap">บัตร NFC</span>
                        </a>

                        <a href="{{ route('admin.nfc-cards.writer') }}"
                           @click="$store.sidebar.closeOnMenuClick()"
                           data-menu-active="{{ request()->routeIs('admin.nfc-cards.writer') ? 'true' : 'false' }}"
                           data-menu-type="submenu"
                           class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-xs {{ request()->routeIs('admin.nfc-cards.writer') ? 'bg-white/20 text-white font-bold' : 'text-white/70 hover:bg-white/10 hover:text-white' }}">
                            <i class="fas fa-edit w-4 text-center"></i>
                            <span x-show="$store.sidebar.shouldExpand" x-transition class="whitespace-nowrap">NFC Writer</span>
                            <span class="px-1.5 py-0.5 text-[10px] bg-green-500 text-white rounded-full">NEW</span>
                        </a>

                        <a href="{{ route('admin.nfc-readers.index') }}"
                           @click="$store.sidebar.closeOnMenuClick()"
                           data-menu-active="{{ request()->routeIs('admin.nfc-readers.*') ? 'true' : 'false' }}"
                           data-menu-type="submenu"
                           class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-xs {{ request()->routeIs('admin.nfc-readers.*') ? 'bg-white/20 text-white font-bold' : 'text-white/70 hover:bg-white/10 hover:text-white' }}">
                            <i class="fas fa-barcode w-4 text-center"></i>
                            <span x-show="$store.sidebar.shouldExpand" x-transition class="whitespace-nowrap">เครื่องอ่าน</span>
                        </a>

                        <a href="{{ route('admin.nfc-transactions.index') }}"
                           @click="$store.sidebar.closeOnMenuClick()"
                           data-menu-active="{{ request()->routeIs('admin.nfc-transactions.*') ? 'true' : 'false' }}"
                           data-menu-type="submenu"
                           class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-xs {{ request()->routeIs('admin.nfc-transactions.*') ? 'bg-white/20 text-white font-bold' : 'text-white/70 hover:bg-white/10 hover:text-white' }}">
                            <i class="fas fa-exchange-alt w-4 text-center"></i>
                            <span x-show="$store.sidebar.shouldExpand" x-transition class="whitespace-nowrap">ธุรกรรม</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Platform Finance (Collapsible Menu) 💼 --}}
        <div class="space-y-1"
             data-menu-active="{{ request()->routeIs('admin.platform-revenue.*') ? 'true' : 'false' }}"
             data-menu-type="parent"
             data-menu-key="platform-finance"
             x-data="{ platformFinanceOpen: {{ request()->routeIs('admin.platform-revenue.*') ? 'true' : 'false' }} }">
            {{-- Platform Finance Header Button --}}
            <button @click="platformFinanceOpen = !platformFinanceOpen"
                    type="button"
                    class="w-full flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.platform-revenue.*') ? 'bg-gradient-to-r from-emerald-500 to-teal-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
                <i class="fas fa-building-columns w-5 text-center drop-shadow"></i>
                <span x-show="$store.sidebar.shouldExpand" x-transition class="flex-1 text-left font-medium drop-shadow whitespace-nowrap">การเงินแพลตฟอร์ม</span>
                <span x-show="$store.sidebar.shouldExpand" x-transition class="px-1.5 py-0.5 text-[10px] bg-emerald-500 text-white rounded-full font-semibold">ADMIN</span>
                <i x-show="$store.sidebar.shouldExpand && platformFinanceOpen" x-transition class="fas fa-chevron-down text-xs drop-shadow"></i>
                <i x-show="$store.sidebar.shouldExpand && !platformFinanceOpen" x-transition class="fas fa-chevron-right text-xs drop-shadow"></i>
            </button>

            {{-- Platform Finance Submenu --}}
            <div x-show="platformFinanceOpen" x-collapse x-cloak class="ml-8 space-y-1">
                {{-- รายได้แพลตฟอร์ม Dashboard --}}
                <a href="{{ route('admin.platform-revenue.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.platform-revenue.index') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.platform-revenue.index') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-chart-pie w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">📊 รายได้แพลตฟอร์ม</span>
                </a>

                {{-- กระเป๋าเงินแพลตฟอร์ม (Platform Wallets) --}}
                <a href="{{ route('admin.platform-revenue.wallets.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.platform-revenue.wallets.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.platform-revenue.wallets.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-vault w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">💰 กระเป๋าเงินแพลตฟอร์ม</span>
                </a>

                {{-- ธุรกรรมแพลตฟอร์ม --}}
                <a href="{{ route('admin.platform-revenue.transactions') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.platform-revenue.transactions') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.platform-revenue.transactions') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-list-check w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">📝 ธุรกรรมแพลตฟอร์ม</span>
                </a>

                {{-- รายงานการเงิน --}}
                <a href="{{ route('admin.platform-revenue.reports') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.platform-revenue.reports') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.platform-revenue.reports') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-file-invoice-dollar w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">📈 รายงานการเงิน</span>
                </a>
            </div>
        </div>

        {{-- TPIX Blockchain --}}
        <x-menu.pinnable-menu-item
            menuKey="admin.tpix.dashboard"
            label="TPIX Blockchain"
            icon="fas fa-cube"
            :url="route('admin.tpix.dashboard')"
            route="admin.tpix.dashboard"
            dashboardType="admin"
            :isActive="request()->routeIs('admin.tpix.dashboard')"
        />

        {{-- TPIX Deployment Wizard --}}
        <x-menu.pinnable-menu-item
            menuKey="admin.tpix.deployment"
            label="TPIX Deployment Wizard"
            icon="fas fa-rocket"
            :url="route('admin.tpix.deployment.index')"
            route="admin.tpix.deployment.index"
            dashboardType="admin"
            :isActive="request()->routeIs('admin.tpix.deployment.*') && !request()->routeIs('admin.tpix.deployment.tutorial')"
        />

        {{-- TPIX Deployment Tutorial --}}
        <x-menu.pinnable-menu-item
            menuKey="admin.tpix.deployment.tutorial"
            label="📖 คู่มือ Deploy TPIX"
            icon="fas fa-book-open"
            :url="route('admin.tpix.deployment.tutorial')"
            route="admin.tpix.deployment.tutorial"
            dashboardType="admin"
            :isActive="request()->routeIs('admin.tpix.deployment.tutorial')"
        />

        {{-- Token Management --}}
        <x-menu.pinnable-menu-item
            menuKey="admin.tokens"
            label="Token Management"
            icon="fas fa-coins"
            :url="route('admin.tokens.index')"
            route="admin.tokens.index"
            dashboardType="admin"
            :isActive="request()->routeIs('admin.tokens.*')"
        />

        {{-- LINE System (Collapsible Menu) 🆕 --}}
        <div class="space-y-1"
             data-menu-active="{{ request()->routeIs('admin.line-*') ? 'true' : 'false' }}"
             data-menu-type="parent"
             data-menu-key="line-system"
             x-data="{ lineOpen: {{ request()->routeIs('admin.line-*') ? 'true' : 'false' }} }">
            {{-- LINE Header Button --}}
            <button @click="lineOpen = !lineOpen"
                    type="button"
                    class="w-full flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.line-*') ? 'bg-gradient-to-r from-green-500 to-emerald-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
                <i class="fab fa-line w-5 text-center drop-shadow"></i>
                <span x-show="$store.sidebar.shouldExpand" x-transition class="flex-1 text-left font-medium drop-shadow whitespace-nowrap">LINE System</span>
                <i x-show="$store.sidebar.shouldExpand && lineOpen" x-transition class="fas fa-chevron-down text-xs drop-shadow"></i>
                <i x-show="$store.sidebar.shouldExpand && !lineOpen" x-transition class="fas fa-chevron-right text-xs drop-shadow"></i>
            </button>

            {{-- LINE Submenu --}}
            <div x-show="lineOpen" x-collapse x-cloak class="ml-8 space-y-1">
                {{-- LINE OA Management --}}
                <a href="{{ route('admin.line-oa.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.line-oa.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.line-oa.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-bullhorn w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">LINE OA</span>
                </a>

                {{-- Facebook Login (OAuth) — อยู่ใต้ LINE เพราะเป็น login provider เหมือนกัน --}}
                <a href="{{ route('admin.auth.facebook-oauth.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.auth.facebook-oauth.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.auth.facebook-oauth.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fab fa-facebook w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Facebook Login</span>
                </a>

                {{-- LINE Bot AI --}}
                <a href="{{ route('admin.line-bot.ai.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.line-bot.ai.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.line-bot.ai.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-robot w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Bot AI</span>
                </a>

                {{-- Rich Menu --}}
                <a href="{{ route('admin.line-bot.rich-menu.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.line-bot.rich-menu.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.line-bot.rich-menu.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-th-large w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Rich Menu</span>
                </a>

                {{-- LINE Membership Signup 🆕 --}}
                <a href="{{ route('admin.line-membership-signup.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.line-membership-signup.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.line-membership-signup.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-user-plus w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">สมัครสมาชิก</span>
                </a>

                {{-- LINE Recruitment (AI Bot สำหรับรับสมัคร) 🆕 --}}
                <a href="{{ route('admin.line-recruitment.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.line-recruitment.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.line-recruitment.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-user-tie w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">รับสมัคร (AI)</span>
                </a>

                {{-- Conversations --}}
                <a href="{{ route('admin.line-bot.ai.conversations') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.line-bot.ai.conversations*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.line-bot.ai.conversations*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-comments w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">การสนทนา</span>
                </a>

                {{-- Broadcast Messages --}}
                <a href="{{ route('admin.line-bot.broadcast.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.line-bot.broadcast.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.line-bot.broadcast.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-bullseye w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Broadcast</span>
                </a>

                {{-- Analytics --}}
                <a href="{{ route('admin.line-bot.ai.analytics') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.line-bot.ai.analytics') || request()->routeIs('admin.line-analytics.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.line-bot.ai.analytics') || request()->routeIs('admin.line-analytics.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-chart-line w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Analytics</span>
                </a>
            </div>
        </div>

        {{-- AI Core - ระบบควบคุม AI แบบรวมศูนย์ 🆕 --}}
        @php $aiCoreActive = request()->routeIs('admin.ai-core.*'); @endphp
        <div class="space-y-1"
             data-menu-active="{{ $aiCoreActive ? 'true' : 'false' }}"
             data-menu-type="parent"
             data-menu-key="ai-core"
             x-data="{ aiCoreOpen: {{ $aiCoreActive ? 'true' : 'false' }} }">
            {{-- AI Core Header Button --}}
            <button @click="aiCoreOpen = !aiCoreOpen"
                    type="button"
                    class="w-full flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ $aiCoreActive ? 'bg-gradient-to-r from-purple-500 to-indigo-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
                <i class="fas fa-microchip w-5 text-center drop-shadow"></i>
                <span x-show="$store.sidebar.shouldExpand" x-transition class="flex-1 text-left font-medium drop-shadow whitespace-nowrap">AI Core</span>
                <i x-show="$store.sidebar.shouldExpand && aiCoreOpen" x-transition class="fas fa-chevron-down text-xs drop-shadow"></i>
                <i x-show="$store.sidebar.shouldExpand && !aiCoreOpen" x-transition class="fas fa-chevron-right text-xs drop-shadow"></i>
            </button>

            {{-- AI Core Submenu --}}
            <div x-show="aiCoreOpen" x-collapse x-cloak class="ml-8 space-y-1">
                {{-- Dashboard --}}
                <a href="{{ route('admin.ai-core.dashboard') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.ai-core.dashboard') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.ai-core.dashboard') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-tachometer-alt w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Dashboard</span>
                </a>

                {{-- Features --}}
                <a href="{{ route('admin.ai-core.features.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.ai-core.features.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.ai-core.features.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-puzzle-piece w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Features</span>
                </a>

                {{-- Tenants --}}
                <a href="{{ route('admin.ai-core.tenants.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.ai-core.tenants.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.ai-core.tenants.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-users w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Tenants</span>
                </a>

                {{-- Quotas --}}
                <a href="{{ route('admin.ai-core.quotas.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.ai-core.quotas.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.ai-core.quotas.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-gauge-high w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Quotas</span>
                </a>

                {{-- Schedules --}}
                <a href="{{ route('admin.ai-core.schedules.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.ai-core.schedules.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.ai-core.schedules.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-clock w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Schedules</span>
                </a>

                {{-- Alerts --}}
                <a href="{{ route('admin.ai-core.alerts.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.ai-core.alerts.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.ai-core.alerts.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-bell w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Alerts</span>
                </a>

                {{-- Analytics --}}
                <a href="{{ route('admin.ai-core.analytics.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.ai-core.analytics.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.ai-core.analytics.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-chart-bar w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Analytics</span>
                </a>

                {{-- Settings --}}
                <a href="{{ route('admin.ai-core.settings.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.ai-core.settings.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.ai-core.settings.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-cog w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Settings</span>
                </a>
            </div>
        </div>

        {{-- Analytics & Reports 📊 --}}
        <div class="space-y-1"
             data-menu-active="{{ request()->routeIs('admin.analytics.*') || request()->routeIs('admin.page-views.*') ? 'true' : 'false' }}"
             data-menu-type="parent"
             data-menu-key="analytics"
             x-data="{ analyticsOpen: {{ request()->routeIs('admin.analytics.*') || request()->routeIs('admin.page-views.*') ? 'true' : 'false' }} }">
            {{-- Analytics Header Button --}}
            <button @click="analyticsOpen = !analyticsOpen"
                    type="button"
                    class="w-full flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.analytics.*') || request()->routeIs('admin.page-views.*') ? 'bg-gradient-to-r from-indigo-500 to-blue-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
                <i class="fas fa-chart-line w-5 text-center drop-shadow"></i>
                <span x-show="$store.sidebar.shouldExpand" x-transition class="flex-1 text-left font-medium drop-shadow whitespace-nowrap">Analytics & Reports</span>
                <i x-show="$store.sidebar.shouldExpand && analyticsOpen" x-transition class="fas fa-chevron-down text-xs drop-shadow"></i>
                <i x-show="$store.sidebar.shouldExpand && !analyticsOpen" x-transition class="fas fa-chevron-right text-xs drop-shadow"></i>
            </button>

            {{-- Analytics Submenu --}}
            <div x-show="analyticsOpen" x-collapse x-cloak class="ml-8 space-y-1">
                {{-- System Analytics Overview --}}
                <a href="{{ route('admin.analytics.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.analytics.index') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.analytics.index') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-tachometer-alt w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">ภาพรวมระบบ</span>
                </a>

                {{-- Page Views Analytics --}}
                <a href="{{ route('admin.analytics.page-views.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.analytics.page-views.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.analytics.page-views.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-eye w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">ผู้เข้าชมแต่ละหน้า</span>
                </a>

                {{-- Real-time Analytics --}}
                <a href="{{ route('admin.analytics.realtime') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.analytics.realtime') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.analytics.realtime') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-stream w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Real-time</span>
                </a>

                {{-- Traffic Analytics --}}
                <a href="{{ route('admin.analytics.traffic') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.analytics.traffic') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.analytics.traffic') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-users w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Traffic & Visitors</span>
                </a>

                {{-- Performance Analytics --}}
                <a href="{{ route('admin.analytics.performance') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.analytics.performance') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.analytics.performance') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-rocket w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Performance</span>
                </a>

                {{-- Business Analytics --}}
                <a href="{{ route('admin.analytics.business') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.analytics.business') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.analytics.business') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-chart-pie w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Business Analytics</span>
                </a>

                {{-- Database Analytics --}}
                <a href="{{ route('admin.analytics.database') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.analytics.database') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.analytics.database') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-database w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Database Stats</span>
                </a>

                {{-- ศูนย์รายงานรวม --}}
                <a href="{{ route('admin.unified-reports.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.unified-reports.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.unified-reports.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-chart-bar w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">ศูนย์รายงานรวม</span>
                </a>
            </div>
        </div>

        {{-- Security & Monitoring 🔒 --}}
        <div class="space-y-1"
             data-menu-active="{{ request()->routeIs('admin.security.*') ? 'true' : 'false' }}"
             data-menu-type="parent"
             data-menu-key="security"
             x-data="{ securityOpen: {{ request()->routeIs('admin.security.*') ? 'true' : 'false' }} }">
            {{-- Security Header Button --}}
            <button @click="securityOpen = !securityOpen"
                    type="button"
                    class="w-full flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.security.*') ? 'bg-gradient-to-r from-red-500 to-pink-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
                <i class="fas fa-shield-halved w-5 text-center drop-shadow"></i>
                <span x-show="$store.sidebar.shouldExpand" x-transition class="flex-1 text-left font-medium drop-shadow whitespace-nowrap">Security</span>
                <i x-show="$store.sidebar.shouldExpand && securityOpen" x-transition class="fas fa-chevron-down text-xs drop-shadow"></i>
                <i x-show="$store.sidebar.shouldExpand && !securityOpen" x-transition class="fas fa-chevron-right text-xs drop-shadow"></i>
            </button>

            {{-- Security Submenu --}}
            <div x-show="securityOpen" x-collapse x-cloak class="ml-8 space-y-1">
                {{-- Security Dashboard --}}
                <a href="{{ route('admin.security.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.security.index') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.security.index') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-tachometer-alt w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Dashboard</span>
                </a>

                {{-- IP Blocking & Rate Limiting --}}
                <a href="{{ route('admin.security.analytics') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.security.analytics') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.security.analytics') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-ban w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">IP & Rate Limit</span>
                </a>

                {{-- Threat Intelligence --}}
                <a href="{{ route('admin.security.threat-intelligence') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.security.threat-intelligence') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.security.threat-intelligence') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-brain w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Threat Intelligence</span>
                </a>
            </div>
        </div>

        {{-- Communication (Email & Notifications) 📧 --}}
        <div class="space-y-1"
             data-menu-active="{{ request()->routeIs('admin.email.*') || request()->routeIs('admin.notifications.*') || request()->routeIs('admin.notification-templates.*') ? 'true' : 'false' }}"
             data-menu-type="parent"
             data-menu-key="communication"
             x-data="{ commOpen: {{ request()->routeIs('admin.email.*') || request()->routeIs('admin.notifications.*') || request()->routeIs('admin.notification-templates.*') ? 'true' : 'false' }} }">
            {{-- Communication Header Button --}}
            <button @click="commOpen = !commOpen"
                    type="button"
                    class="w-full flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.email.*') || request()->routeIs('admin.notifications.*') || request()->routeIs('admin.notification-templates.*') ? 'bg-gradient-to-r from-cyan-500 to-blue-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
                <i class="fas fa-envelope w-5 text-center drop-shadow"></i>
                <span x-show="$store.sidebar.shouldExpand" x-transition class="flex-1 text-left font-medium drop-shadow whitespace-nowrap">Communication</span>
                <i x-show="$store.sidebar.shouldExpand && commOpen" x-transition class="fas fa-chevron-down text-xs drop-shadow"></i>
                <i x-show="$store.sidebar.shouldExpand && !commOpen" x-transition class="fas fa-chevron-right text-xs drop-shadow"></i>
            </button>

            {{-- Communication Submenu --}}
            <div x-show="commOpen" x-collapse x-cloak class="ml-8 space-y-1">
                {{-- Email Dashboard --}}
                <a href="{{ route('admin.email.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.email.index') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.email.index') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-tachometer-alt w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Email Dashboard</span>
                </a>

                {{-- Email Providers --}}
                <a href="{{ route('admin.email.providers') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.email.providers') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.email.providers') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-server w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Providers</span>
                </a>

                {{-- Email Templates --}}
                <a href="{{ route('admin.email.templates.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.email.templates.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.email.templates.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-file-alt w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Templates</span>
                </a>

                {{-- Email Logs --}}
                <a href="{{ route('admin.email.logs') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.email.logs') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.email.logs') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-history w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Logs</span>
                </a>

                {{-- การแจ้งเตือน --}}
                <a href="{{ route('admin.notifications.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.notifications.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.notifications.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-bell w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">การแจ้งเตือน</span>
                </a>

                {{-- เทมเพลตการแจ้งเตือน --}}
                <a href="{{ route('admin.notification-templates.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.notification-templates.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.notification-templates.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-clipboard-list w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">เทมเพลตการแจ้งเตือน</span>
                </a>
            </div>
        </div>

        {{-- AI & Automation (Extended) 🤖 --}}
        <div class="space-y-1"
             data-menu-active="{{ request()->routeIs('admin.ai-providers.*') || request()->routeIs('admin.ai-monitoring.*') ? 'true' : 'false' }}"
             data-menu-type="parent"
             data-menu-key="ai-automation"
             x-data="{ aiAutoOpen: {{ request()->routeIs('admin.ai-providers.*') || request()->routeIs('admin.ai-monitoring.*') ? 'true' : 'false' }} }">
            {{-- AI Automation Header Button --}}
            <button @click="aiAutoOpen = !aiAutoOpen"
                    type="button"
                    class="w-full flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.ai-providers.*') || request()->routeIs('admin.ai-monitoring.*') ? 'bg-gradient-to-r from-violet-500 to-purple-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
                <i class="fas fa-robot w-5 text-center drop-shadow"></i>
                <span x-show="$store.sidebar.shouldExpand" x-transition class="flex-1 text-left font-medium drop-shadow whitespace-nowrap">AI Automation</span>
                <i x-show="$store.sidebar.shouldExpand && aiAutoOpen" x-transition class="fas fa-chevron-down text-xs drop-shadow"></i>
                <i x-show="$store.sidebar.shouldExpand && !aiAutoOpen" x-transition class="fas fa-chevron-right text-xs drop-shadow"></i>
            </button>

            {{-- AI Automation Submenu --}}
            <div x-show="aiAutoOpen" x-collapse x-cloak class="ml-8 space-y-1">
                {{-- AI Providers (API Keys) ⭐ สำคัญ! --}}
                <a href="{{ route('admin.ai-providers.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.ai-providers.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.ai-providers.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-key w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">API Providers</span>
                </a>

                {{-- AI Monitoring --}}
                <a href="{{ route('admin.ai-monitoring.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.ai-monitoring.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.ai-monitoring.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-chart-area w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Monitoring</span>
                </a>
            </div>
        </div>

        {{-- HRM (Human Resource Management) 👔 --}}
        <div class="space-y-1"
             data-menu-active="{{ request()->routeIs('admin.hrm.*') ? 'true' : 'false' }}"
             data-menu-type="parent"
             data-menu-key="hrm"
             x-data="{ hrmOpen: {{ request()->routeIs('admin.hrm.*') ? 'true' : 'false' }} }">
            {{-- HRM Header Button --}}
            <button @click="hrmOpen = !hrmOpen"
                    type="button"
                    class="w-full flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.hrm.*') ? 'bg-gradient-to-r from-teal-500 to-green-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
                <i class="fas fa-user-tie w-5 text-center drop-shadow"></i>
                <span x-show="$store.sidebar.shouldExpand" x-transition class="flex-1 text-left font-medium drop-shadow whitespace-nowrap">HRM</span>
                <i x-show="$store.sidebar.shouldExpand && hrmOpen" x-transition class="fas fa-chevron-down text-xs drop-shadow"></i>
                <i x-show="$store.sidebar.shouldExpand && !hrmOpen" x-transition class="fas fa-chevron-right text-xs drop-shadow"></i>
            </button>

            {{-- HRM Submenu --}}
            <div x-show="hrmOpen" x-collapse x-cloak class="ml-8 space-y-1">
                <a href="{{ route('admin.hrm.dashboard') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.hrm.dashboard') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.hrm.dashboard') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-tachometer-alt w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Dashboard</span>
                </a>

                <a href="{{ route('admin.hrm.employees.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.hrm.employees.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.hrm.employees.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-users w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">พนักงาน</span>
                </a>

                <a href="{{ route('admin.hrm.attendance.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.hrm.attendance.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.hrm.attendance.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-calendar-check w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">เช็คชื่อ</span>
                </a>

                <a href="{{ route('admin.hrm.payroll.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.hrm.payroll.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.hrm.payroll.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-money-check-alt w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">เงินเดือน</span>
                </a>
            </div>
        </div>

        {{-- Accounting 💼 --}}
        <div class="space-y-1"
             data-menu-active="{{ request()->routeIs('admin.accounting.*') ? 'true' : 'false' }}"
             data-menu-type="parent"
             data-menu-key="accounting"
             x-data="{ accountingOpen: {{ request()->routeIs('admin.accounting.*') ? 'true' : 'false' }} }">
            {{-- Accounting Header Button --}}
            <button @click="accountingOpen = !accountingOpen"
                    type="button"
                    class="w-full flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.accounting.*') ? 'bg-gradient-to-r from-amber-500 to-yellow-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
                <i class="fas fa-calculator w-5 text-center drop-shadow"></i>
                <span x-show="$store.sidebar.shouldExpand" x-transition class="flex-1 text-left font-medium drop-shadow whitespace-nowrap">Accounting</span>
                <i x-show="$store.sidebar.shouldExpand && accountingOpen" x-transition class="fas fa-chevron-down text-xs drop-shadow"></i>
                <i x-show="$store.sidebar.shouldExpand && !accountingOpen" x-transition class="fas fa-chevron-right text-xs drop-shadow"></i>
            </button>

            {{-- Accounting Submenu --}}
            <div x-show="accountingOpen" x-collapse x-cloak class="ml-8 space-y-1">
                <a href="{{ route('admin.accounting.dashboard') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.accounting.dashboard') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.accounting.dashboard') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-tachometer-alt w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Dashboard</span>
                </a>

                <a href="{{ route('admin.accounting.invoices.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.accounting.invoices.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.accounting.invoices.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-file-invoice w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">ใบแจ้งหนี้</span>
                </a>

                <a href="{{ route('admin.accounting.reports.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.accounting.reports.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.accounting.reports.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-chart-pie w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">รายงาน</span>
                </a>
            </div>
        </div>

        {{-- POS (Point of Sale) 🛍️ --}}
        <div class="space-y-1"
             data-menu-active="{{ request()->routeIs('admin.pos.*') ? 'true' : 'false' }}"
             data-menu-type="parent"
             data-menu-key="pos"
             x-data="{ posOpen: {{ request()->routeIs('admin.pos.*') ? 'true' : 'false' }} }">
            {{-- POS Header Button --}}
            <button @click="posOpen = !posOpen"
                    type="button"
                    class="w-full flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.pos.*') ? 'bg-gradient-to-r from-pink-500 to-rose-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
                <i class="fas fa-cash-register w-5 text-center drop-shadow"></i>
                <span x-show="$store.sidebar.shouldExpand" x-transition class="flex-1 text-left font-medium drop-shadow whitespace-nowrap">POS</span>
                <i x-show="$store.sidebar.shouldExpand && posOpen" x-transition class="fas fa-chevron-down text-xs drop-shadow"></i>
                <i x-show="$store.sidebar.shouldExpand && !posOpen" x-transition class="fas fa-chevron-right text-xs drop-shadow"></i>
            </button>

            {{-- POS Submenu --}}
            <div x-show="posOpen" x-collapse x-cloak class="ml-8 space-y-1">
                <a href="{{ route('admin.pos.dashboard') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.pos.dashboard') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.pos.dashboard') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-tachometer-alt w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Dashboard</span>
                </a>

                <a href="{{ route('admin.pos.devices.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.pos.devices.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.pos.devices.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-tablet-alt w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">เครื่อง POS</span>
                </a>

                <a href="{{ route('admin.pos.transactions.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.pos.transactions.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.pos.transactions.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-receipt w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">ธุรกรรม</span>
                </a>

                <a href="{{ route('admin.pos.labels.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.pos.labels.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.pos.labels.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-barcode w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">ฉลาก Barcode</span>
                </a>
            </div>
        </div>

        {{-- Hotels 🏨 --}}
        <div class="space-y-1"
             data-menu-active="{{ request()->routeIs('admin.hotels.*') || request()->routeIs('admin.hotel-owners.*') ? 'true' : 'false' }}"
             data-menu-type="parent"
             data-menu-key="hotels"
             x-data="{ hotelsOpen: {{ request()->routeIs('admin.hotels.*') || request()->routeIs('admin.hotel-owners.*') ? 'true' : 'false' }} }">
            {{-- Hotels Header Button --}}
            <button @click="hotelsOpen = !hotelsOpen"
                    type="button"
                    class="w-full flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.hotels.*') || request()->routeIs('admin.hotel-owners.*') ? 'bg-gradient-to-r from-sky-500 to-blue-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
                <i class="fas fa-hotel w-5 text-center drop-shadow"></i>
                <span x-show="$store.sidebar.shouldExpand" x-transition class="flex-1 text-left font-medium drop-shadow whitespace-nowrap">Hotels</span>
                <i x-show="$store.sidebar.shouldExpand && hotelsOpen" x-transition class="fas fa-chevron-down text-xs drop-shadow"></i>
                <i x-show="$store.sidebar.shouldExpand && !hotelsOpen" x-transition class="fas fa-chevron-right text-xs drop-shadow"></i>
            </button>

            {{-- Hotels Submenu --}}
            <div x-show="hotelsOpen" x-collapse x-cloak class="ml-8 space-y-1">
                <a href="{{ route('admin.hotels.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.hotels.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.hotels.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-building w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">โรงแรม</span>
                </a>

                <a href="{{ route('admin.hotel-owners.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.hotel-owners.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.hotel-owners.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-user-circle w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">เจ้าของโรงแรม</span>
                </a>
            </div>
        </div>

        {{-- Homepage Manager - จัดการหน้าแรก 🏠 --}}
        <div class="space-y-1"
             data-menu-active="{{ request()->routeIs('admin.homepage-manager.*') ? 'true' : 'false' }}"
             data-menu-type="parent"
             data-menu-key="homepage-manager"
             x-data="{ homepageOpen: {{ request()->routeIs('admin.homepage-manager.*') ? 'true' : 'false' }} }">
            {{-- Homepage Header Button --}}
            <button @click="homepageOpen = !homepageOpen"
                    type="button"
                    class="w-full flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.homepage-manager.*') ? 'bg-gradient-to-r from-orange-500 to-red-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
                <i class="fas fa-home w-5 text-center drop-shadow"></i>
                <span x-show="$store.sidebar.shouldExpand" x-transition class="flex-1 text-left font-medium drop-shadow whitespace-nowrap">จัดการหน้าแรก</span>
                <span x-show="$store.sidebar.shouldExpand" x-transition class="ml-auto px-1.5 py-0.5 bg-orange-500 text-white text-[10px] font-bold rounded-full shadow-lg">NEW</span>
                <i x-show="$store.sidebar.shouldExpand && homepageOpen" x-transition class="fas fa-chevron-down text-xs drop-shadow"></i>
                <i x-show="$store.sidebar.shouldExpand && !homepageOpen" x-transition class="fas fa-chevron-right text-xs drop-shadow"></i>
            </button>

            {{-- Homepage Submenu --}}
            <div x-show="homepageOpen" x-collapse x-cloak class="ml-8 space-y-1">
                {{-- Visual Builder --}}
                <a href="{{ route('admin.homepage-manager.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.homepage-manager.index') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.homepage-manager.index') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-palette w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Visual Builder</span>
                </a>

                {{-- Preview --}}
                <a href="{{ route('admin.homepage-manager.preview') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.homepage-manager.preview') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.homepage-manager.preview') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-eye w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">ดูตัวอย่าง</span>
                </a>

                {{-- Sections --}}
                <a href="{{ route('admin.homepage-manager.sections.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.homepage-manager.sections.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.homepage-manager.sections.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-layer-group w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">จัดการ Sections</span>
                </a>

                {{-- Templates --}}
                <a href="{{ route('admin.homepage-manager.templates.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.homepage-manager.templates.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.homepage-manager.templates.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-clone w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Templates</span>
                </a>
            </div>
        </div>

        {{-- Content Management 📄 --}}
        <div class="space-y-1"
             data-menu-active="{{ request()->routeIs('admin.pages.*') || request()->routeIs('admin.articles.*') ? 'true' : 'false' }}"
             data-menu-type="parent"
             data-menu-key="content"
             x-data="{ contentOpen: {{ request()->routeIs('admin.pages.*') || request()->routeIs('admin.articles.*') ? 'true' : 'false' }} }">
            {{-- Content Header Button --}}
            <button @click="contentOpen = !contentOpen"
                    type="button"
                    class="w-full flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.pages.*') || request()->routeIs('admin.articles.*') ? 'bg-gradient-to-r from-fuchsia-500 to-purple-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
                <i class="fas fa-file-lines w-5 text-center drop-shadow"></i>
                <span x-show="$store.sidebar.shouldExpand" x-transition class="flex-1 text-left font-medium drop-shadow whitespace-nowrap">Content</span>
                <i x-show="$store.sidebar.shouldExpand && contentOpen" x-transition class="fas fa-chevron-down text-xs drop-shadow"></i>
                <i x-show="$store.sidebar.shouldExpand && !contentOpen" x-transition class="fas fa-chevron-right text-xs drop-shadow"></i>
            </button>

            {{-- Content Submenu --}}
            <div x-show="contentOpen" x-collapse x-cloak class="ml-8 space-y-1">
                <a href="{{ route('admin.pages.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.pages.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.pages.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-pager w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Pages (CMS)</span>
                </a>

                <a href="{{ route('admin.articles.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.articles.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.articles.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-newspaper w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">บทความ</span>
                </a>
            </div>
        </div>

        {{-- KYC Verification 🆕 --}}
        <a href="{{ route('admin.kyc.index') }}"
           @click="$store.sidebar.closeOnMenuClick()"
           data-menu-active="{{ request()->routeIs('admin.kyc.*') ? 'true' : 'false' }}"
           data-menu-type="item"
           data-menu-key="kyc"
           class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.kyc.*') ? 'bg-gradient-to-r from-indigo-500 to-purple-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
            <i class="fas fa-id-card w-5 text-center drop-shadow"></i>
            <span x-show="$store.sidebar.shouldExpand" x-transition class="font-medium drop-shadow whitespace-nowrap">KYC Verification</span>
        </a>

        {{-- Support Tickets (Collapsible Menu) 🆕 --}}
        <div class="space-y-1"
             data-menu-active="{{ request()->routeIs('admin.tickets.*') ? 'true' : 'false' }}"
             data-menu-type="parent"
             data-menu-key="tickets"
             x-data="{ ticketsOpen: {{ request()->routeIs('admin.tickets.*') ? 'true' : 'false' }} }">
            {{-- Tickets Header Button --}}
            <button @click="ticketsOpen = !ticketsOpen"
                    type="button"
                    class="w-full flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.tickets.*') ? 'bg-gradient-to-r from-rose-500 to-pink-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
                <i class="fas fa-headset w-5 text-center drop-shadow"></i>
                <span x-show="$store.sidebar.shouldExpand" x-transition class="flex-1 text-left font-medium drop-shadow whitespace-nowrap">Support Tickets</span>
                <i x-show="$store.sidebar.shouldExpand && ticketsOpen" x-transition class="fas fa-chevron-down text-xs drop-shadow"></i>
                <i x-show="$store.sidebar.shouldExpand && !ticketsOpen" x-transition class="fas fa-chevron-right text-xs drop-shadow"></i>
            </button>

            {{-- Tickets Submenu --}}
            <div x-show="ticketsOpen" x-collapse x-cloak class="ml-8 space-y-1">
                {{-- All Tickets --}}
                <a href="{{ route('admin.tickets.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.tickets.index') || request()->routeIs('admin.tickets.show') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.tickets.index') || request()->routeIs('admin.tickets.show') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-ticket w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Tickets ทั้งหมด</span>
                </a>

                {{-- Analytics --}}
                <a href="{{ route('admin.tickets.analytics') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.tickets.analytics') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.tickets.analytics') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-chart-line w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Analytics</span>
                </a>

                {{-- Ratings --}}
                <a href="{{ route('admin.tickets.ratings') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.tickets.ratings') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.tickets.ratings') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-star w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">ความพึงพอใจ</span>
                </a>

                {{-- Categories --}}
                <a href="{{ route('admin.tickets.categories.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.tickets.categories.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.tickets.categories.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-folder w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">หมวดหมู่</span>
                </a>

                {{-- Canned Responses --}}
                <a href="{{ route('admin.tickets.canned-responses.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.tickets.canned-responses.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.tickets.canned-responses.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-comment-dots w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">ข้อความสำเร็จรูป</span>
                </a>

                {{-- SLA Policies --}}
                <a href="{{ route('admin.tickets.sla-policies.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.tickets.sla-policies.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.tickets.sla-policies.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-clock w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">SLA Policies</span>
                </a>

                {{-- Assignment Rules --}}
                <a href="{{ route('admin.tickets.assignment-rules.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.tickets.assignment-rules.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.tickets.assignment-rules.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-user-check w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">กฎการมอบหมาย</span>
                </a>

                {{-- KB Articles --}}
                <a href="{{ route('admin.tickets.kb-articles.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.tickets.kb-articles.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.tickets.kb-articles.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-book w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Knowledge Base</span>
                </a>

                {{-- Settings --}}
                <a href="{{ route('admin.tickets.settings') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.tickets.settings') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.tickets.settings') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-cog w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">ตั้งค่า</span>
                </a>
            </div>
        </div>

        {{-- AI Bot Profiles --}}
        <a href="{{ route('admin.ai-bots.index') }}"
           data-menu-active="{{ request()->routeIs('admin.ai-bots.*') ? 'true' : 'false' }}"
           data-menu-type="item"
           data-menu-key="ai-bot-profiles"
           class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.ai-bots.*') ? 'bg-gradient-to-r from-purple-500 to-indigo-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
            <i class="fas fa-robot w-5 text-center drop-shadow"></i>
            <span x-show="$store.sidebar.shouldExpand" x-transition class="font-medium drop-shadow whitespace-nowrap">AI Bot Profiles</span>
        </a>

        {{-- (Products และ Orders ย้ายไปอยู่ใน Storefront Menu แล้ว) --}}

        {{-- Anti-Abuse Protection System (Collapsible Menu) 🛡️ --}}
        <div class="space-y-1"
             data-menu-active="{{ request()->routeIs('admin.anti-abuse.*') ? 'true' : 'false' }}"
             data-menu-type="parent"
             data-menu-key="anti-abuse"
             x-data="{ antiAbuseOpen: {{ request()->routeIs('admin.anti-abuse.*') ? 'true' : 'false' }} }">
            {{-- Anti-Abuse Header Button --}}
            <button @click="antiAbuseOpen = !antiAbuseOpen"
                    type="button"
                    class="w-full flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.anti-abuse.*') ? 'bg-gradient-to-r from-red-500 to-orange-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
                <i class="fas fa-shield-alt w-5 text-center drop-shadow"></i>
                <span x-show="$store.sidebar.shouldExpand" x-transition class="flex-1 text-left font-medium drop-shadow whitespace-nowrap">Anti-Abuse</span>
                <i x-show="$store.sidebar.shouldExpand && antiAbuseOpen" x-transition class="fas fa-chevron-down text-xs drop-shadow"></i>
                <i x-show="$store.sidebar.shouldExpand && !antiAbuseOpen" x-transition class="fas fa-chevron-right text-xs drop-shadow"></i>
            </button>

            {{-- Anti-Abuse Submenu --}}
            <div x-show="antiAbuseOpen" x-collapse x-cloak class="ml-8 space-y-1">
                {{-- Dashboard --}}
                <a href="{{ route('admin.anti-abuse.dashboard') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.anti-abuse.dashboard') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.anti-abuse.dashboard') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-tachometer-alt w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Dashboard</span>
                </a>

                {{-- Disputes --}}
                <a href="{{ route('admin.anti-abuse.disputes') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.anti-abuse.disputes*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.anti-abuse.disputes*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-exclamation-triangle w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">ข้อพิพาท</span>
                </a>

                {{-- Trust Scores --}}
                <a href="{{ route('admin.anti-abuse.trust-scores') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.anti-abuse.trust-scores*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.anti-abuse.trust-scores*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-star w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Trust Score</span>
                </a>

                {{-- Penalties --}}
                <a href="{{ route('admin.anti-abuse.penalties') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.anti-abuse.penalties*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.anti-abuse.penalties*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-gavel w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">ค่าปรับ</span>
                </a>

                {{-- Blocks --}}
                <a href="{{ route('admin.anti-abuse.blocks') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.anti-abuse.blocks*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.anti-abuse.blocks*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-user-slash w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">การ Block</span>
                </a>

                {{-- Location History --}}
                <a href="{{ route('admin.anti-abuse.location-history') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.anti-abuse.location-history*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.anti-abuse.location-history*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-map-marked-alt w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">ประวัติ GPS</span>
                </a>
            </div>
        </div>

        {{-- GPS Monitoring Center 📡 --}}
        <a href="{{ route('admin.gps-monitoring.index') }}"
           @click="$store.sidebar.closeOnMenuClick()"
           data-menu-active="{{ request()->routeIs('admin.gps-monitoring.*') ? 'true' : 'false' }}"
           data-menu-type="item"
           data-menu-key="gps-monitor"
           class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.gps-monitoring.*') ? 'bg-gradient-to-r from-green-500 to-teal-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
            <div class="relative">
                <i class="fas fa-satellite w-5 text-center drop-shadow"></i>
                @if(request()->routeIs('admin.gps-monitoring.*'))
                    <span class="absolute -top-1 -right-1 w-2 h-2 bg-green-400 rounded-full animate-ping"></span>
                @endif
            </div>
            <span x-show="$store.sidebar.shouldExpand" x-transition class="font-medium drop-shadow whitespace-nowrap">GPS Monitor</span>
            <span x-show="$store.sidebar.shouldExpand" x-transition class="ml-auto px-1.5 py-0.5 text-[10px] rounded bg-green-500/30 text-green-300 font-bold">LIVE</span>
        </a>

        {{-- Video Missions (ภารกิจดูคลิปรับรางวัล) 🎬 --}}
        <div class="space-y-1"
             data-menu-active="{{ request()->routeIs('admin.video-missions.*') ? 'true' : 'false' }}"
             data-menu-type="parent"
             data-menu-key="video-missions"
             x-data="{ videoMissionsOpen: {{ request()->routeIs('admin.video-missions.*') ? 'true' : 'false' }} }">
            {{-- Video Missions Header Button --}}
            <button @click="videoMissionsOpen = !videoMissionsOpen"
                    type="button"
                    class="w-full flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.video-missions.*') ? 'bg-gradient-to-r from-pink-500 to-purple-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
                <i class="fas fa-video w-5 text-center drop-shadow"></i>
                <span x-show="$store.sidebar.shouldExpand" x-transition class="flex-1 text-left font-medium drop-shadow whitespace-nowrap">ภารกิจดูคลิป</span>
                <span x-show="$store.sidebar.shouldExpand" x-transition class="ml-auto px-1.5 py-0.5 bg-gradient-to-r from-pink-500 to-purple-500 text-white text-[10px] font-bold rounded-full shadow-lg">NEW</span>
                <i x-show="$store.sidebar.shouldExpand && videoMissionsOpen" x-transition class="fas fa-chevron-down text-xs drop-shadow"></i>
                <i x-show="$store.sidebar.shouldExpand && !videoMissionsOpen" x-transition class="fas fa-chevron-right text-xs drop-shadow"></i>
            </button>

            {{-- Video Missions Submenu --}}
            <div x-show="videoMissionsOpen" x-collapse x-cloak class="ml-8 space-y-1">
                {{-- Dashboard --}}
                <a href="{{ route('admin.video-missions.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.video-missions.index') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.video-missions.index') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-chart-pie w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Dashboard</span>
                </a>

                {{-- จัดการภารกิจ --}}
                <a href="{{ route('admin.video-missions.missions') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.video-missions.missions') || request()->routeIs('admin.video-missions.create') || request()->routeIs('admin.video-missions.edit') || request()->routeIs('admin.video-missions.show') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.video-missions.missions') || request()->routeIs('admin.video-missions.create') || request()->routeIs('admin.video-missions.edit') || request()->routeIs('admin.video-missions.show') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-tasks w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">จัดการภารกิจ</span>
                </a>

                {{-- การทำภารกิจ --}}
                <a href="{{ route('admin.video-missions.completions') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.video-missions.completions') || request()->routeIs('admin.video-missions.completion') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.video-missions.completions') || request()->routeIs('admin.video-missions.completion') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-clipboard-check w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">การทำภารกิจ</span>
                </a>

                {{-- Rank Limits --}}
                <a href="{{ route('admin.video-missions.rank-limits') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.video-missions.rank-limits') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.video-missions.rank-limits') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-medal w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Rank Limits</span>
                </a>

                {{-- รายงาน --}}
                <a href="{{ route('admin.video-missions.reports') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.video-missions.reports') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.video-missions.reports') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-chart-line w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">รายงาน</span>
                </a>

                {{-- ตั้งค่า --}}
                <a href="{{ route('admin.video-missions.settings') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.video-missions.settings') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.video-missions.settings') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-cog w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">ตั้งค่า</span>
                </a>

                {{-- นำเข้าจาก YouTube --}}
                <a href="{{ route('admin.video-missions.import-youtube') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.video-missions.import-youtube') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.video-missions.import-youtube') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fab fa-youtube w-4 text-center drop-shadow text-red-400"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">นำเข้า YouTube</span>
                </a>
            </div>
        </div>

        {{-- Games & Entertainment 🎮 ไพ่ทาโร่ต์และเกม --}}
        <div class="space-y-1"
             data-menu-active="{{ request()->routeIs('admin.tarot.*') ? 'true' : 'false' }}"
             data-menu-type="parent"
             data-menu-key="games"
             x-data="{ gamesOpen: {{ request()->routeIs('admin.tarot.*') ? 'true' : 'false' }} }">
            {{-- Games Header Button --}}
            <button @click="gamesOpen = !gamesOpen"
                    type="button"
                    class="w-full flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.tarot.*') ? 'bg-gradient-to-r from-purple-500 to-pink-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
                <i class="fas fa-dice w-5 text-center drop-shadow"></i>
                <span x-show="$store.sidebar.shouldExpand" x-transition class="flex-1 text-left font-medium drop-shadow whitespace-nowrap">🎴 เกม</span>
                <span x-show="$store.sidebar.shouldExpand" x-transition class="ml-auto px-1.5 py-0.5 bg-gradient-to-r from-purple-500 to-pink-500 text-white text-[10px] font-bold rounded-full shadow-lg">NEW</span>
                <i x-show="$store.sidebar.shouldExpand && gamesOpen" x-transition class="fas fa-chevron-down text-xs drop-shadow"></i>
                <i x-show="$store.sidebar.shouldExpand && !gamesOpen" x-transition class="fas fa-chevron-right text-xs drop-shadow"></i>
            </button>

            {{-- Games Submenu --}}
            <div x-show="gamesOpen" x-collapse x-cloak class="ml-8 space-y-1">
                {{-- Tarot Dashboard --}}
                <a href="{{ route('admin.tarot.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.tarot.index') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.tarot.index') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-chart-pie w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Dashboard</span>
                </a>

                {{-- Tarot Cards --}}
                <a href="{{ route('admin.tarot.cards.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.tarot.cards.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.tarot.cards.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-images w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">ไพ่ทาโร่ต์</span>
                </a>

                {{-- Card Backs --}}
                <a href="{{ route('admin.tarot.card-backs.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.tarot.card-backs.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.tarot.card-backs.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-layer-group w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">หลังไพ่</span>
                </a>

                {{-- Interpretations --}}
                <a href="{{ route('admin.tarot.interpretations.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.tarot.interpretations.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.tarot.interpretations.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-book-open w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">คำทำนาย</span>
                </a>

                {{-- Categories --}}
                <a href="{{ route('admin.tarot.categories.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.tarot.categories.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.tarot.categories.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-tags w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">หมวดหมู่</span>
                </a>

                {{-- Readings History --}}
                <a href="{{ route('admin.tarot.readings.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.tarot.readings.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.tarot.readings.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-history w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">ประวัติการดู</span>
                </a>

                {{-- Tarot Settings --}}
                <a href="{{ route('admin.tarot.settings') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.tarot.settings*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.tarot.settings*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-cog w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">ตั้งค่า</span>
                </a>
            </div>
        </div>

        {{-- Academy System (ระบบการเรียนรู้) 🎓 --}}
        <div class="space-y-1"
             data-menu-active="{{ request()->routeIs('admin.academy.*') || request()->routeIs('admin.learning-center.*') || request()->routeIs('admin.quiz-management.*') || request()->routeIs('admin.certificates.*') || request()->routeIs('admin.instructor.*') || request()->routeIs('admin.articles.*') || request()->routeIs('admin.categories.*') ? 'true' : 'false' }}"
             data-menu-type="parent"
             data-menu-key="academy"
             x-data="{ academyOpen: {{ request()->routeIs('admin.academy.*') || request()->routeIs('admin.learning-center.*') || request()->routeIs('admin.quiz-management.*') || request()->routeIs('admin.certificates.*') || request()->routeIs('admin.instructor.*') || request()->routeIs('admin.articles.*') || request()->routeIs('admin.categories.*') ? 'true' : 'false' }} }">
            {{-- Academy Header Button --}}
            <button @click="academyOpen = !academyOpen"
                    type="button"
                    class="w-full flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.academy.*') || request()->routeIs('admin.learning-center.*') || request()->routeIs('admin.quiz-management.*') || request()->routeIs('admin.certificates.*') || request()->routeIs('admin.instructor.*') || request()->routeIs('admin.articles.*') || request()->routeIs('admin.categories.*') ? 'bg-gradient-to-r from-emerald-500 to-teal-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
                <i class="fas fa-graduation-cap w-5 text-center drop-shadow"></i>
                <span x-show="$store.sidebar.shouldExpand" x-transition class="flex-1 text-left font-medium drop-shadow whitespace-nowrap">🎓 Academy</span>
                <span x-show="$store.sidebar.shouldExpand" x-transition class="ml-auto px-1.5 py-0.5 bg-gradient-to-r from-emerald-500 to-teal-500 text-white text-[10px] font-bold rounded-full shadow-lg">LMS</span>
                <i x-show="$store.sidebar.shouldExpand && academyOpen" x-transition class="fas fa-chevron-down text-xs drop-shadow"></i>
                <i x-show="$store.sidebar.shouldExpand && !academyOpen" x-transition class="fas fa-chevron-right text-xs drop-shadow"></i>
            </button>

            {{-- Academy Submenu --}}
            <div x-show="academyOpen" x-collapse x-cloak class="ml-8 space-y-1">
                {{-- Learning Center Dashboard --}}
                <a href="{{ route('admin.learning-center.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.learning-center.index') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.learning-center.index') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-book-open w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">ศูนย์เรียนรู้</span>
                </a>

                {{-- คอร์สเรียน/บทความ --}}
                <a href="{{ route('admin.articles.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.articles.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.articles.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-chalkboard-teacher w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">คอร์สเรียน</span>
                </a>

                {{-- หมวดหมู่ --}}
                <a href="{{ route('admin.categories.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.categories.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.categories.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-folder-tree w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">หมวดหมู่</span>
                </a>

                {{-- จัดการแบบทดสอบ --}}
                <a href="{{ route('admin.quiz-management.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.quiz-management.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.quiz-management.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-clipboard-question w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">แบบทดสอบ</span>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="ml-auto px-1.5 py-0.5 bg-blue-500 text-white text-[9px] font-bold rounded-full">Quiz</span>
                </a>

                {{-- ใบประกาศนียบัตร (Student) --}}
                <a href="{{ route('admin.certificates.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.certificates.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.certificates.*') && !request()->routeIs('admin.academy.certificates.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-award w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">ใบประกาศนักเรียน</span>
                </a>

                {{-- ใบประกาศนียบัตร (System Management) --}}
                <a href="{{ route('admin.academy.certificates.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.academy.certificates.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.academy.certificates.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-certificate w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">จัดการใบประกาศ</span>
                </a>

                {{-- แดชบอร์ดอาจารย์ --}}
                <a href="{{ route('admin.instructor.dashboard') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.instructor.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.instructor.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-chalkboard w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">แดชบอร์ดอาจารย์</span>
                </a>

                {{-- ตั้งค่า Academy --}}
                <a href="{{ route('admin.academy.settings.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.academy.settings.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.academy.settings.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-cog w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">ตั้งค่า Academy</span>
                </a>
            </div>
        </div>

        {{-- Divider --}}
        <div x-show="$store.sidebar.shouldExpand" x-transition class="border-t border-white/30 my-4"></div>

        {{-- Settings --}}
        <a href="{{ route('admin.settings.index') }}"
           data-menu-active="{{ request()->routeIs('admin.settings.index') ? 'true' : 'false' }}"
           data-menu-type="item"
           data-menu-key="settings"
           class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.settings.index') ? 'bg-gradient-to-r from-blue-500 to-purple-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
            <i class="fas fa-cog w-5 text-center drop-shadow"></i>
            <span x-show="$store.sidebar.shouldExpand" x-transition class="font-medium drop-shadow whitespace-nowrap">ตั้งค่า</span>
        </a>

        {{-- Mobile App Management 📱 สำหรับควบคุม App มือถือ (Standalone) --}}
        {{-- Admin ควบคุมได้: Dashboard, Push Notifications, Banner โฆษณา, Device Analytics --}}
        <div class="space-y-1"
             data-menu-active="{{ request()->routeIs('admin.mobile-app.*') ? 'true' : 'false' }}"
             data-menu-type="parent"
             data-menu-key="mobile-app"
             x-data="{ mobileAppOpen: {{ request()->routeIs('admin.mobile-app.*') ? 'true' : 'false' }} }">
            {{-- Mobile App Header Button --}}
            <button @click="mobileAppOpen = !mobileAppOpen"
                    type="button"
                    class="w-full flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.mobile-app.*') ? 'bg-gradient-to-r from-cyan-500 to-blue-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
                <i class="fas fa-mobile-alt w-5 text-center drop-shadow"></i>
                <span x-show="$store.sidebar.shouldExpand" x-transition class="flex-1 text-left font-medium drop-shadow whitespace-nowrap">📱 Mobile App</span>
                <span x-show="$store.sidebar.shouldExpand" x-transition class="ml-auto px-1.5 py-0.5 bg-gradient-to-r from-cyan-500 to-blue-500 text-white text-[10px] font-bold rounded-full shadow-lg animate-pulse">LIVE</span>
                <i x-show="$store.sidebar.shouldExpand && mobileAppOpen" x-transition class="fas fa-chevron-down text-xs drop-shadow"></i>
                <i x-show="$store.sidebar.shouldExpand && !mobileAppOpen" x-transition class="fas fa-chevron-right text-xs drop-shadow"></i>
            </button>

            {{-- Mobile App Submenu --}}
            <div x-show="mobileAppOpen" x-collapse x-cloak class="ml-8 space-y-1">
                {{-- Dashboard (ภาพรวม) --}}
                <a href="{{ route('admin.mobile-app.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.mobile-app.index') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.mobile-app.index') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-tachometer-alt w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">ภาพรวม</span>
                </a>

                {{-- Push Notifications (แจ้งเตือน) --}}
                <a href="{{ route('admin.mobile-app.push.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.mobile-app.push.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.mobile-app.push.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-bell w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Push Notifications</span>
                </a>

                {{-- App Banners (แบนเนอร์โฆษณา) --}}
                <a href="{{ route('admin.mobile-app.banners.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.mobile-app.banners.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.mobile-app.banners.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-ad w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">แบนเนอร์โฆษณา</span>
                </a>

                {{-- Device Analytics (สถิติอุปกรณ์) --}}
                <a href="{{ route('admin.mobile-app.analytics.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   data-menu-active="{{ request()->routeIs('admin.mobile-app.analytics.*') ? 'true' : 'false' }}"
                   data-menu-type="submenu"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.mobile-app.analytics.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-chart-line w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">สถิติอุปกรณ์</span>
                </a>
            </div>
        </div>

        {{-- Site Settings (โลโก้, SEO, Social Media) --}}
        <a href="{{ route('admin.site-settings.index') }}"
           @click="$store.sidebar.closeOnMenuClick()"
           data-menu-active="{{ request()->routeIs('admin.site-settings.*') ? 'true' : 'false' }}"
           data-menu-type="item"
           data-menu-key="site-settings"
           class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.site-settings.*') ? 'bg-gradient-to-r from-blue-500 to-purple-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
            <i class="fas fa-palette w-5 text-center drop-shadow"></i>
            <span x-show="$store.sidebar.shouldExpand" x-transition class="font-medium drop-shadow whitespace-nowrap">ตั้งค่าเว็บไซต์</span>
        </a>

        {{-- Google Maps Settings 🗺️ NEW --}}
        <a href="{{ route('admin.settings.google-maps') }}"
           @click="$store.sidebar.closeOnMenuClick()"
           data-menu-active="{{ request()->routeIs('admin.settings.google-maps*') ? 'true' : 'false' }}"
           data-menu-type="item"
           data-menu-key="google-maps"
           class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.settings.google-maps*') ? 'bg-gradient-to-r from-green-500 to-teal-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
            <i class="fas fa-map-marked-alt w-5 text-center drop-shadow"></i>
            <span x-show="$store.sidebar.shouldExpand" x-transition class="font-medium drop-shadow whitespace-nowrap">Google Maps API</span>
        </a>

        {{-- Cache Settings 🚀 NEW --}}
        <a href="{{ route('admin.cache.index') }}"
           @click="$store.sidebar.closeOnMenuClick()"
           data-menu-active="{{ request()->routeIs('admin.cache.*') ? 'true' : 'false' }}"
           data-menu-type="item"
           data-menu-key="cache"
           class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.cache.*') ? 'bg-gradient-to-r from-purple-500 to-pink-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
            <i class="fas fa-bolt w-5 text-center drop-shadow"></i>
            <span x-show="$store.sidebar.shouldExpand" x-transition class="font-medium drop-shadow whitespace-nowrap">ระบบแคช</span>
        </a>

        {{-- Cloudflare CDN Management ☁️ NEW --}}
        <a href="{{ route('admin.cloudflare.index') }}"
           @click="$store.sidebar.closeOnMenuClick()"
           data-menu-active="{{ request()->routeIs('admin.cloudflare.*') ? 'true' : 'false' }}"
           data-menu-type="item"
           data-menu-key="cloudflare"
           class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.cloudflare.*') ? 'bg-gradient-to-r from-orange-500 to-yellow-500 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
            <i class="fas fa-cloud w-5 text-center drop-shadow"></i>
            <span x-show="$store.sidebar.shouldExpand" x-transition class="font-medium drop-shadow whitespace-nowrap">Cloudflare CDN</span>
        </a>

        {{-- Arrow X Theme Customizer ⭐ NEW --}}
        <a href="{{ route('admin.arrow-x-theme.index') }}"
           @click="$store.sidebar.closeOnMenuClick()"
           data-menu-active="{{ request()->routeIs('admin.arrow-x-theme.*') ? 'true' : 'false' }}"
           data-menu-type="item"
           data-menu-key="arrow-x-theme"
           class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.arrow-x-theme.*') ? 'bg-gradient-to-r from-purple-500 to-pink-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
            <i class="fas fa-paint-brush w-5 text-center drop-shadow"></i>
            <span x-show="$store.sidebar.shouldExpand" x-transition class="font-medium drop-shadow whitespace-nowrap">ปรับแต่งทีม Arrow X</span>
        </a>

        {{-- Help --}}
        <a href="#"
           class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform glass-neu text-white/90 hover:bg-white/20 hover:scale-105">
            <i class="fas fa-question-circle w-5 text-center drop-shadow"></i>
            <span x-show="$store.sidebar.shouldExpand" x-transition class="font-medium drop-shadow whitespace-nowrap">ช่วยเหลือ</span>
        </a>
        @endif {{-- End of @if($useMenuService) --}}
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

                    {{-- Footer Logo (จาก Theme Setting) หรือ Rocket Icon (Fallback) --}}
                    @php
                        $logoAnimation = $themeSetting->footer_logo_animation ?? 'float';
                        $animationClass = match($logoAnimation) {
                            'none' => '',
                            'float' => 'animate-float',
                            'spin' => 'animate-spin-slow',
                            'bounce' => 'animate-bounce',
                            'pulse' => 'animate-pulse',
                            'swing' => 'animate-swing',
                            default => 'animate-float'
                        };
                    @endphp
                    @if($themeSetting && $themeSetting->footer_logo_path)
                        <img src="{{ asset('storage/' . $themeSetting->footer_logo_path) }}"
                             alt="Footer Logo"
                             class="w-10 h-10 object-contain drop-shadow-2xl relative z-10 {{ $animationClass }}">
                    @else
                        <i class="fas fa-rocket text-white text-2xl drop-shadow-2xl relative z-10 {{ $animationClass }}"></i>
                    @endif

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
