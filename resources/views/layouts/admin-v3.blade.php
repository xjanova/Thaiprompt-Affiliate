@php
    /**
     * ตรวจสอบว่าเป็น iframe mode หรือไม่
     * ถ้ามี ?iframe=1 → แสดงเฉพาะ content (ไม่มี sidebar/navbar)
     * ถ้าไม่มี → แสดง parent layout (sidebar + navbar + iframe)
     */
    $isIframeMode = request()->query('iframe') === '1';
@endphp

@if($isIframeMode)
    {{-- ========================================
         IFRAME MODE: Content Only
         คัดลอกทั้งหมดจาก admin-content-only.blade.php
         ======================================== --}}
    <!DOCTYPE html>
    <html lang="th" class="h-full">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', 'Dashboard') - Admin - {{ config('app.name') }}</title>

        {{-- Favicon --}}
        @php
            $themeSetting = \App\Models\ThemeSetting::active();
            $faviconPath = $themeSetting && $themeSetting->favicon_path
                ? asset('storage/' . $themeSetting->favicon_path)
                : asset('favicon.ico');
        @endphp
        <link rel="icon" type="image/x-icon" href="{{ $faviconPath }}">

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

        {{-- Alpine.js CDN Fallback (เผื่อ Vite ไม่โหลด) --}}
        <script>
            // เช็คว่า Alpine.js โหลดจาก Vite หรือยัง
            window.addEventListener('DOMContentLoaded', function() {
                setTimeout(function() {
                    if (typeof Alpine === 'undefined') {
                        console.warn('⚠️ Alpine.js not loaded from Vite, loading from CDN...');

                        // โหลด Alpine.js จาก CDN
                        const script = document.createElement('script');
                        script.src = 'https://cdn.jsdelivr.net/npm/alpinejs@3.13.5/dist/cdn.min.js';
                        script.defer = true;
                        script.onload = function() {
                            console.log('✅ Alpine.js loaded from CDN');
                            // Dispatch event เพื่อบอกว่า Alpine พร้อมแล้ว
                            window.dispatchEvent(new Event('alpine:init'));
                        };
                        script.onerror = function() {
                            console.error('❌ Failed to load Alpine.js from CDN');
                        };
                        document.head.appendChild(script);
                    } else {
                        console.log('✅ Alpine.js loaded from Vite');
                    }
                }, 1000);
            });
        </script>

        @stack('styles')

        {{-- Alpine.js x-cloak --}}
        <style>
            [x-cloak] {
                display: none !important;
            }

            /* Content-only styles (ไม่มี sidebar) */
            body {
                background: transparent !important;
            }
        </style>
    </head>
    <body class="h-full font-sans antialiased bg-transparent"
          x-data="iframeContentManager()"
          x-init="init()">

        {{-- Page Content (เฉพาะ content ไม่มี sidebar/navbar) --}}
        <main class="h-full overflow-y-auto p-4 md:p-6">
            @yield('content')
        </main>

        {{-- Iframe Content Manager (Alpine.js Component) --}}
        <script>
            /**
             * Iframe Content Manager
             *
             * จัดการการส่ง message ไปยัง parent window
             * - อัพเดท title
             * - นำทางไปหน้าอื่น
             * - แสดง notification
             */
            function iframeContentManager() {
                return {
                    isIframe: window.self !== window.top,

                    /**
                     * เริ่มต้น component
                     */
                    init() {
                        console.log('🎯 IFRAME CONTENT MODE: Starting initialization...');
                        console.log('🔍 Current URL:', window.location.href);
                        console.log('🖼️ Is inside iframe?', this.isIframe);

                        // เริ่มต้น theme store
                        this.$store.theme.init();

                        // ถ้าไม่ได้อยู่ใน iframe ให้ redirect ไปหน้าปกติ
                        if (!this.isIframe && !window.location.search.includes('iframe=1')) {
                            console.warn('⚠️ Not in iframe mode, but loaded as content-only layout');
                            console.log('💡 This might cause display issues');
                        }

                        // ส่ง title ไปยัง parent
                        this.updateParentTitle(document.title);

                        console.log('✅ Iframe Content Manager initialized');
                        console.log('📄 Ready to display content');
                    },

                    /**
                     * อัพเดท title ของ parent window
                     *
                     * @param {string} title
                     */
                    updateParentTitle(title) {
                        if (this.isIframe) {
                            this.sendToParent('title-update', { title });
                        }
                    },

                    /**
                     * นำทางไปยัง URL อื่นใน parent
                     *
                     * @param {string} url
                     * @param {string} title
                     */
                    navigateParent(url, title = '') {
                        if (this.isIframe) {
                            this.sendToParent('navigate', { url, title });
                        } else {
                            window.location.href = url;
                        }
                    },

                    /**
                     * แสดง notification ใน parent
                     *
                     * @param {string} message
                     * @param {string} type - success|error|warning|info
                     */
                    notifyParent(message, type = 'info') {
                        if (this.isIframe) {
                            this.sendToParent('notification', { message, type });
                        } else {
                            // Fallback: แสดงใน console
                            console.log(`[${type.toUpperCase()}] ${message}`);
                        }
                    },

                    /**
                     * Reload iframe
                     */
                    reloadIframe() {
                        if (this.isIframe) {
                            this.sendToParent('reload', {});
                        } else {
                            window.location.reload();
                        }
                    },

                    /**
                     * ส่ง message ไปยัง parent window
                     *
                     * @param {string} type
                     * @param {Object} data
                     */
                    sendToParent(type, data) {
                        if (this.isIframe && window.parent) {
                            window.parent.postMessage(
                                { type, data },
                                window.location.origin
                            );
                        }
                    }
                }
            }

            /**
             * Global helpers สำหรับใช้งานใน inline scripts
             */
            window.iframeNavigate = function(url, title = '') {
                const manager = Alpine.store('iframeContent');
                if (manager) {
                    manager.navigateParent(url, title);
                } else {
                    // Fallback
                    if (window.self !== window.top) {
                        window.parent.postMessage(
                            { type: 'navigate', data: { url, title } },
                            window.location.origin
                        );
                    } else {
                        window.location.href = url;
                    }
                }
            };

            window.iframeNotify = function(message, type = 'info') {
                const manager = Alpine.store('iframeContent');
                if (manager) {
                    manager.notifyParent(message, type);
                } else {
                    console.log(`[${type.toUpperCase()}] ${message}`);
                }
            };

            window.iframeReload = function() {
                if (window.self !== window.top) {
                    window.parent.postMessage(
                        { type: 'reload', data: {} },
                        window.location.origin
                    );
                } else {
                    window.location.reload();
                }
            };
        </script>

        @stack('scripts')
    </body>
    </html>

