<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Access Blocked - {{ config('app.name', 'TP-Affiliate') }}</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="antialiased bg-gradient-to-br from-red-50 to-red-100">
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="max-w-md w-full">
            <!-- Error Icon -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-24 h-24 bg-red-500 rounded-full mb-4">
                    <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                    </svg>
                </div>
                <h1 class="text-4xl font-bold text-red-600 mb-2">403</h1>
                <h2 class="text-2xl font-semibold text-gray-800 mb-4">Access Blocked</h2>
            </div>

            <!-- Error Message Card -->
            <div class="bg-white rounded-lg shadow-xl p-8 mb-6">
                <div class="text-center">
                    <p class="text-gray-700 mb-4">
                        {{ $message ?? 'Your IP address has been blocked from accessing this website.' }}
                    </p>

                    @if(isset($ip))
                    <div class="bg-gray-100 rounded-lg p-4 mb-4">
                        <p class="text-sm text-gray-600 mb-1">Your IP Address:</p>
                        <p class="text-lg font-mono font-semibold text-gray-900">{{ $ip }}</p>
                    </div>
                    @endif

                    @if(isset($reason))
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                        <p class="text-sm text-red-600 mb-1">Reason:</p>
                        <p class="text-gray-900">{{ $reason }}</p>
                    </div>
                    @endif

                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-6">
                        <p class="text-sm text-blue-800">
                            <strong>What can you do?</strong>
                        </p>
                        <ul class="text-left text-sm text-blue-700 mt-2 space-y-1">
                            <li class="flex items-start">
                                <span class="mr-2">•</span>
                                <span>If you believe this is a mistake, please contact the website administrator</span>
                            </li>
                            <li class="flex items-start">
                                <span class="mr-2">•</span>
                                <span>Check if you're using a VPN or proxy that might be blocked</span>
                            </li>
                            <li class="flex items-start">
                                <span class="mr-2">•</span>
                                <span>Wait for the block to expire if it's temporary</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="text-center">
                <p class="text-sm text-gray-600 mb-4">
                    Need help? Contact support
                </p>
                <a href="/" class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Return to Homepage
                </a>
            </div>

            <!-- Timestamp -->
            <div class="text-center mt-8">
                <p class="text-xs text-gray-500">
                    Blocked at: {{ now()->format('Y-m-d H:i:s T') }}
                </p>
                <p class="text-xs text-gray-500 mt-1">
                    Request ID: {{ Str::random(16) }}
                </p>
            </div>
        </div>
    </div>
</body>
</html>
