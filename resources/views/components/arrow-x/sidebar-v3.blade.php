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

@php
    // ดึงโลโก้จาก ThemeSetting (โลโก้ธีม - แยกจากโลโก้เว็บไซต์)
    $themeSetting = \App\Models\ThemeSetting::active();
    $themeLogo = $themeSetting && $themeSetting->logo_path
        ? asset('storage/' . $themeSetting->logo_path)
        : $logo;
    $themeBrandName = $themeSetting->brand_name ?? $title;
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
         x-data="{ mlmOpen: {{ request()->routeIs('admin.mlm.*') || request()->routeIs('admin.mlm-prospects.*') ? 'true' : 'false' }} }">
        {{-- Dashboard --}}
        <a href="{{ route('admin.dashboard') }}"
           @click="$store.sidebar.closeOnMenuClick()"
           class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.dashboard') ? 'bg-gradient-to-r from-blue-500 to-purple-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
            <i class="fas fa-home w-5 text-center drop-shadow"></i>
            <span x-show="$store.sidebar.shouldExpand" x-transition class="font-medium drop-shadow whitespace-nowrap">แดชบอร์ด</span>
        </a>

        {{-- Users & Roles (Collapsible Menu) 👥 --}}
        <div class="space-y-1"
             x-data="{ usersOpen: {{ request()->routeIs('admin.users.*') || request()->routeIs('admin.roles.*') ? 'true' : 'false' }} }">
            {{-- Users Header Button --}}
            <button @click="usersOpen = !usersOpen"
                    type="button"
                    class="w-full flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.users.*') || request()->routeIs('admin.roles.*') ? 'bg-gradient-to-r from-blue-500 to-purple-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
                <i class="fas fa-users w-5 text-center drop-shadow"></i>
                <span x-show="$store.sidebar.shouldExpand" x-transition class="flex-1 text-left font-medium drop-shadow whitespace-nowrap">ผู้ใช้งาน</span>
                <i x-show="$store.sidebar.shouldExpand && usersOpen" x-transition class="fas fa-chevron-down text-xs drop-shadow"></i>
                <i x-show="$store.sidebar.shouldExpand && !usersOpen" x-transition class="fas fa-chevron-right text-xs drop-shadow"></i>
            </button>

            {{-- Users Submenu --}}
            <div x-show="usersOpen" x-collapse x-cloak class="ml-8 space-y-1">
                {{-- Users List --}}
                <a href="{{ route('admin.users.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.users.index') || request()->routeIs('admin.users.show') || request()->routeIs('admin.users.edit') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-list w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">รายชื่อผู้ใช้</span>
                </a>

                {{-- Roles & Permissions ⭐ สำคัญ! --}}
                <a href="{{ route('admin.roles.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.roles.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-user-shield w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">บทบาทและสิทธิ์</span>
                </a>
            </div>
        </div>

        {{-- MLM System (Collapsible Menu) --}}
        <div class="space-y-1">
            {{-- MLM Header Button --}}
            <button @click="mlmOpen = !mlmOpen"
                    type="button"
                    class="w-full flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.mlm.*') || request()->routeIs('admin.mlm-prospects.*') ? 'bg-gradient-to-r from-purple-500 to-pink-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
                <i class="fas fa-sitemap w-5 text-center drop-shadow"></i>
                <span x-show="$store.sidebar.shouldExpand" x-transition class="flex-1 text-left font-medium drop-shadow whitespace-nowrap">MLM System</span>
                <i x-show="$store.sidebar.shouldExpand && mlmOpen" x-transition class="fas fa-chevron-down text-xs drop-shadow"></i>
                <i x-show="$store.sidebar.shouldExpand && !mlmOpen" x-transition class="fas fa-chevron-right text-xs drop-shadow"></i>
            </button>

            {{-- MLM Submenu --}}
            <div x-show="mlmOpen" x-collapse x-cloak class="ml-8 space-y-1">
                {{-- Dashboard MLM --}}
                <a href="{{ route('admin.mlm.reports.dashboard') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.mlm.reports.dashboard') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-chart-pie w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Dashboard</span>
                </a>

                {{-- สมาชิก MLM --}}
                <a href="{{ route('admin.mlm.members.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.mlm.members.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-users-cog w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">สมาชิก</span>
                </a>

                {{-- แผน MLM --}}
                <a href="{{ route('admin.mlm.plans.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.mlm.plans.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-layer-group w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">แผน MLM</span>
                </a>

                {{-- คอมมิชชั่น MLM --}}
                <a href="{{ route('admin.mlm.commissions.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.mlm.commissions.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-money-bill-wave w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">คอมมิชชั่น</span>
                </a>

                {{-- Product PV --}}
                <a href="{{ route('admin.mlm.product-pv.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.mlm.product-pv.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-tags w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Product PV</span>
                </a>

                {{-- รายงาน --}}
                <a href="{{ route('admin.mlm.reports.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.mlm.reports.*') && !request()->routeIs('admin.mlm.reports.dashboard') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-chart-line w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">รายงาน</span>
                </a>

                {{-- Genealogy --}}
                <a href="{{ route('admin.mlm.genealogy.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.mlm.genealogy.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-project-diagram w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Genealogy</span>
                </a>

                {{-- Ranks ⭐ สำคัญ! --}}
                <a href="{{ route('admin.ranks.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.ranks.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-medal w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">ระดับสมาชิก</span>
                </a>

                {{-- Prospects --}}
                <a href="{{ route('admin.mlm-prospects.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.mlm-prospects.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-user-plus w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Prospects</span>
                </a>

                {{-- เครื่องคิดเลข --}}
                <a href="{{ route('admin.mlm.calculator') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.mlm.calculator') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-calculator w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">เครื่องคิดเลข</span>
                </a>

                {{-- ตัวอย่าง Placement --}}
                <a href="{{ route('admin.mlm.placement-examples') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.mlm.placement-examples') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-lightbulb w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">ตัวอย่าง Placement</span>
                </a>

                {{-- ตั้งค่า MLM --}}
                <a href="{{ route('admin.mlm.settings.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.mlm.settings.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-cogs w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">ตั้งค่า</span>
                </a>
            </div>
        </div>

        {{-- Wallet & Finance (Collapsible Menu) 💰 --}}
        <div class="space-y-1"
             x-data="{ walletOpen: {{ request()->routeIs('admin.wallet.*') || request()->routeIs('admin.withdrawals.*') || request()->routeIs('admin.wallet-settings.*') || request()->routeIs('admin.payment-gateways.*') || request()->routeIs('admin.cashback.*') || request()->routeIs('admin.investments.*') || request()->routeIs('admin.nfc-*') ? 'true' : 'false' }} }">
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
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.wallet.index') || request()->routeIs('admin.wallet.transactions') || request()->routeIs('admin.wallet.logs') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-tachometer-alt w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Dashboard</span>
                </a>

                {{-- Withdrawals ⭐ สำคัญมาก! --}}
                <a href="{{ route('admin.withdrawals.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
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
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.wallet-settings.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-cog w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">ตั้งค่า Wallet</span>
                </a>

                {{-- Payment Gateways --}}
                <a href="{{ route('admin.payment-gateways.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.payment-gateways.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-credit-card w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Payment Gateways</span>
                </a>

                {{-- Cashback --}}
                <a href="{{ route('admin.cashback.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.cashback.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-gift w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Cashback</span>
                </a>

                {{-- Investments --}}
                <a href="{{ route('admin.investments.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
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
                           class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-xs {{ request()->routeIs('admin.nfc-cards.*') ? 'bg-white/20 text-white font-bold' : 'text-white/70 hover:bg-white/10 hover:text-white' }}">
                            <i class="fas fa-id-card w-4 text-center"></i>
                            <span x-show="$store.sidebar.shouldExpand" x-transition class="whitespace-nowrap">บัตร NFC</span>
                        </a>

                        <a href="{{ route('admin.nfc-readers.index') }}"
                           @click="$store.sidebar.closeOnMenuClick()"
                           class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-xs {{ request()->routeIs('admin.nfc-readers.*') ? 'bg-white/20 text-white font-bold' : 'text-white/70 hover:bg-white/10 hover:text-white' }}">
                            <i class="fas fa-barcode w-4 text-center"></i>
                            <span x-show="$store.sidebar.shouldExpand" x-transition class="whitespace-nowrap">เครื่องอ่าน</span>
                        </a>

                        <a href="{{ route('admin.nfc-transactions.index') }}"
                           @click="$store.sidebar.closeOnMenuClick()"
                           class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-xs {{ request()->routeIs('admin.nfc-transactions.*') ? 'bg-white/20 text-white font-bold' : 'text-white/70 hover:bg-white/10 hover:text-white' }}">
                            <i class="fas fa-exchange-alt w-4 text-center"></i>
                            <span x-show="$store.sidebar.shouldExpand" x-transition class="whitespace-nowrap">ธุรกรรม</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- TPIX Blockchain --}}
        <a href="{{ route('admin.tpix.dashboard') }}"
           @click="$store.sidebar.closeOnMenuClick()"
           class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.tpix.*') ? 'bg-gradient-to-r from-cyan-500 to-blue-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
            <i class="fas fa-cube w-5 text-center drop-shadow"></i>
            <span x-show="$store.sidebar.shouldExpand" x-transition class="font-medium drop-shadow whitespace-nowrap">TPIX Blockchain</span>
        </a>

        {{-- Token Management --}}
        <a href="{{ route('admin.tokens.index') }}"
           @click="$store.sidebar.closeOnMenuClick()"
           class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.tokens.*') ? 'bg-gradient-to-r from-yellow-500 to-orange-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
            <i class="fas fa-coins w-5 text-center drop-shadow"></i>
            <span x-show="$store.sidebar.shouldExpand" x-transition class="font-medium drop-shadow whitespace-nowrap">Token Management</span>
        </a>

        {{-- LINE System (Collapsible Menu) 🆕 --}}
        <div class="space-y-1"
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
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.line-oa.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-bullhorn w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">LINE OA</span>
                </a>

                {{-- LINE Bot AI --}}
                <a href="{{ route('admin.line-bot.ai.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.line-bot.ai.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-robot w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Bot AI</span>
                </a>

                {{-- Rich Menu --}}
                <a href="{{ route('admin.line-bot.rich-menu.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.line-bot.rich-menu.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-th-large w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Rich Menu</span>
                </a>

                {{-- Flex Messages --}}
                <a href="{{ route('admin.line-bot.flex.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.line-bot.flex.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-layer-group w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Flex Messages</span>
                </a>

                {{-- LINE Membership Signup 🆕 --}}
                <a href="{{ route('admin.line-membership-signup.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.line-membership-signup.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-user-plus w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">สมัครสมาชิก</span>
                </a>

                {{-- Conversations --}}
                <a href="{{ route('admin.line-bot.ai.conversations') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.line-bot.ai.conversations*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-comments w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">การสนทนา</span>
                </a>

                {{-- Broadcast Messages --}}
                <a href="{{ route('admin.line-bot.broadcast.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.line-bot.broadcast.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-bullseye w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Broadcast</span>
                </a>

                {{-- LINE Avatars --}}
                <a href="{{ route('admin.line-bot.avatars.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.line-bot.avatars.*') || request()->routeIs('admin.line-bot.avatar.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-user-circle w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Avatars</span>
                </a>

                {{-- Chat Widget --}}
                <a href="{{ route('admin.line-bot.chat-widget.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.line-bot.chat-widget.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-comment-dots w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Chat Widget</span>
                </a>

                {{-- Analytics --}}
                <a href="{{ route('admin.line-bot.ai.analytics') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.line-bot.ai.analytics') || request()->routeIs('admin.line-analytics.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-chart-line w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Analytics</span>
                </a>
            </div>
        </div>

        {{-- AI Core - ระบบควบคุม AI แบบรวมศูนย์ 🆕 --}}
        <div class="space-y-1"
             x-data="{ aiCoreOpen: {{ request()->routeIs('admin.ai-core.*') ? 'true' : 'false' }} }">
            {{-- AI Core Header Button --}}
            <button @click="aiCoreOpen = !aiCoreOpen"
                    type="button"
                    class="w-full flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.ai-core.*') ? 'bg-gradient-to-r from-purple-500 to-indigo-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
                <i class="fas fa-brain w-5 text-center drop-shadow"></i>
                <span x-show="$store.sidebar.shouldExpand" x-transition class="flex-1 text-left font-medium drop-shadow whitespace-nowrap">AI Core</span>
                <i x-show="$store.sidebar.shouldExpand && aiCoreOpen" x-transition class="fas fa-chevron-down text-xs drop-shadow"></i>
                <i x-show="$store.sidebar.shouldExpand && !aiCoreOpen" x-transition class="fas fa-chevron-right text-xs drop-shadow"></i>
            </button>

            {{-- AI Core Submenu --}}
            <div x-show="aiCoreOpen" x-collapse x-cloak class="ml-8 space-y-1">
                {{-- Dashboard --}}
                <a href="{{ route('admin.ai-core.dashboard') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.ai-core.dashboard') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-tachometer-alt w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Dashboard</span>
                </a>

                {{-- Features --}}
                <a href="{{ route('admin.ai-core.features.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.ai-core.features.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-puzzle-piece w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Features</span>
                </a>

                {{-- Tenants --}}
                <a href="{{ route('admin.ai-core.tenants.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.ai-core.tenants.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-users w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Tenants</span>
                </a>

                {{-- Quotas --}}
                <a href="{{ route('admin.ai-core.quotas.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.ai-core.quotas.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-gauge-high w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Quotas</span>
                </a>

                {{-- Schedules --}}
                <a href="{{ route('admin.ai-core.schedules.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.ai-core.schedules.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-clock w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Schedules</span>
                </a>

                {{-- Alerts --}}
                <a href="{{ route('admin.ai-core.alerts.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.ai-core.alerts.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-bell w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Alerts</span>
                </a>

                {{-- Analytics --}}
                <a href="{{ route('admin.ai-core.analytics.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.ai-core.analytics.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-chart-bar w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Analytics</span>
                </a>

                {{-- Settings --}}
                <a href="{{ route('admin.ai-core.settings.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.ai-core.settings.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-cog w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Settings</span>
                </a>
            </div>
        </div>

        {{-- Security & Monitoring 🔒 --}}
        <div class="space-y-1"
             x-data="{ securityOpen: {{ request()->routeIs('admin.security.*') || request()->routeIs('admin.analytics.*') || request()->routeIs('admin.advanced-analytics.*') ? 'true' : 'false' }} }">
            {{-- Security Header Button --}}
            <button @click="securityOpen = !securityOpen"
                    type="button"
                    class="w-full flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.security.*') || request()->routeIs('admin.analytics.*') || request()->routeIs('admin.advanced-analytics.*') ? 'bg-gradient-to-r from-red-500 to-pink-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
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
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.security.index') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-tachometer-alt w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Dashboard</span>
                </a>

                {{-- IP Blocking & Rate Limiting --}}
                <a href="{{ route('admin.security.analytics') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.security.analytics') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-ban w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">IP & Rate Limit</span>
                </a>

                {{-- Threat Intelligence --}}
                <a href="{{ route('admin.security.threat-intelligence') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.security.threat-intelligence') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-brain w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Threat Intelligence</span>
                </a>

                {{-- System Analytics --}}
                <a href="{{ route('admin.analytics.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.analytics.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-chart-line w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">System Analytics</span>
                </a>

                {{-- Advanced Analytics (Phase 3 - Not Yet Implemented) --}}
                {{--
                <a href="{{ route('admin.advanced-analytics.dashboard') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.advanced-analytics.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-chart-pie w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Advanced Analytics</span>
                </a>
                --}}
            </div>
        </div>

        {{-- Communication (Email & Notifications) 📧 --}}
        <div class="space-y-1"
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
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.email.index') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-tachometer-alt w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Email Dashboard</span>
                </a>

                {{-- Email Providers --}}
                <a href="{{ route('admin.email.providers') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.email.providers') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-server w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Providers</span>
                </a>

                {{-- Email Templates --}}
                <a href="{{ route('admin.email.templates') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.email.templates') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-file-alt w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Templates</span>
                </a>

                {{-- Email Logs --}}
                <a href="{{ route('admin.email.logs') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.email.logs') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-history w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Logs</span>
                </a>

                {{-- Notification Management --}}
                <a href="{{ route('admin.notifications.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.notifications.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-bell w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Notifications</span>
                </a>

                {{-- Notification Templates --}}
                <a href="{{ route('admin.notification-templates.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.notification-templates.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-clipboard-list w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Notification Templates</span>
                </a>
            </div>
        </div>

        {{-- AI & Automation (Extended) 🤖 --}}
        <div class="space-y-1"
             x-data="{ aiAutoOpen: {{ request()->routeIs('admin.ai-providers.*') || request()->routeIs('admin.ai-installation.*') || request()->routeIs('admin.ai-monitoring.*') ? 'true' : 'false' }} }">
            {{-- AI Automation Header Button --}}
            <button @click="aiAutoOpen = !aiAutoOpen"
                    type="button"
                    class="w-full flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.ai-providers.*') || request()->routeIs('admin.ai-installation.*') || request()->routeIs('admin.ai-monitoring.*') ? 'bg-gradient-to-r from-violet-500 to-purple-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
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
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.ai-providers.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-key w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">API Providers</span>
                </a>

                {{-- AI Installation --}}
                <a href="{{ route('admin.ai-installation.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.ai-installation.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-download w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Installations</span>
                </a>

                {{-- AI Monitoring --}}
                <a href="{{ route('admin.ai-monitoring.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.ai-monitoring.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-chart-area w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Monitoring</span>
                </a>
            </div>
        </div>

        {{-- HRM (Human Resource Management) 👔 --}}
        <div class="space-y-1"
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
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.hrm.dashboard') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-tachometer-alt w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Dashboard</span>
                </a>

                <a href="{{ route('admin.hrm.employees.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.hrm.employees.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-users w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">พนักงาน</span>
                </a>

                <a href="{{ route('admin.hrm.attendance.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.hrm.attendance.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-calendar-check w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">เช็คชื่อ</span>
                </a>

                <a href="{{ route('admin.hrm.payroll.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.hrm.payroll.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-money-check-alt w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">เงินเดือน</span>
                </a>
            </div>
        </div>

        {{-- Accounting 💼 --}}
        <div class="space-y-1"
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
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.accounting.dashboard') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-tachometer-alt w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Dashboard</span>
                </a>

                <a href="{{ route('admin.accounting.invoices.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.accounting.invoices.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-file-invoice w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">ใบแจ้งหนี้</span>
                </a>

                <a href="{{ route('admin.accounting.reports.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.accounting.reports.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-chart-pie w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">รายงาน</span>
                </a>
            </div>
        </div>

        {{-- POS (Point of Sale) 🛍️ --}}
        <div class="space-y-1"
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
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.pos.dashboard') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-tachometer-alt w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Dashboard</span>
                </a>

                <a href="{{ route('admin.pos.devices.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.pos.devices.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-tablet-alt w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">เครื่อง POS</span>
                </a>

                <a href="{{ route('admin.pos.transactions.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.pos.transactions.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-receipt w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">ธุรกรรม</span>
                </a>
            </div>
        </div>

        {{-- Hotels 🏨 --}}
        <div class="space-y-1"
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
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.hotels.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-building w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">โรงแรม</span>
                </a>

                <a href="{{ route('admin.hotel-owners.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.hotel-owners.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-user-circle w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">เจ้าของโรงแรม</span>
                </a>
            </div>
        </div>

        {{-- Content Management 📄 --}}
        <div class="space-y-1"
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
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.pages.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-pager w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Pages (CMS)</span>
                </a>

                <a href="{{ route('admin.articles.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.articles.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-newspaper w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">บทความ</span>
                </a>
            </div>
        </div>

        {{-- KYC Verification 🆕 --}}
        <a href="{{ route('admin.kyc.index') }}"
           @click="$store.sidebar.closeOnMenuClick()"
           class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.kyc.*') ? 'bg-gradient-to-r from-indigo-500 to-purple-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
            <i class="fas fa-id-card w-5 text-center drop-shadow"></i>
            <span x-show="$store.sidebar.shouldExpand" x-transition class="font-medium drop-shadow whitespace-nowrap">KYC Verification</span>
        </a>

        {{-- Support Tickets (Collapsible Menu) 🆕 --}}
        <div class="space-y-1"
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
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.tickets.index') || request()->routeIs('admin.tickets.show') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-ticket w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Tickets ทั้งหมด</span>
                </a>

                {{-- Analytics --}}
                <a href="{{ route('admin.tickets.analytics') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.tickets.analytics') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-chart-line w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Analytics</span>
                </a>

                {{-- Ratings --}}
                <a href="{{ route('admin.tickets.ratings') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.tickets.ratings') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-star w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">ความพึงพอใจ</span>
                </a>

                {{-- Categories --}}
                <a href="{{ route('admin.tickets.categories.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.tickets.categories.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-folder w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">หมวดหมู่</span>
                </a>

                {{-- Canned Responses --}}
                <a href="{{ route('admin.tickets.canned-responses.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.tickets.canned-responses.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-comment-dots w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">ข้อความสำเร็จรูป</span>
                </a>

                {{-- SLA Policies --}}
                <a href="{{ route('admin.tickets.sla-policies.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.tickets.sla-policies.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-clock w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">SLA Policies</span>
                </a>

                {{-- Assignment Rules --}}
                <a href="{{ route('admin.tickets.assignment-rules.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.tickets.assignment-rules.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-user-check w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">กฎการมอบหมาย</span>
                </a>

                {{-- KB Articles --}}
                <a href="{{ route('admin.tickets.kb-articles.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.tickets.kb-articles.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-book w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Knowledge Base</span>
                </a>

                {{-- Settings --}}
                <a href="{{ route('admin.tickets.settings') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.tickets.settings') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-cog w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">ตั้งค่า</span>
                </a>
            </div>
        </div>

        {{-- ⚠️ TODO: AI & Bots Automation Menu - ยังไม่มี routes และ controllers
             Routes ที่ต้องสร้าง:
             - admin.trading-bot.dashboard (TradingBotController)
             - admin.bot-automation.dashboard (BotAutomationController)
             - admin.ai-bots.marketplace (AiBotController@marketplace)
             - admin.ai-installations.index (AiInstallationController)
             - admin.ai-rentals.index (AiRentalController)

             ปลดคอมเมนต์เมื่อสร้าง routes และ controllers เรียบร้อยแล้ว
        --}}
        {{-- <div class="space-y-1"
             x-data="{ aiBotsOpen: {{ request()->routeIs('admin.trading-bot.*') || request()->routeIs('admin.bot-automation.*') || request()->routeIs('admin.ai-bots.*') ? 'true' : 'false' }} }">
            <button @click="aiBotsOpen = !aiBotsOpen"
                    type="button"
                    class="w-full flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.trading-bot.*') || request()->routeIs('admin.bot-automation.*') || request()->routeIs('admin.ai-bots.*') ? 'bg-gradient-to-r from-purple-500 to-indigo-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
                <i class="fas fa-brain w-5 text-center drop-shadow"></i>
                <span x-show="$store.sidebar.shouldExpand" x-transition class="flex-1 text-left font-medium drop-shadow whitespace-nowrap">AI & Bots</span>
                <i x-show="(sidebarOpen || hovered) && aiBotsOpen" x-transition class="fas fa-chevron-down text-xs drop-shadow"></i>
                <i x-show="(sidebarOpen || hovered) && !aiBotsOpen" x-transition class="fas fa-chevron-right text-xs drop-shadow"></i>
            </button>

            <div x-show="aiBotsOpen" x-collapse x-cloak class="ml-8 space-y-1">
                <a href="{{ route('admin.trading-bot.dashboard') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.trading-bot.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-chart-line w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Trading Bot</span>
                </a>

                <a href="{{ route('admin.bot-automation.dashboard') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.bot-automation.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-robot w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">Bot Automation</span>
                </a>

                <a href="{{ route('admin.ai-bots.marketplace') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.ai-bots.marketplace*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-store w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">AI Marketplace</span>
                </a>

                <a href="{{ route('admin.ai-bots.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.ai-bots.index') || request()->routeIs('admin.ai-bots.manage') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-user-robot w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">AI Bot Profiles</span>
                </a>

                <a href="{{ route('admin.ai-installations.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.ai-installations.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-download w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">AI Installations</span>
                </a>

                <a href="{{ route('admin.ai-rentals.index') }}"
                   @click="$store.sidebar.closeOnMenuClick()"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all text-sm {{ request()->routeIs('admin.ai-rentals.*') ? 'bg-white/30 text-white font-bold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-calendar-check w-4 text-center drop-shadow"></i>
                    <span x-show="$store.sidebar.shouldExpand" x-transition class="drop-shadow whitespace-nowrap">AI Rentals</span>
                </a>
            </div>
        </div> --}}

        {{-- AI Bot Profiles (Active Route Only) --}}
        <a href="{{ route('admin.ai-bots.index') }}"
           class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.ai-bots.*') ? 'bg-gradient-to-r from-purple-500 to-indigo-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
            <i class="fas fa-user-robot w-5 text-center drop-shadow"></i>
            <span x-show="$store.sidebar.shouldExpand" x-transition class="font-medium drop-shadow whitespace-nowrap">AI Bot Profiles</span>
        </a>

        {{-- Products --}}
        <a href="{{ route('admin.ecommerce.products.index') }}"
           class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.ecommerce.products.*') ? 'bg-gradient-to-r from-blue-500 to-purple-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
            <i class="fas fa-box w-5 text-center drop-shadow"></i>
            <span x-show="$store.sidebar.shouldExpand" x-transition class="font-medium drop-shadow whitespace-nowrap">สินค้า</span>
        </a>

        {{-- Orders --}}
        <a href="{{ route('admin.ecommerce.orders.index') }}"
           class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.ecommerce.orders.*') ? 'bg-gradient-to-r from-blue-500 to-purple-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
            <i class="fas fa-shopping-cart w-5 text-center drop-shadow"></i>
            <span x-show="$store.sidebar.shouldExpand" x-transition class="font-medium drop-shadow whitespace-nowrap">คำสั่งซื้อ</span>
        </a>

        {{-- Reports --}}
        <a href="{{ route('admin.ecommerce.reports') }}"
           class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.ecommerce.reports') ? 'bg-gradient-to-r from-blue-500 to-purple-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
            <i class="fas fa-chart-bar w-5 text-center drop-shadow"></i>
            <span x-show="$store.sidebar.shouldExpand" x-transition class="font-medium drop-shadow whitespace-nowrap">รายงาน</span>
        </a>

        {{-- Divider --}}
        <div x-show="$store.sidebar.shouldExpand" x-transition class="border-t border-white/30 my-4"></div>

        {{-- Settings --}}
        <a href="{{ route('admin.settings.index') }}"
           class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.settings.index') ? 'bg-gradient-to-r from-blue-500 to-purple-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
            <i class="fas fa-cog w-5 text-center drop-shadow"></i>
            <span x-show="$store.sidebar.shouldExpand" x-transition class="font-medium drop-shadow whitespace-nowrap">ตั้งค่า</span>
        </a>

        {{-- Site Settings (โลโก้, SEO, Social Media) --}}
        <a href="{{ route('admin.site-settings.index') }}"
           class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all transform {{ request()->routeIs('admin.site-settings.*') ? 'bg-gradient-to-r from-blue-500 to-purple-600 text-white shadow-lg scale-105' : 'glass-neu text-white/90 hover:bg-white/20 hover:scale-105' }}">
            <i class="fas fa-palette w-5 text-center drop-shadow"></i>
            <span x-show="$store.sidebar.shouldExpand" x-transition class="font-medium drop-shadow whitespace-nowrap">ตั้งค่าเว็บไซต์</span>
        </a>

        {{-- Arrow X Theme Customizer ⭐ NEW --}}
        <a href="{{ route('admin.arrow-x-theme.index') }}"
           @click="$store.sidebar.closeOnMenuClick()"
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
