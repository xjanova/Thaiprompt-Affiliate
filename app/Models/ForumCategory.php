<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * ForumCategory Model - หมวดหมู่ฟอรั่ม
 *
 * @property int $id
 * @property int|null $parent_id หมวดหมู่หลัก (สำหรับ sub-category)
 * @property string $name ชื่อหมวดหมู่
 * @property string $slug Slug สำหรับ URL
 * @property string|null $description คำอธิบาย
 * @property string|null $icon Font Awesome icon class
 * @property string|null $icon_color สีไอคอน
 * @property string|null $gradient_from สี gradient เริ่มต้น
 * @property string|null $gradient_to สี gradient สิ้นสุด
 * @property int $display_order ลำดับการแสดงผล
 * @property bool $is_active เปิด/ปิดหมวดหมู่
 * @property bool $is_locked ล็อคไม่ให้สร้างกระทู้ใหม่
 * @property int $threads_count จำนวนกระทู้ (cached)
 * @property int $posts_count จำนวนโพสต์ (cached)
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 *
 * @property-read ForumCategory|null $parent
 * @property-read \Illuminate\Database\Eloquent\Collection|ForumCategory[] $children
 * @property-read \Illuminate\Database\Eloquent\Collection|ForumThread[] $threads
 */
class ForumCategory extends Model
{
    use SoftDeletes;

    /**
     * ชื่อตาราง
     *
     * @var string
     */
    protected $table = 'forum_categories';

    /**
     * คอลัมน์ที่สามารถ mass assign ได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'description',
        'icon',
        'icon_color',
        'gradient_from',
        'gradient_to',
        'display_order',
        'is_active',
        'is_locked',
        'threads_count',
        'posts_count',
    ];

    /**
     * การ cast ค่า
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'is_locked' => 'boolean',
        'threads_count' => 'integer',
        'posts_count' => 'integer',
        'display_order' => 'integer',
    ];

    /**
     * Boot model
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        // สร้าง slug อัตโนมัติจากชื่อ
        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);

                // ตรวจสอบ slug ซ้ำ
                $count = static::where('slug', 'like', $category->slug . '%')->count();
                if ($count > 0) {
                    $category->slug .= '-' . ($count + 1);
                }
            }
        });
    }

    /**
     * ความสัมพันธ์กับหมวดหมู่หลัก
     *
     * @return BelongsTo
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ForumCategory::class, 'parent_id');
    }

    /**
     * ความสัมพันธ์กับหมวดหมู่ย่อย
     *
     * @return HasMany
     */
    public function children(): HasMany
    {
        return $this->hasMany(ForumCategory::class, 'parent_id')
            ->orderBy('display_order')
            ->orderBy('name');
    }

    /**
     * ความสัมพันธ์กับกระทู้
     *
     * @return HasMany
     */
    public function threads(): HasMany
    {
        return $this->hasMany(ForumThread::class, 'category_id');
    }

    /**
     * Scope: หมวดหมู่ที่เปิดใช้งาน
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: หมวดหมู่หลัก (ไม่มี parent)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeParentCategories($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope: เรียงตาม display_order
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('name');
    }

    /**
     * อัปเดตสถิติ
     *
     * @return void
     */
    public function updateStats(): void
    {
        $this->threads_count = $this->threads()->count();
        $this->posts_count = ForumPost::whereHas('thread', function ($query) {
            $query->where('category_id', $this->id);
        })->count();
        $this->save();
    }

    /**
     * ดึง URL ของหมวดหมู่
     *
     * @return string
     */
    public function getUrlAttribute(): string
    {
        return route('forum.category', $this->slug);
    }

    /**
     * ดึง gradient class
     *
     * @return string
     */
    public function getGradientClassAttribute(): string
    {
        if ($this->gradient_from && $this->gradient_to) {
            return "from-{$this->gradient_from} to-{$this->gradient_to}";
        }
        return 'from-blue-500 to-purple-600';
    }
}
