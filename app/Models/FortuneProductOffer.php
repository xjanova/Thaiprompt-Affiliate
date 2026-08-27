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

    /**
     * ส่งสติกเกอร์ / ยกนิ้ว / รูป / ลิงก์ มาในแชทปกติ โดยไม่มีดูดวงค้าง
     *
     * จุดนี้เดิม **เงียบสนิท** (กฎ silent rule 2026-05-01) = ทางตันที่ลูกค้าไม่ได้อะไรเลย
     * เอาการ์ดสินค้าไปแทนความเงียบ ดีกว่าปล่อยให้เขาคิดว่าบอทตาย
     *
     * ⚠️ (2026-08-27) เลิกใช้กับของใหม่แล้ว — แตกเป็น 3 จุดย่อยข้างล่าง
     *    ค่านี้ยังต้องอยู่เพราะประวัติเดิมในตารางเป็น 'gesture' อยู่ 586 แถว
     *    ถ้าลบทิ้ง เพดานรายวันจะนับแถวเก่าไม่เจอ ⇒ คนที่เพิ่งได้การ์ดวันนี้ได้ซ้ำอีกรอบ
     */
    public const TRIGGER_GESTURE = 'gesture';

    /**
     * 🖼️ ลูกค้าส่ง "รูป" มาในแชทปกติ (ไม่ใช่สลิป ไม่มีดูดวงค้าง)
     *
     * แยกจากสติกเกอร์เพราะ owner สั่ง (2026-08-27): เสนอของเฉพาะคนส่งรูปกับส่งลิงก์
     * — คนกลุ่มนี้ตั้งใจสื่อสารบางอย่าง ต่างจากคนกดสติกเกอร์ผ่านๆ
     */
    public const TRIGGER_GESTURE_IMAGE = 'gesture_image';

    /** 🔗 ลูกค้าส่ง "ลิงก์" ล้วนมาในแชทปกติ */
    public const TRIGGER_GESTURE_LINK = 'gesture_link';

    /** 👍 สติกเกอร์ / ยกนิ้ว / อีโมจิล้วน */
    public const TRIGGER_GESTURE_STICKER = 'gesture_sticker';

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
        self::TRIGGER_GESTURE,
        self::TRIGGER_GESTURE_IMAGE,
        self::TRIGGER_GESTURE_LINK,
        self::TRIGGER_GESTURE_STICKER,
    ];

    /**
     * จุดยิงตระกูล "ลูกค้าส่งอะไรมาเฉยๆ ในแชทปกติ"
     *
     * ⚠️ ต้องมี `gesture` เดิมอยู่ด้วยเสมอ — เพดานรายวันนับย้อนหลังในวันเดียวกัน
     *    วันที่ deploy จะมีทั้งแถวเก่า ('gesture') และแถวใหม่ ('gesture_image') ปนกัน
     *
     * @var array<int,string>
     */
    public const GESTURE_TRIGGERS = [
        self::TRIGGER_GESTURE,
        self::TRIGGER_GESTURE_IMAGE,
        self::TRIGGER_GESTURE_LINK,
        self::TRIGGER_GESTURE_STICKER,
    ];

    /**
     * จุดที่ยิง "หลังลูกค้าจ่ายเงินแล้วดูดวงจบ" — มีโควตารายวันเป็นของตัวเอง
     *
     * 🧾 ทำไมต้องแยก (เคสจริง Zurich Mock 2026-08-23):
     *    รับดวงฟรีตอน 13:20 → ได้การ์ดสินค้าไป 1 รอบ → โควตารายวัน (cap=1) หมด
     *    บ่ายจ่าย 99 บาท ดูจบ 15:35 → `celtic_end` โดนเพดานตีตก ไม่ได้การ์ดเลย
     *    ⇒ ของฟรีตอนเช้าไปกินสิทธิ์ของ "นาทีที่ลูกค้าอยากซื้อที่สุด" ซึ่งกลับหัวกับที่ควรเป็น
     *
     * ⚠️ ต้องเป็นสับเซ็ตของ PROACTIVE_TRIGGERS เสมอ — ทั้งสองลิสต์ใช้นับคนละกระเป๋า
     *    ถ้าเพิ่มจุดยิงใหม่ที่แปลว่า "จ่ายเงินแล้วดูจบ" ต้องใส่ทั้งสองที่
     *
     * @var array<int,string>
     */
    public const PAID_END_TRIGGERS = [
        self::TRIGGER_CELTIC_END,
        self::TRIGGER_DEEP_END,
    ];

    /** ตัวเลือกราคาต่ำ */
    public const SLOT_LOW = 'low';

    /** ตัวเลือกราคาสูง */
    public const SLOT_HIGH = 'high';

    /**
     * ใบที่ 3 — ของทั่วไป (ไม่ใช่สายมู) เลือกตามพฤติกรรมลูกค้า
     *
     * ของสายมูในคลังมี ~90 ชิ้น ส่วนของทั่วไปมี 738 ชิ้นในช่วงราคาเดียวกัน
     * ถ้าเสนอแต่สายมู ลูกค้าจะเห็นของวนซ้ำไม่กี่อย่าง
     */
    public const SLOT_EXTRA = 'extra';

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
     * นับแบบ "รอบการเสนอ" — เสนอ 1 ครั้งได้ 2-3 ชิ้น = 2-3 แถว แต่ต้องนับเป็น 1
     * ⇒ นับจำนวน sent_at ที่ไม่ซ้ำกัน
     *
     * @param  array<int,string>|null  $triggers  จำกัดเฉพาะจุดยิงกลุ่มนี้ (null = ทุกจุดที่บอทเสนอเอง)
     */
    public static function proactiveCountToday(string $platform, string $platformUserId, ?array $triggers = null): int
    {
        return static::forUser($platform, $platformUserId)
            ->whereIn('trigger', $triggers ?? self::PROACTIVE_TRIGGERS)
            ->where('sent_at', '>=', now()->startOfDay())
            ->distinct('sent_at')
            ->count('sent_at');
    }

    /**
     * จุดยิงที่บอทเสนอเอง "แต่ไม่ใช่ท้ายบิลที่จ่ายเงินแล้ว" — ใช้เป็นกระเป๋าโควตาปกติ
     *
     * @return array<int,string>
     */
    public static function unpaidProactiveTriggers(): array
    {
        return array_values(array_diff(self::PROACTIVE_TRIGGERS, self::PAID_END_TRIGGERS));
    }

    /**
     * จุดยิงที่แอดมินเปิด/ปิดได้จากหน้าเว็บ + คำอธิบายไทย
     *
     * ⚠️ ไม่มี `gesture` เดิมอยู่ในลิสต์นี้โดยตั้งใจ — มันถูกแทนที่ด้วย 3 ตัวย่อยแล้ว
     *    ถ้าโผล่ในฟอร์ม แอดมินจะติ๊กมันแล้วเปิดสติกเกอร์กลับมาโดยไม่รู้ตัว
     *
     * @return array<string,array{label:string,hint:string,icon:string}>
     */
    public static function configurableTriggers(): array
    {
        return [
            self::TRIGGER_GESTURE_IMAGE => [
                'label' => 'ลูกค้าส่งรูป',
                'hint' => 'ส่งรูปมาในแชทปกติ (ไม่ใช่สลิป · ไม่มีดูดวงค้าง)',
                'icon' => 'fa-image',
            ],
            self::TRIGGER_GESTURE_LINK => [
                'label' => 'ลูกค้าส่งลิงก์',
                'hint' => 'พิมพ์มาเป็นลิงก์ล้วน ไม่มีข้อความอื่น',
                'icon' => 'fa-link',
            ],
            self::TRIGGER_GESTURE_STICKER => [
                'label' => 'สติกเกอร์ / ยกนิ้ว / อีโมจิ',
                'hint' => 'กดสติกเกอร์หรือส่งอีโมจิล้วน',
                'icon' => 'fa-thumbs-up',
            ],
            self::TRIGGER_DAILY_FREE => [
                'label' => 'รับดวงฟรีรายวัน',
                'hint' => 'คนที่ขอดวงฟรีประจำวันไป (กลุ่มใหญ่ที่สุด)',
                'icon' => 'fa-sun',
            ],
            self::TRIGGER_CELTIC_END => [
                'label' => 'ดูไพ่ 99 จบ',
                'hint' => 'จ่ายเงินแล้วดูจบ — นาทีที่ลูกค้าพอใจที่สุด',
                'icon' => 'fa-wand-magic-sparkles',
            ],
            self::TRIGGER_DEEP_END => [
                'label' => 'ดูดวงเจาะลึก 39 จบ',
                'hint' => 'จ่ายเงินแล้วดูจบ',
                'icon' => 'fa-star',
            ],
            self::TRIGGER_PITCH_DECLINED => [
                'label' => 'เสนอดูดวงแล้วไม่เอา',
                'hint' => 'ลูกค้าไม่ดูดวง/เงียบหาย — ทิ้งของไว้ให้',
                'icon' => 'fa-face-frown',
            ],
            self::TRIGGER_CHAT_END => [
                'label' => 'จบบทสนทนาทั่วไป',
                'hint' => 'ยังไม่ได้ต่อสายจริง — ติ๊กไว้ก็ยังไม่ยิง',
                'icon' => 'fa-comments',
            ],
            self::TRIGGER_CUSTOMER_ASK => [
                'label' => 'ลูกค้าถามหาของเอง',
                'hint' => 'ไม่นับเพดานรายวัน — ถามเมื่อไหร่ตอบเมื่อนั้น',
                'icon' => 'fa-cart-shopping',
            ],
        ];
    }

    /**
     * แปลงชนิดสิ่งที่ลูกค้าส่งมา → จุดยิง
     *
     * @param  string  $gestureType  image | link | sticker | emoji
     *
     * @example
     * FortuneProductOffer::triggerForGesture('image'); // 'gesture_image'
     */
    public static function triggerForGesture(string $gestureType): string
    {
        return match ($gestureType) {
            'image' => self::TRIGGER_GESTURE_IMAGE,
            'link' => self::TRIGGER_GESTURE_LINK,
            default => self::TRIGGER_GESTURE_STICKER, // sticker | emoji
        };
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
