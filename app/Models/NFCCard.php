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
        'wallet_id',
        'encrypted_data',
        'encryption_key_hash',
        'card_signature',
        'encryption_version',
        'card_type',
        'balance',
        'credit_limit',
        'daily_spending_limit',
        'monthly_spending_limit',
        'transaction_limit',
        'daily_spent',
        'monthly_spent',
        'last_reset_daily',
        'last_reset_monthly',
        'status',
        'is_paired',
        'is_enabled',
        'disabled_at',
        'disabled_reason',
        'paired_at',
        'activated_at',
        'expires_at',
        'last_used_at',
        'failed_attempts',
        'blocked_until',
        'blocked_reason',
        'last_ip',
        'supports_tpix',
        'tpix_wallet_address',
        'auto_topup_enabled',
        'auto_topup_threshold',
        'auto_topup_amount',
        'metadata',
        'notes',
        'issued_by',
        'paired_by',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'credit_limit' => 'decimal:2',
        'daily_spending_limit' => 'decimal:2',
        'monthly_spending_limit' => 'decimal:2',
        'transaction_limit' => 'decimal:2',
        'daily_spent' => 'decimal:2',
        'monthly_spent' => 'decimal:2',
        'last_reset_daily' => 'date',
        'last_reset_monthly' => 'date',
        'auto_topup_threshold' => 'decimal:2',
        'auto_topup_amount' => 'decimal:2',
        'is_paired' => 'boolean',
        'is_enabled' => 'boolean',
        'supports_tpix' => 'boolean',
        'auto_topup_enabled' => 'boolean',
        'paired_at' => 'datetime',
        'activated_at' => 'datetime',
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
        'blocked_until' => 'datetime',
        'disabled_at' => 'datetime',
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
     *
     * ผู้ใช้ที่เป็นเจ้าของการ์ด
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the wallet linked to this card
     *
     * Wallet ที่ผูกกับการ์ด
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
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

    // ==========================================
    // 🆕 Spending Limits & Controls Methods
    // ==========================================

    /**
     * ตรวจสอบว่าสามารถใช้จ่ายได้หรือไม่
     *
     * @param float $amount จำนวนเงินที่ต้องการใช้จ่าย
     * @return bool
     */
    public function canSpend(float $amount): bool
    {
        // ตรวจสอบว่าการ์ดเปิดใช้งานหรือไม่
        if (!$this->is_enabled) {
            return false;
        }

        // ตรวจสอบสถานะการ์ด
        if (!$this->isActive()) {
            return false;
        }

        // ตรวจสอบยอดเงินคงเหลือ
        if (!$this->hasSufficientBalance($amount)) {
            return false;
        }

        // ตรวจสอบวงเงินต่อธุรกรรม
        if ($amount > $this->transaction_limit) {
            return false;
        }

        // Reset daily spent ถ้าข้ามวัน
        $this->resetDailySpentIfNeeded();

        // ตรวจสอบวงเงินรายวัน
        if (($this->daily_spent + $amount) > $this->daily_spending_limit) {
            return false;
        }

        // Reset monthly spent ถ้าข้ามเดือน
        $this->resetMonthlySpentIfNeeded();

        // ตรวจสอบวงเงินรายเดือน
        if (($this->monthly_spent + $amount) > $this->monthly_spending_limit) {
            return false;
        }

        return true;
    }

    /**
     * บันทึกยอดใช้จ่าย
     *
     * @param float $amount
     * @return bool
     */
    public function recordSpending(float $amount): bool
    {
        $this->resetDailySpentIfNeeded();
        $this->resetMonthlySpentIfNeeded();

        return $this->increment('daily_spent', $amount) &&
               $this->increment('monthly_spent', $amount);
    }

    /**
     * Reset ยอดใช้จ่ายรายวันถ้าข้ามวัน
     *
     * @return void
     */
    protected function resetDailySpentIfNeeded(): void
    {
        $today = now()->toDateString();

        if (!$this->last_reset_daily || $this->last_reset_daily->toDateString() !== $today) {
            $this->update([
                'daily_spent' => 0,
                'last_reset_daily' => $today,
            ]);
        }
    }

    /**
     * Reset ยอดใช้จ่ายรายเดือนถ้าข้ามเดือน
     *
     * @return void
     */
    protected function resetMonthlySpentIfNeeded(): void
    {
        $currentMonth = now()->format('Y-m');
        $lastResetMonth = $this->last_reset_monthly ? $this->last_reset_monthly->format('Y-m') : null;

        if (!$lastResetMonth || $lastResetMonth !== $currentMonth) {
            $this->update([
                'monthly_spent' => 0,
                'last_reset_monthly' => now()->toDateString(),
            ]);
        }
    }

    /**
     * ตั้งค่าวงเงินรายวัน
     *
     * @param float $limit
     * @return bool
     */
    public function setDailyLimit(float $limit): bool
    {
        return $this->update(['daily_spending_limit' => $limit]);
    }

    /**
     * ตั้งค่าวงเงินรายเดือน
     *
     * @param float $limit
     * @return bool
     */
    public function setMonthlyLimit(float $limit): bool
    {
        return $this->update(['monthly_spending_limit' => $limit]);
    }

    /**
     * ตั้งค่าวงเงินต่อธุรกรรม
     *
     * @param float $limit
     * @return bool
     */
    public function setTransactionLimit(float $limit): bool
    {
        return $this->update(['transaction_limit' => $limit]);
    }

    /**
     * ดูยอดเงินคงเหลือที่ใช้ได้วันนี้
     *
     * @return float
     */
    public function getDailyRemainingAttribute(): float
    {
        $this->resetDailySpentIfNeeded();
        return max(0, $this->daily_spending_limit - $this->daily_spent);
    }

    /**
     * ดูยอดเงินคงเหลือที่ใช้ได้เดือนนี้
     *
     * @return float
     */
    public function getMonthlyRemainingAttribute(): float
    {
        $this->resetMonthlySpentIfNeeded();
        return max(0, $this->monthly_spending_limit - $this->monthly_spent);
    }

    // ==========================================
    // 🆕 Enable/Disable Card Methods
    // ==========================================

    /**
     * เปิดใช้งานการ์ด
     *
     * @return bool
     */
    public function enable(): bool
    {
        return $this->update([
            'is_enabled' => true,
            'disabled_at' => null,
            'disabled_reason' => null,
        ]);
    }

    /**
     * ปิดการใช้งานการ์ด
     *
     * @param string|null $reason
     * @return bool
     */
    public function disable(?string $reason = null): bool
    {
        return $this->update([
            'is_enabled' => false,
            'disabled_at' => now(),
            'disabled_reason' => $reason,
        ]);
    }

    /**
     * ตรวจสอบว่าการ์ดเปิดใช้งานอยู่หรือไม่
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->is_enabled === true;
    }

    /**
     * ตรวจสอบว่าการ์ดสามารถใช้งานได้จริงหรือไม่
     * (ทั้ง enabled และ active และไม่ expired และไม่ blocked)
     *
     * @return bool
     */
    public function isUsable(): bool
    {
        return $this->isEnabled() && $this->isActive() && !$this->isExpired() && !$this->isBlocked();
    }

    // ==========================================
    // 🆕 Wallet Integration Methods
    // ==========================================

    /**
     * ผูกกับ Wallet
     *
     * @param int $walletId
     * @return bool
     */
    public function linkWallet(int $walletId): bool
    {
        return $this->update(['wallet_id' => $walletId]);
    }

    /**
     * ยกเลิกการผูกกับ Wallet
     *
     * @return bool
     */
    public function unlinkWallet(): bool
    {
        return $this->update(['wallet_id' => null]);
    }

    /**
     * ตรวจสอบว่าผูกกับ Wallet แล้วหรือไม่
     *
     * @return bool
     */
    public function hasLinkedWallet(): bool
    {
        return $this->wallet_id !== null;
    }

    // ==========================================
    // 🆕 TPIX Integration Methods
    // ==========================================

    /**
     * เปิดใช้งาน TPIX
     *
     * @param string $tpixWalletAddress
     * @return bool
     */
    public function enableTPIX(string $tpixWalletAddress): bool
    {
        return $this->update([
            'supports_tpix' => true,
            'tpix_wallet_address' => $tpixWalletAddress,
        ]);
    }

    /**
     * ปิดใช้งาน TPIX
     *
     * @return bool
     */
    public function disableTPIX(): bool
    {
        return $this->update([
            'supports_tpix' => false,
            'tpix_wallet_address' => null,
        ]);
    }

    /**
     * ตรวจสอบว่ารองรับ TPIX หรือไม่
     *
     * @return bool
     */
    public function supportsTPIX(): bool
    {
        return $this->supports_tpix === true && !empty($this->tpix_wallet_address);
    }

    // ==========================================
    // 🆕 Auto Top-up Methods
    // ==========================================

    /**
     * เปิดใช้งาน Auto Top-up
     *
     * @param float $threshold
     * @param float $amount
     * @return bool
     */
    public function enableAutoTopup(float $threshold, float $amount): bool
    {
        return $this->update([
            'auto_topup_enabled' => true,
            'auto_topup_threshold' => $threshold,
            'auto_topup_amount' => $amount,
        ]);
    }

    /**
     * ปิดใช้งาน Auto Top-up
     *
     * @return bool
     */
    public function disableAutoTopup(): bool
    {
        return $this->update(['auto_topup_enabled' => false]);
    }

    /**
     * ตรวจสอบว่าต้อง Auto Top-up หรือไม่
     *
     * @return bool
     */
    public function needsAutoTopup(): bool
    {
        return $this->auto_topup_enabled &&
               $this->balance < $this->auto_topup_threshold &&
               $this->hasLinkedWallet();
    }

    // ==========================================
    // 🆕 Scope Methods
    // ==========================================

    /**
     * Scope สำหรับการ์ดที่เปิดใช้งาน
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    /**
     * Scope สำหรับการ์ดที่สามารถใช้งานได้
     */
    public function scopeUsable($query)
    {
        return $query->where('is_enabled', true)
                     ->where('status', self::STATUS_ACTIVE)
                     ->where(function($q) {
                         $q->whereNull('expires_at')
                           ->orWhere('expires_at', '>', now());
                     })
                     ->where(function($q) {
                         $q->whereNull('blocked_until')
                           ->orWhere('blocked_until', '<', now());
                     });
    }

    /**
     * Scope สำหรับการ์ดที่รองรับ TPIX
     */
    public function scopeSupportsTPIX($query)
    {
        return $query->where('supports_tpix', true);
    }

    /**
     * Scope สำหรับการ์ดที่ผูกกับ Wallet
     */
    public function scopeWithWallet($query)
    {
        return $query->whereNotNull('wallet_id');
    }
}
