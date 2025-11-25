<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * UserTrustScore Model
 *
 * จัดการคะแนนความน่าเชื่อถือของผู้ใช้/ผู้ให้บริการ
 * ใช้สำหรับตัดสินใจ:
 * - ต้องจ่ายล่วงหน้าหรือไม่
 * - วงเงินสูงสุดต่อครั้ง
 * - การจำกัดการใช้งาน
 *
 * @property int $id
 * @property int|null $user_id
 * @property int|null $provider_id
 * @property string $entity_type
 * @property float $trust_score
 * @property string $trust_level
 */
class UserTrustScore extends Model
{
    protected $fillable = [
        'user_id',
        'provider_id',
        'entity_type',
        'trust_score',
        'payment_score',
        'cancellation_score',
        'punctuality_score',
        'behavior_score',
        'verification_score',
        'total_bookings',
        'completed_bookings',
        'cancelled_bookings',
        'no_show_count',
        'late_cancellation_count',
        'dispute_count',
        'dispute_lost_count',
        'warnings_count',
        'trust_level',
        'requires_prepayment',
        'requires_deposit',
        'max_booking_value',
        'max_active_bookings',
        'suspended_until',
        'suspension_reason',
        'banned_at',
        'ban_reason',
    ];

    protected $casts = [
        'trust_score' => 'decimal:2',
        'payment_score' => 'decimal:2',
        'cancellation_score' => 'decimal:2',
        'punctuality_score' => 'decimal:2',
        'behavior_score' => 'decimal:2',
        'verification_score' => 'decimal:2',
        'requires_prepayment' => 'boolean',
        'requires_deposit' => 'boolean',
        'max_booking_value' => 'decimal:2',
        'suspended_until' => 'datetime',
        'banned_at' => 'datetime',
    ];

    /**
     * ระดับความน่าเชื่อถือ
     */
    public const TRUST_LEVELS = [
        'new' => ['label' => 'ใหม่', 'color' => 'gray', 'min_score' => 0],
        'standard' => ['label' => 'ปกติ', 'color' => 'blue', 'min_score' => 60],
        'trusted' => ['label' => 'น่าเชื่อถือ', 'color' => 'green', 'min_score' => 80],
        'verified' => ['label' => 'ยืนยันแล้ว', 'color' => 'purple', 'min_score' => 90],
        'warning' => ['label' => 'เตือน', 'color' => 'yellow', 'min_score' => 40],
        'restricted' => ['label' => 'จำกัด', 'color' => 'orange', 'min_score' => 20],
        'suspended' => ['label' => 'ระงับชั่วคราว', 'color' => 'red', 'min_score' => 0],
        'banned' => ['label' => 'แบน', 'color' => 'black', 'min_score' => 0],
    ];

    /**
     * น้ำหนักของแต่ละคะแนนย่อย
     */
    public const SCORE_WEIGHTS = [
        'payment_score' => 0.30,
        'cancellation_score' => 0.25,
        'punctuality_score' => 0.15,
        'behavior_score' => 0.20,
        'verification_score' => 0.10,
    ];

    /**
     * ความสัมพันธ์กับ User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ความสัมพันธ์กับ Provider
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(ServiceProvider::class, 'provider_id');
    }

    /**
     * ดึง/สร้าง Trust Score สำหรับ User
     */
    public static function getOrCreateForUser(User $user): self
    {
        return static::firstOrCreate(
            ['user_id' => $user->id, 'entity_type' => 'user'],
            [
                'trust_score' => 80.00,
                'trust_level' => 'new',
            ]
        );
    }

    /**
     * ดึง/สร้าง Trust Score สำหรับ Provider
     */
    public static function getOrCreateForProvider(ServiceProvider $provider): self
    {
        return static::firstOrCreate(
            ['provider_id' => $provider->id, 'entity_type' => 'provider'],
            [
                'trust_score' => 80.00,
                'trust_level' => 'new',
            ]
        );
    }

    /**
     * คำนวณคะแนนรวมใหม่
     */
    public function recalculateTrustScore(): void
    {
        $score = 0;
        foreach (self::SCORE_WEIGHTS as $field => $weight) {
            $score += ($this->$field ?? 100) * $weight;
        }

        $this->trust_score = round($score, 2);
        $this->trust_level = $this->determineTrustLevel();
        $this->updateRestrictions();
        $this->save();
    }

    /**
     * กำหนดระดับความน่าเชื่อถือ
     */
    protected function determineTrustLevel(): string
    {
        if ($this->banned_at) {
            return 'banned';
        }

        if ($this->suspended_until && $this->suspended_until->isFuture()) {
            return 'suspended';
        }

        if ($this->total_bookings < 5) {
            return 'new';
        }

        if ($this->trust_score >= 90 && $this->verification_score >= 80) {
            return 'verified';
        }

        if ($this->trust_score >= 80) {
            return 'trusted';
        }

        if ($this->trust_score >= 60) {
            return 'standard';
        }

        if ($this->trust_score >= 40) {
            return 'warning';
        }

        return 'restricted';
    }

