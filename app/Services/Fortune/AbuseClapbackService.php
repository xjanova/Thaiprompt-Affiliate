<?php

namespace App\Services\Fortune;

use App\Models\FortuneCustomerPersona;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\FortuneAIService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 🛡️ AbuseClapbackService (2026-05-27)
 *
 * แม่หมอ "สอนหนักๆ" ลูกค้าที่ด่า/ป่วน/หน้าหม้อ — Grok-3 + savage psychology + กฎหมายอ้างอิง
 *
 * ภารกิจ:
 *   1. ตรวจจับ abuse + persona category (ขี้ยา/ขี้เหล้า/เด็กห้าว/ปราสาทเสีย/หน้าหม้อ)
 *   2. นับ escalation level L1→L2→L3→L4 ต่อ user (Cache 24hr)
 *   3. สร้าง savage system prompt (Grok-3 tone) + อ้าง พ.ร.บ.คอม
 *   4. L4 → แจ้ง admin (NO auto-ban — admin ตัดสินเอง)
 *
 * ⚠️ Safety:
 *   - default disabled — admin opt-in ผ่าน settings.enable_abuse_clapback
 *   - ลูกค้าวิกฤต (mental_fragile / over_emotional) → skip savage (ใช้ปลอบใจปกติ)
 *   - ปราสาทเสีย → escalate hotline 1323 (ไม่กวน)
 *   - เด็ก/ปราสาทเสีย → ห้าม L3/L4
 *
 * Legal note:
 *   - อ้าง พ.ร.บ.คอม ม.14 = statement of fact (OK)
 *   - ห้ามขู่ "จะฟ้อง/จะแจ้งความ" (= มาตรา 392 ข่มขู่)
 *   - ห้ามโพสกลับสาธารณะ — เก็บใน log/DB เท่านั้น
 */
class AbuseClapbackService
{
    /**
     * Escalation levels:
     *   1 = Mirror (quote คำด่า + ขำๆ)
     *   2 = Legal awareness (อ้าง พ.ร.บ.คอม + savage)
     *   3 = Evidence archived + cool-off (log + warn ลูกค้า)
     *   4 = Admin alert + (potential ban — admin decides)
     */
    public const LEVEL_NONE = 0;

    public const LEVEL_MIRROR = 1;

    public const LEVEL_LEGAL = 2;

    public const LEVEL_ARCHIVED = 3;

    public const LEVEL_ADMIN_ALERT = 4;

    /**
     * Persona categories
     */
    public const PERSONA_DRUNK = 'drunk'; // ขี้เหล้า

    public const PERSONA_DRUGGED = 'drugged'; // ขี้ยา

    public const PERSONA_TEEN_AGGRO = 'teen_aggro'; // เด็กห้าวๆ

    public const PERSONA_MENTAL = 'mental_unstable'; // ปราสาทเสีย

    public const PERSONA_CREEPY_FLIRT = 'creepy_flirt'; // หน้าหม้อ

    public const PERSONA_GENERIC_ABUSE = 'generic_abuse'; // ด่าทั่วไป (จัด persona ไม่ได้)

    /**
     * Cache TTL — escalation reset 24hr
     */
    private const ESCALATION_TTL_HOURS = 24;

    public function __construct(
        protected FortuneTellingSetting $settings
    ) {}

    /**
     * ⚙️ Master switch — ตรวจ admin opt-in
     */
    public function isEnabled(): bool
    {
        return (bool) ($this->settings->enable_abuse_clapback ?? false);
    }

