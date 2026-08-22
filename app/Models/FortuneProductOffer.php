<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FortuneProductOffer — ประวัติการเสนอสินค้าของบอทแม่หมอ
 *
 * ทุกครั้งที่บอทยิงการ์ดสินค้า จะบันทึก 1 แถวต่อ 1 ชิ้น (ปกติเสนอครั้งละ 2 ชิ้น = 2 แถว)
 * ใช้คุมเพดานรายวัน + กันส่งของซ้ำ + ดูสถิติว่าของชิ้นไหนได้ผล
 *
 * @property int $id
 * @property string $platform facebook | line
 * @property string $platform_user_id
 * @property int $marketplace_product_id
 * @property int|null $reading_id
 * @property string $trigger จุดที่ยิง
 * @property string|null $mu_group
 * @property string|null $slot low | high
 * @property \Carbon\Carbon $sent_at
 */
class FortuneProductOffer extends Model
{
    protected $table = 'fortune_product_offers';

    protected $fillable = [
        'platform',
        'platform_user_id',
        'marketplace_product_id',
        'reading_id',
        'trigger',
        'mu_group',
        'slot',
        'context_reason',
        'price_at_send',
        'commission_rate_at_send',
        'sent_at',
        'clicked_at',
    ];

    protected $casts = [
        'price_at_send' => 'decimal:2',
        'commission_rate_at_send' => 'decimal:2',
        'sent_at' => 'datetime',
        'clicked_at' => 'datetime',
    ];

    // ── จุดที่ยิง ─────────────────────────────────────────────────────
    /** จบเซสชันไพ่เซลติก 99 */
    public const TRIGGER_CELTIC_END = 'celtic_end';

    /** จบดูดวงเจาะลึก 39 */
    public const TRIGGER_DEEP_END = 'deep_end';

    /** จบบทสนทนาทั่วไป (คนไม่เคยดูดวงก็ได้) */
    public const TRIGGER_CHAT_END = 'chat_end';

    /** เสนอดูดวงแล้วลูกค้าไม่เอา/เงียบหาย — ทิ้งของไว้ให้ */
    public const TRIGGER_PITCH_DECLINED = 'pitch_declined';

    /** รับดวงฟรีรายวันไป — คนกลุ่มนี้ยังไม่เคยจ่ายก็ได้ของเสนอเหมือนกัน */
    public const TRIGGER_DAILY_FREE = 'daily_free';

    /** ลูกค้าถามหาของเอง (ไม่นับเพดานรายวัน) */
    public const TRIGGER_CUSTOMER_ASK = 'customer_ask';

    /**
     * จุดที่ถือเป็น "บอทเสนอเอง" — นับรวมในเพดานรายวัน
     *
     * ⚠️ `customer_ask` ไม่อยู่ในลิสต์โดยตั้งใจ — ลูกค้าถามเองต้องได้คำตอบเสมอ
     *
     * @var array<int,string>
     */
    public const PROACTIVE_TRIGGERS = [
        self::TRIGGER_CELTIC_END,
        self::TRIGGER_DEEP_END,
        self::TRIGGER_CHAT_END,
        self::TRIGGER_PITCH_DECLINED,
        self::TRIGGER_DAILY_FREE,
    ];

    /** ตัวเลือกราคาต่ำ */
    public const SLOT_LOW = 'low';

    /** ตัวเลือกราคาสูง */
    public const SLOT_HIGH = 'high';

    /**
     * สินค้าที่เสนอไป
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(MarketplaceProduct::class, 'marketplace_product_id');
    }

    /**
     * การดูดวงที่ผูกอยู่
     */
    public function reading(): BelongsTo
    {
        return $this->belongsTo(FortuneReading::class, 'reading_id');
    }

    /**
     * Scope: เฉพาะลูกค้าคนนี้
     */
    public function scopeForUser(Builder $q, string $platform, string $platformUserId): Builder
    {
        return $q->where('platform', $platform)->where('platform_user_id', $platformUserId);
    }

    /**
     * วันนี้บอท "เสนอเอง" ให้ลูกค้าคนนี้ไปแล้วกี่ครั้ง (นับเป็นครั้ง ไม่ใช่จำนวนชิ้น)
     *
     * นับแบบ "รอบการเสนอ" — เสนอ 1 ครั้งได้ 2 ชิ้น = 2 แถว แต่ต้องนับเป็น 1
     * ⇒ นับจำนวน sent_at ที่ไม่ซ้ำกัน
     */
    public static function proactiveCountToday(string $platform, string $platformUserId): int
    {
        return static::forUser($platform, $platformUserId)
            ->whereIn('trigger', self::PROACTIVE_TRIGGERS)
            ->where('sent_at', '>=', now()->startOfDay())
            ->distinct('sent_at')
            ->count('sent_at');
    }

    /**
     * id สินค้าที่เคยส่งให้ลูกค้าคนนี้ใน N วันหลัง — เอาไปกันส่งซ้ำ
     *
     * @param  int  $days  ย้อนหลังกี่วัน
     * @return array<int,int>
     */
    public static function recentProductIds(string $platform, string $platformUserId, int $days = 30): array
    {
        return static::forUser($platform, $platformUserId)
            ->where('sent_at', '>=', now()->subDays($days))
            ->pluck('marketplace_product_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
}
