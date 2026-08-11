{{--
/**
 * หน้าเข้าสู่ระบบ - Premium Login Page
 *
 * ออกแบบใหม่ระดับ Premium:
 * - Firefly glowing effects
 * - 3D floating elements
 * - Glassmorphism card
 * - Smooth animations
 * - Responsive & fit screen
 *
 * @version 3.0.0
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

        // 🔖 โลโก้ต้องเป็นตัวเดียวกับทั้งเว็บ (ธีมปัจจุบัน) มาก่อนเสมอ
        //    เดิมอ่านจาก SiteSetting อย่างเดียว → หน้า login โชว์โลโก้เก่าที่ค้างอยู่ในตั้งค่านั้น
        //    ส่วนหน้าอื่นๆ (storefront/admin V4) ใช้ ThemeSetting.logo_path = คนละรูปกัน
        //    ลำดับ: โลโก้ธีม → โลโก้ใน SiteSetting → public/images/logo.png (สาขา @else ด้านล่าง)
        $logo = optional(\App\Models\ThemeSetting::active())->logo_path ?: $siteSettings->logo;
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

        /* ✨ Floating animation */
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            25% { transform: translateY(-8px) rotate(1deg); }
            75% { transform: translateY(-4px) rotate(-1deg); }
        }
        .animate-float {
            animation: float 6s ease-in-out infinite;
        }

        /* ✨ Pulse glow */
        @keyframes pulseGlow {
            0%, 100% { box-shadow: 0 0 30px rgba(99, 102, 241, 0.4), 0 0 60px rgba(139, 92, 246, 0.2); }
            50% { box-shadow: 0 0 50px rgba(99, 102, 241, 0.6), 0 0 80px rgba(139, 92, 246, 0.4); }
        }
        .animate-pulse-glow {
            animation: pulseGlow 3s ease-in-out infinite;
        }

        /* Fade in up animation */
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
        .animate-fade-in-up {
            animation: fadeInUp 0.8s ease-out;
        }

        /* ✨ Glass effect - Premium */
        .glass-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow:
                0 8px 32px rgba(0, 0, 0, 0.3),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }

        /* Input focus glow */
        .input-glow:focus {
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.3), 0 0 20px rgba(99, 102, 241, 0.2);
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
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.6s ease;
        }
        .btn-shine:hover::before {
            left: 100%;
        }

        /* ✨ Firefly Effect */
        .firefly {
            position: fixed;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            pointer-events: none;
            z-index: 1;
        }

        .firefly::before,
        .firefly::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            transform-origin: center center;
        }

        .firefly::before {
            background: radial-gradient(circle, rgba(129, 140, 248, 0.9) 0%, rgba(99, 102, 241, 0.6) 40%, transparent 70%);
            animation: fireflyGlow 2s ease-in-out infinite alternate;
        }

        .firefly::after {
            background: radial-gradient(circle, rgba(255, 255, 255, 1) 0%, rgba(199, 210, 254, 0.8) 30%, transparent 60%);
            width: 4px;
            height: 4px;
            left: 1px;
            top: 1px;
            animation: fireflyCore 1.5s ease-in-out infinite alternate;
        }

        @keyframes fireflyGlow {
            0% { transform: scale(1); opacity: 0.3; }
            100% { transform: scale(2.5); opacity: 0.8; }
        }

        @keyframes fireflyCore {
            0% { opacity: 0.8; }
            100% { opacity: 1; }
        }

        /* ✨ Gradient border animation */
        .gradient-border {
            position: relative;
        }
        .gradient-border::before {
            content: '';
            position: absolute;
            inset: -2px;
            border-radius: inherit;
            background: linear-gradient(45deg, #6366f1, #8b5cf6, #d946ef, #6366f1);
            background-size: 300% 300%;
            animation: gradientRotate 4s linear infinite;
            z-index: -1;
            opacity: 0.5;
        }
        @keyframes gradientRotate {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* ✨ Premium orb animation */
        @keyframes orbFloat {
            0%, 100% { transform: translate(0, 0) scale(1); }
            25% { transform: translate(20px, -30px) scale(1.05); }
            50% { transform: translate(-10px, 20px) scale(0.95); }
            75% { transform: translate(-20px, -10px) scale(1.02); }
        }
        .orb-animate {
            animation: orbFloat 15s ease-in-out infinite;
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-950 via-indigo-950 to-purple-950 relative overflow-x-hidden">
    {{-- ✨ Firefly Container --}}
    <div id="fireflies" class="fixed inset-0 pointer-events-none z-0 overflow-hidden"></div>

    {{-- Background Effects --}}
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        {{-- ภาพประกอบซุ้มลายกนกทอง (เจนเอง เก็บที่ public/images/art) --}}
        @if(file_exists(public_path('images/art/cos-auth.webp')))
            <img src="{{ asset('images/art/cos-auth.webp') }}" alt="" aria-hidden="true" loading="lazy" decoding="async"
                 class="absolute inset-0 w-full h-full object-cover"
                 style="opacity:.30;">
            <div class="absolute inset-0"
                 style="background:radial-gradient(110% 90% at 50% 45%, rgba(10,8,26,.30) 0%, rgba(10,8,26,.72) 62%, rgba(10,8,26,.94) 100%);"></div>
        @endif
        {{-- Grid Pattern --}}
        <div class="absolute inset-0 opacity-[0.015]"
             style="background-image: linear-gradient(rgba(255,255,255,.1) 1px, transparent 1px),
                                      linear-gradient(90deg, rgba(255,255,255,.1) 1px, transparent 1px);
                    background-size: 60px 60px;">
        </div>

        {{-- Premium Gradient Orbs --}}
        <div class="absolute top-0 -left-40 w-96 h-96 bg-indigo-600/30 rounded-full blur-3xl orb-animate"></div>
        <div class="absolute bottom-0 -right-40 w-96 h-96 bg-purple-600/25 rounded-full blur-3xl orb-animate" style="animation-delay: -5s;"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] bg-blue-500/10 rounded-full blur-3xl orb-animate" style="animation-delay: -10s;"></div>
        <div class="absolute top-1/4 right-1/4 w-64 h-64 bg-pink-500/15 rounded-full blur-3xl orb-animate" style="animation-delay: -3s;"></div>
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
                                <div class="absolute inset-0 bg-gradient-to-r from-blue-500 to-amber-400 rounded-2xl blur-xl opacity-40 group-hover:opacity-60 transition-opacity"></div>
                                <div class="relative bg-white rounded-2xl p-4 shadow-2xl animate-float">
                                    <img src="{{ asset('images/logo.png') }}" alt="{{ $appName }}" class="h-14 sm:h-16 w-auto">
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Title --}}
                    <h1 class="text-2xl sm:text-3xl font-bold text-white mb-2">{{ $appName }}</h1>
                    <p class="text-slate-400 text-sm sm:text-base">เข้าสู่ระบบเพื่อจัดการธุรกิจของคุณ</p>
                </div>

                {{-- Success Message --}}
                @if (session('success'))
                    <div class="mb-4 bg-green-500/20 border border-green-500/30 text-green-200 px-4 py-3 rounded-xl text-sm">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-check-circle"></i>
                            <span>{{ session('success') }}</span>
                        </div>
                    </div>
                @endif

                {{-- Info Message --}}
                @if (session('info'))
                    <div class="mb-4 bg-blue-500/20 border border-blue-500/30 text-blue-200 px-4 py-3 rounded-xl text-sm">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-info-circle"></i>
                            <span>{{ session('info') }}</span>
                        </div>
                    </div>
                @endif

                {{-- Error Message from Session --}}
                @if (session('error'))
                    <div class="mb-4 bg-red-500/20 border border-red-500/30 text-red-200 px-4 py-3 rounded-xl text-sm">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span>{{ session('error') }}</span>
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
                <form method="POST" action="{{ route('login') }}" class="space-y-4 sm:space-y-5" id="loginForm">
                    @csrf

                    {{-- แสดง error เมื่อ session/CSRF หมดอายุ --}}
                    @if ($errors->has('csrf') || $errors->has('turnstile'))
                    <div class="bg-amber-500/20 border border-amber-500/40 text-amber-200 px-4 py-3 rounded-xl text-sm">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-exclamation-triangle text-amber-400"></i>
                            <span>{{ $errors->first('csrf') ?: $errors->first('turnstile') }}</span>
                        </div>
                        <p class="mt-1 text-amber-300/70 text-xs">กรุณากดปุ่มเข้าสู่ระบบอีกครั้ง</p>
                    </div>
                    @endif

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
                    $showLineLogin = $lineSettings && $lineSettings->login_channel_id && $lineSettings->channel_secret;
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

                {{-- Facebook Login (Socialite OAuth) — config จาก DB ผ่าน FacebookOAuthSetting --}}
                @if(\App\Models\FacebookOAuthSetting::isConfigured())
                <div class="mt-3">
                    @if(!$showLineLogin)
                        <div class="relative flex items-center justify-center my-4">
                            <div class="border-t border-white/10 w-full"></div>
                            <span class="bg-transparent px-4 text-slate-500 text-sm absolute">หรือ</span>
                        </div>
                    @endif
                    <a href="{{ route('facebook.login') }}"
                       class="w-full flex items-center justify-center gap-3 px-4 py-3 bg-[#1877F2] hover:bg-[#1465D8] text-white font-semibold rounded-xl shadow-lg hover:shadow-blue-500/25 hover:-translate-y-0.5 transition-all duration-300">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                        เข้าสู่ระบบด้วย Facebook
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

    {{-- ✨ Firefly Effect Generator --}}
    <script>
        /**
         * สร้างเอฟเฟคหิ่งห้อยเรืองแสง
         *
         * หิ่งห้อยจะลอยขึ้นจากด้านล่างของหน้าจอ
         * พร้อมแสงเรืองแสงสีน้ำเงิน/ม่วงสวยงาม
         */
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById('fireflies');
            if (!container) return;

            // จำนวนหิ่งห้อย - ปรับตามขนาดหน้าจอ
            const isMobile = window.innerWidth < 768;
            const fireflyCount = isMobile ? 15 : 25;

            // สร้างหิ่งห้อยแต่ละตัว
            for (let i = 0; i < fireflyCount; i++) {
                createFirefly(container, i);
            }
        });

        /**
         * สร้างหิ่งห้อยตัวเดียว
         *
         * @param {HTMLElement} container - container element
         * @param {number} index - ลำดับหิ่งห้อย (สำหรับ delay)
         */
        function createFirefly(container, index) {
            const firefly = document.createElement('div');
            firefly.className = 'firefly';

            // สุ่มตำแหน่งเริ่มต้น
            const startX = Math.random() * 100;
            const startY = 100 + Math.random() * 20; // เริ่มจากด้านล่าง

            // ตั้งค่าตำแหน่งและ animation
            firefly.style.left = startX + 'vw';
            firefly.style.bottom = -startY + 'px';

            // สุ่มค่า animation
            const duration = 15 + Math.random() * 25; // 15-40 วินาที
            const delay = Math.random() * 10; // delay 0-10 วินาที
            const drift = -50 + Math.random() * 100; // เบนซ้าย-ขวา

            // สุ่มขนาด
            const scale = 0.6 + Math.random() * 0.8; // 0.6-1.4x
            firefly.style.transform = `scale(${scale})`;

            // สุ่มสี (หลายโทนสี)
            const colors = [
                'rgba(129, 140, 248, 0.9)', // indigo
                'rgba(167, 139, 250, 0.9)', // purple
                'rgba(96, 165, 250, 0.9)',  // blue
                'rgba(192, 132, 252, 0.9)', // violet
                'rgba(129, 230, 217, 0.8)', // teal
            ];
            const color = colors[Math.floor(Math.random() * colors.length)];

            // ตั้งค่า CSS Variables สำหรับ animation
            firefly.style.setProperty('--duration', duration + 's');
            firefly.style.setProperty('--delay', delay + 's');
            firefly.style.setProperty('--drift', drift + 'px');
            firefly.style.setProperty('--glow-color', color);

            // สร้าง keyframe animation
            const animationName = `fireflyFloat_${index}`;
            const keyframes = `
                @keyframes ${animationName} {
                    0% {
                        bottom: -20px;
                        left: ${startX}vw;
                        opacity: 0;
                    }
                    10% {
                        opacity: ${0.5 + Math.random() * 0.5};
                    }
                    30% {
                        left: calc(${startX}vw + ${drift * 0.3}px);
                    }
                    50% {
                        left: calc(${startX}vw + ${drift}px);
                    }
                    70% {
                        left: calc(${startX}vw + ${drift * 0.7}px);
                    }
                    90% {
                        opacity: ${0.3 + Math.random() * 0.4};
                    }
                    100% {
                        bottom: 110vh;
                        left: calc(${startX}vw + ${drift * 0.5}px);
                        opacity: 0;
                    }
                }
            `;

            // เพิ่ม keyframe ใหม่
            const styleSheet = document.createElement('style');
            styleSheet.textContent = keyframes;
            document.head.appendChild(styleSheet);

            // ใช้ animation
            firefly.style.animation = `${animationName} ${duration}s ${delay}s ease-in-out infinite`;

            // เพิ่ม glow effect
            firefly.style.boxShadow = `0 0 6px 2px ${color}, 0 0 12px 4px ${color.replace('0.9', '0.4')}`;

            container.appendChild(firefly);

            // เพิ่ม twinkle effect
            setInterval(() => {
                if (Math.random() > 0.7) {
                    firefly.style.opacity = 0.3 + Math.random() * 0.7;
                }
            }, 500 + Math.random() * 1000);
        }

        // ปรับจำนวนหิ่งห้อยเมื่อ resize
        let resizeTimeout;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(function() {
                const container = document.getElementById('fireflies');
                if (container) {
                    // ลบหิ่งห้อยเก่า
                    container.innerHTML = '';
                    // สร้างใหม่ตามขนาดหน้าจอ
                    const isMobile = window.innerWidth < 768;
                    const count = isMobile ? 15 : 25;
                    for (let i = 0; i < count; i++) {
                        createFirefly(container, i);
                    }
                }
            }, 500);
        });
    </script>

    {{-- CSRF Token Auto-Refresh สำหรับมือถือที่เปิดทิ้งไว้นาน --}}
    <script>
        (function() {
            var csrfRefreshInterval = 10 * 60 * 1000; // 10 นาที
            function refreshCsrfToken() {
                fetch(window.location.href, {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                }).then(function() {
                    var match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
                    if (match) {
                        var token = decodeURIComponent(match[1]);
                        var meta = document.querySelector('meta[name="csrf-token"]');
                        if (meta) meta.setAttribute('content', token);
                        var inputs = document.querySelectorAll('input[name="_token"]');
                        inputs.forEach(function(input) { input.value = token; });
                    }
                }).catch(function() {});
            }
            document.addEventListener('visibilitychange', function() {
                if (!document.hidden) refreshCsrfToken();
            });
            setInterval(refreshCsrfToken, csrfRefreshInterval);
        })();
    </script>
</body>
</html>
