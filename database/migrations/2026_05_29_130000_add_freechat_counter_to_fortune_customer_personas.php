<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ย้าย "free-chat daily counter" จาก Cache → DB column (deploy-proof)
     *
     * Spec (user 2026-05-29 — Gap 3 จาก anti-abuse audit):
     *  - เดิม counter free-chat/วัน อยู่ใน Cache key fortune:freechat_count:{platform}:{uid}:{Ymd}
     *  - auto-deploy ทุก push → deploy.sh รัน cache:clear → counter รีเซ็ตเป็น 0
     *  - วันที่ deploy บ่อย → troll ได้ free-chat เกิน FREECHAT_DAILY_CAP (25) หลายเท่า
     *  - แก้: เก็บใน persona column → ทน deploy เหมือน chat_silenced_until / silence_count
     *
     * ⚠️ ALTER TABLE (เพิ่มคอลัมน์) — ใช้ Schema::table + hasColumn (ห้าม hasTable+return)
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('fortune_customer_personas', function (Blueprint $table) {
            // จำนวน free-chat turns ของ "วันนี้" (reset เมื่อ freechat_count_date เปลี่ยนวัน)
            if (! Schema::hasColumn('fortune_customer_personas', 'freechat_count')) {
                $table->unsignedInteger('freechat_count')
                    ->default(0)
                    ->after('admin_abuse_alerted_at')
                    ->comment('จำนวน free-chat turns ของวันนี้ (unpaid, no buy-intent) — เทียบ FREECHAT_DAILY_CAP');
            }

            // วันที่ของ counter ปัจจุบัน — ถ้าไม่ตรง today → reset count เป็น 0
            if (! Schema::hasColumn('fortune_customer_personas', 'freechat_count_date')) {
                $table->date('freechat_count_date')
                    ->nullable()
                    ->after('freechat_count')
                    ->comment('วันที่ของ freechat_count — ใช้ reset รายวัน (แทน Cache TTL endOfDay เดิม)');
            }
        });
    }

    /**
     * Rollback — ลบ columns ที่เพิ่ม
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('fortune_customer_personas', function (Blueprint $table) {
            $columns = [
                'freechat_count',
                'freechat_count_date',
            ];

            foreach ($columns as $col) {
                if (Schema::hasColumn('fortune_customer_personas', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
