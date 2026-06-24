<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 🕉️ (2026-06-25) Re-seed คลังความรู้แม่หมอ — เติมหมวดใหม่ "องค์เทพประจำตัว" (patron_deity, 78 ใบ)
     *
     * หมวดนี้ใช้ในการทำนาย (RAG) ตอบ "มีองค์เทพประจำตัวไหม / องค์ไหน / ใหญ่เล็ก / บูชาเปิดดวง"
     *   แยกต่างหากจาก deities (ขอพร/ไหว้องค์ไหน). seed มาจาก config/fortune_mu_knowledge.php
     *   (seedPerCardGroup auto-iterate ทุก category ใน config → เติม patron_deity ให้เอง)
     *
     * ⚠️ deploy.sh รัน migrate ไม่รัน db:seed → ต้องมี re-seed migration นี้
     *   idempotent firstOrCreate → เติมเฉพาะที่ขาด ไม่ทับ/ไม่ซ้ำ + ไม่แตะที่แอดมินแก้
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
            Log::warning('Re-seed FortuneKnowledge (patron_deity) ใน migration ล้มเหลว (non-blocking)', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function down(): void
    {
        // no-op — ไม่ลบข้อมูลความรู้ (กันลบของที่แอดมินแก้)
    }
};
