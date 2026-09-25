<?php

namespace App\Services;

use App\Exceptions\RiderJobException;
use App\Models\Rider;
use App\Models\RiderJob;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * จุดเดียวที่เปลี่ยนสถานะงานไรเดอร์ได้ (state machine ตาม RiderJob::TRANSITIONS)
 *
 * ทุกการเปลี่ยนสถานะ:
 *   1. DB::transaction + lockForUpdate แถวงาน
 *   2. ตรวจ RiderJob::TRANSITIONS (ผิด → RiderJobException INVALID_TRANSITION 409)
 *   3. ถ้าอยู่สถานะปลายทางอยู่แล้ว → คืนงานเดิม ไม่ทำซ้ำ (กดซ้ำ/เน็ตหลุดแล้วส่งใหม่ได้)
 *   4. เรียก $job->source->onRiderJobStatusChanged($job, $from) ใน transaction เดียวกัน
 *   5. หลัง commit แจ้งผู้ซื้อ/ผู้ขาย/ไรเดอร์ (in-app + Expo push — ไม่ใช้ LINE push)
 *
 * รับงานเป็น race-safe: UPDATE ... WHERE id=? AND status='pending' AND rider_id IS NULL
 * + ล็อกแถวไรเดอร์ กันไรเดอร์คนเดียวถือ 2 งานพร้อมกัน
 */
class RiderJobService
{
    public function __construct(
        private readonly DeliveryFeeCalculator $config,
        private readonly RiderEarningService $earnings,
        private readonly RiderNotificationService $notifier,
    ) {}

    // =====================================================
    // รับงาน / คืนงาน
    // =====================================================

    /**
     * ไรเดอร์กดรับงาน
     *
     * @throws RiderJobException JOB_TAKEN|NOT_ELIGIBLE|HAS_ACTIVE_JOB|INSUFFICIENT_COD_CREDIT|SELF_ORDER
     */
    public function accept(RiderJob $job, Rider $rider): RiderJob
    {
        $rider->refresh();
        $job->refresh();

        // กดซ้ำหลังรับสำเร็จแล้ว → คืนงานเดิม
        if ((int) $job->rider_id === (int) $rider->id && $job->status !== 'pending' && ! $job->isTerminal()) {
            return $job;
        }

        if (! $job->isOpen()) {
            throw RiderJobException::jobTaken();
        }

        $this->assertRiderCanAccept($rider);

        if (in_array((int) $rider->user_id, $job->partyUserIds(), true)) {
            throw RiderJobException::selfOrder();
        }

        if ($job->dispatch_type === 'cascade'
            && $job->current_offer_rider_id !== null
            && (int) $job->current_offer_rider_id !== (int) $rider->id) {
            throw RiderJobException::jobTaken();
        }

        $this->assertWithinPickupRadius($job, $rider);

        $accepted = DB::transaction(function () use ($job, $rider) {
            /** @var Rider $lockedRider */
            $lockedRider = Rider::whereKey($rider->id)->lockForUpdate()->firstOrFail();

            if ($reason = $lockedRider->onlineBlockReason()) {
                throw RiderJobException::notEligible($reason['message'], $reason['code']);
            }

            // อ่านแบบล็อก (เห็นข้อมูลล่าสุดที่ commit แล้ว) → กดรับ 2 งานพร้อมกันจะผ่านได้งานเดียว
            $activeId = RiderJob::where('rider_id', $lockedRider->id)
                ->whereIn('status', RiderJob::ACTIVE_STATUSES)
                ->lockForUpdate()
                ->value('id');
            if ($activeId) {
                throw RiderJobException::hasActiveJob((int) $activeId);
            }

            // ระหว่างรอ lock ระบบอาจปิดรับงานให้แล้ว (auto-offline)
            if ($lockedRider->availability !== 'online') {
                throw RiderJobException::notEligible('กรุณาเปิดรับงานก่อน', 'OFFLINE');
            }

            $this->assertCodCredit($job, $lockedRider);

            $attempts = $job->dispatch_attempts ?? [];
            $marked = false;
            foreach ($attempts as &$attempt) {
                if ((int) ($attempt['rider_id'] ?? 0) === (int) $lockedRider->id && ($attempt['status'] ?? null) === 'pending') {
                    $attempt['status'] = 'accepted';
                    $attempt['responded_at'] = now()->toIso8601String();
                    $marked = true;
                    break;
                }
            }
            unset($attempt);
            if (! $marked) {
                $attempts[] = ['rider_id' => (int) $lockedRider->id, 'status' => 'accepted', 'responded_at' => now()->toIso8601String()];
            }

            // ✅ Race-safe: มีแค่คนเดียวที่ UPDATE ผ่าน
            $query = RiderJob::whereKey($job->id)
                ->where('status', 'pending')
                ->whereNull('rider_id');

            if ($job->dispatch_type === 'cascade') {
                $query->where(function ($q) use ($lockedRider) {
                    $q->whereNull('current_offer_rider_id')
                        ->orWhere('current_offer_rider_id', $lockedRider->id);
                });
            }

            $updated = $query->update([
                'rider_id' => $lockedRider->id,
                'status' => 'accepted',
                'accepted_at' => now(),
                'current_offer_rider_id' => null,
                'offer_expires_at' => null,
                'dispatch_attempts' => json_encode($attempts),
                'tracking_token' => $job->tracking_token ?: Str::random(48),
                'tracking_expires_at' => now()->addHours(max(1, $this->config->intSetting('rider.tracking_expiry_hours'))),
                'gps_active' => true,
                'gps_lost_at' => null,
                'gps_warning_count' => 0,
            ]);

            if ($updated === 0) {
                throw RiderJobException::jobTaken();
            }

            $lockedRider->forceFill(['availability' => 'busy'])->save();
            $lockedRider->increment('total_jobs');

            /** @var RiderJob $fresh */
            $fresh = RiderJob::whereKey($job->id)->firstOrFail();
            $this->callSourceHook($fresh, 'pending');

            return $fresh;
        });

        Log::info('RiderJob: accepted', ['job_id' => $accepted->id, 'rider_id' => $rider->id]);

        $this->notifier->notifyParties($accepted->partyUserIds(), $accepted, 'accepted');

        return $accepted;
    }

