<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $appName = \App\Models\Setting::get('app_name', 'TP-Affiliate');
        $lineSettings = \App\Models\LineOaSetting::getActive();
        $lineId = $lineSettings->line_id ?? null;
    @endphp
    <title>สมัครสมาชิกผ่าน LINE - {{ $appName }}</title>

    {{-- Vite Assets (V3) --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* Custom animations for LINE registration guide */
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

        @keyframes float {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
        }

        .animate-fade-in-up {
            animation: fadeInUp 0.8s ease-out;
        }

        .animated-gradient {
            background: linear-gradient(-45deg, #06C755, #00B900, #00D564, #06C755);
            background-size: 400% 400%;
            animation: gradient 15s ease infinite;
        }

        .dark .animated-gradient {
            background: linear-gradient(-45deg, #047857, #065f46, #064e3b, #047857);
            background-size: 400% 400%;
        }

        .animate-float {
            animation: float 3s ease-in-out infinite;
        }
    </style>
</head>
<body class="animated-gradient min-h-screen">
    <div class="min-h-screen flex items-center justify-center px-4 py-12">
        <div class="max-w-2xl w-full">
            <div class="glass-fusion backdrop-blur-2xl bg-white/90 dark:bg-gray-800/90 rounded-3xl shadow-2xl border border-white/30 dark:border-gray-700/50 p-8 md:p-12 animate-fade-in-up">
                <!-- LINE Logo with Animation -->
                <div class="text-center mb-8">
                    <div class="flex justify-center mb-6 animate-float">
                        <div class="w-24 h-24 bg-gradient-to-br from-[#06C755] to-[#00B900] rounded-2xl shadow-2xl flex items-center justify-center">
                            <svg class="w-16 h-16" viewBox="0 0 24 24" fill="white">
                                <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                            </svg>
                        </div>
                    </div>
                    <h1 class="text-3xl md:text-4xl font-bold bg-gradient-to-r from-[#06C755] via-[#00B900] to-[#00D564] dark:from-green-400 dark:via-emerald-400 dark:to-teal-400 bg-clip-text text-transparent mb-3">
                        สมัครสมาชิกผ่าน LINE OA 📱
                    </h1>
                    <p class="text-gray-700 dark:text-gray-300 text-lg">
                        กรุณาทำตามขั้นตอนด้านล่างเพื่อสมัครสมาชิก
                    </p>
                </div>

                <!-- Steps with Modern Design -->
                <div class="space-y-6 mb-8">
                    <!-- Step 1 -->
                    <div class="group glass-fusion backdrop-blur-md bg-gradient-to-r from-green-50/80 to-emerald-50/80 dark:from-green-900/30 dark:to-emerald-900/30 p-6 rounded-2xl border-2 border-green-200 dark:border-green-700/50 hover:border-green-400 dark:hover:border-green-500 transition-all duration-300 hover:shadow-xl">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 text-white rounded-xl flex items-center justify-center font-bold text-xl shadow-lg group-hover:scale-110 transition-transform">
                                1
                            </div>
                            <div class="flex-1">
                                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">เพิ่มเพื่อน LINE Official Account 👥</h3>
                                <p class="text-gray-700 dark:text-gray-300 mb-4">
                                    สแกน QR Code หรือคลิกปุ่มด้านล่างเพื่อเพิ่มเพื่อน {{ $appName }} LINE OA
                                </p>
                                @if($lineId)
                                <a href="https://line.me/R/ti/p/{{ $lineId }}" target="_blank"
                                   class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-[#06C755] to-[#00B900] hover:from-[#05B04C] hover:to-[#00A000] text-white rounded-xl transition-all duration-300 font-bold shadow-lg hover:shadow-xl transform hover:-translate-y-1 hover:scale-105">
                                    <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                                    </svg>
                                    <span>เพิ่มเพื่อน LINE OA</span>
                                </a>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Step 2 -->
                    <div class="group glass-fusion backdrop-blur-md bg-gradient-to-r from-blue-50/80 to-cyan-50/80 dark:from-blue-900/30 dark:to-cyan-900/30 p-6 rounded-2xl border-2 border-blue-200 dark:border-blue-700/50 hover:border-blue-400 dark:hover:border-blue-500 transition-all duration-300 hover:shadow-xl">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-12 h-12 bg-gradient-to-br from-blue-500 to-cyan-600 text-white rounded-xl flex items-center justify-center font-bold text-xl shadow-lg group-hover:scale-110 transition-transform">
                                2
                            </div>
                            <div class="flex-1">
                                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">แชทกับบอท 💬</h3>
                                <p class="text-gray-700 dark:text-gray-300">
                                    ส่งข้อความใด ๆ ไปยัง LINE OA และทำตามคำแนะนำจากบอทเพื่อสมัครสมาชิก
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3 -->
                    <div class="group glass-fusion backdrop-blur-md bg-gradient-to-r from-purple-50/80 to-pink-50/80 dark:from-purple-900/30 dark:to-pink-900/30 p-6 rounded-2xl border-2 border-purple-200 dark:border-purple-700/50 hover:border-purple-400 dark:hover:border-purple-500 transition-all duration-300 hover:shadow-xl">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-600 text-white rounded-xl flex items-center justify-center font-bold text-xl shadow-lg group-hover:scale-110 transition-transform">
                                3
                            </div>
                            <div class="flex-1">
                                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">รับ Link ลงทะเบียน 🔗</h3>
                                <p class="text-gray-700 dark:text-gray-300">
                                    บอทจะส่ง Link พิเศษให้คุณเพื่อกรอกข้อมูลและลงทะเบียนให้เสร็จสมบูรณ์
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Info Box with Modern Design -->
                <div class="glass-fusion backdrop-blur-md bg-gradient-to-r from-yellow-50/80 to-amber-50/80 dark:from-yellow-900/30 dark:to-amber-900/30 border-2 border-yellow-200 dark:border-yellow-700/50 rounded-2xl p-6 mb-8 shadow-lg">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-12 h-12 bg-gradient-to-br from-yellow-400 to-amber-500 rounded-xl flex items-center justify-center shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h4 class="text-lg font-bold text-yellow-900 dark:text-yellow-300 mb-3">ทำไมต้องสมัครผ่าน LINE? 🤔</h4>
                            <ul class="text-yellow-800 dark:text-yellow-200 space-y-2">
                                <li class="flex items-center gap-2">
                                    <svg class="w-5 h-5 text-green-600 dark:text-green-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span>รับการแจ้งเตือนสำคัญผ่าน LINE</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <svg class="w-5 h-5 text-green-600 dark:text-green-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span>ติดต่อสอบถามได้สะดวกรวดเร็ว</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <svg class="w-5 h-5 text-green-600 dark:text-green-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span>รับโปรโมชั่นและข่าวสารล่าสุด</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <svg class="w-5 h-5 text-green-600 dark:text-green-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span>ระบบปลอดภัยมากขึ้น</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons with 3D Effects -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <a href="{{ route('login') }}"
                       class="group flex items-center justify-center gap-2 px-6 py-4 glass-fusion backdrop-blur-md bg-gray-100/80 dark:bg-gray-700/50 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-xl transition-all duration-300 font-bold shadow-lg hover:shadow-xl border border-gray-200 dark:border-gray-600">
                        <svg class="w-5 h-5 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        <span>กลับไปหน้า Login</span>
                    </a>
                    <a href="{{ route('home') }}"
                       class="group flex items-center justify-center gap-2 px-6 py-4 bg-gradient-to-r from-gray-600 to-gray-700 hover:from-gray-700 hover:to-gray-800 text-white rounded-xl transition-all duration-300 font-bold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <span>กลับหน้าแรก</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Dark Mode Toggle --}}
    <script>
        // Auto dark mode based on system preference
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.documentElement.classList.add('dark');
        }
    </script>
</body>
</html>
