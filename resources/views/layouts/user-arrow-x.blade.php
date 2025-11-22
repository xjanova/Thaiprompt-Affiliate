<!DOCTYPE html>
<html lang="th" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">

    <title>@yield('title', 'Dashboard') - {{ config('app.name') }}</title>

    {{-- Favicon (C
I2 Theme Setting) --}}
    @php
        $themeSetting = \App\Models\ThemeSetting::active();
        $faviconPath = $themeSetting && $themeSetting->favicon_path
            ? asset('storage/' . $themeSetting->favicon_path)
            : asset('favicon.ico');
    @endphp
    <link rel="icon" type="image/x-icon" href="{{ $faviconPath }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ $faviconPath }}">
    <link rel="apple-touch-icon" href="{{ $faviconPath }}">

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

    {{-- Alpine.js x-cloak --}}
    <style>
        [x-cloak] {
            display: none !important;
        }

        /* Mobile Bottom Navigation Padding */
        @media (max-width: 1023px) {
            body {
                padding-bottom: calc(64px + env(safe-area-inset-bottom, 0px));
            }
        }
    </style>

    @stack('styles')
</head>
<body class="h-full font-sans overflow-hidden flex"
      x-data="{ profileOpen: false }"
      x-init="
          // @#4H!I theme store A%0 sidebar store
          $store.theme.init();
          $store.sidebar.init();
      ">

    {{-- Background Gradient 7I+%1A Arrow X Theme --}}
    {{-- Light mode: Colorful gradient | Dark mode: Dark gradient --}}
    <div class="fixed inset-0 -z-10 transition-all duration-500"
         :style="$store.theme.isDark
             ? 'background: linear-gradient(to bottom right, #111827, #1f2937, #111827)'
             : 'background: var(--arrow-x-primary-gradient, linear-gradient(to right, #9333EA, #EC4899, #F97316))'">
    </div>

    {{-- Animated Background Circles '%!@%7H-D+'7I+%1 ('8!2 Theme Settings) --}}
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

    {{-- Sidebar Component *3+#1 User (C
I arrow-x-sidebar component) --}}
    <x-arrow-x.sidebar-v3 type="user" />

    {{-- Main Content Area (Flex-1 @7H-"2"@G!7I5H) --}}
    <div class="flex flex-col flex-1 h-full overflow-hidden">
        {{-- Top Navbar Component --}}
        <x-arrow-x.navbar-v3 type="user" />

        {{-- Page Content --}}
        <main class="flex-1 overflow-y-auto p-4 md:p-6 arrow-x-content">
            @yield('content')
        </main>
    </div>

    {{-- Theme Customizer --}}
    <x-arrow-x.theme-customizer />

    {{-- Toast Notifications --}}
    <div class="fixed bottom-24 right-4 z-[90] space-y-2 max-w-md"
         x-data="{ notifications: [] }"
         @notify.window="notifications.push($event.detail); setTimeout(() => notifications.shift(), 5000)">
        <template x-for="(notification, index) in notifications" :key="index">
            <div
                x-show="true"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform translate-x-full"
                x-transition:enter-end="opacity-100 transform translate-x-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 transform translate-x-0"
                x-transition:leave-end="opacity-0 transform translate-x-full"
                class="px-4 py-3 rounded-lg shadow-lg backdrop-blur-lg"
                :class="{
                    'bg-green-500/90 text-white': notification.type === 'success',
                    'bg-red-500/90 text-white': notification.type === 'error',
                    'bg-blue-500/90 text-white': notification.type === 'info',
                    'bg-yellow-500/90 text-white': notification.type === 'warning'
                }">
                <div class="flex items-center space-x-2">
                    <i class="fas"
                       :class="{
                           'fa-check-circle': notification.type === 'success',
                           'fa-exclamation-circle': notification.type === 'error',
                           'fa-info-circle': notification.type === 'info',
                           'fa-exclamation-triangle': notification.type === 'warning'
                       }"></i>
                    <span x-text="notification.message"></span>
                </div>
            </div>
        </template>
    </div>

    {{-- Laravel Session Flash Messages (A%@G notifications) --}}
    @if (session('success'))
        <div x-data x-init="$dispatch('notify', { type: 'success', message: '{{ session('success') }}' })"></div>
    @endif
    @if (session('error'))
        <div x-data x-init="$dispatch('notify', { type: 'error', message: '{{ session('error') }}' })"></div>
    @endif
    @if (session('info'))
        <div x-data x-init="$dispatch('notify', { type: 'info', message: '{{ session('info') }}' })"></div>
    @endif
    @if (session('warning'))
        <div x-data x-init="$dispatch('notify', { type: 'warning', message: '{{ session('warning') }}' })"></div>
    @endif

    {{-- Mobile Bottom Navigation - A*@	20!7-7- --}}
    <x-mobile-bottom-navigation type="user" />

    {{-- Mobile Quick Actions - FAB --}}
    <x-mobile-quick-actions type="user" />

    @stack('scripts')

    <script>
    /**
     * 1 theme change events 2 Alpine Store
     * -1@ Chart.js A%0 components 5H3@G
     */
    window.addEventListener('theme-changed', (event) => {
        console.log('<� Theme changed in User Dashboard:', event.detail.isDark ? 'Dark' : 'Light');

        // -1@ Chart.js I2!5
        if (typeof Chart !== 'undefined') {
            Chart.defaults.color = event.detail.isDark ? '#e2e8f0' : '#1f2937';
            Chart.defaults.borderColor = event.detail.isDark ? '#374151' : '#e5e7eb';
            Chart.defaults.backgroundColor = event.detail.isDark ? '#1f2937' : '#ffffff';
        }

        // Dispatch event *3+#1 custom components
        window.dispatchEvent(new CustomEvent('user-theme-changed', {
            detail: event.detail
        }));
    });

    /**
     * Helper function *3+#1 show notification
     */
    window.showNotification = function(message, type = 'info') {
        window.dispatchEvent(new CustomEvent('notify', {
            detail: { message, type }
        }));
    };
    </script>
</body>
</html>
