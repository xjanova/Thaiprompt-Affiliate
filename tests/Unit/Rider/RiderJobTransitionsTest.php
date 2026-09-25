<?php

namespace Tests\Unit\Rider;

use App\Models\Rider;
use App\Models\RiderJob;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * ทดสอบ state machine ของงานไรเดอร์ (RiderJob::TRANSITIONS) + ปุ่มที่ไรเดอร์กดได้
 *
 * ที่มา (audit 2026-09-25 RIDER-04 / RIDER-APP-05): เดิม API ให้ไรเดอร์ยิง status อะไรก็ได้
 *   → กด completed ซ้ำได้จนรายได้ถูกนับซ้ำ / ข้ามจาก accepted ไป completed โดยไม่รับของ
 *   / ยกเลิกหลังรับของไปแล้ว ตอนนี้ทุกการเปลี่ยนสถานะต้องผ่านตารางนี้เท่านั้น
 *
 * ⚠️ ใช้ PHPUnit\TestCase ตรงๆ — ตรรกะล้วน ไม่แตะ DB (เครื่อง dev ไม่มี MySQL ก็รันได้)
 */
class RiderJobTransitionsTest extends TestCase
{
    /**
     * ตารางตาม PLAN (สัญญากลางระหว่าง workstream) — ห้ามเปลี่ยนโดยไม่อัปเดตทุกฝั่ง
     */
    public function test_transition_table_matches_contract(): void
    {
        $this->assertSame([
            'pending' => ['accepted', 'cancelled'],
            'accepted' => ['picking_up', 'picked_up', 'pending', 'cancelled'],
            'picking_up' => ['picked_up', 'pending', 'cancelled'],
            'picked_up' => ['delivering', 'delivered', 'failed'],
            'delivering' => ['delivered', 'failed'],
            'delivered' => ['completed'],
            'completed' => [],
            'cancelled' => [],
            'failed' => [],
        ], RiderJob::TRANSITIONS);
    }

    public function test_every_enum_status_has_an_entry(): void
    {
        foreach (['pending', 'accepted', 'picking_up', 'picked_up', 'delivering', 'delivered', 'completed', 'cancelled', 'failed'] as $status) {
            $this->assertArrayHasKey($status, RiderJob::TRANSITIONS, "สถานะ {$status} ต้องอยู่ในตาราง");
        }
    }

    public function test_terminal_statuses_cannot_move(): void
    {
        foreach (RiderJob::TERMINAL_STATUSES as $terminal) {
            foreach (array_keys(RiderJob::TRANSITIONS) as $to) {
                $this->assertFalse(RiderJob::canTransition($terminal, $to), "{$terminal} → {$to} ต้องทำไม่ได้");
            }
        }
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: bool}>
     */
    public static function transitionCases(): array
    {
        return [
            'รับงาน' => ['pending', 'accepted', true],
            'ยกเลิกก่อนมีคนรับ' => ['pending', 'cancelled', true],
            'ข้ามไป completed จาก pending ไม่ได้' => ['pending', 'completed', false],
            'ข้ามไป completed จาก accepted ไม่ได้' => ['accepted', 'completed', false],
            'ข้ามไป delivered โดยไม่รับของไม่ได้' => ['accepted', 'delivered', false],
            'คืนงานก่อนรับของ' => ['accepted', 'pending', true],
            'คืนงานระหว่างไปรับของ' => ['picking_up', 'pending', true],
            'คืนงานหลังรับของแล้วไม่ได้' => ['picked_up', 'pending', false],
            'ยกเลิกหลังรับของแล้วไม่ได้' => ['picked_up', 'cancelled', false],
            'ยกเลิกระหว่างส่งไม่ได้' => ['delivering', 'cancelled', false],
            'รับของข้ามขั้น picking_up ได้' => ['accepted', 'picked_up', true],
            'ส่งเลยโดยไม่กด delivering ได้' => ['picked_up', 'delivered', true],
            'ส่งไม่สำเร็จหลังรับของ' => ['picked_up', 'failed', true],
            'ส่งไม่สำเร็จระหว่างส่ง' => ['delivering', 'failed', true],
            'ส่งไม่สำเร็จก่อนรับของไม่ได้' => ['accepted', 'failed', false],
            'delivered → completed' => ['delivered', 'completed', true],
            'completed ซ้ำไม่ได้' => ['completed', 'completed', false],
            'failed กลับ pending ไม่ได้' => ['failed', 'pending', false],
            'cancelled กลับ pending ไม่ได้' => ['cancelled', 'pending', false],
            'สถานะแปลกปลอม' => ['unknown', 'accepted', false],
        ];
    }

