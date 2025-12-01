<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * ForumLike Model - การกดไลค์ (หัวใจ)
 *
 * ใช้ระบบ polymorphic เพื่อรองรับทั้ง threads และ posts
 *
 * @property int $id
 * @property int $user_id ผู้กดไลค์
 * @property string $likeable_type ประเภท (thread/post)
 * @property int $likeable_id ID ของ item
 * @property \Carbon\Carbon $created_at
 *
 * @property-read User $user
 * @property-read ForumThread|ForumPost $likeable
 */
class ForumLike extends Model
{
    /**
     * ปิด updated_at
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * ชื่อตาราง
     *
     * @var string
     */
    protected $table = 'forum_likes';

    /**
     * คอลัมน์ที่สามารถ mass assign ได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'likeable_type',
        'likeable_id',
        'created_at',
    ];

    /**
     * การ cast ค่า
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * Boot model
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        // ตั้ง created_at อัตโนมัติ
        static::creating(function ($like) {
            $like->created_at = now();
        });

        // อัปเดตสถิติเมื่อกดไลค์
        static::created(function ($like) {
            // เพิ่มจำนวน likes_count ของ item
            $like->likeable->increment('likes_count');

            // เพิ่ม likes_received ให้เจ้าของ content
            $like->likeable->user->increment('forum_likes_received');

            // เพิ่ม likes_given ให้ผู้กดไลค์
            $like->user->increment('forum_likes_given');

            // ตรวจสอบและให้โทรฟี่อัตโนมัติ
            app(\App\Services\ForumTrophyService::class)->checkAndAwardTrophies($like->likeable->user);
        });

        // อัปเดตสถิติเมื่อยกเลิกไลค์
        static::deleted(function ($like) {
            // ลดจำนวน likes_count ของ item
            $like->likeable->decrement('likes_count');

            // ลด likes_received ของเจ้าของ content
            $like->likeable->user->decrement('forum_likes_received');

            // ลด likes_given ของผู้กดไลค์
            $like->user->decrement('forum_likes_given');
        });
    }

    /**
     * ความสัมพันธ์กับผู้กดไลค์
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ความสัมพันธ์ polymorphic กับ item ที่ถูกไลค์
     *
     * @return MorphTo
     */
    public function likeable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Toggle like - กด/ยกเลิกไลค์
     *
     * @param User $user
     * @param Model $likeable
     * @return array{liked: bool, likes_count: int}
     */
    public static function toggleLike(User $user, Model $likeable): array
    {
        $like = self::where('user_id', $user->id)
            ->where('likeable_type', get_class($likeable))
            ->where('likeable_id', $likeable->id)
            ->first();

        if ($like) {
            // ยกเลิกไลค์
            $like->delete();
            $liked = false;
        } else {
            // กดไลค์
            self::create([
                'user_id' => $user->id,
                'likeable_type' => get_class($likeable),
                'likeable_id' => $likeable->id,
            ]);
            $liked = true;
        }

        // รีเฟรช likes_count
        $likeable->refresh();

        return [
            'liked' => $liked,
            'likes_count' => $likeable->likes_count,
        ];
    }
}
