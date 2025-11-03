<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OwnerEarning extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_id',
        'bot_profile_id',
        'rental_id',
        'transaction_id',
        'gross_amount',
        'commission',
        'net_amount',
        'earning_date',
        'earning_month',
        'payout_status',
        'payout_date',
        'payout_method',
        'payout_reference',
        'description',
        'metadata',
    ];

    protected $casts = [
        'gross_amount' => 'decimal:4',
        'commission' => 'decimal:4',
        'net_amount' => 'decimal:4',
        'earning_date' => 'date',
        'payout_date' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the owner
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Get the bot
     */
    public function botProfile(): BelongsTo
    {
        return $this->belongsTo(AiBotProfile::class, 'bot_profile_id');
    }

    /**
     * Get the rental
     */
    public function rental(): BelongsTo
    {
        return $this->belongsTo(BotRental::class, 'rental_id');
    }

    /**
     * Get the transaction
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(RentalTransaction::class, 'transaction_id');
    }

    /**
     * Scope: Pending payouts
     */
    public function scopePending($query)
    {
        return $query->where('payout_status', 'pending');
    }

    /**
     * Scope: Processing payouts
     */
    public function scopeProcessing($query)
    {
        return $query->where('payout_status', 'processing');
    }

    /**
     * Scope: Paid payouts
     */
    public function scopePaid($query)
    {
        return $query->where('payout_status', 'paid');
    }

    /**
     * Scope: For specific owner
     */
    public function scopeForOwner($query, int $ownerId)
    {
        return $query->where('owner_id', $ownerId);
    }

    /**
     * Scope: For specific month
     */
    public function scopeForMonth($query, string $month)
    {
        return $query->where('earning_month', $month);
    }

    /**
     * Get payout status badge color
     */
    public function getPayoutStatusColorAttribute(): string
    {
        return match ($this->payout_status) {
            'paid' => 'success',
            'processing' => 'info',
            'pending' => 'warning',
            'cancelled' => 'danger',
            default => 'secondary',
        };
    }

    /**
     * Get payout status label in Thai
     */
    public function getPayoutStatusLabelAttribute(): string
    {
        return match ($this->payout_status) {
            'paid' => 'จ่ายแล้ว',
            'processing' => 'กำลังดำเนินการ',
            'pending' => 'รอจ่าย',
            'cancelled' => 'ยกเลิก',
            default => 'ไม่ทราบสถานะ',
        };
    }

    /**
     * Get commission rate
     */
    public function getCommissionRateAttribute(): float
    {
        if ($this->gross_amount == 0) {
            return 0;
        }

        return ($this->commission / $this->gross_amount) * 100;
    }
}