    #[DataProvider('transitionCases')]
    public function test_can_transition(string $from, string $to, bool $expected): void
    {
        $this->assertSame($expected, RiderJob::canTransition($from, $to));
    }

    public function test_active_and_terminal_sets_do_not_overlap(): void
    {
        $this->assertSame([], array_intersect(RiderJob::ACTIVE_STATUSES, RiderJob::TERMINAL_STATUSES));
        $this->assertSame(['accepted', 'picking_up', 'picked_up', 'delivering'], RiderJob::ACTIVE_STATUSES);
    }

    public function test_open_job_offers_accept_to_any_rider_but_not_to_guests(): void
    {
        $job = $this->job(['status' => 'pending', 'rider_id' => null, 'dispatch_type' => 'broadcast']);

        $this->assertSame(['accept'], $job->allowedActionsFor($this->rider(7)));
        $this->assertSame([], $job->allowedActionsFor(null));
    }

    public function test_cascade_offer_is_exclusive_to_offered_rider(): void
    {
        $job = $this->job(['status' => 'pending', 'rider_id' => null, 'dispatch_type' => 'cascade', 'current_offer_rider_id' => 3]);

        $this->assertSame(['accept'], $job->allowedActionsFor($this->rider(3)));
        $this->assertSame([], $job->allowedActionsFor($this->rider(4)));
    }

    /**
     * @return array<string, array{0: string, 1: array<int, string>}>
     */
    public static function ownerActionCases(): array
    {
        return [
            'accepted' => ['accepted', ['picking_up', 'picked_up', 'release']],
            'picking_up' => ['picking_up', ['picked_up', 'release']],
            'picked_up' => ['picked_up', ['delivering', 'deliver', 'fail']],
            'delivering' => ['delivering', ['deliver', 'fail']],
            'completed' => ['completed', []],
            'failed' => ['failed', []],
            'cancelled' => ['cancelled', []],
        ];
    }

    /**
     * @param  array<int, string>  $expected
     */
    #[DataProvider('ownerActionCases')]
    public function test_owner_actions_follow_status(string $status, array $expected): void
    {
        $job = $this->job(['status' => $status, 'rider_id' => 9]);

        $this->assertSame($expected, $job->allowedActionsFor($this->rider(9)));
    }

    public function test_other_rider_gets_no_actions_on_assigned_job(): void
    {
        $job = $this->job(['status' => 'accepted', 'rider_id' => 9]);

        $this->assertSame([], $job->allowedActionsFor($this->rider(10)));
    }

    public function test_every_owner_action_maps_to_an_allowed_transition(): void
    {
        $actionTarget = [
            'picking_up' => 'picking_up',
            'picked_up' => 'picked_up',
            'delivering' => 'delivering',
            'deliver' => 'delivered',
            'fail' => 'failed',
            'release' => 'pending',
        ];

        foreach (RiderJob::ACTIVE_STATUSES as $status) {
            $job = $this->job(['status' => $status, 'rider_id' => 1]);
            foreach ($job->allowedActionsFor($this->rider(1)) as $action) {
                $this->assertTrue(
                    RiderJob::canTransition($status, $actionTarget[$action]),
                    "ปุ่ม {$action} ที่สถานะ {$status} ต้องเปลี่ยนสถานะได้จริง"
                );
            }
        }
    }

    public function test_rider_failure_reasons_are_a_subset_of_labels(): void
    {
        foreach (RiderJob::RIDER_FAILURE_REASONS as $code) {
            $this->assertArrayHasKey($code, RiderJob::FAILURE_REASONS);
        }

        $this->assertSame(
            ['customer_unreachable', 'wrong_address', 'customer_refused', 'item_damaged', 'other'],
            RiderJob::RIDER_FAILURE_REASONS
        );
    }

    /**
     * @return array<string, array{0: ?string, 1: ?string}>
     */
    public static function areaCases(): array
    {
        return [
            'กรุงเทพ มีเขต' => ['99/1 ซ.สุขุมวิท 21 แขวงคลองเตยเหนือ เขตวัฒนา กรุงเทพมหานคร 10110', 'เขตวัฒนา กรุงเทพฯ'],
            'ต่างจังหวัด' => ['12 หมู่ 3 ตำบลในเมือง อำเภอเมือง จังหวัดขอนแก่น 40000', 'อ.เมือง จ.ขอนแก่น'],
            'ตัวย่อ' => ['5/5 ถ.มิตรภาพ อ.เมือง จ.นครราชสีมา', 'อ.เมือง จ.นครราชสีมา'],
            'ไม่มีข้อมูลเขต' => ['บ้านเลขที่ 7 ซอยสุข', null],
            'ว่าง' => [null, null],
        ];
    }

