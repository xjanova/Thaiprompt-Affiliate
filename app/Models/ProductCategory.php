<?php

namespace App\Models;

use App\Services\CategoryImageService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductCategory extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'image_url',
        'icon',
        'parent_id',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });

        // ล้าง cache ภาพปกหมวดหมู่ทันทีเมื่อแอดมินแก้ภาพ/ไอคอน/ชื่อ
        // เพื่อไม่ต้องรอ cache หมดอายุ 1 ชั่วโมงถึงจะเห็นผล
        $forgetCoverCache = function () {
            try {
                Cache::forget(CategoryImageService::CACHE_KEY);
                // หน้าร้านแคชรายการหมวดไว้ด้วย (StorefrontController::getCategories)
                // ต้องล้างพร้อมกัน ไม่งั้นเพิ่ม/แก้/ลบหมวดแล้วเมนูหน้าร้านยังเป็นของเก่า 10 นาที
                Cache::forget('storefront_categories_v2');
            } catch (\Throwable $e) {
                // cache store อาจใช้ไม่ได้ตอน migrate/seed — ไม่ให้ล้มการบันทึกข้อมูล
            }
        };

        static::saved($forgetCoverCache);
        static::deleted($forgetCoverCache);
        static::restored($forgetCoverCache);
    }

    /**
     * Get the parent category
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'parent_id');
    }

    /**
     * Get all child categories
     */
    public function children(): HasMany
    {
        return $this->hasMany(ProductCategory::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * Get all products in this category
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    /**
     * Scope: Only active categories
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Only root categories (no parent)
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Get full path (parent > child > grandchild)
     */
    public function getFullPathAttribute(): string
    {
        $path = [$this->name];
        $parent = $this->parent;

        while ($parent) {
            array_unshift($path, $parent->name);
            $parent = $parent->parent;
        }

        return implode(' > ', $path);
    }

    /**
     * แปลง image_url ให้เป็น URL ที่เปิดได้จริง
     *
     * ⚠️ ตั้งใจตั้งชื่อเป็น image_url_full (ไม่ override image_url)
     * เพราะถ้า override getImageUrlAttribute() จะทำให้:
     *   - ฟอร์มแอดมิน (JS) ได้ค่าที่ถูกเติม prefix ซ้ำเวลาบันทึกกลับ
     *   - Storage::delete($category->image_url) ที่อื่นลบไฟล์ไม่เจอ
     *
     * รองรับทั้ง full URL (https://...), absolute path (/storage/...)
     * และ relative path ใน storage (categories/xxx.webp)
     *
     * @return string|null URL ที่ใช้งานได้ หรือ null ถ้าไม่มีภาพ
     *
     * @example
     * <img src="{{ $category->image_url_full }}">
     */
    public function getImageUrlFullAttribute(): ?string
    {
        $value = trim((string) ($this->attributes['image_url'] ?? ''));

        if ($value === '') {
            return null;
        }

        // เป็น full URL อยู่แล้ว (https://... หรือ http://...)
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }

        // เป็น absolute path บนเว็บอยู่แล้ว (/storage/...)
        if (str_starts_with($value, '/')) {
            return $value;
        }

        // เป็น relative path ใน storage → แปลงด้วย Storage::url()
        return Storage::url($value);
    }

    /**
     * Get product count
     */
    public function getProductCountAttribute(): int
    {
        return $this->products()->active()->count();
    }
}
