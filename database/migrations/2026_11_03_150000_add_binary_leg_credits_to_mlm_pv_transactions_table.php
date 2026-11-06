<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    /**
     * เพิ่มบันทึก "PV ของออเดอร์นี้ถูกลงขา binary ให้ upline คนไหน ขาไหน เท่าไร" (audit F11, เจ้าของตัดสิน M6)
     *
     * ตอนคืนเงินต้องหัก PV ขาออกจากคนที่ได้รับจริงเท่านั้น — upline ที่ไม่ active ตอนซื้อไม่ได้รับ PV ขา (F4)
     * ถ้าหักจากทั้งสายจะไปหัก PV ของออเดอร์อื่นหรือดึงคู่ที่ไม่เกี่ยวข้องคืน
     *
     * รูปแบบ: [{"m": member_id, "s": "left"|"right", "pv": 100.0}, ...] บนแถว purchase ของออเดอร์
     * NULL = แถวเก่าก่อนมีคอลัมน์นี้ (คืนเงินจะหักเฉพาะ PV ที่ยังไม่จับคู่ ไม่ดึงคู่คืน)
     *
     * ⚠️ เพิ่มคอลัมน์ nullable อย่างเดียว ไม่แตะข้อมูลเดิม
     */
    public function up(): void
    {
        if (! Schema::hasTable('mlm_pv_transactions')) {
            return;
        }

        Schema::table('mlm_pv_transactions', function (Blueprint $table) {
            $this->safeAddColumn($table, 'mlm_pv_transactions', 'binary_leg_credits', function (Blueprint $table) {
                $table->json('binary_leg_credits')->nullable()->after('attributed_leg');
            });
        });
    }

    /**
     * ลบคอลัมน์ที่เพิ่ม (ข้อมูลประกอบการคืนเงินเท่านั้น ไม่ใช่ยอดเงิน)
     */
    public function down(): void
    {
        $this->safeDropColumn('mlm_pv_transactions', ['binary_leg_credits']);
    }
};
