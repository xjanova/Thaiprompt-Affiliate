<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MlmCommission extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'mlm_member_id',
        'mlm_plan_id',
        'user_id',
        'from_member_id',
        'source_type',
        'source_id',
        'type',
        'level',
        'leg',
        'pv_amount',
        'sales_amount',
        'commission_amount',
        'percentage',
        'left_leg_pv',
        'right_leg_pv',
        'pairs_count',
        'status',
        'approved_at',
        'paid_at',
        'rejected_at',
        'notes',
        'rejection_reason',
        'wallet_transaction_id',
    ];

    protected function casts(): array
    {
        return [
            'pv_amount' => 'decimal:2',
            'sales_amount' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'percentage' => 'decimal:2',
            'left_leg_pv' => 'decimal:2',
            'right_leg_pv' => 'decimal:2',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    /**
     * Get the member who earned this commission
     */
    public function member()
    {
        return $this->belongsTo(MlmMember::class, 'mlm_member_id');
    }

    /**
     * Get the plan
     */
    public function plan()
    {
        return $this->belongsTo(MlmPlan::class, 'mlm_plan_id');
    }

    /**
     * Get the beneficiary user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the member who generated this commission
     */
    public function fromMember()
    {
        return $this->belongsTo(MlmMember::class, 'from_member_id');
    }

    /**
     * Get the wallet transaction (when paid)
     */
    public function walletTransaction()
    {
        return $this->belongsTo(WalletTransaction::class);
    }

    /**
     * Get the source (polymorphic)
     */
    public function source()
    {
        return $this->morphTo('source', 'source_type', 'source_id');
    }

    /**
     * Scope for pending commissions
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for approved commissions
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for paid commissions
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Approve this commission
     */
    public function approve()
    {
        $this->update([
            'status' => 'approved',
            'approved_at' => now(),
        ]);
    }

    /**
     * Reject this commission
     */
    public function reject($reason = null)
    {
        $this->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Mark as paid
     */
    public function markAsPaid($walletTransactionId = null)
    {
        $this->update([
            'status' => 'paid',
            'paid_at' => now(),
            'wallet_transaction_id' => $walletTransactionId,
        ]);
    }
}
