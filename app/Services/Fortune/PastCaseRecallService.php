<?php

namespace App\Services\Fortune;

use App\Models\FortuneCelticQuestion;
use App\Models\FortuneReading;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 📚 PastCaseRecallService — "ลูกค้าถามถึงเคสเก่า → ค้นมาอ้างอิง"
 *
 * ปัญหาเดิม (พบจากเคส FTU-260831-W5209 / 2026-08-31):
 *   ลูกค้าคนเดียวซื้อ Celtic 99 ไป 8 ใบ เรื่องคดีความเรื่องเดียวกันยาว 3 เดือน
 *   ในฐานมีคำถาม-คำตอบเก่า 23 แถว + บทสรุป 5,000-7,700 ตัวอักษร
 *   แต่บอท **ไม่เคยอ่านข้ามบิลเลย**:
 *     1. `fortune_readings.questions` ของบิล Celtic ว่างทุกใบ → ทุกจุดที่ดึง "เรื่อง"
 *        จาก questions[0] ได้ค่าว่าง → prompt เขียนว่า "ไม่ระบุเรื่อง"
 *     2. คำถามจริงอยู่ที่ `fortune_celtic_questions.question` — ไม่มีโค้ดไหนอ่านข้ามบิล
 *        (ทุก call site scope `$reading->celticQuestions()` = บิลปัจจุบันเท่านั้น)
 *     3. `buildPastReadingsContext` ตัดบทสรุปเหลือ 250 ตัวอักษร และ inject แค่ Q1
 *        (Q2+ return ก่อนถึงบรรทัดนั้น) → ลูกค้าถามถึงของเก่ากลางวง = บอทมืดสนิท
 *
 * บริการนี้เป็น **แหล่งเดียว** ของ 3 อย่าง:
 *   - `resolveTopic()`  — "เรื่อง" จริงของบิลเก่า (อ่านจาก celtic questions ก่อน)
 *   - `buildIndex()`    — ดัชนีเคสเก่าแบบย่อ (เปิดตลอด ~500-700 ตัวอักษร)
 *   - `buildRecallBlock()` — ยกคำทำนายเก่ามาอ้างอิง (เปิดเฉพาะตอนลูกค้าอ้างถึงของเก่า)
 *
 * ⚠️ กฎ: ให้ AI อ้างได้ **เฉพาะข้อความที่อยู่ในบล็อกนี้** — ห้ามแต่งว่าเคยบอกอะไรไว้
 *     (ของเดิมสั่ง AI ว่า "ให้ทำนายต่อยอดจากครั้งก่อนได้ เช่น จากที่หมอจันทราเคยบอกไว้..."
 *      โดยไม่ได้ให้เนื้อหาไปด้วย = เชิญให้มโน)
 */
class PastCaseRecallService
{
    /** จำนวนบิลเก่าที่หยิบมาทำดัชนี */
    private const INDEX_LIMIT = 6;

    /** ความยาว "เรื่อง" ที่แสดงในดัชนี */
    private const TOPIC_CHARS = 70;

    /** ความยาวบรรทัดผลลัพธ์ย่อในดัชนี */
    private const OUTCOME_CHARS = 110;

    /** จำนวนบิลเก่าที่ยกคำทำนายมาอ้างอิงได้สูงสุด */
    private const RECALL_MAX_READINGS = 2;

    /** จำนวนตอน (Q&A / บทสรุป) ที่ยกมาต่อ 1 ครั้ง */
    private const RECALL_MAX_EXCERPTS = 4;

    /** ความยาวต่อ 1 ตอนที่ยกมา */
    private const RECALL_EXCERPT_CHARS = 400;

    /** cache ดัชนี (นาที × วินาที) */
    private const INDEX_CACHE_TTL = 1800;

    /** cache ผลค้นเคสเก่า (ต่อคำถาม) — ลูกค้าพิมพ์ซ้ำ/ระบบรีทราย ไม่ต้องสแกนใหม่ */
    private const RECALL_CACHE_TTL = 600;

    /** บิลที่ยังไม่ `completed` และเพิ่งจ่ายภายในกี่ชั่วโมง = ถือว่า "ยังทำนายอยู่" ห้ามนับเป็นเคสเก่า */
    private const IN_FLIGHT_HOURS = 6;

    /** เพดานจำนวนแถว Q&A ที่ดึงมาให้คะแนนต่อบิล (บิลยาวๆ มี 14+ แถว) */
    private const QA_SCAN_LIMIT = 12;

    /** ขนาด n-gram ที่ใช้วัดความตรงกัน — ลูปด้านล่าง unroll ไว้ที่ 3 ห้ามเปลี่ยนเฉยๆ */
    private const GRAM_SIZE = 3;

