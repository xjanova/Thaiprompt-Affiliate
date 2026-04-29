<?php

namespace App\Services\Fortune;

use App\Models\FortuneReading;
use App\Models\UniquePaymentAmount;
use App\Services\CelticCrossService;
use App\Services\CelticSpreadImageGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Trait สำหรับจัดการ Celtic Cross Tarot Mode ใน FortuneConversationService
 *
 * แยกออกมาเพื่อไม่ให้ FortuneConversationService บวมเกินไป (~11000 บรรทัด)
 *
 * ใช้:
 *   class FortuneConversationService { use CelticCrossConversationTrait; }
 *
 * State flow:
 *   STATUS_CELTIC_PENDING_PAYMENT → ลูกค้าจ่าย 99฿ → STATUS_CELTIC_PICKING
 *   STATUS_CELTIC_PICKING (1-10) → ลูกค้ากด "พร้อม" 10 ครั้ง → STATUS_CELTIC_AWAITING_QUESTION (เริ่ม)
 *   STATUS_CELTIC_AWAITING_QUESTION → ลูกค้าพิมพ์คำถาม → STATUS_CELTIC_GENERATING (AI กำลังตอบ)
 *   STATUS_CELTIC_QA_PROMPT → ลูกค้ากด "ถามต่อ"/"พอแค่นี้"
 *     - ถามต่อ → กลับ AWAITING_QUESTION
 *     - พอแค่นี้ หรือ ครบ 3/3 → STATUS_COMPLETED
 */
trait CelticCrossConversationTrait
{
    /**
     * Dispatch handler สำหรับ Celtic Cross states + Tier Choice
     * เรียกจาก main dispatch ใน FortuneConversationService
     *
     * @return array|null  null ถ้าไม่ใช่ Celtic state — ให้ caller ส่งต่อ default handler
     */
    protected function handleCelticState(FortuneReading $reading, string $messageText): ?array
    {
        $status = $reading->conversation_status;

        return match ($status) {
            // 🆕 (2026-04-29) Tier choice — ลูกค้าเลือก 39฿ deep หรือ 99฿ Celtic
            FortuneReading::STATUS_TIER_CHOICE => $this->handleTierChoice($reading, $messageText),
            FortuneReading::STATUS_CELTIC_PENDING_PAYMENT => $this->handleCelticPendingPayment($reading, $messageText),
            FortuneReading::STATUS_CELTIC_PICKING => $this->handleCelticPicking($reading, $messageText),
            FortuneReading::STATUS_CELTIC_AWAITING_QUESTION => $this->handleCelticAwaitingQuestion($reading, $messageText),
            FortuneReading::STATUS_CELTIC_QA_PROMPT => $this->handleCelticQaPrompt($reading, $messageText),
            FortuneReading::STATUS_CELTIC_GENERATING => [
                'action' => 'celtic_processing',
                'message' => "🔮 หมอกำลังพิจารณาไพ่ทั้ง 10 ใบให้เจ้าชะตาอยู่...\n"
                    . "กรุณารอสักครู่ (~30-60 วินาที) ✨",
                'reading' => $reading,
            ],
            default => null, // ไม่ใช่ Celtic state → ให้ caller จัดการต่อ
        };
    }

