<!DOCTYPE html>
<html lang="th" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') - Admin - {{ config('app.name') }}</title>

    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Noto+Sans+Thai:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    {{-- Font Awesome 6.5.1 --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    {{-- Vite Assets --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Arrow X Theme Styles --}}
    <x-arrow-x.theme-styles />

    @stack('styles')
</head>
<body class="h-full font-sans overflow-hidden"
      x-data="{
          sidebarOpen: true,
          profileOpen: false
      }"
      x-init="
          // เริ่มต้น theme store
          $store.theme.init();
      ">

    {{-- Background Gradient พื้นหลังแบบ Dashboard4 - สลับตาม dark mode --}}
    <div class="fixed inset-0 -z-10 transition-colors duration-500"
         :class="$store.theme.isDark
             ? 'bg-gradient-to-br from-purple-500 via-pink-500 to-orange-500'
             : 'bg-gradient-to-br from-gray-100 via-gray-200 to-gray-300'">
    </div>

    {{-- Animated Background Circles วงกลมเคลื่อนไหวพื้นหลัง --}}
    <div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none">
        <div class="absolute top-1/4 left-1/4 w-96 h-96 rounded-full blur-3xl animate-pulse transition-all duration-500"
             :class="$store.theme.isDark
                 ? 'bg-gradient-to-br from-cyan-400 to-blue-600 opacity-30'
                 : 'bg-gradient-to-br from-cyan-300 to-blue-400 opacity-10'">
        </div>
        <div class="absolute bottom-1/4 right-1/4 w-96 h-96 rounded-full blur-3xl animate-pulse transition-all duration-500"
             style="animation-delay: 1s;"
             :class="$store.theme.isDark
                 ? 'bg-gradient-to-br from-pink-400 to-purple-600 opacity-30'
                 : 'bg-gradient-to-br from-pink-300 to-purple-400 opacity-10'">
        </div>
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-96 h-96 rounded-full blur-3xl animate-pulse transition-all duration-500"
             style="animation-delay: 2s;"
             :class="$store.theme.isDark
                 ? 'bg-gradient-to-br from-yellow-400 to-orange-600 opacity-30'
                 : 'bg-gradient-to-br from-yellow-300 to-orange-400 opacity-10'">
        </div>
    </div>

    <div class="flex h-full">
        {{-- Sidebar Component --}}
        <x-arrow-x.sidebar-v3 />

        {{-- Main Content Area --}}
        <div class="flex-1 flex flex-col overflow-hidden">
            {{-- Top Navbar Component --}}
            <x-arrow-x.navbar-v3 />

            {{-- Page Content --}}
            <main class="flex-1 overflow-y-auto p-4 md:p-6">
                @yield('content')
            </main>
        </div>
    </div>

    {{-- Toast Notifications --}}
    <div class="fixed bottom-4 right-4 z-50 space-y-2"
         x-data="{ notifications: [] }"
         @notify.window="notifications.push($event.detail); setTimeout(() => notifications.shift(), 3000)">
        <template x-for="(notification, index) in notifications" :key="index">
            <div x-show="true"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="translate-x-full"
                 class="glass-fusion rounded-xl p-4 shadow-2xl border border-white/30 min-w-[300px]"
                 :class="{
                     'border-l-4 border-l-green-500': notification.type === 'success',
                     'border-l-4 border-l-red-500': notification.type === 'error',
                     'border-l-4 border-l-yellow-500': notification.type === 'warning',
                     'border-l-4 border-l-blue-500': notification.type === 'info'
                 }">
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0">
                        <template x-if="notification.type === 'success'">
                            <i class="fas fa-check-circle text-2xl text-green-400"></i>
                        </template>
                        <template x-if="notification.type === 'error'">
                            <i class="fas fa-exclamation-circle text-2xl text-red-400"></i>
                        </template>
                        <template x-if="notification.type === 'warning'">
                            <i class="fas fa-exclamation-triangle text-2xl text-yellow-400"></i>
                        </template>
                        <template x-if="notification.type === 'info'">
                            <i class="fas fa-info-circle text-2xl text-blue-400"></i>
                        </template>
                    </div>
                    <p class="flex-1 text-white font-medium" x-text="notification.message"></p>
                </div>
            </div>
        </template>
    </div>

    @stack('scripts')
</body>
</html>
