<?php

namespace App\Services;

use App\Exceptions\FreshMarketException;
use App\Jobs\NotifyFreshMarketShopFollowersJob;
use App\Models\FreshMarketOrder;
use App\Models\FreshMarketSeller;
use App\Models\FreshMarketShopFollower;
use App\Models\RiderJob;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * FreshMarketShopPresenceService - ร้านรถเข็น/ตลาดนัด (ร้านเคลื่อนที่) + เปิด/ปิดร้าน + ผู้ติดตามร้าน
 *
 * เจ้าของร้าน:
 *   - open()            "เปิดร้านที่นี่วันนี้" — ตั้งตำแหน่ง + เปิดร้าน (+ ส่งตำแหน่งสดได้) → แจ้งผู้ติดตาม
 *   - updateLocation()  ตำแหน่งสดจากเครื่องร้าน (~ทุก 30 วินาที ระหว่างเปิด) → ย้ายจุดรับของของงานไรเดอร์ที่ยังไม่รับของ
 *   - close()           "ปิดร้าน"
 *   - closeStaleShops() คำสั่งกวาด: เลยเวลาปิด / ตำแหน่งสดเงียบเกิน 30 นาที → ปิดร้านให้อัตโนมัติ
 *
 * ผู้ซื้อ:
 *   - follow()/unfollow() ติดตามร้าน → ร้านเปิดเมื่อไรได้แจ้งเตือน (ในแอป + Expo push)
 *     สูงสุด 1 ครั้งต่อร้านต่อคนทุก 3 ชั่วโมง · ❌ ไม่ใช้ LINE push (โควต้า 300/เดือน)
 *
 * ข้อผิดพลาดที่ผู้ใช้ต้องรู้โยนเป็น FreshMarketException (ข้อความไทย + code) เท่านั้น
 */
class FreshMarketShopPresenceService
{
    /** ร้านเคลื่อนที่ที่ไม่ได้ตั้งเวลาปิด → ปิดให้อัตโนมัติหลังกี่ชั่วโมง (กันลืมกดปิดแล้วค้างเปิดข้ามวัน) */
    public const DEFAULT_MOBILE_OPEN_HOURS = 12;

    /** ตั้งเวลาปิดล่วงหน้าได้ไม่เกินกี่ชั่วโมง */
    public const MAX_OPEN_HOURS = 24;

    /** แจ้งผู้ติดตามเรื่องร้านเปิดได้ 1 ครั้งต่อร้านต่อคนทุกกี่ชั่วโมง */
    public const FOLLOW_NOTIFY_COOLDOWN_HOURS = 3;

    /** ย้ายเกินกี่เมตรจึงอัปเดตจุดรับของของงานไรเดอร์ (กัน GPS แกว่งทำให้แจ้งไรเดอร์รัวๆ) */
    public const PICKUP_MOVE_THRESHOLD_METERS = 30;

    /** แจ้งไรเดอร์ว่าร้านย้ายจุดรับของได้ 1 ครั้งต่องานทุกกี่นาที */
    public const PICKUP_NOTIFY_COOLDOWN_MINUTES = 5;

    /** สถานะงานไรเดอร์ที่ยังไม่ได้รับของ (ย้ายจุดรับได้) */
    public const PICKUP_PENDING_JOB_STATUSES = ['pending', 'accepted', 'picking_up'];

    /** สถานะออเดอร์ที่ยังค้างอยู่กับร้าน */
    public const ACTIVE_ORDER_STATUSES = [
        FreshMarketOrder::STATUS_PENDING,
        FreshMarketOrder::STATUS_ACCEPTED,
        FreshMarketOrder::STATUS_PREPARING,
        FreshMarketOrder::STATUS_READY,
        FreshMarketOrder::STATUS_DELIVERING,
    ];

    // ╔══════════════════════════════════════════╗
    // ║  เจ้าของร้าน: เปิด / ย้าย / ปิด            ║
    // ╚══════════════════════════════════════════╝

