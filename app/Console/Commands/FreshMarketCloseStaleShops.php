<?php

namespace App\Console\Commands;

use App\Services\FreshMarketShopPresenceService;
use Illuminate\Console\Command;

/**
 * ปิดร้านตลาดสดอัตโนมัติ (ทุก 5 นาที)
 *
 * 1) เลยเวลาปิดที่ร้านตั้งไว้ (closes_at) — ร้านเคลื่อนที่ที่ไม่ได้ตั้งเวลา ระบบตั้งให้ 12 ชม. หลังเปิด
 * 2) ร้านเคลื่อนที่ที่เปิดส่งตำแหน่งสด แต่ตำแหน่งเงียบเกิน 30 นาที (แอปปิด/แบตหมด/ลืมกดปิดร้าน)
 *
 * แจ้งเจ้าของร้านในแอป + Expo push (ไม่ใช้ LINE push) · รันซ้ำ/ชนกันได้ปลอดภัย (อัปเดตแบบมีเงื่อนไข)
 *
 * @example php artisan fresh-market:close-stale-shops --limit=200
 */
class FreshMarketCloseStaleShops extends Command
{
    protected $signature = 'fresh-market:close-stale-shops
                            {--limit=200 : จำนวนร้านสูงสุดต่อรอบ}';

    protected $description = 'ตลาดสด: ปิดร้านที่เลยเวลาปิด หรือร้านเคลื่อนที่ที่ตำแหน่งสดเงียบเกิน 30 นาที';

    public function handle(FreshMarketShopPresenceService $presence): int
    {
        $closed = $presence->closeStaleShops(max(1, (int) $this->option('limit')));

        $this->info("ปิดร้านอัตโนมัติ: {$closed} ร้าน");

        return self::SUCCESS;
    }
}
