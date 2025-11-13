<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class NFCCard extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'nfc_cards';

    protected $fillable = [
        'card_number',
        'card_name',
        'user_id',
        'encrypted_data',
        'encryption_key_hash',
        'card_signature',
        'encryption_version',
        'card_type',
        'balance',
        'credit_limit',
        'status',
        'is_paired',
        'paired_at',
        'activated_at',
        'expires_at',
        'last_used_at',
        'failed_attempts',
        'blocked_until',
        'blocked_reason',
        'last_ip',
        'metadata',
        'notes',
        'issued_by',
        'paired_by',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'credit_limit' => 'decimal:2',
        'is_paired' => 'boolean',
        'paired_at' => 'datetime',
        'activated_at' => 'datetime',
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
        'blocked_until' => 'datetime',
        'failed_attempts' => 'integer',
        'encryption_version' => 'integer',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Card Types
     */
    const TYPE_STANDARD = 'standard';
    const TYPE_PREMIUM = 'premium';
    const TYPE_VIP = 'vip';

    /**
     * Card Status
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_BLOCKED = 'blocked';
    const STATUS_EXPIRED = 'expired';
    const STATUS_PENDING = 'pending';

    /**
     * Maximum failed attempts before blocking
     */
    const MAX_FAILED_ATTEMPTS = 3;

    /**
     * Block duration in minutes
     */
    const BLOCK_DURATION_MINUTES = 30;

    /**
     * Get the user who owns this card
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who issued this card
     */
    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    /**
     * Get the user who paired this card
     */
    public function pairer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paired_by');
    }

    /**
     * Get all transactions for this card
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(NFCTransaction::class, 'nfc_card_id');
    }

    /**
     * Get recent transactions
     */
    public function recentTransactions($limit = 10)
    {
        return $this->transactions()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get completed transactions
     */
    public function completedTransactions()
    {
        return $this->transactions()
            ->where('status', NFCTransaction::STATUS_COMPLETED);
    }

    /**
     * Check if card is active
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE && !$this->isExpired() && !$this->isBlocked();
    }

    /**
     * Check if card is expired
     */
    public function isExpired(): bool
    {
        if (!$this->expires_at) {
            return false;
        }

        return $this->expires_at->isPast();
    }

    /**
     * Check if card is blocked
     */
    public function isBlocked(): bool
    {
        if ($this->status === self::STATUS_BLOCKED) {
            return true;
        }

        if ($this->blocked_until && $this->blocked_until->isFuture()) {
            return true;
        }

        return false;
    }

    /**
     * Check if card is paired with a user
     */
    public function isPaired(): bool
    {
        return $this->is_paired && $this->user_id !== null;
    }

    /**
     * Check if card has sufficient balance
     */
    public function hasSufficientBalance(float $amount): bool
    {
        return $this->balance >= $amount;
    }

    /**
     * Check if amount is within credit limit
     */
    public function withinCreditLimit(float $amount): bool
    {
        return $amount <= $this->credit_limit;
    }

    /**
     * Activate card
     */
    public function activate(): bool
    {
        return $this->update([
            'status' => self::STATUS_ACTIVE,
            'activated_at' => now(),
        ]);
    }

    /**
     * Deactivate card
     */
    public function deactivate(): bool
    {
        return $this->update(['status' => self::STATUS_INACTIVE]);
    }

    /**
     * Block card
     */
    public function block(string $reason = null, int $minutes = null): bool
    {
        $minutes = $minutes ?? self::BLOCK_DURATION_MINUTES;

        return $this->update([
            'status' => self::STATUS_BLOCKED,
            'blocked_until' => now()->addMinutes($minutes),
            'blocked_reason' => $reason,
        ]);
    }

    /**
     * Unblock card
     */
    public function unblock(): bool
    {
        return $this->update([
            'status' => self::STATUS_ACTIVE,
            'blocked_until' => null,
            'blocked_reason' => null,
            'failed_attempts' => 0,
        ]);
    }

    /**
     * Pair card with user
     */
    public function pairWithUser(int $userId, int $pairedBy = null): bool
    {
        return $this->update([
            'user_id' => $userId,
            'is_paired' => true,
            'paired_at' => now(),
            'paired_by' => $pairedBy,
            'status' => self::STATUS_ACTIVE,
        ]);
    }

    /**
     * Unpair card from user
     */
    public function unpair(): bool
    {
        return $this->update([
            'user_id' => null,
            'is_paired' => false,
            'paired_at' => null,
            'paired_by' => null,
            'status' => self::STATUS_INACTIVE,
        ]);
    }

    /**
     * Add balance to card
     */
    public function addBalance(float $amount): bool
    {
        return $this->increment('balance', $amount);
    }

    /**
     * Deduct balance from card
     */
    public function deductBalance(float $amount): bool
    {
        if (!$this->hasSufficientBalance($amount)) {
            return false;
        }

        return $this->decrement('balance', $amount);
    }

    /**
     * Set balance
     */
    public function setBalance(float $amount): bool
    {
        return $this->update(['balance' => $amount]);
    }

    /**
     * Increment failed attempts
     */
    public function incrementFailedAttempts(): void
    {
        $this->increment('failed_attempts');

        if ($this->failed_attempts >= self::MAX_FAILED_ATTEMPTS) {
            $this->block('Too many failed attempts');
        }
    }

    /**
     * Reset failed attempts
     */
    public function resetFailedAttempts(): bool
    {
        return $this->update(['failed_attempts' => 0]);
    }

    /**
     * Update last used timestamp
     */
    public function updateLastUsed(string $ip = null): bool
    {
        return $this->update([
            'last_used_at' => now(),
            'last_ip' => $ip,
        ]);
    }

    /**
     * Get status badge color
     */
    public function getStatusBadgeColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_ACTIVE => 'green',
            self::STATUS_INACTIVE => 'gray',
            self::STATUS_BLOCKED => 'red',
            self::STATUS_EXPIRED => 'orange',
            self::STATUS_PENDING => 'yellow',
            default => 'gray',
        };
    }

    /**
     * Get status label in Thai
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_ACTIVE => 'ใช้งานได้',
            self::STATUS_INACTIVE => 'ไม่ได้ใช้งาน',
            self::STATUS_BLOCKED => 'ถูกบล็อก',
            self::STATUS_EXPIRED => 'หมดอายุ',
            self::STATUS_PENDING => 'รอดำเนินการ',
            default => 'ไม่ทราบ',
        };
    }

    /**
     * Get card type label in Thai
     */
    public function getCardTypeLabelAttribute(): string
    {
        return match($this->card_type) {
            self::TYPE_STANDARD => 'มาตรฐาน',
            self::TYPE_PREMIUM => 'พรีเมียม',
            self::TYPE_VIP => 'วีไอพี',
            default => 'ไม่ทราบ',
        };
    }

    /**
     * Get masked card number
     */
    public function getMaskedCardNumberAttribute(): string
    {
        if (strlen($this->card_number) <= 8) {
            return str_repeat('*', strlen($this->card_number) - 4) . substr($this->card_number, -4);
        }

        return substr($this->card_number, 0, 4) . str_repeat('*', strlen($this->card_number) - 8) . substr($this->card_number, -4);
    }

    /**
     * Scope for active cards
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope for paired cards
     */
    public function scopePaired($query)
    {
        return $query->where('is_paired', true)->whereNotNull('user_id');
    }

    /**
     * Scope for unpaired cards
     */
    public function scopeUnpaired($query)
    {
        return $query->where('is_paired', false);
    }

    /**
     * Scope for expired cards
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    /**
     * Scope for specific user
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for specific card type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('card_type', $type);
    }
}
