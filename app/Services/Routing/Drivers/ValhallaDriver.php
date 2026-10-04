<?php

namespace App\Services\Routing\Drivers;

use App\Services\Routing\Polyline;
use App\Services\Routing\RouteProviderException;
use App\Services\Routing\RouteResult;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Valhalla (self-hosted บน prod ที่ 127.0.0.1:8002) — แหล่งแรกของระยะทางตามถนน
 *
 * POST {url}/route
 *   {"locations":[{"lat":..,"lon":..},{"lat":..,"lon":..}], "costing":"motor_scooter", "units":"kilometers", "directions_type":"none"}
 * ตอบ trip.summary.length (กม.) · trip.summary.time (วินาที) · trip.legs[].shape (polyline precision 6)
 *
 * ค่าตั้ง: config('services.valhalla') — url, timeout (วินาที), enabled, costing
 */
class ValhallaDriver implements RouteDriver
{
    public function name(): string
    {
        return RouteResult::SOURCE_VALHALLA;
    }

    public function enabled(): bool
    {
        return (bool) config('services.valhalla.enabled', true)
            && trim((string) config('services.valhalla.url', '')) !== '';
    }

    public function route(float $fromLat, float $fromLng, float $toLat, float $toLng): RouteResult
    {
        $url = rtrim((string) config('services.valhalla.url'), '/').'/route';
        $timeout = max(0.5, (float) config('services.valhalla.timeout', 2.5));

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->connectTimeout(min(1.0, $timeout))
                ->timeout($timeout)
                ->post($url, [
                    'locations' => [
                        ['lat' => round($fromLat, 6), 'lon' => round($fromLng, 6)],
                        ['lat' => round($toLat, 6), 'lon' => round($toLng, 6)],
                    ],
                    'costing' => (string) config('services.valhalla.costing', 'motor_scooter'),
                    'units' => 'kilometers',
                    // ไม่ต้องการคำบอกทางเลี้ยว (ลดขนาดคำตอบ) — ใช้แค่ระยะ เวลา และเส้นทาง
                    'directions_type' => 'none',
                ]);
        } catch (ConnectionException) {
            // ห้ามแนบข้อความ exception ดิบ (มี URL เต็ม)
            throw new RouteProviderException(RouteProviderException::UNAVAILABLE, 'valhalla: ต่อไม่ติดหรือหมดเวลา');
        }

        if ($response->status() === 400) {
            // 4xx ของ Valhalla = หาเส้นทางคู่นี้ไม่เจอ (เช่น error_code 442 No path) — ไม่ใช่บริการล่ม
            throw new RouteProviderException(RouteProviderException::NO_ROUTE, 'valhalla: ไม่พบเส้นทาง (400)');
        }

        if (! $response->successful()) {
            throw new RouteProviderException(RouteProviderException::UNAVAILABLE, 'valhalla: HTTP '.$response->status());
        }

        $trip = $response->json('trip');
        $length = data_get($trip, 'summary.length');
        $seconds = data_get($trip, 'summary.time');

        if (! is_array($trip) || ! is_numeric($length) || ! is_numeric($seconds) || (float) $length < 0) {
            throw new RouteProviderException(RouteProviderException::BAD_RESPONSE, 'valhalla: คำตอบไม่มี trip.summary');
        }

        return new RouteResult(
            round((float) $length, 2),
            max(1, (int) ceil((float) $seconds / 60)),
            $this->shape($trip),
            RouteResult::SOURCE_VALHALLA,
        );
    }

    /**
     * รวม shape ของทุกช่วง (leg) เป็น polyline เดียว precision 6 — 2 จุดปกติมีช่วงเดียว
     *
     * @param  array<string, mixed>  $trip
     */
    private function shape(array $trip): ?string
    {
        $legs = array_values(array_filter((array) ($trip['legs'] ?? []), 'is_array'));
        $shapes = array_values(array_filter(array_map(fn ($leg) => $leg['shape'] ?? null, $legs), fn ($s) => is_string($s) && $s !== ''));

        if ($shapes === []) {
            return null;
        }

        if (count($shapes) === 1) {
            return $shapes[0];
        }

        try {
            $points = [];
            foreach ($shapes as $shape) {
                foreach (Polyline::decode($shape, 6) as $point) {
                    $points[] = $point;
                }
            }

            return Polyline::encode($points, 6);
        } catch (\InvalidArgumentException) {
            return $shapes[0];
        }
    }
}
