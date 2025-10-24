<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Addon extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'type',
        'price',
        'is_active',
        'features',
        'icon',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'features' => 'array',
        'sort_order' => 'integer',
    ];

    /**
     * Get all purchases for this addon
     */
    public function purchases(): HasMany
    {
        return $this->hasMany(AddonPurchase::class);
    }

    /**
     * Get active purchases for this addon
     */
    public function activePurchases(): HasMany
    {
        return $this->hasMany(AddonPurchase::class)
            ->where('is_active', true)
            ->where('payment_status', 'completed');
    }

    /**
     * Scope: Active addons only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Filter by type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }
}
