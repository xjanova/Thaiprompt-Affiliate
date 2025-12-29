<?php

namespace App\Services;

use App\Models\ServiceBooking;
use App\Models\ServiceBookingDispute;
use App\Models\ServiceBookingLocationLog;
use App\Models\ServiceBookingPenalty;
use App\Models\User;
use App\Models\UserBlock;
use App\Models\UserTrustScore;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * AntiAbuseService
 *
 * บริการป้องกันการกลั่นแกล้งและฉ้อโกง
 *
 * ฟีเจอร์หลัก:
 * 1. กฎการยกเลิก (Cancellation Rules)
 * 2. ระบบคะแนนความน่าเชื่อถือ (Trust Score)
 * 3. การตรวจจับพฤติกรรมผิดปกติ
 * 4. การบันทึกประวัติตำแหน่ง (Location History)
 * 5. การจัดการข้อพิพาท (Dispute Management)
 */
class AntiAbuseService
{
    /**
     * กฎการยกเลิก: ช่วงเวลาก่อนนัดหมายที่ต้องเสียค่าปรับ
     */
    public const CANCELLATION_RULES = [
        // ชั่วโมงก่อนนัดหมาย => เปอร์เซ็นต์ค่าปรับ
        0 => 100,    // ไม่มา/ยกเลิกหลังเวลา = 100%
        1 => 80,     // ยกเลิกภายใน 1 ชม. = 80%
        2 => 50,     // ยกเลิกภายใน 2 ชม. = 50%
        6 => 30,     // ยกเลิกภายใน 6 ชม. = 30%
        24 => 10,    // ยกเลิกภายใน 24 ชม. = 10%
        48 => 0,     // ยกเลิกก่อน 48 ชม. = ไม่เสียค่าปรับ
    ];

    /**
     * สถานะที่อนุญาตให้ยกเลิก
     */
    public const CANCELLABLE_STATUSES = [
        'pending',
        'confirmed',
        'waiting_provider',
        'provider_accepted',
    ];

    /**
     * สถานะที่ยกเลิกแล้วต้องเสียค่าปรับแน่นอน (กำลังให้บริการ)
     */
    public const IN_SERVICE_STATUSES = [
        'provider_on_way',
        'in_progress',
    ];

    /**
     * บันทึกตำแหน่ง GPS
     *
     * @param  string  $actorType  'user' หรือ 'provider'
     * @param  array  $locationData  ข้อมูลตำแหน่ง
     */
    public function logLocation(
        ServiceBooking $booking,
        string $actorType,
        array $locationData
    ): ServiceBookingLocationLog {
        $log = ServiceBookingLocationLog::create([
            'service_booking_id' => $booking->id,
            'user_id' => $actorType === 'user' ? $booking->user_id : null,
            'provider_id' => $actorType === 'provider' ? $booking->provider_id : null,
            'actor_type' => $actorType,
            'latitude' => $locationData['latitude'],
            'longitude' => $locationData['longitude'],
            'accuracy' => $locationData['accuracy'] ?? null,
            'speed' => $locationData['speed'] ?? null,
            'heading' => $locationData['heading'] ?? null,
            'altitude' => $locationData['altitude'] ?? null,
            'source' => $locationData['source'] ?? 'gps',
            'battery_level' => $locationData['battery_level'] ?? null,
            'is_charging' => $locationData['is_charging'] ?? null,
            'app_state' => $locationData['app_state'] ?? 'foreground',
            'recorded_at' => $locationData['recorded_at'] ?? now(),
        ]);

        // ตรวจจับ GPS Spoofing
        $spoofingFlags = $log->detectSpoofing();
        if (! empty($spoofingFlags)) {
            $this->flagSuspiciousActivity($booking, 'gps_spoofing', $spoofingFlags);
        }

        return $log;
    }

