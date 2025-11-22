<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลิงก์หมดอายุ - LINE Signup</title>

    {{-- Vite Assets (V3) --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* Expired page animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animate-fade-in {
            animation: fadeIn 0.5s ease-out;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-orange-50 via-amber-50 to-yellow-50 dark:from-gray-900 dark:via-orange-950 dark:to-gray-900 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full animate-fade-in">
        {{-- Expired Card with Glassmorphism --}}
        <div class="glass-fusion backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 rounded-3xl shadow-2xl border border-white/20 dark:border-gray-700/50 p-8 md:p-10 text-center">
            {{-- Clock Icon --}}
            <div class="inline-flex items-center justify-center w-24 h-24 bg-gradient-to-br from-orange-400 to-amber-600 rounded-full mb-6">
                <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>

            {{-- Title with Gradient Text --}}
            <h1 class="text-3xl md:text-4xl font-bold bg-gradient-to-r from-orange-600 to-amber-600 dark:from-orange-400 dark:to-amber-400 bg-clip-text text-transparent mb-4">
                ลิงก์หมดอายุแล้ว ⏰
            </h1>

            {{-- Message --}}
            <div class="glass-fusion backdrop-blur-md bg-orange-50/80 dark:bg-orange-900/30 border-2 border-orange-200 dark:border-orange-700/50 rounded-2xl p-6 mb-8">
                <p class="text-orange-800 dark:text-orange-200 text-base md:text-lg leading-relaxed">
                    ขออภัย ลิงก์เชิญนี้หมดอายุไปแล้ว<br>
                    กรุณาติดต่อผู้ที่เชิญคุณเพื่อขอลิงก์ใหม่
                </p>
            </div>

            @if(isset($sponsor))
                {{-- Sponsor Info --}}
                <div class="glass-fusion backdrop-blur-md bg-gradient-to-br from-blue-50/80 to-indigo-50/80 dark:from-blue-900/30 dark:to-indigo-900/30 border-2 border-blue-200 dark:border-blue-700/50 rounded-2xl p-6 mb-8">
                    <p class="text-sm text-blue-700 dark:text-blue-300 mb-3 font-semibold">
                        ผู้แนะนำของคุณ:
                    </p>
                    <div class="flex items-center justify-center gap-4">
                        <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full flex items-center justify-center text-white font-bold text-lg shadow-lg">
                            {{ substr($sponsor->name ?? 'U', 0, 1) }}
                        </div>
                        <div class="text-left">
                            <div class="text-base font-bold text-blue-900 dark:text-blue-200">{{ $sponsor->name ?? 'ผู้แนะนำ' }}</div>
                            <div class="text-sm text-blue-700 dark:text-blue-400">ติดต่อเพื่อขอลิงก์ใหม่ 📧</div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Action Buttons --}}
            <div class="space-y-3">
                @if(isset($newInviteUrl))
                    <a href="{{ $newInviteUrl }}"
                       class="group inline-flex items-center justify-center w-full px-8 py-4 bg-gradient-to-r from-orange-600 to-amber-600 hover:from-orange-700 hover:to-amber-700 text-white text-lg font-bold rounded-xl transition-all duration-300 shadow-xl hover:shadow-2xl transform hover:-translate-y-1 hover:scale-[1.02]">
                        <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span>ลองอีกครั้งด้วยลิงก์ใหม่</span>
                    </a>
                @endif

                <a href="{{ url('/') }}"
                   class="group block w-full px-6 py-4 glass-fusion backdrop-blur-md bg-gray-100/80 dark:bg-gray-700/50 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 text-lg font-semibold rounded-xl transition-all duration-300 shadow-lg hover:shadow-xl border border-gray-200 dark:border-gray-600 flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    <span>กลับหน้าหลัก</span>
                </a>
            </div>
        </div>

        {{-- Additional Info --}}
        <div class="mt-8 glass-fusion backdrop-blur-md bg-white/60 dark:bg-gray-800/60 rounded-2xl p-6 border border-white/20 dark:border-gray-700/50">
            <div class="flex items-start gap-3 text-sm">
                <svg class="w-6 h-6 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="text-left">
                    <p class="font-semibold text-gray-900 dark:text-white mb-2">💡 คำแนะนำ</p>
                    <p class="text-gray-600 dark:text-gray-400">
                        ลิงก์เชิญมักจะหมดอายุหลังจาก 24-48 ชั่วโมง<br>
                        กรุณาขอลิงก์ใหม่จากผู้แนะนำของคุณ
                    </p>
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
