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
        } catch (Exception $e) {
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
                userProfile: $reading->user_profile ?? null,
                userPosts: null,
                promptTemplate: null,
                readingType: 'deep', // ใช้ config deep — AI ต้องตอบยาว
                birthDate: $reading->birth_date?->format('Y-m-d'),
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
                'questions_remaining' => max(0, $this->getMaxQuestions() - $reading->fresh()->celtic_questions_used),
            ];
        } catch (Exception $e) {
            Log::error('CelticCross: askQuestion ล้มเหลว', [
                'reading_id' => $reading->id,
                'sequence' => $sequence,
                'error' => $e->getMessage(),
            ]);

            // ลบ record ที่ตอบไม่ได้ ให้ลูกค้าลองใหม่ได้
            $questionRecord->delete();

            return [
                'success' => false,
                'message' => 'AI ระบบขัดข้องชั่วคราว ' . $e->getMessage()
                    . "\n\nกรุณาลองใหม่อีกครั้ง — ถ้ายังไม่ได้ ติดต่อแอดมิน",
            ];
        }
    }

    /**
     * สร้าง Main Prompt (Q1) — full storytelling, ผูก 10 ไพ่ × ตำแหน่ง × คำถาม
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

        // Default prompt
        $cardsText = $this->formatCardsForPrompt($cards);

        return "คุณคือ \"{$brandName}\" หมอดูไพ่ยิปซีระดับเซียน เปิดไพ่ Celtic Cross 10 ใบให้ลูกค้าแล้ว\n\n"
            . "📋 คำถามของลูกค้า:\n\"{$userQuestion}\"\n\n"
            . "🃏 ไพ่ที่เปิดได้ทั้ง 10 ตำแหน่ง:\n{$cardsText}\n"
            . "──────────────────────\n"
            . "✍️ ภารกิจการทำนาย (กฎเข้ม):\n\n"
            . "1. **ห้ามอธิบายไพ่ทีละใบเรียงตำแหน่ง** เช่น \"ตำแหน่งที่ 1 ได้ไพ่ X หมายถึง... ตำแหน่งที่ 2...\" — ห้ามเด็ดขาด\n"
            . "2. **ผูกเรื่องราวต่อเนื่อง** จาก: ไพ่แต่ละใบ × ความหมายตำแหน่ง × สถานการณ์ปัจจุบันของลูกค้า — ให้เป็นเรื่องเดียวกัน อ่านแล้วเหมือนนิยายสั้น\n"
            . "3. **ทำนายตามหน้าไพ่จริง** — กลับหัว/ตั้งตรง บอกความหมายต่างกัน อย่าบิดให้เป็นบวกฝืน หรือลบฝืน\n"
            . "4. **ตอบคำถามตรงประเด็น** — ลูกค้าถามอะไร เน้นตอบเรื่องนั้นก่อน เอาไพ่มาสนับสนุน ไม่ใช่เอาไพ่นำแล้วบังคับลูกค้าตามไพ่\n"
            . "5. **โครงสร้างคำทำนาย** (ใช้ย่อหน้าแยก ไม่ต้องใส่หัวข้อ):\n"
            . "   ย่อหน้า 1: เปิดเรื่องสถานการณ์ปัจจุบัน (ใช้ไพ่ตำแหน่ง 1, 2, 5)\n"
            . "   ย่อหน้า 2: รากของปัญหา + ทัศนคติของเจ้าชะตา (ตำแหน่ง 4, 7, 9)\n"
            . "   ย่อหน้า 3: สิ่งที่กำลังจะเกิด + คนรอบข้าง (ตำแหน่ง 6, 8)\n"
            . "   ย่อหน้า 4: ผลลัพธ์ + คำแนะนำ (ตำแหน่ง 3, 10)\n\n"
            . "6. **ความยาว** 1500-2500 ตัวอักษร — แบ่งย่อหน้าอ่านง่าย\n"
            . "7. **โทน** อบอุ่น เป็นกันเอง เหมือนแม่หมอจริงๆ ที่นั่งอ่านไพ่ให้ — ไม่ขรึมจนเกินไป ไม่ตลกจนเกินไป\n"
            . "8. **ปิดท้ายด้วยคำแนะนำตรงประเด็น 2-3 ข้อ** ที่ลูกค้านำไปทำได้จริง\n"
            . "9. **ห้ามใช้ markdown** (** หรือ ##) — ใช้ plain text เท่านั้น\n"
            . "10. **ห้ามถามคำถามกลับ** — เพราะนี่คือคำทำนาย ไม่ใช่บทสนทนา\n\n"
            . "เริ่มทำนายเลย:";
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

        return "คุณคือ \"{$brandName}\" หมอดูไพ่ยิปซี กำลังตอบคำถามเพิ่มของลูกค้า (คำถามที่ {$sequence}/3)\n\n"
            . "🃏 ไพ่ที่เปิดไว้ก่อนหน้าทั้ง 10 ตำแหน่ง:\n{$cardsText}\n"
            . $previousContext
            . "❓ คำถามใหม่: \"{$userQuestion}\"\n\n"
            . "──────────────────────\n"
            . "✍️ กฎการตอบ (เคร่งครัด):\n\n"
            . "1. **ห้ามอธิบายไพ่ใหม่อีก** — ลูกค้าได้รับคำทำนายไพ่ครบแล้วใน Q1\n"
            . "2. **ห้ามทวนคำทำนายเดิม** — ตอบเฉพาะคำถามใหม่นี้\n"
            . "3. **ใช้ไพ่ที่เปิดไว้แล้ว** วิเคราะห์ตอบ — เลือกอ้างถึงไพ่ที่เกี่ยวข้องกับคำถามใหม่ (ไม่ต้องครบ 10)\n"
            . "4. **ความยาว** 400-700 ตัวอักษร — กระชับ ตรงประเด็น\n"
            . "5. **โทน** อบอุ่นเหมือน Q1 แต่เน้นวิเคราะห์ตอบโจทย์มากขึ้น\n"
            . "6. **ห้าม markdown** ห้าม **/##\n"
            . "7. **อ้างไพ่อย่างน้อย 2-3 ใบ** ที่เกี่ยวข้องกับคำถามใหม่ (เรียกชื่อไพ่ + ตำแหน่ง)\n"
            . "8. **ปิดท้ายด้วยคำแนะนำสั้นๆ 1-2 ประโยค**\n\n"
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
        return (int) ($this->settings->celtic_cross_qa_window_minutes ?? 60);
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
}
