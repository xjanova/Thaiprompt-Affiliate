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
     *
     * @deprecated ใช้ hasEngagedComment() แทน — เจ้าของต้องการทักทุกคอมเม้นต์
     *             แม้คนเดิมจะคอมเม้นต์ซ้ำในโพสต์เดิม (per-comment ไม่ใช่ per-post)
     */
    public static function hasEngaged(string $userId, string $postId): bool
    {
        return self::where('facebook_user_id', $userId)
            ->where('facebook_post_id', $postId)
            ->exists();
    }

    /**
     * ตรวจสอบว่าคอมเม้นต์ (ID) นี้เคยถูก engage แล้วหรือไม่
     *
     * ใช้เพื่อป้องกัน webhook retry ส่ง DM ซ้ำสำหรับคอมเม้นต์เดียวกัน
     * (ไม่ใช่ป้องกันคอมเม้นต์ซ้ำจากผู้ใช้คนเดียวกัน — อันนั้นอนุญาต)
     */
    public static function hasEngagedComment(string $commentId): bool
    {
        return self::where('facebook_comment_id', $commentId)->exists();
    }

    /**
     * 📆 ตรวจว่าผู้ใช้คนนี้เคยถูก DM จาก comment engagement ใน 24 ชม. ล่าสุดหรือไม่
     *
     * @deprecated 2026-05-20 — ใช้ hasEngagedRecently($userId, 1) แทน
     *             คงไว้เพื่อ backward compat กับ caller เดิม
     */
    public static function hasEngagedToday(string $userId): bool
    {
        return self::where('facebook_user_id', $userId)
            ->where('engaged_at', '>=', now()->subHours(24))
            ->exists();
    }

    /**
     * 📆 ตรวจว่าผู้ใช้คนนี้เคยถูก DM ใน N ชั่วโมงที่ผ่านมา (rolling hours)
     *
     * 🔁 REVERTED 2026-05-21 — เปลี่ยนกลับเป็น 24h rolling (เดิม 3 calendar days)
     *    เหตุผล: ลูกค้าตอบกลับน้อยลง ("คนเงียบเลย") — cooldown 3 วันยาวเกิน
     *    กลับมาใช้ 24h rolling เหมือนนโยบายเดิม
     *
     * @param  int  $hours  จำนวนชั่วโมง (default 24)
     */
    public static function hasEngagedRecently(string $userId, int $hours = 24): bool
    {
        return self::where('facebook_user_id', $userId)
            ->where('engaged_at', '>=', now()->subHours($hours))
            ->exists();
    }

    /**
     * 🔁 ตรวจว่าผู้ใช้คนนี้เคยถูก DM จาก comment engagement มาก่อนหรือไม่ (returning user check)
     *
     * ใช้ตัดสินว่าจะใช้ "first-time greeting" หรือ "returning-user greeting"
     * - true = คนเก่า (เคย DM แล้ว ≥ 1 ครั้ง) → ทักด้วย "กลับมาแล้วนะคะ"
     * - false = คนใหม่ → ทักด้วย "สวัสดีค่ะ ขอบคุณที่คอมเม้นต์"
     */
    public static function hasAnyEngagement(string $userId): bool
    {
        return self::where('facebook_user_id', $userId)->exists();
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
