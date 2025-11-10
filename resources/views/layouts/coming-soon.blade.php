<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'หน้านี้' }} - กำลังพัฒนา</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        .float-animation {
            animation: float 3s ease-in-out infinite;
        }
        @keyframes gradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .gradient-animation {
            background-size: 200% 200%;
            animation: gradient 15s ease infinite;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-purple-900 via-blue-900 to-indigo-900 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-2xl w-full">
        <!-- Card Container -->
        <div class="bg-white/10 backdrop-blur-xl rounded-3xl shadow-2xl border border-white/20 overflow-hidden">
            <!-- Gradient Header -->
            <div class="bg-gradient-to-r from-purple-600 via-pink-500 to-blue-500 gradient-animation p-8 text-center">
                <div class="float-animation mb-6">
                    <div class="w-24 h-24 mx-auto bg-white/20 backdrop-blur-lg rounded-full flex items-center justify-center border-4 border-white/30">
                        <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                        </svg>
                    </div>
                </div>
                <h1 class="text-4xl font-bold text-white mb-2">🚧 กำลังพัฒนา</h1>
                <p class="text-xl text-white/90">{{ $title ?? 'หน้านี้' }}</p>
            </div>

            <!-- Content -->
            <div class="p-8 text-center">
                <div class="space-y-6">
                    <div class="text-white/90 text-lg">
                        <p class="mb-4">
                            ขออภัย หน้านี้กำลังอยู่ระหว่างการพัฒนา
                        </p>
                        <p class="text-base text-white/70">
                            เรากำลังสร้างฟีเจอร์นี้ให้สมบูรณ์แบบที่สุด
                        </p>
                    </div>

                    <!-- Features Coming Soon -->
                    <div class="bg-white/5 backdrop-blur-sm rounded-xl p-6 border border-white/10">
                        <h3 class="text-white font-semibold mb-4 flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            กำลังจะมีฟีเจอร์
                        </h3>
                        <ul class="text-white/70 space-y-2 text-sm">
                            <li class="flex items-center justify-center gap-2">
                                <span class="w-2 h-2 bg-green-400 rounded-full"></span>
                                UI/UX ที่ทันสมัยและใช้งานง่าย
                            </li>
                            <li class="flex items-center justify-center gap-2">
                                <span class="w-2 h-2 bg-blue-400 rounded-full"></span>
                                ระบบการทำงานที่รวดเร็วและเสถียร
                            </li>
                            <li class="flex items-center justify-center gap-2">
                                <span class="w-2 h-2 bg-purple-400 rounded-full"></span>
                                การวิเคราะห์ข้อมูลที่ครบถ้วน
                            </li>
                        </ul>
                    </div>

                    <!-- Back Button -->
                    <div class="pt-4">
                        <button
                            onclick="window.history.back()"
                            class="inline-flex items-center gap-2 px-8 py-3 bg-gradient-to-r from-purple-600 to-blue-600 hover:from-purple-700 hover:to-blue-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            กลับหน้าที่แล้ว
                        </button>

                        @auth
                        <a
                            href="{{ route(auth()->user()->hasRole('admin') ? 'admin.dashboard' : (auth()->user()->hasRole('seller') ? 'seller.dashboard' : 'user.dashboard')) }}"
                            class="ml-4 inline-flex items-center gap-2 px-8 py-3 bg-white/10 hover:bg-white/20 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-300 border border-white/20">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                            </svg>
                            กลับหน้าหลัก
                        </a>
                        @endauth
                    </div>

                    <!-- Progress Indicator -->
                    <div class="pt-6">
                        <div class="text-sm text-white/50 mb-2">ความคืบหน้าการพัฒนา</div>
                        <div class="w-full bg-white/10 rounded-full h-2 overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-purple-500 via-pink-500 to-blue-500 rounded-full"
                                 style="width: 30%; animation: pulse 2s ease-in-out infinite;">
                            </div>
                        </div>
                        <div class="text-xs text-white/40 mt-2">30% เสร็จสมบูรณ์</div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="bg-white/5 backdrop-blur-sm border-t border-white/10 px-8 py-4 text-center">
                <p class="text-white/50 text-sm">
                    © {{ date('Y') }} {{ config('app.name', 'TP-Affiliate') }} - พัฒนาโดยทีมงาน Thai Prompt
                </p>
            </div>
        </div>

        <!-- Note -->
        <div class="mt-6 text-center">
            <p class="text-white/60 text-sm">
                💡 <strong>หมายเหตุ:</strong> หน้านี้เป็น placeholder ชั่วคราว เมื่อพัฒนาเสร็จจะแทนที่ด้วย controller และ view จริง
            </p>
        </div>
    </div>
</body>
</html>
