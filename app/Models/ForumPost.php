<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ForumPost Model - คอมเมนต์/การตอบกลับในกระทู้
 *
 * @property int $id
 * @property int $thread_id กระทู้ที่ตอบ
 * @property int $user_id ผู้ตอบ
 * @property int|null $parent_id โพสต์แม่ (สำหรับ nested replies)
 * @property string $content เนื้อหา (HTML/Markdown)
 * @property int $likes_count จำนวนไลค์
 * @property bool $is_best_answer เป็นคำตอบที่ดีที่สุด
 * @property bool $is_hidden ถูกซ่อน (แบน)
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 *
 * @property-read ForumThread $thread
 * @property-read User $user
 * @property-read ForumPost|null $parent
 * @property-read \Illuminate\Database\Eloquent\Collection|ForumPost[] $replies
 * @property-read \Illuminate\Database\Eloquent\Collection|ForumLike[] $likes
 */
class ForumPost extends Model
{
    use SoftDeletes;

    /**
     * ชื่อตาราง
     *
     * @var string
     */
    protected $table = 'forum_posts';

    /**
     * คอลัมน์ที่สามารถ mass assign ได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'thread_id',
        'user_id',
        'parent_id',
        'content',
        'likes_count',
        'is_best_answer',
        'is_hidden',
    ];

    /**
     * การ cast ค่า
     *
     * @var array<string, string>
     */
    protected $casts = [
        'likes_count' => 'integer',
        'is_best_answer' => 'boolean',
        'is_hidden' => 'boolean',
    ];

    /**
     * Boot model
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        // อัปเดตสถิติเมื่อสร้างโพสต์
        static::created(function ($post) {
            $post->thread->increment('posts_count');
            $post->thread->updateLastPost($post->user_id);
            $post->thread->category->increment('posts_count');
            $post->user->increment('forum_posts_count');
        });

        // อัปเดตสถิติเมื่อลบโพสต์
        static::deleted(function ($post) {
            $post->thread->decrement('posts_count');
            $post->thread->category->decrement('posts_count');
            $post->user->decrement('forum_posts_count');
        });
    }

    /**
     * ความสัมพันธ์กับกระทู้
     *
     * @return BelongsTo
     */
    public function thread(): BelongsTo
    {
        return $this->belongsTo(ForumThread::class, 'thread_id');
    }

    /**
     * ความสัมพันธ์กับผู้เขียน
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ความสัมพันธ์กับโพสต์แม่
     *
     * @return BelongsTo
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ForumPost::class, 'parent_id');
    }

    /**
     * ความสัมพันธ์กับโพสต์ลูก (nested replies)
     *
     * @return HasMany
     */
    public function replies(): HasMany
    {
        return $this->hasMany(ForumPost::class, 'parent_id')
            ->orderBy('created_at');
    }

    /**
     * ความสัมพันธ์กับไลค์ (polymorphic)
     *
     * @return MorphMany
     */
    public function likes(): MorphMany
    {
        return $this->morphMany(ForumLike::class, 'likeable');
    }

    /**
     * ความสัมพันธ์กับรายงาน (polymorphic)
     *
     * @return MorphMany
     */
    public function reports(): MorphMany
    {
        return $this->morphMany(ForumReport::class, 'reportable');
    }

    /**
     * Scope: โพสต์ที่ไม่ถูกซ่อน
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeVisible($query)
    {
        return $query->where('is_hidden', false);
    }

    /**
     * Scope: โพสต์ระดับบนสุด (ไม่ใช่ reply)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope: โพสต์ที่เป็น best answer
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBestAnswers($query)
    {
        return $query->where('is_best_answer', true);
    }

    /**
     * ตั้งเป็นคำตอบที่ดีที่สุด
     *
     * @return void
     */
    public function markAsBestAnswer(): void
    {
        // ยกเลิก best answer เดิมในกระทู้เดียวกัน
        $this->thread->posts()->where('is_best_answer', true)->update(['is_best_answer' => false]);

        // ตั้งโพสต์นี้เป็น best answer
        $this->update(['is_best_answer' => true]);

        // เพิ่มสถิติให้ผู้ใช้
        $this->user->increment('forum_best_answers_count');
    }

    /**
     * ตรวจสอบว่าผู้ใช้กดไลค์โพสต์นี้หรือยัง
     *
     * @param User|int $user
     * @return bool
     */
    public function isLikedBy($user): bool
    {
        $userId = $user instanceof User ? $user->id : $user;
        return $this->likes()->where('user_id', $userId)->exists();
    }

    /**
     * ซ่อนโพสต์
     *
     * @return void
     */
    public function hide(): void
    {
        $this->update(['is_hidden' => true]);
    }

    /**
     * แสดงโพสต์
     *
     * @return void
     */
    public function show(): void
    {
        $this->update(['is_hidden' => false]);
    }
}