    /**
     * Present tier choice menu — ส่งให้ลูกค้าเลือกระหว่าง 39฿ deep หรือ 99฿ Celtic
     *
     * เรียกจาก handleAfterBasic เมื่อ:
     *   1. ลูกค้ายอมรับว่าอยากดูเชิงลึก (isDeepReadingAccepted=true)
     *   2. enable_celtic_cross = true (admin เปิดบริการ Celtic ไว้)
     */
    protected function presentTierChoice(FortuneReading $reading): array
    {
        $reading->update(['conversation_status' => FortuneReading::STATUS_TIER_CHOICE]);

        $deepPrice = number_format($this->getDeepReadingPrice(), 0);
        $celticPrice = number_format(app(\App\Services\CelticCrossService::class)->getPrice(), 0);

        $message = "✨ *เจ้าชะตาเลือกได้ 2 แบบค่ะ*\n\n"
            . "──────────────────────\n"
            . "🔹 *ดูดวงเชิงลึก {$deepPrice} บาท*\n"
            . "  📅 ใช้วันเกิด + คำถาม 2 ข้อ\n"
            . "  🃏 สุ่มไพ่ยิปซี 1 ใบ ต่อคำถาม\n"
            . "  📜 คำทำนายเชิงลึก ตามดวงดาว + ไพ่\n\n"
            . "🔹 *ดูดวงไพ่ยิปซีเต็มสำรับ {$celticPrice} บาท* 🔮\n"
            . "  🃏 เปิดไพ่ Celtic Cross 10 ใบ — ครบทุกตำแหน่ง\n"
            . "  💬 ถามได้ 3 คำถาม (ภายใน 1 ชม. หลัง Q1)\n"
            . "  🖼️ ได้ภาพ Celtic Cross spread สวยๆ\n"
            . "  📜 คำทำนายแม่นกว่า + ลึกกว่า\n"
            . "──────────────────────\n\n"
            . "👉 พิมพ์ \"39\" เพื่อดูแบบเชิงลึก\n"
            . "👉 พิมพ์ \"99\" หรือ \"celtic\" เพื่อดูเต็มสำรับ\n"
            . "หรือกดปุ่มด้านล่างเลยค่ะ ✨";

        return [
            'action' => 'tier_choice',
            'message' => $message,
            'reading' => $reading,
            'deep_price' => $deepPrice,
            'celtic_price' => $celticPrice,
        ];
    }

    /**
     * State: STATUS_TIER_CHOICE — ลูกค้าเลือกแพคเกจ
     */
    protected function handleTierChoice(FortuneReading $reading, string $messageText): array
    {
        // ยกเลิก
        if ($this->matchesExactKeyword($messageText, ['ยกเลิก', 'cancel', 'stop'])) {
            $reading->update(['conversation_status' => FortuneReading::STATUS_COMPLETED]);

            return [
                'action' => 'cancelled',
                'message' => "ยกเลิกแล้วค่ะ — หากเปลี่ยนใจอยากดูดวงใหม่ พิมพ์ 'ดูดวง' ได้เลย 🔮",
                'reading' => $reading,
            ];
        }

        $textLower = mb_strtolower(trim($messageText));

        // 🔮 99฿ Celtic — keyword: "99", "celtic", "เต็ม", "เต็มสำรับ", "ไพ่ยิปซีเต็ม"
        $celticKeywords = ['99', 'celtic', 'เต็ม', 'เต็มสำรับ', 'ไพ่ยิปซีเต็ม', 'ทาโรต์เต็ม'];
        foreach ($celticKeywords as $kw) {
            if (mb_strpos($textLower, mb_strtolower($kw)) !== false) {
                return $this->startCelticCrossFlow($reading);
            }
        }

        // 🔹 39฿ Deep — keyword: "39", "ปกติ", "deep", "เชิงลึก"
        $deepKeywords = ['39', 'ปกติ', 'deep', 'เชิงลึก', 'ละเอียด'];
        foreach ($deepKeywords as $kw) {
            if (mb_strpos($textLower, mb_strtolower($kw)) !== false) {
                // เริ่ม flow 39฿ — ใช้โครงสร้างเดียวกับ handleAfterBasic เดิม
                $updateData = [
                    'reading_type' => FortuneReading::READING_TYPE_DEEP,
                    'conversation_status' => FortuneReading::STATUS_COLLECTING_BIRTHDATE,
                ];
                if (empty($reading->bill_reference)) {
                    $updateData['bill_reference'] = FortuneReading::generateBillReference();
                }
                $reading->update($updateData);

                return [
                    'action' => 'collecting_birthdate',
                    'message' => $this->getBirthdateRequestMessage(),
                    'reading' => $reading,
                ];
            }
        }

        // ไม่ตรงกับ keyword ใดๆ → re-show menu
        return [
            'action' => 'tier_choice_invalid',
            'message' => "✨ ขอให้เจ้าชะตาเลือกแพคเกจอีกครั้งนะคะ\n\n"
                . "👉 พิมพ์ \"39\" สำหรับดูเชิงลึก 39 บาท\n"
                . "👉 พิมพ์ \"99\" สำหรับดูเต็มสำรับ Celtic Cross 99 บาท\n"
                . "👉 พิมพ์ \"ยกเลิก\" หากไม่ต้องการ",
            'reading' => $reading,
        ];
    }

