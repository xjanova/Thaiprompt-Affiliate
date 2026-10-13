<?php

namespace App\Services;

use App\Models\FreshMarketSetting;
use App\Models\Rider;
use App\Models\RiderJob;
use App\Models\RiderLocation;
use App\Services\Marketplace\MarketTimeouts;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * RiderGpsTrackingService - ตำแหน่งไรเดอร์ + ลิงก์ติดตามของลูกค้า
 *
 * - จุดรับตำแหน่งเดียวจากแอป: recordRiderLocation() (POST /rider/location)
 *   บันทึก last_* ของไรเดอร์ทุกครั้ง, เก็บประวัติเส้นทางเฉพาะตอนมีงาน, GPS กลับมาเองอัตโนมัติ
 * - ตรวจ GPS หายฝั่ง server: detectGpsLoss() (คำสั่ง rider:gps-watch ทุกนาที)
 * - ลูกค้าเห็นตำแหน่งไรเดอร์ได้เฉพาะงานของตัวเองที่ยังวิ่งอยู่ (accepted → delivering)
 *   และไรเดอร์ต้องยินยอมแชร์ตำแหน่งแล้ว — จบงาน/ยกเลิก = หยุดทันที
 * - ลูกค้าเลือกแชร์ตำแหน่งตัวเองให้ไรเดอร์ได้ (ต้องยินยอมเอง) หยุดอัตโนมัติเมื่อจบงาน
 *
 * ❗ ไม่ใช้ LINE push (โควต้า 300/เดือน) — แจ้งผ่าน in-app + Expo push เท่านั้น
 *    GPS หายช่วงสั้น ไม่แจ้งลูกค้า (แสดงบนหน้าติดตามแทน) แจ้งแค่ไรเดอร์
 *    (2026-10-13) GPS หายเกิน rider.gps_lost_release_minutes: ก่อนรับของ = คืนงาน · หลังรับของ = แจ้งแอดมิน + ผู้ซื้อครั้งเดียว
 */
class RiderGpsTrackingService
{
    public FreshMarketSetting $settings;

    protected RiderNotificationService $notifier;

    protected DeliveryFeeCalculator $config;

    public function __construct(?FreshMarketSetting $settings = null)
    {
        $this->settings = $settings ?? FreshMarketSetting::getSettings();
        $this->notifier = app(RiderNotificationService::class);
        $this->config = app(DeliveryFeeCalculator::class);
    }

    // =====================================================
    // Tracking token (ลิงก์ติดตามของลูกค้า)
    // =====================================================

    /**
     * สร้าง/ต่ออายุ tracking token (ปกติสร้างตั้งแต่ตอนสร้างงานแล้ว)
     */
    public function generateTrackingToken(RiderJob $job, ?string $buyerLineUserId = null): string
    {
        $token = $job->tracking_token ?: Str::random(48);
        $expiryHours = max(1, $this->config->intSetting('rider.tracking_expiry_hours'));

        $job->update([
            'tracking_token' => $token,
            'tracking_expires_at' => now()->addHours($expiryHours),
            'gps_active' => true,
            'gps_warning_count' => 0,
            'buyer_line_user_id' => $buyerLineUserId ?? $job->buyer_line_user_id,
        ]);

        return $token;
    }

    /**
     * ตรวจสอบ tracking token แล้วคืน RiderJob (null = ไม่พบ/หมดอายุ)
     */
    public function validateToken(string $token): ?RiderJob
    {
        if (strlen($token) < 32) {
            return null;
        }

        $job = RiderJob::where('tracking_token', $token)
            ->with(['rider', 'freshMarketOrder'])
            ->first();

        if (! $job || ! $job->isTrackingValid()) {
            return null;
        }

        return $job;
    }

    /**
     * สร้าง URL สำหรับติดตาม
     */
    public function getTrackingUrl(RiderJob $job): string
    {
        return url('/taladsod/track/'.$job->tracking_token);
    }

    /**
     * ลูกค้าเห็นตำแหน่งไรเดอร์ของงานนี้ได้หรือไม่ตอนนี้
     */
    public function canShowRiderLocation(RiderJob $job): bool
    {
        return $job->isTrackable()
            && $job->rider !== null
            && $job->rider->hasLocationConsent();
    }

