<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MlmPackage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'name_th',
        'description',
        'description_th',
        'slug',
        'price',
        'pv_value',
        'color',
        'icon',
        'sort_order',
        'badge',
        'features',
        'products',
        'benefits',
        'max_downline',
        'max_withdrawal_per_month',
        'min_withdrawal_amount',
        'is_active',
        'is_popular',
        'is_available_for_new_members',
        'can_upgrade',
        'upgrade_options',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'pv_value' => 'decimal:2',
        'min_withdrawal_amount' => 'decimal:2',
        'features' => 'array',
        'products' => 'array',
        'benefits' => 'array',
        'upgrade_options' => 'array',
        'is_active' => 'boolean',
        'is_popular' => 'boolean',
        'is_available_for_new_members' => 'boolean',
        'can_upgrade' => 'boolean',
    ];

    /**
     * Get members who purchased this package
     */
    public function members()
    {
        return $this->hasMany(MlmMember::class, 'package_id');
    }

    /**
     * Scope to get only active packages
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get packages available for new members
     */
    public function scopeAvailableForNewMembers($query)
    {
        return $query->where('is_active', true)
                     ->where('is_available_for_new_members', true);
    }

    /**
     * Scope to get popular packages
     */
    public function scopePopular($query)
    {
        return $query->where('is_popular', true);
    }

    /**
     * Get ordered packages
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('price');
    }

    /**
     * Check if package can be upgraded to another package
     */
    public function canUpgradeTo($packageId)
    {
        if (!$this->can_upgrade) {
            return false;
        }

        if (empty($this->upgrade_options)) {
            return true; // Can upgrade to any higher package
        }

        return in_array($packageId, $this->upgrade_options);
    }

    /**
     * Get available upgrade packages
     */
    public function getAvailableUpgrades()
    {
        if (!$this->can_upgrade) {
            return collect();
        }

        $query = static::active()
            ->where('price', '>', $this->price)
            ->ordered();

        if (!empty($this->upgrade_options)) {
            $query->whereIn('id', $this->upgrade_options);
        }

        return $query->get();
    }
}
