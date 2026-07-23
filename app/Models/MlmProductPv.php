<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * MlmProductPv Model
 *
 * ⚠️ IMPORTANT: ใช้ค่าจาก MlmGlobalSetting สำหรับ commission_per_pv
 * ไม่ใช้ค่าจาก MlmPlan (per-plan settings) เพื่อความเป็นเอกภาพ
 */
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
     * หาแถว PV ของสินค้าแบบทนทานต่อ plan-key ไม่ตรงกัน
     *
     * 🐛 Fix 2026-07-24: เดิมแต่ละจุด lookup ใช้ plan id คนละตัว
     * (seller เก็บที่ first-active plan / checkout อ่านที่ default_mlm_plan_id /
     * MlmPvService อ่านที่ plan ของ member) → ระบบหลายแผนทำให้ PV ต่อสินค้า
     * ถูกมองข้ามเงียบๆ แล้วตกไป global rate
     *
     * ลำดับค้นหา: plan ที่ระบุ → default plan (settings) → plan ที่ is_default →
     * แถวแรกของสินค้า (plan ใดก็ได้)
     *
     * @param  int  $productId  ID สินค้า
     * @param  int|null  $preferredPlanId  plan ที่อยากได้ก่อน (เช่น plan ของ member)
     */
    public static function resolveForProduct(int $productId, ?int $preferredPlanId = null): ?self
    {
        // 1) plan ที่ระบุมา
        if ($preferredPlanId) {
            $row = static::where('product_id', $productId)
                ->where('mlm_plan_id', $preferredPlanId)
                ->first();
            if ($row) {
                return $row;
            }
        }

        // 2) default plan จาก settings
        $defaultPlanId = (int) MlmGlobalSetting::get('default_mlm_plan_id', 0);
        if ($defaultPlanId && $defaultPlanId !== $preferredPlanId) {
            $row = static::where('product_id', $productId)
                ->where('mlm_plan_id', $defaultPlanId)
                ->first();
            if ($row) {
                return $row;
            }
        }

        // 3) plan ที่ตั้ง is_default ในตาราง plans
        $isDefaultPlanId = MlmPlan::where('is_default', true)->value('id');
        if ($isDefaultPlanId && ! in_array($isDefaultPlanId, [$preferredPlanId, $defaultPlanId])) {
            $row = static::where('product_id', $productId)
                ->where('mlm_plan_id', $isDefaultPlanId)
                ->first();
            if ($row) {
                return $row;
            }
        }

        // 4) แถวแรกของสินค้า (plan ใดก็ได้ — ดีกว่ามองข้าม PV ที่ผู้ขายตั้งไว้)
        return static::where('product_id', $productId)->first();
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
     * ดึงอัตราคอมมิชชั่นที่ใช้งานจริง (custom หรือ global)
     *
     * ใช้ค่าจาก MlmGlobalSetting แทน per-plan settings
     *
     * @return float
     */
    public function getEffectiveCommissionRate()
    {
        // ดึงค่าจาก Global Settings
        $globalCommissionPerPv = MlmGlobalSetting::get('commission_per_pv', 1);

        if ($this->use_global_rate) {
            return $globalCommissionPerPv;
        }

        return $this->custom_commission_per_pv ?? $globalCommissionPerPv;
    }

    /**
     * คำนวณ preview คอมมิชชั่นตามจำนวนสินค้า
     *
     * @param  int  $quantity  จำนวนสินค้า
     * @return float จำนวนคอมมิชชั่น
     */
    public function calculateCommissionPreview($quantity = 1)
    {
        $totalPv = $this->pv_value * $quantity;
        $commissionRate = $this->getEffectiveCommissionRate();

        return $totalPv * $commissionRate;
    }
}
