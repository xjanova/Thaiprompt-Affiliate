<?php

namespace App\Services\Rider;

use App\Exceptions\HandoverException;
use App\Exceptions\RiderJobException;
use App\Models\DeliveryHandover;
use App\Models\EarningsLedger;
use App\Models\FreshMarketOrder;
use App\Models\Order;
use App\Models\PlatformTransaction;
use App\Models\Rider;
use App\Models\RiderHeart;
use App\Models\RiderJob;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\DeliveryFeeCalculator;
use App\Services\FreshMarketService;
use App\Services\Media\ProfilePhotoService;
use App\Services\NotificationService;
use App\Services\RiderEarningService;
use App\Services\RiderJobService;
use App\Services\RiderNotificationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * 🤝 ส่งมอบของจากไรเดอร์ถึงผู้ซื้อ (ไรเดอร์รอบ 2, 2026-10-04 — เลน money)
 *
 * กติกาเจ้าของระบบ: เงินผู้ซื้อพักไว้ ไม่แบ่งให้ใคร (ร้าน/ไรเดอร์/ผู้แนะนำ/เงินคืน) จนกว่าส่งมอบสำเร็จ
 *
 * ปิดงานได้ 4 ทาง:
 *   1. สแกนใส่กัน — ไรเดอร์สแกน QR ของผู้ซื้อ (หรือกรอกรหัส 6 หลักของผู้ซื้อ)
 *      + ผู้ซื้อสแกน QR ของไรเดอร์ (หรือกรอกรหัส 6 หลักของไรเดอร์)
 *      ครบสองฝ่าย → งาน delivering → delivered → completed + การส่งมอบ completed ใน transaction เดียว
 *   2. ผู้ซื้อไม่ออกมารับ — รูปรอบ 1 ที่จุดส่ง (เริ่มนับรอ) → ครบเวลารอ → รูปรอบ 2 → งาน awaiting_release
 *      (ไรเดอร์ว่างรับงานใหม่) → ปลดเงินอัตโนมัติเมื่อครบ rider.handover_auto_release_hours ถ้าผู้ซื้อไม่ร้องเรียน
 *   3. ผู้ซื้อกด "ได้รับของแล้ว" ระหว่างทางสำรอง (รอผู้รับ/วางของแล้ว) → ปิดทันที (method buyer_confirm)
 *   4. ร้องเรียน → ไรเดอร์ถูกปล่อย (งาน awaiting_release) → แอดมินตัดสิน: ปล่อยเงิน หรือคืนเงินผู้ซื้อเต็มจำนวน
 *
 * งานจะ "ต้องสแกนส่งมอบ" (handover_required) ก็ต่อเมื่อผู้ซื้อสั่งและไรเดอร์รับงานจากแอปรุ่นที่รองรับ
 * (ตัดสินตอนรับงาน — RiderJob::handoverRequiredOnAccept) ไม่งั้นเป็นงานแบบเดิมที่กดส่งของได้
 *
 * ความปลอดภัย:
 *   - QR = ข้อมูลสั้นที่เซ็นด้วยกุญแจลับต่องาน (HMAC-SHA256) ผูก id การส่งมอบ + ฝั่ง (b=ผู้ซื้อ, r=ไรเดอร์) + ช่วงเวลา
 *     เปลี่ยนทุก rider.handover_qr_ttl_seconds รับเฉพาะช่วงปัจจุบันและช่วงก่อนหน้า · เทียบลายเซ็นแบบ constant-time
 *   - QR ของผู้ซื้อใช้ได้กับไรเดอร์ของงานเท่านั้น / QR ของไรเดอร์ใช้ได้กับผู้ซื้อของออเดอร์เท่านั้น (ฝั่งผิด = ใช้ไม่ได้)
 *   - รหัส 6 หลักของผู้ซื้อเก็บเป็น hash / ของไรเดอร์ได้จากกุญแจลับ · ผิดครบ 5 ครั้ง ล็อก 10 นาที แยกตัวนับต่อฝั่ง
 *     (นับครั้งผิดใน transaction แยก — ไม่ถูก rollback)
 *   - ไรเดอร์ต้องอยู่ในรัศมี rider.handover_geofence_m ของจุดส่งทุกครั้งที่ยืนยัน/ถ่ายรูป
 *     + ตำแหน่งล่าสุดที่เซิร์ฟเวอร์รู้ (ถ้าสดไม่เกิน 2 นาที) ต้องไม่ไกลเกินรัศมี + 200 ม. (กันปลอมพิกัดในคำขอ)
 *     ยกเว้นผู้ซื้อยืนยันรับของไปแล้ว → ไม่ขังการยืนยันของไรเดอร์ด้วยรัศมี (บันทึกระยะไว้ตรวจย้อนหลัง)
 *   - ทุกการเปลี่ยนสถานะ: ล็อกแถวงานก่อน แล้วค่อยแถวส่งมอบ (ลำดับเดียวกันทุกเส้นทาง กัน deadlock) · กดซ้ำ = ได้ผลเดิม
 */
class HandoverService
{
    public const TOKEN_PREFIX = 'TPH1';

    public const SIDE_BUYER = 'b';

    public const SIDE_RIDER = 'r';

    public const MAX_CODE_ATTEMPTS = 5;

    public const CODE_LOCK_MINUTES = 10;

    public const SOURCE_SHOP = 'shop';

    public const SOURCE_FRESH_MARKET = 'fresh-market';

    public const DISPUTE_REASONS = ['not_received', 'wrong_item', 'damaged', 'other'];

    /** เก็บรูปทางสำรองบน private disk (รูปหน้าบ้านผู้ซื้อ — ห้ามเปิดสาธารณะ) */
    public const PHOTO_DISK = 'local';

    /** สถานะที่ยังสแกน/กรอกรหัสได้ */
    public const SCANNABLE_STATUSES = [
        DeliveryHandover::STATUS_WAITING,
        DeliveryHandover::STATUS_RIDER_CONFIRMED,
        DeliveryHandover::STATUS_BUYER_CONFIRMED,
        DeliveryHandover::STATUS_FALLBACK_WAITING,
    ];

    /** สถานะงานที่ไรเดอร์ถือของอยู่ (ส่งมอบได้) */
    public const JOB_HANDOVER_STATUSES = ['picked_up', 'delivering'];

    /** ตำแหน่งล่าสุดของไรเดอร์บนเซิร์ฟเวอร์ "สด" ไม่เกินกี่วินาที (ใช้ตรวจซ้ำรัศมีจุดส่ง) */
    public const SERVER_LOCATION_FRESH_SECONDS = 120;

    /** ตำแหน่งบนเซิร์ฟเวอร์ห่างจุดส่งเกินรัศมี + ค่านี้ (เมตร) = ปฏิเสธ */
    public const SERVER_LOCATION_SLACK_M = 200;

    /** สถานะงานที่แอดมินตัดสินการส่งมอบได้ (รวมงานที่ถูกปิดเป็น failed ไปแล้ว) */
    public const ADMIN_RESOLVABLE_JOB_STATUSES = ['picked_up', 'delivering', RiderJob::STATUS_AWAITING_RELEASE, 'failed'];

    public function __construct(
        private readonly DeliveryFeeCalculator $config,
        private readonly RiderJobService $jobs,
        private readonly RiderNotificationService $notifier,
    ) {}

    // =====================================================
    // แถวส่งมอบ
    // =====================================================

    /**
     * แถวส่งมอบของงาน (สร้างให้ถ้ายังไม่มี) — null = งานแบบเดิมที่ไม่ต้องสแกน
     *
     * สร้างพร้อมกันหลายคำขอได้ปลอดภัย (rider_job_id unique + createOrFirst)
     */
    public function ensureFor(RiderJob $job): ?DeliveryHandover
    {
        if (! $job->handover_required) {
            return null;
        }

        $existing = DeliveryHandover::where('rider_job_id', $job->id)->first();
        if ($existing) {
            return $existing;
        }

        $secret = base64_encode(random_bytes(32));

        $handover = DeliveryHandover::createOrFirst(
            ['rider_job_id' => $job->id],
            [
                'buyer_user_id' => $this->buyerUserIdFor($job),
                'status' => DeliveryHandover::STATUS_WAITING,
                'secret' => $secret,
                'code_hash' => Hash::make(self::deriveCode($secret)),
                'code_attempts' => 0,
            ]
        );

        $job->setRelation('handover', $handover);

        return $handover;
    }

    /**
     * ผู้ซื้อของงาน = ผู้ซื้อของออเดอร์ต้นทาง (ไม่มีออเดอร์ = customer_id ของงาน)
     */
    public function buyerUserIdFor(RiderJob $job): ?int
    {
        $source = $job->deliverableSource();
        $id = $source?->riderCustomerUserId() ?? $job->customer_id;

        return $id ? (int) $id : null;
    }

    // =====================================================
    // ฝั่งผู้ซื้อ
    // =====================================================

    /**
     * GET /orders/{source}/{id}/handover
     *
     * @return array<string, mixed>
     */
    public function buyerView(User $buyer, string $source, int $orderId): array
    {
        [$order, $job] = $this->resolveBuyerJob($buyer, $source, $orderId);

        return $this->buyerPayload($buyer, $order, $job);
    }

    /**
     * POST /orders/{source}/{id}/handover/scan {token} | {code}
     * ผู้ซื้อสแกน QR บนมือถือไรเดอร์ (หรือกรอกรหัส 6 หลักที่ไรเดอร์แจ้ง — กล้องใช้ไม่ได้) = ยืนยันว่าได้รับของ
     *
     * @return array<string, mixed>
     */
    public function buyerScan(User $buyer, string $source, int $orderId, ?string $token, ?string $code = null): array
    {
        [$order, $job] = $this->resolveBuyerJob($buyer, $source, $orderId);
        $handover = $this->requireHandover($job);

        // กดซ้ำหลังยืนยันแล้ว → ได้ผลเดิม
        if ($handover->buyer_confirmed_at !== null) {
            return $this->buyerPayload($buyer, $order, $job);
        }

        $this->assertScannable($job, $handover);

        $usedCode = false;
        if ($token !== null && trim($token) !== '') {
            $this->verifyToken($handover, $token, self::SIDE_RIDER);
        } else {
            $this->verifyCode($handover, (string) $code, self::SIDE_RIDER);
            $usedCode = true;
        }

        $completed = DB::transaction(function () use ($job, $usedCode) {
            [$lockedJob, $locked] = $this->lockPair($job);

            if ($locked->buyer_confirmed_at !== null) {
                return false;
            }

            $this->assertScannable($lockedJob, $locked);

            $locked->buyer_confirmed_at = now();

            // ฝั่งใดฝั่งหนึ่งใช้รหัส = ปิดงานด้วยวิธี code
            $method = $usedCode || $locked->method === 'code' ? 'code' : ($locked->method ?: 'qr');

            if ($locked->rider_confirmed_at !== null) {
                $this->completeLocked($lockedJob, $locked, $method);

                return true;
            }

            $locked->status = DeliveryHandover::STATUS_BUYER_CONFIRMED;
            if ($usedCode) {
                $locked->method = 'code';
            }
            $locked->save();

            return false;
        });

        $job->refresh()->unsetRelation('handover');

        if ($completed) {
            $this->afterCompleted($job);
        }

        return $this->buyerPayload($buyer, $order, $job);
    }

