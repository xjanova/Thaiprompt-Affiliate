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
            background: radial-gradient(circle, rgba(167, 243, 208, 0.9) 0%, rgba(52, 211, 153, 0.6) 40%, transparent 70%);
            animation: fireflyGlow 2s ease-in-out infinite alternate;
        }

        .firefly::after {
            background: radial-gradient(circle, rgba(255, 255, 255, 1) 0%, rgba(167, 243, 208, 0.8) 30%, transparent 60%);
            width: 4px;
            height: 4px;
            left: 1px;
            top: 1px;
            animation: fireflyCore 1.5s ease-in-out infinite alternate;
        }

        @keyframes fireflyGlow {
            0% { transform: scale(1); opacity: 0.4; }
            100% { transform: scale(2.5); opacity: 0.8; }
        }

        @keyframes fireflyCore {
            0% { opacity: 0.8; }
            100% { opacity: 1; }
        }

        @keyframes fireflyMove {
            0% { transform: translate(0, 0) scale(1); }
            25% { transform: translate(50px, -30px) scale(1.2); }
            50% { transform: translate(100px, 20px) scale(0.8); }
            75% { transform: translate(30px, 60px) scale(1.1); }
            100% { transform: translate(0, 0) scale(1); }
        }

        @keyframes fireflyFloat {
            0%, 100% { opacity: 0; transform: translateY(0) scale(0.5); }
            10% { opacity: 1; }
            90% { opacity: 1; }
            50% { transform: translateY(-100px) scale(1); }
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

    {{-- ✨ Firefly Container --}}
    <div id="fireflies" class="fixed inset-0 pointer-events-none z-0 overflow-hidden"></div>

    {{-- Main Content --}}
    <div class="relative z-10 min-h-screen flex items-center justify-center p-4 py-8">
        <div class="w-full max-w-6xl animate-fade-in-up">
            <div class="grid lg:grid-cols-5 gap-6">

            {{-- Left Side - Registration (3 cols) --}}
            <div class="lg:col-span-3">
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
            </div>
            {{-- END Left Side --}}

            {{-- Right Side - Benefits & Stats (2 cols) - Hidden on mobile --}}
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
                            <span class="text-green-400 text-xs font-semibold animate-pulse">
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
                            <div class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></div>
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
            {{-- END Right Side --}}

            </div>
            {{-- END Grid --}}

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

        // Stats Animation Script
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

            // ✨ Firefly Effect - สร้างหิ่งห้อยสวยๆ
            createFireflies();
        });

        // ✨ Firefly Generator
        function createFireflies() {
            const container = document.getElementById('fireflies');
            if (!container) return;

            const fireflyCount = 25; // จำนวนหิ่งห้อย

            for (let i = 0; i < fireflyCount; i++) {
                createFirefly(container, i);
            }
        }

        function createFirefly(container, index) {
            const firefly = document.createElement('div');
            firefly.className = 'firefly';

            // Random position
            const startX = Math.random() * 100;
            const startY = Math.random() * 100;

            // Random animation duration (8-20 seconds)
            const moveDuration = 8 + Math.random() * 12;
            const glowDelay = Math.random() * 3;

            // Random movement path
            const moveX1 = (Math.random() - 0.5) * 200;
            const moveY1 = (Math.random() - 0.5) * 200;
            const moveX2 = (Math.random() - 0.5) * 300;
            const moveY2 = (Math.random() - 0.5) * 200;
            const moveX3 = (Math.random() - 0.5) * 150;
            const moveY3 = (Math.random() - 0.5) * 250;

            // Random size (4-10px)
            const size = 4 + Math.random() * 6;

            // Random color (green to cyan spectrum)
            const hue = 120 + Math.random() * 60; // 120-180 (green to cyan)

            firefly.style.cssText = `
                left: ${startX}%;
                top: ${startY}%;
                width: ${size}px;
                height: ${size}px;
                --glow-delay: ${glowDelay}s;
                animation: fireflyCustomMove${index} ${moveDuration}s ease-in-out infinite;
            `;

            // Custom glow color
            firefly.style.setProperty('--glow-color', `hsl(${hue}, 80%, 60%)`);

            // Create unique keyframe animation for each firefly
            const styleSheet = document.createElement('style');
            styleSheet.textContent = `
                @keyframes fireflyCustomMove${index} {
                    0%, 100% {
                        transform: translate(0, 0) scale(1);
                        opacity: ${0.3 + Math.random() * 0.4};
                    }
                    25% {
                        transform: translate(${moveX1}px, ${moveY1}px) scale(${0.8 + Math.random() * 0.4});
                        opacity: ${0.5 + Math.random() * 0.5};
                    }
                    50% {
                        transform: translate(${moveX2}px, ${moveY2}px) scale(${0.6 + Math.random() * 0.6});
                        opacity: ${0.4 + Math.random() * 0.4};
                    }
                    75% {
                        transform: translate(${moveX3}px, ${moveY3}px) scale(${0.9 + Math.random() * 0.3});
                        opacity: ${0.6 + Math.random() * 0.4};
                    }
                }
            `;
            document.head.appendChild(styleSheet);

            container.appendChild(firefly);
        }
    </script>
</body>
</html>
