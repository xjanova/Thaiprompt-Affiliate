<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0F0F23">
    <title>กำลังเปลี่ยนเส้นทาง... - Thaiprompt Affiliate</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background: linear-gradient(135deg, #0F0F23 0%, #1A1A2E 50%, #16213E 100%);
            min-height: 100vh;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        @keyframes pulse-ring {
            0% { transform: scale(0.8); opacity: 1; }
            100% { transform: scale(1.4); opacity: 0; }
        }
        @keyframes checkmark {
            0% { stroke-dashoffset: 50; }
            100% { stroke-dashoffset: 0; }
        }
        .pulse-ring {
            animation: pulse-ring 1.5s ease-out infinite;
        }
        .checkmark {
            stroke-dasharray: 50;
            stroke-dashoffset: 50;
            animation: checkmark 0.5s ease-out 0.3s forwards;
        }
    </style>
</head>
<body class="text-white">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-md text-center">
            {{-- Success Animation --}}
            <div class="relative inline-flex items-center justify-center mb-8">
                {{-- Pulse rings --}}
                <div class="absolute w-24 h-24 rounded-full bg-green-500/30 pulse-ring"></div>
                <div class="absolute w-24 h-24 rounded-full bg-green-500/20 pulse-ring" style="animation-delay: 0.3s;"></div>

                {{-- Success icon --}}
                <div class="relative w-20 h-20 rounded-full bg-gradient-to-br from-green-400 to-emerald-600 flex items-center justify-center shadow-lg shadow-green-500/50">
                    <svg class="w-10 h-10 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                        <path class="checkmark" stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
            </div>

            <h1 class="text-2xl font-bold mb-2">เข้าสู่ระบบสำเร็จ!</h1>
            <p class="text-gray-400 mb-8">กำลังเปลี่ยนเส้นทางกลับไปยังแอพ...</p>

            {{-- Loading indicator --}}
            <div class="glass-card rounded-2xl p-6 mb-6">
                <div class="flex items-center justify-center gap-3 mb-4">
                    <div class="w-2 h-2 bg-green-400 rounded-full animate-bounce" style="animation-delay: 0s;"></div>
                    <div class="w-2 h-2 bg-green-400 rounded-full animate-bounce" style="animation-delay: 0.15s;"></div>
                    <div class="w-2 h-2 bg-green-400 rounded-full animate-bounce" style="animation-delay: 0.3s;"></div>
                </div>
                <p class="text-gray-400 text-sm">
                    หากแอพไม่เปิดขึ้นอัตโนมัติ
                </p>
            </div>

            {{-- Manual redirect button --}}
            <a href="{{ $redirectUrl }}"
               id="manualRedirect"
               class="inline-flex items-center justify-center gap-2 w-full py-3 px-4 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-bold rounded-xl shadow-lg shadow-green-500/30 transition transform hover:scale-[1.02] active:scale-[0.98]">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                </svg>
                เปิดแอพ Thaiprompt
            </a>

            {{-- Security info --}}
            <div class="mt-8 p-4 bg-white/5 rounded-xl">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-blue-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div class="text-left">
                        <p class="text-gray-400 text-sm">
                            หน้านี้จะปิดอัตโนมัติหลังจากเปลี่ยนเส้นทางสำเร็จ
                            คุณสามารถปิดหน้านี้ได้เองหากแอพเปิดขึ้นแล้ว
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto redirect after short delay
        setTimeout(function() {
            window.location.href = '{{ $redirectUrl }}';
        }, 1500);

        // Try to close window after redirect
        setTimeout(function() {
            window.close();
        }, 3000);
    </script>
</body>
</html>
