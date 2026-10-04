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
 * ปิดงานได้ 3 ทาง:
 *   1. สแกนใส่กัน — ไรเดอร์สแกน QR ของผู้ซื้อ (หรือกรอกรหัส 6 หลักของผู้ซื้อ) + ผู้ซื้อสแกน QR ของไรเดอร์
 *      ครบสองฝ่าย → งาน delivering → delivered → completed + การส่งมอบ completed ใน transaction เดียว
 *   2. ผู้ซื้อไม่ออกมารับ — รูปรอบ 1 ที่จุดส่ง (เริ่มนับรอ) → ครบเวลารอ → รูปรอบ 2 → งาน awaiting_release
 *      (ไรเดอร์ว่างรับงานใหม่) → ปลดเงินอัตโนมัติเมื่อครบ rider.handover_auto_release_hours ถ้าผู้ซื้อไม่ร้องเรียน
 *   3. ร้องเรียน → แอดมินตัดสิน: ปล่อยเงิน (เหมือนส่งสำเร็จ) หรือคืนเงินผู้ซื้อเต็มจำนวน (ไรเดอร์ไม่ได้ค่าส่ง)
 *
 * ความปลอดภัย:
 *   - QR = ข้อมูลสั้นที่เซ็นด้วยกุญแจลับต่องาน (HMAC-SHA256) ผูก id การส่งมอบ + ฝั่ง (b=ผู้ซื้อ, r=ไรเดอร์) + ช่วงเวลา
 *     เปลี่ยนทุก rider.handover_qr_ttl_seconds รับเฉพาะช่วงปัจจุบันและช่วงก่อนหน้า · เทียบลายเซ็นแบบ constant-time
 *   - QR ของผู้ซื้อใช้ได้กับไรเดอร์ของงานเท่านั้น / QR ของไรเดอร์ใช้ได้กับผู้ซื้อของออเดอร์เท่านั้น (ฝั่งผิด = ใช้ไม่ได้)
 *   - รหัส 6 หลักเก็บเป็น hash · ผิดครบ 5 ครั้ง ล็อก 10 นาที (นับครั้งผิดใน transaction แยก — ไม่ถูก rollback)
 *   - ไรเดอร์ต้องอยู่ในรัศมี rider.handover_geofence_m ของจุดส่งทุกครั้งที่ยืนยัน/ถ่ายรูป
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
     * POST /orders/{source}/{id}/handover/scan {token} — ผู้ซื้อสแกน QR บนมือถือไรเดอร์ = ยืนยันว่าได้รับของ
     *
     * @return array<string, mixed>
     */
    public function buyerScan(User $buyer, string $source, int $orderId, string $token): array
    {
        [$order, $job] = $this->resolveBuyerJob($buyer, $source, $orderId);
        $handover = $this->requireHandover($job);

        // กดซ้ำหลังยืนยันแล้ว → ได้ผลเดิม
        if ($handover->buyer_confirmed_at !== null) {
            return $this->buyerPayload($buyer, $order, $job);
        }

        $this->assertScannable($job, $handover);
        $this->verifyToken($handover, $token, self::SIDE_RIDER);

        $completed = DB::transaction(function () use ($job) {
            [$lockedJob, $locked] = $this->lockPair($job);

            if ($locked->buyer_confirmed_at !== null) {
                return false;
            }

            $this->assertScannable($lockedJob, $locked);

            $locked->buyer_confirmed_at = now();

            if ($locked->rider_confirmed_at !== null) {
                $this->completeLocked($lockedJob, $locked, $locked->method ?: 'qr');

                return true;
            }

            $locked->status = DeliveryHandover::STATUS_BUYER_CONFIRMED;
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

            return true;
        });

        $job->refresh()->unsetRelation('handover');

        if ($changed) {
            Log::warning('Handover: disputed', ['job_id' => $job->id, 'reason' => $reason]);

            $this->notifier->notifyAdmins(
                'ผู้ซื้อร้องเรียนการส่งมอบ ต้องตัดสิน',
                "งาน #{$job->job_number}: ".self::disputeReasonText($reason).($note ? " — {$note}" : '').' กรุณาเลือกปล่อยเงินหรือคืนเงินผู้ซื้อ',
                array_merge($this->pushData($job), ['reason' => $reason]),
                $this->adminJobUrl($job),
                'handover_disputed',
            );
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
        $distance = $this->assertWithinGeofence($job, $lat, $lng);

        $method = 'qr';
        if ($token !== null && $token !== '') {
            $this->verifyToken($handover, $token, self::SIDE_BUYER);
        } else {
            $this->verifyCode($handover, (string) $code);
            $method = 'code';
        }

        $completed = DB::transaction(function () use ($job, $lat, $lng, $distance, $method) {
            [$lockedJob, $locked] = $this->lockPair($job);

            if ($locked->rider_confirmed_at !== null) {
                return false;
            }

            $this->assertScannable($lockedJob, $locked);

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
        $distance = $this->assertWithinGeofence($job, $lat, $lng);

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
                $this->pushData($job, ['screen' => 'order-handover']),
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
        $distance = $this->assertWithinGeofence($job, $lat, $lng);

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
                $this->pushData($job, ['screen' => 'order-handover', 'auto_release_at' => $autoReleaseAt?->toIso8601String()]),
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

            $locked->forceFill([
                'resolved_at' => now(),
                'resolved_by' => $admin->id,
                'resolution' => 'release',
                'resolution_note' => $note,
            ]);

            $this->completeLocked(
                $lockedJob,
                $locked,
                'admin',
                DeliveryHandover::STATUS_RELEASED,
                $locked->rider_confirm_latitude !== null ? (float) $locked->rider_confirm_latitude : null,
                $locked->rider_confirm_longitude !== null ? (float) $locked->rider_confirm_longitude : null,
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

        DB::transaction(function () use ($job, $admin, $note, $reason) {
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

            // 1) งานไรเดอร์ → failed (admin_intervention) — ไม่เคลียร์เงิน ไรเดอร์ไม่ได้ค่าส่ง
            $this->jobs->adminFail($lockedJob, $admin, $reason);

            // 2) ยกเลิกออเดอร์ + คืนเงินเต็มจำนวน (ยังไม่มีการแบ่งเงิน → ไม่ต้องดึงคืนจากใคร)
            $source = $lockedJob->fresh()->deliverableSource();
            if ($source instanceof Order) {
                $source->fresh()->cancel($reason, (int) $admin->id, 'admin');
            } elseif ($source instanceof FreshMarketOrder) {
                app(FreshMarketService::class)->cancelOrder($source->fresh(), $reason, 'admin', $admin);
            }
        });

        Log::info('Handover: admin refunded', ['job_id' => $job->id, 'admin_id' => $admin->id]);

        $fresh = $job->fresh();
        $this->notifyResolved($fresh, 'refund');

        return $fresh->handover()->first();
    }

    /**
     * แอดมินตัดสินได้เมื่อ: ผู้ซื้อร้องเรียน หรือรอปลดเงินอัตโนมัติอยู่
     */
    public function adminCanResolve(RiderJob $job, ?DeliveryHandover $handover): bool
    {
        if (! $handover || $handover->isFinal() || $job->isTerminal()) {
            return false;
        }

        return in_array($handover->status, [
            DeliveryHandover::STATUS_DISPUTED,
            DeliveryHandover::STATUS_FALLBACK_PENDING_RELEASE,
        ], true);
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
     * @throws HandoverException HANDOVER_CODE_INVALID | HANDOVER_CODE_LOCKED
     */
    private function verifyCode(DeliveryHandover $handover, string $code): void
    {
        $code = preg_replace('/\D/', '', $code) ?? '';

        $result = DB::transaction(function () use ($handover, $code) {
            /** @var DeliveryHandover $row */
            $row = DeliveryHandover::whereKey($handover->id)->lockForUpdate()->firstOrFail();

            if ($row->code_locked_until !== null) {
                if ($row->code_locked_until->isFuture()) {
                    return ['locked', $row->code_locked_until];
                }

                // พ้นเวลาล็อกแล้ว → เริ่มนับใหม่
                $row->forceFill(['code_locked_until' => null, 'code_attempts' => 0]);
            }

            if (strlen($code) === 6 && $row->code_hash && Hash::check($code, $row->code_hash)) {
                if ((int) $row->code_attempts > 0 || $row->isDirty()) {
                    $row->forceFill(['code_attempts' => 0, 'code_locked_until' => null])->save();
                }

                return ['ok', null];
            }

            $attempts = (int) $row->code_attempts + 1;

            if ($attempts >= self::MAX_CODE_ATTEMPTS) {
                $until = now()->addMinutes(self::CODE_LOCK_MINUTES);
                $row->forceFill(['code_attempts' => $attempts, 'code_locked_until' => $until])->save();

                return ['locked', $until];
            }

            $row->forceFill(['code_attempts' => $attempts])->save();

            return ['invalid', self::MAX_CODE_ATTEMPTS - $attempts];
        });

        match ($result[0]) {
            'ok' => null,
            'locked' => throw HandoverException::codeLocked(Carbon::parse($result[1])),
            default => throw HandoverException::codeInvalid((int) $result[1]),
        };
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
    ): void {
        $locked->forceFill([
            'status' => $status,
            'method' => $method,
            'completed_at' => now(),
        ])->save();

        $lat ??= $locked->rider_confirm_latitude !== null ? (float) $locked->rider_confirm_latitude : null;
        $lng ??= $locked->rider_confirm_longitude !== null ? (float) $locked->rider_confirm_longitude : null;

        $this->jobs->completeHandoverLocked($lockedJob, $lat, $lng);
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
        $riderUserId = $fresh->rider_id ? Rider::withTrashed()->whereKey($fresh->rider_id)->value('user_id') : null;
        $sellerIds = array_values(array_diff($fresh->partyUserIds(), array_filter([$buyerId, $riderUserId])));
        $earned = round((float) $fresh->rider_earnings + $this->riderBonus($fresh), 2);

        $this->notifier->notifyUsers(
            [$buyerId],
            'handover_completed',
            'ได้รับของเรียบร้อย',
            "ส่งมอบสำเร็จ ขอบคุณที่ใช้บริการ งาน #{$fresh->job_number}",
            $this->pushData($fresh, ['screen' => 'order']),
        );

        if ($riderUserId) {
            $this->notifier->notifyUsers(
                [(int) $riderUserId],
                'handover_completed',
                'ส่งมอบสำเร็จ',
                'รายได้ ฿'.number_format($earned, 2)." เข้ากระเป๋าแล้ว งาน #{$fresh->job_number}",
                $this->pushData($fresh, ['screen' => 'rider-job-detail']),
            );
        }

        if ($sellerIds !== []) {
            $this->notifier->notifyUsers(
                $sellerIds,
                'handover_completed',
                'ลูกค้าได้รับของแล้ว',
                "ไรเดอร์ส่งมอบสำเร็จ รายได้จากออเดอร์นี้เข้ากระเป๋าร้านแล้ว งาน #{$fresh->job_number}",
                $this->pushData($fresh, ['screen' => 'merchant-order']),
            );
        }
    }

    /**
     * แจ้งผู้ซื้อ + ไรเดอร์ว่าแอดมินตัดสินแล้ว
     */
    private function notifyResolved(RiderJob $job, string $resolution): void
    {
        $buyerId = $this->buyerUserIdFor($job);
        $riderUserId = $job->rider_id ? Rider::withTrashed()->whereKey($job->rider_id)->value('user_id') : null;

        $buyerMessage = $resolution === 'refund'
            ? "ทีมงานตรวจสอบแล้ว คืนเงินเต็มจำนวนเข้ากระเป๋าเงินของคุณ งาน #{$job->job_number}"
            : "ทีมงานตรวจสอบแล้ว ยืนยันว่าส่งมอบสำเร็จ งาน #{$job->job_number}";
        $riderMessage = $resolution === 'refund'
            ? "ทีมงานตัดสินคืนเงินผู้ซื้อ งานนี้ไม่มีรายได้ค่าส่ง งาน #{$job->job_number}"
            : "ทีมงานยืนยันการส่งมอบแล้ว รายได้เข้ากระเป๋าของคุณ งาน #{$job->job_number}";

        $this->notifier->notifyUsers([$buyerId], 'handover_resolved', 'ผลการตรวจสอบการส่งมอบ', $buyerMessage,
            $this->pushData($job, ['resolution' => $resolution, 'screen' => 'order']), null, 'high');

        if ($riderUserId) {
            $this->notifier->notifyUsers([(int) $riderUserId], 'handover_resolved', 'ผลการตรวจสอบการส่งมอบ', $riderMessage,
                $this->pushData($job, ['resolution' => $resolution, 'screen' => 'rider-job-detail']), null, 'high');
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
     */
    private function assertWithinGeofence(RiderJob $job, float $lat, float $lng): ?int
    {
        if (! DeliveryFeeCalculator::isValidCoordinate($job->delivery_latitude, $job->delivery_longitude)) {
            // งานไม่มีพิกัดจุดส่ง (ข้อมูลผิดปกติ — createJobForSource บังคับพิกัดเสมอ) → ตรวจระยะไม่ได้ ไม่ขังเงินไว้
            Log::warning('Handover: job has no dropoff coordinate, geofence skipped', ['job_id' => $job->id]);

            return null;
        }

        $distance = self::distanceMeters($lat, $lng, (float) $job->delivery_latitude, (float) $job->delivery_longitude);
        $geofence = max(10, $this->config->intSetting('rider.handover_geofence_m'));

        if ($distance > $geofence) {
            throw HandoverException::tooFar($distance, $geofence);
        }

        return $distance;
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
                'can_dispute' => false, 'disputed_at' => null, 'dispute_reason' => null, 'completed_at' => null,
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
                'qr_token' => null, 'qr_expires_at' => null, 'geofence_m' => $geofence, 'wait_seconds' => $waitSeconds,
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
     * payload push ของการส่งมอบ: source (shop|fresh-market), order_id, job_id
     *
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function pushData(RiderJob $job, array $extra = []): array
    {
        $source = $job->deliverableSource();

        return array_merge([
            'source' => match (true) {
                $source instanceof FreshMarketOrder => self::SOURCE_FRESH_MARKET,
                $source instanceof Order => self::SOURCE_SHOP,
                default => null,
            },
            'order_id' => $job->source_id ? (int) $job->source_id : null,
            'job_id' => (int) $job->id,
        ], $extra);
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
