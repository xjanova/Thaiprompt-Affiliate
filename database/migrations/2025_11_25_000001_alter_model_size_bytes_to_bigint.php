<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เปลี่ยนคอลัมน์ model_size_bytes จาก integer เป็น bigInteger
     *
     * เหตุผล: Model files ขนาดใหญ่ (เช่น 25 GB = 25,547,038,310 bytes)
     * เกินขีดจำกัดของ integer (max ~2.1 billion)
     *
     * @return void
     */
    public function up(): void
    {
        // ตรวจสอบว่าตารางมีอยู่
        if (!Schema::hasTable('huggingface_trending_models')) {
            return;
        }

        // ตรวจสอบว่าคอลัมน์มีอยู่
        if (!Schema::hasColumn('huggingface_trending_models', 'model_size_bytes')) {
            return;
        }

        Schema::table('huggingface_trending_models', function (Blueprint $table) {
            // เปลี่ยนจาก integer เป็น bigInteger unsigned
            // รองรับค่าสูงสุด 18,446,744,073,709,551,615 (unsigned)
            $table->bigInteger('model_size_bytes')->unsigned()->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        if (!Schema::hasTable('huggingface_trending_models')) {
            return;
        }

        if (!Schema::hasColumn('huggingface_trending_models', 'model_size_bytes')) {
            return;
        }

        Schema::table('huggingface_trending_models', function (Blueprint $table) {
            // กลับไปเป็น integer (อาจทำให้ข้อมูลหาย)
            $table->integer('model_size_bytes')->nullable()->change();
        });
    }
};
