{{--
    Mobile Bottom Navigation Component - V3 Modern Design

    Bottom navigation bar แบบ modern app สำหรับมือถือ
    ออกแบบใหม่ให้สวยงาม มีมิติ พร้อม QR Code button กลาง

    Props:
    - type: ประเภท (user, seller, admin)
--}}

@props(['type' => 'user'])

@php
    use App\Models\Setting;

    /**
     * กำหนดเมนูลัดสำหรับแต่ละประเภทผู้ใช้
     * User: หน้าแรก, คอมมิชชั่น, [QR Code ตรงกลาง], กระเป๋าเงิน, โปรไฟล์
     * ไม่มีเมนู hamburger แล้วเพราะมี top bar
     */
    $navigationItems = match($type) {
        'user' => [
            'left' => [
                [
                    'label' => 'หน้าแรก',
                    'icon' => 'fas fa-home',
                    'url' => route('user.dashboard'),
                    'active' => request()->routeIs('user.dashboard'),
                ],
                [
                    'label' => 'คอมมิชชั่น',
                    'icon' => 'fas fa-coins',
                    'url' => route('user.commissions'),
                    'active' => request()->routeIs('user.commissions'),
                ],
            ],
            'center' => [
                'label' => 'เชิญเพื่อน',
                'icon' => 'fas fa-qrcode',
                'url' => route('user.mlm.referral'),
                'active' => request()->routeIs('user.mlm.referral') || request()->routeIs('user.referral'),
            ],
            'right' => [
                [
                    'label' => 'กระเป๋าเงิน',
                    'icon' => 'fas fa-wallet',
                    'url' => route('user.wallet.index'),
                    'active' => request()->routeIs('user.wallet.*'),
                ],
                [
                    'label' => 'โปรไฟล์',
                    'icon' => 'fas fa-user-circle',
                    'url' => route('user.profile'),
                    'active' => request()->routeIs('user.profile'),
                ],
            ],
        ],
        'seller' => [
            'left' => [
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
            ],
            'center' => [
                'label' => 'เพิ่มสินค้า',
                'icon' => 'fas fa-plus',
                'url' => route('seller.products.create'),
                'active' => request()->routeIs('seller.products.create'),
            ],
            'right' => [
                [
                    'label' => 'คำสั่งซื้อ',
                    'icon' => 'fas fa-shopping-bag',
                    'url' => route('seller.orders.index'),
                    'active' => request()->routeIs('seller.orders.*'),
                ],
                [
                    'label' => 'รายงาน',
                    'icon' => 'fas fa-chart-line',
                    'url' => route('seller.analytics.index'),
                    'active' => request()->routeIs('seller.analytics.*'),
                ],
            ],
        ],
        'admin' => [
            'left' => [
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
            ],
            'center' => [
                'label' => 'เพิ่มผู้ใช้',
                'icon' => 'fas fa-user-plus',
                'url' => route('admin.users.create'),
                'active' => request()->routeIs('admin.users.create'),
            ],
            'right' => [
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
            ],
        ],
        default => [
            'left' => [],
            'center' => null,
            'right' => [],
        ],
    };

    /**
     * ดึง theme colors จาก settings
     */
    $primaryStart = Setting::get('theme_primary_start', '#8B5CF6');
    $primaryEnd = Setting::get('theme_primary_end', '#EC4899');
@endphp

