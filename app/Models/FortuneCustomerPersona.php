<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 👤 (2026-05-14) Fortune Customer Persona — บุคลิก/นิสัยลูกค้าระยะยาว
 *
 * เก็บข้อมูลเชิงลึกของลูกค้าแต่ละ ID เพื่อให้ AI ปรับ tone การคุยให้เหมาะสม
 *
 * แยกจาก LineBotConversation (24hr session) — persona เก็บถาวร update ทุกครั้งที่คุย
 *
 * @property int    $id
 * @property string $platform              'facebook' / 'line'
 * @property string $platform_user_id      FB PSID / LINE userId
 * @property string|null $display_name
 * @property array|null  $demographics      {age_range, gender_hint, job_hint, location_hint}
 * @property array|null  $traits            personality traits array
 * @property array|null  $likes
 * @property array|null  $dislikes
 * @property array|null  $conversation_themes
 * @property array|null  $communication_style
 * @property string|null $topic_tags        comma-separated (Obsidian-style)
 * @property string|null $note_markdown
 * @property int         $observation_count
 * @property \Carbon\Carbon|null $last_observed_at
 * @property \Carbon\Carbon|null $last_persona_sync_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class FortuneCustomerPersona extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'fortune_customer_personas';

    protected $fillable = [
        'platform',
        'platform_user_id',
        'display_name',
        'demographics',
        'traits',
        'likes',
        'dislikes',
        'conversation_themes',
        'communication_style',
        // 🚩 (2026-05-25) Risk flags — ลูกค้าที่ต้องระวัง (mental_fragile/abusive/etc.)
        'risk_flags',
        'topic_tags',
        'note_markdown',
        'observation_count',
        'last_observed_at',
        'last_persona_sync_at',
        // 🚫 (2026-05-18) Rambler cooldown — ตรวจจับลูกค้าเวิ่นเว้อ + silence 10 ชม
        'sales_pitch_count',
        'sales_pitch_failed_count',
        'time_waster_score',
        'last_pitch_at',
        'chat_silenced_until',
        'last_silence_reason',
        // 🚫 (2026-05-29 Fix C) Escalation / persistent ban — ผู้กระทำซ้ำข้ามวัน
        'silence_count',
        'admin_abuse_alerted_at',
    ];

    protected $casts = [
        'demographics' => 'array',
        'traits' => 'array',
        'likes' => 'array',
        'dislikes' => 'array',
        'conversation_themes' => 'array',
        'communication_style' => 'array',
        'risk_flags' => 'array',
        'observation_count' => 'integer',
        'last_observed_at' => 'datetime',
        'last_persona_sync_at' => 'datetime',
        // 🚫 (2026-05-18) Rambler cooldown casts
        'sales_pitch_count' => 'integer',
        'sales_pitch_failed_count' => 'integer',
        'time_waster_score' => 'integer',
        'last_pitch_at' => 'datetime',
        'chat_silenced_until' => 'datetime',
        // 🚫 (2026-05-29 Fix C) Escalation casts
        'silence_count' => 'integer',
        'admin_abuse_alerted_at' => 'datetime',
    ];

    /** Window สำหรับนับ pitch ↔ chitchat (นาที) */
    public const PITCH_WINDOW_MINUTES = 30;

    /** Threshold จำนวน chitchat-after-pitch ที่ทำให้ trigger silence */
    public const SILENCE_TRIGGER_THRESHOLD = 3;

    /** ระยะเวลา silence (ชั่วโมง) */
    public const SILENCE_DURATION_HOURS = 10;

    /** คะแนน time_waster ที่บวกเมื่อ trigger silence แต่ละครั้ง */
    public const SCORE_INCREMENT_ON_SILENCE = 20;

    /** คะแนน time_waster ที่ลดเมื่อ resume จาก cooldown */
    public const SCORE_DECAY_ON_RESUME = 10;

    /** Threshold score ที่ inject directive ลง AI prompt */
    public const SCORE_INJECT_THRESHOLD = 40;

    /**
     * 🆕 (2026-05-29) Free-chat daily cap — content-agnostic wind-down
     *
     * จำนวน free-chat turns สูงสุด/วัน (ไม่จ่าย + ไม่มี buy-intent) ก่อนเงียบ
     * ใช้แก้ช่องโหว่ Hook C เดิมที่พึ่ง keyword detector — เคส venter/ผู้สูงอายุ
     * พิมพ์เรื่องแปลกๆ (พระเจ้า/บาป/บุญ/ขายยา) ที่ keyword จับไม่ได้ → ไม่เคยเงียบ
     * (เคสสนิท สาม่าน 87 turns/วัน 0฿). ปรับค่าได้ที่นี่
     */
    public const FREECHAT_DAILY_CAP = 25;

    /**
     * 🚫 (2026-05-29 Fix C) Escalation — โดน silence ครบกี่ครั้ง (lifetime) ถึงยืดเป็น hard block
     *
     * 5 ครั้ง = pattern ชัดเจน (5 วัน/รอบที่ปั่น free-chat จนเงียบ โดยไม่จ่าย ไม่ buy-intent)
     * vulnerable users (hasCriticalKeyword) ไม่เคยมาถึง triggerSilence อยู่แล้ว (bypass upstream)
     */
    public const HARD_BLOCK_SILENCE_THRESHOLD = 5;

    /** ระยะเวลา silence เมื่อ escalate เป็น hard block (ชั่วโมง) — 7 วัน */
    public const HARD_BLOCK_DURATION_HOURS = 168;

    /**
     * 🛒 Keyword bypass — ถ้าข้อความลูกค้ามี keyword พวกนี้ → ข้าม silence
     *
     * เหตุผล: ลูกค้าอยากซื้อจริง / พร้อมจ่าย / กลับมาถามดูดวง → ต้องตอบ
     */
    public const SILENCE_BYPASS_KEYWORDS = [
        'ดูดวง', 'จ่าย', 'โอน', 'qr', 'คิวอาร์', 'พร้อม', 'พร้อมแล้ว',
        '39', '99', 'ไพ่', 'เซลติก', 'celtic', 'deep', 'ละเอียด',
        'สลิป', 'slip', 'ชำระ', 'ค่าบริการ',
    ];

    /**
     * 🔍 หา persona จาก platform + user_id (ไม่สร้างใหม่)
     */
    public static function findByPlatformUser(string $platform, string $userId): ?self
    {
        return self::where('platform', $platform)
            ->where('platform_user_id', $userId)
            ->first();
    }

    /**
     * 🆕 หา หรือ สร้าง persona ใหม่ (idempotent)
     */
    public static function getOrCreate(string $platform, string $userId, ?string $displayName = null): self
    {
        return self::firstOrCreate(
            ['platform' => $platform, 'platform_user_id' => $userId],
            [
                'display_name' => $displayName,
                'observation_count' => 0,
            ]
        );
    }

    /**
     * 🔄 Merge data ใหม่ (additive — ไม่ overwrite ของเก่า)
     *
     * - JSON arrays (traits, likes, etc.) → union (unique)
     * - JSON objects (demographics, communication_style) → ผสม (ค่าใหม่ทับเฉพาะที่ระบุ)
     * - topic_tags → union comma-separated
     */
    public function mergeData(array $extracted): self
    {
        // Array fields — union (unique)
        foreach (['traits', 'likes', 'dislikes', 'conversation_themes'] as $key) {
            if (! empty($extracted[$key]) && is_array($extracted[$key])) {
                $existing = $this->{$key} ?? [];
                $merged = array_values(array_unique(array_merge($existing, $extracted[$key])));
                // Cap ไม่ให้บวมเกินไป — เก็บล่าสุด 30 รายการ
                $this->{$key} = array_slice($merged, -30);
            }
        }

        // Object fields — merge (overwrite specific keys)
        foreach (['demographics', 'communication_style'] as $key) {
            if (! empty($extracted[$key]) && is_array($extracted[$key])) {
                $existing = $this->{$key} ?? [];
                $this->{$key} = array_merge($existing, $extracted[$key]);
            }
        }

        // 🚩 (2026-05-25) Risk flags — sticky merge
        //   Rule: ค่า true ห้ามทับด้วย false อัตโนมัติ — ต้อง explicit positive signal
        //   เช่น mental_fragile=true แล้ว → ถ้า extract รอบหน้าได้ false → IGNORE
        //        (เพราะลูกค้าวิกฤตครั้งเดียว = ต้อง flag ยาว)
        //   ยกเว้น crisis_resources_sent (timestamp string) → ทับได้เสมอ (อัพเดทเวลาส่ง)
        if (! empty($extracted['risk_flags']) && is_array($extracted['risk_flags'])) {
            $existing = $this->risk_flags ?? [];
            $merged = $existing;
            foreach ($extracted['risk_flags'] as $flag => $value) {
                if ($flag === 'crisis_resources_sent') {
                    // Timestamp field — ทับได้
                    $merged[$flag] = $value;
                    continue;
                }
                // Boolean sticky: true ทับ false / true ไม่โดนทับ
                if (! empty($value) && $value !== 'false') {
                    $merged[$flag] = true;
                }
                // value=false → ข้าม (sticky)
            }
            $this->risk_flags = $merged;
        }

        // Topic tags — union comma-separated
        if (! empty($extracted['topic_tags'])) {
            $existingTags = $this->topic_tags
                ? array_map('trim', explode(',', $this->topic_tags))
                : [];
            $newTags = is_array($extracted['topic_tags'])
                ? $extracted['topic_tags']
                : array_map('trim', explode(',', $extracted['topic_tags']));
            $merged = array_values(array_unique(array_filter(array_merge($existingTags, $newTags))));
            $this->topic_tags = implode(', ', array_slice($merged, -50));
        }

        $this->observation_count = ($this->observation_count ?? 0) + 1;
        $this->last_observed_at = now();
        $this->last_persona_sync_at = now();

        return $this;
    }

    /**
     * 🚩 (2026-05-25) เช็คว่ามี risk flag ตัวที่ระบุไหม
     */
    public function hasRiskFlag(string $flag): bool
    {
        $flags = $this->risk_flags ?? [];

        return ! empty($flags[$flag]);
    }

    /**
     * 🚩 (2026-05-25) บันทึก timestamp ที่ส่ง crisis resources (1323/1669)
     */
    public function markCrisisResourcesSent(): void
    {
        $flags = $this->risk_flags ?? [];
        $flags['crisis_resources_sent'] = now()->toIso8601String();
        $this->risk_flags = $flags;
        $this->save();
    }

    /**
     * 🎯 สร้าง context block สำหรับ inject ใน AI system prompt
     *
     * แสดงแค่ข้อมูลที่มี (ไม่เปลือง token)
     * ใช้เพื่อปรับ tone — **ไม่ใช่อ้างตรงๆ ในคำตอบ**
     */
    public function toAiContextBlock(): string
    {
        $lines = [];

        // Demographics
        // 🚫 (2026-05-26) DO NOT inject gender_hint — AI Grok mirror pronoun เป็น "ครับ"
        //   เคส "ขุน" FB 27532619583011840: persona male → บอทตอบ "เข้าใจเลยครับ" (ผิด!)
        //   แม่หมอจันทรา = ผู้หญิงเสมอ ต้องใช้ "ค่ะ" — gender ลูกค้าไม่เกี่ยวกับ pronoun ของแม่หมอ
        //   เก็บ gender_hint ใน DB ไว้ใช้กับ name salutation (คุณ/พี่) แต่ห้าม inject ใน AI prompt
        $demo = $this->demographics ?? [];
        $demoParts = [];
        if (! empty($demo['age_range']) && $demo['age_range'] !== 'unknown') {
            $demoParts[] = "อายุ ~{$demo['age_range']}";
        }
        if (! empty($demo['job_hint']) && $demo['job_hint'] !== 'unknown') {
            $demoParts[] = "งาน: {$demo['job_hint']}";
        }
        if (! empty($demoParts)) {
            $lines[] = '• ' . implode(' / ', $demoParts);
        }

        // Traits
        if (! empty($this->traits)) {
            $lines[] = '• บุคลิก: ' . implode(', ', array_slice($this->traits, -5));
        }

        // Likes / Dislikes (cap แต่ละด้าน 3-5 รายการ)
        if (! empty($this->likes)) {
            $lines[] = '• ชอบ: ' . implode(', ', array_slice($this->likes, -5));
        }
        if (! empty($this->dislikes)) {
            $lines[] = '• ไม่ชอบ: ' . implode(', ', array_slice($this->dislikes, -3));
        }

        // Conversation themes
        if (! empty($this->conversation_themes)) {
            $lines[] = '• เคยคุยเรื่อง: ' . implode(', ', array_slice($this->conversation_themes, -3));
        }

        // Communication style
        $style = $this->communication_style ?? [];
        $styleParts = [];
        if (! empty($style['tone'])) {
            $styleParts[] = "tone: {$style['tone']}";
        }
        if (! empty($style['formality'])) {
            $styleParts[] = "formality: {$style['formality']}";
        }
        if (! empty($style['emoji_usage'])) {
            $styleParts[] = "emoji: {$style['emoji_usage']}";
        }
        if (! empty($styleParts)) {
            $lines[] = '• สไตล์การคุย: ' . implode(' / ', $styleParts);
        }

        // 🚫 (2026-05-18) Rambler/time-waster directive — inject เมื่อคะแนนสูง
        //   เหตุผล: ลูกค้าฟุ้งซ่านไม่ปิดการขายเก่า → AI ต้องตอบสั้น ปฏิเสธ chitchat
        //   Threshold 40 = ติด silence แล้ว 2 รอบ (20 + 20) → low engagement ชัดเจน
        $timeWasterScore = (int) ($this->time_waster_score ?? 0);
        if ($timeWasterScore >= self::SCORE_INJECT_THRESHOLD) {
            $lines[] = "⚠️ score รบกวน: {$timeWasterScore}/100 (เคยคุยฟุ้งไม่ปิดการขาย) — ตอบสั้น ≤1 ประโยค ไม่ chitchat ปรับเข้าเรื่องดูดวงทันที";
        }

        // 🚩 (2026-05-25) Risk flags directive — guidance ละเอียดตามความเสี่ยง
        $riskGuidance = $this->buildRiskGuidanceLines();
        if (! empty($riskGuidance)) {
            $lines = array_merge($lines, $riskGuidance);
        }

        if (empty($lines)) {
            return ''; // ไม่มีข้อมูล → ไม่ inject block
        }

        return "[👤 CUSTOMER_PERSONA — ใช้ปรับ tone เท่านั้น ห้ามอ้างตรงๆ ในคำตอบ]\n"
            . implode("\n", $lines)
            . "\n⚠️ AI ใช้ข้อมูลนี้ \"ใต้พรม\" — ปรับน้ำเสียง/คำพูดให้เข้ากับลูกค้า แต่อย่าเอ่ยถึงว่า \"จำได้ว่า...\" ตรงๆ";
    }

    /**
     * 🚩 (2026-05-25) สร้าง risk guidance lines — directive ละเอียดตาม flag
     *
     * แต่ละ flag → 1 บรรทัด directive ที่ AI ต้องปฏิบัติ
     * ใช้ใน toAiContextBlock() — append หลัง persona lines
     */
    public function buildRiskGuidanceLines(): array
    {
        $flags = $this->risk_flags ?? [];
        if (empty($flags)) {
            return [];
        }

        $lines = [];

        // mental_fragile — วิกฤต ฆ่าตัวตาย ดื้อยา
        if (! empty($flags['mental_fragile'])) {
            $lines[] = '⚠️ MENTAL_FRAGILE: ลูกค้าเคยพูดเรื่องวิกฤต/ทำร้ายตัวเอง → ตอบสั้น 1-2 บรรทัด / ห้าม pitch / ไม่ถามขุดอารมณ์';

            // 7-day cadence สำหรับ 1323/1669
            $lastSent = $flags['crisis_resources_sent'] ?? null;
            $needsResource = true;
            if ($lastSent) {
                try {
                    $needsResource = \Carbon\Carbon::parse($lastSent)
                        ->lt(now()->subDays(7));
                } catch (\Throwable $e) {
                    $needsResource = true;
                }
            }
            if ($needsResource) {
                $lines[] = '   → แทรกบรรทัด "หากต้องการคนคุย โทร 1323 (สุขภาพจิต) หรือ 1669 (ฉุกเฉิน) ฟรี 24 ชม. นะคะ 🙏" ในคำตอบนี้';
            }
        }

        // over_emotional — อ่อนไหวมาก
        if (! empty($flags['over_emotional'])) {
            $lines[] = '⚠️ OVER_EMOTIONAL: อ่อนไหวง่าย → empathy สั้น / ไม่ถามต่อ ห้าม "เล่าให้ฟังหน่อย"';
        }

        // quiet_listener — ตอบสั้น
        if (! empty($flags['quiet_listener'])) {
            $lines[] = '⚠️ QUIET_LISTENER: ตอบสั้นเสมอ → AI ตอบ ≤2 ประโยค ห้ามถามต่อ ห้าม open-ended';
        }

        // abusive_tone — คำหยาบ/ก้าวร้าว
        if (! empty($flags['abusive_tone'])) {
            $lines[] = '⚠️ ABUSIVE_TONE: ใช้คำหยาบ/ก้าวร้าว → ตอบกลางๆ ห้าม emoji เยอะ ห้ามยั่ว ปกป้องขอบเขต';
        }

        // decline_pusher — ปฏิเสธแล้วยังตื๊อ
        if (! empty($flags['decline_pusher'])) {
            $lines[] = '⚠️ DECLINE_PUSHER: เคารพ decline ทันที — ลูกค้า "ไม่พร้อม/ไว้คราวหน้า" → ยุติทันที ห้ามตื๊อขายเพิ่ม';
        }

        // scam_victim — เคยโดนโกง
        if (! empty($flags['scam_victim'])) {
            $lines[] = '⚠️ SCAM_VICTIM: เคยโดนหลอกเงิน → ไม่ pitch แรง / explain ราคา/คุณค่าอย่างชัดเจน';
        }

        // complaint_prone — ชอบบ่น
        if (! empty($flags['complaint_prone'])) {
            $lines[] = '⚠️ COMPLAINT_PRONE: ชอบบ่น/ติ → รับฟัง+empathy / ห้าม defensive / ไม่ pitch ทันทีหลังบ่น';
        }

        // 🃏 (2026-05-25) tarot_literate — ลูกค้ารู้ไพ่ — สำคัญมาก (เคส R3726)
        //   ลูกค้าเป็น tarot reader / เคยเรียนไพ่ → จับได้ทันทีว่าบอท "ยกหน้าตำรา"
        //   ผลคือ: บ่นว่า "เหมือนคนเพิ่งหัด" + เปรียบเทียบกับครั้งก่อน + ลด trust
        if (! empty($flags['tarot_literate'])) {
            $lines[] = '🃏 TAROT_LITERATE: ลูกค้าเป็น tarot reader / รู้ไพ่ — สำคัญมาก:';
            $lines[] = '   → ❌ ลดการอ้างชื่อไพ่ตรงๆ ("ห้าดาบ" / "นักมายากล")';
            $lines[] = '   → ❌ เลิกอธิบายความหมายตามตำรา (textbook meaning)';
            $lines[] = '   → ✅ ใช้ภาษา intuitive: "แม่หมอสัมผัสได้ว่า..." / "พลังในไพ่บอก..."';
            $lines[] = '   → ✅ ผูกไพ่กับ situation จริงของลูกค้าโดยตรง (ไม่ใช่ generic)';
            $lines[] = '   → ✅ ยอมรับเมื่อลูกค้าวิจารณ์ ห้าม defensive ห้ามอ้างว่า "ผมไม่ได้ใช้ตำรา"';
        }

        // 🎭 (2026-05-25) disruptive_troll — มากวนป่วน/เพ้อเจ้อ/ไม่ตั้งใจดูดวง
        if (! empty($flags['disruptive_troll'])) {
            $lines[] = '⚠️ DISRUPTIVE_TROLL: ลูกค้ามาเล่น/กวนป่วน/เพ้อเจ้อ — ไม่จริงจังดูดวง';
            $lines[] = '   → ตอบ ≤1 ประโยค สั้นที่สุด ไม่ engage ไม่ pitch ไม่ empathy';
            $lines[] = '   → ถ้า provoke ติด 3 turn → "🙏 ขอบคุณที่ทักนะคะ หมอขอตัวไปดูเคสที่จริงจังก่อนค่ะ"';
            $lines[] = '   → หลังจากนั้น silent (ระบบจะ set farewell_closed อัตโนมัติ)';
        }

        // 🧠 (2026-05-25) hostile_superior — โต้แย้งหาเรื่อง / คิดว่าเหนือกว่า / กล่าวหาหลอก
        if (! empty($flags['hostile_superior'])) {
            $lines[] = '⚠️ HOSTILE_SUPERIOR: ลูกค้าโต้แย้ง/คิดว่าเหนือกว่า/กล่าวหาว่าหลอก';
            $lines[] = '   → ใช้ psychology แบบมีจิตยา (ไม่โต้กลับไร้เหตุผล) มีอยู่ 3 รูปแบบ:';
            $lines[] = '';
            $lines[] = '   🪞 1. Mirror ที่มีเหตุผล (เลือกใช้เมื่อมีจังหวะดี):';
            $lines[] = '      "ถ้าเชื่อว่าหมอผิด คุณจะมาคุยทำไมคะ? คนที่ชีวิตดีจริงๆ ไม่มีเวลาด่าใครหรอกค่ะ"';
            $lines[] = '';
            $lines[] = '   📜 2. Digital legitimacy proof (เมื่อกล่าวหา "หลอก"):';
            $lines[] = '      "เรามีแจ้งราคาบริการชัดเจน / มีประวัติการดูดวงทุกครั้งให้เรียกดูได้';
            $lines[] = '       ทุกข้อความถูกบันทึกไว้หมด — ไม่ใช่เพจกะโหลกกะลานะคะ"';
            $lines[] = '      → สื่อ "เราระบบใหญ่ มีหลักฐานทุกอย่าง" → ทำให้ลูกค้ารู้สึกว่าเรา "เหนือกว่า"';
            $lines[] = '';
            $lines[] = '   👋 3. Assertive goodbye (เมื่อ provoke ติด 3+ turn / พูดไม่รู้เรื่อง):';
            $lines[] = '      "หมอมีเคสอื่นที่กำลังเครียดต้องช่วย ไม่มีเวลากับคนที่ชีวิตดีอยู่แล้วนะคะ';
            $lines[] = '       ขอตัวก่อนค่ะ 🙏" → หลังจากนั้น silent + แอดมินอาจพิจารณา block';
            $lines[] = '';
            $lines[] = '   🚫 ห้าม: empathy / "เล่าให้ฟังหน่อย" / โต้แบบใช้คำหยาบ / ลดตัวเองลงสู้';
            $lines[] = '   ✅ Tone: นิ่ง / เป็นทางการ / มี dignity / ไม่ตื่นเต้น / ไม่ขอโทษเปล่าๆ';
        }

        return $lines;
    }

    /**
     * 🛒 (2026-05-18) บันทึก "บอทเสนอขาย" — เพิ่ม count + set last_pitch_at
     *
     * Throttle: ถ้า last_pitch_at < 5 นาที → skip (กัน double count flow เดียวกัน)
     *
     * @return bool true = บันทึก, false = throttled
     */
    public function recordPitch(): bool
    {
        // Throttle — pitch flow อาจ trigger หลายจุด (askConfirmation → startDeepFlow → presentTier)
        if ($this->last_pitch_at && $this->last_pitch_at->gt(now()->subMinutes(5))) {
            return false;
        }

        $this->sales_pitch_count = ($this->sales_pitch_count ?? 0) + 1;
        $this->last_pitch_at = now();
        $this->save();

        return true;
    }

    /**
     * 💤 (2026-05-18) ตรวจว่าข้อความลูกค้ามี keyword bypass silence หรือไม่
     *
     * ถ้า true → ดำเนิน flow ปกติ แม้ chat_silenced_until > now
     * เพื่อให้ลูกค้าที่ "พร้อมจ่าย/ดูดวงต่อ" ไม่โดนตัด
     */
    public static function shouldBypassSilence(string $message): bool
    {
        $lower = mb_strtolower(trim($message));
        if ($lower === '') {
            return false;
        }

        foreach (self::SILENCE_BYPASS_KEYWORDS as $keyword) {
            if (mb_strpos($lower, mb_strtolower($keyword)) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * 🤐 (2026-05-18) ตรวจว่า bot ควรเงียบกับลูกค้าคนนี้หรือไม่
     *
     * Lazy resume: ถ้า chat_silenced_until <= now → reset failed_count, decay score
     */
    public function isChatSilenced(): bool
    {
        // Lazy resume — ถ้าหมดเวลาแล้ว clear state
        if ($this->chat_silenced_until && $this->chat_silenced_until->lte(now())) {
            $this->resumeFromCooldown();
            return false;
        }

        return $this->chat_silenced_until !== null
            && $this->chat_silenced_until->gt(now());
    }

    /**
     * 🔄 (2026-05-18) ปลดล็อค cooldown + decay score
     *
     * เรียกอัตโนมัติจาก isChatSilenced() เมื่อ chat_silenced_until <= now
     */
    public function resumeFromCooldown(): void
    {
        $oldScore = (int) ($this->time_waster_score ?? 0);
        $newScore = max(0, $oldScore - self::SCORE_DECAY_ON_RESUME);

        $this->chat_silenced_until = null;
        $this->sales_pitch_failed_count = 0;
        $this->time_waster_score = $newScore;
        $this->save();
    }

    /**
     * 💬 (2026-05-18) บันทึก "ลูกค้าตอบฟุ้งหลังบอทเสนอ" — increment failed_count
     *
     * Caller ต้องตรวจ pre-conditions ก่อนเรียก:
     *  - last_pitch_at อยู่ใน rolling 30min window
     *  - looksLikeMetaOrChitchat(message) = true
     *  - ! shouldBypassSilence(message)
     *
     * @return bool true = ทำให้ failed_count ถึง threshold (caller ควร trigger silence)
     */
    public function recordChitchatAfterPitch(string $messageText): bool
    {
        $this->sales_pitch_failed_count = ($this->sales_pitch_failed_count ?? 0) + 1;
        $this->save();

        return $this->sales_pitch_failed_count >= self::SILENCE_TRIGGER_THRESHOLD;
    }

    /**
     * 🔇 (2026-05-18) Trigger silence cooldown — เงียบ 10 ชม + score+=20
     *
     * @param  string|null  $reason  บันทึกเหตุผล (debug + admin UI)
     */
    public function triggerSilence(?string $reason = null): void
    {
        // 🚫 (2026-05-29 Fix C) นับ silence สะสม (lifetime, ไม่ decay ตอน resume)
        //   ใช้จับ "ผู้กระทำซ้ำข้ามวัน" ที่ time_waster_score ไม่เคยสะสมถึงเพราะ decay -10/resume
        $this->silence_count = (int) ($this->silence_count ?? 0) + 1;

        // โดน silence ครบ threshold (5 ครั้ง) → escalate เป็น hard block (ยืดเป็น 7 วัน)
        $isHardBlock = $this->silence_count >= self::HARD_BLOCK_SILENCE_THRESHOLD;
        $durationHours = $isHardBlock
            ? self::HARD_BLOCK_DURATION_HOURS   // ผู้กระทำซ้ำ → 7 วัน
            : self::SILENCE_DURATION_HOURS;     // ปกติ → 10 ชม.

        $this->chat_silenced_until = now()->addHours($durationHours);
        $this->time_waster_score = min(
            100,
            (int) ($this->time_waster_score ?? 0) + self::SCORE_INCREMENT_ON_SILENCE
        );
        $this->last_silence_reason = $reason;
        $this->save();

        // แจ้ง admin "ครั้งเดียว" เมื่อ escalate เป็น hard block — NO auto-ban (admin ตัดสินเอง)
        //   สอดคล้องหลัก AbuseClapback L4 = admin alert ไม่แบนอัตโนมัติ
        if ($isHardBlock && $this->admin_abuse_alerted_at === null) {
            $this->admin_abuse_alerted_at = now();
            $this->save();
            $this->alertAdminRepeatAbuse($reason);
        }
    }

    /**
     * 🚨 (2026-05-29 Fix C) แจ้ง admin เมื่อลูกค้าโดน silence ซ้ำจนถึง hard block
     *
     * Best-effort + non-blocking — ห้าม throw ขวาง flow silence
     * ไม่ auto-ban — แค่แจ้งให้ admin review เอง (สอดคล้องหลัก AbuseClapback L4)
     */
    protected function alertAdminRepeatAbuse(?string $reason = null): void
    {
        try {
            $name = trim((string) ($this->display_name ?? '')) ?: '(ไม่ทราบชื่อ)';
            $days = (int) round(self::HARD_BLOCK_DURATION_HOURS / 24);

            $msg = sprintf(
                "🚫 Repeat free-chat abuse — silence ครั้งที่ %d (hard block %d วัน)\n".
                'ลูกค้า: %s [%s/%s]'."\n".
                'score: %d | เหตุผล: %s'."\n".
                '→ เงียบถึง %s (review persona #%d)',
                (int) $this->silence_count,
                $days,
                $name,
                (string) $this->platform,
                (string) $this->platform_user_id,
                (int) ($this->time_waster_score ?? 0),
                $reason ?? '-',
                $this->chat_silenced_until?->format('Y-m-d H:i') ?? '-',
                (int) ($this->id ?? 0)
            );

            if (class_exists(\App\Services\LineAlertService::class)) {
                app(\App\Services\LineAlertService::class)->alertSystemError($msg);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::debug('FortuneCustomerPersona: alert repeat-abuse fail (non-blocking)', [
                'persona_id' => $this->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 🎬 (2026-05-18) สร้าง "เนียน goodbye" message ก่อน silence เริ่ม
     *
     * ปรับ tone ตาม communication_style.formality + display_name
     */
    public function buildGoodbyeMessage(): string
    {
        $style = $this->communication_style ?? [];
        $formality = strtolower((string) ($style['formality'] ?? 'polite'));

        $name = trim((string) ($this->display_name ?? ''));
        $greet = $name !== '' ? "คุณ{$name}" : 'จ๊ะ';

        // Formal / polite — สุภาพ
        if (in_array($formality, ['formal', 'polite', 'very_polite'], true)) {
            return "ขออภัยค่ะ {$greet} ✨ ตอนนี้แม่หมอติดดูแลลูกค้าหลายท่าน\n"
                . "ถ้าพร้อมดูดวง พิมพ์ \"ดูดวง\" ได้เลยนะคะ ❤️\n"
                . "เดี๋ยวกลับมาคุยใหม่ตอนว่างค่ะ 🙏";
        }

        // Casual / informal — กันเอง
        return "เดี๋ยวก่อนนะ {$greet} 🌙 แม่ติดดูคนอื่นเยอะ\n"
            . "พร้อมดูดวงเมื่อไหร่พิมพ์ \"ดูดวง\" มาได้เลย ❤️\n"
            . "ไว้เจอกันใหม่นะ";
    }

    /**
     * 🎮 (2026-05-14) Gamification — Level จาก observation_count
     *
     * Lv 1: 1-2 obs / Lv 2: 3-5 / Lv 3: 6-9 / Lv 5: 10-19
     * Lv 7: 20-34 / Lv 10: 35-69 / Lv 15: 70-149 / Lv 20: 150-299
     * Lv 25: 300-599 / Lv 30: 600+
     */
    public function getLevel(): int
    {
        $count = (int) ($this->observation_count ?? 0);
        if ($count <= 0) {
            return 0;
        }

        // Log-scaled: lv ≈ 5 * log2(count + 1)
        $level = (int) floor(5 * log($count + 1, 2));

        return max(1, min(30, $level));
    }

    /**
     * 🎯 XP progress to next level (0-100)
     */
    public function getXpProgress(): int
    {
        $count = (int) ($this->observation_count ?? 0);
        $currentLevel = $this->getLevel();

        if ($currentLevel >= 30) {
            return 100;
        }

        // Inverse of getLevel: 2^(lv/5) - 1
        $currentThreshold = (int) ceil(pow(2, $currentLevel / 5) - 1);
        $nextThreshold = (int) ceil(pow(2, ($currentLevel + 1) / 5) - 1);

        $span = max(1, $nextThreshold - $currentThreshold);
        $progress = (int) round((($count - $currentThreshold) / $span) * 100);

        return max(0, min(100, $progress));
    }

    /**
     * 💎 Rarity tier — common / rare / epic / legendary
     *
     * Combines observation_count + data completeness
     */
    public function getRarity(): string
    {
        $count = (int) ($this->observation_count ?? 0);
        $completeness = $this->getDataCompleteness();

        $score = $count + ($completeness * 0.5);

        return match (true) {
            $score >= 80 => 'legendary',
            $score >= 40 => 'epic',
            $score >= 15 => 'rare',
            default => 'common',
        };
    }

    /**
     * 🎨 Rarity display config (label, color, gradient, glow, icon)
     */
    public function getRarityConfig(): array
    {
        return match ($this->getRarity()) {
            'legendary' => [
                'label' => 'ตำนาน',
                'label_en' => 'Legendary',
                'icon' => '👑',
                'border' => 'border-amber-400 dark:border-amber-500',
                'gradient' => 'from-amber-400 via-yellow-500 to-orange-500',
                'text' => 'text-amber-700 dark:text-amber-300',
                'bg_soft' => 'bg-amber-50 dark:bg-amber-900/30',
                'glow' => 'shadow-[0_0_20px_rgba(251,191,36,0.5)] dark:shadow-[0_0_30px_rgba(251,191,36,0.4)]',
                'ring' => 'ring-2 ring-amber-400',
            ],
            'epic' => [
                'label' => 'มหากาฬ',
                'label_en' => 'Epic',
                'icon' => '💜',
                'border' => 'border-purple-400 dark:border-purple-500',
                'gradient' => 'from-purple-500 via-fuchsia-500 to-pink-500',
                'text' => 'text-purple-700 dark:text-purple-300',
                'bg_soft' => 'bg-purple-50 dark:bg-purple-900/30',
                'glow' => 'shadow-[0_0_18px_rgba(168,85,247,0.45)] dark:shadow-[0_0_25px_rgba(168,85,247,0.35)]',
                'ring' => 'ring-2 ring-purple-400',
            ],
            'rare' => [
                'label' => 'หายาก',
                'label_en' => 'Rare',
                'icon' => '💎',
                'border' => 'border-sky-400 dark:border-sky-500',
                'gradient' => 'from-sky-500 via-blue-500 to-indigo-500',
                'text' => 'text-sky-700 dark:text-sky-300',
                'bg_soft' => 'bg-sky-50 dark:bg-sky-900/30',
                'glow' => 'shadow-[0_0_15px_rgba(56,189,248,0.4)] dark:shadow-[0_0_20px_rgba(56,189,248,0.3)]',
                'ring' => 'ring-2 ring-sky-400',
            ],
            default => [
                'label' => 'ทั่วไป',
                'label_en' => 'Common',
                'icon' => '🌿',
                'border' => 'border-slate-300 dark:border-slate-600',
                'gradient' => 'from-slate-400 via-gray-500 to-zinc-500',
                'text' => 'text-slate-600 dark:text-slate-400',
                'bg_soft' => 'bg-slate-50 dark:bg-slate-800/50',
                'glow' => 'shadow-md',
                'ring' => 'ring-1 ring-slate-300 dark:ring-slate-600',
            ],
        };
    }

    /**
     * 📊 Data completeness — % ของ field ที่มีข้อมูล (0-100)
     */
    public function getDataCompleteness(): int
    {
        $score = 0;
        $max = 8;

        $score += ! empty($this->display_name) ? 1 : 0;
        $score += ! empty($this->demographics) && count(array_filter($this->demographics, fn ($v) => $v && $v !== 'unknown')) > 0 ? 1 : 0;
        $score += ! empty($this->traits) ? 1 : 0;
        $score += ! empty($this->likes) ? 1 : 0;
        $score += ! empty($this->dislikes) ? 1 : 0;
        $score += ! empty($this->conversation_themes) ? 1 : 0;
        $score += ! empty($this->communication_style) ? 1 : 0;
        $score += ! empty($this->topic_tags) ? 1 : 0;

        return (int) round(($score / $max) * 100);
    }

    /**
     * 📡 Radar chart stats (6 axes, 0-100)
     *
     * Map communication_style + traits/conversation_themes counts → numeric scores
     */
    public function getRadarStats(): array
    {
        $style = $this->communication_style ?? [];

        // Formality: formal → 100, casual → 30, mixed → 60
        $formality = match (strtolower($style['formality'] ?? '')) {
            'formal', 'high' => 90,
            'semi-formal', 'semi_formal', 'medium' => 65,
            'casual', 'low' => 30,
            default => 50,
        };

        // Tone warmth
        $tone = match (strtolower($style['tone'] ?? '')) {
            'warm', 'friendly', 'caring' => 90,
            'positive', 'cheerful' => 75,
            'neutral' => 50,
            'reserved', 'serious' => 35,
            'cold', 'distant' => 20,
            default => 55,
        };

        // Emoji usage
        $emoji = match (strtolower($style['emoji_usage'] ?? '')) {
            'high', 'heavy' => 90,
            'medium', 'moderate' => 60,
            'low', 'rare' => 30,
            'none', 'never' => 10,
            default => 45,
        };

        // Verbosity — derive from message length hint or conversation themes count
        $verbosity = match (strtolower($style['verbosity'] ?? '')) {
            'verbose', 'long', 'high' => 85,
            'medium', 'moderate' => 55,
            'concise', 'short', 'low' => 25,
            default => min(80, 30 + count($this->conversation_themes ?? []) * 6),
        };

        // Politeness
        $politeness = match (strtolower($style['politeness'] ?? '')) {
            'very_polite', 'high' => 90,
            'polite', 'medium' => 65,
            'casual', 'low' => 35,
            'rude' => 15,
            default => 60,
        };

        // Engagement — derive from observation_count + likes count
        $obsCount = (int) ($this->observation_count ?? 0);
        $likesCount = count($this->likes ?? []);
        $engagement = min(100, 20 + ($obsCount * 2) + ($likesCount * 5));

        return [
            ['axis' => 'ทางการ', 'value' => $formality],
            ['axis' => 'อบอุ่น', 'value' => $tone],
            ['axis' => 'อีโมจิ', 'value' => $emoji],
            ['axis' => 'ช่างคุย', 'value' => $verbosity],
            ['axis' => 'สุภาพ', 'value' => $politeness],
            ['axis' => 'มีส่วนร่วม', 'value' => $engagement],
        ];
    }

    /**
     * 📊 Stat bars — count vs cap (30) for each list field
     */
    public function getStatBars(): array
    {
        $cap = 30;

        return [
            'traits' => [
                'label' => '🎭 บุคลิก',
                'count' => count($this->traits ?? []),
                'cap' => $cap,
                'color' => 'from-rose-500 to-pink-500',
                'percent' => min(100, (int) round((count($this->traits ?? []) / $cap) * 100)),
            ],
            'likes' => [
                'label' => '❤️ สิ่งที่ชอบ',
                'count' => count($this->likes ?? []),
                'cap' => $cap,
                'color' => 'from-emerald-500 to-green-500',
                'percent' => min(100, (int) round((count($this->likes ?? []) / $cap) * 100)),
            ],
            'dislikes' => [
                'label' => '❌ สิ่งที่ไม่ชอบ',
                'count' => count($this->dislikes ?? []),
                'cap' => $cap,
                'color' => 'from-red-500 to-orange-500',
                'percent' => min(100, (int) round((count($this->dislikes ?? []) / $cap) * 100)),
            ],
            'themes' => [
                'label' => '💬 หัวข้อที่คุย',
                'count' => count($this->conversation_themes ?? []),
                'cap' => $cap,
                'color' => 'from-sky-500 to-blue-500',
                'percent' => min(100, (int) round((count($this->conversation_themes ?? []) / $cap) * 100)),
            ],
        ];
    }

    /**
     * 🏷️ Topic tags as array (parsed)
     */
    public function getTopicTagsArrayAttribute(): array
    {
        if (empty($this->topic_tags)) {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $this->topic_tags))));
    }

    /**
     * 🎨 Platform display config
     */
    public function getPlatformConfig(): array
    {
        return match ($this->platform) {
            'line' => [
                'icon' => '🟢',
                'label' => 'LINE',
                'color' => 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300',
            ],
            'facebook' => [
                'icon' => '📘',
                'label' => 'Facebook',
                'color' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300',
            ],
            default => [
                'icon' => '🌐',
                'label' => ucfirst($this->platform ?? 'unknown'),
                'color' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
            ],
        };
    }

    /**
     * 🎴 Initials สำหรับ avatar (2 chars)
     */
    public function getInitialsAttribute(): string
    {
        $name = trim($this->display_name ?? '');
        if (empty($name)) {
            return '👤';
        }

        $parts = preg_split('/\s+/', $name);
        if (count($parts) >= 2) {
            return mb_substr($parts[0], 0, 1) . mb_substr($parts[1], 0, 1);
        }

        return mb_substr($name, 0, 2);
    }

    /**
     * 📝 Export เป็น Markdown (ObsidianX-ready)
     *
     * Format: YAML frontmatter + sections
     * พร้อม import เข้า ObsidianX vault ได้เลย
     */
    public function toObsidianMarkdown(): string
    {
        $name = $this->display_name ?? "Customer {$this->platform_user_id}";
        $tagsList = $this->topic_tags
            ? array_map(fn ($t) => trim($t), explode(',', $this->topic_tags))
            : [];
        $tagsYaml = empty($tagsList)
            ? '[]'
            : "\n  - " . implode("\n  - ", $tagsList);

        $md = "---\n";
        $md .= "title: \"{$name}\"\n";
        $md .= "platform: {$this->platform}\n";
        $md .= "user_id: {$this->platform_user_id}\n";
        $md .= 'observation_count: ' . $this->observation_count . "\n";
        $md .= 'last_observed: ' . ($this->last_observed_at?->toIso8601String() ?? '-') . "\n";
        $md .= "tags:{$tagsYaml}\n";
        $md .= "---\n\n";
        $md .= "# {$name}\n\n";

        $demo = $this->demographics ?? [];
        if (! empty($demo)) {
            $md .= "## 👥 Demographics\n";
            foreach ($demo as $k => $v) {
                if ($v && $v !== 'unknown') {
                    $md .= "- **{$k}**: {$v}\n";
                }
            }
            $md .= "\n";
        }

        if (! empty($this->traits)) {
            $md .= "## 🎭 Traits\n";
            foreach ($this->traits as $t) {
                $md .= "- {$t}\n";
            }
            $md .= "\n";
        }

        if (! empty($this->likes)) {
            $md .= "## ❤️ Likes\n";
            foreach ($this->likes as $t) {
                $md .= "- {$t}\n";
            }
            $md .= "\n";
        }

        if (! empty($this->dislikes)) {
            $md .= "## ❌ Dislikes\n";
            foreach ($this->dislikes as $t) {
                $md .= "- {$t}\n";
            }
            $md .= "\n";
        }

        if (! empty($this->conversation_themes)) {
            $md .= "## 💬 Conversation Themes\n";
            foreach ($this->conversation_themes as $t) {
                $md .= "- {$t}\n";
            }
            $md .= "\n";
        }

        $style = $this->communication_style ?? [];
        if (! empty($style)) {
            $md .= "## 🗣️ Communication Style\n";
            foreach ($style as $k => $v) {
                $md .= "- **{$k}**: {$v}\n";
            }
            $md .= "\n";
        }

        return $md;
    }
}
