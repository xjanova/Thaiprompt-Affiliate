<?php

namespace App\Events\Marketplace;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * ร้านตลาดสดรับออเดอร์แล้วเตรียมไม่ทันเวลา จนออเดอร์ถูกยกเลิก (ระบบคะแนนชื่อเสียงร้านจะมาฟัง event นี้)
 *
 * ยิงหลังการยกเลิก commit แล้วเท่านั้น · payload เป็น id ล้วน (ไม่ผูกโมเดล — ฟังผ่านคิวได้)
 *
 * @example ShopMissedPrepDeadline::dispatch($order->id, $order->seller_id, 92, ShopMissedPrepDeadline::BY_AUTO);
 */
class ShopMissedPrepDeadline
{
    use Dispatchable;

    /** ระบบยกเลิกเองเมื่อครบเวลา */
    public const BY_AUTO = 'auto';

    /** ผู้ซื้อกดยกเลิกหลังเกินเวลาที่อนุญาต */
    public const BY_BUYER = 'buyer';

    /**
     * @param  int  $orderId  fresh_market_orders.id
     * @param  int  $sellerId  fresh_market_sellers.id
     * @param  int  $minutes  นาทีที่ผ่านไปนับจากร้านรับออเดอร์
     * @param  string  $by  auto|buyer
     */
    public function __construct(
        public readonly int $orderId,
        public readonly int $sellerId,
        public readonly int $minutes,
        public readonly string $by,
    ) {}
}
