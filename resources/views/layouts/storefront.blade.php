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
           Animated Particle Background - อะตอม/เกสรสี
           ======================================== */
        .particle-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
            overflow: hidden;
        }

        .particle {
            position: absolute;
            border-radius: 50%;
            animation: float 20s ease-in-out infinite;
            opacity: 0.4;
        }

        /* สร้าง particles หลายขนาดและสี */
        .particle:nth-child(1) {
            width: 20px;
            height: 20px;
            background: linear-gradient(135deg, #f97316 0%, #ef4444 100%);
            left: 10%;
            top: 20%;
            animation-delay: 0s;
            animation-duration: 25s;
        }

        .particle:nth-child(2) {
            width: 15px;
            height: 15px;
            background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);
            left: 20%;
            top: 60%;
            animation-delay: -2s;
            animation-duration: 20s;
        }

        .particle:nth-child(3) {
            width: 25px;
            height: 25px;
            background: linear-gradient(135deg, #06b6d4 0%, #3b82f6 100%);
            left: 35%;
            top: 30%;
            animation-delay: -4s;
            animation-duration: 28s;
        }

        .particle:nth-child(4) {
            width: 12px;
            height: 12px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            left: 50%;
            top: 70%;
            animation-delay: -6s;
            animation-duration: 22s;
        }

        .particle:nth-child(5) {
            width: 18px;
            height: 18px;
            background: linear-gradient(135deg, #f59e0b 0%, #f97316 100%);
            left: 65%;
            top: 15%;
            animation-delay: -8s;
            animation-duration: 26s;
        }

        .particle:nth-child(6) {
            width: 22px;
            height: 22px;
            background: linear-gradient(135deg, #ec4899 0%, #8b5cf6 100%);
            left: 75%;
            top: 50%;
            animation-delay: -10s;
            animation-duration: 24s;
        }

        .particle:nth-child(7) {
            width: 14px;
            height: 14px;
            background: linear-gradient(135deg, #3b82f6 0%, #06b6d4 100%);
            left: 85%;
            top: 80%;
            animation-delay: -12s;
            animation-duration: 30s;
        }

        .particle:nth-child(8) {
            width: 16px;
            height: 16px;
            background: linear-gradient(135deg, #ef4444 0%, #f97316 100%);
            left: 5%;
            top: 85%;
            animation-delay: -14s;
            animation-duration: 23s;
        }

        .particle:nth-child(9) {
            width: 10px;
            height: 10px;
            background: linear-gradient(135deg, #8b5cf6 0%, #3b82f6 100%);
            left: 45%;
            top: 45%;
            animation-delay: -16s;
            animation-duration: 27s;
        }

        .particle:nth-child(10) {
            width: 20px;
            height: 20px;
            background: linear-gradient(135deg, #10b981 0%, #06b6d4 100%);
            left: 90%;
            top: 25%;
            animation-delay: -18s;
            animation-duration: 21s;
        }

        /* Animation สำหรับการลอย */
        @keyframes float {
            0%, 100% {
                transform: translateY(0) translateX(0) rotate(0deg) scale(1);
            }
            25% {
                transform: translateY(-100px) translateX(50px) rotate(90deg) scale(1.1);
            }
            50% {
                transform: translateY(-50px) translateX(-30px) rotate(180deg) scale(0.9);
            }
            75% {
                transform: translateY(-150px) translateX(20px) rotate(270deg) scale(1.05);
            }
        }

        /* Dark mode adjustments */
        .dark .particle {
            opacity: 0.25;
        }

        /* ลดการแสดงผลบนมือถือเพื่อประสิทธิภาพ */
        @media (max-width: 768px) {
            .particle {
                opacity: 0.2;
            }
            .particle:nth-child(n+7) {
                display: none;
            }
        }

        /* ปิดการแสดงผลถ้าผู้ใช้ต้องการลด motion */
        @media (prefers-reduced-motion: reduce) {
            .particle {
                animation: none;
            }
        }
    </style>

    @stack('styles')
</head>
<body class="min-h-full font-sans bg-gray-50 dark:bg-gray-900"
      x-data="{}"
      x-init="
          // Initialize theme store ถ้ามี
          if ($store.theme) {
              $store.theme.init();
          }
      ">

    {{-- Animated Particle Background - อะตอม/เกสรสีลอยๆ --}}
    <div class="particle-background" aria-hidden="true">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>

    {{-- Main Content Area - Full Width --}}
    <div class="min-h-screen relative z-10">
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
