<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title') - Admin - {{ \App\Models\Setting::get('app_name', 'TP-Affiliate') }}</title>

    @php
        $favicon = \App\Models\Setting::get('favicon');
    @endphp
    @if($favicon)
        <link rel="icon" type="image/x-icon" href="{{ asset($favicon) }}">
    @endif

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Bootstrap CSS (for hotel management views) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Prevent Tailwind-Bootstrap conflicts -->
    <style>
        /* Ensure Bootstrap components work properly */
        .bootstrap-wrapper .container,
        .bootstrap-wrapper .container-fluid,
        .bootstrap-wrapper .row,
        .bootstrap-wrapper [class*="col-"] {
            all: revert;
        }
    </style>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.13.3/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- GSAP -->
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/ScrollTrigger.min.js"></script>

    {{-- Dark Mode System --}}
    <x-dark-mode-init />
    <x-dark-mode-styles />

    <style>
        /* Alpine.js x-cloak */
        [x-cloak] {
            display: none !important;
        }

        /* Smooth transitions */
        * {
            transition-property: background-color, border-color, color, fill, stroke;
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
            transition-duration: 200ms;
        }

        /* Mobile-first responsive utilities */
        @media (max-width: 768px) {
            .mobile-padding {
                padding: 1rem;
            }
        }

        /* Smooth scrolling */
        html {
            scroll-behavior: smooth;
        }

        /* Touch-friendly tap targets */
        button, a {
            min-height: 44px;
            min-width: 44px;
        }
    </style>

    @stack('styles')

    <style>
        /* Ensure Windows Taskbar doesn't overlap with content */
        @php
            $taskbarPosition = \App\Models\WindowsUiSetting::get('windows_taskbar_position', 'top');
            $taskbarHeight = \App\Models\WindowsUiSetting::get('windows_taskbar_height', 48);

            // Content width settings
            $contentWidthMode = \App\Models\WindowsUiSetting::get('content_width_mode', 'container');
            $contentWidthCustom = \App\Models\WindowsUiSetting::get('content_width_custom', 1400);
        @endphp

        @if($taskbarPosition === 'top')
        body {
            padding-top: {{ $taskbarHeight }}px;
        }
        @else
        body {
            padding-bottom: {{ $taskbarHeight }}px;
        }
        @endif
    </style>
