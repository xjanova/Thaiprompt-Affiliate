<?php
/**
 * สคริปต์สำหรับเพิ่ม hasTable check ให้ migrations ที่ไม่มี
 *
 * ใช้งาน: php scripts/fix-migrations-hastable.php
 */

$migrationsDir = __DIR__ . '/../database/migrations';
$fixedCount = 0;
$skippedCount = 0;
$errorCount = 0;

// หาไฟล์ migrations ทั้งหมดที่ขึ้นต้นด้วย 2025_11
$files = glob($migrationsDir . '/2025_11_*.php');

echo "🔧 กำลังสแกน migrations...\n";
echo "พบ " . count($files) . " ไฟล์\n\n";

foreach ($files as $file) {
    $filename = basename($file);
    $content = file_get_contents($file);

    // ข้ามไฟล์ที่มี hasTable อยู่แล้ว
    if (strpos($content, 'hasTable') !== false) {
        echo "⏭️  SKIP: $filename (มี hasTable แล้ว)\n";
        $skippedCount++;
        continue;
    }

    // ข้ามไฟล์ที่ไม่มี Schema::create
    if (strpos($content, 'Schema::create') === false) {
        echo "⏭️  SKIP: $filename (ไม่มี Schema::create)\n";
        $skippedCount++;
        continue;
    }

    // หาชื่อตารางจาก Schema::create
    preg_match_all("/Schema::create\(['\"]([^'\"]+)['\"]/", $content, $matches);

    if (empty($matches[1])) {
        echo "⚠️  WARN: $filename (ไม่พบชื่อตาราง)\n";
        $errorCount++;
        continue;
    }

    $tables = $matches[1];
    $modified = false;

    foreach ($tables as $table) {
        // สร้าง pattern สำหรับหา Schema::create สำหรับตารางนี้
        $pattern = "/(Schema::create\(['\"]" . preg_quote($table, '/') . "['\"],\s*function)/";

        // ถ้ายังไม่มี hasTable check สำหรับตารางนี้
        if (strpos($content, "hasTable('$table')") === false &&
            strpos($content, "hasTable(\"$table\")") === false) {

            // เพิ่ม hasTable check
            $replacement = "if (!Schema::hasTable('$table')) {\n            Schema::create('$table', function";
            $newContent = preg_replace($pattern, $replacement, $content, 1);

            if ($newContent !== $content) {
                // หา closing ของ Schema::create และเพิ่ม }
                // นี่เป็นวิธีที่ซับซ้อน ต้องทำอย่างระมัดระวัง
                $content = $newContent;
                $modified = true;
            }
        }
    }

    if ($modified) {
        // สำหรับตอนนี้แค่รายงานว่าต้องแก้ไขอะไร
        echo "🔧 NEED FIX: $filename\n";
        echo "   ตาราง: " . implode(', ', $tables) . "\n";
        $fixedCount++;
    }
}

echo "\n";
echo "📊 สรุป:\n";
echo "   - ต้องแก้ไข: $fixedCount ไฟล์\n";
echo "   - ข้าม: $skippedCount ไฟล์\n";
echo "   - ปัญหา: $errorCount ไฟล์\n";
echo "\n";
echo "⚠️  สคริปต์นี้แค่รายงาน ไม่ได้แก้ไขไฟล์จริง\n";
echo "   เพราะการเพิ่ม hasTable check ต้องทำอย่างระมัดระวัง\n";
