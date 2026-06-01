<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 🧠 (2026-06-01) แปลงความรู้สายมูเป็น "รายไพ่" (per-card เหมือนสุขภาพ)
     *
     * User: "ความรู้ทุกหมวดต้องตรงกับไพ่แต่ละใบ เหมือนสุขภาพ"
     * เดิม ฮวงจุ้ย/เจ้าที่/องค์เทพ/มนต์ดำ = แถว "หัวข้อรวม" (card_name NULL) 37 แถว
     * → ลบทิ้ง แล้ว reseed เป็นรายไพ่ 78 ใบ/หมวด (card_name = name_en) จาก config .cards
     */
    public function up(): void
    {
        if (! Schema::hasTable('fortune_knowledge')) {
            return;
        }

        // 1) ลบแถวหัวข้อรวมเดิม (topical, card_name NULL) ของ 4 หมวดมู
        try {
            DB::table('fortune_knowledge')
                ->whereIn('category', ['feng_shui', 'guardian_spirits', 'deities', 'black_magic'])
                ->whereNull('card_name')
                ->delete();
        } catch (\Throwable $e) {
            Log::warning('ลบ topical mu rows ล้มเหลว (non-blocking)', ['error' => $e->getMessage()]);
        }

        // 2) reseed per-card (idempotent firstOrCreate — เติม 78 ใบ/หมวด)
        try {
            Artisan::call('db:seed', [
                '--class' => \Database\Seeders\FortuneKnowledgeSeeder::class,
                '--force' => true,
            ]);
        } catch (\Throwable $e) {
            Log::warning('reseed FortuneKnowledge per-card ล้มเหลว (non-blocking)', ['error' => $e->getMessage()]);
        }
    }

    public function down(): void
    {
        // no-op — ไม่ลบข้อมูลความรู้ (กันลบของที่แอดมินแก้)
    }
};