    /**
     * เปิดร้าน ("เปิดร้านที่นี่วันนี้")
     *
     * - ร้านเคลื่อนที่ต้องส่งพิกัด · ร้านที่ไม่มีที่อยู่ประจำแต่ส่งพิกัดมา = กลายเป็นร้านเคลื่อนที่
     * - ร้านประจำที่ส่งพิกัดมา → ไม่ย้ายร้าน (ใช้ที่อยู่ร้านเดิม)
     * - เปิดซ้ำตอนเปิดอยู่แล้ว = ย้ายตำแหน่ง/เปลี่ยนเวลาปิด ไม่แจ้งผู้ติดตามซ้ำ
     *
     * @param  array  $data  latitude?, longitude?, location_label?, closes_at? (ISO datetime หรือ "HH:MM"), live_location_sharing?
     * @return array{seller: FreshMarketSeller, just_opened: bool, pickups_updated: int}
     *
     * @throws FreshMarketException SHOP_UNAVAILABLE|INVALID_LOCATION|LOCATION_REQUIRED|INVALID_CLOSING_TIME
     */
    public function open(FreshMarketSeller $seller, array $data): array
    {
        if (! $seller->is_active || $seller->is_suspended) {
            throw FreshMarketException::make('SHOP_UNAVAILABLE', 'ร้านถูกระงับหรือปิดใช้งานอยู่ จึงเปิดร้านไม่ได้ กรุณาติดต่อทีมงาน', 403);
        }

        $lat = $data['latitude'] ?? null;
        $lng = $data['longitude'] ?? null;
        $hasPoint = $lat !== null && $lat !== '' && $lng !== null && $lng !== '';

        if ($hasPoint && ! $this->isUsableCoordinate($lat, $lng)) {
            throw FreshMarketException::make('INVALID_LOCATION', 'พิกัดตำแหน่งร้านไม่ถูกต้อง กรุณาเปิด GPS แล้วลองใหม่', 422);
        }

        $closesAt = $this->resolveClosesAt($data['closes_at'] ?? null);
        $label = $this->cleanLabel($data['location_label'] ?? null);
        $live = filter_var($data['live_location_sharing'] ?? false, FILTER_VALIDATE_BOOLEAN);

        [$fresh, $justOpened] = DB::transaction(function () use ($seller, $hasPoint, $lat, $lng, $closesAt, $label, $live) {
            /** @var FreshMarketSeller $locked */
            $locked = FreshMarketSeller::whereKey($seller->id)->lockForUpdate()->firstOrFail();
            $wasOpen = $locked->isOpenNow();
            $now = now();

            // ร้านไม่มีที่อยู่ประจำ + ส่งพิกัดมา = ร้านเคลื่อนที่โดยปริยาย
            $mobile = $locked->isMobileShop() || ($hasPoint && ! $locked->hasPickupLocation());

            if ($mobile && ! $hasPoint) {
                throw FreshMarketException::make(
                    'LOCATION_REQUIRED',
                    'กรุณาเปิด GPS เพื่อบอกลูกค้าว่าวันนี้ร้านอยู่ตรงไหน',
                    422
                );
            }

            $updates = [
                'is_open' => true,
                'opened_at' => $wasOpen && $locked->opened_at ? $locked->opened_at : $now,
                'closes_at' => $closesAt
                    ?? ($wasOpen ? $locked->closes_at : ($mobile ? $now->copy()->addHours($this->defaultMobileOpenHours()) : null)),
            ];

            if ($mobile) {
                $updates += [
                    'is_mobile' => true,
                    'current_latitude' => round((float) $lat, 7),
                    'current_longitude' => round((float) $lng, 7),
                    'location_updated_at' => $now,
                    'location_label' => $label ?? ($wasOpen ? $locked->location_label : null),
                    'live_location_sharing' => $live,
                ];
            }

            $locked->fill($updates)->save();

            return [$locked->fresh(), ! $wasOpen];
        });

        $pickups = $fresh->isMobileShop() ? $this->syncRiderPickups($fresh) : 0;

        if ($justOpened) {
            $this->queueFollowerNotification($fresh);
        }

        Log::info('FreshMarketShop: opened', [
            'seller_id' => $fresh->id,
            'mobile' => $fresh->isMobileShop(),
            'just_opened' => $justOpened,
            'live' => (bool) $fresh->live_location_sharing,
        ]);

        return ['seller' => $fresh, 'just_opened' => $justOpened, 'pickups_updated' => $pickups];
    }