    /**
     * คำถามพื้นดวงมาตรฐานที่ระบบยิงเอง — ไม่ใช่ "เรื่อง" ที่ลูกค้าอยากรู้จริง
     */
    private const GENERIC_QUESTION_MARKERS = [
        'ขอดูพื้นดวง',
        'พื้นดวงโดยรวม',
        'พื้นดวงรวม',
        'ช่วยอ่านพื้นดวง',
        'ภาพรวมชีวิตช่วงนี้',
        'ภาพรวมไพ่ทั้ง 10 ใบ',
        // ⚠️ sentinel ภายในที่ถูกเก็บลง `fortune_celtic_questions.question` จริง
        //    ถ้าไม่กรอง จะไปโผล่ในประโยคที่สั่งให้ AI พูด เช่น
        //    "แม่หมอจำได้นะ — 8 วันก่อนเปิดไพ่ให้ เรื่อง \"[IMAGE_ATTACHED]\""
        '__PREDICT_ALL__',
        '[IMAGE_ATTACHED]',
    ];

    /**
     * 🔴 สัญญาณ "แรง" — อ้างถึงการดูดวงครั้งก่อนแบบไม่กำกวม
     *    เจอแล้วดึงประวัติได้เลย ไม่ต้องรอคะแนนความตรง
     */
    private const STRONG_REFERENCE_MARKERS = [
        // อ้างครั้งก่อนตรงๆ
        'ครั้งก่อน', 'ครั้งที่แล้ว', 'ครั้งที่ผ่านมา', 'คราวก่อน', 'คราวที่แล้ว',
        'รอบก่อน', 'รอบที่แล้ว', 'ครั้งแรกที่ดู', 'ที่ดูไปแล้ว',
        // อ้างสิ่งที่แม่หมอเคยพูด
        'ที่เคยบอก', 'ที่บอกไว้', 'ที่เคยถาม', 'ที่ถามไป', 'ที่เคยทำนาย',
        'ที่ทำนายไว้', 'ที่แนะนำไว้', 'ที่เคยแนะนำ', 'เคยดูไว้', 'ตามที่บอก',
        'อย่างที่บอก', 'ที่เคยดู', 'ที่ปรึกษาไว้',
        // ถามความจำ — ⚠️ ต้องเป็น 'ยังจำได้' ไม่ใช่ 'ยังจำ'
        //    'ยังจำ' เป็น substring ของ "ยังจำเป็น" → "หนูยังจำเป็นต้องอยู่กับเขาไหม" จะ fire ผิด
        'จำได้ไหม', 'จำได้มั้ย', 'จำได้รึเปล่า', 'จำได้หรือเปล่า', 'ยังจำได้',
        // อ้างเรื่องเดิม
        'เรื่องเดิม', 'เคสเดิม', 'เคสเก่า', 'เรื่องเก่า', 'เรื่องที่ค้าง',
        'ต่อจากเดิม', 'ต่อเรื่องเดิม', 'เรื่องที่คุยกันไว้',
    ];

    /**
     * 🟡 สัญญาณ "อ่อน" — คำบอกเวลาที่ใช้เล่าเรื่องชีวิตทั่วไปก็ได้
     *    "เมื่อวานหนูทะเลาะกับแฟน" / "ตอนนั้นเขายังดีอยู่" ≠ อ้างถึงคำทำนาย
     *    ⇒ เจอแล้ว **ยังต้องผ่านคะแนนความตรง** (เพดานต่ำกว่าปกติ) ถึงจะดึงประวัติ
     */
    private const WEAK_REFERENCE_MARKERS = [
        'ตอนนั้น', 'เมื่อวาน', 'อาทิตย์ที่แล้ว', 'สัปดาห์ที่แล้ว',
        'อาทิตย์ก่อน', 'สัปดาห์ก่อน', 'เดือนที่แล้ว', 'เดือนก่อน',
    ];

    /** เพดานคะแนนเมื่อไม่มีสัญญาณอะไรเลย */
    private const SCORE_GATE_NONE = 0.45;

    /** เพดานคะแนนเมื่อเจอสัญญาณอ่อน (คำบอกเวลา) */
    private const SCORE_GATE_WEAK = 0.25;

    /** สถานะบิลที่ถือว่า "ทำนายไปแล้ว" — ใช้ค้นย้อนหลังได้ */
    private const RECALLABLE_STATUSES = [
        FortuneReading::STATUS_COMPLETED,
        'celtic_grand_finale',
        'celtic_generating',
    ];

    // ─────────────────────────────────────────────────────────────
    // 1) เรื่องของบิลเก่า
    // ─────────────────────────────────────────────────────────────

