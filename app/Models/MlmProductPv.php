<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MlmProductPv extends Model
{
    use HasFactory;

    protected $table = 'mlm_product_pv';

    protected $fillable = [
        'product_id',
        'mlm_plan_id',
        'pv_value',
        'use_global_rate',
        'custom_commission_per_pv',
        'show_pv_on_product_page',
        'show_commission_preview',
        'pv_description',
        'pv_description_th',
        'requires_qualification',
        'qualification_rules',
    ];

    protected function casts(): array
    {
        return [
            'pv_value' => 'decimal:2',
            'custom_commission_per_pv' => 'decimal:2',
            'use_global_rate' => 'boolean',
            'show_pv_on_product_page' => 'boolean',
            'show_commission_preview' => 'boolean',
            'requires_qualification' => 'boolean',
            'qualification_rules' => 'array',
        ];
    }

    /**
     * Get the product
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the MLM plan
     */
    public function plan()
    {
        return $this->belongsTo(MlmPlan::class, 'mlm_plan_id');
    }

    /**
     * Get display description based on current locale
     */
    public function getDisplayDescriptionAttribute()
    {
        return app()->getLocale() === 'th' && $this->pv_description_th
            ? $this->pv_description_th
            : $this->pv_description;
    }

    /**
     * Get effective commission rate (custom or global)
     */
    public function getEffectiveCommissionRate()
    {
        if ($this->use_global_rate) {
            return $this->plan->global_commission_per_pv;
        }

        return $this->custom_commission_per_pv ?? $this->plan->global_commission_per_pv;
    }

    /**
     * Calculate commission preview for a given quantity
     */
    public function calculateCommissionPreview($quantity = 1)
    {
        $totalPv = $this->pv_value * $quantity;
        $commissionRate = $this->getEffectiveCommissionRate();

        return $totalPv * $commissionRate;
    }
}
