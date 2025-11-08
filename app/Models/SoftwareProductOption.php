<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SoftwareProductOption extends Model
{
    protected $fillable = [
        'software_product_id',
        'name',
        'display_name',
        'description',
        'input_type',
        'is_required',
        'allow_multiple',
        'validation_rules',
        'min_selections',
        'max_selections',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'allow_multiple' => 'boolean',
        'validation_rules' => 'array',
        'min_selections' => 'integer',
        'max_selections' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the software product
     */
    public function softwareProduct(): BelongsTo
    {
        return $this->belongsTo(SoftwareProduct::class, 'software_product_id');
    }

    /**
     * Get all option values
     */
    public function values(): HasMany
    {
        return $this->hasMany(SoftwareProductOptionValue::class, 'software_product_option_id')->orderBy('sort_order');
    }

    /**
     * Get active option values
     */
    public function activeValues(): HasMany
    {
        return $this->values()->where('is_active', true);
    }

    /**
     * Get default value
     */
    public function defaultValue()
    {
        return $this->values()->where('is_default', true)->first();
    }

    /**
     * Get recommended value
     */
    public function recommendedValue()
    {
        return $this->values()->where('is_recommended', true)->first();
    }

    /**
     * Scope: Only active options
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Required options
     */
    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    /**
     * Scope: Ordered by sort order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