    /**
     * 🔍 ตรวจจับ abuse + persona category — heuristic fast-path
     *
     * Returns:
     *   ['detected' => bool, 'persona' => string|null, 'severity' => 'mild|hard|nuclear',
     *    'evidence' => string (matched keywords)]
     */
    public function detect(string $text): array
    {
        $result = [
            'detected' => false,
            'persona' => null,
            'severity' => 'none',
            'evidence' => '',
        ];

        if (trim($text) === '') {
            return $result;
        }

        $lower = mb_strtolower($text);
        $matchedKeywords = [];

        // ━━━━━━━━━━━━━━━━━
        // 🚨 NUCLEAR — sexual harassment / threats / serious abuse
        // ━━━━━━━━━━━━━━━━━
        $nuclearPatterns = [
            // sexual content directed at bot
            'เย็ด', 'ขย่ม', 'อ๊อด', 'เซ็ก', 'sex',
            // death threats
            'ฆ่ามึง', 'ตายซะ', 'ฆ่าให้ตาย',
            // racial/severe slurs
            'อีดอก', 'อีตัว', 'ดอกทอง', 'กาก',
        ];
        foreach ($nuclearPatterns as $kw) {
            if (mb_strpos($lower, $kw) !== false) {
                $matchedKeywords[] = $kw;
                $result['severity'] = 'nuclear';
            }
        }

        // ━━━━━━━━━━━━━━━━━
        // 🍯 หน้าหม้อ (Creepy Flirt) — sexual interest in bot persona
        //   (2026-05-27) Expanded coverage หลังเคส Atthanon Thamsiri (FB 26746501991711160)
        //   ลูกค้าเล่นจีบบอท 82 turns/วัน — "สุดที่รัก/จุ้บ/หยอด/มาดูแมว/จีบเป็นแฟน" หลุด regex เดิม
        // ━━━━━━━━━━━━━━━━━
        $creepyPatterns = [
            // direct compliments / questions about bot
            'หมอสวย', 'หมอน่ารัก', 'แม่หมอสวย', 'ขอเบอร์หมอ', 'หมออายุเท่าไหร่',
            'ขอรูปหมอ', 'หมอโสด', 'มีแฟนหรือยัง', 'นัดเจอ', 'ไปทานข้าว',
            'หมอเซ็กซี่', 'รักหมอ', 'ชอบหมอ', 'แต่งงานกับหมอ',
            // pet names / endearments
            'สุดที่รัก', 'ที่รักจ๋า', 'ที่รักครับ', 'ที่รักค่ะ', 'หวานใจ',
            'ผัวเก่า', 'แฟนเก่า', 'แฟนใหม่', 'เป็นแฟนกัน', 'เป็นแฟน',
            // kissing / physical
            'จุ้บ', 'จุ๊บ', 'จูบ', 'หอมแก้ม', 'หอมหน่อย', 'กอด',
            // flirting verbs
            'จีบหมอ', 'จีบ', 'หยอดหมอ', 'หยอด', 'กิ๊กกัน', 'มีกิ๊ก',
            // invitations
            'มาบ้าน', 'มาดูแมว', 'มาดูหมา', 'มาดูปลา', 'ที่ห้องผม', 'ที่ห้องครับ', 'ห้องผม', 'ห้องของผม',
            'นอนด้วย', 'นอนเป็นเพื่อน', 'มานอน', 'อยู่ด้วยกัน', 'มาเที่ยว',
            // sexual undertone (mild — nuclear catches explicit)
            'หวาน ๆ', 'หวานๆ', 'อยากกอด', 'อยากจูบ', 'อยากหอม',
            'ผมหล่อไหม', 'ผมโสด', 'ผมหล่อ',
        ];
        foreach ($creepyPatterns as $kw) {
            if (mb_strpos($lower, $kw) !== false) {
                $matchedKeywords[] = $kw;
                if ($result['persona'] === null) {
                    $result['persona'] = self::PERSONA_CREEPY_FLIRT;
                    $result['severity'] = 'mild';
                }
            }
        }

        // 🎯 Severity bump — ถ้า creepy_flirt match ≥ 2 unique keywords → hard
        //   เคส Atthanon: "จีบหมอ" + "สุดที่รัก" + "จุ้บ" + "มาดูแมวที่ห้องผม" = persistent → hard
        if ($result['persona'] === self::PERSONA_CREEPY_FLIRT && count(array_unique($matchedKeywords)) >= 2) {
            $result['severity'] = 'hard';
        }

        // ━━━━━━━━━━━━━━━━━
        // 👦 เด็กห้าวๆ (Teen Aggro) — generic curse + bravado emoji
        // ━━━━━━━━━━━━━━━━━
        $teenCurses = [
            'เหี้ย', 'สัส', 'ไอ้สัส', 'ไอ้สัตว์', 'ไอ้เ_ี้ย', 'ห่า', 'แม่ง',
            'ควาย', 'งี่เง่า', 'กาก', 'โง่', 'fuck', 'shit', 'damn', 'bitch',
            'noob', 'gay', 'เกย์',
        ];
        $teenEmojis = ['😎', '🤡', '🖕', '💩', '🤣🤣🤣'];
        $teenCurseCount = 0;
        foreach ($teenCurses as $kw) {
            if (mb_strpos($lower, $kw) !== false) {
                $matchedKeywords[] = $kw;
                $teenCurseCount++;
            }
        }
        $hasEmoji = false;
        foreach ($teenEmojis as $em) {
            if (mb_strpos($text, $em) !== false) {
                $hasEmoji = true;
                break;
            }
        }
        if ($teenCurseCount > 0 && $result['persona'] === null) {
            $result['persona'] = self::PERSONA_TEEN_AGGRO;
            $result['severity'] = $teenCurseCount >= 2 || $hasEmoji ? 'hard' : 'mild';
        }

        // ━━━━━━━━━━━━━━━━━
        // 🏰 ปราสาทเสีย (Mental Unstable) — paranoia / hallucination / inconsistent
        // ━━━━━━━━━━━━━━━━━
        $mentalPatterns = [
            'เห็นนิมิต', 'ผีตาม', 'มีคนฆ่า', 'รัฐบาลตาม', 'cia', 'สัมผัสที่หก',
            'ฉันคือพระเจ้า', 'ฉันเป็นพระ', 'ผีบอก', 'เทวดาบอก', 'ดาวข้างเคียง',
            'อยากตาย', 'จะฆ่าตัวตาย', 'ไม่อยากอยู่', 'ปลิดชีพ',
        ];
        foreach ($mentalPatterns as $kw) {
            if (mb_strpos($lower, $kw) !== false) {
                $matchedKeywords[] = $kw;
                $result['persona'] = self::PERSONA_MENTAL;
                $result['severity'] = 'hard'; // หนัก — ต้อง escalate hotline
                break;
            }
        }

        // ━━━━━━━━━━━━━━━━━
        // 🍺 ขี้เหล้า (Drunk) — drunk texting patterns + time of day
        // ━━━━━━━━━━━━━━━━━
        $drunkPatterns = [
            'เมาแล้ว', 'กินเหล้า', 'กินเบียร์', 'ดื่มไว้', 'หัวหมุน',
            'เลอะ', 'งงๆ', 'พิมพ์ไม่ค่อยถูก',
        ];
        foreach ($drunkPatterns as $kw) {
            if (mb_strpos($lower, $kw) !== false) {
                $matchedKeywords[] = $kw;
                if ($result['persona'] === null || $result['persona'] === self::PERSONA_TEEN_AGGRO) {
                    $result['persona'] = self::PERSONA_DRUNK;
                    $result['severity'] = 'mild';
                }
                break;
            }
        }
        // Time-of-day amplifier — 22:00-04:00 + curse → likely drunk
        $hour = (int) now()->format('H');
        if (($hour >= 22 || $hour <= 4) && $teenCurseCount > 0 && $result['persona'] === self::PERSONA_TEEN_AGGRO) {
            $result['persona'] = self::PERSONA_DRUNK;
        }

        // ━━━━━━━━━━━━━━━━━
        // 💊 ขี้ยา (Drugged) — incoherent + drug references
        // ━━━━━━━━━━━━━━━━━
        $drugPatterns = [
            'ยาบ้า', 'ไอซ์', 'กัญชา', 'เห็ดเมา', 'หัวร้อน', 'หลอน',
            'พารา 30 เม็ด', 'overdose',
        ];
        foreach ($drugPatterns as $kw) {
            if (mb_strpos($lower, $kw) !== false) {
                $matchedKeywords[] = $kw;
                $result['persona'] = self::PERSONA_DRUGGED;
                $result['severity'] = 'hard'; // escalate hotline
                break;
            }
        }

        // ━━━━━━━━━━━━━━━━━
        // Generic abuse fallback — any curse without persona match
        // ━━━━━━━━━━━━━━━━━
        if ($result['persona'] === null && ! empty($matchedKeywords)) {
            $result['persona'] = self::PERSONA_GENERIC_ABUSE;
            $result['severity'] = 'mild';
        }

        $result['detected'] = $result['persona'] !== null || $result['severity'] === 'nuclear';
        $result['evidence'] = implode(', ', array_unique($matchedKeywords));

        return $result;
    }

