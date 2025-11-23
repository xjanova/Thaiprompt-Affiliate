<!DOCTYPE html>
<html lang="th" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') - Admin - {{ config('app.name') }}</title>

    {{-- Favicon (ใช้จาก Theme Setting) --}}
    @php
        $themeSetting = \App\Models\ThemeSetting::active();
        $faviconPath = $themeSetting && $themeSetting->favicon_path
            ? asset('storage/' . $themeSetting->favicon_path)
            : asset('favicon.ico');
    @endphp
    <link rel="icon" type="image/x-icon" href="{{ $faviconPath }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ $faviconPath }}">

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
<body class="h-full font-sans overflow-hidden flex"
      x-data="{ profileOpen: false }"
      x-init="
          // เริ่มต้น theme store และ sidebar store
          $store.theme.init();
          $store.sidebar.init();
      ">

    {{-- Background Gradient พื้นหลังแบบ Dashboard4 - ใช้ Arrow X Theme Variables --}}
    {{-- Light mode: Colorful gradient จาก theme | Dark mode: Dark gradient --}}
    <div class="fixed inset-0 -z-10 transition-all duration-500"
         :style="$store.theme.isDark
             ? 'background: linear-gradient(to bottom right, #111827, #1f2937, #111827)'
             : 'background: var(--arrow-x-primary-gradient, linear-gradient(to right, #9333EA, #EC4899, #F97316))'">
    </div>

    {{-- Animated Background Circles วงกลมเคลื่อนไหวพื้นหลัง (ควบคุมจาก Theme Settings) --}}
    <div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none"
         :style="'display: ' + (window.getComputedStyle(document.documentElement).getPropertyValue('--bg-effects-enabled').trim() === '1' ? 'block' : 'none')">
        {{-- Circle 1 --}}
        <div class="absolute top-1/4 left-1/4 rounded-full animate-pulse transition-all duration-500"
             :style="'width: var(--bg-circle-size); height: var(--bg-circle-size); filter: blur(var(--bg-circle-blur)); animation-duration: var(--bg-animation-duration); ' +
                 ($store.theme.isDark
                     ? 'background: linear-gradient(to bottom right, var(--bg-circle1-color1), var(--bg-circle1-color2)); opacity: ' + Math.min(parseFloat(window.getComputedStyle(document.documentElement).getPropertyValue('--bg-circle-opacity')) * 1.5, 0.3)
                     : 'background: linear-gradient(to bottom right, var(--bg-circle1-color1), var(--bg-circle1-color2)); opacity: var(--bg-circle-opacity)')">
        </div>

        {{-- Circle 2 --}}
        <div class="absolute bottom-1/4 right-1/4 rounded-full animate-pulse transition-all duration-500"
             style="animation-delay: 1s;"
             :style="'width: var(--bg-circle-size); height: var(--bg-circle-size); filter: blur(var(--bg-circle-blur)); animation-duration: var(--bg-animation-duration); ' +
                 ($store.theme.isDark
                     ? 'background: linear-gradient(to bottom right, var(--bg-circle2-color1), var(--bg-circle2-color2)); opacity: ' + Math.min(parseFloat(window.getComputedStyle(document.documentElement).getPropertyValue('--bg-circle-opacity')) * 1.5, 0.3)
                     : 'background: linear-gradient(to bottom right, var(--bg-circle2-color1), var(--bg-circle2-color2)); opacity: var(--bg-circle-opacity)')">
        </div>

        {{-- Circle 3 --}}
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 rounded-full animate-pulse transition-all duration-500"
             style="animation-delay: 2s;"
             :style="'width: var(--bg-circle-size); height: var(--bg-circle-size); filter: blur(var(--bg-circle-blur)); animation-duration: var(--bg-animation-duration); ' +
                 ($store.theme.isDark
                     ? 'background: linear-gradient(to bottom right, var(--bg-circle3-color1), var(--bg-circle3-color2)); opacity: ' + Math.min(parseFloat(window.getComputedStyle(document.documentElement).getPropertyValue('--bg-circle-opacity')) * 1.5, 0.3)
                     : 'background: linear-gradient(to bottom right, var(--bg-circle3-color1), var(--bg-circle3-color2)); opacity: var(--bg-circle-opacity)')">
        </div>
    </div>

    {{-- Sidebar Component (Fixed overlay บน mobile, Relative บน desktop) --}}
    <x-arrow-x.sidebar-v3 />

    {{-- Main Content Area (Flex-1 เพื่อขยายเต็มพื้นที่) --}}
    <div class="flex flex-col flex-1 h-full overflow-hidden">
        {{-- Top Navbar Component --}}
        <x-arrow-x.navbar-v3 />

        {{-- Page Content --}}
        <main class="flex-1 overflow-y-auto p-4 md:p-6">
            @yield('content')
        </main>
    </div>

    {{-- Theme Customizer --}}
    <x-arrow-x.theme-customizer />

    {{-- LINE Quick Settings Panel --}}
    <x-line.quick-settings-panel />

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
