<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PosTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'pos_device_id',
        'pos_session_id',
        'store_id',
        'user_id',
        'customer_id',
        'transaction_code',
        'receipt_number',
        'transaction_date',
        'subtotal',
        'discount_amount',
        'discount_percentage',
        'tax_amount',
        'tax_percentage',
        'service_charge',
        'total_amount',
        'payment_method',
        'payment_details',
        'amount_paid',
        'change_amount',
        'payment_status',
        'discount_code',
        'discount_reason',
        'approved_by',
        'customer_name',
        'customer_phone',
        'customer_email',
        'customer_address',
        'total_items',
        'total_quantity',
        'status',
        'is_synced',
        'synced_at',
        'created_offline',
        'notes',
        'refund_reason',
        'refunded_at',
    ];

    protected $casts = [
        'transaction_date' => 'datetime',
        'payment_details' => 'array',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'tax_percentage' => 'decimal:2',
        'service_charge' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'change_amount' => 'decimal:2',
        'is_synced' => 'boolean',
        'created_offline' => 'boolean',
        'synced_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function posDevice(): BelongsTo
    {
        return $this->belongsTo(PosDevice::class);
    }

    public function posSession(): BelongsTo
    {
        return $this->belongsTo(PosSession::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(VendorStore::class, 'store_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PosTransactionItem::class);
    }

    /**
     * Scopes
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeRefunded($query)
    {
        return $query->where('status', 'refunded');
    }

    public function scopeForDevice($query, $deviceId)
    {
        return $query->where('pos_device_id', $deviceId);
    }

    public function scopeForStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    public function scopeUnsynced($query)
    {
        return $query->where('is_synced', false);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('transaction_date', today());
    }

    /**
     * Helper Methods
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }

    public function canBeRefunded(): bool
    {
        return $this->status === 'completed' && !$this->isRefunded();
    }

    public function refund(?string $reason = null, ?int $userId = null): void
    {
        if (!$this->canBeRefunded()) {
            throw new \Exception('Transaction cannot be refunded');
        }

        $this->update([
            'status' => 'refunded',
            'payment_status' => 'refunded',
            'refund_reason' => $reason,
            'refunded_at' => now(),
            'approved_by' => $userId,
        ]);

        // Restore stock for each item
        foreach ($this->items as $item) {
            if ($item->product) {
                $item->product->increment('stock_quantity', $item->quantity);
            }
        }
    }

    public function calculateTotals(array $items, ?float $discountPercentage = null, ?float $discountAmount = null): array
    {
        $subtotal = collect($items)->sum('subtotal');

        // Calculate discount
        $discount = 0;
        if ($discountAmount) {
            $discount = $discountAmount;
        } elseif ($discountPercentage) {
            $discount = $subtotal * ($discountPercentage / 100);
        }

        $afterDiscount = $subtotal - $discount;

        // Calculate tax
        $taxPercentage = $this->tax_percentage ?? 7.00;
        $taxAmount = $afterDiscount * ($taxPercentage / 100);

        // Calculate service charge
        $serviceCharge = $this->service_charge ?? 0;

        // Calculate total
        $total = $afterDiscount + $taxAmount + $serviceCharge;

        return [
            'subtotal' => round($subtotal, 2),
            'discount_amount' => round($discount, 2),
            'discount_percentage' => $discountPercentage ?? 0,
            'tax_amount' => round($taxAmount, 2),
            'tax_percentage' => $taxPercentage,
            'service_charge' => $serviceCharge,
            'total_amount' => round($total, 2),
        ];
    }

    public function markAsSynced(): void
    {
        $this->update([
            'is_synced' => true,
            'synced_at' => now(),
        ]);
    }

    public function generateTransactionCode(): string
    {
        return 'POS-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 8));
    }

    public function generateReceiptNumber(): string
    {
        return 'RCP-' . date('YmdHis') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            if (empty($transaction->transaction_code)) {
                $transaction->transaction_code = $transaction->generateTransactionCode();
            }
            if (empty($transaction->receipt_number)) {
                $transaction->receipt_number = $transaction->generateReceiptNumber();
            }
            if (empty($transaction->transaction_date)) {
                $transaction->transaction_date = now();
            }
        });

        static::created(function ($transaction) {
            // Update session stats
            if ($transaction->posSession) {
                $transaction->posSession->updateTransactionStats($transaction);
            }

            // Update device stats
            if ($transaction->posDevice) {
                $transaction->posDevice->incrementTransactionStats($transaction->total_amount);
            }
        });
    }
}
