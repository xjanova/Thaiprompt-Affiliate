<?php

/**
 * Template สำหรับสร้างตารางใหม่ (CREATE TABLE)
 *
 * ✅ ใช้เมื่อ: สร้างตารางใหม่ที่ยังไม่มีในฐานข้อมูล
 * ✅ ปลอดภัย: ใช้ Schema::hasTable() + return ได้
 *
 * วิธีใช้:
 * 1. Copy template นี้
 * 2. แก้ไข 'table_name' เป็นชื่อตารางจริง (snake_case, plural)
 * 3. เพิ่ม columns ตามต้องการ
 * 4. Run: php artisan migrate
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง table_name
     *
     * @return void
     */
    public function up(): void
    {
        // ✅ SAFE: เช็คตารางก่อนสร้าง (ใช้ได้เฉพาะ CREATE TABLE)
        if (Schema::hasTable('table_name')) {
            return;
        }

        Schema::create('table_name', function (Blueprint $table) {
            // Primary Key
            $table->id();

            // Foreign Keys - ⚠️ ระบุชื่อตารางชัดเจนเสมอ!
            $table->foreignId('user_id')
                ->constrained('users')  // ⭐ ระบุชื่อตาราง!
                ->onDelete('cascade');

            // Regular Columns
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('user_id');
            $table->index('is_active');
            $table->unique('name');
        });
    }

    /**
     * ลบตาราง table_name
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('table_name');
    }
};
