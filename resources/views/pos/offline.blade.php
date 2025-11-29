<!DOCTYPE html>
<html lang="th" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0f172a">
    <title>Offline - TP-POS</title>

    <link rel="manifest" href="/pos-manifest.json">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Sarabun', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            padding: 20px;
        }

        .container {
            text-align: center;
            max-width: 400px;
        }

        .icon-container {
            width: 120px;
            height: 120px;
            margin: 0 auto 32px;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border-radius: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .icon {
            font-size: 48px;
        }

        h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 16px;
            background: linear-gradient(135deg, #f59e0b, #fbbf24);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        p {
            color: rgba(255, 255, 255, 0.7);
            line-height: 1.6;
            margin-bottom: 24px;
        }

        .card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 24px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 24px;
        }

        .status-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 12px;
            margin-bottom: 8px;
        }

        .status-item:last-child {
            margin-bottom: 0;
        }

        .status-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .status-icon.success {
            background: rgba(34, 197, 94, 0.2);
        }

        .status-icon.warning {
            background: rgba(245, 158, 11, 0.2);
        }

        .status-text {
            text-align: left;
        }

        .status-text strong {
            display: block;
            font-size: 14px;
            color: white;
        }

        .status-text span {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.5);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 16px 32px;
            font-size: 16px;
            font-weight: 600;
            color: white;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            border: none;
            border-radius: 14px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(34, 197, 94, 0.3);
        }

        .btn:active {
            transform: scale(0.98);
        }

        .tips {
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .tips h3 {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.5);
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .tips ul {
            list-style: none;
            text-align: left;
        }

        .tips li {
            padding: 8px 0;
            padding-left: 24px;
            position: relative;
            color: rgba(255, 255, 255, 0.6);
            font-size: 14px;
        }

        .tips li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: #22c55e;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .floating {
            animation: float 3s ease-in-out infinite;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon-container floating">
            <div class="icon">📡</div>
        </div>

        <h1>ไม่มีการเชื่อมต่ออินเทอร์เน็ต</h1>

        <p>
            ไม่ต้องกังวล! คุณยังสามารถดำเนินการขายต่อได้ตามปกติ
            ข้อมูลจะถูกบันทึกไว้และซิงค์อัตโนมัติเมื่อกลับมา online
        </p>

        <div class="card">
            <div class="status-item">
                <div class="status-icon success">💾</div>
                <div class="status-text">
                    <strong>ข้อมูลถูกบันทึกไว้</strong>
                    <span>รายการขายจะถูกเก็บไว้ในเครื่อง</span>
                </div>
            </div>

            <div class="status-item">
                <div class="status-icon success">🔄</div>
                <div class="status-text">
                    <strong>ซิงค์อัตโนมัติ</strong>
                    <span>ข้อมูลจะซิงค์เมื่อกลับมา online</span>
                </div>
            </div>

            <div class="status-item">
                <div class="status-icon warning">📦</div>
                <div class="status-text">
                    <strong>สินค้าจาก Cache</strong>
                    <span>ใช้ข้อมูลสินค้าที่ดาวน์โหลดไว้</span>
                </div>
            </div>
        </div>

        <button class="btn" onclick="window.location.reload()">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            ลองใหม่อีกครั้ง
        </button>

        <div class="tips">
            <h3>สิ่งที่ทำได้แบบ Offline</h3>
            <ul>
                <li>ขายสินค้าจากรายการที่ดาวน์โหลดไว้</li>
                <li>รับชำระเงินสด</li>
                <li>พิมพ์ใบเสร็จ (ถ้าเครื่องพิมพ์เชื่อมต่อ)</li>
                <li>ดูประวัติการขายที่ยังไม่ซิงค์</li>
            </ul>
        </div>
    </div>

    <script>
        // ตรวจสอบการเชื่อมต่อ
        window.addEventListener('online', () => {
            // Redirect กลับไปหน้า POS เมื่อ online
            window.location.href = '/pos/app';
        });

        // พยายาม connect ทุก 5 วินาที
        setInterval(() => {
            if (navigator.onLine) {
                window.location.href = '/pos/app';
            }
        }, 5000);
    </script>
</body>
</html>
