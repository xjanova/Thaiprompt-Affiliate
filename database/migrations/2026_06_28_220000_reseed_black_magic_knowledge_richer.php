<?php

use App\Models\FortuneKnowledge;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 🪬 (2026-06-28, owner เคส FTU-260628-W2965) อัปเดตเนื้อหาคลังไสยศาสตร์รายไพ่ (black_magic) ให้ "อ่านสุด"
     *
     * ปัญหา: เนื้อหาเดิม ~60/78 ใบ ขึ้นต้น "ไม่มีของ · ปกติ" → บอทโหมดดูคุณไสย์ตอบบางๆ
     *   "ไม่ใช่ของ ให้ดูความจริง" + ไพ่ของแรง (ดวงจันทร์) พอกลับหัวถูกตัดเป็น "คลาย" = ล้างสัญญาณทิ้ง
     * แก้: เขียนใหม่ทั้ง 78 ใบให้เป็น "คำอ่านที่ฟันธงได้" (✅ เกราะ / ⚠️ สัญญาณของ / "ไม่ใช่ของ — คือ X")
     *   + ไพ่กลับหัวที่เป็นสัญญาณของ → "ฝังลึก/ค้างเก่า" ไม่ตัดเป็นคลายทันที (กันบอทล้างสัญญาณทิ้ง)
     *
     * ⚠️ deploy.sh รัน migrate ไม่รัน db:seed + seeder ใช้ firstOrCreate (ไม่อัปเดต row เดิม)
     *   → migration นี้ updateOrCreate เนื้อหาจาก config/fortune_mu_knowledge.php (black_magic)
     *   เฉพาะ row ที่ยังเป็นค่า default — ❌ ไม่แตะที่แอดมินแก้ (source != 'card-tie' หรือ updated_at != created_at)
     */
    public function up(): void
    {
        if (! Schema::hasTable('fortune_knowledge')) {
            return;
        }

        $cards = (array) config('fortune_mu_knowledge.black_magic.cards', []);
        $keywords = implode(',', (array) config('fortune_mu_knowledge.black_magic.keywords', []));
        $label = (string) config('fortune_mu_knowledge.black_magic.label', 'black_magic');
        if (empty($cards)) {
            return;
        }

        try {
            foreach ($cards as $nameEn => $content) {
                if ((string) $content === '') {
                    continue;
                }

                $row = FortuneKnowledge::where('category', FortuneKnowledge::CATEGORY_BLACK_MAGIC)
                    ->where('card_name', (string) $nameEn)
                    ->first();

                // สร้างใหม่ถ้าขาด (เครื่อง fresh ที่ยังไม่มีหมวดนี้)
                if (! $row) {
                    FortuneKnowledge::create([
                        'category' => FortuneKnowledge::CATEGORY_BLACK_MAGIC,
                        'card_name' => (string) $nameEn,
                        'subject' => (string) $nameEn,
                        'title' => $nameEn.' ('.$label.')',
                        'keywords' => $keywords,
                        'content' => (string) $content,
                        'priority' => 0,
                        'is_active' => true,
                        'source' => 'card-tie',
                    ]);

                    continue;
                }

                // ❌ ไม่แตะที่แอดมินแก้ — อัปเดตเฉพาะ row default เดิม
                $adminEdited = $row->source !== 'card-tie'
                    || ($row->updated_at && $row->created_at && ! $row->updated_at->equalTo($row->created_at));
                if ($adminEdited) {
                    continue;
                }

                $row->content = (string) $content;
                $row->keywords = $keywords;
                $row->save();
            }

            // ล้าง cache ของ FortuneKnowledgeService (TTL 300s) ให้บอทใช้เนื้อหาใหม่ทันที
            Cache::forget('fortune_knowledge:mucards:'.FortuneKnowledge::CATEGORY_BLACK_MAGIC);
        } catch (\Throwable $e) {
            Log::warning('Re-seed black_magic knowledge (richer) ใน migration ล้มเหลว (non-blocking)', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function down(): void
    {
        // no-op — ไม่ย้อนเนื้อหา (กันลบ/ทับของที่แอดมินแก้ภายหลัง)
    }
};
