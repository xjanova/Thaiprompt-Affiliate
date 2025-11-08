<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SoftwareQuotation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'quotation_number',
        'user_id',
        'software_product_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'company_name',
        'company_address',
        'tax_id',
        'notes',
        'admin_notes',
        'subtotal',
        'discount_amount',
        'discount_percentage',
        'tax_rate',
        'tax_amount',
        'total_amount',
        'setup_total',
        'monthly_total',
        'status',
        'sent_at',
        'accepted_at',
        'rejected_at',
        'expired_at',
        'valid_until',
        'order_id',
        'converted_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'setup_total' => 'decimal:2',
        'monthly_total' => 'decimal:2',
        'sent_at' => 'datetime',
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
        'expired_at' => 'datetime',
        'valid_until' => 'datetime',
        'converted_at' => 'datetime',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($quotation) {
            if (empty($quotation->quotation_number)) {
                $quotation->quotation_number = 'QT-' . date('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(6));
            }

            // Set default valid_until to 30 days from now
            if (empty($quotation->valid_until)) {
                $quotation->valid_until = now()->addDays(30);
            }
        });
    }

    /**
     * Get the user (customer)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the software product
     */
    public function softwareProduct(): BelongsTo
    {
        return $this->belongsTo(SoftwareProduct::class);
    }

    /**
     * Get the converted order
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get all items
     */
    public function items(): HasMany
    {
        return $this->hasMany(SoftwareQuotationItem::class, 'software_quotation_id');
    }

    /**
     * Get installment plan if exists
     */
    public function installmentPlan(): HasMany
    {
        return $this->hasMany(InstallmentPlan::class, 'software_quotation_id');
    }

    /**
     * Calculate totals based on items
     */
    public function calculateTotals(): void
    {
        $this->subtotal = $this->items->sum('subtotal');
        $this->setup_total = $this->items->sum('setup_fee');
        $this->monthly_total = $this->items->sum('monthly_fee');

        // Apply discount
        if ($this->discount_percentage > 0) {
            $this->discount_amount = $this->subtotal * ($this->discount_percentage / 100);
        }

        $amountAfterDiscount = $this->subtotal - $this->discount_amount;

        // Calculate tax
        $this->tax_amount = $amountAfterDiscount * ($this->tax_rate / 100);

        // Calculate total
        $this->total_amount = $amountAfterDiscount + $this->tax_amount;

        $this->save();
    }

    /**
     * Mark as sent
     */
    public function markAsSent(): void
    {
        $this->status = 'sent';
        $this->sent_at = now();
        $this->save();
    }

    /**
     * Mark as accepted
     */
    public function markAsAccepted(): void
    {
        $this->status = 'accepted';
        $this->accepted_at = now();
        $this->save();
    }

    /**
     * Mark as rejected
     */
    public function markAsRejected(): void
    {
        $this->status = 'rejected';
        $this->rejected_at = now();
        $this->save();
    }

    /**
     * Mark as converted to order
     */
    public function markAsConverted(Order $order): void
    {
        $this->status = 'converted';
        $this->order_id = $order->id;
        $this->converted_at = now();
        $this->save();
    }

    /**
     * Check if quotation is expired
     */
    public function isExpired(): bool
    {
        return $this->valid_until && $this->valid_until->isPast();
    }

    /**
     * Check if quotation can be accepted
     */
    public function canBeAccepted(): bool
    {
        return in_array($this->status, ['pending', 'reviewed', 'sent']) && !$this->isExpired();
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'secondary',
            'pending' => 'warning',
            'reviewed' => 'info',
            'sent' => 'primary',
            'accepted' => 'success',
            'rejected' => 'danger',
            'expired' => 'dark',
            'converted' => 'success',
            default => 'secondary',
        };
    }

    /**
     * Get status label in Thai
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'ร่าง',
            'pending' => 'รอการตรวจสอบ',
            'reviewed' => 'ตรวจสอบแล้ว',
            'sent' => 'ส่งแล้ว',
            'accepted' => 'ลูกค้ายอมรับ',
            'rejected' => 'ลูกค้าปฏิเสธ',
            'expired' => 'หมดอายุ',
            'converted' => 'แปลงเป็นคำสั่งซื้อแล้ว',
            default => 'ไม่ทราบสถานะ',
        };
    }

    /**
     * Scope: Pending quotations
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: Sent quotations
     */
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    /**
     * Scope: Accepted quotations
     */
    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    /**
     * Scope: Converted quotations
     */
    public function scopeConverted($query)
    {
        return $query->where('status', 'converted');
    }
}
