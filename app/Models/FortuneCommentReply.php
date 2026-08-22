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
 * @property string $category invite | blessing | thanks | emoji
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
     * จำนวนชุดล่าสุดที่ "ห้ามหยิบซ้ำ" ต่อ 1 ลูกค้า
     *
     * กันไม่ให้คนเดิมเห็นข้อความเดิมสองครั้งติด — เป็นสัญญาณสแปมที่ FB จับได้ง่ายที่สุด
     */
    protected const RECENT_MEMORY = 8;

    /** อายุความจำ (วินาที) — 7 วัน */
    protected const RECENT_TTL = 604800;

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
     * @return string|null ข้อความที่ยังไม่ได้แทน {name} — null = ไม่มีของในคลัง
     *
     * @example
     * $msg = FortuneCommentReply::pick('invite', $psid);
     * // "ขอบคุณที่แวะมาค่ะ {name} 🙏 กดติดตามเพจไว้ รับดวงฟรีทุกเช้าเลยนะคะ"
     */
    public static function pick(string $category, ?string $userKey = null, string $locale = 'th'): ?string
    {
        if (! self::isReady()) {
            return null;
        }

        $recent = $userKey ? self::recentIds($userKey) : [];

        $base = fn () => self::query()
            ->active()
            ->where('category', $category)
            ->where('locale', $locale);

        // รอบแรก: ตัดชุดที่เพิ่งใช้ไปกับคนนี้
        $row = $base()
            ->when(! empty($recent), fn ($q) => $q->whereNotIn('id', $recent))
            ->inRandomOrder()
            ->first();

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
     * @return string|null
     */
    public static function pickRendered(
        string $category,
        string $name,
        ?string $userKey = null,
        string $locale = 'th'
    ): ?string {
        $message = self::pick($category, $userKey, $locale);

        if ($message === null) {
            return null;
        }

        // ชื่อว่าง → ตัดคำนำหน้า "คุณ " ที่ห้อยอยู่ทิ้งด้วย ไม่งั้นได้ "ขอบคุณค่ะ คุณ  🙏"
        $rendered = str_replace('{name}', trim($name), $message);

        return trim(preg_replace('/\s+/u', ' ', $rendered));
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
     * คีย์แคชความจำต่อลูกค้า
     *
     * @param  string  $userKey  ตัวระบุลูกค้า
     * @return string
     */
    protected static function recentKey(string $userKey): string
    {
        return 'fcr:recent:'.md5($userKey);
    }
}
