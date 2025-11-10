<?php
/**
 * Clear All Cache Script
 * วางไฟล์นี้ใน public/ แล้วเข้า http://your-domain/clear-cache.php
 *
 * ⚠️ ลบไฟล์นี้ทิ้งหลังใช้งานเสร็จ!
 */

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clear All Cache</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
        }
        h1 {
            color: #667eea;
            margin-bottom: 30px;
            font-size: 36px;
            text-align: center;
        }
        .status {
            padding: 15px 20px;
            border-radius: 10px;
            margin: 15px 0;
            font-weight: bold;
        }
        .success { background: #d1fae5; color: #065f46; border-left: 5px solid #10b981; }
        .icon { font-size: 64px; text-align: center; margin: 30px 0; }
        .btn {
            display: inline-block;
            padding: 15px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: bold;
            margin-top: 20px;
            text-align: center;
            display: block;
        }
        .warning {
            background: #fef3c7;
            color: #92400e;
            border-left: 5px solid #f59e0b;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        ul { margin: 20px 0 20px 30px; line-height: 2; }
        code {
            background: #1f2937;
            color: #10b981;
            padding: 2px 8px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">🧹</div>
        <h1>Clear All Cache</h1>

<?php
try {
    $results = [];

    // Clear application cache
    Artisan::call('cache:clear');
    $results[] = '✅ Application Cache';

    // Clear config cache
    Artisan::call('config:clear');
    $results[] = '✅ Config Cache';

    // Clear route cache
    Artisan::call('route:clear');
    $results[] = '✅ Route Cache';

    // Clear view cache
    Artisan::call('view:clear');
    $results[] = '✅ View Cache';

    // Clear compiled files
    Artisan::call('clear-compiled');
    $results[] = '✅ Compiled Files';

    echo '<div class="icon">🎉</div>';
    echo '<div class="status success">';
    echo '<strong>สำเร็จ! ล้าง Cache ทั้งหมดแล้ว:</strong><br><br>';
    echo implode('<br>', $results);
    echo '</div>';

    echo '<div class="warning">';
    echo '<h3 style="margin-bottom: 15px;">📋 ขั้นตอนต่อไป:</h3>';
    echo '<ul>';
    echo '<li>Clear browser cache: กด <strong>Ctrl+Shift+R</strong> (Windows/Linux) หรือ <strong>Cmd+Shift+R</strong> (Mac)</li>';
    echo '<li><strong style="color: red;">ลบไฟล์นี้ทิ้ง:</strong> <code>public/clear-cache.php</code></li>';
    echo '<li><strong style="color: red;">ลบไฟล์ restore-menu.php ด้วย:</strong> <code>public/restore-menu.php</code></li>';
    echo '<li>เปิดหน้าเว็บและทดสอบเมนู</li>';
    echo '</ul>';
    echo '</div>';

    echo '<a href="/" class="btn">🏠 กลับหน้าหลัก</a>';

} catch (Exception $e) {
    echo '<div class="status error">';
    echo '<strong>❌ เกิดข้อผิดพลาด:</strong><br>';
    echo $e->getMessage();
    echo '</div>';
}
?>
    </div>
</body>
</html>
