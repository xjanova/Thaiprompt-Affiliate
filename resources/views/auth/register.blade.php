{{--
/**
 * หน้าสมัครสมาชิก - Modern Register Page
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

    <title>สมัครสมาชิก - {{ $appName }}</title>

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

    @if(config('turnstile.enabled') && config('turnstile.points.register'))
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

        /* Pulse animation */
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .animate-pulse-slow {
            animation: pulse 2s ease-in-out infinite;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
        }
        ::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.1);
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.2);
            border-radius: 3px;
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
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-pink-600/10 rounded-full blur-3xl"></div>
    </div>

    {{-- Main Content --}}
    <div class="relative z-10 min-h-screen flex items-center justify-center p-4 py-8">
        <div class="w-full max-w-6xl animate-fade-in-up">
            <div class="grid lg:grid-cols-5 gap-6">

                {{-- Left Side - Registration Form (3 cols) --}}
                <div class="lg:col-span-3">
                    <div class="glass-card rounded-2xl sm:rounded-3xl shadow-2xl p-6 sm:p-8">

                        {{-- Logo & Header --}}
                        <div class="text-center mb-6">
                            {{-- Logo --}}
                            <div class="flex justify-center mb-4">
                                @if($logo)
                                    <div class="relative group">
                                        <div class="absolute inset-0 bg-gradient-to-r from-blue-500 to-purple-500 rounded-2xl blur-xl opacity-40 group-hover:opacity-60 transition-opacity"></div>
                                        <div class="relative bg-white/10 backdrop-blur-sm rounded-2xl p-3 border border-white/20">
                                            <img src="{{ asset('storage/' . $logo) }}"
                                                 alt="{{ $appName }}"
                                                 class="h-14 sm:h-16 w-auto animate-float">
                                        </div>
                                    </div>
                                @else
                                    <div class="relative group">
                                        <div class="absolute inset-0 bg-gradient-to-r from-blue-500 to-amber-400 rounded-2xl blur-xl opacity-40"></div>
                                        <div class="relative bg-white rounded-2xl p-3 shadow-2xl animate-float">
                                            <img src="{{ asset('images/logo.png') }}" alt="ไทยพร๊อมท์" class="h-12 sm:h-14 w-auto">
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <h1 class="text-2xl sm:text-3xl font-bold text-white mb-2">สมัครสมาชิก</h1>
                            <p class="text-slate-400 text-sm">เริ่มต้นสร้างรายได้กับ {{ $appName }}</p>

                            {{-- Free Badge --}}
                            <div class="mt-3 inline-flex items-center gap-2 px-4 py-2 bg-green-500/20 border border-green-500/30 text-green-400 rounded-full text-sm font-semibold">
                                <i class="fas fa-check-circle"></i>
                                ฟรี! ไม่มีค่าใช้จ่าย
                            </div>

                            {{-- Default Sponsor Info --}}
                            @if(!empty($defaultSponsorName) && empty($referralCode))
                            <div class="mt-4 bg-indigo-500/20 border border-indigo-500/30 rounded-xl p-3">
                                <div class="flex items-center justify-center gap-2 text-indigo-300">
                                    <i class="fas fa-link"></i>
                                    <span class="text-sm">คุณกำลังต่อสายงานกับ <strong>{{ $defaultSponsorName }}</strong></span>
                                </div>
                            </div>
                            @endif
                        </div>

                        {{-- Session Messages --}}
                        @if (session('success'))
                        <div class="mb-4 bg-green-500/20 border border-green-500/30 text-green-200 px-4 py-3 rounded-xl text-sm">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-check-circle"></i>
                                <span>{{ session('success') }}</span>
                            </div>
                        </div>
                        @endif

                        @if (session('info'))
                        <div class="mb-4 bg-blue-500/20 border border-blue-500/30 text-blue-200 px-4 py-3 rounded-xl text-sm">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-info-circle"></i>
                                <span>{{ session('info') }}</span>
                            </div>
                        </div>
                        @endif

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

                        @php
                            $lineSettings = \App\Models\LineOaSetting::getActive();
                            $lineRequired = $lineSettings && $lineSettings->require_line_registration;
                            $showLineRegister = $lineSettings && $lineSettings->login_channel_id && $lineSettings->channel_secret;
                        @endphp

                        @if($lineRequired && $showLineRegister)
                            {{-- LINE Required Registration Mode --}}
                            <div class="text-center space-y-5">
                                <div class="bg-green-500/20 border border-green-500/30 rounded-xl p-5">
                                    <div class="w-16 h-16 bg-[#06C755] rounded-full flex items-center justify-center mx-auto mb-4">
                                        <svg class="w-10 h-10 text-white" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                                        </svg>
                                    </div>
                                    <h3 class="text-lg font-bold text-white mb-2">ต้องการ LINE เพื่อสมัครสมาชิก</h3>
                                    <p class="text-slate-400 text-sm mb-4">กรุณาทำตามขั้นตอนด้านล่าง</p>

                                    {{-- Steps --}}
                                    <div class="space-y-3 text-left">
                                        <div class="flex items-center gap-3 bg-white/5 rounded-lg p-3">
                                            <div class="w-8 h-8 bg-green-500 text-white rounded-full flex items-center justify-center font-bold text-sm flex-shrink-0">1</div>
                                            <div>
                                                <p class="font-semibold text-white text-sm">เพิ่มเพื่อน LINE Official Account</p>
                                                <p class="text-xs text-slate-400">สแกน QR Code หรือกดปุ่มด้านล่าง</p>
                                            </div>
                                        </div>

                                        @if($lineSettings->messaging_channel_id)
                                        <div class="flex flex-col items-center py-4">
                                            <div class="bg-white p-3 rounded-xl">
                                                <img src="https://qr-official.line.me/gs/M_{{ $lineSettings->messaging_channel_id }}_GW.png"
                                                     alt="LINE QR Code"
                                                     class="w-36 h-36">
                                            </div>
                                            <a href="#" onclick="addLineFriend(event)"
                                               class="mt-3 px-6 py-3 bg-[#06C755] hover:bg-[#05B34D] text-white font-semibold rounded-xl transition-all">
                                                <i class="fas fa-user-plus mr-2"></i>เพิ่มเพื่อน LINE
                                            </a>
                                        </div>
                                        @endif

                                        <div class="flex items-center gap-3 bg-white/5 rounded-lg p-3">
                                            <div class="w-8 h-8 bg-green-500 text-white rounded-full flex items-center justify-center font-bold text-sm flex-shrink-0">2</div>
                                            <div>
                                                <p class="font-semibold text-white text-sm">กดปุ่มสมัครด้วย LINE</p>
                                                <p class="text-xs text-slate-400">หลังเพิ่มเพื่อนแล้ว กดปุ่มด้านล่าง</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- LINE Register Button --}}
                                <a href="{{ route('line.login') }}{{ request('ref') ? '?ref=' . request('ref') : '' }}"
                                   class="btn-shine block w-full px-6 py-4 bg-[#06C755] hover:bg-[#05B34D] text-white font-bold text-lg rounded-xl shadow-lg hover:shadow-green-500/25 transition-all">
                                    <svg class="w-6 h-6 inline-block mr-2" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                                    </svg>
                                    สมัครสมาชิกด้วย LINE
                                </a>

                                <p class="text-xs text-slate-500">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    ต้องเพิ่มเพื่อน LINE Official Account ก่อนจึงจะสมัครได้
                                </p>
                            </div>

                            <script>
                            /**
                             * ฟังก์ชันเพิ่มเพื่อน LINE
                             *
                             * - บนมือถือ: ใช้ LINE deep link (line://ti/p/@xxx) เพื่อเปิดแอพ LINE โดยตรง
                             * - บน desktop: แจ้งให้สแกน QR code
                             * - มี fallback กรณีไม่มีแอพ LINE
                             */
                            function addLineFriend(event) {
                                event.preventDefault();
                                const lineId = '{{ $lineSettings->messaging_channel_id }}';
                                const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);

                                if (isMobile) {
                                    // ใช้ LINE deep link เพื่อเปิดแอพ LINE โดยตรง
                                    const lineDeepLink = 'line://ti/p/@' + lineId;
                                    const webFallback = 'https://line.me/R/ti/p/@' + lineId;

                                    // พยายามเปิด LINE app ก่อน
                                    const startTime = Date.now();
                                    window.location.href = lineDeepLink;

                                    // ถ้าไม่มี LINE app จะ fallback ไปเว็บ
                                    setTimeout(function() {
                                        // ถ้ายังอยู่ในหน้านี้ (ไม่ได้เปิดแอพ LINE) ให้ไปเว็บแทน
                                        if (Date.now() - startTime < 2000) {
                                            window.location.href = webFallback;
                                        }
                                    }, 1500);
                                } else {
                                    alert('กรุณาใช้โทรศัพท์สแกน QR Code หรือค้นหา @' + lineId + ' ในแอพ LINE');
                                }
                            }
                            </script>
                        @else
                            {{-- Normal Registration Form --}}
                            <form method="POST" action="{{ route('register') }}" class="space-y-4" id="registerForm">
                                @csrf

                                {{-- แสดง error เมื่อ session/CSRF หมดอายุ --}}
                                @if ($errors->has('csrf') || $errors->has('turnstile'))
                                <div class="bg-amber-500/20 border border-amber-500/40 text-amber-200 px-4 py-3 rounded-xl text-sm">
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-exclamation-triangle text-amber-400"></i>
                                        <span>{{ $errors->first('csrf') ?: $errors->first('turnstile') }}</span>
                                    </div>
                                    <p class="mt-1 text-amber-300/70 text-xs">กรุณากดปุ่ม "สมัครสมาชิกเลย!" อีกครั้ง</p>
                                </div>
                                @endif

                                {{-- Referral Code Notice - แสดงชื่อผู้เชิญ --}}
                                @if (!empty($referralCode) && !empty($referrerName))
                                <div class="bg-gradient-to-r from-green-500/20 to-emerald-500/20 border border-green-500/30 rounded-xl p-4">
                                    <div class="flex items-center gap-4">
                                        {{-- รูปผู้เชิญ --}}
                                        @if(!empty($referrerPicture))
                                            <img src="{{ $referrerPicture }}"
                                                 alt="{{ $referrerName }}"
                                                 class="w-12 h-12 rounded-full object-cover ring-2 ring-green-400/50 flex-shrink-0">
                                        @else
                                            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-green-400 to-emerald-600 flex items-center justify-center ring-2 ring-green-400/50 flex-shrink-0">
                                                <i class="fas fa-user text-white text-lg"></i>
                                            </div>
                                        @endif

                                        {{-- ข้อมูลผู้เชิญ --}}
                                        <div class="flex-1 min-w-0">
                                            <p class="text-green-300 text-sm font-medium">
                                                <i class="fas fa-handshake mr-1"></i>
                                                คุณได้รับเชิญจาก
                                            </p>
                                            <p class="text-white font-bold text-lg truncate">{{ $referrerName }}</p>
                                            <p class="text-green-400/70 text-xs">รหัส: {{ $referralCode }}</p>
                                        </div>

                                        {{-- ไอคอน --}}
                                        <div class="flex-shrink-0">
                                            <div class="w-10 h-10 bg-green-500/30 rounded-full flex items-center justify-center">
                                                <i class="fas fa-check text-green-400"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @elseif (!empty($referralCode))
                                {{-- กรณีมีรหัสแต่หาชื่อไม่เจอ --}}
                                <div class="bg-green-500/20 border border-green-500/30 text-green-300 px-4 py-3 rounded-xl text-sm">
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-user-check"></i>
                                        <span>คุณถูกแนะนำโดยรหัส: <strong>{{ $referralCode }}</strong></span>
                                    </div>
                                </div>
                                @endif

                                {{-- Name Field --}}
                                <div>
                                    <label for="name" class="block text-sm font-medium text-slate-300 mb-2">
                                        <i class="fas fa-user mr-2 text-blue-400"></i>ชื่อ-นามสกุล
                                    </label>
                                    <input type="text"
                                           name="name"
                                           id="name"
                                           value="{{ old('name') }}"
                                           class="input-glow w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white placeholder-slate-500 focus:border-blue-500 focus:outline-none transition-all @error('name') border-red-500 @enderror"
                                           placeholder="กรอกชื่อ-นามสกุลของคุณ"
                                           required
                                           autofocus>
                                    @error('name')
                                        <p class="mt-1 text-xs text-red-400"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Email Field --}}
                                <div>
                                    <label for="email" class="block text-sm font-medium text-slate-300 mb-2">
                                        <i class="fas fa-envelope mr-2 text-blue-400"></i>อีเมล
                                    </label>
                                    <input type="email"
                                           name="email"
                                           id="email"
                                           value="{{ old('email') }}"
                                           class="input-glow w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white placeholder-slate-500 focus:border-blue-500 focus:outline-none transition-all @error('email') border-red-500 @enderror"
                                           placeholder="your@email.com"
                                           required>
                                    @error('email')
                                        <p class="mt-1 text-xs text-red-400"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Password Fields --}}
                                <div class="grid sm:grid-cols-2 gap-4">
                                    <div x-data="{ show: false }">
                                        <label for="password" class="block text-sm font-medium text-slate-300 mb-2">
                                            <i class="fas fa-lock mr-2 text-purple-400"></i>รหัสผ่าน
                                        </label>
                                        <div class="relative">
                                            <input :type="show ? 'text' : 'password'"
                                                   name="password"
                                                   id="password"
                                                   class="input-glow w-full px-4 py-3 pr-12 bg-white/5 border border-white/10 rounded-xl text-white placeholder-slate-500 focus:border-blue-500 focus:outline-none transition-all @error('password') border-red-500 @enderror"
                                                   placeholder="••••••••"
                                                   required>
                                            <button type="button" @click="show = !show" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white">
                                                <i class="fas" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
                                            </button>
                                        </div>
                                        @error('password')
                                            <p class="mt-1 text-xs text-red-400"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div x-data="{ show: false }">
                                        <label for="password_confirmation" class="block text-sm font-medium text-slate-300 mb-2">
                                            <i class="fas fa-lock mr-2 text-purple-400"></i>ยืนยันรหัสผ่าน
                                        </label>
                                        <div class="relative">
                                            <input :type="show ? 'text' : 'password'"
                                                   name="password_confirmation"
                                                   id="password_confirmation"
                                                   class="input-glow w-full px-4 py-3 pr-12 bg-white/5 border border-white/10 rounded-xl text-white placeholder-slate-500 focus:border-blue-500 focus:outline-none transition-all"
                                                   placeholder="••••••••"
                                                   required>
                                            <button type="button" @click="show = !show" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white">
                                                <i class="fas" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                {{-- Referral Code Field --}}
                                <div>
                                    <label for="referral_code" class="block text-sm font-medium text-slate-300 mb-2">
                                        <i class="fas fa-gift mr-2 text-amber-400"></i>รหัสแนะนำ (ถ้ามี)
                                    </label>
                                    <input type="text"
                                           name="referral_code"
                                           id="referral_code"
                                           value="{{ old('referral_code', $referralCode ?? '') }}"
                                           class="input-glow w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white placeholder-slate-500 focus:border-blue-500 focus:outline-none transition-all @error('referral_code') border-red-500 @enderror"
                                           placeholder="กรอกรหัสแนะนำจากผู้แนะนำ">
                                    @error('referral_code')
                                        <p class="mt-1 text-xs text-red-400"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Turnstile --}}
                                @if(config('turnstile.enabled') && config('turnstile.points.register'))
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
                                        class="btn-shine w-full bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 hover:from-blue-500 hover:via-purple-500 hover:to-pink-500 text-white py-3.5 rounded-xl font-bold text-lg shadow-lg hover:shadow-purple-500/25 hover:-translate-y-0.5 transition-all duration-300">
                                    <i class="fas fa-rocket mr-2"></i>
                                    สมัครสมาชิกเลย!
                                </button>
                            </form>

                            {{-- LINE Register Option --}}
                            @if($showLineRegister)
                            <div class="mt-5">
                                <div class="relative flex items-center justify-center my-4">
                                    <div class="border-t border-white/10 w-full"></div>
                                    <span class="bg-transparent px-4 text-slate-500 text-sm absolute">หรือ</span>
                                </div>

                                <a href="{{ route('line.login') }}{{ request('ref') ? '?ref=' . request('ref') : '' }}"
                                   class="w-full flex items-center justify-center gap-3 px-4 py-3 bg-[#06C755] hover:bg-[#05B34D] text-white font-semibold rounded-xl shadow-lg hover:shadow-green-500/25 hover:-translate-y-0.5 transition-all duration-300">
                                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                                    </svg>
                                    สมัครด้วย LINE
                                </a>
                            </div>
                            @endif
                        @endif

                        {{-- Footer Links --}}
                        <div class="mt-6 pt-5 border-t border-white/10 text-center space-y-2">
                            <p class="text-slate-400 text-sm">
                                มีบัญชีแล้ว?
                                <a href="{{ route('login') }}" class="text-blue-400 hover:text-blue-300 font-semibold ml-1">
                                    เข้าสู่ระบบ <i class="fas fa-arrow-right ml-1 text-xs"></i>
                                </a>
                            </p>
                            <a href="{{ route('home') }}" class="inline-flex items-center gap-2 text-slate-500 hover:text-slate-300 text-sm transition-colors">
                                <i class="fas fa-arrow-left"></i> กลับหน้าแรก
                            </a>
                        </div>
                    </div>
                </div>

                {{-- Right Side - Benefits & Stats (2 cols) --}}
                <div class="lg:col-span-2 space-y-4 hidden lg:block">

                    {{-- Signup Rewards --}}
                    @if(isset($signupRewards) && $signupRewards->count() > 0)
                    <div class="glass-card rounded-2xl p-5 border border-green-500/30">
                        <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                            <div class="w-8 h-8 bg-green-500 rounded-lg flex items-center justify-center">
                                <i class="fas fa-gift text-white"></i>
                            </div>
                            รับรางวัลเมื่อสมัคร!
                        </h3>
                        <div class="space-y-3">
                            @foreach($signupRewards->take(4) as $reward)
                            <div class="flex items-center gap-3 bg-white/5 rounded-xl p-3">
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center text-lg"
                                     style="background-color: {{ $reward->badge_color }}20; color: {{ $reward->badge_color }}">
                                    {!! $reward->getIconHtml() !!}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-white text-sm truncate">{{ $reward->name }}</p>
                                    <p class="text-xs text-slate-400">{{ $reward->getDisplayText() }}</p>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @if($signupRewards->count() > 4)
                        <p class="text-xs text-center text-slate-400 mt-3">และอีก {{ $signupRewards->count() - 4 }} รางวัล!</p>
                        @endif
                    </div>
                    @endif

                    {{-- Live Stats --}}
                    <div class="glass-card rounded-2xl p-5">
                        <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                            <i class="fas fa-chart-line text-green-400"></i>
                            สถิติสด
                        </h3>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between bg-green-500/10 rounded-xl p-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-users text-white"></i>
                                    </div>
                                    <div>
                                        <p class="text-xs text-slate-400">สมาชิกทั้งหมด</p>
                                        <p class="text-xl font-bold text-white" id="memberCount">0</p>
                                    </div>
                                </div>
                                <span class="text-green-400 text-xs font-semibold animate-pulse-slow">
                                    <i class="fas fa-arrow-up mr-1"></i>+<span id="memberIncrement">0</span>
                                </span>
                            </div>

                            <div class="flex items-center justify-between bg-blue-500/10 rounded-xl p-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-money-bill-wave text-white"></i>
                                    </div>
                                    <div>
                                        <p class="text-xs text-slate-400">รายได้สะสม</p>
                                        <p class="text-xl font-bold text-white">฿<span id="totalEarnings">0</span></p>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center justify-between bg-purple-500/10 rounded-xl p-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-user-plus text-white"></i>
                                    </div>
                                    <div>
                                        <p class="text-xs text-slate-400">สมัครวันนี้</p>
                                        <p class="text-xl font-bold text-white" id="todaySignups">0</p>
                                    </div>
                                </div>
                                <span class="text-purple-400 text-xs font-semibold">
                                    <i class="fas fa-fire mr-1"></i>Hot!
                                </span>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-t border-white/10">
                            <div class="flex items-center text-xs text-slate-400">
                                <div class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse-slow"></div>
                                อัพเดทแบบเรียลไทม์
                            </div>
                        </div>
                    </div>

                    {{-- Benefits --}}
                    <div class="glass-card rounded-2xl p-5">
                        <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                            <i class="fas fa-star text-amber-400"></i>
                            ทำไมต้องเลือกเรา?
                        </h3>
                        <div class="space-y-3">
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 bg-purple-500/20 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-percentage text-purple-400 text-sm"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-white text-sm">คอมมิชชั่นสูง</p>
                                    <p class="text-xs text-slate-400">รับค่าคอมมิชชั่นสูงสุดในตลาด</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 bg-green-500/20 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-clock text-green-400 text-sm"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-white text-sm">ถอนเงินรวดเร็ว</p>
                                    <p class="text-xs text-slate-400">ระบบถอนเงินอัตโนมัติ ภายใน 24 ชม.</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 bg-blue-500/20 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-headset text-blue-400 text-sm"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-white text-sm">ซัพพอร์ต 24/7</p>
                                    <p class="text-xs text-slate-400">ทีมงานพร้อมช่วยเหลือตลอด 24 ชม.</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 bg-pink-500/20 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-shield-alt text-pink-400 text-sm"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-white text-sm">ปลอดภัย 100%</p>
                                    <p class="text-xs text-slate-400">ระบบรักษาความปลอดภัยระดับสูง</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Security Badge --}}
            <div class="mt-6 text-center">
                <p class="text-slate-500 text-xs flex items-center justify-center gap-2">
                    <i class="fas fa-shield-alt text-green-500"></i>
                    ระบบปลอดภัยด้วยการเข้ารหัส SSL
                </p>
            </div>
        </div>
    </div>

    {{-- Alpine.js --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    {{-- CSRF Token Auto-Refresh สำหรับมือถือที่เปิดทิ้งไว้นาน --}}
    <script>
        (function() {
            // Refresh CSRF token ทุก 10 นาที เพื่อป้องกัน session หมดอายุ
            var csrfRefreshInterval = 10 * 60 * 1000; // 10 นาที

            function refreshCsrfToken() {
                fetch('/sanctum/csrf-cookie', {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                }).catch(function() {
                    // ถ้า sanctum endpoint ไม่มี → ใช้ GET หน้าปัจจุบัน
                    fetch(window.location.href, {
                        method: 'GET',
                        credentials: 'same-origin',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    }).then(function(response) {
                        // ดึง CSRF token จาก cookie ที่ได้รับ
                        var match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
                        if (match) {
                            var token = decodeURIComponent(match[1]);
                            // อัพเดท meta tag
                            var meta = document.querySelector('meta[name="csrf-token"]');
                            if (meta) meta.setAttribute('content', token);
                            // อัพเดท hidden input ในฟอร์ม
                            var inputs = document.querySelectorAll('input[name="_token"]');
                            inputs.forEach(function(input) { input.value = token; });
                        }
                    }).catch(function() {});
                });
            }

            // Refresh เมื่อหน้ากลับมา active (มือถือสลับแอป/ล็อคหน้าจอ)
            document.addEventListener('visibilitychange', function() {
                if (!document.hidden) {
                    refreshCsrfToken();
                }
            });

            // Refresh ตาม interval
            setInterval(refreshCsrfToken, csrfRefreshInterval);
        })();
    </script>

    {{-- Stats Animation Script --}}
    <script>
        function animateCounter(element, start, end, duration, decimals = 0) {
            if (!element) return;
            const range = end - start;
            const increment = range / (duration / 16);
            let current = start;
            const timer = setInterval(() => {
                current += increment;
                if ((increment > 0 && current >= end) || (increment < 0 && current <= end)) {
                    current = end;
                    clearInterval(timer);
                }
                element.textContent = decimals > 0
                    ? current.toFixed(decimals).replace(/\B(?=(\d{3})+(?!\d))/g, ",")
                    : Math.floor(current).toLocaleString('en-US');
            }, 16);
        }

        document.addEventListener('DOMContentLoaded', function() {
            const baseMembers = 8547;
            const baseEarnings = 2847653.50;
            const baseTodaySignups = 127;

            animateCounter(document.getElementById('memberCount'), 0, baseMembers, 2000);
            animateCounter(document.getElementById('totalEarnings'), 0, baseEarnings, 2000, 2);
            animateCounter(document.getElementById('todaySignups'), 0, baseTodaySignups, 2000);

            const memberIncEl = document.getElementById('memberIncrement');
            if (memberIncEl) memberIncEl.textContent = '3';

            setInterval(() => {
                const memberInc = Math.floor(Math.random() * 3) + 1;
                const earningsInc = (Math.random() * 2000 + 500).toFixed(2);
                const todayInc = Math.floor(Math.random() * 2) + 1;

                if (memberIncEl) memberIncEl.textContent = memberInc;

                const memberEl = document.getElementById('memberCount');
                const earningsEl = document.getElementById('totalEarnings');
                const todayEl = document.getElementById('todaySignups');

                if (memberEl) {
                    const current = parseInt(memberEl.textContent.replace(/,/g, ''));
                    animateCounter(memberEl, current, current + memberInc, 800);
                }
                if (earningsEl) {
                    const current = parseFloat(earningsEl.textContent.replace(/,/g, ''));
                    animateCounter(earningsEl, current, current + parseFloat(earningsInc), 800, 2);
                }
                if (todayEl) {
                    const current = parseInt(todayEl.textContent.replace(/,/g, ''));
                    animateCounter(todayEl, current, current + todayInc, 800);
                }
            }, 5000);
        });
    </script>
</body>
</html>
