<?php

namespace App\Console\Commands;

use App\Services\RiderGpsTrackingService;
use Illuminate\Console\Command;

/**
 * ตรวจ GPS หายระหว่างส่งงาน ฝั่ง server (ทุกนาที)
 *
 * แอปส่งตำแหน่งมาที่ POST /api/v1/rider/location อย่างเดียว (ไม่เรียก /fresh-market/rider/gps/* แล้ว)
 * → คำสั่งนี้ดู riders.last_location_update ของไรเดอร์ที่มีงานค้าง
 *   เงียบเกิน fresh_market_settings.gps_lost_timeout_seconds → หยุดงานชั่วคราว (gps_active = false)
 *   + แจ้งไรเดอร์ (in-app/Expo push) + แจ้งแอดมินเมื่อเกิน gps_warning_max ครั้ง
 * ตำแหน่งใหม่เข้ามาเมื่อไหร่ งานจะกลับมาทำงานต่อเอง (RiderGpsTrackingService::recordRiderLocation)
 *
 * Usage: php artisan rider:gps-watch
 */
class RiderGpsWatchCommand extends Command
{
    protected $signature = 'rider:gps-watch';

    protected $description = 'ตรวจงานไรเดอร์ที่ GPS เงียบเกินกำหนด แล้วหยุดติดตามชั่วคราวพร้อมแจ้งเตือน';

    public function handle(): int
    {
        $paused = (new RiderGpsTrackingService)->detectGpsLoss();

        $this->info("gps_lost={$paused}");

        return self::SUCCESS;
    }
}
