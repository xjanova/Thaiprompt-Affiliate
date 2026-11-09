<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * คลังคำตอบคอมเมนต์สำเร็จรูป
 *
 * ใช้แทน AI ในคอมเมนต์ทั่วไป (สั้น ไม่มีบริบท) — เรียก AI เฉพาะคอมเมนต์ที่ต้องคิดจริง
 *
 * @property int $id
 * @property string $message ข้อความตอบ (รองรับ {name})
 * @property string $category invite | blessing | thanks | emoji | reply_to_bot | appreciation | web_invite | letting_go | neutral
 * @property string $locale ภาษา (ตอนนี้มีแต่ th)
 * @property bool $is_active
 * @property int $use_count จำนวนครั้งที่ถูกหยิบ
 * @property \Carbon\Carbon|null $last_used_at
 */
class FortuneCommentReply extends Model
{
    use SoftDeletes;

    protected $table = 'fortune_comment_replies';

    /** มีคำชวนกดไลก์/ติดตาม — ใช้กับคนที่ยังไม่เคยโต้ตอบใน Messenger */
    public const CATEGORY_INVITE = 'invite';

    /** อวยพรอย่างเดียว — ใช้กับคนที่คุยกับเราแล้ว */
    public const CATEGORY_BLESSING = 'blessing';

    /** ขอบคุณสำหรับคอมเมนต์ — โทนกลาง ใช้ได้ทั้งสองกลุ่ม */
    public const CATEGORY_THANKS = 'thanks';

    /** ชุดสั้นมาก ไว้ตอบคอมเมนต์อีโมจิล้วน */
    public const CATEGORY_EMOJI = 'emoji';

    /**
     * 🙏 (2026-11-05) ขอบคุณที่ร่วมแสดงความเห็น — 200 แบบ ใช้ตอบคอมเมนต์ทุกคน
     *
     * ไม่ชวนทักแชท ไม่ขอให้กดอะไร (ดู migration 2026_11_05_120000)
     */
    public const CATEGORY_APPRECIATION = 'appreciation';

    /**
     * 🌐 (2026-11-05) ขอบคุณ + ชวนดูดวงที่เว็บ จันทรา.online — 100 แบบ ทุกแบบมี {web_link} จุดเดียว
     *
     * ไม่ใช้ทุกคอมเมนต์ — ProcessCommentEngagement คุมความถี่ (สุ่มบางส่วน + เว้นระยะต่อคน)
     */
    public const CATEGORY_WEB_INVITE = 'web_invite';

    /**
     * ลิงก์เว็บแม่หมอที่โชว์ในคอมเมนต์ — ใช้ชื่อไทย ไม่ใช้ punycode (xn--82c4af5bzdj.online)
     * เพราะ punycode หน้าตาเหมือนลิงก์หลอกลวง ลูกค้าไม่กล้ากด
     */
    public const WEB_LINK = 'https://จันทรา.online';

    /**
     * 🕊️ (2026-11-05) โทนสงบ ให้กำลังใจ — 100 แบบ ตอบคอมเมนต์อโหสิกรรม / ไม่สน / ไม่แคร์ / เจ็บใจ
     *
     * ไม่ร่าเริง (ห้าม "ใจฟู/ยิ้ม") ไม่แนบลิงก์ ไม่ชวนทำอะไร
     */
    public const CATEGORY_LETTING_GO = 'letting_go';

    /** 🕊️ (2026-11-05) โทนกลางสุภาพ — 40 แบบ ตอบคอมเมนต์ที่ตำหนิเพจ ไม่แก้ตัว ไม่ขาย ไม่แนบลิงก์ */
    public const CATEGORY_NEUTRAL = 'neutral';

