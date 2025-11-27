<!DOCTYPE html>
<html lang="th" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">

    <title>@yield('title', 'Wiki') - {{ config('app.name') }}</title>

    {{-- Favicon --}}
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

        /**
         * Glass Fusion Effect - Arrow X V3 Standard
         * Light mode: พื้นหลังขาวโปร่งใสเพื่อให้ตัวหนังสือสีเข้มมีคอนทราสต์ดี
         * Dark mode: พื้นหลังมืดโปร่งใส
         */
        .glass-fusion {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }

        .dark .glass-fusion {
            background: rgba(0, 0, 0, 0.3);
        }

        /**
         * Glass Dropdown Effect
         */
        .glass-dropdown {
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
        }

        /**
         * Glass Neumorphic Effect
         */
        .glass-neu {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }
    </style>

    @stack('styles')
</head>
<body class="min-h-full font-sans"
      x-data="{
          isDark: localStorage.getItem('theme') === 'dark' || (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches),
          toggleTheme() {
              this.isDark = !this.isDark;
              localStorage.setItem('theme', this.isDark ? 'dark' : 'light');
              document.documentElement.classList.toggle('dark', this.isDark);
          }
      }"
      x-init="document.documentElement.classList.toggle('dark', isDark)"
      :class="isDark ? 'dark' : ''">

    {{-- Background Gradient - Arrow X V3 Style --}}
    <div class="fixed inset-0 -z-10 transition-all duration-500"
         :style="isDark
             ? 'background: linear-gradient(to bottom right, #111827, #1f2937, #111827)'
             : 'background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%)'">
    </div>

    {{-- Animated Background Circles - Arrow X V3 --}}
    <div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none">
        <div class="absolute top-1/4 left-1/4 w-96 h-96 rounded-full animate-pulse transition-all duration-500"
             :style="isDark
                 ? 'background: linear-gradient(to bottom right, #3b82f6, #8b5cf6); opacity: 0.1; filter: blur(80px);'
                 : 'background: linear-gradient(to bottom right, #3b82f6, #8b5cf6); opacity: 0.2; filter: blur(80px);'">
        </div>
        <div class="absolute bottom-1/4 right-1/4 w-80 h-80 rounded-full animate-pulse transition-all duration-500"
             style="animation-delay: 1s;"
             :style="isDark
                 ? 'background: linear-gradient(to bottom right, #8b5cf6, #ec4899); opacity: 0.1; filter: blur(80px);'
                 : 'background: linear-gradient(to bottom right, #8b5cf6, #ec4899); opacity: 0.2; filter: blur(80px);'">
        </div>
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-72 h-72 rounded-full animate-pulse transition-all duration-500"
             style="animation-delay: 2s;"
             :style="isDark
                 ? 'background: linear-gradient(to bottom right, #6366f1, #a855f7); opacity: 0.1; filter: blur(80px);'
                 : 'background: linear-gradient(to bottom right, #6366f1, #a855f7); opacity: 0.15; filter: blur(80px);'">
        </div>
    </div>

    {{-- Top Navigation Bar - Arrow X V3 Glass Fusion --}}
    <header class="sticky top-0 z-50 glass-fusion border-b border-white/20 dark:border-gray-700/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                {{-- Logo & Brand --}}
                <div class="flex items-center gap-4">
                    <a href="{{ route('home') }}" class="flex items-center gap-3 group">
                        @php
                            $logo = \App\Models\Setting::get('logo');
                        @endphp
                        @if($logo)
                            <img src="{{ asset('storage/' . $logo) }}" alt="Logo" class="h-10 w-auto group-hover:scale-105 transition-transform">
                        @else
                            <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center text-white font-bold text-lg shadow-lg group-hover:scale-105 transition-transform">
                                TP
                            </div>
                        @endif
                        <span class="hidden sm:block text-gray-800 dark:text-white font-bold text-lg dark:drop-shadow">
                            {{ config('app.name', 'TP-Affiliate') }}
                        </span>
                    </a>
                </div>

                {{-- Navigation Links --}}
                <nav class="hidden md:flex items-center gap-6">
                    <a href="{{ route('home') }}" class="text-gray-600 dark:text-white/80 hover:text-gray-900 dark:hover:text-white font-medium transition-colors dark:drop-shadow">
                        <i class="fas fa-home mr-2"></i>หน้าแรก
                    </a>
                    <a href="{{ route('wiki.index') }}" class="text-gray-900 dark:text-white font-medium transition-colors dark:drop-shadow border-b-2 border-gray-900 dark:border-white pb-1">
                        <i class="fas fa-book mr-2"></i>Wiki
                    </a>
                    <a href="{{ route('marketplace.index') }}" class="text-gray-600 dark:text-white/80 hover:text-gray-900 dark:hover:text-white font-medium transition-colors dark:drop-shadow">
                        <i class="fas fa-store mr-2"></i>Marketplace
                    </a>
                </nav>

                {{-- Right Side Actions --}}
                <div class="flex items-center gap-3">
                    {{-- Dark Mode Toggle --}}
                    <button @click="toggleTheme()"
                            type="button"
                            class="p-2.5 rounded-xl glass-neu hover:bg-gray-200/50 dark:hover:bg-white/20 transition-all hover:scale-110 active:scale-95 text-gray-700 dark:text-white"
                            :title="isDark ? 'เปลี่ยนเป็นโหมดสว่าง' : 'เปลี่ยนเป็นโหมดมืด'">
                        <i x-show="!isDark" class="fas fa-moon dark:drop-shadow"></i>
                        <i x-show="isDark" class="fas fa-sun text-yellow-300 drop-shadow"></i>
                    </button>

                    {{-- Auth Buttons --}}
                    @guest
                        <a href="{{ route('login') }}"
                           class="hidden sm:inline-flex items-center gap-2 px-4 py-2 text-gray-600 dark:text-white/90 hover:text-gray-900 dark:hover:text-white font-medium transition-colors">
                            <i class="fas fa-sign-in-alt"></i>
                            <span>เข้าสู่ระบบ</span>
                        </a>
                        <a href="{{ route('register') }}"
                           class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-400 hover:to-purple-500 text-white font-medium rounded-xl shadow-lg shadow-purple-500/30 transition-all hover:scale-105">
                            <i class="fas fa-user-plus"></i>
                            <span class="hidden sm:inline">สมัครสมาชิก</span>
                        </a>
                    @else
                        {{-- User Dropdown --}}
                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open"
                                    class="flex items-center gap-2 p-2 pr-3 rounded-xl glass-neu hover:bg-gray-200/50 dark:hover:bg-white/20 transition-all hover:scale-105 active:scale-95">
                                <img src="{{ auth()->user()->profile_picture_url }}"
                                     alt="{{ auth()->user()->name }}"
                                     class="w-8 h-8 rounded-lg object-cover shadow-lg"
                                     onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name={{ urlencode(substr(auth()->user()->name, 0, 1)) }}&background=8B5CF6&color=fff&size=64';">
                                <span class="hidden md:block text-gray-800 dark:text-white font-medium text-sm dark:drop-shadow">{{ auth()->user()->name }}</span>
                                <i class="fas fa-chevron-down text-gray-500 dark:text-white/60 text-xs dark:drop-shadow transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>
                            </button>

                            {{-- Dropdown Menu --}}
                            <div x-show="open"
                                 x-cloak
                                 @click.outside="open = false"
                                 x-transition
                                 class="absolute top-full right-0 mt-2 w-56 glass-dropdown rounded-2xl shadow-2xl border border-white/20 overflow-hidden z-50">
                                <div class="px-4 py-3 border-b border-white/20">
                                    <p class="text-white font-medium text-sm">{{ auth()->user()->name }}</p>
                                    <p class="text-white/60 text-xs">{{ auth()->user()->email }}</p>
                                </div>
                                <div class="py-2">
                                    <a href="{{ route('user.dashboard') }}" class="flex items-center gap-3 px-4 py-2.5 text-white/80 hover:text-white hover:bg-white/10 transition-all">
                                        <i class="fas fa-tachometer-alt w-5"></i>
                                        <span>แดชบอร์ด</span>
                                    </a>
                                    <a href="{{ route('user.profile') }}" class="flex items-center gap-3 px-4 py-2.5 text-white/80 hover:text-white hover:bg-white/10 transition-all">
                                        <i class="fas fa-user w-5"></i>
                                        <span>โปรไฟล์</span>
                                    </a>
                                    @if(auth()->user()->hasRole(['admin', 'super_admin']))
                                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-4 py-2.5 text-white/80 hover:text-white hover:bg-white/10 transition-all">
                                        <i class="fas fa-cog w-5"></i>
                                        <span>แอดมิน</span>
                                    </a>
                                    @endif
                                </div>
                                <div class="py-2 border-t border-white/20">
                                    <form action="{{ route('logout') }}" method="POST">
                                        @csrf
                                        <button type="submit" class="w-full flex items-center gap-3 px-4 py-2.5 text-red-300 hover:text-red-200 hover:bg-red-500/20 transition-all">
                                            <i class="fas fa-sign-out-alt w-5"></i>
                                            <span>ออกจากระบบ</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endguest
                </div>
            </div>
        </div>
    </header>

    {{-- Main Content --}}
    <main class="min-h-screen">
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="glass-fusion border-t border-gray-200 dark:border-white/20 dark:border-gray-700/50 py-8 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="text-gray-600 dark:text-white/60 text-sm">
                    &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                </div>
                <div class="flex items-center gap-6">
                    <a href="{{ route('wiki.index') }}" class="text-gray-600 dark:text-white/60 hover:text-gray-900 dark:hover:text-white text-sm transition-colors">Wiki</a>
                    <a href="{{ route('marketplace.index') }}" class="text-gray-600 dark:text-white/60 hover:text-gray-900 dark:hover:text-white text-sm transition-colors">Marketplace</a>
                    @auth
                    <a href="{{ route('user.dashboard') }}" class="text-gray-600 dark:text-white/60 hover:text-gray-900 dark:hover:text-white text-sm transition-colors">Dashboard</a>
                    @endauth
                </div>
            </div>
        </div>
    </footer>

    {{-- Toast Notifications --}}
    <div class="fixed bottom-4 right-4 z-[90] space-y-2 max-w-md"
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

    {{-- Laravel Session Flash Messages --}}
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

    @stack('scripts')

    <script>
    /**
     * Helper function สำหรับ show notification
     */
    window.showNotification = function(message, type = 'info') {
        window.dispatchEvent(new CustomEvent('notify', {
            detail: { message, type }
        }));
    };
    </script>
</body>
</html>
