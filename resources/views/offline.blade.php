<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#111827">
    <title>ไม่มีอินเทอร์เน็ต - TP-Affiliate</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #111827;
            color: white;
            font-family: 'Inter', 'Noto Sans Thai', system-ui, sans-serif;
            padding: 24px;
        }
        .container {
            text-align: center;
            max-width: 400px;
        }
        .icon {
            font-size: 80px;
            margin-bottom: 24px;
            animation: float 3s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        h1 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 12px;
            background: linear-gradient(to right, #8B5CF6, #EC4899);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        p {
            color: #9ca3af;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 32px;
        }
        .btn {
            display: inline-block;
            padding: 14px 36px;
            background: linear-gradient(135deg, #8B5CF6, #EC4899);
            color: white;
            border: none;
            border-radius: 16px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn:active {
            transform: scale(0.95);
        }
        .status {
            margin-top: 32px;
            padding: 12px 20px;
            background: rgba(255,255,255,0.05);
            border-radius: 12px;
            font-size: 14px;
            color: #6b7280;
        }
        .status.online {
            background: rgba(34, 197, 94, 0.1);
            color: #22c55e;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">📴</div>
        <h1>ไม่มีการเชื่อมต่ออินเทอร์เน็ต</h1>
        <p>ขณะนี้คุณไม่ได้เชื่อมต่ออินเทอร์เน็ต<br>กรุณาตรวจสอบการเชื่อมต่อแล้วลองอีกครั้ง</p>
        <button class="btn" onclick="location.reload()">ลองใหม่อีกครั้ง</button>
        <div class="status" id="status">
            กำลังรอการเชื่อมต่อ...
        </div>
    </div>

    <script>
        /**
         * ตรวจจับเมื่อกลับมา online อัตโนมัติ
         */
        window.addEventListener('online', () => {
            const el = document.getElementById('status');
            el.className = 'status online';
            el.textContent = '✅ เชื่อมต่อสำเร็จแล้ว! กำลังโหลดหน้าเว็บ...';
            setTimeout(() => location.reload(), 1000);
        });

        // แสดงสถานะปัจจุบัน
        if (navigator.onLine) {
            const el = document.getElementById('status');
            el.className = 'status online';
            el.textContent = '✅ เชื่อมต่ออินเทอร์เน็ตแล้ว';
            setTimeout(() => location.reload(), 500);
        }
    </script>
</body>
</html>
