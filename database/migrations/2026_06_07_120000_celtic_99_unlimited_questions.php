<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 🌙 (2026-06-07) Celtic 99฿ — กลับไป "ถามได้ไม่จำกัด ภายใน 15 นาที"
     *
     * user spec: "เราเคยทำระบบแบบไม่มีข้อจำกัดเรื่องจำนวนคำถามไปแล้ว ซึ่งก็ดี แก้ปัญหา
     *             ให้ถาม-ตอบ ได้ตลอด ในเวลา 15 นาที — เพราะตอนตีสองคนเข้ามาดู บอทตอบและ
     *             หักโควต้าจนไม่ได้ถาม สร้างประสบการณ์ไม่ดี"
     *
     * จากเดิม (2026-05-23 v3): บังคับ 5 คำถาม / 15 นาที (hard cap จำนวน + เวลา)
     * เป็น (2026-06-07):       ไม่จำกัดจำนวนคำถาม — เหลือ "เวลา 15 นาที" เป็นตัวจำกัดเดียว
     *
     * 🔑 ตัวสวิตช์จริง = ค่าใน DB (คอลัมน์ NOT NULL → PHP `?? 0` เป็น fallback เฉยๆ ไม่ทำงาน)
     *    โค้ดทุกจุดเช็ค `maxQ > 0` อยู่แล้ว → maxQ = 0 หมายถึง "ไม่จำกัด" (admin override เดิม)
     *    ดังนั้น migration นี้แค่ตั้งค่า = 0 ก็พอ — ไม่ต้องแก้ logic ใด
     *
     * ⚠️ window คง 15 นาที (ไม่แตะ celtic_cross_qa_window_minutes) ตาม user spec
     */
    public function up(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        // 1) เปลี่ยน column default → 0 (สำหรับแถวที่จะสร้างในอนาคต = ไม่จำกัด)
        if (Schema::hasColumn('fortune_telling_settings', 'celtic_cross_max_questions')) {
            try {
                DB::statement('ALTER TABLE fortune_telling_settings MODIFY COLUMN celtic_cross_max_questions TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT \'จำนวนคำถามต่อบิล — 0 = ไม่จำกัด ภายในเวลา QA (2026-06-07)\'');
            } catch (\Throwable $e) {
                // SQLite/MariaDB อาจไม่รับ syntax — ข้ามแบบ non-fatal
            }
        }

        // 2) อัพเดทแถวที่มีอยู่ — flip ค่า cap มาตรฐาน (3/5 legacy) → 0 (ไม่จำกัด)
        //    หมาย: ถ้า admin override เป็นเลขอื่น (เช่น 10) → เก็บไว้ ไม่แตะ
        //          ถ้าภายหลังอยากกลับมาจำกัด → ตั้งค่าใน Admin → Fortune Settings ได้เลย
        if (Schema::hasColumn('fortune_telling_settings', 'celtic_cross_max_questions')) {
            DB::table('fortune_telling_settings')
                ->whereIn('celtic_cross_max_questions', [3, 5])
                ->update(['celtic_cross_max_questions' => 0]);
        }
    }

    /**
     * ย้อนกลับ — คืน default เป็น 5 (สเปค 2026-05-23) — ไม่แตะแถวที่มีอยู่ กัน data loss
     */
    public function down(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        if (Schema::hasColumn('fortune_telling_settings', 'celtic_cross_max_questions')) {
            try {
                DB::statement('ALTER TABLE fortune_telling_settings MODIFY COLUMN celtic_cross_max_questions TINYINT UNSIGNED NOT NULL DEFAULT 5 COMMENT \'จำนวนคำถามต่อบิล (default 5 = ตามสเปคใหม่ 2026-05-23)\'');
            } catch (\Throwable $e) {
                // ignore
            }
        }
    }
};
