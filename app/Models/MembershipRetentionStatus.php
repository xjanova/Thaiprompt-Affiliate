<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MembershipRetentionStatus extends Model
{
    use HasFactory;

    protected $table = 'membership_retention_status';

    protected $fillable = [
        'user_id',
        'membership_start_date',
        'status',
        'current_points',
        'required_points',
        'period_start',
        'period_end',
        'next_renewal_date',
        'consecutive_months',
        'last_active_date',
        // Auto-renewal settings
        'auto_renewal_enabled',
        'auto_renewal_source',
        'auto_renewal_priority',
        'auto_renewal_max_amount',
        'auto_renewal_months',
        'auto_renewal_notify_before_days',
        'auto_renewal_last_executed',
        'auto_renewal_failed_count',
        'auto_renewal_paused_until',
    ];

    protected $casts = [
        'current_points' => 'decimal:2',
        'required_points' => 'decimal:2',
        'membership_start_date' => 'date',
        'period_start' => 'date',
        'period_end' => 'date',
        'next_renewal_date' => 'date',
        'consecutive_months' => 'integer',
        'last_active_date' => 'date',
        // Auto-renewal casts
        'auto_renewal_enabled' => 'boolean',
        'auto_renewal_max_amount' => 'decimal:2',
        'auto_renewal_months' => 'integer',
        'auto_renewal_notify_before_days' => 'integer',
        'auto_renewal_last_executed' => 'datetime',
        'auto_renewal_failed_count' => 'integer',
        'auto_renewal_paused_until' => 'date',
    ];

    /**
     * Get the user that owns the retention status
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get retention history records
     */
    public function history()
    {
        return $this->hasMany(MembershipRetentionHistory::class, 'user_id', 'user_id');
    }

    /**
     * Get retention transactions
     */
    public function transactions()
    {
        return $this->hasMany(MembershipRetentionTransaction::class, 'user_id', 'user_id');
    }

    /**
     * Check if status is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if status is expired
     */
    public function isExpired(): bool
    {
        return $this->status === 'expired';
    }

    /**
     * Get days remaining until expiry
     */
    public function daysRemaining(): int
    {
        if (! $this->next_renewal_date) {
            return 0;
        }

        $days = Carbon::today()->diffInDays($this->next_renewal_date, false);

        return max(0, (int) $days);
    }

    /**
     * Get percentage of points achieved
     */
    public function getPointsPercentage(): float
    {
        if ($this->required_points <= 0) {
            return 0;
        }

        return min(100, ($this->current_points / $this->required_points) * 100);
    }

    /**
     * Get remaining points needed
     */
    public function getRemainingPoints(): float
    {
        return max(0, $this->required_points - $this->current_points);
    }

    /**
     * Get health status color
     */
    public function getHealthColor(): string
    {
        $days = $this->daysRemaining();

        if ($days > 14) {
            return 'green';
        } elseif ($days > 7) {
            return 'yellow';
        } elseif ($days > 3) {
            return 'orange';
        } else {
            return 'red';
        }
    }

    /**
     * Get health status percentage (for progress bar)
     */
    public function getHealthPercentage(): float
    {
        if (! $this->period_start || ! $this->period_end) {
            return 0;
        }

        $totalDays = $this->period_start->diffInDays($this->period_end);
        $daysElapsed = $this->period_start->diffInDays(Carbon::today());

        if ($totalDays <= 0) {
            return 100;
        }

        return min(100, ($daysElapsed / $totalDays) * 100);
    }

    /**
     * Scope for active statuses
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for expired statuses
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    /**
     * Scope for expiring soon
     */
    public function scopeExpiringSoon($query, int $days = 7)
    {
        $targetDate = Carbon::today()->addDays($days);

        return $query->where('status', 'active')
            ->whereNotNull('next_renewal_date')
            ->where('next_renewal_date', '<=', $targetDate);
    }

    // ============================================================
    // Auto-Renewal Helper Methods
    // ============================================================

    /**
     * ตรวจสอบว่าเปิด Auto-renewal หรือไม่
     */
    public function isAutoRenewalEnabled(): bool
    {
        return $this->auto_renewal_enabled ?? false;
    }

    /**
     * ตรวจสอบว่า Auto-renewal ถูก pause อยู่หรือไม่
     */
    public function isAutoRenewalPaused(): bool
    {
        if (! $this->auto_renewal_paused_until) {
            return false;
        }

        return Carbon::today()->lte($this->auto_renewal_paused_until);
    }

    /**
     * ตรวจสอบว่าสามารถทำ Auto-renewal ได้หรือไม่
     */
    public function canAutoRenew(): bool
    {
        // ต้องเปิด auto-renewal
        if (! $this->isAutoRenewalEnabled()) {
            return false;
        }

        // ต้องไม่ถูก pause
        if ($this->isAutoRenewalPaused()) {
            return false;
        }

        // ต้องไม่ล้มเหลวเกิน 3 ครั้ง
        if ($this->auto_renewal_failed_count >= 3) {
            return false;
        }

        return true;
    }

    /**
     * ดึงคำอธิบายแหล่งเงิน Auto-renewal
     */
    public function getAutoRenewalSourceLabel(): string
    {
        return match ($this->auto_renewal_source) {
            'wallet' => 'กระเป๋าเงิน',
            'commission' => 'คอมมิชชั่น',
            'both' => 'ทั้งสองแหล่ง',
            default => 'กระเป๋าเงิน',
        };
    }

    /**
     * ดึงคำอธิบายลำดับการหัก Auto-renewal
     */
    public function getAutoRenewalPriorityLabel(): string
    {
        return match ($this->auto_renewal_priority) {
            'wallet_first' => 'หัก Wallet ก่อน',
            'commission_first' => 'หัก Commission ก่อน',
            default => 'หัก Wallet ก่อน',
        };
    }

    /**
     * ดึงสถานะ Auto-renewal เป็นข้อความ
     */
    public function getAutoRenewalStatusText(): string
    {
        if (! $this->isAutoRenewalEnabled()) {
            return 'ปิดอยู่';
        }

        if ($this->isAutoRenewalPaused()) {
            return 'หยุดชั่วคราวถึง '.$this->auto_renewal_paused_until->format('d/m/Y');
        }

        if ($this->auto_renewal_failed_count >= 3) {
            return 'ระงับ (ล้มเหลว 3 ครั้ง)';
        }

        return 'เปิดใช้งาน';
    }

    /**
     * ดึงสีสถานะ Auto-renewal
     */
    public function getAutoRenewalStatusColor(): string
    {
        if (! $this->isAutoRenewalEnabled()) {
            return 'gray';
        }

        if ($this->isAutoRenewalPaused() || $this->auto_renewal_failed_count >= 3) {
            return 'yellow';
        }

        return 'green';
    }

    /**
     * Scope สำหรับ records ที่เปิด auto-renewal
     */
    public function scopeAutoRenewalEnabled($query)
    {
        return $query->where('auto_renewal_enabled', true);
    }

    /**
     * Scope สำหรับ records ที่พร้อม auto-renew
     */
    public function scopeReadyForAutoRenewal($query)
    {
        return $query->where('auto_renewal_enabled', true)
            ->where(function ($q) {
                $q->whereNull('auto_renewal_paused_until')
                    ->orWhere('auto_renewal_paused_until', '<', Carbon::today());
            })
            ->where('auto_renewal_failed_count', '<', 3);
    }
}