    /**
     * POST /orders/{source}/{id}/handover/dispute {reason, note?} — ผู้ซื้อแจ้งว่าไม่ได้รับของ/ของผิด/เสียหาย
     *
     * @return array<string, mixed>
     */
    public function buyerDispute(User $buyer, string $source, int $orderId, string $reason, ?string $note): array
    {
        [$order, $job] = $this->resolveBuyerJob($buyer, $source, $orderId);
        $handover = $this->requireHandover($job);

        // กดซ้ำ → ได้ผลเดิม
        if ($handover->status === DeliveryHandover::STATUS_DISPUTED) {
            return $this->buyerPayload($buyer, $order, $job);
        }

        if (! $this->canDispute($job, $handover)) {
            throw HandoverException::disputeNotAllowed();
        }

        $note = $note !== null && trim($note) !== '' ? mb_substr(trim($note), 0, 1000) : null;

        $changed = DB::transaction(function () use ($job, $reason, $note) {
            [$lockedJob, $locked] = $this->lockPair($job);

            if ($locked->status === DeliveryHandover::STATUS_DISPUTED) {
                return false;
            }

            if (! $this->canDispute($lockedJob, $locked)) {
                throw HandoverException::disputeNotAllowed();
            }

            $locked->forceFill([
                'status' => DeliveryHandover::STATUS_DISPUTED,
                'disputed_at' => now(),
                'dispute_reason' => $reason,
                'dispute_note' => $note,
            ])->save();

            // ร้องเรียนระหว่างไรเดอร์ยังถือของ → ปล่อยไรเดอร์ไปรับงานอื่น (งาน awaiting_release)
            // เงินยังพักอยู่ · ไม่ปลดอัตโนมัติระหว่างร้องเรียน (releaseDue ข้ามสถานะ disputed) · แอดมินตัดสิน
            if (in_array($lockedJob->status, self::JOB_HANDOVER_STATUSES, true)) {
                $this->jobs->markAwaitingReleaseLocked($lockedJob, $this->trackingUntilForRelease(), false);
            }

            return true;
        });

        $job->refresh()->unsetRelation('handover');

        if ($changed) {
            Log::warning('Handover: disputed', ['job_id' => $job->id, 'reason' => $reason]);

            $this->notifier->notifyAdmins(
                'ผู้ซื้อร้องเรียนการส่งมอบ ต้องตัดสิน',
                "งาน #{$job->job_number}: ".self::disputeReasonText($reason).($note ? " — {$note}" : '').' กรุณาเลือกปล่อยเงินหรือคืนเงินผู้ซื้อ',
                array_merge($this->pushBase($job), ['reason' => $reason]),
                $this->adminJobUrl($job),
                'handover_disputed',
            );

            $riderUserId = $this->riderUserIdFor($job);
            if ($riderUserId) {
                $this->notifier->notifyUsers(
                    [$riderUserId],
                    'handover_disputed',
                    'ผู้รับแจ้งร้องเรียนการส่งมอบ',
                    "ทีมงานกำลังตรวจสอบงาน #{$job->job_number} รายได้งานนี้พักไว้จนกว่าจะได้ข้อสรุป คุณรับงานใหม่ได้ตามปกติ",
                    $this->pushData($job, 'rider'),
                    null,
                    'high',
                );
            }
        }

        return $this->buyerPayload($buyer, $order, $job);
    }

    /**
     * POST /orders/{source}/{id}/handover/confirm-received — ผู้ซื้อกด "ได้รับของแล้ว"
     *
     * ใช้ได้ระหว่างทางสำรอง: ไรเดอร์ถ่ายรูปรอที่จุดส่งอยู่ (fallback_waiting) หรือวางของแล้วรอปลดเงิน
     * (fallback_pending_release) — ผู้ซื้อออกมาเจอ/เจอของแล้ว → ปิดงานทันที ปล่อยเงิน (method buyer_confirm)
     *
     * @return array<string, mixed>
     */
    public function buyerConfirmReceived(User $buyer, string $source, int $orderId): array
    {
        [$order, $job] = $this->resolveBuyerJob($buyer, $source, $orderId);
        $handover = $this->requireHandover($job);

        // กดซ้ำหลังยืนยันสำเร็จ → ได้ผลเดิม
        if ($handover->isFinal() && $handover->method === 'buyer_confirm') {
            return $this->buyerPayload($buyer, $order, $job);
        }

        if ($handover->isFinal()) {
            throw HandoverException::final();
        }

        if (! $this->canConfirmReceived($job, $handover)) {
            throw HandoverException::notReady('ยืนยันรับของได้เมื่อไรเดอร์ถึงจุดส่งแล้วรอผู้รับ หรือวางของไว้ให้แล้วเท่านั้น');
        }

        $completed = DB::transaction(function () use ($job) {
            [$lockedJob, $locked] = $this->lockPair($job);

            if ($locked->isFinal()) {
                if ($locked->method === 'buyer_confirm') {
                    return false;
                }

                throw HandoverException::final();
            }

            if (! $this->canConfirmReceived($lockedJob, $locked)) {
                throw HandoverException::notReady('ยืนยันรับของได้เมื่อไรเดอร์ถึงจุดส่งแล้วรอผู้รับ หรือวางของไว้ให้แล้วเท่านั้น');
            }

            $locked->buyer_confirmed_at = now();

            // พิกัดจุดที่ไรเดอร์ถ่ายรูปล่าสุด (รอบ 2 ถ้ามี ไม่งั้นรอบ 1)
            $lat = $locked->waited_latitude ?? $locked->arrival_latitude;
            $lng = $locked->waited_longitude ?? $locked->arrival_longitude;

            $this->completeLocked(
                $lockedJob,
                $locked,
                'buyer_confirm',
                DeliveryHandover::STATUS_COMPLETED,
                $lat !== null ? (float) $lat : null,
                $lng !== null ? (float) $lng : null,
            );

            return true;
        });

        $job->refresh()->unsetRelation('handover');

        if ($completed) {
            Log::info('Handover: buyer confirmed received', ['job_id' => $job->id]);
            $this->afterCompleted($job);
        }

        return $this->buyerPayload($buyer, $order, $job);
    }

    // =====================================================
    // ฝั่งไรเดอร์
    // =====================================================

    /**
     * GET /rider/jobs/{id}/handover
     *
     * @return array<string, mixed>
     */
    public function riderView(Rider $rider, RiderJob $job): array
    {
        $this->assertRiderJob($rider, $job);

        return $this->riderPayload($rider, $job);
    }

    /**
     * POST /rider/jobs/{id}/handover/scan {token?|code?, latitude, longitude}
     * ไรเดอร์สแกน QR ของผู้ซื้อ หรือกรอกรหัส 6 หลักที่ผู้ซื้ออ่านให้ฟัง — ต้องอยู่ในรัศมีจุดส่ง
     *
     * @return array<string, mixed>
     */
    public function riderScan(Rider $rider, RiderJob $job, ?string $token, ?string $code, mixed $lat, mixed $lng): array
    {
        [$lat, $lng] = $this->requireLocation($lat, $lng);
        $this->assertRiderJob($rider, $job);
        $handover = $this->requireHandover($job);

        if ($handover->rider_confirmed_at !== null) {
            return $this->riderPayload($rider, $job);
        }

        $this->assertScannable($job, $handover);

        // ผู้ซื้อยืนยันรับของแล้ว (สแกน QR/กรอกรหัสของไรเดอร์) → ไม่ขังการยืนยันด้วยรัศมี บันทึกระยะไว้ตรวจย้อนหลัง
        if ($handover->buyer_confirmed_at !== null) {
            $distance = $this->distanceToDropoff($job, $lat, $lng);
            Log::info('Handover: rider confirm after buyer confirmed, geofence skipped', ['job_id' => $job->id, 'distance_m' => $distance]);
        } else {
            $distance = $this->assertWithinGeofence($job, $lat, $lng, $rider);
        }

        $method = 'qr';
        if ($token !== null && $token !== '') {
            $this->verifyToken($handover, $token, self::SIDE_BUYER);
        } else {
            $this->verifyCode($handover, (string) $code, self::SIDE_BUYER);
            $method = 'code';
        }

        $completed = DB::transaction(function () use ($job, $lat, $lng, $distance, $method) {
            [$lockedJob, $locked] = $this->lockPair($job);

            if ($locked->rider_confirmed_at !== null) {
                return false;
            }

            $this->assertScannable($lockedJob, $locked);

            // ฝั่งใดฝั่งหนึ่งใช้รหัส = ปิดงานด้วยวิธี code
            $method = $method === 'code' || $locked->method === 'code' ? 'code' : $method;

            $locked->forceFill([
                'rider_confirmed_at' => now(),
                'rider_confirm_latitude' => $lat,
                'rider_confirm_longitude' => $lng,
                'rider_confirm_distance_m' => $distance,
                'method' => $method,
            ]);

            if ($locked->buyer_confirmed_at !== null) {
                $this->completeLocked($lockedJob, $locked, $method);

                return true;
            }

            $locked->status = DeliveryHandover::STATUS_RIDER_CONFIRMED;
            $locked->save();

            return false;
        });

        $job->refresh()->unsetRelation('handover');

        if ($completed) {
            $this->afterCompleted($job);
        }

        return $this->riderPayload($rider, $job);
    }

    /**
     * POST /rider/jobs/{id}/handover/arrival-photo — ถึงจุดส่งแล้วผู้ซื้อไม่ออกมา: รูปรอบ 1 + เริ่มนับเวลารอ
     *
     * @return array<string, mixed>
     */
    public function arrivalPhoto(Rider $rider, RiderJob $job, UploadedFile $photo, mixed $lat, mixed $lng): array
    {
        [$lat, $lng] = $this->requireLocation($lat, $lng);
        $this->assertRiderJob($rider, $job);
        $handover = $this->requireHandover($job);

        // กดซ้ำ → ได้ผลเดิม (ไม่เก็บรูปใหม่ ไม่เลื่อนเวลารอ)
        if ($handover->arrival_photo_at !== null && $handover->status === DeliveryHandover::STATUS_FALLBACK_WAITING) {
            return $this->riderPayload($rider, $job);
        }

        $this->assertCanArrivalPhoto($job, $handover);
        $distance = $this->assertWithinGeofence($job, $lat, $lng, $rider);

        $path = $this->storePhoto($photo, $job, 'arrival');

        try {
            $changed = DB::transaction(function () use ($job, $lat, $lng, $distance, $path) {
                [$lockedJob, $locked] = $this->lockPair($job);

                if ($locked->status === DeliveryHandover::STATUS_FALLBACK_WAITING) {
                    return false;
                }

                $this->assertCanArrivalPhoto($lockedJob, $locked);

                $waitSeconds = max(0, $this->config->intSetting('rider.handover_wait_seconds'));

                $locked->forceFill([
                    'status' => DeliveryHandover::STATUS_FALLBACK_WAITING,
                    'arrival_photo_path' => $path,
                    'arrival_photo_at' => now(),
                    'arrival_latitude' => $lat,
                    'arrival_longitude' => $lng,
                    'arrival_distance_m' => $distance,
                    'wait_until' => now()->addSeconds($waitSeconds),
                ])->save();

                return true;
            });
        } catch (\Throwable $e) {
            $this->deletePhoto($path);
            throw $e;
        }

        if (! $changed) {
            $this->deletePhoto($path);
        }

        $job->refresh()->unsetRelation('handover');

        if ($changed) {
            $buyerId = $this->buyerUserIdFor($job);
            $this->notifier->notifyUsers(
                [$buyerId],
                'handover_arrived',
                'ไรเดอร์มาถึงแล้ว',
                'ไรเดอร์รออยู่ที่จุดส่งของคุณ กรุณาออกมารับของแล้วสแกน QR ของไรเดอร์ งาน #'.$job->job_number,
                $this->pushData($job, 'buyer', ['screen' => 'order-handover']),
                null,
                'high',
            );
        }

        return $this->riderPayload($rider, $job);
    }