</head>
<body class="font-sans antialiased bg-gray-100 dark:bg-gray-900 transition-colors duration-300">
    <!-- Spaceship Background -->
    <x-spaceship-background />

    @php
        $user = auth()->user();
        $userTheme = $user->menu_theme_preference ?? 'millennium';

        // Debug logging
        \Log::info('Admin layout loading', [
            'user_id' => $user->id,
            'theme' => $userTheme,
            'raw_value' => $user->menu_theme_preference
        ]);
    @endphp


    @if($userTheme === 'classic_x')
        <!-- Classic X Sidebar -->
        <x-classic-x-sidebar type="admin" />

        <!-- Main Content Area (with sidebar margin) -->
        <div class="classic-x-content" id="classicXContent">
            <!-- Top Bar -->
            <header class="bg-white dark:bg-gray-800 shadow-sm sticky top-0 z-20">
                <div class="@if($contentWidthMode === 'max') w-full @elseif($contentWidthMode === 'custom') mx-auto @else max-w-7xl mx-auto @endif px-4 sm:px-6 lg:px-8 py-4"
                     @if($contentWidthMode === 'custom') style="max-width: {{ $contentWidthCustom }}px;" @endif>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <h1 class="text-2xl md:text-3xl font-bold text-gray-800 dark:text-white">@yield('title')</h1>
                        </div>

                        <div class="flex items-center space-x-3">
                            <!-- Dashboard Switcher -->
                            <x-dashboard-switcher />

                            <!-- Notification Bell -->
                            <x-notification-bell />

                            <!-- Language Switcher -->
                            <div class="relative z-[60]">
                                <x-language-switcher-pro />
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="@if($contentWidthMode === 'max') w-full @elseif($contentWidthMode === 'custom') mx-auto @else max-w-7xl mx-auto @endif px-4 sm:px-6 lg:px-8 py-6"
                  @if($contentWidthMode === 'custom') style="max-width: {{ $contentWidthCustom }}px;" @endif>
                @yield('content')
            </main>
        </div>

        <!-- Floating Action Buttons for Classic X Theme Only -->
        <x-classic-x-floating-buttons />
    @else
        <!-- Millennium Taskbar -->
        <x-millennium-taskbar type="admin" />

        <!-- Page Loader -->
        <x-page-loader />

        <div class="min-h-screen">
            <!-- Top Bar -->
            <header class="bg-white dark:bg-gray-800 shadow-sm sticky top-0 z-20">
                <div class="@if($contentWidthMode === 'max') w-full @elseif($contentWidthMode === 'custom') mx-auto @else max-w-7xl mx-auto @endif px-4 sm:px-6 lg:px-8 py-4"
                     @if($contentWidthMode === 'custom') style="max-width: {{ $contentWidthCustom }}px;" @endif>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <h1 class="text-2xl md:text-3xl font-bold text-gray-800 dark:text-white">@yield('title')</h1>
                        </div>

                        <div class="flex items-center space-x-3">
                            <!-- Dashboard Switcher -->
                            <x-dashboard-switcher />

                            <!-- Notification Bell -->
                            <x-notification-bell />

                            <!-- Language Switcher -->
                            <div class="relative z-[60]">
                                <x-language-switcher-pro />
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="@if($contentWidthMode === 'max') w-full @elseif($contentWidthMode === 'custom') mx-auto @else max-w-7xl mx-auto @endif px-4 sm:px-6 lg:px-8 py-6"
                  @if($contentWidthMode === 'custom') style="max-width: {{ $contentWidthCustom }}px;" @endif>
                @yield('content')
            </main>
        </div>
    @endif

    <!-- Fixed Floating Toast Notifications -->
    <div class="fixed top-4 right-4 z-[9999] space-y-3 max-w-md">
        @if (session('success'))
            <div x-data="{ show: true }"
                 x-show="show"
                 x-init="setTimeout(() => show = false, 5000)"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform translate-x-full"
                 x-transition:enter-end="opacity-100 transform translate-x-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 transform translate-x-0"
                 x-transition:leave-end="opacity-0 transform translate-x-full"
                 class="bg-green-100 dark:bg-green-900 border-l-4 border-green-500 text-green-700 dark:text-green-100 px-4 py-3 rounded-lg shadow-lg flex items-start justify-between min-w-[320px]">
                <div class="flex items-start">
                    <svg class="h-6 w-6 text-green-500 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="font-medium">{{ session('success') }}</span>
                </div>
                <button @click="show = false" class="ml-4 text-green-700 dark:text-green-100 hover:text-green-900 flex-shrink-0">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        @endif

        @if (session('error'))
            <div x-data="{ show: true }"
                 x-show="show"
                 x-init="setTimeout(() => show = false, 5000)"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform translate-x-full"
                 x-transition:enter-end="opacity-100 transform translate-x-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 transform translate-x-0"
                 x-transition:leave-end="opacity-0 transform translate-x-full"
                 class="bg-red-100 dark:bg-red-900 border-l-4 border-red-500 text-red-700 dark:text-red-100 px-4 py-3 rounded-lg shadow-lg flex items-start justify-between min-w-[320px]">
                <div class="flex items-start">
                    <svg class="h-6 w-6 text-red-500 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="font-medium">{{ session('error') }}</span>
                </div>
                <button @click="show = false" class="ml-4 text-red-700 dark:text-red-100 hover:text-red-900 flex-shrink-0">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        @endif

        @if (session('info'))
            <div x-data="{ show: true }"
                 x-show="show"
                 x-init="setTimeout(() => show = false, 5000)"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform translate-x-full"
                 x-transition:enter-end="opacity-100 transform translate-x-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 transform translate-x-0"
                 x-transition:leave-end="opacity-0 transform translate-x-full"
                 class="bg-blue-100 dark:bg-blue-900 border-l-4 border-blue-500 text-blue-700 dark:text-blue-100 px-4 py-3 rounded-lg shadow-lg flex items-start justify-between min-w-[320px]">
                <div class="flex items-start">
                    <svg class="h-6 w-6 text-blue-500 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="font-medium">{{ session('info') }}</span>
                </div>
                <button @click="show = false" class="ml-4 text-blue-700 dark:text-blue-100 hover:text-blue-900 flex-shrink-0">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        @endif
    </div>

    <script>
        // Chart.js Dark Mode Helpers
        window.isDarkMode = function() {
            return document.documentElement.classList.contains('dark');
        };

        window.getChartColors = function() {
            const isDark = window.isDarkMode();
            return {
                textColor: isDark ? '#e2e8f0' : '#374151',
                gridColor: isDark ? 'rgba(148, 163, 184, 0.1)' : 'rgba(0, 0, 0, 0.05)',
                tooltipBg: isDark ? 'rgba(30, 41, 59, 0.95)' : 'rgba(0, 0, 0, 0.8)',
                borderColor: isDark ? '#475569' : '#e5e7eb',
                chartBorderColor: isDark ? '#1e293b' : '#fff'
            };
        };

        // GSAP Animations
        gsap.registerPlugin(ScrollTrigger);

        document.addEventListener('DOMContentLoaded', function() {
            // Subtle entrance animation
            gsap.from('main > *', {
                y: 10,
                duration: 0.3,
                stagger: 0.05,
                ease: 'power2.out',
                clearProps: 'all'
            });

            // Subtle hover effects on buttons
            const buttons = document.querySelectorAll('button:not([type=submit]), .btn');
            buttons.forEach(button => {
                button.addEventListener('mouseenter', () => {
                    gsap.to(button, { scale: 1.03, duration: 0.15 });
                });
                button.addEventListener('mouseleave', () => {
                    gsap.to(button, { scale: 1, duration: 0.15 });
                });
            });
        });
    </script>

    <!-- jQuery (for Bootstrap and hotel views) -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>

    <!-- Bootstrap JS Bundle (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Dark Mode Toggle Function for Windows UI
        function toggleDarkMode() {
            const isDark = document.documentElement.classList.contains('dark');

            if (isDark) {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('darkMode', 'light');
            } else {
                document.documentElement.classList.add('dark');
                localStorage.setItem('darkMode', 'dark');
            }
        }
    </script>

    {{-- Emergency Alert System --}}
    <x-emergency-alert-banner position="global" />
    <x-emergency-alert-popup position="global" />
    <x-emergency-alert-marquee position="global" />

    {{-- Notification System --}}
    @auth
        <x-immediate-notification-popup />
    @endauth

    @stack('scripts')
</body>
</html>
