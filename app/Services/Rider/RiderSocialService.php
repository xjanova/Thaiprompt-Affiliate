<?php

namespace App\Services\Rider;

use App\Exceptions\RiderSocialException;
use App\Models\FreshMarketOrder;
use App\Models\Order;
use App\Models\Rider;
use App\Models\RiderHeart;
use App\Models\RiderJob;
use App\Models\User;
use App\Services\DeliveryFeeCalculator;
use App\Services\Ekyc\EkycService;
use App\Services\Media\ProfilePhotoService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * หัวใจไรเดอร์ / ไรเดอร์คนโปรด / ไรเดอร์ใกล้ฉัน / สิทธิ์ล็อกเรียก (ไรเดอร์รอบ 2, 2026-10-04 — เลน social)
 *
 * กติกา (เจ้าของอนุมัติ — ห้ามเปลี่ยน):
 *   - ผู้ซื้อให้หัวใจไรเดอร์ได้ 1 ดวงต่อ 1 งานที่ส่งสำเร็จ (เฉพาะผู้ซื้อของออเดอร์นั้น)
 *   - ผู้ซื้อคนหนึ่งให้หัวใจไรเดอร์คนหนึ่งครบ rider.lock_min_hearts (11 = "เกิน 10") → ล็อกเรียกไรเดอร์คนนั้นตอนสั่งได้
 *   - แผนที่ "ไรเดอร์ใกล้ฉัน" เห็นเฉพาะไรเดอร์ที่ออนไลน์ + เปิด show_on_nearby + พิกัดสด
 *     และตำแหน่งที่ส่งออกไปเป็นตำแหน่งเบลอ (~rider.nearby_fuzz_m) เสมอ
 *
 * ความเป็นส่วนตัว: ไม่ส่งพิกัดจริง / เบอร์โทร / ทะเบียนเต็ม / ชื่อเต็ม ของไรเดอร์ออกไปทางนี้เด็ดขาด
 * รูปคนทุกรูปต้องผ่าน ProfilePhotoService::urlFor() (รูปมีลายน้ำรหัสผู้ดู)
 */
class RiderSocialService
{
    /** ประเภทออเดอร์ใน URL /orders/{source}/{id}/... */
    public const SOURCES = ['shop', 'fresh-market'];

    /** รหัสข้อผิดพลาด: ให้หัวใจไม่ได้ (409) */
    public const HEART_NOT_ALLOWED = 'HEART_NOT_ALLOWED';

    /** รหัสข้อผิดพลาด: ล็อกเรียกไรเดอร์ตอนสั่งไม่ได้ (422) */
    public const LOCK_NOT_ALLOWED = 'RIDER_LOCK_NOT_ALLOWED';

    /** ช่วงเวลาที่ตำแหน่งเบลอคงที่ (วินาที) — เรียกซ้ำกี่ครั้งในช่วงเดียวกันก็ได้จุดเดิม หาค่าเฉลี่ยเพื่อเดาจุดจริงไม่ได้ */
    public const FUZZ_WINDOW_SECONDS = 600;

    /** จำนวนไรเดอร์สูงสุดบนแผนที่ใกล้ฉัน */
    private const NEARBY_LIMIT = 50;

    /** จำนวนไรเดอร์คนโปรดสูงสุดที่ส่งกลับ */
    private const FAVORITES_LIMIT = 50;

    /** คำนำหน้าชื่อที่เขียนแยกคำ (ตัดออกก่อนทำชื่อย่อ) */
    private const NAME_TITLES = ['นาย', 'นาง', 'นางสาว', 'น.ส.', 'ด.ช.', 'ด.ญ.', 'mr', 'mr.', 'mrs', 'mrs.', 'ms', 'ms.', 'miss', 'dr', 'dr.'];

    public function __construct(
        private readonly DeliveryFeeCalculator $config,
        private readonly ProfilePhotoService $photos,
    ) {}

    // =====================================================
    // หัวใจ
    // =====================================================

