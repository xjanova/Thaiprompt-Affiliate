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
        // 🎁 (2026-05-04) ห้ามเสนอ "ทำนายฟรี" ใน tier menu — ระบบฟรีให้เฉพาะตอบกลับ DM react/comment
        //   เดิม: shouldOfferFreeCard() เช็ค first-timer + feature → user-facing free button
        //   ใหม่: ฟรี trigger ผ่าน tryAutoFreeCardForFirstReply() เท่านั้น (silent)
        $offerFree = false;

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
        // 🌐 (2026-05-03) localize header + intro — ลูกค้าลาวเห็นเมนูเป็นลาว
        $packageCount = 1 + ($celticEnabled ? 1 : 0) + ($offerFree ? 1 : 0);
        $welcomeLine = \App\Services\FortuneLocaleService::lo(
            '🌙✨ *หมอจันทรายินดีต้อนรับเจ้าชะตาค่ะ* ✨🌙',
            '🌙✨ *ໝໍຈັນທາຍິນດີຕ້ອນຮັບເຈົ້າຊາຕາເດີ* ✨🌙'
        );
        $introLine = \App\Services\FortuneLocaleService::lo(
            "วันนี้เจ้าชะตาอยากให้หมอเปิดทางดวงให้แบบไหนคะ?\n"
                . "เลือกได้ *1 จาก {$packageCount} แพคเกจ* ด้านล่างเลย 👇",
            "ມື້ນີ້ເຈົ້າຊາຕາຢາກໃຫ້ໝໍເປີດທາງດວງໃຫ້ແບບໃດເດີ?\n"
                . "ເລືອກໄດ້ *1 ໃນ {$packageCount} ແພັກເກດ* ດ້ານລຸ່ມເລີຍ 👇"
        );
        $message = $welcomeLine . "\n\n" . $introLine . "\n\n";

        // 🎁 (2026-05-03) แพคเกจ "ทำนายฟรี" — เฉพาะ first-timer + feature เปิด
        if ($offerFree) {
            $freeBlock = \App\Services\FortuneLocaleService::lo(
                "━━━━━━━━━━━━━━━━━\n"
                    . "🎁 *ทำนายฟรี (1 ใบ) — สิทธิ์พิเศษครั้งแรก* 🌙\n"
                    . "━━━━━━━━━━━━━━━━━\n"
                    . "🃏 *เปิดไพ่ยิปซี 1 ใบ ที่จิตเจ้าชะตาเลือกเอง*\n"
                    . "    แม่หมอใช้จิตสัมผัสดวงสมพงษ์ — ทำนายสถานการณ์ปัจจุบัน + ชี้ทางออก\n\n"
                    . "✨ *เหมาะกับ:* คนใหม่ที่อยากลองสัมผัสพลังหมอจันทรา\n"
                    . "⏱️ *เวลา:* ทำนายเสร็จใน 1 นาที\n"
                    . "💎 *เงื่อนไข:* ใช้ได้ครั้งเดียวเท่านั้น/ท่าน\n\n",
                "━━━━━━━━━━━━━━━━━\n"
                    . "🎁 *ທຳນາຍຟຣີ (1 ໃບ) — ສິດທິພິເສດຄັ້ງທຳອິດ* 🌙\n"
                    . "━━━━━━━━━━━━━━━━━\n"
                    . "🃏 *ເປີດໄພ່ຍິບຊີ 1 ໃບ ທີ່ຈິດເຈົ້າຊາຕາເລືອກເອງ*\n"
                    . "    ແມ່ໝໍໃຊ້ຈິດສຳຜັດດວງສົມພົງ — ທຳນາຍສະຖານະການປັດຈຸບັນ + ຊີ້ທາງອອກ\n\n"
                    . "✨ *ເໝາະກັບ:* ຄົນໃໝ່ທີ່ຢາກລອງສຳຜັດພະລັງໝໍຈັນທາ\n"
                    . "⏱️ *ເວລາ:* ທຳນາຍແລ້ວໃນ 1 ນາທີ\n"
                    . "💎 *ເງື່ອນໄຂ:* ໃຊ້ໄດ້ຄັ້ງດຽວເທົ່ານັ້ນ/ທ່ານ\n\n"
            );
            $message .= $freeBlock;
        }

        // 📌 หมายเลขแพคเกจ — dynamic ตามว่ามีฟรีไหม
        $deepNum = \App\Services\FortuneLocaleService::lo(
            $offerFree ? 'ที่ 2' : 'ที่ 1',
            $offerFree ? 'ທີ່ 2' : 'ທີ່ 1'
        );
        $celticNum = \App\Services\FortuneLocaleService::lo(
            $offerFree ? 'ที่ 3' : 'ที่ 2',
            $offerFree ? 'ທີ່ 3' : 'ທີ່ 2'
        );

        // ━━━━━━━━━━━━━━━━━━━━━━━━
        // 🔹 39 บาท — Basic Deep
        // ━━━━━━━━━━━━━━━━━━━━━━━━
        $deepBlock = \App\Services\FortuneLocaleService::lo(
            "━━━━━━━━━━━━━━━━━\n"
                . "🔹 *แพคเกจ{$deepNum} — ดูดวงพื้นฐาน {$deepPrice} บาท* 💫\n"
                . "━━━━━━━━━━━━━━━━━\n"
                . "📅 *วิเคราะห์จากวันเดือนปีเกิด*\n"
                . "    หมอจะคำนวณดาวเจ้าชนะ + ราศี + ลัคนาให้\n\n"
                . "🃏 *ไพ่ยิปซี 1 ใบ ที่จิตเจ้าชะตาเลือกเอง*\n"
                . "    ไพ่ใบเดียว — ตรงประเด็น แม่นยำ ไม่ยกเมฆ\n\n"
                . "💎 *เหมาะกับ:* คนอยากรู้ดวงรวมๆ — เริ่มต้นง่าย ราคาเป็นมิตร\n"
                . "⏱️ *เวลา:* ทำนายเสร็จใน 1-3 นาที\n\n",
            "━━━━━━━━━━━━━━━━━\n"
                . "🔹 *ແພັກເກດ{$deepNum} — ເບິ່ງດວງພື້ນຖານ {$deepPrice} ບາດ* 💫\n"
                . "━━━━━━━━━━━━━━━━━\n"
                . "📅 *ວິເຄາະຈາກວັນເດືອນປີເກີດ*\n"
                . "    ໝໍຈະຄຳນວນດາວເຈົ້າຊະນະ + ລາສີ + ລັກຄະນາໃຫ້\n\n"
                . "🃏 *ໄພ່ຍິບຊີ 1 ໃບ ທີ່ຈິດເຈົ້າຊາຕາເລືອກເອງ*\n"
                . "    ໄພ່ໃບດຽວ — ກົງປະເດັນ ແມ່ນຍຳ ບໍ່ຍົກເມກ\n\n"
                . "💎 *ເໝາະກັບ:* ຄົນຢາກຮູ້ດວງລວມໆ — ເລີ່ມຕົ້ນງ່າຍ ລາຄາເປັນມິດ\n"
                . "⏱️ *ເວລາ:* ທຳນາຍແລ້ວໃນ 1-3 ນາທີ\n\n"
        );
        $message .= $deepBlock;

        // ━━━━━━━━━━━━━━━━━━━━━━━━
        // 🔮 99 บาท — Celtic Cross (Premium) — เฉพาะถ้า admin เปิด
        // ━━━━━━━━━━━━━━━━━━━━━━━━
        if ($celticEnabled) {
            // 🩹 (2026-05-04) ลด ad copy เหลือสั้น ๆ — user request: "แค่บอกว่าดูแบบโบราณ celtic cross ดั้งเดิม ไม่ต้องสาธยายเยอะ"
            $celticBlock = \App\Services\FortuneLocaleService::lo(
                "━━━━━━━━━━━━━━━━━\n"
                    . "👑 *แพคเกจ{$celticNum} — Celtic Cross {$celticPrice} บาท*\n"
                    . "━━━━━━━━━━━━━━━━━\n"
                    . "🃏 ไพ่ 10 ใบ ตามหลัก *Celtic Cross โบราณดั้งเดิม*\n"
                    . "⏱️ ประมาณ 5-10 นาที\n\n",
                "━━━━━━━━━━━━━━━━━\n"
                    . "👑 *ແພັກເກດ{$celticNum} — Celtic Cross {$celticPrice} ບາດ*\n"
                    . "━━━━━━━━━━━━━━━━━\n"
                    . "🃏 ໄພ່ 10 ໃບ ຕາມຫລັກ *Celtic Cross ໂບຮານດັ້ງເດີມ*\n"
                    . "⏱️ ປະມານ 5-10 ນາທີ\n\n"
            );
            $message .= $celticBlock;
        }

        // 👇 CTA — รวมตัวเลือกตามที่มี
        $ctaHeader = \App\Services\FortuneLocaleService::lo(
            "━━━━━━━━━━━━━━━━━\n"
                . "👇 *เลือกแพคเกจของเจ้าชะตา* 👇\n"
                . "━━━━━━━━━━━━━━━━━\n",
            "━━━━━━━━━━━━━━━━━\n"
                . "👇 *ເລືອກແພັກເກດຂອງເຈົ້າຊາຕາ* 👇\n"
                . "━━━━━━━━━━━━━━━━━\n"
        );
        $message .= $ctaHeader;
        if ($offerFree) {
            $message .= \App\Services\FortuneLocaleService::lo(
                "✦ พิมพ์ *\"ทำนายฟรี\"* — รับสิทธิ์ฟรี 1 ใบ\n",
                "✦ ພິມ *\"ທຳນາຍຟຣີ\"* — ຮັບສິດທິຟຣີ 1 ໃບ\n"
            );
        }
        $message .= \App\Services\FortuneLocaleService::lo(
            "✦ พิมพ์ *\"{$deepPrice}\"* — เริ่มแพคเกจพื้นฐาน {$deepPrice} บาท\n",
            "✦ ພິມ *\"{$deepPrice}\"* — ເລີ່ມແພັກເກດພື້ນຖານ {$deepPrice} ບາດ\n"
        );
        if ($celticEnabled) {
            $message .= \App\Services\FortuneLocaleService::lo(
                "✦ พิมพ์ *\"{$celticPrice}\"* หรือ *\"celtic\"* — เริ่มแพคเกจเต็มสำรับ\n",
                "✦ ພິມ *\"{$celticPrice}\"* ຫຼື *\"celtic\"* — ເລີ່ມແພັກເກດເຕັມສຳລັບ\n"
            );
        }
        $message .= \App\Services\FortuneLocaleService::lo(
            "✦ หรือ *กดปุ่มด้านล่าง* ได้เลยค่ะ ✨\n\n"
                . "🙏 ถ้ายังไม่พร้อม พิมพ์ *\"ยกเลิก\"* ได้นะคะ",
            "✦ ຫຼື *ກົດປຸ່ມດ້ານລຸ່ມ* ໄດ້ເລີຍເດີ ✨\n\n"
                . "🙏 ຖ້າຍັງບໍ່ພ້ອມ ພິມ *\"ຍົກເລີກ\"* ໄດ້ເດີ"
        );

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

                // 🛑 (2026-05-06) Pay-Later removed — ทุกคนเข้า pay-first ตรงๆ
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

        // 🩹 (2026-05-04) Resume guard — กัน double-charge เมื่อมีบิลค้าง
        //   เคสที่จับ:
        //     1. ลูกค้าจ่าย 99฿ แล้วหลุดกลาง flow (เปิดไพ่ 6/10 → bug welcome bubble)
        //        → กดปุ่ม 99฿ ใหม่ ระบบเดิมสร้างบิลใหม่ → จ่ายซ้ำ
        //     2. แอดมินรัน fortune:celtic-reset → reading พร้อมใช้แล้ว
        //        แต่ลูกค้าไม่อ่าน DM → กด 99฿ → เดิมสร้างบิลใหม่
        //     3. (2026-05-04 expanded) ลูกค้ามีบิล CELTIC_PENDING_PAYMENT ค้างจ่าย
        //        + UPA ยัง reserved (ภายใน 30 นาที) → กด 99 ใหม่ ระบบเดิมสร้างบิลใหม่
        //        ทำให้บิลขัดกัน (เคสที่ user รายงาน FTU-260504-T8747)
        //   กฎ: ถ้ามี Celtic ที่ paid + questions_used=0 + 24h → resume (เคส 1, 2)
        //        ถ้ามี Celtic pending payment + UPA active → ใช้บิล/QR เดิม (เคส 3)
        $resumable = $this->findResumableCelticReading($reading);
        if ($resumable && $resumable->id !== $reading->id) {
            Log::info('Celtic: resume existing paid reading (skip new bill)', [
                'new_reading_id' => $reading->id,
                'resume_reading_id' => $resumable->id,
                'resume_status' => $resumable->conversation_status,
                'picked_count' => $resumable->getCelticPickedCount(),
            ]);

            // ปิด reading ใหม่ที่ trigger เข้ามา (กัน orphan)
            $reading->update(['conversation_status' => FortuneReading::STATUS_COMPLETED]);

            return $this->buildCelticResumeResponse($resumable, /*fromAdminReset:*/ false);
        }

        // 🩹 (2026-05-04) Pending-payment dedup — ใช้บิลเดิมถ้า UPA ยัง active
        $pending = $this->findPendingCelticBill($reading);
        if ($pending && $pending->id !== $reading->id) {
            Log::info('Celtic: reuse existing pending payment bill (skip new UPA)', [
                'new_reading_id' => $reading->id,
                'pending_reading_id' => $pending->id,
                'bill_reference' => $pending->bill_reference,
            ]);

            // ปิด reading ใหม่ที่ trigger (กัน orphan)
            $reading->update(['conversation_status' => FortuneReading::STATUS_COMPLETED]);

            return $this->buildCelticPendingPaymentReuseResponse($pending);
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

        // ✅ (2026-05-04) Explicit pick keywords — ชัดเจนว่าจะเปิดไพ่ ห้ามถือเป็น chitchat
        //    เคสที่เคยติด: "พร้อมแล้วค่ะ", "เปิดเลย", "ok", "yes", "ใช่" — ลูกค้ายืนยันชัด
        //    ที่เคย bug: looksLikeMetaOrChitchat อาจจับ "ดี" prefix → "ดีค่ะเปิดเลย" → chitchat → ไม่เปิดไพ่
        $isExplicitPick = $this->matchesCelticReadyKeyword($messageText);

        // 🔄 ลูกค้าพิมพ์ "ดูดวง" / "เริ่มใหม่" — ห้ามถือเป็น "พร้อม" สุ่มไพ่
        if (! $isExplicitPick && $this->looksLikeFortuneRestartRequest($messageText)) {
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

        // chitchat → ย้ำขั้นตอน (ข้ามได้ถ้า explicit pick keyword)
        if (! $isExplicitPick && $this->looksLikeMetaOrChitchat($messageText)) {
            return [
                'action' => 'celtic_chitchat_reminder',
                'message' => "🃏 ตอนนี้อยู่ขั้นเปิดไพ่นะคะ\n\n"
                    . $this->buildCelticPickPromptText($reading)
                    . "\n\nเจ้าชะตาแค่พิมพ์ 'พร้อม' เพื่อให้หมอสุ่มไพ่ใบถัดไปค่ะ",
                'reading' => $reading,
            ];
        }

        // 📊 (2026-05-04) Diagnostic log — ทำให้ debug stuck-at-card-N ในอนาคตง่าย
        \Illuminate\Support\Facades\Log::info('🃏 Celtic: handleCelticPicking → attempt pickNextCard', [
            'reading_id' => $reading->id,
            'picked_count_before' => $reading->getCelticPickedCount(),
            'next_position' => $reading->getNextCelticPosition(),
            'message_preview' => mb_substr($messageText, 0, 80),
            'explicit_pick_kw' => $isExplicitPick,
        ]);

        // ไม่ใช่ chitchat — ถือว่า "พร้อม" เปิดไพ่
        $service = app(CelticCrossService::class);
        $result = $service->pickNextCard($reading);

        if (! $result['success']) {
            // 📊 (2026-05-04) Log failure ชัดเจน — กัน silent stuck
            \Illuminate\Support\Facades\Log::warning('🃏 Celtic: pickNextCard ล้มเหลว', [
                'reading_id' => $reading->id,
                'next_position' => $reading->getNextCelticPosition(),
                'failure_message' => $result['message'] ?? null,
            ]);

            // 🛟 (2026-05-04) ใส่ Quick Reply ในข้อความ failure ด้วย — ไม่ให้ลูกค้าค้าง
            //    เปลี่ยน action เป็น celtic_chitchat_reminder ที่ ChannelManager มี QR อยู่แล้ว
            $picked = $reading->getCelticPickedCount();
            $next = $reading->getNextCelticPosition() ?? '?';

            return [
                'action' => 'celtic_chitchat_reminder',
                'message' => "⚠️ สุ่มไพ่ใบที่ {$next} ไม่สำเร็จ — ลองพิมพ์ 'พร้อม' หรือกดปุ่มข้างล่างอีกครั้งนะคะ\n\n"
                    . "🃏 เปิดไพ่ไปแล้ว *{$picked}/10 ใบ*\n\n"
                    . ($result['message'] ?? '')
                    . "\n\nหากกดแล้วไม่หาย ลองพิมพ์ *'สับใหม่'* เพื่อรีเซ็ตไพ่ (ไม่ต้องจ่ายซ้ำ)",
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
     *
     * 🩹 (2026-05-05) เพิ่มข้อความ "ต้องเปิดครบ 10 ใบก่อนทำนาย" — user spec
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
        $picked = $reading->getCelticPickedCount();
        $remaining = 10 - $picked;

        return "🃏 *ใบที่ {$next}/10 — ตำแหน่ง [{$name}]*\n"
            . "💭 ตำแหน่งนี้บอกถึง: {$desc}\n\n"
            . "🧘 ตั้งจิต หลับตา 3 วินาที นึกถึงสิ่งที่อยากรู้\n"
            . "เมื่อพร้อมแล้ว พิมพ์ 'พร้อม' เพื่อให้หมอเปิดไพ่ใบนี้ค่ะ\n\n"
            . "📌 *ต้องเปิดครบ 10 ใบก่อน แม่หมอจึงเริ่มทำนาย*\n"
            . "   เปิดไปแล้ว {$picked}/10 — เหลืออีก *{$remaining} ใบ*";
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
                . "🃏 หมอจะเปิดไพ่ให้ทีละใบ ทั้งหมด *10 ใบ*\n"
                . "📌 ต้องเปิดครบทั้ง 10 ใบก่อน — แม่หมอจึงจะเริ่มทำนายและให้เจ้าชะตาถามคำถามได้\n\n"
                . "──────────────────────\n"
                . $this->buildCelticPickPromptText($reading),
            'reading' => $reading,
        ];
    }

    /**
     * หา Celtic reading ที่ paid + ยังไม่สำเร็จ — ใช้ resume แทนสร้างบิลใหม่
     *
     * เกณฑ์:
     *   - reading_type = celtic_cross
     *   - is_paid = true
     *   - celtic_questions_used = 0 (ลูกค้ายังไม่ได้ใช้สิทธิ์ถามเลย)
     *   - paid_at >= now()-24h (กันเก่าเกินไป)
     *   - conversation_status ∈ {CELTIC_PICKING, AWAITING_QUESTION, GENERATING, QA_PROMPT}
     *     (ไม่รวม COMPLETED — เสร็จแล้วไม่ resume)
     *
     * รองรับทั้ง $context.facebook_user_id และ platform_user_id
     */
    protected function findResumableCelticReading(FortuneReading $context): ?FortuneReading
    {
        $userId = $context->facebook_user_id ?? $context->platform_user_id;
        if (empty($userId)) {
            return null;
        }

        return FortuneReading::where(function ($q) use ($userId) {
                $q->where('facebook_user_id', $userId)
                    ->orWhere('platform_user_id', $userId);
            })
            ->where('reading_type', FortuneReading::READING_TYPE_CELTIC_CROSS)
            ->where('is_paid', true)
            ->where('celtic_questions_used', 0)
            ->where('paid_at', '>=', now()->subHours(24))
            ->whereIn('conversation_status', [
                FortuneReading::STATUS_CELTIC_PICKING,
                FortuneReading::STATUS_CELTIC_AWAITING_QUESTION,
                FortuneReading::STATUS_CELTIC_GENERATING,
                FortuneReading::STATUS_CELTIC_QA_PROMPT,
            ])
            ->latest('paid_at')
            ->first();
    }

    /**
     * หา Celtic reading ที่ยังรอจ่าย — ใช้ดูซ้ำแทนสร้างบิลใหม่
     *
     * เกณฑ์:
     *   - reading_type = celtic_cross
     *   - status = CELTIC_PENDING_PAYMENT
     *   - is_paid = false
     *   - มี UPA reserved + ยังไม่หมดอายุ
     *
     * เคสที่ปัด: UPA expired/cancelled → ปล่อยให้ flow สร้างบิลใหม่ตามปกติ
     */
    protected function findPendingCelticBill(FortuneReading $context): ?FortuneReading
    {
        $userId = $context->facebook_user_id ?? $context->platform_user_id;
        if (empty($userId)) {
            return null;
        }

        $candidate = FortuneReading::where(function ($q) use ($userId) {
                $q->where('facebook_user_id', $userId)
                    ->orWhere('platform_user_id', $userId);
            })
            ->where('reading_type', FortuneReading::READING_TYPE_CELTIC_CROSS)
            ->where('conversation_status', FortuneReading::STATUS_CELTIC_PENDING_PAYMENT)
            ->where('is_paid', false)
            ->whereNotNull('unique_payment_amount_id')
            ->latest('updated_at')
            ->first();

        if (! $candidate) {
            return null;
        }

        // เช็ค UPA ว่ายัง reserved + ไม่หมดอายุ
        $upa = $candidate->uniquePaymentAmount;
        if (! $upa || $upa->status !== 'reserved' || $upa->expires_at <= now()) {
            return null;
        }

        return $candidate;
    }

    /**
     * Build response สำหรับ reuse pending payment bill (เคส 3 ใน resume guard)
     * ส่ง QR + bill_ref + เวลาเหลือ — ลูกค้าโอนยอดเดิม
     */
    protected function buildCelticPendingPaymentReuseResponse(FortuneReading $reading): array
    {
        $upa = $reading->uniquePaymentAmount;
        $payAmount = number_format((float) $upa->unique_amount, 2);
        $billRef = $reading->bill_reference ?? '-';
        $remainingMin = (int) max(0, now()->diffInMinutes($upa->expires_at, false));
        $name = $reading->facebook_user_name ?? 'เจ้าชะตา';

        // หา QR image (ถ้ามี method generatePromptPayQrImage)
        $qrImageUrl = null;
        try {
            if (method_exists($this, 'generatePromptPayQrImage')) {
                $qrImageUrl = $this->generatePromptPayQrImage((float) $upa->unique_amount, $reading->id);
            }
            if (! $qrImageUrl && method_exists($this, 'getPaymentQrImageUrl')) {
                $qrImageUrl = $this->getPaymentQrImageUrl();
            }
        } catch (\Throwable $e) {
            // ignore QR fail — ส่ง text only ก็ได้
        }

        $message = "🌙 *พบบิล Celtic Cross ของคุณ{$name}ที่ยังรอชำระ*\n\n"
            . "📋 เลขบิล: {$billRef}\n"
            . "💰💰 ยอดที่ต้องโอน: *{$payAmount}* บาท 💰💰\n"
            . "⏳ เหลือเวลา: {$remainingMin} นาที\n\n"
            . "═══════════════════════\n\n"
            . "⚠️ *โอนยอดให้ตรงเป๊ะ {$payAmount} บาท* (ทศนิยมด้วย!)\n"
            . "✅ โอนแล้ว ระบบตัดบิลอัตโนมัติ → เริ่มเปิดไพ่ทันที\n"
            . "❌ พิมพ์ *\"ยกเลิก\"* ถ้าไม่ต้องการต่อ\n\n"
            . "💡 ไม่ต้องสร้างบิลใหม่ — ใช้บิลนี้โอนเลยค่ะ";

        return [
            'action' => 'celtic_pending_payment_reuse',
            'message' => $message,
            'reading' => $reading,
            'payment_qr_url' => $qrImageUrl,
        ];
    }

    /**
     * สร้าง response สำหรับ resume Celtic reading — ใช้ทั้ง 3 จุด:
     *   1. startCelticCrossFlow (ลูกค้ากด 99฿ ใหม่ทั้งที่มีบิลค้าง)
     *   2. fortune:celtic-recover --auto (auto-recovery scheduled task)
     *   3. fortune:celtic-reset (admin reset → DM ลูกค้า)
     *
     * Response message ตรงกับ state ปัจจุบัน:
     *   - CELTIC_PICKING + 0 picked → "เริ่มเปิดไพ่ใบที่ 1"
     *   - CELTIC_PICKING + N picked → "ต่อจากใบที่ N+1"
     *   - CELTIC_AWAITING_QUESTION → "เปิดครบแล้ว ถามคำถามได้เลย"
     *   - CELTIC_GENERATING → "แม่หมอกำลังพิจารณาอยู่"
     *   - CELTIC_QA_PROMPT → "ถามต่อ / พอแค่นี้"
     *
     * Public — ให้ console commands (recover/reset) เรียกได้ด้วย
     */
    public function buildCelticResumeResponse(FortuneReading $reading, bool $fromAdminReset = false): array
    {
        $picked = (int) $reading->getCelticPickedCount();
        $billRef = $reading->bill_reference ?? '-';
        $name = $reading->facebook_user_name ?? 'เจ้าชะตา';

        // หัวข้อ — แตกต่างกันระหว่าง admin reset / customer-triggered resume
        if ($fromAdminReset) {
            $header = "🔄 *แอดมินรีเซ็ตการดูดวงให้แล้วค่ะ คุณ{$name}*\n"
                . "💚 ค่าครูเดิมยังใช้ได้ — ไม่ต้องจ่ายซ้ำ\n"
                . "📋 เลขบิล: {$billRef}\n\n"
                . "═══════════════════════\n\n";
        } else {
            $header = "✨ *พบบิลของคุณ{$name}ที่ยังใช้สิทธิ์ไม่ครบ*\n"
                . "💚 ค่าครูเดิมยังใช้ได้ — ไม่ต้องจ่ายซ้ำ\n"
                . "📋 เลขบิล: {$billRef}\n\n"
                . "═══════════════════════\n\n";
        }

        switch ($reading->conversation_status) {
            case FortuneReading::STATUS_CELTIC_PICKING:
                if ($picked === 0) {
                    $body = "🔮 *เริ่มเปิดไพ่ Celtic Cross กันเลย*\n"
                        . "หมอจะเปิดไพ่ให้ทีละใบ พร้อมตำแหน่งที่ได้\n\n"
                        . "──────────────────────\n"
                        . $this->buildCelticPickPromptText($reading);
                } else {
                    $body = "🃏 เปิดไพ่ไปแล้ว *{$picked}/10 ใบ* — เริ่มต่อกันค่ะ!\n\n"
                        . "──────────────────────\n"
                        . $this->buildCelticPickPromptText($reading);
                }

                return [
                    'action' => 'celtic_pick_prompt',
                    'message' => $header . $body,
                    'reading' => $reading,
                ];

            case FortuneReading::STATUS_CELTIC_AWAITING_QUESTION:
                $maxQ = (int) ($this->settings->celtic_cross_max_questions ?? 5);
                $qaWindow = (int) ($this->settings->celtic_cross_qa_window_minutes ?? 30);
                $qLimitText = $maxQ <= 0 ? 'ไม่จำกัด' : "{$maxQ} คำถาม";
                $body = "🌟 *เปิดไพ่ครบ 10 ใบแล้ว — แม่หมอพร้อมตอบคำถาม*\n\n"
                    . "💬 พิมพ์คำถามแรกที่อยากรู้มาได้เลย — แม่หมอจะอ่านพลังงานให้\n\n"
                    . "❓ ถามได้ *{$qLimitText}* (ภายใน {$qaWindow} นาที)\n"
                    . "🔚 พิมพ์ *\"พอแค่นี้\"* เมื่อพอใจ";

                return [
                    'action' => 'celtic_resume_qa',
                    'message' => $header . $body,
                    'reading' => $reading,
                ];

            case FortuneReading::STATUS_CELTIC_GENERATING:
                $body = "🌌 แม่หมอกำลังพิจารณาไพ่ทั้ง 10 ใบให้เจ้าชะตาอยู่...\n"
                    . "กรุณารอสักครู่ (~30-60 วินาที) ✨";

                return [
                    'action' => 'celtic_processing',
                    'message' => $header . $body,
                    'reading' => $reading,
                ];

            case FortuneReading::STATUS_CELTIC_QA_PROMPT:
                $body = "💬 *ต้องการถามต่อหรือพอแค่นี้?*\n\n"
                    . "👉 พิมพ์ *\"ถามต่อ\"* เพื่อถามคำถามถัดไป\n"
                    . "🔚 พิมพ์ *\"พอแค่นี้\"* เพื่อจบรอบ";

                return [
                    'action' => 'celtic_qa_prompt_resume',
                    'message' => $header . $body,
                    'reading' => $reading,
                ];

            default:
                // Fallback — ถือว่าเริ่มใหม่
                $body = "🔮 พิมพ์ *\"พร้อม\"* เพื่อเปิดไพ่ใบถัดไปค่ะ";

                return [
                    'action' => 'celtic_pick_prompt',
                    'message' => $header . $body,
                    'reading' => $reading,
                ];
        }
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
            // 🆕 (2026-05-06) เพิ่ม Quick Reply ปุ่ม "เริ่มถามคำถาม"
            //    ลูกค้าหลังเปิดไพ่ครบ มักงงไม่รู้ต้องทำอะไรต่อ
            //    ปุ่มนี้เป็น UX cue — กดแล้ว bot ส่ง prompt ตัวอย่างคำถามซ้ำ
            'quick_replies' => [
                [
                    'content_type' => 'text',
                    'title' => '💬 เริ่มถามคำถาม',
                    'payload' => 'CELTIC_START_Q',
                ],
            ],
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

        // 💬 (2026-05-06) ลูกค้ากดปุ่ม "เริ่มถามคำถาม" — แค่ส่ง prompt hint ใหม่ ไม่ใช่คำถามจริง
        if ($this->matchesExactKeyword($messageText, ['เริ่มถามคำถาม', 'เริ่ม', 'พร้อมถาม', 'ขอถาม'])) {
            $remainingMin = $reading->getCelticQaRemainingMinutes();
            $timeHint = $remainingMin !== null && $remainingMin > 0
                ? "⏳ ถามได้อีก {$remainingMin} นาที"
                : '⏳ ถามได้ภายใน 30 นาทีนับจากคำทำนายแรก';

            return [
                'action' => 'celtic_start_question_hint',
                'message' => "💬 *เริ่มถามคำถามได้เลย*\n\n"
                    . "พิมพ์คำถามที่อยากรู้มาเลยค่ะ เช่น:\n\n"
                    . "   • *\"ความรักช่วงนี้เป็นไง\"*\n"
                    . "   • *\"งานในเดือนหน้า\"*\n"
                    . "   • *\"ควรย้ายงานไหม\"*\n\n"
                    . $timeHint . "\n"
                    . "🔚 หรือพิมพ์ *\"พอแค่นี้\"* เมื่อพอใจ",
                'reading' => $reading,
            ];
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

        // 🛡️ (2026-05-05) DISABLE ai_signal early-end ตาม user spec
        //   user 2026-05-05: "สำคัญอย่าสรุปก่อนลูกค้าถามคำถามสุดท้ายต้องสรุปที่คำถามสุดท้าย"
        //   เดิม: wants_end จาก AI → end session ตั้งแต่ Q2 → ลูกค้าโดนตัดก่อนถามครบ
        //   ใหม่: AI's wants_end ignore ทั้งหมด — ลูกค้าตัดสินใจเอง (พิมพ์ "พอแค่นี้") หรือ max reached
        if ($wantsEnd) {
            \Log::info('Celtic: AI signaled wants_end — ignored (user spec: never auto-end before max)', [
                'reading_id' => $reading->id,
                'sequence' => $sequence,
                'max_questions' => $maxQuestions,
            ]);
        }

        // 🔢 (2026-05-03) ถ้าตอบไปครบ max แล้ว → จบ session พร้อมคำตอบสุดท้าย
        //   เฉพาะเมื่อ maxQuestions > 0 (0 = unlimited)
        //   ⭐ (2026-05-05) endCelticSession จะ combine คำตอบสุดท้าย ($result['response']) + Grand Finale
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
     *   - AI ส่ง [END_SESSION] token (นอกเรื่อง / ครอบคลุม / วกวน) — DEPRECATED 2026-05-05
     *   - Time window QA หมด (time_expired) — ลูกค้าจ่ายแล้ว ต้องได้ summary ทุกครั้ง
     *   - ลูกค้าพิมพ์ "พอแค่นี้" / "จบ" (customer_said_done)
     *   - ครบ max questions (max_questions_reached) — combine final answer + summary
     *   - Idle timeout (idle) — เรียกจาก scheduled command fortune:celtic-auto-finalize
     *
     * @param  string  $reason  'ai_signal' | 'time_expired' | 'customer_said_done' | 'max_questions_reached' | 'idle'
     * @param  string|null  $aiMessage  ถ้ามีคำตอบสุดท้ายจาก AI — รวมเข้ากับ summary
     */
    public function endCelticSession(FortuneReading $reading, string $reason = 'ai_signal', ?string $aiMessage = null): array
    {
        // 🔄 reset state กลับ COMPLETED → normal loop พร้อมรับ "ดูดวง" ใหม่ได้
        $reading->update(['conversation_status' => FortuneReading::STATUS_COMPLETED]);

        $maxQ = (int) ($this->settings->celtic_cross_max_questions ?? 5);
        $qaWindow = (int) ($this->settings->celtic_cross_qa_window_minutes ?? 30);

        // 🌟 (2026-05-05) Grand Finale Master Summary — generate ทุกครั้งที่เข้าเงื่อนไข
        //   user spec 2026-05-05: "หากยังถามไม่ครบแต่ยุติลงก่อน...หลุดหมดเวลาคุย ให้เข้าโฟลว์
        //   บทสรุปเองและส่งคำทำนายสุดท้ายไปให้อัตโนมัติ"
        //   เดิม: skip ตอน time_expired/idle (เพราะคิดว่าลูกค้า offline)
        //   ใหม่: generate ทุกครั้งถ้ามีคำถาม + ไพ่ครบ — ลูกค้าจ่าย 99 บาท สมควรได้ summary
        //         (idle/time_expired pushed ผ่าน fortune:celtic-auto-finalize command)
        $shouldGenerateFinale = $reading->fresh()->celtic_questions_used >= 1
            && $reading->getCelticPickedCount() >= 10;

        $grandFinale = null;
        if ($shouldGenerateFinale) {
            try {
                $service = app(CelticCrossService::class);
                $grandFinaleResult = $service->generateGrandFinaleSummary($reading);
                if ($grandFinaleResult['success']) {
                    $grandFinale = $grandFinaleResult['summary'];
                    \Log::info('🌟 Celtic Grand Finale generated', [
                        'reading_id' => $reading->id,
                        'reason' => $reason,
                        'has_deep_link' => $grandFinaleResult['has_deep_link'],
                        'summary_len' => mb_strlen($grandFinale),
                    ]);
                }
            } catch (\Throwable $e) {
                \Log::warning('Celtic: Grand Finale generation threw — fallback to default', [
                    'reading_id' => $reading->id,
                    'error' => $e->getMessage(),
                ]);
                // ไม่ fail ทั้ง endCelticSession — ใช้ default closing แทน
            }
        }

        // 🌟 ถ้ามี Grand Finale → ใช้แทน default closing (สวยกว่า ลึกกว่า)
        // 🩹 (2026-05-05) max_questions_reached + has aiMessage → combine final answer + summary
        //   user spec: "เมื่อลูกค้าถามคำถามที่ 3 ก็ให้ตอบคำถามพร้อมสรุป"
        //   เดิม: aiMessage ถูก replace ด้วย Grand Finale (ลูกค้าไม่ได้คำตอบสุดท้าย!)
        //   ใหม่: ส่งคำตอบสุดท้ายก่อน → แล้วต่อด้วย Grand Finale
        if (! empty($grandFinale)) {
            $finalAnswerSection = '';
            if (! empty($aiMessage) && in_array($reason, ['max_questions_reached', 'time_expired', 'idle'], true)) {
                $finalAnswerSection = "🎴 *คำทำนายข้อสุดท้ายของเจ้าชะตา:*\n\n"
                    . trim($aiMessage) . "\n\n"
                    . str_repeat('━', 17) . "\n\n";
            }

            $closingMessage = $finalAnswerSection
                . "🌟✨ *บทสรุปสุดท้ายจากแม่หมอจันทรา* ✨🌟\n"
                . "👑 *VIP Master Reading*\n\n"
                . str_repeat('━', 17) . "\n\n"
                . $grandFinale . "\n\n"
                . str_repeat('━', 17) . "\n\n"
                . "💎 *ขอบคุณที่ไว้วางใจแม่หมอนะคะ*\n"
                . "💜 หากต้องการดูใหม่ พิมพ์ *\"ดูดวง\"* ได้ตลอดเลยค่ะ ✨";
        } else {
            // Default closings (เดิม) — ใช้เมื่อ Grand Finale skip หรือ fail
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
        }

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
            'has_grand_finale' => ! empty($grandFinale),
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
    /**
     * 🃏 (2026-05-04) ตรวจ explicit pick keywords สำหรับ CELTIC_PICKING
     *
     * เคยมี bug: ลูกค้าพิมพ์ "ดีค่ะเปิดเลย" / "ok พร้อม" / "เอาเลย" → ถูก looksLikeMetaOrChitchat
     * จับว่าเป็น chitchat (เพราะ "ดี" prefix หรือ "OK" มี "k") → ส่ง reminder วน ไม่เปิดไพ่
     *
     * แก้: ถ้า text มี keyword ชัดเจน → ตัดสินใจ "เปิดไพ่" ทันที ข้าม chitchat heuristic
     *
     * Match strategy: str_contains (ไม่ใช่ exact) เพื่อจับ "พร้อมแล้วค่ะเปิดเลย" / "ok เปิด"
     */
    protected function matchesCelticReadyKeyword(string $text): bool
    {
        $clean = mb_strtolower(trim($text));
        if ($clean === '') {
            return false;
        }

        // คำที่บ่งชี้ชัดว่าจะเปิดไพ่ — ทั้งไทย/อังกฤษ/ลาว
        $keywords = [
            'พร้อม', 'พรอม', 'เปิดเลย', 'เปิดไพ่', 'เปิดต่อ', 'เปิด', 'เอาเลย', 'เอาแล้ว',
            'ok', 'okay', 'โอเค', 'oke', 'okok',
            'yes', 'ใช่', 'ใช่ค่ะ', 'ใช่ครับ', 'จัด', 'จัดไป', 'ไป', 'go',
            'ໄພ່ຕໍ່', 'ພ້ອມ', 'ເປີດ', 'ເປີດໄພ່',
        ];

        foreach ($keywords as $kw) {
            $kwLower = mb_strtolower($kw);
            if (str_contains($clean, $kwLower)) {
                return true;
            }
        }

        return false;
    }

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