    /**
     * POST /rider/jobs/{id}/handover/waited-photo — รอครบเวลาแล้ว: รูปรอบ 2 → วางของ งานรอปลดเงิน (ไรเดอร์ว่าง)
     *
     * @return array<string, mixed>
     */
    public function waitedPhoto(Rider $rider, RiderJob $job, UploadedFile $photo, mixed $lat, mixed $lng): array
    {
        [$lat, $lng] = $this->requireLocation($lat, $lng);
        $this->assertRiderJob($rider, $job);
        $handover = $this->requireHandover($job);

        if ($handover->status === DeliveryHandover::STATUS_FALLBACK_PENDING_RELEASE) {
            return $this->riderPayload($rider, $job);
        }

        $this->assertCanWaitedPhoto($job, $handover);
        $this->assertWithinGeofence($job, $lat, $lng, $rider);

        $path = $this->storePhoto($photo, $job, 'waited');
        $autoReleaseAt = null;

        try {
            $changed = DB::transaction(function () use ($job, $lat, $lng, $path, &$autoReleaseAt) {
                [$lockedJob, $locked] = $this->lockPair($job);

                if ($locked->status === DeliveryHandover::STATUS_FALLBACK_PENDING_RELEASE) {
                    return false;
                }

                $this->assertCanWaitedPhoto($lockedJob, $locked);

                $hours = max(1, $this->config->intSetting('rider.handover_auto_release_hours'));
                $autoReleaseAt = now()->addHours($hours);

                $locked->forceFill([
                    'status' => DeliveryHandover::STATUS_FALLBACK_PENDING_RELEASE,
                    'method' => 'fallback',
                    'waited_photo_path' => $path,
                    'waited_photo_at' => now(),
                    'waited_latitude' => $lat,
                    'waited_longitude' => $lng,
                    'auto_release_at' => $autoReleaseAt,
                ])->save();

                // ลิงก์ติดตามของผู้ซื้อใช้ได้จนถึงเวลาปลดเงิน + ช่วงผ่อนผัน
                $grace = max(1, $this->config->intSetting('rider.tracking_grace_minutes'));
                $this->jobs->markAwaitingReleaseLocked($lockedJob, $autoReleaseAt->copy()->addMinutes($grace));

                return true;
            });
        } catch (\Throwable $e) {
            $this->deletePhoto($path);
            throw $e;
        }

        if (! $changed) {
            $this->deletePhoto($path);
        }

        $job->refresh()->unsetRelation('handover');

        if ($changed) {
            $hours = max(1, $this->config->intSetting('rider.handover_auto_release_hours'));
            $this->notifier->notifyUsers(
                [$this->buyerUserIdFor($job)],
                'handover_auto_release_scheduled',
                'ไรเดอร์วางของไว้ให้แล้ว',
                "ไรเดอร์รอแล้วไม่พบผู้รับ จึงวางของไว้ที่จุดส่งพร้อมถ่ายรูปไว้ ถ้าไม่ได้รับของ กรุณาแจ้งภายใน {$hours} ชั่วโมง งาน #{$job->job_number}",
                $this->pushData($job, 'buyer', ['screen' => 'order-handover', 'auto_release_at' => $autoReleaseAt?->toIso8601String()]),
                null,
                'high',
            );
        }

        return $this->riderPayload($rider, $job);
    }

    // =====================================================
    // ระบบ / แอดมิน
    // =====================================================

    /**
     * ปลดเงินอัตโนมัติ: งานที่วางของแล้ว ครบเวลา และผู้ซื้อไม่ร้องเรียน (rider:handover-release ทุกนาที)
     *
     * @return int จำนวนที่ปลดได้
     */
    public function releaseDue(int $limit = 100): int
    {
        $ids = DeliveryHandover::query()
            ->where('status', DeliveryHandover::STATUS_FALLBACK_PENDING_RELEASE)
            ->whereNull('disputed_at')
            ->whereNotNull('auto_release_at')
            ->where('auto_release_at', '<=', now())
            // งานถูกปิดทางอื่นไปแล้ว (เช่น แอดมินยกเลิกออเดอร์/คืนเงิน → failed) → ไม่ต้องปลด
            ->whereIn('rider_job_id', RiderJob::query()->where('status', RiderJob::STATUS_AWAITING_RELEASE)->select('id'))
            ->orderBy('auto_release_at')
            ->limit(max(1, $limit))
            ->pluck('rider_job_id');

        $released = 0;

        foreach ($ids as $jobId) {
            $job = RiderJob::find($jobId);
            if (! $job) {
                continue;
            }

            try {
                $done = DB::transaction(function () use ($job) {
                    [$lockedJob, $locked] = $this->lockPair($job);

                    // ตรวจซ้ำหลังล็อก: ผู้ซื้ออาจร้องเรียนพอดี
                    if ($locked->status !== DeliveryHandover::STATUS_FALLBACK_PENDING_RELEASE
                        || $lockedJob->status !== RiderJob::STATUS_AWAITING_RELEASE
                        || $locked->disputed_at !== null
                        || $locked->auto_release_at === null
                        || $locked->auto_release_at->isFuture()) {
                        return false;
                    }

                    $this->completeLocked($lockedJob, $locked, 'fallback', DeliveryHandover::STATUS_RELEASED);

                    return true;
                });
            } catch (\Throwable $e) {
                Log::error('Handover: auto release failed', ['job_id' => $jobId, 'error' => $e->getMessage()]);

                continue;
            }

            if ($done) {
                $released++;
                $this->afterCompleted($job->fresh());
            }
        }

        return $released;
    }

    /**
     * แอดมินตัดสิน "ปล่อยเงิน" — ปิดงานเหมือนส่งสำเร็จ แบ่งเงินตามปกติ
     *
     * ตัดสินได้ทุกสถานะที่ยังไม่จบ (รอสแกน / ยืนยันฝั่งเดียว / รอผู้รับ / วางของ / ร้องเรียน)
     * รวมงานที่ถูกปิดเป็น failed ไปแล้ว ถ้าออเดอร์ยังไม่ถูกยกเลิก/คืนเงิน และยังไม่มีงานส่งใหม่แทน
     *
     * @throws HandoverException HANDOVER_FINAL | HANDOVER_NOT_READY
     */
    public function adminRelease(RiderJob $job, User $admin, ?string $note): DeliveryHandover
    {
        $handover = $this->requireHandover($job);
        if ($handover->isFinal()) {
            throw HandoverException::final();
        }

        $note = $note !== null && trim($note) !== '' ? mb_substr(trim($note), 0, 1000) : null;

        DB::transaction(function () use ($job, $admin, $note) {
            [$lockedJob, $locked] = $this->lockPair($job);

            if ($locked->isFinal()) {
                throw HandoverException::final();
            }

            if (! $this->adminCanResolve($lockedJob, $locked)) {
                throw HandoverException::notReady('การส่งมอบนี้ยังไม่อยู่ในสถานะที่แอดมินตัดสินได้');
            }

            // งานที่ถูกปิดเป็น failed: ปล่อยเงินได้เฉพาะเมื่อออเดอร์ยังเปิดอยู่ (ยกเลิก/คืนเงินไปแล้ว = ไม่มีเงินให้ปล่อย)
            if ($lockedJob->status === 'failed' && $this->sourceClosed($lockedJob)) {
                throw HandoverException::notReady('ออเดอร์นี้ถูกยกเลิกหรือคืนเงินไปแล้ว ปล่อยเงินไม่ได้ (ใช้ "คืนเงินผู้ซื้อ" เพื่อปิดเรื่อง)');
            }

            $locked->forceFill([
                'resolved_at' => now(),
                'resolved_by' => $admin->id,
                'resolution' => 'release',
                'resolution_note' => $note,
            ]);

            $lat = $locked->rider_confirm_latitude ?? $locked->waited_latitude ?? $locked->arrival_latitude;
            $lng = $locked->rider_confirm_longitude ?? $locked->waited_longitude ?? $locked->arrival_longitude;

            $this->completeLocked(
                $lockedJob,
                $locked,
                'admin',
                DeliveryHandover::STATUS_RELEASED,
                $lat !== null ? (float) $lat : null,
                $lng !== null ? (float) $lng : null,
                $lockedJob->status === 'failed',
            );
        });

        Log::info('Handover: admin released', ['job_id' => $job->id, 'admin_id' => $admin->id]);

        $fresh = $job->fresh();
        $this->afterCompleted($fresh, false);
        $this->notifyResolved($fresh, 'release');

        return $fresh->handover()->first();
    }

