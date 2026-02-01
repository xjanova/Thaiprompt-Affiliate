<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Fortune Comment Engagement
 *
 * ติดตามการ engage คอมเม้นต์ เพื่อป้องกันส่งซ้ำ
 *
 * @property int $id
 * @property string $facebook_user_id
 * @property string $facebook_post_id
 * @property string $facebook_comment_id
 * @property string|null $comment_text
 * @property string|null $comment_reply
 * @property string|null $dm_message
 * @property array|null $user_profile
 * @property \Carbon\Carbon|null $engaged_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class FortuneCommentEngagement extends Model
{
    protected $table = 'fortune_comment_engagements';

    protected $fillable = [
        'facebook_user_id',
        'facebook_post_id',
        'facebook_comment_id',
        'comment_text',
        'comment_reply',
        'dm_message',
        'user_profile',
        'engaged_at',
    ];

    protected $casts = [
        'user_profile' => 'array',
        'engaged_at' => 'datetime',
    ];

    /**
     * ตรวจสอบว่า user นี้เคยถูก engage ในโพสต์นี้แล้วหรือไม่
     */
    public static function hasEngaged(string $userId, string $postId): bool
    {
        return self::where('facebook_user_id', $userId)
            ->where('facebook_post_id', $postId)
            ->exists();
    }

    /**
     * Scope: ตาม Facebook User
     */
    public function scopeByFacebookUser($query, string $facebookUserId)
    {
        return $query->where('facebook_user_id', $facebookUserId);
    }

    /**
     * Scope: วันนี้
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }
}
