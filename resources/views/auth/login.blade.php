<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $appName = \App\Models\Setting::get('app_name', 'TP-Affiliate');
        $logo = \App\Models\Setting::get('logo');
        $logoUrl = $logo ? asset($logo) : asset('images/logo.svg');
    @endphp
    <title>เข้าสู่ระบบ - {{ $appName }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @if(config('turnstile.enabled') && config('turnstile.points.login'))
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endif
    <style>
        /* Animated gradient background */
        @keyframes gradient {
            0% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
            100% {
                background-position: 0% 50%;
            }
        }

        .animated-gradient {
            background: linear-gradient(-45deg, #667eea, #764ba2, #f093fb, #4facfe);
            background-size: 400% 400%;
            animation: gradient 15s ease infinite;
        }

        /* Floating animation */
        @keyframes float {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-20px);
            }
        }

        .float-animation {
            animation: float 6s ease-in-out infinite;
        }

        /* Fade in animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in-up {
            animation: fadeInUp 0.8s ease-out;
        }

        /* Glass morphism effect */
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        /* Glow effect for logo */
        @keyframes glow {
            0%, 100% {
                filter: drop-shadow(0 0 20px rgba(102, 126, 234, 0.5));
            }
            50% {
                filter: drop-shadow(0 0 30px rgba(118, 75, 162, 0.8));
            }
        }

        .logo-glow {
            animation: glow 3s ease-in-out infinite;
        }

        /* Premium input styling */
        .premium-input {
            transition: all 0.3s ease;
        }

        .premium-input:focus {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.2);
        }

        /* Button hover effect */
        .premium-button {
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .premium-button:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
        }

        .premium-button:hover:before {
            left: 100%;
        }

        .premium-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }

        /* Floating particles */
        @keyframes float-particle {
            0%, 100% {
                transform: translate(0, 0) rotate(0deg);
                opacity: 0.5;
            }
            33% {
                transform: translate(30px, -30px) rotate(120deg);
                opacity: 0.8;
            }
            66% {
                transform: translate(-20px, 20px) rotate(240deg);
                opacity: 0.6;
            }
        }

        .particle {
            position: absolute;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            animation: float-particle 10s infinite;
        }
    </style>
</head>
<body class="animated-gradient min-h-screen relative overflow-hidden">
    <!-- Decorative particles -->
    <div class="particle" style="width: 100px; height: 100px; top: 10%; left: 10%; animation-delay: 0s;"></div>
    <div class="particle" style="width: 60px; height: 60px; top: 70%; left: 80%; animation-delay: 2s;"></div>
    <div class="particle" style="width: 80px; height: 80px; top: 40%; left: 5%; animation-delay: 4s;"></div>
    <div class="particle" style="width: 120px; height: 120px; top: 60%; left: 90%; animation-delay: 6s;"></div>

    <div class="min-h-screen flex items-center justify-center px-4 relative z-10">
        <div class="max-w-md w-full fade-in-up">
            <div class="glass-effect rounded-2xl shadow-2xl p-10">
                <!-- Logo Section -->
                <div class="text-center mb-8">
                    <div class="flex justify-center mb-6">
                        <img src="{{ $logoUrl }}"
                             alt="{{ $appName }} Logo"
                             class="w-32 h-32 logo-glow float-animation object-contain">
                    </div>
                    <h1 class="text-5xl font-extrabold bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 bg-clip-text text-transparent mb-3">
                        {{ $appName }}
                    </h1>
                    <p class="text-gray-600 text-lg font-medium">ระบบบริหารจัดการพันธมิตร</p>
                    <div class="w-24 h-1 bg-gradient-to-r from-indigo-600 to-purple-600 mx-auto mt-4 rounded-full"></div>
                </div>

                @if (session('info'))
                    <div class="mb-6 bg-gradient-to-r from-blue-50 to-indigo-50 border-l-4 border-blue-500 text-blue-800 px-5 py-4 rounded-lg shadow-md">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                            <span class="font-medium">{{ session('info') }}</span>
                        </div>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-6 bg-gradient-to-r from-red-50 to-pink-50 border-l-4 border-red-500 text-red-800 px-5 py-4 rounded-lg shadow-md">
                        <div class="flex items-start">
                            <svg class="w-5 h-5 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            <ul class="list-disc list-inside space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li class="font-medium">{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="space-y-6">
                    @csrf

                    <div>
                        <label for="email" class="block text-sm font-bold text-gray-700 mb-2">
                            <span class="flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                                    <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                                </svg>
                                อีเมล
                            </span>
                        </label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}"
                               class="premium-input w-full px-5 py-3 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-gray-700 font-medium shadow-sm"
                               placeholder="example@email.com"
                               required autofocus>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-bold text-gray-700 mb-2">
                            <span class="flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                                </svg>
                                รหัสผ่าน
                            </span>
                        </label>
                        <input type="password" name="password" id="password"
                               class="premium-input w-full px-5 py-3 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-gray-700 font-medium shadow-sm"
                               placeholder="••••••••"
                               required>
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center cursor-pointer group">
                            <input type="checkbox" name="remember"
                                   class="w-5 h-5 rounded border-2 border-gray-300 text-indigo-600 focus:ring-2 focus:ring-indigo-500 transition">
                            <span class="ml-3 text-sm font-medium text-gray-700 group-hover:text-indigo-600 transition">จดจำฉัน</span>
                        </label>
                    </div>

                    @if(config('turnstile.enabled') && config('turnstile.points.login'))
                    <div class="flex justify-center">
                        <div class="cf-turnstile"
                             data-sitekey="{{ config('turnstile.site_key') }}"
                             data-theme="{{ config('turnstile.theme') }}"
                             data-size="{{ config('turnstile.size') }}">
                        </div>
                    </div>
                    @endif

                    <button type="submit"
                            class="premium-button w-full bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 text-white py-4 rounded-xl font-bold text-lg shadow-lg hover:shadow-2xl transition duration-300">
                        <span class="flex items-center justify-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                            </svg>
                            เข้าสู่ระบบ
                        </span>
                    </button>
                </form>

                <div class="mt-8 pt-6 border-t border-gray-200">
                    <div class="text-center">
                        <a href="{{ route('register') }}"
                           class="inline-flex items-center text-indigo-600 hover:text-purple-600 font-semibold transition duration-300 group">
                            <span class="mr-2">ยังไม่มีบัญชี?</span>
                            <span class="underline">สมัครสมาชิก</span>
                            <svg class="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Back to Home Link -->
            <div class="mt-6 text-center">
                <a href="{{ route('home') }}"
                   class="inline-flex items-center px-6 py-3 bg-white/20 backdrop-blur-md text-white font-semibold rounded-xl hover:bg-white/30 transition duration-300 shadow-lg group">
                    <svg class="w-5 h-5 mr-2 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    กลับหน้าแรก
                </a>
            </div>

            <!-- Footer Text -->
            <div class="mt-8 text-center">
                <p class="text-white/80 text-sm font-medium">
                    ระบบปลอดภัยด้วยการเข้ารหัสระดับสูง 🔒
                </p>
            </div>
        </div>
    </div>
</body>
</html>