    /**
     * ไรเดอร์คืนงาน (ก่อนรับของเท่านั้น) → งานกลับเป็น pending แล้วกระจายใหม่
     */
    public function release(RiderJob $job, Rider $rider, string $reason): RiderJob
    {
        $reason = trim($reason) !== '' ? mb_substr(trim($reason), 0, 255) : 'ไรเดอร์คืนงาน';

        [$fresh, $changed] = $this->transition(
            $job,
            'pending',
            $rider,
            function (RiderJob $locked) use ($rider, $reason) {
                $attempts = $locked->dispatch_attempts ?? [];
                $attempts[] = [
                    'rider_id' => (int) $rider->id,
                    'status' => 'released',
                    'reason' => $reason,
                    'at' => now()->toIso8601String(),
                ];

                $locked->fill([
                    'rider_id' => null,
                    'accepted_at' => null,
                    'release_count' => (int) $locked->release_count + 1,
                    'dispatch_attempts' => $attempts,
                    'current_offer_rider_id' => null,
                    'offer_expires_at' => null,
                    'gps_active' => true,
                    'gps_lost_at' => null,
                    'gps_warning_count' => 0,
                ]);

                Rider::whereKey($rider->id)->increment('cancelled_jobs');
            },
            fn (RiderJob $locked) => $locked->status === 'pending'
                && in_array((int) $rider->id, $locked->releasedRiderIds(), true),
            afterSave: fn () => Rider::find($rider->id)?->refreshAvailabilityAfterJob(),
        );

        if (! $changed) {
            return $fresh;
        }

        Log::info('RiderJob: released', ['job_id' => $fresh->id, 'rider_id' => $rider->id, 'reason' => $reason]);

        $this->afterBackToQueue($fresh, 'released');

        return $fresh->fresh();
    }

    // =====================================================
    // ระหว่างทาง
    // =====================================================

    /**
     * ไรเดอร์กำลังเดินทางไปรับของ
     */
    public function markPickingUp(RiderJob $job, Rider $rider): RiderJob
    {
        [$fresh, $changed] = $this->transition($job, 'picking_up', $rider);

        if ($changed) {
            $this->notifier->notifyParties($fresh->partyUserIds(), $fresh, 'picking_up');
        }

        return $fresh;
    }

    /**
     * ไรเดอร์รับของแล้ว (รูปยืนยันไม่บังคับ)
     */
    public function markPickedUp(RiderJob $job, Rider $rider, ?UploadedFile $photo = null): RiderJob
    {
        $job->refresh();
        if ($job->status === 'picked_up' && (int) $job->rider_id === (int) $rider->id) {
            return $job;
        }

        $path = $this->storePhoto($photo, $job, 'pickup_proof', false);

        try {
            [$fresh, $changed] = $this->transition($job, 'picked_up', $rider, function (RiderJob $locked) use ($path) {
                $locked->picked_up_at = now();
                if ($path) {
                    $locked->pickup_proof_image = $path;
                }
            });
        } catch (\Throwable $e) {
            $this->deletePhoto($path);
            throw $e;
        }

        if (! $changed) {
            $this->deletePhoto($path);
        } else {
            $this->notifier->notifyParties($fresh->partyUserIds(), $fresh, 'picked_up');
        }

        return $fresh;
    }

