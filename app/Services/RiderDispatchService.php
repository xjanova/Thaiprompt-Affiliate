<?php

namespace App\Services;

use App\Contracts\RiderDeliverable;
use App\Exceptions\RiderJobException;
use App\Jobs\CascadeRiderDispatchJob;
use App\Models\Rider;
use App\Models\RiderJob;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Rider Dispatch Service — สร้างงานไรเดอร์จากออเดอร์ และกระจายงานให้ไรเดอร์
 *
 * จุดเข้าเดียวสำหรับทุกระบบ (ตลาดสด, ร้านค้า e-commerce ฯลฯ):
 *   createJobForSource($order, 'fresh_market' | 'shop_delivery')
 *   cancelJobsForSource($order, 'buyer' | 'seller' | 'admin' | 'system', $reason)
 *
 * โหมดกระจายงาน (Setting rider.dispatch_mode):
 *   - broadcast (ค่าเริ่มต้น): แจ้งไรเดอร์ที่เข้าเงื่อนไขทุกคนในรัศมี คนแรกที่กดรับได้งาน (race-safe)
 *   - cascade: เสนอทีละคน รอ rider.offer_timeout_seconds แล้วเลื่อนไปคนถัดไป
 *
 * ไรเดอร์ที่เข้าเงื่อนไข: อนุมัติแล้ว + ไม่ถูกระงับ + online + พิกัดสดไม่เกิน 15 นาที + อยู่ในรัศมี
 * + ไม่มีงานค้าง + ไม่ใช่ผู้ซื้อ/ผู้ขายของออเดอร์ + วงเงิน COD พอ + (มัดจำ ถ้าเปิด rider.require_deposit)
 *
 * แจ้งเตือนไรเดอร์ผ่าน Expo push + in-app เท่านั้น ❗ ห้าม LINE push (โควต้า 300/เดือน)
 */
class RiderDispatchService
{
    public function __construct(
        private readonly DeliveryFeeCalculator $config,
        private readonly RiderNotificationService $notifier,
    ) {}

    // =====================================================
    // สร้าง / ยกเลิกงานจากออเดอร์ต้นทาง
    // =====================================================

