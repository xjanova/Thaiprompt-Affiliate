<?php

namespace App\Http\Controllers\FreshMarket;

use App\Exceptions\DeliveryTrackingException;
use App\Exceptions\FreshMarketException;
use App\Http\Controllers\Controller;
use App\Models\FreshMarketSeller;
use App\Models\FreshMarketShopFollower;
use App\Services\DeliveryTrackingService;
use App\Services\FreshMarketShopPresenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * หน้าเว็บ (session) ของร้านรถเข็น/ตลาดนัด + ติดตามร้าน + ติดตามไรเดอร์ของผู้ซื้อ
 *
 * ทุก action ตอบ JSON เมื่อเรียกแบบ AJAX (Accept: application/json + X-CSRF-TOKEN)
 * ส่งฟอร์มปกติ → redirect กลับพร้อม flash success/error
 * รูปแบบ JSON เหมือน API แอป: {success, message, data} / ผิดพลาด {success:false, code, message}
 */
class ShopPresenceController extends Controller
{
    public function __construct(
        private readonly FreshMarketShopPresenceService $presence,
        private readonly DeliveryTrackingService $tracking,
    ) {}

    // ===== เจ้าของร้าน =====

    /**
     * GET /taladsod/seller/presence (taladsod.seller.presence)
     */
    public function sellerPresence(Request $request): JsonResponse
    {
        $seller = $this->sellerOf($request);

        if (! $seller) {
            return $this->error('NOT_SELLER', 'คุณยังไม่ได้สมัครเป็นผู้ขาย', 403);
        }

        return $this->ok($this->ownerPayload($seller), 'ดึงสถานะร้านสำเร็จ');
    }

    /**
     * POST /taladsod/seller/open (taladsod.seller.open) — latitude, longitude, location_label, closes_at, live_location_sharing
     */
    public function sellerOpen(Request $request): JsonResponse|RedirectResponse
    {
        $seller = $this->sellerOf($request);

        if (! $seller) {
            return $this->fail($request, 'NOT_SELLER', 'คุณยังไม่ได้สมัครเป็นผู้ขาย', 403);
        }

        $validator = Validator::make($request->all(), [
            'latitude' => 'nullable|required_with:longitude|numeric|between:-90,90',
            'longitude' => 'nullable|required_with:latitude|numeric|between:-180,180',
            'location_label' => 'nullable|string|max:150',
            'closes_at' => 'nullable|string|max:40',
            'live_location_sharing' => 'nullable|boolean',
        ], [
            'latitude.required_with' => 'กรุณาปักหมุดตำแหน่งร้านให้ครบ',
            'longitude.required_with' => 'กรุณาปักหมุดตำแหน่งร้านให้ครบ',
        ]);

        if ($validator->fails()) {
            return $this->fail($request, 'VALIDATION_ERROR', $validator->errors()->first(), 422);
        }

        return $this->run($request, function () use ($seller, $validator) {
            $result = $this->presence->open($seller, $validator->validated());

            return [
                array_merge($this->ownerPayload($result['seller']), [
                    'just_opened' => $result['just_opened'],
                    'pickups_updated' => $result['pickups_updated'],
                ]),
                $result['just_opened'] ? 'เปิดร้านแล้ว ผู้ติดตามร้านจะได้รับแจ้งเตือน' : 'อัปเดตร้านเรียบร้อยแล้ว',
            ];
        });
    }

