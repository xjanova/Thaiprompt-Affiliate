<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เริ่มสมัครสำเร็จ - LINE Signup</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-green-50 to-blue-50 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-lg w-full">
        {{-- Success Card --}}
        <div class="bg-white rounded-2xl shadow-2xl p-8 text-center">
            {{-- Success Icon --}}
            <div class="inline-flex items-center justify-center w-20 h-20 bg-green-100 rounded-full mb-6">
                <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>

            {{-- Title --}}
            <h1 class="text-2xl font-bold text-gray-900 mb-3">เริ่มสมัครสำเร็จ!</h1>

            {{-- Message --}}
            <p class="text-gray-600 mb-6">
                ระบบได้เริ่มกระบวนการสมัครสมาชิกของคุณแล้ว
            </p>

            {{-- LINE Instructions --}}
            <div class="bg-green-50 border-2 border-green-200 rounded-xl p-6 mb-6">
                <div class="flex items-start">
                    <svg class="w-6 h-6 text-green-600 mr-3 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314" />
                    </svg>
                    <div class="text-left">
                        <h3 class="font-semibold text-green-900 mb-2">ขั้นตอนต่อไป</h3>
                        <ol class="text-sm text-green-800 space-y-2">
                            <li class="flex items-start">
                                <span class="font-bold mr-2">1.</span>
                                <span>เปิดแอพ LINE ของคุณ</span>
                            </li>
                            <li class="flex items-start">
                                <span class="font-bold mr-2">2.</span>
                                <span>คุณจะได้รับข้อความจาก Bot ของเรา</span>
                            </li>
                            <li class="flex items-start">
                                <span class="font-bold mr-2">3.</span>
                                <span>ตอบคำถามตามที่ Bot ถาม เพื่อทำการสมัครให้เสร็จสมบูรณ์</span>
                            </li>
                        </ol>
                    </div>
                </div>
            </div>

            @if(isset($sponsor))
                {{-- Sponsor Info --}}
                <div class="bg-indigo-50 border border-indigo-200 rounded-xl p-4 mb-6">
                    <p class="text-sm text-indigo-900 mb-2">
                        <strong>คุณได้รับเชิญจาก:</strong>
                    </p>
                    <div class="flex items-center justify-center">
                        <div class="w-10 h-10 bg-indigo-600 rounded-full flex items-center justify-center text-white font-bold mr-3">
                            {{ substr($sponsor->name, 0, 1) }}
                        </div>
                        <div class="text-left">
                            <div class="text-sm font-semibold text-indigo-900">{{ $sponsor->name }}</div>
                            <div class="text-xs text-indigo-700">ผู้แนะนำของคุณ</div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Action Button --}}
            <a href="https://line.me/"
               target="_blank"
               class="inline-flex items-center justify-center w-full px-6 py-3 bg-[#06C755] hover:bg-[#05B04C] text-white font-semibold rounded-lg transition shadow-lg">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314" />
                </svg>
                เปิดแอพ LINE
            </a>
        </div>

        {{-- Additional Info --}}
        <div class="mt-6 text-center text-sm text-gray-600">
            <p>กระบวนการสมัครจะใช้เวลาประมาณ 3-5 นาที</p>
            <p class="mt-2">หากไม่ได้รับข้อความจาก Bot กรุณาตรวจสอบว่าคุณได้เพิ่มเพื่อน Bot แล้ว</p>
        </div>
    </div>
</body>
</html>