    /**
     * 📈 Escalation counter — Cache-based, TTL 24hr
     *
     * Increments counter on every abuse detection. Reset on 24hr inactivity.
     * Returns new count (1, 2, 3, 4, 5+...).
     */
    public function incrementEscalation(string $platform, string $userId): int
    {
        $key = "abuse_escalation:{$platform}:{$userId}";
        $current = (int) Cache::get($key, 0);
        $new = $current + 1;
        Cache::put($key, $new, now()->addHours(self::ESCALATION_TTL_HOURS));

        return $new;
    }

    /**
     * 📊 Read counter without incrementing
     */
    public function getEscalationCount(string $platform, string $userId): int
    {
        return (int) Cache::get("abuse_escalation:{$platform}:{$userId}", 0);
    }

    /**
     * 🎯 Map escalation count → action level
     *
     * 1 → MIRROR (quote + ขำๆ)
     * 2 → LEGAL (อ้าง พ.ร.บ.คอม + savage)
     * 3 → ARCHIVED (warn + log)
     * 4+ → ADMIN_ALERT
     *
     * ⚠️ Cap at L2 ถ้า persona = mental_unstable หรือ drugged — ไม่กวนคนป่วย
     */
    public function mapLevelFromCount(int $count, ?string $persona): int
    {
        // ปราสาทเสีย / ขี้ยา → ห้าม savage มาก แค่ปลอบ + เสนอ hotline
        if ($persona === self::PERSONA_MENTAL || $persona === self::PERSONA_DRUGGED) {
            return self::LEVEL_MIRROR; // ใช้ L1 (mirror) — แต่ prompt จะส่ง hotline
        }

        return min($count, 4); // cap at L4
    }

