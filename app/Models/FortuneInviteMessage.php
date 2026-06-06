<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Fortune Invite Message Model
 *
 * 💬 คลังข้อความ "ชวนดูดวงแบบเนียน" — สุ่มส่งใน DM กลับ
 * เมื่อลูกค้าได้รูปแบนเนอร์ในสัปดาห์นี้แล้ว (ไม่ส่งรูปซ้ำ → ส่งข้อความแทน)
 *
 * ข้อความเป็นแนว ทริค/ทางออก/จังหวะชีวิต ไม่ขายตรง
 * รองรับ {name} = ชื่อลูกค้า (แทนที่อัตโนมัติตอนส่ง)
 *
 * @property int $id
 * @property string $message ข้อความเชิญชวน (รองรับ {name})
 * @property string $category หมวดหมู่
 * @property bool $is_active เปิดใช้งานหรือไม่
 * @property int $sort_order ลำดับ
 * @property int $send_count จำนวนครั้งที่ส่ง
 * @property \Carbon\Carbon|null $last_sent_at ส่งล่าสุดเมื่อ
 * @property int|null $created_by แอดมินที่สร้าง
 */
class FortuneInviteMessage extends Model
{
    use SoftDeletes;

    protected $table = 'fortune_invite_messages';

    protected $fillable = [
        'message',
        'category',
        'is_active',
        'sort_order',
        'send_count',
        'last_sent_at',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'send_count' => 'integer',
        'last_sent_at' => 'datetime',
    ];

    /**
     * Scope: เฉพาะที่เปิดใช้งาน
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: เรียงตาม sort_order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('id', 'asc');
    }

    /**
     * 🎲 สุ่มข้อความที่เปิดใช้งาน 1 ข้อความ
     *
     * ใช้ DB-side random (inRandomOrder) — ไม่ต้องโหลดทั้ง 100 ข้อความเข้า memory
     *
     * @return self|null null ถ้าไม่มีข้อความ active เลย (caller จะ fallback ไปส่งรูป/ทักทายปกติ)
     */
    public static function pickActive(): ?self
    {
        return self::active()->inRandomOrder()->first();
    }

    /**
     * 🚦 Gate กลาง — user คนนี้ "ควรงดส่งรูป" สัปดาห์นี้หรือไม่
     *
     * true เมื่อ: เปิดระบบ rotation (toggle) + ได้รูปแบนเนอร์ในสัปดาห์นี้แล้ว
     * ใช้โดยทุก DM path (comment / reaction / welcome ทักเพจครั้งแรก) ให้ตัดสินใจตรงกัน
     *
     * @param  string  $userId  facebook_user_id / line_user_id
     * @param  string  $platform  'facebook' | 'line'
     */
    public static function shouldSuppressImage(string $userId, string $platform = 'facebook'): bool
    {
        return FortuneTellingSetting::getSettings()->isInviteRotationEnabled()
            && FortuneUserCredit::hasReceivedImageThisWeek($userId, $platform);
    }

    /**
     * 🎯 ตัดสินใจ "ส่งข้อความชวนแทนรูป" สำหรับ user คนนี้
     *
     * คืน message instance ที่จะส่งเป็นข้อความ (caller render + recordSend เอง)
     * หรือ null = ให้ส่งรูปแบนเนอร์ตามปกติ
     *
     * null เกิดเมื่อ: ปิด toggle / ยังไม่ได้รูปสัปดาห์นี้ / ไม่มีข้อความ active เลย
     *
     * @param  string  $userId  facebook_user_id / line_user_id
     * @param  string  $platform  'facebook' | 'line'
     */
    public static function resolveFor(string $userId, string $platform = 'facebook'): ?self
    {
        if (! self::shouldSuppressImage($userId, $platform)) {
            return null;
        }

        return self::pickActive();
    }

    /**
     * 🔘 Quick Replies 3 ปุ่ม ที่แนบไปกับข้อความชวน (Facebook)
     *
     * 1. 🔮 ดูดวงเลย      → INVITE_READ_NOW  (เข้า flow ดูดวง + เคลียร์ opt-out)
     * 2. 🔕 พัก 7 วัน      → INVITE_SNOOZE_7D (พัก DM 7 วัน)
     * 3. 🚫 ไม่ต้องส่งอีก   → INVITE_OPTOUT    (หยุด DM ตาม comment/reaction ถาวร)
     *
     * payload เป็น namespace ใหม่ (INVITE_*) ไม่ชนกับปุ่มเดิมในระบบ
     * route ผ่าน FacebookWebhookController::handleQuickReply()
     *
     * @return array<int, array{content_type: string, title: string, payload: string}>
     */
    public static function quickReplies(): array
    {
        return [
            ['content_type' => 'text', 'title' => '🔮 ดูดวงเลย', 'payload' => 'INVITE_READ_NOW'],
            ['content_type' => 'text', 'title' => '🔕 พัก 7 วัน', 'payload' => 'INVITE_SNOOZE_7D'],
            ['content_type' => 'text', 'title' => '🚫 ไม่ต้องส่งอีก', 'payload' => 'INVITE_OPTOUT'],
        ];
    }

    /**
     * แทนที่ {name} ด้วยชื่อจริงของลูกค้า
     *
     * - ชื่อ valid (เป็นชื่อคนจริง) → "คุณ{name}" → "คุณสมชาย"
     * - ชื่อ invalid/ว่าง (FACEBOOK-XXX, "คุณ", ว่าง) → {name} → "" เพื่อไม่ให้ได้ "คุณคุณ"
     *
     * @param  string|null  $name  ชื่อลูกค้าดิบ
     * @return string ข้อความพร้อมส่ง
     */
    public function render(?string $name): string
    {
        $clean = FortuneUserCredit::isHumanLikeName($name) ? trim((string) $name) : '';

        $text = str_replace('{name}', $clean, (string) $this->message);

        // เก็บกวาด double space ที่เกิดจาก {name} ว่าง (เช่น "ของคุณ  จะ")
        $text = preg_replace('/[ \t]{2,}/u', ' ', $text);

        return trim($text);
    }

    /**
     * บันทึกสถิติการส่ง
     */
    public function recordSend(): void
    {
        $this->increment('send_count');
        $this->update(['last_sent_at' => now()]);
    }
}
