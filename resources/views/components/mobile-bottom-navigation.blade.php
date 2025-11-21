{{--
    Mobile Bottom Navigation Component - แบบแอพมือถือ

    Bottom navigation bar แบบ modern app สำหรับมือถือ
    พร้อม glassmorphism, smooth transitions และ active states

    Props:
    - type: ประเภท (user, seller, admin)
--}}

@props(['type' => 'user'])

@php
    use App\Models\Setting;

    /**
     * กำหนดเมนูลัดสำหรับแต่ละประเภทผู้ใช้
     *
     * รองรับ 5 เมนูหลักสำหรับมือถือ
     */
    $navigationItems = match($type) {
        'user' => [
            [
                'label' => 'หน้าแรก',
                'icon' => 'fas fa-home',
                'url' => route('user.dashboard'),
                'active' => request()->routeIs('user.dashboard'),
            ],
            [
                'label' => 'กระเป๋าเงิน',
                'icon' => 'fas fa-wallet',
                'url' => route('user.wallet.index'),
                'active' => request()->routeIs('user.wallet.*'),
            ],
            [
                'label' => 'คอมมิชชั่น',
                'icon' => 'fas fa-dollar-sign',
                'url' => route('user.commissions'),
                'active' => request()->routeIs('user.commissions'),
            ],
            [
                'label' => 'โปรไฟล์',
                'icon' => 'fas fa-user',
                'url' => route('user.profile'),
                'active' => request()->routeIs('user.profile'),
            ],
            [
                'label' => 'เมนู',
                'icon' => 'fas fa-bars',
                'url' => '#',
                'active' => false,
                'action' => 'toggleSidebar', // เปิด sidebar
            ],
        ],
        'seller' => [
            [
                'label' => 'หน้าแรก',
                'icon' => 'fas fa-home',
                'url' => route('seller.dashboard'),
                'active' => request()->routeIs('seller.dashboard'),
            ],
            [
                'label' => 'สินค้า',
                'icon' => 'fas fa-box',
                'url' => route('seller.products.index'),
                'active' => request()->routeIs('seller.products.*'),
            ],
            [
                'label' => 'คำสั่งซื้อ',
                'icon' => 'fas fa-shopping-cart',
                'url' => route('seller.orders.index'),
                'active' => request()->routeIs('seller.orders.*'),
            ],
            [
                'label' => 'รายงาน',
                'icon' => 'fas fa-chart-line',
                'url' => route('seller.analytics'),
                'active' => request()->routeIs('seller.analytics'),
            ],
            [
                'label' => 'เมนู',
                'icon' => 'fas fa-bars',
                'url' => '#',
                'active' => false,
                'action' => 'toggleSidebar',
            ],
        ],
        'admin' => [
            [
                'label' => 'แดชบอร์ด',
                'icon' => 'fas fa-tachometer-alt',
                'url' => route('admin.dashboard'),
                'active' => request()->routeIs('admin.dashboard'),
            ],
            [
                'label' => 'ผู้ใช้',
                'icon' => 'fas fa-users',
                'url' => route('admin.users.index'),
                'active' => request()->routeIs('admin.users.*'),
            ],
            [
                'label' => 'รายงาน',
                'icon' => 'fas fa-chart-bar',
                'url' => route('admin.reports.index'),
                'active' => request()->routeIs('admin.reports.*'),
            ],
            [
                'label' => 'ตั้งค่า',
                'icon' => 'fas fa-cog',
                'url' => route('admin.settings.index'),
                'active' => request()->routeIs('admin.settings.*'),
            ],
            [
                'label' => 'เมนู',
                'icon' => 'fas fa-bars',
                'url' => '#',
                'active' => false,
                'action' => 'toggleSidebar',
            ],
        ],
        default => [],
    };

    /**
     * ดึง theme colors จาก settings
     */
    $primaryStart = Setting::get('theme_primary_start', '#3B82F6');
    $primaryEnd = Setting::get('theme_primary_end', '#1D4ED8');
@endphp

