<?php

namespace App\Events\Marketplace;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * ไรเดอร์ทิ้งงานที่รับแล้ว (คืนงานเอง หรือระบบคืนงานให้เพราะ GPS หายก่อนรับของ) — งานกลับเข้าคิว
 *
 * ยิงหลังการคืนงาน commit แล้วเท่านั้น · payload เป็น id ล้วน
 *
 * @example RiderAbandonedJob::dispatch($job->id, $rider->id, RiderAbandonedJob::REASON_GPS_LOST);
 */
class RiderAbandonedJob
{
    use Dispatchable;

    /** ไรเดอร์กดคืนงานเอง */
    public const REASON_RELEASED = 'released';

    /** GPS หายก่อนรับของเกินเวลา ระบบคืนงานให้ */
    public const REASON_GPS_LOST = 'gps_lost';

    /**
     * @param  int  $riderJobId  rider_jobs.id
     * @param  int  $riderId  riders.id
     * @param  string  $reason  released|gps_lost
     */
    public function __construct(
        public readonly int $riderJobId,
        public readonly int $riderId,
        public readonly string $reason,
    ) {}
}
