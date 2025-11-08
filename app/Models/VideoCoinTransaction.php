<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VideoCoinTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'reference_type',
        'reference_id',
        'description',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'metadata' => 'array',
    ];

    /**
     * Get the user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the reference model
     */
    public function reference()
    {
        return $this->morphTo();
    }

    /**
     * Scope for earning transactions
     */
    public function scopeEarnings($query)
    {
        return $query->where('amount', '>', 0);
    }

    /**
     * Scope for spending transactions
     */
    public function scopeSpendings($query)
    {
        return $query->where('amount', '<', 0);
    }

    /**
     * Scope for transaction type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get formatted amount
     */
    public function getFormattedAmountAttribute()
    {
        $prefix = $this->amount > 0 ? '+' : '';
        return $prefix . number_format($this->amount, 2);
    }
}