    /**
     * สร้างงานไรเดอร์ (pending, ยังไม่มีไรเดอร์) จากออเดอร์ แล้วกระจายงาน
     *
     * idempotent: ถ้าออเดอร์นี้มีงานที่ยังไม่จบอยู่แล้ว → คืนงานเดิม ไม่สร้างซ้ำ
     *
     * ออเดอร์ต้นทางกำหนดเพิ่มได้ (ไม่บังคับ):
     *   - riderDeliveryFeeCharged(): ?float  ค่าส่งที่ลูกค้าจ่ายจริง → ใช้เป็น total_fee แทนการคำนวณใหม่
     *   - riderCustomerUserId(): ?int         user_id ผู้ซื้อ (ไม่มี → ใช้ buyer_id / user_id / customer_id)
     *   - key 'area' ใน riderDropoffPoint()   พื้นที่หยาบที่โชว์ไรเดอร์ก่อนรับงาน (เช่น "เขตบางรัก กรุงเทพฯ")
     *   - riderCanDispatch(): bool             ยังเรียกไรเดอร์ได้ไหม (ตรวจซ้ำหลังล็อกแถวออเดอร์ — กันงานของออเดอร์ที่ยกเลิกแล้ว)
     *   - onRiderCodSettled(RiderJob): void    RiderEarningService เรียกหลังหักเงิน COD จากไรเดอร์เข้าระบบสำเร็จ
     *
     * @param  string  $jobType  fresh_market | shop_delivery | delivery ...
     *
     * @throws RiderJobException SOURCE_NOT_DISPATCHABLE เมื่อออเดอร์ยกเลิก/คืนเงิน/ส่งถึงแล้ว/ยังไม่จ่าย
     * @throws RiderJobException INVALID_LOCATION|OUT_OF_SERVICE_AREA|COD_LIMIT_EXCEEDED
     */
    public function createJobForSource(RiderDeliverable&Model $source, string $jobType): RiderJob
    {
        $existing = RiderJob::forSource($source)->nonTerminal()->latest('id')->first();
        if ($existing) {
            return $existing;
        }

        // ตรวจก่อนคำนวณค่าส่ง (ตอบเร็ว) แล้วตรวจซ้ำหลังล็อกแถวออเดอร์ด้านล่าง
        if (! $this->sourceCanDispatch($source)) {
            throw RiderJobException::sourceNotDispatchable();
        }

        $pickup = $this->normalizePoint($source->riderPickupPoint(), 'จุดรับของ');
        $dropoff = $this->normalizePoint($source->riderDropoffPoint(), 'จุดส่งของ');

        $quote = $this->config->quote($pickup['latitude'], $pickup['longitude'], $dropoff['latitude'], $dropoff['longitude']);

        if (! $quote['within_service_area']) {
            throw RiderJobException::outOfServiceArea($quote['distance_km'], $quote['max_distance_km']);
        }

        $fees = $this->applyChargedFee($source, $quote);

        $cod = round(max(0.0, $source->riderCodAmount()), 2);
        if ($cod > $this->config->maxCodAmount()) {
            throw RiderJobException::codLimitExceeded($cod, $this->config->maxCodAmount());
        }

        $customerId = $this->resolveCustomerId($source);
        $orderRef = (string) (data_get($source, 'order_number') ?: '#'.$source->getKey());
        $jobType = trim($jobType) !== '' ? mb_substr(trim($jobType), 0, 30) : 'delivery';

        [$job, $created] = DB::transaction(function () use ($source, $jobType, $pickup, $dropoff, $quote, $fees, $cod, $customerId, $orderRef) {
            // ล็อกแถวออเดอร์ต้นทาง → คำขอพร้อมกัน 2 ครั้งจะไม่สร้างงานซ้ำ
            $lockedSource = $source->newQuery()->whereKey($source->getKey())->lockForUpdate()->first();

            $existing = RiderJob::forSource($source)->nonTerminal()->latest('id')->first();
            if ($existing) {
                return [$existing, false];
            }

            // ตรวจซ้ำหลังล็อก: ออเดอร์อาจถูกยกเลิก/คืนเงิน/จ่ายเงินระหว่างทาง (race กับคำสั่งยกเลิก)
            if (! $lockedSource || ! $this->sourceCanDispatch($lockedSource)) {
                throw RiderJobException::sourceNotDispatchable();
            }

            // ยอด COD ล่าสุดหลังล็อก (เช่น ออเดอร์เพิ่งถูกตั้งจ่ายแล้ว → ไม่ต้องเก็บเงินสด)
            if ($lockedSource instanceof RiderDeliverable) {
                $cod = round(max(0.0, $lockedSource->riderCodAmount()), 2);
                if ($cod > $this->config->maxCodAmount()) {
                    throw RiderJobException::codLimitExceeded($cod, $this->config->maxCodAmount());
                }
            }

            $job = new RiderJob;
            $job->fill([
                'job_type' => $jobType,
                'source_type' => $source->getMorphClass(),
                'source_id' => $source->getKey(),
                'title' => mb_substr((new RiderJob(['job_type' => $jobType]))->job_type_text.' '.$orderRef, 0, 255),
                'description' => $source->riderItemsSummary(),
                'pickup_address' => $pickup['address'],
                'pickup_latitude' => $pickup['latitude'],
                'pickup_longitude' => $pickup['longitude'],
                'pickup_contact_name' => $pickup['name'],
                'pickup_contact_phone' => $pickup['phone'],
                'pickup_notes' => $pickup['notes'],
                'delivery_address' => $dropoff['address'],
                'delivery_latitude' => $dropoff['latitude'],
                'delivery_longitude' => $dropoff['longitude'],
                'delivery_contact_name' => $dropoff['name'],
                'delivery_contact_phone' => $dropoff['phone'],
                'delivery_notes' => $dropoff['notes'],
                'delivery_area' => $dropoff['area'] ?: RiderJob::deriveArea($dropoff['address']),
                'distance_km' => $quote['distance_km'],
                'estimated_duration_minutes' => $quote['estimated_duration_minutes'],
                'base_fee' => $fees['base_fee'],
                'distance_fee' => $fees['distance_fee'],
                'extra_fee' => 0,
                'total_fee' => $fees['total_fee'],
                'rider_earnings' => $fees['rider_earnings'],
                'platform_fee' => $fees['platform_fee'],
                'cod_amount' => $cod,
                'customer_id' => $customerId,
                'status' => 'pending',
                'dispatch_type' => $this->config->dispatchMode(),
                'dispatch_radius_km' => $this->config->floatSetting('rider.offer_radius_km'),
                'dispatch_round' => 0,
                'tracking_token' => Str::random(48),
                'tracking_expires_at' => now()->addHours(max(1, $this->config->intSetting('rider.tracking_expiry_hours'))),
                'gps_active' => true,
                'gps_warning_count' => 0,
            ]);
            $job->save();

            return [$job, true];
        });

        if (! $created) {
            return $job;
        }

        Log::info('RiderDispatch: job created', [
            'job_id' => $job->id,
            'source' => $job->source_type.'#'.$job->source_id,
            'total_fee' => $fees['total_fee'],
            'cod' => $cod,
        ]);

        // กระจายงานหลัง commit (ถ้าผู้เรียกเปิด transaction ไว้ จะรอจน commit จริง)
        $jobId = $job->id;
        $initialDispatch = function () use ($jobId) {
            try {
                $fresh = RiderJob::find($jobId);
                if ($fresh) {
                    $this->dispatch($fresh);
                }
            } catch (\Throwable $e) {
                Log::error('RiderDispatch: initial dispatch failed (sweep will retry)', [
                    'job_id' => $jobId,
                    'error' => $e->getMessage(),
                ]);
            }
        };

        try {
            DB::afterCommit($initialDispatch);
        } catch (\RuntimeException) {
            $initialDispatch();
        }

        return $job;
    }