    /**
     * ไรเดอร์ออกเดินทางไปส่ง
     */
    public function markDelivering(RiderJob $job, Rider $rider): RiderJob
    {
        [$fresh, $changed] = $this->transition($job, 'delivering', $rider);

        if ($changed) {
            $this->notifier->notifyParties($fresh->partyUserIds(), $fresh, 'delivering');
        }

        return $fresh;
    }

    /**
     * ส่งของถึงมือลูกค้า → delivered → completed (ในคำขอเดียว) + เคลียร์เงินไรเดอร์
     *
     * @param  bool  $codCollected  ต้องเป็น true เมื่องานเก็บเงินปลายทาง
     *
     * @throws RiderJobException PHOTO_REQUIRED|COD_CONFIRM_REQUIRED|INVALID_TRANSITION|NOT_YOUR_JOB
     */
    public function deliver(RiderJob $job, Rider $rider, UploadedFile $photo, ?float $lat, ?float $lng, bool $codCollected): RiderJob
    {
        $job->refresh();

        // กดซ้ำหลังส่งสำเร็จ → คืนงานเดิม (และลองเคลียร์เงินซ้ำแบบ idempotent)
        if (in_array($job->status, ['delivered', 'completed'], true) && (int) $job->rider_id === (int) $rider->id) {
            $this->settleQuietly($job);

            return $job->fresh();
        }

        $cod = round((float) $job->cod_amount, 2);
        if ($cod > 0 && ! $codCollected) {
            throw RiderJobException::codConfirmRequired($cod);
        }

        $path = $this->storePhoto($photo, $job, 'delivery_proof', true);
        $graceMinutes = max(1, $this->config->intSetting('rider.tracking_grace_minutes'));
        $validPoint = $lat !== null && $lng !== null && DeliveryFeeCalculator::isValidCoordinate($lat, $lng);

        try {
            $completed = DB::transaction(function () use ($job, $rider, $path, $cod, $validPoint, $lat, $lng, $graceMinutes) {
                $locked = $this->lockJob($job);

                if ((int) $locked->rider_id !== (int) $rider->id) {
                    throw RiderJobException::notYourJob();
                }

                $from = (string) $locked->status;
                if (! RiderJob::canTransition($from, 'delivered')) {
                    throw RiderJobException::invalidTransition($from, 'delivered');
                }

                // ขั้นที่ 1: delivered
                $locked->fill([
                    'status' => 'delivered',
                    'delivered_at' => now(),
                    'delivery_proof_image' => $path,
                    'delivered_latitude' => $validPoint ? $lat : null,
                    'delivered_longitude' => $validPoint ? $lng : null,
                    'cod_collected_at' => $cod > 0 ? now() : null,
                ])->save();
                $this->callSourceHook($locked, $from);

                // ขั้นที่ 2: completed ทันที (ปิดลิงก์ติดตาม + หยุดแชร์ตำแหน่งลูกค้า)
                $locked->fill(array_merge([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'tracking_expires_at' => now()->addMinutes($graceMinutes),
                ], $this->stopCustomerSharing()))->save();
                $this->callSourceHook($locked, 'delivered');

                Rider::find($rider->id)?->refreshAvailabilityAfterJob();

                return $locked;
            });
        } catch (\Throwable $e) {
            $this->deletePhoto($path);
            throw $e;
        }

        Log::info('RiderJob: delivered+completed', ['job_id' => $completed->id, 'rider_id' => $rider->id, 'cod' => $cod]);

        $this->settleQuietly($completed);
        $this->notifier->notifyParties($completed->partyUserIds(), $completed, 'completed');

        return $completed->fresh();
    }

    /**
     * ส่งไม่สำเร็จ (หลังรับของแล้ว) → failed + แจ้งแอดมินจัดการคืนของ
     *
     * @throws RiderJobException INVALID_REASON|INVALID_TRANSITION|NOT_YOUR_JOB
     */
    public function fail(RiderJob $job, Rider $rider, string $reasonCode, ?string $note, ?UploadedFile $photo = null): RiderJob
    {
        if (! in_array($reasonCode, RiderJob::RIDER_FAILURE_REASONS, true)) {
            throw RiderJobException::invalidReason();
        }

        $note = $note !== null ? trim($note) : null;
        if ($reasonCode === 'other' && ($note === null || $note === '')) {
            throw new RiderJobException(RiderJobException::INVALID_REASON, 'กรุณาระบุรายละเอียดที่ส่งไม่สำเร็จ', 422);
        }

        $job->refresh();
        if ($job->status === 'failed' && (int) $job->rider_id === (int) $rider->id) {
            return $job;
        }

        $path = $this->storePhoto($photo, $job, 'failure_proof', false);

        try {
            [$fresh, $changed] = $this->markFailed($job, $rider, $reasonCode, $note, $path);
        } catch (\Throwable $e) {
            $this->deletePhoto($path);
            throw $e;
        }

        if ($changed) {
            $this->afterFailed($fresh, $rider);
        }

        return $fresh;
    }