    /**
     * ผู้ซื้อให้หัวใจไรเดอร์ของออเดอร์ (1 ดวงต่องาน — กดซ้ำ/กดพร้อมกันได้ผลเดิม already = true)
     *
     * @return array{hearts_from_me: int, hearts_total: int, can_lock: bool, already: bool, lock_min_hearts: int}
     *
     * @throws RiderSocialException HEART_NOT_ALLOWED (409)
     */
    public function giveHeart(User $buyer, string $source, int $orderId): array
    {
        // ออเดอร์ของคนอื่น / ไม่มีอยู่จริง → คำตอบเดียวกัน (ไม่บอกว่าออเดอร์มีอยู่)
        $order = $this->findBuyerOrder($buyer, $source, $orderId);
        if (! $order) {
            throw RiderSocialException::make(self::HEART_NOT_ALLOWED, 'ให้หัวใจได้เฉพาะออเดอร์ของคุณที่ไรเดอร์ส่งสำเร็จแล้ว', 409);
        }

        $job = $this->completedJobFor($order);
        $rider = $job ? Rider::find($job->rider_id) : null;

        if (! $job || ! $rider || (int) $rider->user_id === (int) $buyer->id) {
            throw RiderSocialException::make(self::HEART_NOT_ALLOWED, 'ให้หัวใจได้หลังไรเดอร์ส่งของถึงมือคุณเรียบร้อยแล้ว', 409);
        }

        $already = RiderHeart::where('rider_job_id', $job->id)->exists();

        if (! $already) {
            try {
                // แถวหัวใจ + ตัวนับรวมของไรเดอร์ ต้องสำเร็จพร้อมกัน
                DB::transaction(function () use ($rider, $buyer, $job) {
                    RiderHeart::create([
                        'rider_id' => $rider->id,
                        'user_id' => $buyer->id,
                        'rider_job_id' => $job->id,
                    ]);

                    Rider::whereKey($rider->id)->increment('hearts_count');
                });
            } catch (UniqueConstraintViolationException) {
                // กดพร้อมกัน 2 ครั้ง: อีกคำขอบันทึกไปก่อนแล้ว (rider_job_id unique) → ไม่นับซ้ำ
                $already = true;
            }
        }

        $rider->refresh();
        $mine = $this->pairHearts((int) $rider->id, (int) $buyer->id);

        return [
            'hearts_from_me' => $mine,
            'hearts_total' => (int) $rider->hearts_count,
            'can_lock' => $this->canLock($rider, $mine),
            'already' => $already,
            'lock_min_hearts' => $this->lockMinHearts(),
        ];
    }

    /**
     * จำนวนหัวใจที่ผู้ซื้อคนนี้ให้ไรเดอร์คนนี้
     */
    public function pairHearts(int $riderId, int $userId): int
    {
        return RiderHeart::where('rider_id', $riderId)->where('user_id', $userId)->count();
    }

    /**
     * ต้องให้หัวใจกี่ดวงถึงล็อกเรียกได้ (rider.lock_min_hearts — ค่าเริ่มต้น 11)
     */
    public function lockMinHearts(): int
    {
        return max(1, $this->config->intSetting('rider.lock_min_hearts'));
    }

    /**
     * ผู้ซื้อที่ให้หัวใจ $pairHearts ดวง ล็อกเรียกไรเดอร์คนนี้ได้หรือไม่
     */
    public function canLock(Rider $rider, int $pairHearts): bool
    {
        return $pairHearts >= $this->lockMinHearts() && $this->riderLockable($rider);
    }

    /**
     * ไรเดอร์อยู่ในสถานะที่ถูกล็อกเรียกได้ (อนุมัติแล้ว ไม่ถูกระงับ เป็นไรเดอร์ส่งของ บัญชีผู้ใช้ไม่ถูกบล็อก)
     */
    public function riderLockable(Rider $rider): bool
    {
        if ($rider->status !== 'approved' || $rider->suspended_at !== null || $rider->trashed() || ! $rider->isDeliveryRider()) {
            return false;
        }

        $user = $rider->relationLoaded('user') ? $rider->user : $rider->user()->first();

        return $user !== null && $user->blocked_at === null;
    }

