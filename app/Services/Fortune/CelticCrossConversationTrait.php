<?php

namespace App\Services\Fortune;

use App\Jobs\ProcessVoiceSummaryJob;
use App\Models\FortuneReading;
use App\Models\UniquePaymentAmount;
use App\Services\CelticCrossService;
use App\Services\CelticSpreadImageGenerator;
use App\Services\FcmNotificationService;
use App\Services\FortuneLocaleService;
use Illuminate\Support\Facades\Cache;
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
     * @return array|null null ถ้าไม่ใช่ Celtic state — ให้ caller ส่งต่อ default handler
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
            FortuneReading::STATUS_CELTIC_GENERATING => $this->handleCelticGenerating($reading),
            default => null, // ไม่ใช่ Celtic state → ให้ caller จัดการต่อ
        };
    }

    /**
     * 🛡️ (2026-05-14) Handle CELTIC_GENERATING state with stuck-recovery
     *
     * Bug ที่แก้: เดิม return "หมอกำลังพิจารณา..." ตายตัว ถ้า AI call ค้าง
     * (PHP-FPM kill กลางทาง, OpenAI timeout >FPM limit, fatal error)
     * → status STUCK ที่ GENERATING ตลอดไป → ลูกค้าถามอะไรก็ตอบแบบเดิม
     *
     * วิธีกู้: เช็ค updated_at ของ reading
     * - ถ้า > STUCK_THRESHOLD_SEC (120s) → revert เป็น AWAITING_QUESTION
     *   + แจ้งลูกค้าให้พิมพ์คำถามใหม่
     * - ถ้ายังไม่เกิน → reply "กำลังพิจารณา" ตามเดิม
     *
     * Threshold 120s = OPENAI_RESPONSES_TIMEOUT — ถ้าเกินแน่นอนว่า process ตาย
     */
    protected function handleCelticGenerating(FortuneReading $reading): array
    {
        // 🆕 (2026-05-17) เดิม 240s — เพราะมี typing delay 60-120s + AI 30-60s
        // 🐛 (2026-05-29) ลดเป็น 90s — typing delay + debounce buffer ถูกลบหมดแล้ว
        //   (zero-delay 2026-05-23 + single-bot 2026-05-29) → ไม่มี delay บวกอีก
        //   AI gpt-5.5 ตอบจริง 20-40s (เคส 4211: seq 21-37s) → 90s = buffer เกินพอ
        //   เคสจริง reading 4211: status ค้าง 212s → ลูกค้าเงียบ → admin ต้องเข้าช่วยตอบเอง
        //   ลด threshold → ลูกค้าพิมพ์ปุ๊บ recover ทันที (ถามใหม่ได้ใน 90s แทนรอ 240s)
        $stuckThresholdSec = 90;
        $generatingForSec = $reading->updated_at
            ? abs(now()->diffInSeconds($reading->updated_at, false))
            : 0;

        // ค้างเกิน threshold → ถือว่า process ตายแล้ว → revert state เพื่อให้ถามใหม่ได้
        if ($generatingForSec >= $stuckThresholdSec) {
            \Log::warning('Celtic: GENERATING state stuck — auto-recovering to AWAITING_QUESTION', [
                'reading_id' => $reading->id,
                'bill_reference' => $reading->bill_reference,
                'stuck_for_sec' => $generatingForSec,
                'threshold_sec' => $stuckThresholdSec,
                'platform' => $reading->platform,
            ]);

            $reading->update(['conversation_status' => FortuneReading::STATUS_CELTIC_AWAITING_QUESTION]);

            $remainingMin = $reading->getCelticQaRemainingMinutes();
            $timeHint = $remainingMin !== null && $remainingMin > 0
                ? "⏳ เหลือเวลาคุยอีก {$remainingMin} นาที"
                : '⏳ ยังคุยกับแม่หมอได้ในช่วงเวลาที่กำหนด';

            return [
                'action' => 'celtic_stuck_recovered',
                'message' => "🌙 ขออภัยค่ะ เมื่อกี้แม่หมอเชื่อมจิตช้าไปนิด ✨\n\n"
                    ."💬 รบกวนเจ้าชะตาพิมพ์คำถามมาใหม่อีกครั้งนะคะ\n"
                    .'แม่หมอพร้อมรับฟังเสมอ 🙏'."\n\n"
                    .$timeHint,
                'reading' => $reading,
            ];
        }

        // ยังไม่ stuck → reply ตามเดิม
        return [
            'action' => 'celtic_processing',
            'message' => "🔮 หมอกำลังพิจารณาไพ่ทั้ง 10 ใบให้เจ้าชะตาอยู่...\n"
                .'กรุณารอสักครู่ (~30-60 วินาที) ✨',
            'reading' => $reading,
        ];
    }

    /**
     * 🌙 (2026-05-14) ส่ง pre-reply ทันทีก่อน AI call (Celtic Q&A)
     *
     * user report: "AI เหมือนจะตอบแต่เงียบ"
     * เคสจริง: AI ใช้เวลา 30-60+ วินาที — ลูกค้าไม่เห็นว่า bot ยอมรับคำถาม
     * Fix: push intermediate ack message ทันที — ลูกค้าเห็นว่า bot กำลังคิด
     *
     * Non-blocking: ส่งผ่าน push API + catch ทุก error (ไม่ให้กระทบ AI flow)
     */
    protected function sendCelticThinkingAck(FortuneReading $reading): void
    {
        $ackMessage = "🌙 *แม่หมอกำลังเชื่อมจิตกับไพ่ของเจ้าชะตา...*\n"
            ."ขอเวลา 1-2 นาที ✨\n\n"
            .'(อย่าเพิ่งพิมพ์ซ้ำนะคะ — แม่หมอกำลังตั้งสมาธิ 🙏)';

        if (! empty($reading->facebook_user_id)) {
            try {
                $fbService = app(\App\Services\FacebookWebhookService::class);
                $fbService->sendMessage($reading->facebook_user_id, $ackMessage);
            } catch (\Throwable $e) {
                \Log::debug('Celtic ack: FB send fail', ['error' => $e->getMessage()]);
            }
        } elseif (! empty($reading->line_user_id)) {
            try {
                $lineService = app(\App\Services\LineFortuneService::class);
                $lineService->sendMessage($reading->line_user_id, $ackMessage);
            } catch (\Throwable $e) {
                \Log::debug('Celtic ack: LINE send fail', ['error' => $e->getMessage()]);
            }
        }
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
        $deepEnabled = $this->settings->isDeepReadingEnabled();
        $celticEnabled = (bool) ($this->settings->enable_celtic_cross ?? false);

        // 🌙 (2026-05-23 → 2026-05-27 modified) Celtic-only mode handling
        //   เดิม: Deep ปิด + Celtic เปิด → startCelticCrossFlow ทันที (สร้างบิลเลย — ลูกค้าสับสน)
        //   ใหม่: render intro card สำหรับ Celtic-only ก่อน — ให้ลูกค้าเห็นราคา+ยืนยัน ก่อนสร้างบิล
        //   user feedback: "พิมพ์ดูดวง→สร้างบิลเลย ลูกค้ายังไม่รู้ว่าต้องเสียเงิน"
        //   ⚠️ ขั้นถัด: handleTierChoice มี keyword "celtic"/"99"/"เริ่มเลย" จับเข้า startCelticCrossFlow

        // 🔒 (2026-05-03) ถ้า admin ปิด Celtic — ปกติข้าม tier menu ไปดูดวง 39฿ ตรงๆ
        //    แต่ถ้า offerFree = true → ต้องโชว์ menu (มีปุ่ม [ฟรี] [39]) ก่อน
        // 💰 (2026-05-10 v3) Pay-First — ปิด legacy COLLECTING_BIRTHDATE
        //    เดิม: เก็บวันเกิด → คำถาม → เปิดไพ่ → afterTarotCardDrawn fall through สร้างบิล
        //    ใหม่: สร้างบิลทันที → จ่าย → เก็บข้อมูล (เลียนแบบ pay-first ที่ user สั่งย้าย)
        if (! $celticEnabled && ! $offerFree) {
            $reading->update([
                'reading_type' => FortuneReading::READING_TYPE_DEEP,
            ]);
            if (empty($reading->bill_reference)) {
                $reading->update(['bill_reference' => FortuneReading::generateBillReference()]);
            }

            // 💳 (2026-05-22) Route ตาม payment mode
            return $this->routePayFirstDeep($reading);
        }

        $reading->update(['conversation_status' => FortuneReading::STATUS_TIER_CHOICE]);

        $deepPrice = number_format($this->getDeepReadingPrice(), 0);
        $celticPrice = number_format(app(CelticCrossService::class)->getPrice(), 0);
        // 🔢 (2026-05-03) อ่านจาก settings — ตรงกับที่ admin ตั้ง (0 = ไม่จำกัด)
        $maxQRaw = (int) ($this->settings->celtic_cross_max_questions ?? 5);
        $qaWindow = (int) ($this->settings->celtic_cross_qa_window_minutes ?? 15);
        $qLimitText = $maxQRaw <= 0 ? 'ไม่จำกัด' : "{$maxQRaw} คำถาม";

        // 🎁 หัวเมนู — เปลี่ยนตามว่ามีปุ่มฟรีหรือไม่
        // 🌐 (2026-05-03) localize header + intro — ลูกค้าลาวเห็นเมนูเป็นลาว
        // 🌙 (2026-05-23) packageCount นับเฉพาะที่ enabled — Deep ปิดก็ไม่นับ
        // 🆕 (2026-05-27) Celtic-only mode = intro tone (ไม่ใช่ "เลือก 1 จาก 1")
        $packageCount = ($deepEnabled ? 1 : 0) + ($celticEnabled ? 1 : 0) + ($offerFree ? 1 : 0);
        $isCelticOnlyIntro = ! $deepEnabled && $celticEnabled && ! $offerFree;

        $welcomeLine = FortuneLocaleService::lo(
            '🌙✨ *หมอจันทรายินดีต้อนรับเจ้าชะตาค่ะ* ✨🌙',
            '🌙✨ *ໝໍຈັນທາຍິນດີຕ້ອນຮັບເຈົ້າຊາຕາເດີ* ✨🌙'
        );
        if ($isCelticOnlyIntro) {
            // Celtic-only — intro tone (ไม่บังคับ ลูกค้าเลือกเองได้ พร้อมจะอ่านราคาก่อน)
            $introLine = FortuneLocaleService::lo(
                "ตอนนี้แม่หมอเปิดบริการ *Celtic Cross* เพียงแพคเกจเดียวค่ะ\n"
                    .'ลองอ่านรายละเอียดด้านล่างก่อน — ถ้าพร้อมค่อยเริ่มได้เลย 👇',
                "ຕອນນີ້ແມ່ໝໍເປີດບໍລິການ *Celtic Cross* ພຽງແພັກເກດດຽວເດີ\n"
                    .'ລອງອ່ານລາຍລະອຽດດ້ານລຸ່ມກ່ອນ — ຖ້າພ້ອມຄ່ອຍເລີ່ມໄດ້ເລີຍ 👇'
            );
        } else {
            $introLine = FortuneLocaleService::lo(
                "วันนี้เจ้าชะตาอยากให้หมอเปิดทางดวงให้แบบไหนคะ?\n"
                    ."เลือกได้ *1 จาก {$packageCount} แพคเกจ* ด้านล่างเลย 👇",
                "ມື້ນີ້ເຈົ້າຊາຕາຢາກໃຫ້ໝໍເປີດທາງດວງໃຫ້ແບບໃດເດີ?\n"
                    ."ເລືອກໄດ້ *1 ໃນ {$packageCount} ແພັກເກດ* ດ້ານລຸ່ມເລີຍ 👇"
            );
        }
        $message = $welcomeLine."\n\n".$introLine."\n\n";

        // 🎁 (2026-05-03) แพคเกจ "ทำนายฟรี" — เฉพาะ first-timer + feature เปิด
        if ($offerFree) {
            $freeBlock = FortuneLocaleService::lo(
                "━━━━━━━━━━━━━━━━━\n"
                    ."🎁 *ทำนายฟรี (1 ใบ) — สิทธิ์พิเศษครั้งแรก* 🌙\n"
                    ."━━━━━━━━━━━━━━━━━\n"
                    ."🃏 *เปิดไพ่ยิปซี 1 ใบ ที่จิตเจ้าชะตาเลือกเอง*\n"
                    ."    แม่หมอใช้จิตสัมผัสดวงสมพงษ์ — ทำนายสถานการณ์ปัจจุบัน + ชี้ทางออก\n\n"
                    ."✨ *เหมาะกับ:* คนใหม่ที่อยากลองสัมผัสพลังหมอจันทรา\n"
                    ."⏱️ *เวลา:* ทำนายเสร็จใน 1 นาที\n"
                    ."💎 *เงื่อนไข:* ใช้ได้ครั้งเดียวเท่านั้น/ท่าน\n\n",
                "━━━━━━━━━━━━━━━━━\n"
                    ."🎁 *ທຳນາຍຟຣີ (1 ໃບ) — ສິດທິພິເສດຄັ້ງທຳອິດ* 🌙\n"
                    ."━━━━━━━━━━━━━━━━━\n"
                    ."🃏 *ເປີດໄພ່ຍິບຊີ 1 ໃບ ທີ່ຈິດເຈົ້າຊາຕາເລືອກເອງ*\n"
                    ."    ແມ່ໝໍໃຊ້ຈິດສຳຜັດດວງສົມພົງ — ທຳນາຍສະຖານະການປັດຈຸບັນ + ຊີ້ທາງອອກ\n\n"
                    ."✨ *ເໝາະກັບ:* ຄົນໃໝ່ທີ່ຢາກລອງສຳຜັດພະລັງໝໍຈັນທາ\n"
                    ."⏱️ *ເວລາ:* ທຳນາຍແລ້ວໃນ 1 ນາທີ\n"
                    ."💎 *ເງື່ອນໄຂ:* ໃຊ້ໄດ້ຄັ້ງດຽວເທົ່ານັ້ນ/ທ່ານ\n\n"
            );
            $message .= $freeBlock;
        }

        // 📌 หมายเลขแพคเกจ — dynamic ตามว่ามีฟรีไหม + Deep เปิดไหม
        // 🌙 (2026-05-23) ถ้า Deep ปิด — Celtic จะรับเลข Deep แทน
        $deepNum = FortuneLocaleService::lo(
            $offerFree ? 'ที่ 2' : 'ที่ 1',
            $offerFree ? 'ທີ່ 2' : 'ທີ່ 1'
        );
        // Celtic อยู่ลำดับถัดจาก [ฟรี] + [Deep ถ้าเปิด]
        $celticPos = ($offerFree ? 1 : 0) + ($deepEnabled ? 1 : 0) + 1;
        $celticNum = FortuneLocaleService::lo(
            "ที่ {$celticPos}",
            "ທີ່ {$celticPos}"
        );

        // ━━━━━━━━━━━━━━━━━━━━━━━━
        // 🔹 Deep (39 บาท) — Basic — เฉพาะถ้า admin เปิด
        // ━━━━━━━━━━━━━━━━━━━━━━━━
        // 🌙 (2026-05-23) wrap ใน guard — Deep ปิด = ไม่ render block (กัน "39 บาท" หลุดแม้ caller จะลืม guard)
        if ($deepEnabled) {
            $deepBlock = FortuneLocaleService::lo(
                "━━━━━━━━━━━━━━━━━\n"
                    ."🔹 *แพคเกจ{$deepNum} — ดูดวงพื้นฐาน {$deepPrice} บาท* 💫\n"
                    ."━━━━━━━━━━━━━━━━━\n"
                    ."📅 *วิเคราะห์จากวันเดือนปีเกิด*\n"
                    ."    หมอจะคำนวณดาวเจ้าชนะ + ราศี + ลัคนาให้\n\n"
                    ."🃏 *ไพ่ยิปซี 1 ใบ ที่จิตเจ้าชะตาเลือกเอง*\n"
                    ."    ไพ่ใบเดียว — ตรงประเด็น แม่นยำ ไม่ยกเมฆ\n\n"
                    ."💎 *เหมาะกับ:* คนอยากรู้ดวงรวมๆ — เริ่มต้นง่าย ราคาเป็นมิตร\n"
                    ."⏱️ *เวลา:* ทำนายเสร็จใน 1-3 นาที\n\n",
                "━━━━━━━━━━━━━━━━━\n"
                    ."🔹 *ແພັກເກດ{$deepNum} — ເບິ່ງດວງພື້ນຖານ {$deepPrice} ບາດ* 💫\n"
                    ."━━━━━━━━━━━━━━━━━\n"
                    ."📅 *ວິເຄາະຈາກວັນເດືອນປີເກີດ*\n"
                    ."    ໝໍຈະຄຳນວນດາວເຈົ້າຊະນະ + ລາສີ + ລັກຄະນາໃຫ້\n\n"
                    ."🃏 *ໄພ່ຍິບຊີ 1 ໃບ ທີ່ຈິດເຈົ້າຊາຕາເລືອກເອງ*\n"
                    ."    ໄພ່ໃບດຽວ — ກົງປະເດັນ ແມ່ນຍຳ ບໍ່ຍົກເມກ\n\n"
                    ."💎 *ເໝາະກັບ:* ຄົນຢາກຮູ້ດວງລວມໆ — ເລີ່ມຕົ້ນງ່າຍ ລາຄາເປັນມິດ\n"
                    ."⏱️ *ເວລາ:* ທຳນາຍແລ້ວໃນ 1-3 ນາທີ\n\n"
            );
            $message .= $deepBlock;
        }

        // ━━━━━━━━━━━━━━━━━━━━━━━━
        // 🔮 99 บาท — Celtic Cross (Premium) — เฉพาะถ้า admin เปิด
        // ━━━━━━━━━━━━━━━━━━━━━━━━
        if ($celticEnabled) {
            // 🩹 (2026-05-04) ลด ad copy เหลือสั้น ๆ — user request: "แค่บอกว่าดูแบบโบราณ celtic cross ดั้งเดิม"
            // 🌙 (2026-05-23 v3) ประกาศกติกาให้ชัด — 5 คำถาม / 15 นาที (จาก settings)
            $celticBlock = FortuneLocaleService::lo(
                "━━━━━━━━━━━━━━━━━\n"
                    ."👑 *แพคเกจ{$celticNum} — Celtic Cross {$celticPrice} บาท*\n"
                    ."━━━━━━━━━━━━━━━━━\n"
                    ."🃏 ไพ่ 10 ใบ ตามหลัก *Celtic Cross โบราณดั้งเดิม*\n"
                    ."💬 *คุยกับแม่หมอได้ {$qLimitText} ภายใน {$qaWindow} นาที*\n"
                    ."⏱️ ทำนายเสร็จใน 5-10 นาที (พิมพ์คำถามได้ทันที ไม่มีรอ)\n\n",
                "━━━━━━━━━━━━━━━━━\n"
                    ."👑 *ແພັກເກດ{$celticNum} — Celtic Cross {$celticPrice} ບາດ*\n"
                    ."━━━━━━━━━━━━━━━━━\n"
                    ."🃏 ໄພ່ 10 ໃບ ຕາມຫລັກ *Celtic Cross ໂບຮານດັ້ງເດີມ*\n"
                    ."💬 *ຄຸຍກັບແມ່ໝໍໄດ້ {$qLimitText} ພາຍໃນ {$qaWindow} ນາທີ*\n"
                    ."⏱️ ທຳນາຍແລ້ວໃນ 5-10 ນາທີ (ພິມຄຳຖາມໄດ້ທັນທີ)\n\n"
            );
            $message .= $celticBlock;
        }

        // 👇 CTA — รวมตัวเลือกตามที่มี
        // 🆕 (2026-05-27) Celtic-only intro = CTA tone "พร้อม/ไว้คราวหน้า" (ไม่ใช่ "เลือกแพคเกจ")
        if ($isCelticOnlyIntro) {
            $ctaHeader = FortuneLocaleService::lo(
                "━━━━━━━━━━━━━━━━━\n"
                    ."👇 *พร้อมเริ่มหรือยังคะ?* 👇\n"
                    ."━━━━━━━━━━━━━━━━━\n",
                "━━━━━━━━━━━━━━━━━\n"
                    ."👇 *ພ້ອມເລີ່ມຫຼືຍັງເດີ?* 👇\n"
                    ."━━━━━━━━━━━━━━━━━\n"
            );
            $message .= $ctaHeader;
            $message .= FortuneLocaleService::lo(
                "✦ ถ้าพร้อม — พิมพ์ *\"เริ่มเลย\"* / *\"celtic\"* / *\"{$celticPrice}\"*\n"
                    ."✦ ถ้าอยากถามอะไรก่อน — พิมพ์มาได้เลย แม่หมอตอบให้\n"
                    .'✦ ถ้ายังไม่พร้อม — พิมพ์ *"ไว้คราวหน้า"* หรือ *"ยกเลิก"* ก็ได้ ไม่เป็นไรค่ะ 🙏',
                "✦ ຖ້າພ້ອມ — ພິມ *\"ເລີ່ມເລີຍ\"* / *\"celtic\"* / *\"{$celticPrice}\"*\n"
                    ."✦ ຖ້າຢາກຖາມຫຍັງກ່ອນ — ພິມມາໄດ້ເລີຍ ແມ່ໝໍຕອບໃຫ້\n"
                    .'✦ ຖ້າຍັງບໍ່ພ້ອມ — ພິມ *"ໄວ້ຄາວໜ້າ"* ຫຼື *"ຍົກເລີກ"* ກໍ່ໄດ້ ບໍ່ເປັນຫຍັງເດີ 🙏'
            );
        } else {
            $ctaHeader = FortuneLocaleService::lo(
                "━━━━━━━━━━━━━━━━━\n"
                    ."👇 *เลือกแพคเกจของเจ้าชะตา* 👇\n"
                    ."━━━━━━━━━━━━━━━━━\n",
                "━━━━━━━━━━━━━━━━━\n"
                    ."👇 *ເລືອກແພັກເກດຂອງເຈົ້າຊາຕາ* 👇\n"
                    ."━━━━━━━━━━━━━━━━━\n"
            );
            $message .= $ctaHeader;
            if ($offerFree) {
                $message .= FortuneLocaleService::lo(
                    "✦ พิมพ์ *\"ทำนายฟรี\"* — รับสิทธิ์ฟรี 1 ใบ\n",
                    "✦ ພິມ *\"ທຳນາຍຟຣີ\"* — ຮັບສິດທິຟຣີ 1 ໃບ\n"
                );
            }
            // 🌙 (2026-05-23) Deep CTA — แสดงเฉพาะเมื่อ Deep เปิด
            if ($deepEnabled) {
                $message .= FortuneLocaleService::lo(
                    "✦ พิมพ์ *\"{$deepPrice}\"* — เริ่มแพคเกจพื้นฐาน {$deepPrice} บาท\n",
                    "✦ ພິມ *\"{$deepPrice}\"* — ເລີ່ມແພັກເກດພື້ນຖານ {$deepPrice} ບາດ\n"
                );
            }
            if ($celticEnabled) {
                $message .= FortuneLocaleService::lo(
                    "✦ พิมพ์ *\"{$celticPrice}\"* หรือ *\"celtic\"* — เริ่มแพคเกจเต็มสำรับ\n",
                    "✦ ພິມ *\"{$celticPrice}\"* ຫຼື *\"celtic\"* — ເລີ່ມແພັກເກດເຕັມສຳລັບ\n"
                );
            }
            $message .= FortuneLocaleService::lo(
                "✦ หรือ *กดปุ่มด้านล่าง* ได้เลยค่ะ ✨\n\n"
                    .'🙏 ถ้ายังไม่พร้อม พิมพ์ *"ยกเลิก"* ได้นะคะ',
                "✦ ຫຼື *ກົດປຸ່ມດ້ານລຸ່ມ* ໄດ້ເລີຍເດີ ✨\n\n"
                    .'🙏 ຖ້າຍັງບໍ່ພ້ອມ ພິມ *"ຍົກເລີກ"* ໄດ້ເດີ'
            );
        }

        return [
            'action' => 'tier_choice',
            'message' => $message,
            'reading' => $reading,
            'deep_price' => $deepPrice,
            'celtic_price' => $celticPrice,
            'offer_free' => $offerFree, // 🎁 (2026-05-03) flag ให้ ChannelManager เพิ่มปุ่ม "🎁 ทำนายฟรี"
            'celtic_only_intro' => $isCelticOnlyIntro, // 🆕 (2026-05-27) flag ให้ ChannelManager เปลี่ยน label ปุ่ม
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

        // 🛑 (2026-05-27) Soft decline ที่ tier_choice — ลูกค้ายังไม่ทันสร้างบิล แต่บอก "ไม่พร้อม/ไว้คราวหน้า/ไม่เอา"
        //   User spec: "ฟัง อย่าตื้อ — ลูกค้ายังไม่รู้ว่าต้องจ่ายเงิน เห็น intro แล้วบอกไม่เอา → ยกเลิกให้เลย"
        //   ⚠️ ต้องเช็ค looksLikeNeedPaymentHelp ก่อน — กัน false-cancel "ไม่เข้าใจ" / "งง"
        if (method_exists($this, 'looksLikeSoftDeclineDuringPayment')
            && method_exists($this, 'looksLikeNeedPaymentHelp')
            && ! $this->looksLikeNeedPaymentHelp($messageText)
            && $this->looksLikeSoftDeclineDuringPayment($messageText)
            && method_exists($this, 'executeCancelAndReturnToChat')) {
            \Illuminate\Support\Facades\Log::info('Fortune: soft decline ที่ tier_choice → cancel + ขอบคุณ', [
                'reading_id' => $reading->id,
                'text_preview' => mb_substr($messageText, 0, 60),
            ]);

            return $this->executeCancelAndReturnToChat($reading, 'soft_decline_tier_choice');
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
        $celticPriceInt = (int) app(CelticCrossService::class)->getPrice();
        $qaWindow = (int) ($this->settings->celtic_cross_qa_window_minutes ?? 15);
        $deepEnabledLocal = $this->settings->isDeepReadingEnabled();
        $celticEnabledLocal = (bool) ($this->settings->enable_celtic_cross ?? false);

        // 🆕 (2026-05-27) Celtic-only intro accept — ลูกค้ากด "เริ่มเลย" หรือพิมพ์ "ตกลง/พร้อม/เอา"
        //   ใช้เฉพาะ Celtic-only mode (Deep ปิด) — ไม่ ambiguous
        //   เคสเสริม: ลูกค้ากดปุ่ม postback TIER_CELTIC_99 (FB ส่ง "ดูดวง" + Cache flag)
        //             → Cache flag = "intent ชัด ต้องการ Celtic" → ยอมรับเป็น accept
        if (! $deepEnabledLocal && $celticEnabledLocal) {
            $userIdForCache = $reading->facebook_user_id ?? $reading->platform_user_id;
            $hasForceTierFlag = ! empty($userIdForCache)
                && \Illuminate\Support\Facades\Cache::get("fortune:force_tier:{$userIdForCache}") === 'celtic';

            if ($hasForceTierFlag) {
                \Illuminate\Support\Facades\Log::info('Fortune: Celtic-only intro accepted via postback Cache flag', [
                    'reading_id' => $reading->id,
                ]);

                return $this->startCelticCrossFlow($reading);
            }

            $acceptKeywords = [
                'เริ่มเลย', 'เริ่ม', 'พร้อม', 'พร้อมแล้ว', 'ตกลง', 'โอเค', 'เอาเลย', 'เอา',
                'ok', 'okay', 'yes', 'ใช่', 'ใช่ค่ะ', 'ใช่ครับ', 'จัดมา',
                'celtic_intro_start', 'celtic_intro_accept',
            ];
            foreach ($acceptKeywords as $kw) {
                if ($textLower === mb_strtolower($kw) || mb_strpos($textLower, mb_strtolower($kw)) === 0) {
                    \Illuminate\Support\Facades\Log::info('Fortune: Celtic-only intro accepted', [
                        'reading_id' => $reading->id,
                        'keyword' => $kw,
                    ]);

                    return $this->startCelticCrossFlow($reading);
                }
            }
        }

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
                // 💰 (2026-05-10 v3) Pay-First — ปิด legacy COLLECTING_BIRTHDATE
                //   เดิม comment บอก "ทุกคนเข้า pay-first" แต่ logic ส่งไป COLLECTING_BIRTHDATE legacy
                //   จริง — ลูกค้าเก็บวันเกิด/คำถาม/เปิดไพ่ จนเสร็จ → เปิดไพ่ไม่ไปขั้นชำระเงิน
                //   ใหม่: เลือก 39 → สร้างบิลทันที → ลูกค้าจ่าย → ค่อยขอวันเกิด
                $updateData = [
                    'reading_type' => FortuneReading::READING_TYPE_DEEP,
                ];
                if (empty($reading->bill_reference)) {
                    $updateData['bill_reference'] = FortuneReading::generateBillReference();
                }
                $reading->update($updateData);

                // 💳 (2026-05-22) Route ตาม payment mode
                return $this->routePayFirstDeep($reading);
            }
        }

        // ❓ ไม่ตรงกับ keyword ใดๆ
        //
        // 🤖 (2026-05-19 Batch 4) ก่อน fallback re-show menu → ลอง AI chitchat ตอบให้
        //   User spec: "ระหว่างนำเสนอแพคเกจ ถ้าผู้ใช้พูดอื่น AI ต้องตอบได้ ไม่ใช่บังคับเลือกอย่างเดียว"
        //
        //   Strategy: ถ้า message ดูเป็น chitchat/meta (คำถาม, ความรู้สึก, เรื่องอื่น) →
        //     ใช้ buildAIAssistedStepReminder ตอบ AI สั้นๆ + เตือนเบาๆ "ยังรอเลือกแพคเกจอยู่นะคะ"
        //     ถ้า AI disabled / Gatekeeper throttle / API fail → fallback re-show menu (hint อย่างเดียว)
        //
        //   Pattern เดียวกับ handleConfirmationResponse:3712, handleBirthdateInput, handleQuestionInput
        //   ✅ safe ที่ปลอดภัย (ทดสอบมาแล้วใน flow อื่น) — ถ้า looksLikeMetaOrChitchat=false → fallback เดิม
        // 🌙 (2026-05-23) สร้าง hint dynamic — ถ้า Deep ปิด ไม่ใส่บรรทัด 39
        $deepEnabledHint = $this->settings->isDeepReadingEnabled();
        $celticEnabledHint = (bool) ($this->settings->enable_celtic_cross ?? false);
        $stepHintCompact = "🙏 ยังรอเจ้าชะตาเลือกแพคเกจอยู่นะคะ\n";
        if ($deepEnabledHint) {
            $stepHintCompact .= "🔹 *\"{$deepPriceInt}\"* — ดูดวงพื้นฐาน {$deepPriceInt} บาท (วันเกิด + ไพ่ 1 ใบ)\n";
        }
        if ($celticEnabledHint) {
            $stepHintCompact .= "🔮 *\"{$celticPriceInt}\"* หรือ *\"celtic\"* — ไพ่ยิปซีเต็มสำรับ {$celticPriceInt} บาท (10 ใบ + คุยจุใจ {$qaWindow} นาที)\n";
        }
        $stepHintCompact .= '❌ *"ยกเลิก"* — หากไม่ต้องการตอนนี้';

        // 🛡️ Safe guard — ถ้า looksLikeMetaOrChitchat/buildAIAssistedStepReminder ไม่มี (trait isolation)
        //   หรือ throw exception → fallback re-show menu ปกติ ไม่ทำให้ flow crash
        try {
            if (method_exists($this, 'looksLikeMetaOrChitchat')
                && $this->looksLikeMetaOrChitchat($messageText)) {
                $aiMessage = $this->buildAIAssistedStepReminder(
                    $messageText,
                    $stepHintCompact,
                    $reading->user_profile,
                    'tier_choice'
                );

                return [
                    'action' => 'tier_choice_chitchat',
                    'message' => $aiMessage,
                    'reading' => $reading,
                ];
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('handleTierChoice: chitchat fallback ล้ม (non-blocking)', [
                'reading_id' => $reading->id ?? null,
                'error' => $e->getMessage(),
            ]);
            // fall through to default re-show menu
        }

        // ❓ ไม่ใช่ chitchat (หรือ AI fail) → ส่ง re-show menu แบบกระชับ (fallback เดิม)
        // 🌙 (2026-05-23) re-show menu dynamic — ไม่ใส่บรรทัด 39 ถ้า Deep ปิด
        $reshowMessage = "🙏 ขอให้เจ้าชะตาเลือกแพคเกจอีกครั้งนะคะ\n\n";
        if ($deepEnabledHint) {
            $reshowMessage .= "🔹 พิมพ์ *\"{$deepPriceInt}\"* — ดูดวงพื้นฐาน {$deepPriceInt} บาท\n"
                ."    📅 วันเดือนปีเกิด + 🃏 ไพ่ยิปซี 1 ใบ\n\n";
        }
        if ($celticEnabledHint) {
            $reshowMessage .= "🔮 พิมพ์ *\"{$celticPriceInt}\"* หรือ *\"celtic\"* — ไพ่ยิปซีเต็มสำรับ {$celticPriceInt} บาท\n"
                ."    🃏 Celtic Cross 10 ใบ + คุยกับแม่หมอจุใจ {$qaWindow} นาที\n\n";
        }
        $reshowMessage .= "❌ พิมพ์ *\"ยกเลิก\"* หากไม่ต้องการดูตอนนี้\n\n"
            .'👇 หรือกดปุ่มด้านล่างก็ได้นะคะ ✨';

        return [
            'action' => 'tier_choice_invalid',
            'message' => $reshowMessage,
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
    protected function startCelticCrossFlow(FortuneReading $reading, bool $skipStripeGate = false): array
    {
        // 🔒 (2026-05-17) Race-condition lock — กันสร้างบิลซ้อนจากการกดรัวๆ
        //   user report: "ลูกค้าสร้างบิลรัวๆ ได้"
        //   atomic Cache::add lock 10s ครอบ resume check + UPA gen + setCelticPendingPayment
        //   ถ้า lock ไม่ได้ = มี request กำลังสร้างอยู่ → wait + เช็ค pending → reuse
        $userId = $reading->facebook_user_id ?? $reading->platform_user_id;

        // 🔒 (2026-05-20) Defense-in-depth — ห้ามสร้างบิลใหม่ระหว่างทำนาย
        //   ถ้าลูกค้ามี IN_PREDICTION reading อื่นที่ paid อยู่ (39฿ AI gen หรือ
        //   Celtic flow อื่น) → ห้ามสร้าง Celtic ใหม่
        if (! empty($userId) && method_exists($this, 'isInPrediction') && $this->isInPrediction($userId)) {
            \Log::warning('Celtic: startCelticCrossFlow ถูกเรียกระหว่างทำนาย — silent skip', [
                'facebook_user_id' => $userId,
                'reading_id' => $reading->id,
            ]);

            return [
                'action' => 'silent_skip_in_prediction',
                'message' => null,
                'reading' => null,
            ];
        }

        // 🛒 (2026-05-18) Hook A — บันทึก "บอทเสนอขาย Celtic 99฿" (throttle 5min)
        try {
            if (! empty($userId)) {
                $platformForPitch = $reading->platform
                    ?? (preg_match('/^U[0-9a-f]{32}$/i', (string) $userId) ? 'line' : 'facebook');
                app(\App\Services\Fortune\CustomerPersonaService::class)
                    ->recordPitch($platformForPitch, $userId, $reading->facebook_user_name ?? null);
            }
        } catch (\Throwable $e) {
            // non-blocking
            \Log::debug('Celtic: recordPitch failed (non-blocking)', ['error' => $e->getMessage()]);
        }

        $lockKey = "fortune:celtic_create_lock:{$userId}";
        $lockAcquired = ! empty($userId) ? \Illuminate\Support\Facades\Cache::add($lockKey, 1, 10) : true;

        if (! $lockAcquired) {
            \Log::info('Celtic: bill_create_lock contention — wait + reuse pending', [
                'facebook_user_id' => $userId,
                'reading_id' => $reading->id,
            ]);
            usleep(600000); // 600ms — รอ request แรก commit

            $pending = $this->findPendingCelticBill($reading);
            if ($pending && $pending->id !== $reading->id) {
                $reading->update(['conversation_status' => FortuneReading::STATUS_COMPLETED]);

                return $this->buildCelticPendingPaymentReuseResponse($pending);
            }
            // ถ้า request แรกล้มเหลว → fall through to normal flow (lock จะหมดอายุใน 10s)
        }

        try {
            return $this->doStartCelticCrossFlow($reading, $skipStripeGate);
        } finally {
            if ($lockAcquired && ! empty($userId)) {
                \Illuminate\Support\Facades\Cache::forget($lockKey);
            }
        }
    }

    /**
     * 🤖 (2026-05-17) Inner implementation of startCelticCrossFlow
     * แยกออกมาเพื่อให้ outer method ห่อ Cache lock ได้ clean
     */
    protected function doStartCelticCrossFlow(FortuneReading $reading, bool $skipStripeGate = false): array
    {
        // 💳 (2026-05-22) Payment method matrix:
        //   - both / stripe_only → ถามวิธีชำระก่อน
        //   - sms_only / none → ไป QR Thai (createPaymentBill ด้านล่าง) ตรงเลย
        //   $skipStripeGate=true → ลูกค้าเลือก QR Thai แล้ว → ลงสร้างบิล UPA ตรงเลย
        $celticPaymentMode = $this->getActivePaymentMode();
        if (! $skipStripeGate && ($celticPaymentMode === 'both' || $celticPaymentMode === 'stripe_only')) {
            // ตรวจ resume / dedup ก่อน (ถ้ามี Celtic ค้าง → ไม่ต้องถามวิธีชำระใหม่)
            $resumable = $this->findResumableCelticReading($reading);
            if ($resumable && $resumable->id !== $reading->id) {
                $reading->update(['conversation_status' => FortuneReading::STATUS_COMPLETED]);

                return $this->buildCelticResumeResponse($resumable, false);
            }
            $pending = $this->findPendingCelticBill($reading);
            if ($pending && $pending->id !== $reading->id) {
                $reading->update(['conversation_status' => FortuneReading::STATUS_COMPLETED]);

                return $this->buildCelticPendingPaymentReuseResponse($pending);
            }

            // เปลี่ยน reading_type=celtic_cross + status=AWAITING_PAYMENT_METHOD
            $reading->update([
                'reading_type' => FortuneReading::READING_TYPE_CELTIC_CROSS,
                'conversation_status' => FortuneReading::STATUS_AWAITING_PAYMENT_METHOD,
            ]);

            return $this->askPaymentMethod($reading);
        }

        // เช็ค toggle
        if (! $this->settings->enable_celtic_cross) {
            $reading->update(['conversation_status' => FortuneReading::STATUS_COMPLETED]);

            return [
                'action' => 'celtic_disabled',
                'message' => "🔮 ขออภัยค่ะ บริการดูดวงไพ่ยิปซีเต็มสำรับ Celtic Cross ปิดการใช้งานชั่วคราว\n\n"
                    .'ขณะนี้สามารถดูดวงพื้นฐานฟรีได้ตามปกติ พิมพ์คำถามมาได้เลย 🙏',
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

            return $this->buildCelticResumeResponse($resumable, /* fromAdminReset: */ false);
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
                        ."กรุณาพิมพ์ 'celtic cross' อีกครั้งในอีก 10 วินาที เพื่อให้ระบบสร้างบิลใหม่ค่ะ\n\n"
                        .'⚠️ *อย่าโอนเงิน*จนกว่าจะได้รับบิลใหม่',
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
                app(FcmNotificationService::class)->notifyNewFortuneReading($reading);
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

            $qaWindow = (int) ($this->settings->celtic_cross_qa_window_minutes ?? 15);
            $maxQ = (int) ($this->settings->celtic_cross_max_questions ?? 5);
            $qLimitTxt = $maxQ > 0 ? "{$maxQ} คำถาม" : 'ไม่จำกัด';

            return [
                'action' => 'celtic_pending_payment',
                // 🌙 (2026-05-23 v3) ประกาศกติกาให้ชัดในบิล — 5 คำถาม / 15 นาที
                'message' => "🔮 *ดูดวงไพ่ยิปซีเต็มสำรับ Celtic Cross*\n\n"
                    ."✨ ค่าครู: {$baseAmountStr} บาท\n"
                    ."🃏 เปิดไพ่ 10 ใบ ตำแหน่งครบสายพันปี\n"
                    ."💬 คุยกับแม่หมอได้ *{$qLimitTxt} ภายใน {$qaWindow} นาที* (นับจากคำทำนายแรก)\n"
                    ."⚡ ตอบทันที ไม่มีรอ — พิมพ์ปุ๊บแม่หมอตอบปั๊บ\n"
                    ."🖼️ ได้รับภาพ Celtic Cross spread สวยๆ ส่งให้ตอนจบทำนาย เป็นที่ระลึก\n\n"
                    ."──────────────────────\n"
                    ."💸 *ค่าครูสำหรับบิลนี้: {$payAmount} บาท*\n"
                    ."(ต้องโอนทศนิยมตรงเป๊ะ ระบบใช้ทศนิยมจับคู่บิลเจ้าชะตา)\n\n"
                    ."👉 โอนตามจำนวนนี้ผ่าน QR ที่ส่งให้ — บิลหมดอายุใน 30 นาที\n\n"
                    ."💚 *กรุณาโอนให้ตรง ตรงจุดทศนิยมด้วย*\n"
                    ."เพื่อเปิดไพ่ยิปซี 10 ใบ ทีละใบ เมื่อครบแล้วจึงเริ่มถาม\n"
                    .'ถามได้ทุกเรื่องในช่วงเวลานี้ค่ะ ✨',
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
        // 💳 (2026-05-14) ลูกค้ารอจ่าย Celtic แต่ขอเลขบัญชี/QR — ส่งช่องทางทันที ไม่ปิดบิล
        if (method_exists($this, 'maybePresentPaymentInfo')) {
            if ($paymentInfo = $this->maybePresentPaymentInfo($messageText)) {
                return $paymentInfo;
            }
        }

        // 🔓 ยกเลิก
        // 🩹 (2026-05-08 audit fix CRIT-1b) — route ผ่าน closeAllActiveConversations
        //   เพื่อ cancel UPA Celtic + FCM push + wisdom DM ครบ
        if ($this->matchesExactKeyword($messageText, ['ยกเลิก', 'cancel', 'stop', 'ไม่จ่าย'])) {
            $userId = $reading->facebook_user_id ?: ($reading->line_user_id ?: $reading->platform_user_id);
            if (! empty($userId) && method_exists($this, 'closeAllActiveConversations')) {
                $this->closeAllActiveConversations($userId);
            } else {
                $reading->update(['conversation_status' => FortuneReading::STATUS_COMPLETED]);
            }

            return [
                'action' => 'celtic_cancelled',
                'message' => "ยกเลิก Celtic Cross แล้วค่ะ — ไม่เป็นไรนะคะ\n\n"
                    ."หากต้องการดูใหม่ พิมพ์ 'celtic cross' หรือ 'ไพ่ยิปซีเต็ม' ได้เลย 🔮",
                'reading' => $reading,
            ];
        }

        // เช็คว่าจ่ายแล้วยัง — ถ้าจ่ายแล้ว transition ทันที (ป้องกันลูกค้าค้าง)
        $reading->refresh();
        if ($reading->is_paid) {
            // 🚀 (2026-05-16) Auto-pick first card ถ้าลูกค้าพิมพ์ "พร้อม" ขณะ state race
            //   เคส: SMS match → state update CELTIC_PICKING (commit ใน DB)
            //         แต่ลูกค้าพิมพ์ "พร้อม" ก่อน push prompt ถึงเครื่อง
            //         → webhook อ่าน state เก่า CELTIC_PENDING_PAYMENT
            //   ก่อน fix: ส่ง prompt "พร้อม" → ลูกค้าต้องพิมพ์ "พร้อม" ซ้ำ → 2 รอบ
            //   ใหม่: ถ้าลูกค้าพิมพ์ ready keyword อยู่แล้ว → transition + pick ทันที (1 รอบ)
            $reading->update(['conversation_status' => FortuneReading::STATUS_CELTIC_PICKING]);
            if (method_exists($this, 'matchesCelticReadyKeyword')
                && $this->matchesCelticReadyKeyword($messageText)) {
                return $this->handleCelticPicking($reading->fresh(), $messageText);
            }

            return $this->onCelticPaymentConfirmed($reading);
        }

        $payAmount = $reading->amount_paid
            ? number_format((float) $reading->amount_paid, 2)
            : '99.00';

        // 🛑 (2026-05-24) Soft decline detector — ลูกค้าบอก "ไม่พร้อม/ไว้คราวหน้า"
        //   User spec: "ฟังเขาไม่ใช่ส่งซ้ำเหมือนอยากขาย — ยกเลิกถ้าเขาบอกไม่พร้อม"
        //   ⚠️ ต้องเช็ค looksLikeNeedPaymentHelp ก่อน — กัน false-cancel "โอนไม่เป็น"
        if (method_exists($this, 'looksLikeSoftDeclineDuringPayment')
            && method_exists($this, 'looksLikeNeedPaymentHelp')
            && ! $this->looksLikeNeedPaymentHelp($messageText)
            && $this->looksLikeSoftDeclineDuringPayment($messageText)
            && method_exists($this, 'executeCancelAndReturnToChat')) {
            \Illuminate\Support\Facades\Log::info('Fortune: soft decline detected (celtic_pending_payment) → cancel + ขอบคุณ', [
                'reading_id' => $reading->id,
                'text_preview' => mb_substr($messageText, 0, 60),
            ]);

            return $this->executeCancelAndReturnToChat($reading, 'soft_decline');
        }

        // 🔄 ลูกค้าพิมพ์ "ดูดวง" / "เริ่มใหม่" — แจ้งสถานะรอจ่ายให้ชัด
        if ($this->looksLikeFortuneRestartRequest($messageText)) {
            return [
                'action' => 'celtic_pending_payment_hint',
                'message' => "🌙 เจ้าชะตาเลือกแพคเกจไพ่ยิปซีเต็มสำรับ 99 บาทไว้แล้วนะคะ\n\n"
                    ."💸 ตอนนี้รอเจ้าชะตา *โอนค่าครู {$payAmount} บาท* ตาม QR ที่ส่งให้\n"
                    ."(ต้องโอนทศนิยมตรงเป๊ะ ระบบใช้ทศนิยมจับคู่บิล)\n\n"
                    ."──────────────────────\n"
                    ."✅ หลังโอนเสร็จ — พิมพ์ *\"โอนแล้ว\"* ระบบจะเช็คให้\n"
                    .'❌ หรือพิมพ์ *"ยกเลิก"* เพื่อไม่ดูแล้ว',
                'reading' => $reading,
            ];
        }

        // 🩹 (2026-05-07 review C1) Bill Psychology สำหรับ Celtic pending bills
        //   หาก Pro key + bill_psychology_enabled → คุยกับลูกค้าแบบจิตวิทยา
        //   ไม่งั้น fallback เป็น message เดิม
        //
        // 🌧️ (2026-05-22) เพิ่ม looksLikeCustomerExcuseOrLifeUpdate —
        //   ลูกค้าพิมพ์ "ไฟดับ/รอแป๊บ/ไม่มีเงิน/แบตหมด" — bot จะรับฟัง ไม่ใช่ส่ง QR ซ้ำเดิม
        //   เคสจริง FB: ลูกค้าโกรธ "พอรอโอนแล้ว ส่งแต่แบบนี้ไม่ฟังที่ลูกค้าบอกเลย"
        $aiPrefix = '';
        $shouldTriggerAi = method_exists($this, 'looksLikeMetaOrChitchat')
            && (
                $this->looksLikeMetaOrChitchat($messageText)
                || (method_exists($this, 'looksLikeCustomerExcuseOrLifeUpdate')
                    && $this->looksLikeCustomerExcuseOrLifeUpdate($messageText))
            );

        if ($shouldTriggerAi && method_exists($this, 'tryBillPsychologyResponse')) {
            try {
                $platform = $reading->platform ?? (preg_match('/^U[0-9a-f]{32}$/i', $reading->facebook_user_id ?? '') ? 'line' : 'facebook');
                $platformUserId = $reading->facebook_user_id ?? $reading->line_user_id ?? '';

                if (! empty($platformUserId)) {
                    // คำนวณ remainingMinutes สำหรับ Celtic (UPA expires_at)
                    $upa = $reading->uniquePaymentAmount;
                    $remainingMinutes = $upa && $upa->expires_at
                        ? max(0, (int) now()->diffInMinutes($upa->expires_at, false))
                        : 30;

                    $billProResponse = $this->tryBillPsychologyResponse(
                        $platform,
                        $platformUserId,
                        $messageText,
                        $reading,
                        $remainingMinutes
                    );

                    if (! empty($billProResponse)) {
                        $aiPrefix = $billProResponse."\n\n";
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Celtic: Bill Psychology in handleCelticPendingPayment ล้มเหลว', [
                    'error' => $e->getMessage(),
                    'reading_id' => $reading->id,
                ]);
            }

            // 🩹 (2026-05-22) Fallback — Bill Psychology ไม่ทำงาน (no Pro key / budget block / fail)
            //   ใช้ buildPendingPaymentNudge (เบากว่า ใช้ chat_ai_api_key)
            //   เดิม Celtic ไม่มี layer นี้ ทำให้แย่กว่า handlePendingPayment ของ Deep 39฿
            if (empty($aiPrefix) && method_exists($this, 'buildPendingPaymentNudge')) {
                try {
                    $upa = $reading->uniquePaymentAmount;
                    $remainingMinutes = $upa && $upa->expires_at
                        ? max(0, (int) now()->diffInMinutes($upa->expires_at, false))
                        : 30;

                    $nudge = $this->buildPendingPaymentNudge($reading, $messageText, $remainingMinutes);
                    if (! empty($nudge)) {
                        $aiPrefix = $nudge."\n\n";
                    }
                } catch (\Throwable $e) {
                    Log::warning('Celtic: buildPendingPaymentNudge fallback ล้มเหลว', [
                        'error' => $e->getMessage(),
                        'reading_id' => $reading->id,
                    ]);
                }
            }
        }

        // ยังไม่จ่าย — ตอบเตือนเรื่องจ่ายเงิน (พร้อม AI prefix ถ้ามี)
        $message = $aiPrefix;
        if (empty($aiPrefix)) {
            $message .= "💸 รอเจ้าชะตาโอนค่าครู {$payAmount} บาทตาม QR ที่ส่งให้นะคะ\n\n"
                ."📌 หลังโอนเสร็จ หมอจะรู้อัตโนมัติแล้วเปิดไพ่ให้\n"
                ."📌 พิมพ์ 'ยกเลิก' ถ้าไม่ต้องการต่อ";
        } else {
            // มี AI prefix แล้ว — เก็บแค่ payment summary สั้น ๆ
            $message .= "💸 *ค่าครู: {$payAmount} บาท* (ทศนิยมต้องตรง)\n"
                .'📌 พิมพ์ "เช็คสถานะ" เมื่อโอนแล้ว · "ยกเลิก" เพื่อไม่ทำต่อ';
        }

        return [
            'action' => 'celtic_awaiting_payment',
            'message' => $message,
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
        // 🆘 (2026-05-16) Status inquiry — ลูกค้าไม่เห็นไพ่ที่เปิดไปแล้ว (LINE message lost)
        //   user report: "ลูกค้าพิมพ์ แล้วมีการเปิดไพ่ แต่มันไม่ส่งข้อความกลับมาให้เห็นที่ไลน์
        //                  ลูกค้างง ว่าถึงไหนแล้ว"
        //   ก่อน fix: matchesCelticReadyKeyword=false → fall to chitchat reminder
        //         → ลูกค้าได้แต่ "พิมพ์ พร้อม" — ไม่รู้ตอนนี้เปิดถึงใบไหน
        //   ใหม่: detect "ไม่เห็น/ภาพไม่ขึ้น/ถึงไหน/ใบที่เท่าไหร่" → resume message + รูปใบล่าสุด
        if ($this->looksLikeCelticStatusInquiry($messageText)) {
            return $this->buildCelticStatusRecovery($reading);
        }

        // 🔓 ยกเลิก / เริ่มใหม่ (anti-fraud: ก่อน Q1 ตอบ → สับใหม่ได้ 1 ครั้ง/บิล)
        // 🆕 (2026-05-17) Limit สับใหม่เหลือ 1 ครั้ง — กันลูกค้าสับจนได้ไพ่ที่ชอบ
        //   ดู CelticCrossService::resetPickedCards (throw พร้อมข้อความที่ส่งกลับลูกค้า)
        if ($this->matchesExactKeyword($messageText, ['เริ่มใหม่', 'restart', 'reset', 'สับใหม่'])) {
            try {
                app(CelticCrossService::class)->resetPickedCards($reading);

                return [
                    'action' => 'celtic_reset',
                    'message' => "🔄 สับไพ่ใหม่เรียบร้อย — ไพ่ที่เคยเลือกถูกล้างแล้ว\n"
                        ."⚠️ *หมายเหตุ: สับใหม่ได้ครั้งเดียวต่อบิล* — โควต้าหมดแล้วนะคะ\n\n"
                        ."ตอนนี้ตั้งจิตให้แน่วแน่ แล้วเลือกใหม่อย่างมั่นใจค่ะ\n\n"
                        .$this->buildCelticPickPromptText($reading->fresh()),
                    'reading' => $reading,
                ];
            } catch (\Exception $e) {
                return [
                    'action' => 'celtic_reset_denied',
                    'message' => '❌ '.$e->getMessage(),
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
            $canShuffle = $reading->canShuffleCelticAgain();
            $shuffleLine = $canShuffle
                ? "🔄 หรือพิมพ์ *\"สับใหม่\"* เพื่อสับไพ่ใหม่ (ได้ครั้งเดียว — ยังไม่จ่ายซ้ำ)\n"
                : "🔄 สับใหม่ใช้ครบโควต้า 1 ครั้งแล้ว — เปิดไพ่ต่อได้เลยค่ะ\n";

            return [
                'action' => 'celtic_restart_hint',
                'message' => "🌙 เจ้าชะตาอยู่ในรอบดูดวงไพ่ยิปซีอยู่แล้วนะคะ\n\n"
                    ."🃏 ตอนนี้เปิดไพ่ไปแล้ว *{$picked}/10 ใบ* — ใบถัดไปคือใบที่ {$next}\n\n"
                    ."──────────────────────\n"
                    ."👉 พิมพ์ *\"พร้อม\"* เพื่อเปิดไพ่ใบถัดไป\n"
                    .$shuffleLine
                    .'❌ พิมพ์ *"ยกเลิก"* ถ้าไม่อยากดูแล้ว',
                'reading' => $reading,
            ];
        }

        // chitchat → ย้ำขั้นตอน (ข้ามได้ถ้า explicit pick keyword)
        if (! $isExplicitPick && $this->looksLikeMetaOrChitchat($messageText)) {
            return [
                'action' => 'celtic_chitchat_reminder',
                'message' => "🃏 ตอนนี้อยู่ขั้นเปิดไพ่นะคะ\n\n"
                    .$this->buildCelticPickPromptText($reading)
                    ."\n\nเจ้าชะตาแค่พิมพ์ 'พร้อม' เพื่อให้หมอสุ่มไพ่ใบถัดไปค่ะ",
                'reading' => $reading,
            ];
        }

        // 📊 (2026-05-04) Diagnostic log — ทำให้ debug stuck-at-card-N ในอนาคตง่าย
        Log::info('🃏 Celtic: handleCelticPicking → attempt pickNextCard', [
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
            Log::warning('🃏 Celtic: pickNextCard ล้มเหลว', [
                'reading_id' => $reading->id,
                'next_position' => $reading->getNextCelticPosition(),
                'failure_message' => $result['message'] ?? null,
            ]);

            // 🛟 (2026-05-04) ใส่ Quick Reply ในข้อความ failure ด้วย — ไม่ให้ลูกค้าค้าง
            //    เปลี่ยน action เป็น celtic_chitchat_reminder ที่ ChannelManager มี QR อยู่แล้ว
            $picked = $reading->getCelticPickedCount();
            $next = $reading->getNextCelticPosition() ?? '?';

            $recoveryHint = $reading->canShuffleCelticAgain()
                ? "\n\nหากกดแล้วไม่หาย ลองพิมพ์ *'สับใหม่'* เพื่อรีเซ็ตไพ่ (ได้ครั้งเดียวต่อบิล — ไม่ต้องจ่ายซ้ำ)"
                : "\n\nลองพิมพ์ 'พร้อม' อีกครั้ง หรือพิมพ์ 'ยกเลิก' ถ้าต้องการออกจากรอบนี้";

            return [
                'action' => 'celtic_chitchat_reminder',
                'message' => "⚠️ สุ่มไพ่ใบที่ {$next} ไม่สำเร็จ — ลองพิมพ์ 'พร้อม' หรือกดปุ่มข้างล่างอีกครั้งนะคะ\n\n"
                    ."🃏 เปิดไพ่ไปแล้ว *{$picked}/10 ใบ*\n\n"
                    .($result['message'] ?? '')
                    .$recoveryHint,
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
            ."ได้ไพ่ *{$cardNameTh}* {$reversed}\n"
            ."({$cardNameEn})\n\n"
            ."📖 ความหมายไพ่นี้: {$meaning}";

        // ครบ 10 ใบหรือยัง
        if ($result['is_complete']) {
            return $this->onCelticAllCardsPicked($reading, $message, $imageUrl);
        }

        // ขยับไป position ถัดไป
        $reading->refresh();
        $nextPrompt = $this->buildCelticPickPromptText($reading);
        $message .= "\n\n──────────────────────\n".$nextPrompt;

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
            ."💭 ตำแหน่งนี้บอกถึง: {$desc}\n\n"
            ."🧘 ตั้งจิต หลับตา 3 วินาที นึกถึงสิ่งที่อยากรู้\n"
            ."เมื่อพร้อมแล้ว พิมพ์ 'พร้อม' เพื่อให้หมอเปิดไพ่ใบนี้ค่ะ\n\n"
            ."📌 *ต้องเปิดครบ 10 ใบก่อน แม่หมอจึงเริ่มทำนาย*\n"
            ."   เปิดไปแล้ว {$picked}/10 — เหลืออีก *{$remaining} ใบ*";
    }

    /**
     * เลือกครบ 10 ใบ → สร้างภาพ composite → เข้าโหมดถาม Q1
     */
    protected function promptNextCelticCard(FortuneReading $reading): array
    {
        return [
            'action' => 'celtic_pick_prompt',
            'message' => "✅ ค่าครูเข้าระบบแล้ว ขอบคุณค่ะ\n\n"
                ."🔮 *ดูดวง Celtic Cross เริ่มเลย*\n"
                ."🃏 หมอจะเปิดไพ่ให้ทีละใบ ทั้งหมด *10 ใบ*\n"
                ."📌 ต้องเปิดครบทั้ง 10 ใบก่อน — แม่หมอจึงจะเริ่มทำนายและให้เจ้าชะตาถามคำถามได้\n\n"
                ."──────────────────────\n"
                .$this->buildCelticPickPromptText($reading),
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
            ."📋 เลขบิล: {$billRef}\n"
            ."💰💰 ยอดที่ต้องโอน: *{$payAmount}* บาท 💰💰\n"
            ."⏳ เหลือเวลา: {$remainingMin} นาที\n\n"
            ."═══════════════════════\n\n"
            ."⚠️ *โอนยอดให้ตรงเป๊ะ {$payAmount} บาท* (ทศนิยมด้วย!)\n"
            ."✅ โอนแล้ว ระบบตัดบิลอัตโนมัติ → เริ่มเปิดไพ่ทันที\n"
            ."❌ พิมพ์ *\"ยกเลิก\"* ถ้าไม่ต้องการต่อ\n\n"
            .'💡 ไม่ต้องสร้างบิลใหม่ — ใช้บิลนี้โอนเลยค่ะ';

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
                ."💚 ค่าครูเดิมยังใช้ได้ — ไม่ต้องจ่ายซ้ำ\n"
                ."📋 เลขบิล: {$billRef}\n\n"
                ."═══════════════════════\n\n";
        } else {
            $header = "✨ *พบบิลของคุณ{$name}ที่ยังใช้สิทธิ์ไม่ครบ*\n"
                ."💚 ค่าครูเดิมยังใช้ได้ — ไม่ต้องจ่ายซ้ำ\n"
                ."📋 เลขบิล: {$billRef}\n\n"
                ."═══════════════════════\n\n";
        }

        switch ($reading->conversation_status) {
            case FortuneReading::STATUS_CELTIC_PICKING:
                if ($picked === 0) {
                    $body = "🔮 *เริ่มเปิดไพ่ Celtic Cross กันเลย*\n"
                        ."หมอจะเปิดไพ่ให้ทีละใบ พร้อมตำแหน่งที่ได้\n\n"
                        ."──────────────────────\n"
                        .$this->buildCelticPickPromptText($reading);
                } else {
                    $body = "🃏 เปิดไพ่ไปแล้ว *{$picked}/10 ใบ* — เริ่มต่อกันค่ะ!\n\n"
                        ."──────────────────────\n"
                        .$this->buildCelticPickPromptText($reading);
                }

                return [
                    'action' => 'celtic_pick_prompt',
                    'message' => $header.$body,
                    'reading' => $reading,
                ];

            case FortuneReading::STATUS_CELTIC_AWAITING_QUESTION:
                // 🌙 (2026-05-23 v3) เริ่มต้น / resume — ประกาศกติกาให้ชัด (5 คำถาม / 15 นาที)
                //    + ถ้าเริ่มถามแล้ว — แสดง "เหลือ X คำถาม / Y นาที"
                $qaWindow = (int) ($this->settings->celtic_cross_qa_window_minutes ?? 15);
                $maxQ = (int) ($this->settings->celtic_cross_max_questions ?? 5);
                $usedQ = (int) ($reading->celtic_questions_used ?? 0);
                $remainingMin = $reading->getCelticQaRemainingMinutes();

                if ($usedQ === 0) {
                    // ยังไม่ถามครั้งแรก
                    $qLine = $maxQ > 0
                        ? "❓ ถามได้ *{$maxQ} คำถาม* ภายใน *{$qaWindow} นาที*\n"
                        : "⏳ คุยกันได้ภายใน *{$qaWindow} นาที*\n";
                    $body = "🌟 *เปิดไพ่ครบ 10 ใบแล้ว — แม่หมอพร้อมรับฟังเจ้าชะตา*\n\n"
                        ."💬 *พิมพ์คำถามแรกได้เลยค่ะ* ✨\n\n"
                        .$qLine
                        .'⚡ ตอบทันที ไม่มีรอ';
                } else {
                    // ถามต่อ — แสดงเหลือเท่าไหร่
                    $remainingQ = $maxQ > 0 ? max(0, $maxQ - $usedQ) : null;
                    $qLine = $remainingQ !== null
                        ? "❓ เหลือถามได้อีก *{$remainingQ} คำถาม* (จากทั้งหมด {$maxQ})\n"
                        : '';
                    $timeLine = $remainingMin !== null
                        ? "⏳ เหลือเวลา *{$remainingMin} นาที* (จากทั้งหมด {$qaWindow})"
                        : "⏳ คุยได้ภายใน *{$qaWindow} นาที*";
                    $body = "🌟 *แม่หมอพร้อมรับคำถามต่อไป*\n\n"
                        ."💬 *พิมพ์คำถามได้เลยค่ะ* — หรือกด *\"📜 เลิกทำนายและสรุปผล\"* เมื่อพร้อม ✨\n\n"
                        .$qLine
                        .$timeLine;
                }

                return [
                    'action' => 'celtic_resume_qa',
                    'message' => $header.$body,
                    'reading' => $reading,
                ];

            case FortuneReading::STATUS_CELTIC_GENERATING:
                $body = "🌌 แม่หมอกำลังพิจารณาไพ่ทั้ง 10 ใบให้เจ้าชะตาอยู่...\n"
                    .'กรุณารอสักครู่ (~30-60 วินาที) ✨';

                return [
                    'action' => 'celtic_processing',
                    'message' => $header.$body,
                    'reading' => $reading,
                ];

            case FortuneReading::STATUS_CELTIC_QA_PROMPT:
                // 🛑 (2026-05-16) เอาปุ่ม "ถามต่อ" ออก — พิมพ์คำถามมาได้เลย
                $body = "💬 *อยากถามอะไรต่อก็พิมพ์มาได้เลยค่ะ*\n\n"
                    ."👉 พิมพ์คำถามที่อยากรู้ — แม่หมอจะอ่านพลังงานให้\n"
                    .'📜 หรือกด *"เลิกทำนายและสรุปผล"* เมื่อพร้อมจบรอบ';

                return [
                    'action' => 'celtic_qa_prompt_resume',
                    'message' => $header.$body,
                    'reading' => $reading,
                ];

            default:
                // Fallback — ถือว่าเริ่มใหม่
                $body = '🔮 พิมพ์ *"พร้อม"* เพื่อเปิดไพ่ใบถัดไปค่ะ';

                return [
                    'action' => 'celtic_pick_prompt',
                    'message' => $header.$body,
                    'reading' => $reading,
                ];
        }
    }

    /**
     * State Transition: เลือกครบ 10 ใบ → สร้างภาพ composite → ถาม Q1
     *
     * 🌙 (2026-05-08 v3) Pro Session — เปิด Hard Session อวตารแม่หมอ Premium ทันที
     *   AI Pro (sensitive key) เข้ามาดูแลเจ้าชะตาตลอด 30 นาทีนับจากนี้
     *   ระบบอื่นๆ block ทั้งหมด — ออกได้ผ่าน "พอแค่นี้/ขอบคุณ"+confirm หรือหมดเวลา
     */
    protected function onCelticAllCardsPicked(FortuneReading $reading, string $lastCardMessage, ?string $lastCardImage = null): array
    {
        // ขยับ state เข้า chat session
        $reading->update(['conversation_status' => FortuneReading::STATUS_CELTIC_AWAITING_QUESTION]);

        // 🌙 (2026-05-08 v3) เปิด Pro Session — อวตารแม่หมอเข้ามารับช่วง 30 นาที
        try {
            $this->enterProSession($reading, 'celtic');
        } catch (\Throwable $proErr) {
            \Log::warning('Celtic ProSession: enter ล้มเหลว (non-blocking)', [
                'reading_id' => $reading->id,
                'error' => $proErr->getMessage(),
            ]);
        }

        // 🆕 (2026-05-14) AI initiates conversation
        //   user spec: "เมื่อเปิดไพ่ครบ ให้ AI ถามเลยคุยกับ user เลย ให้เริ่มถาม"
        //   เดิม: ส่ง static text "เล่าให้แม่หมอฟัง" + รอ user พิมพ์ก่อน
        //   ใหม่: call AI ด้วย sentinel "__OPENING_GREETING__" → AI ทักทาย+ชวนเล่าเรื่อง
        //         + เซ็ต celtic_first_answered_at เพื่อ start QA window
        $qaWindow = (int) ($this->settings->celtic_cross_qa_window_minutes ?? 15);
        $name = $reading->resolveCustomerName();

        // 🔔 (2026-05-14) AI Ping — Loading update 10s/30s/60s + admin alert > 1 min
        //   เปิด AI session ก่อน call AI — pings วิ่ง async ใน queue worker
        //   ถ้า AI เสร็จก่อน 10s → ping ทั้งหมด skip (cache cleared)
        $platform = ! empty($reading->facebook_user_id) ? 'facebook' : 'line';
        $userId = $reading->facebook_user_id ?: $reading->line_user_id;
        if ($userId) {
            \App\Services\Fortune\FortuneAiPingDispatcher::start(
                $reading->id,
                $platform,
                $userId,
                'celtic-opening'
            );
        }

        $service = app(CelticCrossService::class);
        try {
            $openingResult = $service->generateOpeningGreeting($reading);
        } finally {
            // ปิด AI session — pings ที่ยังไม่ run จะ skip
            if ($userId) {
                \App\Services\Fortune\FortuneAiPingDispatcher::complete($reading->id);
            }
        }

        $openingText = $openingResult['success']
            ? trim($openingResult['response'])
            // fallback ถ้า AI fail — แม่หมอทักทายแบบ static (กัน UX แตก)
            : "🌙✨ *แม่หมอจันทราพร้อมแล้วค่ะ คุณ{$name}* ✨🌙\n\n"
                ."🃏 ไพ่ทั้ง 10 ใบของเจ้าชะตาเปิดออกแล้ว — แม่หมอเห็นพลังงานที่ห่อหุ้มเจ้าชะตาอยู่\n\n"
                .'💬 เล่าให้แม่หมอฟังได้เลย — ตอนนี้มีเรื่องอะไรคาใจที่สุด?';

        // 🌙 (2026-05-23 v3) Start QA window only — เก็บแค่ timestamp ไม่ bump counter
        //    เดิม: markCelticAnswered(1) → celtic_questions_used = 1 (offset 1 คำถามผิด)
        //    ใหม่: set celtic_first_answered_at ตรงๆ — ปล่อยให้ counter เริ่ม 0
        //          → คำถามแรกจริงของลูกค้า markCelticAnswered(1) → used = 1 ✅
        try {
            if (empty($reading->celtic_first_answered_at)) {
                $reading->update(['celtic_first_answered_at' => now()]);
            }
        } catch (\Throwable $e) {
            \Log::warning('Celtic: set celtic_first_answered_at after opening greeting fail', [
                'reading_id' => $reading->id,
                'error' => $e->getMessage(),
            ]);
        }

        // 🌙 (2026-05-23 v3) Footer — ประกาศกติกาให้ชัด (5 คำถาม / 15 นาที)
        //    user spec: "ต้องบอกกติการให้ชัดทุกที่"
        $maxQ = (int) ($this->settings->celtic_cross_max_questions ?? 5);
        $qLimitLine = $maxQ > 0
            ? "❓ ถามได้ *{$maxQ} คำถาม* ภายใน *{$qaWindow} นาที*\n"
            : "⏳ คุยกับแม่หมอได้ *{$qaWindow} นาที* นับจากนี้\n";

        $footer = "\n\n──────────────────────\n"
            ."💬 *แม่หมอพร้อมทำนาย พิมพ์คำถามได้เลยค่ะ* ✨\n\n"
            .$qLimitLine
            ."⚡ ตอบทันที ไม่มีรอ — พิมพ์ปุ๊บแม่หมอตอบปั๊บ\n"
            .'🖼️ ภาพไพ่จัดเรียง — แม่หมอจะส่งตอนจบเป็นที่ระลึก';

        return [
            'action' => 'celtic_all_picked',
            'message' => $lastCardMessage."\n\n──────────────────────\n\n".$openingText.$footer,
            'reading' => $reading,
            'tarot_image_url' => $lastCardImage,
        ];
    }

    /**
     * State: CELTIC_AWAITING_QUESTION
     * ลูกค้าพิมพ์คำถาม Q1, Q2, หรือ Q3
     */
    protected function handleCelticAwaitingQuestion(FortuneReading $reading, string $messageText): array
    {
        $question = trim($messageText);

        // 🔚 (2026-05-23) ลูกค้าขอจบ → 2-step confirm กันมือลั่น
        //    user spec: "ปุ่มยุติทำนายเปลี่ยนเป็น เลิกทำนายและสรุปผล แทน และถามก่อนว่าจะเลิกแล้วสรุปเลย
        //                 จริงไหม เพราะบางคนมือไปกดผิด"
        //    Helper return:
        //      - array = handled (ส่ง confirm prompt / yes → end / no → continue)
        //      - null  = ไม่เกี่ยว — pass through ลงไปทำงานต่อ
        $confirmResult = $this->handleCelticEndConfirmation($reading, $messageText);
        if ($confirmResult !== null) {
            return $confirmResult;
        }

        // 🆕 (2026-05-13) Free Chat mode — ลบ Q1/Q2/Q3 + ลบ predict-now button
        //   user spec: "ไม่ต้องมีการทำนาย ให้หมอเริ่มบริบทชวนคุย"
        //   ทุก message ของลูกค้า (สั้น/ยาว, เล่าเรื่อง/ถามคำถาม) → AI ตอบแบบ chat
        //   ไม่มี short-message reject, ไม่มี max_questions enforce, ไม่มี predict-now keyword

        // 🔄 ลูกค้าพิมพ์ "ดูดวง" / "เริ่มใหม่" lone — ตอบ contextual ว่ายังอยู่ในรอบ
        if ($this->looksLikeFortuneRestartRequest($messageText)) {
            $remainingMin = $reading->getCelticQaRemainingMinutes();
            // 🌙 (2026-05-24) ใช้ dynamic qa window — เดิม hardcode "30 นาที"
            //   ปัจจุบันสเปคใหม่ 15 นาที (Session 2026-05-23 #7) — admin override ได้
            $qaWindow = app(CelticCrossService::class)->getQaWindowMinutes();
            $timeHint = $remainingMin !== null && $remainingMin > 0
                ? "⏳ คุยกับแม่หมอได้อีก {$remainingMin} นาที"
                : "⏳ คุยกับแม่หมอได้ภายใน {$qaWindow} นาทีนับจากเปิดไพ่";

            return [
                'action' => 'celtic_already_in_session',
                'message' => "🌙 เจ้าชะตาอยู่ในรอบคุยกับแม่หมออยู่แล้วนะคะ\n\n"
                    ."💬 เล่าเรื่องที่ค้างคาใจมาได้เลย — แม่หมอพร้อมรับฟัง\n\n"
                    .$timeHint."\n"
                    // 🌙 (2026-05-23) เปลี่ยน "ยุติการทำนาย" → "เลิกทำนายและสรุปผล" + 2-step confirm
                    .'📜 หรือกด *"เลิกทำนายและสรุปผล"* เมื่อพร้อมจบรอบ',
                'reading' => $reading,
            ];
        }

        // เช็ค time window (นับจาก first message)
        if (! $reading->canAskMoreCeltic()) {
            return $this->endCelticSession($reading, 'time_expired');
        }

        // 🛑 (2026-05-13) ลบ max_questions enforcement — flow ใหม่เป็น free chat
        //   user spec: คุยเรื่อยๆ จนถึง time_expired หรือ "พอแค่นี้"

        // 🌙 (2026-05-29) Single-bot — ลบ Message Debounce buffer (ProcessBufferedCelticMessageJob)
        //   user spec: "ใช้ตัวเดียวคุยเลย เหมือนพูดคุยกับหมอ แล้วแยกแยะคำถาม สรุปคำถาม"
        //   เดิม (2026-05-20 Phase 4a): Q2+ (celtic_questions_used >= 1) → buffer 3 วิ + dispatch
        //     ProcessBufferedCelticMessageJob (job แยก) → flush + AI ตอบทีหลัง
        //     = path คนละทางกับ Q1 (immediate) → flow ไม่สม่ำเสมอ + เสี่ยง delivered_at ไม่ถูก mark
        //       (เคสจริง reading 4191 สมร: Q2 ครอบครัวถูก redeliver ซ้ำ)
        //   ใหม่: ทุกคำถามไป immediate path เดียวด้านล่าง (askQuestion sync) — หมอคนเดียวตอบสด
        //     สม่ำเสมอ + ChannelManager mark delivered ครบทุกข้อ
        //   หมายเหตุ: ProcessBufferedCelticMessageJob ยังคงไฟล์ไว้ (เผื่อ job ค้างใน queue) แต่ไม่ dispatch ใหม่

        // ส่งให้ AI Pool — ทุก message ส่งเข้า askQuestion (chat-style follow-up)
        $reading->update(['conversation_status' => FortuneReading::STATUS_CELTIC_GENERATING]);

        // 🌙 (2026-05-14) Pre-reply "กำลังคิด" — ส่ง push message ทันทีก่อน AI call
        //   user report: "AI เหมือนจะตอบแต่เงียบ"
        //   เคสจริง: AI ใช้เวลา 30-60+ วินาที (OpenAI) → ลูกค้ารอนาน ไม่เห็น feedback
        //   ปัญหา: webhook return 200 OK ทันที แต่ลูกค้าไม่เห็นข้อความ "กำลังคิด"
        //   Fix: push intermediate ack ทันที — ลูกค้าเห็นว่า bot ยอมรับคำถามแล้ว
        try {
            $this->sendCelticThinkingAck($reading);
        } catch (\Throwable $ackErr) {
            \Log::debug('Celtic: send thinking ack fail (non-blocking)', [
                'reading_id' => $reading->id,
                'error' => $ackErr->getMessage(),
            ]);
        }

        // 🔔 (2026-05-14) AI Ping — Loading update 10s/30s/60s + admin alert > 1 min
        //   ต่อจาก thinking ack ทันที — pings วิ่ง async ใน queue worker
        $platform = ! empty($reading->facebook_user_id) ? 'facebook' : 'line';
        $userId = $reading->facebook_user_id ?: $reading->line_user_id;
        if ($userId) {
            \App\Services\Fortune\FortuneAiPingDispatcher::start(
                $reading->id,
                $platform,
                $userId,
                'celtic-question'
            );
        }

        // 🛡️ (2026-05-20 hotfix) State recovery — ถ้า askQuestion throw exception
        //   → state ค้างที่ GENERATING → IN-PREDICTION Guard silent_skip ทุกข้อความตามมา
        //   → บอทเงียบตลอดกาล ลูกค้าจ่าย 99฿ แล้วใช้ไม่ได้
        //   Fix: finally block restore state เป็น AWAITING_QUESTION ทุกกรณี (ถ้าไม่สำเร็จ)
        $service = app(CelticCrossService::class);
        $result = ['success' => false, 'message' => 'AI ระบบขัดข้องชั่วคราว ลองอีกครั้งค่ะ'];
        $exceptionThrown = null;
        try {
            $result = $service->askQuestion($reading, $question);
        } catch (\Throwable $askErr) {
            // เก็บไว้ log/return หลัง finally — ห้าม throw ต่อ (จะทำให้ state stuck)
            $exceptionThrown = $askErr;
            \Log::error('Celtic: askQuestion exception (Q1 sync path) — state จะถูกคืน', [
                'reading_id' => $reading->id,
                'error' => $askErr->getMessage(),
                'trace' => mb_substr($askErr->getTraceAsString(), 0, 500),
            ]);
        } finally {
            // ปิด AI session — pings ที่ยังไม่ run จะ skip
            if ($userId) {
                \App\Services\Fortune\FortuneAiPingDispatcher::complete($reading->id);
            }

            // 🚨 State recovery — ถ้า exception หรือ success=false → คืน state ทันที
            //   ไม่งั้น IN-PREDICTION Hard Guard จะ silent_skip ทุก message ตามมา
            if ($exceptionThrown !== null || empty($result['success'])) {
                try {
                    $reading->update(['conversation_status' => FortuneReading::STATUS_CELTIC_AWAITING_QUESTION]);
                } catch (\Throwable $stateErr) {
                    \Log::critical('Celtic: state recovery FAILED — reading stuck!', [
                        'reading_id' => $reading->id,
                        'error' => $stateErr->getMessage(),
                    ]);
                }
            }
        }

        if ($exceptionThrown !== null) {
            return [
                'action' => 'celtic_ai_failed',
                'message' => '⚠️ AI ระบบขัดข้องชั่วคราว ลองพิมพ์คำถามอีกครั้งค่ะ',
                'reading' => $reading,
            ];
        }

        if (! $result['success']) {
            return [
                'action' => 'celtic_ai_failed',
                'message' => '⚠️ '.($result['message'] ?? 'AI ระบบขัดข้องชั่วคราว ลองอีกครั้งค่ะ'),
                'reading' => $reading,
            ];
        }

        $reading->refresh();
        $sequence = $result['sequence'];
        $wantsEnd = (bool) ($result['wants_end'] ?? false);

        // 🛡️ (2026-05-05) DISABLE ai_signal early-end ตาม user spec
        //   user 2026-05-05: "สำคัญอย่าสรุปก่อนลูกค้าถามคำถามสุดท้ายต้องสรุปที่คำถามสุดท้าย"
        //   เดิม: wants_end จาก AI → end session ตั้งแต่ Q2 → ลูกค้าโดนตัดก่อนถามครบ
        //   ใหม่: AI's wants_end ignore ทั้งหมด — ลูกค้าตัดสินใจเอง (พิมพ์ "ยุติการทำนาย") หรือ time_expired
        if ($wantsEnd) {
            \Log::info('Celtic: AI signaled wants_end — ignored (user spec: free chat ภายในเวลา)', [
                'reading_id' => $reading->id,
                'sequence' => $sequence,
            ]);
        }

        // 🃏 (2026-05-16) Off-topic detection — AI signal [OFF_TOPIC_REPICK]
        //   user spec: "ใครที่ถามคำถามเริ่มเยอะเกิน 5 คำถาม ที่เริ่มไม่เกี่ยวเรื่องเดิม
        //               แม่หมอต้องพยายามให้จับไพ่ใหม่ (จบการทำนายนี้ แล้วให้จ่ายใหม่)
        //               เพราะไพ่ชุดเดียวอาจตอบได้ไม่ตรงกับคำถามที่ต่างออกไป
        //               ให้จับใหม่ และต้องออกจากการสนทนาก่อน"
        //
        //   Detection: AI ใส่ token [OFF_TOPIC_REPICK] ที่ท้ายข้อความ (จาก prompt Q6+)
        //   strip token จาก response + exit session ด้วย reason 'off_topic_repick'
        //   Pattern กว้างเผื่อ AI แต่งรูปแบบ — รับ space / underscore / hyphen / dot ระหว่างคำ
        $offTopicPattern = '/\[\s*OFF[_\s.-]?TOPIC[_\s.-]?REPICK\s*\]/iu';
        $aiResponse = (string) ($result['response'] ?? '');
        if (preg_match($offTopicPattern, $aiResponse)) {
            $aiResponse = trim(preg_replace($offTopicPattern, '', $aiResponse));

            \Log::info('Celtic: AI signaled off-topic → ชวนจับไพ่ใหม่ + exit', [
                'reading_id' => $reading->id,
                'sequence' => $sequence,
            ]);

            // ส่ง response สั้นๆ + closing message ชวนจับไพ่ใหม่ + เหตุผลที่ต้องจับใหม่
            //   user spec 2026-05-16: "ต้องให้เหตุผลว่า ไพ่ชุดเดิม อาจตอบเรื่องราวใหม่ๆ ได้ไม่ครบ"
            $repickMessage = $aiResponse
                ."\n\n──────────────────────\n"
                ."🃏 *แม่หมอเห็นว่าเจ้าชะตาเริ่มถามเรื่องใหม่ที่ต่างจากเดิม* 🌙\n\n"
                ."ไพ่ชุดเดิม (10 ใบ) ที่แม่หมอเปิดให้ — *พลังงานผูกไว้กับเรื่องที่เจ้าชะตามาดูตอนแรก*\n"
                ."ถ้าตอบเรื่องราวใหม่ๆ ที่ต่างออกไป *อาจตอบได้ไม่ครบ ไม่ลึก ไม่ตรง* เท่าที่ควรค่ะ\n\n"
                ."🔮 ถ้าอยากให้แม่หมอดูเรื่องใหม่ให้แม่นยำ — *ต้องจับไพ่ใหม่* นะคะ\n"
                ."   พิมพ์ *\"ดูดวง\"* เมื่อพร้อม แม่หมอจะเปิดประตูพลังให้ใหม่อีกครั้ง\n\n"
                .'🙏 ขอบคุณที่ไว้วางใจแม่หมอจันทรานะคะ ✨';

            // จบ session — exit Pro Session + status COMPLETED
            return $this->endCelticSession($reading, 'off_topic_repick', $repickMessage);
        }

        // 🌙 (2026-05-23 v3) ปรับแนวทางใหม่ทั้งหมด — user spec final:
        //   "ในการทำนายแบบ 99 เปลี่ยนไม่ให้มีการดีเลย์ในการตอบ
        //    แต่ให้ถาม 5 คำถาม ภายใน 15 นาที และต้องบอกกติการให้ชัดทุกที่"
        //
        //   เปลี่ยนจาก:
        //     - ❌ Silent sandbagging (Session #1) → ❌ Sequence-aware delay 0-60s (Session #2)
        //     - ❌ ห้ามประกาศ max questions
        //   มาเป็น:
        //     - ✅ ส่งทันทีทุกคำถาม (zero delay)
        //     - ✅ บังคับ 5 คำถาม + 15 นาที (hard cap ทั้งคู่)
        //     - ✅ ประกาศกติกาให้ชัดทุกข้อความ (เหลือ X คำถาม / Y นาที)

        // ดึง platform + user ID สำหรับ logging
        $platform = $reading->platform;
        if (! $platform || ! in_array($platform, ['facebook', 'line'], true)) {
            $candidateId = $reading->platform_user_id ?: $reading->facebook_user_id ?: '';
            $platform = preg_match('/^U[a-f0-9]{32}$/i', $candidateId) ? 'line' : 'facebook';
        }
        $sendUserId = $platform === 'line'
            ? ($reading->platform_user_id ?: $reading->facebook_user_id)
            : ($reading->facebook_user_id ?: $reading->platform_user_id);

        // นับ counter หลัง AI ตอบ + markCelticAnswered() (refresh ก่อน)
        $reading->refresh();
        $remainingMin = $reading->getCelticQaRemainingMinutes();
        $qaWindow = (int) ($this->settings->celtic_cross_qa_window_minutes ?? 15);
        $maxQ = (int) ($this->settings->celtic_cross_max_questions ?? 5);
        $usedQ = (int) ($reading->celtic_questions_used ?? 0);
        $remainingQ = $maxQ > 0 ? max(0, $maxQ - $usedQ) : null;

        // ⛔ (2026-05-23 v3) Hard cap — ถามครบ max แล้ว → จบ session พร้อม Grand Finale
        //    endCelticSession('max_questions_reached', $aiResponse) จะรวม
        //    คำทำนายล่าสุด + summary ฟันธงแล้วส่งให้ลูกค้า + ปิด session
        if ($maxQ > 0 && $usedQ >= $maxQ) {
            // typing_off ก่อนปิด session — เคลียร์ typing dots
            if ($sendUserId && $platform === 'facebook') {
                try {
                    $settings = \App\Models\FortuneTellingSetting::getSettings();
                    (new \App\Services\FacebookWebhookService($settings))
                        ->sendTypingIndicator($sendUserId, false);
                } catch (\Throwable $e) {
                    // ignore — non-blocking
                }
            }

            \Log::info('Celtic: max_questions_reached → end session with Grand Finale', [
                'reading_id' => $reading->id,
                'used' => $usedQ,
                'max' => $maxQ,
                'platform' => $platform,
            ]);

            return $this->endCelticSession($reading, 'max_questions_reached', $aiResponse);
        }

        // 📢 (2026-05-23 v3) Footer — ประกาศกติกาให้ชัดทุกข้อความ
        //    user spec: "ต้องบอกกติการให้ชัดทุกที่"
        $timeHint = $remainingMin !== null
            ? "⏳ เหลือเวลา *{$remainingMin} นาที* (จากทั้งหมด {$qaWindow} นาที)"
            : "⏳ คุยได้ภายใน *{$qaWindow} นาที* นับจากคำทำนายแรก";

        $qHint = '';
        if ($remainingQ !== null) {
            $qHint = $remainingQ > 0
                ? "\n❓ เหลือถามได้อีก *{$remainingQ} คำถาม* (จากทั้งหมด {$maxQ} คำถาม)"
                : "\n❓ ครบ {$maxQ} คำถามแล้ว — แม่หมอกำลังเตรียมสรุปท้ายให้";
        }

        $followupOffer = "\n\n──────────────────────\n"
            .$timeHint
            .$qHint."\n"
            .'💬 พิมพ์คำถามต่อไปได้เลย — หรือกด *"📜 เลิกทำนายและสรุปผล"* เมื่อพร้อม ✨';

        $finalMessage = $aiResponse.$followupOffer;

        // 🚀 (2026-05-23 v3) ส่งทันที — ไม่มี delay ทุกคำถาม
        //    user spec: "เปลี่ยนไม่ให้มีการดีเลย์ในการตอบ"
        $reading->update(['conversation_status' => FortuneReading::STATUS_CELTIC_AWAITING_QUESTION]);

        if ($sendUserId && $platform === 'facebook') {
            try {
                $settings = \App\Models\FortuneTellingSetting::getSettings();
                (new \App\Services\FacebookWebhookService($settings))
                    ->sendTypingIndicator($sendUserId, false);
            } catch (\Throwable $e) {
                // ignore — non-blocking
            }
        }

        \Log::info('Celtic: immediate prediction (no delay)', [
            'reading_id' => $reading->id,
            'sequence' => $sequence,
            'used' => $usedQ,
            'max' => $maxQ,
            'remaining_q' => $remainingQ,
            'remaining_min' => $remainingMin,
            'platform' => $platform,
        ]);

        return [
            'action' => 'celtic_question_answered',
            'message' => $finalMessage,
            'reading' => $reading,
            // 🐛 (2026-05-29) ส่ง sequence ให้ ChannelManager mark delivered ตรง row (กัน redeliver ซ้ำ)
            'sequence' => $sequence,
        ];
    }

    // 🛑 (2026-05-14) handleCelticPredictAll + buildPredictAllPrompt + CELTIC_PREDICT_NOW
    //   ทั้งระบบ predict-now ถูกลบออกตาม user spec —
    //   "เอาระบบ q1 2 3 ออก, เอาปุ่มทำนายเดี๋ยวนี้ออก, เมื่อเปิดไพ่ครบให้ AI ถามเลย"
    //   AI initiates หลังเปิดไพ่ครบ ผ่าน generateOpeningGreeting()
    //   ทุกข้อความถัดไป → buildFollowupPrompt (prompt เดียวกัน ไม่สน sequence)

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
     * @param  string  $reason  'ai_signal' | 'time_expired' | 'customer_said_done' | 'max_questions_reached' | 'idle' | 'off_topic_repick'
     * @param  string|null  $aiMessage  ถ้ามีคำตอบสุดท้ายจาก AI — รวมเข้ากับ summary
     */
    public function endCelticSession(FortuneReading $reading, string $reason = 'ai_signal', ?string $aiMessage = null): array
    {
        // 🔄 reset state กลับ COMPLETED → normal loop พร้อมรับ "ดูดวง" ใหม่ได้
        $reading->update(['conversation_status' => FortuneReading::STATUS_COMPLETED]);

        // 🐛 (2026-05-29) กัน redeliver cron ส่งคำตอบรายข้อซ้ำหลัง session จบ
        //   เคสจริง reading 4191 (สมร มนต์คาถา): Q2 "ครอบครัว" push แรก delivered_at ไม่ถูก set
        //   (FB confirm false-negative) → fortune:celtic-redeliver จับ → ส่งซ้ำ 12:18
        //   หลัง Grand Finale (12:17) ไปแล้ว → ลูกค้าเห็น "ครอบครัว" โผล่ซ้ำหลังสรุป
        //   Fix: session จบ = Grand Finale รวมคำตอบทุกข้อแล้ว → mark ทุก answered question
        //        เป็น delivered กัน cron จับมา re-push (idempotent: เฉพาะที่ delivered_at ยังว่าง)
        try {
            $reading->celticQuestions()
                ->whereNotNull('answered_at')
                ->whereNull('delivered_at')
                ->update(['delivered_at' => now()]);
        } catch (\Throwable $markErr) {
            \Log::debug('Celtic: mark-all-delivered on session end fail (non-blocking)', [
                'reading_id' => $reading->id,
                'error' => $markErr->getMessage(),
            ]);
        }

        // 🃏 (2026-05-16) off_topic_repick — ลูกค้าถามนอกเรื่องเดิม → ชวนจับไพ่ใหม่
        //   ใช้ $aiMessage ที่ส่งเข้ามาตรงๆ (รวม response + ชวนจับใหม่ไว้แล้ว) — skip Grand Finale
        //   เหตุผล: ลูกค้าจะไป flow "ดูดวง" ใหม่ — Grand Finale ไม่เกี่ยว
        //   ใช้ action 'celtic_session_ended' เพื่อให้ channel handler ส่งภาพ composite ที่ระลึกได้
        if ($reason === 'off_topic_repick' && ! empty($aiMessage)) {
            // ปิด Pro Session ก่อน (อย่าให้ค้าง)
            if (method_exists($this, 'clearProSessionFlags')) {
                $this->clearProSessionFlags($reading);
            }

            // สร้างภาพ composite ที่ระลึก (best-effort)
            $composeUrl = null;
            try {
                $generator = app(CelticSpreadImageGenerator::class);
                $composeUrl = $generator->generate($reading);
            } catch (\Throwable $e) {
                \Log::warning('Celtic: composite image fail (off_topic_repick)', [
                    'reading_id' => $reading->id,
                    'error' => $e->getMessage(),
                ]);
            }

            return [
                'action' => 'celtic_session_ended',
                'message' => $aiMessage,
                'reading' => $reading,
                'celtic_summary_image_url' => $composeUrl,
            ];
        }

        // 🌙 (2026-05-08 v3) Pro Session linger detection
        //   ถ้า Pro Session ยังเปิดอยู่ → "ลาแบบหลอก" — ส่ง summary แต่ AI ยังอยู่ต่อ
        //   user spec: "แม้จะมีการสรุปเหมือนจากลา แล้ว แต่ไม่ได้ลาจริง"
        //   ทำงานสำหรับ reason: max_questions_reached / time_expired / idle / ai_signal
        //   ⚠️ customer_said_done = ไม่เคยมาจาก Pro Session active แล้ว (Pro gate catches first)
        $proSessionActive = method_exists($this, 'isInProSession')
            ? $this->isInProSession($reading)
            : false;
        $proSessionRemaining = ($proSessionActive && method_exists($this, 'getProSessionRemainingMinutes'))
            ? $this->getProSessionRemainingMinutes($reading)
            : 0;

        $maxQ = (int) ($this->settings->celtic_cross_max_questions ?? 5);
        $qaWindow = (int) ($this->settings->celtic_cross_qa_window_minutes ?? 15);

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
            // 🌙 (2026-05-24) เพิ่ม customer_said_done ใน combine list — user pivot
            //   "พอใจก่อน ก็สรุป" → ต้องได้ Grand Finale เต็มรูปแบบเหมือนครบ 5Q/หมดเวลา
            if (! empty($aiMessage) && in_array($reason, ['max_questions_reached', 'time_expired', 'idle', 'customer_said_done'], true)) {
                $finalAnswerSection = "🎴 *คำทำนายข้อสุดท้ายของเจ้าชะตา:*\n\n"
                    .trim($aiMessage)."\n\n"
                    .str_repeat('━', 17)."\n\n";
            }

            // 🌙 (2026-05-23 v3) เหตุผลการจบ — บอกชัดว่าครบกติกาแล้ว (5 คำถาม / 15 นาที)
            //   🌙 (2026-05-24) เพิ่ม customer_said_done — บอกชัดว่าลูกค้าขอจบเอง
            $reasonNotice = match ($reason) {
                'max_questions_reached' => '✅ *ครบ '.max(1, $maxQ)." คำถามตามกติกาแล้ว* — แม่หมอส่งบทสรุปท้ายให้ค่ะ\n\n",
                'time_expired' => "⏰ *ครบ {$qaWindow} นาทีตามกติกาแล้ว* — แม่หมอส่งบทสรุปท้ายให้ค่ะ\n\n",
                'customer_said_done' => "💝 *เจ้าชะตาขอจบรอบนี้* — แม่หมอส่งบทสรุปท้ายให้ค่ะ\n\n",
                default => '',
            };

            $closingMessage = $finalAnswerSection
                .$reasonNotice
                ."🌟✨ *บทสรุปสุดท้ายจากแม่หมอจันทรา* ✨🌟\n"
                ."👑 *VIP Master Reading*\n\n"
                .str_repeat('━', 17)."\n\n"
                .$grandFinale."\n\n"
                .str_repeat('━', 17)."\n\n"
                ."💎 *ขอบคุณที่ไว้วางใจแม่หมอนะคะ*\n"
                .'💜 หากต้องการดูใหม่ พิมพ์ *"ดูดวง"* ได้ตลอดเลยค่ะ ✨';
        } else {
            // Default closings (เดิม) — ใช้เมื่อ Grand Finale skip หรือ fail
            $closingMessage = match ($reason) {
                'time_expired' => "⏰ *เวลาคุยกับแม่หมอหมดแล้วค่ะ*\n\n"
                    ."{$qaWindow} นาทีนับจากคำทำนายแรก ผ่านไปเรียบร้อย — แม่หมอขอจบบทสนทนานี้\n"
                    ."เพื่อไปสร้างบารมีกับเจ้าชะตาท่านอื่นต่อ ขอให้เจ้าชะตาโชคดีนะคะ 🙏✨\n\n"
                    .'💜 หากต้องการดูใหม่ พิมพ์ *"ดูดวง"* ได้ตลอดเลยค่ะ',

                'idle' => "🌙 *แม่หมอเห็นว่าเจ้าชะตาเงียบไปนาน*\n\n"
                    ."พลังงานในวงไพ่จางลงแล้ว แม่หมอขอจบบทสนทนานี้นะคะ\n"
                    ."ขอให้เจ้าชะตาเจอแต่สิ่งดีๆ 🙏✨\n\n"
                    .'💜 พิมพ์ *"ดูดวง"* ได้ตลอดเมื่อพร้อมนะคะ',

                'customer_said_done' => "🌟 *ขอบคุณที่ใช้บริการดูดวงไพ่ยิปซี Celtic Cross นะคะ*\n\n"
                    ."คำทำนายเป็นแสงไฟชี้ทาง — แต่การตัดสินใจอยู่ที่เจ้าชะตาเอง 🙏\n"
                    ."ขอให้เจ้าชะตาเจอแต่สิ่งดีๆ นะคะ ✨\n\n"
                    .'💜 หากต้องการดูใหม่ พิมพ์ *"ดูดวง"* ได้ตลอดเลยค่ะ',

                'max_questions_reached' => ($aiMessage ? trim($aiMessage)."\n\n" : '')
                    .'🌟 *เจ้าชะตาถามครบ '.max(1, $maxQ)." คำถามแล้วค่ะ*\n\n"
                    ."แม่หมอตอบครบทุกข้อสงสัยของเจ้าชะตา 🙏✨\n"
                    ."คำทำนายเป็นแสงไฟชี้ทาง — เจ้าชะตาตัดสินใจเอง 💫\n\n"
                    .'💜 หากต้องการดูใหม่ พิมพ์ *"ดูดวง"* ได้ตลอดนะคะ',

                default => ($aiMessage ? trim($aiMessage)."\n\n" : '')
                    ."🌟 *แม่หมอกล่าวลาเจ้าชะตา*\n\n"
                    ."คำทำนายเป็นแสงไฟชี้ทาง — เจ้าชะตาตัดสินใจเอง 🙏\n\n"
                    .'💜 หากต้องการดูใหม่ พิมพ์ *"ดูดวง"* ได้ตลอดนะคะ',
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

        // 🎙️ (2026-05-08) Dispatch voice summary ใน background — ไม่ block closing message
        //    ทำไมต้อง async:
        //      AI summary 5-15s + MiniMax synth 3-10s + chain fallback +5-15s = อาจรวม 30s+
        //      ลูกค้าจ่าย 99฿ — รอนานหลังกด "พอแค่นี้" ทำให้ UX แย่
        //    แทนที่จะ generate sync ที่นี่ → dispatch job, job จะ push audio ทีหลัง
        $voiceWillSend = false;
        try {
            if ($this->settings->shouldGenerateVoiceSummary($reading)) {
                // ใช้ source ที่ดีที่สุดที่มี — เก็บไว้ใน reading state ให้ job อ่าน
                $voiceSource = $grandFinale
                    ?: ($aiMessage ?: $reading->fresh()->deep_response);

                if (! empty($voiceSource) && mb_strlen(trim($voiceSource)) >= 50) {
                    // เก็บ source text ลง state เผื่อ deep_response ไม่ครอบคลุม
                    $reading->setConversationState('voice_summary_source_text', mb_substr($voiceSource, 0, 5000));
                    $reading->setConversationState('voice_summary_status', 'queued');
                    $reading->setConversationState('voice_summary_queued_at', now()->toIso8601String());

                    // Dispatch async — UI ส่ง closing+image ก่อน, voice push 5-15s หลัง
                    ProcessVoiceSummaryJob::dispatchSmart(
                        $reading->id,
                        $reading->platform ?: 'facebook',
                        $reading->platform_user_id ?: $reading->facebook_user_id ?: ''
                    );
                    $voiceWillSend = true;

                    \Log::info('🎙️ Celtic: dispatched voice summary job', [
                        'reading_id' => $reading->id,
                        'source_len' => mb_strlen($voiceSource),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            // dispatch fail = ไม่กระทบ closing message
            \Log::warning('Celtic: voice dispatch exception (non-blocking)', [
                'reading_id' => $reading->id,
                'error' => $e->getMessage(),
            ]);
        }

        // ถ้าจะส่งเสียงทีหลัง — เพิ่ม hint ใน closing message ให้ลูกค้ารอ
        if ($voiceWillSend) {
            $closingMessage .= "\n\n🎙️ _แม่หมอกำลังอัดเสียงสรุปให้ฟังภายใน 1 นาที — รอสักครู่นะคะ_ ✨";
        }

        // 🌙 (2026-05-08 v3) Pro Session linger hint
        //   ถ้า Pro Session ยังมีเวลาเหลือ → "ลาแบบหลอก" — สรุปจบแต่บอกว่ายังอยู่ต่อ
        //   user spec: "แม้จะมีการสรุปเหมือนจากลา แล้ว แต่ไม่ได้ลาจริง เอไอ อวาต้าแม่หมอ
        //              ก็ยังอยู่และ ถามเพิ่มว่าจะถามอะไรไหม"
        //   เกิดเมื่อ: max_questions_reached (3Q ครบ) แต่ window 30 นาทียังเหลือ
        if ($proSessionActive && $proSessionRemaining > 0
            && in_array($reason, ['max_questions_reached', 'ai_signal'], true)) {
            // 🌙 (2026-05-23) เปลี่ยน "ยุติการทำนาย" → "เลิกทำนายและสรุปผล" + 2-step confirm
            $closingMessage .= "\n\n──────────────────────\n"
                ."🌙 *แต่แม่หมอยังไม่ลานะคะ — ยังเปิดประตูพลังให้อีก {$proSessionRemaining} นาที* ✨\n\n"
                ."💬 ถ้าเจ้าชะตามีอะไรอยากถามเพิ่มเติมจากบทสรุป — พิมพ์มาได้เลยค่ะ\n"
                ."   แม่หมอจะอ่านพลังงานจากไพ่ทั้ง 10 ใบให้ละเอียดยิ่งขึ้น\n\n"
                .'📜 หรือถ้าพอใจแล้วพิมพ์ *"เลิกทำนายและสรุปผล"* แม่หมอจะสรุปผลให้ค่ะ';
        } elseif (in_array($reason, ['customer_said_done', 'time_expired', 'idle'], true)
            && method_exists($this, 'clearProSessionFlags')) {
            // 🩹 (2026-05-09 audit fix CC2) Clear Pro Session flag เมื่อ customer ลาจริง
            //    เคสเดิม: customer_said_done → status=COMPLETED แต่ pro_session_active=true ค้าง
            //    → 30 นาทีถัดมา upstream Pro gate intercept "ดูดวง" → AI ตอบจาก context เก่า
            //    → ลูกค้าเริ่ม flow ใหม่ไม่ได้จนกว่า window จะหมดเอง
            //    Fix: clear flag explicit เมื่อ reason = customer/time/idle (ไม่ clear ใน
            //         max_questions_reached/ai_signal เพราะ linger hint ใช้งานได้อยู่)
            $this->clearProSessionFlags($reading);
        }

        return [
            'action' => 'celtic_session_ended',
            'message' => $closingMessage,
            'reading' => $reading,
            'end_reason' => $reason,
            'celtic_summary_image_url' => $composeUrl,
            'has_grand_finale' => ! empty($grandFinale),
            // 🎙️ (2026-05-08) ตอนนี้ voice ส่งผ่าน async job ไม่ใช่ใน return อีก
            //    เก็บ flag ไว้เผื่อ admin debug ต้อง trigger inline (เช่น playground)
            'voice_will_send_async' => $voiceWillSend,
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
        // 🔚 (2026-05-23) "ยุติการทำนาย" / "เลิกทำนายและสรุปผล" → 2-step confirm กันมือลั่น
        $confirmResult = $this->handleCelticEndConfirmation($reading, $messageText);
        if ($confirmResult !== null) {
            return $confirmResult;
        }

        // อย่างอื่น = ถือเป็นคำถามใหม่
        $reading->update(['conversation_status' => FortuneReading::STATUS_CELTIC_AWAITING_QUESTION]);

        return $this->handleCelticAwaitingQuestion($reading, $messageText);
    }

    /**
     * 🔚 (2026-05-23) 2-step confirm สำหรับ "เลิกทำนายและสรุปผล"
     *
     * Why:
     *   user spec: ปุ่มยุติเดิม → กดผิดมือลั่น = session จบทันที สูญเงิน 99฿
     *   ใหม่: เปลี่ยนปุ่มเป็น "📜 เลิกทำนายและสรุปผล" + ถามยืนยันก่อนจริงๆ
     *
     * Flow:
     *   1. ครั้งแรก: ลูกค้าพิมพ์ "ยุติ"/"เลิก"/"จบ" หรือกดปุ่ม CELTIC_END_ASK
     *      → ส่ง confirm prompt + Quick Replies (ใช่/ขอคุยต่อ)
     *      → set Cache flag celtic_pending_end_confirm:{readingId} TTL 2 min
     *   2. ครั้งที่สอง (มี flag):
     *      → "ใช่"/"ส่งสรุป" / CELTIC_END_YES → call endCelticSession (จริง)
     *      → "ไม่"/"ขอคุยต่อ" / CELTIC_END_NO → clear flag + กลับ Q&A
     *      → อย่างอื่น → clear flag + ปล่อยให้ caller handle ปกติ
     *
     * @return array|null array = handled, null = ไม่เกี่ยว (caller จัดการต่อ)
     */
    protected function handleCelticEndConfirmation(FortuneReading $reading, string $messageText): ?array
    {
        // 🔥 (2026-05-24) ลบ 2-step confirm — user pivot: "พอใจก่อน ก็สรุปเลย ไม่ต้องรอกดปุ่ม"
        //   เดิม: keyword "พอ/จบ" → ASK confirm + Quick Reply ใช่/ไม่ → รอกด ✅ → endCelticSession
        //   ใหม่: keyword end → endCelticSession ทันที (combine final answer + Grand Finale)
        //   ตัด "ขอบคุณ"/"thanks" ออก — false positive สูง (ลูกค้าขอบคุณแล้วถามต่อ)
        //   เก็บ NO keyword + clear legacy pending cache สำหรับ user ที่ติดจากเวอร์ชันก่อน

        $cacheKey = "celtic_pending_end_confirm:{$reading->id}";
        $hadPending = Cache::has($cacheKey);

        // 🔚 End keywords — explicit "เลิก/พอ/จบ/ส่งสรุป" → endCelticSession ทันที
        $endKeywords = [
            // ปุ่ม / postback (ทุกแบบ → end ทันที)
            'CELTIC_END_YES', 'CELTIC_END_ASK', 'CELTIC_END',
            // เลิก / ยุติ
            'ยุติการทำนาย', 'ยุติทำนาย', 'ยุติ',
            'เลิกทำนายและสรุปผล', 'เลิกทำนาย', 'เลิก',
            'จบการทำนาย', 'จบเลย', 'จบ',
            // พอใจ / พอแล้ว
            'พอแค่นี้', 'พอแล้ว', 'พอ', 'หยุด', 'stop',
            // ขอสรุป
            'ส่งสรุป', 'ส่งสรุปเลย', 'สรุปเลย', 'สรุป',
            // legacy YES ที่ลูกค้าอาจกดจาก confirm prompt เก่า
            'ใช่ ส่งสรุปเลย', 'ส่งเลย',
        ];

        if ($this->matchesExactKeyword($messageText, $endKeywords)) {
            Cache::forget($cacheKey); // clear legacy pending if any
            Log::info('Celtic: end keyword → endCelticSession ทันที (no confirm)', [
                'reading_id' => $reading->id,
                'message' => mb_substr($messageText, 0, 50),
                'had_legacy_pending' => $hadPending,
            ]);

            return $this->endCelticSession($reading, 'customer_said_done');
        }

        // 🔚 (2026-05-29) Defense ชั้น 2 — วลีจบยาวชัดเจน → str_contains (เผื่อ exact match พลาด)
        //   เคส R4253: ปุ่ม "📜 เลิกทำนายและสรุปผล" — ถ้า emoji แปลกใหม่ที่ normalizeUserInput
        //   strip ไม่หมด ให้ str_contains จับซ้ำ (belt-and-suspenders คู่กับ strip emoji)
        //   ปลอดภัย: วลี 9-18 อักษร แทบเป็นไปไม่ได้ในคำถามทำนายปกติ + กัน negation นำหน้า
        //   ("ไม่อยากเลิกทำนาย" / "ยังไม่จบ" → ไม่ end)
        $normalizedEnd = $this->normalizeUserInput($messageText);
        $longEndPhrases = ['เลิกทำนายและสรุปผล', 'ยุติการทำนาย', 'จบการทำนาย', 'เลิกทำนาย'];
        $hasNegation = (bool) preg_match('/(ไม่อยาก|ไม่ต้องการ|ยังไม่|อย่าเพิ่ง|ไม่เลิก|ไม่จบ)/u', $normalizedEnd);
        if (! $hasNegation) {
            foreach ($longEndPhrases as $phrase) {
                if (str_contains($normalizedEnd, $phrase)) {
                    Cache::forget($cacheKey);
                    Log::info('Celtic: end phrase (str_contains defense) → endCelticSession', [
                        'reading_id' => $reading->id,
                        'matched_phrase' => $phrase,
                        'raw' => mb_substr($messageText, 0, 50),
                    ]);

                    return $this->endCelticSession($reading, 'customer_said_done');
                }
            }
        }

        // 🔄 NO keyword — เก็บไว้รองรับ user ที่กด "ขอคุยต่อ" จาก confirm prompt เก่า
        //   หลังจาก deploy แล้วระบบไม่ส่ง confirm prompt อีก → keyword นี้แทบไม่มี trigger
        $noKeywords = [
            'CELTIC_END_NO',
            'ขอคุยต่อ', 'คุยต่อ', 'ขอคุยต่ออีกหน่อย', 'ขออีก',
        ];
        if ($hadPending && $this->matchesExactKeyword($messageText, $noKeywords)) {
            Cache::forget($cacheKey);
            Log::info('Celtic: end_confirm NO (legacy) → continue Q&A', [
                'reading_id' => $reading->id,
            ]);

            return [
                'action' => 'celtic_continue',
                'message' => "🌙 *ได้ค่ะ — แม่หมอยังอยู่ตรงนี้* ✨\n\n"
                    .'💬 พิมพ์คำถามต่อมาได้เลยนะคะ แม่หมอรอฟังอยู่',
                'reading' => $reading,
            ];
        }

        // อย่างอื่น → ปล่อยให้ caller handle ปกติ (clear legacy pending ถ้ามี)
        if ($hadPending) {
            Cache::forget($cacheKey);
        }

        return null;
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

    /**
     * 🆘 (2026-05-16) ตรวจว่าลูกค้าถาม "ถึงไหนแล้ว / ไม่เห็นภาพ"
     *
     * เคสจริง: LINE message lost (replyToken expire + push fail) → ลูกค้าไม่เห็นไพ่
     *   แม้ระบบเปิดไพ่ใน DB แล้ว → ลูกค้างง ว่าถึงไหนแล้ว
     *   ก่อน fix: matchesCelticReadyKeyword=false → chitchat reminder loop
     *   ใหม่: detect status inquiry → ส่ง state + รูปใบล่าสุดให้ดูใหม่
     */
    protected function looksLikeCelticStatusInquiry(string $text): bool
    {
        $clean = mb_strtolower(trim($text));
        if ($clean === '') {
            return false;
        }

        // limit ความยาว — กัน false positive จากคำถามจริง
        if (mb_strlen($clean) > 50) {
            return false;
        }

        $patterns = [
            // ไม่เห็น / ไม่ขึ้น / ไม่มา
            'ไม่เห็น', 'มองไม่เห็น', 'ไม่ขึ้น', 'ไม่มา', 'ไม่ได้รูป', 'ไม่ได้ภาพ',
            'ภาพไม่ขึ้น', 'ภาพไม่มา', 'รูปไม่ขึ้น', 'รูปไม่มา', 'ไม่มีภาพ', 'ไม่มีรูป',
            'ไม่ส่งมา', 'ไม่ตอบ', 'เงียบ', 'หายไป', 'หาย',
            // ถึงไหน
            'ถึงไหน', 'อยู่ตรงไหน', 'อยู่ขั้นไหน', 'ขั้นไหน', 'อะไรแล้ว',
            'กี่ใบแล้ว', 'ใบที่เท่าไหร่', 'เปิดถึงใบไหน', 'ใบไหนแล้ว',
            // English
            'where', "don't see", 'not see', 'no image', 'missing',
        ];

        foreach ($patterns as $kw) {
            if (str_contains($clean, mb_strtolower($kw))) {
                return true;
            }
        }

        return false;
    }

    /**
     * 🆘 (2026-05-16) สร้าง status recovery — ส่ง state ปัจจุบัน + รูปใบล่าสุด
     *
     * ใช้คู่กับ looksLikeCelticStatusInquiry — ลูกค้าไม่เห็น message ก่อนหน้า
     *
     * Returns:
     *   - message: บอกว่าเปิดถึงใบที่เท่าไหร่ + คำถามที่อยากถามถัดไป
     *   - tarot_image_url: รูปใบล่าสุดที่เปิด (ลูกค้าไม่เห็นเพราะ message lost)
     *   - action: 'celtic_chitchat_reminder' → ChannelManager รู้ว่าต้องส่งทั้ง image + text + QR
     */
    protected function buildCelticStatusRecovery(FortuneReading $reading): array
    {
        $picked = $reading->getCelticPickedCount();
        $cards = $reading->getCelticCards();
        $lastCard = ! empty($cards) ? end($cards) : null;
        $lastImage = $lastCard['image_url'] ?? null;
        $lastPositionName = '';
        $lastCardName = '';
        if (is_array($lastCard)) {
            $lastPosition = (int) ($lastCard['position'] ?? 0);
            $meta = FortuneReading::CELTIC_POSITIONS[$lastPosition] ?? null;
            $lastPositionName = $meta['name'] ?? '';
            $lastCardName = $lastCard['card_name_th'] ?? '';
        }

        $nextPosition = $reading->getNextCelticPosition();
        $nextPrompt = $this->buildCelticPickPromptText($reading);

        $header = "🌙 ขออภัยค่ะถ้าเจ้าชะตาไม่เห็นข้อความก่อนหน้า — แม่หมอส่งให้ใหม่นะคะ ✨\n\n";

        if ($picked === 0 || $lastImage === null) {
            // ยังไม่เปิดใบไหนเลย
            $message = $header
                ."🃏 ยังไม่ได้เปิดไพ่ใบไหน — เริ่มจากใบที่ 1\n\n"
                ."──────────────────────\n"
                .$nextPrompt;
        } elseif ($nextPosition === null) {
            // เปิดครบ 10 แล้ว — รอคำถาม
            $message = $header
                ."✨ เปิดไพ่ครบ 10 ใบแล้ว — แม่หมอพร้อมรับฟัง\n"
                .'💬 พิมพ์คำถามที่อยากรู้มาได้เลยค่ะ';
        } else {
            // กลางทาง — บอกใบล่าสุด + ใบถัดไป
            $message = $header
                ."🃏 ตอนนี้เปิดไพ่ไปแล้ว *{$picked}/10 ใบ*\n";
            if ($lastCardName && $lastPositionName) {
                $message .= "📌 ใบล่าสุด — *{$lastCardName}* [{$lastPositionName}]\n";
            }
            $message .= "\n──────────────────────\n".$nextPrompt;
        }

        return [
            'action' => 'celtic_chitchat_reminder', // reuse — ChannelManager จัด image + QR ให้
            'message' => $message,
            'reading' => $reading,
            'tarot_image_url' => $lastImage,  // ส่งรูปใบล่าสุดให้ลูกค้าดูซ้ำ
        ];
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
