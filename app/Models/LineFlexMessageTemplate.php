<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LineFlexMessageTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'category',
        'description',
        'thumbnail',
        'flex_content',
        'is_seed',
        'is_active',
        'usage_count',
        'created_by',
    ];

    protected $casts = [
        'flex_content' => 'array',
        'is_seed' => 'boolean',
        'is_active' => 'boolean',
        'usage_count' => 'integer',
    ];

    /**
     * Get creator
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Increment usage count
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Get categories
     */
    public static function getCategories(): array
    {
        return [
            'general' => 'ทั่วไป',
            'promotion' => 'โปรโมชั่น',
            'product' => 'สินค้า',
            'notification' => 'แจ้งเตือน',
            'event' => 'กิจกรรม',
            'greeting' => 'ทักทาย',
            'info' => 'ข้อมูล',
            'custom' => 'กำหนดเอง',
        ];
    }

    /**
     * Scope: Active templates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: By category
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope: Seed templates
     */
    public function scopeSeeds($query)
    {
        return $query->where('is_seed', true);
    }
}
