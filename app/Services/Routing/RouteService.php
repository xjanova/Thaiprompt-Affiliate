<?php

namespace App\Services\Routing;

use App\Services\DeliveryFeeCalculator;
use App\Services\Routing\Drivers\GoogleRoutesDriver;
use App\Services\Routing\Drivers\RouteDriver;
use App\Services\Routing\Drivers\ValhallaDriver;
use App\Support\SafeLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 🛵 ระยะทางตามถนนจริง (ไรเดอร์รอบ 2) — แหล่งความจริงเดียวของ "ระยะ + เวลา + เส้นทาง" ของงานส่งของ
 *
 * ลำดับ (เจ้าของสั่ง 2026-10-04):
 *   1. Valhalla self-hosted (prod 127.0.0.1:8002, costing motor_scooter)
 *   2. Google Routes API v2 — เฉพาะเมื่อตั้ง services.google_maps.api_key
 *   3. เส้นตรง (haversine) × rider.road_factor — ไม่มีวันล้ม หน้าชำระเงินไม่ค้าง
 *
 * กันช้า/กันล่ม:
 *   - cache ผลตามพิกัดปัด 4 ตำแหน่ง (~11 ม.) 10 นาที → ตะกร้า → ชำระเงิน → สร้างงาน ได้ระยะเดียวกัน
 *   - ตัดวงจร (circuit breaker): บริการที่ต่อไม่ติด/หมดเวลา/5xx ถูกข้าม 3 นาที, คีย์เสีย (401/403) 15 นาที,
 *     ตอบมั่ว 3 ครั้งใน 5 นาที → ข้าม 3 นาที — ผู้ซื้อคนถัดไปไม่ต้องรอบริการที่ตาย
 *   - ผลแบบเส้นตรงไม่ cache (คำนวณเร็วอยู่แล้ว และพอบริการกลับมาจะได้ระยะถนนทันที)
 *
 * ห้าม log คีย์/URL ที่มีคีย์ — ข้อความ exception ของ driver ไม่มี URL อยู่แล้ว และผ่าน SafeLog อีกชั้น
 */
class RouteService
{
    /** อายุ cache ผลเส้นทาง (วินาที) */
    public const CACHE_TTL_SECONDS = 600;

    /** ตัดวงจรเมื่อบริการล่ม/หมดเวลา (วินาที) */
    public const CIRCUIT_OPEN_SECONDS = 180;

    /** ตัดวงจรเมื่อคีย์ใช้ไม่ได้ (วินาที) */
    public const AUTH_CIRCUIT_OPEN_SECONDS = 900;

    /** ตอบมั่วกี่ครั้งภายในช่วงเวลา จึงตัดวงจร */
    public const SOFT_FAILURE_THRESHOLD = 3;

    public const SOFT_FAILURE_WINDOW_SECONDS = 300;

    /** จุดรับ/ส่งใกล้กันไม่เกินนี้ (กม.) = จุดเดียวกัน ไม่ต้องถามบริการเส้นทาง */
    public const SAME_POINT_KM = 0.02;

    private const CACHE_PREFIX = 'rider_route:v1:';

    /** @var array<int, RouteDriver> */
    private array $drivers;

    /**
     * @param  array<int, RouteDriver>|null  $drivers  ลำดับผู้ให้บริการ (null = Valhalla → Google)
     */
    public function __construct(?array $drivers = null)
    {
        $this->drivers = $drivers ?? [new ValhallaDriver, new GoogleRoutesDriver];
    }

    /**
     * หาเส้นทางจาก → ถึง
     *
     * @param  float|null  $roadFactor  ตัวคูณระยะถนนตอนตกไปคิดแบบเส้นตรง (null = rider.road_factor)
     * @param  float|null  $avgSpeedKmh  ความเร็วเฉลี่ยตอนคิดแบบเส้นตรง (null = rider.avg_speed_kmh)
     *
     * @throws \InvalidArgumentException พิกัดไม่ถูกต้อง
     */
    public function route(float $fromLat, float $fromLng, float $toLat, float $toLng, ?float $roadFactor = null, ?float $avgSpeedKmh = null): RouteResult
    {
        if (! DeliveryFeeCalculator::isValidCoordinate($fromLat, $fromLng) || ! DeliveryFeeCalculator::isValidCoordinate($toLat, $toLng)) {
            throw new \InvalidArgumentException('พิกัดไม่ถูกต้อง');
        }

        $straightKm = DeliveryFeeCalculator::haversineKm($fromLat, $fromLng, $toLat, $toLng);

        // จุดเดียวกัน (เช่นร้านส่งให้ตึกเดียวกัน) — บริการเส้นทางมักตอบ error กับจุดซ้ำ
        if ($straightKm < self::SAME_POINT_KM) {
            return $this->straightLine($straightKm, $roadFactor, $avgSpeedKmh);
        }

        $cacheKey = $this->cacheKey($fromLat, $fromLng, $toLat, $toLng);
        $cached = RouteResult::fromArray($this->cacheGet($cacheKey));
        if ($cached !== null && $cached->isRoad()) {
            return $cached;
        }

        foreach ($this->drivers as $driver) {
            if (! $driver->enabled() || $this->circuitOpen($driver->name())) {
                continue;
            }

            try {
                $result = $driver->route($fromLat, $fromLng, $toLat, $toLng);
            } catch (RouteProviderException $e) {
                $this->recordFailure($driver->name(), $e);

                continue;
            } catch (\Throwable $e) {
                // บั๊กไม่คาดคิดใน driver ก็ห้ามทำให้หน้าชำระเงินล่ม
                $this->recordFailure($driver->name(), new RouteProviderException(RouteProviderException::BAD_RESPONSE, $driver->name().': '.SafeLog::exceptionMessage($e)));

                continue;
            }

            if (! $this->plausible($result, $straightKm)) {
                $this->recordFailure($driver->name(), new RouteProviderException(
                    RouteProviderException::BAD_RESPONSE,
                    $driver->name().': ระยะถนนสั้นกว่าเส้นตรง ('.number_format($result->distance_km, 2).' < '.number_format($straightKm, 2).' กม.)'
                ));

                continue;
            }

            $this->resetFailures($driver->name());
            $this->cachePut($cacheKey, $result->toArray());

            return $result;
        }

        return $this->straightLine($straightKm, $roadFactor, $avgSpeedKmh);
    }

