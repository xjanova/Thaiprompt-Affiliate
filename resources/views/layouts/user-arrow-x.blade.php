<!DOCTYPE html>
<html lang="th" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    @php
        // ดึงไอคอนจาก SiteSetting ที่แอดมินอัพโหลดไว้
        $siteSetting = \App\Models\SiteSetting::getSetting();
        $appName = $siteSetting->app_name ?: ($siteSetting->site_name ?: config('app.name'));
        $appIcon = $siteSetting->app_icon_url;

        // Favicon ดึงจาก ThemeSetting ก่อน ถ้าไม่มีใช้จาก SiteSetting
        $themeSetting = \App\Models\ThemeSetting::active();
        $faviconPath = $themeSetting && $themeSetting->favicon_path
            ? asset('storage/' . $themeSetting->favicon_path)
            : $siteSetting->favicon_url;
    @endphp
    <meta name="apple-mobile-web-app-title" content="{{ $appName }}">
    <meta name="theme-color" content="#8B5CF6">

    {{-- PWA Manifest (dynamic - ดึงไอคอนจาก Admin Settings) --}}
    <link rel="manifest" href="{{ route('manifest.json') }}">

    <title>@yield('title', 'Dashboard') - {{ config('app.name') }}</title>

    {{-- Favicon + App Icon จาก Admin --}}
    <link rel="icon" href="{{ $faviconPath }}">
    <link rel="shortcut icon" href="{{ $faviconPath }}">
    <link rel="apple-touch-icon" href="{{ $appIcon }}">
    <link rel="apple-touch-icon" sizes="512x512" href="{{ $appIcon }}">

    {{-- Google Fonts (โหลดเฉพาะ weight ที่ใช้จริง เพื่อลดขนาดไฟล์) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">

    {{-- Font Awesome 6.5.1 --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    {{-- Vite Assets --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Arrow X Theme Styles --}}
    <x-arrow-x.theme-styles />

    {{-- Alpine.js x-cloak + Mobile Performance Optimizations --}}
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

        /**
         * ปิด animation ที่ไม่จำเป็นบนมือถือ เพื่อลดการใช้ GPU/แบตเตอรี่
         * - ปิด background circles animation
         * - ลด backdrop-blur (หนักมากบนมือถือ)
         * - ปิด animate-pulse ที่ไม่จำเป็น
         */
        @media (max-width: 767px) {
            /* ปิด background circles ที่ทำให้ร้อน */
            .mobile-hide-bg-effects {
                display: none !important;
            }

            /* ลด blur effect ลง (ประหยัด GPU) */
            .glass-fusion {
                backdrop-filter: blur(4px) !important;
                -webkit-backdrop-filter: blur(4px) !important;
            }

            /* ปิด continuous pulse animations */
            .animate-pulse {
                animation: none !important;
            }

            /* ปิด hover scale effects (ไม่มี hover บนมือถือ) */
            .hover\:scale-105,
            .hover\:scale-110,
            .group-hover\:scale-110 {
                --tw-scale-x: 1 !important;
                --tw-scale-y: 1 !important;
            }
        }

        /* รองรับ prefers-reduced-motion */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>

    @stack('styles')
</head>
<body class="h-full font-sans overflow-hidden flex"
      x-data="{ profileOpen: false }"
      x-init="
          // เริ่มต้น theme store และ sidebar store
          $store.theme.init();
          $store.sidebar.init();
      ">

    {{-- Background Gradient จาก Arrow X Theme --}}
    {{-- Light mode: Colorful gradient | Dark mode: Dark gradient --}}
    <div class="fixed inset-0 -z-10 transition-all duration-500"
         :style="$store.theme.isDark
             ? 'background: linear-gradient(to bottom right, #111827, #1f2937, #111827)'
             : 'background: var(--arrow-x-primary-gradient, linear-gradient(to right, #9333EA, #EC4899, #F97316))'">
    </div>

    {{-- Animated Background Circles ตามการตั้งค่า Theme Settings (ปิดบนมือถือเพื่อ performance) --}}
    <div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none mobile-hide-bg-effects"
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

    {{-- Sidebar Component สำหรับ User (ใช้ arrow-x-sidebar component) --}}
    <x-arrow-x.sidebar-v3 type="user" />

    {{-- Main Content Area (Flex-1 เพื่อขยายเต็มที่) --}}
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

    {{-- Laravel Session Flash Messages (แปลงเป็น notifications) --}}
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

    {{-- Mobile Bottom Navigation - แสดงเฉพาะมือถือ --}}
    <x-mobile-bottom-navigation type="user" />

    {{-- Mobile Quick Actions - FAB --}}
    <x-mobile-quick-actions type="user" />

    {{-- 🤖 น้อง Eve ผู้ช่วย AI — ต้องวางก่อน @stack('scripts') เสมอ
         (วิดเจ็ตส่งสคริปต์ตัวเองผ่าน @push('scripts') ถ้าวางทีหลัง stack จะไม่ถูก render = ปุ่มขึ้นแต่กดไม่ทำงาน) --}}
    {{-- mobileOffset=88 เพราะหน้านี้มีแถบเมนูล่าง (mobile-bottom-navigation) ลอยอยู่ --}}
    <x-eve.widget surface="user" :mobile-offset="88" />

    @stack('scripts')

    <script>
    /**
     * รับ theme change events จาก Alpine Store
     * เพื่ออัพเดท Chart.js และ components ต่างๆ
     */
    window.addEventListener('theme-changed', (event) => {
        console.log('Theme changed in User Dashboard:', event.detail.isDark ? 'Dark' : 'Light');

        // อัพเดท Chart.js ถ้ามี
        if (typeof Chart !== 'undefined') {
            Chart.defaults.color = event.detail.isDark ? '#e2e8f0' : '#1f2937';
            Chart.defaults.borderColor = event.detail.isDark ? '#374151' : '#e5e7eb';
            Chart.defaults.backgroundColor = event.detail.isDark ? '#1f2937' : '#ffffff';
        }

        // Dispatch event สำหรับ custom components
        window.dispatchEvent(new CustomEvent('user-theme-changed', {
            detail: event.detail
        }));
    });

    /**
     * Helper function สำหรับ show notification
     */
    window.showNotification = function(message, type = 'info') {
        window.dispatchEvent(new CustomEvent('notify', {
            detail: { message, type }
        }));
    };
    </script>

    {{-- PWA: Service Worker Registration + Install Prompt --}}
    <div x-data="pwaInstall()" x-cloak>
        {{-- Install Banner (แสดงเฉพาะเมื่อรองรับ PWA + ยังไม่ได้ install) --}}
        <div x-show="showInstallBanner"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-full"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0 translate-y-full"
             class="fixed bottom-20 left-4 right-4 md:left-auto md:right-4 md:w-96 z-[110] bg-gray-900 border border-purple-500/30 rounded-2xl shadow-2xl p-4">

            <div class="flex items-start gap-3">
                {{-- ไอคอน --}}
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-download text-white text-lg"></i>
                </div>

                {{-- ข้อความ --}}
                <div class="flex-1 min-w-0">
                    <h3 class="text-white font-bold text-sm">ติดตั้งแอป TP-Affiliate</h3>
                    <p class="text-gray-400 text-xs mt-0.5">เพิ่มลงหน้าจอหลักเพื่อเข้าถึงได้ง่ายขึ้น</p>
                </div>

                {{-- ปุ่มปิด --}}
                <button @click="dismissInstall()" class="text-gray-500 hover:text-white p-1" type="button">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>

            {{-- ปุ่ม Actions --}}
            <div class="flex gap-2 mt-3">
                <button @click="installApp()"
                        class="flex-1 py-2.5 bg-gradient-to-r from-purple-600 to-pink-600 text-white text-sm font-semibold rounded-xl active:scale-95 transition-transform"
                        type="button">
                    <i class="fas fa-plus mr-1"></i> ติดตั้ง
                </button>
                <button @click="dismissInstall()"
                        class="px-4 py-2.5 bg-gray-800 text-gray-400 text-sm rounded-xl active:scale-95 transition-transform"
                        type="button">
                    ไว้ทีหลัง
                </button>
            </div>
        </div>
    </div>

    <script>
    /**
     * PWA Install Prompt Component
     * จัดการ beforeinstallprompt event และแสดง custom install banner
     */
    function pwaInstall() {
        return {
            deferredPrompt: null,
            showInstallBanner: false,

            init() {
                // ลงทะเบียน Service Worker
                if ('serviceWorker' in navigator) {
                    navigator.serviceWorker.register('/service-worker.js', { scope: '/' })
                        .then((reg) => {
                            // ตรวจหา update ใหม่
                            reg.addEventListener('updatefound', () => {
                                const newWorker = reg.installing;
                                newWorker.addEventListener('statechange', () => {
                                    if (newWorker.state === 'activated' && navigator.serviceWorker.controller) {
                                        window.showNotification && window.showNotification('อัพเดทแอปสำเร็จ! กรุณารีเฟรช', 'info');
                                    }
                                });
                            });
                        })
                        .catch((err) => console.warn('SW registration failed:', err));
                }

                // จับ beforeinstallprompt event
                window.addEventListener('beforeinstallprompt', (e) => {
                    e.preventDefault();
                    this.deferredPrompt = e;

                    // เช็คว่าเคย dismiss ไปแล้วหรือยัง (ภายใน 7 วัน)
                    const dismissed = localStorage.getItem('pwa-install-dismissed');
                    if (dismissed) {
                        const daysSince = (Date.now() - parseInt(dismissed)) / (1000 * 60 * 60 * 24);
                        if (daysSince < 7) return;
                    }

                    // แสดง banner หลังจาก 5 วินาที (ให้ user ได้ใช้งานก่อน)
                    setTimeout(() => {
                        this.showInstallBanner = true;
                    }, 5000);
                });

                // ตรวจจับว่า install สำเร็จ
                window.addEventListener('appinstalled', () => {
                    this.showInstallBanner = false;
                    this.deferredPrompt = null;
                    localStorage.removeItem('pwa-install-dismissed');
                    window.showNotification && window.showNotification('ติดตั้งแอปสำเร็จ!', 'success');
                });
            },

            async installApp() {
                if (!this.deferredPrompt) return;

                this.deferredPrompt.prompt();
                const { outcome } = await this.deferredPrompt.userChoice;

                if (outcome === 'accepted') {
                    this.showInstallBanner = false;
                }
                this.deferredPrompt = null;
            },

            dismissInstall() {
                this.showInstallBanner = false;
                localStorage.setItem('pwa-install-dismissed', Date.now().toString());
            }
        };
    }
    </script>
</body>
</html>
