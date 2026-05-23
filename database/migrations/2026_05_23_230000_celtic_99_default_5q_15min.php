<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 🌙 (2026-05-23 v3) Celtic 99฿ — ปรับ default เป็น 5 คำถาม / 15 นาที
     *
     * user spec: "ในการทำนายแบบ 99 เปลี่ยนไม่ให้มีการดีเลย์ในการตอบ
     *             แต่ให้ถาม 5 คำถาม ภายใน 15 นาที และต้องบอกกติการให้ชัดทุกที่"
     *
     * จากเดิม:
     *   - celtic_cross_max_questions default 3 (legacy) → ปรับเป็น 5
     *   - celtic_cross_qa_window_minutes default 60 (legacy) → ปรับเป็น 15
     *
     * Update ทั้ง column default + แถวที่มีอยู่ (ถ้าใช้ค่า default เก่า / 30 / 60)
     */
    public function up(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        // 1) เปลี่ยน column default ใหม่ (สำหรับแถวที่จะสร้างในอนาคต)
        if (Schema::hasColumn('fortune_telling_settings', 'celtic_cross_max_questions')) {
            try {
                DB::statement('ALTER TABLE fortune_telling_settings MODIFY COLUMN celtic_cross_max_questions TINYINT UNSIGNED NOT NULL DEFAULT 5 COMMENT \'จำนวนคำถามต่อบิล (default 5 = ตามสเปคใหม่ 2026-05-23)\'');
            } catch (\Throwable $e) {
                // SQLite/MariaDB อาจไม่รับ syntax — ข้ามแบบ non-fatal
            }
        }

        if (Schema::hasColumn('fortune_telling_settings', 'celtic_cross_qa_window_minutes')) {
            try {
                DB::statement('ALTER TABLE fortune_telling_settings MODIFY COLUMN celtic_cross_qa_window_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 15 COMMENT \'เวลาคุยกับแม่หมอ (นาที) — default 15 = ตามสเปคใหม่ 2026-05-23\'');
            } catch (\Throwable $e) {
                // ignore
            }
        }

        // 2) อัพเดทแถวที่มีอยู่ — ถ้าค่ายังเป็น legacy default ให้ปรับเป็นค่าใหม่
        //    เงื่อนไข: ถ้า max_questions เป็น 0/3 (legacy) → 5
        //              ถ้า window เป็น 30/60 (legacy) → 15
        //    หมาย: ถ้า admin override ไปเป็น 10 คำถาม / 45 นาที ฯลฯ — เก็บไว้
        if (Schema::hasColumn('fortune_telling_settings', 'celtic_cross_max_questions')) {
            DB::table('fortune_telling_settings')
                ->whereIn('celtic_cross_max_questions', [0, 3])
                ->update(['celtic_cross_max_questions' => 5]);
        }

        if (Schema::hasColumn('fortune_telling_settings', 'celtic_cross_qa_window_minutes')) {
            DB::table('fortune_telling_settings')
                ->whereIn('celtic_cross_qa_window_minutes', [30, 60])
                ->update(['celtic_cross_qa_window_minutes' => 15]);
        }
    }

    /**
     * ย้อนกลับ — คืน default เก่า (ไม่แตะข้อมูลแถวที่มีอยู่ เพื่อกัน data loss)
     */
    public function down(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        if (Schema::hasColumn('fortune_telling_settings', 'celtic_cross_max_questions')) {
            try {
                DB::statement('ALTER TABLE fortune_telling_settings MODIFY COLUMN celtic_cross_max_questions TINYINT UNSIGNED NOT NULL DEFAULT 3 COMMENT \'จำนวนคำถามต่อบิล (Q1=full + Q2-3=follow-up)\'');
            } catch (\Throwable $e) {
                // ignore
            }
        }

        if (Schema::hasColumn('fortune_telling_settings', 'celtic_cross_qa_window_minutes')) {
            try {
                DB::statement('ALTER TABLE fortune_telling_settings MODIFY COLUMN celtic_cross_qa_window_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 60 COMMENT \'เวลาที่ถาม Q2-3 ได้หลัง Q1 ตอบเสร็จ (นาที)\'');
            } catch (\Throwable $e) {
                // ignore
            }
        }
    }
};
