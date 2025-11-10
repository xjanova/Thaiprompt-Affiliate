<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เกิดข้อผิดพลาด - LINE Signup</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-red-50 to-orange-50 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full">
        {{-- Error Card --}}
        <div class="bg-white rounded-2xl shadow-2xl p-8 text-center">
            {{-- Error Icon --}}
            <div class="inline-flex items-center justify-center w-20 h-20 bg-red-100 rounded-full mb-6">
                <svg class="w-10 h-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>

            {{-- Title --}}
            <h1 class="text-2xl font-bold text-gray-900 mb-3">เกิดข้อผิดพลาด</h1>

            {{-- Message --}}
            <p class="text-gray-600 mb-8">
                {{ $message ?? 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง' }}
            </p>

            {{-- Action Buttons --}}
            <div class="space-y-3">
                <button onclick="window.history.back()"
                        class="w-full px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition">
                    ← ย้อนกลับ
                </button>

                <a href="{{ url('/') }}"
                   class="block w-full px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-lg transition">
                    กลับหน้าหลัก
                </a>
            </div>
        </div>

        {{-- Additional Info --}}
        <div class="mt-6 text-center text-sm text-gray-600">
            <p>หากปัญหายังคงอยู่ กรุณาติดต่อผู้ดูแลระบบ</p>
        </div>
    </div>
</body>
</html>
