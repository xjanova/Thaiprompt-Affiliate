<?php

/**
 * Template สำหรับเพิ่มคอลัมน์ใหม่ (ALTER TABLE - ADD COLUMNS)
 *
 * ⚠️ CRITICAL: ห้ามใช้ Schema::hasTable() + return!
 * เพราะจะทำให้คอลัมน์ใหม่ไม่ถูกสร้าง!
 *
 * ✅ ใช้เมื่อ: ต้องการเพิ่มคอลัมน์ใหม่ในตารางที่มีอยู่แล้ว
 * ✅ แนะนำ: ใช้ SafeMigration trait แทน (ดู safe_migration_template.php)
 *
 * วิธีใช้:
 * 1. Copy template นี้
 * 2. แก้ไข 'table_name' เป็นชื่อตารางจริง
 * 3. แก้ไข column names และ types
 * 4. Run: php artisan migrate
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์ใหม่ในตาราง table_name
     *
     * ⚠️ IMPORTANT: ห้ามใช้ Schema::hasTable() + return
     * เพราะจะทำให้คอลัมน์ใหม่ไม่ถูกสร้าง!
     *
     * @return void
     */
    public function up(): void
    {
        // ❌ WRONG: ห้ามทำแบบนี้เมื่อเพิ่มคอลัมน์!
        // if (Schema::hasTable('table_name')) {
        //     return;  // ❌ คอลัมน์ใหม่จะไม่ถูกสร้าง!
        // }

        // ✅ CORRECT: ใช้ Schema::table() และเช็คแต่ละคอลัมน์
        Schema::table('table_name', function (Blueprint $table) {
            // เช็คว่าคอลัมน์มีอยู่แล้วหรือยัง
            if (!Schema::hasColumn('table_name', 'new_column')) {
                $table->string('new_column')->nullable()->after('existing_column');
            }

            if (!Schema::hasColumn('table_name', 'another_column')) {
                $table->boolean('another_column')->default(false);
            }

            if (!Schema::hasColumn('table_name', 'price')) {
                $table->decimal('price', 10, 2)->default(0);
            }

            if (!Schema::hasColumn('table_name', 'metadata')) {
                $table->json('metadata')->nullable();
            }
        });
    }

    /**
     * ลบคอลัมน์ที่เพิ่มเข้าไป
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('table_name', function (Blueprint $table) {
            // เช็คก่อนลบ
            if (Schema::hasColumn('table_name', 'new_column')) {
                $table->dropColumn('new_column');
            }

            if (Schema::hasColumn('table_name', 'another_column')) {
                $table->dropColumn('another_column');
            }

            if (Schema::hasColumn('table_name', 'price')) {
                $table->dropColumn('price');
            }

            if (Schema::hasColumn('table_name', 'metadata')) {
                $table->dropColumn('metadata');
            }
        });
    }
};
