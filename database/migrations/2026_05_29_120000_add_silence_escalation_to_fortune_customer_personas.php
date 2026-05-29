<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มฟิลด์ "escalation / persistent ban" สำหรับลูกค้าที่โดน silence ซ้ำๆ
     *
     * Spec (user 2026-05-29 — Fix C จาก anti-abuse audit):
     *  - เดิม silence เป็น 10 ชม. แล้ว reset ทุกวัน → troll ได้ free-chat 25 ครั้ง/วัน ตลอดไป
     *    ไม่มี escalation, ไม่มีการแจ้ง admin (L4 gap — ค้างมาตั้งแต่เคส "วิวัฒน์")
     *  - แก้: นับจำนวนครั้งที่โดน silence (lifetime, ไม่ decay) → ครบ threshold (5 ครั้ง)
     *    → ยืดเวลา silence เป็น 7 วัน + แจ้ง admin 1 ครั้ง (ไม่ auto-ban ถาวร — admin ตัดสินเอง)
     *
     * ⚠️ ALTER TABLE (เพิ่มคอลัมน์) — ใช้ Schema::table + hasColumn (ห้าม hasTable+return)
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('fortune_customer_personas', function (Blueprint $table) {
            // นับจำนวนครั้งที่โดน silence ทั้งหมด (lifetime) — ไม่ decay ตอน resume
            //   ใช้แยกจาก time_waster_score (ซึ่ง decay -10 ทุกครั้งที่ resume)
            //   เพื่อจับ "ผู้กระทำซ้ำข้ามวัน" ที่ score ไม่เคยสะสมถึงเพราะ decay
            if (! Schema::hasColumn('fortune_customer_personas', 'silence_count')) {
                $table->unsignedInteger('silence_count')
                    ->default(0)
                    ->after('last_silence_reason')
                    ->comment('จำนวนครั้งที่โดน silence ทั้งหมด (lifetime, ไม่ decay) — ใช้ escalate');
            }

            // เวลาที่แจ้ง admin เรื่อง abuse ครั้งล่าสุด — กัน alert ซ้ำ (null = ยังไม่เคยแจ้ง)
            if (! Schema::hasColumn('fortune_customer_personas', 'admin_abuse_alerted_at')) {
                $table->timestamp('admin_abuse_alerted_at')
                    ->nullable()
                    ->after('silence_count')
                    ->comment('เวลาแจ้ง admin เรื่อง repeat abuse ครั้งล่าสุด — กัน alert ซ้ำ');
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
                'silence_count',
                'admin_abuse_alerted_at',
            ];

            foreach ($columns as $col) {
                if (Schema::hasColumn('fortune_customer_personas', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
