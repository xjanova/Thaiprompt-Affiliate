<?php

namespace App\Services;

use App\Exceptions\RiderJobException;
use App\Models\Setting;
use App\Services\Routing\RouteResult;
use App\Services\Routing\RouteService;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * คำนวณค่าส่งไรเดอร์ + ส่วนแบ่งไรเดอร์/แพลตฟอร์ม (แหล่งความจริงเดียวของระบบ)
 *
 * ทุกที่ที่ต้องรู้ค่าส่ง (หน้าชำระเงิน, ตลาดสด, สร้างงานไรเดอร์) ต้องเรียก quote() ตัวนี้
 * เพื่อให้ "ค่าส่งที่ลูกค้าจ่าย" = "total_fee ของงานไรเดอร์" เสมอ
 *
 * สูตร:
 *   distance_km   = ระยะตามถนนจาก RouteService (Valhalla → Google → เส้นตรง × rider.road_factor)
 *   distance_fee  = max(0, distance_km − rider.free_km) × rider.per_km_fee
 *   total_fee     = ปัดขึ้นเป็นบาทเต็ม( max(rider.min_fee, rider.base_fee + distance_fee) ) + surcharge
 *   surcharge     = rider.night_surcharge (ช่วง [night_start_hour, night_end_hour)) + rider.peak_surcharge (ชั่วโมงเร่งด่วน)
 *   rider_earnings = total_fee × rider.rider_share_percent / 100
 *   platform_fee  = total_fee − rider_earnings
 *
 * ไรเดอร์รอบ 2 (2026-10-04) — ร้านกำหนดเพิ่มได้ (คอลัมน์ของ vendor_stores / fresh_market_sellers):
 *   shop_bonus   = rider_bonus_peak (ถ้ามากกว่า rider_bonus และอยู่ในชั่วโมงเร่งด่วน) ไม่งั้น rider_bonus — ไรเดอร์ได้เต็ม ร้านจ่าย
 *   free_delivery = rider_free_delivery → buyer_fee = 0 และ shop_subsidy = total_fee (ร้านออกค่าส่งทั้งหมด)
 *   rider_total  = rider_earnings + shop_bonus
 *   total_fee ยังหมายถึง "ค่าส่งเต็ม" เสมอ · buyer_fee = ที่ผู้ซื้อจ่ายจริง
 *   เพดาน (capShopCosts): shop_subsidy + shop_bonus ≤ รายได้สุทธิของร้านจากออเดอร์นั้น — ลดค่าส่งที่ออกให้ก่อน แล้วลดโบนัส
 *
 * ค่าตั้งค่าอ่านจากตาราง settings (group = rider) ผ่าน key ใน DEFAULTS
 * ส่ง $overrides เข้า constructor ได้ (ใช้ในเทสต์ที่ไม่มีฐานข้อมูล — โหมดนี้คิดระยะแบบเส้นตรงเท่านั้น ไม่ยิงเครือข่าย)
 */
class DeliveryFeeCalculator
{
    /**
     * ชั่วโมงเร่งด่วน [เริ่ม, จบ) ตามเวลาไทย — ไรเดอร์ว่างน้อย ร้านตั้งโบนัสช่วงนี้แยกได้
     *
     * @var array<int, array{0: int, 1: int}>
     */
    public const PEAK_HOURS = [[11, 13], [17, 19]];

    /** เขตเวลาที่ใช้ตัดสินกลางคืน/ชั่วโมงเร่งด่วน */
    public const TIMEZONE = 'Asia/Bangkok';

