<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Fortune Post Reaction
 *
 * ติดตาม reactions (like, love ฯลฯ) ที่ user กดบนโพสต์เพจ
 *
 * @property int $id
 * @property string $facebook_user_id
 * @property string $facebook_post_id
 * @property string|null $reaction_type like, love, wow, haha, sad, angry
 * @property string|null $verb add, remove, edit
 * @property string|null $user_name
 * @property bool $dm_attempted
 * @property bool $dm_success
 * @property \Carbon\Carbon|null $reacted_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class FortunePostReaction extends Model
{
    protected $table = 'fortune_post_reactions';

    use \App\Models\Concerns\BelongsToFortunePage;   // 🏬 (2026-08-10) ระบบสาขา

    protected $fillable = [
        'fortune_page_id',   // 🏬 สาขา/เพจต้นทาง
        'facebook_user_id',
        'facebook_post_id',
        'reaction_type',
        'verb',
        'user_name',
        'dm_attempted',
        'dm_success',
        'reacted_at',
    ];

    protected $casts = [
        'dm_attempted' => 'boolean',
        'dm_success' => 'boolean',
        'reacted_at' => 'datetime',
    ];

    /**
     * 🏬 จำกัด query ตามเพจ (สาขา) — pattern เดียวกับ FortuneCommentEngagement::scopePage
     *
     * `$pageId = null` = ไม่ระบุเพจ ⇒ นับรวมทุกเพจ (พฤติกรรมเดิม ใช้กับ caller เก่าที่ยังไม่ส่งมา)
     */
    private static function scopePage(\Illuminate\Database\Eloquent\Builder $q, ?int $pageId): \Illuminate\Database\Eloquent\Builder
    {
        return $pageId === null ? $q : $q->where('fortune_page_id', $pageId);
    }

    /**
     * ตรวจสอบว่า user นี้เคยกด reaction ในโพสต์ใดมาก่อนหรือไม่
     * ใช้ประเมิน "warm lead" ตอน user comment ครั้งแรก
     */
    public static function hasReacted(string $userId): bool
    {
        return self::where('facebook_user_id', $userId)->exists();
    }

    /**
     * ตรวจสอบว่า user reaction บนโพสต์เฉพาะนี้
     */
    public static function hasReactedOnPost(string $userId, string $postId): bool
    {
        return self::where('facebook_user_id', $userId)
            ->where('facebook_post_id', $postId)
            ->exists();
    }

    /**
     * Scope: reaction ที่ยังไม่ได้ลองส่ง DM
     */
    public function scopeUnattempted($query)
    {
        return $query->where('dm_attempted', false);
    }

    /**
     * 📆 ตรวจว่าเคยส่ง DM "สำเร็จ" ให้ user คนนี้ใน N ชั่วโมงที่ผ่านมา (rolling hours)
     *
     * 🔁 REVERTED 2026-05-21 — เปลี่ยนกลับเป็น 24h rolling (เดิม 3 calendar days)
     *    เหตุผล: ลูกค้าตอบกลับน้อยลง ("คนเงียบเลย") — cooldown 3 วันยาวเกิน
     *    กลับมาใช้ 24h rolling เหมือนนโยบายเดิม
     *
     * 🏬 (2026-09-01) นับแยกรายเพจ (pageId) — ให้สมมาตรกับ FortuneCommentEngagement::hasDmRecently
     *    เดิมนับรวมทุกเพจ ⇒ ลูกค้าที่ได้ DM จากสาขา A แล้ว จะไม่ได้ DM จากสาขา B ทั้งวัน
     *
     * @param  int  $hours  จำนวนชั่วโมง (default 24)
     */
    public static function hasDmSuccessRecently(string $userId, int $hours = 24, ?int $pageId = null): bool
    {
        return self::scopePage(self::where('facebook_user_id', $userId), $pageId)
            ->where('dm_success', true)
            ->where('updated_at', '>=', now()->subHours($hours))
            ->exists();
    }

    /**
     * 🔁 ตรวจว่าเคยส่ง DM "สำเร็จ" ให้ user คนนี้มาก่อนหรือไม่ (returning user check)
     *
     * ใช้ตัดสินว่าจะใช้ "first-time greeting" หรือ "returning-user greeting"
     */
    public static function hasEverDmSuccess(string $userId): bool
    {
        return self::where('facebook_user_id', $userId)
            ->where('dm_success', true)
            ->exists();
    }
}
