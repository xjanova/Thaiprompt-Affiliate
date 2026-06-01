<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 🗓️ (2026-06-01) เพิ่ม enable_celtic_life_reading toggle + seed หมวดชีวิต
     *
     * เปิด/ปิดการ inject ความรู้รายไพ่หมวดชีวิต (ช่วงอายุ/สถานการณ์-จังหวะเวลา/
     *   การศึกษา-อาชีพ/ธุรกิจ-การงาน) จากคลัง RAG เข้า prompt Celtic 99
     *
     * + reseed (FortuneKnowledgeSeeder ตอนนี้รวมหมวดชีวิต 4×78 = 312 ใบ) เพื่อให้ deploy เติม DB เอง
     */
    public function up(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_telling_settings', 'enable_celtic_life_reading')) {
                $table->boolean('enable_celtic_life_reading')
                    ->default(true)
                    ->after('enable_celtic_physiognomy');
            }
        });

        // reseed — เติมหมวดชีวิต (idempotent firstOrCreate, ไม่ทับของที่แอดมินแก้)
        if (Schema::hasTable('fortune_knowledge')) {
            try {
                Artisan::call('db:seed', [
                    '--class' => \Database\Seeders\FortuneKnowledgeSeeder::class,
                    '--force' => true,
                ]);
            } catch (\Throwable $e) {
                Log::warning('seed หมวดชีวิต ใน migration ล้มเหลว (non-blocking)', ['error' => $e->getMessage()]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (Schema::hasColumn('fortune_telling_settings', 'enable_celtic_life_reading')) {
                $table->dropColumn('enable_celtic_life_reading');
            }
        });
    }
};
