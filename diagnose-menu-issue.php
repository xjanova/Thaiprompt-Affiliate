#!/usr/bin/env php
<?php

/**
 * สคริปต์ตรวจสอบปัญหาเมนู TPIX & Token Management
 *
 * วิธีใช้: php diagnose-menu-issue.php
 */

echo "🔍 กำลังวิเคราะห์ปัญหาเมนู TPIX Blockchain & Token Management...\n";
echo str_repeat("=", 60) . "\n\n";

// ตรวจสอบว่า Laravel bootstrap ได้หรือไม่
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    echo "❌ ERROR: vendor/autoload.php ไม่พบ\n";
    echo "   กรุณารัน: composer install\n\n";
    exit(1);
}

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Route;

// รายการ routes ที่ต้องตรวจสอบ
$requiredRoutes = [
    // TPIX Blockchain
    'admin.tpix.dashboard',
    'admin.tpix.network-status',
    'admin.tpix.wallets',
    'admin.tpix.transactions',
    'admin.tpix.settings',

    // Token Management
    'admin.tokens.index',
    'admin.tokens.import-cmc',
];

echo "1️⃣  ตรวจสอบ Routes\n";
echo str_repeat("-", 60) . "\n";

$allRoutes = Route::getRoutes();
$foundRoutes = [];
$missingRoutes = [];

foreach ($requiredRoutes as $routeName) {
    if (Route::has($routeName)) {
        $route = Route::getRoutes()->getByName($routeName);
        $foundRoutes[] = $routeName;
        echo "  ✅ {$routeName}\n";
        echo "     → " . $route->uri() . "\n";
    } else {
        $missingRoutes[] = $routeName;
        echo "  ❌ {$routeName} (ไม่พบ!)\n";
    }
}

echo "\n";

// สรุปผล
echo "2️⃣  สรุปผลการตรวจสอบ\n";
echo str_repeat("-", 60) . "\n";
echo "  Routes ที่พบ: " . count($foundRoutes) . "/" . count($requiredRoutes) . "\n";
echo "  Routes ที่หายไป: " . count($missingRoutes) . "\n";
echo "\n";

if (count($missingRoutes) > 0) {
    echo "❌ ปัญหา: มี Routes ที่หายไป!\n\n";
    echo "🔧 วิธีแก้:\n";
    echo "   1. ตรวจสอบว่า routes/admin.php มี route definitions สำหรับ:\n";
    foreach ($missingRoutes as $route) {
        echo "      - {$route}\n";
    }
    echo "\n   2. ตรวจสอบว่า bootstrap/app.php โหลด routes/admin.php\n";
    echo "\n   3. ล้าง route cache:\n";
    echo "      php artisan route:clear\n";
    echo "      php artisan route:cache\n\n";
} else {
    echo "✅ Routes ทั้งหมดพร้อมใช้งาน!\n\n";

    echo "🎯 ถ้าเมนูยังไม่ทำงาน ให้ตรวจสอบ:\n\n";

    echo "   A. Cache ปัญหา:\n";
    echo "      php artisan config:clear\n";
    echo "      php artisan cache:clear\n";
    echo "      php artisan view:clear\n";
    echo "      php artisan route:cache\n";
    echo "      php artisan config:cache\n\n";

    echo "   B. JavaScript Console:\n";
    echo "      1. เปิด Developer Tools (F12)\n";
    echo "      2. ไปที่ Console tab\n";
    echo "      3. คลิกเมนู TPIX Blockchain หรือ Token Management\n";
    echo "      4. ดูว่ามี JavaScript errors หรือไม่\n\n";

    echo "   C. Network Tab:\n";
    echo "      1. เปิด Developer Tools (F12)\n";
    echo "      2. ไปที่ Network tab\n";
    echo "      3. คลิกลิงก์ในเมนูย่อย (เช่น Dashboard)\n";
    echo "      4. ดูว่า request ไปที่ไหน และได้ response อะไร\n\n";

    echo "   D. Laravel Logs:\n";
    echo "      tail -f storage/logs/laravel.log\n\n";
}

// ตรวจสอบ Controllers
echo "3️⃣  ตรวจสอบ Controllers\n";
echo str_repeat("-", 60) . "\n";

$controllers = [
    'App\Http\Controllers\Admin\TPIXController',
    'App\Http\Controllers\Admin\TokenManagementController',
];

foreach ($controllers as $controller) {
    if (class_exists($controller)) {
        echo "  ✅ {$controller}\n";

        // List methods
        $reflection = new ReflectionClass($controller);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        echo "     Methods: ";
        $publicMethods = [];
        foreach ($methods as $method) {
            if ($method->class === $controller && !in_array($method->name, ['__construct', 'middleware'])) {
                $publicMethods[] = $method->name;
            }
        }
        echo implode(', ', $publicMethods) . "\n";
    } else {
        echo "  ❌ {$controller} (ไม่พบ!)\n";
    }
}

echo "\n";

// ตรวจสอบ Views
echo "4️⃣  ตรวจสอบ Views\n";
echo str_repeat("-", 60) . "\n";

$views = [
    'admin.tpix.dashboard',
    'admin.tpix.network-status',
    'admin.tpix.wallets',
    'admin.tpix.transactions',
    'admin.tpix.settings',
    'admin.tokens.import-cmc',
];

foreach ($views as $view) {
    $viewPath = resource_path('views/' . str_replace('.', '/', $view) . '.blade.php');
    if (file_exists($viewPath)) {
        echo "  ✅ {$view}\n";
        echo "     → {$viewPath}\n";
    } else {
        echo "  ❌ {$view} (ไม่พบ!)\n";
    }
}

echo "\n";
echo str_repeat("=", 60) . "\n";
echo "✨ การวิเคราะห์เสร็จสิ้น!\n\n";
