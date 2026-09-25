<?php

namespace App\Services;

use App\Exceptions\RiderJobException;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;

/**
 * คำนวณค่าส่งไรเดอร์ + ส่วนแบ่งไรเดอร์/แพลตฟอร์ม (แหล่งความจริงเดียวของระบบ)
 *
 * ทุกที่ที่ต้องรู้ค่าส่ง (หน้าชำระเงิน, ตลาดสด, สร้างงานไรเดอร์) ต้องเรียก quote() ตัวนี้
 * เพื่อให้ "ค่าส่งที่ลูกค้าจ่าย" = "total_fee ของงานไรเดอร์" เสมอ
 *
 * สูตร:
 *   distance_km   = haversine(pickup, dropoff) × rider.road_factor   (ถนนจริงอ้อมกว่าเส้นตรง)
 *   distance_fee  = max(0, distance_km − rider.free_km) × rider.per_km_fee
 *   total_fee     = ปัดขึ้นเป็นบาทเต็ม( max(rider.min_fee, rider.base_fee + distance_fee) )
 *   rider_earnings = total_fee × rider.rider_share_percent / 100
 *   platform_fee  = total_fee − rider_earnings
 *
 * ค่าตั้งค่าอ่านจากตาราง settings (group = rider) ผ่าน key ใน DEFAULTS
 * ส่ง $overrides เข้า constructor ได้ (ใช้ในเทสต์ที่ไม่มีฐานข้อมูล)
 */
class DeliveryFeeCalculator
{
    /**
     * ค่าเริ่มต้นของทุก key ระบบไรเดอร์ (ต้องตรงกับ migration seed_rider_dispatch_settings)
     *
     * @var array<string, mixed>
     */
    public const DEFAULTS = [
        'rider.dispatch_mode' => 'broadcast',
        'rider.require_deposit' => false,
        'rider.base_fee' => 30.0,
        'rider.per_km_fee' => 10.0,
        'rider.free_km' => 2.0,
        'rider.min_fee' => 30.0,
        'rider.max_distance_km' => 15.0,
        'rider.road_factor' => 1.3,
        'rider.rider_share_percent' => 80.0,
        'rider.offer_radius_km' => 5.0,
        'rider.max_offer_radius_km' => 10.0,
        'rider.max_dispatch_rounds' => 3,
        'rider.rebroadcast_interval_minutes' => 2,
        'rider.offer_timeout_seconds' => 120,
        'rider.max_cod_amount' => 2000.0,
        'rider.pending_timeout_minutes' => 10,
        'rider.location_fresh_minutes' => 15,
        'rider.location_retention_days' => 30,
        'rider.tracking_expiry_hours' => 24,
        'rider.tracking_grace_minutes' => 15,
        'rider.avg_speed_kmh' => 25.0,
        'rider.max_release_count' => 3,
    ];

    /**
     * รัศมีโลก (กม.)
     */
    private const EARTH_RADIUS_KM = 6371.0;

    /**
     * ค่าที่โหลดแล้ว (โหลดครั้งเดียวต่อ instance)
     *
     * @var array<string, mixed>|null
     */
    private ?array $values = null;

    /**
     * @param  array<string, mixed>|null  $overrides  ค่าที่ใช้แทน settings (ถ้าส่งมา จะไม่แตะฐานข้อมูลเลย)
     */
    public function __construct(private readonly ?array $overrides = null) {}

    /**
     * อ่านค่าตั้งค่าระบบไรเดอร์ 1 key (มีค่า default เสมอ)
     */
    public function setting(string $key): mixed
    {
        $values = $this->values();

        return array_key_exists($key, $values) ? $values[$key] : (self::DEFAULTS[$key] ?? null);
    }

    public function floatSetting(string $key): float
    {
        return (float) $this->setting($key);
    }

    public function intSetting(string $key): int
    {
        return (int) $this->setting($key);
    }

