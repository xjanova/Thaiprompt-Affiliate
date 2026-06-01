<?php

namespace App\Services;

use App\Models\FortuneKnowledge;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * 🧠 FortuneKnowledgeService — RAG retrieval ของคลังความรู้แม่หมอ
 *
 * ดึง "องค์ความรู้ที่ตรงเรื่อง" จากตาราง fortune_knowledge (แอดมินแก้ได้) มาป้อนให้
 *   แม่หมอ (AI) ทำนายตามหน้าไพ่ — แทนคำตอบกว้างๆ
 *
 * Retrieval = เจาะจง (deterministic):
 *   - health → ตาม card_name (ไพ่ 10 ใบที่เปิด)
 *   - มู (ฮวงจุ้ย/เจ้าที่/องค์เทพ) → detect keyword → ดึง entries ของหมวดนั้น
 *   - black_magic → ดึงทั้งหมวด (เสริม buildBlackMagicDirective)
 *
 * Fallback: ถ้า DB ว่าง (ยังไม่ seed) → อ่านจาก config (fortune_tarot_health / fortune_mu_knowledge)
 *   → กัน regression. คืนค่า "เฉพาะข้อมูลความรู้" ส่วน "กฎการอ่าน/จรรยาบรรณ" อยู่ใน directive (โค้ด)
 *
 * อ้างอิงแพทเทิร์น: App\Services\AdminQARetriever (cache + retrieval)
 */
class FortuneKnowledgeService
{
    /** หมวดสายมูที่ detect จากคำถามได้ (black_magic มี directive ของตัวเองแยก) */
    public const MU_DETECTABLE = [
        FortuneKnowledge::CATEGORY_FENG_SHUI,
        FortuneKnowledge::CATEGORY_GUARDIAN_SPIRITS,
        FortuneKnowledge::CATEGORY_DEITIES,
    ];

    /** cache TTL สั้น — คลังความรู้เปลี่ยนไม่บ่อย แต่ให้แอดมินแก้แล้วเห็นไว */
    protected const CACHE_TTL = 300;

    // ════════════════════════════════════════════════════════════════
    // HEALTH — ตำราสุขภาพรายไพ่
    // ════════════════════════════════════════════════════════════════

    /**
     * สร้างบล็อกความรู้สุขภาพของไพ่ที่เปิด (เฉพาะ 10 ใบ — ไม่ dump ทั้งหมด)
     *
     * @param  array<int, array>  $cards  ผลจาก FortuneReading::getCelticCards()
     * @return string ว่าง = ไม่มีข้อมูล
     */
    public function healthLinesForCards(array $cards): string
    {
        return $this->linesFromCardMap($cards, $this->healthMap());
    }

    /**
     * สร้างบล็อกโหงวเฮ้ง/ลักษณะคน ของไพ่ที่เปิด (เฉพาะ 10 ใบ)
     *
     * @param  array<int, array>  $cards  ผลจาก FortuneReading::getCelticCards()
     */
    public function physiognomyLinesForCards(array $cards): string
    {
        return $this->linesFromCardMap($cards, $this->personaMap());
    }

    /**
     * สร้างบรรทัดรายไพ่จาก map (name_en => ['content']) — ใช้ร่วม health/physiognomy
     *
     * @param  array<int, array>  $cards
     * @param  array<string, array>  $map
     */
    protected function linesFromCardMap(array $cards, array $map): string
    {
        if (empty($map)) {
            return '';
        }

        $lines = [];
        for ($pos = 1; $pos <= 10; $pos++) {
            $card = $cards[$pos] ?? null;
            if (! $card) {
                continue;
            }
            $nameEn = (string) ($card['card_name_en'] ?? '');
            $entry = $map[$nameEn] ?? null;
            if (! $entry) {
                continue;
            }
            $nameTh = (string) ($card['card_name_th'] ?? '') ?: ($nameEn ?: '?');
            $orientation = ! empty($card['is_reversed']) ? '(กลับหัว)' : '(ตั้งตรง)';
            $positionName = (string) ($card['position_name'] ?? '?');

            $lines[] = "• ตำแหน่ง {$pos} [{$positionName}] — {$nameTh} {$orientation}\n"
                .'   '.str_replace("\n", "\n   ", trim((string) $entry['content']));
        }

        return implode("\n", $lines);
    }

    /**
     * แผนที่สุขภาพ: name_en => ['content', 'severity'] (DB → fallback config) + cache
     *
     * @return array<string, array>
     */
    protected function healthMap(): array
    {
        return Cache::remember('fortune_knowledge:health_map', self::CACHE_TTL, function () {
            // 1) DB ก่อน (try/catch — DB ล่ม/ยังไม่ migrate → ใช้ config fallback)
            try {
                if (Schema::hasTable('fortune_knowledge')) {
                    $rows = FortuneKnowledge::active()
                        ->byCategory(FortuneKnowledge::CATEGORY_HEALTH)
                        ->whereNotNull('card_name')
                        ->get();
                    if ($rows->isNotEmpty()) {
                        $map = [];
                        foreach ($rows as $r) {
                            $map[(string) $r->card_name] = [
                                'content' => (string) $r->content,
                                'severity' => (string) ($r->severity ?? ''),
                            ];
                        }

                        return $map;
                    }
                }
            } catch (\Throwable $e) {
                // fall through to config fallback
            }

            // 2) Fallback: config
            $map = [];
            foreach ((array) config('fortune_tarot_health.cards', []) as $nameEn => $e) {
                if (! is_array($e)) {
                    continue;
                }
                $map[(string) $nameEn] = [
                    'content' => 'อวัยวะ/ระบบ: '.(string) ($e['body'] ?? '')."\n"
                        .'ตั้งตรง: '.(string) ($e['up'] ?? '')."\n"
                        .'กลับหัว: '.(string) ($e['rev'] ?? ''),
                    'severity' => '',
                ];
            }

            return $map;
        });
    }

