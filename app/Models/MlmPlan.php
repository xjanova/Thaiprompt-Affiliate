<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MlmPlan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'name_th',
        'description',
        'description_th',
        'slug',
        'type',
        'is_active',
        'is_default',
        'color',
        'icon',
        'sort_order',
        'joining_fee',
        'requires_joining_fee',
        'use_pv_system',
        'global_pv_rate',
        'global_commission_per_pv',
        'unilevel_levels',
        'unilevel_max_depth',
        'unilevel_compression',
        'binary_pair_commission',
        'binary_match_percentage',
        'binary_max_pairs_per_day',
        'binary_max_commission_per_day',
        'binary_flush_percentage',
        'binary_spillover',
        'binary_pairing_type',
        'requires_rank',
        'rank_requirements',
        'auto_placement',
        'auto_placement_type',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'joining_fee' => 'decimal:2',
            'requires_joining_fee' => 'boolean',
            'use_pv_system' => 'boolean',
            'global_pv_rate' => 'decimal:2',
            'global_commission_per_pv' => 'decimal:2',
            'unilevel_levels' => 'array',
            'unilevel_max_depth' => 'integer',
            'unilevel_compression' => 'boolean',
            'binary_pair_commission' => 'decimal:2',
            'binary_match_percentage' => 'decimal:2',
            'binary_max_pairs_per_day' => 'decimal:2',
            'binary_max_commission_per_day' => 'decimal:2',
            'binary_flush_percentage' => 'decimal:2',
            'binary_spillover' => 'boolean',
            'requires_rank' => 'boolean',
            'rank_requirements' => 'array',
            'auto_placement' => 'boolean',
        ];
    }

    /**
     * Get all members in this plan
     */
    public function members()
    {
        return $this->hasMany(MlmMember::class);
    }

    /**
     * Get all commissions for this plan
     */
    public function commissions()
    {
        return $this->hasMany(MlmCommission::class);
    }

    /**
     * Get product PV configurations
     */
    public function productPvConfigs()
    {
        return $this->hasMany(MlmProductPv::class);
    }

    /**
     * Get display name based on current locale
     */
    public function getDisplayNameAttribute()
    {
        return app()->getLocale() === 'th' && $this->name_th
            ? $this->name_th
            : $this->name;
    }

    /**
     * Get display description based on current locale
     */
    public function getDisplayDescriptionAttribute()
    {
        return app()->getLocale() === 'th' && $this->description_th
            ? $this->description_th
            : $this->description;
    }

    /**
     * Scope for active plans
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the default plan
     */
    public static function getDefault()
    {
        return static::where('is_default', true)->first();
    }
}