    public function boolSetting(string $key): bool
    {
        $value = $this->setting($key);

        return is_bool($value) ? $value : filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * โหมดกระจายงาน: broadcast (ส่งทุกคนในรัศมี คนแรกที่กดได้) | cascade (เสนอทีละคน)
     */
    public function dispatchMode(): string
    {
        $mode = (string) $this->setting('rider.dispatch_mode');

        return in_array($mode, ['broadcast', 'cascade'], true) ? $mode : 'broadcast';
    }

    public function maxCodAmount(): float
    {
        return $this->floatSetting('rider.max_cod_amount');
    }

    public function maxDistanceKm(): float
    {
        return $this->floatSetting('rider.max_distance_km');
    }

    /**
     * ระยะทางอยู่ในพื้นที่ให้บริการหรือไม่
     */
    public function isWithinServiceArea(float $distanceKm): bool
    {
        return $distanceKm <= $this->maxDistanceKm();
    }

    /**
     * พิกัดใช้งานได้หรือไม่ (ไม่ใช่ 0,0 และอยู่ในช่วงที่ถูกต้อง)
     */
    public static function isValidCoordinate(mixed $lat, mixed $lng): bool
    {
        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return false;
        }

        $lat = (float) $lat;
        $lng = (float) $lng;

        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return false;
        }

