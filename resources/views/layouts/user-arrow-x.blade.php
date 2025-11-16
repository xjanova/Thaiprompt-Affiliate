<!DOCTYPE html>
<html lang="th" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }" :class="{ 'dark': darkMode }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        $appName = \App\Models\Setting::get('app_name', 'TP-Affiliate');
        $favicon = \App\Models\Setting::get('favicon');
    @endphp

    <title>@yield('title') - {{ $appName }}</title>

    @if($favicon)
        <link rel="icon" type="image/x-icon" href="{{ asset($favicon) }}">
    @endif

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    {{-- Font Awesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

    {{-- Tailwind CSS --}}
    <script src="https://cdn.tailwindcss.com"></script>

    {{-- Alpine.js --}}
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.13.3/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>

    {{-- Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        /**
         * Arrow X User Layout Styles
         *
         * Glass Fusion + Gradient Mesh + Dark Mode
         */

        /* Gradient Mesh Background */
        .gradient-mesh-bg {
            background:
                radial-gradient(ellipse at top left, rgba(139, 92, 246, 0.08) 0%, transparent 50%),
                radial-gradient(ellipse at top right, rgba(236, 72, 153, 0.08) 0%, transparent 50%),
                radial-gradient(ellipse at bottom left, rgba(59, 130, 246, 0.08) 0%, transparent 50%),
                radial-gradient(ellipse at bottom right, rgba(251, 146, 60, 0.08) 0%, transparent 50%),
                #f8fafc;
        }

        .dark .gradient-mesh-bg {
            background:
                radial-gradient(ellipse at top left, rgba(139, 92, 246, 0.05) 0%, transparent 50%),
                radial-gradient(ellipse at top right, rgba(236, 72, 153, 0.05) 0%, transparent 50%),
                radial-gradient(ellipse at bottom left, rgba(59, 130, 246, 0.05) 0%, transparent 50%),
                radial-gradient(ellipse at bottom right, rgba(251, 146, 60, 0.05) 0%, transparent 50%),
                #0f172a;
        }

        /* Glass Fusion Card */
        .glass-fusion-card {
            background: linear-gradient(
                135deg,
                rgba(255, 255, 255, 0.25) 0%,
                rgba(255, 255, 255, 0.15) 100%
            );
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .dark .glass-fusion-card {
            background: linear-gradient(
                135deg,
                rgba(31, 41, 55, 0.8) 0%,
                rgba(17, 24, 39, 0.7) 100%
            );
            border-color: rgba(75, 85, 99, 0.5);
        }

        /* Sidebar Glass Effect */
        .sidebar-glass {
            background: linear-gradient(
                180deg,
                rgba(255, 255, 255, 0.95) 0%,
                rgba(255, 255, 255, 0.9) 100%
            );
            backdrop-filter: blur(30px) saturate(200%);
            -webkit-backdrop-filter: blur(30px) saturate(200%);
            border-right: 1px solid rgba(229, 231, 235, 0.8);
        }

        .dark .sidebar-glass {
            background: linear-gradient(
                180deg,
                rgba(15, 23, 42, 0.95) 0%,
                rgba(15, 23, 42, 0.9) 100%
            );
            border-right-color: rgba(51, 65, 85, 0.8);
        }

        /* Custom Scrollbar */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(139, 92, 246, 0.3);
            border-radius: 3px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(139, 92, 246, 0.5);
        }

        .dark .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(139, 92, 246, 0.4);
        }

        /* Menu Item Hover Effect */
        .menu-item {
            position: relative;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .menu-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 0;
            background: linear-gradient(180deg, #8B5CF6, #EC4899);
            border-radius: 0 4px 4px 0;
            transition: height 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .menu-item:hover::before,
        .menu-item.active::before {
            height: 70%;
        }

        .menu-item:hover {
            background: linear-gradient(
                90deg,
                rgba(139, 92, 246, 0.1) 0%,
                transparent 100%
            );
        }

        .dark .menu-item:hover {
            background: linear-gradient(
                90deg,
                rgba(139, 92, 246, 0.15) 0%,
                transparent 100%
            );
        }

        .menu-item.active {
            background: linear-gradient(
                90deg,
                rgba(139, 92, 246, 0.15) 0%,
                transparent 100%
            );
        }

        .dark .menu-item.active {
            background: linear-gradient(
                90deg,
                rgba(139, 92, 246, 0.2) 0%,
                transparent 100%
            );
        }
    </style>

    @stack('styles')
</head>
<body class="gradient-mesh-bg min-h-screen" x-data="userLayout()" x-init="init()">
    <div class="flex h-screen overflow-hidden">
        {{-- Sidebar --}}
        <aside class="sidebar-glass w-64 flex-shrink-0 hidden lg:flex lg:flex-col fixed lg:static inset-y-0 left-0 z-50 transform transition-transform duration-300"
               :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">

            {{-- Logo Section --}}
            <div class="flex items-center justify-between px-6 py-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center space-x-3">
                    @php
                        $logo = \App\Models\Setting::get('logo');
                        $logoUrl = $logo ? asset($logo) : asset('images/logo.svg');
                    @endphp
                    <img src="{{ $logoUrl }}" alt="{{ $appName }}" class="h-10 w-auto">
                    <span class="text-xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">
                        {{ $appName }}
                    </span>
                </div>
            </div>

            {{-- User Profile Card --}}
            <div class="px-4 py-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center space-x-3 px-3 py-2 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl">
                    <img src="{{ auth()->user()->profile_picture_url }}"
                         alt="{{ auth()->user()->name }}"
                         class="w-12 h-12 rounded-full ring-2 ring-purple-500 ring-offset-2">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                            {{ auth()->user()->name }}
                        </p>
                        <p class="text-xs text-gray-600 dark:text-gray-400 truncate">
                            {{ auth()->user()->email }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Navigation Menu --}}
            <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto custom-scrollbar">
                {{-- Dashboard --}}
                <a href="{{ route('user.dashboard') }}"
                   class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg {{ request()->routeIs('user.dashboard') ? 'active' : '' }}">
                    <i class="fas fa-home w-5 text-purple-500"></i>
                    <span class="font-medium text-gray-700 dark:text-gray-300">แดชบอร์ด</span>
                </a>

                {{-- Wallet --}}
                <a href="{{ route('user.wallet.index') }}"
                   class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg {{ request()->routeIs('user.wallet.*') ? 'active' : '' }}">
                    <i class="fas fa-wallet w-5 text-green-500"></i>
                    <span class="font-medium text-gray-700 dark:text-gray-300">กระเป๋าเงิน</span>
                    @if(($user->wallet_balance ?? 0) > 0)
                        <span class="ml-auto text-xs font-semibold text-green-600 dark:text-green-400">
                            ฿{{ number_format($user->wallet_balance, 2) }}
                        </span>
                    @endif
                </a>

                {{-- Commissions --}}
                <a href="{{ route('user.commissions.index') }}"
                   class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg {{ request()->routeIs('user.commissions.*') ? 'active' : '' }}">
                    <i class="fas fa-money-bill-wave w-5 text-blue-500"></i>
                    <span class="font-medium text-gray-700 dark:text-gray-300">คอมมิชชั่น</span>
                </a>

                {{-- Affiliates --}}
                <a href="{{ route('user.affiliates.team') }}"
                   class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg {{ request()->routeIs('user.affiliates.*') ? 'active' : '' }}">
                    <i class="fas fa-users w-5 text-orange-500"></i>
                    <span class="font-medium text-gray-700 dark:text-gray-300">ทีมงาน</span>
                </a>

                {{-- Rank --}}
                <a href="{{ route('user.rank.progress') }}"
                   class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg {{ request()->routeIs('user.rank.*') ? 'active' : '' }}">
                    <i class="fas fa-trophy w-5 text-yellow-500"></i>
                    <span class="font-medium text-gray-700 dark:text-gray-300">ระดับ Rank</span>
                </a>

                {{-- Divider --}}
                <div class="my-4 border-t border-gray-200 dark:border-gray-700"></div>

                {{-- Profile --}}
                <a href="{{ route('user.profile.edit') }}"
                   class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg {{ request()->routeIs('user.profile.*') ? 'active' : '' }}">
                    <i class="fas fa-user-edit w-5 text-indigo-500"></i>
                    <span class="font-medium text-gray-700 dark:text-gray-300">โปรไฟล์</span>
                </a>

                {{-- Settings --}}
                <a href="{{ route('user.settings') }}"
                   class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg {{ request()->routeIs('user.settings*') ? 'active' : '' }}">
                    <i class="fas fa-cog w-5 text-gray-500"></i>
                    <span class="font-medium text-gray-700 dark:text-gray-300">การตั้งค่า</span>
                </a>

                {{-- Support --}}
                <a href="{{ route('user.support') }}"
                   class="menu-item flex items-center space-x-3 px-4 py-3 rounded-lg {{ request()->routeIs('user.support*') ? 'active' : '' }}">
                    <i class="fas fa-headset w-5 text-pink-500"></i>
                    <span class="font-medium text-gray-700 dark:text-gray-300">ติดต่อซัพพอร์ต</span>
                </a>
            </nav>

            {{-- Logout Button --}}
            <div class="px-4 py-4 border-t border-gray-200 dark:border-gray-700">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="w-full flex items-center justify-center space-x-2 px-4 py-3 bg-gradient-to-r from-red-500 to-pink-500 hover:from-red-600 hover:to-pink-600 text-white rounded-xl font-semibold transition-all duration-300 transform hover:scale-105 shadow-lg">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>ออกจากระบบ</span>
                    </button>
                </form>
            </div>
        </aside>

        {{-- Main Content --}}
        <div class="flex-1 flex flex-col overflow-hidden">
            {{-- Top Header --}}
            <header class="glass-fusion-card sticky top-0 z-40 shadow-sm">
                <div class="px-4 sm:px-6 lg:px-8 py-4">
                    <div class="flex items-center justify-between">
                        {{-- Mobile Menu Button --}}
                        <button @click="sidebarOpen = !sidebarOpen"
                                class="lg:hidden p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                            <i class="fas fa-bars text-xl text-gray-700 dark:text-gray-300"></i>
                        </button>

                        {{-- Page Title (Mobile) --}}
                        <h2 class="lg:hidden text-lg font-bold text-gray-900 dark:text-white">
                            @yield('title', 'แดชบอร์ด')
                        </h2>

                        {{-- Right Actions --}}
                        <div class="flex items-center space-x-4 ml-auto">
                            {{-- Dark Mode Toggle --}}
                            <button @click="toggleDarkMode()"
                                    class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                <i class="fas" :class="darkMode ? 'fa-sun text-yellow-400' : 'fa-moon text-gray-600'"></i>
                            </button>

                            {{-- Notifications --}}
                            <button class="relative p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                <i class="fas fa-bell text-gray-600 dark:text-gray-300"></i>
                                <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            {{-- Main Content Area --}}
            <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
                @yield('content')
            </main>
        </div>
    </div>

    {{-- Mobile Sidebar Overlay --}}
    <div x-show="sidebarOpen"
         @click="sidebarOpen = false"
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden"></div>

    @stack('scripts')

    <script>
    /**
     * User Layout Alpine.js Component
     */
    function userLayout() {
        return {
            sidebarOpen: false,
            darkMode: localStorage.getItem('darkMode') === 'true',

            init() {
                // Apply saved dark mode
                if (this.darkMode) {
                    document.documentElement.classList.add('dark');
                }
            },

            toggleDarkMode() {
                this.darkMode = !this.darkMode;

                if (this.darkMode) {
                    document.documentElement.classList.add('dark');
                    localStorage.setItem('darkMode', 'true');
                } else {
                    document.documentElement.classList.remove('dark');
                    localStorage.setItem('darkMode', 'false');
                }

                // Dispatch event for other components
                window.dispatchEvent(new CustomEvent('dark-mode-changed', {
                    detail: { isDark: this.darkMode }
                }));
            }
        };
    }
    </script>
</body>
</html>
