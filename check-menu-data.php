#!/usr/bin/env php
<?php

/**
 * Simple script to check if menu data exists in database
 * Run: php check-menu-data.php
 */

// Load Laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n";
echo "========================================\n";
echo "  Menu Data Verification Script\n";
echo "========================================\n\n";

$menuTypes = ['admin', 'seller', 'user'];

foreach ($menuTypes as $type) {
    $key = "windows_start_menu_items_{$type}";
    echo "Checking: {$key}\n";
    echo str_repeat('-', 40) . "\n";

    try {
        // Check using Model
        $data = \App\Models\WindowsUiSetting::get($key);

        if ($data && is_array($data) && count($data) > 0) {
            echo "✅ Status: DATA FOUND\n";
            echo "📊 Items: " . count($data) . "\n";

            // Show first 3 items as sample
            $sampleCount = min(3, count($data));
            echo "\n📝 Sample items:\n";
            for ($i = 0; $i < $sampleCount; $i++) {
                $item = $data[$i];
                $icon = $item['icon'] ?? '❓';
                $label = $item['label'] ?? 'N/A';
                $route = $item['route'] ?? '(submenu parent)';
                echo "   {$icon} {$label}\n";
                echo "      Route: {$route}\n";

                // Check if route exists
                if ($route && $route !== '(submenu parent)') {
                    try {
                        $url = route($route);
                        echo "      URL: {$url} ✅\n";
                    } catch (\Exception $e) {
                        echo "      ⚠️  Route '{$route}' does not exist!\n";
                    }
                }

                // Show submenu if exists
                if (isset($item['submenu']) && is_array($item['submenu'])) {
                    echo "      Submenu: " . count($item['submenu']) . " items\n";
                    foreach (array_slice($item['submenu'], 0, 2) as $subitem) {
                        $subLabel = $subitem['label'] ?? 'N/A';
                        $subRoute = $subitem['route'] ?? 'N/A';
                        echo "         → {$subLabel} ({$subRoute})\n";
                    }
                }

                echo "\n";
            }
        } else {
            echo "❌ Status: NO DATA FOUND\n";
            echo "\n";
            echo "Possible causes:\n";
            echo "1. WindowsUiSeeder has not been run\n";
            echo "2. Database connection issue\n";
            echo "3. Migration not run (table doesn't exist)\n";
            echo "\n";
            echo "Solutions:\n";
            echo "- Run: php artisan migrate\n";
            echo "- Run: php artisan db:seed --class=WindowsUiSeeder\n";
            echo "\n";
        }
    } catch (\Exception $e) {
        echo "❌ ERROR: " . $e->getMessage() . "\n";
        echo "\n";
    }

    echo "\n";
}

echo "========================================\n";
echo "  Database Table Check\n";
echo "========================================\n\n";

try {
    // Check if table exists
    $tableExists = \Schema::hasTable('windows_ui_settings');

    if ($tableExists) {
        echo "✅ Table 'windows_ui_settings' exists\n\n";

        // Count total records
        $totalRecords = \App\Models\WindowsUiSetting::count();
        echo "📊 Total records: {$totalRecords}\n\n";

        // Show all menu-related keys
        $menuKeys = \App\Models\WindowsUiSetting::where('key', 'LIKE', '%menu%')->get();
        if ($menuKeys->count() > 0) {
            echo "🔑 Menu-related keys in database:\n";
            foreach ($menuKeys as $setting) {
                echo "   - {$setting->key} (type: {$setting->type})\n";
            }
        } else {
            echo "⚠️  No menu-related keys found in database\n";
        }
    } else {
        echo "❌ Table 'windows_ui_settings' does NOT exist\n";
        echo "\n";
        echo "Solution:\n";
        echo "- Run: php artisan migrate\n";
    }
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n========================================\n";
echo "  Check Complete\n";
echo "========================================\n\n";