    /**
     * ตำแหน่งสดจากเครื่องร้าน (ร้านเคลื่อนที่ที่เปิดอยู่เท่านั้น)
     *
     * @return array{seller: FreshMarketSeller, pickups_updated: int}
     *
     * @throws FreshMarketException INVALID_LOCATION|NOT_MOBILE_SHOP|SHOP_CLOSED
     */
    public function updateLocation(FreshMarketSeller $seller, mixed $lat, mixed $lng, ?string $label = null): array
    {
        if (! $this->isUsableCoordinate($lat, $lng)) {
            throw FreshMarketException::make('INVALID_LOCATION', 'พิกัดตำแหน่งร้านไม่ถูกต้อง', 422);
        }

        if (! $seller->isMobileShop()) {
            throw FreshMarketException::make(
                'NOT_MOBILE_SHOP',
                'ร้านนี้เป็นร้านประจำที่ ไม่ต้องส่งตำแหน่งสด (แก้ที่อยู่ร้านได้ที่หน้าตั้งค่าร้าน)',
                422
            );
        }

        $updates = [
            'current_latitude' => round((float) $lat, 7),
            'current_longitude' => round((float) $lng, 7),
            'location_updated_at' => now(),
            'updated_at' => now(),
        ];

        $label = $this->cleanLabel($label);
        if ($label !== null) {
            $updates['location_label'] = $label;
        }

        // อัปเดตแบบมีเงื่อนไข (ไม่ lock แถวทุก 30 วินาที) — ร้านต้องเปิดอยู่และยังไม่เลยเวลาปิด
        $updated = FreshMarketSeller::whereKey($seller->id)
            ->where('is_open', true)
            ->where(fn ($q) => $q->whereNull('closes_at')->orWhere('closes_at', '>', now()))
            ->update($updates);

        if ($updated === 0) {
            throw FreshMarketException::make('SHOP_CLOSED', 'ร้านปิดอยู่ กรุณากด "เปิดร้าน" ก่อนส่งตำแหน่ง', 409);
        }

        $fresh = $seller->fresh();

        return ['seller' => $fresh, 'pickups_updated' => $this->syncRiderPickups($fresh)];
    }

    /**
     * ปิดร้าน (ออเดอร์ที่รับไว้แล้วยังทำต่อได้ตามปกติ)
     *
     * @return array{seller: FreshMarketSeller, was_open: bool, active_orders: int}
     */
    public function close(FreshMarketSeller $seller): array
    {
        $updated = FreshMarketSeller::whereKey($seller->id)
            ->where('is_open', true)
            ->update([
                'is_open' => false,
                'closes_at' => null,
                'live_location_sharing' => false,
                'updated_at' => now(),
            ]);

        $activeOrders = FreshMarketOrder::where('seller_id', $seller->id)
            ->whereIn('order_status', self::ACTIVE_ORDER_STATUSES)
            ->count();

        if ($updated > 0) {
            Log::info('FreshMarketShop: closed', ['seller_id' => $seller->id, 'active_orders' => $activeOrders]);
        }

        return ['seller' => $seller->fresh(), 'was_open' => $updated > 0, 'active_orders' => $activeOrders];
    }

    /**
     * สลับร้านประจำที่ ↔ ร้านเคลื่อนที่ (จากหน้าตั้งค่าร้าน)
     *
     * - เป็นร้านเคลื่อนที่ → ปิดร้านไว้ก่อน รอกด "เปิดร้านที่นี่วันนี้" พร้อมตำแหน่ง
     * - กลับเป็นร้านประจำ → ลบตำแหน่งปัจจุบันทิ้ง แล้วเปิดตามที่อยู่ร้าน (ร้านประจำเปิดเป็นค่าเริ่มต้น)
     */
    public function setMobile(FreshMarketSeller $seller, bool $mobile): FreshMarketSeller
    {
        if ($seller->isMobileShop() === $mobile) {
            return $seller;
        }

        $updates = $mobile
            ? [
                'is_mobile' => true,
                'is_open' => false,
                'closes_at' => null,
                'live_location_sharing' => false,
            ]
            : [
                'is_mobile' => false,
                'is_open' => true,
                'closes_at' => null,
                'live_location_sharing' => false,
                'current_latitude' => null,
                'current_longitude' => null,
                'location_label' => null,
                'location_updated_at' => null,
            ];

        $seller->fill($updates)->save();

        return $seller->fresh();
    }

