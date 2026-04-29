<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์ Celtic Cross ใน fortune_readings
     *
     * - celtic_summary_image_path: path รูป composite ภาพ spread 10 ใบที่จัดเรียงแล้ว
     * - celtic_questions_used: นับจำนวนคำถามที่ใช้ไปแล้ว (0-3)
     * - celtic_first_answered_at: timestamp ที่ Q1 ตอบเสร็จ — ใช้คำนวณ 1 ชม. window สำหรับ Q2-3
     *
     * Note:
     *   - reading_type ใช้ค่า 'celtic_cross' (column เป็น string ไม่ใช่ enum — ใช้ได้เลย)
     *   - 10 ไพ่ที่เลือกได้เก็บใน conversation_state JSON (ไม่ต้องเพิ่ม column)
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('fortune_readings', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_readings', 'celtic_summary_image_path')) {
                $table->string('celtic_summary_image_path', 500)
                    ->nullable()
                    ->after('reading_image_url')
                    ->comment('Path ภาพ composite Celtic Cross spread (storage/app/public)');
            }

            if (! Schema::hasColumn('fortune_readings', 'celtic_questions_used')) {
                $table->unsignedTinyInteger('celtic_questions_used')
                    ->default(0)
                    ->after('celtic_summary_image_path')
                    ->comment('จำนวนคำถามที่ใช้ไปแล้ว (0-3)');
            }

            if (! Schema::hasColumn('fortune_readings', 'celtic_first_answered_at')) {
                $table->timestamp('celtic_first_answered_at')
                    ->nullable()
                    ->after('celtic_questions_used')
                    ->comment('Timestamp ที่ Q1 ตอบเสร็จ — ใช้คำนวณ 1 ชม. window');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fortune_readings', function (Blueprint $table) {
            $columns = [
                'celtic_summary_image_path',
                'celtic_questions_used',
                'celtic_first_answered_at',
            ];

            foreach ($columns as $col) {
                if (Schema::hasColumn('fortune_readings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