    /**
     * ออเดอร์ต้นทางเรียกไรเดอร์ได้หรือไม่ — ใช้ hook เสริม riderCanDispatch(): bool ของออเดอร์ (ถ้ามี)
     *
     * ออเดอร์ที่ไม่ได้กำหนด hook ถือว่าเรียกได้ (เช่น ต้นทางทดสอบ) · ใช้ร่วมกับหน้าแอดมินเพื่อซ่อนปุ่ม "สร้างงานใหม่"
     */
    public function sourceCanDispatch(Model $source): bool
    {
        if (method_exists($source, 'riderCanDispatch')) {
            return (bool) $source->riderCanDispatch();
        }

        return true;
    }

    /**
     * ยกเลิกงานไรเดอร์ทั้งหมดของออเดอร์ (เรียกเมื่อออเดอร์ถูกยกเลิก)
     *
     * - ยังไม่รับของ → cancelled
     * - รับของแล้ว → failed (order_cancelled) + แจ้งไรเดอร์นำของคืนร้าน + แจ้งแอดมิน
     *
     * ไม่เรียก onRiderJobStatusChanged กลับไปที่ออเดอร์ (ออเดอร์เป็นคนสั่งเอง กันวนซ้ำ)
     *
     * @param  string  $cancelledBy  buyer|seller|admin|system|customer
     */
    public function cancelJobsForSource(Model $source, string $cancelledBy, string $reason): void
    {
        $jobs = RiderJob::forSource($source)->nonTerminal()->get();
        $service = app(RiderJobService::class);

        foreach ($jobs as $job) {
            try {
                if (in_array($job->status, ['pending', 'accepted', 'picking_up'], true)) {
                    $service->cancel($job, $cancelledBy, $reason, false);
                } elseif (in_array($job->status, ['picked_up', 'delivering'], true)) {
                    $service->failForCancelledSource($job, $reason);
                }
            } catch (RiderJobException $e) {
                // สถานะเปลี่ยนไประหว่างทาง (เช่น ส่งเสร็จพอดี) → ข้าม ไม่ล้มการยกเลิกออเดอร์
                Log::warning('RiderDispatch: cannot cancel job for source', [
                    'job_id' => $job->id,
                    'code' => $e->errorCode,
                ]);
            }
        }
    }

    // =====================================================
    // กระจายงาน
    // =====================================================

    /**
     * กระจายงานตามโหมดของงาน
     *
     * @param  bool  $isRedispatch  true = ไรเดอร์คืนงาน/ถูกระงับ → เริ่มรอบใหม่ (ไม่นับรอบเดิม)
     */
    public function dispatch(RiderJob $job, bool $isRedispatch = false): int
    {
        if (! $job->isOpen()) {
            return 0;
        }

        if ($isRedispatch) {
            RiderJob::whereKey($job->id)->open()->update([
                'dispatch_round' => 0,
                'dispatch_radius_km' => $this->config->floatSetting('rider.offer_radius_km'),
                'current_offer_rider_id' => null,
                'offer_expires_at' => null,
            ]);
            $job->refresh();
        }

        $mode = $job->dispatch_type === 'cascade' ? 'cascade' : $this->config->dispatchMode();

        if ($mode === 'cascade') {
            return $this->cascadeOffer($job) ? 1 : 0;
        }

        return $this->broadcast($job);
    }