    /**
     * ลูกค้าตอบคำถามที่บอทถามไว้ใต้คอมเมนต์ = สนใจจริง
     *
     * ชุดนี้มีหน้าที่เดียว: พาเข้า Messenger เพื่อคุยต่อ
     * เพราะคำตอบละเอียดทำในคอมเมนต์สาธารณะไม่ได้ (ยาวเกิน + เป็นเรื่องส่วนตัว)
     */
    public const CATEGORY_REPLY_TO_BOT = 'reply_to_bot';

    /**
     * จำนวนชุดล่าสุดที่ "ห้ามหยิบซ้ำ" ต่อ 1 ลูกค้า
     *
     * กันไม่ให้คนเดิมเห็นข้อความเดิมสองครั้งติด — เป็นสัญญาณสแปมที่ FB จับได้ง่ายที่สุด
     */
    protected const RECENT_MEMORY = 8;

    /** อายุความจำ (วินาที) — 7 วัน */
    protected const RECENT_TTL = 604800;

    /**
     * 🗨️ (2026-11-09) จำนวนชุดล่าสุดที่ "ห้ามหยิบซ้ำ" ต่อ 1 เพจ (สาขา)
     *
     * ความจำต่อคนอย่างเดียวไม่พอ — คนละคนแต่เพจเดียวกันยังได้ข้อความเดิมติดกันได้
     * และสิ่งที่ Facebook เห็นคือ "เพจนี้ตอบประโยคเดิมซ้ำ ๆ" ไม่ใช่ต่อคน
     */
    protected const RECENT_SCOPE_MEMORY = 20;

    /** อายุความจำต่อเพจ (วินาที) — 1 วัน */
    protected const RECENT_SCOPE_TTL = 86400;

    protected $fillable = [
        'message',
        'category',
        'locale',
        'is_active',
        'sort_order',
        'use_count',
        'last_used_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'use_count' => 'integer',
        'last_used_at' => 'datetime',
    ];

    /**
     * Scope: เฉพาะชุดที่เปิดใช้งาน
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * ตารางพร้อมใช้หรือยัง
     *
     * ⚠️ ระหว่าง deploy ไฟล์โค้ดขึ้นก่อน migrate เสมอ — ถ้าอ้างตารางที่ยังไม่มี
     *    การตอบคอมเมนต์จะพังทั้งระบบในช่วงนั้น
     *    ไม่มีตาราง = ทำตัวเหมือนก่อนมีฟีเจอร์นี้ (ให้ caller ใช้ชุดฝังโค้ดเดิม)
     *
     * @return bool
     */
    public static function isReady(): bool
    {
        return Cache::remember(
            'fortune_comment_replies:table_exists',
            300,
            fn () => Schema::hasTable('fortune_comment_replies')
        );
    }

