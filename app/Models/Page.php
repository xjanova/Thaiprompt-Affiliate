<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Page extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'title',
        'slug',
        'type',
        'content',
        'meta_data',
        'is_published',
        'sort_order',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'meta_data' => 'array',
            'is_published' => 'boolean',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($page) {
            if (empty($page->slug)) {
                $page->slug = Str::slug($page->title);
            }
        });

        static::updating(function ($page) {
            if ($page->isDirty('title') && empty($page->slug)) {
                $page->slug = Str::slug($page->title);
            }
        });
    }

    /**
     * Scope for published pages
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope for specific type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get page types
     */
    public static function getTypes(): array
    {
        return [
            'about' => 'เกี่ยวกับเรา',
            'faq' => 'คำถามที่พบบ่อย',
            'contact' => 'ติดต่อเรา',
            'terms' => 'ข้อกำหนดการใช้งาน',
            'privacy' => 'นโยบายความเป็นส่วนตัว',
            'custom' => 'หน้าอื่นๆ',
        ];
    }
}