    /**
     * ดึงตำแหน่งปัจจุบันของไรเดอร์ (ให้ลูกค้า — เฉพาะงานที่ยังวิ่งอยู่)
     *
     * @return array<string, mixed>
     */
    public function getCurrentLocation(RiderJob $job): array
    {
        if (! $this->canShowRiderLocation($job)) {
            return [
                'available' => false,
                'job_status' => $job->status,
                'job_status_text' => $job->status_text,
                'reason' => $job->isTrackable() ? 'consent_missing' : 'job_not_active',
            ];
        }

        $rider = $job->rider;
        $timeout = (int) ($this->settings->gps_lost_timeout_seconds ?? 120);

        $isStale = $rider->last_location_update
            ? $rider->last_location_update->diffInSeconds(now()) > $timeout
            : true;

        $latest = RiderLocation::where('job_id', $job->id)
            ->orderByDesc('recorded_at')
            ->first(['speed', 'heading']);

        return [
            'available' => ! $isStale && (bool) $job->gps_active,
            'job_status' => $job->status,
            'job_status_text' => $job->status_text,
            'latitude' => (float) $rider->last_latitude,
            'longitude' => (float) $rider->last_longitude,
            'updated_at' => $rider->last_location_update?->toIso8601String(),
            'updated_ago' => $rider->last_location_update?->diffForHumans(),
            'gps_active' => (bool) $job->gps_active,
            'speed' => $latest?->speed !== null ? (float) $latest->speed : null,
            'heading' => $latest?->heading !== null ? (float) $latest->heading : null,
        ];
    }

    /**
     * ดึงเส้นทางการเดินทางของไรเดอร์ (เฉพาะงานที่ยังวิ่งอยู่)
     */
    public function getRouteHistory(RiderJob $job, int $limit = 100): array
    {
        if (! $this->canShowRiderLocation($job)) {
            return [];
        }

        $locations = RiderLocation::forJob($job->id)
            ->orderBy('recorded_at', 'desc')
            ->limit(max(1, min(500, $limit)))
            ->get(['latitude', 'longitude', 'speed', 'recorded_at'])
            ->reverse()
            ->values();

        return $locations->map(fn ($loc) => [
            'lat' => (float) $loc->latitude,
            'lng' => (float) $loc->longitude,
            'speed' => $loc->speed !== null ? (float) $loc->speed : null,
            'time' => $loc->recorded_at?->toIso8601String(),
        ])->toArray();
    }

    // =====================================================
    // รับตำแหน่งจากแอปไรเดอร์
    // =====================================================

    /**
     * จุดรับตำแหน่งหลักจากแอป (POST /api/v1/rider/location)
     *
     * - อัปเดต last_latitude/last_longitude/last_location_update ของไรเดอร์ทุกครั้ง
     * - มีงานค้าง → บันทึก rider_locations (เส้นทาง) + ถ้า GPS เคยหาย ให้กลับมาทำงานต่อ
     * - ไม่มีงาน → ไม่เก็บประวัติ (ลดข้อมูลส่วนบุคคล)
     *
     * @param  array<string, mixed>  $data  latitude, longitude, accuracy?, speed?, heading?, battery_level? ...
     * @return array{has_active_job: bool, job_id: ?int, is_tracking: bool, gps_resumed: bool}
     */
    public function recordRiderLocation(Rider $rider, array $data): array
    {
        $clean = RiderLocation::sanitize($data);

        $rider->forceFill([
            'last_latitude' => $clean['latitude'],
            'last_longitude' => $clean['longitude'],
            'last_location_update' => now(),
        ])->save();

        $job = $rider->activeJob();
        if (! $job) {
            return ['has_active_job' => false, 'job_id' => null, 'is_tracking' => false, 'gps_resumed' => false];
        }

        RiderLocation::recordLocation($rider->id, $data, $job->id);

        $resumed = false;
        // GPS กลับมา: หยุดชั่วคราวอยู่ หรือเคยแจ้งแอดมิน/ผู้ซื้อเรื่อง GPS หายไปแล้ว (จบเหตุการณ์ → ครั้งหน้าแจ้งใหม่ได้)
        if (! $job->gps_active || $job->gps_lost_alerted_at !== null) {
            $this->handleGpsResume($job);
            $resumed = true;
        }

        return ['has_active_job' => true, 'job_id' => (int) $job->id, 'is_tracking' => true, 'gps_resumed' => $resumed];
    }

    /**
     * อัพเดทตำแหน่งแบบระบุงาน (endpoint เก่า /fresh-market/rider/gps/update — ใช้ recordRiderLocation แทน)
     */
    public function updateLocation(Rider $rider, RiderJob $job, array $locationData): void
    {
        $this->recordRiderLocation($rider, $locationData);
    }

