<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        $pageType = $pageType ?? 'home';
        $seoData = $seoData ?? [];
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

    <!-- Fonts -->
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    {{-- Dark Mode System --}}
    <x-dark-mode-init />
    <x-dark-mode-styles />

        {{-- แถบหัวโนวาอยู่ในเลย์เอาต์ (เรนเดอร์หลัง stack) จึงโหลดสไตล์ตรงนี้เอง --}}
        <link href="https://fonts.googleapis.com/css2?family=Anuphan:wght@400;500;600;700&family=Trirong:wght@600;700&family=Cinzel:wght@600&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="{{ asset('theme-nova/nova.css') }}?v={{ is_file(public_path('theme-nova/nova.css')) ? filemtime(public_path('theme-nova/nova.css')) : 1 }}">
        <link rel="stylesheet" href="{{ asset('theme-nova/nova-tw.css') }}?v={{ is_file(public_path('theme-nova/nova-tw.css')) ? filemtime(public_path('theme-nova/nova-tw.css')) : 1 }}">

    @stack('styles')

    @stack('seo')

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Laravel Echo Configuration --}}
    <x-echo-config />
</head>
<body class="font-sans antialiased bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 transition-colors duration-300 nv-tw">
        <div class="nv-tw" style="min-height:100vh; display:flex; flex-direction:column;">
            <x-nova.header :solid="true" :search="true" :active="request()->routeIs('tarot.*') ? 'fortune' : ''" />
            <main class="nv-auto-bg" style="flex:1;">
                @yield('content')
            </main>
            <x-nova.footer />
        </div>

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
