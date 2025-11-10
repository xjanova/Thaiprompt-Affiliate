<?php
/**
 * 🔧 Fix WindowsUiSeeder.php - Convert Route Names to URLs
 *
 * This script converts all route names in WindowsUiSeeder.php to proper URLs
 */

$seederFile = __DIR__ . '/database/seeders/WindowsUiSeeder.php';

if (!file_exists($seederFile)) {
    die("❌ Error: {$seederFile} not found\n");
}

echo "🔄 Fixing WindowsUiSeeder.php...\n\n";

$content = file_get_contents($seederFile);
$originalContent = $content;

/**
 * Convert route name to URL
 */
function convertRouteToUrl($matches) {
    $routeName = $matches[1];

    // Handle null
    if ($routeName === 'null') {
        return "'url' => '#'";
    }

    // Remove quotes if present
    $routeName = trim($routeName, "'\"");

    if (empty($routeName)) {
        return "'url' => '#'";
    }

    // Remove .index, .show, .create, .edit suffixes
    $url = preg_replace('/\.(index|show|create|edit|store|update|destroy)$/', '', $routeName);

    // Replace dots with slashes
    $url = str_replace('.', '/', $url);

    // Add leading slash
    if ($url[0] !== '/') {
        $url = '/' . $url;
    }

    return "'url' => '{$url}'";
}

// Pattern to match 'url' => 'route.name' or 'url' => null
$pattern = "/'url'\s*=>\s*(['\"]?[a-zA-Z0-9._-]+['\"]?|null)/";

$count = 0;
$content = preg_replace_callback($pattern, function($matches) use (&$count) {
    $count++;
    $result = convertRouteToUrl($matches);
    echo "  ✓ Line {$count}: {$matches[0]} -> {$result}\n";
    return $result;
}, $content);

// Write back to file
if ($content !== $originalContent) {
    file_put_contents($seederFile, $content);
    echo "\n✅ Fixed {$count} route names in WindowsUiSeeder.php\n";
    echo "📄 File updated successfully!\n";
} else {
    echo "\nℹ️  No changes needed - file is already correct\n";
}

echo "\n";
echo str_repeat("=", 70) . "\n";
echo "✨ Done!\n";
echo str_repeat("=", 70) . "\n";
