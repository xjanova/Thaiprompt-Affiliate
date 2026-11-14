{{-- 🔐 (2026-11-03) T13 — หน้ารองรับเมื่อลิงก์เข้าห้องแม่หมอใช้ไม่ได้
     (หมดอายุ / ใช้ไปแล้ว / เสีย / บัญชีไม่ใช่ลูกค้า / ระบบยุ่ง)
     ห้ามจบที่หน้า login ของ Thaiprompt — มีปุ่มขอลิงก์ใหม่เข้าแชท (เฉพาะ FB) และปุ่มเข้าด้วย Facebook
     ⚠️ ห้ามพูดถึงเงิน/ราคา/โอน ในหน้านี้เด็ดขาด --}}
@php
    $copy = [
        'expired' => ['⏳', 'ลิงก์นี้หมดเวลาแล้วค่ะ', 'เพื่อความปลอดภัยของบัญชีคุณ ลิงก์เข้าห้องดูดวงใช้ได้ในเวลาจำกัดค่ะ'],
        'used' => ['🔐', 'ลิงก์นี้ถูกใช้ไปแล้วค่ะ', 'เพื่อความปลอดภัย ลิงก์แต่ละอันใช้เข้าห้องดูดวงได้ครั้งเดียวค่ะ'],
        'invalid' => ['🌫️', 'ลิงก์นี้เปิดไม่ได้ค่ะ', 'ลิงก์อาจไม่ครบหรือถูกตัดไประหว่างทาง ลองกลับไปทักแม่หมอในแชทอีกครั้งนะคะ'],
        'denied' => ['🙏', 'ลิงก์นี้ใช้กับบัญชีนี้ไม่ได้ค่ะ', 'ลิงก์เข้าห้องดูดวงใช้ได้กับบัญชีลูกค้าเท่านั้น กรุณาเข้าด้วย Facebook แทนนะคะ'],
        'staff_session' => ['🧑‍💼', 'ตอนนี้เบราว์เซอร์นี้ล็อกอินเป็นบัญชีทีมงานอยู่', 'ลิงก์นี้เป็นของลูกค้า ระบบจึงไม่สลับบัญชีให้ และลิงก์ยังใช้ได้สำหรับลูกค้าตามเดิมค่ะ'],
        'busy' => ['🌙', 'แม่หมอกำลังเตรียมห้องอยู่ค่ะ', 'ระบบยุ่งเล็กน้อย รอสักครู่แล้วลองเปิดลิงก์จากแชทอีกครั้งนะคะ'],
        'error' => ['🌙', 'ยังเปิดห้องแม่หมอไม่ได้ค่ะ', 'ขออภัยค่ะ ระบบขัดข้องชั่วคราว ลองใหม่อีกครั้งหรือเข้าด้วย Facebook ได้เลยค่ะ'],
        'throttled' => ['⏱️', 'เปิดลิงก์ถี่เกินไปค่ะ', 'รอสักครู่แล้วลองใหม่อีกครั้งนะคะ'],
    ];
    [$icon, $heading, $detail] = $copy[$reason] ?? $copy['expired'];

    $statusCopy = [
        'sent' => ['ok', 'แม่หมอส่งลิงก์ใหม่เข้าแชทให้แล้วค่ะ กลับไปที่ Messenger แล้วแตะปุ่ม "เข้าห้องดูดวง" ได้เลย'],
        'limited' => ['warn', 'ขอลิงก์ใหม่ครบจำนวนแล้วในชั่วโมงนี้ค่ะ ลองทักแม่หมอในแชทแทนได้เลยนะคะ'],
        'failed' => ['warn', 'ส่งลิงก์เข้าแชทไม่สำเร็จค่ะ ลองทักแม่หมอในแชทสักคำก่อน แล้วกดขอลิงก์ใหม่อีกครั้งนะคะ'],
        'unavailable' => ['warn', 'ส่งลิงก์ใหม่ให้ลิงก์นี้ไม่ได้ค่ะ ลองกลับไปทักแม่หมอในแชทอีกครั้งนะคะ'],
    ];
    $status = $resendStatus && isset($statusCopy[$resendStatus]) ? $statusCopy[$resendStatus] : null;
@endphp
<!DOCTYPE html>
<html lang="th">
<head>
    @include('web-fortune._head', ['title' => $heading.' — แม่หมอจันทรา'])