    /**
     * คำนวณค่าปรับการยกเลิก
     *
     * @return array ['fee' => float, 'percentage' => int, 'reason' => string]
     */
    public function calculateCancellationFee(ServiceBooking $booking): array
    {
        // ถ้ากำลังให้บริการอยู่ = 100%
        if (in_array($booking->status, self::IN_SERVICE_STATUSES)) {
            return [
                'fee' => $booking->total_amount,
                'percentage' => 100,
                'reason' => 'ยกเลิกระหว่างให้บริการ',
                'is_late' => true,
            ];
        }

        // คำนวณเวลาก่อนนัดหมาย
        $scheduledAt = $booking->scheduled_at;
        if (! $scheduledAt) {
            return [
                'fee' => 0,
                'percentage' => 0,
                'reason' => 'ไม่มีกำหนดเวลานัด',
                'is_late' => false,
            ];
        }

        $hoursUntil = now()->diffInHours($scheduledAt, false);

        // หาเปอร์เซ็นต์ค่าปรับ
        $percentage = 0;
        $reason = 'ยกเลิกล่วงหน้าเกิน 48 ชม.';
        $isLate = false;

        foreach (self::CANCELLATION_RULES as $hours => $pct) {
            if ($hoursUntil <= $hours) {
                $percentage = $pct;
                if ($hours === 0) {
                    $reason = 'ไม่มาตามนัดหมาย';
                    $isLate = true;
                } elseif ($hours <= 2) {
                    $reason = "ยกเลิกกระทันหัน (ภายใน {$hours} ชม.)";
                    $isLate = true;
                } else {
                    $reason = "ยกเลิกล่วงหน้าน้อยกว่า {$hours} ชม.";
                    $isLate = $hours <= 6;
                }
                break;
            }
        }

        $fee = ($booking->total_amount * $percentage) / 100;

        return [
            'fee' => round($fee, 2),
            'percentage' => $percentage,
            'reason' => $reason,
            'is_late' => $isLate,
        ];
    }

