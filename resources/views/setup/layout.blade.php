<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'ติดตั้งระบบ' }} - TP-Affiliate</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        .step-item {
            @apply relative flex flex-col items-center;
        }
        .step-circle {
            @apply w-10 h-10 rounded-full flex items-center justify-center font-bold z-10 transition-all duration-300;
        }
        .step-circle.active {
            @apply bg-indigo-600 text-white ring-4 ring-indigo-200;
        }
        .step-circle.completed {
            @apply bg-green-600 text-white;
        }
        .step-circle.pending {
            @apply bg-gray-200 text-gray-500;
        }
        .step-line {
            @apply absolute top-5 left-1/2 w-full h-0.5 bg-gray-200 -z-10;
        }
        .step-line.completed {
            @apply bg-green-600;
        }
        .progress-bar {
            @apply h-2 bg-indigo-600 transition-all duration-500 rounded-full;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-indigo-50 via-purple-50 to-pink-50 min-h-screen">
    <div class="min-h-screen py-8 px-4">
        <!-- Header -->
        <div class="max-w-4xl mx-auto mb-8">
            <div class="text-center">
                <h1 class="text-4xl font-bold text-indigo-600 mb-2">🚀 TP-Affiliate</h1>
                <p class="text-gray-600">Installation Wizard - ติดตั้งระบบอัตโนมัติ</p>
            </div>
        </div>

        @yield('content')

        <!-- Footer -->
        <div class="max-w-4xl mx-auto mt-8 text-center text-sm text-gray-600">
            <p>© {{ date('Y') }} TP-Affiliate. All rights reserved. | Version {{ config('version.current', '1.0.0') }}</p>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