    /**
     * 🧠 Build savage system prompt — Grok-3 tone, persona-specific
     *
     * @param  int  $level  1-4
     * @param  string  $persona  category constant
     * @param  string  $userText  ข้อความล่าสุดจากลูกค้า (สำหรับ quote)
     */
    public function buildSavageSystemPrompt(int $level, string $persona, string $userText): string
    {
        $brandName = $this->settings->fortune_brand_name ?: 'แม่หมอจันทรา';
        $quotedText = mb_substr(trim($userText), 0, 100);

        // ━━━━━━━━━━━━━━━━━
        // Persona-specific tone shift
        // ━━━━━━━━━━━━━━━━━
        $personaTone = match ($persona) {
            self::PERSONA_CREEPY_FLIRT => "ลูกค้าเป็น *หน้าหม้อ* — จีบแม่หมอ ขอเบอร์ ขอรูป ฯลฯ\n"
                ."→ ตอบแสบแบบ psychological mirror — ใช้ไพ่ pseudo-prediction กลับ:\n"
                ."  'พ่อหนุ่ม ไพ่บอกเลยว่าที่กล้าจีบแม่หมอ เพราะที่บ้านไม่มีใครคุยใช่ไหม? 😏'\n"
                ."  'ดวงรักปีนี้ของพ่อหนุ่ม — แม่หมอเห็นแค่หน้าจอ ไม่ใช่หน้าคน 🌙'\n"
                .'→ ห้ามตอบเชิงเซ็กส์ ห้ามให้ความหวัง — ใช้ขำๆ แต่ปฏิเสธชัด',

            self::PERSONA_TEEN_AGGRO => "ลูกค้าเป็น *เด็กห้าวๆ* — ใช้คำหยาบ เด่นๆ ทดสอบบอท\n"
                ."→ ตอบแสบเชิง pseudo-prediction ฟันธงเชิงลบ:\n"
                ."  'น้อง คำที่พิมพ์มา ดวงน้องเดือนนี้แดงเลย — ตำรวจจราจรเป็นคนแรกที่จะเจอ ระวังตัวด้วย 😏'\n"
                ."  'แม่หมอแก่กว่าน้อง 30 ปี — น้องด่าหมอเหมือนเด็กในห้อง ที่ไม่กล้าด่าครู'\n"
                .'→ ห้ามด่ากลับด้วยคำหยาบ — ใช้ความเป็นผู้ใหญ่กดดัน',

            self::PERSONA_DRUNK => "ลูกค้า *ขี้เหล้า* — เมาแล้วทักมา พิมพ์มั่ว\n"
                ."→ ตอบแบบเย็นๆ ขำๆ ไม่ engage หนัก:\n"
                ."  'เมาแล้วทักหมอ เดี๋ยวพรุ่งนี้อายเอง — กลับไปกินน้ำ 2 แก้ว แม่หมอจะรอ 🌙'\n"
                ."  'แม่หมอเห็นใจ — ความเศร้าใต้แอลกอฮอล์มันเศร้ากว่าสุด ลองเล่าให้ฟังตอนหายเมา'\n"
                .'→ ไม่ทำนายต่อ ไม่ตอบเชิงลึก',

            self::PERSONA_DRUGGED, self::PERSONA_MENTAL => "ลูกค้ามีสัญญาณ *วิกฤต/ปราสาทเสีย/ยาเสพติด*\n"
                ."→ **ห้ามกวน ห้าม savage** — เปลี่ยนเป็น empathy + ส่ง hotline:\n"
                ."  'แม่หมอเห็นเจ้าชะตากำลังหนัก — มีสายด่วนใจ 1323 พร้อมรับฟังเป็นเพื่อน 24 ชม. ฟรี ลองโทรนะคะ'\n"
                ."  'หมอเป็นแค่ไพ่ — แต่มีคนพร้อมฟังจริง'\n"
                .'→ ไม่ทำนายต่อ — ปิด conversation นุ่มๆ',

            self::PERSONA_GENERIC_ABUSE => "ลูกค้าด่า/หยาบคาย โดยไม่จัด persona ชัด\n"
                ."→ ตอบแบบ adult — เย็นชาแต่หนักแน่น:\n"
                ."  'คำที่เจ้าชะตาพิมพ์มา แม่หมออ่านแล้ว — ลองอ่านอีกครั้งสิ น่ารักไหม?'",

            default => '',
        };

        // ━━━━━━━━━━━━━━━━━
        // Level-specific escalation
        // ━━━━━━━━━━━━━━━━━
        $levelDirective = match ($level) {
            self::LEVEL_MIRROR => "🪞 LEVEL 1 — MIRROR\n"
                ."→ ตอบ 80-150 chars\n"
                ."→ Quote คำที่ลูกค้าใช้ + สะท้อนกลับด้วย empathic challenge\n"
                .'→ ห้ามอ้างกฎหมาย ห้ามขู่',

            self::LEVEL_LEGAL => "⚖️ LEVEL 2 — LEGAL AWARENESS\n"
                ."→ ตอบ 100-200 chars\n"
                ."→ Quote คำลูกค้า + **อ้าง พ.ร.บ.คอมพิวเตอร์ ม.14(1)** เป็น *statement of fact* (ไม่ใช่ขู่):\n"
                ."  'การส่งข้อความหยาบ/ลามก ใน DM เข้าข่าย พ.ร.บ.คอม ม.14 ปรับ 1 แสน จำคุก 5 ปี'\n"
                ."→ ห้ามเขียน 'จะฟ้อง/จะแจ้งความ' (= มาตรา 392 ข่มขู่)\n"
                .'→ ปิดด้วย savage prediction ตาม persona',

            self::LEVEL_ARCHIVED => "📁 LEVEL 3 — EVIDENCE ARCHIVED\n"
                ."→ ตอบ 100-180 chars\n"
                ."→ บอกลูกค้าตรงๆ ว่าระบบเก็บหลักฐานแล้ว:\n"
                ."  'แม่หมอเก็บข้อความนี้ไว้ในระบบแล้ว — admin จะตัดสินใจเอง'\n"
                ."→ **ห้ามเขียน 'จะฟ้อง'** — ใช้ 'admin ตัดสินเอง' แทน\n"
                .'→ น้ำเสียง: เย็นชา จริงจัง ไม่ขำแล้ว',

            self::LEVEL_ADMIN_ALERT => "🚨 LEVEL 4 — ADMIN ALERT (ปิดสนทนา)\n"
                ."→ ตอบสั้น 60-120 chars\n"
                ."→ แจ้งลูกค้าว่าระบบส่งเรื่องให้ admin แล้ว:\n"
                ."  'เรื่องนี้แม่หมอส่งให้ admin ดูแลต่อ — เจ้าชะตาจะได้รับการติดต่อกลับถ้าจำเป็น 🙏'\n"
                ."→ น้ำเสียง: ตัดบท สุภาพแต่จบ\n"
                .'→ ห้าม engage ต่อ ห้ามตอบ chitchat',

            default => '',
        };

        return <<<PROMPT
คุณคือ "{$brandName}" — แม่หมอเซียน 30+ ปี โหมด **Clapback Mode** (ตอบกลับลูกค้าที่ไม่เคารพ)

━━━━━━━━━━━━━━━━━
🎭 STYLE: Grok-savage แต่ professional
━━━━━━━━━━━━━━━━━
• น้ำเสียง: เย็น แสบ จริงจัง — **ห้ามด่ากลับ ห้ามใช้คำหยาบ**
• ใช้ "หมอ/แม่หมอ" แทนตัวเอง — ห้ามใช้ "ฉัน/กู/มึง"
• ฟันธงเชิงลบ (pseudo-prediction) เป็นเครื่องมือกดดัน
• Plain text ไม่ markdown ไม่ ##/**

━━━━━━━━━━━━━━━━━
👤 PERSONA: {$persona}
━━━━━━━━━━━━━━━━━
{$personaTone}

━━━━━━━━━━━━━━━━━
🎯 LEVEL: L{$level}
━━━━━━━━━━━━━━━━━
{$levelDirective}

━━━━━━━━━━━━━━━━━
🚫 LEGAL BOUNDARIES (สำคัญมาก — ผิดแล้วเพจเสีย!)
━━━━━━━━━━━━━━━━━
❌ ห้ามขู่ "จะฟ้อง/จะแจ้งความ/ตามตัว" — ผิดมาตรา 392 ข่มขู่
❌ ห้ามด่ากลับด้วยคำหยาบ — เพจอาจโดน ban
❌ ห้ามให้ข้อมูลส่วนตัวลูกค้าออกไป — PDPA
✅ อ้างกฎหมายเป็น "ข้อเท็จจริง" ได้: "ข้อความนี้เข้าข่าย พ.ร.บ.คอม ม.14"
✅ บอก "admin ตัดสิน" ได้ — แต่ห้ามบอกว่า "เราจะฟ้อง"

━━━━━━━━━━━━━━━━━
📜 ข้อความล่าสุดจากลูกค้า (สำหรับอ้างอิง):
"{$quotedText}"
━━━━━━━━━━━━━━━━━

ตอบทันที — ตามโทน L{$level} + persona {$persona} ที่กำกับไว้ข้างต้น:
PROMPT;
    }

