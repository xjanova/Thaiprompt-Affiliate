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

        /* Custom scrollbar for sidebar */
        .sidebar-scroll::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar-scroll::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.1);
        }

        .sidebar-scroll::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 3px;
        }

        .sidebar-scroll::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
        }
    </style>

    @stack('styles')

    <style>
        /* Ensure Windows Taskbar doesn't overlap with content */
        @php
            $taskbarPosition = \App\Models\WindowsUiSetting::get('windows_taskbar_position', 'top');
            $taskbarHeight = \App\Models\WindowsUiSetting::get('windows_taskbar_height', 48);
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

    <!-- Windows Taskbar -->
    <x-windows-taskbar />

    <!-- Page Loader -->
    <x-page-loader />
    <div class="min-h-screen" x-data="{ menuOpen: false, profileDropdown: false }">

        <!-- Main Content -->
        <div class="transition-all duration-300">
            <!-- Top Bar -->
            <header class="bg-white dark:bg-gray-800 shadow-sm sticky top-0 z-50">
                <div class="flex items-center justify-between px-6 py-4">
                    <div class="flex items-center space-x-4">
                        <!-- Menu Button -->
                        <div class="relative" x-data="{ open: false }" @click.away="open = false">
                            <button @click="open = !open"
                                    class="flex items-center space-x-2 px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white rounded-lg transition-all duration-200 shadow-md hover:shadow-lg">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                                <span class="font-medium">เมนู</span>
                            </button>

                            <!-- Dropdown Menu -->
                            <div x-show="open"
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 transform scale-95"
                                 x-transition:enter-end="opacity-100 transform scale-100"
                                 x-transition:leave="transition ease-in duration-150"
                                 x-transition:leave-start="opacity-100 transform scale-100"
                                 x-transition:leave-end="opacity-0 transform scale-95"
                                 class="absolute left-0 mt-2 w-80 bg-gradient-to-b from-gray-900 via-gray-800 to-gray-900 rounded-lg shadow-2xl overflow-y-auto max-h-[80vh] sidebar-scroll"
                                 style="display: none;">

                                <!-- Logo Section -->
                                <div class="flex items-center justify-center h-16 bg-gradient-primary relative border-b border-gray-700">
                                    @php
                                        $logo = \App\Models\Setting::get('logo');
                                        $appName = \App\Models\Setting::get('app_name', 'TP-Affiliate');
                                    @endphp
                                    @if($logo)
                                        <img src="{{ asset($logo) }}" alt="Logo" width="48" height="48" class="h-12 object-contain">
                                    @else
                                        <span class="text-white font-bold text-2xl">{{ $appName }}</span>
                                    @endif
                                </div>

                                <!-- Navigation -->
                                <nav class="p-3">
                                    <!-- Dashboard -->
                                    <a href="{{ route('user.dashboard') }}"
                                       class="flex items-center px-3 py-2.5 mb-1 text-gray-300 hover:bg-gradient-to-r hover:from-indigo-600 hover:to-purple-600 hover:text-white rounded-lg transition-all duration-200 group {{ request()->routeIs('user.dashboard') ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-lg' : '' }}">
                                        <span class="text-xl">📊</span>
                                        <span class="ml-3 text-sm font-medium">แดชบอร์ด</span>
                                    </a>

                                    <!-- Profile -->
                                    <a href="{{ route('user.profile') }}"
                                       class="flex items-center px-3 py-2.5 mb-1 text-gray-300 hover:bg-gradient-to-r hover:from-indigo-600 hover:to-purple-600 hover:text-white rounded-lg transition-all duration-200 group {{ request()->routeIs('user.profile') ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-lg' : '' }}">
                                        <span class="text-xl">👤</span>
                                        <span class="ml-3 text-sm font-medium">โปรไฟล์</span>
                                    </a>

                                    <!-- KYC Verification -->
                                    <a href="{{ route('user.kyc.index') }}"
                                       class="flex items-center px-3 py-2.5 mb-1 text-gray-300 hover:bg-gradient-to-r hover:from-indigo-600 hover:to-purple-600 hover:text-white rounded-lg transition-all duration-200 group {{ request()->routeIs('user.kyc.*') ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-lg' : '' }}">
                                        <span class="text-xl">🪪</span>
                                        <span class="ml-3 text-sm font-medium flex-1">ยืนยันตัวตน (KYC)</span>
                                        @if(auth()->user()->kyc_status === 'pending')
                                            <span class="ml-auto bg-yellow-500 text-white text-xs rounded-full px-1.5 py-0.5 animate-pulse">รอตรวจสอบ</span>
                                        @elseif(auth()->user()->kyc_status === 'approved')
                                            <span class="ml-auto bg-green-500 text-white text-xs rounded-full px-1.5 py-0.5">✓</span>
                                        @elseif(auth()->user()->kyc_status === 'rejected')
                                            <span class="ml-auto bg-red-500 text-white text-xs rounded-full px-1.5 py-0.5">!</span>
                                        @endif
                                    </a>

                                    <!-- Commissions -->
                                    <a href="{{ route('user.commissions') }}"
                                       class="flex items-center px-3 py-2.5 mb-1 text-gray-300 hover:bg-gradient-to-r hover:from-indigo-600 hover:to-purple-600 hover:text-white rounded-lg transition-all duration-200 group {{ request()->routeIs('user.commissions') ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-lg' : '' }}">
                                        <span class="text-xl">💰</span>
                                        <span class="ml-3 text-sm font-medium">คอมมิชชั่น</span>
                                    </a>

                                    <!-- Shop -->
                                    <a href="{{ route('user.shop.index') }}"
                                       class="flex items-center px-3 py-2.5 mb-1 text-gray-300 hover:bg-gradient-to-r hover:from-green-600 hover:to-teal-600 hover:text-white rounded-lg transition-all duration-200 group {{ request()->routeIs('user.shop.*') ? 'bg-gradient-to-r from-green-600 to-teal-600 text-white shadow-lg' : '' }}">
                                        <span class="text-xl">🛒</span>
                                        <span class="ml-3 text-sm font-medium">ไปช๊อปปิ้ง</span>
                                    </a>

                                    <!-- Hotel Bookings -->
                                    <a href="{{ route('hotels.bookings.index') }}"
                                       class="flex items-center px-3 py-2.5 mb-1 text-gray-300 hover:bg-gradient-to-r hover:from-orange-600 hover:to-amber-600 hover:text-white rounded-lg transition-all duration-200 group {{ request()->routeIs('hotels.bookings.*') ? 'bg-gradient-to-r from-orange-600 to-amber-600 text-white shadow-lg' : '' }}">
                                        <span class="text-xl">🏨</span>
                                        <span class="ml-3 text-sm font-medium">การจองโรงแรม</span>
                                    </a>

                                    <!-- Ticket Support -->
                                    @php
                                        $myOpenTickets = \App\Models\Ticket::where('user_id', auth()->id())->open()->count();
                                    @endphp
                                    <a href="{{ route('user.tickets.index') }}"
                                       class="flex items-center px-3 py-2.5 mb-1 text-gray-300 hover:bg-gradient-to-r hover:from-blue-600 hover:to-indigo-600 hover:text-white rounded-lg transition-all duration-200 group {{ request()->routeIs('user.tickets.*') ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-lg' : '' }}">
                                        <span class="text-xl">🎫</span>
                                        <span class="ml-3 text-sm font-medium flex-1">Ticket Support</span>
                                        @if($myOpenTickets > 0)
                                            <span class="ml-auto bg-blue-500 text-white text-xs rounded-full px-1.5 py-0.5">{{ $myOpenTickets }}</span>
                                        @endif
                                    </a>

                                    <!-- Wallet Dropdown Menu -->
                                    <div x-data="{ walletOpen: false }" class="relative mb-1">
                                        @php
                                            $walletActive = request()->routeIs('user.wallet.*');
                                        @endphp

                                        <!-- Main Wallet Button -->
                                        <button
                                           class="flex items-center w-full px-3 py-2.5 text-gray-300 hover:bg-gradient-to-r hover:from-indigo-600 hover:to-purple-600 hover:text-white rounded-lg transition-all duration-200 group {{ $walletActive ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-lg' : '' }}"
                                           @click="walletOpen = !walletOpen">
                                            <span class="text-xl">💳</span>
                                            <span class="ml-3 text-sm font-medium flex-1 text-left">
                                                กระเป๋าเงิน THB
                                            </span>
                                            <svg class="w-3.5 h-3.5 ml-2 transition-transform duration-200" :class="{ 'rotate-180': walletOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>

                                        <!-- Dropdown Submenu -->
                                        <div x-show="walletOpen"
                                             x-transition:enter="transition ease-out duration-200"
                                             x-transition:enter-start="opacity-0 transform -translate-y-2"
                                             x-transition:enter-end="opacity-100 transform translate-y-0"
                                             x-transition:leave="transition ease-in duration-150"
                                             x-transition:leave-start="opacity-100"
                                             x-transition:leave-end="opacity-0"
                                             class="mt-1 mb-2 ml-3 space-y-0.5 bg-gray-800/30 rounded-lg p-1.5 backdrop-blur-sm border border-gray-700/30"
                                             style="display: none;">

                                            <a href="{{ route('user.wallet.index') }}"
                                               class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-indigo-500 hover:to-purple-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('user.wallet.index') ? 'bg-gradient-to-r from-indigo-500 to-purple-500 text-white' : '' }}">
                                                <span class="mr-2">👛</span>
                                                <span>กระเป๋าของฉัน</span>
                                            </a>

                                            <a href="{{ route('user.wallet.transactions') }}"
                                               class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-indigo-500 hover:to-purple-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('user.wallet.transactions') ? 'bg-gradient-to-r from-indigo-500 to-purple-500 text-white' : '' }}">
                                                <span class="mr-2">📝</span>
                                                <span>ประวัติธุรกรรม</span>
                                            </a>

                                            <div class="border-t border-gray-700/30 my-1"></div>

                                            <a href="{{ route('user.wallet.withdraw') }}"
                                               class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-indigo-500 hover:to-purple-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('user.wallet.withdraw') ? 'bg-gradient-to-r from-indigo-500 to-purple-500 text-white' : '' }}">
                                                <span class="mr-2">💸</span>
                                                <span>ถอนเงิน</span>
                                            </a>

                                            <a href="{{ route('user.wallet.withdrawals') }}"
                                               class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-indigo-500 hover:to-purple-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('user.wallet.withdrawals') ? 'bg-gradient-to-r from-indigo-500 to-purple-500 text-white' : '' }}">
                                                <span class="mr-2">📋</span>
                                                <span>ประวัติการถอน</span>
                                            </a>

                                            <div class="border-t border-gray-700/30 my-1"></div>

                                            <a href="{{ route('user.wallet.payment-methods') }}"
                                               class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-indigo-500 hover:to-purple-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('user.wallet.payment-methods') ? 'bg-gradient-to-r from-indigo-500 to-purple-500 text-white' : '' }}">
                                                <span class="mr-2">🏦</span>
                                                <span>ช่องทางรับเงิน</span>
                                            </a>
                                        </div>
                                    </div>

                                    <!-- Crypto Wallet Dropdown Menu -->
                                    <div x-data="{ cryptoOpen: false }" class="relative mb-1">
                                        @php
                                            $cryptoActive = request()->routeIs('user.crypto-wallet.*');
                                        @endphp

                                        <!-- Main Crypto Wallet Button -->
                                        <button
                                           class="flex items-center w-full px-3 py-2.5 text-gray-300 hover:bg-gradient-to-r hover:from-amber-600 hover:to-orange-600 hover:text-white rounded-lg transition-all duration-200 group {{ $cryptoActive ? 'bg-gradient-to-r from-amber-600 to-orange-600 text-white shadow-lg' : '' }}"
                                           @click="cryptoOpen = !cryptoOpen">
                                            <span class="text-xl">₿</span>
                                            <span class="ml-3 text-sm font-medium flex-1 text-left">
                                                กระเป๋าคริปโต
                                            </span>
                                            <svg class="w-3.5 h-3.5 ml-2 transition-transform duration-200" :class="{ 'rotate-180': cryptoOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>

                                        <!-- Dropdown Submenu -->
                                        <div x-show="cryptoOpen"
                                             x-transition:enter="transition ease-out duration-200"
                                             x-transition:enter-start="opacity-0 transform -translate-y-2"
                                             x-transition:enter-end="opacity-100 transform translate-y-0"
                                             x-transition:leave="transition ease-in duration-150"
                                             x-transition:leave-start="opacity-100"
                                             x-transition:leave-end="opacity-0"
                                             class="mt-1 mb-2 ml-3 space-y-0.5 bg-gray-800/30 rounded-lg p-1.5 backdrop-blur-sm border border-gray-700/30"
                                             style="display: none;">

                                            <a href="{{ route('user.crypto-wallet.index') }}"
                                               class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-amber-500 hover:to-orange-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('user.crypto-wallet.index') ? 'bg-gradient-to-r from-amber-500 to-orange-500 text-white' : '' }}">
                                                <span class="mr-2">🪙</span>
                                                <span>กระเป๋าคริปโต</span>
                                            </a>

                                            <a href="{{ route('user.crypto-wallet.wallet-management') }}"
                                               class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-amber-500 hover:to-orange-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('user.crypto-wallet.wallet-management') ? 'bg-gradient-to-r from-amber-500 to-orange-500 text-white' : '' }}">
                                                <span class="mr-2">💼</span>
                                                <span>จัดการกระเป๋า</span>
                                            </a>

                                            <a href="{{ route('user.crypto-wallet.transactions') }}"
                                               class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-amber-500 hover:to-orange-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('user.crypto-wallet.transactions') ? 'bg-gradient-to-r from-amber-500 to-orange-500 text-white' : '' }}">
                                                <span class="mr-2">📝</span>
                                                <span>ประวัติธุรกรรม</span>
                                            </a>

                                            <div class="border-t border-gray-700/30 my-1"></div>

                                            <!-- Premium Trading & Portfolio -->
                                            <a href="{{ route('user.crypto-wallet.trading') }}"
                                               class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-purple-500 hover:to-pink-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('user.crypto-wallet.trading') ? 'bg-gradient-to-r from-purple-500 to-pink-500 text-white' : '' }}">
                                                <span class="mr-2">📊</span>
                                                <span>เทรดดิ้ง</span>
                                            </a>

                                            <a href="{{ route('user.crypto-wallet.portfolio') }}"
                                               class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-purple-500 hover:to-pink-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('user.crypto-wallet.portfolio') ? 'bg-gradient-to-r from-purple-500 to-pink-500 text-white' : '' }}">
                                                <span class="mr-2">💼</span>
                                                <span>พอร์ตโฟลิโอ</span>
                                            </a>

                                            <div class="border-t border-gray-700/30 my-1"></div>

                                            <a href="{{ route('user.crypto-wallet.deposit') }}"
                                               class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-amber-500 hover:to-orange-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('user.crypto-wallet.deposit*') ? 'bg-gradient-to-r from-amber-500 to-orange-500 text-white' : '' }}">
                                                <span class="mr-2">📥</span>
                                                <span>ฝากเหรียญ</span>
                                            </a>

                                            <a href="{{ route('user.crypto-wallet.withdraw') }}"
                                               class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-amber-500 hover:to-orange-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('user.crypto-wallet.withdraw*') ? 'bg-gradient-to-r from-amber-500 to-orange-500 text-white' : '' }}">
                                                <span class="mr-2">📤</span>
                                                <span>ถอนเหรียญ</span>
                                            </a>

                                            <a href="{{ route('user.crypto-wallet.withdrawals') }}"
                                               class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-amber-500 hover:to-orange-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('user.crypto-wallet.withdrawals') ? 'bg-gradient-to-r from-amber-500 to-orange-500 text-white' : '' }}">
                                                <span class="mr-2">📋</span>
                                                <span>ประวัติการถอน</span>
                                            </a>

                                            <div class="border-t border-gray-700/30 my-1"></div>

                                            <a href="{{ route('user.crypto-wallet.exchange') }}"
                                               class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-amber-500 hover:to-orange-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('user.crypto-wallet.exchange*') ? 'bg-gradient-to-r from-amber-500 to-orange-500 text-white' : '' }}">
                                                <span class="mr-2">💱</span>
                                                <span>แลกเปลี่ยน THB ↔ Crypto</span>
                                            </a>
                                        </div>
                                    </div>

                                    <!-- Investment Dropdown Menu -->
                                    <div x-data="{ investmentOpen: false }" class="relative mb-1">
                                        @php
                                            $investmentActive = request()->routeIs('user.investments.*');
                                        @endphp

                                        <!-- Main Investment Button -->
                                        <button
                                           class="flex items-center w-full px-3 py-2.5 text-gray-300 hover:bg-gradient-to-r hover:from-purple-600 hover:to-pink-600 hover:text-white rounded-lg transition-all duration-200 group {{ $investmentActive ? 'bg-gradient-to-r from-purple-600 to-pink-600 text-white shadow-lg' : '' }}"
                                           @click="investmentOpen = !investmentOpen">
                                            <span class="text-xl">📈</span>
                                            <span class="ml-3 text-sm font-medium flex-1 text-left">
                                                การลงทุน ROI
                                            </span>
                                            <svg class="w-3.5 h-3.5 ml-2 transition-transform duration-200" :class="{ 'rotate-180': investmentOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>

                                        <!-- Dropdown Submenu -->
                                        <div x-show="investmentOpen"
                                             x-transition:enter="transition ease-out duration-200"
                                             x-transition:enter-start="opacity-0 transform -translate-y-2"
                                             x-transition:enter-end="opacity-100 transform translate-y-0"
                                             x-transition:leave="transition ease-in duration-150"
                                             x-transition:leave-start="opacity-100"
                                             x-transition:leave-end="opacity-0"
                                             class="mt-1 mb-2 ml-3 space-y-0.5 bg-gray-800/30 rounded-lg p-1.5 backdrop-blur-sm border border-gray-700/30"
                                             style="display: none;">

                                            <a href="{{ route('user.investments.index') }}"
                                               class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-purple-500 hover:to-pink-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('user.investments.index') ? 'bg-gradient-to-r from-purple-500 to-pink-500 text-white' : '' }}">
                                                <span class="mr-2">🏠</span>
                                                <span>แดชบอร์ด</span>
                                            </a>

                                            <a href="{{ route('user.investments.plans') }}"
                                               class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-purple-500 hover:to-pink-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('user.investments.plans*') ? 'bg-gradient-to-r from-purple-500 to-pink-500 text-white' : '' }}">
                                                <span class="mr-2">💎</span>
                                                <span>แผนการลงทุน</span>
                                            </a>

                                            <div class="border-t border-gray-700/30 my-1"></div>

                                            <a href="{{ route('user.investments.index') }}#roi-distributions"
                                               class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-purple-500 hover:to-pink-500 hover:text-white rounded-md transition-all duration-200">
                                                <span class="mr-2">💰</span>
                                                <span>ประวัติ ROI</span>
                                            </a>
                                        </div>
                                    </div>

                                    <!-- Marketing Section Divider -->
                                    <div class="border-t border-gray-700/50 my-2"></div>

                                    <!-- Marketing Header -->
                                    <div class="px-3 py-2">
                                        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">การตลาด</span>
                                    </div>

                                    <!-- Referrals -->
                                    <a href="{{ route('user.referrals') }}"
                                       class="flex items-center px-3 py-2.5 mb-1 text-gray-300 hover:bg-gradient-to-r hover:from-indigo-600 hover:to-purple-600 hover:text-white rounded-lg transition-all duration-200 group {{ request()->routeIs('user.referrals') ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-lg' : '' }}">
                                        <span class="text-xl">👥</span>
                                        <span class="ml-3 text-sm font-medium">ผู้แนะนำ</span>
                                    </a>

                                    <!-- Organization -->
                                    <a href="{{ route('user.organization') }}"
                                       class="flex items-center px-3 py-2.5 mb-1 text-gray-300 hover:bg-gradient-to-r hover:from-indigo-600 hover:to-purple-600 hover:text-white rounded-lg transition-all duration-200 group {{ request()->routeIs('user.organization') && !request()->routeIs('user.organization.binary') ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-lg' : '' }}">
                                        <span class="text-xl">🌳</span>
                                        <span class="ml-3 text-sm font-medium">ผังสายงาน</span>
                                    </a>

                                    <!-- Binary Organization -->
                                    <a href="{{ route('user.organization.binary') }}"
                                       class="flex items-center px-3 py-2.5 mb-1 text-gray-300 hover:bg-gradient-to-r hover:from-purple-600 hover:to-pink-600 hover:text-white rounded-lg transition-all duration-200 group {{ request()->routeIs('user.organization.binary') ? 'bg-gradient-to-r from-purple-600 to-pink-600 text-white shadow-lg' : '' }}">
                                        <span class="text-xl">🌲</span>
                                        <span class="ml-3 text-sm font-medium">ผัง Binary</span>
                                    </a>

                                    <!-- Retention Dropdown Menu -->
                                    <div x-data="{ retentionOpen: false }" class="relative mb-1">
                                        @php
                                            $retentionActive = request()->routeIs('user.retention.*');
                                        @endphp

                                        <!-- Main Retention Button -->
                                        <button
                                           class="flex items-center w-full px-3 py-2.5 text-gray-300 hover:bg-gradient-to-r hover:from-red-600 hover:to-pink-600 hover:text-white rounded-lg transition-all duration-200 group {{ $retentionActive ? 'bg-gradient-to-r from-red-600 to-pink-600 text-white shadow-lg' : '' }}"
                                           @click="retentionOpen = !retentionOpen">
                                            <span class="text-xl">💖</span>
                                            <span class="ml-3 text-sm font-medium flex-1 text-left">
                                                รักษายอด
                                            </span>
                                            <svg class="w-3.5 h-3.5 ml-2 transition-transform duration-200" :class="{ 'rotate-180': retentionOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>

                                        <!-- Dropdown Submenu -->
                                        <div x-show="retentionOpen"
                                             x-transition:enter="transition ease-out duration-200"
                                             x-transition:enter-start="opacity-0 transform -translate-y-2"
                                             x-transition:enter-end="opacity-100 transform translate-y-0"
                                             x-transition:leave="transition ease-in duration-150"
                                             x-transition:leave-start="opacity-100"
                                             x-transition:leave-end="opacity-0"
                                             class="mt-1 mb-2 ml-3 space-y-0.5 bg-gray-800/30 rounded-lg p-1.5 backdrop-blur-sm border border-gray-700/30"
                                             style="display: none;">

                                            <a href="{{ route('user.retention.index') }}"
                                               class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-red-500 hover:to-pink-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('user.retention.index') ? 'bg-gradient-to-r from-red-500 to-pink-500 text-white' : '' }}">
                                                <span class="mr-2">💗</span>
                                                <span>สถานะพลังชีวิต</span>
                                            </a>

                                            <a href="{{ route('user.retention.repair') }}"
                                               class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-red-500 hover:to-pink-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('user.retention.repair') ? 'bg-gradient-to-r from-red-500 to-pink-500 text-white' : '' }}">
                                                <span class="mr-2">🔧</span>
                                                <span>ซ่อมสิทธิ์</span>
                                            </a>

                                            <a href="{{ route('user.retention.advance-renewal') }}"
                                               class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-red-500 hover:to-pink-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('user.retention.advance-renewal') ? 'bg-gradient-to-r from-red-500 to-pink-500 text-white' : '' }}">
                                                <span class="mr-2">⏰</span>
                                                <span>เติมวันล่วงหน้า</span>
                                            </a>
                                        </div>
                                    </div>

                                    <!-- Marketing Tools Dropdown Menu -->
                                    <div x-data="{ marketingToolsOpen: false }" class="relative mb-1">
                                        @php
                                            $marketingToolsActive = request()->routeIs('user.mlm.income-simulator') ||
                                                                    request()->routeIs('user.mlm.income-comparison') ||
                                                                    request()->routeIs('user.mlm.dividend-simulator');
                                        @endphp

                                        <!-- Main Marketing Tools Button -->
                                        <button
                                           class="flex items-center w-full px-3 py-2.5 text-gray-300 hover:bg-gradient-to-r hover:from-yellow-600 hover:to-orange-600 hover:text-white rounded-lg transition-all duration-200 group {{ $marketingToolsActive ? 'bg-gradient-to-r from-yellow-600 to-orange-600 text-white shadow-lg' : '' }}"
                                           @click="marketingToolsOpen = !marketingToolsOpen">
                                            <span class="text-xl">🛠️</span>
                                            <span class="ml-3 text-sm font-medium flex-1 text-left">
                                                เครื่องมือการตลาด
                                            </span>
                                            <svg class="w-3.5 h-3.5 ml-2 transition-transform duration-200" :class="{ 'rotate-180': marketingToolsOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>

                                        <!-- Dropdown Submenu -->
                                        <div x-show="marketingToolsOpen"
                                             x-transition:enter="transition ease-out duration-200"
                                             x-transition:enter-start="opacity-0 transform -translate-y-2"
                                             x-transition:enter-end="opacity-100 transform translate-y-0"
                                             x-transition:leave="transition ease-in duration-150"
                                             x-transition:leave-start="opacity-100"
                                             x-transition:leave-end="opacity-0"
                                             class="mt-1 mb-2 ml-3 space-y-0.5 bg-gray-800/30 rounded-lg p-1.5 backdrop-blur-sm border border-gray-700/30"
                                             style="display: none;">

                                            <a href="{{ route('user.mlm.income-simulator') }}"
                                               class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-yellow-500 hover:to-orange-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('user.mlm.income-simulator') ? 'bg-gradient-to-r from-yellow-500 to-orange-500 text-white' : '' }}">
                                                <span class="mr-2">💰</span>
                                                <span>จำลองรายได้</span>
                                            </a>

                                            <a href="{{ route('user.mlm.income-comparison') }}"
                                               class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-yellow-500 hover:to-orange-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('user.mlm.income-comparison') ? 'bg-gradient-to-r from-yellow-500 to-orange-500 text-white' : '' }}">
                                                <span class="mr-2">📊</span>
                                                <span>เปรียบเทียบรายได้</span>
                                            </a>

                                            <a href="{{ route('user.mlm.dividend-simulator') }}"
                                               class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-yellow-500 hover:to-orange-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('user.mlm.dividend-simulator') ? 'bg-gradient-to-r from-yellow-500 to-orange-500 text-white' : '' }}">
                                                <span class="mr-2">💎</span>
                                                <span>จำลองการปันผล</span>
                                            </a>
                                        </div>
                                    </div>

                                    <!-- Theme Settings -->
                                    <div class="mb-1">
                                        <a href="{{ route('user.themes.index') }}"
                                           class="flex items-center w-full px-3 py-2.5 text-gray-300 hover:bg-gradient-to-r hover:from-purple-600 hover:to-pink-600 hover:text-white rounded-lg transition-all duration-200 group {{ request()->routeIs('user.themes.*') ? 'bg-gradient-to-r from-purple-600 to-pink-600 text-white shadow-lg' : '' }}">
                                            <span class="text-xl">🎨</span>
                                            <span class="ml-3 text-sm font-medium">
                                                ตั้งค่าธีม
                                            </span>
                                        </a>
                                    </div>

                                    <!-- Spacing at bottom for scrolling -->
                                    <div class="h-4"></div>
                                </nav>
                            </div>
                        </div>

                        <h1 class="text-2xl font-semibold text-gray-800 dark:text-white">@yield('title')</h1>
                    </div>

                    <div class="flex items-center space-x-3">
                        <!-- Dashboard Switcher -->
                        <x-dashboard-switcher />

                        <!-- Dark Mode Toggle -->
                        <x-dark-mode-toggle />

                        <!-- Notification Bell -->
                        <x-notification-bell />

                        <!-- Language Switcher -->
                        <div class="relative z-[60]">
                            <x-language-switcher-pro />
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="p-6">
                @yield('content')
            </main>
        </div>
    </div>

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

        // GSAP Animations - ปรับให้ไม่กระทบการแสดงผล
        gsap.registerPlugin(ScrollTrigger);

        // Animate page entrance
        document.addEventListener('DOMContentLoaded', function() {
            // ปิด animations ที่อาจทำให้แสดงผลไม่ครบ
            // Removed initial opacity animations to prevent display issues

            // Subtle entrance animation - ใช้แค่ translateY ไม่ใช้ opacity
            gsap.from('main > *', {
                y: 10,
                duration: 0.3,
                stagger: 0.05,
                ease: 'power2.out',
                clearProps: 'all' // Clear all properties after animation
            });

            // Removed scroll-triggered animations to prevent rendering issues
            // เอา card animations ออกเพื่อป้องกันปัญหาการแสดงผล

            // Subtle hover effects on buttons - ไม่กระทบการแสดงผล
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

    {{-- Google Translate Widget (Like WordPress Plugins) --}}

    {{-- Immediate Notification Popup --}}
    <x-immediate-notification-popup />

    {{-- Floating Tools --}}
    <x-floating-tools />

    @stack('scripts')
</body>
</html>
