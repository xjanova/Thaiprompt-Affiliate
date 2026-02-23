<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * HoroscopeDreamDictionary Model — พจนานุกรมทำนายฝัน
 *
 * เก็บสัญลักษณ์ฝัน + ความหมาย + เลขเด็ด + ระดับลางบอกเหตุ
 *
 * @property int $id
 * @property int $category_id FK หมวดหมู่
 * @property string $keyword_th คำค้นหา
 * @property string $meaning_th ความหมาย
 * @property array|null $lucky_numbers เลขเด็ด
 * @property bool $is_good_omen ลางดี/ร้าย
 * @property int $intensity ระดับ 1-5
 * @property int $search_count จำนวนค้นหา
 * @property bool $is_active เปิด/ปิดใช้งาน
 */
class HoroscopeDreamDictionary extends Model
{
    protected $table = 'horoscope_dream_dictionary';

    protected $fillable = [
        'category_id',
        'keyword_th',
        'meaning_th',
        'lucky_numbers',
        'is_good_omen',
        'intensity',
        'search_count',
        'is_active',
    ];

    protected $casts = [
        'lucky_numbers' => 'array',
        'is_good_omen' => 'boolean',
        'intensity' => 'integer',
        'search_count' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * หมวดหมู่ของสัญลักษณ์
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(HoroscopeDreamCategory::class, 'category_id');
    }

    /**
     * เฉพาะที่เปิดใช้งาน
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * ค้นหาด้วยคำไทย (LIKE)
     */
    public function scopeSearchKeyword($query, string $keyword)
    {
        return $query->where('keyword_th', 'LIKE', "%{$keyword}%");
    }

    /**
     * เรียงตามยอดนิยม
     */
    public function scopePopular($query)
    {
        return $query->orderByDesc('search_count');
    }

    /**
     * เพิ่มจำนวนการค้นหา
     */
    public function incrementSearch(): void
    {
        $this->increment('search_count');
    }

    /**
     * แสดงเลขเด็ดเป็น string
     */
    public function getLuckyNumbersDisplayAttribute(): string
    {
        if (empty($this->lucky_numbers)) {
            return '-';
        }

        return implode(', ', $this->lucky_numbers);
    }

    /**
     * ไอคอนลางดี/ร้าย
     */
    public function getOmenIconAttribute(): string
    {
        return $this->is_good_omen ? '✨' : '⚠️';
    }
}
