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

        // 🔔 (2026-06-20) marker สำหรับ fortune:flow-nudge — กระตุ้นถ้าเงียบ 1 นาที / ออกถ้า 30 นาที
        $reading->setConversationState('tier_choice_shown_at', now()->toIso8601String());
        $reading->setConversationState('flow_nudge_sent_at', null);

        $deepPrice = number_format($this->getDeepReadingPrice(), 0);
        $celticPrice = number_format(app(CelticCrossService::class)->getPrice(), 0);
        // 🔢 (2026-05-03) อ่านจาก settings — ตรงกับที่ admin ตั้ง (0 = ไม่จำกัด)
        $maxQRaw = (int) ($this->settings->celtic_cross_max_questions ?? 0);
        $qaWindow = (int) ($this->settings->celtic_cross_qa_window_minutes ?? 15);
        $qLimitText = $maxQRaw <= 0 ? 'ไม่จำกัด' : "{$maxQRaw} คำถาม";
        // 🌙 (2026-06-08) หน้าต่างคุยของแพคเกจ 39 (พื้นดวง) — คู่ขนานกับ Celtic 99
        $deepWindow = (int) ($this->settings->deep_reading_qa_window_minutes ?? 7);

        // 🎁 หัวเมนู — เปลี่ยนตามว่ามีปุ่มฟรีหรือไม่
        // 🌐 (2026-05-03) localize header + intro — ลูกค้าลาวเห็นเมนูเป็นลาว
        // 🌙 (2026-05-23) packageCount นับเฉพาะที่ enabled — Deep ปิดก็ไม่นับ
        // 🆕 (2026-05-27) Celtic-only mode = intro tone (ไม่ใช่ "เลือก 1 จาก 1")
        $packageCount = ($deepEnabled ? 1 : 0) + ($celticEnabled ? 1 : 0) + ($offerFree ? 1 : 0);
        $isCelticOnlyIntro = ! $deepEnabled && $celticEnabled && ! $offerFree;

        // 🧓 (2026-06-12) ลูกค้าส่วนใหญ่เป็นผู้มีอายุ + drop-off ตรงหน้าราคา — ลดอีโมจิ/ตัด * (FB/LINE
        //   ไม่ render markdown = เห็นดอกจันดิบ) / สั้นลง / น้ำเสียงนิ่งน่าเชื่อถือ ไม่เหมือนโฆษณา
        $welcomeLine = FortuneLocaleService::lo(
            '🌙 แม่หมอจันทรายินดีต้อนรับค่ะ',
            '🌙✨ *ໝໍຈັນທາຍິນດີຕ້ອນຮັບເຈົ້າຊາຕາເດີ* ✨🌙'
        );
        if ($isCelticOnlyIntro) {
            // Celtic-only — intro tone (ไม่บังคับ ลูกค้าเลือกเองได้ พร้อมจะอ่านราคาก่อน)
            // 🌙 (2026-05-31) intro hook — ชูจุดต่าง "ไม่ใช่ดูดวงสำเร็จรูป คุยกับแม่หมอตัวจริง" (user: น่ากด+สั้น)
            $introLine = FortuneLocaleService::lo(
                'ที่นี่ไม่ใช่ดูดวงสำเร็จรูปนะคะ แม่หมอเปิดไพ่จริงให้ แล้วคุยตอบกันสดๆ เหมือนนั่งดูต่อหน้า',
                'ບ່ອນນີ້ບໍ່ແມ່ນເບິ່ງດວງສຳເລັດຮູບເດີ — ແມ່ໝໍເປີດໄພ່ຈິງໃຫ້ ແລ້ວ *ລົມຕອບກັນສົດໆ* ຄືກັບນັ່ງເບິ່ງຕໍ່ໜ້າ ✨'
            );
        } else {
            // 🧓 (2026-06-12) สร้างความเชื่อใจก่อนพูดเรื่องเงิน — แม่หมอตอบเองจริง ไม่ใช่ข้อความสำเร็จรูป
            // 📦 (2026-08-13) ตัดบรรทัด "เลือกแบบที่สบายใจได้เลยนะคะ" — ซ้ำกับ CTA ท้ายเมนู
            //   ทุกตัวอักษรมีค่า: ข้อความนี้ต้อง ≤ 640 ตัว ไม่งั้น FB ส่งเนื้อความแยกอีกกล่อง
            $introLine = FortuneLocaleService::lo(
                'แม่หมอเปิดไพ่จริงและตอบเองทุกข้อความ ไม่ใช่คำทำนายสำเร็จรูป',
                "ມື້ນີ້ເຈົ້າຊາຕາຢາກໃຫ້ໝໍເປີດທາງດວງໃຫ້ແບບໃດເດີ?\n"
                    ."ເລືອກໄດ້ *1 ໃນ {$packageCount} ແພັກເກດ* ດ້ານລຸ່ມເລີຍ 👇"
            );
        }
        $message = $welcomeLine."\n\n".$introLine."\n\n";

        // 🎁 (2026-05-03) แพคเกจ "ทำนายฟรี" — เฉพาะ first-timer + feature เปิด
        if ($offerFree) {
            // 📦 (2026-08-13) ย่อเหลือ 2 บรรทัด ทรงเดียวกับบล็อก 39/99 — 2 เหตุผล
            //   1. บล็อกเดิม ~250 ตัว ดันข้อความรวมทะลุ 640 → FB แยกเนื้อความออกเป็นอีกกล่อง
            //   2. บล็อกนี้ตกหล่นจากรอบล้างอีโมจิ/ดอกจัน (2026-06-12) — FB/LINE ไม่ render
            //      markdown ลูกค้าเห็น *ดอกจันดิบ* จริง ๆ ต่างจากบล็อก 39/99 ที่ล้างไปแล้ว
            $freeBlock = FortuneLocaleService::lo(
                "🎁 ทำนายฟรี (1 ใบ) — สิทธิ์พิเศษครั้งแรก\n"
                    ."เปิดไพ่ 1 ใบ ที่จิตเจ้าชะตาเลือกเอง เสร็จใน 1 นาที (ใช้ได้ครั้งเดียว/ท่าน)\n\n",
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
                "🔹 ดูพื้นดวง — ค่าครู {$deepPrice} บาท\n"
                    ."คำนวณดาวประจำตัวจากวันเกิด ผสมกับไพ่ อ่านภาพรวมชีวิตให้ทันที\n"
                    ."คุยถามแม่หมอต่อได้ {$deepWindow} นาที\n\n",
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
            // 🌙 (2026-05-31) Celtic-only intro = ฉบับสั้น + ชูจุดต่าง (ฟันธง+ภาพไพ่ที่ระลึก) — user: "น่ากด+สั้น คนแก่ปวดตา"
            if ($isCelticOnlyIntro) {
                $celticBlock = FortuneLocaleService::lo(
                    "👑 Celtic Cross — ค่าครู {$celticPrice} บาท\n"
                        ."เปิดไพ่โบราณ 10 ใบ แม่หมอฟันธงให้ทีละใบ\n"
                        ."ถามแม่หมอได้ {$qLimitText} ใน {$qaWindow} นาที ตอบสดไม่มีรอ\n"
                        ."พร้อมภาพไพ่สวยๆ เก็บเป็นที่ระลึก\n\n",
                    "👑 *Celtic Cross • ຄ່າຄູ {$celticPrice} ບາດ*\n"
                        ."🃏 ເປີດໄພ່ 10 ໃບ ຟັນທົງເທື່ອລະໃບ (ໄພ່ບູຮານດັ້ງເດີມ)\n"
                        ."💬 ຖາມແມ່ໝໍໄດ້ {$qLimitText} ໃນ {$qaWindow} ນາທີ — ຕອບສົດບໍ່ມີລໍ\n"
                        ."🎁 ແຖມຮູບໄພ່ງາມໆ ເກັບເປັນທີ່ລະນຶກ\n\n"
                );
            } else {
                $celticBlock = FortuneLocaleService::lo(
                    "👑 ไพ่เต็มสำรับ Celtic Cross — ค่าครู {$celticPrice} บาท\n"
                        ."เปิดไพ่โบราณ 10 ใบ แม่หมอฟันธงให้ทีละใบ\n"
                        ."ถามได้ {$qLimitText} ใน {$qaWindow} นาที ตอบสดไม่มีรอ\n\n",
                    "━━━━━━━━━━━━━━━━━\n"
                        ."👑 *ແພັກເກດ{$celticNum} — Celtic Cross {$celticPrice} ບາດ*\n"
                        ."━━━━━━━━━━━━━━━━━\n"
                        ."🃏 ໄພ່ 10 ໃບ ຕາມຫລັກ *Celtic Cross ໂບຮານດັ້ງເດີມ*\n"
                        ."💬 *ຄຸຍກັບແມ່ໝໍໄດ້ {$qLimitText} ພາຍໃນ {$qaWindow} ນາທີ*\n"
                        ."⏱️ ທຳນາຍແລ້ວໃນ 5-10 ນາທີ (ພິມຄຳຖາມໄດ້ທັນທີ)\n\n"
                );
            }
            $message .= $celticBlock;
        }

        // ━━━━━━━━━━━━━━━━━━━━━━━━
        // 🪬 (2026-06-24) ดูคุณไสย์ / มนต์ดำ 99฿ — โหมดเจาะเรื่องของโดยเฉพาะ (gate enable_celtic_black_magic_mode)
        //   ราคาเท่า Celtic 99 (ใช้ engine เดียวกัน 10 ใบ) แต่ prompt ล็อกเทเรื่องคุณไสย์ 100% ทั้งรอบ
        // ━━━━━━━━━━━━━━━━━━━━━━━━
        $blackMagicEnabled = $celticEnabled && (bool) ($this->settings->enable_celtic_black_magic_mode ?? true);
        if ($blackMagicEnabled) {
            // 📦 (2026-08-13) ตัดเส้นคั่น ━ ออก (18 ตัวอักษรเปล่า ๆ) + รวม 2 บรรทัดเป็นบรรทัดเดียว
            //   บล็อก 39/99 ด้านบนไม่มีเส้นคั่นอยู่แล้ว — ของเดิมจึงคั่นข้างเดียวดูเบี้ยวด้วย
            $message .= FortuneLocaleService::lo(
                "🪬 ดูคุณไสย์ / มนต์ดำ / โดนของ — ค่าครู {$celticPrice} บาท\n"
                    ."เปิดไพ่ 10 ใบ ล็อกทั้งสำรับเจาะว่าโดนจริงไหม ชนิดของ ใครทำ วิธีแก้\n\n",
                "━━━━━━━━━━━━━━━━━\n"
                    ."🪬 ເບິ່ງຄຸນໄສ / ມົນດຳ / ໂດນຂອງ — ຄ່າຄູ {$celticPrice} ບາດ\n"
                    ."ເປີດໄພ່ 10 ໃບ ລັອກພະລັງທັງສຳຮັບເຈາະເລື່ອງຂອງ/ຄຸນໄສໂດຍສະເພາະ\n\n"
            );
        }

        // 👇 CTA — รวมตัวเลือกตามที่มี
        // 🆕 (2026-05-27) Celtic-only intro = CTA tone "พร้อม/ไว้คราวหน้า" (ไม่ใช่ "เลือกแพคเกจ")
        if ($isCelticOnlyIntro) {
            // 🌙 (2026-05-31 → 2026-06-12) CTA สั้น เป็นมิตรกับผู้มีอายุ + ประโยคลดความกลัวเรื่องเงิน
            $message .= FortuneLocaleService::lo(
                "พร้อมเริ่ม กดปุ่มด้านล่าง หรือพิมพ์ {$celticPrice} ได้เลยค่ะ\n\n"
                    ."ยังไม่แน่ใจ ถามแม่หมอก่อนได้ ไม่คิดค่าใช้จ่ายค่ะ\n"
                    .'ไม่สะดวกตอนนี้ พิมพ์ "ไว้คราวหน้า" ได้นะคะ 🙏',
                "✨ ພ້ອມ — ກົດປຸ່ມ *\"ໂອນຄ່າບູຊາຄູ\"* ດ້ານລຸ່ມໄດ້ເລີຍ\n"
                    ."💬 ຢາກຖາມກ່ອນ — ພິມມາໄດ້ເລີຍ ແມ່ໝໍຕອບໃຫ້\n"
                    .'🙏 ຍັງບໍ່ພ້ອມ — ພິມ *"ໄວ້ຄາວໜ້າ"* ກໍ່ໄດ້ເດີ'
            );
        } else {
            // 🧓 (2026-06-12) CTA สั้น + ประโยคลดความกลัว — ถามก่อนได้ฟรี / ไม่พร้อมไม่เป็นไร
            //   (ตัวเลือกพิมพ์เลขรวมเหลือบรรทัดเดียว — handleTierChoice รับ "39"/"99"/"celtic" เหมือนเดิม)
            $typeHints = [];
            if ($offerFree) {
                $typeHints[] = '"ทำนายฟรี"';
            }
            if ($deepEnabled) {
                $typeHints[] = $deepPrice;
            }
            if ($celticEnabled) {
                $typeHints[] = $celticPrice;
            }
            $typeHintText = implode(' หรือ ', $typeHints);

            // 🚪 (2026-08-13) บรรทัด "ไว้คราวหน้า" = ทางออกเดียวที่เหลือบนหน้าจอ FB แล้ว
            //   (ปุ่ม "❌ ยกเลิก" ถูกตัดออกเพื่อให้เมนูเหลือกล่องเดียว — FortuneChannelManager)
            //   ห้ามลบบรรทัดนี้ ไม่งั้นลูกค้าที่ไม่อยากดูจะหาทางออกไม่เจอ = ค้างในโฟลว์จนคิวปิดเอง
            $message .= FortuneLocaleService::lo(
                "กดปุ่มด้านล่าง หรือพิมพ์ {$typeHintText} ได้เลยค่ะ\n"
                    ."ยังไม่แน่ใจ ถามแม่หมอก่อนได้ ไม่คิดค่าใช้จ่ายค่ะ\n"
                    .'ไม่สะดวกตอนนี้ พิมพ์ "ไว้คราวหน้า" ได้นะคะ 🙏',
                "✦ ຫຼື ກົດປຸ່ມດ້ານລຸ່ມ ໄດ້ເລີຍເດີ\n\n"
                    .'🙏 ຖ້າຍັງບໍ່ພ້ອມ ພິມ "ຍົກເລີກ" ໄດ້ເດີ'
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
            'black_magic_enabled' => $blackMagicEnabled, // 🪬 (2026-06-24) flag ให้ ChannelManager เพิ่มปุ่ม "ดูคุณไสย"
            // 🎨 (2026-07-25) meta กติกาแพคเกจแบบ structured — LINE ใช้ประกอบ Flex เมนูเลือกแพคเกจ
            //   (FB ใช้ $message เดิม — ไม่กระทบ). field นี้ไม่มี → LINE fallback text+ปุ่มแบบเดิม
            'tier_meta' => [
                'welcome_line' => $welcomeLine,
                'deep_enabled' => $deepEnabled,
                'celtic_enabled' => $celticEnabled,
                'deep_window' => $deepWindow,
                'qa_window' => $qaWindow,
                'q_limit_text' => $qLimitText,
                'voice_enabled' => (bool) ($this->settings->voice_summary_enabled ?? false),
            ],
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
        $deepWindow = (int) ($this->settings->deep_reading_qa_window_minutes ?? 7);
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

                // ⚡ (2026-08-03) ยืนยันแพคเกจแล้ว = ไม่ต้องถาม "QR หรือ บัตร?" ซ้ำ → ออก QR เลย
                return $this->startCelticCrossFlow($reading, skipStripeGate: true);
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

                    // ⚡ (2026-08-03) ยืนยันแพคเกจแล้ว → QR ทันที ไม่ถามวิธีชำระซ้ำ
                    return $this->startCelticCrossFlow($reading, skipStripeGate: true);
                }
            }
        }

        // 🪬 (2026-06-24) ดูคุณไสย์ / มนต์ดำ — โหมดบังคับ (ลูกค้ากดปุ่ม "ดูคุณไสย" หรือพิมพ์เอง)
        //   ⚠️ ตรวจก่อน generic Celtic — ปุ่มส่ง text "ดูคุณไสย" ไม่มี "99"/"celtic" ; gate ด้วย setting
        //   ตั้งธง black_magic_mode บน reading (persist) + carrier cache → buildBlackMagicDirective เทเรื่องนี้ 100%
        if ((bool) ($this->settings->enable_celtic_black_magic_mode ?? true)) {
            $bmKeywords = ['คุณไสย', 'มนต์ดำ', 'มนตร์ดำ', 'ดูคุณไสย', 'tier_celtic_blackmagic', 'blackmagic', 'black_magic'];
            foreach ($bmKeywords as $kw) {
                if (mb_strpos($textLower, mb_strtolower($kw)) !== false) {
                    $bmUserId = $reading->facebook_user_id ?? $reading->platform_user_id;
                    $reading->setConversationState('black_magic_mode', true);
                    if (! empty($bmUserId)) {
                        \Illuminate\Support\Facades\Cache::put('fortune:force_black_magic:'.$bmUserId, true, now()->addHours(2));
                    }
                    \Illuminate\Support\Facades\Log::info('Fortune: ลูกค้าเลือกโหมดดูคุณไสย์ (tier_choice)', [
                        'reading_id' => $reading->id,
                        'keyword' => $kw,
                    ]);

                    // ⚡ (2026-08-03) เลือกแพคเกจแล้ว → QR ทันที ไม่ถามวิธีชำระซ้ำ
                    return $this->startCelticCrossFlow($reading, skipStripeGate: true);
                }
            }
        }

        // 🪬 (2026-06-24) ผ่านด่านคุณไสย์มาแล้ว = ลูกค้าเลือกแพคเกจปกติ → ล้าง carrier เก่า กัน bleed เข้าโหมดคุณไสย์
        $bmClearUid = $reading->facebook_user_id ?? $reading->platform_user_id;
        if (! empty($bmClearUid)) {
            \Illuminate\Support\Facades\Cache::forget('fortune:force_black_magic:'.$bmClearUid);
        }

        // 🔮 99฿ Celtic Cross — keyword: "99", "celtic", "เต็ม", "เต็มสำรับ", "ไพ่ยิปซีเต็ม", "พรีเมียม"
        // ⚠️ เช็ค Celtic ก่อน Deep เผื่อข้อความมีทั้ง "99" และ "39" (ไม่ค่อยเกิด แต่กันไว้)
        $celticKeywords = [
            (string) $celticPriceInt,  // "99" (ดึงจาก service ไม่ hardcode)
            'celtic', 'เซลติก', 'เต็มสำรับ', 'เต็ม สำรับ', 'ไพ่ยิปซีเต็ม', 'ทาโรต์เต็ม',
            'พรีเมียม', 'premium', 'แพคเกจ 2', 'แพคเกจที่ 2', 'แบบที่ 2',
            // 🆕 (2026-05-31) ลูกค้าพิมพ์ตามปุ่มเก่า "โอนค่าบูชาครู" → เข้า Celtic flow (กดหรือพิมพ์ก็ได้)
            'บูชาครู', 'โอนค่าบูชาครู', 'ค่าบูชาครู',
            // 🆕 (2026-06-08) ปุ่มเก่า "👑 VIP ดูไพ่เต็ม" — เก็บไว้ ลูกค้าเก่าอาจพิมพ์ตามป้ายที่เคยเห็น
            'vip ดูไพ่', 'ดูไพ่เต็ม', 'ไพ่เต็ม',
            // 🆕 (2026-08-12, owner) ป้ายใหม่ "ดู vip ส่วนตัว 99บาท" ทุกช่องทาง
            //   ⚠️ ป้ายปุ่ม FB เด้งกลับมาเป็น "ข้อความ" ได้ (ไม่มี payload) → ต้องมีคีย์เวิร์ดรับ
            //   ดู [[rule_fb_quickreply_label_arrives_as_text]] · "99" ด้านบนรับอยู่แล้ว นี่คือชั้นสำรอง
            //   เผื่อกรณีป้ายถูกตัด/ราคาเปลี่ยน (ป้าย 20 ตัวพอดี — 3 หลักจะโดนตัดท้าย)
            'vip ส่วนตัว', 'ดู vip',
            'tier_celtic', 'tier_celtic_99',  // payload จาก FB button
        ];
        foreach ($celticKeywords as $kw) {
            if (mb_strpos($textLower, mb_strtolower($kw)) !== false) {
                // ⚡ (2026-08-03) พิมพ์/กด "99" หรือ "celtic" = เลือกแพคเกจแล้ว รู้ราคาแล้ว
                //   → ออกบิล + QR ทันที ไม่ถาม "QR หรือ บัตร?" ซ้ำอีกด่าน
                //   (เจ้าของ: "ไม่ใช่มาถาม แล้วถามอีก" — อยากจ่ายบัตรพิมพ์ "บัตร" หลังบิลออกได้)
                return $this->startCelticCrossFlow($reading, skipStripeGate: true);
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
                // ⚡ (2026-08-03) พิมพ์/กด "39" = เลือกแพคเกจแล้ว รู้ราคาแล้ว → QR ทันที
                //   ไม่ถาม "QR หรือ บัตร?" ซ้ำ (เจ้าของ: "ไม่ใช่มาถาม แล้วถามอีก")
                return $this->routePayFirstDeep($reading, skipPaymentGate: true);
            }
        }

        // 💳 (2026-06-20) ลูกค้าบอก "พร้อมจ่าย/ขอ QR" แต่ยังไม่เลือกแพคเกจ (เช่น "QR","พร้อมโอน","โอนเลย")
        //   เคสจริง (มุกดา แสนนุภาพ FTU-260620): พิมพ์ "QR" ที่ tier_choice → tier_choice_invalid
        //   "เลือกแพคเกจอีกครั้ง" → ลูกค้างงว่าทำไมไม่ส่ง QR. วางหลัง keyword tier (39/99/celtic ชนะก่อน)
        //   - เปิดแพคเกจเดียว → route ออก QR เลย / เปิดทั้งคู่ → ชวนเลือกแบบ payment-positive (มีปุ่มด้านล่าง)
        // 🆕 (2026-06-27) เพิ่ม "ธนาคาร/บัญชี/พร้อมเพย์" — ลูกค้าถามช่องทางจ่ายตั้งแต่ยังไม่เลือกแพคเกจ
        //   (เคสจริงที่เจ้าของยกมา: พิมพ์ "ธนาคาร" → เดิมหลุดไปกล่อง "เลือกแพคเกจอีกครั้ง")
        $paymentIntentNoTier = ['qr', 'คิวอาร์', 'พร้อมโอน', 'ขอโอน', 'โอนเลย', 'พร้อมจ่าย', 'จ่ายเลย', 'ขอเลขบัญชี', 'ขอบัญชี', 'เลขบัญชี', 'ธนาคาร', 'บัญชี', 'พร้อมเพย์', 'promptpay'];
        foreach ($paymentIntentNoTier as $kw) {
            if (mb_strpos($textLower, mb_strtolower($kw)) !== false) {
                // เปิดเฉพาะ Deep → ออก QR Deep ทันที
                if ($deepEnabledLocal && ! $celticEnabledLocal) {
                    $updateData = ['reading_type' => FortuneReading::READING_TYPE_DEEP];
                    if (empty($reading->bill_reference)) {
                        $updateData['bill_reference'] = FortuneReading::generateBillReference();
                    }
                    $reading->update($updateData);

                    return $this->routePayFirstDeep($reading);
                }
                // เปิดเฉพาะ Celtic → เข้า Celtic flow (ออก QR 99)
                if ($celticEnabledLocal && ! $deepEnabledLocal) {
                    return $this->startCelticCrossFlow($reading);
                }

                // เปิดทั้งคู่ → เลือกแพคเกจไม่ได้แทน ขอให้เลือก (payment-positive ไม่ใช่ "ผิด")
                return [
                    'action' => 'tier_choice_invalid',
                    'message' => "🙏 ยินดีค่ะ! แม่หมอส่ง QR ให้ได้เลย — เลือกแพคเกจก่อนนะคะ แล้ว QR จะตามมาทันที 👇\n\n"
                        ."🔹 พิมพ์ *\"{$deepPriceInt}\"* — ดูพื้นดวง {$deepPriceInt} บาท\n"
                        ."🔮 พิมพ์ *\"{$celticPriceInt}\"* หรือ *\"celtic\"* — ไพ่ยิปซีเต็มสำรับ {$celticPriceInt} บาท\n\n"
                        .'👇 หรือกดปุ่มด้านล่างก็ได้นะคะ ✨',
                    'reading' => $reading,
                ];
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
            $stepHintCompact .= "🔹 *\"{$deepPriceInt}\"* — ดูพื้นดวง {$deepPriceInt} บาท (วันเกิด + ไพ่ + คุย {$deepWindow} นาที)\n";
        }
        if ($celticEnabledHint) {
            $stepHintCompact .= "🔮 *\"{$celticPriceInt}\"* หรือ *\"celtic\"* — ไพ่ยิปซีเต็มสำรับ {$celticPriceInt} บาท (10 ใบ + คุยจุใจ {$qaWindow} นาที)\n";
        }
        $stepHintCompact .= '❌ *"ยกเลิก"* — หากไม่ต้องการตอนนี้';

        // 🧭 (2026-06-22) ลูกค้าสับสน "ขั้นตอน" ตอนเลือกแพคเกจ → ต้องได้คำแนะนำ ไม่ใช่เด้งเมนูซ้ำดื้อๆ
        //   เคส FTU-260622-R6853 (จุไร พิกุลแย้ม): พิมพ์ "จะไปทางไหนค่ะ" → หลุดทุก keyword → "เลือกแพคเกจอีกครั้ง"
        //   ⚠️ จงใจเช็คเฉพาะ state tier_choice นี้ (ยังไม่ได้ถามดวง) — ไม่เติมใน looksLikeMetaOrChitchat
        //      ที่ใช้ร่วมหลาย state เพราะ substring เช่น "ไปยังไง"/"ไม่เข้าใจ" จะไปชนคำถามทำนายจริงตอนเก็บคำถาม
        $tierHelpPatterns = ['ไปทางไหน', 'ไปยังไง', 'เริ่มยังไง', 'เริ่มไง', 'เริ่มตรงไหน', 'ต่อยังไง', 'ทำไงต่อ', 'ยังไงต่อ', 'เอาไง', 'ไม่เข้าใจ', 'งง', 'ทำไง'];
        $looksLikeTierHelp = false;
        foreach ($tierHelpPatterns as $hp) {
            if (mb_strpos($textLower, $hp) !== false) {
                $looksLikeTierHelp = true;
                break;
            }
        }

        // 🗣️ (2026-06-27) ขยาย gate — ระหว่าง "เลือกแพคเกจ" ลูกค้าพิมพ์อะไรที่ไม่ใช่คำสั่ง
        //   ต้องให้ AI ตอบบริบท ไม่ใช่เด้งเมนูซ้ำ (เจ้าของสั่ง: "รับข้อความเพื่อจำแนกบริบท
        //   ไม่ใช่เอาแต่ส่งกล่อง — เช่น 'ธนาคาร' / 'ไม่มีเงิน' ต้องคุยตอบปกติ")
        //   เดิมจับแค่ tierHelp + meta/chitchat → "ไม่มีเงิน"/ประโยคทั่วไป หลุดไปกล่อง
        //   เพิ่ม: looksLikeCustomerExcuseOrLifeUpdate (ไม่มีเงิน/ไฟดับ/รอแป๊บ/แพงไป...)
        //          + catch-all ≥ 6 ตัวอักษร (คำสั่ง 39/99/celtic/qr ถูกจับไปหมดแล้วด้านบน)
        $trimmedLen = mb_strlen(trim($messageText));
        $looksSubstantive = $looksLikeTierHelp
            || (method_exists($this, 'looksLikeMetaOrChitchat')
                && $this->looksLikeMetaOrChitchat($messageText))
            || (method_exists($this, 'looksLikeCustomerExcuseOrLifeUpdate')
                && $this->looksLikeCustomerExcuseOrLifeUpdate($messageText))
            || $trimmedLen >= 6;

        // 🛡️ Safe guard — ถ้า buildAIAssistedStepReminder ไม่มี (trait isolation)
        //   หรือ throw exception → fallback re-show menu ปกติ ไม่ทำให้ flow crash
        try {
            if ($looksSubstantive) {
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
            $reshowMessage .= "🔹 พิมพ์ *\"{$deepPriceInt}\"* — ดูพื้นดวง {$deepPriceInt} บาท\n"
                ."    📅 วันเกิด + 🃏 ไพ่ + 💬 คุยกับแม่หมอ {$deepWindow} นาที\n\n";
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
        // 🌍 (2026-06-03) Foreign-customer gate (defense ที่ chokepoint สร้างบิล Celtic)
        //   ครอบ path ที่ไม่ผ่าน startDeepReadingFlow (เช่น พิมพ์ "99" ตอน TIER_CHOICE)
        $fcUserId = $reading->facebook_user_id ?: $reading->platform_user_id;

        // 🪬 (2026-06-24) โหมดคุณไสย์ — carrier flag จากปุ่ม FB (force_tier=celtic_blackmagic) / single-click bypass
        //   → ติดธงบน reading ที่จะกลายเป็นบิล Celtic ให้ถาวร (กันธงหายถ้า reading object เปลี่ยนระหว่าง start flow)
        if ($fcUserId && (bool) ($this->settings->enable_celtic_black_magic_mode ?? true)
            && \Illuminate\Support\Facades\Cache::get('fortune:force_black_magic:'.$fcUserId)
            && ! $reading->getConversationState('black_magic_mode', false)) {
            $reading->setConversationState('black_magic_mode', true);
        }

        if ($fcUserId) {
            $fcPlat = $reading->platform ?: $this->detectPlatformFromUserId((string) $fcUserId);
            if ($this->isForeignCustomerBlocked($fcPlat, (string) $fcUserId, null, $reading->facebook_user_name)) {
                \Illuminate\Support\Facades\Log::info('Fortune: foreign customer blocked — service off (Celtic chokepoint)', [
                    'reading_id' => $reading->id,
                ]);
                $reading->update(['conversation_status' => FortuneReading::STATUS_COMPLETED]);

                return $this->foreignServiceClosedResponse();
            }
        }

        // 📜 (2026-06-06) Consent Gate — กล่องกติกาก่อนสร้างบิล Celtic 99
        //   ครอบทุกทางเข้า (กดปุ่ม/พิมพ์ "99"/"celtic") — มาก่อนเลือกวิธีชำระ + ก่อน UPA
        //   เด้งทุกครั้ง (Cache::pull กิน flag) เว้นเพิ่งกด "พร้อมบูชาครู" / ลูกค้าจ่ายแล้ว
        if ($fcUserId && ($consentGate = $this->consentGateOrNull((string) $fcUserId, 'celtic', $reading))) {
            return $consentGate;
        }

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
                    FortuneReading::billTimeoutMinutes() // ⏰ (2026-06-12) ตาม setting default 3 ชม. (เหมือน 39฿)
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
            $maxQ = (int) ($this->settings->celtic_cross_max_questions ?? 0);
            $qLimitTxt = $maxQ > 0 ? "{$maxQ} คำถาม" : 'ไม่จำกัด';

            // ⏰ (2026-06-12) อายุบิลตาม setting (default 3 ชม. — เดิมฮาร์ดโค้ด "30 นาที")
            $billTimeout = FortuneReading::billTimeoutMinutes();
            $billTimeoutLabel = $billTimeout >= 60
                ? intdiv($billTimeout, 60).' ชั่วโมง'.($billTimeout % 60 > 0 ? ' '.($billTimeout % 60).' นาที' : '')
                : $billTimeout.' นาที';

            // 🛡️ (2026-06-12) Bill-Troll Guard — แนบคำเตือนถ้าลูกค้ามีประวัติไม่ชำระ 2 ครั้งใน 3 วัน
            $trollWarning = method_exists($this, 'appendTrollWarningIfNeeded')
                ? $this->appendTrollWarningIfNeeded($reading)
                : '';

            return [
                'action' => 'celtic_pending_payment',
                // 🌙 (2026-05-23 v3) ประกาศกติกาให้ชัดในบิล — 5 คำถาม / 15 นาที
                // 🆕 (2026-05-31) แนบบัญชีหลัก + ชี้ไปบับเบิลเลขบัญชี (renderer ส่งเลขล้วนแยก กดค้างก๊อปง่าย)
                'message' => "🔮 *ดูดวงไพ่ยิปซีเต็มสำรับ Celtic Cross*\n\n"
                    ."✨ ค่าบูชาครู: {$baseAmountStr} บาท\n"
                    ."🃏 เปิดไพ่ 10 ใบ ตำแหน่งครบสายพันปี\n"
                    ."💬 คุยกับแม่หมอได้ *{$qLimitTxt} ภายใน {$qaWindow} นาที* (นับจากคำทำนายแรก)\n"
                    ."⚡ ตอบทันที ไม่มีรอ — พิมพ์ปุ๊บแม่หมอตอบปั๊บ\n"
                    ."🖼️ ได้รับภาพ Celtic Cross spread สวยๆ ส่งให้ตอนจบทำนาย เป็นที่ระลึก\n\n"
                    ."──────────────────────\n"
                    ."💸 *ค่าบูชาครูสำหรับบิลนี้: {$payAmount} บาท*\n"
                    ."(ต้องโอนทศนิยมตรงเป๊ะ ระบบใช้ทศนิยมจับคู่บิลเจ้าชะตา)\n\n"
                    ."📲 *สแกน QR ในภาพได้เลย* — บิลหมดอายุใน {$billTimeoutLabel}\n"
                    ."หรือโอนเข้าเลขบัญชีด้านล่างก็ได้นะคะ ✨\n\n"
                    .$this->getBankAccountsListMessage(true)
                    ."\n💚 *กรุณาโอนให้ตรง ตรงจุดทศนิยมด้วย* เพื่อเปิดไพ่ยิปซี 10 ใบ ค่ะ ✨\n"
                    // 🃏 (2026-06-17) แจ้งขั้นต่อไปแบบเป็นธรรมชาติ — โอนครบแล้วรอแม่หมอเปิดไพ่ 10 ใบให้
                    .'🃏 เมื่อโอนครบแล้วตามยอด รอเปิดไพ่ 10 ใบกับแม่หมอได้เลยนะคะ ✨'
                    .$trollWarning,
                'reading' => $reading,
                'celtic_price' => $payAmount,
                'celtic_base_price' => $basePrice,
                'celtic_bill_reference' => $reading->bill_reference,
                'unique_payment_amount' => $uniqueAmount,
                'payment_qr_url' => $qrImageUrl, // ✅ FortuneChannelManager จะส่งภาพ QR ออก
                'show_qr' => true,
                // 🆕 (2026-05-31) เลขบัญชีหลัก ส่งเป็นบับเบิลเดี่ยวให้ลูกค้าก๊อปง่าย (renderer จัดการ)
                'copyable_account' => $this->getCopyableAccountNumber(),
            ];
        } catch (\Throwable $e) {
            Log::error('Celtic: startCelticCrossFlow error', [
                'reading_id' => $reading->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'action' => 'celtic_bill_creation_failed',
                'message' => "🌙 ขอแม่หมอสักครู่นะคะ\nกรุณาลองใหม่อีกครั้งใน 10 วินาทีค่ะ",
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
        // 🌍 (2026-08-23) ตอบด่านยืนยันจ่ายบัตร (ถ้าถามค้างไว้) — ต้องมาก่อนตัวจับ keyword
        if (method_exists($this, 'handleForeignCardConfirmReply')) {
            if ($cardConfirm = $this->handleForeignCardConfirmReply($reading, $messageText)) {
                return $cardConfirm;
            }
        }

        // 🌍 (2026-08-23) ลูกค้าต่างประเทศขอจ่ายบัตร ระหว่างบิล Celtic ค้างอยู่
        //   ต้องอยู่ **ก่อน** maybePresentPaymentInfo — ไม่งั้นโดนกล่อง "เลขบัญชี/QR" กลืนไปก่อน
        //   เคสที่มา: บิล FTU-260822-U7900 — เมนูเลือกวิธีจ่ายทำงานก่อนสร้างบิลเท่านั้น
        //   พอบิลเกิดแล้วไม่มีทางกลับเข้าเลนบัตรเลย
        //   → ถามยืนยันก่อนเสมอ (สลับเลน = ปิดบิลไทยทิ้ง + คิดค่าบริการเพิ่ม)
        if (method_exists($this, 'looksLikeCardPaymentRequest')
            && method_exists($this, 'isStripeForeignFallbackAvailable')
            && method_exists($this, 'askForeignCardConfirm')
            && $this->looksLikeCardPaymentRequest($messageText)
            && $this->isStripeForeignFallbackAvailable()) {
            // 🛡️ จ่าย QR ไปแล้วระหว่างพิมพ์ → ห้ามเปิดเลนบัตรซ้ำ
            $reading->refresh();
            if (! $reading->is_paid) {
                return $this->askForeignCardConfirm($reading);
            }
        }

        // 💳 (2026-05-14) ลูกค้ารอจ่าย Celtic แต่ขอเลขบัญชี/QR — ส่งช่องทางทันที ไม่ปิดบิล
        if (method_exists($this, 'maybePresentPaymentInfo')) {
            if ($paymentInfo = $this->maybePresentPaymentInfo($messageText, $reading->facebook_user_id, $reading)) {
                return $paymentInfo;
            }
        }

        // 🔓 ยกเลิก
        // 🩹 (2026-05-08 audit fix CRIT-1b) — route ผ่าน closeAllActiveConversations
        //   เพื่อ cancel UPA Celtic + FCM push + wisdom DM ครบ
        if ($this->matchesExactKeyword($messageText, ['ยกเลิก', 'cancel', 'stop', 'ไม่จ่าย'])) {
            $userId = $reading->facebook_user_id ?: ($reading->line_user_id ?: $reading->platform_user_id);
            if (! empty($userId) && method_exists($this, 'closeAllActiveConversations')) {
                // 📜 (2026-06-06) ส่ง messageText → แยกเจตนายกเลิก (เบี้ยว → รูป+เตือนแรง / สุดวิสัย → ปกติ)
                $this->closeAllActiveConversations($userId, $messageText);
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

        // 🔍 (2026-05-31) Fuzzy Payment — ลูกค้าเคลม "โอนแล้ว/เช็คสถานะ" แต่ยอดไม่ตรงเป๊ะ
        //   เคสจริง: บิล 99.77 แต่ลูกค้าโอน 99 กลม / 100 (เกิน) → exact match พลาด → ค้างตลอด
        //   เดิม Celtic ไม่ต่อสาย fuzzy เลย (ต่อแค่ Deep handlePendingPayment) → ระบบ "โอนไม่ตรงยอด" ตายสำหรับ 99฿
        //   ตอนนี้: เคลมจ่าย + ยังไม่ตัดบิล → ลอง fuzzy (auto-approve / ask-confirm ใช่-ไม่ใช่ / push admin)
        //   ⚠️ SCB SMS ไม่มีชื่อผู้โอน → name score ต่ำ → ส่วนใหญ่จะถามยืนยัน "ใช่/ไม่ใช่" ก่อนตัด (ปลอดภัย)
        if (method_exists($this, 'isPaymentClaimRequest')
            && method_exists($this, 'tryFuzzyAutoApproveOnClaim')
            && $this->isPaymentClaimRequest($messageText)) {
            $celticUpa = $reading->uniquePaymentAmount;
            if ($celticUpa) {
                $fuzzyResult = $this->tryFuzzyAutoApproveOnClaim($reading, $celticUpa);
                if ($fuzzyResult !== null) {
                    return $fuzzyResult;
                }
            }

            // 🧾 (2026-05-31) SlipOK on-ping — SMS ไม่พบ → ตรวจสลิป (รวม look-back 10 ข้อความ)
            //   askIfNoSlip=true: หาสลิปไม่เจอ → ขอให้ส่งสลิป
            if (empty($reading->slipok_verified_at)
                && method_exists($this, 'trySlipOkVerifyForReading')) {
                $slipResult = $this->trySlipOkVerifyForReading($reading, null, null, true);
                if ($slipResult !== null) {
                    return $slipResult;
                }
            }
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

        // 🔄 (2026-06-12) ลูกค้าขอเปลี่ยนแพคเกจ (Celtic 99 → Deep 39) — รับฟัง ไม่ดันบิลเดิม
        //   ยกเลิกบิลเดิม (reason=package_switch ไม่นับ strike) + เปิดบิลใหม่ทันที
        if (method_exists($this, 'detectTierSwitchRequest')
            && method_exists($this, 'switchPendingBillTier')
            && ($switchTarget = $this->detectTierSwitchRequest($messageText, 'celtic')) !== null) {
            return $this->switchPendingBillTier($reading, $switchTarget);
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
        // 💬 (2026-06-12) ขยาย: ข้อความยาว ≥ 10 ตัวอักษร = ลูกค้าคุย → AI ตอบบทสนทนา
        //   (บิลอายุ 3 ชม. — "คุยก็ต้องคุยก่อน" ไม่ใช่ส่งกล่องจ่ายเงินซ้ำเดิม)
        $aiPrefix = '';
        $shouldTriggerAi = method_exists($this, 'looksLikeMetaOrChitchat')
            && (
                $this->looksLikeMetaOrChitchat($messageText)
                || (method_exists($this, 'looksLikeCustomerExcuseOrLifeUpdate')
                    && $this->looksLikeCustomerExcuseOrLifeUpdate($messageText))
                || mb_strlen(trim($messageText)) >= 10
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

        // 🧹 (2026-06-28) จ่าย/กู้บิล Celtic แล้ว → ปิดบิล "ขายใหม่" ที่ค้างชนกัน
        //   (เคส FTU-260628-W2607: บิลหมดอายุ → ลูกค้าทักใหม่ → บอทเปิดบิลใหม่ → โอนเข้าบิลเก่า → 2 flow ซ้อน)
        $this->cancelCompetingPrePaymentReadings($reading, 'celtic_payment_confirmed');

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

        // 🔢 (2026-08-17 owner) พาดหัวต้องบอกตรงๆ ว่า "เปิดไพ่ใบที่เท่าไหร่"
        //   เดิม "ใบที่ N/10" — ชัดอยู่แล้วแต่ไม่ได้บอกว่ากำลัง "เปิด" ใบนั้น
        $message = "🃏✨ *เปิดไพ่ใบที่ {$count}/10 — ตำแหน่ง [{$positionName}]*\n\n"
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
            // 🎨 (2026-07-25) ข้อมูลไพ่แบบ structured — LINE ใช้ประกอบ Flex "รูป+คำแนะนำในกล่องเดียว"
            //   (FB ใช้ message เดิม — ไม่กระทบ). ถ้า field นี้ไม่มี → LINE fallback รูป+ข้อความแบบเดิม
            'celtic_card' => [
                'position' => $position,
                'position_name' => $positionName,
                'card_name_th' => $cardNameTh,
                'card_name_en' => $cardNameEn,
                'reversed_label' => $reversed,
                'meaning' => $meaning,
                'next_prompt' => $nextPrompt,
                'picked_count' => $count,
            ],
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

        return "🃏 *ใบถัดไป — ใบที่ {$next}/10 · ตำแหน่ง [{$name}]*\n"
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

        $message = "🌙 *พบบิล Celtic Cross ของคุณ{$name}ที่ยังรอโอน*\n\n"
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
                ."💚 ค่าครูเดิมยังใช้ได้ — ไม่ต้องโอนซ้ำ\n"
                ."📋 เลขบิล: {$billRef}\n\n"
                ."═══════════════════════\n\n";
        } else {
            $header = "✨ *พบบิลของคุณ{$name}ที่ยังใช้สิทธิ์ไม่ครบ*\n"
                ."💚 ค่าครูเดิมยังใช้ได้ — ไม่ต้องโอนซ้ำ\n"
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
                $maxQ = (int) ($this->settings->celtic_cross_max_questions ?? 0);
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
                        ."💬 *พิมพ์คำถามได้เลยค่ะ* — หรือพิมพ์ *\"เลิก\"* เมื่อพร้อมจบและรับสรุป ✨\n\n"
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
                    .'📜 หรือพิมพ์ *"เลิก"* เมื่อพร้อมจบรอบ';

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

        // 🎂 (2026-06-08) Celtic 99 — ถามวันเดือนปีเกิดเป็นคำถามแรก (บังคับ) + ทำนายพื้นดวงผสมไพ่
        //   user spec: "คำถามแรก = วันเดือนปีเกิด. ถ้ายังไม่เคยมีในฐาน 39 (ไม่เคยให้วันเกิด/ทำนาย)
        //   ให้ทำนายพื้นดวงก่อน แล้วค่อยนำพื้นดวง (ดาวเจ้าชนะแบบที่ 39 ใช้) มาทำนายร่วมกับไพ่ต่อไป"
        //     • เคยให้วันเกิด/ทำ 39 แล้ว → ใช้เลย ไม่ถามซ้ำ (Q&A ฉีด astrology อัตโนมัติใน buildFollowupPrompt)
        //     • ไม่เคย → ถามวันเกิด (ยังไม่ start QA window กันกินเวลา 15 นาที) → ได้แล้วทำนายพื้นดวง 1 ชุด
        //   องค์ความรู้แม่หมอ (FortuneKnowledge RAG) + AdminQA RAG ติดมากับ askQuestion อยู่แล้ว (ไม่ bypass)
        //   fail-safe: ถ้าด่านนี้พัง → ไหลต่อทักทายปกติ (card-first) ไม่ให้ flow เรือธงแตก
        try {
            if ($this->celticBirthdateFirstEnabled()
                && empty($reading->getConversationState('celtic_birthdate_text'))
                && empty($reading->getConversationState('celtic_birthdate_skipped'))) {

                $priorBirth = $this->findPriorBirthDateForCeltic($reading);
                if ($priorBirth !== null) {
                    // เคยให้วันเกิด/ทำ 39 มาแล้ว → ใช้เลย ไม่ถามซ้ำ
                    $reading->setConversationState('celtic_birthdate_text', 'เจ้าชะตาเกิด '.$priorBirth);
                    $reading->setConversationState('celtic_birthdate_from_prior', true);
                    // 🌟 (2026-06-19 FTU-260619-C9002) ลูกค้าซื้อซ้ำ (วันเกิดอยู่ในฐาน) ต้องได้ "พื้นดวงเปิดตัว + เรื่องเด่น"
                    //   เหมือน path พิมพ์วันเกิดเอง — เดิม path นี้ "ไหลต่อทักทายเฉยๆ" ไม่ตั้ง celtic_base_chart
                    //   → reading 7238 (C9002) ข้ามพื้นดวง = เรื่องเด่นหาย. ตั้ง flag ที่นี่ แล้วยิงพื้นดวง
                    //   proactive ตอนสร้าง opening ด้านล่าง (ไม่กินสิทธิ์คำถามแรกของลูกค้า — base chart = seq 1 เหมือน manual)
                    //   🆕 (2026-06-23) ไม่ start QA window ที่นี่ — เริ่มจับเวลาที่คำถามจริงข้อแรกของลูกค้า (Q2)
                    $reading->setConversationState('celtic_base_chart', true);
                    // ไหลต่อไปสร้าง opening ด้านล่าง — จะยิงพื้นดวงเปิดตัวแทนทักทายเฉยๆ
                } else {
                    // ไม่เคยมีในฐาน → ถามวันเกิดก่อน (ยังไม่ตั้ง celtic_first_answered_at = ยังไม่ start window)
                    $reading->setConversationState('celtic_birthdate_pending', true);
                    $reading->setConversationState('celtic_birthdate_attempts', 0);

                    return [
                        // reuse 'celtic_all_picked' → ส่งรูปไพ่ใบสุดท้าย + ข้อความขอวันเกิด (render เดิม ปลอดภัย)
                        'action' => 'celtic_all_picked',
                        'message' => $lastCardMessage."\n\n──────────────────────\n\n"
                            .$this->buildCelticAskBirthdateMessage($reading),
                        'reading' => $reading,
                        'tarot_image_url' => $lastCardImage,
                    ];
                }
            }
        } catch (\Throwable $bdErr) {
            \Log::warning('Celtic: birthdate-first step fail (fallback to normal opening)', [
                'reading_id' => $reading->id,
                'error' => $bdErr->getMessage(),
            ]);
            try {
                $reading->setConversationState('celtic_birthdate_pending', false);
            } catch (\Throwable $e) {
                // ignore
            }
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
            // 🌟 (2026-06-19 FTU-260619-C9002) from_prior → ยิง "พื้นดวงเปิดตัว" (เรื่องเด่น+พื้นฐานดวง)
            //   proactive แทนทักทายเฉยๆ (ping loading ครอบอยู่แล้ว) — ไม่งั้นลูกค้าซื้อซ้ำข้ามพื้นดวง = เรื่องเด่นหาย
            //   askQuestion เคลียร์ flag celtic_base_chart ให้เอง (กันคำถามถัดไปยิงซ้ำ)
            if ($reading->getConversationState('celtic_base_chart')) {
                // 🔒 (2026-06-19 bug-hunt) Race guard — ตั้ง STATUS_CELTIC_GENERATING ก่อนยิงพื้นดวง
                //   พื้นดวง = หลาย AI call (30-90s) > mutex 'fortune:processing' TTL 30s → mutex หมดอายุกลางคัน
                //   ถ้าสถานะยังเป็น AWAITING_QUESTION (ไม่อยู่ใน AI_GENERATING_STATUSES) ข้อความลูกค้าที่พิมพ์
                //   ระหว่างนั้นจะหลุด IN-PREDICTION Hard Guard → เข้า askQuestion ซ้ำ (celtic_base_chart ยัง true)
                //   → ยิงพื้นดวงซ้ำ + คำถามจริงของลูกค้าหาย. mirror handleCelticAwaitingQuestion (L~2215)
                //   → คืน AWAITING_QUESTION เสมอใน finally (พร้อมรับคำถามจริงต่อ ทั้ง success/fail)
                try {
                    $reading->update(['conversation_status' => FortuneReading::STATUS_CELTIC_GENERATING]);
                } catch (\Throwable $stErr) {
                    // non-blocking
                }
                try {
                    $openingResult = $service->askQuestion(
                        $reading,
                        // 🪬 (2026-06-30) โหมดคุณไสย์ → พื้นดวงเปิดตัวเป็นเรื่องของ/คุณไสย์ (ไม่ใช่ รัก/งาน/เงินทั่วไป)
                        $this->celticBaseChartQuestion($reading),
                        true // 🆕 (2026-06-23) พื้นดวงเปิดตัว = ไม่เริ่มจับเวลา (เริ่มที่คำถามจริงข้อแรก)
                    );
                    // ถ้าพื้นดวง fail → fallback ทักทายปกติ (กัน opening แตก + เคลียร์ flag กันค้าง)
                    if (empty($openingResult['success']) || trim((string) ($openingResult['response'] ?? '')) === '') {
                        try {
                            $reading->setConversationState('celtic_base_chart', false);
                        } catch (\Throwable $e) {
                            // ignore
                        }
                        $openingResult = $service->generateOpeningGreeting($reading);
                    }
                } catch (\Throwable $bcErr) {
                    // askQuestion ปกติ catch เองคืน success=false แต่ถ้า throw ก่อน try ภายใน (เช่น create row พัง)
                    //   → กัน opening แตก: เคลียร์ flag + fallback ทักทาย (mirror handleCelticAwaitingQuestion)
                    \Log::warning('Celtic: from-prior base chart proactive ล้มเหลว → fallback ทักทาย', [
                        'reading_id' => $reading->id,
                        'error' => $bcErr->getMessage(),
                    ]);
                    try {
                        $reading->setConversationState('celtic_base_chart', false);
                    } catch (\Throwable $e) {
                        // ignore
                    }
                    $openingResult = $service->generateOpeningGreeting($reading);
                } finally {
                    // คืนสถานะ AWAITING_QUESTION เสมอ — ลูกค้าพร้อมพิมพ์คำถามจริงต่อ (opening จบที่สถานะนี้)
                    try {
                        $reading->update(['conversation_status' => FortuneReading::STATUS_CELTIC_AWAITING_QUESTION]);
                    } catch (\Throwable $stErr) {
                        // non-blocking
                    }
                }
            } else {
                $openingResult = $service->generateOpeningGreeting($reading);
            }
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

        // 🎂 (2026-06-08) เคยมีวันเกิดในฐาน (39/เคยทำ) → บอกว่าจำได้ + จะอ่านไพ่ผสมดวงดาวให้
        if ($reading->getConversationState('celtic_birthdate_from_prior')) {
            $openingText = "🎂 แม่หมอจำวันเกิดของเจ้าชะตาได้แล้วนะคะ — จะอ่านไพ่ผสมกับดวงดาว (ดาวเจ้าชนะ) ให้เลยค่ะ ✨\n\n"
                .$openingText;
        }

        // 🆕 (2026-06-23, owner) ไม่ start QA window ที่ opening/พื้นดวงอีกต่อไป
        //   เดิม: set celtic_first_answered_at ตรงนี้ (window เริ่มที่ Q1/พื้นดวง)
        //   ใหม่: เริ่มจับเวลาที่ "คำถามจริงข้อแรกของลูกค้า" (Q2) ผ่าน markCelticAnswered(startQaWindow=true)
        //        → พื้นดวงเปิดตัว (Q1 auto) ฟรี ไม่กินเวลา 15 นาที

        // 🌙 (2026-05-23 v3) Footer — ประกาศกติกาให้ชัด (5 คำถาม / 15 นาที)
        //    user spec: "ต้องบอกกติการให้ชัดทุกที่"
        $maxQ = (int) ($this->settings->celtic_cross_max_questions ?? 0);
        $qLimitLine = $maxQ > 0
            ? "❓ ถามได้ *{$maxQ} คำถาม* ภายใน *{$qaWindow} นาที*\n"
            : "⏳ คุยกับแม่หมอได้ *{$qaWindow} นาที* นับจากนี้\n";

        $footer = "\n\n──────────────────────\n"
            ."💬 *แม่หมอพร้อมทำนาย พิมพ์คำถามได้เลยค่ะ* ✨\n\n"
            .$qLimitLine
            ."⚡ ตอบทันที ไม่มีรอ — พิมพ์ปุ๊บแม่หมอตอบปั๊บ\n"
            .'🖼️ ภาพไพ่จัดเรียง — แม่หมอจะส่งตอนจบเป็นที่ระลึก';

        // 🔔 (2026-06-23) ลูกค้าพร้อมพิมพ์คำถามแล้ว (พื้นดวง/ทักทายส่งแล้ว) → จุดอ้างอิง nudge 1 นาที
        //   (เฉพาะ path นี้ = prior-birthdate/ทักทาย ; path ถามวันเกิดยัง return ก่อนหน้า ยังไม่ ready)
        try {
            $reading->setConversationState('pro_session_ready_at', now()->toIso8601String());
        } catch (\Throwable $e) {
            // non-blocking
        }

        return [
            'action' => 'celtic_all_picked',
            'message' => $lastCardMessage."\n\n──────────────────────\n\n".$openingText.$footer,
            'reading' => $reading,
            'tarot_image_url' => $lastCardImage,
        ];
    }

    /**
     * 🎂 (2026-06-08) เปิด/ปิดด่านถามวันเกิดก่อนใน Celtic 99 (default เปิด)
     *   ถ้า column ยังไม่ถูก migrate → ?? true → เปิดไว้ (graceful)
     */
    protected function celticBirthdateFirstEnabled(): bool
    {
        return (bool) ($this->settings->enable_celtic_birthdate_first ?? true);
    }

    /**
     * 🎂 (2026-06-08) หาวันเกิดที่ลูกค้าเคยให้/เคยทำ 39 มาก่อน (เพื่อไม่ถามซ้ำ)
     *
     * ⚠️ fortune_readings ไม่มี column line_user_id — LINE ใช้ platform_user_id
     *    (อ้างอิง FortuneReading::scopeForUser + persona lookup)
     *
     * @return string|null วันเกิด d/m/Y (ค.ศ.) หรือ null ถ้าไม่เคยมี
     */
    protected function findPriorBirthDateForCeltic(FortuneReading $reading): ?string
    {
        $hit = $this->findPriorBirthdateHitForCeltic($reading);

        // ⚠️ ต้องคืน d/m/Y เท่านั้น — ปลายทางคือ regex ของ ThaiAstrologyService
        //    ที่รับเฉพาะ d/m/YYYY ส่ง Y-m-d ไปคือมองไม่เห็นเงียบ ๆ
        return $hit === null ? null : $hit['date']->format('d/m/Y');
    }

    /**
     * 🎂 (2026-08-21) เวอร์ชันที่คืน "ที่มา" มาด้วย — ใช้เขียนข้อความยืนยันแบบซื่อสัตย์
     *
     * ของเดิมค้นแต่ `fortune_readings` เหมือนฝั่ง Deep 39 เป๊ะ (คนละไฟล์ คนละเมธอด
     * แต่บั๊กเดียวกัน) ⇒ ลูกค้าที่ให้วันเกิดไว้ตอนขอดวงฟรีรายวันถูกถามซ้ำตอนจ่าย 99
     *
     * @return array{ymd:string,date:\Carbon\Carbon,source:string,reading_id:int|null}|null
     */
    protected function findPriorBirthdateHitForCeltic(FortuneReading $reading): ?array
    {
        return \App\Services\Fortune\BirthdateResolver::forReading($reading);
    }

    /**
     * 🎂 (2026-06-08) ข้อความขอวันเกิด (ครั้งแรก หลังเปิดไพ่ครบ 10 ใบ)
     */
    protected function buildCelticAskBirthdateMessage(FortuneReading $reading): string
    {
        $name = $reading->resolveCustomerName();

        return "🌙✨ แม่หมอจันทราเปิดไพ่ Celtic Cross ครบทั้ง 10 ใบให้คุณ{$name}แล้วค่ะ ✨\n\n"
            ."🎂 ก่อนเริ่มทำนาย แม่หมอขอ *วันเดือนปีเกิด* ของเจ้าชะตาก่อนนะคะ\n"
            ."   แม่หมอจะได้ดู *ดาวเจ้าชนะ* (พื้นดวงจากวันเกิด) ผสมกับพลังไพ่ทั้ง 10 ใบ — ทำนายแม่นและลึกขึ้นค่ะ\n\n"
            ."📅 พิมพ์มาได้เลย เช่น *29 มกราคม 2516* หรือ *29/01/2516*\n"
            .'(ถ้าไม่สะดวกบอก พิมพ์ว่า *ข้าม* แม่หมอจะดูจากไพ่ให้ค่ะ)';
    }

    /**
     * 🎂 (2026-06-08) ลูกค้าขอข้ามการให้วันเกิด → ดูจากไพ่อย่างเดียว (card-first)
     */
    protected function looksLikeBirthdateSkip(string $text): bool
    {
        $t = mb_strtolower(trim($text), 'UTF-8');
        if ($t === '') {
            return false;
        }
        $skipWords = ['ข้าม', 'ไม่บอก', 'ไม่อยากบอก', 'ไม่ให้', 'ไม่ระบุ', 'จำไม่ได้',
            'ไม่ทราบ', 'ไม่รู้', 'ไม่สะดวก', 'ดูจากไพ่', 'ไพ่อย่างเดียว', 'skip'];
        foreach ($skipWords as $w) {
            if (mb_strpos($t, $w) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * 🎂 (2026-06-08) เริ่มนับ QA window ถ้ายังไม่เริ่ม — เรียกหลังได้/ข้ามวันเกิด
     *   (กันด่านวันเกิดกินเวลา 15 นาทีของลูกค้า — window เริ่มหลังด่านนี้จบ)
     */
    protected function startCelticQaWindowIfNeeded(FortuneReading $reading): void
    {
        try {
            if (empty($reading->celtic_first_answered_at)) {
                $reading->update(['celtic_first_answered_at' => now()]);
            }
            // 🎂 (2026-06-08) align Pro Session avatar timer กับ QA window — กันด่านวันเกิดกินเวลา
            //   (enterProSession ตั้ง pro_session_started_at ตั้งแต่เปิดไพ่ครบ → reset ให้เริ่มนับตรงนี้)
            if ($reading->getConversationState('pro_session_active')) {
                $reading->setConversationState('pro_session_started_at', now()->toIso8601String());
            }
        } catch (\Throwable $e) {
            \Log::warning('Celtic: start QA window fail (non-blocking)', [
                'reading_id' => $reading->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 🪬 (2026-06-30 FTU-260630-M8981) คำถาม "พื้นดวงเปิดตัว" สังเคราะห์อัตโนมัติ
     *
     * เคสจริง reading 8247 (โหมดดูคุณไสย์): คำถามพื้นดวงเดิมขอ "นิสัย+ความรัก+งาน+เงิน+สุขภาพ"
     *   → พื้นดวงเปิดตัวไหลไปเรื่องรัก/งานทั่วไป ทั้งที่ลูกค้าเลือก "ดูคุณไสย์" → หลุดเรื่อง
     *
     * เมื่อ black_magic_mode forced → ถามพื้นดวงเป็นเรื่องของ/คุณไสย์โดยเฉพาะ
     *   (askQuestion จะเห็นคำถามนี้ + isBlackMagicModeForced=true → buildBlackMagicDirective forced เปิดเต็ม)
     */
    protected function celticBaseChartQuestion(FortuneReading $reading): string
    {
        try {
            if (app(CelticCrossService::class)->isBlackMagicModeForced($reading)) {
                return 'ช่วยอ่านพื้นดวงเรื่อง "ของ/คุณไสย์" ให้เจ้าชะตาหน่อยค่ะ — ล็อกพลังไพ่ทั้ง 10 ใบทะลุของ '
                    .'ผสมดาวเจ้าชนะ (วันเดือนปีเกิด): ตอนนี้โดนของ/คุณไสย์/เสน่ห์/ถูกกระทำอะไรไหม ใครทำ มาทางไหน '
                    .'หนักแค่ไหน มีเกราะ/สิ่งศักดิ์สิทธิ์คุ้มครองไหม และจะแก้/ถอน/ป้องกันให้รอดยังไง';
            }
        } catch (\Throwable $e) {
            // non-blocking — เช็ค forced ไม่ได้ → ใช้คำถามพื้นดวงปกติ
        }

        return 'ช่วยอ่านพื้นดวงรวมของเจ้าชะตาให้หน่อยค่ะ โดยดูจากดาวเจ้าชนะ (วันเดือนปีเกิด) '
            .'ผสมกับภาพรวมไพ่ทั้ง 10 ใบที่เปิด — ทั้งนิสัยพื้นฐาน จุดเด่นจุดอ่อน และภาพรวม '
            .'ความรัก การงาน การเงิน สุขภาพ ในช่วงนี้';
    }

    /**
     * 🎂 (2026-06-08) ขั้นรับวันเกิดของ Celtic 99 (คำถามแรกบังคับ — ข้ามได้ถ้าไม่ให้)
     *
     * @return array|string
     *                      - array  = จัดการจบแล้ว (ถามวันเกิดซ้ำ / ข้ามไป card-first) → ส่งกลับลูกค้าเลย
     *                      - string = ได้วันเกิดแล้ว = คำถามสังเคราะห์ "พื้นดวงรวม" → ให้ไหลเข้า askQuestion ต่อ
     */
    protected function handleCelticBirthdateStep(FortuneReading $reading, string $text)
    {
        $text = trim($text);

        // 🔘 (2026-08-13) ปุ่มค้าง/คำตอบรับ ≠ ความพยายามพิมพ์วันเกิด — เช็คก่อน parse
        //   เคสจริง reading 11055 (ยุวารีย์): เปิดไพ่ครบ → ระบบขอวันเกิด → ลูกค้ากดปุ่ม "พร้อม"
        //   (CELTIC_READY ที่ค้างบนการ์ดเปิดไพ่เก่า — template button กดซ้ำได้เสมอ) 2 ครั้ง
        //   → attempts ครบ 2 → auto-skip ฆ่า gate วันเกิดทั้งที่ลูกค้าไม่เคยพิมพ์ผิดเลย
        //   → sticker 👍 ที่ตามมาแย่งช่องพื้นดวง Q1 + วันเกิดจริงที่พิมพ์ทีหลังกลายเป็น "คำถาม"
        //   วางไว้บนสุด: ประหยัด AI fallback ใน parseBirthDate ด้วย (ปุ่ม/คำตอบรับไม่มีทางเป็นวันเกิด)
        if ($this->isCelticBirthdateNavAck($text)) {
            \Log::info('Celtic: birthdate step ได้ปุ่มค้าง/คำตอบรับ → ย้ำขอวันเกิด (ไม่นับ attempts)', [
                'reading_id' => $reading->id,
                'text' => mb_substr($text, 0, 30),
            ]);

            return [
                'action' => 'celtic_ask_birthdate',
                'message' => "🌙 แม่หมอรอ *วันเดือนปีเกิด* ของเจ้าชะตาอยู่นะคะ\n\n"
                    ."📅 พิมพ์มาได้เลย เช่น *29 มกราคม 2516* หรือ *29/01/2516*\n"
                    .'(ถ้าไม่สะดวกบอก พิมพ์ว่า *ข้าม* แม่หมอจะดูจากไพ่ให้ค่ะ)',
                'reading' => $reading,
            ];
        }

        // ลองอ่านวันเกิด "ก่อน" เสมอ (ตัวเดียวกับ 39 — เข้าใจไทยธรรมชาติ เช่น "29 มกราคม 2516")
        //   ⚠️ ต้อง parse ก่อนเช็คคำว่า "ข้าม" — กันเคสมีวันเกิดจริงปนคำพูดเผลอ
        //   เช่น "จำไม่ได้แน่ แต่ 29 มกราคม 2516" → ต้องอ่านวันเกิดได้ ไม่ใช่ตีเป็นข้าม
        $parsed = null;
        try {
            $parsed = $this->parseBirthDate($text);
        } catch (\Throwable $e) {
            $parsed = null;
        }

        if (! empty($parsed)) {
            // ✅ ได้วันเกิด → เก็บ + start window + สร้างคำถามพื้นดวง
            try {
                $reading->update(['birth_date' => $parsed]);
            } catch (\Throwable $e) {
                // non-blocking — เขียน birth_date ไม่ได้ ไม่ควรทำ flow แตก (ยังมี celtic_birthdate_text)
            }

            $human = $parsed;
            try {
                $human = \Carbon\Carbon::parse($parsed)->format('d/m/Y');
            } catch (\Throwable $e) {
                // ใช้ค่าดิบ
            }

            $reading->setConversationState('celtic_birthdate_text', 'เจ้าชะตาเกิด '.$human);
            $reading->setConversationState('celtic_birthdate_pending', false);
            // 🌟 (2026-06-08) flag คำทำนายพื้นดวงเปิดตัว — รอบแรกใช้โครงสร้างแบบ 39 (ดวงดาวเต็ม)
            //   ผสานไพ่ 10 ใบ + ยาว 1500-3000 (buildFollowupPrompt อ่าน flag นี้ + เคลียร์ทิ้งหลังใช้)
            $reading->setConversationState('celtic_base_chart', true);
            // 🆕 (2026-06-23) ไม่ start window ที่นี่ — เริ่มจับเวลาที่คำถามจริงข้อแรกของลูกค้า (Q2)

            \Log::info('Celtic: birthdate captured → generate พื้นดวง (39-style base chart)', [
                'reading_id' => $reading->id,
                'birth_date' => $human,
            ]);

            // คำถามสังเคราะห์ = พื้นดวงรวม (ดาวเจ้าชนะจากวันเกิด + ภาพรวมไพ่ 10 ใบ)
            //   ผ่าน askQuestion → buildFollowupPrompt → ฉีด birthAstroBlock + แม่หมอ Knowledge RAG +
            //   AdminQA RAG อัตโนมัติ (ไม่ bypass องค์ความรู้เดิม)
            //   🪬 (2026-06-30) โหมดคุณไสย์ → พื้นดวงเป็นเรื่องของ/คุณไสย์ (helper เช็ค forced ให้)
            return $this->celticBaseChartQuestion($reading);
        }

        // 🔍 (2026-06-09 FTU-260609-P3147) parse วันเกิดไม่ผ่าน — เก็บ raw input ไว้ debug
        //   เคสจริง reading 5528 (San Jun): ลูกค้าพิมพ์วันเกิดมา แต่ parseBirthDate คืน null → ถามซ้ำ
        //   → ลูกค้าเงียบ. success path มี log 'birthdate captured' อยู่แล้ว แต่ fail path ไม่มี
        //   → มองไม่เห็นว่าลูกค้าพิมพ์อะไรจริง. เติม log นี้ให้ครบ (log-only, ไม่กระทบ flow)
        \Log::info('Celtic: birthdate parse ไม่ผ่าน (raw input สำหรับ debug)', [
            'reading_id' => $reading->id,
            'platform' => $reading->platform,
            'raw_input' => mb_substr($text, 0, 160),
            'len' => mb_strlen($text),
            'is_skip' => $this->looksLikeBirthdateSkip($text),
            'attempts_so_far' => (int) $reading->getConversationState('celtic_birthdate_attempts', 0),
        ]);

        // อ่านวันเกิดไม่ได้ → ลูกค้าขอข้าม / ไม่ให้วันเกิด → ดูจากไพ่อย่างเดียว
        if ($this->looksLikeBirthdateSkip($text)) {
            $reading->setConversationState('celtic_birthdate_pending', false);
            $reading->setConversationState('celtic_birthdate_skipped', true);
            // 🆕 (2026-06-23) ไม่ start window ที่นี่ — เริ่มจับเวลาที่คำถามจริงข้อแรกของลูกค้า
            // 🔔 (2026-06-23 bug-hunt) ข้ามวันเกิด = พร้อมพิมพ์คำถามแล้ว → ตั้งจุดอ้างอิง nudge 1 นาที (ไม่มีพื้นดวง)
            try {
                $reading->setConversationState('pro_session_ready_at', now()->toIso8601String());
            } catch (\Throwable $e) {
                // non-blocking
            }

            return [
                'action' => 'celtic_ask_birthdate',
                'message' => "🌙 ได้ค่ะ ไม่เป็นไร — แม่หมอจะอ่านจากพลังไพ่ทั้ง 10 ใบให้ก็แม่นได้เช่นกัน ✨\n\n"
                    .'💬 อยากให้แม่หมอดูเรื่องอะไรก่อนดีคะ? พิมพ์มาได้เลยค่ะ',
                'reading' => $reading,
            ];
        }

        // ❌ parse ไม่ได้ + ไม่ใช่ข้าม → นับครั้ง + ถามซ้ำ / ข้ามถ้าครบ 2 ครั้ง (ไม่บล็อกลูกค้า)
        $attempts = (int) $reading->getConversationState('celtic_birthdate_attempts', 0) + 1;
        $reading->setConversationState('celtic_birthdate_attempts', $attempts);

        if ($attempts >= 2) {
            // ครบ 2 ครั้ง → เลิกถามวันเกิด ดูจากไพ่ให้
            //   🛡️ (A3) กันคำถามหล่นหาย: ถ้าลูกค้าพิมพ์ "คำถามจริง" แทนวันเกิด → ส่งคำถามนั้นเข้า
            //   askQuestion ตอบจากไพ่ทันที (ไม่ทิ้งให้พิมพ์ใหม่) — ไหลต่อเป็น card-first
            $reading->setConversationState('celtic_birthdate_pending', false);
            $reading->setConversationState('celtic_birthdate_skipped', true);
            // 🆕 (2026-06-23) ไม่ start window ที่นี่ — เริ่มจับเวลาที่คำถามจริงข้อแรกของลูกค้า

            $carry = trim($text);
            if ($carry !== '') {
                \Log::info('Celtic: birthdate 2 fails → carry ข้อความเป็นคำถาม (card-first)', [
                    'reading_id' => $reading->id,
                    'text' => mb_substr($carry, 0, 40),
                ]);

                return $carry; // ไหลเข้า askQuestion ปกติ — ตอบจากไพ่ (ยังไม่มี astrology)
            }

            // 🔔 (2026-06-23 bug-hunt) ครบ 2 ครั้ง ไม่มีคำถามแนบ = พร้อมพิมพ์คำถาม → ตั้งจุดอ้างอิง nudge
            try {
                $reading->setConversationState('pro_session_ready_at', now()->toIso8601String());
            } catch (\Throwable $e) {
                // non-blocking
            }

            return [
                'action' => 'celtic_ask_birthdate',
                'message' => "🌙 ไม่เป็นไรค่ะ — แม่หมอจะอ่านจากพลังไพ่ทั้ง 10 ใบให้ก็แม่นได้เช่นกัน ✨\n\n"
                    .'💬 อยากให้แม่หมอดูเรื่องอะไรก่อนดีคะ? พิมพ์มาได้เลยค่ะ',
                'reading' => $reading,
            ];
        }

        // 🎂 (2026-08-30) ขัดกันระหว่าง "วันในสัปดาห์ที่บอก" กับ "วันที่ที่พิมพ์" → ถามให้ตรงจุด
        //   แทนข้อความ generic ที่ไม่บอกว่าผิดตรงไหน (ลูกค้าจะพิมพ์ชุดเดิมกลับมา แล้วโดน auto-skip)
        //   ⚠️ ไม่แตะเพดาน celtic_birthdate_attempts — ลูกค้าจ่าย 99 แล้ว ห้ามเสี่ยงให้ติดวน
        //      ครบ 2 ครั้งยังข้ามไปดูจากไพ่เหมือนเดิมทุกประการ
        if (($bdConflict = $this->birthDateConflict()) !== null) {
            return [
                'action' => 'celtic_ask_birthdate',
                'message' => $this->buildBirthDayConflictQuestion($bdConflict)
                    ."\n\n".'(ถ้าไม่สะดวกบอก พิมพ์ว่า *ข้าม* แม่หมอจะดูจากไพ่ให้ค่ะ)',
                'reading' => $reading,
            ];
        }

        return [
            'action' => 'celtic_ask_birthdate',
            'message' => "🌙 แม่หมอขอ *วันเดือนปีเกิด* ของเจ้าชะตาก่อนนะคะ เพื่อดูดาวเจ้าชนะผสมกับไพ่ค่ะ\n\n"
                ."📅 พิมพ์มาได้เลย เช่น *29 มกราคม 2516* หรือ *29/01/2516*\n"
                .'(ถ้าไม่สะดวกบอก พิมพ์ว่า *ข้าม* แม่หมอจะดูจากไพ่ให้ค่ะ)',
            'reading' => $reading,
        ];
    }

    /**
     * 🔘 (2026-08-13) ข้อความนี้เป็น "ปุ่มค้าง/คำตอบรับ" ไม่ใช่ความพยายามพิมพ์วันเกิด
     *
     * "พร้อม" มาจากปุ่ม CELTIC_READY ที่ค้างบนการ์ดเปิดไพ่เก่า (template button กดซ้ำได้เสมอ)
     * คำตอบรับสั้นๆ (โอเค/ครับ/ค่ะ/ได้/👍) = ลูกค้ารับทราบ ไม่ใช่วันเกิดที่พิมพ์ผิด
     * → ทั้งหมดนี้ต้องไม่นับเป็น celtic_birthdate_attempts (กัน auto-skip ฆ่า gate วันเกิดฟรีๆ)
     *
     * เทียบแบบ exact-match ทั้งข้อความเท่านั้น — กันชนกับวันเกิดจริง/คำขอข้ามโดยสิ้นเชิง
     */
    protected function isCelticBirthdateNavAck(string $text): bool
    {
        $t = mb_strtolower(trim($text));
        if ($t === '') {
            return true;
        }

        return in_array($t, [
            'พร้อม', 'พร้อมค่ะ', 'พร้อมครับ', 'พร้อมแล้ว', 'พร้อมเลย',
            'เปิด', 'เปิดไพ่', 'เปิดเลย', 'เปิดต่อ', 'ไปต่อ', 'ต่อเลย',
            'โอเค', 'โอเคค่ะ', 'โอเคครับ', 'ok', 'okay', 'ตกลง',
            'ได้', 'ได้ค่ะ', 'ได้ครับ', 'ได้เลย',
            'ครับ', 'ค่ะ', 'คะ', 'ค่า', 'จ้า', 'จ้ะ', 'ครับผม',
            'ขอบคุณ', 'ขอบคุณค่ะ', 'ขอบคุณครับ',
            '👍', '👍🏻', '👍🏼', '👍🏽', '👍🏾', '👍🏿', '🙏', '❤️', '😊', '✨',
        ], true);
    }

    /**
     * 🎂 (2026-08-13) ข้อความนี้เป็น "วันเกิดล้วนๆ" (ไม่ใช่คำถามที่มีวันเกิดปน)
     *
     * ใช้กับ late-birthdate capture ใน Q&A (gate วันเกิดถูก skip ไปแล้ว แต่ลูกค้าเพิ่งส่งวันเกิดตามมา)
     * ต้องเข้มพอที่จะไม่แย่งคำถามจริง เช่น "แฟนเกิด 24/8/2510 เข้ากันไหม" = คำถาม (วันเกิดคนอื่น
     * + มีคำถามปน) → ต้องไม่จับ ปล่อยไหลเข้า askQuestion ตามเดิม
     */
    protected function looksLikePureBirthdateInput(string $text): bool
    {
        $t = trim($text);
        if ($t === '' || mb_strlen($t) > 45) {
            return false;
        }

        // มีร่องรอยคำถาม → เป็นคำถาม ไม่ใช่การส่งวันเกิด
        if (preg_match('/[?？]|ไหม|มั้ย|มั๊ย|หรอ|เหรอ|ยังไง|อย่างไร|อะไร|ทำไม|เมื่อไหร่|ช่วยดู|อยากรู้|ดูเรื่อง/u', $t)) {
            return false;
        }

        // พูดถึงคนอื่น → เป็นวันเกิดของคนอื่นในบริบทคำถาม ไม่ใช่วันเกิดเจ้าชะตา
        if (preg_match('/แฟน|คู่รัก|สามี|ภรรยา|เมีย|ผัว|ลูกชาย|ลูกสาว|เพื่อน|คุณแม่|คุณพ่อ|เขาเกิด|เธอเกิด/u', $t)) {
            return false;
        }

        // ⚠️ ต้องมี "โครงเลขวันที่" จริงๆ ก่อนค่อยเรียก parseBirthDate — ตัว parser มี AI fallback
        //   (parseBirthDate บรรทัดท้าย) ถ้าปล่อยข้อความแชทสั้นๆ ทั่วไปไหลเข้า = เผา AI call ทุกข้อความ
        $hasDigitDate = (bool) preg_match('/\d{1,2}[\/\-\.\s]+\d{1,2}[\/\-\.\s]+\d{2,4}/u', $t);
        $hasThaiMonthDate = (bool) preg_match(
            '/\d{1,2}\s*(?:ม\.?ค|ก\.?พ|มี\.?ค|เม\.?ย|พ\.?ค|มิ\.?ย|ก\.?ค|ส\.?ค|ก\.?ย|ต\.?ค|พ\.?ย|ธ\.?ค'
            .'|มกรา|กุมภา|มีนา|เมษา|พฤษภา|มิถุนา|กรกฎา|สิงหา|กันยา|ตุลา|พฤศจิกา|ธันวา)/u',
            $t
        );
        if (! $hasDigitDate && ! $hasThaiMonthDate) {
            return false;
        }

        try {
            return ! empty($this->parseBirthDate($t));
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 🗺️ (2026-06-08) สร้าง/ดึง "แผนที่ดาวชะตา" (birth chart) ของ Celtic — ส่งคู่ภาพไพ่ตอนสรุป
     *
     * user spec: "ตอนสรุปให้ส่งแผนที่ดาวชะตาไปกับแผนภูมิภาพไพ่พร้อมกัน"
     * ใช้ตัวสร้างเดียวกับ 39 (FortuneChartService::generateBirthChart) + cache ลง reading_image_url
     * (กัน gen ซ้ำ). ถ้าลูกค้าข้ามวันเกิด (ไม่มี birth_date) → null = ส่งแค่ภาพไพ่
     *
     * @return string|null URL ภาพแผนที่ดาว หรือ null
     */
    protected function buildCelticBirthChartUrl(FortuneReading $reading): ?string
    {
        // ใช้ภาพเดิมถ้าเคยสร้างแล้ว (กัน render ซ้ำ)
        if (! empty($reading->reading_image_url)) {
            return $reading->reading_image_url;
        }
        if (empty($reading->birth_date)) {
            return null; // ไม่มีวันเกิด (ลูกค้าข้าม) → ไม่มีแผนที่ดาว
        }

        try {
            $name = $reading->resolveCustomerName();
            $bd = \Carbon\Carbon::parse($reading->birth_date)->format('Y-m-d');
            $chartService = new \App\Services\FortuneChartService;
            $url = $chartService->generateBirthChart($bd, $name, null);
            if (! empty($url)) {
                $reading->update(['reading_image_url' => $url]);

                return $url;
            }
        } catch (\Throwable $e) {
            \Log::warning('Celtic: birth chart image gen fail (non-blocking)', [
                'reading_id' => $reading->id,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * State: CELTIC_AWAITING_QUESTION
     * ลูกค้าพิมพ์คำถาม Q1, Q2, หรือ Q3
     */
    protected function handleCelticAwaitingQuestion(FortuneReading $reading, string $messageText): array
    {
        $question = trim($messageText);

        // 🆕 (2026-06-23) flag: คำถามนี้เป็น "พื้นดวงเปิดตัว auto" (จาก birthdate step) หรือไม่
        //   → ถ้าใช่ ส่ง isAutoBaseChart=true ให้ askQuestion (นับ used แต่ไม่เริ่มจับเวลา 15 นาที)
        $isCelticBaseChart = false;

        // 🎂 (2026-06-08) ขั้นถามวันเกิด (คำถามแรกบังคับใน Celtic 99) — ต้องอยู่บนสุด ก่อน Q&A ปกติ
        //   handleCelticBirthdateStep คืน:
        //     • array  = จัดการจบแล้ว (ถามวันเกิดซ้ำ / ข้าม) → ส่งกลับลูกค้าเลย
        //     • string = ได้วันเกิดแล้ว = คำถามสังเคราะห์ "พื้นดวงรวม" → ไหลเข้า askQuestion ปกติด้านล่าง
        //       (buildFollowupPrompt ฉีด ดาวเจ้าชนะ + แม่หมอ Knowledge RAG + AdminQA RAG ให้อัตโนมัติ)
        if ($reading->getConversationState('celtic_birthdate_pending')) {
            $bdStep = $this->handleCelticBirthdateStep($reading, $question);
            if (is_array($bdStep)) {
                return $bdStep;
            }
            // ได้วันเกิดแล้ว → แทนข้อความลูกค้าด้วยคำถามพื้นดวง แล้วไหลต่อเข้า askQuestion ปกติ
            $question = $bdStep;
            $messageText = $bdStep;
            // 🛡️ (2026-06-23 bug-hunt) แยก "คำถามพื้นดวงสังเคราะห์" (parse วันเกิดสำเร็จ → ตั้ง celtic_base_chart)
            //   ออกจาก "คำถามจริงของลูกค้าที่ carry มา" (พิมพ์คำถามแทนวันเกิดครบ 2 ครั้ง → คืนสตริงคำถามจริง
            //   โดยไม่ตั้ง celtic_base_chart). เคส carry ต้องเริ่มจับเวลา + ไม่ flag เป็นพื้นดวง
            $isCelticBaseChart = (bool) $reading->getConversationState('celtic_base_chart', false);
        } elseif (empty($reading->birth_date) && $this->looksLikePureBirthdateInput($question)) {
            // 🎂 (2026-08-13) วันเกิดมาช้า — gate วันเกิดถูกปิดไปแล้ว (auto-skip/ข้าม) แต่ลูกค้า
            //   เพิ่งส่ง "วันเกิดล้วนๆ" ตามมา
            //   เคสจริง reading 11055 (ยุวารีย์): ปุ่ม "พร้อม" ค้างเผา attempts จน auto-skip →
            //   ลูกค้าพิมพ์ "24/8/2500" ตามมา → ตกเป็น "คำถาม" seq 4 (AI ตอบรับเฉยๆ แต่กินสิทธิ์
            //   + พื้นดวงที่จ่ายมาไม่เคยถูกสร้าง)
            //   Fix: ส่งกลับเข้า handleCelticBirthdateStep เหมือน gate ยังเปิด → parse สำเร็จ →
            //   ได้คำถามพื้นดวงสังเคราะห์ → ลูกค้าได้พื้นดวงเต็ม (ดาวเจ้าชนะ + ไพ่ 10 ใบ)
            \Log::info('Celtic: late birthdate captured หลัง gate ปิด → สร้างพื้นดวงให้', [
                'reading_id' => $reading->id,
                'birthdate_text' => mb_substr($question, 0, 40),
            ]);

            $bdStep = $this->handleCelticBirthdateStep($reading, $question);
            if (is_array($bdStep)) {
                return $bdStep;
            }
            $question = $bdStep;
            $messageText = $bdStep;
            $reading->setConversationState('celtic_birthdate_skipped', false);
            $isCelticBaseChart = (bool) $reading->getConversationState('celtic_base_chart', false);
        }

        // 🔢 (2026-06-06 R5125) ลูกค้าเลือก "ทั้งสองข้อ" จากกล่องคำถามแนะนำ (1และ2 / เอาสองข้อ / ทั้งคู่)
        //   เคสจริง FTU-260606-W4360 seq3: ลูกค้าพิมพ์ "1และ2" → resolveCelticSuggestionPick รับแค่
        //   ^[12]$ → หลุดไปให้ AI ตอบรับมั่ว ("ได้ เลือกทั้ง 1 และ 2 เลย...") + กินสิทธิ์ 1 ข้อฟรี
        //   user spec: "ตอบข้อที่เลือกก่อน แล้วคำถามที่เหลือ + คำถามแนะนำใหม่ เป็นรูทแยกให้กดต่อ"
        //   → ตอบข้อ 1 เต็มๆ ตอนนี้ (กิน 1 สิทธิ์ ตามจริง) + carry ข้อ 2 ไป re-offer หลังคำตอบ (ไม่หล่น)
        //   ต้องอยู่บนสุด — ก่อน end-confirm/readiness-ack
        $carryForwardQuestion = null;
        $bothPick = $this->resolveCelticSuggestionPickBoth($reading, $question);
        if ($bothPick !== null) {
            \Log::info('Celtic: customer picked BOTH suggested questions → answer #1 now, carry #2', [
                'reading_id' => $reading->id,
                'tapped' => $question,
                'answer_now' => mb_substr($bothPick['answer'], 0, 50),
                'carry' => mb_substr((string) ($bothPick['carry'] ?? ''), 0, 50),
            ]);
            $question = $bothPick['answer'];
            $messageText = $bothPick['answer'];
            $carryForwardQuestion = $bothPick['carry'] ?? null;
        } else {
            // 🔢 (2026-06-05) กดปุ่มเลขเดี่ยว 1/2 = เลือกคำถามแนะนำข้อนั้น → คืนคำถามเต็มจาก Cache
            //   แล้วไหลเข้า askQuestion ปกติ (นับเป็นคำถามจริง 1 ข้อ เหมือนพิมพ์เอง)
            $pickedSuggestion = $this->resolveCelticSuggestionPick($reading, $question);
            if ($pickedSuggestion !== null) {
                \Log::info('Celtic: customer tapped suggested-question number → expand to full question', [
                    'reading_id' => $reading->id,
                    'tapped' => $question,
                    'resolved' => mb_substr($pickedSuggestion, 0, 60),
                ]);
                $question = $pickedSuggestion;
                $messageText = $pickedSuggestion;
            } elseif ($this->looksLikeSuggestionNumberInput($question)) {
                // 🔢 (2026-06-06 R5125) พิมพ์เลข/"ทั้งสอง" แต่ไม่มี suggestion ค้าง (cache หมด/ไม่เคยเสนอ)
                //   เคสจริง FTU-260606-W4360 seq4: "1" ตกไปให้ askQuestion เป็นข้อความ literal →
                //   AI งง → seq ว่าง answered_at=NULL (หล่นหาย ลูกค้าไม่ได้คำตอบ)
                //   user spec: "ระบบต้องดำเนินต่อได้" — ชวนถามใหม่แทนการทิ้ง + ไม่กินสิทธิ์
                //   ⏳ ghost-bug guard: ถ้าหมดเวลา QA แล้ว → ปิด session ตามปกติ (อย่า re-invite ลอยๆ
                //   ข้ามด่าน canAskMoreCeltic ด้านล่างเพราะ return ก่อน) — ลูกค้าต้องได้ Grand Finale
                if (! $reading->canAskMoreCeltic()) {
                    return $this->endCelticSession($reading, 'time_expired');
                }

                \Log::info('Celtic: bare suggestion-number without cached suggestions → re-invite (no burn)', [
                    'reading_id' => $reading->id,
                    'text' => mb_substr($question, 0, 20),
                ]);

                return [
                    'action' => 'celtic_invite_question',
                    'message' => "🌙 อยากให้แม่หมอทำนายเรื่องไหนต่อดีคะ?\n\n"
                        .'💬 พิมพ์เรื่องที่อยากรู้มาได้เลย — ความรัก การงาน การเงิน สุขภาพ '
                        .'หรือเรื่องที่ค้างคาใจค่ะ ✨',
                    'reading' => $reading,
                ];
            }
        }

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
                    .'📜 หรือพิมพ์ *"เลิก"* เมื่อพร้อมจบรอบ',
                'reading' => $reading,
            ];
        }

        // เช็ค time window (นับจาก first message)
        // 💾 (2026-08-17) คำถามที่ "มาช้ากว่านาฬิกาไม่กี่วินาที" ต้องไม่หล่นหายเงียบ
        //   เคสจริง reading 11182 (FTU-260817-N3846 · Celtic 99฿):
        //     20:11:32 ตอบข้อ 9 เสร็จ → 20:12:53 ลูกค้าพิมพ์ข้อ 10 → window 15 นาทีหมดพอดี
        //     → ตกด่านนี้ไป endCelticSession ทันที **โดยไม่เคยสร้างแถวใน fortune_celtic_questions**
        //     → Grand Finale เห็น pending_count=0 → บทสรุปไม่มีคำตอบข้อนั้นเลย
        //     = ลูกค้าจ่าย 99฿ แล้วคำถามสุดท้ายหายทั้งข้อ
        //   ⚠️ ตาข่าย 2026-08-07 ("อย่าให้มันคาใจ") อุดเฉพาะ "แถวที่มีอยู่แล้วแต่ยังไม่มีคำตอบ"
        //      ไม่ครอบเคสนี้ที่ยัง **ไม่มีแถว** → ต้องฝากแถว pending ให้ก่อนปิดรอบ
        if (! $reading->canAskMoreCeltic()) {
            $this->stashUnansweredCelticQuestion($reading, $question);

            return $this->endCelticSession($reading, 'time_expired');
        }

        // 🆕 (2026-05-31) ด่านกันคำทักทาย/ตอบรับ ก่อนคำถามแรก — ไม่ยิงทำนาย ไม่กินโควต้า
        //   เคสจริง R4474 (FTU-260531-P7895): ลูกค้าพิมพ์ "พร้อม" หลังเปิดไพ่ครบ → Q1 ยิงทำนาย
        //   "ความรัก" มั่ว (เดาธีมจากไพ่ถ้วย) + เปลือง 1/5 โควต้า เพราะ Q1 path เดิมไม่มี TYPE
        //   classifier. ตอนนี้เพิ่ม classifier ใน prompt แล้ว แต่ AI-directive ไม่ 100% → ด่าน
        //   deterministic นี้กันชั้นแรกแบบชัวร์
        //   Scope: เฉพาะตอนยังไม่มีคำถามจริง (celtic_questions_used == 0) — กัน "พร้อม/ค่ะ/โอเค/
        //   เริ่มเลย" ไม่ให้เปลือง Q1. คำถามจริงข้อแรก (มี "ไหม"/ระบุเรื่อง) ผ่านไป askQuestion ปกติ
        //   🎯 (2026-06-18) ขยายด่านนี้ให้ครอบ "ทุกเทิร์น" (ไม่ใช่แค่ Q1) + เพิ่มเศษวันเกิด
        //   เคสจริง R7145: ack "วันจันทร"/"ปีฉลู" (เศษวันเกิด) ถูกนับเป็นคำถามทำนาย TYPE:A เพราะ
        //   Q2+ เดิมพึ่ง classifier โมเดลเล็ก 100%. bias: กำกวม→ปล่อยผ่าน (ลูกค้าจ่ายเงิน) →
        //   ด่าน deterministic นี้ "แคบ-precision สูง" (ack ล้วน / เศษวันเกิด whole-match) เท่านั้น
        $usedQ = (int) ($reading->celtic_questions_used ?? 0);
        $isReadinessAck = $this->looksLikeReadinessAck($question);
        $isBirthdateFragment = $this->looksLikeBirthdateFragmentOnly($question);
        if ($isReadinessAck || $isBirthdateFragment) {
            \Log::info('Celtic: non-prediction message — ชวนถามต่อ ไม่กินโควต้า', [
                'reading_id' => $reading->id,
                'used_q' => $usedQ,
                'kind' => $isBirthdateFragment ? 'birthdate_fragment' : 'readiness_ack',
                'text' => mb_substr($question, 0, 40),
            ]);

            if ($isBirthdateFragment) {
                $inviteMsg = "🌙 รับทราบค่ะ — แม่หมอเก็บไว้ผูกกับดวงให้นะคะ\n\n"
                    .'💬 อยากให้แม่หมอดูเรื่องอะไรต่อคะ? พิมพ์คำถามมาได้เลย (ความรัก งาน เงิน สุขภาพ หรือเรื่องที่ค้างคาใจ)';
            } elseif ($usedQ === 0) {
                $inviteMsg = "🌙 เปิดไพ่ครบแล้วค่ะ — เจ้าชะตาอยากให้แม่หมอดูเรื่องอะไรก่อนดีคะ\n\n"
                    .'💬 พิมพ์ถามเข้ามาได้เลย เช่น ความรัก การงาน การเงิน สุขภาพ ของหาย '
                    .'หรือเรื่องที่ค้างคาใจ — แม่หมอจะเปิดไพ่ทำนายให้ทีละเรื่องค่ะ ✨';
            } else {
                $inviteMsg = '🌙 ได้ค่ะ — พิมพ์ถามเรื่องที่อยากรู้ต่อได้เลยนะคะ แม่หมอจะเปิดไพ่ทำนายให้ ✨';
            }

            return [
                'action' => 'celtic_invite_question',
                'message' => $inviteMsg,
                'reading' => $reading,
            ];
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

        // 🆕 FIX D (2026-06-22) Settle window — นิ่งรอลูกค้า "รัวคำ" ให้จบก่อนตอบทีเดียว
        //   owner spec: "ระหว่างทำนายก็ต้องคุมสถานการณ์ได้ — ถ้ารอ N วิแล้วลูกค้าพิมพ์อีกก็รอต่อ
        //   จนกว่าจะหยุดค่อยตอบ ไม่ตอบทุกข้อความ". กันเคส R1626 (ข้อความสับสนรัวๆ ถูกนับเป็นคำถามทีละข้อ)
        //   กลไก: trailing debounce — append เข้า buffer + dispatch ProcessBufferedCelticMessageJob
        //     (delay window+1; job default isReadyToFlush นับจาก "ข้อความล่าสุด" → flush เมื่อเงียบครบ window)
        //     → job flush + askQuestion(combined) + ส่งทีเดียว. ระหว่างรอ return silent_skip (ไม่ตอบ)
        //   Scope: เฉพาะ Q&A (handleCelticAwaitingQuestion) — การเปิดไพ่ไม่ผ่านนี่ → กดทีละใบยังไว
        //   ปิดได้ด้วย setting celtic_qa_settle_seconds=0 (fallback 10 วิ)
        //   🛡️ (2026-06-23 bug-hunt) ข้าม settle-buffer สำหรับ "พื้นดวงเปิดตัว" (isCelticBaseChart) —
        //     เป็นข้อความสังเคราะห์เดี่ยวจากระบบ (ไม่ใช่ลูกค้ารัวคำ) → ไม่ต้อง debounce
        //     ถ้า buffer จะทำ flag isAutoBaseChart หาย (job เรียก askQuestion ไม่มี flag) → timer เริ่มที่ Q1 ผิด
        $settleSec = (int) ($this->settings->celtic_qa_settle_seconds ?? 10);
        if ($settleSec > 0 && ! $isCelticBaseChart) {
            $dPlatform = $reading->platform;
            if (! $dPlatform || ! in_array($dPlatform, ['facebook', 'line'], true)) {
                $cand = $reading->platform_user_id ?: $reading->facebook_user_id ?: '';
                $dPlatform = preg_match('/^U[a-f0-9]{32}$/i', $cand) ? 'line' : 'facebook';
            }
            $dUserId = $dPlatform === 'line'
                ? ($reading->platform_user_id ?: $reading->facebook_user_id)
                : ($reading->facebook_user_id ?: $reading->platform_user_id);

            if ($dUserId) {
                // 🔢 (2026-06-23 FIX D fix) carry (both-pick ข้อ 2) → Cache TTL 3 นาที (local var หายเมื่อ silent_skip)
                //   job อ่านกลับใน finalizeCelticAnswer ด้วย cache()->pull (atomic read+delete) + TTL กัน stale leak ถ้า job หาย
                if ($carryForwardQuestion !== null && trim((string) $carryForwardQuestion) !== '') {
                    cache()->put('celtic:pending_carry:'.$reading->id, $carryForwardQuestion, now()->addMinutes(3));
                }
                // 🔔 (2026-06-23) ลูกค้าพิมพ์คำถาม (กำลัง buffer) = engaged → กัน nudge ตามถามยิงระหว่างรอ settle window
                //   🔔 (2026-06-30) nudge เปลี่ยนเป็นตามทุก interval → กดเวลา last_nudge เพื่อระงับ nudge ช่วง settle
                //   (คำถามจะ process ในไม่กี่วินาที → markCelticAnswered ปิด awaiting_first_question เอง)
                $reading->setConversationState('pro_session_nudge_sent', true);
                $reading->setConversationState('pro_session_last_nudge_at', now()->toIso8601String());

                app(\App\Services\Fortune\MessageBuffer::class)->append('celtic_q', $dUserId, $question);

                // 🛟 (2026-08-21) จดคำถามลง conversation_state ด้วย — buffer อยู่บน Cache (redis DB 1)
                //   ซึ่ง `php artisan cache:clear` = `flushdb()` ล้างทั้ง DB ไม่ใช่ลบตาม prefix
                //   เคสจริงฝั่ง Deep 39 (FTU-260821-K9664): deploy กิน buffer → job เจอว่าง → return เงียบ
                //   → คำถามลูกค้าที่จ่ายเงินแล้วระเหย ไม่มี error ที่ไหนเลย. Celtic 99 มีรูเดียวกันเป๊ะ
                //   (แก้ฝั่งเดียว = อีกฝั่งเป็นระเบิดเวลา — บทเรียนเดิมจาก spam guard FB/LINE)
                $this->rememberPendingProSessionQuestion($reading, $question, 'celtic');

                \App\Jobs\ProcessBufferedCelticMessageJob::dispatch($reading->id, $dPlatform, $dUserId, $settleSec)
                    ->delay(now()->addSeconds($settleSec + 1));

                \Log::info('Celtic FIX D: settle-buffer คำถาม (นิ่งรอรัว)', [
                    'reading_id' => $reading->id,
                    'settle_sec' => $settleSec,
                    'q_preview' => mb_substr($question, 0, 40),
                ]);

                // นิ่ง — ไม่ตอบทันที (job จะรวมตอบทีเดียวเมื่อลูกค้าหยุดพิมพ์ครบ window)
                return [
                    'action' => 'silent_skip',
                    'message' => null,
                    'reading' => $reading,
                ];
            }
        }

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
        // 🌙 (2026-06-06) user spec: "อย่าแจ้งลูกค้าว่าเอไอขัดข้องเด็ดขาด" — default message นุ่ม (ไม่ใช้คำว่า AI/ขัดข้อง)
        $result = ['success' => false, 'message' => 'แม่หมอขอตั้งสมาธิที่ไพ่อีกครู่นะคะ'];
        $exceptionThrown = null;
        try {
            $result = $service->askQuestion($reading, $question, $isCelticBaseChart);
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

        // 🌙 (2026-06-06) user spec: "อย่าแจ้งลูกค้าว่าเอไอขัดข้องเด็ดขาด"
        //   ห้ามใช้คำว่า "AI/เอไอ/ขัดข้อง" + ห้าม echo $result['message'] (อาจมี technical text)
        //   state ถูกคืนเป็น AWAITING_QUESTION ใน finally block แล้ว → ลูกค้าพิมพ์คำถามซ้ำได้ทันที (resume)
        //   ใช้โทน "แม่หมอตั้งสมาธิอีกครู่" — ลูกค้าไม่รู้สึกว่าระบบพัง + รู้ว่าถามซ้ำได้
        $softRetryMessage = '🌙 แม่หมอขอตั้งสมาธิที่ไพ่อีกครู่นะคะ — พิมพ์คำถามเดิมส่งมาอีกครั้งได้เลยค่ะ ✨';

        if ($exceptionThrown !== null) {
            return [
                'action' => 'celtic_ai_failed',
                'message' => $softRetryMessage,
                'reading' => $reading,
            ];
        }

        if (! $result['success']) {
            return [
                'action' => 'celtic_ai_failed',
                'message' => $softRetryMessage,
                'reading' => $reading,
            ];
        }

        // 🔔 (2026-06-23) พื้นดวงเปิดตัว (auto Q1) เพิ่งส่งสำเร็จ → ลูกค้าพร้อมถาม Q2
        //   ตั้งจุดอ้างอิง nudge 1 นาที (manual-birthdate path ; prior-birthdate ตั้งใน onCelticAllCardsPicked)
        if ($isCelticBaseChart) {
            try {
                $reading->setConversationState('pro_session_ready_at', now()->toIso8601String());
            } catch (\Throwable $e) {
                // non-blocking
            }
        }

        // 🔧 (2026-06-23 FIX D fix) decoration (footer กติกา / กล่องคำถามแนะนำ / carry / off-topic-max-cap)
        //   แยกเป็น finalizeCelticAnswer ให้ ProcessBufferedCelticMessageJob (settle-buffer path) เรียกได้เหมือนกัน
        //   ไม่งั้นคำตอบที่ผ่าน buffer จะไม่มี footer/ปุ่มแนะนำ + คำถาม both-pick หาย
        return $this->finalizeCelticAnswer($reading, $result, $carryForwardQuestion);
    }

    /**
     * 🔧 (2026-06-23) Decoration หลัง askQuestion สำเร็จ — off-topic/max-cap + footer กติกา +
     *   กล่องคำถามแนะนำ (ปุ่มเลข) + carry-forward. ใช้ร่วม inline path (handleCelticAwaitingQuestion)
     *   + ProcessBufferedCelticMessageJob (กัน drift). public wrapper = finalizeCelticAnswerPublic
     *
     * @param  array  $result  ผลจาก CelticCrossService::askQuestion (ต้อง success แล้ว)
     * @param  string|null  $carryForwardQuestion  คำถามข้อ 2 ที่ค้างจาก both-pick (null = อ่านจาก state — เคส buffer)
     */
    protected function finalizeCelticAnswer(FortuneReading $reading, array $result, ?string $carryForwardQuestion = null): array
    {
        // 🔢 (2026-06-23 FIX D fix) เคส buffer: carry หายตอน silent_skip → อ่านจาก Cache (pull = read+delete atomic)
        //   TTL 3 นาที กัน stale leak ถ้า job หาย ; inline both-pick ส่ง param มาตรงๆ (ข้าม Cache)
        if ($carryForwardQuestion === null) {
            $cachedCarry = cache()->pull('celtic:pending_carry:'.$reading->id);
            if (is_string($cachedCarry) && trim($cachedCarry) !== '') {
                $carryForwardQuestion = $cachedCarry;
            }
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

        // 🔢 (2026-06-05) คำถามแนะนำต่อยอด — askQuestion ดึง+strip [NEXTQ] ให้แล้ว (DB/bridge/redeliver สะอาด)
        //   อ่าน structured 'next_questions' เป็นหลัก + defense-in-depth: strip token ที่อาจหลงเหลือใน
        //   $aiResponse (เผื่อ path เก่า/อื่นไม่ strip) — กัน token รั่วถึงลูกค้าทุกกรณี รวม Grand Finale Q5
        $celticNextQuestions = $result['next_questions'] ?? [];
        $leftoverNextQuestions = $this->extractCelticNextQuestions($aiResponse); // strips $aiResponse ในตัว
        if (empty($celticNextQuestions) && ! empty($leftoverNextQuestions)) {
            $celticNextQuestions = $leftoverNextQuestions;
        }

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
        //
        //   🌙 (2026-06-07 UPDATE) ยกเลิก hard cap "จำนวนคำถาม" — กลับไป "ถามได้ไม่จำกัด ภายใน 15 นาที"
        //     user spec: "ตอนตีสองคนเข้ามาดู บอทตอบและหักโควต้าจนไม่ได้ถาม = ประสบการณ์ไม่ดี"
        //     ตอนนี้ maxQ=0 (DB) → block hard-cap ด้านล่าง ($maxQ > 0) ถูกข้าม → เหลือแค่ time window 15 นาที
        //     (TYPE/quota machinery คงไว้ — ไม่ทำงานเมื่อ maxQ=0 เท่านั้น ไม่ต้องรื้อ)

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
        $maxQ = (int) ($this->settings->celtic_cross_max_questions ?? 0);
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

        // 🔇 (2026-08-28, owner) "นำส่วนที่รายงานเวลา ถามแม่หมอ พิมพ์คำถามต่อไปได้เลย
        //    นั้นออกไปเลย โผล่ทีเดียวตอนใกล้หมดเวลา 3 นาทีสุดท้าย พอ"
        //
        //    ทับสเปกเดิม (2026-05-23 v3 "ต้องบอกกติกาให้ชัดทุกที่") — กล่องนับถอยหลัง
        //    ต่อท้ายทุกคำตอบทำให้คำทำนายอ่านเหมือนใบเสร็จ และเร่งลูกค้ากลาย ๆ
        //
        // ⚠️ 1 กรณีที่ยัง **ต้องบอกเสมอ**: โควตาคำถามหมดพอดี
        //    บรรทัดนั้นไม่ใช่การทวง แต่เป็นการอธิบายว่าทำไมอีกเดี๋ยวจะมีบทสรุปมา
        //    ตัดทิ้ง = ลูกค้าเจอบอทตัดจบเองโดยไม่รู้สาเหตุ (เคสตีสองเดิมของ 2026-06-07)
        $followupOffer = '';

        if ($remainingQ !== null && $remainingQ <= 0) {
            $followupOffer = "\n\n──────────────────────\n"
                ."❓ ครบ {$maxQ} คำถามแล้ว — แม่หมอกำลังเตรียมสรุปท้ายให้นะคะ ✨";
        } elseif ($remainingMin !== null && $remainingMin > 0 && $remainingMin <= 3) {
            // ⏳ 3 นาทีสุดท้าย — โผล่ **ครั้งเดียว** ต่อ 1 เซสชัน
            //   ธงอยู่ใน conversation_state (DB) ห้ามเก็บบน Cache: deploy รัน cache:clear
            //   (= flushdb) 3 หนต่อรอบ ⇒ ลูกค้าที่จ่าย 99 จะโดนเตือนซ้ำ
            $alreadyTold = false;

            try {
                $alreadyTold = ! empty($reading->getConversationState('time_notice_sent'));

                if (! $alreadyTold) {
                    $reading->setConversationState('time_notice_sent', true);
                }
            } catch (\Throwable $e) {
                // อ่าน/เขียนธงไม่ได้ → ยอมแจ้ง ดีกว่าเงียบตอนใกล้หมดเวลา
                \Log::debug('Celtic: อ่านธงแจ้งเวลาไม่ได้ (ปล่อยแจ้ง)', [
                    'reading_id' => $reading->id,
                    'error' => $e->getMessage(),
                ]);
            }

            if (! $alreadyTold) {
                $followupOffer = "\n\n──────────────────────\n"
                    ."⏳ *เหลือเวลาอีก {$remainingMin} นาที* (จากทั้งหมด {$qaWindow} นาที)\n"
                    .'💬 ถามต่อได้เลยค่ะ — หรือพิมพ์ *"เลิก"* เมื่อพร้อมจบและรับสรุป ✨';
            }
        }

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

        // 🔢 (2026-06-05) กล่องที่ 2 — คำถามแนะนำต่อยอดเป็นปุ่มเลข (เฉพาะตอนยังเหลือสิทธิ์ถาม)
        //   user spec: "ยกเว้นคำถาม Q5 ไม่ต้องสร้างปุ่ม เพราะหมดโควต้าถามแล้ว"
        //   ที่นี่ผ่าน hard-cap (usedQ >= maxQ) มาแล้ว → usedQ < maxQ → ยังถามต่อได้เสมอ
        //   remainingQ === null = admin ตั้งถามไม่จำกัด (maxQ=0) → ยังโชว์ปุ่มได้
        //   sync Cache กับคำตอบล่าสุด: มีคำถามแนะนำ → put / ไม่มี → forget (กันกดเลขเก่าค้าง)
        // 🔢 (2026-06-06 R5125) ลูกค้าเลือก "ทั้งสองข้อ" — เอาข้อ 2 ที่ค้างมา re-offer เป็นปุ่มแรก
        //   รวมกับคำถามแนะนำใหม่ที่เพิ่งทำนายได้ (user spec: "คำถามที่เหลือ + คำถามแนะนำใหม่ เพิ่มอีก 1")
        //   → กล่องถัดไป = [ข้อ 2 ที่ค้าง] + [คำถามแนะนำใหม่] (ตัดซ้ำ + ตัดค่าว่าง + จำกัด 2 ปุ่ม 1️⃣2️⃣)
        if ($carryForwardQuestion !== null && trim((string) $carryForwardQuestion) !== '') {
            array_unshift($celticNextQuestions, $carryForwardQuestion);
            $celticNextQuestions = array_values(array_unique(array_filter(
                $celticNextQuestions,
                static fn ($q) => is_string($q) && trim($q) !== ''
            )));
            $celticNextQuestions = array_slice($celticNextQuestions, 0, 2);
        }

        $suggestionBox = null;
        $suggestionButtons = [];
        if (! empty($celticNextQuestions) && ($remainingQ === null || $remainingQ > 0)) {
            $suggestionBox = $this->buildCelticSuggestionBox($celticNextQuestions);
            $suggestionButtons = $this->buildCelticSuggestionButtons($celticNextQuestions);
            // เก็บคำถามเต็มไว้ map ตอนลูกค้ากดเลข (TTL ยาวกว่า qa window เผื่อกดช้า)
            $this->storeCelticSuggestions($reading, $celticNextQuestions);
        } else {
            $this->forgetCelticSuggestions($reading);
        }

        return [
            'action' => 'celtic_question_answered',
            'message' => $finalMessage,
            'reading' => $reading,
            // 🐛 (2026-05-29) ส่ง sequence ให้ ChannelManager mark delivered ตรง row (กัน redeliver ซ้ำ)
            'sequence' => $sequence,
            // 🔢 (2026-06-05) กล่องที่ 2 (คำถามแนะนำ + ปุ่มเลข) — ChannelManager ส่งต่อหลังคำทำนายถึงแล้ว
            'suggestion_box' => $suggestionBox,
            'quick_replies' => $suggestionButtons,
        ];
    }

    /**
     * 🔧 (2026-06-23) public wrapper — ให้ ProcessBufferedCelticMessageJob เรียก decoration เดียวกับ inline path
     *   (carry อ่านจาก state ใน finalizeCelticAnswer เอง — buffer path ไม่มี local carry)
     */
    public function finalizeCelticAnswerPublic(FortuneReading $reading, array $result): array
    {
        // carry (both-pick ข้อ 2) อ่านจาก Cache ภายใน finalizeCelticAnswer เอง (buffer path ไม่มี local carry)
        return $this->finalizeCelticAnswer($reading, $result, null);
    }

    /**
     * 🔢 (2026-06-05) ดึง "คำถามแนะนำต่อยอด" จาก token [NEXTQ]q1|q2[/NEXTQ] + ตัด token ออกจากคำทำนาย
     *
     * AI คาย token ท้ายข้อความ — เราตัดออก (กล่องคำทำนายต้องสะอาด ไม่มีรายการคำถาม)
     * แล้วคืน 2 คำถามไปทำเป็นปุ่มเลข. pattern กว้าง — เผื่อ AI ใส่ช่องว่าง/ขีดในชื่อ token
     *
     * @param  string  $aiResponse  คำทำนาย (แก้ไขโดยอ้างอิง — token ถูกตัดออก)
     * @return array<int,string> รายการคำถาม 0-2 ข้อ
     */
    protected function extractCelticNextQuestions(string &$aiResponse): array
    {
        // 🔢 (2026-06-05 v2) delegate ไป parser กลางที่ทน token ผิดรูป (AI ดรอป tag เปิด — เคส 5023)
        //   เดิม regex ครบคู่ที่นี่ก็ match ไม่ได้เหมือน askQuestion → token รั่ว. รวม source เดียว
        //   กัน drift: askQuestion + askQuestionAsAdmin + fallback นี้ ใช้ logic เดียวกันหมด
        return \App\Services\CelticCrossService::pullNextQuestions($aiResponse);
    }

    /**
     * 🛟 (2026-08-23) เก็บคำถามแนะนำไว้ 2 ที่ — Cache (เร็ว) + conversation_state (ทน deploy)
     *
     * เคสจริง บิล FTU-260822-U7900: ลูกค้าจ่าย 99฿ แล้วกดปุ่มเลข "1" เวลา 00:15:06 และ 00:16:59
     * ทั้งสองครั้งบอทตอบกลับเป็น "อยากให้ทำนายเรื่องไหนต่อดีคะ?" แทนคำทำนาย
     * ต้นเหตุ: deploy รอบ 00:14:40–00:17:52 รัน `cache:clear` 3 หน
     * ซึ่งเป็น **flushdb ทั้ง redis DB ไม่ใช่ลบตาม prefix** → คีย์ celtic:suggq หายเกลี้ยง
     * → resolveCelticSuggestionPick() คืน null → เลข "1" กลายเป็นข้อความสั้นธรรมดา
     *
     * ลูกค้าต้องพิมพ์คำถามเองยาวๆ ถึงได้คำทำนาย = เสียเวลาไป 2 นาทีจากหน้าต่าง 15 นาทีที่จ่ายเงินมา
     *
     * ⚠️ อย่าเก็บของที่ "ลูกค้าจ่ายเงินแล้ว" ไว้บน Cache อย่างเดียวเด็ดขาด — deploy กินได้ตลอด
     */
    protected function storeCelticSuggestions(FortuneReading $reading, array $questions): void
    {
        $clean = array_values($questions);

        cache()->put("celtic:suggq:{$reading->id}", $clean, now()->addMinutes(20));
        $reading->setConversationState('celtic_suggq', $clean);
        $reading->setConversationState('celtic_suggq_at', now()->toIso8601String());
    }

    /**
     * 🛟 (2026-08-23) ล้างคำถามแนะนำทั้ง 2 ที่
     */
    protected function forgetCelticSuggestions(FortuneReading $reading): void
    {
        cache()->forget("celtic:suggq:{$reading->id}");
        $reading->setConversationState('celtic_suggq', null);
        $reading->setConversationState('celtic_suggq_at', null);
    }

    /**
     * 🛟 (2026-08-23) อ่านคำถามแนะนำ — Cache ก่อน ถ้าหายค่อยกู้จาก DB
     *
     * @return array<int,string>|null null = ไม่มี suggestion ค้างจริงๆ (หรือหมดอายุ)
     */
    protected function loadCelticSuggestions(FortuneReading $reading): ?array
    {
        $stored = cache()->get("celtic:suggq:{$reading->id}");
        if (is_array($stored) && $stored !== []) {
            return $stored;
        }

        // Cache หาย (deploy/flushdb) → กู้จาก conversation_state ที่อยู่ใน DB
        $fromDb = $reading->getConversationState('celtic_suggq');
        if (! is_array($fromDb) || $fromDb === []) {
            return null;
        }

        // เคารพ TTL เดิม 20 นาที — กันเลข "1" ของบทสนทนาเมื่อวานมาตอบคำถามวันนี้
        $at = $reading->getConversationState('celtic_suggq_at');
        if ($at) {
            try {
                if (\Carbon\Carbon::parse($at)->lt(now()->subMinutes(20))) {
                    return null;
                }
            } catch (\Throwable $e) {
                return null;
            }
        }

        \Illuminate\Support\Facades\Log::info('Celtic: กู้คำถามแนะนำจาก DB (cache หาย — น่าจะโดน deploy ล้าง)', [
            'reading_id' => $reading->id,
            'count' => count($fromDb),
        ]);

        // อุ่น cache กลับ ให้รอบถัดไป hit ตามปกติ
        cache()->put("celtic:suggq:{$reading->id}", $fromDb, now()->addMinutes(20));

        return $fromDb;
    }

    /**
     * 🔢 (2026-06-05) สร้าง "กล่องที่ 2" — คำถามแนะนำเป็นเลข + เตือน "ถามเรื่องเดิม ไพ่แม่นกว่า"
     *
     * user spec: คำถามเต็มอยู่ในกล่อง (ไม่จำกัด 20 ตัวอักษรเหมือน label ปุ่ม) / ปุ่มโชว์แค่เลข /
     *            เตือนว่าถ้าเปลี่ยนเรื่อง พลังไพ่กระจาย ความแม่นลดลง
     *
     * @param  array<int,string>  $questions  1-2 คำถาม
     */
    protected function buildCelticSuggestionBox(array $questions): string
    {
        $box = "🔮 อยากให้แม่หมอทำนาย *เรื่องไหนต่อ* ดีคะ — กดเลขเลือกได้เลย\n\n";
        foreach ($questions as $i => $q) {
            $num = $i === 0 ? '1️⃣' : '2️⃣';
            $box .= "{$num} {$q}\n";
        }
        $box .= "\n💬 หรือพิมพ์เรื่องที่อยากรู้มาเองก็ได้\n\n"
            ."✨ *เคล็ดความแม่น*: ไพ่ชุดนี้ผูกพลังกับ *เรื่องเดิม* — ถามต่อเรื่องเดียวกันยิ่งลึกยิ่งแม่น\n"
            .'ถ้าเปลี่ยนไปถามเรื่องใหม่ พลังไพ่จะเริ่มกระจาย ความแม่นอาจลดลงมากค่ะ 🌙';

        return $box;
    }

    /**
     * 🔢 (2026-06-05) สร้างปุ่มเลข (โชว์แค่ตัวเลข) — โครงเดียวใช้ได้ทั้ง FB + LINE
     *
     * FB อ่าน title/payload | LINE อ่าน label/text — ใส่ครบทั้ง 4 key ในปุ่มเดียว
     * • payload `CELTIC_SUGGQ_N` → FB handleQuickReply map เป็น text 'N'
     * • text 'N' → LINE ส่งตรงตอนกด
     * เลข N เข้า resolveCelticSuggestionPick → คืนคำถามเต็มจาก Cache
     *
     * @param  array<int,string>  $questions  1-2 คำถาม
     */
    protected function buildCelticSuggestionButtons(array $questions): array
    {
        $emoji = ['1️⃣', '2️⃣'];
        $buttons = [];
        foreach ($questions as $i => $q) {
            $n = (string) ($i + 1);
            $buttons[] = [
                'title' => $emoji[$i] ?? $n,        // FB label (โชว์แค่เลข)
                'label' => $emoji[$i] ?? $n,        // LINE label (โชว์แค่เลข)
                'text' => $n,                       // LINE ส่งข้อความนี้ตอนกด
                'payload' => 'CELTIC_SUGGQ_'.$n,    // FB quick_reply payload
            ];
        }

        return $buttons;
    }

    /**
     * 🔢 (2026-06-05) ลูกค้ากดปุ่มเลข 1/2 → คืนคำถามแนะนำเต็มที่เก็บไว้ (Cache) มาแทน
     *
     * รองรับทั้ง "1"/"2" (LINE text / FB payload→processConversationalMessage)
     * + "1️⃣"/"2️⃣" (เผื่อ FB ส่ง title แทน payload — strip keycap ก่อนเทียบ)
     *
     * @return string|null คำถามเต็ม หรือ null ถ้าไม่ใช่การกดเลข/ไม่มี suggestion ค้างอยู่
     */
    protected function resolveCelticSuggestionPick(FortuneReading $reading, string $text): ?string
    {
        // strip variation-selector (FE0F) + keycap (20E3) → "1️⃣" กลายเป็น "1"
        $n = trim((string) preg_replace('/[\x{FE00}-\x{FE0F}\x{20E3}]/u', '', $text));
        if (! preg_match('/^[12]$/', $n)) {
            return null; // ไม่ใช่การกดเลขแนะนำ
        }

        // 🛟 (2026-08-23) อ่านผ่าน loader ที่มี DB fallback — Cache อย่างเดียวโดน deploy ล้างได้
        $stored = $this->loadCelticSuggestions($reading);
        if (! is_array($stored)) {
            return null; // ไม่มี suggestion ค้าง (หมดอายุ/ไม่เคยเสนอ) → ปล่อยเป็นข้อความปกติ
        }

        return $stored[(int) $n - 1] ?? null;
    }

    /**
     * 🔢 (2026-06-06 R5125) ลูกค้าเลือก "ทั้งสองข้อ" จากกล่องคำถามแนะนำ
     *
     * รองรับ: "1และ2" / "1 และ 2" / "1,2" / "1 2" / "1กับ2" / "1+2" / "ข้อ1และ2"
     *         + วลีไทย: "ทั้งสอง" / "ทั้งคู่" / "ทั้ง2" / "เอาสองข้อ" / "เอาทั้งสอง" / "ขอทั้งคู่" / "both"
     *
     * คืน ['answer' => คำถามข้อ1เต็ม, 'carry' => คำถามข้อ2เต็ม] เมื่อมี suggestion ค้าง ≥ 2 ข้อ
     * คืน null ถ้าไม่ใช่ both-pick หรือ suggestion ค้างไม่ถึง 2 (ให้ guard อื่นจัดการ)
     *
     * @return array{answer:string,carry:?string}|null
     */
    protected function resolveCelticSuggestionPickBoth(FortuneReading $reading, string $text): ?array
    {
        if (! $this->looksLikeCelticPickBoth($text)) {
            return null;
        }

        // 🛟 (2026-08-23) อ่านผ่าน loader ที่มี DB fallback — Cache อย่างเดียวโดน deploy ล้างได้
        $stored = $this->loadCelticSuggestions($reading);
        if (! is_array($stored) || count($stored) < 2) {
            return null; // ไม่มีคำถามแนะนำ ≥ 2 ให้เลือก → ปล่อยให้ re-invite guard จัดการ
        }

        return [
            'answer' => (string) $stored[0],
            'carry' => isset($stored[1]) ? (string) $stored[1] : null,
        ];
    }

    /**
     * 🔢 (2026-06-06 R5125) ข้อความ = "เลือกทั้งสองข้อ" ไหม (เลข 1<sep>2 หรือวลี both)
     *
     * เน้น precision สูง — เฉพาะรูปแบบที่ชัดเจนว่าเลือกทั้งคู่ (กัน false positive คำถามจริง)
     */
    protected function looksLikeCelticPickBoth(string $text): bool
    {
        // strip keycap (20E3) + variation selector (FE0F) → "1️⃣" เป็น "1"
        $t = mb_strtolower(trim((string) preg_replace('/[\x{FE00}-\x{FE0F}\x{20E3}]/u', '', $text)));
        if ($t === '') {
            return false;
        }

        // 🚫 มี particle คำถาม → เป็นคำถามจริง ไม่ใช่การเลือก
        //   ⚠️ ghost-bug guard: กัน "ทั้งคู่จะรักกันไหม" / "เราทั้งสองจะรอดไหม" (คำถามรักที่มี "ทั้งคู่")
        //   ถูกตีเป็น both-pick แล้วไฮแจ็กไปตอบคำถามแนะนำแทนคำถามจริงของลูกค้า
        foreach (['ไหม', 'มั้ย', 'มัย', 'หรือ', 'เหรอ', 'หรอ', 'รึ', 'อะไร', 'ทำไม', 'ยังไง',
            'อย่างไร', 'เมื่อไหร่', 'เมื่อไร', 'ที่ไหน', 'ใคร', 'กี่', 'เท่าไหร่', 'เท่าไร', '?'] as $qm) {
            if (str_contains($t, $qm)) {
                return false;
            }
        }

        // strip คำขึ้นต้น (เอา/ขอ/อยาก/ดู) + คำลงท้ายสุภาพ → เหลือ "แก่น"
        $core = trim((string) preg_replace('/^(เอาที่|เอา|ขอดู|ขอ|อยากได้|อยากดู|อยาก|ดู)\s*/u', '', $t));
        $core = trim((string) preg_replace('/\s*(ค่ะ|คะ|ค่า|ครับ|คับ|ครับผม|นะ|น่ะ|เลย|ด้วย|ก่อน|จ้า|จ้ะ|จ๊ะ|จ๋า|ละ|ล่ะ|ๆ)+$/u', '', $core));
        $coreNs = (string) preg_replace('/\s+/u', '', $core); // ตัดช่องว่าง

        // วลี both แบบ EXACT (=== ไม่ใช่ str_contains) — กัน substring ในประโยค ("เราทั้งคู่จะรอด")
        $baseBoth = ['ทั้งสองข้อ', 'ทั้งสอง', 'ทั้งคู่', 'ทั้ง2', 'สองข้อ', 'both'];
        if (in_array($coreNs, $baseBoth, true)) {
            return true;
        }

        // เลข "1<sep>2" — sep = ข้อ/และ/กับ/,/./+/&/ หรือ "ช่องว่าง" ("1 2")
        //   ใช้ $core (คงช่องว่าง) แยก "1 2" (=ทั้งคู่) ออกจาก "12" (=สิบสอง กำกวม ไม่นับ)
        //   anchored ^...$ → ทั้ง string ต้องเป็น 1<sep>2 เท่านั้น (ประโยคยาวไม่ผ่าน เช่น "1 บวก 2 เท่ากับ 3")
        $norm = (string) preg_replace('/(ข้อที่|ข้อ|และ|กับ|\+|&|,|\.|\/|\s+)/u', '|', $core);
        if (preg_match('/^\|*1\|+2\|*$/u', $norm) || preg_match('/^\|*2\|+1\|*$/u', $norm)) {
            return true;
        }

        return false;
    }

    /**
     * 🔢 (2026-06-06 R5125) input เป็น "เลขเลือก/ทั้งสอง" ล้วนๆ (ไม่มีเนื้อคำถาม) ไหม
     *
     * ใช้กันเคส cache หมด/ไม่เคยเสนอ แล้วลูกค้ากดเลข → ชวนถามใหม่แทนการ feed "1" ให้ AI (หล่นหาย)
     */
    protected function looksLikeSuggestionNumberInput(string $text): bool
    {
        $n = trim((string) preg_replace('/[\x{FE00}-\x{FE0F}\x{20E3}]/u', '', $text));
        if (preg_match('/^[12]$/', $n)) {
            return true;
        }

        return $this->looksLikeCelticPickBoth($text);
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
    /**
     * 💾 (2026-08-26) เก็บบทสรุปที่จบ session ลง DB "ก่อน" ส่งออก
     *
     * 🔴 บั๊กเก่าที่อยู่มานาน: บทสรุปถูก generate แล้ว return ให้ ChannelManager ส่งเลย
     *    **ไม่เคยถูกเก็บที่ไหน** — ตาราง `fortune_readings` มีแต่ `celtic_summary_image_url` (รูป)
     *    ⇒ ส่งไม่ออกเมื่อไหร่ (โควต้า push หมด / LINE ล่ม / replyToken ตาย) = **ข้อความหายถาวร**
     *      ลูกค้าจ่าย 99฿ แล้วไม่ได้บทสรุป กู้ไม่ได้ ต้อง generate ใหม่เสียค่า AI ซ้ำและได้คนละข้อความ
     *      (เคสจริง 2026-08-25: 2 บิล 99฿ ตอนโควต้าหมด — ย้อนหลังพิสูจน์ไม่ได้ด้วยซ้ำว่าถึงลูกค้าไหม)
     *
     * เก็บแล้ว `LineFortuneWebhookController::flushParkedCelticSummary()` จะส่งคืนผ่าน reply (ฟรี)
     * ตอนลูกค้าทักครั้งหน้า โดยดูจากธง `celtic_summary_delivered === false`
     *
     * ครอบทั้ง Celtic ปกติและคุณไสย (ใช้ endCelticSession ร่วมกัน) + ทั้ง FB/LINE
     * non-blocking ทุกกรณี — เก็บไม่ได้ห้ามทำให้บทสรุปส่งไม่ออก
     */
    protected function stashCelticFinale(FortuneReading $reading, ?string $text, ?string $chartUrl, ?string $composeUrl): void
    {
        try {
            if (trim((string) $text) === '') {
                return;
            }

            $reading->setConversationState('celtic_finale_text', mb_substr((string) $text, 0, 20000));
            $reading->setConversationState('celtic_finale_chart_url', $chartUrl);
            $reading->setConversationState('celtic_finale_image_url', $composeUrl);
            $reading->setConversationState('celtic_finale_built_at', now()->toIso8601String());
        } catch (\Throwable $e) {
            \Log::warning('Celtic: เก็บบทสรุปลง state ไม่สำเร็จ (non-blocking)', [
                'reading_id' => $reading->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

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

            $repickChartUrl = $this->buildCelticBirthChartUrl($reading);

            // 💾 (2026-08-26) เก็บก่อนส่ง — เส้นนี้ก็เป็นเนื้อหาที่ลูกค้าจ่ายเงิน ห้ามหายถ้า push ไม่ออก
            $this->stashCelticFinale($reading, $aiMessage, $repickChartUrl, $composeUrl);

            return [
                'action' => 'celtic_session_ended',
                'message' => $aiMessage,
                'reading' => $reading,
                'celtic_summary_image_url' => $composeUrl,
                // 🗺️ (2026-06-08) แผนที่ดาวชะตา ส่งคู่ภาพไพ่ตอนสรุป (null ถ้าไม่มีวันเกิด)
                'chart_image_url' => $repickChartUrl,
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

        $maxQ = (int) ($this->settings->celtic_cross_max_questions ?? 0);
        $qaWindow = (int) ($this->settings->celtic_cross_qa_window_minutes ?? 15);

        // 🌟 (2026-05-05) Grand Finale Master Summary — generate ทุกครั้งที่เข้าเงื่อนไข
        //   user spec 2026-05-05: "หากยังถามไม่ครบแต่ยุติลงก่อน...หลุดหมดเวลาคุย ให้เข้าโฟลว์
        //   บทสรุปเองและส่งคำทำนายสุดท้ายไปให้อัตโนมัติ"
        //   เดิม: skip ตอน time_expired/idle (เพราะคิดว่าลูกค้า offline)
        //   ใหม่: generate ทุกครั้งถ้าไพ่ครบ 10 ใบ — ลูกค้าจ่าย 99 บาท สมควรได้ summary
        //         (idle/time_expired pushed ผ่าน fortune:celtic-auto-finalize command)
        //   🩹 (2026-07-09) ตัดเงื่อนไข celtic_questions_used >= 1 ออก — counter เชื่อไม่ได้
        //     (เคสจริง 8591/6561/4701: เปิดไพ่ครบ 10 แต่ q_used=0 → เดิมไม่ generate → จ่าย 99
        //      แล้วไม่ได้ Grand Finale เลย ค้างถาวร). ไพ่ครบ 10 = สร้าง summary ได้ (คำถาม optional)
        $shouldGenerateFinale = $reading->getCelticPickedCount() >= 10;

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
                .$this->closingInviteLine($reading);
        } else {
            // Default closings (เดิม) — ใช้เมื่อ Grand Finale skip หรือ fail
            $closingMessage = match ($reason) {
                'time_expired' => "⏰ *เวลาคุยกับแม่หมอหมดแล้วค่ะ*\n\n"
                    ."{$qaWindow} นาทีนับจากคำทำนายแรก ผ่านไปเรียบร้อย — แม่หมอขอจบบทสนทนานี้\n"
                    ."เพื่อไปสร้างบารมีกับเจ้าชะตาท่านอื่นต่อ ขอให้เจ้าชะตาโชคดีนะคะ 🙏✨\n\n"
                    .$this->closingInviteLine($reading),

                'idle' => "🌙 *แม่หมอเห็นว่าเจ้าชะตาเงียบไปนาน*\n\n"
                    ."พลังงานในวงไพ่จางลงแล้ว แม่หมอขอจบบทสนทนานี้นะคะ\n"
                    ."ขอให้เจ้าชะตาเจอแต่สิ่งดีๆ 🙏✨\n\n"
                    .$this->closingInviteLine($reading),

                'customer_said_done' => "🌟 *ขอบคุณที่ใช้บริการดูดวงไพ่ยิปซี Celtic Cross นะคะ*\n\n"
                    ."คำทำนายเป็นแสงไฟชี้ทาง — แต่การตัดสินใจอยู่ที่เจ้าชะตาเอง 🙏\n"
                    ."ขอให้เจ้าชะตาเจอแต่สิ่งดีๆ นะคะ ✨\n\n"
                    .$this->closingInviteLine($reading),

                'max_questions_reached' => ($aiMessage ? trim($aiMessage)."\n\n" : '')
                    .'🌟 *เจ้าชะตาถามครบ '.max(1, $maxQ)." คำถามแล้วค่ะ*\n\n"
                    ."แม่หมอตอบครบทุกข้อสงสัยของเจ้าชะตา 🙏✨\n"
                    ."คำทำนายเป็นแสงไฟชี้ทาง — เจ้าชะตาตัดสินใจเอง 💫\n\n"
                    .$this->closingInviteLine($reading),

                default => ($aiMessage ? trim($aiMessage)."\n\n" : '')
                    ."🌟 *แม่หมอกล่าวลาเจ้าชะตา*\n\n"
                    ."คำทำนายเป็นแสงไฟชี้ทาง — เจ้าชะตาตัดสินใจเอง 🙏\n\n"
                    .$this->closingInviteLine($reading),
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

        // 🎧 (2026-06-20) Voice summary = ON-DEMAND (user spec: "ขึ้นว่าให้บอทอ่านให้ฟัง จึงจะสร้างไฟล์เสียง")
        //    เดิม: dispatch อัตโนมัติทุกครั้งที่จบ → เปลี่ยนเป็น เก็บ source ไว้ + ขึ้นคำชวนให้ลูกค้า
        //    พิมพ์ "อ่านให้ฟัง" เอง → handleCelticVoiceReadRequest ค่อย dispatch ProcessVoiceSummaryJob
        //    ⚠️ เสียง = "ผู้ช่วย AI" อ่านให้ ไม่ใช่เสียงแม่หมอจริง — ต้องสื่อให้ชัดในคำชวน
        //    🐛 deep_response ว่างสำหรับ Celtic → ใช้ grandFinale / aiMessage / celtic_grand_finale_summary
        $voiceOnDemandReady = false;
        try {
            if ($this->settings->shouldGenerateVoiceSummary($reading)) {
                $voiceSource = $grandFinale
                    ?: ($aiMessage ?: (string) $reading->getConversationState('celtic_grand_finale_summary', ''));

                if (! empty($voiceSource) && mb_strlen(trim($voiceSource)) >= 50) {
                    // เก็บ source text ลง state ให้ on-demand job อ่าน (single source of truth)
                    $reading->setConversationState('voice_summary_source_text', mb_substr($voiceSource, 0, 5000));
                    $reading->setConversationState('voice_summary_status', 'available_on_demand');
                    $voiceOnDemandReady = true;
                }
            }
        } catch (\Throwable $e) {
            // เก็บ source fail = ไม่กระทบ closing message
            \Log::warning('Celtic: voice source store exception (non-blocking)', [
                'reading_id' => $reading->id,
                'error' => $e->getMessage(),
            ]);
        }

        // 🌙 (2026-05-08 v3) Pro Session linger hint
        //   ถ้า Pro Session ยังมีเวลาเหลือ → "ลาแบบหลอก" — สรุปจบแต่บอกว่ายังอยู่ต่อ
        //   user spec: "แม้จะมีการสรุปเหมือนจากลา แล้ว แต่ไม่ได้ลาจริง เอไอ อวาต้าแม่หมอ
        //              ก็ยังอยู่และ ถามเพิ่มว่าจะถามอะไรไหม"
        //   เกิดเมื่อ: max_questions_reached (3Q ครบ) แต่ window 30 นาทียังเหลือ
        // 🤝 (2026-08-29 FTU-260829-M9469) time_expired ก็ต้อง linger ด้วย
        //   เดิม linger จำกัดที่ ['max_questions_reached', 'ai_signal'] = **ตายสนิทบน prod**:
        //     • max_questions_reached ยิงได้เฉพาะตอน celtic_cross_max_questions > 0 — prod ตั้ง 0 (ไม่จำกัด)
        //     • ai_signal ไม่มี call site เลย (เป็นแค่ค่า default ของพารามิเตอร์ ไม่มีใครส่งเข้ามา)
        //   ⇒ ทุกบิลจริงตกไปเส้น clearProSessionFlags = วางสายทันทีหลังบทสรุป
        //      (บิล FTU-260829-M9469: ถาม 11 ข้อรัว ๆ แล้วโดนตัดบท — 56% ของบิลโดนแบบนี้)
        //   ตอนนี้เปิด time_expired เข้า linger ตามสเปกเจ้าของ "ยังคุยต่อได้ในเรื่องการทำนายรอบเดียวกัน"
        $aftercareOn = method_exists($this->settings, 'isCelticAftercareEnabled')
            && $this->settings->isCelticAftercareEnabled();
        $lingerReasons = $aftercareOn
            ? ['max_questions_reached', 'ai_signal', 'time_expired']
            : ['max_questions_reached', 'ai_signal'];

        if ($proSessionActive && $proSessionRemaining > 0
            && in_array($reason, $lingerReasons, true)) {
            // 🤝 ธงบอกว่า "เข้าโหมดคุยต่อหลังบทสรุปแล้ว" — cron ปิดท้ายใช้ธงนี้หาเป้า
            //   เก็บบน conversation_state (MySQL) ไม่ใช่ Cache — deploy รัน cache:clear = flushdb
            //   (บทเรียน FTU-260821-K9664) ของลูกค้าที่จ่ายเงินแล้วห้ามอยู่บน Cache อย่างเดียว
            $reading->setConversationState('celtic_aftercare_started_at', now()->toIso8601String());
            $reading->setConversationState('celtic_aftercare_last_msg_at', now()->toIso8601String());
            $reading->setConversationState('celtic_aftercare_farewelled', false);

            // 🌙 (2026-05-23) เปลี่ยน "ยุติการทำนาย" → "เลิกทำนายและสรุปผล" + 2-step confirm
            $closingMessage .= "\n\n──────────────────────\n"
                ."🌙 *แต่แม่หมอยังไม่ลานะคะ — ยังอยู่เป็นเพื่อนเจ้าชะตาอีก {$proSessionRemaining} นาที* ✨\n\n"
                ."💬 ถ้ายังมีอะไรค้างคาใจจากบทสรุป — ถามต่อได้เลยค่ะ\n"
                ."   แม่หมอจะอ่านพลังงานจากไพ่ทั้ง 10 ใบให้ละเอียดยิ่งขึ้น\n\n"
                .'🙏 หรือถ้าพอใจแล้ว พิมพ์ *"ขอบคุณ"* แม่หมอจะอวยพรส่งท้ายให้ค่ะ';
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

        // 🎧 (2026-06-20) คำชวนให้ลูกค้าขอฟังเสียง — ผู้ช่วย AI อ่านบทสรุปให้ฟัง (on-demand)
        //   ขึ้นเฉพาะเมื่อเปิด voice_summary_enabled + มีบทสรุปพร้อม → ลูกค้าพิมพ์ "อ่านให้ฟัง" เอง
        //   สื่อชัดว่าเป็น "เสียงผู้ช่วย AI" ไม่ใช่เสียงแม่หมอ (กันลูกค้าเข้าใจผิด)
        if ($voiceOnDemandReady) {
            $closingMessage .= "\n\n──────────────────────\n"
                ."🎧 *อยากให้ผู้ช่วย AI อ่านบทสรุปนี้ให้ฟังไหมคะ?*\n"
                .'กดปุ่ม *"🎧 อ่านให้ฟัง"* ด้านล่าง (หรือพิมพ์ก็ได้) — _เป็นเสียงผู้ช่วย AI อ่านให้ ไม่ใช่เสียงแม่หมอนะคะ_ ✨';
        }

        // ⭐ (2026-06-17) Review Invite — ชวนรีวิวเพจ Facebook หลังสรุป VIP (เฉพาะลูกค้าจ่ายเงิน)
        //   ตัดสินที่จุดจบ session — ครอบทั้ง webhook + cron auto-finalize (วิ่งผ่าน endCelticSession เหมือนกัน)
        //   non-blocking: รีวิวพังห้ามกระทบคำทำนาย → fail = null (ChannelManager ข้ามเอง)
        //   🛡️ ห้ามส่งตอน "linger" (อวยพรหลอก — Pro Session ยังเหลือเวลา ลูกค้าคุยต่อได้)
        //      เพราะข้อความบอก "ยังไม่ลา ยังเปิดประตูให้อีก X นาที" → ชวนรีวิวตอนนี้จะขัดกัน
        //      จะส่งตอนจบจริง (time_expired/idle/customer_said_done หรือครบไม่มีเวลา Pro เหลือ)
        //   🤝 (2026-08-29) ใช้ $lingerReasons ตัวเดียวกับด้านบน — ถ้าลิสต์สองที่หลุดกัน
        //      time_expired จะ linger (ยังคุยต่อ) แต่โดนชวนรีวิว/เสนอขายทับหน้าทันที = ขัดกันเอง
        $isLingering = ($proSessionActive ?? false) && ($proSessionRemaining ?? 0) > 0
            && in_array($reason, $lingerReasons, true);
        $reviewInvite = null;
        if (! $isLingering) {
            try {
                $reviewInvite = (new \App\Services\Fortune\FortuneReviewInviteService($this->settings))
                    ->attachIfEligible($reading);
            } catch (\Throwable $e) {
                \Log::warning('Celtic: review invite attach fail (non-blocking)', [
                    'reading_id' => $reading->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $chartUrl = $this->buildCelticBirthChartUrl($reading);

        // 💾 (2026-08-26) เก็บบทสรุป Grand Finale ลง DB "ก่อน" ส่ง — ดู stashCelticFinale()
        $this->stashCelticFinale($reading, $closingMessage, $chartUrl, $composeUrl);

        return [
            'action' => 'celtic_session_ended',
            'message' => $closingMessage,
            'reading' => $reading,
            'end_reason' => $reason,
            'celtic_summary_image_url' => $composeUrl,
            // ⭐ (2026-06-17) payload ชวนรีวิว (null = ไม่ส่ง) — ChannelManager ส่ง bubble ถัดจากข้อความปิด
            'review_invite' => $reviewInvite,
            // 🛒 (2026-08-23) linger = "อวยพรหลอก" Pro Session ยังเหลือเวลา ลูกค้าคุยต่อได้
            //   ⇒ ยังไม่ใช่จุดจบจริง ห้ามเสนอขายของทับ (เหตุผลเดียวกับที่ไม่ชวนรีวิวตอน linger)
            //   ต้องส่งออกมาเป็นธงแยก ไม่ใช่ให้ ChannelManager เดาจาก review_invite===null
            //   เพราะรีวิวเป็น null ได้จากอีกหลายเหตุ (ไม่เข้าเงื่อนไข/ยังไม่จ่าย/เคยชวนแล้ว)
            'is_lingering' => $isLingering,
            // 🗺️ (2026-06-08) แผนที่ดาวชะตา ส่งคู่ภาพไพ่ตอนสรุป (null ถ้าไม่มีวันเกิด)
            'chart_image_url' => $chartUrl,
            'has_grand_finale' => ! empty($grandFinale),
            // 🎧 (2026-06-20) Voice = on-demand — flag บอกว่ามีบทสรุปพร้อมให้ลูกค้าขอฟังเสียง
            'voice_on_demand_ready' => $voiceOnDemandReady,
        ];
    }

    /**
     * 🤝 (2026-08-29 FTU-260829-M9469) อยู่ในช่วง "คุยต่อหลังบทสรุป" หรือไม่
     *
     * เงื่อนไข: ส่งบทสรุปไปแล้ว (celtic_aftercare_started_at) + ยังไม่ได้กล่าวลา
     * ไม่เช็คเวลาในนี้ — อายุเซสชันคุมด้วย pro_session_window_minutes (isInProSession) อยู่แล้ว
     * ⚠️ ธงต้องอ่านจาก conversation_state (MySQL) ไม่ใช่ Cache — deploy รัน cache:clear = flushdb
     */
    protected function isInCelticAftercare(FortuneReading $reading): bool
    {
        if (method_exists($this->settings, 'isCelticAftercareEnabled')
            && ! $this->settings->isCelticAftercareEnabled()) {
            return false;
        }

        if (empty($reading->getConversationState('celtic_aftercare_started_at'))) {
            return false;
        }

        return ! (bool) $reading->getConversationState('celtic_aftercare_farewelled', false);
    }

    /**
     * 🤝 (2026-08-29) ตัวจับ "สัญญาณวางสาย" ของลูกค้า — ขอบคุณ/ลาก่อน แบบไม่มีคำถามพ่วง
     *
     * ทำไมไม่ใช้ looksLikeReadinessAck(): ตัวนั้นนับ "ค่ะ/โอเค/พร้อม" เป็น ack ด้วย
     *   ซึ่งกลางวงสนทนาแปลว่า "รับทราบ ถามต่อ" ไม่ใช่ "ลาแล้ว" → วางสายก่อนเวลา = แย่กว่าเดิม
     * ตัวนี้บังคับว่าต้องมี **คำลา/คำขอบคุณจริง** แล้วค่อยเช็คว่าไม่มีเนื้อหาตามหลัง
     *
     * เคสที่ต้องผ่าน (= ลา):    "ขอบคุณค่ะ" / "ขอบคุณมากๆ นะคะแม่หมอ" / "ขอบใจจ้า" / "บายค่ะ"
     * เคสที่ต้องไม่ผ่าน (= ถามต่อ): "ขอบคุณค่ะ แล้วเรื่องงานล่ะ" / "ขอบคุณ ถามอีกข้อได้ไหม"
     *
     * ⚠️ regex ไทยห้ามลืม \p{M} — คลาส [^\p{L}\p{N}\s] กินสระ/วรรณยุกต์ทิ้ง (บทเรียนเดิม)
     *    ที่นี่เลยใช้ preg_replace เฉพาะอีโมจิ + ตัดคำลงท้ายด้วย pattern ที่เขียนเป็นคำเต็ม
     */
    protected function looksLikeCelticFarewell(string $text): bool
    {
        $clean = mb_strtolower(trim($text));
        if ($clean === '') {
            return false;
        }

        // ตัดอีโมจิ/ZWJ/VS16 ออกก่อน — ปุ่มและคำลาของลูกค้ามักมีอีโมจิพ่วง
        //   (VS16 U+FE0F เป็น \p{M} — ถ้าไม่ตัดจะเหลือค้างแล้วเทียบคำไม่ตรง)
        $clean = trim((string) preg_replace(
            '/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}\x{2190}-\x{21FF}\x{FE0F}\x{200D}]/u',
            '',
            $clean
        ));
        if ($clean === '') {
            return false;
        }

        // 🚫 มี marker คำถาม = ยังอยากคุยต่อ ห้ามวางสายเด็ดขาด
        foreach ([
            'ไหม', 'มั้ย', 'มัย', 'หรือ', 'เหรอ', 'หรอ', 'รึ', 'เมื่อไหร่', 'เมื่อไร',
            'ทำไม', 'อะไร', 'ยังไง', 'อย่างไร', 'ที่ไหน', 'ใคร', 'กี่', 'เท่าไหร่', 'เท่าไร', '?',
            'ขอถาม', 'อยากรู้', 'อยากถาม', 'อยากดู', 'ช่วยดู', 'ดูเรื่อง', 'ดูให้', 'ดูหน่อย', 'ทำนาย',
        ] as $qm) {
            if (str_contains($clean, mb_strtolower($qm))) {
                return false;
            }
        }

        // ต้องขึ้นต้นด้วยคำลา/คำขอบคุณจริง ๆ (ไม่ใช่แค่ "ค่ะ" ลอย ๆ)
        //   🚫 ห้ามใส่ "สวัสดีค่ะ/สวัสดีครับ" — ภาษาไทยใช้ทั้งทักทายและลา
        //      ลูกค้าทักกลับมาคุยต่อแล้วโดนวางสาย = บั๊กตัวเดียวกับที่กำลังแก้อยู่
        //   🚫 ห้ามใส่คำสั้นอย่าง "พอ" ที่นี่ — ตัวเทียบเป็นแบบ prefix + ยอมให้เหลือหาง ≤2 ตัว
        //      ⇒ "พอดี"/"พอใจ" จะกลายเป็นคำลาทันที. คำพวกนั้นอยู่ใน exact-match ที่ caller แทน
        $farewellWords = [
            'ขอบพระคุณ', 'ขอบคุณ', 'ขอบคุน', 'ขอบใจ',
            'ลาก่อน', 'บายบาย', 'บ๊ายบาย', 'บาย',
            'thank you', 'thankyou', 'thanks', 'thank', 'thx', 'goodbye', 'bye',
        ];

        $tailPattern = '/\s*(ค่ะ|คะ|ค่า|ครับ|คับ|ครับผม|จ้า|จ้ะ|จ๊ะ|จ้าา|จ๋า|นะ|น่ะ|แล้ว|เลย|ละ|ล่ะ|ฮะ|ฮ่ะ|แม่หมอ|แม่|หมอ|ๆ)\s*$/u';

        foreach ($farewellWords as $word) {
            if (! str_starts_with($clean, mb_strtolower($word))) {
                continue;
            }

            $rest = trim(mb_substr($clean, mb_strlen($word)));
            // ตัด intensifier นำหน้า (มากๆ/จริงๆ/หลายๆ) + คำลงท้าย 3 รอบ (เผื่อซ้อน "นะคะแม่หมอ")
            $rest = trim((string) preg_replace('/^(?:มากมาย|มาก|จริง|หลาย|เด้อ|งับ|ฮะ|ค้าบ|ๆ)+/u', '', $rest));
            for ($i = 0; $i < 3; $i++) {
                $rest = trim((string) preg_replace($tailPattern, '', $rest));
            }

            // เหลือเนื้อหาตามหลัง = ยังมีเรื่องจะคุย ("ขอบคุณค่ะ แล้วเรื่องงานล่ะ") → ไม่ใช่คำลา
            return $rest === '' || mb_strlen($rest) <= 2;
        }

        return false;
    }

    /**
     * 🤝 (2026-08-29) ดักข้อความระหว่างช่วงคุยต่อหลังบทสรุป
     *
     * @return array|null array = จัดการแล้ว (คืนให้ caller ส่งเลย), null = ไม่เกี่ยว ปล่อยไหลไป AI ตอบต่อ
     */
    protected function handleCelticAftercareMessage(FortuneReading $reading, string $messageText): ?array
    {
        // 🆕 หมายเหตุ: ทางออก "ขอเปิดรอบใหม่" ไม่ได้อยู่ที่นี่ — ดักที่ Pro Session Hard Guard
        //   (FortuneConversationService) ก่อนเรียก handleProSession เพราะเมธอดนั้นคืนค่าเสมอ
        //   ถ้าดักที่นี่แล้ว return null ข้อความจะไหลต่อไปโดน settle-buffer อมเข้าไปให้ AI ตอบ
        //   = ลูกค้าเปิดบิลใหม่ไม่ได้จนกว่าจะหมดเวลา (ซ้ำรอย incident 82 ลูกค้าติดผี 2026-07-08)

        // 🔚 คำสั่งจบตรง ๆ ("พอแล้ว"/"เลิก"/"จบ") — ระหว่างช่วงคุยต่อ status = COMPLETED แล้ว
        //   ⇒ ไม่ผ่าน handleCelticAwaitingQuestion อีก ⇒ handleCelticEndConfirmation ไม่ถูกเรียก
        //   ถ้าไม่ดักตรงนี้ ลูกค้าพิมพ์ "พอแล้ว" จะตกไปให้ AI ตอบเป็นคำถาม (ไม่ยอมวางสาย)
        //   ใช้ matchesExactKeyword (exact บน normalized) ไม่ใช่ prefix — "พอ" สั้นเกินกว่าจะ match หลวม ๆ
        //   ("พอดี"/"พอใจ" ต้องไม่โดนจับ)
        if ($this->matchesExactKeyword($messageText, [
            'พอแล้ว', 'พอแค่นี้', 'พอ', 'จบ', 'จบแล้ว', 'จบเลย', 'เลิก', 'ยุติ', 'หยุด', 'stop',
        ])) {
            Log::info('Celtic aftercare: ลูกค้าสั่งจบตรง ๆ → กล่าวลา+อวยพร', [
                'reading_id' => $reading->id,
                'text_preview' => mb_substr($messageText, 0, 40),
            ]);

            return $this->closeCelticAftercare($reading, 'customer_farewell');
        }

        // 🙏 ลูกค้าลาเอง → กล่าวลา + อวยพร (สเปก: "เพื่อความประทับใจที่สุด")
        if ($this->looksLikeCelticFarewell($messageText)) {
            Log::info('Celtic aftercare: จับสัญญาณลาจากลูกค้า → กล่าวลา+อวยพร', [
                'reading_id' => $reading->id,
                'text_preview' => mb_substr($messageText, 0, 40),
            ]);

            return $this->closeCelticAftercare($reading, 'customer_farewell');
        }

        // ยังคุยอยู่ → เลื่อนนาฬิกาเงียบออกไป แล้วปล่อยให้ AI ตอบตามปกติ
        $reading->setConversationState('celtic_aftercare_last_msg_at', now()->toIso8601String());

        return null;
    }

    /**
     * 🤝 (2026-08-29) ลูกค้าขอเปิดดวงรอบใหม่ระหว่างช่วงคุยต่อหรือไม่
     *
     * ตั้งใจให้แคบ — จับเฉพาะคำสั่งเปิดรอบใหม่ตรง ๆ / ราคาแพคเกจ
     * ห้ามกว้าง: "ดูให้หน่อย" กลางวงคือถามต่อในรอบเดิม ไม่ใช่ขอรอบใหม่
     */
    protected function looksLikeNewReadingRequestDuringAftercare(string $text): bool
    {
        $clean = mb_strtolower(trim($this->normalizeUserInput($text)));
        if ($clean === '') {
            return false;
        }

        $exact = [
            'ดูดวง', 'ดูดวงใหม่', 'ดูใหม่', 'เปิดไพ่ใหม่', 'ทำนายใหม่', 'เริ่มใหม่',
            'celtic', 'ดูดวงต่อ', 'ขอดูดวง', 'ดูดวงอีก', 'ดูอีกรอบ', 'อีกรอบ',
        ];

        foreach ($exact as $kw) {
            if ($clean === $kw) {
                return true;
            }
        }

        // ราคาแพคเกจพิมพ์เอง (39/99) = เจตนาเปิดบิลใหม่ชัดเจน
        $celticPrice = (int) ($this->settings->celtic_cross_price ?? 99);
        $deepPrice = (int) ($this->settings->deep_reading_price ?? 39);
        foreach ([$celticPrice, $deepPrice] as $price) {
            if ($clean === (string) $price) {
                return true;
            }
        }

        return false;
    }

    /**
     * 🤝 (2026-08-29) ปิดช่วงคุยต่อ — กล่าวลา + อวยพร แล้วเก็บของให้เรียบร้อย
     *
     * ⚠️ ห้าม generate Grand Finale ซ้ำ — ลูกค้าได้บทสรุปไปแล้วตอนนาทีที่ 15
     *    (บิลจริงใช้ 32,376 tokens ต่อบทสรุป 1 ครั้ง — ยิงซ้ำ = จ่าย 2 เท่าให้ของที่ลูกค้ามีแล้ว)
     *    ตรงนี้เป็นคำอวยพรสั้น ๆ ไม่ผ่าน AI = ฟรีและไม่มีทางล่ม
     *
     * @param  string  $reason  customer_farewell | idle | total_cap | new_reading_requested
     * @return array|null null = ไม่ต้องส่งอะไร (เคสลูกค้าขอเปิดรอบใหม่ — ปล่อยไหลไป flow ปกติ)
     */
    protected function closeCelticAftercare(FortuneReading $reading, string $reason): ?array
    {
        // 🛡️ idempotent — กัน cron กับ webhook ปิดชนกันแล้วลูกค้าได้คำอวยพร 2 กล่อง
        if ((bool) $reading->getConversationState('celtic_aftercare_farewelled', false)) {
            return null;
        }

        $reading->setConversationState('celtic_aftercare_farewelled', true);
        $reading->setConversationState('celtic_aftercare_closed_reason', $reason);
        $reading->setConversationState('celtic_aftercare_closed_at', now()->toIso8601String());

        if (method_exists($this, 'clearProSessionFlags')) {
            $this->clearProSessionFlags($reading);
        }

        // 🔓 ปลดแคช "กำลังทำนายอยู่" ทันที — ไม่งั้นลูกค้าพิมพ์ "ดูดวง" ต่อแล้วยังโดนบล็อก
        try {
            $uid = (string) ($reading->platform_user_id ?: $reading->facebook_user_id ?: '');
            if ($uid !== '' && method_exists($this, 'clearPaidActiveCache')) {
                $this->clearPaidActiveCache($uid);
            }
        } catch (\Throwable $e) {
            Log::debug('Celtic aftercare: clear paid-active cache fail (non-blocking)', [
                'reading_id' => $reading->id,
                'error' => $e->getMessage(),
            ]);
        }

        Log::info('Celtic aftercare: ปิดช่วงคุยต่อ', [
            'reading_id' => $reading->id,
            'reason' => $reason,
        ]);

        // ลูกค้าขอเปิดรอบใหม่ = ไม่ต้องอวยพรขวางทาง ปล่อยเข้า flow เปิดบิลเลย
        if ($reason === 'new_reading_requested') {
            return null;
        }

        // 🧩 ใช้ action 'celtic_session_ended' เดิม ไม่ประดิษฐ์ action ใหม่ —
        //   ChannelManager จัดการ review_invite / ของเสริมดวง แบบ **แยกตาม action**
        //   action ใหม่จะตกไป default = ส่งแต่ข้อความ แล้วคำชวนรีวิวหายเงียบ
        //   ฟิลด์รูป/เสียงที่ไม่ได้ส่งมา ฝั่งนั้น `! empty()` กันไว้หมดแล้ว → ข้ามเองปลอดภัย
        return [
            'action' => 'celtic_session_ended',
            'message' => $this->buildCelticFarewellMessage($reading, $reason),
            'reading' => $reading,
            'end_reason' => "aftercare_{$reason}",
            // ⭐ ตอนนี้คือ "จุดจบจริง" แล้ว → ชวนรีวิว + เสนอของเสริมดวงได้
            //   (ตอนส่งบทสรุปนาทีที่ 15 กันไว้ด้วย is_lingering เพราะยังไม่ลา ⇒ ลูกค้าได้ครั้งเดียว ไม่ซ้ำ)
            'is_lingering' => false,
            'review_invite' => $this->attachAftercareReviewInvite($reading),
            // ไม่ส่งรูปไพ่/แผนที่ดาว/เสียงซ้ำ — ลูกค้าได้ไปแล้วพร้อมบทสรุปตอนนาทีที่ 15
        ];
    }

    /**
     * 🤝 (2026-08-29) คำกล่าวลา + อวยพรส่งท้าย (สคริปต์ ไม่ผ่าน AI)
     *
     * เจ้าของสั่ง: "บอทก็กล่าวลาและอวยพร เพื่อความประทับใจที่สุด"
     * แม่หมอเป็นหญิงเสมอ — ลงท้าย "ค่ะ/นะคะ" ห้าม "ครับ" เด็ดขาด
     */
    protected function buildCelticFarewellMessage(FortuneReading $reading, string $reason): string
    {
        $name = $reading->resolveCustomerName();

        $opening = match ($reason) {
            'idle' => "🌙 *แม่หมอเห็นว่าเจ้าชะตาพักไปแล้วนะคะ คุณ{$name}*\n\n"
                ."แม่หมอขอเก็บไพ่ทั้งสิบใบกลับเข้าสำรับก่อนนะคะ\n\n",
            'total_cap' => "🌙 *ครบเวลาของวงไพ่รอบนี้แล้วค่ะ คุณ{$name}*\n\n"
                ."แม่หมออยู่เป็นเพื่อนจนสุดทางที่พลังไพ่จะพาไปได้แล้วนะคะ\n\n",
            default => "🙏 *ขอบคุณที่ไว้วางใจแม่หมอนะคะ คุณ{$name}*\n\n",
        };

        return $opening
            ."✨ *คำอวยพรส่งท้ายจากแม่หมอจันทรา*\n\n"
            ."ขอให้เส้นทางข้างหน้าของเจ้าชะตาสว่างไสว\n"
            ."สิ่งที่เพียรทำมาทั้งหมด ขอให้ออกดอกออกผลทันตาเห็น\n"
            ."เรื่องที่หนักอยู่ ขอให้คลี่คลายลงทีละเปลาะ\n"
            ."คนที่คิดร้ายขอให้ห่างไกล คนที่จริงใจขอให้เข้ามา\n"
            ."เงินทองไหลมาไม่ขาดสาย สุขภาพแข็งแรง จิตใจเป็นสุข 🙏\n\n"
            ."💫 คำทำนายเป็นเพียงแสงไฟชี้ทาง — เจ้าชะตาเป็นคนเดินเองนะคะ\n\n"
            .str_repeat('━', 17)."\n\n"
            .$this->closingInviteLine($reading);
    }

    /**
     * 🤝 (2026-08-29) แนบคำชวนรีวิวตอนปิดช่วงคุยต่อ (จุดจบจริง)
     *
     * non-blocking: รีวิวพังห้ามกลืนคำอวยพร → fail = null
     */
    protected function attachAftercareReviewInvite(FortuneReading $reading): ?array
    {
        try {
            return (new \App\Services\Fortune\FortuneReviewInviteService($this->settings))
                ->attachIfEligible($reading);
        } catch (\Throwable $e) {
            Log::warning('Celtic aftercare: review invite attach fail (non-blocking)', [
                'reading_id' => $reading->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * 🎧 (2026-06-20) ตรวจว่าลูกค้าขอให้ "ผู้ช่วย AI อ่านบทสรุปให้ฟัง" หรือไม่
     *
     * ใช้ keyword จำเพาะ (contiguous) — กัน false positive กับการขอให้ทำนาย/อ่านไพ่
     * เช่น "อ่านดวงให้ฟัง" จะไม่ match เพราะไม่ใช่ "อ่านให้ฟัง" ติดกัน
     */
    protected function looksLikeVoiceReadRequest(string $text): bool
    {
        $t = mb_strtolower(trim($text));
        if ($t === '') {
            return false;
        }

        $keywords = [
            'อ่านให้ฟัง', 'อ่านให้หน่อย', 'อ่านสรุปให้ฟัง', 'อ่านบทสรุปให้ฟัง',
            'ฟังเสียงสรุป', 'ฟังเสียง', 'ขอเสียง', 'ขอไฟล์เสียง', 'อยากฟังเสียง',
            'อ่านเป็นเสียง', 'อ่านออกเสียง', 'เสียงสรุป',
        ];

        foreach ($keywords as $kw) {
            if (mb_strpos($t, $kw) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * 🎧 (2026-06-20) หา reading ล่าสุดที่จ่ายแล้ว + มีเนื้อหาพร้อมอ่านเสียง (24 ชม.)
     *
     * ครอบ 2 แบบ (user: "แบบ 39 ก็ให้อ่านออกเสียงได้"):
     *   - Celtic 99 → มี voice_summary_source_text ใน state (เก็บตอน endCelticSession)
     *   - Deep 39  → มี deep_response (column — คำทำนายเชิงลึก)
     * เงื่อนไข: จ่ายแล้ว + จบ session (COMPLETED) → กันยิงตอนยังทำนายไม่จบ
     */
    protected function findRecentVoiceableReading(string $userId): ?FortuneReading
    {
        try {
            return FortuneReading::whereIn('reading_type', [
                FortuneReading::READING_TYPE_CELTIC_CROSS,
                FortuneReading::READING_TYPE_DEEP,
            ])
                ->where('is_paid', true)
                ->where('conversation_status', FortuneReading::STATUS_COMPLETED)
                ->where(function ($q) use ($userId) {
                    // ⚠️ fortune_readings ไม่มีคอลัมน์ line_user_id — LINE เก็บ id ที่ platform_user_id
                    //   (+ facebook_user_id ซ้ำ). ห้ามใส่ line_user_id ใน WHERE → QueryException
                    $q->where('platform_user_id', $userId)
                        ->orWhere('facebook_user_id', $userId);
                })
                // 🎧 (2026-06-21 owner spec) ลูกค้าเก่าเรียกฟัง "คำทำนายล่าสุด" ได้เสมอ — ไม่จำกัดเวลา
                //   source (deep_response / celtic_grand_finale_summary) อยู่ใน DB ถาวร → reuse ไฟล์เดิม
                //   ถ้ามี (generate() cache) ไม่มีก็ regen ใหม่. orderByDesc+first() = เอาอันล่าสุดอันเดียว
                ->where(function ($q) {
                    // celtic = source ใน state (voice_summary_source_text ที่เก็บตอนจบ
                    //   หรือ celtic_grand_finale_summary — ครอบ reading เก่าก่อนมีฟีเจอร์ด้วย)
                    // deep = deep_response (column — คำทำนายเชิงลึก)
                    $q->where('conversation_state', 'like', '%voice_summary_source_text%')
                        ->orWhere('conversation_state', 'like', '%celtic_grand_finale_summary%')
                        ->orWhere(function ($q2) {
                            $q2->where('reading_type', FortuneReading::READING_TYPE_DEEP)
                                ->whereNotNull('deep_response')
                                ->where('deep_response', '!=', '');
                        });
                })
                ->orderByDesc('updated_at')
                ->first();
        } catch (\Throwable $e) {
            \Log::debug('Fortune: findRecentVoiceableReading fail (non-blocking)', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * 🎧 (2026-06-20) หา reading ที่ "จ่ายแล้วแต่ยังไม่จบ" (กำลังทำนาย) — ใช้บอกลูกค้าให้รอ
     *
     * เมื่อลูกค้าพิมพ์ "อ่านให้ฟัง" ตอนยังไม่มีบทสรุป (ทุกจุด) → ตอบ "รอทำนายจบก่อน" แทนเงียบ
     * (paid + ยังไม่ COMPLETED + ภายใน 2 ชม. = กำลังอยู่ในกระบวนการทำนายจริง)
     */
    protected function findPaidReadingAwaitingSummary(string $userId): ?FortuneReading
    {
        try {
            return FortuneReading::whereIn('reading_type', [
                FortuneReading::READING_TYPE_CELTIC_CROSS,
                FortuneReading::READING_TYPE_DEEP,
            ])
                ->where('is_paid', true)
                ->where('conversation_status', '!=', FortuneReading::STATUS_COMPLETED)
                ->where(function ($q) use ($userId) {
                    $q->where('platform_user_id', $userId)
                        ->orWhere('facebook_user_id', $userId);
                })
                ->where('updated_at', '>=', now()->subHours(2))
                ->orderByDesc('updated_at')
                ->first();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * 🎧 (2026-06-20) จัดการคำขอ "อ่านให้ฟัง" — dispatch voice job แบบ on-demand
     *
     * คืน:
     *   - array = handled (ส่งข้อความ + dispatch job)
     *   - null  = ไม่เกี่ยว → ปล่อยให้ flow ปกติทำงานต่อ (ไม่ดักมั่ว)
     *
     * Gate แน่น: ต้องเปิด voice_summary_enabled + keyword ตรง + มี reading ที่มีบทสรุปพร้อม
     */
    protected function handleCelticVoiceReadRequest(string $userId, string $messageText): ?array
    {
        if (empty($this->settings->voice_summary_enabled)) {
            return null;
        }
        if (! $this->looksLikeVoiceReadRequest($messageText)) {
            return null;
        }

        $reading = $this->findRecentVoiceableReading($userId);
        if (! $reading) {
            // 🎧 (2026-06-20 owner spec) ยังไม่มีบทสรุปพร้อมอ่าน:
            //   - ถ้ามี reading "จ่ายแล้ว + กำลังทำนายอยู่" → บอกให้รอทำนายจบก่อน (ไม่เงียบ)
            //   - ถ้าไม่มี reading เลย → null ปล่อย flow ปกติ (อย่า hijack แชททั่วไป)
            $pending = $this->findPaidReadingAwaitingSummary($userId);
            if ($pending) {
                return [
                    'action' => 'celtic_voice_generating',
                    'message' => "🎧 ตอนนี้แม่หมอกำลังทำนายให้อยู่นะคะ\n\n"
                        .'รอให้คำทำนายเสร็จก่อน แล้วพิมพ์ *"อ่านให้ฟัง"* อีกครั้ง — ผู้ช่วย AI จะอ่านบทสรุปให้ฟังค่ะ ✨',
                    'reading' => $pending,
                ];
            }

            return null;
        }

        // 🛡️ เคารพ tier scope (celtic_99_only / paid_all / all) — กัน dispatch แล้ว generate() เด้ง
        if (! $this->settings->shouldGenerateVoiceSummary($reading)) {
            return null;
        }

        // กันสั่งซ้ำถี่ๆ (มือลั่น/กดปุ่มรัว) — ภายใน 60 วิ ตอบว่ากำลังทำ
        $throttleKey = "fortune:voice_ondemand:{$reading->id}";
        if (\Illuminate\Support\Facades\Cache::has($throttleKey)) {
            return [
                'action' => 'celtic_voice_generating',
                'message' => '🎧 ผู้ช่วย AI กำลังอ่านบทสรุปให้ฟังอยู่ค่ะ รอสักครู่นะคะ ✨',
                'reading' => $reading,
            ];
        }
        \Illuminate\Support\Facades\Cache::put($throttleKey, true, 60);

        // 🔁 รีเซ็ต pushed flag — ให้ job re-push ได้ทุกครั้งที่ลูกค้าขอ (ใช้ไฟล์ cache เดิม ไม่ regen)
        //    (job มี idempotent skip ถ้า voice_summary_pushed=true → re-request จะเงียบถ้าไม่ reset)
        try {
            $reading->setConversationState('voice_summary_pushed', false);
        } catch (\Throwable $e) {
            // ignore — best-effort
        }

        try {
            ProcessVoiceSummaryJob::dispatchSmart(
                $reading->id,
                $reading->platform ?: ($this->currentPlatform ?? 'facebook'),
                $reading->platform_user_id ?: ($reading->facebook_user_id ?: $userId)
            );

            \Log::info('🎧 Celtic: on-demand voice dispatched', [
                'reading_id' => $reading->id,
                'platform' => $reading->platform,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Cache::forget($throttleKey);
            \Log::warning('Celtic: on-demand voice dispatch fail', [
                'reading_id' => $reading->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'action' => 'celtic_voice_failed',
                'message' => 'ขออภัยค่ะ ระบบอ่านเสียงขัดข้องชั่วคราว ลองพิมพ์ "อ่านให้ฟัง" ใหม่อีกครั้งนะคะ 🙏',
                'reading' => $reading,
            ];
        }

        return [
            'action' => 'celtic_voice_generating',
            'message' => "🎧 ได้เลยค่ะ — ผู้ช่วย AI กำลังอ่านบทสรุปคำทำนายให้ฟัง รอสักครู่นะคะ ✨\n"
                .'_(เป็นเสียงระบบผู้ช่วย AI ไม่ใช่เสียงแม่หมอนะคะ)_',
            'reading' => $reading,
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
     * 💾 (2026-08-17) ฝาก "คำถามที่มาหลังหมดเวลา" ไว้เป็นแถว pending
     *
     * ให้ Grand Finale ยกมาตอบปิดให้จบ (buildGrandFinalePrompt รับ $pendingQuestions อยู่แล้ว
     * ตั้งแต่ 2026-08-07 — ที่ขาดคือ "แถว" สำหรับคำถามที่มาตอนรอบปิดพอดี)
     *
     * เกณฑ์ที่ *ไม่* ฝาก (ไม่ใช่คำถามจริง — ฝากไปก็ทำให้บทสรุปเพี้ยน):
     *   - สั้นกว่า 8 ตัวอักษร
     *   - คำตอบรับ/คำทักทาย (looksLikeReadinessAck) เช่น "ค่ะ" "โอเค" "พร้อม"
     *   - เศษวันเกิด (looksLikeBirthdateFragmentOnly) เช่น "วันจันทร์" "ปีฉลู"
     *   - ซ้ำกับข้อที่ฝากไว้แล้ว (FB/LINE retry ยิงข้อความเดิมซ้ำได้)
     *   - ฝากไปแล้วครบ 3 ข้อ (กันบทสรุปบวมจนตอบไม่ครบสักข้อ)
     *
     * ⚠️ best-effort ทั้งเมธอด — ล้มเหลวยังไงก็ห้ามขวางการปิดรอบ
     *    (ลูกค้าที่จ่ายแล้วต้องได้บทสรุปเสมอ)
     *
     * @return bool true = ฝากสำเร็จ
     */
    protected function stashUnansweredCelticQuestion(FortuneReading $reading, string $question): bool
    {
        try {
            $q = trim($question);

            if (mb_strlen($q) < 8
                || $this->looksLikeReadinessAck($q)
                || $this->looksLikeBirthdateFragmentOnly($q)
                || $this->looksLikeCelticStatusInquiry($q)) {
                return false;
            }

            $q = mb_substr($q, 0, 1000);

            $pending = $reading->celticQuestions()->whereNull('answered_at')->get();
            if ($pending->count() >= 3) {
                Log::info('Celtic: มีคำถามค้างครบเพดานแล้ว → ไม่ฝากเพิ่ม', [
                    'reading_id' => $reading->id,
                    'pending' => $pending->count(),
                ]);

                return false;
            }

            foreach ($pending as $row) {
                if (trim((string) $row->question) === $q) {
                    return false;
                }
            }

            // ⚠️ (fortune_reading_id, sequence) มี unique index fcq_reading_seq_unique
            //    → ต้องนับจาก MAX ของ "ทุกแถว" ไม่ใช่เฉพาะแถวที่ตอบแล้ว
            $seq = max((int) $reading->celticQuestions()->max('sequence') + 1, 1);

            \App\Models\FortuneCelticQuestion::create([
                'fortune_reading_id' => $reading->id,
                'sequence' => $seq,
                'question' => $q,
            ]);

            Log::info('Celtic: ฝากคำถามที่มาหลังหมดเวลา → ให้บทสรุปตอบปิด', [
                'reading_id' => $reading->id,
                'sequence' => $seq,
                'q_preview' => mb_substr($q, 0, 60),
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::warning('Celtic: ฝากคำถามค้างไม่สำเร็จ (non-blocking)', [
                'reading_id' => $reading->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
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
     * 🆕 (2026-05-31) ตรวจว่าข้อความเป็นแค่ "คำทักทาย/ตอบรับ" ไม่ใช่คำถามทำนาย
     *
     * เคสจริง R4474 (FTU-260531-P7895): ลูกค้าพิมพ์ "พร้อม" หลังเปิดไพ่ → Q1 ยิงทำนายมั่ว
     *   + กินโควต้า เพราะ Q1 path เดิมไม่มี TYPE classifier
     *
     * ใช้ใน handleCelticAwaitingQuestion (เฉพาะก่อนคำถามแรก) → ชวนถามแทนการทำนาย
     *
     * Safety (กัน false positive — ห้ามจับคำถามจริง):
     *   1. reject ทันทีถ้ามี particle คำถาม (ไหม/เมื่อไหร่/อะไร/...) หรือ keyword ขอดูดวง
     *   2. exact-match แก่นคำ หลัง strip คำลงท้าย/emoji — ไม่ใช่ str_contains
     *   3. จำกัดความยาว (กันประโยคเล่าเรื่อง)
     *   → "พร้อมดูเรื่องงานไหม" (มี "ไหม") / "ความรัก" (topic word) จะ "ไม่" match
     */
    /**
     * 📅 (2026-06-18) เศษวันเกิดล้วน (whole-match) — กันถูกนับเป็นคำถามทำนาย (เคส R7145 "วันจันทร"/"ปีฉลู")
     *
     * precision สูงโดยตั้งใจ: คืน true เฉพาะเมื่อ "core" (หลังตัด prefix วัน/ปี/เกิด + คำลงท้าย) = ชื่อวัน
     *   หรือ ปีนักษัตร *พอดีทั้งคำ* — กัน false positive คำที่บังเอิญมี substring (เช่น "วอกแวก" ≠ "วอก")
     *   + reject ถ้ามี marker คำถาม (กัน "วันจันทร์ดีไหม"). กำกวม/ยาว → false (ปล่อยให้ AI จัด TYPE)
     */
    protected function looksLikeBirthdateFragmentOnly(string $text): bool
    {
        $clean = mb_strtolower(trim($text));
        if ($clean === '' || mb_strlen($clean) > 20) {
            return false;
        }

        // ลบ emoji + คำลงท้ายสุภาพ
        $clean = trim((string) preg_replace('/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}\x{2190}-\x{21FF}\x{FE0F}\x{200D}]/u', '', $clean));
        $clean = trim((string) preg_replace('/\s*(ค่ะ|คะ|ค่า|ครับ|คับ|จ้า|จ้ะ|นะ|น่ะ|ฮะ|ฮ่ะ)\s*$/u', '', $clean));

        // มี marker คำถาม → ไม่ใช่เศษวันเกิดล้วน (กัน "วันจันทร์ดีไหม")
        foreach (['ไหม', 'มั้ย', 'มัย', 'หรือ', 'เหรอ', 'หรอ', 'เมื่อไหร่', 'ทำไม', 'อะไร', 'ยังไง', 'ที่ไหน', 'ใคร', '?'] as $qm) {
            if (str_contains($clean, $qm)) {
                return false;
            }
        }

        // ตัด prefix บริบทวันเกิด แล้วต้องเหลือ "ชื่อวัน/ปีนักษัตร" พอดีทั้งคำ (whole-match)
        $core = trim((string) preg_replace('/^(วันเกิด|เกิดวัน|เกิดปี|วันที่|เกิด|วัน|ปี)\s*/u', '', $clean));
        $days = ['อาทิตย์', 'จันทร์', 'จันทร', 'อังคาร', 'พุธ', 'พฤหัส', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'];
        $zodiac = ['ชวด', 'ฉลู', 'ขาล', 'เถาะ', 'มะโรง', 'มะเส็ง', 'มะเมีย', 'มะแม', 'วอก', 'ระกา', 'จอ', 'กุน'];
        $all = array_merge($days, $zodiac);

        return in_array($core, $all, true) || in_array($clean, $all, true);
    }

    protected function looksLikeReadinessAck(string $text): bool
    {
        $clean = mb_strtolower(trim($text));
        if ($clean === '') {
            return false;
        }

        // ลบ emoji + variation selector (เก็บตัวอักษร/ตัวเลข/ช่องว่าง/คำลงท้าย)
        $clean = trim((string) preg_replace('/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}\x{2190}-\x{21FF}\x{FE0F}\x{200D}]/u', '', $clean));

        // 🚫 มี particle/keyword คำถาม → ไม่ใช่ ack เด็ดขาด (กัน false positive คำถามจริง)
        $questionMarkers = [
            'ไหม', 'มั้ย', 'มัย', 'หรือ', 'เหรอ', 'หรอ', 'รึ', 'เมื่อไหร่', 'เมื่อไร',
            'ทำไม', 'อะไร', 'ยังไง', 'อย่างไร', 'ที่ไหน', 'ใคร', 'กี่', 'เท่าไหร่', 'เท่าไร', '?',
            'ขอถาม', 'อยากรู้', 'อยากถาม', 'อยากดู', 'ช่วยดู', 'ดูเรื่อง', 'ดูให้', 'ดูหน่อย', 'ทำนาย',
        ];
        foreach ($questionMarkers as $qm) {
            if (str_contains($clean, mb_strtolower($qm))) {
                return false;
            }
        }

        // strip คำลงท้ายสุภาพไทย + honorific (แม่/หมอ) — define ก่อน ใช้ทั้ง thx-remainder + core ด้านล่าง
        $tailPattern = '/\s*(ค่ะ|คะ|ค่า|ครับ|คับ|ครับผม|จ้า|จ้ะ|จ๊ะ|จ้าา|จ๋า|นะ|น่ะ|แล้ว|เลย|ละ|ล่ะ|ฮะ|ฮ่ะ|แม่หมอ|แม่|หมอ|ๆ)\s*$/u';

        // 🆕 (2026-05-31 v2) คำขอบคุณ = ack (เคส R4543 FTU-260531-V2978: "ขอบคุณค่ะแม่" → ยิง Q1 มั่ว)
        // 🩹 (2026-06-12 v3) เดิม return true ทันทีที่ขึ้นต้น "ขอบคุณ" — comment เดิมอ้างว่า
        //   "ขอบคุณค่ะ แล้วงานล่ะ" ถูก reject ที่ questionMarkers = ไม่จริง ("แล้วเรื่องงานล่ะ"
        //   ไม่มี marker สักตัวในลิสต์) → คำถามที่ตามหลังคำขอบคุณโดนกินเป็น ack
        //   ใหม่: ตัดคำขอบคุณ + intensifier + คำลงท้ายออก → ถ้ายังเหลือเนื้อหา = ไม่ใช่ ack ล้วน
        //   ปล่อยไหลไปให้ AI TYPE classifier ตัดสิน (อาจเป็นคำถามต่อ)
        foreach (['ขอบพระคุณ', 'ขอบคุณ', 'ขอบคุน', 'ขอบใจ', 'thank you', 'thankyou', 'thanks', 'thank', 'thx'] as $thx) {
            if (! str_starts_with($clean, mb_strtolower($thx))) {
                continue;
            }

            $rest = trim(mb_substr($clean, mb_strlen($thx)));
            // ตัด intensifier นำหน้า (มากๆ/จริงๆ/หลายๆ/เด้อ) + คำลงท้าย 3 รอบ (เผื่อซ้อน "นะคะแม่หมอ")
            $rest = trim((string) preg_replace('/^(?:มากมาย|มาก|จริง|หลาย|เด้อ|งับ|ฮะ|ค้าบ|ๆ)+/u', '', $rest));
            for ($i = 0; $i < 3; $i++) {
                $rest = trim((string) preg_replace($tailPattern, '', $rest));
            }

            if ($rest === '' || mb_strlen($rest) <= 2) {
                return true; // ขอบคุณล้วน (เคส R4543 "ขอบคุณค่ะแม่") = ack
            }

            return false; // มีเนื้อหาตามหลังคำขอบคุณ ("ขอบคุณค่ะ แล้วเรื่องงานล่ะ") — ไม่ใช่ ack ล้วน
        }
        $core = trim((string) preg_replace($tailPattern, '', $clean));
        $core = trim((string) preg_replace($tailPattern, '', $core));

        // มีแต่คำลงท้ายสุภาพล้วน ("ค่ะ"/"ครับ"/"จ้า") = ตอบรับ ไม่ใช่คำถาม
        if ($core === '') {
            return true;
        }

        // ack จริงสั้น — กันประโยคเล่าเรื่องยาว
        if (mb_strlen($core) > 18) {
            return false;
        }

        // 🆕 (2026-06-05) compound readiness — ขึ้นต้น "พร้อม..." = ตอบรับพร้อม ไม่ใช่คำถาม
        //   เคส R5023 (FTU-260605-X8071): ลูกค้าพิมพ์ "พร้อมฟัง" → เดิมไม่อยู่ใน whitelist (มีแค่
        //   "พร้อม") → หลุดไปให้ AI classifier ที่ bias→A → นับเป็น Q1 มั่ว (เสีย 1/5 สิทธิ์ จ่าย 99฿).
        //   ครอบ "พร้อมฟัง/พร้อมแล้ว/พร้อมรับฟัง/พร้อมเริ่ม" ฯลฯ ด้วย prefix แทน enumerate ทุกแบบ.
        //   ปลอดภัย: คำถามจริงถูก reject ที่ questionMarkers ด้านบนแล้ว (เช่น "พร้อมดูเรื่องงานไหม")
        //   + core ≤ 18 ตัวอักษร (ผ่าน guard ความยาวมาแล้ว) → กันประโยคเล่าเรื่องยาว
        //   ใช้เฉพาะ prefix "พร้อม" (= ready ชัดเจน) — ไม่ใช้ "เริ่ม" prefix กัน false positive
        //   "เริ่มต้นชีวิตใหม่"/"เริ่มงานใหม่" (เริ่ม-ack จริงอยู่ใน $acks ด้านล่างแล้ว)
        if (str_starts_with($core, 'พร้อม')) {
            return true;
        }

        // whitelist คำตอบรับ/พร้อม (ไทย/อังกฤษ/ลาว) — exact match แก่นคำ
        //   topic word (ความรัก/งาน/เงิน) ไม่อยู่ใน list → ผ่านไปทำนายจริง ✅
        $acks = [
            'พร้อม', 'พรอม', 'เริ่ม', 'เริ่มเลย', 'เริ่มได้', 'เริ่มได้เลย', 'เอา', 'เอาเลย',
            'โอเค', 'โอเก', 'oke', 'ok', 'okay', 'okk', 'yes', 'yep', 'y',
            'ใช่', 'ตกลง', 'จัดมา', 'จัดไป', 'จัด', 'ได้', 'ได้เลย', 'ดี', 'เยี่ยม',
            'อืม', 'อืมม', 'อึม', 'รับทราบ', 'เข้าใจ', 'เข้าใจแล้ว', 'จ้า', 'ค่ะ', 'คะ', 'ครับ',
            // 🆕 (2026-06-05) ตอบรับแบบ "รอฟัง" (เคส 5023 เผื่อพิมพ์สั้น) — listen-ack ที่ไม่ขึ้นต้นพร้อม/เริ่ม
            'ฟัง', 'ฟังอยู่', 'รอฟัง', 'รับฟัง', 'รออยู่', 'ว่ามา', 'ว่ามาเลย', 'บอกมา', 'บอกมาเลย',
            'ພ້ອມ', 'ໂອເຄ', 'ໄດ້', 'ໄດ້ເລີຍ',
        ];

        return in_array($core, $acks, true);
    }

    /**
     * Detection: ลูกค้าต้องการ Celtic Cross
     *
     * @param  bool  $includePrice  นับ "99 บาท" เป็นสัญญาณด้วยไหม
     *                              (resolveExplicitTierRequest ปิดไว้ เพราะจัดการเลขเองพร้อม length guard)
     */
    protected function matchesCelticCrossKeyword(string $text, bool $includePrice = true): bool
    {
        $keywords = [
            'celtic cross', 'celtic', 'เซลติก', 'ไพ่ยิปซีเต็ม', 'ไพ่ยิปซีเต็มสำรับ',
            'ดูเต็ม', 'ดูชุดใหญ่', 'ทาโรต์เต็ม', 'tarot full',
        ];

        $lower = mb_strtolower(trim($text));
        foreach ($keywords as $kw) {
            if (mb_strpos($lower, mb_strtolower($kw)) !== false) {
                return true;
            }
        }

        // 💰 (2026-08-03) ราคา Celtic — ต้องมี word boundary
        //   🐛 เดิมใช้ substring '99 บาท' ตรงๆ → "ราคา 1399 บาทซื้อดีไหม" มี "99 บาท" อยู่ข้างใน
        //      → ถูกลากเข้า Celtic flow (สร้างบิล 99 ให้คนที่แค่ถามเรื่องอื่น)
        //   ⚠️ ดึงราคาจาก service ไม่ hardcode — admin เปลี่ยนราคาได้
        if ($includePrice) {
            $celticPriceInt = (int) app(CelticCrossService::class)->getPrice();
            if ($celticPriceInt > 0
                && preg_match('/(?<![\d])'.$celticPriceInt.'(?![\d])\s*(฿|บาท|บ\.?)/u', $lower)) {
                return true;
            }
        }

        return false;
    }
}
