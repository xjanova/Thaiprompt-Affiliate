<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FortuneCommentLinkBlock Model
 *
 * บันทึกเหตุการณ์ "เจอลิงก์ภายนอกในคอมเมนต์ → บล็อกคนโพสต์"
 *
 * บอทซ่อนคอมเมนต์เองไม่ได้ (Page token ยังไม่มี pages_manage_engagement — ติด App Review)
 * จึงทำได้แค่บล็อกคนโพสต์ แล้วเก็บ permalink ไว้ให้แอดมินกดไปลบคอมเมนต์เอง
 *
 * @property int $id
 * @property string $platform ช่องทาง — facebook
 * @property string $platform_user_id PSID ของคนโพสต์
 * @property string|null $display_name ชื่อบน Facebook
 * @property string $comment_id comment_id จาก webhook
 * @property string|null $post_id post_id ของโพสต์/Reel
 * @property string|null $permalink ลิงก์เด้งไปที่คอมเมนต์
 * @property string|null $message ข้อความคอมเมนต์
 * @property string|null $matched_domain โดเมนที่ทำให้ถูกตัด
 * @property string $detected_from text | attachment
 * @property bool $page_blocked บล็อกบนเพจสำเร็จ
 * @property string|null $block_error เหตุผลที่บล็อกไม่สำเร็จ
 * @property bool $bot_banned แบนระดับบอทสำเร็จ
 * @property bool $hide_succeeded ซ่อนคอมเมนต์สำเร็จ
 * @property string $status blocked | unblocked | detect_only
 * @property bool $is_read แอดมินจัดการแล้ว
 * @property bool $comment_deleted แอดมินยืนยันว่าลบคอมเมนต์แล้ว
 * @property \Carbon\Carbon|null $blocked_at
 * @property \Carbon\Carbon|null $unblocked_at
 * @property int|null $unblocked_by
 */
class FortuneCommentLinkBlock extends Model
{
    /**
     * @var string
     */
    protected $table = 'fortune_comment_link_blocks';

    /**
     * @var array<string>
     */
    protected $fillable = [
        'platform',
        'platform_user_id',
        'display_name',
        'comment_id',
        'post_id',
        'permalink',
        'message',
        'matched_domain',
        'detected_from',
        'page_blocked',
        'block_error',
        'bot_banned',
        'hide_succeeded',
        'status',
        'is_read',
        'comment_deleted',
        'blocked_at',
        'unblocked_at',
        'unblocked_by',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'page_blocked' => 'boolean',
        'bot_banned' => 'boolean',
        'hide_succeeded' => 'boolean',
        'is_read' => 'boolean',
        'comment_deleted' => 'boolean',
        'blocked_at' => 'datetime',
        'unblocked_at' => 'datetime',
    ];

    /**
     * แอดมินที่กดปลดบล็อก
     */
    public function unblockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'unblocked_by');
    }

    /**
     * กรองตามช่องทาง
     */
    public function scopeForPlatform(Builder $query, string $platform): Builder
    {
        return $query->where('platform', $platform);
    }

    /**
     * เฉพาะที่แอดมินยังไม่ได้จัดการ (ใช้ทำ badge ตัวเลขบนเมนู)
     */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('is_read', false);
    }

    /**
     * เฉพาะที่ยังถูกบล็อกอยู่
     */
    public function scopeStillBlocked(Builder $query): Builder
    {
        return $query->where('status', 'blocked');
    }

    /**
     * 🔗 ประกอบ permalink ไปยังคอมเมนต์ตรงๆ จากข้อมูลที่ webhook ส่งมา (ไม่ต้องยิง Graph API)
     *
     * รูปแบบที่ Facebook ส่งมา:
     * - post_id    = "{pageId}_{storyFbid}"
     * - comment_id = "{parentId}_{commentLocalId}"
     *
     * @param  string|null  $postId  post_id จาก webhook
     * @param  string|null  $commentId  comment_id จาก webhook
     * @param  string|null  $pageId  facebook_page_id (เผื่อ post_id ไม่มี prefix)
     * @return string|null ลิงก์ที่กดแล้วเด้งไปที่คอมเมนต์นั้น
     *
     * @example self::buildPermalink('107173337600346_1638063184997740', '1555858729884853_1045309708083310');
     * // https://www.facebook.com/permalink.php?story_fbid=1638063184997740&id=107173337600346&comment_id=1045309708083310
     *
     * @tip ถ้าเป็น Reel ลิงก์อาจเด้งไปหน้าโพสต์แทนตัวคอมเมนต์ — แอดมินยังหาได้จากชื่อ+ข้อความที่บันทึกไว้
     */
    public static function buildPermalink(?string $postId, ?string $commentId, ?string $pageId = null): ?string
    {
        if (empty($commentId)) {
            return null;
        }

        // แยก local id ของคอมเมนต์ (ส่วนหลัง underscore)
        $commentLocal = str_contains($commentId, '_')
            ? substr($commentId, strrpos($commentId, '_') + 1)
            : $commentId;

        // แยก story_fbid + page id จาก post_id
        $storyFbid = null;
        if (! empty($postId)) {
            if (str_contains($postId, '_')) {
                [$postOwner, $storyFbid] = explode('_', $postId, 2);
                $pageId = $pageId ?: $postOwner;
            } else {
                $storyFbid = $postId;
            }
        }

        // ไม่มี story_fbid → ยังเดาไม่ได้ ให้แอดมินค้นจากข้อความแทน
        if (empty($storyFbid) || empty($pageId)) {
            return null;
        }

        return 'https://www.facebook.com/permalink.php?'.http_build_query([
            'story_fbid' => $storyFbid,
            'id' => $pageId,
            'comment_id' => $commentLocal,
        ]);
    }
}
