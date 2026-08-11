<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TarotCardBackImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'image_url',
        'is_default',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Scope for active images
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for default image
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Get default card back image
     */
    public static function getDefault()
    {
        return static::where('is_default', true)
            ->where('is_active', true)
            ->first() ?? static::where('is_active', true)->first();
    }

    /**
     * Get raw image_url value from database (for debugging/deletion)
     */
    public function getRawImageUrl(): ?string
    {
        return $this->attributes['image_url'] ?? null;
    }

    /**
     * Get image URL with asset helper
     *
     * รูปหลังไพ่เก็บใน storage/app/public/tarot/card-backs/
     * และเข้าถึงผ่าน /storage/tarot/card-backs/xxx.webp
     */
    public function getImageUrlAttribute($value)
    {
        // ถ้าไม่มีค่า ใช้ default — ภาพหลังไพ่ลายกนกทอง (public/images/art/tarot-card-back.webp)
        // เผื่อไฟล์ยังไม่ถูก deploy ให้ตกกลับไปที่ SVG ตัวเดิม
        if (! $value) {
            return file_exists(public_path('images/art/tarot-card-back.webp'))
                ? asset('images/art/tarot-card-back.webp')
                : asset('images/tarot/card-back-default.svg');
        }

        // ถ้าเป็น full URL อยู่แล้ว (http/https) ใช้ตรงๆ
        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }

        // ถ้าเป็น URL แบบ /storage/... (จาก upload ใหม่)
        // Trust database value และ return asset URL
        if (str_starts_with($value, '/storage/')) {
            return asset($value);
        }

        // ถ้าเป็น path แบบไม่มี / นำหน้า (เช่น tarot/card-backs/xxx.webp)
        // แปลงเป็น storage URL
        if (str_starts_with($value, 'tarot/')) {
            return asset('storage/'.$value);
        }

        // ถ้าเป็น path แบบเดิม (เช่น /images/tarot/xxx.png)
        if (str_starts_with($value, '/images/') || str_starts_with($value, 'images/')) {
            return asset($value);
        }

        // สำหรับ path อื่นๆ ที่ไม่รู้จัก ลอง return ตรงๆ
        return asset($value);
    }

    /**
     * Boot method to handle default logic
     */
    protected static function boot()
    {
        parent::boot();

        // When setting a new default, remove default from others
        static::saving(function ($model) {
            if ($model->is_default) {
                static::where('id', '!=', $model->id)
                    ->update(['is_default' => false]);
            }
        });
    }
}