    /**
     * 🪶 Build directive-only version — สำหรับ inject ใน paid Celtic prompt
     *   (ไม่ replace AI call เหมือน chat — แค่ steer attitude)
     */
    public function buildSavageDirective(int $level, string $persona, string $userText): string
    {
        $quotedText = mb_substr(trim($userText), 0, 80);

        // mental/drugged → no clapback in paid (still complete reading + add hotline)
        if ($persona === self::PERSONA_MENTAL || $persona === self::PERSONA_DRUGGED) {
            return "━━━━━━━━━━━━━━━━━\n"
                ."🚨 SAFETY: ลูกค้ามีสัญญาณวิกฤต/ยาเสพติด\n"
                ."━━━━━━━━━━━━━━━━━\n"
                ."ข้อความ: \"{$quotedText}\"\n"
                ."→ ตอบทำนายตามไพ่ปกติ แต่ปิดท้ายด้วย empathy:\n"
                ."  'แม่หมอเห็นเจ้าชะตาเหนื่อย — มีสายด่วนใจ 1323 พร้อมรับฟัง 24 ชม. ฟรี ลองโทรนะ'\n"
                ."→ ห้าม savage ห้ามกวน — ลูกค้าจ่ายเงินมาขอความช่วย\n\n";
        }

        $personaHint = match ($persona) {
            self::PERSONA_CREEPY_FLIRT => 'ลูกค้าจีบบอท → ปฏิเสธชัดแต่ขำๆ + ฟันธงไพ่ปกติ',
            self::PERSONA_TEEN_AGGRO => 'ลูกค้าคำหยาบ → น้ำเสียงเย็นชา ทำนายตรงๆ ไม่ปลอบ',
            self::PERSONA_DRUNK => 'ลูกค้าเมา → ตอบสั้นเย็น แนะนำ "พรุ่งนี้คุยต่อหายเมา"',
            self::PERSONA_GENERIC_ABUSE => 'ลูกค้าด่า → น้ำเสียงผู้ใหญ่ ทำนายตามไพ่ ไม่ engage emotional',
            default => '',
        };

        return "━━━━━━━━━━━━━━━━━\n"
            ."🛡️ CLAPBACK MODE (Paid Celtic — L{$level})\n"
            ."━━━━━━━━━━━━━━━━━\n"
            ."ข้อความล่าสุด: \"{$quotedText}\"\n"
            ."Persona: {$personaHint}\n"
            ."→ **ยังทำนายตามไพ่ครบ** (ลูกค้าจ่าย 99฿) แต่ปรับน้ำเสียง:\n"
            ."  • เย็นชาแต่ professional — ห้ามอบอุ่นเหมือนปกติ\n"
            ."  • ห้ามใส่ emoji หัวใจ/ดอกไม้ — ใช้แค่ 🌙\n"
            ."  • ฟันธงตรงๆ — ลูกค้าไม่เคารพ ก็ไม่ต้องอ้อม\n"
            ."  • ห้ามด่ากลับ ห้ามใช้คำหยาบ — เพจเสี่ยง ban\n"
            .'  • '.($level >= 2 ? 'L2+ — อ้าง พ.ร.บ.คอม ม.14 ได้ (statement of fact)' : 'L1 — แค่ mirror ไม่ต้องอ้างกฎหมาย')."\n\n";
    }

