<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'V3 UI Demo') - Thaiprompt Affiliate</title>

    {{-- Tailwind CSS --}}
    @vite(['resources/css/app.css'])

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Noto+Sans+Thai:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    {{-- Font Awesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        /* Custom Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes scaleIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }

        @keyframes shimmer {
            0% { background-position: -1000px 0; }
            100% { background-position: 1000px 0; }
        }

        .animate-fade-in { animation: fadeIn 0.5s ease-out; }
        .animate-slide-up { animation: slideUp 0.6s ease-out; }
        .animate-scale-in { animation: scaleIn 0.3s ease-out; }

        /* 3D Perspective */
        .perspective-1000 { perspective: 1000px; }
        .rotate-y-2 { transform: rotateY(2deg); }
        .rotate-y-3 { transform: rotateY(3deg); }

        /* Glassmorphism */
        .glass {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        /* Dark mode glassmorphism */
        .dark .glass {
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Neumorphism Light */
        .neu-light {
            background: #e0e5ec;
            box-shadow: 8px 8px 16px #c5cad0, -8px -8px 16px #fbffff;
        }

        .neu-light:hover {
            box-shadow: inset 8px 8px 16px #c5cad0, inset -8px -8px 16px #fbffff;
        }

        /* Neumorphism Dark */
        .dark .neu-dark {
            background: #2d3748;
            box-shadow: 8px 8px 16px #1a202c, -8px -8px 16px #404d64;
        }

        .dark .neu-dark:hover {
            box-shadow: inset 8px 8px 16px #1a202c, inset -8px -8px 16px #404d64;
        }
    </style>

    @stack('styles')
</head>
<body class="bg-gray-50 dark:bg-gray-900 font-sans antialiased">

    {{-- Navigation Bar --}}
    <nav class="fixed top-0 left-0 right-0 z-50 bg-white/80 dark:bg-gray-800/80 backdrop-blur-lg border-b border-gray-200 dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                {{-- Logo --}}
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-rocket text-white text-lg"></i>
                    </div>
                    <div>
                        <h1 class="text-lg font-bold text-gray-900 dark:text-white">V3 UI Demo</h1>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Thaiprompt Affiliate</p>
                    </div>
                </div>

                {{-- Theme Navigation --}}
                <div class="hidden md:flex items-center gap-4">
                    <a href="{{ route('demo.index') }}"
                       class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                        <i class="fas fa-home mr-2"></i>
                        หน้าแรก
                    </a>
                    <a href="{{ route('demo.theme1') }}"
                       class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                        Theme 1
                    </a>
                    <a href="{{ route('demo.theme2') }}"
                       class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                        Theme 2
                    </a>
                    <a href="{{ route('demo.theme3') }}"
                       class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                        Theme 3
                    </a>
                </div>

                {{-- Dark Mode Toggle --}}
                <button x-data="{ dark: localStorage.getItem('darkMode') === 'true' }"
                        x-init="$watch('dark', val => { localStorage.setItem('darkMode', val); document.documentElement.classList.toggle('dark', val); }); document.documentElement.classList.toggle('dark', dark);"
                        @click="dark = !dark"
                        class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                    <i class="fas fa-moon" x-show="!dark"></i>
                    <i class="fas fa-sun" x-show="dark" style="display: none;"></i>
                </button>
            </div>
        </div>
    </nav>

    {{-- Main Content --}}
    <main class="pt-20 min-h-screen">
        @yield('content')
    </main>

    {{-- Alpine.js --}}
    @vite(['resources/js/app.js'])

    @stack('scripts')

</body>
</html>
