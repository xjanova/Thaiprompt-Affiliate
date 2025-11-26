<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

/**
 * WalletDebt Model
 *
 * ระบบหนี้/ติดลบ - เมื่อ refund แล้วผู้ขายถอนเงินไปแล้ว
 *
 * @property int $id
 * @property int $user_id
 * @property float $original_amount
 * @property float $deducted_amount
 * @property float $remaining_amount
 * @property string $source_type
 * @property int $source_id
 * @property string $reason
 * @property string|null $admin_note
 * @property string $status
 * @property \Carbon\Carbon|null $fully_paid_at
 * @property int|null $created_by
 * @property int|null $waived_by
 * @property \Carbon\Carbon|null $waived_at
 * @property string|null $waive_reason
 * @property int $priority
 * @property array|null $metadata
 */
class WalletDebt extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'original_amount',
        'deducted_amount',
        'remaining_amount',
        'source_type',
        'source_id',
        'reason',
        'admin_note',
        'status',
        'fully_paid_at',
        'created_by',
        'waived_by',
        'waived_at',
        'waive_reason',
        'priority',
        'metadata',
    ];

    protected $casts = [
        'original_amount' => 'decimal:4',
        'deducted_amount' => 'decimal:4',
        'remaining_amount' => 'decimal:4',
        'fully_paid_at' => 'datetime',
        'waived_at' => 'datetime',
        'priority' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * สถานะ
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_PAID = 'paid';
    const STATUS_WAIVED = 'waived';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * ผู้ที่ติดหนี้
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ผู้สร้างหนี้ (Admin)
     */
    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * ผู้ยกเว้นหนี้
     */
    public function waivedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'waived_by');
    }

    /**
     * หนี้ที่ยังค้าง
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * หนี้ที่ชำระแล้ว
     */
    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    /**
     * ของ user
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * เรียงตาม priority
     */
    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'asc')->orderBy('created_at', 'asc');
    }

    /**
     * สร้างหนี้ใหม่
     */
    public static function createDebt(
        int $userId,
        float $amount,
        string $sourceType,
        int $sourceId,
        string $reason,
        ?int $createdBy = null,
        int $priority = 1,
        array $metadata = []
    ): self {
        return static::create([
            'user_id' => $userId,
            'original_amount' => $amount,
            'deducted_amount' => 0,
            'remaining_amount' => $amount,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'reason' => $reason,
            'status' => self::STATUS_ACTIVE,
            'created_by' => $createdBy,
            'priority' => $priority,
            'metadata' => $metadata,
        ]);
    }

    /**
     * หักหนี้
     *
     * @param float $amount จำนวนที่หัก
     * @param int|null $fromEarningId หักจาก earning ไหน
     * @return float จำนวนที่หักได้จริง (อาจน้อยกว่าที่ขอหักถ้าหนี้เหลือน้อยกว่า)
     */
    public function deduct(float $amount, ?int $fromEarningId = null): float
    {
        return DB::transaction(function () use ($amount, $fromEarningId) {
            // ถ้าหนี้ไม่ active ไม่หัก
            if ($this->status !== self::STATUS_ACTIVE) {
                return 0;
            }

            // คำนวณจำนวนที่หักได้จริง
            $actualDeduction = min($amount, $this->remaining_amount);

            // อัพเดทยอดหนี้
            $this->deducted_amount += $actualDeduction;
            $this->remaining_amount -= $actualDeduction;

            // บันทึกประวัติการหัก
            $history = $this->metadata['deduction_history'] ?? [];
            $history[] = [
                'date' => now()->toIso8601String(),
                'amount' => $actualDeduction,
                'from_earning_id' => $fromEarningId,
            ];
            $this->metadata = array_merge($this->metadata ?? [], ['deduction_history' => $history]);

            // ถ้าหนี้หมดแล้ว
            if ($this->remaining_amount <= 0) {
                $this->status = self::STATUS_PAID;
                $this->fully_paid_at = now();
            }

            $this->save();

            return $actualDeduction;
        });
    }

    /**
     * หักหนี้ทั้งหมดของ user จาก earning
     *
     * @param int $userId
     * @param float $amount จำนวนเงินที่มีให้หัก
     * @param int|null $fromEarningId
     * @return array ['total_deducted' => float, 'remaining' => float]
     */
    public static function deductAllDebts(int $userId, float $amount, ?int $fromEarningId = null): array
    {
        $totalDeducted = 0;
        $remaining = $amount;

        // ดึงหนี้ทั้งหมดที่ยังค้าง เรียงตาม priority
        $debts = static::forUser($userId)->active()->byPriority()->get();

        foreach ($debts as $debt) {
            if ($remaining <= 0) {
                break;
            }

            $deducted = $debt->deduct($remaining, $fromEarningId);
            $totalDeducted += $deducted;
            $remaining -= $deducted;
        }

        return [
            'total_deducted' => $totalDeducted,
            'remaining' => $remaining,
        ];
    }

    /**
     * ยกเว้นหนี้
     */
    public function waive(int $waivedBy, string $reason): bool
    {
        return $this->update([
            'status' => self::STATUS_WAIVED,
            'waived_by' => $waivedBy,
            'waived_at' => now(),
            'waive_reason' => $reason,
        ]);
    }

    /**
     * ยกเลิก
     */
    public function cancel(): bool
    {
        return $this->update(['status' => self::STATUS_CANCELLED]);
    }

    /**
     * ดึงยอดหนี้รวมของ user
     */
    public static function getTotalDebtForUser(int $userId): float
    {
        return static::forUser($userId)->active()->sum('remaining_amount');
    }

    /**
     * เปอร์เซ็นต์ที่ชำระแล้ว
     */
    public function getPaidPercentageAttribute(): float
    {
        if ($this->original_amount == 0) {
            return 100;
        }

        return ($this->deducted_amount / $this->original_amount) * 100;
    }

    /**
     * สีตามสถานะ
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => 'red',
            self::STATUS_PAID => 'green',
            self::STATUS_WAIVED => 'yellow',
            self::STATUS_CANCELLED => 'gray',
            default => 'gray',
        };
    }

    /**
     * Label ไทยตามสถานะ
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => 'ค้างชำระ',
            self::STATUS_PAID => 'ชำระแล้ว',
            self::STATUS_WAIVED => 'ยกเว้น',
            self::STATUS_CANCELLED => 'ยกเลิก',
            default => 'ไม่ทราบ',
        };
    }
}
