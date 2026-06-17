<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 🧑‍🤝‍🧑 (2026-06-17) เพิ่ม enable_celtic_person_role toggle + seed ตำราตำแหน่งบุคคล 78 ใบ
     *
     * เปิด/ปิดการ inject ตำราตำแหน่งบุคคล/ระบุตัวคน ประจำไพ่ (จากคลัง RAG) เข้า prompt Celtic 99
     *   เมื่อลูกค้าเอ่ยถึง "ตัวบุคคล" (พ่อ/แม่/พี่/น้อง/ป้า/น้า/อา/เพื่อน/ผู้อุปถัมภ์) หรือถาม "ใครคือ..."
     *
     * + reseed FortuneKnowledgeSeeder (firstOrCreate = ไม่ทับของเดิม/admin edit) ให้ deploy เติม
     *   78 ใบหมวด person_role เข้า DB เอง — เพราะ deploy.sh รัน migrate แต่ข้าม seed บน DB ที่มีข้อมูล
     *
     * default = true. admin ปิดได้ผ่าน:
     *   UPDATE fortune_telling_settings SET enable_celtic_person_role = 0
     */
    public function up(): void
    {
        if (Schema::hasTable('fortune_telling_settings')) {
            Schema::table('fortune_telling_settings', function (Blueprint $table) {
                // เช็คคอลัมน์ก่อนเพิ่ม — ห้ามใช้ Schema::hasTable()+return ตอนเพิ่มคอลัมน์
                if (! Schema::hasColumn('fortune_telling_settings', 'enable_celtic_person_role')) {
                    $table->boolean('enable_celtic_person_role')
                        ->default(true)
                        ->after('enable_celtic_physiognomy');
                }
            });
        }

        // เติมข้อมูลหมวด person_role เข้า DB อัตโนมัติ (idempotent — firstOrCreate ไม่ทับ admin edit)
        if (Schema::hasTable('fortune_knowledge')) {
            try {
                Artisan::call('db:seed', [
                    '--class' => \Database\Seeders\FortuneKnowledgeSeeder::class,
                    '--force' => true,
                ]);
            } catch (\Throwable $e) {
                Log::warning('seed หมวดตำแหน่งบุคคล ใน migration ล้มเหลว (non-blocking)', ['error' => $e->getMessage()]);
            }
        }
    }

    /**
     * ลบ toggle (ไม่ลบข้อมูลความรู้ใน fortune_knowledge — แอดมินจัดการเอง)
     */
    public function down(): void
    {
        if (Schema::hasTable('fortune_telling_settings')) {
            Schema::table('fortune_telling_settings', function (Blueprint $table) {
                if (Schema::hasColumn('fortune_telling_settings', 'enable_celtic_person_role')) {
                    $table->dropColumn('enable_celtic_person_role');
                }
            });
        }
    }
};
