{{--
/**
 * หน้าเข้าสู่ระบบ Mobile App - Premium Login Page
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
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0F172A">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>เข้าสู่ระบบ - Thaiprompt Affiliate</title>

    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;500;600;700&display=swap" rel="stylesheet">

    {{-- Tailwind CDN - ใช้สำหรับ mobile เพื่อความเสถียร --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Kanit', 'sans-serif'],
                    },
                }
            }
        }
    </script>

    <style>
        body {
            font-family: 'Kanit', sans-serif;
            background: linear-gradient(135deg, #0F172A 0%, #1E1B4B 50%, #312E81 100%);
            min-height: 100vh;
            overflow-x: hidden;
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
            border-color: #6366f1;
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

        .firefly::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(129, 140, 248, 0.9) 0%, rgba(99, 102, 241, 0.6) 40%, transparent 70%);
            animation: fireflyGlow 2s ease-in-out infinite alternate;
        }

        @keyframes fireflyGlow {
            0% { transform: scale(1); opacity: 0.3; }
            100% { transform: scale(2.5); opacity: 0.8; }
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

        /* LINE Button special style */
        .line-btn {
            background: linear-gradient(135deg, #00B900 0%, #00E676 100%);
            box-shadow: 0 8px 30px rgba(0, 185, 0, 0.4);
            transition: all 0.3s ease;
        }
        .line-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 40px rgba(0, 185, 0, 0.5);
        }
        .line-btn:active {
            transform: scale(0.98);
        }

        /* Gradient border animation */
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
    </style>
</head>
<body class="text-white">
    {{-- ✨ Firefly Container --}}
    <div id="fireflies" class="fixed inset-0 pointer-events-none z-0 overflow-hidden"></div>

    {{-- Background Effects --}}
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        {{-- Grid Pattern --}}
        <div class="absolute inset-0 opacity-[0.02]"
             style="background-image: linear-gradient(rgba(255,255,255,.1) 1px, transparent 1px),
                                      linear-gradient(90deg, rgba(255,255,255,.1) 1px, transparent 1px);
                    background-size: 60px 60px;">
        </div>

        {{-- Premium Gradient Orbs --}}
        <div class="absolute top-0 -left-32 w-72 h-72 bg-indigo-600/30 rounded-full blur-3xl orb-animate"></div>
        <div class="absolute bottom-0 -right-32 w-72 h-72 bg-purple-600/25 rounded-full blur-3xl orb-animate" style="animation-delay: -5s;"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-blue-500/10 rounded-full blur-3xl orb-animate" style="animation-delay: -10s;"></div>
    </div>

    {{-- Main Content --}}
    <div class="relative z-10 min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-md animate-fade-in-up">

            {{-- Logo & Header --}}
            <div class="text-center mb-6">
                {{-- Logo with glow --}}
                <div class="flex justify-center mb-4">
                    <div class="relative group">
                        {{-- Glow Effect --}}
                        <div class="absolute inset-0 bg-gradient-to-r from-blue-500 via-purple-500 to-cyan-500 rounded-2xl blur-xl opacity-50 group-hover:opacity-70 transition-opacity animate-pulse-glow"></div>
                        {{-- Logo Box --}}
                        <div class="relative w-20 h-20 bg-gradient-to-br from-blue-600 via-purple-600 to-pink-500 rounded-2xl flex items-center justify-center shadow-2xl animate-float">
                            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <h1 class="text-2xl font-bold text-white mb-1">เข้าสู่ระบบ</h1>
                <p class="text-slate-400 text-sm">เพื่อเชื่อมต่อกับแอพ Thaiprompt</p>

                {{-- Device Badge --}}
                @if($deviceName)
                    <div class="mt-3 inline-flex items-center gap-2 px-4 py-2 rounded-full bg-blue-500/20 border border-blue-500/30 text-blue-300 text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                        <span class="font-medium">{{ $deviceName }}</span>
                    </div>
                @endif
            </div>

            {{-- Login Card --}}
            <div class="glass-card rounded-2xl p-6 shadow-2xl">

                @php
                    // ตรวจสอบว่า LINE Login ถูกตั้งค่าแล้วหรือไม่
                    $lineSettings = \App\Models\LineOaSetting::getActive();
                    $showLineLogin = $lineSettings && $lineSettings->login_channel_id && $lineSettings->channel_secret;
                @endphp

                {{-- Success Message --}}
                @if(session('success'))
                    <div class="mb-4 p-4 rounded-xl bg-green-500/20 border border-green-500/30 animate-fade-in-up">
                        <div class="flex items-center gap-3 text-green-400">
                            <div class="w-8 h-8 rounded-full bg-green-500/30 flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <span class="font-medium">{{ session('success') }}</span>
                        </div>
                    </div>
                @endif

                {{-- Error Message --}}
                @if(session('error'))
                    <div class="mb-4 p-4 rounded-xl bg-red-500/20 border border-red-500/30 animate-fade-in-up">
                        <div class="flex items-center gap-3 text-red-400">
                            <div class="w-8 h-8 rounded-full bg-red-500/30 flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <span class="font-medium">{{ session('error') }}</span>
                        </div>
                    </div>
                @endif

                @if($errors->any())
                    <div class="mb-4 p-4 rounded-xl bg-red-500/20 border border-red-500/30 animate-fade-in-up">
                        <div class="flex items-center gap-3 text-red-400">
                            <div class="w-8 h-8 rounded-full bg-red-500/30 flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <span class="font-medium">{{ $errors->first() }}</span>
                        </div>
                    </div>
                @endif

                {{-- ⭐ LINE Login - แสดงเมื่อตั้งค่าแล้ว --}}
                @if($showLineLogin)
                <div class="mb-6">
                    <a href="{{ route('line.login', ['mobile_token' => $token, 'state' => $state]) }}"
                       class="line-btn w-full flex items-center justify-center gap-3 py-4 px-6 text-white font-bold rounded-xl text-lg">
                        <svg class="w-7 h-7" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                        </svg>
                        เข้าสู่ระบบด้วย LINE
                    </a>
                    <p class="text-center text-slate-500 text-xs mt-2">✨ แนะนำ - เร็วและปลอดภัย</p>
                </div>

                {{-- Divider --}}
                <div class="relative my-6">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-white/10"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-4 bg-slate-900/50 text-slate-500 rounded-full">หรือใช้อีเมล</span>
                    </div>
                </div>
                @endif

                {{-- Email Login Form --}}
                <form method="POST" action="{{ route('mobile-login.login') }}" id="loginForm">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <input type="hidden" name="state" value="{{ $state }}">

                    {{-- Email --}}
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-slate-300 mb-2">
                            <svg class="w-4 h-4 inline mr-1 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            อีเมล
                        </label>
                        <input type="email" name="email" value="{{ old('email') }}" required autofocus
                               class="input-glow w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white placeholder-slate-500 focus:outline-none transition-all"
                               placeholder="example@email.com">
                    </div>

                    {{-- Password --}}
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-slate-300 mb-2">
                            <svg class="w-4 h-4 inline mr-1 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                            รหัสผ่าน
                        </label>
                        <div class="relative">
                            <input type="password" name="password" id="passwordInput" required
                                   class="input-glow w-full px-4 py-3 pr-12 bg-white/5 border border-white/10 rounded-xl text-white placeholder-slate-500 focus:outline-none transition-all"
                                   placeholder="••••••••">
                            <button type="button" onclick="togglePassword()"
                                    class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white transition-colors">
                                <svg id="eyeIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Submit Button --}}
                    <button type="submit" id="submitBtn"
                            class="btn-shine w-full py-3.5 px-4 bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 hover:from-blue-500 hover:via-purple-500 hover:to-pink-500 text-white font-bold rounded-xl shadow-lg hover:shadow-purple-500/25 transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span id="btnText" class="flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                            </svg>
                            เข้าสู่ระบบ
                        </span>
                        <span id="btnLoading" class="hidden flex items-center justify-center gap-2">
                            <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            กำลังเข้าสู่ระบบ...
                        </span>
                    </button>
                </form>
            </div>

            {{-- Security Notice --}}
            <div class="mt-6 text-center">
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/5 border border-white/10">
                    <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                    <span class="text-slate-400 text-sm">การเชื่อมต่อนี้ปลอดภัยด้วย HTTPS</span>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        function togglePassword() {
            var input = document.getElementById('passwordInput');
            var icon = document.getElementById('eyeIcon');

            if (input.type === 'password') {
                input.type = 'text';
                icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>';
            } else {
                input.type = 'password';
                icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>';
            }
        }

        // แสดง loading state เมื่อกด submit
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            var btn = document.getElementById('submitBtn');
            var btnText = document.getElementById('btnText');
            var btnLoading = document.getElementById('btnLoading');

            btn.disabled = true;
            btnText.classList.add('hidden');
            btnLoading.classList.remove('hidden');
        });

        // ✨ Firefly Effect Generator - Mobile Optimized
        document.addEventListener('DOMContentLoaded', function() {
            var container = document.getElementById('fireflies');
            if (!container) return;

            // จำนวนหิ่งห้อย - ลดลงสำหรับ mobile เพื่อประสิทธิภาพ
            var isMobile = window.innerWidth < 768;
            var fireflyCount = isMobile ? 12 : 20;

            // สร้างหิ่งห้อยแต่ละตัว
            for (var i = 0; i < fireflyCount; i++) {
                createFirefly(container, i);
            }
        });

        function createFirefly(container, index) {
            var firefly = document.createElement('div');
            firefly.className = 'firefly';

            // สุ่มตำแหน่งเริ่มต้น
            var startX = Math.random() * 100;
            firefly.style.left = startX + 'vw';
            firefly.style.bottom = '-20px';

            // สุ่มค่า animation
            var duration = 12 + Math.random() * 18;
            var delay = Math.random() * 8;
            var drift = -40 + Math.random() * 80;
            var scale = 0.5 + Math.random() * 0.7;

            firefly.style.transform = 'scale(' + scale + ')';

            // สุ่มสี
            var colors = [
                'rgba(129, 140, 248, 0.9)',
                'rgba(167, 139, 250, 0.9)',
                'rgba(96, 165, 250, 0.9)',
                'rgba(192, 132, 252, 0.9)',
            ];
            var color = colors[Math.floor(Math.random() * colors.length)];
            firefly.style.boxShadow = '0 0 6px 2px ' + color + ', 0 0 12px 4px ' + color.replace('0.9', '0.4');

            // สร้าง animation keyframes
            var animationName = 'fireflyFloat_' + index;
            var keyframes = '@keyframes ' + animationName + ' { ' +
                '0% { bottom: -20px; left: ' + startX + 'vw; opacity: 0; } ' +
                '10% { opacity: ' + (0.4 + Math.random() * 0.4) + '; } ' +
                '50% { left: calc(' + startX + 'vw + ' + drift + 'px); } ' +
                '90% { opacity: ' + (0.2 + Math.random() * 0.3) + '; } ' +
                '100% { bottom: 110vh; opacity: 0; } ' +
            '}';

            var styleSheet = document.createElement('style');
            styleSheet.textContent = keyframes;
            document.head.appendChild(styleSheet);

            firefly.style.animation = animationName + ' ' + duration + 's ' + delay + 's ease-in-out infinite';

            container.appendChild(firefly);
        }
    </script>
</body>
</html>
