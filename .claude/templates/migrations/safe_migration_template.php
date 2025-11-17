<?php

/**
 * Template สำหรับใช้ SafeMigration Trait (⭐ RECOMMENDED)
 *
 * ✅ แนะนำให้ใช้วิธีนี้เสมอ! ปลอดภัยที่สุด
 *
 * SafeMigration จะเช็คให้อัตโนมัติว่า:
 * - คอลัมน์มีอยู่แล้วหรือยัง
 * - Index มีอยู่แล้วหรือยัง
 * - Foreign key มีอยู่แล้วหรือยัง
 *
 * วิธีใช้:
 * 1. Copy template นี้
 * 2. แก้ไข 'table_name' และ column names
 * 3. Run: php artisan migrate
 *
 * อ่านเพิ่มเติม: database/migrations/README_MIGRATIONS.md
 */

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;  // ⭐ ใช้ trait นี้

    /**
     * เพิ่มคอลัมน์ใหม่อย่างปลอดภัย
     *
     * SafeMigration จะเช็คให้อัตโนมัติ
     * ไม่ต้องเช็ค Schema::hasColumn() เอง
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('table_name', function (Blueprint $table) {
            // ✅ Safe methods จะเช็คให้เองอัตโนมัติ
            $this->safeAddColumn($table, 'table_name', 'new_column', function($table) {
                $table->string('new_column')->nullable();
            });

            $this->safeAddColumn($table, 'table_name', 'another_column', function($table) {
                $table->boolean('another_column')->default(false);
            });

            $this->safeAddColumn($table, 'table_name', 'price', function($table) {
                $table->decimal('price', 10, 2)->default(0);
            });

            // เพิ่มหลาย columns พร้อมกัน
            $this->safeAddColumns($table, 'table_name', [
                'first_name' => fn($t) => $t->string('first_name')->nullable(),
                'last_name' => fn($t) => $t->string('last_name')->nullable(),
                'age' => fn($t) => $t->integer('age')->default(0),
            ]);
        });

        // เพิ่ม indexes อย่างปลอดภัย
        $this->safeAddIndex('table_name', 'new_column');
        $this->safeAddIndex('table_name', ['first_name', 'last_name']);
    }

    /**
     * ลบคอลัมน์อย่างปลอดภัย
     *
     * @return void
     */
    public function down(): void
    {
        // ลบ indexes ก่อน
        $this->safeDropIndex('table_name', 'table_name_new_column_index');
        $this->safeDropIndex('table_name', 'table_name_first_name_last_name_index');

        // แล้วลบ columns
        $this->safeDropColumn('table_name', [
            'new_column',
            'another_column',
            'price',
            'first_name',
            'last_name',
            'age',
        ]);
    }
};