    /**
     * ตรวจคำขอล็อกเรียกไรเดอร์ตอนสั่งซื้อ (แอป: ตะกร้าร้านค้า + ออเดอร์ตลาดสด)
     *
     * @param  mixed  $preferredRiderId  ค่าจากคำขอ (ว่าง = ไม่ได้ขอล็อก)
     * @param  bool  $riderDelivery  เลือกส่งด้วยไรเดอร์หรือไม่
     * @return int|null id ไรเดอร์ที่ล็อกได้ · null = ไม่ได้ขอล็อก
     *
     * @throws RiderSocialException RIDER_LOCK_NOT_ALLOWED (422)
     */
    public function resolveCheckoutLock(User $buyer, mixed $preferredRiderId, bool $riderDelivery): ?int
    {
        if ($preferredRiderId === null || $preferredRiderId === '' || $preferredRiderId === false) {
            return null;
        }

        if (! is_numeric($preferredRiderId) || (int) $preferredRiderId <= 0) {
            throw RiderSocialException::make(self::LOCK_NOT_ALLOWED, 'ไรเดอร์ที่เลือกไม่ถูกต้อง', 422);
        }

        if (! $riderDelivery) {
            throw RiderSocialException::make(self::LOCK_NOT_ALLOWED, 'ล็อกเรียกไรเดอร์ได้เฉพาะเมื่อเลือกส่งด้วยไรเดอร์', 422);
        }

        $riderId = (int) $preferredRiderId;
        $min = $this->lockMinHearts();
        $hearts = $this->pairHearts($riderId, (int) $buyer->id);

        // ตรวจหัวใจก่อน: ไรเดอร์ที่ไม่มีอยู่จริงได้ 0 ดวงเหมือนกัน (ไม่บอกว่ามี id นี้หรือไม่)
        if ($hearts < $min) {
            throw RiderSocialException::make(
                self::LOCK_NOT_ALLOWED,
                "ล็อกเรียกไรเดอร์คนนี้ได้เมื่อคุณให้หัวใจครบ {$min} ดวง (ตอนนี้ {$hearts} ดวง)",
                422,
                ['hearts_from_me' => $hearts, 'lock_min_hearts' => $min]
            );
        }

        $rider = Rider::with('user')->find($riderId);
        if (! $rider || (int) $rider->user_id === (int) $buyer->id || ! $this->riderLockable($rider)) {
            throw RiderSocialException::make(
                self::LOCK_NOT_ALLOWED,
                'ไรเดอร์คนนี้ยังรับงานไม่ได้ในขณะนี้ กรุณาสั่งโดยไม่ล็อกไรเดอร์',
                422,
                ['hearts_from_me' => $hearts, 'lock_min_hearts' => $min]
            );
        }

        return $riderId;
    }

    // =====================================================
    // ไรเดอร์ใกล้ฉัน (ตำแหน่งเบลอ)
    // =====================================================

