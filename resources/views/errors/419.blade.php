<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>419 - Session หมดอายุ | {{ config('app.name', 'TP-Affiliate') }}</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

    <style>
        body {
            font-family: 'Noto Sans Thai', 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
    </style>

    {{-- Auto-redirect กลับหน้าเดิมหลัง 2 วินาที --}}
    <script>
        // รอ 2 วินาทีแล้ว redirect กลับไปหน้าเดิม (GET request จะสร้าง CSRF token ใหม่)
        setTimeout(function() {
            var backUrl = document.referrer || '/';
            window.location.href = backUrl;
        }, 2000);
    </script>
</head>
<body class="flex items-center justify-center p-4">
    <div class="max-w-md w-full text-center">
        {{-- Icon --}}
        <div class="mb-6">
            <div class="inline-flex items-center justify-center w-24 h-24 bg-white/20 backdrop-blur-lg rounded-full">
                <i class="fas fa-clock text-5xl text-white"></i>
            </div>
        </div>

        {{-- Message --}}
        <h1 class="text-3xl font-bold text-white mb-3">Session หมดอายุ</h1>
        <p class="text-white/80 mb-6">
            กรุณารอสักครู่... กำลังพากลับไปหน้าเดิม
        </p>

        {{-- Loading --}}
        <div class="mb-8">
            <div class="inline-flex items-center gap-2 text-white/70 text-sm">
                <i class="fas fa-spinner fa-spin"></i>
                <span>กำลังโหลดใหม่...</span>
            </div>
        </div>

        {{-- Manual buttons --}}
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <button onclick="window.history.back()"
                    class="px-6 py-3 bg-white text-purple-700 font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all hover:-translate-y-0.5">
                <i class="fas fa-arrow-left mr-2"></i>ย้อนกลับ
            </button>
            <a href="/"
               class="px-6 py-3 bg-white/20 backdrop-blur text-white font-semibold rounded-xl border border-white/30 hover:bg-white/30 transition-all hover:-translate-y-0.5">
                <i class="fas fa-home mr-2"></i>หน้าแรก
            </a>
        </div>

        {{-- Tip --}}
        <p class="mt-8 text-white/50 text-xs">
            <i class="fas fa-info-circle mr-1"></i>
            ถ้าเปิดหน้าทิ้งไว้นาน ระบบจะหมดอายุอัตโนมัติ กรุณาลองใหม่อีกครั้ง
        </p>
    </div>
</body>
</html>
