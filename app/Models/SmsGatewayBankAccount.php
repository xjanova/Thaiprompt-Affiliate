<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SMS Gateway Bank Account Model
 *
 * บัญชีธนาคารสำหรับ SMS Payment Gateway
 * แต่ละร้านค้าสามารถตั้งบัญชีธนาคารของตัวเองได้
 * store_id = null หมายถึงบัญชีของ platform/admin
 */
class SmsGatewayBankAccount extends Model
{
    protected $fillable = [
        'store_id',
        'bank_code',
        'bank_name',
        'account_name',
        'account_number',
        'branch',
        'promptpay_id',
        'promptpay_type',
        'qr_image',
        'is_active',
        'is_primary',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_primary' => 'boolean',
    ];

    // ========================================
    // RELATIONSHIPS
    // ========================================

    public function store(): BelongsTo
    {
        return $this->belongsTo(VendorStore::class, 'store_id');
    }

    // ========================================
    // SCOPES
    // ========================================

    public function scopeForStore($query, ?int $storeId)
    {
        if ($storeId === null) {
            return $query->whereNull('store_id');
        }
        return $query->where('store_id', $storeId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('is_primary', 'desc')->orderBy('sort_order');
    }

    // ========================================
    // HELPERS
    // ========================================

    /**
     * ดึงบัญชีหลักของร้าน (หรือ admin)
     */
    public static function getPrimary(?int $storeId): ?self
    {
        return static::forStore($storeId)
            ->active()
            ->primary()
            ->first()
            ?? static::forStore($storeId)->active()->first();
    }

    /**
     * ชื่อธนาคารไทย
     */
    public function getBankDisplayNameAttribute(): string
    {
        $banks = config('smschecker.supported_banks', []);
        return $banks[$this->bank_code] ?? $this->bank_code;
    }

    /**
     * เลขบัญชีที่ mask แล้ว (แสดงเฉพาะ 4 ตัวท้าย)
     */
    public function getMaskedAccountNumberAttribute(): string
    {
        $num = $this->account_number;
        if (strlen($num) <= 4) {
            return $num;
        }
        return str_repeat('*', strlen($num) - 4) . substr($num, -4);
    }
}