    /**
     * ไรเดอร์ที่ออนไลน์รอบตัวผู้ซื้อ (ตำแหน่งเบลอ ระยะคิดจากจุดเบลอ)
     *
     * กรองด้วยระยะจาก "จุดเบลอ" ไม่ใช่จุดจริง → ขยับจุดค้นหาไปมาเพื่อหาขอบรัศมีก็ได้แค่จุดเบลอเดิม
     *
     * @return array{riders: array<int, array<string, mixed>>, radius_km: float, fuzz_m: int, lock_min_hearts: int}
     */
    public function nearby(User $viewer, float $lat, float $lng): array
    {
        $radiusKm = max(0.5, $this->config->floatSetting('rider.nearby_radius_km'));
        $fuzzM = $this->fuzzMeters();
        $window = $this->fuzzWindow();

        // กรองสี่เหลี่ยมคร่าวๆ ด้วย index (เผื่อระยะเบลอ) แล้วค่อยตัดด้วยระยะจากจุดเบลอใน PHP
        $marginKm = $radiusKm + ($fuzzM * 1.5 / 1000);
        $latDelta = $marginKm / 111.0;
        $lngDelta = $marginKm / (111.0 * max(0.1, cos(deg2rad($lat))));

        $riders = $this->visibleOnMapQuery()
            ->where('user_id', '!=', $viewer->id)
            ->whereBetween('last_latitude', [$lat - $latDelta, $lat + $latDelta])
            ->whereBetween('last_longitude', [$lng - $lngDelta, $lng + $lngDelta])
            ->withExists(['jobs as has_active_job' => fn ($q) => $q->whereIn('status', RiderJob::ACTIVE_STATUSES)])
            ->with('user')
            ->limit(300)
            ->get();

        $points = [];
        foreach ($riders as $rider) {
            [$fuzzLat, $fuzzLng] = $this->fuzzedPoint($rider, $window, $fuzzM);
            $distance = DeliveryFeeCalculator::haversineKm($lat, $lng, $fuzzLat, $fuzzLng);

            if ($distance <= $radiusKm) {
                $points[] = ['rider' => $rider, 'lat' => $fuzzLat, 'lng' => $fuzzLng, 'distance' => $distance];
            }
        }

        usort($points, fn ($a, $b) => $a['distance'] <=> $b['distance'] ?: $a['rider']->id <=> $b['rider']->id);
        $points = array_slice($points, 0, self::NEARBY_LIMIT);

        $hearts = $this->heartsFromViewer($viewer, array_map(fn ($p) => (int) $p['rider']->id, $points));

        $out = [];
        foreach ($points as $point) {
            /** @var Rider $rider */
            $rider = $point['rider'];
            $mine = $hearts[(int) $rider->id] ?? 0;
            $busy = (bool) $rider->getAttribute('has_active_job') || $rider->availability === 'busy';

            $out[] = array_merge($this->personCard($rider, $viewer, $mine), [
                'approx_latitude' => $point['lat'],
                'approx_longitude' => $point['lng'],
                'distance_km' => round($point['distance'], 1),
                'status' => $busy ? 'busy' : 'available',
                'is_favorite' => $mine > 0,
            ]);
        }

        return [
            'riders' => $out,
            'radius_km' => round($radiusKm, 2),
            'fuzz_m' => $fuzzM,
            'lock_min_hearts' => $this->lockMinHearts(),
        ];
    }

    /**
     * ตำแหน่งเบลอของไรเดอร์ในช่วงเวลา $window
     *
     * 1) ปัดตำแหน่งจริงลงช่องตาราง ขนาด rider.nearby_fuzz_m (ใช้จุดกลางช่อง)
     * 2) เลื่อนจุดกลางช่องด้วยค่าที่ได้จาก HMAC(กุญแจแอป, ไรเดอร์ + ช่วงเวลา 10 นาที) ไม่เกินครึ่งช่อง
     * → ในช่วงเดียวกันได้จุดเดิมทุกครั้ง (เรียกซ้ำเพื่อเฉลี่ยหาจุดจริงไม่ได้) และไม่มีใครเดาค่าเลื่อนได้โดยไม่มีกุญแจ
     *
     * @return array{0: float, 1: float} [lat, lng]
     */
    public function fuzzedPoint(Rider $rider, ?int $window = null, ?int $fuzzM = null): array
    {
        $window ??= $this->fuzzWindow();
        $fuzzM ??= $this->fuzzMeters();

        $lat = (float) $rider->last_latitude;
        $lng = (float) $rider->last_longitude;

        $latStep = $fuzzM / 111320.0;
        $cellLat = (floor($lat / $latStep) + 0.5) * $latStep;
        $lngStep = $fuzzM / (111320.0 * max(0.1, cos(deg2rad($cellLat))));
        $cellLng = (floor($lng / $lngStep) + 0.5) * $lngStep;

        $hash = hash_hmac('sha256', 'rider-nearby|'.$rider->id.'|'.$window, $this->fuzzKey(), true);
        $u1 = unpack('N', substr($hash, 0, 4))[1] / 4294967295;
        $u2 = unpack('N', substr($hash, 4, 4))[1] / 4294967295;

        return [
            round($cellLat + ($u1 - 0.5) * $latStep, 5),
            round($cellLng + ($u2 - 0.5) * $lngStep, 5),
        ];
    }

