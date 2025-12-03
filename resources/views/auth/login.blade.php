{{--
/**
 * หน้าเข้าสู่ระบบ - Modern Login Page
 *
 * ออกแบบใหม่ให้สอดคล้องกับธีม V3:
 * - Deep Blue + Purple gradient background
 * - Glassmorphism card
 * - Smooth animations
 * - Responsive & fit screen
 *
 * @version 2.0.0
 * @author Thaiprompt Team
 */
--}}
<!DOCTYPE html>
<html lang="th" x-data="{ darkMode: true }" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        // ดึงข้อมูลจาก SiteSetting (ที่แอดมินตั้งค่า)
        $siteSettings = \App\Models\SiteSetting::getSetting();
        $appName = $siteSettings->site_name ?? 'TP-Affiliate Pro';
        $logo = $siteSettings->logo;
        $favicon = $siteSettings->favicon;
    @endphp

    <title>เข้าสู่ระบบ - {{ $appName }}</title>

    @if($favicon)
        <link rel="icon" type="image/x-icon" href="{{ asset('storage/' . $favicon) }}">
    @endif

    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Kanit:wght@400;500;600;700&display=swap" rel="stylesheet">

    {{-- Font Awesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />

    {{-- Vite Assets --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @if(config('turnstile.enabled') && config('turnstile.points.login'))
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endif

    <style>
        body {
            font-family: 'Kanit', 'Inter', sans-serif;
        }

        /* Floating animation */
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        .animate-float {
            animation: float 4s ease-in-out infinite;
        }

        /* Fade in up animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .animate-fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }

        /* Glass effect */
        .glass-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Input focus glow */
        .input-glow:focus {
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.3);
        }

        /* Button shine effect */
        .btn-shine {
            position: relative;
            overflow: hidden;
        }
        .btn-shine::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        .btn-shine:hover::before {
            left: 100%;
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-blue-950 to-purple-950 relative overflow-x-hidden">
    {{-- Background Effects --}}
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        {{-- Grid Pattern --}}
        <div class="absolute inset-0 opacity-[0.02]"
             style="background-image: linear-gradient(rgba(255,255,255,.1) 1px, transparent 1px),
                                      linear-gradient(90deg, rgba(255,255,255,.1) 1px, transparent 1px);
                    background-size: 50px 50px;">
        </div>

        {{-- Gradient Orbs --}}
        <div class="absolute top-0 -left-40 w-80 h-80 bg-blue-600/20 rounded-full blur-3xl"></div>
        <div class="absolute bottom-0 -right-40 w-80 h-80 bg-purple-600/20 rounded-full blur-3xl"></div>
    </div>

    {{-- Main Content --}}
    <div class="relative z-10 min-h-screen flex items-center justify-center p-4 sm:p-6">
        <div class="w-full max-w-md animate-fade-in-up">

            {{-- Login Card --}}
            <div class="glass-card rounded-2xl sm:rounded-3xl shadow-2xl p-6 sm:p-8">

                {{-- Logo & Header --}}
                <div class="text-center mb-6 sm:mb-8">
                    {{-- Logo --}}
                    <div class="flex justify-center mb-4">
                        @if($logo)
                            <div class="relative group">
                                {{-- Glow Effect --}}
                                <div class="absolute inset-0 bg-gradient-to-r from-blue-500 to-purple-500 rounded-2xl blur-xl opacity-40 group-hover:opacity-60 transition-opacity"></div>
                                {{-- Logo Image --}}
                                <div class="relative bg-white/10 backdrop-blur-sm rounded-2xl p-4 border border-white/20">
                                    <img src="{{ asset('storage/' . $logo) }}"
                                         alt="{{ $appName }}"
                                         class="h-16 sm:h-20 w-auto animate-float">
                                </div>
                            </div>
                        @else
                            <div class="relative group">
                                <div class="absolute inset-0 bg-gradient-to-r from-blue-500 to-purple-500 rounded-2xl blur-xl opacity-50"></div>
                                <div class="relative w-20 h-20 bg-gradient-to-br from-blue-600 via-purple-600 to-pink-500 rounded-2xl flex items-center justify-center shadow-2xl animate-float">
                                    <span class="text-white font-black text-3xl">TP</span>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Title --}}
                    <h1 class="text-2xl sm:text-3xl font-bold text-white mb-2">{{ $appName }}</h1>
                    <p class="text-slate-400 text-sm sm:text-base">เข้าสู่ระบบเพื่อจัดการธุรกิจของคุณ</p>
                </div>

                {{-- Info Message --}}
                @if (session('info'))
                    <div class="mb-4 bg-blue-500/20 border border-blue-500/30 text-blue-200 px-4 py-3 rounded-xl text-sm">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-info-circle"></i>
                            <span>{{ session('info') }}</span>
                        </div>
                    </div>
                @endif

                {{-- Error Messages --}}
                @if ($errors->any())
                    <div class="mb-4 bg-red-500/20 border border-red-500/30 text-red-200 px-4 py-3 rounded-xl text-sm">
                        <div class="flex items-start gap-2">
                            <i class="fas fa-exclamation-circle mt-0.5"></i>
                            <ul class="space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                {{-- Login Form --}}
                <form method="POST" action="{{ route('login') }}" class="space-y-4 sm:space-y-5">
                    @csrf

                    {{-- Email Field --}}
                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-300 mb-2">
                            <i class="fas fa-envelope mr-2 text-blue-400"></i>อีเมล
                        </label>
                        <input type="email"
                               name="email"
                               id="email"
                               value="{{ old('email') }}"
                               class="input-glow w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white placeholder-slate-500 focus:border-blue-500 focus:outline-none transition-all"
                               placeholder="your@email.com"
                               required
                               autofocus>
                    </div>

                    {{-- Password Field --}}
                    <div x-data="{ showPassword: false }">
                        <label for="password" class="block text-sm font-medium text-slate-300 mb-2">
                            <i class="fas fa-lock mr-2 text-purple-400"></i>รหัสผ่าน
                        </label>
                        <div class="relative">
                            <input :type="showPassword ? 'text' : 'password'"
                                   name="password"
                                   id="password"
                                   class="input-glow w-full px-4 py-3 pr-12 bg-white/5 border border-white/10 rounded-xl text-white placeholder-slate-500 focus:border-blue-500 focus:outline-none transition-all"
                                   placeholder="••••••••"
                                   required>
                            <button type="button"
                                    @click="showPassword = !showPassword"
                                    class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white transition-colors">
                                <i class="fas" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                        </div>
                    </div>

                    {{-- Remember Me --}}
                    <div class="flex items-center justify-between">
                        <label class="flex items-center cursor-pointer group">
                            <input type="checkbox"
                                   name="remember"
                                   class="w-4 h-4 rounded border-slate-600 bg-white/5 text-blue-500 focus:ring-blue-500 focus:ring-offset-0">
                            <span class="ml-2 text-sm text-slate-400 group-hover:text-slate-300 transition-colors">จดจำฉัน</span>
                        </label>
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="text-sm text-blue-400 hover:text-blue-300 transition-colors">
                                ลืมรหัสผ่าน?
                            </a>
                        @endif
                    </div>

                    {{-- Turnstile --}}
                    @if(config('turnstile.enabled') && config('turnstile.points.login'))
                    <div class="flex justify-center">
                        <div class="cf-turnstile"
                             data-sitekey="{{ config('turnstile.site_key') }}"
                             data-theme="dark"
                             data-size="{{ config('turnstile.size') }}">
                        </div>
                    </div>
                    @endif

                    {{-- Submit Button --}}
                    <button type="submit"
                            class="btn-shine w-full bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 hover:from-blue-500 hover:via-purple-500 hover:to-pink-500 text-white py-3 sm:py-3.5 rounded-xl font-semibold text-base shadow-lg hover:shadow-purple-500/25 hover:-translate-y-0.5 transition-all duration-300">
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        เข้าสู่ระบบ
                    </button>
                </form>

                {{-- LINE Login --}}
                @php
                    $lineSettings = \App\Models\LineOaSetting::getActive();
                    $showLineLogin = $lineSettings && $lineSettings->channel_id && $lineSettings->channel_secret;
                @endphp

                @if($showLineLogin)
                <div class="mt-5 sm:mt-6">
                    <div class="relative flex items-center justify-center my-4">
                        <div class="border-t border-white/10 w-full"></div>
                        <span class="bg-transparent px-4 text-slate-500 text-sm absolute">หรือ</span>
                    </div>

                    <a href="{{ route('line.login') }}"
                       class="w-full flex items-center justify-center gap-3 px-4 py-3 bg-[#06C755] hover:bg-[#05B34D] text-white font-semibold rounded-xl shadow-lg hover:shadow-green-500/25 hover:-translate-y-0.5 transition-all duration-300">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                        </svg>
                        เข้าสู่ระบบด้วย LINE
                    </a>
                </div>
                @endif

                {{-- Register Link --}}
                <div class="mt-6 pt-5 border-t border-white/10 text-center">
                    <p class="text-slate-400 text-sm">
                        ยังไม่มีบัญชี?
                        <a href="{{ route('register') }}" class="text-blue-400 hover:text-blue-300 font-semibold ml-1 transition-colors">
                            สมัครสมาชิก
                            <i class="fas fa-arrow-right ml-1 text-xs"></i>
                        </a>
                    </p>
                </div>
            </div>

            {{-- Back to Home --}}
            <div class="mt-6 text-center">
                <a href="{{ route('home') }}"
                   class="inline-flex items-center gap-2 px-5 py-2.5 bg-white/10 hover:bg-white/20 backdrop-blur-sm text-white/80 hover:text-white text-sm font-medium rounded-xl transition-all duration-300">
                    <i class="fas fa-arrow-left"></i>
                    กลับหน้าแรก
                </a>
            </div>

            {{-- Security Badge --}}
            <div class="mt-4 text-center">
                <p class="text-slate-500 text-xs flex items-center justify-center gap-2">
                    <i class="fas fa-shield-alt text-green-500"></i>
                    ระบบปลอดภัยด้วยการเข้ารหัส SSL
                </p>
            </div>
        </div>
    </div>

    {{-- Alpine.js --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html>
