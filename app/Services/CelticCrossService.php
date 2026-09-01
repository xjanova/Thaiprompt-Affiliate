<?php

namespace App\Services;

use App\Models\FortuneCelticQuestion;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Models\TarotCard;
use App\Services\Fortune\CustomerPersonaService;
use App\Services\Fortune\ThaiAstrologyService;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

/**
 * บริการ orchestrate Celtic Cross Tarot Mode (99 บาท ค่าครู)
 *
 * Flow:
 * 1. pickNextCard() — สุ่มไพ่ใบถัดไปสำหรับ position 1-10
 * 2. askQuestion() — ส่งคำถามให้ AI Pool ทำนาย
 *    - sequence=1 → main prompt (full storytelling)
 *    - sequence=2,3 → followup prompt (no card explain)
 * 3. canAskMore() — เช็คว่าถามต่อได้ไหม (counter + 1hr window)
 *
 * รูป composite delegate ให้ CelticSpreadImageGenerator
 */
class CelticCrossService
{
    // 🎚️ (2026-08-17) นโยบายเลือกโมเดลย้ายไปอยู่ที่ App\Services\Fortune\FortuneModelRouter
    //   (คุณไสย์ → sol เสมอ · คำถามยาก → sol เฉพาะเทิร์น · อื่นๆ → luna)
    //   รวมไว้ที่เดียวเพราะ Deep 39 ใช้กฎชุดเดียวกัน — แยกเขียนเมื่อไหร่เพี้ยนคนละทางแน่

    /** จำนวนครั้งที่ยอมลอง insert แถวคำถามใหม่เมื่อเลข sequence ชนกัน (race 2 เส้นทาง) */
    private const SEQUENCE_INSERT_RETRIES = 4;

    protected FortuneTellingSetting $settings;

    public function __construct(?FortuneTellingSetting $settings = null)
    {
        $this->settings = $settings ?? FortuneTellingSetting::getSettings();
    }

    /**
     * 🔎 (2026-08-31) เป็น error "คีย์ซ้ำ" ของ MySQL หรือเปล่า (SQLSTATE 23000 / errno 1062)
     *
     * เช็คด้วยรหัสมาตรฐาน ไม่ผูกกับชื่อ index — เปลี่ยนชื่อ index ทีหลังก็ยังจับได้
     */
    private function isDuplicateEntryError(\Illuminate\Database\QueryException $e): bool
    {
        return ((string) $e->getCode() === '23000')
            && ((int) ($e->errorInfo[1] ?? 0) === 1062);
    }

    /**
     * 🛡️ (2026-08-31) สร้างแถวคำถามแบบทนการชนเลข sequence — **แหล่งเดียวของทุกเส้นทางลูกค้า**
     *
     * ทำไมต้องมี: `(fortune_reading_id, sequence)` มี unique `fcq_reading_seq_unique`
     *   แต่เลข sequence ถูกคำนวณจาก MAX(sequence) ของ **แถวที่ตอบแล้ว** เท่านั้น
     *   ⇒ แถวที่ AI กำลัง gen อยู่ไม่ดันเลข ⇒ 2 เส้นทางที่ยิงพร้อมกันได้เลขเดียวกัน
     *   ⇒ ตัวหลังชน 1062 → exception หลุดถึง ProcessBufferedCelticMessageJob
     *      → ตอบ `celtic_ai_failed` = "⚠️ เกิดข้อผิดพลาด ลองพิมพ์ใหม่อีกครั้งค่ะ" **ใส่หน้าลูกค้าที่จ่าย 99฿**
     *
     * เคสจริง reading 11920 (2026-08-31 22:18:05) — ระบบ gen พื้นดวงรอบกู้อยู่
     *   ลูกค้าพิมพ์ "การเงินคับ" วินาทีเดียวกัน → insert '11920-1' ซ้ำ → ลูกค้าเห็นข้อความ error
     *
     * ⚠️ `askQuestionAsAdmin` (L~1383) มี retry แบบนี้อยู่ก่อนแล้ว — แต่ไม่เคยถูกยกมาใช้ที่เส้นลูกค้า
     *
     * @return array{0: FortuneCelticQuestion, 1: int}|null [แถวที่สร้าง, sequence จริงที่ใช้] · null = สร้างไม่สำเร็จ
     */
    private function createQuestionRecordSafely(FortuneReading $reading, int $sequence, string $question): ?array
    {
        for ($attempt = 1; $attempt <= self::SEQUENCE_INSERT_RETRIES; $attempt++) {
            try {
                $record = FortuneCelticQuestion::create([
                    'fortune_reading_id' => $reading->id,
                    'sequence' => $sequence,
                    'question' => $question,
                ]);

                return [$record, $sequence];
            } catch (\Illuminate\Database\QueryException $e) {
                if (! $this->isDuplicateEntryError($e)) {
                    throw $e; // error อื่น = ปัญหาจริง ห้ามกลืน
                }

                // เลขชน → ขยับไปเลขว่างถัดไป (นับ **ทุกแถว** ไม่ใช่เฉพาะที่ตอบแล้ว)
                $takenMax = (int) FortuneCelticQuestion::where('fortune_reading_id', $reading->id)->max('sequence');
                $sequence = max($takenMax + 1, $sequence + 1);

                Log::warning('CelticCross: sequence ชนกัน → ขยับเลขแล้วลองใหม่', [
                    'reading_id' => $reading->id,
                    'attempt' => $attempt,
                    'next_sequence' => $sequence,
                ]);
            }
        }

        Log::error('CelticCross: สร้างแถวคำถามไม่สำเร็จ — sequence ชนซ้ำจนครบ retry', [
            'reading_id' => $reading->id,
            'last_sequence' => $sequence,
        ]);

        return null;
    }

    /**
     * 🪬 (2026-08-17) คืน modelOverrides สำหรับโหมดดูคุณไสย์ — ส่งเข้า generateWithRetryAndFallback
     *
     * ⚠️ ต้องส่งเป็นพารามิเตอร์ `modelOverrides` เท่านั้น — **ห้ามไปตั้ง $model บน service ก่อนเรียก**
     *    เพราะ generateWithRetryAndFallbackInner สร้าง $allKeys จาก getAllAvailableKeys() ใหม่
     *    ซึ่งแต่ละ key พก model ของตัวเองมา → ค่าที่ตั้งไว้ก่อนหน้าถูกเขียนทับเงียบๆ
     *    (เจอจริงตอนเทส R8966: log บอก "สลับเป็น sol" แต่คำทำนายออกมาเป็น luna)
     *
     * modelOverrides map เป็น provider => model และถูก apply ทั้ง fallback chain
     * key ของค่ายอื่นยังใช้ model ตัวเองตามเดิม (ชื่อ model ข้ามค่ายไม่ได้) — ปลอดภัยอยู่แล้ว
     *
     * นโยบายโมเดลของ Celtic 99฿:
     *   🪬 โหมดดูคุณไสย์      → sol เสมอ ทุกเทิร์น (Q1 + คุยต่อ + บทสรุป)
     *   🧠 โหมดปกติ + คำถามยาก → sol เฉพาะเทิร์นนั้น
     *   🌙 โหมดปกติ ทั่วไป     → luna (ค่า default ของ key)
     *
     * @param  string|null  $userQuestion  คำถามลูกค้าเทิร์นนี้ (null = ไม่มี เช่น บทสรุปตอนจบ)
     * @return array<string, string>|null null = ใช้โมเดลปกติของ key
     */
    protected function resolveCelticModelOverrides(FortuneReading $reading, ?string $userQuestion = null): ?array
    {
        return app(\App\Services\Fortune\FortuneModelRouter::class)->celticOverrides($reading, $userQuestion);
    }

    /**
     * สุ่มไพ่ใบถัดไปสำหรับ position ที่ยังไม่เลือก
     *
     * @return array ['success' => bool, 'card' => array, 'position' => int, 'message' => str]
     */
    public function pickNextCard(FortuneReading $reading): array
    {
        $position = $reading->getNextCelticPosition();
        if ($position === null) {
            return [
                'success' => false,
                'message' => 'เลือกไพ่ครบ 10 ใบแล้ว',
            ];
        }

        $usedCardIds = $reading->getCelticPickedCardIds();

        try {
            $card = TarotCard::where('is_active', true)
                ->when(! empty($usedCardIds), fn ($q) => $q->whereNotIn('id', $usedCardIds))
                ->inRandomOrder()
                ->first();

            if (! $card) {
                throw new Exception('ไม่พบไพ่ทาโรต์ในระบบ — กรุณา seed tarot_cards table');
            }

            $isReversed = (bool) random_int(0, 1);
            $cardNameTh = $card->getName('th');
            $cardNameEn = $card->getName('en');
            $meaning = $card->getMeaning($isReversed, 'th');
            $imageUrl = $card->image_url;

            $reading->addCelticCard(
                $position,
                $card->id,
                $cardNameTh,
                $cardNameEn,
                $isReversed,
                $meaning,
                $imageUrl
            );

            $positionMeta = FortuneReading::CELTIC_POSITIONS[$position] ?? null;
            $positionLabel = $positionMeta['name'] ?? '?';

            return [
                'success' => true,
                'position' => $position,
                'position_name' => $positionLabel,
                'position_description' => $positionMeta['description'] ?? '',
                'card_id' => $card->id,
                'card_name_th' => $cardNameTh,
                'card_name_en' => $cardNameEn,
                'is_reversed' => $isReversed,
                'meaning' => $meaning,
                'image_url' => $imageUrl,
                'picked_count' => $reading->getCelticPickedCount(),
                'is_complete' => $reading->getCelticPickedCount() >= 10,
            ];
        } catch (\Throwable $e) {
            Log::error('CelticCross: pickNextCard ล้มเหลว', [
                'reading_id' => $reading->id,
                'position' => $position,
                'error' => $e->getMessage(),
            ]);

            // 🛡️ (2026-05-13) Sanitize — ห้าม leak technical error ลงในแชท
            //   user report: "มีการโชว์โค๊ดบางส่วน และ error ออก แชท บัคร้ายแรง"
            return [
                'success' => false,
                'message' => '🃏 ขออภัยค่ะ แม่หมอสุ่มไพ่ไม่สำเร็จในรอบนี้ — ลองพิมพ์ "พร้อม" อีกครั้งนะคะ ✨',
            ];
        }
    }

    /**
     * ส่งคำถามให้ AI Pool ทำนาย
     *
     * @param  string  $userQuestion  คำถามที่ลูกค้าพิมพ์
     * @param  bool  $isAutoBaseChart  true = พื้นดวงเปิดตัว (Q1 auto) — นับ used แต่ "ไม่เริ่มจับเวลา 15 นาที"
     *                                 (owner 2026-06-23: เริ่มจับเวลาที่คำถามจริงข้อแรกของลูกค้า = Q2)
     * @return array ['success' => bool, 'response' => str, 'question_record' => FortuneCelticQuestion, 'message' => str]
     */
    public function askQuestion(FortuneReading $reading, string $userQuestion, bool $isAutoBaseChart = false): array
    {
        $userQuestion = trim($userQuestion);
        if ($userQuestion === '') {
            return ['success' => false, 'message' => 'กรุณาพิมพ์คำถาม'];
        }

        if (! $reading->canAskMoreCeltic()) {
            // 🌙 (2026-05-24) ใช้ dynamic settings — เดิม hardcode "1 ชั่วโมง" ตั้งแต่สเปคเก่า
            //   🌙 (2026-06-07) maxQ=0 = ไม่จำกัดคำถาม → ข้อความต้องไม่โชว์ "(0 คำถาม)"
            //     เมื่อ maxQ=0 canAskMoreCeltic จะ false เฉพาะตอน "หมดเวลา" เท่านั้น (ไม่มี cap จำนวน)
            $maxQ = $this->getMaxQuestions();
            $qaWindow = $this->getQaWindowMinutes();

            $capMsg = $maxQ > 0
                ? "ครบจำนวนคำถามแล้ว ({$maxQ} คำถาม) หรือเลยเวลา {$qaWindow} นาทีค่ะ"
                : "หมดเวลาคุยกับแม่หมอแล้ว (ครบ {$qaWindow} นาที) — ถ้าอยากดูต่อ เปิดไพ่ใหม่ได้เลยค่ะ ✨";

            return ['success' => false, 'message' => $capMsg];
        }

        // 🛡️ (2026-05-21) Sequence จาก records จริง — กัน counter inconsistency
        //   เคสจริง: reading 3201 q_used=2 แต่มี 1 record → customer ถามต่อ sequence=3 (short)
        //            ที่ถูกคือ sequence=2 (เพราะมีแค่ Q1 record จริง)
        //   ใช้ MAX(records.sequence) + 1 → consistent กับข้อมูลจริง
        //   Fallback to counter ถ้า query fail
        try {
            $maxSeq = (int) $reading->celticQuestions()
                ->whereNotNull('answered_at')
                ->max('sequence');
            $sequence = max($maxSeq + 1, 1);
        } catch (\Throwable $e) {
            $sequence = $reading->celtic_questions_used + 1;
        }
        $cards = $reading->getCelticCards();

        if (count($cards) < 10) {
            return ['success' => false, 'message' => 'ต้องเลือกไพ่ครบ 10 ใบก่อน'];
        }

        // 🛑 (2026-05-14) ลบ predict-all sentinel ตาม user spec
        //   "เอาระบบ Q1/Q2/Q3 ออก ใช้ prompt เดียวเท่านั้น"
        //   → ทุก question (ทั้ง initial + followup) → buildFollowupPrompt
        $storedQuestion = mb_substr($userQuestion, 0, 1000);
        $isPredictAll = false; // 🚫 legacy flag — keep for length-check below

        // สร้าง record ใน fortune_celtic_questions ก่อน (เผื่อ AI fail)
        //
        // 🛡️ (2026-08-31) กัน race ที่ทำให้ลูกค้าเห็น "⚠️ เกิดข้อผิดพลาด ลองพิมพ์ใหม่อีกครั้งค่ะ"
        //   $sequence ด้านบนนับจาก MAX(sequence) **เฉพาะแถวที่ answered_at ไม่ null**
        //   ⇒ แถวที่ AI กำลัง gen อยู่ (ยังไม่ answered) **ไม่ดันเลขขึ้น**
        //   ⇒ ถ้ามี 2 เส้นทางยิงพร้อมกัน ทั้งคู่ได้เลขเดียวกัน → ตัวหลังชน unique
        //      `fcq_reading_seq_unique` → SQLSTATE 23000 / 1062 → exception หลุดขึ้นไปถึง
        //      ProcessBufferedCelticMessageJob → ตอบ celtic_ai_failed ใส่หน้าลูกค้าที่จ่ายเงินแล้ว
        //   เคสจริง reading 11920 (2026-08-31 22:18:05): ระบบกำลัง gen พื้นดวงรอบกู้อยู่
        //      ลูกค้าพิมพ์ "การเงินคับ" วินาทีเดียวกัน → insert '11920-1' ซ้ำ → ลูกค้าโดนข้อความ error
        //   วิธีแก้: ชนแล้วขยับไปเลขว่างถัดไป (นับ **ทุกแถว** ไม่ใช่เฉพาะที่ตอบแล้ว) แล้วลองใหม่
        $inserted = $this->createQuestionRecordSafely($reading, $sequence, $storedQuestion);
        if (! $inserted) {
            return ['success' => false, 'message' => 'ระบบกำลังประมวลผลคำถามก่อนหน้าอยู่ค่ะ รอสักครู่นะคะ'];
        }
        [$questionRecord, $sequence] = $inserted;

        try {
            $startTime = microtime(true);

            // 🌟 (2026-06-13) "พื้นดวงเปิดตัว" — gen แบบแบ่งบล็อก (3 calls, context ลีน/บล็อก)
            //   โมเดลเล็ก (gpt-5.4-mini) + prompt บวม ~24k + directive 20 บล็อก = ตามฟอร์ม 9/6 ส่วนไม่ครบ
            //   → เฉพาะรอบแรกหลังได้วันเกิด (flag celtic_base_chart) ยิงทีละกลุ่ม section (lean) แล้วต่อกัน
            //   ⚠️ ต้องเช็ค flag ก่อนเรียก buildFollowupPrompt (ตัวนั้น "กิน" flag ทิ้งที่ L~1416)
            //   ล้มเหลว/ทุก call ว่าง → $response คง null → ตก fallback single-call ด้านล่าง (override 6 ส่วนเดิม)
            $celticPurpose = 'prediction_celtic';
            $response = null;
            $tokensUsed = 0;
            $aiProvider = null;
            $aiModel = null;
            if ($reading->getConversationState('celtic_base_chart')) {
                try {
                    $sectioned = $this->generateBaseChartSectioned($reading, $cards, $userQuestion);
                    if ($sectioned !== null && trim((string) ($sectioned['response'] ?? '')) !== '') {
                        $response = trim($sectioned['response']);
                        $tokensUsed = (int) ($sectioned['tokens_used'] ?? 0);
                        $aiProvider = $sectioned['provider'] ?? null;
                        $aiModel = $sectioned['model'] ?? null;
                        // สำเร็จ → กิน flag (กัน fallback/คำถามถัดไปทำซ้ำ)
                        try {
                            $reading->setConversationState('celtic_base_chart', false);
                        } catch (\Throwable $flagErr) {
                            // non-blocking
                        }
                        Log::info('CelticCross: base-chart sectioned สำเร็จ', [
                            'reading_id' => $reading->id,
                            'response_len' => mb_strlen($response),
                            'tokens' => $tokensUsed,
                        ]);
                    }
                } catch (\Throwable $bcErr) {
                    // ไม่กิน flag → ปล่อย fallback buildFollowupPrompt ใช้ override เดิม (ลูกค้าได้คำทำนายเสมอ)
                    Log::warning('CelticCross: base-chart sectioned ล้มเหลว → fallback single-call', [
                        'reading_id' => $reading->id,
                        'error' => $bcErr->getMessage(),
                    ]);
                }
            }

            // 🆕 (2026-05-14) Single prompt — buildFollowupPrompt ทุก turn (ปกติ + fallback base-chart)
            //   🌙 (2026-05-29) Single-bot: Celtic ใช้ key เดียว prediction_celtic + openai เสมอ
            //     (persona "แม่หมอจันทรา" คงเส้น / การแยกแยะ-ขอข้อมูลเพิ่ม จัดการใน prompt อยู่แล้ว)
            //     sensitive ยังใช้ที่อื่น (Vision/Deep 39/Pro Session) — ไม่แตะ
            if ($response === null) {
                $prompt = $this->buildFollowupPrompt($reading, $userQuestion, $cards, $sequence);

                $preferredProvider = 'openai';
                $aiService = new FortuneAIService($this->settings, $celticPurpose, $preferredProvider);

                // 📚 (2026-06-06) แนบ AdminQA RAG — ตอบเรื่องบริการ/ราคา [TYPE:E] ไม่หักโควต้า (self-gate similarity)
                $prompt = $aiService->injectAdminQARagFewShot(
                    $prompt,
                    $userQuestion,
                    $reading,
                    [
                        \App\Models\FortuneAdminQA::CATEGORY_PRE_PAYMENT,
                        \App\Models\FortuneAdminQA::CATEGORY_PRE_PURCHASE,
                        \App\Models\FortuneAdminQA::CATEGORY_PAYMENT_CONFIRM,
                    ],
                );

                // 🪪 (2026-08-17) ผูก usage log กับใบดูดวงนี้ → คิดต้นทุน AI ต่อ 1 ใบได้
                $aiService->forReading($reading);

                $result = $aiService->generateWithRetryAndFallback(
                    questions: [$prompt],
                    userProfile: null,                  // 🌙 แม่หมอจันทรา ไม่ดูโปรไฟล์ FB — ใช้พลังไพ่ + จิตเจ้าชะตา
                    userPosts: null,
                    promptTemplate: '{questions}',      // 🚫 ไม่ wrap default deep template — Celtic prompt ออกตรงๆ
                    readingType: 'deep',                // ใช้ config deep — AI ต้องตอบยาว
                    birthDate: null,                    // 🌙 ไม่ใช้วันเกิด — แม่หมอใช้พลังจักรวาลล้วงลึกผ่านไพ่
                    userContext: "celtic_cross:{$reading->id}:q{$sequence}",
                    purpose: $celticPurpose,
                    // 🪬 คุณไสย์ → sol เสมอ · 🧠 โหมดปกติ + คำถามยาก → sol เฉพาะเทิร์นนี้
                    modelOverrides: $this->resolveCelticModelOverrides($reading, $userQuestion),
                );

                $response = trim($result['response'] ?? '');
                $tokensUsed = (int) ($result['tokens_used'] ?? 0);
                $aiProvider = $result['provider'] ?? null;
                $aiModel = $result['model'] ?? null;
            }
            $responseTimeMs = (int) round((microtime(true) - $startTime) * 1000);

            // 🌙 (2026-05-29) ลบ Sensitive Mode logging/budget tracking ใน Celtic
            //   เดิม: ถ้า purpose='sensitive' → บันทึก cost + logSensitiveEvent
            //   ตอนนี้ Celtic ใช้ prediction_celtic + openai เสมอ (ดู block ด้านบน) → ไม่มี sensitive turn
            //   (Sensitive Mode สำหรับ Vision/Deep/Pro Session ยังทำงานปกติที่ FortuneConversationService)

            // 🆕 (2026-05-31) Pre-detect TYPE token ก่อน minChars guard
            //   เคส R4474 (FTU-260531-P7895): Q1 ได้ classifier [A/B/C/D] แล้ว — ถ้า AI ตอบ
            //   [TYPE:C] (เช่น ลูกค้าทัก "พร้อม" → แม่หมอชวนถาม ~40 chars) จะสั้นกว่า minChars
            //   Q1=100 → throw "ตอบสั้นเกินไป" ผิด ๆ → ลูกค้าเจอ error
            //   Fix: เฉพาะ TYPE:A (ฟอร์มทำนายเต็ม) ที่ต้องยาว — B/C/D (ชวนถาม/ปลอบ/ฟัง) สั้นได้
            //   (detection เต็ม + strip token ทำที่บล็อกล่าง — นี่แค่ peek เพื่อเลือก threshold)
            $preType = 'A';
            if (preg_match('/TYPE\s*[:：]\s*([A-E])/iu', $response, $ptm)) {
                $preType = strtoupper($ptm[1]);
            }

            // 🛡️ (2026-05-21) Sequence-aware threshold
            //   Q1 (sequence=1) + TYPE:A: form prediction → ต้อง >= 100 chars (9 sections)
            //   Q2+ หรือ non-prediction (B/C/D): chitchat/empathy/ชวนถาม → 30 chars OK
            //   เคสจริง: reading 3201 Q4 ลูกค้าถาม "การงาน" AI ตอบ 68 chars → throw
            //            → ลูกค้าได้ error แทนคำตอบดี ๆ
            //   user spec: Q2+ chat-smart mode allowed short responses
            $minChars = ($sequence === 1 && $preType === 'A') ? 100 : 30;
            if ($response === '' || mb_strlen($response) < $minChars) {
                throw new Exception('AI ตอบกลับสั้นเกินไป ('.mb_strlen($response).' ตัวอักษร, min='.$minChars.')');
            }

            // 🛡️ (2026-05-13) Truncation guard — log warning ถ้าตอบสั้นกว่าคาด
            //   Chat mode: 500-900 chars (free chat)
            //   Predict-all: 2000-3000 chars (admin trigger)
            //   ถ้า response < 200 chars → อาจถูกตัด (chat AI hallucinated short)
            $responseLen = mb_strlen($response);
            $minExpected = $isPredictAll ? 1500 : 200;
            if ($responseLen < $minExpected) {
                Log::warning('CelticCross: response อาจถูก truncate / สั้นเกินคาด', [
                    'reading_id' => $reading->id,
                    'sequence' => $sequence,
                    'response_len' => $responseLen,
                    'min_expected' => $minExpected,
                    'tokens_used' => $tokensUsed,
                    'provider' => $aiProvider,
                    'model' => $aiModel,
                    'is_predict_all' => $isPredictAll,
                ]);
            }

            // 🔚 แยก signal [END_SESSION] ออกจาก response (token ที่ AI ใส่เมื่อพร้อมจบ)
            // Detect ทุกแบบ: [END_SESSION] / [end_session] / [END SESSION] / [จบ]
            $wantsEnd = false;
            if (preg_match('/\[\s*(END[_\s]?SESSION|จบ|END)\s*\]/iu', $response)) {
                $wantsEnd = true;
                $response = trim(preg_replace('/\[\s*(END[_\s]?SESSION|จบ|END)\s*\]/iu', '', $response));
            }

            // 🆕 (2026-05-20 Phase 2) Parse TYPE token — แยก "คำถามทำนาย" vs chitchat/empathy
            //   AI ใส่ token [TYPE:A/B/C/D] ที่ต้น response (ตาม prompt Phase 1):
            //     • TYPE:A = คำถามทำนาย → save + นับ quota
            //     • TYPE:B = empathy/ปลอบใจ → ไม่ save + ไม่นับ
            //     • TYPE:C = chitchat สั้น → ไม่ save + ไม่นับ
            //     • TYPE:D = เล่าเรื่อง/บริบท → ไม่ save + ไม่นับ
            //     • TYPE:E = ถามนอกเรื่องทำนาย — มีคำตอบแอดมิน (AdminQA) → ตอบ / ไม่มี → ดึงกลับทำนาย → ไม่ save + ไม่นับ
            //   Fallback: ถ้าไม่มี token → default 'A' (เพื่อปลอดภัย — ลูกค้าจ่ายเงินต้องได้ Q)
            //
            // 🛡️ (2026-05-20 hotfix v2) Strip TYPE token — bulletproof variant
            //   เคสจริง: ลูกค้าเห็น "[TYPE:A]" / "[TYPE: C]" / "**[TYPE:A]**" / "หมอว่า : [TYPE:C]"
            //   อาจมี zero-width char / fullwidth bracket / nbsp ปนมา → regex เดิมพลาด
            //
            // 2-pass strategy:
            //   Pass 1: normalize — remove zero-width chars + map fullwidth bracket → ASCII
            //   Pass 2: strip ทุก variant ของ TYPE token (bracket optional, ทุกตำแหน่ง)
            //
            // detection ก่อน strip — จับ type จาก response ดิบ
            $responseType = 'A';
            if (preg_match('/TYPE\s*[:：]\s*([A-E])/iu', $response, $tm)) {
                $responseType = strtoupper($tm[1]);
            }

            // Pass 1: remove zero-width chars (U+200B, U+200C, U+200D, U+FEFF, U+00A0 nbsp)
            $response = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}\x{00A0}]/u', '', $response);

            // Pass 2: strip TYPE token ทุกรูปแบบ
            //   - bracket: [ ] หรือ 【 】 หรือ ［ ］ (fullwidth) หรือไม่มี bracket
            //   - markdown wrapper: ** ** หรือ * * หรือ ` ` รอบ token
            //   - colon: : (ASCII) หรือ ： (fullwidth)
            $typeStripPattern = '/[`*]{0,3}[\[\【\［\「]?\s*TYPE\s*[:：]\s*[A-E]\s*[\]\】\］\」]?[`*]{0,3}/iu';
            $response = preg_replace($typeStripPattern, '', $response);

            // Cleanup leftover whitespace + empty lines
            $response = trim(preg_replace('/\n{3,}/', "\n\n", (string) $response));
            $response = trim(preg_replace('/^\s*[:：]\s*/u', '', $response)); // กรณี "หมอว่า :" เหลือ colon ลอย

            // 🔢 (2026-06-05 v2) ดึง "คำถามแนะนำต่อยอด" + strip token ออกจาก response
            //   ⚠️ ต้องทำที่นี่ (จุดเดียวกับ strip [TYPE:X]/[END_SESSION]) — ไม่งั้น token รั่วเข้า
            //   DB (response/ai_response) + bridgeToConversationLog (ป้อนกลับ AI) + redeliver cron ส่งซ้ำ
            //   pullNextQuestions ทน token ผิดรูป (AI ดรอป tag เปิด — เคส 5023) + กันรั่ว 100%
            //   คืนเป็น structured 'next_questions' ให้ caller (trait) แปลงเป็นปุ่มเลข 1️⃣2️⃣
            $nextQuestions = self::pullNextQuestions($response);

            // 🚫 Non-prediction (B/C/D) — ไม่บันทึก row + ไม่ increment counter
            //   user spec 2026-05-20: "นับเป็นคำถามที่ต้องบันทึกคือคำถามที่เราตอบเพื่อทำนายเท่านั้น"
            if ($responseType !== 'A') {
                Log::info('CelticCross: non-prediction response — skip save + counter', [
                    'reading_id' => $reading->id,
                    'attempted_sequence' => $sequence,
                    'response_type' => $responseType,
                    'response_preview' => mb_substr($response, 0, 80),
                    'tokens_used' => $tokensUsed,
                ]);

                // ลบ row ที่สร้างไว้ก่อน AI call (line 142) — ยังไม่ใช่คำถามทำนาย
                $questionRecord->delete();

                // Bridge ยังคงบันทึก chat history (เพื่อให้ chat post-Celtic เห็น context)
                $this->bridgeToConversationLog($reading, 'user', $userQuestion);
                $this->bridgeToConversationLog($reading, 'assistant', $response);

                return [
                    'success' => true,
                    'response' => $response,
                    'question_record' => null,           // ไม่มี record
                    'sequence' => $reading->celtic_questions_used, // counter เดิม ไม่ increment
                    'is_prediction' => false,             // 🆕 flag ให้ caller รู้
                    'response_type' => $responseType,
                    'wants_end' => false,
                ];
            }

            // ✅ TYPE:A — คำถามทำนายจริง: save + increment ตามเดิม
            // อัพเดต question record
            $questionRecord->update([
                'response' => $response,
                'ai_provider' => $aiProvider,
                'ai_model' => $aiModel,
                'ai_tokens_used' => $tokensUsed,
                'ai_response_time_ms' => $responseTimeMs,
                'answered_at' => now(),
            ]);

            // อัพเดต counter ใน reading
            //   🆕 (2026-06-23) พื้นดวงเปิดตัว (isAutoBaseChart) → นับ used แต่ไม่เริ่มจับเวลา QA window
            $reading->refresh();
            $reading->markCelticAnswered($sequence, ! $isAutoBaseChart);

            // 🃏 Celtic บันทึกแยกจาก 39฿ deep:
            //   • Q1 + Q2 + Q3+... → เก็บแยก row ใน fortune_celtic_questions table (มีแล้วด้านบน)
            //   • ai_response = Q1 (preview สำหรับ admin list view) — generic field, ไม่ทับ deep_response
            //   • ห้ามแตะ deep_response/basic_response — schema คนละแบบ (39฿ มีวันเกิด, Celtic มี 10 ใบ)
            //   • reading_type 'celtic_cross' ถูกตั้งจาก setCelticPendingPayment() อยู่แล้ว
            //   • ภาพ 10 ใบ → celtic_summary_image_path (CelticSpreadImageGenerator สร้างหลังเปิดครบ)
            if ($sequence === 1) {
                $reading->update([
                    'ai_response' => $response,
                    'ai_provider' => $aiProvider,
                    'ai_model' => $aiModel,
                    'tokens_used' => $tokensUsed,
                    'responded_at' => now(),
                ]);
            } else {
                // Q2+ → สะสม tokens เผื่อ admin track ต้นทุน
                $reading->update([
                    'tokens_used' => ($reading->tokens_used ?? 0) + $tokensUsed,
                ]);
            }

            Log::info('CelticCross: คำถามสำเร็จ', [
                'reading_id' => $reading->id,
                'sequence' => $sequence,
                'response_len' => mb_strlen($response),
                'tokens' => $tokensUsed,
                'response_time_ms' => $responseTimeMs,
            ]);

            // 🌙 (2026-05-14) Bridge Celtic chat → LineBotConversation
            //   user spec: "หมด 30 นาที กลับไปแชทปกติ (groq) ต้องเห็น Celtic chat ย้อนได้"
            //   → บันทึก user message + assistant response ลง LineBotConversation
            //     เพื่อให้ post-Celtic Groq chat ดึง history ได้ผ่าน getConversationHistoryForAI
            $this->bridgeToConversationLog($reading, 'user', $userQuestion);
            $this->bridgeToConversationLog($reading, 'assistant', $response);

            return [
                'success' => true,
                'response' => $response,
                'question_record' => $questionRecord->fresh(),
                'sequence' => $sequence,
                'questions_used' => $reading->fresh()->celtic_questions_used,
                'is_prediction' => true,                // 🆕 (Phase 2) — TYPE:A = คำถามทำนายจริง
                'response_type' => 'A',                 // 🆕 (Phase 2) — สำหรับ caller log
                'wants_end' => $wantsEnd, // 🔚 AI signal ว่าพร้อมจบ session แล้ว
                'next_questions' => $nextQuestions,     // 🔢 (2026-06-05) คำถามแนะนำต่อยอด → ปุ่มเลข 1️⃣2️⃣
            ];
        } catch (\Throwable $e) {
            // ⚠️ catch Throwable (ไม่ใช่แค่ Exception) — กัน PHP Error/TypeError
            // ทำให้ reading ค้างที่ STATUS_CELTIC_GENERATING ตลอดไป
            Log::error('CelticCross: askQuestion ล้มเหลว', [
                'reading_id' => $reading->id,
                'sequence' => $sequence,
                'error' => $e->getMessage(),
                'trace' => substr($e->getTraceAsString(), 0, 500),
            ]);

            // ลบ record ที่ตอบไม่ได้ ให้ลูกค้าลองใหม่ได้
            try {
                $questionRecord->delete();
            } catch (\Throwable $delErr) {
                // ignore — ไม่ critical
            }

            // 🛡️ (2026-05-13) Sanitize — ห้าม leak technical error (cURL/Gemini quota/Provider name) ลงในแชท
            //   user report: "AI ระบบขัดข้องชั่วคราว ... cURL error 28: Operation timed out ... gemini/sen ..."
            //   นี่ leak ชื่อ provider + ชื่อ key + URL ของ Gemini API → bug ร้ายแรง
            //   แทนด้วย user-friendly message — รายละเอียดยังคงใน Log::error ด้านบน
            return [
                'success' => false,
                'message' => "🌙 ขออภัยค่ะ แม่หมอติดขัดเล็กน้อยในการเชื่อมจิตกับจักรวาลตอนนี้ ✨\n\n"
                    ."⏳ รบกวนเจ้าชะตารอสักครู่แล้วลองพิมพ์คำถามใหม่อีกครั้งนะคะ\n"
                    .'📌 ถ้ายังไม่ได้ พิมพ์ "ขอคุยกับคน" เพื่อให้แอดมินช่วย 🙏',
            ];
        }
    }

    /**
     * 🌟 (2026-06-13) "พื้นดวงเปิดตัว" — gen แบบแบ่งบล็อก (3 calls, context ลีน/บล็อก)
     *
     * ปัญหา: โมเดลเล็ก (gpt-5.4-mini) + prompt บวม ~24k tokens + directive 20 บล็อก
     *        → ตามฟอร์มโครงสร้าง (emoji หัวข้อ) ไม่ครบ/ไม่สม่ำเสมอ (chronic, ไม่ใช่ regression)
     * แก้: ยิง 3 call โฟกัส แต่ละ call ใช้ "context กลางลีน" (persona + card-first + ไพ่ 10 ใบ + ดาวเจ้าชนะ)
     *      — ไม่ยัด directive 20 บล็อก — + สั่งเขียน "เฉพาะกลุ่ม section ที่กำหนด" → โมเดลทำตามฟอร์มแม่น
     *      แล้วต่อ 3 บล็อกเป็นคำทำนายเดียว ส่งเข้า pipeline เดิม (นับ/เก็บ/ส่ง เหมือน single-call)
     *
     * แต่ละ call แนบ "สรุปย่อบล็อกก่อนหน้า" (cap) เพื่อความต่อเนื่อง + กันทวนไพ่ซ้ำ/เนื้อตีกัน
     *
     * @param  array  $cards  ไพ่ 10 ใบ (จาก getCelticCards)
     * @return array|null ['response'=>str, 'tokens_used'=>int, 'provider'=>?str, 'model'=>?str] | null ถ้าทุก call ว่าง
     */
    protected function generateBaseChartSectioned(FortuneReading $reading, array $cards, string $userQuestion): ?array
    {
        $brandName = $this->settings->fortune_brand_name ?: 'แม่หมอจันทรา';
        $cardsText = $this->formatCardsForPrompt($cards);

        // 🌟 ดาวเจ้าชนะ — ใช้จุดกลางร่วมกับ buildFollowupPrompt + buildGrandFinalePrompt
        $birthAstroBlock = $this->buildBirthAstrologyBlockFor($reading, $userQuestion);

        // 👤 persona-lite (เบา — ไม่ใช่ directive 20 บล็อก)
        $personaBlock = '';
        try {
            // 🧭 (2026-09-01) LINE id อยู่ในคอลัมน์ facebook_user_id (ไม่มีคอลัมน์ line_user_id จริง)
            //   ⇒ ห้ามเดา platform จาก !empty(facebook_user_id) — ใช้คอลัมน์ platform + U-regex fallback
            $uid = (string) ($reading->facebook_user_id ?: $reading->platform_user_id ?: '');
            $platform = $reading->platform
                ?: (preg_match('/^U[0-9a-f]{32}$/i', $uid) ? 'line' : 'facebook');
            if ($uid !== '') {
                $personaBlock = (string) app(\App\Services\Fortune\CustomerPersonaService::class)
                    // withPastRecall=false → เส้นนี้เป็น "พื้นดวงเปิดตัว" คำถามเป็น boilerplate ของระบบ
                    //   ไม่ใช่ข้อความลูกค้า ⇒ ไม่มีอะไรให้จับคู่กับเคสเก่า (เลี่ยงจ่าย token เปล่า)
                    ->buildInjectBlock($platform, $uid, $userQuestion, false);
            }
        } catch (\Throwable $e) {
            // skip
        }

        // 🧩 context กลางลีน (ใช้ซ้ำทุก call) — persona + card-first + ไพ่ + ดาวเจ้าชนะ
        $shared = ($personaBlock !== '' ? $personaBlock."\n\n" : '')
            ."คุณคือ \"{$brandName}\" — แม่หมอเซียนไพ่เซลติก กำลังอ่าน \"พื้นดวงเปิดตัว\" ครั้งแรกให้เจ้าชะตา (เพิ่งได้วันเกิด)\n"
            ."ผสาน \"ดวงดาว (หลักเจ้าชนะจากวันเกิด)\" + \"ไพ่ทั้ง 10 ใบ\" เป็นเรื่องเดียวกัน — ดาวยืนยัน/เสริมสิ่งที่ไพ่บอก\n\n"
            .$this->buildCardFirstMandate()
            .$this->buildCurrentDateContext()
            .$this->buildCardTalkPolicy(null)
            .$this->buildCardNamingDirective()
            ."━━━━━━━━━━━━━━━━━\n🃏 ไพ่ 10 ใบ (Celtic Cross) ของเจ้าชะตา:\n━━━━━━━━━━━━━━━━━\n".$cardsText."\n\n"
            .($birthAstroBlock !== ''
                ? "━━━━━━━━━━━━━━━━━\n🌟 ดวงดาวจากวันเกิด (หลักเจ้าชนะ)\n━━━━━━━━━━━━━━━━━\n".$birthAstroBlock."\n\n"
                : '');

        // 🪬 (2026-06-26) ถ้าเปิด "โหมดดูคุณไสย์" — ทั้งรอบพื้นดวงเจาะเรื่องของ/มนต์ดำ/สิ่งลี้ลับ ไม่ทำดวงทั่วไป
        //   (owner spec: พื้นดวงต้องพูดแต่เรื่องคุณไสย์ + ใช้ดาวเจ้าชนะคำนวณทิศ/ฤกษ์สะเดาะเคราะห์)
        $bmForced = $this->isBlackMagicModeForced($reading);
        if ($bmForced) {
            $shared .= "🪬 *รอบนี้คือ \"พื้นดวงเปิดตัว โหมดดูคุณไสย์/มนต์ดำ/สิ่งลี้ลับ\" เต็มรูปแบบ* — เจ้าชะตาเลือกมาเพื่อรู้ว่า \"ตกลงโดนของ/คุณไสย์อะไรหรือไม่\"\n"
                ."ล็อกพลังไพ่ทั้ง 10 ใบทะลุเรื่องของ/คุณไสย์โดยเฉพาะทั้งรอบ — ❌ ห้ามทำนาย รัก/งาน/เงิน/สุขภาพ แบบดวงทั่วไป ; ใช้ดวงดาวจากวันเกิดเพื่อคำนวณ \"ทิศ/ฤกษ์สะเดาะเคราะห์\" เป็นหลัก\n\n";
        }

        //   🧭 (2026-06-18) คลังสัญญาณรายไพ่ (โดนของ/สุขภาพ/ไพ่คู่) — *ไม่* ใส่ใน $shared
        //   (รีวิว: ใส่ shared = บวมทุก 4 call ~+46%/call = regression ที่ sectioning สร้างมาแก้)
        //   → ฉีดเฉพาะ group ที่ใช้จริงผ่าน key 'signal' (g0=ครบ, g2=สุขภาพ, g3=เตือน, g1=ไม่ใส่)

        // 🪬 (2026-06-26) โหมดดูคุณไสย์ → สลับโครงพื้นดวงทั้งหมดเป็น "วินิจฉัยของ/มนต์ดำ" 3 ส่วน
        //   (แทน รัก-งาน-เงิน-สุขภาพ) — โดนอะไร/ใครทำ/ความรุนแรง → ทางแก้ทำเอง+ทิศสะเดาะเคราะห์+ครูบา
        if ($bmForced) {
            $groups = [
                [
                    'label' => 'bm-g0-verdict',
                    'len' => '450-750',
                    'must' => ['🪬'],
                    'signal' => ['black_magic', 'combo'], // ฟันธงต้องเห็นสัญญาณไสยศาสตร์+ไพ่คู่อันตราย
                    'spec' => "เขียน \"คำวินิจฉัยเปิดตัว\" — ฟันธงทันทีว่าเจ้าชะตา \"โดนของ/คุณไสย์จริงไหม\" (ส่วนแรกสุด สั้น คม ชัด):\n"
                        ."🪬 ผลอ่านพลังไพ่ทะลุของ\n"
                        ."• สแกนไพ่ทั้ง 10 ใบ + คลังสัญญาณไสยศาสตร์ด้านบน → สรุปตรงๆ ว่า *โดนของจริงไหม* (อิงหน้าไพ่ × ตำแหน่ง Celtic — ไสยศาสตร์ไทย ไม่ใช่สากล)\n"
                        ."• ✅ ถ้าไพ่ชี้ว่าโดนจริง → ฟันธง \"โดนชนิดไหน\" (เสน่ห์ยาแฝด/ของกิน/ของฝัง/อาคม-ลงเลขยันต์/วิญญาณ-ผีพราย/ตะปูผี ฯลฯ) + ความรุนแรง (หนัก/กลาง/เบา)\n"
                        ."• 🛟 ถ้าไพ่ \"ไม่ชี้ว่าโดนของ\" → บอกตรงๆ ว่า \"ไพ่ไม่ได้บอกว่าโดนของนะคะ\" ❌ ห้ามปั้นให้โดน + ให้กำลังใจหนักแน่น (ดูแลกาย-ใจ-คนรอบตัว)\n"
                        ."• ⚠️ เกณฑ์อันตราย: ไพ่ชี้หนักถึงขั้นเสี่ยงเสียสติ/ถึงชีวิต/คิดทำร้ายตัวเอง → เตือนตรงอย่างมีสติ + เร่งพึ่งพระ/แพทย์ + สายด่วนใจ 1323 (มีคนรับฟัง)\n"
                        ."🧠 ลูกค้าเปราะบาง/วิตกง่าย → ฟันธงตามไพ่ได้ แต่เน้นดึงสติ ไม่ขยี้ความกลัว ไม่ขายของแพง\n"
                        .'❌ ห้ามปิดท้ายส่วนนี้ด้วย เคล็ด/คาถา/ทิศ/ธรรมะ — เก็บไว้ส่วนทางแก้ด้านล่าง',
                ],
                [
                    'label' => 'bm-g1-diagnosis',
                    'len' => '600-900',
                    // 🎯 (2026-08-17) เหลือหัวข้อ "หลัก" ของบล็อกตัวเดียว — 👤/📅/🛡️/⚖️ เป็นหัวข้อย่อย
                    //   ถ้าบังคับย่อยด้วย = ตกเกณฑ์แล้วถอยไปแบ่งบล็อกทุกครั้ง ทั้งที่เนื้อหาครบ
                    'must' => ['🔍'],
                    'signal' => ['black_magic', 'combo'],
                    'spec' => "เจาะรายละเอียด \"ตกลงโดนอะไร ใครทำ เพราะอะไร\" (อ่านตามหน้าไพ่ × ตำแหน่ง Celtic เท่านั้น — ไม่ทำพาดหัวซ้ำ):\n"
                        ."🔍 ชนิดของ — ระบุให้ชัดตามไพ่ (อย่ากั๊ก)\n"
                        ."👤 ผู้น่าสงสัย — บอก \"ลักษณะ\" เท่านั้น (เพศ/ความสัมพันธ์ คนใกล้ตัว-คู่แข่ง-อดีตคนรัก-เพื่อนงาน / ช่วงวัย / ทิศที่มา) ❌ ห้ามมั่วชื่อจริง → ชวนเจ้าชะตายืนยัน \"คนลักษณะนี้คิดออกไหมว่าใคร\"\n"
                        ."📅 เมื่อไหร่ + 🧭 มาทางไหน + 💔 มูลเหตุ (อิจฉาริษยา/ชู้สาว-หึงหวง/ผลประโยชน์-ธุรกิจ/แค้นเก่า)\n"
                        ."🛡️ เกราะป้องกัน/สิ่งศักดิ์สิทธิ์คุ้มครอง (อ่านเมื่อไพ่ชี้เท่านั้น) — ไพ่บวกแรง/แสงสว่าง-เทวดา → บอกได้ว่า \"ของเข้าไม่เต็ม/คลายเอง/มีสิ่งคุ้มครอง\" ❌ ไพ่ไม่ชี้ = ไม่ปั้น\n"
                        ."⚖️ กรรมย้อนผู้ทำไหม — ตอบตามไพ่จริง ❌ ห้ามปั้นแช่งให้สะใจ ชี้ให้เจ้าชะตาโฟกัส \"ป้องกัน+แก้ที่ตัวเอง\"\n"
                        .'⚠️ อ่านตามไพ่จริงเท่านั้น — ปั้นว่าโดนทั้งที่ไพ่ไม่ชี้ = ทำให้ลูกค้าหลอน/ระแวง (ผิดจรรยาบรรณร้ายแรง อาจมีคนรู้จริงมาลองของ)',
                ],
                [
                    'label' => 'bm-g2-remedy',
                    'len' => '600-900',
                    'must' => ['🙏'], // 🧭/🛕/📿 เป็นหัวข้อย่อย — ดูเหตุผลที่ bm-g1
                    'signal' => ['black_magic'],
                    'spec' => "สรุป \"ทางแก้ที่ทำได้จริง ไม่เปลืองเงิน ไม่งมงาย\" (เน้นทำเองก่อน • อ้างหลักครูบาอาจารย์/พระเกจิที่มีจริง):\n"
                        ."🙏 ทำเองที่บ้านก่อน — สวดมนต์ (อิติปิโส / พาหุงมหากา / คาถาที่เป็นที่รู้จักจริง) / แผ่เมตตา / ทำบุญอุทิศ / รักษาศีล / สมาธิ / น้ำมนต์\n"
                        ."🧭 ทิศสะเดาะเคราะห์ทำเอง — หันหน้าไป \"ทิศมงคล/ทิศเทวดาประจำวันเกิด\" ของเจ้าชะตา (คำนวณจากดาวเจ้าชนะด้านบน) ตอนสวด/ลอยเคราะห์/ตักบาตร (ยังไม่รู้วันเกิด → ขอก่อน)\n"
                        ."🛕 ถ้าหนักจริง — บวช/อยู่วัดปฏิบัติธรรม 3/7/9/15 วัน ตามความหนัก + เลือก \"ทิศวัด\" ที่ถูกโฉลกกับวันเกิด → ค่อยพึ่งพระ/ผู้รู้ที่ไว้ใจได้\n"
                        ."📿 อ้างอิงแนวทางตามตำราครูบาอาจารย์/พระเกจิสายไสยศาสตร์ไทยที่ \"มีอยู่จริง\" (ถ้าคลังตำราด้านบนมี → ยึดตามนั้น) — ⚠️ ❌ ห้ามมั่วชื่อหลวงพ่อ/สำนัก/เลขคาถาที่ไม่มีจริง ; ไม่แน่ใจ → แนะแนวทางกลางที่ถูกต้อง\n"
                        ."❌ ห้ามแนะพิธีแพง/สะเดาะเคราะห์ด่วนเป็นหมื่น ❌ ห้ามชี้ร้าน-อาจารย์เจาะจงเพื่อหากิน\n"
                        .'ปิดท้าย 1 บรรทัดสั้น: ชวนเจ้าชะตาถามเจาะเรื่องของ/การแก้ต่อได้เลย (❌ ไม่ใส่ รัก/งาน/เงิน/สุขภาพ ทั่วไป ❌ ไม่ใส่ ฤกษ์/สีมงคล/เลขมงคลทั่วไป)',
                ],
            ];
        } else {
            // 📐 4 กลุ่ม section: g0 พาดหัวเรื่องเด่น (ฟันธง) + g1-g3 (6 ส่วนพื้นดวงเปิดตัว)
            //   🎯 (2026-06-18) g0 มาก่อน — ชี้ "เรื่องเด่นที่สุด" ของสำรับเป็นเรื่องแรก ตาม user directive

            // 🕉️ (2026-06-27 owner) "องค์เทพ/สิ่งศักดิ์สิทธิ์คุ้มครอง-บูชาประจำตัว" — เพิ่มเป็นบล็อกสุดท้ายใน g2
            //   ระวังพร๊อมบวม: ฝังในคอลเดิม (g2) ไม่ยิง AI call เพิ่ม + signal ดึงเฉพาะ patron_deity (card-gated)
            //   gate เดิม enable_celtic_mu_knowledge → ปิด = ไม่ฉีดทั้ง knowledge และคำสั่ง (กันโมเดลมั่วชื่อองค์/คาถา)
            $deityEnabled = (bool) ($this->settings->enable_celtic_mu_knowledge ?? true);
            $g2Signal = $deityEnabled ? ['health', 'love', 'patron_deity'] : ['health', 'love'];
            $g2DeitySpec = $deityEnabled
                ? "🕉️ องค์เทพ/สิ่งศักดิ์สิทธิ์คุ้มครอง-บูชาประจำตัว (บล็อกสุดท้าย • อ่านจาก \"คลังองค์เทพรายไพ่\" ด้านบน):\n"
                    ."• สรุป *องค์เดียว* ที่เด่นสุดเป็น \"องค์อุปถัมภ์/คุ้มครองประจำตัว\" ของเจ้าชะตา — มีเสมอ ❌ ห้ามตอบ \"ไม่มี/แล้วแต่ศรัทธา\"\n"
                    ."• บอกระดับองค์ (มหาเทพ / เทพชั้นรอง / ครูบา-สิ่งศักดิ์สิทธิ์สายไทย) + คุ้มครอง-หนุนดวงด้านไหนตามหน้าไพ่\n"
                    ."• บูชาเปิดดวงอย่างไร — ของบูชา + วันไหว้ + คาถา/บทสวด *เฉพาะองค์นั้น* (ดึงจากคลัง) ❌ ห้ามมั่วชื่อองค์/คาถาที่ไม่มีจริง ไม่แน่ใจ→แนะแนวทางกลางที่ถูกต้อง\n"
                    ."• ✅ บูชาเองที่บ้านได้ ไม่เสียเงินก้อน — ❌ ห้ามชวนรับขันธ์/ครอบครู/ทำพิธีแพง\n"
                : '';

            $groups = [
                [
                    'label' => 'g0-headline',
                    'len' => '400-700',
                    'must' => ['🎯'], // 🆕 (2026-08-17) หัวข้อบังคับ — ใช้ตรวจผลของโหมดคอลเดียว
                    'signal' => ['black_magic', 'health', 'combo'], // headline ต้องสแกนสัญญาณครบทุกหมวด
                    'spec' => "เขียน \"พาดหัวเรื่องเด่นที่สุดของรอบนี้\" — เปิดมาฟันธงทันที (นี่คือส่วนแรกสุด สั้น คม ชัด):\n"
                        ."🎯 เรื่องเด่นรอบนี้\n"
                        ."• สแกนไพ่ทั้ง 10 ใบแบบองค์รวม + คลังสัญญาณด้านบน → ชี้ \"สิ่งที่ไพ่ส่งเสียงดังที่สุด\" เพียง *1 เรื่อง* (ดีหรือร้ายก็ได้)\n"
                        ."   ตัวอย่างเรื่องเด่น: โชคลาภ-เงินก้อนเข้า-ถูกหวย • เกณฑ์เลื่อนขั้น-ได้งาน • เนื้อคู่-ความรักชัด •\n"
                        ."   คนในบ้านป่วยหรือตัวเองป่วย • อุบัติเหตุ-เดินทางอันตราย • เกณฑ์สูญเสีย (ญาติ/เงินก้อน/งาน) • คนคิดร้าย-หักหลัง • โดนของ-คุณไสย์\n"
                        ."• ฟันธงให้เจาะจง (ใส่เท่าที่ \"หน้าไพ่+ตำแหน่ง+ดาว\" ชี้จริง ไม่ต้องครบทุกช่อง): *เรื่องอะไร • หนักหรือเบา • ใคร/อะไร • ที่ไหน • เมื่อไหร่ • มาจากใคร/แหล่งใด*\n"
                        ."   ❌ ห้ามตอบคลุมๆ แบบกั๊ก เช่น \"ระวังสุขภาพนะ\"/\"อาจมีเกณฑ์...\"\n"
                        ."• ถ้าเรื่องเด่นเป็น \"เคราะห์หนัก\" (สูญเสีย/ป่วยหนัก/อุบัติเหตุ) → บอกตรงว่า \"รอบนี้แม่หมอขอโฟกัสเรื่องนี้ก่อนเป็นเรื่องหลัก\"\n"
                        ."   + เตือนชัด + แนบ *ทางแก้ที่ทำได้จริง 1 ข้อ* (ทำเอง/ไม่เปลืองเงิน) — ❌ ห้ามขายพิธีแพง ห้ามขู่ให้กลัว\n"
                        ."• ⚖️ \"โดนของ-คุณไสย์\" ทำเป็นพาดหัวได้ *เฉพาะเมื่อคลังสัญญาณไสยศาสตร์ด้านบนมีบรรทัดเตือนจริง* (มี ⚠️ / ระบุว่ามีของ)\n"
                        ."   — ถ้าทุกใบ \"ไม่มีของ\" *ห้าม* ยกเรื่องของมาขู่เด็ดขาด. และเรื่องของ = แค่ \"ทักให้รู้ตัว\" ในพาดหัว ถ้าอยากเจาะลึกจริง\n"
                        ."   ชวนเปิดไพ่ชุดใหม่ถามเรื่องของเป็นคำถามแรก (เพื่อล็อกพลังไพ่ทะลุของโดยเฉพาะ) — รอบพื้นดวงนี้ยังไม่เจาะ\n"
                        ."• ถ้าสำรับสงบ ไม่มีเคราะห์เด่น → พาดหัวด้วย \"จุดเด่นเชิงบวก/โอกาส\" ที่ไพ่ชูชัดสุดแทน (ไม่ต้องเค้นหาเรื่องร้าย)\n"
                        ."🧠 ลูกค้าเปราะบาง/วิตกง่าย → ฟันธงตามไพ่ได้ แต่เน้นทางป้องกัน+ดึงสติ ไม่ขยี้ความกลัว\n"
                        .'❌ ห้ามปิดท้ายส่วนนี้ด้วย เคล็ด/สีมงคล/เลข/ฤกษ์/ธรรมะ/ชวนถามต่อ — รอบนี้ทำแค่ "พาดหัวเรื่องเด่น" เรื่องเดียว เดี๋ยวลงรายละเอียดด้านล่าง',
                ],
                [
                    'label' => 'g1-foundation',
                    'len' => '500-800',
                    'must' => ['🌟', '🔮'],
                    'spec' => "เขียน 2 ส่วนนี้ (ขึ้นหัวข้อด้วย emoji ตามนี้เป๊ะ เว้นบรรทัดให้อ่านง่าย • พาดหัวเรื่องเด่นทำไปแล้วในส่วนแรก ไม่ต้องทำซ้ำ):\n"
                        ."🌟 พื้นฐานดวง — ราศี/ปีนักษัตร/ธาตุ + ดาวเจ้าชนะ/ดาวมิตร/ดาวศัตรู + นิสัยพื้นฐาน (จุดแข็ง-จุดอ่อน) ผูกกับไพ่ที่สื่อบุคลิก\n"
                        .'🔮 ภาพรวมชีวิตช่วงนี้ — โยงดาวเสวยอายุ/ดวงปีนี้ กับภาพรวมไพ่ 10 ใบ (อดีต→ปัจจุบัน→อนาคต)',
                ],
                [
                    'label' => 'g2-areas',
                    'len' => $deityEnabled ? '750-1100' : '600-900', // +องค์เทพ 1 บล็อก → ขยายเพดานเล็กน้อยเมื่อ gate เปิด
                    'must' => ['💞', '💼', '💰', '🌿'], // องค์เทพไม่ใส่ — เป็น conditional (ผูก $deityEnabled)
                    'signal' => $g2Signal, // สุขภาพ(อวัยวะ)+ความรัก รายไพ่ (+องค์เทพ ถ้า gate เปิด) — ตัด wealth กัน g2 บวม
                    // 🚫 (2026-06-19 FTU-260619-C9002) ตัด g3-closing ทิ้ง — owner: "ฤกษ์/สีมงคล/ธรรมะทิ้งท้าย/ระวัง
                    //   เพิ่งเริ่มไม่ควรมีใน Q1" → ย้ายไปบทสรุป VIP (buildGrandFinalePrompt) เท่านั้น. g2 = ส่วนสุดท้ายของ Q1
                    // 🕉️ (2026-06-27 owner) ข้อยกเว้นเฉพาะ "องค์เทพประจำตัว" — owner สั่งให้มีใน Q1 (คนละเรื่องกับ สีมงคล/เลข/ฤกษ์ทั่วไป ที่ยังห้าม)
                    'spec' => 'เขียน 4 ด้านนี้'.($deityEnabled ? ' + ปิดท้ายด้วยองค์เทพประจำตัว' : '')." (ขึ้นหัวข้อด้วย emoji ตามนี้เป๊ะ • แต่ละด้านผูกดาว(ภพ)+ไพ่ที่เกี่ยวข้อง • ฟันธงชัด • ไม่ต้องทำพาดหัวเรื่องเด่นซ้ำ):\n"
                        ."💞 ความรัก\n"
                        ."💼 การงาน\n"
                        ."💰 การเงิน\n"
                        ."🌿 สุขภาพ\n"
                        ."❌ 4 ด้านบนนี้ ห้ามแปะ สีมงคล/เลขมงคล/ฤกษ์/วันมงคล/ทิศ/บทสรุปรวม/ธรรมะทิ้งท้าย — เก็บไว้บทสรุป VIP ตอนจบเท่านั้น (รอบนี้เพิ่งเปิดดวง)\n"
                        .$g2DeitySpec
                        .'ปิดท้ายด้วย 1 บรรทัดสั้นเท่านั้น: ชวนเจ้าชะตาพิมพ์ถามเจาะลึกเรื่องที่อยากรู้ได้เลย (❌ ไม่สรุปยาว ❌ ไม่ใส่ธรรมะ/มงคลทั่วไป)',
                ],
            ];
        } // end else (โครงพื้นดวงปกติ — โหมดคุณไสย์ใช้ groups ด้านบน)

        $aiService = new FortuneAIService($this->settings, 'prediction_celtic', 'openai');

        // 🚀 (2026-08-17) ลอง "คอลเดียวก้อนใหญ่" ก่อน — ถ้าโมเดลทำตามฟอร์มครบก็จบใน 1 call
        //   เหตุผลที่เคยต้องแบ่ง 3 คอล = gpt-5.4-mini ตามฟอร์มไม่ครบ (avg 2.08/9 หัวข้อ) ไม่ใช่ context เต็ม
        //   พอเปลี่ยนเป็น gpt-5.6-luna (AA index 52 vs ~41 · context 1.05M) ข้อจำกัดนั้นหายไป
        //   วัดจริงบน prod 7/7 รอบ ข้ามลูกค้า 4 คน → ได้หัวข้อครบ 8/8 ทุกครั้ง
        //   ใช้ token ~16k (จาก ~37k) และเร็วขึ้น ~38% เพราะไม่ต้องส่ง shared context ซ้ำ 3 รอบ
        //   ❗ ไม่ผ่านเกณฑ์เมื่อไหร่ → ตกลงไปโหมดแบ่งบล็อกเดิมอัตโนมัติ (ลูกค้าไม่มีทางได้ของพัง)
        //
        // 🪬 (2026-08-17 owner) โหมดคุณไสย์ก็ยิงคอลเดียวเหมือนกัน — "sol รับพร๊อมบวมได้สบาย"
        //   ครั้งแรกที่วัดมันตกเกณฑ์เพราะเกณฑ์ 'must' ตั้งไว้เข้มเกิน (ไปบังคับหัวข้อ *ย่อย* 👤/🧭)
        //   ตอนนี้เหลือหัวข้อหลักบล็อกละ 1 ตัว + สั่งห้ามรวบหัวข้อในพรอมต์ → sol มีโอกาสผ่านจริง
        //   ถ้ายังคายมาไม่ครบ ตาข่ายเดิมก็ยังพาไปโหมดแบ่งบล็อกอัตโนมัติ (ลูกค้าไม่มีทางได้ของพัง)
        $single = $this->tryBaseChartSingleCall($aiService, $reading, $shared, $groups);
        if ($single !== null) {
            return $single;
        }

        $blocks = [];
        $totalTokens = 0;
        $provider = null;
        $model = null;
        $priorSummary = '';

        foreach ($groups as $g) {
            try {
                $r = $this->generateBaseChartGroup($aiService, $reading, $shared, $g, $priorSummary);

                $txt = trim((string) ($r['response'] ?? ''));

                if ($txt !== '') {
                    $blocks[] = $txt;
                    $totalTokens += (int) ($r['tokens_used'] ?? 0);
                    $provider = $provider ?? ($r['provider'] ?? null);
                    $model = $model ?? ($r['model'] ?? null);
                    // running summary (cap ท้าย 700 ตัว) เพื่อความต่อเนื่อง + กันทวนซ้ำ
                    $priorSummary = mb_substr(trim($priorSummary."\n".$txt), -700);
                    // 🔒 (2026-06-19 bug-hunt) bump updated_at ทุกบล็อกที่สำเร็จ — พื้นดวงยิงหลาย AI call (30-90s)
                    //   ไม่เขียน $reading ระหว่างทาง → ถ้ารวมเกิน 90s Hard Guard อาจเข้าใจผิดว่า reading ค้าง
                    //   (stale updated_at) แล้วปล่อยข้อความ concurrent ยิงพื้นดวงซ้ำ. touch กัน false-recovery (ทั้ง 2 path)
                    try {
                        $reading->touch();
                    } catch (\Throwable $touchErr) {
                        // non-blocking
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('CelticCross: base-chart section fail (ข้ามส่วนนี้)', [
                    'reading_id' => $reading->id,
                    'group' => $g['label'],
                    'error' => $e->getMessage(),
                ]);
                // ข้าม section นี้ — ทำต่อ (partial ดีกว่า fallback ที่จ่าย token ซ้ำ)
            }
        }

        if (empty($blocks)) {
            return null; // ทุก call ล้มเหลว → ให้ askQuestion fallback ไป single-call
        }

        return [
            'response' => implode("\n\n", $blocks),
            'tokens_used' => $totalTokens,
            'provider' => $provider,
            'model' => $model,
        ];
    }

    /**
     * 🧱 (2026-08-17) ยิง AI "หนึ่งบล็อก" ของพื้นดวงเปิดตัว
     *
     * แยกออกมาจาก loop เดิมเพื่อให้โหมดซ่อม (repairBaseChartMissingGroup) เรียกใช้
     * prompt/สเปก/โมเดล **ชุดเดียวกันเป๊ะ** — บล็อกที่ซ่อมจึงคุณภาพเท่าโหมดแบ่งบล็อกปกติ
     *
     * @param  array  $g  1 element ของ $groups (label/len/must/signal/spec)
     * @param  string  $priorSummary  ท้ายข้อความที่เขียนไปแล้ว (กันทวนซ้ำ) — '' = ไม่ส่ง
     * @return array ['response' => string, 'tokens_used' => int, 'provider' => ?string, 'model' => ?string]
     *
     * @throws \Throwable ปล่อยให้ caller ตัดสินใจ (loop = ข้ามบล็อก / repair = ยกเลิกการซ่อม)
     */
    protected function generateBaseChartGroup(
        FortuneAIService $aiService,
        FortuneReading $reading,
        string $shared,
        array $g,
        string $priorSummary = ''
    ): array {
        // 🧭 (2026-06-18) ฉีดคลังสัญญาณเฉพาะ group ที่ใช้ (ไม่ใส่ shared → กันบวมทุก call)
        $signalBlock = ! empty($g['signal'])
            ? $this->buildBaseChartSignalKnowledge($reading, $g['signal'])
            : '';

        $prompt = $shared
            .$signalBlock
            ."━━━━━━━━━━━━━━━━━\n📐 งานรอบนี้ — เขียนเฉพาะส่วนที่กำหนด (ห้ามเขียนส่วนอื่น ห้ามสรุปรวบ)\n━━━━━━━━━━━━━━━━━\n"
            .$g['spec']."\n\n"
            .($priorSummary !== ''
                ? "📜 ส่วนที่เขียนไปแล้ว (อ่านเพื่อให้ต่อเนื่อง • อย่าทวนซ้ำ • อย่าทวนไพ่ใบเดิมแบบลอกข้อความ):\n".$priorSummary."\n\n"
                : '')
            ."📏 ความยาว {$g['len']} ตัวอักษร • plain text + emoji หัวข้อ • ❌ ห้าม markdown (**, ##) • ฟันธงตามไพ่+ดาว ห้ามกำกวม\n"
            .'ขึ้นต้นด้วยหัวข้อ emoji แรกของส่วนนี้ทันที (ห้ามทักทาย ห้ามเกริ่นนำ ห้ามใส่ [TYPE]):';

        // 🪪 (2026-08-17) ต้องเรียกทุกครั้งก่อน generate — context เป็น one-shot
        //   ถูกล้างหลัง generate ทุกครั้ง เรียกครั้งเดียวนอก loop = กลุ่มที่ 2 เป็นต้นไป reading_id หาย
        $aiService->forReading($reading);

        $r = $aiService->generateWithRetryAndFallback(
            questions: [$prompt],
            userProfile: null,
            userPosts: null,
            promptTemplate: '{questions}',
            readingType: 'deep',
            birthDate: null,
            userContext: "celtic_basechart:{$reading->id}:{$g['label']}",
            purpose: 'prediction_celtic',
            // 🪬 พื้นดวงเปิดตัว: ตรวจแค่ธงคุณไสย์ (คำถาม Q1 เป็นข้อความสังเคราะห์ ไม่ต้องเสียค่า detector)
            modelOverrides: $this->resolveCelticModelOverrides($reading),
        );

        $txt = trim((string) ($r['response'] ?? ''));
        // strip TYPE token เผื่อ AI ใส่มา (เราจัดเป็น A เองใน askQuestion) — ใช้ pattern เดียวกับ askQuestion
        $stripped = preg_replace('/[`*]{0,3}[\[\【\［]?\s*TYPE\s*[:：]\s*[A-E]\s*[\]\】\］]?[`*]{0,3}/iu', '', $txt);
        if (is_string($stripped)) {
            $txt = trim($stripped);
        }

        return [
            'response' => $txt,
            'tokens_used' => (int) ($r['tokens_used'] ?? 0),
            'provider' => $r['provider'] ?? null,
            'model' => $r['model'] ?? null,
        ];
    }

    /**
     * 🚀 (2026-08-17) พื้นดวงเปิดตัวแบบ "คอลเดียว" — รวมทุก section เป็น prompt ก้อนเดียว
     *
     * ใช้ groups ชุดเดียวกับโหมดแบ่งบล็อกเป๊ะ (spec/len ไม่แตะ) ต่างแค่ยิงรวดเดียว
     * ประหยัดเพราะไม่ต้องส่ง $shared (persona + ไพ่ 10 ใบ + ดาวเจ้าชนะ + card-first) ซ้ำทุก call
     *
     * ⚠️ ตาข่ายกันพลาด — คืน null (= ให้ caller ตกไปโหมดแบ่งบล็อกเดิม) เมื่อ:
     *   1. AI ล้มเหลว/คืนค่าว่าง
     *   2. หัวข้อบังคับ ('must' ของทุก group) มาไม่ครบ  ← กันอาการ non-compliance ที่เคยเจอกับโมเดลเล็ก
     *   3. ความยาวไม่ถึง 60% ของผลรวมขอบล่างใน 'len'  ← กันคายมาสั้นกุด
     * ลูกค้าที่จ่ายเงินแล้วจึงไม่มีทางได้คำทำนายที่โครงไม่ครบจากทางนี้
     *
     * @param  array  $groups  โครงสร้าง section เดียวกับ generateBaseChartSectioned
     * @return array|null ['response','tokens_used','provider','model'] | null = ไม่ผ่านเกณฑ์
     */
    protected function tryBaseChartSingleCall(FortuneAIService $aiService, FortuneReading $reading, string $shared, array $groups): ?array
    {
        if (empty($groups)) {
            return null;
        }

        // 🧭 คลังสัญญาณ = union ของทุก group (คอลเดียวต้องเห็นครบเหมือนที่ 3 คอลเห็นรวมกัน)
        $signals = [];
        foreach ($groups as $g) {
            foreach (($g['signal'] ?? []) as $s) {
                $signals[$s] = true;
            }
        }
        $signalBlock = ! empty($signals)
            ? $this->buildBaseChartSignalKnowledge($reading, array_keys($signals))
            : '';

        // 📐 ต่อ spec ทุกส่วนเรียงตามลำดับเดิม
        $sectionSpecs = '';
        $minChars = 0;
        $mustHeaders = [];
        foreach ($groups as $i => $g) {
            $sectionSpecs .= "\n———— ส่วนที่ ".($i + 1)." (ยาว {$g['len']} ตัวอักษร) ————\n".$g['spec']."\n";
            $minChars += (int) $g['len']; // '400-700' → (int) = 400 (ขอบล่าง)
            foreach (($g['must'] ?? []) as $h) {
                $mustHeaders[] = $h;
            }
        }
        $minChars = (int) max(1200, $minChars * 0.6);

        // ✅ (2026-08-17) เช็คลิสต์อีโมจิแบบ "ระบุตัวจริง" — แก้ที่ต้นเหตุ ไม่ใช่รอตาข่ายรับ
        //   เคสจริง R11182: คำสั่งเดิมพูดลอยๆ ว่า "ทุกหัวข้อที่มี emoji ต้องขึ้นเป็นหัวข้อจริง"
        //   โมเดลเขียน 🌟 แล้วเล่า "ภาพรวมชีวิตช่วงนี้" ต่อเป็นความเรียง — **🔮 หายไปเฉยๆ**
        //   → ตกเกณฑ์เพราะอีโมจิตัวเดียว ทิ้งงาน 4,280 ตัวที่ดีอยู่แล้ว
        //   วิธีที่ได้ผลกว่า: ยัดรายชื่ออีโมจิที่บังคับจริงเป็นเช็คลิสต์ + สั่งให้ไล่ทวนก่อนส่ง
        //   (ดึงจาก $groups['must'] ตัวเดียวกับที่ใช้ตรวจ → พรอมต์กับเกณฑ์ไม่มีทางหลุดจากกัน)
        $mustList = implode('  ', array_unique($mustHeaders));

        $prompt = $shared
            .$signalBlock
            ."━━━━━━━━━━━━━━━━━\n📐 งานรอบนี้ — เขียน \"พื้นดวงเปิดตัว\" ให้ครบทุกส่วนใน *คำตอบเดียว* เรียงตามลำดับด้านล่างเป๊ะ\n━━━━━━━━━━━━━━━━━\n"
            .$sectionSpecs
            ."\n📏 plain text + emoji หัวข้อ • ❌ ห้าม markdown (**, ##) • ฟันธงตามไพ่+ดาว ห้ามกำกวม\n"
            // 🎯 (2026-08-17) กันอาการ "รวบหัวข้อ" ที่เจอตอนยิงคอลเดียวในโหมดคุณไสย์ —
            //   โมเดลเขียนหัวข้อแรกของบล็อกแล้วเล่าที่เหลือเป็นความเรียงยาว หัวข้อย่อยหายไปเฉยๆ
            ."❗ ทุกหัวข้อที่มี emoji นำหน้าในสเปกด้านบน ต้องขึ้นเป็น *หัวข้อจริง* ในคำตอบให้ครบทุกอัน\n"
            ."   ❌ ห้ามรวบหลายหัวข้อเป็นย่อหน้าเดียว ❌ ห้ามข้ามหัวข้อใดหัวข้อหนึ่ง\n\n"
            ."━━━━━━━━━━━━━━━━━\n"
            ."🚨 เช็คลิสต์บังคับ — คำตอบนี้ *ต้อง* มีอีโมจิทุกตัวนี้ ขึ้นต้นบรรทัดในฐานะหัวข้อ:\n"
            ."   {$mustList}\n"
            ."   • แต่ละตัวขึ้นบรรทัดใหม่ ตามด้วยชื่อหัวข้อของมัน แล้วค่อยเขียนเนื้อหา\n"
            ."   • ❌ ห้ามเอาอีโมจิเหล่านี้ไปแทรกกลางย่อหน้าแทนการขึ้นหัวข้อ\n"
            ."   • ก่อนส่งคำตอบ ให้ไล่ทวนทีละตัวว่าครบทุกตัวจริง — ขาดตัวเดียวถือว่างานไม่ผ่าน\n"
            ."━━━━━━━━━━━━━━━━━\n"
            .'เขียนทุกส่วนต่อกันในคำตอบเดียว ขึ้นต้นด้วยหัวข้อ emoji ของส่วนที่ 1 ทันที (ห้ามทักทาย ห้ามเกริ่นนำ ห้ามใส่ [TYPE]):';

        try {
            // 🪪 (2026-08-17) ผูก usage log กับใบดูดวงนี้ (พื้นดวงยิงคอลเดียว)
            $aiService->forReading($reading);

            $r = $aiService->generateWithRetryAndFallback(
                questions: [$prompt],
                userProfile: null,
                userPosts: null,
                promptTemplate: '{questions}',
                readingType: 'deep',
                birthDate: null,
                userContext: "celtic_basechart_single:{$reading->id}",
                purpose: 'prediction_celtic',
                modelOverrides: $this->resolveCelticModelOverrides($reading), // 🪬 คุณไสย์ → sol
            );
        } catch (\Throwable $e) {
            Log::warning('CelticCross: base-chart คอลเดียวล้มเหลว → ใช้โหมดแบ่งบล็อกแทน', [
                'reading_id' => $reading->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        $txt = trim((string) ($r['response'] ?? ''));
        // strip TYPE token เผื่อ AI ใส่มา (pattern เดียวกับโหมดแบ่งบล็อก)
        $stripped = preg_replace('/[`*]{0,3}[\[\【\［]?\s*TYPE\s*[:：]\s*[A-E]\s*[\]\】\］]?[`*]{0,3}/iu', '', $txt);
        if (is_string($stripped)) {
            $txt = trim($stripped);
        }

        if ($txt === '') {
            return null;
        }

        // ✅ เกณฑ์ผ่าน: หัวข้อบังคับครบ + ยาวพอ
        $missing = array_values(array_filter($mustHeaders, fn ($h) => ! str_contains($txt, $h)));
        $len = mb_strlen($txt);

        if (! empty($missing) || $len < $minChars) {
            // 🩹 (2026-08-17) ซ่อมเฉพาะบล็อกที่ขาด แทนทิ้งทั้งก้อนไปยิงใหม่ 3 คอล
            //   เคสจริง R11182: คอลเดียวคาย 4,280 ตัว (ยาวกว่าผลสุดท้าย 4,143 ด้วยซ้ำ)
            //   ขาดแค่ 🔮 ตัวเดียว → ทิ้งหมด ยิงใหม่ 3 คอล = 54,163 token / 42.8s
            //   แทนที่จะเป็น ~16k / ~16s. ซ่อม 1 บล็อก = แย่สุด 2 คอล ไม่ใช่ 4
            $repair = $this->repairBaseChartMissingGroup($aiService, $reading, $shared, $groups, $txt, $missing, $len, $minChars);
            if ($repair !== null) {
                try {
                    $reading->touch();
                } catch (\Throwable $touchErr) {
                    // non-blocking
                }

                return [
                    'response' => $repair['response'],
                    'tokens_used' => (int) ($r['tokens_used'] ?? 0) + (int) $repair['tokens_used'],
                    'provider' => $r['provider'] ?? $repair['provider'],
                    'model' => $r['model'] ?? $repair['model'],
                ];
            }

            Log::warning('CelticCross: base-chart คอลเดียวไม่ผ่านเกณฑ์ → ตกไปโหมดแบ่งบล็อก', [
                'reading_id' => $reading->id,
                'missing_headers' => $missing,
                'chars' => $len,
                'min_chars' => $minChars,
                'model' => $r['model'] ?? null,
            ]);

            return null;
        }

        // 🔒 bump updated_at — เหตุผลเดียวกับโหมดแบ่งบล็อก (กัน Hard Guard เข้าใจผิดว่า reading ค้าง)
        try {
            $reading->touch();
        } catch (\Throwable $touchErr) {
            // non-blocking
        }

        Log::info('CelticCross: base-chart คอลเดียวสำเร็จ', [
            'reading_id' => $reading->id,
            'chars' => $len,
            'tokens' => $r['tokens_used'] ?? null,
            'model' => $r['model'] ?? null,
        ]);

        return [
            'response' => $txt,
            'tokens_used' => (int) ($r['tokens_used'] ?? 0),
            'provider' => $r['provider'] ?? null,
            'model' => $r['model'] ?? null,
        ];
    }

    /**
     * 🩹 (2026-08-17) ซ่อมพื้นดวงคอลเดียวที่ "ขาดหัวข้อบังคับของบล็อกเดียว"
     *
     * แทนที่จะทิ้งงานทั้งก้อนแล้วยิงใหม่ 3 คอล → ยิงซ่อมเฉพาะบล็อกนั้น 1 คอล
     * แล้ว splice กลับเข้าที่เดิม (ใช้ generateBaseChartGroup ตัวเดียวกับโหมดแบ่งบล็อก
     * → คุณภาพบล็อกที่ซ่อมเท่าของเดิมเป๊ะ)
     *
     * ❌ ไม่ซ่อม (คืน null = ให้ caller ตกไปโหมดแบ่งบล็อกเดิม) เมื่อ:
     *   1. ความยาวรวมไม่ถึงเกณฑ์ — โมเดลคายมาน้อยทั้งก้อน ซ่อมบล็อกเดียวไม่พอ
     *   2. บล็อกที่ขาดมีมากกว่า 1 — ซ่อม 2 ใน 3 ก็ไม่ประหยัดแล้ว + splice เสี่ยงขึ้น
     *   3. หาตำแหน่ง splice ไม่ได้ / ลำดับหัวข้อในคำตอบสลับกัน
     *   4. บล็อกที่ซ่อมมาเองก็ยังขาดหัวข้อบังคับ
     * → ลูกค้าที่จ่ายเงินไม่มีทางได้ของที่โครงไม่ครบจากทางนี้ (เกณฑ์เดิมไม่ถูกผ่อน)
     *
     * @param  array  $missing  หัวข้อบังคับที่หายไปจาก $txt
     * @return array|null ['response','tokens_used','provider','model'] | null = ซ่อมไม่ได้
     */
    protected function repairBaseChartMissingGroup(
        FortuneAIService $aiService,
        FortuneReading $reading,
        string $shared,
        array $groups,
        string $txt,
        array $missing,
        int $len,
        int $minChars
    ): ?array {
        // เงื่อนไข 1 — สั้นทั้งก้อน = ปัญหาไม่ได้อยู่ที่หัวข้อเดียว
        if ($len < $minChars || empty($missing)) {
            return null;
        }

        // เงื่อนไข 2 — หาว่าหัวข้อที่ขาดกระจุกอยู่บล็อกเดียวไหม
        $affected = [];
        foreach ($groups as $i => $g) {
            foreach (($g['must'] ?? []) as $h) {
                if (in_array($h, $missing, true)) {
                    $affected[$i] = true;
                    break;
                }
            }
        }
        if (count($affected) !== 1) {
            return null;
        }
        $gi = (int) array_key_first($affected);
        $target = $groups[$gi];

        // เงื่อนไข 3 — หาช่วงข้อความของบล็อกนี้ (start = หัวข้อแรกของบล็อกที่ยังอยู่,
        //   end = หัวข้อแรกของบล็อกถัดไปที่ยังอยู่ / ท้ายข้อความ)
        $start = null;
        foreach (($target['must'] ?? []) as $h) {
            $p = $this->findHeaderPosition($txt, $h);
            if ($p !== null && ($start === null || $p < $start)) {
                $start = $p;
            }
        }

        $end = mb_strlen($txt);
        for ($j = $gi + 1; $j < count($groups); $j++) {
            foreach (($groups[$j]['must'] ?? []) as $h) {
                $p = $this->findHeaderPosition($txt, $h);
                if ($p !== null && $p < $end) {
                    $end = $p;
                }
            }
        }

        // บล็อกหายทั้งบล็อก → แทรกที่หัวบล็อกถัดไป ; หัวข้อสลับลำดับ → ไม่เสี่ยง splice
        if ($start !== null && $start > $end) {
            return null;
        }

        // เงื่อนไข 4 — ยิงซ่อม (ส่งท้ายของเนื้อหาก่อนหน้าไปกันเขียนทวนซ้ำ)
        try {
            $prior = mb_substr(mb_substr($txt, 0, $start ?? $end), -700);
            $rep = $this->generateBaseChartGroup($aiService, $reading, $shared, $target, $prior);
        } catch (\Throwable $e) {
            Log::warning('CelticCross: base-chart ซ่อมบล็อกล้มเหลว → ตกไปโหมดแบ่งบล็อก', [
                'reading_id' => $reading->id,
                'group' => $target['label'] ?? '?',
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        $block = trim((string) ($rep['response'] ?? ''));
        if ($block === '') {
            return null;
        }

        foreach (($target['must'] ?? []) as $h) {
            if (! str_contains($block, $h)) {
                Log::warning('CelticCross: บล็อกที่ซ่อมมายังขาดหัวข้อ → ตกไปโหมดแบ่งบล็อก', [
                    'reading_id' => $reading->id,
                    'group' => $target['label'] ?? '?',
                    'still_missing' => $h,
                ]);

                return null;
            }
        }

        // splice: มีบล็อกเดิมบางส่วน = แทนที่ทั้งช่วง [start,end) ; ไม่มีเลย = แทรกก่อนบล็อกถัดไป
        $cut = $start ?? $end;
        $merged = rtrim(mb_substr($txt, 0, $cut))."\n\n".$block."\n\n".ltrim(mb_substr($txt, $end));
        $merged = trim(preg_replace("/\n{3,}/u", "\n\n", $merged) ?? $merged);

        // 🛡️ ตรวจซ้ำหลัง splice — ช่วงที่ถูกแทนที่อาจกลืนหัวข้อของบล็อกอื่นไปด้วย
        //   (เกิดได้ถ้าโมเดลเขียนหัวข้อสลับลำดับกับสเปก) → เจอเมื่อไหร่ ถอยไปโหมดแบ่งบล็อก
        foreach ($groups as $g) {
            foreach (($g['must'] ?? []) as $h) {
                if (! str_contains($merged, $h)) {
                    Log::warning('CelticCross: splice บล็อกซ่อมแล้วหัวข้ออื่นหาย → ตกไปโหมดแบ่งบล็อก', [
                        'reading_id' => $reading->id,
                        'group' => $target['label'] ?? '?',
                        'lost_header' => $h,
                    ]);

                    return null;
                }
            }
        }

        Log::info('CelticCross: base-chart ซ่อมบล็อกที่ขาดสำเร็จ (ไม่ต้องยิงใหม่ 3 คอล)', [
            'reading_id' => $reading->id,
            'group' => $target['label'] ?? '?',
            'missing_headers' => $missing,
            'mode' => $start !== null ? 'replace' : 'insert',
            'chars_before' => $len,
            'chars_after' => mb_strlen($merged),
            'repair_tokens' => $rep['tokens_used'] ?? 0,
        ]);

        return [
            'response' => $merged,
            'tokens_used' => (int) ($rep['tokens_used'] ?? 0),
            'provider' => $rep['provider'] ?? null,
            'model' => $rep['model'] ?? null,
        ];
    }

    /**
     * 🔎 (2026-08-17) หาตำแหน่งของ "หัวข้ออีโมจิ" ในข้อความ — ใช้ตอน splice บล็อกที่ซ่อม
     *
     * ชอบตำแหน่งที่อยู่ *ต้นบรรทัด* ก่อนเสมอ เพราะอีโมจิเดียวกัน (เช่น 💰) โผล่กลางย่อหน้าได้
     * ถ้าไปตัดตรงนั้นจะได้ข้อความขาดกลางประโยค — ไม่เจอต้นบรรทัดค่อยถอยไปใช้ตำแหน่งแรกที่เจอ
     *
     * @return int|null ตำแหน่งแบบ multi-byte (ใช้กับ mb_substr ได้ตรงๆ) | null = ไม่มีในข้อความ
     */
    protected function findHeaderPosition(string $txt, string $header): ?int
    {
        $offset = 0;
        foreach (explode("\n", $txt) as $line) {
            $trimmed = ltrim($line);
            if ($trimmed !== '' && str_starts_with($trimmed, $header)) {
                return $offset + (mb_strlen($line) - mb_strlen($trimmed));
            }
            $offset += mb_strlen($line) + 1; // +1 = "\n"
        }

        $p = mb_strpos($txt, $header);

        return $p === false ? null : $p;
    }

    /**
     * 🧭 (2026-06-18) คลังสัญญาณ "รายไพ่" สำหรับพาดหัวเรื่องเด่น — พื้นดวงเปิดตัว
     *
     * User directive 2026-06-18: "ไพ่ 10 ใบ ต้องพูด \"เรื่องเด่นของไพ่\" ก่อนเป็นเรื่องแรก —
     *   โดนของ/เกณฑ์สูญเสีย/คนป่วย/อุบัติเหตุ/โชคลาภ ต้องฟันธงชัด (ใคร/อะไร/ที่ไหน/เมื่อไหร่/
     *   มากน้อย/จากใคร) ... องค์ความรู้ทำไว้แล้วแต่ใช้ไม่เต็มที่"
     *
     * ROOT CAUSE: คลังความรู้ทั้งหมด (black_magic/health/combo/mu) detect จาก "คำถามลูกค้า" —
     *   แต่ Q1 "พื้นดวงเปิดตัว" เป็นคำถามสังเคราะห์ (ไม่มีคำว่า "โดนของ"/"ป่วย") → คลังไม่เคย fire
     *   ในรอบเปิดตัว (รอบ holistic 10 ใบที่สำคัญสุด) → แม่หมอมองไม่เห็นสัญญาณเด่นจากหน้าไพ่
     *
     * แก้: ดึงคลัง "รายไพ่" ของหมวดที่บอก "สัญญาณเด่น/เตือนเคราะห์" แบบ *card-gated* (ไม่ผูก keyword) =
     *   ไสยศาสตร์(โดนของ) + สุขภาพ(อวัยวะ/โรค) + ไพ่คู่(สัญญาณรวม เช่น หอคอย+ดาบ10=จบเจ็บ)
     *   → ให้แม่หมอ "สแกนหาเรื่องเด่น" จากไพ่เองได้ แม้ลูกค้ายังไม่ถาม
     *
     * ⚠️ หมายเหตุ (จากรีวิว 2026-06-18): ตำราไสยศาสตร์+สุขภาพมีครบ 78 ใบ → "เกือบทุกใบมีบรรทัด"
     *   (ไม่ใช่ว่างเปล่า) — black_magic ส่วนใหญ่เป็นบรรทัด "ไม่มีของ — ..." (เซฟอยู่ที่ *เนื้อหา* ไม่ใช่
     *   ความว่าง). ดังนั้น *ห้ามใส่ใน $shared* (จะบวมทุก call) — ให้ caller เลือกหมวด ($include) ฉีดเฉพาะ
     *   group ที่ใช้ (g0=ครบ, g2=health, g3=black_magic+combo). + แนบ "เบรกจรรยาบรรณ" ไปกับเนื้อโดนของเสมอ
     *
     * เคารพ toggle เดิม (admin ปิดรายหมวดได้)
     *
     * @param  array<int, string>  $include  หมวดที่ต้องการ: 'black_magic' | 'health' | 'combo' (default = ครบ)
     * @return string ว่าง = ไม่มีสัญญาณ/ไพ่ไม่ครบ/ปิดทุก toggle/ไม่เลือกหมวด
     */
    protected function buildBaseChartSignalKnowledge(FortuneReading $reading, array $include = ['black_magic', 'health', 'combo']): string
    {
        $cards = $reading->getCelticCards();
        if (count($cards) < 10) {
            return '';
        }

        $svc = app(\App\Services\FortuneKnowledgeService::class);
        $sections = [];
        $hasBlackMagic = false;

        // 🪬 ไสยศาสตร์รายไพ่ (โดนของ/มนต์ดำ) — โชว์ "เฉพาะใบที่มีสัญญาณจริง" (orientation-aware)
        //   ตัด noise บรรทัด "ไม่มีของ" ~9 บรรทัด + กัน fear-anchoring (ดู blackMagicSignalLinesForCards)
        //   ไม่มีสัญญาณ = section หาย → gate "พาดหัวโดนของเฉพาะมีบรรทัดเตือนจริง" เป็นจริงเอง
        if (in_array('black_magic', $include, true) && (bool) ($this->settings->enable_celtic_black_magic_mode ?? true)) {
            $bm = trim((string) $svc->blackMagicSignalLinesForCards($cards));
            if ($bm !== '') {
                $hasBlackMagic = true;
                $sections[] = "🪬 สัญญาณไสยศาสตร์/ของ — *เฉพาะใบที่ไพ่ชี้จริง* (ใบอื่นในสำรับ = ไม่มีของ ไม่ต้องเอ่ยถึง):\n".$bm."\n"
                    ."   🧭 จรรยาบรรณเรื่องของ: ❌ ห้ามขายความกลัว ❌ ห้ามขู่ว่าโดนของหนักจะตาย/พินาศ ❌ ห้ามแนะพิธีแก้ด่วนเป็นเงินก้อนโต\n"
                    .'   • ทักเท่าที่ไพ่เตือน ทำเป็นพาดหัวเฉพาะถ้า "เด่นจริง" / ลูกค้าเปราะบาง-ระแวง → ดึงสติ ดูสุขภาพกาย-ใจควบคู่';
            }
        }

        // 🩺 สุขภาพรายไพ่ (อวัยวะ/ระบบ/แนวโน้มอาการ)
        if (in_array('health', $include, true) && (bool) ($this->settings->enable_celtic_health_tome ?? true)) {
            $hl = trim((string) $svc->healthLinesForCards($cards));
            if ($hl !== '') {
                $sections[] = "🩺 สัญญาณสุขภาพ (อวัยวะ/ระบบ รายไพ่ — เทียบเคียง ไม่ใช่วินิจฉัยแทนแพทย์):\n".$hl;
            }
        }

        // 🔗 ไพ่คู่ที่ปรากฏจริงบนโต๊ะ (สัญญาณรวม เช่น หอคอย+ดาบ10=จบเจ็บ, ปีศาจ+พระจันทร์=โดนหลอก/เสพติด)
        if (in_array('combo', $include, true) && (bool) ($this->settings->enable_celtic_combos ?? true)) {
            $cb = trim((string) $svc->comboLinesForCards($cards));
            if ($cb !== '') {
                $sections[] = "🔗 ไพ่คู่เด่นบนโต๊ะ (สัญญาณรวม — \"เชื่อมโยงไพ่\" ไม่ใช่รายใบ):\n".$cb;
            }
        }

        // ❤️💰📅 (2026-06-18) ตำราด้าน ความรัก/การเงิน/ฤกษ์ แบบ card-gated — สำหรับพื้นดวงเปิดตัว
        //   เดิม mu tomes detect จาก $userQuestion (= วันเกิด) → ไม่เคยยิงตอนเปิดดวง ทั้งที่ g2/g3 ถามด้านนี้ตรงๆ
        //   ดึงตามไพ่ที่เปิด (ไม่ต้องมีคำถาม) เหมือน health/combo — เคารพ toggle เดิม (enable_celtic_*)
        $muTomes = [
            'love' => ['enable_celtic_love', \App\Services\FortuneKnowledgeService::LOVE_DETECTABLE, '❤️ ความรัก/เนื้อคู่ (รายไพ่)'],
            'wealth' => ['enable_celtic_wealth', \App\Services\FortuneKnowledgeService::WEALTH_DETECTABLE, '💰 การเงิน/โชคลาภ (รายไพ่)'],
            'auspicious' => ['enable_celtic_auspicious', \App\Services\FortuneKnowledgeService::AUSPICIOUS_DETECTABLE, '📅 ฤกษ์/มงคล (รายไพ่)'],
            // 🕉️ (2026-06-27 owner) องค์เทพ/สิ่งศักดิ์สิทธิ์คุ้มครอง-บูชาประจำตัว — card-gated เข้า Q1 พื้นดวง
            //   ใช้ gate เดิม enable_celtic_mu_knowledge (ไม่มี toggle ใหม่) — caller (g2) ต้องใส่ 'patron_deity' ใน $include
            'patron_deity' => ['enable_celtic_mu_knowledge', [\App\Models\FortuneKnowledge::CATEGORY_PATRON_DEITY], '🕉️ องค์เทพ/สิ่งศักดิ์สิทธิ์คุ้มครอง-บูชาประจำตัว (รายไพ่)'],
        ];
        foreach ($muTomes as $muKey => [$gate, $cats, $label]) {
            if (in_array($muKey, $include, true) && (bool) ($this->settings->{$gate} ?? true)) {
                $ml = trim((string) $svc->muLinesForCards($cards, $cats));
                if ($ml !== '') {
                    $sections[] = $label.":\n".$ml;
                }
            }
        }

        if (empty($sections)) {
            return '';
        }

        $header = $hasBlackMagic
            ? '⚠️ คลังนี้ "เทียบเคียง" เท่านั้น — สำรับนี้มีบางใบส่งสัญญาณ "ของ" จริง: ทักเท่าที่ไพ่บอก ห้ามขยายเกิน ห้ามขายความกลัว'
            : '⚠️ คลังนี้ "เทียบเคียง" เท่านั้น — ดึงเฉพาะที่หน้าไพ่ชี้ชัด ห้ามตอบกว้างเกินไพ่';

        return "━━━━━━━━━━━━━━━━━\n"
            ."🧭 คลังสัญญาณรายไพ่ — ใช้ \"สแกนหาเรื่องเด่น\" ของสำรับนี้ (อ่านจากหน้าไพ่ ไม่ต้องรอลูกค้าถาม)\n"
            ."━━━━━━━━━━━━━━━━━\n"
            .$header."\n\n"
            .implode("\n\n", $sections)."\n"
            ."━━━━━━━━━━━━━━━━━\n\n";
    }

    /**
     * 🤖 (2026-05-17 Phase 2) askQuestionAsAdmin — admin ส่งคำถามแทนลูกค้า (sync, return JSON)
     *
     * Standalone clone ของ askQuestion — bypass ทุก block + push customer ภายในเอง
     * เรียกจาก AJAX endpoint sync (ไม่ใช่ background) — admin เห็นผลใน UI ทันที
     *
     * Bypass:
     *   - canAskMoreCeltic check (admin sovereign)
     *   - time window
     *   - state transitions (ไม่แตะ STATUS_CELTIC_GENERATING)
     *
     * Side effects:
     *   - บันทึก FortuneCelticQuestion record (sequence = count + 1)
     *   - ไม่ตัด celtic_questions_used counter
     *   - สะสม tokens_used เพื่อ track ต้นทุน
     *   - Bridge → LineBotConversation
     *   - Push คำตอบให้ลูกค้าผ่าน FortuneChannelManager
     *
     * @return array รายละเอียดสำหรับ JSON response
     */
    public function askQuestionAsAdmin(FortuneReading $reading, string $userQuestion): array
    {
        $startTotal = microtime(true);
        $userQuestion = trim($userQuestion);

        $result = [
            'success' => false,
            'reading_id' => $reading->id,
            'sequence' => null,
            'pushed' => false,
            'ai_provider' => null,
            'ai_model' => null,
            'tokens_used' => 0,
            'response_len' => 0,
            'response_preview' => null,
            'response_full' => null,
            'elapsed_ms' => 0,
            'message' => null,
        ];

        if ($userQuestion === '') {
            $result['message'] = 'กรุณาพิมพ์คำถาม';

            return $result;
        }

        $cards = $reading->getCelticCards();
        if (count($cards) < 10) {
            $result['message'] = 'ต้องเลือกไพ่ครบ 10 ใบก่อน (มี '.count($cards).' ใบ)';

            return $result;
        }

        // 🔒 sequence + insert พร้อม retry — กัน race condition
        //   ก่อนหน้านี้: count()+1 ชนกับ unique(fortune_reading_id, sequence) เมื่อ:
        //     - ลูกค้าถามใน LINE/FB ระหว่างที่ admin ask AI กำลังรัน (30-60s)
        //     - หรือ records เก่ามี gap เพราะ AI fail → record ถูก delete
        //   ใหม่: MAX(sequence)+1 + retry 5 ครั้งถ้าเจอ duplicate
        $questionRecord = null;
        $sequence = 0;
        $maxAttempts = 5;
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $sequence = (int) FortuneCelticQuestion::where('fortune_reading_id', $reading->id)
                ->max('sequence') + 1;

            try {
                $questionRecord = FortuneCelticQuestion::create([
                    'fortune_reading_id' => $reading->id,
                    'sequence' => $sequence,
                    'question' => mb_substr($userQuestion, 0, 1000),
                ]);
                break; // สำเร็จ
            } catch (\Illuminate\Database\QueryException $e) {
                // 1062 = duplicate entry — อาจเกิดถ้า customer thread insert ก่อนใน race
                $isDuplicate = $e->getCode() === '23000' || str_contains($e->getMessage(), 'Duplicate entry');
                if (! $isDuplicate || $attempt === $maxAttempts) {
                    Log::error('Celtic askQuestionAsAdmin: insert record fail', [
                        'reading_id' => $reading->id,
                        'sequence' => $sequence,
                        'attempt' => $attempt,
                        'error' => $e->getMessage(),
                    ]);
                    $result['message'] = 'ไม่สามารถบันทึกคำถามได้ (retry '.$attempt.'/'.$maxAttempts.'): '.$e->getMessage();

                    return $result;
                }
                // wait + retry
                usleep(100000); // 100ms
            }
        }

        if (! $questionRecord) {
            $result['message'] = 'ไม่สามารถบันทึกคำถามได้หลัง retry '.$maxAttempts.' ครั้ง';

            return $result;
        }

        $result['sequence'] = $sequence;

        try {
            $aiStart = microtime(true);
            $prompt = $this->buildFollowupPrompt($reading, $userQuestion, $cards, $sequence);

            // 🎯 OpenAI primary + purpose='prediction_celtic'
            $aiService = new FortuneAIService($this->settings, 'prediction_celtic', 'openai');

            // 🪪 (2026-08-17) ผูก usage log กับใบดูดวงนี้
            $aiService->forReading($reading);

            $aiResult = $aiService->generateWithRetryAndFallback(
                questions: [$prompt],
                userProfile: null,
                userPosts: null,
                promptTemplate: '{questions}',
                readingType: 'deep',
                birthDate: null,
                userContext: "celtic_cross_admin:{$reading->id}:q{$sequence}",
                purpose: 'prediction_celtic',
                modelOverrides: $this->resolveCelticModelOverrides($reading, $userQuestion),
            );

            $aiElapsedMs = (int) ((microtime(true) - $aiStart) * 1000);
            $response = trim($aiResult['response'] ?? '');
            $tokensUsed = (int) ($aiResult['tokens_used'] ?? 0);
            $aiProvider = $aiResult['provider'] ?? null;
            $aiModel = $aiResult['model'] ?? null;

            if ($response === '' || mb_strlen($response) < 50) {
                throw new Exception('AI ตอบกลับสั้นเกินไป ('.mb_strlen($response).' chars)');
            }

            // Strip [END_SESSION] / [OFF_TOPIC_REPICK] tokens
            $response = trim(preg_replace('/\[\s*(END[_\s]?SESSION|จบ|END|OFF[_\s.-]?TOPIC[_\s.-]?REPICK)\s*\]/iu', '', $response));

            // 🆕 (2026-05-31) Strip TYPE token ด้วย — buildFollowupPrompt (Q1+Q2+) ใส่ [TYPE:X]
            //   มาทุก turn แต่ path admin นี้เดิมไม่ strip → [TYPE:A] หลุดโผล่หน้าลูกค้า
            //   ใช้ pattern เดียวกับ askQuestion (รองรับ fullwidth bracket/markdown wrapper)
            $response = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}\x{00A0}]/u', '', (string) $response);
            $response = trim((string) preg_replace('/[`*]{0,3}[\[\【\［\「]?\s*TYPE\s*[:：]\s*[A-E]\s*[\]\】\］\」]?[`*]{0,3}/iu', '', (string) $response));

            // 🔢 (2026-06-05 v2) Strip token คำถามแนะนำ — buildFollowupPrompt มี directive คำถามแนะนำ
            //   admin path ไม่สร้างปุ่มแนะนำ → แค่ลบ token กันรั่วหน้าลูกค้า + DB + bridge
            //   ใช้ pullNextQuestions (ทน token ผิดรูป AI ดรอป tag เปิด) ทิ้งค่า return ที่ดึงได้
            self::pullNextQuestions($response);

            $questionRecord->update([
                'response' => $response,
                'ai_provider' => $aiProvider,
                'ai_model' => $aiModel,
                'ai_tokens_used' => $tokensUsed,
                'ai_response_time_ms' => $aiElapsedMs,
                'answered_at' => now(),
            ]);

            $reading->update([
                'tokens_used' => ($reading->tokens_used ?? 0) + $tokensUsed,
            ]);

            // Bridge
            $this->bridgeToConversationLog($reading, 'user', $userQuestion);
            $this->bridgeToConversationLog($reading, 'assistant', $response);

            // Push ลูกค้า
            $userId = $reading->platform_user_id ?? $reading->facebook_user_id;
            if (! empty($userId)) {
                $platform = $reading->platform
                    ?? (preg_match('/^U[0-9a-f]{32}$/i', $userId) ? 'line' : 'facebook');

                // 🌙 (2026-05-23 v3) admin push — บอกกติกาให้ชัด (5 คำถาม / 15 นาที)
                $remainingMin = $reading->getCelticQaRemainingMinutes();
                $qaWindow = (int) ($this->settings->celtic_cross_qa_window_minutes ?? 15);
                $maxQ = $this->getMaxQuestions();
                $usedQ = (int) ($reading->celtic_questions_used ?? 0);
                $remainingQ = $maxQ > 0 ? max(0, $maxQ - $usedQ) : null;

                $timeHint = $remainingMin !== null
                    ? "⏳ เหลือเวลา *{$remainingMin} นาที* (จาก {$qaWindow})"
                    : "⏳ คุยได้ภายใน *{$qaWindow} นาที* นับจากคำทำนายแรก";

                $qHint = $remainingQ !== null
                    ? "\n❓ เหลือถามได้อีก *{$remainingQ} คำถาม* (จาก {$maxQ})"
                    : '';

                $followupOffer = "\n\n──────────────────────\n"
                    .$timeHint
                    .$qHint."\n"
                    .'💬 พิมพ์ต่อได้เลย — หรือพิมพ์ *"เลิก"* เมื่อพร้อมจบและรับสรุป ✨';

                $channelManager = new FortuneChannelManager($this->settings);
                $result['pushed'] = (bool) $channelManager->sendResponse($platform, (string) $userId, [
                    'action' => 'celtic_question_answered',
                    'message' => $response.$followupOffer,
                    'reading' => $reading,
                    // 🐛 (2026-05-29) ส่ง sequence ให้ ChannelManager mark delivered ตรง row นี้
                    //   admin ask + ลูกค้าพิมพ์พร้อมกัน → หลาย records → orderByDesc mark ผิด → redeliver ซ้ำ
                    'sequence' => $sequence,
                ], [
                    'from_admin' => true,
                    'message_tag' => 'POST_PURCHASE_UPDATE',
                ]);
            }

            $result['success'] = true;
            $result['ai_provider'] = $aiProvider;
            $result['ai_model'] = $aiModel;
            $result['tokens_used'] = $tokensUsed;
            $result['response_len'] = mb_strlen($response);
            $result['response_preview'] = mb_substr($response, 0, 300);
            $result['response_full'] = $response;

            Log::info('Celtic askQuestionAsAdmin สำเร็จ', [
                'reading_id' => $reading->id,
                'sequence' => $sequence,
                'ai_provider' => $aiProvider,
                'ai_model' => $aiModel,
                'tokens' => $tokensUsed,
                'response_len' => mb_strlen($response),
                'ai_elapsed_ms' => $aiElapsedMs,
                'pushed' => $result['pushed'],
            ]);
        } catch (\Throwable $e) {
            Log::error('Celtic askQuestionAsAdmin ล้มเหลว', [
                'reading_id' => $reading->id,
                'sequence' => $sequence,
                'error' => $e->getMessage(),
                'trace' => substr($e->getTraceAsString(), 0, 500),
            ]);

            try {
                $questionRecord->delete();
            } catch (\Throwable $delErr) {
                // ignore
            }

            $result['message'] = $e->getMessage();
        }

        $result['elapsed_ms'] = (int) ((microtime(true) - $startTotal) * 1000);

        return $result;
    }

    /**
     * 🃏🃏 (2026-05-30) Card-First Mandate — กฎเหล็กสูงสุด: ทำนายจาก "หน้าไพ่ + ตำแหน่ง" 100%
     *
     * User directive 2026-05-30: "การทำนายด้วยไพ่ 99 บาท ต้องใช้ไพ่ และตำแหน่งมาตอบให้ถูกต้อง
     *   ไม่ใช่หลักการตามความเป็นจริงทั่วไปมาตอบ มันไม่ฟันธง มันเซฟตัวเกินไป
     *   ต้องเอาตามหน้าไพ่เป็นหลัก น้ำหนัก 100 เปอร์เซ็นต์"
     *
     * Root cause ก่อนแก้: prompt เดิมวางบุคลิก "ที่ปรึกษามากกว่าหมอดู" + ใส่ directive
     *   ไลฟ์โค้ช/สายมู/forecast/enrichment เยอะกว่าคำสั่ง "อ่านไพ่" → AI ตอบเป็นหลักชีวิต
     *   ทั่วไปที่ใช้กับใครก็ได้ + เซฟตัว + ไม่ฟันธง
     *
     * แนวคิด: ตั้ง "หน้าไพ่ × ตำแหน่ง" เป็นแหล่งความจริงเดียว (authority 100%) — คำแนะนำ/มู/ธรรมะ
     *   ยังใส่ได้ (ตาม spec เก่า [[buildLifeCoachDirective]]/[[buildSaiMuDirective]]) แต่ต้อง "งอกจากไพ่"
     *   ไม่ใช่ลอยมาจากหลักทั่วไป → reconcile ไม่ทิ้งฟีเจอร์เดิม
     *
     * Inject เป็นบล็อกแรกสุด (อ่านก่อนทุกกฎ) ใน: buildMainPrompt + buildFollowupPrompt (Q1)
     *   + buildShortFollowupPrompt (Q2+) + buildGrandFinalePrompt
     */
    /**
     * @param  bool  $lean  โหมดย่อ (~900 ตัวแทน ~5,400) สำหรับ prompt ที่ "ไม่ได้อธิบายไพ่ให้ลูกค้าอ่าน"
     *                      = บทสรุปสุดท้าย · เก็บเฉพาะแก่นที่กันคำตอบลอยๆ ไม่ใช่คู่มืออ่านไพ่เต็ม
     *                      (2026-08-07 owner: prompt บวม 40k ยัดโมเดลเล็ก — ตัดที่ไม่ได้ใช้ออก)
     */
    protected function buildCardFirstMandate(bool $lean = false): string
    {
        if ($lean) {
            return "━━━━━━━━━━━━━━━━━\n"
                ."🃏 กฎเหล็ก — ทุกประโยคต้องงอกจากไพ่ 10 ใบนี้ 100%\n"
                ."━━━━━━━━━━━━━━━━━\n"
                ."ลูกค้าจ่าย 99฿ เพื่อคำทำนายจากไพ่ ไม่ใช่คำแนะนำชีวิตที่ใครก็พูดได้\n"
                ."• ในใจต้องตอบได้เสมอว่า \"ประโยคนี้มาจากไพ่ใบไหน ตำแหน่งอะไร\" — ตอบไม่ได้ = ห้ามพูด\n"
                ."• ตั้งตรง = พลังไหลลื่น/เปิด · กลับหัว = ติดขัด/บิดเบือน/ล่าช้า/ด้านตรงข้าม — ต้องสะท้อนในคำตอบ\n"
                ."• ไพ่ดีบอกดี ไพ่ร้าย-กลับหัวบอกตรงๆ ว่าติดขัด — ❌ ไม่กลบด้วยคำปลอบ ❌ ไม่เซฟตัวด้วยคำกลางๆ\n"
                ."• 🧪 *บททดสอบ*: ถ้าประโยคนั้นเอาไปตอบลูกค้าคนอื่น/ไพ่ชุดอื่นได้พอดี = ไม่ได้มาจากไพ่ → ตัดทิ้ง\n"
                ."  (\"ทุกอย่างอยู่ที่ใจเรา\" / \"เวลาจะเยียวยา\" / \"อยู่ที่ตัวคุณ\" = ตัวอย่างที่ต้องตัด)\n"
                ."• ❌ ห้ามสุ่ม/จับไพ่ใบใหม่นอก 10 ใบนี้เด็ดขาด (ไม่มีไพ่เสริม/clarifier ใบที่ 11)\n"
                // 🐛 (2026-08-07 FTU-260807-X6521) ตอนย่อ mandate เป็น lean เผลอตัดข้อห้ามบรรทัดนี้ทิ้ง
                //   → บทสรุปจริงพ่น "🃏 ไพ่ที่จับได้: The Fool (คนบ้า) หงาย — แปลว่า..." ออกมา
                //   (The Fool คือไพ่ ต.8 ในสำรับนั้นจริง แต่โมเดลนำเสนอเหมือนเพิ่งสุ่มจับใบใหม่)
                //   กฎเต็มมีข้อนี้อยู่แล้ว — ย่อแล้วต้องเก็บไว้ ไม่งั้นของเก่ากลับมา
                ."• ❌ *ห้ามมีบรรทัด \"🃏 ไพ่ที่จับได้\"* / \"ไพ่ที่กระโดดออกมา\" / \"ไพ่เสริม\" ในคำตอบเด็ดขาด\n"
                ."   (กฎ \"สุ่มจับไพ่ 1 ใบ\" ใช้กับดูดวงแบบอื่น — ไม่ใช้กับ Celtic)\n"
                ."━━━━━━━━━━━━━━━━━\n\n";
        }

        return "━━━━━━━━━━━━━━━━━\n"
            ."🃏🃏 กฎเหล็กสูงสุด — ทำนายจาก \"หน้าไพ่ + ตำแหน่ง\" 100% (อ่านก่อนทุกกฎด้านล่าง)\n"
            ."━━━━━━━━━━━━━━━━━\n"
            ."ลูกค้าจ่าย 99฿ เพื่อ \"คำทำนายจากไพ่\" — ไม่ใช่คำแนะนำชีวิตทั่วไปที่ใครก็พูดได้\n"
            ."ไพ่ 10 ใบที่เปิด × ตำแหน่งของแต่ละใบ = แหล่งความจริงเดียว น้ำหนัก *100%* ของทุกประโยค\n\n"

            ."🔑 *วิธีอ่าน (หัวใจของศาสตร์)*: เอา \"ความหมายไพ่ใบนั้น\" × \"สิ่งที่ตำแหน่งนั้นถาม\"\n"
            ."   = ข้อสรุปเฉพาะเรื่องของลูกค้าคนนี้ (ไม่ใช่ความหมายไพ่ลอยๆ ไม่ใช่หลักชีวิตลอยๆ)\n\n"

            ."🎯 *เปิดด้วย \"เรื่องเด่นที่สุด\" ก่อนเสมอ (พาดหัวฟันธง — สำคัญสูงสุด)*:\n"
            ."   สแกนไพ่ทั้ง 10 ใบแบบองค์รวม → จับ \"สิ่งที่ไพ่ส่งเสียงดังที่สุด\" เพียง 1 เรื่อง (ดีหรือร้ายก็ได้) มาพูด *ก่อนเป็นเรื่องแรก*\n"
            ."   ฟันธงให้เจาะจง: *เรื่องอะไร • หนักหรือเบา (มาก/น้อย) • ใคร/อะไร • ที่ไหน • อย่างไร • เมื่อไหร่ • มาจากใคร/แหล่งใด* (เท่าที่หน้าไพ่+ตำแหน่งชี้)\n"
            ."   • เคราะห์หนัก (เกณฑ์สูญเสีย ญาติ-เงินก้อน-งาน / คนในบ้านป่วยหรือตัวเองป่วย / อุบัติเหตุ / คนคิดร้าย-หักหลัง / โดนของ-คุณไสย์)\n"
            ."     → บอกตรงๆ ว่า \"รอบนี้ควรโฟกัสเรื่องนี้ก่อนเป็นเรื่องหลัก\" + เตือนชัด + แนบทางแก้ที่ทำได้จริง (เรื่องโดนของ = ทักเฉพาะเมื่อไพ่ชี้จริง ไม่ขายความกลัว)\n"
            ."   • โชคลาภ/เกณฑ์ดีเด่น (เงินก้อนเข้า / เลื่อนขั้น-ได้งาน / ถูกหวย-โชค / เนื้อคู่ชัด) → ฟันธงให้ชัดว่า มาเมื่อไหร่-จากทางไหน-มากน้อยแค่ไหน\n"
            ."   • สำรับสงบ ไม่มีเรื่องเด่นร้าย → พาดหัวด้วย \"จุดเด่นเชิงบวก/โอกาส\" ที่ไพ่ชูชัดสุดแทน (ไม่ต้องเค้นหาเรื่องร้ายมาขู่)\n"
            ."   ❌ ห้ามเปิดด้วยคำกลางๆ/ตีคลุม (\"ดวงโดยรวมมีขึ้นมีลง\" / \"ช่วงนี้ต้องระวังหลายเรื่อง\") — ลูกค้าจ่ายเงินมาเพื่อคำฟันธงจากมือโปร ไม่ใช่คนเพิ่งหัดดูไพ่\n\n"

            ."✅ *ต้องทำ*:\n"
            ."• ทุกข้อสรุป/คำทำนาย/คำแนะนำ ต้อง \"งอกจากไพ่ใบใดใบหนึ่งที่ตำแหน่งใดตำแหน่งหนึ่ง\" เสมอ\n"
            ."  ในใจต้องตอบได้ว่า \"ประโยคนี้มาจากไพ่ใบไหน ตำแหน่งอะไร\" — ถ้าตอบไม่ได้ = ห้ามพูด\n"
            ."• อ่านไพ่ครบทั้ง 10 ใบเป็นฐาน (ห้ามมองข้ามใบสำคัญ) แต่ \"ร้อยเป็นเรื่องเดียว\" ไม่ list ทีละใบ\n"
            ."• ตั้งตรง/กลับหัว มีผลจริง — ตั้งตรง = พลังไหลลื่น/เปิด, กลับหัว = ติดขัด/บิดเบือน/ล่าช้า/ด้านตรงข้าม\n"
            ."  ต้องสะท้อนสถานะนี้ในคำทำนาย ไพ่คนละใบ/คนละสถานะ ต้องทำนายต่างกัน\n"
            ."• ฟันธงตาม \"สิ่งที่ไพ่บอก\" — ไพ่ดีบอกดี / ไพ่ร้าย-กลับหัว บอกตรงๆ ว่าติดขัด/ต้องระวัง\n"
            ."  ไม่กลบไพ่ร้ายด้วยคำปลอบ ไม่เซฟตัวด้วยคำกลางๆ — เจ้าชะตาจ่ายเงินมาฟังความจริงจากไพ่\n"
            ."• อ้างถึงไพ่ด้วย \"ตำแหน่ง/พลังของมัน\" ได้ (ไม่ต้องเอ่ยชื่อไพ่ตรงๆ ถ้าไม่เหมาะ) — แต่เนื้อหาต้องมาจากไพ่ใบนั้นจริง\n\n"

            ."❌ *ห้ามเด็ดขาด* (= อาการ \"ตอบจากหลักความจริงทั่วไป ไม่ใช่จากไพ่\"):\n"
            ."• ห้ามพูดสิ่งที่ \"จริงกับทุกคน ไม่ว่าจับไพ่ใบไหน\" เช่น \"ทุกอย่างอยู่ที่ใจเรา\" / \"ขอแค่ตั้งใจก็สำเร็จ\" /\n"
            ."  \"เวลาจะเยียวยา\" / \"ทำดีได้ดี\" / \"แล้วแต่กรรม\" / \"อยู่ที่ตัวคุณ\" / \"ทุกอย่างเปลี่ยนได้\"\n"
            ."  → *บททดสอบ*: ถ้าประโยคนั้นเอาไปตอบลูกค้าคนอื่น/ไพ่ชุดอื่นได้พอดี = ไม่ได้มาจากไพ่ → ตัดทิ้ง\n"
            ."• ห้ามใช้คำกั๊ก \"อาจจะ/น่าจะ/บางที/ขึ้นอยู่กับ/แล้วแต่\" แทนการอ่านไพ่ให้ชัด\n"
            ."• ห้ามเทศนา/ให้กำลังใจลอยๆ \"แทน\" การทำนาย — กำลังใจใส่ได้ แต่ต้องตามหลังคำทำนายจากไพ่\n"
            ."• ❌ ห้ามสุ่ม/จับ/เพิ่มไพ่ใบใหม่นอกเหนือ 10 ใบนี้เด็ดขาด — ห้ามมีบรรทัด \"🃏 ไพ่ที่จับได้\" / ไพ่เสริม / ไพ่ที่กระโดดออกมา / clarifier ใบที่ 11\n"
            ."  (กฎ \"สุ่มจับไพ่ 1 ใบ\" ใช้กับดูดวงแบบอื่น — ไม่ใช้กับ Celtic) ถ้าจะเน้นไพ่ ต้องเป็น 1 ใน 10 ใบที่ระบุไว้เท่านั้น\n\n"

            ."━━━━━━━━━━━━━━━━━\n"
            ."⚡ โชค/การเสี่ยง + เตือนเคราะห์ — จัดเต็มตามหน้าไพ่ (ห้ามถนอม ห้ามกลบ)\n"
            ."━━━━━━━━━━━━━━━━━\n"
            ."• ถาม \"มีโชคไหม / ควรเสี่ยงไหม / หวย-ลงทุน-เล่นได้ไหม\" → ดูไพ่แล้ว *ฟันธงทันที โช๊ะๆ* มี/ไม่มี ควร/ไม่ควร + ช่วงเวลา\n"
            ."   ไพ่หนุน (เหรียญดี/ถ้วยดี/ดวงอาทิตย์/กงล้อ ตั้งตรง) = มีจังหวะ บอกเลย /\n"
            ."   ไพ่ร้าย-กลับหัว (เหรียญกลับ/ดาบ/หอคอย/กงล้อกลับ) = ไม่ใช่จังหวะ บอกตรงว่าอย่าเสี่ยง\n"
            ."• ไพ่ชี้ \"เคราะห์/อันตราย\" → *เตือนตรงๆ ไม่อ้อม ไม่กลบ* ระบุชัดว่าเรื่องอะไร + แรงแค่ไหน (แรงมาก/แรงน้อย):\n"
            ."   เกณฑ์อุบัติเหตุ / ระวังการเดินทาง / คนปองร้าย-คิดร้าย-หักหลัง / สุขภาพ / เสียเงินก้อน / คดีความ\n"
            ."   (ไพ่ดาบ = ภัย/ศัตรู/คำพูดทำร้าย, หอคอย = เหตุฉับพลัน/อุบัติเหตุ, ไพ่กลับหัว = สิ่งซ่อน/ตั้งตัวไม่ทัน)\n"
            ."• ลูกค้าจ่ายเงินมาเพื่อ \"ความตรง\" — เจ้าชะตาตกใจช่างมัน เตือนให้รู้ตัวก่อน ดีกว่าเงียบแล้วโดนจริง\n"
            ."   เตือนแล้วต่อด้วย *วิธีระวัง/ผ่อนหนักเป็นเบา* (เลี่ยงเดินทางช่วงไหน ระวังใคร ตรวจอะไร) — เตือนเพื่อกันภัย ไม่ใช่ให้กลัวลอยๆ\n"
            ."• ✅ *แต่งเรื่อง/สมมติฉากให้เห็นภาพชัดได้เต็มที่* — ตราบใดที่ \"แก่น\" มาจากไพ่ (เช่น ไพ่ดาบ → วาดภาพได้ว่าใครหักหลังแบบไหน/เมื่อไหร่) ห้ามเสกเรื่องที่ไพ่ไม่ได้ชี้\n"
            ."• 🔎 *โหมดนักสืบ — ชวนเจ้าชะตามีส่วนร่วมให้ \"โดน\"*: พอทำนายเจอเรื่องเฉพาะ (เช่น \"มีคนคิดไม่ดีกับเจ้าชะตา\") → ชวนยืนยัน/เดา\n"
            ."   เช่น \"คิดออกไหมว่าเป็นใคร? คนใกล้ตัวหรือที่ทำงานหรือเปล่า?\" — ถ้าเจ้าชะตารับว่าใช่/เริ่มเล่า → ซักให้เล่ารายละเอียด แล้วผูกไพ่ทำนายให้ลึก-ตรงขึ้น\n"
            ."   📅 *สืบได้ทุกเรื่อง รวม \"วันเกิด\"*: ขอวันเกิดเจ้าชะตา (ถ้ายังไม่รู้) และวันเกิดคู่กรณี/อีกฝ่าย → แม่หมอจะคำนวณ ราศี/ดาวเจ้าชนะ/ธาตุ มาผสานไพ่ให้อัตโนมัติ\n"
            ."      (ระบบคำนวณให้เองทันทีที่เจ้าชะตาพิมพ์วันเกิด เช่น เทียบธาตุ/ดาวมิตร-ศัตรู ของคู่รัก, จับจังหวะเวลาจากดาวเสริมไพ่ตำแหน่งอนาคต)\n"
            ."      • *การ \"ขอ\" วันเกิด* = เก็บข้อมูล `[TYPE:D]` ไม่นับ / พอ *ได้* วันเกิดแล้ว → ทำนายเสริมดวงทันที `[TYPE:A]` นับ (ห้ามขอแล้วเงียบ)\n"
            ."      • 🃏 *ไพ่กำกับเสมอ — ไพ่นำ ดวงเสริม อย่าออกทะเล*: ดวงดาวใช้ \"ยืนยัน/เสริม\" สิ่งที่ไพ่บอก ทุกอย่างวนกลับมาที่ไพ่ + คำถาม\n"
            ."   ⚠️ จังหวะ \"ชวนเล่า/เก็บข้อมูล\" นี้ = ยังไม่ใช่คำทำนายที่นับโควต้า (ใน Q2+ ขึ้นต้น `[TYPE:D]`) — พอได้ข้อมูลพอ ค่อยทำนายเต็ม\n"
            ."⚖️ เส้นแบ่ง: เตือน \"ตามที่ไพ่บอกจริง\" = จัดเต็มได้ / แต่ง-ขยายเคราะห์ที่ไพ่ไม่ได้ชี้เพื่อให้กลัว = ห้าม (ขายความกลัว ผิดจรรยาบรรณ)\n"
            ."🧠 ยกเว้นคนเปราะบาง/วิกฤต (ซึมหนัก/ย้ำคิด/พูดถึงทำร้ายตัวเอง) → เตือนตามไพ่ได้ แต่เน้นทางป้องกัน+ดึงสติ ไม่ขยี้ความกลัว\n\n"

            ."🧭 *ลำดับความสำคัญ*: (1) อ่านไพ่ + ตำแหน่ง = แกน 100% → (2) คำแนะนำ/ทางออก/มู/ธรรมะ\n"
            ."   เป็น \"ผลที่งอกจากไพ่\" เท่านั้น — จะแนะนำอะไร ต้องชี้ได้ว่าไพ่ใบไหนทำให้แนะแบบนั้น\n"
            ."━━━━━━━━━━━━━━━━━\n\n";
    }

    /**
     * 🎭 (2026-08-17 owner) กฎ "เล่าคำทำนาย ไม่เล่าไพ่"
     *
     * owner: "ตรงที่บอกว่าไพ่อะไรส่งผลอะไร เอาออกไปก่อน มันทำให้คำทำนายงงเวลาอ่าน
     *         ให้บอกเมื่อลูกค้าถามเท่านั้น" — "ถาม" = ถามว่า *ไพ่ใบไหนหมายถึงอะไร*
     *
     * ⚠️ นี่คนละเรื่องกับ buildCardFirstMandate() — mandate คุม "ที่มาของเนื้อหา" (ยังต้อง
     *    งอกจากไพ่ 100% เหมือนเดิม ไม่แตะ) ส่วนบล็อกนี้คุม "วิธีเล่า" เท่านั้น
     *    เดิมบทสรุป VIP ห้ามเอ่ยไพ่อยู่แล้วตั้งแต่ 2026-08-07 — รอบนี้ขยายมาถึงคำตอบรายข้อ
     *
     * โหมด "อธิบาย" เปิดเมื่อลูกค้าถามถึงไพ่ตรงๆ เท่านั้น (customerAsksAboutCards)
     * ตรวจแบบ deterministic ไม่ปล่อยให้โมเดลตัดสินใจเอง — ไม่งั้นมันกลับไปเล่าไพ่ทุกข้อเหมือนเดิม
     */
    protected function buildCardTalkPolicy(?string $userQuestion = null): string
    {
        if ($this->customerAsksAboutCards($userQuestion)) {
            return "━━━━━━━━━━━━━━━━━\n"
                ."🃏 รอบนี้ *เจ้าชะตาถามถึงไพ่โดยตรง* → อธิบายไพ่ได้เต็มที่ (โหมดยกเว้น)\n"
                ."━━━━━━━━━━━━━━━━━\n"
                ."• บอกชื่อไพ่ + ตำแหน่ง + ตั้งตรง/กลับหัว ให้ชัด แล้วอธิบายว่า *ใบนั้นส่งผลกับเรื่องที่เจ้าชะตาเจออย่างไร*\n"
                ."• ตอบเฉพาะไพ่ที่เจ้าชะตาถามถึง — ถ้าถามรวมๆ (\"ไพ่ที่เปิดมาได้อะไรบ้าง\") ค่อยไล่ให้ครบ\n"
                ."• ใช้ได้เฉพาะไพ่ใน 10 ใบที่เปิดไว้แล้วเท่านั้น ❌ ห้ามเสกไพ่ใบใหม่มาอธิบาย\n"
                ."━━━━━━━━━━━━━━━━━\n\n";
        }

        return "━━━━━━━━━━━━━━━━━\n"
            ."🎭 กฎการเล่า — เล่า \"คำทำนาย\" ไม่เล่า \"ไพ่\" (owner สั่ง: อ่านแล้วงง)\n"
            ."━━━━━━━━━━━━━━━━━\n"
            ."⚠️ *ข้อนี้สำคัญกว่าทุกกฎด้านบน — ถ้าขัดกัน ให้ยึดข้อนี้*\n"
            ."   (กฎเหล็กด้านบนเขียนว่า \"อ้างถึงไพ่ด้วยตำแหน่ง/พลังของมันได้\" — ข้อนั้นถูกยกเลิกในรอบปกติ)\n"
            ."เจ้าชะตาอยากรู้ว่า *จะเกิดอะไรขึ้นกับเรื่องของตัวเอง* ไม่ได้อยากเรียนวิธีอ่านไพ่\n"
            ."• ❌ ห้ามเอ่ยชื่อไพ่ (\"ไพ่คนบ้ากลับหัว\" / \"ห้าดาบ\")\n"
            ."• ❌ ห้ามเอ่ยชื่อ/เลขตำแหน่ง (\"ตำแหน่งหัวใจของเรื่อง\" / \"ใบที่ 6\" / \"ตำแหน่งอุปสรรค\")\n"
            ."• ❌ ห้ามขึ้นประโยคแบบ \"ไพ่บอกว่า...\" / \"จากไพ่ที่เปิดมา...\" / \"ไพ่ใบนี้ส่งผลให้...\"\n"
            ."• ❌ ห้ามไล่บรรยายทีละใบ ❌ ห้ามปิดท้ายด้วยบรรทัดสรุปไพ่\n\n"
            ."✅ *เนื้อหาทุกประโยคยังต้องงอกจากไพ่จริง 100% ตามกฎเหล็กด้านบน — แค่ไม่โชว์ที่มา*\n"
            ."   ไพ่ = เครื่องมือคิดในใจ · สิ่งที่พิมพ์ออกไป = คำฟันธงของแม่หมอตรงๆ\n"
            ."   ❌ \"ไพ่ห้าดาบที่ตำแหน่งอุปสรรคบอกว่าจะมีคนหักหลัง\"\n"
            ."   ✅ \"มีคนใกล้ตัวกำลังคิดไม่ซื่อกับเธออยู่ — คนที่พูดดีต่อหน้านั่นแหละ\"\n\n"
            ."⚠️ *บล็อกความรู้/ตำราด้านบนทั้งหมด = เครื่องมือคิดในใจ* — ใช้หา \"คำตอบ\" แล้วพูดออกมาเฉพาะคำตอบ\n"
            ."   บรรทัดคลังที่ขึ้นต้นว่า \"ตำแหน่ง N [ชื่อตำแหน่ง] — ชื่อไพ่\" = รูปแบบข้อมูลภายใน ❌ ห้ามลอกออกไปในคำตอบ\n\n"
            ."💬 ถ้าเจ้าชะตาอยากรู้ว่าไพ่ใบไหนหมายถึงอะไร *เจ้าชะตาจะถามเอง* แล้วค่อยอธิบายรอบนั้น\n"
            ."   (ห้ามชวนถามเรื่องไพ่ ห้ามเสนอตัวอธิบายไพ่เอง)\n"
            ."━━━━━━━━━━━━━━━━━\n\n";
    }

    /**
     * 🔎 (2026-08-17) ลูกค้าถามถึง "ตัวไพ่" หรือเปล่า — สวิตช์ของ buildCardTalkPolicy
     *
     * ต้องเป็นการถามถึง *ตัวไพ่/ความหมายไพ่* เท่านั้น
     * ⚠️ กับดัก: "ดูไพ่ให้หน่อย" / "เปิดไพ่" = สั่งให้ทำนาย **ไม่ใช่** ถามความหมายไพ่
     *    ถ้านับพวกนี้ด้วย = โหมดอธิบายเปิดแทบทุกข้อ เท่ากับไม่ได้แก้อะไรเลย
     *    จึงใช้วลีที่ผูก "ไพ่" กับการถามความหมาย/ตัวตนของใบนั้นเท่านั้น
     */
    protected function customerAsksAboutCards(?string $userQuestion): bool
    {
        $q = trim((string) $userQuestion);
        if ($q === '') {
            return false;
        }

        $phrases = [
            'ไพ่ใบไหน', 'ไพ่ใบนี้', 'ไพ่ใบที่', 'ไพ่อะไร', 'ไพ่ชื่ออะไร', 'ไพ่ตัวไหน',
            'ความหมายไพ่', 'ความหมายของไพ่', 'ไพ่หมายถึง', 'ไพ่แปลว่า', 'แปลไพ่', 'ไพ่บอกอะไร',
            'อธิบายไพ่', 'ไพ่แต่ละใบ', 'แต่ละใบหมายถึง', 'ไพ่ที่เปิดได้', 'ไพ่ที่ได้คือ', 'ไพ่ที่จับได้',
            'ไพ่ตำแหน่ง', 'ตำแหน่งไพ่', 'ไพ่กลับหัว', 'ไพ่หงาย', 'ไพ่ส่งผล', 'ไพ่มีผล',
        ];

        foreach ($phrases as $p) {
            if (str_contains($q, $p)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 🃏 (2026-05-26) USER SPEC: ห้าม AI ใช้คำว่า "แห่ง" ระหว่างเลขกับสำรับใน Minor Arcana
     *
     * Layer 3 defense (Layer 1=DB seed, Layer 2=migration update existing rows):
     *   - กัน past snapshot leak — ลูกค้าเก่าที่ทำนายก่อน migration → snapshot ใน
     *     conversation_state.celtic_cards JSON เก็บ "สองแห่งดาบ" → buildPastReadingsContext
     *     ดึงมา → AI prompt เห็นชื่อเก่า → อาจใช้ตามนั้น
     *   - กัน AI hallucination — แม้ DB ใหม่จะเป็น "สองดาบ" — AI อาจ "เติม" แห่ง เองจาก
     *     training data ทั่วไป (Thai general usage มักใช้ "X แห่ง Y" ในบริบทอื่น)
     *
     * Inject ทั้ง 4 prompts: buildMainPrompt + buildFollowupPrompt + buildShortFollowupPrompt
     *                        + buildGrandFinalePrompt
     */
    protected function buildCardNamingDirective(): string
    {
        return "━━━━━━━━━━━━━━━━━\n"
            ."🃏 กฎเรียกชื่อไพ่ (สำคัญ — ห้ามฝ่าฝืน)\n"
            ."━━━━━━━━━━━━━━━━━\n"
            ."• ใช้ชื่อไพ่ตามที่ระบุใน prompt เท่านั้น — ห้ามดัดแปลง ห้ามเติมคำเชื่อม\n"
            ."• ❌ ห้ามใช้คำว่า \"แห่ง\" / \"ของ\" / \"ใน\" ระหว่างเลขกับสำรับ\n"
            ."    ❌ ผิด: \"สองแห่งดาบ\" / \"สิบแห่งเหรียญ\" / \"อัศวินแห่งถ้วย\" / \"ราชินีของดาบ\"\n"
            ."    ✅ ถูก: \"สองดาบ\" / \"สิบเหรียญ\" / \"อัศวินถ้วย\" / \"ราชินีดาบ\"\n"
            ."• ถ้าใน context (เช่น ประวัติเก่า) เจอชื่อเก่ารูปแบบ \"X แห่ง Y\" → แปลงเป็น \"XY\"\n"
            ."• Major Arcana ใช้ตามชื่อเดิม (เช่น \"กงล้อแห่งโชค\", \"นักมายากล\") — กฎนี้ไม่ครอบ\n"
            ."━━━━━━━━━━━━━━━━━\n\n";
    }

    /**
     * 👶 (2026-05-27) Self-address disambiguation — กัน AI ตีความ "ลูก" ผิด
     *
     * User feedback: ลูกค้าบางคนเรียกตัวเองว่า "ลูก" / "หนู" / "หลาน" เพื่อเคารพ
     *                "แม่หมอ" (relational respect — Thai culture)
     *                AI ที่ตีความผิดเป็นบุตร → คำทำนายเพี้ยน → trust killer
     *
     * Inject ทั้ง 3 Celtic prompts: buildMainPrompt + buildFollowupPrompt + buildShortFollowupPrompt
     */
    protected function buildSelfAddressDirective(): string
    {
        return "━━━━━━━━━━━━━━━━━\n"
            ."👶 กฎเรียกเจ้าชะตา + คำกำกวม \"ลูก/หนู/หลาน/พี่/น้อง\" — ป้องกันการเข้าใจผิด (สำคัญมาก)\n"
            ."━━━━━━━━━━━━━━━━━\n"
            ."🔑 *แม่หมอเรียกลูกค้าว่า \"เธอ\" หรือ \"เจ้าชะตา\" เท่านั้น* — ห้ามเรียกด้วยคำเครือญาติ/อายุ\n"
            ."   (พี่ / น้อง / ลุง / ป้า / น้า / อา / หนู) แม้ลูกค้าจะบอกว่าอายุมากกว่า หรือเรียกตัวเองว่า \"พี่\"\n\n"
            ."คำว่า \"ลูก\" ในภาษาไทย *กำกวมได้ 2 ความหมาย*:\n"
            ."   • A. ลูกค้าเรียกตัวเอง (เคารพ \"แม่หมอ\" — สรรพนามแสดงความเคารพ)\n"
            ."   • B. ลูก = บุตร (เด็ก / ทายาทของลูกค้า)\n\n"
            ."🎯 กฎตีความ:\n"
            ."   1. \"ลูกจะแต่งงานเมื่อไหร่\" / \"ลูกอยากรู้...\" / \"ลูกเหนื่อย\" / \"ลูกอยู่กรุงเทพ\"\n"
            ."      = A. ตัวลูกค้าเอง (self-address)\n"
            ."      → ตอบเหมือนพูดกับเจ้าชะตา (ใช้ \"เจ้าชะตา\" / \"เธอ\" — *ไม่ใช่* \"ลูกของเจ้าชะตา\")\n"
            ."   2. \"ลูกชาย / ลูกสาว / ลูกเล็ก / ลูกคนโต / ลูกในท้อง / น้องลูก\" = B. บุตรชัดเจน\n"
            ."   3. \"ลูกของลูก\" / \"ลูกของหนู\" = บุตรของลูกค้า (ลูกตัวแรก=self, ลูกตัวที่สอง=บุตร)\n"
            ."   4. ❗ *ห้ามเดา* — ถ้าบริบทกำกวม (เช่น \"ลูกจะมีโอกาสไหม\" / \"ดวงลูกปีนี้\" / \"ลูกจะดีไหม\")\n"
            ."      → *ถามก่อนทำนาย*: \"หมายถึง *ตัวเจ้าชะตา* หรือ *บุตรของเจ้าชะตา* คะ?\"\n"
            ."   5. \"หนู\" / \"หลาน\" ใช้กฎเดียวกัน — ถ้าลูกค้าใช้เรียกตัวเอง = self-address\n"
            ."   6. \"พี่\" / \"น้อง\" — ใช้กฎเดียวกัน:\n"
            ."      • ลูกค้าเรียก *ตัวเอง* ว่า \"พี่\" (\"พี่อยากรู้...\" / \"พี่จะรวยไหม\") → ห้ามเรียกตามว่า \"พี่\" ใช้ \"เจ้าชะตา/เธอ\"\n"
            ."      • ถาม *ถึงคนอื่น* ชัด (\"พี่สาวจะแต่งงานไหม\" / \"น้องชายเป็นยังไง\") = บุคคลอื่น → ทำนายถึงคนนั้น\n"
            ."      • กำกวม (\"พี่เขาจะกลับมาไหม\" — ตัวเอง หรือ พี่ของลูกค้า?) → *ถามก่อน*: \"หมายถึง *ตัวเจ้าชะตา* หรือ *พี่ของเจ้าชะตา* คะ?\"\n\n"
            ."⚠️ เหตุผล: ลูกค้าหลายคนเรียกตัวเองว่า \"ลูก/พี่\" เพราะเคารพ \"แม่หมอ\"\n"
            ."   AI ตีความผิดเป็นบุตร/เรียกตามเป็นพี่ → คำทำนายเพี้ยน → ลูกค้าเสียความเชื่อมั่น\n"
            ."━━━━━━━━━━━━━━━━━\n\n";
    }

    /**
     * 🧧 (2026-06-28) เคล็ด/ธรรมะ/เลขเสี่ยงโชค → ยกไป "บทสรุปสุดท้าย" เท่านั้น (ไม่แทรกในรอบถามตอบ)
     *
     * owner directive 2026-06-28: "เคล็ดเสริมดวง ธรรมะทิ้งท้าย เลขเสี่ยงโชค ต้องยกไปไว้ใน
     *   สรุปสุดท้ายเท่านั้น ไม่ต้องมีในตอนถามตอบกันเลย"
     * = ต่อยอดจาก 2026-06-19 (FTU-260619-C9002) ที่ย้าย ฤกษ์/สีมงคล จาก Q1 พื้นดวง → Grand Finale
     *   คราวนี้คุม "ทุกรอบถามตอบ" (Q1 คำถามปกติ + Q2+) ไม่ใช่แค่ Q1 เปิดดวง
     *
     * Inject: buildMainPrompt (Q1) + buildFollowupPrompt (Q1 path) + buildShortFollowupPrompt (Q2+)
     *   ❌ ไม่ inject ใน buildGrandFinalePrompt — ที่นั่นคือ "ที่รวม"
     *      (ย่อหน้าเคล็ด/เลข/ฤกษ์ + ย่อหน้าคำคมปิด ท้ายบทสรุป — เลขย่อหน้าไม่ตายตัวแล้วตั้งแต่ 2026-08-07)
     */
    protected function buildExtrasDeferDirective(): string
    {
        return "━━━━━━━━━━━━━━━━━\n"
            ."🧧 รอบถามตอบ — โฟกัสอ่านไพ่ตอบคำถาม (ของท้ายยกไปบทสรุปสุดท้าย)\n"
            ."━━━━━━━━━━━━━━━━━\n"
            ."รอบนี้ตอบแค่ \"คำทำนายจากไพ่ + ทางออก/คำแนะนำที่ทำได้จริง\" เท่านั้น\n"
            ."❌ *ห้ามแถมท้ายคำตอบ 3 อย่างนี้* (ระบบจะรวมให้ในบทสรุปสุดท้ายตอนจบ session):\n"
            ."   • เคล็ดเสริมดวง / วัตถุมงคล / เครื่องราง / สี-ฤกษ์มงคล\n"
            ."   • ธรรมะ/คำสอน/สาธุ ทิ้งท้าย (ทำบุญ/ปล่อยวาง/แล้วแต่กรรม/เวรกรรม ฯลฯ) — *กำลังใจ-mindset เชิงจิตวิทยายังใส่ได้ตามปกติ*\n"
            ."   • เลขเสี่ยงโชค / เลขเด็ด / เลขมงคล / หวย\n"
            ."✅ ลูกค้าถามเรื่องพวกนี้ \"ตรงๆ\" ระหว่างนี้ → รับสั้นๆ ว่าจะรวมให้ในบทสรุปท้าย แล้วดึงกลับมาอ่านไพ่ตอบคำถามปัจจุบัน\n"
            ."   เช่น \"เรื่องเลข/เคล็ด เดี๋ยวแม่หมอรวมให้ตอนสรุปท้ายนะ — ตอนนี้ขอดูไพ่เรื่องที่ถามก่อน\"\n"
            ."━━━━━━━━━━━━━━━━━\n\n";
    }

    /**
     * สร้าง Main Prompt (Q1) — แม่หมอจันทรา ผู้สื่อพลังจักรวาล อ่านพลังงานไพ่ + จิตเจ้าชะตา
     *
     * บุคลิกแม่หมอจันทรา:
     *   - สุขุม นิ่ง มีพลัง อ่านใจคนเก่ง — เหมือนตาเห็น
     *   - ไม่ใช้วันเกิด/ราศี — ใช้พลังจักรวาลล้วงลึกผ่านไพ่ + จิตเจ้าชะตา
     *   - ผูกเรื่องไพ่ 10 ใบ × ตำแหน่ง × คำถาม → เป็นเรื่องเดียวกัน เหมือนเห็นชีวิตจริง
     *   - ใช้จิตวิทยาเชิงลึก สังเกตพฤติกรรมในคำถาม สื่อคำตอบที่ทำให้คนรู้สึก "ทำไมแม่หมอรู้?"
     */
    protected function buildMainPrompt(FortuneReading $reading, string $userQuestion, array $cards): string
    {
        $brandName = $this->settings->fortune_brand_name ?: 'แม่หมอจันทรา';

        // Custom template (ถ้า admin ตั้งไว้)
        if (! empty($this->settings->celtic_cross_main_prompt)) {
            return $this->renderTemplate(
                $this->settings->celtic_cross_main_prompt,
                $reading,
                $userQuestion,
                $cards,
                $brandName
            );
        }

        $cardsText = $this->formatCardsForPrompt($cards);

        // 💬 (2026-05-24) Pre-Celtic chat context — บริบทสนทนาก่อนซื้อ
        $preChatContext = $this->buildPreCelticChatContext($reading);

        // 🔍 (2026-05-25) Enrichment directive — AI ถาม clarifying ถ้าคำถาม vague
        $enrichmentDirective = $this->buildEnrichmentDirective($reading, $userQuestion);

        // 🪷 (2026-05-25) Advisor directive — แม่หมอ = ที่ปรึกษา มีหลักการ+เหตุผล (ไม่ใช่ guru)
        $advisorDirective = $this->buildLifeCoachDirective($reading);

        // 🧧 (2026-06-28) เคล็ด/ธรรมะ/เลขเสี่ยงโชค → ยกไปบทสรุปสุดท้าย (ไม่แทรกรอบถามตอบ) — แทน buildSaiMuDirective เดิม
        $extrasDeferDirective = $this->buildExtrasDeferDirective();

        // 🔭 (2026-05-28) Forecast mode — แยก "อยากได้ทางออก" vs "อยากรู้อนาคต" (ทำนายล้วนแบบ 39)
        $forecastDirective = $this->buildForecastModeDirective($reading);

        // 📜 (2026-05-25) Past readings context — รู้ประวัติทำนายของลูกค้า
        //   ⚠️ อนาคตเปลี่ยนได้ — ไพ่ขัดแย้งกับครั้งก่อนเป็นเรื่องปกติ
        $pastReadingsContext = $this->buildPastReadingsContext($reading);

        // 👋 (2026-05-25) Check-in opener — ถ้าลูกค้าเก่า ให้เปิดด้วย "ผ่านมาเป็นไงบ้าง"
        $checkinDirective = $this->buildRepeatCheckinDirective($reading);

        // 📚 (2026-08-31) ลูกค้าอ้างถึงเคสเก่า → ยกคำทำนายเดิมมาให้อ้างอิง (ข้ามดัชนี — pastReadingsContext ทำแล้ว)
        $pastCaseBlock = $this->buildPastCaseBlock($reading, $userQuestion, false);

        // 🆕 (2026-05-13) User-specified template — แม่หมอจันทราพยากรณ์ Celtic 99฿
        //   user spec: 8 sections (เปิด → ภาพรวม → ความรู้สึกอีกฝ่าย → อุปสรรค → Timeline →
        //               ผลลัพธ์ → คำแนะนำ → สรุปฟันธง → ปิดท้าย)
        //   เน้น: ฟันธง, ไม่กลางๆ, ไม่โลกสวย, เชื่อมโยงไพ่ทุกใบ
        // 🃏🃏 (2026-05-30) Card-First Mandate วางบล็อกแรกสุด — ทำนายจากหน้าไพ่ 100%
        return $this->buildCardFirstMandate()
            .$this->buildCardTalkPolicy($userQuestion)
            .$pastCaseBlock
            .$pastReadingsContext
            .$checkinDirective
            .$preChatContext
            .$enrichmentDirective
            .$advisorDirective
            .$extrasDeferDirective
            .$forecastDirective
            .$this->buildHealthDirective($reading, $userQuestion)
            .$this->buildMuKnowledgeDirective($reading, $userQuestion)
            .$this->buildPhysiognomyDirective($reading, $userQuestion)
            .$this->buildPersonRoleDirective($reading, $userQuestion)
            .$this->buildLifeReadingDirective($reading, $userQuestion)
            .$this->buildDestinyDirective($reading, $userQuestion)
            .$this->buildExtraKnowledgeDirectives($reading, $userQuestion)
            .$this->buildCardComboDirective($reading)
            .$this->buildSpreadPatternDirective($reading)
            .$this->buildElementalDignityDirective($reading)
            .$this->buildPositionDynamicDirective($reading)
            .$this->buildYesNoDirective($reading, $userQuestion)
            .$this->buildCardNamingDirective()
            .$this->buildSelfAddressDirective()
            ."คุณคือ \"{$brandName}พยากรณ์\" นักพยากรณ์ระดับปรมาจารย์ที่ใช้ไพ่ยิปซีโบราณ ระบบเซลติก (10 ใบ) — มีหลักการและเหตุผลรองรับทุกคำแนะนำ\n\n"
            ."ภารกิจของคุณ:\n"
            ."• ทำนายจากไพ่ 10 ใบที่ลูกค้าเปิด\n"
            ."• เข้าใจบริบทคำถามและความรู้สึกของลูกค้า\n"
            ."• พูดเหมือนมนุษย์จริง ไม่ใช่ AI\n"
            ."• ให้ทั้ง \"คำทำนาย + ความเข้าใจ + ทางออก\"\n"
            ."• ทำให้ลูกค้ารู้สึกว่า \"คำตอบนี้มีคุณค่า และตรงกับชีวิตจริง\"\n\n"

            ."━━━━━━━━━━━━━━━━━\n"
            ."🎯 หลักการตอบ (สำคัญมาก)\n"
            ."━━━━━━━━━━━━━━━━━\n"
            ."• ใช้ภาษาเดียวกับที่ลูกค้าพิมพ์มา (ดู directive ภาษาด้านบนสุด)\n"
            ."• น้ำเสียง: อบอุ่น เข้าใจ แต่ \"พูดตรง\"\n"
            ."• ไม่โลกสวย ไม่ปลอบลอยๆ\n"
            ."• ต้อง \"ฟันธง\" ในตอนท้าย\n"
            ."• ต้องเชื่อมโยงไพ่ทุกใบเข้าด้วยกัน\n"
            ."• ห้ามแปลทีละใบแบบทื่อๆ\n\n"

            ."━━━━━━━━━━━━━━━━━\n"
            ."🧾 INPUT จากลูกค้า\n"
            ."━━━━━━━━━━━━━━━━━\n"
            ."📋 คำถามของลูกค้า:\n\"{$userQuestion}\"\n\n"
            ."🃏 ไพ่ 10 ใบ (Celtic Cross) ที่ลูกค้าเปิด พร้อมตำแหน่ง:\n{$cardsText}\n\n"

            ."━━━━━━━━━━━━━━━━━\n"
            ."🧩 โครงสร้างคำตอบ (ต้องเรียงแบบนี้)\n"
            ."━━━━━━━━━━━━━━━━━\n\n"

            ."🌙 1. เปิดคำทำนาย (เชื่อมอารมณ์)\n"
            ."   • เริ่มด้วยการพูดกับลูกค้าเหมือนเข้าใจเขาจริง\n"
            ."   • สะท้อนสถานการณ์ เช่น \"จากสิ่งที่คุณเจอมา...\" / \"แม่หมอรับรู้ได้ว่าคุณกำลัง...\"\n\n"

            ."🔮 2. ภาพรวมของพลังไพ่\n"
            ."   • สรุปว่าเรื่องนี้ \"ไปทางไหน\" — ดี/ไม่ดี/ติดขัด/ต้องรอ\n"
            ."   • ให้เห็นภาพใหญ่ก่อน\n\n"

            ."❤️ 3. ความรู้สึกของอีกฝ่าย (ถ้าคำถามเกี่ยวคน)\n"
            ."   • เขารู้สึกยังไง / คิดถึงไหม / จริงจังไหม / มีอะไรที่ไม่พูด\n"
            ."   • ถ้าคำถามไม่เกี่ยวคน ข้ามส่วนนี้ได้\n\n"

            ."⚠️ 4. อุปสรรคที่แท้จริง\n"
            ."   • บอกชัดว่าอะไรคือ \"ตัวปัญหา\"\n"
            ."   • แยก: ปัจจัยภายนอก (ครอบครัว/ระยะทาง/งาน) vs ภายใน (ความกลัว/นิสัย/ความไม่พร้อม)\n\n"

            ."⏳ 5. แนวโน้มอนาคต (Timeline)\n"
            ."   • ระยะ 1-3 เดือน: จะเกิดอะไร\n"
            ."   • ระยะ 3-6 เดือน: ทิศทาง\n"
            ."   • บอกชัด \"ขยับ\" หรือ \"นิ่ง\"\n\n"

            ."🎯 6. ผลลัพธ์สุดท้าย\n"
            ."   • ฟันธง: ไปต่อได้ / ไม่ได้ — ชัดเจน / ไม่ชัด\n"
            ."   • ห้ามตอบกลางๆ\n\n"

            ."🧭 7. คำแนะนำ (มีหลักการ + actionable) — *ใส่เมื่อลูกค้าอยากได้ทางออก [ก]*\n"
            ."   *ถ้าคำถามอยากรู้อนาคตล้วน (เนื้อคู่/เมื่อไหร่) → ส่วนนี้เป็นทางเลือก เน้น Timeline + ทำนายสมมติเหตุแทน (ดู 🔭 คำถาม 2 แบบ)*\n"
            ."   **ส่วน A — เหตุผลรองรับ (จากไพ่ + พฤติกรรม):**\n"
            ."     • ทำไมถึงแนะนำแบบนี้ — เชื่อมไพ่ + สถานการณ์จริง\n"
            ."     • ลูกค้ามีส่วนรับผิดชอบอะไร (ไม่โทษอีกฝ่ายอย่างเดียว)\n"
            ."   **ส่วน B — 3 ขั้น actionable ทำได้สัปดาห์นี้:**\n"
            ."     1. สิ่งที่ \"หยุดทำ\" (เช่น \"หยุดเช็คเฟสเขา 7 วัน\")\n"
            ."     2. สิ่งที่ \"เริ่มทำ\" (เช่น \"เริ่มลิสต์สิ่งที่ตัวเองได้/เสีย จากความสัมพันธ์นี้\")\n"
            ."     3. \"เช็คใจตัวเอง\" ทุก X วัน — เพิ่ม accountability\n"
            ."   **ส่วน C — คำถามให้ลูกค้าถามตัวเอง (1-2 ข้อ):**\n"
            ."     เช่น \"ถ้าวันนี้เธอเป็นเพื่อนของตัวเอง — เธอจะแนะนำเพื่อนคนนี้ให้รอต่อไหม?\"\n\n"

            ."🔥 8. สรุปฟันธง (Bullet 4-6 ข้อสั้นๆ)\n"
            ."   ตัวอย่าง:\n"
            ."   • เขามีใจ ✔️\n"
            ."   • แต่ไม่พร้อม ❗\n"
            ."   • จะกลับมา แต่ไม่ชัด ❗\n"
            ."   • คุณควรรอไม่เกิน 3 เดือน ⏳\n\n"

            ."🌟 9. บทสรุปและทางออกของจิต\n"
            ."   **3 ส่วน:**\n"
            ."     1. **1 ความจริงที่ลูกค้าต้องยอมรับ** — truth bomb อย่างเข้าใจ\n"
            ."        เช่น \"แม่หมอจะพูดตรงๆ — เขาไม่ได้ลังเลเพราะรักไม่พอ แต่เพราะเขายังไม่รู้ว่าตัวเองต้องการอะไร\"\n"
            ."     2. **1 mindset shift** — มุมมองใหม่ที่จะเปลี่ยนชีวิต\n"
            ."        เช่น \"เลิกถามว่า 'เขาจะกลับมาไหม' → เริ่มถามว่า 'ฉันจะเป็นคนที่ดีที่สุดของตัวเองได้ยังไงในช่วงนี้'\"\n"
            ."     3. **คำเชิญกลับมาหาตัวเอง** — focus inward ไม่ใช่ติดกับอีกฝ่าย\n"
            ."        เช่น \"ไม่ว่าเขาจะกลับมาหรือไม่ — เธอต้องเดินต่อให้แข็งแรงขึ้นก่อน นั่นคือชัยชนะที่แท้\"\n\n"

            ."━━━━━━━━━━━━━━━━━\n"
            ."🚫 ห้ามทำ\n"
            ."━━━━━━━━━━━━━━━━━\n"
            ."• ห้ามตอบสั้น\n"
            ."• ห้ามกำกวม\n"
            ."• ห้ามแปลไพ่ทีละใบแบบ \"ตำแหน่ง 1 ได้ไพ่ X... ตำแหน่ง 2 ได้ไพ่ Y...\"\n"
            ."• ห้ามใช้คำทั่วไปที่ใช้ได้กับทุกคน (\"อยู่ที่ตัวคุณ\"/\"แล้วแต่กรรม\"/\"ทุกอย่างเปลี่ยนได้\")\n"
            ."• ห้ามใช้ markdown (**, ##, ฯลฯ) — plain text ล้วน + emoji หัวข้อได้\n"
            ."• ✅ ขอวันเกิด/ข้อมูลเพิ่มได้ ถ้าจะทำให้ทำนายแม่นขึ้น (เช่น ช่วงเวลาตามดาวเจ้าชนะ)\n"
            ."   แต่ต้องทำนายเบื้องต้นจากไพ่ก่อนเสมอ — ห้ามขอแล้วไม่ตอบอะไร\n\n"

            ."━━━━━━━━━━━━━━━━━\n"
            ."🎯 เป้าหมายสุดท้าย\n"
            ."━━━━━━━━━━━━━━━━━\n"
            ."คำตอบต้องทำให้ลูกค้า:\n"
            ."• รู้สึก \"โดน\" — เหมือนแม่หมอเห็นชีวิตจริง\n"
            ."• เข้าใจสถานการณ์ตัวเอง\n"
            ."• เห็นทางเลือกชีวิตชัดขึ้น\n\n"

            ."📏 ความยาว: 1500-2500 ตัวอักษร แบ่งย่อหน้าตามโครงสร้าง 9 ส่วน อ่านง่าย\n\n"

            .'เริ่มทำนายทันทีจากข้อมูลที่ได้รับ (ไม่ต้องทักทายซ้ำ — ขึ้นด้วยส่วนที่ 1 \"เปิดคำทำนาย\" เลย):';
    }

    /**
     * 🌙 (2026-05-14) Opening Greeting — AI ทักทายเองหลังเปิดไพ่ครบ 10 ใบ
     *
     * user spec: "เมื่อเปิดไพ่ครบ ให้ AI ถามเลยคุยกับ user เลย ให้เริ่มถาม"
     * → AI สร้างข้อความเปิดบทสนทนา + ชวนเล่าเรื่อง (ไม่ใช่ predict-all)
     *
     * Trigger: เรียกจาก onCelticAllCardsPicked หลัง user เปิดไพ่ใบที่ 10
     * Length: 400-700 chars (สั้นๆ พอให้ user รู้สึกแม่หมอเริ่มสนทนา)
     */
    public function generateOpeningGreeting(FortuneReading $reading): array
    {
        $cards = $reading->getCelticCards();
        if (count($cards) < 10) {
            return ['success' => false, 'message' => 'เปิดไพ่ไม่ครบ 10 ใบ'];
        }

        $name = $reading->resolveCustomerName();

        // 🌙 (2026-06-06 R5125) Opening = "ทักทาย + ชวนถาม" ล้วนๆ — ห้ามทำนาย/เกริ่นไพ่ก่อนลูกค้าถาม
        //   user spec (บิล FTU-260606-W4360): "เขายังไม่ได้ถามคำถาม ทำไมชอบตอบก่อน เคยแก้หลายรอบ
        //   แล้วไม่หายสักที"
        //
        //   ROOT CAUSE ที่แก้ไม่หาย 3 รอบ: prompt เดิม (ข้อ 2) สั่ง AI ว่า "แม่หมอบอกสิ่งที่เห็น
        //   จากไพ่ 1-2 ประเด็น" → AI เดาธีมก่อนลูกค้าถามทุกครั้ง (= ตอบก่อนถาม). รอบก่อนๆ (R4474/
        //   R4543/R5023) แก้แค่ readiness-ack whitelist ("พร้อม"/"ขอบคุณ"/"พร้อมฟัง") ไม่เคยแตะ
        //   opening เลย → อาการ "ตอบก่อน" ไม่หาย.
        //
        //   FIX ที่ราก: ทำเป็น template ตายตัว (ไม่เรียก AI) → AI เดาธีมไม่ได้เด็ดขาด + ตอบทันที 0s
        //   + ไม่เปลือง token. คำทำนายจะเกิด "หลัง" ลูกค้าพิมพ์คำถามเท่านั้น (ผ่าน askQuestion).
        //   user เลือกแนวทางนี้ (AskUserQuestion 2026-06-06): "ทักทาย+ชวนถามล้วน".
        $variants = [
            "🌙 สวัสดีค่ะ คุณ{$name} — แม่หมอจันทราเปิดไพ่ Celtic Cross ครบทั้ง 10 ใบให้แล้วค่ะ ✨\n\n"
                ."🃏 ไพ่พร้อมแล้ว เหลือแค่รอเจ้าชะตาเปิดใจ\n\n"
                .'💬 อยากให้แม่หมอดูเรื่องอะไรก่อนดีคะ? เล่าเรื่องที่ค้างคาใจมาได้เลย — '
                .'ความรัก การงาน การเงิน สุขภาพ หรือเรื่องไหนก็บอกแม่หมอมาค่ะ',

            "🌙✨ แม่หมอจันทราพร้อมแล้วค่ะ คุณ{$name} ✨🌙\n\n"
                ."🃏 ไพ่ทั้ง 10 ใบของเจ้าชะตาเปิดออกหมดแล้ว — พลังพร้อมให้แม่หมออ่าน\n\n"
                .'💬 เจ้าชะตาอยากเริ่มจากเรื่องไหนก่อนคะ? พิมพ์เรื่องที่อยากรู้มาได้เลย '
                .'แม่หมอจะเปิดไพ่ทำนายให้ทีละเรื่องค่ะ',

            "🌙 คุณ{$name}คะ — แม่หมอเปิดไพ่ครบทั้งสำรับให้แล้วนะคะ 🃏✨\n\n"
                ."ทุกใบพร้อมเล่าเรื่องราวของเจ้าชะตา\n\n"
                .'💬 มีเรื่องอะไรที่อยากให้แม่หมอดูให้เป็นเรื่องแรกคะ? บอกมาได้เลย — '
                .'ความรัก งาน เงิน สุขภาพ หรือเรื่องที่กำลังหนักใจอยู่',
        ];

        // เลือก variant แบบ deterministic ต่อ reading (ไม่ใช้ random — กัน flapping ตอน retry)
        $idx = abs(crc32(($reading->bill_reference ?? '').'|'.$reading->id)) % count($variants);
        $response = $variants[$idx];

        try {
            // bridge → LineBotConversation (assistant turn แรก) ให้ post-Celtic chat เห็น context
            $this->bridgeToConversationLog($reading, 'assistant', $response);
        } catch (\Throwable $e) {
            Log::warning('Celtic: opening greeting bridge fail (non-blocking)', [
                'reading_id' => $reading->id,
                'error' => $e->getMessage(),
            ]);
        }

        Log::info('Celtic: opening greeting (template, no-predict)', [
            'reading_id' => $reading->id,
            'variant' => $idx,
        ]);

        return ['success' => true, 'response' => $response];
    }

    /**
     * 🛑 (2026-05-14) ลบ buildPredictAllPrompt — ตาม user spec "เอาระบบ Q1/Q2/Q3 ออก"
     * ปุ่ม "ทำนายเดี๋ยวนี้" + sentinel __PREDICT_ALL__ + handleCelticPredictAll ถูกลบหมดแล้ว
     *
     * @deprecated 2026-05-14 — ใช้ generateOpeningGreeting() แทน
     */
    protected function buildPredictAllPromptDeprecated(FortuneReading $reading, array $cards): string
    {
        $brandName = $this->settings->fortune_brand_name ?: 'แม่หมอจันทรา';
        $cardsText = $this->formatCardsForPrompt($cards);

        return "คุณคือ \"{$brandName}\" — หมอดูไพ่ยิปซีระดับเซียน 30+ ปี\n"
            ."พลังพิเศษ: อ่านพลังงานจักรวาลที่หลั่งผ่านไพ่ + จิตเจ้าชะตาที่ตั้งสมาธิเลือกไพ่\n"
            ."บุคลิก: สุขุม นิ่ง อบอุ่น เปี่ยมพลัง — พูดน้อย แต่แทงใจดำได้ทุกประโยค\n"
            ."เจ้าชะตาเพิ่งตั้งจิตให้นิ่ง เปิดไพ่ Celtic Cross 10 ใบเสร็จ — ทุกใบสุ่มจากกระแสจิตของตน ไม่ใช่บังเอิญ\n\n"

            ."━━━━━━━━━━━━━━━━━\n"
            ."🌟 ภารกิจ: ทำนายพื้นฐานทุกเรื่อง (ไม่มีคำถามเฉพาะ — เจ้าชะตายังไม่ทันถาม)\n\n"
            ."🃏 ไพ่ทั้ง 10 ที่กระแสจิตเจ้าชะตาเลือก:\n{$cardsText}\n"
            ."━━━━━━━━━━━━━━━━━\n\n"

            ."✍️ ทำนายให้ครบ 5 ด้านนี้ — ทุกด้านต้องมี (ห้ามข้าม):\n\n"

            ."1️⃣ 💕 ความรัก / คู่ครอง:\n"
            ."   • มีคู่/โสด/มีคนแอบรัก/กำลังจะมีใครเข้ามา → ฟันธง บอกชัด\n"
            ."   • ระบุลักษณะคน (เพศ/วัย/อาชีพ/บุคลิก) ที่ไพ่บอก\n"
            ."   • ถ้ามีคู่อยู่ — สถานะความสัมพันธ์เป็นไง? ดีขึ้น/แย่ลง/มีปัญหา?\n\n"

            ."2️⃣ 💼 การงาน / อาชีพ:\n"
            ."   • งานปัจจุบันมั่นคง/ไม่มั่นคง? ควรเปลี่ยน/อยู่ต่อ?\n"
            ."   • โอกาสใหม่กำลังเข้ามาหรือไม่? ลักษณะงาน?\n"
            ."   • อุปสรรค/คนช่วย/คู่แข่ง → ระบุชัด\n\n"

            ."3️⃣ 💰 การเงิน / โชคลาภ:\n"
            ."   • รายได้ช่วงนี้ดี/ไม่ดี? เงินเข้า/เงินออกเยอะ?\n"
            ."   • โชคลาภ/รางวัล/ลาภลอย — มี/ไม่มี?\n"
            ."   • ระวังเรื่องเงิน — ใคร/อะไรเป็นจุดเสี่ยง?\n\n"

            ."4️⃣ 🌿 สุขภาพ + จิตใจ:\n"
            ."   • ร่างกายช่วงนี้แข็งแรง/อ่อนล้า? อวัยวะที่ต้องระวัง?\n"
            ."   • จิตใจ — เครียด/สงบ/ฟุ้งซ่าน?\n"
            ."   • คำเตือน + วิธีดูแลตัวเอง\n\n"

            ."5️⃣ 👨‍👩‍👧 ครอบครัว + คนใกล้ชิด:\n"
            ."   • ความสัมพันธ์ในครอบครัวเป็นไง? มีปัญหากับใคร?\n"
            ."   • เพื่อน/หุ้นส่วน/คนที่ไว้ใจได้ — ใครเป็นมิตรแท้?\n"
            ."   • ควรระวังใคร?\n\n"

            ."🎯 หลักการตอบ — ฟันธง ตรงประเด็น ไม่อ้อมค้อม:\n"
            ."   • ห้ามใช้ \"อาจจะ/น่าจะ/บางที/ขึ้นอยู่กับ/แล้วแต่\" — ฟันธงจากไพ่\n"
            ."   • ดี → บอกตรงว่าดี / ไม่ดี → บอกตรงว่าไม่ดี (เตือนได้แต่ห้ามอ้อม)\n"
            ."   • ทุกตัวละคร/เหตุการณ์/สัญลักษณ์ในไพ่ → เล่าให้หมด ห้ามข้าม\n"
            ."   • ผูกไพ่หลายใบเป็นเรื่องเดียวกัน — ไม่ใช่ list ทีละใบ\n\n"

            ."🚫 ข้อห้าม:\n"
            ."   1. ห้ามถามคำถามกลับ — นี่คือทำนายหลัก ไม่ใช่บทสนทนา\n"
            ."   2. ห้ามใช้ markdown (**, ##, -, ฯลฯ) — plain text ล้วน\n"
            ."   3. ห้ามอ้างวันเกิด/ดาว/ราศี/เลขมงคล — ใช้แค่พลังไพ่\n"
            ."   4. ห้ามตอบลอยๆ \"อยู่ที่ตัวคุณ\" \"กรรมเก่า\" — ฟันธงจากไพ่ก่อน คำแนะนำตอนท้าย\n\n"

            ."📏 ความยาว: 2000-3000 ตัวอักษร แบ่งย่อหน้า 5 ส่วน (5 ด้าน) ใช้ emoji หัวด้านได้\n"
            ."🎭 โทน: สุขุม อบอุ่น มั่นใจ ฟันธง — เหมือนแม่หมอเห็นชีวิตเจ้าชะตาผ่านไพ่\n\n"

            ."🎯 ก่อนปิดท้าย — *บังคับ* ใส่ \"กล่องสรุปฟันธง 5 ด้าน\" (เนื้อๆ 1 บรรทัด/ด้าน ห้ามน้ำ ห้ามอ้อม):\n"
            ."   🎯 สรุปฟันธง:\n"
            ."   💕 ความรัก: [ฟันธง 1 บรรทัด — มี/ไม่มีคู่ + ลักษณะคน + timeline]\n"
            ."     ตัวอย่าง: \"จะได้คนใหม่ ก.ค. นี้ — ชายวัย 30+ จากแวดวงงาน รสนิยมเข้ากัน\"\n"
            ."   💼 การงาน: [ฟันธง 1 บรรทัด — มั่นคง/เปลี่ยน/เลื่อน + timeline]\n"
            ."     ตัวอย่าง: \"งานปัจจุบันมั่นคง ก.ค.-ก.ย. มีโอกาสเลื่อนตำแหน่ง — ระวังคู่แข่งหญิงในออฟฟิศ\"\n"
            ."   💰 การเงิน: [ฟันธง 1 บรรทัด — รายได้ + โชคลาภ + จุดเสี่ยง]\n"
            ."     ตัวอย่าง: \"รายได้เพิ่ม 15% มิ.ย. มีลาภลอยจากญาติผู้ใหญ่ — ระวังเซ็นสัญญาเดือน ส.ค.\"\n"
            ."   🌿 สุขภาพ: [ฟันธง 1 บรรทัด — แข็งแรง/อ่อนล้า + อวัยวะที่ต้องระวัง]\n"
            ."     ตัวอย่าง: \"แข็งแรงโดยรวม แต่ระวังกระเพาะ ก.ค. นี้ — นอนน้อย ใจฟุ้ง\"\n"
            ."   👨‍👩‍👧 ครอบครัว: [ฟันธง 1 บรรทัด — สัมพันธ์ + ใครต้องระวัง]\n"
            ."     ตัวอย่าง: \"พ่อแม่สุขภาพดี — พี่น้องมีเรื่องขัดแย้งเรื่องเงิน ก.ค. ต้องเป็นกลาง\"\n\n"

            ."💡 หลังกล่องสรุปฟันธง: ปิดท้ายด้วยประโยคเชิญเจ้าชะตาถามเจาะลึกเรื่องที่อยากรู้ต่อ\n"
            .'เริ่มทำนายเลย (เริ่มประโยคแรกด้วยพลัง อย่าทักทายซ้ำ):';
    }

    /**
     * สร้าง Followup Prompt — ตอบคำถามใหม่โดยใช้ไพ่เดิม
     *
     * 🆕 (2026-05-16 v3) Sequence-aware short style — ปรับ threshold rev2:
     *   - Q1-Q2: full storytelling 9 sections — 800-1500 chars (ลูกค้าจ่าย 99฿ ต้องคุ้ม)
     *   - Q3+: short analytical กระชับ — 200-450 chars
     *   - Off-topic repick: judgment-based (ไม่ใช่ sequence threshold เด็ดขาด)
     *     AI ใส่ [OFF_TOPIC_REPICK] เฉพาะถ้าเห็นลูกค้า "ออกทะเล/วุ่นวายหนัก/ไพ่ตอบไม่ได้แล้ว"
     *     เปิดให้ใช้ตั้งแต่ Q3+ (มี short style แล้ว มีโอกาสตอบหลายๆ ครั้งก่อน)
     *
     * 👤 Persona context — inject ทุก Q เพื่อให้ tone กลมกลืน (เพศ/อายุ/ความกังวล)
     * 🙏 สายมู + ธรรมะ — แม่หมอผสานปรัชญาเข้าด้วยกัน ไม่ขัดแย้งตัวเอง
     */
    protected function buildFollowupPrompt(
        FortuneReading $reading,
        string $userQuestion,
        array $cards,
        int $sequence
    ): string {
        $brandName = $this->settings->fortune_brand_name ?: 'แม่หมอจันทรา';

        if (! empty($this->settings->celtic_cross_followup_prompt)) {
            return $this->renderTemplate(
                $this->settings->celtic_cross_followup_prompt,
                $reading,
                $userQuestion,
                $cards,
                $brandName,
                $sequence
            );
        }

        $cardsText = $this->formatCardsForPrompt($cards);

        // ดึงบทสนทนาเก่า → ส่งให้ AI เพื่อความต่อเนื่อง (ไม่อ้าง Q1/Q2/Q3 sequence)
        $previousQA = $reading->celticQuestions()
            ->whereNotNull('answered_at')
            ->orderBy('sequence')
            ->get();

        $previousContext = '';
        if ($previousQA->isNotEmpty()) {
            // 📜 (2026-06-13) ใช้คำทำนายเดิม "ทั้งหมด" ช่วยตอบ Q2+ — โดยเฉพาะ Q1 (พื้นดวงเปิดตัว)
            //   เดิม cap 300 ตัว/คำตอบ → Q1 พื้นดวง ~3000-3500 ตัว ถูกตัดเหลือ 300 = Q2+ มองไม่เห็นพื้นดวง
            //   ใหม่: Q1 (sequence=1) = ฐานหลัก cap สูง (3000) / คำตอบอื่น cap กลาง (500) — ทุกคำตอบต้องสอดคล้อง
            $previousContext = "📜 บริบทต่อเนื่องของรอบนี้ — คำทำนาย Q1 (พื้นดวง) + บทสนทนาที่ผ่านมา:\n"
                ."⚓ ใช้เป็น *background เพื่อความต่อเนื่อง* — ห้ามขัดแย้งกับที่เคยทำนายไว้\n"
                ."🎴 *แต่อย่าเล่าซ้ำพื้นดวง* — คำถามใหม่ต้อง \"อ่านไพ่ใหม่ให้ตรงคำถามนั้นโดยเฉพาะ\" (เจาะไพ่ตำแหน่งที่ตอบคำถาม + ตั้งตรง/กลับหัว) ไม่ใช่ลอกประโยค/ไพ่/กรอบเวลาเดิมมาวางซ้ำ\n"
                ."🧭 แล้ว *ต่อยอดเป็นทางออกที่ทำได้จริง* ให้เจ้าชะตา (แม่หมอที่เก่ง = บอกความจริงตามหน้าไพ่ + ให้ทางออกที่ใช้ได้จริง ไม่ใช่แค่คำปลอบ):\n\n";
            foreach ($previousQA as $q) {
                $qText = trim($q->question) === '__PREDICT_ALL__'
                    ? 'ทำนายดวงพื้นฐาน'
                    : mb_substr($q->question, 0, 200);
                $isFoundation = ((int) $q->sequence === 1);
                $ansCap = $isFoundation ? 3000 : 500;
                $full = (string) ($q->response ?? '');
                $ans = mb_substr($full, 0, $ansCap);
                $truncated = mb_strlen($full) > $ansCap ? '...' : '';
                $label = $isFoundation
                    ? 'แม่หมอ [พื้นดวงเปิดตัว Q1 — ฐานหลัก ใช้อ้างอิงทุกคำตอบ]'
                    : 'แม่หมอ';
                $previousContext .= "ลูกค้า: {$qText}\n";
                $previousContext .= "{$label}: {$ans}{$truncated}\n\n";
            }
        }

        // 🌟 (2026-05-30) Birth-date astrology — ถ้าลูกค้าพิมพ์วันเกิดมา → คำนวณดวงดาวสด (ราศี/ดาวเจ้าชนะ/ธาตุ)
        //   เคส bill FTU-260530-Z4397: ลูกค้าใส่วันเกิดตัวเอง+คู่ปรับใน Q1 แต่ Celtic เดิมส่ง birthDate=null
        //   → AI เดาจากตัวเลขดิบ ไม่ได้คำนวณดวงดาวจริง (ระบบ 39฿ มี engine แต่ Celtic ไม่เรียก)
        //   ทางแก้ B (user 2026-05-30): parse วันเกิดจาก "คำถามปัจจุบัน + คำถามเก่า" (คงข้อมูลข้ามเทิร์น)
        //                                แล้วคำนวณสด ผสานเข้า prompt — ไม่แตะ Deep 39฿
        //   รองรับหลายคน (เช่นถามความเข้ากันคู่รัก) — ดู ThaiAstrologyService
        $birthAstroSourceText = $userQuestion;
        foreach ($previousQA as $pq) {
            $birthAstroSourceText .= "\n".(string) $pq->question;
        }

        // 🔎 (2026-05-30) Detective mode — รวมแหล่งวันเกิดให้ครบ + คงข้ามเทิร์น
        //   ปัญหาเดิม: source = userQuestion + previousQA (เฉพาะ TYPE:A) → วันเกิดที่ลูกค้าให้
        //   ในเทิร์น TYPE:D (record ถูกลบ) จะหายเทิร์นหน้า. แก้: persist ลง conversation_state
        //   + auto-use วันเกิดจาก Deep 39฿ เดิมถ้ามี (ไม่ต้องถามซ้ำ — "ดาวเจ้าชนะที่เคยทำไปแล้ว")
        //   ($userQuestion = ตัวที่ต้อง persist ถ้ามีวันเกิด — ที่เหลือคือแหล่งค้นอย่างเดียว)
        $birthAstroBlock = $this->buildBirthAstrologyBlockFor($reading, $birthAstroSourceText, $userQuestion);

        // 👤 (2026-05-16) Inject persona — เพศ/อายุ/บุคลิก → ให้ AI ปรับ tone กลมกลืน
        //    Guard: ถ้า persona ไม่มีข้อมูล → return '' → ไม่ inject
        //    Sanitize: bracket directive `[👤 CUSTOMER_PERSONA...]` ถูก filter ใน FortuneAIService อยู่แล้ว
        $personaBlock = '';
        $personaModel = null;
        // 🧭 (2026-09-01) LINE id อยู่ในคอลัมน์ facebook_user_id — ใช้คอลัมน์ platform + U-regex เท่านั้น
        $celticUserId = (string) ($reading->facebook_user_id ?: $reading->platform_user_id ?: '');
        $celticPlatform = $reading->platform
            ?: (preg_match('/^U[0-9a-f]{32}$/i', $celticUserId) ? 'line' : 'facebook');
        try {
            if ($celticUserId !== '') {
                $personaService = app(CustomerPersonaService::class);
                // withPastRecall=false → buildPastCaseBlock() ยิงเองพร้อม exclude บิลปัจจุบัน
                $personaBlock = $personaService->buildInjectBlock($celticPlatform, $celticUserId, $userQuestion, false);
                $personaModel = $personaService->getCached($celticPlatform, $celticUserId);
            }
        } catch (\Throwable $e) {
            // persona fail → skip ไป — ไม่ block flow
            Log::debug('Celtic: persona inject fail (non-blocking)', [
                'reading_id' => $reading->id,
                'error' => $e->getMessage(),
            ]);
        }

        // 🛡️ (2026-05-27) Abuse Clapback directive — ลูกค้าด่า/ป่วน/หน้าหม้อ → savage mode
        //   ใน paid Celtic: L1+L2 เท่านั้น (ไม่ pause/ban — ทำนายครบตามที่จ่าย)
        //   ตอบแสบแต่ professional + อ้าง พ.ร.บ.คอม
        $clapbackDirective = '';
        try {
            if ($celticUserId !== '') {
                $clapbackDirective = app(\App\Services\Fortune\AbuseClapbackService::class)
                    ->getCelticDirective($celticPlatform, $celticUserId, $userQuestion, $personaModel);
            }
        } catch (\Throwable $e) {
            Log::debug('Celtic: clapback inject fail (non-blocking)', [
                'reading_id' => $reading->id,
                'error' => $e->getMessage(),
            ]);
        }

        // 🆕 (2026-05-20) Q2+ — chat-smart mode (AI ตัดสินใจประเภท input เอง)
        //    user spec 2026-05-20: Q1 = ฟอร์มเต็ม / Q2+ = แม่หมอคุยฉลาดสมจริง
        //    AI แยก: คำถาม (ใช้ไพ่) / ระบาย (empathy) / chitchat (สั้น) / เล่าเรื่อง (ฟัง+ผูกไพ่)
        //    เก่า (rev2): Q1-Q2 full storytelling, Q3+ short → เปลี่ยน guard >= 3 → >= 2
        if ($sequence >= 2) {
            // 🌙 (2026-05-23) Silent sandbagging — คำนวณ TYPE:A counter เหลือเท่าไหร่
            //    user spec: ลูกค้าจำกติก 30 นาที — ห้ามประกาศ max questions
            //    AI ตอบสั้นลง + แนะให้ใคร่ครวญเมื่อใกล้ max → ดึงเวลาให้ถึง 30 นาที
            //    หลัง window หมด → FortuneCelticAutoFinalize cron ส่ง grand_finale
            $questionsUsed = (int) ($reading->celtic_questions_used ?? 0);
            $maxQuestions = (int) ($this->settings->celtic_cross_max_questions ?? 0);
            $remaining = $maxQuestions > 0 ? max(0, $maxQuestions - $questionsUsed) : 999;

            // 💬 (2026-05-24) Pre-Celtic chat context — pass ไป Q2+ เช่นกัน
            $preChatContextQ2 = $this->buildPreCelticChatContext($reading);

            // 🪷 (2026-05-27) Advisor directive — ต้อง inject Q2+ ด้วย
            //   user complaint: "Q2+ ตอบสั้น เน้นเคล็ด/ธรรมะ ไม่ให้คำปรึกษา"
            //   root cause: buildLifeCoachDirective ถูก inject แค่ Q1 → Q2+ เพี้ยนไป dharma talk
            //   🛡️ (2026-05-27) prepend clapback directive — supersedes advisor tone ถ้า abuse
            //   🔮 (2026-05-28) append สายมู directive — ให้คำแนะนำสายมูได้จริง ไม่มั่ว
            //   🔭 (2026-05-28) append forecast directive — แยก ทางออก vs ทำนายอนาคตล้วน
            $advisorDirectiveQ2 = $clapbackDirective
                .$this->buildLifeCoachDirective($reading)
                .$this->buildExtrasDeferDirective()
                .$this->buildForecastModeDirective($reading)
                // 🪬 (2026-05-29) โหมดคุณไสย์ — inject Q2+ ด้วย (lock เรื่อง / reject ถ้าเปิดประเด็นช้า)
                .$this->buildBlackMagicDirective($reading, $userQuestion, $previousContext)
                // 🩺 (2026-06-01) ตำราสุขภาพ — inject Q2+ ด้วย (ถามสุขภาพ → เทียบอวัยวะ/อาการตามหน้าไพ่)
                .$this->buildHealthDirective($reading, $userQuestion, $previousContext)
                .$this->buildMuKnowledgeDirective($reading, $userQuestion, $previousContext)
                .$this->buildPhysiognomyDirective($reading, $userQuestion, $previousContext)
                .$this->buildPersonRoleDirective($reading, $userQuestion, $previousContext)
                .$this->buildLifeReadingDirective($reading, $userQuestion, $previousContext)
                .$this->buildDestinyDirective($reading, $userQuestion, $previousContext)
                .$this->buildExtraKnowledgeDirectives($reading, $userQuestion, $previousContext)
                .$this->buildCardComboDirective($reading)
                .$this->buildSpreadPatternDirective($reading)
                .$this->buildElementalDignityDirective($reading)
                .$this->buildPositionDynamicDirective($reading)
                // 🎯 (2026-08-07) Question Router — บอกตรงๆ ว่าคำถามนี้ต้องอ่านไพ่ใบไหนเป็นแกน
                //   Q2+ = ตอบคำถามเจาะจง (ไม่ได้อธิบายไพ่ครบ 10 ใบเหมือนพื้นดวง Q1) → ชี้เป้าได้
                //   ❗ ไม่ inject ใน Q1 พื้นดวง เพราะที่นั่นสเปคคือ "อ้างไพ่ครบ 10 ใบ"
                .$this->buildQuestionRoutingDirective($reading, $userQuestion)
                .$this->buildYesNoDirective($reading, $userQuestion, $previousContext)
                // 📚 (2026-08-31) เคสเก่า — **ต้องมีที่ Q2+ ด้วย**
                //   เดิม pastReadingsContext อยู่ใต้บรรทัดนี้ แต่ branch นี้ return ก่อน
                //   ⇒ ลูกค้าถามถึงเรื่องที่เคยดูไว้กลางวง บอทไม่มีข้อมูลเลย (เคส FTU-260831-W5209)
                .$this->buildPastCaseBlock($reading, $userQuestion);

            return $this->buildShortFollowupPrompt(
                $brandName,
                $cardsText,
                $previousContext,
                $userQuestion,
                $sequence,
                $personaBlock,
                $remaining,
                $preChatContextQ2,
                $advisorDirectiveQ2,
                $birthAstroBlock,
                $this->isBlackMagicModeForced($reading)
            );
        }

        // 🌙 (2026-05-14) Q1 default prompt — แม่หมอจันทราพยากรณ์ Celtic 99฿
        //   user spec 2026-05-14: "เอาระบบ Q1/Q2/Q3 ออก ใช้ prompt เดียวเท่านั้น"
        //   ลบ sequence-aware language → AI ตอบเหมือนคุยกับลูกค้าธรรมดา
        //   admin override ผ่าน settings.celtic_cross_followup_prompt
        $personaPrefix = $personaBlock !== '' ? $personaBlock."\n\n" : '';

        // 💬 (2026-05-24) Pre-Celtic chat context — บริบทสนทนาก่อนซื้อ
        $preChatContext = $this->buildPreCelticChatContext($reading);

        // 🔍 (2026-05-25) Enrichment directive — AI ถาม clarifying ถ้าคำถาม vague
        $enrichmentDirective = $this->buildEnrichmentDirective($reading, $userQuestion);

        // 🪷 (2026-05-25) Advisor directive — แม่หมอ = ที่ปรึกษา มีหลักการ+เหตุผล
        //   🛡️ (2026-05-27) prepend clapback directive — supersedes advisor tone ถ้า abuse
        $advisorDirective2 = $clapbackDirective.$this->buildLifeCoachDirective($reading);

        // 🧧 (2026-06-28) เคล็ด/ธรรมะ/เลขเสี่ยงโชค → ยกไปบทสรุปสุดท้าย (ไม่แทรกรอบถามตอบ) — แทน buildSaiMuDirective เดิม
        $extrasDeferDirective2 = $this->buildExtrasDeferDirective();

        // 🔭 (2026-05-28) Forecast mode — แยก "อยากได้ทางออก" vs "อยากรู้อนาคต" (ทำนายล้วนแบบ 39)
        $forecastDirective2 = $this->buildForecastModeDirective($reading);

        // 📜 (2026-05-25) Past readings context — ลูกค้าเก่า ดวงเปลี่ยนได้
        $pastReadingsContext = $this->buildPastReadingsContext($reading);

        // 👋 (2026-05-25) Check-in opener สำหรับลูกค้าเก่า
        $checkinDirective = $this->buildRepeatCheckinDirective($reading);

        // 📚 (2026-08-31) ลูกค้าอ้างถึงเคสเก่า → ยกคำทำนายเดิมมาให้อ้างอิง (ข้ามดัชนี — pastReadingsContext ทำแล้ว)
        $pastCaseBlock = $this->buildPastCaseBlock($reading, $userQuestion, false);

        // 🎯 (2026-05-25 Patch C/D) Complaint + Multi-bullet handling — Q1 ก็เจอ
        //    Patch C: เคส lookup feedback ที่ Q1 อาจมี complaint แล้ว (ลูกค้าจ่ายเพราะ Free reading ดี → Celtic Q1 บ่น)
        //    Patch D: เคส R3719 Q1 ถาม 1.งาน 2.เงิน 3.สุขภาพ 4.ความรัก — ใช้ทันที Q1
        $complaintHandlingQ1 = $this->detectComplaintInQuestion($userQuestion)
            ? "━━━━━━━━━━━━━━━━━\n"
                ."🪞 *รับมือการบ่น/วิจารณ์* (ตรวจพบในข้อความล่าสุด)\n"
                ."━━━━━━━━━━━━━━━━━\n"
                ."ลูกค้า *บ่น/วิจารณ์* — เปิดคำทำนายด้วย acknowledgment 1-2 ประโยค (\"ขอบคุณที่บอกตรงๆ\")\n"
                ."ทำนายด้วย intuitive mode — เลิกอ้างชื่อไพ่ตรงๆ ใช้ภาษาพลังงาน\n"
                ."ห้าม defensive (\"ที่ต่างกัน อาจเป็นเพราะ...\") เด็ดขาด\n\n"
            : '';

        // 📋 (2026-05-29 supersede Patch D) ลูกค้าถามหลายเรื่อง → ตอบข้อแรก + ชวนถามทีละข้อ
        //    เดิม Patch D: "ตอบทุก bullet" — user เปลี่ยนนโยบาย: ตอบรวบ = ตื้น ไม่แม่น
        //    ใหม่: ทำนายเรื่องสำคัญสุด 1 เรื่องให้ลึก + ชวนถามที่เหลือทีละเรื่อง (แม่นกว่า)
        $multiBulletHandlingQ1 = $this->detectMultiBulletQuestion($userQuestion)
            ? "━━━━━━━━━━━━━━━━━\n"
                ."📋 *ลูกค้าถามหลายเรื่องใน turn เดียว* (ตรวจพบหลายหัวข้อ)\n"
                ."━━━━━━━━━━━━━━━━━\n"
                ."⚠️ ห้ามตอบรวบทุกเรื่องในคราวเดียว — ไพ่ชุดเดียวทำนายหลายเรื่องพร้อมกัน = ตื้น ไม่แม่น\n"
                ."✅ ให้ทำแบบนี้แทน:\n"
                ."   1. เลือก *เรื่องสำคัญ/เร่งด่วนที่สุดเพียง 1 เรื่อง* → เปิดไพ่ทำนายเต็มที่ ลึก ฟันธงชัด\n"
                ."   2. ปิดท้าย ชวนถามที่เหลือ *ทีละเรื่อง* (พูดเป็นธรรมชาติ ไม่ใช่ template):\n"
                ."      เช่น \"แม่หมอเห็นเจ้าชะตาถามมาหลายเรื่อง ขอเปิดไพ่เรื่อง [เรื่องแรก] ให้ชัดก่อนนะคะ —\n"
                ."      ส่วนเรื่อง [ที่เหลือ] พิมพ์ถามเข้ามาทีละเรื่อง แม่หมอจะทำนายให้แม่นทีละข้อค่ะ ✨\"\n"
                ."📌 เหตุผล: โฟกัสทีละเรื่อง = ไพ่ตอบตรงเรื่องนั้น = แม่นกว่าตอบรวบหลายเรื่อง\n\n"
            : '';

        // 🌟 (2026-06-08) คำทำนายพื้นดวงเปิดตัว — รอบแรกหลังได้วันเกิด: ทำนายแบบ 39 (ดวงดาวเต็ม) ผสานไพ่ 10 ใบ
        //   user spec: "คำทำนายแรก = พื้นฐานดวงแบบเดียวกับ 39 ทำนายดวงดาว แต่อิงไพ่ 10 ใบแทน 1 ใบ
        //   มันยาว แต่แบ่งส่ง" → flag celtic_base_chart ตั้งตอน handleCelticBirthdateStep ได้วันเกิด
        //   → override โครงสร้าง+ความยาวเฉพาะรอบนี้ (FB sendMessage ตัดทุก 1800 ตัวอักษร = แบ่งส่งเอง /
        //   LINE ยาวได้ถึง 5000). ดวงดาว (buildCelticBirthAstrologyBlock) + RAG + ไพ่ 10 ใบ มีในพรอมต์อยู่แล้ว
        $baseChartDirective = '';
        if ($reading->getConversationState('celtic_base_chart') && $this->isBlackMagicModeForced($reading)) {
            // 🪬 (2026-06-26) พื้นดวงเปิดตัวของ "โหมดดูคุณไสย์" — เจาะเรื่องของ/มนต์ดำ ไม่ทำดวงทั่วไป
            //   (ใช้เมื่อ generateBaseChartSectioned ล้ม → fallback single-call ; buildBlackMagicDirective forced
            //    ต่อท้าย prompt อยู่แล้ว — directive นี้แค่ override โครงสร้าง/ความยาวให้โฟกัสคุณไสย์)
            $baseChartDirective =
                "⚡⚡ คำสั่งพิเศษ — \"พื้นดวงเปิดตัว โหมดดูคุณไสย์\" (รอบแรก • สำคัญสูงสุด • OVERRIDE กฎความยาว/โครงสร้างด้านล่าง) ⚡⚡\n"
                ."━━━━━━━━━━━━━━━━━\n"
                ."เจ้าชะตาเลือก \"โหมดดูคุณไสย์/มนต์ดำ/สิ่งลี้ลับ\" — คำทำนายพื้นดวงรอบแรกนี้ *เจาะเรื่องของ/คุณไสย์เต็มรูปแบบเท่านั้น*\n"
                ."❌ ห้ามทำนาย รัก/งาน/เงิน/สุขภาพ แบบดวงทั่วไป — ใช้ดวงดาวจากวันเกิด \"เพื่อคำนวณทิศ/ฤกษ์สะเดาะเคราะห์\" เป็นหลัก (ไม่ใช่ทำนายนิสัย/ภพ)\n"
                ."📐 โครงสร้าง 3 ส่วน (ตามรายละเอียดในบล็อก 🪬 ด้านล่าง):\n"
                ."  1) 🪬 ฟันธงโดนของจริงไหม + ชนิดของ + ความรุนแรง (ไพ่ไม่ชี้ = บอกตรง ไม่ปั้น + ให้กำลังใจ)\n"
                ."  2) 🔍 โดนอะไร / ใครทำ (ลักษณะ ❌ห้ามมั่วชื่อ) / เมื่อไหร่ / ทำไม / มาทางไหน + เกราะคุ้มครอง (ถ้าไพ่ชี้)\n"
                ."  3) 🙏 ทางแก้ทำเองก่อน + ทิศสะเดาะเคราะห์ตามวันเกิด + อ้างครูบา/พระเกจิที่มีจริง — ❌ ห้ามพิธีแพง/งมงาย\n"
                ."📏 ความยาว *1500-3000 ตัวอักษร* (ระบบแบ่งส่งเอง) • 🃏 อ้างไพ่ครบ 10 ใบ • 🚫 ขึ้นต้น `[TYPE:A]` เสมอ\n"
                ."━━━━━━━━━━━━━━━━━\n\n"
                .$this->buildBaseChartSignalKnowledge($reading, ['black_magic', 'combo']);

            try {
                $reading->setConversationState('celtic_base_chart', false);
            } catch (\Throwable $e) {
                // non-blocking
            }
        } elseif ($reading->getConversationState('celtic_base_chart')) {
            $baseChartDirective =
                "⚡⚡ คำสั่งพิเศษ — \"คำทำนายพื้นดวงเปิดตัว\" (รอบแรกเท่านั้น • สำคัญสูงสุด • OVERRIDE กฎความยาว/โครงสร้างด้านล่าง) ⚡⚡\n"
                ."━━━━━━━━━━━━━━━━━\n"
                ."นี่คือคำทำนาย \"พื้นฐานดวง\" ครั้งแรกของเจ้าชะตา (เพิ่งได้วันเกิด) — ทำนายแบบ *โหราศาสตร์ไทยเต็มรูปแบบ*\n"
                ."ใช้ \"ดวงดาว (หลักเจ้าชนะ) เป็นแกนหลัก\" แล้ว *ผสานไพ่ทั้ง 10 ใบ* ที่เปิดไว้เข้าไปทุกส่วน\n"
                ."(ห้ามทำนายจากไพ่อย่างเดียว และห้ามทำนายจากดาวอย่างเดียว — ต้องร้อยเป็นเรื่องเดียวกัน)\n\n"
                ."📐 โครงสร้าง — *เปิดด้วยพาดหัวเรื่องเด่นก่อน* แล้วตามด้วย 4 ส่วน (ใส่ให้ครบ • ใช้ emoji หัวข้อ • เว้นย่อหน้าให้อ่านง่าย):\n"
                ."  0) 🎯 เรื่องเด่นรอบนี้ — สแกนไพ่ 10 ใบ ชี้ \"สิ่งที่ไพ่ส่งเสียงดังที่สุด\" 1 เรื่อง (ดี/ร้าย) ฟันธงเจาะจง:\n"
                ."     เรื่องอะไร/หนักเบา/ใคร/ที่ไหน/อย่างไร/เมื่อไหร่/จากใคร — เคราะห์หนัก (โดนของ/สูญเสีย/ป่วย/อุบัติเหตุ) → \"โฟกัสเรื่องนี้ก่อน\"\n"
                ."     + ทางแก้ทำได้จริง 1 ข้อ / สำรับสงบ → พาดหัวจุดเด่นเชิงบวก — ❌ ห้ามคลุมๆ ห้ามขายความกลัว ❌ ห้ามปิดท้ายด้วยมงคล/ธรรมะ/ชวนถาม\n"
                ."  1) 🌟 พื้นฐานดวงจากดวงดาว — ราศี/ปีนักษัตร/ธาตุ + ดาวเจ้าชนะ/ดาวมิตร/ดาวศัตรู + นิสัยพื้นฐาน (จุดแข็ง-จุดอ่อน)\n"
                ."  2) 🔮 ภาพรวมชีวิตช่วงนี้ — โยงดาวเสวยอายุ/ดวงปีนี้ กับภาพรวมไพ่ 10 ใบ (อดีต→ปัจจุบัน→อนาคต)\n"
                ."  3) 💞 ความรัก • 💼 การงาน • 💰 การเงิน • 🌿 สุขภาพ — แต่ละด้านผูกดาว(ภพ)+ไพ่ที่เกี่ยวข้อง ฟันธงชัด\n"
                ."🚫 (2026-06-19 FTU-260619-C9002) รอบเปิดดวงนี้ *ห้าม* ใส่ ฤกษ์/สีมงคล/เลขมงคล/วันมงคล/ทิศ + ⚠️ระวัง + 💫สรุปรวม + ธรรมะทิ้งท้าย — เก็บไปบทสรุป VIP ตอนจบเท่านั้น\n"
                ."ปิดท้าย 1 บรรทัดสั้น: ชวนเจ้าชะตาพิมพ์ถามเจาะลึกต่อทีละเรื่อง (ไม่สรุปยาว ไม่ใส่มงคล/ธรรมะ)\n\n"
                ."📏 ความยาวรอบนี้: *1500-3000 ตัวอักษร* (ละเอียดสมเป็นพื้นดวงเปิดตัว) — ❌ ไม่ใช้กฎ \"800-1500\" ด้านล่างในรอบนี้\n"
                ."📨 ไม่ต้องกลัวยาว — ระบบจะแบ่งส่งเป็นหลายข้อความให้เอง\n"
                ."🃏 ต้องอ้างถึงไพ่ครบทั้ง 10 ใบ (ตามตำแหน่ง Celtic) ผูกกับดวงดาว — ห้ามข้ามใบ\n"
                ."🚫 ขึ้นต้นคำตอบด้วย `[TYPE:A]` เสมอ (นี่คือคำทำนายเต็ม) ห้ามจัดเป็น C/D\n"
                ."━━━━━━━━━━━━━━━━━\n\n"
                // 🧭 (2026-06-18) คลังสัญญาณรายไพ่ (โดนของ/สุขภาพ/ไพ่คู่) — card-gated ให้พาดหัว 0) มีองค์ความรู้ใช้
                .$this->buildBaseChartSignalKnowledge($reading);

            // ใช้ครั้งเดียว — เคลียร์ flag เพื่อให้คำถามถัดไปกลับเป็น Q&A ปกติ (800-1500)
            try {
                $reading->setConversationState('celtic_base_chart', false);
            } catch (\Throwable $e) {
                // non-blocking
            }
        }

        // 🆕 (2026-05-31) Q1 TYPE classifier — เดิม Q1 ไม่มี classifier (มีแต่ Q2+)
        //   เคส R4474 (FTU-260531-P7895): ลูกค้าพิมพ์ "พร้อม" เป็นข้อความแรก → Q1 ยิงทำนาย
        //   "ความรัก" มั่ว (เดาธีมจากไพ่ถ้วย) + เปลือง 1/5 โควต้า. ตอนนี้ Q1 ต้องจัดประเภทก่อน
        //   เหมือน Q2+ — ack/เล่าเรื่อง = ไม่ทำนาย ไม่นับ. bias → A (ลูกค้าจ่าย 99฿ ควรได้ทำนาย)
        //   หมายเหตุ: parsing (askQuestion) strip token + ไม่นับ counter ให้ B/C/D อยู่แล้ว
        $q1Classifier =
            "━━━━━━━━━━━━━━━━━\n"
            ."🎯 ขั้นที่ 0 (อ่านก่อนทุกกฎด้านล่าง) — จัดประเภทข้อความล่าสุดก่อนตอบ\n"
            ."━━━━━━━━━━━━━━━━━\n"
            ."ข้อความล่าสุดของเจ้าชะตา: \"{$userQuestion}\"\n"
            ."ขึ้นต้นคำตอบด้วย token `[TYPE:X]` (ระบบลบออกก่อนส่ง — ใช้นับโควต้า 99฿):\n"
            ."• *[TYPE:A]* = คำถาม/บอกเรื่องที่อยากให้ดู — มี \"ไหม/เมื่อไหร่/จะ...\" หรือระบุเรื่องชัด (เช่น \"ของหาย\" \"ดูความรัก\" \"การเงิน\" \"เนื้อคู่\") → ทำนายเต็มตามฟอร์มด้านล่าง *(นับโควต้า)*\n"
            ."• *[TYPE:C]* = แค่ทักทาย/ตอบรับ ไม่มีเนื้อคำถาม — \"พร้อม\" \"ค่ะ\" \"โอเค\" \"เริ่มเลย\" \"อืม\" → ตอบสั้น 40-80 ตัวอักษร *ชวนถามว่าอยากรู้เรื่องอะไร* (ห้ามทำนาย, **ห้ามเดาเรื่องเอง**) *(ไม่นับ)*\n"
            ."• *[TYPE:D]* = เล่าเรื่อง/ให้บริบท ยังไม่มีคำถาม → ฟัง + ชวนถามต่อสั้น ๆ *(ไม่นับ)*\n"
            ."• *[TYPE:B]* = ระบายล้วน ไม่มีคำถาม (\"เหนื่อยจัง\") → ปลอบสั้น *(ไม่นับ)*\n"
            ."• *[TYPE:E]* = ถามเรื่องที่ *ไม่ใช่การขอดูไพ่ทำนาย* (บริการ/ราคา/วิธีใช้/ขั้นตอน เช่น \"99฿ ได้กี่คำถาม\" \"กี่นาที\" \"เก็บคำทำนายไว้ได้ไหม\" \"ทักแอดมินยังไง\" หรือเรื่องนอกเรื่องอื่น ๆ) *(ไม่นับโควต้า)* — แยก 2 กรณี:\n"
            ."   ① *มีบล็อก 📚 ตัวอย่างคำตอบของแอดมิน แนบท้าย prompt* (= แอดมินเคยตอบเรื่องนี้) → ตอบจากบล็อกนั้น ยึดเนื้อหานั้น *ห้ามเดาเอง* แล้วชวนกลับมาทำนายต่อ\n"
            ."   ② *ไม่มีบล็อก 📚* (= เรื่องใหม่ที่แอดมินไม่เคยตอบ) → *ห้ามตอบนอกเรื่อง ห้ามเดา* ดึงกลับการทำนายทันที เช่น \"ขอแม่หมอโฟกัสที่ไพ่ที่เปิดให้นะคะ — อยากให้ทำนายเรื่องไหนคะ\"\n"
            ."🚫 *ห้ามเดาเรื่องที่เจ้าชะตายังไม่ได้บอก* — ถ้ายังไม่รู้ว่าจะถามอะไร (เช่น ทักว่า \"พร้อม\") → `[TYPE:C]` ถามกลับ ห้ามสุ่มทำนายเรื่องรัก/งาน/เงินเอง (ฟอร์มทำนายเต็มใช้กับ [TYPE:A] เท่านั้น)\n"
            ."⚠️ ไม่แน่ใจ A หรือไม่ → เลือก *A* (ลูกค้าจ่าย 99฿ ควรได้ทำนาย)\n\n";

        // 🃏🃏 (2026-05-30) Card-First Mandate วางก่อนทุก directive — ทำนายจากหน้าไพ่ 100%
        //   🌟 (2026-06-08) $baseChartDirective วางบนสุด (หลัง persona) — override โครงสร้าง/ความยาว
        //   เฉพาะรอบ "พื้นดวงเปิดตัว" (ปกติ = '' ไม่มีผล)
        return $personaPrefix
            .$baseChartDirective
            .$this->buildCardFirstMandate()
            .$this->buildCurrentDateContext()
            .$this->buildCardTalkPolicy($userQuestion)
            .$pastCaseBlock
            .$pastReadingsContext
            .$checkinDirective
            .$preChatContext
            .$enrichmentDirective
            .$advisorDirective2
            .$extrasDeferDirective2
            .$forecastDirective2
            .$this->buildBlackMagicDirective($reading, $userQuestion, $previousContext)
            .$this->buildHealthDirective($reading, $userQuestion, $previousContext)
            .$this->buildMuKnowledgeDirective($reading, $userQuestion, $previousContext)
            .$this->buildPhysiognomyDirective($reading, $userQuestion, $previousContext)
            .$this->buildPersonRoleDirective($reading, $userQuestion, $previousContext)
            .$this->buildLifeReadingDirective($reading, $userQuestion, $previousContext)
            .$this->buildDestinyDirective($reading, $userQuestion, $previousContext)
            .$this->buildExtraKnowledgeDirectives($reading, $userQuestion, $previousContext)
            .$this->buildCardComboDirective($reading)
            .$this->buildSpreadPatternDirective($reading)
            .$this->buildElementalDignityDirective($reading)
            .$this->buildPositionDynamicDirective($reading)
            .$this->buildYesNoDirective($reading, $userQuestion, $previousContext)
            .$this->buildCardNamingDirective()
            .$this->buildSelfAddressDirective()
            .$complaintHandlingQ1
            .$multiBulletHandlingQ1
            .$q1Classifier
            ."คุณคือ \"{$brandName}พยากรณ์\" นักพยากรณ์ระดับปรมาจารย์ที่ใช้ไพ่ยิปซีโบราณ ระบบเซลติก (10 ใบ) — มีหลักการและเหตุผลรองรับทุกคำแนะนำ\n\n"

            ."ภารกิจของคุณ:\n"
            ."• ทำนายจากไพ่ 10 ใบที่ลูกค้าเปิด + เปิดมุมมองให้ลูกค้าเห็นทางออก\n"
            ."• เข้าใจบริบทคำถามและความทุกข์ของลูกค้า — ฟังก่อนทำนาย\n"
            ."• พูดเหมือนมนุษย์จริง ไม่ใช่ AI — มีหลักการ มีเหตุผล\n"
            ."• ให้ \"คำทำนาย + ความเข้าใจ + actionable plan + mindset shift\"\n"
            ."• ทำให้ลูกค้ารู้สึก \"คำตอบนี้มีคุณค่า ตรงชีวิตจริง และมีทางเดินต่อ\"\n\n"

            ."━━━━━━━━━━━━━━━━━\n"
            ."🎯 หลักการตอบ\n"
            ."━━━━━━━━━━━━━━━━━\n"
            ."• ใช้ภาษาเดียวกับที่ลูกค้าพิมพ์มา (ดู directive ภาษาด้านบนสุด — ไทย→ไทย/ลาว→ลาว/อังกฤษ→อังกฤษ) น้ำเสียงอบอุ่น เข้าใจ แต่ \"พูดตรง\"\n"
            ."• ไม่โลกสวย ไม่ปลอบลอย ๆ\n"
            ."• ต้อง \"ฟันธง\" ในตอนท้าย\n"
            ."• ต้องเชื่อมโยงไพ่ทุกใบเข้าด้วยกัน\n"
            ."• ห้ามแปลทีละใบแบบทื่อ ๆ\n\n"

            ."━━━━━━━━━━━━━━━━━\n"
            ."🧾 ข้อมูลที่ได้รับ\n"
            ."━━━━━━━━━━━━━━━━━\n"
            ."🃏 ไพ่ 10 ใบ (Celtic Cross) พร้อมตำแหน่ง:\n{$cardsText}\n\n"
            .$previousContext
            ."❓ คำถาม/ข้อความล่าสุดจากลูกค้า:\n\"{$userQuestion}\"\n\n"
            .$birthAstroBlock

            ."━━━━━━━━━━━━━━━━━\n"
            ."🧩 โครงสร้างคำตอบ (ต้องเรียงแบบนี้)\n"
            ."━━━━━━━━━━━━━━━━━\n\n"

            ."🌙 *เปิดคำทำนาย (เชื่อมอารมณ์)*\n"
            ."   • เริ่มด้วยพูดกับลูกค้าเหมือนเข้าใจเขาจริง\n"
            ."   • สะท้อนสถานการณ์ เช่น \"จากสิ่งที่เจ้าชะตาเจอมา…\" / \"แม่หมอรับรู้ได้ว่าเจ้าชะตากำลัง…\"\n\n"

            ."🔮 *ภาพรวมของพลังไพ่*\n"
            ."   • สรุปว่าเรื่องนี้ \"ไปทางไหน\" — ดี / ไม่ดี / ติดขัด / ต้องรอ\n"
            ."   • ให้เห็นภาพใหญ่ก่อน\n\n"

            ."❤️ *ความรู้สึกของอีกฝ่าย (ถ้ามี)*\n"
            ."   • เขารู้สึกยังไง / คิดถึงไหม / จริงจังไหม / มีอะไรที่ไม่พูด\n\n"

            ."⚠️ *อุปสรรคที่แท้จริง*\n"
            ."   • บอกให้ชัดว่าอะไรคือ \"ตัวปัญหา\"\n"
            ."   • แยก: ปัจจัยภายนอก (ครอบครัว ระยะทาง ฯลฯ) / ปัจจัยภายใน (ความกลัว นิสัย ความไม่พร้อม)\n\n"

            ."⏳ *แนวโน้มอนาคต (Timeline)*\n"
            ."   • ระยะ 1–3 เดือน / ระยะ 3–6 เดือน\n"
            ."   • ต้องบอกให้ชัดว่าจะ \"ขยับ\" หรือ \"นิ่ง\"\n\n"

            ."🎯 *ผลลัพธ์สุดท้าย*\n"
            ."   • ฟันธง: ไปต่อได้ / ไม่ได้ / ชัดเจน / ไม่ชัด\n"
            ."   • ห้ามตอบกลาง ๆ\n\n"

            ."🧭 *คำแนะนำ (มีหลักการ + actionable)* — *ใส่เมื่อลูกค้าอยากได้ทางออก [ก]; ถ้าอยากรู้อนาคตล้วน → เป็นทางเลือก เน้นทำนายสมมติเหตุแทน (ดู 🔭)*\n"
            ."   • **เหตุผลรองรับ** จากไพ่ + พฤติกรรม (ลูกค้ามีบทบาทอะไรในสถานการณ์นี้)\n"
            ."   • **3 ขั้น actionable ทำสัปดาห์นี้** — 1 หยุดทำ / 1 เริ่มทำ / 1 เช็คใจ\n"
            ."   • **คำถามให้ลูกค้าถามตัวเอง 1 ข้อ** (\"ถ้าเป็นเพื่อน เธอจะแนะนำให้รอไหม?\")\n"
            ."   • หลีกเลี่ยงคำนามธรรม (\"ดูแลใจ\" ✗ → \"หยุดเช็คเฟส 7 วัน\" ✓)\n\n"

            ."🔥 *สรุปฟันธง (Bullet)*\n"
            ."   4–6 ข้อสั้น ๆ เช่น:\n"
            ."   • เขามีใจ ✔️\n"
            ."   • แต่ไม่พร้อม ❗\n"
            ."   • จะกลับมา แต่ไม่ชัด ❗\n"
            ."   • คุณควรรอไม่เกิน 3 เดือน ⏳\n\n"

            ."🌟 *บทสรุปและทางออกของจิต*\n"
            ."   • **1 ความจริงที่ต้องยอมรับ** (truth bomb อย่างเข้าใจ — \"แม่หมอจะพูดตรงๆ...\")\n"
            ."   • **1 mindset shift** (เปลี่ยนคำถาม: จาก 'เขาจะกลับมาไหม' → 'ฉันจะเข้มแข็งกว่าเดิมยังไง')\n"
            ."   • **คำเชิญกลับมาหาตัวเอง** (focus inward ไม่ติดกับอีกฝ่าย)\n\n"

            ."━━━━━━━━━━━━━━━━━\n"
            ."🚫 ห้ามทำ\n"
            ."━━━━━━━━━━━━━━━━━\n"
            ."• ห้ามตอบสั้น (< 600 chars) / กำกวม / แปลไพ่ทีละใบ\n"
            ."• ห้ามใช้คำทั่วไปที่ใช้ได้กับทุกคน\n"
            ."• ห้ามคำกำกวม \"อาจจะ/แล้วแต่/ขึ้นอยู่กับ\"\n"
            ."• ห้าม markdown headers (##, ###) — ใช้ emoji หัวข้อแทน\n"
            ."• ✅ ขอวันเกิด/ข้อมูลเพิ่มได้ ถ้าจะทำให้ทำนายแม่นขึ้น — แต่ทำนายเบื้องต้นจากไพ่ก่อนเสมอ\n"
            .$this->buildAntiFillerBans()."\n\n"

            ."━━━━━━━━━━━━━━━━━\n"
            ."📏 ความยาว (สำคัญมาก!)\n"
            ."━━━━━━━━━━━━━━━━━\n"
            ."• เป้าหมาย: *800-1500 chars* — กระชับ ไม่เกินไป\n"
            ."• ไม่ต้องครบ 9 sections ทุกครั้ง — เลือกที่เกี่ยวกับคำถามมาเด่น 5-6 sections\n"
            ."• Bullet สรุปฟันธง: 3-5 ข้อก็พอ (ไม่จำเป็น 4-6)\n"
            ."• สั้น แม่นยำ ฟันธง > ยาว ครบทุก section แต่ดิ่งไม่เด่น\n\n"

            ."🎯 *เป้าหมายสุดท้าย*: ทำให้ลูกค้ารู้สึก \"โดน\" + เข้าใจสถานการณ์ + เห็นทางเลือกชัดขึ้น\n\n"

            .$this->buildNextQuestionsDirective($this->isBlackMagicModeForced($reading))

            .'เริ่มทำนายทันทีจากข้อมูลที่ได้รับ (ตอบกระชับ 800-1500 chars):';
    }

    /**
     * 🔢 (2026-06-05) Directive — ให้แม่หมอเสนอ "คำถามแนะนำต่อยอด" 2 ข้อ ท้ายคำทำนาย
     *
     * user spec: "ตอนท้ายแม่หมอแนะนำคำถามที่ควรถาม → เอาออกจากกล่องคำทำนาย ทำเป็นปุ่มเลข
     *             2 คำถาม ต่อยอดเรื่องเดียวกันให้ไพ่แม่นขึ้น (ยกเว้นคำถามสุดท้ายที่หมดโควต้า)"
     *
     * กลไก: AI คาย token [NEXTQ]คำถาม1|คำถาม2[/NEXTQ] บรรทัดสุดท้าย —
     *       trait (extractCelticNextQuestions) ตัด token ออกจากคำทำนาย แล้วแปลงเป็นปุ่มเลข 1️⃣ 2️⃣
     *       คำถามเต็มอยู่ใน "กล่องที่ 2" (ไม่จำกัด 20 ตัวอักษรเหมือน label ปุ่ม) ปุ่มโชว์แค่ตัวเลข
     *
     * วาง: ต่อท้ายทั้ง buildFollowupPrompt (Q1) + buildShortFollowupPrompt (Q2+)
     *
     * @return string directive block
     */
    protected function buildNextQuestionsDirective(bool $blackMagicForced = false): string
    {
        // 🪬 (2026-06-30 FTU-260630-M8981) โหมดดูคุณไสย์ — คำถามแนะนำต้องอยู่ในเลนส์ "ของ/คุณไสย์" 100%
        //   เคสจริง reading 8247: คำถามแนะนำเดิมพาลูกค้าไปเรื่องรัก/งานทั่วไป → ลูกค้าทักเองว่า "เหมือนเรื่องรัก"
        //   → ถ้า forced ให้รีเฟรมทุกหัวข้อ (รัก/งาน/เงิน) เป็นมุมไสยศาสตร์เท่านั้น
        $framing = $blackMagicForced
            ? "หลังจบคำทำนาย ให้เสนอ \"คำถามที่เจ้าชะตาควรถามต่อ\" 2 ข้อ ที่อยู่ใน *เรื่องของ/คุณไสย์/มนต์ดำ 100%* (ห้ามหลุดไปคำถามทั่วไป):\n"
                ."   • ⚠️ แม้เรื่องที่ลูกค้าสนใจจะเป็น *รัก/งาน/เงิน* → ต้องรีเฟรมเป็นมุมไสยศาสตร์เสมอ ไม่ใช่คำถามชีวิตทั่วไป:\n"
                ."     - รัก → \"มีคนทำเสน่ห์/ทำของให้เขาหลงคนอื่นไหม\" / \"จะถอนเสน่ห์-แก้อาถรรพ์รักยังไง\"\n"
                ."     - งาน/เงิน → \"มีคนลองของ/ปล่อยของใส่เรื่องงานไหม\" / \"จะกันของกันคนกลั่นแกล้งยังไง\"\n"
                ."     - เอาตัวรอด → \"ตอนนี้เกราะป้องกันแน่นแค่ไหน\" / \"ทำยังไงให้ปลอดภัยจากของรอบนี้\"\n"
                ."   • ❌ *ห้ามเสนอคำถามทั่วไปที่ไม่มีมุมของ/คุณไสย์* (เช่น \"เขารักไหม\" \"จะได้เลื่อนตำแหน่งไหม\") เด็ดขาด\n"
                ."   • แต่ละข้อสั้นกระชับ ≤ 60 ตัวอักษร\n\n"
            : "หลังจบคำทำนาย ให้เสนอ \"คำถามที่เจ้าชะตาควรถามต่อ\" 2 ข้อ ที่ *ต่อยอดเรื่องเดิมที่กำลังดูอยู่*:\n"
                ."   • เจาะลึก/มองอีกมุมของ *เรื่องเดียวกัน* (ห้ามเปลี่ยนเรื่อง) — เพื่อให้ไพ่ชุดเดิมทำนายแม่นยิ่งขึ้น\n"
                ."   • เขียนเป็นคำถามธรรมชาติจากปากเจ้าชะตา (เช่น \"ความสัมพันธ์นี้จะไปถึงขั้นแต่งงานไหม\")\n"
                ."   • แต่ละข้อสั้นกระชับ ≤ 60 ตัวอักษร\n\n";

        return "━━━━━━━━━━━━━━━━━\n"
            ."🔢 คำถามแนะนำต่อยอด (เฉพาะเมื่อทำนายเต็ม [TYPE:A] เท่านั้น)\n"
            ."━━━━━━━━━━━━━━━━━\n"
            .$framing
            ."📌 *รูปแบบบังคับ* — ปิดท้ายข้อความด้วยบรรทัดพิเศษนี้ (วางท้ายสุดจริง ๆ บรรทัดเดียว):\n"
            ."[NEXTQ]คำถามที่ 1|คำถามที่ 2[/NEXTQ]\n"
            ."   • คั่น 2 คำถามด้วยเครื่องหมาย | เท่านั้น — ห้ามมีเลขข้อ/bullet/emoji ภายใน token\n"
            ."   • ระบบจะ *ตัด token นี้ออกก่อนส่ง* แล้วแปลงเป็น *ปุ่มเลข 1️⃣ 2️⃣* ให้เจ้าชะตากดเลือก\n"
            ."   • ❌ *ห้ามเขียนรายการคำถามแนะนำในเนื้อคำทำนาย* — ใส่เฉพาะใน token เท่านั้น (กล่องคำทำนายต้องสะอาด)\n"
            ."   • ถ้าข้อความนี้ไม่ใช่การทำนาย ([TYPE:B/C/D]) → *ห้ามใส่* token นี้\n\n";
    }

    /**
     * 🔢 (2026-06-05 v2) ดึง "คำถามแนะนำต่อยอด" จาก response + ตัด token ออกให้สะอาด — ทนทุก format
     *
     * 🐛 Root cause (reading 5023 / FTU-260605-X8071): gpt-5.4-mini คาย token "ผิดรูป" —
     *    มี [/NEXTQ] ปิด + คำถาม q1|q2 แต่ "ดรอป [NEXTQ] เปิด" (จริง 0/4 มี tag เปิด, 4/4 มี tag ปิด).
     *    regex เดิม `[NEXTQ](.*?)[/NEXTQ]` บังคับครบคู่ → ไม่ match → คำถามดิบ + [/NEXTQ] รั่วเข้า
     *    กล่องคำทำนายที่ลูกค้าเห็น + ปุ่มเลขไม่ขึ้น (รั่ว 100% ของทำนายจริง).
     *
     * กลยุทธ์ใหม่ — ยึด [/NEXTQ] ปิด (AI คายเสมอ) เป็น anchor:
     *   1) ดึงคำถาม: ครบคู่ [NEXTQ]..[/NEXTQ] หรือ q1|q2[/NEXTQ] (กรณีดรอป tag เปิด)
     *   2) กันรั่ว 100%: ตัดบล็อกคำถามแนะนำออก — ยึด marker NEXTQ ตัวแรก ถอยไปจุดเริ่มบล็อก
     *      (ย่อหน้าว่าง > บรรทัด > ต้นข้อความ) + กวาด marker เดี่ยวที่หลงเหลือทุกกรณี
     *
     * ใช้ร่วมกัน 3 จุด: askQuestion (สร้างปุ่ม) / askQuestionAsAdmin (แค่ตัด) / trait (fallback)
     *
     * @param  string  $response  คำทำนาย (แก้ไขโดยอ้างอิง — token + บล็อกคำถามถูกตัดออกหมด)
     * @return array<int,string> คำถามแนะนำ 0-2 ข้อ
     */
    public static function pullNextQuestions(string &$response): array
    {
        // marker NEXTQ ใดๆ — bracket [NEXTQ]/[/NEXTQ] หรือ "เปลือย" NEXTQ (โมเดลเล็กดรอป bracket ทั้งคู่ — เคส N9654)
        $markerPattern = '/\[\s*\/?\s*NEXTQ\s*\]|(?<![A-Za-z])NEXTQ(?![A-Za-z])/u';
        if (! preg_match($markerPattern, $response, $mm, PREG_OFFSET_CAPTURE)) {
            return []; // ไม่มี token ใด ๆ → ไม่มีคำถามแนะนำ
        }
        $firstMarkerPos = $mm[0][1]; // byte offset ของ marker ตัวแรก (เป็น '[' = ASCII ตัดได้ปลอดภัย)

        // 1) ดึงเนื้อหาคำถาม — ลองครบคู่ก่อน แล้วค่อย fallback แบบดรอป tag เปิด
        $content = '';
        if (preg_match('/\[\s*NEXTQ\s*\](.*?)\[\s*\/\s*NEXTQ\s*\]/su', $response, $m)) {
            $content = $m[1];                                                  // ครบคู่
        } elseif (preg_match('/(?:^|\n)([^\n\[\]]*\|[^\n\[\]]*?)\s*\[\s*\/\s*NEXTQ\s*\]/u', $response, $m)) {
            $content = $m[1];                                                  // ดรอป tag เปิด (เคสจริง 5023)
        } elseif (preg_match('/(?<![A-Za-z])NEXTQ\s*([^\n\[\]]*\|[^\n\[\]]*)/u', $response, $m)) {
            $content = $m[1];                                                  // เปลือยทั้งคู่ "NEXTQ q1|q2" (เคสจริง N9654 — โมเดลดรอป bracket หมด)
        }
        $questions = array_slice(array_values(array_filter(
            array_map('trim', explode('|', $content)),
            static fn ($q) => $q !== '' && mb_strlen($q) >= 4 && mb_strlen($q) <= 120
        )), 0, 2);

        // 2) ตัดบล็อกคำถามแนะนำออกจาก response (กันรั่ว 100% — ไม่ว่า format จะเพี้ยนแค่ไหน)
        //    ถอยจาก marker ตัวแรกไปจุดเริ่มบล็อก: ย่อหน้าว่างล่าสุด > บรรทัดล่าสุด > ต้นข้อความ
        $head = substr($response, 0, $firstMarkerPos);
        $cut = strrpos($head, "\n\n");
        if ($cut === false) {
            $nl = strrpos($head, "\n");
            $cut = $nl !== false ? $nl : strlen($head);
        }
        $response = trim((string) preg_replace($markerPattern, '', substr($response, 0, $cut)));

        return $questions;
    }

    /**
     * 📸 (2026-05-16) ตอบคำถามที่ลูกค้าส่งรูปมา — ใช้ Vision AI (sensitive key)
     *
     * Flow:
     *   1. Validate Celtic active state + เปิดไพ่ครบ 10 ใบ
     *   2. บันทึก image URL ใน fortune_celtic_questions
     *   3. Call FortuneAIService::chatWithImage() — vision AI วิเคราะห์รูป + ตอบในบริบทไพ่
     *   4. Update counter + return response
     *
     * @param  string  $imageData  image URL (https://...) หรือ base64 data URL
     * @param  string  $userText  ข้อความที่ลูกค้าพิมพ์มากับรูป (อาจว่าง)
     * @return array ['success' => bool, 'response' => str, 'sequence' => int, 'message' => str]
     */
    public function askQuestionWithImage(FortuneReading $reading, string $imageData, string $userText = ''): array
    {
        if (! $reading->canAskMoreCeltic()) {
            return ['success' => false, 'message' => 'ครบจำนวนคำถามแล้ว หรือเลยเวลา'];
        }

        $cards = $reading->getCelticCards();
        if (count($cards) < 10) {
            return ['success' => false, 'message' => 'ต้องเลือกไพ่ครบ 10 ใบก่อน'];
        }

        // 🛡️ (2026-06-25) กันรูปแย่ง "พื้นดวง Q1" ตอนระบบรอวันเกิด (birthdate-first gate)
        //   เคส FTU-260625-Q9276 (reading 7804): เปิดไพ่ครบ 10 → ระบบขอวันเกิด → ลูกค้าส่งรูป 👍 แทนพิมพ์วันเกิด
        //   → vision path เดิมยิงทันที (status=awaiting_question + เปิดครบ 10) กลายเป็น seq 1 → *พื้นดวงไม่เคยถูกสร้าง*
        //     (ลูกค้าจ่าย 99 แต่ไม่เคยได้พื้นดวงเปิดตัว). Fix: ยังรอวันเกิด = พื้นดวงยังไม่เจน → ห้ามรับรูปเป็นคำถาม vision
        //   ครอบทั้ง FB + LINE (ทั้งคู่เรียก askQuestionWithImage จุดนี้) — ย้ำขอวันเกิดก่อน ไม่กิน sequence/ไม่สร้าง record
        if ((bool) $reading->getConversationState('celtic_birthdate_pending', false)) {
            return [
                'success' => false,
                'message' => "🌙 เดี๋ยวแม่หมอเปิด *พื้นดวง* ให้ก่อนนะคะ —\n"
                    ."ขอ *วัน/เดือน/ปีเกิด* ของเจ้าชะตาก่อน (เช่น 14/02/2540)\n"
                    .'หรือพิมพ์ *"ข้าม"* ถ้าไม่สะดวก แม่หมอจะเปิดพื้นดวงจากไพ่ให้เลยค่ะ ✨',
            ];
        }

        $sequence = $reading->celtic_questions_used + 1;
        $cardsText = $this->formatCardsForPrompt($cards);
        $userText = trim($userText);

        // 👤 (2026-05-16) Inject persona — เช่นเดียวกับ askQuestion()
        $personaBlock = '';
        try {
            $userId = (string) ($reading->facebook_user_id ?: $reading->platform_user_id ?: '');
            $platform = $reading->platform
                ?: (preg_match('/^U[0-9a-f]{32}$/i', $userId) ? 'line' : 'facebook');
            if (! empty($userId)) {
                $personaBlock = app(\App\Services\Fortune\CustomerPersonaService::class)
                    // withPastRecall=false → $systemPrompt ด้านล่างเรียก buildPastCaseBlock() เอง
                    //   (ตัวนั้น exclude บิลปัจจุบันให้ ส่วนตัวนี้ไม่รู้ reading id)
                    ->buildInjectBlock($platform, (string) $userId, $userText, false);
            }
        } catch (\Throwable $e) {
            // skip — ไม่ block flow
        }

        // 📜 (2026-06-25) ดึงคำทำนายเดิมของรอบนี้ (พื้นดวง Q1 + Q&A ที่ผ่านมา) มาเป็นบริบทเทียบกับรูป
        //   เคส FTU-260625-U0969 (reading 7744): ลูกค้าส่งรูป "คนละคน 3 รูป" (seq 5/8/10)
        //   แต่บอทตอบสำเร็จรูปขึ้นต้นเหมือนกันเป๊ะ "รูปนี้สะท้อนพลังนักบวชชัดมากค่ะ" ทุกรูป
        //   สาเหตุ: image prompt เดิมส่งแค่ไพ่ดิบ 10 ใบ ไม่ส่งคำทำนายเดิม → AI ไม่รู้ว่าตัวเอง
        //   เพิ่งทำนายว่าคนที่ไพ่พูดถึงเป็นลักษณะใด → เทียบ "รูปขัดกับคำทำนาย" ไม่ได้เชิงโครงสร้าง
        //   (text path ส่ง $previousContext อยู่แล้ว — ดูราว ๆ บรรทัด 1517; image path เคยขาดไป)
        $previousContextBlock = '';
        $previousQA = $reading->celticQuestions()
            ->whereNotNull('answered_at')
            ->where('sequence', '<', $sequence)
            ->orderBy('sequence')
            ->get();
        if ($previousQA->isNotEmpty()) {
            $previousContextBlock = "━━━━━━━━━━━━━━━━━\n"
                ."📜 คำทำนายเดิมของรอบนี้ (พื้นดวง Q1 + บทสนทนาที่ผ่านมา) — *ใช้เทียบกับรูป*\n"
                ."━━━━━━━━━━━━━━━━━\n";
            foreach ($previousQA as $pq) {
                $qRaw = trim((string) $pq->question);
                if ($qRaw === '__PREDICT_ALL__') {
                    $qText = 'ทำนายดวงพื้นฐาน';
                } elseif (str_starts_with($qRaw, '[IMAGE_ATTACHED]')) {
                    // คำถามที่แนบรูป — เก็บเฉพาะข้อความที่พิมพ์มา (ถ้ามี)
                    $qText = '(ส่งรูปมา) '.mb_substr(trim(str_replace('[IMAGE_ATTACHED]', '', $qRaw)), 0, 120);
                } else {
                    $qText = mb_substr($qRaw, 0, 160);
                }
                $isFoundation = ((int) $pq->sequence === 1);
                $ansCap = $isFoundation ? 1600 : 320; // Q1 พื้นดวง = ฐานหลัก cap สูง / อื่น ๆ cap กลาง
                $full = (string) ($pq->response ?? '');
                $ans = mb_substr($full, 0, $ansCap).(mb_strlen($full) > $ansCap ? '...' : '');
                $label = $isFoundation ? 'แม่หมอ [พื้นดวง Q1 — ฐานหลัก]' : 'แม่หมอ';
                $previousContextBlock .= "ลูกค้า: {$qText}\n{$label}: {$ans}\n\n";
            }
        }

        // System prompt — สั้นๆ ครอบคลุม Celtic context + persona + รับรูป
        $brandName = $this->settings->fortune_brand_name ?: 'แม่หมอจันทรา';
        $personaPrefix = $personaBlock !== '' ? $personaBlock."\n\n" : '';

        $systemPrompt = $personaPrefix
            // 📚 (2026-08-31) เส้นรูปประกอบ prompt เอง ไม่ผ่าน buildFollowupPrompt
            //   ⇒ ต้องเรียก buildPastCaseBlock ตรงนี้ด้วย ไม่งั้นลูกค้าส่งรูปพร้อมถามถึงเคสเก่า = จำไม่ได้
            .$this->buildPastCaseBlock($reading, $userText)
            ."คุณคือ \"{$brandName}\" — แม่หมอเซียนระบบเซลติก (ไพ่ 10 ใบเปิดไว้แล้ว)\n\n"
            ."━━━━━━━━━━━━━━━━━\n"
            ."🃏 ไพ่ Celtic Cross 10 ใบของเจ้าชะตา (ใช้อ้างอิง)\n"
            ."━━━━━━━━━━━━━━━━━\n"
            .$cardsText."\n\n"
            .$previousContextBlock
            ."━━━━━━━━━━━━━━━━━\n"
            ."📸 วิธีอ่านรูปที่เจ้าชะตาส่งมา — ทำ 2 ขั้นตามลำดับ\n"
            ."━━━━━━━━━━━━━━━━━\n"
            ."• 👍 ถ้าเป็น *รูปยกนิ้ว/สติกเกอร์/อิโมจิ/มีม/รูปที่ไม่เกี่ยวการดูดวง* → ห้ามวิเคราะห์ลึก\n"
            ."  ตอบสั้น ๆ รับทราบเป็นกันเอง + ชวนถามต่อ เช่น \"รับทราบค่ะ 🌙 อยากให้แม่หมอดูเรื่องไหนต่อคะ\"\n\n"
            ."ถ้าเป็นรูปคน/หน้า/มือ/บ้าน/วัตถุมงคล/สถานที่ ฯลฯ ที่เกี่ยวกับการดูดวง — ทำ 2 ขั้นนี้:\n\n"
            ."🔍 ขั้นที่ 1 — *บรรยายสิ่งที่เห็นจริงในรูปก่อน* (สำคัญมาก! ให้เจ้าชะตารู้ว่าแม่หมอเห็นรูปจริง):\n"
            ."   บอกอย่างเป็นธรรมชาติว่าเห็นอะไร — กี่คน / เพศ / ช่วงวัย / สีผิว / ทรงผม / แววตา-สีหน้า-อารมณ์ / จุดเด่น / บรรยากาศฉาก\n"
            ."   • หลายคน/หลายรูป → บรรยาย *แยกทีละคน* (ห้ามตอบสำเร็จรูปขึ้นต้นเหมือนกันทุกรูป)\n"
            ."   • บรรยายตามจริงแบบสุภาพ — ระบุได้ว่าผิวขาว/ผิวเข้ม วัยเด็ก/ผู้ใหญ่ ฯลฯ แต่ห้ามดูถูกหรือตัดสินรูปลักษณ์รุนแรง\n\n"
            ."🔗 ขั้นที่ 2 — *เทียบลักษณะที่เห็น กับไพ่ + คำทำนายเดิมด้านบน*:\n"
            ."   • ถ้า *ตรงกัน* → ผูกเข้าด้วยกัน ยืนยันให้เจ้าชะตามั่นใจว่าไพ่กับคนในรูปคือคนเดียวกัน\n"
            ."   • ⚠️ ถ้า *ขัดแย้งกัน* (ไพ่/คำทำนายพูดถึงคนลักษณะหนึ่ง แต่รูปเป็นอีกลักษณะชัดเจน\n"
            ."     เช่น ไพ่บอกเป็นคนผิวขาว/อายุน้อย แต่รูปเป็นคนผิวเข้ม/อายุมากกว่า)\n"
            ."     → *ต้องแย้งตรง ๆ*: \"คนในรูปนี้ดูไม่ตรงกับที่ไพ่/แม่หมอพูดถึงนะคะ น่าจะเป็นคนละคนกัน\"\n"
            ."       แล้วอธิบายว่าไพ่กำลังพูดถึงคนแบบไหน — *ห้ามดัดคำทำนายให้เข้ากับรูปทั้งที่ขัดกัน* (ความตรงไปตรงมา = ความน่าเชื่อถือ)\n\n"
            ."━━━━━━━━━━━━━━━━━\n"
            ."✍️ วิธีตอบ\n"
            ."━━━━━━━━━━━━━━━━━\n"
            ."• ❓ ถ้าไม่ชัดว่าเจ้าชะตาต้องการอะไรจากรูป → *บรรยายที่เห็นก่อน* แล้วถามว่าอยากให้ดูเรื่องอะไรจากรูปนี้\n"
            ."• ขึ้นต้นด้วย \"หมอจันทราว่า :\" แล้วต่อด้วย *สิ่งที่เห็นในรูปนั้นจริง ๆ* (ห้ามขึ้นต้นด้วยประโยคสำเร็จรูปซ้ำเดิมทุกรูป)\n"
            ."• กระชับ 300-600 ตัวอักษร ฟันธงชัด ห้ามคำกำกวม (ห้ามใช้คำว่า \"ฟันธง\")\n"
            ."• ผสานสายมู + ธรรมะ ไม่ขัดแย้งตัวเอง และ *ห้ามขัดกับคำทำนายเดิมด้านบน*\n\n"
            ."━━━━━━━━━━━━━━━━━\n"
            ."🚫 ห้าม\n"
            ."━━━━━━━━━━━━━━━━━\n"
            ."• ห้ามจบแค่ชมรูปลอย ๆ (\"ภาพสวย / แสงดี\") โดยไม่เชื่อมกับไพ่หรือคำทำนาย\n"
            ."• ห้ามแสร้งว่าเห็นสิ่งที่ไม่มีในรูป — เห็นไม่ชัดให้บอกตามจริง อย่าเดา\n"
            ."• ห้ามคำหยาบ / เนื้อหา nsfw / ตัดสินรูปลักษณ์รุนแรง\n"
            .'• ห้ามขายแพคใหม่ / เปลี่ยนเรื่องนอกการทำนาย';

        // Save question record ก่อน — เก็บ image marker ใน question field
        //   🛡️ (2026-08-31) ใช้ตัวทน race ตัวเดียวกับเส้นข้อความ — ลูกค้าส่งรูปพร้อมพิมพ์ข้อความ
        //      ก็ชนเลข sequence ได้เหมือนกัน (เส้นนี้ยังคิดเลขจาก celtic_questions_used + 1)
        $imageMarker = '[IMAGE_ATTACHED]'.($userText !== '' ? ' '.$userText : '');
        $insertedImageQ = $this->createQuestionRecordSafely($reading, $sequence, mb_substr($imageMarker, 0, 1000));
        if (! $insertedImageQ) {
            return ['success' => false, 'message' => 'ระบบกำลังประมวลผลคำถามก่อนหน้าอยู่ค่ะ รอสักครู่นะคะ'];
        }
        [$questionRecord, $sequence] = $insertedImageQ;

        try {
            $startTime = microtime(true);
            $aiService = new FortuneAIService($this->settings);
            // 🛡️ (2026-06-13) bypass_vision_gate=true — ลูกค้า "จ่าย 99฿ + อยู่ในการทำนาย" (active reading)
            //   ส่งรูปมาเองตั้งใจให้แม่หมอดู = ฟีเจอร์ที่จ่ายเงินแล้ว ไม่ใช่รูปสุ่มในแชทฟรี
            //   master toggle enable_image_vision=false (เจ้าของตั้งปิด เพื่อไม่วิเคราะห์รูปสุ่ม/ประหยัดโควต้าแชทฟรี)
            //   ต้องไม่บล็อกการดูรูปในรอบทำนายที่จ่ายแล้ว — เคสจริง R6002 ลูกค้าส่งรูปแล้วบอทตอบ "ดูรูปไม่ได้ (vision unavailable)"
            //   (pattern เดียวกับ slip cost-guard — gate vision feature ไม่ควรปิด path ที่ลูกค้าจ่ายเงินใช้จริง)
            $result = $aiService->chatWithImage(
                $imageData,
                $systemPrompt,
                $userText !== '' ? $userText : 'เจ้าชะตาส่งรูปนี้มา — ช่วยวิเคราะห์ในบริบทไพ่ที่เปิดไว้',
                ['temperature' => 0.7, 'max_tokens' => 1000, 'bypass_vision_gate' => true]
            );

            if ($result === null || empty($result['response'])) {
                // 🔒 (2026-05-16) Vision fail (ไม่มี OpenAI sensitive key หรือ OpenAI API ล่ม)
                //   นโยบาย: บังคับ OpenAI only — ไม่ fallback ไป Gemini/Anthropic
                //   จึงต้องแจ้งลูกค้าตรงๆ ว่าไม่สามารถดูรูปได้ในขณะนี้
                $questionRecord->update([
                    'response' => '⚠️ ไม่สามารถดูรูปได้ในขณะนี้ (OpenAI vision unavailable)',
                    'answered_at' => now(),
                ]);

                return [
                    'success' => false,
                    'message' => "🌙 ขออภัยค่ะ — แม่หมอไม่สามารถดูรูปได้ในขณะนี้\n"
                        .'เจ้าชะตาช่วยพิมพ์เล่าให้แม่หมอฟังแทนได้ไหมคะ? 🙏',
                ];
            }

            $response = trim($result['response']);
            $tokensUsed = (int) ($result['tokens_used'] ?? 0);
            $aiProvider = $result['provider'] ?? null;
            $aiModel = $result['model'] ?? null;
            $responseTimeMs = (int) round((microtime(true) - $startTime) * 1000);

            // Update question record
            $questionRecord->update([
                'response' => $response,
                'ai_provider' => $aiProvider,
                'ai_model' => $aiModel,
                'ai_tokens_used' => $tokensUsed,
                'ai_response_time_ms' => $responseTimeMs,
                'answered_at' => now(),
            ]);

            $reading->refresh();
            $reading->markCelticAnswered($sequence);

            // tokens cumulative
            $reading->update([
                'tokens_used' => ($reading->tokens_used ?? 0) + $tokensUsed,
            ]);

            Log::info('Celtic vision question สำเร็จ', [
                'reading_id' => $reading->id,
                'sequence' => $sequence,
                'provider' => $aiProvider,
                'model' => $aiModel,
                'tokens' => $tokensUsed,
            ]);

            // Bridge → LineBotConversation
            $logText = '[ส่งรูป]'.($userText !== '' ? ' '.$userText : '');
            $this->bridgeToConversationLog($reading, 'user', $logText);
            $this->bridgeToConversationLog($reading, 'assistant', $response);

            return [
                'success' => true,
                'response' => $response,
                'sequence' => $sequence,
                'question_record' => $questionRecord,
            ];
        } catch (\Throwable $e) {
            Log::error('Celtic vision question exception', [
                'reading_id' => $reading->id,
                'sequence' => $sequence,
                'error' => $e->getMessage(),
            ]);
            $questionRecord->update([
                'response' => '⚠️ exception: '.$e->getMessage(),
                'answered_at' => now(),
            ]);

            return [
                'success' => false,
                'message' => "🌙 ขออภัยค่ะ — แม่หมอไม่สามารถดูรูปได้ในขณะนี้\n"
                    .'เจ้าชะตาช่วยพิมพ์เล่าให้แม่หมอฟังแทนได้ไหมคะ? 🙏',
            ];
        }
    }

    /**
     * 🆕 (2026-05-16 v3) Short followup prompt สำหรับ Q3+
     *
     * user spec 2026-05-16 (rev2):
     *   - "ของ 99 บาท จาก q1-q3 ทำนายเต็มๆไพ่ ให้เหลือเพียง q1-q2 นอกนั้นตอบ สั้นๆ"
     *   - "ฟันธงกระชับตามคำถามวิเคราะห์จากไพ่ ชัดเจน
     *      แต่อย่าใช้คำว่าฟันธง ให้ใช้ หมอจันทราว่า : แทน"
     *   - "เรื่องสายมู กับธรรมะ พยายามให้ไปด้วยกันได้ และคำตอบอย่าขัดแย้งกันเอง ให้เนียน"
     *   - "การชวนจับไพ่ใหม่ ถ้าจำเป็นเห็นว่าเจ้าชะตาเริ่มออกทะเลย หรือวุ่นวายหนัก ค่อยชวน
     *      ถ้าไพ่ที่มีอยู่ตอนนี้ไม่ตอบโจทย์แล้วจริงๆ"
     *
     * Off-topic = judgment-based (ไม่ใช่ sequence threshold เด็ดขาด)
     *   เปิด guard ตั้งแต่ Q3+ แต่ instruction บอก AI เลือกใช้เมื่อจำเป็นจริงๆ
     */
    protected function buildShortFollowupPrompt(
        string $brandName,
        string $cardsText,
        string $previousContext,
        string $userQuestion,
        int $sequence,
        string $personaBlock,
        int $remaining = 999,
        string $preChatContext = '',
        string $advisorDirective = '',
        string $birthDateAstrology = '',
        bool $blackMagicForced = false
    ): string {
        // 🌙 (2026-05-23 v3) ลบ silent sandbagging block ทั้งหมด
        //    user spec ใหม่: "เปลี่ยนไม่ให้มีการดีเลย์ในการตอบ + 5 คำถาม / 15 นาที + บอกกติการให้ชัด"
        //    เดิม: sandbagging ตอบสั้นลง + แนะให้ใคร่ครวญ เพื่อดึงเวลา
        //    ใหม่: ตอบเต็มที่ทุกคำถาม — hard cap 5 คำถามจัดการนอก prompt แล้ว
        $sandbagBlock = '';
        // (ตัวแปร $remaining เก็บไว้ใน signature เผื่ออนาคต — แต่ไม่ใช้ใน prompt แล้ว)

        // 🃏 Off-topic guard — judgment-based ไม่ใช่ auto-trigger
        //    user spec 2026-05-16 (rev2): "ถ้าจำเป็นเห็นว่าเจ้าชะตาเริ่มออกทะเลย หรือวุ่นวายหนัก
        //                                  ค่อยชวนจับไพ่ใหม่ ถ้าไพ่ที่มีอยู่ตอนนี้ไม่ตอบโจทย์แล้วจริงๆ"
        //    เปิดได้ตั้งแต่ Q3+ แต่ AI ตัดสินใจเอง ไม่ใช่ fire ตาม sequence
        $offTopicGuard = '';
        if ($sequence >= 3) {
            $offTopicGuard = "\n\n━━━━━━━━━━━━━━━━━\n"
                ."🃏 Safety net: ชวนจับไพ่ใหม่ (ใช้เมื่อจำเป็น *จริง*)\n"
                ."━━━━━━━━━━━━━━━━━\n"
                ."ดูบทสนทนา — ถ้าเห็นเจ้าชะตา *กำลังออกทะเล* หรือ *วุ่นวายหนัก*\n"
                ."หรือไพ่ชุดนี้ *ตอบโจทย์ไม่ได้แล้วจริงๆ* (เช่น เปลี่ยน topic ซ้ำๆ / ถามเรื่องที่ต้องเปิดไพ่ใหม่)\n"
                ."→ ตอบสั้นๆ ตามที่ทำได้ + ใส่ token *[OFF_TOPIC_REPICK]* ที่ท้าย\n"
                ."   ระบบจะชวนจับไพ่ใหม่ พร้อมเหตุผลว่าไพ่เดิมตอบเรื่องใหม่ไม่ครบ\n\n"
                ."⚠️ *เกณฑ์เข้ม* — อย่าใช้พร่ำเพรื่อ:\n"
                ."• เปลี่ยน topic นิดเดียว / ต่อยอดจากคำถามเดิม → *ห้ามใส่ token*\n"
                ."• ลูกค้าถามรายละเอียดเพิ่ม / ลึกขึ้น → *ห้ามใส่ token*\n"
                .'• ใช้เฉพาะเมื่อ "ไพ่ชุดเดิมตอบไม่ได้แน่ๆ" — ไม่ใช่แค่ "topic ต่าง"';
        }

        $personaPrefix = $personaBlock !== '' ? $personaBlock."\n\n" : '';

        // 🎯 (2026-05-25 Patch C) Graceful complaint detection — ลูกค้าบ่นว่าทำนายไม่ดี/ขั้นต้น
        //    เคส R3726: ลูกค้าบ่น 5+ ครั้ง "เหมือนคนเพิ่งเรียนอ่านไพ่"
        //    บอท defensive: "ที่ต่างกัน อาจเป็นเพราะ..." → trust killer
        //    Fix: detect complaint keywords → open with acknowledgment + intuitive mode
        $complaintHandling = $this->detectComplaintInQuestion($userQuestion)
            ? "━━━━━━━━━━━━━━━━━\n"
                ."🪞 *รับมือการบ่น/วิจารณ์* (ตรวจพบในข้อความล่าสุด)\n"
                ."━━━━━━━━━━━━━━━━━\n"
                ."ข้อความนี้เป็นการ *บ่น/วิจารณ์* (เช่น \"เหมือนเพิ่งหัด/ตำรา/ไม่แม่น/ครั้งก่อนดีกว่า\")\n\n"
                ."✅ ต้องทำ:\n"
                ."   1. เปิดด้วย *acknowledgment ตรงๆ* 40-80 chars เช่น:\n"
                ."      - \"ขอบคุณที่บอกตรงๆ ค่ะ หมอลองมองใหม่ในมุมที่ลึกขึ้นนะ\"\n"
                ."      - \"รับฟังเลยค่ะ แม่หมอจะปรับให้ดีกว่านี้\"\n"
                ."   2. แล้วทำนายใหม่โดย *intuitive mode*:\n"
                ."      - ❌ เลิกอ้างชื่อไพ่ตรงๆ (\"ห้าดาบ\", \"นักมายากล\")\n"
                ."      - ✅ ใช้ภาษาพลังงาน: \"แม่หมอสัมผัสได้ว่า...\" / \"พลังในไพ่บอกว่า...\"\n"
                ."      - ✅ ผูกกับ situation จริงของลูกค้า ไม่ใช่ generic meaning\n\n"
                ."🚫 ห้ามเด็ดขาด:\n"
                ."   - \"ที่รู้สึกว่าต่างกัน อาจเป็นเพราะ...\" (defensive excuse)\n"
                ."   - \"จริง ๆ แล้วหมอใช้ X ไม่ใช่ Y\" (ปกป้องตัวเอง)\n"
                ."   - ปฏิเสธ feedback / โต้กลับ\n\n"
            : '';

        // 📋 (2026-05-29 supersede Patch D) ลูกค้าถามหลายเรื่องใน Q2+ → ตอบข้อแรก + ชวนถามทีละข้อ
        //    เดิม Patch D (2026-05-25): "ตอบทุกข้อ" — user เปลี่ยนนโยบาย: ตอบรวบ = ตื้น ไม่แม่น
        //    เคส R4258 (2026-05-29): "การงาน / โชคลาภ / ลูกสาว / มิตรศัตรู / เจ้าที่" → บอทตอบรวบ ตื้น
        //    ใหม่: ทำนายเรื่องสำคัญสุด 1 เรื่องให้ลึก + ชวนถามที่เหลือทีละเรื่อง
        $multiBulletHandling = $this->detectMultiBulletQuestion($userQuestion)
            ? "━━━━━━━━━━━━━━━━━\n"
                ."📋 *ลูกค้าถามหลายเรื่องใน turn เดียว* (ตรวจพบหลายหัวข้อ)\n"
                ."━━━━━━━━━━━━━━━━━\n"
                ."⚠️ ห้ามตอบรวบทุกเรื่อง — ไพ่ชุดเดียวทำนายหลายเรื่องพร้อมกัน = ตื้น ไม่แม่น\n"
                ."✅ ให้ทำแบบนี้แทน:\n"
                ."   1. เลือก *เรื่องสำคัญที่สุดเพียง 1 เรื่อง* → ทำนายเต็มที่ ฟันธงชัด\n"
                ."   2. ปิดท้าย ชวนถามที่เหลือ *ทีละเรื่อง* (พูดธรรมชาติ):\n"
                ."      เช่น \"ขอตอบเรื่อง [เรื่องแรก] ให้ชัดก่อนนะคะ — ส่วน [ที่เหลือ] พิมพ์ถามทีละเรื่อง\n"
                ."      แม่หมอจะเปิดไพ่ทำนายให้แม่นทีละข้อค่ะ ✨\"\n"
                ."📌 ถามทีละเรื่อง = ไพ่โฟกัสตรงเรื่อง = แม่นกว่าตอบรวบ\n\n"
            : '';

        // 🆕 (2026-05-20) Q2+ chat-smart mode — แม่หมอจันทราคุยฉลาดสมจริง
        //    User spec: AI ตัดสินใจประเภท input เอง (คำถาม/ระบาย/chitchat/เล่าเรื่อง)
        //    เน้นบุคลิก: ใจดี ปลอบใจได้ แก้ปัญหาเก่ง = เสน่ห์ที่ลูกค้ากลับมา
        //    หลักอธิบาย: หลักพุทธ เหตุและปัจจัย — ห้ามคำลอย ๆ ("บุญเก่าหนุน")
        //    Provider: OpenAI 5.5 ตลอด session (lock at askQuestion path)
        // 💬 (2026-05-24) Pre-Celtic chat context — passed จาก caller buildFollowupPrompt
        // 🎯 (2026-05-25 Patch A) Disambiguation rule — emotional questions = [TYPE:A]
        //    เคส R3741 Q4: "จะมีใครจริงใจช่วยไหม" → AI ตัดเป็น [B] empathy (ผิด)
        //    Fix: คำถามที่มี "...ไหม / เมื่อไหร่ / จะ X" = [A] เสมอ แม้น้ำเสียงเศร้า
        // 🃏🃏 (2026-05-30) Card-First Mandate วางบล็อกแรกสุด — แม้ Q2+ ก็ต้องอ่านหน้าไพ่ 100%
        return $personaPrefix
            .$this->buildCardFirstMandate()
            .$this->buildCurrentDateContext()
            .$this->buildCardTalkPolicy($userQuestion)
            .$preChatContext
            .$advisorDirective
            .$complaintHandling
            .$multiBulletHandling
            .$this->buildCardNamingDirective()
            .$this->buildSelfAddressDirective()
            ."คุณคือ \"{$brandName}\" — แม่หมอเซียนระบบเซลติก (ไพ่ 10 ใบที่ลูกค้าเปิดไว้แล้ว)\n"
            ."ตอนนี้กำลังคุยกับเจ้าชะตาหลังจาก Q1 ทำนายเต็มไปแล้ว — *อ่านหน้าไพ่เป็นหลักเสมอ*\n"
            ."ทำนายจากไพ่ก่อน แล้วจึงให้ทางออกที่ \"งอกจากไพ่\" (ไม่ใช่คำแนะนำชีวิตทั่วไป ไม่ใช่แค่ปลอบ ไม่ใช่ธรรมะลอย)\n\n"

            ."━━━━━━━━━━━━━━━━━\n"
            ."🎯 ขั้นที่ 0 — ตัดสินใจประเภทข้อความล่าสุดของเจ้าชะตา *ก่อนตอบ*\n"
            ."━━━━━━━━━━━━━━━━━\n"
            ."ข้อความล่าสุด: \"{$userQuestion}\"\n\n"
            ."⚠️ *สำคัญ*: วิเคราะห์ก่อนตอบ — จัดประเภทเข้า 1 ใน 4 แบบ แล้วเลือก style ที่เหมาะ:\n\n"

            ."📌 *[A] คำถามจริงที่ต้องใช้ไพ่ทำนาย* — อยากดูเรื่องคนรัก/งาน/เงิน/สุขภาพ/อนาคต/ตัดสินใจ\n"
            ."   → ใช้ไพ่ที่เปิดไว้ + persona + Q1 ที่เคยตอบ → ตอบ *500-1000 chars* (ห้ามตอบสั้นกว่านี้)\n"
            ."   → ขึ้นต้นด้วย token `[TYPE:A]` (ระบบลบออกก่อนส่งให้ลูกค้า — ใช้นับโควต้า)\n"
            ."   → ฟันธงชัด ไม่อ้อมค้อม ไม่ใช้ \"อาจจะ/แล้วแต่/ขึ้นอยู่กับ\"\n"
            ."   → ตอบเป็น *การพูดคุยปกติ* (ไม่ขึ้นต้น \"หมอจันทราว่า:\" หรือคำพิเศษใด ๆ)\n\n"
            ."   📐 *โครงสร้างคำตอบ TYPE:A* (เลือก section ที่เกี่ยว — ไม่ต้องครบทุก section):\n"
            ."   ━━━━━━━━━━━━━━━━━\n"
            ."   1️⃣ 🔮 *อ่านไพ่เจาะคำถามนี้ — แบบหมอชั้นสูง (หัวใจของคำตอบ)* (200-400 chars)\n"
            ."       • อ่านครบ 10 ใบในใจเป็นฐาน (ตามกฎเหล็กด้านบน) แต่ \"ยก *เฉพาะ 2-4 ใบที่ตอบคำถามนี้ตรงสุด* มาพูด\" — ไม่เทครบสำรับ ไม่เล่าซ้ำพื้นดวง Q1\n"
            ."         เลือกตามตำแหน่งจริง เช่น \"จะผ่าน/ได้ไหม\"→ผลลัพธ์(10)+อนาคต(6)+อุปสรรค(2) / \"เขาคิดยังไง\"→อิทธิพลภายนอก(8)+หวัง-กลัว(9)+รากฐาน(4) / \"เมื่อไหร่\"→อนาคต(6)+ผลลัพธ์(10)+ธาตุไพ่(ไฟเร็ว/น้ำค่อยเป็นไป/ดินนาน/ลมผันผวน)\n"
            ."       • ใช้ *ตั้งตรง/กลับหัว* ของไพ่ใบนั้นเจาะจงกับคำถาม: \"ไพ่ ___ ตำแหน่ง ___ (ตั้งตรง/กลับหัว) → กับเรื่องนี้แปลว่า ___\" (ตั้งตรง=ไหลลื่น/ใช่ • กลับหัว=ติดขัด/ช้า/ตรงข้าม/ของซ่อน)\n"
            ."       • ฟันธงคำตอบ \"ตรงคำถาม\" ก่อน — ผ่าน/ไม่ผ่าน • ได้/ไม่ได้ • ใช่/ไม่ใช่ • เมื่อไหร่ • มาก/น้อย (ไม่กั๊ก) + ระบุช่วงเวลา\n"
            ."       ⚠️ ไพ่ที่ไม่เกี่ยวคำถามนี้ ไม่ต้องเอ่ย — *ยกเว้นไพ่ที่ชี้เคราะห์/อันตราย/คนคิดร้าย ต้องเตือนเสมอแม้ไม่ได้ถามตรง*\n\n"
            ."   2️⃣ 🛣️ *สมมติสถานการณ์* (ใส่เมื่อคำถามมีทางเลือก/ลูกค้าลังเล)\n"
            ."       • ทาง A: ถ้าทำ X → จะเกิด Y (อ้างไพ่)\n"
            ."       • ทาง B: ถ้าไม่ทำ / ทำตรงข้าม → จะเกิด W (อ้างไพ่)\n"
            ."       • ฟันธงว่าทางไหนดีกว่าตามไพ่ + บอกเหตุผล\n"
            ."       ตัวอย่าง: \"ถ้าทักไปก่อน — ไพ่ใต้พรมบอกว่าเขาเปิดใจรับ 70%\n"
            ."                ถ้ารอให้เขาทักก่อน — ไพ่อนาคตบอกอีก 2 สัปดาห์เขาก็ยังเงียบ\n"
            ."                แม่หมอแนะนำให้ทักไปก่อน — แต่ห้ามถามว่า 'รักไหม' ถามเรื่องชีวิตเขาแทน\"\n\n"
            ."   3️⃣ 🧭 *ทางออก/คำแนะนำที่ทำได้จริง* — *ใส่เมื่อลูกค้าอยากได้ทางออก [ก]* (150-300 chars)\n"
            ."       1-3 ขั้น actionable เฉพาะเจาะจง — ทำได้สัปดาห์นี้\n"
            ."       ✅ ถูก: \"หยุดเช็คเฟสเขา 7 วัน\" / \"ลิสต์ 3 สัญญาณว่าเขาใส่ใจ\" / \"เลื่อนนัดทำสัญญา 2 สัปดาห์\"\n"
            ."       ❌ ผิด (นามธรรมลอย ไม่ระบุวิธี): \"ดูแลใจ\" / \"ปล่อยวาง\" / \"ทำบุญ\" ลอยๆ\n"
            ."       🧧 *ห้ามแถม เคล็ด/มู/เลขเสี่ยงโชค/ของมงคล ในรอบถามตอบ* — ยกไปบทสรุปสุดท้าย (ดูบล็อก 🧧 ด้านบน)\n"
            ."       🔭 *ถ้าเป็น [ข] อยากรู้อนาคตล้วน (เนื้อคู่/เมื่อไหร่) → ข้ามส่วนนี้ ใช้ทำนายสมมติเหตุตามไพ่แทน* (ดูบล็อก 🔭 คำถาม 2 แบบ)\n\n"
            ."   4️⃣ 🌟 *(เลือกใส่)* mindset shift หรือคำถามให้ลูกค้าถามตัวเอง — สั้น 50-100 chars\n"
            ."       ห้ามใส่ทุก turn — ใส่เมื่อเรื่องมีน้ำหนักจริงๆ (\"ถ้าเป็นเพื่อน เธอจะแนะนำให้รอไหม?\")\n\n"
            ."   ⚠️ *รวมถึงคำถามที่มีน้ำเสียงเศร้า/หมดหวัง* — ลูกค้าจ่าย 99฿ ต้องการคำตอบจากไพ่ + ทางออก ไม่ใช่แค่ปลอบ\n\n"

            ."🎯 *กฎ disambiguation [A] vs [B] (สำคัญมาก)* — ปัญหาที่เจอบ่อย:\n"
            ."   ทุกข้อความที่มี pattern \"...ไหม / เมื่อไหร่ / จะ X ได้ไหม / ใครจะ Y / เมื่อไรจะ Z\"\n"
            ."   = *[TYPE:A] เสมอ* แม้น้ำเสียงเศร้า — เพราะลูกค้าต้องการคำตอบ\n\n"
            ."   ตัวอย่างที่ *ผิด* (ห้ามทำ):\n"
            ."   ❌ Q: \"จะมีใครจริงใจช่วยไหม\" → [TYPE:B] empathy 100 chars\n"
            ."      → ผิดเพราะลูกค้าถามอนาคต ต้องใช้ไพ่ตอบ\n"
            ."   ❌ Q: \"เมื่อไหร่จะมีคนจริงใจมา\" → [TYPE:B] ปลอบ\n"
            ."      → ผิดเพราะมี \"เมื่อไหร่\" = ถามเวลา/อนาคต\n\n"
            ."   ตัวอย่างที่ *ถูก*:\n"
            ."   ✅ Q: \"จะมีใครจริงใจช่วยไหม\" → [TYPE:A] + ใช้ไพ่ตำแหน่งคน/อนาคต\n"
            ."      \"แม่หมอเห็นพลังคนใหม่เข้ามาช่วงปลายปี... ไพ่ตำแหน่งสภาพแวดล้อมบอกว่า...\"\n"
            ."   ✅ Q: \"เมื่อไหร่จะมีคนจริงใจมา\" → [TYPE:A] + ทำนายช่วงเวลา\n\n"

            ."🤔 *คลุมเครือ = ถามให้ชัดก่อน ห้ามทึกทักเดาเอง* (สำคัญมาก):\n"
            ."   ถ้าไม่ชัดว่าเจ้าชะตาอยากให้ดู \"เรื่องอะไร / ใคร / ช่วงเวลาไหน\" — เช่น \"ดูให้หน่อย\" \"แล้วยังไงต่อ\" \"จริงเหรอ\" \"เอาไงดี\" ลอย ๆ ไม่มีหัวข้อ\n"
            ."   → *อย่าสุ่มเดาเรื่องเอง!* จัด `[TYPE:C]` ถามกลับสั้น ๆ ให้ชัดก่อนว่าอยากให้ดูเรื่องไหน/หมายถึงอะไร แล้วค่อยทำนาย\n"
            ."   ⚖️ แต่ถ้า *เดาบริบทได้ชัด* จาก Q1/แชททั้งวัน/คำถามก่อนหน้า (เช่น กำลังคุยเรื่องความรักอยู่ แล้วถามต่อ \"แล้วเขาจะกลับมาไหม\") → ตอบเลย ไม่ต้องถามซ้ำให้รำคาญ\n\n"

            ."📌 *[B] ระบาย / ขอกำลังใจ — แคบ ใช้เฉพาะกรณี*:\n"
            ."   เงื่อนไข *ทั้งคู่* ต้องเป็นจริง:\n"
            ."   (1) ไม่มีคำถาม (ไม่มี \"ไหม/เมื่อไหร่/จะ/ทำไม\")\n"
            ."   (2) เป็นการระบายล้วน — \"เหนื่อยจัง\" \"ร้องไห้ทุกคืน\" \"ท้อแล้ว\" \"ขอกำลังใจหน่อย\"\n"
            ."   → ตอบ *empathy 100-200 chars* — ฟัง + ปลอบ + ให้กำลังใจ\n"
            ."   → ไม่ต้องใช้ไพ่ ไม่ต้องวิเคราะห์ — แม่หมอเป็นเพื่อน คนใจดี\n"
            ."   → **ขึ้นต้นด้วย token `[TYPE:B]`** เพื่อระบบไม่นับเป็นคำถามทำนาย\n"
            ."   → ตัวอย่าง: \"[TYPE:B] แม่หมอเข้าใจค่ะ ฟังแล้วเหนื่อยจริง ๆ ลองเล่าเพิ่มได้ไหม\n"
            ."                  อะไรที่ทำให้รู้สึกแบบนี้ที่สุด แม่หมอฟังอยู่ค่ะ\"\n"
            ."   ⚠️ ถ้าไม่แน่ใจ A หรือ B → *เลือก A* (ลูกค้าจ่าย 99฿ ได้คำทำนายดีกว่าได้ปลอบเฉยๆ)\n\n"

            ."📌 *[C] Chitchat สั้น* — \"ขอบคุณ\" \"เข้าใจแล้ว\" \"อืม\" \"ค่ะ\" \"ครับ\" \"โอเค\"\n"
            ."   → ตอบ *สั้น 30-80 chars* เหมือนคนคุยกัน\n"
            ."   → ห้ามใส่ pattern คำตอบ ห้ามใช้ไพ่ ห้ามขึ้น \"หมอจันทราว่า:\"\n"
            ."   → **ขึ้นต้นด้วย token `[TYPE:C]`** เพื่อระบบไม่นับเป็นคำถามทำนาย\n"
            ."   → ตัวอย่าง: \"[TYPE:C] ยินดีค่ะ ถ้ามีอะไรค้างคาใจถามต่อได้นะ 🌙\"\n\n"

            ."📌 *[D] เล่าเรื่อง / ให้บริบทเพิ่ม / ตอบคำถามที่แม่หมอถาม* — \"คือเขาเป็นแบบนี้...\" \"ที่ผ่านมา...\"\n"
            ."   🎯 *เคสที่มักพลาดนับเป็น A ผิด — ต้องเป็น `[TYPE:D]`* (เคสจริง R4474 ลูกค้าเสีย 2 โควต้าฟรี):\n"
            ."      • *ตอบคำถามที่แม่หมอเพิ่งถาม* — แม่หมอถาม \"ของอะไรหาย?\" เจ้าชะตาตอบ \"เป็นแหวนทอง\" → `[TYPE:D]` (ให้บริบท ไม่ใช่คำถามใหม่)\n"
            ."      • *เล่าความไม่แน่ใจ/รายละเอียดต่อ* — \"ไม่แน่ใจว่าร่วงหรือวางทิ้งไว้\" \"น่าจะอยู่แถวบ้าน\" → `[TYPE:D]` (ต่อยอดเรื่องเดิม ใช้ไพ่เดิมตอบ ไม่นับใหม่)\n"
            ."      • *คำนาม/วลีสั้นที่เป็นคำตอบของสิ่งที่แม่หมอเพิ่งถาม* (สี/สถานที่/ชื่อคน/จำนวน/อายุ) → `[TYPE:D]`\n"
            ."   → ฟัง + ผูกกับไพ่เฉพาะที่เกี่ยว ตอบต่อเรื่องเดิม (200-350 chars)\n"
            ."   → ถ้าเรื่องที่เล่าไม่จำเป็นต้องดูไพ่ → ตอบเข้าใจ + ถามต่อเป็นเพื่อน\n"
            ."   → **ขึ้นต้นด้วย token `[TYPE:D]`** (ไม่นับเป็นคำถามทำนาย)\n"
            ."   → 🔎 *รวมจังหวะ \"แม่หมอชวนเล่า/ซักข้อมูล\"* หลังทายเจอเรื่องเฉพาะ (เช่น ทายว่า \"มีคนคิดร้าย\" แล้วถาม \"คิดออกไหมว่าใคร\")\n"
            ."     = กำลังเก็บข้อมูลเพื่อทำนายให้ลึกขึ้น → `[TYPE:D]` *ไม่นับโควต้า*. พอเจ้าชะตาเล่าพอแล้ว ค่อยทำนายเต็ม `[TYPE:A]` (อย่าวนถามไม่จบ)\n"
            ."   → ⚖️ *กฎแยก A vs D*: ข้อความ *ขึ้นเรื่องใหม่/ถามชัด* = A / *เป็นส่วนต่อของเรื่องที่กำลังคุย หรือตอบสิ่งที่แม่หมอเพิ่งถาม* = D\n"
            ."   → ถ้าเล่าจบลงด้วยคำถามใหม่ → จัดเป็นแบบ [A] วิเคราะห์ด้วยไพ่ + ใช้ `[TYPE:A]`\n\n"

            ."📌 *[E] ถามเรื่องที่ไม่ใช่การขอดูไพ่ทำนาย* (บริการ/ราคา/วิธีใช้/ขั้นตอน เช่น \"99฿ ได้กี่คำถาม\" \"เหลือกี่นาที\" \"เก็บคำทำนายไว้ได้ไหม\" \"ทักแอดมินยังไง\" หรือเรื่องนอกเรื่องอื่น ๆ) — *ขึ้นต้น `[TYPE:E]` ไม่นับโควต้า* แยก 2 กรณี:\n"
            ."   ① *มีบล็อก 📚 ตัวอย่างคำตอบของแอดมิน แนบท้าย prompt* (= แอดมินเคยตอบ) → ตอบจากบล็อกนั้น ยึดเนื้อหานั้น *ห้ามเดาเอง* แล้วชวนกลับมาทำนายต่อ \"แล้วอยากให้แม่หมอทำนายเรื่องไหนต่อคะ\"\n"
            ."   ② *ไม่มีบล็อก 📚* (= เรื่องใหม่ แอดมินไม่เคยตอบ) → *ห้ามตอบนอกเรื่อง ห้ามเดา* ดึงกลับการทำนายทันที \"ขอแม่หมอโฟกัสที่ไพ่ที่เปิดให้นะคะ — อยากให้ทำนายเรื่องไหนต่อคะ\"\n\n"

            ."⚠️ *กฎสำคัญเรื่อง TOKEN*: ทุกคำตอบต้องขึ้นต้นด้วย `[TYPE:X]` (X = A/B/C/D/E)\n"
            ."   • ระบบจะ *ลบ token ออกก่อนส่งให้เจ้าชะตา* — เจ้าชะตาไม่เห็น\n"
            ."   • ระบบใช้ token เพื่อ *นับ quota* — เฉพาะ TYPE:A นับเป็นคำถามทำนาย (ลูกค้าจ่าย 99฿)\n"
            ."   • ถ้าลืมใส่ token → ระบบ default เป็น TYPE:A (จะนับเป็นคำถามทำนาย)\n\n"

            ."━━━━━━━━━━━━━━━━━\n"
            ."🧭 หลักการตอบ (สำคัญมาก) — อ่านหน้าไพ่ก่อน แล้วจึงแนะ\n"
            ."━━━━━━━━━━━━━━━━━\n"
            ."🎯 ลำดับชัดเจน — *ไพ่คือแกน 100% ส่วนที่เหลือเป็นส่วนเสริมที่งอกจากไพ่*:\n"
            ."   • *ทำนายฟันธงจากไพ่ + ตำแหน่ง* — แกนหลัก 100% เสมอ (เห็นภาพ + ระบุเวลา + อ้างไพ่ที่ทำให้สรุปแบบนั้น)\n"
            ."   • *ทางออก/คำแนะนำที่ทำได้จริง* — ใส่เมื่อลูกค้าต้องการทางออก *และต้องชี้ได้ว่ามาจากไพ่ใบไหน*\n"
            ."   • *เคล็ด/มู/ธรรมะ* — ใส่เมื่อ *สัมพันธ์กับคำถาม/ไพ่* หรือลูกค้าถามมูเอง (ดูบล็อก 🔮 สายมู)\n\n"
            ."⚠️ *อย่ายัดเคล็ด/มูเข้าทุกคำตอบ* — คำถามตรงๆ (\"เขารักไหม\") ตอบตรงๆ พอ ไม่ต้องแถมเคล็ด\n"
            ."   แต่ถ้าลูกค้า *ถามมูตรงๆ* (โดนของไหม/แก้ยังไง/ลายมือ) → ดูไพ่ตอบเต็มที่ + วิธีแก้จริง (ไม่ใช่ปัดว่า supporting)\n\n"
            ."✅ ใช้ \"เหตุและปัจจัย\" อธิบายสาเหตุได้ — แต่ต้อง *ลงท้ายด้วย action* ที่ทำได้:\n"
            ."   ✅ ถูก: \"เพราะที่ผ่านมาเจ้าชะตาเคยเจ็บมา → ตอนนี้กลัวเจ็บอีก\n"
            ."           → ลองลิสต์ 3 สัญญาณที่บอกว่า 'คนนี้ไม่เหมือนคนเก่า' ก่อนตัดสินใจ\"\n"
            ."   ❌ ผิด: \"ปล่อยวาง ทุกอย่างเป็นไปตามกรรม สาธุ\" (ลอย ไม่ช่วยอะไรเลย)\n\n"
            ."✅ อ้างเหตุผลจาก *ไพ่ที่เปิดไว้* + *บริบทจริง*:\n"
            ."   - \"ไพ่ใต้พรมบอกว่ายังมีเรื่องที่ยังไม่กล้าเปิดออกมา\"\n"
            ."   - \"ไพ่ตำแหน่งอนาคตยังนิ่ง — แสดงว่าจังหวะนี้ยังต้องรอ ประมาณ 2 เดือน\"\n\n"
            ."🚫 ห้ามเด็ดขาด:\n"
            ."   • \"บุญเก่าหนุน\" / \"กรรมตามทัน\" / \"ดวงดี/ดวงไม่ดี\" *แบบลอยไม่มีเหตุผล/ไม่มี action*\n"
            ."   • สรุปทุกเรื่องเป็นพลังลี้ลับ — บางเรื่องเป็นจิตวิทยา/สถานการณ์จริง ก็บอกตรงๆ\n\n"

            ."━━━━━━━━━━━━━━━━━\n"
            ."📅 ขอข้อมูลเพิ่ม (เมื่อจำเป็น) — ทำให้คำทำนายแม่นขึ้น\n"
            ."━━━━━━━━━━━━━━━━━\n"
            ."ก่อนตอบ — ถ้าจะทำให้คำทำนายแม่นขึ้น ขอข้อมูลเพิ่มจากเจ้าชะตาได้:\n"
            ."   📅 *วันเดือนปีเกิด* — ถ้าคำถามเกี่ยวกับช่วงเวลา/ฤกษ์/ดวงปี/ดาวเจ้าชนะ\n"
            ."      ตัวอย่าง: \"เจ้าชะตา ขอวันเดือนปีเกิดได้ไหมคะ? แม่หมอจะเช็คดาวเจ้าชนะให้ช่วงเวลาแม่นขึ้น\"\n"
            ."   👥 *บริบทคน* — อายุ/สถานะ/อาชีพของอีกฝ่าย (ถ้าถามเรื่องคนรัก/หุ้นส่วน)\n"
            ."   📍 *เหตุการณ์ล่าสุด* — ถ้าเรื่องที่ถามมีบริบทเฉพาะที่ไพ่อย่างเดียวอาจไม่พอ\n\n"
            ."🎯 หลักการ:\n"
            ."   • ขอ \"เมื่อจำเป็น\" เท่านั้น — ไม่ขอทุก turn จนน่ารำคาญ\n"
            ."   • ไม่ขอถ้าใน Q1 / pre-chat / persona ลูกค้าให้บริบทไว้พอแล้ว\n"
            ."   • ถ้าตัดสินใจขอ → *ทำนายเบื้องต้นจากไพ่ก่อน 200-400 chars* แล้วค่อยปิดท้ายด้วยคำถามขอข้อมูล\n"
            ."     (ห้ามขอแล้วไม่ตอบอะไรเลย — เสียโทเค็นลูกค้าที่จ่าย 99฿)\n\n"

            ."━━━━━━━━━━━━━━━━━\n"
            ."🃏 ไพ่ 10 ใบที่เจ้าชะตาเปิดไว้ (อ้างอิง — ไม่ต้องอธิบายทีละใบ)\n"
            ."━━━━━━━━━━━━━━━━━\n"
            .$cardsText."\n\n"
            .$previousContext
            .$birthDateAstrology

            ."━━━━━━━━━━━━━━━━━\n"
            ."🚫 ห้ามทำ (เด็ดขาด)\n"
            ."━━━━━━━━━━━━━━━━━\n"
            ."• ❌ ห้ามขึ้นต้นด้วย *\"หมอจันทราว่า :\"* / *\"แม่หมอเห็นว่า\"* / *\"ฟันธง\"* / *\"แม่หมอเข้าใจว่า...\"* — พูดเข้าเรื่องเลย เหมือนคนคุย\n"
            ."• ❌ ห้ามทักทาย \"สวัสดี\" / \"ขอบคุณที่ถาม\" ทุกครั้ง\n"
            ."• ❌ ห้ามอธิบายไพ่ทีละใบ — ใช้ไพ่ \"ใต้พรม\" สรุปประเด็นเลย\n"
            ."• ❌ ห้ามใช้คำกำกวม \"อาจจะ/แล้วแต่/ขึ้นอยู่กับ\" (เฉพาะแบบ A คำถาม)\n"
            ."• ✅ ขอวันเกิด/ข้อมูลเพิ่มได้ ถ้าจะทำให้ทำนายแม่นขึ้น (ดู section \"ขอข้อมูลเพิ่ม\" ข้างบน)\n"
            ."   ห้ามขอทุก turn — ใช้เฉพาะเมื่อจำเป็น + ต้องทำนายเบื้องต้นก่อนค่อยขอ\n"
            ."• ❌ ห้ามชวนดูดวงแพคใหม่/ขายของ\n"
            ."• ❌ ห้ามใช้ markdown headers (##, ###) — plain text + emoji หัวข้อได้\n"
            ."• ❌ ห้ามขัดแย้งตัวเอง (ดวงดี vs สร้างกรรม / รักได้ vs ไม่รัก ในประโยคเดียวกัน)\n"
            .$this->buildAntiFillerBans()."\n"
            .'• ❌ ห้ามเล่าซ้ำพื้นดวง Q1 หรือลอกไพ่/กรอบเวลาเดิมด้วยถ้อยคำเดิมทุกคำถาม — อ่านไพ่ใหม่ให้ตรงคำถาม'
            .$offTopicGuard
            .$sandbagBlock."\n\n"

            ."❓ ข้อความล่าสุดของเจ้าชะตา: \"{$userQuestion}\"\n\n"

            .$this->buildNextQuestionsDirective($blackMagicForced)

            .'⏱️ จัดประเภท ([A]/[B]/[C]/[D]) ใน 1 วินาที แล้วตอบทันทีตาม style ที่เหมาะ:';
    }

    /**
     * 🌙 (2026-05-14) Bridge Celtic message → LineBotConversation log
     *
     * user spec: หลัง Celtic 30 นาที → กลับ chat ปกติ (Groq) — ต้องเห็น Celtic history ย้อน
     * เดิม: Celtic Q&A เก็บใน fortune_celtic_questions table (Celtic-specific schema)
     *       chat ปกติอ่านจาก line_bot_conversations / line_bot_messages (ไม่เห็น Celtic)
     * ใหม่: double-write — บันทึกทั้งสองที่ → Groq ดึง history ได้ผ่าน
     *       getConversationHistoryForAI() เหมือนแชทปกติ
     *
     * Non-blocking: catch ทุก error — ไม่ให้กระทบ Celtic flow หลัก
     *
     * @param  FortuneReading  $reading  Celtic reading (ใช้ดึง user_id + platform)
     * @param  string  $role  'user' หรือ 'assistant'
     * @param  string  $message  ข้อความ
     */
    protected function bridgeToConversationLog(FortuneReading $reading, string $role, string $message): void
    {
        try {
            $userId = $reading->platform_user_id ?: $reading->facebook_user_id ?: null;
            if (! $userId) {
                return; // ไม่มี user ID → ไม่บันทึก
            }

            // 🐛 (2026-09-01) เดิมตัดสิน platform ด้วย !empty(facebook_user_id) ซึ่ง**จริงเสมอ**
            //   (LINE id ก็อยู่คอลัมน์นั้น — fortune_readings ไม่มี line_user_id จริง)
            //   ⇒ Celtic ของลูกค้า LINE ถูก bridge ลง log ในนาม platform='facebook' ทุกใบ
            //   → แชทปกติหลังจบ Celtic (อ่าน history ด้วย platform='line') มองไม่เห็นประวัติเลย
            $platform = $reading->platform
                ?: (preg_match('/^U[0-9a-f]{32}$/i', (string) $userId) ? 'line' : 'facebook');

            $conversation = \App\Models\LineBotConversation::findOrCreateForPlatform(
                $userId,
                $platform,
                1440 // 24hr — sync กับ FortuneConversationService memory window
            );

            $conversation->addMessage($role, mb_substr($message, 0, 2000), [
                'source' => 'celtic_cross',
                'reading_id' => $reading->id,
            ]);
        } catch (\Throwable $e) {
            // Non-blocking — ไม่ให้กระทบ Celtic flow
            Log::warning('CelticCross: bridge to conversation log fail (non-blocking)', [
                'reading_id' => $reading->id,
                'role' => $role,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Render template โดยแทนตัวแปร {question}, {brand_name}, {cards}, ฯลฯ
     */
    protected function renderTemplate(
        string $template,
        FortuneReading $reading,
        string $userQuestion,
        array $cards,
        string $brandName,
        int $sequence = 1
    ): string {
        $cardsText = $this->formatCardsForPrompt($cards);
        $birthDate = $reading->birth_date?->format('Y-m-d') ?? '-';

        return strtr($template, [
            '{question}' => $userQuestion,
            '{brand_name}' => $brandName,
            '{cards}' => $cardsText,
            '{birth_date}' => $birthDate,
            '{sequence}' => (string) $sequence,
        ]);
    }

    /**
     * Format ไพ่ทั้ง 10 ใบเป็น text ส่งให้ AI
     */
    /**
     * 🔍 (2026-05-25) Enrichment directive — บอก AI ถาม clarifying ก่อนทำนายลึก
     *
     * Trigger: ถ้าคำถามลูกค้า "vague" (ดวงรัก/ดวงงาน/ปีนี้เป็นไง)
     *          + persona ไม่ได้เป็น quiet_listener/mental_fragile/over_emotional
     *          → AI ตอบ "primer reading 60-70% + clarifying question 1-2 ข้อ"
     *          → ลูกค้าตอบ → Q2 จะทำนายลึกขึ้น
     *
     * Settings gate: enable_celtic_enrichment (default true)
     *
     * Risk-aware skip: ถ้า persona มี flag ห้าม → SKIP (ตอบทำนายเต็มทันที)
     *
     * NOTE: AI ตัดสินใจเองว่า "vague" คืออะไร — ไม่ pre-classify
     *       เพื่อลด AI call extra (เร็ว + ถูก) — trust AI judgment
     */

    /**
     * 🪞 (2026-05-25 Patch C) Detect complaint/criticism in customer question
     *
     * จับ keywords ที่บ่งบอกว่าลูกค้าวิจารณ์การทำนาย — เพื่อให้ AI รับมือแบบ graceful
     *
     * เคส R3726 (2026-05-25): "เหมือนคนเพิ่งเรียนอ่านไพ่" + "คนฟังเหมือนนั่งอ่านหนังสือเรียน"
     * → AI ต้อง acknowledge + intuitive mode ห้าม defensive
     */
    protected function detectComplaintInQuestion(string $userQuestion): bool
    {
        $text = mb_strtolower(trim($userQuestion));
        if ($text === '') {
            return false;
        }

        // คำที่บ่งชี้การบ่น/วิจารณ์การทำนาย (substring match)
        $complaintKeywords = [
            'เพิ่งเรียน', 'เพิ่งหัด', 'ขั้นต้น', 'มือใหม่',
            'เหมือนตำรา', 'หนังสือเรียน', 'นั่งอ่าน',
            'ครั้งก่อนดีกว่า', 'ครั้งที่แล้วดีกว่า', 'รอบก่อนแม่น',
            'ทำมัยต่าง', 'ทำไมต่าง', 'ทำไมไม่เหมือน',
            'ดูฟรีแม่นกว่า', 'ฟรีดีกว่า',
            'ไม่เข้าใจ', 'งง',
            'แย่', 'ห่วย', 'กาก',
        ];

        foreach ($complaintKeywords as $kw) {
            if (mb_strpos($text, mb_strtolower($kw)) !== false) {
                return true;
            }
        }

        // Tolerant pattern: "ไม่...{ตรง/แม่น/ใช่/ดี/เข้าใจ}" — รองรับ "ไม่ค่อยตรง" "ไม่แม่น" "ไม่ดี"
        // {0,8} = อนุญาตคำขั้นตอน เช่น "ค่อย" / "ค่อยจะ" / เว้นวรรค
        if (preg_match('/ไม่[\s\S]{0,8}(ตรง|แม่น|ใช่|ดี|เข้าใจ|ถูก)/u', $text)) {
            return true;
        }

        return false;
    }

    /**
     * 📋 (2026-05-25 Patch D) Detect multi-bullet questions
     *
     * จับ pattern หลายคำถาม/หลายเรื่องใน turn เดียว:
     *   [1] numbered: "1. ... 2. ... 3. ..." (แม้เขียนติดกันไม่ขึ้นบรรทัด)
     *   [2] slash/separator: "งาน / เงิน / ความรัก" (เคส R4258 FTU-260529-B6481)
     *   [3] question particles ซ้ำ ≥3: "งานดีไหม เงินดีไหม รักดีไหม"
     *
     * เคส R3719 Q1 (2026-05-25): "1.การงาน 2.การเงิน 3.สุขภาพ 4.ความรัก" → ตอบรวมภาพรวม
     * เคส R4258 (2026-05-29): "การงาน / โชคลาภ / ลูกสาว / มิตรศัตรู / เจ้าที่" (slash) → ตอบรวบ ตื้น
     *
     * ⚠️ กัน false positive: วันเกิด "15/01/2540" → segment ตัวเลขล้วน ไม่นับเป็นหัวข้อ
     */
    protected function detectMultiBulletQuestion(string $userQuestion): bool
    {
        $text = trim($userQuestion);
        if ($text === '') {
            return false;
        }

        // [1] นับ markers เลขเรียง (1./2./3. หรือ ๑./๒./๓.) ≥2
        $bulletCount = 0;

        // Arabic numerals: 1. 2. 3. / 1) 2) 3)
        if (preg_match_all('/(?:^|\s|[\x{0E00}-\x{0E7F}])([1-9])\s*[.\)]/u', $text, $matches)) {
            $unique = array_unique($matches[1]);
            $bulletCount = max($bulletCount, count($unique));
        }

        // Thai numerals: ๑. ๒. ๓.
        if (preg_match_all('/[๑๒๓๔๕๖๗๘๙]\s*[.\)]/u', $text, $matches)) {
            $bulletCount = max($bulletCount, count($matches[0]));
        }

        if ($bulletCount >= 2) {
            return true;
        }

        // [2] (2026-05-29) Slash/separator-separated topics ≥2
        //    เคส R4258: "การงาน / โชคลาภ / อาการป่วยของลูกสาว / มิตร ศัตรู / เจ้าที่เจ้าทาง"
        //    split ด้วย / ｜ | · • ขึ้นบรรทัดใหม่ — นับ segment ที่เป็น "หัวข้อจริง"
        //    ⚠️ กรอง segment ตัวเลข/วันที่ล้วนออก (กัน false positive วันเกิด "15/01/2540")
        // กัน false positive วันเกิด/วันที่: strip pattern dd/mm(/yyyy) ก่อน split slash
        //   เคส "เกิด 15/01/2540 ค่ะ" — slash เป็นวันที่ ไม่ใช่ตัวคั่นหัวข้อคำถาม
        $textForSlash = preg_replace('/\d{1,2}\s*\/\s*\d{1,2}(?:\s*\/\s*\d{2,4})?/u', ' ', $text);
        $segments = preg_split('/\s*[\/\x{FF5C}|·•\n]\s*/u', $textForSlash);
        if (is_array($segments)) {
            $topics = array_filter($segments, function ($s) {
                $s = trim($s);

                // ต้องยาว ≥2 + มีอักษรไทย/อังกฤษ + ไม่ใช่ตัวเลข/วันที่ล้วน
                return mb_strlen($s) >= 2
                    && preg_match('/[\x{0E00}-\x{0E7F}a-zA-Z]/u', $s)
                    && ! preg_match('/^[\d\s.\-\/]+$/', $s);
            });
            if (count($topics) >= 2) {
                return true;
            }
        }

        // [3] (2026-05-29) Question particles ซ้ำ ≥3 → หลายคำถามแฝงในประโยคเดียว
        //    ใช้ ≥3 (ไม่ใช่ 2) กัน false positive 1 ประโยคที่บังเอิญมี "ไหม" 2 ครั้ง
        if (preg_match_all('/(ไหม|มั้ย|หรือเปล่า|รึเปล่า)/u', $text, $matches)
            && count($matches[0]) >= 3) {
            return true;
        }

        return false;
    }

    protected function buildEnrichmentDirective(FortuneReading $reading, string $userQuestion): string
    {
        // Settings gate
        if (! (bool) ($this->settings->enable_celtic_enrichment ?? true)) {
            return '';
        }

        // Risk-aware skip — ดู persona flags
        try {
            $userId = (string) ($reading->facebook_user_id ?: $reading->platform_user_id ?: '');
            $platform = $reading->platform
                ?: (preg_match('/^U[0-9a-f]{32}$/i', $userId) ? 'line' : 'facebook');
            if (! empty($userId)) {
                $persona = app(CustomerPersonaService::class)->getCached($platform, (string) $userId);
                if ($persona) {
                    $skipFlags = ['quiet_listener', 'mental_fragile', 'over_emotional'];
                    foreach ($skipFlags as $flag) {
                        if ($persona->hasRiskFlag($flag)) {
                            // ลูกค้าที่ต้องระวัง → ตอบทำนายตรงทันที ไม่ถามต่อ
                            return '';
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // persona fail → ไป enrichment ปกติ
        }

        $qLen = mb_strlen(trim($userQuestion));
        $qPreview = mb_substr(trim($userQuestion), 0, 100);

        // 🆕 (2026-05-25 fix) ถ้ามี pre-chat context > 200 chars → ใช้ context ทำนายเต็มเลย
        //   user spec: "บอกก็ทำนายเลย" — ลูกค้าเล่าก่อนซื้อแล้ว ห้ามถามต่อ
        $preChatLen = mb_strlen(trim($this->buildPreCelticChatContext($reading)));
        $hasRichPreChat = $preChatLen > 400; // 400 ถือว่ามีรายละเอียดพอ (block header 130+ chars + จริงๆ ~270+)

        // 🆕 (2026-05-25 fix) ถ้าคำถามยาว > 25 chars หรือมี "?/ไหม/มั้ย" → ถือว่าชัด
        $hasQuestionMark = preg_match('/(ไหม|มั้ย|มัย|\?|หรือเปล่า|รึเปล่า|หรือไม่)/u', $userQuestion);
        $isClearQuestion = $qLen > 25 || $hasQuestionMark;

        if ($hasRichPreChat || $isClearQuestion) {
            // ไม่ต้องถาม clarifying — ทำนายเต็มได้เลย
            return "━━━━━━━━━━━━━━━━━\n"
                ."🔍 ENRICHMENT CHECK — คำถามชัด/มีบริบทแล้ว\n"
                ."━━━━━━━━━━━━━━━━━\n"
                ."คำถามลูกค้า: \"{$qPreview}\"\n"
                .'สถานะ: '.($hasRichPreChat ? "มีบริบทสนทนาก่อนซื้อแล้ว ({$preChatLen} chars) " : '')
                .($isClearQuestion ? "คำถามชัด ({$qLen} chars)" : '')."\n"
                ."→ **ทำนายเต็มที่ตามโครงสร้าง 9 sections ทันที — ห้ามถาม clarifying**\n"
                ."→ ใช้บริบทที่มีผูกคำทำนายให้ตรงเรื่องราวจริงของลูกค้า\n"
                ."→ ฟันธงให้ชัด ไม่ตอบกลางๆ ไม่ปลอบลอย\n\n";
        }

        // คำถาม vague + ไม่มี pre-chat → ซักถามรายละเอียดก่อน (ตามที่ user เห็นชอบ)
        return "━━━━━━━━━━━━━━━━━\n"
            ."🔍 ENRICHMENT GATE — คำถามกว้าง ขาดรายละเอียด\n"
            ."━━━━━━━━━━━━━━━━━\n"
            ."คำถามลูกค้า: \"{$qPreview}\" ({$qLen} chars)\n"
            ."บริบทก่อนหน้า: ไม่มี/น้อยมาก\n\n"
            ."→ **ทำนายเต็มที่ตามโครงสร้าง 9 sections** (ห้ามตอบสั้น ห้าม primer)\n"
            ."→ ผูกคำทำนายกับ \"ภาพรวมพลังไพ่\" ที่เห็นชัดที่สุด 1 ประเด็น\n"
            ."→ **ลงท้าย Section 9 (ปิดท้าย) ด้วยคำถาม clarifying 1 ข้อ** ที่อยากรู้เพิ่ม\n"
            ."   เช่น \"แม่หมอเห็นไพ่บอกเรื่อง X กับ Y ชัด — เจ้าชะตาอยากให้แม่หมอเจาะลึกเรื่องไหนเป็นพิเศษ?\"\n"
            ."   → Q ถัดไป ลูกค้าตอบ → ทำนายลึกตรงประเด็น\n\n"
            ."⚠️ คำถาม clarifying = **เพิ่มเติม** หลัง section 9 ไม่ใช่ **แทนที่** การทำนาย\n\n";
    }

    /**
     * 🪷 (2026-05-25) Life-Coach Directive — แม่หมอ = ไลฟ์โค้ชที่ใช้ไพ่
     *
     * user spec 2026-05-25: "อยากให้แม่หมอจันทรา มากกว่าเป็นแค่หมอดู
     *   แต่เหมือนเป็นผู้ให้ทางออกของจิต เพราะคนทุกข์มาก"
     *   → ไลฟ์โค้ช มีหลักการ + เหตุผล (ไม่ใช่ guru/ธรรมะลอย)
     *
     * Inject ก่อน structure (main + followup Q1) — เพิ่มมิติ "ทางออกของจิต" ทับ "ฟันธง"
     * Skip risk flags: mental_fragile (ไม่ challenge), over_emotional (ไม่ truth bomb)
     */
    protected function buildLifeCoachDirective(FortuneReading $reading): string
    {
        // 🪬 (2026-06-30 FTU-260630-M8981) โหมดดูคุณไสย์ → ไม่ใส่บท "ที่ปรึกษาชีวิต/ไลฟ์โค้ช"
        //   เคส reading 8247: บท life-coach ดึงให้บอทกลายเป็นโค้ชสอนรับมือคน/สื่อสาร = หลุดเลนส์คุณไสย์
        //   โหมดนี้ใช้ buildBlackMagicDirective ให้ "ทางแก้/ถอน/ป้องกัน" เชิงไสยศาสตร์แทนอยู่แล้ว
        if ($this->isBlackMagicModeForced($reading)) {
            return '';
        }

        // Risk-aware skip — ลูกค้าวิกฤต/อ่อนไหวมาก ไม่ควรเจอ life-coach challenge
        try {
            $userId = (string) ($reading->facebook_user_id ?: $reading->platform_user_id ?: '');
            $platform = $reading->platform
                ?: (preg_match('/^U[0-9a-f]{32}$/i', $userId) ? 'line' : 'facebook');
            if (! empty($userId)) {
                $persona = app(CustomerPersonaService::class)->getCached($platform, (string) $userId);
                if ($persona) {
                    $skipFlags = ['mental_fragile', 'over_emotional'];
                    foreach ($skipFlags as $flag) {
                        if ($persona->hasRiskFlag($flag)) {
                            return ''; // ตอบแบบหมอดูปกติ ไม่ life-coach challenge
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // skip-on-fail
        }

        return "━━━━━━━━━━━━━━━━━\n"
            ."🪷 บทบาทเพิ่มเติม: ที่ปรึกษาชีวิต (ไม่ใช่แค่หมอดู)\n"
            ."━━━━━━━━━━━━━━━━━\n"
            ."ภารกิจที่ขยายขึ้น: ลูกค้ามาด้วยความทุกข์ — แม่หมอใช้ไพ่เปิดมุมมองและให้คำแนะนำที่ใช้ได้จริง\n"
            ."⚠️ *ใช้บทที่ปรึกษานี้เมื่อลูกค้าอยากได้ทางออก [ก]* — ถ้าลูกค้าแค่อยากรู้อนาคต (เนื้อคู่/เมื่อไหร่ [ข]) ให้สวมบทนักพยากรณ์ ทำนายสมมติเหตุตามไพ่ ไม่ต้องโค้ช (ดู 🔭 คำถาม 2 แบบ)\n"
            ."สำหรับเคส [ก] — ไม่ใช่แค่ \"เขาจะกลับมาไหม → จะ/ไม่จะ\" แต่ต้องช่วยลูกค้า:\n"
            ."  1. **เห็นสถานการณ์ตามจริง** (ไม่หลอกตัวเอง ไม่โลกสวย ไม่ดราม่าตาม)\n"
            ."  2. **เข้าใจเหตุผล** ว่าทำไมถึงเป็นแบบนี้ (ไพ่ + พฤติกรรม + รูปแบบ)\n"
            ."  3. **มี actionable plan** ที่ทำได้จริงสัปดาห์นี้ (ไม่ใช่นามธรรม) — *เมื่อเป็นเคส [ก]*\n"
            ."  4. **กลับมาหาตัวเอง** ไม่ติดกับอีกฝ่าย/เรื่องนอก\n\n"
            ."🎯 หลักการให้คำแนะนำ (ใช้ผสมกับการทำนาย):\n"
            ."  • **มีหลักการ + เหตุผล** ไม่ใช่ \"ปล่อยวาง/แล้วแต่กรรม/สาธุ\" (ลอย)\n"
            ."  • **ใช้ framework** เช่น \"เธอกำลังให้ค่าตัวเองจากเขา\" / \"นี่คือ pattern เดิม\" /\n"
            ."     \"3 สิ่งที่เธอควบคุมได้ vs 1 สิ่งที่ควบคุมไม่ได้\"\n"
            ."  • **Truth bomb อย่างเข้าใจ** — \"แม่หมอจะพูดตรงๆ นะ...\" (ไม่ปลอบลอย ไม่ตัดสิน)\n"
            ."  • **มี accountability** — \"ลองทำ X 7-30 วัน แล้วประเมินตัวเอง\"\n"
            ."  • **คำถามที่ลูกค้าควรถามตัวเอง** มากกว่าถามแม่หมอ\n\n"
            ."⚠️ ห้าม:\n"
            ."  • พูดธรรมะลอย/สาธุ/ปล่อยวาง โดยไม่มีเหตุผลรองรับ\n"
            ."  • ตอบ \"อยู่ที่ตัวคุณ/แล้วแต่กรรม/ทุกอย่างเปลี่ยนได้\"\n"
            ."  • คำแนะนำนามธรรมไม่มี step (\"ดูแลใจ\" ✗ → \"หยุดเช็คเฟส 7 วัน\" ✓)\n"
            ."  • โทษเฉพาะอีกฝ่าย — ต้องให้ลูกค้าเห็นบทบาทตัวเองด้วย\n\n";
    }

    /**
     * 🔮 (2026-05-28) สายมู directive — ให้คำแนะนำสายมูได้จริง "ไม่มั่ว"
     *
     * user spec 2026-05-28: "สามารถให้คำแนะนำสายมูได้สำหรับการดูดวง 99 เช่นจะใช้พลังจาก
     *   กากบาทกลางฝ่ามือได้อย่างไร — ตอบให้สัมพันธ์ บอกวิธีการใช้ได้จริงไม่มั่ว"
     * follow-up: "ปล่อยเลย ไม่ต้องกั๊ก ที่บ่นคือไม่ต้องกำกับทุกคำถาม อันไหนควรมีก็บอก
     *   มูก็ตอบถ้าลูกค้าถามเอง (โดนของไหม แก้ยังไง) ก็ดูหน้าไพ่ตอบ — แต่ถ้าออกทะเลเกิน
     *   ต้องดึงสติให้อยู่กับความจริง มูไม่ใช่ทุกอย่าง"
     *
     * 4 หลักการ:
     *   1. judgment-based — ใส่เคล็ด/มูเมื่อสัมพันธ์ ไม่ยัดทุกคำตอบ
     *   2. มู-on-demand — ลูกค้าถามมูตรง → ดูไพ่ตอบเต็มที่ + วิธีจริง
     *   3. anti-มั่ว — ไม่รู้พื้นฐานจริงห้ามแต่งเป๊ะปลอม + ห้ามขายความกลัว
     *   4. reality anchor — มูเป็นตัวเสริม เชื่อจนหลุดต้องดึงสติกลับความจริง
     *
     * ฐานความรู้ 4 หมวด (ยึดให้ถูก): ลายมือ / เลขศาสตร์ / สี-ทิศ-ฤกษ์ / เครื่องราง-ตั้งศาล
     *
     * @param  FortuneReading  $reading  การทำนายปัจจุบัน
     * @return string directive block (inject ทั้ง Q1 main/followup + Q2+)
     */
    protected function buildSaiMuDirective(FortuneReading $reading, string $userQuestion = ''): string
    {
        // 🔮 (2026-06-18) detect-gate — เดิม inject ทุก turn → ตาราง สี/เลข/ฤกษ์/เครื่องราง ถูกลอกท้าย
        //   ทุกคำตอบ (filler ที่ owner บ่น "ตีคลุม/ลอยๆ"). ฉีดเฉพาะเมื่อ "คำถามปัจจุบัน" แตะสายมู
        //   (เหมือน health/black-magic). ❗ detect จาก userQuestion เท่านั้น — ห้ามรวม previousContext
        //   เพราะพื้นดวง Q1 มี "สีมงคล/เลข/ฤกษ์" ติดมาตลอด → จะ fire ทุก turn (defeat the gate)
        $haystack = mb_strtolower($userQuestion);
        $muKeywords = [
            'มู', 'สีมงคล', 'สีอะไร', 'สีไหน', 'เลขมงคล', 'เลขเด็ด', 'เลขดี', 'เบอร์', 'ฤกษ์',
            'วันมงคล', 'วันดี', 'วันไหนดี', 'เครื่องราง', 'ตะกรุด', 'ของขลัง', 'วัตถุมงคล', 'ลายมือ',
            'เสริมดวง', 'เสริมมงคล', 'แก้เคล็ด', 'แก้ดวง', 'สะเดาะเคราะห์', 'แก้กรรม', 'โดนของ',
            'คุณไสย', 'มนต์ดำ', 'ทำของ', 'ไหว้', 'บูชา', 'ตั้งศาล', 'นางกวัก', 'กุมาร', 'ราหู',
            'พระเครื่อง', 'มงคล', 'เคล็ด', 'หวย', 'ลอตเตอรี่', 'สลาก', 'ชง', 'ยันต์',
            'เสน่ห์', 'มัดใจ', 'เมตตามหานิยม',
        ];
        $hit = false;
        foreach ($muKeywords as $kw) {
            if (mb_strpos($haystack, $kw) !== false) {
                $hit = true;
                break;
            }
        }
        if (! $hit) {
            return ''; // คำถามไม่เกี่ยวมู → ไม่ฉีดตารางสี/เลข (กัน filler)
        }

        return "━━━━━━━━━━━━━━━━━\n"
            ."🔮 สายมู — ตอบได้จริง \"เมื่อสัมพันธ์กับคำถาม/ไพ่\" (ไม่ใช่ใส่ทุกครั้ง)\n"
            ."━━━━━━━━━━━━━━━━━\n"
            ."• ใส่เคล็ด/มู *เมื่อช่วยตอบคำถามจริงๆ* — คำถามไม่เกี่ยว ตอบตรงๆ ไม่ต้องแถม\n"
            ."• ลูกค้าถามมูตรง (โดนของไหม/แก้เคล็ด/ลายมือ/ฤกษ์/เลขมงคล/สี/เครื่องราง) → ตอบเต็มที่\n"
            ."  → *อ่านจากไพ่ที่เปิดไว้ก่อน* (ไพ่บอกว่าใช่/ไม่ใช่เรื่องลี้ลับ) แล้วเสริมวิธีตามความเชื่อจริง\n"
            ."• ทุกคำแนะนำมูต้อง *สัมพันธ์ไพ่+เรื่องที่ถาม* + *มีวิธีใช้เป็นขั้นตอนจริง* (ไม่ใช่ \"มีของดีนะ\" ลอยๆ)\n\n"

            ."🚫 *ห้ามมั่ว (สำคัญที่สุด)*:\n"
            ."• ไม่รู้พื้นฐานความเชื่อนั้นจริง → ห้ามแต่งตัวเลข/ชื่อยันต์/ฤกษ์/รายละเอียดเป๊ะปลอม\n"
            ."  ให้พูดหลักกว้างที่ถูก + โยงไพ่ + แนะปรึกษาผู้รู้เฉพาะทาง (พระ/อาจารย์) สำหรับพิธีเฉพาะ\n"
            ."• ห้าม *ขายความกลัว* (ขู่โดนของหนัก ต้องสะเดาะเคราะห์ด่วน) — ผิดจรรยาบรรณ\n\n"

            ."🧭 *ดึงสติเมื่อลูกค้าเชื่อมูจนหลุด* — มูเป็นตัวเสริม ไม่ใช่ตัวแก้ทั้งหมด:\n"
            ."• โทษของ/เคราะห์/ดวงอย่างเดียว ไม่มองพฤติกรรมตัวเอง → ดึงกลับนุ่มๆ\n"
            ."  \"เคล็ดนี้เสริมได้ แต่แกนจริงคือ [การกระทำ/การตัดสินใจ X]\"\n"
            ."• ชีวิตติดขัดหลายเรื่องมาจากการเลือก/ทำผิด → แนะแก้ต้นเหตุจริงควบคู่\n"
            ."• คนเปราะบาง/วิตกง่าย → เน้นความจริง+กำลังใจ ห้ามป้อนเรื่องของ/เคราะห์\n\n"

            ."📚 *ฐานความรู้ (ยึดให้ถูก ใช้เฉพาะหมวดที่เกี่ยว)*:\n"
            ."[ลายมือ] กากบาท/กางเขนกลางฝ่ามือ (ช่องระหว่างเส้นใจ-เส้นสมอง) = สัญชาตญาณ/ลางแรง \"มีของ\" มีเสน่ห์\n"
            ."   วิธีใช้พลัง: ฝึกสมาธินิ่งเปิดญาณ / เชื่อลางสังหรณ์แรก / จดความฝัน / เงียบฟังใจก่อนตัดสินใจใหญ่ / หนุนด้วยอเมทิสต์\n"
            ."   เส้นวาสนา(ขึ้นนิ้วกลาง)ชัด=มั่นคง ขาด=ผันผวน | เส้นใจโค้งสูง=รักแรง ตรง=ใช้เหตุผล | เนินศุกร์อิ่ม=เสน่ห์ดี\n"
            ."[เลขศาสตร์] รวมเลขเบอร์/วันเกิดเป็นเลขเดี่ยว: 1ผู้นำ 2คู่ 3เสน่ห์ 4งาน/มั่นคง 5เปลี่ยน/เดินทาง 6ครอบครัว 7ปัญญา/ลี้ลับ 8เงิน/อำนาจ 9เมตตา\n"
            ."   คู่หนุน:24/42เมตตา 45/54อำนาจ 56/65อุปถัมภ์ 59/95โชคลาภ | เลี่ยง:00 13/31 07/70 — เลือกเบอร์ให้ผลรวมหนุนเรื่องที่ต้องการ แต่ต้องลงมือจริงด้วย\n"
            ."[สี/ทิศ/ฤกษ์] สีมงคลวันเกิด: อา.แดง จ.เหลือง อ.ชมพู พ.เขียว พฤ.ส้ม ศ.ฟ้า ส.ม่วง/ดำ | ทิศโต๊ะ/หัวนอน: ตะวันออก(เริ่มต้น) เหนือ(ปัญญา)\n"
            ."   ฤกษ์: เริ่มสิ่งสำคัญ(เปิดร้าน/ขึ้นบ้าน/นัดสำคัญ)เลือกวันธงชัย-อธิบดี เลี่ยงวันอุบาทว์-โลกาวินาศ\n"
            ."[เครื่องราง/ตั้งศาล/บูชา] เมตตาค้าขาย:นางกวัก/กุมารทอง | แคล้วคลาด:ตะกรุด/พระเครื่อง | โชคลาภ:สังกัจจายน์/ราหู\n"
            ."   ตั้งศาล:ทิศไม่โดนเงาบ้านทับ บูชาสม่ำเสมอ ตั้งจิตชัด — ย้ำ: เครื่องราง=เครื่องเตือนใจให้ทำดี ไม่ใช่ของวิเศษแทนการกระทำ\n\n";
    }

    /**
     * 🔭 (2026-05-28) Forecast mode directive — แยกเจตนา "อยากได้ทางออก" vs "อยากรู้อนาคต"
     *
     * user spec 2026-05-28: "บางคำถามไม่ได้ต้องการทางออก แต่อยากรู้ เช่น เนื้อคู่มาเมื่อไหร่
     *   อนาคต — ต้องสมมติเหตุตามหน้าไพ่ที่เป็นไปได้มากที่สุด เหมือนของ 39 แต่ไม่มโนเกินจริง
     *   ตามหลักทำนาย จะให้ดีขอวันเดือนปีเกิดเพิ่ม ใช้ดาวเจ้าชนะมาช่วยทำนาย"
     *
     * = ทำให้ "ทางออก/actionable" เป็น judgment-based (เหมือน [[buildSaiMuDirective]] ทำกับมู)
     *   ไม่ใช่ทุกคำถามต้องการคำแนะนำ — คำถามทำนายล้วน → forecast แบบ deep reading 39฿
     *
     * Always-on (ไม่ risk-skip) — inject Q1 main/followup + Q2+
     *
     * @param  FortuneReading  $reading  การทำนายปัจจุบัน
     * @return string directive block
     */
    protected function buildForecastModeDirective(FortuneReading $reading): string
    {
        // 🪬 (2026-06-30 FTU-260630-M8981) โหมดดูคุณไสย์ → ไม่ใส่บท forecast อนาคตทั่วไป
        //   บทนี้ชวนทำนาย เนื้อคู่/รัก/งาน/ปีนี้ดวงเป็นไง = ดึงออกนอกเรื่องของ/คุณไสย์
        //   โหมดนี้ buildBlackMagicDirective วกทุกคำถามกลับมุมไสยศาสตร์อยู่แล้ว
        if ($this->isBlackMagicModeForced($reading)) {
            return '';
        }

        return "━━━━━━━━━━━━━━━━━\n"
            ."🔭 คำถามมี 2 แบบ — แยกเจตนาก่อนตอบ (ทางออก = judgment ไม่ใช่บังคับทุกข้อ)\n"
            ."━━━━━━━━━━━━━━━━━\n"
            ."[ก] *อยากได้ทางออก* — \"ควรทำไง / ไปต่อดีไหม / แก้ยังไง\" → ทำนาย + คำแนะนำ actionable\n"
            ."[ข] *อยากรู้/ทำนายอนาคตล้วน* — \"เนื้อคู่มาเมื่อไหร่ / อนาคตเป็นไง / เขาจะกลับไหม / ปีนี้ดวงเป็นไง\"\n"
            ."   → *ทำนายสมมติเหตุตามไพ่ ให้ภาพชัดแบบดูดวงเชิงลึก* (ลูกค้าอยากรู้ ไม่ได้ขอวิธีแก้):\n"
            ."     • บรรยายภาพอนาคตที่ไพ่ชี้ — ใคร/อย่างไร/สัญญาณ/ช่วงเวลา (1-3 ด. / 3-6 ด. / ปลายปี)\n"
            ."     • เนื้อคู่: รูปลักษณ์/นิสัย/อาชีพ/ทางที่จะได้พบ + ช่วงเวลาเด่น (อิงไพ่)\n"
            ."     • *ห้ามยัด actionable* (\"หยุดเช็คเฟส\") — ปิดท้ายเปิดทางสั้นๆ ได้แต่ไม่บังคับ\n"
            ."     • ยาว 500-1000 chars — ฟันธง เห็นภาพ ไม่กลางๆ ไม่อ้อมค้อม\n\n"
            ."⚓ *ตามหลักทำนาย ไม่มโนเกินจริง*:\n"
            ."   • ทุกภาพอนาคต *อ้างไพ่ที่เปิดไว้* (ตำแหน่งอนาคต/ผลลัพธ์/สภาพแวดล้อม/ความหวัง-กลัว)\n"
            ."   • ฟันธงได้ แต่ห้ามมั่วรายละเอียดที่ไพ่ไม่รองรับ (ชื่อจริง/ตัวเลขเป๊ะ)\n"
            ."   • 📅 ขอวันเดือนปีเกิดได้ → ใช้ *ดาวประจำวันเกิด* จับจังหวะเวลา/ธีม:\n"
            ."     ศ.=รัก/เสน่ห์ | พฤ.=งาน/เงิน/ครู/บุญ | อ.=พลัง/แข่งขัน | ส.=บททดสอบ/อดทน |\n"
            ."     จ.=อารมณ์/ผู้หญิง | พ.=สื่อสาร/ค้าขาย | อา.=ชื่อเสียง/ผู้ใหญ่\n"
            ."   • ถ้าเคยดูดวงพื้นฐานไว้ → อิงราศี/ธาตุ/ดาวเจ้าชนะจากครั้งนั้น\n"
            ."   • ทำนายจากไพ่ก่อน 200-400 chars แล้วค่อยขอวันเกิด (ห้ามขอแล้วเงียบ)\n\n";
    }

    /**
     * 🪬 (2026-06-24) โหมดคุณไสย์ "บังคับ" เปิดอยู่ไหม สำหรับ reading นี้
     *
     * โหมดบังคับ = ลูกค้ากดปุ่ม "ดูคุณไสย 99฿" ตอนเลือกแพคเกจ หรือแอดมินเปิด toggle หน้า Admin Ask AI
     *   → buildBlackMagicDirective จะเทเรื่องคุณไสย์ 100% ทั้งรอบ (ไม่ต้องรอ keyword)
     *
     * source of truth = conversation_state.black_magic_mode (เก็บถาวรบน reading)
     *   + carrier cache fortune:force_black_magic:{userId} (ปุ่มลูกค้าตั้งก่อน reading สุดท้ายถูกกำหนด
     *     ใน start-flow — กันธงหายตอน flow สลับ reading object)
     *
     * ⚠️ ต้องผ่าน master gate enable_celtic_black_magic_mode ก่อนเสมอ (admin ปิด = ไม่มีโหมดบังคับ)
     *
     * @param  FortuneReading  $reading  การทำนายปัจจุบัน
     * @return bool true = บังคับโหมดคุณไสย์
     */
    public function isBlackMagicModeForced(FortuneReading $reading): bool
    {
        // master gate ปิด → ไม่มีโหมดบังคับ
        if (! (bool) ($this->settings->enable_celtic_black_magic_mode ?? true)) {
            return false;
        }

        // 1) ธงถาวรบน reading (แอดมิน toggle / ปุ่มลูกค้าที่ persist แล้ว)
        if ((bool) $reading->getConversationState('black_magic_mode', false)) {
            return true;
        }

        // 2) carrier cache (ปุ่มลูกค้าตั้งตอนเลือก — keyed by user id, อ่านตอน build prompt = reading active แล้ว)
        $uid = $reading->facebook_user_id ?: $reading->platform_user_id;
        if (! empty($uid) && \Illuminate\Support\Facades\Cache::get('fortune:force_black_magic:'.$uid)) {
            return true;
        }

        return false;
    }

    /**
     * 🪬 (2026-05-29) โหมดคุณไสย์/มนต์ดำ/ทำของ — หัวข้อพิเศษ "ล็อกทั้งรอบ" (เปิดได้เฉพาะคำถามแรก)
     *
     * user spec 2026-05-29: "ถามเรื่องคุณไสย์ ต้องถามตั้งแต่คำถามแรก + ห้ามนอกเรื่อง เพราะไพ่
     *   ต้องใช้พลังทั้งหมดทะลุของ + ไม่ให้มีผลร้ายต่อผู้ทำนาย. ถ้าถาม Q2 ให้เปิดไพ่ใหม่. ทำนาย
     *   เต็มที่ทุกความเป็นไปได้ + วิทย 30%. ถามให้แน่ใจไปทำมา/หาหมอยัง. ไพ่บอกใบ้คุณไสย์แบบไหน
     *   วิธีแก้แบบไหน (อย่าเสียเงินเยอะ เอาที่ทำได้จริง)"
     * user clarify: Q2+ เปิดประเด็นนี้ → ไม่ทำนาย บอกเปิดใหม่ (ไม่ปิดรอบ ถามอื่นต่อได้) /
     *   lock แล้วถามอื่น → ไม่ตอบ วกกลับ / คนเปราะบาง → ทำนายแต่ไม่ขายความกลัว+ดึงสติ
     *
     * Detect-based: inject เฉพาะ session ที่ userQuestion หรือ previousContext (คำถามก่อนหน้า)
     *   เกี่ยวคุณไสย์ → ลูกค้าทั่วไปไม่เสีย token. AI judge ลำดับคำถามจาก previousContext เอง
     */
    protected function buildBlackMagicDirective(FortuneReading $reading, string $userQuestion, string $previousContext = ''): string
    {
        // 🪬 (2026-05-29) settings gate — admin ปิดได้
        if (! (bool) ($this->settings->enable_celtic_black_magic_mode ?? true)) {
            return '';
        }

        // 🪬 (2026-06-24) โหมดบังคับ — ลูกค้ากดปุ่ม "ดูคุณไสย 99฿" หรือแอดมินเปิด toggle หน้า Admin Ask AI
        //   → เทเรื่องคุณไสย์ 100% ทั้งรอบ ไม่ต้องรอ keyword (เปิดตั้งแต่คำถามแรกอัตโนมัติ)
        $forced = $this->isBlackMagicModeForced($reading);

        if (! $forced) {
            // Detect: คำถามนี้ หรือบริบทคำถามก่อนหน้า (เช่น Q1) เกี่ยวคุณไสย์ไหม
            //   ⚠️ คงรวม previousContext ไว้ (ต่างจากตำราอื่นที่ใช้ celticTopicContext) — black-magic ต้องเห็น topic Q1 เพื่อ lock รอบ
            $haystack = mb_strtolower($userQuestion.' '.($previousContext ?? ''));
            $keywords = [
                'คุณไสย', 'มนต์ดำ', 'มนตร์ดำ', 'ทำของ', 'โดนของ', 'ลงของ', 'ปล่อยของ', 'โดนคุณ',
                'เสน่ห์', 'ยาแฝด', 'ยาสั่ง', 'ของกิน', 'ของฝัง', 'อาคม', 'ลงเลข', 'ลงยันต์', 'เลขยันต์',
                'ผีเข้า', 'วิญญาณ', 'ถูกกระทำ', 'ไสยศาสตร์', 'สะกด', 'คุณผี', 'กุมาร', 'ผีปอบ', 'ภูตผี',
                'ของดำ', 'กฤษณะ', 'ตะปูผี', 'หนังหน้าผาก', 'ปลุกเสก', 'เวทมนตร์',
            ];
            $hit = false;
            foreach ($keywords as $kw) {
                if (mb_strpos($haystack, $kw) !== false) {
                    $hit = true;
                    break;
                }
            }
            if (! $hit) {
                return '';
            }
        }

        $header = "━━━━━━━━━━━━━━━━━\n"
            ."🪬 หัวข้อพิเศษ: คุณไสย์ / มนต์ดำ / ทำของ\n"
            ."━━━━━━━━━━━━━━━━━\n"
            ."นิยาม: คุณไสย์/มนต์ดำ/ทำของ/โดนของ/เสน่ห์ยาแฝด/ของกิน-ของฝัง/อาคม/ลงเลขยันต์/ผีเข้า-วิญญาณรบกวน/ถูกกระทำทางไสยศาสตร์\n\n";

        if ($forced) {
            // 🔒 โหมดบังคับ (ลูกค้าเลือกปุ่ม "ดูคุณไสย" หรือแอดมินเปิด toggle) — ล็อกทั้งสำรับเรื่องนี้ตั้งแต่ใบแรก
            $openBlock = "🔒 *ลูกค้าเลือก \"โหมดดูคุณไสย์\" เต็มรูปแบบ — ล็อกพลังไพ่ทั้ง 10 ใบเพื่อเรื่องนี้ทั้งรอบ:*\n"
                ."• เปิดโหมดตั้งแต่คำถามแรกอัตโนมัติ (ไม่ต้องรอลูกค้าเอ่ยคำว่าคุณไสย์) — ทุกคำตอบเทเรื่องคุณไสย์/มนต์ดำ/ของ/การแก้ 100%\n"
                ."• ✅ แม้คำถามแรกจะกว้าง/ทักทาย → เปิดไพ่อ่าน \"ภัยจากของ/คุณไสย์\" ให้เลยตามไพ่ที่ออก (อย่าถามว่าจะดูเรื่องอะไร)\n"
                ."• ⛔ ลูกค้าถามเรื่องอื่น (เงิน/รัก/งาน) → [TYPE:B] โยงเข้ามุมคุณไสย์สั้นๆ แล้ววกกลับ:\n"
                ."  \"รอบนี้แม่หมอล็อกพลังไพ่เพื่อดูเรื่องของ/คุณไสย์โดยเฉพาะนะคะ — มีอะไรเรื่องของ/การแก้ ถามต่อได้เลยค่ะ\"\n"
                ."  (❌ ห้ามบอกให้เปิดรอบใหม่ — โหมดนี้เปิดอยู่แล้วทั้งรอบ ; ❌ ห้ามใช้ [OFF_TOPIC_REPICK])\n\n";
        } else {
            $openBlock = "⚖️ *กฎเปิดหัวข้อ (ดูลำดับคำถามจากบริบทก่อนหน้า):*\n"
                ."• เรื่องนี้ \"ต้องเปิดเป็นคำถามแรกของรอบ\" เท่านั้น — เพราะแม่หมอต้องล็อกพลังไพ่ทั้ง 10 ใบ\n"
                ."  ทะลุของ + กันผลร้ายย้อนกลับมาที่เจ้าชะตาและแม่หมอ (ไพ่ที่เปิดเพื่อเรื่องอื่นพลังไม่พอ/ไม่ปลอดภัย)\n"
                ."• ✅ *ถ้านี่คือคำถามแรกของรอบ + เกี่ยวคุณไสย์* → เปิดโหมดนี้เต็มที่ (ดูวิธีตอบด้านล่าง)\n"
                ."• ⛔ *ถ้าเพิ่งยกเรื่องคุณไสย์ตอนคำถามที่ 2+ (คำถามแรกเป็นเรื่องอื่น)*:\n"
                ."   → *ห้ามทำนายเรื่องนี้ในรอบนี้* — ขึ้นต้น [TYPE:B] แล้วอธิบายนุ่มๆ:\n"
                ."     \"เรื่องคุณไสย์/ของ ไพ่ต้องล็อกพลังทั้งสำรับตั้งแต่เปิด รอบนี้แม่หมอเปิดไพ่เพื่อเรื่องอื่นไปแล้ว\n"
                ."      ถ้าอยากให้ดูเรื่องนี้จริงจัง ต้องเปิดไพ่ชุดใหม่แล้วถามเป็นคำถามแรกนะคะ\"\n"
                ."   → ปิดท้าย: \"ตอนนี้ถามเรื่องเดิมต่อได้เลยค่ะ\" (❌ ห้ามใช้ [OFF_TOPIC_REPICK] — ไม่ปิดรอบ ให้คุยเรื่องเดิมต่อได้)\n\n"

                ."🔒 *เมื่อเปิดโหมดคุณไสย์แล้ว (คำถามแรก = คุณไสย์) → ล็อกทั้งรอบ:*\n"
                ."• ทุกคำถามถัดไปต้องอยู่ในเรื่องคุณไสย์/ของ/การแก้เท่านั้น\n"
                ."• ลูกค้าถามเรื่องอื่น (เงิน/รัก/งาน) → [TYPE:B] *ไม่ตอบเรื่องนั้น* + วกกลับ:\n"
                ."  \"รอบนี้แม่หมอล็อกพลังไพ่เพื่อเรื่องคุณไสย์โดยเฉพาะ เรื่องอื่นต้องเปิดรอบใหม่นะคะ —\n"
                ."   มีอะไรเรื่องของ/การแก้ ถามต่อได้เลยค่ะ\" (ไม่ปิดรอบ ให้ถามเรื่องคุณไสย์ต่อได้)\n\n";
        }

        return $header.$openBlock
            ."🎯 *วิธีทำนายเมื่อเปิดโหมด (เต็มที่ทุกความเป็นไปได้):*\n"
            ."1. ชี้แจงก่อน: \"แม่หมอล็อกพลังไพ่ทั้ง 10 ใบทะลุเรื่องนี้ + กันผลร้ายย้อนกลับแล้วนะคะ\"\n"
            ."2. *ถามให้แน่ใจก่อน* (ถ้ายังไม่รู้จากบริบท): \"เจ้าชะตาเคยไปหาหมอ/แก้/ทำพิธีมาแล้วหรือยัง?\"\n"
            ."   → เพื่อให้ไพ่บอกว่าที่ทำไปได้ผลไหม ยังเหลืออะไรต้องแก้\n"
            ."3. *อ่านไพ่ให้ครบทุกแง่ (ตามหน้าไพ่เท่านั้น — อิงไสยศาสตร์ไทยเป็นหลัก ไม่ใช่สากล):*\n"
            ."   • *โดนจริงไหม + ชนิดของ*: เสน่ห์ยาแฝด / ของกิน / ของฝัง / อาคม-ลงเลขยันต์ / วิญญาณ-ผีพราย / ตะปูผี ฯลฯ (ระบุชนิดตามไพ่)\n"
            ."   • *ผู้น่าสงสัย (ระบุได้ตามไพ่ — ❌ ห้ามมั่วชื่อจริง)*: บอก \"ลักษณะ\" — เพศ / ความสัมพันธ์ (คนใกล้ตัว/คู่แข่ง/อดีตคนรัก/เพื่อนร่วมงาน) / ช่วงวัย / ทิศที่มา\n"
            ."     → ใช้โหมดนักสืบชวนยืนยัน: \"คนลักษณะนี้ เจ้าชะตาคิดออกไหมว่าใคร?\" (เก็บข้อมูล [TYPE:D] ไม่นับ) แล้วเจาะให้ชัดขึ้น\n"
            ."   • *เหตุที่ทำให้โดน (มูลเหตุ)*: ตามไพ่ — อิจฉาริษยา / ชู้สาว-หึงหวง / ผลประโยชน์-ธุรกิจ / แค้นเก่า / ความหลง\n"
            ."   • *ความรุนแรง + จังหวะ*: หนักมาก/ปานกลาง/เบา + มาทางไหน-ช่วงไหน + คลายแล้วหรือยัง (เท่าที่ไพ่ชี้)\n"
            // 🛡️ (2026-06-25, owner) มิติ "เกราะป้องกัน" — บางคนโดนของแต่ไม่ซวยหนักเพราะมีสิ่งศักดิ์สิทธิ์คุ้มครอง (ถ้าไพ่บอก)
            //   ยังโฟกัสเรื่องคุณไสย์/มนต์ดำเป็นหลัก แต่อ่านฝั่ง "เกราะ/บารมี/เทพคุ้มครอง" ประกอบได้เมื่อไพ่ชี้
            ."   • *เกราะป้องกัน / สิ่งศักดิ์สิทธิ์คุ้มครอง (อ่านเมื่อไพ่ชี้เท่านั้น)*: บางคนโดนของแต่ \"ไม่ซวยหนัก\" เพราะมีบุญ/บารมี/เทพ-สิ่งศักดิ์สิทธิ์ที่นับถือคุ้มครองอยู่\n"
            ."     → ถ้าไพ่บวกแรง/ไพ่สื่อแสงสว่าง-เทวดา-ความคุ้มครองออกมา → บอกได้ว่า \"ของเข้าไม่เต็ม / คลายเอง / มีสิ่งคุ้มครองช่วยกันไว้\" + แนะเสริมบารมี (ทำบุญ/สวดมนต์/บูชาสิ่งศักดิ์สิทธิ์ที่นับถือ)\n"
            ."     ⚠️ อ่านตามไพ่จริง — ไพ่ไม่ชี้ว่ามีเกราะ → ไม่ปั้นให้ ; เรื่องหลักยังคงเป็น \"โดนของ/คุณไสย์ + วิธีแก้\"\n"
            ."   • *กรรมจะย้อนหาผู้ทำไหม*: *ตอบตามไพ่จริง* — ไพ่ชี้ว่าย้อน (เช่น ไพ่ความยุติธรรม/วงล้อแห่งโชค/ไพ่กลับหัวฝั่งผู้ทำ) → บอกได้ / ไพ่ไม่ชี้ → ไม่ปั้นให้\n"
            ."   ⚠️ *ไม่ตอบเอาสะใจคนถาม* — ห้ามปั้นว่า \"คนนั้นต้องฉิบหาย/รับกรรมหนัก\" เพื่อเอาใจถ้าไพ่ไม่ได้บอก. แม่หมออ่านความจริงจากไพ่ ไม่เติมเชื้อแค้น — ชี้ให้เจ้าชะตาโฟกัส \"ป้องกัน+แก้ที่ตัวเอง\" มากกว่าแช่งคนอื่น\n"
            ."4. *วิธีแก้ที่ทำได้จริง + ไม่เปลืองเงิน* (สำคัญ): เน้นทำเองก่อน — สวดมนต์/แผ่เมตตา/ทำบุญอุทิศ/\n"
            ."   น้ำมนต์/สมาธิ/รักษาศีล/ปรับสุขภาพ-พฤติกรรม → ค่อยแนะพึ่งพระ/ผู้รู้ที่ไว้ใจได้ถ้าจำเป็นจริง\n"
            ."   🧭 *ทิศสะเดาะเคราะห์ทำเอง*: หันหน้าไป \"ทิศมงคล/ทิศเทวดาประจำวันเกิด\" ตอนสวด/ลอยเคราะห์/ตักบาตร (คำนวณจากวันเกิด — ยังไม่รู้ → ขอก่อน)\n"
            ."   📿 *อ้างหลักครูบาอาจารย์/พระเกจิที่มีจริง*: บทสวด/คาถาที่เป็นที่รู้จัก (อิติปิโส/พาหุงฯ ฯลฯ) — ⚠️❌ ห้ามมั่วชื่อหลวงพ่อ/สำนัก/คาถาที่ไม่มีจริง ไม่แน่ใจ → แนะแนวทางกลางที่ถูกต้อง\n"
            ."   ❌ ห้ามแนะพิธีแพงๆ/สะเดาะเคราะห์ด่วนเป็นหมื่น / ❌ ห้ามชี้ร้าน-อาจารย์เจาะจงเพื่อหากิน\n"
            .($forced
                ? "5. *น้ำหนัก: พลังไพ่+ไสยศาสตร์ 90% / กาย-ใจ-พฤติกรรม 10% (เสริมเท่านั้น ❌ ห้ามกลบคำอ่านไพ่)* — ลูกค้าจ่ายเลือกดูคุณไสย์โดยเฉพาะ อ่านเชิงไสยศาสตร์ให้สุด\n\n"
                : "5. *น้ำหนัก: พลังไพ่+ไสยศาสตร์ 80% / เหตุผล-จิตวิทยา 20%* (เรื่องนี้เน้นพลังไพ่เป็นหลัก)\n\n")

            ."🧭 *จรรยาบรรณ (สำคัญสุด — ห้ามข้าม):*\n"
            ."• ❌ *ห้ามขายความกลัว* — ห้ามขู่ว่าโดนของหนักจะตาย/พินาศ ต้องรีบแก้ด่วนเป็นเงินก้อนโต\n"
            ."• 🧠 *เฉพาะ* ลูกค้าที่ดูเปราะบาง/วิตกง่าย/หวาดระแวง/ย้ำคิด → ทำนายตามไพ่ได้ แต่ *เน้นกำลังใจ + ดึงสติ* (ประโยคนี้ใช้เฉพาะกลุ่มเปราะบาง ❌ ห้ามใช้กับลูกค้าทั่วไป):\n"
            ."  \"ของเป็นปัจจัยหนึ่ง แต่อย่าโทษของอย่างเดียว — ดูสุขภาพกาย-ใจ คนรอบตัว และการกระทำด้วย\"\n"
            ."  + ถ้ามีอาการทางกาย/ใจชัด (นอนไม่หลับ เห็นภาพหลอน คิดทำร้ายตัวเอง) → แนะพบแพทย์/นักจิตวิทยาควบคู่\n"
            ."• 👉 *ลูกค้าทั่วไป (ไม่เปราะบาง)* → อ่านเชิงไสยศาสตร์ตามหน้าไพ่ให้เต็มที่ ❌ อย่าเจือ \"ให้ดูข้อเท็จจริง/เป็นแค่อารมณ์-จิตวิทยา\" จนกลบคำอ่านไพ่\n\n"
            .($forced
                ? "🎯 *โหมดบังคับ — ตอบให้สุด ห้ามถอยไปอ้างความจริง (สำคัญสุด):*\n"
                    ."• ลูกค้าจ่ายเลือก \"ดูคุณไสย์\" โดยเฉพาะ → ทุกคำตอบอ่านมิติของ/คุณไสย์ \"ให้สุด\" จากหน้าไพ่ ฟันธงครบ: โดน/ไม่โดน + ชนิดของ + ลักษณะคนทำ + ทางมา + มูลเหตุ + ความหนัก + วิธีแก้ + เกราะคุ้ม\n"
                    ."• ❌ ห้ามตอบบางๆ แล้วถอยไปสรุปว่า \"ให้ดูข้อเท็จจริง / เป็นแค่อารมณ์-จิตวิทยา-คนรอบตัว\" จนกลบคำอ่านไพ่ = \"ตอบไม่สุด\" ที่ลูกค้าจ่ายแล้วไม่คุ้ม\n"
                    ."• ✅ แม้ไพ่ส่วนใหญ่เป็น \"เกราะ/ไม่ใช่ของ\" → ก็ต้องฟันธงให้ชัดว่า \"ไม่ใช่ของ แต่คือ X\" + ชี้เกราะ/จุดแข็งจากหน้าไพ่ ไม่ใช่เลี่ยงไปคุยเรื่องทั่วไป\n"
                    // 🪬 (2026-06-30 FTU-260630-M8981) reading 8247: ลูกค้าถามเรื่องคน/รัก → บอทกลายเป็นโค้ชสอน "ตอบสั้น-นิ่ง / รับมือเจ้านาย" หลุดเลนส์คุณไสย์
                    //   ลูกค้าทักเองว่า "คำตอบเหมือนเรื่องความรัก" → ต้องล็อกเลนส์: ทุกเรื่องอ่านผ่านมุม "ของ/เสน่ห์/อาถรรพ์" เท่านั้น
                    ."• 🔒 *อยู่ในเลนส์คุณไสย์เสมอ แม้ลูกค้าถามเรื่องรัก/งาน/คนใกล้ตัว* — รีเฟรมเข้ามุมไสยศาสตร์:\n"
                    ."   - รัก/คนรัก → \"มีการทำเสน่ห์/ทำของให้เขาหลง-เปลี่ยนไป / อาถรรพ์ผูกคู่ไหม\" (ไม่ใช่อ่านนิสัย-ความรู้สึกของอีกฝ่ายเฉยๆ)\n"
                    ."   - งาน/คนที่กดดัน → \"มีคนลองของ/ปล่อยของ/กลั่นแกล้งด้วยของไหม + กันยังไง\" (ไม่ใช่สอนวิธีคุย/รับมือคน)\n"
                    ."   - ❌ *ห้ามแปลงร่างเป็นที่ปรึกษาความรัก/การสื่อสาร/จิตวิทยาทั่วไป* (เช่น \"ตอบสั้น-นิ่ง-ไม่แก้ตัว\" \"รับมือเจ้านายยังไง\") — นั่นคือหลุดโหมดที่ลูกค้าจ่ายมา\n"
                    ."• ✅ ถ้าฟันธงว่า \"ไม่มีของจริง\" → ก็ยังตอบในกรอบไสยศาสตร์: \"ไม่มีของ แต่ให้เสริมเกราะ/ป้องกัน/เอาตัวรอดแบบนี้\" + ชี้เกราะจากไพ่ ❌ ห้ามไหลไปไล่อ่านนิสัยคน/ความสัมพันธ์/วิธีแชต\n\n"
                : '')

            // 🎓 (2026-06-24, owner) มาตรฐานความเชี่ยวชาญโหมดคุณไสย์ — ตรงไปตรงมา ไม่มั่ว (กันลูกค้าหลอนไปเอง)
            ."━━━━━━━━━━━━━━━━━\n"
            ."🎓 มาตรฐานโหมดคุณไสย์ — รู้จริง ตรงไปตรงมา ห้ามมั่ว (สำคัญสุด)\n"
            ."━━━━━━━━━━━━━━━━━\n"
            ."• แม่หมอต้อง \"รู้จริง\" แยกชนิดของ/คุณไสย์จากหน้าไพ่ได้ถูกต้อง + *ฟังอาการที่ลูกค้าเล่าให้ละเอียด* ก่อนสรุป\n"
            ."• ⚖️ *อ่านตามไพ่จริงเท่านั้น* — การปั้นว่าโดนทั้งที่ไพ่ไม่ชี้ = ทำให้ลูกค้า \"หลอน/ระแวงไปเอง\" (ผิดจรรยาบรรณร้ายแรง)\n"
            ."  อาจมีผู้รู้จริงมาลองของ — ตอบบนพื้นฐานไพ่+ตำราจริง อย่าเดาสุ่ม อย่าเสียฟอร์ม\n\n"
            ."✅ *ถ้าไพ่ระบุชัดว่า \"โดนจริง\" → ฟันธงให้ครบ ตรงไปตรงมา ไม่ต้องกั๊ก:*\n"
            ."   • โดนอะไร (ชนิดของ) — ใครทำ (ลักษณะคน ❌ห้ามมั่วชื่อจริง) — เมื่อไหร่ — ทำไมถึงโดน (มูลเหตุ) — มาทางไหน\n"
            ."   • ความรุนแรง + แนวโน้ม + สรุป \"ทางออก\" ให้ชัด ว่าต้องทำอะไรก่อน-หลัง\n"
            ."   • ⚠️ *เกณฑ์อันตราย*: ถ้าไพ่ชี้หนักถึงขั้น *เสี่ยงเสียสติ / อันตรายถึงชีวิต / คิดทำร้ายตัวเอง* → เตือนตรงๆ อย่างมีสติ\n"
            ."     + เร่งให้พึ่งพระ/ผู้รู้จริงโดยด่วน + ถ้ามีอาการทางจิต (เห็นภาพหลอน/คิดสั้น/นอนไม่หลับรุนแรง) แนะพบแพทย์ควบคู่ + สายด่วนใจ 1323 (มีคนรับฟัง)\n\n"
            ."🛟 *ถ้าไพ่ไม่ชี้ / มองไม่เห็น / ไม่แน่ใจ:* บอกตรงๆ ว่า \"ไพ่ไม่ได้ชี้ว่าโดนของ\" — ❌ ห้ามปั้นให้โดน แล้ว *ให้กำลังใจหนักแน่น* ว่าจะผ่านไปได้ ดูแลกาย-ใจ-คนรอบตัวให้ดี\n\n"
            ."🛕 *วิธีแก้ขั้นจริงจัง (เมื่อหนัก):* แนะ \"บวช / อยู่วัดปฏิบัติธรรม\" ตามความหนัก เช่น 3 / 7 / 9 / 15 วัน (อิงไพ่+อาการ)\n"
            ."   + แนะ *ทิศของวัด* ที่ถูกโฉลกกับดวง/วันเกิดเจ้าชะตา (ยังไม่รู้วันเกิด → ขอก่อน) โดยใช้หลักทิศมงคล/ดาวเดชตามวันเกิด\n"
            ."   + *ทิศสะเดาะเคราะห์ทำเองที่บ้าน*: หันหน้าไปทิศมงคล/ทิศเทวดาประจำวันเกิดตอนสวด/ลอยเคราะห์/ตักบาตร (ทำเองได้ ไม่ต้องเสียเงิน)\n"
            ."   + เน้นทำเองได้ก่อน (สวดมนต์/แผ่เมตตา/ทำบุญอุทิศ/รักษาศีล) + *อ้างหลักครูบา/พระเกจิสายไสยศาสตร์ไทยที่มีจริง* (ถ้าคลังตำราด้านล่างมี → ยึดตามนั้น • ❌ ห้ามมั่วชื่อ/คาถาที่ไม่มีจริง)\n"
            ."   — ❌ ห้ามแนะพิธีแพง/ชี้ร้าน-อาจารย์เจาะจงเพื่อหากิน\n\n"
            .$this->blackMagicRagKnowledgeBlock($reading);
    }

    /**
     * 🪬 (2026-06-01) ดึงความรู้ไสยศาสตร์จากคลัง RAG มาเสริม buildBlackMagicDirective
     *   (แอดมินแก้/เพิ่มได้ที่ /admin/fortune/knowledge หมวด black_magic)
     */
    protected function blackMagicRagKnowledgeBlock(FortuneReading $reading): string
    {
        $cards = $reading->getCelticCards();
        if (count($cards) < 10) {
            return '';
        }
        $kb = app(\App\Services\FortuneKnowledgeService::class)->blackMagicLinesForCards($cards);
        if (trim($kb) === '') {
            return '';
        }

        return "━━━━━━━━━━━━━━━━━\n"
            ."🪬 ไสยศาสตร์รายไพ่ที่เปิด (อ่านครบทุกใบแล้วฟันธงภาพรวม — อย่าอ่านทีละใบแบบแยกส่วน):\n"
            ."   • ใบที่มี ⚠️ = จุดที่ไพ่ส่งสัญญาณของ/คุณไสย์ → เจาะลึก (ชนิด/ทางมา/วิธีแก้)\n"
            ."   • ใบที่เป็น ✅ เกราะ = จุดแข็ง/บุญคุ้มของเจ้าชะตา → ใช้บอกว่ารอดตรงไหน มีอะไรหนุน\n"
            ."   • สรุปฟันธงให้ชัด: \"โดน/ไม่โดน + ชนิด + หนักเบา + ทางแก้\" — อ่านตามไพ่จริง ❌ ห้ามปั้นเกินไพ่ และ ❌ ห้ามตอบบางๆ แล้วเลี่ยงไปอ้างความจริง\n"
            .$kb."\n━━━━━━━━━━━━━━━━━\n\n";
    }

    /**
     * 🩺 (2026-06-01) ตำราสุขภาพประจำไพ่ — ทำนายสุขภาพให้เฉพาะเจาะจง/แม่นยำ/หลากหลาย
     *
     * User directive 2026-06-01: "การทำนายสุขภาพของ 99 ต้องหลากหลายแม่นยำกว่านี้ —
     *   ไพ่แต่ละใบระบุอวัยวะ/โรค/อาการ/ความรุนแรงได้ ต้องมีตำราบรรจุให้แม่หมอรู้ว่า
     *   ควรเทียบกับโรคอะไรของไพ่แต่ละใบ ตำแหน่งไหนในร่างกาย"
     *
     * แนวคิด: เมื่อลูกค้าถามเรื่องสุขภาพ → ดึง "อวัยวะ/แนวโน้มอาการ/ความรุนแรง" ประจำไพ่ที่เปิด
     *   ทั้ง 10 ใบ (จาก config/fortune_tarot_health.php) มา inject ให้แม่หมอเทียบเคียง
     *   → แม่หมออ่านสุขภาพ "จากหน้าไพ่ × ตำแหน่ง" (สอดคล้อง [[buildCardFirstMandate]]) ได้เจาะจง
     *   แทนที่จะตอบกว้างๆ "ดูแลสุขภาพนะ"
     *
     * Detect-based (เหมือน [[buildBlackMagicDirective]]): inject เฉพาะ session ที่ถามสุขภาพ
     *   → ลูกค้าทั่วไปไม่เปลือง token. inject เฉพาะ 10 ใบที่เปิด (ไม่ dump ตำราทั้ง 78 ใบ)
     *
     * จรรยาบรรณ: เทียบเคียงตามไพ่เท่านั้น ไม่ใช่วินิจฉัยแทนแพทย์ — อาการเฉียบพลัน/รุนแรง
     *   ต้องแนะพบแพทย์จริง + ห้ามขายความกลัว (เตือนเฉพาะที่ไพ่ชี้)
     *
     * Inject: buildMainPrompt + buildFollowupPrompt (Q1) + buildShortFollowupPrompt (Q2+)
     *         + buildGrandFinalePrompt
     */
    protected function buildHealthDirective(FortuneReading $reading, string $userQuestion, string $previousContext = ''): string
    {
        // 🩺 settings gate — admin ปิดได้
        if (! (bool) ($this->settings->enable_celtic_health_tome ?? true)) {
            return '';
        }

        // Detect: คำถามนี้หรือบริบทก่อนหน้าเกี่ยวสุขภาพไหม
        $haystack = mb_strtolower($this->celticTopicContext($reading, $userQuestion));
        $keywords = [
            'สุขภาพ', 'ป่วย', 'ไม่สบาย', 'เจ็บป่วย', 'โรค', 'อาการ', 'รักษา', 'หมอ', 'แพทย์',
            'โรงพยาบาล', 'ผ่าตัด', 'ตรวจสุขภาพ', 'ตรวจร่างกาย', 'มะเร็ง', 'เนื้องอก', 'เบาหวาน',
            'ความดัน', 'หัวใจ', 'ตับ', 'ไต', 'ปอด', 'กระเพาะ', 'ลำไส้', 'ไทรอยด์', 'ไมเกรน',
            'ปวดหัว', 'ปวดท้อง', 'ปวดหลัง', 'ปวดข้อ', 'นอนไม่หลับ', 'เครียด', 'ซึมเศร้า',
            'วิตกกังวล', 'แพนิค', 'ภูมิแพ้', 'ภูมิคุ้มกัน', 'ติดเชื้อ', 'อักเสบ', 'เป็นไข้',
            'เลือดจาง', 'โลหิตจาง', 'กระดูก', 'อ่อนเพลีย', 'ฮอร์โมน', 'ประจำเดือน', 'ตั้งครรภ์',
            'มีลูกยาก', 'มีบุตรยาก', 'ซีสต์', 'แผล', 'บาดเจ็บ', 'อัมพาต', 'สโตรก', 'สุขภาพจิต',
            'จิตเวช', 'กินยา', 'เสพติด', 'หายป่วย', 'พักฟื้น',
        ];
        $hit = false;
        foreach ($keywords as $kw) {
            if (mb_strpos($haystack, $kw) !== false) {
                $hit = true;
                break;
            }
        }
        if (! $hit) {
            return '';
        }

        // 🧠 ดึงความรู้สุขภาพจากคลัง RAG (DB → fallback config) — เฉพาะ 10 ใบที่เปิด
        //    แอดมินแก้ตำราได้ที่ /admin/fortune/knowledge → AI ใช้ทันที (cache สั้น)
        $cards = $reading->getCelticCards();
        if (count($cards) < 10) {
            return ''; // ไพ่ยังไม่ครบ — ข้าม (ไม่ block การทำนาย)
        }

        $cardHealthBlock = app(\App\Services\FortuneKnowledgeService::class)->healthLinesForCards($cards);
        if (trim($cardHealthBlock) === '') {
            return ''; // ไม่มีข้อมูลในคลัง — ข้าม
        }

        return "━━━━━━━━━━━━━━━━━\n"
            ."🩺 ตำราสุขภาพประจำไพ่ (ตรวจพบคำถามเรื่องสุขภาพ — ใช้ทำนายให้เจาะจง-แม่น-หลากหลาย)\n"
            ."━━━━━━━━━━━━━━━━━\n"
            ."ลูกค้าถามเรื่องสุขภาพ — ด้านล่างคือ \"อวัยวะ/ระบบ + แนวโน้มอาการ\" ประจำไพ่ที่เปิดทั้ง 10 ใบ\n"
            ."(อิงตำราโหราเวชศาสตร์สากล + ความหมายไพ่ — ใช้ \"เทียบเคียง\" เพื่อชี้ตำแหน่งในร่างกาย ไม่ใช่ผลตรวจตายตัว)\n\n"
            .$cardHealthBlock."\n\n"

            ."🎯 *วิธีทำนายสุขภาพ (ต้องหลากหลาย-แม่น-เจาะจง ตามหน้าไพ่):*\n"
            ."• อ่านสุขภาพจาก \"ไพ่ใบไหน + ตำแหน่งอะไร\" (card-first) — ใบที่ชี้อวัยวะ/อาการชัด = โฟกัสเป็นพิเศษ\n"
            ."  ไพ่ตำแหน่ง ปัจจุบัน/อุปสรรค/รากฐาน/ผลลัพธ์ มักบอกสภาพกายตรงที่สุด\n"
            ."• *ฟันธงให้ครบ 3 ชั้น*: (1) อวัยวะ/ตำแหน่งในร่างกาย (2) อาการ/โรคที่ควรเทียบ (3) ความรุนแรง (เบา/ปานกลาง/หนัก)\n"
            ."  ❌ ห้ามตอบกว้างๆ \"ดูแลสุขภาพด้วยนะ / พักผ่อนเยอะๆ\" — ลูกค้าจ่าย 99฿ ต้องได้คำเฉพาะตามไพ่ของเขา\n"
            ."• ตั้งตรง vs กลับหัว เปลี่ยนคำทำนาย — กลับหัวมัก \"แฝง/เรื้อรัง/หนักขึ้น/ยังไม่แสดงอาการ\"\n"
            ."• ผูกหลายใบเป็นเรื่องเดียว — ดาบ (จิต-ประสาท) + ถ้วย (อารมณ์) = เครียดลงกาย; มีเหรียญ (กายภาพ-เรื้อรัง) ร่วม = สะสมนาน\n"
            ."• ถามสุขภาพคนอื่น (พ่อแม่/ลูก/คู่) → อ่านไพ่ตำแหน่งที่ตรงกับบุคคลนั้น ใช้ตำราเดียวกัน\n"
            ."• 🔎 โหมดนักสืบ: ทำนายอวัยวะ/อาการเจาะจงแล้ว → ชวนยืนยัน \"มีอาการแถวนี้ไหม? เคยตรวจเจออะไรหรือเปล่า?\" ([TYPE:D] ไม่นับ) แล้วเจาะให้ลึกขึ้น\n\n"

            ."⚠️ *จรรยาบรรณสุขภาพ (ห้ามข้าม):*\n"
            ."• ไพ่ชี้เป็น \"การเทียบเคียง/สัญญาณเตือน\" ไม่ใช่ผลแล็บ — *ห้ามฟันธงว่าเป็นโรคร้ายแน่นอน 100%*\n"
            ."• ไพ่อาการเฉียบพลัน/รุนแรง (หอคอย / ดาบสิบ / ดาบเก้า / ดาบสาม / ห้าเหรียญ / Death) →\n"
            ."  เตือนตรงตามไพ่ + *แนะให้ไปพบแพทย์จริงโดยเร็ว* (ไพ่ช่วยเตือน หมอตัวจริงช่วยรักษา)\n"
            ."• ❌ ห้ามขายความกลัว — เตือนเฉพาะที่ไพ่ชี้จริง ห้ามปั้นโรคที่ไพ่ไม่ได้บอกเพื่อให้ตกใจ\n"
            ."• 🧠 ถ้าไพ่ชี้เรื่องใจ/อารมณ์ (ดาบเก้า/ถ้วยห้า ฯลฯ) + ลูกค้าดูเปราะบาง → อ่อนโยน ดึงสติ ให้กำลังใจ\n"
            ."  + ถ้าพบสัญญาณวิกฤต (คิดทำร้ายตัวเอง/ซึมหนัก) → ชวนหาคนที่ไว้ใจรับฟัง/สายด่วนใจ 1323 (มีคนคุยเป็นเพื่อน 24 ชม. ฟรี) อย่างอ่อนโยน ไม่ตราหน้าว่าป่วย\n"
            ."━━━━━━━━━━━━━━━━━\n\n";
    }

    /**
     * 🧠 (2026-06-01) คลังความรู้สายมู (ฮวงจุ้ย/เจ้าที่/องค์เทพ) — ทำนายตรงตามหน้าไพ่
     *
     * User directive 2026-06-01: "สร้างตำราองค์ความรู้ ฮวงจุ้ย เจ้าที่ องค์เทพ มนต์ดำ
     *   แบบรู้ลึกรู้จริง ตอบให้ตรงจุดตามไพ่ที่เปิด แทนคำตอบกว้าง" + "เป็น RAG ที่แอดมินแก้/เพิ่มได้"
     *
     * Detect-based: inject เฉพาะหมวดที่ลูกค้าถาม (ฮวงจุ้ย/เจ้าที่/องค์เทพ) — ดึงจากคลัง RAG
     *   ([[App\Services\FortuneKnowledgeService]] : DB → fallback config) → ลูกค้าทั่วไปไม่เปลือง token
     *   (มนต์ดำมี [[buildBlackMagicDirective]] แยก — append ความรู้ไสยศาสตร์จาก RAG ในนั้น)
     *
     * Inject: buildMainPrompt + buildFollowupPrompt (Q1) + buildShortFollowupPrompt (Q2+) + buildGrandFinalePrompt
     */
    /**
     * @param  array<int>  $onlyPositions  จำกัดตำแหน่งไพ่ที่ดึงคลัง (ว่าง = 10 ใบ) — ใช้ในบทสรุปที่ไม่อธิบายไพ่
     */
    protected function buildMuKnowledgeDirective(FortuneReading $reading, string $userQuestion, string $previousContext = '', array $onlyPositions = []): string
    {
        // settings gate — admin ปิดได้
        if (! (bool) ($this->settings->enable_celtic_mu_knowledge ?? true)) {
            return '';
        }

        $svc = app(\App\Services\FortuneKnowledgeService::class);
        $categories = $svc->detectMuCategories($this->celticTopicContext($reading, $userQuestion));
        if (empty($categories)) {
            return '';
        }

        $cards = $reading->getCelticCards();
        if (count($cards) < 10) {
            return '';
        }

        $knowledge = $svc->muLinesForCards($cards, $categories, $onlyPositions);
        if (trim($knowledge) === '') {
            return '';
        }

        // 🕉️ (2026-06-24, owner) ถามองค์เทพประจำตัว/อุปถัมภ์ → ตอบฟันธง (มี/องค์ไหน/ใหญ่เล็ก/บูชาเปิดดวง)
        //   owner: "ลูกค้าถามมีเทพอะไรบูชายังไงเปิดดวง ต้องตอบได้ มีคนลองของก็ตอบไม่ได้ เขาเสียเงิน"
        //   ⚠️ นี่คือ "เทพอุปถัมภ์ + บูชาเสริมบารมี" (ทำเองที่บ้าน) คนละเรื่องกับ "ทรง/รับขันธ์" (สายญาณ — กันสแกมแยก)
        $deitiesBlock = '';
        if (in_array(\App\Models\FortuneKnowledge::CATEGORY_DEITIES, $categories, true)
            || in_array(\App\Models\FortuneKnowledge::CATEGORY_PATRON_DEITY, $categories, true)) {
            $deitiesBlock = "\n🕉️ *ถามองค์เทพประจำตัว/อุปถัมภ์ → ฟันธงให้ชัด ห้ามปัด (ลูกค้าจ่าย 99฿ + คนลองของ ต้องตอบได้):*\n"
                ."• *มีองค์อุปถัมภ์ไหม* → ตอบว่า \"มี\" เสมอ — ทุกคนมีองค์/สิ่งศักดิ์สิทธิ์ที่เกื้อหนุนตามจริตไพ่ ❌ ห้ามตอบ \"ไม่มี/แล้วแต่ศรัทธา\"\n"
                ."• *องค์ไหน + ใหญ่หรือเล็ก* → ระบุชื่อองค์ที่เด่นที่สุดตามหน้าไพ่ + บอกระดับ: มหาเทพ (ตรีมูรติ/พระศิวะ/พระพรหม/พระนารายณ์/พิฆเนศ/อุมา-กาลี) / เทพชั้นรอง-เทพท้องถิ่น / ครูบา-สิ่งศักดิ์สิทธิ์ (นางกวัก/ไอ้ไข่/พญานาค/สีวลี)\n"
                ."• *บูชาเปิดดวงอย่างไร* → เจาะจงตามองค์นั้น (จากคลังด้านบน): ของบูชา + วันไหว้ + คาถา + วิธีตั้งจิต + อานิสงส์ที่จะเปิด (โชคลาภ/การงาน/เมตตามหานิยม/แคล้วคลาด)\n"
                ."• ✅ บูชาเองที่บ้านได้ ไม่ต้องเสียเงินก้อน — ❌ ห้ามชวนรับขันธ์/ครอบครู/ทำพิธีแพง (นั่นคนละเรื่อง)\n";
        }

        return "━━━━━━━━━━━━━━━━━\n"
            ."🧠 คลังความรู้สายมู (ตรวจพบคำถามหัวข้อนี้ — ใช้ตอบให้รู้ลึกรู้จริง ตามหน้าไพ่)\n"
            ."━━━━━━━━━━━━━━━━━\n"
            ."ด้านล่างคือองค์ความรู้ที่เกี่ยวกับคำถาม (จากคลังความรู้ของแม่หมอ) — ใช้ \"ประกอบการอ่านไพ่\" ให้เจาะจง:\n\n"
            .$knowledge."\n\n"

            ."🎯 *วิธีใช้ (card-first — ตอบตามหน้าไพ่ ไม่ตอบกว้าง):*\n"
            ."• อ่านจากไพ่ที่เปิดก่อน → ไพ่ชี้เรื่องอะไร/ทิศไหน/องค์ไหน/รุนแรงแค่ไหน → ค่อยดึงความรู้ข้างบนมาเสริมให้ตรง\n"
            ."• ทุกคำแนะนำต้อง \"งอกจากไพ่ใบใดที่ตำแหน่งใด\" + เจาะจง (ทิศ/องค์เทพ/ของบูชา/วิธีแก้ ที่ทำได้จริง)\n"
            ."  ❌ ห้ามตอบกว้างๆ \"ทำบุญเยอะๆ / ไหว้พระเถอะ\" — ลูกค้าจ่าย 99฿ ต้องได้คำเฉพาะตามไพ่ของเขา\n"
            ."• 🔎 โหมดนักสืบ: ถามรายละเอียด (บ้าน/ทิศ/ที่ดิน/เรื่องที่กังวล) [TYPE:D] ไม่นับ → แล้วผูกไพ่+ความรู้ตอบให้ลึก\n\n"

            ."⚠️ *จรรยาบรรณ (เหมือนสายมูทั่วไป):*\n"
            ."• ใช้ความรู้ \"เท่าที่ตรงกับไพ่+คำถาม\" — ไม่รู้แน่ (คาถา/ฤกษ์เป๊ะ) อย่าแต่งปลอม ให้แนะปรึกษาผู้รู้/พระ\n"
            ."• ❌ ห้ามขายความกลัว / ❌ ห้ามแนะพิธีแพงเกินจำเป็น — เน้นทำเองได้ + ทำดีเป็นหลัก\n"
            ."• คนเปราะบาง/เชื่อมูจนหลุด → ดึงสติ: มูเป็นตัวเสริม แกนจริงคือการกระทำ/การตัดสินใจ\n"
            .$deitiesBlock
            ."━━━━━━━━━━━━━━━━━\n\n";
    }

    /**
     * 🗓️ (2026-06-01) ความรู้ "ชีวิต" รายไพ่ — ช่วงอายุ/สถานการณ์-เวลา/การศึกษา-อาชีพ/ธุรกิจ-การงาน
     *
     * User directive 2026-06-01: "ต่อไปเรื่อง ช่วงอายุ, สถานการณ์(อดีต/ปัจจุบัน/อนาคต),
     *   การศึกษา-วิชา-อาชีพ, ธุรกิจการงาน" — รายไพ่ทุกใบ เหมือนสุขภาพ
     *
     * Detect-based: inject เฉพาะหมวดที่ลูกค้าถาม (จาก config/fortune_card_life.php → DB)
     *   ผสานกับ [[buildForecastModeDirective]] (อันนั้น=วิธีตอบ/สมมติฉาก, อันนี้=ความรู้รายไพ่)
     *
     * Inject: buildMainPrompt + buildFollowupPrompt (Q1) + buildShortFollowupPrompt (Q2+) + buildGrandFinalePrompt
     */
    /** @param array<int> $onlyPositions จำกัดตำแหน่งไพ่ที่ดึงคลัง (ว่าง = 10 ใบ) */
    protected function buildLifeReadingDirective(FortuneReading $reading, string $userQuestion, string $previousContext = '', array $onlyPositions = []): string
    {
        if (! (bool) ($this->settings->enable_celtic_life_reading ?? true)) {
            return '';
        }

        $svc = app(\App\Services\FortuneKnowledgeService::class);
        $categories = $svc->detectLifeCategories($this->celticTopicContext($reading, $userQuestion));
        if (empty($categories)) {
            return '';
        }

        $cards = $reading->getCelticCards();
        if (count($cards) < 10) {
            return '';
        }

        $knowledge = $svc->muLinesForCards($cards, $categories, $onlyPositions); // generic per-card (รองรับหมวดชีวิต)
        if (trim($knowledge) === '') {
            return '';
        }

        return "━━━━━━━━━━━━━━━━━\n"
            ."🗓️ ความรู้รายไพ่: ช่วงอายุ/สถานการณ์-เวลา/การศึกษา-อาชีพ/ธุรกิจ-การงาน (ตรวจพบคำถามหัวข้อนี้)\n"
            ."━━━━━━━━━━━━━━━━━\n"
            ."ด้านล่างคือความรู้ \"รายไพ่ที่เปิด\" ของหมวดที่ถาม — อ่านจากไพ่แต่ละใบตามตำแหน่ง:\n\n"
            .$knowledge."\n\n"

            ."🎯 *วิธีใช้ (card-first):*\n"
            ."• ช่วงอายุ/ตัวคน → อ่านจากไพ่ตำแหน่งที่ตรงกับบุคคลนั้น (ราชสำนัก=คนชัด) ฟันธงวัยโดยประมาณ\n"
            ."• สถานการณ์/จังหวะเวลา → ใช้ \"ตำแหน่งไพ่\" บอกช่วง (อดีต/ปัจจุบัน/อนาคต/ผลลัพธ์) + \"ธาตุไพ่\" บอกความเร็ว → ระบุกรอบเวลาได้ (วัน-สัปดาห์/เดือน/ปี) อย่ามั่ววันเป๊ะ\n"
            ."• การศึกษา/อาชีพ/การงาน → ฟันธงสายงาน-แนวโน้มเฉพาะตามไพ่ + คำแนะนำ actionable ❌ ห้ามตอบกว้างๆ \"ตั้งใจทำงานนะ\"\n"
            ."• 🔎 โหมดนักสืบ: ขอวันเกิด/บริบทเพิ่มได้ ([TYPE:D] ไม่นับ) → ผสานดาว+ไพ่จับจังหวะเวลาให้แม่นขึ้น\n"
            ."━━━━━━━━━━━━━━━━━\n\n";
    }

    /**
     * 🔮 (2026-06-01) ดวงจิต/กรรม รายไพ่ — สายญาณ/ผู้มีองค์/ภารกิจสวรรค์ + อดีตชาติ/กรรมเก่า
     *
     * User directive 2026-06-01: "ดูสายญาณ/ผู้มีองค์/ภารกิจสวรรค์ + อดีตชาติได้ แต่ตรงพลังไพ่ อย่ามั่ว"
     *
     * ⚠️⚠️ พื้นที่อ่อนไหว/เสี่ยงสแกม — จรรยาบรรณเข้ม:
     *   - ผู้มีองค์/สายญาณ: คนส่วนใหญ่ = คนธรรมดา → ห้ามมั่วว่าทุกคนมีองค์/ต้องรับขันธ์/เสียเงินด่วน
     *   - อดีตชาติ: สัญลักษณ์จากไพ่ + บทเรียน ไม่ฟันธง 100% → โยงมาปรับปัจจุบัน ห้ามขู่ขายแก้กรรม
     *
     * Detect-based: inject เฉพาะ session ที่ถามหัวข้อนี้
     * Inject: buildMainPrompt + buildFollowupPrompt (Q1) + buildShortFollowupPrompt (Q2+) + buildGrandFinalePrompt
     */
    /** @param array<int> $onlyPositions จำกัดตำแหน่งไพ่ที่ดึงคลัง (ว่าง = 10 ใบ) */
    protected function buildDestinyDirective(FortuneReading $reading, string $userQuestion, string $previousContext = '', array $onlyPositions = []): string
    {
        if (! (bool) ($this->settings->enable_celtic_destiny ?? true)) {
            return '';
        }

        $svc = app(\App\Services\FortuneKnowledgeService::class);
        $categories = $svc->detectDestinyCategories($this->celticTopicContext($reading, $userQuestion));
        if (empty($categories)) {
            return '';
        }

        $cards = $reading->getCelticCards();
        if (count($cards) < 10) {
            return '';
        }

        $knowledge = $svc->muLinesForCards($cards, $categories, $onlyPositions); // generic per-card (รองรับหมวดดวงจิต)
        if (trim($knowledge) === '') {
            return '';
        }

        return "━━━━━━━━━━━━━━━━━\n"
            ."🔮 ดวงจิต/กรรม รายไพ่: สายญาณ-ผู้มีองค์-ภารกิจสวรรค์ / อดีตชาติ-กรรมเก่า (ตรวจพบคำถามนี้)\n"
            ."━━━━━━━━━━━━━━━━━\n"
            ."ด้านล่างคือความรู้รายไพ่ที่เปิด — อ่านจากไพ่แต่ละใบตามตำแหน่ง:\n\n"
            .$knowledge."\n\n"

            ."⚠️ *จรรยาบรรณเข้ม (ห้ามข้าม — พื้นที่นี้คนชอบมั่ว/ขายของ):*\n"
            ."• *ผู้มีองค์/สายญาณ*: ❌ ห้ามมั่วว่ามีองค์ทุกคน — ไพ่ส่วนใหญ่บอกว่าเป็น \"คนธรรมดา\" ตอบตรงตามไพ่\n"
            ."  ชี้ว่ามีสายญาณ *เฉพาะเมื่อไพ่หนุนจริง* (นักบวช/พระจันทร์/ฤๅษี/ดาว ฯลฯ) พูดแบบ \"อาจไวทางจิต/เหมาะทางธรรม\" ไม่ใช่ \"ต้องรับขันธ์ด่วน\"\n"
            ."  ✅ ย้ำเสมอ: \"สนใจทางธรรม → ปฏิบัติ-ทำบุญเองได้ ไม่ต้องเสียเงินก้อนโต ระวังคนหลอกให้แก้/รับขันธ์ด่วน\"\n"
            ."  🕉️ *แยกให้ออก (สำคัญ):* \"มีเทพอุปถัมภ์ไหม / บูชาองค์ไหนเปิดดวง\" = *ตอบได้ทุกคน* ฟันธงองค์+ใหญ่เล็ก+วิธีบูชา (ดูหมวดองค์เทพ) — กฎ \"คนธรรมดา\" ใช้เฉพาะการ *เป็นร่างทรง/ทรงเจ้า/ต้องรับขันธ์-ครอบครู* เท่านั้น ❌ ห้ามเอามาปัดคำถามเรื่องบูชาเปิดดวง\n"
            ."• *อดีตชาติ/กรรม*: เป็น \"สัญลักษณ์จากไพ่ + บทเรียน\" ไม่ใช่ความจริงตายตัว — ขึ้นต้น \"ไพ่สื่อว่า...\" แล้วโยงมา *ปรับปัจจุบัน*\n"
            ."  ❌ ห้ามขู่เรื่องกรรม/เจ้ากรรมนายเวรเพื่อขายพิธีแก้กรรมแพง — แก้กรรม = ทำดี/ทำบุญ/ให้อภัย ทำเองได้\n"
            ."• ทุกอย่างต้อง \"งอกจากไพ่ใบที่เปิด\" — ❌ ห้ามมั่วว่าเคยเป็นใครเป๊ะ/มีองค์อะไรเป๊ะ ที่ไพ่ไม่ได้ชี้\n"
            ."━━━━━━━━━━━━━━━━━\n\n";
    }

    /**
     * 🧩 (2026-06-02) ความรู้รายไพ่ 10 หมวดเสริม — ต่อยอดคลัง RAG ให้ครบทุกหัวข้อยอดฮิต
     *
     * ความรัก/เนื้อคู่ + การเงิน/โชคลาภ + ฤกษ์ยาม/วันมงคล + เลขศาสตร์/เบอร์มงคล + ของมงคล/เครื่องราง +
     *   จิตใจ/อารมณ์ + ครอบครัว/บุตร/บริวาร + เดินทาง/ต่างแดน + คดีความ/สัญญา + แก้กรรม/เสริมดวง
     *
     * ใช้ retriever ตัวเดียวกับหมวดเดิม ([[App\Services\FortuneKnowledgeService]]::muLinesForCards →
     *   DB fortune_knowledge → config fallback). Detect-based: inject เฉพาะหมวดที่ลูกค้าถาม (keyword)
     *   → ลูกค้าทั่วไปไม่เปลือง token. แต่ละหมวดมี toggle (enable_celtic_*) + จรรยาบรรณเฉพาะหมวด
     *   (คดี→ไม่ใช่ทนาย / แก้กรรม→ทำเองฟรี / จิตใจวิกฤต→1323 / ความรัก→กันสแกม / การเงิน→ไม่เชียร์พนัน)
     *
     * Inject: buildMainPrompt + buildFollowupPrompt (Q1) + buildShortFollowupPrompt (Q2+) + buildGrandFinalePrompt
     */
    /**
     * 🎯 (2026-06-18) Topic context สำหรับ detect-gate ตำราความรู้ (Q2+)
     *
     * = "คำถามปัจจุบัน + คำถามล่าสุด 3 ข้อของลูกค้า" (ไม่ใช่ $previousContext)
     * เหตุผล: $previousContext = คำตอบ AI พื้นดวง Q1 ที่พูดครบทุกด้าน (รัก/งาน/เงิน/สุขภาพ/ฤกษ์)
     *   → ถ้า detect บนนั้น ตำราเกือบทุกหมวดจะ fire ทุก turn = over-fire + prompt บวมบนโมเดลเล็ก
     * ใช้ร่วม buildExtraKnowledgeDirectives + ตำราพี่น้อง (health/mu/life/destiny/yesno/physiognomy/personRole)
     * ยกเว้น buildBlackMagicDirective ที่ต้องเห็น topic Q1 เพื่อ lock รอบ (จึงคง previousContext ไว้)
     */
    protected function celticTopicContext(FortuneReading $reading, string $userQuestion): string
    {
        $priorQuestions = '';
        try {
            $priorQuestions = $reading->celticQuestions()
                ->whereNotNull('answered_at')
                ->orderByDesc('sequence')
                ->limit(3)
                ->pluck('question')
                ->map(function ($q) {
                    // ตัดคำถามสังเคราะห์ (พื้นดวงเปิดตัว) + __PREDICT_ALL__ ออกจาก topic context
                    //   มันพูดทุกด้าน (รัก/งาน/เงิน/สุขภาพ) → ถ้าค้างใน window จะ detect over-fire ทุกหมวด
                    //   🪬 (2026-06-30) รวมคำถามพื้นดวงโหมดคุณไสย์ (celticBaseChartQuestion forced) — กัน over-fire เช่นกัน
                    $q = (string) $q;

                    return (trim($q) === '__PREDICT_ALL__'
                        || mb_strpos($q, 'พื้นดวงรวม') !== false
                        || mb_strpos($q, 'พื้นดวงเรื่อง "ของ') !== false) ? '' : $q;
                })
                ->implode(' ');
        } catch (\Throwable $e) {
            // non-blocking — fallback ใช้คำถามปัจจุบันอย่างเดียว
        }

        return trim($userQuestion.' '.$priorQuestions);
    }

    /**
     * 🚫 (2026-06-18) ห้าม filler ซ้ำ — ใช้ร่วมทุก path (Q1 ถามตรง + Q2+) กัน treatment drift
     *   เดิม ban 2 บรรทัดนี้อยู่แค่ Q2+ → Q1 non-base-chart ยังปิดท้ายด้วย "ดวงชี้ทาง..." + สีมงคล (เคส R7159)
     *   คืนเป็น bullet ต่อใน "🚫 ห้ามทำ" ได้เลย (บรรทัดแรกขึ้น "• ❌" / บรรทัดท้ายไม่มี \n)
     */
    protected function buildAntiFillerBans(): string
    {
        return "• ❌ ห้ามแปะ \"สีมงคล/เลขมงคล/วันมงคล/ทิศ\" ซ้ำท้ายทุกคำตอบ — ใส่เฉพาะเมื่อคำถามเกี่ยวฤกษ์/มงคล หรือลูกค้าถามเอง\n"
            .'• ❌ ห้ามปิดท้ายด้วยวลีกว้างที่ใช้กับใครก็ได้ ("ดวงชี้ทางแต่คนเดินเอง" / "กรรมดีคือที่พึ่ง" / "ทุกอย่างอยู่ที่ใจเรา" / "ขอแค่ตั้งใจ" / "แล้วแต่กรรม") — ปิดด้วยข้อสรุปฟันธงเฉพาะเรื่องนี้';
    }

    /**
     * @param  array<string>  $forceGroups  gate key ของหมวดที่ต้องดึงคลังมาเสมอ แม้ detect จากคำถามไม่เจอ
     *                                      (ใช้กับบทสรุปสุดท้าย ที่มีย่อหน้าเคล็ด/ฤกษ์/เลข ตายตัว)
     * @param  array<int>  $onlyPositions  จำกัดตำแหน่งไพ่ที่ดึงคลัง (ว่าง = 10 ใบ)
     */
    protected function buildExtraKnowledgeDirectives(FortuneReading $reading, string $userQuestion, string $previousContext = '', array $forceGroups = [], array $onlyPositions = []): string
    {
        $cards = $reading->getCelticCards();
        if (count($cards) < 10) {
            return '';
        }

        $svc = app(\App\Services\FortuneKnowledgeService::class);

        // 🎯 (2026-06-13) detect หมวดความรู้จาก "คำถามลูกค้า" (ปัจจุบัน + ย้อนหลัง 3 รอบ = topic ที่กำลังคุย)
        //   เดิมใช้ $previousContext เต็ม — แต่ตอนนี้ที่นั่นมีคำทำนาย Q1 "พื้นดวง" ที่พูดถึง *ทุกด้าน*
        //   (รัก/งาน/เงิน/สุขภาพ/ฤกษ์) → detect fire เกือบทุกหมวดทุก turn = ฉีดความรู้เกินจำเป็น + ไม่โฟกัส + เปลือง token
        //   ใหม่: ใช้ "คำถามลูกค้า" เป็นสัญญาณ topic → โฟกัสเรื่องที่ถามจริง (ตรงกับ "ตอบตามคำถาม")
        //   + ยังจับ follow-up กำกวม ("เขาจะกลับมาไหม" หลังเพิ่งถามความรัก) ผ่าน topic continuity โดยไม่เหวี่ยงแหทุกหมวด
        $ctx = $this->celticTopicContext($reading, $userQuestion);

        // 10 หมวดเสริม — [toggle key, detect method, ไอคอน+ชื่อหมวด, จรรยาบรรณเฉพาะหมวด]
        $groups = [
            ['enable_celtic_love', 'detectLoveCategories', '❤️ ความรัก/เนื้อคู่',
                "• ทำนายตามไพ่ตรงๆ — ❌ ห้ามการันตี \"คนรักกลับมาแน่/ได้แต่งแน่\" หรือให้ความหวังลมๆ\n"
                .'• ❌ ห้ามแนะของมัดใจ/เสน่ห์/พิธีเรียกคนรักราคาแพง — เน้นปรับตัว/สื่อสาร/ดูแลใจตนเองที่ทำได้จริง'],
            ['enable_celtic_wealth', 'detectWealthCategories', '💰 การเงิน/โชคลาภ',
                "• เตือนกระแสเงิน/หนี้/จังหวะลงทุนตามไพ่ตามจริง — ❌ ห้ามเชียร์พนัน/หวย/ลงทุนเสี่ยงเกินตัว\n"
                .'• โชคลาภ = "แนวโน้ม" ไม่ใช่การันตีถูกรางวัล — เน้นวินัยการเงิน/ลงมือหาเพิ่มเป็นหลัก'],
            ['enable_celtic_auspicious', 'detectAuspiciousCategories', '📅 ฤกษ์ยาม/วันมงคล',
                '• ฤกษ์จากไพ่ = "ช่วงจังหวะที่หนุน" (อ่านจากตำแหน่ง+ธาตุไพ่) ไม่ใช่วัน-เวลาเป๊ะ — ❌ ห้ามมั่ววันเป๊ะถ้าไพ่ไม่ได้ชี้'],
            ['enable_celtic_numerology', 'detectNumerologyCategories', '🔢 เลขศาสตร์/เบอร์มงคล',
                '• เลข/เบอร์ = ตัวเสริมพลังตามไพ่ ไม่ใช่ตัวชี้ชะตา — ❌ ห้ามขายความกลัว "เบอร์นี้พังชีวิต" หรือบังคับเปลี่ยนเบอร์'],
            ['enable_celtic_lucky_items', 'detectLuckyItemsCategories', '🧿 ของมงคล/สีมงคล/เครื่องราง',
                '• เน้นของ/สีที่หาได้-ทำเองได้ตามไพ่ — ❌ ห้ามเชียร์เครื่องราง/วัตถุมงคลราคาแพงหรือบังคับซื้อ'],
            ['enable_celtic_mental', 'detectMentalCategories', '🧠 จิตใจ/อารมณ์',
                "• อ่านอารมณ์/ความเครียดตามไพ่อย่างอ่อนโยน ดึงสติ ให้กำลังใจ\n"
                .'• 🚨 ถ้าไพ่+คำถามชี้วิกฤต (คิดทำร้ายตัวเอง/ซึมหนัก/หมดหวัง) → ชวนหาคนรับฟัง/สายด่วนใจ 1323 (มีเพื่อนคุย 24 ชม. ฟรี) ทันที อย่างอ่อนโยน ไม่ตราหน้าว่าป่วย'],
            ['enable_celtic_family', 'detectFamilyCategories', '👨‍👩‍👧 ครอบครัว/บุตร/บริวาร',
                "• เรื่องคนในครอบครัว/บุตร = อ่านจากไพ่ตำแหน่งที่ตรงกับบุคคลนั้น\n"
                .'• เรื่องมีบุตร/มีบุตรยาก = "แนวโน้มจากไพ่" ❌ ห้ามฟันธงแทนแพทย์ — มีปัญหาจริงให้แนะปรึกษาแพทย์'],
            ['enable_celtic_travel', 'detectTravelCategories', '✈️ เดินทาง/ต่างแดน/ย้ายถิ่น',
                '• ทริป/ย้ายถิ่น/ทำงานต่างแดน = แนวโน้มจากไพ่ — เรื่องวีซ่า/เอกสาร ❌ ห้ามการันตีผลอนุมัติ ให้ทำตามระเบียบจริงควบคู่'],
            ['enable_celtic_legal', 'detectLegalCategories', '⚖️ คดีความ/ข้อพิพาท/สัญญา',
                "• ❗ ไพ่ชี้ \"แนวโน้ม/ท่าที\" เท่านั้น — *ไม่ใช่คำปรึกษากฎหมาย* ❌ ห้ามการันตีแพ้/ชนะคดี\n"
                .'• เรื่องคดี/สัญญาจริง ให้แนะปรึกษาทนาย/ผู้รู้กฎหมายควบคู่เสมอ'],
            ['enable_celtic_remedy', 'detectRemedyCategories', '🪷 แก้กรรม/สะเดาะเคราะห์/เสริมดวง',
                "• แก้กรรม/เสริมดวง = ทำดี/ทำบุญ/ให้อภัย/ปรับการกระทำ — *ทำเองได้ฟรี*\n"
                .'• ❌ ห้ามขู่เรื่องกรรม/เจ้ากรรมนายเวร หรือขายพิธีแก้กรรม-สะเดาะเคราะห์ราคาแพงเด็ดขาด'],
        ];

        // 🧭 (2026-08-07) คลังสำรองสำหรับหมวดที่ "บทสรุปสุดท้ายต้องใช้เสมอ" แม้ลูกค้าไม่เคยถาม
        //   บทสรุปมีย่อหน้า "เคล็ด/ฤกษ์/เลข/สีมงคล/ทิศ" ตายตัว (owner directive 2026-06-28 ย้ายของท้ายมารวมที่นี่)
        //   แต่ detect ยิงจาก "คำถามลูกค้า" — คนที่ถามแต่เรื่องความรักจะไม่ทริกเกอร์หมวดพวกนี้เลย
        //   → ย่อหน้านั้นถูกเขียนโดยไม่มีคลังรองรับ = AI มโนเลข/สี/ฤกษ์เอง (ดู DailyAstroBrief บทเรียนเดียวกัน)
        $forceFallback = [
            'enable_celtic_auspicious' => \App\Services\FortuneKnowledgeService::AUSPICIOUS_DETECTABLE,
            'enable_celtic_numerology' => \App\Services\FortuneKnowledgeService::NUMEROLOGY_DETECTABLE,
            'enable_celtic_lucky_items' => \App\Services\FortuneKnowledgeService::LUCKY_ITEMS_DETECTABLE,
            'enable_celtic_remedy' => \App\Services\FortuneKnowledgeService::REMEDY_DETECTABLE,
        ];

        $blocks = [];
        foreach ($groups as [$gate, $detectMethod, $heading, $ethics]) {
            // toggle gate — admin ปิดรายหมวดได้ (default เปิด ถ้าคอลัมน์ยังไม่มีก็ถือว่าเปิด)
            //   ⚠️ gate ปิด = ปิดจริงแม้อยู่ใน forceGroups (แอดมินสั่งปิดต้องชนะเสมอ)
            if (! (bool) ($this->settings->{$gate} ?? true)) {
                continue;
            }

            $categories = $svc->{$detectMethod}($ctx);
            $wasDetected = ! empty($categories);

            // detect ไม่เจอ แต่ caller บังคับหมวดนี้ (บทสรุปสุดท้าย) → ใช้คลังของหมวดนั้นตรงๆ
            if (! $wasDetected && in_array($gate, $forceGroups, true) && isset($forceFallback[$gate])) {
                $categories = $forceFallback[$gate];
            }

            if (empty($categories)) {
                continue;
            }

            $knowledge = $svc->muLinesForCards($cards, $categories, $onlyPositions);
            if (trim($knowledge) === '') {
                continue;
            }

            $why = $wasDetected ? 'ตรวจพบคำถามหมวดนี้' : 'ใช้เขียนย่อหน้าเคล็ด/ฤกษ์/เลข ท้ายบทสรุป';
            $blocks[] = "▸ {$heading} ({$why})\n"
                .$knowledge."\n"
                ."*จรรยาบรรณหมวดนี้:*\n".$ethics;
        }

        if (empty($blocks)) {
            return '';
        }

        return "━━━━━━━━━━━━━━━━━\n"
            ."🧩 คลังความรู้รายไพ่ (หมวดที่ลูกค้าถาม) — ใช้ \"ประกอบการอ่านไพ่\" ให้เจาะจง ไม่ตอบกว้าง\n"
            ."━━━━━━━━━━━━━━━━━\n"
            .'🎯 *card-first:* อ่านจากไพ่ที่เปิด (ใบไหน/ตำแหน่งไหน/ธาตุ/ตั้งตรง-กลับหัว) ก่อน → แล้วดึงความรู้ด้านล่างมาเสริมให้ตรง '
            ."ทุกคำแนะนำต้อง \"งอกจากไพ่\" + actionable (ลูกค้าจ่าย 99฿ ต้องได้คำเฉพาะ ไม่ใช่ \"ทำบุญเยอะๆ\")\n\n"
            .implode("\n\n", $blocks)."\n"
            ."━━━━━━━━━━━━━━━━━\n\n";
    }

    /**
     * 🔗 (2026-06-02) ไพ่คู่/ไพ่สัมพันธ์ — กลไก "เชื่อมโยงไพ่" ที่ prompt สั่งแต่เดิมไม่มีคลังรองรับ
     *
     * หมอดูจริงอ่าน "ความสัมพันธ์ระหว่างไพ่" ไม่ใช่ทีละใบ — เช่น หอคอย+ดาบ10 = จบเจ็บ,
     *   คู่รัก+ถ้วย2 = เนื้อคู่, ปีศาจ+พระจันทร์ = โดนหลอก/เสพติด
     *
     * ไม่ใช่ detect-based (ไม่ผูก keyword) — คู่ไพ่เกี่ยวกับ "หน้าไพ่ที่เปิด" เสมอ
     *   inject เฉพาะเมื่อ "เจอคู่เด่นจริงบนโต๊ะ" → ไม่เปลือง token ถ้าไม่มีคู่
     *
     * Inject: buildMainPrompt + buildFollowupPrompt (Q1) + buildShortFollowupPrompt (Q2+) + buildGrandFinalePrompt
     */
    protected function buildCardComboDirective(FortuneReading $reading): string
    {
        // settings gate — admin ปิดได้ (default เปิด ถ้าคอลัมน์ยังไม่มีก็ถือว่าเปิด)
        if (! (bool) ($this->settings->enable_celtic_combos ?? true)) {
            return '';
        }

        $cards = $reading->getCelticCards();
        if (count($cards) < 10) {
            return '';
        }

        $combos = app(\App\Services\FortuneKnowledgeService::class)->comboLinesForCards($cards);
        if (trim($combos) === '') {
            return '';
        }

        return "━━━━━━━━━━━━━━━━━\n"
            ."🔗 ไพ่คู่/ไพ่สัมพันธ์ (พบคู่ไพ่เด่นบนโต๊ะ) — \"เชื่อมโยงไพ่\" ตามที่หลักการสั่ง\n"
            ."━━━━━━━━━━━━━━━━━\n"
            .'ด้านล่างคือคู่ไพ่ที่ "ออกด้วยกัน" ในสำรับนี้ มีความหมายพิเศษเมื่อมาเจอกัน — '
            ."ใช้ \"ร้อยเรื่องราว\" ไม่ใช่แปลทีละใบ:\n\n"
            .$combos."\n\n"
            .'🎯 *วิธีใช้:* หยิบคู่ที่ตรงกับคำถามลูกค้ามา "เน้นเป็นแกนคำทำนาย" — '
            ."เล่าว่าพลัง 2 ใบนี้มาเจอกันแล้วเกิดอะไร เชื่อมกับตำแหน่งที่มันอยู่\n"
            ."• คู่ ✨ = จุดแข็ง/โอกาสที่ควรย้ำ · คู่ ⚠️/⚠️⚠️ = จุดเตือน/บทเรียน (สื่อ \"เปลี่ยนผ่าน\" ไม่ใช่ขู่)\n"
            ."• ❌ ห้ามยกคู่ไพ่ที่ไม่เกี่ยวคำถามมาทื่อๆ — เลือกที่ \"ตอบโจทย์เขา\"\n"
            ."━━━━━━━━━━━━━━━━━\n\n";
    }

    /**
     * 🎴 (2026-06-02) อ่านภาพรวมสำรับ — ดูบรรยากาศ 10 ใบก่อนเจาะรายใบ (reading mechanic)
     *
     * หมอดูจริงดู "ภาพใหญ่" ก่อน: Major เยอะ = พรหมลิขิต/เรื่องใหญ่, สำรับเด่น = ธีม,
     *   กลับหัวเยอะ = ติดขัด/ภายใน, ราชสำนักเยอะ = คนหลายคน, Ace หลายใบ = เริ่มใหม่
     *
     * ไม่ผูก keyword — ภาพรวมมีในทุกสำรับเสมอ (ออกเสมอถ้า toggle เปิด + ครบ 10 ใบ)
     *
     * Inject: buildMainPrompt + buildFollowupPrompt (Q1) + buildShortFollowupPrompt (Q2+) + buildGrandFinalePrompt
     */
    protected function buildSpreadPatternDirective(FortuneReading $reading): string
    {
        // settings gate — admin ปิดได้ (default เปิด)
        if (! (bool) ($this->settings->enable_celtic_patterns ?? true)) {
            return '';
        }

        $cards = $reading->getCelticCards();
        if (count($cards) < 10) {
            return '';
        }

        $patterns = app(\App\Services\FortuneKnowledgeService::class)->spreadPatternLines($cards);
        if (trim($patterns) === '') {
            return '';
        }

        return "━━━━━━━━━━━━━━━━━\n"
            ."🎴 ภาพรวมสำรับ (อ่านบรรยากาศ 10 ใบ ก่อนเจาะรายใบ)\n"
            ."━━━━━━━━━━━━━━━━━\n"
            ."สถิติสำรับนี้บ่งทิศทาง \"พลังรวม\" ของดวง — ใช้ \"ตั้งโทน\" คำทำนายทั้งบท:\n\n"
            .$patterns."\n\n"
            .'🎯 *วิธีใช้:* เปิดคำทำนายด้วย "ภาพรวม" นี้ก่อน (เช่น "สำรับนี้ไพ่ชุดใหญ่เด่น '
            ."บอกว่าเรื่องนี้เป็นจังหวะใหญ่ของชีวิต...\") แล้วค่อยเจาะรายใบ/ตำแหน่งให้สอดคล้องโทนรวม\n"
            ."• ❌ ห้ามอ่านภาพรวมแล้วขัดกับรายใบ — ต้อง \"ร้อยให้เป็นเรื่องเดียวกัน\"\n"
            ."• ภาพรวม = บรรยากาศ/ทิศทาง ไม่ใช่คำฟันธง (กลับหัวเยอะ/Major เยอะ = ช่วงเปลี่ยนผ่าน ไม่ใช่ลางร้าย)\n"
            ."━━━━━━━━━━━━━━━━━\n\n";
    }

    /**
     * 🔥💧 (2026-06-03) ธาตุเสริม-ขัด (Elemental Dignities — Golden Dawn)
     *
     * reading mechanic ตัวที่ 3 — ตำรา Golden Dawn: ไพ่ที่อยู่คู่กัน "เสริม-หักล้าง"
     *   กันด้วยธาตุ (ไฟ-ลม/น้ำ-ดิน เสริม · ไฟ-น้ำ/ลม-ดิน ขัด · ธาตุเดียวกัน ทวีคูณ)
     *
     * คำนวณคู่ตำแหน่งสำคัญ (1↔2, 3↔6, 4↔5, 7↔8, 9↔10) + สรุปสำรับ
     * ไม่ผูก keyword — ธาตุมีอยู่ในทุกสำรับ (ออกเสมอเมื่อ toggle เปิด + ครบ 10 ใบ)
     *
     * Inject: buildMainPrompt + buildFollowupPrompt (Q1) + buildShortFollowupPrompt (Q2+) + buildGrandFinalePrompt
     */
    protected function buildElementalDignityDirective(FortuneReading $reading): string
    {
        // settings gate — admin ปิดได้ (default เปิด)
        if (! (bool) ($this->settings->enable_celtic_dignity ?? true)) {
            return '';
        }

        $cards = $reading->getCelticCards();
        if (count($cards) < 10) {
            return '';
        }

        $lines = app(\App\Services\FortuneKnowledgeService::class)->elementalDignityLines($cards);
        if (trim($lines) === '') {
            return '';
        }

        return "━━━━━━━━━━━━━━━━━\n"
            ."🔥💧 ธาตุเสริม-ขัด (Elemental Dignities — ตำรา Golden Dawn)\n"
            ."━━━━━━━━━━━━━━━━━\n"
            ."ไพ่ที่อยู่คู่กัน \"เสริม-หักล้าง\" กันด้วยธาตุ — บอกว่าพลังในเรื่องนี้ \"ไหลลื่น\" หรือ \"ปะทะกัน\":\n\n"
            .$lines."\n\n"
            ."🎯 *วิธีใช้:* นี่คือ \"แผนภาพพลัง\" เบื้องหลัง — เอาไปประกอบคำทำนายว่า\n"
            ."• คู่ ✨ เสริม = หยิบมาเล่าได้ว่า \"พลังนี้หนุนกัน เรื่องจะลื่นไหลทางนี้\"\n"
            ."• คู่ ⚡ ขัด = สะท้อนความตึง/ทางเลือก/ต้องประนีประนอม — แม่หมอเล่าให้เห็น \"จุดที่บีบ\"\n"
            ."• คู่ 🔁 เหมือนกัน = ทวีคูณ-พุ่งทางเดียว ไม่มีตัวถ่วง (ดี/ร้ายแล้วแต่ธาตุนั้น)\n"
            ."• ❌ ห้ามใช้คำว่า \"ขัด\" แบบขู่ — สื่อ \"ความตึง/บทเรียน\" เชิงสร้างสรรค์\n"
            ."━━━━━━━━━━━━━━━━━\n\n";
    }

    /**
     * 📍 (2026-06-03) ความสัมพันธ์ตำแหน่ง Celtic (Position Dynamics — diagnostic pairs)
     *
     * reading mechanic ตัวที่ 4 — หมอดูไม่ได้อ่านตำแหน่งทีละช่อง แต่อ่าน "คู่ตำแหน่ง"
     *   เช่น ต.5 (หวัง) vs ต.7 (ตัวเอง) → ตรงกันไหม / ต.9 vs ต.10 → หวังจริงไหม
     *
     * เลเยอร์นี้ไม่ฟันธงเอง — ส่ง "ชุดคำถาม diagnostic + เคล็ดวิเคราะห์" ให้ AI
     *   เพื่อให้สังเคราะห์เอง (เหมือนหมอดูเก่าๆ ที่อ่านโดยถามตัวเอง)
     *
     * Inject: buildMainPrompt + buildFollowupPrompt (Q1) + buildShortFollowupPrompt (Q2+) + buildGrandFinalePrompt
     */
    protected function buildPositionDynamicDirective(FortuneReading $reading): string
    {
        if (! (bool) ($this->settings->enable_celtic_dynamics ?? true)) {
            return '';
        }

        $cards = $reading->getCelticCards();
        if (count($cards) < 10) {
            return '';
        }

        $lines = app(\App\Services\FortuneKnowledgeService::class)->positionDynamicLines($cards);
        if (trim($lines) === '') {
            return '';
        }

        return "━━━━━━━━━━━━━━━━━\n"
            ."📍 ความสัมพันธ์ตำแหน่ง Celtic (Diagnostic Pairs)\n"
            ."━━━━━━━━━━━━━━━━━\n"
            ."หมอดูเก่าๆ ไม่อ่านตำแหน่งทีละช่อง แต่อ่าน \"คู่ตำแหน่ง\" — ด้านล่างคือ\n"
            ."\"ชุดคำถามวิเคราะห์ + เคล็ดอ่าน\" ที่แม่หมอควรตอบให้ครบ:\n\n"
            .$lines."\n\n"
            ."🎯 *วิธีใช้:* แม่หมอ \"ถามตัวเอง\" แต่ละคู่ก่อน แล้วเอาคำตอบมาประกอบเป็นคำทำนาย\n"
            ."• โดยเฉพาะคู่ ต.9↔ต.10 (หวัง→ผลลัพธ์) และ ต.5↔ต.6 (อดีต→อนาคต) — ใช้ในการ \"ฟันธง\" ปลายเรื่อง\n"
            ."• คู่ ต.7↔ต.8 (เรา-คนรอบ) ใช้เวลาเรื่องเป็นความสัมพันธ์ — ชี้จุดที่ \"คนละมุม\"\n"
            ."• ❌ ห้ามอ่านตำแหน่งเป็นเอกเทศแล้วลืม \"คู่\" — เพราะตำแหน่งต่างๆ เชื่อมเป็นเรื่องเดียวกัน\n"
            ."━━━━━━━━━━━━━━━━━\n\n";
    }

    /**
     * 🎯 (2026-06-03) น้ำหนัก Yes/No — ฟันธงคำถามใช่/ไม่ใช่
     *
     * reading mechanic ตัวที่ 5 (เลเยอร์สุดท้าย) — คำนวณคะแนน + ตัดสิน
     *   detect "คำถาม yes/no" จากข้อความก่อน inject (ไม่งั้นจะ inject ทุกเซสชัน)
     *
     * คะแนน: รายไพ่ +/- × ตัวคูณตำแหน่ง (ต.10 ×2.5, ต.6 ×2.0 สำคัญสุด)
     *   กลับหัว = พลิกสัญลักษณ์ (Tower กลับหัว = ลบน้อยลง, Sun กลับหัว = บวกน้อยลง)
     *
     * Inject: buildMainPrompt + buildFollowupPrompt (Q1) + buildShortFollowupPrompt (Q2+) + buildGrandFinalePrompt
     */
    protected function buildYesNoDirective(FortuneReading $reading, string $userQuestion, string $previousContext = ''): string
    {
        if (! (bool) ($this->settings->enable_celtic_yesno ?? true)) {
            return '';
        }

        // Detect คำถาม yes/no (ภาษาไทย — \b ใน regex ไม่ทำงานกับไทย ใช้ mb_strpos ตรงๆ)
        $haystack = mb_strtolower($this->celticTopicContext($reading, $userQuestion));
        if ($haystack === '') {
            return '';
        }
        // คำชี้ Yes/No ที่พบบ่อย (เรียงจากเฉพาะเจาะจง → ทั่วไป)
        $signals = [
            'หรือเปล่า', 'รึเปล่า', 'หรือไม่', 'ใช่ไหม', 'ใช่มั้ย', 'ใช่หรือ',
            'ได้ไหม', 'ได้มั้ย', 'ดีไหม', 'ดีมั้ย', 'ควรไหม', 'ควรมั้ย',
            'เขาจะ', 'เค้าจะ', 'เธอจะ', 'จะกลับ', 'จะรัก', 'จะแต่ง', 'จะเลิก',
            'จะได้', 'จะรวย', 'จะติด', 'จะผ่าน', 'จะมา', 'จะเป็น',
            // อันนี้กว้างสุด — วางท้าย
            'ไหม', 'มั้ย', 'หรือ',
        ];
        $hit = false;
        foreach ($signals as $kw) {
            if (mb_strpos($haystack, $kw) !== false) {
                $hit = true;
                break;
            }
        }
        if (! $hit) {
            return '';
        }

        $cards = $reading->getCelticCards();
        if (count($cards) < 10) {
            return '';
        }

        $block = app(\App\Services\FortuneKnowledgeService::class)->yesNoVerdict($cards);
        if (trim($block) === '') {
            return '';
        }

        return "━━━━━━━━━━━━━━━━━\n"
            ."🎯 น้ำหนัก Yes/No (ตรวจพบคำถามใช่/ไม่ใช่) — เลขฟันธงเชิงสถิติ\n"
            ."━━━━━━━━━━━━━━━━━\n"
            ."ระบบคำนวณ \"น้ำหนักดี/ร้ายรายไพ่ × ตัวคูณตำแหน่ง\" (ต.10 ผลลัพธ์ ×2.5, ต.6 อนาคต ×2.0):\n\n"
            .$block."\n\n"
            ."🎯 *วิธีใช้:* นี่คือ \"เข็มทิศ\" สำหรับฟันธง — ผลรวมบอกแนวโน้มชัด\n"
            ."• ✅/🟢 = ฟันธงทางบวก · 🟡 = เลือกข้างที่ไพ่เอนไป (ดูต.6/10) แล้วฟันธงข้างนั้น · 🔶/🔴 = ฟันธงทางลบ\n"
            ."• ❌ ห้ามตอบเลขดิบ (ลูกค้าไม่ต้องการเห็นคะแนน) — แปลเป็นภาษาคน เช่น \"ใช่ค่ะ ไพ่หนุน\" หรือ \"ไม่ค่ะ ยังไม่ใช่จังหวะ\"\n"
            ."• ใช้ \"ฟันธงตอนท้าย\" เป็นหลัก — เนื้อทำนายยังอ่านจากไพ่ตามปกติ\n"
            ."• 🎯 *ฟันธงนำเสมอ* แม้ก้ำกึ่งก็ต้องชี้ว่าไพ่เอนทางไหน — ห้ามตอบลอยว่า \"ขึ้นกับตัวเอง/แล้วแต่เจ้าชะตา\"\n"
            ."• 🚫 *ห้ามตอบแบบ \"ได้ แต่...\" ที่กั๊กจนลบล้างคำฟันธงตัวเอง* (\"ได้ แต่ก็ไม่แน่นอน/ขึ้นกับหลายอย่าง\" = ห้าม)\n"
            ."   ถ้าจะมี \"แต่\" ต้องเป็น *เงื่อนไขที่ทำให้สำเร็จ* หรือ *ช่วงเวลา* เท่านั้น:\n"
            ."   ✅ \"ได้ — ถ้าทำ X\" / \"ได้ — ภายใน Y\" / \"ยังไม่ได้ตอนนี้ แต่ช่วง Z ไพ่จะพลิกเป็นได้\"\n"
            ."• ⚠️ ถ้าผลขัดกับคำตอบที่ได้จากเลเยอร์อื่น → ให้น้ำหนักไพ่ในตำแหน่ง 6 และ 10 มากกว่า\n"
            ."━━━━━━━━━━━━━━━━━\n\n";
    }

    /**
     * 👤 (2026-06-01) ตำราโหงวเฮ้ง/ลักษณะคน ประจำไพ่ — อ่าน "คน" จากหน้าไพ่
     *
     * User directive 2026-06-01: "เพิ่มตำราโหงวเฮ้ง ลักษณะคน ประจำไพ่ให้ครบ 78 ใบ"
     *
     * ใช้เมื่อลูกค้าถามถึง "คน" — เขาเป็นใคร/หน้าตา-นิสัยแบบไหน/เนื้อคู่/คู่กรณี/คนที่จะมา
     *   → ดึงลักษณะคนประจำไพ่ที่เปิด (คลัง RAG: DB→config) มาให้แม่หมออ่านคนจากไพ่
     *   ผูกกับโหมดนักสืบ "คิดออกไหมว่าใคร" (ดู [[buildCardFirstMandate]] 🔎)
     *
     * Detect-based: inject เฉพาะ session ที่ถามเรื่องคน → ลูกค้าทั่วไปไม่เปลือง token
     *
     * Inject: buildMainPrompt + buildFollowupPrompt (Q1) + buildShortFollowupPrompt (Q2+) + buildGrandFinalePrompt
     */
    protected function buildPhysiognomyDirective(FortuneReading $reading, string $userQuestion, string $previousContext = ''): string
    {
        // settings gate — admin ปิดได้
        if (! (bool) ($this->settings->enable_celtic_physiognomy ?? true)) {
            return '';
        }

        // Detect: ถามเรื่อง "คน" ไหม
        $haystack = mb_strtolower($this->celticTopicContext($reading, $userQuestion));
        $keywords = [
            'เป็นคนยังไง', 'เป็นคนแบบไหน', 'คนยังไง', 'คนแบบไหน', 'นิสัย', 'อุปนิสัย', 'หน้าตา',
            'รูปร่าง', 'ลักษณะ', 'โหงวเฮ้ง', 'ดูคน', 'เขาเป็นคน', 'นิสัยใจคอ', 'เนื้อคู่', 'คู่แท้',
            'คนที่จะมา', 'คนที่ชอบ', 'คนที่คุย', 'มือที่สาม', 'คู่กรณี', 'แฟนเป็นคน', 'เขาหน้าตา',
            'คนรอบตัว', 'อีกฝ่ายเป็นคน', 'เขานิสัย', 'ดูนิสัย', 'ดูลักษณะ',
        ];
        $hit = false;
        foreach ($keywords as $kw) {
            if (mb_strpos($haystack, $kw) !== false) {
                $hit = true;
                break;
            }
        }
        if (! $hit) {
            return '';
        }

        $cards = $reading->getCelticCards();
        if (count($cards) < 10) {
            return '';
        }

        $block = app(\App\Services\FortuneKnowledgeService::class)->physiognomyLinesForCards($cards);
        if (trim($block) === '') {
            return '';
        }

        return "━━━━━━━━━━━━━━━━━\n"
            ."👤 ตำราโหงวเฮ้ง/ลักษณะคน ประจำไพ่ (ตรวจพบคำถามเกี่ยวกับ \"คน\")\n"
            ."━━━━━━━━━━━━━━━━━\n"
            ."ด้านล่างคือ \"รูปลักษณ์/โหงวเฮ้ง + นิสัย\" ที่ไพ่แต่ละใบที่เปิดบ่งถึง — ใช้อ่าน \"คน\" ตามหน้าไพ่:\n\n"
            .$block."\n\n"

            ."🎯 *วิธีอ่านคน (card-first — ตามหน้าไพ่ ไม่มั่ว):*\n"
            ."• อ่าน \"คน\" จากไพ่ในตำแหน่งที่ตรงกับบุคคลนั้น — ตัวเจ้าชะตา / สิ่งแวดล้อม (คนรอบตัว) / ความหวัง-กลัว / ผลลัพธ์ หรือไพ่ราชสำนัก (ข้าราชบริพาร/อัศวิน/ราชินี/กษัตริย์ = คนจริง)\n"
            ."• ฟันธงลักษณะเด่น: เพศ/วัย (ราชสำนัก) + รูปร่าง-ผิว-หน้า (สำรับ/ธาตุ) + นิสัยเด่น (ความหมายไพ่) + ตั้งตรง/กลับหัว (ด้านดี/ด้านลบ)\n"
            ."  ❌ ห้ามตอบกว้างๆ \"เป็นคนดี/นิสัยดี\" — ต้องเฉพาะตามไพ่ (เช่น \"ชายผิวเข้ม รูปร่างล่ำ หนักแน่นแต่ดื้อ\")\n"
            ."• 🔎 *โหมดนักสืบ (สำคัญ)*: ทำนายลักษณะคนแล้ว → ชวนเจ้าชะตายืนยัน \"ลักษณะแบบนี้ คิดออกไหมว่าเป็นใคร?\" ([TYPE:D] ไม่นับ) แล้วเจาะให้ตรงตัวขึ้น\n"
            ."• ⚠️ ชี้ \"ลักษณะ/แนวโน้ม\" ตามไพ่ — *ห้ามมั่วชื่อจริง/หน้าตาเป๊ะ 100%* (ไพ่บอกแนวคน ไม่ใช่บัตรประชาชน)\n"
            ."━━━━━━━━━━━━━━━━━\n\n";
    }

    /**
     * 🧑‍🤝‍🧑 (2026-06-17) ตำราตำแหน่งบุคคล — ระบุว่าไพ่ใบไหน = "ใคร" ในชีวิตเจ้าชะตา
     *
     * User directive: "เพิ่มหมวดตำแหน่งบุคคล (พ่อ/แม่/พี่/ป้า/น้า/อา/น้อง/เพื่อน/ผู้อุปถัมภ์...)
     *   เพื่อใช้ระบุว่าเป็นใคร เมื่อต้องกล่าวถึงในคำถาม"
     *
     * ต่างจาก physiognomy (หน้าตา/นิสัย) — หมวดนี้ตอบ "เป็นใคร/ความสัมพันธ์/ตำแหน่งในชีวิต"
     *   detect เมื่อคำถามเอ่ยถึงตัวบุคคล (เครือญาติ/สังคม) หรือถามว่า "ใครคือ..."
     *   → ดึง role tome รายไพ่ 10 ใบ → อ่าน card-first ว่าไพ่ตำแหน่งไหน = บุคคลใด
     *
     * gate: enable_celtic_person_role (default on)
     */
    protected function buildPersonRoleDirective(FortuneReading $reading, string $userQuestion, string $previousContext = ''): string
    {
        // settings gate — admin ปิดได้
        if (! (bool) ($this->settings->enable_celtic_person_role ?? true)) {
            return '';
        }

        // Detect: คำถามเอ่ยถึง "ตัวบุคคล" (เครือญาติ/สังคม) หรือถาม "เป็นใคร" ไหม
        //   ⚠️ เลี่ยงคำพยางค์เดียวที่เป็น substring คำอื่น (น้า=หน้า, อา=เอา/อาหาร, ตา=ตาม, ย่า=อย่าง)
        //   ⚠️ ตัดชื่อแม่หมอออกก่อน — กัน 'แม่' bare ไป match "แม่หมอ" (ลูกค้าทักบ่อยมาก) = over-fire
        $haystack = mb_strtolower($this->celticTopicContext($reading, $userQuestion));
        $haystack = str_replace(['แม่หมอดู', 'แม่หมอ', 'พ่อหมอ'], ' ', $haystack);
        $keywords = [
            // เครือญาติ
            'พ่อ', 'แม่', 'ลูกชาย', 'ลูกสาว', 'พี่ชาย', 'พี่สาว', 'น้องชาย', 'น้องสาว', 'พี่น้อง',
            'พี่', 'น้อง', 'หลาน', 'ญาติ', 'พ่อแม่', 'ครอบครัว', 'คนในบ้าน', 'คนในครอบครัว',
            'คุณพ่อ', 'คุณแม่', 'คุณป้า', 'คุณน้า', 'คุณอา', 'คุณลุง', 'ลุง', 'คุณตา', 'คุณยาย', 'ยาย', 'ปู่', 'คุณปู่', 'คุณย่า',
            'ลูกพี่ลูกน้อง',
            // คู่/สังคม/งาน
            'สามี', 'ภรรยา', 'เมีย', 'แฟน', 'เนื้อคู่', 'คู่กรณี', 'มือที่สาม',
            'เพื่อน', 'เจ้านาย', 'หัวหน้า', 'ลูกน้อง', 'หุ้นส่วน', 'นายทุน', 'เจ้าหนี้', 'ลูกหนี้',
            'คนอุปถัมภ์', 'ผู้อุปถัมภ์', 'ผู้มีพระคุณ', 'ผู้สนับสนุน',
            // ถาม "เป็นใคร"
            'เป็นใคร', 'ใครคือ', 'คือใคร', 'หมายถึงใคร', 'ใครกันแน่', 'คนนี้คือ', 'ระบุว่าเป็นใคร',
            'ตำแหน่งบุคคล', 'ใครในชีวิต', 'ไพ่ใบไหนคือ', 'ใบไหนคือ',
        ];
        $hit = false;
        foreach ($keywords as $kw) {
            if (mb_strpos($haystack, $kw) !== false) {
                $hit = true;
                break;
            }
        }
        if (! $hit) {
            return '';
        }

        $cards = $reading->getCelticCards();
        if (count($cards) < 10) {
            return '';
        }

        $block = app(\App\Services\FortuneKnowledgeService::class)->personRoleLinesForCards($cards);
        if (trim($block) === '') {
            return '';
        }

        return "━━━━━━━━━━━━━━━━━\n"
            ."🧑‍🤝‍🧑 ตำราตำแหน่งบุคคล ประจำไพ่ (ตรวจพบคำถามที่เอ่ยถึง \"ตัวบุคคล\")\n"
            ."━━━━━━━━━━━━━━━━━\n"
            ."ด้านล่างคือ \"ตำแหน่งบุคคล/ความสัมพันธ์\" ที่ไพ่แต่ละใบที่เปิดมักบ่งถึง — ใช้ \"ระบุว่าใคร\" ตามหน้าไพ่:\n\n"
            .$block."\n\n"

            ."🎯 *วิธีระบุตัวบุคคล (card-first — ตามหน้าไพ่ + ตำแหน่ง ไม่มั่ว):*\n"
            ."• จับคู่ \"คนที่ลูกค้าเอ่ยถึง\" กับไพ่ในตำแหน่งที่ตรง — ตัวเจ้าชะตา(ต.1) / สิ่งแวดล้อม-คนรอบข้าง(ต.7?) / ความหวัง-กลัว(ต.9) / ผลลัพธ์(ต.10) หรือไพ่ราชสำนัก (ข้าราชบริพาร=เด็ก/อัศวิน=ชายหนุ่ม/ราชินี=หญิงผู้ใหญ่/กษัตริย์=ชายผู้ใหญ่ = ตัวบุคคลจริง)\n"
            ."• เพศ/วัย: ราชสำนัก+สำรับช่วยชี้ (เช่น กษัตริย์เหรียญ=ชายผู้ใหญ่มีฐานะ→พ่อ/เจ้านาย/ผู้อุปถัมภ์ ; ราชินีถ้วย=หญิงผู้ใหญ่ใจดี→แม่/คนรัก)\n"
            ."  ❌ ห้ามตอบกว้างๆ \"คนรอบตัวคุณ\" — ต้องเจาะตามไพ่ (เช่น \"ไพ่ราชาเหรียญในตำแหน่งคนรอบข้าง = ผู้ใหญ่ชายมีฐานะ น่าจะเป็นพ่อหรือเจ้านายที่อุปถัมภ์\")\n"
            ."• 🔎 *โหมดนักสืบ*: ระบุตำแหน่งบุคคลแล้ว → ชวนเจ้าชะตายืนยัน \"ที่แม่หมอเห็น น่าจะเป็น...ใช่คนนี้ไหม?\" ([TYPE:D] ไม่นับ) แล้วเจาะให้ตรงตัวขึ้น\n"
            ."• ⚠️ ไพ่บอก \"แนวบุคคล/ความสัมพันธ์ที่เป็นไปได้\" — *ห้ามฟันธงชื่อจริง/ตัวคนเป๊ะ 100%* (ให้ลูกค้าเป็นคนยืนยัน)\n"
            ."━━━━━━━━━━━━━━━━━\n\n";
    }

    /**
     * 🩺 helper — แปลง name_en เป็น suit key สำหรับ fallback ตำราสุขภาพ
     *   (ใช้เมื่อ lookup รายใบไม่เจอ เช่น ชื่อไพ่เพี้ยน)
     */
    protected function resolveSuitKeyForHealth(string $nameEn): string
    {
        $n = mb_strtolower($nameEn);
        if (mb_strpos($n, 'cups') !== false) {
            return 'cups';
        }
        if (mb_strpos($n, 'wands') !== false) {
            return 'wands';
        }
        if (mb_strpos($n, 'swords') !== false) {
            return 'swords';
        }
        if (mb_strpos($n, 'pentacles') !== false) {
            return 'pentacles';
        }

        return 'major';
    }

    /**
     * 💬 (2026-05-24) Pre-Celtic chat context — inject บทสนทนาก่อนซื้อ Celtic เข้า prompt
     *
     * Why: AI generates predictions โดย "ไม่รู้" ว่าลูกค้าเคยเล่าอะไรก่อนหน้านี้
     *   เช่น conv 4961 ลูกค้าเล่า "ทิ้งผมกะลูกสาว / ลูกสาวเรียน มม.ส ปี 2" ก่อนซื้อ
     *   → AI ทำนายโดยไม่รู้บริบทนี้ → generic
     *
     * Window: 60 นาทีก่อน paid_at (หรือ created_at ถ้ายังไม่ paid — เคสฟรี)
     * Cap: 12 turns / 1500 chars total (กัน token bloat)
     * Filter: skip messages ที่เป็น quick_replies/payment templates (noise)
     *
     * Returns formatted block หรือ '' ถ้าไม่มีบทสนทนา
     */
    protected function buildPreCelticChatContext(FortuneReading $reading): string
    {
        try {
            $userId = $reading->facebook_user_id
                ?: $reading->platform_user_id
                ?: null;

            if (empty($userId)) {
                return '';
            }

            // Anchor time: paid_at > celtic_first_answered_at > created_at
            $anchorTime = $reading->paid_at
                ?? $reading->celtic_first_answered_at
                ?? $reading->created_at;

            if (! $anchorTime) {
                return '';
            }

            // ค้น conversation (LineBotConversation ครอบทั้ง FB+LINE — column = line_user_id legacy)
            $conversation = \App\Models\LineBotConversation::where('line_user_id', (string) $userId)
                ->orderBy('updated_at', 'desc')
                ->first();

            if (! $conversation) {
                return '';
            }

            // 🌙 (2026-06-13) ใช้ "แชททั้งวัน" ก่อน anchor (เดิม 60 นาที) — owner: เอาที่คุยมาทั้งวันมาช่วยตอบ
            //   windowStart = ต้นวันของ anchor (เที่ยงคืน) → ครอบ chitchat ทั้งวันก่อนซื้อ/ก่อนคำถามนี้
            //   (บทสนทนา "ระหว่างทำนาย" อยู่ใน previousContext แล้ว — ตรงนี้เน้นแชททั่วไปทั้งวัน)
            $windowStart = (clone $anchorTime)->startOfDay();
            $messages = \App\Models\LineBotMessage::where('conversation_id', $conversation->id)
                ->whereBetween('created_at', [$windowStart, $anchorTime])
                ->orderBy('id', 'asc')
                ->limit(30)  // safety cap
                ->get();

            if ($messages->isEmpty()) {
                return '';
            }

            // Filter noise + format
            $lines = [];
            $totalChars = 0;
            $maxChars = 2800;  // 🌙 (2026-06-13) เพิ่มจาก 1500 — รองรับแชททั้งวัน
            $maxTurns = 24;    // 🌙 (2026-06-13) เพิ่มจาก 12 — รองรับแชททั้งวัน
            $turnCount = 0;

            // Pattern noise ที่ skip — payment template, sticky QR text, system messages
            $noisePatterns = [
                '/^🌙\s*สวัสดี/u',
                '/^📋\s*เมนู/u',
                '/^💰.*BAHT/u',
                '/QR\s*Code/i',
                '/PromptPay/i',
                '/^✅.*ระบบกำลังดำเนินการ/u',
                '/^หมอจันทรา.*พักรับคำถาม/u',
            ];

            foreach ($messages as $msg) {
                if ($turnCount >= $maxTurns || $totalChars >= $maxChars) {
                    break;
                }

                $text = trim((string) $msg->message);
                if ($text === '') {
                    continue;
                }

                // Skip ข้อความสั้นเกินจากบอท (noise: typing/empty/ack)
                if ($msg->role === 'assistant' && mb_strlen($text) < 8) {
                    continue;
                }

                // Skip noise patterns (template/system)
                $isNoise = false;
                foreach ($noisePatterns as $pattern) {
                    if (preg_match($pattern, $text)) {
                        $isNoise = true;
                        break;
                    }
                }
                if ($isNoise) {
                    continue;
                }

                $role = $msg->role === 'assistant' ? 'แม่หมอ' : 'ลูกค้า';
                $snippet = mb_substr($text, 0, $msg->role === 'assistant' ? 150 : 200);

                $line = "{$role}: {$snippet}";
                $totalChars += mb_strlen($line);
                $lines[] = $line;
                $turnCount++;
            }

            if (empty($lines)) {
                return '';
            }

            $block = "━━━━━━━━━━━━━━━━━\n"
                ."💬 บริบทสนทนาก่อนเปิดไพ่ (ใช้ผูกคำทำนายให้ตรงเรื่องราวจริงของลูกค้า)\n"
                ."━━━━━━━━━━━━━━━━━\n"
                .implode("\n", $lines)."\n\n";

            return $block;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::debug('Celtic: buildPreCelticChatContext fail (non-blocking)', [
                'reading_id' => $reading->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return '';
        }
    }

    /**
     * 📜 (2026-05-25) Past readings context — ดึงประวัติทำนายเก่าของลูกค้า เพื่อ weave storyline
     *
     * ⚠️ **สำคัญมาก:** ใช้เป็น context — **ไม่ใช่ constraint**
     *   user spec 2026-05-25: "ไพ่ขัดแย้งได้ เพราะอนาคตเปลี่ยนได้"
     *   AI ห้ามผูกมัดให้ทำนายตรงกับครั้งก่อน — ถ้าขัดแย้ง = ปกติ
     *   AI ต้องพูดอย่างนุ่มนวลว่า "พลังเปลี่ยน / ดวงคลาย / พลิกได้" — ไม่ใช่ "ครั้งก่อนผิด"
     *
     * Cache 30 นาที per reading (กัน DB hit ซ้ำใน Q1+Q2+...)
     *
     * @return string ว่างถ้าไม่ใช่ลูกค้าเก่า / ไม่มี FB user
     */
    protected function buildPastReadingsContext(FortuneReading $reading): string
    {
        try {
            // 🧭 (2026-09-01) LINE ก็ได้ context นี้ด้วย — LINE id อยู่ในคอลัมน์ facebook_user_id อยู่แล้ว
            //   (คอมเมนต์เก่าบอก "LINE ยังไม่ support" = เข้าใจผิดจากชื่อคอลัมน์)
            $userId = $reading->facebook_user_id ?: $reading->platform_user_id ?: null;
            if (empty($userId)) {
                return '';
            }

            $cacheKey = "fortune:past_readings_ctx:{$reading->id}";
            $cached = \Illuminate\Support\Facades\Cache::get($cacheKey);
            if ($cached !== null) {
                return $cached === 'EMPTY' ? '' : $cached;
            }

            // ดึง 5 readings ล่าสุดที่จ่ายเงิน (paid) + ไม่ใช่ตัวปัจจุบัน
            $past = FortuneReading::where('facebook_user_id', $userId)
                ->where('id', '!=', $reading->id)
                ->where('is_paid', true)
                ->whereIn('conversation_status', [
                    FortuneReading::STATUS_COMPLETED,
                    'celtic_grand_finale',
                    'celtic_generating',
                ])
                ->orderByDesc('paid_at')
                ->limit(5)
                ->get();

            if ($past->isEmpty()) {
                \Illuminate\Support\Facades\Cache::put($cacheKey, 'EMPTY', 1800);

                return '';
            }

            $lines = [];
            foreach ($past as $p) {
                $daysAgo = (int) max(0, now()->diffInDays($p->paid_at, false) * -1);
                $daysText = $daysAgo === 0 ? 'วันนี้' : ($daysAgo === 1 ? 'เมื่อวาน' : "{$daysAgo} วันก่อน");

                // 🏷️ (2026-08-31) "เรื่อง" ต้องมาจาก PastCaseRecallService
                //   ⚠️ `fortune_readings.questions` ของบิล Celtic **ว่างทุกใบ** (คำถามจริงอยู่ที่
                //      fortune_celtic_questions) → โค้ดเดิมพิมพ์ "(ไม่ระบุเรื่อง)" ให้ AI เสมอ
                $firstQ = app(\App\Services\Fortune\PastCaseRecallService::class)->resolveTopic($p);
                $topicText = $firstQ !== '' ? $firstQ : '(ไม่ระบุเรื่อง)';

                $type = $p->reading_type ?? 'basic';
                $typeLabel = $type === 'celtic_cross' ? 'Celtic 10 ใบ' : ($type === 'deep' ? 'Deep' : 'Basic');

                // หาคำทำนายสรุป — celtic_grand_finale_summary > ai_response (cap 250 chars)
                $summary = (string) ($p->getConversationState('celtic_grand_finale_summary', '') ?? '');
                if ($summary === '') {
                    $summary = (string) ($p->ai_response ?? '');
                }
                $summarySnippet = mb_substr(trim($summary), 0, 250);

                // ไพ่ Celtic (ถ้ามี)
                $cardsLine = '';
                if ($type === 'celtic_cross') {
                    $cards = $p->getCelticCards();
                    if (! empty($cards)) {
                        $cardNames = [];
                        foreach ($cards as $c) {
                            $name = (string) ($c['card_name_th'] ?? $c['card_name_en'] ?? '?');
                            $rev = ! empty($c['is_reversed']) ? ' (กลับหัว)' : '';
                            $cardNames[] = $name.$rev;
                            if (count($cardNames) >= 4) {
                                $cardNames[] = '...';
                                break;
                            }
                        }
                        $cardsLine = '  ไพ่: '.implode(' / ', $cardNames)."\n";
                    }
                }

                $lines[] = "• [{$daysText}] {$typeLabel} — \"{$topicText}\"\n"
                    .$cardsLine
                    ."  สรุป: {$summarySnippet}".(mb_strlen($summary) > 250 ? '…' : '');
            }

            $block = "━━━━━━━━━━━━━━━━━\n"
                .'📜 ประวัติทำนายเก่าของลูกค้า ('.count($past)." ครั้งล่าสุด)\n"
                ."━━━━━━━━━━━━━━━━━\n"
                .implode("\n\n", $lines)."\n\n"
                ."⚠️ **กฎสำคัญ: ดวงเปลี่ยนได้**\n"
                ."• ใช้ประวัตินี้เพื่อ **ต่อ storyline + รู้จักลูกค้า** — ไม่ใช่ผูกมัด\n"
                ."• ถ้าวันนี้ไพ่ออกขัดแย้งกับครั้งก่อน = **ปกติ** เพราะอนาคตเปลี่ยนได้ตามการกระทำ\n"
                ."• ห้ามพูดว่า \"ครั้งก่อนผิด\" / \"ทำนายพลาด\" — ใช้ \"พลังเปลี่ยน\" / \"ดวงคลาย\" / \"ความตั้งใจของลูกค้าพลิกสถานการณ์\"\n"
                ."• ถ้าผลตรงกับครั้งก่อน → ยืนยันว่า \"แม่หมอเห็นพลังเดิมต่อเนื่อง\" + ลึกขึ้น\n"
                ."• weave เรื่องราว: \"ครั้งก่อนเจอ X / วันนี้พลังเปลี่ยนเป็น Y\"\n\n";

            \Illuminate\Support\Facades\Cache::put($cacheKey, $block, 1800);

            return $block;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::debug('Celtic: buildPastReadingsContext fail (non-blocking)', [
                'reading_id' => $reading->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return '';
        }
    }

    /**
     * 👋 (2026-05-25) Check-in directive — ลูกค้าเก่า เปิดด้วย "ผ่านมาเป็นไงบ้าง"
     *
     * Trigger: มี ≥1 paid reading ภายใน 30 วัน + ตัวปัจจุบันยังไม่ตอบ Q1 (sequence=1)
     *
     * @return string directive หรือว่างเปล่าถ้าเป็นลูกค้าใหม่
     */
    protected function buildRepeatCheckinDirective(FortuneReading $reading): string
    {
        try {
            $userId = $reading->facebook_user_id ?? null;
            if (empty($userId)) {
                return '';
            }

            // ดูครั้งล่าสุดภายใน 30 วัน
            $lastRecent = FortuneReading::where('facebook_user_id', $userId)
                ->where('id', '!=', $reading->id)
                ->where('is_paid', true)
                ->where('paid_at', '>=', now()->subDays(30))
                ->orderByDesc('paid_at')
                ->first();

            if (! $lastRecent) {
                return ''; // ลูกค้าใหม่ หรือนานเกิน 30 วัน — ไม่ต้อง check-in
            }

            $daysAgo = (int) max(0, now()->diffInDays($lastRecent->paid_at, false) * -1);
            $daysText = $daysAgo === 0 ? 'วันนี้' : ($daysAgo === 1 ? 'เมื่อวาน' : "{$daysAgo} วันก่อน");

            // 🏷️ (2026-08-31) เดิมอ่าน questions[0] ซึ่งว่างทุกใบใน Celtic → check-in ไม่เคยเอ่ยเรื่อง
            $lastTopic = mb_substr(
                app(\App\Services\Fortune\PastCaseRecallService::class)->resolveTopic($lastRecent),
                0,
                50
            );
            $topicHint = $lastTopic !== '' ? " เรื่อง \"{$lastTopic}\"" : '';

            return "━━━━━━━━━━━━━━━━━\n"
                ."👋 [REPEAT_CUSTOMER_CHECKIN] เปิดคำทำนายด้วยการ check-in\n"
                ."━━━━━━━━━━━━━━━━━\n"
                ."ลูกค้าคนนี้เคยดูดวงไปแล้ว{$daysText}{$topicHint}\n"
                ."🎯 AI: **เปิด section 1 ด้วยการ check-in 1-2 ประโยค** ก่อนเริ่มทำนายปกติ:\n"
                ."  ตัวอย่าง: \"ผ่านมา{$daysText}แล้วเนอะ ที่แม่หมอทำนายไว้คราวก่อน...เป็นยังไงบ้างคะ?\"\n"
                ."  หรือ: \"แม่หมอจำได้นะ — {$daysText}เปิดไพ่ให้{$topicHint} ผ่านมา...สถานการณ์เป็นยังไงบ้าง?\"\n"
                ."⚠️ ห้ามทำเหมือนเจอครั้งแรก — ห้ามถามชื่อ / ห้ามทักทาย formal เกินไป\n"
                ."⚠️ ถ้าลูกค้าไม่ตอบ check-in (ถามคำถามใหม่เลย) → AI ทำนายปกติ ไม่ต้องบังคับ\n\n";
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::debug('Celtic: buildRepeatCheckinDirective fail (non-blocking)', [
                'reading_id' => $reading->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return '';
        }
    }

    /**
     * 📚 (2026-08-31) เคสเก่าของลูกค้า — ดัชนี (เปิดตลอด) + คำทำนายเก่าฉบับเต็ม (เปิดตอนลูกค้าอ้างถึง)
     *
     * เกิดจากเคส FTU-260831-W5209: ลูกค้าซื้อ Celtic 99 ไป 8 ใบ เรื่องคดีความเรื่องเดียวยาว 3 เดือน
     * ในฐานมี Q&A เก่า 23 แถว + บทสรุป 5-7 พันตัวอักษร แต่ prompt ไม่เคยอ่านข้ามบิลเลย
     *   - `buildPastReadingsContext` ตัดเหลือ 250 ตัวอักษร + inject แค่ Q1
     *   - Q2+ return ก่อนถึงบรรทัดนั้น → ลูกค้าถามถึงของเก่ากลางวง = บอทมืดสนิท
     *
     * ⚠️ ต้องเรียก **ทุกจุดที่ประกอบ prompt** (Q1 main / Q1 followup / Q2+ short)
     *    ตกจุดเดียว = ลูกค้าถามถึงเคสเก่าในเทิร์นนั้นแล้วบอทจำไม่ได้
     *
     * @param  string|null  $userQuestion  คำถามลูกค้าเทิร์นนี้ — ใช้ค้นว่าเคสเก่าใบไหนตรง
     * @param  bool  $withIndex  false = ข้ามดัชนี (ใช้ที่ Q1 ซึ่ง buildPastReadingsContext
     *                           แจงเคสเก่าให้อยู่แล้ว — ใส่ทั้งคู่ = จ่าย token ซ้ำเปล่าๆ)
     */
    protected function buildPastCaseBlock(
        FortuneReading $reading,
        ?string $userQuestion = null,
        bool $withIndex = true
    ): string {
        try {
            $recall = app(\App\Services\Fortune\PastCaseRecallService::class);

            return ($withIndex ? $recall->buildIndex($reading) : '')
                .$recall->buildRecallBlock($reading, $userQuestion);
        } catch (\Throwable $e) {
            Log::debug('Celtic: buildPastCaseBlock fail (non-blocking)', [
                'reading_id' => $reading->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return '';
        }
    }

    protected function formatCardsForPrompt(array $cards): string
    {
        $lines = [];
        for ($pos = 1; $pos <= 10; $pos++) {
            $card = $cards[$pos] ?? null;
            if (! $card) {
                continue;
            }
            $position = $card['position_name'] ?? '?';
            $positionDesc = $card['position_description'] ?? '';
            $name = $card['card_name_th'] ?? '?';
            $nameEn = $card['card_name_en'] ?? '';
            $reversed = ! empty($card['is_reversed']) ? '(กลับหัว)' : '(ตั้งตรง)';
            // 🃏 (2026-05-30) เพิ่ม cap 200→420 — ให้ AI มีเนื้อหาหน้าไพ่อ่านมากขึ้น
            //   เดิม 200 ตัด meaning สั้น → AI เติมช่องว่างด้วยหลักทั่วไป (อาการ "เซฟตัว/ไม่ฟันธง")
            $meaning = mb_substr($card['meaning'] ?? '', 0, 420);

            $lines[] = "ตำแหน่ง {$pos} [{$position}] — {$positionDesc}\n"
                ."  ไพ่: {$name} {$reversed} ({$nameEn})\n"
                ."  ความหมาย: {$meaning}";
        }

        return implode("\n\n", $lines);
    }

    /**
     * 🎯 ตำแหน่งไพ่ fallback ของบทสรุป — ใช้เมื่อ Question Router จับหมวดไม่ได้
     *
     * 1 = หัวใจของเรื่อง · 2 = อุปสรรค · 6 = อนาคตอันใกล้ · 10 = ผลลัพธ์
     * ปกติ CelticQuestionRouter จะเลือกตำแหน่งให้ตามคำถามจริง — ค่านี้คือด่านสุดท้าย
     */
    protected const FINALE_KEY_POSITIONS = [1, 2, 6, 10];

    /**
     * 🎯 (2026-08-07) สร้างบล็อก "ไพ่ที่ต้องอ่านตอบคำถามนี้" จาก CelticQuestionRouter
     *
     * owner: "ต้องฉลาดในการดึงไพ่ที่เกี่ยวข้องกับคำถาม ถามอนาคตต้องดึงไพ่ตำแหน่งอนาคตเป็นหลัก"
     *
     * @param  array|null  $route  ส่ง route ที่คำนวณไว้แล้วมาได้ (บทสรุปใช้ routeMany)
     * @return string ว่าง = ปิดสวิตช์ / config พัง / ไพ่ไม่ครบ (ไม่ทำให้ path ลูกค้าพัง)
     */
    protected function buildQuestionRoutingDirective(
        FortuneReading $reading,
        string $questionText,
        bool $isFinale = false,
        ?array $route = null
    ): string {
        try {
            $cards = $reading->getCelticCards();
            if (count($cards) < 10) {
                return '';
            }

            $router = app(\App\Services\Fortune\CelticQuestionRouter::class);
            $route ??= $router->route($questionText);

            return $router->buildDirective($cards, $route, $isFinale);
        } catch (\Throwable $e) {
            // non-blocking — ลูกค้าจ่ายเงินแล้ว ห้ามล้มเพราะ config routing
            Log::warning('CelticCross: question routing ล้มเหลว (ข้าม)', [
                'reading_id' => $reading->id,
                'error' => $e->getMessage(),
            ]);

            return '';
        }
    }

    /**
     * รวมคำถามทั้งรอบ → หาหมวด + ตำแหน่งไพ่ที่บทสรุปต้องใช้
     *
     * @param  array<int, string>  $questionTexts
     * @return array{route: array, positions: array<int>}
     */
    protected function routeFinaleQuestions(array $questionTexts): array
    {
        try {
            $route = app(\App\Services\Fortune\CelticQuestionRouter::class)->routeMany($questionTexts);
            $positions = ! empty($route['positions']) ? $route['positions'] : self::FINALE_KEY_POSITIONS;

            return ['route' => $route, 'positions' => $positions];
        } catch (\Throwable $e) {
            return ['route' => [], 'positions' => self::FINALE_KEY_POSITIONS];
        }
    }

    public function getMaxQuestions(): int
    {
        // 🌙 (2026-06-07) Default 0 = ไม่จำกัดคำถาม ภายในเวลา 15 นาที (เดิม 5 — ยกเลิก hard cap จำนวน)
        return (int) ($this->settings->celtic_cross_max_questions ?? 0);
    }

    public function getPrice(): float
    {
        return (float) ($this->settings->celtic_cross_price ?? 99.00);
    }

    public function getQaWindowMinutes(): int
    {
        return (int) ($this->settings->celtic_cross_qa_window_minutes ?? 15);
    }

    /**
     * Reset Celtic state ให้ลูกค้าเริ่มเลือกไพ่ใหม่ (ใช้กรณี anti-fraud restart ก่อน Q1)
     *
     * เก็บ bill_reference + is_paid + amount_paid ไว้ — ลูกค้าไม่ต้องจ่ายซ้ำ
     *
     * 🆕 (2026-05-17) Anti-fraud: สับไพ่ใหม่ได้แค่ 1 ครั้ง/บิล
     *   กันลูกค้าสับไม่หยุดจนได้ไพ่ที่ "ชอบ" — ทำลายความศักดิ์สิทธิ์
     *   Counter เก็บใน conversation_state['celtic_shuffle_count'] (ค่าเริ่ม 0)
     */
    public function resetPickedCards(FortuneReading $reading): void
    {
        if ($reading->celtic_questions_used > 0) {
            // ห้าม reset ถ้าตอบ Q ไปแล้ว (anti-fraud)
            throw new Exception('ไม่สามารถสับไพ่ใหม่ได้ — ได้รับคำทำนายไปแล้ว ต้องเริ่มรอบใหม่ (จ่ายค่าครูใหม่) เท่านั้นค่ะ');
        }

        $shuffleCount = (int) $reading->getConversationState('celtic_shuffle_count', 0);
        if ($shuffleCount >= 1) {
            throw new Exception('สับไพ่ใหม่ได้เพียง 1 ครั้งต่อบิลเท่านั้น — ใช้ครบโควต้าแล้วค่ะ ตั้งจิตให้แน่วแน่แล้วเปิดไพ่ใบถัดไปเลยนะคะ');
        }

        $reading->setConversationState('celtic_cards', []);
        $reading->setConversationState('celtic_shuffle_count', $shuffleCount + 1);
        $reading->update([
            'conversation_status' => FortuneReading::STATUS_CELTIC_PICKING,
            'celtic_summary_image_path' => null,
        ]);
    }

    /**
     * 🌟 (2026-05-04) Generate Grand Finale Master Summary
     *
     * เรียกตอนจบ session — สรุประดับปรมาจารย์ ผูกทุกคำถามกับไพ่ทั้ง 10 ใบ
     * + ดึงข้อมูลจาก Deep 39฿ reading ของ user เดียวกัน (ถ้ามี) มาใช้:
     *   - วันเดือนปีเกิด → astrology
     *   - ดาวเจ้าชนะ จากผลทำนายเดิม
     *
     * Returns ['success' => bool, 'summary' => string, 'has_deep_link' => bool]
     *
     * Sync AI call ~20-30s — เรียกจาก endCelticSession เมื่อ customer ยังออนไลน์
     * (customer_said_done / max_questions_reached / ai_signal)
     */
    public function generateGrandFinaleSummary(FortuneReading $reading): array
    {
        $cards = $reading->getCelticCards();
        if (count($cards) < 10) {
            return [
                'success' => false,
                'summary' => '',
                'has_deep_link' => false,
                'reason' => 'cards_incomplete',
            ];
        }

        // ดึง Q&A ทั้งหมด — แยก "ตอบแล้ว" ออกจาก "ยังค้างคำตอบ"
        // 🆕 (2026-08-07 owner: "อย่าให้มันคาใจ") เดิมกรอง whereNotNull('answered_at')
        //   → คำถามที่เจ้าชะตาถามทิ้งไว้ตอนหมดเวลา/รอบปิดตัดก่อน หายจากบทสรุปทั้งข้อ
        //     = จ่าย 99฿ แล้วถามค้างไว้ ไม่เคยได้คำตอบเลย (นี่คือต้นตอ "คาใจ" ตัวจริง)
        //   ตอนนี้ดึงมาทั้งหมด แล้วส่งข้อที่ยังค้างเข้าบทสรุปให้ตอบปิดให้จบ
        $allQuestions = $reading->celticQuestions()
            ->orderBy('sequence')
            ->get();

        $answeredQuestions = $allQuestions
            ->filter(fn ($q) => $q->answered_at !== null && trim((string) $q->response) !== '')
            ->values();

        $pendingQuestions = $allQuestions
            ->filter(fn ($q) => $q->answered_at === null || trim((string) $q->response) === '')
            ->values();

        if ($answeredQuestions->isEmpty() && $pendingQuestions->isEmpty()) {
            return [
                'success' => false,
                'summary' => '',
                'has_deep_link' => false,
                'reason' => 'no_questions',
            ];
        }

        // ค้นหา Deep 39฿ reading ของ user เดียวกัน (ใช้ birth_date + ดาวเจ้าชนะ)
        $deepReading = $this->findLinkedDeepReading($reading);

        $prompt = $this->buildGrandFinalePrompt($reading, $cards, $answeredQuestions, $deepReading, $pendingQuestions);

        try {
            $startTime = microtime(true);
            // 🆕 (2026-05-07) Grand Finale = Celtic paid summary → request 'prediction' purpose
            $aiService = new FortuneAIService($this->settings, 'prediction');

            // 🔮 (2026-05-04 fix B1) ใส่ {birth_date_section} ใน template เมื่อมี Deep linked
            //    เพื่อให้ formatBirthDateSection inject ข้อมูล:
            //      - ดาวเจ้าชนะ (planet by day-of-week)
            //      - ธาตุ + ราศี + อายุ
            //      - ดาวมิตร / ดาวศัตรู / สีมงคล / เลขมงคล
            //    เดิม template = '{questions}' → birthDate ส่งไปแต่ไม่ใส่ใน prompt
            $template = $deepReading?->birth_date
                ? "{questions}\n\n{birth_date_section}"
                : '{questions}';

            // 🪪 (2026-08-17) ผูกกับใบ Celtic ($reading) ไม่ใช่ใบ Deep ที่ลิงก์มา
            //   — ต้นทุนบทสรุปนี้เป็นของบิล Celtic 99฿ ใบนี้
            $aiService->forReading($reading);

            $result = $aiService->generateWithRetryAndFallback(
                questions: [$prompt],
                userProfile: null,
                userPosts: null,
                promptTemplate: $template,
                readingType: 'deep',
                birthDate: $deepReading?->birth_date?->format('Y-m-d'),
                userContext: "celtic_finale:{$reading->id}",
                // บทสรุปรวบทุกคำถาม — ไม่มีคำถามเดี่ยวให้ตรวจ → ดูแค่ธงคุณไสย์
                modelOverrides: $this->resolveCelticModelOverrides($reading),
            );

            $summary = trim($result['response'] ?? '');
            $tokensUsed = (int) ($result['tokens_used'] ?? 0);
            $responseTimeMs = (int) round((microtime(true) - $startTime) * 1000);

            // ลบ token [END_SESSION] ถ้า AI ติดมา
            $summary = trim(preg_replace('/\[\s*(END[_\s]?SESSION|จบ|END)\s*\]/iu', '', $summary));

            if ($summary === '' || mb_strlen($summary) < 200) {
                throw new Exception('AI Grand Finale ตอบสั้นเกินไป ('.mb_strlen($summary).' chars)');
            }

            // 🗑️ (2026-08-07 owner: "ตัดออกเลยเพราะมีภาพแล้ว") ถอด "รายการไพ่ 10 ใบ" ต่อท้ายบทสรุป
            //   เดิม (2026-06-07) ต่อท้าย เลข+ชื่อไพ่+ตั้งตรง/กลับหัว ตาม spec "บางคนต้องการ"
            //   ตอนนี้ลูกค้าเห็นภาพไพ่ตอนเปิดครบ 10 ใบอยู่แล้ว + บทสรุปเลิกอธิบายไพ่แล้ว
            //   → รายการชื่อไพ่ท้ายบทกลายเป็นส่วนเกิน (เมธอด buildCelticCardListBlock ถูกลบทิ้งด้วย)

            // อัพเดต token tracking
            $reading->update([
                'tokens_used' => ($reading->tokens_used ?? 0) + $tokensUsed,
            ]);
            $reading->setConversationState('celtic_grand_finale_at', now()->toIso8601String());
            $reading->setConversationState('celtic_grand_finale_summary', $summary);

            Log::info('CelticCross: Grand Finale สำเร็จ', [
                'reading_id' => $reading->id,
                'questions_count' => $answeredQuestions->count(),
                // 🆕 คำถามที่ค้างไว้แล้วถูกยกมาตอบปิดในบทสรุป (ควรเป็น 0 ในเคสปกติ)
                'pending_count' => $pendingQuestions->count(),
                'has_deep_link' => $deepReading !== null,
                'summary_len' => mb_strlen($summary),
                'tokens' => $tokensUsed,
                'response_time_ms' => $responseTimeMs,
            ]);

            return [
                'success' => true,
                'summary' => $summary,
                'has_deep_link' => $deepReading !== null,
                'tokens_used' => $tokensUsed,
            ];
        } catch (\Throwable $e) {
            Log::warning('CelticCross: Grand Finale ล้มเหลว — fallback simple closing', [
                'reading_id' => $reading->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'summary' => '',
                'has_deep_link' => $deepReading !== null,
                'reason' => 'ai_failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * ค้นหา Deep 39฿ reading ของ user เดียวกัน (มี birth_date)
     *
     * ใช้สำหรับ Grand Finale ผสมข้อมูลโหราศาสตร์เข้ากับ Celtic
     */
    protected function findLinkedDeepReading(FortuneReading $reading): ?FortuneReading
    {
        $fbId = $reading->facebook_user_id;
        $platformId = $reading->platform_user_id;

        if (empty($fbId) && empty($platformId)) {
            return null;
        }

        return FortuneReading::query()
            ->where(function ($q) use ($fbId, $platformId) {
                if (! empty($fbId)) {
                    $q->where('facebook_user_id', $fbId);
                }
                if (! empty($platformId)) {
                    $q->orWhere('platform_user_id', $platformId);
                }
            })
            ->where('reading_type', FortuneReading::READING_TYPE_DEEP)
            ->where('is_paid', true)
            ->whereNotNull('birth_date')
            ->whereNotNull('deep_response')
            ->orderByDesc('responded_at')
            ->first();
    }

    /**
     * ✂️ ย่อคำถามให้เหลือบรรทัดเดียวสำหรับเช็คลิสต์ในบทสรุป
     *
     * ใช้ยุบขึ้นบรรทัดใหม่/ช่องว่างซ้อน (คำถามลูกค้ามักพิมพ์หลายบรรทัด)
     * ⚠️ ยุบเฉพาะ whitespace — ห้ามใช้ regex ตัดอักขระ เพราะจะกินสระ/วรรณยุกต์ไทย (Mark class)
     */
    protected function shortenQuestionForChecklist(?string $question, int $limit = 110): string
    {
        // marker ภายในระบบ — แปลงให้อ่านรู้เรื่องแทนการโชว์ token ดิบให้ AI
        $clean = str_replace('[IMAGE_ATTACHED]', '(เจ้าชะตาส่งรูปมา)', (string) $question);
        $clean = trim((string) preg_replace('/\s+/u', ' ', $clean));

        if ($clean === '') {
            return '(ข้อความว่าง)';
        }

        return mb_strlen($clean) > $limit
            ? mb_substr($clean, 0, $limit).'…'
            : $clean;
    }

    /**
     * 📆 บริบทวันที่ปัจจุบัน — บล็อกเล็กที่ต้องมีในทุกพรอมต์ที่ทำนาย "ช่วงเวลา"
     *
     * ⚠️ ทำไม (2026-09-02 — เคส FTU-260902-Y8063):
     *   โมเดลไม่รู้ว่าวันนี้วันที่เท่าไหร่ ถ้าไม่บอก → เดาเอา
     *   บิลจริงวันที่ 2 กันยายน 2569 แต่พื้นดวงเปิดตัวตอบว่า
     *   "ช่วงเมษายน–มิถุนายน 2569 จะมีงานเร่ง..." = **ทำนายย้อนอดีตไป 3 เดือน**
     *   เดิมมีบล็อกนี้เฉพาะ buildGrandFinalePrompt — อีก 3 พรอมต์ตาบอดวันที่
     */
    protected function buildCurrentDateContext(): string
    {
        $now = now();
        $monthTh = ['', 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
            'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
        $month = $monthTh[(int) $now->format('n')];
        $yearBE = (int) $now->format('Y') + 543;
        $day = (int) $now->format('j');

        $nextYearBE = $yearBE + 1;

        return "📆 *วันนี้คือ {$day} {$month} {$yearBE}* — ใช้ผูกช่วงเวลาทุกครั้งที่ทำนาย\n"
            ."   ❌ ห้ามชี้ช่วงเวลาที่ผ่านไปแล้วว่าเป็นอนาคต — เดือนที่มาก่อนเดือน{$month} {$yearBE} คืออดีต\n"
            ."   ✅ นับจากวันนี้ไปข้างหน้าเสมอ เช่น \"ภายใน 2 สัปดาห์\" / \"ปลายเดือน{$month}\" / \"ต้นปี {$nextYearBE}\"\n\n";
    }

    /**
     * 🌟 ผังดวงจากวันเกิดสำหรับพรอมต์ Celtic — จุดกลางจุดเดียวของทั้งเลน 99฿
     *
     * รวมตรรกะที่เคยก็อปกันอยู่ 2 ที่ (generateBaseChartSectioned + buildFollowupPrompt
     * ซึ่งคอมเมนต์เดิมเขียนไว้เองว่า "replicate ... เพื่อ parity") ให้เหลือที่เดียว
     * — ตอนเพิ่มบทสรุปใหญ่เข้ามาเป็นที่ 3 ถ้าไม่รวมก่อนจะกลายเป็น 3 ชุดที่ค่อยๆ drift
     *
     * ลำดับหาวันเกิด (สำคัญ ห้ามสลับ):
     *   1. ข้อความที่ส่งเข้ามา ($seedText — คำถามปัจจุบัน + คำถามเก่า)
     *   2. + `celtic_birthdate_text` ที่เคยเก็บไว้ (คงข้ามเทิร์น แม้เทิร์นนั้นถูกจัดเป็น TYPE:D)
     *   3. ยังไม่เจอ → ดึงจากบิล Deep 39฿ ที่ user เดียวกันเคยทำ (auto ไม่ต้องถามซ้ำ)
     *
     * @param  string  $seedText  ข้อความที่อาจมีวันเกิด
     * @param  string|null  $persistCandidate  ถ้าเจอวันเกิดในข้อความนี้ → เก็บลง state (null = ไม่เก็บ)
     * @return string ว่าง = ไม่พบวันเกิด หรือคำนวณไม่ได้ (ทำนายจากไพ่ล้วนต่อไป — ห้าม throw)
     */
    protected function buildBirthAstrologyBlockFor(
        FortuneReading $reading,
        string $seedText,
        ?string $persistCandidate = null
    ): string {
        $astro = new ThaiAstrologyService;
        $source = $seedText;

        $persistedBirth = (string) $reading->getConversationState('celtic_birthdate_text', '');
        if ($persistedBirth !== '') {
            $source .= "\n".$persistedBirth;
        }

        try {
            // เจอวันเกิดในเทิร์นนี้ → เก็บไว้ กันหายถ้าเทิร์นถูกจัดเป็น TYPE:D (record ถูกลบ)
            if ($persistCandidate !== null && ! empty($astro->extractBirthDatesFromText($persistCandidate))) {
                $reading->setConversationState(
                    'celtic_birthdate_text',
                    mb_substr(trim($persistedBirth."\n".$persistCandidate), 0, 500)
                );
            }
        } catch (\Throwable $e) {
            // non-blocking
        }

        try {
            if (empty($astro->extractBirthDatesFromText($source))) {
                $linkedDeep = $this->findLinkedDeepReading($reading);
                if ($linkedDeep && $linkedDeep->birth_date) {
                    $source .= "\nเจ้าชะตาเกิด ".$linkedDeep->birth_date->format('d/m/Y');
                }
            }
        } catch (\Throwable $e) {
            // non-blocking
        }

        try {
            return (string) $astro->buildCelticBirthAstrologyBlock($source);
        } catch (\Throwable $e) {
            // คำนวณดาวไม่ได้ → ทำนายจากไพ่ล้วน ดีกว่าล้มทั้งบิลที่ลูกค้าจ่ายแล้ว
            return '';
        }
    }

    /**
     * 🌟 Build Grand Finale Prompt — บทสรุปที่ "รวบทุกคำถามมาตอบให้จบ"
     *
     * 🔄 (2026-08-07 owner directive: "ไพ่ 99 ควรรวบรวมที่ถามมาสรุปด้วย / อย่าให้มันคาใจ /
     *     ไม่ต้องอธิบายไพ่แล้ว")
     *   - เดิม: ไล่บรรยายไพ่ตำแหน่ง 1-9 ทีละย่อหน้า (9 ย่อหน้า) แล้วปิดด้วยใบที่ 10
     *     → ลูกค้าได้ "คำอธิบายไพ่ซ้ำ" แต่คำถามบางข้อไม่เคยถูกตอบให้ชัด = คาใจ
     *   - ใหม่: แกนบทสรุป = *ไล่ตอบทีละคำถาม* (รวมข้อที่ยังค้างคำตอบ) ห้ามอธิบายไพ่
     *     ไพ่ยังเป็นแหล่งความจริง 100% (buildCardFirstMandate) — แค่ไม่โชว์ที่มา พูดเป็นคำฟันธง
     *
     * โครงสร้าง:
     *   - Persona: นักพยากรณ์ระดับเซียน 30+ ปี เห็นชะตามาเป็นพันคน
     *   - Context: ทุกคำถาม + ทุกคำตอบ + คำถามที่ค้าง + ไพ่ 10 ใบ + (optional) วันเกิด/ดาวเจ้าชนะ
     *   - Output: พาดหัวฟันธง → ตอบทีละคำถาม → บทสรุปรวม → คำแนะนำ → เคล็ด/ฤกษ์ → คำคมลา
     *
     * @param  Collection  $questions  คำถามที่ตอบไปแล้ว (มี response)
     * @param  Collection|null  $pendingQuestions  คำถามที่ถามแล้วยังไม่ได้คำตอบ — ต้องตอบปิดในบทนี้
     */
    protected function buildGrandFinalePrompt(
        FortuneReading $reading,
        array $cards,
        Collection $questions,
        ?FortuneReading $deepReading,
        ?Collection $pendingQuestions = null
    ): string {
        $pendingQuestions ??= collect();
        $brandName = $this->settings->fortune_brand_name ?: 'แม่หมอจันทรา';
        $cardsText = $this->formatCardsForPrompt($cards);

        // 🎯 ข้อความคำถามรวม (ตอบแล้ว + ค้าง) — ใช้ป้อน directive ตรวจหัวข้อ
        //    ⚠️ ต้องรวมข้อที่ค้างด้วย ไม่งั้นคำถามสุขภาพ/สายมู/คุณไสย์ที่ถามท้ายสุดแล้วค้าง
        //    จะไม่ทริกเกอร์ตำราที่เกี่ยว → บทสรุปตอบข้อนั้นแบบไม่มีองค์ความรู้หนุน
        $allQuestionText = $questions->pluck('question')
            ->merge($pendingQuestions->pluck('question'))
            ->implode(' ');

        // 🪬 (2026-06-30 FTU-260630-M8981) โหมดดูคุณไสย์ — บทสรุป VIP (และเสียงอ่านที่ดึงจากบทนี้)
        //   ต้องสรุปเรื่องของ/คุณไสย์ + วิธีแก้ ไม่ genericize เป็นดวงทั่วไป/เลข-สีมงคล
        $bmForced = $this->isBlackMagicModeForced($reading);
        $bmFinaleFraming = $bmForced
            ? "━━━━━━━━━━━━━━━━━\n"
                ."🪬 *บทสรุปนี้ = โหมดดูคุณไสย์/มนต์ดำ — ต้องสรุปเรื่อง \"ของ/คุณไสย์\" ที่คุยกันทั้งรอบ ไม่ใช่ดวงทั่วไป*\n"
                ."━━━━━━━━━━━━━━━━━\n"
                ."• ทุกย่อหน้า (รวมส่วนไล่ตอบทีละคำถาม) อ่านผ่านเลนส์ \"ภัยจากของ/พลังลบ/เกราะคุ้มครอง\" — ตอบทุกคำถามในกรอบเรื่องของ/คุณไสย์ ไม่ใช่ รัก/งาน/เงิน/สุขภาพทั่วไป\n"
                ."• *พาดหัว* = ฟันธง \"โดน/ไม่โดนของ + ชนิด\" (ไม่ใช่พาดหัวเรื่องเด่นรัก/งาน/เงิน)\n"
                ."• *บทสรุปรวม* = สรุปฟันธงสุดท้าย โดน/ไม่โดน + ความรุนแรง + แนวโน้มคลาย/ยังค้าง\n"
                ."• *ส่วนคำแนะนำ + ส่วนเคล็ด/ฤกษ์* = *วิธีแก้/ถอน/ป้องกัน + ทิศสะเดาะเคราะห์ทำเอง + เสริมเกราะ/บารมี* (เน้น \"การแก้ไข\" ที่เจ้าชะตาอยากรู้) — แทนคำแนะนำชีวิต/เลข-สีมงคล/ฤกษ์ทั่วไป\n"
                ."• ❌ ห้าม genericize เป็นเรื่องรัก/งาน/เงิน/สุขภาพทั่วไป — ถ้าไพ่ฟันธงว่า \"ไม่มีของ\" → สรุปในกรอบไสยศาสตร์ (ไม่มีของ แต่ให้เสริมเกราะ/ป้องกันแบบนี้) ❌ ไม่เปลี่ยนเป็นบทสรุปดวงทั่วไป\n"
                // 🪬 (2026-08-07) เดิมเนื้อคุณไสย์ 7 แง่กระจายอยู่ในย่อหน้าไล่ไพ่ 1-9 ที่ตัดออกไปแล้ว
                //   ถ้าไม่ระบุเป็นเช็คลิสต์ไว้ตรงนี้ บทสรุปโหมดคุณไสย์จะบางลงทันทีเมื่อลูกค้าถามน้อยข้อ
                ."\n🔒 *เช็คลิสต์บังคับ — ก่อนจบบทต้องครอบคลุมครบ 7 แง่* (สอดเข้าไปในคำตอบแต่ละข้อ + บทสรุปรวม ไม่ต้องทำเป็นหัวข้อแยก):\n"
                ."   1) โดนของจริงไหม — ฟันธง โดน/ไม่โดน\n"
                ."   2) ชนิดของ — เสน่ห์-ยาแฝด / ของกิน / ของฝัง / อาคม-ลงเลขยันต์ / วิญญาณ-ผีพราย / ตะปูผี ฯลฯ (ตามที่ไพ่ชี้)\n"
                ."   3) ลักษณะผู้ทำ — เพศ / ความสัมพันธ์ (คนใกล้ตัว-คู่แข่ง-อดีตคนรัก) / ช่วงวัย / ทิศที่มา  ❌ ห้ามมั่วชื่อจริง\n"
                ."   4) มูลเหตุที่ถูกทำ — อิจฉา / ชู้สาว-หึงหวง / ผลประโยชน์ / แค้นเก่า / ความหลง\n"
                ."   5) ความรุนแรง + จังหวะ — หนัก/กลาง/เบา · มาช่วงไหน · คลายแล้วหรือยังค้าง\n"
                ."   6) เกราะคุ้มครองที่เจ้าชะตามีอยู่ — สิ่งศักดิ์สิทธิ์/บุญเก่าที่ช่วยไว้ (ถ้าไพ่ชี้)\n"
                ."   7) วิธีแก้/ถอน/ป้องกัน ที่ทำเองได้จริง — ❌ ไม่พิธีแพง ❌ ไม่ขายความกลัว\n"
                ."   ⚠️ ถ้าไพ่ชี้ว่า \"ไม่มีของ\" → ข้อ 2-4 ตอบในกรอบไสยฯ ว่าทำไมถึงไม่ใช่ของ (แล้วอะไรคือต้นเหตุจริง) แล้วเน้นข้อ 6-7\n\n"
            : '';

        // สร้าง Q&A history ครบ + เช็คลิสต์คำถามที่ต้องตอบให้ครบ (กันตกหล่น = กันคาใจ)
        //   เช็คลิสต์แยกจาก history เพราะ AI ไล่ตาม list สั้นๆ ได้ครบกว่าไล่จากบทสนทนายาว
        $qaHistory = '';
        $questionChecklist = '';
        $checklistNo = 0;

        foreach ($questions as $i => $q) {
            $idx = $i + 1;
            $question = mb_substr($q->question, 0, 500);
            $answer = mb_substr($q->response ?? '', 0, 1500);
            $qaHistory .= "Q{$idx}: {$question}\n";
            $qaHistory .= "A{$idx}: {$answer}\n\n";

            $checklistNo++;
            $questionChecklist .= "   {$checklistNo}. ".$this->shortenQuestionForChecklist($q->question)."\n";
        }

        if ($qaHistory === '') {
            $qaHistory = "   (รอบนี้ยังไม่มีคำถามข้อไหนได้รับคำตอบ — บทสรุปนี้คือคำตอบครั้งแรกและครั้งเดียวที่เจ้าชะตาจะได้รับ)\n\n";
        }

        // ⏳ คำถามที่ถามแล้วยังไม่ได้คำตอบ (หมดเวลา/รอบปิดตัดก่อน/AI ล้ม) — ต้องตอบปิดในบทนี้
        //   ⚠️ ข้ามคำถามที่เป็น "รูปภาพล้วน" — รูปไม่ได้อยู่ใน context ของบทสรุป
        //      ถ้าดันให้ตอบ AI จะมโนสิ่งที่อยู่ในรูป (แย่กว่าไม่ตอบ)
        $pendingBlock = '';
        foreach ($pendingQuestions as $q) {
            $textOnly = trim(str_replace('[IMAGE_ATTACHED]', '', (string) $q->question));
            if ($textOnly === '') {
                continue;
            }

            $checklistNo++;
            $questionChecklist .= "   {$checklistNo}. ".$this->shortenQuestionForChecklist($textOnly)."   ⚠️ ยังไม่ได้ตอบ\n";
            $pendingBlock .= '   • '.mb_substr($textOnly, 0, 500)."\n";
        }

        if ($pendingBlock !== '') {
            $pendingBlock = "⏳ *คำถามที่เจ้าชะตาถามทิ้งไว้แต่ยังไม่ได้รับคำตอบ* (รอบปิดก่อน):\n"
                .$pendingBlock
                ."👉 ข้อเหล่านี้ต้องได้คำตอบ *เต็มรูปแบบ ฟันธง* ในบทสรุปนี้ — ห้ามข้าม ห้ามตอบสั้นกว่าข้ออื่น\n"
                ."   ❌ ห้ามพูดถึงเรื่อง \"หมดเวลา/ตอบไม่ทัน/ไว้คราวหน้า\" — เจ้าชะตาจ่ายแล้ว ต้องได้คำตอบตรงนี้\n"
                ."━━━━━━━━━━━━━━━━━\n\n";
        }

        if ($questionChecklist === '') {
            $questionChecklist = "   (ไม่มีคำถามระบุชัด — ให้สรุปภาพรวมชีวิตช่วงนี้ของเจ้าชะตาแบบฟันธงแทน)\n";
        }

        // 🌟 (2026-09-01) ผังดวงจริง — เดิม "บทสรุปใหญ่" เป็นจุดเดียวในเลน 99฿ ที่ผังดวงไปไม่ถึง
        //
        //   ⚠️ ของเดิม: $deepContext ติดป้ายว่า "ดาว/ราศี/ลัคนา/ดวงดาวเจ้าชนะ" แต่สิ่งที่ยัดเข้าไป
        //     คือ deep_response 800 ตัวแรก = *ข้อความคำทำนายเก่า* ไม่ใช่ผังดวง — ซ้ำร้ายคำทำนาย 39฿
        //     ตัวนั้นเองก็ไม่เคยมีดวง (prompt_template ใน DB ไม่มี {birth_date_section})
        //     ⇒ ป้ายสัญญาว่ามีโหราศาสตร์ แต่ข้างในไม่มีจริง + คนที่ซื้อ 99 อย่างเดียวไม่ได้อะไรเลย
        //
        //   ตอนนี้คำนวณผังจริงเองเหมือน 3 จุดแรกของเลนนี้ (พื้นดวงเปิดตัว / Q1 / Q2+)
        $finaleAstroBlock = $this->buildBirthAstrologyBlockFor($reading, $allQuestionText);
        $finaleAstroContext = $finaleAstroBlock !== ''
            ? "\n━━━━━━━━━━━━━━━━━\n"
                ."🌟 ผังดวงของเจ้าชะตา (แม่หมอคำนวณเอง — ใช้ผสมกับไพ่ในบทสรุปนี้)\n"
                ."━━━━━━━━━━━━━━━━━\n"
                .$finaleAstroBlock."\n"
                ."👉 ผสมดวงกับไพ่ให้คำตอบแต่ละข้อแน่นกว่ารอบถามตอบ เช่น เชื่อมจังหวะดาว/ดาวเสวยอายุ\n"
                ."    เข้ากับแนวโน้มที่ไพ่ตำแหน่งอนาคตชี้ → ระบุช่วงเดือนได้แม่นขึ้น\n"
                ."    (ใช้เป็นเนื้อคำตอบ — ❌ ห้ามบรรยายองศา/ตัวเลข ❌ ห้ามอธิบายว่ามาจากไพ่ใบไหน)\n\n"
            : '';

        // คำทำนายเดิมจากบิล 39฿ (ถ้ามี) — เป็น "บริบทว่าเคยตอบอะไรไป" ไม่ใช่แหล่งข้อมูลดวง
        $deepContext = '';
        if ($deepReading) {
            $birthDate = $deepReading->birth_date?->format('d/m/Y') ?? '-';
            $deepResponse = mb_substr((string) $deepReading->deep_response, 0, 800);
            $deepContext = "\n━━━━━━━━━━━━━━━━━\n"
                ."📜 คำทำนายเดิมจากบิลดูดวงพื้นฐาน 39฿ ครั้งก่อน (วันเกิด: {$birthDate}):\n"
                .$deepResponse."\n"
                ."━━━━━━━━━━━━━━━━━\n\n"
                ."👉 ใช้เป็น *บริบทว่าเคยบอกอะไรไปแล้ว* — บทสรุปนี้ต้องต่อยอด ไม่กลับคำ ไม่ลอกซ้ำ\n"
                ."    ⚠️ ข้อเท็จจริงเรื่องดาว/ราศี/ภพ ให้ยึด \"ผังดวง\" ด้านบนเท่านั้น ไม่ใช่จากข้อความนี้\n\n";
        }

        // ปัจจุบัน — เดือน/ฤดู (ใช้ผูกช่วงเวลา)
        $now = now();
        $monthTh = ['', 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
            'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
        $currentMonth = $monthTh[(int) $now->format('n')];
        $currentYearBE = (int) $now->format('Y') + 543;

        // 🇱🇦 (2026-05-04 fix B2) Lao locale directive — ลูกค้าลาวต้องได้บทสรุปเป็นลาว
        //    เดิม: prompt ฮาร์ดโค้ดไทย → AI ตอบไทย ไม่ mirror ตามภาษาลูกค้า
        //    เคสคือ Lao customer paid 99฿ → Q&A AI ตอบลาว → จบ session → Grand Finale กลับเป็นไทย
        $isLao = FortuneLocaleService::current() === FortuneLocaleService::LOCALE_LO;
        $localeDirective = '';
        if ($isLao) {
            $localeDirective = "[🇱🇦 ພາສາ — ສຳຄັນທີ່ສຸດ / Language directive — OVERRIDES ALL RULES BELOW]\n"
                ."ລູກຄ້າຄົນນີ້ໃຊ້ພາສາລາວ → **ຂຽນບົດສະຫຼຸບສຸດທ້າຍເປັນພາສາລາວທັງໝົດ** (Reply in LAO, not Thai)\n"
                ."- ຫ້າມຕອບເປັນພາສາໄທ ເຖິງວ່າຕົວຢ່າງໃນ prompt ດ້ານລຸ່ມຈະເປັນພາສາໄທ\n"
                ."- ຄຳເອີ້ນຕົນເອງ: 'ແມ່ໝໍຈັນທະຣາ' (ບໍ່ແມ່ນ 'แม่หมอจันทรา')\n"
                ."- ຄຳເອີ້ນລູກຄ້າ: 'ເຈົ້າຊາຕາ' / 'ທ່ານ' (ບໍ່ແມ່ນ 'เจ้าชะตา')\n"
                ."- ໃຊ້ຄຳລົງທ້າຍລາວເຊັ່ນ 'ເດີ້' / 'ເນາະ' / 'ນັ້ນແຫລະ' (ບໍ່ແມ່ນ 'ค่ะ/คะ/นะคะ')\n"
                ."- ບົດສະຫຼຸບ 6 ຍ່ອໜ້າຂ້າງລຸ່ມ — ໂຄງສ້າງເໝືອນ ແຕ່ເນື້ອຄວາມເປັນພາສາລາວ\n\n";
        }

        // 🪶 (2026-08-07 owner: "prompt บวมไป ทำให้กระชับ") บทสรุป = LEAN ASSEMBLY
        //   หลักการ: บทนี้ "ไม่อธิบายไพ่" แล้ว → เครื่องมือที่มีไว้ *อ่านไพ่* ไม่ต้องมีในบทนี้
        //   การอ่านไพ่เกิดครบแล้วตอนถามตอบ และคำตอบเหล่านั้นอยู่ใน Q&A history ด้านล่างแล้ว
        //   ❌ ตัดออก: buildCardComboDirective / buildSpreadPatternDirective /
        //             buildElementalDignityDirective / buildPositionDynamicDirective (≈6-8k ตัว)
        //             — ทั้ง 4 เป็นกลไก "หาคำทำนายจากไพ่" ไม่ใช่ "เขียนบทสรุป"
        //   ✂️ ย่อ: buildCardFirstMandate(lean) 5,438 → ~900 ตัว
        //   🎯 คลังความรู้: เอาเฉพาะไพ่ตำแหน่งหลัก (หัวใจ/อุปสรรค/อนาคตอันใกล้/ผลลัพธ์)
        //      ย่อหน้าท้ายต้องการ เลข/สี/ฤกษ์ *ชุดเดียว* — ให้ 10 ชุดคือให้โมเดลเลือกเอง = ทางมั่วอีกทาง
        //   ✅ คงไว้เต็ม: คำถาม + คำตอบเดิมทั้งรอบ (owner: ต้องเช็คไม่ให้ขัดกันเอง) + ตำราที่ลูกค้าถามจริง
        //
        // 🎯 (2026-08-07) ตำแหน่งไพ่ไม่ fix แล้ว — Question Router เลือกจาก "คำถามจริงทั้งรอบ"
        //    (เดิม hardcode [1,2,6,10] ไม่ว่าลูกค้าถามอะไร)
        $finaleRoute = $this->routeFinaleQuestions(
            $questions->pluck('question')->merge($pendingQuestions->pluck('question'))->all()
        );
        $keyPos = $finaleRoute['positions'];

        return $localeDirective
            .$this->buildCardNamingDirective()
            .$this->buildCardFirstMandate(lean: true)
            // 🩺 (2026-06-01) ตำราสุขภาพ — ถ้ามีคำถามสุขภาพในรอบนี้ ให้บทสรุปเทียบอวัยวะ/อาการตามไพ่
            .$this->buildHealthDirective($reading, $allQuestionText)
            .$this->buildMuKnowledgeDirective($reading, $allQuestionText, '', $keyPos)
            // 🧧 (2026-06-28) สายมู (เคล็ด/เลข/สี/ฤกษ์/เครื่องราง) — ย้ายจากรอบถามตอบมาที่บทสรุปนี้ (owner directive)
            .$this->buildSaiMuDirective($reading, $allQuestionText)
            .$this->buildPhysiognomyDirective($reading, $allQuestionText)
            .$this->buildPersonRoleDirective($reading, $allQuestionText)
            .$this->buildLifeReadingDirective($reading, $allQuestionText, '', $keyPos)
            .$this->buildDestinyDirective($reading, $allQuestionText, '', $keyPos)
            // 🪬 (2026-06-30) โหมดดูคุณไสย์ — ฉีดกฎ + คลังไสยศาสตร์รายไพ่ ให้บทสรุปอ่านสุดเรื่องของ (เฉพาะ forced — กันกระทบบทสรุปปกติ)
            .($bmForced ? $this->buildBlackMagicDirective($reading, $allQuestionText, $qaHistory) : '')
            // 🧭 (2026-08-07) บังคับดึงคลัง ฤกษ์/เลข/ของมงคล-สี/แก้กรรม เสมอในบทสรุป
            //   เพราะย่อหน้าท้าย "เคล็ด/ฤกษ์/เลข/สี/ทิศ" มีตายตัวทุกบท ไม่ว่าลูกค้าจะถามหรือไม่
            .$this->buildExtraKnowledgeDirectives($reading, $allQuestionText, '', [
                'enable_celtic_auspicious',
                'enable_celtic_numerology',
                'enable_celtic_lucky_items',
                'enable_celtic_remedy',
            ], $keyPos)
            // 🎯 (2026-08-07) ชี้เป้าไพ่ที่บทสรุปต้องใช้ (คิดในใจ — บทนี้ไม่เอ่ยชื่อไพ่)
            .(! empty($finaleRoute['route'])
                ? $this->buildQuestionRoutingDirective($reading, $allQuestionText, true, $finaleRoute['route'])
                : '')
            .$this->buildYesNoDirective($reading, $allQuestionText)
            ."คุณคือ \"{$brandName}\" — *นักพยากรณ์ชั้นปรมาจารย์ระดับเซียน* ผ่านการดูชะตาคนมาเป็นพันคน 30+ ปี\n"
            ."สถานะ: คุณกำลังจะปิดบทสนทนากับเจ้าชะตาท่านนี้ — ขณะนี้คือ *บทสรุปสุดท้ายระดับศาสตร์ลึก*\n"
            ."บุคลิก: สุขุม นิ่ง อบอุ่น เปี่ยมพลัง พูดน้อยแต่แทงใจดำ — เห็นเหมือนตาเห็น\n\n"

            ."━━━━━━━━━━━━━━━━━\n"
            ."🃏 ไพ่ Celtic Cross 10 ใบที่เจ้าชะตาเลือก:\n{$cardsText}\n"
            ."━━━━━━━━━━━━━━━━━\n\n"

            ."💬 บทสนทนาที่คุยกันมาทั้งหมด ({$questions->count()} คำถามที่ตอบไปแล้ว):\n"
            ."⚓ *นี่คือสิ่งที่แม่หมอพูดไปแล้วในรอบนี้ — ต้องอ่านก่อนเขียน:*\n"
            ."   • ❌ *ห้ามขัดกับคำตอบเดิม* (เคยบอกว่า \"ได้\" แล้วบทสรุปบอก \"ไม่ได้\" = เจ้าชะตาจับได้ทันที เสียความน่าเชื่อถือทั้งรอบ)\n"
            ."   • ถ้าไพ่ชี้ต่างจากที่เคยตอบจริงๆ → *ขยายความให้ละเอียดขึ้น* ไม่ใช่กลับคำ (\"ที่บอกว่าได้ — ได้จริง แต่ต้องผ่าน...ก่อน\")\n"
            ."   • ใช้จับ \"ตอนนี้กำลังคุยเรื่องอะไรกันอยู่\" → บทสรุปต้องต่อจากเรื่องนั้น ไม่ใช่เริ่มเรื่องใหม่\n"
            ."   • ❌ ห้ามลอกประโยคเดิมมาวางซ้ำ — บทสรุปต้อง *สั้นกว่า ชัดกว่า ฟันธงกว่า* คำตอบเดิม\n\n"
            ."{$qaHistory}"
            ."━━━━━━━━━━━━━━━━━\n"
            .$pendingBlock
            .$finaleAstroContext
            .$deepContext
            ."📆 บริบทช่วงเวลาปัจจุบัน: เดือน{$currentMonth} ปี {$currentYearBE} — ใช้ผูกการทำนายช่วงเวลา\n\n"

            ."✍️ ภารกิจ: ปิดรอบด้วยบทสรุประดับ *ปรมาจารย์ฟันธง* —\n"
            ."   *รวบทุกคำถามที่เจ้าชะตาถามมาตลอดรอบ มาตอบให้จบทีละข้อ ไม่เหลือค้างคาใจแม้ข้อเดียว*\n\n"

            ."━━━━━━━━━━━━━━━━━\n"
            ."🚨 กฎ 3 ข้อของบทสรุปนี้ (สำคัญกว่าทุกกฎด้านบน — ถ้าขัดกัน ให้ยึด 3 ข้อนี้)\n"
            ."━━━━━━━━━━━━━━━━━\n"
            ."1️⃣ *ห้ามอธิบายไพ่* — เจ้าชะตาอ่านคำอธิบายไพ่มาครบแล้วในรอบถามตอบ รอบนี้เอาแต่ *คำตอบ*\n"
            ."   ❌ ห้ามเอ่ยชื่อไพ่  ❌ ห้ามเอ่ยชื่อ/เลขตำแหน่ง (หัวใจของเรื่อง/อุปสรรค/ผลลัพธ์/ใบที่ 6 ฯลฯ)\n"
            ."   ❌ ห้ามไล่บรรยายทีละใบ  ❌ ห้ามขึ้นประโยคว่า \"ไพ่บอกว่า...\" / \"จากไพ่ที่เปิดมา...\"\n"
            ."   ✅ แต่เนื้อหาทุกประโยค *ยังต้องงอกจากไพ่จริง* ตามกฎเหล็กด้านบน — แค่ไม่โชว์ที่มา\n"
            ."      พูดออกมาเป็นคำฟันธงของแม่หมอตรงๆ: \"เรื่องนี้จะจบลงแบบ... ราวเดือน...\"\n"
            ."   ⚠️ *ทุกบล็อกความรู้/ตำราด้านบน = เครื่องมือคิดในใจ* — ใช้หา \"คำตอบ\" แล้วพูดออกมาเฉพาะคำตอบ\n"
            ."      บล็อกพวกนั้นเขียนว่า \"อ่านจากไพ่แต่ละใบตามตำแหน่ง\" = *วิธีคิด* ไม่ใช่ *วิธีเขียน*\n"
            ."      และบรรทัดคลังที่ขึ้นต้นว่า \"ตำแหน่ง N [ชื่อตำแหน่ง] — ชื่อไพ่\" = รูปแบบข้อมูลภายใน ❌ ห้ามลอกออกไปในบท\n"
            ."      ✅ ความรู้ในคลัง (เลข/สี/ฤกษ์/ทิศ/อวัยวะ/ลักษณะคน) *ต้องใช้ตามที่คลังให้มา* — ❌ ห้ามคิดตัวเลข/สี/ฤกษ์เอง\n\n"

            ."2️⃣ *ตอบให้ครบทุกคำถาม* — ทุกข้อในเช็คลิสต์ด้านล่างต้องมีคำตอบฟันธงในบทสรุปนี้\n"
            ."   ก่อนจบ ให้ไล่เช็คในใจว่าครบทุกข้อจริง — ข้อที่ตกหล่น = งานเสีย\n\n"

            ."3️⃣ *ห้ามคาใจ* — ทุกคำตอบต้อง \"ปิดจบ\" ไม่ทิ้งปลายเปิด\n"
            ."   ❌ ห้าม \"แล้วแต่/ขึ้นอยู่กับ/ต้องดูต่อ/อาจจะ/คงจะ\"  ❌ ห้ามบอกให้ไปถามใหม่/ดูดวงเพิ่ม\n"
            ."   ✅ ข้อไหนที่รอบถามตอบเคยตอบกำกวม เลี่ยง หรือตอบไม่ตรงคำถาม → *รอบนี้ต้องฟันธงให้ชัด*\n"
            ."   ✅ ทุกข้อต้องมีอย่างน้อย: คำตอบชัด (ใช่/ไม่ใช่ • ได้/ไม่ได้ • มา/ไม่มา) + เมื่อไหร่ + เพราะอะไร\n\n"

            ."━━━━━━━━━━━━━━━━━\n"
            ."📋 เช็คลิสต์คำถามที่ต้องตอบให้ครบ ({$checklistNo} รายการ):\n"
            .$questionChecklist
            // 🐛 (2026-08-07 FTU-260807-X6521) เดิมเขียนว่า "ข้ามได้" (อ่อนไป) → บทสรุปจริงเสีย 2 ย่อหน้า
            //   ไปตอบ "ข้อสี่ที่บอกว่ากดผิดเป็น 1 — รับทราบ" + "ข้อหก เจ็ด แปด ที่เป็นคำขอบคุณ"
            //   พื้นที่นั้นควรใช้เจาะคำถามจริงให้ลึกกว่า
            ."   🚫 *รายการที่ไม่ใช่คำถามจริง* (\"พร้อม\" / \"ขอบคุณ\" / \"ค่ะ\" / \"กดผิด\" / \"หมดคำถามแล้ว\" / คำตอบรับ)\n"
            ."      = *ห้ามทำเป็นย่อหน้าตอบ* และห้ามเอ่ยถึงเลย — เอาพื้นที่ไปเจาะคำถามจริงให้ลึกขึ้นแทน\n"
            ."━━━━━━━━━━━━━━━━━\n\n"

            ."🌟 โครงสร้างบทสรุป (ย่อหน้าแยก ไม่มีหัวข้อ ไม่มี markdown):\n\n"

            .$bmFinaleFraming

            ."ย่อหน้า 0 — *พาดหัวเรื่องเด่นที่สุด (ฟันธง — มาก่อนทักทาย)* (40-70 คำ):\n"
            ."   🎯 *ในใจ*: สแกนไพ่ทั้ง 10 ใบ จับ \"เรื่องที่ส่งเสียงดังที่สุด\" เพียง 1 เรื่อง (ดี/ร้ายก็ได้)\n"
            ."      โดยถ่วงน้ำหนักไพ่ผลลัพธ์(ต.10) + อนาคตอันใกล้(ต.6) เป็นแกน\n"
            ."   ✍️ *ที่เขียนออกมา*: พูดเรื่องนั้นตรงๆ เป็นคำฟันธงของแม่หมอ — อะไร/ใคร/เมื่อไหร่/มากน้อย/แหล่ง\n"
            ."      ❌ ห้ามเอ่ยว่ามาจากไพ่หรือตำแหน่งไหน (\"ไพ่ทั้ง 10 ใบบอกว่า...\" = ผิด → \"เรื่องที่ต้องรู้ก่อนเลยคือ...\" = ถูก)\n"
            ."   เคราะห์หนัก (สูญเสีย/ป่วย/อุบัติเหตุ) → เตือนตรง+ทางแก้จริง / เด่นบวก (โชคลาภ/เลื่อนขั้น/เนื้อคู่) → บอกชัดมาเมื่อไหร่-จากทางไหน\n"
            ."   ❌ ห้ามเปิดด้วยคำกลางๆ/ทักทายก่อน — พาดหัวฟันธงต้องเป็นบรรทัดแรกสุดของบทสรุป (เรื่องโดนของ = ทักเฉพาะที่ไพ่ชี้จริง ไม่ขายความกลัว)\n\n"

            ."ย่อหน้า 1 — *ทักทายและโยงรวมประเด็นที่ถามมา* (50-80 คำ):\n"
            ."   เปิดด้วยคำเรียกอบอุ่น (เจ้าชะตา/เธอ) → พูดถึงเรื่องที่ถามมาทั้งรอบ ผูกเป็นเส้นเรื่องเดียว\n"
            ."   ไม่ใช่ \"คำถาม 1 คือ A คำถาม 2 คือ B\" — ต้องเล่าให้เห็นว่าทุกเรื่องที่ถามมาโยงกันยังไง\n"
            ."   ปิดย่อหน้าด้วยประโยคทำนองว่า \"รอบนี้แม่หมอจะตอบให้ครบทุกข้อที่เธอถามมา ไม่ให้ค้างคาใจ\"\n\n"

            ."━━━━━━━━━━━━━━━━━\n"
            ."🎯 ย่อหน้า 2 เป็นต้นไป — *ไล่ตอบทีละคำถาม (แกนหลักของบทสรุปนี้)*\n"
            ."━━━━━━━━━━━━━━━━━\n"
            ."   **กฎสำคัญ:**\n"
            ."   ✅ 1 ย่อหน้า = 1 คำถาม เรียงตามลำดับในเช็คลิสต์ด้านบน — ย่อหน้าละ 90-140 คำ\n"
            ."   ✅ เปิดย่อหน้าด้วยการ *ทวนสิ่งที่ถามสั้นๆ ด้วยภาษาแม่หมอ* แล้วตามด้วย *คำตอบฟันธงทันที*\n"
            ."      เช่น \"ที่เธอถามว่าจะได้กลับไปคืนดีกันไหม — คำตอบคือ ได้ แต่ไม่ใช่รอบนี้ ต้องรอราวเดือน...\"\n"
            ."      เช่น \"เรื่องเงินก้อนที่รออยู่ — เข้าแน่ ราวปลายเดือน... แต่จะเข้าไม่เต็มจำนวนที่หวัง\"\n"
            ."   ✅ ทุกข้อต้องครบ 4 อย่าง: *คำตอบชัด* + *ช่วงเวลา* + *เหตุ/เงื่อนไขที่ทำให้เกิดหรือไม่เกิด* + *สิ่งที่ต้องทำ*\n"
            ."   ✅ ข้อที่ติดป้าย ⚠️ ยังไม่ได้ตอบ → ตอบเต็มรูปแบบเท่าข้ออื่น ห้ามตอบผ่านๆ\n"
            ."   ✅ *ถามเยอะ* → รวบข้อที่เป็นเรื่องเดียวกันเข้าย่อหน้าเดียว: 7-10 ข้อ → ไม่เกิน 6 ย่อหน้า · เกิน 10 ข้อ → ไม่เกิน 8 ย่อหน้า\n"
            ."      ⚠️ รวบ = *เขียนรวมย่อหน้า* ไม่ใช่ *ตอบน้อยลง* — ทุกข้อในเช็คลิสต์ต้องมี \"ประโยคคำตอบของตัวเอง\"\n"
            ."      อยู่ในย่อหน้าที่รวมกับข้ออื่นได้ แต่ต้องชี้ได้ว่าประโยคไหนคือคำตอบของข้อไหน (ไล่นับให้ครบก่อนจบ)\n"
            ."   ✅ *ถ้ามีคำถามจริงแค่ 1-2 ข้อ → ห้ามจบสั้น* — เจ้าชะตาจ่าย 99฿ เท่ากับคนที่ถามเยอะ\n"
            ."      → แตกเรื่องนั้นเป็น 3-4 ย่อหน้าตามแง่มุม: *ต้นเหตุ* / *คนที่เกี่ยวข้อง* / *จังหวะเวลา* / *ทางออก*\n"
            ."      → แล้วเติมเรื่องสำคัญที่ไพ่ชี้แรงแม้เจ้าชะตาไม่ได้ถาม (สิ่งที่ควรรู้ก่อนจบรอบ) อีก 1-2 ย่อหน้า\n"
            ."   ❌ ห้ามลอกคำตอบเดิมจากรอบถามตอบมาซ้ำคำต่อคำ — รอบนี้ต้อง *สั้นกว่า ชัดกว่า ฟันธงกว่า*\n"
            ."   ❌ ห้ามเอ่ยชื่อไพ่/ตำแหน่งไพ่ในย่อหน้าเหล่านี้ (ตามกฎข้อ 1️⃣) — เอาแต่คำตอบ\n\n"

            ."━━━━━━━━━━━━━━━━━\n"
            ."🏁 ย่อหน้าถัดมา — *คำตอบสุดท้ายของทั้งรอบ (บทสรุปรวม)*\n"
            ."━━━━━━━━━━━━━━━━━\n"
            ."   นี่คือ *หัวใจของบทสรุป* — ปลายทางของเรื่องทั้งหมดที่คุยกันมา จะจบลงยังไง\n"
            ."   ✅ ฟันธง 1 ภาพใหญ่ — ชีวิตช่วงนี้ของเจ้าชะตากำลังเดินไปทางไหน และจะลงเอยแบบไหน\n"
            ."   ✅ ระบุช่วงเวลาที่จะเห็นผล (ผูกกับเดือน{$currentMonth} ปี {$currentYearBE})\n"
            ."   ✅ ถ้ามีข้อมูลโหราศาสตร์ 39฿ → เชื่อมจังหวะดาวให้ระบุเดือนแม่นขึ้น\n"
            ."   ✅ ความยาว 100-150 คำ — กระชับแต่หนักแน่น\n"
            ."   ❌ ไม่เอ่ยไพ่/ตำแหน่ง — พูดเป็นคำฟันธงของแม่หมอตรงๆ\n\n"

            ."ย่อหน้าถัดมา — *คำแนะนำที่ใช้ได้จริง 3 ข้อ* (60-100 คำ):\n"
            ."   ข้อปฏิบัติเฉพาะตัวที่ตรงกับลักษณะบุคคลและสิ่งที่ไพ่ชี้ (ไม่เอ่ยว่ามาจากไพ่)\n"
            ."   1. สิ่งที่ \"หยุดทำ\" / 2. สิ่งที่ \"เริ่มทำ\" / 3. \"เช็คใจตัวเอง\" ทุก X วัน\n"
            ."   ไม่ใช่นามธรรม (\"ดูแลใจ\" ✗) → ต้อง concrete (\"หยุดเช็คเฟส 7 วัน\" ✓)\n\n"

            // 📅⚠️ (2026-06-19 FTU-260619-C9002) ย้าย "ฤกษ์/สีมงคล + สิ่งที่ต้องระวัง" จาก Q1 (พื้นดวงเปิดตัว)
            //   มาไว้บทสรุป VIP เท่านั้น ตาม owner — Q1 เพิ่งเปิดดวงไม่ควรมีบทปิด/มงคล
            ."ย่อหน้าถัดมา — *สิ่งที่ต้องระวัง + เคล็ด/ฤกษ์/เลข/ของมงคลเฉพาะตัว* (รวมของท้ายที่เลื่อนมาจากรอบถามตอบทั้งหมด) (60-90 คำ):\n"
            ."   ⚠️ สิ่งที่ต้องระวัง (จากดาวศัตรู + สัญญาณเตือนที่ไพ่ชี้) + ทางแก้/เสริมดวง/เคล็ดที่ทำเองได้ (❌ ไม่ขายความกลัว ไม่พิธีแพง)\n"
            ."   📅 วัน/ช่วงเวลามงคล + สีมงคล + เลขมงคล/เลขเสี่ยงโชค + ทิศ — สั้น กระชับ ตรงดวงคนนี้ (ไม่มั่วตัวเลขถ้าไพ่/ดาวไม่ได้ชี้)\n\n"

            ."ย่อหน้าสุดท้าย — *คำคมจากลาส่วนตัว* (40-60 คำ — signature):\n"
            ."   ✅ ต้องตรงกับอุปนิสัยและช่วงอายุของเจ้าชะตา\n"
            ."   ✅ ใช้ภาษาเปี่ยมความหมาย — เปรียบเทียบกับธรรมชาติ/วิถีชีวิต\n"
            ."   ✅ ห้ามทั่วไป (\"ขอให้โชคดี\", \"ทุกอย่างจะดีขึ้น\") — ต้องเฉพาะคนคนนี้เท่านั้น\n"
            ."   ตัวอย่าง: คนวัยกลางคน + ธาตุน้ำ → \"แม่น้ำที่เคยเชี่ยวกราก เมื่อพบทะเลก็ต้องนิ่งสงบ — เจ้าชะตาถึงจุดที่ใจต้องเรียนรู้สงบ\"\n"
            ."   ปิดด้วย \"แม่หมอขอลาเจ้าชะตาเพียงเท่านี้ ขอให้... 🙏✨\"\n\n"

            ."🚫 ข้อห้ามเด็ดขาด:\n"
            ."   1. ห้ามใช้ markdown (** ## - ฯลฯ) — plain text ล้วน\n"
            ."   2. **ห้ามพูดถึงไพ่ทุกรูปแบบ** — ไม่เอ่ยชื่อไพ่ ไม่เอ่ยตำแหน่ง ไม่ไล่บรรยายทีละใบ\n"
            ."      ❌ \"ไพ่ X ปรากฏที่ตำแหน่ง Y\" / \"ตำแหน่ง 1 ได้ไพ่...\" / \"ไพ่บอกว่า...\" / \"หัวใจของเรื่องนี้คือ...\"\n"
            ."      ❌ *ห้ามปิดท้ายด้วยบรรทัด \"🃏 ไพ่ที่จับได้: ...\"* หรือยกไพ่ใบใดใบหนึ่งมาอธิบายท้ายบท (เจอหลุดจริงมาแล้ว)\n"
            ."      → พูดเป็นคำตอบตรงๆ ของแม่หมอแทน: \"เรื่องนี้คำตอบคือ...\" / \"สิ่งที่ขวางเธออยู่คือ...\"\n"
            ."   3. **ห้ามข้ามคำถาม** — ทุกข้อในเช็คลิสต์ต้องมีคำตอบ (ยกเว้นรายการที่ไม่ใช่คำถามจริง)\n"
            ."   4. ห้ามทักทายว่า \"สวัสดี\" — เริ่มด้วย \"เจ้าชะตา...\" หรือชื่อโดยตรง\n"
            ."   5. ห้ามคำเลี่ยง \"อาจจะ/น่าจะ/บางที\" — ฟันธง\n"
            ."   6. ห้ามใส่ [END_SESSION] หรือ token พิเศษใดๆ\n"
            ."   7. ห้ามขายของ ขอติดตาม ขอแชร์ — บทสุดท้ายต้องสุขุม สง่างาม\n"
            ."   8. ห้ามใช้คำว่า \"ไลฟ์โค้ช\" / \"life coach\" — ใช้ \"แม่หมอ\" หรือ \"ที่ปรึกษา\" แทน\n\n"

            ."📏 ความยาวรวม: 900-1500 คำ (พาดหัว + ทักทาย + ตอบทีละคำถาม + บทสรุปรวม + คำแนะนำ + เคล็ด + คำคมลา)\n"
            ."🎭 โทน: ปรมาจารย์สุขุมอบอุ่น พูดน้อยแต่ลึก ฟันธง เนื้อๆ ไม่น้ำ\n\n"

            .'เริ่มเขียนบทสรุปสุดท้ายเลย (อย่าทักทายซ้ำ อย่าใส่ "นี่คือบทสรุป..."):';
    }

    /**
     * 🔮 สร้างข้อความ "นำลูกค้ากลับมาที่จุดเดิม" สำหรับ Celtic active state
     *
     * ใช้กับ 2 เคสหลัก:
     *   1. ลูกค้าส่งรูป/สลิป/sticker ระหว่างกลาง flow → ตอบเตือนแทน silent
     *   2. ลูกค้าหายไปแล้วกลับมา (พิมพ์อะไรก็ตาม) → resume ที่ position เดิม
     *
     * Returns ['message' => str, 'quick_replies' => array]
     * Caller responsible สำหรับการส่ง — ใช้ FB sendMessage หรือ LINE replyMessage
     *
     * @param  string  $reason  context สำหรับปรับ wording (image|sticker|generic|reentry)
     */
    public function buildResumeMessage(FortuneReading $reading, string $reason = 'generic'): array
    {
        $status = $reading->conversation_status;
        $billRef = $reading->bill_reference ?? '-';

        // Header ตาม reason
        $header = match ($reason) {
            'image' => "📸 รับรูปแล้วค่ะ — แต่ตอนนี้เจ้าชะตาอยู่ในรอบดูดวงไพ่ Celtic นะคะ\n\n",
            'sticker' => "💖 ขอบคุณค่ะ — แต่ตอนนี้เจ้าชะตาอยู่ในรอบดูดวงไพ่ Celtic นะคะ\n\n",
            'reentry' => "🌙 ยินดีต้อนรับกลับมาค่ะเจ้าชะตา — แม่หมอรอเจ้าชะตาอยู่\n\n",
            default => "🔮 เจ้าชะตาอยู่ในรอบดูดวงไพ่ Celtic Cross อยู่นะคะ\n\n",
        };

        return match ($status) {
            FortuneReading::STATUS_CELTIC_PICKING => $this->buildPickingResume($reading, $header, $billRef),
            FortuneReading::STATUS_CELTIC_AWAITING_QUESTION => $this->buildAwaitingQuestionResume($reading, $header, $billRef),
            FortuneReading::STATUS_CELTIC_QA_PROMPT => $this->buildAwaitingQuestionResume($reading, $header, $billRef),
            FortuneReading::STATUS_CELTIC_GENERATING => [
                'message' => $header
                    ."🔮 *แม่หมอกำลังพิจารณาไพ่ทั้ง 10 ใบให้เจ้าชะตาอยู่*\n"
                    ."ใช้เวลาประมาณ 30-60 วินาที — รอสักครู่นะคะ ✨\n\n"
                    ."📋 บิลของเจ้าชะตา: {$billRef}",
                'quick_replies' => [],
            ],
            default => [
                'message' => $header.'💬 พิมพ์ข้อความถึงแม่หมอได้เลยค่ะ',
                'quick_replies' => [],
            ],
        };
    }

    /**
     * Resume message สำหรับ STATUS_CELTIC_PICKING
     */
    protected function buildPickingResume(FortuneReading $reading, string $header, string $billRef): array
    {
        $picked = $reading->getCelticPickedCount();
        $next = $reading->getNextCelticPosition();

        if ($next === null) {
            return [
                'message' => $header.'✨ เจ้าชะตาเปิดไพ่ครบ 10 ใบแล้ว — พิมพ์คำถามที่อยากรู้มาได้เลยค่ะ',
                'quick_replies' => [],
            ];
        }

        $meta = FortuneReading::CELTIC_POSITIONS[$next] ?? null;
        $name = $meta['name'] ?? '?';
        $desc = $meta['description'] ?? '';
        // 🔢 (2026-08-17 owner) บอกเลขใบทุกครั้ง — ตรงกับปุ่มขั้นเปิดไพ่ใน FortuneChannelManager
        //   ที่นี่ $next ยืนยันแล้วว่าไม่ null (early return ด้านบนเมื่อครบ 10 ใบ)
        $btnLabel = '🃏 เปิดไพ่ใบที่ '.$next;

        return [
            'message' => $header
                ."🃏 ตอนนี้เปิดไพ่ไปแล้ว *{$picked}/10 ใบ*\n"
                ."📍 ใบถัดไป — *ใบที่ {$next}: [{$name}]*\n"
                ."💭 ตำแหน่งนี้บอกถึง: {$desc}\n\n"
                ."──────────────────────\n"
                ."👉 พิมพ์ *\"พร้อม\"* (หรือกดปุ่มข้างล่าง) เพื่อให้แม่หมอเปิดไพ่ใบนี้\n"
                ."📋 บิล: {$billRef}",
            'quick_replies' => [
                ['title' => $btnLabel, 'payload' => 'CELTIC_READY'],
            ],
        ];
    }

    /**
     * Resume message สำหรับ STATUS_CELTIC_AWAITING_QUESTION / QA_PROMPT
     */
    protected function buildAwaitingQuestionResume(FortuneReading $reading, string $header, string $billRef): array
    {
        $remainingMin = $reading->getCelticQaRemainingMinutes();
        $usedQ = (int) ($reading->celtic_questions_used ?? 0);
        $maxQ = $this->getMaxQuestions();
        $qaWindow = $this->getQaWindowMinutes();

        $timeHint = $remainingMin !== null && $remainingMin > 0
            ? "⏳ เหลือเวลา *{$remainingMin} นาที* (จากทั้งหมด {$qaWindow})"
            : ($remainingMin === 0
                ? '⏳ หมดเวลาคุยแล้ว — แม่หมอจะปิด session'
                : "⏳ คุยได้ภายใน *{$qaWindow} นาที* นับจากคำทำนายแรก");

        // 🌙 (2026-05-23 v3) ประกาศกติกาให้ชัด — user spec: "ต้องบอกกติการให้ชัดทุกที่"
        $qHint = '';
        if ($maxQ > 0) {
            $remainingQ = max(0, $maxQ - $usedQ);
            $qHint = $usedQ === 0
                ? "\n❓ ถามได้ *{$maxQ} คำถาม* ภายในเวลานี้"
                : "\n❓ เหลือถามได้อีก *{$remainingQ} คำถาม* (จากทั้งหมด {$maxQ})";
        }

        $promptLine = $usedQ === 0
            ? '💬 พิมพ์ *คำถามแรก* ที่อยากรู้มาเลยค่ะ — แม่หมอจะอ่านพลังจากไพ่ทั้ง 10 ใบ'
            : '💬 พิมพ์ *คำถามถัดไป* ที่อยากรู้ — หรือพิมพ์ *"เลิก"* เมื่อพร้อมจบและรับสรุป';

        return [
            'message' => $header
                ."🃏 เจ้าชะตาเปิดไพ่ครบ 10 ใบแล้ว ✅\n\n"
                .$promptLine."\n\n"
                .$timeHint
                .$qHint."\n"
                ."📋 บิล: {$billRef}",
            'quick_replies' => [],
        ];
    }
}