    /**
     * หมายเลขช่วงเวลา 10 นาทีปัจจุบัน
     */
    public function fuzzWindow(): int
    {
        return intdiv(now()->getTimestamp(), self::FUZZ_WINDOW_SECONDS);
    }

    // =====================================================
    // ไรเดอร์คนโปรด + การ์ดไรเดอร์
    // =====================================================

    /**
     * ไรเดอร์ที่ฉันเคยให้หัวใจ (หัวใจมากสุดก่อน)
     *
     * @return array{riders: array<int, array<string, mixed>>, lock_min_hearts: int}
     */
    public function favorites(User $viewer): array
    {
        $rows = RiderHeart::query()
            ->where('user_id', $viewer->id)
            ->groupBy('rider_id')
            ->selectRaw('rider_id, COUNT(*) as hearts, MAX(created_at) as last_heart_at')
            ->orderByDesc('hearts')
            ->orderByDesc('last_heart_at')
            ->limit(self::FAVORITES_LIMIT)
            ->get();

        $riderIds = $rows->pluck('rider_id')->map(fn ($id) => (int) $id)->all();
        if ($riderIds === []) {
            return ['riders' => [], 'lock_min_hearts' => $this->lockMinHearts()];
        }

        $riders = Rider::with('user')->whereIn('id', $riderIds)->get()->keyBy('id');

        // งานล่าสุดที่ไรเดอร์คนนั้นส่งให้ฉันสำเร็จ
        $lastOrders = RiderJob::query()
            ->where('customer_id', $viewer->id)
            ->whereIn('rider_id', $riderIds)
            ->where('status', 'completed')
            ->groupBy('rider_id')
            ->selectRaw('rider_id, MAX(completed_at) as last_at')
            ->pluck('last_at', 'rider_id');

        $out = [];
        foreach ($rows as $row) {
            $rider = $riders->get((int) $row->rider_id);
            if (! $rider) {
                continue; // บัญชีไรเดอร์ถูกลบ
            }

            $lastAt = $lastOrders->get((int) $row->rider_id);

            $out[] = array_merge($this->personCard($rider, $viewer, (int) $row->hearts), [
                'online' => $this->isVisibleOnline($rider),
                'last_order_at' => $lastAt ? Carbon::parse($lastAt)->toIso8601String() : null,
            ]);
        }

        return ['riders' => $out, 'lock_min_hearts' => $this->lockMinHearts()];
    }

    /**
     * การ์ดไรเดอร์ 1 คน
     *
     * ดูได้เมื่อ: เป็นตัวเอง / เคยให้หัวใจ / ไรเดอร์เคยหรือกำลังส่งของให้ฉัน / ไรเดอร์กำลังแสดงบนแผนที่ใกล้ฉัน
     * (คนแปลกหน้าไล่เลข id ดูไรเดอร์ที่ปิดการแสดงตัวไม่ได้)
     *
     * @return array<string, mixed>
     *
     * @throws RiderSocialException RIDER_NOT_FOUND (404)
     */
    public function card(User $viewer, int $riderId): array
    {
        $rider = Rider::with('user')->find($riderId);

        if (! $rider || ! $this->viewerCanSeeCard($viewer, $rider)) {
            throw RiderSocialException::make('RIDER_NOT_FOUND', 'ไม่พบไรเดอร์', 404);
        }

        return array_merge($this->personCard($rider, $viewer), [
            'completed_jobs' => (int) $rider->completed_jobs,
            'joined_at' => ($rider->approved_at ?? $rider->created_at)?->toIso8601String(),
        ]);
    }

