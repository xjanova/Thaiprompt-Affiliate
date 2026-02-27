<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImage extends Model
{
    protected $fillable = [
        'product_id',
        'image_url',
        'thumbnail_url',
        'sort_order',
        'is_primary',
        'alt_text',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_primary' => 'boolean',
    ];

    /**
     * Get the product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Accessor: แปลง image_url เป็น URL ที่ใช้งานได้
     *
     * รองรับทั้ง full URL (https://...) และ relative path (products/xxx.webp)
     */
    public function getUrlAttribute(): ?string
    {
        if (empty($this->image_url)) {
            return null;
        }

        // เป็น full URL อยู่แล้ว
        if (filter_var($this->image_url, FILTER_VALIDATE_URL)) {
            return $this->image_url;
        }

        // เป็น relative path → ใช้ Storage::url()
        return \Illuminate\Support\Facades\Storage::url($this->image_url);
    }

    /**
     * Accessor: แปลง thumbnail_url เป็น URL ที่ใช้งานได้
     */
    public function getThumbnailAttribute(): ?string
    {
        if (empty($this->thumbnail_url)) {
            return $this->url; // fallback ใช้ภาพหลัก
        }

        if (filter_var($this->thumbnail_url, FILTER_VALIDATE_URL)) {
            return $this->thumbnail_url;
        }

        return \Illuminate\Support\Facades\Storage::url($this->thumbnail_url);
    }

    /**
     * Scope: Primary images
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }
}
