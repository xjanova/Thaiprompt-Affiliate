<!DOCTYPE html>
<html lang="th" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin - {{ config('app.name') }}</title>

    {{-- Favicon --}}
    @php
        $themeSetting = \App\Models\ThemeSetting::active();
        $faviconPath = $themeSetting && $themeSetting->favicon_path
            ? asset('storage/' . $themeSetting->favicon_path)
            : asset('favicon.ico');
    @endphp
    <link rel="icon" type="image/x-icon" href="{{ $faviconPath }}">

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { overflow: hidden; font-family: sans-serif; }

        /* Layout: Sidebar + Content */
        .admin-layout {
            display: flex;
            height: 100vh;
            width: 100vw;
        }

        /* Sidebar Iframe */
        #sidebar-iframe {
            width: 280px;
            height: 100%;
            border: none;
            flex-shrink: 0;
        }

        /* Content Iframe */
        #content-iframe {
            flex: 1;
            height: 100%;
            border: none;
        }

        /* Loading Indicator */
        .loading {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 20px 40px;
            border-radius: 10px;
            z-index: 9999;
            display: none;
        }

        .loading.show {
            display: block;
        }
    </style>
</head>
<body>
    {{-- Loading Indicator --}}
    <div id="loading" class="loading">กำลังโหลด...</div>

    {{-- Main Layout: 2 Iframes --}}
    <div class="admin-layout">
        {{-- Iframe 1: Sidebar/Menu (ไม่ต้องโหลดใหม่) --}}
        <iframe
            id="sidebar-iframe"
            src="{{ route('admin.sidebar') }}"
            title="Sidebar Menu">
        </iframe>

        {{-- Iframe 2: Content (โหลดตามเมนู) --}}
        <iframe
            id="content-iframe"
            src="{{ route('admin.dashboard') }}"
            title="Main Content">
        </iframe>
    </div>

    <script>
        /**
         * Simple Iframe Communication
         * ไม่ต้องใช้ Alpine.js!
         */

        const sidebarIframe = document.getElementById('sidebar-iframe');
        const contentIframe = document.getElementById('content-iframe');
        const loading = document.getElementById('loading');

        // ฟัง message จาก sidebar iframe
        window.addEventListener('message', function(event) {
            // เช็ค origin (security)
            if (event.origin !== window.location.origin) return;

            const { type, url, title } = event.data;

            if (type === 'navigate') {
                console.log('🎯 Navigate to:', url);

                // แสดง loading
                loading.classList.add('show');

                // เปลี่ยน URL ของ content iframe
                contentIframe.src = url;

                // อัพเดท browser URL (ไม่มี reload)
                history.pushState({ url }, title, url);

                // อัพเดท page title
                if (title) {
                    document.title = title + ' - Admin - {{ config("app.name") }}';
                }
            }
        });

        // ซ่อน loading เมื่อ content โหลดเสร็จ
        contentIframe.addEventListener('load', function() {
            setTimeout(function() {
                loading.classList.remove('show');
            }, 200);
        });

        // รองรับ browser back/forward
        window.addEventListener('popstate', function(event) {
            if (event.state && event.state.url) {
                contentIframe.src = event.state.url;
            }
        });

        console.log('✅ Two-iframe system initialized');
        console.log('📋 Sidebar iframe:', sidebarIframe.src);
        console.log('📄 Content iframe:', contentIframe.src);
    </script>
</body>
</html>