    /**
     * PersonCard ของไรเดอร์ (ไม่มีพิกัด/เบอร์โทร/ทะเบียนเต็ม)
     *
     * @return array{id: int, display_name: string, photo_url: ?string, verified: bool, vehicle_type: ?string, vehicle_label: string,
     *               plate_masked: ?string, hearts_total: int, hearts_from_me: int, can_lock: bool}
     */
    public function personCard(Rider $rider, ?User $viewer, ?int $heartsFromMe = null): array
    {
        $heartsFromMe ??= $viewer ? $this->pairHearts((int) $rider->id, (int) $viewer->id) : 0;
        $user = $rider->relationLoaded('user') ? $rider->user : $rider->user()->first();
        $isSelf = $viewer !== null && (int) $rider->user_id === (int) $viewer->id;

        return [
            'id' => (int) $rider->id,
            'display_name' => self::displayName($rider->full_name),
            'photo_url' => $user ? $this->photoUrl($user, $viewer) : null,
            // 🪪 ป้ายทอง "ยืนยันตัวตนแล้ว" (AI eKYC / แอดมินอนุมัติ)
            'verified' => EkycService::badge($user),
            'vehicle_type' => $rider->vehicle_type,
            'vehicle_label' => $this->vehicleLabel($rider),
            'plate_masked' => self::maskPlate($rider->vehicle_plate),
            'hearts_total' => (int) $rider->hearts_count,
            'hearts_from_me' => $heartsFromMe,
            'can_lock' => $viewer !== null && ! $isSelf && $this->canLock($rider, $heartsFromMe),
        ];
    }

    // =====================================================
    // ข้อมูลเสริมในงานไรเดอร์ (RiderJob::toApiSummary)
    // =====================================================

    /**
     * คีย์ที่เลน social เพิ่มใน RiderJob::toApiSummary()
     *
     * - locked_by_buyer: งานนี้ผู้ซื้อล็อกเรียกไรเดอร์ที่กำลังดูอยู่ (ผู้ดู null = แอดมิน/เจ้าของออเดอร์ → มีการล็อกหรือไม่)
     * - buyer: การ์ดผู้ซื้อ — เฉพาะไรเดอร์ที่รับงานแล้ว หรือไรเดอร์ที่ได้ข้อเสนอเฉพาะตัวจากการล็อก และงานยังไม่จบ
     *
     * @return array{locked_by_buyer: bool, buyer: array{display_name: string, photo_url: ?string, hearts_given: int}|null}
     */
    public function jobSummaryKeys(RiderJob $job, ?Rider $viewer): array
    {
        $preferred = $job->preferred_rider_id !== null ? (int) $job->preferred_rider_id : null;

        return [
            'locked_by_buyer' => $preferred !== null && ($viewer === null || $preferred === (int) $viewer->id),
            'buyer' => $viewer ? $this->buyerCardFor($job, $viewer) : null,
        ];
    }

    // =====================================================
    // ตัวช่วยแสดงผล
    // =====================================================

    /**
     * ชื่อแสดงผล = ชื่อต้น + อักษรแรกของนามสกุล เช่น "สมชาย ใจดี" → "สมชาย จ." · "John Smith" → "John S."
     *
     * ภาษาไทยข้ามสระหน้า (เ แ โ ใ ไ) ใช้พยัญชนะตัวแรก · ตัดคำนำหน้าที่เขียนแยกคำ (นาย/นาง/นางสาว/Mr.)
     */
    public static function displayName(?string $fullName): string
    {
        $name = trim((string) preg_replace('/\s+/u', ' ', (string) $fullName));
        if ($name === '') {
            return 'ไม่ระบุชื่อ';
        }

        $parts = explode(' ', $name);
        while (count($parts) > 1 && in_array(mb_strtolower($parts[0]), self::NAME_TITLES, true)) {
            array_shift($parts);
        }

        // คำนำหน้าแบบย่อที่เขียนติดชื่อ เช่น "น.ส.สมหญิง"
        $first = (string) preg_replace('/^(น\.ส\.|ด\.ช\.|ด\.ญ\.)/u', '', $parts[0]);
        if ($first === '') {
            $first = $parts[0];
        }

        if (count($parts) < 2) {
            return $first;
        }

        $initial = self::initialOf($parts[count($parts) - 1]);

        return $initial !== '' ? $first.' '.$initial.'.' : $first;
    }

