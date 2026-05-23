<?php

namespace App\Services\Fortune;

use App\Models\FortuneReading;
use App\Services\FortuneLocaleService;

/**
 * 🔔 Trait: Pay-First *Warning* Gate (2026-05-03 v2 — refactored from BLOCK to WARN)
 *
 * นโยบายใหม่ (per user 2026-05-03):
 *   "เดิมไม่จ่ายสร้างบิลไม่ได้ ให้แก้เป็นสร้างบิลได้ แต่เตือนว่ามีบิลค้างให้ชำระด้วย"
 *
 *   - ลูกค้ามีบิล deep/celtic ที่ยังไม่จ่าย → **อนุญาตให้ทำ service อื่นได้** (สร้างบิลใหม่/ดูฟรี/etc.)
 *   - แต่ bot **เตือนใน reply** ว่ามีบิลค้าง พร้อมรายละเอียด:
 *     "⚠️ เจ้าชะตามีบิล {bill_ref} ค้างอยู่ — ค่าครู {amount} บาท
 *      ถ้าจะจ่าย ใช้ QR เก่า (ทศนิยมต้องตรงเป๊ะ) ไม่งั้นบิลจะค้างถาวร ต้องทักแอดมิน"
 *
 * ⚠️ เปลี่ยนจาก v1 (BLOCK):
 *   - v1: ทุก message → return blocking response, ไม่ให้ทำอะไรอื่น
 *   - v2: return warning string only, processMessage prepend ลง final reply
 *
 * Re-engagement (FB 24hr / LINE 1hr) ยังคงทำงาน — track last_reengagement_at เหมือนเดิม
 *
 * Auto-revive removed: ลูกค้าสร้างบิลใหม่ได้เอง ไม่ต้อง revive
 */
trait PayFirstGateTrait
{
    /**
     * 🔔 Gate check — เรียกจาก processMessage หลัง dedup/lock
     *
     * @return string|null warning text (ใส่ก่อน final response) / null ถ้าไม่มีบิลค้าง
     */
    protected function payFirstGate(string $platform, string $platformUserId, string $messageText, ?array $userProfile = null): ?string
    {
        // 🛑 (2026-05-06) Pay-Later total removal — gate disabled
        //   user spec: "ไม่ต้องเช็คว่ามีบิลค้าง อะไรที่เกี่ยวกับระบบนี้ นำออกให้หมด"
        //   findBlockingUnpaidBill() return null เสมอ → ไม่มี warning ไหนถูก trigger
        //   เก็บ method ไว้กัน trait users พัง (FortuneConversationService ยังเรียก)
        return null;
    }

