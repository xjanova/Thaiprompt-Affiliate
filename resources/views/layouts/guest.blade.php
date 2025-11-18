<!DOCTYPE html>
<html lang="th" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }" :class="{ 'dark': darkMode }" x-init="$watch('darkMode', val => localStorage.setItem('darkMode', val))">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        $appName = \App\Models\Setting::get('app_name', 'TP-Affiliate');
        $favicon = \App\Models\Setting::get('favicon');
    @endphp

    <title>@yield('title', 'หน้าแรก') - {{ $appName }}</title>

    @if($favicon)
        <link rel="icon" type="image/x-icon" href="{{ asset($favicon) }}">
    @endif

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet" />

    {{-- Font Awesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

    {{-- Vite Assets (Alpine.js + Tailwind CSS) --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /**
         * Guest Layout Styles - สำหรับ Landing Pages
         *
         * Clean, minimal layout ไม่มี sidebar/navbar
         */

        /* Smooth scroll */
        html {
            scroll-behavior: smooth;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 10px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(139, 92, 246, 0.5);
            border-radius: 5px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: rgba(139, 92, 246, 0.7);
        }

        /* Dark mode scrollbar */
        .dark ::-webkit-scrollbar-thumb {
            background: rgba(139, 92, 246, 0.6);
        }

        .dark ::-webkit-scrollbar-thumb:hover {
            background: rgba(139, 92, 246, 0.8);
        }

        /* Prevent FOUC (Flash of Unstyled Content) */
        [x-cloak] {
            display: none !important;
        }
    </style>

    @stack('styles')
</head>
<body class="antialiased bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 transition-colors duration-300">

    {{-- Main Content (ไม่มี wrapper ใดๆ) --}}
    @yield('content')

    {{-- Scripts --}}
    @stack('scripts')

</body>
</html>