    /**
     * แอดมินตัดสิน "คืนเงิน" — งานส่งไม่สำเร็จ (ไรเดอร์ไม่ได้ค่าส่ง) + ยกเลิกออเดอร์ คืนเงินผู้ซื้อเต็มจำนวน
     *
     * ทั้งหมดใน transaction เดียว: คืนเงินไม่สำเร็จ = ไม่มีอะไรเปลี่ยน (แอดมินเห็นข้อความแล้วแก้ต้นเหตุก่อน)
     *
     * - ของถึงมือผู้ซื้อแล้ว (วางของ/สแกนเจอกันแล้ว) → ไม่คืนสต็อกเข้าร้าน
     *   ไรเดอร์ยังถือของอยู่ (รอสแกน/รอผู้รับ) → คืนสต็อกตามปกติ และแจ้งไรเดอร์ให้นำของคืนร้าน
     * - แจ้งเตือนจากขั้นย่อย (งานส่งไม่สำเร็จ / ออเดอร์ถูกยกเลิก / คืนเงิน) ถูกปิดไว้
     *   แล้วแจ้งข้อความเดียวที่ตรงเหตุการณ์: ผู้ซื้อ · ไรเดอร์ · ร้าน (handover_resolved) + แอดมิน
     * - ออเดอร์ถูกยกเลิก/คืนเงินไปก่อนแล้ว → ปิดเรื่องอย่างเดียว ไม่คืนเงินซ้ำ
     *
     * @throws HandoverException|\Throwable
     */
    public function adminRefund(RiderJob $job, User $admin, string $note): DeliveryHandover
    {
        $handover = $this->requireHandover($job);
        if ($handover->isFinal()) {
            throw HandoverException::final();
        }

        $note = mb_substr(trim($note), 0, 1000);
        $reason = 'แอดมินตัดสินคืนเงินผู้ซื้อ (ร้องเรียนการส่งมอบ)'.($note !== '' ? ": {$note}" : '');
        $alreadyClosed = false;
        $itemWithBuyer = true;

        NotificationService::muteUsersDuring($this->resolutionAudience($job), function () use ($job, $admin, $note, $reason, &$alreadyClosed, &$itemWithBuyer) {
            DB::transaction(function () use ($job, $admin, $note, $reason, &$alreadyClosed, &$itemWithBuyer) {
                [$lockedJob, $locked] = $this->lockPair($job);

                if ($locked->isFinal()) {
                    throw HandoverException::final();
                }

                if (! $this->adminCanResolve($lockedJob, $locked)) {
                    throw HandoverException::notReady('การส่งมอบนี้ยังไม่อยู่ในสถานะที่แอดมินตัดสินได้');
                }

                $locked->forceFill([
                    'status' => DeliveryHandover::STATUS_REFUNDED,
                    'method' => 'admin',
                    'resolved_at' => now(),
                    'resolved_by' => $admin->id,
                    'resolution' => 'refund',
                    'resolution_note' => $note !== '' ? $note : null,
                ])->save();

                $alreadyClosed = $this->sourceClosed($lockedJob);
                $itemWithBuyer = $this->itemLeftWithBuyer($lockedJob, $locked);

                // 1) งานไรเดอร์ → failed (admin_intervention) — ไม่เคลียร์เงิน ไรเดอร์ไม่ได้ค่าส่ง · ไม่แจ้งข้อความ "ส่งไม่สำเร็จ"
                $this->jobs->adminFail($lockedJob, $admin, $reason, false);

                if ($alreadyClosed) {
                    return;
                }

                // 2) ยกเลิกออเดอร์ + คืนเงินเต็มจำนวน (ยังไม่มีการแบ่งเงิน → ไม่ต้องดึงคืนจากใคร)
                //    ของอยู่กับผู้ซื้อแล้ว → ไม่คืนสต็อกเข้าร้าน
                $source = $lockedJob->fresh()->deliverableSource();
                if ($source instanceof Order) {
                    if ($itemWithBuyer && $source->stock_deducted_at !== null) {
                        // Order::cancel คืนสต็อกเฉพาะออเดอร์ที่ stock_deducted_at ยังไม่ว่าง → ล้างไว้ก่อน (ของไม่ได้กลับร้าน)
                        Order::whereKey($source->id)->update(['stock_deducted_at' => null]);
                        Log::info('Handover: admin refund keeps stock out (item left the shop)', ['job_id' => $lockedJob->id, 'order_id' => $source->id]);
                    }
                    $source->fresh()->cancel($reason, (int) $admin->id, 'admin');
                } elseif ($source instanceof FreshMarketOrder) {
                    // ออเดอร์ตลาดสดเป็น delivery_failed แล้ว (hook ของงาน) → cancelOrder ไม่คืนสต็อกเอง
                    app(FreshMarketService::class)->cancelOrder($source->fresh(), $reason, 'admin', $admin);
                }
            });
        });

        Log::info('Handover: admin refunded', [
            'job_id' => $job->id,
            'admin_id' => $admin->id,
            'already_closed' => $alreadyClosed,
            'item_with_buyer' => $itemWithBuyer,
        ]);

        $fresh = $job->fresh();
        $this->notifyResolved($fresh, 'refund', $alreadyClosed, ! $itemWithBuyer);

        $this->notifier->notifyAdmins(
            $alreadyClosed ? 'ปิดเรื่องร้องเรียนการส่งมอบแล้ว' : 'คืนเงินผู้ซื้อแล้ว (ตัดสินการส่งมอบ)',
            "งาน #{$fresh->job_number}: แอดมิน {$admin->name} ".($alreadyClosed
                ? 'ปิดเรื่อง (ออเดอร์ถูกยกเลิก/คืนเงินไปก่อนแล้ว)'
                : 'ตัดสินคืนเงินผู้ซื้อเต็มจำนวน ยกเลิกออเดอร์ ไรเดอร์ไม่ได้ค่าส่ง'.($itemWithBuyer ? '' : ' (ไรเดอร์ยังถือของ — ประสานนำของคืนร้าน)'))
                .($note !== '' ? " — {$note}" : ''),
            array_merge($this->pushBase($fresh), ['resolution' => 'refund']),
            $this->adminJobUrl($fresh),
        );

        return $fresh->handover()->first();
    }

    /**
     * แอดมินตัดสินได้เมื่อ: การส่งมอบยังไม่จบ (ทุกสถานะ) + ไรเดอร์รับของไปแล้ว (หรืองานถูกปิดเป็น failed ไปแล้ว)
     *
     * งาน failed ที่มีงานส่งใหม่ของออเดอร์เดียวกันแล้ว = เรื่องเก่า ตัดสินไม่ได้ (ไปตัดสินที่งานใหม่)
     */
    public function adminCanResolve(RiderJob $job, ?DeliveryHandover $handover): bool
    {
        if (! $handover || $handover->isFinal()) {
            return false;
        }

        if (! in_array($job->status, self::ADMIN_RESOLVABLE_JOB_STATUSES, true)) {
            return false;
        }

        if ($job->status === 'failed' && $job->source_type && $job->source_id) {
            $superseded = RiderJob::query()
                ->where('source_type', $job->source_type)
                ->where('source_id', $job->source_id)
                ->where('id', '>', $job->id)
                ->exists();

            if ($superseded) {
                return false;
            }
        }

        return true;
    }

    /**
     * ของออกจากมือไรเดอร์ไปถึงผู้ซื้อแล้วหรือยัง (ใช้ตัดสินว่าคืนเงินแล้วต้องคืนสต็อกเข้าร้านไหม)
     *
     * = วางของแล้ว (รูปรอบ 2 / งานรอปลดเงิน) หรือเจอกันแล้ว (ฝั่งใดฝั่งหนึ่งสแกน/กรอกรหัสยืนยัน)
     */
    private function itemLeftWithBuyer(RiderJob $job, DeliveryHandover $handover): bool
    {
        return $job->status === RiderJob::STATUS_AWAITING_RELEASE
            || $handover->waited_photo_at !== null
            || $handover->rider_confirmed_at !== null
            || $handover->buyer_confirmed_at !== null;
    }

    /**
     * ผู้ซื้อกด "ได้รับของแล้ว" ได้ไหม (ทางสำรอง: รอผู้รับที่จุดส่ง / วางของแล้วรอปลดเงิน)
     */
    public function canConfirmReceived(RiderJob $job, ?DeliveryHandover $handover): bool
    {
        if (! $handover || $handover->isFinal()) {
            return false;
        }

        if ($handover->status === DeliveryHandover::STATUS_FALLBACK_WAITING) {
            return in_array($job->status, self::JOB_HANDOVER_STATUSES, true);
        }

        return $handover->status === DeliveryHandover::STATUS_FALLBACK_PENDING_RELEASE
            && $job->status === RiderJob::STATUS_AWAITING_RELEASE;
    }

    // =====================================================
    // QR / รหัส
    // =====================================================

    /**
     * อายุ QR แต่ละช่วง (วินาที)
     */
    public function ttlSeconds(): int
    {
        return max(15, $this->config->intSetting('rider.handover_qr_ttl_seconds'));
    }

    public function currentWindow(): int
    {
        return intdiv(now()->getTimestamp(), $this->ttlSeconds());
    }

    /**
     * เวลาหมดอายุของ QR ช่วงนี้ (แอปเปลี่ยน QR ใหม่ตอนนี้ — ระบบยังรับช่วงก่อนหน้าอีก 1 ช่วงเผื่อสแกนช้า)
     */
    public function windowEndsAt(int $window): Carbon
    {
        return Carbon::createFromTimestamp(($window + 1) * $this->ttlSeconds())->setTimezone(config('app.timezone') ?: 'UTC');
    }

    /**
     * QR ของฝั่งหนึ่ง: TPH1.{id}.{b|r}.{window}.{ลายเซ็น 22 ตัว}
     */
    public function tokenFor(DeliveryHandover $handover, string $side, ?int $window = null): string
    {
        $window ??= $this->currentWindow();

        return implode('.', [self::TOKEN_PREFIX, (int) $handover->id, $side, $window, $this->sign($handover, $side, $window)]);
    }

    /**
     * รหัส 6 หลักของการส่งมอบ — ได้จากกุญแจลับสุ่มต่องาน (สุ่มเท่ากับกุญแจ) จึงแสดงให้ผู้ซื้อซ้ำได้
     * ฝั่งตรวจใช้ code_hash (bcrypt) เท่านั้น
     */
    public static function deriveCode(string $secret): string
    {
        $number = hexdec(substr(hash_hmac('sha256', 'handover-code', $secret), 0, 8)) % 1000000;

        return str_pad((string) $number, 6, '0', STR_PAD_LEFT);
    }

    public function codeFor(DeliveryHandover $handover): string
    {
        return self::deriveCode((string) $handover->secret);
    }

    /**
     * รหัส 6 หลักของไรเดอร์ (ให้ผู้ซื้อกรอกเมื่อสแกน QR ของไรเดอร์ไม่ได้) — ได้จากกุญแจลับต่องาน คนละค่ากับของผู้ซื้อ
     */
    public static function deriveRiderCode(string $secret): string
    {
        $number = hexdec(substr(hash_hmac('sha256', 'handover-code-rider', $secret), 0, 8)) % 1000000;

        return str_pad((string) $number, 6, '0', STR_PAD_LEFT);
    }

    public function riderCodeFor(DeliveryHandover $handover): string
    {
        return self::deriveRiderCode((string) $handover->secret);
    }

    /**
     * ตรวจ QR (ลายเซ็น constant-time → ฝั่ง → id → ช่วงเวลา)
     *
     * @throws HandoverException HANDOVER_TOKEN_INVALID | HANDOVER_TOKEN_EXPIRED
     */
    private function verifyToken(DeliveryHandover $handover, string $token, string $expectedSide): void
    {
        $token = trim($token);

        if (! preg_match('/^'.self::TOKEN_PREFIX.'\.(\d{1,12})\.([br])\.(\d{1,12})\.([A-Za-z0-9_-]{22})$/', $token, $m)) {
            throw HandoverException::tokenInvalid();
        }

        [$id, $side, $window, $signature] = [(int) $m[1], $m[2], (int) $m[3], $m[4]];

        // ลายเซ็นคิดจากกุญแจของการส่งมอบนี้ → QR ของงานอื่น/ฝั่งอื่น/แก้ตัวเลข ไม่มีทางตรง
        $expected = $this->sign($handover, $side, $window);
        if (! hash_equals($expected, $signature) || $id !== (int) $handover->id || $side !== $expectedSide) {
            throw HandoverException::tokenInvalid();
        }

        $current = $this->currentWindow();
        if ($window > $current) {
            throw HandoverException::tokenInvalid();
        }

        if ($window < $current - 1) {
            throw HandoverException::tokenExpired();
        }
    }

    private function sign(DeliveryHandover $handover, string $side, int $window): string
    {
        $raw = hash_hmac('sha256', self::TOKEN_PREFIX.'|'.(int) $handover->id.'|'.$side.'|'.$window, (string) $handover->secret, true);

        return rtrim(strtr(base64_encode(substr($raw, 0, 16)), '+/', '-_'), '=');
    }

