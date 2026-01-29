<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UniquePaymentAmount extends Model
{
    use HasFactory;

    protected $fillable = [
        'base_amount',
        'unique_amount',
        'decimal_suffix',
        'transaction_id',
        'transaction_type',
        'status',
        'expires_at',
        'matched_at',
    ];

    protected $casts = [
        'base_amount' => 'decimal:2',
        'unique_amount' => 'decimal:2',
        'expires_at' => 'datetime',
        'matched_at' => 'datetime',
    ];

    // Scopes
    public function scopeReserved($query)
    {
        return $query->where('status', 'reserved');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'reserved')
            ->where('expires_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'reserved')
            ->where('expires_at', '<=', now());
    }

    /**
     * Generate a unique amount for a base price.
     * Adds a unique decimal suffix (0.01 - 0.99) to differentiate transactions.
     *
     * @param float $baseAmount The original price
     * @param int|null $transactionId Related transaction ID
     * @param string $transactionType Type of transaction
     * @param int $expiryMinutes How long the amount is reserved
     * @return self|null
     */
    public static function generate(
        float $baseAmount,
        ?int $transactionId = null,
        string $transactionType = 'order',
        int $expiryMinutes = 30
    ): ?self {
        // Clean up expired reservations
        static::where('status', 'reserved')
            ->where('expires_at', '<=', now())
            ->update(['status' => 'expired']);

        // Find available suffix (01-99)
        $usedSuffixes = static::where('base_amount', $baseAmount)
            ->where('status', 'reserved')
            ->where('expires_at', '>', now())
            ->pluck('decimal_suffix')
            ->toArray();

        // Generate random suffix not in use
        $availableSuffixes = array_diff(range(1, 99), $usedSuffixes);

        if (empty($availableSuffixes)) {
            // All suffixes used for this amount - try extending to different base
            return null;
        }

        // Pick random available suffix
        $suffix = $availableSuffixes[array_rand($availableSuffixes)];
        $uniqueAmount = floor($baseAmount) + ($suffix / 100);

        return static::create([
            'base_amount' => $baseAmount,
            'unique_amount' => $uniqueAmount,
            'decimal_suffix' => $suffix,
            'transaction_id' => $transactionId,
            'transaction_type' => $transactionType,
            'status' => 'reserved',
            'expires_at' => now()->addMinutes($expiryMinutes),
        ]);
    }

    /**
     * Find the matching unique amount record for a received payment.
     */
    public static function findMatch(float $amount): ?self
    {
        return static::where('unique_amount', $amount)
            ->where('status', 'reserved')
            ->where('expires_at', '>', now())
            ->first();
    }
}
