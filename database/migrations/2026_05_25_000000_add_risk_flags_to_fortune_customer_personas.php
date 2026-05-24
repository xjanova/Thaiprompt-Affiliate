<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 🚩 (2026-05-25) เพิ่ม risk_flags JSON ให้ AI รู้ว่าลูกค้าคนนี้ต้องระวัง
     *
     * Flags ที่เก็บ (8 ตัว):
     * - mental_fragile: เคยพูดวิกฤต/ฆ่าตัวตาย/ดื้อยา/ซึมเศร้ารุนแรง
     * - crisis_resources_sent: timestamp ส่ง 1323/1669 ครั้งล่าสุด
     * - complaint_prone: ชอบบ่น/โกรธ/ติ
     * - decline_pusher: ปฏิเสธแล้วยังตื๊อ (เคารพ decline เข้มขึ้น)
     * - abusive_tone: ใช้คำหยาบ/ก้าวร้าว
     * - over_emotional: อ่อนไหวมาก ภาษาระวัง
     * - scam_victim: เคยเล่าโดนโกง → ไม่ขายแรง
     * - quiet_listener: ตอบสั้น 1-2 คำ → อย่าถามเยอะ
     *
     * Sticky behavior: ส่วนใหญ่ true → maintain (extract job ห้ามทับเป็น false
     * อัตโนมัติ — ต้อง explicit positive signal)
     */
    public function up(): void
    {
        Schema::table('fortune_customer_personas', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_customer_personas', 'risk_flags')) {
                $table->json('risk_flags')->nullable()->after('communication_style');
            }
        });
    }

    /**
     * ลบ risk_flags
     */
    public function down(): void
    {
        Schema::table('fortune_customer_personas', function (Blueprint $table) {
            if (Schema::hasColumn('fortune_customer_personas', 'risk_flags')) {
                $table->dropColumn('risk_flags');
            }
        });
    }
};