<!-- Mobile Bottom Navigation - แสดงเฉพาะบนมือถือและแท็บเล็ต -->
<nav
    x-data="{ showNav: true, lastScroll: 0 }"
    x-init="
        // ซ่อน nav เมื่อ scroll ลง แสดงเมื่อ scroll ขึ้น
        window.addEventListener('scroll', () => {
            let currentScroll = window.pageYOffset;
            if (currentScroll > lastScroll && currentScroll > 100) {
                showNav = false;
            } else {
                showNav = true;
            }
            lastScroll = currentScroll;
        });
    "
    x-show="showNav"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="translate-y-full"
    x-transition:enter-end="translate-y-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="translate-y-0"
    x-transition:leave-end="translate-y-full"
    class="fixed bottom-0 left-0 right-0 z-[100] lg:hidden"
    style="padding-bottom: env(safe-area-inset-bottom, 0px);">

    <!-- Glassmorphism Background -->
    <div class="relative bg-white/90 dark:bg-gray-900/90 backdrop-blur-xl border-t border-gray-200/50 dark:border-gray-700/50 shadow-2xl">
        <!-- Gradient Accent Line -->
        <div class="absolute top-0 left-0 right-0 h-0.5 bg-gradient-to-r from-purple-500 via-blue-500 to-purple-500"></div>

        <!-- Navigation Items Container -->
        <div class="max-w-screen-xl mx-auto">
            <div class="grid grid-cols-5 gap-0">
                @foreach($navigationItems as $index => $item)
                    @if(isset($item['action']) && $item['action'] === 'toggleSidebar')
                        {{-- ปุ่มเปิดเมนู - เชื่อมกับ sidebar --}}
                        <button
                            type="button"
                            @click="
                                // เรียกใช้ toggleSidebar จาก arrowXSidebar component
                                const sidebarEl = document.querySelector('[x-data*=arrowXSidebar]');
                                if (sidebarEl) {
                                    const component = Alpine.$data(sidebarEl);
                                    if (component && typeof component.toggleSidebar === 'function') {
                                        component.toggleSidebar();
                                    }
                                }
                            "
                            class="flex flex-col items-center justify-center py-3 px-2 group relative overflow-hidden
                                   text-gray-600 dark:text-gray-400 hover:text-purple-600 dark:hover:text-purple-400
                                   active:scale-95 transition-all duration-200">

                            <!-- Active Background -->
                            <div class="absolute inset-0 bg-gradient-to-br from-purple-500/10 to-blue-500/10 opacity-0 group-hover:opacity-100 transition-opacity duration-200"></div>

                            <!-- Icon -->
                            <div class="relative mb-1 transform group-hover:scale-110 transition-transform duration-200">
                                <i class="{{ $item['icon'] }} text-xl"></i>

                                <!-- Pulse Effect on Hover -->
                                <div class="absolute inset-0 bg-purple-500 rounded-full opacity-0 group-hover:opacity-20 group-hover:animate-ping"></div>
                            </div>

                            <!-- Label -->
                            <span class="text-[10px] font-medium relative">{{ $item['label'] }}</span>
                        </button>
                    @else
                        {{-- ลิงก์ปกติ --}}
                        <a
                            href="{{ $item['url'] }}"
                            class="flex flex-col items-center justify-center py-3 px-2 group relative overflow-hidden
                                   {{ $item['active']
                                       ? 'text-purple-600 dark:text-purple-400'
                                       : 'text-gray-600 dark:text-gray-400 hover:text-purple-600 dark:hover:text-purple-400'
                                   }}
                                   active:scale-95 transition-all duration-200">

                            <!-- Active Indicator -->
                            @if($item['active'])
                                <div class="absolute top-0 left-1/2 -translate-x-1/2 w-12 h-1 bg-gradient-to-r from-purple-500 to-blue-500 rounded-b-full"></div>
                                <div class="absolute inset-0 bg-gradient-to-br from-purple-500/10 to-blue-500/10"></div>
                            @endif

                            <!-- Hover Background -->
                            <div class="absolute inset-0 bg-gradient-to-br from-purple-500/10 to-blue-500/10 opacity-0 group-hover:opacity-100 transition-opacity duration-200"></div>

                            <!-- Icon -->
                            <div class="relative mb-1 transform {{ $item['active'] ? 'scale-110' : '' }} group-hover:scale-110 transition-transform duration-200">
                                <i class="{{ $item['icon'] }} text-xl"></i>

                                <!-- Active Glow -->
                                @if($item['active'])
                                    <div class="absolute inset-0 bg-purple-500 rounded-full blur-sm opacity-30"></div>
                                @endif

                                <!-- Pulse Effect on Hover -->
                                <div class="absolute inset-0 bg-purple-500 rounded-full opacity-0 group-hover:opacity-20 group-hover:animate-ping"></div>
                            </div>

                            <!-- Label -->
                            <span class="text-[10px] font-medium relative {{ $item['active'] ? 'font-bold' : '' }}">
                                {{ $item['label'] }}
                            </span>

                            <!-- Notification Badge (ถ้ามี) -->
                            @if(isset($item['badge']) && $item['badge'] > 0)
                                <span class="absolute top-2 right-[calc(50%-0.5rem)] min-w-[18px] h-[18px] flex items-center justify-center bg-red-500 text-white text-[9px] font-bold rounded-full px-1 shadow-lg">
                                    {{ $item['badge'] > 99 ? '99+' : $item['badge'] }}
                                </span>
                            @endif
                        </a>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
</nav>

<style>
    /**
     * Safe Area Inset Support
     *
     * รองรับ notch และ home indicator บน iOS/Android
     */
    @supports (padding-bottom: env(safe-area-inset-bottom)) {
        .mobile-bottom-nav {
            padding-bottom: env(safe-area-inset-bottom);
        }
    }

    /**
     * Prevent Content Overlap
     *
     * เพิ่ม padding-bottom ให้ content เพื่อไม่ให้ถูกบัง bottom nav
     */
    @media (max-width: 1023px) {
        body {
            padding-bottom: calc(64px + env(safe-area-inset-bottom, 0px));
        }
    }

    /**
     * Smooth Transitions
     */
    .mobile-bottom-nav a,
    .mobile-bottom-nav button {
        -webkit-tap-highlight-color: transparent;
    }
</style>

<script>
    /**
     * Mobile Bottom Navigation Utilities
     *
     * Helper functions สำหรับ bottom navigation
     */
    document.addEventListener('alpine:init', () => {
        // ตรวจสอบว่า Alpine.js loaded แล้ว
        console.log('Mobile Bottom Navigation initialized');
    });
</script>
