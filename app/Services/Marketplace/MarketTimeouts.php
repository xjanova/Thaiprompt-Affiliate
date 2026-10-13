<?php

namespace App\Services\Marketplace;

use App\Models\FreshMarketOrder;
use App\Models\RiderJob;
use App\Models\Setting;
use App\Services\DeliveryFeeCalculator;
use Illuminate\Support\Carbon;

/**
 * ⏱️ กติกาตัดเวลาของตลาดสด/ไรเดอร์ (เจ้าของระบบตัดสินแล้ว 2026-10-13 — ห้ามถามซ้ำ)
 *
 * 1. ร้านตลาดสดรับออเดอร์แล้วไม่กด "พร้อม" (accepted/preparing นับจาก accepted_at)
 *    - ครบ fresh_market.prep_buyer_cancel_minutes (45) → ผู้ซื้อยกเลิก + คืนเงินเต็มจำนวนได้ทุกช่องทาง
 *    - ครบ fresh_market.prep_auto_cancel_minutes (90)  → ระบบยกเลิก + คืนเงินเต็ม + แจ้งแอดมิน (fresh-market:sweep-orders)
 * 2. หาไรเดอร์ไม่ได้ (นับจากรอบหาไรเดอร์ปัจจุบัน RiderJob::searchStartedAt — เริ่มใหม่ทุกครั้งที่งานกลับเข้าคิว)
 *    - ครบ rider.no_rider_buyer_cancel_minutes (30) → ผู้ซื้อยกเลิก + คืนเงินเต็มได้
 *    - ครบ rider.no_rider_auto_cancel_minutes (60)  → ระบบยกเลิกออเดอร์ต้นทาง + คืนเงิน (ร้านไม่ได้เงิน) + แจ้งผู้ซื้อ/ร้าน/แอดมิน
 * 3. ไรเดอร์รับงานแล้ว GPS หาย rider.gps_lost_release_minutes (10)
 *    - ก่อนรับของ → คืนงานเข้าคิว (นับเป็นการยกเลิกงานของไรเดอร์) · หลังรับของ → แจ้งแอดมิน + ผู้ซื้อครั้งเดียวต่อเหตุการณ์
 *
 * ค่าทุกตัวแก้ได้ในหลังบ้าน (ตลาดสด → ระบบตลาด / ไรเดอร์ → ตั้งค่า) · อ่านเมื่อจำเป็นเท่านั้น (ไม่แตะฐานข้อมูลถ้าไม่มีเวลาให้เทียบ)
 *
 * @example
 * $timeouts = app(MarketTimeouts::class);
 * if ($timeouts->noRiderBuyerCancelDue($job)) { ... }
 */
class MarketTimeouts
{
    public const PREP_BUYER_CANCEL_DEFAULT = 45;

    public const PREP_AUTO_CANCEL_DEFAULT = 90;

    /** ข้อความที่แอปแสดงใต้ปุ่มยกเลิก (buyer_cancel_note) */
    public const NOTE_PREP = 'ร้านเตรียมอาหารเกิน %d นาที ยกเลิกและรับเงินคืนเต็มจำนวนได้';

    public const NOTE_NO_RIDER = 'ยังหาไรเดอร์ไม่ได้เกิน %d นาที ยกเลิกและรับเงินคืนเต็มจำนวนได้';

    /** ออเดอร์ที่ยังไม่ได้จ่ายเข้าระบบ (เก็บเงินปลายทาง) — ไม่มีเงินให้คืน */
    public const NOTE_PREP_UNPAID = 'ร้านเตรียมอาหารเกิน %d นาที ยกเลิกออเดอร์ได้';

    public const NOTE_NO_RIDER_UNPAID = 'ยังหาไรเดอร์ไม่ได้เกิน %d นาที ยกเลิกออเดอร์ได้';

    /** เหตุผลการยกเลิกที่บันทึกลงออเดอร์ */
    public const REASON_NO_RIDER = 'ไม่มีไรเดอร์รับงาน';

    /** ประเภทเหตุผลที่ผู้ซื้อยกเลิกได้นอกเหนือกติกาปกติ */
    public const KIND_PREP = 'prep';

    public const KIND_NO_RIDER = 'no_rider';

    /** @var array<string, int> ค่าที่อ่านแล้วใน instance นี้ */
    private array $memo = [];

    private ?DeliveryFeeCalculator $riderConfig = null;

    // =====================================================
    // ค่าตั้ง
    // =====================================================

    public function prepBuyerCancelMinutes(): int
    {
        return $this->memo['prep_buyer'] ??= max(5, (int) Setting::get('fresh_market.prep_buyer_cancel_minutes', self::PREP_BUYER_CANCEL_DEFAULT));
    }

    /**
     * ระบบยกเลิกเอง — ไม่ต่ำกว่าเวลาที่ผู้ซื้อยกเลิกได้ (ผู้ซื้อต้องมีโอกาสตัดสินใจก่อน)
     */
    public function prepAutoCancelMinutes(): int
    {
        return $this->memo['prep_auto'] ??= max(
            $this->prepBuyerCancelMinutes() + 1,
            (int) Setting::get('fresh_market.prep_auto_cancel_minutes', self::PREP_AUTO_CANCEL_DEFAULT)
        );
    }

