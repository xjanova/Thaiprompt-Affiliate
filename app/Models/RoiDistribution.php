<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoiDistribution extends Model
{
    use HasFactory;

    protected $fillable = [
        'distribution_number',
        'staking_position_id',
        'user_id',
        'wallet_id',
        'wallet_transaction_id',
        'roi_amount',
        'base_amount',
        'bonus_amount',
        'rank_bonus',
        'roi_rate',
        'distribution_type',
        'status',
        'distribution_date',
        'processed_at',
        'completed_at',
        'day_number',
        'total_days',
        'accumulated_roi',
        'error_message',
        'retry_count',
        'last_retry_at',
        'is_compounded',
        'new_position_id',
        'metadata',
        'notes',
    ];

    protected $casts = [
        'roi_amount' => 'decimal:2',
        'base_amount' => 'decimal:2',
        'bonus_amount' => 'decimal:2',
        'rank_bonus' => 'decimal:2',
        'roi_rate' => 'decimal:4',
        'accumulated_roi' => 'decimal:2',
        'distribution_date' => 'date',
        'processed_at' => 'datetime',
        'completed_at' => 'datetime',
        'last_retry_at' => 'datetime',
        'is_compounded' => 'boolean',
        'metadata' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($distribution) {
            if (!$distribution->distribution_number) {
                $distribution->distribution_number = 'ROI' . strtoupper(uniqid());
            }
        });
    }

    /**
     * Get the staking position
     */
    public function stakingPosition(): BelongsTo
    {
        return $this->belongsTo(StakingPosition::class);
    }

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the wallet
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Get the wallet transaction
     */
    public function walletTransaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class);
    }

    /**
     * Get the new position (if compounded)
     */
    public function newPosition(): BelongsTo
    {
        return $this->belongsTo(StakingPosition::class, 'new_position_id');
    }

    /**
     * Scope for pending distributions
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for completed distributions
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for failed distributions
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope for distributions ready to process
     */
    public function scopeReadyToProcess($query)
    {
        return $query->where('status', 'pending')
            ->where('distribution_date', '<=', now()->toDateString());
    }

    /**
     * Scope for today's distributions
     */
    public function scopeToday($query)
    {
        return $query->whereDate('distribution_date', now()->toDateString());
    }

    /**
     * Scope for a specific user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for a specific staking position
     */
    public function scopeForPosition($query, $positionId)
    {
        return $query->where('staking_position_id', $positionId);
    }

    /**
     * Mark as processing
     */
    public function markAsProcessing(): void
    {
        $this->update([
            'status' => 'processing',
            'processed_at' => now(),
        ]);
    }

    /**
     * Mark as completed
     */
    public function markAsCompleted(?int $walletTransactionId = null): void
    {
        $data = [
            'status' => 'completed',
            'completed_at' => now(),
        ];

        if ($walletTransactionId) {
            $data['wallet_transaction_id'] = $walletTransactionId;
        }

        $this->update($data);
    }

    /**
     * Mark as failed
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Increment retry count
     */
    public function incrementRetry(): void
    {
        $this->increment('retry_count');
        $this->update([
            'last_retry_at' => now(),
        ]);
    }

    /**
     * Check if can retry
     */
    public function canRetry(int $maxRetries = 3): bool
    {
        return $this->status === 'failed' && $this->retry_count < $maxRetries;
    }

    /**
     * Get display status
     */
    public function getDisplayStatusAttribute(): string
    {
        return match($this->status) {
            'pending' => 'รอจ่าย',
            'processing' => 'กำลังจ่าย',
            'completed' => 'จ่ายแล้ว',
            'failed' => 'ล้มเหลว',
            'cancelled' => 'ยกเลิก',
            default => $this->status,
        };
    }

    /**
     * Get display type
     */
    public function getDisplayTypeAttribute(): string
    {
        return match($this->distribution_type) {
            'daily' => 'รายวัน',
            'weekly' => 'รายสัปดาห์',
            'monthly' => 'รายเดือน',
            'maturity' => 'ครบกำหนด',
            'compound' => 'รีลงทุน',
            'bonus' => 'โบนัส',
            default => $this->distribution_type,
        };
    }

    /**
     * Check if this is a compound distribution
     */
    public function isCompound(): bool
    {
        return $this->is_compounded || $this->distribution_type === 'compound';
    }

    /**
     * Check if overdue
     */
    public function isOverdue(): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }

        return now()->toDateString() > $this->distribution_date->toDateString();
    }
}
