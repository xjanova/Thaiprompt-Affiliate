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

    /** หมวด "ชีวิต" ที่ detect ได้ (per-card ใน config/fortune_card_life.php) */
    public const LIFE_DETECTABLE = [
        FortuneKnowledge::CATEGORY_AGE_RANGE,
        FortuneKnowledge::CATEGORY_TIMING,
        FortuneKnowledge::CATEGORY_CAREER_STUDY,
        FortuneKnowledge::CATEGORY_BUSINESS_WORK,
    ];

    /** หมวด "ดวงจิต/กรรม" ที่ detect ได้ (per-card ใน config/fortune_card_destiny.php) */
    public const DESTINY_DETECTABLE = [
        FortuneKnowledge::CATEGORY_SPIRITUAL_CALLING,
        FortuneKnowledge::CATEGORY_PAST_LIFE,
    ];

    /** หมวด "ความรัก/เนื้อคู่" ที่ detect ได้ (per-card ใน config/fortune_card_love.php) */
    public const LOVE_DETECTABLE = [
        FortuneKnowledge::CATEGORY_LOVE_RELATIONSHIP,
    ];

    /** หมวด "การเงิน/โชคลาภ" ที่ detect ได้ (per-card ใน config/fortune_card_wealth.php) */
    public const WEALTH_DETECTABLE = [
        FortuneKnowledge::CATEGORY_WEALTH_LUCK,
    ];

    /** หมวด "ฤกษ์ยาม/วันมงคล" ที่ detect ได้ (per-card ใน config/fortune_card_timing_auspicious.php) */
    public const AUSPICIOUS_DETECTABLE = [
        FortuneKnowledge::CATEGORY_AUSPICIOUS_TIMING,
    ];

    /** หมวด "เลขศาสตร์/เบอร์มงคล" ที่ detect ได้ (per-card ใน config/fortune_card_numerology.php) */
    public const NUMEROLOGY_DETECTABLE = [
        FortuneKnowledge::CATEGORY_NUMEROLOGY,
    ];

    /** หมวด "ของมงคล/สีมงคล/เครื่องราง" ที่ detect ได้ (per-card ใน config/fortune_card_lucky_items.php) */
    public const LUCKY_ITEMS_DETECTABLE = [
        FortuneKnowledge::CATEGORY_LUCKY_ITEMS,
    ];

    /** หมวด "จิตใจ/อารมณ์" ที่ detect ได้ (per-card ใน config/fortune_card_mental.php) */
    public const MENTAL_DETECTABLE = [
        FortuneKnowledge::CATEGORY_MENTAL_EMOTIONAL,
    ];

    /** หมวด "ครอบครัว/บุตร/บริวาร" ที่ detect ได้ (per-card ใน config/fortune_card_family.php) */
    public const FAMILY_DETECTABLE = [
        FortuneKnowledge::CATEGORY_FAMILY_CHILDREN,
    ];

    /** หมวด "เดินทาง/ต่างแดน/ย้ายถิ่น" ที่ detect ได้ (per-card ใน config/fortune_card_travel.php) */
    public const TRAVEL_DETECTABLE = [
        FortuneKnowledge::CATEGORY_TRAVEL_ABROAD,
    ];

    /** หมวด "คดีความ/ข้อพิพาท/สัญญา" ที่ detect ได้ (per-card ใน config/fortune_card_legal.php) */
    public const LEGAL_DETECTABLE = [
        FortuneKnowledge::CATEGORY_LEGAL_DISPUTES,
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
        return $this->detectCategories($text, self::MU_DETECTABLE);
    }

    /**
     * ตรวจหมวด "ชีวิต" (ช่วงอายุ/สถานการณ์/การศึกษา-อาชีพ/การงาน)
     *
     * @return array<string>
     */
    public function detectLifeCategories(string $text): array
    {
        return $this->detectCategories($text, self::LIFE_DETECTABLE);
    }

    /**
     * ตรวจหมวด "ดวงจิต/กรรม" (สายญาณ/ผู้มีองค์/อดีตชาติ)
     *
     * @return array<string>
     */
    public function detectDestinyCategories(string $text): array
    {
        return $this->detectCategories($text, self::DESTINY_DETECTABLE);
    }

    /**
     * ตรวจหมวด "ความรัก/เนื้อคู่"
     *
     * @return array<string>
     */
    public function detectLoveCategories(string $text): array
    {
        return $this->detectCategories($text, self::LOVE_DETECTABLE);
    }

    /**
     * ตรวจหมวด "การเงิน/โชคลาภ"
     *
     * @return array<string>
     */
    public function detectWealthCategories(string $text): array
    {
        return $this->detectCategories($text, self::WEALTH_DETECTABLE);
    }

    /**
     * ตรวจหมวด "ฤกษ์ยาม/วันมงคล"
     *
     * @return array<string>
     */
    public function detectAuspiciousCategories(string $text): array
    {
        return $this->detectCategories($text, self::AUSPICIOUS_DETECTABLE);
    }

    /**
     * ตรวจหมวด "เลขศาสตร์/เบอร์มงคล"
     *
     * @return array<string>
     */
    public function detectNumerologyCategories(string $text): array
    {
        return $this->detectCategories($text, self::NUMEROLOGY_DETECTABLE);
    }

    /**
     * ตรวจหมวด "ของมงคล/สีมงคล/เครื่องราง"
     *
     * @return array<string>
     */
    public function detectLuckyItemsCategories(string $text): array
    {
        return $this->detectCategories($text, self::LUCKY_ITEMS_DETECTABLE);
    }

    /**
     * ตรวจหมวด "จิตใจ/อารมณ์"
     *
     * @return array<string>
     */
    public function detectMentalCategories(string $text): array
    {
        return $this->detectCategories($text, self::MENTAL_DETECTABLE);
    }

    /**
     * ตรวจหมวด "ครอบครัว/บุตร/บริวาร"
     *
     * @return array<string>
     */
    public function detectFamilyCategories(string $text): array
    {
        return $this->detectCategories($text, self::FAMILY_DETECTABLE);
    }

    /**
     * ตรวจหมวด "เดินทาง/ต่างแดน/ย้ายถิ่น"
     *
     * @return array<string>
     */
    public function detectTravelCategories(string $text): array
    {
        return $this->detectCategories($text, self::TRAVEL_DETECTABLE);
    }

    /**
     * ตรวจหมวด "คดีความ/ข้อพิพาท/สัญญา"
     *
     * @return array<string>
     */
    public function detectLegalCategories(string $text): array
    {
        return $this->detectCategories($text, self::LEGAL_DETECTABLE);
    }

    /**
     * ตรวจว่าคำถามตรงหมวดใดใน $categories (จาก keyword ใน config — logic)
     *
     * @param  array<string>  $categories
     * @return array<string>
     */
    protected function detectCategories(string $text, array $categories): array
    {
        $haystack = mb_strtolower($text);
        $hits = [];
        foreach ($categories as $cat) {
            $keywords = (array) config($this->configBaseFor($cat).'.keywords', []);
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
     * config base ของหมวด: ชีวิต → fortune_card_life.{cat} / อื่น → fortune_mu_knowledge.{cat}
     */
    protected function configBaseFor(string $category): string
    {
        if (in_array($category, self::LIFE_DETECTABLE, true)) {
            return "fortune_card_life.{$category}";
        }
        if (in_array($category, self::DESTINY_DETECTABLE, true)) {
            return "fortune_card_destiny.{$category}";
        }
        if (in_array($category, self::LOVE_DETECTABLE, true)) {
            return "fortune_card_love.{$category}";
        }
        if (in_array($category, self::WEALTH_DETECTABLE, true)) {
            return "fortune_card_wealth.{$category}";
        }
        if (in_array($category, self::AUSPICIOUS_DETECTABLE, true)) {
            return "fortune_card_timing_auspicious.{$category}";
        }
        if (in_array($category, self::NUMEROLOGY_DETECTABLE, true)) {
            return "fortune_card_numerology.{$category}";
        }
        if (in_array($category, self::LUCKY_ITEMS_DETECTABLE, true)) {
            return "fortune_card_lucky_items.{$category}";
        }
        if (in_array($category, self::MENTAL_DETECTABLE, true)) {
            return "fortune_card_mental.{$category}";
        }
        if (in_array($category, self::FAMILY_DETECTABLE, true)) {
            return "fortune_card_family.{$category}";
        }
        if (in_array($category, self::TRAVEL_DETECTABLE, true)) {
            return "fortune_card_travel.{$category}";
        }
        if (in_array($category, self::LEGAL_DETECTABLE, true)) {
            return "fortune_card_legal.{$category}";
        }

        return "fortune_mu_knowledge.{$category}";
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
            $label = (string) config($this->configBaseFor($cat).'.label', $cat);
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
            foreach ((array) config($this->configBaseFor($category).'.cards', []) as $nameEn => $content) {
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
