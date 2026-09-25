<?php

namespace App\Console\Commands;

use App\Models\RiderLocation;
use App\Services\DeliveryFeeCalculator;
use Illuminate\Console\Command;

/**
 * ลบประวัติตำแหน่งไรเดอร์ที่เก่ากว่านโยบายเก็บข้อมูล (ทุกวัน)
 *
 * ค่าเริ่มต้น rider.location_retention_days = 30 วัน (PDPA / Google Play Data safety)
 *
 * Usage: php artisan rider:purge-locations [--days=30]
 */
class RiderPurgeLocationsCommand extends Command
{
    protected $signature = 'rider:purge-locations {--days= : จำนวนวันที่เก็บไว้ (ไม่ใส่ = ใช้ค่าตั้งค่า)}';

    protected $description = 'ลบ rider_locations ที่เก่ากว่านโยบายเก็บข้อมูล (ค่าเริ่มต้น 30 วัน)';

    public function handle(DeliveryFeeCalculator $config): int
    {
        $days = $this->option('days') !== null
            ? (int) $this->option('days')
            : $config->intSetting('rider.location_retention_days');

        $days = max(1, $days);
        $deleted = RiderLocation::purgeOlderThan($days);

        $this->info("purged={$deleted} older_than_days={$days}");

        return self::SUCCESS;
    }
}
