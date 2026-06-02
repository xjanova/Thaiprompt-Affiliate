<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 🧩 (2026-06-02) เพิ่ม toggle 10 หมวดความรู้รายไพ่เสริม + seed ข้อมูลเข้า DB
     *
     * เปิด/ปิดการ inject ความรู้รายไพ่ 10 หมวดใหม่จากคลัง RAG เข้า prompt Celtic 99:
     *   ความรัก/การเงิน/ฤกษ์ยาม/เลขศาสตร์/ของมงคล/จิตใจ/ครอบครัว/เดินทาง/คดีความ/แก้กรรม
     *
     * + reseed FortuneKnowledgeSeeder (firstOrCreate = ไม่ทับของเดิม/admin edit) ให้ deploy
     *   เติม 78×10 = 780 ใบเข้า DB เอง — แก้ root cause ที่สาขา nifty-gates ไม่มี migration นี้
     *   (deploy.sh รัน migrate แต่ข้าม seed บน DB ที่มีข้อมูล) จึงต้อง seed ผ่าน migration แบบหมวดเดิม
     */
    public function up(): void
    {
        // toggle ทั้ง 10 หมวด (default = เปิด) — ต่อท้าย enable_celtic_destiny ตาม pattern เดิม
        $toggles = [
            'enable_celtic_love',
            'enable_celtic_wealth',
            'enable_celtic_auspicious',
            'enable_celtic_numerology',
            'enable_celtic_lucky_items',
            'enable_celtic_mental',
            'enable_celtic_family',
            'enable_celtic_travel',
            'enable_celtic_legal',
            'enable_celtic_remedy',
        ];

        if (Schema::hasTable('fortune_telling_settings')) {
            Schema::table('fortune_telling_settings', function (Blueprint $table) use ($toggles) {
                $previous = 'enable_celtic_destiny';
                foreach ($toggles as $col) {
                    // เช็คทีละคอลัมน์ — ห้ามใช้ Schema::hasTable()+return ตอนเพิ่มคอลัมน์
                    if (! Schema::hasColumn('fortune_telling_settings', $col)) {
                        $table->boolean($col)->default(true)->after($previous);
                    }
                    $previous = $col;
                }
            });
        }

        // เติมข้อมูล 10 หมวดเข้า DB อัตโนมัติ (idempotent — firstOrCreate)
        if (Schema::hasTable('fortune_knowledge')) {
            try {
                Artisan::call('db:seed', [
                    '--class' => \Database\Seeders\FortuneKnowledgeSeeder::class,
                    '--force' => true,
                ]);
            } catch (\Throwable $e) {
                Log::warning('seed 10 หมวดความรู้เสริม ใน migration ล้มเหลว (non-blocking)', ['error' => $e->getMessage()]);
            }
        }
    }

    /**
     * ลบ toggle ทั้ง 10 หมวด (ไม่ลบข้อมูลความรู้ใน fortune_knowledge — แอดมินจัดการเอง)
     */
    public function down(): void
    {
        $toggles = [
            'enable_celtic_love',
            'enable_celtic_wealth',
            'enable_celtic_auspicious',
            'enable_celtic_numerology',
            'enable_celtic_lucky_items',
            'enable_celtic_mental',
            'enable_celtic_family',
            'enable_celtic_travel',
            'enable_celtic_legal',
            'enable_celtic_remedy',
        ];

        if (Schema::hasTable('fortune_telling_settings')) {
            Schema::table('fortune_telling_settings', function (Blueprint $table) use ($toggles) {
                foreach ($toggles as $col) {
                    if (Schema::hasColumn('fortune_telling_settings', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
