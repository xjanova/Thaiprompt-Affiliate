<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

/**
 * FortuneWebFunnelEvent — เหตุการณ์หนึ่งครั้งบนเส้นทาง แชทเพจ FB → ห้องแม่หมอบนเว็บจันทรา (2026-11-14)
 *
 * เดิมไม่มีตัวนับที่ใช้ได้: consumed_at ของ fortune_web_handoffs เป็น 0 ตลอดตั้งแต่ 2026-11-04
 * (เว็บจันทราพาไปหน้าเลือกห้องเลย ไม่ส่ง nonce กลับ) — ตารางนี้นับทุกขั้นแยกจากกัน
 *
 * @property int $id
 * @property string $event box_sent|link_opened|link_dead|chatted|paid
 * @property string|null $platform_user_id PSID ในแชทเพจ
 * @property int|null $thaiprompt_user_id
 * @property string|null $juntra_user_ref
 * @property int|null $fortune_page_id
 * @property string|null $source
 * @property string|null $amount
 * @property \Carbon\Carbon|null $created_at
 */
class FortuneWebFunnelEvent extends Model
{
    use MassPrunable;

    protected $table = 'fortune_web_funnel_events';

    public $timestamps = false;

    /** บอทส่งกล่องลิงก์เข้าห้องแม่หมอ (source ขึ้นต้น button: = ลูกค้ากดปุ่มเอง) */
    public const BOX_SENT = 'box_sent';

    /** ลูกค้าแตะลิงก์ ล็อกอินอัตโนมัติสำเร็จ ส่งต่อไปเว็บจันทรา (source = new_account|existing_account) */
    public const LINK_OPENED = 'link_opened';

    /** ลูกค้าแตะลิงก์แต่ใช้ไม่ได้ (source = เหตุผล: used/expired/busy/error/denied ...) */
    public const LINK_DEAD = 'link_dead';

    /** ลูกค้าที่มาจาก FB คุยกับแม่หมอบนเว็บ (บันทึกครั้งแรกของวันต่อคน) */
    public const CHATTED = 'chatted';

    /** ลูกค้าที่มาจาก FB ซื้อคำทำนายบนเว็บ (amount = ยอดบิล · source = bill:{id}) */
    public const PAID = 'paid';

    /** เก็บกี่วัน */
    public const PRUNE_AFTER_DAYS = 400;

    protected $fillable = [
        'event',
        'platform_user_id',
        'thaiprompt_user_id',
        'juntra_user_ref',
        'fortune_page_id',
        'source',
        'amount',
        'created_at',
    ];

    protected $casts = [
        'thaiprompt_user_id' => 'integer',
        'fortune_page_id' => 'integer',
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    /**
     * แถวที่ต้อง prune — เก่ากว่า PRUNE_AFTER_DAYS วัน
     */
    public function prunable(): Builder
    {
        return static::query()->where('created_at', '<', now()->subDays(self::PRUNE_AFTER_DAYS));
    }
}
