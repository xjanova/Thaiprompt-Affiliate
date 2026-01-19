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
                    // เริ่มต้น theme store
                    this.$store.theme.init();

                    // ถ้าไม่ได้อยู่ใน iframe ให้ redirect ไปหน้าปกติ
                    if (!this.isIframe && !window.location.search.includes('iframe=1')) {
                        console.log('ℹ️ Not in iframe mode, consider redirecting to full layout');
                        // Uncomment ถ้าต้องการ force redirect
                        // window.location.href = window.location.pathname;
                    }

                    // ส่ง title ไปยัง parent
                    this.updateParentTitle(document.title);

                    console.log('📄 Iframe Content Manager initialized');
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