<!-- Mobile Bottom Navigation - V3 Modern Design -->
<nav
    x-data="{
        showNav: true,
        lastScroll: 0,
        centerActive: {{ $navigationItems['center']['active'] ?? 'false' ? 'true' : 'false' }}
    }"
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
    x-transition:enter-start="translate-y-full opacity-0"
    x-transition:enter-end="translate-y-0 opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="translate-y-0 opacity-100"
    x-transition:leave-end="translate-y-full opacity-0"
    class="fixed bottom-0 left-0 right-0 z-[100] lg:hidden"
    style="padding-bottom: env(safe-area-inset-bottom, 0px);">

    <!-- Main Container with 3D Effect -->
    <div class="relative">
        <!-- Shadow Layer for 3D Depth -->
        <div class="absolute -top-4 left-4 right-4 h-4 bg-gradient-to-t from-black/10 to-transparent rounded-t-3xl"></div>

        <!-- Glassmorphism Background -->
        <div class="relative bg-white/95 dark:bg-gray-900/95 backdrop-blur-2xl border-t border-white/20 dark:border-gray-700/30 shadow-[0_-8px_32px_rgba(0,0,0,0.12)] dark:shadow-[0_-8px_32px_rgba(0,0,0,0.4)]">

            <!-- Animated Gradient Border Top -->
            <div class="absolute top-0 left-0 right-0 h-[2px] overflow-hidden">
                <div class="h-full w-[200%] bg-gradient-to-r from-purple-500 via-pink-500 via-blue-500 to-purple-500 animate-gradient-x"></div>
            </div>

            <!-- Navigation Items Container -->
            <div class="relative flex items-end justify-between px-2 pt-1 pb-2">

                <!-- Left Items -->
                <div class="flex items-center space-x-1">
                    @foreach($navigationItems['left'] ?? [] as $item)
                        <a
                            href="{{ $item['url'] }}"
                            class="nav-item group relative flex flex-col items-center justify-center w-16 py-2 rounded-2xl transition-all duration-300
                                   {{ $item['active']
                                       ? 'text-purple-600 dark:text-purple-400'
                                       : 'text-gray-500 dark:text-gray-400 hover:text-purple-600 dark:hover:text-purple-400'
                                   }}">

                            <!-- Active Background Glow -->
                            @if($item['active'])
                                <div class="absolute inset-0 bg-gradient-to-br from-purple-500/15 to-pink-500/15 rounded-2xl"></div>
                                <div class="absolute top-0 left-1/2 -translate-x-1/2 w-8 h-1 bg-gradient-to-r from-purple-500 to-pink-500 rounded-full shadow-lg shadow-purple-500/50"></div>
                            @endif

                            <!-- Hover Effect -->
                            <div class="absolute inset-0 bg-gradient-to-br from-purple-500/0 to-pink-500/0 group-hover:from-purple-500/10 group-hover:to-pink-500/10 rounded-2xl transition-all duration-300"></div>

                            <!-- Icon Container with 3D Effect -->
                            <div class="relative z-10 w-10 h-10 flex items-center justify-center rounded-xl transition-all duration-300
                                        {{ $item['active'] ? 'bg-gradient-to-br from-purple-500/20 to-pink-500/20 shadow-lg' : '' }}
                                        group-hover:scale-110 group-hover:-translate-y-0.5 group-active:scale-95">
                                <i class="{{ $item['icon'] }} text-lg transition-transform duration-300 {{ $item['active'] ? 'drop-shadow-[0_2px_4px_rgba(139,92,246,0.3)]' : '' }}"></i>
                            </div>

                            <!-- Label -->
                            <span class="relative z-10 text-[10px] font-semibold mt-0.5 transition-all duration-300 {{ $item['active'] ? 'text-purple-600 dark:text-purple-400' : '' }}">
                                {{ $item['label'] }}
                            </span>
                        </a>
                    @endforeach
                </div>

                <!-- Center Floating Action Button (QR Code) -->
                @if($navigationItems['center'] ?? null)
                    <div class="relative -mt-6 z-20">
                        <!-- Outer Glow Ring -->
                        <div class="absolute inset-0 -m-1 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 opacity-30 blur-md animate-pulse"></div>

                        <!-- Button Container -->
                        <a
                            href="{{ $navigationItems['center']['url'] }}"
                            class="relative flex flex-col items-center justify-center w-16 h-16 rounded-full
                                   bg-gradient-to-br from-purple-500 via-purple-600 to-pink-500
                                   shadow-[0_8px_24px_rgba(139,92,246,0.4),0_4px_12px_rgba(236,72,153,0.3)]
                                   hover:shadow-[0_12px_32px_rgba(139,92,246,0.5),0_6px_16px_rgba(236,72,153,0.4)]
                                   active:scale-95 active:shadow-[0_4px_16px_rgba(139,92,246,0.4)]
                                   transition-all duration-300 transform hover:-translate-y-1
                                   border-4 border-white dark:border-gray-900
                                   group overflow-hidden">

                            <!-- Shine Effect -->
                            <div class="absolute inset-0 bg-gradient-to-tr from-white/30 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>

                            <!-- Rotating Border Animation (on active) -->
                            @if($navigationItems['center']['active'] ?? false)
                                <div class="absolute -inset-1 rounded-full border-2 border-dashed border-purple-300/50 animate-spin-slow"></div>
                            @endif

                            <!-- Icon -->
                            <i class="{{ $navigationItems['center']['icon'] }} text-white text-2xl drop-shadow-lg transform group-hover:scale-110 transition-transform duration-300"></i>
                        </a>

                        <!-- Label below button -->
                        <span class="absolute -bottom-5 left-1/2 -translate-x-1/2 text-[10px] font-bold text-purple-600 dark:text-purple-400 whitespace-nowrap">
                            {{ $navigationItems['center']['label'] }}
                        </span>
                    </div>
                @endif

                <!-- Right Items -->
                <div class="flex items-center space-x-1">
                    @foreach($navigationItems['right'] ?? [] as $item)
                        <a
                            href="{{ $item['url'] }}"
                            class="nav-item group relative flex flex-col items-center justify-center w-16 py-2 rounded-2xl transition-all duration-300
                                   {{ $item['active']
                                       ? 'text-purple-600 dark:text-purple-400'
                                       : 'text-gray-500 dark:text-gray-400 hover:text-purple-600 dark:hover:text-purple-400'
                                   }}">

                            <!-- Active Background Glow -->
                            @if($item['active'])
                                <div class="absolute inset-0 bg-gradient-to-br from-purple-500/15 to-pink-500/15 rounded-2xl"></div>
                                <div class="absolute top-0 left-1/2 -translate-x-1/2 w-8 h-1 bg-gradient-to-r from-purple-500 to-pink-500 rounded-full shadow-lg shadow-purple-500/50"></div>
                            @endif

                            <!-- Hover Effect -->
                            <div class="absolute inset-0 bg-gradient-to-br from-purple-500/0 to-pink-500/0 group-hover:from-purple-500/10 group-hover:to-pink-500/10 rounded-2xl transition-all duration-300"></div>

                            <!-- Icon Container with 3D Effect -->
                            <div class="relative z-10 w-10 h-10 flex items-center justify-center rounded-xl transition-all duration-300
                                        {{ $item['active'] ? 'bg-gradient-to-br from-purple-500/20 to-pink-500/20 shadow-lg' : '' }}
                                        group-hover:scale-110 group-hover:-translate-y-0.5 group-active:scale-95">
                                <i class="{{ $item['icon'] }} text-lg transition-transform duration-300 {{ $item['active'] ? 'drop-shadow-[0_2px_4px_rgba(139,92,246,0.3)]' : '' }}"></i>

                                {{-- Wallet badge แสดงยอดเงิน (optional) --}}
                                @if($item['icon'] === 'fas fa-wallet' && isset($item['badge']))
                                    <span class="absolute -top-1 -right-1 min-w-[18px] h-[18px] flex items-center justify-center bg-gradient-to-r from-green-500 to-emerald-500 text-white text-[8px] font-bold rounded-full px-1 shadow-lg shadow-green-500/30">
                                        {{ $item['badge'] }}
                                    </span>
                                @endif
                            </div>

                            <!-- Label -->
                            <span class="relative z-10 text-[10px] font-semibold mt-0.5 transition-all duration-300 {{ $item['active'] ? 'text-purple-600 dark:text-purple-400' : '' }}">
                                {{ $item['label'] }}
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</nav>

