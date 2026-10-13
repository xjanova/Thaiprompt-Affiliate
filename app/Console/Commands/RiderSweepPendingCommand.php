<?php

namespace App\Console\Commands;

use App\Services\RiderDispatchService;
use App\Services\RiderEarningService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * กวาดงานไรเดอร์ที่ยังไม่มีคนรับ + เคลียร์เงินงานที่ค้าง (ทุกนาที)
 *
 * 1. งาน pending ที่ไม่มีคนรับ → ขยายรัศมีแล้วแจ้งไรเดอร์เพิ่ม (สูงสุด rider.max_dispatch_rounds รอบ)
 * 2. รอเกิน rider.pending_timeout_minutes → dispatch_type = manual_needed + แจ้งแอดมิน/ผู้ซื้อ/ผู้ขาย
 *    แล้วยังเสนอไรเดอร์ที่เพิ่งออนไลน์ต่อ (ไม่แจ้งแอดมินซ้ำ) · ครบ rider.no_rider_auto_cancel_minutes → ยกเลิกออเดอร์ + คืนเงิน
 *    (นับจากรอบหาไรเดอร์ปัจจุบัน — เริ่มใหม่เมื่องานกลับเข้าคิว)
 * 3. cascade ที่ offer หมดเวลา (กรณีคิวไม่ได้รัน) → เสนอคนถัดไป
 * 4. งาน completed ที่ยังเคลียร์เงินไม่ครบ (วอลเลตล็อก/ยอด COD ไม่พอ) → ลองใหม่
 *
 * Usage: php artisan rider:sweep-pending [--limit=100]
 */
class RiderSweepPendingCommand extends Command
{
    protected $signature = 'rider:sweep-pending {--limit=100 : จำนวนงานสูงสุดต่อรอบ}';

    protected $description = 'กระจายงานไรเดอร์ที่ยังไม่มีคนรับซ้ำด้วยรัศมีกว้างขึ้น แจ้งแอดมินเมื่อหาไรเดอร์ไม่ได้ และเคลียร์เงินที่ค้าง';

    public function handle(RiderDispatchService $dispatch, RiderEarningService $earnings): int
    {
        $limit = max(1, (int) $this->option('limit'));

        $stats = $dispatch->sweepPending($limit);

        $settled = 0;
        foreach ($earnings->unsettledJobs(50) as $job) {
            try {
                $earnings->settle($job);
                $settled++;
            } catch (\Throwable $e) {
                Log::error('rider:sweep-pending settle failed', ['job_id' => $job->id, 'error' => $e->getMessage()]);
            }
        }

        $this->info(sprintf(
            'rebroadcast=%d escalated=%d cascade_moved=%d reoffered=%d auto_cancelled=%d settle_attempts=%d',
            $stats['rebroadcast'],
            $stats['escalated'],
            $stats['cascade_moved'],
            $stats['reoffered'] ?? 0,
            $stats['auto_cancelled'] ?? 0,
            $settled
        ));

        return self::SUCCESS;
    }
}