@else
    {{-- ========================================
         NORMAL MODE: Full Layout with Iframe
         คัดลอกทั้งหมดจาก admin-iframe.blade.php
         ======================================== --}}
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

        {{-- Alpine.js CDN Fallback (เผื่อ Vite ไม่โหลด) --}}
        <script>
            // เช็คว่า Alpine.js โหลดจาก Vite หรือยัง
            window.addEventListener('DOMContentLoaded', function() {
                setTimeout(function() {
                    if (typeof Alpine === 'undefined') {
                        console.warn('⚠️ Alpine.js not loaded from Vite, loading from CDN...');

                        // โหลด Alpine.js จาก CDN
                        const script = document.createElement('script');
                        script.src = 'https://cdn.jsdelivr.net/npm/alpinejs@3.13.5/dist/cdn.min.js';
                        script.defer = true;
                        script.onload = function() {
                            console.log('✅ Alpine.js loaded from CDN');
                            // Dispatch event เพื่อบอกว่า Alpine พร้อมแล้ว
                            window.dispatchEvent(new Event('alpine:init'));
                        };
                        script.onerror = function() {
                            console.error('❌ Failed to load Alpine.js from CDN');
                        };
                        document.head.appendChild(script);
                    } else {
                        console.log('✅ Alpine.js loaded from Vite');
                    }
                }, 1000);
            });
        </script>

        @stack('styles')

        {{-- Alpine.js x-cloak - ซ่อน element จนกว่า Alpine จะโหลดเสร็จ --}}
        <style>
            [x-cloak] {
                display: none !important;
            }

            /* Iframe Seamless Styles - ทำให้ดูไม่ออกว่าเป็น iframe */
            #content-iframe {
                border: none;
                width: 100%;
                height: 100%;
                display: block;
            }

            /* Loading Overlay */
            .iframe-loading-overlay {
                position: absolute;
                inset: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                background: rgba(255, 255, 255, 0.8);
                backdrop-filter: blur(4px);
                z-index: 50;
                transition: opacity 0.3s ease;
            }

            .dark .iframe-loading-overlay {
                background: rgba(17, 24, 39, 0.8);
            }

            /* Smooth Transitions */
            .iframe-container {
                transition: opacity 0.2s ease;
            }

            .iframe-container.loading {
                opacity: 0.6;
            }
        </style>
    </head>
    <body class="h-full font-sans overflow-hidden flex"
          x-data="iframeNavigationManager()"
          x-init="init()"
          @popstate.window="handleBrowserNavigation($event)">

        {{-- Background Gradient พื้นหลังแบบ Dashboard4 - ใช้ Arrow X Theme Variables --}}
        <div class="fixed inset-0 -z-10 transition-all duration-500"
             :style="$store.theme.isDark
                 ? 'background: linear-gradient(to bottom right, #111827, #1f2937, #111827)'
                 : 'background: var(--arrow-x-primary-gradient, linear-gradient(to right, #9333EA, #EC4899, #F97316))'">
        </div>

        {{-- Animated Background Circles วงกลมเคลื่อนไหวพื้นหลัง --}}
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

        {{-- Sidebar Component with Iframe Navigation --}}
        <x-arrow-x.sidebar-v3 :iframe-mode="false" />

        {{-- Main Content Area with Iframe --}}
        <div class="flex flex-col flex-1 h-full overflow-hidden">
            {{-- Top Navbar Component --}}
            <x-arrow-x.navbar-v3 />

            {{-- Iframe Container --}}
            <div class="flex-1 relative iframe-container" :class="{ 'loading': isLoading }">
                {{-- Loading Overlay --}}
                <div x-show="isLoading"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="iframe-loading-overlay">
                    <div class="flex flex-col items-center space-y-3">
                        {{-- Spinner --}}
                        <div class="relative">
                            <div class="w-12 h-12 rounded-full border-4 border-gray-200 dark:border-gray-700"></div>
                            <div class="w-12 h-12 rounded-full border-4 border-blue-600 border-t-transparent animate-spin absolute inset-0"></div>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400" x-text="loadingMessage"></p>
                    </div>
                </div>

                {{-- Content Iframe (Seamless) --}}
                <iframe
                    id="content-iframe"
                    name="content-frame"
                    src="{{ route('admin.dashboard') }}?iframe=1"
                    :src="currentUrl"
                    @load="handleIframeLoad()"
                    sandbox="allow-same-origin allow-scripts allow-forms allow-popups allow-modals allow-downloads"
                    loading="lazy"
                    allowfullscreen>
                </iframe>
            </div>
        </div>

        {{-- Iframe Navigation Manager (Alpine.js Component) --}}
        <script>
            /**
             * Iframe Navigation Manager
             *
             * จัดการการนำทางแบบ iframe seamless:
             * - คลิกเมนูแล้วเปลี่ยน iframe src
             * - อัพเดท browser URL ด้วย History API
             * - รองรับ back/forward button
             * - Loading state management
             */
            function iframeNavigationManager() {
                return {
                    currentUrl: '{{ route('admin.dashboard') }}?iframe=1',
                    isLoading: false,
                    loadingMessage: 'กำลังโหลด...',
                    iframe: null,

                    /**
                     * เริ่มต้น component
                     */
                    init() {
                        console.log('🚀 Iframe Navigation Manager: Starting initialization...');

                        // เช็คว่า Alpine.js พร้อมหรือยัง
                        if (typeof this.$store === 'undefined') {
                            console.error('❌ Alpine.js stores not available!');
                            console.warn('⚠️ Retrying in 500ms...');

                            // Retry after delay
                            setTimeout(() => {
                                this.init();
                            }, 500);
                            return;
                        }

                        // เริ่มต้น theme store และ sidebar store
                        try {
                            this.$store.theme.init();
                            this.$store.sidebar.init();
                            console.log('✅ Alpine stores initialized');
                        } catch (error) {
                            console.error('❌ Failed to initialize Alpine stores:', error);
                        }

                        // เก็บ reference ของ iframe
                        this.iframe = document.getElementById('content-iframe');
                        if (!this.iframe) {
                            console.error('❌ Content iframe not found!');
                            return;
                        }
                        console.log('✅ Iframe element found:', this.iframe);

                        // ฟัง message จาก iframe (สำหรับ communication)
                        window.addEventListener('message', (event) => {
                            this.handleIframeMessage(event);
                        });

                        // ฟังการคลิกเมนูจาก sidebar
                        document.addEventListener('navigate-iframe', (event) => {
                            console.log('📨 Navigate event received:', event.detail);
                            this.navigateTo(event.detail.url, event.detail.title);
                        });

                        // Set initial URL จาก current path
                        const initialPath = window.location.pathname + window.location.search;
                        console.log('🔍 Current path:', initialPath);

                        // ลบ ?iframe=1 ออกจาก path ถ้ามี (ป้องกัน double ?iframe=1)
                        const cleanPath = initialPath.replace(/[?&]iframe=1/g, '');

                        // เพิ่ม ?iframe=1 เข้าไป
                        const separator = cleanPath.includes('?') ? '&' : '?';
                        this.currentUrl = cleanPath + separator + 'iframe=1';

                        console.log('✅ Iframe Navigation Manager initialized');
                        console.log('📍 Initial iframe URL:', this.currentUrl);
                    },

                    /**
                     * นำทางไปยัง URL ใหม่
                     *
                     * @param {string} url - URL ที่ต้องการไป
                     * @param {string} title - Title สำหรับ history
                     */
                    navigateTo(url, title = '') {
                        // แสดง loading
                        this.isLoading = true;
                        this.loadingMessage = title ? `กำลังโหลด ${title}...` : 'กำลังโหลด...';

                        // เพิ่ม ?iframe=1 parameter เพื่อบอกว่าโหลดใน iframe
                        const iframeUrl = url + (url.includes('?') ? '&' : '?') + 'iframe=1';

                        // เปลี่ยน iframe src
                        this.currentUrl = iframeUrl;

                        // อัพเดท browser URL (ไม่มี iframe parameter)
                        if (window.location.pathname + window.location.search !== url) {
                            window.history.pushState(
                                { url: url, title: title },
                                title,
                                url
                            );
                        }

                        // อัพเดท page title
                        if (title) {
                            document.title = `${title} - Admin - {{ config('app.name') }}`;
                        }
                    },

                    /**
                     * จัดการเมื่อ iframe โหลดเสร็จ
                     */
                    handleIframeLoad() {
                        // ซ่อน loading
                        setTimeout(() => {
                            this.isLoading = false;
                        }, 200);

                        // อ่าน title จาก iframe
                        try {
                            const iframeDocument = this.iframe.contentDocument || this.iframe.contentWindow.document;
                            const iframeTitle = iframeDocument.title;

                            if (iframeTitle) {
                                document.title = iframeTitle;
                            }
                        } catch (error) {
                            // Ignore cross-origin errors
                            console.warn('Cannot read iframe title:', error.message);
                        }
                    },

                    /**
                     * จัดการ message จาก iframe
                     *
                     * @param {MessageEvent} event
                     */
                    handleIframeMessage(event) {
                        // ตรวจสอบ origin (ความปลอดภัย)
                        if (event.origin !== window.location.origin) {
                            return;
                        }

                        const { type, data } = event.data;

                        switch (type) {
                            case 'navigate':
                                // iframe ต้องการนำทางไปหน้าอื่น
                                this.navigateTo(data.url, data.title);
                                break;

                            case 'title-update':
                                // iframe อัพเดท title
                                document.title = data.title;
                                break;

                            case 'notification':
                                // iframe ส่ง notification (แสดงใน parent)
                                this.showNotification(data);
                                break;

                            case 'reload':
                                // iframe ต้องการ reload ตัวเอง
                                this.iframe.src = this.iframe.src;
                                break;

                            default:
                                console.warn('Unknown iframe message type:', type);
                        }
                    },

                    /**
                     * จัดการ browser back/forward
                     *
                     * @param {PopStateEvent} event
                     */
                    handleBrowserNavigation(event) {
                        if (event.state && event.state.url) {
                            // มี state จาก pushState
                            this.navigateTo(event.state.url, event.state.title);
                        } else {
                            // ไม่มี state (กลับไป initial page)
                            this.navigateTo(window.location.pathname + window.location.search);
                        }
                    },

                    /**
                     * แสดง notification (จาก iframe)
                     *
                     * @param {Object} data
                     */
                    showNotification(data) {
                        // ใช้ระบบ notification ที่มีอยู่แล้ว
                        // หรือแสดงด้วย Alpine.js notification component
                        console.log('Notification from iframe:', data);

                        // TODO: Integrate with existing notification system
                        if (window.showToast) {
                            window.showToast(data.message, data.type || 'info');
                        }
                    }
                }
            }
        </script>

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
@endif
