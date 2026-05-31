<?php

namespace Database\Seeders;

use App\Models\FortuneKnowledge;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

/**
 * 🧠 Seed คลังองค์ความรู้แม่หมอ (RAG) จาก config → ตาราง fortune_knowledge
 *
 * อ่านจาก 2 แหล่ง:
 *   - config/fortune_tarot_health.php  → สุขภาพรายไพ่ (78 ใบ, card_name = name_en)
 *   - config/fortune_mu_knowledge.php  → ฮวงจุ้ย/เจ้าที่/องค์เทพ/มนต์ดำ
 *
 * ⚠️ idempotent แบบ "create-if-missing" (firstOrCreate) — รันซ้ำปลอดภัย
 *    + "ไม่ทับ" ของที่แอดมินแก้ไปแล้วใน DB (สำคัญ: คลังนี้แอดมินแก้เองได้)
 */
class FortuneKnowledgeSeeder extends Seeder
{
    public function run(): void
    {
        // เผื่อถูกเรียกก่อนตารางถูกสร้าง (กัน fatal)
        if (! Schema::hasTable('fortune_knowledge')) {
            return;
        }

        $this->command?->info('🌱 กำลัง seed คลังความรู้แม่หมอ (RAG)...');

        $health = $this->seedHealth();
        $persona = $this->seedPhysiognomy();
        $mu = $this->seedMuKnowledge();

        $this->command?->info("✅ Seed คลังความรู้สำเร็จ — สุขภาพ {$health} + โหงวเฮ้ง {$persona} + สายมู {$mu} หัวข้อ");
    }

    /**
     * Seed ตำราสุขภาพรายไพ่ จาก config/fortune_tarot_health.php
     */
    protected function seedHealth(): int
    {
        $tome = (array) config('fortune_tarot_health.cards', []);
        $count = 0;

        foreach ($tome as $nameEn => $entry) {
            if (! is_array($entry)) {
                continue;
            }
            $body = (string) ($entry['body'] ?? '');
            $up = (string) ($entry['up'] ?? '');
            $rev = (string) ($entry['rev'] ?? '');

            $content = "อวัยวะ/ระบบ: {$body}\n"
                ."ตั้งตรง: {$up}\n"
                ."กลับหัว: {$rev}";

            // ความรุนแรง: เดาจากเครื่องหมาย ⚠️ ในเนื้อหา
            $severity = str_contains($up.$rev, '⚠️⚠️') ? 'high'
                : (str_contains($up.$rev, '⚠️') ? 'medium' : 'low');

            FortuneKnowledge::firstOrCreate(
                [
                    'category' => FortuneKnowledge::CATEGORY_HEALTH,
                    'card_name' => $nameEn,
                ],
                [
                    'subject' => $nameEn,
                    'title' => "{$nameEn} (สุขภาพ)",
                    'keywords' => $nameEn,
                    'content' => $content,
                    'severity' => $severity,
                    'priority' => 0,
                    'is_active' => true,
                    'source' => 'Liber 777 / lilly-tarot / teachmetarot',
                ]
            );
            $count++;
        }

        return $count;
    }

    /**
     * Seed ตำราโหงวเฮ้ง/ลักษณะคน รายไพ่ จาก config/fortune_card_persona.php
     */
    protected function seedPhysiognomy(): int
    {
        $tome = (array) config('fortune_card_persona.cards', []);
        $count = 0;

        foreach ($tome as $nameEn => $entry) {
            if (! is_array($entry)) {
                continue;
            }
            $look = (string) ($entry['look'] ?? '');
            $trait = (string) ($entry['trait'] ?? '');
            $rev = (string) ($entry['rev'] ?? '');

            $content = "รูปลักษณ์/โหงวเฮ้ง: {$look}\n"
                ."นิสัย/ลักษณะ: {$trait}\n"
                ."กลับหัว (ด้านลบ): {$rev}";

            FortuneKnowledge::firstOrCreate(
                [
                    'category' => FortuneKnowledge::CATEGORY_PHYSIOGNOMY,
                    'card_name' => $nameEn,
                ],
                [
                    'subject' => $nameEn,
                    'title' => "{$nameEn} (โหงวเฮ้ง)",
                    'keywords' => $nameEn,
                    'content' => $content,
                    'priority' => 0,
                    'is_active' => true,
                    'source' => 'Golden Dawn persona / โหงวเฮ้งจีน',
                ]
            );
            $count++;
        }

        return $count;
    }

    /**
     * Seed องค์ความรู้สายมู จาก config/fortune_mu_knowledge.php
     */
    protected function seedMuKnowledge(): int
    {
        $tome = (array) config('fortune_mu_knowledge', []);
        $count = 0;

        foreach ($tome as $category => $section) {
            if (! is_array($section)) {
                continue;
            }
            $keywords = implode(',', (array) ($section['keywords'] ?? []));
            $entries = (array) ($section['entries'] ?? []);

            foreach ($entries as $entry) {
                if (! is_array($entry) || empty($entry['title'])) {
                    continue;
                }

                FortuneKnowledge::firstOrCreate(
                    [
                        'category' => $category,
                        'title' => (string) $entry['title'],
                    ],
                    [
                        'card_name' => null,
                        'subject' => (string) ($entry['subject'] ?? ''),
                        'keywords' => $keywords,
                        'content' => (string) ($entry['content'] ?? ''),
                        'severity' => null,
                        'priority' => (int) ($entry['priority'] ?? 0),
                        'is_active' => true,
                        'source' => (string) ($entry['source'] ?? ''),
                    ]
                );
                $count++;
            }
        }

        return $count;
    }
}
