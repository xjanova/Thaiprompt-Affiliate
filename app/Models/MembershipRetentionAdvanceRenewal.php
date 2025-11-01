<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MembershipRetentionAdvanceRenewal extends Model
{
    use HasFactory;

    protected $table = 'membership_retention_advance_renewals';

    protected $fillable = [
        'user_id',
        'months_advance',
        'total_cost',
        'valid_from',
        'valid_until',
        'payment_status',
        'paid_at',
        'notes',
    ];

    protected $casts = [
        'months_advance' => 'integer',
        'total_cost' => 'decimal:2',
        'valid_from' => 'date',
        'valid_until' => 'date',
        'paid_at' => 'date',
    ];

    /**
     * Get the user that owns the advance renewal
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if paid
     */
    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    /**
     * Check if active
     */
    public function isActive(): bool
    {
        $today = today();
        return $this->isPaid()
            && $this->valid_from <= $today
            && $this->valid_until >= $today;
    }

    /**
     * Scope for paid renewals
     */
    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    /**
     * Scope for active renewals
     */
    public function scopeActive($query)
    {
        $today = today();
        return $query->where('payment_status', 'paid')
            ->where('valid_from', '<=', $today)
            ->where('valid_until', '>=', $today);
    }
}