    // =====================================================
    // ยกเลิก / แอดมิน
    // =====================================================

    /**
     * ยกเลิกงาน (ก่อนรับของเท่านั้น — หลังรับของต้องใช้ fail/adminFail)
     *
     * @param  string  $by  admin|seller|buyer|customer|rider|system
     * @param  bool  $notifySource  false เมื่อออเดอร์ต้นทางเป็นคนสั่งยกเลิกเอง (กันวนกลับ)
     *
     * @throws RiderJobException INVALID_TRANSITION
     */
    public function cancel(RiderJob $job, string $by, string $reason, bool $notifySource = true): RiderJob
    {
        $by = $this->normalizeCancelledBy($by);
        $reason = trim($reason) !== '' ? mb_substr(trim($reason), 0, 1000) : 'ยกเลิกงาน';
        $previousRiderId = $job->rider_id;
        $graceMinutes = max(1, $this->config->intSetting('rider.tracking_grace_minutes'));

        [$fresh, $changed] = $this->transition(
            $job,
            'cancelled',
            null,
            function (RiderJob $locked) use ($by, $reason, $graceMinutes, &$previousRiderId) {
                $previousRiderId = $locked->rider_id;

                $locked->fill(array_merge([
                    'cancelled_by' => $by,
                    'cancellation_reason' => $reason,
                    'cancelled_at' => now(),
                    'current_offer_rider_id' => null,
                    'offer_expires_at' => null,
                    'tracking_expires_at' => now()->addMinutes($graceMinutes),
                ], $this->stopCustomerSharing()));

                if ($by === 'rider' && $locked->rider_id) {
                    Rider::whereKey($locked->rider_id)->increment('cancelled_jobs');
                }
            },
            null,
            $notifySource,
            afterSave: function (RiderJob $locked) {
                if ($locked->rider_id) {
                    Rider::find($locked->rider_id)?->refreshAvailabilityAfterJob();
                }
            },
        );

        if (! $changed) {
            return $fresh;
        }

        Log::info('RiderJob: cancelled', ['job_id' => $fresh->id, 'by' => $by]);

        if ($previousRiderId) {
            $this->notifier->notifyRider(
                Rider::find($previousRiderId),
                $fresh,
                'งานถูกยกเลิก',
                "งาน #{$fresh->job_number} ถูกยกเลิกแล้ว ไม่ต้องไปรับของ",
                'cancelled'
            );
        }

        if ($notifySource) {
            $this->notifier->notifyParties($fresh->partyUserIds(), $fresh, 'cancelled');
        }

        return $fresh;
    }

    /**
     * แอดมินปิดงานที่รับของไปแล้วเป็น "ส่งไม่สำเร็จ" (เช่น ไรเดอร์หายไปพร้อมของ)
     */
    public function adminFail(RiderJob $job, User $admin, string $reason): RiderJob
    {
        [$fresh, $changed] = $this->markFailed($job, null, 'admin_intervention', mb_substr(trim($reason), 0, 1000)." (แอดมิน #{$admin->id})", null);

        if ($changed) {
            $this->afterFailed($fresh, $fresh->rider);
        }

        return $fresh;
    }

    /**
     * ออเดอร์ถูกยกเลิกหลังไรเดอร์รับของแล้ว → failed (order_cancelled) + ให้ไรเดอร์นำของคืนร้าน
     *
     * ไม่เรียก hook กลับไปที่ออเดอร์ (ออเดอร์เป็นคนสั่งยกเลิกเอง)
     */
    public function failForCancelledSource(RiderJob $job, string $reason): RiderJob
    {
        [$fresh, $changed] = $this->markFailed($job, null, 'order_cancelled', mb_substr(trim($reason), 0, 1000), null, false);

        if ($changed) {
            $rider = $fresh->rider;

            $this->notifier->notifyRider(
                $rider,
                $fresh,
                'ออเดอร์ถูกยกเลิก กรุณานำของคืนร้าน',
                "ออเดอร์ของงาน #{$fresh->job_number} ถูกยกเลิกระหว่างจัดส่ง กรุณานำสินค้าคืนร้านค้า ทีมงานจะติดต่อเรื่องค่าวิ่ง",
                'order_cancelled'
            );

            $this->notifier->notifyAdmins(
                'ออเดอร์ถูกยกเลิกระหว่างไรเดอร์ถือของ',
                "งาน #{$fresh->job_number}: {$reason} — ประสานไรเดอร์นำของคืนร้าน",
                ['job_id' => (int) $fresh->id, 'rider_id' => $rider?->id]
            );
        }

        return $fresh;
    }

