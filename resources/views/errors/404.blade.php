<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 - ไม่พบหน้าที่คุณต้องการ | {{ config('app.name', 'TP-Affiliate') }}</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

    <!-- Google Fonts -->
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

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

        /* Animated gradient background */
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
        }

        .gradient-bg-dark {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
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
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        .float-animation {
            animation: float 3s ease-in-out infinite;
        }

        /* Pulse animation */
        @keyframes pulse-slow {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .pulse-slow {
            animation: pulse-slow 2s ease-in-out infinite;
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
            animation: slideInUp 0.6s ease-out;
        }

        /* Hover effect for cards */
        .hover-lift {
            transition: all 0.3s ease;
        }

        .hover-lift:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        /* Menu item hover effect */
        .menu-item {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .menu-item::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .menu-item:hover::before {
            width: 300px;
            height: 300px;
        }

        .menu-item:hover {
            transform: scale(1.05);
        }

        /* Dark mode toggle */
        .dark .gradient-bg {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            background-size: 400% 400%;
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
            <!-- Error Icon & Message -->
            <div class="text-center mb-8 slide-in-up">
                <!-- Animated 404 Icon -->
                <div class="inline-block mb-8 float-animation">
                    <div class="relative">
                        <!-- Background circle with pulse effect -->
                        <div class="absolute inset-0 bg-white/20 dark:bg-gray-800/40 rounded-full blur-xl pulse-slow"></div>

                        <!-- Main icon container -->
                        <div class="relative bg-white dark:bg-gray-800 rounded-full p-8 shadow-2xl">
                            <div class="text-center">
                                <i class="fas fa-search text-6xl text-indigo-600 dark:text-indigo-400 mb-2"></i>
                                <div class="text-7xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 dark:from-indigo-400 dark:to-purple-400 bg-clip-text text-transparent">
                                    404
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Error Message -->
                <h1 class="text-4xl md:text-5xl font-bold text-white mb-4">
                    ไม่พบหน้าที่คุณต้องการ
                </h1>
                <p class="text-xl text-white/90 dark:text-gray-300 mb-8">
                    ขออภัย หน้าที่คุณกำลังมองหาอาจถูกย้าย ลบ หรือไม่เคยมีอยู่จริง
                </p>
            </div>

            <!-- Navigation Menu Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8 slide-in-up" style="animation-delay: 0.2s;">
                <!-- Home -->
                <a href="/" class="menu-item hover-lift bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg text-center group">
                    <div class="w-16 h-16 mx-auto mb-4 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                        <i class="fas fa-home text-2xl text-white"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-2">หน้าแรก</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">กลับสู่หน้าหลัก</p>
                </a>

                <!-- Dashboard -->
                <a href="{{ auth()->check() ? route('user.dashboard') : route('login') }}" class="menu-item hover-lift bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg text-center group">
                    <div class="w-16 h-16 mx-auto mb-4 bg-gradient-to-br from-purple-500 to-purple-600 rounded-full flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                        <i class="fas fa-tachometer-alt text-2xl text-white"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-2">แดชบอร์ด</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">จัดการบัญชี</p>
                </a>

                <!-- Contact -->
                <a href="{{ route('frontend.contact') ?? '#' }}" class="menu-item hover-lift bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg text-center group">
                    <div class="w-16 h-16 mx-auto mb-4 bg-gradient-to-br from-green-500 to-green-600 rounded-full flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                        <i class="fas fa-envelope text-2xl text-white"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-2">ติดต่อเรา</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">ส่งข้อความหาเรา</p>
                </a>

                <!-- Help -->
                <a href="{{ route('user.tickets.index') ?? '#' }}" class="menu-item hover-lift bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg text-center group">
                    <div class="w-16 h-16 mx-auto mb-4 bg-gradient-to-br from-orange-500 to-orange-600 rounded-full flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                        <i class="fas fa-question-circle text-2xl text-white"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-2">ช่วยเหลือ</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">ศูนย์ช่วยเหลือ</p>
                </a>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center mb-8 slide-in-up" style="animation-delay: 0.4s;">
                <!-- Go Back Button -->
                <button
                    onclick="window.history.back()"
                    class="group inline-flex items-center px-8 py-4 bg-white dark:bg-gray-800 text-gray-800 dark:text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 hover:-translate-y-1"
                >
                    <i class="fas fa-arrow-left mr-3 group-hover:-translate-x-1 transition-transform duration-300"></i>
                    ย้อนกลับ
                </button>

                <!-- Go to Homepage Button -->
                <a
                    href="/"
                    class="group inline-flex items-center px-8 py-4 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 hover:-translate-y-1"
                >
                    <i class="fas fa-home mr-3"></i>
                    ไปหน้าแรก
                    <i class="fas fa-arrow-right ml-3 group-hover:translate-x-1 transition-transform duration-300"></i>
                </a>
            </div>

            <!-- Quick Links -->
            <div class="bg-white/10 dark:bg-gray-800/40 backdrop-blur-sm rounded-xl p-6 text-center slide-in-up" style="animation-delay: 0.6s;">
                <h3 class="text-lg font-semibold text-white mb-4">ลิงก์ยอดนิยม</h3>
                <div class="flex flex-wrap justify-center gap-3">
                    @auth
                        <a href="{{ route('user.dashboard') }}" class="px-4 py-2 bg-white/20 hover:bg-white/30 dark:bg-gray-700/50 dark:hover:bg-gray-700/70 text-white rounded-lg text-sm transition-all duration-300 hover:scale-105">
                            <i class="fas fa-chart-line mr-2"></i>Dashboard
                        </a>
                        <a href="{{ route('user.genealogy') }}" class="px-4 py-2 bg-white/20 hover:bg-white/30 dark:bg-gray-700/50 dark:hover:bg-gray-700/70 text-white rounded-lg text-sm transition-all duration-300 hover:scale-105">
                            <i class="fas fa-sitemap mr-2"></i>Genealogy
                        </a>
                        <a href="{{ route('user.wallet.index') }}" class="px-4 py-2 bg-white/20 hover:bg-white/30 dark:bg-gray-700/50 dark:hover:bg-gray-700/70 text-white rounded-lg text-sm transition-all duration-300 hover:scale-105">
                            <i class="fas fa-wallet mr-2"></i>Wallet
                        </a>
                        <a href="{{ route('user.profile') }}" class="px-4 py-2 bg-white/20 hover:bg-white/30 dark:bg-gray-700/50 dark:hover:bg-gray-700/70 text-white rounded-lg text-sm transition-all duration-300 hover:scale-105">
                            <i class="fas fa-user mr-2"></i>Profile
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="px-4 py-2 bg-white/20 hover:bg-white/30 dark:bg-gray-700/50 dark:hover:bg-gray-700/70 text-white rounded-lg text-sm transition-all duration-300 hover:scale-105">
                            <i class="fas fa-sign-in-alt mr-2"></i>เข้าสู่ระบบ
                        </a>
                        <a href="{{ route('register') }}" class="px-4 py-2 bg-white/20 hover:bg-white/30 dark:bg-gray-700/50 dark:hover:bg-gray-700/70 text-white rounded-lg text-sm transition-all duration-300 hover:scale-105">
                            <i class="fas fa-user-plus mr-2"></i>สมัครสมาชิก
                        </a>
                    @endauth
                    <a href="{{ route('sitemap') ?? '#' }}" class="px-4 py-2 bg-white/20 hover:bg-white/30 dark:bg-gray-700/50 dark:hover:bg-gray-700/70 text-white rounded-lg text-sm transition-all duration-300 hover:scale-105">
                        <i class="fas fa-map mr-2"></i>Sitemap
                    </a>
                </div>
            </div>

            <!-- Error Details (Optional - can be shown for debugging) -->
            @if(config('app.debug'))
            <div class="mt-8 bg-white/10 dark:bg-gray-800/40 backdrop-blur-sm rounded-xl p-6 slide-in-up" style="animation-delay: 0.8s;">
                <details class="text-white">
                    <summary class="cursor-pointer font-semibold mb-2">
                        <i class="fas fa-info-circle mr-2"></i>รายละเอียดสำหรับ Developer
                    </summary>
                    <div class="mt-4 text-sm bg-black/20 rounded-lg p-4 font-mono">
                        <p><strong>Requested URL:</strong> {{ request()->fullUrl() }}</p>
                        <p><strong>Method:</strong> {{ request()->method() }}</p>
                        <p><strong>Time:</strong> {{ now()->format('Y-m-d H:i:s T') }}</p>
                        <p><strong>IP Address:</strong> {{ request()->ip() }}</p>
                    </div>
                </details>
            </div>
            @endif

            <!-- Footer -->
            <div class="text-center mt-12 slide-in-up" style="animation-delay: 1s;">
                <p class="text-white/70 text-sm">
                    © {{ date('Y') }} {{ config('app.name', 'TP-Affiliate') }}. All rights reserved.
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
        <div class="absolute top-20 left-10 w-32 h-32 bg-white/5 rounded-full blur-2xl animate-pulse"></div>
        <div class="absolute top-40 right-20 w-48 h-48 bg-purple-500/10 rounded-full blur-3xl animate-pulse" style="animation-delay: 1s;"></div>
        <div class="absolute bottom-20 left-1/4 w-40 h-40 bg-indigo-500/10 rounded-full blur-2xl animate-pulse" style="animation-delay: 2s;"></div>
        <div class="absolute bottom-32 right-1/3 w-36 h-36 bg-blue-500/10 rounded-full blur-3xl animate-pulse" style="animation-delay: 1.5s;"></div>
    </div>
</body>
</html>
