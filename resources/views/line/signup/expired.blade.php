<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลิงก์หมดอายุ - LINE Signup</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-orange-50 to-yellow-50 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full">
        {{-- Expired Card --}}
        <div class="bg-white rounded-2xl shadow-2xl p-8 text-center">
            {{-- Expired Icon --}}
            <div class="inline-flex items-center justify-center w-20 h-20 bg-orange-100 rounded-full mb-6">
                <svg class="w-10 h-10 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>

            {{-- Title --}}
            <h1 class="text-2xl font-bold text-gray-900 mb-3">ลิงก์เชิญหมดอายุแล้ว</h1>

            {{-- Message --}}
            <p class="text-gray-600 mb-6">
                ขออภัย ลิงก์เชิญนี้หมดอายุไปแล้ว<br>
                กรุณาติดต่อผู้ที่เชิญคุณเพื่อขอลิงก์ใหม่
            </p>

            @if(isset($sponsor))
                {{-- Sponsor Info --}}
                <div class="bg-indigo-50 border-2 border-indigo-200 rounded-xl p-6 mb-6">
                    <p class="text-sm text-indigo-900 mb-3 font-medium">คุณได้รับเชิญจาก:</p>
                    <div class="flex items-center justify-center">
                        <div class="w-12 h-12 bg-indigo-600 rounded-full flex items-center justify-center text-white font-bold text-lg mr-3">
                            {{ substr($sponsor->name, 0, 1) }}
                        </div>
                        <div class="text-left">
                            <div class="font-semibold text-indigo-900">{{ $sponsor->name }}</div>
                            <div class="text-sm text-indigo-700">{{ $sponsor->email }}</div>
                        </div>
                    </div>

                    <div class="mt-4 pt-4 border-t border-indigo-200">
                        <p class="text-xs text-indigo-800">
                            กรุณาติดต่อผู้แนะนำของคุณเพื่อขอลิงก์เชิญใหม่
                        </p>
                    </div>
                </div>
            @endif

            {{-- Info Box --}}
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                <div class="flex items-start text-left">
                    <svg class="w-5 h-5 text-yellow-600 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="text-sm text-yellow-800">
                        <p class="font-medium mb-1">ทำไมลิงก์หมดอายุ?</p>
                        <p>ลิงก์เชิญมีอายุ 7 วัน เพื่อความปลอดภัยและป้องกันการใช้งานที่ไม่เหมาะสม</p>
                    </div>
                </div>
            </div>

            {{-- Action Button --}}
            <a href="{{ url('/') }}"
               class="inline-block w-full px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition">
                กลับหน้าหลัก
            </a>
        </div>

        {{-- Additional Info --}}
        <div class="mt-6 text-center text-sm text-gray-600">
            <p>หากคุณต้องการสมัครสมาชิก</p>
            <p class="mt-1">กรุณาติดต่อผู้ที่เชิญคุณเพื่อขอลิงก์ใหม่</p>
        </div>
    </div>
</body>
</html>