    /**
     * 🏷️ หา "เรื่อง" จริงของบิล — แหล่งเดียวสำหรับทุก prompt
     *
     * ลำดับ: คำถาม Celtic แถวแรกที่ไม่ใช่ boilerplate → questions[0] → categories
     *
     * @param  array<int, string>|null  $celticQuestionsByReading  คำถาม Celtic ที่ preload มาแล้ว (กัน N+1)
     */
    public function resolveTopic(FortuneReading $reading, ?array $celticQuestionsByReading = null): string
    {
        try {
            // 1. คำถาม Celtic จริงของลูกค้า (ตัว boilerplate พื้นดวงข้ามไป)
            $celticList = $celticQuestionsByReading !== null
                ? ($celticQuestionsByReading[$reading->id] ?? [])
                : FortuneCelticQuestion::where('fortune_reading_id', $reading->id)
                    ->orderBy('sequence')
                    ->limit(4)
                    ->pluck('question')
                    ->all();

            foreach ($celticList as $q) {
                $q = trim((string) $q);
                if ($q === '' || $this->isGenericQuestion($q)) {
                    continue;
                }

                return mb_substr($q, 0, self::TOPIC_CHARS);
            }

            // 2. questions[] ของ reading (Deep 39 ใช้ช่องนี้)
            $questions = $reading->questions ?? [];
            if (is_array($questions)) {
                foreach ($questions as $q) {
                    $q = trim((string) $q);
                    if ($q === '' || $this->isGenericQuestion($q)) {
                        continue;
                    }

                    return mb_substr($q, 0, self::TOPIC_CHARS);
                }
            }

            // 3. หมวดหมู่ (หยาบสุด แต่ดีกว่า "ไม่ระบุเรื่อง")
            $categories = $reading->categories ?? [];
            if (is_array($categories) && ! empty($categories)) {
                return mb_substr(implode(', ', array_filter($categories)), 0, self::TOPIC_CHARS);
            }

            return '';
        } catch (\Throwable $e) {
            Log::debug('PastCaseRecall: resolveTopic fail (non-blocking)', [
                'reading_id' => $reading->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return '';
        }
    }

    // ─────────────────────────────────────────────────────────────
    // 2) ดัชนีเคสเก่า (เปิดตลอดสำหรับลูกค้าเก่า)
    // ─────────────────────────────────────────────────────────────

    /**
     * 📇 ดัชนีเคสเก่าแบบย่อ — บอก AI ว่า "เคยคุยเรื่องอะไรไว้บ้าง เมื่อไหร่ ผลว่าไง"
     *
     * ตั้งใจให้สั้น (~500-700 ตัวอักษร) เพราะ inject ทุกเทิร์นของลูกค้าเก่า
     * เนื้อคำทำนายเต็มอยู่ที่ `buildRecallBlock()` ซึ่งเปิดเฉพาะตอนจำเป็น
     *
     * @return string ว่างถ้าไม่ใช่ลูกค้าเก่า
     */
    public function buildIndex(FortuneReading $reading): string
    {
        $userId = $this->resolveUserId($reading);
        $readingId = (int) ($reading->id ?? 0);
        // ⚠️ id = 0 (model ที่ยังไม่ save) จะได้ cache key "r0" ซึ่งใช้ร่วมกันข้ามลูกค้า — ห้ามผ่าน
        if ($userId === '' || $readingId <= 0) {
            return '';
        }

        return $this->buildIndexForUser($userId, $readingId);
    }

    /**
     * 📇 เวอร์ชันที่เรียกจากเส้นแชท (ไม่มี FortuneReading ปัจจุบัน)
     *
     * @param  string  $userId  FB PSID / LINE userId
     * @param  int|null  $excludeReadingId  บิลที่กำลังทำอยู่ (ไม่ต้องเอามาเป็น "ของเก่า")
     */
    public function buildIndexForUser(string $userId, ?int $excludeReadingId = null): string
    {
        try {
            $userId = trim($userId);
            if ($userId === '') {
                return '';
            }

            $cacheKey = 'fortune:past_case_index:'.($excludeReadingId !== null ? "r{$excludeReadingId}" : "u{$userId}");
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return $cached === 'EMPTY' ? '' : $cached;
            }

            $past = $this->fetchPastReadings($userId, $excludeReadingId, self::INDEX_LIMIT);
            if ($past->isEmpty()) {
                Cache::put($cacheKey, 'EMPTY', self::INDEX_CACHE_TTL);

                return '';
            }

            $topicMap = $this->preloadCelticQuestions($past->pluck('id')->all());

            $lines = [];
            foreach ($past as $p) {
                $when = $this->formatWhen($p);
                $topic = $this->resolveTopic($p, $topicMap);
                $topicText = $topic !== '' ? "\"{$topic}\"" : '(ไม่ได้ระบุหัวข้อ)';
                $typeLabel = $this->typeLabel($p);
                $outcome = $this->clip($this->recallableText($p), self::OUTCOME_CHARS);

                $line = "• [{$when}] {$typeLabel} — {$topicText}";
                if ($outcome !== '') {
                    $line .= "\n  ผลที่ทำนายไว้: {$outcome}";
                }
                $lines[] = $line;
            }

            $block = "━━━━━━━━━━━━━━━━━\n"
                .'📇 [PAST_CASE_INDEX] เคสเก่าของลูกค้าคนนี้ ('.$past->count()." ครั้งล่าสุด)\n"
                ."━━━━━━━━━━━━━━━━━\n"
                .implode("\n", $lines)."\n"
                ."🎯 ใช้ดัชนีนี้เพื่อ **รู้ว่าเคยคุยอะไรไว้** — ห้ามแต่งรายละเอียดที่ไม่ได้อยู่ในนี้\n"
                ."🎯 ถ้าลูกค้าอ้างถึงเรื่องเก่า ระบบจะส่งคำทำนายฉบับเต็มมาให้ในบล็อก [PAST_CASE_RECALL]\n"
                ."🚨 นี่คือ **ข้อมูลอ้างอิง ไม่ใช่คำตอบ** — คำทำนายรอบนี้อ่านจากไพ่ชุดใหม่ที่เปิดวันนี้เท่านั้น\n"
                ."   ประวัติเก่ามีไว้ **ต่อยอด** ให้เรื่องเดินหน้า ห้ามลอกคำตอบเก่ามาตอบซ้ำ\n\n";

            Cache::put($cacheKey, $block, self::INDEX_CACHE_TTL);

            return $block;
        } catch (\Throwable $e) {
            Log::debug('PastCaseRecall: buildIndex fail (non-blocking)', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return '';
        }
    }

    // ─────────────────────────────────────────────────────────────
    // 3) ค้นเคสเก่ามาอ้างอิง (เปิดเฉพาะตอนลูกค้าอ้างถึง)
    // ─────────────────────────────────────────────────────────────

    /**
     * 🔎 ลูกค้ากำลังอ้างถึงของเก่าหรือเปล่า
     */
    public function mentionsPastCase(?string $text): bool
    {
        return $this->referenceStrength($text) !== 'none';
    }

    /**
     * 🔎 ความแรงของสัญญาณ: 'strong' | 'weak' | 'none'
     *
     * strong = อ้างถึงการดูดวงครั้งก่อนชัดเจน → ดึงประวัติได้เลย
     * weak   = แค่คำบอกเวลา (เมื่อวาน/เดือนก่อน) → ต้องผ่านคะแนนความตรงก่อน
     */
    public function referenceStrength(?string $text): string
    {
        $text = trim((string) $text);
        if ($text === '') {
            return 'none';
        }

        foreach (self::STRONG_REFERENCE_MARKERS as $marker) {
            if (mb_strpos($text, $marker) !== false) {
                return 'strong';
            }
        }

        foreach (self::WEAK_REFERENCE_MARKERS as $marker) {
            if (mb_strpos($text, $marker) !== false) {
                return 'weak';
            }
        }

        // "3 วันก่อน" / "5 วันที่แล้ว" — บอกเวลาเฉยๆ ยังไม่พอ ต้องมีคะแนนหนุน
        return preg_match('/\d+\s*วัน(ก่อน|ที่แล้ว)/u', $text) ? 'weak' : 'none';
    }

    /**
     * 📜 ยกคำทำนายเก่าที่ "ตรงกับที่ลูกค้าถาม" มาให้ AI อ้างอิง
     *
     * เปิดเมื่อ:
     *   - ลูกค้าอ้างถึงของเก่าตรงๆ (mentionsPastCase) — เปิดเสมอ
     *   - หรือคำถามใหม่ทับกับเคสเก่าสูงมาก (≥ 0.45) และยาวพอ (≥ 25 ตัวอักษร)
     *
     * @return string ว่างถ้าไม่เข้าเงื่อนไข / ไม่มีเคสเก่า
     */
    public function buildRecallBlock(FortuneReading $reading, ?string $userQuestion): string
    {
        $userId = $this->resolveUserId($reading);
        $readingId = (int) ($reading->id ?? 0);
        if ($userId === '' || $readingId <= 0) {
            return '';
        }

        return $this->buildRecallBlockForUser($userId, $readingId, $userQuestion);
    }

    /**
     * 📜 เวอร์ชันที่เรียกจากเส้นแชท (ไม่มี FortuneReading ปัจจุบัน)
     *
     * @param  bool  $explicitOnly  true = ทำงานเฉพาะตอนสัญญาณแรง (ไม่แตะ DB ถ้าไม่เข้า)
     *                              เส้นแชทฟรีปริมาณสูงต้องใช้ true — ห้ามยิง query + สแกน n-gram ทุกข้อความ
     * @param  string  $mode  'cards' = มีไพ่ชุดใหม่เปิดอยู่ (Celtic/Deep ที่จ่ายแล้ว) → สั่งให้ทำนายจากไพ่วันนี้
     *                        'chat'  = แชทเปล่า **ไม่มีไพ่** → อ้างอิงได้อย่างเดียว ห้ามเปิดไพ่/ทำนายรอบใหม่
     */
    public function buildRecallBlockForUser(
        string $userId,
        ?int $excludeReadingId,
        ?string $userQuestion,
        bool $explicitOnly = false,
        string $mode = 'cards'
    ): string {
        try {
            $userId = trim($userId);
            $question = trim((string) $userQuestion);
            if ($userId === '' || $question === '') {
                return '';
            }

            $strength = $this->referenceStrength($question);
            $strong = $strength === 'strong';

            // 💸 ตัดทิ้งก่อนแตะ DB — ข้อความสั้น/ไม่มีสัญญาณ ไม่ต้องเสียทั้ง query และ n-gram
            if (! $strong && ($explicitOnly || mb_strlen($question) < 25)) {
                return '';
            }

            // 🗂️ cache ผลลัพธ์ต่อ (ผู้ใช้ + คำถาม) — ลูกค้าพิมพ์ซ้ำ/รีทราย ไม่ต้องสแกนใหม่
            $cacheKey = 'fortune:past_case_recall:'.md5($userId.'|'.$excludeReadingId.'|'.$mode.'|'.$question);
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return $cached === 'EMPTY' ? '' : $cached;
            }

            $past = $this->fetchPastReadings($userId, $excludeReadingId, self::INDEX_LIMIT);
            if ($past->isEmpty()) {
                return '';
            }

            $qGrams = $this->grams(mb_substr($question, 0, 400));
            $dayWindow = $this->parseDayWindow($question);

            // ให้คะแนนแต่ละบิลเก่า: ความทับซ้อนของเนื้อหา + โบนัสช่วงเวลาที่ลูกค้าพูดถึง
            $scored = [];
            foreach ($past as $p) {
                $text = $this->recallableText($p);
                if ($text === '') {
                    continue;
                }

                $score = $this->overlap($qGrams, $text);

                if ($dayWindow !== null) {
                    $days = $this->daysAgo($p);
                    if ($days !== null && $days >= $dayWindow[0] && $days <= $dayWindow[1]) {
                        $score += 0.5; // ลูกค้าระบุช่วงเวลาชัด → ดันบิลในช่วงนั้นขึ้นมา
                    }
                }

                $scored[] = ['reading' => $p, 'score' => $score];
            }

            if (empty($scored)) {
                // ไม่มีบทสรุปให้เทียบคะแนน (บิลเก่าที่ ai_response ว่าง) แต่ลูกค้าอ้างของเก่าชัดเจน
                //   → ยังต้องยกอะไรให้ได้ ใช้บิลล่าสุดไปก่อน ดีกว่าปล่อยบอทตอบว่าจำไม่ได้
                //   ⚠️ เฉพาะสัญญาณ **แรง** เท่านั้น — คำบอกเวลาอย่าง "เมื่อวาน" ห้ามยัดบิลมั่ว
                if (! $strong) {
                    return $this->cacheEmpty($cacheKey);
                }
                $scored = [['reading' => $past->first(), 'score' => 0.0]];
            }

            usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);

            // เพดานคะแนนตามความแรงของสัญญาณ (สัญญาณแรง = ผ่านเลย)
            $gate = match ($strength) {
                'strong' => 0.0,
                'weak' => self::SCORE_GATE_WEAK,
                default => self::SCORE_GATE_NONE,
            };
            if ($scored[0]['score'] < $gate) {
                return $this->cacheEmpty($cacheKey);
            }

            $sections = [];
            foreach (array_slice($scored, 0, self::RECALL_MAX_READINGS) as $entry) {
                $section = $this->buildReadingSection($entry['reading'], $qGrams);
                if ($section !== '') {
                    $sections[] = $section;
                }
            }

            if (empty($sections)) {
                return $this->cacheEmpty($cacheKey);
            }

            // หัวบล็อกต้องพูดตรงกับเหตุผลที่ยิง — ห้ามบอกว่า "ลูกค้าอ้างถึงเรื่องเก่า"
            // ทั้งที่จริงยิงเพราะเนื้อหาทับกันเฉยๆ (AI จะไปกล่าวหาลูกค้าว่าพูดถึงของเก่า)
            $header = $strong
                ? 'ลูกค้าอ้างถึงเรื่องเก่า — นี่คือของจริงที่แม่หมอเคยทำนายไว้'
                : 'เรื่องที่ลูกค้าถามรอบนี้ตรงกับเคสเก่า — นี่คือของจริงที่แม่หมอเคยทำนายไว้';

            $block = "━━━━━━━━━━━━━━━━━\n"
                ."📜 [PAST_CASE_RECALL] {$header}\n"
                ."━━━━━━━━━━━━━━━━━\n"
                .implode("\n", $sections)
                .$this->buildUsageRules($mode);

            Cache::put($cacheKey, $block, self::RECALL_CACHE_TTL);

            return $block;
        } catch (\Throwable $e) {
            Log::debug('PastCaseRecall: buildRecallBlock fail (non-blocking)', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return '';
        }
    }

    // ─────────────────────────────────────────────────────────────
    // Helper — ประกอบเนื้อของบิลเก่า 1 ใบ
    // ─────────────────────────────────────────────────────────────

    /**
     * จำผลว่างไว้ด้วย — กันสแกนซ้ำเมื่อลูกค้าพิมพ์ข้อความเดิม
     */
    private function cacheEmpty(string $cacheKey): string
    {
        Cache::put($cacheKey, 'EMPTY', self::RECALL_CACHE_TTL);

        return '';
    }

    /**
     * 🚨 กฎการใช้ประวัติเก่า — **ต่างกันตามว่ามีไพ่ชุดใหม่อยู่ตรงหน้าหรือเปล่า**
     *
     * ⚠️ เส้นแชทฟรี **ไม่มีไพ่** — ถ้าไปสั่งว่า "ทำนายจากไพ่ชุดใหม่วันนี้" AI จะแต่งชื่อไพ่ขึ้นมาเอง
     *    (ชนกับกำแพง `buildLastReadingRecallContext` ที่สั่งห้ามเปิดไพ่ใหม่ตรงๆ)
     */
    private function buildUsageRules(string $mode): string
    {
        if ($mode === 'chat') {
            return "\n🚨 **สถานะของข้อมูลชุดนี้ = อ้างอิงเท่านั้น**\n"
                ."• ตอนนี้ **ไม่มีไพ่เปิดอยู่** — ห้ามเปิดไพ่ใบใหม่ ห้ามแต่งชื่อไพ่ ห้ามสร้างคำทำนายรอบใหม่\n"
                ."• ทำได้แค่ **ทวนของเดิม** ให้ลูกค้าฟัง + รับฟัง + คุยต่อจากเรื่องที่ค้างไว้\n"
                ."• ถ้าลูกค้าอยากรู้ต่อว่าตอนนี้ดวงเป็นยังไง → ชวนเปิดไพ่รอบใหม่ (ไม่ใช่ทำนายให้เลย)\n\n"
                ."🎯 **วิธีอ้างอิง:**\n"
                ."• บอกให้ชัดว่าเคยพูดอะไรไว้ **เมื่อไหร่** เช่น \"ที่แม่หมอเปิดไพ่ให้เมื่อ 8 วันก่อน บอกไว้ว่า...\"\n"
                ."• ⛔ อ้างได้ **เฉพาะข้อความที่อยู่ในบล็อกนี้เท่านั้น** — ห้ามแต่งเพิ่มว่าเคยบอกอะไรไว้\n"
                ."• ⛔ ถ้าลูกค้าถามถึงเรื่องที่ **ไม่มี**ในบล็อกนี้ → บอกตรงๆ ว่าขอให้ทวนอีกที ห้ามเดา\n\n";
        }

        return "\n🚨 **สถานะของข้อมูลชุดนี้ = อ้างอิงเท่านั้น ไม่ใช่คำตอบ**\n"
            ."• คำทำนายรอบนี้ต้องอ่านจาก **ไพ่ชุดใหม่ที่เปิดในรอบนี้เท่านั้น** — ประวัติข้างบนใช้แค่\n"
            ."  \"รู้ว่าเคยคุยอะไรไว้\" แล้ว **ต่อยอด** ให้เรื่องเดินหน้า\n"
            ."• ⛔ ห้ามลอกคำตอบเก่ามาตอบซ้ำ / ห้ามสรุปโดยอิงไพ่ของบิลเก่า — ไพ่ชุดนั้นจบไปแล้ว\n"
            ."• ✅ ท่าที่ถูก: \"ครั้งก่อนแม่หมอเห็น X — รอบนี้ไพ่ชุดใหม่บอกว่า Y\" (Y ต้องมาจากไพ่รอบนี้)\n\n"
            ."🎯 **วิธีอ้างอิง:**\n"
            ."• บอกให้ชัดว่าเคยพูดอะไรไว้ **เมื่อไหร่** เช่น \"ที่แม่หมอเปิดไพ่ให้เมื่อ 8 วันก่อน บอกไว้ว่า...\"\n"
            ."• ตอบต่อจากของเดิม — ห้ามเริ่มนับหนึ่งใหม่เหมือนไม่เคยคุยกัน\n"
            ."• ⛔ อ้างได้ **เฉพาะข้อความที่อยู่ในบล็อกนี้เท่านั้น** — ห้ามแต่งเพิ่มว่าเคยบอกอะไรไว้\n"
            ."• ⛔ ถ้าลูกค้าถามถึงเรื่องที่ **ไม่มี**ในบล็อกนี้ → บอกตรงๆ ว่าขอให้ทวนอีกที ห้ามเดา\n"
            ."• 🔄 ดวงเปลี่ยนได้ — ถ้ารอบนี้ไพ่ออกต่างจากเดิม = ปกติ ใช้คำว่า \"พลังเปลี่ยน / ดวงคลาย\"\n"
            ."  ห้ามพูดว่า \"ครั้งก่อนผิด\" / \"ทำนายพลาด\"\n\n";
    }

    /**
     * ประกอบ section ของบิลเก่า 1 ใบ: หัวเรื่อง + Q&A ที่ตรงคำถามที่สุด + บทสรุป
     *
     * @param  array<string, bool>  $qGrams
     */
    private function buildReadingSection(FortuneReading $past, array $qGrams): string
    {
        $when = $this->formatWhen($past);
        $typeLabel = $this->typeLabel($past);
        $topic = $this->resolveTopic($past);
        $topicText = $topic !== '' ? " — \"{$topic}\"" : '';

        $excerpts = [];

        // 1. Q&A เก่าที่ทับกับคำถามใหม่มากที่สุด
        $qas = FortuneCelticQuestion::where('fortune_reading_id', $past->id)
            ->whereNotNull('response')
            ->orderBy('sequence')
            ->limit(self::QA_SCAN_LIMIT) // กันบิลยาว 14+ แถวมาลาก n-gram ทั้งกอง
            ->get(['sequence', 'question', 'response']);

        $rankedQa = [];
        foreach ($qas as $qa) {
            $body = trim((string) $qa->response);
            if ($body === '') {
                continue;
            }
            $rankedQa[] = [
                'score' => $this->overlap($qGrams, (string) $qa->question.' '.$body),
                'question' => trim((string) $qa->question),
                'response' => $body,
            ];
        }

        usort($rankedQa, fn ($a, $b) => $b['score'] <=> $a['score']);

        foreach (array_slice($rankedQa, 0, self::RECALL_MAX_EXCERPTS - 1) as $qa) {
            $q = mb_substr($qa['question'], 0, 90);
            $a = $this->clip($qa['response'], self::RECALL_EXCERPT_CHARS);
            $excerpts[] = "  ↳ ลูกค้าถาม: \"{$q}\"\n    แม่หมอตอบไว้: {$a}";
        }

        // 2. บทสรุปฟันธง (ถ้ามี) — ท่อนหัวคือใจความ
        $summary = trim((string) ($past->getConversationState('celtic_grand_finale_summary', '') ?? ''));
        if ($summary === '') {
            $summary = trim((string) ($past->ai_response ?? ''));
        }
        if ($summary !== '') {
            $excerpts[] = '  ↳ บทสรุปที่ให้ไว้: '.$this->clip($summary, self::RECALL_EXCERPT_CHARS);
        }

        if (empty($excerpts)) {
            return '';
        }

        return "\n▸ [{$when}] {$typeLabel}{$topicText}\n".implode("\n", $excerpts)."\n";
    }

    // ─────────────────────────────────────────────────────────────
    // Helper — query
    // ─────────────────────────────────────────────────────────────

    /**
     * ดึงบิลเก่าที่จ่ายเงินแล้ว + ทำนายเสร็จแล้ว
     *
     * ⚠️ ต้องแมตช์ทั้ง `facebook_user_id` และ `platform_user_id`
     *    (LINE ID นั่งอยู่ในคอลัมน์ชื่อ facebook — `fortune_readings` ไม่มี `line_user_id`)
     *
     * @return Collection<int, FortuneReading>
     */
    private function fetchPastReadings(string $userId, ?int $excludeReadingId, int $limit): Collection
    {
        return FortuneReading::where(function ($q) use ($userId) {
            $q->where('facebook_user_id', $userId)
                ->orWhere('platform_user_id', $userId);
        })
            ->when($excludeReadingId !== null, fn ($q) => $q->where('id', '!=', $excludeReadingId))
            ->where('is_paid', true)
            ->whereIn('conversation_status', self::RECALLABLE_STATUSES)
            // 🛡️ กันบิลที่ **ยังทำนายอยู่** หลุดมาเป็น "เคสเก่า" ของตัวเอง
            //    `celtic_generating` ถูกตั้งก่อนเรียก askQuestion() และ `ai_response` ก็มีคำทำนาย
            //    ของรอบนี้อยู่แล้ว ⇒ ถ้าไม่กัน AI จะโดนสั่งว่า "ห้ามอิงไพ่ชุดเก่า" ทั้งที่นั่นคือไพ่วันนี้
            //    (เส้นแชทส่ง excludeReadingId = null จึงพึ่ง where ตัวนี้อย่างเดียว)
            ->where(function ($q) {
                $q->where('conversation_status', FortuneReading::STATUS_COMPLETED)
                    ->orWhere('paid_at', '<', now()->subHours(self::IN_FLIGHT_HOURS));
            })
            ->orderByDesc('paid_at')
            ->limit($limit)
            ->get();
    }

    /**
     * preload คำถาม Celtic ของหลายบิลพร้อมกัน (กัน N+1 ตอนทำดัชนี)
     *
     * @param  array<int, int>  $readingIds
     * @return array<int, array<int, string>>
     */
    private function preloadCelticQuestions(array $readingIds): array
    {
        if (empty($readingIds)) {
            return [];
        }

        $map = [];
        $rows = FortuneCelticQuestion::whereIn('fortune_reading_id', $readingIds)
            ->orderBy('sequence')
            ->get(['fortune_reading_id', 'question']);

        foreach ($rows as $row) {
            $rid = (int) $row->fortune_reading_id;
            if (count($map[$rid] ?? []) >= 4) {
                continue; // พอแค่ 4 แถวแรกต่อบิล
            }
            $map[$rid][] = (string) $row->question;
        }

        return $map;
    }

    /**
     * ข้อความของบิลเก่าที่เอามาให้คะแนนความตรงกับคำถามใหม่ได้
     */
    private function recallableText(FortuneReading $reading): string
    {
        $summary = trim((string) ($reading->getConversationState('celtic_grand_finale_summary', '') ?? ''));
        if ($summary === '') {
            $summary = trim((string) ($reading->ai_response ?? ''));
        }

        return $summary;
    }

    // ─────────────────────────────────────────────────────────────
    // Helper — ข้อความ / เวลา
    // ─────────────────────────────────────────────────────────────

    private function resolveUserId(FortuneReading $reading): string
    {
        return (string) ($reading->facebook_user_id ?? $reading->platform_user_id ?? '');
    }

    private function isGenericQuestion(string $question): bool
    {
        foreach (self::GENERIC_QUESTION_MARKERS as $marker) {
            if (mb_strpos($question, $marker) !== false) {
                return true;
            }
        }

        return false;
    }

    private function typeLabel(FortuneReading $reading): string
    {
        return match ((string) $reading->reading_type) {
            'celtic_cross' => 'ไพ่ 10 ใบ (99฿)',
            'deep' => 'ดูดวงเชิงลึก (39฿)',
            default => 'ดูดวง',
        };
    }

    /**
     * จำนวนวันที่ผ่านมาจากวันที่จ่ายเงิน (null ถ้าไม่มี paid_at)
     */
    private function daysAgo(FortuneReading $reading): ?int
    {
        $anchor = $reading->paid_at ?? $reading->created_at;
        if (! $anchor) {
            return null;
        }

        return (int) max(0, now()->diffInDays($anchor, false) * -1);
    }

    private function formatWhen(FortuneReading $reading): string
    {
        $days = $this->daysAgo($reading);
        if ($days === null) {
            return 'ครั้งก่อน';
        }

        $label = match (true) {
            $days === 0 => 'วันนี้',
            $days === 1 => 'เมื่อวาน',
            default => "{$days} วันก่อน",
        };

        $anchor = $reading->paid_at ?? $reading->created_at;

        return $anchor ? $label.' ('.$anchor->format('j M').')' : $label;
    }

    /**
     * แปลง "เมื่อวาน / อาทิตย์ที่แล้ว / 5 วันก่อน" เป็นช่วงวัน [ต่ำสุด, สูงสุด]
     *
     * @return array{0: int, 1: int}|null
     */
    private function parseDayWindow(string $text): ?array
    {
        if (preg_match('/(\d+)\s*วัน(?:ก่อน|ที่แล้ว)/u', $text, $m)) {
            $n = (int) $m[1];

            return [max(0, $n - 2), $n + 2];
        }

        if (mb_strpos($text, 'เมื่อวาน') !== false) {
            return [0, 2];
        }

        foreach (['อาทิตย์ที่แล้ว', 'สัปดาห์ที่แล้ว', 'อาทิตย์ก่อน', 'สัปดาห์ก่อน'] as $marker) {
            if (mb_strpos($text, $marker) !== false) {
                return [4, 14];
            }
        }

        foreach (['เดือนที่แล้ว', 'เดือนก่อน'] as $marker) {
            if (mb_strpos($text, $marker) !== false) {
                return [20, 45];
            }
        }

        return null;
    }

    /**
     * ตัดข้อความให้สั้น + รวบช่องว่าง/ขึ้นบรรทัด (prompt อ่านง่ายขึ้น + ประหยัด token)
     */
    private function clip(string $text, int $chars): string
    {
        $flat = trim((string) preg_replace('/\s+/u', ' ', $text));
        if ($flat === '') {
            return '';
        }

        return mb_strlen($flat) > $chars ? mb_substr($flat, 0, $chars).'…' : $flat;
    }

    // ─────────────────────────────────────────────────────────────
    // Helper — วัดความตรงกันแบบ n-gram (ภาษาไทยไม่มีช่องว่าง ตัดคำไม่ได้)
    // ─────────────────────────────────────────────────────────────

    /**
     * แตกข้อความเป็น 3-gram
     *
     * ⚠️ ลบเฉพาะ ช่องว่าง / เครื่องหมายวรรคตอน / สัญลักษณ์
     *    **ห้ามลบ \p{M}** — สระ/วรรณยุกต์ไทยเป็น Mark ถ้าลบทิ้ง คำจะเพี้ยนทั้งชุด
     *
     * @return array<string, bool>
     */
    private function grams(string $text): array
    {
        $n = self::GRAM_SIZE;
        $clean = (string) preg_replace('/[\s\p{P}\p{S}]+/u', '', $text);

        if ($clean === '') {
            return [];
        }

        // ⚡ ต้องแตกเป็น array ตัวอักษร **ครั้งเดียว** แล้วค่อยหั่น
        //    ห้ามใช้ mb_substr() ในลูป — มันสแกนตั้งแต่ offset 0 ทุกครั้ง = O(n²)
        //    วัดจริง: ข้อความ 8,000 ตัวอักษร แบบ mb_substr ใช้ 134ms · แบบนี้เหลือหลัก ms
        $chars = preg_split('//u', $clean, -1, PREG_SPLIT_NO_EMPTY);
        if ($chars === false) {
            return [];
        }

        $len = count($chars);
        if ($len < $n) {
            return [$clean => true];
        }

        $out = [];
        $last = $len - $n;
        for ($i = 0; $i <= $last; $i++) {
            $out[$chars[$i].$chars[$i + 1].$chars[$i + 2]] = true;
        }

        return $out;
    }

    /**
     * สัดส่วน gram ของคำถามใหม่ที่ไปโผล่ในข้อความเก่า (0.0 - 1.0)
     *
     * @param  array<string, bool>  $qGrams
     */
    private function overlap(array $qGrams, string $text): float
    {
        if (empty($qGrams) || trim($text) === '') {
            return 0.0;
        }

        $tGrams = $this->grams(mb_substr($text, 0, 8000));
        if (empty($tGrams)) {
            return 0.0;
        }

        return count(array_intersect_key($qGrams, $tGrams)) / count($qGrams);
    }
}