    /**
     * Broadcast: แจ้งไรเดอร์ที่เข้าเงื่อนไขทุกคนในรัศมี (ข้ามคนที่เคยแจ้งแล้ว)
     *
     * @return int จำนวนไรเดอร์ที่แจ้งรอบนี้
     */
    public function broadcast(RiderJob $job, ?float $radiusKm = null): int
    {
        $radiusKm ??= (float) ($job->dispatch_radius_km ?: $this->config->floatSetting('rider.offer_radius_km'));

        $riders = $this->findEligibleRiders($job, $radiusKm);
        $already = array_map('intval', $job->candidate_riders ?? []);
        $newRiders = $riders->reject(fn (Rider $r) => in_array((int) $r->id, $already, true))->values();

        $candidates = array_values(array_unique(array_merge($already, $newRiders->pluck('id')->map(fn ($id) => (int) $id)->all())));

        $updated = RiderJob::whereKey($job->id)->open()->update([
            'candidate_riders' => json_encode($candidates),
            'dispatch_radius_km' => round($radiusKm, 2),
            'dispatch_round' => (int) $job->dispatch_round + 1,
            'last_dispatched_at' => now(),
            'dispatch_type' => $job->dispatch_type === 'manual_needed' ? 'manual_needed' : 'broadcast',
        ]);

        if ($updated === 0) {
            return 0; // มีคนรับไปแล้วระหว่างค้นหา
        }

        $job->refresh();

        if ($newRiders->isNotEmpty()) {
            $this->notifier->notifyRidersNewJob($newRiders, $job);
        }

        Log::info('RiderDispatch: broadcast', [
            'job_id' => $job->id,
            'round' => $job->dispatch_round,
            'radius_km' => $radiusKm,
            'notified' => $newRiders->count(),
        ]);

        return $newRiders->count();
    }

    /**
     * Cascade: เสนองานให้ไรเดอร์ที่ใกล้ที่สุดที่ยังไม่เคยเสนอ 1 คน
     *
     * @return bool เสนอได้หรือไม่ (false = ไม่มีใครเหลือในรัศมีนี้ — sweep จะขยายรัศมี)
     */
    public function cascadeOffer(RiderJob $job, ?float $radiusKm = null): bool
    {
        $radiusKm ??= (float) ($job->dispatch_radius_km ?: $this->config->floatSetting('rider.offer_radius_km'));
        $timeout = max(30, $this->config->intSetting('rider.offer_timeout_seconds'));

        $attempted = $job->notifiedRiderIds();
        $next = $this->findEligibleRiders($job, $radiusKm)
            ->first(fn (Rider $r) => ! in_array((int) $r->id, $attempted, true));

        $offered = DB::transaction(function () use ($job, $next, $radiusKm, $timeout) {
            /** @var RiderJob|null $locked */
            $locked = RiderJob::whereKey($job->id)->lockForUpdate()->first();
            if (! $locked || ! $locked->isOpen()) {
                return false;
            }

            $locked->fill([
                'dispatch_type' => $locked->dispatch_type === 'manual_needed' ? 'manual_needed' : 'cascade',
                'dispatch_radius_km' => round($radiusKm, 2),
                'dispatch_round' => (int) $locked->dispatch_round + ($next ? 0 : 1),
                'last_dispatched_at' => now(),
            ]);

            if (! $next) {
                $locked->save();

                return false;
            }

            $attempts = $locked->dispatch_attempts ?? [];
            $attempts[] = ['rider_id' => (int) $next->id, 'sent_at' => now()->toIso8601String(), 'status' => 'pending'];

            $locked->fill([
                'current_offer_rider_id' => $next->id,
                'offer_sent_at' => now(),
                'offer_expires_at' => now()->addSeconds($timeout),
                'dispatch_attempts' => $attempts,
                'candidate_riders' => array_values(array_unique(array_merge(array_map('intval', $locked->candidate_riders ?? []), [(int) $next->id]))),
            ])->save();

            return true;
        });

        if (! $offered || ! $next) {
            return false;
        }

        $job->refresh();
        $this->notifier->notifyRidersNewJob([$next], $job);

        CascadeRiderDispatchJob::dispatch($job->id)->delay(now()->addSeconds($timeout + 2));

        Log::info('RiderDispatch: cascade offer', ['job_id' => $job->id, 'rider_id' => $next->id, 'timeout' => $timeout]);

        return true;
    }