    /**
     * ตรวจรหัส 6 หลัก — นับครั้งผิดใน transaction ของตัวเอง (commit ก่อน throw → นับจริงแม้คำขอล้ม)
     *
     * @param  string  $side  เจ้าของรหัส: SIDE_BUYER = รหัสผู้ซื้อ (ไรเดอร์กรอก · code_attempts)
     *                        SIDE_RIDER = รหัสไรเดอร์ (ผู้ซื้อกรอก · rider_code_attempts) — ตัวนับแยกกัน
     *
     * @throws HandoverException HANDOVER_CODE_INVALID | HANDOVER_CODE_LOCKED
     */
    private function verifyCode(DeliveryHandover $handover, string $code, string $side): void
    {
        $code = preg_replace('/\D/', '', $code) ?? '';
        [$attemptsColumn, $lockColumn] = $side === self::SIDE_RIDER
            ? ['rider_code_attempts', 'rider_code_locked_until']
            : ['code_attempts', 'code_locked_until'];

        $result = DB::transaction(function () use ($handover, $code, $side, $attemptsColumn, $lockColumn) {
            /** @var DeliveryHandover $row */
            $row = DeliveryHandover::whereKey($handover->id)->lockForUpdate()->firstOrFail();

            /** @var Carbon|null $lockedUntil */
            $lockedUntil = $row->{$lockColumn};
            if ($lockedUntil !== null) {
                if ($lockedUntil->isFuture()) {
                    return ['locked', $lockedUntil];
                }

                // พ้นเวลาล็อกแล้ว → เริ่มนับใหม่
                $row->forceFill([$lockColumn => null, $attemptsColumn => 0]);
            }

            if (strlen($code) === 6 && $this->codeMatches($row, $code, $side)) {
                if ((int) $row->{$attemptsColumn} > 0 || $row->isDirty()) {
                    $row->forceFill([$attemptsColumn => 0, $lockColumn => null])->save();
                }

                return ['ok', null];
            }

            $attempts = (int) $row->{$attemptsColumn} + 1;

            if ($attempts >= self::MAX_CODE_ATTEMPTS) {
                $until = now()->addMinutes(self::CODE_LOCK_MINUTES);
                $row->forceFill([$attemptsColumn => $attempts, $lockColumn => $until])->save();

                return ['locked', $until];
            }

            $row->forceFill([$attemptsColumn => $attempts])->save();

            return ['invalid', self::MAX_CODE_ATTEMPTS - $attempts];
        });

        match ($result[0]) {
            'ok' => null,
            'locked' => throw HandoverException::codeLocked(
                Carbon::parse($result[1]),
                $side === self::SIDE_RIDER ? 'หรือสแกน QR บนมือถือของไรเดอร์แทน' : 'หรือให้ผู้รับสแกน QR แทน',
            ),
            default => throw HandoverException::codeInvalid((int) $result[1]),
        };
    }

    /**
     * รหัสตรงไหม — ผู้ซื้อ: เทียบ hash (bcrypt) · ไรเดอร์: คิดจากกุญแจลับแล้วเทียบแบบ constant-time
     */
    private function codeMatches(DeliveryHandover $row, string $code, string $side): bool
    {
        if ($side === self::SIDE_RIDER) {
            return hash_equals(self::deriveRiderCode((string) $row->secret), $code);
        }

        return $row->code_hash && Hash::check($code, $row->code_hash);
    }

    // =====================================================
    // ภายใน: ปิดงาน
    // =====================================================

    /**
     * ปิดการส่งมอบ + ปิดงาน (ภายใน transaction ที่ล็อกงานและแถวส่งมอบแล้ว)
     *
     * ตั้งสถานะการส่งมอบก่อนเปลี่ยนสถานะงาน → OrderObserver ที่ทำงานตอนออเดอร์เป็น delivered
     * เห็นว่าส่งมอบสำเร็จแล้ว จึงแบ่งเงินร้าน + ปล่อยเงินทันที (ไม่พัก 7 วัน)
     */
    private function completeLocked(
        RiderJob $lockedJob,
        DeliveryHandover $locked,
        string $method,
        string $status = DeliveryHandover::STATUS_COMPLETED,
        ?float $lat = null,
        ?float $lng = null,
        bool $adminOverride = false,
    ): void {
        $locked->forceFill([
            'status' => $status,
            'method' => $method,
            'completed_at' => now(),
        ])->save();

        $lat ??= $locked->rider_confirm_latitude !== null ? (float) $locked->rider_confirm_latitude : null;
        $lng ??= $locked->rider_confirm_longitude !== null ? (float) $locked->rider_confirm_longitude : null;

        $this->jobs->completeHandoverLocked($lockedJob, $lat, $lng, $adminOverride);
    }

    /**
     * หลัง commit: จ่ายไรเดอร์ → ปิดออเดอร์ตลาดสด (โอนเงินร้าน) → แจ้งผู้ซื้อ/ไรเดอร์/ร้าน (ครั้งเดียวต่อการปิด)
     */
    private function afterCompleted(RiderJob $job, bool $notify = true): void
    {
        $this->jobs->settleEarnings($job);

        $source = $job->deliverableSource();

        // ตลาดสด: ผู้ซื้อยืนยันด้วยการสแกนแล้ว (หรือครบเวลา/แอดมินปล่อย) → ปิดออเดอร์ โอนเงินร้านทันที
        if ($source instanceof FreshMarketOrder) {
            try {
                $order = $source->fresh();
                if ($order && $order->order_status === FreshMarketOrder::STATUS_DELIVERED) {
                    app(FreshMarketService::class)->completeOrder($order, 'system');
                }
            } catch (\Throwable $e) {
                // ล้มได้ (เช่น COD ยังไม่นำส่ง) → fresh-market:auto-complete เก็บตกภายหลัง
                Log::warning('Handover: complete fresh market order failed', ['job_id' => $job->id, 'error' => $e->getMessage()]);
            }
        }

        if (! $notify) {
            return;
        }

        $fresh = $job->fresh() ?? $job;
        $buyerId = $this->buyerUserIdFor($fresh);
        $riderUserId = $this->riderUserIdFor($fresh);
        $sellerIds = $this->sellerUserIdsFor($fresh);
        $earned = round((float) $fresh->rider_earnings + $this->riderBonus($fresh), 2);

        $this->notifier->notifyUsers(
            [$buyerId],
            'handover_completed',
            'ได้รับของเรียบร้อย',
            "ส่งมอบสำเร็จ ขอบคุณที่ใช้บริการ งาน #{$fresh->job_number}",
            $this->pushData($fresh, 'buyer', ['screen' => 'order']),
        );

        if ($riderUserId) {
            $this->notifier->notifyUsers(
                [$riderUserId],
                'handover_completed',
                'ส่งมอบสำเร็จ',
                'รายได้ ฿'.number_format($earned, 2)." เข้ากระเป๋าแล้ว งาน #{$fresh->job_number}",
                $this->pushData($fresh, 'rider'),
            );
        }

        if ($sellerIds !== []) {
            $this->notifier->notifyUsers(
                $sellerIds,
                'handover_completed',
                'ลูกค้าได้รับของแล้ว',
                "ไรเดอร์ส่งมอบสำเร็จ รายได้จากออเดอร์นี้เข้ากระเป๋าร้านแล้ว งาน #{$fresh->job_number}",
                $this->pushData($fresh, 'seller'),
            );
        }
    }

    /**
     * แจ้งผู้ซื้อ + ไรเดอร์ + ร้าน ว่าแอดมินตัดสินแล้ว (ข้อความเดียวต่อคน ตรงเหตุการณ์)
     *
     * @param  bool  $alreadyClosed  ออเดอร์ถูกยกเลิก/คืนเงินไปก่อนแล้ว (คืนเงิน = ปิดเรื่องอย่างเดียว)
     * @param  bool  $riderHoldsItem  คืนเงินตอนไรเดอร์ยังถือของอยู่ → บอกไรเดอร์ให้นำของคืนร้าน
     */
    private function notifyResolved(RiderJob $job, string $resolution, bool $alreadyClosed = false, bool $riderHoldsItem = false): void
    {
        $buyerId = $this->buyerUserIdFor($job);
        $riderUserId = $this->riderUserIdFor($job);
        $sellerIds = $this->sellerUserIdsFor($job);
        $orderRef = $this->orderNumberFor($job);
        $orderText = $orderRef ? "ออเดอร์ #{$orderRef} " : '';

        if ($resolution === 'refund') {
            $buyerMessage = $alreadyClosed
                ? "ทีมงานปิดเรื่องร้องเรียนแล้ว {$orderText}ถูกยกเลิกและคืนเงินให้คุณไปก่อนหน้านี้ งาน #{$job->job_number}"
                : "ทีมงานตรวจสอบแล้ว ยกเลิก{$orderText}และคืนเงินเต็มจำนวน (รวมค่าส่ง) เข้ากระเป๋าเงินของคุณแล้ว งาน #{$job->job_number}";
            $riderMessage = $riderHoldsItem
                ? "ทีมงานตัดสินคืนเงินผู้ซื้อ ออเดอร์ถูกยกเลิก กรุณานำสินค้าคืนร้าน งานนี้ไม่มีรายได้ค่าส่ง งาน #{$job->job_number}"
                : "ทีมงานตัดสินคืนเงินผู้ซื้อ งานนี้ไม่มีรายได้ค่าส่ง งาน #{$job->job_number}";
            $sellerMessage = "ทีมงานตัดสินคืนเงินผู้ซื้อ {$orderText}ถูกยกเลิก ไม่มีรายได้จากออเดอร์นี้ งาน #{$job->job_number}";
        } else {
            $buyerMessage = "ทีมงานตรวจสอบแล้ว ยืนยันว่าส่งมอบสำเร็จ งาน #{$job->job_number}";
            $riderMessage = "ทีมงานยืนยันการส่งมอบแล้ว รายได้เข้ากระเป๋าของคุณ งาน #{$job->job_number}";
            $sellerMessage = "ทีมงานยืนยันว่าลูกค้าได้รับของแล้ว รายได้จาก{$orderText}เข้ากระเป๋าร้าน งาน #{$job->job_number}";
        }

        $this->notifier->notifyUsers([$buyerId], 'handover_resolved', 'ผลการตรวจสอบการส่งมอบ', $buyerMessage,
            $this->pushData($job, 'buyer', ['resolution' => $resolution, 'screen' => 'order']), null, 'high');

        if ($riderUserId) {
            $this->notifier->notifyUsers([$riderUserId], 'handover_resolved', 'ผลการตรวจสอบการส่งมอบ', $riderMessage,
                $this->pushData($job, 'rider', ['resolution' => $resolution]), null, 'high');
        }

        if ($sellerIds !== []) {
            $this->notifier->notifyUsers($sellerIds, 'handover_resolved', 'ผลการตรวจสอบการส่งมอบ', $sellerMessage,
                $this->pushData($job, 'seller', ['resolution' => $resolution]), null, 'high');
        }
    }

    // =====================================================
    // ภายใน: ตรวจเงื่อนไข
    // =====================================================

