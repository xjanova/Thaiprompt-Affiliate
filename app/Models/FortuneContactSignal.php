<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * FortuneContactSignal Model
 *
 * เก็บพฤติกรรมการส่งข้อความของแต่ละ contact เพื่อตรวจจับ
 * "คนที่ส่งแต่ลิงก์/รูป ไม่เคยพิมพ์คุยจริง" → candidate สำหรับแบน
 *
 * 🛡️ ใช้ร่วมกับ FortuneContactSignalService (บันทึก) + FortuneScanLinkSpammers (สแกน)
 *
 * @property int $id
 * @property string $platform 'facebook' หรือ 'line'
 * @property string $platform_user_id PSID หรือ LINE userId
 * @property string|null $display_name ชื่อที่แสดง (snapshot)
 * @property int $inbound_total ข้อความขาเข้าทั้งหมด
 * @property int $link_image_count จำนวนข้อความที่เป็นลิงก์/รูปล้วน
 * @property int $real_text_count จำนวนข้อความที่พิมพ์คุยจริง
 * @property int $interaction_count จำนวนการ engage (กดปุ่ม/คุยจริง)
 * @property int $wl_link_count จำนวนลิงก์ที่ยิงมาหลังถูก whitelist (ไม่นับรูป/วิดีโอ)
 * @property int $wl_link_days จำนวนวันต่างกันที่ยิงลิงก์หลัง whitelist
 * @property \Carbon\Carbon|null $wl_last_link_day วันล่าสุดที่ยิงลิงก์หลัง whitelist
 * @property int $burst_streak จำนวนลิงก์/รูปที่ส่งติดต่อกัน (คุยจริง = รีเซ็ตเป็น 0)
 * @property \Carbon\Carbon|null $burst_last_at เวลาลิงก์/รูปล่าสุดที่นับเข้า streak
 * @property int $burst_ban_count เคยโดนแบนด้วยกฎ "ยิงติดกัน" มาแล้วกี่ครั้ง
 * @property string|null $last_sample ตัวอย่างข้อความสแปมล่าสุด
 * @property bool $whitelisted เคยคุยจริง/เคยจ่าย → ห้ามแบนอัตโนมัติ
 * @property string $status tracking | flagged | banned | cleared
 * @property \Carbon\Carbon|null $first_seen_at
 * @property \Carbon\Carbon|null $last_seen_at
 * @property \Carbon\Carbon|null $flagged_at
 * @property \Carbon\Carbon|null $banned_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class FortuneContactSignal extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'fortune_contact_signals';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'platform',
        'platform_user_id',
        'display_name',
        'inbound_total',
        'link_image_count',
        'link_caption_count',
        'real_text_count',
        'interaction_count',
        'active_days',
        'wl_link_count',
        'wl_link_days',
        'wl_last_link_day',
        'burst_streak',
        'burst_last_at',
        'burst_ban_count',
        'last_sample',
        'whitelisted',
        'status',
        'first_seen_at',
        'last_seen_at',
        'last_spam_day',
        'flagged_at',
        'banned_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'inbound_total' => 'integer',
        'link_image_count' => 'integer',
        'link_caption_count' => 'integer',
        'real_text_count' => 'integer',
        'interaction_count' => 'integer',
        'active_days' => 'integer',
        'wl_link_count' => 'integer',
        'wl_link_days' => 'integer',
        'burst_streak' => 'integer',
        'burst_last_at' => 'datetime',
        'burst_ban_count' => 'integer',
        'whitelisted' => 'boolean',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'last_spam_day' => 'date',
        'wl_last_link_day' => 'date',
        'flagged_at' => 'datetime',
        'banned_at' => 'datetime',
    ];

    /**
     * Scope: กรองตาม platform
     *
     * @param  string  $platform  ชื่อ platform (facebook, line)
     */
    public function scopeForPlatform(Builder $query, string $platform): Builder
    {
        return $query->where('platform', $platform);
    }

    /**
     * Scope: ผู้ต้องสงสัย "ส่งแต่ลิงก์/รูป ไม่เคยคุย"
     *
     * เงื่อนไข:
     *  - ไม่เคยพิมพ์คุยจริง (real_text_count = 0)
     *  - ลิงก์/รูปล้วน + ลิงก์มีคำประกบสั้น รวมกัน ≥ $minLinkImage ครั้ง
     *  - ส่งสแปมในจำนวนวันต่างกัน ≥ $minActiveDays (ความถี่)
     *  - ไม่ถูก whitelist (ไม่เคยจ่าย/ไม่เคย engage)
     *  - ยัง tracking อยู่ (ยังไม่ถูกแบน/เคลียร์)
     *
     * @param  int  $minLinkImage  จำนวนลิงก์/รูป+caption ขั้นต่ำที่ถือว่าเป็นสแปม
     * @param  int  $minActiveDays  จำนวนวันที่ส่งสแปมต่างกันขั้นต่ำ (1 = ไม่บังคับความถี่)
     */
    public function scopeSuspects(Builder $query, int $minLinkImage = 3, int $minActiveDays = 1): Builder
    {
        return $query->where('whitelisted', false)
            ->where('status', 'tracking')
            ->where('real_text_count', 0)
            ->where('active_days', '>=', $minActiveDays)
            ->whereRaw('(link_image_count + link_caption_count) >= ?', [$minLinkImage]);
    }

    /**
     * Scope: ผู้ต้องสงสัย "ราง 2" — ถูก whitelist แล้วแต่ยังยิงลิงก์รัวๆ
     *
     * ต่างจาก scopeSuspects ตรงที่:
     *  - ไม่สนใจ whitelisted / real_text_count (คนกลุ่มนี้เคยคุยจริงมาก่อนทั้งนั้น)
     *  - นับเฉพาะ wl_link_* ซึ่งนับ "ลิงก์" อย่างเดียว ไม่นับรูป/วิดีโอ
     *    → ลูกค้าที่ส่งสลิปซ้ำๆ ไม่มีวันเข้าเกณฑ์นี้
     *  - counter เริ่มที่ 0 ทุกแถวตอน migrate → ไม่มีการแบนย้อนหลัง
     *
     * 🛡️ ยังต้องผ่าน safety net "ไม่เคยจ่ายเงิน" ในคำสั่งสแกนอีกชั้นก่อนแบนจริง
     *
     * @param  int  $minLinks  จำนวนลิงก์ขั้นต่ำหลัง whitelist ที่ถือว่าเป็นสแปม
     * @param  int  $minActiveDays  จำนวนวันต่างกันขั้นต่ำ (ความถี่ — 2 = ยิงข้ามวัน)
     */
    public function scopeWhitelistedSpammers(Builder $query, int $minLinks = 5, int $minActiveDays = 2): Builder
    {
        return $query->where('status', 'tracking')
            ->where('wl_link_count', '>=', $minLinks)
            ->where('wl_link_days', '>=', $minActiveDays);
    }
}
