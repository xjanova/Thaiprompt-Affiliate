<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'pos_device_id',
        'user_id',
        'session_code',
        'opened_at',
        'closed_at',
        'status',
        'opening_cash',
        'closing_cash',
        'expected_cash',
        'cash_difference',
        'total_transactions',
        'total_sales',
        'total_cash_sales',
        'total_card_sales',
        'total_other_sales',
        'total_refunds',
        'total_refund_amount',
        'opening_notes',
        'closing_notes',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'opening_cash' => 'decimal:2',
        'closing_cash' => 'decimal:2',
        'expected_cash' => 'decimal:2',
        'cash_difference' => 'decimal:2',
        'total_sales' => 'decimal:2',
        'total_cash_sales' => 'decimal:2',
        'total_card_sales' => 'decimal:2',
        'total_other_sales' => 'decimal:2',
        'total_refund_amount' => 'decimal:2',
    ];

    /**
     * Relationships
     */
    public function posDevice(): BelongsTo
    {
        return $this->belongsTo(PosDevice::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PosTransaction::class);
    }

    /**
     * Scopes
     */
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    public function scopeForDevice($query, $deviceId)
    {
        return $query->where('pos_device_id', $deviceId);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Helper Methods
     */
    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed' || $this->status === 'forced_close';
    }

    public function closeSession(float $closingCash, ?string $notes = null): void
    {
        $expectedCash = $this->opening_cash + $this->total_cash_sales - $this->total_refund_amount;
        $difference = $closingCash - $expectedCash;

        $this->update([
            'closed_at' => now(),
            'status' => 'closed',
            'closing_cash' => $closingCash,
            'expected_cash' => $expectedCash,
            'cash_difference' => $difference,
            'closing_notes' => $notes,
        ]);
    }

    public function forceClose(?string $reason = null): void
    {
        $this->update([
            'closed_at' => now(),
            'status' => 'forced_close',
            'closing_notes' => $reason ?? 'Force closed by system',
        ]);
    }

    public function updateTransactionStats(PosTransaction $transaction): void
    {
        $this->increment('total_transactions');
        $this->increment('total_sales', $transaction->total_amount);

        // Update payment method totals
        switch ($transaction->payment_method) {
            case 'cash':
                $this->increment('total_cash_sales', $transaction->total_amount);
                break;
            case 'card':
                $this->increment('total_card_sales', $transaction->total_amount);
                break;
            default:
                $this->increment('total_other_sales', $transaction->total_amount);
                break;
        }

        // Handle refunds
        if ($transaction->payment_status === 'refunded') {
            $this->increment('total_refunds');
            $this->increment('total_refund_amount', $transaction->total_amount);
        }
    }

    public function generateSessionCode(): string
    {
        return 'SES-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($session) {
            if (empty($session->session_code)) {
                $session->session_code = $session->generateSessionCode();
            }
            if (empty($session->opened_at)) {
                $session->opened_at = now();
            }
        });
    }
}
