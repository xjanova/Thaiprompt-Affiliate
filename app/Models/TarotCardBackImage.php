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
     * Get image URL with asset helper
     *
     * รูปหลังไพ่เก็บใน storage/app/public/tarot/card-backs/
     * และเข้าถึงผ่าน /storage/tarot/card-backs/xxx.webp
     */
    public function getImageUrlAttribute($value)
    {
        if (!$value) {
            return asset('images/tarot/card-back-default.svg');
        }

        // ถ้าเป็น URL แบบ /storage/... ให้ตรวจสอบว่าไฟล์มีอยู่จริงใน storage
        if (str_starts_with($value, '/storage/')) {
            $storagePath = str_replace('/storage/', '', $value);
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($storagePath)) {
                return asset($value);
            }
        }

        // ถ้าเป็น path แบบเดิม (เช่น /images/tarot/xxx.png)
        if (str_starts_with($value, '/images/') || str_starts_with($value, 'images/')) {
            if (file_exists(public_path($value))) {
                return asset($value);
            }
        }

        // ถ้าเป็น full URL อยู่แล้ว
        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }

        return asset('images/tarot/card-back-default.svg');
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
