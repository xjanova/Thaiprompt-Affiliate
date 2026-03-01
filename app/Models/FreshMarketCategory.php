<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * FreshMarketCategory - หมวดหมู่สินค้าตลาดสด
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $icon
 * @property string|null $description
 * @property int|null $parent_id
 * @property int $sort_order
 * @property bool $is_active
 * @property string|null $image_url
 */
class FreshMarketCategory extends Model
{
    protected $table = 'fresh_market_categories';

    protected $fillable = [
        'name',
        'slug',
        'icon',
        'description',
        'parent_id',
        'sort_order',
        'is_active',
        'image_url',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * สร้าง slug อัตโนมัติจากชื่อ
     */
    protected static function booted(): void
    {
        static::creating(function (self $category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name) ?: Str::random(8);
            }
        });
    }

    /**
     * หมวดหมู่แม่
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * หมวดหมู่ลูก
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * สินค้าในหมวดหมู่นี้
     */
    public function listings(): HasMany
    {
        return $this->hasMany(FreshMarketListing::class, 'category_id');
    }

    /**
     * Scope: เฉพาะหมวดหมู่ที่เปิดใช้งาน
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: เฉพาะหมวดหมู่หลัก (ไม่มี parent)
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }
}
