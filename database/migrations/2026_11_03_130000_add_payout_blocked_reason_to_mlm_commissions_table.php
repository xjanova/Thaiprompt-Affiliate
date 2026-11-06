<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    /**
     * เพิ่มเหตุผลที่จ่ายคอมไม่ได้ลงแถว mlm_commissions (audit F9)
     *
     * เดิมแถวที่กองไม่พอจ่าย ค้างสถานะ approved เงียบๆ (exception ถูกกลืน, หน้าจอตอบ "สำเร็จ")
     * แล้วรอดูดเงินกองของออเดอร์อื่นเมื่อกองโตพอ — ตอนนี้บันทึกรหัสเหตุผล + เวลาไว้ให้แอดมินเห็น
     *
     * ⚠️ เพิ่มคอลัมน์ nullable อย่างเดียว ไม่แตะข้อมูลเดิม
     */
    public function up(): void
    {
        if (! Schema::hasTable('mlm_commissions')) {
            return;
        }

        Schema::table('mlm_commissions', function (Blueprint $table) {
            // รหัสเหตุผล เช่น pool_insufficient / order_budget_exceeded / wallet_inactive
            $this->safeAddColumn($table, 'mlm_commissions', 'payout_blocked_reason', function (Blueprint $table) {
                $table->string('payout_blocked_reason', 50)->nullable()->after('status');
            });

            $this->safeAddColumn($table, 'mlm_commissions', 'payout_blocked_at', function (Blueprint $table) {
                $table->timestamp('payout_blocked_at')->nullable()->after('payout_blocked_reason');
            });
        });
    }

    /**
     * ลบคอลัมน์ที่เพิ่ม (เป็นข้อมูลวินิจฉัยเท่านั้น ไม่ใช่ยอดเงิน)
     */
    public function down(): void
    {
        $this->safeDropColumn('mlm_commissions', ['payout_blocked_reason', 'payout_blocked_at']);
    }
};
