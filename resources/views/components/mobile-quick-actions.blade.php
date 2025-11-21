{{--
    Mobile Quick Actions Component - ปุ่มลัดเร็วแบบแอพ

    Floating Action Buttons (FAB) สำหรับ quick actions บนมือถือ
    พร้อม expandable menu และ smooth animations

    Props:
    - type: ประเภท (user, seller, admin)
--}}

@props(['type' => 'user'])

@php
    /**
     * กำหนด Quick Actions สำหรับแต่ละประเภทผู้ใช้
     *
     * แต่ละ action จะแสดงเป็นปุ่มลอยที่ด้านล่างขวา
     */
    $quickActions = match($type) {
        'user' => [
            [
                'label' => 'ฝากเงิน',
                'icon' => 'fas fa-plus-circle',
                'url' => route('user.wallet.deposit'),
                'color' => 'from-green-500 to-emerald-600',
                'darkColor' => 'from-green-600 to-emerald-700',
            ],
            [
                'label' => 'ถอนเงิน',
                'icon' => 'fas fa-minus-circle',
                'url' => route('user.wallet.withdraw'),
                'color' => 'from-orange-500 to-red-600',
                'darkColor' => 'from-orange-600 to-red-700',
            ],
            [
                'label' => 'โอนเงิน',
                'icon' => 'fas fa-exchange-alt',
                'url' => route('user.wallet.transfer'),
                'color' => 'from-blue-500 to-indigo-600',
                'darkColor' => 'from-blue-600 to-indigo-700',
            ],
            [
                'label' => 'แชร์ลิงก์',
                'icon' => 'fas fa-share-alt',
                'url' => route('user.mlm.referral'),
                'color' => 'from-purple-500 to-pink-600',
                'darkColor' => 'from-purple-600 to-pink-700',
            ],
        ],
        'seller' => [
            [
                'label' => 'เพิ่มสินค้า',
                'icon' => 'fas fa-plus',
                'url' => route('seller.products.create'),
                'color' => 'from-green-500 to-emerald-600',
                'darkColor' => 'from-green-600 to-emerald-700',
            ],
            [
                'label' => 'สแกนคิวอาร์',
                'icon' => 'fas fa-qrcode',
                'url' => route('seller.qr-scanner'),
                'color' => 'from-blue-500 to-indigo-600',
                'darkColor' => 'from-blue-600 to-indigo-700',
            ],
            [
                'label' => 'คำสั่งซื้อใหม่',
                'icon' => 'fas fa-shopping-cart',
                'url' => route('seller.orders.index'),
                'color' => 'from-purple-500 to-pink-600',
                'darkColor' => 'from-purple-600 to-pink-700',
            ],
        ],
        'admin' => [
            [
                'label' => 'เพิ่มผู้ใช้',
                'icon' => 'fas fa-user-plus',
                'url' => route('admin.users.create'),
                'color' => 'from-green-500 to-emerald-600',
                'darkColor' => 'from-green-600 to-emerald-700',
            ],
            [
                'label' => 'รายงาน',
                'icon' => 'fas fa-chart-bar',
                'url' => route('admin.reports.index'),
                'color' => 'from-blue-500 to-indigo-600',
                'darkColor' => 'from-blue-600 to-indigo-700',
            ],
        ],
        default => [],
    };
@endphp

<!-- Quick Actions FAB - แสดงเฉพาะบนมือถือและแท็บเล็ต -->
<div
    x-data="{
        fabOpen: false,
        showFab: true,
        lastScroll: 0,

        init() {
            // ซ่อน FAB เมื่อ scroll ลงเร็ว แสดงเมื่อ scroll ขึ้น
            window.addEventListener('scroll', () => {
                let currentScroll = window.pageYOffset;
                if (currentScroll > this.lastScroll && currentScroll > 200) {
                    this.showFab = false;
                    this.fabOpen = false; // ปิดเมนูด้วยถ้ากำลังเปิดอยู่
                } else {
                    this.showFab = true;
                }
                this.lastScroll = currentScroll;
            });
        },

        toggleFab() {
            this.fabOpen = !this.fabOpen;
        }
    }"
    class="fixed z-[90] lg:hidden"
    style="
        bottom: calc(80px + env(safe-area-inset-bottom, 0px));
        right: 1rem;
    ">

    <!-- Quick Action Buttons -->
    <div class="flex flex-col-reverse items-end space-y-reverse space-y-3 mb-3">
        @foreach($quickActions as $index => $action)
            <a
                href="{{ $action['url'] }}"
                x-show="fabOpen"
                x-transition:enter="transition ease-out duration-{{ 200 + ($index * 50) }}"
                x-transition:enter-start="opacity-0 translate-y-4 scale-75"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 scale-75"
                style="transition-delay: {{ $index * 50 }}ms;"
                class="group flex items-center space-x-3 active:scale-95"
                @click="fabOpen = false">

                <!-- Label -->
                <span class="px-3 py-2 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 text-sm font-medium rounded-lg shadow-lg whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                    {{ $action['label'] }}
                </span>

                <!-- Action Button -->
                <div class="relative">
                    <div class="w-12 h-12 bg-gradient-to-br {{ $action['color'] }} dark:{{ $action['darkColor'] }} rounded-full shadow-lg flex items-center justify-center text-white transform group-hover:scale-110 transition-transform duration-200">
                        <i class="{{ $action['icon'] }} text-lg"></i>
                    </div>

                    <!-- Glow Effect -->
                    <div class="absolute inset-0 bg-gradient-to-br {{ $action['color'] }} rounded-full blur-md opacity-50 group-hover:opacity-70 transition-opacity duration-200"></div>
                </div>
            </a>
        @endforeach
    </div>

    <!-- Main FAB Button -->
    <button
        type="button"
        @click="toggleFab()"
        x-show="showFab"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-75"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-75"
        class="relative w-14 h-14 bg-gradient-to-br from-purple-600 to-blue-600 dark:from-purple-700 dark:to-blue-700 rounded-full shadow-2xl flex items-center justify-center text-white transform hover:scale-110 active:scale-95 transition-all duration-200"
        aria-label="Quick Actions">

        <!-- Icon - เปลี่ยนตาม state -->
        <svg
            x-show="!fabOpen"
            class="w-6 h-6"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
            xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
        </svg>

        <svg
            x-show="fabOpen"
            x-cloak
            class="w-6 h-6 transform rotate-45"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
            xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
        </svg>

        <!-- Pulsing Ring -->
        <div class="absolute inset-0 rounded-full bg-purple-500 animate-ping opacity-20"></div>

        <!-- Glow Effect -->
        <div class="absolute inset-0 rounded-full bg-gradient-to-br from-purple-600 to-blue-600 blur-md opacity-50"></div>
    </button>

    <!-- Overlay - ปิดเมนูเมื่อคลิกนอกพื้นที่ -->
    <div
        x-show="fabOpen"
        @click="fabOpen = false"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 -z-10 bg-black/20 backdrop-blur-[1px]">
    </div>
</div>

<style>
    /**
     * Alpine.js x-cloak
     *
     * ซ่อน element ก่อนที่ Alpine.js จะ initialize
     */
    [x-cloak] {
        display: none !important;
    }

    /**
     * Prevent tap highlight on mobile
     */
    .mobile-quick-actions button,
    .mobile-quick-actions a {
        -webkit-tap-highlight-color: transparent;
    }

    /**
     * Smooth animations
     */
    @media (prefers-reduced-motion: reduce) {
        * {
            animation-duration: 0.01ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: 0.01ms !important;
        }
    }
</style>