    /**
     * ทะเบียนรถแบบปิดบัง — ตัวเลขทุกตัวยกเว้น 2 ตัวท้ายเป็น • เช่น "1กข 1234" → "•กข ••34"
     */
    public static function maskPlate(?string $plate): ?string
    {
        $plate = trim((string) $plate);
        if ($plate === '') {
            return null;
        }

        $chars = mb_str_split($plate);
        $digits = count(array_filter($chars, fn ($c) => ctype_digit($c)));
        $seen = 0;
        $out = '';

        foreach ($chars as $char) {
            if (ctype_digit($char)) {
                $seen++;
                $out .= $seen > $digits - 2 ? $char : '•';
            } else {
                $out .= $char;
            }
        }

        return $out;
    }

    // =====================================================
    // ภายใน
    // =====================================================

    /**
     * ไรเดอร์ที่ "แสดงตัวบนแผนที่" ได้ตอนนี้: อนุมัติ ไม่ถูกระงับ ออนไลน์/กำลังส่ง เปิด show_on_nearby
     * ยินยอมแชร์ตำแหน่ง พิกัดสด เป็นไรเดอร์ส่งของ และบัญชีผู้ใช้ไม่ถูกบล็อก
     *
     * @return \Illuminate\Database\Eloquent\Builder<Rider>
     */
    private function visibleOnMapQuery()
    {
        $freshMinutes = max(1, $this->config->intSetting('rider.location_fresh_minutes'));

        return Rider::query()
            ->approved()
            ->whereNull('suspended_at')
            ->whereIn('availability', ['online', 'busy'])
            ->where('show_on_nearby', true)
            ->whereNotNull('share_location_consent_at')
            ->whereNotNull('last_latitude')
            ->whereNotNull('last_longitude')
            ->where('last_location_update', '>=', now()->subMinutes($freshMinutes))
            ->where(function ($q) {
                $q->whereIn('rider_type', ['delivery', 'both'])->orWhereNull('rider_type');
            })
            ->whereHas('user', fn ($q) => $q->whereNull('blocked_at'));
    }

    /**
     * ไรเดอร์คนนี้กำลังแสดงตัวบนแผนที่อยู่หรือไม่ (ใช้เป็น "ออนไลน์" ในรายการคนโปรด)
     */
    private function isVisibleOnline(Rider $rider): bool
    {
        return $this->visibleOnMapQuery()->whereKey($rider->id)->exists();
    }

    private function viewerCanSeeCard(User $viewer, Rider $rider): bool
    {
        if ((int) $rider->user_id === (int) $viewer->id) {
            return true;
        }

        if (RiderHeart::where('rider_id', $rider->id)->where('user_id', $viewer->id)->exists()) {
            return true;
        }

        if (RiderJob::where('rider_id', $rider->id)->where('customer_id', $viewer->id)->exists()) {
            return true;
        }

        return $this->isVisibleOnline($rider);
    }

    /**
     * การ์ดผู้ซื้อให้ไรเดอร์ (ชื่อย่อ + รูปลายน้ำ + หัวใจที่ผู้ซื้อคนนี้เคยให้ไรเดอร์คนนี้)
     *
     * @return array{display_name: string, photo_url: ?string, verified: bool, hearts_given: int}|null
     */
    private function buyerCardFor(RiderJob $job, Rider $viewer): ?array
    {
        if (! $job->customer_id || $job->isTerminal()) {
            return null;
        }

        $assigned = $job->rider_id !== null && (int) $job->rider_id === (int) $viewer->id;
        $lockedOffer = $job->isOpen()
            && $job->preferred_rider_id !== null
            && (int) $job->preferred_rider_id === (int) $viewer->id
            && $job->preferred_until !== null
            && $job->preferred_until->isFuture();

        if (! $assigned && ! $lockedOffer) {
            return null;
        }

        $buyer = User::find((int) $job->customer_id);
        if (! $buyer) {
            return null;
        }

        $viewerUser = $viewer->relationLoaded('user') ? $viewer->user : $viewer->user()->first();

        return [
            'display_name' => self::displayName($buyer->name),
            'photo_url' => $this->photoUrl($buyer, $viewerUser),
            'verified' => EkycService::badge($buyer),
            'hearts_given' => $this->pairHearts((int) $viewer->id, (int) $buyer->id),
        ];
    }