    /**
     * แผนที่โหงวเฮ้ง/ลักษณะคน: name_en => ['content'] (DB → fallback config) + cache
     *
     * @return array<string, array>
     */
    protected function personaMap(): array
    {
        return Cache::remember('fortune_knowledge:persona_map', self::CACHE_TTL, function () {
            // 1) DB ก่อน (try/catch — DB ล่ม/ยังไม่ migrate → ใช้ config fallback)
            try {
                if (Schema::hasTable('fortune_knowledge')) {
                    $rows = FortuneKnowledge::active()
                        ->byCategory(FortuneKnowledge::CATEGORY_PHYSIOGNOMY)
                        ->whereNotNull('card_name')
                        ->get();
                    if ($rows->isNotEmpty()) {
                        $map = [];
                        foreach ($rows as $r) {
                            $map[(string) $r->card_name] = ['content' => (string) $r->content];
                        }

                        return $map;
                    }
                }
            } catch (\Throwable $e) {
                // fall through to config fallback
            }

            // 2) Fallback: config
            $map = [];
            foreach ((array) config('fortune_card_persona.cards', []) as $nameEn => $e) {
                if (! is_array($e)) {
                    continue;
                }
                $map[(string) $nameEn] = [
                    'content' => 'รูปลักษณ์/โหงวเฮ้ง: '.(string) ($e['look'] ?? '')."\n"
                        .'นิสัย/ลักษณะ: '.(string) ($e['trait'] ?? '')."\n"
                        .'กลับหัว (ด้านลบ): '.(string) ($e['rev'] ?? ''),
                ];
            }

            return $map;
        });
    }

    // ════════════════════════════════════════════════════════════════
    // มู — ฮวงจุ้ย / เจ้าที่ / องค์เทพ
    // ════════════════════════════════════════════════════════════════

    /**
     * ตรวจว่าคำถามเกี่ยวหมวดมูใดบ้าง (จาก keyword ใน config — เป็น logic, ไม่ใช่ knowledge)
     *
     * @return array<string> รายชื่อ category ที่ตรง
     */
    public function detectMuCategories(string $text): array
    {
        $haystack = mb_strtolower($text);
        $hits = [];
        foreach (self::MU_DETECTABLE as $cat) {
            $keywords = (array) config("fortune_mu_knowledge.{$cat}.keywords", []);
            foreach ($keywords as $kw) {
                if ($kw !== '' && mb_strpos($haystack, mb_strtolower((string) $kw)) !== false) {
                    $hits[] = $cat;
                    break;
                }
            }
        }

        return $hits;
    }

    /**
     * สร้างบล็อกความรู้สายมูแบบ "รายไพ่" ของหมวดที่ระบุ (per-card เหมือนสุขภาพ)
     *
     * @param  array<int, array>  $cards  ไพ่ 10 ใบที่เปิด
     * @param  array<string>  $categories  หมวดที่ detect ได้
     */
    public function muLinesForCards(array $cards, array $categories): string
    {
        $blocks = [];
        foreach ($categories as $cat) {
            $lines = $this->linesFromCardMap($cards, $this->muCardMap($cat));
            if (trim($lines) === '') {
                continue;
            }
            $label = (string) config("fortune_mu_knowledge.{$cat}.label", $cat);
            $blocks[] = "【{$label}】\n".$lines;
        }

        return implode("\n\n", $blocks);
    }

    /**
     * ความรู้ไสยศาสตร์ "รายไพ่" (เสริม buildBlackMagicDirective)
     *
     * @param  array<int, array>  $cards
     */
    public function blackMagicLinesForCards(array $cards): string
    {
        return $this->linesFromCardMap($cards, $this->muCardMap(FortuneKnowledge::CATEGORY_BLACK_MAGIC));
    }

    /**
     * แผนที่ความรู้สายมูรายไพ่: name_en => ['content'] (DB → fallback config .cards) + cache
     *
     * @return array<string, array>
     */
    protected function muCardMap(string $category): array
    {
        return Cache::remember("fortune_knowledge:mucards:{$category}", self::CACHE_TTL, function () use ($category) {
            // 1) DB ก่อน (try/catch — DB ล่ม/ยังไม่ migrate → ใช้ config fallback)
            try {
                if (Schema::hasTable('fortune_knowledge')) {
                    $rows = FortuneKnowledge::active()
                        ->byCategory($category)
                        ->whereNotNull('card_name')
                        ->get();
                    if ($rows->isNotEmpty()) {
                        $map = [];
                        foreach ($rows as $r) {
                            $map[(string) $r->card_name] = ['content' => (string) $r->content];
                        }

                        return $map;
                    }
                }
            } catch (\Throwable $e) {
                // fall through to config fallback
            }

            // 2) Fallback: config .cards
            $map = [];
            foreach ((array) config("fortune_mu_knowledge.{$category}.cards", []) as $nameEn => $content) {
                $map[(string) $nameEn] = ['content' => (string) $content];
            }

            return $map;
        });
    }

    /**
     * ล้าง cache (เรียกตอนแอดมินแก้คลังความรู้)
     */
    public function clearCache(): void
    {
        Cache::forget('fortune_knowledge:health_map');
        Cache::forget('fortune_knowledge:persona_map');
        foreach (FortuneKnowledge::CATEGORIES as $cat) {
            Cache::forget("fortune_knowledge:mucards:{$cat}");
        }
    }
}
