<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครแล้ว - LINE Signup</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-50 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full">
        {{-- Already Registered Card --}}
        <div class="bg-white rounded-2xl shadow-2xl p-8 text-center">
            {{-- Success Icon --}}
            <div class="inline-flex items-center justify-center w-20 h-20 bg-blue-100 rounded-full mb-6">
                <svg class="w-10 h-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>

            {{-- Title --}}
            <h1 class="text-2xl font-bold text-gray-900 mb-3">คุณสมัครสมาชิกแล้ว</h1>

            {{-- Message --}}
            <p class="text-gray-600 mb-6">
                ลิงก์เชิญนี้ได้ถูกใช้สมัครสมาชิกเรียบร้อยแล้ว<br>
                คุณได้เป็นส่วนหนึ่งของเราแล้ว!
            </p>

            {{-- Success Box --}}
            <div class="bg-green-50 border-2 border-green-200 rounded-xl p-6 mb-6">
                <div class="flex items-center justify-center mb-4">
                    <div class="w-16 h-16 bg-green-600 rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                </div>

                <h3 class="font-semibold text-green-900 mb-2">การสมัครสำเร็จ!</h3>
                <p class="text-sm text-green-800 mb-3">
                    คุณได้สมัครสมาชิกกับเราเรียบร้อยแล้ว
                </p>

                @if(isset($prospect) && $prospect->registeredUser)
                    <div class="pt-3 border-t border-green-300">
                        <p class="text-xs text-green-700 mb-1">บัญชีของคุณ:</p>
                        <p class="text-sm font-medium text-green-900">{{ $prospect->registeredUser->name }}</p>
                        <p class="text-xs text-green-700">{{ $prospect->registeredUser->email }}</p>
                    </div>
                @endif
            </div>

            {{-- Info Box --}}
            <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-4 mb-6 text-left">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-indigo-600 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="text-sm text-indigo-800">
                        <p class="font-medium mb-1">ขั้นตอนต่อไป</p>
                        <ul class="space-y-1 list-disc list-inside">
                            <li>เข้าสู่ระบบด้วยบัญชีของคุณ</li>
                            <li>เริ่มใช้งานระบบ Affiliate</li>
                            <li>สร้างรายได้จากการแนะนำเพื่อน</li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="space-y-3">
                <a href="{{ url('/login') }}"
                   class="block w-full px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition">
                    เข้าสู่ระบบ
                </a>

                <a href="{{ url('/') }}"
                   class="block w-full px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-lg transition">
                    กลับหน้าหลัก
                </a>
            </div>
        </div>

        {{-- Additional Info --}}
        <div class="mt-6 text-center text-sm text-gray-600">
            <p>หากคุณลืมรหัสผ่าน สามารถรีเซ็ตได้ที่หน้าเข้าสู่ระบบ</p>
        </div>
    </div>
</body>
</html>
