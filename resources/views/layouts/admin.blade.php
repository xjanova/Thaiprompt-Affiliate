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
    </style>

    @stack('styles')
</head>
<body class="font-sans antialiased bg-gray-100">
    <div class="min-h-screen" x-data="{ sidebarOpen: false, sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true', profileDropdown: false }">
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
        <div class="fixed inset-y-0 left-0 z-40 bg-gradient-to-b from-gray-900 via-gray-800 to-gray-900 shadow-2xl transform transition-all duration-300 ease-in-out overflow-y-auto"
             style="box-shadow: 4px 0 20px rgba(0, 0, 0, 0.5);"
             :class="{
                 '-translate-x-full md:translate-x-0': !sidebarOpen,
                 'translate-x-0': sidebarOpen,
                 'w-64': !sidebarCollapsed,
                 'md:w-20': sidebarCollapsed
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
            <nav class="mt-8 px-2">
                <a href="{{ route('admin.dashboard') }}"
                   class="flex items-center px-4 py-3 mb-2 text-gray-300 hover:bg-gradient-to-r hover:from-indigo-600 hover:to-purple-600 hover:text-white rounded-lg transition-all group {{ request()->routeIs('admin.dashboard') ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-md' : '' }}">
                    <span class="text-2xl transition-all" :class="{ 'md:mx-auto': sidebarCollapsed }">📊</span>
                    <span class="ml-3 transition-all" :class="{ 'md:hidden': sidebarCollapsed }" x-show="!sidebarCollapsed || sidebarOpen">แดชบอร์ด</span>
                </a>

                <a href="{{ route('admin.users.index') }}"
                   class="flex items-center px-4 py-3 mb-2 text-gray-300 hover:bg-gradient-to-r hover:from-indigo-600 hover:to-purple-600 hover:text-white rounded-lg transition-all group {{ request()->routeIs('admin.users.*') ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-md' : '' }}">
                    <span class="text-2xl transition-all" :class="{ 'md:mx-auto': sidebarCollapsed }">👥</span>
                    <span class="ml-3 transition-all" :class="{ 'md:hidden': sidebarCollapsed }" x-show="!sidebarCollapsed || sidebarOpen">ผู้ใช้</span>
                </a>

                <a href="{{ route('admin.affiliates.index') }}"
                   class="flex items-center px-4 py-3 mb-2 text-gray-300 hover:bg-gradient-to-r hover:from-indigo-600 hover:to-purple-600 hover:text-white rounded-lg transition-all group {{ request()->routeIs('admin.affiliates.*') ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-md' : '' }}">
                    <span class="text-2xl transition-all" :class="{ 'md:mx-auto': sidebarCollapsed }">🌐</span>
                    <span class="ml-3 transition-all" :class="{ 'md:hidden': sidebarCollapsed }" x-show="!sidebarCollapsed || sidebarOpen">Affiliates</span>
                </a>

                <a href="{{ route('admin.commissions.index') }}"
                   class="flex items-center px-4 py-3 mb-2 text-gray-300 hover:bg-gradient-to-r hover:from-indigo-600 hover:to-purple-600 hover:text-white rounded-lg transition-all group {{ request()->routeIs('admin.commissions.*') ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-md' : '' }}">
                    <span class="text-2xl transition-all" :class="{ 'md:mx-auto': sidebarCollapsed }">💰</span>
                    <span class="ml-3 transition-all" :class="{ 'md:hidden': sidebarCollapsed }" x-show="!sidebarCollapsed || sidebarOpen">คอมมิชชั่น</span>
                </a>

                <div class="border-t border-gray-700 my-4"></div>
                <div class="px-4 py-2 text-xs text-gray-500 uppercase tracking-wider" :class="{ 'md:hidden': sidebarCollapsed }" x-show="!sidebarCollapsed || sidebarOpen">
                    จัดการระบบ
                </div>

                <a href="{{ route('admin.sliders.index') }}"
                   class="flex items-center px-4 py-3 mb-2 text-gray-300 hover:bg-gradient-to-r hover:from-indigo-600 hover:to-purple-600 hover:text-white rounded-lg transition-all group {{ request()->routeIs('admin.sliders.*') ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-md' : '' }}">
                    <span class="text-2xl transition-all" :class="{ 'md:mx-auto': sidebarCollapsed }">🖼️</span>
                    <span class="ml-3 transition-all" :class="{ 'md:hidden': sidebarCollapsed }" x-show="!sidebarCollapsed || sidebarOpen">สไลด์</span>
                </a>

                <a href="{{ route('admin.premium-page.index') }}"
                   class="flex items-center px-4 py-3 mb-2 text-gray-300 hover:bg-gradient-to-r hover:from-indigo-600 hover:to-purple-600 hover:text-white rounded-lg transition-all group {{ request()->routeIs('admin.premium-page.*') ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-md' : '' }}">
                    <span class="text-2xl transition-all" :class="{ 'md:mx-auto': sidebarCollapsed }">🎨</span>
                    <span class="ml-3 transition-all" :class="{ 'md:hidden': sidebarCollapsed }" x-show="!sidebarCollapsed || sidebarOpen">จัดการหน้าแรก</span>
                </a>

                <a href="{{ route('admin.pages.index') }}"
                   class="flex items-center px-4 py-3 mb-2 text-gray-300 hover:bg-gradient-to-r hover:from-indigo-600 hover:to-purple-600 hover:text-white rounded-lg transition-all group {{ request()->routeIs('admin.pages.*') ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-md' : '' }}">
                    <span class="text-2xl transition-all" :class="{ 'md:mx-auto': sidebarCollapsed }">📄</span>
                    <span class="ml-3 transition-all" :class="{ 'md:hidden': sidebarCollapsed }" x-show="!sidebarCollapsed || sidebarOpen">จัดการหน้าเพจ</span>
                </a>

                <a href="{{ route('admin.seo.index') }}"
                   class="flex items-center px-4 py-3 mb-2 text-gray-300 hover:bg-gradient-to-r hover:from-indigo-600 hover:to-purple-600 hover:text-white rounded-lg transition-all group {{ request()->routeIs('admin.seo.*') ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-md' : '' }}">
                    <span class="text-2xl transition-all" :class="{ 'md:mx-auto': sidebarCollapsed }">🔍</span>
                    <span class="ml-3 transition-all" :class="{ 'md:hidden': sidebarCollapsed }" x-show="!sidebarCollapsed || sidebarOpen">จัดการ SEO</span>
                </a>

                <a href="{{ route('admin.settings.languages') }}"
                   class="flex items-center px-4 py-3 mb-2 text-gray-300 hover:bg-gradient-to-r hover:from-indigo-600 hover:to-purple-600 hover:text-white rounded-lg transition-all group {{ request()->routeIs('admin.settings.languages*') ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-md' : '' }}">
                    <span class="text-2xl transition-all" :class="{ 'md:mx-auto': sidebarCollapsed }">🌍</span>
                    <span class="ml-3 transition-all" :class="{ 'md:hidden': sidebarCollapsed }" x-show="!sidebarCollapsed || sidebarOpen">จัดการภาษา</span>
                </a>

                <a href="{{ route('admin.settings.index') }}"
                   class="flex items-center px-4 py-3 mb-2 text-gray-300 hover:bg-gradient-to-r hover:from-indigo-600 hover:to-purple-600 hover:text-white rounded-lg transition-all group {{ request()->routeIs('admin.settings.*') ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-md' : '' }}">
                    <span class="text-2xl transition-all" :class="{ 'md:mx-auto': sidebarCollapsed }">⚙️</span>
                    <span class="ml-3 transition-all" :class="{ 'md:hidden': sidebarCollapsed }" x-show="!sidebarCollapsed || sidebarOpen">ตั้งค่า</span>
                </a>

                <div class="border-t border-gray-700 my-4"></div>

                <a href="{{ route('home') }}"
                   class="flex items-center px-4 py-3 mb-2 text-gray-300 hover:bg-gradient-to-r hover:from-green-600 hover:to-teal-600 hover:text-white rounded-lg transition-all group">
                    <span class="text-2xl transition-all" :class="{ 'md:mx-auto': sidebarCollapsed }">🏠</span>
                    <span class="ml-3 transition-all" :class="{ 'md:hidden': sidebarCollapsed }" x-show="!sidebarCollapsed || sidebarOpen">กลับหน้าแรก</span>
                </a>

                <form method="POST" action="{{ route('logout') }}" class="mt-2">
                    @csrf
                    <button type="submit" class="flex items-center w-full px-4 py-3 text-gray-300 hover:bg-gradient-to-r hover:from-red-600 hover:to-pink-600 hover:text-white rounded-lg transition-all group">
                        <span class="text-2xl transition-all" :class="{ 'md:mx-auto': sidebarCollapsed }">🚪</span>
                        <span class="ml-3 transition-all" :class="{ 'md:hidden': sidebarCollapsed }" x-show="!sidebarCollapsed || sidebarOpen">ออกจากระบบ</span>
                    </button>
                </form>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="transition-all duration-300" :class="{ 'md:ml-64': !sidebarCollapsed, 'md:ml-20': sidebarCollapsed }">
            <!-- Top Bar -->
            <header class="bg-white shadow-sm sticky top-0 z-20">
                <div class="flex items-center justify-between px-6 py-4">
                    <div class="flex items-center">
                        <button @click="sidebarOpen = !sidebarOpen" class="text-gray-500 hover:text-gray-700 focus:outline-none md:hidden mr-4">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>

                        <h1 class="text-2xl font-semibold text-gray-800">@yield('title')</h1>
                    </div>

                    <div class="flex items-center space-x-4">
                        <!-- Language Switcher -->
                        <div class="relative z-[60]">
                            @include('components.language-switcher')
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

    @stack('scripts')
</body>
</html>
