<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Wallet extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'balance',
        'currency',
        'wallet_address',
        'pin_hash',
        'two_factor_enabled',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'status',
        'last_transaction_at',
        'total_income',
        'total_expense',
        'failed_attempts',
        'locked_until',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'total_income' => 'decimal:2',
        'total_expense' => 'decimal:2',
        'two_factor_enabled' => 'boolean',
        'failed_attempts' => 'integer',
        'last_transaction_at' => 'datetime',
        'locked_until' => 'datetime',
    ];

    protected $hidden = [
        'pin_hash',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($wallet) {
            if (empty($wallet->wallet_address)) {
                $wallet->wallet_address = 'TPW' . strtoupper(Str::random(16));
            }
        });
    }

    /**
     * Get the user that owns the wallet
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all transactions for this wallet
     */
    public function transactions()
    {
        return $this->hasMany(WalletTransaction::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get all logs for this wallet
     */
    public function logs()
    {
        return $this->hasMany(WalletLog::class)->orderBy('created_at', 'desc');
    }

    /**
     * Check if wallet is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active' &&
               (!$this->locked_until || $this->locked_until->isPast());
    }

    /**
     * Check if wallet is locked
     */
    public function isLocked(): bool
    {
        return $this->status === 'locked' ||
               ($this->locked_until && $this->locked_until->isFuture());
    }

    /**
     * Lock wallet temporarily
     */
    public function lockTemporary(int $minutes = 30): void
    {
        $this->update([
            'locked_until' => now()->addMinutes($minutes),
        ]);
    }

    /**
     * Lock wallet permanently
     */
    public function lockPermanent(): void
    {
        $this->update([
            'status' => 'locked',
            'locked_until' => null,
        ]);
    }

    /**
     * Unlock wallet
     */
    public function unlock(): void
    {
        $this->update([
            'status' => 'active',
            'locked_until' => null,
            'failed_attempts' => 0,
        ]);
    }

    /**
     * Increment failed attempts
     */
    public function incrementFailedAttempts(): void
    {
        $this->increment('failed_attempts');

        // Lock wallet after 5 failed attempts
        if ($this->failed_attempts >= 5) {
            $this->lockTemporary(30);
        }
    }

    /**
     * Reset failed attempts
     */
    public function resetFailedAttempts(): void
    {
        $this->update(['failed_attempts' => 0]);
    }

    /**
     * Check if PIN is set
     */
    public function hasPIN(): bool
    {
        return !empty($this->pin_hash);
    }

    /**
     * Verify PIN
     */
    public function verifyPIN(string $pin): bool
    {
        if (!$this->hasPIN()) {
            return false;
        }

        return password_verify($pin, $this->pin_hash);
    }

    /**
     * Set PIN
     */
    public function setPIN(string $pin): void
    {
        $this->update([
            'pin_hash' => bcrypt($pin),
        ]);
    }

    /**
     * Format balance with currency
     */
    public function getFormattedBalanceAttribute(): string
    {
        return number_format($this->balance, 2) . ' ' . $this->currency;
    }

    /**
     * Format total income with currency
     */
    public function getFormattedTotalIncomeAttribute(): string
    {
        return number_format($this->total_income, 2) . ' ' . $this->currency;
    }

    /**
     * Format total expense with currency
     */
    public function getFormattedTotalExpenseAttribute(): string
    {
        return number_format($this->total_expense, 2) . ' ' . $this->currency;
    }
}
