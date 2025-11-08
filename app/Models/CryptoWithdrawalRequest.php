<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CryptoWithdrawalRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'crypto_wallet_id',
        'crypto_currency_id',
        'request_id',
        'to_address',
        'network',
        'amount',
        'network_fee',
        'platform_fee',
        'total_fee',
        'net_amount',
        'estimated_gas_price',
        'estimated_gas_limit',
        'status',
        'requires_admin_approval',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'crypto_transaction_id',
        'verification_code',
        'verification_sent_at',
        'verified_at',
        'two_factor_verified',
        'address_validated',
        'address_validation_result',
        'user_note',
        'admin_note',
        'is_suspicious',
        'risk_flags',
        'risk_score',
        'processed_at',
        'completed_at',
        'failed_at',
        'error_message',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'amount' => 'decimal:18',
        'network_fee' => 'decimal:18',
        'platform_fee' => 'decimal:18',
        'total_fee' => 'decimal:18',
        'net_amount' => 'decimal:18',
        'requires_admin_approval' => 'boolean',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'verification_sent_at' => 'datetime',
        'verified_at' => 'datetime',
        'two_factor_verified' => 'boolean',
        'address_validated' => 'boolean',
        'is_suspicious' => 'boolean',
        'risk_score' => 'decimal:2',
        'processed_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($request) {
            if (empty($request->request_id)) {
                $request->request_id = 'CWDR' . strtoupper(Str::random(20));
            }
        });
    }

    /**
     * Check if pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if approved
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if rejected
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Check if completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if verified
     */
    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    /**
     * Check if 2FA verified
     */
    public function is2FAVerified(): bool
    {
        return $this->two_factor_verified === true;
    }

    /**
     * Generate verification code
     */
    public function generateVerificationCode(): string
    {
        $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $this->update([
            'verification_code' => $code,
            'verification_sent_at' => now(),
        ]);

        return $code;
    }

    /**
     * Verify code
     */
    public function verifyCode(string $code): bool
    {
        if ($this->verification_code !== $code) {
            return false;
        }

        // Check if code is expired (10 minutes)
        if ($this->verification_sent_at->addMinutes(10)->isPast()) {
            return false;
        }

        $this->update([
            'verified_at' => now(),
        ]);

        return true;
    }

    /**
     * Mark as approved
     */
    public function approve(User $admin): void
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $admin->id,
            'approved_at' => now(),
        ]);
    }

    /**
     * Mark as rejected
     */
    public function reject(User $admin, string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'rejected_by' => $admin->id,
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Mark as processing
     */
    public function markAsProcessing(): void
    {
        $this->update([
            'status' => 'processing',
            'processed_at' => now(),
        ]);
    }

    /**
     * Mark as broadcasted
     */
    public function markAsBroadcasted(): void
    {
        $this->update([
            'status' => 'broadcasted',
        ]);
    }

    /**
     * Mark as completed
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark as failed
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'failed_at' => now(),
        ]);
    }

    /**
     * Calculate risk score based on amount, frequency, etc.
     */
    public function calculateRiskScore(): float
    {
        $score = 0;
        $flags = [];

        // Check withdrawal amount
        if ($this->amount > 100000) { // Large amount in crypto terms
            $score += 30;
            $flags[] = 'large_amount';
        }

        // Check if new address
        $previousWithdrawals = static::where('user_id', $this->user_id)
            ->where('to_address', $this->to_address)
            ->where('id', '!=', $this->id)
            ->count();

        if ($previousWithdrawals === 0) {
            $score += 20;
            $flags[] = 'new_address';
        }

        // Check frequency (multiple withdrawals in short time)
        $recentWithdrawals = static::where('user_id', $this->user_id)
            ->where('created_at', '>', now()->subHours(24))
            ->count();

        if ($recentWithdrawals > 3) {
            $score += 25;
            $flags[] = 'high_frequency';
        }

        // Check if address validation failed
        if (!$this->address_validated) {
            $score += 15;
            $flags[] = 'address_validation_failed';
        }

        // Update risk score and flags
        $this->update([
            'risk_score' => min(100, $score),
            'risk_flags' => json_encode($flags),
            'is_suspicious' => $score >= 50,
            'requires_admin_approval' => $score >= 50,
        ]);

        return $score;
    }

    // ==================== Relationships ====================

    /**
     * User who requested withdrawal
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Crypto wallet
     */
    public function cryptoWallet()
    {
        return $this->belongsTo(CryptoWallet::class);
    }

    /**
     * Crypto currency
     */
    public function currency()
    {
        return $this->belongsTo(CryptoCurrency::class, 'crypto_currency_id');
    }

    /**
     * Admin who approved
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Admin who rejected
     */
    public function rejecter()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /**
     * Related crypto transaction
     */
    public function cryptoTransaction()
    {
        return $this->belongsTo(CryptoTransaction::class);
    }

    // ==================== Scopes ====================

    /**
     * Filter by status
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Pending requests
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Requires approval
     */
    public function scopeRequiresApproval($query)
    {
        return $query->where('requires_admin_approval', true)
                     ->whereIn('status', ['pending', 'verifying']);
    }

    /**
     * Approved requests
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Rejected requests
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Completed requests
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Failed requests
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Suspicious requests
     */
    public function scopeSuspicious($query)
    {
        return $query->where('is_suspicious', true);
    }

    /**
     * High risk requests
     */
    public function scopeHighRisk($query)
    {
        return $query->where('risk_score', '>=', 50);
    }

    /**
     * Recent first
     */
    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Filter by network
     */
    public function scopeNetwork($query, string $network)
    {
        return $query->where('network', $network);
    }
}