    /**
     * เริ่ม Celtic Cross flow — เรียกจาก handleAfterBasic หรือ keyword detection
     *
     * Flow ปลอดภัย (เหมือน 39฿ deep flow):
     * 1. DB::transaction wrap ทั้งหมด
     * 2. UniquePaymentAmount::generate(99, ...) → ได้ราคามีทศนิยม เช่น 99.07
     * 3. setCelticPendingPayment(UPA) → reading.unique_payment_amount_id + amount_paid + status
     * 4. Post-commit verify — ถ้า inconsistency → cleanup UPA + แจ้งลูกค้าให้ลองใหม่ (ห้ามส่ง QR)
     */
    protected function startCelticCrossFlow(FortuneReading $reading): array
    {
        // เช็ค toggle
        if (! $this->settings->enable_celtic_cross) {
            $reading->update(['conversation_status' => FortuneReading::STATUS_COMPLETED]);

            return [
                'action' => 'celtic_disabled',
                'message' => "🔮 ขออภัยค่ะ บริการดูดวงไพ่ยิปซีเต็มสำรับ Celtic Cross ปิดการใช้งานชั่วคราว\n\n"
                    . "ขณะนี้สามารถดูดวงพื้นฐานฟรีได้ตามปกติ พิมพ์คำถามมาได้เลย 🙏",
                'reading' => $reading,
            ];
        }

        $service = app(CelticCrossService::class);
        $basePrice = $service->getPrice(); // float (เช่น 99.00)

        try {
            // ⚠️ CRITICAL — ห้ามส่ง QR ออกจนกว่าจะ verify ว่า UPA + reading consistency
            $billData = DB::transaction(function () use ($reading, $basePrice) {
                $uniqueAmount = UniquePaymentAmount::generate(
                    $basePrice,
                    $reading->id,
                    'fortune_reading',
                    30 // หมดอายุใน 30 นาที (เหมือน 39฿)
                );

                if (! $uniqueAmount) {
                    throw new \RuntimeException('Celtic UPA generate ล้มเหลว');
                }

                $reading->setCelticPendingPayment($uniqueAmount);

                return ['upa' => $uniqueAmount, 'reading' => $reading];
            });

            $uniqueAmount = $billData['upa'];

            // 🔒 Post-commit verification
            $verified = FortuneReading::where('id', $reading->id)
                ->where('unique_payment_amount_id', $uniqueAmount->id)
                ->where('conversation_status', FortuneReading::STATUS_CELTIC_PENDING_PAYMENT)
                ->whereNotNull('bill_reference')
                ->first();

            if (! $verified) {
                Log::critical('Celtic: createPaymentBill verification fail — ห้ามส่ง QR', [
                    'reading_id' => $reading->id,
                    'upa_id' => $uniqueAmount->id,
                ]);

                // เคลียร์ UPA ที่ orphan
                try {
                    $uniqueAmount->refresh();
                    if ($uniqueAmount->status === 'reserved') {
                        $uniqueAmount->cancel();
                    }
                } catch (\Throwable $e) {
                    // ignore
                }

                return [
                    'action' => 'celtic_bill_creation_failed',
                    'message' => "🙏 ขออภัยค่ะ — ระบบเตรียมบิลไม่สำเร็จ\n\n"
                        . "กรุณาพิมพ์ 'celtic cross' อีกครั้งในอีก 10 วินาที เพื่อให้ระบบสร้างบิลใหม่ค่ะ\n\n"
                        . '⚠️ *อย่าโอนเงิน*จนกว่าจะได้รับบิลใหม่',
                    'reading' => $reading,
                ];
            }

            $reading = $verified;
            $payAmount = number_format((float) $uniqueAmount->unique_amount, 2);
            $baseAmountStr = number_format($basePrice, 0);

            // 🎯 สร้าง PromptPay QR (dynamic ยอดเงิน) — fallback เป็น static QR ถ้าสร้างไม่ได้
            // method นี้อยู่ใน FortuneConversationService (parent class) — เรียกได้เพราะ trait อยู่ใน class
            $qrImageUrl = null;
            try {
                if (method_exists($this, 'generatePromptPayQrImage')) {
                    $qrImageUrl = $this->generatePromptPayQrImage((float) $uniqueAmount->unique_amount, $reading->id);
                }
                if (! $qrImageUrl && method_exists($this, 'getPaymentQrImageUrl')) {
                    $qrImageUrl = $this->getPaymentQrImageUrl();
                }
            } catch (\Throwable $qrErr) {
                Log::warning('Celtic: QR gen fail (ส่ง text-only แทน)', ['error' => $qrErr->getMessage()]);
            }

            return [
                'action' => 'celtic_pending_payment',
                'message' => "🔮 *ดูดวงไพ่ยิปซีเต็มสำรับ Celtic Cross*\n\n"
                    . "✨ ค่าครู: {$baseAmountStr} บาท\n"
                    . "🃏 เปิดไพ่ 10 ใบ ตำแหน่งครบสายพันปี\n"
                    . "💬 ถามได้ 3 คำถาม (ภายใน 1 ชั่วโมงหลังคำถามแรก)\n"
                    . "🖼️ ได้รับภาพ Celtic Cross spread สวยๆ ส่งให้ดูครบทุกใบ\n\n"
                    . "──────────────────────\n"
                    . "💸 *ค่าครูสำหรับบิลนี้: {$payAmount} บาท*\n"
                    . "(ต้องโอนทศนิยมตรงเป๊ะ ระบบใช้ทศนิยมจับคู่บิลเจ้าชะตา)\n\n"
                    . "👉 โอนตามจำนวนนี้ผ่าน QR ที่ส่งให้ — บิลหมดอายุใน 30 นาที\n"
                    . "หลังโอนเสร็จ หมอจะให้เจ้าชะตาเปิดไพ่ทันที",
                'reading' => $reading,
                'celtic_price' => $payAmount,
                'celtic_base_price' => $basePrice,
                'celtic_bill_reference' => $reading->bill_reference,
                'unique_payment_amount' => $uniqueAmount,
                'payment_qr_url' => $qrImageUrl, // ✅ FortuneChannelManager จะส่งภาพ QR ออก
                'show_qr' => true,
            ];
        } catch (\Throwable $e) {
            Log::error('Celtic: startCelticCrossFlow error', [
                'reading_id' => $reading->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'action' => 'celtic_bill_creation_failed',
                'message' => "🙏 ขออภัยค่ะ ระบบขัดข้องชั่วคราว\nกรุณาลองใหม่อีกครั้งใน 10 วินาที",
                'reading' => $reading,
            ];
        }
    }

    /**
     * State: CELTIC_PENDING_PAYMENT
     * รอลูกค้าจ่ายเงิน — ตอบสอบถามได้ แต่ห้ามไปต่อจนกว่าจะจ่าย
     */
    protected function handleCelticPendingPayment(FortuneReading $reading, string $messageText): array
    {
        // 🔓 ยกเลิก
        if ($this->matchesExactKeyword($messageText, ['ยกเลิก', 'cancel', 'stop', 'ไม่จ่าย'])) {
            $reading->update(['conversation_status' => FortuneReading::STATUS_COMPLETED]);

            return [
                'action' => 'celtic_cancelled',
                'message' => "ยกเลิก Celtic Cross แล้วค่ะ — ไม่เป็นไรนะคะ\n\n"
                    . "หากต้องการดูใหม่ พิมพ์ 'celtic cross' หรือ 'ไพ่ยิปซีเต็ม' ได้เลย 🔮",
                'reading' => $reading,
            ];
        }

        // เช็คว่าจ่ายแล้วยัง — ถ้าจ่ายแล้ว transition ทันที (ป้องกันลูกค้าค้าง)
        $reading->refresh();
        if ($reading->is_paid) {
            return $this->onCelticPaymentConfirmed($reading);
        }

        // ยังไม่จ่าย — ตอบเตือนเรื่องจ่ายเงิน
        return [
            'action' => 'celtic_awaiting_payment',
            'message' => "💸 รอเจ้าชะตาโอนค่าครู 99 บาทตาม QR ที่ส่งให้นะคะ\n\n"
                . "📌 หลังโอนเสร็จ หมอจะรู้อัตโนมัติแล้วเปิดไพ่ให้\n"
                . "📌 พิมพ์ 'ยกเลิก' ถ้าไม่ต้องการต่อ",
            'reading' => $reading,
        ];
    }

    /**
     * เรียกหลังตรวจสอบว่าค่าครูเข้าระบบแล้ว — เริ่มเปิดไพ่ใบที่ 1
     * เรียกจาก: handleCelticPendingPayment (เมื่อ refresh เจอ is_paid=true)
     *           หรือจาก SMS payment confirmation hook ภายนอก
     */
    public function onCelticPaymentConfirmed(FortuneReading $reading): array
    {
        $reading->update(['conversation_status' => FortuneReading::STATUS_CELTIC_PICKING]);

        return $this->promptNextCelticCard($reading);
    }

    /**
     * State: CELTIC_PICKING
     * ลูกค้าตอบ "พร้อม" → สุ่มไพ่ + ส่งรูป → ขยับ position ถัดไป
     */
    protected function handleCelticPicking(FortuneReading $reading, string $messageText): array
    {
        // 🔓 ยกเลิก / เริ่มใหม่ (anti-fraud: ก่อน Q1 ตอบ → restart ฟรี)
        if ($this->matchesExactKeyword($messageText, ['เริ่มใหม่', 'restart', 'reset', 'สับใหม่'])) {
            try {
                app(CelticCrossService::class)->resetPickedCards($reading);

                return [
                    'action' => 'celtic_reset',
                    'message' => "🔄 เริ่มใหม่ครบ — ไพ่ที่เคยเลือกถูกล้างแล้ว\n"
                        . "ตอนนี้ตั้งจิตให้แน่วแน่อีกครั้ง แล้วเลือกใหม่นะคะ\n\n"
                        . $this->buildCelticPickPromptText($reading->fresh()),
                    'reading' => $reading,
                ];
            } catch (\Exception $e) {
                return [
                    'action' => 'celtic_reset_denied',
                    'message' => "❌ ไม่สามารถเริ่มใหม่ได้ — เจ้าชะตาได้รับคำทำนายไปแล้ว\n"
                        . 'ต้องเริ่มรอบใหม่ (จ่ายค่าครูใหม่) เท่านั้นค่ะ',
                    'reading' => $reading,
                ];
            }
        }

        // chitchat → ย้ำขั้นตอน
        if ($this->looksLikeMetaOrChitchat($messageText)) {
            return [
                'action' => 'celtic_chitchat_reminder',
                'message' => "🃏 ตอนนี้อยู่ขั้นเปิดไพ่นะคะ\n\n"
                    . $this->buildCelticPickPromptText($reading)
                    . "\n\nเจ้าชะตาแค่พิมพ์ 'พร้อม' เพื่อให้หมอสุ่มไพ่ใบถัดไปค่ะ",
                'reading' => $reading,
            ];
        }

        // ไม่ใช่ chitchat — ถือว่า "พร้อม" เปิดไพ่
        $service = app(CelticCrossService::class);
        $result = $service->pickNextCard($reading);

        if (! $result['success']) {
            return [
                'action' => 'celtic_pick_failed',
                'message' => '⚠️ ' . ($result['message'] ?? 'สุ่มไพ่ไม่สำเร็จ ลองอีกครั้ง'),
                'reading' => $reading,
            ];
        }

        $position = $result['position'];
        $positionName = $result['position_name'];
        $cardNameTh = $result['card_name_th'];
        $cardNameEn = $result['card_name_en'];
        $reversed = $result['is_reversed'] ? '(กลับหัว)' : '(ตั้งตรง)';
        $meaning = mb_substr($result['meaning'], 0, 200);
        $imageUrl = $result['image_url'];
        $count = $result['picked_count'];

        $message = "🃏✨ *ใบที่ {$count}/10 — ตำแหน่ง [{$positionName}]*\n\n"
            . "ได้ไพ่ *{$cardNameTh}* {$reversed}\n"
            . "({$cardNameEn})\n\n"
            . "📖 ความหมายไพ่นี้: {$meaning}";

        // ครบ 10 ใบหรือยัง
        if ($result['is_complete']) {
            return $this->onCelticAllCardsPicked($reading, $message, $imageUrl);
        }

        // ขยับไป position ถัดไป
        $reading->refresh();
        $nextPrompt = $this->buildCelticPickPromptText($reading);
        $message .= "\n\n──────────────────────\n" . $nextPrompt;

        return [
            'action' => 'celtic_card_picked',
            'message' => $message,
            'reading' => $reading,
            'tarot_image_url' => $imageUrl,
            'celtic_picked_count' => $count,
            'celtic_total' => 10,
        ];
    }

    /**
     * สร้างข้อความเชิญตั้งจิต + เปิดไพ่ใบถัดไป
     */
    protected function buildCelticPickPromptText(FortuneReading $reading): string
    {
        $next = $reading->getNextCelticPosition();
        if ($next === null) {
            return '✨ เลือกครบ 10 ใบแล้วค่ะ';
        }

        $meta = FortuneReading::CELTIC_POSITIONS[$next] ?? null;
        $name = $meta['name'] ?? '?';
        $desc = $meta['description'] ?? '';

        return "🃏 *ใบที่ {$next}/10 — ตำแหน่ง [{$name}]*\n"
            . "💭 ตำแหน่งนี้บอกถึง: {$desc}\n\n"
            . "🧘 ตั้งจิต หลับตา 3 วินาที นึกถึงสิ่งที่อยากรู้\n"
            . "เมื่อพร้อมแล้ว พิมพ์ 'พร้อม' เพื่อให้หมอเปิดไพ่ใบนี้ค่ะ";
    }

    /**
     * เลือกครบ 10 ใบ → สร้างภาพ composite → เข้าโหมดถาม Q1
     */
    protected function promptNextCelticCard(FortuneReading $reading): array
    {
        return [
            'action' => 'celtic_pick_prompt',
            'message' => "✅ ค่าครูเข้าระบบแล้ว ขอบคุณค่ะ\n\n"
                . "🔮 *ดูดวง Celtic Cross เริ่มเลย*\n"
                . 'หมอจะเปิดไพ่ให้ทีละใบ พร้อมตำแหน่งที่ได้\n\n'
                . "──────────────────────\n"
                . $this->buildCelticPickPromptText($reading),
            'reading' => $reading,
        ];
    }

    /**
     * State Transition: เลือกครบ 10 ใบ → สร้างภาพ composite → ถาม Q1
     */
    protected function onCelticAllCardsPicked(FortuneReading $reading, string $lastCardMessage, ?string $lastCardImage = null): array
    {
        // สร้างภาพ composite
        $composeUrl = null;
        try {
            $generator = app(CelticSpreadImageGenerator::class);
            $composeUrl = $generator->generate($reading);
        } catch (\Exception $e) {
            Log::warning('Celtic: composite image fail', [
                'reading_id' => $reading->id,
                'error' => $e->getMessage(),
            ]);
        }

        // ขยับ state เข้า awaiting question
        $reading->update(['conversation_status' => FortuneReading::STATUS_CELTIC_AWAITING_QUESTION]);

        $followupText = "\n\n──────────────────────\n"
            . "🌟 *เปิดไพ่ครบ 10 ใบแล้ว!*\n"
            . "เจ้าชะตาดูภาพ Celtic Cross ที่จัดเรียงให้แล้วนะคะ ✨\n\n"
            . "💬 ตอนนี้เจ้าชะตาต้องการ **ถามอะไรเป็นคำถามที่ 1/3** คะ?\n"
            . "พิมพ์คำถามมาได้เลย หมอจะวิเคราะห์ไพ่ทั้ง 10 ใบให้ตอบค่ะ";

        return [
            'action' => 'celtic_all_picked',
            'message' => $lastCardMessage . $followupText,
            'reading' => $reading,
            'tarot_image_url' => $lastCardImage,
            'celtic_summary_image_url' => $composeUrl, // ส่งภาพ composite ก่อนข้อความถามคำถาม
        ];
    }

    /**
     * State: CELTIC_AWAITING_QUESTION
     * ลูกค้าพิมพ์คำถาม Q1, Q2, หรือ Q3
     */
    protected function handleCelticAwaitingQuestion(FortuneReading $reading, string $messageText): array
    {
        // ❌ คำถามสั้นเกินไป — ขอใหม่
        $question = trim($messageText);
        if (mb_strlen($question) < 5) {
            return [
                'action' => 'celtic_question_too_short',
                'message' => "✍️ คำถามสั้นเกินไป\n"
                    . 'กรุณาพิมพ์คำถามที่ต้องการให้หมอทำนาย เช่น "ปีนี้ความรักจะเป็นอย่างไร" หรือ "ควรลาออกไหม"',
                'reading' => $reading,
            ];
        }

        // เช็ค anti-fraud: window 1 ชม. (ถ้า Q1 ตอบไปแล้วและเลยเวลา → ปฏิเสธ)
        if (! $reading->canAskMoreCeltic()) {
            $reading->update(['conversation_status' => FortuneReading::STATUS_COMPLETED]);

            return [
                'action' => 'celtic_qa_window_expired',
                'message' => "⏰ ครบเวลา 1 ชั่วโมงในการถามต่อแล้วค่ะ\n\n"
                    . "หากเจ้าชะตาต้องการดูใหม่ ต้องเริ่มรอบใหม่ (ค่าครู 99 บาท)\n"
                    . "พิมพ์ 'celtic cross' เพื่อเริ่มใหม่ค่ะ 🔮",
                'reading' => $reading,
            ];
        }

        // ส่งให้ AI Pool
        $reading->update(['conversation_status' => FortuneReading::STATUS_CELTIC_GENERATING]);

        $service = app(CelticCrossService::class);
        $result = $service->askQuestion($reading, $question);

        if (! $result['success']) {
            // กลับเข้า awaiting state ให้ลองใหม่
            $reading->update(['conversation_status' => FortuneReading::STATUS_CELTIC_AWAITING_QUESTION]);

            return [
                'action' => 'celtic_ai_failed',
                'message' => '⚠️ ' . ($result['message'] ?? 'AI ระบบขัดข้องชั่วคราว ลองอีกครั้งค่ะ'),
                'reading' => $reading,
            ];
        }

        // สำเร็จ → ขยับเข้า QA prompt state
        $reading->refresh();
        $sequence = $result['sequence'];
        $remaining = $result['questions_remaining'];
        $used = $result['questions_used'];

        $reading->update(['conversation_status' => FortuneReading::STATUS_CELTIC_QA_PROMPT]);

        $followupOffer = '';
        if ($remaining > 0) {
            $maxQ = $service->getMaxQuestions();
            $followupOffer = "\n\n──────────────────────\n"
                . "💬 *เจ้าชะตาอยากถามต่อไหมคะ? (ใช้ไป {$used}/{$maxQ})*\n"
                . "ถามได้อีก {$remaining} คำถาม ภายในเวลา 1 ชั่วโมงนับจาก Q1\n\n"
                . "👉 พิมพ์คำถามใหม่ — หรือ พิมพ์ 'พอแค่นี้' เพื่อจบ";
        } else {
            $reading->update(['conversation_status' => FortuneReading::STATUS_COMPLETED]);
            $followupOffer = "\n\n──────────────────────\n"
                . "🌟 ครบ 3/3 คำถามแล้วค่ะ\n"
                . 'ขอให้เจ้าชะตาโชคดี เจอแต่สิ่งดีๆ นะคะ 🙏✨';
        }

        return [
            'action' => 'celtic_question_answered',
            'message' => $result['response'] . $followupOffer,
            'reading' => $reading,
            'celtic_sequence' => $sequence,
            'celtic_questions_remaining' => $remaining,
        ];
    }

    /**
     * State: CELTIC_QA_PROMPT
     * รอลูกค้าตอบว่าจะถามต่อหรือพอแค่นี้
     */
    protected function handleCelticQaPrompt(FortuneReading $reading, string $messageText): array
    {
        // "พอแค่นี้" / "จบ"
        if ($this->matchesExactKeyword($messageText, ['พอแค่นี้', 'พอแล้ว', 'จบ', 'ขอบคุณ', 'thanks', 'พอ'])) {
            $reading->update(['conversation_status' => FortuneReading::STATUS_COMPLETED]);

            return [
                'action' => 'celtic_completed',
                'message' => "🌟 ขอบคุณที่ใช้บริการดูดวง Celtic Cross นะคะ\n"
                    . "ขอให้เจ้าชะตาเจอแต่สิ่งดีๆ คำทำนายเป็นแสงไฟชี้ทาง — แต่ตัดสินใจอยู่ที่เจ้าชะตาเอง\n\n"
                    . "💜 หากต้องการดูใหม่ พิมพ์ 'ดูดวง' หรือ 'celtic cross' ได้เลย 🙏",
                'reading' => $reading,
            ];
        }

        // อย่างอื่น = ถือเป็นคำถามใหม่
        $reading->update(['conversation_status' => FortuneReading::STATUS_CELTIC_AWAITING_QUESTION]);

        return $this->handleCelticAwaitingQuestion($reading, $messageText);
    }

    /**
     * Detection: ลูกค้าต้องการ Celtic Cross
     */
    protected function matchesCelticCrossKeyword(string $text): bool
    {
        $keywords = [
            'celtic cross', 'celtic', 'เซลติก', 'ไพ่ยิปซีเต็ม', 'ไพ่ยิปซีเต็มสำรับ',
            'ดูเต็ม', 'ดูชุดใหญ่', 'ทาโรต์เต็ม', 'tarot full',
            '99 บาท', '99บาท',
        ];

        $lower = mb_strtolower(trim($text));
        foreach ($keywords as $kw) {
            if (mb_strpos($lower, mb_strtolower($kw)) !== false) {
                return true;
            }
        }

        return false;
    }
}
