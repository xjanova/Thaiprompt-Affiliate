<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * HoroscopeDreamCategory Model — หมวดหมู่ทำนายฝัน
 *
 * เก็บหมวดหมู่สำหรับจัดกลุ่มสัญลักษณ์ในฝัน
 * เช่น สัตว์, คน, ธรรมชาติ, น้ำ, ตาย, เงิน ฯลฯ
 *
 * @property int $id
 * @property string $name_th ชื่อหมวดหมู่
 * @property string $slug URL slug
 * @property string|null $icon ไอคอน
 * @property string $color_hex สีหมวดหมู่
 * @property string|null $description_th คำอธิบาย
 * @property bool $is_active เปิด/ปิดใช้งาน
 * @property int $sort_order ลำดับ
 */
class HoroscopeDreamCategory extends Model
{
    protected $table = 'horoscope_dream_categories';

    protected $fillable = [
        'name_th',
        'slug',
        'icon',
        'color_hex',
        'description_th',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * สัญลักษณ์ฝันทั้งหมดในหมวดนี้
     */
    public function dreamSymbols(): HasMany
    {
        return $this->hasMany(HoroscopeDreamDictionary::class, 'category_id');
    }

    /**
     * เฉพาะหมวดที่เปิดใช้งาน
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * เรียงตาม sort_order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * จำนวนสัญลักษณ์ในหมวด
     */
    public function getSymbolCountAttribute(): int
    {
        return $this->dreamSymbols()->where('is_active', true)->count();
    }
}
