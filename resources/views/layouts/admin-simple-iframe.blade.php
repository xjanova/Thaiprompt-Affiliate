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

    {{-- Vite Assets --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Arrow X Theme Styles --}}
    <x-arrow-x.theme-styles />

    <style>
        /* Content Iframe - Seamless */
        #content-iframe {
            border: none;
            width: 100%;
            height: 100%;
            display: block;
        }

        /* Loading */
        .loading-overlay {
            position: absolute;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            background: rgba(0,0,0,0.5);
            z-index: 100;
        }

        .loading-overlay.show {
            display: flex;
        }
    </style>
</head>
<body class="h-full font-sans overflow-hidden flex">
    {{-- Background Effects --}}
    <div class="fixed inset-0 -z-10 transition-all duration-500"
         style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%)">
    </div>

    {{-- Sidebar (โลโก้ + เมนู) - ไม่โหลดซ้ำ --}}
    <x-arrow-x.sidebar-v3 :iframe-mode="false" />

    {{-- Main Content Area --}}
    <div class="flex flex-col flex-1 h-full overflow-hidden">
        {{-- Navbar - ไม่โหลดซ้ำ --}}
        <x-arrow-x.navbar-v3 />

        {{-- Content Iframe Container --}}
        <div class="flex-1 relative">
            {{-- Loading Overlay --}}
            <div id="loading" class="loading-overlay">
                <div class="text-white text-lg">กำลังโหลด...</div>
            </div>

            {{-- Content Iframe --}}
            <iframe
                id="content-iframe"
                src="{{ route('admin.dashboard') }}"
                title="Content">
            </iframe>
        </div>
    </div>

    {{-- Simple Navigation Script - ไม่ต้อง Alpine.js --}}
    <script>
        const contentIframe = document.getElementById('content-iframe');
        const loading = document.getElementById('loading');

        // คลิกเมนู → เปลี่ยน iframe
        document.addEventListener('click', function(e) {
            const link = e.target.closest('a[href]');

            if (link) {
                const href = link.getAttribute('href');

                // ถ้าเป็น internal link
                if (href && !href.startsWith('#') && !href.startsWith('http') && href !== 'javascript:void(0)') {
                    e.preventDefault();

                    // แสดง loading
                    loading.classList.add('show');

                    // เปลี่ยน iframe
                    contentIframe.src = href;

                    // อัพเดท URL
                    history.pushState({ url: href }, '', href);

                    console.log('Navigate to:', href);
                }
            }
        });

        // ซ่อน loading เมื่อโหลดเสร็จ
        contentIframe.addEventListener('load', function() {
            setTimeout(() => loading.classList.remove('show'), 200);
        });

        // Browser back/forward
        window.addEventListener('popstate', function(event) {
            if (event.state && event.state.url) {
                contentIframe.src = event.state.url;
            }
        });

        console.log('✅ Simple iframe navigation ready');
    </script>
</body>
</html>