    /**
     * ปิดร้านอัตโนมัติ (fresh-market:close-stale-shops ทุก 5 นาที)
     *
     * - เลยเวลาปิดที่ตั้งไว้ (closes_at)
     * - ร้านเคลื่อนที่ที่เปิดตำแหน่งสดไว้ แต่ตำแหน่งเงียบเกิน 30 นาที
     *
     * @return int จำนวนร้านที่ปิดรอบนี้
     */
    public function closeStaleShops(int $limit = 200): int
    {
        $now = now();
        $staleCutoff = $now->copy()->subMinutes(FreshMarketSeller::LIVE_LOCATION_STALE_MINUTES);

        $staleCondition = function ($q) use ($now, $staleCutoff) {
            $q->where(function ($q) use ($now) {
                $q->whereNotNull('closes_at')->where('closes_at', '<=', $now);
            })->orWhere(function ($q) use ($staleCutoff) {
                $q->where('is_mobile', true)
                    ->where('live_location_sharing', true)
                    ->where(function ($q) use ($staleCutoff) {
                        $q->whereNull('location_updated_at')->orWhere('location_updated_at', '<', $staleCutoff);
                    });
            });
        };

        $candidates = FreshMarketSeller::where('is_open', true)
            ->where($staleCondition)
            ->orderBy('id')
            ->limit(max(1, $limit))
            ->get();

        $closed = 0;

        foreach ($candidates as $seller) {
            try {
                $reason = ($seller->closes_at && $seller->closes_at->lte($now)) ? 'time_up' : 'location_stale';

                // ปิดแบบมีเงื่อนไขเดิม → ร้านที่เพิ่งส่งตำแหน่งใหม่/เลื่อนเวลาปิดระหว่างนี้ไม่ถูกปิด
                $updated = FreshMarketSeller::whereKey($seller->id)
                    ->where('is_open', true)
                    ->where($staleCondition)
                    ->update([
                        'is_open' => false,
                        'closes_at' => null,
                        'live_location_sharing' => false,
                        'updated_at' => now(),
                    ]);

                if ($updated === 0) {
                    continue;
                }

                $closed++;
                $this->notifySellerAutoClosed($seller, $reason);
            } catch (\Throwable $e) {
                Log::error('FreshMarketShop: auto-close failed', ['seller_id' => $seller->id, 'error' => $e->getMessage()]);
            }
        }

        return $closed;
    }

    // ╔══════════════════════════════════════════╗
    // ║  จุดรับของของงานไรเดอร์ (ร้านเคลื่อนที่)    ║
    // ╚══════════════════════════════════════════╝

    /**
     * ร้านเคลื่อนที่ย้ายตำแหน่ง → ย้ายจุดรับของของงานไรเดอร์ที่ยังไม่ได้รับของ + แจ้งไรเดอร์ (ไม่ถี่เกิน 5 นาทีต่องาน)
     *
     * ค่าส่งไม่เปลี่ยน (คิดจากผู้ซื้อไปแล้วตอนสั่ง)
     *
     * @return int จำนวนงานที่ย้ายจุดรับของ
     */
    public function syncRiderPickups(FreshMarketSeller $seller): int
    {
        $point = $seller->isMobileShop() ? $seller->pickupPoint() : null;

        if (! $point) {
            return 0;
        }

        try {
            $orders = FreshMarketOrder::where('seller_id', $seller->id)
                ->where('delivery_type', 'rider')
                ->whereIn('order_status', self::ACTIVE_ORDER_STATUSES)
                ->get(['id', 'rider_job_id']);

            if ($orders->isEmpty()) {
                return 0;
            }

            $orderIds = $orders->pluck('id')->map(fn ($id) => (int) $id)->all();
            $linkedJobIds = $orders->pluck('rider_job_id')->filter()->map(fn ($id) => (int) $id)->all();

            $jobs = RiderJob::query()
                ->whereIn('status', self::PICKUP_PENDING_JOB_STATUSES)
                ->where(function ($q) use ($orderIds, $linkedJobIds) {
                    $q->where(function ($q) use ($orderIds) {
                        $q->where('source_type', (new FreshMarketOrder)->getMorphClass())
                            ->whereIn('source_id', $orderIds);
                    });

                    if (! empty($linkedJobIds)) {
                        $q->orWhereIn('id', $linkedJobIds);
                    }
                })
                ->with('rider')
                ->get();
        } catch (\Throwable $e) {
            Log::error('FreshMarketShop: load rider jobs for pickup sync failed', ['seller_id' => $seller->id, 'error' => $e->getMessage()]);

            return 0;
        }

        $moved = 0;

        foreach ($jobs as $job) {
            $distanceM = ($job->pickup_latitude !== null && $job->pickup_longitude !== null)
                ? DeliveryFeeCalculator::haversineKm(
                    (float) $job->pickup_latitude,
                    (float) $job->pickup_longitude,
                    $point['latitude'],
                    $point['longitude']
                ) * 1000
                : PHP_FLOAT_MAX;

            if ($distanceM < self::PICKUP_MOVE_THRESHOLD_METERS) {
                continue;
            }

            // อัปเดตแบบมีเงื่อนไข: ไรเดอร์รับของไปแล้วระหว่างนี้ = ไม่ย้าย
            $updated = RiderJob::whereKey($job->id)
                ->whereIn('status', self::PICKUP_PENDING_JOB_STATUSES)
                ->update([
                    'pickup_latitude' => $point['latitude'],
                    'pickup_longitude' => $point['longitude'],
                    'pickup_address' => mb_substr($point['address'], 0, 500),
                    'updated_at' => now(),
                ]);

            if ($updated === 0) {
                continue;
            }

            $moved++;

            if ($job->rider_id && $job->rider
                && Cache::add('fm:pickup-moved:'.$job->id, 1, now()->addMinutes(self::PICKUP_NOTIFY_COOLDOWN_MINUTES))) {
                try {
                    app(RiderNotificationService::class)->notifyRider(
                        $job->rider,
                        $job,
                        'ร้านย้ายจุดรับของ',
                        "ร้าน {$seller->shop_name} ย้ายตำแหน่ง จุดรับของงาน #{$job->job_number} อัปเดตแล้ว กรุณาเปิดแผนที่อีกครั้ง",
                        'pickup_moved'
                    );
                } catch (\Throwable $e) {
                    Log::warning('FreshMarketShop: notify rider pickup moved failed', ['job_id' => $job->id, 'error' => $e->getMessage()]);
                }
            }
        }

        return $moved;
    }

