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

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

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
    <!-- Page Loader -->
    <x-page-loader />
    <div class="min-h-screen" x-data="{
        sidebarOpen: false,
        sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
        profileDropdown: false,
        systemMenuOpen: false,
        // Dropdown states with persistence
        marketingMenuOpen: localStorage.getItem('marketingMenuOpen') === 'true',
        walletOpen: localStorage.getItem('walletOpen') === 'true',
        emailMenuOpen: localStorage.getItem('emailMenuOpen') === 'true',
        lineMenuOpen: localStorage.getItem('lineMenuOpen') === 'true',
        accountingMenuOpen: localStorage.getItem('accountingMenuOpen') === 'true',
        ecommerceMenuOpen: localStorage.getItem('ecommerceMenuOpen') === 'true',
        mlmMenuOpen: localStorage.getItem('mlmMenuOpen') === 'true',
        posMenuOpen: localStorage.getItem('posMenuOpen') === 'true',
        academyMenuOpen: localStorage.getItem('academyMenuOpen') === 'true',
        // Auto-open dropdown if current page is in submenu
        init() {
            this.checkActiveMenu();
        },
        toggleMenu(menuName) {
            this[menuName] = !this[menuName];
            localStorage.setItem(menuName, this[menuName]);
        },
        checkActiveMenu() {
            const currentPath = window.location.pathname;

            // Reset all menus first
            this.marketingMenuOpen = false;
            this.walletOpen = false;
            this.emailMenuOpen = false;
            this.lineMenuOpen = false;
            this.systemMenuOpen = false;
            this.accountingMenuOpen = false;
            this.ecommerceMenuOpen = false;
            this.mlmMenuOpen = false;
            this.posMenuOpen = false;
            this.academyMenuOpen = false;

            // Open only the relevant menu based on current path
            if (currentPath.includes('/admin/line-oa') || currentPath.includes('/admin/line-bot') || currentPath.includes('/admin/otp') || currentPath.includes('/admin/ai-bots') || currentPath.includes('/admin/ai-providers') || currentPath.includes('/admin/ai-monitoring') || currentPath.includes('/admin/ai-installation')) {
                this.lineMenuOpen = true;
            } else if (currentPath.includes('/admin/affiliates') || currentPath.includes('/admin/retention') || currentPath.includes('/admin/ranks')) {
                this.marketingMenuOpen = true;
            } else if (currentPath.includes('/admin/wallet') || currentPath.includes('/admin/withdrawals') || currentPath.includes('/admin/payment-gateways')) {
                this.walletOpen = true;
            } else if (currentPath.includes('/admin/email')) {
                this.emailMenuOpen = true;
            } else if (currentPath.includes('/admin/accounting')) {
                this.accountingMenuOpen = true;
            } else if (currentPath.includes('/admin/ecommerce')) {
                this.ecommerceMenuOpen = true;
            } else if (currentPath.includes('/admin/mlm')) {
                this.mlmMenuOpen = true;
            } else if (currentPath.includes('/admin/pos') || currentPath.includes('/pos/')) {
                this.posMenuOpen = true;
            } else if (currentPath.includes('/admin/academy') || currentPath.includes('/admin/learning-center') || currentPath.includes('/admin/instructor') || currentPath.includes('/admin/quiz') || currentPath.includes('/admin/certificates')) {
                this.academyMenuOpen = true;
            } else if (currentPath.includes('/admin/windows-ui') || currentPath.includes('/admin/settings') || currentPath.includes('/admin/premium-page') || currentPath.includes('/admin/header-editor') || currentPath.includes('/admin/templates') || currentPath.includes('/admin/pages') || currentPath.includes('/admin/seo') || currentPath.includes('/admin/translations') || currentPath.includes('/admin/notifications') || currentPath.includes('/admin/roles')) {
                this.systemMenuOpen = true;
            }

            // Update localStorage for all menus
            localStorage.setItem('marketingMenuOpen', this.marketingMenuOpen);
            localStorage.setItem('walletOpen', this.walletOpen);
            localStorage.setItem('emailMenuOpen', this.emailMenuOpen);
            localStorage.setItem('lineMenuOpen', this.lineMenuOpen);
            localStorage.setItem('accountingMenuOpen', this.accountingMenuOpen);
            localStorage.setItem('ecommerceMenuOpen', this.ecommerceMenuOpen);
            localStorage.setItem('mlmMenuOpen', this.mlmMenuOpen);
            localStorage.setItem('posMenuOpen', this.posMenuOpen);
            localStorage.setItem('academyMenuOpen', this.academyMenuOpen);
            localStorage.setItem('systemMenuOpen', this.systemMenuOpen);
        }
    }">
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
                    <img src="{{ asset($logo) }}" alt="Logo" width="48" height="48" class="h-12 object-contain" :class="{ 'md:h-10': sidebarCollapsed }">
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

                <!-- KYC Verification -->
                @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('view_kyc_verifications'))
                    @php
                        $pendingKycCount = \App\Models\KycVerification::pending()->count();
                    @endphp
                    <a href="{{ route('admin.kyc.index') }}"
                       class="flex items-center px-3 py-2.5 mb-1 text-gray-300 hover:bg-gradient-to-r hover:from-indigo-600 hover:to-purple-600 hover:text-white rounded-lg transition-all duration-200 group {{ request()->routeIs('admin.kyc.*') ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-lg' : '' }}">
                        <span class="text-xl transition-all" :class="{ 'md:mx-auto': sidebarCollapsed }">🪪</span>
                        <span class="ml-3 text-sm font-medium transition-all flex-1" :class="{ 'md:hidden': sidebarCollapsed }" x-show="!sidebarCollapsed || sidebarOpen">ยืนยันตัวตน (KYC)</span>
                        @if($pendingKycCount > 0)
                            <span class="ml-auto bg-red-500 text-white text-xs rounded-full px-1.5 py-0.5 animate-pulse" :class="{ 'md:hidden': sidebarCollapsed }" x-show="!sidebarCollapsed || sidebarOpen">{{ $pendingKycCount }}</span>
                        @endif
                    </a>
                @endif

                <!-- Marketing System Dropdown -->
                <div class="relative mb-1">
                    @php
                        $marketingActive = request()->routeIs('admin.affiliates.*') ||
                                          request()->routeIs('admin.retention.*') ||
                                          request()->routeIs('admin.ranks.*');
                    @endphp

                    <!-- Main Marketing Menu Button -->
                    <button
                       class="flex items-center w-full px-3 py-2.5 text-gray-300 hover:bg-gradient-to-r hover:from-pink-600 hover:to-rose-600 hover:text-white rounded-lg transition-all duration-200 group {{ $marketingActive ? 'bg-gradient-to-r from-pink-600 to-rose-600 text-white shadow-lg' : '' }}"
                       @click="toggleMenu('marketingMenuOpen')">
                        <span class="text-xl transition-all" :class="{ 'md:mx-auto': sidebarCollapsed }">📈</span>
                        <span class="ml-3 text-sm font-medium transition-all flex-1 text-left" :class="{ 'md:hidden': sidebarCollapsed }" x-show="!sidebarCollapsed || sidebarOpen">
                            ระบบการตลาด
                        </span>
                        <svg class="w-3.5 h-3.5 ml-2 transition-transform duration-200" :class="{ 'rotate-180': marketingMenuOpen, 'md:hidden': sidebarCollapsed }" x-show="!sidebarCollapsed || sidebarOpen" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <!-- Dropdown Submenu -->
                    <div x-show="marketingMenuOpen && (!sidebarCollapsed || sidebarOpen)"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 transform -translate-y-2"
                         x-transition:enter-end="opacity-100 transform translate-y-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         class="mt-1 mb-2 ml-3 space-y-0.5 bg-gray-800/30 rounded-lg p-1.5 backdrop-blur-sm border border-gray-700/30"
                         style="display: none;">

                        <!-- Affiliates Management -->
                        <a href="{{ route('admin.affiliates.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-pink-500 hover:to-rose-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.affiliates.*') && !request()->routeIs('admin.affiliates.tree') ? 'bg-gradient-to-r from-pink-500 to-rose-500 text-white' : '' }}">
                            <span class="mr-2">🌐</span>
                            <span>Affiliates</span>
                        </a>

                        <a href="{{ route('admin.affiliates.tree') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-pink-500 hover:to-rose-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.affiliates.tree') ? 'bg-gradient-to-r from-pink-500 to-rose-500 text-white' : '' }}">
                            <span class="mr-2">🌳</span>
                            <span>โครงสร้างทีม</span>
                        </a>

                        <div class="border-t border-gray-700/30 my-1"></div>

                        <!-- Membership Retention -->
                        <a href="{{ route('admin.retention.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-pink-500 hover:to-rose-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.retention.*') ? 'bg-gradient-to-r from-pink-500 to-rose-500 text-white' : '' }}">
                            <span class="mr-2">💖</span>
                            <span>ระบบรักษายอด</span>
                        </a>

                        <div class="border-t border-gray-700/30 my-1"></div>

                        <!-- Rank Management -->
                        <a href="{{ route('admin.ranks.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-pink-500 hover:to-rose-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.ranks.index') || request()->routeIs('admin.ranks.edit') || request()->routeIs('admin.ranks.create') ? 'bg-gradient-to-r from-pink-500 to-rose-500 text-white' : '' }}">
                            <span class="mr-2">🏆</span>
                            <span>จัดการระดับ Rank</span>
                        </a>

                        <a href="{{ route('admin.ranks.promotions.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-pink-500 hover:to-rose-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.ranks.promotions.*') ? 'bg-gradient-to-r from-pink-500 to-rose-500 text-white' : '' }}">
                            <span class="mr-2">⬆️</span>
                            <span>การเลื่อนระดับ</span>
                        </a>

                        <div class="border-t border-gray-700/30 my-1"></div>

                        <!-- Marketing Settings -->
                        <div class="px-2 py-1">
                            <span class="text-[10px] text-gray-500 uppercase font-semibold">การตั้งค่า</span>
                        </div>

                        <!-- Affiliate Settings would go here if you have a dedicated settings page -->
                        <!-- For now, these can be added later based on your requirements -->

                    </div>
                </div>

                <!-- Commissions -->
                <a href="{{ route('admin.commissions.index') }}"
                   class="flex items-center px-3 py-2.5 mb-1 text-gray-300 hover:bg-gradient-to-r hover:from-indigo-600 hover:to-purple-600 hover:text-white rounded-lg transition-all duration-200 group {{ request()->routeIs('admin.commissions.*') ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-lg' : '' }}">
                    <span class="text-xl transition-all" :class="{ 'md:mx-auto': sidebarCollapsed }">💰</span>
                    <span class="ml-3 text-sm font-medium transition-all" :class="{ 'md:hidden': sidebarCollapsed }" x-show="!sidebarCollapsed || sidebarOpen">คอมมิชชั่น</span>
                </a>

                <!-- Wallet Dropdown Menu -->
                <div class="relative mb-1">
                    @php
                        $walletActive = request()->routeIs('admin.wallet.*') || request()->routeIs('admin.withdrawals.*') || request()->routeIs('admin.wallet-settings.*') || request()->routeIs('admin.payment-gateways.*');
                        $pendingCount = \App\Models\WithdrawalRequest::pending()->count();
                    @endphp

                    <!-- Main Wallet Button -->
                    <button
                       class="flex items-center w-full px-3 py-2.5 text-gray-300 hover:bg-gradient-to-r hover:from-indigo-600 hover:to-purple-600 hover:text-white rounded-lg transition-all duration-200 group {{ $walletActive ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-lg' : '' }}"
                       @click="toggleMenu('walletOpen')">
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

                        @if(auth()->user()->hasPermission('manage_payment_settings') || auth()->user()->isSuperAdmin())
                        <a href="{{ route('admin.payment-gateways.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-blue-500 hover:to-cyan-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.payment-gateways.*') ? 'bg-gradient-to-r from-blue-500 to-cyan-500 text-white' : '' }}">
                            <span class="mr-2">💳</span>
                            <span>Payment Gateways</span>
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

                <!-- Security -->
                <a href="{{ route('admin.security.index') }}"
                   class="flex items-center px-3 py-2.5 mb-1 text-gray-300 hover:bg-gradient-to-r hover:from-red-600 hover:to-pink-600 hover:text-white rounded-lg transition-all duration-200 group {{ request()->routeIs('admin.security.*') ? 'bg-gradient-to-r from-red-600 to-pink-600 text-white shadow-lg' : '' }}">
                    <span class="text-xl transition-all" :class="{ 'md:mx-auto': sidebarCollapsed }">🛡️</span>
                    <span class="ml-3 text-sm font-medium transition-all" :class="{ 'md:hidden': sidebarCollapsed }" x-show="!sidebarCollapsed || sidebarOpen">ความปลอดภัย</span>
                </a>

                <!-- Academy System Dropdown -->
                <div class="relative mb-1">
                    @php
                        $academyActive = request()->routeIs('admin.learning-center.*') ||
                                         request()->routeIs('admin.academy.*') ||
                                         request()->routeIs('admin.instructor.*') ||
                                         request()->routeIs('admin.quiz.*') ||
                                         request()->routeIs('admin.certificates.*');
                    @endphp

                    <!-- Main Academy Menu Button -->
                    <button
                       class="flex items-center w-full px-3 py-2.5 text-gray-300 hover:bg-gradient-to-r hover:from-purple-600 hover:to-indigo-600 hover:text-white rounded-lg transition-all duration-200 group {{ $academyActive ? 'bg-gradient-to-r from-purple-600 to-indigo-600 text-white shadow-lg' : '' }}"
                       @click="toggleMenu('academyMenuOpen')">
                        <span class="text-xl transition-all" :class="{ 'md:mx-auto': sidebarCollapsed }">🎓</span>
                        <span class="ml-3 text-sm font-medium transition-all flex-1 text-left" :class="{ 'md:hidden': sidebarCollapsed }" x-show="!sidebarCollapsed || sidebarOpen">
                            Academy System
                        </span>
                        <svg class="w-3.5 h-3.5 ml-2 transition-transform duration-200" :class="{ 'rotate-180': academyMenuOpen, 'md:hidden': sidebarCollapsed }" x-show="!sidebarCollapsed || sidebarOpen" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <!-- Dropdown Submenu -->
                    <div x-show="academyMenuOpen && (!sidebarCollapsed || sidebarOpen)"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 transform -translate-y-2"
                         x-transition:enter-end="opacity-100 transform translate-y-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         class="mt-1 mb-2 ml-3 space-y-0.5 bg-gray-800/30 rounded-lg p-1.5 backdrop-blur-sm border border-gray-700/30"
                         style="display: none;">

                        <!-- Academy Home Section -->
                        <div class="px-2 py-1">
                            <span class="text-[10px] text-gray-500 uppercase font-semibold">หน้าหลัก</span>
                        </div>

                        <a href="{{ route('admin.learning-center.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-purple-500 hover:to-indigo-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.learning-center.index') ? 'bg-gradient-to-r from-purple-500 to-indigo-500 text-white' : '' }}">
                            <span class="mr-2">🏠</span>
                            <span>หน้าแรก Academy</span>
                        </a>

                        <a href="{{ route('admin.instructor.dashboard') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-purple-500 hover:to-indigo-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.instructor.dashboard') ? 'bg-gradient-to-r from-purple-500 to-indigo-500 text-white' : '' }}">
                            <span class="mr-2">👨‍🏫</span>
                            <span>แดชบอร์ดครูผู้สอน</span>
                        </a>

                        <a href="{{ route('admin.certificates.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-purple-500 hover:to-indigo-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.certificates.index') ? 'bg-gradient-to-r from-purple-500 to-indigo-500 text-white' : '' }}">
                            <span class="mr-2">🏆</span>
                            <span>ใบประกาศนียบัตรของฉัน</span>
                        </a>

                        <div class="border-t border-gray-700/30 my-1"></div>

                        <!-- Management Section -->
                        <div class="px-2 py-1">
                            <span class="text-[10px] text-gray-500 uppercase font-semibold">จัดการคอร์ส</span>
                        </div>

                        <a href="{{ route('admin.articles.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-purple-500 hover:to-indigo-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.articles.*') ? 'bg-gradient-to-r from-purple-500 to-indigo-500 text-white' : '' }}">
                            <span class="mr-2">📝</span>
                            <span>จัดการคอร์ส</span>
                        </a>

                        <a href="{{ route('admin.categories.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-purple-500 hover:to-indigo-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.categories.*') ? 'bg-gradient-to-r from-purple-500 to-indigo-500 text-white' : '' }}">
                            <span class="mr-2">📂</span>
                            <span>หมวดหมู่</span>
                        </a>

                        <div class="border-t border-gray-700/30 my-1"></div>

                        <!-- Certificate Management Section -->
                        <div class="px-2 py-1">
                            <span class="text-[10px] text-gray-500 uppercase font-semibold">ใบประกาศนียบัตร</span>
                        </div>

                        <a href="{{ route('admin.academy.certificates.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-purple-500 hover:to-indigo-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.academy.certificates.index') ? 'bg-gradient-to-r from-purple-500 to-indigo-500 text-white' : '' }}">
                            <span class="mr-2">📜</span>
                            <span>จัดการใบประกาศ</span>
                        </a>

                        <a href="{{ route('admin.academy.certificates.create') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-purple-500 hover:to-indigo-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.academy.certificates.create') ? 'bg-gradient-to-r from-purple-500 to-indigo-500 text-white' : '' }}">
                            <span class="mr-2">➕</span>
                            <span>สร้างใบประกาศ</span>
                        </a>

                        <div class="border-t border-gray-700/30 my-1"></div>

                        <!-- Settings Section -->
                        <div class="px-2 py-1">
                            <span class="text-[10px] text-gray-500 uppercase font-semibold">การตั้งค่า</span>
                        </div>

                        <a href="{{ route('admin.academy.settings.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-purple-500 hover:to-indigo-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.academy.settings.*') ? 'bg-gradient-to-r from-purple-500 to-indigo-500 text-white' : '' }}">
                            <span class="mr-2">⚙️</span>
                            <span>ตั้งค่า Academy</span>
                        </a>
                    </div>
                </div>

                <!-- Email Management Dropdown -->
                <div class="relative mb-1">
                    @php
                        $emailActive = request()->routeIs('admin.email.*');
                    @endphp

                    <!-- Main Email Menu Button -->
                    <button
                       class="flex items-center w-full px-3 py-2.5 text-gray-300 hover:bg-gradient-to-r hover:from-blue-600 hover:to-cyan-600 hover:text-white rounded-lg transition-all duration-200 group {{ $emailActive ? 'bg-gradient-to-r from-blue-600 to-cyan-600 text-white shadow-lg' : '' }}"
                       @click="toggleMenu('emailMenuOpen')">
                        <span class="text-xl transition-all" :class="{ 'md:mx-auto': sidebarCollapsed }">📧</span>
                        <span class="ml-3 text-sm font-medium transition-all flex-1 text-left" :class="{ 'md:hidden': sidebarCollapsed }" x-show="!sidebarCollapsed || sidebarOpen">
                            จัดการอีเมล
                        </span>
                        <svg class="w-3.5 h-3.5 ml-2 transition-transform duration-200" :class="{ 'rotate-180': emailMenuOpen, 'md:hidden': sidebarCollapsed }" x-show="!sidebarCollapsed || sidebarOpen" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <!-- Dropdown Submenu -->
                    <div x-show="emailMenuOpen && (!sidebarCollapsed || sidebarOpen)"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 transform -translate-y-2"
                         x-transition:enter-end="opacity-100 transform translate-y-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         class="mt-1 mb-2 ml-3 space-y-0.5 bg-gray-800/30 rounded-lg p-1.5 backdrop-blur-sm border border-gray-700/30"
                         style="display: none;">

                        <a href="{{ route('admin.email.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-blue-500 hover:to-cyan-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.email.index') ? 'bg-gradient-to-r from-blue-500 to-cyan-500 text-white' : '' }}">
                            <span class="mr-2">📊</span>
                            <span>แดชบอร์ด</span>
                        </a>

                        <a href="{{ route('admin.email.logs') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-blue-500 hover:to-cyan-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.email.logs') || request()->routeIs('admin.email.logs.show') ? 'bg-gradient-to-r from-blue-500 to-cyan-500 text-white' : '' }}">
                            <span class="mr-2">📝</span>
                            <span>ประวัติการส่ง</span>
                        </a>

                        <div class="border-t border-gray-700/30 my-1"></div>

                        <a href="{{ route('admin.email.providers') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-blue-500 hover:to-cyan-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.email.providers*') ? 'bg-gradient-to-r from-blue-500 to-cyan-500 text-white' : '' }}">
                            <span class="mr-2">🔌</span>
                            <span>Email Providers</span>
                        </a>

                        <a href="{{ route('admin.email.templates') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-blue-500 hover:to-cyan-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.email.templates*') ? 'bg-gradient-to-r from-blue-500 to-cyan-500 text-white' : '' }}">
                            <span class="mr-2">📋</span>
                            <span>Email Templates</span>
                        </a>
                    </div>
                </div>

                <!-- LINE Management Dropdown -->
                <div class="relative mb-1">
                    @php
                        $lineActive = request()->routeIs('admin.line-oa.*') ||
                                      request()->routeIs('admin.line-bot.*') ||
                                      request()->routeIs('admin.otp.*');
                    @endphp

                    <!-- Main LINE Menu Button -->
                    <button
                       class="flex items-center w-full px-3 py-2.5 text-gray-300 hover:bg-gradient-to-r hover:from-green-600 hover:to-emerald-600 hover:text-white rounded-lg transition-all duration-200 group {{ $lineActive ? 'bg-gradient-to-r from-green-600 to-emerald-600 text-white shadow-lg' : '' }}"
                       @click="toggleMenu('lineMenuOpen')">
                        <span class="text-xl transition-all" :class="{ 'md:mx-auto': sidebarCollapsed }">
                            <svg class="w-5 h-5 inline-block" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                            </svg>
                        </span>
                        <span class="ml-3 text-sm font-medium transition-all flex-1 text-left" :class="{ 'md:hidden': sidebarCollapsed }" x-show="!sidebarCollapsed || sidebarOpen">
                            Line & AI
                        </span>
                        <svg class="w-3.5 h-3.5 ml-2 transition-transform duration-200" :class="{ 'rotate-180': lineMenuOpen, 'md:hidden': sidebarCollapsed }" x-show="!sidebarCollapsed || sidebarOpen" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <!-- Dropdown Submenu -->
                    <div x-show="lineMenuOpen && (!sidebarCollapsed || sidebarOpen)"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 transform -translate-y-2"
                         x-transition:enter-end="opacity-100 transform translate-y-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         class="mt-1 mb-2 ml-3 space-y-0.5 bg-gray-800/30 rounded-lg p-1.5 backdrop-blur-sm border border-gray-700/30"
                         style="display: none;">

                        <!-- LINE OA Settings Section -->
                        <div class="px-2 py-1">
                            <span class="text-[10px] text-gray-500 uppercase font-semibold">การตั้งค่า</span>
                        </div>

                        <a href="{{ route('admin.line-oa.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-green-500 hover:to-emerald-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.line-oa.index') ? 'bg-gradient-to-r from-green-500 to-emerald-500 text-white' : '' }}">
                            <span class="mr-2">⚙️</span>
                            <span>ตั้งค่า LINE OA</span>
                        </a>

                        <a href="{{ route('admin.line-oa.logs') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-green-500 hover:to-emerald-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.line-oa.logs') ? 'bg-gradient-to-r from-green-500 to-emerald-500 text-white' : '' }}">
                            <span class="mr-2">📊</span>
                            <span>ประวัติการใช้งาน</span>
                        </a>

                        <a href="{{ route('admin.otp.settings') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-green-500 hover:to-emerald-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.otp.*') ? 'bg-gradient-to-r from-green-500 to-emerald-500 text-white' : '' }}">
                            <span class="mr-2">🔐</span>
                            <span>ตั้งค่า OTP</span>
                        </a>

                        <div class="border-t border-gray-700/30 my-1"></div>

                        <!-- AI Chat Bot Section -->
                        <div class="px-2 py-1 mt-1">
                            <span class="text-[10px] text-purple-400 uppercase font-bold tracking-wider">🤖 AI Chat Bot</span>
                        </div>

                        <a href="{{ route('admin.line-bot.ai.index') }}"
                           class="flex items-center px-3 py-2 text-sm text-gray-300 hover:bg-gradient-to-r hover:from-purple-600 hover:to-indigo-600 hover:text-white rounded-lg transition-all duration-200 {{ request()->routeIs('admin.line-bot.ai.*') ? 'bg-gradient-to-r from-purple-600 to-indigo-600 text-white shadow-lg' : '' }}">
                            <span class="mr-2">⚡</span>
                            <span class="font-semibold">AI Settings</span>
                        </a>

                        <a href="{{ route('admin.line-bot.ai.conversations') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-purple-500 hover:to-indigo-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.line-bot.ai.conversations') ? 'bg-gradient-to-r from-purple-500 to-indigo-500 text-white' : '' }}">
                            <span class="mr-2">💬</span>
                            <span>Conversations</span>
                        </a>

                        <a href="{{ route('admin.line-bot.ai.analytics') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-purple-500 hover:to-indigo-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.line-bot.ai.analytics') ? 'bg-gradient-to-r from-purple-500 to-indigo-500 text-white' : '' }}">
                            <span class="mr-2">📊</span>
                            <span>Analytics</span>
                        </a>

                        <div class="border-t border-gray-700/30 my-2"></div>

                        <!-- AI System Section -->
                        <div class="px-2 py-1">
                            <span class="text-[10px] text-gray-500 uppercase font-semibold">ระบบ AI</span>
                        </div>

                        <a href="{{ route('admin.ai-bots.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-green-500 hover:to-emerald-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.ai-bots.*') ? 'bg-gradient-to-r from-green-500 to-emerald-500 text-white' : '' }}">
                            <span class="mr-2">🤖</span>
                            <span>AI Bots</span>
                        </a>

                        <a href="{{ route('admin.ai-providers.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-green-500 hover:to-emerald-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.ai-providers.*') ? 'bg-gradient-to-r from-green-500 to-emerald-500 text-white' : '' }}">
                            <span class="mr-2">🔌</span>
                            <span>AI Providers</span>
                        </a>

                        <a href="{{ route('admin.ai-monitoring.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-green-500 hover:to-emerald-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.ai-monitoring.*') ? 'bg-gradient-to-r from-green-500 to-emerald-500 text-white' : '' }}">
                            <span class="mr-2">📊</span>
                            <span>AI Monitoring</span>
                        </a>

                        <a href="{{ route('admin.ai-installation.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-green-500 hover:to-emerald-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.ai-installation.*') ? 'bg-gradient-to-r from-green-500 to-emerald-500 text-white' : '' }}">
                            <span class="mr-2">⚙️</span>
                            <span>AI Installation</span>
                        </a>

                        <div class="border-t border-gray-700/30 my-1"></div>

                        <!-- Messages Section -->
                        <div class="px-2 py-1">
                            <span class="text-[10px] text-gray-500 uppercase font-semibold">ข้อความ & เมนู</span>
                        </div>

                        <a href="{{ route('admin.line-bot.flex.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-green-500 hover:to-emerald-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.line-bot.flex.*') ? 'bg-gradient-to-r from-green-500 to-emerald-500 text-white' : '' }}">
                            <span class="mr-2">💬</span>
                            <span>Flex Messages</span>
                        </a>

                        <a href="{{ route('admin.line-bot.rich-menu.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-green-500 hover:to-emerald-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.line-bot.rich-menu.*') ? 'bg-gradient-to-r from-green-500 to-emerald-500 text-white' : '' }}">
                            <span class="mr-2">📱</span>
                            <span>Rich Menus</span>
                        </a>

                        <a href="{{ route('admin.line-bot.broadcast.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-green-500 hover:to-emerald-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.line-bot.broadcast.*') ? 'bg-gradient-to-r from-green-500 to-emerald-500 text-white' : '' }}">
                            <span class="mr-2">📢</span>
                            <span>Broadcast</span>
                        </a>

                        <div class="border-t border-gray-700/30 my-1"></div>

                        <!-- Widget Section -->
                        <div class="px-2 py-1">
                            <span class="text-[10px] text-gray-500 uppercase font-semibold">Chat Widget</span>
                        </div>

                        <a href="{{ route('admin.line-bot.chat-widget.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-green-500 hover:to-emerald-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.line-bot.chat-widget.*') ? 'bg-gradient-to-r from-green-500 to-emerald-500 text-white' : '' }}">
                            <span class="mr-2">💭</span>
                            <span>ตั้งค่า Widget</span>
                        </a>

                        <a href="{{ route('admin.line-bot.avatars.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-green-500 hover:to-emerald-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.line-bot.avatars.*') ? 'bg-gradient-to-r from-green-500 to-emerald-500 text-white' : '' }}">
                            <span class="mr-2">🎭</span>
                            <span>Avatars</span>
                        </a>

                        <div class="border-t border-gray-700/30 my-1"></div>

                        <!-- Resources Section -->
                        <div class="px-2 py-1">
                            <span class="text-[10px] text-gray-500 uppercase font-semibold">ทรัพยากร</span>
                        </div>

                        <a href="https://developers.line.biz/console/" target="_blank"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-green-500 hover:to-emerald-500 hover:text-white rounded-md transition-all duration-200">
                            <span class="mr-2">🔗</span>
                            <span class="flex-1">LINE Developers</span>
                            <svg class="w-3 h-3 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                        </a>

                        <a href="https://developers.line.biz/en/docs/messaging-api/flex-message-elements/" target="_blank"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-green-500 hover:to-emerald-500 hover:text-white rounded-md transition-all duration-200">
                            <span class="mr-2">📖</span>
                            <span class="flex-1">Flex Message Docs</span>
                            <svg class="w-3 h-3 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                        </a>
                    </div>
                </div>

                <!-- Divider -->
                <div class="border-t border-gray-700/50 my-3"></div>

                <!-- HRM System Dropdown -->
                <div class="relative mb-1">
                    @php
                        $hrmActive = request()->routeIs('admin.hrm.*');
                    @endphp

                    <!-- Main HRM Menu Button -->
                    <button
                       class="flex items-center w-full px-3 py-2.5 text-gray-300 hover:bg-gradient-to-r hover:from-blue-600 hover:to-cyan-600 hover:text-white rounded-lg transition-all duration-200 group {{ $hrmActive ? 'bg-gradient-to-r from-blue-600 to-cyan-600 text-white shadow-lg' : '' }}"
                       @click="toggleMenu('hrmMenuOpen')">
                        <span class="text-xl transition-all" :class="{ 'md:mx-auto': sidebarCollapsed }">👥</span>
                        <span class="ml-3 text-sm font-medium transition-all flex-1 text-left" :class="{ 'md:hidden': sidebarCollapsed }" x-show="!sidebarCollapsed || sidebarOpen">
                            ระบบ HRM
                        </span>
                        <svg class="w-3.5 h-3.5 ml-2 transition-transform duration-200" :class="{ 'rotate-180': hrmMenuOpen, 'md:hidden': sidebarCollapsed }" x-show="!sidebarCollapsed || sidebarOpen" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <!-- HRM Submenu -->
                    <div x-show="hrmMenuOpen"
                         x-collapse
                         class="pl-6 mt-1 space-y-0.5"
                         :class="{ 'md:hidden': sidebarCollapsed }"
                         style="display: {{ $hrmActive ? 'block' : 'none' }};">

                        <!-- Dashboard -->
                        <a href="{{ route('admin.hrm.dashboard') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-blue-500 hover:to-cyan-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.hrm.dashboard') ? 'bg-gradient-to-r from-blue-500 to-cyan-500 text-white' : '' }}">
                            <span class="mr-2">📊</span>
                            <span>Dashboard</span>
                        </a>

                        <div class="border-t border-gray-700/30 my-1"></div>

                        <!-- Employee Management Section -->
                        <div class="px-2 py-1">
                            <span class="text-[10px] text-gray-500 uppercase font-semibold">จัดการพนักงาน</span>
                        </div>

                        <a href="{{ route('admin.hrm.employees.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-blue-500 hover:to-cyan-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.hrm.employees.index') ? 'bg-gradient-to-r from-blue-500 to-cyan-500 text-white' : '' }}">
                            <span class="mr-2">👤</span>
                            <span>รายชื่อพนักงาน</span>
                        </a>

                        <a href="{{ route('admin.hrm.employees.create') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-blue-500 hover:to-cyan-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.hrm.employees.create') ? 'bg-gradient-to-r from-blue-500 to-cyan-500 text-white' : '' }}">
                            <span class="mr-2">➕</span>
                            <span>เพิ่มพนักงานใหม่</span>
                        </a>

                        <a href="{{ route('admin.hrm.departments.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-blue-500 hover:to-cyan-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.hrm.departments.*') ? 'bg-gradient-to-r from-blue-500 to-cyan-500 text-white' : '' }}">
                            <span class="mr-2">🏢</span>
                            <span>แผนก</span>
                        </a>

                        <a href="{{ route('admin.hrm.positions.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-blue-500 hover:to-cyan-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.hrm.positions.*') ? 'bg-gradient-to-r from-blue-500 to-cyan-500 text-white' : '' }}">
                            <span class="mr-2">💼</span>
                            <span>ตำแหน่งงาน</span>
                        </a>

                        <div class="border-t border-gray-700/30 my-1"></div>

                        <!-- Attendance Section -->
                        <div class="px-2 py-1">
                            <span class="text-[10px] text-gray-500 uppercase font-semibold">เวลาเข้างาน</span>
                        </div>

                        <a href="{{ route('admin.hrm.attendance.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-blue-500 hover:to-cyan-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.hrm.attendance.index') ? 'bg-gradient-to-r from-blue-500 to-cyan-500 text-white' : '' }}">
                            <span class="mr-2">🕐</span>
                            <span>บันทึกเวลา</span>
                        </a>

                        <div class="border-t border-gray-700/30 my-1"></div>

                        <!-- Leave Management Section -->
                        <div class="px-2 py-1">
                            <span class="text-[10px] text-gray-500 uppercase font-semibold">จัดการลา</span>
                        </div>

                        <a href="{{ route('admin.hrm.leave.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-blue-500 hover:to-cyan-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.hrm.leave.index') ? 'bg-gradient-to-r from-blue-500 to-cyan-500 text-white' : '' }}">
                            <span class="mr-2">📅</span>
                            <span>คำขอลา</span>
                        </a>

                        <a href="{{ route('admin.hrm.leave.calendar') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-blue-500 hover:to-cyan-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.hrm.leave.calendar') ? 'bg-gradient-to-r from-blue-500 to-cyan-500 text-white' : '' }}">
                            <span class="mr-2">🗓️</span>
                            <span>ปฏิทินการลา</span>
                        </a>

                        <a href="{{ route('admin.hrm.leave.types') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-blue-500 hover:to-cyan-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.hrm.leave.types*') ? 'bg-gradient-to-r from-blue-500 to-cyan-500 text-white' : '' }}">
                            <span class="mr-2">📋</span>
                            <span>ประเภทการลา</span>
                        </a>

                        <div class="border-t border-gray-700/30 my-1"></div>

                        <!-- Payroll Section -->
                        <div class="px-2 py-1">
                            <span class="text-[10px] text-gray-500 uppercase font-semibold">เงินเดือน</span>
                        </div>

                        <a href="{{ route('admin.hrm.payroll.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-blue-500 hover:to-cyan-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.hrm.payroll.index') ? 'bg-gradient-to-r from-blue-500 to-cyan-500 text-white' : '' }}">
                            <span class="mr-2">💰</span>
                            <span>เงินเดือนพนักงาน</span>
                        </a>

                        <div class="border-t border-gray-700/30 my-1"></div>

                        <!-- Performance Section -->
                        <div class="px-2 py-1">
                            <span class="text-[10px] text-gray-500 uppercase font-semibold">ประเมินผล</span>
                        </div>

                        <a href="{{ route('admin.hrm.performance.reviews.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-blue-500 hover:to-cyan-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.hrm.performance.reviews.*') ? 'bg-gradient-to-r from-blue-500 to-cyan-500 text-white' : '' }}">
                            <span class="mr-2">⭐</span>
                            <span>ประเมินผลงาน</span>
                        </a>

                        <a href="{{ route('admin.hrm.performance.templates.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-blue-500 hover:to-cyan-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.hrm.performance.templates.*') ? 'bg-gradient-to-r from-blue-500 to-cyan-500 text-white' : '' }}">
                            <span class="mr-2">📄</span>
                            <span>แบบฟอร์มประเมิน</span>
                        </a>

                        <div class="border-t border-gray-700/30 my-1"></div>

                        <!-- Recruitment Section -->
                        <div class="px-2 py-1">
                            <span class="text-[10px] text-gray-500 uppercase font-semibold">สรรหา</span>
                        </div>

                        <a href="{{ route('admin.hrm.recruitment.jobs.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-blue-500 hover:to-cyan-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.hrm.recruitment.jobs.*') ? 'bg-gradient-to-r from-blue-500 to-cyan-500 text-white' : '' }}">
                            <span class="mr-2">📢</span>
                            <span>ตำแหน่งว่าง</span>
                        </a>

                        <a href="{{ route('admin.hrm.recruitment.applications.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-blue-500 hover:to-cyan-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.hrm.recruitment.applications.*') ? 'bg-gradient-to-r from-blue-500 to-cyan-500 text-white' : '' }}">
                            <span class="mr-2">📨</span>
                            <span>ใบสมัครงาน</span>
                        </a>

                        <div class="border-t border-gray-700/30 my-1"></div>

                        <!-- Training Section -->
                        <div class="px-2 py-1">
                            <span class="text-[10px] text-gray-500 uppercase font-semibold">พัฒนาบุคลากร</span>
                        </div>

                        <a href="{{ route('admin.hrm.training.courses.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-blue-500 hover:to-cyan-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.hrm.training.courses.*') ? 'bg-gradient-to-r from-blue-500 to-cyan-500 text-white' : '' }}">
                            <span class="mr-2">🎓</span>
                            <span>หลักสูตรฝึกอบรม</span>
                        </a>

                        <a href="{{ route('admin.hrm.training.schedules') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-blue-500 hover:to-cyan-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.hrm.training.schedules') || request()->routeIs('admin.hrm.training.enrollments.*') ? 'bg-gradient-to-r from-blue-500 to-cyan-500 text-white' : '' }}">
                            <span class="mr-2">📆</span>
                            <span>กำหนดการอบรม</span>
                        </a>
                    </div>
                </div>

                <!-- Accounting System Dropdown -->
                @if(config('license.addons.accounting.enabled') && (auth()->user()->hasPermission('accounting.view_dashboard') || auth()->user()->isSuperAdmin()))
                <div class="relative mb-1">
                    @php
                        $accountingActive = request()->routeIs('admin.accounting.*');
                    @endphp

                    <!-- Main Accounting Menu Button -->
                    <button
                       class="flex items-center w-full px-3 py-2.5 text-gray-300 hover:bg-gradient-to-r hover:from-green-600 hover:to-emerald-600 hover:text-white rounded-lg transition-all duration-200 group {{ $accountingActive ? 'bg-gradient-to-r from-green-600 to-emerald-600 text-white shadow-lg' : '' }}"
                       @click="toggleMenu('accountingMenuOpen')">
                        <span class="text-xl transition-all" :class="{ 'md:mx-auto': sidebarCollapsed }">📊</span>
                        <span class="ml-3 text-sm font-medium transition-all flex-1 text-left" :class="{ 'md:hidden': sidebarCollapsed }" x-show="!sidebarCollapsed || sidebarOpen">
                            ระบบบัญชี
                        </span>
                        <svg class="w-3.5 h-3.5 ml-2 transition-transform duration-200" :class="{ 'rotate-180': accountingMenuOpen, 'md:hidden': sidebarCollapsed }" x-show="!sidebarCollapsed || sidebarOpen" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <!-- Accounting Submenu -->
                    <div x-show="accountingMenuOpen"
                         x-collapse
                         class="pl-6 mt-1 space-y-0.5"
                         :class="{ 'md:hidden': sidebarCollapsed }"
                         style="display: {{ $accountingActive ? 'block' : 'none' }};">

                        <!-- Dashboard -->
                        <a href="{{ route('admin.accounting.dashboard') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-green-500 hover:to-emerald-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.accounting.dashboard') ? 'bg-gradient-to-r from-green-500 to-emerald-500 text-white' : '' }}">
                            <i class="fas fa-chart-line text-xs mr-2 w-4"></i>
                            <span>Dashboard</span>
                        </a>

                        <div class="border-t border-gray-700/30 my-1"></div>

                        <!-- Invoices -->
                        @if(auth()->user()->hasPermission('accounting.view_invoices') || auth()->user()->isSuperAdmin())
                        <a href="{{ route('admin.accounting.invoices.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-green-500 hover:to-emerald-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.accounting.invoices.*') ? 'bg-gradient-to-r from-green-500 to-emerald-500 text-white' : '' }}">
                            <i class="fas fa-file-invoice text-xs mr-2 w-4"></i>
                            <span>ใบแจ้งหนี้</span>
                        </a>
                        @endif

                        <!-- Expenses -->
                        @if(auth()->user()->hasPermission('accounting.view_expenses') || auth()->user()->isSuperAdmin())
                        <a href="{{ route('admin.accounting.expenses.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-green-500 hover:to-emerald-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.accounting.expenses.*') ? 'bg-gradient-to-r from-green-500 to-emerald-500 text-white' : '' }}">
                            <i class="fas fa-receipt text-xs mr-2 w-4"></i>
                            <span>รายจ่าย</span>
                        </a>
                        @endif

                        <div class="border-t border-gray-700/30 my-1"></div>

                        <!-- Contacts -->
                        @if(auth()->user()->hasPermission('accounting.view_contacts') || auth()->user()->isSuperAdmin())
                        <a href="{{ route('admin.accounting.contacts.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-green-500 hover:to-emerald-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.accounting.contacts.*') ? 'bg-gradient-to-r from-green-500 to-emerald-500 text-white' : '' }}">
                            <i class="fas fa-address-book text-xs mr-2 w-4"></i>
                            <span>ลูกค้า/ผู้จำหน่าย</span>
                        </a>
                        @endif

                        <!-- Products -->
                        @if(auth()->user()->hasPermission('accounting.view_products') || auth()->user()->isSuperAdmin())
                        <a href="{{ route('admin.accounting.products.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-green-500 hover:to-emerald-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.accounting.products.*') ? 'bg-gradient-to-r from-green-500 to-emerald-500 text-white' : '' }}">
                            <i class="fas fa-box text-xs mr-2 w-4"></i>
                            <span>สินค้า/บริการ</span>
                        </a>
                        @endif

                        <div class="border-t border-gray-700/30 my-1"></div>

                        <!-- Reports -->
                        @if(auth()->user()->hasPermission('accounting.view_reports') || auth()->user()->isSuperAdmin())
                        <a href="{{ route('admin.accounting.reports.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-green-500 hover:to-emerald-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.accounting.reports.*') ? 'bg-gradient-to-r from-green-500 to-emerald-500 text-white' : '' }}">
                            <i class="fas fa-chart-bar text-xs mr-2 w-4"></i>
                            <span>รายงาน</span>
                        </a>
                        @endif

                        <!-- FlowAccount Integration -->
                        @if(auth()->user()->hasPermission('accounting.manage_flowaccount') || auth()->user()->isSuperAdmin())
                        <a href="{{ route('admin.accounting.flowaccount.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-green-500 hover:to-emerald-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.accounting.flowaccount.*') ? 'bg-gradient-to-r from-green-500 to-emerald-500 text-white' : '' }}">
                            <i class="fas fa-plug text-xs mr-2 w-4"></i>
                            <span>เชื่อมต่อ FlowAccount</span>
                        </a>
                        @endif
                    </div>
                </div>
                @endif

                <!-- E-Commerce Management Dropdown -->
                <div class="relative mb-1">
                    @php
                        $ecommerceActive = request()->routeIs('admin.ecommerce.*');
                    @endphp

                    <!-- Main E-Commerce Menu Button -->
                    <button
                       class="flex items-center w-full px-3 py-2.5 text-gray-300 hover:bg-gradient-to-r hover:from-orange-600 hover:to-yellow-600 hover:text-white rounded-lg transition-all duration-200 group {{ $ecommerceActive ? 'bg-gradient-to-r from-orange-600 to-yellow-600 text-white shadow-lg' : '' }}"
                       @click="toggleMenu('ecommerceMenuOpen')">
                        <span class="text-xl transition-all" :class="{ 'md:mx-auto': sidebarCollapsed }">🛒</span>
                        <span class="ml-3 text-sm font-medium transition-all flex-1 text-left" :class="{ 'md:hidden': sidebarCollapsed }" x-show="!sidebarCollapsed || sidebarOpen">
                            E-Commerce
                        </span>
                        <svg class="w-3.5 h-3.5 ml-2 transition-transform duration-200" :class="{ 'rotate-180': ecommerceMenuOpen, 'md:hidden': sidebarCollapsed }" x-show="!sidebarCollapsed || sidebarOpen" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <!-- Dropdown Submenu -->
                    <div x-show="ecommerceMenuOpen && (!sidebarCollapsed || sidebarOpen)"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 transform -translate-y-2"
                         x-transition:enter-end="opacity-100 transform translate-y-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         class="mt-1 mb-2 ml-3 space-y-0.5 bg-gray-800/30 rounded-lg p-1.5 backdrop-blur-sm border border-gray-700/30"
                         style="display: none;">

                        <a href="{{ route('admin.ecommerce.dashboard') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-orange-500 hover:to-yellow-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.ecommerce.dashboard') ? 'bg-gradient-to-r from-orange-500 to-yellow-500 text-white' : '' }}">
                            <span class="mr-2">📊</span>
                            <span>Dashboard</span>
                        </a>

                        <div class="border-t border-gray-700/30 my-1"></div>

                        <a href="{{ route('admin.ecommerce.products.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-orange-500 hover:to-yellow-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.ecommerce.products.*') ? 'bg-gradient-to-r from-orange-500 to-yellow-500 text-white' : '' }}">
                            <span class="mr-2">📦</span>
                            <span>จัดการสินค้า</span>
                        </a>

                        <a href="{{ route('admin.ecommerce.orders.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-orange-500 hover:to-yellow-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.ecommerce.orders.*') ? 'bg-gradient-to-r from-orange-500 to-yellow-500 text-white' : '' }}">
                            <span class="mr-2">📋</span>
                            <span>จัดการคำสั่งซื้อ</span>
                        </a>

                        <a href="{{ route('admin.ecommerce.categories.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-orange-500 hover:to-yellow-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.ecommerce.categories.*') ? 'bg-gradient-to-r from-orange-500 to-yellow-500 text-white' : '' }}">
                            <span class="mr-2">🏷️</span>
                            <span>หมวดหมู่สินค้า</span>
                        </a>

                        <a href="{{ route('admin.ecommerce.reviews.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-orange-500 hover:to-yellow-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.ecommerce.reviews.*') ? 'bg-gradient-to-r from-orange-500 to-yellow-500 text-white' : '' }}">
                            <span class="mr-2">⭐</span>
                            <span>รีวิวสินค้า</span>
                        </a>

                        <div class="border-t border-gray-700/30 my-1"></div>

                        <a href="{{ route('admin.ecommerce.reports') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-orange-500 hover:to-yellow-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.ecommerce.reports') ? 'bg-gradient-to-r from-orange-500 to-yellow-500 text-white' : '' }}">
                            <span class="mr-2">📈</span>
                            <span>รายงานยอดขาย</span>
                        </a>
                    </div>
                </div>

                <!-- MLM System Management Dropdown -->
                <div class="relative mb-1">
                    @php
                        $mlmActive = request()->routeIs('admin.mlm.*');
                    @endphp

                    <!-- Main MLM Menu Button -->
                    <button
                       class="flex items-center w-full px-3 py-2.5 text-gray-300 hover:bg-gradient-to-r hover:from-purple-600 hover:to-pink-600 hover:text-white rounded-lg transition-all duration-200 group {{ $mlmActive ? 'bg-gradient-to-r from-purple-600 to-pink-600 text-white shadow-lg' : '' }}"
                       @click="toggleMenu('mlmMenuOpen')">
                        <span class="text-xl transition-all" :class="{ 'md:mx-auto': sidebarCollapsed }">🏆</span>
                        <span class="ml-3 text-sm font-medium transition-all flex-1 text-left" :class="{ 'md:hidden': sidebarCollapsed }" x-show="!sidebarCollapsed || sidebarOpen">
                            ระบบ MLM
                        </span>
                        <svg class="w-3.5 h-3.5 ml-2 transition-transform duration-200" :class="{ 'rotate-180': mlmMenuOpen, 'md:hidden': sidebarCollapsed }" x-show="!sidebarCollapsed || sidebarOpen" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <!-- Dropdown Submenu -->
                    <div x-show="mlmMenuOpen && (!sidebarCollapsed || sidebarOpen)"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 transform -translate-y-2"
                         x-transition:enter-end="opacity-100 transform translate-y-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         class="mt-1 mb-2 ml-3 space-y-0.5 bg-gray-800/30 rounded-lg p-1.5 backdrop-blur-sm border border-gray-700/30"
                         style="display: none;">

                        <a href="{{ route('admin.mlm.plans.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-purple-500 hover:to-pink-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.mlm.plans.*') ? 'bg-gradient-to-r from-purple-500 to-pink-500 text-white' : '' }}">
                            <span class="mr-2">📋</span>
                            <span>แผน MLM</span>
                        </a>

                        <a href="{{ route('admin.mlm.members.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-purple-500 hover:to-pink-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.mlm.members.*') ? 'bg-gradient-to-r from-purple-500 to-pink-500 text-white' : '' }}">
                            <span class="mr-2">👥</span>
                            <span>สมาชิก MLM</span>
                        </a>

                        <a href="{{ route('admin.mlm.commissions.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-purple-500 hover:to-pink-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.mlm.commissions.*') ? 'bg-gradient-to-r from-purple-500 to-pink-500 text-white' : '' }}">
                            <span class="mr-2">💰</span>
                            <span>คอมมิชชั่น MLM</span>
                        </a>

                        <a href="{{ route('admin.mlm.product-pv.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-purple-500 hover:to-pink-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.mlm.product-pv.*') ? 'bg-gradient-to-r from-purple-500 to-pink-500 text-white' : '' }}">
                            <span class="mr-2">🎯</span>
                            <span>กำหนด PV สินค้า</span>
                        </a>

                        <div class="border-t border-gray-700/30 my-1"></div>

                        <a href="{{ route('admin.mlm.reports.dashboard') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-purple-500 hover:to-pink-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.mlm.reports.*') ? 'bg-gradient-to-r from-purple-500 to-pink-500 text-white' : '' }}">
                            <span class="mr-2">📊</span>
                            <span>รายงาน & สถิติ</span>
                        </a>

                        <a href="{{ route('admin.mlm.settings.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-purple-500 hover:to-pink-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.mlm.settings.*') ? 'bg-gradient-to-r from-purple-500 to-pink-500 text-white' : '' }}">
                            <span class="mr-2">⚙️</span>
                            <span>ตั้งค่า MLM</span>
                        </a>
                    </div>
                </div>

                <!-- POS Management Dropdown -->
                @if(auth()->user()->isSuperAdmin() || auth()->user()->hasRole('admin'))
                <div class="relative mb-1">
                    @php
                        $posActive = request()->routeIs('admin.pos.*') || request()->routeIs('pos.*');
                    @endphp

                    <!-- Main POS Menu Button -->
                    <button
                       class="flex items-center w-full px-3 py-2.5 text-gray-300 hover:bg-gradient-to-r hover:from-blue-600 hover:to-cyan-600 hover:text-white rounded-lg transition-all duration-200 group {{ $posActive ? 'bg-gradient-to-r from-blue-600 to-cyan-600 text-white shadow-lg' : '' }}"
                       @click="toggleMenu('posMenuOpen')">
                        <span class="text-xl transition-all" :class="{ 'md:mx-auto': sidebarCollapsed }">🏪</span>
                        <span class="ml-3 text-sm font-medium transition-all flex-1 text-left" :class="{ 'md:hidden': sidebarCollapsed }" x-show="!sidebarCollapsed || sidebarOpen">
                            ระบบ POS
                        </span>
                        <svg class="w-3.5 h-3.5 ml-2 transition-transform duration-200" :class="{ 'rotate-180': posMenuOpen, 'md:hidden': sidebarCollapsed }" x-show="!sidebarCollapsed || sidebarOpen" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <!-- Dropdown Submenu -->
                    <div x-show="posMenuOpen && (!sidebarCollapsed || sidebarOpen)"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 transform -translate-y-2"
                         x-transition:enter-end="opacity-100 transform translate-y-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         class="mt-1 mb-2 ml-3 space-y-0.5 bg-gray-800/30 rounded-lg p-1.5 backdrop-blur-sm border border-gray-700/30"
                         style="display: none;">

                        <a href="{{ route('admin.pos.dashboard') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-blue-500 hover:to-cyan-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.pos.dashboard') ? 'bg-gradient-to-r from-blue-500 to-cyan-500 text-white' : '' }}">
                            <span class="mr-2">📊</span>
                            <span>Dashboard POS</span>
                        </a>

                        <div class="border-t border-gray-700/30 my-1"></div>

                        <a href="{{ route('admin.pos.devices.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-blue-500 hover:to-cyan-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.pos.devices.*') ? 'bg-gradient-to-r from-blue-500 to-cyan-500 text-white' : '' }}">
                            <span class="mr-2">📱</span>
                            <span>จัดการอุปกรณ์</span>
                        </a>

                        <a href="{{ route('admin.pos.transactions.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-blue-500 hover:to-cyan-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.pos.transactions.*') ? 'bg-gradient-to-r from-blue-500 to-cyan-500 text-white' : '' }}">
                            <span class="mr-2">💰</span>
                            <span>รายการขาย</span>
                        </a>

                        <a href="{{ route('admin.pos.analytics') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-blue-500 hover:to-cyan-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.pos.analytics') ? 'bg-gradient-to-r from-blue-500 to-cyan-500 text-white' : '' }}">
                            <span class="mr-2">📈</span>
                            <span>รายงาน & สถิติ</span>
                        </a>
                    </div>
                </div>
                @endif

                <!-- System Management Dropdown -->
                <div class="relative mb-1">
                    @php
                        $systemActive = request()->routeIs('admin.windows-ui.*') ||
                                       request()->routeIs('admin.premium-page.*') ||
                                       request()->routeIs('admin.header-editor.*') ||
                                       request()->routeIs('admin.templates.*') ||
                                       request()->routeIs('admin.pages.*') ||
                                       request()->routeIs('admin.seo.*') ||
                                       request()->routeIs('admin.themes.*') ||
                                       request()->routeIs('admin.icons.*') ||
                                       request()->routeIs('admin.settings.languages*') ||
                                       request()->routeIs('admin.translations.*') ||
                                       request()->routeIs('admin.notifications.*') ||
                                       request()->routeIs('admin.notification-templates.*') ||
                                       request()->routeIs('admin.settings.index') ||
                                       request()->routeIs('admin.webp.*') ||
                                       request()->routeIs('admin.roles.*');
                    @endphp

                    <!-- Main System Menu Button -->
                    <button
                       class="flex items-center w-full px-3 py-2.5 text-gray-300 hover:bg-gradient-to-r hover:from-emerald-600 hover:to-teal-600 hover:text-white rounded-lg transition-all duration-200 group {{ $systemActive ? 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white shadow-lg' : '' }}"
                       @click="toggleMenu('systemMenuOpen')">
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

                        <a href="{{ route('admin.windows-ui.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-blue-500 hover:to-cyan-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.windows-ui.*') ? 'bg-gradient-to-r from-blue-500 to-cyan-500 text-white shadow-lg' : '' }}">
                            <span class="mr-2">🖥️</span>
                            <span>Windows UI Theme</span>
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

                        <a href="{{ route('admin.themes.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-emerald-500 hover:to-teal-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.themes.*') ? 'bg-gradient-to-r from-emerald-500 to-teal-500 text-white' : '' }}">
                            <span class="mr-2">🎨</span>
                            <span>จัดการธีม (Theme)</span>
                        </a>

                        <a href="{{ route('admin.icons.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-emerald-500 hover:to-teal-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.icons.*') ? 'bg-gradient-to-r from-emerald-500 to-teal-500 text-white' : '' }}">
                            <span class="mr-2">🖼️</span>
                            <span>จัดการไอคอน (Icons)</span>
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

                        <a href="{{ route('admin.roles.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-emerald-500 hover:to-teal-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.roles.*') ? 'bg-gradient-to-r from-emerald-500 to-teal-500 text-white' : '' }}">
                            <span class="mr-2">🔐</span>
                            <span>จัดการบทบาทและสิทธิ์</span>
                        </a>

                        <div class="border-t border-gray-700/30 my-1"></div>

                        <a href="{{ route('admin.webp.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-emerald-500 hover:to-teal-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.webp.*') ? 'bg-gradient-to-r from-emerald-500 to-teal-500 text-white' : '' }}">
                            <span class="mr-2">🖼️</span>
                            <span>จัดการรูปภาพ WebP</span>
                        </a>

                        <a href="{{ route('admin.settings.index') }}"
                           class="flex items-center px-3 py-1.5 text-xs text-gray-300 hover:bg-gradient-to-r hover:from-emerald-500 hover:to-teal-500 hover:text-white rounded-md transition-all duration-200 {{ request()->routeIs('admin.settings.index') ? 'bg-gradient-to-r from-emerald-500 to-teal-500 text-white' : '' }}">
                            <span class="mr-2">⚙️</span>
                            <span>ตั้งค่าทั่วไป</span>
                        </a>
                    </div>
                </div>

                <!-- Version Info -->
                <div class="mt-4 px-3" :class="{ 'md:hidden': sidebarCollapsed }" x-show="!sidebarCollapsed || sidebarOpen">
                    <div class="bg-gray-800/50 dark:bg-gray-900/50 backdrop-blur-sm rounded-lg p-3 border border-gray-700/30 dark:border-gray-600/30">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs text-gray-400 dark:text-gray-300">เวอร์ชั่น</span>
                            <span class="text-xs font-semibold text-white bg-gradient-to-r from-indigo-500 to-purple-500 px-2 py-0.5 rounded-full">
                                {{ config('version.current') }}
                            </span>
                        </div>
                        <div class="text-[10px] text-gray-600 dark:text-gray-400 space-y-1">
                            <div class="flex items-center justify-between">
                                <span>App</span>
                                <span class="text-gray-400 dark:text-gray-300">{{ config('version.current') }} {{ config('version.name') }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span>Laravel</span>
                                <span class="text-gray-400 dark:text-gray-300">{{ app()->version() }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span>PHP</span>
                                <span class="text-gray-400 dark:text-gray-300">{{ PHP_VERSION }}</span>
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
    </script>

    {{-- Google Translate Widget (Like WordPress Plugins) --}}

    @stack('scripts')
</body>
</html>
