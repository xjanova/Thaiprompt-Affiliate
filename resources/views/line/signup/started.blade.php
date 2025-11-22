<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เริ่มสมัครสำเร็จ - LINE Signup</title>

    {{-- Vite Assets (V3) --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* Custom animations for success page */
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

        @keyframes pulse-glow {
            0%, 100% {
                box-shadow: 0 0 20px rgba(6, 199, 85, 0.4);
            }
            50% {
                box-shadow: 0 0 40px rgba(6, 199, 85, 0.6);
            }
        }

        .animate-fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }

        .animate-pulse-glow {
            animation: pulse-glow 2s ease-in-out infinite;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-green-50 via-emerald-50 to-teal-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-lg w-full animate-fade-in-up">
        {{-- Success Card with Glassmorphism --}}
        <div class="glass-fusion backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 rounded-3xl shadow-2xl border border-white/20 dark:border-gray-700/50 p-8 md:p-10 text-center">
            {{-- Success Icon with Glow Effect --}}
            <div class="inline-flex items-center justify-center w-24 h-24 bg-gradient-to-br from-green-400 to-emerald-600 rounded-full mb-6 animate-pulse-glow">
                <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>

            {{-- Title with Gradient Text --}}
            <h1 class="text-3xl md:text-4xl font-bold bg-gradient-to-r from-green-600 to-emerald-600 dark:from-green-400 dark:to-emerald-400 bg-clip-text text-transparent mb-4">
                เริ่มสมัครสำเร็จ! ✨
            </h1>

            {{-- Message --}}
            <p class="text-gray-700 dark:text-gray-300 mb-8 text-lg">
                ระบบได้เริ่มกระบวนการสมัครสมาชิกของคุณแล้ว
            </p>

            {{-- LINE Instructions with Modern Design --}}
            <div class="glass-fusion backdrop-blur-md bg-gradient-to-br from-green-50/80 to-emerald-50/80 dark:from-green-900/30 dark:to-emerald-900/30 border-2 border-green-200 dark:border-green-700/50 rounded-2xl p-6 md:p-8 mb-8 shadow-lg">
                <div class="flex items-start gap-4">
                    {{-- LINE Icon --}}
                    <div class="flex-shrink-0 w-12 h-12 bg-[#06C755] rounded-xl flex items-center justify-center shadow-lg">
                        <svg class="w-7 h-7 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314" />
                        </svg>
                    </div>

                    <div class="text-left flex-1">
                        <h3 class="text-lg font-bold text-green-900 dark:text-green-300 mb-3 flex items-center gap-2">
                            <span>ขั้นตอนต่อไป</span>
                            <span class="text-2xl">👇</span>
                        </h3>
                        <ol class="space-y-3 text-sm md:text-base">
                            <li class="flex items-start gap-3 text-green-800 dark:text-green-200">
                                <span class="flex-shrink-0 w-6 h-6 bg-green-600 dark:bg-green-500 text-white rounded-full flex items-center justify-center text-xs font-bold">1</span>
                                <span class="pt-0.5">เปิดแอพ LINE ของคุณ 📱</span>
                            </li>
                            <li class="flex items-start gap-3 text-green-800 dark:text-green-200">
                                <span class="flex-shrink-0 w-6 h-6 bg-green-600 dark:bg-green-500 text-white rounded-full flex items-center justify-center text-xs font-bold">2</span>
                                <span class="pt-0.5">คุณจะได้รับข้อความจาก Bot ของเรา 💬</span>
                            </li>
                            <li class="flex items-start gap-3 text-green-800 dark:text-green-200">
                                <span class="flex-shrink-0 w-6 h-6 bg-green-600 dark:bg-green-500 text-white rounded-full flex items-center justify-center text-xs font-bold">3</span>
                                <span class="pt-0.5">ตอบคำถามตามที่ Bot ถาม เพื่อทำการสมัครให้เสร็จสมบูรณ์ ✅</span>
                            </li>
                        </ol>
                    </div>
                </div>
            </div>

            @if(isset($sponsor))
                {{-- Sponsor Info with Modern Card --}}
                <div class="glass-fusion backdrop-blur-md bg-gradient-to-br from-indigo-50/80 to-purple-50/80 dark:from-indigo-900/30 dark:to-purple-900/30 border-2 border-indigo-200 dark:border-indigo-700/50 rounded-2xl p-6 mb-8 shadow-lg">
                    <p class="text-sm text-indigo-700 dark:text-indigo-300 mb-3 font-semibold">
                        คุณได้รับเชิญจาก:
                    </p>
                    <div class="flex items-center justify-center gap-4">
                        <div class="w-12 h-12 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center text-white font-bold text-lg shadow-lg">
                            {{ substr($sponsor->name, 0, 1) }}
                        </div>
                        <div class="text-left">
                            <div class="text-base font-bold text-indigo-900 dark:text-indigo-200">{{ $sponsor->name }}</div>
                            <div class="text-sm text-indigo-700 dark:text-indigo-400">ผู้แนะนำของคุณ 🤝</div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Action Button with 3D Effect --}}
            <a href="https://line.me/"
               target="_blank"
               class="group inline-flex items-center justify-center w-full px-8 py-4 bg-gradient-to-r from-[#06C755] to-[#00B900] hover:from-[#05B04C] hover:to-[#00A000] text-white text-lg font-bold rounded-xl transition-all duration-300 shadow-xl hover:shadow-2xl transform hover:-translate-y-1 hover:scale-[1.02]">
                <svg class="w-6 h-6 mr-3 group-hover:scale-110 transition-transform" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314" />
                </svg>
                <span>เปิดแอพ LINE ตอนนี้เลย</span>
                <svg class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                </svg>
            </a>
        </div>

        {{-- Additional Info with Dark Mode --}}
        <div class="mt-8 text-center space-y-2">
            <p class="text-sm text-gray-600 dark:text-gray-400 flex items-center justify-center gap-2">
                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>กระบวนการสมัครจะใช้เวลาประมาณ 3-5 นาที</span>
            </p>
            <p class="text-sm text-gray-600 dark:text-gray-400 flex items-center justify-center gap-2">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>หากไม่ได้รับข้อความ กรุณาตรวจสอบว่าคุณได้เพิ่มเพื่อน Bot แล้ว</span>
            </p>
        </div>
    </div>

    {{-- Dark Mode Toggle (Optional) --}}
    <script>
        // Optional: Auto dark mode based on system preference
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.documentElement.classList.add('dark');
        }
    </script>
</body>
</html>