<style>
    /**
     * Gradient Animation
     */
    @keyframes gradient-x {
        0%, 100% {
            transform: translateX(0);
        }
        50% {
            transform: translateX(-50%);
        }
    }

    .animate-gradient-x {
        animation: gradient-x 3s ease infinite;
    }

    /**
     * Slow Spin Animation
     */
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

    /**
     * Safe Area Inset Support
     * รองรับ notch และ home indicator บน iOS/Android
     */
    @supports (padding-bottom: env(safe-area-inset-bottom)) {
        .mobile-bottom-nav {
            padding-bottom: env(safe-area-inset-bottom);
        }
    }

    /**
     * Prevent Content Overlap
     * เพิ่ม padding-bottom ให้ content เพื่อไม่ให้ถูกบัง bottom nav
     */
    @media (max-width: 1023px) {
        body {
            padding-bottom: calc(80px + env(safe-area-inset-bottom, 0px));
        }
    }

    /**
     * Smooth Touch Feedback
     */
    .nav-item {
        -webkit-tap-highlight-color: transparent;
        touch-action: manipulation;
    }

    /**
     * Active Item Bounce Animation
     */
    .nav-item:active {
        animation: tap-bounce 0.2s ease;
    }

    @keyframes tap-bounce {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(0.92); }
    }
</style>
