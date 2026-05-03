<?php

namespace App\Services\Fortune;

use App\Models\FortuneReading;
use App\Services\FortuneLocaleService;
use Illuminate\Support\Facades\Log;

/**
 * 🔒 Trait: Pay-First Gate (2026-05-03)
 *
 * นโยบาย "ขอคำทำนายก่อนทำได้ครั้งเดียวต่อ 1 คน":
 *   - ลูกค้ามีบิล deep/celtic ที่ยังไม่จ่าย → lock ทุก service
 *   - คุยปกติ / ดูดวงใหม่ / ฟรี / ดูประวัติ — ทั้งหมด redirect ไปจ่ายเงิน
 *   - ยกเว้น keyword "แอดมิน" / "คุยกับคน" → handoff ปกติ (ตามระบบเดิม)
 *
 * Auto-revive expired bills (สูงสุด 3 รอบ):
 *   - บิล expired (status COMPLETED + ยังไม่จ่าย) ลูกค้ากลับมา → revive อัตโนมัติ
 *   - UPA ใหม่ + amount ใหม่ + bill_reference เดิม
 *   - revive_count + 1 — ครบ 3 → admin only
 *
 * Re-engagement (FB 24hr window):
 *   - ลูกค้าเงียบ >24 ชม. แล้วกลับมา → bot ใส่ greeting แม่หมอใน reply
 *   - track ใน conversation_state.last_reengagement_at — กันส่งซ้ำใน window
 */
trait PayFirstGateTrait
{
    /**
     * 🔒 Gate check — เรียกจาก processMessage หลัง dedup/lock
     *
     * @return array|null  null ถ้าไม่ block, array response ถ้า block
     */
    protected function payFirstGate(string $platform, string $platformUserId, string $messageText, ?array $userProfile = null): ?array
    {
        $blockingBill = FortuneReading::findBlockingUnpaidBill($platform, $platformUserId);

        if (! $blockingBill) {
            return null; // ไม่มีบิลค้าง → ผ่าน
        }

        // ✅ ยกเว้น admin handoff keyword (ลูกค้าขอคุยกับคน — ต้องผ่านเสมอ)
        if (method_exists($this, 'detectCustomerHandoffRequest')
            && $this->detectCustomerHandoffRequest($messageText)) {
            return null;
        }

        // ✅ ยกเว้น payment confirmation message ("โอนแล้ว" "จ่ายแล้ว" etc.)
        //    ให้ flow ปกติ (handlePendingPayment) เช็คให้
        if ($this->isPaymentConfirmationMessage($messageText)) {
            return null;
        }

        // ✅ (2026-05-03 audit fix #3) ยกเว้น cancel keyword — ลูกค้าต้องยกเลิกได้เสมอ
        //    หลังยกเลิก bill จะ status=COMPLETED + unpaid → gate ยังคง lock ในรอบหน้า
        //    (ป้องกันการ abuse: ยกเลิกแล้ว create ใหม่วน)
        if (method_exists($this, 'isCancelRequest') && $this->isCancelRequest($messageText)) {
            return null;
        }

        // 🚫 BLOCK + ส่งข้อความตามสถานะบิล
        return $this->buildBlockingResponse($blockingBill, $messageText, $userProfile);
    }

    /**
     * 💸 ตรวจว่าข้อความเป็นการแจ้งโอน (ปล่อยให้ flow ปกติเช็ค)
     */
    protected function isPaymentConfirmationMessage(string $text): bool
    {
        $clean = mb_strtolower(trim($text));
        $keywords = [
            // ไทย
            'โอนแล้ว', 'จ่ายแล้ว', 'จ่ายเงินแล้ว', 'โอนเงินแล้ว', 'ชำระแล้ว', 'paid',
            'เช็คสถานะ', 'เช็คบิล', 'check status', 'check_payment_status',
            'report_payment', 'แจ้งโอน', 'แจ้งชำระเงิน',
            // 🇱🇦 ลาว
            'ໂອນແລ້ວ', 'ຈ່າຍແລ້ວ', 'ຊຳລະແລ້ວ', 'ເຊັກສະຖານະ',
        ];
        foreach ($keywords as $kw) {
            if (mb_strpos($clean, mb_strtolower($kw)) !== false && mb_strlen($clean) <= 30) {
                return true;
            }
        }
        return false;
    }