    // =====================================================
    // GPS หาย / กลับมา
    // =====================================================

    /**
     * ตรวจงานที่ GPS ไรเดอร์เงียบเกิน gps_lost_timeout_seconds (rider:gps-watch ทุกนาที)
     *
     * @return int จำนวนงานที่ถูกหยุดชั่วคราวรอบนี้
     */
    public function detectGpsLoss(): int
    {
        $timeout = max(30, (int) ($this->settings->gps_lost_timeout_seconds ?? 120));
        $cutoff = now()->subSeconds($timeout);
        $count = 0;

        $jobs = RiderJob::query()
            ->whereIn('status', RiderJob::ACTIVE_STATUSES)
            ->where('gps_active', true)
            ->whereNotNull('rider_id')
            ->whereHas('rider', function ($q) use ($cutoff) {
                $q->where(function ($q2) use ($cutoff) {
                    $q2->whereNull('last_location_update')->orWhere('last_location_update', '<', $cutoff);
                });
            })
            ->with('rider')
            ->limit(200)
            ->get();

        foreach ($jobs as $job) {
            try {
                $this->handleGpsLost($job);
                $count++;
            } catch (\Throwable $e) {
                Log::error('RiderGPS: detect loss failed', ['job_id' => $job->id, 'error' => $e->getMessage()]);
            }
        }

        return $count;
    }

    /**
     * กติกาตัดเวลา (2026-10-13): GPS ไรเดอร์หายเกิน rider.gps_lost_release_minutes (rider:gps-watch ทุกนาที)
     *
     * ไม่ขึ้นกับ gps_active (detectGpsLoss ตั้ง false หลังเตือนครั้งแรกแล้วไม่เคยดูงานนั้นอีก)
     * เวลาสัญญาณล่าสุด = ค่ามากสุดของ ตำแหน่งล่าสุดของไรเดอร์ / เวลารับงาน / เวลารับของ (แอปยังทำงานอยู่ตอนกดปุ่มเหล่านั้น)
     *
     * - ก่อนรับของ (accepted / picking_up) → คืนงานเข้าคิว (RiderJobService::releaseForGpsLoss — นับ cancelled_jobs, แจ้งไรเดอร์/ผู้ซื้อ/ร้าน)
     * - หลังรับของ (picked_up / delivering) → แจ้งแอดมิน + ผู้ซื้อครั้งเดียวต่อเหตุการณ์ (gps_lost_alerted_at) ไม่ทำอะไรกับงานเอง
     *
     * @return array{released: int, alerted: int}
     */
    public function enforceGpsLossRules(int $limit = 200): array
    {
        $stats = ['released' => 0, 'alerted' => 0];
        $minutes = app(MarketTimeouts::class)->gpsLostReleaseMinutes();
        $cutoff = now()->subMinutes($minutes);

        $jobs = RiderJob::query()
            ->whereIn('status', RiderJob::ACTIVE_STATUSES)
            ->whereNotNull('rider_id')
            ->where(fn ($q) => $q->whereNull('accepted_at')->orWhere('accepted_at', '<=', $cutoff))
            ->where(fn ($q) => $q->whereNull('picked_up_at')->orWhere('picked_up_at', '<=', $cutoff))
            // หลังรับของที่แจ้งไปแล้วในเหตุการณ์นี้ → ไม่ต้องโหลดมาอีก
            ->where(fn ($q) => $q->whereIn('status', ['accepted', 'picking_up'])->orWhereNull('gps_lost_alerted_at'))
            ->whereHas('rider', function ($q) use ($cutoff) {
                $q->where(fn ($q2) => $q2->whereNull('last_location_update')->orWhere('last_location_update', '<=', $cutoff));
            })
            ->with('rider')
            ->orderBy('id')
            ->limit(max(1, $limit))
            ->get();

        foreach ($jobs as $job) {
            try {
                $lastSignal = $this->lastSignalAt($job);
                if ($lastSignal !== null && $lastSignal->gt($cutoff)) {
                    continue;
                }

                // 🛑 (2026-10-13) พิกัดล่าสุดอยู่ใกล้จุดที่ต้องรอ (ร้านก่อนรับของ / จุดส่งหลังรับของ) = กำลังรอ ไม่ใช่หาย
                //    แอปรุ่นเก่าส่งพิกัดเฉพาะตอนขยับ 25 ม. → ยืนรออาหารหรือรอผู้ซื้อลงมาเกิน 10 นาที (ล็อกจอ)
                //    จะดูเหมือน GPS หาย แล้วโดนปลดงาน + นับยกเลิก + ปิดรับงาน ทั้งที่ยืนอยู่หน้าร้าน
                if ($this->lastSeenNearWaitPoint($job)) {
                    continue;
                }

                if (in_array($job->status, ['accepted', 'picking_up'], true)) {
                    if (app(RiderJobService::class)->releaseForGpsLoss($job, $minutes)) {
                        $stats['released']++;
                    }
                } elseif ($this->alertGpsLostAfterPickup($job, $minutes)) {
                    $stats['alerted']++;
                }
            } catch (\Throwable $e) {
                Log::error('RiderGPS: enforce gps-loss rule failed', ['job_id' => $job->id, 'error' => $e->getMessage()]);
            }
        }

        return $stats;
    }

