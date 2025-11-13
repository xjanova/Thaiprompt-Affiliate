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
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.13.3/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>

    {{-- Dark Mode System --}}
    <x-dark-mode-init />
    <x-dark-mode-styles />

    @stack('styles')

    @stack('seo')

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
</head>
<body class="font-sans antialiased bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 transition-colors duration-300">
    <!-- Spaceship Background -->
    <x-spaceship-background />

    @php
        // Get user theme preference (use millennium as default for guests)
        $userTheme = auth()->check() ? (auth()->user()->menu_theme_preference ?? 'millennium') : 'millennium';
    @endphp

    @if($userTheme === 'classic_x')
        <!-- Classic X Sidebar -->
        <x-classic-x-sidebar type="user" />

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
    @else
        <!-- Millennium Taskbar -->
        <x-millennium-taskbar type="user" />

        <div class="min-h-screen">
            <!-- Page Content -->
            <main>
                @yield('content')
            </main>

            <!-- Footer -->
            @include('layouts.footer')
        </div>
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

    @stack('scripts')
</body>
</html>
