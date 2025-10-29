<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title') - Admin - {{ config('app.name', 'TP-Affiliate') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    @stack('styles')
</head>
<body class="font-sans antialiased bg-gray-100">
    <div class="min-h-screen" x-data="{ sidebarOpen: false }">
        <!-- Sidebar -->
        <div class="fixed inset-y-0 left-0 z-50 w-64 bg-gray-900 transform transition-transform duration-300 ease-in-out md:translate-x-0"
             :class="{ '-translate-x-full': !sidebarOpen, 'translate-x-0': sidebarOpen }">
            <div class="flex items-center justify-center h-16 bg-gray-800">
                <span class="text-white text-2xl font-bold">TP-Admin</span>
            </div>

            <nav class="mt-8">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition {{ request()->routeIs('admin.dashboard') ? 'bg-gray-800 text-white' : '' }}">
                    <span class="text-xl mr-3">📊</span>
                    <span>แดชบอร์ด</span>
                </a>

                <a href="{{ route('admin.users.index') }}" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition {{ request()->routeIs('admin.users.*') ? 'bg-gray-800 text-white' : '' }}">
                    <span class="text-xl mr-3">👥</span>
                    <span>ผู้ใช้</span>
                </a>

                <a href="{{ route('admin.affiliates.index') }}" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition {{ request()->routeIs('admin.affiliates.*') ? 'bg-gray-800 text-white' : '' }}">
                    <span class="text-xl mr-3">🌐</span>
                    <span>Affiliates</span>
                </a>

                <a href="{{ route('admin.commissions.index') }}" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition {{ request()->routeIs('admin.commissions.*') ? 'bg-gray-800 text-white' : '' }}">
                    <span class="text-xl mr-3">💰</span>
                    <span>คอมมิชชั่น</span>
                </a>

                <a href="{{ route('admin.settings.index') }}" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition {{ request()->routeIs('admin.settings.*') ? 'bg-gray-800 text-white' : '' }}">
                    <span class="text-xl mr-3">⚙️</span>
                    <span>ตั้งค่า</span>
                </a>

                <div class="border-t border-gray-800 mt-4 pt-4">
                    <a href="{{ route('home') }}" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition">
                        <span class="text-xl mr-3">🏠</span>
                        <span>กลับหน้าแรก</span>
                    </a>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex items-center w-full px-6 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition">
                            <span class="text-xl mr-3">🚪</span>
                            <span>ออกจากระบบ</span>
                        </button>
                    </form>
                </div>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="md:ml-64">
            <!-- Top Bar -->
            <header class="bg-white shadow-sm">
                <div class="flex items-center justify-between px-6 py-4">
                    <button @click="sidebarOpen = !sidebarOpen" class="text-gray-500 focus:outline-none md:hidden">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    <h1 class="text-2xl font-semibold text-gray-800">@yield('title')</h1>

                    <div class="flex items-center">
                        <span class="text-gray-600 mr-4">{{ Auth::user()->name }}</span>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="p-6">
                @if (session('success'))
                    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                        {{ session('error') }}
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