    /** โบนัสไรเดอร์ที่ร้านตั้งได้สูงสุด (บาท/ออเดอร์) — ตรงกับ validation ของ PUT /seller/rider-pay */
    public const MAX_SHOP_BONUS = 100.0;

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
        // ===== ไรเดอร์รอบ 2 (2026-10-04) =====
        // ส่วนเพิ่มค่าส่งช่วงกลางคืน/ชั่วโมงเร่งด่วน (บาท) — 0 = ไม่เพิ่ม จนกว่าแอดมินจะตั้ง
        'rider.night_surcharge' => 0.0,
        'rider.night_start_hour' => 22,
        'rider.night_end_hour' => 6,
        'rider.peak_surcharge' => 0.0,
        // งานไรเดอร์ต้องจ่ายก่อน (เงินพักไว้จนส่งมอบ) — 0 = ปิดเก็บเงินปลายทางกับไรเดอร์
        'rider.allow_cod' => false,
        // ส่งมอบของ: งานใหม่ต้องสแกน · รัศมีจุดส่ง (ม.) · อายุ QR (วิ) · รอผู้ซื้อ (วิ) · ปลดเงินอัตโนมัติ (ชม.)
        'rider.handover_enabled' => true,
        'rider.handover_geofence_m' => 150,
        'rider.handover_qr_ttl_seconds' => 60,
        'rider.handover_wait_seconds' => 180,
        'rider.handover_auto_release_hours' => 24,
        // ล็อกเรียกไรเดอร์: ผู้ซื้อคนนั้นต้องให้หัวใจไรเดอร์คนนั้นอย่างน้อยกี่ดวง (เกิน 10 = 11) · สิทธิ์รับก่อนกี่วินาที
        'rider.lock_min_hearts' => 11,
        'rider.lock_offer_seconds' => 60,
        // ไรเดอร์ใกล้ฉัน: รัศมี (กม.) · เบลอตำแหน่ง (ม.)
        'rider.nearby_radius_km' => 3.0,
        'rider.nearby_fuzz_m' => 200,
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
     * @param  RouteService|null  $routes  ตัวหาระยะตามถนน (null = ใช้ของ container — ยกเว้นโหมด overrides ที่คิดเส้นตรงอย่างเดียว)
     */
    public function __construct(
        private readonly ?array $overrides = null,
        private readonly ?RouteService $routes = null,
    ) {}

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
     * ใบเสนอราคาค่าส่ง (ระยะตามถนนจริง + ส่วนเพิ่มตามช่วงเวลา + ค่าที่ร้านตั้ง)
     *
     * @param  Model|null  $store  ร้านต้นทาง (VendorStore / FreshMarketSeller) — อ่าน rider_bonus, rider_bonus_peak, rider_free_delivery
     * @param  CarbonInterface|null  $at  เวลาที่ใช้ตัดสินกลางคืน/ชั่วโมงเร่งด่วน (null = ตอนนี้)
     * @return array{distance_km: float, base_fee: float, distance_fee: float, total_fee: float, rider_earnings: float, platform_fee: float, estimated_duration_minutes: int, within_service_area: bool, max_distance_km: float, distance_source: string, route_polyline: ?string, surcharge: float, shop_bonus: float, shop_subsidy: float, free_delivery: bool, buyer_fee: float, rider_total: float, subsidy_capped: bool, bonus_capped: bool}
     *
     * @throws RiderJobException INVALID_LOCATION เมื่อพิกัดไม่ถูกต้อง
     */
    public function quote(float $pickupLat, float $pickupLng, float $dropLat, float $dropLng, ?Model $store = null, ?CarbonInterface $at = null): array
    {
        if (! self::isValidCoordinate($pickupLat, $pickupLng)) {
            throw RiderJobException::invalidLocation('จุดรับของ');
        }
        if (! self::isValidCoordinate($dropLat, $dropLng)) {
            throw RiderJobException::invalidLocation('จุดส่งของ');
        }

        $route = $this->route($pickupLat, $pickupLng, $dropLat, $dropLng);

        return $this->quoteForDistance($route->distance_km, $store, $at ?? now(), $route);
    }

    /**
     * ระยะ/เวลา/เส้นทางจากจุดรับ → จุดส่ง
     *
     * โหมด overrides (เทสต์ไม่มีฐานข้อมูล) ไม่มี RouteService → คิดเส้นตรง × road_factor แบบเดิม ไม่ยิงเครือข่าย
     */
    public function route(float $pickupLat, float $pickupLng, float $dropLat, float $dropLng): RouteResult
    {
        $routes = $this->routes ?? ($this->overrides === null ? app(RouteService::class) : null);
        $roadFactor = max(1.0, $this->floatSetting('rider.road_factor'));
        $speed = max(5.0, $this->floatSetting('rider.avg_speed_kmh'));

        if ($routes === null) {
            $distanceKm = $this->roadDistanceKm($pickupLat, $pickupLng, $dropLat, $dropLng);

            return new RouteResult($distanceKm, max(1, (int) ceil($distanceKm / $speed * 60)), null, RouteResult::SOURCE_HAVERSINE);
        }

        return $routes->route($pickupLat, $pickupLng, $dropLat, $dropLng, $roadFactor, $speed);
    }

