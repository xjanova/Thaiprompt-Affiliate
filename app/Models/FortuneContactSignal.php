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
        'real_text_count',
        'interaction_count',
        'last_sample',
        'whitelisted',
        'status',
        'first_seen_at',
        'last_seen_at',
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
        'real_text_count' => 'integer',
        'interaction_count' => 'integer',
        'whitelisted' => 'boolean',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
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
     *  - ส่งลิงก์/รูป ≥ $minLinkImage ครั้ง
     *  - ไม่ถูก whitelist (ไม่เคยจ่าย/ไม่เคย engage)
     *  - ยัง tracking อยู่ (ยังไม่ถูกแบน/เคลียร์)
     *
     * @param  int  $minLinkImage  จำนวนลิงก์/รูปขั้นต่ำที่ถือว่าเป็นสแปม
     */
    public function scopeSuspects(Builder $query, int $minLinkImage = 3): Builder
    {
        return $query->where('whitelisted', false)
            ->where('status', 'tracking')
            ->where('real_text_count', 0)
            ->where('link_image_count', '>=', $minLinkImage);
    }
}
