<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0F0F23">
    <title>ยืนยันการเข้าสู่ระบบ - Thaiprompt Affiliate</title>
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
            {{-- Logo --}}
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-gradient-to-br from-green-500 via-emerald-500 to-teal-500 mb-4 shadow-lg shadow-green-500/30">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold">ยืนยันการเข้าสู่ระบบ</h1>
                <p class="text-gray-400 mt-2">อนุญาตให้แอพเข้าถึงบัญชีของคุณ</p>
            </div>

            {{-- Confirm Card --}}
            <div class="glass-card rounded-2xl p-6">
                {{-- User Info --}}
                <div class="flex items-center gap-4 mb-6 p-4 bg-white/5 rounded-xl">
                    <div class="w-14 h-14 rounded-full bg-gradient-to-br from-blue-500 to-purple-500 flex items-center justify-center text-xl font-bold">
                        {{ strtoupper(substr($user->name ?? $user->email, 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-white truncate">{{ $user->name ?? 'ผู้ใช้' }}</p>
                        <p class="text-gray-400 text-sm truncate">{{ $user->email }}</p>
                    </div>
                    <div class="flex-shrink-0">
                        <span class="inline-flex items-center px-2 py-1 rounded-full bg-green-500/20 text-green-400 text-xs">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            เข้าสู่ระบบแล้ว
                        </span>
                    </div>
                </div>

                {{-- Device Info --}}
                @if($deviceName)
                <div class="mb-6 p-4 bg-blue-500/10 border border-blue-500/20 rounded-xl">
                    <div class="flex items-center gap-3">
                        <svg class="w-8 h-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                        <div>
                            <p class="text-sm text-gray-400">อุปกรณ์ที่ขอเข้าสู่ระบบ</p>
                            <p class="font-semibold text-white">{{ $deviceName }}</p>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Permission Info --}}
                <div class="mb-6">
                    <h3 class="text-sm font-medium text-gray-300 mb-3">แอพจะสามารถ:</h3>
                    <ul class="space-y-2">
                        <li class="flex items-center gap-2 text-gray-400 text-sm">
                            <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            ดูข้อมูลโปรไฟล์ของคุณ
                        </li>
                        <li class="flex items-center gap-2 text-gray-400 text-sm">
                            <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            เข้าถึงข้อมูล Affiliate ของคุณ
                        </li>
                        <li class="flex items-center gap-2 text-gray-400 text-sm">
                            <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            ดูยอดเงินและทำธุรกรรม
                        </li>
                    </ul>
                </div>

                {{-- Action Buttons --}}
                <form method="POST" action="{{ route('mobile-login.authorize') }}">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <input type="hidden" name="state" value="{{ $state }}">

                    <button type="submit"
                            class="w-full py-3 px-4 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-bold rounded-xl shadow-lg shadow-green-500/30 transition transform hover:scale-[1.02] active:scale-[0.98] mb-3">
                        <span class="flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            อนุญาต
                        </span>
                    </button>
                </form>

                <form method="POST" action="{{ route('mobile-login.deny') }}">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <input type="hidden" name="state" value="{{ $state }}">

                    <button type="submit"
                            class="w-full py-3 px-4 bg-white/5 hover:bg-white/10 text-gray-300 font-medium rounded-xl border border-white/10 transition">
                        ปฏิเสธ
                    </button>
                </form>
            </div>

            {{-- Security Notice --}}
            <div class="mt-6 text-center">
                <div class="inline-flex items-center gap-2 text-gray-500 text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                    กรุณาตรวจสอบให้แน่ใจว่าคุณเปิดจากแอพที่ต้องการ
                </div>
            </div>
        </div>
    </div>
</body>
</html>
