<?php

namespace App\Console\Commands;

use App\Models\Rider;
use App\Models\RiderJob;
use App\Services\DeliveryFeeCalculator;
use App\Services\RiderNotificationService;
use Illuminate\Console\Command;

/**
 * ปิดรับงานอัตโนมัติให้ "ไรเดอร์ผี" (ทุก 5 นาที)
 *
 * ไรเดอร์ที่ online แต่ไม่ส่งตำแหน่งเกิน rider.location_fresh_minutes (ค่าเริ่มต้น 15 นาที)
 * เช่น ปิดแอป/แบตหมด → ตั้งเป็น offline + แจ้งในแอป เพื่อไม่ให้ถูกนับเป็นคนพร้อมรับงาน
 * ยกเว้นไรเดอร์ที่มีงานค้างอยู่ (ตรวจ GPS หายด้วย rider:gps-watch แทน)
 *
 * Usage: php artisan rider:auto-offline
 */
class RiderAutoOfflineCommand extends Command
{
    protected $signature = 'rider:auto-offline';

    protected $description = 'ตั้งไรเดอร์ที่ online แต่ไม่ส่งตำแหน่งเกินกำหนดให้เป็น offline';

    public function handle(DeliveryFeeCalculator $config, RiderNotificationService $notifier): int
    {
        $minutes = max(1, $config->intSetting('rider.location_fresh_minutes'));
        $cutoff = now()->subMinutes($minutes);
        $count = 0;

        Rider::query()
            ->where('availability', 'online')
            ->where(function ($q) use ($cutoff) {
                $q->whereNull('last_location_update')->orWhere('last_location_update', '<', $cutoff);
            })
            ->whereNotExists(function ($q) {
                $q->selectRaw('1')
                    ->from('rider_jobs')
                    ->whereColumn('rider_jobs.rider_id', 'riders.id')
                    ->whereIn('rider_jobs.status', RiderJob::ACTIVE_STATUSES)
                    ->whereNull('rider_jobs.deleted_at');
            })
            ->orderBy('id')
            ->chunkById(200, function ($riders) use ($notifier, &$count, $minutes) {
                foreach ($riders as $rider) {
                    // ตั้งแบบมีเงื่อนไข กันทับกรณีไรเดอร์เพิ่งส่งตำแหน่งเข้ามาพอดี
                    $updated = Rider::whereKey($rider->id)
                        ->where('availability', 'online')
                        ->where(function ($q) use ($minutes) {
                            $q->whereNull('last_location_update')
                                ->orWhere('last_location_update', '<', now()->subMinutes($minutes));
                        })
                        ->update(['availability' => 'offline']);

                    if ($updated === 0) {
                        continue;
                    }

                    $count++;

                    $notifier->notifyUser(
                        (int) $rider->user_id,
                        'rider_account',
                        'ระบบปิดรับงานให้อัตโนมัติ',
                        "ไม่ได้รับตำแหน่ง GPS เกิน {$minutes} นาที ระบบจึงปิดรับงานให้ก่อน เปิดแอปแล้วกด \"เริ่มรับงาน\" เพื่อกลับมารับงาน",
                        ['type' => 'rider_account', 'event' => 'auto_offline', 'screen' => 'rider'],
                    );
                }
            });

        $this->info("auto_offline={$count}");

        return self::SUCCESS;
    }
}
