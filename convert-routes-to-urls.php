<?php
/**
 * 🔄 Convert Route Names to URLs in Windows UI Settings
 *
 * This script converts old route names (e.g., "admin.kyc.index")
 * to URL format (e.g., "/admin/kyc") in the database.
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\WindowsUiSetting;

echo "🔄 Converting route names to URLs...\n\n";

/**
 * Convert route name to URL
 *
 * @param string $routeName
 * @return string
 */
function routeNameToUrl($routeName) {
    if (empty($routeName) || $routeName === '#') {
        return '#';
    }

    // Remove .index, .show, .create, .edit suffixes
    $url = preg_replace('/\.(index|show|create|edit|store|update|destroy)$/', '', $routeName);

    // Replace dots with slashes
    $url = str_replace('.', '/', $url);

    // Add leading slash
    if ($url[0] !== '/') {
        $url = '/' . $url;
    }

    return $url;
}

/**
 * Convert menu items recursively
 *
 * @param array $items
 * @return array
 */
function convertMenuItems($items) {
    if (!is_array($items)) {
        return $items;
    }

    return array_map(function($item) {
        // Convert route to url if exists
        if (isset($item['route'])) {
            $oldRoute = $item['route'];
            $item['url'] = routeNameToUrl($oldRoute);
            unset($item['route']);
            echo "  ✓ Converted: {$oldRoute} -> {$item['url']}\n";
        }

        // Convert submenu items if exists
        if (isset($item['submenu']) && is_array($item['submenu'])) {
            $item['submenu'] = array_map(function($subitem) {
                if (isset($subitem['route'])) {
                    $oldRoute = $subitem['route'];
                    $subitem['url'] = routeNameToUrl($oldRoute);
                    unset($subitem['route']);
                    echo "    ✓ Submenu: {$oldRoute} -> {$subitem['url']}\n";
                }
                return $subitem;
            }, $item['submenu']);
        }

        return $item;
    }, $items);
}

// Get all Windows UI Settings
$settings = WindowsUiSetting::all();
$updatedCount = 0;

foreach ($settings as $setting) {
    $key = $setting->key;
    $value = $setting->value;

    // Only process menu-related settings
    if (strpos($key, 'menu') === false && strpos($key, 'taskbar') === false && strpos($key, 'tray') === false) {
        continue;
    }

    echo "📝 Processing: {$key}\n";

    // Decode JSON value
    if (is_string($value)) {
        $data = json_decode($value, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo "  ⚠️  Skip: Not valid JSON\n\n";
            continue;
        }
    } else {
        $data = $value;
    }

    // Check if it's an array of menu items
    if (!is_array($data) || empty($data)) {
        echo "  ⚠️  Skip: Empty or not an array\n\n";
        continue;
    }

    // Convert menu items
    $convertedData = convertMenuItems($data);

    // Check if anything was changed
    if (json_encode($data) !== json_encode($convertedData)) {
        // Update in database
        $setting->value = $convertedData;
        $setting->save();

        $updatedCount++;
        echo "  ✅ Updated!\n\n";
    } else {
        echo "  ℹ️  No changes needed\n\n";
    }
}

echo "\n";
echo "=" . str_repeat("=", 50) . "\n";
echo "✨ Conversion completed!\n";
echo "📊 Updated {$updatedCount} settings\n";
echo "=" . str_repeat("=", 50) . "\n";
echo "\n";
echo "🧹 Don't forget to clear cache:\n";
echo "   php artisan optimize:clear\n";
echo "\n";
