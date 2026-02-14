<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'user_id',
        'store_id',
        'type',
        'payment_method',
        'status',
        'amount',
        'currency',
        'order_id',
        'wallet_transaction_id',
        'gateway',
        'gateway_transaction_id',
        'gateway_response',
        'promptpay_qr_code',
        'promptpay_ref_no',
        'metadata',
        'notes',
        'paid_at',
        'expired_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'gateway_response' => 'array',
        'metadata' => 'array',
        'paid_at' => 'datetime',
        'expired_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->transaction_id)) {
                $model->transaction_id = self::generateBillReference($model->store_id);
            }
        });

        // Auto-generate unique amount สำหรับ SMS Checker matching
        // ทำงานหลังจาก transaction ถูกสร้างเสร็จ (มี ID แล้ว)
        static::created(function ($model) {
            $model->generateUniqueAmountIfNeeded();
        });
    }

    /**
     * สร้างเลขที่บิล (transaction_id) ตาม store prefix
     *
     * - Platform store (admin) → PRE-{YYMMDD}-{XXXXX}
     * - Vendor store            → SEL-{storeId}-{YYMMDD}-{XXXXX}
     * - Legacy (backward compat) → TXN-{random12} (เมื่อ store_id = null)
     */
    public static function generateBillReference(?int $storeId): string
    {
        $datePart = now()->format('ymd');
        $platformStoreId = VendorStore::getPlatformStoreId();

        if ($storeId === null || (int) $storeId === (int) $platformStoreId) {
            $prefix = 'PRE';
        } else {
            $prefix = "SEL-{$storeId}";
        }

        for ($i = 0; $i < 10; $i++) {
            $random = strtoupper(Str::random(5));
            $ref = "{$prefix}-{$datePart}-{$random}";
            if (! self::where('transaction_id', $ref)->exists()) {
                return $ref;
            }
        }

        // Fallback: ใช้ microtime
        $uniquePart = strtoupper(substr(md5(microtime()), 0, 5));

        return "{$prefix}-{$datePart}-{$uniquePart}";
    }

    /**
     * สร้าง unique amount (เพิ่มทศนิยม) สำหรับ SMS Checker auto-matching
     *
     * เฉพาะ promptpay/bank_transfer ที่ status = pending
     * และยังไม่มี unique amount (ไม่มี unique_amount_id ใน metadata)
     */
    public function generateUniqueAmountIfNeeded(): void
    {
        // เฉพาะ promptpay และ bank_transfer
        if (! in_array($this->payment_method, ['promptpay', 'bank_transfer'])) {
            return;
        }

        // เฉพาะ pending transactions
        if ($this->status !== 'pending') {
            return;
        }

        // ตรวจสอบว่ายังไม่มี unique amount
        $metadata = $this->metadata ?? [];
        if (! empty($metadata['unique_amount_id'])) {
            return; // มี unique amount แล้ว
        }

        // ตรวจสอบว่า SMS Checker เปิดใช้งาน
        if (! config('smschecker.enabled', true)) {
            // ตรวจสอบแบบ fallback
            try {
                $hasDevice = SmsCheckerDevice::where('status', 'active')->exists();
                if (! $hasDevice) {
                    return;
                }
            } catch (\Exception $e) {
                return;
            }
        }

        try {
            $uniqueAmount = UniquePaymentAmount::generate(
                (float) $this->amount,
                $this->id,
                $this->type ?? 'order_payment',
                config('smschecker.unique_amount_expiry', 30)
            );

            if ($uniqueAmount) {
                // เก็บยอดเดิมใน metadata แล้วอัพเดทยอดชำระเป็น unique amount
                $metadata['original_amount'] = (float) $this->amount;
                $metadata['unique_amount_id'] = $uniqueAmount->id;
                $metadata['decimal_suffix'] = $uniqueAmount->decimal_suffix;

                // ใช้ update แบบ silent เพื่อไม่ trigger events ซ้ำ
                static::withoutEvents(function () use ($uniqueAmount, $metadata) {
                    $this->update([
                        'amount' => $uniqueAmount->unique_amount,
                        'metadata' => $metadata,
                    ]);
                });

                Log::info('SMS Checker: Auto-generated unique amount on create', [
                    'transaction_id' => $this->id,
                    'original_amount' => $metadata['original_amount'],
                    'unique_amount' => $uniqueAmount->unique_amount,
                    'suffix' => $uniqueAmount->decimal_suffix,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('SMS Checker: Failed to generate unique amount on create', [
                'transaction_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get the user that owns the transaction
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the order associated with the transaction
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the wallet transaction
     */
    public function walletTransaction()
    {
        return $this->belongsTo(WalletTransaction::class);
    }

    /**
     * UniquePaymentAmount ที่เชื่อมโยง (ใช้สำหรับ SMS Checker matching)
     *
     * UniquePaymentAmount.transaction_id → PaymentTransaction.id
     */
    public function uniquePaymentAmount()
    {
        return $this->hasOne(UniquePaymentAmount::class, 'transaction_id');
    }

    /**
     * Scope for pending transactions
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for completed transactions
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for failed transactions
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Mark transaction as completed
     */
    public function markAsCompleted()
    {
        $this->update([
            'status' => 'completed',
            'paid_at' => now(),
        ]);
    }

    /**
     * Mark transaction as failed
     */
    public function markAsFailed($reason = null)
    {
        $this->update([
            'status' => 'failed',
            'notes' => $reason,
        ]);
    }

    /**
     * Check if transaction is expired
     */
    public function isExpired()
    {
        return $this->expired_at && $this->expired_at->isPast();
    }

    /**
     * Check if transaction is completed
     */
    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    /**
     * Check if transaction is pending
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }
}