    /**
     * offer (cascade) หมดเวลา → บันทึกว่าหมดเวลาแล้วเสนอคนถัดไป
     */
    public function handleOfferTimeout(RiderJob $job): void
    {
        $job->refresh();

        if (! $job->isOpen() || $job->dispatch_type !== 'cascade' || ! $job->isOfferExpired()) {
            return;
        }

        if ($job->current_offer_rider_id) {
            $job->recordOfferResponse((int) $job->current_offer_rider_id, 'expired');
        }

        $this->cascadeOffer($job->fresh());
    }

    /**
     * ไรเดอร์ปฏิเสธงาน (จากปุ่มในแอปหรือ LINE postback)
     */
    public function handleRiderReject(RiderJob $job, Rider $rider): void
    {
        $job->refresh();
        if (! $job->isOpen()) {
            return;
        }

        if ($job->dispatch_type === 'cascade' && (int) $job->current_offer_rider_id === (int) $rider->id) {
            $job->recordOfferResponse((int) $rider->id, 'rejected');
            $this->cascadeOffer($job->fresh());

            return;
        }

        // broadcast: จดไว้ว่าไม่สนใจ (ไม่แจ้งซ้ำรอบขยายรัศมี)
        $attempts = $job->dispatch_attempts ?? [];
        $attempts[] = ['rider_id' => (int) $rider->id, 'status' => 'rejected', 'responded_at' => now()->toIso8601String()];
        RiderJob::whereKey($job->id)->open()->update(['dispatch_attempts' => json_encode($attempts)]);

        Log::info('RiderDispatch: rider rejected job', ['job_id' => $job->id, 'rider_id' => $rider->id]);
    }

    /**
     * งานที่ไรเดอร์คนนี้เห็นในหน้า "งานที่รอรับ" (กรองเงื่อนไขเดียวกับตอนกระจายงาน)
     *
     * @return Collection<int, RiderJob>
     */
    public function availableJobsFor(Rider $rider, ?float $latitude = null, ?float $longitude = null, int $limit = 20): Collection
    {
        $lat = $latitude ?? ($rider->last_latitude !== null ? (float) $rider->last_latitude : null);
        $lng = $longitude ?? ($rider->last_longitude !== null ? (float) $rider->last_longitude : null);

        if ($lat === null || $lng === null || ! DeliveryFeeCalculator::isValidCoordinate($lat, $lng)) {
            return new Collection;
        }

        $maxRadius = max(
            $this->config->floatSetting('rider.max_offer_radius_km'),
            $this->config->floatSetting('rider.offer_radius_km')
        );
        $latDelta = $maxRadius / 111.0;
        $lngDelta = $maxRadius / (111.0 * max(0.1, cos(deg2rad($lat))));

        $walletBalance = $rider->walletBalance();

        $jobs = RiderJob::query()
            ->open()
            ->whereBetween('pickup_latitude', [$lat - $latDelta, $lat + $latDelta])
            ->whereBetween('pickup_longitude', [$lng - $lngDelta, $lng + $lngDelta])
            ->where(function ($q) use ($rider) {
                // cascade: เห็นเฉพาะงานที่กำลังเสนอให้ตัวเอง
                $q->where('dispatch_type', '!=', 'cascade')
                    ->orWhereNull('current_offer_rider_id')
                    ->orWhere('current_offer_rider_id', $rider->id);
            })
            ->orderBy('created_at')
            ->limit(200)
            ->get();

        $filtered = $jobs->filter(function (RiderJob $job) use ($rider, $lat, $lng, $walletBalance) {
            if (in_array((int) $rider->id, $job->releasedRiderIds(), true)) {
                return false;
            }

            if (in_array((int) $rider->user_id, $job->partyUserIds(), true)) {
                return false;
            }

            $radius = max((float) ($job->dispatch_radius_km ?? 0), $this->config->floatSetting('rider.offer_radius_km'));
            $distance = DeliveryFeeCalculator::haversineKm($lat, $lng, (float) $job->pickup_latitude, (float) $job->pickup_longitude);
            if ($distance > $radius) {
                return false;
            }

            $required = round((float) $job->cod_amount - (float) $job->rider_earnings, 2);
            if ((float) $job->cod_amount > 0 && $required > 0 && $walletBalance < $required) {
                return false;
            }

            $job->setAttribute('distance_to_rider_km', round($distance, 2));

            return true;
        });

        return $filtered
            ->sortBy(fn (RiderJob $job) => $job->getAttribute('distance_to_rider_km'))
            ->take($limit)
            ->values();
    }