    /**
     * ใบเสนอราคาจากระยะทางที่รู้แล้ว (กม.)
     *
     * ส่วนเพิ่มกลางคืน/เร่งด่วนคิดเฉพาะเมื่อส่ง $at มา (ตารางค่าส่งในหน้าแอดมิน/ผู้ช่วยตั้งราคาไม่ขึ้นกับเวลา)
     * ไม่ส่ง $at = โบนัสร้านใช้ rider_bonus ปกติ
     *
     * @param  Model|null  $store  ร้านต้นทาง (อ่าน rider_bonus, rider_bonus_peak, rider_free_delivery)
     * @param  RouteResult|null  $route  ผลเส้นทาง (ให้ distance_source, route_polyline, เวลาเดินทางจริง)
     * @return array{distance_km: float, base_fee: float, distance_fee: float, total_fee: float, rider_earnings: float, platform_fee: float, estimated_duration_minutes: int, within_service_area: bool, max_distance_km: float, distance_source: string, route_polyline: ?string, surcharge: float, shop_bonus: float, shop_subsidy: float, free_delivery: bool, buyer_fee: float, rider_total: float, subsidy_capped: bool, bonus_capped: bool}
     */
    public function quoteForDistance(float $distanceKm, ?Model $store = null, ?CarbonInterface $at = null, ?RouteResult $route = null): array
    {
        $distanceKm = round(max(0.0, $distanceKm), 2);

        $baseFee = round(max(0.0, $this->floatSetting('rider.base_fee')), 2);
        $perKm = max(0.0, $this->floatSetting('rider.per_km_fee'));
        $freeKm = max(0.0, $this->floatSetting('rider.free_km'));
        $minFee = max(0.0, $this->floatSetting('rider.min_fee'));

        $rawDistanceFee = max(0.0, $distanceKm - $freeKm) * $perKm;

        // ปัดขึ้นเป็นบาทเต็ม (กันเศษสตางค์บนใบเสร็จ) และไม่ต่ำกว่าค่าส่งขั้นต่ำ
        $formulaFee = (float) ceil(round(max($minFee, $baseFee + $rawDistanceFee), 2));
        $formulaFee = max($formulaFee, $baseFee);

        // ส่วนเพิ่มกลางคืน/ชั่วโมงเร่งด่วน (ผู้ซื้อจ่าย ไรเดอร์ได้ส่วนแบ่งตามปกติ)
        $surcharge = $at !== null ? $this->surchargeAt($at) : 0.0;
        $totalFee = round($formulaFee + $surcharge, 2);

        // distance_fee = ส่วนที่เกินค่าพื้นฐาน (รวมส่วนเติมให้ถึงขั้นต่ำ) → base + distance + surcharge = total เสมอ
        $distanceFee = round($totalFee - $baseFee - $surcharge, 2);

        $split = $this->split($totalFee);

        // ค่าที่ร้านตั้ง: โบนัสไรเดอร์ (ร้านจ่าย ไรเดอร์ได้เต็ม) + ส่งฟรี (ร้านออกค่าส่งทั้งหมดแทนผู้ซื้อ)
        $shopBonus = $this->shopBonusFor($store, $at);
        $freeDelivery = self::storeFreeDelivery($store);

        return [
            'distance_km' => $distanceKm,
            'base_fee' => $baseFee,
            'distance_fee' => $distanceFee,
            'total_fee' => $totalFee,
            'rider_earnings' => $split['rider_earnings'],
            'platform_fee' => $split['platform_fee'],
            'estimated_duration_minutes' => $route !== null
                ? $route->duration_minutes + 5
                : $this->estimateDurationMinutes($distanceKm),
            'within_service_area' => $this->isWithinServiceArea($distanceKm),
            'max_distance_km' => round($this->maxDistanceKm(), 2),
            // ===== ไรเดอร์รอบ 2 =====
            'distance_source' => $route?->source ?? RouteResult::SOURCE_HAVERSINE,
            'route_polyline' => $route?->polyline,
            'surcharge' => $surcharge,
            'shop_bonus' => $shopBonus,
            'shop_subsidy' => $freeDelivery ? $totalFee : 0.0,
            'free_delivery' => $freeDelivery,
            'buyer_fee' => $freeDelivery ? 0.0 : $totalFee,
            'rider_total' => round($split['rider_earnings'] + $shopBonus, 2),
            // ยังไม่รู้ยอดของออเดอร์ = ยังไม่ถูกจำกัด (capShopCosts ตั้งค่าเมื่อรู้รายได้ร้าน)
            'subsidy_capped' => false,
            'bonus_capped' => false,
        ];
    }

