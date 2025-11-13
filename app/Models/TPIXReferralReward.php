<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TPIXReferralReward extends Model
{
    use HasFactory;

    protected $table = 'tpix_referral_rewards';

    protected $fillable = [
        'referral_code_id',
        'referral_use_id',
        'user_id',
        'reward_type',
        'token_id',
        'amount',
        'status',
        'paid_at',
        'tx_hash',
        'metadata',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Relationships
     */
    public function referralCode()
    {
        return $this->belongsTo(TPIXReferralCode::class, 'referral_code_id');
    }

    public function referralUse()
    {
        return $this->belongsTo(TPIXReferralUse::class, 'referral_use_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function token()
    {
        return $this->belongsTo(TPIXToken::class, 'token_id');
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeReferrer($query)
    {
        return $query->where('reward_type', 'referrer');
    }

    public function scopeReferee($query)
    {
        return $query->where('reward_type', 'referee');
    }

    /**
     * Helpers
     */
    public function markAsPaid(string $txHash)
    {
        $this->update([
            'status' => 'paid',
            'paid_at' => now(),
            'tx_hash' => $txHash,
        ]);
    }

    public function markAsFailed(string $reason)
    {
        $this->update([
            'status' => 'failed',
            'metadata' => array_merge($this->metadata ?? [], ['failure_reason' => $reason]),
        ]);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
}
