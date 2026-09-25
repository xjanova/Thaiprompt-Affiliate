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

    /**
     * แปลงค่าหมวดหมู่จาก request (id หรือ slug) เป็นรายการ id ที่ต้องกรอง
     * รวมหมวดหมู่ลูกด้วย — เลือก "ผัก" ต้องเห็นสินค้าใน "ผักใบ", "ผักกินหัว" ด้วย
     *
     * @return array<int> ว่าง = ไม่พบหมวดหมู่
     */
    public static function resolveIds(int|string|null $category): array
    {
        if ($category === null || $category === '') {
            return [];
        }

        $found = is_numeric($category)
            ? static::find((int) $category)
            : static::where('slug', (string) $category)->first();

        if (! $found) {
            return [];
        }

        $childIds = static::where('parent_id', $found->id)->pluck('id')->all();

        return array_values(array_unique(array_merge([(int) $found->id], array_map('intval', $childIds))));
    }

    /**
     * เดาหมวดหมู่จากคำใบ้ (เช่นที่ AI สกัดจากแชท LINE: "ผักสด", "ผลไม้")
     *
     * @return int|null id หมวดหมู่ที่ใกล้ที่สุด หรือ null ถ้าไม่เจอ
     */
    public static function guessIdFromHint(?string $hint): ?int
    {
        $hint = trim((string) $hint);

        if ($hint === '') {
            return null;
        }

        $exact = static::active()
            ->where(fn ($q) => $q->where('name', $hint)->orWhere('slug', $hint))
            ->value('id');

        if ($exact) {
            return (int) $exact;
        }

        // ชื่อหมวดหมู่อยู่ในคำใบ้ หรือคำใบ้อยู่ในชื่อหมวดหมู่
        foreach (static::active()->orderBy('sort_order')->get(['id', 'name']) as $category) {
            $name = trim((string) $category->name);
            if ($name !== '' && (mb_stripos($hint, $name) !== false || mb_stripos($name, $hint) !== false)) {
                return (int) $category->id;
            }
        }

        return null;
    }
}
