<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingInvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id',
        'product_id',
        'description',
        'quantity',
        'unit',
        'unit_price',
        'discount_amount',
        'tax_rate',
        'tax_amount',
        'total_amount',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            // Calculate amounts
            $subtotal = $item->quantity * $item->unit_price;
            $afterDiscount = $subtotal - $item->discount_amount;
            $item->tax_amount = $afterDiscount * ($item->tax_rate / 100);
            $item->total_amount = $afterDiscount + $item->tax_amount;
        });

        static::saved(function ($item) {
            // Update invoice totals
            if ($item->invoice) {
                $item->invoice->calculateTotals();
                $item->invoice->save();
            }
        });

        static::deleted(function ($item) {
            // Update invoice totals
            if ($item->invoice) {
                $item->invoice->calculateTotals();
                $item->invoice->save();
            }
        });
    }

    /**
     * Get the invoice
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(AccountingInvoice::class, 'invoice_id');
    }

    /**
     * Get the product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(AccountingProduct::class, 'product_id');
    }

    /**
     * Get line total before tax
     */
    public function getLineTotalAttribute(): float
    {
        return ($this->quantity * $this->unit_price) - $this->discount_amount;
    }
}