    /** รัศมีที่ถือว่าไรเดอร์ "รออยู่ที่จุดนัด" (เมตร) */
    public const WAIT_POINT_RADIUS_M = 300;

    /**
     * พิกัดล่าสุดของไรเดอร์อยู่ใกล้จุดที่งานนี้ต้องรอไหม
     * ก่อนรับของ (accepted / picking_up) = จุดรับของ · หลังรับของ = จุดส่ง · ไม่มีพิกัด = false
     */
    protected function lastSeenNearWaitPoint(RiderJob $job): bool
    {
        $rider = $job->rider;
        if (! $rider || $rider->last_latitude === null || $rider->last_longitude === null) {
            return false;
        }

        $beforePickup = in_array($job->status, ['accepted', 'picking_up'], true);
        $lat = $beforePickup ? $job->pickup_latitude : $job->delivery_latitude;
        $lng = $beforePickup ? $job->pickup_longitude : $job->delivery_longitude;
        if ($lat === null || $lng === null) {
            return false;
        }

        $meters = DeliveryFeeCalculator::haversineKm(
            (float) $rider->last_latitude,
            (float) $rider->last_longitude,
            (float) $lat,
            (float) $lng
        ) * 1000;

        return $meters <= self::WAIT_POINT_RADIUS_M;
    }

    /**
     * เวลาที่ได้สัญญาณจากไรเดอร์ล่าสุดของงานนี้
     */
    protected function lastSignalAt(RiderJob $job): ?\Illuminate\Support\Carbon
    {
        $candidates = array_filter([
            $job->rider?->last_location_update,
            $job->accepted_at,
            $job->picked_up_at,
        ]);

        if ($candidates === []) {
            return null;
        }

        return collect($candidates)->sortByDesc(fn ($at) => $at->getTimestamp())->first();
    }

    /**
     * หลังรับของ GPS หายเกินกำหนด → แจ้งแอดมิน + ผู้ซื้อ (ครั้งเดียวต่อเหตุการณ์ — ล้างเมื่อ GPS กลับมา)
     */
    protected function alertGpsLostAfterPickup(RiderJob $job, int $minutes): bool
    {
        $updated = RiderJob::whereKey($job->id)
            ->whereIn('status', ['picked_up', 'delivering'])
            ->whereNull('gps_lost_alerted_at')
            ->update(['gps_lost_alerted_at' => now()]);

        if ($updated === 0) {
            return false;
        }

        $job->refresh();
        $job->loadMissing('rider');
        $riderName = $job->rider?->full_name ?? '-';
        $riderPhone = $job->rider?->phone ?? '-';

        Log::warning('RiderGPS: GPS lost after pickup — admins and buyer alerted', ['job_id' => $job->id, 'rider_id' => $job->rider_id, 'minutes' => $minutes]);

        $this->notifier->notifyAdmins(
            'GPS ไรเดอร์หายระหว่างนำส่ง',
            "งาน #{$job->job_number} ไรเดอร์ {$riderName} ({$riderPhone}) รับของแล้วแต่ระบบไม่ได้รับตำแหน่งเกิน {$minutes} นาที กรุณาติดต่อไรเดอร์",
            ['job_id' => (int) $job->id, 'rider_id' => $job->rider_id ? (int) $job->rider_id : null, 'event' => 'gps_lost_after_pickup']
        );

        $buyerId = null;
        try {
            $buyerId = $job->deliverableSource()?->riderCustomerUserId();
        } catch (\Throwable) {
            // ออเดอร์ต้นทางโหลดไม่ได้ → ใช้ผู้ซื้อของงาน
        }
        $buyerId ??= $job->customer_id ? (int) $job->customer_id : null;

        if ($buyerId) {
            $this->notifier->notifyUser(
                $buyerId,
                'delivery_update',
                'ไรเดอร์ขาดการติดต่อชั่วคราว',
                "ระบบไม่ได้รับตำแหน่งไรเดอร์ของงาน #{$job->job_number} เกิน {$minutes} นาที ทีมงานกำลังติดต่อไรเดอร์ให้",
                [
                    'type' => 'delivery_update',
                    'event' => 'gps_lost',
                    'role' => 'buyer',
                    'screen' => 'order',
                    'job_id' => (int) $job->id,
                    'source' => RiderNotificationService::sourceKey($job),
                    'order_id' => $job->source_id ? (int) $job->source_id : null,
                ],
                null,
                'high'
            );
        }

        return true;
    }

