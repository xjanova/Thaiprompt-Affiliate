<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\RiderSocialException;
use App\Http\Controllers\Api\V1\Concerns\SellerAppResponses;
use App\Http\Controllers\Controller;
use App\Services\DeliveryFeeCalculator;
use App\Services\Rider\RiderSocialService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * หัวใจไรเดอร์ / ไรเดอร์คนโปรด / ไรเดอร์ใกล้ฉัน (ไรเดอร์รอบ 2, 2026-10-04 — เลน social)
 *
 * GET  /api/v1/riders/nearby?lat=&lng=          ไรเดอร์ออนไลน์รอบตัว (ตำแหน่งเบลอ ~rider.nearby_fuzz_m)
 * GET  /api/v1/riders/favorites                 ไรเดอร์ที่ฉันเคยให้หัวใจ (หัวใจมากสุดก่อน)
 * GET  /api/v1/riders/{id}                      การ์ดไรเดอร์
 * POST /api/v1/orders/{source}/{id}/heart       ให้หัวใจไรเดอร์ของออเดอร์ (source = shop | fresh-market)
 *
 * ❗ ไม่ส่งพิกัดจริง / เบอร์โทร / ทะเบียนเต็มของไรเดอร์ออกทางนี้ · ไม่ส่งข้อความ exception ดิบให้แอป
 */
class RiderSocialApiController extends Controller
{
    use SellerAppResponses;

    public function __construct(private readonly RiderSocialService $social) {}

    /**
     * GET /riders/nearby?lat=&lng= (รับ latitude/longitude ได้ด้วย)
     */
    public function nearby(Request $request): JsonResponse
    {
        foreach (['latitude' => 'lat', 'longitude' => 'lng'] as $from => $to) {
            if ($request->filled($from) && ! $request->filled($to)) {
                $request->merge([$to => $request->input($from)]);
            }
        }

        $data = $this->validateOrFail($request, [
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ], [
            'lat.required' => 'กรุณาเปิดตำแหน่งเพื่อดูไรเดอร์ใกล้คุณ',
            'lng.required' => 'กรุณาเปิดตำแหน่งเพื่อดูไรเดอร์ใกล้คุณ',
            'lat.numeric' => 'พิกัดไม่ถูกต้อง',
            'lng.numeric' => 'พิกัดไม่ถูกต้อง',
            'lat.between' => 'พิกัดไม่ถูกต้อง',
            'lng.between' => 'พิกัดไม่ถูกต้อง',
        ]);

        if ($data instanceof JsonResponse) {
            return $data;
        }

        if (! DeliveryFeeCalculator::isValidCoordinate($data['lat'], $data['lng'])) {
            return $this->fail('VALIDATION_ERROR', 'พิกัดไม่ถูกต้อง', 422);
        }

        return $this->guard('nearby', fn () => $this->ok(
            $this->social->nearby($request->user(), (float) $data['lat'], (float) $data['lng']),
            'ดึงไรเดอร์ใกล้คุณสำเร็จ'
        ));
    }

    /**
     * GET /riders/favorites
     */
    public function favorites(Request $request): JsonResponse
    {
        return $this->guard('favorites', fn () => $this->ok(
            $this->social->favorites($request->user()),
            'ดึงไรเดอร์คนโปรดสำเร็จ'
        ));
    }

    /**
     * GET /riders/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        return $this->guard('show', fn () => $this->ok(
            ['rider' => $this->social->card($request->user(), $id)],
            'ดึงข้อมูลไรเดอร์สำเร็จ'
        ));
    }

    /**
     * POST /orders/{source}/{id}/heart — 1 ดวงต่องานที่ส่งสำเร็จ (กดซ้ำได้ผลเดิม already = true)
     */
    public function heart(Request $request, string $source, int $id): JsonResponse
    {
        return $this->guard('heart', function () use ($request, $source, $id) {
            $result = $this->social->giveHeart($request->user(), $source, $id);

            return $this->ok($result, $result['already'] ? 'คุณให้หัวใจงานนี้ไปแล้ว' : 'ให้หัวใจไรเดอร์แล้ว ขอบคุณที่ให้กำลังใจ');
        });
    }

    /**
     * ครอบทุก action: ข้อผิดพลาดทางธุรกิจ → JSON ภาษาไทย · อื่นๆ log แล้วตอบข้อความกลาง
     *
     * @param  callable(): JsonResponse  $callback
     */
    private function guard(string $action, callable $callback): JsonResponse
    {
        try {
            return $callback();
        } catch (RiderSocialException $e) {
            return $this->fail($e->errorCode, $e->getMessage(), $e->httpStatus, $e->data);
        } catch (\Throwable $e) {
            Log::error('RiderSocialApi: '.$action.' failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);

            return $this->fail('SERVER_ERROR', 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง', 500);
        }
    }
}