    /**
     * บริการนี้ถูกตัดวงจรอยู่หรือไม่ (ใช้ในเทสต์/หน้าแอดมิน)
     */
    public function circuitOpen(string $driver): bool
    {
        try {
            $until = Cache::get(self::CACHE_PREFIX.'circuit:'.$driver);
        } catch (\Throwable) {
            return false;
        }

        return is_numeric($until) && (int) $until > time();
    }

    /**
     * ระยะแบบเส้นตรง × ตัวคูณถนน (ทางสุดท้าย — ไม่มีวันล้ม)
     */
    private function straightLine(float $straightKm, ?float $roadFactor, ?float $avgSpeedKmh): RouteResult
    {
        if ($roadFactor === null || $avgSpeedKmh === null) {
            $settings = app(DeliveryFeeCalculator::class);
            $roadFactor ??= $settings->floatSetting('rider.road_factor');
            $avgSpeedKmh ??= $settings->floatSetting('rider.avg_speed_kmh');
        }

        $distanceKm = round($straightKm * max(1.0, $roadFactor), 2);
        $speed = max(5.0, $avgSpeedKmh);

        return new RouteResult(
            $distanceKm,
            max(1, (int) ceil($distanceKm / $speed * 60)),
            null,
            RouteResult::SOURCE_HAVERSINE,
        );
    }

    /**
     * ระยะถนนต้องไม่สั้นกว่าเส้นตรง (เผื่อคลาดเคลื่อน 10% + 50 ม.) — สั้นกว่านั้น = ข้อมูลผิด
     */
    private function plausible(RouteResult $result, float $straightKm): bool
    {
        return $result->distance_km >= 0 && $result->distance_km + 0.05 >= $straightKm * 0.9;
    }

    /**
     * บันทึกความล้มเหลว → ตัดวงจรตามชนิด
     */
    private function recordFailure(string $driver, RouteProviderException $e): void
    {
        // หาเส้นทางคู่นี้ไม่เจอ = บริการยังดีอยู่ ไม่ต้องตัดวงจร
        if ($e->kind === RouteProviderException::NO_ROUTE) {
            return;
        }

        try {
            if ($e->kind === RouteProviderException::BAD_RESPONSE) {
                $failKey = self::CACHE_PREFIX.'fails:'.$driver;
                Cache::add($failKey, 0, self::SOFT_FAILURE_WINDOW_SECONDS);
                $fails = (int) Cache::increment($failKey);

                if ($fails < self::SOFT_FAILURE_THRESHOLD) {
                    return;
                }
            }

            $seconds = $e->kind === RouteProviderException::AUTH ? self::AUTH_CIRCUIT_OPEN_SECONDS : self::CIRCUIT_OPEN_SECONDS;
            Cache::put(self::CACHE_PREFIX.'circuit:'.$driver, time() + $seconds, $seconds);
            Cache::forget(self::CACHE_PREFIX.'fails:'.$driver);
        } catch (\Throwable) {
            // cache ใช้ไม่ได้ → ไม่ตัดวงจร แต่ยังตกไปตัวถัดไปตามปกติ
            return;
        }

        // log ครั้งเดียวตอนตัดวงจร (ไม่ log ทุกคำขอ)
        Log::warning('RouteService: พักใช้ '.$driver.' ชั่วคราว', [
            'kind' => $e->kind,
            'seconds' => $e->kind === RouteProviderException::AUTH ? self::AUTH_CIRCUIT_OPEN_SECONDS : self::CIRCUIT_OPEN_SECONDS,
            'reason' => SafeLog::redactSecrets($e->getMessage()),
        ]);
    }

    private function resetFailures(string $driver): void
    {
        try {
            Cache::forget(self::CACHE_PREFIX.'fails:'.$driver);
        } catch (\Throwable) {
            // ข้าม
        }
    }

    /**
     * key cache ตามพิกัดปัด 4 ตำแหน่ง (ใช้ sprintf ทศนิยมคงที่ — ไม่พึ่งการแปลง float เป็นสตริงแบบอัตโนมัติ)
     */
    private function cacheKey(float $fromLat, float $fromLng, float $toLat, float $toLng): string
    {
        return self::CACHE_PREFIX.sprintf('%.4f,%.4f;%.4f,%.4f', $fromLat, $fromLng, $toLat, $toLng);
    }

    private function cacheGet(string $key): mixed
    {
        try {
            return Cache::get($key);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $value
     */
    private function cachePut(string $key, array $value): void
    {
        try {
            Cache::put($key, $value, self::CACHE_TTL_SECONDS);
        } catch (\Throwable) {
            // cache ล่ม = แค่ช้าลง ไม่ใช่ error
        }
    }
}
