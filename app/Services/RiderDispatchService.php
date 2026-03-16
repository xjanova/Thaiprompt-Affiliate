<?php

namespace App\Services;

use App\Jobs\CascadeRiderDispatchJob;
use App\Models\FreshMarketOrder;
use App\Models\FreshMarketSetting;
use App\Models\Rider;
use App\Models\RiderJob;
use Illuminate\Support\Facades\DB;
use App\Services\RiderGpsTrackingService;
use Illuminate\Support\Facades\Log;

/**
 * Rider Dispatch Service
 *
 * จัดการการจ่ายงานให้ไรเดอร์ คำนวณค่าส่ง จัดการค่าประกัน
 * รองรับ cascade dispatch (เสนอทีละคน) และ broadcast (เสนอทุกคน)
 */
class RiderDispatchService
{
    /**
     * ค่าส่งพื้นฐาน (บาท)
     */
    protected float $baseFee;

    /**
     * ค่าส่งต่อกิโลเมตร (บาท)
     */
    protected float $perKmFee;

    /**
     * ค่าส่งขั้นต่ำ (บาท)
     */
    protected float $minFee;

    /**
     * อัตราส่วนรายได้ไรเดอร์ (80%)
     */
    protected float $riderEarningsRate;

    /**
     * รัศมีค้นหาเริ่มต้น (กม.)
     */
    protected float $defaultSearchRadius;

    /**
     * เวลาให้ไรเดอร์ตอบรับ (วินาที)
     */
    protected int $offerTimeoutSeconds;

    /**
     * จำนวนครั้งสูงสุดที่เสนองาน cascade
     */
    protected int $maxDispatchAttempts;

    /**
     * รัศมี broadcast สูงสุด (กม.)
     */
    protected float $broadcastMaxRadius;

    public function __construct()
    {
        // ดึงค่าจาก settings เพื่อให้ admin ปรับได้ หากไม่มีใช้ค่า default
        $settings = FreshMarketSetting::getSettings();
        $this->baseFee = $settings->delivery_base_fee ?? 30.0;
        $this->perKmFee = $settings->delivery_per_km_fee ?? 10.0;
        $this->minFee = $settings->delivery_min_fee ?? 30.0;
        $this->riderEarningsRate = $settings->rider_earnings_rate ?? 0.80;
        $this->defaultSearchRadius = $settings->default_search_radius_km ?? 5.0;
        $this->offerTimeoutSeconds = $settings->rider_offer_timeout_seconds ?? 120;
        $this->maxDispatchAttempts = $settings->max_dispatch_attempts ?? 5;
        $this->broadcastMaxRadius = $settings->broadcast_max_radius_km ?? 10.0;
    }