    /**
     * ดำเนินการยกเลิก booking พร้อมคำนวณค่าปรับ
     *
     * @param  string  $cancelledByType  'user', 'provider', 'system', 'admin'
     * @param  string  $reason  เหตุผล
     * @return array ['success' => bool, 'fee' => float, 'message' => string]
     */
    public function cancelBooking(
        ServiceBooking $booking,
        string $cancelledByType,
        string $reason
    ): array {
        // ตรวจสอบว่ายกเลิกได้หรือไม่
        $cancellableStatuses = array_merge(self::CANCELLABLE_STATUSES, self::IN_SERVICE_STATUSES);
        if (! in_array($booking->status, $cancellableStatuses)) {
            return [
                'success' => false,
                'fee' => 0,
                'message' => 'ไม่สามารถยกเลิกในสถานะนี้ได้',
            ];
        }

        // คำนวณค่าปรับ
        $feeInfo = $this->calculateCancellationFee($booking);

        return DB::transaction(function () use ($booking, $cancelledByType, $reason, $feeInfo) {
            // อัพเดท booking
            $booking->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
                'cancelled_by_type' => $cancelledByType,
                'cancellation_fee' => $feeInfo['fee'],
            ]);

            // สร้าง Penalty record ถ้ามีค่าปรับ
            if ($feeInfo['fee'] > 0) {
                $this->createPenalty($booking, $cancelledByType, $feeInfo);
            }

            // อัพเดท Trust Score
            $this->updateTrustScoreForCancellation($booking, $cancelledByType, $feeInfo['is_late']);

            return [
                'success' => true,
                'fee' => $feeInfo['fee'],
                'message' => $feeInfo['fee'] > 0
                    ? 'ยกเลิกสำเร็จ ค่าปรับ ฿'.number_format($feeInfo['fee'], 2)
                    : 'ยกเลิกสำเร็จ ไม่มีค่าปรับ',
            ];
        });
    }

    /**
     * สร้าง Penalty record
     */
    protected function createPenalty(
        ServiceBooking $booking,
        string $cancelledByType,
        array $feeInfo
    ): ServiceBookingPenalty {
        $penalizedType = $cancelledByType;
        $userId = $cancelledByType === 'user' ? $booking->user_id : null;
        $providerId = $cancelledByType === 'provider' ? $booking->provider_id : null;

        return ServiceBookingPenalty::create([
            'service_booking_id' => $booking->id,
            'user_id' => $userId,
            'provider_id' => $providerId,
            'penalized_type' => $penalizedType,
            'penalty_type' => $feeInfo['is_late'] ? 'late_cancellation' : 'late_cancellation',
            'penalty_amount' => $feeInfo['fee'],
            'trust_score_deduction' => $feeInfo['is_late'] ? 10 : 5,
            'status' => 'pending',
            'reason' => $feeInfo['reason'],
        ]);
    }

    /**
     * อัพเดท Trust Score หลังยกเลิก
     */
    protected function updateTrustScoreForCancellation(
        ServiceBooking $booking,
        string $cancelledByType,
        bool $isLate
    ): void {
        if ($cancelledByType === 'user') {
            $trustScore = UserTrustScore::getOrCreateForUser($booking->user);
            $trustScore->penalizeForCancellation($isLate);
        } elseif ($cancelledByType === 'provider' && $booking->provider) {
            $trustScore = UserTrustScore::getOrCreateForProvider($booking->provider);
            $trustScore->penalizeForCancellation($isLate);
        }
    }

    /**
     * จัดการกรณี No-Show (ไม่มาตามนัด)
     */
    public function handleNoShow(ServiceBooking $booking, string $noShowType): array
    {
        return DB::transaction(function () use ($booking, $noShowType) {
            // คำนวณค่าปรับ 100%
            $fee = $booking->total_amount;

            // อัพเดท booking
            $booking->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => $noShowType === 'user' ? 'ลูกค้าไม่มาตามนัด' : 'ผู้ให้บริการไม่มาตามนัด',
                'cancelled_by_type' => 'system',
                'cancellation_fee' => $fee,
            ]);

            // สร้าง Penalty
            $penalty = ServiceBookingPenalty::create([
                'service_booking_id' => $booking->id,
                'user_id' => $noShowType === 'user' ? $booking->user_id : null,
                'provider_id' => $noShowType === 'provider' ? $booking->provider_id : null,
                'penalized_type' => $noShowType,
                'penalty_type' => 'no_show',
                'penalty_amount' => $fee,
                'trust_score_deduction' => 20,
                'status' => 'pending',
                'reason' => 'ไม่มาตามนัดหมาย',
            ]);

            // อัพเดท Trust Score
            if ($noShowType === 'user') {
                $trustScore = UserTrustScore::getOrCreateForUser($booking->user);
                $trustScore->penalizeForNoShow();
            } elseif ($booking->provider) {
                $trustScore = UserTrustScore::getOrCreateForProvider($booking->provider);
                $trustScore->penalizeForNoShow();
            }

            // สร้าง Dispute อัตโนมัติ
            $dispute = $this->createAutoDispute($booking, 'no_show', $noShowType);

            return [
                'success' => true,
                'fee' => $fee,
                'penalty' => $penalty,
                'dispute' => $dispute,
                'message' => 'บันทึกการไม่มาตามนัดเรียบร้อย',
            ];
        });
    }

    /**
     * สร้าง Dispute อัตโนมัติ
     */
    protected function createAutoDispute(
        ServiceBooking $booking,
        string $type,
        string $accusedType
    ): ServiceBookingDispute {
        return ServiceBookingDispute::create([
            'service_booking_id' => $booking->id,
            'reporter_type' => 'system',
            'accused_user_id' => $accusedType === 'user' ? $booking->user_id : null,
            'accused_provider_id' => $accusedType === 'provider' ? $booking->provider_id : null,
            'accused_type' => $accusedType,
            'dispute_type' => $type,
            'title' => ServiceBookingDispute::DISPUTE_TYPES[$type] ?? $type,
            'description' => "ระบบตรวจพบการ {$type} โดยอัตโนมัติ",
            'status' => 'pending',
            'priority' => $type === 'no_show' ? 'high' : 'medium',
        ]);
    }

    /**
     * ตรวจจับพฤติกรรมน่าสงสัยและบันทึก flag
     */
    public function flagSuspiciousActivity(
        ServiceBooking $booking,
        string $flagType,
        array $details = []
    ): void {
        $currentFlags = $booking->suspicious_flags ?? [];
        $currentFlags[] = [
            'type' => $flagType,
            'details' => $details,
            'flagged_at' => now()->toIso8601String(),
        ];

        $booking->update([
            'is_suspicious' => true,
            'suspicious_flags' => $currentFlags,
        ]);

        // Log สำหรับ monitoring
        Log::warning('Suspicious activity detected', [
            'booking_id' => $booking->id,
            'flag_type' => $flagType,
            'details' => $details,
        ]);
    }

    /**
     * ตรวจสอบว่าผู้ใช้สามารถจองได้หรือไม่
     */
    public function canUserBook(User $user, float $amount = 0): array
    {
        $trustScore = UserTrustScore::getOrCreateForUser($user);

        // ตรวจสอบ ban/suspend
        if (! $trustScore->canBook()) {
            return [
                'allowed' => false,
                'reason' => $trustScore->trust_level === 'banned'
                    ? 'บัญชีถูกระงับถาวร: '.$trustScore->ban_reason
                    : 'บัญชีถูกระงับชั่วคราวถึง '.$trustScore->suspended_until->format('d/m/Y H:i'),
                'requires_prepayment' => false,
            ];
        }

        // ตรวจสอบวงเงิน
        if ($trustScore->max_booking_value && $amount > $trustScore->max_booking_value) {
            return [
                'allowed' => false,
                'reason' => 'ยอดจองเกินวงเงินที่อนุญาต (สูงสุด ฿'.number_format($trustScore->max_booking_value, 2).')',
                'requires_prepayment' => true,
            ];
        }

        // ตรวจสอบจำนวน active bookings
        if ($trustScore->max_active_bookings) {
            $activeCount = ServiceBooking::where('user_id', $user->id)
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->count();

            if ($activeCount >= $trustScore->max_active_bookings) {
                return [
                    'allowed' => false,
                    'reason' => 'มีการจองที่ยังไม่เสร็จสิ้นเกินจำนวนที่อนุญาต',
                    'requires_prepayment' => false,
                ];
            }
        }

        return [
            'allowed' => true,
            'reason' => null,
            'requires_prepayment' => $trustScore->mustPrepay(),
            'trust_level' => $trustScore->trust_level,
            'trust_score' => $trustScore->trust_score,
        ];
    }

    /**
     * ตรวจสอบว่า 2 ฝ่าย block กันหรือไม่
     */
    public function areBlocked(
        ?int $userId = null,
        ?int $providerId = null,
        ?int $otherUserId = null,
        ?int $otherProviderId = null
    ): bool {
        return UserBlock::where(function ($q) use ($userId, $providerId, $otherUserId, $otherProviderId) {
            // User blocks Provider
            if ($userId && $otherProviderId) {
                $q->orWhere(function ($q2) use ($userId, $otherProviderId) {
                    $q2->where('blocker_user_id', $userId)
                        ->where('blocked_provider_id', $otherProviderId);
                });
            }

            // Provider blocks User
            if ($providerId && $otherUserId) {
                $q->orWhere(function ($q2) use ($providerId, $otherUserId) {
                    $q2->where('blocker_provider_id', $providerId)
                        ->where('blocked_user_id', $otherUserId);
                });
            }
        })->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        })->exists();
    }

    /**
     * Block ผู้ใช้/ผู้ให้บริการ
     */
    public function blockUser(
        string $blockerType,
        ?int $blockerUserId,
        ?int $blockerProviderId,
        string $blockedType,
        ?int $blockedUserId,
        ?int $blockedProviderId,
        string $reason,
        string $blockType = 'personal',
        ?Carbon $expiresAt = null
    ): UserBlock {
        return UserBlock::create([
            'blocker_user_id' => $blockerUserId,
            'blocker_provider_id' => $blockerProviderId,
            'blocker_type' => $blockerType,
            'blocked_user_id' => $blockedUserId,
            'blocked_provider_id' => $blockedProviderId,
            'blocked_type' => $blockedType,
            'block_type' => $blockType,
            'reason' => $reason,
            'expires_at' => $expiresAt,
        ]);
    }

    /**
     * ดึงประวัติตำแหน่งของ booking
     */
    public function getLocationHistory(ServiceBooking $booking, ?string $actorType = null): array
    {
        $query = ServiceBookingLocationLog::where('service_booking_id', $booking->id)
            ->orderBy('recorded_at', 'asc');

        if ($actorType) {
            $query->where('actor_type', $actorType);
        }

        $logs = $query->get();

        // สร้าง GeoJSON LineString
        $userPath = [];
        $providerPath = [];

        foreach ($logs as $log) {
            $point = [
                'lat' => $log->latitude,
                'lng' => $log->longitude,
                'time' => $log->recorded_at->toIso8601String(),
                'accuracy' => $log->accuracy,
            ];

            if ($log->actor_type === 'user') {
                $userPath[] = $point;
            } else {
                $providerPath[] = $point;
            }
        }

        return [
            'user_path' => $userPath,
            'provider_path' => $providerPath,
            'total_points' => $logs->count(),
        ];
    }
}
