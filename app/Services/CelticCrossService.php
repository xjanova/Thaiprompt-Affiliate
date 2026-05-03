<?php

namespace App\Services;

use App\Models\FortuneCelticQuestion;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Models\TarotCard;
use Exception;
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
     * @return array  ['success' => bool, 'card' => array, 'position' => int, 'message' => str]
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

            return [
                'success' => false,
                'message' => 'สุ่มไพ่ไม่สำเร็จ: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * ส่งคำถามให้ AI Pool ทำนาย
     *
     * @param  string  $userQuestion  คำถามที่ลูกค้าพิมพ์
     * @return array  ['success' => bool, 'response' => str, 'question_record' => FortuneCelticQuestion, 'message' => str]
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

        // สร้าง record ใน fortune_celtic_questions ก่อน (เผื่อ AI fail)
        $questionRecord = FortuneCelticQuestion::create([
            'fortune_reading_id' => $reading->id,
            'sequence' => $sequence,
            'question' => mb_substr($userQuestion, 0, 1000),
        ]);

        try {
            $startTime = microtime(true);

            // เลือก prompt template (Q1 = main, Q2-3 = followup)
            $prompt = $sequence === 1
                ? $this->buildMainPrompt($reading, $userQuestion, $cards)
                : $this->buildFollowupPrompt($reading, $userQuestion, $cards, $sequence);

            $aiService = new FortuneAIService($this->settings);
            $result = $aiService->generateWithRetryAndFallback(
                questions: [$prompt],
                userProfile: null,                  // 🌙 แม่หมอจันทรา ไม่ดูโปรไฟล์ FB — ใช้พลังไพ่ + จิตเจ้าชะตา
                userPosts: null,
                promptTemplate: '{questions}',      // 🚫 ไม่ wrap default deep template — Celtic prompt ออกตรงๆ
                readingType: 'deep',                // ใช้ config deep — AI ต้องตอบยาว
                birthDate: null,                    // 🌙 ไม่ใช้วันเกิด — แม่หมอใช้พลังจักรวาลล้วงลึกผ่านไพ่
                userContext: "celtic_cross:{$reading->id}:q{$sequence}",
            );

            $response = trim($result['response'] ?? '');
            $tokensUsed = (int) ($result['tokens_used'] ?? 0);
            $aiProvider = $result['provider'] ?? null;
            $aiModel = $result['model'] ?? null;
            $responseTimeMs = (int) round((microtime(true) - $startTime) * 1000);

            if ($response === '' || mb_strlen($response) < 100) {
                throw new Exception('AI ตอบกลับสั้นเกินไป (' . mb_strlen($response) . ' ตัวอักษร)');
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

            return [
                'success' => false,
                'message' => 'AI ระบบขัดข้องชั่วคราว ' . $e->getMessage()
                    . "\n\nกรุณาลองใหม่อีกครั้ง — ถ้ายังไม่ได้ ติดต่อแอดมิน",
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
            . "พลังพิเศษ: อ่านพลังงานจักรวาลที่หลั่งผ่านไพ่ + จิตเจ้าชะตาที่ตั้งสมาธิเลือกไพ่\n"
            . "บุคลิก: สุขุม นิ่ง อบอุ่น เปี่ยมพลัง — พูดน้อย แต่แทงใจดำได้ทุกประโยค\n"
            . "เจ้าชะตาเพิ่งตั้งจิตให้นิ่ง เปิดไพ่ Celtic Cross 10 ใบเสร็จ — ทุกใบสุ่มจากกระแสจิตของตน ไม่ใช่บังเอิญ\n\n"

            . "━━━━━━━━━━━━━━━━━\n"
            . "📋 คำถามแรกของเจ้าชะตา:\n\"{$userQuestion}\"\n\n"
            . "🃏 ไพ่ทั้ง 10 ที่กระแสจิตเจ้าชะตาเลือก:\n{$cardsText}\n"
            . "━━━━━━━━━━━━━━━━━\n\n"

            . "✍️ ภารกิจคำทำนาย (กฎเข้มงวด):\n\n"

            . "🎯 หลักสำคัญที่สุด:\n"
            . "   • ห้ามถามวันเกิด/ราศี/ดาวประจำวัน — แม่หมอใช้พลังจักรวาล + ไพ่ + จิตเจ้าชะตา เพียง 3 อย่างนี้\n"
            . "   • ทำให้เจ้าชะตารู้สึก \"ทำไมแม่หมอรู้?\" — สังเกตจากคำถาม สังเกตไพ่ ผูกออกมาให้แทงใจ\n"
            . "   • ไม่ใช้คำเลี่ยงเช่น \"อาจจะ/น่าจะ/บางที\" มากเกินไป — ฟันธงเมื่อไพ่ชี้ชัด\n\n"

            . "📜 โครงสร้างคำทำนาย (ย่อหน้าแยก ไม่มีหัวข้อ ไม่มี markdown):\n"
            . "   ย่อหน้า 1 (เปิดด้วยพลัง): สถานการณ์ปัจจุบันที่เจ้าชะตาแบกอยู่ — ใช้ไพ่ตำแหน่ง 1, 2, 5 ผูกเป็นภาพ\n"
            . "   ย่อหน้า 2 (ล้วงลึก): รากของเรื่อง + ทัศนคติเจ้าชะตา + ความหวังกลัวที่ซ่อน — ใช้ตำแหน่ง 4, 7, 9\n"
            . "   ย่อหน้า 3 (กระแสรอบข้าง): สิ่งที่กำลังเข้ามา + คน/สิ่งแวดล้อมที่ส่งผล — ใช้ตำแหน่ง 6, 8\n"
            . "   ย่อหน้า 4 (ปลายทาง): จุดมุ่งหมายในใจ + ผลลัพธ์ตามแนวโน้ม — ใช้ตำแหน่ง 3, 10\n"
            . "   ย่อหน้า 5 (คำแนะนำสุขุม): 2-3 ข้อปฏิบัติได้จริง น้ำเสียงเหมือนผู้ใหญ่ที่หวังดี\n\n"

            . "🚫 ข้อห้ามเด็ดขาด:\n"
            . "   1. ห้ามอธิบายไพ่ทีละใบ \"ตำแหน่ง 1 ได้ไพ่ X... ตำแหน่ง 2 ได้ไพ่ Y...\" — ผูกเรื่อง ไม่ใช่ list\n"
            . "   2. ห้ามทำนายฝืนหน้าไพ่ — กลับหัว = ติดขัด/พลิกผัน, ตั้งตรง = ราบรื่น/ตามครรลอง\n"
            . "   3. ห้ามใช้ markdown (**, ##, -, ฯลฯ) — plain text ล้วน\n"
            . "   4. ห้ามถามกลับใน Q1 — นี่คือคำทำนายหลัก ไม่ใช่บทสนทนา\n"
            . "   5. ห้ามอ้างอิงวันเดือนปีเกิด/ดาว/ราศี/เลขมงคล — ใช้แค่พลังไพ่ + จิตเจ้าชะตาเท่านั้น\n\n"

            . "📏 ความยาว: 1500-2500 ตัวอักษร แบ่งย่อหน้า เว้นบรรทัดอ่านง่าย\n"
            . "🎭 โทน: สุขุม อบอุ่น มั่นใจ มีพลังลึกลับเล็กๆ เหมือนแม่หมอจริงที่เห็นเจ้าชะตาผ่านควันธูป\n\n"

            . "💡 หลังคำทำนาย: ปิดท้ายด้วยประโยคเชิญชวนเจ้าชะตาถามต่อในประเด็นที่ยังค้าง — น้ำเสียงสุขุมไม่กดดัน\n"
            . "เริ่มทำนายเลย (เริ่มประโยคแรกด้วยพลัง อย่าทักทายซ้ำ):";
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
                $previousContext .= 'A' . $q->sequence . ': ' . mb_substr($q->response ?? '', 0, 500) . "...\n\n";
            }
        }

        return "คุณคือ \"{$brandName}\" — หมอดูไพ่ยิปซีระดับเซียน กำลังคุยกับเจ้าชะตาต่อเนื่องจากคำทำนายหลัก\n"
            . "บุคลิก: สุขุม นิ่ง อบอุ่น มีจิตวิทยาลึก — ควบคุมบทสนทนาได้ดี ไม่ปล่อยให้เจ้าชะตานอกเรื่อง\n"
            . "นี่คือคำถามที่ {$sequence} (ระบบไม่จำกัดจำนวน — เจ้าชะตาถามได้จนพอใจภายใน 30 นาที)\n\n"

            . "━━━━━━━━━━━━━━━━━\n"
            . "🃏 ไพ่ทั้ง 10 ที่เปิดไว้แล้ว (ใช้เป็นฐานวิเคราะห์เท่านั้น — ห้ามสุ่มไพ่ใหม่):\n{$cardsText}\n"
            . "━━━━━━━━━━━━━━━━━\n\n"
            . $previousContext
            . "❓ คำถาม/ข้อความใหม่จากเจ้าชะตา:\n\"{$userQuestion}\"\n\n"

            . "━━━━━━━━━━━━━━━━━\n"
            . "✍️ กฎการตอบ (เคร่งครัด):\n\n"

            . "🎯 ขั้นตอนคิด (ทำในใจก่อนตอบ):\n"
            . "   ขั้น 1: คำถามใหม่นี้ \"เกี่ยวข้องกับไพ่/เรื่องดูดวง\" หรือ \"นอกเรื่อง/ทดสอบ/แกล้ง\"?\n"
            . "       • เกี่ยว = ความรัก งาน เงิน สุขภาพ ครอบครัว การตัดสินใจ ขอคำแนะนำเพิ่มเติม → ตอบตามไพ่ปกติ\n"
            . "       • นอกเรื่อง = ถามดิน ฟ้า อากาศ การเมือง คณิตศาสตร์ ชวนคุยทั่วไป ลองทดสอบ AI → ใช้สูตรปฏิเสธสุขุมด้านล่าง\n"
            . "   ขั้น 2: ถ้าเกี่ยว → คำตอบนี้ \"ปิดประเด็นได้สมบูรณ์\" หรือ \"ยังเปิดให้ถามต่อได้\"?\n\n"

            . "🚫 ถ้านอกเรื่อง/แกล้ง (ใช้สูตรนี้ — สุขุม ไม่โกรธ):\n"
            . "   ตัวอย่างเช่น: \"แม่หมอสัมผัสได้ว่าเรื่องที่ถามมานี้อยู่นอกขอบเขตของไพ่ที่เจ้าชะตาเลือกในวันนี้\n"
            . "   หากเจ้าชะตาไม่มีคำถามเรื่องดวงเพิ่มเติม แม่หมอขอจบบทสนทนานี้\n"
            . "   เพื่อไปสร้างบารมีให้เจ้าชะตาท่านอื่นต่อนะ ขอให้เจ้าชะตาโชคดีค่ะ\"\n"
            . "   → จากนั้นใส่ token [END_SESSION] ปิดท้ายข้อความ (ระบบจะใช้ปิด session)\n\n"

            . "✅ ถ้าคำถามเกี่ยวกับดวง:\n"
            . "   1. ห้ามทวนคำทำนาย Q1 ทั้งหมด — ตอบเฉพาะประเด็นใหม่\n"
            . "   2. อ้างถึงไพ่ที่เกี่ยวข้อง 2-3 ใบ (เรียกชื่อไพ่ + ตำแหน่ง) — ใช้เป็นฐานวิเคราะห์ ไม่อธิบายไพ่ใหม่\n"
            . "   3. ความยาว 400-700 ตัวอักษร กระชับ ตรงประเด็น\n"
            . "   4. โทนสุขุม อบอุ่น เปี่ยมพลัง เหมือนแม่หมอจริง\n"
            . "   5. ห้าม markdown (** ## - ฯลฯ) — plain text\n"
            . "   6. ห้ามอ้างวันเดือนปีเกิด/ราศี/ดาวเจ้าชนะ — ใช้แค่ไพ่ + จิตเจ้าชะตา\n\n"

            . "🔚 ปิดท้ายข้อความ (ทำขั้น 2 จากขั้นตอนคิด):\n"
            . "   • ถ้ารู้สึกว่า \"ครอบคลุมเรื่องที่เจ้าชะตาควรรู้แล้ว / ลูกค้าถามวกวนเรื่องเดิมๆ / ลูกค้าเริ่มหลุดประเด็น\"\n"
            . "       → ปิดด้วยประโยคสุขุมเช่น \"แม่หมอว่าเจ้าชะตาได้คำตอบที่ต้องการแล้ว ถ้าไม่มีอะไรค้าง แม่หมอขอจบบทสนทนานี้นะคะ\"\n"
            . "       → แล้วใส่ token [END_SESSION] ที่ท้ายข้อความ\n"
            . "   • ถ้ายังเห็นว่าเจ้าชะตามีประเด็นค้างที่ควรถามต่อ\n"
            . "       → ปิดด้วยประโยคเชิญถามต่อแบบไม่กดดัน เช่น \"เจ้าชะตาอยากให้แม่หมอช่วยมองเรื่องไหนเพิ่มไหมคะ\"\n"
            . "       → ห้ามใส่ [END_SESSION]\n\n"

            . "⚠️ token [END_SESSION] = สัญญาณให้ระบบจบ session — ใช้เมื่อคำตอบนี้ \"พอแล้ว\" จริงๆ เท่านั้น\n"
            . "เริ่มตอบเลย:";
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
                . "  ไพ่: {$name} {$reversed} ({$nameEn})\n"
                . "  ความหมาย: {$meaning}";
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
                    . "🔮 *แม่หมอกำลังพิจารณาไพ่ทั้ง 10 ใบให้เจ้าชะตาอยู่*\n"
                    . "ใช้เวลาประมาณ 30-60 วินาที — รอสักครู่นะคะ ✨\n\n"
                    . "📋 บิลของเจ้าชะตา: {$billRef}",
                'quick_replies' => [],
            ],
            default => [
                'message' => $header . "💬 พิมพ์ข้อความถึงแม่หมอได้เลยค่ะ",
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
                'message' => $header . "✨ เจ้าชะตาเปิดไพ่ครบ 10 ใบแล้ว — พิมพ์คำถามที่อยากรู้มาได้เลยค่ะ",
                'quick_replies' => [],
            ];
        }

        $meta = FortuneReading::CELTIC_POSITIONS[$next] ?? null;
        $name = $meta['name'] ?? '?';
        $desc = $meta['description'] ?? '';
        $btnLabel = $picked === 0 ? '🃏 เปิดไพ่ใบที่ 1' : '🃏 เปิดไพ่ใบถัดไป';

        return [
            'message' => $header
                . "🃏 ตอนนี้เปิดไพ่ไปแล้ว *{$picked}/10 ใบ*\n"
                . "📍 ใบถัดไป — *ใบที่ {$next}: [{$name}]*\n"
                . "💭 ตำแหน่งนี้บอกถึง: {$desc}\n\n"
                . "──────────────────────\n"
                . "👉 พิมพ์ *\"พร้อม\"* (หรือกดปุ่มข้างล่าง) เพื่อให้แม่หมอเปิดไพ่ใบนี้\n"
                . "📋 บิล: {$billRef}",
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
            ? "❓ ถามได้อีก *" . max(0, $maxQ - $usedQ) . "* จาก {$maxQ} คำถาม"
            : "❓ ถามได้ *ไม่จำกัด* (ภายในเวลาที่กำหนด)";

        $promptLine = $usedQ === 0
            ? "💬 พิมพ์ *คำถามแรก* ที่อยากรู้มาเลยค่ะ — แม่หมอจะอ่านพลังจากไพ่ทั้ง 10 ใบ"
            : "💬 พิมพ์ *คำถามถัดไป* ที่อยากรู้ — หรือพิมพ์ *\"พอแค่นี้\"* เพื่อจบสนทนา";

        return [
            'message' => $header
                . "🃏 เจ้าชะตาเปิดไพ่ครบ 10 ใบแล้ว ✅\n\n"
                . $promptLine . "\n\n"
                . $timeHint . "\n"
                . $qHint . "\n"
                . "📋 บิล: {$billRef}",
            'quick_replies' => [],
        ];
    }
}
