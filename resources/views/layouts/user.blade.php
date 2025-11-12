<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">

    <title>@yield('title') - {{ \App\Models\Setting::get('app_name', 'TP-Affiliate') }}</title>

    @php
        $favicon = \App\Models\Setting::get('favicon');
    @endphp
    @if($favicon)
        <link rel="icon" type="image/x-icon" href="{{ asset($favicon) }}">
        <link rel="apple-touch-icon" href="{{ asset($favicon) }}">
    @endif

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.13.3/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- GSAP -->
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js"></script>

    {{-- Dark Mode System --}}
    <x-dark-mode-init />
    <x-dark-mode-styles />

    @php
        // Gradient color variables from settings with defaults
        $primaryStart = \App\Models\Setting::get('theme_primary_start', '#3B82F6');
        $primaryEnd = \App\Models\Setting::get('theme_primary_end', '#1D4ED8');
        $secondaryStart = \App\Models\Setting::get('theme_secondary_start', '#10B981');
        $secondaryEnd = \App\Models\Setting::get('theme_secondary_end', '#059669');
        $accentStart = \App\Models\Setting::get('theme_accent_start', '#F59E0B');
        $accentEnd = \App\Models\Setting::get('theme_accent_end', '#D97706');
    @endphp

    <style>
        :root {
            --gradient-primary: linear-gradient(135deg, {{ $primaryStart }}, {{ $primaryEnd }});
            --gradient-secondary: linear-gradient(135deg, {{ $secondaryStart }}, {{ $secondaryEnd }});
            --gradient-accent: linear-gradient(135deg, {{ $accentStart }}, {{ $accentEnd }});
        }

        .bg-gradient-primary {
            background: var(--gradient-primary);
        }

        .bg-gradient-secondary {
            background: var(--gradient-secondary);
        }

        .bg-gradient-accent {
            background: var(--gradient-accent);
        }

        /* Dark Mode Variables */
        .dark {
            color-scheme: dark;
        }

        .dark body {
            background-color: #0f172a;
            color: #e2e8f0;
        }

        .dark .bg-gray-100 {
            background-color: #1e293b;
        }

        .dark .bg-gray-50 {
            background-color: #0f172a;
        }

        .dark .bg-white {
            background-color: #1e293b;
            color: #e2e8f0;
        }

        .dark .text-gray-800 {
            color: #e2e8f0;
        }

        .dark .text-gray-900 {
            color: #f1f5f9;
        }

        .dark .text-gray-700 {
            color: #cbd5e1;
        }

        .dark .text-gray-600 {
            color: #94a3b8;
        }

        .dark .text-gray-500 {
            color: #94a3b8;
        }

        .dark .border-gray-200 {
            border-color: #334155;
        }

        .dark .divide-gray-200 {
            border-color: #334155;
        }

        .dark .shadow-sm {
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.5);
        }

        .dark .shadow-md {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.5), 0 2px 4px -1px rgba(0, 0, 0, 0.3);
        }

        .dark .shadow-lg {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.5), 0 4px 6px -2px rgba(0, 0, 0, 0.3);
        }

        .dark .shadow-xl {
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5), 0 10px 10px -5px rgba(0, 0, 0, 0.4);
        }

        .dark .hover\:bg-gray-50:hover {
            background-color: #334155;
        }

        /* Dark mode for tables */
        .dark table thead {
            background-color: #0f172a;
        }

        .dark table tbody tr:hover {
            background-color: #334155;
        }

        .dark input,
        .dark select,
        .dark textarea {
            background-color: #1e293b;
            border-color: #334155;
            color: #e2e8f0;
        }

        .dark input:focus,
        .dark select:focus,
        .dark textarea:focus {
            border-color: #6366f1;
            background-color: #1e293b;
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

        /* Smooth transitions */
        * {
            transition-property: background-color, border-color, color, fill, stroke;
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
            transition-duration: 200ms;
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
        $userTheme = auth()->user()->menu_theme_preference ?? 'millennium';
    @endphp

    @if($userTheme === 'classic_x')
        <!-- Classic X Sidebar -->
        <x-classic-x-sidebar type="user" />

        <!-- Classic X Content Wrapper -->
        <div class="classic-x-content" id="classicXContent">
    @else
        <!-- Millennium Taskbar -->
        <x-millennium-taskbar type="user" />
    @endif

    <!-- Page Loader -->
    <x-page-loader />

    <div class="min-h-screen">
        <!-- Top Bar -->
        <header class="bg-white dark:bg-gray-800 shadow-sm sticky top-0 z-50">
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

    @if($userTheme === 'classic_x')
        </div> <!-- Close classic-x-content wrapper -->
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
                 class="bg-green-100 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded-lg shadow-lg flex items-start justify-between min-w-[320px]">
                <div class="flex items-start">
                    <svg class="h-6 w-6 text-green-500 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="font-medium">{{ session('success') }}</span>
                </div>
                <button @click="show = false" class="ml-4 text-green-700 hover:text-green-900 flex-shrink-0">
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
                 class="bg-red-100 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded-lg shadow-lg flex items-start justify-between min-w-[320px]">
                <div class="flex items-start">
                    <svg class="h-6 w-6 text-red-500 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="font-medium">{{ session('error') }}</span>
                </div>
                <button @click="show = false" class="ml-4 text-red-700 hover:text-red-900 flex-shrink-0">
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

    {{-- Immediate Notification Popup --}}
    <x-immediate-notification-popup />

    @stack('scripts')
</body>
</html>