    /**
     * แอดมินมอบหมายงานให้ไรเดอร์คนใหม่ (คงสถานะเดิม — pending จะกลายเป็น accepted)
     *
     * @throws RiderJobException NOT_ELIGIBLE|HAS_ACTIVE_JOB|SELF_ORDER|INSUFFICIENT_COD_CREDIT|INVALID_TRANSITION
     */
    public function adminReassign(RiderJob $job, Rider $newRider, User $admin): RiderJob
    {
        $job->refresh();
        $newRider->refresh();

        if ($job->isTerminal() || $job->status === 'delivered') {
            throw RiderJobException::invalidTransition((string) $job->status, (string) $job->status);
        }

        if ((int) $job->rider_id === (int) $newRider->id) {
            return $job;
        }

        if ($reason = $newRider->onlineBlockReason()) {
            throw RiderJobException::notEligible($reason['message'], $reason['code']);
        }

        if (in_array((int) $newRider->user_id, $job->partyUserIds(), true)) {
            throw RiderJobException::selfOrder();
        }

        $oldRiderId = null;

        $fresh = DB::transaction(function () use ($job, $newRider, $admin, &$oldRiderId) {
            $locked = $this->lockJob($job);

            if ($locked->isTerminal() || $locked->status === 'delivered') {
                throw RiderJobException::invalidTransition((string) $locked->status, (string) $locked->status);
            }

            /** @var Rider $lockedRider */
            $lockedRider = Rider::whereKey($newRider->id)->lockForUpdate()->firstOrFail();

            $activeId = RiderJob::where('rider_id', $lockedRider->id)
                ->whereIn('status', RiderJob::ACTIVE_STATUSES)
                ->where('id', '!=', $locked->id)
                ->value('id');
            if ($activeId) {
                throw RiderJobException::hasActiveJob((int) $activeId);
            }

            $this->assertCodCredit($locked, $lockedRider);

            $from = (string) $locked->status;
            $oldRiderId = $locked->rider_id;

            $attempts = $locked->dispatch_attempts ?? [];
            $attempts[] = [
                'rider_id' => (int) $lockedRider->id,
                'status' => 'admin_assigned',
                'by_admin_id' => (int) $admin->id,
                'replaced_rider_id' => $oldRiderId ? (int) $oldRiderId : null,
                'at' => now()->toIso8601String(),
            ];

            $locked->fill([
                'rider_id' => $lockedRider->id,
                'status' => $from === 'pending' ? 'accepted' : $from,
                'accepted_at' => $from === 'pending' ? now() : ($locked->accepted_at ?? now()),
                'current_offer_rider_id' => null,
                'offer_expires_at' => null,
                'dispatch_attempts' => $attempts,
                'tracking_token' => $locked->tracking_token ?: Str::random(48),
                'tracking_expires_at' => now()->addHours(max(1, $this->config->intSetting('rider.tracking_expiry_hours'))),
                'gps_active' => true,
                'gps_lost_at' => null,
                'gps_warning_count' => 0,
            ])->save();

            $lockedRider->forceFill(['availability' => 'busy'])->save();
            $lockedRider->increment('total_jobs');

            if ($oldRiderId) {
                Rider::find($oldRiderId)?->refreshAvailabilityAfterJob();
            }

            $this->callSourceHook($locked, $from);

            return $locked;
        });

        Log::info('RiderJob: admin reassigned', [
            'job_id' => $fresh->id,
            'from_rider' => $oldRiderId,
            'to_rider' => $newRider->id,
            'admin_id' => $admin->id,
        ]);

        if ($oldRiderId) {
            $this->notifier->notifyRider(
                Rider::find($oldRiderId),
                $fresh,
                'งานถูกย้ายให้ไรเดอร์คนอื่น',
                "ทีมงานย้ายงาน #{$fresh->job_number} ให้ไรเดอร์คนอื่นแล้ว",
                'reassigned_away'
            );
        }

        $this->notifier->notifyRider(
            $newRider,
            $fresh,
            'ได้รับมอบหมายงานใหม่',
            "ทีมงานมอบหมายงาน #{$fresh->job_number} ให้คุณ เปิดแอปเพื่อดูรายละเอียด",
            'assigned'
        );

        $this->notifier->notifyParties($fresh->partyUserIds(), $fresh, 'reassigned');

        return $fresh;
    }

