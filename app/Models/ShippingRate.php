<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * ShippingRate Model
 *
 * เก็บอัตราค่าจัดส่งตามน้ำหนักและโซน
 *
 * @property int $id
 * @property string $name ชื่อ tier (เช่น "น้ำหนักเบา", "น้ำหนักปานกลาง")
 * @property string $zone โซน (domestic/international)
 * @property float $min_weight_kg น้ำหนักขั้นต่ำ (กก.)
 * @property float $max_weight_kg น้ำหนักสูงสุด (กก.) - 0 = ไม่จำกัด
 * @property float $base_fee ค่าธรรมเนียมพื้นฐาน (บาท)
 * @property float $per_kg_fee ค่าธรรมเนียมต่อ กก. (บาท)
 * @property float $min_fee ค่าส่งขั้นต่ำ (บาท)
 * @property float|null $max_fee ค่าส่งสูงสุด (บาท) - null = ไม่จำกัด
 * @property bool $is_active สถานะใช้งาน
 * @property int $sort_order ลำดับ
 */
class ShippingRate extends Model
{
    protected $fillable = [
        'name',
        'zone',
        'min_weight_kg',
        'max_weight_kg',
        'base_fee',
        'per_kg_fee',
        'min_fee',
        'max_fee',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'min_weight_kg' => 'decimal:3',
        'max_weight_kg' => 'decimal:3',
        'base_fee' => 'decimal:2',
        'per_kg_fee' => 'decimal:2',
        'min_fee' => 'decimal:2',
        'max_fee' => 'decimal:2',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Scope: เฉพาะ rates ที่ใช้งานอยู่
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: เรียงตาม sort_order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Scope: กรองตามโซน
     */
    public function scopeForZone($query, string $zone)
    {
        return $query->where('zone', $zone);
    }
}