    /**
     * หยิบคำตอบ 1 ชุด — เลี่ยงชุดที่เพิ่งใช้กับลูกค้าคนนี้ไป
     *
     * @param  string  $category  หมวดที่ต้องการ
     * @param  string|null  $userKey  ตัวระบุลูกค้า (PSID) — null = ไม่ต้องกันซ้ำ
     * @param  string  $locale  ภาษา
     * @param  string|null  $scopeKey  ตัวระบุเพจ/สาขา — null = ไม่กันซ้ำระดับเพจ
     * @return string|null ข้อความที่ยังไม่ได้แทน {name} — null = ไม่มีของในคลัง
     *
     * @example
     * $msg = FortuneCommentReply::pick('invite', $psid);
     * // "ขอบคุณที่แวะมาค่ะ {name} 🙏 กดติดตามเพจไว้ รับดวงฟรีทุกเช้าเลยนะคะ"
     */
    public static function pick(string $category, ?string $userKey = null, string $locale = 'th', ?string $scopeKey = null): ?string
    {
        if (! self::isReady()) {
            return null;
        }

        $recent = $userKey ? self::recentIds($userKey) : [];
        $recentScope = $scopeKey ? self::recentScopeIds($scopeKey) : [];

        $base = fn () => self::query()
            ->active()
            ->where('category', $category)
            ->where('locale', $locale);

        // รอบแรก: ตัดชุดที่เพิ่งใช้ไปกับคนนี้ + ที่เพิ่งใช้บนเพจนี้
        $avoid = array_values(array_unique(array_merge($recent, $recentScope)));
        $row = $base()
            ->when(! empty($avoid), fn ($q) => $q->whereNotIn('id', $avoid))
            ->inRandomOrder()
            ->first();

        // รอบสอง (คลังเล็ก): ตัดแค่ของคนนี้ + ตัวล่าสุดของเพจ (ห้ามซ้ำติดกันบนเพจเดียวกัน)
        if ($row === null && ! empty($recentScope)) {
            $avoid = array_values(array_unique(array_merge($recent, [end($recentScope)])));
            $row = $base()->whereNotIn('id', $avoid)->inRandomOrder()->first();
        }

        // 🔁 รอบสอง: คลังหมวดนี้เล็กกว่าความจำ → ทุกชุดโดนตัดหมด
        //    ⚠️ ห้ามคืน null เพราะตัวกันซ้ำ — คอมเมนต์เงียบแย่กว่าตอบซ้ำ
        //    (บทเรียนเดียวกับ FortuneInviteMessage::pickActive)
        $row ??= $base()->inRandomOrder()->first();

        if ($row === null) {
            return null;
        }

        if ($userKey) {
            self::rememberUsed($userKey, $row->id);
        }

        if ($scopeKey) {
            self::rememberScopeUsed($scopeKey, $row->id);
        }

        $row->recordUse();

        return $row->message;
    }

    /**
     * หยิบแล้วแทนชื่อลูกค้าให้เลย
     *
     * @param  string  $category  หมวดที่ต้องการ
     * @param  string  $name  ชื่อลูกค้า
     * @param  string|null  $userKey  ตัวระบุลูกค้า (PSID)
     * @param  string  $locale  ภาษา
     * @param  string|null  $scopeKey  ตัวระบุเพจ/สาขา (กันซ้ำระดับเพจ)
     * @return string|null
     */
    public static function pickRendered(
        string $category,
        string $name,
        ?string $userKey = null,
        string $locale = 'th',
        ?string $scopeKey = null
    ): ?string {
        $message = self::pick($category, $userKey, $locale, $scopeKey);

        if ($message === null) {
            return null;
        }

        // ชื่อว่าง → ตัดคำนำหน้า "คุณ " ที่ห้อยอยู่ทิ้งด้วย ไม่งั้นได้ "ขอบคุณค่ะ คุณ  🙏"
        $rendered = str_replace('{name}', trim($name), $message);

        // 🔗 ลิงก์เข้าแชทของ "เพจที่กำลังทำงานอยู่" — ห้ามฝัง id ตายตัว
        //    ระบบมีหลายสาขา (fortune_pages) ถ้า hardcode ไว้ สาขาอื่นจะส่งลูกค้าไปผิดเพจ
        if (str_contains($rendered, '{page_link}')) {
            $rendered = str_replace('{page_link}', self::messengerLink(), $rendered);
        }

        if (str_contains($rendered, '{web_link}')) {
            $rendered = str_replace('{web_link}', self::WEB_LINK, $rendered);
        }

        return trim(preg_replace('/\s+/u', ' ', $rendered));
    }

    /**
     * ลิงก์เข้า Messenger ของเพจที่ context ชี้อยู่ตอนนี้
     *
     * ใช้ m.me เพราะเป็นโดเมนของ Meta เอง — ไม่โดนลดการมองเห็นแบบลิงก์ออกนอกแพลตฟอร์ม
     *
     * @return string เช่น "m.me/107173337600346" — คืนค่าว่างถ้าหา page id ไม่เจอ
     */
    protected static function messengerLink(): string
    {
        $pageId = FortuneTellingSetting::getSettings()->facebook_page_id ?? null;

        return $pageId ? 'm.me/'.$pageId : '';
    }

