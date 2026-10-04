<?php

namespace Tests\Feature\RiderR2\Fixtures;

use Tests\Feature\Rider\Fixtures\FakeDeliverableSource;

/**
 * ออเดอร์ปลอมที่บอกได้ว่าผู้ซื้อล็อกเรียกไรเดอร์คนไหน (hook riderPreferredRiderId ของ RiderDispatchService)
 *
 * config เพิ่ม: preferred_rider_id
 */
class LockableFakeSource extends FakeDeliverableSource
{
    public function riderPreferredRiderId(): ?int
    {
        $value = self::$config[(int) $this->getKey()]['preferred_rider_id'] ?? null;

        return $value !== null ? (int) $value : null;
    }
}