    /**
     * รอบกวาดงานค้าง (rider:sweep-pending ทุกนาที)
     *
     * - งาน cascade ที่ offer หมดเวลา → เสนอคนถัดไป
     * - ยังไม่มีคนรับ → ทุก rider.rebroadcast_interval_minutes ขยายรัศมี ×1.5 (ไม่เกิน rider.max_offer_radius_km)
     *   ได้ไม่เกิน rider.max_dispatch_rounds รอบ
     * - รอเกิน rider.pending_timeout_minutes → dispatch_type = manual_needed + แจ้งแอดมิน/ผู้ซื้อ/ผู้ขาย
     *
     * @return array{rebroadcast: int, escalated: int, cascade_moved: int}
     */
    public function sweepPending(int $limit = 100): array
    {
        $stats = ['rebroadcast' => 0, 'escalated' => 0, 'cascade_moved' => 0];

        $interval = max(1, $this->config->intSetting('rider.rebroadcast_interval_minutes'));
        $maxRounds = max(1, $this->config->intSetting('rider.max_dispatch_rounds'));
        $timeout = max(1, $this->config->intSetting('rider.pending_timeout_minutes'));
        $baseRadius = max(0.5, $this->config->floatSetting('rider.offer_radius_km'));
        $maxRadius = max($baseRadius, $this->config->floatSetting('rider.max_offer_radius_km'));

        $jobs = RiderJob::query()
            ->open()
            ->where(function ($q) {
                $q->whereNull('dispatch_type')->orWhere('dispatch_type', '!=', 'manual_needed');
            })
            ->orderBy('id')
            ->limit($limit)
            ->get();

        foreach ($jobs as $job) {
            try {
                if ($job->created_at && $job->created_at->lte(now()->subMinutes($timeout))) {
                    if ($this->escalateNoRider($job)) {
                        $stats['escalated']++;
                    }

                    continue;
                }

                if ($job->dispatch_type === 'cascade' && $job->current_offer_rider_id && $job->isOfferExpired()) {
                    $this->handleOfferTimeout($job);
                    $stats['cascade_moved']++;

                    continue;
                }

                $due = $job->last_dispatched_at === null || $job->last_dispatched_at->lte(now()->subMinutes($interval));
                if (! $due || (int) $job->dispatch_round >= $maxRounds) {
                    continue;
                }

                // รอบแรก (ยังไม่เคยกระจาย) ใช้รัศมีตั้งต้น รอบถัดไปขยาย ×1.5 (อย่างน้อย +1 กม.)
                $current = (float) ($job->dispatch_radius_km ?: $baseRadius);
                $radius = (int) $job->dispatch_round === 0
                    ? $current
                    : min($maxRadius, max($current * 1.5, $current + 1.0));

                if ($job->dispatch_type === 'cascade') {
                    if (! $job->hasPendingOffer()) {
                        $this->cascadeOffer($job, $radius);
                    }
                } else {
                    $this->broadcast($job, $radius);
                }

                $stats['rebroadcast']++;
            } catch (\Throwable $e) {
                Log::error('RiderDispatch: sweep error', ['job_id' => $job->id, 'error' => $e->getMessage()]);
            }
        }

        return $stats;
    }

    /**
     * หาไรเดอร์ไม่ได้ในเวลาที่กำหนด → ส่งต่อให้แอดมินจัดการเอง (ทำครั้งเดียวต่องาน)
     */
    public function escalateNoRider(RiderJob $job): bool
    {
        $updated = RiderJob::whereKey($job->id)
            ->open()
            ->where(function ($q) {
                $q->whereNull('dispatch_type')->orWhere('dispatch_type', '!=', 'manual_needed');
            })
            ->update([
                'dispatch_type' => 'manual_needed',
                'current_offer_rider_id' => null,
                'offer_expires_at' => null,
            ]);

        if ($updated === 0) {
            return false;
        }

        $job->refresh();

        Log::warning('RiderDispatch: no rider found, escalated to admin', ['job_id' => $job->id]);

        $this->notifier->notifyAdmins(
            'ไม่มีไรเดอร์รับงาน',
            "งาน #{$job->job_number} รอเกิน ".$this->config->intSetting('rider.pending_timeout_minutes').' นาที กรุณามอบหมายไรเดอร์',
            ['job_id' => (int) $job->id]
        );

        $this->notifier->notifyParties($job->partyUserIds(), $job, 'no_rider');

        return true;
    }

