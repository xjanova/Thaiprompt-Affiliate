{{--
/**
 * หน้าสมัครสมาชิกผ่าน LINE OA - บังคับเพิ่มเพื่อนก่อนสมัคร
 *
 * Flow:
 * 1. แสดง QR Code สำหรับเพิ่มเพื่อน LINE OA
 * 2. ผู้ใช้สแกน QR Code และเพิ่มเพื่อน
 * 3. หน้าเว็บ polling เช็คสถานะ
 * 4. เมื่อสมัครเสร็จ → auto redirect ไปหน้าอัพเดทข้อมูล
 *
 * @version 1.0.0
 */
--}}
<!DOCTYPE html>
<html lang="th" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        $siteSettings = \App\Models\SiteSetting::getSetting();
        $appName = $siteSettings->site_name ?? 'TP-Affiliate Pro';
        $logo = $siteSettings->logo;
        $favicon = $siteSettings->favicon;
    @endphp

    <title>สมัครสมาชิกผ่าน LINE - {{ $appName }}</title>

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

        /* Pulse glow */
        @keyframes pulseGlow {
            0%, 100% { box-shadow: 0 0 20px rgba(6, 199, 85, 0.4); }
            50% { box-shadow: 0 0 40px rgba(6, 199, 85, 0.7); }
        }
        .animate-pulse-glow {
            animation: pulseGlow 2s ease-in-out infinite;
        }

        /* Glass effect */
        .glass-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Status indicator */
        .status-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(1.1); }
        }

        /* Shimmer loading */
        .shimmer {
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
        }

        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-green-950 to-emerald-950 relative overflow-x-hidden"
      x-data="lineRegistration()"
      x-init="init()">
    {{-- Background Effects --}}
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute inset-0 opacity-[0.02]"
             style="background-image: linear-gradient(rgba(255,255,255,.1) 1px, transparent 1px),
                                      linear-gradient(90deg, rgba(255,255,255,.1) 1px, transparent 1px);
                    background-size: 50px 50px;">
        </div>
        <div class="absolute top-0 -left-40 w-80 h-80 bg-green-600/20 rounded-full blur-3xl"></div>
        <div class="absolute bottom-0 -right-40 w-80 h-80 bg-emerald-600/20 rounded-full blur-3xl"></div>
    </div>

    {{-- Main Content --}}
    <div class="relative z-10 min-h-screen flex items-center justify-center p-4 py-8">
        <div class="w-full max-w-2xl animate-fade-in-up">
            <div class="glass-card rounded-3xl shadow-2xl p-6 sm:p-10">

                {{-- Header --}}
                <div class="text-center mb-8">
                    {{-- Logo --}}
                    <div class="flex justify-center mb-6">
                        <div class="relative group">
                            <div class="absolute inset-0 bg-gradient-to-r from-green-500 to-emerald-500 rounded-2xl blur-xl opacity-40 group-hover:opacity-60 transition-opacity"></div>
                            <div class="relative w-20 h-20 bg-gradient-to-br from-[#06C755] to-[#00B900] rounded-2xl flex items-center justify-center shadow-2xl animate-float">
                                <svg class="w-12 h-12" viewBox="0 0 24 24" fill="white">
                                    <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <h1 class="text-3xl sm:text-4xl font-bold text-white mb-3">
                        สมัครสมาชิกผ่าน LINE
                    </h1>
                    <p class="text-slate-400 text-lg">
                        เพิ่มเพื่อน LINE OA แล้วสมัครสมาชิกอัตโนมัติ
                    </p>

                    {{-- Free Badge --}}
                    <div class="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-green-500/20 border border-green-500/30 text-green-400 rounded-full text-sm font-semibold">
                        <i class="fas fa-check-circle"></i>
                        ฟรี! ไม่มีค่าใช้จ่าย
                    </div>
                </div>

                {{-- Sponsor Info (ถ้ามี) --}}
                @if($sponsor)
                <div class="mb-6 bg-gradient-to-r from-indigo-500/20 to-purple-500/20 border border-indigo-500/30 rounded-2xl p-4">
                    <div class="flex items-center gap-4">
                        @if($sponsor->line_picture_url || $sponsor->avatar_url)
                            <img src="{{ $sponsor->line_picture_url ?? $sponsor->avatar_url }}"
                                 alt="{{ $sponsor->name }}"
                                 class="w-14 h-14 rounded-full object-cover ring-2 ring-indigo-400/50">
                        @else
                            <div class="w-14 h-14 rounded-full bg-gradient-to-br from-indigo-400 to-purple-600 flex items-center justify-center ring-2 ring-indigo-400/50">
                                <i class="fas fa-user text-white text-xl"></i>
                            </div>
                        @endif
                        <div class="flex-1">
                            <p class="text-indigo-300 text-sm font-medium">
                                <i class="fas fa-handshake mr-1"></i> คุณได้รับเชิญจาก
                            </p>
                            <p class="text-white font-bold text-lg">{{ $sponsor->name }}</p>
                        </div>
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 bg-green-500/30 rounded-full flex items-center justify-center">
                                <i class="fas fa-check text-green-400"></i>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Status Section --}}
                <div class="mb-8">
                    {{-- Status: Pending - รอเพิ่มเพื่อน --}}
                    <div x-show="status === 'pending'" class="space-y-6">
                        {{-- Step 1: QR Code --}}
                        <div class="bg-white/5 rounded-2xl p-6 text-center">
                            <div class="mb-4">
                                <div class="inline-flex items-center gap-2 px-4 py-2 bg-yellow-500/20 border border-yellow-500/30 text-yellow-400 rounded-full text-sm font-semibold">
                                    <div class="status-dot bg-yellow-500"></div>
                                    <span>ขั้นตอนที่ 1: เพิ่มเพื่อน LINE OA</span>
                                </div>
                            </div>

                            @if($qrCodeUrl)
                            <div class="flex flex-col items-center">
                                <div class="bg-white p-4 rounded-2xl shadow-xl mb-4 animate-pulse-glow">
                                    <img src="{{ $qrCodeUrl }}"
                                         alt="LINE QR Code"
                                         class="w-44 h-44 sm:w-52 sm:h-52">
                                </div>
                                <p class="text-slate-400 text-sm mb-4">สแกน QR Code ด้วยแอพ LINE</p>

                                @if($addFriendUrl)
                                <a href="{{ $addFriendUrl }}"
                                   target="_blank"
                                   @click="recordAddFriendClick()"
                                   class="inline-flex items-center px-6 py-3 bg-[#06C755] hover:bg-[#05B34D] text-white font-bold rounded-xl transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                                    <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                                    </svg>
                                    <span>เพิ่มเพื่อน LINE OA</span>
                                </a>
                                @endif
                            </div>
                            @else
                            <div class="text-red-400">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                ยังไม่ได้ตั้งค่า LINE OA
                            </div>
                            @endif
                        </div>

                        {{-- Instructions --}}
                        <div class="bg-gradient-to-r from-blue-500/10 to-cyan-500/10 border border-blue-500/20 rounded-2xl p-5">
                            <h3 class="text-white font-bold mb-4 flex items-center gap-2">
                                <i class="fas fa-info-circle text-blue-400"></i>
                                ขั้นตอนการสมัคร
                            </h3>
                            <ol class="space-y-3 text-slate-300 text-sm">
                                <li class="flex items-start gap-3">
                                    <span class="flex-shrink-0 w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs font-bold">1</span>
                                    <span>สแกน QR Code หรือกดปุ่ม "เพิ่มเพื่อน LINE OA"</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <span class="flex-shrink-0 w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs font-bold">2</span>
                                    <span>กด "เพิ่มเพื่อน" ในแอพ LINE</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <span class="flex-shrink-0 w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs font-bold">3</span>
                                    <span>ทำตามคำแนะนำจากบอท LINE</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <span class="flex-shrink-0 w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs font-bold">4</span>
                                    <span>หน้านี้จะอัพเดทอัตโนมัติเมื่อสมัครเสร็จ</span>
                                </li>
                            </ol>
                        </div>
                    </div>

                    {{-- Status: Followed - เพิ่มเพื่อนแล้ว --}}
                    <div x-show="status === 'followed'" class="space-y-6">
                        <div class="bg-gradient-to-r from-green-500/20 to-emerald-500/20 border border-green-500/30 rounded-2xl p-6 text-center">
                            <div class="mb-4">
                                <div class="inline-flex items-center gap-2 px-4 py-2 bg-green-500/30 border border-green-500/40 text-green-400 rounded-full text-sm font-semibold">
                                    <i class="fas fa-check-circle"></i>
                                    <span>เพิ่มเพื่อน LINE OA แล้ว!</span>
                                </div>
                            </div>
                            <div class="w-20 h-20 bg-green-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-user-plus text-green-400 text-3xl"></i>
                            </div>
                            <h3 class="text-xl font-bold text-white mb-2">กำลังดำเนินการ...</h3>
                            <p class="text-slate-400">กรุณาทำตามคำแนะนำใน LINE เพื่อสมัครสมาชิก</p>

                            {{-- Loading indicator --}}
                            <div class="mt-6 flex items-center justify-center gap-3 text-slate-400">
                                <div class="w-2 h-2 bg-green-500 rounded-full animate-bounce" style="animation-delay: 0s;"></div>
                                <div class="w-2 h-2 bg-green-500 rounded-full animate-bounce" style="animation-delay: 0.1s;"></div>
                                <div class="w-2 h-2 bg-green-500 rounded-full animate-bounce" style="animation-delay: 0.2s;"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Status: In Progress - กำลังสมัคร --}}
                    <div x-show="status === 'in_progress'" class="space-y-6">
                        <div class="bg-gradient-to-r from-blue-500/20 to-purple-500/20 border border-blue-500/30 rounded-2xl p-6 text-center">
                            <div class="mb-4">
                                <div class="inline-flex items-center gap-2 px-4 py-2 bg-blue-500/30 border border-blue-500/40 text-blue-400 rounded-full text-sm font-semibold">
                                    <div class="status-dot bg-blue-500"></div>
                                    <span>กำลังสมัครสมาชิก...</span>
                                </div>
                            </div>
                            <div class="w-20 h-20 bg-blue-500/20 rounded-full flex items-center justify-center mx-auto mb-4 animate-pulse">
                                <i class="fas fa-spinner fa-spin text-blue-400 text-3xl"></i>
                            </div>
                            <h3 class="text-xl font-bold text-white mb-2">กำลังดำเนินการ</h3>
                            <p class="text-slate-400">กรุณาตอบคำถามใน LINE เพื่อดำเนินการสมัครต่อ</p>
                        </div>
                    </div>

                    {{-- Status: Completed - สมัครเสร็จ --}}
                    <div x-show="status === 'completed'" class="space-y-6">
                        <div class="bg-gradient-to-r from-green-500/20 to-emerald-500/20 border border-green-500/30 rounded-2xl p-6 text-center">
                            <div class="mb-4">
                                <div class="inline-flex items-center gap-2 px-4 py-2 bg-green-500/30 border border-green-500/40 text-green-400 rounded-full text-sm font-semibold">
                                    <i class="fas fa-check-circle"></i>
                                    <span>สมัครสมาชิกสำเร็จ!</span>
                                </div>
                            </div>
                            <div class="w-20 h-20 bg-green-500/20 rounded-full flex items-center justify-center mx-auto mb-4 animate-pulse-glow">
                                <i class="fas fa-check text-green-400 text-3xl"></i>
                            </div>
                            <h3 class="text-2xl font-bold text-white mb-2">ยินดีต้อนรับ!</h3>
                            <p class="text-slate-400 mb-4" x-text="userName ? 'สวัสดี ' + userName : 'การสมัครสมาชิกเสร็จสมบูรณ์'"></p>

                            <p class="text-slate-300 mb-6">กำลังนำคุณไปหน้าอัพเดทข้อมูล...</p>

                            <div class="flex items-center justify-center gap-3">
                                <div class="w-2 h-2 bg-green-500 rounded-full animate-bounce" style="animation-delay: 0s;"></div>
                                <div class="w-2 h-2 bg-green-500 rounded-full animate-bounce" style="animation-delay: 0.1s;"></div>
                                <div class="w-2 h-2 bg-green-500 rounded-full animate-bounce" style="animation-delay: 0.2s;"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Status: Expired - หมดอายุ --}}
                    <div x-show="status === 'expired'" class="space-y-6">
                        <div class="bg-gradient-to-r from-red-500/20 to-orange-500/20 border border-red-500/30 rounded-2xl p-6 text-center">
                            <div class="mb-4">
                                <div class="inline-flex items-center gap-2 px-4 py-2 bg-red-500/30 border border-red-500/40 text-red-400 rounded-full text-sm font-semibold">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <span>Session หมดอายุ</span>
                                </div>
                            </div>
                            <div class="w-20 h-20 bg-red-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-clock text-red-400 text-3xl"></i>
                            </div>
                            <h3 class="text-xl font-bold text-white mb-2">กรุณาเริ่มใหม่</h3>
                            <p class="text-slate-400 mb-4">Session การสมัครหมดอายุแล้ว กรุณาเริ่มใหม่อีกครั้ง</p>

                            <a href="{{ route('line.registration.register') }}{{ request('ref') ? '?ref=' . request('ref') : '' }}"
                               class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl transition-all">
                                <i class="fas fa-redo mr-2"></i>
                                เริ่มใหม่
                            </a>
                        </div>
                    </div>
                </div>

                {{-- Footer Links --}}
                <div class="pt-6 border-t border-white/10 text-center space-y-2">
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

            {{-- Security Badge --}}
            <div class="mt-6 text-center">
                <p class="text-slate-500 text-xs flex items-center justify-center gap-2">
                    <i class="fas fa-shield-alt text-green-500"></i>
                    ระบบปลอดภัยด้วยการเข้ารหัส SSL
                </p>
            </div>
        </div>
    </div>

    {{-- Alpine.js Component --}}
    <script>
        function lineRegistration() {
            return {
                sessionToken: '{{ $session->session_token }}',
                status: '{{ $session->status }}',
                userName: null,
                redirectUrl: null,
                authToken: null,
                pollingInterval: null,
                pollingDelay: 3000, // 3 วินาที

                init() {
                    // เริ่ม polling
                    this.startPolling();
                },

                startPolling() {
                    // เช็คทันที
                    this.checkStatus();

                    // เริ่ม interval
                    this.pollingInterval = setInterval(() => {
                        this.checkStatus();
                    }, this.pollingDelay);
                },

                stopPolling() {
                    if (this.pollingInterval) {
                        clearInterval(this.pollingInterval);
                        this.pollingInterval = null;
                    }
                },

                async checkStatus() {
                    try {
                        const response = await fetch(`/line/registration/status/${this.sessionToken}`, {
                            method: 'GET',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });

                        if (!response.ok) {
                            console.error('Status check failed:', response.status);
                            return;
                        }

                        const data = await response.json();

                        if (data.success && data.data) {
                            this.status = data.data.status;

                            // อัพเดทข้อมูลผู้ใช้ (ถ้ามี)
                            if (data.data.user) {
                                this.userName = data.data.user.name;
                            }

                            // ถ้าสมัครเสร็จแล้ว → redirect
                            if (this.status === 'completed' && data.data.redirect_url) {
                                this.stopPolling();
                                this.redirectUrl = data.data.redirect_url;

                                // รอ 2 วินาทีแล้ว redirect
                                setTimeout(() => {
                                    window.location.href = this.redirectUrl;
                                }, 2000);
                            }

                            // ถ้าหมดอายุ → หยุด polling
                            if (this.status === 'expired') {
                                this.stopPolling();
                            }
                        }
                    } catch (error) {
                        console.error('Error checking status:', error);
                    }
                },

                async recordAddFriendClick() {
                    try {
                        await fetch(`/line/registration/click/${this.sessionToken}`, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            },
                        });
                    } catch (error) {
                        console.error('Error recording click:', error);
                    }
                },
            };
        }
    </script>
</body>
</html>