    #[DataProvider('areaCases')]
    public function test_derive_area_never_leaks_house_number(?string $address, ?string $expected): void
    {
        $area = RiderJob::deriveArea($address);

        $this->assertSame($expected, $area);
        if ($area !== null) {
            $this->assertDoesNotMatchRegularExpression('/\d/', $area, 'พื้นที่หยาบต้องไม่มีบ้านเลขที่/รหัสไปรษณีย์');
        }
    }

    public function test_summary_masks_dropoff_before_accept(): void
    {
        $job = $this->job([
            'id' => 50,
            'status' => 'pending',
            'rider_id' => null,
            'job_number' => 'JOB260925000050',
            'job_type' => 'fresh_market',
            'title' => 'ส่งของตลาดสด #FM1',
            'pickup_address' => 'ร้านป้าแดง ตลาดบางรัก',
            'pickup_latitude' => 13.7291234,
            'pickup_longitude' => 100.5210987,
            'pickup_contact_phone' => '0811111111',
            'delivery_address' => '88/8 ซ.เจริญกรุง 30 แขวงบางรัก เขตบางรัก กรุงเทพมหานคร 10500',
            'delivery_latitude' => 13.7312345,
            'delivery_longitude' => 100.5156789,
            'delivery_contact_name' => 'คุณลูกค้า',
            'delivery_contact_phone' => '0822222222',
            'distance_km' => 1.2,
            'total_fee' => 30,
            'rider_earnings' => 24,
            'platform_fee' => 6,
            'cod_amount' => 0,
        ]);

        $data = $job->toApiSummary($this->rider(1));

        $this->assertNull($data['dropoff']['address']);
        $this->assertNull($data['dropoff']['name']);
        $this->assertNull($data['dropoff']['phone']);
        $this->assertNull($data['pickup']['phone']);
        $this->assertTrue($data['dropoff']['is_approximate']);
        $this->assertSame('เขตบางรัก กรุงเทพฯ', $data['dropoff']['area']);
        $this->assertSame(13.73, $data['dropoff']['latitude']);
        $this->assertSame(100.52, $data['dropoff']['longitude']);

        // ตัวเลขต้องเป็น number ไม่ใช่ string ทศนิยม
        $this->assertIsFloat($data['total_fee']);
        $this->assertIsFloat($data['rider_earnings']);
        $this->assertIsFloat($data['distance_km']);
        $this->assertSame(['accept'], $data['allowed_actions']);
        $this->assertFalse($data['is_mine']);
    }

    public function test_summary_reveals_dropoff_to_assigned_rider(): void
    {
        $job = $this->job([
            'id' => 51,
            'status' => 'accepted',
            'rider_id' => 1,
            'delivery_address' => '88/8 ซ.เจริญกรุง 30 เขตบางรัก กรุงเทพมหานคร',
            'delivery_latitude' => 13.7312345,
            'delivery_longitude' => 100.5156789,
            'delivery_contact_phone' => '0822222222',
            'pickup_contact_phone' => '0811111111',
        ]);

        $data = $job->toApiSummary($this->rider(1));

        $this->assertTrue($data['is_mine']);
        $this->assertSame('0822222222', $data['dropoff']['phone']);
        $this->assertSame('0811111111', $data['pickup']['phone']);
        $this->assertSame(13.7312345, $data['dropoff']['latitude']);
        $this->assertFalse($data['dropoff']['is_approximate']);

        // ไรเดอร์คนอื่นมองงานที่มีเจ้าของแล้ว → ปิดข้อมูลเหมือนเดิม
        $other = $job->toApiSummary($this->rider(2));
        $this->assertNull($other['dropoff']['phone']);
        $this->assertNull($other['dropoff']['address']);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function job(array $attributes): RiderJob
    {
        $job = new RiderJob;
        $job->forceFill($attributes);

        return $job;
    }

    private function rider(int $id): Rider
    {
        $rider = new Rider;
        $rider->forceFill(['id' => $id, 'user_id' => $id + 1000]);

        return $rider;
    }
}