    /**
     * ออเดอร์ของผู้ซื้อคนนี้ (ไม่ใช่ของตัวเอง/ไม่มี → null)
     */
    private function findBuyerOrder(User $buyer, string $source, int $orderId): ?Model
    {
        return match ($source) {
            'shop' => Order::where('id', $orderId)->where('user_id', $buyer->id)->first(),
            'fresh-market' => FreshMarketOrder::where('id', $orderId)->where('buyer_id', $buyer->id)->first(),
            default => null,
        };
    }

    /**
     * งานไรเดอร์ของออเดอร์ที่ส่งสำเร็จแล้ว (ล่าสุด) — ตลาดสดดูลิงก์เก่า rider_job_id ด้วย
     */
    private function completedJobFor(Model $order): ?RiderJob
    {
        $job = RiderJob::forSource($order)
            ->where('status', 'completed')
            ->whereNotNull('rider_id')
            ->latest('id')
            ->first();

        if (! $job && $order instanceof FreshMarketOrder && $order->rider_job_id) {
            $job = RiderJob::whereKey($order->rider_job_id)
                ->where('status', 'completed')
                ->whereNotNull('rider_id')
                ->first();
        }

        return $job;
    }

    /**
     * หัวใจที่ผู้ดูให้ไรเดอร์แต่ละคน (query เดียว)
     *
     * @param  array<int, int>  $riderIds
     * @return array<int, int> rider_id => จำนวน
     */
    private function heartsFromViewer(User $viewer, array $riderIds): array
    {
        if ($riderIds === []) {
            return [];
        }

        return RiderHeart::query()
            ->where('user_id', $viewer->id)
            ->whereIn('rider_id', $riderIds)
            ->groupBy('rider_id')
            ->selectRaw('rider_id, COUNT(*) as hearts')
            ->pluck('hearts', 'rider_id')
            ->map(fn ($n) => (int) $n)
            ->all();
    }

    /**
     * รูปโปรไฟล์ลายน้ำ (เลน photo) — พังต้องไม่ทำให้ทั้งรายการพัง
     */
    private function photoUrl(User $subject, ?User $viewer): ?string
    {
        try {
            return $this->photos->urlFor($subject, $viewer);
        } catch (\Throwable $e) {
            Log::warning('RiderSocial: photo url failed', ['user_id' => $subject->id, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * ป้ายยานพาหนะ เช่น "มอเตอร์ไซค์ Honda แดง"
     */
    private function vehicleLabel(Rider $rider): string
    {
        return trim(implode(' ', array_filter([
            $rider->vehicle_type_text,
            trim((string) $rider->vehicle_brand),
            trim((string) $rider->vehicle_color),
        ], fn ($part) => $part !== '')));
    }

    private function fuzzMeters(): int
    {
        return max(50, $this->config->intSetting('rider.nearby_fuzz_m'));
    }

    /**
     * กุญแจลับสำหรับค่าเลื่อนตำแหน่งเบลอ (มาจาก APP_KEY — คนนอกคำนวณย้อนไม่ได้)
     */
    private function fuzzKey(): string
    {
        return hash('sha256', 'rider-nearby-fuzz|'.(string) config('app.key'));
    }

    /**
     * อักษรย่อของคำ: ไทย = พยัญชนะตัวแรก (ข้ามสระหน้า) · อื่นๆ = ตัวแรกพิมพ์ใหญ่
     */
    private static function initialOf(string $word): string
    {
        if (preg_match('/^[\x{0E00}-\x{0E7F}]/u', $word)) {
            if (preg_match('/[ก-ฮ]/u', $word, $m)) {
                return $m[0];
            }

            return mb_substr($word, 0, 1);
        }

        return mb_strtoupper(mb_substr($word, 0, 1));
    }
}
