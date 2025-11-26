<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ระบบปิดปรับปรุงชั่วคราว | {{ config('app.name', 'TP-Affiliate') }}</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

    <!-- Google Fonts -->
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    @php
        // ดึงการตั้งค่าเว็บไซต์
        $siteSettings = \App\Models\SiteSetting::getSetting();

        // ดึง AppMaintenance ถ้ามี
        $appMaintenance = null;
        if (class_exists(\App\Models\AppMaintenance::class)) {
            $appMaintenance = \App\Models\AppMaintenance::getInstance();
        }
    @endphp

    <style>
        * {
            font-family: 'Figtree', sans-serif;
        }

        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            .dark-mode-auto {
                background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
                color: #f1f5f9;
            }
        }

        /* Animated gradient background - ใช้สีส้ม/เหลืองสำหรับ maintenance */
        .gradient-bg {
            background: linear-gradient(135deg, #f97316 0%, #eab308 50%, #f97316 100%);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
        }

        .gradient-bg-dark {
            background: linear-gradient(135deg, #1e293b 0%, #422006 50%, #0f172a 100%);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Floating animation */
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(5deg); }
        }

        .float-animation {
            animation: float 3s ease-in-out infinite;
        }

        /* Gear rotation animation */
        @keyframes gear-rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .gear-rotate {
            animation: gear-rotate 8s linear infinite;
        }

        .gear-rotate-reverse {
            animation: gear-rotate 6s linear infinite reverse;
        }

        /* Pulse animation */
        @keyframes pulse-glow {
            0%, 100% {
                opacity: 1;
                box-shadow: 0 0 20px rgba(249, 115, 22, 0.4);
            }
            50% {
                opacity: 0.8;
                box-shadow: 0 0 40px rgba(249, 115, 22, 0.6);
            }
        }

        .pulse-glow {
            animation: pulse-glow 2s ease-in-out infinite;
        }

        /* Slide in animation */
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .slide-in-up {
            animation: slideInUp 0.6s ease-out forwards;
            opacity: 0;
        }

        /* Social icon hover effect */
        .social-icon {
            transition: all 0.3s ease;
        }

        .social-icon:hover {
            transform: translateY(-5px) scale(1.1);
        }

        /* Progress bar animation */
        @keyframes progress-bar {
            0% { width: 0%; }
            50% { width: 70%; }
            100% { width: 100%; }
        }

        .progress-animation {
            animation: progress-bar 3s ease-in-out infinite;
        }

        /* Wrench swing animation */
        @keyframes wrench-swing {
            0%, 100% { transform: rotate(-10deg); }
            50% { transform: rotate(10deg); }
        }

        .wrench-swing {
            animation: wrench-swing 1s ease-in-out infinite;
            transform-origin: 50% 100%;
        }

        /* Dark mode classes */
        .dark .gradient-bg {
            background: linear-gradient(135deg, #1e293b 0%, #422006 50%, #0f172a 100%);
        }

        .dark .text-gray-800 { color: #f1f5f9; }
        .dark .text-gray-600 { color: #cbd5e1; }
        .dark .text-gray-700 { color: #e2e8f0; }
        .dark .bg-white { background-color: #1e293b; }
        .dark .bg-gray-50 { background-color: #0f172a; }
        .dark .border-gray-200 { border-color: #334155; }
    </style>

    <script>
        // Dark mode initialization
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark')
        } else {
            document.documentElement.classList.remove('dark')
        }

        function toggleDarkMode() {
            if (document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark')
                localStorage.theme = 'light'
            } else {
                document.documentElement.classList.add('dark')
                localStorage.theme = 'dark'
            }
        }
    </script>
</head>
<body class="antialiased gradient-bg dark:gradient-bg-dark min-h-screen">
    <!-- Dark Mode Toggle Button -->
    <button
        onclick="toggleDarkMode()"
        class="fixed top-6 right-6 z-50 p-3 bg-white dark:bg-gray-800 rounded-full shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-110"
        aria-label="Toggle dark mode"
    >
        <i class="fas fa-moon text-gray-800 dark:text-yellow-300 text-lg"></i>
    </button>

    <div class="min-h-screen flex items-center justify-center px-4 py-12">
        <div class="max-w-4xl w-full">
            <!-- Maintenance Icon & Message -->
            <div class="text-center mb-8 slide-in-up" style="animation-delay: 0.1s;">
                <!-- Animated Maintenance Icon -->
                <div class="inline-block mb-8 float-animation">
                    <div class="relative">
                        <!-- Background circle with pulse effect -->
                        <div class="absolute inset-0 bg-white/20 dark:bg-gray-800/40 rounded-full blur-xl pulse-glow"></div>

                        <!-- Main icon container -->
                        <div class="relative bg-white dark:bg-gray-800 rounded-full p-8 shadow-2xl pulse-glow">
                            <div class="text-center relative">
                                <!-- Gear icons -->
                                <div class="absolute -top-4 -left-4">
                                    <i class="fas fa-cog text-4xl text-orange-400 gear-rotate"></i>
                                </div>
                                <div class="absolute -bottom-2 -right-2">
                                    <i class="fas fa-cog text-3xl text-yellow-400 gear-rotate-reverse"></i>
                                </div>

                                <!-- Main wrench icon -->
                                <i class="fas fa-tools text-6xl text-orange-600 dark:text-orange-400 wrench-swing"></i>

                                <!-- 503 text -->
                                <div class="text-4xl font-bold bg-gradient-to-r from-orange-600 to-yellow-500 dark:from-orange-400 dark:to-yellow-400 bg-clip-text text-transparent mt-2">
                                    503
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Maintenance Message -->
                <h1 class="text-4xl md:text-5xl font-bold text-white mb-4">
                    ระบบปิดปรับปรุงชั่วคราว
                </h1>
                <p class="text-xl text-white/90 dark:text-gray-300 mb-4">
                    @if($appMaintenance && $appMaintenance->title)
                        {{ $appMaintenance->title }}
                    @else
                        กรุณารอสักครู่ เรากำลังปรับปรุงระบบให้ดียิ่งขึ้น
                    @endif
                </p>

                @if($siteSettings->maintenance_message || ($appMaintenance && $appMaintenance->message))
                <div class="bg-white/10 dark:bg-gray-800/40 backdrop-blur-sm rounded-xl p-4 mb-4 max-w-2xl mx-auto">
                    <p class="text-white/80 dark:text-gray-300">
                        <i class="fas fa-info-circle mr-2 text-yellow-300"></i>
                        {{ $appMaintenance->message ?? $siteSettings->maintenance_message }}
                    </p>
                </div>
                @endif

                <!-- Progress Bar -->
                <div class="max-w-md mx-auto mb-8">
                    <div class="bg-white/20 dark:bg-gray-700/50 rounded-full h-3 overflow-hidden">
                        <div class="bg-gradient-to-r from-orange-400 to-yellow-400 h-full rounded-full progress-animation"></div>
                    </div>
                    <p class="text-white/70 text-sm mt-2">
                        <i class="fas fa-spinner fa-spin mr-1"></i>
                        กำลังดำเนินการ...
                    </p>
                </div>
            </div>

            <!-- Countdown Timer (ถ้ามี scheduled_end) -->
            @if($appMaintenance && $appMaintenance->scheduled_end && $appMaintenance->show_countdown)
            <div class="bg-white/10 dark:bg-gray-800/40 backdrop-blur-sm rounded-xl p-6 mb-8 text-center slide-in-up" style="animation-delay: 0.2s;">
                <h3 class="text-lg font-semibold text-white mb-4">
                    <i class="fas fa-clock mr-2"></i>
                    คาดว่าจะกลับมาในอีก
                </h3>
                <div class="flex justify-center gap-4" x-data="countdown('{{ $appMaintenance->scheduled_end->toISOString() }}')" x-init="init()">
                    <div class="bg-white/20 dark:bg-gray-700/50 rounded-lg p-4 min-w-[80px]">
                        <span class="text-3xl font-bold text-white" x-text="days">00</span>
                        <p class="text-white/70 text-sm">วัน</p>
                    </div>
                    <div class="bg-white/20 dark:bg-gray-700/50 rounded-lg p-4 min-w-[80px]">
                        <span class="text-3xl font-bold text-white" x-text="hours">00</span>
                        <p class="text-white/70 text-sm">ชั่วโมง</p>
                    </div>
                    <div class="bg-white/20 dark:bg-gray-700/50 rounded-lg p-4 min-w-[80px]">
                        <span class="text-3xl font-bold text-white" x-text="minutes">00</span>
                        <p class="text-white/70 text-sm">นาที</p>
                    </div>
                    <div class="bg-white/20 dark:bg-gray-700/50 rounded-lg p-4 min-w-[80px]">
                        <span class="text-3xl font-bold text-white" x-text="seconds">00</span>
                        <p class="text-white/70 text-sm">วินาที</p>
                    </div>
                </div>
            </div>
            @endif

            <!-- Social Media Links -->
            @if($siteSettings->facebook_url || $siteSettings->twitter_url || $siteSettings->instagram_url || $siteSettings->line_url || $siteSettings->youtube_url)
            <div class="bg-white/10 dark:bg-gray-800/40 backdrop-blur-sm rounded-xl p-6 mb-8 text-center slide-in-up" style="animation-delay: 0.3s;">
                <h3 class="text-lg font-semibold text-white mb-4">
                    <i class="fas fa-heart mr-2 text-red-400"></i>
                    ติดตามเราได้ที่
                </h3>
                <div class="flex justify-center gap-4 flex-wrap">
                    @if($siteSettings->facebook_url)
                    <a href="{{ $siteSettings->facebook_url }}"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="social-icon w-14 h-14 bg-gradient-to-br from-blue-600 to-blue-700 rounded-full flex items-center justify-center shadow-lg hover:shadow-blue-500/50">
                        <i class="fab fa-facebook-f text-2xl text-white"></i>
                    </a>
                    @endif

                    @if($siteSettings->twitter_url)
                    <a href="{{ $siteSettings->twitter_url }}"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="social-icon w-14 h-14 bg-gradient-to-br from-gray-800 to-black rounded-full flex items-center justify-center shadow-lg hover:shadow-gray-500/50">
                        <i class="fab fa-x-twitter text-2xl text-white"></i>
                    </a>
                    @endif

                    @if($siteSettings->instagram_url)
                    <a href="{{ $siteSettings->instagram_url }}"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="social-icon w-14 h-14 bg-gradient-to-br from-pink-500 via-red-500 to-yellow-500 rounded-full flex items-center justify-center shadow-lg hover:shadow-pink-500/50">
                        <i class="fab fa-instagram text-2xl text-white"></i>
                    </a>
                    @endif

                    @if($siteSettings->line_url)
                    <a href="{{ $siteSettings->line_url }}"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="social-icon w-14 h-14 bg-gradient-to-br from-green-500 to-green-600 rounded-full flex items-center justify-center shadow-lg hover:shadow-green-500/50">
                        <i class="fab fa-line text-2xl text-white"></i>
                    </a>
                    @endif

                    @if($siteSettings->youtube_url)
                    <a href="{{ $siteSettings->youtube_url }}"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="social-icon w-14 h-14 bg-gradient-to-br from-red-600 to-red-700 rounded-full flex items-center justify-center shadow-lg hover:shadow-red-500/50">
                        <i class="fab fa-youtube text-2xl text-white"></i>
                    </a>
                    @endif
                </div>
                <p class="text-white/60 text-sm mt-4">
                    ติดตามข่าวสารและอัพเดทล่าสุดจากเรา
                </p>
            </div>
            @endif

            <!-- Contact Info -->
            @if($siteSettings->contact_email || $siteSettings->contact_phone)
            <div class="bg-white/10 dark:bg-gray-800/40 backdrop-blur-sm rounded-xl p-6 mb-8 text-center slide-in-up" style="animation-delay: 0.4s;">
                <h3 class="text-lg font-semibold text-white mb-4">
                    <i class="fas fa-headset mr-2 text-cyan-400"></i>
                    ต้องการความช่วยเหลือ?
                </h3>
                <div class="flex justify-center gap-6 flex-wrap">
                    @if($siteSettings->contact_email)
                    <a href="mailto:{{ $siteSettings->contact_email }}"
                       class="inline-flex items-center px-6 py-3 bg-white/20 hover:bg-white/30 dark:bg-gray-700/50 dark:hover:bg-gray-700/70 text-white rounded-lg transition-all duration-300 hover:scale-105">
                        <i class="fas fa-envelope mr-3 text-xl"></i>
                        <div class="text-left">
                            <span class="text-xs text-white/70 block">อีเมล</span>
                            <span class="font-medium">{{ $siteSettings->contact_email }}</span>
                        </div>
                    </a>
                    @endif

                    @if($siteSettings->contact_phone)
                    <a href="tel:{{ $siteSettings->contact_phone }}"
                       class="inline-flex items-center px-6 py-3 bg-white/20 hover:bg-white/30 dark:bg-gray-700/50 dark:hover:bg-gray-700/70 text-white rounded-lg transition-all duration-300 hover:scale-105">
                        <i class="fas fa-phone mr-3 text-xl"></i>
                        <div class="text-left">
                            <span class="text-xs text-white/70 block">โทรศัพท์</span>
                            <span class="font-medium">{{ $siteSettings->contact_phone }}</span>
                        </div>
                    </a>
                    @endif
                </div>
            </div>
            @endif

            <!-- Notification Sign-up (ถ้าต้องการเพิ่มในอนาคต) -->
            <div class="bg-white/10 dark:bg-gray-800/40 backdrop-blur-sm rounded-xl p-6 mb-8 text-center slide-in-up" style="animation-delay: 0.5s;">
                <h3 class="text-lg font-semibold text-white mb-2">
                    <i class="fas fa-bell mr-2 text-yellow-300"></i>
                    กลับมาเร็วๆ นี้!
                </h3>
                <p class="text-white/70 text-sm">
                    ขออภัยในความไม่สะดวก เราจะกลับมาให้บริการเร็วที่สุด
                </p>

                <!-- Refresh Button -->
                <button
                    onclick="location.reload()"
                    class="mt-4 inline-flex items-center px-6 py-3 bg-gradient-to-r from-orange-500 to-yellow-500 hover:from-orange-600 hover:to-yellow-600 text-white font-semibold rounded-lg shadow-lg hover:shadow-xl transition-all duration-300 hover:-translate-y-1"
                >
                    <i class="fas fa-sync-alt mr-2"></i>
                    ลองอีกครั้ง
                </button>
            </div>

            <!-- Footer -->
            <div class="text-center mt-12 slide-in-up" style="animation-delay: 0.6s;">
                <p class="text-white/70 text-sm">
                    © {{ date('Y') }} {{ $siteSettings->site_name ?? config('app.name', 'TP-Affiliate') }}. All rights reserved.
                </p>
                <p class="text-white/50 text-xs mt-2">
                    <i class="fas fa-clock mr-1"></i>
                    {{ now()->format('Y-m-d H:i:s T') }}
                </p>
            </div>
        </div>
    </div>

    <!-- Decorative Elements -->
    <div class="fixed top-0 left-0 w-full h-full pointer-events-none overflow-hidden" style="z-index: -1;">
        <!-- Floating circles -->
        <div class="absolute top-20 left-10 w-32 h-32 bg-yellow-500/10 rounded-full blur-2xl animate-pulse"></div>
        <div class="absolute top-40 right-20 w-48 h-48 bg-orange-500/10 rounded-full blur-3xl animate-pulse" style="animation-delay: 1s;"></div>
        <div class="absolute bottom-20 left-1/4 w-40 h-40 bg-red-500/10 rounded-full blur-2xl animate-pulse" style="animation-delay: 2s;"></div>
        <div class="absolute bottom-32 right-1/3 w-36 h-36 bg-yellow-400/10 rounded-full blur-3xl animate-pulse" style="animation-delay: 1.5s;"></div>

        <!-- Floating tools -->
        <div class="absolute top-1/4 left-10 text-white/10">
            <i class="fas fa-wrench text-8xl gear-rotate"></i>
        </div>
        <div class="absolute bottom-1/4 right-10 text-white/10">
            <i class="fas fa-cog text-9xl gear-rotate-reverse"></i>
        </div>
    </div>

    <!-- Alpine.js สำหรับ Countdown -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.5/dist/cdn.min.js"></script>
    <script>
        // Countdown function สำหรับ Alpine.js
        function countdown(endDate) {
            return {
                days: '00',
                hours: '00',
                minutes: '00',
                seconds: '00',

                init() {
                    this.updateCountdown();
                    setInterval(() => this.updateCountdown(), 1000);
                },

                updateCountdown() {
                    const end = new Date(endDate).getTime();
                    const now = new Date().getTime();
                    const diff = end - now;

                    if (diff <= 0) {
                        // Time's up - refresh the page
                        location.reload();
                        return;
                    }

                    this.days = String(Math.floor(diff / (1000 * 60 * 60 * 24))).padStart(2, '0');
                    this.hours = String(Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60))).padStart(2, '0');
                    this.minutes = String(Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60))).padStart(2, '0');
                    this.seconds = String(Math.floor((diff % (1000 * 60)) / 1000)).padStart(2, '0');
                }
            }
        }
    </script>
</body>
</html>