    /**
     * ล็อกแถวงานก่อน แล้วค่อยแถวส่งมอบ (ลำดับเดียวทุกเส้นทาง) — ต้องอยู่ใน transaction
     *
     * @return array{0: RiderJob, 1: DeliveryHandover}
     */
    private function lockPair(RiderJob $job): array
    {
        /** @var RiderJob|null $lockedJob */
        $lockedJob = RiderJob::whereKey($job->id)->lockForUpdate()->first();
        if (! $lockedJob) {
            throw RiderJobException::jobNotFound();
        }

        $locked = DeliveryHandover::where('rider_job_id', $lockedJob->id)->lockForUpdate()->first();
        if (! $locked) {
            $this->ensureFor($lockedJob);
            $locked = DeliveryHandover::where('rider_job_id', $lockedJob->id)->lockForUpdate()->firstOrFail();
        }

        return [$lockedJob, $locked];
    }

    private function requireHandover(RiderJob $job): DeliveryHandover
    {
        if (! $job->handover_required) {
            throw HandoverException::notReady('งานนี้ไม่ต้องสแกนส่งมอบ');
        }

        $handover = $this->ensureFor($job);
        if (! $handover) {
            throw HandoverException::notReady();
        }

        return $handover;
    }

    /**
     * สแกน/กรอกรหัสได้: ไรเดอร์ถือของอยู่ + การส่งมอบยังไม่จบ/ไม่ได้ร้องเรียน/ยังไม่วางของ
     */
    private function assertScannable(RiderJob $job, DeliveryHandover $handover): void
    {
        if ($handover->isFinal()) {
            throw HandoverException::final();
        }

        if (! in_array($handover->status, self::SCANNABLE_STATUSES, true)) {
            throw HandoverException::notReady(match ($handover->status) {
                DeliveryHandover::STATUS_DISPUTED => 'การส่งมอบนี้อยู่ระหว่างทีมงานตรวจสอบ',
                DeliveryHandover::STATUS_FALLBACK_PENDING_RELEASE => 'ไรเดอร์วางของไว้แล้ว ระบบจะปลดเงินอัตโนมัติ หากมีปัญหากรุณาแจ้งร้องเรียน',
                default => 'ยังส่งมอบไม่ได้ในตอนนี้',
            });
        }

        if (! in_array($job->status, self::JOB_HANDOVER_STATUSES, true)) {
            throw HandoverException::notReady(in_array($job->status, ['pending', 'accepted', 'picking_up'], true)
                ? 'ไรเดอร์ยังไม่ได้รับของจากร้าน'
                : 'งานนี้ไม่อยู่ในขั้นส่งมอบแล้ว');
        }
    }

    private function assertCanArrivalPhoto(RiderJob $job, DeliveryHandover $handover): void
    {
        if ($handover->isFinal()) {
            throw HandoverException::final();
        }

        if (! in_array($job->status, self::JOB_HANDOVER_STATUSES, true)) {
            throw HandoverException::notReady('งานนี้ไม่อยู่ในขั้นส่งมอบแล้ว');
        }

        // เก็บเงินปลายทาง: ต้องพบผู้รับเพื่อเก็บเงิน วางของทิ้งไว้ไม่ได้
        if ((float) $job->cod_amount > 0) {
            throw HandoverException::notReady('งานเก็บเงินปลายทางต้องพบผู้รับ วางของไว้ไม่ได้ กรุณาติดต่อผู้รับหรือแจ้งส่งไม่สำเร็จ');
        }

        if (! in_array($handover->status, [DeliveryHandover::STATUS_WAITING, DeliveryHandover::STATUS_RIDER_CONFIRMED], true)) {
            throw HandoverException::notReady('ถ่ายรูปถึงจุดส่งไม่ได้ในขั้นนี้');
        }
    }

    private function assertCanWaitedPhoto(RiderJob $job, DeliveryHandover $handover): void
    {
        if ($handover->isFinal()) {
            throw HandoverException::final();
        }

        if ($handover->status !== DeliveryHandover::STATUS_FALLBACK_WAITING || ! in_array($job->status, self::JOB_HANDOVER_STATUSES, true)) {
            throw HandoverException::notReady('กรุณาถ่ายรูปถึงจุดส่งรอบแรกก่อน');
        }

        if ($handover->wait_until !== null && $handover->wait_until->isFuture()) {
            throw HandoverException::waitNotOver($handover->wait_until);
        }
    }

    private function assertRiderJob(Rider $rider, RiderJob $job): void
    {
        if ($job->rider_id === null || (int) $job->rider_id !== (int) $rider->id) {
            throw RiderJobException::notYourJob();
        }
    }

    /**
     * @return array{0: float, 1: float}
     */
    private function requireLocation(mixed $lat, mixed $lng): array
    {
        if (! DeliveryFeeCalculator::isValidCoordinate($lat, $lng)) {
            throw HandoverException::locationRequired();
        }

        return [round((float) $lat, 7), round((float) $lng, 7)];
    }

    /**
     * ไรเดอร์ต้องอยู่ในรัศมีจุดส่ง — คืนระยะห่าง (เมตร)
     *
     * ตรวจ 2 ชั้น:
     *   1. พิกัดที่แอปส่งมากับคำขอ ต้องอยู่ในรัศมี rider.handover_geofence_m
     *   2. ตำแหน่งล่าสุดที่เซิร์ฟเวอร์รู้ (ถ้าสดไม่เกิน 2 นาที) ต้องไม่ไกลเกินรัศมี + 200 ม.
     *      — กันแอปดัดแปลงส่งพิกัดปลอมมากับคำขอ · ไม่มีตำแหน่งสดบนเซิร์ฟเวอร์ = เชื่อพิกัดในคำขออย่างเดียว
     */
    private function assertWithinGeofence(RiderJob $job, float $lat, float $lng, ?Rider $rider = null): ?int
    {
        $distance = $this->distanceToDropoff($job, $lat, $lng);

        if ($distance === null) {
            // งานไม่มีพิกัดจุดส่ง (ข้อมูลผิดปกติ — createJobForSource บังคับพิกัดเสมอ) → ตรวจระยะไม่ได้ ไม่ขังเงินไว้
            Log::warning('Handover: job has no dropoff coordinate, geofence skipped', ['job_id' => $job->id]);

            return null;
        }

        $geofence = max(10, $this->config->intSetting('rider.handover_geofence_m'));

        if ($distance > $geofence) {
            throw HandoverException::tooFar($distance, $geofence);
        }

        $serverDistance = $this->serverDistanceToDropoff($job, $rider);
        if ($serverDistance !== null && $serverDistance > $geofence + self::SERVER_LOCATION_SLACK_M) {
            Log::warning('Handover: request location inside geofence but last server location is far', [
                'job_id' => $job->id,
                'rider_id' => $rider?->id,
                'request_distance_m' => $distance,
                'server_distance_m' => $serverDistance,
            ]);

            throw HandoverException::tooFar($serverDistance, $geofence);
        }

        return $distance;
    }

    /**
     * ระยะจากพิกัดหนึ่งถึงจุดส่งของงาน (เมตร) — งานไม่มีพิกัดจุดส่ง = null
     */
    private function distanceToDropoff(RiderJob $job, float $lat, float $lng): ?int
    {
        if (! DeliveryFeeCalculator::isValidCoordinate($job->delivery_latitude, $job->delivery_longitude)) {
            return null;
        }

        return self::distanceMeters($lat, $lng, (float) $job->delivery_latitude, (float) $job->delivery_longitude);
    }

    /**
     * ระยะจากตำแหน่งล่าสุดบนเซิร์ฟเวอร์ของไรเดอร์ถึงจุดส่ง — เฉพาะตำแหน่งที่สดไม่เกิน SERVER_LOCATION_FRESH_SECONDS
     */
    private function serverDistanceToDropoff(RiderJob $job, ?Rider $rider): ?int
    {
        if (! $rider
            || $rider->last_location_update === null
            || ! DeliveryFeeCalculator::isValidCoordinate($rider->last_latitude, $rider->last_longitude)
            || Carbon::parse($rider->last_location_update)->lt(now()->subSeconds(self::SERVER_LOCATION_FRESH_SECONDS))) {
            return null;
        }

        return $this->distanceToDropoff($job, (float) $rider->last_latitude, (float) $rider->last_longitude);
    }

