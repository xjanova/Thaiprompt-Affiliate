<?php

namespace Tests\Unit\Rider;

use App\Exceptions\RiderJobException;
use App\Services\DeliveryFeeCalculator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * ทดสอบสูตรค่าส่งไรเดอร์ (แหล่งความจริงเดียว — หน้าชำระเงินและงานไรเดอร์ต้องได้เลขเดียวกัน)
 *
 * ที่มา (audit RIDER-APP-07 / RIDER-21 / G10): งานจริงเคยได้ rider_earnings = 0
 *   และแต่ละไฟล์อ่านค่าตั้งค่าคนละชื่อ → ค่าส่งลูกค้ากับค่าส่งในงานไม่ตรงกัน
 *
 * ⚠️ ใช้ค่าที่ส่งเข้า constructor (overrides) → ไม่แตะ DB
 */
class DeliveryFeeCalculatorTest extends TestCase
{
    private function calc(array $overrides = []): DeliveryFeeCalculator
    {
        return new DeliveryFeeCalculator($overrides);
    }

    /**
     * ค่าเริ่มต้น: base 30, 10 บาท/กม. หลัง 2 กม.แรก, ขั้นต่ำ 30, ไรเดอร์ได้ 80%
     *
     * @return array<string, array{0: float, 1: float, 2: float, 3: float}>
     */
    public static function distanceCases(): array
    {
        return [
            // [ระยะทาง, total_fee, distance_fee, rider_earnings]
            '0 กม.' => [0.0, 30.0, 0.0, 24.0],
            '2 กม. (ยังฟรี)' => [2.0, 30.0, 0.0, 24.0],
            '5 กม.' => [5.0, 60.0, 30.0, 48.0],
            '3.25 กม. ปัดขึ้นเป็นบาท' => [3.25, 43.0, 13.0, 34.4],
            '15 กม.' => [15.0, 160.0, 130.0, 128.0],
        ];
    }

    #[DataProvider('distanceCases')]
    public function test_quote_for_distance(float $distance, float $total, float $distanceFee, float $rider): void
    {
        $q = $this->calc()->quoteForDistance($distance);

        $this->assertSame($total, $q['total_fee']);
        $this->assertSame(30.0, $q['base_fee']);
        $this->assertSame($distanceFee, $q['distance_fee']);
        $this->assertSame($rider, $q['rider_earnings']);
        $this->assertSame(round($total - $rider, 2), $q['platform_fee']);
    }

    public function test_parts_always_add_up(): void
    {
        $calc = $this->calc(['rider.rider_share_percent' => 77.7, 'rider.per_km_fee' => 9.5]);

        foreach ([0, 0.4, 1.99, 2.01, 7.33, 12.5, 14.99] as $km) {
            $q = $calc->quoteForDistance($km);

            $this->assertEqualsWithDelta($q['total_fee'], $q['base_fee'] + $q['distance_fee'], 0.001, "base+distance ≠ total ที่ {$km} กม.");
            $this->assertEqualsWithDelta($q['total_fee'], $q['rider_earnings'] + $q['platform_fee'], 0.001, "rider+platform ≠ total ที่ {$km} กม.");
            $this->assertSame((float) ceil($q['total_fee']), $q['total_fee'], 'ค่าส่งต้องเป็นบาทเต็ม');
        }
    }

    public function test_min_fee_applies(): void
    {
        $q = $this->calc(['rider.min_fee' => 45])->quoteForDistance(0.5);

        $this->assertSame(45.0, $q['total_fee']);
        $this->assertSame(15.0, $q['distance_fee']);
    }

    public function test_service_area_limit(): void
    {
        $calc = $this->calc(['rider.max_distance_km' => 10]);

        $this->assertTrue($calc->quoteForDistance(10)['within_service_area']);
        $this->assertFalse($calc->quoteForDistance(10.01)['within_service_area']);
    }

    public function test_quote_uses_road_factor(): void
    {
        // 0.01 องศาละติจูด ≈ 1.112 กม. เส้นตรง × 1.3 ≈ 1.45 กม.
        $q = $this->calc()->quote(13.70, 100.50, 13.71, 100.50);

        $this->assertEqualsWithDelta(1.45, $q['distance_km'], 0.01);
        $this->assertSame(30.0, $q['total_fee']);
        $this->assertGreaterThan(0, $q['estimated_duration_minutes']);
    }

    public function test_haversine_one_degree_latitude(): void
    {
        $this->assertEqualsWithDelta(111.19, DeliveryFeeCalculator::haversineKm(0, 0, 1, 0), 0.05);
    }

    public function test_invalid_coordinates_are_rejected(): void
    {
        try {
            $this->calc()->quote(0.0, 0.0, 13.7, 100.5);
            $this->fail('พิกัด 0,0 ต้องถูกปฏิเสธ');
        } catch (RiderJobException $e) {
            $this->assertSame(RiderJobException::INVALID_LOCATION, $e->errorCode);
            $this->assertSame(422, $e->httpStatus);
        }

        $this->assertFalse(DeliveryFeeCalculator::isValidCoordinate(91, 100));
        $this->assertFalse(DeliveryFeeCalculator::isValidCoordinate('abc', 100));
        $this->assertTrue(DeliveryFeeCalculator::isValidCoordinate(13.7563, 100.5018));
    }

    public function test_split_respects_share_bounds(): void
    {
        $this->assertSame(['rider_earnings' => 50.0, 'platform_fee' => 0.0], $this->calc(['rider.rider_share_percent' => 150])->split(50));
        $this->assertSame(['rider_earnings' => 0.0, 'platform_fee' => 50.0], $this->calc(['rider.rider_share_percent' => -5])->split(50));
        $this->assertSame(['rider_earnings' => 40.0, 'platform_fee' => 10.0], $this->calc()->split(50));
    }

    public function test_settings_defaults_and_casts(): void
    {
        $calc = $this->calc(['rider.dispatch_mode' => 'weird', 'rider.require_deposit' => '1']);

        $this->assertSame('broadcast', $calc->dispatchMode());
        $this->assertTrue($calc->boolSetting('rider.require_deposit'));
        $this->assertSame(2000.0, $calc->maxCodAmount());
        $this->assertSame(15, $calc->intSetting('rider.location_fresh_minutes'));
        $this->assertSame('cascade', $this->calc(['rider.dispatch_mode' => 'cascade'])->dispatchMode());
    }
}
