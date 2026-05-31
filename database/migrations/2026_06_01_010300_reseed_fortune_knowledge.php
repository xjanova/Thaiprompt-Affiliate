<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 🧠 (2026-06-01) Re-seed คลังความรู้แม่หมอ — เติมหมวดที่เพิ่มทีหลัง (โหงวเฮ้ง ฯลฯ)
     *
     * 🐞 Bug ที่แก้: migration สร้างตาราง (010000) auto-seed แค่ "ตอนสร้างครั้งแรก"
     *   → ตอน deploy RAG base ยังไม่มีหมวด physiognomy → 78 ใบโหงวเฮ้งไม่ถูก seed เข้า DB
     *   (create-table migration ไม่ rerun เพราะ table มีแล้ว)
     *
     * Fix: migration นี้เรียก FortuneKnowledgeSeeder ซ้ำ (idempotent firstOrCreate
     *   → เติมเฉพาะที่ขาด ไม่ทับ/ไม่ซ้ำของเดิม + ไม่แตะที่แอดมินแก้)
     *
     * ⚠️ บทเรียน: เพิ่มข้อมูล seed ใหม่ใน FortuneKnowledgeSeeder ครั้งหน้า →
     *   ต้องเพิ่ม re-seed migration แบบนี้ด้วย (deploy.sh รัน migrate ไม่รัน db:seed)
     */
    public function up(): void
    {
        if (! Schema::hasTable('fortune_knowledge')) {
            return;
        }

        try {
            Artisan::call('db:seed', [
                '--class' => \Database\Seeders\FortuneKnowledgeSeeder::class,
                '--force' => true,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Re-seed FortuneKnowledge ใน migration ล้มเหลว (non-blocking)', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function down(): void
    {
        // no-op — ไม่ลบข้อมูลความรู้ (กันลบของที่แอดมินแก้)
    }
};