    /**
     * อัพเดทข้อจำกัดตามคะแนน
     */
    protected function updateRestrictions(): void
    {
        $level = $this->trust_level;

        switch ($level) {
            case 'verified':
            case 'trusted':
                $this->requires_prepayment = false;
                $this->requires_deposit = false;
                $this->max_booking_value = null;
                $this->max_active_bookings = null;
                break;

            case 'standard':
                $this->requires_prepayment = false;
                $this->requires_deposit = false;
                $this->max_booking_value = 50000;
                $this->max_active_bookings = 5;
                break;

            case 'new':
                $this->requires_prepayment = false;
                $this->requires_deposit = false;
                $this->max_booking_value = 10000;
                $this->max_active_bookings = 2;
                break;

            case 'warning':
                $this->requires_prepayment = true;
                $this->requires_deposit = false;
                $this->max_booking_value = 5000;
                $this->max_active_bookings = 1;
                break;

            case 'restricted':
                $this->requires_prepayment = true;
                $this->requires_deposit = true;
                $this->max_booking_value = 2000;
                $this->max_active_bookings = 1;
                break;

            case 'suspended':
            case 'banned':
                $this->max_booking_value = 0;
                $this->max_active_bookings = 0;
                break;
        }
    }

    /**
     * หักคะแนนเมื่อยกเลิก
     */
    public function penalizeForCancellation(bool $isLate = false): void
    {
        $deduction = $isLate ? 10 : 5;

        $this->cancellation_score = max(0, $this->cancellation_score - $deduction);
        $this->cancelled_bookings++;

        if ($isLate) {
            $this->late_cancellation_count++;
        }

        $this->recalculateTrustScore();
    }

    /**
     * หักคะแนนเมื่อไม่มา (No Show)
     */
    public function penalizeForNoShow(): void
    {
        $this->cancellation_score = max(0, $this->cancellation_score - 20);
        $this->punctuality_score = max(0, $this->punctuality_score - 15);
        $this->no_show_count++;

        $this->recalculateTrustScore();
    }

    /**
     * หักคะแนนเมื่อไม่จ่ายเงิน
     */
    public function penalizeForPaymentDefault(): void
    {
        $this->payment_score = max(0, $this->payment_score - 25);
        $this->recalculateTrustScore();
    }

    /**
     * หักคะแนนเมื่อแพ้ข้อพิพาท
     */
    public function penalizeForDisputeLost(): void
    {
        $this->behavior_score = max(0, $this->behavior_score - 15);
        $this->dispute_count++;
        $this->dispute_lost_count++;

        $this->recalculateTrustScore();
    }

    /**
     * เพิ่มคะแนนเมื่อทำงานสำเร็จ
     */
    public function rewardForCompletion(): void
    {
        $this->completed_bookings++;
        $this->total_bookings++;

        // เพิ่มคะแนนทีละน้อย
        $this->payment_score = min(100, $this->payment_score + 0.5);
        $this->cancellation_score = min(100, $this->cancellation_score + 0.5);
        $this->punctuality_score = min(100, $this->punctuality_score + 0.3);

        $this->recalculateTrustScore();
    }

    /**
     * ระงับการใช้งานชั่วคราว
     */
    public function suspend(int $days, string $reason): void
    {
        $this->suspended_until = now()->addDays($days);
        $this->suspension_reason = $reason;
        $this->trust_level = 'suspended';
        $this->save();
    }

    /**
     * แบนถาวร
     */
    public function ban(string $reason): void
    {
        $this->banned_at = now();
        $this->ban_reason = $reason;
        $this->trust_level = 'banned';
        $this->save();
    }

    /**
     * ปลดแบน
     */
    public function unban(): void
    {
        $this->banned_at = null;
        $this->ban_reason = null;
        $this->suspended_until = null;
        $this->suspension_reason = null;
        $this->recalculateTrustScore();
    }

    /**
     * ตรวจสอบว่าสามารถจองได้หรือไม่
     */
    public function canBook(): bool
    {
        if ($this->trust_level === 'banned') {
            return false;
        }

        if ($this->trust_level === 'suspended' && $this->suspended_until?->isFuture()) {
            return false;
        }

        return true;
    }

    /**
     * ตรวจสอบว่าต้องจ่ายล่วงหน้าหรือไม่
     */
    public function mustPrepay(): bool
    {
        return $this->requires_prepayment || $this->requires_deposit;
    }

    /**
     * ดึง label ระดับความน่าเชื่อถือ
     */
    public function getTrustLevelLabel(): string
    {
        return self::TRUST_LEVELS[$this->trust_level]['label'] ?? $this->trust_level;
    }

    /**
     * ดึงสีของระดับ
     */
    public function getTrustLevelColor(): string
    {
        return self::TRUST_LEVELS[$this->trust_level]['color'] ?? 'gray';
    }
}
