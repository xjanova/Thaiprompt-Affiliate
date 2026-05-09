{{--
    💳 Stripe Checkout Success Page — ลูกค้าจ่ายเสร็จกลับมาหน้านี้

    Layout: minimal standalone (ไม่ใช้ admin/user layout เพราะ public)
    Mobile-first + dark mode auto via prefers-color-scheme
--}}
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ชำระเงินสำเร็จ - แม่หมอจันทรา</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'media' };
    </script>
</head>
<body class="bg-gradient-to-br from-purple-50 via-pink-50 to-yellow-50 dark:from-gray-900 dark:via-purple-950 dark:to-gray-900 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-8 text-center">
        {{-- ✅ Success Icon --}}
        <div class="mb-6 flex justify-center">
            <div class="w-24 h-24 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center animate-bounce">
                <svg class="w-12 h-12 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
        </div>

        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
            ชำระเงินสำเร็จ! 🎉
        </h1>
        <p class="text-gray-600 dark:text-gray-300 mb-6">
            ขอบคุณค่ะ เจ้าชะตา 🙏<br>
            แม่หมอจันทราจะเริ่มทำนายให้ทันที
        </p>

        @if($reading)
            <div class="bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/30 dark:to-pink-900/30 rounded-xl p-4 mb-6 text-left">
                <div class="text-sm text-gray-600 dark:text-gray-400">เลขที่บิล</div>
                <div class="font-mono font-semibold text-gray-900 dark:text-white">{{ $reading->bill_reference ?? '-' }}</div>

                <div class="text-sm text-gray-600 dark:text-gray-400 mt-3">ยอดชำระ</div>
                <div class="font-bold text-2xl text-purple-600 dark:text-purple-400">
                    {{ number_format($reading->amount_paid, 0) }} บาท
                </div>
            </div>
        @endif

        {{-- 📱 ลิงก์กลับไปแชท --}}
        <div class="space-y-3">
            <p class="text-sm text-gray-700 dark:text-gray-300 font-medium">
                🔮 กลับไปที่แชทเพื่อดูคำทำนาย
            </p>

            @if(($platform ?? '') === 'facebook')
                <a href="https://m.me/{{ config('services.facebook.page_username', 'maemordjantra') }}"
                   class="block w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-xl transition shadow-lg">
                    💬 เปิด Messenger
                </a>
            @elseif(($platform ?? '') === 'line')
                <a href="https://line.me/R/oaMessage/{{ config('services.line.fortune_oa_id', '@maemordjantra') }}"
                   class="block w-full bg-green-500 hover:bg-green-600 text-white font-semibold py-3 px-6 rounded-xl transition shadow-lg">
                    💚 เปิด LINE
                </a>
            @else
                <a href="javascript:window.close()"
                   class="block w-full bg-gray-600 hover:bg-gray-700 text-white font-semibold py-3 px-6 rounded-xl transition">
                    ปิดหน้านี้
                </a>
            @endif
        </div>

        <p class="text-xs text-gray-500 dark:text-gray-400 mt-6">
            ✨ คำทำนายจะส่งให้ในแชทภายใน 1-2 นาที
        </p>
    </div>
</body>
</html>