    public static function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): int
    {
        return (int) round(DeliveryFeeCalculator::haversineKm($lat1, $lng1, $lat2, $lng2) * 1000);
    }

    /**
     * ผู้ซื้อร้องเรียนได้เมื่อ: ไรเดอร์วางของ/รอผู้รับอยู่ หรือไรเดอร์ยืนยันฝั่งตัวเองแล้วแต่ผู้ซื้อยังไม่ยืนยัน
     */
    public function canDispute(RiderJob $job, ?DeliveryHandover $handover): bool
    {
        if (! $handover || $handover->isFinal() || $handover->status === DeliveryHandover::STATUS_DISPUTED) {
            return false;
        }

        if (! in_array($job->status, array_merge(self::JOB_HANDOVER_STATUSES, [RiderJob::STATUS_AWAITING_RELEASE]), true)) {
            return false;
        }

        if (in_array($handover->status, [DeliveryHandover::STATUS_FALLBACK_WAITING, DeliveryHandover::STATUS_FALLBACK_PENDING_RELEASE], true)) {
            return true;
        }

        return $handover->rider_confirmed_at !== null && $handover->buyer_confirmed_at === null;
    }

    // =====================================================
    // ภายใน: ออเดอร์ของผู้ซื้อ
    // =====================================================

    /**
     * ออเดอร์ของผู้ซื้อคนนี้ + งานไรเดอร์ล่าสุดของออเดอร์
     *
     * @return array{0: Order|FreshMarketOrder, 1: RiderJob}
     */
    private function resolveBuyerJob(User $buyer, string $source, int $orderId): array
    {
        $order = match ($source) {
            self::SOURCE_SHOP => Order::whereKey($orderId)->where('user_id', $buyer->id)->first(),
            self::SOURCE_FRESH_MARKET => FreshMarketOrder::whereKey($orderId)->where('buyer_id', $buyer->id)->first(),
            default => null,
        };

        if (! $order) {
            throw HandoverException::orderNotFound();
        }

        $job = RiderJob::forSource($order)->nonTerminal()->latest('id')->first()
            ?? RiderJob::forSource($order)->latest('id')->first();

        if (! $job) {
            throw HandoverException::notReady('ออเดอร์นี้ยังไม่มีไรเดอร์');
        }

        return [$order, $job];
    }

    // =====================================================
    // ภายใน: ข้อมูลตอบกลับ
    // =====================================================

    /**
     * { handover: HandoverBuyer, rider: PersonCard|null, job: {id, status, distance_to_dropoff_m}, settlement: Settlement|null }
     *
     * @return array<string, mixed>
     */
    private function buyerPayload(User $buyer, Model $order, RiderJob $job): array
    {
        $handover = $job->handover_required ? $this->ensureFor($job) : null;
        $rider = $job->rider_id ? Rider::with('user')->find($job->rider_id) : null;

        return [
            'handover' => $this->handoverBuyer($job, $handover),
            'rider' => $rider ? $this->riderCard($rider, $buyer) : null,
            'job' => [
                'id' => (int) $job->id,
                'status' => (string) $job->status,
                'distance_to_dropoff_m' => $this->riderDistanceToDropoff($job, $rider),
            ],
            'settlement' => $handover && in_array($handover->status, [DeliveryHandover::STATUS_COMPLETED, DeliveryHandover::STATUS_RELEASED], true)
                ? $this->settlementFor($order, $job)
                : null,
            // เวลาเซิร์ฟเวอร์ ให้แอปชดเชยนาฬิกาเครื่องที่เพี้ยน (นับถอยหลัง QR / เวลารอ / เวลาปลดเงิน)
            'server_now' => now()->toIso8601String(),
        ];
    }

    /**
     * HandoverBuyer (QR ฝั่งผู้ซื้อ + รหัส 6 หลักให้อ่านให้ไรเดอร์ฟัง — แสดงเฉพาะตอนไรเดอร์ถือของ)
     *
     * @return array<string, mixed>
     */
    private function handoverBuyer(RiderJob $job, ?DeliveryHandover $handover): array
    {
        if (! $handover) {
            return [
                'required' => false, 'status' => 'not_required', 'method' => null,
                'rider_confirmed' => false, 'buyer_confirmed' => false,
                'qr_token' => null, 'qr_expires_at' => null, 'code' => null,
                'wait_until' => null, 'auto_release_at' => null, 'arrival_photo_at' => null, 'waited_photo_at' => null,
                'can_dispute' => false, 'can_confirm_received' => false,
                'disputed_at' => null, 'dispute_reason' => null, 'completed_at' => null,
            ];
        }

        $showQr = ! $handover->isFinal()
            && in_array($handover->status, self::SCANNABLE_STATUSES, true)
            && in_array($job->status, self::JOB_HANDOVER_STATUSES, true)
            && $handover->rider_confirmed_at === null;
        $window = $this->currentWindow();

        return [
            'required' => true,
            'status' => (string) $handover->status,
            'method' => $handover->method,
            'rider_confirmed' => $handover->rider_confirmed_at !== null,
            'buyer_confirmed' => $handover->buyer_confirmed_at !== null,
            'qr_token' => $showQr ? $this->tokenFor($handover, self::SIDE_BUYER, $window) : null,
            'qr_expires_at' => $showQr ? $this->windowEndsAt($window)->toIso8601String() : null,
            'code' => $showQr ? $this->codeFor($handover) : null,
            'wait_until' => $handover->wait_until?->toIso8601String(),
            'auto_release_at' => $handover->auto_release_at?->toIso8601String(),
            'arrival_photo_at' => $handover->arrival_photo_at?->toIso8601String(),
            'waited_photo_at' => $handover->waited_photo_at?->toIso8601String(),
            'can_dispute' => $this->canDispute($job, $handover),
            'can_confirm_received' => $this->canConfirmReceived($job, $handover),
            'disputed_at' => $handover->disputed_at?->toIso8601String(),
            'dispute_reason' => $handover->dispute_reason,
            'completed_at' => $handover->completed_at?->toIso8601String(),
        ];
    }

    /**
     * { handover: HandoverRider, buyer: PersonCard|null }
     *
     * @return array<string, mixed>
     */
    private function riderPayload(Rider $rider, RiderJob $job): array
    {
        $handover = $job->handover_required ? $this->ensureFor($job) : null;
        $buyerId = $this->buyerUserIdFor($job);
        $buyer = $buyerId ? User::find($buyerId) : null;
        $viewer = $rider->relationLoaded('user') ? $rider->user : User::find($rider->user_id);

        return [
            'handover' => $this->handoverRider($job, $handover),
            'buyer' => $buyer ? $this->buyerCard($buyer, $viewer) : null,
            // เวลาเซิร์ฟเวอร์ ให้แอปชดเชยนาฬิกาเครื่องที่เพี้ยน (นับถอยหลัง QR / เวลารอรูปรอบ 2)
            'server_now' => now()->toIso8601String(),
        ];
    }

    /**
     * HandoverRider (QR ฝั่งไรเดอร์ให้ผู้ซื้อสแกน)
     *
     * @return array<string, mixed>
     */
    private function handoverRider(RiderJob $job, ?DeliveryHandover $handover): array
    {
        $geofence = max(10, $this->config->intSetting('rider.handover_geofence_m'));
        $waitSeconds = max(0, $this->config->intSetting('rider.handover_wait_seconds'));

        if (! $handover) {
            return [
                'required' => false, 'status' => 'not_required', 'method' => null,
                'rider_confirmed' => false, 'buyer_confirmed' => false,
                'qr_token' => null, 'qr_expires_at' => null, 'code' => null, 'geofence_m' => $geofence, 'wait_seconds' => $waitSeconds,
                'wait_until' => null, 'auto_release_at' => null, 'arrival_photo_at' => null, 'waited_photo_at' => null,
                'can_arrival_photo' => false, 'can_waited_photo' => false, 'completed_at' => null,
            ];
        }

        $jobInHand = in_array($job->status, self::JOB_HANDOVER_STATUSES, true);
        $showQr = $jobInHand
            && ! $handover->isFinal()
            && in_array($handover->status, self::SCANNABLE_STATUSES, true)
            && $handover->buyer_confirmed_at === null;
        $window = $this->currentWindow();

        return [
            'required' => true,
            'status' => (string) $handover->status,
            'method' => $handover->method,
            'rider_confirmed' => $handover->rider_confirmed_at !== null,
            'buyer_confirmed' => $handover->buyer_confirmed_at !== null,
            'qr_token' => $showQr ? $this->tokenFor($handover, self::SIDE_RIDER, $window) : null,
            'qr_expires_at' => $showQr ? $this->windowEndsAt($window)->toIso8601String() : null,
            // รหัส 6 หลักของไรเดอร์ ให้ผู้ซื้อกรอกเมื่อกล้องใช้ไม่ได้ (แสดงเฉพาะตอน QR ใช้ได้)
            'code' => $showQr ? $this->riderCodeFor($handover) : null,
            'geofence_m' => $geofence,
            'wait_seconds' => $waitSeconds,
            'wait_until' => $handover->wait_until?->toIso8601String(),
            'auto_release_at' => $handover->auto_release_at?->toIso8601String(),
            'arrival_photo_at' => $handover->arrival_photo_at?->toIso8601String(),
            'waited_photo_at' => $handover->waited_photo_at?->toIso8601String(),
            'can_arrival_photo' => $jobInHand
                && (float) $job->cod_amount <= 0
                && in_array($handover->status, [DeliveryHandover::STATUS_WAITING, DeliveryHandover::STATUS_RIDER_CONFIRMED], true),
            'can_waited_photo' => $jobInHand
                && (float) $job->cod_amount <= 0
                && $handover->status === DeliveryHandover::STATUS_FALLBACK_WAITING
                && ($handover->wait_until === null || ! $handover->wait_until->isFuture()),
            'completed_at' => $handover->completed_at?->toIso8601String(),
        ];
    }

    /**
     * PersonCard ของไรเดอร์ (ผู้ซื้อดู)
     *
     * @return array<string, mixed>
     */
    private function riderCard(Rider $rider, User $viewer): array
    {
        $heartsFromMe = (int) RiderHeart::where('rider_id', $rider->id)->where('user_id', $viewer->id)->count();
        $vehicleLabel = trim(implode(' ', array_filter([$rider->vehicle_brand, $rider->vehicle_color ? 'สี'.$rider->vehicle_color : null])));

        return [
            'id' => (int) $rider->id,
            'display_name' => self::shortName((string) $rider->full_name),
            'photo_url' => $rider->user ? app(ProfilePhotoService::class)->urlFor($rider->user, $viewer) : null,
            'vehicle_type' => $rider->vehicle_type,
            'vehicle_label' => $vehicleLabel !== '' ? $vehicleLabel : $rider->vehicle_type_text,
            'plate_masked' => self::maskPlate($rider->vehicle_plate),
            'hearts_total' => (int) ($rider->hearts_count ?? 0),
            'hearts_from_me' => $heartsFromMe,
            'can_lock' => $heartsFromMe >= max(1, $this->config->intSetting('rider.lock_min_hearts')),
        ];
    }

    /**
     * PersonCard ของผู้ซื้อ (ไรเดอร์ดู — ชื่อย่อ + รูปลายน้ำเท่านั้น)
     *
     * @return array<string, mixed>
     */
    private function buyerCard(User $buyer, ?User $viewer): array
    {
        return [
            'id' => (int) $buyer->id,
            'display_name' => self::shortName((string) $buyer->name),
            'photo_url' => app(ProfilePhotoService::class)->urlFor($buyer, $viewer),
        ];
    }

    /**
     * ระยะไรเดอร์ถึงจุดส่ง (เมตร) — เฉพาะตอนไรเดอร์ถือของและตำแหน่งยังสด
     */
    private function riderDistanceToDropoff(RiderJob $job, ?Rider $rider): ?int
    {
        if (! $rider || ! in_array($job->status, self::JOB_HANDOVER_STATUSES, true) || ! $rider->hasFreshLocation()) {
            return null;
        }

        if (! DeliveryFeeCalculator::isValidCoordinate($job->delivery_latitude, $job->delivery_longitude)) {
            return null;
        }

        return self::distanceMeters((float) $rider->last_latitude, (float) $rider->last_longitude, (float) $job->delivery_latitude, (float) $job->delivery_longitude);
    }

    /**
     * สรุปการแบ่งเงินจริงของออเดอร์ (อ่านจากรายการเงินที่เกิดขึ้นแล้วเท่านั้น)
     *
     * @return array{total_paid: float, seller_amount: float, rider_amount: float, referrer_amount: float, platform_amount: float, lines: array<int, array{key: string, label: string, amount: float, note: ?string}>}
     */
    public function settlementFor(Model $order, RiderJob $job): array
    {
        $riderAmount = round((float) WalletTransaction::where('reference_type', RiderEarningService::REF_EARNING)
            ->where('reference_id', $job->id)
            ->where('type', 'deposit')
            ->sum('amount'), 2);
        $bonus = $this->riderBonus($job);

        if ($order instanceof FreshMarketOrder) {
            $sellerUserId = (int) ($order->seller?->user_id ?? 0);
            $totalPaid = $order->payment_method === 'cod' ? $order->grand_total : $order->walletPaidAmount();
            $itemsPaid = round((float) $order->total_amount, 2);
            $deliveryPaid = round((float) $order->delivery_fee, 2);
            $sellerAmount = round((float) WalletTransaction::where('reference_type', FreshMarketService::REF_PAYOUT)
                ->where('reference_id', $order->id)
                ->where('user_id', $sellerUserId)
                ->sum('amount')
                + (float) PlatformTransaction::where('source_type', FreshMarketOrder::class)
                    ->where('source_id', $order->id)
                    ->where('sub_type', FreshMarketService::PLATFORM_GP_DEBT)
                    ->where('type', 'income')
                    ->sum('amount'), 2);
            $referrer = round((float) PlatformTransaction::where('source_type', FreshMarketOrder::class)
                ->where('source_id', $order->id)
                ->where('sub_type', FreshMarketService::PLATFORM_REFERRAL)
                ->where('type', 'expense')
                ->sum('amount'), 2);
            $cashback = round((float) WalletTransaction::where('reference_type', FreshMarketService::REF_CASHBACK)
                ->where('reference_id', $order->id)
                ->sum('amount'), 2);
        } else {
            /** @var Order $order */
            $totalPaid = round((float) $order->total_amount, 2);
            $itemsPaid = round(max(0.0, (float) $order->subtotal - (float) ($order->product_discount ?? 0)), 2);
            $deliveryPaid = round(max(0.0, (float) $order->shipping_fee - (float) ($order->shipping_discount ?? 0)), 2);
            $sellerAmount = round((float) EarningsLedger::where('source_type', 'Order')
                ->where('source_id', $order->id)
                ->where('earning_type', EarningsLedger::TYPE_SELLER_SALE)
                ->where('status', '!=', EarningsLedger::STATUS_CANCELLED)
                ->get(['net_amount', 'breakdown'])
                ->sum(fn (EarningsLedger $l) => (float) (data_get($l->breakdown, 'payout.ledger_net') ?? $l->net_amount))
                + (float) PlatformTransaction::where('source_type', 'Order')
                    ->where('source_id', $order->id)
                    ->where('sub_type', 'admin_shop_sale')
                    ->where('type', PlatformTransaction::TYPE_INCOME)
                    ->sum('amount'), 2);
            $referrer = round((float) PlatformTransaction::where('source_type', 'Order')
                ->where('source_id', $order->id)
                ->where('sub_type', 'mlm_commission_pool')
                ->where('type', PlatformTransaction::TYPE_INCOME)
                ->sum('amount'), 2);
            $cashback = $order->cashback_processed ? round((float) $order->cashback_amount, 2) : 0.0;
        }

        $totalPaid = round((float) $totalPaid, 2);
        $platform = round($totalPaid - $sellerAmount - $riderAmount - $referrer, 2);

        $lines = [
            ['key' => 'items', 'label' => 'ค่าสินค้า', 'amount' => $itemsPaid, 'note' => null],
            ['key' => 'delivery', 'label' => 'ค่าส่งที่คุณจ่าย', 'amount' => $deliveryPaid, 'note' => $deliveryPaid <= 0 ? 'ร้านออกค่าส่งให้' : null],
            ['key' => 'seller', 'label' => 'ร้านค้าได้รับ', 'amount' => $sellerAmount, 'note' => 'หลังหักค่าธรรมเนียม'.($bonus > 0 ? ' และโบนัสไรเดอร์' : '')],
            ['key' => 'rider', 'label' => 'ไรเดอร์ได้รับ', 'amount' => $riderAmount, 'note' => $bonus > 0 ? 'รวมโบนัสจากร้าน ฿'.number_format($bonus, 2) : null],
        ];
        if ($referrer > 0) {
            $lines[] = ['key' => 'referrer', 'label' => 'ค่าแนะนำเพื่อน', 'amount' => $referrer, 'note' => null];
        }
        $lines[] = ['key' => 'platform', 'label' => 'ค่าบริการแพลตฟอร์ม', 'amount' => $platform, 'note' => 'รวมค่าธรรมเนียมและภาษี'];
        if ($cashback > 0) {
            $lines[] = ['key' => 'cashback', 'label' => 'เงินคืนให้คุณ', 'amount' => $cashback, 'note' => 'คืนจากส่วนของแพลตฟอร์ม'];
        }

        return [
            'total_paid' => $totalPaid,
            'seller_amount' => $sellerAmount,
            'rider_amount' => $riderAmount,
            'referrer_amount' => $referrer,
            'platform_amount' => $platform,
            'lines' => $lines,
        ];
    }

    /**
     * โบนัสที่ร้านเติมให้ไรเดอร์ของงานนี้ (ค่าบนงาน → ไม่มีให้ดูค่าที่ล็อกไว้บนออเดอร์)
     */
    public function riderBonus(RiderJob $job): float
    {
        return app(RiderEarningService::class)->bonusFor($job);
    }

    /**
     * payload push ของการส่งมอบต่อผู้รับ: role (buyer|rider|seller) + screen + source (shop|fresh-market) + order_id + job_id
     *
     * screen เริ่มต้นตามบทบาท: ผู้ซื้อ = order-handover · ไรเดอร์ = rider-job-detail · ร้าน = merchant-order (แทนได้ด้วย $extra)
     *
     * @param  string  $role  buyer|rider|seller
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function pushData(RiderJob $job, string $role, array $extra = []): array
    {
        return array_merge($this->pushBase($job), [
            'role' => $role,
            'screen' => match ($role) {
                'buyer' => 'order-handover',
                'rider' => 'rider-job-detail',
                'seller' => 'merchant-order',
                default => null,
            },
        ], $extra);
    }

    /**
     * ข้อมูลอ้างอิงงาน/ออเดอร์ของ push (ไม่มี role — ใช้กับแจ้งเตือนแอดมิน)
     *
     * @return array<string, mixed>
     */
    private function pushBase(RiderJob $job): array
    {
        return [
            'source' => RiderNotificationService::sourceKey($job),
            'order_id' => $job->source_id ? (int) $job->source_id : null,
            'job_id' => (int) $job->id,
        ];
    }

    /**
     * user_id ของไรเดอร์ของงาน (รวมไรเดอร์ที่ถูกลบแบบ soft delete)
     */
    private function riderUserIdFor(RiderJob $job): ?int
    {
        $id = $job->rider_id ? Rider::withTrashed()->whereKey($job->rider_id)->value('user_id') : null;

        return $id ? (int) $id : null;
    }

    /**
     * user_id ของร้าน/ผู้ขายของออเดอร์ (ผู้เกี่ยวข้องที่ไม่ใช่ผู้ซื้อและไม่ใช่ไรเดอร์)
     *
     * @return array<int, int>
     */
    private function sellerUserIdsFor(RiderJob $job): array
    {
        return array_values(array_diff($job->partyUserIds(), array_filter([$this->buyerUserIdFor($job), $this->riderUserIdFor($job)])));
    }

    /**
     * เลขออเดอร์ต้นทาง (ใช้ในข้อความแจ้งเตือน)
     */
    private function orderNumberFor(RiderJob $job): ?string
    {
        $source = $job->deliverableSource();

        return $source instanceof Model ? ($source->getAttribute('order_number') ?: null) : null;
    }

    /**
     * ออเดอร์ต้นทางจบไปแล้ว (ยกเลิก/คืนเงิน/ปิดออเดอร์) หรือไม่ — ใช้กันปล่อยเงิน/คืนเงินซ้ำของงานที่ปิดเป็น failed
     */
    private function sourceClosed(RiderJob $job): bool
    {
        $source = $job->deliverableSource();

        if ($source instanceof Order) {
            $status = Order::whereKey($source->id)->value('status');

            return in_array($status, Order::TERMINAL_STATUSES, true);
        }

        if ($source instanceof FreshMarketOrder) {
            $status = FreshMarketOrder::whereKey($source->id)->value('order_status');

            return in_array($status, FreshMarketOrder::TERMINAL_STATUSES, true);
        }

        return false;
    }

    /**
     * ผู้ที่ปิดแจ้งเตือนจากขั้นย่อยระหว่างแอดมินตัดสินคืนเงิน (ผู้ซื้อ ร้าน ไรเดอร์ แอดมิน) — แล้วค่อยแจ้งข้อความเดียว
     *
     * @return array<int, int>
     */
    private function resolutionAudience(RiderJob $job): array
    {
        $ids = array_merge($job->partyUserIds(), array_filter([$this->buyerUserIdFor($job), $this->riderUserIdFor($job)]));

        try {
            $admins = User::query()
                ->where(fn ($q) => $q->whereIn('role', ['admin', 'super_admin'])->orWhere('is_super_admin', true))
                ->limit(200)
                ->pluck('id')
                ->all();
            $ids = array_merge($ids, $admins);
        } catch (\Throwable $e) {
            Log::warning('Handover: cannot load admins for mute', ['error' => $e->getMessage()]);
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }

    /**
     * ลิงก์ติดตามของผู้ซื้อใช้ได้ถึงเวลาปลดเงินอัตโนมัติ + ช่วงผ่อนผัน
     */
    private function trackingUntilForRelease(): Carbon
    {
        $hours = max(1, $this->config->intSetting('rider.handover_auto_release_hours'));
        $grace = max(1, $this->config->intSetting('rider.tracking_grace_minutes'));

        return now()->addHours($hours)->addMinutes($grace);
    }

    private function adminJobUrl(RiderJob $job): ?string
    {
        try {
            return route('admin.rider-jobs.show', $job);
        } catch (\Throwable) {
            return null;
        }
    }

    // =====================================================
    // รูปทางสำรอง (private disk)
    // =====================================================

    private function storePhoto(UploadedFile $photo, RiderJob $job, string $kind): string
    {
        $mime = (string) $photo->getMimeType();
        if (! $photo->isValid() || ! str_starts_with($mime, 'image/')) {
            throw new RiderJobException(RiderJobException::PHOTO_REQUIRED, 'ไฟล์รูปไม่ถูกต้อง กรุณาถ่ายรูปใหม่', 422);
        }

        $path = $photo->store("rider_jobs/{$job->id}/handover_{$kind}", self::PHOTO_DISK);

        if (! $path) {
            Log::error('Handover: cannot store photo', ['job_id' => $job->id, 'kind' => $kind]);
            throw new RiderJobException(RiderJobException::PHOTO_REQUIRED, 'บันทึกรูปไม่สำเร็จ กรุณาลองใหม่', 422);
        }

        return $path;
    }

    private function deletePhoto(?string $path): void
    {
        if ($path) {
            try {
                Storage::disk(self::PHOTO_DISK)->delete($path);
            } catch (\Throwable) {
                // ไฟล์กำพร้าไม่กระทบข้อมูล
            }
        }
    }

    // =====================================================
    // ตัวช่วยแสดงผล
    // =====================================================

    /**
     * ชื่อแรก + อักษรแรกของนามสกุล เช่น "สมชาย ใจดี" → "สมชาย ใ."
     */
    public static function shortName(string $name): string
    {
        $parts = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($parts === []) {
            return 'ผู้ใช้';
        }

        return isset($parts[1]) ? $parts[0].' '.mb_substr($parts[1], 0, 1).'.' : $parts[0];
    }

    /**
     * ทะเบียนแบบปิดบางส่วน เช่น "1กข 1234" → "1กข **34"
     */
    public static function maskPlate(?string $plate): ?string
    {
        $plate = trim((string) $plate);
        if ($plate === '') {
            return null;
        }

        $length = mb_strlen($plate);
        if ($length <= 2) {
            return str_repeat('*', $length);
        }

        $head = mb_substr($plate, 0, $length - 2);
        $tail = mb_substr($plate, -2);

        // ปิดเฉพาะตัวเลขชุดท้าย (หมวดอักษรหน้าทะเบียนยังเห็น ใช้ยืนยันว่าเป็นรถคันที่มา)
        $masked = preg_replace_callback('/\d+$/u', fn ($m) => str_repeat('*', strlen($m[0])), $head);

        return ($masked ?? $head).$tail;
    }

    public static function disputeReasonText(?string $reason): string
    {
        return match ($reason) {
            'not_received' => 'ไม่ได้รับของ',
            'wrong_item' => 'ได้ของผิด',
            'damaged' => 'ของเสียหาย',
            'other' => 'อื่นๆ',
            default => (string) $reason,
        };
    }
}
