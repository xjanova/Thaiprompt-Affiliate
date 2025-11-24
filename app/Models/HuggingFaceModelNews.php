<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Model สำหรับข่าวสาร Hugging Face Models
 *
 * จัดการข่าวสารและอัพเดทเกี่ยวกับ Hugging Face models
 *
 * @property int $id
 * @property string $title หัวข้อข่าว
 * @property string $content เนื้อหาข่าว
 * @property string|null $summary สรุปข่าว
 * @property string|null $model_id Model ID
 * @property string|null $model_name ชื่อ model
 * @property string|null $model_type ประเภท model
 * @property array|null $model_tags Tags
 * @property string $news_type ประเภทข่าว
 * @property string $importance ระดับความสำคัญ
 * @property string|null $source_url ลิงก์แหล่งข้อมูล
 * @property string|null $model_url ลิงก์ model
 * @property string|null $demo_url ลิงก์ demo
 * @property string|null $paper_url ลิงก์ paper
 * @property string|null $image_url รูปภาพ
 * @property array|null $gallery_urls รูปภาพเพิ่มเติม
 * @property int|null $model_downloads จำนวน downloads
 * @property int|null $model_likes จำนวน likes
 * @property float|null $benchmark_score คะแนน benchmark
 * @property string|null $author_name ผู้เขียน
 * @property string|null $author_organization องค์กร
 * @property bool $is_published เผยแพร่แล้ว
 * @property bool $is_featured แสดงเด่น
 * @property bool $is_pinned ปักหมุด
 * @property \Carbon\Carbon|null $published_at เวลาเผยแพร่
 * @property \Carbon\Carbon|null $featured_until แสดงเด่นจนถึง
 * @property int $view_count จำนวนครั้งที่อ่าน
 * @property int $share_count จำนวนครั้งที่แชร์
 * @property int $bookmark_count จำนวน bookmark
 * @property string|null $slug URL slug
 * @property string|null $meta_description Meta description
 * @property array|null $meta_keywords Meta keywords
 * @property array|null $metadata ข้อมูลเพิ่มเติม
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class HuggingFaceModelNews extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'huggingface_model_news';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'title',
        'content',
        'summary',
        'model_id',
        'model_name',
        'model_type',
        'model_tags',
        'news_type',
        'importance',
        'source_url',
        'model_url',
        'demo_url',
        'paper_url',
        'image_url',
        'gallery_urls',
        'model_downloads',
        'model_likes',
        'benchmark_score',
        'author_name',
        'author_organization',
        'is_published',
        'is_featured',
        'is_pinned',
        'published_at',
        'featured_until',
        'view_count',
        'share_count',
        'bookmark_count',
        'slug',
        'meta_description',
        'meta_keywords',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'model_tags' => 'array',
        'gallery_urls' => 'array',
        'model_downloads' => 'integer',
        'model_likes' => 'integer',
        'benchmark_score' => 'decimal:2',
        'is_published' => 'boolean',
        'is_featured' => 'boolean',
        'is_pinned' => 'boolean',
        'published_at' => 'datetime',
        'featured_until' => 'datetime',
        'view_count' => 'integer',
        'share_count' => 'integer',
        'bookmark_count' => 'integer',
        'meta_keywords' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Boot method
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        // สร้าง slug อัตโนมัติ
        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->title) . '-' . Str::random(6);
            }

            if (empty($model->published_at) && $model->is_published) {
                $model->published_at = now();
            }
        });
    }

    /**
     * ตรวจสอบว่าข่าวนี้แสดงเด่นอยู่หรือไม่
     *
     * @return bool
     */
    public function isFeaturedNow(): bool
    {
        if (!$this->is_featured) {
            return false;
        }

        if ($this->featured_until && $this->featured_until->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * เพิ่มจำนวนการอ่าน
     *
     * @return void
     */
    public function incrementViews(): void
    {
        $this->increment('view_count');
    }

    /**
     * เพิ่มจำนวนการแชร์
     *
     * @return void
     */
    public function incrementShares(): void
    {
        $this->increment('share_count');
    }

    /**
     * เพิ่มจำนวน bookmark
     *
     * @return void
     */
    public function incrementBookmarks(): void
    {
        $this->increment('bookmark_count');
    }

    /**
     * เผยแพร่ข่าว
     *
     * @return void
     */
    public function publish(): void
    {
        $this->is_published = true;
        $this->published_at = now();
        $this->save();
    }

    /**
     * ยกเลิกการเผยแพร่
     *
     * @return void
     */
    public function unpublish(): void
    {
        $this->is_published = false;
        $this->save();
    }

    /**
     * ตั้งเป็นข่าวเด่น
     *
     * @param int|null $days จำนวนวันที่แสดงเด่น
     * @return void
     */
    public function feature(?int $days = null): void
    {
        $this->is_featured = true;

        if ($days !== null) {
            $this->featured_until = now()->addDays($days);
        }

        $this->save();
    }

    /**
     * ยกเลิกข่าวเด่น
     *
     * @return void
     */
    public function unfeature(): void
    {
        $this->is_featured = false;
        $this->featured_until = null;
        $this->save();
    }

    /**
     * Scope: เฉพาะข่าวที่เผยแพร่แล้ว
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * Scope: เฉพาะข่าวเด่น
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true)
            ->where(function ($q) {
                $q->whereNull('featured_until')
                    ->orWhere('featured_until', '>', now());
            });
    }

    /**
     * Scope: เฉพาะข่าวที่ปักหมุด
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    /**
     * Scope: ตามประเภทข่าว
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('news_type', $type);
    }

    /**
     * Scope: ตามระดับความสำคัญ
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $importance
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeImportant($query, string $importance = 'high')
    {
        return $query->where('importance', $importance);
    }

    /**
     * Scope: เฉพาะข่าวล่าสุด
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $days จำนวนวัน
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('published_at', '>=', now()->subDays($days));
    }

    /**
     * Scope: ค้นหาข้อความ
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $search
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
                ->orWhere('content', 'like', "%{$search}%")
                ->orWhere('summary', 'like', "%{$search}%")
                ->orWhere('model_name', 'like', "%{$search}%");
        });
    }

    /**
     * ได้รับ URL เต็ม
     *
     * @return string
     */
    public function getUrlAttribute(): string
    {
        return route('admin.ai-rental.news.show', $this->slug);
    }
}
