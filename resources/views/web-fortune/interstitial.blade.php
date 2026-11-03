{{-- 🔐 (2026-11-03) T13 — หน้า "กำลังพาไปหาแม่หมอ…"
     GET ไม่มีผลข้างเคียง: หน้านี้แค่ auto-submit POST (มี CSRF) ไปใช้ token จริง
     crawler ของ FB / prefetch เปิดหน้านี้ได้ แต่ไม่รัน JS ⇒ ไม่สร้างบัญชี ไม่ login ไม่กิน token
     มีปุ่มสำรองให้กดเองเมื่อ JS ถูกปิด --}}
<!DOCTYPE html>
<html lang="th">
<head>
    @include('web-fortune._head', ['title' => 'กำลังพาไปหาแม่หมอ… — แม่หมอจันทรา'])
</head>
<body class="min-h-screen bg-gradient-to-br from-indigo-50 via-purple-50 to-amber-50 dark:from-gray-950 dark:via-indigo-950 dark:to-gray-900 text-gray-900 dark:text-gray-100 font-sans antialiased">
    <main class="min-h-screen flex items-center justify-center px-4 py-10">
        <div class="w-full max-w-md rounded-3xl bg-white/80 dark:bg-gray-800/70 backdrop-blur-xl shadow-2xl shadow-purple-500/10 ring-1 ring-white/60 dark:ring-white/10 p-8 text-center">
            <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-gradient-to-br from-purple-500 to-amber-400 shadow-lg shadow-purple-500/30 animate-float">
                <span class="text-4xl" aria-hidden="true">🌙</span>
            </div>

            @php
                $autoSubmit = $autoSubmit ?? true;
                $switchFromName = $switchFromName ?? null;
            @endphp

            <h1 class="text-2xl font-bold mb-2 bg-gradient-to-r from-purple-700 to-amber-600 dark:from-purple-300 dark:to-amber-300 bg-clip-text text-transparent">
                {{ $autoSubmit ? 'กำลังพาไปหาแม่หมอ…' : 'เข้าห้องแม่หมอ' }}
            </h1>

            @if ($switchFromName !== null)
                {{-- 🔐 เบราว์เซอร์นี้ล็อกอินเป็นบัญชีอื่นอยู่ — ห้ามสลับเงียบ ๆ ต้องให้ลูกค้ากดยืนยันเอง --}}
                <div class="mb-6 rounded-2xl bg-amber-50 dark:bg-amber-900/30 ring-1 ring-amber-200 dark:ring-amber-800 p-4 text-left text-sm text-amber-900 dark:text-amber-100 leading-relaxed">
                    ตอนนี้เบราว์เซอร์นี้เข้าสู่ระบบเป็น <strong>{{ $switchFromName }}</strong> อยู่ค่ะ<br>
                    ลิงก์นี้เป็นของแชท Messenger อีกบัญชีหนึ่ง ถ้ากดด้านล่าง ระบบจะออกจากบัญชีเดิมแล้วพาเข้าบัญชีของลิงก์นี้แทน
                </div>
            @elseif (! $autoSubmit)
                <p class="text-gray-600 dark:text-gray-300 mb-8 leading-relaxed">
                    แตะปุ่มด้านล่างเพื่อเข้าห้องแม่หมอจันทราได้เลยค่ะ
                </p>
            @else
                <p class="text-gray-600 dark:text-gray-300 mb-8 leading-relaxed">
                    แม่หมอจันทรากำลังเปิดห้องรอคุณอยู่ค่ะ<br>รอสักครู่นะคะ
                </p>

                <div class="flex justify-center gap-2 mb-8" aria-hidden="true">
                    <span class="h-2.5 w-2.5 rounded-full bg-purple-500 animate-bounce"></span>
                    <span class="h-2.5 w-2.5 rounded-full bg-purple-400 animate-bounce [animation-delay:150ms]"></span>
                    <span class="h-2.5 w-2.5 rounded-full bg-amber-400 animate-bounce [animation-delay:300ms]"></span>
                </div>
            @endif

            <form id="wf-go-form" method="POST" action="{{ route('web-fortune.consume') }}" data-auto="{{ $autoSubmit ? '1' : '0' }}">
                @csrf
                <input type="hidden" name="tk" value="{{ $token }}">
                @if ($switchFromName !== null)
                    <input type="hidden" name="confirm_switch" value="1">
                @endif
                <button id="wf-go-button" type="submit"
                        class="w-full min-h-[48px] px-6 py-3 rounded-2xl font-semibold text-white bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 dark:from-purple-500 dark:to-indigo-500 shadow-lg shadow-purple-500/30 transition disabled:opacity-60 disabled:cursor-wait focus:outline-none focus-visible:ring-4 focus-visible:ring-purple-300 dark:focus-visible:ring-purple-700">
                    🔮 เข้าห้องแม่หมอ
                </button>
            </form>

            @if ($autoSubmit)
                <p class="mt-4 text-xs text-gray-500 dark:text-gray-400">
                    ถ้าหน้านี้ไม่เปลี่ยนเอง แตะปุ่มด้านบนได้เลยค่ะ
                </p>
            @endif
        </div>
    </main>

    <script>
        // ส่งฟอร์มอัตโนมัติครั้งเดียว — กันกดซ้ำ (token ใช้ได้ครั้งเดียว)
        (function () {
            var form = document.getElementById('wf-go-form');
            var button = document.getElementById('wf-go-button');
            var sent = false;
            function go() {
                if (sent) { return; }
                sent = true;
                button.disabled = true;
                form.submit();
            }
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                go();
            });
            // กดย้อนกลับมาจาก bfcache → เปิดปุ่มให้กดเองได้อีกครั้ง (ไม่ auto-submit ซ้ำ)
            window.addEventListener('pageshow', function (e) {
                if (e.persisted) {
                    sent = false;
                    button.disabled = false;
                }
            });
            // auto-submit เฉพาะเปิดจากแอปแชท และไม่ได้สลับบัญชี (กัน login CSRF)
            if (form.dataset.auto === '1') {
                setTimeout(go, 400);
            }
        })();
    </script>
</body>
</html>