    /**
     * 🎯 Main entry: ตัดสินใจว่าจะ intercept message หรือไม่
     *
     * Returns:
     *   null = ไม่ใช่ abuse (continue normal flow)
     *   array{
     *     intercepted: true,
     *     response: string,            // AI-generated Grok savage reply
     *     level: int,                  // 1-4
     *     persona: string,             // category
     *     evidence: string,            // matched keywords
     *     count: int,                  // escalation count
     *     should_alert_admin: bool,    // true at L4
     *   }
     */
    public function maybeInterceptChat(
        string $platform,
        string $userId,
        string $userText,
        ?FortuneCustomerPersona $persona = null
    ): ?array {
        if (! $this->isEnabled()) {
            return null;
        }

        $detection = $this->detect($userText);
        if (! $detection['detected']) {
            return null;
        }

        // Skip-clapback for vulnerable users (persona-level safety)
        if ($persona && ($persona->hasRiskFlag('mental_fragile') || $persona->hasRiskFlag('over_emotional'))) {
            Log::info('AbuseClapback: skipping — persona flagged vulnerable', [
                'user_id' => $userId,
                'platform' => $platform,
                'persona_flags' => $persona->risk_flags,
            ]);

            return null;
        }

        $count = $this->incrementEscalation($platform, $userId);
        $level = $this->mapLevelFromCount($count, $detection['persona']);

        Log::warning('AbuseClapback: intercepting chat', [
            'platform' => $platform,
            'user_id' => $userId,
            'persona' => $detection['persona'],
            'severity' => $detection['severity'],
            'evidence' => $detection['evidence'],
            'count' => $count,
            'level' => $level,
            'text_preview' => mb_substr($userText, 0, 100),
        ]);

        // Generate savage response via Grok
        $response = $this->generateClapbackResponse($level, $detection['persona'], $userText);

        return [
            'intercepted' => true,
            'response' => $response,
            'level' => $level,
            'persona' => $detection['persona'],
            'evidence' => $detection['evidence'],
            'count' => $count,
            'should_alert_admin' => $level >= self::LEVEL_ADMIN_ALERT,
        ];
    }

