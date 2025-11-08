<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SoftwareQuotationItem extends Model
{
    protected $fillable = [
        'software_quotation_id',
        'software_product_id',
        'item_name',
        'item_description',
        'quantity',
        'unit_price',
        'setup_fee',
        'monthly_fee',
        'subtotal',
        'selected_options',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'setup_fee' => 'decimal:2',
        'monthly_fee' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'selected_options' => 'array',
        'sort_order' => 'integer',
    ];

    /**
     * Get the quotation
     */
    public function quotation(): BelongsTo
    {
        return $this->belongsTo(SoftwareQuotation::class, 'software_quotation_id');
    }

    /**
     * Get the software product
     */
    public function softwareProduct(): BelongsTo
    {
        return $this->belongsTo(SoftwareProduct::class);
    }

    /**
     * Get selected options
     */
    public function selectedOptions(): HasMany
    {
        return $this->hasMany(SoftwareQuotationSelectedOption::class, 'software_quotation_item_id');
    }

    /**
     * Calculate subtotal based on options
     */
    public function calculateSubtotal(): void
    {
        $basePrice = $this->unit_price * $this->quantity;
        $optionsTotal = $this->selectedOptions->sum(function ($option) {
            return $option->price_modifier * $option->quantity;
        });

        $this->subtotal = $basePrice + $optionsTotal;
        $this->setup_fee = $this->selectedOptions->sum('setup_fee');
        $this->monthly_fee = $this->selectedOptions->sum('monthly_fee');

        $this->save();
    }

    /**
     * Get formatted subtotal
     */
    public function getFormattedSubtotalAttribute(): string
    {
        return number_format($this->subtotal, 2);
    }
}