    /**
     * POST /taladsod/seller/location (taladsod.seller.location) — ตำแหน่งสดจากเบราว์เซอร์ของร้าน (AJAX)
     */
    public function sellerLocation(Request $request): JsonResponse|RedirectResponse
    {
        $seller = $this->sellerOf($request);

        if (! $seller) {
            return $this->fail($request, 'NOT_SELLER', 'คุณยังไม่ได้สมัครเป็นผู้ขาย', 403);
        }

        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'location_label' => 'nullable|string|max:150',
        ]);

        if ($validator->fails()) {
            return $this->fail($request, 'VALIDATION_ERROR', $validator->errors()->first(), 422);
        }

        $data = $validator->validated();

        return $this->run($request, function () use ($seller, $data) {
            $result = $this->presence->updateLocation($seller, $data['latitude'], $data['longitude'], $data['location_label'] ?? null);
            $fresh = $result['seller'];

            return [[
                'is_open' => $fresh->isOpenNow(),
                'location' => $fresh->publicLocation(),
                'location_updated_at' => $fresh->location_updated_at?->toIso8601String(),
                'pickups_updated' => $result['pickups_updated'],
                'next_send_seconds' => 30,
            ], 'อัปเดตตำแหน่งร้านแล้ว'];
        });
    }

    /**
     * POST /taladsod/seller/close (taladsod.seller.close)
     */
    public function sellerClose(Request $request): JsonResponse|RedirectResponse
    {
        $seller = $this->sellerOf($request);

        if (! $seller) {
            return $this->fail($request, 'NOT_SELLER', 'คุณยังไม่ได้สมัครเป็นผู้ขาย', 403);
        }

        return $this->run($request, function () use ($seller) {
            $result = $this->presence->close($seller);

            return [
                array_merge($this->ownerPayload($result['seller']), [
                    'was_open' => $result['was_open'],
                    'active_orders' => $result['active_orders'],
                ]),
                $result['active_orders'] > 0
                    ? 'ปิดร้านแล้ว — ยังมีออเดอร์ค้าง '.$result['active_orders'].' รายการ กรุณาจัดการให้เสร็จ'
                    : 'ปิดร้านแล้ว',
            ];
        });
    }

    /**
     * POST /taladsod/seller/mobile-mode (taladsod.seller.mobile-mode) — is_mobile = 1|0
     */
    public function sellerMobileMode(Request $request): JsonResponse|RedirectResponse
    {
        $seller = $this->sellerOf($request);

        if (! $seller) {
            return $this->fail($request, 'NOT_SELLER', 'คุณยังไม่ได้สมัครเป็นผู้ขาย', 403);
        }

        $validator = Validator::make($request->all(), ['is_mobile' => 'required|boolean']);

        if ($validator->fails()) {
            return $this->fail($request, 'VALIDATION_ERROR', 'กรุณาเลือกประเภทร้าน', 422);
        }

        return $this->run($request, function () use ($seller, $request) {
            $fresh = $this->presence->setMobile($seller, $request->boolean('is_mobile'));

            return [
                $this->ownerPayload($fresh),
                $fresh->isMobileShop()
                    ? 'ตั้งเป็นร้านเคลื่อนที่แล้ว กด "เปิดร้านที่นี่วันนี้" เมื่อพร้อมขาย'
                    : 'ตั้งเป็นร้านประจำที่แล้ว ลูกค้าจะเห็นตำแหน่งตามที่อยู่ร้าน',
            ];
        });
    }

    // ===== ผู้ซื้อ =====

    /**
     * GET /taladsod/shop/{id}/location (taladsod.shop.location) — public, ใช้ poll บนหน้าร้าน
     */
    public function shopLocation(int $id): JsonResponse
    {
        $seller = FreshMarketSeller::find($id);

        if (! $seller || ! $seller->isVisibleToBuyers()) {
            return $this->error('SHOP_NOT_FOUND', 'ไม่พบร้านนี้', 404);
        }

        $location = $seller->publicLocation();
        $open = $seller->isOpenNow();

        return $this->ok([
            'shop_id' => (int) $seller->id,
            'is_open' => $open,
            'is_mobile' => $seller->isMobileShop(),
            'location' => $location,
            'closes_at' => $open ? $seller->closes_at?->toIso8601String() : null,
            'closed_message' => $open ? null : FreshMarketSeller::CLOSED_MESSAGE,
            'poll_interval_seconds' => ($location['is_live'] ?? false) ? 30 : 120,
        ], $location ? 'ดึงตำแหน่งร้านสำเร็จ' : ($open ? 'ร้านยังไม่ได้ปักหมุดตำแหน่ง' : 'ร้านปิดอยู่'));
    }

    /**
     * POST /taladsod/shop/{id}/follow (taladsod.shop.follow)
     */
    public function follow(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $seller = FreshMarketSeller::find($id);

        if (! $seller) {
            return $this->fail($request, 'SHOP_NOT_FOUND', 'ไม่พบร้านนี้', 404);
        }

        return $this->run($request, function () use ($request, $seller) {
            $this->presence->follow($request->user(), $seller);

            return [[
                'shop_id' => (int) $seller->id,
                'is_following' => true,
                'followers_count' => $this->presence->followersCount($seller),
            ], 'ติดตามร้านแล้ว ร้านเปิดเมื่อไรจะแจ้งให้ทราบ'];
        });
    }

    /**
     * DELETE /taladsod/shop/{id}/follow (taladsod.shop.unfollow)
     */
    public function unfollow(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $seller = FreshMarketSeller::find($id);

        if (! $seller) {
            return $this->fail($request, 'SHOP_NOT_FOUND', 'ไม่พบร้านนี้', 404);
        }

        return $this->run($request, function () use ($request, $seller) {
            $this->presence->unfollow($request->user(), $seller);

            return [[
                'shop_id' => (int) $seller->id,
                'is_following' => false,
                'followers_count' => $this->presence->followersCount($seller),
            ], 'เลิกติดตามร้านแล้ว'];
        });
    }

    /**
     * GET /taladsod/me/followed-shops (taladsod.followed-shops) — JSON รายการร้านที่ติดตาม
     */
    public function followedShops(Request $request): JsonResponse
    {
        $rows = FreshMarketShopFollower::where('user_id', $request->user()->id)
            ->whereHas('seller', fn ($q) => $q->where('is_active', true)->where('is_suspended', false))
            ->with('seller')
            ->latest('id')
            ->limit(100)
            ->get();

        $shops = $rows->map(fn (FreshMarketShopFollower $row) => array_merge(
            $this->presence->shopCard($row->seller, null, true),
            [
                'notify' => (bool) $row->notify,
                'followed_at' => $row->created_at?->toIso8601String(),
                'url' => route('taladsod.seller', $row->seller_id),
            ]
        ))->sortByDesc(fn ($shop) => $shop['is_open'] ? 1 : 0)->values();

        return $this->ok($shops, 'ดึงร้านที่ติดตามสำเร็จ');
    }

    /**
     * GET /taladsod/delivery/{source}/{id}/rider-location (taladsod.delivery.rider-location)
     */
    public function deliveryRiderLocation(Request $request, string $source, int $id): JsonResponse
    {
        try {
            $order = $this->tracking->findBuyerOrder($request->user(), $source, $id);

            return $this->ok($this->tracking->riderLocation($order, $source), 'ดึงตำแหน่งไรเดอร์สำเร็จ');
        } catch (DeliveryTrackingException $e) {
            return $this->error($e->errorCode(), $e->getMessage(), $e->httpStatus());
        } catch (\Throwable $e) {
            Log::error('DeliveryTrackingWeb: rider location failed', ['user_id' => $request->user()?->id, 'error' => $e->getMessage()]);

            return $this->error('SERVER_ERROR', 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง', 500);
        }
    }

    /**
     * POST /taladsod/delivery/{source}/{id}/share-location (taladsod.delivery.share-location) — share, latitude?, longitude?
     */
    public function deliveryShareLocation(Request $request, string $source, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'share' => 'required|boolean',
            'latitude' => 'nullable|required_with:longitude|numeric|between:-90,90',
            'longitude' => 'nullable|required_with:latitude|numeric|between:-180,180',
        ], [
            'share.required' => 'กรุณาระบุว่าจะเปิดหรือปิดการแชร์ตำแหน่ง',
        ]);

        if ($validator->fails()) {
            return $this->error('VALIDATION_ERROR', $validator->errors()->first(), 422);
        }

        $data = $validator->validated();

        try {
            $order = $this->tracking->findBuyerOrder($request->user(), $source, $id);
            $result = $this->tracking->setSharing(
                $order,
                filter_var($data['share'], FILTER_VALIDATE_BOOLEAN),
                $data['latitude'] ?? null,
                $data['longitude'] ?? null
            );

            return $this->ok($result, $result['sharing']
                ? 'กำลังแชร์ตำแหน่งให้ไรเดอร์ (หยุดอัตโนมัติเมื่อส่งของเสร็จ)'
                : 'หยุดแชร์ตำแหน่งแล้ว');
        } catch (DeliveryTrackingException $e) {
            return $this->error($e->errorCode(), $e->getMessage(), $e->httpStatus());
        } catch (\Throwable $e) {
            Log::error('DeliveryTrackingWeb: share location failed', ['user_id' => $request->user()?->id, 'error' => $e->getMessage()]);

            return $this->error('SERVER_ERROR', 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง', 500);
        }
    }

    // ===== Helpers =====

    /**
     * @return array<string, mixed>
     */
    protected function ownerPayload(FreshMarketSeller $seller): array
    {
        return array_merge($seller->presencePayload(true), [
            'shop_id' => (int) $seller->id,
            'shop_name' => $seller->shop_name,
            'has_fixed_location' => $seller->hasPickupLocation(),
            'followers_count' => $this->presence->followersCount($seller),
            'live_send_interval_seconds' => 30,
            'live_stale_minutes' => FreshMarketSeller::LIVE_LOCATION_STALE_MINUTES,
        ]);
    }

    protected function sellerOf(Request $request): ?FreshMarketSeller
    {
        return FreshMarketSeller::where('user_id', $request->user()->id)->first();
    }

    /**
     * รัน action ที่คืน [data, message] → JSON หรือ redirect กลับพร้อม flash
     *
     * @param  callable(): array{0: mixed, 1: string}  $callback
     */
    protected function run(Request $request, callable $callback): JsonResponse|RedirectResponse
    {
        try {
            [$data, $message] = $callback();
        } catch (FreshMarketException $e) {
            return $this->fail($request, $e->errorCode(), $e->getMessage(), $e->httpStatus());
        } catch (\Throwable $e) {
            Log::error('FreshMarketShopWeb: action failed', [
                'user_id' => $request->user()?->id,
                'path' => $request->path(),
                'error' => $e->getMessage(),
            ]);

            return $this->fail($request, 'SERVER_ERROR', 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง', 500);
        }

        if ($request->expectsJson()) {
            return $this->ok($data, $message);
        }

        return back()->with('success', $message);
    }

    protected function fail(Request $request, string $code, string $message, int $status): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return $this->error($code, $message, $status);
        }

        return back()->with('error', $message)->withInput();
    }

    protected function ok(mixed $data, string $message): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ]);
    }

    protected function error(string $code, string $message, int $status): JsonResponse
    {
        return response()->json([
            'success' => false,
            'code' => $code,
            'message' => $message,
        ], $status);
    }
}
