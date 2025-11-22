<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เกิดข้อผิดพลาด - LINE Signup</title>

    {{-- Vite Assets (V3) --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* Error page animations */
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-10px); }
            20%, 40%, 60%, 80% { transform: translateX(10px); }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }

        .animate-shake {
            animation: shake 0.5s ease-in-out;
        }

        .animate-fade-in {
            animation: fadeIn 0.4s ease-out;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-red-50 via-orange-50 to-pink-50 dark:from-gray-900 dark:via-red-950 dark:to-gray-900 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full animate-fade-in">
        {{-- Error Card with Glassmorphism --}}
        <div class="glass-fusion backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 rounded-3xl shadow-2xl border border-white/20 dark:border-gray-700/50 p-8 md:p-10 text-center">
            {{-- Error Icon with Shake Animation --}}
            <div class="inline-flex items-center justify-center w-24 h-24 bg-gradient-to-br from-red-400 to-orange-600 rounded-full mb-6 animate-shake">
                <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>

            {{-- Title with Gradient Text --}}
            <h1 class="text-3xl md:text-4xl font-bold bg-gradient-to-r from-red-600 to-orange-600 dark:from-red-400 dark:to-orange-400 bg-clip-text text-transparent mb-4">
                เกิดข้อผิดพลาด ⚠️
            </h1>

            {{-- Error Message --}}
            <div class="glass-fusion backdrop-blur-md bg-red-50/80 dark:bg-red-900/30 border-2 border-red-200 dark:border-red-700/50 rounded-2xl p-6 mb-8">
                <p class="text-red-800 dark:text-red-200 text-base md:text-lg font-medium">
                    {{ $message ?? 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง' }}
                </p>
            </div>

            {{-- Action Buttons with 3D Effects --}}
            <div class="space-y-3">
                <button onclick="window.history.back()"
                        class="group w-full px-6 py-4 bg-gradient-to-r from-red-600 to-orange-600 hover:from-red-700 hover:to-orange-700 text-white text-lg font-bold rounded-xl transition-all duration-300 shadow-xl hover:shadow-2xl transform hover:-translate-y-1 hover:scale-[1.02] flex items-center justify-center gap-2">
                    <svg class="w-5 h-5 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span>ย้อนกลับ</span>
                </button>

                <a href="{{ url('/') }}"
                   class="group block w-full px-6 py-4 glass-fusion backdrop-blur-md bg-gray-100/80 dark:bg-gray-700/50 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 text-lg font-semibold rounded-xl transition-all duration-300 shadow-lg hover:shadow-xl border border-gray-200 dark:border-gray-600 flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    <span>กลับหน้าหลัก</span>
                </a>
            </div>
        </div>

        {{-- Additional Help Info with Dark Mode --}}
        <div class="mt-8 glass-fusion backdrop-blur-md bg-white/60 dark:bg-gray-800/60 rounded-2xl p-6 border border-white/20 dark:border-gray-700/50">
            <div class="flex items-start gap-3 text-sm">
                <svg class="w-6 h-6 text-orange-600 dark:text-orange-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="text-left">
                    <p class="font-semibold text-gray-900 dark:text-white mb-2">ต้องการความช่วยเหลือ?</p>
                    <p class="text-gray-600 dark:text-gray-400">
                        หากปัญหายังคงอยู่ กรุณาติดต่อผู้ดูแลระบบ<br>
                        หรือลองใหม่อีกครั้งในภายหลัง
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

        // Trigger shake animation on load
        window.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                document.querySelector('.animate-shake')?.classList.remove('animate-shake');
            }, 500);
        });
    </script>
</body>
</html>
