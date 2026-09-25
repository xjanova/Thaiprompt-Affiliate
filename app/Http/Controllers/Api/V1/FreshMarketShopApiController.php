<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\FreshMarketException;
use App\Http\Controllers\Controller;
use App\Models\FreshMarketSeller;
use App\Models\FreshMarketShopFollower;
use App\Models\User;
use App\Services\FreshMarketShopPresenceService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * API ร้านตลาดสด: หน้าร้าน + ตำแหน่งร้านสด + ติดตามร้าน + เจ้าของร้านเปิด/ปิด/ส่งตำแหน่ง (รถเข็น/ตลาดนัด)
 *
 * ผู้ซื้อ (public):  GET /fresh-market/shops/{id} · GET /fresh-market/shops/{id}/location
 * ผู้ซื้อ (sanctum): POST|DELETE /fresh-market/shops/{id}/follow · GET /fresh-market/me/followed-shops
 * เจ้าของร้าน:      GET /fresh-market/seller/presence · POST /fresh-market/seller/open
 *                    POST /fresh-market/seller/location (6 ครั้ง/นาที) · POST /fresh-market/seller/close
 *
 * รูปแบบตอบกลับ {success, message, data} · ผิดพลาดมี code (UPPER_SNAKE) + HTTP status ที่ตรง
 * ตำแหน่งร้านเคลื่อนที่เปิดเผยเฉพาะตอนร้านเปิดเท่านั้น
 */
class FreshMarketShopApiController extends Controller
{
    public function __construct(private readonly FreshMarketShopPresenceService $presence) {}

    // ===== ผู้ซื้อ =====

    /**
     * GET /fresh-market/shops/{id} — หน้าร้าน + สถานะเปิด/ปิด + ตำแหน่ง (ตอนเปิด) + สินค้า
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $viewer = $this->viewer($request);
        $seller = FreshMarketSeller::find($id);
        $isOwner = $seller && $viewer && (int) $seller->user_id === (int) $viewer->id;

        if (! $seller || (! $seller->isVisibleToBuyers() && ! $isOwner)) {
            return $this->error('SHOP_NOT_FOUND', 'ไม่พบร้านนี้', 404);
        }

        $listings = $seller->listings()
            ->active()
            ->inStock()
            ->with('category:id,name,icon')
            ->withCount('optionGroups')
            ->latest()
            ->limit(50)
            ->get();

        $profile = $this->presence->shopProfile($seller, $viewer);
        $canOrder = (bool) $profile['can_order'] && ! $isOwner;

        return $this->ok(array_merge($profile, [
            'is_owner' => $isOwner,
            'listings' => $listings->map(fn ($l) => [
                'id' => (int) $l->id,
                'slug' => $l->slug,
                'title' => $l->title,
                'description' => $l->description,
                'price' => (float) $l->price,
                'compare_at_price' => $l->compare_at_price !== null ? (float) $l->compare_at_price : null,
                'unit' => $l->unit,
                'main_image_url' => $l->primary_image,
                'is_organic' => (bool) $l->is_organic,
                'category' => $l->category?->name,
                'track_stock' => $l->tracksStock(),
                'max_order_quantity' => (int) $l->max_order_quantity,
                'has_options' => (int) ($l->option_groups_count ?? 0) > 0,
                'can_order' => $canOrder,
            ])->values(),
        ]), 'ดึงข้อมูลร้านสำเร็จ');
    }

    /**
     * GET /fresh-market/shops/nearby?lat&lng&radius — ร้านที่เปิดอยู่ใกล้คุณ (รวมรถเข็น/ตลาดนัดที่เปิดอยู่ตอนนี้)
     *
     * ร้านปิดไม่ถูกส่งออก (ไม่เปิดเผยตำแหน่ง) · เรียงใกล้ → ไกล · สูงสุด 30 ร้าน
     */
    public function nearby(Request $request): JsonResponse
    {
        $data = $this->validateRequest($request, [
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:0.5|max:50',
        ], [
            'lat.required' => 'กรุณาส่งตำแหน่งของคุณ',
            'lng.required' => 'กรุณาส่งตำแหน่งของคุณ',
            'lat.*' => 'ตำแหน่งไม่ถูกต้อง',
            'lng.*' => 'ตำแหน่งไม่ถูกต้อง',
            'radius.*' => 'รัศมีค้นหาต้องอยู่ระหว่าง 0.5-50 กม.',
        ]);

        return $this->handle(function () use ($request, $data) {
            $shops = $this->presence->nearbyShops(
                (float) $data['lat'],
                (float) $data['lng'],
                isset($data['radius']) ? (float) $data['radius'] : 10.0,
                $this->viewer($request)
            );

            return $this->ok([
                'shops' => $shops,
                'count' => count($shops),
                'radius_km' => isset($data['radius']) ? (float) $data['radius'] : 10.0,
            ], count($shops) > 0 ? 'ดึงร้านใกล้คุณสำเร็จ' : 'ยังไม่มีร้านที่เปิดอยู่ใกล้คุณ');
        });
    }

