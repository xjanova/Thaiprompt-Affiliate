<?php
/**
 * Restore Menu Data Script
 * วางไฟล์นี้ใน public/ แล้วเข้า http://your-domain/restore-menu.php
 *
 * ⚠️ ลบไฟล์นี้ทิ้งหลังใช้งานเสร็จเพื่อความปลอดภัย!
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
    <title>Restore Menu Data</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 1200px;
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
        h2 {
            color: #764ba2;
            margin: 30px 0 15px;
            font-size: 24px;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }
        .status {
            padding: 15px 20px;
            border-radius: 10px;
            margin: 15px 0;
            font-weight: bold;
        }
        .success { background: #d1fae5; color: #065f46; border-left: 5px solid #10b981; }
        .error { background: #fee2e2; color: #991b1b; border-left: 5px solid #ef4444; }
        .warning { background: #fef3c7; color: #92400e; border-left: 5px solid #f59e0b; }
        .info { background: #dbeafe; color: #1e40af; border-left: 5px solid #3b82f6; }
        pre {
            background: #1f2937;
            color: #10b981;
            padding: 20px;
            border-radius: 10px;
            overflow-x: auto;
            margin: 15px 0;
            font-size: 14px;
            line-height: 1.6;
        }
        .btn {
            display: inline-block;
            padding: 15px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: bold;
            margin: 20px 10px 0 0;
            border: none;
            cursor: pointer;
            font-size: 16px;
            transition: transform 0.2s;
        }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(102, 126, 234, 0.5); }
        .btn-danger { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }
        .card {
            background: #f9fafb;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            padding: 20px;
            margin: 15px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        th {
            background: #667eea;
            color: white;
            font-weight: bold;
        }
        tr:hover { background: #f3f4f6; }
        .icon { font-size: 48px; text-align: center; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Restore Menu Data Script</h1>

<?php
$action = $_GET['action'] ?? 'check';

try {
    switch ($action) {
        case 'check':
            echo '<div class="icon">🔍</div>';
            echo '<h2>1️⃣ ตรวจสอบข้อมูลปัจจุบัน</h2>';

            $menuTypes = ['admin', 'seller', 'user'];

            foreach ($menuTypes as $type) {
                $key = "windows_start_menu_items_{$type}";
                $data = \App\Models\WindowsUiSetting::get($key);

                echo "<div class='card'>";
                echo "<h3 style='color: #667eea; margin-bottom: 15px;'>📋 {$type} Menu</h3>";

                if ($data && is_array($data) && count($data) > 0) {
                    echo "<div class='status success'>✅ พบข้อมูล: " . count($data) . " รายการ</div>";

                    // ตรวจสอบว่าใช้ field 'route' หรือ 'url'
                    $firstItem = $data[0];
                    $hasRoute = isset($firstItem['route']);
                    $hasUrl = isset($firstItem['url']);

                    if ($hasRoute) {
                        echo "<div class='status success'>✅ ใช้ field 'route' (ถูกต้อง)</div>";
                    } elseif ($hasUrl) {
                        echo "<div class='status error'>❌ ใช้ field 'url' (ผิด - ต้องแก้ไข)</div>";
                    }

                    // แสดงตัวอย่าง 3 รายการแรก
                    echo "<h4>ตัวอย่าง 3 รายการแรก:</h4>";
                    echo "<table>";
                    echo "<tr><th>Icon</th><th>Label</th><th>Route/URL</th><th>Submenu</th></tr>";

                    foreach (array_slice($data, 0, 3) as $item) {
                        $routeOrUrl = $item['route'] ?? $item['url'] ?? 'N/A';
                        $submenuCount = isset($item['submenu']) ? count($item['submenu']) : 0;

                        echo "<tr>";
                        echo "<td style='font-size: 24px;'>{$item['icon']}</td>";
                        echo "<td>{$item['label']}</td>";
                        echo "<td><code>{$routeOrUrl}</code></td>";
                        echo "<td>{$submenuCount} items</td>";
                        echo "</tr>";
                    }
                    echo "</table>";

                } else {
                    echo "<div class='status error'>❌ ไม่พบข้อมูล</div>";
                }

                echo "</div>";
            }

            echo '<h2>🎯 ตัวเลือกดำเนินการ</h2>';
            echo '<div class="card">';
            echo '<a href="?action=restore" class="btn btn-danger" onclick="return confirm(\'คุณแน่ใจหรือไม่? ข้อมูลเมนูปัจจุบันจะถูกลบและคืนค่าเป็น default\')">🔄 ลบข้อมูลเดิม และคืนค่า Default</a>';
            echo '<a href="?action=check" class="btn">🔍 ตรวจสอบอีกครั้ง</a>';
            echo '</div>';

            echo '<div class="status warning">';
            echo '⚠️ <strong>คำเตือน:</strong> การคืนค่า Default จะลบการปรับแต่งเมนูที่คุณทำไว้ทั้งหมด';
            echo '</div>';

            break;

        case 'restore':
            echo '<div class="icon">🔄</div>';
            echo '<h2>2️⃣ กำลังคืนค่าข้อมูลเมนู...</h2>';

            // ลบข้อมูลเดิม
            $deleted = DB::table('windows_ui_settings')
                ->whereIn('key', [
                    'windows_start_menu_items_admin',
                    'windows_start_menu_items_seller',
                    'windows_start_menu_items_user'
                ])
                ->delete();

            echo "<div class='status success'>✅ ลบข้อมูลเก่า: {$deleted} records</div>";

            // รัน seeder
            try {
                Artisan::call('db:seed', ['--class' => 'WindowsUiSeeder', '--force' => true]);
                echo "<div class='status success'>✅ รัน WindowsUiSeeder สำเร็จ</div>";
            } catch (Exception $e) {
                echo "<div class='status error'>❌ รัน seeder ล้มเหลว: " . $e->getMessage() . "</div>";
                echo "<div class='status info'>💡 กรุณารันคำสั่งนี้ใน terminal:<br><code>php artisan db:seed --class=WindowsUiSeeder --force</code></div>";
            }

            // ตรวจสอบผลลัพธ์
            echo '<h2>3️⃣ ผลลัพธ์</h2>';

            $menuTypes = ['admin', 'seller', 'user'];
            $success = true;

            foreach ($menuTypes as $type) {
                $key = "windows_start_menu_items_{$type}";
                $data = \App\Models\WindowsUiSetting::get($key);

                echo "<div class='card'>";
                echo "<h3 style='color: #667eea;'>📋 {$type} Menu</h3>";

                if ($data && is_array($data) && count($data) > 0) {
                    echo "<div class='status success'>✅ " . count($data) . " รายการ</div>";

                    // ตรวจสอบว่าใช้ field 'route'
                    $firstItem = $data[0];
                    if (isset($firstItem['route'])) {
                        echo "<div class='status success'>✅ ใช้ field 'route' (ถูกต้อง)</div>";
                    }

                    // แสดงตัวอย่าง
                    echo "<pre>" . json_encode(array_slice($data, 0, 2), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
                } else {
                    echo "<div class='status error'>❌ ไม่พบข้อมูล</div>";
                    $success = false;
                }

                echo "</div>";
            }

            if ($success) {
                echo '<div class="icon">🎉</div>';
                echo '<div class="status success" style="font-size: 18px; text-align: center;">';
                echo '✅ คืนค่าข้อมูลสำเร็จ! กรุณาทำตามขั้นตอนต่อไป:';
                echo '</div>';

                echo '<div class="card">';
                echo '<h4>📋 ขั้นตอนต่อไป:</h4>';
                echo '<ol style="line-height: 2;">';
                echo '<li>Clear Laravel cache: <code>php artisan optimize:clear</code></li>';
                echo '<li>Clear browser cache: กด <strong>Ctrl+Shift+R</strong></li>';
                echo '<li><strong style="color: red;">ลบไฟล์นี้ทิ้ง:</strong> <code>public/restore-menu.php</code></li>';
                echo '<li>Reload หน้าเว็บและทดสอบเมนู</li>';
                echo '</ol>';
                echo '</div>';
            }

            echo '<a href="?action=check" class="btn">🔍 ตรวจสอบข้อมูล</a>';

            break;
    }

} catch (Exception $e) {
    echo '<div class="status error">';
    echo '<strong>❌ เกิดข้อผิดพลาด:</strong><br>';
    echo $e->getMessage();
    echo '</div>';
    echo '<pre>' . $e->getTraceAsString() . '</pre>';
}
?>

        <div class="card" style="margin-top: 40px; background: #fef3c7; border-color: #f59e0b;">
            <h4 style="color: #92400e; margin-bottom: 10px;">⚠️ คำเตือนด้านความปลอดภัย</h4>
            <p style="color: #92400e;">ไฟล์นี้เป็นไฟล์ชั่วคราวสำหรับแก้ไขข้อมูล <strong>ต้องลบทิ้งหลังใช้งานเสร็จ</strong> เพื่อป้องกันการเข้าถึงโดยไม่ได้รับอนุญาต</p>
            <p style="color: #92400e; margin-top: 10px;">ตำแหน่งไฟล์: <code>public/restore-menu.php</code></p>
        </div>
    </div>
</body>
</html>