    /**
     * 🚫 สร้าง response ตอน lock — แยกตามสถานะบิล
     */
    protected function buildBlockingResponse(FortuneReading $bill, string $messageText, ?array $userProfile = null): array
    {
        $name = $bill->facebook_user_name ?? ($userProfile['name'] ?? 'คุณ');
        $billRef = $bill->bill_reference ?? '-';
        $isCeltic = $bill->reading_type === FortuneReading::READING_TYPE_CELTIC_CROSS;
        $packageLabel = $isCeltic
            ? FortuneLocaleService::lo('🔮 Celtic Cross 99 บาท', '🔮 Celtic Cross 99 ບາດ')
            : FortuneLocaleService::lo('🔹 ดูดวงเชิงลึก 39 บาท', '🔹 ເບິ່ງດວງເຈາະເລິກ 39 ບາດ');

        $isExpired = $bill->conversation_status === FortuneReading::STATUS_COMPLETED;

        // 🌙 Re-engagement greeting (FB: >24hr / LINE: >1hr — guard ใน shouldSendReengagement)
        // 🆕 (2026-05-03 audit fix #12) เพิ่มข้อความบอกว่า "สิทธิ์ฟรีถูกใช้ไปแล้ว"
        //    ถ้าลูกค้าใช้ free_card ไปแล้ว — ป้องกันความสับสน
        $reengagement = '';
        if ($bill->shouldSendReengagement()) {
            $reengagement = FortuneLocaleService::lo(
                "🌙 *เจ้าชะตา{$name} กลับมาแล้วนะ*\nแม่หมอเตรียมดวงไว้ตั้งแต่ครั้งก่อน — ดาวยังรอเจ้าชะตาอยู่ ✨\n\n",
                "🌙 *ເຈົ້າຊາຕາ{$name} ກັບມາແລ້ວເດີ*\nແມ່ໝໍຕຽມດວງໄວ້ຕັ້ງແຕ່ຄັ້ງກ່ອນ — ດາວຍັງລໍເຈົ້າຊາຕາຢູ່ ✨\n\n"
            );
            $bill->setConversationState('last_reengagement_at', now()->toIso8601String());
            $reengageCount = (int) $bill->getConversationState('reengagement_count', 0);
            $bill->setConversationState('reengagement_count', $reengageCount + 1);
        }

        // 💎 ถ้าใช้ free_card แล้ว — แทรก context กันสับสน
        $platformUserId = $bill->platform_user_id ?? $bill->facebook_user_id;
        $platform = $bill->platform ?? 'facebook';
        if ($platformUserId && FortuneReading::hasUsedFreeCard($platform, $platformUserId)) {
            $reengagement .= FortuneLocaleService::lo(
                "💎 *(สิทธิ์ทำนายฟรีของเจ้าชะตาถูกใช้ไปแล้ว — ครั้งนี้ต้องโอนค่าครู)*\n\n",
                "💎 *(ສິດທິທຳນາຍຟຣີຂອງເຈົ້າຊາຕາໃຊ້ໄປແລ້ວ — ຄັ້ງນີ້ຕ້ອງໂອນຄ່າຄູ)*\n\n"
            );
        }

        // 📊 Case A: บิล active pending → resend QR + ยอดเดิม
        if (! $isExpired) {
            $payAmount = $bill->amount_paid
                ? number_format((float) $bill->amount_paid, 2)
                : '0.00';
            $expiresAt = $bill->updated_at->copy()->addMinutes(FortuneReading::PAYMENT_TIMEOUT_MINUTES);
            $remainingMinutes = (int) max(1, ceil(abs(now()->diffInMinutes($expiresAt, true))));

            $message = $reengagement . FortuneLocaleService::lo(
                "💸 *เจ้าชะตามีบิลค้างอยู่ค่ะ*\n\n"
                    . "📋 บิล: {$billRef} ({$packageLabel})\n"
                    . "💰 *ค่าครู: {$payAmount} บาท* (ทศนิยมต้องตรงเป๊ะ)\n"
                    . "⏳ บิลเหลืออีก {$remainingMinutes} นาที\n\n"
                    . "🌟 *ต้องโอนบิลค้างก่อน — ถึงจะคุยเรื่องอื่นได้นะ*\n"
                    . "(ระบบใช้ทศนิยมจับคู่บิล — โอนยอดอื่นจะไม่ตรง)\n\n"
                    . "✅ โอนเสร็จแล้ว → พิมพ์ 'โอนแล้ว' หรือ 'เช็คสถานะ'\n"
                    . "💬 อยากคุยกับคน → พิมพ์ 'แอดมิน'",
                "💸 *ເຈົ້າຊາຕາມີບິນຄ້າງຢູ່ເດີ*\n\n"
                    . "📋 ບິນ: {$billRef} ({$packageLabel})\n"
                    . "💰 *ຄ່າຄູ: {$payAmount} ບາດ* (ທົດສະນິຍົມຕ້ອງຕົງເປັະ)\n"
                    . "⏳ ບິນເຫຼືອອີກ {$remainingMinutes} ນາທີ\n\n"
                    . "🌟 *ຕ້ອງໂອນບິນຄ້າງກ່ອນ — ຈິ່ງຈະຄຸຍເລື່ອງອື່ນໄດ້ເດີ*\n"
                    . "(ລະບົບໃຊ້ທົດສະນິຍົມຈັບຄູ່ບິນ — ໂອນຍອດອື່ນຈະບໍ່ຕົງ)\n\n"
                    . "✅ ໂອນແລ້ວ → ພິມ 'ໂອນແລ້ວ' ຫຼື 'ເຊັກສະຖານະ'\n"
                    . "💬 ຢາກຄຸຍກັບຄົນ → ພິມ 'ແອັດມິນ'"
            );

            return [
                'action' => 'payment_lock_pending',
                'message' => $message,
                'reading' => $bill,
                'payment_qr_url' => $this->safeGeneratePaymentQr($bill),
                'show_qr' => true,
            ];
        }

        // 📊 Case B: บิล expired (status COMPLETED + ยังไม่จ่าย)

        // ⛔ Reach revive limit → admin only
        if ($bill->reachedReviveLimit()) {
            $reviveCount = $bill->getReviveCount();

            return [
                'action' => 'payment_lock_admin',
                'message' => $reengagement . FortuneLocaleService::lo(
                    "⛔ *เจ้าชะตามีบิลค้างไม่จ่ายมา {$reviveCount} ครั้งแล้ว*\n\n"
                        . "📋 บิลเก่า: {$billRef}\n\n"
                        . "🙏 แม่หมอขอให้เจ้าชะตา *ทักแอดมิน* เพื่อขอเปิดบิลใหม่\n"
                        . "พิมพ์ *\"แอดมิน\"* หรือ *\"คุยกับคน\"* — แอดมินจะเข้ามาคุยให้ค่ะ ✨",
                    "⛔ *ເຈົ້າຊາຕາມີບິນຄ້າງບໍ່ຈ່າຍມາ {$reviveCount} ຄັ້ງແລ້ວ*\n\n"
                        . "📋 ບິນເກົ່າ: {$billRef}\n\n"
                        . "🙏 ແມ່ໝໍຂໍໃຫ້ເຈົ້າຊາຕາ *ທັກແອັດມິນ* ເພື່ອຂໍເປີດບິນໃໝ່\n"
                        . "ພິມ *\"ແອັດມິນ\"* ຫຼື *\"ຄຸຍກັບຄົນ\"* — ແອັດມິນຈະເຂົ້າມາຄຸຍໃຫ້ເດີ ✨"
                ),
                'reading' => $bill,
            ];
        }

        // 🔄 Auto-revive — สร้าง UPA + amount ใหม่
        $revived = $bill->reviveBillForRepay();

        if (! $revived) {
            // Revive fail (UPA generate fail หรือเหตุอื่น) → admin only fallback
            Log::warning('PayFirstGate: revive fail — fallback admin', [
                'reading_id' => $bill->id,
            ]);

            return [
                'action' => 'payment_lock_admin',
                'message' => $reengagement . FortuneLocaleService::lo(
                    "🙏 ขออภัยค่ะ — ระบบเตรียมบิลใหม่ไม่สำเร็จในจังหวะนี้\n"
                        . "ขอให้เจ้าชะตาทักแอดมินช่วยเปิดบิลให้ได้ค่ะ — พิมพ์ \"แอดมิน\"",
                    "🙏 ຂໍອະໄພເດີ — ລະບົບຕຽມບິນໃໝ່ບໍ່ສຳເລັດໃນຈັງຫວະນີ້\n"
                        . "ຂໍໃຫ້ເຈົ້າຊາຕາທັກແອັດມິນຊ່ວຍເປີດບິນໃຫ້ — ພິມ \"ແອັດມິນ\""
                ),
                'reading' => $bill,
            ];
        }

        $newAmount = number_format((float) $revived->amount_paid, 2);
        $reviveCount = $revived->getReviveCount();
        $reviveText = $reviveCount === 1
            ? FortuneLocaleService::lo('(เปิดใหม่ครั้งที่ 1)', '(ເປີດໃໝ່ຄັ້ງທີ 1)')
            : FortuneLocaleService::lo("(เปิดใหม่ครั้งที่ {$reviveCount})", "(ເປີດໃໝ່ຄັ້ງທີ {$reviveCount})");

        $message = $reengagement . FortuneLocaleService::lo(
            "🔄 *แม่หมอเปิดบิลเดิมให้ใหม่แล้วนะ* {$reviveText}\n\n"
                . "📋 บิล: {$billRef} ({$packageLabel})\n"
                . "💰 *ค่าครูใหม่: {$newAmount} บาท* (ทศนิยมต้องตรงเป๊ะ)\n"
                . "⏳ บิลใหม่หมดอายุใน 30 นาที\n\n"
                . "🌟 ครั้งนี้แม่หมอขอให้เจ้าชะตา *ตั้งใจโอนก่อนนะ* — ไม่งั้นต้องทักแอดมินให้เปิดใหม่ค่ะ\n\n"
                . "✅ โอนเสร็จแล้ว → พิมพ์ 'โอนแล้ว' หรือ 'เช็คสถานะ'\n"
                . "💬 อยากคุยกับคน → พิมพ์ 'แอดมิน'",
            "🔄 *ແມ່ໝໍເປີດບິນເກົ່າໃຫ້ໃໝ່ແລ້ວເດີ* {$reviveText}\n\n"
                . "📋 ບິນ: {$billRef} ({$packageLabel})\n"
                . "💰 *ຄ່າຄູໃໝ່: {$newAmount} ບາດ* (ທົດສະນິຍົມຕ້ອງຕົງເປັະ)\n"
                . "⏳ ບິນໃໝ່ໝົດອາຍຸໃນ 30 ນາທີ\n\n"
                . "🌟 ຄັ້ງນີ້ແມ່ໝໍຂໍໃຫ້ເຈົ້າຊາຕາ *ຕັ້ງໃຈໂອນກ່ອນເດີ* — ບໍ່ດັ່ງນັ້ນຕ້ອງທັກແອັດມິນໃຫ້ເປີດໃໝ່\n\n"
                . "✅ ໂອນແລ້ວ → ພິມ 'ໂອນແລ້ວ' ຫຼື 'ເຊັກສະຖານະ'\n"
                . "💬 ຢາກຄຸຍກັບຄົນ → ພິມ 'ແອັດມິນ'"
        );

        return [
            'action' => 'payment_lock_revived',
            'message' => $message,
            'reading' => $revived,
            'payment_qr_url' => $this->safeGeneratePaymentQr($revived),
            'show_qr' => true,
        ];
    }

    /**
     * 🎯 ลอง generate PromptPay QR — fallback null ถ้า fail
     */
    protected function safeGeneratePaymentQr(FortuneReading $reading): ?string
    {
        try {
            if (method_exists($this, 'generatePromptPayQrImage') && $reading->amount_paid) {
                return $this->generatePromptPayQrImage((float) $reading->amount_paid, $reading->id);
            }
            if (method_exists($this, 'getPaymentQrImageUrl')) {
                return $this->getPaymentQrImageUrl();
            }
        } catch (\Throwable $e) {
            Log::warning('PayFirstGate: QR generate fail', [
                'reading_id' => $reading->id,
                'error' => $e->getMessage(),
            ]);
        }
        return null;
    }
}
