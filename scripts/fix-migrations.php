<?php

/**
 * Script แก้ไข migrations ที่มีปัญหา
 *
 * เพิ่ม hasTable check ให้กับ migrations ที่ใช้ Schema::table
 * เพื่อป้องกัน error เมื่อตารางยังไม่มี
 */
$migrationsPath = __DIR__.'/../database/migrations';
$files = glob($migrationsPath.'/*.php');

$fixed = 0;
$skipped = 0;
$errors = [];

foreach ($files as $file) {
    $content = file_get_contents($file);
    $filename = basename($file);

    // ข้ามไฟล์ที่มี hasTable แล้ว
    if (strpos($content, 'hasTable') !== false) {
        $skipped++;

        continue;
    }

    // ข้ามไฟล์ที่ไม่มี Schema::table
    if (strpos($content, 'Schema::table') === false) {
        $skipped++;

        continue;
    }

    // หา table names ที่ถูกแก้ไข
    preg_match_all("/Schema::table\s*\(\s*['\"]([^'\"]+)['\"]/", $content, $matches);

    if (empty($matches[1])) {
        $skipped++;

        continue;
    }

    $tables = array_unique($matches[1]);
    $modified = false;

    foreach ($tables as $table) {
        // สร้าง pattern สำหรับ Schema::table('tablename', function
        $pattern = "/(Schema::table\s*\(\s*['\"]".preg_quote($table, '/')."['\"])/";

        // ตรวจสอบว่ายังไม่มี hasTable check สำหรับตารางนี้
        if (strpos($content, "hasTable('$table')") === false && strpos($content, "hasTable(\"$table\")") === false) {
            // หา function up() และเพิ่ม check ที่ต้นฟังก์ชัน
            $upPattern = "/(public\s+function\s+up\s*\(\s*\)\s*:\s*void\s*\{)/";

            if (preg_match($upPattern, $content)) {
                // เพิ่ม hasTable check หลัง function up() {
                $check = "$1\n        // ตรวจสอบว่าตาราง {$table} มีอยู่แล้วหรือไม่\n        if (!Schema::hasTable('{$table}')) {\n            return;\n        }\n";
                $newContent = preg_replace($upPattern, $check, $content, 1);

                if ($newContent !== $content) {
                    $content = $newContent;
                    $modified = true;
                }
            }
        }
    }

    if ($modified) {
        file_put_contents($file, $content);
        echo "✓ Fixed: $filename\n";
        $fixed++;
    }
}

echo "\n";
echo "=================================\n";
echo "Fixed: $fixed files\n";
echo "Skipped: $skipped files\n";
if (! empty($errors)) {
    echo 'Errors: '.count($errors)."\n";
    foreach ($errors as $err) {
        echo "  - $err\n";
    }
}
