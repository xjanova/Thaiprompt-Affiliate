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

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- GSAP -->
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/ScrollTrigger.min.js"></script>

    @php
        $primaryStart = \App\Models\Setting::get('theme_primary_start', '#3B82F6');
        $primaryEnd = \App\Models\Setting::get('theme_primary_end', '#1D4ED8');
        $secondaryStart = \App\Models\Setting::get('theme_secondary_start', '#10B981');
        $secondaryEnd = \App\Models\Setting::get('theme_secondary_end', '#059669');
    @endphp

    <style>
        :root {
            --gradient-primary: linear-gradient(135deg, {{ $primaryStart }}, {{ $primaryEnd }});
            --gradient-secondary: linear-gradient(135deg, {{ $secondaryStart }}, {{ $secondaryEnd }});
        }

        .bg-gradient-primary {
            background: var(--gradient-primary);
        }

        .bg-gradient-secondary {
            background: var(--gradient-secondary);
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
            background-color: #1e293b;
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

        .dark .shadow-xl {
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5), 0 10px 10px -5px rgba(0, 0, 0, 0.4);
        }

        .dark .hover\:bg-gray-50:hover {
            background-color: #334155;
        }

        .dark .bg-gray-100 {
            background-color: #1e293b;
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
</head>
<body class="font-sans antialiased bg-gray-100">
    <div class="min-h-screen" x-data="{ sidebarOpen: false, sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true', profileDropdown: false, systemMenuOpen: false }">
        <!-- Overlay for mobile -->
        <div x-show="sidebarOpen"
             @click="sidebarOpen = false"
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-gray-600 bg-opacity-75 z-30 md:hidden"
             style="display: none;"></div>

        <!-- Sidebar -->
        <div class="fixed inset-y-0 left-0 z-40 bg-gradient-to-b from-gray-900 via-gray-800 to-gray-900 shadow-2xl transform transition-all duration-300 ease-in-out overflow-y-auto sidebar-scroll"
             style="box-shadow: 4px 0 20px rgba(0, 0, 0, 0.5);"
             :class="{
                 '-translate-x-full md:translate-x-0': !sidebarOpen,
                 'translate-x-0': sidebarOpen,
                 'w-56': !sidebarCollapsed,
                 'md:w-16': sidebarCollapsed
             }">
            <!-- Logo Section -->
            <div class="flex items-center justify-center h-16 bg-gradient-primary relative">
                @php
                    $logo = \App\Models\Setting::get('logo');
                @endphp
                @if($logo)
                    <img src="{{ asset($logo) }}" alt="Logo" class="h-10 object-contain" :class="{ 'md:h-8': sidebarCollapsed }">
                @else
                    @php
                        $appName = \App\Models\Setting::get('app_name', 'TP-Affiliate');
                        $appNameShort = mb_substr($appName, 0, 2);
                    @endphp
                    <span class="text-white font-bold transition-all" :class="{ 'text-2xl': !sidebarCollapsed, 'md:text-lg': sidebarCollapsed }">
                        <span x-show="!sidebarCollapsed">{{ $appName }}</span>
                        <span x-show="sidebarCollapsed" class="hidden md:block">{{ $appNameShort }}</span>
                    </span>
                @endif

                <!-- Collapse Toggle (Desktop only) -->
                <button @click="sidebarCollapsed = !sidebarCollapsed; localStorage.setItem('sidebarCollapsed', sidebarCollapsed)"
                        class="hidden md:flex absolute -right-4 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white rounded-full p-2.5 shadow-2xl transition-all duration-300 items-center justify-center group hover:scale-110 border-2 border-white"
                        title="ซ่อน/แสดงเมนู">
                    <svg class="w-5 h-5 transition-transform duration-300 drop-shadow-lg" :class="{ 'rotate-180': sidebarCollapsed }" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>
            </div>

            <!-- Navigation -->
            <nav class="mt-6 px-3">
                <!-- Dashboard -->
                <a href="{{ route('admin.dashboard') }}"
                   class="flex items-center px-3 py-2.5 mb-1 text-gray-300 hover:bg-gradient-to-r hover:from-indigo-600 hover:to-purple-600 hover:text-white rounded-lg transition-all duration-200 group {{ request()->routeIs('admin.dashboard') ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-lg' : '' }}">
                    <span class="text-xl transition-all" :class="{ 'md:mx-auto': sidebarCollapsed }">📊</span>
                    <span class="ml-3 text-sm font-medium transition-all" :class="{ 'md:hidden': sidebarCollapsed }" x-show="!sidebarCollapsed || sidebarOpen">แดชบอร์ด</span>
                </a>

                <!-- Users -->
                <a href="{{ route('admin.users.index') }}"
                   class="flex items-center px-3 py-2.5 mb-1 text-gray-300 hover:bg-gradient-to-r hover:from-indigo-600 hover:to-purple-600 hover:text-white rounded-lg transition-all duration-200 group {{ request()->routeIs('admin.users.*') ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-lg' : '' }}">
                    <span class="text-xl transition-all" :class="{ 'md:mx-auto': sidebarCollapsed }">👥</span>
                    <span class="ml-3 text-sm font-medium transition-all" :class="{ 'md:hidden': sidebarCollapsed }" x-show="!sidebarCollapsed || sidebarOpen">ผู้ใช้</span>
                </a>

                <!-- Affiliates -->
                <a href="{{ route('admin.affiliates.index') }}"
                   class="flex items-center px-3 py-2.5 mb-1 text-gray-300 hover:bg-gradient-to-r hover:from-indigo-600 hover:to-purple-600 hover:text-white rounded-lg transition-all duration-200 group {{ request()->routeIs('admin.affiliates.*') ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-lg' : '' }}">
                    <span class="text-xl transition-all" :class="{ 'md:mx-auto': sidebarCollapsed }">🌐</span>
                    <span class="ml-3 text-sm font-medium transition-all" :class="{ 'md:hidden': sidebarCollapsed }" x-show="!sidebarCollapsed || sidebarOpen">Affiliates</span>
                </a>

                <!-- Commissions -->
                <a href="{{ route('admin.commissions.index') }}"
                   class="flex items-center px-3 py-2.5 mb-1 text-gray-300 hover:bg-gradient-to-r hover:from-indigo-600 hover:to-purple-600 hover:text-white rounded-lg transition-all duration-200 group {{ request()->routeIs('admin.commissions.*') ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-lg' : '' }}">
                    <span class="text-xl transition-all" :class="{ 'md:mx-auto': sidebarCollapsed }">💰</span>
                    <span class="ml-3 text-sm font-medium transition-all" :class="{ 'md:hidden': sidebarCollapsed }" x-show="!sidebarCollapsed || sidebarOpen">คอมมิชชั่น</span>
                </a>

                <!-- Wallet Dropdown Menu -->
                <div x-data="{ walletOpen: false }" @mouseenter="!sidebarCollapsed ? walletOpen = true : null" @mouseleave="walletOpen = false" class="relative mb-1">
                    @php
                        $walletActive = request()->routeIs('admin.wallet.*') || request()->routeIs('admin.withdrawals.*') || request()->routeIs('admin.wallet-settings.*');
                        $pendingCount = \App\Models\WithdrawalRequest::pending()->count();
                    @endphp

                    <!-- Main Wallet Button -->
                    <button
                       class="flex items-center w-full px-3 py-2.5 text-gray-300 hover:bg-gradient-to-r hover:from-indigo-600 hover:to-purple-600 hover:text-white rounded-lg transition-all duration-200 group {{ $walletActive ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-lg' : '' }}"
                       @click="sidebarCollapsed ? null : (walletOpen = !walletOpen)">
                        <span class="text-xl transition-all" :class="{ 'md:mx-auto': sidebarCollapsed }">💳</span>
                        <span class="ml-3 text-sm font-medium transition-all flex-1 text-left" :class="{ 'md:hidden': sidebarCollapsed }" x-show="!sidebarCollapsed || sidebarOpen">
                            กระเป๋าเงิน
                        </span>
                        @if($pendingCount > 0)
                            <span class="ml-auto bg-red-500 text-white text-xs rounded-full px-1.5 py-0.5 animate-pulse" :class="{ 'md:hidden': sidebarCollapsed }" x-show="!sidebarCollapsed || sidebarOpen">{{ $pendingCount }}</span>
                        @endif
                        <svg class="w-3.5 h-3.5 ml-2 transition-transform duration-200" :class="{ 'rotate-180': walletOpen, 'md:hidden': sidebarCollapsed }" x-show="!sidebarCollapsed || sidebarOpen" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <!-- Dropdown Submenu -->
                    <div x-show="walletOpen && (!sidebarCollapsed || sidebarOpen)"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 transform -translate-y-2"
                         x-transition:enter-end="opacity-100 transform translate-y-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         class="mt-1 mb-2 ml-3 space-y-0.5 bg-gray-800/30 rounded-lg p-1.5 backdrop-blur-sm border border-gray-700/30"
                         style="display: none;">

                        <a href="{{ route('admin.wallet.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-indigo-500 hover:to-purple-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.wallet.index') ? 'bg-gradient-to-r from-indigo-500 to-purple-500 text-white' : '' }}">
                            <span class="mr-2">👛</span>
                            <span>กระเป๋าของฉัน</span>
                        </a>

                        <a href="{{ route('admin.wallet.transactions') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-indigo-500 hover:to-purple-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.wallet.transactions') ? 'bg-gradient-to-r from-indigo-500 to-purple-500 text-white' : '' }}">
                            <span class="mr-2">📝</span>
                            <span>ธุรกรรม</span>
                        </a>

                        @if(auth()->user()->hasPermission('view_all_wallets') || auth()->user()->isSuperAdmin())
                        <a href="{{ route('admin.wallet.all') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-indigo-500 hover:to-purple-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.wallet.all') || request()->routeIs('admin.wallet.show') ? 'bg-gradient-to-r from-indigo-500 to-purple-500 text-white' : '' }}">
                            <span class="mr-2">💼</span>
                            <span>กระเป๋าทั้งหมด</span>
                        </a>
                        @endif

                        @if(auth()->user()->hasPermission('approve_withdrawals') || auth()->user()->isSuperAdmin())
                        <div class="border-t border-gray-700/30 my-1"></div>

                        <a href="{{ route('admin.withdrawals.pending') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-yellow-500 hover:to-orange-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.withdrawals.pending') ? 'bg-gradient-to-r from-yellow-500 to-orange-500 text-white' : '' }}">
                            <span class="mr-2">⏳</span>
                            <span class="flex-1">รอดำเนินการ</span>
                            @if($pendingCount > 0)
                                <span class="bg-red-500 text-white text-[10px] rounded-full px-1.5 py-0.5 animate-pulse">{{ $pendingCount }}</span>
                            @endif
                        </a>

                        <a href="{{ route('admin.withdrawals.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-indigo-500 hover:to-purple-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.withdrawals.index') ? 'bg-gradient-to-r from-indigo-500 to-purple-500 text-white' : '' }}">
                            <span class="mr-2">💸</span>
                            <span>คำขอถอนทั้งหมด</span>
                        </a>
                        @endif

                        @if(auth()->user()->hasPermission('manage_wallet_settings') || auth()->user()->isSuperAdmin())
                        <div class="border-t border-gray-700/30 my-1"></div>

                        <a href="{{ route('admin.wallet-settings.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-indigo-500 hover:to-purple-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.wallet-settings.*') ? 'bg-gradient-to-r from-indigo-500 to-purple-500 text-white' : '' }}">
                            <span class="mr-2">⚙️</span>
                            <span>ตั้งค่าระบบ</span>
                        </a>
                        @endif

                        <a href="{{ route('admin.wallet.logs') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-indigo-500 hover:to-purple-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.wallet.logs') ? 'bg-gradient-to-r from-indigo-500 to-purple-500 text-white' : '' }}">
                            <span class="mr-2">🔒</span>
                            <span>ประวัติความปลอดภัย</span>
                        </a>
                    </div>
                </div>

                <!-- Divider -->
                <div class="border-t border-gray-700/50 my-3"></div>

                <!-- System Management Dropdown -->
                <div x-data="{ systemMenuOpen: false }" @mouseenter="!sidebarCollapsed ? systemMenuOpen = true : null" @mouseleave="systemMenuOpen = false" class="relative mb-1">
                    @php
                        $systemActive = request()->routeIs('admin.sliders.*') ||
                                       request()->routeIs('admin.premium-page.*') ||
                                       request()->routeIs('admin.header-editor.*') ||
                                       request()->routeIs('admin.templates.*') ||
                                       request()->routeIs('admin.pages.*') ||
                                       request()->routeIs('admin.seo.*') ||
                                       request()->routeIs('admin.settings.languages*') ||
                                       request()->routeIs('admin.translations.*') ||
                                       request()->routeIs('admin.notifications.*') ||
                                       request()->routeIs('admin.notification-templates.*') ||
                                       request()->routeIs('admin.settings.index');
                    @endphp

                    <!-- Main System Menu Button -->
                    <button
                       class="flex items-center w-full px-3 py-2.5 text-gray-300 hover:bg-gradient-to-r hover:from-emerald-600 hover:to-teal-600 hover:text-white rounded-lg transition-all duration-200 group {{ $systemActive ? 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white shadow-lg' : '' }}"
                       @click="sidebarCollapsed ? null : (systemMenuOpen = !systemMenuOpen)">
                        <span class="text-xl transition-all" :class="{ 'md:mx-auto': sidebarCollapsed }">⚙️</span>
                        <span class="ml-3 text-sm font-medium transition-all flex-1 text-left" :class="{ 'md:hidden': sidebarCollapsed }" x-show="!sidebarCollapsed || sidebarOpen">
                            จัดการระบบ
                        </span>
                        <svg class="w-3.5 h-3.5 ml-2 transition-transform duration-200" :class="{ 'rotate-180': systemMenuOpen, 'md:hidden': sidebarCollapsed }" x-show="!sidebarCollapsed || sidebarOpen" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <!-- Dropdown Submenu -->
                    <div x-show="systemMenuOpen && (!sidebarCollapsed || sidebarOpen)"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 transform -translate-y-2"
                         x-transition:enter-end="opacity-100 transform translate-y-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         class="mt-1 mb-2 ml-3 space-y-0.5 bg-gray-800/30 rounded-lg p-1.5 backdrop-blur-sm border border-gray-700/30"
                         style="display: none;">

                        <a href="{{ route('admin.sliders.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-emerald-500 hover:to-teal-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.sliders.*') ? 'bg-gradient-to-r from-emerald-500 to-teal-500 text-white' : '' }}">
                            <span class="mr-2">🖼️</span>
                            <span>สไลด์</span>
                        </a>

                        <a href="{{ route('admin.premium-page.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-emerald-500 hover:to-teal-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.premium-page.*') ? 'bg-gradient-to-r from-emerald-500 to-teal-500 text-white' : '' }}">
                            <span class="mr-2">🎨</span>
                            <span>จัดการหน้าแรก</span>
                        </a>

                        <a href="{{ route('admin.header-editor.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-emerald-500 hover:to-teal-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.header-editor.*') ? 'bg-gradient-to-r from-emerald-500 to-teal-500 text-white' : '' }}">
                            <span class="mr-2">📐</span>
                            <span>แก้ไข Header & Menu</span>
                        </a>

                        <a href="{{ route('admin.templates.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-emerald-500 hover:to-teal-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.templates.*') ? 'bg-gradient-to-r from-emerald-500 to-teal-500 text-white' : '' }}">
                            <span class="mr-2">🎭</span>
                            <span>Template Builder</span>
                        </a>

                        <a href="{{ route('admin.pages.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-emerald-500 hover:to-teal-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.pages.*') ? 'bg-gradient-to-r from-emerald-500 to-teal-500 text-white' : '' }}">
                            <span class="mr-2">📄</span>
                            <span>จัดการหน้าเพจ</span>
                        </a>

                        <a href="{{ route('admin.seo.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-emerald-500 hover:to-teal-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.seo.*') ? 'bg-gradient-to-r from-emerald-500 to-teal-500 text-white' : '' }}">
                            <span class="mr-2">🔍</span>
                            <span>จัดการ SEO</span>
                        </a>

                        <div class="border-t border-gray-700/30 my-1"></div>

                        <a href="{{ route('admin.settings.languages') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-emerald-500 hover:to-teal-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.settings.languages*') ? 'bg-gradient-to-r from-emerald-500 to-teal-500 text-white' : '' }}">
                            <span class="mr-2">🌍</span>
                            <span>จัดการภาษา</span>
                        </a>

                        <a href="{{ route('admin.translations.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-emerald-500 hover:to-teal-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.translations.*') ? 'bg-gradient-to-r from-emerald-500 to-teal-500 text-white' : '' }}">
                            <span class="mr-2">📝</span>
                            <span>ตั้งค่าการแปล</span>
                        </a>

                        <a href="{{ route('admin.notifications.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-emerald-500 hover:to-teal-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.notifications.*') && !request()->routeIs('admin.notification-templates.*') ? 'bg-gradient-to-r from-emerald-500 to-teal-500 text-white' : '' }}">
                            <span class="mr-2">🔔</span>
                            <span>จัดการการแจ้งเตือน</span>
                        </a>

                        <a href="{{ route('admin.notification-templates.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-emerald-500 hover:to-teal-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.notification-templates.*') ? 'bg-gradient-to-r from-emerald-500 to-teal-500 text-white' : '' }}">
                            <span class="mr-2">📋</span>
                            <span>เทมเพลตการแจ้งเตือน</span>
                        </a>

                        <div class="border-t border-gray-700/30 my-1"></div>

                        <a href="{{ route('admin.settings.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-emerald-500 hover:to-teal-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.settings.index') ? 'bg-gradient-to-r from-emerald-500 to-teal-500 text-white' : '' }}">
                            <span class="mr-2">⚙️</span>
                            <span>ตั้งค่าทั่วไป</span>
                        </a>
                    </div>
                </div>

                <!-- Divider -->
                <div class="border-t border-gray-700/50 my-3"></div>

                <!-- Quick Actions -->
                <a href="{{ route('home') }}"
                   class="flex items-center px-3 py-2.5 mb-1 text-gray-300 hover:bg-gradient-to-r hover:from-green-600 hover:to-teal-600 hover:text-white rounded-lg transition-all duration-200 group">
                    <span class="text-xl transition-all" :class="{ 'md:mx-auto': sidebarCollapsed }">🏠</span>
                    <span class="ml-3 text-sm font-medium transition-all" :class="{ 'md:hidden': sidebarCollapsed }" x-show="!sidebarCollapsed || sidebarOpen">กลับหน้าแรก</span>
                </a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex items-center w-full px-3 py-2.5 text-gray-300 hover:bg-gradient-to-r hover:from-red-600 hover:to-pink-600 hover:text-white rounded-lg transition-all duration-200 group">
                        <span class="text-xl transition-all" :class="{ 'md:mx-auto': sidebarCollapsed }">🚪</span>
                        <span class="ml-3 text-sm font-medium transition-all" :class="{ 'md:hidden': sidebarCollapsed }" x-show="!sidebarCollapsed || sidebarOpen">ออกจากระบบ</span>
                    </button>
                </form>

                <!-- Version Info -->
                <div class="mt-4 px-3" :class="{ 'md:hidden': sidebarCollapsed }" x-show="!sidebarCollapsed || sidebarOpen">
                    <div class="bg-gray-800/50 backdrop-blur-sm rounded-lg p-3 border border-gray-700/30">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs text-gray-400">เวอร์ชั่น</span>
                            <span class="text-xs font-semibold text-white bg-gradient-to-r from-indigo-500 to-purple-500 px-2 py-0.5 rounded-full">
                                {{ config('version.current') }}
                            </span>
                        </div>
                        @if(config('version.name'))
                            <p class="text-xs text-gray-500 mb-2">{{ config('version.name') }}</p>
                        @endif
                        <div class="text-[10px] text-gray-600 space-y-1">
                            <div class="flex items-center justify-between">
                                <span>Laravel</span>
                                <span class="text-gray-400">{{ app()->version() }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span>PHP</span>
                                <span class="text-gray-400">{{ PHP_VERSION }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Spacing at bottom for scrolling -->
                <div class="h-4"></div>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="transition-all duration-300" :class="{ 'md:ml-56': !sidebarCollapsed, 'md:ml-16': sidebarCollapsed }">
            <!-- Top Bar -->
            <header class="bg-white shadow-sm sticky top-0 z-20">
                <div class="flex items-center justify-between px-6 py-4">
                    <div class="flex items-center">
                        <button @click="sidebarOpen = !sidebarOpen" class="text-gray-500 hover:text-gray-700 focus:outline-none md:hidden mr-4 transition-colors">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>

                        <h1 class="text-2xl font-semibold text-gray-800">@yield('title')</h1>
                    </div>

                    <div class="flex items-center space-x-3">
                        <!-- Dashboard Switcher -->
                        <x-dashboard-switcher />

                        <!-- Theme Toggle -->
                        <x-theme-toggle />

                        <!-- Notification Bell -->
                        <x-notification-bell />

                        <!-- Language Switcher -->
                        <div class="relative z-[60]">
                            <x-language-switcher-pro />
                        </div>

                        <!-- Profile Dropdown -->
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" @click.away="open = false"
                                    class="flex items-center space-x-3 focus:outline-none hover:bg-gray-50 rounded-lg px-3 py-2 transition">
                                <img src="{{ Auth::user()->profile_picture_url }}"
                                     alt="{{ Auth::user()->name }}"
                                     class="h-10 w-10 rounded-full object-cover border-2 border-indigo-500 shadow-md">
                                <div class="hidden md:block text-left">
                                    <p class="text-sm font-semibold text-gray-700">{{ Auth::user()->name }}</p>
                                    <p class="text-xs text-gray-500">{{ ucfirst(Auth::user()->role) }}</p>
                                </div>
                                <svg class="h-4 w-4 text-gray-600 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>

                            <!-- Dropdown Menu -->
                            <div x-show="open"
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-150"
                                 x-transition:leave-start="opacity-100 scale-100"
                                 x-transition:leave-end="opacity-0 scale-95"
                                 class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-xl border border-gray-200 py-2 z-[70]"
                                 style="display: none;">
                                <div class="px-4 py-3 border-b border-gray-100">
                                    <p class="text-sm font-medium text-gray-900">{{ Auth::user()->name }}</p>
                                    <p class="text-sm text-gray-500 truncate">{{ Auth::user()->email }}</p>
                                </div>

                                <a href="{{ route('user.profile') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 transition">
                                    <span class="text-xl mr-3">👤</span>
                                    <span>โปรไฟล์</span>
                                </a>

                                <a href="{{ route('user.dashboard') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 transition">
                                    <span class="text-xl mr-3">📊</span>
                                    <span>แดชบอร์ดผู้ใช้</span>
                                </a>

                                <div class="border-t border-gray-100 my-1"></div>

                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="flex items-center w-full px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition">
                                        <span class="text-xl mr-3">🚪</span>
                                        <span>ออกจากระบบ</span>
                                    </button>
                                </form>
                            </div>
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

        @if (session('warning'))
            <div x-data="{ show: true }"
                 x-show="show"
                 x-init="setTimeout(() => show = false, 5000)"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform translate-x-full"
                 x-transition:enter-end="opacity-100 transform translate-x-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 transform translate-x-0"
                 x-transition:leave-end="opacity-0 transform translate-x-full"
                 class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 px-4 py-3 rounded-lg shadow-lg flex items-start justify-between min-w-[320px]">
                <div class="flex items-start">
                    <svg class="h-6 w-6 text-yellow-500 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <span class="font-medium">{{ session('warning') }}</span>
                </div>
                <button @click="show = false" class="ml-4 text-yellow-700 hover:text-yellow-900 flex-shrink-0">
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
                 class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 px-4 py-3 rounded-lg shadow-lg flex items-start justify-between min-w-[320px]">
                <div class="flex items-start">
                    <svg class="h-6 w-6 text-blue-500 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="font-medium">{{ session('info') }}</span>
                </div>
                <button @click="show = false" class="ml-4 text-blue-700 hover:text-blue-900 flex-shrink-0">
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

        // Animate page entrance
        document.addEventListener('DOMContentLoaded', function() {
            // Fade in content
            gsap.from('main > *', {
                opacity: 0,
                y: 20,
                duration: 0.6,
                stagger: 0.1,
                ease: 'power2.out'
            });

            // Animate cards on scroll
            const cards = document.querySelectorAll('.bg-white');
            cards.forEach(card => {
                gsap.from(card, {
                    scrollTrigger: {
                        trigger: card,
                        start: 'top 80%',
                        toggleActions: 'play none none reverse'
                    },
                    opacity: 0,
                    y: 30,
                    duration: 0.5,
                    ease: 'power2.out'
                });
            });

            // Animate buttons on hover
            const buttons = document.querySelectorAll('button:not([type=submit]), .btn');
            buttons.forEach(button => {
                button.addEventListener('mouseenter', () => {
                    gsap.to(button, { scale: 1.05, duration: 0.2 });
                });
                button.addEventListener('mouseleave', () => {
                    gsap.to(button, { scale: 1, duration: 0.2 });
                });
            });
        });
    </script>

    {{-- Google Translate Widget (Like WordPress Plugins) --}}

    @stack('scripts')
</body>
</html>
