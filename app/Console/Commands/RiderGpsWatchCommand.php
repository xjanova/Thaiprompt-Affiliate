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
 * (2026-10-13) เงียบเกิน rider.gps_lost_release_minutes (10): ก่อนรับของ → คืนงานเข้าคิว (นับยกเลิกงาน)
 *   หลังรับของ → แจ้งแอดมิน + ผู้ซื้อครั้งเดียวต่อเหตุการณ์ (RiderGpsTrackingService::enforceGpsLossRules)
 *
 * Usage: php artisan rider:gps-watch
 */
class RiderGpsWatchCommand extends Command
{
    protected $signature = 'rider:gps-watch';

    protected $description = 'ตรวจงานไรเดอร์ที่ GPS เงียบเกินกำหนด แล้วหยุดติดตามชั่วคราวพร้อมแจ้งเตือน';

    public function handle(): int
    {
        $service = new RiderGpsTrackingService;
        $paused = $service->detectGpsLoss();

        // กติกาตัดเวลา (2026-10-13): หายเกิน rider.gps_lost_release_minutes → ก่อนรับของคืนงาน · หลังรับของแจ้งแอดมิน+ผู้ซื้อ
        $rules = $service->enforceGpsLossRules();

        $this->info("gps_lost={$paused} released={$rules['released']} alerted={$rules['alerted']}");

        return self::SUCCESS;
    }
}
