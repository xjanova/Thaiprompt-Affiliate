<?php

namespace App\Services\Routing\Drivers;

use App\Services\Routing\Polyline;
use App\Services\Routing\RouteProviderException;
use App\Services\Routing\RouteResult;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Google Routes API v2 (computeRoutes) — สำรองเมื่อ Valhalla ใช้ไม่ได้
 *
 * ใช้เฉพาะเมื่อตั้ง config('services.google_maps.api_key') ไว้ (ว่าง = ข้ามไปคิดแบบเส้นตรง)
 * คีย์ส่งทาง header X-Goog-Api-Key เท่านั้น (ไม่อยู่ใน URL → ไม่หลุดลง log)
 * ลองโหมดจักรยานยนต์ (TWO_WHEELER) ก่อน ถ้าพื้นที่ไม่รองรับ/ไม่มีเส้นทาง → ลองโหมดรถยนต์ (DRIVE)
 * polyline ของ Google เป็น precision 5 → แปลงเป็น precision 6 ตามสัญญากลาง
 */
class GoogleRoutesDriver implements RouteDriver
{
    public const ENDPOINT = 'https://routes.googleapis.com/directions/v2:computeRoutes';

    public const FIELD_MASK = 'routes.distanceMeters,routes.duration,routes.polyline.encodedPolyline';

    public function name(): string
    {
        return RouteResult::SOURCE_GOOGLE;
    }

    public function enabled(): bool
    {
        return trim((string) config('services.google_maps.api_key', '')) !== '';
    }

    public function route(float $fromLat, float $fromLng, float $toLat, float $toLng): RouteResult
    {
        $lastError = null;

        foreach (['TWO_WHEELER', 'DRIVE'] as $mode) {
            try {
                return $this->compute($mode, $fromLat, $fromLng, $toLat, $toLng);
            } catch (RouteProviderException $e) {
                // บริการล่ม/คีย์เสีย → ลองโหมดอื่นก็ไม่ช่วย หยุดเลย
                if (in_array($e->kind, [RouteProviderException::UNAVAILABLE, RouteProviderException::AUTH], true)) {
                    throw $e;
                }
                $lastError = $e;
            }
        }

        throw $lastError ?? new RouteProviderException(RouteProviderException::NO_ROUTE, 'google: ไม่พบเส้นทาง');
    }

    /**
     * เรียก computeRoutes 1 ครั้งด้วยโหมดเดินทางที่กำหนด
     *
     * @throws RouteProviderException
     */
    private function compute(string $mode, float $fromLat, float $fromLng, float $toLat, float $toLng): RouteResult
    {
        $timeout = max(0.5, (float) config('services.google_maps.routes_timeout', 2.5));

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->withHeaders([
                    'X-Goog-Api-Key' => (string) config('services.google_maps.api_key'),
                    'X-Goog-FieldMask' => self::FIELD_MASK,
                ])
                ->connectTimeout(min(1.5, $timeout))
                ->timeout($timeout)
                ->post(self::ENDPOINT, [
                    'origin' => ['location' => ['latLng' => ['latitude' => round($fromLat, 6), 'longitude' => round($fromLng, 6)]]],
                    'destination' => ['location' => ['latLng' => ['latitude' => round($toLat, 6), 'longitude' => round($toLng, 6)]]],
                    'travelMode' => $mode,
                    'polylineEncoding' => 'ENCODED_POLYLINE',
                    'languageCode' => 'th',
                    'units' => 'METRIC',
                ]);
        } catch (ConnectionException) {
            throw new RouteProviderException(RouteProviderException::UNAVAILABLE, 'google: ต่อไม่ติดหรือหมดเวลา');
        }

        $this->assertStatus($response, $mode);

        $route = $response->json('routes.0');
        $meters = data_get($route, 'distanceMeters');
        $duration = data_get($route, 'duration');

        if (! is_array($route) || ! is_numeric($meters)) {
            // {} = ไม่มีเส้นทางสำหรับโหมดนี้ (เช่นพื้นที่ไม่รองรับจักรยานยนต์)
            throw new RouteProviderException(RouteProviderException::NO_ROUTE, 'google: ไม่มีเส้นทางโหมด '.$mode);
        }

        // duration มาเป็นสตริงวินาที เช่น "754s"
        $seconds = is_string($duration) && preg_match('/^(\d+(?:\.\d+)?)s$/', $duration, $m) ? (float) $m[1] : null;
        $distanceKm = round((float) $meters / 1000, 2);
        $minutes = $seconds !== null ? max(1, (int) ceil($seconds / 60)) : max(1, (int) ceil($distanceKm / 25 * 60));

        $polyline = null;
        $encoded = data_get($route, 'polyline.encodedPolyline');
        if (is_string($encoded) && $encoded !== '') {
            try {
                $polyline = Polyline::convert($encoded, 5, 6);
            } catch (\InvalidArgumentException) {
                $polyline = null;
            }
        }

        return new RouteResult($distanceKm, $minutes, $polyline, RouteResult::SOURCE_GOOGLE);
    }

    /**
     * แปลงสถานะ HTTP เป็นชนิดปัญหา
     *
     * @throws RouteProviderException
     */
    private function assertStatus(Response $response, string $mode): void
    {
        $status = $response->status();

        if ($response->successful()) {
            return;
        }

        if (in_array($status, [401, 403], true)) {
            throw new RouteProviderException(RouteProviderException::AUTH, 'google: คีย์ใช้ไม่ได้ (HTTP '.$status.')');
        }

        if ($status === 429 || $status >= 500) {
            throw new RouteProviderException(RouteProviderException::UNAVAILABLE, 'google: HTTP '.$status);
        }

        // 400/404 = โหมดนี้ใช้ไม่ได้กับพิกัดคู่นี้ → ลองโหมดถัดไป
        throw new RouteProviderException(RouteProviderException::NO_ROUTE, 'google: HTTP '.$status.' โหมด '.$mode);
    }
}
