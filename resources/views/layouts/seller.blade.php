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

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- GSAP -->
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js"></script>

    @php
        $primaryStart = \App\Models\Setting::get('theme_primary_start', '#3B82F6');
        $primaryEnd = \App\Models\Setting::get('theme_primary_end', '#1D4ED8');
        $secondaryStart = \App\Models\Setting::get('theme_secondary_start', '#10B981');
        $secondaryEnd = \App\Models\Setting::get('theme_secondary_end', '#059669');
        $accentStart = \App\Models\Setting::get('theme_accent_start', '#8B5CF6');
        $accentEnd = \App\Models\Setting::get('theme_accent_end', '#6D28D9');
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
    </script>
</head>
<body class="font-sans antialiased bg-gray-50">
    <div class="min-h-screen" x-data="{ mobileMenuOpen: false, showNotifications: false }">
        <!-- Mobile Header -->
        <header class="bg-gradient-primary text-white sticky top-0 z-40 shadow-lg">
            <div class="flex items-center justify-between px-4 py-3">
                <!-- Logo -->
                <div class="flex items-center space-x-3">
                    <button @click="mobileMenuOpen = !mobileMenuOpen" class="lg:hidden">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    <x-logo height="h-8" />
                </div>

                <!-- Right Actions -->
                <div class="flex items-center space-x-2">
                    <!-- Dashboard Switcher -->
                    <x-dashboard-switcher />

                    <!-- Language Switcher -->
                    @include('components.language-switcher')

                    <!-- Notifications -->
                    <button @click="showNotifications = !showNotifications" class="relative p-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        <span class="absolute top-0 right-0 block h-2 w-2 bg-red-500 rounded-full"></span>
                    </button>
                </div>
            </div>
        </header>

        <!-- Mobile Menu Overlay -->
        <div x-show="mobileMenuOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="mobileMenuOpen = false"
             class="fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden"
             style="display: none;">
        </div>

        <!-- Mobile Menu -->
        <nav x-show="mobileMenuOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="-translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="-translate-x-full"
             class="fixed inset-y-0 left-0 w-64 bg-white shadow-xl z-50 transform lg:hidden"
             style="display: none;">

            <div class="p-4 bg-gradient-primary text-white">
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center text-cyan-600 font-bold text-lg">
                        {{ substr(Auth::user()->name, 0, 1) }}
                    </div>
                    <div>
                        <p class="font-semibold">{{ Auth::user()->name }}</p>
                        <p class="text-sm opacity-90">ผู้ขาย</p>
                    </div>
                </div>
            </div>

            <div class="py-4">
                <a href="{{ route('seller.dashboard') }}" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100 {{ request()->routeIs('seller.dashboard') ? 'bg-gray-100 border-l-4 border-cyan-600' : '' }}">
                    <span class="text-xl mr-3">📊</span>
                    <span>แดชบอร์ด</span>
                </a>

                <a href="{{ route('seller.products') }}" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100 {{ request()->routeIs('seller.products') ? 'bg-gray-100 border-l-4 border-cyan-600' : '' }}">
                    <span class="text-xl mr-3">📦</span>
                    <span>สินค้า</span>
                </a>

                <a href="{{ route('seller.sales') }}" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100 {{ request()->routeIs('seller.sales') ? 'bg-gray-100 border-l-4 border-cyan-600' : '' }}">
                    <span class="text-xl mr-3">🛒</span>
                    <span>ยอดขาย</span>
                </a>

                <a href="{{ route('seller.analytics') }}" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100 {{ request()->routeIs('seller.analytics') ? 'bg-gray-100 border-l-4 border-cyan-600' : '' }}">
                    <span class="text-xl mr-3">📈</span>
                    <span>วิเคราะห์</span>
                </a>

                <a href="{{ route('seller.profile') }}" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100 {{ request()->routeIs('seller.profile') ? 'bg-gray-100 border-l-4 border-cyan-600' : '' }}">
                    <span class="text-xl mr-3">👤</span>
                    <span>โปรไฟล์</span>
                </a>

                <div class="border-t mt-4 pt-4">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex items-center w-full px-6 py-3 text-gray-700 hover:bg-gray-100">
                            <span class="text-xl mr-3">🚪</span>
                            <span>ออกจากระบบ</span>
                        </button>
                    </form>
                </div>
            </div>
        </nav>

        <!-- Desktop Sidebar -->
        <aside class="hidden lg:block fixed left-0 top-0 h-screen w-64 bg-white shadow-lg z-30">
            <div class="p-4 bg-gradient-primary text-white">
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center text-cyan-600 font-bold text-lg">
                        {{ substr(Auth::user()->name, 0, 1) }}
                    </div>
                    <div>
                        <p class="font-semibold">{{ Auth::user()->name }}</p>
                        <p class="text-sm opacity-90">ผู้ขาย</p>
                    </div>
                </div>
            </div>

            <nav class="py-4">
                <a href="{{ route('seller.dashboard') }}" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100 {{ request()->routeIs('seller.dashboard') ? 'bg-gray-100 border-l-4 border-cyan-600' : '' }}">
                    <span class="text-xl mr-3">📊</span>
                    <span>แดชบอร์ด</span>
                </a>

                <a href="{{ route('seller.products') }}" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100 {{ request()->routeIs('seller.products') ? 'bg-gray-100 border-l-4 border-cyan-600' : '' }}">
                    <span class="text-xl mr-3">📦</span>
                    <span>สินค้า</span>
                </a>

                <a href="{{ route('seller.sales') }}" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100 {{ request()->routeIs('seller.sales') ? 'bg-gray-100 border-l-4 border-cyan-600' : '' }}">
                    <span class="text-xl mr-3">🛒</span>
                    <span>ยอดขาย</span>
                </a>

                <a href="{{ route('seller.analytics') }}" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100 {{ request()->routeIs('seller.analytics') ? 'bg-gray-100 border-l-4 border-cyan-600' : '' }}">
                    <span class="text-xl mr-3">📈</span>
                    <span>วิเคราะห์</span>
                </a>

                <a href="{{ route('seller.profile') }}" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100 {{ request()->routeIs('seller.profile') ? 'bg-gray-100 border-l-4 border-cyan-600' : '' }}">
                    <span class="text-xl mr-3">👤</span>
                    <span>โปรไฟล์</span>
                </a>

                <div class="border-t mt-4 pt-4">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex items-center w-full px-6 py-3 text-gray-700 hover:bg-gray-100">
                            <span class="text-xl mr-3">🚪</span>
                            <span>ออกจากระบบ</span>
                        </button>
                    </form>
                </div>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="lg:ml-64 min-h-screen">
            <div class="p-4 lg:p-6">
                @if (session('success'))
                    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg">
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
                        {{ session('error') }}
                    </div>
                @endif

                @yield('content')
            </div>
        </main>

        <!-- Bottom Navigation (Mobile Only) -->
        <nav class="lg:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 z-30">
            <div class="flex justify-around">
                <a href="{{ route('seller.dashboard') }}" class="flex flex-col items-center py-2 px-3 {{ request()->routeIs('seller.dashboard') ? 'text-cyan-600' : 'text-gray-600' }}">
                    <span class="text-2xl">📊</span>
                    <span class="text-xs mt-1">แดชบอร์ด</span>
                </a>

                <a href="{{ route('seller.products') }}" class="flex flex-col items-center py-2 px-3 {{ request()->routeIs('seller.products') ? 'text-cyan-600' : 'text-gray-600' }}">
                    <span class="text-2xl">📦</span>
                    <span class="text-xs mt-1">สินค้า</span>
                </a>

                <a href="{{ route('seller.sales') }}" class="flex flex-col items-center py-2 px-3 {{ request()->routeIs('seller.sales') ? 'text-cyan-600' : 'text-gray-600' }}">
                    <span class="text-2xl">🛒</span>
                    <span class="text-xs mt-1">ยอดขาย</span>
                </a>

                <a href="{{ route('seller.profile') }}" class="flex flex-col items-center py-2 px-3 {{ request()->routeIs('seller.profile') ? 'text-cyan-600' : 'text-gray-600' }}">
                    <span class="text-2xl">👤</span>
                    <span class="text-xs mt-1">โปรไฟล์</span>
                </a>
            </div>
        </nav>
    </div>

    <script>
        // GSAP Animations
        document.addEventListener('DOMContentLoaded', function() {
            // Fade in content
            gsap.from('main > div > *', {
                opacity: 0,
                y: 20,
                duration: 0.5,
                stagger: 0.1,
                ease: 'power2.out'
            });
        });
    </script>

    @stack('scripts')
</body>
</html>