    /**
     * จัดการเมื่อ GPS ของไรเดอร์หาย (หยุดงานชั่วคราว + แจ้งไรเดอร์ + แจ้งแอดมินเมื่อเกินจำนวนครั้ง)
     */
    public function handleGpsLost(RiderJob $job): array
    {
        $maxWarnings = max(1, (int) ($this->settings->gps_warning_max ?? 3));

        // ตั้ง gps_active = false แบบมีเงื่อนไข → ไม่นับเตือนซ้ำถ้าอีกคำขอทำไปแล้ว
        $updated = RiderJob::whereKey($job->id)
            ->where('gps_active', true)
            ->update([
                'gps_active' => false,
                'gps_lost_at' => now(),
                'gps_warning_count' => DB::raw('gps_warning_count + 1'),
            ]);

        $job->refresh();
        $currentWarnings = (int) $job->gps_warning_count;
        $flowStopped = $currentWarnings >= $maxWarnings;

        if ($updated > 0) {
            $job->loadMissing('rider');

            $this->notifier->notifyRider(
                $job->rider,
                $job,
                'GPS ขาดการเชื่อมต่อ',
                "ระบบไม่ได้รับตำแหน่งของคุณ ({$currentWarnings}/{$maxWarnings}) กรุณาเปิด GPS และเปิดแอปค้างไว้ระหว่างส่งงาน #{$job->job_number}",
                'gps_lost'
            );

            if ($flowStopped) {
                Log::warning('RiderGPS: GPS lost too many times', [
                    'job_id' => $job->id,
                    'warnings' => $currentWarnings,
                    'max' => $maxWarnings,
                ]);

                $this->notifier->notifyAdmins(
                    'ไรเดอร์ปิด GPS ระหว่างส่งงานเกินกำหนด',
                    "งาน #{$job->job_number} GPS หาย {$currentWarnings} ครั้ง (ไรเดอร์ ".($job->rider?->full_name ?? '-').') กรุณาติดต่อไรเดอร์',
                    ['job_id' => (int) $job->id, 'rider_id' => $job->rider_id]
                );
            }
        }

        return [
            'warning_count' => $currentWarnings,
            'max_warnings' => $maxWarnings,
            'flow_stopped' => $flowStopped,
            'message' => $flowStopped
                ? "🚫 GPS ถูกปิดเกินจำนวนที่กำหนด ({$currentWarnings}/{$maxWarnings})\nงานถูกระงับชั่วคราว กรุณาติดต่อแอดมิน"
                : "⚠️ คุณปิด GPS แล้ว ({$currentWarnings}/{$maxWarnings})\nกรุณาเปิด GPS เพื่อดำเนินการต่อ\n\nถ้าต้องการปิดจริงๆ งานจัดส่งจะถูกระงับชั่วคราว",
        ];
    }

    /**
     * GPS กลับมาแล้ว (เรียกอัตโนมัติจาก recordRiderLocation)
     */
    public function handleGpsResume(RiderJob $job): void
    {
        $job->resumeFromGpsLoss();

        Log::info('RiderGPS: GPS resumed', ['job_id' => $job->id]);
    }

    /**
     * ไรเดอร์ยืนยันปิด GPS เอง → หยุดติดตามชั่วคราว (แอดมินเห็นในรายการงาน)
     */
    public function confirmGpsOff(RiderJob $job): array
    {
        $job->update([
            'gps_active' => false,
            'gps_lost_at' => now(),
        ]);

        Log::info('RiderGPS: rider confirmed GPS off', ['job_id' => $job->id]);

        return [
            'message' => "⏸️ ปิด GPS แล้ว งานจัดส่งถูกหยุดชั่วคราว\n\nเปิด GPS อีกครั้งเพื่อดำเนินการต่อ",
        ];
    }