    /**
     * บันทึกว่าถูกหยิบไปใช้
     *
     * @return void
     */
    public function recordUse(): void
    {
        // เขียนครั้งเดียวด้วย query ตรง — ตัวนี้ถูกเรียกทุกคอมเมนต์ (prod ~2,500 ครั้ง/วัน)
        // ใช้ Eloquent save จะกลายเป็น 2 เขียน/ครั้ง และไป touch updated_at ของแถวคลังโดยไม่จำเป็น
        static::query()
            ->whereKey($this->getKey())
            ->update([
                'use_count' => DB::raw('use_count + 1'),
                'last_used_at' => now(),
            ]);
    }

    /**
     * รายการ id ที่เพิ่งใช้กับลูกค้าคนนี้
     *
     * @param  string  $userKey  ตัวระบุลูกค้า
     * @return array<int>
     */
    protected static function recentIds(string $userKey): array
    {
        return Cache::get(self::recentKey($userKey), []);
    }

    /**
     * จำว่าเพิ่งใช้ชุดไหนกับลูกค้าคนนี้
     *
     * @param  string  $userKey  ตัวระบุลูกค้า
     * @param  int  $id  id ของชุดที่ใช้
     * @return void
     */
    protected static function rememberUsed(string $userKey, int $id): void
    {
        $key = self::recentKey($userKey);

        $ids = Cache::get($key, []);
        $ids[] = $id;

        // เก็บแค่ N ตัวหลังสุด
        if (count($ids) > self::RECENT_MEMORY) {
            $ids = array_slice($ids, -self::RECENT_MEMORY);
        }

        Cache::put($key, $ids, self::RECENT_TTL);
    }

    /**
     * รายการ id ที่เพิ่งใช้บนเพจนี้ (ทุกหมวดรวมกัน — id ไม่ชนข้ามหมวดอยู่แล้ว)
     *
     * @param  string  $scopeKey  ตัวระบุเพจ
     * @return array<int>
     */
    protected static function recentScopeIds(string $scopeKey): array
    {
        $ids = Cache::get(self::recentScopeKey($scopeKey), []);

        return is_array($ids) ? $ids : [];
    }

    /**
     * จำว่าเพิ่งใช้ชุดไหนบนเพจนี้
     *
     * @param  string  $scopeKey  ตัวระบุเพจ
     * @param  int  $id  id ของชุดที่ใช้
     * @return void
     */
    protected static function rememberScopeUsed(string $scopeKey, int $id): void
    {
        $ids = self::recentScopeIds($scopeKey);
        $ids[] = $id;

        if (count($ids) > self::RECENT_SCOPE_MEMORY) {
            $ids = array_slice($ids, -self::RECENT_SCOPE_MEMORY);
        }

        Cache::put(self::recentScopeKey($scopeKey), array_values($ids), self::RECENT_SCOPE_TTL);
    }

    protected static function recentScopeKey(string $scopeKey): string
    {
        return 'fcr:recent_page:'.md5($scopeKey);
    }

    /**
     * คีย์แคชความจำต่อลูกค้า
     *
     * @param  string  $userKey  ตัวระบุลูกค้า
     * @return string
     */
    protected static function recentKey(string $userKey): string
    {
        // 🏬 ตั้งใจ **ไม่** ผูกกับสาขา (ต่างจากคีย์อื่นในระบบนี้)
        //    เพราะสิ่งที่กันคือ "ลูกค้าคนเดิมเห็นข้อความเดิมซ้ำ"
        //    ซึ่งเป็นเรื่องของสายตาลูกค้า ไม่ใช่เรื่องของเพจ
        //    ถ้าแยกตามสาขา คนที่ตามเราหลายเพจจะเจอข้อความเดิมซ้ำได้ = ที่เรากันอยู่พอดี
        return 'fcr:recent:'.md5($userKey);
    }
}