</head>
<body class="min-h-screen bg-gradient-to-br from-indigo-50 via-purple-50 to-amber-50 dark:from-gray-950 dark:via-indigo-950 dark:to-gray-900 text-gray-900 dark:text-gray-100 font-sans antialiased">
    <main class="min-h-screen flex items-center justify-center px-4 py-10">
        <div class="w-full max-w-md rounded-3xl bg-white/80 dark:bg-gray-800/70 backdrop-blur-xl shadow-2xl shadow-purple-500/10 ring-1 ring-white/60 dark:ring-white/10 p-8 text-center">
            <div class="mx-auto mb-5 flex h-20 w-20 items-center justify-center rounded-full bg-gradient-to-br from-purple-100 to-amber-100 dark:from-purple-900/60 dark:to-amber-900/40 shadow-inner">
                <span class="text-4xl" aria-hidden="true">{{ $icon }}</span>
            </div>

            <h1 class="text-xl sm:text-2xl font-bold mb-2 text-gray-900 dark:text-white">{{ $heading }}</h1>
            <p class="text-gray-600 dark:text-gray-300 mb-6 leading-relaxed">{{ $detail }}</p>

            @if ($status)
                <div role="status"
                     class="mb-6 rounded-2xl px-4 py-3 text-sm leading-relaxed {{ $status[0] === 'ok'
                        ? 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-200 dark:ring-emerald-800'
                        : 'bg-amber-50 text-amber-800 ring-1 ring-amber-200 dark:bg-amber-900/30 dark:text-amber-200 dark:ring-amber-800' }}">
                    {{ $status[1] }}
                </div>
            @endif

            <div class="space-y-3">
                @if ($canContinue)
                    <a href="{{ route('web-fortune.sso') }}"
                       class="flex w-full min-h-[48px] items-center justify-center px-6 py-3 rounded-2xl font-semibold text-white bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 dark:from-purple-500 dark:to-indigo-500 shadow-lg shadow-purple-500/30 transition">
                        🔮 ไปห้องแม่หมอต่อ
                    </a>
                @endif

                @if ($canResend && $resendStatus !== 'sent')
                    <form method="POST" action="{{ route('web-fortune.resend') }}" data-wf-once>
                        @csrf
                        <input type="hidden" name="tk" value="{{ $token }}">
                        <input type="hidden" name="reason" value="{{ $reason }}">
                        <button type="submit"
                                class="w-full min-h-[48px] px-6 py-3 rounded-2xl font-semibold text-white bg-gradient-to-r from-purple-600 to-amber-500 hover:from-purple-700 hover:to-amber-600 dark:from-purple-500 dark:to-amber-500 shadow-lg shadow-purple-500/30 transition disabled:opacity-60 disabled:cursor-wait">
                            💌 ส่งลิงก์ใหม่เข้าแชทให้หน่อย
                        </button>
                    </form>
                @endif

                @if (! $canContinue && ! empty($facebookLoginUrl))
                    <a href="{{ $facebookLoginUrl }}"
                       class="flex w-full min-h-[48px] items-center justify-center px-6 py-3 rounded-2xl font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 ring-1 ring-blue-200 dark:text-blue-200 dark:bg-blue-900/30 dark:hover:bg-blue-900/50 dark:ring-blue-800 transition">
                        เข้าด้วย Facebook
                    </a>
                @endif
            </div>

            @if ($platform === 'line')
                <p class="mt-6 text-sm text-gray-500 dark:text-gray-400">กลับไปทักแม่หมอใน LINE อีกครั้ง แม่หมอจะส่งลิงก์ใหม่ให้ค่ะ</p>
            @else
                <p class="mt-6 text-sm text-gray-500 dark:text-gray-400">หรือกลับไปทักแม่หมอในแชท Messenger ได้ทุกเมื่อนะคะ</p>
            @endif
        </div>
    </main>

    <script>
        // กันกดขอลิงก์ซ้ำระหว่างรอ (ฝั่ง server จำกัด 3 ครั้ง/ชม. อยู่แล้ว)
        document.querySelectorAll('form[data-wf-once]').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                if (form.dataset.sent) { e.preventDefault(); return; }
                form.dataset.sent = '1';
                var b = form.querySelector('button');
                if (b) { setTimeout(function () { b.disabled = true; }, 0); }
            });
        });
    </script>
</body>
</html>
