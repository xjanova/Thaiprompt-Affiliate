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
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
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

        .fade-in-up {
            animation: fadeInUp 0.8s ease-out;
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

        .animated-gradient {
            background: linear-gradient(-45deg, #06C755, #00B900, #00D564, #06C755);
            background-size: 400% 400%;
            animation: gradient 15s ease infinite;
        }
    </style>
</head>
<body class="animated-gradient min-h-screen">
    <div class="min-h-screen flex items-center justify-center px-4 py-12">
        <div class="max-w-2xl w-full">
            <div class="bg-white rounded-2xl shadow-2xl p-8 md:p-12 fade-in-up">
                <!-- LINE Logo -->
                <div class="text-center mb-8">
                    <div class="flex justify-center mb-6">
                        <svg class="w-24 h-24" viewBox="0 0 24 24" fill="#06C755">
                            <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                        </svg>
                    </div>
                    <h1 class="text-3xl md:text-4xl font-bold text-gray-800 mb-3">
                        สมัครสมาชิกผ่าน LINE OA
                    </h1>
                    <p class="text-gray-600 text-lg">
                        กรุณาทำตามขั้นตอนด้านล่างเพื่อสมัครสมาชิก
                    </p>
                </div>

                <!-- Steps -->
                <div class="space-y-6 mb-8">
                    <!-- Step 1 -->
                    <div class="flex items-start bg-gradient-to-r from-green-50 to-emerald-50 p-6 rounded-xl border border-green-200">
                        <div class="bg-green-500 text-white rounded-full w-12 h-12 flex items-center justify-center font-bold text-xl mr-4 flex-shrink-0">
                            1
                        </div>
                        <div class="flex-1">
                            <h3 class="text-xl font-bold text-gray-800 mb-2">เพิ่มเพื่อน LINE Official Account</h3>
                            <p class="text-gray-700 mb-3">
                                สแกน QR Code หรือคลิกปุ่มด้านล่างเพื่อเพิ่มเพื่อน {{ $appName }} LINE OA
                            </p>
                            @if($lineId)
                            <a href="https://line.me/R/ti/p/{{ $lineId }}" target="_blank"
                               class="inline-flex items-center px-6 py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition font-bold shadow-lg">
                                <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                                </svg>
                                เพิ่มเพื่อน LINE OA
                            </a>
                            @endif
                        </div>
                    </div>

                    <!-- Step 2 -->
                    <div class="flex items-start bg-gradient-to-r from-blue-50 to-cyan-50 p-6 rounded-xl border border-blue-200">
                        <div class="bg-blue-500 text-white rounded-full w-12 h-12 flex items-center justify-center font-bold text-xl mr-4 flex-shrink-0">
                            2
                        </div>
                        <div class="flex-1">
                            <h3 class="text-xl font-bold text-gray-800 mb-2">แชทกับบอท</h3>
                            <p class="text-gray-700">
                                ส่งข้อความใด ๆ ไปยัง LINE OA และทำตามคำแนะนำจากบอทเพื่อสมัครสมาชิก
                            </p>
                        </div>
                    </div>

                    <!-- Step 3 -->
                    <div class="flex items-start bg-gradient-to-r from-purple-50 to-pink-50 p-6 rounded-xl border border-purple-200">
                        <div class="bg-purple-500 text-white rounded-full w-12 h-12 flex items-center justify-center font-bold text-xl mr-4 flex-shrink-0">
                            3
                        </div>
                        <div class="flex-1">
                            <h3 class="text-xl font-bold text-gray-800 mb-2">รับ Link ลงทะเบียน</h3>
                            <p class="text-gray-700">
                                บอทจะส่ง Link พิเศษให้คุณเพื่อกรอกข้อมูลและลงทะเบียนให้เสร็จสมบูรณ์
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Info Box -->
                <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6 mb-8">
                    <div class="flex items-start">
                        <div class="bg-yellow-400 rounded-full p-2 mr-4 flex-shrink-0">
                            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-bold text-yellow-900 mb-2">ทำไมต้องสมัครผ่าน LINE?</h4>
                            <ul class="text-yellow-800 text-sm space-y-1">
                                <li>✓ รับการแจ้งเตือนสำคัญผ่าน LINE</li>
                                <li>✓ ติดต่อสอบถามได้สะดวกรวดเร็ว</li>
                                <li>✓ รับโปรโมชั่นและข่าวสารล่าสุด</li>
                                <li>✓ ระบบปลอดภัยมากขึ้น</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex flex-col sm:flex-row gap-4">
                    <a href="{{ route('login') }}"
                       class="flex-1 text-center px-6 py-3 bg-gray-200 text-gray-700 rounded-xl hover:bg-gray-300 transition font-bold">
                        <i class="fas fa-arrow-left mr-2"></i>
                        กลับไปหน้า Login
                    </a>
                    <a href="{{ route('home') }}"
                       class="flex-1 text-center px-6 py-3 bg-gradient-to-r from-gray-600 to-gray-700 text-white rounded-xl hover:from-gray-700 hover:to-gray-800 transition font-bold">
                        <i class="fas fa-home mr-2"></i>
                        กลับหน้าแรก
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