    /**
     * ไรเดอร์ที่เข้าเงื่อนไขรับงานนี้ เรียงจากใกล้จุดรับของที่สุด
     *
     * @return \Illuminate\Support\Collection<int, Rider>
     */
    public function findEligibleRiders(RiderJob $job, float $radiusKm, int $limit = 30)
    {
        if ($job->pickup_latitude === null || $job->pickup_longitude === null) {
            return collect();
        }

        $lat = (float) $job->pickup_latitude;
        $lng = (float) $job->pickup_longitude;
        $radiusKm = max(0.1, $radiusKm);

        // กรองสี่เหลี่ยมคร่าวๆ ด้วย index ก่อน แล้วค่อยคำนวณระยะจริงใน PHP (ไม่ผูกกับ MySQL acos)
        $latDelta = $radiusKm / 111.0;
        $lngDelta = $radiusKm / (111.0 * max(0.1, cos(deg2rad($lat))));

        $excludeRiderIds = array_values(array_unique(array_merge(
            $job->releasedRiderIds(),
            $this->rejectedRiderIds($job)
        )));
        $partyUserIds = $job->partyUserIds();

        $riders = Rider::query()
            ->availableForDelivery($this->config)
            ->whereBetween('last_latitude', [$lat - $latDelta, $lat + $latDelta])
            ->whereBetween('last_longitude', [$lng - $lngDelta, $lng + $lngDelta])
            ->when($excludeRiderIds !== [], fn ($q) => $q->whereNotIn('id', $excludeRiderIds))
            ->when($partyUserIds !== [], fn ($q) => $q->whereNotIn('user_id', $partyUserIds))
            ->whereHas('user', fn ($q) => $q->whereNull('blocked_at'))
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('rider_jobs as active_jobs')
                    ->whereColumn('active_jobs.rider_id', 'riders.id')
                    ->whereIn('active_jobs.status', RiderJob::ACTIVE_STATUSES)
                    ->whereNull('active_jobs.deleted_at');
            })
            ->limit(300)
            ->get();

        if ($riders->isEmpty()) {
            return collect();
        }

        // วงเงิน COD
        $codRequired = round((float) $job->cod_amount - (float) $job->rider_earnings, 2);
        $balances = [];
        if ((float) $job->cod_amount > 0 && $codRequired > 0) {
            $balances = Wallet::whereIn('user_id', $riders->pluck('user_id')->all())
                ->pluck('balance', 'user_id')
                ->map(fn ($b) => (float) $b)
                ->all();
        }

        return $riders
            ->map(function (Rider $rider) use ($lat, $lng) {
                $rider->setAttribute('distance_to_pickup_km', round(DeliveryFeeCalculator::haversineKm(
                    (float) $rider->last_latitude,
                    (float) $rider->last_longitude,
                    $lat,
                    $lng
                ), 2));

                return $rider;
            })
            ->filter(function (Rider $rider) use ($radiusKm, $job, $codRequired, $balances) {
                if ($rider->getAttribute('distance_to_pickup_km') > $radiusKm) {
                    return false;
                }

                if (! $rider->matchesJob((string) $job->job_type, (float) $job->distance_km, (float) $job->rider_earnings)) {
                    return false;
                }

                if ((float) $job->cod_amount > 0 && $codRequired > 0 && ($balances[$rider->user_id] ?? 0.0) < $codRequired) {
                    return false;
                }

                return true;
            })
            ->sortBy(fn (Rider $rider) => $rider->getAttribute('distance_to_pickup_km'))
            ->take($limit)
            ->values();
    }

    // =====================================================
    // เครื่องมือเดิม (คงไว้ให้โค้ดเก่าเรียกได้)
    // =====================================================

    /**
     * คำนวณค่าส่งจากระยะทาง (กม.) — ใช้สูตรเดียวกับ DeliveryFeeCalculator
     */
    public function calculateDeliveryFee(float $distanceKm): float
    {
        return $this->config->quoteForDistance($distanceKm)['total_fee'];
    }

    /**
     * ระยะทางเส้นตรง (กม.) ปัด 2 ตำแหน่ง
     */
    public function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        return round(DeliveryFeeCalculator::haversineKm($lat1, $lng1, $lat2, $lng2), 2);
    }

    /**
     * แอดมินบันทึกการรับเงินประกันไรเดอร์ (ใช้เมื่อเปิด rider.require_deposit)
     */
    public function handleDepositPayment(Rider $rider, float $amount, string $transactionId): bool
    {
        if ($rider->deposit_status === 'paid') {
            return false;
        }

        $rider->markDepositPaid($transactionId, round($amount, 2));

        Log::info('RiderDispatch: deposit recorded', [
            'rider_id' => $rider->id,
            'amount' => $amount,
            'transaction_id' => $transactionId,
        ]);

        return true;
    }

    // =====================================================
    // ภายใน
    // =====================================================

    /**
     * ตรวจ/จัดรูปจุดรับ-ส่งจาก RiderDeliverable
     *
     * @return array{name: string, address: string, latitude: float, longitude: float, phone: ?string, notes: ?string, area: ?string}
     */
    private function normalizePoint(array $point, string $label): array
    {
        $lat = $point['latitude'] ?? null;
        $lng = $point['longitude'] ?? null;

        if (! DeliveryFeeCalculator::isValidCoordinate($lat, $lng)) {
            throw RiderJobException::invalidLocation($label);
        }

        $address = trim((string) ($point['address'] ?? ''));
        $phone = trim((string) ($point['phone'] ?? ''));
        $notes = trim((string) ($point['notes'] ?? ''));
        $area = trim((string) ($point['area'] ?? ''));

        return [
            'name' => mb_substr(trim((string) ($point['name'] ?? '')), 0, 255),
            'address' => $address !== '' ? mb_substr($address, 0, 255) : 'ตามหมุดบนแผนที่',
            'latitude' => round((float) $lat, 8),
            'longitude' => round((float) $lng, 8),
            'phone' => $phone !== '' ? mb_substr($phone, 0, 255) : null,
            'notes' => $notes !== '' ? $notes : null,
            'area' => $area !== '' ? mb_substr($area, 0, 255) : null,
        ];
    }

    /**
     * ใช้ค่าส่งที่ลูกค้าจ่ายจริง (ถ้าออเดอร์บอกมา) เพื่อให้ total_fee ตรงกับใบเสร็จ
     *
     * @param  array<string, mixed>  $quote
     * @return array{base_fee: float, distance_fee: float, total_fee: float, rider_earnings: float, platform_fee: float}
     */
    private function applyChargedFee(Model $source, array $quote): array
    {
        $charged = null;
        if (method_exists($source, 'riderDeliveryFeeCharged')) {
            $value = $source->riderDeliveryFeeCharged();
            $charged = is_numeric($value) ? round((float) $value, 2) : null;
        }

        if ($charged === null || $charged <= 0) {
            return [
                'base_fee' => (float) $quote['base_fee'],
                'distance_fee' => (float) $quote['distance_fee'],
                'total_fee' => (float) $quote['total_fee'],
                'rider_earnings' => (float) $quote['rider_earnings'],
                'platform_fee' => (float) $quote['platform_fee'],
            ];
        }

        $base = round(min((float) $quote['base_fee'], $charged), 2);
        $split = $this->config->split($charged);

        return [
            'base_fee' => $base,
            'distance_fee' => round($charged - $base, 2),
            'total_fee' => $charged,
            'rider_earnings' => $split['rider_earnings'],
            'platform_fee' => $split['platform_fee'],
        ];
    }

    /**
     * user_id ผู้ซื้อของออเดอร์ (ต้องมีอยู่จริงในตาราง users — FK)
     */
    private function resolveCustomerId(Model $source): ?int
    {
        $id = null;

        if (method_exists($source, 'riderCustomerUserId')) {
            $id = $source->riderCustomerUserId();
        }

        if (! $id) {
            foreach (['buyer_id', 'user_id', 'customer_id'] as $column) {
                $value = $source->getAttribute($column);
                if (is_numeric($value) && (int) $value > 0) {
                    $id = (int) $value;
                    break;
                }
            }
        }

        if (! $id) {
            return null;
        }

        return User::whereKey((int) $id)->exists() ? (int) $id : null;
    }

    /**
     * ไรเดอร์ที่กดปฏิเสธงานนี้แล้ว (ไม่แจ้งซ้ำ)
     *
     * @return array<int, int>
     */
    private function rejectedRiderIds(RiderJob $job): array
    {
        $ids = [];
        foreach ($job->dispatch_attempts ?? [] as $attempt) {
            if (in_array($attempt['status'] ?? null, ['rejected', 'expired'], true) && isset($attempt['rider_id'])) {
                $ids[] = (int) $attempt['rider_id'];
            }
        }

        return $ids;
    }
}
