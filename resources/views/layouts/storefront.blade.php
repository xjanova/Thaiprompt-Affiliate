{{--
    Storefront Layout - Full Width Layout สำหรับหน้าร้านค้า

    Layout แบบเต็มหน้าจอ ไม่มี Sidebar และ Top Navbar ของ Dashboard
    ใช้เมนูของหน้า Shop แทน (Mega Menu, Search Bar, Cart ฯลฯ)

    Features:
    - Full width layout
    - ไม่มี sidebar
    - ไม่มี top navbar ของ dashboard
    - รองรับ Dark Mode
    - Toast Notifications
    - Responsive Design
--}}
<!DOCTYPE html>
<html lang="th" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5, user-scalable=yes">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">

    <title>@yield('title', 'ร้านค้าออนไลน์') - {{ config('app.name') }}</title>

    {{-- Meta Tags สำหรับ SEO --}}
    @yield('meta')

    {{-- Favicon (ใช้ Theme Setting) --}}
    @php
        $themeSetting = \App\Models\ThemeSetting::active();
        $faviconPath = $themeSetting && $themeSetting->favicon_path
            ? asset('storage/' . $themeSetting->favicon_path)
            : asset('favicon.ico');
    @endphp
    <link rel="icon" type="image/x-icon" href="{{ $faviconPath }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ $faviconPath }}">
    <link rel="apple-touch-icon" href="{{ $faviconPath }}">

    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Noto+Sans+Thai:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    {{-- Font Awesome 6.5.1 --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    {{-- Vite Assets --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Alpine.js x-cloak --}}
    <style>
        [x-cloak] {
            display: none !important;
        }

        /* Smooth scrolling สำหรับทั้งหน้า */
        html {
            scroll-behavior: smooth;
        }

        /* Custom Scrollbar สำหรับ Storefront */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .dark ::-webkit-scrollbar-track {
            background: #1f2937;
        }

        ::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }

        .dark ::-webkit-scrollbar-thumb {
            background: #4b5563;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #a1a1a1;
        }

        .dark ::-webkit-scrollbar-thumb:hover {
            background: #6b7280;
        }

        /* ========================================
           Liquid Lava Lamp Background - RGB Glow
           ลิควิดลาวาลอยขึ้นลง พร้อมเรืองแสงในโหมดมืด
           ======================================== */
        .lava-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
            overflow: hidden;
        }

        .lava-blob {
            position: absolute;
            border-radius: 45% 55% 60% 40% / 55% 45% 55% 45%;
            filter: blur(60px);
            opacity: 0.4;
            will-change: transform, border-radius;
        }

        /* RGB Gradient สีสันสดใส - Light Mode */
        .lava-blob:nth-child(1) {
            width: 250px;
            height: 280px;
            background: linear-gradient(180deg, #ff0844 0%, #ffb199 50%, #ff0844 100%);
            left: 5%;
            top: 10%;
            animation: lavaFloat1 12s ease-in-out infinite, morphBlob1 8s ease-in-out infinite;
        }

        .lava-blob:nth-child(2) {
            width: 200px;
            height: 220px;
            background: linear-gradient(180deg, #00d4ff 0%, #00ffab 50%, #00d4ff 100%);
            left: 20%;
            top: 40%;
            animation: lavaFloat2 14s ease-in-out infinite, morphBlob2 10s ease-in-out infinite;
            animation-delay: -3s;
        }

        .lava-blob:nth-child(3) {
            width: 280px;
            height: 300px;
            background: linear-gradient(180deg, #a855f7 0%, #ec4899 50%, #a855f7 100%);
            left: 35%;
            top: 60%;
            animation: lavaFloat3 16s ease-in-out infinite, morphBlob3 12s ease-in-out infinite;
            animation-delay: -5s;
        }

        .lava-blob:nth-child(4) {
            width: 180px;
            height: 200px;
            background: linear-gradient(180deg, #facc15 0%, #fb923c 50%, #facc15 100%);
            right: 30%;
            top: 20%;
            animation: lavaFloat1 13s ease-in-out infinite, morphBlob1 9s ease-in-out infinite;
            animation-delay: -2s;
        }

        .lava-blob:nth-child(5) {
            width: 220px;
            height: 240px;
            background: linear-gradient(180deg, #22c55e 0%, #06b6d4 50%, #22c55e 100%);
            right: 10%;
            top: 50%;
            animation: lavaFloat2 15s ease-in-out infinite, morphBlob2 11s ease-in-out infinite;
            animation-delay: -7s;
        }

        .lava-blob:nth-child(6) {
            width: 200px;
            height: 220px;
            background: linear-gradient(180deg, #3b82f6 0%, #8b5cf6 50%, #3b82f6 100%);
            right: 5%;
            top: 80%;
            animation: lavaFloat3 11s ease-in-out infinite, morphBlob3 7s ease-in-out infinite;
            animation-delay: -4s;
        }

        .lava-blob:nth-child(7) {
            width: 160px;
            height: 180px;
            background: linear-gradient(180deg, #f43f5e 0%, #fb7185 50%, #f43f5e 100%);
            left: 50%;
            top: 30%;
            animation: lavaFloat1 17s ease-in-out infinite, morphBlob1 10s ease-in-out infinite;
            animation-delay: -8s;
        }

        .lava-blob:nth-child(8) {
            width: 190px;
            height: 210px;
            background: linear-gradient(180deg, #14b8a6 0%, #0ea5e9 50%, #14b8a6 100%);
            left: 70%;
            top: 70%;
            animation: lavaFloat2 13s ease-in-out infinite, morphBlob2 8s ease-in-out infinite;
            animation-delay: -6s;
        }

        /* Animation - ลอยขึ้นลงและซ้ายขวา */
        @keyframes lavaFloat1 {
            0%, 100% {
                transform: translate(0, 0) scale(1);
            }
            25% {
                transform: translate(30px, -50px) scale(1.05);
            }
            50% {
                transform: translate(-20px, -100px) scale(0.95);
            }
            75% {
                transform: translate(40px, -50px) scale(1.02);
            }
        }

        @keyframes lavaFloat2 {
            0%, 100% {
                transform: translate(0, 0) scale(1);
            }
            33% {
                transform: translate(-40px, -80px) scale(1.08);
            }
            66% {
                transform: translate(30px, -40px) scale(0.92);
            }
        }

        @keyframes lavaFloat3 {
            0%, 100% {
                transform: translate(0, 0) scale(1);
            }
            50% {
                transform: translate(50px, -120px) scale(1.1);
            }
        }

        /* Blob morphing - เปลี่ยนรูปร่าง */
        @keyframes morphBlob1 {
            0%, 100% {
                border-radius: 45% 55% 60% 40% / 55% 45% 55% 45%;
            }
            25% {
                border-radius: 60% 40% 45% 55% / 40% 60% 50% 50%;
            }
            50% {
                border-radius: 50% 50% 55% 45% / 45% 55% 60% 40%;
            }
            75% {
                border-radius: 40% 60% 50% 50% / 60% 40% 45% 55%;
            }
        }

        @keyframes morphBlob2 {
            0%, 100% {
                border-radius: 55% 45% 50% 50% / 45% 55% 45% 55%;
            }
            33% {
                border-radius: 40% 60% 55% 45% / 55% 45% 55% 45%;
            }
            66% {
                border-radius: 60% 40% 45% 55% / 50% 50% 50% 50%;
            }
        }

        @keyframes morphBlob3 {
            0%, 100% {
                border-radius: 50% 50% 45% 55% / 55% 45% 60% 40%;
            }
            50% {
                border-radius: 45% 55% 55% 45% / 40% 60% 45% 55%;
            }
        }

        /* ===== Dark Mode - RGB Glow Effect ===== */
        .dark .lava-blob {
            filter: blur(60px);
            opacity: 0.7;
        }

        .dark .lava-blob:nth-child(1) {
            background: linear-gradient(180deg, #ff0844 0%, #ff3366 50%, #ff0844 100%);
            box-shadow:
                0 0 40px rgba(255, 8, 68, 0.8),
                0 0 80px rgba(255, 8, 68, 0.6),
                0 0 120px rgba(255, 8, 68, 0.4),
                inset 0 0 30px rgba(255, 255, 255, 0.2);
        }

        .dark .lava-blob:nth-child(2) {
            background: linear-gradient(180deg, #00d4ff 0%, #00ffff 50%, #00d4ff 100%);
            box-shadow:
                0 0 40px rgba(0, 212, 255, 0.8),
                0 0 80px rgba(0, 212, 255, 0.6),
                0 0 120px rgba(0, 212, 255, 0.4),
                inset 0 0 30px rgba(255, 255, 255, 0.2);
        }

        .dark .lava-blob:nth-child(3) {
            background: linear-gradient(180deg, #a855f7 0%, #d946ef 50%, #a855f7 100%);
            box-shadow:
                0 0 40px rgba(168, 85, 247, 0.8),
                0 0 80px rgba(168, 85, 247, 0.6),
                0 0 120px rgba(168, 85, 247, 0.4),
                inset 0 0 30px rgba(255, 255, 255, 0.2);
        }

        .dark .lava-blob:nth-child(4) {
            background: linear-gradient(180deg, #facc15 0%, #fde047 50%, #facc15 100%);
            box-shadow:
                0 0 40px rgba(250, 204, 21, 0.8),
                0 0 80px rgba(250, 204, 21, 0.6),
                0 0 120px rgba(250, 204, 21, 0.4),
                inset 0 0 30px rgba(255, 255, 255, 0.2);
        }

        .dark .lava-blob:nth-child(5) {
            background: linear-gradient(180deg, #22c55e 0%, #4ade80 50%, #22c55e 100%);
            box-shadow:
                0 0 40px rgba(34, 197, 94, 0.8),
                0 0 80px rgba(34, 197, 94, 0.6),
                0 0 120px rgba(34, 197, 94, 0.4),
                inset 0 0 30px rgba(255, 255, 255, 0.2);
        }

        .dark .lava-blob:nth-child(6) {
            background: linear-gradient(180deg, #3b82f6 0%, #60a5fa 50%, #3b82f6 100%);
            box-shadow:
                0 0 40px rgba(59, 130, 246, 0.8),
                0 0 80px rgba(59, 130, 246, 0.6),
                0 0 120px rgba(59, 130, 246, 0.4),
                inset 0 0 30px rgba(255, 255, 255, 0.2);
        }

        .dark .lava-blob:nth-child(7) {
            background: linear-gradient(180deg, #f43f5e 0%, #fb7185 50%, #f43f5e 100%);
            box-shadow:
                0 0 40px rgba(244, 63, 94, 0.8),
                0 0 80px rgba(244, 63, 94, 0.6),
                0 0 120px rgba(244, 63, 94, 0.4),
                inset 0 0 30px rgba(255, 255, 255, 0.2);
        }

        .dark .lava-blob:nth-child(8) {
            background: linear-gradient(180deg, #14b8a6 0%, #2dd4bf 50%, #14b8a6 100%);
            box-shadow:
                0 0 40px rgba(20, 184, 166, 0.8),
                0 0 80px rgba(20, 184, 166, 0.6),
                0 0 120px rgba(20, 184, 166, 0.4),
                inset 0 0 30px rgba(255, 255, 255, 0.2);
        }

        /* ลดขนาดและจำนวนบนมือถือเพื่อประสิทธิภาพ */
        @media (max-width: 768px) {
            .lava-blob {
                transform: scale(0.7);
                filter: blur(30px);
            }
            .lava-blob:nth-child(n+6) {
                display: none;
            }
            .dark .lava-blob {
                filter: blur(40px);
            }
        }

        /* ปิดการแสดงผลถ้าผู้ใช้ต้องการลด motion */
        @media (prefers-reduced-motion: reduce) {
            .lava-blob {
                animation: none;
                bottom: 50%;
                transform: translateY(50%);
            }
        }
    </style>

    @stack('styles')
</head>
<body class="min-h-full font-sans"
      x-data="{}"
      x-init="
          // Initialize theme store ถ้ามี
          if ($store.theme) {
              $store.theme.init();
          }
      ">

    {{-- Base Background Layer - สำหรับ light/dark mode --}}
    <div class="fixed inset-0 bg-gray-50 dark:bg-gray-900 -z-20"></div>

    {{-- Lava Background Slot - สำหรับหน้าที่ต้องการ lava effect --}}
    @yield('lava-background')

    {{-- Main Content Area - Full Width --}}
    <div class="min-h-screen relative z-0">
        @yield('content')
    </div>

    {{-- Floating Cart Button (สำหรับหน้าที่ไม่มี cart ใน navbar) --}}
    @auth
    <div x-data="floatingCart()"
         x-init="init()"
         @cart-updated.window="loadCartCount()"
         class="fixed bottom-6 right-6 z-[9997]">

        {{-- Cart FAB Button --}}
        <button @click="openDrawer()"
                type="button"
                class="relative w-14 h-14 rounded-full
                       bg-gradient-to-r from-orange-500 to-red-500
                       text-white shadow-xl
                       hover:shadow-2xl hover:scale-110
                       active:scale-95 transition-all
                       flex items-center justify-center
                       group">
            <svg class="w-6 h-6 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>

            {{-- Badge --}}
            <span x-show="cartCount > 0"
                  x-text="cartCount > 99 ? '99+' : cartCount"
                  x-transition
                  class="absolute -top-1 -right-1 min-w-[22px] h-[22px] px-1
                         bg-yellow-400 text-gray-900 text-xs font-bold
                         rounded-full flex items-center justify-center
                         shadow-lg animate-bounce">
            </span>
        </button>

        {{-- Backdrop --}}
        <div x-show="isOpen"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="closeDrawer()"
             class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[9998]"
             style="display: none;">
        </div>

        {{-- Drawer Panel --}}
        <div x-show="isOpen"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="translate-x-full"
             @keydown.escape.window="closeDrawer()"
             class="fixed top-0 right-0 w-full max-w-md h-full
                    bg-white dark:bg-gray-800
                    shadow-2xl z-[9999]
                    flex flex-col"
             style="display: none;">

            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4
                        border-b border-gray-200 dark:border-gray-700
                        bg-gradient-to-r from-orange-500 to-red-500">
                <h2 class="text-xl font-bold text-white flex items-center gap-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    ตะกร้าสินค้า
                    <span x-show="cartCount > 0" x-text="`(${cartCount})`" class="text-white/80"></span>
                </h2>
                <button @click="closeDrawer()" type="button"
                        class="p-2 rounded-lg text-white/80 hover:text-white hover:bg-white/20 transition-all">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Content --}}
            <div class="flex-1 overflow-y-auto">
                {{-- Loading --}}
                <div x-show="loading" class="flex items-center justify-center py-16">
                    <svg class="animate-spin h-10 w-10 text-orange-500" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                </div>

                {{-- Empty State --}}
                <div x-show="!loading && items.length === 0" class="flex flex-col items-center justify-center py-16 px-6">
                    <div class="w-20 h-20 mb-4 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                        <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">ตะกร้าว่างเปล่า</h3>
                    <p class="text-gray-500 dark:text-gray-400 text-center text-sm">เลือกซื้อสินค้าได้เลย!</p>
                </div>

                {{-- Cart Items --}}
                <div x-show="!loading && items.length > 0" class="divide-y divide-gray-200 dark:divide-gray-700">
                    <template x-for="item in items" :key="item.id">
                        <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-all">
                            <div class="flex gap-3">
                                <div class="w-16 h-16 rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-700 flex-shrink-0">
                                    <img :src="item.image || '/images/no-image.png'" :alt="item.name"
                                         class="w-full h-full object-cover" onerror="this.src='/images/no-image.png'">
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-medium text-gray-900 dark:text-white text-sm line-clamp-2" x-text="item.name"></h4>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1" x-text="formatPrice(item.price)"></p>
                                    <div class="flex items-center gap-2 mt-2">
                                        <div class="flex items-center border border-gray-300 dark:border-gray-600 rounded-lg">
                                            <button @click="updateQuantity(item.id, item.quantity - 1)"
                                                    class="w-7 h-7 flex items-center justify-center text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                                            </button>
                                            <span class="w-8 text-center text-sm font-medium text-gray-900 dark:text-white" x-text="item.quantity"></span>
                                            <button @click="updateQuantity(item.id, item.quantity + 1)"
                                                    class="w-7 h-7 flex items-center justify-center text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                            </button>
                                        </div>
                                        <button @click="removeItem(item.id)"
                                                class="p-1.5 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                        <span class="ml-auto font-bold text-orange-600 dark:text-orange-400 text-sm" x-text="formatPrice(item.price * item.quantity)"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Footer --}}
            <div x-show="items.length > 0"
                 class="flex-shrink-0 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 p-4">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-gray-600 dark:text-gray-400">รวมทั้งหมด</span>
                    <span class="text-xl font-bold text-orange-600 dark:text-orange-400" x-text="formatPrice(cartTotal)"></span>
                </div>
                <div class="flex flex-col gap-2">
                    <a href="{{ route('checkout.index') }}" @click="closeDrawer()"
                       class="w-full py-3 text-center font-bold text-white bg-gradient-to-r from-orange-500 to-red-500 rounded-xl shadow-lg hover:shadow-xl transition-all">
                        ดำเนินการชำระเงิน
                    </a>
                    <a href="{{ route('cart.index') }}" @click="closeDrawer()"
                       class="w-full py-2.5 text-center font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl transition-all">
                        ดูตะกร้าแบบเต็ม
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
    function floatingCart() {
        return {
            isOpen: false,
            loading: false,
            items: [],
            cartCount: 0,
            cartTotal: 0,

            init() {
                this.loadCartCount();
                setInterval(() => this.loadCartCount(), 30000);
            },

            async loadCartCount() {
                try {
                    const res = await fetch('{{ route("cart.count") }}', {
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                    });
                    if (res.ok) {
                        const data = await res.json();
                        this.cartCount = data.count || 0;
                    }
                } catch (e) { console.debug('Cart count error:', e); }
            },

            async loadCart() {
                this.loading = true;
                try {
                    const res = await fetch('{{ route("cart.mini") }}', {
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                    });
                    if (res.ok) {
                        const data = await res.json();
                        this.items = data.items || [];
                        this.cartCount = data.count || 0;
                        this.cartTotal = data.total || 0;
                    }
                } catch (e) { console.error('Cart load error:', e); }
                this.loading = false;
            },

            async updateQuantity(itemId, qty) {
                if (qty < 1) { this.removeItem(itemId); return; }
                try {
                    const res = await fetch(`/cart/${itemId}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ quantity: qty })
                    });
                    if (res.ok) { await this.loadCart(); window.dispatchEvent(new CustomEvent('cart-updated')); }
                } catch (e) { console.error('Update error:', e); }
            },

            async removeItem(itemId) {
                try {
                    const res = await fetch(`/cart/${itemId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    });
                    if (res.ok) { await this.loadCart(); window.dispatchEvent(new CustomEvent('cart-updated')); }
                } catch (e) { console.error('Remove error:', e); }
            },

            openDrawer() {
                this.isOpen = true;
                this.loadCart();
                document.body.style.overflow = 'hidden';
            },

            closeDrawer() {
                this.isOpen = false;
                document.body.style.overflow = '';
            },

            formatPrice(price) {
                return new Intl.NumberFormat('th-TH', { style: 'currency', currency: 'THB', minimumFractionDigits: 0 }).format(price);
            }
        };
    }
    </script>
    @endauth

    {{-- Toast Notifications --}}
    <div class="fixed bottom-4 right-4 z-[9999] space-y-2 max-w-md"
         x-data="{ notifications: [] }"
         @notify.window="notifications.push($event.detail); setTimeout(() => notifications.shift(), 5000)">
        <template x-for="(notification, index) in notifications" :key="index">
            <div
                x-show="true"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform translate-x-full"
                x-transition:enter-end="opacity-100 transform translate-x-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 transform translate-x-0"
                x-transition:leave-end="opacity-0 transform translate-x-full"
                class="px-4 py-3 rounded-lg shadow-lg backdrop-blur-lg"
                :class="{
                    'bg-green-500/90 text-white': notification.type === 'success',
                    'bg-red-500/90 text-white': notification.type === 'error',
                    'bg-blue-500/90 text-white': notification.type === 'info',
                    'bg-yellow-500/90 text-white': notification.type === 'warning'
                }">
                <div class="flex items-center space-x-2">
                    <i class="fas"
                       :class="{
                           'fa-check-circle': notification.type === 'success',
                           'fa-exclamation-circle': notification.type === 'error',
                           'fa-info-circle': notification.type === 'info',
                           'fa-exclamation-triangle': notification.type === 'warning'
                       }"></i>
                    <span x-text="notification.message"></span>
                </div>
            </div>
        </template>
    </div>

    {{-- Laravel Session Flash Messages --}}
    @if (session('success'))
        <div x-data x-init="$dispatch('notify', { type: 'success', message: '{{ session('success') }}' })"></div>
    @endif
    @if (session('error'))
        <div x-data x-init="$dispatch('notify', { type: 'error', message: '{{ session('error') }}' })"></div>
    @endif
    @if (session('info'))
        <div x-data x-init="$dispatch('notify', { type: 'info', message: '{{ session('info') }}' })"></div>
    @endif
    @if (session('warning'))
        <div x-data x-init="$dispatch('notify', { type: 'warning', message: '{{ session('warning') }}' })"></div>
    @endif

    @stack('scripts')

    <script>
    /**
     * Helper function สำหรับแสดง notification
     */
    window.showNotification = function(message, type = 'info') {
        window.dispatchEvent(new CustomEvent('notify', {
            detail: { message, type }
        }));
    };

    /**
     * Theme detection และ initialization
     */
    document.addEventListener('DOMContentLoaded', function() {
        // ตรวจสอบ dark mode preference
        if (localStorage.getItem('theme') === 'dark' ||
            (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    });

    /**
     * Toggle Dark Mode
     */
    window.toggleDarkMode = function() {
        document.documentElement.classList.toggle('dark');
        localStorage.setItem('theme', document.documentElement.classList.contains('dark') ? 'dark' : 'light');
    };
    </script>
</body>
</html>
