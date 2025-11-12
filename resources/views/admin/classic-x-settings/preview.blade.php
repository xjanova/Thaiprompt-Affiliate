<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Classic X Theme Preview</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>

    {{-- Dark Mode System --}}
    <x-dark-mode-init />
    <x-dark-mode-styles />
</head>
<body class="font-sans antialiased bg-gray-100 dark:bg-gray-900">
    <!-- Classic X Sidebar Preview -->
    <x-classic-x-sidebar type="admin" />

    <!-- Main Content Area -->
    <div class="classic-x-content" id="classicXContent">
        <!-- Top Bar -->
        <header class="bg-white dark:bg-gray-800 shadow-sm sticky top-0 z-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <h1 class="text-2xl md:text-3xl font-bold text-gray-800 dark:text-white">
                            Classic X Theme Preview
                        </h1>
                    </div>
                    <div class="flex items-center space-x-3">
                        <button onclick="window.close()" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-white rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                            <i class="fas fa-times mr-2"></i>
                            Close Preview
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Preview Content -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="space-y-6">
                <!-- Info Card -->
                <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-xl shadow-xl p-8 text-white">
                    <div class="flex items-center mb-4">
                        <i class="fas fa-palette text-5xl mr-4"></i>
                        <div>
                            <h2 class="text-3xl font-bold">Classic X Theme</h2>
                            <p class="text-blue-100">WordPress-Inspired Premium Design</p>
                        </div>
                    </div>
                    <p class="text-lg">
                        This is a live preview of your Classic X theme settings. Navigate through the menu items on the sidebar to test the functionality.
                    </p>
                </div>

                <!-- Features Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                        <div class="text-blue-500 text-4xl mb-4">
                            <i class="fas fa-paint-brush"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-2">Customizable</h3>
                        <p class="text-gray-600 dark:text-gray-400">
                            Over 30+ settings to customize colors, typography, and effects
                        </p>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                        <div class="text-purple-500 text-4xl mb-4">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-2">Responsive</h3>
                        <p class="text-gray-600 dark:text-gray-400">
                            Fully responsive design that works on all devices
                        </p>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                        <div class="text-green-500 text-4xl mb-4">
                            <i class="fas fa-magic"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-2">Premium Effects</h3>
                        <p class="text-gray-600 dark:text-gray-400">
                            3D depth, shadows, gradients, and smooth animations
                        </p>
                    </div>
                </div>

                <!-- Sample Content -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8">
                    <h3 class="text-2xl font-bold text-gray-800 dark:text-white mb-4">Sample Content</h3>
                    <div class="prose dark:prose-invert max-w-none">
                        <p class="text-gray-600 dark:text-gray-400 mb-4">
                            This preview demonstrates how your content will appear with the Classic X theme. The sidebar on the left provides easy navigation to all your admin features.
                        </p>
                        <h4 class="text-xl font-semibold text-gray-800 dark:text-white mb-3">Key Features:</h4>
                        <ul class="space-y-2 text-gray-600 dark:text-gray-400">
                            <li class="flex items-center">
                                <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                Collapsible sidebar with smooth animations
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                Multi-level menu support
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                Professional WordPress-inspired design
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                Dark mode compatible
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                Customizable colors and effects
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Action Card -->
                <div class="bg-gradient-to-r from-green-500 to-teal-500 rounded-xl shadow-xl p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-xl font-bold mb-2">Ready to Save?</h3>
                            <p class="text-green-100">Close this preview and save your settings to apply the theme.</p>
                        </div>
                        <button onclick="window.close()" class="px-6 py-3 bg-white text-green-600 rounded-lg hover:bg-green-50 transition-colors font-semibold">
                            <i class="fas fa-times mr-2"></i>
                            Close Preview
                        </button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Initialize sidebar state from parent window or default
        if (localStorage.getItem('classic-x-sidebar-collapsed') === 'true') {
            document.getElementById('classicXSidebar')?.classList.add('collapsed');
            document.getElementById('classicXContent')?.classList.add('sidebar-collapsed');
        }
    </script>
</body>
</html>