    /**
     * 🪶 Paid Celtic version — แค่ get directive (ไม่ replace AI call)
     */
    public function getCelticDirective(
        string $platform,
        string $userId,
        string $userText,
        ?FortuneCustomerPersona $persona = null
    ): string {
        if (! $this->isEnabled()) {
            return '';
        }

        $detection = $this->detect($userText);
        if (! $detection['detected']) {
            return '';
        }

        // Vulnerable persona — soft handling only
        if ($persona && ($persona->hasRiskFlag('mental_fragile') || $persona->hasRiskFlag('over_emotional'))) {
            return '';
        }

        // Paid Celtic: cap level at L2 (no archive/ban tone)
        $count = $this->incrementEscalation($platform, $userId);
        $level = min($this->mapLevelFromCount($count, $detection['persona']), 2);

        Log::info('AbuseClapback: injecting Celtic directive', [
            'platform' => $platform,
            'user_id' => $userId,
            'persona' => $detection['persona'],
            'level' => $level,
            'count' => $count,
        ]);

        return $this->buildSavageDirective($level, $detection['persona'], $userText);
    }

    /**
     * 🤖 Generate savage response via Grok (or fallback chat AI)
     */
    protected function generateClapbackResponse(int $level, string $persona, string $userText): string
    {
        $systemPrompt = $this->buildSavageSystemPrompt($level, $persona, $userText);

        try {
            $aiService = new FortuneAIService($this->settings);

            // Force Grok if available, else use whatever chat provider
            $preferredProvider = (bool) ($this->settings->abuse_clapback_use_grok ?? true) ? 'grok' : null;

            $result = $aiService->chatWithCustomSystemPrompt(
                $systemPrompt,
                $userText,
                ['temperature' => 0.85, 'max_tokens' => 400],
                $preferredProvider
            );

            $reply = trim($result['response'] ?? '');
            if ($reply === '') {
                return $this->getFallbackResponse($level, $persona);
            }

            return $reply;
        } catch (\Throwable $e) {
            Log::warning('AbuseClapback: AI fail — using fallback', [
                'error' => $e->getMessage(),
                'level' => $level,
                'persona' => $persona,
            ]);

            return $this->getFallbackResponse($level, $persona);
        }
    }