    /**
     * จัดการงานค้างหลังระงับไรเดอร์
     *
     * - ยังไม่รับของ (accepted/picking_up) → คืนเข้าคิวให้ไรเดอร์คนอื่น
     * - รับของแล้ว → แจ้งแอดมินให้มอบหมายใหม่ (ของอยู่กับไรเดอร์ ย้ายเองไม่ได้)
     */
    public function handleRiderSuspended(Rider $rider): ?RiderJob
    {
        $job = $rider->activeJob();
        if (! $job) {
            $rider->refreshAvailabilityAfterJob();

            return null;
        }

        if (in_array($job->status, ['accepted', 'picking_up'], true)) {
            [$fresh, $changed] = $this->transition(
                $job,
                'pending',
                $rider,
                function (RiderJob $locked) use ($rider) {
                    $attempts = $locked->dispatch_attempts ?? [];
                    $attempts[] = [
                        'rider_id' => (int) $rider->id,
                        'status' => 'released',
                        'reason' => 'ไรเดอร์ถูกระงับ',
                        'at' => now()->toIso8601String(),
                    ];
                    $locked->fill([
                        'rider_id' => null,
                        'accepted_at' => null,
                        'dispatch_attempts' => $attempts,
                        'gps_active' => true,
                        'gps_lost_at' => null,
                        'gps_warning_count' => 0,
                    ]);
                },
                afterSave: fn () => Rider::find($rider->id)?->refreshAvailabilityAfterJob(),
            );

            if ($changed) {
                $this->afterBackToQueue($fresh, 'released');
            }

            return $fresh;
        }

        $this->notifier->notifyAdmins(
            'ไรเดอร์ที่ถูกระงับยังถือของอยู่',
            "งาน #{$job->job_number} สถานะ {$job->status_text} — กรุณามอบหมายไรเดอร์ใหม่หรือปิดงาน",
            ['job_id' => (int) $job->id, 'rider_id' => (int) $rider->id]
        );

        return $job;
    }

    /**
     * ปุ่มที่ไรเดอร์คนนี้กดได้ตอนนี้
     *
     * @return array<int, string>
     */
    public function allowedActions(RiderJob $job, ?Rider $rider): array
    {
        return $job->allowedActionsFor($rider);
    }

    // =====================================================
    // ภายใน
    // =====================================================

    /**
     * เปลี่ยนสถานะแบบมาตรฐาน
     *
     * @param  callable(RiderJob, string): void|null  $mutate  แก้ฟิลด์เพิ่มก่อน save
     * @param  callable(RiderJob): bool|null  $alreadyDone  true = ทำไปแล้ว ไม่ต้องทำซ้ำ
     * @param  callable(RiderJob): void|null  $afterSave  ทำต่อหลัง save (ใน transaction)
     * @return array{0: RiderJob, 1: bool} [งานล่าสุด, เปลี่ยนจริงหรือไม่]
     */
    private function transition(
        RiderJob $job,
        string $to,
        ?Rider $actor,
        ?callable $mutate = null,
        ?callable $alreadyDone = null,
        bool $notifySource = true,
        ?callable $afterSave = null,
    ): array {
        return DB::transaction(function () use ($job, $to, $actor, $mutate, $alreadyDone, $notifySource, $afterSave) {
            $locked = $this->lockJob($job);

            if ($alreadyDone && $alreadyDone($locked)) {
                return [$locked, false];
            }

            if ($actor && (int) $locked->rider_id !== (int) $actor->id) {
                throw RiderJobException::notYourJob();
            }

            $from = (string) $locked->status;
            if ($from === $to) {
                return [$locked, false];
            }

            if (! RiderJob::canTransition($from, $to)) {
                throw RiderJobException::invalidTransition($from, $to);
            }

            $locked->status = $to;
            if ($mutate) {
                $mutate($locked, $from);
            }
            $locked->save();

            if ($afterSave) {
                $afterSave($locked);
            }

            if ($notifySource) {
                $this->callSourceHook($locked, $from);
            }

            return [$locked, true];
        });
    }

    /**
     * ล็อกแถวงาน (ต้องอยู่ใน transaction)
     */
    private function lockJob(RiderJob $job): RiderJob
    {
        /** @var RiderJob|null $locked */
        $locked = RiderJob::whereKey($job->id)->lockForUpdate()->first();

        if (! $locked) {
            throw RiderJobException::jobNotFound();
        }

        return $locked;
    }