    // ╔══════════════════════════════════════════╗
    // ║  ผู้ติดตามร้าน                             ║
    // ╚══════════════════════════════════════════╝

    /**
     * ติดตามร้าน (กดซ้ำได้ ไม่เกิดแถวซ้ำ)
     *
     * @throws FreshMarketException SHOP_NOT_FOUND|CANNOT_FOLLOW_OWN_SHOP
     */
    public function follow(User $user, FreshMarketSeller $seller): FreshMarketShopFollower
    {
        if (! $seller->isVisibleToBuyers()) {
            throw FreshMarketException::make('SHOP_NOT_FOUND', 'ไม่พบร้านนี้', 404);
        }

        if ((int) $seller->user_id === (int) $user->id) {
            throw FreshMarketException::make('CANNOT_FOLLOW_OWN_SHOP', 'ติดตามร้านของตัวเองไม่ได้', 422);
        }

        try {
            $follower = FreshMarketShopFollower::firstOrCreate(
                ['seller_id' => $seller->id, 'user_id' => $user->id],
                ['notify' => true]
            );
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            // กดติดตามสองครั้งพร้อมกัน → อีกคำขอสร้างไปแล้ว
            $follower = FreshMarketShopFollower::where('seller_id', $seller->id)->where('user_id', $user->id)->firstOrFail();
        }

        if (! $follower->notify) {
            $follower->forceFill(['notify' => true])->save();
        }

        return $follower;
    }

    /**
     * เลิกติดตามร้าน
     *
     * @return bool true = เคยติดตามอยู่
     */
    public function unfollow(User $user, FreshMarketSeller $seller): bool
    {
        return FreshMarketShopFollower::where('seller_id', $seller->id)
            ->where('user_id', $user->id)
            ->delete() > 0;
    }

    public function isFollowing(?User $user, FreshMarketSeller $seller): bool
    {
        if (! $user) {
            return false;
        }

        return FreshMarketShopFollower::where('seller_id', $seller->id)->where('user_id', $user->id)->exists();
    }

    public function followersCount(FreshMarketSeller $seller): int
    {
        return FreshMarketShopFollower::where('seller_id', $seller->id)->count();
    }