    /**
     * เพดานต้นทุนไรเดอร์ที่ร้านเลือกจ่าย (money-review C1) — ร้านจ่ายได้ไม่เกินรายได้สุทธิที่คาดว่าจะได้จากออเดอร์นั้น
     *
     * ไม่งั้นสินค้า ฿1 + ส่งฟรี + โบนัส 100 ที่ 15 กม. → แพลตฟอร์มจ่ายไรเดอร์เกินเงินที่ผู้ซื้อจ่ายเข้ามา
     * (ตอนแบ่งเงิน capRiderDeduction หักร้านได้ไม่เกินรายได้ ส่วนที่เหลือแพลตฟอร์มรับภาระ)
     *
     * ลำดับการลด: ค่าส่งที่ร้านออกให้ก่อน (ผู้ซื้อจ่ายส่วนที่เหลือ → buyer_fee สูงขึ้น, subsidy_capped)
     * แล้วจึงลดโบนัสไรเดอร์ (bonus_capped) · ค่าส่งเต็ม (total_fee) และส่วนแบ่งไรเดอร์ไม่เปลี่ยน
     *
     * ฟังก์ชันบริสุทธิ์ (ไม่แตะฐานข้อมูล) — เรียกซ้ำด้วยงบที่น้อยลงได้ผลเท่ากับเรียกครั้งเดียวด้วยงบนั้น
     *
     * @param  array<string, mixed>  $quote  ผลจาก quote()/quoteForDistance() (ใช้ total_fee, rider_earnings, shop_bonus, shop_subsidy, free_delivery)
     * @param  float  $shopNet  รายได้สุทธิที่ร้านคาดว่าจะได้ = ยอดสินค้าหลังส่วนลดที่ร้านออก − GP/VAT/ค่าแนะนำ ตามอัตราปัจจุบัน
     * @param  float  $otherShopCosts  ต้นทุนอื่นที่ร้านจ่ายจากรายได้ก้อนเดียวกันก่อน (เช่น ส่วนลดค่าส่งจากคูปองร้าน)
     * @return array<string, mixed> quote เดิมที่ปรับ shop_subsidy, shop_bonus, buyer_fee, rider_total, free_delivery + subsidy_capped, bonus_capped
     */
    public static function capShopCosts(array $quote, float $shopNet, float $otherShopCosts = 0.0): array
    {
        $budget = round(max(0.0, $shopNet - max(0.0, $otherShopCosts)), 2);
        $totalFee = round(max(0.0, (float) ($quote['total_fee'] ?? 0)), 2);
        $wantSubsidy = round(min($totalFee, max(0.0, (float) ($quote['shop_subsidy'] ?? 0))), 2);
        $wantBonus = round(max(0.0, (float) ($quote['shop_bonus'] ?? 0)), 2);

        // 1) ค่าส่งที่ร้านออกให้ก่อน 2) โบนัสไรเดอร์จากงบที่เหลือ
        $subsidy = round(min($wantSubsidy, $budget), 2);
        $bonus = round(min($wantBonus, max(0.0, $budget - $subsidy)), 2);

        $subsidyCapped = $subsidy < $wantSubsidy || ! empty($quote['subsidy_capped']);
        $bonusCapped = $bonus < $wantBonus || ! empty($quote['bonus_capped']);

        $quote['shop_subsidy'] = $subsidy;
        $quote['shop_bonus'] = $bonus;
        $quote['buyer_fee'] = round($totalFee - $subsidy, 2);
        $quote['rider_total'] = round((float) ($quote['rider_earnings'] ?? 0) + $bonus, 2);
        // ร้านเลือกส่งฟรีแต่ออกได้ไม่เต็ม → ผู้ซื้อจ่ายส่วนที่เหลือ ไม่ใช่ "ส่งฟรี" แล้ว
        $quote['free_delivery'] = (bool) ($quote['free_delivery'] ?? false) && ! $subsidyCapped;
        $quote['subsidy_capped'] = $subsidyCapped;
        $quote['bonus_capped'] = $bonusCapped;

        return $quote;
    }

