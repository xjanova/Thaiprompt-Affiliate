<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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

    use \App\Models\Concerns\BelongsToFortunePage;   // 🏬 (2026-08-10) ระบบสาขา

    protected $fillable = [
        'fortune_page_id',   // 🏬 สาขา/เพจต้นทาง
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
     * เพดานการตอบคอมเมนต์สาธารณะ ต่อ 1 คน ต่อ 24 ชม.
     *
     * ไม่เกี่ยวกับโควตา DM — คนเดิมเม้นต์หลายคลิปต้องได้คำตอบทุกคลิป (เจ้าของสั่ง)
     * แต่ต้องมีเพดานไว้ ไม่งั้นคนที่ไล่เม้นต์ 30 คลิปรวดจะได้ตอบครบ 30 = ดูเป็นบอทชัด
     */
    public const MAX_PUBLIC_REPLIES_PER_DAY = 5;

    /**
     * 🏬 จำกัดคิวรีให้อยู่ในเพจเดียว
     *
     * 🚨 ทำไมต้องมี ทั้งที่ PSID ของเฟซบุ๊กแยกตามเพจอยู่แล้ว (2026-08-27)
     *   วันนี้โควตาไม่ไหลข้ามเพจ — แต่เป็นเพราะ **คุณสมบัติของ PSID** ไม่ใช่เพราะโค้ดตั้งใจ
     *   วันไหนเปลี่ยนไปเก็บ ASID (id ระดับแอป ซึ่งเหมือนกันทุกเพจ) โควตาจะรวมกันทันที
     *   โดยไม่มีอะไรเตือน: คนเม้นต์เพจ ก. 5 ครั้ง แล้วเพจ ข. จะเงียบใส่เขาทั้งวัน
     *   ⇒ เขียนเงื่อนไขให้ชัด ไม่พึ่งพฤติกรรมของ id ที่เราไม่ได้เป็นคนกำหนด
     *   (กฎเดียวกับเบรกเกอร์ FB ที่เคยเป็น global แล้วเพจไร้สิทธิ์ลากเพจที่มีสิทธิ์ตายไปด้วย)
     *
     * `$pageId = null` = ไม่ระบุเพจ ⇒ นับรวมทุกเพจ (พฤติกรรมเดิม ใช้กับ caller เก่าที่ยังไม่ส่งมา)
     */
    private static function scopePage(Builder $q, ?int $pageId): Builder
    {
        return $pageId === null ? $q : $q->where('fortune_page_id', $pageId);
    }

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
    /**
     * @deprecated 2026-08-23 — กำกวมหลังแยกนับ DM/คอมเมนต์
     *             ใช้ hasDmRecently() ถ้าจะถามเรื่อง DM (เกือบทุกที่ต้องการอันนี้)
     *             เพราะตอนนี้มีแถวที่ "ตอบคอมเมนต์อย่างเดียว ไม่ได้ DM" แล้ว
     *             ถ้ายังใช้ตัวนี้ตัดสินใจเรื่อง DM = ตอบคอมเมนต์ไปกินสิทธิ์ DM
     */
    public static function hasEngagedRecently(string $userId, int $hours = 24): bool
    {
        return self::where('facebook_user_id', $userId)
            ->where('engaged_at', '>=', now()->subHours($hours))
            ->exists();
    }

    /**
     * 💰 เคยได้ "DM" จากสายคอมเมนต์ ใน N ชม.ล่าสุดไหม
     *
     * นับเฉพาะแถวที่ส่ง DM จริง (dm_message ไม่ว่าง) — แถวที่ตอบคอมเมนต์อย่างเดียวไม่นับ
     *
     * 🚨 เจ้าของสั่ง (2026-08-23): "ต้องแยกนับ dm กับ คอมเม้นต์ เพราะเราปิดการขายผ่าน dm
     *    dm ต้องส่ง แม้คอมเม้นจะตอบไปแล้ว" ⇒ ห้ามให้การตอบคอมเมนต์มากินโควตา DM
     *
     * @param  int  $hours  จำนวนชั่วโมง (default 24)
     */
    public static function hasDmRecently(string $userId, int $hours = 24, ?int $pageId = null): bool
    {
        return self::scopePage(self::where('facebook_user_id', $userId), $pageId)
            ->where('engaged_at', '>=', now()->subHours($hours))
            ->whereNotNull('dm_message')
            ->where('dm_message', '!=', '')
            ->exists();
    }

    /**
     * 💬 ตอบคอมเมนต์สาธารณะให้คนนี้ไปกี่ครั้งแล้วใน N ชม.ล่าสุด
     *
     * ใช้คุมไม่ให้คนเดิมได้คำตอบรัวเกินไปจนดูเป็นบอท (คนละตัวกับโควตา DM)
     *
     * @param  int  $hours  จำนวนชั่วโมง (default 24)
     */
    public static function publicReplyCountRecent(string $userId, int $hours = 24, ?int $pageId = null): int
    {
        return self::scopePage(self::where('facebook_user_id', $userId), $pageId)
            ->where('engaged_at', '>=', now()->subHours($hours))
            ->whereNotNull('comment_reply')
            ->where('comment_reply', '!=', '')
            ->count();
    }

    /**
     * 💬 คนนี้ยังเหลือสิทธิ์ให้ตอบคอมเมนต์อยู่ไหม (เพจนี้)
     *
     * รวมการอ่านเพดานจากหลังบ้าน + การนับ ไว้ที่เดียว — เดิมกระจายอยู่ 2 จุด
     * (`FacebookWebhookController` กับ `ProcessCommentEngagement`) แล้วต้องแก้ให้ตรงกันทุกครั้ง
     *
     * @param  int|null  $pageId  จำกัดเฉพาะเพจนี้ (null = นับรวมทุกเพจ)
     *
     * @example
     * if (! FortuneCommentEngagement::hasPublicReplyQuota($psid, $pageId)) { return; }
     */
    public static function hasPublicReplyQuota(string $userId, ?int $pageId = null, int $hours = 24): bool
    {
        // getSettings() = ตัวที่รู้จักสาขา + memo 5 วินาที (ไม่ยิง DB ทุกคอมเมนต์)
        // และ merge `settings_override` ของเพจให้ด้วย ⇒ เพจตั้งเพดานของตัวเองได้ถ้าต้องการ
        try {
            $cap = FortuneTellingSetting::getSettings()->publicCommentReplyDailyCap();
        } catch (\Throwable) {
            $cap = self::MAX_PUBLIC_REPLIES_PER_DAY;
        }

        // 0 = ไม่จำกัด (ห้ามตีความว่าปิด — การปิดใช้สวิตช์ enable_public_comment_reply)
        if ($cap <= 0) {
            return true;
        }

        return self::publicReplyCountRecent($userId, $hours, $pageId) < $cap;
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
