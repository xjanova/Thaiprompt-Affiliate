<?php

namespace App\Services\Routing;

/**
 * ผลการหาเส้นทาง 1 ครั้ง (สัญญากลางไรเดอร์รอบ 2)
 *
 * - distance_km      ระยะตามถนน (กม. ทศนิยม 2 ตำแหน่ง)
 * - duration_minutes เวลาเดินทางอย่างเดียว (นาที ปัดขึ้น) — ยังไม่รวมเวลารับของ
 * - polyline         เส้นทางแบบ encoded polyline precision 6 (null = ไม่มี เช่นคำนวณแบบเส้นตรง)
 * - source           valhalla | google | haversine
 */
final class RouteResult
{
    public const SOURCE_VALHALLA = 'valhalla';

    public const SOURCE_GOOGLE = 'google';

    public const SOURCE_HAVERSINE = 'haversine';

    public function __construct(
        public readonly float $distance_km,
        public readonly int $duration_minutes,
        public readonly ?string $polyline,
        public readonly string $source,
    ) {}

    /**
     * ได้ระยะจากถนนจริง (ไม่ใช่เส้นตรง × ตัวคูณ)
     */
    public function isRoad(): bool
    {
        return $this->source !== self::SOURCE_HAVERSINE;
    }

    /**
     * @return array{distance_km: float, duration_minutes: int, polyline: ?string, source: string}
     */
    public function toArray(): array
    {
        return [
            'distance_km' => $this->distance_km,
            'duration_minutes' => $this->duration_minutes,
            'polyline' => $this->polyline,
            'source' => $this->source,
        ];
    }

    /**
     * สร้างกลับจากค่าที่เก็บใน cache (ข้อมูลเสีย = null)
     *
     * @param  mixed  $data
     */
    public static function fromArray($data): ?self
    {
        if (! is_array($data) || ! isset($data['distance_km'], $data['duration_minutes'], $data['source'])) {
            return null;
        }

        if (! in_array($data['source'], [self::SOURCE_VALHALLA, self::SOURCE_GOOGLE, self::SOURCE_HAVERSINE], true)) {
            return null;
        }

        return new self(
            round((float) $data['distance_km'], 2),
            (int) $data['duration_minutes'],
            isset($data['polyline']) && is_string($data['polyline']) && $data['polyline'] !== '' ? $data['polyline'] : null,
            (string) $data['source'],
        );
    }
}