    public function noRiderBuyerCancelMinutes(): int
    {
        return $this->memo['no_rider_buyer'] ??= max(5, $this->riderConfig()->intSetting('rider.no_rider_buyer_cancel_minutes'));
    }

    public function noRiderAutoCancelMinutes(): int
    {
        return $this->memo['no_rider_auto'] ??= max(
            $this->noRiderBuyerCancelMinutes() + 1,
            $this->riderConfig()->intSetting('rider.no_rider_auto_cancel_minutes')
        );
    }

    public function gpsLostReleaseMinutes(): int
    {
        return $this->memo['gps_lost'] ??= max(3, $this->riderConfig()->intSetting('rider.gps_lost_release_minutes'));
    }

    // =====================================================
    // ร้านเตรียมช้า (ตลาดสด)
    // =====================================================

    /**
     * เวลาเริ่มนับการเตรียมของ (accepted/preparing เท่านั้น) — null = ไม่อยู่ในช่วงเตรียม
     */
    public static function prepStartedAt(FreshMarketOrder $order): ?Carbon
    {
        if (! in_array($order->order_status, [FreshMarketOrder::STATUS_ACCEPTED, FreshMarketOrder::STATUS_PREPARING], true)) {
            return null;
        }

        $start = $order->accepted_at ?? ($order->exists ? $order->created_at : null);

        return $start ? Carbon::instance($start) : null;
    }

    public function prepBuyerCancelDue(FreshMarketOrder $order): bool
    {
        return $this->elapsedAtLeast(self::prepStartedAt($order), fn () => $this->prepBuyerCancelMinutes());
    }

    public function prepAutoCancelDue(FreshMarketOrder $order): bool
    {
        return $this->elapsedAtLeast(self::prepStartedAt($order), fn () => $this->prepAutoCancelMinutes());
    }

    // =====================================================
    // หาไรเดอร์ไม่ได้
    // =====================================================

    public function noRiderBuyerCancelDue(?RiderJob $job): bool
    {
        return $job !== null && $job->isOpen()
            && $this->elapsedAtLeast($job->searchStartedAt(), fn () => $this->noRiderBuyerCancelMinutes());
    }

    public function noRiderAutoCancelDue(?RiderJob $job): bool
    {
        return $job !== null && $job->isOpen()
            && $this->elapsedAtLeast($job->searchStartedAt(), fn () => $this->noRiderAutoCancelMinutes());
    }

    // =====================================================
    // ข้อความ
    // =====================================================

    /**
     * ข้อความใต้ปุ่มยกเลิกของผู้ซื้อ ตามเหตุผลที่ทำให้ยกเลิกได้
     *
     * @param  bool  $paid  ผู้ซื้อจ่ายเงินเข้าระบบแล้ว (false = เก็บเงินปลายทาง → ไม่พูดถึงการคืนเงิน)
     */
    public function buyerCancelNote(?string $kind, bool $paid = true): ?string
    {
        return match ($kind) {
            self::KIND_PREP => sprintf($paid ? self::NOTE_PREP : self::NOTE_PREP_UNPAID, $this->prepBuyerCancelMinutes()),
            self::KIND_NO_RIDER => sprintf($paid ? self::NOTE_NO_RIDER : self::NOTE_NO_RIDER_UNPAID, $this->noRiderBuyerCancelMinutes()),
            default => null,
        };
    }

    /**
     * เหตุผลยกเลิกที่บันทึกลงออเดอร์ เมื่อผู้ซื้อยกเลิกเพราะเกินเวลาแต่ไม่ได้พิมพ์เหตุผลเอง
     */
    public function buyerCancelReason(string $kind): string
    {
        return match ($kind) {
            self::KIND_PREP => 'ผู้ซื้อยกเลิก: ร้านเตรียมสินค้าเกิน '.$this->prepBuyerCancelMinutes().' นาที',
            default => 'ผู้ซื้อยกเลิก: '.self::REASON_NO_RIDER.'เกิน '.$this->noRiderBuyerCancelMinutes().' นาที',
        };
    }

    /**
     * จำนวนนาทีที่ผ่านไปแล้ว (ปัดลง) — ใช้แนบใน event/log
     */
    public static function minutesSince(?\DateTimeInterface $start): int
    {
        return $start ? max(0, (int) floor((now()->getTimestamp() - $start->getTimestamp()) / 60)) : 0;
    }

    // =====================================================
    // ภายใน
    // =====================================================

    /**
     * ผ่านไปแล้วอย่างน้อย N นาทีหรือยัง (อ่านค่าตั้งเมื่อมีเวลาให้เทียบเท่านั้น)
     *
     * @param  callable(): int  $minutes
     */
    private function elapsedAtLeast(?\DateTimeInterface $start, callable $minutes): bool
    {
        if ($start === null) {
            return false;
        }

        return $start->getTimestamp() <= now()->subMinutes($minutes())->getTimestamp();
    }

    private function riderConfig(): DeliveryFeeCalculator
    {
        return $this->riderConfig ??= app(DeliveryFeeCalculator::class);
    }
}
