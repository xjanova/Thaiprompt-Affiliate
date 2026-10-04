<?php

namespace App\Services\Routing;

/**
 * เข้ารหัส/ถอดรหัส encoded polyline (อัลกอริทึมของ Google)
 *
 * - Valhalla ส่ง shape มาเป็น precision 6 (ทศนิยม 6 ตำแหน่ง)
 * - Google Routes API ส่งมาเป็น precision 5
 * - ระบบเราเก็บ/ส่งให้แอปเป็น precision 6 เสมอ (สัญญากลาง route_polyline) → Google ต้องแปลงก่อน
 */
final class Polyline
{
    /**
     * ถอดรหัส polyline เป็นรายการจุด [[lat, lng], ...]
     *
     * @return array<int, array{0: float, 1: float}>
     *
     * @throws \InvalidArgumentException เมื่อสตริงเสีย (อ่านไม่จบ)
     */
    public static function decode(string $encoded, int $precision = 6): array
    {
        $factor = 10 ** $precision;
        $points = [];
        $index = 0;
        $length = strlen($encoded);
        $lat = 0;
        $lng = 0;

        while ($index < $length) {
            foreach (['lat', 'lng'] as $axis) {
                $result = 0;
                $shift = 0;

                do {
                    if ($index >= $length) {
                        throw new \InvalidArgumentException('polyline ไม่สมบูรณ์');
                    }
                    $byte = ord($encoded[$index++]) - 63;
                    if ($byte < 0) {
                        throw new \InvalidArgumentException('polyline มีอักขระไม่ถูกต้อง');
                    }
                    $result |= ($byte & 0x1F) << $shift;
                    $shift += 5;
                } while ($byte >= 0x20 && $shift < 60);

                $delta = ($result & 1) ? ~($result >> 1) : ($result >> 1);

                if ($axis === 'lat') {
                    $lat += $delta;
                } else {
                    $lng += $delta;
                }
            }

            $points[] = [$lat / $factor, $lng / $factor];
        }

        return $points;
    }

    /**
     * เข้ารหัสรายการจุด [[lat, lng], ...] เป็น polyline
     *
     * @param  array<int, array{0: float, 1: float}>  $points
     */
    public static function encode(array $points, int $precision = 6): string
    {
        $factor = 10 ** $precision;
        $output = '';
        $prevLat = 0;
        $prevLng = 0;

        foreach ($points as $point) {
            // ปัดเป็นจำนวนเต็มก่อนหาผลต่าง (กันเศษสะสมจาก float)
            $lat = (int) round(((float) $point[0]) * $factor);
            $lng = (int) round(((float) $point[1]) * $factor);

            $output .= self::encodeValue($lat - $prevLat).self::encodeValue($lng - $prevLng);

            $prevLat = $lat;
            $prevLng = $lng;
        }

        return $output;
    }

    /**
     * แปลง precision (เช่น polyline ของ Google precision 5 → precision 6 ของระบบ)
     */
    public static function convert(string $encoded, int $fromPrecision, int $toPrecision): string
    {
        if ($fromPrecision === $toPrecision) {
            return $encoded;
        }

        return self::encode(self::decode($encoded, $fromPrecision), $toPrecision);
    }

    /**
     * เข้ารหัสตัวเลข 1 ค่า (signed varint แบบ Google)
     */
    private static function encodeValue(int $value): string
    {
        $value = $value < 0 ? ~($value << 1) : ($value << 1);
        $chunk = '';

        while ($value >= 0x20) {
            $chunk .= chr((0x20 | ($value & 0x1F)) + 63);
            $value >>= 5;
        }

        return $chunk.chr($value + 63);
    }
}
