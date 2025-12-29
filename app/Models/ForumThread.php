<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * ForumThread Model - กระทู้/หัวข้อในฟอรั่ม
 *
 * @property int $id
 * @property int $category_id หมวดหมู่
 * @property int $user_id ผู้สร้างกระทู้
 * @property string $title หัวข้อกระทู้
 * @property string $slug Slug สำหรับ URL
 * @property string $content เนื้อหากระทู้ (HTML/Markdown)
 * @property string|null $excerpt บทคัดย่อ
 * @property bool $is_pinned ปักหมุดไว้ด้านบน
 * @property bool $is_locked ล็อคไม่ให้ตอบ
 * @property bool $is_featured กระทู้แนะนำ
 * @property int $views_count จำนวนการเข้าชม
 * @property int $posts_count จำนวนคอมเมนต์
 * @property int $likes_count จำนวนไลค์
 * @property int|null $last_post_user_id ผู้ตอบกลับล่าสุด
 * @property \Carbon\Carbon|null $last_post_at เวลาตอบกลับล่าสุด
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property-read ForumCategory $category
 * @property-read User $user
 * @property-read User|null $lastPostUser
 * @property-read \Illuminate\Database\Eloquent\Collection|ForumPost[] $posts
 * @property-read \Illuminate\Database\Eloquent\Collection|ForumLike[] $likes
 */
class ForumThread extends Model
{
    use SoftDeletes;

    /**
     * ชื่อตาราง
     *
     * @var string
     */
    protected $table = 'forum_threads';

    /**
     * คอลัมน์ที่สามารถ mass assign ได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'category_id',
        'user_id',
        'title',
        'slug',
        'content',
        'excerpt',
        'is_pinned',
        'is_locked',
        'is_featured',
        'views_count',
        'posts_count',
        'likes_count',
        'last_post_user_id',
        'last_post_at',
    ];

    /**
     * การ cast ค่า
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_pinned' => 'boolean',
        'is_locked' => 'boolean',
        'is_featured' => 'boolean',
        'views_count' => 'integer',
        'posts_count' => 'integer',
        'likes_count' => 'integer',
        'last_post_at' => 'datetime',
    ];

    /**
     * Boot model
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        // สร้าง slug และ excerpt อัตโนมัติ
        static::creating(function ($thread) {
            if (empty($thread->slug)) {
                $thread->slug = Str::slug($thread->title);

                // ตรวจสอบ slug ซ้ำ
                $count = static::where('slug', 'like', $thread->slug.'%')->count();
                if ($count > 0) {
                    $thread->slug .= '-'.($count + 1);
                }
            }

            if (empty($thread->excerpt)) {
                $thread->excerpt = Str::limit(strip_tags($thread->content), 200);
            }
        });

        // อัปเดตสถิติเมื่อสร้างกระทู้
        static::created(function ($thread) {
            $thread->category->increment('threads_count');
            $thread->user->increment('forum_threads_count');
        });

        // อัปเดตสถิติเมื่อลบกระทู้
        static::deleted(function ($thread) {
            $thread->category->decrement('threads_count');
            $thread->user->decrement('forum_threads_count');
        });
    }

    /**
     * ความสัมพันธ์กับหมวดหมู่
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ForumCategory::class, 'category_id');
    }

    /**
     * ความสัมพันธ์กับผู้สร้าง
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ความสัมพันธ์กับผู้ตอบกลับล่าสุด
     */
    public function lastPostUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_post_user_id');
    }

    /**
     * ความสัมพันธ์กับคอมเมนต์
     */
    public function posts(): HasMany
    {
        return $this->hasMany(ForumPost::class, 'thread_id');
    }

    /**
     * ความสัมพันธ์กับไลค์ (polymorphic)
     */
    public function likes(): MorphMany
    {
        return $this->morphMany(ForumLike::class, 'likeable');
    }

    /**
     * ความสัมพันธ์กับรายงาน (polymorphic)
     */
    public function reports(): MorphMany
    {
        return $this->morphMany(ForumReport::class, 'reportable');
    }

    /**
     * Scope: กระทู้ที่ปักหมุด
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    /**
     * Scope: กระทู้แนะนำ
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope: กระทู้ที่ไม่ถูกล็อค
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnlocked($query)
    {
        return $query->where('is_locked', false);
    }

    /**
     * Scope: เรียงตามกิจกรรมล่าสุด
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLatestActivity($query)
    {
        return $query->orderByDesc('is_pinned')
            ->orderByDesc('last_post_at')
            ->orderByDesc('created_at');
    }

    /**
     * Scope: กระทู้ยอดนิยม (ไลค์เยอะ)
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePopular($query)
    {
        return $query->orderByDesc('likes_count')
            ->orderByDesc('views_count');
    }

    /**
     * เพิ่มจำนวนการเข้าชม
     */
    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    /**
     * อัปเดตการตอบกลับล่าสุด
     */
    public function updateLastPost(int $userId): void
    {
        $this->update([
            'last_post_user_id' => $userId,
            'last_post_at' => now(),
        ]);
    }

    /**
     * ตรวจสอบว่าผู้ใช้กดไลค์กระทู้นี้หรือยัง
     *
     * @param  User|int  $user
     */
    public function isLikedBy($user): bool
    {
        $userId = $user instanceof User ? $user->id : $user;

        return $this->likes()->where('user_id', $userId)->exists();
    }

    /**
     * ดึง URL ของกระทู้
     */
    public function getUrlAttribute(): string
    {
        return route('forum.thread.show', $this->slug);
    }

    /**
     * ดึงเวลาที่มีกิจกรรมล่าสุด
     *
     * @return \Carbon\Carbon
     */
    public function getLastActivityAttribute()
    {
        return $this->last_post_at ?? $this->created_at;
    }
}
