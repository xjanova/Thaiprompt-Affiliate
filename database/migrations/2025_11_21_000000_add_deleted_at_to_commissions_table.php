<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์ deleted_at ในตาราง commissions
     *
     * ⚠️ IMPORTANT: ห้ามใช้ Schema::hasTable() + return
     * เพราะจะทำให้คอลัมน์ใหม่ไม่ถูกสร้าง!
     *
     * @return void
     */
    public function up(): void
    {
        // ✅ CORRECT: ใช้ Schema::table() และเช็คแต่ละคอลัมน์
        Schema::table('commissions', function (Blueprint $table) {
            // เช็คว่าคอลัมน์ deleted_at มีอยู่แล้วหรือยัง
            if (!Schema::hasColumn('commissions', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });
    }

    /**
     * ลบคอลัมน์ deleted_at ที่เพิ่มเข้าไป
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('commissions', function (Blueprint $table) {
            // เช็คก่อนลบ
            if (Schema::hasColumn('commissions', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
