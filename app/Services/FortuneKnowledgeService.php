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

    // ════════════════════════════════════════════════════════════════
    // STORY — สัญญาณ "ตัวละคร + เหตุการณ์ข้างหน้า" แบบย่อ (โหมดซีรี่ส์)
    // ════════════════════════════════════════════════════════════════

    /** ตำแหน่งที่เป็น "ตัวละคร" ของเรื่อง → ป้ายบทบาท */
    public const STORY_CAST_POSITIONS = [
        7 => 'ตัวเจ้าชะตา',
        8 => 'คนรอบตัว/อิทธิพลภายนอก',
        2 => 'ผู้ขวาง/คู่ปรับ/ผู้ก่อเรื่อง',
    ];

    /** ตำแหน่งที่เป็น "เหตุการณ์ข้างหน้า" → ป้าย */
    public const STORY_EVENT_POSITIONS = [
        6 => 'อนาคตอันใกล้',
        10 => 'ผลลัพธ์/จุดจบ',
        2 => 'สิ่งที่จะมาขวาง',
    ];

    /** ความยาวสูงสุดต่อ 1 ชิ้นข้อมูล — กันบล็อกบวม (คลังเต็มมีถึง 300 ตัว/ใบ/หมวด) */
    protected const STORY_BIT_MAX = 95;

    /**
     * 🎬 (2026-09-04 owner) สัญญาณสำหรับ "เล่าเป็นซีรี่ส์" — ตัวละคร (รูปพรรณ/วัย) + เหตุการณ์ข้างหน้า
     *
     * owner: "เราเคยทำว่าให้สร้างเป็นเหมือนซีรี่ เหตุการณ์ มีตัวละคร ... มีเกณฑ์เดินทางแล้วมีปัญหา
     *   อุบัติเหตุ หรือมีคนสร้างเรื่องให้เรา หรือมีคนนำโชคมาให้ รูปร่างหน้าตาอายุ (มีในคลังความรู้)
     *   โรคต่างๆ มีในคลังหมด แต่เหมือนไม่ถูกหยิบมาใช้เลย"
     *
     * ⚠️ ทำไมคลังไม่เคยถูกใช้ ทั้งที่มีครบ:
     *   คลัง persona/person_role/health/travel/legal/wealth *ทุกตัว* ยิงตาม "คีย์เวิร์ดในคำถาม"
     *   (buildPhysiognomyDirective → ต้องมีคำว่า หน้าตา/ลักษณะ · buildHealthDirective → ต้องมีคำว่า ป่วย/โรค …)
     *   ⇒ เหตุการณ์ที่ลูกค้า *ไม่รู้จะถาม* (เดินทางแล้วมีเรื่อง · คนสร้างเรื่อง · คนนำโชค · โรคที่กำลังมา)
     *     ไม่มีวันโผล่ — ทั้งที่นี่คือของที่ต้อง "รีบบอก" ที่สุด
     *   ตัวนี้ยิงตาม *หน้าไพ่* (card-gated) ไม่รอคำถาม — แต่ย่อให้เหลือเฉพาะตำแหน่งที่เป็น
     *   ตัวละคร (7/8/2) กับเหตุการณ์ข้างหน้า (6/10/2) และตัดแต่ละชิ้นเหลือ ≤95 ตัว
     *   (เท 10 ใบ × 5 คลังเต็ม = 10k+ ตัวอักษร — บวมเกินจะฉีดทุกคำถาม)
     *
     * @param  array<int, array>  $cards  ผลจาก FortuneReading::getCelticCards()
     * @return string ว่าง = ไม่มีสัญญาณ (ผู้เรียกต้องไม่ฉีดบทซีรี่ส์ — ห้ามปั้นเรื่องจากอากาศ)
     */
    public function storySignalLines(array $cards): string
    {
        if (count($cards) < 10) {
            return '';
        }

        $persona = $this->personaMap();
        $role = $this->personRoleMap();
        $age = $this->muCardMap(FortuneKnowledge::CATEGORY_AGE_RANGE);
        $health = $this->healthMap();
        $travel = $this->muCardMap(FortuneKnowledge::CATEGORY_TRAVEL_ABROAD);
        $legal = $this->muCardMap(FortuneKnowledge::CATEGORY_LEGAL_DISPUTES);
        $wealth = $this->muCardMap(FortuneKnowledge::CATEGORY_WEALTH_LUCK);
        $timing = $this->muCardMap(FortuneKnowledge::CATEGORY_TIMING);

        $castLines = [];
        $castPositions = self::STORY_CAST_POSITIONS;

        // 👤 ไพ่ราชสำนัก (Page/Knight/Queen/King) ที่ตกตำแหน่งอนาคต = "คนที่จะเข้ามา" — ต้องอยู่ในตัวละครด้วย
        foreach ([6 => 'คนที่จะเข้ามา (อนาคตอันใกล้)', 10 => 'คนที่อยู่ปลายทาง (ผลลัพธ์)'] as $pos => $label) {
            $nameEn = (string) ($cards[$pos]['card_name_en'] ?? '');
            if (preg_match('/^(Page|Knight|Queen|King) of /', $nameEn)) {
                $castPositions[$pos] = $label;
            }
        }

        foreach ($castPositions as $pos => $roleLabel) {
            $card = $cards[$pos] ?? null;
            if (! $card) {
                continue;
            }
            [$nameEn, $nameTh, $rev] = $this->storyCardIdentity($card);

            $bits = [];
            $look = $this->storyLine($persona[$nameEn]['content'] ?? '', 'รูปลักษณ์');
            if ($look !== '') {
                $bits[] = 'รูปพรรณ: '.$look;
            }
            $trait = $rev
                ? $this->storyLine($persona[$nameEn]['content'] ?? '', 'กลับหัว')
                : $this->storyLine($persona[$nameEn]['content'] ?? '', 'นิสัย');
            if ($trait !== '') {
                $bits[] = 'นิสัย/ท่าที: '.$trait;
            }
            $who = $this->storyLine($role[$nameEn]['content'] ?? '', 'ตำแหน่งบุคคล');
            if ($who !== '') {
                $bits[] = 'มักเป็น: '.$who;
            }
            $ageTxt = $this->storyClip($this->orientedContent((string) ($age[$nameEn]['content'] ?? ''), $rev));
            if ($ageTxt !== '') {
                $bits[] = 'วัย: '.$ageTxt;
            }
            if (empty($bits)) {
                continue;
            }
            $castLines[] = "• [{$roleLabel}] {$nameTh} ".($rev ? 'กลับหัว' : 'ตั้งตรง').' — '.implode(' · ', $bits);
        }

        $eventLines = [];
        foreach (self::STORY_EVENT_POSITIONS as $pos => $label) {
            $card = $cards[$pos] ?? null;
            if (! $card) {
                continue;
            }
            [$nameEn, $nameTh, $rev] = $this->storyCardIdentity($card);

            $bits = [];
            $h = (string) ($health[$nameEn]['content'] ?? '');
            if ($h !== '') {
                $body = $this->storyLine($h, 'อวัยวะ');
                $tend = $rev ? $this->storyLine($h, 'กลับหัว') : $this->storyLine($h, 'ตั้งตรง');
                if ($body === '' && $tend === '') {
                    $tend = $this->storyClip($this->orientedContent($h, $rev));
                }
                $joined = trim($body.($body !== '' && $tend !== '' ? ' — ' : '').$tend);
                if ($joined !== '') {
                    $bits[] = '🩺 '.$joined;
                }
            }
            foreach ([
                ['✈️ เดินทาง', $travel],
                ['⚖️ ข้อพิพาท/สัญญา', $legal],
                ['💰 ลาภ/เงิน', $wealth],
            ] as [$icon, $map]) {
                $txt = $this->storyClip($this->orientedContent((string) ($map[$nameEn]['content'] ?? ''), $rev));
                if ($txt !== '') {
                    $bits[] = $icon.': '.$txt;
                }
            }
            if ($pos !== 2) {
                $t = $this->storyClip($this->orientedContent((string) ($timing[$nameEn]['content'] ?? ''), $rev));
                if ($t !== '') {
                    $bits[] = '⏳ จังหวะ: '.$t;
                }
            }
            if (empty($bits)) {
                continue;
            }
            $eventLines[] = "• [{$label}] {$nameTh} ".($rev ? 'กลับหัว' : 'ตั้งตรง').' — '.implode(' · ', $bits);
        }

        // 🪬 สัญญาณของ/คุณไสย์ — ใช้ตัวเดิมที่กรอง "เฉพาะใบที่มีของจริง" อยู่แล้ว (orientation-aware)
        $bm = trim((string) $this->blackMagicSignalLinesForCards($cards));

        if (empty($castLines) && empty($eventLines) && $bm === '') {
            return '';
        }

        $out = '';
        if (! empty($castLines)) {
            $out .= "🎭 ตัวละครในสำรับ (รูปพรรณ/วัย/ท่าที — ใช้เฉพาะที่ไพ่ชี้):\n".implode("\n", $castLines)."\n";
        }
        if (! empty($eventLines)) {
            $out .= ($out !== '' ? "\n" : '')
                ."⚡ เหตุการณ์ข้างหน้าที่ไพ่ชี้ (ต.6 อนาคตอันใกล้ · ต.10 ผลลัพธ์ · ต.2 สิ่งที่จะมาขวาง):\n"
                .implode("\n", $eventLines)."\n";
        }
        if ($bm !== '') {
            $out .= ($out !== '' ? "\n" : '')."🪬 สัญญาณของ/คุณไสย์ (เฉพาะใบที่ชี้จริง — ทักเท่าที่ไพ่บอก ห้ามขยาย):\n".$bm."\n";
        }

        return $out;
    }

    /**
     * ดึง name_en / name_th / กลับหัว ของไพ่ 1 ใบ (ใช้ซ้ำใน storySignalLines)
     *
     * @return array{0:string,1:string,2:bool}
     */
    protected function storyCardIdentity(array $card): array
    {
        $nameEn = (string) ($card['card_name_en'] ?? '');
        $nameTh = (string) ($card['card_name_th'] ?? '') ?: ($nameEn ?: '?');

        return [$nameEn, $nameTh, ! empty($card['is_reversed'])];
    }

    /**
     * หยิบ "บรรทัดที่ขึ้นต้นด้วยป้าย" ออกจากเนื้อคลัง แล้วตัดป้ายทิ้ง — เช่น "รูปลักษณ์/โหงวเฮ้ง: X" → "X"
     *
     * คลังเขียนเป็นหลายบรรทัด (รูปลักษณ์ / นิสัย / กลับหัว) — โหมดซีรี่ส์เอาแค่บรรทัดที่ต้องการ
     * ไม่เจอป้าย = คืนว่าง (ผู้เรียกตัดสินใจเองว่าจะ fallback ไหม)
     */
    protected function storyLine(string $content, string $labelPrefix): string
    {
        foreach (preg_split('/\R/u', trim($content)) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || mb_strpos($line, $labelPrefix) !== 0) {
                continue;
            }
            $value = preg_replace('/^[^:：]*[:：]\s*/u', '', $line);

            return $this->storyClip((string) $value);
        }

        return '';
    }

    /** ตัดชิ้นข้อมูลให้สั้น — บล็อกซีรี่ส์ต้องไม่บวม */
    protected function storyClip(string $text): string
    {
        $text = trim((string) preg_replace('/\s+/u', ' ', $text));
        if ($text === '') {
            return '';
        }

        return mb_strlen($text) > self::STORY_BIT_MAX
            ? rtrim(mb_substr($text, 0, self::STORY_BIT_MAX)).'…'
            : $text;
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
        foreach ($this->positionsOf($cards) as $pos) {
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
     * 🔮 (2026-09-21) ตำแหน่งที่มีไพ่อยู่จริง เรียงน้อยไปมาก (เฉพาะเลข ≥ 1)
     *
     * เดิมลูปรายไพ่เขียนตายตัว `for ($pos = 1; $pos <= 10; ...)` ตามสำรับ Celtic ของบอท
     * → ไพ่ 12 เดือนของเว็บ เดือนที่ 11-12 ไม่เคยได้ตำราสุขภาพ/มู/คู่ไพ่เลย
     * สำรับ 10 ใบของบอท (เลข 1..10) ได้ลำดับเดิมทุกอย่าง
     *
     * @param  array<int|string, mixed>  $cards
     * @return array<int, int>
     */
    protected function positionsOf(array $cards): array
    {
        $positions = array_values(array_filter(array_keys($cards), fn ($k) => is_int($k) && $k >= 1));
        sort($positions);

        return $positions;
    }

    /**
     * 🩺 (2026-09-21) คำถามนี้เกี่ยวกับสุขภาพไหม — ตำราสุขภาพรายไพ่ใส่เฉพาะเมื่อ "ถูกถาม"
     *
     * ต้นเรื่อง: เลนไพ่เว็บเคยแนบตำราสุขภาพทุกคำทำนาย — ถามเรื่องความรัก ได้หอคอยตั้งตรง
     * แล้วแม่หมอเห็นบรรทัด "สโตรก — ต้องไปโรงพยาบาล" ติดไปด้วย · บอท Celtic 99 ใส่เฉพาะคำถามสุขภาพ
     * มาตลอด (คำชี้ชุดเดียวกับที่เขียนไว้ในเมธอด CelticCrossService::buildHealthDirective)
     */
    public const HEALTH_QUESTION_KEYWORDS = [
        'สุขภาพ', 'ป่วย', 'ไม่สบาย', 'เจ็บป่วย', 'โรค', 'อาการ', 'รักษา', 'หมอ', 'แพทย์',
        'โรงพยาบาล', 'ผ่าตัด', 'ตรวจสุขภาพ', 'ตรวจร่างกาย', 'มะเร็ง', 'เนื้องอก', 'เบาหวาน',
        'ความดัน', 'หัวใจ', 'ตับ', 'ไต', 'ปอด', 'กระเพาะ', 'ลำไส้', 'ไทรอยด์', 'ไมเกรน',
        'ปวดหัว', 'ปวดท้อง', 'ปวดหลัง', 'ปวดข้อ', 'นอนไม่หลับ', 'เครียด', 'ซึมเศร้า',
        'วิตกกังวล', 'แพนิค', 'ภูมิแพ้', 'ภูมิคุ้มกัน', 'ติดเชื้อ', 'อักเสบ', 'เป็นไข้',
        'เลือดจาง', 'โลหิตจาง', 'กระดูก', 'อ่อนเพลีย', 'ฮอร์โมน', 'ประจำเดือน', 'ตั้งครรภ์',
        'มีลูกยาก', 'มีบุตรยาก', 'ซีสต์', 'แผล', 'บาดเจ็บ', 'อัมพาต', 'สโตรก', 'สุขภาพจิต',
        'จิตเวช', 'กินยา', 'เสพติด', 'หายป่วย', 'พักฟื้น',
    ];

    public static function looksLikeHealthQuestion(string $question): bool
    {
        $q = mb_strtolower(trim($question));
        if ($q === '') {
            return false;
        }
        foreach (self::HEALTH_QUESTION_KEYWORDS as $kw) {
            if (mb_strpos($q, $kw) !== false) {
                return true;
            }
        }

        return false;
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
        foreach ($this->positionsOf($cards) as $pos) {
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
        foreach ($this->positionsOf($cards) as $pos) {
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
     * @param  array<int, array>  $cards  key = ตำแหน่ง canonical 1..10 (ลำดับ Waite ของบอท)
     * @param  array<int, int>  $displayPos  🔮 (2026-09-21) ตำแหน่ง canonical => เลขที่จะพิมพ์ — ใช้ตอนไพ่มาจาก
     *                                        ผังอื่น (Celtic ของเว็บ, JuntraSpreadProfiles::celticToCanonical) ให้บรรทัดที่พิมพ์
     *                                        ตรงกับ "ใบที่ N" ที่ลูกค้าเห็น · ว่าง = พิมพ์เลข canonical (บอทเหมือนเดิม)
     */
    public function positionDynamicLines(array $cards, array $displayPos = []): string
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

            $na = $displayPos[$a] ?? $a;
            $nb = $displayPos[$b] ?? $b;
            $lines[] = "▸ {$label}\n"
                ."   ต.{$na} ({$posLabel[$a]}): {$byPos[$a]['th']}{$oa}\n"
                ."   ต.{$nb} ({$posLabel[$b]}): {$byPos[$b]['th']}{$ob}\n"
                ."   ❓ ถามตัวเอง: {$question}\n"
                ."   💡 {$tip}";
        }

        return implode("\n\n", $lines);
    }

    // ════════════════════════════════════════════════════════════════
    // 🎯 น้ำหนัก Yes/No (Weighted Verdict)
    // ════════════════════════════════════════════════════════════════

    /**
     * คำนวณคะแนน Yes/No พร้อมรายละเอียดต่อใบ + ตัดสินผลลัพธ์ (บอท Celtic 99 — ตำแหน่ง 1..10 ลำดับ Waite)
     *
     * @param  array<int, array>  $cards
     * @return string ว่าง = ไม่มีไพ่ตั้งตรงที่รู้น้ำหนักเลย (เช่น กลับหัวทุกใบ)
     */
    public function yesNoVerdict(array $cards): string
    {
        // 🔮 (2026-09-21) บอท Celtic 99 ใช้ตาชั่งตัวเดียวกับไพ่เว็บ (yesNoVerdictFor) — ตัวคูณตำแหน่งลำดับ Waite ของบอทเอง
        //   เดิมที่นี่พลิกเครื่องหมายไพ่กลับหัวทุกใบ → หอคอย/ดาบสิบกลับหัวที่ ต.10 (×2.5) ได้ +7.5 ดันผลเป็น "ใช่ชัด"
        //   ตำรา yes/no ให้ไพ่กลับหัวไม่ตรงกัน (ไม่ใช่ / ล่าช้า / ก้ำกึ่ง) จึงไม่นับคะแนน ให้แม่หมออ่านความหมายกลับหัวเอง
        //   และเดิมต้องมีน้ำหนักครบ 10 ใบถึงจะคิด — ตอนนี้มีไพ่ตั้งตรงที่รู้น้ำหนักสักใบก็คิดได้ (เกณฑ์ย่อตามตัวคูณของใบตั้งตรง)
        //   สำรับตั้งตรงครบ 10 ใบ: ตัวคูณรวมเท่าเดิม เกณฑ์ ±5/±2 เท่าเดิม ผลฟันธงเหมือนเดิมทุกกรณี
        $multipliers = array_map('floatval', (array) config('fortune_yes_no_weights.position_multiplier', []));
        $celtic = array_filter($cards, fn ($pos) => is_int($pos) && $pos >= 1 && $pos <= 10, ARRAY_FILTER_USE_KEY);

        return $this->yesNoVerdictFor($celtic, $multipliers);
    }

    /**
     * 🔮 (2026-09-15) ตาชั่ง Yes/No สำหรับสำรับกี่ใบก็ได้ (ไพ่เว็บจันทรา 1/3/5/10 ใบ)
     *
     * เกณฑ์ ±5/±2 ใน config ตั้งจากผลรวมตัวคูณของ Celtic 10 ตำแหน่ง
     * ที่นี่ **ย่อเกณฑ์ตามสัดส่วนผลรวมตัวคูณ** ของไพ่ที่นับคะแนนจริง (บอท Celtic 99 ก็เรียกผ่าน yesNoVerdict())
     * — ไม่ย่อ = ไพ่ 3 ใบแทบไม่มีทางถึง "ใช่ชัด" เลย ตาชั่งจะเอียงไปทาง "ก้ำกึ่ง" ตลอด
     *
     * 🔮 (2026-09-21) แก้สองจุดที่ทำให้ตาชั่งฟันธงผิดตำรา:
     *   1. เดิมพลิกเครื่องหมายไพ่กลับหัวทุกใบ → หอคอย/ดาบสิบ/ดาบสาม/ห้าเหรียญกลับหัวได้ +3 "ใช่ชัด"
     *      ตำรา yes/no แต่ละเจ้าให้ไพ่กลับหัวไม่ตรงกัน (ไม่ใช่ / ล่าช้า / ก้ำกึ่ง) — ตามกฎ "แหล่งขัดกัน = ไม่เดา"
     *      ไพ่กลับหัวจึง **ไม่นับคะแนน** ให้แม่หมออ่านจากความหมายกลับหัวของใบนั้นเอง (card_weights ด้านตั้งตรง
     *      ตรงกับตาราง yes/no ทั่วไปอยู่แล้ว จึงยังใช้ต่อ)
     *   2. เกณฑ์ที่ย่อตามสัดส่วนทำให้ไพ่ใบเดียวที่ +1 ("ใช่แบบเบา" เช่น The Fool, Temperance) กลายเป็น "ใช่ชัด"
     *      → เพิ่มด่านขั้นต่ำเป็นหน่วยคะแนนจริง: ชัด ต้อง |รวม| ≥ 2 · เอียง ต้อง |รวม| ≥ 1
     *      ไพ่ใบเดียวจึงตรงกับชั้นของไพ่เอง (±2/±3 ชัด · ±1 เอียง · 0 ก้ำกึ่ง) ส่วน 10 ใบ (ย่อ ×1) เกณฑ์เดิมทุกอย่าง
     *
     * @param  array<int, array>  $cards  key = ตำแหน่ง 1..N (card_name_en / card_name_th / is_reversed)
     * @param  array<int, float>  $multipliers  ตำแหน่ง => ตัวคูณ (ไม่ระบุ = 1.0)
     * @return string ว่าง = ใช้ไม่ได้ (ไม่มีไพ่ตั้งตรงที่รู้จักน้ำหนักเลย)
     */
    public function yesNoVerdictFor(array $cards, array $multipliers): string
    {
        $cfg = (array) config('fortune_yes_no_weights', []);
        $weights = (array) ($cfg['card_weights'] ?? []);
        $verdicts = (array) ($cfg['verdicts'] ?? []);
        $celticSum = array_sum(array_map('floatval', (array) ($cfg['position_multiplier'] ?? []))) ?: 10.0;

        if (empty($weights)) {
            return '';
        }

        $total = 0.0;
        $multSum = 0.0;
        $lines = [];
        $reversedLines = [];
        ksort($cards);
        foreach ($cards as $pos => $card) {
            $nameEn = (string) ($card['card_name_en'] ?? '');
            if ($nameEn === '' || ! array_key_exists($nameEn, $weights)) {
                continue;
            }
            $th = ((string) ($card['card_name_th'] ?? '')) ?: $nameEn;
            if (! empty($card['is_reversed'])) {
                $reversedLines[] = "   ต.{$pos} {$th}(กลับหัว): ไม่นับคะแนน — อ่านตามความหมายกลับหัวของไพ่ใบนี้ (มักเป็นติดขัด/ยังไม่ใช่ตอนนี้)";

                continue;
            }
            $raw = (int) $weights[$nameEn];
            $mult = (float) ($multipliers[$pos] ?? 1.0);
            $total += $raw * $mult;
            $multSum += $mult;

            $lines[] = "   ต.{$pos} {$th}: ".($raw > 0 ? '+' : '')."{$raw} × {$mult} = ".number_format($raw * $mult, 1);
        }

        if ($multSum <= 0) {
            return '';
        }

        // เกณฑ์ "รวม ≥ threshold → ผลนี้" — ด่านขั้นต่ำ: ใช่ชัด ≥ 2 · ใช่ ≥ 1 · ก้ำกึ่ง > −1 · ยังไม่ใช่ > −2
        $scale = $multSum / $celticSum;
        $floors = ['strong_yes' => 2.0, 'lean_yes' => 1.0, 'unclear' => -0.999, 'lean_no' => -1.999];
        $verdictKey = 'strong_no';
        foreach (['strong_yes', 'lean_yes', 'unclear', 'lean_no'] as $k) {
            $t = (float) ($verdicts[$k]['threshold'] ?? 0) * $scale;
            $t = $floors[$k] > 0 ? max($t, $floors[$k]) : min($t, $floors[$k]);
            if ($total >= $t) {
                $verdictKey = $k;
                break;
            }
        }
        $verdict = (array) ($verdicts[$verdictKey] ?? []);

        return '📊 คะแนนรวม: '.number_format($total, 1).' (จาก '.count($lines)." ใบตั้งตรง)\n"
            .($verdict['icon'] ?? '').' ผลฟันธง: '.($verdict['text'] ?? '')."\n\n"
            ."🔍 รายละเอียดต่อใบ (คะแนน × ตัวคูณตำแหน่ง):\n"
            .implode("\n", array_merge($lines, $reversedLines));
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
