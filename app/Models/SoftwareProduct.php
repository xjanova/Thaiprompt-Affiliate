<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SoftwareProduct extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'category_id',
        'developer_id',
        'name',
        'slug',
        'short_description',
        'description',
        'features',
        'base_price',
        'price_label',
        'currency',
        'main_image_url',
        'gallery_images',
        'technical_specs',
        'demo_url',
        'documentation_url',
        'is_customizable',
        'is_featured',
        'is_active',
        'requires_consultation',
        'estimated_delivery_days',
        'meta_data',
        'meta_title',
        'meta_description',
        'tags',
        'view_count',
        'quotation_count',
        'order_count',
        'sort_order',
        // File Storage Fields
        'storage_type',
        'file_url',
        's3_bucket',
        's3_key',
        's3_region',
        'google_drive_file_id',
        'local_file_path',
        'file_size',
        'file_hash',
        'version',
        'requires_license',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'features' => 'array',
        'gallery_images' => 'array',
        'technical_specs' => 'array',
        'is_customizable' => 'boolean',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'requires_consultation' => 'boolean',
        'estimated_delivery_days' => 'integer',
        'meta_data' => 'array',
        'tags' => 'array',
        'view_count' => 'integer',
        'quotation_count' => 'integer',
        'order_count' => 'integer',
        'sort_order' => 'integer',
        'file_size' => 'integer',
        'requires_license' => 'boolean',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
        });

        static::updating(function ($product) {
            if ($product->isDirty('name') && empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
        });
    }

    /**
     * Get the category
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(SoftwareProductCategory::class, 'category_id');
    }

    /**
     * Get all options
     */
    public function options(): HasMany
    {
        return $this->hasMany(SoftwareProductOption::class, 'software_product_id')->orderBy('sort_order');
    }

    /**
     * Get active options
     */
    public function activeOptions(): HasMany
    {
        return $this->options()->where('is_active', true);
    }

    /**
     * Get all quotations for this product
     */
    public function quotations(): HasMany
    {
        return $this->hasMany(SoftwareQuotation::class, 'software_product_id');
    }

    /**
     * ความสัมพันธ์กับ Developer (User)
     */
    public function developer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'developer_id');
    }

    /**
     * ความสัมพันธ์กับ Software Licenses
     */
    public function licenses(): HasMany
    {
        return $this->hasMany(SoftwareLicense::class, 'software_product_id');
    }

    /**
     * ความสัมพันธ์กับ Software Downloads
     */
    public function downloads(): HasMany
    {
        return $this->hasMany(SoftwareDownload::class, 'software_product_id');
    }

    /**
     * Scope: Only active products
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Only featured products
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope: Only customizable products
     */
    public function scopeCustomizable($query)
    {
        return $query->where('is_customizable', true);
    }

    /**
     * Scope: Ordered by sort order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Increment view count
     */
    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    /**
     * Increment quotation count
     */
    public function incrementQuotationCount(): void
    {
        $this->increment('quotation_count');
    }

    /**
     * Increment order count
     */
    public function incrementOrderCount(): void
    {
        $this->increment('order_count');
    }

    /**
     * Get formatted base price
     */
    public function getFormattedBasePriceAttribute(): string
    {
        return number_format($this->base_price, 2);
    }

    /**
     * Get price display (with label if exists)
     */
    public function getPriceDisplayAttribute(): string
    {
        $price = $this->formatted_base_price;
        if ($this->price_label) {
            return "{$this->price_label} {$price} {$this->currency}";
        }
        return "{$price} {$this->currency}";
    }

    /**
     * ตรวจสอบว่ามีไฟล์หรือไม่
     */
    public function hasFile(): bool
    {
        return !empty($this->storage_type) && (
            !empty($this->file_url) ||
            !empty($this->s3_key) ||
            !empty($this->google_drive_file_id) ||
            !empty($this->local_file_path)
        );
    }

    /**
     * ตรวจสอบว่าสามารถดาวน์โหลดได้หรือไม่
     */
    public function isDownloadable(): bool
    {
        return $this->hasFile() && $this->is_active;
    }

    /**
     * ดึง URL สำหรับดาวน์โหลด (ขึ้นอยู่กับประเภทการจัดเก็บ)
     */
    public function getDownloadUrl(): ?string
    {
        if (!$this->hasFile()) {
            return null;
        }

        return match($this->storage_type) {
            'url' => $this->file_url,
            's3' => route('software.download', ['product' => $this->id]),
            'google_drive' => route('software.download', ['product' => $this->id]),
            'local' => route('software.download', ['product' => $this->id]),
            default => null,
        };
    }

    /**
     * ดึงขนาดไฟล์ในรูปแบบที่อ่านง่าย
     */
    public function getFileSizeFormattedAttribute(): ?string
    {
        if (!$this->file_size) {
            return null;
        }

        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * นับจำนวน licenses ที่ active
     */
    public function getActiveLicensesCountAttribute(): int
    {
        return $this->licenses()
            ->where('status', 'active')
            ->count();
    }

    /**
     * นับจำนวน downloads ที่สำเร็จ
     */
    public function getCompletedDownloadsCountAttribute(): int
    {
        return $this->downloads()
            ->where('status', 'completed')
            ->count();
    }
}
