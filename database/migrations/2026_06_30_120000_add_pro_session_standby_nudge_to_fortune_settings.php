<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มการตั้งค่า "สแตนบายรอลูกค้าที่ยังไม่ถาม" + "ระยะตามลูกค้า (nudge)"
     *
     * owner spec 2026-06-30 (FTU-260630-M8981 follow-up):
     *   - เวลา QA (celtic 15 / deep 7) เริ่มจับ "หลังลูกค้าถามคำถามแรกจริง" เท่านั้น (มีอยู่แล้ว)
     *   - ถ้าลูกค้ายังไม่ถาม (เงียบหลังพื้นดวง/คำทำนาย) → สแตนบายรอ 30 นาที + ตามทุก 10 นาที
     *     ครบ 30 นาทีไม่ถาม → สรุปคำทำนายให้เลย (กฎ "จ่ายแล้วต้องได้บทสรุป")
     *
     * - pro_session_standby_minutes         = สแตนบายรอลูกค้าที่ยังไม่ถามเลย ก่อน auto-finalize (default 30)
     * - pro_session_nudge_interval_minutes  = ตามลูกค้าให้เริ่มถามทุกกี่นาที ระหว่างสแตนบาย (default 10)
     *
     * ⚠️ ALTER TABLE — ห้ามใช้ Schema::hasTable()+return
     */
    public function up(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_telling_settings', 'pro_session_standby_minutes')) {
                $table->unsignedSmallInteger('pro_session_standby_minutes')->default(30)
                    ->after('celtic_cross_qa_window_minutes');
            }
            if (! Schema::hasColumn('fortune_telling_settings', 'pro_session_nudge_interval_minutes')) {
                $table->unsignedSmallInteger('pro_session_nudge_interval_minutes')->default(10)
                    ->after('pro_session_standby_minutes');
            }
        });
    }

    /**
     * ลบคอลัมน์ที่เพิ่ม
     */
    public function down(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            foreach (['pro_session_standby_minutes', 'pro_session_nudge_interval_minutes'] as $col) {
                if (Schema::hasColumn('fortune_telling_settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
