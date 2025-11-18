<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AI Core Quota Model
 *
 * เก็บข้อมูลโควต้าแบบละเอียด
 * แยกตาม period และ feature
 *
 * @property int $id
 * @property int $tenant_id Tenant เจ้าของโควต้า
 * @property int $feature_id Feature ที่มีโควต้า
 * @property string $period_type ประเภทช่วงเวลา
 * @property \Carbon\Carbon $period_start วันที่เริ่มต้น period
 * @property \Carbon\Carbon $period_end วันที่สิ้นสุด period
 * @property int $quota_limit จำนวนโควต้าที่กำหนด
 * @property int $quota_used จำนวนโควต้าที่ใช้ไป
 * @property int $quota_remaining จำนวนโควต้าที่เหลือ (computed)
 * @property bool $allow_overage อนุญาตให้เกินโควต้า
 * @property int $overage_used จำนวนที่ใช้เกินโควต้า
 * @property float|null $overage_price ราคาต่อหน่วยที่เกิน
 * @property int $bonus_quota โควต้าพิเศษ
 * @property string|null $bonus_reason เหตุผลที่ได้โควต้าพิเศษ
 * @property string $status สถานะโควต้า
 * @property \Carbon\Carbon|null $exhausted_at วันที่โควต้าหมด
 * @property bool $auto_reset รีเซ็ตอัตโนมัติ
 * @property \Carbon\Carbon|null $next_reset_at วันที่รีเซ็ตครั้งถัดไป
 * @property array|null $metadata ข้อมูลเพิ่มเติม
 */
class AICoreQuota extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ai_core_quotas';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'tenant_id',
        'feature_id',
        'period_type',
        'period_start',
        'period_end',
        'quota_limit',
        'quota_used',
        'allow_overage',
        'overage_used',
        'overage_price',
        'bonus_quota',
        'bonus_reason',
        'status',
        'exhausted_at',
        'auto_reset',
        'next_reset_at',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'quota_limit' => 'integer',
        'quota_used' => 'integer',
        'allow_overage' => 'boolean',
        'overage_used' => 'integer',
        'overage_price' => 'decimal:4',
        'bonus_quota' => 'integer',
        'auto_reset' => 'boolean',
        'exhausted_at' => 'datetime',
        'next_reset_at' => 'datetime',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * ความสัมพันธ์กับ Tenant
     *
     * @return BelongsTo
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(AICoreTenant::class, 'tenant_id');
    }

    /**
     * ความสัมพันธ์กับ Feature
     *
     * @return BelongsTo
     */
    public function feature(): BelongsTo
    {
        return $this->belongsTo(AICoreFeature::class, 'feature_id');
    }

    /**
     * Scope: เฉพาะ quota ที่ active
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('period_end', '>=', now());
    }

    /**
     * Scope: เฉพาะ quota ที่หมดแล้ว
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExhausted($query)
    {
        return $query->where('status', 'exhausted');
    }

    /**
     * Scope: กรองตาม period type
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $periodType
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePeriodType($query, string $periodType)
    {
        return $query->where('period_type', $periodType);
    }

    /**
     * ตรวจสอบว่ามีโควต้าเหลืออยู่หรือไม่
     *
     * @param int $amount จำนวนที่ต้องการใช้
     * @return bool
     */
    public function hasRemaining(int $amount = 1): bool
    {
        $totalLimit = $this->quota_limit + $this->bonus_quota;
        $remaining = $totalLimit - $this->quota_used;

        return $remaining >= $amount;
    }

    /**
     * ตรวจสอบว่าสามารถใช้ overage ได้หรือไม่
     *
     * @return bool
     */
    public function canUseOverage(): bool
    {
        return $this->allow_overage && !$this->hasRemaining();
    }

    /**
     * ใช้โควต้า
     *
     * @param int $amount จำนวนที่ต้องการใช้
     * @return bool
     */
    public function consume(int $amount = 1): bool
    {
        if ($this->hasRemaining($amount)) {
            // ใช้โควต้าปกติ
            $this->increment('quota_used', $amount);

            // ตรวจสอบว่าโควต้าหมดหรือยัง
            if (!$this->hasRemaining()) {
                $this->update([
                    'status' => 'exhausted',
                    'exhausted_at' => now(),
                ]);
            }

            return true;
        }

        if ($this->canUseOverage()) {
            // ใช้ overage
            $this->increment('overage_used', $amount);
            return true;
        }

        return false;
    }

    /**
     * รีเซ็ตโควต้า
     *
     * @return bool
     */
    public function reset(): bool
    {
        $updates = [
            'quota_used' => 0,
            'overage_used' => 0,
            'status' => 'active',
            'exhausted_at' => null,
        ];

        // คำนวณ period ใหม่
        $now = now();
        $updates['period_start'] = $now->copy()->startOfDay();

        $updates['period_end'] = match ($this->period_type) {
            'daily' => $now->copy()->endOfDay(),
            'weekly' => $now->copy()->endOfWeek(),
            'monthly' => $now->copy()->endOfMonth(),
            'yearly' => $now->copy()->endOfYear(),
            default => $this->period_end,
        };

        // คำนวณวันรีเซ็ตครั้งถัดไป
        if ($this->auto_reset) {
            $updates['next_reset_at'] = match ($this->period_type) {
                'daily' => $updates['period_end']->copy()->addDay(),
                'weekly' => $updates['period_end']->copy()->addWeek(),
                'monthly' => $updates['period_end']->copy()->addMonth(),
                'yearly' => $updates['period_end']->copy()->addYear(),
                default => null,
            };
        }

        return $this->update($updates);
    }

    /**
     * เพิ่มโควต้าพิเศษ
     *
     * @param int $amount จำนวนโควต้าที่เพิ่ม
     * @param string|null $reason เหตุผล
     * @return bool
     */
    public function addBonus(int $amount, ?string $reason = null): bool
    {
        return $this->update([
            'bonus_quota' => $this->bonus_quota + $amount,
            'bonus_reason' => $reason ?? $this->bonus_reason,
            'status' => 'active', // คืนสถานะเป็น active ถ้าเคยหมด
        ]);
    }

    /**
     * คำนวณค่าใช้จ่าย overage
     *
     * @return float
     */
    public function calculateOverageCost(): float
    {
        if (!$this->overage_price || $this->overage_used === 0) {
            return 0.0;
        }

        return $this->overage_used * $this->overage_price;
    }

    /**
     * ตรวจสอบว่า period หมดอายุหรือยัง
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->period_end->isPast();
    }

    /**
     * ตรวจสอบว่าถึงเวลารีเซ็ตหรือยัง
     *
     * @return bool
     */
    public function shouldReset(): bool
    {
        return $this->auto_reset
            && $this->next_reset_at
            && $this->next_reset_at->isPast();
    }
}