    /**
     * 💸 ตรวจว่าข้อความเป็นการแจ้งโอน
     */
    protected function isPaymentConfirmationMessage(string $text): bool
    {
        $clean = mb_strtolower(trim($text));
        $keywords = [
            'โอนแล้ว', 'จ่ายแล้ว', 'จ่ายเงินแล้ว', 'โอนเงินแล้ว', 'ชำระแล้ว', 'paid',
            'เช็คสถานะ', 'เช็คบิล', 'check status', 'check_payment_status',
            'report_payment', 'แจ้งโอน', 'แจ้งชำระเงิน',
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
     * 🔔 สร้างข้อความเตือนเรื่องบิลค้าง — ใส่ก่อน final response
     *
     * ย้ำสำคัญ: เศษทศนิยมต้องตรง — ถ้าผิดยอด บิลค้างถาวร ต้องทักแอดมิน
     */
    protected function buildPaymentWarning(FortuneReading $bill): string
    {
        $name = $bill->facebook_user_name ?? 'คุณ';
        $billRef = $bill->bill_reference ?? '-';
        $isCeltic = $bill->reading_type === FortuneReading::READING_TYPE_CELTIC_CROSS;
        // 🌙 (2026-05-23) ใช้ราคาจริงจากบิล — ไม่ฮาร์ดโค้ด 39/99
        //   ลูกค้าค้างบิลรู้ราคาตัวเองอยู่แล้ว + setting อาจเปลี่ยน — pull จาก amount_paid
        //   ถ้า amount_paid null/0 → ไม่แสดงราคาเลย (กัน "Cross -฿" / "ดูดวงเชิงลึก 0฿")
        $billAmountSuffix = $bill->amount_paid
            ? ' '.number_format((float) $bill->amount_paid, 0).'฿'
            : '';
        $packageLabel = $isCeltic
            ? FortuneLocaleService::lo("🔮 Celtic Cross{$billAmountSuffix}", "🔮 Celtic Cross{$billAmountSuffix}")
            : FortuneLocaleService::lo("🔹 ดูดวงเชิงลึก{$billAmountSuffix}", "🔹 ເບິ່ງດວງເຊີງເລິກ{$billAmountSuffix}");

        $isExpired = $bill->conversation_status === FortuneReading::STATUS_COMPLETED;
        $payAmount = $bill->amount_paid
            ? number_format((float) $bill->amount_paid, 2)
            : '0.00';

        // 🌙 Re-engagement greeting (FB: >24hr / LINE: >1hr)
        $reengagement = '';
        if ($bill->shouldSendReengagement()) {
            $reengagement = FortuneLocaleService::lo(
                "🌙 *เจ้าชะตา{$name} กลับมาแล้วนะ*\nแม่หมอเตรียมดวงไว้ตั้งแต่ครั้งก่อน — ดาวยังรอเจ้าชะตาอยู่ ✨\n\n",
                "🌙 *ເຈົ້າຊາຕາ{$name} ກັບມາແລ້ວເດີ*\nແມ່ໝໍຕຽມດວງໄວ້ຕັ້ງແຕ່ຄັ້ງກ່ອນ — ດາວຍັງລໍເຈົ້າຊາຕາຢູ່ ✨\n\n"
            );
            $bill->setConversationState('last_reengagement_at', now()->toIso8601String());
            $bill->setConversationState('reengagement_count', (int) $bill->getConversationState('reengagement_count', 0) + 1);
        }

        // ⚠️ Warning body — เน้นเศษทศนิยม + ทำไมต้องตรงเป๊ะ
        if ($isExpired) {
            // บิล expired แล้ว — แจ้งให้ลูกค้ารู้ว่าบิลเก่าหมดอายุ ถ้าจะจ่ายต้องสร้างบิลใหม่
            $warning = FortuneLocaleService::lo(
                "⚠️ *เจ้าชะตามีบิลเก่าหมดอายุยังไม่จ่าย*\n"
                    ."📋 บิล: {$billRef} ({$packageLabel}) — ค่าครู {$payAmount} บาท (หมดอายุแล้ว)\n"
                    ."💡 ถ้าอยากจ่ายบิลเดิม ทักแอดมินช่วยเปิดให้ — ไม่งั้นสร้างบิลใหม่ได้ตามปกติ\n\n",
                "⚠️ *ເຈົ້າຊາຕາມີບິນເກົ່າໝົດອາຍຸຍັງບໍ່ຈ່າຍ*\n"
                    ."📋 ບິນ: {$billRef} ({$packageLabel}) — ຄ່າຄູ {$payAmount} ບາດ (ໝົດອາຍຸແລ້ວ)\n"
                    ."💡 ຖ້າຢາກຈ່າຍບິນເດີມ ທັກແອັດມິນຊ່ວຍເປີດໃຫ້ — ບໍ່ດັ່ງນັ້ນສ້າງບິນໃໝ່ໄດ້ຕາມປົກກະຕິ\n\n"
            );
        } else {
            // บิล active — ย้ำว่าทศนิยมต้องตรง
            $expiresAt = $bill->updated_at->copy()->addMinutes(FortuneReading::PAYMENT_TIMEOUT_MINUTES);
            $remainingMinutes = (int) max(1, ceil(abs(now()->diffInMinutes($expiresAt, true))));

            $warning = FortuneLocaleService::lo(
                "⚠️ *เจ้าชะตายังมีบิลค้างยังไม่จ่ายอยู่นะ*\n"
                    ."📋 บิล: {$billRef} ({$packageLabel})\n"
                    ."💸 *ค่าครู: {$payAmount} บาท* (เหลืออีก {$remainingMinutes} นาที)\n"
                    ."🔔 *เศษทศนิยมต้องตรงเป๊ะ!* — โอนผิดยอด = บิลค้างถาวร ต้องทักแอดมินช่วย\n\n",
                "⚠️ *ເຈົ້າຊາຕາຍັງມີບິນຄ້າງຍັງບໍ່ຈ່າຍຢູ່ເດີ*\n"
                    ."📋 ບິນ: {$billRef} ({$packageLabel})\n"
                    ."💸 *ຄ່າຄູ: {$payAmount} ບາດ* (ເຫຼືອອີກ {$remainingMinutes} ນາທີ)\n"
                    ."🔔 *ເສດທົດສະນິຍົມຕ້ອງຕົງເປັະ!* — ໂອນຜິດຍອດ = ບິນຄ້າງຖາວອນ ຕ້ອງທັກແອັດມິນຊ່ວຍ\n\n"
            );
        }

        return $reengagement.$warning;
    }
}
