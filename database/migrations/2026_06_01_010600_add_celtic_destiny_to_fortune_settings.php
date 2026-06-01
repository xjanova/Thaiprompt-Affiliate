<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 🔮 (2026-06-01) เพิ่ม enable_celtic_destiny toggle + seed หมวดดวงจิต/กรรม
     *
     * เปิด/ปิดการ inject ความรู้รายไพ่หมวดดวงจิต (สายญาณ/ผู้มีองค์/ภารกิจสวรรค์ + อดีตชาติ/กรรมเก่า)
     *   จากคลัง RAG เข้า prompt Celtic 99 — มีจรรยาบรรณเข้ม (กันมั่ว/ขายความกลัว)
     *
     * + reseed (FortuneKnowledgeSeeder รวมหมวดดวงจิต 2×78 = 156 ใบ) ให้ deploy เติม DB เอง
     */
    public function up(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_telling_settings', 'enable_celtic_destiny')) {
                $table->boolean('enable_celtic_destiny')
                    ->default(true)
                    ->after('enable_celtic_life_reading');
            }
        });

        if (Schema::hasTable('fortune_knowledge')) {
            try {
                Artisan::call('db:seed', [
                    '--class' => \Database\Seeders\FortuneKnowledgeSeeder::class,
                    '--force' => true,
                ]);
            } catch (\Throwable $e) {
                Log::warning('seed หมวดดวงจิต/กรรม ใน migration ล้มเหลว (non-blocking)', ['error' => $e->getMessage()]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (Schema::hasColumn('fortune_telling_settings', 'enable_celtic_destiny')) {
                $table->dropColumn('enable_celtic_destiny');
            }
        });
    }
};
