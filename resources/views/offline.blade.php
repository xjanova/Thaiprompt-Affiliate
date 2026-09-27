<!DOCTYPE html>
{{--
 | หน้าไม่มีอินเทอร์เน็ต (service worker เสิร์ฟจาก cache ตอนออฟไลน์) — ธีมโนวา
 | ⚠️ หน้านี้ต้องอยู่ได้โดยไม่มีเน็ต: CSS อยู่ในไฟล์ทั้งหมด ไม่พึ่งฟอนต์/CSS ภายนอก
 |    รูปตะเกียงอยู่ใน PRECACHE_ASSETS ของ public/service-worker.js (ถ้าโหลดไม่ได้ก็ซ่อนเอง)
 --}}
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0d1b3d">
    <meta name="robots" content="noindex">
    <title>ไม่มีอินเทอร์เน็ต - Thai Prompt</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            min-height: 100vh; min-height: 100svh;
            display: flex; align-items: center; justify-content: center;
            padding: 24px;
            color: #fbf6ea;
            font-family: 'Anuphan', 'Noto Sans Thai', 'Leelawadee UI', system-ui, sans-serif;
            background:
                radial-gradient(900px 520px at 50% -10%, rgba(29, 54, 118, .75) 0%, rgba(13, 27, 61, 0) 70%),
                radial-gradient(600px 400px at 50% 110%, rgba(240, 201, 106, .10) 0%, rgba(240, 201, 106, 0) 70%),
                linear-gradient(180deg, #0d1b3d 0%, #0a1530 55%, #060b1c 100%);
        }
        .card {
            position: relative; width: min(440px, 100%);
            padding: 34px 28px 28px; border-radius: 28px; text-align: center;
            background: linear-gradient(160deg, rgba(20, 36, 84, .78), rgba(7, 13, 32, .9));
            border: 1px solid rgba(245, 210, 127, .26);
            box-shadow: 0 40px 90px -36px rgba(0, 0, 0, .9), inset 0 1px 0 rgba(255, 255, 255, .06);
        }
        .deco { display: block; width: 116px; height: auto; margin: -92px auto 4px; filter: drop-shadow(0 20px 24px rgba(0, 0, 0, .55)); animation: float 6s ease-in-out infinite; }
        .icon { width: 64px; height: 64px; margin: 0 auto 14px; border-radius: 20px; display: grid; place-items: center; background: linear-gradient(180deg, #fbe3a8, #d4a64a); box-shadow: inset 0 1px 0 rgba(255, 255, 255, .7), 0 12px 26px -12px rgba(240, 201, 106, .8); }
        .icon svg { width: 32px; height: 32px; color: #1a1405; }
        @keyframes float { 0%, 100% { transform: translateY(0) rotate(-3deg); } 50% { transform: translateY(-10px) rotate(3deg); } }
        h1 {
            font-size: 24px; font-weight: 700; line-height: 1.35; margin-bottom: 10px;
            background: linear-gradient(100deg, #fff1c7 0%, #f5d27f 30%, #d4a64a 58%, #fbe3a8 80%, #c8962f 100%);
            -webkit-background-clip: text; background-clip: text;
            -webkit-text-fill-color: transparent; color: #f5d27f;
        }
        p { color: rgba(246, 239, 221, .75); font-size: 16px; line-height: 1.7; margin-bottom: 26px; }
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            min-height: 48px; padding: 0 32px; border: 0; border-radius: 999px;
            font: inherit; font-size: 16px; font-weight: 600; cursor: pointer; text-decoration: none;
            color: #1a1405; background: linear-gradient(180deg, #fbe3a8 0%, #f0c96a 40%, #d4a64a 100%);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, .7), inset 0 -2px 0 rgba(138, 100, 32, .35), 0 12px 28px -12px rgba(240, 201, 106, .8);
            transition: transform .2s;
        }
        .btn:active { transform: scale(.96); }
        .btn:focus-visible { outline: 2px solid #fbe3a8; outline-offset: 3px; }
        .status { margin-top: 24px; padding: 11px 16px; border-radius: 14px; font-size: 14px; color: rgba(246, 239, 221, .6); background: rgba(255, 255, 255, .04); border: 1px solid rgba(245, 210, 127, .14); }
        .status.online { color: #f5d27f; border-color: rgba(245, 210, 127, .4); background: rgba(240, 201, 106, .08); }
        @media (prefers-reduced-motion: reduce) { .deco { animation: none; } }
    </style>
</head>
<body>
    <main class="card">
        <img class="deco" src="/images/nova/deco/lamp.webp" alt="" aria-hidden="true" onerror="this.remove()">
        <div class="icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 2l20 20"/><path d="M8.5 16.5a5 5 0 0 1 7 0"/><path d="M5 12.9a10 10 0 0 1 5.2-2.8"/><path d="M19 12.9a10 10 0 0 0-2.1-1.6"/><path d="M1.5 8.8a15 15 0 0 1 4.3-2.6"/><path d="M22.5 8.8A15 15 0 0 0 11 5.1"/><path d="M12 20h.01"/></svg>
        </div>
        <h1>ไม่มีการเชื่อมต่ออินเทอร์เน็ต</h1>
        <p>ขณะนี้เชื่อมต่อไม่ได้<br>ตรวจสอบ Wi-Fi หรือเน็ตมือถือ แล้วลองอีกครั้ง</p>
        <button type="button" class="btn" id="retry">ลองใหม่อีกครั้ง</button>
        <div class="status" id="status" role="status" aria-live="polite">กำลังรอการเชื่อมต่อ...</div>
    </main>

    <script>
        /**
         * กลับไปหน้าที่ผู้ใช้ตั้งใจเปิด
         * - service worker เสิร์ฟหน้านี้แทนหน้าเดิม (URL ยังเป็นหน้าเดิม) → reload
         * - เปิด /offline ตรงๆ → ไปหน้าแรก (เดิม reload ตัวเองวนไม่รู้จบตอนออนไลน์)
         */
        function nvGoBack() {
            if (location.pathname.replace(/\/+$/, '') === '/offline') {
                location.href = '/';
            } else {
                location.reload();
            }
        }

        var nvStatus = document.getElementById('status');
        document.getElementById('retry').addEventListener('click', nvGoBack);

        // กลับมาออนไลน์ → โหลดหน้าเดิมให้เอง
        window.addEventListener('online', function () {
            nvStatus.className = 'status online';
            nvStatus.textContent = 'เชื่อมต่อได้แล้ว กำลังโหลดหน้าเว็บ...';
            setTimeout(nvGoBack, 1000);
        });

        // เน็ตมือถือใช้ได้แต่ยังเข้าเซิร์ฟเวอร์ไม่ได้ → ไม่โหลดซ้ำเอง (เดิมโหลดทุกครึ่งวินาทีวนไม่หยุด) ให้ผู้ใช้กดลองใหม่
        if (navigator.onLine) {
            nvStatus.className = 'status online';
            nvStatus.textContent = location.pathname.replace(/\/+$/, '') === '/offline'
                ? 'เชื่อมต่ออินเทอร์เน็ตแล้ว กดปุ่มเพื่อกลับหน้าแรก'
                : 'อินเทอร์เน็ตใช้ได้ แต่ยังติดต่อเว็บไม่ได้ ลองใหม่อีกครั้งในอีกสักครู่';
        }
    </script>
</body>
</html>
