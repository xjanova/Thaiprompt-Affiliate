<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มฟิลด์ตรวจจับ "ลูกค้าเวิ่นเว้อ" + cooldown 10 ชม
     *
     * Spec (user 2026-05-18):
     *  - เสนอขาย 3 ครั้งใน rolling 30 นาที + ลูกค้าตอบฟุ้ง = silence 10 ชม
     *  - Silence = เงียบสนิท ยกเว้น keyword 'ดูดวง / จ่าย / โอน / qr / พร้อม / 39 / 99'
     *  - Resume: failed_count=0, score -=10, chat_silenced_until=null
     *  - ไม่ permanent — cooldown ซ้ำได้
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('fortune_customer_personas', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_customer_personas', 'sales_pitch_count')) {
                $table->unsignedInteger('sales_pitch_count')
                    ->default(0)
                    ->after('observation_count')
                    ->comment('จำนวนครั้งที่บอทเสนอขายทั้งหมด (lifetime)');
            }

            if (! Schema::hasColumn('fortune_customer_personas', 'sales_pitch_failed_count')) {
                $table->unsignedInteger('sales_pitch_failed_count')
                    ->default(0)
                    ->after('sales_pitch_count')
                    ->comment('จำนวน pitch ที่ลูกค้าตอบฟุ้งภายใน window (reset เมื่อ cooldown หมด)');
            }

            if (! Schema::hasColumn('fortune_customer_personas', 'time_waster_score')) {
                $table->unsignedTinyInteger('time_waster_score')
                    ->default(0)
                    ->after('sales_pitch_failed_count')
                    ->comment('คะแนนรบกวน 0-100 — สูง = ฟุ้งซ่านไม่ปิดการขายบ่อย');
            }

            if (! Schema::hasColumn('fortune_customer_personas', 'last_pitch_at')) {
                $table->timestamp('last_pitch_at')
                    ->nullable()
                    ->after('time_waster_score')
                    ->comment('เวลาเสนอขายครั้งล่าสุด — ใช้คำนวณ rolling 30min window');
            }

            if (! Schema::hasColumn('fortune_customer_personas', 'chat_silenced_until')) {
                $table->timestamp('chat_silenced_until')
                    ->nullable()
                    ->after('last_pitch_at')
                    ->comment('ปิดเสียง AI chat ถึงเวลานี้ — null = ไม่ปิด');
            }

            if (! Schema::hasColumn('fortune_customer_personas', 'last_silence_reason')) {
                $table->text('last_silence_reason')
                    ->nullable()
                    ->after('chat_silenced_until')
                    ->comment('เหตุผลที่ปิดเสียงครั้งล่าสุด — debug + admin UI');
            }
        });

        // เพิ่ม index สำหรับ query "ใครยังถูก silence อยู่"
        Schema::table('fortune_customer_personas', function (Blueprint $table) {
            $indexName = 'fcp_chat_silenced_until_idx';
            $exists = collect(\DB::select(
                "SHOW INDEX FROM fortune_customer_personas WHERE Key_name = ?",
                [$indexName]
            ))->isNotEmpty();

            if (! $exists) {
                $table->index('chat_silenced_until', $indexName);
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
            $indexName = 'fcp_chat_silenced_until_idx';
            $exists = collect(\DB::select(
                "SHOW INDEX FROM fortune_customer_personas WHERE Key_name = ?",
                [$indexName]
            ))->isNotEmpty();

            if ($exists) {
                $table->dropIndex($indexName);
            }
        });

        Schema::table('fortune_customer_personas', function (Blueprint $table) {
            $columns = [
                'sales_pitch_count',
                'sales_pitch_failed_count',
                'time_waster_score',
                'last_pitch_at',
                'chat_silenced_until',
                'last_silence_reason',
            ];

            foreach ($columns as $col) {
                if (Schema::hasColumn('fortune_customer_personas', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
