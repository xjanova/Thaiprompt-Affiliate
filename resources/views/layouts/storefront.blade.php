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
            z-index: 9990;
            overflow: hidden;
        }

        .lava-blob {
            position: absolute;
            border-radius: 45% 55% 60% 40% / 55% 45% 55% 45%;
            filter: blur(40px);
            opacity: 0.6;
            will-change: transform, border-radius;
        }

        /* RGB Gradient สีสันสดใส - Light Mode */
        .lava-blob:nth-child(1) {
            width: 180px;
            height: 200px;
            background: linear-gradient(180deg, #ff0844 0%, #ffb199 50%, #ff0844 100%);
            left: 5%;
            bottom: -100px;
            animation: lavaRise1 18s ease-in-out infinite, morphBlob1 8s ease-in-out infinite;
        }

        .lava-blob:nth-child(2) {
            width: 150px;
            height: 170px;
            background: linear-gradient(180deg, #00d4ff 0%, #00ffab 50%, #00d4ff 100%);
            left: 20%;
            bottom: -80px;
            animation: lavaRise2 22s ease-in-out infinite, morphBlob2 10s ease-in-out infinite;
            animation-delay: -5s;
        }

        .lava-blob:nth-child(3) {
            width: 200px;
            height: 220px;
            background: linear-gradient(180deg, #a855f7 0%, #ec4899 50%, #a855f7 100%);
            left: 38%;
            bottom: -120px;
            animation: lavaRise3 20s ease-in-out infinite, morphBlob3 12s ease-in-out infinite;
            animation-delay: -8s;
        }

        .lava-blob:nth-child(4) {
            width: 130px;
            height: 150px;
            background: linear-gradient(180deg, #facc15 0%, #fb923c 50%, #facc15 100%);
            left: 55%;
            bottom: -70px;
            animation: lavaRise4 16s ease-in-out infinite, morphBlob1 9s ease-in-out infinite;
            animation-delay: -3s;
        }

        .lava-blob:nth-child(5) {
            width: 170px;
            height: 190px;
            background: linear-gradient(180deg, #22c55e 0%, #06b6d4 50%, #22c55e 100%);
            left: 72%;
            bottom: -90px;
            animation: lavaRise5 24s ease-in-out infinite, morphBlob2 11s ease-in-out infinite;
            animation-delay: -12s;
        }

        .lava-blob:nth-child(6) {
            width: 160px;
            height: 180px;
            background: linear-gradient(180deg, #3b82f6 0%, #8b5cf6 50%, #3b82f6 100%);
            left: 88%;
            bottom: -85px;
            animation: lavaRise1 19s ease-in-out infinite, morphBlob3 7s ease-in-out infinite;
            animation-delay: -7s;
        }

        .lava-blob:nth-child(7) {
            width: 140px;
            height: 160px;
            background: linear-gradient(180deg, #f43f5e 0%, #fb7185 50%, #f43f5e 100%);
            left: 12%;
            bottom: -75px;
            animation: lavaRise3 21s ease-in-out infinite, morphBlob1 10s ease-in-out infinite;
            animation-delay: -15s;
        }

        .lava-blob:nth-child(8) {
            width: 120px;
            height: 140px;
            background: linear-gradient(180deg, #14b8a6 0%, #0ea5e9 50%, #14b8a6 100%);
            left: 62%;
            bottom: -65px;
            animation: lavaRise2 17s ease-in-out infinite, morphBlob2 8s ease-in-out infinite;
            animation-delay: -10s;
        }

        /* Animation - ลอยขึ้นจากล่างขึ้นบน แล้วกลับลงมา */
        @keyframes lavaRise1 {
            0%, 100% {
                transform: translateY(0) scale(1);
            }
            50% {
                transform: translateY(calc(-100vh - 100px)) scale(1.1);
            }
        }

        @keyframes lavaRise2 {
            0%, 100% {
                transform: translateY(0) scale(0.9);
            }
            50% {
                transform: translateY(calc(-100vh - 150px)) scale(1);
            }
        }

        @keyframes lavaRise3 {
            0%, 100% {
                transform: translateY(0) scale(1.05);
            }
            50% {
                transform: translateY(calc(-100vh - 80px)) scale(0.95);
            }
        }

        @keyframes lavaRise4 {
            0%, 100% {
                transform: translateY(0) scale(0.95);
            }
            50% {
                transform: translateY(calc(-100vh - 120px)) scale(1.08);
            }
        }

        @keyframes lavaRise5 {
            0%, 100% {
                transform: translateY(0) scale(1);
            }
            50% {
                transform: translateY(calc(-100vh - 180px)) scale(1.05);
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

    {{-- Liquid Lava Lamp Background - RGB Glow ลอยขึ้นลงเหมือนลาวา (อยู่บน content แต่ pointer-events: none) --}}
    <div class="lava-background" aria-hidden="true">
        <div class="lava-blob"></div>
        <div class="lava-blob"></div>
        <div class="lava-blob"></div>
        <div class="lava-blob"></div>
        <div class="lava-blob"></div>
        <div class="lava-blob"></div>
        <div class="lava-blob"></div>
        <div class="lava-blob"></div>
    </div>

    {{-- Main Content Area - Full Width --}}
    <div class="min-h-screen relative z-0">
        @yield('content')
    </div>

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
