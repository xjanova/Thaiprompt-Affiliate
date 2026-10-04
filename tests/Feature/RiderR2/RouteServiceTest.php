<?php

namespace Tests\Feature\RiderR2;

use App\Services\DeliveryFeeCalculator;
use App\Services\Routing\Polyline;
use App\Services\Routing\RouteResult;
use App\Services\Routing\RouteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * 🛵 ไรเดอร์รอบ 2 — ระยะทางตามถนน: Valhalla → Google Routes → เส้นตรง × road_factor
 *
 * เครื่อง dev/CI ไม่มี Valhalla จริง → ทุกเทสต์ใช้ Http::fake
 * ตรวจ: ผลจากแต่ละแหล่ง, cache, ตัดวงจร (หน้าชำระเงินห้ามรอบริการที่ตาย), คีย์ Google ไม่หลุดลง log
 */
class RouteServiceTest extends TestCase
{
    use RefreshDatabase;

    /** ร้าน (สีลม) → ลูกค้า (บางรัก) ~1.6 กม. เส้นตรง */
    private const FROM = [13.7291, 100.5210];

    private const TO = [13.7391, 100.5310];

    private const GOOGLE_KEY = 'AIzaTEST-google-routes-key-123';

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config([
            'services.valhalla.enabled' => true,
            'services.valhalla.url' => 'http://127.0.0.1:8002',
            'services.valhalla.timeout' => 2.5,
            'services.google_maps.api_key' => '',
        ]);
    }

    private function valhallaBody(float $km = 2.35, float $seconds = 410, ?string $shape = null): array
    {
        return [
            'trip' => [
                'status' => 0,
                'summary' => ['length' => $km, 'time' => $seconds],
                'legs' => [['shape' => $shape ?? Polyline::encode([self::FROM, [13.7340, 100.5250], self::TO], 6), 'summary' => ['length' => $km]]],
            ],
        ];
    }

    private function route(array $from = self::FROM, array $to = self::TO): RouteResult
    {
        return app(RouteService::class)->route($from[0], $from[1], $to[0], $to[1]);
    }

    public function test_valhalla_success_returns_road_distance_duration_and_polyline(): void
    {
        $shape = Polyline::encode([self::FROM, [13.7340, 100.5250], self::TO], 6);
        Http::fake(['127.0.0.1:8002/route' => Http::response($this->valhallaBody(2.35, 410, $shape))]);

        $result = $this->route();

        $this->assertSame('valhalla', $result->source);
        $this->assertSame(2.35, $result->distance_km);
        $this->assertSame(7, $result->duration_minutes, '410 วินาที ปัดขึ้น = 7 นาที');
        $this->assertSame($shape, $result->polyline);

        Http::assertSent(function (Request $request) {
            return $request->url() === 'http://127.0.0.1:8002/route'
                && $request['costing'] === 'motor_scooter'
                && $request['units'] === 'kilometers'
                && $request['locations'][0] === ['lat' => self::FROM[0], 'lon' => self::FROM[1]]
                && $request['locations'][1] === ['lat' => self::TO[0], 'lon' => self::TO[1]];
        });
    }

    public function test_result_is_cached_by_rounded_coordinates(): void
    {
        Http::fake(['127.0.0.1:8002/route' => Http::response($this->valhallaBody())]);

        $this->route();
        // ต่างกันหลักทศนิยมที่ 6 (~10 ซม.) → key เดียวกัน ไม่ยิงซ้ำ
        $second = $this->route([self::FROM[0] + 0.000001, self::FROM[1]]);

        $this->assertSame('valhalla', $second->source);
        Http::assertSentCount(1);
    }

    public function test_valhalla_timeout_falls_back_to_google_and_opens_circuit(): void
    {
        config(['services.google_maps.api_key' => self::GOOGLE_KEY]);
        $googlePoly = Polyline::encode([self::FROM, self::TO], 5);

        $valhallaCalls = 0;
        Http::fake(function (Request $request) use ($googlePoly, &$valhallaCalls) {
            if (str_contains($request->url(), '127.0.0.1:8002')) {
                $valhallaCalls++;

                throw new ConnectionException('cURL error 28: Operation timed out after 2500 milliseconds');
            }

            return Http::response(['routes' => [[
                'distanceMeters' => 4200,
                'duration' => '600s',
                'polyline' => ['encodedPolyline' => $googlePoly],
            ]]]);
        });

        $result = $this->route();

        $this->assertSame('google', $result->source);
        $this->assertSame(4.2, $result->distance_km);
        $this->assertSame(10, $result->duration_minutes);
        // polyline ของ Google (precision 5) ถูกแปลงเป็น precision 6 ตามสัญญากลาง
        $decoded = Polyline::decode((string) $result->polyline, 6);
        $this->assertEqualsWithDelta(self::FROM[0], $decoded[0][0], 0.00001);
        $this->assertEqualsWithDelta(self::TO[1], $decoded[1][1], 0.00001);

        Http::assertSent(fn (Request $r) => str_contains($r->url(), 'routes.googleapis.com')
            && $r->hasHeader('X-Goog-Api-Key', self::GOOGLE_KEY)
            && $r->hasHeader('X-Goog-FieldMask', 'routes.distanceMeters,routes.duration,routes.polyline.encodedPolyline')
            && $r['travelMode'] === 'TWO_WHEELER'
            && ! str_contains($r->url(), self::GOOGLE_KEY));

        // Valhalla ถูกตัดวงจร → คำขอถัดไป (พิกัดอื่น) ไม่ไปรอ Valhalla อีก
        $this->assertTrue(app(RouteService::class)->circuitOpen('valhalla'));
        $this->route([13.7000, 100.5000], [13.7100, 100.5100]);

        $this->assertSame(1, $valhallaCalls, 'Valhalla ที่หมดเวลาต้องถูกข้ามระหว่างตัดวงจร');
    }

    public function test_google_two_wheeler_without_route_retries_with_drive(): void
    {
        config(['services.valhalla.enabled' => false, 'services.google_maps.api_key' => self::GOOGLE_KEY]);

        Http::fake(function (Request $request) {
            if ($request['travelMode'] === 'TWO_WHEELER') {
                return Http::response([]); // {} = ไม่มีเส้นทางโหมดนี้
            }

            return Http::response(['routes' => [['distanceMeters' => 3000, 'duration' => '420s']]]);
        });

        $result = $this->route();

        $this->assertSame('google', $result->source);
        $this->assertSame(3.0, $result->distance_km);
        $this->assertNull($result->polyline);
        Http::assertSentCount(2);
    }

    public function test_everything_down_falls_back_to_haversine_times_road_factor(): void
    {
        Http::fake(['127.0.0.1:8002/route' => Http::response(['error' => 'boom'], 503)]);

        $result = $this->route();
        $straight = DeliveryFeeCalculator::haversineKm(self::FROM[0], self::FROM[1], self::TO[0], self::TO[1]);

        $this->assertSame('haversine', $result->source);
        $this->assertEqualsWithDelta(round($straight * 1.3, 2), $result->distance_km, 0.001);
        $this->assertNull($result->polyline);
        // ไม่มี Google key → ไม่ยิง Google เลย
        Http::assertNotSent(fn (Request $r) => str_contains($r->url(), 'googleapis'));
        $this->assertTrue(app(RouteService::class)->circuitOpen('valhalla'));
    }

    public function test_valhalla_no_route_does_not_open_circuit(): void
    {
        Http::fake(['127.0.0.1:8002/route' => Http::response(['error_code' => 442, 'error' => 'No path could be found for input'], 400)]);

        $this->assertSame('haversine', $this->route()->source);
        $this->assertFalse(app(RouteService::class)->circuitOpen('valhalla'), 'หาเส้นทางคู่เดียวไม่เจอ ≠ บริการล่ม');
    }

    public function test_bad_responses_open_circuit_only_after_three_failures(): void
    {
        Http::fake(['127.0.0.1:8002/route' => Http::response(['unexpected' => true])]);
        $service = app(RouteService::class);

        $service->route(13.70, 100.50, 13.71, 100.51);
        $service->route(13.72, 100.50, 13.73, 100.51);
        $this->assertFalse($service->circuitOpen('valhalla'));

        $service->route(13.74, 100.50, 13.75, 100.51);
        $this->assertTrue($service->circuitOpen('valhalla'));
    }

    public function test_road_distance_shorter_than_straight_line_is_rejected(): void
    {
        Http::fake(['127.0.0.1:8002/route' => Http::response($this->valhallaBody(0.2, 60))]);

        $result = $this->route();

        $this->assertSame('haversine', $result->source, 'ระยะถนนสั้นกว่าเส้นตรงเป็นไปไม่ได้ → ข้ามไปทางสำรอง');
    }

    public function test_same_point_does_not_call_any_provider(): void
    {
        Http::fake();

        $result = $this->route(self::FROM, self::FROM);

        $this->assertSame('haversine', $result->source);
        $this->assertSame(0.0, $result->distance_km);
        Http::assertNothingSent();
    }

    public function test_google_key_never_reaches_logs(): void
    {
        config(['services.valhalla.enabled' => false, 'services.google_maps.api_key' => self::GOOGLE_KEY]);
        $logs = [];
        Event::listen(MessageLogged::class, function (MessageLogged $e) use (&$logs) {
            $logs[] = $e->message.' '.json_encode($e->context);
        });

        Http::fake(['routes.googleapis.com/*' => Http::response(['error' => ['message' => 'API key not valid. key='.self::GOOGLE_KEY]], 403)]);

        $this->assertSame('haversine', $this->route()->source);
        $this->assertTrue(app(RouteService::class)->circuitOpen('google'), 'คีย์เสีย → พักใช้ Google');
        $this->assertNotEmpty($logs, 'ต้อง log ตอนตัดวงจร');
        foreach ($logs as $line) {
            $this->assertStringNotContainsString(self::GOOGLE_KEY, $line);
        }
    }

    public function test_fee_quote_uses_road_route_and_returns_contract_keys(): void
    {
        $shape = Polyline::encode([self::FROM, self::TO], 6);
        Http::fake(['127.0.0.1:8002/route' => Http::response($this->valhallaBody(5.0, 900, $shape))]);

        $quote = app(DeliveryFeeCalculator::class)->quote(self::FROM[0], self::FROM[1], self::TO[0], self::TO[1], null, now()->setTimezone('Asia/Bangkok')->setTime(9, 0));

        $this->assertSame(5.0, $quote['distance_km']);
        $this->assertSame(60.0, $quote['total_fee'], '30 + (5 − 2) × 10');
        $this->assertSame(48.0, $quote['rider_earnings']);
        $this->assertSame(20, $quote['estimated_duration_minutes'], 'เดินทาง 15 นาที + รับของ 5 นาที');
        $this->assertSame('valhalla', $quote['distance_source']);
        $this->assertSame($shape, $quote['route_polyline']);
        $this->assertSame(60.0, $quote['buyer_fee']);
        $this->assertSame(0.0, $quote['shop_bonus']);
        $this->assertSame(48.0, $quote['rider_total']);
    }

    public function test_polyline_round_trip_and_precision_conversion(): void
    {
        $points = [[13.729100, 100.521000], [13.739123, 100.531456], [13.70, 100.49]];

        $encoded = Polyline::encode($points, 6);
        $decoded = Polyline::decode($encoded, 6);

        foreach ($points as $i => $point) {
            $this->assertEqualsWithDelta($point[0], $decoded[$i][0], 0.0000011);
            $this->assertEqualsWithDelta($point[1], $decoded[$i][1], 0.0000011);
        }

        // ตัวอย่างมาตรฐานของ Google (precision 5)
        $this->assertSame('_p~iF~ps|U_ulLnnqC_mqNvxq`@', Polyline::encode([[38.5, -120.2], [40.7, -120.95], [43.252, -126.453]], 5));
        $this->assertSame(Polyline::encode([[38.5, -120.2], [40.7, -120.95]], 6), Polyline::convert(Polyline::encode([[38.5, -120.2], [40.7, -120.95]], 5), 5, 6));
    }
}
