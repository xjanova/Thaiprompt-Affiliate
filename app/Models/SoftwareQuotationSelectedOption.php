<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SoftwareQuotationSelectedOption extends Model
{
    protected $fillable = [
        'software_quotation_item_id',
        'software_product_option_id',
        'software_product_option_value_id',
        'option_name',
        'option_value',
        'option_display_label',
        'price_modifier',
        'setup_fee',
        'monthly_fee',
        'quantity',
    ];

    protected $casts = [
        'price_modifier' => 'decimal:2',
        'setup_fee' => 'decimal:2',
        'monthly_fee' => 'decimal:2',
        'quantity' => 'integer',
    ];

    /**
     * Get the quotation item
     */
    public function quotationItem(): BelongsTo
    {
        return $this->belongsTo(SoftwareQuotationItem::class, 'software_quotation_item_id');
    }

    /**
     * Get the option
     */
    public function option(): BelongsTo
    {
        return $this->belongsTo(SoftwareProductOption::class, 'software_product_option_id');
    }

    /**
     * Get the option value
     */
    public function optionValue(): BelongsTo
    {
        return $this->belongsTo(SoftwareProductOptionValue::class, 'software_product_option_value_id');
    }

    /**
     * Get total price for this option
     */
    public function getTotalPriceAttribute(): float
    {
        return $this->price_modifier * $this->quantity;
    }
}