    /**
     * เปลี่ยนเป็น failed (ใช้ร่วมกันระหว่างไรเดอร์/แอดมิน/ออเดอร์ถูกยกเลิก)
     *
     * @return array{0: RiderJob, 1: bool}
     */
    private function markFailed(RiderJob $job, ?Rider $actor, string $reasonCode, ?string $note, ?string $photoPath, bool $notifySource = true): array
    {
        $graceMinutes = max(1, $this->config->intSetting('rider.tracking_grace_minutes'));

        return $this->transition(
            $job,
            'failed',
            $actor,
            function (RiderJob $locked) use ($reasonCode, $note, $photoPath, $graceMinutes) {
                $locked->fill(array_merge([
                    'failed_at' => now(),
                    'failure_reason' => $reasonCode,
                    'failure_note' => $note !== null ? mb_substr($note, 0, 1000) : null,
                    'failure_proof_image' => $photoPath,
                    'tracking_expires_at' => now()->addMinutes($graceMinutes),
                ], $this->stopCustomerSharing()));
            },
            null,
            $notifySource,
            afterSave: function (RiderJob $locked) {
                if ($locked->rider_id) {
                    Rider::find($locked->rider_id)?->refreshAvailabilityAfterJob();
                }
            },
        );
    }

    /**
     * แจ้งทุกฝ่ายหลังงานล้มเหลว
     */
    private function afterFailed(RiderJob $job, ?Rider $rider): void
    {
        Log::warning('RiderJob: failed', ['job_id' => $job->id, 'reason' => $job->failure_reason]);

        $this->notifier->notifyParties($job->partyUserIds(), $job, 'failed');

        $this->notifier->notifyAdmins(
            'งานส่งไม่สำเร็จ ต้องประสานคืนของ',
            "งาน #{$job->job_number}: ".(RiderJob::FAILURE_REASONS[$job->failure_reason] ?? $job->failure_reason)
                .($job->failure_note ? " — {$job->failure_note}" : '')
                .($rider ? " (ไรเดอร์ {$rider->full_name})" : ''),
            ['job_id' => (int) $job->id, 'rider_id' => $rider?->id]
        );
    }

    /**
     * หลังงานกลับเข้าคิว (คืนงาน/ไรเดอร์ถูกระงับ): กระจายใหม่ หรือส่งให้แอดมินถ้าคืนบ่อยเกิน
     */
    private function afterBackToQueue(RiderJob $job, string $event): void
    {
        $this->notifier->notifyParties($job->partyUserIds(), $job, $event);

        $maxReleases = max(1, $this->config->intSetting('rider.max_release_count'));

        if ((int) $job->release_count >= $maxReleases) {
            RiderJob::whereKey($job->id)->where('status', 'pending')->update(['dispatch_type' => 'manual_needed']);

            $this->notifier->notifyAdmins(
                'งานถูกคืนหลายครั้ง ต้องจัดไรเดอร์เอง',
                "งาน #{$job->job_number} ถูกคืน {$job->release_count} ครั้ง กรุณามอบหมายไรเดอร์",
                ['job_id' => (int) $job->id]
            );

            return;
        }

        try {
            app(RiderDispatchService::class)->dispatch($job->fresh(), true);
        } catch (\Throwable $e) {
            Log::error('RiderJob: re-dispatch failed', ['job_id' => $job->id, 'error' => $e->getMessage()]);
        }
    }

    /**
     * เรียก hook ของออเดอร์ต้นทาง (อยู่ใน transaction เดียวกับการเปลี่ยนสถานะ)
     */
    private function callSourceHook(RiderJob $job, string $from): void
    {
        $source = $job->deliverableSource();

        if ($source) {
            $source->onRiderJobStatusChanged($job, $from);
        }
    }

    /**
     * ตรวจสิทธิ์รับงานของไรเดอร์ (แปลงเป็น exception ที่ตรงรหัส)
     */
    private function assertRiderCanAccept(Rider $rider): void
    {
        $reason = $rider->acceptBlockReason();
        if ($reason === null) {
            return;
        }

        if ($reason['code'] === 'HAS_ACTIVE_JOB') {
            throw RiderJobException::hasActiveJob($rider->activeJob()?->id);
        }

        throw RiderJobException::notEligible($reason['message'], $reason['code']);
    }

    /**
     * ไรเดอร์ต้องอยู่ในรัศมีกระจายงาน (+2 กม. เผื่อ GPS คลาดเคลื่อน)
     */
    private function assertWithinPickupRadius(RiderJob $job, Rider $rider): void
    {
        if ($rider->last_latitude === null || $rider->last_longitude === null
            || $job->pickup_latitude === null || $job->pickup_longitude === null) {
            return;
        }

        // รัศมีของงาน = รัศมีกระจายรอบล่าสุด (sweep ขยายได้ถึง rider.max_offer_radius_km) แต่ไม่ต่ำกว่าค่าตั้งต้น
        $radius = max(
            (float) ($job->dispatch_radius_km ?? 0),
            $this->config->floatSetting('rider.offer_radius_km')
        );

        $distance = DeliveryFeeCalculator::haversineKm(
            (float) $rider->last_latitude,
            (float) $rider->last_longitude,
            (float) $job->pickup_latitude,
            (float) $job->pickup_longitude
        );

        if ($distance > $radius + 2.0) {
            throw RiderJobException::notEligible(
                'คุณอยู่ห่างจุดรับของ '.number_format($distance, 1).' กม. ไกลเกินกว่าจะรับงานนี้',
                'TOO_FAR'
            );
        }
    }

