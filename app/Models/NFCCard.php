<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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
        // 🆕 NFC Security & Anti-Counterfeit Columns
        'nfc_uid',
        'anti_counterfeit_code',
        'nfc_signature',
        'nfc_written_at',
        'nfc_written_by',
        // 🆕 Card Lock System Columns
        'is_locked',
        'locked_at',
        'locked_by',
        'unlocked_at',
        'unlocked_by',
        // 🆕 Card Info Columns (จากการอ่านบัตร)
        'card_type_detected',
        'memory_size',
        'ndef_writeable',
        'ndef_records_count',
        'ndef_records_data',
        'last_read_at',
        'read_count',
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
        // 🆕 NFC Security Casts
        'nfc_written_at' => 'datetime',
        'nfc_written_by' => 'integer',
        // 🆕 Card Lock Casts
        'is_locked' => 'boolean',
        'locked_at' => 'datetime',
        'locked_by' => 'integer',
        'unlocked_at' => 'datetime',
        'unlocked_by' => 'integer',
        // 🆕 Card Info Casts
        'memory_size' => 'integer',
        'ndef_writeable' => 'boolean',
        'ndef_records_count' => 'integer',
        'ndef_records_data' => 'array',
        'last_read_at' => 'datetime',
        'read_count' => 'integer',
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

    const STATUS_SUSPENDED = 'suspended';

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
        return $this->status === self::STATUS_ACTIVE && ! $this->isExpired() && ! $this->isBlocked();
    }

    /**
     * Check if card is expired
     */
    public function isExpired(): bool
    {
        if (! $this->expires_at) {
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
    public function block(?string $reason = null, ?int $minutes = null): bool
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
    public function pairWithUser(int $userId, ?int $pairedBy = null): bool
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
        if (! $this->hasSufficientBalance($amount)) {
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
    public function updateLastUsed(?string $ip = null): bool
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
        return match ($this->status) {
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
        return match ($this->status) {
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
        return match ($this->card_type) {
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
            return str_repeat('*', strlen($this->card_number) - 4).substr($this->card_number, -4);
        }

        return substr($this->card_number, 0, 4).str_repeat('*', strlen($this->card_number) - 8).substr($this->card_number, -4);
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
     * @param  float  $amount  จำนวนเงินที่ต้องการใช้จ่าย
     */
    public function canSpend(float $amount): bool
    {
        // ตรวจสอบว่าการ์ดเปิดใช้งานหรือไม่
        if (! $this->is_enabled) {
            return false;
        }

        // ตรวจสอบสถานะการ์ด
        if (! $this->isActive()) {
            return false;
        }

        // ตรวจสอบยอดเงินคงเหลือ
        if (! $this->hasSufficientBalance($amount)) {
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
     */
    protected function resetDailySpentIfNeeded(): void
    {
        $today = now()->toDateString();

        if (! $this->last_reset_daily || $this->last_reset_daily->toDateString() !== $today) {
            $this->update([
                'daily_spent' => 0,
                'last_reset_daily' => $today,
            ]);
        }
    }

    /**
     * Reset ยอดใช้จ่ายรายเดือนถ้าข้ามเดือน
     */
    protected function resetMonthlySpentIfNeeded(): void
    {
        $currentMonth = now()->format('Y-m');
        $lastResetMonth = $this->last_reset_monthly ? $this->last_reset_monthly->format('Y-m') : null;

        if (! $lastResetMonth || $lastResetMonth !== $currentMonth) {
            $this->update([
                'monthly_spent' => 0,
                'last_reset_monthly' => now()->toDateString(),
            ]);
        }
    }

    /**
     * ตั้งค่าวงเงินรายวัน
     */
    public function setDailyLimit(float $limit): bool
    {
        return $this->update(['daily_spending_limit' => $limit]);
    }

    /**
     * ตั้งค่าวงเงินรายเดือน
     */
    public function setMonthlyLimit(float $limit): bool
    {
        return $this->update(['monthly_spending_limit' => $limit]);
    }

    /**
     * ตั้งค่าวงเงินต่อธุรกรรม
     */
    public function setTransactionLimit(float $limit): bool
    {
        return $this->update(['transaction_limit' => $limit]);
    }

    /**
     * ดูยอดเงินคงเหลือที่ใช้ได้วันนี้
     */
    public function getDailyRemainingAttribute(): float
    {
        $this->resetDailySpentIfNeeded();

        return max(0, $this->daily_spending_limit - $this->daily_spent);
    }

    /**
     * ดูยอดเงินคงเหลือที่ใช้ได้เดือนนี้
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
     */
    public function isEnabled(): bool
    {
        return $this->is_enabled === true;
    }

    /**
     * ตรวจสอบว่าการ์ดสามารถใช้งานได้จริงหรือไม่
     * (ทั้ง enabled และ active และไม่ expired และไม่ blocked)
     */
    public function isUsable(): bool
    {
        return $this->isEnabled() && $this->isActive() && ! $this->isExpired() && ! $this->isBlocked();
    }

    // ==========================================
    // 🆕 Wallet Integration Methods
    // ==========================================

    /**
     * ผูกกับ Wallet
     */
    public function linkWallet(int $walletId): bool
    {
        return $this->update(['wallet_id' => $walletId]);
    }

    /**
     * ยกเลิกการผูกกับ Wallet
     */
    public function unlinkWallet(): bool
    {
        return $this->update(['wallet_id' => null]);
    }

    /**
     * ตรวจสอบว่าผูกกับ Wallet แล้วหรือไม่
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
     */
    public function supportsTPIX(): bool
    {
        return $this->supports_tpix === true && ! empty($this->tpix_wallet_address);
    }

    // ==========================================
    // 🆕 Auto Top-up Methods
    // ==========================================

    /**
     * เปิดใช้งาน Auto Top-up
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
     */
    public function disableAutoTopup(): bool
    {
        return $this->update(['auto_topup_enabled' => false]);
    }

    /**
     * ตรวจสอบว่าต้อง Auto Top-up หรือไม่
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
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->where(function ($q) {
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

    // ==========================================
    // 🆕 NFC Lock & Card Info Methods
    // ==========================================

    /**
     * ผู้ที่เขียนข้อมูลลงบัตร NFC
     */
    public function nfcWriter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nfc_written_by');
    }

    /**
     * ผู้ที่ล็อคบัตร
     */
    public function locker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    /**
     * ผู้ที่ปลดล็อคบัตร
     */
    public function unlocker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'unlocked_by');
    }

    /**
     * ตรวจสอบว่าบัตรถูกล็อคหรือไม่
     */
    public function isLocked(): bool
    {
        return $this->is_locked === true;
    }

    /**
     * ตรวจสอบว่าบัตรถูกเขียนข้อมูลลงแล้วหรือยัง
     */
    public function isNfcWritten(): bool
    {
        return ! empty($this->nfc_uid) && ! empty($this->anti_counterfeit_code);
    }

    /**
     * ดึงข้อมูลประเภทบัตรที่ตรวจพบ
     */
    public function getCardTypeDetectedLabelAttribute(): ?string
    {
        if (! $this->card_type_detected) {
            return null;
        }

        $labels = [
            'NTAG213' => 'NTAG213 (144 bytes)',
            'NTAG215' => 'NTAG215 (504 bytes)',
            'NTAG216' => 'NTAG216 (888 bytes)',
            'MIFARE_CLASSIC_1K' => 'Mifare Classic 1K',
            'MIFARE_CLASSIC_4K' => 'Mifare Classic 4K',
            'MIFARE_ULTRALIGHT' => 'Mifare Ultralight',
            'MIFARE_ULTRALIGHT_C' => 'Mifare Ultralight C',
        ];

        return $labels[$this->card_type_detected] ?? $this->card_type_detected;
    }

    /**
     * ดึงข้อมูลขนาดหน่วยความจำในหน่วยที่อ่านได้
     */
    public function getMemorySizeLabelAttribute(): ?string
    {
        if (! $this->memory_size) {
            return null;
        }

        if ($this->memory_size >= 1024) {
            return round($this->memory_size / 1024, 1).' KB';
        }

        return $this->memory_size.' bytes';
    }

    /**
     * Scope สำหรับบัตรที่ถูกล็อค
     */
    public function scopeLocked($query)
    {
        return $query->where('is_locked', true);
    }

    /**
     * Scope สำหรับบัตรที่ยังไม่ได้เขียน NFC
     */
    public function scopeNotWritten($query)
    {
        return $query->whereNull('nfc_uid');
    }

    /**
     * Scope สำหรับบัตรที่เขียน NFC แล้ว
     */
    public function scopeWritten($query)
    {
        return $query->whereNotNull('nfc_uid')
            ->whereNotNull('anti_counterfeit_code');
    }

    /**
     * พักการใช้งานบัตร (Suspend)
     */
    public function suspend(?string $reason = null): bool
    {
        return $this->update([
            'status' => self::STATUS_SUSPENDED,
            'blocked_reason' => $reason,
        ]);
    }
}
