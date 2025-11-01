<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MembershipRetentionRepair extends Model
{
    use HasFactory;

    protected $table = 'membership_retention_repairs';

    protected $fillable = [
        'user_id',
        'repair_period_month',
        'points_needed',
        'repair_cost',
        'payment_status',
        'repaired_at',
        'notes',
    ];

    protected $casts = [
        'points_needed' => 'decimal:2',
        'repair_cost' => 'decimal:2',
        'repaired_at' => 'date',
    ];

    /**
     * Get the user that owns the repair
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
     * Scope for paid repairs
     */
    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    /**
     * Scope for pending repairs
     */
    public function scopePending($query)
    {
        return $query->where('payment_status', 'pending');
    }
}
