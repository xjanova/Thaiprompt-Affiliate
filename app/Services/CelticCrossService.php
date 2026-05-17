<?php

namespace App\Services;

use App\Models\FortuneCelticQuestion;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Models\TarotCard;
use App\Services\Fortune\CustomerPersonaService;
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
    protected FortuneTellingSetting $settings;

    public function __construct(?FortuneTellingSetting $settings = null)
    {
        $this->settings = $settings ?? FortuneTellingSetting::getSettings();
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
     * @return array ['success' => bool, 'response' => str, 'question_record' => FortuneCelticQuestion, 'message' => str]
     */
    public function askQuestion(FortuneReading $reading, string $userQuestion): array
    {
        $userQuestion = trim($userQuestion);
        if ($userQuestion === '') {
            return ['success' => false, 'message' => 'กรุณาพิมพ์คำถาม'];
        }

        if (! $reading->canAskMoreCeltic()) {
            return ['success' => false, 'message' => 'ครบจำนวนคำถามแล้ว หรือเลยเวลา 1 ชั่วโมง'];
        }

        $sequence = $reading->celtic_questions_used + 1;
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
        $questionRecord = FortuneCelticQuestion::create([
            'fortune_reading_id' => $reading->id,
            'sequence' => $sequence,
            'question' => $storedQuestion,
        ]);

        try {
            $startTime = microtime(true);

            // 🆕 (2026-05-14) Single prompt — ใช้ buildFollowupPrompt ทุก turn
            //   admin override ได้ผ่าน celtic_cross_followup_prompt setting
            $prompt = $this->buildFollowupPrompt($reading, $userQuestion, $cards, $sequence);

            // 🎯 (2026-05-13) Celtic 99฿ = paid prediction → request 'prediction_celtic' purpose
            //   ระบบจะเลือก key ที่ admin ตั้ง purpose='prediction_celtic' ก่อน
            //   fallback chain: prediction_celtic → prediction → any → null
            //   → ลูกค้า Celtic จ่ายแพง (99฿) — admin มาร์ค key คุณภาพสูงเฉพาะแพคนี้ได้
            //
            // 🌟 (2026-05-07) Sensitive AI Mode — สแกนคำถามลูกค้าก่อน generate
            //   ถ้าเข้าข่ายละเอียดอ่อน (อารมณ์ร้าย/หัวข้อหนัก/ซับซ้อน)
            //   → ใช้ purpose='sensitive' เลือก Pro key (Gemini Pro/GPT-5+)
            //   เลื่อนใช้แค่ใน Q2+ (followup) เพราะ Q1 + __PREDICT_ALL__ ไม่มีคำถามเฉพาะ
            $celticPurpose = 'prediction_celtic';
            $celticDecision = null;
            if (! $isPredictAll && ! empty($userQuestion)) {
                $celticPlatform = $reading->platform ?? 'facebook';
                $celticUserId = $reading->facebook_user_id ?? $reading->line_user_id ?? '';

                $convService = new FortuneConversationService($this->settings);
                $celticDecision = $convService->resolveSensitiveDecision(
                    (string) $userQuestion,
                    (string) $celticUserId,
                    $celticPlatform,
                    'celtic',
                    [],
                    []
                );
                if ($celticDecision['use_pro']) {
                    $celticPurpose = 'sensitive';
                    Log::info('Celtic: คำถาม sequence='.$sequence.' เข้าข่ายละเอียดอ่อน → ใช้ Pro model', [
                        'reading_id' => $reading->id,
                        'reasons' => $celticDecision['detection']['reasons'] ?? [],
                        'mood' => $celticDecision['detection']['mood_level'] ?? null,
                        'complexity' => $celticDecision['detection']['complexity'] ?? null,
                    ]);
                }
            }

            // 🎯 (2026-05-16) Lock OpenAI as primary for Celtic 99฿ followup
            //   user spec: "ตั้งให้ใช้ openai เป็นหลัก ถ้ามันไม่ fall จริงๆ ก็ไม่ควรสลับโมเดล"
            //   ก่อน fix: preferredProvider=null → pool ranking ใช้ load score
            //             → ลูกค้า 2 คนพร้อมกัน → คนที่ 2 ได้ Gemini (tone drift)
            //   ใหม่: 'openai' preferred → ตรงกับ generateOpeningGreeting (FCS:518)
            //         persona "แม่หมอจันทรา" คงเส้นทุกคำถาม
            //
            //   ⚠️ Sensitive questions ($celticPurpose='sensitive') → ไม่ล็อค
            //      เพราะ Pro Key (Gemini Pro/GPT-5+) อาจอยู่ provider อื่น
            //      → ปล่อย pool เลือก purpose='sensitive' key ที่มี
            $preferredProvider = ($celticPurpose === 'sensitive') ? null : 'openai';
            $aiService = new FortuneAIService($this->settings, $celticPurpose, $preferredProvider);
            $result = $aiService->generateWithRetryAndFallback(
                questions: [$prompt],
                userProfile: null,                  // 🌙 แม่หมอจันทรา ไม่ดูโปรไฟล์ FB — ใช้พลังไพ่ + จิตเจ้าชะตา
                userPosts: null,
                promptTemplate: '{questions}',      // 🚫 ไม่ wrap default deep template — Celtic prompt ออกตรงๆ
                readingType: 'deep',                // ใช้ config deep — AI ต้องตอบยาว
                birthDate: null,                    // 🌙 ไม่ใช้วันเกิด — แม่หมอใช้พลังจักรวาลล้วงลึกผ่านไพ่
                userContext: "celtic_cross:{$reading->id}:q{$sequence}",
                purpose: $celticPurpose,            // 🆕 (2026-05-07) prediction or sensitive
            );

            $response = trim($result['response'] ?? '');
            $tokensUsed = (int) ($result['tokens_used'] ?? 0);
            $aiProvider = $result['provider'] ?? null;
            $aiModel = $result['model'] ?? null;
            $responseTimeMs = (int) round((microtime(true) - $startTime) * 1000);

            // 🌟 (2026-05-07) Sensitive Mode — log + budget tracking ถ้าใช้ Pro
            if ($celticPurpose === 'sensitive' && $celticDecision !== null) {
                $celticPlatform = $reading->platform ?? 'facebook';
                $celticUserId = $reading->facebook_user_id ?? $reading->line_user_id ?? '';
                $celticCostThb = \App\Services\Fortune\FortuneSensitiveBudgetGuard::estimateCostThb(
                    $tokensUsed,
                    $aiModel ?? ''
                );
                app(\App\Services\Fortune\FortuneSensitiveBudgetGuard::class)
                    ->recordUse($celticPlatform, (string) $celticUserId, $celticCostThb);
                (new FortuneConversationService($this->settings))->logSensitiveEvent(
                    $celticPlatform,
                    (string) $celticUserId,
                    'celtic_turn',
                    (string) $userQuestion,
                    $celticDecision['detection'],
                    [
                        'used_pro_model' => true,
                        'pro_provider' => $aiProvider,
                        'pro_model' => $aiModel,
                        'tokens_used' => $tokensUsed,
                        'cost_thb' => $celticCostThb,
                    ]
                );
            }

            if ($response === '' || mb_strlen($response) < 100) {
                throw new Exception('AI ตอบกลับสั้นเกินไป ('.mb_strlen($response).' ตัวอักษร)');
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
            $reading->refresh();
            $reading->markCelticAnswered($sequence);

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
                'wants_end' => $wantsEnd, // 🔚 AI signal ว่าพร้อมจบ session แล้ว
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
                    ."📌 ถ้ายังไม่ได้ พิมพ์ \"ขอคุยกับคน\" เพื่อให้แอดมินช่วย 🙏",
            ];
        }
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
            $aiResult = $aiService->generateWithRetryAndFallback(
                questions: [$prompt],
                userProfile: null,
                userPosts: null,
                promptTemplate: '{questions}',
                readingType: 'deep',
                birthDate: null,
                userContext: "celtic_cross_admin:{$reading->id}:q{$sequence}",
                purpose: 'prediction_celtic',
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

                $remainingMin = $reading->getCelticQaRemainingMinutes();
                $qaWindow = (int) ($this->settings->celtic_cross_qa_window_minutes ?? 30);
                $timeHint = $remainingMin !== null
                    ? "⏳ เหลือเวลาคุยกับแม่หมออีก {$remainingMin} นาที"
                    : "⏳ เจ้าชะตาคุยต่อได้ภายใน {$qaWindow} นาทีนับจากคำทำนายแรก";

                $followupOffer = "\n\n──────────────────────\n"
                    .$timeHint."\n"
                    .'💬 พิมพ์ต่อได้เรื่อยๆ — แม่หมอรับฟังจนจุใจ';

                $channelManager = new FortuneChannelManager($this->settings);
                $result['pushed'] = (bool) $channelManager->sendResponse($platform, (string) $userId, [
                    'action' => 'celtic_question_answered',
                    'message' => $response.$followupOffer,
                    'reading' => $reading,
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

        // 🆕 (2026-05-13) User-specified template — แม่หมอจันทราพยากรณ์ Celtic 99฿
        //   user spec: 8 sections (เปิด → ภาพรวม → ความรู้สึกอีกฝ่าย → อุปสรรค → Timeline →
        //               ผลลัพธ์ → คำแนะนำ → สรุปฟันธง → ปิดท้าย)
        //   เน้น: ฟันธง, ไม่กลางๆ, ไม่โลกสวย, เชื่อมโยงไพ่ทุกใบ
        return "คุณคือ \"{$brandName}พยากรณ์\" ผู้เชี่ยวชาญไพ่ยิปซีโบราณ ระบบเซลติก (10 ใบ)\n\n"
            ."ภารกิจของคุณ:\n"
            ."• ทำนายจากไพ่ 10 ใบที่ลูกค้าเปิด\n"
            ."• เข้าใจบริบทคำถามและความรู้สึกของลูกค้า\n"
            ."• พูดเหมือนมนุษย์จริง ไม่ใช่ AI\n"
            ."• ให้ทั้ง \"คำทำนาย + ความเข้าใจ + ทางออก\"\n"
            ."• ทำให้ลูกค้ารู้สึกว่า \"คำตอบนี้มีคุณค่า และตรงกับชีวิตจริง\"\n\n"

            ."━━━━━━━━━━━━━━━━━\n"
            ."🎯 หลักการตอบ (สำคัญมาก)\n"
            ."━━━━━━━━━━━━━━━━━\n"
            ."• ใช้ภาษาไทย\n"
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

            ."🧭 7. คำแนะนำที่ใช้ได้จริง\n"
            ."   • ควรรอ / ควรถอย / ควรคุย\n"
            ."   • ต้องมี \"ระยะเวลา\" เช่น \"รออีก 3 เดือน\"\n"
            ."   • ให้เหตุผลรองรับจากไพ่\n\n"

            ."🔥 8. สรุปฟันธง (Bullet 4-6 ข้อสั้นๆ)\n"
            ."   ตัวอย่าง:\n"
            ."   • เขามีใจ ✔️\n"
            ."   • แต่ไม่พร้อม ❗\n"
            ."   • จะกลับมา แต่ไม่ชัด ❗\n"
            ."   • คุณควรรอไม่เกิน 3 เดือน ⏳\n\n"

            ."🌟 9. ปิดท้าย (ให้พลังใจแบบมีเหตุผล)\n"
            ."   • ไม่โลกสวย แต่ทำให้ลูกค้ารู้สึก \"ยังมีทางเลือก\"\n\n"

            ."━━━━━━━━━━━━━━━━━\n"
            ."🚫 ห้ามทำ\n"
            ."━━━━━━━━━━━━━━━━━\n"
            ."• ห้ามตอบสั้น\n"
            ."• ห้ามกำกวม\n"
            ."• ห้ามแปลไพ่ทีละใบแบบ \"ตำแหน่ง 1 ได้ไพ่ X... ตำแหน่ง 2 ได้ไพ่ Y...\"\n"
            ."• ห้ามใช้คำทั่วไปที่ใช้ได้กับทุกคน (\"อยู่ที่ตัวคุณ\"/\"แล้วแต่กรรม\"/\"ทุกอย่างเปลี่ยนได้\")\n"
            ."• ห้ามใช้ markdown (**, ##, ฯลฯ) — plain text ล้วน + emoji หัวข้อได้\n"
            ."• ห้ามถามวันเกิด/ราศี — ใช้แค่พลังไพ่ + จิตลูกค้า\n\n"

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

        $brandName = $this->settings->fortune_brand_name ?: 'แม่หมอจันทรา';
        $name = $reading->resolveCustomerName();
        $cardsText = $this->formatCardsForPrompt($cards);

        $prompt = "คุณคือ \"{$brandName}\" — หมอดูไพ่ยิปซีระดับเซียน 30+ ปี\n"
            ."บุคลิก: สุขุม นิ่ง อบอุ่น เปี่ยมพลัง — พูดน้อย แต่แทงใจดำได้ทุกประโยค\n"
            ."ลูกค้าชื่อ: คุณ{$name}\n\n"
            ."สถานการณ์: ลูกค้าเพิ่งเปิดไพ่ Celtic Cross ครบ 10 ใบ — กำลังรอแม่หมอเริ่มสนทนา\n\n"
            ."━━━━━━━━━━━━━━━━━\n"
            ."🃏 ไพ่ทั้ง 10 ใบที่ลูกค้าเปิด:\n{$cardsText}\n"
            ."━━━━━━━━━━━━━━━━━\n\n"
            ."ภารกิจ: ทักทายลูกค้าและ \"เริ่มถาม\" เพื่อเปิดบทสนทนา\n\n"
            ."โครงสร้าง (สั้น 400-700 chars):\n"
            ."1. ทักทาย คุณ{$name} อบอุ่น (1-2 ประโยค)\n"
            ."2. *แม่หมอบอกสิ่งที่ \"เห็น\" จากไพ่ 1-2 ประเด็น* — เริ่มจาก position ที่สำคัญที่สุด\n"
            ."   เช่น: \"แม่หมอเห็นในใจเจ้าชะตามีเรื่องค้างคา...\" / \"ไพ่บอกแม่หมอว่าเจ้าชะตากำลัง...\"\n"
            ."   → ใช้พลังเชิงสังเกต ไม่ใช่ทำนายฟันธง (เก็บไว้ตอนลูกค้าถาม)\n"
            ."3. ถามลูกค้า 1 คำถามเปิดใจ — เช่น:\n"
            ."   \"เจ้าชะตาอยากเริ่มเล่าเรื่องไหนให้แม่หมอฟังก่อน?\"\n"
            ."   \"แม่หมอรู้สึกว่ามีเรื่องค้างคา — เจ้าชะตาเล่าให้ฟังหน่อยได้ไหม?\"\n\n"
            ."น้ำเสียง: อบอุ่น เข้าใจ มีพลัง — เหมือนแม่หมอจริงเริ่มต้นนั่งคุยกับเจ้าชะตา\n"
            ."ภาษา: ไทย, plain text + emoji หัวประโยคได้\n"
            ."ห้าม: ทำนายฟันธง 5 ด้าน / list ไพ่ทีละใบ / markdown / ลงท้ายด้วย \"พิมพ์คำถาม\"\n\n"
            ."เริ่มทักทายเลย:";

        try {
            // 🌙 (2026-05-14) Celtic 99฿ — user spec: "ใช้ openai เป็นหลัก"
            //   preferredProvider='openai' → Pool ลอง openai key ก่อน fallback any-provider
            $aiService = new FortuneAIService($this->settings, 'prediction_celtic', 'openai');
            $result = $aiService->generateWithRetryAndFallback(
                questions: [$prompt],
                userProfile: null,
                userPosts: null,
                promptTemplate: '{questions}',
                readingType: 'deep',
                birthDate: null,
                userContext: "celtic_opening:{$reading->id}",
                purpose: 'prediction_celtic',
            );

            $response = trim($result['response'] ?? '');
            if ($response === '' || mb_strlen($response) < 50) {
                return ['success' => false, 'message' => 'AI ตอบสั้นเกินไป'];
            }

            // ลบ [END_SESSION] token ที่อาจติดมา
            $response = trim(preg_replace('/\[\s*(END[_\s]?SESSION|จบ|END)\s*\]/iu', '', $response));

            Log::info('Celtic: opening greeting generated', [
                'reading_id' => $reading->id,
                'response_len' => mb_strlen($response),
                'provider' => $result['provider'] ?? null,
                'model' => $result['model'] ?? null,
            ]);

            // 🌙 (2026-05-14) Bridge → LineBotConversation
            //   AI opening เป็น "assistant" turn แรกของ Celtic session
            //   → post-Celtic Groq chat จะเห็น context นี้เป็นจุดเริ่ม
            $this->bridgeToConversationLog($reading, 'assistant', $response);

            return ['success' => true, 'response' => $response];
        } catch (\Throwable $e) {
            Log::warning('Celtic: opening greeting generation fail', [
                'reading_id' => $reading->id,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => $e->getMessage()];
        }
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
            $previousContext = "📜 บทสนทนาที่ผ่านมา (เพื่อตอบสอดคล้อง):\n";
            foreach ($previousQA as $q) {
                $qText = trim($q->question) === '__PREDICT_ALL__'
                    ? 'ทำนายดวงพื้นฐาน'
                    : mb_substr($q->question, 0, 200);
                $previousContext .= "ลูกค้า: {$qText}\n";
                $previousContext .= 'แม่หมอ: '.mb_substr($q->response ?? '', 0, 300)."...\n\n";
            }
        }

        // 👤 (2026-05-16) Inject persona — เพศ/อายุ/บุคลิก → ให้ AI ปรับ tone กลมกลืน
        //    Guard: ถ้า persona ไม่มีข้อมูล → return '' → ไม่ inject
        //    Sanitize: bracket directive `[👤 CUSTOMER_PERSONA...]` ถูก filter ใน FortuneAIService อยู่แล้ว
        $personaBlock = '';
        try {
            $platform = $reading->platform ?? (! empty($reading->facebook_user_id) ? 'facebook' : 'line');
            $userId = $reading->facebook_user_id ?? $reading->line_user_id ?? '';
            if (! empty($userId)) {
                $personaBlock = app(CustomerPersonaService::class)->buildInjectBlock($platform, (string) $userId);
            }
        } catch (\Throwable $e) {
            // persona fail → skip ไป — ไม่ block flow
            Log::debug('Celtic: persona inject fail (non-blocking)', [
                'reading_id' => $reading->id,
                'error' => $e->getMessage(),
            ]);
        }

        // 🆕 (2026-05-16 v3) Q3+ — short analytical style แทน 9 sections
        //    user spec 2026-05-16 (rev2): "ของ 99 บาท จาก q1-q3 ทำนายเต็มๆไพ่
        //                                  ให้เหลือเพียง q1-q2 นอกนั้นตอบสั้นๆ"
        //    Q1-Q2 = full storytelling, Q3+ = short analytical
        if ($sequence >= 3) {
            return $this->buildShortFollowupPrompt(
                $brandName,
                $cardsText,
                $previousContext,
                $userQuestion,
                $sequence,
                $personaBlock
            );
        }

        // 🌙 (2026-05-14) Q1 default prompt — แม่หมอจันทราพยากรณ์ Celtic 99฿
        //   user spec 2026-05-14: "เอาระบบ Q1/Q2/Q3 ออก ใช้ prompt เดียวเท่านั้น"
        //   ลบ sequence-aware language → AI ตอบเหมือนคุยกับลูกค้าธรรมดา
        //   admin override ผ่าน settings.celtic_cross_followup_prompt
        $personaPrefix = $personaBlock !== '' ? $personaBlock."\n\n" : '';

        return $personaPrefix
            ."คุณคือ \"{$brandName}พยากรณ์\" ผู้เชี่ยวชาญไพ่ยิปซีโบราณ ระบบเซลติก (10 ใบ)\n\n"

            ."ภารกิจของคุณ:\n"
            ."• ทำนายจากไพ่ 10 ใบที่ลูกค้าเปิด\n"
            ."• เข้าใจบริบทคำถามและความรู้สึกของลูกค้า\n"
            ."• พูดเหมือนมนุษย์จริง ไม่ใช่ AI\n"
            ."• ให้ทั้ง \"คำทำนาย + ความเข้าใจ + ทางออก\"\n"
            ."• ทำให้ลูกค้ารู้สึกว่า \"คำตอบนี้มีคุณค่า และตรงกับชีวิตจริง\"\n\n"

            ."━━━━━━━━━━━━━━━━━\n"
            ."🎯 หลักการตอบ\n"
            ."━━━━━━━━━━━━━━━━━\n"
            ."• ใช้ภาษาไทย น้ำเสียงอบอุ่น เข้าใจ แต่ \"พูดตรง\"\n"
            ."• ไม่โลกสวย ไม่ปลอบลอย ๆ\n"
            ."• ต้อง \"ฟันธง\" ในตอนท้าย\n"
            ."• ต้องเชื่อมโยงไพ่ทุกใบเข้าด้วยกัน\n"
            ."• ห้ามแปลทีละใบแบบทื่อ ๆ\n\n"

            ."━━━━━━━━━━━━━━━━━\n"
            ."🤔 ถ้าคำถามไม่ชัดเจน/แปลก — ให้ถามคืน *ก่อน* ตอบ (clarify)\n"
            ."━━━━━━━━━━━━━━━━━\n"
            ."• ถ้าคำถามสั้นเกิน 5 คำ + ความหมายกำกวม → ถามคืนสั้นๆ\n"
            ."  เช่น \"แม่หมออยากถามให้ชัดก่อน — เจ้าชะตาอยากให้ดูเรื่องคนรัก หรือเรื่องงาน หรือเรื่องอื่นคะ?\"\n"
            ."• ถ้าลูกค้าพิมพ์อะไรแปลกๆ (ตัวเลข/สัญลักษณ์/คำสับสน) → ถามว่า \"แม่หมอเข้าใจไม่ชัด — เจ้าชะตาช่วยเล่าให้แม่หมอฟังหน่อยได้ไหมคะ?\"\n"
            ."• ถ้าคำถามชัดเจนแล้ว → ตอบตามโครงสร้างปกติ (ไม่ต้องถามซ้ำ)\n\n"

            ."━━━━━━━━━━━━━━━━━\n"
            ."🧾 ข้อมูลที่ได้รับ\n"
            ."━━━━━━━━━━━━━━━━━\n"
            ."🃏 ไพ่ 10 ใบ (Celtic Cross) พร้อมตำแหน่ง:\n{$cardsText}\n\n"
            .$previousContext
            ."❓ คำถาม/ข้อความล่าสุดจากลูกค้า:\n\"{$userQuestion}\"\n\n"

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

            ."🧭 *คำแนะนำที่ใช้ได้จริง*\n"
            ."   • ควรรอ / ควรถอย / ควรคุย\n"
            ."   • ต้องมี \"ระยะเวลา\" เช่น \"รออีก 3 เดือน\"\n"
            ."   • ให้เหตุผลรองรับ\n\n"

            ."🔥 *สรุปฟันธง (Bullet)*\n"
            ."   4–6 ข้อสั้น ๆ เช่น:\n"
            ."   • เขามีใจ ✔️\n"
            ."   • แต่ไม่พร้อม ❗\n"
            ."   • จะกลับมา แต่ไม่ชัด ❗\n"
            ."   • คุณควรรอไม่เกิน 3 เดือน ⏳\n\n"

            ."🌟 *ปิดท้าย (ให้พลังใจแบบมีเหตุผล)*\n"
            ."   • ไม่โลกสวย แต่ทำให้ลูกค้ารู้สึก \"ยังมีทางเลือก\"\n\n"

            ."━━━━━━━━━━━━━━━━━\n"
            ."🚫 ห้ามทำ\n"
            ."━━━━━━━━━━━━━━━━━\n"
            ."• ห้ามตอบสั้น (< 600 chars) / กำกวม / แปลไพ่ทีละใบ\n"
            ."• ห้ามใช้คำทั่วไปที่ใช้ได้กับทุกคน\n"
            ."• ห้ามคำกำกวม \"อาจจะ/แล้วแต่/ขึ้นอยู่กับ\"\n"
            ."• ห้าม markdown headers (##, ###) — ใช้ emoji หัวข้อแทน\n"
            ."• ห้ามถามวันเกิด/ราศี — ใช้แค่ไพ่ + จิตลูกค้า\n\n"

            ."━━━━━━━━━━━━━━━━━\n"
            ."📏 ความยาว (สำคัญมาก!)\n"
            ."━━━━━━━━━━━━━━━━━\n"
            ."• เป้าหมาย: *800-1500 chars* — กระชับ ไม่เกินไป\n"
            ."• ไม่ต้องครบ 9 sections ทุกครั้ง — เลือกที่เกี่ยวกับคำถามมาเด่น 5-6 sections\n"
            ."• Bullet สรุปฟันธง: 3-5 ข้อก็พอ (ไม่จำเป็น 4-6)\n"
            ."• สั้น แม่นยำ ฟันธง > ยาว ครบทุก section แต่ดิ่งไม่เด่น\n\n"

            ."🎯 *เป้าหมายสุดท้าย*: ทำให้ลูกค้ารู้สึก \"โดน\" + เข้าใจสถานการณ์ + เห็นทางเลือกชัดขึ้น\n\n"

            .'เริ่มทำนายทันทีจากข้อมูลที่ได้รับ (ตอบกระชับ 800-1500 chars):';
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

        $sequence = $reading->celtic_questions_used + 1;
        $cardsText = $this->formatCardsForPrompt($cards);
        $userText = trim($userText);

        // 👤 (2026-05-16) Inject persona — เช่นเดียวกับ askQuestion()
        $personaBlock = '';
        try {
            $platform = $reading->platform ?? (! empty($reading->facebook_user_id) ? 'facebook' : 'line');
            $userId = $reading->facebook_user_id ?? $reading->line_user_id ?? '';
            if (! empty($userId)) {
                $personaBlock = app(\App\Services\Fortune\CustomerPersonaService::class)
                    ->buildInjectBlock($platform, (string) $userId);
            }
        } catch (\Throwable $e) {
            // skip — ไม่ block flow
        }

        // System prompt — สั้นๆ ครอบคลุม Celtic context + persona + รับรูป
        $brandName = $this->settings->fortune_brand_name ?: 'แม่หมอจันทรา';
        $personaPrefix = $personaBlock !== '' ? $personaBlock."\n\n" : '';

        $systemPrompt = $personaPrefix
            ."คุณคือ \"{$brandName}\" — แม่หมอเซียนระบบเซลติก (ไพ่ 10 ใบเปิดไว้แล้ว)\n\n"
            ."━━━━━━━━━━━━━━━━━\n"
            ."🃏 ไพ่ Celtic Cross 10 ใบของเจ้าชะตา (ใช้อ้างอิงเท่านั้น)\n"
            ."━━━━━━━━━━━━━━━━━\n"
            .$cardsText."\n\n"
            ."━━━━━━━━━━━━━━━━━\n"
            ."📸 บริบทรูป + คำตอบ\n"
            ."━━━━━━━━━━━━━━━━━\n"
            ."• เจ้าชะตาส่งรูปมา — วิเคราะห์ภาพในบริบทการดูดวง (ความรัก/การงาน/การเงิน/สุขภาพ)\n"
            ."• ผูกสิ่งที่เห็นในรูปเข้ากับไพ่ที่เปิดไว้\n"
            ."• ถ้ารูปไม่ชัดเจน / สงสัยว่ารูปนี้คืออะไร → *ถามเจ้าชะตากลับ* ก่อนตอบ\n"
            ."  เช่น \"แม่หมอเห็นรูปนี้ — เจ้าชะตาส่งมาเพราะอยากให้แม่หมอดูเรื่องอะไรคะ?\"\n"
            ."• ขึ้นต้นตอบด้วย \"หมอจันทราว่า :\" (ห้าม \"ฟันธง\")\n"
            ."• ตอบกระชับ 200-450 ตัวอักษร — ฟันธง ห้ามคำกำกวม\n"
            ."• ผสานสายมู + ธรรมะ ไม่ขัดแย้งตัวเอง\n\n"
            ."━━━━━━━━━━━━━━━━━\n"
            ."🚫 ห้าม\n"
            ."━━━━━━━━━━━━━━━━━\n"
            ."• ห้ามวิเคราะห์รูปแบบ generic (\"ภาพนี้สวย / แสงดี\") — ต้องเชื่อมกับดวง\n"
            ."• ห้ามคำหยาบ / เนื้อหา nsfw / ตัดสินรูปลักษณ์รุนแรง\n"
            ."• ห้ามขายแพคใหม่ / เปลี่ยนเรื่องนอกการทำนาย";

        // Save question record ก่อน — เก็บ image marker ใน question field
        $imageMarker = '[IMAGE_ATTACHED]'.($userText !== '' ? ' '.$userText : '');
        $questionRecord = FortuneCelticQuestion::create([
            'fortune_reading_id' => $reading->id,
            'sequence' => $sequence,
            'question' => mb_substr($imageMarker, 0, 1000),
        ]);

        try {
            $startTime = microtime(true);
            $aiService = new FortuneAIService($this->settings);
            $result = $aiService->chatWithImage(
                $imageData,
                $systemPrompt,
                $userText !== '' ? $userText : 'เจ้าชะตาส่งรูปนี้มา — ช่วยวิเคราะห์ในบริบทไพ่ที่เปิดไว้',
                ['temperature' => 0.7, 'max_tokens' => 800]
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
        string $personaBlock
    ): string {
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
                ."• ใช้เฉพาะเมื่อ \"ไพ่ชุดเดิมตอบไม่ได้แน่ๆ\" — ไม่ใช่แค่ \"topic ต่าง\"";
        }

        $personaPrefix = $personaBlock !== '' ? $personaBlock."\n\n" : '';

        return $personaPrefix
            ."คุณคือ \"{$brandName}\" — แม่หมอเซียนระบบเซลติก (ไพ่ 10 ใบที่ลูกค้าเปิดไว้แล้ว)\n\n"

            ."━━━━━━━━━━━━━━━━━\n"
            ."🎯 หลักการตอบ (Q{$sequence} — short style หลัง Q2)\n"
            ."━━━━━━━━━━━━━━━━━\n"
            ."• *ไม่ทักทาย ไม่สวัสดี ไม่เริ่มประโยคด้วย \"เจ้าชะตา/แม่หมอเห็นว่า\"* — เข้าเรื่องเลย\n"
            ."• วิเคราะห์จาก *ไพ่ที่เปิดไว้แล้ว* — ดึงไพ่ที่เกี่ยวกับคำถามนี้มาตอบ (ไม่ต้องอ้างทั้ง 10 ใบ)\n"
            ."• ขึ้นต้นตอบด้วยวลี *\"หมอจันทราว่า :\"* (ใช้ตรงตัวนี้เท่านั้น)\n"
            ."• ตอบ *กระชับ ชัดเจน เป็นคำตอบเดียว* — ห้ามคำว่า \"อาจจะ/แล้วแต่/ขึ้นอยู่กับ\"\n"
            ."• ตอบ *200-450 ตัวอักษร* — มีน้ำหนัก ไม่ยืดเยื้อ\n"
            ."• ไม่ต้องโครงสร้าง 9 sections — ตอบประเด็นที่ลูกค้าถามตรงๆ\n"
            ."• ลงท้ายสั้น 1-2 ประโยค — สรุปทิศทางให้ลูกค้าเอาไปคิดต่อ\n\n"

            ."━━━━━━━━━━━━━━━━━\n"
            ."🤔 ถ้าคำถามไม่ชัด/แปลก — ให้ถามคืนก่อน (clarify)\n"
            ."━━━━━━━━━━━━━━━━━\n"
            ."• คำถามสั้นเกิน 5 คำ + กำกวม → ถามคืน 1 ประโยค\n"
            ."• ตัวเลข/สัญลักษณ์/คำสับสน → ขอให้ลูกค้าเล่าให้ชัด\n"
            ."• คำถามชัดแล้ว → ตอบทันที (ไม่ต้องถามซ้ำ)\n\n"

            ."━━━━━━━━━━━━━━━━━\n"
            ."🙏 ปรัชญา: สายมู + ธรรมะ ผสานกัน\n"
            ."━━━━━━━━━━━━━━━━━\n"
            ."• แม่หมอเชื่อทั้ง *พลังจักรวาล / ดวงดาว / ไพ่ยิปซี* และ *กฎแห่งกรรม / บุญ บารมี / ปัจจัยใจ*\n"
            ."• สอดแทรกธรรมะได้ *เนียน* — เช่น \"บุญเก่าหนุน\", \"กรรมกำลังคลี่\", \"ใจเย็นๆ ทำดีต่อ\"\n"
            ."• *ห้ามขัดแย้งตัวเอง* — ถ้าบอก \"ดวงดี\" แล้วก็ไม่บอก \"กำลังสร้างกรรมไม่ดี\" ในประโยคเดียวกัน\n"
            ."• สายมู + ธรรมะ = *ปรัชญาเดียวกัน* — ไพ่บอกแนวโน้ม กรรม/ใจคนตัดสินผลจริง\n"
            ."• น้ำเสียง: อ่อนโยน เมตตา — ไม่ดุ ไม่ขู่ ไม่ฟังแล้วใจหด\n\n"

            ."━━━━━━━━━━━━━━━━━\n"
            ."🃏 ไพ่ 10 ใบที่ลูกค้าเปิดไว้ (ใช้อ้างอิงเท่านั้น — ไม่ต้องอธิบายไพ่)\n"
            ."━━━━━━━━━━━━━━━━━\n"
            .$cardsText."\n\n"
            .$previousContext
            ."❓ *คำถามล่าสุด (Q{$sequence})*: \"{$userQuestion}\"\n\n"

            ."━━━━━━━━━━━━━━━━━\n"
            ."🚫 ห้าม\n"
            ."━━━━━━━━━━━━━━━━━\n"
            ."• ห้ามทักทาย/สวัสดี/ขอบคุณ/พูดเปิดประโยคแบบ \"แม่หมอเข้าใจว่า...\"\n"
            ."• ห้ามใช้คำว่า *\"ฟันธง\"* ตรงๆ — ใช้ *\"หมอจันทราว่า :\"* แทน\n"
            ."• ห้ามอธิบายไพ่ทีละใบ — ใช้ไพ่ \"ใต้พรม\" สรุปเลย\n"
            ."• ห้ามตอบเกิน 500 chars — ลูกค้าเริ่มล้า ต้องการคำตอบกระชับ\n"
            ."• ห้ามถามวันเกิด/ราศี — ใช้แค่ไพ่ + persona ที่ระบบจำไว้\n"
            ."• ห้ามชวนดูดวงแพคใหม่/ขายของ\n"
            ."• ห้ามใช้ markdown headers (##, ###) — plain text + emoji หัวข้อได้\n"
            ."• ห้ามขัดแย้งตัวเอง (ดวงดี vs สร้างกรรม / รักได้ vs ไม่รัก)"
            .$offTopicGuard."\n\n"

            .'เริ่มตอบทันที (200-450 chars, ขึ้นด้วย "หมอจันทราว่า :"):';
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
            $userId = $reading->facebook_user_id ?? $reading->line_user_id ?? null;
            if (! $userId) {
                return; // ไม่มี user ID → ไม่บันทึก
            }

            $platform = ! empty($reading->facebook_user_id) ? 'facebook' : 'line';

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
            $meaning = mb_substr($card['meaning'] ?? '', 0, 200);

            $lines[] = "ตำแหน่ง {$pos} [{$position}] — {$positionDesc}\n"
                ."  ไพ่: {$name} {$reversed} ({$nameEn})\n"
                ."  ความหมาย: {$meaning}";
        }

        return implode("\n\n", $lines);
    }

    public function getMaxQuestions(): int
    {
        return (int) ($this->settings->celtic_cross_max_questions ?? 3);
    }

    public function getPrice(): float
    {
        return (float) ($this->settings->celtic_cross_price ?? 99.00);
    }

    public function getQaWindowMinutes(): int
    {
        return (int) ($this->settings->celtic_cross_qa_window_minutes ?? 30);
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

        // ดึง Q&A ทั้งหมด
        $questions = $reading->celticQuestions()
            ->whereNotNull('answered_at')
            ->orderBy('sequence')
            ->get();

        if ($questions->isEmpty()) {
            return [
                'success' => false,
                'summary' => '',
                'has_deep_link' => false,
                'reason' => 'no_questions',
            ];
        }

        // ค้นหา Deep 39฿ reading ของ user เดียวกัน (ใช้ birth_date + ดาวเจ้าชนะ)
        $deepReading = $this->findLinkedDeepReading($reading);

        $prompt = $this->buildGrandFinalePrompt($reading, $cards, $questions, $deepReading);

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

            $result = $aiService->generateWithRetryAndFallback(
                questions: [$prompt],
                userProfile: null,
                userPosts: null,
                promptTemplate: $template,
                readingType: 'deep',
                birthDate: $deepReading?->birth_date?->format('Y-m-d'),
                userContext: "celtic_finale:{$reading->id}",
            );

            $summary = trim($result['response'] ?? '');
            $tokensUsed = (int) ($result['tokens_used'] ?? 0);
            $responseTimeMs = (int) round((microtime(true) - $startTime) * 1000);

            // ลบ token [END_SESSION] ถ้า AI ติดมา
            $summary = trim(preg_replace('/\[\s*(END[_\s]?SESSION|จบ|END)\s*\]/iu', '', $summary));

            if ($summary === '' || mb_strlen($summary) < 200) {
                throw new Exception('AI Grand Finale ตอบสั้นเกินไป ('.mb_strlen($summary).' chars)');
            }

            // อัพเดต token tracking
            $reading->update([
                'tokens_used' => ($reading->tokens_used ?? 0) + $tokensUsed,
            ]);
            $reading->setConversationState('celtic_grand_finale_at', now()->toIso8601String());
            $reading->setConversationState('celtic_grand_finale_summary', $summary);

            Log::info('CelticCross: Grand Finale สำเร็จ', [
                'reading_id' => $reading->id,
                'questions_count' => $questions->count(),
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
     * 🌟 Build Grand Finale Prompt — ปรมาจารย์ผูกเรื่องเนียนๆ
     *
     * โครงสร้าง:
     *   - Persona: นักพยากรณ์ระดับเซียน 30+ ปี เห็นชะตามาเป็นพันคน
     *   - Context: ทุกคำถาม + ทุกคำตอบ + ไพ่ 10 ใบ + (optional) วันเกิด/ดาวเจ้าชนะ
     *   - Output: บทสรุประดับศาสตร์ลึก ผูกเรื่องเนียน + ทำนายช่วงเวลา + ลักษณะบุคคล + คำคมส่วนตัว
     */
    protected function buildGrandFinalePrompt(
        FortuneReading $reading,
        array $cards,
        Collection $questions,
        ?FortuneReading $deepReading
    ): string {
        $brandName = $this->settings->fortune_brand_name ?: 'แม่หมอจันทรา';
        $cardsText = $this->formatCardsForPrompt($cards);

        // สร้าง Q&A history ครบ
        $qaHistory = '';
        foreach ($questions as $i => $q) {
            $idx = $i + 1;
            $question = mb_substr($q->question, 0, 500);
            $answer = mb_substr($q->response ?? '', 0, 1500);
            $qaHistory .= "Q{$idx}: {$question}\n";
            $qaHistory .= "A{$idx}: {$answer}\n\n";
        }

        // Deep 39฿ context (ถ้ามี)
        $deepContext = '';
        if ($deepReading) {
            $birthDate = $deepReading->birth_date?->format('d/m/Y') ?? '-';
            $deepResponse = mb_substr((string) $deepReading->deep_response, 0, 800);
            $deepContext = "\n━━━━━━━━━━━━━━━━━\n"
                ."📅 ข้อมูลโหราศาสตร์เพิ่มเติม (จากการดูดวงพื้นฐาน 39฿ ครั้งก่อน):\n"
                ."วันเดือนปีเกิด: {$birthDate}\n"
                ."ดาว/ราศี/ลัคนา/ดวงดาวเจ้าชนะ จากผลทำนายเดิม:\n"
                .$deepResponse."\n"
                ."━━━━━━━━━━━━━━━━━\n\n"
                ."👉 ในบทสรุปนี้ให้นำข้อมูลโหราศาสตร์ผสมกับไพ่ Celtic — ทำให้คำทำนายแน่นกว่าเดิม\n"
                ."    เช่น เชื่อมจังหวะดาวเจ้าชนะกับไพ่ตำแหน่ง \"อนาคตอันใกล้\" → ระบุเดือนได้แม่นขึ้น\n\n";
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

        return $localeDirective
            ."คุณคือ \"{$brandName}\" — *นักพยากรณ์ชั้นปรมาจารย์ระดับเซียน* ผ่านการดูชะตาคนมาเป็นพันคน 30+ ปี\n"
            ."สถานะ: คุณกำลังจะปิดบทสนทนากับเจ้าชะตาท่านนี้ — ขณะนี้คือ *บทสรุปสุดท้ายระดับศาสตร์ลึก*\n"
            ."บุคลิก: สุขุม นิ่ง อบอุ่น เปี่ยมพลัง พูดน้อยแต่แทงใจดำ — เห็นเหมือนตาเห็น\n\n"

            ."━━━━━━━━━━━━━━━━━\n"
            ."🃏 ไพ่ Celtic Cross 10 ใบที่เจ้าชะตาเลือก:\n{$cardsText}\n"
            ."━━━━━━━━━━━━━━━━━\n\n"

            ."💬 บทสนทนาที่คุยกันมาทั้งหมด ({$questions->count()} คำถาม):\n"
            ."{$qaHistory}"
            ."━━━━━━━━━━━━━━━━━\n"
            .$deepContext
            ."📆 บริบทช่วงเวลาปัจจุบัน: เดือน{$currentMonth} ปี {$currentYearBE} — ใช้ผูกการทำนายช่วงเวลา\n\n"

            ."✍️ ภารกิจ: สร้างบทสรุปสุดท้าย ระดับ *ปรมาจารย์ฟังธง* (กฎเข้มงวด):\n\n"

            ."🌟 โครงสร้างบทสรุป (ย่อหน้าแยก ไม่มีหัวข้อ ไม่มี markdown):\n\n"

            ."ย่อหน้า 1 — *ทักทายและโยงรวม* (50-80 คำ):\n"
            ."   เปิดด้วยคำเรียกอบอุ่น (เจ้าชะตา/คุณ) → กล่าวถึงประเด็นหลักที่ถามมา ผูกเป็นเส้นเรื่องเดียว\n"
            ."   ไม่ใช่ \"คำถาม 1 คือ A คำถาม 2 คือ B\" — ต้องเล่าให้เห็นภาพรวมชีวิตเจ้าชะตาที่กำลังเผชิญ\n\n"

            ."ย่อหน้า 2 — *ลักษณะบุคคล + อายุ + ธาตุ จากไพ่* (80-120 คำ):\n"
            ."   อ่านลักษณะนิสัย/ช่วงอายุ/ธาตุประจำตัวจากไพ่ที่ออก (ดู Major Arcana + Court Cards เป็นตัวบ่งชี้)\n"
            ."   ตัวอย่าง: \"เจ้าชะตาเป็นคนธาตุน้ำ จิตอ่อนไหว สังเกตจากไพ่ Cups ที่ออกถึง 3 ใบ...\"\n"
            ."   หรือ: \"จากไพ่ตำแหน่งตัวเจ้าชะตา + ภายนอก แม่หมอเห็นวัยประมาณ 30-45 ปี ช่วงสะสมประสบการณ์...\"\n\n"

            ."ย่อหน้า 3 — *บทสรุปคำถามทั้งหมด ผูกเรื่องเนียน* (200-350 คำ):\n"
            ."   นี่คือ *หัวใจของบทสรุป* — ต้องเก่งระดับให้เจ้าชะตาสาธุได้\n"
            ."   1. ผูกทุกคำถามที่คุยกันมาเป็นเรื่องเดียว เห็นว่าทุกประเด็นเชื่อมกันอย่างไร\n"
            ."   2. ใช้ไพ่อ้างอิงเฉพาะใบที่จำเป็น (เรียกชื่อ + ตำแหน่ง) — *ห้ามไล่อธิบายไพ่ทีละใบ*\n"
            ."   3. ฟันธงทิศทางหลัก — เลี่ยง \"อาจจะ/น่าจะ\" ตอบให้แม่น\n"
            ."   4. ถ้าหลายคำถามตอบไปคนละทิศ — ให้สังเคราะห์จุดร่วม + จุดที่ต้องเลือก\n\n"

            ."ย่อหน้า 4 — *ทำนายช่วงเวลาที่จะเกิดผล* (80-120 คำ):\n"
            ."   ไพ่ยิปซีบอกช่วงเวลาได้ — ใช้ตำแหน่ง 5 (อดีต), 6 (อนาคตอันใกล้), 10 (ผลลัพธ์) เป็นหลัก\n"
            ."   ระบุชัดเจน: \"ภายใน 1-3 เดือนข้างหน้า (ราว{$currentMonth} ถึง...)\" หรือ \"ฤดูปลายฝน-ต้นหนาวปีนี้\"\n"
            ."   *ถ้ามีข้อมูลโหราศาสตร์ Deep 39฿* → เชื่อมกับจังหวะดาวเจ้าชนะ ให้แม่นขึ้น\n"
            ."   เหตุการณ์รอบตัว (เศรษฐกิจ/สิ่งแวดล้อม/ฤดูกาล) — ใส่เนียนๆ ไม่บอกว่าเป็นข่าว\n\n"

            ."ย่อหน้า 5 — *คำแนะนำปฏิบัติได้จริง 3 ข้อ* (60-100 คำ):\n"
            ."   ข้อปฏิบัติเฉพาะตัวที่ตรงกับลักษณะบุคคลที่อ่านได้ในย่อหน้า 2\n"
            ."   เช่น คนธาตุไฟ → \"นิ่งให้เป็น คิดก่อนพูด\"  คนธาตุน้ำ → \"กล้าพูดในสิ่งที่รู้สึก\"\n\n"

            ."ย่อหน้า 6 — *คำคมจากลาส่วนตัว* (40-60 คำ — สำคัญที่สุด):\n"
            ."   นี่คือ *signature ของแม่หมอ* — เจ้าชะตาต้องจดจำคำนี้ไปทั้งชีวิต\n"
            ."   ✅ ต้องตรงกับอุปนิสัยและช่วงอายุที่อ่านได้\n"
            ."   ✅ ใช้ภาษาเปี่ยมความหมาย — เปรียบเทียบกับธรรมชาติ/วิถีชีวิต\n"
            ."   ✅ ห้ามทั่วไป (\"ขอให้โชคดี\", \"ทุกอย่างจะดีขึ้น\") — ต้องเฉพาะคนคนนี้เท่านั้น\n"
            ."   ตัวอย่าง: คนวัยกลางคน + ธาตุน้ำ → \"แม่น้ำที่เคยเชี่ยวกราก เมื่อพบทะเลก็ต้องนิ่งสงบ — เจ้าชะตาถึงจุดที่ใจต้องเรียนรู้สงบ\"\n"
            ."   ปิดด้วย \"แม่หมอขอลาเจ้าชะตาเพียงเท่านี้ ขอให้... 🙏✨\"\n\n"

            ."🚫 ข้อห้ามเด็ดขาด:\n"
            ."   1. ห้ามใช้ markdown (** ## - ฯลฯ) — plain text ล้วน\n"
            ."   2. ห้ามไล่ไพ่ทีละใบ — ผูกเรื่อง ไม่ใช่ list\n"
            ."   3. ห้ามทักทายว่า \"สวัสดี\" — เริ่มด้วย \"เจ้าชะตา...\" หรือชื่อโดยตรง\n"
            ."   4. ห้ามคำเลี่ยง \"อาจจะ/น่าจะ/บางที\" บ่อยเกินไป — ฟันธง\n"
            ."   5. ห้ามใส่ [END_SESSION] หรือ token พิเศษใดๆ\n"
            ."   6. ห้ามขายของ ขอติดตาม ขอแชร์ — บทสุดท้ายต้องสุขุม สง่างาม\n\n"

            ."📏 ความยาวรวม: 600-1000 คำ (พอดี อ่านสบาย)\n"
            ."🎭 โทน: ปรมาจารย์ผู้ผ่านโลกมามาก สุขุมอบอุ่น พูดน้อยแต่ลึก — เหมือนปู่ย่าให้พรหลานคนสนิท\n\n"

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
        $btnLabel = $picked === 0 ? '🃏 เปิดไพ่ใบที่ 1' : '🃏 เปิดไพ่ใบถัดไป';

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
        $maxQ = $this->getMaxQuestions();
        $usedQ = (int) ($reading->celtic_questions_used ?? 0);

        $timeHint = $remainingMin !== null && $remainingMin > 0
            ? "⏳ คุยกับแม่หมอได้อีก {$remainingMin} นาที"
            : ($remainingMin === 0
                ? '⏳ หมดเวลาคุยแล้ว — แม่หมอจะปิด session'
                : "⏳ คุยกับแม่หมอได้ภายใน {$this->getQaWindowMinutes()} นาทีนับจากคำทำนายแรก");

        $qHint = $maxQ > 0
            ? '❓ ถามได้อีก *'.max(0, $maxQ - $usedQ)."* จาก {$maxQ} คำถาม"
            : '❓ ถามได้ *ไม่จำกัด* (ภายในเวลาที่กำหนด)';

        $promptLine = $usedQ === 0
            ? '💬 พิมพ์ *คำถามแรก* ที่อยากรู้มาเลยค่ะ — แม่หมอจะอ่านพลังจากไพ่ทั้ง 10 ใบ'
            : '💬 พิมพ์ *คำถามถัดไป* ที่อยากรู้ — หรือพิมพ์ *"พอแค่นี้"* เพื่อจบสนทนา';

        return [
            'message' => $header
                ."🃏 เจ้าชะตาเปิดไพ่ครบ 10 ใบแล้ว ✅\n\n"
                .$promptLine."\n\n"
                .$timeHint."\n"
                .$qHint."\n"
                ."📋 บิล: {$billRef}",
            'quick_replies' => [],
        ];
    }
}
