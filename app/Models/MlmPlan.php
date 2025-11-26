<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * MlmPlan Model
 *
 * ⚠️ IMPORTANT: Per-plan commission settings ถูกย้ายไปที่ MlmGlobalSetting แล้ว
 * Model นี้เก็บเฉพาะข้อมูลพื้นฐานของ Plan เท่านั้น (ชื่อ, ประเภท, ค่าธรรมเนียม)
 *
 * @see MlmGlobalSetting สำหรับการตั้งค่าคอมมิชชั่นทั้งหมด
 */
class MlmPlan extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * ⚠️ ลบ per-plan commission settings ออกแล้ว
     * ใช้ MlmGlobalSetting แทน
     */
    protected $fillable = [
        // ข้อมูลพื้นฐาน
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
        // ค่าธรรมเนียมสมัครสมาชิก
        'joining_fee',
        'requires_joining_fee',
        // การตั้งค่าขั้นสูง
        'advanced_settings',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'joining_fee' => 'decimal:2',
            'requires_joining_fee' => 'boolean',
            'advanced_settings' => 'array',
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
     * Get products included in this package
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'mlm_plan_products')
            ->withPivot('quantity', 'discount_percentage', 'sort_order')
            ->withTimestamps()
            ->orderBy('mlm_plan_products.sort_order');
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
