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
        FortuneKnowledge::CATEGORY_PATRON_DEITY,
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

    /** หมวด "แก้กรรม/สะเดาะเคราะห์/เสริมดวง" ที่ detect ได้ (per-card ใน config/fortune_card_remedy.php) */
    public const REMEDY_DETECTABLE = [
        FortuneKnowledge::CATEGORY_REMEDY_BOOST,
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
     * สร้างบล็อกตำแหน่งบุคคล/ระบุตัวคน ของไพ่ที่เปิด (เฉพาะ 10 ใบ)
     *
     * @param  array<int, array>  $cards  ผลจาก FortuneReading::getCelticCards()
     */
    public function personRoleLinesForCards(array $cards): string
    {
        return $this->linesFromCardMap($cards, $this->personRoleMap());
    }

    /**
     * สร้างบรรทัดรายไพ่จาก map (name_en => ['content']) — ใช้ร่วม health/physiognomy/person_role
     *
     * @param  array<int, array>  $cards
     * @param  array<string, array>  $map
     */
    protected function linesFromCardMap(array $cards, array $map, array $onlyPositions = []): string
    {
        if (empty($map)) {
            return '';
        }

        $lines = [];
        for ($pos = 1; $pos <= 10; $pos++) {
            // จำกัดตำแหน่ง (ถ้าผู้เรียกระบุ) — ว่าง = เอาครบเหมือนเดิม
            if (! empty($onlyPositions) && ! in_array($pos, $onlyPositions, true)) {
                continue;
            }

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
            $isReversed = ! empty($card['is_reversed']);
            $orientation = $isReversed ? '(กลับหัว)' : '(ตั้งตรง)';
            $positionName = (string) ($card['position_name'] ?? '?');

            // 🎯 (2026-09-01) ส่งเฉพาะคำแปล "ด้านที่ไพ่ออกจริง" — ห้ามส่งทั้งสองด้าน
            $content = $this->orientedContent((string) $entry['content'], $isReversed);
            if ($content === '') {
                continue;
            }

            $lines[] = "• ตำแหน่ง {$pos} [{$positionName}] — {$nameTh} {$orientation}\n"
                .'   '.str_replace("\n", "\n   ", $content);
        }

        return implode("\n", $lines);
    }

    /**
     * 🎯 ตัดคำแปลให้เหลือเฉพาะ "ด้านที่ไพ่ออกจริง" (ตั้งตรง หรือ กลับหัว)
     *
     * ⚠️ ทำไมต้องมี (2026-09-01 — owner: "ตอบเซฟตลอด บอกว่าได้ แต่ขัดแย้งว่าไม่ได้ง่ายๆ"):
     *   คลังความรู้เก็บสองด้านไว้ในสตริงเดียว —
     *     'The Chariot' => '✨✨ ฤกษ์ออกรถ-เดินทางก้าวหน้า · กลับหัว = ⚠️ ทิศทางไม่ลงตัว-รถเสีย-ทางตัน'
     *   เดิม dump ทั้งก้อนให้ AI ทุกใบ (~935 entries ที่พ่วงด้านตรงข้าม) → ไพ่ตั้งตรงชี้ "ได้"
     *   แต่ AI ยังเห็น "⚠️ ทางตัน" ในบรรทัดเดียวกัน → เกลี่ยออกมาเป็น "ได้ แต่ไม่ง่าย"
     *   = เครื่องผลิตคำตอบกั๊กโดยตรง ทั้งที่ระบบรู้อยู่แล้วว่าไพ่ออกด้านไหน
     *
     * 🛡️ ปลอดภัยกับข้อมูลที่แอดมินแก้เอง (DB): ไม่เจอตัวคั่น → คืนทั้งก้อนเหมือนเดิม
     *   และถ้าครึ่งที่เลือกว่างเปล่า → fallback เป็นทั้งก้อน (ดีกว่าไพ่ใบนั้นหายไปเงียบๆ)
     *
     * @param  string  $content  เนื้อความจากคลัง (DB หรือ config)
     * @param  bool  $isReversed  ไพ่ใบนี้ออกกลับหัวหรือไม่
     * @return string ว่าง = ไม่มีเนื้อความให้ใช้
     *
     * @example
     * $this->orientedContent('ดีมาก · กลับหัว = ⚠️ ติดขัด', false); // → 'ดีมาก'
     * $this->orientedContent('ดีมาก · กลับหัว = ⚠️ ติดขัด', true);  // → '⚠️ ติดขัด'
     */
    protected function orientedContent(string $content, bool $isReversed): string
    {
        $content = trim($content);
        if ($content === '') {
            return '';
        }

        // ตัวคั่นที่ใช้จริงในคลัง = ' · กลับหัว = ' — เผื่อ ':' และช่องว่างไม่ตรงสำหรับแถวที่แอดมินพิมพ์เอง
        $parts = preg_split('/\s*·?\s*กลับหัว\s*[=:]\s*/u', $content, 2);

        // ไม่เจอตัวคั่น (หรือ regex พัง) → คืนทั้งก้อนเหมือนเดิม
        if (! is_array($parts) || count($parts) < 2) {
            return $content;
        }

        $picked = trim((string) ($isReversed ? $parts[1] : $parts[0]));

        // ครึ่งที่เลือกว่าง (เช่นเขียน 'กลับหัว = ...' โดยไม่มีด้านตั้งตรง) → ใช้ทั้งก้อนกันข้อมูลหาย
        return $picked !== '' ? $picked : $content;
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

    /**
     * แผนที่ตำแหน่งบุคคล: name_en => ['content'] (DB → fallback config) + cache
     *
     * @return array<string, array>
     */
    protected function personRoleMap(): array
    {
        return Cache::remember('fortune_knowledge:person_role_map', self::CACHE_TTL, function () {
            // 1) DB ก่อน (try/catch — DB ล่ม/ยังไม่ migrate → ใช้ config fallback)
            try {
                if (Schema::hasTable('fortune_knowledge')) {
                    $rows = FortuneKnowledge::active()
                        ->byCategory(FortuneKnowledge::CATEGORY_PERSON_ROLE)
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
            foreach ((array) config('fortune_card_person_role.cards', []) as $nameEn => $e) {
                if (! is_array($e)) {
                    continue;
                }
                $map[(string) $nameEn] = [
                    'content' => 'ตำแหน่งบุคคลที่ไพ่นี้มักแทน: '.(string) ($e['roles'] ?? '')."\n"
                        .'อ่านอย่างไร: '.(string) ($e['note'] ?? '')."\n"
                        .'กลับหัว/ด้านลบ: '.(string) ($e['rev'] ?? ''),
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
     * ตรวจหมวด "แก้กรรม/สะเดาะเคราะห์/เสริมดวง"
     *
     * @return array<string>
     */
    public function detectRemedyCategories(string $text): array
    {
        return $this->detectCategories($text, self::REMEDY_DETECTABLE);
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
        if (in_array($category, self::REMEDY_DETECTABLE, true)) {
            return "fortune_card_remedy.{$category}";
        }

        return "fortune_mu_knowledge.{$category}";
    }

    /**
     * สร้างบล็อกความรู้สายมูแบบ "รายไพ่" ของหมวดที่ระบุ (per-card เหมือนสุขภาพ)
     *
     * @param  array<int, array>  $cards  ไพ่ 10 ใบที่เปิด
     * @param  array<string>  $categories  หมวดที่ detect ได้
     */
    /**
     * @param  array<int>  $onlyPositions  จำกัดเฉพาะตำแหน่งไพ่ที่ระบุ (ว่าง = ครบ 10 ใบ)
     *                                     ใช้กับ prompt ที่ "ไม่ได้อธิบายไพ่" (บทสรุป) — ลดขนาด prompt
     *                                     และลดโอกาสโมเดลเลือกเลข/สี/ฤกษ์มั่วจาก 10 ชุดที่ให้เลือก
     */
    public function muLinesForCards(array $cards, array $categories, array $onlyPositions = []): string
    {
        $blocks = [];
        foreach ($categories as $cat) {
            $lines = $this->linesFromCardMap($cards, $this->muCardMap($cat), $onlyPositions);
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
     * 🪬 (2026-06-18) ไสยศาสตร์ "เฉพาะใบที่มีสัญญาณจริง" (orientation-aware) — สำหรับ proactive card-scan
     *
     * ต่างจาก blackMagicLinesForCards (เต็ม รวมบรรทัด "ไม่มีของ") ที่ใช้ตอนลูกค้า "ถาม" เรื่องของ
     * โดยตรง (ต้องโชว์ครบเพื่อให้แม่หมอยืนยัน "ใบนี้ไม่มีของ" ปลอบใจได้). เมธอดนี้ใช้ตอน "แม่หมอ
     * สแกนเอง" (พื้นดวงเปิดตัว) → คืนเฉพาะใบที่ orientation ปัจจุบันส่งสัญญาณจริง (⚠️/อาจมีของ) เพื่อ:
     *   (1) ตัด noise บรรทัด "ไม่มีของ" ~9 บรรทัด → ลด token
     *   (2) กัน fear-anchoring (โมเดลเล็กไม่เห็นลิสต์ "ไม่มีของ" ยาวๆ แล้วเผลอปั้นเรื่องของ)
     *   (3) ทำให้ gate "พาดหัวโดนของได้เฉพาะมีบรรทัดเตือนจริง" สะอาด (ไม่มีสัญญาณ = บล็อกว่าง)
     *
     * เนื้อหารายไพ่รูปแบบ "ตั้งตรง · กลับหัว = ..." → เลือก "เฉพาะส่วนของ orientation ที่เปิดจริง"
     * (กัน false-positive: ⚠️ ที่อยู่ฝั่งกลับหัว จะไม่ติดถ้าไพ่ออกตั้งตรง)
     *
     * @param  array<int, array>  $cards
     * @return string ว่าง = ไม่มีใบไหนส่งสัญญาณของในสำรับนี้
     */
    public function blackMagicSignalLinesForCards(array $cards): string
    {
        $map = $this->muCardMap(FortuneKnowledge::CATEGORY_BLACK_MAGIC);
        if (empty($map)) {
            return '';
        }

        $lines = [];
        for ($pos = 1; $pos <= 10; $pos++) {
            $card = $cards[$pos] ?? null;
            if (! $card) {
                continue;
            }
            $entry = $map[(string) ($card['card_name_en'] ?? '')] ?? null;
            if (! $entry) {
                continue;
            }
            $content = trim((string) ($entry['content'] ?? ''));
            if ($content === '') {
                continue;
            }

            // เลือกเฉพาะส่วนของ orientation ปัจจุบัน (รูปแบบ "ตั้งตรง · กลับหัว = ...")
            $reversed = ! empty($card['is_reversed']);
            $active = $content;
            if (mb_strpos($content, '· กลับหัว =') !== false) {
                [$up, $rev] = array_pad(explode('· กลับหัว =', $content, 2), 2, '');
                $active = trim($reversed ? $rev : $up);
            }

            // มีสัญญาณจริงเฉพาะเมื่อ active portion เตือน (⚠️/อาจมีของ) และไม่ใช่ "ไม่มีของ/ปลอดของ"
            $hasSignal = (mb_strpos($active, '⚠️') !== false || mb_strpos($active, 'อาจมีของ') !== false)
                && mb_strpos($active, 'ไม่มีของ') === false
                && mb_strpos($active, 'ปลอดของ') === false;
            if (! $hasSignal) {
                continue;
            }

            $nameTh = ((string) ($card['card_name_th'] ?? '')) ?: ((string) ($card['card_name_en'] ?? '') ?: '?');
            $orientation = $reversed ? '(กลับหัว)' : '(ตั้งตรง)';
            $positionName = (string) ($card['position_name'] ?? '?');
            $lines[] = "• ตำแหน่ง {$pos} [{$positionName}] — {$nameTh} {$orientation}\n   ".str_replace("\n", "\n   ", $active);
        }

        return implode("\n", $lines);
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

    // ════════════════════════════════════════════════════════════════
    // 🔗 ไพ่คู่/ไพ่สัมพันธ์ — ความหมายพิเศษเมื่อไพ่ 2 ใบออกด้วยกัน
    // ════════════════════════════════════════════════════════════════

    /**
     * สร้างบล็อก "ไพ่คู่" ที่ปรากฏจริงบนโต๊ะ (เช็คทุกคู่ใน 10 ใบที่เปิด)
     *
     * คู่ไพ่ = กลไก "เชื่อมโยงไพ่" (ไม่ใช่ความหมายรายใบ) — ออกเฉพาะคู่ที่มีจริง
     *
     * @param  array<int, array>  $cards  ไพ่ 10 ใบ (จาก FortuneReading::getCelticCards)
     * @return string ว่าง = ไม่เจอคู่เด่นในสำรับนี้
     */
    public function comboLinesForCards(array $cards): string
    {
        $combos = $this->comboMap();
        if (empty($combos)) {
            return '';
        }

        // รวบรวมไพ่ที่เปิด (name_en => meta ใบแรกที่เจอ)
        $present = [];
        for ($pos = 1; $pos <= 10; $pos++) {
            $card = $cards[$pos] ?? null;
            if (! $card) {
                continue;
            }
            $nameEn = (string) ($card['card_name_en'] ?? '');
            if ($nameEn === '' || isset($present[$nameEn])) {
                continue;
            }
            $present[$nameEn] = [
                'pos' => $pos,
                'th' => ((string) ($card['card_name_th'] ?? '')) ?: $nameEn,
                'rev' => ! empty($card['is_reversed']),
            ];
        }

        $names = array_keys($present);
        $count = count($names);
        $lines = [];

        // เช็คทุกคู่ (ไม่ซ้ำ ไม่สนลำดับ)
        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $key = $this->comboKey($names[$i], $names[$j]);
                if (! isset($combos[$key])) {
                    continue;
                }
                $entry = $combos[$key];
                $a = $present[$names[$i]];
                $b = $present[$names[$j]];
                $oa = $a['rev'] ? '(กลับหัว)' : '';
                $ob = $b['rev'] ? '(กลับหัว)' : '';
                $tone = trim((string) ($entry['tone'] ?? ''));
                $tone = ($tone === '' || $tone === '—') ? '' : $tone.' ';
                $lines[] = "• {$a['th']}{$oa} [ต.{$a['pos']}] + {$b['th']}{$ob} [ต.{$b['pos']}] → "
                    .$tone.trim((string) ($entry['meaning'] ?? ''));
            }
        }

        return implode("\n", $lines);
    }

    /**
     * แผนที่ไพ่คู่: 'a|b'(เรียง) => ['tone','meaning'] (จาก config) + cache
     *
     * @return array<string, array>
     */
    protected function comboMap(): array
    {
        return Cache::remember('fortune_knowledge:combos', self::CACHE_TTL, function () {
            $map = [];
            foreach ((array) config('fortune_card_combos.combos', []) as $entry) {
                if (! is_array($entry)) {
                    continue;
                }
                $a = (string) ($entry['a'] ?? '');
                $b = (string) ($entry['b'] ?? '');
                if ($a === '' || $b === '') {
                    continue;
                }
                $map[$this->comboKey($a, $b)] = [
                    'tone' => (string) ($entry['tone'] ?? ''),
                    'meaning' => (string) ($entry['meaning'] ?? ''),
                ];
            }

            return $map;
        });
    }

    /**
     * key ของคู่ไพ่ — เรียงชื่อให้ lookup ได้ไม่สนลำดับ a/b
     */
    protected function comboKey(string $a, string $b): string
    {
        $pair = [$a, $b];
        sort($pair);

        return implode('|', $pair);
    }

    // ════════════════════════════════════════════════════════════════
    // 🎴 อ่านภาพรวมสำรับ — Major/สำรับเด่น/กลับหัว/ราชสำนัก/Ace/เลขซ้ำ
    // ════════════════════════════════════════════════════════════════

    /** ชื่อ Major Arcana 22 ใบ (name_en) สำหรับจำแนกสำรับ */
    protected const MAJOR_ARCANA = [
        'The Fool', 'The Magician', 'The High Priestess', 'The Empress', 'The Emperor',
        'The Hierophant', 'The Lovers', 'The Chariot', 'Strength', 'The Hermit',
        'Wheel of Fortune', 'Justice', 'The Hanged Man', 'Death', 'Temperance',
        'The Devil', 'The Tower', 'The Star', 'The Moon', 'The Sun', 'Judgement', 'The World',
    ];

    /** Rank → เลข (Pip + ราชสำนัก) */
    protected const RANK_NUMBER = [
        'Ace' => 1, 'Two' => 2, 'Three' => 3, 'Four' => 4, 'Five' => 5,
        'Six' => 6, 'Seven' => 7, 'Eight' => 8, 'Nine' => 9, 'Ten' => 10,
    ];

    /**
     * สร้างบล็อก "ภาพรวมสำรับ" จากการนับ 10 ใบ (Major/สำรับ/กลับหัว/ราชสำนัก/Ace/เลขซ้ำ)
     *
     * @param  array<int, array>  $cards
     * @return string ว่าง = สำรับไม่ครบ 10
     */
    public function spreadPatternLines(array $cards): string
    {
        // นับสถิติสำรับ
        $total = 0;
        $major = 0;
        $reversed = 0;
        $court = 0;
        $aces = 0;
        $suit = ['Wands' => 0, 'Cups' => 0, 'Swords' => 0, 'Pentacles' => 0];
        $numberCount = [];

        for ($pos = 1; $pos <= 10; $pos++) {
            $card = $cards[$pos] ?? null;
            if (! $card) {
                continue;
            }
            $nameEn = (string) ($card['card_name_en'] ?? '');
            if ($nameEn === '') {
                continue;
            }
            $total++;
            if (! empty($card['is_reversed'])) {
                $reversed++;
            }

            $c = $this->classifyCard($nameEn);
            if ($c['arcana'] === 'major') {
                $major++;

                continue;
            }
            if (isset($suit[$c['suit']])) {
                $suit[$c['suit']]++;
            }
            if ($c['isCourt']) {
                $court++;
            }
            if ($c['isAce']) {
                $aces++;
            }
            if ($c['number'] !== null && ! $c['isCourt']) {
                $numberCount[$c['number']] = ($numberCount[$c['number']] ?? 0) + 1;
            }
        }

        if ($total < 10) {
            return '';
        }

        $cfg = (array) config('fortune_spread_patterns', []);
        $lines = [];

        // 1) Major Arcana (เกณฑ์: ≤2 น้อย / 5-6 เด่น / 7+ ท่วม)
        if ($major >= 7) {
            $lines[] = '• '.($cfg['major']['dominant'] ?? '')." (Major {$major}/10)";
        } elseif ($major >= 5) {
            $lines[] = '• '.($cfg['major']['heavy'] ?? '')." (Major {$major}/10)";
        } elseif ($major <= 2) {
            $lines[] = '• '.($cfg['major']['few'] ?? '')." (Major {$major}/10)";
        }

        // 2) สำรับเด่น (≥4 ใบในสำรับเดียว)
        foreach ($suit as $s => $n) {
            if ($n >= 4) {
                $lines[] = '• '.($cfg['suit_dominant'][$s] ?? '')." ({$s} {$n} ใบ)";
            }
        }

        // 3) สำรับขาด (0 ใบ — ชี้เฉพาะเมื่อ Major ไม่ท่วม ไม่งั้นกำกวม)
        if ($major <= 5) {
            foreach ($suit as $s => $n) {
                if ($n === 0) {
                    $lines[] = '• '.($cfg['suit_absent'][$s] ?? '');
                }
            }
        }

        // 4) กลับหัว (≤2 ลื่น / 5-6 ติดขัด / 7+ ปิดกั้น)
        if ($reversed >= 7) {
            $lines[] = '• '.($cfg['reversed']['dominant'] ?? '')." (กลับหัว {$reversed}/10)";
        } elseif ($reversed >= 5) {
            $lines[] = '• '.($cfg['reversed']['heavy'] ?? '')." (กลับหัว {$reversed}/10)";
        } elseif ($reversed <= 2) {
            $lines[] = '• '.($cfg['reversed']['few'] ?? '')." (กลับหัว {$reversed}/10)";
        }

        // 5) ราชสำนักเยอะ (≥4)
        if ($court >= 4) {
            $lines[] = '• '.($cfg['court']['heavy'] ?? '')." (ราชสำนัก {$court} ใบ)";
        }

        // 6) Ace หลายใบ (≥2)
        if ($aces >= 2) {
            $lines[] = '• '.($cfg['aces']['multiple'] ?? '')." (Ace {$aces} ใบ)";
        }

        // 7) เลขซ้ำ (Pip เลขเดียวกัน ≥3 ใบ)
        foreach ($numberCount as $num => $n) {
            if ($n >= 3 && isset($cfg['repeated_number'][$num])) {
                $lines[] = '• '.$cfg['repeated_number'][$num]." (เลข {$num} ซ้ำ {$n} ใบ)";
            }
        }

        return implode("\n", $lines);
    }

    // ════════════════════════════════════════════════════════════════
    // 🔥💧 ธาตุเสริม-ขัด (Elemental Dignities — Golden Dawn)
    // ════════════════════════════════════════════════════════════════

    /** Suit → element (Minor Arcana) */
    protected const SUIT_ELEMENT = [
        'Wands' => 'fire',
        'Cups' => 'water',
        'Swords' => 'air',
        'Pentacles' => 'earth',
    ];

    /**
     * สร้างบล็อก "ธาตุเสริม-ขัด" จากการคำนวณคู่ตำแหน่งสำคัญ + สรุปสำรับ
     *
     * @param  array<int, array>  $cards
     * @return string ว่าง = สำรับไม่ครบ 10
     */
    public function elementalDignityLines(array $cards): string
    {
        $cfg = (array) config('fortune_elemental_dignities', []);
        $matrix = (array) ($cfg['matrix'] ?? []);
        $elementLabel = (array) ($cfg['element_label'] ?? []);
        $pairText = (array) ($cfg['pair_interpretation'] ?? []);
        $pairs = (array) ($cfg['celtic_pairs'] ?? []);

        if (empty($matrix) || empty($pairs)) {
            return '';
        }

        // คำนวณธาตุของไพ่แต่ละตำแหน่ง
        $byPos = [];
        for ($pos = 1; $pos <= 10; $pos++) {
            $card = $cards[$pos] ?? null;
            if (! $card) {
                continue;
            }
            $nameEn = (string) ($card['card_name_en'] ?? '');
            if ($nameEn === '') {
                continue;
            }
            $byPos[$pos] = [
                'en' => $nameEn,
                'th' => ((string) ($card['card_name_th'] ?? '')) ?: $nameEn,
                'rev' => ! empty($card['is_reversed']),
                'el' => $this->elementOf($nameEn),
            ];
        }
        if (count($byPos) < 10) {
            return '';
        }

        // 1) คู่ตำแหน่งสำคัญ (Celtic dynamics)
        $pairLines = [];
        foreach ($pairs as $pair) {
            [$a, $b, $name] = [$pair[0] ?? null, $pair[1] ?? null, $pair[2] ?? ''];
            if (! isset($byPos[$a], $byPos[$b])) {
                continue;
            }
            $ea = $byPos[$a]['el'];
            $eb = $byPos[$b]['el'];
            if ($ea === null || $eb === null) {
                continue;
            }
            $tone = $matrix[$ea][$eb] ?? null;
            if ($tone === null) {
                continue;
            }
            $icon = ['same' => '🔁', 'friendly' => '✨', 'contrary' => '⚡', 'neutral' => '➖'][$tone] ?? '';
            $pairLines[] = "{$icon} {$name}: "
                ."{$byPos[$a]['th']} ({$elementLabel[$ea]}) × {$byPos[$b]['th']} ({$elementLabel[$eb]}) "
                .'→ '.($pairText[$tone] ?? $tone);
        }

        // 2) สรุประดับสำรับ — นับ tone ทุกคู่ของ 10 ใบ
        $toneCount = ['same' => 0, 'friendly' => 0, 'contrary' => 0, 'neutral' => 0];
        $positions = array_keys($byPos);
        $n = count($positions);
        for ($i = 0; $i < $n; $i++) {
            for ($j = $i + 1; $j < $n; $j++) {
                $ea = $byPos[$positions[$i]]['el'];
                $eb = $byPos[$positions[$j]]['el'];
                if ($ea === null || $eb === null) {
                    continue;
                }
                $t = $matrix[$ea][$eb] ?? null;
                if ($t && isset($toneCount[$t])) {
                    $toneCount[$t]++;
                }
            }
        }
        $totalPairs = array_sum($toneCount);
        $friendlyPct = $totalPairs > 0 ? (($toneCount['friendly'] + $toneCount['same']) / $totalPairs) : 0;
        $contraryPct = $totalPairs > 0 ? ($toneCount['contrary'] / $totalPairs) : 0;

        $summaryKey = 'balanced';
        if ($friendlyPct >= 0.55) {
            $summaryKey = 'highly_friendly';
        } elseif ($contraryPct >= 0.40) {
            $summaryKey = 'highly_contrary';
        }
        $summary = (string) ($cfg['spread_summary'][$summaryKey] ?? '');

        $out = "📊 ภาพรวมธาตุของสำรับ: {$summary}\n"
            ."   (เสริม {$toneCount['friendly']} · เหมือนกัน {$toneCount['same']} · ขัด {$toneCount['contrary']} · กลาง {$toneCount['neutral']} จาก {$totalPairs} คู่)\n";

        if (! empty($pairLines)) {
            $out .= "\n🔍 คู่ตำแหน่งสำคัญ:\n".implode("\n", $pairLines);
        }

        return $out;
    }

    /**
     * ธาตุของไพ่ — Minor: ตาม suit / Major: ตาม Golden Dawn assignment
     */
    protected function elementOf(string $nameEn): ?string
    {
        // Major Arcana
        $majorMap = (array) config('fortune_elemental_dignities.major_elements', []);
        if (isset($majorMap[$nameEn])) {
            return (string) $majorMap[$nameEn];
        }

        // Minor Arcana — Suit → element
        $c = $this->classifyCard($nameEn);
        if ($c['suit'] !== null && isset(self::SUIT_ELEMENT[$c['suit']])) {
            return self::SUIT_ELEMENT[$c['suit']];
        }

        return null;
    }

    // ════════════════════════════════════════════════════════════════
    // 📍 ความสัมพันธ์ตำแหน่ง Celtic (Position Dynamics — diagnostic pairs)
    // ════════════════════════════════════════════════════════════════

    /**
     * สร้างบล็อก "ความสัมพันธ์คู่ตำแหน่ง" ตามคู่ที่ตำราหมอดูใช้วิเคราะห์
     *
     * @param  array<int, array>  $cards
     */
    public function positionDynamicLines(array $cards): string
    {
        $cfg = (array) config('fortune_position_dynamics', []);
        $dynamics = (array) ($cfg['dynamics'] ?? []);
        $posLabel = (array) ($cfg['position_label'] ?? []);

        if (empty($dynamics)) {
            return '';
        }

        // เก็บไพ่รายตำแหน่ง
        $byPos = [];
        for ($pos = 1; $pos <= 10; $pos++) {
            $card = $cards[$pos] ?? null;
            if (! $card) {
                continue;
            }
            $byPos[$pos] = [
                'th' => ((string) ($card['card_name_th'] ?? '')) ?: ((string) ($card['card_name_en'] ?? '')),
                'rev' => ! empty($card['is_reversed']),
            ];
        }
        if (count($byPos) < 10) {
            return '';
        }

        $lines = [];
        foreach ($dynamics as $dyn) {
            $a = (int) ($dyn['a'] ?? 0);
            $b = (int) ($dyn['b'] ?? 0);
            if (! isset($byPos[$a], $byPos[$b])) {
                continue;
            }
            $oa = $byPos[$a]['rev'] ? '(กลับหัว)' : '';
            $ob = $byPos[$b]['rev'] ? '(กลับหัว)' : '';
            $label = (string) ($dyn['label'] ?? '');
            $question = (string) ($dyn['question'] ?? '');
            $tip = (string) ($dyn['tip'] ?? '');

            $lines[] = "▸ {$label}\n"
                ."   ต.{$a} ({$posLabel[$a]}): {$byPos[$a]['th']}{$oa}\n"
                ."   ต.{$b} ({$posLabel[$b]}): {$byPos[$b]['th']}{$ob}\n"
                ."   ❓ ถามตัวเอง: {$question}\n"
                ."   💡 {$tip}";
        }

        return implode("\n\n", $lines);
    }

    // ════════════════════════════════════════════════════════════════
    // 🎯 น้ำหนัก Yes/No (Weighted Verdict)
    // ════════════════════════════════════════════════════════════════

    /**
     * คำนวณคะแนน Yes/No พร้อมรายละเอียดต่อใบ + ตัดสินผลลัพธ์
     *
     * @param  array<int, array>  $cards
     * @return string ว่าง = สำรับไม่ครบ
     */
    public function yesNoVerdict(array $cards): string
    {
        $cfg = (array) config('fortune_yes_no_weights', []);
        $weights = (array) ($cfg['card_weights'] ?? []);
        $multipliers = (array) ($cfg['position_multiplier'] ?? []);
        $verdicts = (array) ($cfg['verdicts'] ?? []);

        if (empty($weights)) {
            return '';
        }

        $total = 0.0;
        $contributions = [];
        $present = 0;

        for ($pos = 1; $pos <= 10; $pos++) {
            $card = $cards[$pos] ?? null;
            if (! $card) {
                continue;
            }
            $nameEn = (string) ($card['card_name_en'] ?? '');
            if ($nameEn === '' || ! array_key_exists($nameEn, $weights)) {
                continue;
            }
            $present++;
            $rawScore = (int) $weights[$nameEn];
            // กลับหัว = พลิกสัญลักษณ์ (Tower กลับหัวกลายเป็นน้อยร้าย, Sun กลับหัวอ่อนลง ฯลฯ)
            if (! empty($card['is_reversed'])) {
                $rawScore = -$rawScore;
            }
            $mult = (float) ($multipliers[$pos] ?? 1.0);
            $weighted = $rawScore * $mult;
            $total += $weighted;

            $th = ((string) ($card['card_name_th'] ?? '')) ?: $nameEn;
            $rev = ! empty($card['is_reversed']) ? '(กลับหัว)' : '';
            $sign = $rawScore > 0 ? '+' : '';
            $contributions[] = "   ต.{$pos} {$th}{$rev}: {$sign}{$rawScore} × {$mult} = "
                .number_format($weighted, 1);
        }

        if ($present < 10) {
            return '';
        }

        // ตัดสินผลลัพธ์
        $verdictKey = 'strong_no';
        foreach (['strong_yes', 'lean_yes', 'unclear', 'lean_no'] as $k) {
            $threshold = (float) ($verdicts[$k]['threshold'] ?? 0);
            if ($total >= $threshold) {
                $verdictKey = $k;
                break;
            }
        }
        $verdict = (array) ($verdicts[$verdictKey] ?? []);

        return '📊 คะแนนรวม: '.number_format($total, 1)." (จาก 10 ใบ)\n"
            .($verdict['icon'] ?? '').' ผลฟันธง: '.($verdict['text'] ?? '')."\n\n"
            ."🔍 รายละเอียดต่อใบ (คะแนน × ตัวคูณตำแหน่ง):\n"
            .implode("\n", $contributions);
    }

    /**
     * จำแนกไพ่จาก name_en → arcana/suit/rank/isCourt/isAce/number
     *
     * @return array{arcana:string, suit:?string, rank:?string, isCourt:bool, isAce:bool, number:?int}
     */
    protected function classifyCard(string $nameEn): array
    {
        $base = [
            'arcana' => 'minor', 'suit' => null, 'rank' => null,
            'isCourt' => false, 'isAce' => false, 'number' => null,
        ];

        if (in_array($nameEn, self::MAJOR_ARCANA, true)) {
            $base['arcana'] = 'major';

            return $base;
        }

        // รูปแบบ "{Rank} of {Suit}"
        if (! preg_match('/^(.+?)\s+of\s+(Wands|Cups|Swords|Pentacles)$/', $nameEn, $m)) {
            return $base;
        }

        $rank = $m[1];
        $base['suit'] = $m[2];
        $base['rank'] = $rank;
        $base['isCourt'] = in_array($rank, ['Page', 'Knight', 'Queen', 'King'], true);
        $base['isAce'] = ($rank === 'Ace');
        $base['number'] = self::RANK_NUMBER[$rank] ?? null;

        return $base;
    }

    /**
     * ล้าง cache (เรียกตอนแอดมินแก้คลังความรู้)
     */
    public function clearCache(): void
    {
        Cache::forget('fortune_knowledge:health_map');
        Cache::forget('fortune_knowledge:persona_map');
        Cache::forget('fortune_knowledge:person_role_map');
        Cache::forget('fortune_knowledge:combos');
        foreach (FortuneKnowledge::CATEGORIES as $cat) {
            Cache::forget("fortune_knowledge:mucards:{$cat}");
        }
    }
}
