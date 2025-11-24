<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เปลี่ยนคอลัมน์ model_size_bytes จาก integer เป็น bigInteger
     *
     * เหตุผล: ค่า model_size_bytes ของโมเดลขนาดใหญ่ (เช่น Llama 3.1 405B = 869GB)
     * เกินค่าสูงสุดของ integer (2,147,483,647) จึงต้องใช้ bigInteger แทน
     *
     * @return void
     */
    public function up(): void
    {
        // ตรวจสอบว่าตารางและคอลัมน์มีอยู่
        if (!Schema::hasTable('huggingface_trending_models')) {
            return;
        }

        if (!Schema::hasColumn('huggingface_trending_models', 'model_size_bytes')) {
            return;
        }

        // เปลี่ยนประเภทคอลัมน์เป็น bigInteger
        Schema::table('huggingface_trending_models', function (Blueprint $table) {
            $table->bigInteger('model_size_bytes')
                ->nullable()
                ->comment('ขนาด model (bytes)')
                ->change();
        });
    }

    /**
     * Rollback - เปลี่ยนกลับเป็น integer
     *
     * หมายเหตุ: การ rollback อาจทำให้ข้อมูลสูญหายหากมีค่าเกิน integer max
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
            $table->integer('model_size_bytes')
                ->nullable()
                ->comment('ขนาด model (bytes)')
                ->change();
        });
    }
};
