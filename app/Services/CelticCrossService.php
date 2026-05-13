<?php

namespace App\Services;

use App\Models\FortuneCelticQuestion;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Models\TarotCard;
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

        // 🔮 (2026-05-07) Sentinel "__PREDICT_ALL__" → ทำนายพื้นฐานทุกเรื่อง (ไม่มีคำถามเฉพาะ)
        //   เก็บใน DB เป็นข้อความที่ admin/ลูกค้าอ่านเข้าใจ
        $isPredictAll = trim($userQuestion) === '__PREDICT_ALL__';
        $storedQuestion = $isPredictAll
            ? 'ทำนายดวงพื้นฐานจากไพ่ทั้ง 10 ใบ (รัก/งาน/เงิน/สุขภาพ/ครอบครัว)'
            : mb_substr($userQuestion, 0, 1000);

        // สร้าง record ใน fortune_celtic_questions ก่อน (เผื่อ AI fail)
        $questionRecord = FortuneCelticQuestion::create([
            'fortune_reading_id' => $reading->id,
            'sequence' => $sequence,
            'question' => $storedQuestion,
        ]);

        try {
            $startTime = microtime(true);

            // เลือก prompt template
            //   Q1 + sentinel = predict-all prompt (ทำนายพื้นฐานทุกเรื่อง)
            //   Q1 + คำถามจริง = main prompt (ตอบคำถามเฉพาะ)
            //   Q2+ = followup prompt
            if ($sequence === 1 && $isPredictAll) {
                $prompt = $this->buildPredictAllPrompt($reading, $cards);
            } elseif ($sequence === 1) {
                $prompt = $this->buildMainPrompt($reading, $userQuestion, $cards);
            } else {
                $prompt = $this->buildFollowupPrompt($reading, $userQuestion, $cards, $sequence);
            }

            // 🆕 (2026-05-07) Celtic 99฿ = paid prediction → request 'prediction' purpose
            //   ระบบจะเลือก key ที่ admin ตั้ง purpose='prediction' เป็นอันดับแรก
            //
            // 🌟 (2026-05-07) Sensitive AI Mode — สแกนคำถามลูกค้าก่อน generate
            //   ถ้าเข้าข่ายละเอียดอ่อน (อารมณ์ร้าย/หัวข้อหนัก/ซับซ้อน)
            //   → ใช้ purpose='sensitive' เลือก Pro key (Gemini Pro/GPT-5+)
            //   เลื่อนใช้แค่ใน Q2+ (followup) เพราะ Q1 + __PREDICT_ALL__ ไม่มีคำถามเฉพาะ
            $celticPurpose = 'prediction';
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

            $aiService = new FortuneAIService($this->settings, $celticPurpose);
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
            //   Q1 spec: 1500-2500 chars + กล่อง "🎯 ฟันธง" ปิดท้าย (บังคับ)
            //   ถ้า response 100-1500 chars หรือไม่มี "ฟันธง" → อาจถูกตัดจาก max_tokens
            //   ไม่ throw — ยังส่งให้ลูกค้า แต่ log ไว้ admin ตรวจ + ดู provider/model
            $responseLen = mb_strlen($response);
            $hasFundthong = str_contains($response, '🎯 ฟันธง')
                || str_contains($response, 'ฟันธง:')
                || str_contains($response, '🎯 สรุปฟันธง');
            if ($responseLen < 1500 || ! $hasFundthong) {
                Log::warning('CelticCross: response อาจถูก truncate / ฟันธงตกหล่น', [
                    'reading_id' => $reading->id,
                    'sequence' => $sequence,
                    'response_len' => $responseLen,
                    'has_fundthong' => $hasFundthong,
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

        return "คุณคือ \"{$brandName}\" — หมอดูไพ่ยิปซีระดับเซียน 30+ ปี\n"
            ."พลังพิเศษ: อ่านพลังงานจักรวาลที่หลั่งผ่านไพ่ + จิตเจ้าชะตาที่ตั้งสมาธิเลือกไพ่\n"
            ."บุคลิก: สุขุม นิ่ง อบอุ่น เปี่ยมพลัง — พูดน้อย แต่แทงใจดำได้ทุกประโยค\n"
            ."เจ้าชะตาเพิ่งตั้งจิตให้นิ่ง เปิดไพ่ Celtic Cross 10 ใบเสร็จ — ทุกใบสุ่มจากกระแสจิตของตน ไม่ใช่บังเอิญ\n\n"

            ."━━━━━━━━━━━━━━━━━\n"
            ."📋 คำถามแรกของเจ้าชะตา:\n\"{$userQuestion}\"\n\n"
            ."🃏 ไพ่ทั้ง 10 ที่กระแสจิตเจ้าชะตาเลือก:\n{$cardsText}\n"
            ."━━━━━━━━━━━━━━━━━\n\n"

            ."✍️ ภารกิจคำทำนาย (กฎเข้มงวด):\n\n"

            ."🎯 หลักสำคัญที่สุด — ฟันธง ตรงประเด็น ไม่อ้อมค้อม:\n"
            ."   • ห้ามถามวันเกิด/ราศี/ดาวประจำวัน — แม่หมอใช้พลังจักรวาล + ไพ่ + จิตเจ้าชะตา เพียง 3 อย่างนี้\n"
            ."   • ทำให้เจ้าชะตารู้สึก \"ทำไมแม่หมอรู้?\" — สังเกตจากคำถาม สังเกตไพ่ ผูกออกมาให้แทงใจ\n"
            ."   • 🔥 ฟันธงเสมอ — ห้ามใช้ \"อาจจะ/น่าจะ/บางที/ขึ้นอยู่กับ\" หลีกเลี่ยงคำเลี่ยง\n"
            ."   • ดี → บอกว่าดี / ไม่ดี → บอกตรงว่าไม่ดี (เตือนได้แต่อย่าอ้อม)\n"
            ."   • คำถาม yes/no (เช่น \"มีคู่ไหม\" / \"ได้งานไหม\" / \"ควรไปไหม\") → ตอบ มี/ไม่มี, ได้/ไม่ได้, ควร/ไม่ควร แล้วค่อยอธิบายเหตุผลจากไพ่\n"
            ."   • ห้ามทิ้งคำตอบลอยๆ — ทุกคำถามต้อง commit คำตอบฟันธงก่อน แล้วขยายเหตุผล\n"
            ."   • ตัวละคร/บุคคล/เหตุการณ์/สัญลักษณ์ในไพ่ — เล่าให้หมด ห้ามข้าม (เช่น The Lovers = มีคนเข้ามา/รัก / Death = จบ-เริ่มใหม่ / 3 of Swords = อกหัก/แตกหัก)\n\n"

            ."📜 โครงสร้างคำทำนาย (ย่อหน้าแยก ไม่มีหัวข้อ ไม่มี markdown):\n"
            ."   ย่อหน้า 1 (เปิดด้วยพลัง + ฟันธงคำตอบ): สถานการณ์ปัจจุบันที่เจ้าชะตาแบกอยู่ — ใช้ไพ่ตำแหน่ง 1, 2, 5\n"
            ."        ⚡ ถ้าคำถาม yes/no → ใส่คำตอบฟันธงในย่อหน้านี้ทันที (เช่น \"มี.\" / \"ไม่ได้.\" / \"ควร.\")\n"
            ."   ย่อหน้า 2 (ล้วงลึก): รากของเรื่อง + ทัศนคติเจ้าชะตา + ความหวังกลัวที่ซ่อน — ใช้ตำแหน่ง 4, 7, 9\n"
            ."   ย่อหน้า 3 (กระแสรอบข้าง + ตัวละคร): สิ่งที่กำลังเข้ามา + คน/สิ่งแวดล้อมที่ส่งผล — ใช้ตำแหน่ง 6, 8\n"
            ."        🎭 ระบุ \"คน\" ในไพ่ให้ชัด (เช่น \"ผู้ชายคนหนึ่ง อายุประมาณ X\" / \"ผู้หญิงในวงงาน\")\n"
            ."   ย่อหน้า 4 (ปลายทาง — ฟันธงผลลัพธ์): จุดมุ่งหมายในใจ + ผลลัพธ์ตามแนวโน้ม — ใช้ตำแหน่ง 3, 10\n"
            ."        🎯 ฟันธงผลลัพธ์ — สำเร็จ/ล้มเหลว, ดี/ไม่ดี, ระบุชัด\n"
            ."   ย่อหน้า 5 (คำเตือน + คำแนะนำ): ถ้าไพ่เตือนเรื่องไม่ดี → เตือนตรงๆ + 2-3 ข้อปฏิบัติได้จริง\n"
            ."   ย่อหน้า 6 (🎯 สรุปฟันธง — บังคับ บรรทัดสุดท้าย): ขึ้นบรรทัดใหม่ เริ่มด้วย \"🎯 ฟันธง: \" ตอบคำถามเจ้าชะตาตรงๆ 1-2 บรรทัด *เนื้อๆ ห้ามน้ำ*\n"
            ."        • คำถาม yes/no → \"🎯 ฟันธง: ใช่ — [เหตุผลสั้น 1 ประโยค]\" หรือ \"🎯 ฟันธง: ไม่ใช่ — [เหตุผล]\"\n"
            ."          ตัวอย่าง: \"🎯 ฟันธง: มี — ผู้ชายวัย 30+ จากแวดวงงาน เข้ามาก่อน ก.ค. นี้\"\n"
            ."        • คำถามผลลัพธ์ → \"🎯 ฟันธง: [ผลเด็ดขาด + timeline + คนเกี่ยวข้อง]\"\n"
            ."          ตัวอย่าง: \"🎯 ฟันธง: ได้งานใหม่ก่อน มิ.ย. — เป็นงานหุ้นส่วน รายได้เพิ่ม 20%\"\n"
            ."        • ห้ามใช้ \"อาจจะ/ขึ้นอยู่กับ/แล้วแต่/ทุกอย่างเปลี่ยนได้\" — ฟันธงข้างเดียว\n\n"

            ."🚫 ข้อห้ามเด็ดขาด:\n"
            ."   1. ห้ามอธิบายไพ่ทีละใบ \"ตำแหน่ง 1 ได้ไพ่ X... ตำแหน่ง 2 ได้ไพ่ Y...\" — ผูกเรื่อง ไม่ใช่ list\n"
            ."   2. ห้ามทำนายฝืนหน้าไพ่ — กลับหัว = ติดขัด/พลิกผัน, ตั้งตรง = ราบรื่น/ตามครรลอง\n"
            ."   3. ห้ามใช้ markdown (**, ##, -, ฯลฯ) — plain text ล้วน\n"
            ."   4. ห้ามถามกลับใน Q1 — นี่คือคำทำนายหลัก ไม่ใช่บทสนทนา\n"
            ."   5. ห้ามอ้างอิงวันเดือนปีเกิด/ดาว/ราศี/เลขมงคล — ใช้แค่พลังไพ่ + จิตเจ้าชะตาเท่านั้น\n"
            ."   6. ห้ามตอบลอยๆ \"แล้วแต่กรรมเก่า\" / \"อยู่ที่ตัวคุณ\" / \"ทุกอย่างเปลี่ยนได้\" — ฟันธงจากไพ่ก่อน คำแนะนำตอนท้าย\n\n"

            ."📏 ความยาว: 1500-2500 ตัวอักษร แบ่งย่อหน้า เว้นบรรทัดอ่านง่าย\n"
            ."🎭 โทน: สุขุม อบอุ่น มั่นใจ ฟันธง — เหมือนแม่หมอจริงที่เห็นเจ้าชะตาผ่านควันธูป รู้คำตอบแน่ๆ ไม่อ้อม\n\n"

            ."💡 หลังคำทำนาย: ปิดท้ายด้วยประโยคเชิญชวนเจ้าชะตาถามต่อในประเด็นที่ยังค้าง — น้ำเสียงสุขุมไม่กดดัน\n"
            .'เริ่มทำนายเลย (เริ่มประโยคแรกด้วยพลัง อย่าทักทายซ้ำ):';
    }

    /**
     * 🔮 (2026-05-07) Predict-All Prompt — ทำนายพื้นฐานทุกเรื่องจากไพ่ทั้ง 10 ใบ ไม่มีคำถามเฉพาะ
     *
     * Trigger: ลูกค้ากดปุ่ม "🔮 ทำนายดวงเดี๋ยวนี้" หลังเปิดไพ่ครบ 10 ใบ
     * Spec:
     *   - ทำนายครอบคลุม 5 เรื่อง: ความรัก / การงาน / การเงิน / สุขภาพ / ครอบครัว+ครอบครัว
     *   - ฟันธงทุกเรื่อง — ไม่อ้อมค้อม
     *   - ตัวละคร/เหตุการณ์ในไพ่ → เล่าให้หมด
     *   - ดีว่าดี ไม่ดีว่าไม่ดี + เตือน
     */
    protected function buildPredictAllPrompt(FortuneReading $reading, array $cards): string
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
     * สร้าง Followup Prompt (Q2-Q3) — ตอบคำถามใหม่โดยใช้ไพ่เดิม ไม่อธิบายไพ่ใหม่
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

        // ดึง Q1 ตอบไว้ → ส่งให้ AI เพื่อความต่อเนื่อง
        $previousQA = $reading->celticQuestions()
            ->whereNotNull('answered_at')
            ->orderBy('sequence')
            ->get();

        $previousContext = '';
        if ($previousQA->isNotEmpty()) {
            $previousContext = "📜 บทสนทนาที่ผ่านมา (เพื่อให้ตอบสอดคล้อง):\n";
            foreach ($previousQA as $q) {
                $previousContext .= "Q{$q->sequence}: {$q->question}\n";
                $previousContext .= 'A'.$q->sequence.': '.mb_substr($q->response ?? '', 0, 500)."...\n\n";
            }
        }

        return "คุณคือ \"{$brandName}\" — หมอดูไพ่ยิปซีระดับเซียน กำลังคุยกับเจ้าชะตาต่อเนื่องจากคำทำนายหลัก\n"
            ."บุคลิก: สุขุม นิ่ง อบอุ่น มีจิตวิทยาลึก — ควบคุมบทสนทนาได้ดี ไม่ปล่อยให้เจ้าชะตานอกเรื่อง\n"
            ."นี่คือคำถามที่ {$sequence} (ระบบไม่จำกัดจำนวน — เจ้าชะตาถามได้จนพอใจภายใน 30 นาที)\n\n"

            ."━━━━━━━━━━━━━━━━━\n"
            ."🃏 ไพ่ทั้ง 10 ที่เปิดไว้แล้ว (ใช้เป็นฐานวิเคราะห์เท่านั้น — ห้ามสุ่มไพ่ใหม่):\n{$cardsText}\n"
            ."━━━━━━━━━━━━━━━━━\n\n"
            .$previousContext
            ."❓ คำถาม/ข้อความใหม่จากเจ้าชะตา:\n\"{$userQuestion}\"\n\n"

            ."━━━━━━━━━━━━━━━━━\n"
            ."✍️ กฎการตอบ (เคร่งครัด):\n\n"

            ."🎯 ขั้นตอนคิด (ทำในใจก่อนตอบ):\n"
            ."   ขั้น 1: คำถามใหม่นี้ \"เกี่ยวข้องกับไพ่/เรื่องดูดวง\" หรือ \"นอกเรื่อง/ทดสอบ/แกล้ง\"?\n"
            ."       • เกี่ยว = ความรัก งาน เงิน สุขภาพ ครอบครัว การตัดสินใจ ขอคำแนะนำเพิ่มเติม → ตอบตามไพ่ปกติ\n"
            ."       • นอกเรื่อง = ถามดิน ฟ้า อากาศ การเมือง คณิตศาสตร์ ชวนคุยทั่วไป ลองทดสอบ AI → ใช้สูตรปฏิเสธสุขุมด้านล่าง\n"
            ."   ขั้น 2: ถ้าเกี่ยว → คำตอบนี้ \"ปิดประเด็นได้สมบูรณ์\" หรือ \"ยังเปิดให้ถามต่อได้\"?\n\n"

            ."🚫 ถ้านอกเรื่อง/แกล้ง (ใช้สูตรนี้ — สุขุม ไม่โกรธ):\n"
            ."   ตัวอย่างเช่น: \"แม่หมอสัมผัสได้ว่าเรื่องที่ถามมานี้อยู่นอกขอบเขตของไพ่ที่เจ้าชะตาเลือกในวันนี้\n"
            ."   หากเจ้าชะตาไม่มีคำถามเรื่องดวงเพิ่มเติม แม่หมอขอจบบทสนทนานี้\n"
            ."   เพื่อไปสร้างบารมีให้เจ้าชะตาท่านอื่นต่อนะ ขอให้เจ้าชะตาโชคดีค่ะ\"\n"
            ."   → จากนั้นใส่ token [END_SESSION] ปิดท้ายข้อความ (ระบบจะใช้ปิด session)\n\n"

            ."✅ ถ้าคำถามเกี่ยวกับดวง — ฟันธง ตรงประเด็น ไม่อ้อมค้อม:\n"
            ."   1. ห้ามทวนคำทำนาย Q1 ทั้งหมด — ตอบเฉพาะประเด็นใหม่\n"
            ."   2. อ้างถึงไพ่ที่เกี่ยวข้อง 2-3 ใบ (เรียกชื่อไพ่ + ตำแหน่ง) — ใช้เป็นฐานวิเคราะห์ ไม่อธิบายไพ่ใหม่\n"
            ."   3. 🔥 ฟันธงเสมอ — คำถาม yes/no (\"มีคู่ไหม\" / \"ได้งานไหม\" / \"ควรไปไหม\") → ตอบ มี/ไม่มี, ได้/ไม่ได้, ควร/ไม่ควร ในประโยคแรก แล้วค่อยอธิบาย\n"
            ."   4. ห้ามใช้ \"อาจจะ/น่าจะ/บางที/ขึ้นอยู่กับ/แล้วแต่\" — ฟันธงจากไพ่\n"
            ."   5. ดี → บอกตรงว่าดี / ไม่ดี → บอกตรงว่าไม่ดี (เตือนได้แต่ห้ามอ้อม)\n"
            ."   6. ตัวละคร/บุคคล/เหตุการณ์ในไพ่ → เล่าให้หมด ห้ามข้าม\n"
            ."   7. ความยาว 500-900 ตัวอักษร กระชับ ตรงประเด็น (ฟันธงสั้นได้ ขยายเหตุผลยาว)\n"
            ."   8. โทนสุขุม อบอุ่น เปี่ยมพลัง มั่นใจ ฟันธง — เหมือนแม่หมอเห็นชัดจากไพ่\n"
            ."   9. ห้าม markdown (** ## - ฯลฯ) — plain text\n"
            ."   10. ห้ามอ้างวันเดือนปีเกิด/ราศี/ดาวเจ้าชนะ — ใช้แค่ไพ่ + จิตเจ้าชะตา\n"
            ."   11. ห้ามตอบลอยๆ \"อยู่ที่ตัวคุณ\" \"กรรมเก่า\" \"ทุกอย่างเปลี่ยนได้\" — ฟันธงจากไพ่ก่อน คำแนะนำตอนท้าย\n"
            ."   12. 🎯 *บังคับใส่บรรทัดสรุปฟันธง* — บรรทัดรองสุดท้าย ก่อนประโยคเชิญถามต่อ/[END_SESSION]\n"
            ."        • รูปแบบ: \"🎯 ฟันธง: [คำตอบเนื้อๆ 1 บรรทัด]\"\n"
            ."        • คำถาม yes/no → \"🎯 ฟันธง: ใช่ — [เหตุผลสั้น]\" / \"🎯 ฟันธง: ไม่ใช่ — [เหตุผล]\"\n"
            ."        • คำถามผลลัพธ์ → \"🎯 ฟันธง: [ผลลัพธ์ + timeline + คนเกี่ยวข้อง]\"\n"
            ."        • ห้าม \"อาจจะ/แล้วแต่/ขึ้นอยู่กับ\" — ตอบเด็ดขาดข้างเดียว\n\n"

            ."🔚 ปิดท้ายข้อความ (ทำขั้น 2 จากขั้นตอนคิด):\n"
            ."   • ถ้ารู้สึกว่า \"ครอบคลุมเรื่องที่เจ้าชะตาควรรู้แล้ว / ลูกค้าถามวกวนเรื่องเดิมๆ / ลูกค้าเริ่มหลุดประเด็น\"\n"
            ."       → ปิดด้วยประโยคสุขุมเช่น \"แม่หมอว่าเจ้าชะตาได้คำตอบที่ต้องการแล้ว ถ้าไม่มีอะไรค้าง แม่หมอขอจบบทสนทนานี้นะคะ\"\n"
            ."       → แล้วใส่ token [END_SESSION] ที่ท้ายข้อความ\n"
            ."   • ถ้ายังเห็นว่าเจ้าชะตามีประเด็นค้างที่ควรถามต่อ\n"
            ."       → ปิดด้วยประโยคเชิญถามต่อแบบไม่กดดัน เช่น \"เจ้าชะตาอยากให้แม่หมอช่วยมองเรื่องไหนเพิ่มไหมคะ\"\n"
            ."       → ห้ามใส่ [END_SESSION]\n\n"

            ."⚠️ token [END_SESSION] = สัญญาณให้ระบบจบ session — ใช้เมื่อคำตอบนี้ \"พอแล้ว\" จริงๆ เท่านั้น\n"
            .'เริ่มตอบเลย:';
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
     */
    public function resetPickedCards(FortuneReading $reading): void
    {
        if ($reading->celtic_questions_used > 0) {
            // ห้าม reset ถ้าตอบ Q ไปแล้ว (anti-fraud)
            throw new Exception('ไม่สามารถ reset ได้ — ได้รับคำทำนายไปแล้ว');
        }

        $reading->setConversationState('celtic_cards', []);
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
