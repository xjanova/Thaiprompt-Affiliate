<?php

namespace App\Console\Commands;

use App\Services\FreshMarketService;
use Illuminate\Console\Command;

/**
 * กวาดออเดอร์ตลาดสดตาม SLA
 *
 * 1) pending ที่ร้านไม่กดรับเกิน fresh_market.pending_expiry_minutes (30 นาที)
 *    → ยกเลิกโดยระบบ + คืนสต็อก + คืนเงิน (เฉพาะที่เก็บมาแล้ว)
 * 2) accepted/preparing ที่ร้านไม่กด "พร้อม" เกิน fresh_market.prep_auto_cancel_minutes (90 นาที — 2026-10-13)
 *    → ยกเลิกโดยระบบ + คืนเงินเต็มจำนวน + แจ้งผู้ซื้อ/ร้าน/แอดมิน
 * 3) delivered ที่ผู้ซื้อไม่กดยืนยันเกิน fresh_market.auto_complete_hours (24 ชม.)
 *    → ปิดออเดอร์ + ปล่อยเงินให้ร้าน (ครั้งเดียว)
 *
 * ปลอดภัยต่อการรันซ้ำ/รันพร้อมกัน: ทุกออเดอร์ผ่าน lock + ตรวจสถานะใน FreshMarketService
 *
 * @example php artisan fresh-market:sweep-orders --limit=100
 */
class FreshMarketSweepOrders extends Command
{
    protected $signature = 'fresh-market:sweep-orders
                            {--limit=100 : จำนวนออเดอร์สูงสุดต่อประเภทต่อรอบ}
                            {--only= : ทำเฉพาะ expire, prep หรือ complete}';

    protected $description = 'ตลาดสด: ยกเลิกออเดอร์ที่ร้านไม่รับเกินเวลา และปิดออเดอร์ที่ส่งถึงแล้วแต่ไม่ยืนยันเกินเวลา';

    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $only = (string) $this->option('only');
        $service = new FreshMarketService;

        if ($only === '' || $only === 'expire') {
            $expired = $service->expirePendingOrders($limit);
            $this->info("ยกเลิกออเดอร์ที่หมดเวลารอร้าน: {$expired} รายการ");
        }

        if ($only === '' || $only === 'prep') {
            $overdue = $service->expireOverduePreparation($limit);
            $this->info("ยกเลิกออเดอร์ที่ร้านเตรียมไม่ทันเวลา: {$overdue} รายการ");
        }

        if ($only === '' || $only === 'complete') {
            $completed = $service->autoCompleteDeliveredOrders($limit);
            $this->info("ปิดออเดอร์อัตโนมัติ: {$completed} รายการ");
        }

        return self::SUCCESS;
    }
}
