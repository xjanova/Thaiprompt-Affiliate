<?php

namespace App\Events\Marketplace;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * ออเดอร์ถูกยกเลิกเพราะหาไรเดอร์ไม่ได้ (ระบบยกเลิกเอง หรือผู้ซื้อยกเลิกหลังรอเกินเวลา)
 *
 * ยิงหลังการยกเลิก commit แล้วเท่านั้น · payload เป็น id ล้วน
 *
 * @example NoRiderOrderCancelled::dispatch($job->id, FreshMarketOrder::class, $order->id, 61, NoRiderOrderCancelled::BY_AUTO);
 */
class NoRiderOrderCancelled
{
    use Dispatchable;

    public const BY_AUTO = 'auto';

    public const BY_BUYER = 'buyer';

    /**
     * @param  int  $riderJobId  งานไรเดอร์ที่หาคนรับไม่ได้
     * @param  string|null  $sourceType  คลาสออเดอร์ต้นทาง (App\Models\FreshMarketOrder | App\Models\Order)
     * @param  int|null  $sourceId  id ออเดอร์ต้นทาง
     * @param  int  $minutes  นาทีที่หาไรเดอร์ในรอบปัจจุบัน
     * @param  string  $by  auto|buyer
     */
    public function __construct(
        public readonly int $riderJobId,
        public readonly ?string $sourceType,
        public readonly ?int $sourceId,
        public readonly int $minutes,
        public readonly string $by,
    ) {}
}
