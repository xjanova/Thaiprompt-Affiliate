<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * StoreBanner Model - จัดการ Banner ของร้านค้า
 *
 * Model นี้จัดการ banners ที่แสดงบนหน้า storefront และหน้าร้านค้าแต่ละร้าน
 * รองรับทั้ง banner ของระบบ (homepage) และ banner ของร้านค้าแต่ละร้าน
 *
 * @property int $id
 * @property int|null $store_id
 * @property string $location
 * @property string|null $category_slug
 * @property string|null $title
 * @property string|null $subtitle
 * @property string|null $badge
 * @property string|null $highlight_text
 * @property string|null $highlight_label
 * @property string|null $image_url
 * @property string|null $gradient
 * @property string|null $cta_text
 * @property string|null $cta_url
 * @property int $sort_order
 * @property bool $is_active
 * @property \Carbon\Carbon|null $start_at
 * @property \Carbon\Carbon|null $end_at
 * @property int $view_count
 * @property int $click_count
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class StoreBanner extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'store_banners';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'store_id',
        'location',
        'category_slug',
        'title',
        'subtitle',
        'badge',
        'highlight_text',
        'highlight_label',
        'image_url',
        'gradient',
        'cta_text',
        'cta_url',
        'sort_order',
        'is_active',
        'start_at',
        'end_at',
        'view_count',
        'click_count',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'store_id' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'view_count' => 'integer',
        'click_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * ความสัมพันธ์กับ VendorStore
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(VendorStore::class, 'store_id');
    }

    /**
     * Scope: เฉพาะ banners ที่ active
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: เฉพาะ banners ที่กำลังแสดงอยู่ (ตามช่วงเวลา)
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCurrentlyDisplaying($query)
    {
        $now = now();

        return $query->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('start_at')
                    ->orWhere('start_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('end_at')
                    ->orWhere('end_at', '>=', $now);
            });
    }

    /**
     * Scope: Banner สำหรับ Homepage
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForHomepage($query)
    {
        return $query->where('location', 'homepage')
            ->whereNull('store_id');
    }

    /**
     * Scope: Banner สำหรับร้านค้าเฉพาะ
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForStore($query, int $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    /**
     * Scope: Banner สำหรับหมวดหมู่เฉพาะ
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForCategory($query, string $categorySlug)
    {
        return $query->where('location', 'category')
            ->where('category_slug', $categorySlug);
    }

    /**
     * Scope: เรียงตาม sort_order
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * เพิ่ม view count
     */
    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    /**
     * เพิ่ม click count
     */
    public function incrementClickCount(): void
    {
        $this->increment('click_count');
    }

    /**
     * ตรวจสอบว่า banner กำลังแสดงอยู่หรือไม่
     */
    public function isCurrentlyDisplaying(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();

        if ($this->start_at && $this->start_at > $now) {
            return false;
        }

        if ($this->end_at && $this->end_at < $now) {
            return false;
        }

        return true;
    }

    /**
     * ดึง click-through rate (CTR)
     */
    public function getCtrAttribute(): float
    {
        if ($this->view_count === 0) {
            return 0.0;
        }

        return round(($this->click_count / $this->view_count) * 100, 2);
    }
}