    /**
     * ส่วนเพิ่มค่าส่ง ณ เวลาที่กำหนด (บาท) = กลางคืน + ชั่วโมงเร่งด่วน (ถ้าซ้อนกันได้ทั้งคู่)
     */
    public function surchargeAt(CarbonInterface $at): float
    {
        $hour = $this->localHour($at);
        $surcharge = 0.0;

        if ($this->isNightHour($hour)) {
            $surcharge += max(0.0, $this->floatSetting('rider.night_surcharge'));
        }

        if (self::isPeakHour($hour)) {
            $surcharge += max(0.0, $this->floatSetting('rider.peak_surcharge'));
        }

        return round($surcharge, 2);
    }

    /**
     * ชั่วโมงนี้ (0–23 เวลาไทย) อยู่ช่วงกลางคืน [night_start_hour, night_end_hour) หรือไม่ — ข้ามเที่ยงคืนได้ เช่น 22 → 6
     * เริ่ม = จบ หมายถึงปิดส่วนเพิ่มกลางคืน
     */
    public function isNightHour(int $hour): bool
    {
        $start = ((($this->intSetting('rider.night_start_hour')) % 24) + 24) % 24;
        $end = ((($this->intSetting('rider.night_end_hour')) % 24) + 24) % 24;

        if ($start === $end) {
            return false;
        }

        return $start < $end
            ? $hour >= $start && $hour < $end
            : $hour >= $start || $hour < $end;
    }

    /**
     * ชั่วโมงนี้ (0–23 เวลาไทย) เป็นชั่วโมงเร่งด่วนหรือไม่ (PEAK_HOURS)
     */
    public static function isPeakHour(int $hour): bool
    {
        foreach (self::PEAK_HOURS as [$start, $end]) {
            if ($hour >= $start && $hour < $end) {
                return true;
            }
        }

        return false;
    }

    /**
     * เวลานี้เป็นชั่วโมงเร่งด่วนหรือไม่
     */
    public function isPeakAt(CarbonInterface $at): bool
    {
        return self::isPeakHour($this->localHour($at));
    }

    /**
     * โบนัสไรเดอร์ที่ร้านจ่าย (บาท) — ชั่วโมงเร่งด่วนใช้ rider_bonus_peak ถ้ามากกว่า rider_bonus
     */
    public function shopBonusFor(?Model $store, ?CarbonInterface $at = null): float
    {
        if ($store === null) {
            return 0.0;
        }

        $bonus = self::clampBonus($store->getAttribute('rider_bonus'));
        $peak = self::clampBonus($store->getAttribute('rider_bonus_peak'));

        if ($at !== null && $peak > $bonus && $this->isPeakAt($at)) {
            return $peak;
        }

        return $bonus;
    }

    /**
     * ร้านเลือก "ส่งฟรี" (ร้านออกค่าส่งทั้งหมด) หรือไม่
     */
    public static function storeFreeDelivery(?Model $store): bool
    {
        return $store !== null && filter_var($store->getAttribute('rider_free_delivery'), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * โบนัสร้านอยู่ในช่วง 0..MAX_SHOP_BONUS ทศนิยม 2 ตำแหน่ง (ค่าเสีย/ติดลบ = 0)
     */
    public static function clampBonus(mixed $value): float
    {
        if (! is_numeric($value)) {
            return 0.0;
        }

        return round(min(self::MAX_SHOP_BONUS, max(0.0, (float) $value)), 2);
    }

    /**
     * ชั่วโมงตามเวลาไทย (0–23)
     */
    private function localHour(CarbonInterface $at): int
    {
        return (int) $at->copy()->setTimezone(self::TIMEZONE)->format('G');
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