    // =====================================================
    // ตำแหน่งลูกค้า (ลูกค้าต้องยินยอมเอง)
    // =====================================================

    /**
     * ลูกค้าเปิด/ปิดการแชร์ตำแหน่งให้ไรเดอร์ (caller ต้องตรวจว่าเป็นเจ้าของออเดอร์)
     */
    public function setCustomerLocationSharing(RiderJob $job, bool $enabled): void
    {
        if ($enabled && ! $job->isTrackable()) {
            return; // งานจบแล้ว/ยังไม่มีไรเดอร์ → ไม่เริ่มแชร์
        }

        $job->forceFill([
            'customer_share_location' => $enabled,
            'customer_last_latitude' => $enabled ? $job->customer_last_latitude : null,
            'customer_last_longitude' => $enabled ? $job->customer_last_longitude : null,
            'customer_location_at' => $enabled ? $job->customer_location_at : null,
        ])->save();
    }

    /**
     * อัปเดตตำแหน่งลูกค้า (เฉพาะเมื่อเปิดแชร์ + งานยังวิ่งอยู่) — คืน false ถ้าไม่ได้บันทึก
     */
    public function updateCustomerLocation(RiderJob $job, float $latitude, float $longitude): bool
    {
        if (! $job->customer_share_location || ! $job->isTrackable()
            || ! DeliveryFeeCalculator::isValidCoordinate($latitude, $longitude)) {
            return false;
        }

        $job->forceFill([
            'customer_last_latitude' => $latitude,
            'customer_last_longitude' => $longitude,
            'customer_location_at' => now(),
        ])->save();

        return true;
    }

    /**
     * ดึงตำแหน่งลูกค้าสำหรับไรเดอร์ (ที่อยู่จัดส่ง + ตำแหน่งสดถ้าลูกค้ายินยอม)
     */
    public function getCustomerLocation(RiderJob $job): array
    {
        $live = $job->customerLiveLocation();

        return [
            'latitude' => (float) $job->delivery_latitude,
            'longitude' => (float) $job->delivery_longitude,
            'address' => $job->delivery_address,
            'contact_name' => $job->delivery_contact_name,
            'contact_phone' => $job->isTerminal() ? null : $job->delivery_contact_phone,
            'google_maps_url' => "https://www.google.com/maps?q={$job->delivery_latitude},{$job->delivery_longitude}",
            'live_location' => $live,
        ];
    }

    /**
     * ดึงตำแหน่งร้านค้า (pickup) สำหรับไรเดอร์
     */
    public function getPickupLocation(RiderJob $job): array
    {
        return [
            'latitude' => (float) $job->pickup_latitude,
            'longitude' => (float) $job->pickup_longitude,
            'address' => $job->pickup_address,
            'contact_name' => $job->pickup_contact_name,
            'contact_phone' => $job->isTerminal() ? null : $job->pickup_contact_phone,
            'google_maps_url' => "https://www.google.com/maps?q={$job->pickup_latitude},{$job->pickup_longitude}",
        ];
    }

    /**
     * สร้าง Google Maps static image URL
     */
    public function getStaticMapUrl(float $lat, float $lng, int $zoom = 15, string $size = '600x300'): string
    {
        $apiKey = config('services.google_maps.api_key', '');

        return "https://maps.googleapis.com/maps/api/staticmap?center={$lat},{$lng}&zoom={$zoom}&size={$size}&markers=color:red|{$lat},{$lng}&key={$apiKey}";
    }

    /**
     * แจ้งลิงก์ติดตามให้ลูกค้า (in-app + Expo push — ไม่ใช้ LINE push)
     */
    public function sendTrackingLinkToBuyer(RiderJob $job): void
    {
        if (! $job->tracking_token || ! $job->customer_id) {
            return;
        }

        $trackingUrl = $this->getTrackingUrl($job);
        $riderName = $job->rider?->full_name ?? 'ไรเดอร์';

        $this->notifier->notifyUser(
            (int) $job->customer_id,
            'delivery_update',
            'ไรเดอร์กำลังมา',
            "{$riderName} รับงาน #{$job->job_number} แล้ว กดเพื่อติดตามตำแหน่ง",
            ['type' => 'delivery_update', 'event' => 'tracking_link', 'job_id' => (int) $job->id, 'tracking_url' => $trackingUrl],
            $trackingUrl
        );
    }
}
