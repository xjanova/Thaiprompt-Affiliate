<?php

namespace App\Services\Fortune;

use App\Models\FortuneReading;
use Illuminate\Support\Facades\Log;

/**
 * 🌐 (2026-11-03) FB web-only — hard guard ที่ "จุดสร้างบิล" (design หัวข้อ 1.3 / งาน T5)
 *
 * ด่านขาเข้า (WebOnlyGateTrait) ดักข้อความไว้แล้วก็จริง แต่บิลยังเกิดได้จากทางอื่นที่ไม่ผ่านด่าน
 * (cron, SmsPaymentService, VerifySlipFallbackJob, Stripe webhook, ปุ่มเก่าในประวัติแชท ฯลฯ)
 * จึงต้องมีด่านสุดท้ายที่จุดสร้างบิลทุกจุด — ที่นี่คือ "คำถามเดียว" ที่จุดเหล่านั้นถาม
 *
 * กติกา:
 *   - จริงเฉพาะเมื่อช่องทางที่ resolve ได้คือ facebook **และ** ธง facebook_web_only เปิด
 *     (ผ่าน FortuneBotMode::fbWebOnlyFor — platform ของบิลชนะ hint, id ตัวเลขล้วน = FB เสมอ)
 *   - ห้ามเชื่อ currentPlatform ของ FCS ตัวเดียว (ค่าเริ่มต้น 'line') — ส่งเป็น hint เท่านั้น
 *   - fail-closed: ตรวจพัง + บริบทเป็น FB → บล็อก / LINE, Telegram ไม่โดนเด็ดขาด
 *
 * จุดที่เรียก: FCS createPaymentBill / askPaymentMethod / startStripePaymentFlow /
 *   startStripeForeignFallback / returnFromStripeForeignFallback / createPartialTopupBill /
 *   createProvisionalCelticReading / applyAdminSwitchCharge · CelticCross doStartCelticCrossFlow ·
 *   FortuneReading reviveBillForRepay / reissueThaiQrAmount / cancelExpiredPendingBills ·
 *   FortuneStripeService createCheckoutSession / restoreAfterCheckoutAbandoned
 */
final class FbBillingGuard
{
    /** เหตุผลปิดบิลที่ย้ายไปเว็บ — ค่าเดียวกับ WebOnlyGateTrait::WEB_ONLY_CANCEL_REASON */
    public const CANCEL_REASON = 'moved_to_web';

    /**
     * ห้ามออกบิล / QR / Stripe / top-up ให้ลูกค้าคนนี้บน FB ไหม (ไม่ log — ใช้ใน cron ที่วนหลายแถว)
     *
     * @param  FortuneReading|null  $reading  บิลที่จะออก/แก้ (platform ของบิลชนะ hint)
     * @param  string|null  $platformUserId  id ลูกค้า (ว่าง = เอาจากบิล)
     * @param  string|null  $platformHint  ช่องทางที่ผู้เรียกคิดว่าเป็น (เช่น currentPlatform ของ FCS)
     */
    public static function appliesTo(?FortuneReading $reading = null, ?string $platformUserId = null, ?string $platformHint = null): bool
    {
        $uid = self::resolveUserId($reading, $platformUserId);

        try {
            return (new FortuneBotMode)->fbWebOnlyFor($platformHint, $uid !== '' ? $uid : null, $reading);
        } catch (\Throwable $e) {
            // อ่าน setting ไม่ได้ (เช่น DB หลุด) — fail-closed เฉพาะบริบท FB
            return FortuneBotMode::resolvePlatform($platformHint, $uid, $reading) === FortuneBotMode::FB_WEB_ONLY_PLATFORM;
        }
    }

    /**
     * เหมือน appliesTo() แต่ log critical ทุกครั้งที่บล็อก (ใช้ที่จุดสร้างบิล — ทุกครั้งที่โดน = มีเส้นที่หลุดด่านขาเข้า)
     *
     * @param  string  $point  ชื่อจุดสร้างบิล (ใช้ใน log)
     *
     * @example
     * if (FbBillingGuard::blocks($reading, null, $this->currentPlatform, 'createPaymentBill')) {
     *     return $this->webOnlyBillBlockedResponse($reading, 'createPaymentBill');
     * }
     */
    public static function blocks(?FortuneReading $reading = null, ?string $platformUserId = null, ?string $platformHint = null, string $point = 'unknown'): bool
    {
        if (! self::appliesTo($reading, $platformUserId, $platformHint)) {
            return false;
        }

        try {
            Log::critical('🌐 FbBillingGuard: บล็อกการออกบิลบน Facebook (web-only) — มีเส้นที่หลุดด่านขาเข้ามาถึงจุดสร้างบิล', [
                'point' => $point,
                'reading_id' => $reading?->id,
                'platform_user_id' => self::resolveUserId($reading, $platformUserId),
                'status' => $reading?->conversation_status,
            ]);
        } catch (\Throwable $e) {
            // log พังต้องไม่ทำให้ด่านเปิด
        }

        return true;
    }

    /**
     * ปิดบิลที่ยังไม่จ่ายเป็น moved_to_web (ไม่แตะบิลที่จ่ายแล้ว / มีเงินเข้ามาบางส่วน)
     *
     * @return bool ปิดจริงไหม
     */
    public static function closeAsMovedToWeb(FortuneReading $reading): bool
    {
        try {
            if ($reading->is_paid || (float) ($reading->partial_paid_total ?? 0) > 0) {
                return false;
            }

            // คืนยอดโอนที่จองไว้ — กัน SMS จับคู่บิลที่เราไม่ส่งของแล้ว (เงินที่เข้ามาภายหลังไปคิวเงินกำพร้า)
            $upa = $reading->uniquePaymentAmount;
            if ($upa && $upa->status === 'reserved') {
                $upa->cancel();
            }

            $reading->update(['conversation_status' => FortuneReading::STATUS_COMPLETED]);
            $reading->setConversationState('cancellation_reason', self::CANCEL_REASON);
            $reading->setConversationState('cancelled_at', now()->toIso8601String());

            return true;
        } catch (\Throwable $e) {
            Log::warning('🌐 FbBillingGuard: ปิดบิลเป็น moved_to_web ไม่สำเร็จ', [
                'reading_id' => $reading->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * id ลูกค้าจาก parameter หรือจากบิล ('' = ไม่รู้)
     */
    private static function resolveUserId(?FortuneReading $reading, ?string $platformUserId): string
    {
        $uid = trim((string) $platformUserId);
        if ($uid !== '' || $reading === null) {
            return $uid;
        }

        return trim((string) ($reading->platform_user_id ?: ($reading->facebook_user_id ?: '')));
    }
}
