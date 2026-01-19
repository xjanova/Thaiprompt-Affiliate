{{-- ไฟล์ทดสอบ Dashboard ชั่วคราว --}}
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Test</title>
    <style>
        body {
            font-family: 'Noto Sans Thai', sans-serif;
            padding: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 20px;
        }
        .stat-card {
            background: rgba(255, 255, 255, 0.15);
            padding: 20px;
            border-radius: 15px;
            margin: 10px 0;
        }
        .success { color: #4ade80; }
        .error { color: #f87171; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Dashboard Test Page</h1>
        <p>หน้านี้ใช้สำหรับทดสอบว่า Dashboard Controller ทำงานถูกต้องหรือไม่</p>

        <hr style="margin: 20px 0; border-color: rgba(255,255,255,0.3);">

        <h2>📊 ข้อมูลจาก Controller:</h2>

        <div class="stat-card">
            <strong>✅ Controller ทำงานสำเร็จ!</strong><br>
            หน้านี้โหลดได้แสดงว่า DashboardController::index() ทำงานปกติ
        </div>

        <div class="stat-card">
            <strong>👥 ผู้ใช้ทั้งหมด:</strong>
            <span class="success">{{ number_format($stats['total_users'] ?? 0) }}</span>
        </div>

        <div class="stat-card">
            <strong>🌐 MLM Members:</strong>
            <span class="success">{{ number_format($stats['total_affiliates'] ?? 0) }}</span>
        </div>

        <div class="stat-card">
            <strong>💰 รายได้ทั้งหมด:</strong>
            <span class="success">฿{{ number_format($stats['paid_commissions'] ?? 0, 2) }}</span>
        </div>

        <div class="stat-card">
            <strong>⏳ รอดำเนินการ:</strong>
            <span class="success">{{ number_format($stats['pending_commissions'] ?? 0) }}</span>
        </div>

        <hr style="margin: 20px 0; border-color: rgba(255,255,255,0.3);">

        <h2>🔍 การทดสอบ:</h2>
        <ul style="line-height: 2;">
            <li><strong>URL ปัจจุบัน:</strong> <code style="background: rgba(0,0,0,0.3); padding: 5px 10px; border-radius: 5px;">{{ Request::fullUrl() }}</code></li>
            <li><strong>Route name:</strong> <code style="background: rgba(0,0,0,0.3); padding: 5px 10px; border-radius: 5px;">{{ Route::currentRouteName() }}</code></li>
            <li><strong>Is Iframe Mode:</strong> <code style="background: rgba(0,0,0,0.3); padding: 5px 10px; border-radius: 5px;">{{ request()->query('iframe') === '1' ? 'YES' : 'NO' }}</code></li>
            <li><strong>Alpine.js โหลด:</strong> <span id="alpine-status" class="error">กำลังตรวจสอบ...</span></li>
            <li><strong>Layout ที่ใช้:</strong> <code style="background: rgba(0,0,0,0.3); padding: 5px 10px; border-radius: 5px;">admin-v3.blade.php</code></li>
        </ul>

        <hr style="margin: 20px 0; border-color: rgba(255,255,255,0.3);">

        <h2>✅ ลิงก์ทดสอบ:</h2>
        <ul style="line-height: 2;">
            <li><a href="{{ route('admin.dashboard') }}" style="color: #60a5fa; text-decoration: underline;">Dashboard (ปกติ)</a></li>
            <li><a href="{{ route('admin.dashboard') }}?iframe=1" style="color: #60a5fa; text-decoration: underline;">Dashboard (Iframe Mode)</a></li>
        </ul>

        <div style="margin-top: 30px; padding: 20px; background: rgba(255,255,255,0.1); border-radius: 10px;">
            <strong>💡 คำแนะนำ:</strong>
            <ol style="margin: 10px 0; padding-left: 20px;">
                <li>ถ้าหน้านี้แสดงได้ → Controller และ Data ทำงานปกติ ✅</li>
                <li>ถ้า Dashboard ปกติไม่แสดง → ปัญหาอยู่ที่ View หรือ Component ❌</li>
                <li>ถ้า Iframe Mode ไม่แสดง → ปัญหาอยู่ที่การโหลดใน iframe ❌</li>
            </ol>
        </div>
    </div>

    <script>
        // ตรวจสอบ Alpine.js
        setTimeout(function() {
            const status = document.getElementById('alpine-status');
            if (typeof Alpine !== 'undefined') {
                status.textContent = 'โหลดสำเร็จ ✅';
                status.className = 'success';
            } else {
                status.textContent = 'ไม่โหลด ❌';
                status.className = 'error';
            }
        }, 2000);
    </script>
</body>
</html>
