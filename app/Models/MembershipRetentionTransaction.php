<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MembershipRetentionTransaction extends Model
{
    use HasFactory;

    protected $table = 'membership_retention_transactions';

    protected $fillable = [
        'user_id',
        'commission_id',
        'transaction_type',
        'points',
        'period_month',
        'description',
        'metadata',
    ];

    protected $casts = [
        'points' => 'decimal:2',
        'metadata' => 'array',
    ];

    /**
     * Get the user that owns the transaction
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the MLM commission associated with the transaction
     *
     * อ้างอิงไปยัง mlm_commissions table
     */
    public function commission()
    {
        return $this->belongsTo(MlmCommission::class, 'commission_id');
    }

    /**
     * Scope for specific period
     */
    public function scopeForPeriod($query, string $periodMonth)
    {
        return $query->where('period_month', $periodMonth);
    }

    /**
     * Scope for specific transaction type
     */
    public function scopeType($query, string $type)
    {
        return $query->where('transaction_type', $type);
    }
}
