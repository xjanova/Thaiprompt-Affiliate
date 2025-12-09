<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0F0F23">
    <title>เกิดข้อผิดพลาด - Thaiprompt Affiliate</title>
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
    </style>
</head>
<body class="text-white">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-md">
            {{-- Error Icon --}}
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-gradient-to-br from-red-500 to-red-600 mb-4 shadow-lg shadow-red-500/30">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold">เกิดข้อผิดพลาด</h1>
            </div>

            {{-- Error Card --}}
            <div class="glass-card rounded-2xl p-6">
                <div class="text-center">
                    @if($error === 'invalid_request')
                        <div class="mb-4">
                            <svg class="w-16 h-16 mx-auto text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                            </svg>
                        </div>
                        <p class="text-lg text-gray-300 mb-6">{{ $message ?? 'ลิงก์ไม่ถูกต้อง กรุณาเปิดจากแอพใหม่อีกครั้ง' }}</p>
                    @elseif($error === 'invalid_token')
                        <div class="mb-4">
                            <svg class="w-16 h-16 mx-auto text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                        </div>
                        <p class="text-lg text-gray-300 mb-6">{{ $message ?? 'Token ไม่ถูกต้องหรือหมดอายุ' }}</p>
                    @elseif($error === 'expired')
                        <div class="mb-4">
                            <svg class="w-16 h-16 mx-auto text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <p class="text-lg text-gray-300 mb-6">{{ $message ?? 'Session หมดอายุแล้ว กรุณาลองใหม่' }}</p>
                    @else
                        <div class="mb-4">
                            <svg class="w-16 h-16 mx-auto text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <p class="text-lg text-gray-300 mb-6">{{ $message ?? 'เกิดข้อผิดพลาดที่ไม่คาดคิด' }}</p>
                    @endif

                    {{-- Instructions --}}
                    <div class="bg-white/5 rounded-xl p-4 mb-6">
                        <h3 class="font-semibold text-white mb-2">วิธีแก้ไข</h3>
                        <ol class="text-left text-gray-400 text-sm space-y-2">
                            <li class="flex items-start gap-2">
                                <span class="bg-blue-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs flex-shrink-0">1</span>
                                <span>ปิดหน้าต่างนี้</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="bg-blue-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs flex-shrink-0">2</span>
                                <span>กลับไปที่แอพ Thaiprompt</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="bg-blue-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs flex-shrink-0">3</span>
                                <span>กดปุ่มเข้าสู่ระบบใหม่อีกครั้ง</span>
                            </li>
                        </ol>
                    </div>

                    {{-- Close Button --}}
                    <button onclick="window.close(); location.href='thaiprompt://auth?error={{ $error }}';"
                            class="w-full py-3 px-4 bg-gradient-to-r from-gray-600 to-gray-700 hover:from-gray-700 hover:to-gray-800 text-white font-bold rounded-xl transition">
                        ปิดหน้าต่างนี้
                    </button>
                </div>
            </div>

            {{-- Footer --}}
            <div class="mt-6 text-center">
                <p class="text-gray-500 text-sm">
                    หากพบปัญหาซ้ำ กรุณาติดต่อฝ่ายสนับสนุน
                </p>
            </div>
        </div>
    </div>
</body>
</html>
