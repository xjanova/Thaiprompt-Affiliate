<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RankPromotion extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'from_rank_id',
        'to_rank_id',
        'promotion_type',
        'status',
        'purchase_amount',
        'payment_method',
        'transaction_id',
        'approved_by',
        'approved_at',
        'approval_notes',
        'rejection_reason',
        'rejected_at',
        'requirements_snapshot',
        'expires_at',
        'activated_at',
        'notes',
    ];

    protected $casts = [
        'purchase_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'expires_at' => 'datetime',
        'activated_at' => 'datetime',
        'requirements_snapshot' => 'array',
    ];

    /**
     * Get the user that owns this promotion
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the rank promoted from
     */
    public function fromRank(): BelongsTo
    {
        return $this->belongsTo(Rank::class, 'from_rank_id');
    }

    /**
     * Get the rank promoted to
     */
    public function toRank(): BelongsTo
    {
        return $this->belongsTo(Rank::class, 'to_rank_id');
    }

    /**
     * Get the admin who approved
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Scope for pending promotions
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for approved promotions
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for active promotions
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for auto promotions
     */
    public function scopeAuto($query)
    {
        return $query->where('promotion_type', 'auto');
    }

    /**
     * Scope for manual promotions
     */
    public function scopeManual($query)
    {
        return $query->where('promotion_type', 'manual');
    }

    /**
     * Scope for purchase promotions
     */
    public function scopePurchase($query)
    {
        return $query->where('promotion_type', 'purchase');
    }

    /**
     * Approve this promotion
     */
    public function approve(User $admin, ?string $notes = null): bool
    {
        $this->status = 'approved';
        $this->approved_by = $admin->id;
        $this->approved_at = now();
        $this->approval_notes = $notes;

        if ($this->save()) {
            // Update user's rank
            $this->user->current_rank_id = $this->to_rank_id;
            $this->user->rank_updated_at = now();
            $this->user->save();

            // Update MLM member's rank (if exists)
            $mlmMember = $this->user->mlmMembers()->first();
            if ($mlmMember) {
                $mlmMember->rank_id = $this->to_rank_id;
                $mlmMember->save();
            }

            return true;
        }

        return false;
    }

    /**
     * Reject this promotion
     */
    public function reject(?string $reason = null): bool
    {
        $this->status = 'rejected';
        $this->rejection_reason = $reason;
        $this->rejected_at = now();

        return $this->save();
    }

    /**
     * Activate this promotion
     */
    public function activate(): bool
    {
        $this->status = 'active';
        $this->activated_at = now();

        if ($this->save()) {
            // Update user's rank
            $this->user->current_rank_id = $this->to_rank_id;
            $this->user->rank_updated_at = now();
            $this->user->save();

            // Update MLM member's rank (if exists)
            $mlmMember = $this->user->mlmMembers()->first();
            if ($mlmMember) {
                $mlmMember->rank_id = $this->to_rank_id;
                $mlmMember->save();
            }

            return true;
        }

        return false;
    }

    /**
     * Check if promotion is expired
     */
    public function isExpired(): bool
    {
        if (!$this->expires_at) {
            return false;
        }

        return now()->isAfter($this->expires_at);
    }
}