    /**
     * งาน COD: วอลเลตไรเดอร์ต้องมี ≥ cod_amount − rider_earnings (เงินที่ต้องนำส่งคืนระบบ)
     *
     * และต้องไม่มีงาน COD เก่าที่ส่งแล้วแต่ยังนำส่งเงินไม่ได้ (cod_settled_at ว่าง) —
     * กันไรเดอร์ที่ถอน/ใช้เงินจนหักไม่ได้ รับงานเก็บเงินสดเพิ่มไปเรื่อยๆ
     */
    private function assertCodCredit(RiderJob $job, Rider $rider): void
    {
        $cod = round((float) $job->cod_amount, 2);
        if ($cod <= 0) {
            return;
        }

        if (RiderJob::riderHasUnsettledCod((int) $rider->id)) {
            throw new RiderJobException(
                RiderJobException::INSUFFICIENT_COD_CREDIT,
                'คุณยังมีเงินเก็บปลายทางจากงานก่อนที่ยังไม่ได้นำส่งเข้าระบบ กรุณาเติมเงินเข้าวอลเลตให้พอก่อนรับงานเก็บเงินปลายทางใหม่',
                403,
                ['unsettled_cod' => true]
            );
        }

        $required = round($cod - (float) $job->rider_earnings, 2);
        if ($required <= 0) {
            return;
        }

        $available = round((float) (Wallet::where('user_id', $rider->user_id)->value('balance') ?? 0), 2);
        if ($available < $required) {
            throw RiderJobException::insufficientCodCredit($required, $available);
        }
    }

    /**
     * เคลียร์เงินแบบไม่ให้ล้มคำขอ (ล้มเหลว → sweep ลองใหม่)
     */
    private function settleQuietly(RiderJob $job): void
    {
        try {
            $this->earnings->settle($job);
        } catch (\Throwable $e) {
            Log::error('RiderJob: settle failed (will retry in sweep)', [
                'job_id' => $job->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * ฟิลด์ที่ต้องล้างเมื่องานจบ (หยุดแชร์ตำแหน่งลูกค้าอัตโนมัติ)
     *
     * @return array<string, mixed>
     */
    private function stopCustomerSharing(): array
    {
        return [
            'customer_share_location' => false,
            'customer_last_latitude' => null,
            'customer_last_longitude' => null,
            'customer_location_at' => null,
        ];
    }

    /**
     * เก็บรูปยืนยัน (public disk, ชื่อไฟล์สุ่มยาว)
     */
    private function storePhoto(?UploadedFile $photo, RiderJob $job, string $kind, bool $required): ?string
    {
        if (! $photo) {
            if ($required) {
                throw RiderJobException::photoRequired();
            }

            return null;
        }

        $mime = (string) $photo->getMimeType();
        if (! $photo->isValid() || ! str_starts_with($mime, 'image/')) {
            throw new RiderJobException(RiderJobException::PHOTO_REQUIRED, 'ไฟล์รูปไม่ถูกต้อง กรุณาถ่ายรูปใหม่', 422);
        }

        $path = $photo->store("rider_jobs/{$job->id}/{$kind}", 'public');

        if (! $path) {
            Log::error('RiderJob: cannot store photo', ['job_id' => $job->id, 'kind' => $kind]);
            throw new RiderJobException(RiderJobException::PHOTO_REQUIRED, 'บันทึกรูปไม่สำเร็จ กรุณาลองใหม่', 422);
        }

        return $path;
    }

    private function deletePhoto(?string $path): void
    {
        if ($path) {
            try {
                Storage::disk('public')->delete($path);
            } catch (\Throwable) {
                // ลบไม่ได้ก็ไม่เป็นไร (ไฟล์กำพร้า ไม่กระทบข้อมูล)
            }
        }
    }

    /**
     * ค่าที่ใส่คอลัมน์ cancelled_by ได้
     */
    private function normalizeCancelledBy(string $by): string
    {
        $by = strtolower(trim($by));

        return in_array($by, ['rider', 'customer', 'system', 'admin', 'seller', 'buyer'], true) ? $by : 'system';
    }
}