    /**
     * แจ้งผู้ติดตามว่าร้านเปิดแล้ว (เรียกจาก NotifyFreshMarketShopFollowersJob)
     *
     * กันแจ้งถี่: "จอง" แถวผู้ติดตามทีละแถวด้วย UPDATE ... WHERE last_notified_at เก่ากว่า 3 ชม.
     * → รันซ้ำ/รันพร้อมกันก็แจ้งแต่ละคนครั้งเดียว
     *
     * @return int จำนวนคนที่แจ้ง
     */
    public function notifyFollowersShopOpened(int $sellerId): int
    {
        $seller = FreshMarketSeller::find($sellerId);

        if (! $seller || ! $seller->acceptsOrders()) {
            return 0;
        }

        $cutoff = now()->subHours(self::FOLLOW_NOTIFY_COOLDOWN_HOURS);
        $due = function ($q) use ($cutoff) {
            $q->whereNull('last_notified_at')->orWhere('last_notified_at', '<=', $cutoff);
        };

        $notified = 0;
        $title = $seller->shop_name.' เปิดร้านแล้ว';
        $label = trim((string) $seller->location_label);
        $message = $label !== ''
            ? "ร้านที่คุณติดตามเปิดขายแล้วที่ {$label} กดดูเมนูและตำแหน่งร้าน"
            : 'ร้านที่คุณติดตามเปิดขายแล้ว กดดูเมนูและสั่งได้เลย';
        $url = $this->safeRoute('taladsod.seller', $seller->id);

        FreshMarketShopFollower::where('seller_id', $seller->id)
            ->where('notify', true)
            ->where($due)
            ->with('user')
            ->chunkById(200, function ($rows) use ($seller, $due, $title, $message, $url, &$notified) {
                foreach ($rows as $row) {
                    $claimed = FreshMarketShopFollower::whereKey($row->id)
                        ->where('notify', true)
                        ->where($due)
                        ->update(['last_notified_at' => now()]);

                    if ($claimed === 0 || ! $row->user || (int) $row->user_id === (int) $seller->user_id) {
                        continue;
                    }

                    try {
                        app(NotificationService::class)->create(
                            $row->user,
                            'fresh_market_shop_open',
                            $title,
                            $message,
                            [
                                'type' => 'fresh_market_shop_open',
                                'event' => 'shop_opened',
                                'seller_id' => (int) $seller->id,
                                'shop_id' => (int) $seller->id,
                                'shop_name' => $seller->shop_name,
                                'is_mobile' => $seller->isMobileShop(),
                                'screen' => 'taladsod-shop',
                                'channel' => 'default',
                            ],
                            $url,
                            'ดูร้าน',
                            'normal',
                            false,
                            false,
                            '🛒',
                            'green'
                        );
                        $notified++;
                    } catch (\Throwable $e) {
                        Log::warning('FreshMarketShop: notify follower failed', [
                            'seller_id' => $seller->id,
                            'user_id' => $row->user_id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

        if ($notified > 0) {
            Log::info('FreshMarketShop: followers notified', ['seller_id' => $seller->id, 'count' => $notified]);
        }

        return $notified;
    }

    // ╔══════════════════════════════════════════╗
    // ║  ข้อมูลร้านสำหรับผู้ซื้อ (API + เว็บ)        ║
    // ╚══════════════════════════════════════════╝

    /**
     * ข้อมูลร้านแบบย่อ + สถานะหน้าร้าน (ผู้ซื้อ)
     *
     * @return array<string, mixed>
     */
    public function shopCard(FreshMarketSeller $seller, ?User $viewer = null, ?bool $isFollowing = null): array
    {
        return [
            'id' => (int) $seller->id,
            'shop_name' => $seller->shop_name,
            'shop_image' => $seller->shop_image,
            'rating_average' => (float) $seller->rating_average,
            'rating_count' => (int) $seller->rating_count,
            'is_verified' => (bool) $seller->is_verified,
            'is_mobile' => $seller->isMobileShop(),
            'is_open' => $seller->isOpenNow(),
            'is_following' => $isFollowing ?? $this->isFollowing($viewer, $seller),
            'presence' => $seller->presencePayload(),
        ];
    }

    /**
     * ข้อมูลร้านเต็ม (หน้าร้าน) + สถานะหน้าร้าน + ผู้ติดตาม
     *
     * ร้านเคลื่อนที่ไม่เปิดเผยที่อยู่/พิกัดที่ลงทะเบียน (อาจเป็นบ้าน) — เห็นเฉพาะตำแหน่งตอนเปิดร้าน
     *
     * @return array<string, mixed>
     */
    public function shopProfile(FreshMarketSeller $seller, ?User $viewer = null): array
    {
        $mobile = $seller->isMobileShop();
        $presence = $seller->presencePayload();

        return array_merge($this->shopCard($seller, $viewer), [
            'shop_description' => $seller->shop_description,
            'total_sales' => (int) $seller->total_sales,
            'province' => $seller->province,
            'district' => $mobile ? null : $seller->district,
            'address' => $mobile ? null : $seller->address,
            'latitude' => $presence['location']['latitude'] ?? null,
            'longitude' => $presence['location']['longitude'] ?? null,
            'followers_count' => $this->followersCount($seller),
            'can_order' => (bool) $presence['can_order'],
            'closed_message' => $presence['closed_message'],
            'location_poll_seconds' => $presence['live_location_sharing'] ? 30 : 120,
        ]);
    }

    /**
     * ร้านที่ "เปิดอยู่ใกล้คุณ" (หน้าแรกตลาดสดบนเว็บ + แอป) เรียงจากใกล้ไปไกล
     *
     * - ร้านเคลื่อนที่ (รถเข็น/ตลาดนัด) ใช้ตำแหน่งตอนนี้ · ร้านประจำใช้ที่อยู่ร้าน
     * - ส่งเฉพาะร้านที่เปิดอยู่และมีตำแหน่งสาธารณะ (ร้านปิด = ไม่เปิดเผยตำแหน่ง เหมือน publicLocation())
     * - กรองคร่าวด้วยกรอบสี่เหลี่ยมใน SQL แล้วคำนวณระยะจริง (Haversine) ใน PHP
     *
     * @return array<int, array<string, mixed>> shopCard() + distance_km, location, listings_count, top_items[], url
     */
    public function nearbyShops(float $lat, float $lng, float $radiusKm = 10, ?User $viewer = null, int $limit = 30): array
    {
        if (! $this->isUsableCoordinate($lat, $lng)) {
            return [];
        }

        $radiusKm = max(0.5, min(50.0, $radiusKm));
        $limit = max(1, min(60, $limit));
        $dLat = $radiusKm / 111.0;
        $dLng = $radiusKm / max(0.01, 111.0 * cos(deg2rad($lat)));
        $now = now();

        $candidates = FreshMarketSeller::query()
            ->where('is_active', true)
            ->where('is_suspended', false)
            ->when(! \App\Models\FreshMarketListing::autoApproveSellers(), fn ($q) => $q->where('is_verified', true))
            ->where('is_open', true)
            ->where(fn ($q) => $q->whereNull('closes_at')->orWhere('closes_at', '>', $now))
            ->where(function ($q) use ($lat, $lng, $dLat, $dLng) {
                // ร้านเคลื่อนที่: ตำแหน่งปัจจุบัน
                $q->where(fn ($m) => $m->where('is_mobile', true)
                    ->whereBetween('current_latitude', [$lat - $dLat, $lat + $dLat])
                    ->whereBetween('current_longitude', [$lng - $dLng, $lng + $dLng]))
                    // ร้านประจำที่: ที่อยู่ร้าน
                    ->orWhere(fn ($f) => $f->where('is_mobile', false)
                        ->whereBetween('latitude', [$lat - $dLat, $lat + $dLat])
                        ->whereBetween('longitude', [$lng - $dLng, $lng + $dLng]));
            })
            ->limit(300)
            ->get();

        $rows = [];

        foreach ($candidates as $seller) {
            $location = $seller->publicLocation();

            if (! $location) {
                continue;
            }

            $distance = DeliveryFeeCalculator::haversineKm($lat, $lng, (float) $location['latitude'], (float) $location['longitude']);

            if ($distance > $radiusKm) {
                continue;
            }

            $rows[] = ['seller' => $seller, 'location' => $location, 'distance' => $distance];
        }

        usort($rows, fn ($a, $b) => $a['distance'] <=> $b['distance']);
        $rows = array_slice($rows, 0, $limit);

        if ($rows === []) {
            return [];
        }

        $ids = array_map(fn ($r) => (int) $r['seller']->id, $rows);

        // สินค้าที่ขายอยู่ของร้านเหล่านี้ (query เดียว) → จำนวน + 3 รายการล่าสุดต่อร้าน
        $listings = \App\Models\FreshMarketListing::visibleToBuyers()
            ->inStock()
            ->whereIn('seller_id', $ids)
            ->latest()
            ->limit(600)
            ->get(['id', 'seller_id', 'slug', 'title', 'price', 'unit', 'main_image_url', 'images', 'status', 'is_available'])
            ->groupBy('seller_id');

        $followed = $viewer
            ? FreshMarketShopFollower::where('user_id', $viewer->id)->whereIn('seller_id', $ids)->pluck('seller_id')->map(fn ($id) => (int) $id)->all()
            : [];

        return array_map(function (array $row) use ($listings, $followed) {
            /** @var FreshMarketSeller $seller */
            $seller = $row['seller'];
            $items = $listings->get($seller->id, collect());

            return array_merge($this->shopCard($seller, null, in_array((int) $seller->id, $followed, true)), [
                'distance_km' => round($row['distance'], 2),
                'location' => $row['location'],
                'listings_count' => $items->count(),
                'top_items' => $items->take(3)->map(fn ($l) => [
                    'id' => (int) $l->id,
                    'slug' => $l->slug,
                    'title' => $l->title,
                    'price' => round((float) $l->price, 2),
                    'unit' => $l->unit,
                    'image_url' => $l->primary_image,
                ])->values()->all(),
                'url' => $this->safeRoute('taladsod.seller', $seller->id),
            ]);
        }, $rows);
    }

    // ╔══════════════════════════════════════════╗
    // ║  Helpers                                 ║
    // ╚══════════════════════════════════════════╝

    /**
     * แปลงเวลาปิดร้าน: ISO datetime / "Y-m-d H:i" / "HH:MM" (วันนี้ ถ้าเลยแล้ว = พรุ่งนี้) — null = ไม่กำหนด
     *
     * @throws FreshMarketException INVALID_CLOSING_TIME
     */
    public function resolveClosesAt(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            $value = trim((string) $value);

            if (preg_match('/^([01]?\d|2[0-3]):([0-5]\d)$/', $value, $m)) {
                $at = now()->setTime((int) $m[1], (int) $m[2]);

                if ($at->lte(now())) {
                    $at->addDay();
                }
            } else {
                $at = Carbon::parse($value)->setTimezone(config('app.timezone'));
            }
        } catch (\Throwable $e) {
            throw FreshMarketException::make('INVALID_CLOSING_TIME', 'รูปแบบเวลาปิดร้านไม่ถูกต้อง', 422);
        }

        if ($at->lte(now()->addMinutes(5))) {
            throw FreshMarketException::make('INVALID_CLOSING_TIME', 'เวลาปิดร้านต้องอยู่หลังเวลาปัจจุบันอย่างน้อย 5 นาที', 422);
        }

        if ($at->gt(now()->addHours(self::MAX_OPEN_HOURS))) {
            throw FreshMarketException::make('INVALID_CLOSING_TIME', 'ตั้งเวลาปิดร้านล่วงหน้าได้ไม่เกิน '.self::MAX_OPEN_HOURS.' ชั่วโมง', 422);
        }

        return $at;
    }

    /**
     * ร้านเคลื่อนที่ที่ไม่ได้ตั้งเวลาปิด → ปิดให้อัตโนมัติหลังกี่ชั่วโมง (Setting แก้ได้)
     */
    protected function defaultMobileOpenHours(): int
    {
        $hours = (int) Setting::get('fresh_market.mobile_default_open_hours', self::DEFAULT_MOBILE_OPEN_HOURS);

        return max(1, min(self::MAX_OPEN_HOURS, $hours));
    }

    protected function isUsableCoordinate(mixed $lat, mixed $lng): bool
    {
        return DeliveryFeeCalculator::isValidCoordinate($lat, $lng)
            && ! ((float) $lat == 0.0 && (float) $lng == 0.0);
    }

    protected function cleanLabel(mixed $label): ?string
    {
        if ($label === null) {
            return null;
        }

        $clean = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $label)) ?? '');

        return $clean !== '' ? mb_substr($clean, 0, 150) : null;
    }

    /**
     * เข้าคิวแจ้งผู้ติดตาม (หลัง commit — rollback = ไม่แจ้ง)
     */
    protected function queueFollowerNotification(FreshMarketSeller $seller): void
    {
        try {
            NotifyFreshMarketShopFollowersJob::dispatch((int) $seller->id)->afterCommit();
        } catch (\Throwable $e) {
            Log::warning('FreshMarketShop: queue follower notification failed', ['seller_id' => $seller->id, 'error' => $e->getMessage()]);
        }
    }

    /**
     * แจ้งเจ้าของร้านว่าระบบปิดร้านให้อัตโนมัติ (ในแอป + Expo push ผ่าน NotificationService)
     */
    protected function notifySellerAutoClosed(FreshMarketSeller $seller, string $reason): void
    {
        $user = $seller->user;

        if (! $user) {
            return;
        }

        $message = $reason === 'time_up'
            ? 'ถึงเวลาปิดร้านที่ตั้งไว้ ระบบปิดร้านให้แล้ว ลูกค้าจะเห็นร้านเป็น "ปิดอยู่" จนกว่าคุณจะกดเปิดร้านอีกครั้ง'
            : 'ไม่ได้รับตำแหน่งสดของร้านเกิน '.FreshMarketSeller::LIVE_LOCATION_STALE_MINUTES.' นาที ระบบปิดร้านให้ชั่วคราว กดเปิดร้านอีกครั้งเมื่อพร้อมขาย';

        try {
            app(NotificationService::class)->create(
                $user,
                'fresh_market_shop',
                'ร้านปิดอัตโนมัติ',
                $message,
                [
                    'type' => 'fresh_market_shop',
                    'event' => 'auto_closed',
                    'reason' => $reason,
                    'seller_id' => (int) $seller->id,
                    'screen' => 'merchant-taladsod',
                    'channel' => 'orders',
                ],
                $this->safeRoute('taladsod.seller.dashboard'),
                'เปิดร้าน',
                'normal'
            );
        } catch (\Throwable $e) {
            Log::warning('FreshMarketShop: notify auto-close failed', ['seller_id' => $seller->id, 'error' => $e->getMessage()]);
        }
    }

    protected function safeRoute(string $name, mixed $params = []): ?string
    {
        try {
            return route($name, $params);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