        // 0,0 = ค่าว่างที่หลุดมา (กลางทะเล) ไม่ใช่พิกัดจริงในไทย
        return ! (abs($lat) < 0.000001 && abs($lng) < 0.000001);
    }

    /**
     * ระยะทางเส้นตรงบนผิวโลก (กม.) ด้วยสูตร Haversine — ไม่ปัดเศษ
     */
    public static function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lngDelta / 2) ** 2;

        return self::EARTH_RADIUS_KM * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /**
     * ระยะทางถนนโดยประมาณ (กม.) = เส้นตรง × road_factor ปัด 2 ตำแหน่ง
     */
    public function roadDistanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $factor = max(1.0, $this->floatSetting('rider.road_factor'));

        return round(self::haversineKm($lat1, $lng1, $lat2, $lng2) * $factor, 2);
    }

    /**
     * ใบเสนอราคาค่าส่ง
     *
     * @return array{distance_km: float, base_fee: float, distance_fee: float, total_fee: float, rider_earnings: float, platform_fee: float, estimated_duration_minutes: int, within_service_area: bool, max_distance_km: float}
     *
     * @throws RiderJobException INVALID_LOCATION เมื่อพิกัดไม่ถูกต้อง
     */
    public function quote(float $pickupLat, float $pickupLng, float $dropLat, float $dropLng): array
    {
        if (! self::isValidCoordinate($pickupLat, $pickupLng)) {
            throw RiderJobException::invalidLocation('จุดรับของ');
        }
        if (! self::isValidCoordinate($dropLat, $dropLng)) {
            throw RiderJobException::invalidLocation('จุดส่งของ');
        }

        $distanceKm = $this->roadDistanceKm($pickupLat, $pickupLng, $dropLat, $dropLng);

        return $this->quoteForDistance($distanceKm);
    }

    /**
     * ใบเสนอราคาจากระยะทางที่รู้แล้ว (กม.)
     *
     * @return array{distance_km: float, base_fee: float, distance_fee: float, total_fee: float, rider_earnings: float, platform_fee: float, estimated_duration_minutes: int, within_service_area: bool, max_distance_km: float}
     */
    public function quoteForDistance(float $distanceKm): array
    {
        $distanceKm = round(max(0.0, $distanceKm), 2);

        $baseFee = round(max(0.0, $this->floatSetting('rider.base_fee')), 2);
        $perKm = max(0.0, $this->floatSetting('rider.per_km_fee'));
        $freeKm = max(0.0, $this->floatSetting('rider.free_km'));
        $minFee = max(0.0, $this->floatSetting('rider.min_fee'));

        $rawDistanceFee = max(0.0, $distanceKm - $freeKm) * $perKm;

        // ปัดขึ้นเป็นบาทเต็ม (กันเศษสตางค์บนใบเสร็จ) และไม่ต่ำกว่าค่าส่งขั้นต่ำ
        $totalFee = (float) ceil(round(max($minFee, $baseFee + $rawDistanceFee), 2));
        $totalFee = max($totalFee, $baseFee);

        // distance_fee = ส่วนที่เกินค่าพื้นฐาน (รวมส่วนเติมให้ถึงขั้นต่ำ) → base + distance = total เสมอ
        $distanceFee = round($totalFee - $baseFee, 2);

        $split = $this->split($totalFee);

        return [
            'distance_km' => $distanceKm,
            'base_fee' => $baseFee,
            'distance_fee' => $distanceFee,
            'total_fee' => $totalFee,
            'rider_earnings' => $split['rider_earnings'],
            'platform_fee' => $split['platform_fee'],
            'estimated_duration_minutes' => $this->estimateDurationMinutes($distanceKm),
            'within_service_area' => $this->isWithinServiceArea($distanceKm),
            'max_distance_km' => round($this->maxDistanceKm(), 2),
        ];
    }

    /**
     * แบ่งค่าส่งให้ไรเดอร์/แพลตฟอร์ม (ใช้เมื่อค่าส่งถูกกำหนดมาแล้ว เช่นยอดที่ลูกค้าจ่ายจริง)
     *
     * @return array{rider_earnings: float, platform_fee: float}
     */
    public function split(float $totalFee): array
    {
        $totalFee = round(max(0.0, $totalFee), 2);
        $share = min(100.0, max(0.0, $this->floatSetting('rider.rider_share_percent')));

        $riderEarnings = round($totalFee * $share / 100, 2);
        $platformFee = round($totalFee - $riderEarnings, 2);

        return [
            'rider_earnings' => $riderEarnings,
            'platform_fee' => $platformFee,
        ];
    }

    /**
     * เวลาเดินทางโดยประมาณ (นาที) = ระยะ ÷ ความเร็วเฉลี่ย + เวลารับของ 5 นาที
     */
    public function estimateDurationMinutes(float $distanceKm): int
    {
        $speed = max(5.0, $this->floatSetting('rider.avg_speed_kmh'));

        return (int) ceil($distanceKm / $speed * 60) + 5;
    }

    /**
     * โหลดค่าทั้งหมดครั้งเดียว (1 query) แล้วแปลงชนิดตามคอลัมน์ type
     *
     * @return array<string, mixed>
     */
    private function values(): array
    {
        if ($this->values !== null) {
            return $this->values;
        }

        if ($this->overrides !== null) {
            return $this->values = array_merge(self::DEFAULTS, $this->overrides);
        }

        $values = self::DEFAULTS;

        try {
            $rows = Setting::query()
                ->whereIn('key', array_keys(self::DEFAULTS))
                ->get(['key', 'value', 'type']);

            foreach ($rows as $row) {
                $values[$row->key] = $this->cast($row->value, (string) $row->type, self::DEFAULTS[$row->key] ?? null);
            }
        } catch (\Throwable $e) {
            // ตาราง settings อ่านไม่ได้ → ใช้ค่า default (ไม่ให้หน้าชำระเงินล่ม)
            Log::warning('DeliveryFeeCalculator: cannot read settings, using defaults', ['error' => $e->getMessage()]);
        }

        return $this->values = $values;
    }

    /**
     * แปลงค่าจากตาราง settings ให้เป็นชนิดเดียวกับค่า default
     */
    private function cast(mixed $value, string $type, mixed $default): mixed
    {
        if ($value === null || $value === '') {
            return $default;
        }

        return match (true) {
            $type === 'boolean' || is_bool($default) => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            $type === 'integer' || is_int($default) => (int) $value,
            $type === 'float' || is_float($default) => (float) $value,
            default => (string) $value,
        };
    }
}
