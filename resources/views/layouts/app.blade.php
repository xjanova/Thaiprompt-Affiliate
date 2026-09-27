<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        $pageType = $pageType ?? 'home';
        $seoData = $seoData ?? [];
        // ธีมโนวา: เฉพาะหน้าสาธารณะที่ยังใช้เลย์เอาต์นี้ — ส่วนผู้ใช้/โรงแรม/หลังบ้านที่ใช้เลย์เอาต์เดียวกันไม่แตะ (เจ้าของสั่งยกเว้นส่วนผู้ใช้)
        // หน้าโรงแรม/ตลาดบอท: เฉพาะหน้าดูสาธารณะ (ระบุชื่อ route ตรงๆ) — หน้าจอง/เช่า (ต้องล็อกอิน) ยังเป็นเลย์เอาต์เดิม
        $novaShell = config('shop.nova_public', true) && request()->routeIs(
            'tarot.*', 'qr-barcode.*', 'software.products.*',
            'marketplace.index', 'marketplace.show',
            'hotels.index', 'hotels.featured', 'hotels.show', 'hotels.search', 'hotels.by-city', 'hotels.by-province', 'hotels.by-region', 'hotels.reviews.index'
        );
    @endphp

    {!! render_seo_meta($pageType, $seoData) !!}

    {{-- Structured Data (Schema.org JSON-LD) — สัญญาณสำคัญสำหรับ Google AI Overviews / Gemini --}}
    {!! render_global_structured_data() !!}

    @php
        $favicon = \App\Models\Setting::get('favicon');
    @endphp
    @if($favicon)
        <link rel="icon" type="image/x-icon" href="{{ asset($favicon) }}">
    @endif

    <!-- Preconnect to external domains for better performance -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link rel="dns-prefetch" href="https://cdn.tailwindcss.com">
    <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">

    <!-- Fonts -->
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    {{-- Dark Mode System --}}
    <x-dark-mode-init />
    <x-dark-mode-styles />

    @if($novaShell)
        {{-- แถบหัวโนวาอยู่ในเลย์เอาต์ (เรนเดอร์หลัง stack) จึงโหลดสไตล์ตรงนี้เอง --}}
        <link href="https://fonts.googleapis.com/css2?family=Anuphan:wght@400;500;600;700&family=Trirong:wght@600;700&family=Cinzel:wght@600&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="{{ asset('theme-nova/nova.css') }}?v={{ is_file(public_path('theme-nova/nova.css')) ? filemtime(public_path('theme-nova/nova.css')) : 1 }}">
        <link rel="stylesheet" href="{{ asset('theme-nova/nova-tw.css') }}?v={{ is_file(public_path('theme-nova/nova-tw.css')) ? filemtime(public_path('theme-nova/nova-tw.css')) : 1 }}">
    @endif

    @stack('styles')

    @stack('seo')

    @unless($novaShell)
    <style>
        /* Ensure Windows UI has proper z-index */
        body {
            @php
                $taskbarPosition = \App\Models\WindowsUiSetting::get('windows_taskbar_position', 'top');
                $taskbarHeight = \App\Models\WindowsUiSetting::get('windows_taskbar_height', 48);
            @endphp
            @if($taskbarPosition === 'top')
                padding-top: {{ $taskbarHeight }}px;
            @else
                padding-bottom: {{ $taskbarHeight }}px;
            @endif
        }
    </style>
    @endunless

    {{-- Laravel Echo Configuration --}}
    <x-echo-config />
</head>
<body class="font-sans antialiased bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 transition-colors duration-300{{ $novaShell ? ' nv-tw' : '' }}">
    <!-- Spaceship Background -->
    <x-spaceship-background />

    @php
        // กำหนด taskbar type ตาม auth status
        $taskbarType = auth()->check() ? 'user' : 'guest';
    @endphp

    @if($novaShell)
        {{-- ธีมโนวา: แถบหัวทึบติดบน + ท้ายเว็บโนวา แทนแถบงาน Classic X --}}
        <div class="nv-tw" style="min-height:100vh; display:flex; flex-direction:column;">
            <x-nova.header :solid="true" :search="true" :active="request()->routeIs('tarot.*') ? 'fortune' : ''" />
            <main style="flex:1;">
                @yield('content')
            </main>
            <x-nova.footer />
        </div>
    @else
    <!-- Classic X Sidebar -->
    <x-classic-x-sidebar type="{{ $taskbarType }}" />

    <!-- Classic X Content Wrapper -->
    <div class="classic-x-content" id="classicXContent">
        <!-- Page Content -->
        <main>
            @yield('content')
        </main>

        <!-- Footer -->
        @include('layouts.footer')
    </div>

    <!-- Floating Action Buttons for Classic X Theme -->
    <x-classic-x-floating-buttons />
    @endif

    {{-- Google Translate Widget (Like WordPress Plugins) --}}

    {{-- Dark Mode Toggle Function --}}
    <script>
        function toggleDarkMode() {
            const isDark = document.documentElement.classList.contains('dark');

            if (isDark) {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('darkMode', 'light');
            } else {
                document.documentElement.classList.add('dark');
                localStorage.setItem('darkMode', 'dark');
            }
        }
    </script>

    {{-- Cookie Consent Banner --}}
    <x-cookie-consent-banner />

    {{-- Emergency Alert System --}}
    <x-emergency-alert-banner position="global" />
    <x-emergency-alert-popup position="global" />
    <x-emergency-alert-marquee position="global" />

    {{-- Service Worker for Offline Support --}}
    @vite('resources/js/service-worker-register.js')

    {{-- Laravel Echo for Real-time Notifications --}}
    @vite('resources/js/echo-setup.js')

    @stack('scripts')
</body>
</html>
