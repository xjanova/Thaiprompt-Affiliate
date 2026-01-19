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
    <x-arrow-x.sidebar-v3 :iframe-mode="true" />

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
                    // เริ่มต้น theme store และ sidebar store
                    this.$store.theme.init();
                    this.$store.sidebar.init();

                    // เก็บ reference ของ iframe
                    this.iframe = document.getElementById('content-iframe');

                    // ฟัง message จาก iframe (สำหรับ communication)
                    window.addEventListener('message', (event) => {
                        this.handleIframeMessage(event);
                    });

                    // ฟังการคลิกเมนูจาก sidebar
                    document.addEventListener('navigate-iframe', (event) => {
                        this.navigateTo(event.detail.url, event.detail.title);
                    });

                    // Set initial URL จาก current path
                    const initialPath = window.location.pathname + window.location.search;
                    if (initialPath !== '/admin') {
                        this.currentUrl = initialPath + (initialPath.includes('?') ? '&' : '?') + 'iframe=1';
                    }

                    console.log('🖼️ Iframe Navigation Manager initialized');
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

    @stack('scripts')
</body>
</html>
