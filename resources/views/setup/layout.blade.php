<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') - ThaiPrompt Marketplace Setup</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-gray-800 mb-2">ThaiPrompt Marketplace</h1>
            <p class="text-gray-600">ตัวช่วยติดตั้งระบบ</p>
        </div>

        <!-- Progress Steps -->
        <div class="max-w-4xl mx-auto mb-8">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <div class="flex items-center">
                        <div class="flex items-center text-white relative">
                            <div class="rounded-full transition duration-500 ease-in-out h-12 w-12 py-3 border-2 {{ request()->is('setup') || request()->is('setup/requirements*') || request()->is('setup/database*') || request()->is('setup/admin*') || request()->is('setup/complete*') ? 'bg-indigo-600 border-indigo-600' : 'border-gray-300 bg-white text-gray-500' }} flex items-center justify-center">
                                <i class="fas fa-check"></i>
                            </div>
                            <div class="absolute top-0 -ml-10 text-center mt-16 w-32 text-xs font-medium {{ request()->is('setup') ? 'text-indigo-600' : 'text-gray-500' }}">ยินดีต้อนรับ</div>
                        </div>
                        <div class="flex-auto border-t-2 transition duration-500 ease-in-out {{ request()->is('setup/requirements*') || request()->is('setup/database*') || request()->is('setup/admin*') || request()->is('setup/complete*') ? 'border-indigo-600' : 'border-gray-300' }}"></div>
                    </div>
                </div>
                <div class="flex-1">
                    <div class="flex items-center">
                        <div class="flex items-center text-white relative">
                            <div class="rounded-full transition duration-500 ease-in-out h-12 w-12 py-3 border-2 {{ request()->is('setup/requirements*') || request()->is('setup/database*') || request()->is('setup/admin*') || request()->is('setup/complete*') ? 'bg-indigo-600 border-indigo-600' : 'border-gray-300 bg-white text-gray-500' }} flex items-center justify-center">
                                <i class="fas fa-server"></i>
                            </div>
                            <div class="absolute top-0 -ml-10 text-center mt-16 w-32 text-xs font-medium {{ request()->is('setup/requirements*') ? 'text-indigo-600' : 'text-gray-500' }}">ตรวจสอบระบบ</div>
                        </div>
                        <div class="flex-auto border-t-2 transition duration-500 ease-in-out {{ request()->is('setup/database*') || request()->is('setup/admin*') || request()->is('setup/complete*') ? 'border-indigo-600' : 'border-gray-300' }}"></div>
                    </div>
                </div>
                <div class="flex-1">
                    <div class="flex items-center">
                        <div class="flex items-center text-white relative">
                            <div class="rounded-full transition duration-500 ease-in-out h-12 w-12 py-3 border-2 {{ request()->is('setup/database*') || request()->is('setup/admin*') || request()->is('setup/complete*') ? 'bg-indigo-600 border-indigo-600' : 'border-gray-300 bg-white text-gray-500' }} flex items-center justify-center">
                                <i class="fas fa-database"></i>
                            </div>
                            <div class="absolute top-0 -ml-10 text-center mt-16 w-32 text-xs font-medium {{ request()->is('setup/database*') ? 'text-indigo-600' : 'text-gray-500' }}">ฐานข้อมูล</div>
                        </div>
                        <div class="flex-auto border-t-2 transition duration-500 ease-in-out {{ request()->is('setup/admin*') || request()->is('setup/complete*') ? 'border-indigo-600' : 'border-gray-300' }}"></div>
                    </div>
                </div>
                <div class="flex-1">
                    <div class="flex items-center">
                        <div class="flex items-center text-white relative">
                            <div class="rounded-full transition duration-500 ease-in-out h-12 w-12 py-3 border-2 {{ request()->is('setup/admin*') || request()->is('setup/complete*') ? 'bg-indigo-600 border-indigo-600' : 'border-gray-300 bg-white text-gray-500' }} flex items-center justify-center">
                                <i class="fas fa-user-shield"></i>
                            </div>
                            <div class="absolute top-0 -ml-10 text-center mt-16 w-32 text-xs font-medium {{ request()->is('setup/admin*') ? 'text-indigo-600' : 'text-gray-500' }}">ผู้ดูแลระบบ</div>
                        </div>
                        <div class="flex-auto border-t-2 transition duration-500 ease-in-out {{ request()->is('setup/complete*') ? 'border-indigo-600' : 'border-gray-300' }}"></div>
                    </div>
                </div>
                <div>
                    <div class="flex items-center text-white relative">
                        <div class="rounded-full transition duration-500 ease-in-out h-12 w-12 py-3 border-2 {{ request()->is('setup/complete*') ? 'bg-indigo-600 border-indigo-600' : 'border-gray-300 bg-white text-gray-500' }} flex items-center justify-center">
                            <i class="fas fa-flag-checkered"></i>
                        </div>
                        <div class="absolute top-0 -ml-10 text-center mt-16 w-32 text-xs font-medium {{ request()->is('setup/complete*') ? 'text-indigo-600' : 'text-gray-500' }}">เสร็จสิ้น</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="max-w-3xl mx-auto">
            <div class="bg-white rounded-lg shadow-lg p-8">
                @yield('content')
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-8 text-gray-600 text-sm">
            <p>ThaiPrompt Marketplace v1.1.0</p>
            <p class="mt-2">ติดต่อสนับสนุน: support@thaiprompt.com</p>
        </div>
    </div>

    <script>
        // Setup CSRF token for all AJAX requests
        document.addEventListener('DOMContentLoaded', function() {
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            // Add CSRF token to all fetch requests
            const originalFetch = window.fetch;
            window.fetch = function(url, options = {}) {
                options.headers = options.headers || {};
                options.headers['X-CSRF-TOKEN'] = token;
                options.headers['Accept'] = 'application/json';
                options.headers['Content-Type'] = 'application/json';
                return originalFetch(url, options);
            };
        });
    </script>

    @stack('scripts')
</body>
</html>
