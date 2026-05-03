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
            // 🆕 (2026-04-29) Tier choice — ลูกค้าเลือก 39฿ Basic Deep หรือ 99฿ Celtic Cross
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
     * Present tier choice menu — ส่งให้ลูกค้าเลือกระหว่าง 39฿ Basic Deep หรือ 99฿ Celtic Cross
     *
     * 🎯 จุดประสงค์: เป็นประตูทางเข้า "ดูดวง" ทั้งหมดในระบบ
     *   - ลูกค้าพิมพ์ "ดูดวง" / "ดูดวงละเอียด" / "ทำนาย" → มาที่นี่ทันที
     *   - ไม่มีการดูดวงฟรีเป็น dummy ก่อนแล้วค่อยถาม (ทำให้สับสน)
     *   - ลูกค้าต้องเลือกแพคเกจอย่างใดอย่างหนึ่งเสมอ
     *
     * 🆕 (2026-04-29) แพคเกจปัจจุบัน:
     *   - 39 บาท = ดูวันเดือนปีเกิด + ไพ่ยิปซี 1 ใบ (เข้าถึงง่าย ราคาเป็นมิตร)
     *   - 99 บาท = ไพ่ยิปซี Celtic Cross เต็มสำรับ 10 ใบ (พรีเมียม แม่นยำลึกซึ้ง)
     *
     * เรียกจาก:
     *   1. startDeepReadingFlow (เมื่อ Celtic เปิด)
     *   2. handleAfterBasic (เมื่อลูกค้ารับ deep + Celtic เปิด)
     *   3. askFortuneConfirmation (เมื่อ Celtic + Deep เปิดทั้งคู่ — fast path)
     */
    protected function presentTierChoice(FortuneReading $reading): array
    {
        // 🎁 (2026-05-03) ตรวจสอบว่าควรเสนอ "ทำนายฟรี 1 ใบ" หรือไม่ (first-timer + feature เปิด)
        $offerFree = FortuneReading::shouldOfferFreeCard(
            $this->currentPlatform,
            $reading->platform_user_id ?? $reading->facebook_user_id ?? ''
        );

        // 🔒 (2026-05-03) ถ้า admin ปิด Celtic — ปกติข้าม tier menu ไปดูดวง 39฿ ตรงๆ
        //    แต่ถ้า offerFree = true → ต้องโชว์ menu (มีปุ่ม [ฟรี] [39]) ก่อน
        if (! $this->settings->enable_celtic_cross && ! $offerFree) {
            $deepPriceInt = (int) $this->getDeepReadingPrice();
            $reading->update([
                'reading_type' => FortuneReading::READING_TYPE_DEEP,
                'conversation_status' => FortuneReading::STATUS_COLLECTING_BIRTHDATE,
            ]);
            if (empty($reading->bill_reference)) {
                $reading->update(['bill_reference' => FortuneReading::generateBillReference()]);
            }

            return [
                'action' => 'collecting_birthdate',
                'message' => "✨ เริ่มดูดวง *{$deepPriceInt} บาท* 🔹\n\n"
                    . $this->getBirthdateRequestMessage(),
                'reading' => $reading,
            ];
        }

        $reading->update(['conversation_status' => FortuneReading::STATUS_TIER_CHOICE]);

        $deepPrice = number_format($this->getDeepReadingPrice(), 0);
        $celticPrice = number_format(app(\App\Services\CelticCrossService::class)->getPrice(), 0);
        // 🔢 (2026-05-03) อ่านจาก settings — ตรงกับที่ admin ตั้ง (0 = ไม่จำกัด)
        $maxQRaw = (int) ($this->settings->celtic_cross_max_questions ?? 5);
        $qaWindow = (int) ($this->settings->celtic_cross_qa_window_minutes ?? 30);
        $qLimitText = $maxQRaw <= 0 ? 'ไม่จำกัด' : "{$maxQRaw} คำถาม";
        $celticEnabled = (bool) ($this->settings->enable_celtic_cross ?? false);

        // 🎁 หัวเมนู — เปลี่ยนตามว่ามีปุ่มฟรีหรือไม่
        $packageCount = 1 + ($celticEnabled ? 1 : 0) + ($offerFree ? 1 : 0);
        $message = "🌙✨ *หมอจันทรายินดีต้อนรับเจ้าชะตาค่ะ* ✨🌙\n\n"
            . "วันนี้เจ้าชะตาอยากให้หมอเปิดทางดวงให้แบบไหนคะ?\n"
            . "เลือกได้ *1 จาก {$packageCount} แพคเกจ* ด้านล่างเลย 👇\n\n";

        // 🎁 (2026-05-03) แพคเกจ "ทำนายฟรี" — เฉพาะ first-timer + feature เปิด
        if ($offerFree) {
            $message .= "━━━━━━━━━━━━━━━━━\n"
                . "🎁 *ทำนายฟรี (1 ใบ) — สิทธิ์พิเศษครั้งแรก* 🌙\n"
                . "━━━━━━━━━━━━━━━━━\n"
                . "🃏 *เปิดไพ่ยิปซี 1 ใบ ที่จิตเจ้าชะตาเลือกเอง*\n"
                . "    แม่หมอใช้จิตสัมผัสดวงสมพงษ์ — ทำนายสถานการณ์ปัจจุบัน + ชี้ทางออก\n\n"
                . "✨ *เหมาะกับ:* คนใหม่ที่อยากลองสัมผัสพลังหมอจันทรา\n"
                . "⏱️ *เวลา:* ทำนายเสร็จใน 1 นาที\n"
                . "💎 *เงื่อนไข:* ใช้ได้ครั้งเดียวเท่านั้น/ท่าน\n\n";
        }

        // 📌 หมายเลขแพคเกจ — dynamic ตามว่ามีฟรีไหม
        $freeNum = $offerFree ? '🎁 ฟรี' : null;
        $deepNum = $offerFree ? 'ที่ 2' : 'ที่ 1';
        $celticNum = $offerFree ? 'ที่ 3' : 'ที่ 2';

        // ━━━━━━━━━━━━━━━━━━━━━━━━
        // 🔹 39 บาท — Basic Deep
        // ━━━━━━━━━━━━━━━━━━━━━━━━
        $message .= "━━━━━━━━━━━━━━━━━\n"
            . "🔹 *แพคเกจ{$deepNum} — ดูดวงพื้นฐาน {$deepPrice} บาท* 💫\n"
            . "━━━━━━━━━━━━━━━━━\n"
            . "📅 *วิเคราะห์จากวันเดือนปีเกิด*\n"
            . "    หมอจะคำนวณดาวเจ้าชนะ + ราศี + ลัคนาให้\n\n"
            . "🃏 *ไพ่ยิปซี 1 ใบ ที่จิตเจ้าชะตาเลือกเอง*\n"
            . "    ไพ่ใบเดียว — ตรงประเด็น แม่นยำ ไม่ยกเมฆ\n\n"
            . "💎 *เหมาะกับ:* คนอยากรู้ดวงรวมๆ — เริ่มต้นง่าย ราคาเป็นมิตร\n"
            . "⏱️ *เวลา:* ทำนายเสร็จใน 1-3 นาที\n\n";

        // ━━━━━━━━━━━━━━━━━━━━━━━━
        // 🔮 99 บาท — Celtic Cross (Premium) — เฉพาะถ้า admin เปิด
        // ━━━━━━━━━━━━━━━━━━━━━━━━
        if ($celticEnabled) {
            $message .= "━━━━━━━━━━━━━━━━━\n"
                . "🔮 *แพคเกจ{$celticNum} — ไพ่ยิปซีเต็มสำรับ {$celticPrice} บาท* 👑\n"
                . "━━━━━━━━━━━━━━━━━\n"
                . "🃏 *เปิดไพ่ Celtic Cross 10 ใบ — เต็มสำรับ*\n"
                . "    ตำแหน่งครบ: ปัจจุบัน • อุปสรรค • ความหวัง • อนาคต\n\n"
                . "🌌 *แม่หมอใช้พลังจักรวาล + จิตเจ้าชะตา*\n"
                . "    ไม่ต้องบอกวันเกิด — แม่หมอล้วงลึกผ่านไพ่ที่เจ้าชะตาเลือกเอง\n\n"
                . "💬 *ถามได้ {$qLimitText}* (ภายใน {$qaWindow} นาทีหลังคำทำนายแรก)\n"
                . "    คุยกับแม่หมอจนพอใจ — แม่หมอจะตัดสินใจจบเองเมื่อครอบคลุม\n\n"
                . "🖼️ *ได้ภาพ Celtic Cross spread* สวยงาม\n"
                . "    เก็บไว้เป็นที่ระลึก แชร์ให้เพื่อนได้\n\n"
                . "📜 *คำทำนายแม่นกว่า ลึกกว่า เห็นภาพรวม*\n"
                . "    ใช้ศาสตร์ไพ่ยิปซีโบราณ + จิตวิทยาสุขุม เห็นเหมือนตาเห็น\n\n"
                . "👑 *เหมาะกับ:* คนที่จริงจัง อยากเห็นภาพชัดทุกแง่\n"
                . "⏱️ *เวลา:* เปิดไพ่ + ทำนายเสร็จใน 5-10 นาที\n\n";
        }

        // 👇 CTA — รวมตัวเลือกตามที่มี
        $message .= "━━━━━━━━━━━━━━━━━\n"
            . "👇 *เลือกแพคเกจของเจ้าชะตา* 👇\n"
            . "━━━━━━━━━━━━━━━━━\n";
        if ($offerFree) {
            $message .= "✦ พิมพ์ *\"ทำนายฟรี\"* — รับสิทธิ์ฟรี 1 ใบ\n";
        }
        $message .= "✦ พิมพ์ *\"{$deepPrice}\"* — เริ่มแพคเกจพื้นฐาน {$deepPrice} บาท\n";
        if ($celticEnabled) {
            $message .= "✦ พิมพ์ *\"{$celticPrice}\"* หรือ *\"celtic\"* — เริ่มแพคเกจเต็มสำรับ\n";
        }
        $message .= "✦ หรือ *กดปุ่มด้านล่าง* ได้เลยค่ะ ✨\n\n"
            . "🙏 ถ้ายังไม่พร้อม พิมพ์ *\"ยกเลิก\"* ได้นะคะ";

        return [
            'action' => 'tier_choice',
            'message' => $message,
            'reading' => $reading,
            'deep_price' => $deepPrice,
            'celtic_price' => $celticPrice,
            'offer_free' => $offerFree, // 🎁 (2026-05-03) flag ให้ ChannelManager เพิ่มปุ่ม "🎁 ทำนายฟรี"
        ];
    }

    /**
     * State: STATUS_TIER_CHOICE — ลูกค้าเลือกแพคเกจ
     *
     * 🎯 รับการเลือกของลูกค้า — ต้องเลือกอย่างใดอย่างหนึ่ง:
     *   - 39฿ Basic Deep (วันเกิด + ไพ่ 1 ใบ)
     *   - 99฿ Celtic Cross (ไพ่ยิปซีเต็มสำรับ 10 ใบ)
     *
     * รองรับ:
     *   - กดปุ่ม Quick Reply (FB payload "TIER_DEEP_39" / "TIER_CELTIC_99")
     *   - พิมพ์ตัวเลข "39" / "99"
     *   - พิมพ์คำหลัก "เชิงลึก" / "ละเอียด" / "celtic" / "เต็มสำรับ"
     *   - พิมพ์ "ยกเลิก" เพื่อออกจาก flow
     */
    protected function handleTierChoice(FortuneReading $reading, string $messageText): array
    {
        // ❌ ยกเลิก
        if ($this->matchesExactKeyword($messageText, ['ยกเลิก', 'cancel', 'stop'])) {
            $reading->update(['conversation_status' => FortuneReading::STATUS_COMPLETED]);

            return [
                'action' => 'cancelled',
                'message' => "🙏 ยกเลิกแล้วค่ะ\n\nหากเปลี่ยนใจอยากให้หมอจันทราดูดวงให้\nพิมพ์ *\"ดูดวง\"* ได้ตลอดเลยนะคะ 🔮✨",
                'reading' => $reading,
            ];
        }

        // 🎁 (2026-05-03) ทำนายฟรี — ถ้าลูกค้าพิมพ์/กด → ปิด tier reading + เริ่ม free flow
        if ($this->matchesFreeCardKeyword($messageText)) {
            $reading->update(['conversation_status' => FortuneReading::STATUS_COMPLETED]);

            return $this->startFreeCardFlow(
                $reading->platform_user_id ?? $reading->facebook_user_id,
                $reading->user_profile
            );
        }

        $textLower = mb_strtolower(trim($messageText));
        $deepPriceInt = (int) $this->getDeepReadingPrice();
        $celticPriceInt = (int) app(\App\Services\CelticCrossService::class)->getPrice();
        $maxQRaw = (int) ($this->settings->celtic_cross_max_questions ?? 5);
        $qLimitText = $maxQRaw <= 0 ? 'ไม่จำกัด' : "{$maxQRaw} คำถาม";

        // 🔮 99฿ Celtic Cross — keyword: "99", "celtic", "เต็ม", "เต็มสำรับ", "ไพ่ยิปซีเต็ม", "พรีเมียม"
        // ⚠️ เช็ค Celtic ก่อน Deep เผื่อข้อความมีทั้ง "99" และ "39" (ไม่ค่อยเกิด แต่กันไว้)
        $celticKeywords = [
            (string) $celticPriceInt,  // "99" (ดึงจาก service ไม่ hardcode)
            'celtic', 'เซลติก', 'เต็มสำรับ', 'เต็ม สำรับ', 'ไพ่ยิปซีเต็ม', 'ทาโรต์เต็ม',
            'พรีเมียม', 'premium', 'แพคเกจ 2', 'แพคเกจที่ 2', 'แบบที่ 2',
            'tier_celtic', 'tier_celtic_99',  // payload จาก FB button
        ];
        foreach ($celticKeywords as $kw) {
            if (mb_strpos($textLower, mb_strtolower($kw)) !== false) {
                return $this->startCelticCrossFlow($reading);
            }
        }

        // 🔹 39฿ Basic Deep — keyword: "39", "ปกติ", "พื้นฐาน", "deep", "เชิงลึก", "ละเอียด"
        // ⚠️ ใช้ราคาจาก getDeepReadingPrice() (admin override ได้) — ไม่ hardcode
        $deepKeywords = [
            (string) $deepPriceInt,  // "39" (หรือราคาที่ admin ตั้งไว้)
            'ปกติ', 'พื้นฐาน', 'พื้น ฐาน', 'basic',
            'deep', 'เชิงลึก', 'เชิง ลึก', 'ละเอียด', 'แบบเชิงลึก', 'แบบละเอียด',
            'แพคเกจ 1', 'แพคเกจที่ 1', 'แบบที่ 1', 'อันแรก',
            'tier_deep', 'tier_deep_39',  // payload จาก FB button
        ];
        foreach ($deepKeywords as $kw) {
            if (mb_strpos($textLower, mb_strtolower($kw)) !== false) {
                // เริ่ม flow Basic Deep — ใช้โครงสร้างเดียวกับ handleAfterBasic เดิม
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
                    'message' => "✨ เลือกแพคเกจ *ดูดวงพื้นฐาน {$deepPriceInt} บาท* แล้วค่ะ 🔹\n\n"
                        . $this->getBirthdateRequestMessage(),
                    'reading' => $reading,
                ];
            }
        }

        // ❓ ไม่ตรงกับ keyword ใดๆ → re-show menu แบบกระชับ (ไม่ส่งข้อความยาวซ้ำ)
        return [
            'action' => 'tier_choice_invalid',
            'message' => "🙏 ขอให้เจ้าชะตาเลือกแพคเกจอีกครั้งนะคะ\n\n"
                . "🔹 พิมพ์ *\"{$deepPriceInt}\"* — ดูดวงพื้นฐาน {$deepPriceInt} บาท\n"
                . "    📅 วันเดือนปีเกิด + 🃏 ไพ่ยิปซี 1 ใบ\n\n"
                . "🔮 พิมพ์ *\"{$celticPriceInt}\"* หรือ *\"celtic\"* — ไพ่ยิปซีเต็มสำรับ {$celticPriceInt} บาท\n"
                . "    🃏 Celtic Cross 10 ใบ + ถามได้ {$qLimitText}\n\n"
                . "❌ พิมพ์ *\"ยกเลิก\"* หากไม่ต้องการดูตอนนี้\n\n"
                . "👇 หรือกดปุ่มด้านล่างก็ได้นะคะ ✨",
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

            // 📲 (2026-05-03) FCM push ให้แอพ SMS Checker เห็นบิล Celtic ใหม่ทันที
            //    bug เดิม: Deep 39฿ มี FCM push แต่ Celtic 99฿ ขาด → SMS app ไม่รู้บิล →
            //    ต้องรอ polling หรือ admin manual review (ลูกค้าโอนแล้วบิลค้างยาว)
            //    notifyNewFortuneReading ใช้ field generic (bill_reference, amount_paid)
            //    → ใช้กับ Celtic ได้เลย ไม่ต้องเพิ่ม method แยก
            try {
                app(\App\Services\FcmNotificationService::class)->notifyNewFortuneReading($reading);
            } catch (\Throwable $fcmErr) {
                Log::warning('Celtic: FCM push new_fortune_reading ล้มเหลว (non-blocking)', [
                    'reading_id' => $reading->id,
                    'error' => $fcmErr->getMessage(),
                ]);
            }

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

            $maxQRaw = (int) ($this->settings->celtic_cross_max_questions ?? 5);
            $qaWindow = (int) ($this->settings->celtic_cross_qa_window_minutes ?? 30);
            $qLimitText = $maxQRaw <= 0 ? 'ไม่จำกัด' : "{$maxQRaw} คำถาม";

            return [
                'action' => 'celtic_pending_payment',
                'message' => "🔮 *ดูดวงไพ่ยิปซีเต็มสำรับ Celtic Cross*\n\n"
                    . "✨ ค่าครู: {$baseAmountStr} บาท\n"
                    . "🃏 เปิดไพ่ 10 ใบ ตำแหน่งครบสายพันปี\n"
                    . "💬 ถามได้ {$qLimitText} (ภายใน {$qaWindow} นาทีหลังคำถามแรก)\n"
                    . "🖼️ ได้รับภาพ Celtic Cross spread สวยๆ ส่งให้ตอนจบทำนาย เป็นที่ระลึก\n\n"
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

        $payAmount = $reading->amount_paid
            ? number_format((float) $reading->amount_paid, 2)
            : '99.00';

        // 🔄 ลูกค้าพิมพ์ "ดูดวง" / "เริ่มใหม่" — แจ้งสถานะรอจ่ายให้ชัด
        if ($this->looksLikeFortuneRestartRequest($messageText)) {
            return [
                'action' => 'celtic_pending_payment_hint',
                'message' => "🌙 เจ้าชะตาเลือกแพคเกจไพ่ยิปซีเต็มสำรับ 99 บาทไว้แล้วนะคะ\n\n"
                    . "💸 ตอนนี้รอเจ้าชะตา *โอนค่าครู {$payAmount} บาท* ตาม QR ที่ส่งให้\n"
                    . "(ต้องโอนทศนิยมตรงเป๊ะ ระบบใช้ทศนิยมจับคู่บิล)\n\n"
                    . "──────────────────────\n"
                    . "✅ หลังโอนเสร็จ — พิมพ์ *\"โอนแล้ว\"* ระบบจะเช็คให้\n"
                    . "❌ หรือพิมพ์ *\"ยกเลิก\"* เพื่อไม่ดูแล้ว",
                'reading' => $reading,
            ];
        }

        // ยังไม่จ่าย — ตอบเตือนเรื่องจ่ายเงิน
        return [
            'action' => 'celtic_awaiting_payment',
            'message' => "💸 รอเจ้าชะตาโอนค่าครู {$payAmount} บาทตาม QR ที่ส่งให้นะคะ\n\n"
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

        // 🔄 ลูกค้าพิมพ์ "ดูดวง" / "เริ่มใหม่" — ห้ามถือเป็น "พร้อม" สุ่มไพ่
        if ($this->looksLikeFortuneRestartRequest($messageText)) {
            $picked = $reading->getCelticPickedCount();
            $next = $reading->getNextCelticPosition() ?? 11;

            return [
                'action' => 'celtic_restart_hint',
                'message' => "🌙 เจ้าชะตาอยู่ในรอบดูดวงไพ่ยิปซีอยู่แล้วนะคะ\n\n"
                    . "🃏 ตอนนี้เปิดไพ่ไปแล้ว *{$picked}/10 ใบ* — ใบถัดไปคือใบที่ {$next}\n\n"
                    . "──────────────────────\n"
                    . "👉 พิมพ์ *\"พร้อม\"* เพื่อเปิดไพ่ใบถัดไป\n"
                    . "🔄 หรือพิมพ์ *\"สับใหม่\"* เพื่อสับไพ่เริ่มใหม่ (ยังไม่จ่ายซ้ำ)\n"
                    . "❌ พิมพ์ *\"ยกเลิก\"* ถ้าไม่อยากดูแล้ว",
                'reading' => $reading,
            ];
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
        // 🖼️ (2026-05-03) ไม่ส่งภาพ composite ตอนนี้ — เก็บไว้โชว์ตอนจบ session
        //    เดิม: ส่งภาพหลังเปิดครบ 10 ใบ → ลูกค้าเห็นก่อนถาม Q1
        //    ใหม่: รวบเป็น "ที่ระลึก" ตอน endCelticSession (ดูดีกว่า)

        // ขยับ state เข้า awaiting question
        $reading->update(['conversation_status' => FortuneReading::STATUS_CELTIC_AWAITING_QUESTION]);

        $maxQ = (int) ($this->settings->celtic_cross_max_questions ?? 5);
        $qaWindow = (int) ($this->settings->celtic_cross_qa_window_minutes ?? 30);
        $qLimitText = $maxQ <= 0 ? 'ไม่จำกัด' : "{$maxQ} คำถาม";
        $followupText = "\n\n──────────────────────\n"
            . "🌟 *เปิดไพ่ครบ 10 ใบแล้ว!*\n\n"
            . "🌌 ตอนนี้แม่หมอกำลังเชื่อมพลังจักรวาลกับไพ่ทั้ง 10 ใบของเจ้าชะตา\n"
            . "💬 พิมพ์คำถามแรกที่อยากรู้มาได้เลย — แม่หมอจะอ่านพลังงานให้\n\n"
            . "❓ ถามได้ *{$qLimitText}* (ภายใน {$qaWindow} นาที)\n"
            . "🖼️ ภาพไพ่จัดเรียงสวยๆ — แม่หมอจะส่งให้ตอนจบทำนาย เป็นที่ระลึก ✨\n"
            . "🔚 พิมพ์ *\"พอแค่นี้\"* เมื่อพอใจ";

        return [
            'action' => 'celtic_all_picked',
            'message' => $lastCardMessage . $followupText,
            'reading' => $reading,
            'tarot_image_url' => $lastCardImage,
            // celtic_summary_image_url removed — เลื่อนไป endCelticSession
        ];
    }

    /**
     * State: CELTIC_AWAITING_QUESTION
     * ลูกค้าพิมพ์คำถาม Q1, Q2, หรือ Q3
     */
    protected function handleCelticAwaitingQuestion(FortuneReading $reading, string $messageText): array
    {
        $question = trim($messageText);

        // 🔚 ลูกค้าขอจบ → จบสุขุม
        if ($this->matchesExactKeyword($messageText, ['พอแค่นี้', 'พอแล้ว', 'จบ', 'ขอบคุณ', 'thanks', 'พอ', 'หยุด', 'stop'])) {
            return $this->endCelticSession($reading, 'customer_said_done');
        }

        // 🔄 ลูกค้าพิมพ์ "ดูดวง" / "เริ่มใหม่" lone — ไม่ส่งให้ AI ตีความเป็นคำถาม
        // ตอบ contextual ว่าเจ้าชะตาอยู่ในรอบดูดวงอยู่แล้ว ให้พิมพ์คำถามจริง
        if ($this->looksLikeFortuneRestartRequest($messageText)) {
            $remainingMin = $reading->getCelticQaRemainingMinutes();
            $timeHint = $remainingMin !== null && $remainingMin > 0
                ? "⏳ คุยกับแม่หมอได้อีก {$remainingMin} นาที"
                : '⏳ คุยกับแม่หมอได้ภายใน 30 นาทีนับจากคำทำนายแรก';

            return [
                'action' => 'celtic_already_in_session',
                'message' => "🌙 เจ้าชะตาอยู่ในรอบดูดวงไพ่ยิปซีกับแม่หมออยู่แล้วนะคะ\n\n"
                    . "💬 พิมพ์คำถามเฉพาะที่อยากรู้มาได้เลย เช่น:\n"
                    . "   • *\"ความรักช่วงนี้เป็นไง\"*\n"
                    . "   • *\"งานในเดือนหน้า\"*\n"
                    . "   • *\"ควรย้ายงานไหม\"*\n\n"
                    . $timeHint . "\n"
                    . "❌ หรือพิมพ์ *\"พอแค่นี้\"* เพื่อจบสนทนา",
                'reading' => $reading,
            ];
        }

        // ❌ คำถามสั้นเกินไป — ขอใหม่
        if (mb_strlen($question) < 5) {
            return [
                'action' => 'celtic_question_too_short',
                'message' => "✍️ คำถามสั้นเกินไป\n"
                    . 'กรุณาพิมพ์คำถามที่ต้องการให้หมอทำนาย เช่น "ปีนี้ความรักจะเป็นอย่างไร" หรือ "ควรลาออกไหม"',
                'reading' => $reading,
            ];
        }

        // เช็ค time window (นับจาก Q1 ตอบเสร็จ)
        if (! $reading->canAskMoreCeltic()) {
            return $this->endCelticSession($reading, 'time_expired');
        }

        // 🔢 (2026-05-03) เช็คจำนวนคำถาม — admin ตั้งใน celtic_cross_max_questions
        //   0 = ไม่จำกัด (skip enforcement), N>0 = max N คำถาม
        $maxQuestions = (int) ($this->settings->celtic_cross_max_questions ?? 5);
        if ($maxQuestions > 0 && $reading->celtic_questions_used >= $maxQuestions) {
            return $this->endCelticSession($reading, 'max_questions_reached');
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

        $reading->refresh();
        $sequence = $result['sequence'];
        $wantsEnd = (bool) ($result['wants_end'] ?? false);

        // 🔚 AI signal ว่าพร้อมจบ session (ลูกค้านอกเรื่อง / ถามครอบคลุมแล้ว / ลูกค้าวกวน)
        // 🛡️ guard: Q1 ห้ามจบ — ลูกค้าเพิ่งจ่ายค่าครู ต้องเปิดให้ถามต่ออย่างน้อยรอบหนึ่ง
        if ($wantsEnd && $sequence > 1) {
            return $this->endCelticSession($reading, 'ai_signal', $result['response']);
        }

        // 🔢 (2026-05-03) ถ้าตอบไปครบ max แล้ว → จบ session พร้อมคำตอบสุดท้าย
        //   เฉพาะเมื่อ maxQuestions > 0 (0 = unlimited)
        if ($maxQuestions > 0 && $sequence >= $maxQuestions) {
            return $this->endCelticSession($reading, 'max_questions_reached', $result['response']);
        }

        // ปกติ — ตอบเสร็จ ให้ลูกค้าถามต่อได้
        $reading->update(['conversation_status' => FortuneReading::STATUS_CELTIC_AWAITING_QUESTION]);

        $remainingMin = $reading->getCelticQaRemainingMinutes();
        $qaWindow = (int) ($this->settings->celtic_cross_qa_window_minutes ?? 30);
        $timeHint = $remainingMin !== null
            ? "⏳ เหลือเวลาคุยกับแม่หมออีก {$remainingMin} นาที"
            : "⏳ เจ้าชะตาคุยต่อได้ภายใน {$qaWindow} นาทีนับจากคำทำนายแรก";

        if ($maxQuestions > 0) {
            $remainingQs = max(0, $maxQuestions - $sequence);
            $qHint = "❓ ถามได้อีก *{$remainingQs}* จาก {$maxQuestions} คำถาม";
        } else {
            $qHint = "❓ ถามได้ *ไม่จำกัด* (ภายในเวลาที่กำหนด)";
        }

        $followupOffer = "\n\n──────────────────────\n"
            . $timeHint . "\n"
            . $qHint . "\n"
            . "💬 พิมพ์คำถามถัดไปได้เลย — หรือพิมพ์ *\"พอแค่นี้\"* เพื่อจบสนทนา ✨";

        return [
            'action' => 'celtic_question_answered',
            'message' => $result['response'] . $followupOffer,
            'reading' => $reading,
            'celtic_sequence' => $sequence,
        ];
    }

    /**
     * จบ Celtic session อย่างสุขุม → reset state ให้กลับเข้า normal loop ของระบบ
     *
     * เรียกเมื่อ:
     *   - AI ส่ง [END_SESSION] token (นอกเรื่อง / ครอบคลุม / วกวน)
     *   - Time window 30 นาที หมด
     *   - ลูกค้าพิมพ์ "พอแค่นี้" / "จบ"
     *   - Idle timeout (เรียกจาก scheduled command)
     *
     * @param  string  $reason  'ai_signal' | 'time_expired' | 'customer_said_done' | 'idle'
     * @param  string|null  $aiMessage  ถ้า AI ส่งข้อความปิดมาด้วย — แสดงข้อความนั้นแทน default
     */
    public function endCelticSession(FortuneReading $reading, string $reason = 'ai_signal', ?string $aiMessage = null): array
    {
        // 🔄 reset state กลับ COMPLETED → normal loop พร้อมรับ "ดูดวง" ใหม่ได้
        $reading->update(['conversation_status' => FortuneReading::STATUS_COMPLETED]);

        $maxQ = (int) ($this->settings->celtic_cross_max_questions ?? 5);
        $qaWindow = (int) ($this->settings->celtic_cross_qa_window_minutes ?? 30);

        $closingMessage = match ($reason) {
            'time_expired' => "⏰ *เวลาคุยกับแม่หมอหมดแล้วค่ะ*\n\n"
                . "{$qaWindow} นาทีนับจากคำทำนายแรก ผ่านไปเรียบร้อย — แม่หมอขอจบบทสนทนานี้\n"
                . "เพื่อไปสร้างบารมีกับเจ้าชะตาท่านอื่นต่อ ขอให้เจ้าชะตาโชคดีนะคะ 🙏✨\n\n"
                . "💜 หากต้องการดูใหม่ พิมพ์ *\"ดูดวง\"* ได้ตลอดเลยค่ะ",

            'idle' => "🌙 *แม่หมอเห็นว่าเจ้าชะตาเงียบไปนาน*\n\n"
                . "พลังงานในวงไพ่จางลงแล้ว แม่หมอขอจบบทสนทนานี้นะคะ\n"
                . "ขอให้เจ้าชะตาเจอแต่สิ่งดีๆ 🙏✨\n\n"
                . "💜 พิมพ์ *\"ดูดวง\"* ได้ตลอดเมื่อพร้อมนะคะ",

            'customer_said_done' => "🌟 *ขอบคุณที่ใช้บริการดูดวงไพ่ยิปซี Celtic Cross นะคะ*\n\n"
                . "คำทำนายเป็นแสงไฟชี้ทาง — แต่การตัดสินใจอยู่ที่เจ้าชะตาเอง 🙏\n"
                . "ขอให้เจ้าชะตาเจอแต่สิ่งดีๆ นะคะ ✨\n\n"
                . "💜 หากต้องการดูใหม่ พิมพ์ *\"ดูดวง\"* ได้ตลอดเลยค่ะ",

            // 🔢 (2026-05-03) ครบโควต้าคำถาม (เกิดเฉพาะเมื่อ maxQ > 0)
            'max_questions_reached' => ($aiMessage ? trim($aiMessage) . "\n\n" : '')
                . "🌟 *เจ้าชะตาถามครบ " . max(1, $maxQ) . " คำถามแล้วค่ะ*\n\n"
                . "แม่หมอตอบครบทุกข้อสงสัยของเจ้าชะตา 🙏✨\n"
                . "คำทำนายเป็นแสงไฟชี้ทาง — เจ้าชะตาตัดสินใจเอง 💫\n\n"
                . "💜 หากต้องการดูใหม่ พิมพ์ *\"ดูดวง\"* ได้ตลอดนะคะ",

            default => ($aiMessage ? trim($aiMessage) . "\n\n" : '')
                . "🌟 *แม่หมอกล่าวลาเจ้าชะตา*\n\n"
                . "คำทำนายเป็นแสงไฟชี้ทาง — เจ้าชะตาตัดสินใจเอง 🙏\n\n"
                . "💜 หากต้องการดูใหม่ พิมพ์ *\"ดูดวง\"* ได้ตลอดนะคะ",
        };

        // 🖼️ (2026-05-03) สร้างภาพ Celtic Cross spread ตอนจบ — โชว์ทีเดียวสวยๆ
        //   เดิมโชว์หลังเปิดครบ 10 ใบ (ก่อนถาม Q1) → ลูกค้าตื่นเต้นเกินไป
        //   ตอนนี้: รวบรวมเป็น "ที่ระลึก" ตอนปิดทำนาย
        $composeUrl = null;
        try {
            $generator = app(CelticSpreadImageGenerator::class);
            $composeUrl = $generator->generate($reading);
        } catch (\Throwable $e) {
            \Log::warning('Celtic: composite image fail (endCelticSession)', [
                'reading_id' => $reading->id,
                'error' => $e->getMessage(),
            ]);
        }

        return [
            'action' => 'celtic_session_ended',
            'message' => $closingMessage,
            'reading' => $reading,
            'end_reason' => $reason,
            'celtic_summary_image_url' => $composeUrl,
        ];
    }

    /**
     * State: CELTIC_QA_PROMPT (legacy — ปัจจุบันใช้ CELTIC_AWAITING_QUESTION เป็นหลัก)
     *
     * 🆕 (2026-05-02) AI-driven flow — ระบบไม่ส่ง state นี้แล้ว
     * เก็บ handler ไว้กรณี reading เก่าค้างอยู่ใน state นี้
     */
    protected function handleCelticQaPrompt(FortuneReading $reading, string $messageText): array
    {
        // "พอแค่นี้" / "จบ" → จบ session อย่างสุขุม
        if ($this->matchesExactKeyword($messageText, ['พอแค่นี้', 'พอแล้ว', 'จบ', 'ขอบคุณ', 'thanks', 'พอ'])) {
            return $this->endCelticSession($reading, 'customer_said_done');
        }

        // อย่างอื่น = ถือเป็นคำถามใหม่
        $reading->update(['conversation_status' => FortuneReading::STATUS_CELTIC_AWAITING_QUESTION]);

        return $this->handleCelticAwaitingQuestion($reading, $messageText);
    }

    /**
     * Detection: ลูกค้าพิมพ์คำขอเริ่มดูดวงใหม่ระหว่างอยู่ใน Celtic flow
     *
     * เช่น "ดูดวง", "ดูดวงใหม่", "เริ่มใหม่", "ทำนาย" — คำเปล่าๆ ไม่มีบริบท
     * ใช้กัน UX ปัญหา: ลูกค้าพิมพ์ "ดูดวง" ระหว่างเปิดไพ่ → ถือเป็น "พร้อม" สุ่มไพ่ผิด
     */
    protected function looksLikeFortuneRestartRequest(string $text): bool
    {
        $clean = mb_strtolower(trim($text));
        // ลบคำลงท้ายไทย
        $clean = preg_replace('/\s*(ค่ะ|ครับ|คะ|จ้า|จ้ะ|จ๊ะ|นะ|หน่อย|ที|สิ|เลย)\s*$/u', '', $clean);

        // จำกัดเฉพาะข้อความสั้น (≤ 25 char) เพื่อไม่จับคำถามจริง เช่น "ดูดวงเรื่องความรักหน่อย"
        if (mb_strlen($clean) > 25) {
            return false;
        }

        // 🇱🇦 Lao: ເບິ່ງດວງ, ທຳນາຍ, ເລີ່ມໃໝ່, ໃໝ່
        $exact = ['ดูดวง', 'ดูดวงใหม่', 'ขอดูดวง', 'อยากดูดวง', 'ทำนาย', 'ทำนายให้', 'หมอดู',
            'เริ่มใหม่', 'ดูใหม่', 'restart', 'reset', 'ใหม่', 'start over',
            'ເບິ່ງດວງ', 'ເບິ່ງດວງໃໝ່', 'ຂໍເບິ່ງດວງ', 'ຢາກເບິ່ງດວງ', 'ທຳນາຍ', 'ທຳນາຍໃຫ້', 'ຫມໍດູ',
            'ເລີ່ມໃໝ່', 'ເບິ່ງໃໝ່', 'ໃໝ່'];

        foreach ($exact as $kw) {
            if ($clean === $kw || $clean === mb_strtolower($kw)) {
                return true;
            }
        }

        return false;
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
