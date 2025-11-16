#!/usr/bin/env php
<?php
/**
 * V3 Theme Migration Script
 *
 * แปลง Blade views จาก layout เก่าเป็น V3 theme system อัตโนมัติ
 *
 * การใช้งาน:
 * php scripts/migrate-to-v3-theme.php <file-path>
 * php scripts/migrate-to-v3-theme.php resources/views/admin/analytics/performance.blade.php
 */

if ($argc < 2) {
    echo "Usage: php migrate-to-v3-theme.php <file-path>\n";
    echo "Example: php migrate-to-v3-theme.php resources/views/admin/analytics/performance.blade.php\n";
    exit(1);
}

$filePath = $argv[1];

if (!file_exists($filePath)) {
    echo "❌ Error: File not found: {$filePath}\n";
    exit(1);
}

echo "🔄 Migrating: {$filePath}\n";

$content = file_get_contents($filePath);
$original = $content;

// 1. เปลี่ยน layout
$content = preg_replace("/@extends\('layouts\.admin'\)/", "@extends('layouts.admin-v3')", $content);
$content = preg_replace("/@extends\(\"layouts\.admin\"\)/", "@extends('layouts.admin-v3')", $content);

// 2. แทนที่ bg-white ด้วย glass-fusion
$content = preg_replace('/\bbg-white\b(?!\/)/', 'glass-fusion', $content);
$content = preg_replace('/\bbg-white\/(\d+)\b/', 'glass-fusion', $content);

// 3. เพิ่ม dark mode สำหรับ text colors
$content = preg_replace('/\btext-gray-800\b/', 'text-gray-900 dark:text-white', $content);
$content = preg_replace('/\btext-gray-700\b/', 'text-gray-700 dark:text-gray-300', $content);
$content = preg_replace('/\btext-gray-600\b/', 'text-gray-600 dark:text-gray-400', $content);
$content = preg_replace('/\btext-gray-500\b/', 'text-gray-500 dark:text-gray-400', $content);

// 4. แทนที่สี hardcoded ด้วย CSS Variables (สำหรับ inline styles)
$colorMap = [
    'rgb\(147, 51, 234\)' => "getCSSColor('--arrow-x-accent')",
    'rgb\(249, 115, 22\)' => "getCSSColor('--arrow-x-warning')",
    'rgb\(34, 197, 94\)' => "getCSSColor('--arrow-x-success')",
    'rgb\(239, 68, 68\)' => "getCSSColor('--arrow-x-error')",
    'rgb\(59, 130, 246\)' => "getCSSColor('--arrow-x-primary-start')",
];

foreach ($colorMap as $old => $new) {
    $content = str_replace($old, $new, $content);
}

// 5. เพิ่ม border สำหรับ glass-fusion
$content = preg_replace('/(<div[^>]*class="[^"]*glass-fusion[^"]*")/', '$1 border border-white/20 dark:border-white/10', $content);

// 6. แปลง gradient backgrounds ให้ใช้ CSS Variables
$gradientPatterns = [
    '/bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600/' => 'style="background: var(--arrow-x-primary-gradient)"',
    '/bg-gradient-to-r from-green-600 via-emerald-600 to-teal-600/' => 'style="background: var(--arrow-x-success-gradient)"',
    '/bg-gradient-to-br from-blue-500 to-blue-600/' => 'style="background: var(--arrow-x-primary-gradient)"',
    '/bg-gradient-to-br from-purple-500 to-purple-600/' => 'style="background: var(--arrow-x-accent-gradient)"',
    '/bg-gradient-to-br from-green-500 to-emerald-600/' => 'style="background: var(--arrow-x-success-gradient)"',
    '/bg-gradient-to-br from-orange-500 to-red-600/' => 'style="background: var(--arrow-x-warning-gradient)"',
];

foreach ($gradientPatterns as $pattern => $replacement) {
    $content = preg_replace($pattern, $replacement, $content);
}

// 7. เพิ่ม hover:scale-105 สำหรับ stat cards
$content = preg_replace('/(<div[^>]*class="[^"]*glass-fusion[^"]*shadow[^"]*p-6[^"]*")/', '$1 hover:scale-105 transition-transform', $content);

// 8. แปลง bg-gray-50, bg-gray-100 เป็น glass-fusion variants
$content = preg_replace('/\bbg-gray-50\b/', 'bg-gray-100/50 dark:bg-gray-800/50', $content);
$content = preg_replace('/\bbg-gray-100\b/', 'bg-gray-100/50 dark:bg-gray-800/50', $content);
$content = preg_replace('/\bbg-gray-200\b/', 'bg-gray-200 dark:bg-gray-700', $content);

// 9. เพิ่ม dark mode สำหรับ borders
$content = preg_replace('/\bborder-gray-200\b/', 'border-gray-200 dark:border-gray-700', $content);
$content = preg_replace('/\bborder-gray-300\b/', 'border-gray-300 dark:border-gray-600', $content);

// 10. แปลง rounded-lg เป็น rounded-xl สำหรับความสวยงาม
$content = preg_replace('/\brounded-lg\b/', 'rounded-xl', $content);

// ตรวจสอบว่ามีการเปลี่ยนแปลงหรือไม่
if ($content === $original) {
    echo "⚠️  ไม่มีการเปลี่ยนแปลง\n";
    exit(0);
}

// สำรองไฟล์เดิม
$backupPath = $filePath . '.backup';
if (!file_exists($backupPath)) {
    copy($filePath, $backupPath);
    echo "💾 Backup สร้างที่: {$backupPath}\n";
}

// บันทึกไฟล์ใหม่
file_put_contents($filePath, $content);

echo "✅ Migration สำเร็จ!\n";
echo "\nการเปลี่ยนแปลงที่ทำ:\n";
echo "- เปลี่ยน layout เป็น layouts.admin-v3\n";
echo "- แทนที่ bg-white ด้วย glass-fusion\n";
echo "- เพิ่ม dark mode classes\n";
echo "- ปรับ UI elements ให้ทันสมัย\n";
echo "\n⚠️  โปรดตรวจสอบไฟล์และปรับแต่งเพิ่มเติมด้วยตนเอง\n";
echo "📝 Backup: {$backupPath}\n";