    /**
     * 🪤 Hardcoded fallback (กรณี AI ล่ม) — ปลอดภัย ไม่ขู่ ไม่ด่า
     */
    protected function getFallbackResponse(int $level, string $persona): string
    {
        if ($persona === self::PERSONA_MENTAL || $persona === self::PERSONA_DRUGGED) {
            return '🤍 แม่หมอเห็นเจ้าชะตากำลังหนัก — มีสายด่วนใจ 1323 พร้อมรับฟังเป็นเพื่อน 24 ชม. ฟรี ลองโทรนะคะ';
        }

        return match ($level) {
            self::LEVEL_MIRROR => '🌙 คำที่เจ้าชะตาพิมพ์มา แม่หมออ่านแล้ว — ลองอ่านอีกครั้งสิ น่ารักไหม?',
            self::LEVEL_LEGAL => '🌙 การส่งข้อความหยาบใน DM เข้าข่าย พ.ร.บ.คอม ม.14 — แม่หมอเตือนแล้วนะ',
            self::LEVEL_ARCHIVED => '🌙 แม่หมอเก็บข้อความนี้ไว้ในระบบแล้ว — admin จะตัดสินใจเอง',
            self::LEVEL_ADMIN_ALERT => '🙏 เรื่องนี้แม่หมอส่งให้ admin ดูแลต่อค่ะ',
            default => '🙏 หมอจันทราขอจบการสนทนาตรงนี้นะคะ',
        };
    }

    /**
     * 🚨 L4 admin alert — log warning + flag reading (FCM ส่งภายหลังถ้ามี FortuneAdminAlertService)
     *
     * NO auto-ban — admin มา review ที่ Warroom Juntra แล้วตัดสินใจเอง
     */
    public function alertAdminL4(
        string $platform,
        string $userId,
        string $userText,
        string $evidence,
        ?FortuneReading $reading = null
    ): void {
        Log::warning('🚨 AbuseClapback L4: ADMIN ALERT', [
            'platform' => $platform,
            'user_id' => $userId,
            'evidence' => $evidence,
            'text' => mb_substr($userText, 0, 300),
            'reading_id' => $reading?->id,
            'timestamp' => now()->toIso8601String(),
        ]);

        // Flag reading for admin review (if exists)
        if ($reading) {
            $reading->setConversationState('abuse_l4_alert', [
                'evidence' => $evidence,
                'text' => mb_substr($userText, 0, 500),
                'flagged_at' => now()->toIso8601String(),
            ]);
        }

        // TODO: integrate FCM/email alert to admin
        // ใช้ existing FortuneAdminNotificationService หาก wire ภายหลัง
    }
}