    /**
     * GET /fresh-market/shops/{id}/location — ตำแหน่งร้านตอนนี้ (สำหรับ poll บนหน้าร้าน)
     *
     * ร้านปิด → 200 is_open=false, location=null (แอปหยุด poll แล้วแสดงปุ่มติดตามร้าน)
     */
    public function location(Request $request, int $id): JsonResponse
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
            'server_time' => now()->toIso8601String(),
        ], match (true) {
            $location !== null => 'ดึงตำแหน่งร้านสำเร็จ',
            $open => 'ร้านยังไม่ได้ปักหมุดตำแหน่ง',
            default => 'ร้านปิดอยู่',
        });
    }

    /**
     * POST /fresh-market/shops/{id}/follow — ติดตามร้าน (รับแจ้งเตือนเมื่อร้านเปิด)
     */
    public function follow(Request $request, int $id): JsonResponse
    {
        return $this->handle(function () use ($request, $id) {
            $seller = FreshMarketSeller::find($id);

            if (! $seller) {
                return $this->error('SHOP_NOT_FOUND', 'ไม่พบร้านนี้', 404);
            }

            $this->presence->follow($request->user(), $seller);

            return $this->ok([
                'shop_id' => (int) $seller->id,
                'is_following' => true,
                'notify' => true,
                'followers_count' => $this->presence->followersCount($seller),
            ], 'ติดตามร้านแล้ว ร้านเปิดเมื่อไรจะแจ้งให้ทราบ');
        });
    }

    /**
     * DELETE /fresh-market/shops/{id}/follow — เลิกติดตามร้าน
     */
    public function unfollow(Request $request, int $id): JsonResponse
    {
        return $this->handle(function () use ($request, $id) {
            $seller = FreshMarketSeller::find($id);

            if (! $seller) {
                return $this->error('SHOP_NOT_FOUND', 'ไม่พบร้านนี้', 404);
            }

            $this->presence->unfollow($request->user(), $seller);

            return $this->ok([
                'shop_id' => (int) $seller->id,
                'is_following' => false,
                'followers_count' => $this->presence->followersCount($seller),
            ], 'เลิกติดตามร้านแล้ว');
        });
    }

    /**
     * GET /fresh-market/me/followed-shops?page&per_page — ร้านที่ฉันติดตาม (ร้านที่เปิดอยู่ขึ้นก่อน)
     */
    public function followedShops(Request $request): JsonResponse
    {
        $perPage = max(1, min($request->integer('per_page', 20), 50));

        $page = FreshMarketShopFollower::where('user_id', $request->user()->id)
            ->whereHas('seller', fn ($q) => $q->where('is_active', true)->where('is_suspended', false))
            ->with('seller')
            ->latest('id')
            ->paginate($perPage);

        $items = collect($page->items())
            ->map(fn (FreshMarketShopFollower $row) => array_merge(
                $this->presence->shopCard($row->seller, null, true),
                [
                    'notify' => (bool) $row->notify,
                    'followed_at' => $row->created_at?->toIso8601String(),
                ]
            ))
            ->sortByDesc(fn ($shop) => $shop['is_open'] ? 1 : 0)
            ->values();

        return response()->json([
            'success' => true,
            'message' => 'ดึงร้านที่ติดตามสำเร็จ',
            'data' => $items,
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    // ===== เจ้าของร้าน =====

    /**
     * GET /fresh-market/seller/presence — สถานะหน้าร้านของฉัน
     */
    public function sellerPresence(Request $request): JsonResponse
    {
        $seller = $this->sellerOrFail($request);

        return $this->ok($this->ownerPayload($seller), 'ดึงสถานะร้านสำเร็จ');
    }

    /**
     * POST /fresh-market/seller/open — "เปิดร้านที่นี่วันนี้"
     *
     * body: latitude, longitude (บังคับสำหรับร้านเคลื่อนที่), location_label?, closes_at? (ISO หรือ "HH:MM"),
     *       live_location_sharing? (bool — แอปส่ง POST /seller/location ทุก ~30 วินาทีระหว่างเปิด)
     */
    public function open(Request $request): JsonResponse
    {
        $seller = $this->sellerOrFail($request);

        $data = $this->validateRequest($request, [
            'latitude' => 'nullable|required_with:longitude|numeric|between:-90,90',
            'longitude' => 'nullable|required_with:latitude|numeric|between:-180,180',
            'location_label' => 'nullable|string|max:150',
            'closes_at' => 'nullable|string|max:40',
            'live_location_sharing' => 'nullable|boolean',
        ], [
            'latitude.required_with' => 'กรุณาส่งพิกัดให้ครบทั้งละติจูดและลองจิจูด',
            'longitude.required_with' => 'กรุณาส่งพิกัดให้ครบทั้งละติจูดและลองจิจูด',
        ]);

        return $this->handle(function () use ($seller, $data) {
            $result = $this->presence->open($seller, $data);

            return $this->ok(array_merge($this->ownerPayload($result['seller']), [
                'just_opened' => $result['just_opened'],
                'pickups_updated' => $result['pickups_updated'],
            ]), $result['just_opened']
                ? 'เปิดร้านแล้ว ผู้ติดตามร้านจะได้รับแจ้งเตือน'
                : 'อัปเดตร้านเรียบร้อยแล้ว');
        });
    }

    /**
     * POST /fresh-market/seller/location — ตำแหน่งสดของร้านเคลื่อนที่ (ระหว่างเปิดร้าน, ไม่เกิน 6 ครั้ง/นาที)
     */
    public function sellerLocation(Request $request): JsonResponse
    {
        $seller = $this->sellerOrFail($request);

        $data = $this->validateRequest($request, [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'location_label' => 'nullable|string|max:150',
            'accuracy' => 'nullable|numeric',
        ]);

        return $this->handle(function () use ($seller, $data) {
            $result = $this->presence->updateLocation($seller, $data['latitude'], $data['longitude'], $data['location_label'] ?? null);
            $fresh = $result['seller'];

            return $this->ok([
                'is_open' => $fresh->isOpenNow(),
                'location' => $fresh->publicLocation(),
                'location_updated_at' => $fresh->location_updated_at?->toIso8601String(),
                'pickups_updated' => $result['pickups_updated'],
                'next_send_seconds' => 30,
            ], 'อัปเดตตำแหน่งร้านแล้ว');
        });
    }

    /**
     * POST /fresh-market/seller/close — ปิดร้าน (ออเดอร์ที่รับไว้แล้วยังทำต่อได้)
     */
    public function close(Request $request): JsonResponse
    {
        $seller = $this->sellerOrFail($request);

        return $this->handle(function () use ($seller) {
            $result = $this->presence->close($seller);

            $message = $result['active_orders'] > 0
                ? 'ปิดร้านแล้ว — ยังมีออเดอร์ค้าง '.$result['active_orders'].' รายการ กรุณาจัดการให้เสร็จ'
                : 'ปิดร้านแล้ว';

            return $this->ok(array_merge($this->ownerPayload($result['seller']), [
                'was_open' => $result['was_open'],
                'active_orders' => $result['active_orders'],
            ]), $message);
        });
    }

    // ===== Helpers =====

    /**
     * สถานะหน้าร้านสำหรับเจ้าของร้าน
     *
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

    /**
     * ผู้ใช้ที่ส่ง Bearer token มา (เส้นทาง public ก็รู้ได้ว่าใครดู เพื่อบอก is_following)
     */
    protected function viewer(Request $request): ?User
    {
        try {
            $user = $request->user('sanctum');
        } catch (\Throwable $e) {
            $user = null;
        }

        return $user instanceof User ? $user : null;
    }

    protected function sellerOrFail(Request $request): FreshMarketSeller
    {
        $seller = FreshMarketSeller::where('user_id', $request->user()->id)->first();

        if (! $seller) {
            throw new HttpResponseException($this->error('NOT_SELLER', 'คุณยังไม่ได้สมัครเป็นผู้ขาย', 403));
        }

        return $seller;
    }

    protected function validateRequest(Request $request, array $rules, array $messages = []): array
    {
        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'code' => 'VALIDATION_ERROR',
                'errors' => $validator->errors(),
            ], 422));
        }

        return $validator->validated();
    }

    /**
     * FreshMarketException → JSON ภาษาไทย · exception อื่น log แล้วตอบข้อความกลาง
     */
    protected function handle(callable $callback): JsonResponse
    {
        try {
            return $callback();
        } catch (FreshMarketException $e) {
            return $this->error($e->errorCode(), $e->getMessage(), $e->httpStatus());
        } catch (HttpResponseException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('FreshMarketShopAPI: ทำรายการล้มเหลว', [
                'user_id' => auth()->id(),
                'path' => request()->path(),
                'error' => $e->getMessage(),
            ]);

            return $this->error('SERVER_ERROR', 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง', 500);
        }
    }

    protected function ok(mixed $data, string $message = 'สำเร็จ', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    protected function error(string $code, string $message, int $status): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'code' => $code,
        ], $status);
    }
}
