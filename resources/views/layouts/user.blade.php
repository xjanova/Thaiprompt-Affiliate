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

                    @php
                        $logo = \App\Models\Setting::get('logo');
                    @endphp
                    @if($logo)
                        <img src="{{ asset($logo) }}" alt="Logo" class="h-8 object-contain">
                    @else
                        <span class="text-xl font-bold">{{ \App\Models\Setting::get('app_name', 'TP-Affiliate') }}</span>
                    @endif
                </div>

                <!-- Right Actions -->
                <div class="flex items-center space-x-2">
                    <!-- Notification Bell -->
                    <x-notification-bell />

                    <!-- Language Switcher -->
                    <x-language-switcher-pro />
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
                    <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center text-indigo-600 font-bold text-lg">
                        {{ substr(Auth::user()->name, 0, 1) }}
                    </div>
                    <div>
                        <p class="font-semibold">{{ Auth::user()->name }}</p>
                        <p class="text-sm opacity-90">{{ Auth::user()->email }}</p>
                    </div>
                </div>
            </div>

            <div class="py-4">
                <a href="{{ route('user.dashboard') }}" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100 {{ request()->routeIs('user.dashboard') ? 'bg-gray-100 border-l-4 border-indigo-600' : '' }}">
                    <span class="text-xl mr-3">📊</span>
                    <span>แดชบอร์ด</span>
                </a>

                <a href="{{ route('user.profile') }}" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100 {{ request()->routeIs('user.profile') ? 'bg-gray-100 border-l-4 border-indigo-600' : '' }}">
                    <span class="text-xl mr-3">👤</span>
                    <span>โปรไฟล์</span>
                </a>

                <a href="{{ route('user.commissions') }}" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100 {{ request()->routeIs('user.commissions') ? 'bg-gray-100 border-l-4 border-indigo-600' : '' }}">
                    <span class="text-xl mr-3">💰</span>
                    <span>คอมมิชชั่น</span>
                </a>

                <!-- Wallet Dropdown Menu -->
                <div x-data="{ walletOpen: false }" class="relative">
                    <!-- Main Wallet Button -->
                    <button @click="walletOpen = !walletOpen" class="flex items-center w-full px-6 py-3 text-gray-700 hover:bg-gray-100 {{ request()->routeIs('user.wallet.*') ? 'bg-gray-100 border-l-4 border-indigo-600' : '' }}">
                        <span class="text-xl mr-3">💳</span>
                        <span class="flex-1 text-left">กระเป๋าเงิน</span>
                        <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': walletOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <!-- Dropdown Submenu -->
                    <div x-show="walletOpen"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 transform -translate-y-2"
                         x-transition:enter-end="opacity-100 transform translate-y-0"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         class="mt-2 mb-2 ml-6 space-y-1 bg-gray-50 rounded-lg p-2 border-l-2 border-indigo-200"
                         style="display: none;">

                        <a href="{{ route('user.wallet.index') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 rounded-md {{ request()->routeIs('user.wallet.index') ? 'bg-indigo-100 text-indigo-700 font-semibold' : '' }}">
                            <span class="mr-2">👛</span>
                            <span>กระเป๋าของฉัน</span>
                        </a>

                        <a href="{{ route('user.wallet.transactions') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 rounded-md {{ request()->routeIs('user.wallet.transactions') ? 'bg-indigo-100 text-indigo-700 font-semibold' : '' }}">
                            <span class="mr-2">📝</span>
                            <span>ประวัติธุรกรรม</span>
                        </a>

                        <div class="border-t border-gray-300 my-2"></div>

                        <a href="{{ route('user.wallet.withdraw') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 rounded-md {{ request()->routeIs('user.wallet.withdraw') ? 'bg-indigo-100 text-indigo-700 font-semibold' : '' }}">
                            <span class="mr-2">💸</span>
                            <span>ถอนเงิน</span>
                        </a>

                        <a href="{{ route('user.wallet.withdrawals') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 rounded-md {{ request()->routeIs('user.wallet.withdrawals') ? 'bg-indigo-100 text-indigo-700 font-semibold' : '' }}">
                            <span class="mr-2">📋</span>
                            <span>ประวัติการถอน</span>
                        </a>

                        <div class="border-t border-gray-300 my-2"></div>

                        <a href="{{ route('user.wallet.payment-methods') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 rounded-md {{ request()->routeIs('user.wallet.payment-methods') ? 'bg-indigo-100 text-indigo-700 font-semibold' : '' }}">
                            <span class="mr-2">🏦</span>
                            <span>ช่องทางรับเงิน</span>
                        </a>
                    </div>
                </div>

                <a href="{{ route('user.referrals') }}" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100 {{ request()->routeIs('user.referrals') ? 'bg-gray-100 border-l-4 border-indigo-600' : '' }}">
                    <span class="text-xl mr-3">👥</span>
                    <span>ผู้แนะนำ</span>
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
                    <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center text-indigo-600 font-bold text-lg">
                        {{ substr(Auth::user()->name, 0, 1) }}
                    </div>
                    <div>
                        <p class="font-semibold">{{ Auth::user()->name }}</p>
                        <p class="text-sm opacity-90">{{ Auth::user()->email }}</p>
                    </div>
                </div>
            </div>

            <nav class="py-4">
                <a href="{{ route('user.dashboard') }}" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100 {{ request()->routeIs('user.dashboard') ? 'bg-gray-100 border-l-4 border-indigo-600' : '' }}">
                    <span class="text-xl mr-3">📊</span>
                    <span>แดชบอร์ด</span>
                </a>

                <a href="{{ route('user.profile') }}" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100 {{ request()->routeIs('user.profile') ? 'bg-gray-100 border-l-4 border-indigo-600' : '' }}">
                    <span class="text-xl mr-3">👤</span>
                    <span>โปรไฟล์</span>
                </a>

                <a href="{{ route('user.commissions') }}" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100 {{ request()->routeIs('user.commissions') ? 'bg-gray-100 border-l-4 border-indigo-600' : '' }}">
                    <span class="text-xl mr-3">💰</span>
                    <span>คอมมิชชั่น</span>
                </a>

                <!-- Wallet Dropdown Menu -->
                <div x-data="{ walletOpen: false }" class="relative">
                    <!-- Main Wallet Button -->
                    <button @click="walletOpen = !walletOpen" class="flex items-center w-full px-6 py-3 text-gray-700 hover:bg-gray-100 {{ request()->routeIs('user.wallet.*') ? 'bg-gray-100 border-l-4 border-indigo-600' : '' }}">
                        <span class="text-xl mr-3">💳</span>
                        <span class="flex-1 text-left">กระเป๋าเงิน</span>
                        <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': walletOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <!-- Dropdown Submenu -->
                    <div x-show="walletOpen"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 transform -translate-y-2"
                         x-transition:enter-end="opacity-100 transform translate-y-0"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         class="mt-2 mb-2 ml-6 space-y-1 bg-gray-50 rounded-lg p-2 border-l-2 border-indigo-200"
                         style="display: none;">

                        <a href="{{ route('user.wallet.index') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 rounded-md {{ request()->routeIs('user.wallet.index') ? 'bg-indigo-100 text-indigo-700 font-semibold' : '' }}">
                            <span class="mr-2">👛</span>
                            <span>กระเป๋าของฉัน</span>
                        </a>

                        <a href="{{ route('user.wallet.transactions') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 rounded-md {{ request()->routeIs('user.wallet.transactions') ? 'bg-indigo-100 text-indigo-700 font-semibold' : '' }}">
                            <span class="mr-2">📝</span>
                            <span>ประวัติธุรกรรม</span>
                        </a>

                        <div class="border-t border-gray-300 my-2"></div>

                        <a href="{{ route('user.wallet.withdraw') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 rounded-md {{ request()->routeIs('user.wallet.withdraw') ? 'bg-indigo-100 text-indigo-700 font-semibold' : '' }}">
                            <span class="mr-2">💸</span>
                            <span>ถอนเงิน</span>
                        </a>

                        <a href="{{ route('user.wallet.withdrawals') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 rounded-md {{ request()->routeIs('user.wallet.withdrawals') ? 'bg-indigo-100 text-indigo-700 font-semibold' : '' }}">
                            <span class="mr-2">📋</span>
                            <span>ประวัติการถอน</span>
                        </a>

                        <div class="border-t border-gray-300 my-2"></div>

                        <a href="{{ route('user.wallet.payment-methods') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 rounded-md {{ request()->routeIs('user.wallet.payment-methods') ? 'bg-indigo-100 text-indigo-700 font-semibold' : '' }}">
                            <span class="mr-2">🏦</span>
                            <span>ช่องทางรับเงิน</span>
                        </a>
                    </div>
                </div>

                <a href="{{ route('user.referrals') }}" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100 {{ request()->routeIs('user.referrals') ? 'bg-gray-100 border-l-4 border-indigo-600' : '' }}">
                    <span class="text-xl mr-3">👥</span>
                    <span>ผู้แนะนำ</span>
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
                <a href="{{ route('user.dashboard') }}" class="flex flex-col items-center py-2 px-3 {{ request()->routeIs('user.dashboard') ? 'text-indigo-600' : 'text-gray-600' }}">
                    <span class="text-2xl">📊</span>
                    <span class="text-xs mt-1">แดชบอร์ด</span>
                </a>

                <a href="{{ route('user.wallet.index') }}" class="flex flex-col items-center py-2 px-3 {{ request()->routeIs('user.wallet.*') ? 'text-indigo-600' : 'text-gray-600' }}">
                    <span class="text-2xl">💳</span>
                    <span class="text-xs mt-1">กระเป๋าเงิน</span>
                </a>

                <a href="{{ route('user.commissions') }}" class="flex flex-col items-center py-2 px-3 {{ request()->routeIs('user.commissions') ? 'text-indigo-600' : 'text-gray-600' }}">
                    <span class="text-2xl">💰</span>
                    <span class="text-xs mt-1">คอมมิชชั่น</span>
                </a>

                <a href="{{ route('user.profile') }}" class="flex flex-col items-center py-2 px-3 {{ request()->routeIs('user.profile') ? 'text-indigo-600' : 'text-gray-600' }}">
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

    {{-- Google Translate Widget (Like WordPress Plugins) --}}

    @stack('scripts')
</body>
</html>