    /**
     * ค้นหาไรเดอร์ที่ใกล้ที่สุดสำหรับ order
     *
     * @param FreshMarketOrder $order คำสั่งซื้อ
     * @param float $radiusKm รัศมีค้นหา (กม.)
     * @param int $limit จำนวนสูงสุดที่จะแสดง
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findNearestRiders(
        FreshMarketOrder $order,
        float $radiusKm = 0,
        int $limit = 5
    ) {
        if ($radiusKm <= 0) {
            $radiusKm = $this->defaultSearchRadius;
        }

        // ดึงพิกัดร้านค้าจาก seller
        $seller = $order->seller;
        if (! $seller) {
            Log::warning('RiderDispatch: order ไม่มี seller', ['order_id' => $order->id]);

            return collect();
        }

        // ถ้า seller ไม่มีพิกัด ให้ใช้พิกัดจาก listing
        $latitude = $seller->latitude ?? $order->listing?->latitude;
        $longitude = $seller->longitude ?? $order->listing?->longitude;

        if (! $latitude || ! $longitude) {
            Log::warning('RiderDispatch: ไม่มีพิกัดร้านค้า', [
                'order_id' => $order->id,
                'seller_id' => $seller->id,
            ]);

            return collect();
        }

        // ค้นหาไรเดอร์ที่พร้อมรับงานส่งของ ใกล้ร้านค้า
        return Rider::availableForDelivery()
            ->nearby($latitude, $longitude, $radiusKm)
            ->limit($limit)
            ->get();
    }

    /**
     * จ่ายงานให้ไรเดอร์ (สร้าง job + assign ทันที)
     *
     * @param FreshMarketOrder $order คำสั่งซื้อ
     * @param Rider $rider ไรเดอร์ที่จะรับงาน
     * @return RiderJob|null งานที่สร้าง
     */
    public function dispatchToRider(FreshMarketOrder $order, Rider $rider): ?RiderJob
    {
        if (! $rider->canAcceptJobs()) {
            Log::warning('RiderDispatch: ไรเดอร์ไม่พร้อมรับงาน', [
                'rider_id' => $rider->id,
                'status' => $rider->status,
                'availability' => $rider->availability,
                'deposit_status' => $rider->deposit_status,
            ]);

            return null;
        }

        return DB::transaction(function () use ($order, $rider) {
            // คำนวณระยะทาง
            $seller = $order->seller;
            $sellerLat = $seller->latitude ?? $order->listing?->latitude;
            $sellerLng = $seller->longitude ?? $order->listing?->longitude;
            $buyerLat = $order->buyer_latitude;
            $buyerLng = $order->buyer_longitude;

            $distance = 0;
            if ($sellerLat && $sellerLng && $buyerLat && $buyerLng) {
                $distance = $this->calculateDistance($sellerLat, $sellerLng, $buyerLat, $buyerLng);
            }

            // คำนวณค่าส่ง
            $deliveryFee = $this->calculateDeliveryFee($distance);
            $riderEarnings = round($deliveryFee * $this->riderEarningsRate, 2);
            $platformFee = round($deliveryFee - $riderEarnings, 2);

            // สร้าง RiderJob
            $job = RiderJob::create([
                'rider_id' => $rider->id,
                'job_type' => 'fresh_market',
                'title' => 'ส่งของตลาดสด #' . $order->order_number,
                'description' => 'ส่งสินค้าจากร้าน ' . ($seller->shop_name ?? 'ตลาดสด'),
                'pickup_address' => $seller->address ?? '',
                'pickup_latitude' => $sellerLat,
                'pickup_longitude' => $sellerLng,
                'pickup_contact_name' => $seller->shop_name ?? $seller->name ?? '',
                'pickup_contact_phone' => $seller->phone ?? '',
                'delivery_address' => $order->delivery_address ?? '',
                'delivery_latitude' => $buyerLat,
                'delivery_longitude' => $buyerLng,
                'delivery_contact_name' => $order->buyer?->name ?? '',
                'delivery_contact_phone' => $order->buyer?->phone ?? '',
                'delivery_notes' => $order->delivery_notes,
                'distance_km' => $distance,
                'base_fee' => $this->baseFee,
                'distance_fee' => max(0, $deliveryFee - $this->baseFee),
                'total_fee' => $deliveryFee,
                'rider_earnings' => $riderEarnings,
                'platform_fee' => $platformFee,
                'customer_id' => $order->buyer_id,
                'status' => 'pending',
            ]);

            // อัปเดท order
            $order->update([
                'rider_id' => $rider->id,
                'rider_job_id' => $job->id,
                'rider_assigned_at' => now(),
                'delivery_fee' => $deliveryFee,
                'delivery_distance_km' => $distance,
            ]);

            // สร้าง tracking token และส่ง LINE notification ให้ลูกค้า
            try {
                $gpsService = new RiderGpsTrackingService();
                $buyerLineUserId = $order->buyer?->line_user_id ?? null;
                $gpsService->generateTrackingToken($job, $buyerLineUserId);
                $gpsService->sendTrackingLinkToBuyer($job->fresh());
            } catch (\Throwable $e) {
                Log::warning('RiderDispatch: ไม่สามารถสร้าง tracking token', [
                    'job_id' => $job->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // ตั้งสถานะไรเดอร์เป็น busy
            $rider->setBusy();

            Log::info('RiderDispatch: จ่ายงานสำเร็จ', [
                'order_id' => $order->id,
                'rider_id' => $rider->id,
                'job_id' => $job->id,
                'distance_km' => $distance,
                'delivery_fee' => $deliveryFee,
            ]);

            return $job;
        });
    }

    // =====================================================
    // Cascade Dispatch (เสนอทีละคน รอ 2 นาที)
    // =====================================================

    /**
     * Cascade dispatch: สร้าง job ก่อน แล้วเสนอให้ไรเดอร์ทีละคน
     *
     * ขั้นตอน:
     * 1. หาไรเดอร์ใกล้เคียงหลายคน
     * 2. สร้าง RiderJob (ยังไม่ assign rider)
     * 3. เสนอให้ไรเดอร์คนแรก
     * 4. ตั้ง timeout 2 นาที → ถ้าไม่ตอบ ส่งต่อคนถัดไป
     *
     * @param FreshMarketOrder $order คำสั่งซื้อ
     * @return RiderJob|null
     */
    public function cascadeDispatch(FreshMarketOrder $order): ?RiderJob
    {
        $riders = $this->findNearestRiders($order, $this->defaultSearchRadius, $this->maxDispatchAttempts);

        if ($riders->isEmpty()) {
            Log::info('RiderDispatch: Cascade - ไม่พบไรเดอร์ใกล้เคียง', ['order_id' => $order->id]);

            return null;
        }

        // คำนวณระยะทางและค่าส่ง
        $seller = $order->seller;
        $sellerLat = $seller->latitude ?? $order->listing?->latitude;
        $sellerLng = $seller->longitude ?? $order->listing?->longitude;
        $buyerLat = $order->buyer_latitude;
        $buyerLng = $order->buyer_longitude;

        $distance = 0;
        if ($sellerLat && $sellerLng && $buyerLat && $buyerLng) {
            $distance = $this->calculateDistance($sellerLat, $sellerLng, $buyerLat, $buyerLng);
        }

        $deliveryFee = $this->calculateDeliveryFee($distance);
        $riderEarnings = round($deliveryFee * $this->riderEarningsRate, 2);
        $platformFee = round($deliveryFee - $riderEarnings, 2);

        // กรองไรเดอร์ตาม preferences
        $candidateIds = $riders->filter(function (Rider $rider) use ($distance, $deliveryFee) {
            return $rider->matchesJob('fresh_market', $distance, $deliveryFee);
        })->pluck('id')->values()->toArray();

        if (empty($candidateIds)) {
            Log::info('RiderDispatch: Cascade - ไม่มีไรเดอร์ตรง preferences', ['order_id' => $order->id]);

            return null;
        }

        // สร้าง RiderJob (ยังไม่มี rider_id)
        $job = DB::transaction(function () use ($order, $seller, $sellerLat, $sellerLng, $buyerLat, $buyerLng, $distance, $deliveryFee, $riderEarnings, $platformFee, $candidateIds) {
            $job = RiderJob::create([
                'job_type' => 'fresh_market',
                'title' => 'ส่งของตลาดสด #' . $order->order_number,
                'description' => 'ส่งสินค้าจากร้าน ' . ($seller->shop_name ?? 'ตลาดสด'),
                'pickup_address' => $seller->address ?? '',
                'pickup_latitude' => $sellerLat,
                'pickup_longitude' => $sellerLng,
                'pickup_contact_name' => $seller->shop_name ?? $seller->name ?? '',
                'pickup_contact_phone' => $seller->phone ?? '',
                'delivery_address' => $order->delivery_address ?? '',
                'delivery_latitude' => $buyerLat,
                'delivery_longitude' => $buyerLng,
                'delivery_contact_name' => $order->buyer?->name ?? '',
                'delivery_contact_phone' => $order->buyer?->phone ?? '',
                'delivery_notes' => $order->delivery_notes,
                'distance_km' => $distance,
                'base_fee' => $this->baseFee,
                'distance_fee' => max(0, $deliveryFee - $this->baseFee),
                'total_fee' => $deliveryFee,
                'rider_earnings' => $riderEarnings,
                'platform_fee' => $platformFee,
                'customer_id' => $order->buyer_id,
                'status' => 'pending',
                'dispatch_type' => 'auto',
                'candidate_riders' => $candidateIds,
            ]);

            // อัปเดท order เชื่อมกับ job
            $order->update([
                'rider_job_id' => $job->id,
                'delivery_fee' => $deliveryFee,
                'delivery_distance_km' => $distance,
            ]);

            return $job;
        });

        // เสนองานให้ไรเดอร์คนแรก
        $this->offerJobToNextRider($job);

        Log::info('RiderDispatch: Cascade dispatch เริ่มต้น', [
            'order_id' => $order->id,
            'job_id' => $job->id,
            'candidate_count' => count($candidateIds),
        ]);

        return $job;
    }

    /**
     * เสนองานให้ไรเดอร์คนถัดไปใน cascade queue (iterative)
     */
    public function offerJobToNextRider(RiderJob $job): bool
    {
        // วนหาไรเดอร์ที่พร้อมรับงาน (ข้ามคนที่ไม่พร้อม)
        for ($i = 0; $i < $this->maxDispatchAttempts; $i++) {
            if ($job->status !== 'pending') {
                return false;
            }

            $nextRiderId = $job->getNextCandidateRiderId();

            if (! $nextRiderId) {
                Log::info('RiderDispatch: Cascade - หมดไรเดอร์ที่จะเสนอ', ['job_id' => $job->id]);

                return false;
            }

            $rider = Rider::find($nextRiderId);

            if (! $rider || ! $rider->canAcceptJobs()) {
                // ไรเดอร์ไม่พร้อม → บันทึกและข้ามไปคนถัดไป
                $job->recordOffer($nextRiderId, $this->offerTimeoutSeconds);
                $job->recordOfferResponse($nextRiderId, 'skipped');
                $job->refresh();

                continue;
            }

            // บันทึก offer
            $job->recordOffer($nextRiderId, $this->offerTimeoutSeconds);

            // ส่ง LINE Flex Message ให้ไรเดอร์
            $sent = $this->notifyRiderOfJob($rider, $job);

            if (! $sent) {
                // ส่ง LINE ไม่สำเร็จ → ข้ามไปคนถัดไป
                $job->recordOfferResponse($nextRiderId, 'send_failed');
                $job->refresh();

                continue;
            }

            // ตั้ง timeout job → ถ้าไม่ตอบ ส่งให้คนถัดไป
            CascadeRiderDispatchJob::dispatch($job->id)
                ->delay(now()->addSeconds($this->offerTimeoutSeconds));

            Log::info('RiderDispatch: Cascade - เสนองานให้ไรเดอร์', [
                'job_id' => $job->id,
                'rider_id' => $nextRiderId,
                'timeout_seconds' => $this->offerTimeoutSeconds,
            ]);

            return true;
        }

        return false;
    }

    /**
     * ไรเดอร์ตอบรับงาน (จาก LINE postback)
     *
     * ใช้ atomic UPDATE WHERE status='pending' เพื่อป้องกัน race condition
     * เมื่อมีไรเดอร์หลายคนกดรับพร้อมกัน (broadcast)
     *
     * @return bool สำเร็จหรือไม่ (อาจไม่สำเร็จถ้ามีคนอื่นรับก่อน)
     */
    public function handleRiderAccept(RiderJob $job, Rider $rider): bool
    {
        return DB::transaction(function () use ($job, $rider) {
            // อัพเดท dispatch_attempts ก่อนทำ atomic update
            $attempts = $job->dispatch_attempts ?? [];
            foreach ($attempts as &$attempt) {
                if ($attempt['rider_id'] === $rider->id && $attempt['status'] === 'pending') {
                    $attempt['status'] = 'accepted';
                    $attempt['responded_at'] = now()->toIso8601String();
                    break;
                }
            }
            unset($attempt);

            // Race-safe: atomic UPDATE WHERE status='pending'
            $updated = RiderJob::where('id', $job->id)
                ->where('status', 'pending')
                ->update([
                    'rider_id' => $rider->id,
                    'status' => 'accepted',
                    'accepted_at' => now(),
                    'current_offer_rider_id' => null,
                    'offer_expires_at' => null,
                    'dispatch_attempts' => json_encode($attempts),
                ]);

            if ($updated === 0) {
                // งานถูกรับแล้ว (race condition)
                return false;
            }

            $job->refresh();

            // อัปเดท order
            $order = $job->freshMarketOrder;
            if ($order) {
                $order->update([
                    'rider_id' => $rider->id,
                    'rider_assigned_at' => now(),
                    'rider_accepted_at' => now(),
                ]);
            }

            // ตั้งสถานะไรเดอร์เป็น busy
            $rider->setBusy();

            // สร้าง tracking token
            try {
                $gpsService = new RiderGpsTrackingService();
                $buyerLineUserId = $order?->buyer?->line_user_id ?? null;
                $gpsService->generateTrackingToken($job, $buyerLineUserId);
                $gpsService->sendTrackingLinkToBuyer($job->fresh());
            } catch (\Throwable $e) {
                Log::warning('RiderDispatch: ไม่สามารถสร้าง tracking', [
                    'job_id' => $job->id,
                    'error' => $e->getMessage(),
                ]);
            }

            Log::info('RiderDispatch: ไรเดอร์รับงาน', [
                'job_id' => $job->id,
                'rider_id' => $rider->id,
            ]);

            return true;
        });
    }

    /**
     * ไรเดอร์ปฏิเสธงาน (จาก LINE postback)
     */
    public function handleRiderReject(RiderJob $job, Rider $rider): void
    {
        $job->recordOfferResponse($rider->id, 'rejected');

        Log::info('RiderDispatch: ไรเดอร์ปฏิเสธงาน', [
            'job_id' => $job->id,
            'rider_id' => $rider->id,
        ]);

        // ถ้าเป็น cascade → เสนอให้คนถัดไปทันที
        if ($job->dispatch_type === 'auto') {
            $this->offerJobToNextRider($job->fresh());
        }
    }

    /**
     * จัดการเมื่อ offer หมดเวลา (เรียกจาก CascadeRiderDispatchJob)
     */
    public function handleOfferTimeout(RiderJob $job): void
    {
        // ตรวจสอบว่า job ยังรอไรเดอร์อยู่
        if ($job->status !== 'pending') {
            return;
        }

        // ตรวจสอบว่า offer หมดเวลาจริง
        if (! $job->isOfferExpired()) {
            return;
        }

        $expiredRiderId = $job->current_offer_rider_id;

        if ($expiredRiderId) {
            $job->recordOfferResponse($expiredRiderId, 'expired');

            Log::info('RiderDispatch: Cascade - offer หมดเวลา', [
                'job_id' => $job->id,
                'rider_id' => $expiredRiderId,
            ]);
        }

        // เสนอให้คนถัดไป
        $offered = $this->offerJobToNextRider($job->fresh());

        if (! $offered) {
            Log::warning('RiderDispatch: Cascade - หมดไรเดอร์ทุกคนแล้ว', [
                'job_id' => $job->id,
            ]);
        }
    }

    // =====================================================
    // Broadcast Dispatch (เสนอทุกคนพร้อมกัน)
    // =====================================================

    /**
     * Broadcast dispatch: ส่ง push ให้ไรเดอร์ทุกคนในรัศมี
     * ใช้เครดิต broadcast ของร้านค้า
     * คนแรกที่กด "รับงาน" จะได้งาน (race-safe)
     *
     * @param FreshMarketOrder $order คำสั่งซื้อ
     * @return RiderJob|null
     */
    public function broadcastDispatch(FreshMarketOrder $order): ?RiderJob
    {
        $seller = $order->seller;

        if (! $seller || ! $seller->hasBroadcastCredits()) {
            Log::info('RiderDispatch: Broadcast - ร้านค้าไม่มีเครดิต', [
                'order_id' => $order->id,
                'seller_id' => $seller?->id,
            ]);

            return null;
        }

        // ค้นหาไรเดอร์ในรัศมี broadcast
        $riders = $this->findNearestRiders($order, $this->broadcastMaxRadius, 20);

        if ($riders->isEmpty()) {
            Log::info('RiderDispatch: Broadcast - ไม่พบไรเดอร์', ['order_id' => $order->id]);

            return null;
        }

        // คำนวณระยะทางและค่าส่ง
        $sellerLat = $seller->latitude ?? $order->listing?->latitude;
        $sellerLng = $seller->longitude ?? $order->listing?->longitude;
        $buyerLat = $order->buyer_latitude;
        $buyerLng = $order->buyer_longitude;

        $distance = 0;
        if ($sellerLat && $sellerLng && $buyerLat && $buyerLng) {
            $distance = $this->calculateDistance($sellerLat, $sellerLng, $buyerLat, $buyerLng);
        }

        $deliveryFee = $this->calculateDeliveryFee($distance);
        $riderEarnings = round($deliveryFee * $this->riderEarningsRate, 2);
        $platformFee = round($deliveryFee - $riderEarnings, 2);

        // กรองตาม preferences
        $matchedRiders = $riders->filter(function (Rider $rider) use ($distance, $deliveryFee) {
            return $rider->matchesJob('fresh_market', $distance, $deliveryFee);
        });

        if ($matchedRiders->isEmpty()) {
            Log::info('RiderDispatch: Broadcast - ไม่มีไรเดอร์ตรง preferences', ['order_id' => $order->id]);

            return null;
        }

        // หักเครดิต broadcast
        if (! $seller->useBroadcastCredit()) {
            return null;
        }

        // สร้าง RiderJob (ยังไม่มี rider)
        $job = DB::transaction(function () use ($order, $seller, $sellerLat, $sellerLng, $buyerLat, $buyerLng, $distance, $deliveryFee, $riderEarnings, $platformFee, $matchedRiders) {
            $job = RiderJob::create([
                'job_type' => 'fresh_market',
                'title' => '📢 ส่งของตลาดสด #' . $order->order_number,
                'description' => 'Broadcast จากร้าน ' . ($seller->shop_name ?? 'ตลาดสด'),
                'pickup_address' => $seller->address ?? '',
                'pickup_latitude' => $sellerLat,
                'pickup_longitude' => $sellerLng,
                'pickup_contact_name' => $seller->shop_name ?? $seller->name ?? '',
                'pickup_contact_phone' => $seller->phone ?? '',
                'delivery_address' => $order->delivery_address ?? '',
                'delivery_latitude' => $buyerLat,
                'delivery_longitude' => $buyerLng,
                'delivery_contact_name' => $order->buyer?->name ?? '',
                'delivery_contact_phone' => $order->buyer?->phone ?? '',
                'delivery_notes' => $order->delivery_notes,
                'distance_km' => $distance,
                'base_fee' => $this->baseFee,
                'distance_fee' => max(0, $deliveryFee - $this->baseFee),
                'total_fee' => $deliveryFee,
                'rider_earnings' => $riderEarnings,
                'platform_fee' => $platformFee,
                'customer_id' => $order->buyer_id,
                'status' => 'pending',
                'dispatch_type' => 'broadcast',
                'candidate_riders' => $matchedRiders->pluck('id')->toArray(),
            ]);

            $order->update([
                'rider_job_id' => $job->id,
                'delivery_fee' => $deliveryFee,
                'delivery_distance_km' => $distance,
            ]);

            return $job;
        });

        // Push ให้ทุกไรเดอร์ที่ตรง
        foreach ($matchedRiders as $rider) {
            $this->notifyRiderOfJob($rider, $job);
        }

        Log::info('RiderDispatch: Broadcast dispatch สำเร็จ', [
            'order_id' => $order->id,
            'job_id' => $job->id,
            'riders_notified' => $matchedRiders->count(),
        ]);

        return $job;
    }

    // =====================================================
    // LINE Notification
    // =====================================================

    /**
     * ส่ง LINE Flex Message แจ้งงานให้ไรเดอร์
     */
    public function notifyRiderOfJob(Rider $rider, RiderJob $job): bool
    {
        if (! $rider->line_user_id) {
            Log::debug('RiderDispatch: ไรเดอร์ไม่มี LINE User ID', ['rider_id' => $rider->id]);

            return false;
        }

        try {
            $lineService = new FreshMarketLineService();

            $flex = $lineService->buildRiderJobOfferFlex([
                'job_id' => $job->id,
                'job_number' => $job->job_number,
                'title' => $job->title,
                'pickup_address' => $job->pickup_address,
                'delivery_address' => $job->delivery_address,
                'distance_km' => $job->distance_km,
                'total_fee' => $job->total_fee,
                'rider_earnings' => $job->rider_earnings,
                'dispatch_type' => $job->dispatch_type,
            ]);

            $lineService->sendFlexMessage(
                $rider->line_user_id,
                $flex,
                "งานใหม่: {$job->title} - รายได้ ฿" . number_format($job->rider_earnings)
            );

            return true;
        } catch (\Throwable $e) {
            Log::warning('RiderDispatch: ส่ง LINE ให้ไรเดอร์ล้มเหลว', [
                'rider_id' => $rider->id,
                'job_id' => $job->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * แจ้งไรเดอร์ที่เหลือว่างานถูกรับแล้ว (สำหรับ broadcast)
     */
    public function notifyOtherRidersJobTaken(RiderJob $job): void
    {
        if ($job->dispatch_type !== 'broadcast') {
            return;
        }

        $acceptedRiderId = $job->rider_id;
        $candidateIds = $job->candidate_riders ?? [];

        try {
            $lineService = new FreshMarketLineService();
            $flex = $lineService->buildJobTakenFlex($job->job_number);

            foreach ($candidateIds as $riderId) {
                if ($riderId === $acceptedRiderId) {
                    continue;
                }

                $rider = Rider::find($riderId);
                if ($rider?->line_user_id) {
                    $lineService->sendFlexMessage(
                        $rider->line_user_id,
                        $flex,
                        "งาน {$job->job_number} มีไรเดอร์รับแล้ว"
                    );
                }
            }
        } catch (\Throwable $e) {
            Log::warning('RiderDispatch: แจ้งไรเดอร์อื่นล้มเหลว', [
                'job_id' => $job->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // =====================================================
    // Legacy Methods (ยังคงใช้งานได้)
    // =====================================================

    /**
     * คำนวณค่าส่ง
     *
     * @param float $distanceKm ระยะทาง (กม.)
     * @return float ค่าส่ง (บาท)
     */
    public function calculateDeliveryFee(float $distanceKm): float
    {
        $fee = $this->baseFee + ($distanceKm * $this->perKmFee);

        return max($this->minFee, round($fee, 2));
    }

    /**
     * คำนวณระยะทางด้วย Haversine formula
     *
     * @param float $lat1 ละติจูดจุดเริ่ม
     * @param float $lng1 ลองจิจูดจุดเริ่ม
     * @param float $lat2 ละติจูดจุดปลาย
     * @param float $lng2 ลองจิจูดจุดปลาย
     * @return float ระยะทาง (กม.)
     */
    public function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // km

        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) * sin($latDelta / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($lngDelta / 2) * sin($lngDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 2);
    }

    /**
     * จัดการค่าประกัน - บันทึกการชำระ
     *
     * @param Rider $rider ไรเดอร์
     * @param float $amount จำนวนเงิน
     * @param string $transactionId หมายเลขอ้างอิง
     * @return bool
     */
    public function handleDepositPayment(Rider $rider, float $amount, string $transactionId): bool
    {
        if ($rider->deposit_status === 'paid') {
            Log::info('RiderDispatch: ไรเดอร์จ่ายค่าประกันแล้ว', ['rider_id' => $rider->id]);

            return false;
        }

        $rider->markDepositPaid($transactionId, $amount);

        Log::info('RiderDispatch: บันทึกค่าประกันสำเร็จ', [
            'rider_id' => $rider->id,
            'amount' => $amount,
            'transaction_id' => $transactionId,
        ]);

        return true;
    }

    /**
     * Auto-dispatch: หาไรเดอร์ใกล้สุดแล้วจ่ายงานอัตโนมัติ
     * (legacy — ใช้ cascadeDispatch() แทนได้)
     *
     * @param FreshMarketOrder $order คำสั่งซื้อ
     * @return RiderJob|null
     */
    public function autoDispatch(FreshMarketOrder $order): ?RiderJob
    {
        // ค้นหาไรเดอร์ใกล้ที่สุด
        $riders = $this->findNearestRiders($order, $this->defaultSearchRadius, 1);

        if ($riders->isEmpty()) {
            Log::info('RiderDispatch: ไม่พบไรเดอร์ใกล้เคียง', [
                'order_id' => $order->id,
            ]);

            return null;
        }

        $rider = $riders->first();

        return $this->dispatchToRider($order, $rider);
    }
}
