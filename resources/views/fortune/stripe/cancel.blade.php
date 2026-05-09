{{--
    ❌ Stripe Checkout Cancel Page — ลูกค้ายกเลิก / ปิด tab Stripe
    Controller revert state ไป AWAITING_PAYMENT_METHOD แล้ว
    หน้านี้แค่บอกลูกค้ากลับไปแชทเลือกวิธีใหม่
--}}
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ยกเลิกการชำระเงิน - แม่หมอจันทรา</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'media' };
    </script>
</head>
<body class="bg-gradient-to-br from-gray-50 via-yellow-50 to-orange-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-8 text-center">
        <div class="mb-6 flex justify-center">
            <div class="w-24 h-24 bg-yellow-100 dark:bg-yellow-900/40 rounded-full flex items-center justify-center">
                <svg class="w-12 h-12 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
        </div>

        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
            ยกเลิกการชำระเงิน
        </h1>
        <p class="text-gray-600 dark:text-gray-300 mb-6">
            ไม่เป็นไรค่ะ 🙏<br>
            กลับไปที่แชทเพื่อเลือกวิธีชำระเงินใหม่ได้เลย
        </p>

        <div class="bg-purple-50 dark:bg-purple-900/30 rounded-xl p-4 mb-6 text-left text-sm">
            <p class="text-gray-700 dark:text-gray-300">
                💚 <strong>QR Code ไทย</strong> — สำหรับลูกค้าในไทย<br>
                💳 <strong>บัตรต่างประเทศ</strong> — Visa, Mastercard, AmEx
            </p>
        </div>

        @if(($platform ?? '') === 'facebook')
            <a href="https://m.me/{{ config('services.facebook.page_username', 'maemordjantra') }}"
               class="block w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-xl transition shadow-lg">
                💬 กลับไป Messenger
            </a>
        @elseif(($platform ?? '') === 'line')
            <a href="https://line.me/R/oaMessage/{{ config('services.line.fortune_oa_id', '@maemordjantra') }}"
               class="block w-full bg-green-500 hover:bg-green-600 text-white font-semibold py-3 px-6 rounded-xl transition shadow-lg">
                💚 กลับไป LINE
            </a>
        @else
            <a href="javascript:window.close()"
               class="block w-full bg-gray-600 hover:bg-gray-700 text-white font-semibold py-3 px-6 rounded-xl transition">
                ปิดหน้านี้
            </a>
        @endif
    </div>
</body>
</html>
