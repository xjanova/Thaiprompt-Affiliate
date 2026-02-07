<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่ม UNIQUE constraint บน earnings_ledger (source_type, source_id, earning_type)
     *
     * ป้องกัน Race Condition: ถ้า 2 request เข้ามาพร้อมกัน
     * จะมีเพียง request เดียวที่ insert สำเร็จ อีก request จะเกิด duplicate key error
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('earnings_ledger', function (Blueprint $table) {
            // ลบ index เดิม (ถ้ามี) แล้วเปลี่ยนเป็น unique
            // index เดิม: $table->index(['source_type', 'source_id']);
            try {
                $table->dropIndex(['source_type', 'source_id']);
            } catch (\Exception $e) {
                // Index อาจไม่มี ก็ไม่เป็นไร
            }

            // เพิ่ม UNIQUE constraint ป้องกัน duplicate distribution
            // ใช้ earning_type ร่วมด้วย เพราะ 1 Order อาจมีหลาย earning_type
            // (เช่น seller_sale + mlm_commission)
            $table->unique(
                ['source_type', 'source_id', 'earning_type'],
                'earnings_ledger_source_type_unique'
            );
        });
    }

    /**
     * ย้อนกลับ: ลบ UNIQUE และเพิ่ม INDEX เดิมกลับ
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('earnings_ledger', function (Blueprint $table) {
            $table->dropUnique('earnings_ledger_source_type_unique');
            $table->index(['source_type', 'source_id']);
        });
    }
};
